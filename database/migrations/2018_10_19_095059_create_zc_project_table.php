<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateZcProjectTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'zc_project';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('众筹项目id');
            $table->integer('cat_id')->default(0)->comment('分类id');
            $table->string('title')->default('')->comment('项目标题');
            $table->string('init_id')->default('')->comment('发起人id');
            $table->string('start_time')->default('')->comment('项目开始时间');
            $table->string('end_time')->default('')->comment('项目结束时间');
            $table->decimal('amount', 10)->default(0.00)->comment('众筹金额');
            $table->decimal('join_money', 10)->default(0.00)->comment('已筹金额');
            $table->integer('join_num')->default(0)->comment('支持者数量');
            $table->integer('focus_num')->default(0)->comment('关注数量');
            $table->integer('prais_num')->default(0)->comment('点赞数量');
            $table->string('title_img')->default('')->comment('封面图片');
            $table->text('details')->nullable()->comment('众筹详情');
            $table->text('describe')->nullable()->comment('项目简述');
            $table->text('risk_instruction')->nullable()->comment('风险说明');
            $table->text('img')->nullable()->comment('项目详情图片');
            $table->boolean('is_best')->default(0)->comment('是否首页推荐');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '众筹项目'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('zc_project');
    }
}
