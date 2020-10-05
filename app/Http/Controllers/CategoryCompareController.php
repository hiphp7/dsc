<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Services\Article\ArticleCommonService;
use App\Services\Category\CategoryGoodsService;
use App\Services\Goods\GoodsService;

/**
 * 商品比较程序
 */
class CategoryCompareController extends InitController
{
    protected $goodsService;
    protected $categoryGoodsService;
    protected $articleCommonService;

    public function __construct(
        GoodsService $goodsService,
        CategoryGoodsService $categoryGoodsService,
        ArticleCommonService $articleCommonService
    )
    {
        $this->goodsService = $goodsService;
        $this->categoryGoodsService = $categoryGoodsService;
        $this->articleCommonService = $articleCommonService;
    }

    public function index()
    {
        /**
         * Start
         *
         * @param $warehouse_id 仓库ID
         * @param $area_id 省份ID
         * @param $area_city 城市ID
         */
        $warehouse_id = $this->warehouseId();
        $area_id = $this->areaId();
        $area_city = $this->areaCity();
        /* End */

        $goods_ids = addslashes_deep(request()->input('goods', 0));

        $cache_goods = '';
        if ($goods_ids) {
            $cache_goods = is_array($goods_ids) ? implode(',', $goods_ids) : $goods_ids;
        }

        //模板缓存
        $cache_id = sprintf('%X', crc32($cache_goods . '-' . $warehouse_id . '-' . $area_id . '-' . $area_city . '-' . session('user_rank', 0) . '_' . $this->config['lang']));
        $content = cache()->remember('category_compare.dwt.' . $cache_id, $this->config['cache_time'], function () use ($goods_ids, $warehouse_id, $area_id, $area_city) {
            if (!empty($goods_ids) && is_array($goods_ids) && count($goods_ids) > 1) {

                $compare = request()->input('compare', '');
                $highlight = request()->input('highlight', '');

                $cat_compare = $this->categoryGoodsService->getCatCompare($goods_ids, $compare, $highlight, $warehouse_id, $area_id, $area_city);
                $goods_list = $cat_compare['goods_list'];
                $basic_arr = $cat_compare['basic_arr'];
                $type_id = $cat_compare['type_id'];

                $param_goods_id = '';
                $g_count = count($goods_ids);
                $new_goods_arr = array_unique($goods_ids);
                $n_count = count($new_goods_arr);
                foreach ($goods_ids as $goods_id_val) {
                    if (empty($goods_id_val) || $g_count != $n_count) {
                        return show_message($GLOBALS['_LANG']['prompt_page']);
                    }
                    $param_goods_id .= 'goods[]=' . $goods_id_val . '&amp;';
                }
                $param_goods_id = substr($param_goods_id, 0, -5);

                $attribute = Attribute::select('attr_id', 'attr_name', 'attr_input_category')
                    ->where('cat_id', $type_id)
                    ->orderByRaw('sort_order, attr_id asc')
                    ->get();
                $attribute = $attribute ? $attribute->toArray() : [];

                if ($attribute) {
                    $arr = [];
                    foreach ($attribute as $rt) {
                        $arr[$rt['attr_id']]['attr_id'] = $rt['attr_id'];
                        $arr[$rt['attr_id']]['attr_name'] = $rt['attr_name'];
                        $attribute_basic[$rt['attr_id']] = $rt['attr_name'];
                    }

                    $attribute = $arr;
                }

                //高亮显示不同
                if ($highlight == 1) {
                    if ($attribute) {
                        foreach ($attribute as $key => $val) {
                            $basic_gid_arr = [];
                            $basic_gid = [];
                            foreach ($basic_arr as $gid => $v) {
                                if (isset($basic_arr[$gid]['properties'][$key]['value'])) {
                                    $basic_gid_arr[] = str_replace(' ', '', $basic_arr[$gid]['properties'][$key]['value']);
                                }
                                $basic_gid[] = $gid;
                            }

                            $basic_unique = array_unique($basic_gid_arr);
                            if (!(count($basic_unique) == 1) && !empty($basic_unique)) {
                                $attribute[$key]['attr_highlight'] = 1;
                            }
                        }
                    }

                    if (!empty($goods_list)) {
                        $brand_diff = array_unique(array_column($goods_list, 'brand_name'));
                        $weight_diff = array_unique(array_column($goods_list, 'goods_weight'));
                        if (count($brand_diff) > 1) {
                            $this->smarty->assign('brand_attr_highlight', 1);
                        }
                        if (count($weight_diff) > 1) {
                            $this->smarty->assign('weight_attr_highlight', 1);
                        }
                    }
                }

                //隐藏相同项
                //拿出第一个数组的key
                //查找其他数组中是否存在这个key
                //如果所有的数组都存在这个key则隐藏，反之则跳过
                if ($compare == 1) {
                    if ($attribute) {
                        foreach ($attribute as $key => $val) {
                            $basic_gid_arr = [];
                            $basic_gid = [];
                            foreach ($basic_arr as $gid => $v) {
                                if (isset($basic_arr[$gid]['properties'][$key]['value'])) {
                                    $basic_gid_arr[] = str_replace(' ', '', $basic_arr[$gid]['properties'][$key]['value']);
                                }
                                $basic_gid[] = $gid;
                            }
                            $basic_unique = array_unique($basic_gid_arr);

                            if ((count($basic_unique) == 1) || empty($basic_unique)) {
                                foreach ($basic_gid as $b_val) {
                                    unset($basic_arr[$b_val]['properties'][$key]);
                                }
                                unset($attribute[$key]);
                            }
                        }
                    }
                    if (!empty($goods_list)) {
                        $brand_like = array_unique(array_column($goods_list, 'brand_name'));
                        $weight_like = array_unique(array_column($goods_list, 'goods_weight'));
                        if (count($brand_like) == 1) {
                            $this->smarty->assign('brand_attr_hidden', 1);
                        }
                        if (count($weight_like) == 1) {
                            $this->smarty->assign('weight_attr_hidden', 1);
                        }
                    }
                }

                //@author guan 暂无对比项 start
                $len = 4 - count($goods_list);
                $goods_count = [];
                for ($c = 1; $c <= $len; $c++) {
                    $goods_count[] = $c;
                }
                $this->smarty->assign('goods_count', $goods_count);
                //@author guan 暂无对比项 end

                $this->smarty->assign('attribute', $attribute);
                $this->smarty->assign('goods_list', $goods_list);
                $this->smarty->assign('basic_arr', $basic_arr);
                $this->smarty->assign('is_compare', $compare);
                $this->smarty->assign('is_highlight', $highlight);
                $this->smarty->assign('ids', $param_goods_id);
            } else {
                return show_message($GLOBALS['_LANG']['compare_no_goods']);
            }

            assign_template();
            $position = assign_ur_here(0, $GLOBALS['_LANG']['goods_compare']);
            $this->smarty->assign('page_title', $position['title']);    // 页面标题
            $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置

            /**
             * Start
             *
             * 商品推荐
             * 【'best' ：精品, 'new' ：新品, 'hot'：热销】
             */

            $where = [
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city
            ];

            /* 推荐商品 */
            $where['type'] = 'best';
            $best_goods = $this->goodsService->getRecommendGoods($where);

            $this->smarty->assign('best_goods', $best_goods);
            /* End */

            $categories_pro = get_category_tree_leve_one();
            $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版
            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助

            assign_dynamic('compare');

            return $this->smarty->display('category_compare.dwt');
        });

        return $content;
    }
}
