<?php

namespace App\Patch;


use App\Models\ShopConfig;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Migration_v1_2_4
{
    public function run()
    {
        $this->shopConfig();
        $this->filterWords();
        $this->filterWordsRanks();
        $this->filterWordsLogs();
        $this->valueCardRecord();
        $this->goods();
        $this->usersReal();
        $this->solveDealconcurrent();
        $this->connectUser();
        $this->clearCache();
    }

    /**
     * 更新商城配置
     */
    private function shopConfig()
    {
        /* 隐藏IP定位类型选择（默认IP库） */
        ShopConfig::where('code', 'ip_type')->update([
            'type' => 'hidden',
            'store_range' => '0,1',
            'value' => 0
        ]);

        /* 过滤词开关 */
        $count = DB::table('shop_config')->where('code', 'filter_words_control')->count();
        if ($count < 1) {
            $parent_id = ShopConfig::where('code', 'extend_basic')->value('id');

            // 默认数据
            ShopConfig::insert([
                'parent_id' => $parent_id,
                'code' => 'filter_words_control',
                'value' => 1,
                'type' => 'hidden',
                'shop_group' => 'filter_words'
            ]);
        }

        // 隐藏IP定位类型选择（默认IP库）
        ShopConfig::where('code', 'ip_type')
            ->update([
                'type' => 'hidden',
                'store_range' => '0,1',
                'value' => 0
            ]);

        $count = ShopConfig::where('code', 'cloud_is_open')->count();
        if ($count < 1) {
            $parent_id = ShopConfig::where('code', 'extend_basic')->value('id');
            $parent_id = !empty($parent_id) ? $parent_id : 0;

            // 默认数据
            $rows = [
                [
                    'parent_id' => $parent_id,
                    'code' => 'cloud_is_open',
                    'value' => 0,
                    'type' => 'hidden',
                    'shop_group' => 'cloud_api'
                ]
            ];
            ShopConfig::insert($rows);
        }

        /* 更新版本 */
        ShopConfig::where('code', 'dsc_version')->update([
            'value' => 'v1.2.4'
        ]);
    }

    /**
     * 过滤词列表
     *
     * @return bool
     */
    private function filterWords()
    {

        $table = 'filter_words';

        if (Schema::hasTable($table)) {
            return false;
        }
        Schema::create($table, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->string('words')->comment('违禁词');
            $table->tinyInteger('rank')->unsigned()->default(0)->comment('禁止等级');
            $table->integer('admin_id')->unsigned()->default(0)->comment('管理员ID');
            $table->integer('created_at')->unsigned()->default(0)->comment('添加时间');
            $table->integer('click_count')->unsigned()->default(0)->comment('点击数');
            $table->boolean('status')->default(1)->comment('状态 0关闭 1开启');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . $table . "` comment '过滤词列表'");
    }

    /**
     * 过滤词等级表
     *
     * @return bool
     */
    private function filterWordsRanks()
    {

        $table = 'filter_words_ranks';

        if (Schema::hasTable($table)) {
            return false;
        }
        Schema::create($table, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->string('name')->comment('违禁词等级名称');
            $table->string('scenes')->default('')->comment('禁止场景');
            $table->boolean('action')->default(0)->comment('阻止行为 0禁止提交 1需要审核');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . $table . "` comment '过滤词等级表（待启用）'");
    }

    /**
     * 过滤词日志表
     *
     * @return bool
     */
    private function filterWordsLogs()
    {

        $table = 'filter_words_logs';

        if (Schema::hasTable($table)) {
            return false;
        }

        Schema::create($table, function (Blueprint $table) {
            $table->increments('id')->comment('自增ID');
            $table->integer('user_id')->unsigned()->default(0)->comment('会员ID');
            $table->string('filter_words')->default('')->comment('违禁词');
            $table->string('note')->default('')->comment('提交内容');
            $table->string('url')->default('')->comment('提交来源URL');
            $table->integer('created_at')->unsigned()->default(0)->comment('提交时间');
        });

        $prefix = config('database.connections.mysql.prefix');
        DB::statement("ALTER TABLE `" . $prefix . $table . "` comment '过滤词日志表'");
    }

    private function valueCardRecord()
    {

        $table = 'value_card_record';

        if (Schema::hasColumn($table, 'add_val')) {
            Schema::table($table, function (Blueprint $table) {
                //修改字段结构
                $table->decimal('add_val', 10, 2)->default('0.00')->change();
            });
        }
    }

    /**
     * 添加关联地区字段
     *
     * @return bool
     */
    private function goods()
    {
        $table = 'goods';

        if (!Schema::hasTable($table)) {
            return false;
        }
        if (!Schema::hasColumn($table, 'area_link')) {
            Schema::table($table, function (Blueprint $table) {
                $table->boolean('area_link')->default(0)->index('area_link')->comment('判断是否关联地区');
            });
        }
    }

    /**
     * 更新字段
     */
    private function usersReal()
    {
        $table = 'users_real';
        if (Schema::hasColumn($table, 'front_of_id_card')) {
            Schema::table($table, function (Blueprint $table) {
                //修改字段结构
                $table->string('front_of_id_card', 255)->default('')->change();
            });
        }

        if (Schema::hasColumn($table, 'reverse_of_id_card')) {
            Schema::table($table, function (Blueprint $table) {
                //修改字段结构
                $table->string('reverse_of_id_card', 255)->default('')->change();
            });
        }
    }

    private function solveDealconcurrent()
    {
        $table = 'solve_dealconcurrent';

        if (!Schema::hasTable($table)) {
            return false;
        }

        if (!Schema::hasColumn($table, 'flow_type')) {
            Schema::table($table, function (Blueprint $table) {
                $table->boolean('flow_type')->default(0)->comment('商品类型（flow_type：秒杀、普通商品）');
            });
        }
    }

    private function connectUser()
    {
        $table = 'connect_user';

        if (!Schema::hasColumn($table, 'user_type')) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('user_type', 30)->default('user')->comment('用户类型（user用户，merchant商家，admin平台）');
            });
        }
    }

    /**
     * 清除缓存
     *
     * @throws Exception
     */
    protected function clearCache()
    {
        cache()->forget('shop_config');
    }
}