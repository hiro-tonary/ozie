# 本番環境と同じPHP 7.4を使用
# 本番環境: PHP 7.4.33（モジュールモード）on FreeBSD 13.0 + Apache 2.4.62
FROM php:7.4-apache

# タイムゾーン設定
RUN echo "Asia/Tokyo" > /etc/timezone && \
    dpkg-reconfigure -f noninteractive tzdata

# 必要なパッケージのインストール
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    libonig-dev \
    zip \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install mysqli \
    && docker-php-ext-install mbstring \
    && rm -rf /var/lib/apt/lists/*

# Apacheモジュール有効化
RUN a2enmod rewrite headers

# 本番サーバーのディレクトリ構造を再現
RUN mkdir -p /home/tonary/include/ozie \
    && mkdir -p /home/tonary/tmp/ozie/tool/clear_csv \
    && mkdir -p /home/tonary/tmp/ozie/tool/session \
    && mkdir -p /home/tonary/www/ozie \
    && chmod -R 777 /home/tonary/tmp/ozie

# DocumentRootを変更
ENV APACHE_DOCUMENT_ROOT /var/www/html/ozie

# 作業ディレクトリ
WORKDIR /var/www/html/ozie

# Apacheユーザーで実行
RUN chown -R www-data:www-data /var/www/html /home/tonary/tmp/ozie

EXPOSE 80
