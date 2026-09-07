# Keycloak SSO

OIDC authorization-code flow with PKCE against a single issuer. Ported from
Minairo's `minairo/auth/` — the match order in `UserResolver` and the claim
validation in `OidcClient` deliberately mirror `service.py` and `oidc.py`, so
fix bugs in both.

| File | Role |
| --- | --- |
| `mode.php` | DISABLED / ENABLED / ENFORCED, and the fallback rules |
| `config.php` | Issuer credentials from wp-config; `null` when unconfigured |
| `oidc-client.php` | Discovery, PKCE, code exchange, ID-token verification |

ID-token signatures are verified with `firebase/php-jwt` (`^7.1`; everything
below 7.0 is blocked by CVE-2025-45769). It is declared in the theme's own
`composer.json`, so the site pulls it into `wp-content/vendor` and the
autoloader cascade in `functions.php` finds it either way.

OIDC Core 3.1.3.7 would let a confidential client skip the signature and rely
on TLS to the token endpoint instead. We do not: that safety would be a
property of the deployment, not of this code. `WP_Http`'s `https_ssl_verify`
filter is global, so any plugin can disable peer verification site-wide, and an
issuer reached over an internal `http://` hop has no TLS at all — both fail
open, silently. Two checks stay ours because php-jwt does not make them: `iss`
and `aud` (it validates neither), and the *presence* of `exp` (it validates the
value only when the claim exists, `JWT.php:188`).
| `user-resolver.php` | Claims → `WP_User`, or `SsoDenied` |
| `login-flow.php` | wp-login.php wiring and enforcement |

## Configuration

Constants in `wp-config-docker.php`, from the compose environment:

```php
define( 'SC_SSO_MODE',          getenv( 'SC_SSO_MODE' ) ?: 'DISABLED' );
define( 'SC_SSO_ISSUER',        getenv( 'SC_SSO_ISSUER' ) ?: '' );        // https://…/realms/softcatala
define( 'SC_SSO_CLIENT_ID',     getenv( 'SC_SSO_CLIENT_ID' ) ?: '' );
define( 'SC_SSO_CLIENT_SECRET', getenv( 'SC_SSO_CLIENT_SECRET' ) ?: '' );
```

## Keycloak client

Confidential (`Client authentication` on), `Standard flow` enabled. Two URI
settings, and both are needed — logout breaks silently without the second:

```
Valid redirect URIs:              https://www.softcatala.org/wp/wp-login.php?action=sc-oidc-callback
Valid post logout redirect URIs:  https://www.softcatala.org/
```

Note the **`/wp/`**. Core lives there (`wordpress-install-dir` in the site's
composer.json), so `wp_login_url()` — which is `site_url('wp-login.php')`, not
`home_url()` — resolves under it. Confirm the real value rather than trusting
this line:

```bash
wp option get siteurl        # the redirect URI is <siteurl>/wp-login.php?action=sc-oidc-callback
```

If Keycloak answers `Invalid parameter: redirect_uri`, it is almost always the
query string or a trailing slash; `…/wp/wp-login.php*` as the pattern resolves
it. No `Web origins` entry is needed: every leg is a server-side redirect or a
back-channel POST, never a browser XHR.

Known behaviour: logout sends `client_id` + `post_logout_redirect_uri` but no
`id_token_hint` (we do not retain the ID token), so Keycloak shows a logout
confirmation screen rather than returning straight away.

Under ENFORCED, `enforce()` deliberately lets a few wp-login.php actions
through instead of bouncing them to Keycloak: `postpass` (password-protected
posts, used by anonymous visitors), `confirmaction` (privacy-request links),
`?loggedout=1`, and `?interim-login=1` (the expired-session modal, an iframe
Keycloak refuses to render in).

## Modes

- **DISABLED** — no routes registered at all; the callback is absent, not just
  hidden. This is the default, so an install that sets nothing is untouched.
- **ENABLED** — a Keycloak button alongside the normal password form.
- **ENFORCED** — Keycloak only. `wp-login.php` redirects to the issuer, password
  login is refused for **everybody**, and password reset is disabled.

Two fallbacks, both failing towards "the site still lets people in": an
unrecognised `SC_SSO_MODE` falls back to DISABLED, and so does any mode whose
issuer credentials are incomplete. Both raise an admin notice.

## Break-glass

ENFORCED leaves no password-reachable account, by design. To get back in:

1. `SC_SSO_MODE=ENABLED` in the compose environment
2. restart the container
3. log in with a password

Note that ENFORCED also disables WordPress recovery mode, which still needs a
normal login after its email link — so this is the only recovery path.

A Keycloak outage is *not* a lockout: WP auth cookies are validated locally, so
everyone already logged in stays logged in. Only new logins stop.

## Identity

`sub` is the identity, stored in the `sc_oidc_subject` usermeta. Email is only
ever a **one-time bridge** from a WP account predating SSO to its Keycloak
account — matching on email every login would hand an existing account to
anyone who can get that address onto a Keycloak user.

```
1. usermeta sc_oidc_subject == sub   → log in
2. a WP user holds claims.email      → link once, then log in
3. otherwise                         → provision as subscriber
```

Refusals (`SsoDenied`): `subject_conflict` (a second Keycloak account claiming a
linked address), `email_conflict` (unverified address), `inactive`, `no_email`,
`no_subject`, `ambiguous_subject`.

Provisioning is automatic because realm membership is already a deliberate
sysadmin act — but at the capability floor, with promotion left to a human in
wp-admin.

### `status_member`

The pre-existing ACF field `author.php` uses for the members archive doubles as
an inactive check. It is **defence in depth, not the gate**: offboarding deletes
the Keycloak account, so an ex-member normally never reaches this code. It
covers the window where the two systems disagree, and stops a returning member's
new `sub` auto-linking to their old role.

**An absent value means active.** The field postdates most accounts, and
`author.php` treats a missing row as a current member. Reading it as inactive
would lock out everyone who predates it, with no password fallback under
ENFORCED. Only an explicitly stored falsy value denies.

## Deliberately not done

- **Realm roles → WP roles.** Promotion is manual in wp-admin. With a
  sysadmin-managed realm, mapping a group claim could remove that step.
- **Deactivation lag.** Disabling someone in Keycloak does not touch their live
  WP cookie; they stay logged in until it expires (up to 14 days with
  remember-me). Closing this needs a refresh-token check on
  `determine_current_user`.
- **Application Passwords are untouched.** The `sc/v1/tasca` endpoints
  authenticate with them and Keycloak has no equivalent for machine clients, so
  ENFORCED exempts that path. `AuthLoginFlowTest` guards it.
