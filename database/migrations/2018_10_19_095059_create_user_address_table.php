<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUserAddressTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'user_address';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('address_id')->comment('自增ID');
            $table->string('address_name', 50)->default('')->comment('此字段暂时无用');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('会员ID');
            $table->string('consignee', 60)->default('')->comment('收货人名称');
            $table->string('email', 60)->default('')->comment('收货邮箱');
            $table->integer('country')->unsigned()->default(0)->comment('所在国家');
            $table->integer('province')->unsigned()->default(0)->comment('所在省份');
            $table->integer('city')->unsigned()->default(0)->comment('所在城市');
            $table->integer('district')->unsigned()->default(0)->comment('所在地区');
            $table->integer('street')->unsigned()->default(0)->comment('所在街道');
            $table->string('address', 120)->default('')->comment('收货人地址');
            $table->string('zipcode', 60)->default('')->comment('收货人邮政编码');
            $table->string('tel', 60)->default('')->comment('联系电话');
            $table->string('mobile', 60)->default('')->comment('手机号');
            $table->string('sign_building', 120)->default('')->comment('建筑物');
            $table->string('best_time', 120)->default('')->comment('希望配送时间');
            $table->boolean('audit')->default(0)->comment('状态');
            $table->string('update_time', 120)->default('')->comment('修改时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '会员收货地址'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_address');
    }
}
