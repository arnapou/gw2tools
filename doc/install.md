"Minimal" Install tutorial on Ubuntu 18.04
=================

## Install Mysql 

```
sudo apt-get install mysql-server
```

## Install Mongodb

```
sudo apt-get install mongodb
```

## Install PHP 

```
sudo add-apt-repository ppa:ondrej/php
sudo apt-get update
sudo apt-get install php7.2 php7.2-curl php7.2-dev php7.2-gd php7.2-intl php7.2-bcmath \
                     php7.2-json php7.2-mbstring php7.2-mysql php7.2-xml php7.2-zip
sudo apt-get install php-pear
sudo pecl install mongodb
sudo echo "extension=mongodb.so" > /etc/php/7.2/mods-available/mongodb.ini
sudo phpenmod mongodb
```

To check the extension mongo is activated : `php -m | grep mongo`

## Install Apache2 

```
sudo apt-get install libapache2-mod-php7.2 apache2
sudo a2enmod php7.2 rewrite expires
sudo /etc/init.d/apache2 restart
```

The domain name should target the subfolder `gw2tools/web`
```
DocumentRoot /your/path/gw2tools/web
```

## Install composer

Process on https://getcomposer.org/download/

## Install project

```
git clone https://github.com/arnapou/gw2tools.git
cd gw2tools
php composer.phar update
sudo chmod 777 ./var/*
```

Init MySQL db (reminder auth config is into `app/config/parameters.yml`)
Note that your db should already exists before running the command : 
```
php bin/console doctrine:schema:update --force
```

Init mongo data (it will take minutes to load all data from the api)
```
php bin/console gw2tool:populate en
```

Finalize : 
```
php bin/console cache:clear --env=prod
php bin/console assetic:dump --env=prod

sudo chown www-data:www-data -R .
```

## Crontab

Every 15 minutes `*/15 * * * *`

```
/usr/bin/php /your/path/bin/console gw2tool:populate en
/usr/bin/php /your/path/bin/console gw2tool:populate fr
/usr/bin/php /your/path/bin/console gw2tool:populate es
/usr/bin/php /your/path/bin/console gw2tool:populate de

/usr/bin/php /your/path/bin/console gw2tool:clean

/usr/bin/php /your/path/bin/console gw2tool:statistics
```

