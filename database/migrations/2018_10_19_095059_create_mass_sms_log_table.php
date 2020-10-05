<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMassSmsLogTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'mass_sms_log';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('template_id')->unsigned()->default(0)->index('template_id')->comment('短信模板id');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('会员ID');
            $table->boolean('send_status')->default(0)->comment('0:未发送,1:已发送,2:发送失败');
            $table->integer('last_send')->unsigned()->default(0)->comment('最后发送时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '群发信息记录'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('mass_sms_log');
    }
}
