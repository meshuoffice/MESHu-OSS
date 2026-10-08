# ベースOS: AlmaLinux 9
FROM almalinux:9

# 1. dnfの除外設定を解除してアップデート
RUN sed -i 's/tsflags=nodocs//g' /etc/dnf/dnf.conf

# 2. EPEL、Remiリポジトリ、PHP 8.2、PostgreSQLクライアント、日本語パッケージのインストール
RUN dnf -y update && \
    dnf -y install epel-release && \
    dnf -y install https://rpms.remirepo.net/enterprise/remi-release-9.rpm && \
    dnf -y module enable php:remi-8.2 -y && \
    dnf -y install \
        php \
        php-fpm \
        php-cli \
        php-common \
        php-mbstring \
        php-bcmath \
        php-gd \
        php-intl \
        php-xml \
        php-json \
        php-pdo \
        php-pgsql \
        php-zip \
        php-curl \
        php-opcache \
        unzip \
        git \
        postgresql && \
    dnf -y install glibc-langpack-ja langpacks-ja glibc-common && \
    dnf clean all

RUN sed -i 's/;clear_env = no/clear_env = no/g' /etc/php-fpm.d/www.conf

# --- タイムゾーンおよびロケール設定 ---
RUN ln -sf /usr/share/zoneinfo/Asia/Tokyo /etc/localtime && \
    echo "date.timezone = Asia/Tokyo" > /etc/php.d/50-timezone.ini

ENV TZ=Asia/Tokyo
ENV LANG=ja_JP.UTF-8
ENV LANGUAGE=ja_JP:ja
ENV LC_ALL=ja_JP.UTF-8

# --- 作業ディレクトリ ---
WORKDIR /var/www/html

# PHP-FPMの設定をコンテナ外部接続（Nginx）向けに最適化
RUN sed -i 's/listen = \/run\/php-fpm\/www.sock/listen = 9000/g' /etc/php-fpm.d/www.conf && \
    sed -i 's/listen.allowed_clients = 127.0.0.1/;listen.allowed_clients = 127.0.0.1/g' /etc/php-fpm.d/www.conf

# PHP-FPM実行ディレクトリ作成
RUN mkdir -p /run/php-fpm

# Composer公式バイナリの配置
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

EXPOSE 9000

CMD ["/usr/sbin/php-fpm", "--nodaemonize"]
