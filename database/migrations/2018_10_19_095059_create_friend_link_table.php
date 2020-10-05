<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateFriendLinkTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'friend_link';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('link_id')->comment('自增ID');
            $table->string('link_name')->default('')->index('link_name')->comment('链接名称');
            $table->string('link_url')->default('')->comment('链接地址');
            $table->string('link_logo')->default('')->comment('链接Logo图片');
            $table->boolean('show_order')->default(50)->index('show_order')->comment('显示顺序');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '友情链接'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('friend_link');
    }
}
