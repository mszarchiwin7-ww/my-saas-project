FROM php:8.2-apache

# mysqli extension ကို ထည့်သွင်းခြင်း
RUN docker-php-ext-install mysqli pdo pdo_mysql

# ပရောဂျက်ဖိုင်များကို Server ဆီသို့ ကူးယူခြင်း
COPY . /var/www/html/

EXPOSE 80
