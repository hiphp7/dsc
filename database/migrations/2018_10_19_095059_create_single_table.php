<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSingleTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'single';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('single_id');
            $table->integer('order_id')->index('order_id');
            $table->string('single_name', 100);
            $table->text('single_description');
            $table->char('single_like', 8)->nullable();
            $table->string('user_name', 100)->nullable();
            $table->boolean('is_audit');
            $table->string('order_sn', 20);
            $table->string('addtime', 20);
            $table->string('goods_name', 120);
            $table->integer('goods_id')->index('goods_id');
            $table->integer('user_id')->index('user_id');
            $table->string('order_time', 20);
            $table->integer('comment_id')->nullable()->index('comment_id');
            $table->string('single_ip', 15)->nullable()->default('');
            $table->integer('cat_id')->nullable()->index('cat_id');
            $table->string('integ', 8)->nullable();
            $table->integer('single_browse_num')->unsigned()->nullable()->default(0);
            $table->integer('cover')->unsigned()->default(0);
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '此表已废弃'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('single');
    }
}
