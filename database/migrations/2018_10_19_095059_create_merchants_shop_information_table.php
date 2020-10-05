<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMerchantsShopInformationTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'merchants_shop_information';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('shop_id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('商家ID（同dsc_users表user_id）');
            $table->integer('region_id')->unsigned()->default(0)->comment('地区ID');
            $table->boolean('shoprz_type')->default(0)->comment('期望店铺类型-旗舰店');
            $table->boolean('subShoprz_type')->default(0)->comment('1厂商直营旗舰店，2厂商授权旗舰店，卖场型旗舰店');
            $table->string('shop_expireDateStart')->default('')->comment('授权开始有效期');
            $table->string('shop_expireDateEnd')->default('')->comment('授权结束有效期');
            $table->boolean('shop_permanent')->default(0)->comment('是否永久（0-否，1-是）');
            $table->string('authorizeFile')->default('')->comment('上传文件');
            $table->string('shop_hypermarketFile')->default('')->comment('上传文件');
            $table->integer('shop_categoryMain')->unsigned()->default(0)->comment('主营类目');
            $table->text('user_shopMain_category')->nullable()->comment('选择主营类目二级分类');
            $table->string('shoprz_brandName', 150)->default('')->index('shoprz_brandName')->comment('品牌名称');
            $table->string('shop_class_keyWords', 150)->default('')->comment('类目描述关键词');
            $table->string('shopNameSuffix', 150)->default('')->index('shopNameSuffix')->comment('店铺后缀');
            $table->string('rz_shopName', 150)->default('')->index('rz_shopName')->comment('期望店铺名称');
            $table->string('hopeLoginName', 150)->default('')->comment('期望店铺登陆用户名');
            $table->string('merchants_message')->default('')->comment('审核商家不通过内容提示信息');
            $table->integer('allow_number')->unsigned()->default(0)->comment('审核操作允许的次数');
            $table->boolean('steps_audit')->default(0)->comment('入驻流程步骤');
            $table->boolean('merchants_audit')->default(0)->index('merchants_audit')->comment('审核状态（0未审核，1已审核，2审核未通过）');
            $table->boolean('review_goods')->default(1)->comment('设置是否审核其商品');
            $table->integer('sort_order')->unsigned()->default(100)->index('sort_order')->comment('排序');
            $table->integer('store_score')->default(5)->comment('店铺街排序');
            $table->boolean('is_street')->default(0)->index('is_street')->comment('是否在店铺街显示（0否，1是）');
            $table->boolean('is_IM')->default(1)->comment('是否开启"在线客服"功能 0:关闭 1:启用');
            $table->boolean('self_run')->default(0)->index('self_run')->comment('自营店铺');
            $table->boolean('shop_close')->default(1)->index('shop_close')->comment('是否关闭店铺（0：关闭，1：开启）');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '会员入驻商家信息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('merchants_shop_information');
    }
}
