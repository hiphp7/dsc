<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateZcInitiatorTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'zc_initiator';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('发起人id');
            $table->string('name')->default('')->comment('发起人名称');
            $table->string('company')->default('')->comment('发起人公司');
            $table->string('img')->default('')->comment('图片');
            $table->text('intro')->nullable()->comment('简介');
            $table->text('describe')->nullable()->comment('详情');
            $table->integer('rank')->default(0)->comment('发起人等级标识');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '众筹发起人'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('zc_initiator');
    }
}
