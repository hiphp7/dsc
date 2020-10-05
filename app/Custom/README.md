### 二次开发基本规范

一、 当前 app\Custom 目录为二开目录，所有编程规范 基于 Laravel 框架， Guestbook 模块为开发demo 可作为参考，不实现任何功能。

 > 步骤1. 新建模块目录，目录名自定义 
 
 exp: Guestbook
 
 > 步骤2. 新建模块路由文件， routes.php （文件名不可修改）
 
 - 2.1 新建路由，PC、mobile、api 路由
 exp:
 ```php
<?php
// Guestbook
Route::group(['namespace' => 'Guestbook\Controllers', 'prefix' => 'gb'], function () {

    // pc
    Route::middleware('web')->group(function () {
        Route::get('/', 'IndexController@index')->name('guestbook.index');
        Route::get('add', 'IndexController@add')->name('guestbook.add');
        Route::post('save', 'IndexController@save')->name('guestbook.save');
    });

    // mobile
    Route::middleware('web')->prefix('mobile')->group(function () {
        Route::get('/', 'MobileController@index')->name('mobile.guestbook.index');
        Route::get('add', 'MobileController@add')->name('mobile.guestbook.add');
        Route::post('save', 'MobileController@save')->name('mobile.guestbook.save');
    });

    // api
    Route::middleware('api')->prefix('api')->group(function () {
        Route::get('/', 'ApiController@index')->name('api.guestbook.index');
        Route::get('add', 'ApiController@add')->name('api.guestbook.add');
        Route::post('save', 'ApiController@save')->name('api.guestbook.save');
    });
});
```
 
 - 2.2 如果涉及修改现有路由，建议采用 新建一个 Common 模块，再在此模块下新建 routes.php  覆盖原路由。
 exp： 原路由 admin/goods.php 需要将路由引入开发模块来
 
 ```php
 <?php
 //  继承平台后台模块
 Route::group(['namespace' => 'Guestbook\Controllers\Common', 'prefix' => 'admin'], function () {
     Route::any('goods.php', 'GoodsController@index')->name('admin.goods.index');
 });
 
 ```
 此时访问 admin/goods.php  会进入 Common\GoodsController 中的index方法
 
 
 >  步骤3. 新建模块控制器目录 Controllers，然后新建控制器文件 如 IndexController.php
 
 所有开发前台控制器 需要继承 App\Custom\Controller， 后台控制器 需要继承 App\Custom\BaseAdminController,
 api 控制器 需要继承 App\Api\Foundation\Controllers\Controller
 
 exp:
 > web前台：
 
```php
<?php

namespace App\Custom\Guestbook\Controllers;

use App\Custom\Controller as FrontController;

class IndexController extends FrontController
{
    
}
```
 
> web后台：
 
```php
<?php

namespace App\Custom\Guestbook\Controllers;

use App\Custom\BaseAdminController as BaseController;

class AdminController extends BaseController
{
    
}
```
 
 
> api:

```php
<?php

namespace App\Custom\Guestbook\Controllers;

use App\Api\Foundation\Controllers\Controller as FrontController;

class ApiController extends FrontController
{
    
}
```
 
 > 步骤4. 新建模块模型目录 Models， 然后新建模型文件
 模型文件命名以数据表名为依据， 如果是系统已有数据模型，可以继承已有模型。
 
 exp:
 ```php
<?php

namespace App\Custom\Guestbook\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Guestbook
 * @package App\Custom\Guestbook\Models
 */
class Guestbook extends Model
{
    protected $table = 'guestbook';

    protected $primaryKey = 'id';

    public $timestamps = false;
}
```
 
 
 > 步骤5. 新建模块模板目录 Views，Views下新建 .blade.php 模板文件，命名规则： app/Custom/模块名/Views/控制器名/控制器方法名.blade.php
 

 exp： app/Custom/Guestbook/Views/index/index.blade.php
 
  引入规则： 如果 resources/views/ 目录下 有 index.blade.php 则引入，否则在当前开发目录下查找引入
 
```blade
{{-- 引入 app/Custom/Guestbook/Views/index/index.blade.php --}}
 @include('guestbook.views.index.index')  
 
 {{--引入同目录下nav模板文件--}}
 @include('index.nav')
 
``` 
 
 
 > 步骤6. 新建模块 Services 服务目录， 新建Services服务文件，  命名规则：CustomService.php
 
 
 > 步骤7. 新建模块 Repositories 容器目录， 新建 Repositories 容器文件， 命名规则：CustomRepository.php
 （可选但推荐，对接 Models 依赖注入提供给 Services， 然后再到 Controllers ）
 
 
 > 步骤8. 新建模块 database 目录，再新建两个子目录 migrations（数据迁移）和 seeds （数据填充）， 主要用于此模块下 需要新建数据表，修改数据表，填充初始数据等操作。  详见 demo README.md
 
 
 > 步骤9. 开发场景：每个开发模块需要独立修改或添加现在的平台、商家后台菜单 以及菜单语言包、权限
 
 exp: 新建 menu.php、 lang.php、 priv.php、 priv_seller.php 四个文件，文件名固定 
 

 - 9.1 添加菜单 menu.php 
  
 ```
 // 子菜单
 $modules['26_custom']['01_custom'] = 'gb';
 
 // 左侧菜单分类
 $menu_top['custom_top'] = '26_custom';
 ```
 
 - 9.2 添加语言包 lang.php (平台与商家共用)
 ```
 $_LANG['26_custom'] = '开发菜单管理';
 $_LANG['01_custom'] = '开发子菜单';
 ```
 - 9.3 添加平台菜单权限 priv.php
 ```
 $purview['01_custom'] = 'custom';
 ```
 
 - 9.4 添加商家菜单权限 priv_seller.php 
 ```
 $purview['01_custom'] = 'custom';
 ```
 
 > 步骤10.  前端样式 css、js、image 文件等  可以在 public 目录下 新建 custom/assets 目录，分别按模块建 对应的
css、js、image 目录 即可。

exp:  blade模板中使用

```blade
{{ assets('custom/assets/guestbook/css/style.css')  }}
{{ assets('custom/assets/guestbook/js/index.js')  }}
{{ assets('custom/assets/guestbook/image/image.png')  }}
```

   