<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFilterWordsRanksTable extends Migration
{
    protected $table_name = 'filter_words_ranks';
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
            $table->string('name')->comment('违禁词等级名称');
            $table->string('scenes')->default('')->comment('禁止场景');
            $table->boolean('action')->default(0)->comment('阻止行为 0禁止提交 1需要审核');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . $this->table_name . "` comment '过滤词等级表（待启用）'");
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
