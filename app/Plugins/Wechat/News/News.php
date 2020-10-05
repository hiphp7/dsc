<?php

namespace App\Plugins\Wechat\News;

use App\Http\Controllers\Wechat\PluginController;

/**
 * Class News 新品查询
 * @package App\Plugins\Wechat\News
 */
class News extends PluginController
{
    // 插件名称
    protected $plugin_name = '';
    // 商家ID
    protected $wechat_ru_id = 0;
    // 配置
    protected $cfg = [];

    /**
     * 构造方法
     *
     * @param array $cfg
     */
    public function __construct($cfg = [])
    {
        parent::__construct();
        $this->plugin_name = strtolower(basename(__FILE__, '.php'));
        $this->cfg = $cfg;
        $this->wechat_ru_id = isset($this->cfg['wechat_ru_id']) ? $this->cfg['wechat_ru_id'] : 0;

        $this->plugin_assign('config', $this->cfg);
    }

    /**
     * 安装
     */
    public function install()
    {
        if ($this->wechat_ru_id > 0) {
            // 查询商家管理员
            $this->assign('admin_info', $this->cfg['seller']);
            $this->assign('ru_id', $this->cfg['seller']['ru_id']);
            $this->assign('seller_name', $this->cfg['seller']['user_name']);

            //判断编辑个人资料权限
            $this->assign('privilege_seller', $this->cfg['privilege_seller']);
            // 商家菜单列表
            $this->assign('seller_menu', $this->cfg['menu']);
            // 当前选择菜单
            $this->assign('menu_select', $this->cfg['menu_select']);
            // 当前位置
            $this->assign('postion', $this->cfg['postion']);
        }

        return $this->plugin_display('install', $this->_data);
    }

    /**
     * 获取数据
     *
     * @param string $fromusername
     * @param array $info
     * @return array
     */
    public function returnData($fromusername = '', $info = [])
    {
        $articles = ['type' => 'text', 'content' => lang('wechat.new_goods_empty')];

        $where = [
            'is_new' => 1
        ];
        $goods = $this->wechatPluginService->recommendGoods($where, 5, $this->wechat_ru_id);

        if (!empty($goods)) {
            $articles = [];
            $articles['type'] = 'news';
            foreach ($goods as $key => $val) {
                $articles['content'][$key]['PicUrl'] = isset($val['goods_img']) ? $val['goods_img'] : '';
                $articles['content'][$key]['Title'] = $val['goods_name'];
                $articles['content'][$key]['Description'] = isset($val['goods_brief']) ? $val['goods_brief'] : '';
                $articles['content'][$key]['Url'] = dsc_url('/#/goods/' . $val['goods_id']);
            }
            // 积分赠送
            if ($this->wechat_ru_id == 0) {
                $this->wechatPluginService->updatePoint($fromusername, $info);
            }
        }

        return $articles;
    }

    /**
     * 行为操作
     */
    public function executeAction()
    {
    }
}
