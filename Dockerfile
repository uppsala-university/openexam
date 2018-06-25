FROM php:7.1-apache

### Phalcon installation ###
# Taken from: https://github.com/phalcon/dockerfiles/blob/master/php-fpm/7.1-min/Dockerfile
ENV PHALCON_VERSION=3.2.4

RUN curl -sSL "https://codeload.github.com/phalcon/cphalcon/tar.gz/v${PHALCON_VERSION}" | tar -xz \
    && cd cphalcon-${PHALCON_VERSION}/build \
    && ./install \
    && cp ../tests/_ci/phalcon.ini $(php-config --configure-options | grep -o "with-config-file-scan-dir=\([^ ]*\)" | awk -F'=' '{print $2}') \
    && cd ../../ \
    && rm -r cphalcon-${PHALCON_VERSION}
############################

RUN apt-get update -y \
    && apt-get install -y openssl zip unzip git mysql-client gettext-base libldap2-dev locales vim \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/

RUN pecl install \
        xdebug \
    && docker-php-ext-enable \
        xdebug \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && echo "xdebug.remote_enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_host=192.168.1.236" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_autostart=1" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_connect_back=0" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
    # && echo "xdebug.idekey=xdebug-atom" >> /usr/local/etc/php/conf.d/xdebug.ini
    # && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
    # && echo "xdebug.remote_log=/var/log/xdebug.log" >> /usr/local/etc/php/conf.d/xdebug.ini \
    # && echo "xdebug.remote_host=192.168.1.236" >> /usr/local/etc/php/conf.d/xdebug.ini \
    # && echo "xdebug.remote_port=9000" >> /usr/local/etc/php/conf.d/xdebug.ini \
    # && echo "xdebug.remote_autostart=1" >> /usr/local/etc/php/conf.d/xdebug.ini \

RUN locale-gen sv_SE.UTF-8
ENV LANG sv_SE.UTF-8
ENV LANGUAGE sv_SE:sv
ENV LC_TIME sv_SE.UTF-8
ENV LC_CTYPE sv_SE.UTF-8

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN docker-php-ext-install pdo pdo_mysql mbstring ldap gettext

RUN echo extension=gettext.so > /usr/local/etc/php/conf.d/gettext.ini

COPY composer.json /tmp/

WORKDIR /tmp

RUN composer install

RUN a2enmod rewrite

COPY phalcon-mvc/app/config/apache.conf /etc/apache2/sites-enabled/000-default.conf

WORKDIR /var/www/open-exam

COPY . /var/www/open-exam

RUN ln -s /tmp/vendor /var/www/open-exam/vendor
