<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFilterWordsTable extends Migration
{
    protected $table_name = 'filter_words';
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
            $table->string('words')->comment('违禁词');
            $table->tinyInteger('rank')->unsigned()->default(0)->comment('禁止等级');
            $table->integer('admin_id')->unsigned()->default(0)->comment('管理员ID');
            $table->integer('created_at')->unsigned()->default(0)->comment('添加时间');
            $table->integer('click_count')->unsigned()->default(0)->comment('点击数');
            $table->boolean('status')->default(1)->comment('状态 0关闭 1开启');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . $this->table_name . "` comment '过滤词列表'");
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
