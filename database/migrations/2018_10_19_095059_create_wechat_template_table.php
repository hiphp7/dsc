<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWechatTemplateTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wechat_template';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('wechat_id')->unsigned()->default(0)->index()->comment('公众号id');
            $table->string('template_id')->default('')->comment('模板id');
            $table->string('code')->default('')->comment('模板消息标识');
            $table->string('content')->default('')->comment('自定义备注');
            $table->text('template')->nullable()->comment('模板消息模板');
            $table->string('title')->default('')->comment('模板消息标题');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->boolean('status')->default(0)->comment('启用状态 0 禁止 1 开启');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '微信通模板消息列表'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wechat_template');
    }
}
