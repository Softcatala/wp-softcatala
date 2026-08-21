#!/usr/bin/env bash
# Starts and seeds the isolated local WordPress browser-test environment.

set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

dev_compose=(docker compose -p softcatala-dev -f docker-compose.dev.yml)
dev_port="${SC_DEV_PORT:-8080}"
dev_url="http://127.0.0.1:${dev_port}"

case "${1:-up}" in
	up)
		"${dev_compose[@]}" up -d --build db wordpress

		for _ in $(seq 1 90); do
			if curl -fsS "${dev_url}/wp-admin/install.php" >/dev/null 2>&1 \
				|| curl -fsS "${dev_url}/" >/dev/null 2>&1; then
				break
			fi
			sleep 1
		done

		wp_cli=("${dev_compose[@]}" run --rm cli wp --path=/var/www/html)

		if ! "${wp_cli[@]}" core is-installed >/dev/null 2>&1; then
			"${wp_cli[@]}" core install \
				--url="${dev_url}" \
				--title="Softcatalà local" \
				--admin_user=admin \
				--admin_password=softcatala-local \
				--admin_email=local@example.invalid \
				--skip-email
		fi

		"${wp_cli[@]}" plugin install advanced-custom-fields wordpress-seo --activate
		"${wp_cli[@]}" language core install ca --activate
		"${wp_cli[@]}" theme activate wp-softcatala
		"${wp_cli[@]}" eval-file /var/www/html/wp-content/themes/wp-softcatala/.docker/dev/seed.php
		"${wp_cli[@]}" rewrite flush --hard

		echo "Softcatalà local disponible a ${dev_url}"
		;;
	down)
		"${dev_compose[@]}" down
		;;
	reset)
		"${dev_compose[@]}" down --volumes
		;;
	wp)
		shift
		"${dev_compose[@]}" run --rm cli wp --path=/var/www/html "$@"
		;;
	*)
		echo "ús: $0 [up|down|reset|wp ...]" >&2
		exit 1
		;;
esac
