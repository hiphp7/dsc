<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTemporaryFilesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'temporary_files';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->string('type', 50)->default('')->comment('类型(如:goods,cat,brand)');
            $table->string('path')->default('')->comment('路径');
            $table->integer('add_time')->default(0)->comment('添加时间');
            $table->boolean('identity')->default(0)->comment('身份(0:会员,1:管理员)');
            $table->integer('user_id')->unsigned()->default(0)->comment('会员id');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '批发求购文件上传'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('temporary_files');
    }
}
