<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMerchantsCategoryTemporarydateTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'merchants_category_temporarydate';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('ct_id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('商家ID（同dsc_users表user_id）');
            $table->integer('cat_id')->unsigned()->default(0)->index('cat_id')->comment('分类ID');
            $table->integer('parent_id')->unsigned()->default(0)->index('parent_id')->comment('父级ID，同dsc_category的cat_id');
            $table->string('cat_name')->default('')->index('cat_name')->comment('分类名称');
            $table->string('parent_name')->default('')->comment('父级分类名称');
            $table->boolean('is_add')->default(0)->comment('是否添加');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商家入驻流程填写分类临时信息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('merchants_category_temporarydate');
    }
}
