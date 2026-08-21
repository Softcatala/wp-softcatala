FROM wordpress:php8.4-apache

RUN a2enmod rewrite headers \
	&& printf '%s\n' \
		'Alias /themes/wp-softcatala /var/www/html/wp-content/themes/wp-softcatala' \
		'<Directory /var/www/html/wp-content/themes/wp-softcatala>' \
		'    Options FollowSymLinks' \
		'    AllowOverride All' \
		'    Require all granted' \
		'</Directory>' \
		> /etc/apache2/conf-available/softcatala-dev.conf \
	&& a2enconf softcatala-dev
