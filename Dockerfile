FROM php:8.2-apache

# mysqli extension ကို တပ်ဆင်ခြင်း
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Railway ရဲ့ PORT variable ကို Apache က ဖတ်နိုင်အောင် ပြင်ဆင်ခြင်း
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# ဖိုင်များကို Server ဆီသို့ ကူးယူခြင်း
COPY . /var/www/html/
