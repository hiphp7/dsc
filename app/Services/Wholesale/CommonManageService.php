<?php

namespace App\Services\Wholesale;

use App\Models\SellerShopinfo;
use App\Models\ShopConfig;
use App\Models\WholesaleOrderGoods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Common\CommonManageService as Common;

class CommonManageService
{
    protected $common;
    protected $baseRepository;
    protected $timeRepository;

    public function __construct(
        Common $common,
        BaseRepository $baseRepository,
        TimeRepository $timeRepository
    )
    {
        $this->common = $common;
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
    }

    /**
     * @param $modules
     * @param $purview
     */
    public function setSellerMenu($modules, $purview)
    {

        //菜单排序
        foreach ($modules as $key => $value) {
            ksort($modules[$key]);
        }
        ksort($modules);

        //商家权限
        $action_list = session('supply_action_list') ? explode(',', session('supply_action_list', '')) : [];

        //判断编辑个人资料权限
        $privilege_seller = 0;
        if (in_array('privilege_seller', $action_list)) {
            $privilege_seller = 1;
        }

        //权限子菜单
        $action_menu = array();
        foreach ($purview as $key => $val) {
            if (is_array($val)) {
                foreach ($val as $k => $v) {
                    if (in_array($v, $action_list)) {
                        $action_menu[$key] = $v;
                    }
                }
            } else {
                if (in_array($val, $action_list)) {
                    $action_menu[$key] = $val;
                }
            }
        }

        //匹配父菜单
        foreach ($modules as $key => $val) {
            foreach ($val as $k => $v) {
                if (!array_key_exists($k, $action_menu)) {
                    unset($modules[$key][$k]);
                }
            }

            if (empty($modules[$key])) {
                unset($modules[$key]);
            }
        }

        //菜单赋值
        $menu = array();
        $i = 0;
        foreach ($modules as $key => $val) {
            $menu[$i] = array(
                'action' => $key,
                'label' => $this->getMenuUrl(reset($val), $GLOBALS['_LANG'][$key]),
                'url' => $this->getMenuUrl(reset($val)),
                'children' => array()
            );

            foreach ($val as $k => $v) {
                if ($this->getMenuUrl($v)) {
                    $menu[$i]['children'][] = array(
                        'action' => $k,
                        'label' => $this->getMenuUrl($v, $GLOBALS['_LANG'][$k]),
                        'url' => $this->getMenuUrl($v),
                        'status' => $this->getUserMenuStatus($k)
                    );
                }
            }

            $i++;
        }

        $seller_logo = ShopConfig::where('code', 'seller_logo')->value('value');
        $seller_logo = $seller_logo ? strstr($seller_logo, "images") : '';


        $arr = [
            'privilege' => $privilege_seller,
            'menu' => $menu,
            'logo' => $seller_logo
        ];

        return $arr;
    }

    /**
     * 菜单栏名称
     *
     * @return array|bool
     */
    public function getMenuName($modules)
    {
        $menu_arr = '';
        @$url = basename(PHP_SELF) . "?" . request()->server('QUERY_STRING');
        if ($url) {
            //过滤多余的查询
            $url = str_replace('&uselastfilter=1', '', $url);
            $menu_arr = $this->getMenuArr($url, $modules);
        }

        return $menu_arr;
    }

    /**
     * @param string $url
     * @param string $name
     * @return string
     */
    private function getMenuUrl($url = '', $name = '')
    {
        if ($url) {
            $url_arr = explode('?', $url);
            if (!$url_arr[0]) {
                $url = '';
                if ($name && $url) {
                    $name = '<span style="text-decoration: line-through; color:#ccc; ">' . $name . '</span>';
                }
            }
        }

        if ($name) {
            return $name;
        } else {
            return $url;
        }
    }

