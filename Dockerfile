FROM node:20-bookworm AS client-builder

WORKDIR /app

COPY src/client ./src/client

RUN cd src/client \
    && node .yarn/releases/yarn-4.1.0.cjs install --immutable \
    && node .yarn/releases/yarn-4.1.0.cjs build

FROM php:8.3-apache-bookworm

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN set -ex; \
	savedAptMark="$(apt-mark showmanual)"; \
	apt-get update; \
	apt-get install -y --no-install-recommends \
		libfreetype6-dev \
		libjpeg-dev \
		libpng-dev \
		libzip-dev \
		libldap2-dev \
		libicu-dev \
		unzip \
	; \
	\
	docker-php-ext-configure gd --with-freetype --with-jpeg; \
	docker-php-ext-configure ldap \
	    --with-libdir=lib/$(uname -m)-linux-gnu/ \
	; \
	\
	docker-php-ext-install -j "$(nproc)" \
		gd \
		opcache \
		intl \
		pdo_mysql \
		zip \
		ldap \
	; \
	\
	apt-mark auto '.*' > /dev/null; \
	apt-mark manual unzip; \
	apt-mark manual $savedAptMark; \
	ldd "$(php -r 'echo ini_get("extension_dir");')"/*.so \
		| awk '/=>/ { so = $(NF-1); if (index(so, "/usr/local/") == 1) { next }; gsub("^/(usr/)?", "", so); print so }' \
		| sort -u \
		| xargs -r dpkg-query -S \
		| cut -d: -f1 \
		| sort -u \
		| xargs -rt apt-mark manual; \
	\
	apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
	rm -rf /var/cache/apt/archives; \
	rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . /var/www/html
COPY --from=client-builder /app/web/dist /var/www/html/web/dist
COPY docker-entrypoint.sh /usr/local/bin/cia-ferias-entrypoint

RUN set -ex; \
	cd /var/www/html/src; \
	composer install --no-dev --no-scripts --optimize-autoloader; \
	mkdir -p /var/www/html/lib/confs/cryptokeys /var/www/html/src/cache /var/www/html/src/log /var/www/html/src/config/proxy; \
	chown -R www-data:www-data /var/www/html; \
	chmod -R 775 /var/www/html/lib/confs /var/www/html/src/cache /var/www/html/src/log /var/www/html/src/config /usr/local/bin/cia-ferias-entrypoint

RUN { \
		echo 'opcache.memory_consumption=128'; \
		echo 'opcache.interned_strings_buffer=8'; \
		echo 'opcache.max_accelerated_files=4000'; \
		echo 'opcache.revalidate_freq=60'; \
		echo 'opcache.fast_shutdown=1'; \
		echo 'opcache.enable_cli=1'; \
	} > /usr/local/etc/php/conf.d/opcache-recommended.ini; \
	\
	if command -v a2enmod; then \
		a2enmod rewrite; \
	fi;

ENTRYPOINT ["cia-ferias-entrypoint"]
CMD ["apache2-foreground"]
