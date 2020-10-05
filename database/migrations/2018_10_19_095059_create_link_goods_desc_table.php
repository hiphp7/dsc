<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateLinkGoodsDescTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'link_goods_desc';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->text('goods_id')->nullable()->comment('商品ID');
            $table->integer('ru_id')->unsigned()->default(0)->index('ru_id')->comment('商家ID');
            $table->string('desc_name')->default('')->comment('描述名称');
            $table->text('goods_desc')->nullable()->comment('商品描述内容');
            $table->boolean('review_status')->default(1)->index('review_status')->comment('审核状态');
            $table->string('review_content')->default('')->comment('审核内容');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '关联商品描述信息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('link_goods_desc');
    }
}
