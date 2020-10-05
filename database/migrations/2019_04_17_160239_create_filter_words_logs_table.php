<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFilterWordsLogsTable extends Migration
{
    protected $table_name = 'filter_words_logs';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->table_name)) {
            return false;
        }
        
        Schema::create($this->table_name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->comment('会员ID');
            $table->string('filter_words')->default('')->comment('违禁词');
            $table->string('note')->default('')->comment('提交内容');
            $table->string('url')->default('')->comment('提交来源URL');
            $table->integer('created_at')->unsigned()->default(0)->comment('提交时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . $this->table_name . "` comment '过滤词日志表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop($this->table_name);
    }
}
