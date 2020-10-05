<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTeamGoodsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'team_goods';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('goods_id')->unsigned()->default(0)->comment('拼团商品id');
            $table->decimal('team_price', 10, 2)->default(0.00)->comment('拼团商品价格');
            $table->integer('team_num')->unsigned()->default(0)->comment('几人团');
            $table->integer('validity_time')->unsigned()->default(0)->comment('开团有效期(小时)');
            $table->integer('limit_num')->unsigned()->default(0)->comment('已参团人数(添加虚拟数量)');
            $table->integer('astrict_num')->unsigned()->default(0)->comment('限购数量');
            $table->integer('tc_id')->unsigned()->default(0)->comment('频道id');
            $table->boolean('is_audit')->default(0)->comment('0未审核，1未通过，2通过');
            $table->boolean('is_team')->default(1)->comment('显示0否 1显示');
            $table->integer('sort_order')->unsigned()->default(0)->comment('排序');
            $table->string('team_desc')->default('')->comment('拼团介绍');
            $table->string('isnot_aduit_reason')->default('')->comment('审核未通过理由');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '拼团商品'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('team_goods');
    }
}
