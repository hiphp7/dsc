<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpgradeWholesaleModulesTable extends Migration
{
    private $prefix;

    public function __construct()
    {
        $this->prefix = config('database.connections.mysql.prefix');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasColumn('suppliers', 'real_name')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->integer('user_id')->default(0)->comment('申请人user_id');
                $table->string('real_name', 50)->comment('真实姓名');
                $table->string('self_num')->comment('身份证号码');
                $table->string('company_name', 100)->comment('公司名称');
                $table->string('company_address')->comment('公司地址');
                $table->string('front_of_id_card', 60)->comment('身份证正面');
                $table->string('reverse_of_id_card', 60)->comment('身份证反面');
                $table->string('license_fileImg')->comment('营业执照副本电子版');
                $table->string('organization_fileImg')->comment('组织机构代码证电子版');
                $table->string('linked_bank_fileImg')->comment('银行开户许可证电子版');
                $table->integer('region_id')->default(0)->comment('入驻区域ID（市级）');
                $table->text('user_shopMain_category')->comment('主营类目');
                $table->string('mobile_phone', 30)->comment('手机号');
                $table->integer('add_time')->default(0)->comment('添加时间');
                $table->tinyInteger('review_status')->default(1)->comment('审核状态');
                $table->string('email', 60)->comment('联系邮箱');
                $table->string('suppliers_logo', 60)->comment('供应商LOGO');
            });
        };

        if (Schema::hasTable('goods_type_cat') && !Schema::hasColumn('goods_type_cat', 'suppliers_id')) {
            Schema::table('goods_type_cat', function (Blueprint $table) {
                $table->integer('suppliers_id')->default(0)->comment('供应商ID');
            });
        };

        if (Schema::hasTable('goods_type') && !Schema::hasColumn('goods_type', 'suppliers_id')) {
            Schema::table('goods_type', function (Blueprint $table) {
                $table->integer('suppliers_id')->default(0)->comment('供应商ID');
            });
        };

        if (Schema::hasTable('gallery_album') && !Schema::hasColumn('gallery_album', 'suppliers_id')) {
            Schema::table('gallery_album', function (Blueprint $table) {
                $table->integer('suppliers_id')->default(0)->comment('供应商ID');
            });
        };

        if (!Schema::hasColumn('wholesale', 'goods_brief')) {
            Schema::table('wholesale', function (Blueprint $table) {
                $table->tinyInteger('standard_goods')->default(0)->comment('标准商品库');
                $table->string('goods_sn')->index('goods_sn')->comment('商品货品');
                $table->unsignedInteger('brand_id')->default(0)->index('brand_id')->comment('品牌ID');
                $table->decimal('promote_price')->default('0.00')->comment('促销价格');
                $table->decimal('goods_weight')->default('0.00')->comment('重量');
                $table->decimal('retail_price')->default('0.00')->comment('建议零售价');
                $table->unsignedTinyInteger('warn_number')->default(1)->comment('库存警告');
                $table->string('goods_brief')->comment('商品描述');
                $table->text('goods_desc')->comment('商品详情');
                $table->string('goods_thumb')->comment('商品缩略图');
                $table->string('goods_img')->comment('商品图片');
                $table->unsignedTinyInteger('export_type')->comment('导出设置类型');
                $table->text('export_type_ext')->comment('导出设置类型扩展');
                $table->string('original_img')->comment('商品原图');
                $table->unsignedInteger('add_time')->default(0)->comment('添加时间');
                $table->unsignedInteger('sort_order')->default(100)->index('sort_order')->comment('排序');
                $table->unsignedTinyInteger('is_delete')->default(0)->index('is_delete')->comment('是否删除：0 否 1 是');
                $table->unsignedTinyInteger('is_best')->default(0)->comment('精品');
                $table->unsignedTinyInteger('is_new')->default(0)->comment('新品');
                $table->unsignedTinyInteger('is_hot')->default(0)->comment('热销');
                $table->unsignedInteger('last_update')->default(0)->comment('更新时间');
                $table->unsignedTinyInteger('is_xiangou')->default(0)->comment('是否限购：0 否 1 是');
                $table->unsignedInteger('xiangou_start_date')->default(0)->comment('限购开始时间');
                $table->unsignedInteger('xiangou_end_date')->default(0)->comment('限购结束时间');
                $table->unsignedInteger('xiangou_num')->default(0)->comment('限购数量');
                $table->unsignedInteger('sales_volume')->default(0)->index('sales_volume')->comment('销量');
                $table->text('goods_product_tag')->comment('货品标签');
                $table->string('goods_unit')->comment('商品单位');
                $table->string('goods_cause')->comment('商品类型标签');
                $table->string('bar_code')->comment('条形码');
                $table->string('goods_service')->comment('商品服务');
                $table->unsignedTinyInteger('is_shipping')->default(0)->comment('是否配送：0 否 1 是');
                $table->string('keywords')->comment('关键字');
                $table->text('pinyin_keyword')->comment('拼音关键字');
                $table->text('desc_mobile')->comment('手机详情');
            });
        };

        if (Schema::hasTable('wholesale')) {
            if (Schema::hasColumn('wholesale', 'wholesale_cat_id') && !Schema::hasColumn('wholesale', 'cat_id')) {
                Schema::table('wholesale', function (Blueprint $table) {
                    $table->renameColumn('wholesale_cat_id', 'cat_id')->comment('分类ID');
                });
            }
            if (Schema::hasColumn('wholesale', 'user_id') && !Schema::hasColumn('wholesale', 'suppliers_id')) {
                Schema::table('wholesale', function (Blueprint $table) {
                    $table->renameColumn('user_id', 'suppliers_id')->comment('供货商ID');
                });
            }

            if (Schema::hasColumn('wholesale', 'goods_id')) {
                Schema::table('wholesale', function (Blueprint $table) {
                    $table->dropColumn('goods_id');
                });
            }

            if (Schema::hasColumn('wholesale', 'act_id')) {
                Schema::table('wholesale', function (Blueprint $table) {
                    $table->renameColumn('act_id', 'goods_id');
                });
            }
        };

        /* 检查索引 */
        $hasIndex = $this->hasIndex('wholesale', 'tid');

        if (!$hasIndex) {
            Schema::table('wholesale', function (Blueprint $table) {
                $table->index('tid', 'tid');
            });
        }

        /* 检查索引 */
        $hasIndex = $this->hasIndex('wholesale', 'freight');

        if (!$hasIndex) {
            Schema::table('wholesale', function (Blueprint $table) {
                $table->index('freight', 'freight');
            });
        }

        /* 检查索引 */
        $hasIndex = $this->hasIndex('wholesale', 'suppliers_id');

        if (!$hasIndex) {
            Schema::table('wholesale', function (Blueprint $table) {
                $table->index('suppliers_id', 'suppliers_id');
            });
        }

        if (!Schema::hasColumn('wholesale_extend', 'width')) {
            Schema::table('wholesale_extend', function (Blueprint $table) {
                $table->string('width')->comment('宽度');
                $table->string('height')->comment('高度');
                $table->string('depth')->comment('深度');
                $table->string('origincountry')->comment('产国');
                $table->string('originplace')->comment('产地');
                $table->string('assemblycountry')->comment('组装国');
                $table->string('barcodetype')->comment('条码类型');
                $table->string('catena')->comment('产品系列');
                $table->string('isbasicunit')->comment('是否是基本单元');
                $table->string('packagetype')->comment('包装类型');
                $table->string('grossweight')->comment('毛重');
                $table->string('netweight')->comment('净重');
                $table->string('netcontent')->comment('净含量');
                $table->string('licensenum')->comment('生产许可证');
                $table->string('healthpermitnum')->comment('卫生许可证');
            });
        };

        if (!Schema::hasColumn('wholesale_order_info', 'shipping_id')) {
            Schema::table('wholesale_order_info', function (Blueprint $table) {
                $table->unsignedTinyInteger('shipping_status')->comment('发货状态');
                $table->text('shipping_id')->comment('快递公司ID');
                $table->text('shipping_name')->comment('快递公司名称');
                $table->text('shipping_code')->comment('快递公司代码');
                $table->integer('shipping_time')->default(0)->comment('发货时间');
                $table->unsignedInteger('suppliers_id')->comment('供货商ID');
                $table->unsignedTinyInteger('is_settlement')->comment('是否结算');
                $table->string('invoice_no')->comment('运单号');
                $table->tinyInteger('is_refund')->comment('是否申请退换货');
                $table->decimal('return_amount')->default('0.00')->comment('退款金额');
                $table->unsignedTinyInteger('chargeoff_status')->default(0)->comment('账单状态');
                $table->string('zipcode')->comment('邮政编码');
                $table->string('tel')->comment('收货人电话');
                $table->string('best_time')->comment('送货时间');
                $table->string('sign_building')->comment('建筑物（标识）');
                $table->decimal('goods_amount')->default('0.00')->comment('商品总金额');
                $table->decimal('money_paid')->default('0.00')->comment('已付款金额');
                $table->decimal('surplus')->default('0.00')->comment('余额支付金额');
                $table->decimal('adjust_fee')->default('0.00')->comment('调节金额');
            });
        };

        if (Schema::hasColumn('wholesale_cart', 'ru_id')) {
            Schema::table('wholesale_cart', function (Blueprint $table) {
                $table->renameColumn('ru_id', 'suppliers_id')->comment('供货商ID');
            });
        };

        if (Schema::hasColumn('wholesale_order_goods', 'ru_id')) {
            Schema::table('wholesale_order_goods', function (Blueprint $table) {
                $table->dropColumn('ru_id');
            });
        };

        if (!Schema::hasColumn('suppliers', 'suppliers_money')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->decimal('suppliers_money')->comment('0.00')->comment('供货商资金');
                $table->string('suppliers_percent')->default('100')->comment('供货商比例');
                $table->decimal('frozen_money')->comment('0.00')->comment('供货商冻结资金');
            });
        };

        if (!Schema::hasColumn('suppliers_account_log_detail', 'suppliers_percent')) {
            Schema::table('suppliers_account_log_detail', function (Blueprint $table) {
                $table->string('suppliers_percent')->comment('供货商比例');
            });
        };

        if (!Schema::hasColumn('wholesale_order_action', 'shipping_status')) {
            Schema::table('wholesale_order_action', function (Blueprint $table) {
                $table->tinyInteger('shipping_status')->default(0)->comment('配送状态');
                $table->tinyInteger('pay_status')->default(0)->comment('支付状态');
            });
        };

        if (Schema::hasTable('admin_action') && !Schema::hasColumn('admin_action', 'action_code')) {
            Schema::table('admin_action', function (Blueprint $table) {
                $table->string('action_code')->change();
            });
        };
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('user_id');
            $table->dropColumn('real_name');
            $table->dropColumn('self_num');
            $table->dropColumn('company_name');
            $table->dropColumn('company_address');
            $table->dropColumn('front_of_id_card');
            $table->dropColumn('reverse_of_id_card');
            $table->dropColumn('license_fileImg');
            $table->dropColumn('organization_fileImg');
            $table->dropColumn('linked_bank_fileImg');
            $table->dropColumn('region_id');
            $table->dropColumn('user_shopMain_category');
            $table->dropColumn('mobile_phone');
            $table->dropColumn('add_time');
            $table->dropColumn('review_status');
            $table->dropColumn('email');
            $table->dropColumn('suppliers_logo');
        });

        Schema::table('goods_type_cat', function (Blueprint $table) {
            $table->dropColumn('suppliers_id');
        });
        Schema::table('goods_type', function (Blueprint $table) {
            $table->dropColumn('suppliers_id');
        });
        Schema::table('gallery_album', function (Blueprint $table) {
            $table->dropColumn('suppliers_id');
        });

        Schema::table('wholesale', function (Blueprint $table) {
            $table->unsignedInteger('goods_id');
            $table->renameColumn('suppliers_id', 'user_id');
            $table->renameColumn('cat_id', 'wholesale_cat_id');
            $table->renameColumn('goods_id', 'act_id');
            $table->dropColumn('standard_goods');
            $table->dropColumn('goods_sn');
            $table->dropColumn('brand_id');
            $table->dropColumn('promote_price');
            $table->dropColumn('goods_weight');
            $table->dropColumn('retail_price');
            $table->dropColumn('warn_number');
            $table->dropColumn('goods_brief');
            $table->dropColumn('goods_desc');
            $table->dropColumn('goods_thumb');
            $table->dropColumn('goods_img');
            $table->dropColumn('export_type');
            $table->dropColumn('export_type_ext');
            $table->dropColumn('original_img');
            $table->dropColumn('add_time');
            $table->dropColumn('sort_order');
            $table->dropColumn('is_delete');
            $table->dropColumn('is_best');
            $table->dropColumn('is_new');
            $table->dropColumn('is_hot');
            $table->dropColumn('last_update');
            $table->dropColumn('is_xiangou');
            $table->dropColumn('xiangou_start_date');
            $table->dropColumn('xiangou_end_date');
            $table->dropColumn('xiangou_num');
            $table->dropColumn('sales_volume');
            $table->dropColumn('goods_product_tag');
            $table->dropColumn('goods_unit');
            $table->dropColumn('goods_cause');
            $table->dropColumn('bar_code');
            $table->dropColumn('goods_service');
            $table->dropColumn('is_shipping');
            $table->dropColumn('keywords');
            $table->dropColumn('pinyin_keyword');
            $table->dropColumn('desc_mobile');
            $table->dropIndex('cat_id');
            $table->dropIndex('tid');
            $table->dropIndex('freight');
            $table->dropIndex('suppliers_id');
        });

        Schema::table('wholesale_extend', function (Blueprint $table) {
            $table->dropColumn('width');
            $table->dropColumn('height');
            $table->dropColumn('depth');
            $table->dropColumn('origincountry');
            $table->dropColumn('originplace');
            $table->dropColumn('assemblycountry');
            $table->dropColumn('barcodetype');
            $table->dropColumn('catena');
            $table->dropColumn('isbasicunit');
            $table->dropColumn('packagetype');
            $table->dropColumn('grossweight');
            $table->dropColumn('netweight');
            $table->dropColumn('netcontent');
            $table->dropColumn('licensenum');
            $table->dropColumn('healthpermitnum');
        });

        Schema::table('wholesale_order_info', function (Blueprint $table) {
            $table->dropColumn('shipping_status');
            $table->dropColumn('shipping_id');
            $table->dropColumn('shipping_name');
            $table->dropColumn('shipping_code');
            $table->dropColumn('shipping_time');
            $table->dropColumn('suppliers_id');
            $table->dropColumn('is_settlement');
            $table->dropColumn('invoice_no');
            $table->dropColumn('is_refund');
            $table->dropColumn('return_amount');
            $table->dropColumn('chargeoff_status');
            $table->dropColumn('zipcode');
            $table->dropColumn('tel');
            $table->dropColumn('best_time');
            $table->dropColumn('sign_building');
            $table->dropColumn('goods_amount');
            $table->dropColumn('money_paid');
            $table->dropColumn('surplus');
            $table->dropColumn('adjust_fee');
        });

        Schema::table('wholesale_cart', function (Blueprint $table) {
            $table->renameColumn('suppliers_id', 'ru_id');
        });

        Schema::table('wholesale_order_goods', function (Blueprint $table) {
            $table->unsignedInteger('ru_id');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('suppliers_money');
            $table->dropColumn('suppliers_percent');
            $table->dropColumn('frozen_money');
        });

        Schema::table('suppliers_account_log_detail', function (Blueprint $table) {
            $table->dropColumn('suppliers_percent');
        });

        Schema::table('wholesale_order_action', function (Blueprint $table) {
            $table->dropColumn('shipping_status');
            $table->dropColumn('pay_status');
        });
    }

    /**
     * 判断索引是否存在
     *
     * @return bool
     */
    public function hasIndex($table, $name)
    {
        $sql = "SHOW index FROM `" . $this->prefix . $table . "` WHERE column_name LIKE '" . $name . "'";
        $list = DB::select($sql);

        if ($list) {
            return true;
        } else {
            return false;
        }
    }
}
