#/bin/bash

BASE=`pwd`

# Update dependencies
composer install

# Setup MySQL
mysql -e 'create database www;'

# Install wp-cli
curl -sS -o /tmp/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && chmod a+x /tmp/wp

# Setup php-fmt
php-fpm --fpm-config $BASE/travis/php-fmt.conf

# Setup wp
mkdir /tmp/www
cd /tmp/www
/tmp/wp core download
/tmp/wp core config --dbname=www --dbuser=root
/tmp/wp core install --url=http://localhost:8080 --title=Test --admin_user=admin --admin_password=password --admin_email=admin@example.com
ln -s $BASE /tmp/www/wp-content/plugins/email-media-import
/tmp/wp plugin activate email-media-import
/tmp/wp plugin install rest-api --activate
/tmp/wp option update permalink_structure /archives/%post_id%
/tmp/wp post create $BASE/tests/import-content.txt --post_type=page --post_title='import'

# Start nginx
nginx -c $BASE/travis/nginx.conf