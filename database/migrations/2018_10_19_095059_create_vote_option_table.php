<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateVoteOptionTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'vote_option';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('option_id')->comment('自增ID');
            $table->integer('vote_id')->unsigned()->default(0)->index('vote_id')->comment('关联的投票主题id，取值表dsc_vote');
            $table->string('option_name', 250)->default('')->comment('投票选项的值');
            $table->integer('option_count')->unsigned()->default(0)->comment('该选项的票数');
            $table->boolean('option_order')->default(100)->comment('排序');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '投票选项'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('vote_option');
    }
}
