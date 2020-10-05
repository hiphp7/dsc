<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMerchantsDtFileTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'merchants_dt_file';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('dtf_id')->comment('自增ID');
            $table->integer('cat_id')->unsigned()->default(0)->index('cat_id')->comment('分类ID');
            $table->integer('dt_id')->unsigned()->default(0)->index('dt_id')->comment('资质ID，同dsc_merchants_documenttitle的dt_id');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('商家ID（同dsc_users表user_id）');
            $table->string('permanent_file')->default('')->comment('上传资质文件（图片格式）');
            $table->string('permanent_date')->default('')->comment('上传资质日期时间');
            $table->boolean('cate_title_permanent')->default(0)->comment('是否永久有效（0-否，1-是）');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商家入驻流程分类资质信息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('merchants_dt_file');
    }
}
