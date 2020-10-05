<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePackTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'pack';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('pack_id')->comment('自增ID号');
            $table->integer('user_id')->unsigned()->index('user_id')->comment('商家ID（同dsc_users表user_id）');
            $table->string('pack_name', 120)->default('')->comment('名称');
            $table->string('pack_img')->default('')->comment('图片');
            $table->decimal('pack_fee', 10, 2)->unsigned()->default(0.00)->comment('费用（使用这个包装所需要支付的费用，免费时设置为0）');
            $table->integer('free_money')->unsigned()->default(0)->comment('免费额度(当用户消费金额超过这个值时，将免费使用这个包装，设置为0时表明必须支付包装费用)');
            $table->string('pack_desc')->default('')->comment('描述');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商品包装信息配置'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('pack');
    }
}
