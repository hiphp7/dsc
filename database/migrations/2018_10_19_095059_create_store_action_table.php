<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateStoreActionTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'store_action';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('action_id')->comment('自增ID');
            $table->integer('parent_id')->unsigned()->default(0)->index('parent_id')->comment('父类ID');
            $table->string('action_code', 200)->default('')->comment('权限编码');
            $table->string('relevance', 20)->default('')->comment('关联');
            $table->string('action_name', 20)->default('')->comment('权限名称');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '门店权限'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('store_action');
    }
}
