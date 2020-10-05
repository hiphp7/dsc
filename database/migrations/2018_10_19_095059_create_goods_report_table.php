<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGoodsReportTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'goods_report';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('report_id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->comment('会员id');
            $table->string('user_name', 60)->comment('会员名称');
            $table->integer('goods_id')->unsigned()->default(0)->comment('商品id');
            $table->string('goods_name', 120)->comment('商品名称');
            $table->string('goods_image')->comment('商品图片');
            $table->integer('title_id')->unsigned()->default(0)->comment('举报主题id');
            $table->integer('type_id')->unsigned()->default(0)->comment('举报类型id');
            $table->text('inform_content')->comment('举报内容');
            $table->integer('add_time')->unsigned()->default(0)->comment('举报时间');
            $table->boolean('report_state')->default(0)->comment('举报状态  （未处理：0，已处理：1,用户取消：2，用户删除：3）');
            $table->boolean('handle_type')->default(0)->comment('举报处理结果（1：无效举报，2：恶意举报，3：有效举报）');
            $table->text('handle_message')->comment('举报处理信息');
            $table->integer('handle_time')->unsigned()->default(0)->comment('处理时间');
            $table->integer('admin_id')->default(0)->comment('管理员id');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商品举报'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('goods_report');
    }
}
