FROM php:7.4-apache AS base
LABEL maintainer "Alessandro Lucarini <dixiedream@hotmail.it>"
ENV TIMEZONE Europe/Rome
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
ENV DEBUG 0
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN apt-get update \
     && apt-get install -y \
     curl git tini \
     && apt-get autoremove -y \
     && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

ADD https://raw.githubusercontent.com/mlocati/docker-php-extension-installer/master/install-php-extensions /usr/local/bin/

RUN chmod uga+x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions mcrypt zip pdo_mysql gd

COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY . /var/www/html

WORKDIR /var/www/html

RUN composer install --no-dev --prefer-dist --optimize-autoloader

EXPOSE 80


FROM base AS dev
ENV DEBUG 1
ENV XDEBUG_ENABLE true
ENV DOCKERHOST host.docker.internal
RUN install-php-extensions xdebug
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
RUN echo "xdebug.remote_enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_autostart=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.idekey=VSCODE" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    # Row below not working on linux until docker 20.10
    && echo "xdebug.remote_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

FROM dev AS test
RUN composer install
ENV PATH ${PATH}:./vendor/bin
RUN phpcs --standard=PSR2 -n --ignore=Base,Map,migrations ./src
RUN phpunit --coverage-text --testsuite Unit 
CMD ["phpunit", "--coverage-text", "--testsuite", "Integration"]

FROM test AS audit
RUN curl -sfL https://raw.githubusercontent.com/aquasecurity/trivy/master/contrib/install.sh | sh -s -- -b /usr/local/bin \
    && trivy filesystem --no-progress /

FROM base AS prod
RUN install-php-extensions apcu opcache
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT [ "docker-entrypoint.sh" ]
HEALTHCHECK --interval=5s --timeout=3s CMD curl --fail http://localhost:80/ || exit 1
CMD ["apache2-foreground"]
