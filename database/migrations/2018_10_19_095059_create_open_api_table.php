<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateOpenApiTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'open_api';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->string('name', 120)->default('')->comment('接口名称');
            $table->string('app_key', 160)->default('')->unique('app_key')->comment('接口秘钥');
            $table->text('action_code')->nullable()->comment('接口权限');
            $table->boolean('is_open')->default(0)->index('is_open')->comment('是否开启');
            $table->string('add_time', 60)->default('')->comment('添加时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '开放接口'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('open_api');
    }
}
