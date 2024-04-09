FROM wordpress:latest
COPY xhprof.ini $PHP_INI_DIR/conf.d/xhprof.ini
RUN pecl install xhprof-2.3.9 \
	&& docker-php-ext-enable xhprof \
