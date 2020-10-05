<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateFeedbackTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'feedback';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('msg_id')->comment('自增ID');
            $table->integer('parent_id')->unsigned()->default(0)->index('parent_id')->comment('父节点，取自该表msg_id；反馈该值为0；回复反馈为节点id');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('反馈的用户的id');
            $table->string('user_name', 60)->default('')->comment('反馈的用户的用户名');
            $table->string('user_email', 60)->default('')->comment('反馈的用户的邮箱');
            $table->string('msg_title', 200)->default('')->comment('反馈的标题，回复为reply');
            $table->boolean('msg_type')->default(0)->index('msg_type')->comment('反馈的类型，0，留言；1，投诉；2，询问；3，售后；4，求购');
            $table->boolean('msg_status')->default(0)->index('msg_status')->comment('反馈的状态');
            $table->text('msg_content')->comment('反馈的内容');
            $table->integer('msg_time')->unsigned()->default(0)->comment('反馈时间');
            $table->string('message_img')->default('0')->comment('用户上传的文件的地址');
            $table->integer('order_id')->unsigned()->default(0)->index('order_id')->comment('该反馈关联的订单id，由用户提交，取值于 dsc_order_info的order_id；0，为无匹配；');
            $table->boolean('msg_area')->default(0)->index('msg_area')->comment('反馈区域');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '用户反馈信息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('feedback');
    }
}
