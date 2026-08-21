FROM php:8.2-cli

# mysqli extension ကို တပ်ဆင်ခြင်း
RUN docker-php-ext-install mysqli pdo pdo_mysql

# ပရောဂျက်ဖိုင်များကို ကူးယူခြင်း
COPY . /app
WORKDIR /app

# Railway PORT ဖြင့် Built-in Server ကို စတင်ခြင်း
CMD ["php", "-S", "0.0.0.0:80"]
