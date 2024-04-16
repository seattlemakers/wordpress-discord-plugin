FROM wordpress:latest
RUN pecl install xhprof-2.3.9 \
	&& pecl install xdebug \
	&& docker-php-ext-enable xhprof xdebug

COPY conf.d/* $PHP_INI_DIR/conf.d/
