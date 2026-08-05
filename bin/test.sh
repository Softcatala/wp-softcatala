#!/usr/bin/env bash
#
# Runs the same checks as CI, inside Docker. Docker is the only thing that
# needs to exist on the host -- no PHP, Composer, Node or npm.
#
#   ./bin/test.sh              # everything CI runs
#   ./bin/test.sh phpunit      # WordPress test suite
#   ./bin/test.sh lint         # phpcs + phpmd over classes/ (full, noisy)
#   ./bin/test.sh lint-changed # phpcs + phpmd over files changed vs master
#   ./bin/test.sh phpcs        # coding standards (classes/)
#   ./bin/test.sh phpcbf       # auto-fix coding standards
#   ./bin/test.sh phpmd        # mess detector
#   ./bin/test.sh frontend     # vitest + tsc
#   ./bin/test.sh build        # frontend production build into static/
#   ./bin/test.sh shell        # interactive shell in the PHP container
#   ./bin/test.sh down         # stop containers and drop the volumes
#
# Overridable: PHP_VERSION (default 8.4, matching production), WP_VERSION
# (default latest), WP_MULTISITE (default 0).
#
#   PHP_VERSION=8.2 ./bin/test.sh phpunit

set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

COMPOSE="docker compose -f docker-compose.test.yml"

if ! docker info >/dev/null 2>&1; then
	echo "Docker does not seem to be running." >&2
	exit 1
fi

# Composer deps live in the bind-mounted checkout, so this is a no-op after the
# first run. Installed by the container's PHP, never the host's.
php_deps() {
	$COMPOSE run --rm --no-deps php sh -c \
		'[ -d vendor ] || composer install --no-interaction --no-progress'
}

node_deps() {
	$COMPOSE run --rm node sh -c \
		'[ -d node_modules/vitest ] || npm ci --no-audit --no-fund'
}

run_phpunit() {
	php_deps
	$COMPOSE run --rm php sh -c '
		set -e
		bash bin/install-wp-tests.sh "$WP_DB_NAME" "$WP_DB_USER" "$WP_DB_PASS" "$WP_DB_HOST" "$WP_VERSION" true
		vendor/bin/phpunit "$@"
	' -- "$@"
}

run_phpcs() {
	php_deps
	$COMPOSE run --rm --no-deps php vendor/bin/phpcs "$@"
}

run_phpcbf() {
	php_deps
	$COMPOSE run --rm --no-deps php vendor/bin/phpcbf "$@"
}

run_phpmd() {
	php_deps
	$COMPOSE run --rm --no-deps php composer phpmd
}

run_lint() {
	php_deps
	$COMPOSE run --rm --no-deps php bash bin/lint-php.sh "$@"
}

run_frontend() {
	node_deps
	$COMPOSE run --rm node npm run typecheck
	$COMPOSE run --rm node npm test
}

run_build() {
	node_deps
	$COMPOSE run --rm node npm run build
}

case "${1-all}" in
	phpunit)      shift; run_phpunit "$@" ;;
	lint)         run_lint ;;
	lint-changed) shift; run_lint --changed "${1-origin/master}" ;;
	phpcs)        shift; run_phpcs "$@" ;;
	phpcbf)       shift; run_phpcbf "$@" ;;
	phpmd)        run_phpmd ;;
	frontend)     run_frontend ;;
	build)        run_build ;;
	shell)        php_deps; $COMPOSE run --rm php bash ;;
	down)         $COMPOSE down --volumes --remove-orphans ;;
	all)
		# Mirrors the CI jobs: frontend checks, lint on changed files, tests.
		# Lint findings don't stop the run, exactly as they don't block CI.
		run_frontend
		run_lint --changed "${2-origin/master}" \
			|| echo ">>> Lint findings above are advisory (see bin/lint-php.sh); continuing."
		run_phpunit
		;;
	*)
		echo "usage: $0 [all|phpunit|lint|lint-changed|phpcs|phpcbf|phpmd|frontend|build|shell|down]" >&2
		exit 1
		;;
esac
