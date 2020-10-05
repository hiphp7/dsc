<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWechatQrcodeTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wechat_qrcode';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('wechat_id')->unsigned()->default(0)->index()->comment('公众号id');
            $table->boolean('type')->default(0)->comment('二维码类型，0临时，1永久');
            $table->integer('expire_seconds')->unsigned()->default(0)->comment('二维码有效时间');
            $table->integer('scene_id')->unsigned()->default(0)->comment('场景值ID');
            $table->string('username')->default('')->comment('推荐人');
            $table->string('function')->default('')->comment('功能');
            $table->string('ticket')->default('')->comment('二维码ticket');
            $table->string('qrcode_url')->default('')->comment('二维码路径');
            $table->integer('endtime')->unsigned()->default(0)->comment('结束时间');
            $table->integer('scan_num')->unsigned()->default(0)->comment('扫描量');
            $table->boolean('status')->default(1)->comment('状态');
            $table->integer('sort')->unsigned()->default(0)->comment('排序');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '微信通二维码管理'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wechat_qrcode');
    }
}
