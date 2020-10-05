# 安装说明

### 数据迁移

```
php artisan migrate --force
php artisan migrate --force --path=app\Custom\CrossBorder\database\migrations\
php artisan migrate --force --path=app\Custom\Distribute\database\migrations\
```

### 数据填充

```
php artisan db:seed --force
php artisan db:seed --force --class=CrossBorderSeeder
php artisan db:seed --force --class=DRPModuleSeeder
```
