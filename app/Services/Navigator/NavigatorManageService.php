<?php

namespace App\Services\Navigator;

use App\Models\ArticleCat;
use App\Models\Category;
use App\Models\Nav;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Commission\CommissionService;
use App\Services\Order\OrderService;


class NavigatorManageService
{
    protected $baseRepository;
    protected $orderService;
    protected $commissionService;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        OrderService $orderService,
        CommissionService $commissionService,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->orderService = $orderService;
        $this->commissionService = $commissionService;
        $this->dscRepository = $dscRepository;
    }


    public function getNav()
    {
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? '' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);

        $filter['record_count'] = Nav::count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 查询 */
        $res = Nav::orderBy('type', 'DESC');
        if (empty($_REQUEST['sort_by'])) {
            $res = $res->orderBy('vieworder', 'ASC');
        } else {
            $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);
        }
        $res = $res->offset($filter['start'])
            ->limit($filter['page_size']);
        $navdb = $this->baseRepository->getToArrayGet($res);

        $type = "";
        $navdb2 = [];
        foreach ($navdb as $k => $v) {
            if (!empty($type) && $type != $v['type']) {
                $navdb2[] = [];
            }
            $navdb2[] = $v;
            $type = $v['type'];
        }

        $arr = ['navdb' => $navdb2, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /*------------------------------------------------------ */
    //-- 排序相�    �
    /*------------------------------------------------------ */
    public function sortNav($a, $b)
    {
        return $a['vieworder'] > $b['vieworder'] ? 1 : -1;
    }

    /*------------------------------------------------------ */
    //-- 获得系统列表
    /*------------------------------------------------------ */
    public function getSysnav()
    {
        $sysmain = [
            [$GLOBALS['_LANG']['view_cart'], 'flow.php'],
            [$GLOBALS['_LANG']['brand'], 'brand.php'],
            [$GLOBALS['_LANG']['group_buy_goods'], 'group_buy.php'],
            [$GLOBALS['_LANG']['news'], 'news.php'],
            [$GLOBALS['_LANG']['snatch'], 'snatch.php'],
            [$GLOBALS['_LANG']['user_center'], 'user.php'],
            [$GLOBALS['_LANG']['wholesale'], 'wholesale.php'],
            [$GLOBALS['_LANG']['activity'], 'activity.php'],
            [$GLOBALS['_LANG']['auction'], 'auction.php'],
            [$GLOBALS['_LANG']['package'], 'package.php'],
            [$GLOBALS['_LANG']['exchange'], 'exchange.php'],
            [$GLOBALS['_LANG']['gift_gard'], 'gift_gard.php'],
            [$GLOBALS['_LANG']['crowdfunding'], 'crowdfunding.php'],
            [$GLOBALS['_LANG']['coupons'], 'coupons.php?act=coupons_index'],
            [$GLOBALS['_LANG']['store_street'], 'store_street.php'],
            [$GLOBALS['_LANG']['history_list'], 'history_list.php'],
            [$GLOBALS['_LANG']['message'], 'message.php'],
            [$GLOBALS['_LANG']['merchants'], 'merchants.php'],
        ];

        return $sysmain;
    }

    /*------------------------------------------------------ */
    //-- 根据URI对导航栏项目进行分析，确定为商品分类还是文章分类
    /*------------------------------------------------------ */
    public function analyseUri($uri)
    {
        $uri = strtolower(str_replace('&amp;', '&', $uri));
        $arr = explode('-', $uri);
        switch ($arr[0]) {
            case 'category':
                return ['type' => 'c', 'id' => $arr[1]];
                break;
            case 'article_cat':
                return ['type' => 'a', 'id' => $arr[1]];
                break;
            default:

                break;
        }

        $uri_arr = explode('?', $uri);
        $uri_arr[1] = isset($uri_arr[1]) ? $uri_arr[1] : '';
        list($fn, $pm) = $uri_arr;

        if (strpos($uri, '&') === false) {
            $arr = [$pm];
        } else {
            $arr = explode('&', $pm);
        }
        switch ($fn) {
            case 'category.php':
                //商品分类
                foreach ($arr as $k => $v) {
                    $v_arr = explode('=', $v);
                    $v_arr['1'] = isset($v_arr) ? $v_arr : '';
                    list($key, $val) = $v_arr['1'];
                    if ($key == 'id') {
                        return ['type' => 'c', 'id' => $val];
                    }
                }
                break;
            case 'article_cat.php':
                //文章分类
                foreach ($arr as $k => $v) {
                    list($key, $val) = explode('=', $v);
                    if ($key == 'id') {
                        return ['type' => 'a', 'id' => $val];
                    }
                }
                break;
            default:
                //未知
                return false;
                break;
        }
    }

    /*------------------------------------------------------ */
    //-- 是否显示
    /*------------------------------------------------------ */
    public function isShowInNav($type, $id)
    {
        $tablename = '';
        if ($type == 'c') {
            $tablename = Category::where('cat_id', $id)->value('show_in_nav');
        } else {
            $tablename = ArticleCat::where('cat_id', $id)->value('show_in_nav');
        }
        $tablename = $tablename ? $tablename : '';
        return $tablename;
    }

    /*------------------------------------------------------ */
    //-- 设置是否显示
    /*------------------------------------------------------ */
    public function setShowInNav($type, $id, $val)
    {
        $data = ['show_in_nav' => $val];
        if ($type == 'c') {
            Category::where('cat_id', $id)->update($data);
        } else {
            ArticleCat::where('cat_id', $id)->update($data);
        }

        clear_cache_files();
    }
}
