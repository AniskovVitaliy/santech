FROM php:8.2-fpm

COPY ./docker/php/php.ini /usr/local/etc/php/conf.d/php.ini

CMD touch /usr/local/php-logs/php-fpm.log && \
  chmod 666 /usr/local/php-logs/php-fpm.log && \
  php-fpm -F

# base command for ubuntu of container
RUN apt-get update  \
    && apt-get install -y \
      apt-utils \
      curl \
      wget \
      libmagickwand-dev \
      libbz2-dev \
      redis-server \
      libpq-dev \
      libpng-dev \
      libgmp-dev \
      libzip-dev \
      zip \
      unzip \
      git \
    && docker-php-ext-configure \
      gd --enable-gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install mysqli \
      pdo_mysql \
      bcmath \
      zip \
      -j$(nproc) gd \
    && docker-php-ext-enable mysqli \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# xdebug
RUN pecl install xdebug && \
    docker-php-ext-enable xdebug

COPY ./docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# composer
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN curl -sS https://getcomposer.org/installer | php -- \
    --filename=composer \
    --install-dir=/usr/local/bin

# base path
WORKDIR /var/www
