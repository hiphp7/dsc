<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateWholesalePurchaseTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'wholesale_purchase';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('purchase_id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->comment('会员id');
            $table->boolean('status')->default(0)->comment('状态');
            $table->string('subject')->default('')->comment('求购标题');
            $table->boolean('type')->default(0)->comment('类型');
            $table->string('contact_name', 50)->default('')->comment('联系人姓名');
            $table->boolean('contact_gender')->default(0)->comment('联系人性别');
            $table->string('contact_phone', 50)->default('')->comment('联系人电话');
            $table->string('contact_email', 50)->default('')->comment('联系人邮箱');
            $table->string('supplier_company_name', 50)->default('')->comment('供应商公司名');
            $table->string('supplier_contact_phone', 50)->default('')->comment('供应商联系电话');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->integer('end_time')->unsigned()->default(0)->comment('结束时间');
            $table->boolean('need_invoice')->default(0)->comment('是否开发票');
            $table->string('invoice_tax_rate', 50)->default('')->comment('发票税率');
            $table->integer('consignee_region')->unsigned()->default(0)->comment('收货人仓库');
            $table->string('consignee_address')->default('')->comment('收货人地址');
            $table->string('description')->default('')->comment('描述');
            $table->boolean('review_status')->default(1)->comment('审核状态');
            $table->text('review_content')->nullable()->comment('审核内容');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '批发求购'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wholesale_purchase');
    }
}
