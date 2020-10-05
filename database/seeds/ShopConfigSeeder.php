<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Seeder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShopConfigSeeder extends Seeder
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * DeleteFileSeeder constructor.
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }


    /**
     * Run the database seeds.
     *
     * @throws Exception
     */
    public function run()
    {
        /* PC登录右侧 */
        $this->PcLoginRight();
        
        $this->update();
        $this->cloudIsOpen();
        $this->crossBorder();

        // 是否启用支付密码
        $this->usePaypwd();
        // 新增商店设置所在区域
        $this->add_shop_district();

        /* 补漏字段 */
        $this->tableField();

        /* 跨境订单提交文章ID */
        $this->CrossBorderArticleId();

        /* 确认收货发短信开关 */
        $this->AffirmReceivedSmsSwitch();

        // v1.4.4 隐藏原短信配置
        $this->hiddenSms();
    }

    /**
     * 更新
     *
     * @throws Exception
     */
    private function update()
    {
        /* 去除复杂重写 */
        $count = DB::table('shop_config')->where('code', 'rewrite')->count();
        if ($count > 0) {
            // 默认数据
            $rows = [
                'store_range' => '0,1'
            ];
            DB::table('shop_config')->where('code', 'rewrite')->update($rows);
        }

        /* 电子面单开关 */
        $config_id = DB::table('shop_config')->where('code', 'extend_basic')->value('id');
        $config_id = $config_id ? $config_id : 0;

        $other = [
            'parent_id' => $config_id,
            'type' => 'select',
            'store_range' => '0,1',
            'value' => 0,
            'sort_order' => 1
        ];
        $count = DB::table('shop_config')->where('code', 'tp_api')->count();
        if ($count > 0) {
            DB::table('shop_config')->where('code', 'tp_api')->update($other);
        } else {
            $other['code'] = 'tp_api';
            DB::table('shop_config')->insert($other);
        }

        /* 去除发票类型税率 */
        $count = DB::table('shop_config')->where('code', 'invoice_type')->count();
        if ($count > 0) {
            /* 删除 */
            DB::table('shop_config')->where('code', 'invoice_type')->delete();
        }

        /* 隐藏是否启用首页可视化配置 */
        $count = DB::table('shop_config')->where('code', 'openvisual')->where('type', 'select')->count();
        if ($count > 0) {
            // 默认数据
            $rows = [
                'type' => 'hidden'
            ];
            DB::table('shop_config')->where('code', 'openvisual')->update($rows);
        }

        /* 去除头部右侧翻转效果图片配置 */
        $count = DB::table('shop_config')->where('code', 'site_commitment')->count();
        if ($count > 0) {
            /* 删除 */
            DB::table('shop_config')->where('code', 'site_commitment')->delete();
        }

        /* 等级积分清零开关 */
        $other = [
            'parent_id' => $config_id,
            'type' => 'hidden',
            'store_range' => '0,1',
            'value' => 0,
            'sort_order' => 1
        ];
        $count = DB::table('shop_config')->where('code', 'open_user_rank_set')->count();
        if (!$count) {
            $other['code'] = 'open_user_rank_set';
            DB::table('shop_config')->insert($other);
        }

        /* 等级积分清零时间 */
        $other = [
            'parent_id' => $config_id,
            'type' => 'hidden',
            'store_range' => '',
            'value' => 12,
            'sort_order' => 1
        ];
        $count = DB::table('shop_config')->where('code', 'clear_rank_point')->count();
        if (!$count) {
            $other['code'] = 'clear_rank_point';
            DB::table('shop_config')->insert($other);
        }

        $count = DB::table('shop_config')->where('code', 'cloud_storage')->count();
        if ($count <= 0) {

            $parent_id = DB::table('shop_config')->where('code', 'extend_basic')->value('id');

            DB::table('shop_config')->insert([
                'parent_id' => $parent_id,
                'code' => 'cloud_storage',
                'type' => 'select',
                'store_range' => '0,1',
                'sort_order' => 1,
                'value' => 0,
                'shop_group' => 'cloud'
            ]);
        }

        DB::table('shop_config')->where('code', 'open_oss')->update([
            'shop_group' => 'cloud'
        ]);

        DB::table('shop_config')->where('code', 'addon')->update([
            'shop_group' => 'ecjia'
        ]);

        /* 过滤词开关 */
        $count = DB::table('shop_config')->where('code', 'filter_words_control')->count();
        if ($count <= 0) {
            $parent_id = DB::table('shop_config')->where('code', 'extend_basic')->value('id');

            // 默认数据
            DB::table('shop_config')->insert([
                'parent_id' => $parent_id,
                'code' => 'filter_words_control',
                'value' => 1,
                'type' => 'hidden',
                'shop_group' => 'filter_words'
            ]);
        }

        // 隐藏IP定位类型选择（默认IP库）
        DB::table('shop_config')->where('code', 'ip_type')
            ->update([
                'type' => 'hidden',
                'store_range' => '0,1',
                'value' => 0
            ]);


        // 隐藏网站域名
        $count = DB::table('shop_config')->where('code', 'site_domain')->count();
        if ($count > 0) {
            DB::table('shop_config')->where('code', 'site_domain')
                ->update([
                    'type' => 'hidden',
                    'value' => ''
                ]);
        }


        /* 修正语言包 */
        DB::table('shop_config')->where('code', 'lang')
            ->update([
                'value' => 'zh-CN'
            ]);

        $count = DB::table('shop_config')->where('code', 'show_mobile')->count();

        if ($count < 1) {
            $parent_id = DB::table('shop_config')->where('code', 'extend_basic')->value('id');

            /* 是否显示手机号码 */
            DB::table('shop_config')->where('code', 'show_mobile')
                ->insert([
                    'parent_id' => $parent_id,
                    'code' => 'show_mobile',
                    'value' => 1,
                    'type' => 'hidden'
                ]);
        }

        $count = DB::table('shop_config')->where('code', 'area_pricetype')->count();

        /* 商品设置地区模式时 */
        if ($count < 1) {

            $parent_id = DB::table('shop_config')->where('code', 'goods_base')->value('id');

            DB::table('shop_config')->insert([
                'parent_id' => $parent_id,
                'code' => 'area_pricetype',
                'type' => 'select',
                'store_range' => '0,1',
                'shop_group' => 'goods'
            ]);
        } else {
            DB::table('shop_config')->where('code', 'area_pricetype')
                ->update([
                    'type' => 'select'
                ]);
        }

        $count = DB::table('shop_config')->where('code', 'appkey')->count();
        if ($count == 0) {

            $parent_id = DB::table('shop_config')->where('code', 'extend_basic')->value('id');

            // 默认数据
            $rows = [
                'parent_id' => $parent_id,
                'code' => 'appkey',
                'type' => 'hidden'
            ];
            DB::table('shop_config')->where('code', 'rewrite')->update($rows);
        }

        /* 去除一步购物 */
        $count = DB::table('shop_config')->where('code', 'one_step_buy')->count();
        if ($count > 0) {
            /* 删除 */
            DB::table('shop_config')->where('code', 'one_step_buy')->delete();
        }

        /* 253创蓝短信 */
        /* 253创蓝短信 用户名*/
        $count = DB::table('shop_config')->where('code', 'chuanglan_account')->count();
        if ($count == 0) {

            $parent_id = DB::table('shop_config')->where('code', 'sms')->value('id');
            // 默认数据
            $rows = [
                'parent_id' => $parent_id,
                'code' => 'chuanglan_account',
                'type' => 'text',
            ];
            DB::table('shop_config')->insert($rows);
        }
        /* 253创蓝短信 密码*/
        $count = DB::table('shop_config')->where('code', 'chuanglan_password')->count();
        if ($count == 0) {
            $parent_id = DB::table('shop_config')->where('code', 'sms')->value('id');
            // 默认数据
            $rows = [
                'parent_id' => $parent_id,
                'code' => 'chuanglan_password',
                'type' => 'text',
            ];
            DB::table('shop_config')->insert($rows);
        }
        /* 253创蓝短信 请求地址*/
        $count = DB::table('shop_config')->where('code', 'chuanglan_api_url')->count();
        if ($count == 0) {
            $parent_id = DB::table('shop_config')->where('code', 'sms')->value('id');
            // 默认数据
            $rows = [
                'parent_id' => $parent_id,
                'code' => 'chuanglan_api_url',
                'type' => 'text',
            ];
            DB::table('shop_config')->insert($rows);
        }
        /* 253创蓝短信 签名*/
        $count = DB::table('shop_config')->where('code', 'chuanglan_signa')->count();
        if ($count == 0) {
            $parent_id = DB::table('shop_config')->where('code', 'sms')->value('id');
            // 默认数据
            $rows = [
                'parent_id' => $parent_id,
                'code' => 'chuanglan_signa',
                'type' => 'text',
            ];
            DB::table('shop_config')->insert($rows);
        }
        /* 253创蓝短信  end*/

        $count = DB::table('shop_config')->where('code', 'oss_network')->count();

        if ($count <= 0) {
            $parent_id = DB::table('shop_config')->where('code', 'extend_basic')->value('id');

            DB::table('shop_config')->insert([
                'parent_id' => $parent_id,
                'code' => 'oss_network',
                'type' => 'hidden',
                'store_range' => '0,1',
                'sort_order' => 1,
                'value' => 0
            ]);
        }

        /* 隐私 start*/
        $count = DB::table('shop_config')->where('code', 'privacy')->count();

        if ($count <= 0) {
            $parent_id = DB::table('shop_config')->where('code', 'shop_info')->value('id');

            DB::table('shop_config')->insert([
                'parent_id' => $parent_id,
                'code' => 'privacy',
                'type' => 'text',
                'store_range' => '',
                'sort_order' => 1,
                'value' => ''
            ]);
        }
        /* 隐私 end*/

        $this->changeDscVersion();

        $this->clearCache();
    }

    /**
     * 新增贡云启用开关
     */
    private function cloudIsOpen()
    {
        $result = DB::table('shop_config')->where('code', 'cloud_is_open')->count();
        if (empty($result)) {
            $parent_id = DB::table('shop_config')->where('code', 'extend_basic')->value('id');
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
            DB::table('shop_config')->insert($rows);
        }
    }

    /**
     * 新增支付密码启用开关 (购物流程配置)
     */
    private function usePaypwd()
    {
        $result = DB::table('shop_config')->where('code', 'use_paypwd')->count();
        if (empty($result)) {
            $parent_id = DB::table('shop_config')->where('code', 'shopping_flow')->value('id');
            $parent_id = !empty($parent_id) ? $parent_id : 0;

            // 默认数据
            $rows = [
                [
                    'parent_id' => $parent_id,
                    'code' => 'use_paypwd',
                    'value' => 1,
                    'type' => 'select',
                    'store_range' => '1,0',
                    'sort_order' => 1,
                    'shop_group' => ''
                ]
            ];
            DB::table('shop_config')->insert($rows);
        }
    }

    /**
     * 新增商店设置所在区域
     */
    private function add_shop_district()
    {
        $result = DB::table('shop_config')->where('code', 'shop_district')->count();
        if (empty($result)) {
            $parent_id = DB::table('shop_config')->where('code', 'shop_info')->value('id');
            $parent_id = !empty($parent_id) ? $parent_id : 1;

            // 默认数据
            $rows = [
                [
                    'parent_id' => $parent_id,
                    'code' => 'shop_district',
                    'value' => '',
                    'type' => 'manual',
                    'store_range' => '',
                    'sort_order' => 0,
                    'shop_group' => ''
                ]
            ];
            DB::table('shop_config')->insert($rows);

            // 修改选择地区配置排序
            $where = [
                'shop_name',
                'shop_title',
                'shop_desc',
                'shop_keywords',
                'shop_country',
                'shop_province',
                'shop_city'
            ];
            DB::table('shop_config')->whereIn('code', $where)->update(['sort_order' => 0]);
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

    /**
     * 跨境配置
     */
    protected function crossBorder()
    {
        $result = DB::table('shop_config')->where('code', 'limited_amount')->count();
        if (empty($result)) {
            $parent_id = DB::table('shop_config')->where('code', 'shop_info')->value('id');
            $parent_id = !empty($parent_id) ? $parent_id : 0;
            // 默认数据
            $rows = [
                [
                    'parent_id' => $parent_id,
                    'code' => 'limited_amount',
                    'value' => '1000',
                    'type' => 'text',
                    'sort_order' => '1'
                ], [
                    'parent_id' => $parent_id,
                    'code' => 'duty_free',
                    'value' => '0',
                    'type' => 'hidden',
                    'sort_order' => '1'
                ],
            ];
            DB::table('shop_config')->insert($rows);
        }
    }

    /**
     * 补漏字段
     */
    public function tableField()
    {
        if (!Schema::hasColumn('obs_configure', 'port')) {
            Schema::table('obs_configure', function (Blueprint $table) {
                $table->boolean('port', 15)->comment('端口号');
            });
        }
    }

    /**
     * 修改版本号
     */
    private function changeDscVersion()
    {
        $appPath = app_path('Patch/');
        $list = $this->filesystem->allFiles($appPath);

        $dsc_version = '';
        if ($list) {
            foreach ($list as $key => $val) {
                $name = str_replace('Migration_', '', $this->filesystem->name($val));
                $dsc_version = str_replace('_', '.', $name);
            }
        }

        if ($dsc_version) {
            DB::table('shop_config')->where('code', 'dsc_version')
                ->update([
                    'value' => $dsc_version
                ]);
        }
    }


    /**
     * 跨境订单提交文章ID
     */
    private function CrossBorderArticleId()
    {
        /* 跨境订单提交文章ID */
        $count = DB::table('shop_config')->where('code', 'cross_border_article_id')->count();

        if ($count <= 0) {
            // 默认数据
            $rows = [
                'parent_id' => '4',
                'code' => 'cross_border_article_id',
                'value' => '0',
                'type' => 'text'
            ];
            DB::table('shop_config')->insert($rows);
        }
    }

    /**
     * 确认收货发短信开关
     */
    private function AffirmReceivedSmsSwitch()
    {
        /* 确认收货发短信开关 */
        $count = DB::table('shop_config')->where('code', 'sms_order_received')->count();

        if ($count <= 0) {
            // 默认数据
            $rows = [
                'parent_id' => '8',
                'code' => 'sms_order_received',
                'type' => 'select',
                'store_range' => '1,0',
                'value' => '0',
                'sort_order' => '13',
                'shop_group' => 'sms'
            ];
            DB::table('shop_config')->insert($rows);
        }

        /* 商家确认收货发短信开关 */
        $count = DB::table('shop_config')->where('code', 'sms_shop_order_received')->count();

        if ($count <= 0) {
            // 默认数据
            $rows = [
                'parent_id' => '8',
                'code' => 'sms_shop_order_received',
                'type' => 'select',
                'store_range' => '1,0',
                'value' => '0',
                'sort_order' => '13',
                'shop_group' => 'sms'
            ];
            DB::table('shop_config')->insert($rows);
        }
    }

    /**
     * 我要开店
     */
    private function PcLoginRight()
    {
        $count = DB::table('shop_config')->where('code', 'login_right')->count();

        if ($count <= 0) {
            // 默认数据
            $rows = [
                'parent_id' => '942',
                'code' => 'login_right',
                'type' => 'text',
                'value' => '我要开店',
                'sort_order' => '1'
            ];
            DB::table('shop_config')->insert($rows);
        }

        $count = DB::table('shop_config')->where('code', 'login_right_link')->count();

        if ($count <= 0) {
            // 默认数据
            $rows = [
                'parent_id' => '942',
                'code' => 'login_right_link',
                'type' => 'text',
                'value' => 'merchants.php',
                'sort_order' => '1'
            ];
            DB::table('shop_config')->insert($rows);
        }
    }

    /**
     * v1.4.4 隐藏旧短信配置
     */
    private function hiddenSms()
    {
        $code_arr = [
            // 互亿
            'sms_ecmoban_user',
            'sms_ecmoban_password',
            // 阿里大于
            'ali_appkey',
            'ali_secretkey',
            // 阿里云
            'access_key_id',
            'access_key_secret',
            // 模板堂
            'dsc_appkey',
            'dsc_appsecret',
            // 华为云
            'huawei_sms_key',
            'huawei_sms_secret',
            // 创蓝
            'chuanglan_account',
            'chuanglan_password',
            'chuanglan_api_url',
            'chuanglan_signa',
        ];
        DB::table('shop_config')->where('type', '<>', 'hidden')->where(function ($query) use ($code_arr) {
            $query->whereIn('code', $code_arr)->orWhere('code', 'sms_type');
        })->update(['type' => 'hidden']);
    }
}
