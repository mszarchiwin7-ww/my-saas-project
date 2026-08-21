FROM php:8.2-apache

# mysqli extension ကို တပ်ဆင်ခြင်း
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Railway PORT ကို Apache သို့ ချိတ်ဆက်ပေးခြင်း
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
RUN sed -i 's/80/\${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# ပရောဂျက်ဖိုင်များကို ကူးယူခြင်း
COPY . /var/www/html/
