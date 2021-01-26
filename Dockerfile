FROM centos:7
RUN yum install -y http://rpms.remirepo.net/enterprise/remi-release-7.rpm
RUN yum install -y epel-release yum-utils
RUN yum-config-manager --disable remi-php54
RUN yum-config-manager --enable remi-php74

RUN yum install -y wget unzip OpenSSL PDO Mbstring Tokenizer xml zip curl
RUN yum install -y php-pgsql php-pecl-memcache php-pecl-memcached php-gd php-mbstring php-mcrypt php-xml php-pecl-apc php-cli php-pear php-zip

RUN curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.34.0/install.sh | bash
RUN source $HOME/.bashrc && nvm install 12.14.1

RUN ln -s $HOME/.nvm/versions/node/v12.14.1/bin/node /usr/bin/node
RUN ln -s $HOME/.nvm/versions/node/v12.14.1/bin/npm /usr/bin/npm



RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN HASH="$(wget -q -O - https://composer.github.io/installer.sig)"
# RUN php -r "if (hash_file('SHA384', 'composer-setup.php') === '$HASH') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer

WORKDIR /home/app
COPY . /home/app

RUN composer install
RUN npm install
RUN php artisan migrate
RUN php artisan serve