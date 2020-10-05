<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateVoteTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'vote';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('vote_id')->comment('自增ID');
            $table->string('vote_name', 250)->default('')->comment('在线调查主题');
            $table->integer('start_time')->unsigned()->default(0)->comment('在线调查开始时间');
            $table->integer('end_time')->unsigned()->default(0)->comment('在线调查结束时间');
            $table->boolean('can_multi')->default(0)->comment('能否多选，0，可以；1，不可以');
            $table->integer('vote_count')->unsigned()->default(0)->comment('投票人数也可以说投票次数');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '投票'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('vote');
    }
}
