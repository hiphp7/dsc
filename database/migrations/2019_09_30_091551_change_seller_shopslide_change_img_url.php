<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeSellerShopslideChangeImgUrl extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //修改seller_shopslide表中img_url和img_link字段长度
        if (Schema::hasColumn('seller_shopslide', 'img_url')) {
            Schema::table('seller_shopslide', function (Blueprint $table) {
                $table->string('img_url', 150)->change();
                $table->string('img_link', 150)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
