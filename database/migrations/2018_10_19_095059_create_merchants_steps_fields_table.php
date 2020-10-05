<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMerchantsStepsFieldsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'merchants_steps_fields';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('fid')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('商家ID（同dsc_users表user_id）');
            $table->boolean('agreement')->default(0)->comment('是否同意');
            $table->string('steps_site')->default('')->comment('提交入驻步骤的地址');
            $table->text('site_process')->nullable()->comment('入驻步骤位置');
            $table->string('contactName')->default('')->comment('联系人姓名');
            $table->string('contactPhone')->default('')->comment('联系人手机');
            $table->string('contactEmail')->default('')->comment('联系人电子邮箱');
            $table->string('organization_code')->default('')->comment('组织机构代码');
            $table->string('organization_fileImg')->default('')->comment('组织机构代码证电子版');
            $table->string('companyName')->default('')->comment('公司名称');
            $table->string('business_license_id')->default('')->comment('营业执照注册号');
            $table->string('legal_person')->default('')->comment('法定代表人姓名');
            $table->string('personalNo')->default('')->comment('身份证号');
            $table->string('legal_person_fileImg')->default('')->comment('法人身份证电子版');
            $table->string('license_comp_adress')->default('')->comment('营业执照所在地');
            $table->string('license_adress')->default('')->comment('营业执照详细地址');
            $table->string('establish_date')->default('')->comment('成立日期');
            $table->string('business_term')->default('')->comment('营业期限');
            $table->boolean('shopTime_term')->default(0)->comment('是否永久状态：0 否 1 是');
            $table->string('busines_scope')->default('')->comment('经营范围');
            $table->string('license_fileImg')->default('')->comment('营业执照副本电子版');
            $table->string('company_located')->default('')->comment('公司所在地');
            $table->string('company_adress')->default('')->comment('公司详细地址');
            $table->string('company_contactTel')->default('')->comment('公司电话');
            $table->string('company_tentactr')->default('')->comment('公司紧急联系人');
            $table->string('company_phone')->default('')->comment('公司紧急联系人手机');
            $table->string('taxpayer_id')->default('')->comment('纳税人识别号');
            $table->char('taxs_type', 150)->default('')->comment('纳税人类型');
            $table->char('taxs_num', 60)->default('')->comment('纳税类型税码');
            $table->string('tax_fileImg')->default('')->comment('税务登记证电子版');
            $table->string('status_tax_fileImg')->default('')->comment('一般纳税人资格证电子版');
            $table->string('company_name')->default('')->comment('公司名称');
            $table->string('account_number')->default('')->comment('公司银行账号');
            $table->string('bank_name')->default('')->comment('开户银行支行名称');
            $table->string('linked_bank_number')->default('')->comment('开户银行支行联行号');
            $table->string('linked_bank_address')->default('')->comment('开户银行支行所在地');
            $table->string('linked_bank_fileImg')->default('')->comment('银行开户许可证电子版');
            $table->char('company_type', 180)->default('')->comment('公司类型');
            $table->string('company_website')->default('')->comment('公司官网地址');
            $table->string('company_sale')->default('')->comment('最近一年销售额');
            $table->char('shop_seller_have_experience', 180)->default('')->comment('同类电子商务网站经验');
            $table->string('shop_website')->default('')->comment('网店地址');
            $table->string('shop_employee_num')->default('')->comment('网店运营人数');
            $table->char('shop_sale_num', 180)->default('')->comment('可网售商品数量');
            $table->char('shop_average_price', 180)->default('')->comment('预计平均客单价');
            $table->char('shop_warehouse_condition', 180)->default('')->comment('仓库情况');
            $table->string('shop_warehouse_address')->default('')->comment('仓库地址');
            $table->string('shop_delicery_company')->default('')->comment('常用物流公司');
            $table->char('shop_erp_type', 180)->default('')->comment('ERP类型');
            $table->string('shop_operating_company')->default('')->comment('代运营公司名称');
            $table->string('shop_buy_ecmoban_store', 180)->default('')->comment('是否会选择商创仓储');
            $table->char('shop_buy_delivery', 180)->default('')->comment('是否会选择平台物流');
            $table->string('preVendorId')->default('')->comment('推荐码');
            $table->string('preVendorId_fileImg')->default('')->comment('电子版');
            $table->char('shop_vertical', 180)->default('')->comment('垂直站');
            $table->string('registered_capital')->default('')->comment('注册资本');
            $table->string('contactXinbie')->default('')->comment('性别');
            $table->string('is_distribution', 30)->default('')->comment('开启分销资格');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '入驻流程填写信息'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('merchants_steps_fields');
    }
}
