<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUserBankTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'user_bank';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->string('bank_name', 50)->default('')->comment('银行名称');
            $table->string('bank_card', 50)->default('')->comment('银行卡号');
            $table->string('bank_region')->default('')->comment('开户行所在地');
            $table->string('bank_user_name', 10)->default('')->comment('银行卡持有人姓名');
            $table->integer('user_id')->default(0)->index('user_id')->comment('会员id');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '会员绑定银行信息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_bank');
    }
}
