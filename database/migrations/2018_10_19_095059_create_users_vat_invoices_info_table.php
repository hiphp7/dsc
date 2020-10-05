<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUsersVatInvoicesInfoTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'users_vat_invoices_info';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('用户ID');
            $table->string('company_name', 60)->default('')->comment('公司名称');
            $table->string('company_address')->default('')->comment('公司地址');
            $table->string('tax_id', 20)->default('')->comment('税号');
            $table->string('company_telephone', 20)->default('')->comment('公司电话');
            $table->string('bank_of_deposit', 20)->default('')->comment('开户行');
            $table->string('bank_account', 30)->default('')->comment('银行卡号');
            $table->string('consignee_name', 20)->default('')->comment('收票人姓名');
            $table->string('consignee_mobile_phone', 15)->default('')->comment('收票人手机号');
            $table->string('consignee_address')->default('')->comment('收票地址');
            $table->boolean('audit_status')->default(0)->index('audit_status')->comment('审核状态');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->integer('country')->unsigned()->default(0)->comment('联动国家');
            $table->integer('province')->unsigned()->default(0)->comment('联动省份');
            $table->integer('city')->unsigned()->default(0)->comment('联动城市');
            $table->integer('district')->unsigned()->default(0)->comment('联动城市');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" .$prefix. "$name` comment '增值发票'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('users_vat_invoices_info');
    }
}
