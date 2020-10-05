<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWholesaleOrderPrintSettingTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wholesale_order_print_setting';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('suppliers_id')->unsigned()->default(0)->index('suppliers_id')->comment('供货商ID');
            $table->string('specification', 50)->comment('规格');
            $table->string('printer', 50)->comment('名称');
            $table->integer('width')->unsigned()->default(0)->comment('宽度');
            $table->boolean('is_default')->default(0)->comment('是否默认');
            $table->boolean('sort_order')->default(0)->comment('排序');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment ''");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wholesale_order_print_setting');
    }

}
