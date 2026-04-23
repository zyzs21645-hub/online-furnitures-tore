FROM php:8.2-apache

# تثبيت الإضافات
RUN apt-get update \
    && docker-php-ext-install pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# تحديد مجلد المشروع داخل Apache
ENV APACHE_DOCUMENT_ROOT=/var/www/html/admin

RUN sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf \
    && sed -ri "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# مجلد العمل
WORKDIR /var/www/html

# نسخ الملفات
COPY . /var/www/html

# صلاحيات مجلد الرفع
RUN chown -R www-data:www-data /var/www/html/admin/uploads

# فتح البورت
EXPOSE 80

# حل مشكلة MPM + تشغيل Apache
CMD a2dismod mpm_event mpm_worker 2>/dev/null && \
    a2enmod mpm_prefork && \
    apache2-foreground