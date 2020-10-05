<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateObsConfigureTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'obs_configure';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID号');
            $table->string('bucket')->comment('OSS模块对象名称');
            $table->string('keyid')->comment('Key值');
            $table->string('keysecret')->comment('Key密码');
            $table->boolean('is_cname')->default(0)->comment('是否域名绑定');
            $table->string('endpoint')->comment('绑定域名地址');
            $table->string('regional', 100)->comment('OSS绑定区域');
            $table->string('port', 15)->comment('端口号');
            $table->boolean('is_use')->default(0)->index('is_use')->comment('是否启用（0否，1是）');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('obs_configure');
    }
}
