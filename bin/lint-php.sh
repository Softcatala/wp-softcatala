#!/usr/bin/env bash
#
# PHPCS + PHPMD over classes/.
#
#   bin/lint-php.sh                     # everything (1200+ pre-existing findings)
#   bin/lint-php.sh --changed origin/master   # only files touched since <ref>
#
# The full run is informational: the ruleset was written for WPCS 1 and the
# codebase predates the far stricter WPCS 3, so a blocking repo-wide gate would
# never go green. CI gates the --changed scope instead, which keeps new and
# edited code to the standard without demanding a 1200-violation cleanup first.
#
# Runs the tools directly, so it works both inside the test container
# (./bin/test.sh) and on a CI runner with the same Composer dependencies.

set -uo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

SCOPE=all
BASE=""

if [ "${1-}" = "--changed" ]; then
	SCOPE=changed
	BASE=${2-origin/master}
fi

if [ ! -x vendor/bin/phpcs ]; then
	echo "Composer dependencies are missing. Run 'composer install' first." >&2
	exit 1
fi

FILES=()

if [ "$SCOPE" = changed ]; then
	# Diffing from the merge base rather than the base tip keeps unrelated
	# changes on master out of the scope, and diffing to the working tree (no
	# ...HEAD) means uncommitted edits are linted too when run locally.
	MERGE_BASE=$(git merge-base "$BASE" HEAD 2>/dev/null || echo "$BASE")

	# Only classes/ is covered by phpcs.xml, so restrict to it.
	while IFS= read -r file; do
		[ -n "$file" ] && FILES+=("$file")
	done < <(git diff --name-only --diff-filter=ACMR "$MERGE_BASE" -- classes 2>/dev/null | grep '\.php$' || true)

	if [ ${#FILES[@]} -eq 0 ]; then
		echo "No changed files under classes/ since ${BASE}; nothing to lint."
		exit 0
	fi

	echo "Linting ${#FILES[@]} changed file(s) since ${BASE}:"
	printf '  %s\n' "${FILES[@]}"
fi

status=0

echo
echo "--- PHPCS ---"
if [ "$SCOPE" = changed ]; then
	vendor/bin/phpcs "${FILES[@]}" || status=1
else
	vendor/bin/phpcs || status=1
fi

echo
echo "--- PHPMD ---"
# phpmd 2.x and its pdepend dependency still emit implicit-nullable deprecations
# on PHP 8.4, which drown out the actual findings.
PHPMD=(php -d error_reporting="E_ALL & ~E_DEPRECATED" vendor/bin/phpmd)
if [ "$SCOPE" = changed ]; then
	# phpmd takes a comma-separated list of paths.
	printf -v joined '%s,' "${FILES[@]}"
	"${PHPMD[@]}" "${joined%,}" text ./phpmd.xml || status=1
else
	"${PHPMD[@]}" classes/ text ./phpmd.xml --exclude 'vendor/' || status=1
fi

exit $status
