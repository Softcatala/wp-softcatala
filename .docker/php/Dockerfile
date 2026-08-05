# PHP image for running the theme's test suite and static analysis locally.
# Mirrors what .github/workflows/ci.yml installs on the runner, so `bin/test.sh`
# and CI exercise the same toolchain.
ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-cli

RUN apt-get update \
	&& apt-get install -y --no-install-recommends git unzip \
	&& rm -rf /var/lib/apt/lists/* \
	&& docker-php-ext-install mysqli

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
	WP_TESTS_DIR=/wp-test-suite/wordpress-tests-lib \
	WP_CORE_DIR=/wp-test-suite/wordpress

WORKDIR /app
