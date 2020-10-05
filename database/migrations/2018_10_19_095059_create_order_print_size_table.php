<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateOrderPrintSizeTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'order_print_size';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID号');
            $table->string('type', 50)->default('')->comment('类型');
            $table->string('specification', 50)->default('')->comment('规格');
            $table->string('width', 50)->default('')->comment('高度');
            $table->string('height', 50)->default('')->comment('宽度');
            $table->string('size', 50)->default('')->comment('大小');
            $table->string('description')->default('')->comment('描述');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '电子面单规格设置'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('order_print_size');
    }
}
