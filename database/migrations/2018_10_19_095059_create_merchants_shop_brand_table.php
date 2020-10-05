<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMerchantsShopBrandTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'merchants_shop_brand';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('bid')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('商家ID（同dsc_users表user_id）');
            $table->string('bank_name_letter', 150)->default('')->comment('品牌英文名称');
            $table->string('brandName', 180)->default('')->comment('品牌中文名称');
            $table->char('brandFirstChar', 60)->default('')->comment('首字母');
            $table->string('brandLogo')->default('')->comment('品牌logo');
            $table->boolean('brandType')->default(0)->comment('品牌类型');
            $table->boolean('brand_operateType')->default(0)->comment('品牌经营类型');
            $table->string('brandEndTime')->default('')->comment('品牌使用期限');
            $table->boolean('brandEndTime_permanent')->default(0)->comment('是否永久（0-否，1-是）');
            $table->string('site_url')->default('')->comment('品牌跳转地址');
            $table->text('brand_desc')->nullable()->comment('品牌描述');
            $table->string('sort_order')->default('')->comment('排序');
            $table->boolean('is_show')->default(0)->index('is_show')->comment('是否显示（0-否，1-是）');
            $table->boolean('is_delete')->default(0)->comment('是否删除（预留字段）');
            $table->boolean('major_business')->default(1)->comment('（预留字段）');
            $table->boolean('audit_status')->default(0)->index('audit_status')->comment('品牌审核状态（0-已审核，1-已审核）');
            $table->string('add_time', 120)->default('')->comment('添加时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商家品牌'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('merchants_shop_brand');
    }
}
