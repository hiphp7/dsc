<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWxappTemplateTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wxapp_template';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('wx_wechat_id')->unsigned()->default(0)->index()->comment('公众号id');
            $table->string('wx_template_id')->default('')->comment('小程序模板id');
            $table->string('wx_code')->default('')->comment('小程序模板消息标识');
            $table->string('wx_content')->default('')->comment('小程序自定义备注');
            $table->text('wx_template')->nullable()->comment('小程序模板消息模板');
            $table->string('wx_keyword_id')->default('')->comment('小程序关键词id');
            $table->string('wx_title')->default('')->comment('小程序模板消息标题');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->boolean('status')->default(0)->comment('启用状态 0 禁止 1 开启');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '小程序模板消息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wxapp_template');
    }
}
