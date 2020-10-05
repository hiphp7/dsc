<?php

namespace App\Custom\Distribute\Controllers\Seller;

use App\Modules\Seller\Controllers\GetAjaxContentController as FrontController;

/*
 * 获取ajax数据
 */

class GetAjaxContentController extends FrontController
{

    public function index()
    {
        load_helper('goods', 'seller');
        load_helper('visual');

        $_REQUEST['act'] = trim($_REQUEST['act']);

        $menus = isset($_SESSION['menus']) ? $_SESSION['menus'] : '';
        $this->smarty->assign('menus', $menus);

        $adminru = get_admin_ru_id();

        if ($_REQUEST['act'] == 'filter_category') {

            $cat_id = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
            $cat_type_show = empty($_REQUEST['cat_type_show']) ? 0 : intval($_REQUEST['cat_type_show']);
            $user_id = $adminru['ru_id'];
            $result = ['error' => 0, 'message' => '', 'content' => ''];
//            $table = isset($_REQUEST['table']) && $_REQUEST['table'] != 'undefined' ? trim($_REQUEST['table']) : 'category';
            $table = 'category';

            if ($table == 'wholesale_cat') {
                $user_id = 0;
            }

            //上级分类列表
            if ($cat_type_show == 1) {
                $parent_cat_list = get_seller_select_category($cat_id, 1, true, $user_id, $table);
                $filter_category_navigation = get_seller_array_category_info($parent_cat_list, $table);
            } else {
                $parent_cat_list = get_select_category($cat_id, 1, true, 0, $table);
                $filter_category_navigation = get_array_category_info($parent_cat_list, $table);
            }

            $cat_nav = "";
            if ($filter_category_navigation) {
                foreach ($filter_category_navigation as $key => $val) {
                    if ($key == 0) {
                        $cat_nav .= $val['cat_name'];
                    } elseif ($key > 0) {
                        $cat_nav .= " > " . $val['cat_name'];
                    }
                }
            } else {
                $cat_nav = $GLOBALS['_LANG']['select_cat'];
            }
            $result['cat_nav'] = $cat_nav;

            //分类级别
            $cat_level = count($parent_cat_list);

            if ($cat_type_show == 1) {
                if ($cat_level <= 3) {
                    $filter_category_list = get_seller_category_list($cat_id, 2, $user_id);
                } else {
                    $filter_category_list = get_seller_category_list($cat_id, 0, $user_id);
                    $cat_level -= 1;
                }
            } else {
                //补充筛选商家分类
                $seller_shop_cat = seller_shop_cat($user_id);

                if ($cat_level <= 3) {
                    $filter_category_list = get_category_list($cat_id, 2, $seller_shop_cat, $user_id, $cat_level, $table);
                } else {
                    $filter_category_list = get_category_list($cat_id, 0, [], $user_id, 0, $table);
                    $cat_level -= 1;
                }
            }

            $this->smarty->assign('user_id', $user_id); //分类等级

            if ($user_id) {
                $this->smarty->assign('seller_cat_type_show', $cat_type_show);
                $this->smarty->assign('seller_filter_category_navigation', $filter_category_navigation);
                $this->smarty->assign('seller_filter_category_list', $filter_category_list);
            } else {
                $this->smarty->assign('cat_type_show', $cat_type_show);
            }

            $this->smarty->assign('filter_category_level', $cat_level);
            $this->smarty->assign('table', $table);
            $this->smarty->assign('filter_category_navigation', $filter_category_navigation);
            $this->smarty->assign('filter_category_list', $filter_category_list);

            if ($cat_type_show) {
                if (empty($filter_category_list)) {
                    $result['type'] = 1;
                }
                $result['content'] = $this->smarty->fetch('library/filter_category_seller.lbi');
            } else {
                $result['content'] = $this->smarty->fetch('library/filter_category_seller.lbi');
            }

            return json_encode($result);
        }
        return parent::index();
    }

}
