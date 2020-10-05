wget https://mirrors.aliyun.com/composer/composer.phar -O /usr/local/bin/composer
chmod a+x /usr/local/bin/composer
composer u -o -vvv --no-dev
cp .env.example .env
php artisan key:generate
php artisan storage:link
php artisan optimize
cur_dir=$(pwd)
chown -R www:www ${cur_dir}/bootstrap/cache
chown -R www:www ${cur_dir}/storage
rm ${cur_dir}/init.sh -f
