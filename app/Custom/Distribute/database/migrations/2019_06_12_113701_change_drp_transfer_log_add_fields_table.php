<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDrpTransferLogAddFieldsTable extends Migration
{
    protected $tableName = 'drp_transfer_log'; // 分销商佣金转出（提现）记录表

    /**
     * 运行数据库迁移
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 'check_status')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->unsignedInteger('check_status')->default(0)->comment('审核状态: 0 未审核,1 已审核, 2 已拒绝');
            });
        }
        if (!Schema::hasColumn($this->tableName, 'deposit_type')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->unsignedInteger('deposit_type')->default(0)->comment('提现方式: 0 线下付款, 1 微信企业付款至零钱, 2 微信企业付款至银行卡');
            });
        }
        if (!Schema::hasColumn($this->tableName, 'deposit_status')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->unsignedInteger('deposit_status')->default(0)->comment('提现状态: 0 未提现, 1 已提现');
            });
        }
        if (!Schema::hasColumn($this->tableName, 'deposit_fee')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->decimal('deposit_fee', 10)->default(0.00)->comment('手续费');
            });
        }
        if (!Schema::hasColumn($this->tableName, 'bank_info')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->text('bank_info')->nullable()->comment('提现银行卡信息 json格式');
            });
        }
        if (!Schema::hasColumn($this->tableName, 'trade_no')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->string('trade_no')->default('')->comment('提现交易号');
            });
        }
        if (!Schema::hasColumn($this->tableName, 'deposit_data')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->text('deposit_data')->nullable()->comment('微信交易返回数据 json格式');
            });
        }
        if (!Schema::hasColumn($this->tableName, 'finish_status')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->unsignedInteger('finish_status')->default(0)->comment('到账状态: 0 未到账 1 已到账');
            });
        }
    }

    /**
     * 回滚数据库迁移
     *
     * @return void
     */
    public function down()
    {
        // 删除字段
        if (Schema::hasColumn($this->tableName, 'check_status')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('check_status');
            });
        }
        if (Schema::hasColumn($this->tableName, 'deposit_type')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('deposit_type');
            });
        }
        if (Schema::hasColumn($this->tableName, 'deposit_status')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('deposit_status');
            });
        }
        if (Schema::hasColumn($this->tableName, 'deposit_fee')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('deposit_fee');
            });
        }
        if (Schema::hasColumn($this->tableName, 'bank_info')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('bank_info');
            });
        }
        if (Schema::hasColumn($this->tableName, 'trade_no')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('trade_no');
            });
        }
        if (Schema::hasColumn($this->tableName, 'deposit_data')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('deposit_data');
            });
        }
        if (Schema::hasColumn($this->tableName, 'finish_status')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('finish_status');
            });
        }
    }
}
