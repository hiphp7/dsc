<?php

namespace App\Services\Merchant;

use App\Models\ArticleCat;
use App\Models\Category;
use App\Models\MerchantsNav;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Commission\CommissionService;
use App\Services\Order\OrderService;


class MerchantsNavigatorManageService
{
    protected $baseRepository;
    protected $merchantCommonService;
    protected $orderService;
    protected $commissionService;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        OrderService $orderService,
        MerchantCommonService $merchantCommonService,
        CommissionService $commissionService,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->orderService = $orderService;
        $this->commissionService = $commissionService;
        $this->dscRepository = $dscRepository;
    }


    public function getNav()
    {
        $adminru = get_admin_ru_id();

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'vieworder' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);

        $filter['record_count'] = MerchantsNav::where('ru_id', $adminru['ru_id'])->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 查询 */
        $res = MerchantsNav::where('ru_id', $adminru['ru_id'])
            ->orderBy('type', 'DESC')
            ->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])
            ->limit($filter['page_size']);
        $navdb = $this->baseRepository->getToArrayGet($res);

        $type = "";
        $navdb2 = [];
        foreach ($navdb as $k => $v) {
            if (!empty($type) && $type != $v['type']) {
                $navdb2[] = [];
            }
            $navdb2[] = $v;

            $data = ['shoprz_brandName', 'shop_class_keyWords', 'shopNameSuffix'];
            $shop_info = get_table_date('merchants_shop_information', "user_id = '" . $v['ru_id'] . "'", $data);
            $navdb2[$k]['user_name'] = $shop_info['shoprz_brandName'] . $shop_info['shopNameSuffix'];

            $type = $v['type'];
        }

        $arr = ['navdb' => $navdb2, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /*------------------------------------------------------ */
    //-- 排序相�    �
    /*------------------------------------------------------ */
    public function sort_nav($a, $b)
    {
        return $a['vieworder'] > $b['vieworder'] ? 1 : -1;
    }

    /*------------------------------------------------------ */
    //-- 根据URI对导航栏项目进行分析，确定�    �为商品分类还是文章分类
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

        list($fn, $pm) = explode('?', $uri);

        if (strpos($uri, '&') === false) {
            $arr = [$pm];
        } else {
            $arr = explode('&', $pm);
        }
        switch ($fn) {
            case 'category.php':
                //商品分类
                foreach ($arr as $k => $v) {
                    list($key, $val) = explode('=', $v);
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
        $show_in_nav = '';
        if ($type == 'c') {
            $show_in_nav = Category::where('cat_id', $id)->value('show_in_nav');
        } else {
            $show_in_nav = ArticleCat::where('cat_id', $id)->value('show_in_nav');
        }
        $show_in_nav = $show_in_nav ? $show_in_nav : '';
        return $show_in_nav;
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