    /**
     * 返回快捷菜单选中状态
     *
     * @param string $action
     * @return int
     */
    private function getUserMenuStatus($action = '')
    {
        $user_menu_arr = $this->getUserMenuList();
        if ($user_menu_arr && in_array($action, $user_menu_arr)) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 菜单列表
     *
     * @param string $url
     * @param array $list
     * @return array
     */
    private function getMenuArr($url = '', $list = array())
    {
        static $menu_arr = array();
        static $menu_key = null;
        foreach ($list as $key => $val) {
            if (is_array($val)) {
                $menu_key = $key;
                $this->getMenuArr($url, $val);
            } else {
                if ($val == $url) {
                    $menu_arr['action'] = $menu_key;
                    $menu_arr['current'] = $key;
                }
            }
        }

        return $menu_arr;
    }

    /**
     * 获取快捷菜单详细列表信息
     *
     * @return array
     */
    public function getUserMenuPro($modules)
    {
        $user_menu_pro = [];
        $user_menu_arr = $this->getUserMenuList();

        if ($user_menu_arr) {
            foreach ($user_menu_arr as $key => $val) {
                $user_menu_pro[$key] = $this->getMenuInfo($val, $modules);
            }
        }

        return $user_menu_pro;
    }

    /**
     * 返回快捷菜单列表
     *
     * @return array
     */
    private function getUserMenuList()
    {
        $adminru = $this->common->getAdminIdSeller();

        $user_menu = [];
        if ($adminru && $adminru['ru_id'] > 0) {
            $user_menu = SellerShopinfo::where('ru_id', $adminru['ru_id'])->value('user_menu');

            if ($user_menu) {
                $user_menu = explode(',', $user_menu);
            }
        }

        return $user_menu;
    }

    /**
     * 根据action获取菜单名称和url
     *
     * @param string $action
     * @param $modules
     * @return array|bool
     */
    private function getMenuInfo($action = '', $modules)
    {
        foreach ($modules as $key => $val) {
            foreach ($val as $k => $v) {
                if ($k == $action) {
                    $user_info = array(
                        'action' => $k,
                        'label' => $GLOBALS['_LANG'][$k],
                        'url' => $v);
                    return $user_info;
                }
            }
        }

        return [];
    }

    /**
     * 取得销售明细数据信息
     * @param bool $is_pagination 是否分页
     * @return  array   销售明细数据
     */
    public function getSaleList($is_pagination = true)
    {
        $adminru = get_admin_ru_id();

        /* 时间参数 */
        $filter['start_date'] = empty($_REQUEST['start_date']) ? $this->timeRepository->getLocalStrtoTime('-7 days') : $this->timeRepository->getLocalStrtoTime($_REQUEST['start_date']);
        $filter['end_date'] = empty($_REQUEST['end_date']) ? $this->timeRepository->getLocalStrtoTime('today') : $this->timeRepository->getLocalStrtoTime($_REQUEST['end_date']);
        $filter['goods_sn'] = empty($_REQUEST['goods_sn']) ? '' : trim($_REQUEST['goods_sn']);
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'goods_number' : trim($_REQUEST['sort_by']);

        $filter['suppliers_id'] = !empty($_REQUEST['suppliers_id']) ? intval($_REQUEST['suppliers_id']) : $adminru['suppliers_id'];
        $filter['order_status'] = !empty($_REQUEST['order_status']) ? explode(',', $_REQUEST['order_status']) : '';
        $filter['shipping_status'] = !empty($_REQUEST['shipping_status']) ? explode(',', $_REQUEST['shipping_status']) : '';
        $filter['time_type'] = !empty($_REQUEST['time_type']) ? intval($_REQUEST['time_type']) : 0;

        $row = WholesaleOrderGoods::whereRaw(1);

        if ($filter['goods_sn']) {
            $row = $row->where('goods_sn', $filter['goods_sn']);
        }

        $row = $row->whereHas('getWholesaleOrderInfo', function ($query) use ($filter) {
            $query = $query->whereHas('getMainOrderId', function ($query) {
                $query->selectRaw("count(*) as count")->Having('count', 0);
            });

            if ($filter['suppliers_id'] > 0) {
                $query = $query->where('suppliers_id', $filter['suppliers_id']);
            }

            $time = $filter['end_date'] + 86400;

            if ($filter['time_type'] == 1) {
                $query = $query->where('add_time', '>=', $filter['start_date'])
                    ->where('add_time', '<=', $time);
            } else {
                $query = $query->where('shipping_time', '>=', $filter['start_date'])
                    ->where('shipping_time', '<=', $time);
            }

            if (!empty($filter['order_status'])) {
                $order_status = $this->baseRepository->getExplode($filter['order_status']);
                $query = $query->whereIn('order_status', $order_status);
            }

            if (!empty($filter['shipping_status'])) {
                $shipping_status = $this->baseRepository->getExplode($filter['shipping_status']);
                $query->whereIn('shipping_status', $shipping_status);
            }
        });

        $res = $record_count = $row;

        $filter['record_count'] = $record_count->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        $res = $res->with([
            'getWholesaleOrderInfo' => function ($query) {
                $query->with(['getSuppliers']);
            }
        ]);

        $res = $res->orderBy($filter['sort_by'], 'DESC');
        if ($is_pagination) {
            if ($filter['start'] > 0) {
                $res = $res->skip($filter['start']);
            }

            if ($filter['page_size'] > 0) {
                $res = $res->take($filter['page_size']);
            }
        }

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $item) {
                $order = $item['get_wholesale_order_info'];
                $res[$key]['order_id'] = $order['order_id'];
                $res[$key]['order_sn'] = $order['order_sn'];
                $res[$key]['suppliers_id'] = $order['suppliers_id'];
                $suppliers_name = $order['get_suppliers']['suppliers_name'] ?? '';
                $res[$key]['shop_name'] = $suppliers_name;
                $res[$key]['goods_number'] = $item['goods_number'];
                $res[$key]['sales_price'] = $item['goods_price'];
                $res[$key]['total_fee'] = $item['goods_number'] * $item['goods_price'];
                $res[$key]['sales_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $order['add_time']);
            }
        }

        $arr = [
            'sale_list_data' => $res,
            'filter' => $filter,
            'page_count' => $filter['page_count'],
            'record_count' => $filter['record_count']
        ];

        return $arr;
    }
}
