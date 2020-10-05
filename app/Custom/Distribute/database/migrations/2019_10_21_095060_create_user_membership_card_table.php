<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUserMembershipCardTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'user_membership_card';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->string('name', 60)->default('')->comment('权益卡名称');
            $table->tinyInteger('type')->unsigned()->default(1)->comment('权益卡类型： 1 普通权益卡 2 分销权益卡');
            $table->string('description')->default('')->comment('权益卡说明');
            $table->string('background_img')->default('')->comment('权益卡背景图');
            $table->string('background_color')->default('')->comment('权益卡背景颜色');
            $table->text('receive_value')->comment('权益卡领取条件配置,序列化');
            $table->string('expiry_type')->default('forever')->comment('过期时间类型： forever(永久), days(多少天数), timespan(时间间隔)');
            $table->string('expiry_date')->default('')->comment('过期时间');
            $table->integer('enable')->unsigned()->default(0)->comment('权益卡状态：0 关闭 1 开启');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->integer('update_time')->unsigned()->default(0)->comment('修改时间');
            $table->integer('sort')->unsigned()->default(50)->comment('排序');
            $table->integer('user_rank_id')->unsigned()->default(0)->index()->comment('用户等级ID，关联user_rank.rank_id');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '权益卡表'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_membership_card');
    }
}
