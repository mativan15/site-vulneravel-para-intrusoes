FROM php:5.6.40-apache

RUN sed -i \
      -e 's/deb.debian.org/archive.debian.org/g' \
      -e 's/security.debian.org/archive.debian.org/g' \
      -e '/stretch-updates/d' \
      /etc/apt/sources.list \
    && printf 'Acquire::Check-Valid-Until "false";\n' > /etc/apt/apt.conf.d/99no-check-valid \
    && docker-php-ext-install mysqli

COPY docker/entrada.sh /usr/local/bin/entrada.sh
RUN chmod +x /usr/local/bin/entrada.sh

CMD ["entrada.sh"]
