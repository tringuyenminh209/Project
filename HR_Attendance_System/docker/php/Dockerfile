# Image nền: PHP 8.4 bản FPM (khớp 13_インフラ設計 9.1)
FROM php:8.4-fpm

# Cài tool hệ điều hành mà Composer + Laravel cần
#   git/unzip: composer tải package   libzip-dev/libicu-dev: để compile extension zip/intl
RUN apt-get update && apt-get install -y \
        git unzip libzip-dev libicu-dev \
    && rm -rf /var/lib/apt/lists/*     # dọn cache apt -> image nhẹ hơn

# Extension PHP:
#   pdo_mysql : Laravel nói chuyện với MySQL — THIẾU CÁI NÀY LÀ LỖI "could not find driver"
#   zip       : composer giải nén package
#   intl      : xử lý đa ngôn ngữ/định dạng (Laravel khuyến nghị)
#   bcmath    : tính số thập phân chính xác (work_hours DECIMAL!)
RUN docker-php-ext-install pdo_mysql zip intl bcmath

# Lấy binary composer từ image chính thức (multi-stage copy — trick phổ biến)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html