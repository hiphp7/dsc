<?php

namespace App\Http\Controllers;

use App\Services\Category\CategoryBrandService;
use App\Services\Category\CategoryService;

class AjaxCategoryController extends InitController
{
    protected $categoryService;
    protected $categoryBrandService;

    public function __construct(
        CategoryService $categoryService,
        CategoryBrandService $categoryBrandService
    )
    {
        $this->categoryService = $categoryService;
        $this->categoryBrandService = $categoryBrandService;
    }

    public function index()
    {

        $result = ['error' => 0, 'message' => '', 'content' => ''];

        //jquery Ajax跨域
        $is_jsonp = intval(request()->input('is_jsonp', 0));
        $act = request()->input('act', '');

        /*------------------------------------------------------ */
        //-- 分类树子分类
        /*------------------------------------------------------ */
        if ($act == 'getCategoryCallback') {

            $cat_id = intval(request()->input('cat_id', 0));
            $warehouse_id = intval(request()->input('warehouse_id', 0));
            $area_id = intval(request()->input('area_id', 0));
            $area_city = intval(request()->input('area_city', 0));

            $cat_topic_file = "category_topic" . $cat_id;
            $category_topic = cache($cat_topic_file);
            $category_topic = !is_null($category_topic) ? $category_topic : false;

            if ($category_topic === false) {
                $category_topic = $this->categoryService->getCategoryTopic($cat_id);
                if ($category_topic) {
                    cache()->forever($cat_topic_file, $category_topic);
                }
            }

            $this->smarty->assign('category_topic', $category_topic);

            $cat_file = "category_tree_child" . $cat_id;
            $child_tree = cache($cat_file);
            $child_tree = !is_null($child_tree) ? $child_tree : false;

            //分类树子分类分类列表
            if ($child_tree === false) {
                $child_tree = $this->categoryService->catList($cat_id, 1);
                cache()->forever($cat_file, $child_tree);
            }

            $this->smarty->assign('child_tree', $child_tree);

            $has_child = 0;
            if (!empty($child_tree)) {
                foreach ($child_tree as $k => $v) {
                    if (isset($v['child_tree']) && !empty($v['child_tree'])) {
                        $has_child = 1;
                        break;
                    }
                }
            }
            $result['has_child'] = $has_child;
            $this->smarty->assign('has_child', $has_child);

            //分类树品牌
            $brands_file = "category_tree_brands" . $cat_id;
            $brands_ad = cache($brands_file);
            $brands_ad = !is_null($brands_ad) ? $brands_ad : false;

            if ($brands_ad === false) {
                $brands_ad = $this->categoryBrandService->getCategoryBrandsAd($cat_id, $warehouse_id, $area_id, $area_city);
                cache()->forever($brands_file, $brands_ad);
            }

            $this->smarty->assign('brands_ad', $brands_ad);

            $result['cat_id'] = $cat_id;
            $result['cat_content'] = $this->smarty->fetch("library/index_cat_tree.lbi");
        }

        /*------------------------------------------------------ */
        //-- 加载商家分类
        /*------------------------------------------------------ */
        elseif ($act == 'cat_store_list') {
            $merchant_id = intval(request()->input('merchant_id', 0));

            $cat_list = $this->categoryService->getMerchantsCatList(0, $merchant_id);

            $this->smarty->assign('cat_store_list', $cat_list);
            $result['content'] = $this->smarty->fetch('library/cat_store_list.lbi');
        }

        return response()->json($result);
    }
}
