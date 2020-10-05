<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePartnerListTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'partner_list';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('link_id')->comment('自增ID号');
            $table->string('link_name')->default('')->comment('合作伙伴名称');
            $table->string('link_url')->default('')->comment('合作伙伴URL');
            $table->string('link_logo')->default('')->comment('合作伙伴LOGO');
            $table->boolean('show_order')->default(50)->index('show_order')->comment('展示订单');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '合作伙伴链接'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('partner_list');
    }
}
