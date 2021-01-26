FROM phpdockerio/php73-fpm:latest

# Arguments defined in docker-compose.yml
# ARG user
# ARG uid
ARG DEBIAN_FRONTEND=noninteractive


# Install system dependencies
RUN apt-get update \
    && apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    postgresql-client \
    && apt-get -y --no-install-recommends install  php7.3-pgsql php7.3-gd php-redis \
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.34.0/install.sh | bash
RUN . $HOME/.bashrc && nvm install 12.14.1

RUN ln -s $HOME/.nvm/versions/node/v12.14.1/bin/node /usr/bin/node
RUN ln -s $HOME/.nvm/versions/node/v12.14.1/bin/npm /usr/bin/npm

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
# RUN docker-php-ext-install pgsql

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
# RUN useradd -G www-data,root -u $uid -d /home/$user $user
# RUN mkdir -p /home/$user/.composer && \
#     chown -R $user:$user /home/$user

# Set working directory
WORKDIR /var/www
# USER $user