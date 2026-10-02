FROM php:8.2-fpm-bookworm

# Configuração global de memória do PHP
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/99-memory-limit.ini

# Corrige mirrors para HTTPS
RUN sed -i 's|http://deb.debian.org|https://deb.debian.org|g' /etc/apt/sources.list.d/debian.sources \
 && sed -i 's|http://deb.debian.org/debian-security|https://deb.debian.org/debian-security|g' /etc/apt/sources.list.d/debian.sources

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip curl git libpng-dev libonig-dev libxml2-dev \
    libjpeg-dev libfreetype6-dev libxslt-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo_mysql mbstring zip exif bcmath xsl \
    && apt-get clean

# Copia o Composer da imagem oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define o diretório de trabalho
WORKDIR /var/www

# Copia o conteúdo do projeto para o container
COPY . .

# Cria pasta temporária do mPDF e define permissões
RUN mkdir -p /var/www/vendor/mpdf/mpdf/tmp && \
    chmod -R 777 /var/www/vendor/mpdf/mpdf/tmp

# Instala as dependências do Laravel em produção
RUN composer install --no-dev --optimize-autoloader

# Ajusta permissões para root ter controle total
RUN chown -R root:root /var/www && \
    chmod -R 755 /var/www && \
    chmod -R 777 /var/www/storage /var/www/bootstrap/cache /var/www/vendor/mpdf/mpdf/tmp

# NÃO muda para www-data - fica como ROOT
# USER www-data

# Exponha a porta do PHP-FPM
EXPOSE 9000

# Comando para iniciar o PHP-FPM como root
CMD ["php-fpm"]