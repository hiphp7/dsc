<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAppAdTable extends Migration
{
    protected $table_name = 'app_ad';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable($this->table_name)) {
            return false;
        }
        Schema::create($this->table_name, function (Blueprint $table) {
            $table->increments('ad_id')->comment('自增ID');
            $table->integer('position_id')->unsigned()->default(0)->index('position_id')->comment('广告位置ID');
            $table->integer('media_type')->default(0)->comment('流媒体类型 默认 0 图片');
            $table->string('ad_name', 60)->default('')->comment('广告名称');
            $table->string('ad_link')->default('')->comment('广告链接');
            $table->text('ad_code')->nullable()->comment('广告编码');
            $table->integer('click_count')->unsigned()->default(0)->comment('点击数');
            $table->integer('sort_order')->nullable()->comment('排序');
            $table->integer('enabled')->default(1)->comment('是否可用： 0不可用、1可用');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . $this->table_name . "` comment 'app广告'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop($this->table_name);
    }
}
