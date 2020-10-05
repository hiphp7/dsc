<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateBrandTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'brand';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('brand_id')->comment('自增ID号');
            $table->string('brand_name')->default('')->index('brand_name')->comment('品牌中文名称');
            $table->string('brand_letter')->comment('品牌英文名称');
            $table->char('brand_first_char', 1)->comment('品牌首字母');
            $table->string('brand_logo')->default('')->comment('上传的该品牌公司logo图片');
            $table->string('index_img')->comment('品牌专区大图');
            $table->string('brand_bg')->comment('品牌背景');
            $table->text('brand_desc')->nullable()->comment('品牌描述');
            $table->string('site_url')->default('')->comment('品牌的网址');
            $table->boolean('sort_order')->default(50)->comment('品牌在前台页面的排序');
            $table->boolean('is_show')->default(1)->index('is_show')->comment('该品牌是否显示，0，否；1，显示');
            $table->boolean('is_delete')->default(0)->comment('是否删除（状态）');
            $table->boolean('audit_status')->default(1)->index('audit_status')->comment('品牌审核状态');
            $table->string('add_time')->comment('添加时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '品牌'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('brand');
    }
}
