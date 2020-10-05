<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAppQrcodeTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'app_qrcode';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
			$table->string('login_code')->default('')->comment('扫码登录类型');
			$table->integer('user_id')->unsigned()->nullable()->index('user_id')->comment('用户ID');
            $table->string('user_name')->default('')->comment('扫码人');
			$table->text('token')->nullable()->comment('扫码人token');
			$table->string('sid')->default('')->comment('扫码唯一标识');
            $table->boolean('is_ok')->default(0)->comment('是否扫码(0未扫码,1已扫码,2已授权,3已取消)');
			$table->integer('login_time')->unsigned()->default(0)->comment('扫码登录时间');
			$table->integer('add_time')->unsigned()->default(0)->comment('二维码生成时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment 'app扫码临时记录'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('app_qrcode');
    }
}
