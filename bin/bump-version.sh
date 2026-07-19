#!/usr/bin/env bash
set -euo pipefail

BUMP="patch"
for arg in "$@"; do
	case $arg in
		--major) BUMP="major" ;;
		--minor) BUMP="minor" ;;
		--patch) BUMP="patch" ;;
		*) echo "Unknown argument: $arg" >&2; exit 1 ;;
	esac
done

REPO="softcatala/wp-softcatala"
API_URL="https://api.github.com/repos/${REPO}/releases/latest"

RESPONSE=$(curl -sf "$API_URL" 2>/dev/null) || {
	echo "Failed to fetch latest release from GitHub (is there a published release?)" >&2
	exit 1
}

TAG=$(echo "$RESPONSE" | grep '"tag_name"' | sed 's/.*"tag_name": *"\([^"]*\)".*/\1/')
if [[ -z "$TAG" ]]; then
	echo "Could not parse tag_name from GitHub response" >&2
	exit 1
fi

CURRENT="${TAG#v}"
echo "Latest published version: $CURRENT"

IFS='.' read -r MAJOR MINOR PATCH <<< "$CURRENT"

case $BUMP in
	major) MAJOR=$((MAJOR + 1)); MINOR=0; PATCH=0 ;;
	minor) MINOR=$((MINOR + 1)); PATCH=0 ;;
	patch) PATCH=$((PATCH + 1)) ;;
esac

NEW="$MAJOR.$MINOR.$PATCH"
echo "Bumping $BUMP → $NEW"

ROOT="$(cd "$(dirname "$0")/.." && pwd)"

# portable in-place argument for both GNU sed and Mac OSX sed
if [[ $(uname -s) == 'Darwin' ]]; then
	sed_i=(-i '')
else
	sed_i=(-i)
fi

sed "${sed_i[@]}" "s/^Version: .*/Version: $NEW/" "$ROOT/style.css"
sed "${sed_i[@]}" "s/define( 'WP_SOFTCATALA_VERSION', '[^']*' );/define( 'WP_SOFTCATALA_VERSION', '$NEW' );/" "$ROOT/functions.php"

echo "Updated style.css and functions.php to $NEW"
