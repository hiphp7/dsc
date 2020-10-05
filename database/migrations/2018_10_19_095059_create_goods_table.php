<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGoodsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $name = 'goods';
        if (Schema::hasTable($name)) {
            return false;
        }
        Schema::create($name, function (Blueprint $table) {
            $table->increments('goods_id')->comment('自增ID');
            $table->integer('cat_id')->unsigned()->default(0)->index('cat_id')->comment('分类ID');
            $table->integer('user_cat')->unsigned()->default(0)->index('user_cat')->comment('商家分类id');
            $table->integer('user_id')->unsigned()->index('user_id')->comment('商家ID（同dsc_users表user_id）');
            $table->string('goods_sn', 60)->default('')->index('goods_sn')->comment('商品货号');
            $table->string('bar_code', 60)->default('')->comment('条形码');
            $table->string('goods_name', 120)->default('')->comment('商品名称');
            $table->string('goods_name_style', 60)->default('+')->comment('商品名称样式');
            $table->integer('click_count')->unsigned()->default(0)->comment('点击数');
            $table->integer('brand_id')->unsigned()->default(0)->index('brand_id')->comment('品牌名称');
            $table->string('provider_name', 100)->default('')->comment('供货人的名称');
            $table->integer('goods_number')->unsigned()->default(0)->index('goods_number')->comment('商品数量');
            $table->decimal('goods_weight', 10, 3)->unsigned()->default(0.000)->index('goods_weight')->comment('商品重量');
            $table->integer('default_shipping')->unsigned()->default(0)->comment('默认配送方式');
            $table->decimal('market_price', 10, 2)->unsigned()->default(0.00)->comment('市场价格');
            $table->decimal('cost_price', 10, 2)->default(0.00)->comment('成本价');
            $table->decimal('shop_price', 10, 2)->unsigned()->default(0.00)->comment('销售价格');
            $table->decimal('promote_price', 10, 2)->unsigned()->default(0.00)->comment('促销价格');
            $table->integer('promote_start_date')->unsigned()->default(0)->index('promote_start_date')->comment('促销开始时间');
            $table->integer('promote_end_date')->unsigned()->default(0)->index('promote_end_date')->comment('促销结束时间');
            $table->boolean('warn_number')->default(1)->comment('商品库存报警数量');
            $table->string('keywords')->default('')->comment('商品关键字');
            $table->string('goods_brief')->default('')->comment('商品的简短描述');
            $table->text('goods_desc')->nullable()->comment('商品的详细描述');
            $table->text('desc_mobile')->nullable()->comment('手机详情');
            $table->string('goods_thumb')->default('')->comment('商品在前台显示的微缩图片，如在分类筛选时显示的小图片');
            $table->string('goods_img')->default('')->comment('商品的实际大小图片，如进入该商品页时介绍商品属性所显示的大图片');
            $table->string('original_img')->default('')->comment('上传的商品的原始图片');
            $table->boolean('is_real')->default(1)->comment('是否是实物，1，是；0，否；比如虚拟卡就为0，不是实物');
            $table->string('extension_code', 30)->default('')->comment('商品的扩展属性，比如像虚拟卡');
            $table->boolean('is_on_sale')->default(1)->index('is_on_sale')->comment('该商品是否开放销售，1，是；0，否');
            $table->boolean('is_alone_sale')->default(1)->index('is_alone_sale')->comment('是否能单独销售，1，是；0，否；如果不能单独销售，则只能作为某商品的配件或者赠品销售');
            $table->boolean('is_shipping')->default(0)->comment('0表示此商品免运费，1按照正常运费计算');
            $table->integer('integral')->unsigned()->default(0)->comment('购买该商品可以使用的积分数量，用积分代替金额消费');
            $table->integer('add_time')->unsigned()->default(0)->comment('添加时间');
            $table->integer('sort_order')->unsigned()->default(100)->index('sort_order')->comment('商品排序');
            $table->boolean('is_delete')->default(0)->index('is_delete')->comment('是否删除到回收站');
            $table->boolean('is_best')->default(0)->comment('加入推荐（0非精品，1精品）');
            $table->boolean('is_new')->default(0)->comment('加入推荐（0非新品，1新品）');
            $table->boolean('is_hot')->default(0)->comment('加入推荐（0非热销，1热销）');
            $table->boolean('is_promote')->default(0)->comment('是否促销（0非促销，1促销）');
            $table->boolean('is_volume')->default(0)->comment('是否阶梯价（0非阶梯，1阶梯）');
            $table->boolean('is_fullcut')->default(0)->comment('是否促销（0非满减，1满减）');
            $table->boolean('bonus_type_id')->default(0)->comment('购买该商品所能领到的红包类型');
            $table->integer('last_update')->unsigned()->default(0)->index('last_update')->comment('最后更新时间');
            $table->integer('goods_type')->unsigned()->default(0)->comment('商品所属类型id，取值表dsc_goods_type的cat_id');
            $table->string('seller_note')->default('')->comment('商品的商家备注，仅商家可见');
            $table->integer('give_integral')->default(-1)->comment('赠送消费积分数');
            $table->integer('rank_integral')->default(-1)->comment('赠送等级积分数');
            $table->integer('suppliers_id')->unsigned()->default(0)->index()->comment('供应商ID');
            $table->boolean('is_check')->default(0)->comment('废弃字段');
            $table->boolean('store_hot')->default(0)->comment('商家加入推荐（0非精品，1精品）');
            $table->boolean('store_new')->default(0)->comment('商家加入推荐（0非新品，1新品）');
            $table->boolean('store_best')->default(0)->comment('商家加入推荐（0非热销，1热销）');
            $table->integer('group_number')->unsigned()->default(0)->comment('组合购买限制数量');
            $table->boolean('is_xiangou')->default(0)->comment('是否限购');
            $table->integer('xiangou_start_date')->default(0)->index('xiangou_start_date')->comment('限购开始时间');
            $table->integer('xiangou_end_date')->default(0)->index('xiangou_end_date')->comment('限购结束时间');
            $table->integer('xiangou_num')->unsigned()->default(0)->comment('限购设定数量');
            $table->boolean('review_status')->default(1)->index('review_status')->comment('商品审核状态');
            $table->string('review_content')->comment('商品审核不通过内容');
            $table->text('goods_shipai')->nullable()->comment('废弃字段');
            $table->integer('comments_number')->unsigned()->default(0)->comment('商品评论数');
            $table->integer('sales_volume')->unsigned()->default(0)->index('sales_volume')->comment('商品销量');
            $table->integer('comment_num')->unsigned()->default(0)->comment('评论数');
            $table->boolean('model_price')->default(0)->comment('商品价格模式（0-默认，1-仓库，2-地区）');
            $table->boolean('model_inventory')->default(0)->comment('商品库存模式（0-默认，1-仓库，2-地区）');
            $table->boolean('model_attr')->default(0)->comment('商品属性模式（0-默认，1-仓库，2-地区）');
            $table->decimal('largest_amount', 10, 2)->unsigned()->default(0.00)->comment('废弃字段');
            $table->text('pinyin_keyword')->nullable()->comment('商品名称拼音');
            $table->string('goods_product_tag', 2000)->nullable()->comment('商品标签');
            $table->string('goods_tag')->nullable()->comment('商品标签');
            $table->string('stages', 512)->default('')->comment('是否分期, 空字符串:不分期 有数据:分期(数据里含分别分多少期)');
            $table->decimal('stages_rate', 10, 2)->default(0.50)->comment('白条分期 费率 默认0.5% ');
            $table->boolean('freight')->default(2)->index('freight')->comment('商品运费模式');
            $table->decimal('shipping_fee', 10, 2)->default(0.00)->comment('商品运费');
            $table->integer('tid')->unsigned()->default(0)->index('tid')->comment('商品运费模板id');
            $table->string('goods_unit', 15)->default('个')->comment('商品单位');
            $table->string('goods_cause', 10)->comment('商品退换货标识字段');
            $table->decimal('dis_commission', 10, 2)->default(0.00)->comment('分销佣金百分比');
            $table->boolean('is_distribution')->default(0)->comment('商品是否参与分销');
            $table->string('commission_rate', 10)->default('0')->comment('佣金比例');
            $table->integer('from_seller')->default(0)->index('from_seller')->comment('商家导入的商品标注来源');
            $table->integer('user_brand')->unsigned()->default(0)->index('user_brand')->comment('品牌统一使用平台品牌ID异步操作');
            $table->string('product_table', 60)->default('products')->comment('货品表');
            $table->integer('product_id')->unsigned()->default(0)->comment('商品默认勾选属性货品');
            $table->decimal('product_price', 10, 2)->unsigned()->default(0.00)->comment('商品默认勾选属性货品价格');
            $table->decimal('product_promote_price', 10, 2)->unsigned()->default(0.00)->comment('促销价格');
            $table->string('goods_video')->default('')->comment('商品视频');
            $table->boolean('is_show')->default(1)->comment('是否显示 0 隐藏 1 显示');
            $table->integer('cloud_id')->unsigned()->default(0)->comment('贡云商品ID');
            $table->string('cloud_goodsname')->default('')->comment('贡云商品名称');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . "$name` comment '商品'");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('goods');
    }
}
