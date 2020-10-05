<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUserRankRightsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'user_rank_rights';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('rights_id')->unsigned()->default(0)->index()->comment('权益id,关联 user_membership');
            $table->text('rights_configure')->comment('权益配置,序列化');
            $table->integer('user_rank_id')->unsigned()->default(0)->index()->comment('会员等级id,关联 user_rank.rank_id');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->integer('update_time')->unsigned()->default(0)->comment('修改时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '会员等级权益表'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_rank_rights');
    }
}
