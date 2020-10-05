<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateZcGoodsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'zc_goods';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('产品id');
            $table->integer('pid')->default(0)->comment('众筹项目id');
            $table->integer('limit')->default(0)->comment('产品数量');
            $table->integer('backer_num')->default(0)->comment('售出商品数量');
            $table->decimal('price', 10)->default(0.00)->comment('产品价格');
            $table->decimal('shipping_fee', 10)->default(0.00)->comment('产品运费');
            $table->text('content')->nullable()->comment('内容描述');
            $table->string('img')->default('')->comment('图片');
            $table->integer('return_time')->default(0)->comment('预计回报时间');
            $table->text('backer_list')->nullable()->comment('支持者列表');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '众筹商品'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('zc_goods');
    }
}
