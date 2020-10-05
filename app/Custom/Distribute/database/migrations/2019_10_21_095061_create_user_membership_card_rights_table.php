<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUserMembershipCardRightsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'user_membership_card_rights';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('rights_id')->unsigned()->default(0)->index()->comment('权益id,关联 user_membership');
            $table->text('rights_configure')->comment('权益配置,序列化');
            $table->integer('membership_card_id')->unsigned()->default(0)->index()->comment('权益卡id,关联 user_membership_card');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->integer('update_time')->unsigned()->default(0)->comment('修改时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '权益卡权益绑定关系表'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_membership_card_rights');
    }
}
