FROM php:8.2-apache

# Install mysqli and other required extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Configure Apache to listen on port 8080 (Railway requirement)
RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# Copy project files to Apache root
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Start Apache in the foreground (ဒီစာကြောင်းလေးကို ထסיံထည့်ပေးပါ)
CMD ["apache2-foreground"]
