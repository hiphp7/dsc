<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnAppidToWechatMenu extends Migration
{
    protected $table;

    public function __construct()
    {
        $this->table = 'wechat_menu';
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->table, 'appid')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->string('appid')->default('')->comment('小程序APPID');
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
        Schema::table($this->table, function (Blueprint $table) {
            $table->dropColumn('appid');
        });
    }
}
