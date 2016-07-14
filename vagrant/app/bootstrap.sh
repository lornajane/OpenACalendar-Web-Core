#!/usr/bin/env bash

echo "en_GB.UTF-8 UTF-8" >> /etc/locale.gen

locale-gen

sudo apt-get update
sudo apt-get install -y postgresql apache2 php-gd php php-curl php-pgsql git php-intl php-geoip curl zip libapache2-mod-php

mkdir /home/ubuntu/bin
cd /home/ubuntu/bin
wget -q https://getcomposer.org/composer.phar

cd /vagrant
php /home/ubuntu/composer.phar  install

mkdir /home/ubuntu/fileStore
chown www-data:www-data  /home/ubuntu/fileStore

mkdir /home/ubuntu/logs
chown www-data:www-data  /home/ubuntu/logs

mkdir /home/ubuntu/bin
cd /home/ubuntu/bin
wget https://getcomposer.org/composer.phar

cd /vagrant
php /home/ubuntu/bin/composer.phar install

cp /vagrant/vagrant/app/apache.conf /etc/apache2/sites-enabled/
cp /vagrant/vagrant/app/config.test.php /vagrant/config.test.php
cp /vagrant/vagrant/app/config.php /vagrant/config.php
cp /vagrant/vagrant/app/99-custom.ini /etc/php/7.0/fpm/conf.d/
cp /vagrant/vagrant/app/test /home/ubuntu/test
chmod a+rx /home/ubuntu/test

sudo su --login -c "psql -c \"CREATE USER openacalendartest WITH PASSWORD 'testpassword';\"" postgres
sudo su --login -c "psql -c \"CREATE DATABASE openacalendartest WITH OWNER openacalendartest ENCODING 'UTF8'  LC_COLLATE='en_GB.UTF-8' LC_CTYPE='en_GB.UTF-8'  TEMPLATE=template0 ;\"" postgres

sudo su --login -c "psql -c \"CREATE USER openacalendar WITH PASSWORD 'password';\"" postgres
sudo su --login -c "psql -c \"CREATE DATABASE openacalendar WITH OWNER openacalendar ENCODING 'UTF8'  LC_COLLATE='en_GB.UTF-8' LC_CTYPE='en_GB.UTF-8'  TEMPLATE=template0 ;\"" postgres



php /vagrant/core/cli/upgradeDatabase.php
php /vagrant/core/cli/loadStaticData.php

chown www-data:www-data  /vagrant/cache/templates.web


a2enmod rewrite
/etc/init.d/apache2 restart
