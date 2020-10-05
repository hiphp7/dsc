<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSellerShopinfoTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'seller_shopinfo';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('商店id');
            $table->integer('ru_id')->unsigned()->default(0)->index('ru_id')->comment('入驻商家id');
            $table->string('shop_name', 50)->default('')->comment('店铺名称');
            $table->string('shop_title', 50)->default('')->comment('店铺标题');
            $table->string('shop_keyword', 50)->default('')->comment('店铺关键字');
            $table->integer('country')->unsigned()->default(0)->comment('所在国家');
            $table->integer('province')->unsigned()->default(0)->comment('所在省份');
            $table->integer('city')->unsigned()->default(0)->comment('所在城市');
            $table->integer('district')->unsigned()->default(0)->comment('所在区域');
            $table->string('shop_address', 50)->default('')->comment('详细地址');
            $table->string('seller_email', 120)->default('')->comment('邮箱地址');
            $table->string('kf_qq', 120)->default('')->comment('客服qq');
            $table->string('kf_ww', 120)->default('')->comment('客服旺旺');
            $table->string('meiqia', 20)->default('')->comment('美恰');
            $table->boolean('kf_type')->default(0)->comment('客服类型');
            $table->string('kf_tel', 50)->default('')->comment('客服电话');
            $table->string('site_head', 125)->default('')->comment('废弃字段');
            $table->char('mobile', 11)->default('')->comment('手机号');
            $table->string('shop_logo')->default('')->comment('店铺logo');
            $table->string('logo_thumb')->default('')->comment('Logo缩略图（店铺搜索页使用');
            $table->string('street_thumb')->default('')->comment('店铺街封面图');
            $table->string('brand_thumb')->default('')->comment('店铺街品牌图');
            $table->string('notice', 100)->default('')->comment('店铺公告');
            $table->string('street_desc')->default('')->comment('店铺街描述');
            $table->text('shop_header')->nullable()->comment('店铺头部');
            $table->string('shop_color', 20)->default('')->comment('店铺整体色调');
            $table->boolean('shop_style')->default(1)->comment('店铺样式1显示左侧信息和分类，0不显示左侧信息和分类');
            $table->boolean('shop_close')->default(1)->comment('是否关闭店铺（0：关闭，1：开启）');
            $table->boolean('apply')->default(0)->comment('是否申请加入店铺街，0否，1是');
            $table->boolean('is_street')->default(0)->comment('是否以加入店铺街，0否，1是');
            $table->string('remark', 100)->default('')->comment('网站管理员备注信息');
            $table->string('seller_theme', 20)->default('')->comment('店铺模板');
            $table->boolean('win_goods_type')->default(1)->comment('店铺橱窗类型');
            $table->string('store_style', 20)->default('')->comment('显示店铺名称类型');
            $table->boolean('check_sellername')->default(0)->comment('入驻品牌店铺名称');
            $table->boolean('shopname_audit')->default(1)->comment('店铺名称审核状态');
            $table->integer('shipping_id')->unsigned()->default(0)->index('shipping_id')->comment('店铺默认配送方式');
            $table->string('shipping_date')->default('')->comment('配送日期');
            $table->string('longitude', 100)->default('')->comment('经度');
            $table->string('tengxun_key')->default('')->comment('腾讯KEY');
            $table->string('latitude', 100)->default('')->comment('纬度');
            $table->integer('kf_appkey')->default(0)->comment('在线客服appkey');
            $table->string('kf_touid')->default('')->comment('在线客服账号(旺旺号)');
            $table->string('kf_logo')->default('')->comment('在线客服头像');
            $table->string('kf_welcomeMsg')->default('')->comment('在线客服欢迎信息');
            $table->char('kf_secretkey', 32)->default('')->comment('appkeySecret');
            $table->text('user_menu')->nullable()->comment('店铺快捷菜单');
            $table->boolean('kf_im_switch')->default(1)->comment('云旺IM客服开关');
            $table->decimal('seller_money', 10)->default(0.00)->comment('商家账户金额');
            $table->decimal('frozen_money', 10)->default(0.00)->comment('冻结资金');
            $table->decimal('credit_money', 10)->unsigned()->default(0.00)->comment('信用额度');
            $table->string('seller_templates', 160)->default('')->comment('商家模板');
            $table->boolean('templates_mode')->default(1)->comment('模板类型');
            $table->string('js_appkey', 50)->default('')->comment('极速扫码 appkey');
            $table->string('js_appsecret', 50)->default('')->comment('极速扫码 appsecret');
            $table->boolean('print_type')->default(0)->comment('快递单打印方式（0.默认，1.快递鸟）');
            $table->string('kdniao_printer', 50)->default('')->comment('快递单打印机');
            $table->boolean('business_practice')->default(0)->comment('商家经营类型');
            $table->boolean('review_status')->default(1)->comment('审核状态');
            $table->string('review_content', 100)->default('')->comment('审核内容');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '店铺基本信息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('seller_shopinfo');
    }
}
