# dockerfile for laravel
FROM php:8.0-fpm

# Arguments defined in docker-compose.yml
ARG user
ARG uid

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    sudo \
    npm

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan commands
RUN useradd -G www-data,sudo -u $uid -d /home/$user $user \
    && echo "$user ALL=(ALL) NOPASSWD:ALL" >> /etc/sudoers \
    && mkdir -p /home/$user/.composer \
    && chown -R $user:$user /home/$user

# add permission on /var/www
RUN chown -R $user:$user /var/www

# Set working directory
WORKDIR /var/www

USER $user

# Install PM2 globally
RUN sudo npm install pm2 -g
