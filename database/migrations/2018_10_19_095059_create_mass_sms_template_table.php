<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMassSmsTemplateTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'mass_sms_template';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->string('temp_id')->default('')->index('temp_id')->comment('模板ID');
            $table->string('temp_content')->default('')->comment('模板内容');
            $table->string('content')->default('')->comment('自定义内容');
            $table->integer('add_time')->default(0)->comment('添加时间');
            $table->string('set_sign')->default('')->comment('设置短信签名');
            $table->boolean('signature')->default(0)->comment('短信模板签名');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '群发短信模板'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('mass_sms_template');
    }
}
