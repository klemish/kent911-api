FROM node:19-alpine AS node
FROM php:8.2-fpm-alpine
COPY --from=node /usr/lib /usr/lib
COPY --from=node /usr/local/share /usr/local/share
COPY --from=node /usr/local/lib /usr/local/lib
COPY --from=node /usr/local/include /usr/local/include
COPY --from=node /usr/local/bin /usr/local/bin

# Add Build Dependencies
RUN apk add --update --no-cache --virtual .build-deps  \
    zlib-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libxml2-dev \
    bzip2-dev \
    libzip-dev

# Add Production Dependencies
RUN apk add --update --no-cache \
    jpegoptim \
    pngquant \
    optipng \
    supervisor \
    nano \
    icu-dev \
    freetype-dev \
    nginx \
    postgresql-dev \
    oniguruma-dev \
    npm \
    zip \
    unzip \
    libzip-dev

# Install PHP Redis
RUN apk --no-cache add pcre-dev ${PHPIZE_DEPS} \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del pcre-dev ${PHPIZE_DEPS} \
    && rm -rf /tmp/pear

# Configure & Install Extension
RUN docker-php-ext-install pdo pdo_pgsql zip gd mbstring xml bcmath soap intl
RUN docker-php-ext-enable pdo pdo_pgsql zip gd mbstring xml bcmath soap intl

# Setup Working Dir
WORKDIR /var/www

ADD . /var/www

# Add Composer
RUN curl -s https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="./vendor/bin:$PATH"
RUN cd /var/www
RUN composer install --no-dev --optimize-autoloader

# Setup Crond and Supervisor
COPY ./scheduler /etc/cron.d/scheduler
RUN chmod 0644 /etc/cron.d/scheduler \
    && crontab /etc/cron.d/scheduler

ADD start-container /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container
ADD supervisord.conf /etc/supervisor/conf.d/supervisord.conf
ADD /docker/8.2/php.ini $PHP_INI_DIR/conf.d/
ADD nginx.conf /etc/nginx/conf.d/


RUN cd /var/www
RUN npm install && npm run build
RUN php artisan telescope:install
COPY ./public ./html

# Remove Build Dependencies
RUN apk del -f .build-deps
EXPOSE 80
ENTRYPOINT [ "start-container" ]
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
