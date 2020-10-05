<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'users';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('user_id')->comment('自增ID');
            $table->string('aite_id')->default('')->comment('此字段已废弃');
            $table->string('email', 60)->default('')->index('email')->comment('会员邮箱');
            $table->string('user_name', 60)->default('')->unique('user_name')->comment('用户名');
            $table->string('nick_name', 60)->default('')->comment('用户昵称');
            $table->string('password', 32)->default('')->comment('用户密码');
            $table->string('question')->default('')->comment('安全问题');
            $table->string('answer')->default('')->comment('问题答案');
            $table->boolean('sex')->default(0)->comment('性别，0，保密；1，男；2，女');
            $table->date('birthday')->default('1000-01-01')->comment('生日日期');
            $table->decimal('user_money', 10, 2)->default(0.00)->comment('用户现有资金');
            $table->decimal('frozen_money', 10, 2)->default(0.00)->comment('用户冻结资金');
            $table->integer('pay_points')->default(0)->comment('消费积分');
            $table->integer('rank_points')->unsigned()->default(0)->comment('等级积分');
            $table->integer('address_id')->unsigned()->default(0)->index('address_id')->comment('收货信息id，取值表dsc_user_address');
            $table->integer('reg_time')->unsigned()->default(0)->comment('注册时间');
            $table->integer('last_login')->unsigned()->default(0)->comment('最后一次登录时间');
            $table->dateTime('last_time')->default('1000-01-01 00:00:00')->comment('最后一次登录时间');
            $table->string('last_ip', 15)->default('')->comment('最后一次登录IP');
            $table->integer('visit_count')->unsigned()->default(0)->comment('登录次数');
            $table->integer('user_rank')->unsigned()->default(0)->comment('会员等级ID，取值dsc_user_rank');
            $table->boolean('is_special')->default(0)->comment('是否特殊会员');
            $table->string('ec_salt', 10)->nullable()->comment('密码扩展信息');
            $table->string('salt', 10)->default('')->comment('密码加密类型');
            $table->integer('drp_parent_id')->unsigned()->nullable()->default(0)->comment('分销商父级id');
            $table->integer('parent_id')->default(0)->index('parent_id')->comment('推荐人会员ID');
            $table->boolean('flag')->default(0)->index('flag')->comment('已废弃');
            $table->string('alias', 60)->default('')->comment('昵称');
            $table->string('msn', 60)->default('')->comment('msn');
            $table->string('qq', 20)->default('')->comment('qq号');
            $table->string('office_phone', 20)->default('')->comment('办公电话');
            $table->string('home_phone', 20)->default('')->comment('家庭电话');
            $table->string('mobile_phone', 20)->default('')->index('mobile_phone')->comment('手机');
            $table->boolean('is_validated')->default(0)->index('is_validated')->comment('是否验证');
            $table->decimal('credit_line', 10, 2)->unsigned()->comment('信用额度');
            $table->string('passwd_question', 50)->default('')->comment('找回密码问题');
            $table->string('passwd_answer')->default('')->comment('找回问题答案');
            $table->text('user_picture')->nullable()->comment('会员头像图片');
            $table->text('old_user_picture')->nullable()->comment('旧会员头像图片');
            $table->integer('report_time')->default(0)->comment('冻结举报权限时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '会员列表'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('users');
    }
}
