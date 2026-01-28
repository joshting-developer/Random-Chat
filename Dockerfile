# syntax=docker/dockerfile:1

#############################
# 1) Frontend build (Vite)
#############################
FROM joshting1999/node-php-dev:v0.1.1 AS fe
WORKDIR /var/www/html

# 只先拷貝 package lock 來吃 cache
COPY package.json package-lock.json ./
RUN npm ci

# 再拷貝其餘程式碼
COPY . .
COPY .env.production .env

# 產出 build（Laravel + Vite 預設在 public/build）
RUN npm run build


#############################
# 2) PHP vendor build
#############################
FROM joshting1999/nginx-fpm:v1.5 AS vendor
WORKDIR /var/www/html

# 只先拷貝 composer 檔案來吃 cache
COPY composer.json composer.lock ./

# 先裝依賴，但不要跑 scripts（避免 artisan 不存在）
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts

# 再拷貝其餘程式碼（這時 artisan 才會出現）
COPY . .

# 再補跑 Laravel 必要的腳本（等同 post-autoload-dump 的核心）
RUN php artisan package:discover --ansi

# （可選）再 dump 一次 autoload（通常可留著）
RUN composer dump-autoload -o


#############################
# 3) Final runtime image
#############################
FROM joshting1999/nginx-fpm:v1.5 AS runtime
WORKDIR /var/www/html

# 先放程式碼（包含 public、routes、app...）
COPY . .

# 放 vendor（以 vendor stage 為準）
COPY --from=vendor /var/www/html/vendor ./vendor

# 放 Vite build 產物（以 fe stage 為準）
COPY --from=fe /var/www/html/public/build ./public/build

# 權限（至少確保 Laravel cache 能寫）
RUN mkdir -p storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80
