<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGuestbookTable extends Migration
{
    protected $tableName = 'guestbook'; // 留言表

    /**
     * 运行数据库迁移
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable($this->tableName)) {
            Schema::create($this->tableName, function (Blueprint $table) {
                $table->increments('id');
                $table->string('title')->default('')->comment('标题');
                $table->string('author')->default('')->comment('作者');
                $table->string('content')->default('')->comment('内容');
                $table->string('create_time')->default('')->comment('创建时间');
            });

            $prefix = config('database.connections.mysql.prefix');
            DB::statement("ALTER TABLE `" . $prefix . "$this->tableName` comment '留言表'");
        }
    }

    /**
     * 回滚数据库迁移
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable($this->tableName)) {
            Schema::drop($this->tableName);
        }
    }
}
