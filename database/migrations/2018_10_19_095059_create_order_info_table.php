<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateOrderInfoTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'order_info';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('order_id')->comment('自增ID号');
            $table->integer('main_order_id')->unsigned()->default(0)->index('main_order_id')->comment('区分主订单作用（0为主订单，大于0是子订单）');
            $table->string('order_sn', 100)->default('')->unique()->comment('订单编号');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id')->comment('会员ID');
            $table->boolean('order_status')->default(0)->index('order_status')->comment('订单状态');
            $table->boolean('shipping_status')->default(0)->index('shipping_status')->comment('配送状态');
            $table->boolean('pay_status')->default(0)->index('pay_status')->comment('支付状态');
            $table->string('consignee', 60)->default('')->comment('收货人地址');
            $table->integer('country')->unsigned()->default(0)->index('country')->comment('国家');
            $table->integer('province')->unsigned()->default(0)->index('province')->comment('省份');
            $table->integer('city')->unsigned()->default(0)->index('city')->comment('城市');
            $table->integer('district')->unsigned()->default(0)->index('district')->comment('地区');
            $table->integer('street')->unsigned()->default(0)->index('street')->comment('街道');
            $table->string('address')->default('')->comment('收货地址');
            $table->string('zipcode', 60)->default('')->comment('收货人邮政编号');
            $table->string('tel', 60)->default('')->comment('收货人电话');
            $table->string('mobile', 60)->default('')->comment('收货人手机号码');
            $table->string('email', 60)->default('')->comment('收货人邮箱');
            $table->string('best_time', 120)->default('')->comment('收货人收货时间');
            $table->string('sign_building', 120)->default('')->comment('建筑物');
            $table->string('postscript')->default('')->comment('订单附言，由用户提交订单前填写');
            $table->text('shipping_id')->comment('配送方式ID');
            $table->text('shipping_name')->comment('配送方式名称');
            $table->text('shipping_code')->comment('配送方式代码名称（文件名称）');
            $table->text('shipping_type')->comment('配送类型（0-配送，1-自提）');
            $table->integer('pay_id')->unsigned()->default(0)->index('pay_id')->comment('支付方式ID');
            $table->string('pay_name', 120)->default('')->comment('支付方式名称');
            $table->string('how_oos', 120)->default('')->comment('缺货处理方式，等待所有商品备齐后再发； 取消订单；与店主协商');
            $table->string('how_surplus', 120)->default('')->comment('根据字段猜测应该是余额处理方式，程序未作这部分实现');
            $table->string('pack_name', 120)->default('')->comment('包装名称，取值表dsc_pack');
            $table->string('card_name', 120)->default('')->comment('贺卡的名称，取值dsc_card');
            $table->string('card_message')->default('')->comment('贺卡内容，由用户提交');
            $table->string('inv_payee', 120)->default('')->comment('发票抬头，用户页面填写');
            $table->string('inv_content', 120)->default('')->comment('发票内容，用户页面选择，取值dsc_shop_config的code字段的值为invoice_content的value');
            $table->decimal('goods_amount', 10, 2)->unsigned()->default(0.00)->comment('商品总金额');
            $table->decimal('cost_amount', 10, 2)->unsigned()->default(0.00)->comment('订单成本');
            $table->decimal('shipping_fee', 10, 2)->unsigned()->default(0.00)->comment('配送费用');
            $table->decimal('insure_fee', 10, 2)->unsigned()->default(0.00)->comment('保价费用');
            $table->decimal('pay_fee', 10, 2)->unsigned()->default(0.00)->comment('支付费用,跟支付方式的配置相关，取值表dsc_payment');
            $table->decimal('pack_fee', 10, 2)->unsigned()->default(0.00)->comment('包装费用，取值表取值表dsc_pack');
            $table->decimal('card_fee', 10, 2)->unsigned()->default(0.00)->comment('贺卡费用，取值dsc_card');
            $table->decimal('money_paid', 10, 2)->unsigned()->default(0.00)->comment('已付款金额');
            $table->decimal('surplus', 10, 2)->unsigned()->default(0.00)->comment('该订单使用余额的数量，取用户设定余额，用户可用余额，订单金额中最小者');
            $table->integer('integral')->unsigned()->default(0)->comment('使用的积分的数量，取用户使用积分，商品可用积分，用户拥有积分中最小者');
            $table->decimal('integral_money', 10, 2)->unsigned()->default(0.00)->comment('使用积分金额');
            $table->decimal('bonus', 10, 2)->unsigned()->default(0.00)->comment('使用红包金额');
            $table->decimal('order_amount', 10, 2)->default(0.00)->comment('应付款金额');
            $table->decimal('return_amount', 10, 2)->unsigned()->default(0.00)->comment('订单整站退款金额');
            $table->smallInteger('from_ad')->default(0)->comment('订单由某广告带来的广告id，应该取值于dsc_ad');
            $table->string('referer')->default('')->comment('订单的来源页面');
            $table->integer('add_time')->unsigned()->default(0)->comment('订单生成时间');
            $table->integer('confirm_time')->unsigned()->default(0)->comment('订单确认时间');
            $table->integer('pay_time')->unsigned()->default(0)->comment('订单支付时间');
            $table->integer('shipping_time')->unsigned()->default(0)->comment('订单配送时间');
            $table->integer('confirm_take_time')->unsigned()->default(0)->comment('确认收货时间');
            $table->integer('auto_delivery_time')->unsigned()->default(15)->comment('订单自动确认收货时间（天数）');
            $table->boolean('pack_id')->default(0)->comment('包装id，取值取值表dsc_pack');
            $table->boolean('card_id')->default(0)->comment('贺卡id，用户在页面选择，取值取值dsc_card');
            $table->integer('bonus_id')->unsigned()->default(0)->comment('红包的id，dsc_user_bonus的bonus_id');
            $table->string('invoice_no')->default('')->comment('发货单号，发货时填写，可在订单查询查看');
            $table->string('extension_code', 30)->default('')->index('extension_code')->comment('通过活动购买的商品的代号；GROUP_BUY是团购；AUCTION，是拍卖；SNATCH，夺宝奇兵；正常普通产品该处为空');
            $table->integer('extension_id')->unsigned()->default(0)->index('extension_id')->comment('通过活动购买的物品的id，取值dsc_goods_activity；如果是正常普通商品，该处为0');
            $table->string('to_buyer')->default('')->comment('商家给客户的留言,当该字段有值时可以在订单查询看到');
            $table->string('pay_note')->default('')->comment('付款备注，在订单管理里编辑修改');
            $table->integer('agency_id')->unsigned()->index('agency_id')->comment('该笔订单被指派给的办事处的id，根据订单内容和办事处负责范围自动决定，也可以有管理员修改，取值于表dsc_agency');
            $table->string('inv_type', 60)->comment('发票类型，用户页面选择，取值ecs_shop_config的code字段的值为invoice_type的value');
            $table->decimal('tax', 10, 2)->unsigned()->comment('发票税额');
            $table->boolean('is_separate')->default(0)->comment('0，未分成或等待分成；1，已分成；2，取消分成');
            $table->integer('parent_id')->unsigned()->default(0)->index('parent_id')->comment('能获得推荐分成的用户id，id取值于表ecs_users');
            $table->decimal('discount', 10, 2)->unsigned()->comment('折扣金额');
            $table->decimal('discount_all', 10, 2)->unsigned()->comment('分单时记录红包总金额');
            $table->boolean('is_delete')->default(0)->comment('会操作删除订单状态（0为删除，1删除回收站，2会员订单列表不显示该订单信息）');
            $table->boolean('is_settlement')->default(0)->comment('账单结算状态：0 未结算 1 已结算');
            $table->integer('sign_time')->nullable()->comment('此字段暂时无用');
            $table->boolean('is_single')->nullable()->default(0)->comment('此字段暂时无用');
            $table->integer('point_id')->unsigned()->default(0)->comment('自提编号');
            $table->string('shipping_dateStr')->comment('自提时间');
            $table->integer('supplier_id')->default(0)->index('supplier_id')->comment('供货商ID');
            $table->char('froms', 10)->default('pc')->comment('pc:电脑,mobile:手机,app:应用');
            $table->decimal('coupons', 10, 2)->unsigned()->default(0.00)->comment('优惠券金额');
            $table->integer('uc_id')->unsigned()->default(0)->index('uc_id')->comment('用户优惠券ID');
            $table->integer('is_zc_order')->nullable()->default(0)->index('is_zc_order')->comment('是否是众筹订单');
            $table->integer('zc_goods_id')->index('zc_goods_id')->comment('众筹商品ID');
            $table->boolean('is_frozen')->default(0)->comment('是否冻结');
            $table->boolean('drp_is_separate')->default(0)->comment('订单分销状态');
            $table->integer('team_id')->unsigned()->default(0)->comment('开团记录id');
            $table->integer('team_parent_id')->unsigned()->default(0)->comment('团长id');
            $table->integer('team_user_id')->unsigned()->default(0)->comment('团员id');
            $table->decimal('team_price', 10, 2)->default(0.00)->comment('拼团商品价格');
            $table->boolean('chargeoff_status')->default(0)->index('chargeoff_status')->comment('账单 (0:未结账 1:已出账 2:已结账单)');
            $table->boolean('invoice_type')->default(0)->comment('发票类型');
            $table->integer('vat_id')->default(0)->comment('增值税发票信息ID 关联 users_vat_invoices_info表自增ID');
            $table->string('tax_id')->default('')->comment('纳税人识别号');
            $table->boolean('is_update_sale')->default(0)->comment('判断付款时是否更新了销量,0为未更新，1为已更新');
            $table->integer('ru_id')->unsigned()->default(0)->index('ru_id')->comment('商家ID（同dsc_users表user_id）');
            $table->smallInteger('main_count')->unsigned()->default(0)->index('main_count')->comment('子订单数量');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商品订单'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('order_info');
    }
}
