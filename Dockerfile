FROM ubuntu:22.04

RUN ln -sf /usr/share/zoneinfo/Europe/Moscow /etc/localtime && \
    echo "Europe/Moscow" > /etc/timezone

RUN apt-get update && \
    apt-get install -y --no-install-recommends \
    build-essential cmake libsqlite3-dev php-sqlite3 curl php-cli php-mbstring php-xml php-zip ca-certificates \
    nginx php-fpm php-gd && \
    rm -rf /var/lib/apt/lists/*

RUN update-ca-certificates
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer --version

WORKDIR /app

COPY ./source /app
COPY ./server /app
COPY ./server/composer.json /var/www/html/composer.json
COPY ./server/sources/bio.png /var/www/html/bio.png

RUN mkdir build && cd build && cmake .. && make && mv skud_system /app/skud_system

COPY ./server/index.php /var/www/html/index.php
COPY ./server/export.php /var/www/html/export.php
COPY ./server/monthly_table.php /var/www/html/monthly_table.php

RUN echo "server { \
    listen 80; \
    root /var/www/html; \
    index index.php index.html; \
    server_name localhost; \
    location / { \
        try_files \$uri \$uri/ =404; \
    } \
    location ~ \.php\$ { \
        include snippets/fastcgi-php.conf; \
        fastcgi_pass unix:/run/php/php8.1-fpm.sock; \
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name; \
        include fastcgi_params; \
    } \
}" > /etc/nginx/sites-available/default

EXPOSE 80

WORKDIR /var/www/html
RUN composer install

CMD /app/skud_system & service php8.1-fpm start && nginx -g "daemon off;"
