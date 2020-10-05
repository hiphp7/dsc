<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSmsTemplateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'sms_template';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID号');
            $table->string('temp_id')->default('')->index('temp_id')->comment('短信模板ID');
            $table->string('temp_content')->default('')->comment('短信模板内容');
            $table->integer('add_time')->default(0)->comment('新增短信模板时间');
            $table->string('set_sign')->default('')->comment('设置短信签名');
            $table->string('send_time')->default('')->index()->comment('发送时机');
            $table->boolean('signature')->default(0)->comment('短信模板签名');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '短信配置'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('sms_template');
    }
}
