<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 砍价模块数据填充
 * Class BargainModuleSeeder
 */
class BargainModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->touchAdPosition();
        $this->touchAd();
        $this->adminAction();
		$this->wechatTemplate();
    }

    private function touchAdPosition()
    {
        $result = DB::table('touch_ad_position')->where('position_id', '1020')->count();
        if (empty($result)) {
            // 默认数据
            $rows = [
                [
                    'position_id' => '1020',
                    'user_id' => '0',
                    'position_name' => '砍价首页banner',
                    'ad_width' => '360',
                    'ad_height' => '168',
                    'position_desc' => '',
                    'position_style' => '{foreach $ads as $ad}<div class="swiper-slide">{$ad}</div>{/foreach}',
                    'is_public' => '0',
                    'theme' => 'ecmoban_dsc',
                ]
            ];
            DB::table('touch_ad_position')->insert($rows);
        }
    }

    private function touchAd()
    {
        $result = DB::table('touch_ad')->where('position_id', '1020')->count();
        if (empty($result)) {
            // 默认数据
            $rows = [
                [
                    'position_id' => '1020',
                    'media_type' => '0',
                    'ad_name' => '砍价首页banner-01',
                    'ad_link' => '',
                    'link_color' => '',
                    'ad_code' => '1509663779787829146.jpg',
                    'start_time' => '1508708579',
                    'end_time' => '1574372579',
                    'link_man' => '',
                    'link_email' => '',
                    'link_phone' => '',
                    'click_count' => '0',
                    'enabled' => '1',
                    'is_new' => '0',
                    'is_hot' => '0',
                    'is_best' => '0',
                    'public_ruid' => '0',
                    'ad_type' => '0',
                    'goods_name' => '0',
                ]
            ];
            DB::table('touch_ad')->insert($rows);
        }
    }

    private function adminAction()
    {
        $result = DB::table('admin_action')->where('action_code', 'bargain_manage')->count();
        if (empty($result)) {
            // 默认数据
            $rows = [
                [
                    'parent_id' => '7',
                    'action_code' => 'bargain_manage'
                ]
            ];
            DB::table('admin_action')->insert($rows);
        }
    }
	
	private function wechatTemplate()
    {
        $result = DB::table('wechat_template')->where('code', 'OPENTM410292733')->count();
        if (empty($result)) {
            // 默认数据
            $rows = [
                [
                    'wechat_id' => 1,
                    'code' => 'OPENTM410292733',
                    'template' => '{{first.DATA}}商品名称：{{keyword1.DATA}}底价：{{keyword2.DATA}}{{remark.DATA}}',
                    'title' => '砍价成功提醒'
                ]
            ];
            DB::table('wechat_template')->insert($rows);
        }
    }
	
	
}