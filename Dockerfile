FROM ubuntu:18.04

LABEL maintainer="Alfredo Martinez"

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
    && apt-get install -y gnupg tzdata \
    && echo "UTC" > /etc/timezone \
    && dpkg-reconfigure -f noninteractive tzdata

RUN apt-get update \
    && apt-get install -y curl zip unzip git supervisor sqlite3 \
    nginx php7.2-fpm php7.2-cli \
    php7.2-pgsql php7.2-sqlite3 php7.2-gd \
    php7.2-curl php7.2-memcached \
    php7.2-imap php7.2-mysql php7.2-mbstring \
    php7.2-xml php7.2-zip php7.2-bcmath php7.2-soap \
    php7.2-intl php7.2-readline php7.2-xdebug \
    php-msgpack php-igbinary \
    vim iputils-ping \
    && php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer \
    && mkdir /run/php \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    && echo "daemon off;" >> /etc/nginx/nginx.conf

RUN ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

COPY docker_config/default /etc/nginx/sites-available/default
COPY docker_config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker_config/php-fpm.conf /etc/php/7.2/fpm/php-fpm.conf
COPY docker_config/start-container.sh /usr/bin/start-container
RUN chmod +x /usr/bin/start-container

WORKDIR /var/www/html
COPY . /var/www/html
RUN composer install
RUN chgrp -R www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache \
    && chmod -R 777 vendor

RUN php artisan key:generate --no-interaction
RUN php artisan storage:link
RUN php artisan migrate --seed

ENTRYPOINT ["start-container"]