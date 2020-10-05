<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateZcTopicTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'zc_topic';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('topic_id')->comment('话题id');
            $table->integer('parent_topic_id')->default(0)->comment('父话题id');
            $table->integer('reply_topic_id')->default(0)->comment('回复话题的id');
            $table->boolean('topic_status')->default(1)->comment('话题状态1显示，0隐藏');
            $table->text('topic_content')->nullable()->comment('话题内容');
            $table->integer('user_id')->default(0)->comment('会员id');
            $table->integer('pid')->default(0)->comment('众筹项目id');
            $table->string('add_time')->default('')->comment('发表时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '众筹话题'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('zc_topic');
    }
}
