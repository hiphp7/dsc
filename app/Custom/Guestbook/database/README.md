
### 数据迁移 与 数据填充

- 1. 数据迁移

将本模块 migrations 目录下所有文件，复制到 database/migrations/ 目录下 然后执行
```
php artisan migrate
```
或者
```
php artisan migrate --path=app/Custom/本模块名/database/migrations/
```

- 2. 数据填充

将本模块 seeder 目录下 TestSeeder 文件，复制到 database/seeder/ 目录下 然后执行
```
php artisan db:seed --class=TestSeeder
```

   