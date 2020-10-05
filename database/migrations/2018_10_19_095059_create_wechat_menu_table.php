<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWechatMenuTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wechat_menu';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('wechat_id')->unsigned()->default(0)->index()->comment('公众号id');
            $table->integer('pid')->unsigned()->default(0)->comment('父级ID');
            $table->string('name')->default('')->comment('菜单标题');
            $table->string('type')->default('')->comment('菜单的响应动作类型');
            $table->string('key')->default('')->comment('菜单KEY值，click类型必须');
            $table->string('url')->default('')->comment('网页链接，view类型必须');
            $table->integer('sort')->unsigned()->default(0)->comment('排序');
            $table->boolean('status')->default(0)->comment('显示状态');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '微信通自定义菜单'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wechat_menu');
    }
}
