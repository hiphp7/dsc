<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateBiaotiaoLogChangeTable extends Migration
{
    private $tableName = 'biaotiao_log_change';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->tableName)) {
            return false;
        }

        Schema::create($this->tableName, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('log_id')->index('log_id')->default(0)->comment('白条（dsc_baitiao_log）记录ID');
            $table->decimal('original_price', 10, 2)->default('0.00')->comment('白条分期原价');
            $table->decimal('chang_price', 10, 2)->default('0.00')->comment('白条分期修改后价格');
            $table->integer('add_time')->default(0)->comment('添加时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . $this->tableName . "` comment '白条分期金额修改记录表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->tableName);
    }
}
