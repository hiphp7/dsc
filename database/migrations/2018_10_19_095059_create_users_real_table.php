<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUsersRealTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'users_real';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('real_id')->comment('自增ID');
            $table->text('user_id')->comment('用户ID');
            $table->string('real_name', 60)->default('')->comment('真实姓名');
            $table->string('bank_mobile', 20)->comment('银行预留手机号');
            $table->string('bank_name', 60)->comment('银行名称');
            $table->string('bank_card')->default('')->comment('银行卡号');
            $table->string('self_num')->default('')->comment('身份证号');
            $table->integer('add_time')->comment('新增时间');
            $table->string('review_content', 200)->comment('审核内容');
            $table->boolean('review_status')->default(0)->comment('审核状态');
            $table->integer('review_time')->comment('审核时间');
            $table->boolean('user_type')->default(0)->comment('用户类型：0 会员，1 店铺');
            $table->string('front_of_id_card', 60)->comment('身份证正面');
            $table->string('reverse_of_id_card', 60)->comment('身份证反面');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '店铺和会员实名认证'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('users_real');
    }
}
