#!/usr/bin/env bash
#
# Installs WordPress core and the wordpress-develop PHPUnit test suite so
# `phpunit` can bootstrap a real WordPress.
#
#   usage: bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]
#
# Unlike the classic WordPress scaffold version this script needs neither
# subversion nor a MySQL client binary: the test suite is pulled from the
# wordpress-develop tarball and the database is created through PHP's mysqli,
# which is already required to run the tests at all.

set -euo pipefail

if [ $# -lt 3 ]; then
	echo "usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]"
	exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}
SKIP_DB_CREATE=${6-false}

WP_TESTS_DIR=${WP_TESTS_DIR-/tmp/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR-/tmp/wordpress}
WP_CORE_DIR=${WP_CORE_DIR%/}
WP_CORE_THEME_DIR="$WP_CORE_DIR/wp-content/themes/wp-softcatala"
THEME_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)

download() {
	if command -v curl >/dev/null; then
		# --fail so a 404 aborts here instead of saving an error page that only
		# blows up later as "not in gzip format".
		curl -fsSL "$1" -o "$2"
	else
		wget -nv -O "$2" "$1"
	fi
}

# wordpress.org ships releases as 6.7, wordpress-develop tags them as 6.7.0.
develop_tag() {
	if [[ $1 =~ ^[0-9]+\.[0-9]+$ ]]; then
		echo "tags/$1.0"
	else
		echo "tags/$1"
	fi
}

resolve_version() {
	if [ "$WP_VERSION" = 'nightly' ] || [ "$WP_VERSION" = 'trunk' ]; then
		DEVELOP_REF='trunk'
		return
	fi

	if [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+(\.[0-9]+)?$ ]]; then
		DEVELOP_REF=$(develop_tag "$WP_VERSION")
		return
	fi

	# http serves a single offer, whereas https serves multiple; we only want one.
	download http://api.wordpress.org/core/version-check/1.7/ /tmp/wp-latest.json
	local latest
	latest=$(grep -o '"version":"[^"]*' /tmp/wp-latest.json | head -1 | sed 's/"version":"//')

	if [ -z "$latest" ]; then
		echo "Latest WordPress version could not be found" >&2
		exit 1
	fi

	WP_VERSION=$latest
	DEVELOP_REF=$(develop_tag "$latest")
}

discard_stale_install() {
	# Both directories are usually cached (a Docker volume locally, nothing in
	# CI). Without this, asking for a different WP_VERSION would silently keep
	# whatever was installed first.
	local marker="$WP_CORE_DIR/.sc-wp-version"

	if [ -f "$marker" ] && [ "$(cat "$marker")" != "$WP_VERSION" ]; then
		echo "Cached WordPress $(cat "$marker") differs from requested $WP_VERSION; reinstalling."
		rm -rf "$WP_CORE_DIR" "$WP_TESTS_DIR"
	fi
}

install_wp() {
	if [ -d "$WP_CORE_DIR" ] && [ -f "$WP_CORE_DIR/wp-load.php" ]; then
		return
	fi

	mkdir -p "$WP_CORE_DIR"

	local archive='latest'
	if [ "$WP_VERSION" = 'nightly' ] || [ "$WP_VERSION" = 'trunk' ]; then
		download https://wordpress.org/nightly-builds/wordpress-latest.zip /tmp/wordpress-nightly.zip
		unzip -q /tmp/wordpress-nightly.zip -d /tmp/wordpress-nightly
		mv /tmp/wordpress-nightly/wordpress/* "$WP_CORE_DIR"
		return
	fi

	if [ "$WP_VERSION" != 'latest' ]; then
		archive="wordpress-$WP_VERSION"
	fi

	download "https://wordpress.org/${archive}.tar.gz" /tmp/wordpress.tar.gz
	tar --strip-components=1 -zxmf /tmp/wordpress.tar.gz -C "$WP_CORE_DIR"

	echo "$WP_VERSION" > "$WP_CORE_DIR/.sc-wp-version"
}

install_test_suite() {
	if [ ! -d "$WP_TESTS_DIR/includes" ]; then
		mkdir -p "$WP_TESTS_DIR"

		# The test suite only ships in wordpress-develop, not in the release
		# tarball. Pull the two directories the bootstrap needs plus the config
		# sample, rather than the whole ~100MB working tree.
		download "https://github.com/WordPress/wordpress-develop/archive/refs/${DEVELOP_REF}.tar.gz" /tmp/wordpress-develop.tar.gz
		tar -zxf /tmp/wordpress-develop.tar.gz -C "$WP_TESTS_DIR" --strip-components=3 --wildcards \
			'*/tests/phpunit/includes' '*/tests/phpunit/data'
		tar -zxf /tmp/wordpress-develop.tar.gz -C "$WP_TESTS_DIR" --strip-components=1 --wildcards \
			'*/wp-tests-config-sample.php'
	fi

	if [ ! -f "$WP_TESTS_DIR/wp-tests-config.php" ]; then
		cp "$WP_TESTS_DIR/wp-tests-config-sample.php" "$WP_TESTS_DIR/wp-tests-config.php"

		sed -i "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR/':" "$WP_TESTS_DIR/wp-tests-config.php"
		sed -i "s/youremptytestdbnamehere/$DB_NAME/" "$WP_TESTS_DIR/wp-tests-config.php"
		sed -i "s/yourusernamehere/$DB_USER/" "$WP_TESTS_DIR/wp-tests-config.php"
		sed -i "s/yourpasswordhere/$DB_PASS/" "$WP_TESTS_DIR/wp-tests-config.php"
		sed -i "s|localhost|${DB_HOST}|" "$WP_TESTS_DIR/wp-tests-config.php"
	fi
}

install_db() {
	if [ "$SKIP_DB_CREATE" = "true" ]; then
		return 0
	fi

	php -r '
		$host = $argv[1];
		$port = 3306;
		if ( strpos( $host, ":" ) !== false ) {
			list( $host, $port ) = explode( ":", $host, 2 );
		}
		$db = @new mysqli( $host, $argv[2], $argv[3], "", (int) $port );
		if ( $db->connect_errno ) {
			fwrite( STDERR, "Could not connect to the database: " . $db->connect_error . "\n" );
			exit( 1 );
		}
		$db->query( "CREATE DATABASE IF NOT EXISTS `" . $db->real_escape_string( $argv[4] ) . "`" );
	' "$DB_HOST" "$DB_USER" "$DB_PASS" "$DB_NAME"
}

install_plugins() {
	# The theme calls ACF's get_field() from save_post hooks and providers, so
	# without it a large part of the suite errors out. The free build from
	# wordpress.org is enough for what the tests touch.
	local plugin_dir="$WP_CORE_DIR/wp-content/plugins"

	if [ -d "$plugin_dir/advanced-custom-fields" ]; then
		return
	fi

	mkdir -p "$plugin_dir"
	download https://downloads.wordpress.org/plugin/advanced-custom-fields.latest-stable.zip /tmp/acf.zip
	unzip -q -o /tmp/acf.zip -d "$plugin_dir"
}

install_theme() {
	# Symlinked rather than copied: the tests switch to this theme, and a copy
	# would go stale on every edit (and drag in node_modules/vendor).
	mkdir -p "$(dirname "$WP_CORE_THEME_DIR")"
	rm -rf "$WP_CORE_THEME_DIR"
	ln -s "$THEME_DIR" "$WP_CORE_THEME_DIR"
}

resolve_version
discard_stale_install
install_wp
install_test_suite
install_db
install_plugins
install_theme

echo "WordPress $WP_VERSION test suite ready in $WP_TESTS_DIR"
