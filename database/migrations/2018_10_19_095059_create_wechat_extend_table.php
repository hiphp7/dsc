<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWechatExtendTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wechat_extend';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('wechat_id')->unsigned()->default(0)->index()->comment('公众号id');
            $table->string('name')->default('')->comment('功能名称');
            $table->string('keywords')->default('')->comment('关键词');
            $table->string('command')->default('')->comment('扩展词');
            $table->text('config')->nullable()->comment('配置信息');
            $table->string('type')->default('')->comment('类型');
            $table->boolean('enable')->default(0)->comment('是否安装，1为已安装，0未安装');
            $table->string('author')->default('')->comment('作者');
            $table->string('website')->default('')->comment('网址');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '微信通功能扩展'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wechat_extend');
    }
}
