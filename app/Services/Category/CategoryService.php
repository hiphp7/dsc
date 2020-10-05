<?php

namespace App\Services\Category;

use App\Models\Category;
use App\Models\CatRecommend;
use App\Models\Goods;
use App\Models\MerchantsCategory;
use App\Models\PresaleCat;
use App\Models\WholesaleCat;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;
use Illuminate\Support\Carbon;

/**
 * Class CategoryService
 * @package App\Services\Category
 */
class CategoryService
{
    protected $config;
    protected $baseRepository;
    protected $dscRepository;
    protected $merchantCommonService;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 取得最近的上级分类的grade值
     *
     * @access  public
     * @param int $cat_id //当前的cat_id
     *
     * @return int
     */
    public function getParentGrade($cat_id)
    {
        static $res = null;

        $res = Category::select('parent_id', 'cat_id', 'grade')->get();
        $res = $res ? $res->toArray() : [];

        if ($res) {
            $parent_arr = [];
            $grade_arr = [];

            foreach ($res as $val) {
                $parent_arr[$val['cat_id']] = $val['parent_id'];
                $grade_arr[$val['cat_id']] = $val['grade'];
            }

            while ($parent_arr[$cat_id] > 0 && $grade_arr[$cat_id] == 0) {
                $cat_id = $parent_arr[$cat_id];
            }

            return $grade_arr[$cat_id];
        } else {
            return 0;
        }
    }

    /**
     * 多维数组转一维数组【分类】
     *
     * 根据父级分类ID查找所有子分类
     *
     * @access  public
     * @param array $list
     *
     * @return int
     */
    public function getCatListChildren($parent_id = 0)
    {

        //顶级分类页分类显示
        $cache_name = 'get_cat_list_children' . $parent_id;

        $cat_list = cache($cache_name);
        $cat_list = !is_null($cat_list) ? $cat_list : false;

        //将数据写入缓存文件
        if ($cat_list === false) {
            $cat_list = Category::getList($parent_id)
                ->where('is_show', 1)
                ->orderBy('sort_order')
                ->orderBy('cat_id');

            $cat_list = $this->baseRepository->getToArrayGet($cat_list);

            if ($cat_list) {
                $cat_list = $this->dscRepository->getCatVal($cat_list);
                $cat_list = $this->baseRepository->getFlatten($cat_list);

                $cat_list = !empty($parent_id) ? collect($cat_list)->prepend($parent_id)->all() : $cat_list;
            } else {
                $cat_list = [$parent_id];
            }

            cache()->forever($cache_name, $cat_list);
        }

        return $cat_list;
    }

    /**
     * 获得分类列表
     *
     * @access  public
     * @param int $cat_id
     *
     * @return array
     */
    public function getCatList($cat_id = 0)
    {
        $cache_id = 'get_cat_list_' . $cat_id;

        //顶级分类页分类显示
        $arr = cache($cache_id);
        $arr = !is_null($arr) ? $arr : false;

        //将数据写入缓存文件
        if ($arr === false) {
            $categories_child = Category::getList($cat_id)
                ->where('is_show', 1)
                ->orderBy('sort_order')
                ->orderBy('cat_id')
                ->get();
            $categories_child = $categories_child->toArray();

            $arr = [];
            if ($categories_child) {
                foreach ($categories_child as $key => $val) {

                    $val['cat_icon'] = !empty($val['cat_icon']) ? $this->dscRepository->getImagePath($val['cat_icon']) : '';
                    $val['touch_catads'] = !empty($val['touch_catads']) ? $this->dscRepository->getImagePath($val['touch_catads']) : '';
                    $val['touch_icon'] = !empty($val['touch_icon']) ? $this->dscRepository->getImagePath($val['touch_icon']) : '';

                    $arr[$val['cat_id']] = $val;
                }
            }

            cache([$cache_id => $arr], Carbon::now()->addDays(7));
        }

        return $arr;
    }

    /**
     * 获取当前分类的子分类列表
     * @param int $cat_id
     * @return \Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getCategoryChild($cat_id = 0)
    {
        $cache_id = 'get_cat_child_' . $cat_id;

        $arr = cache($cache_id);
        $arr = !is_null($arr) ? $arr : false;

        if ($arr === false) {
            $arr = Category::where('is_show', 1)
                ->where('parent_id', $cat_id)
                ->orderBy('sort_order')
                ->orderBy('cat_id')
                ->get();

            $arr = $arr ? $arr->toArray() : [];

            if ($arr) {
                foreach ($arr as $k => $v) {
                    $goods_thumb = Goods::where('cat_id', $v['cat_id'])
                        ->where('is_delete', 0)
                        ->where('is_on_sale', 1)
                        ->where('is_alone_sale', 1)
                        ->where('review_status', '>', 2)
                        ->orderBy('sort_order')
                        ->orderBy('goods_id', 'DESC')
                        ->value('goods_thumb');

                    $v['touch_icon'] = empty($v['touch_icon']) ? $goods_thumb : $v['touch_icon'];

                    $arr[$k]['touch_icon'] = $this->dscRepository->getImagePath($v['touch_icon']);
                    $arr[$k]['cat_icon'] = $this->dscRepository->getImagePath($v['cat_icon']);
                    $arr[$k]['cat_name'] = (isset($v['cat_alias_name']) && !empty($v['cat_alias_name'])) ? $v['cat_alias_name'] : $v['cat_name'];
                }
            }

            cache([$cache_id => $arr], Carbon::now()->addDays(7));
        }

        return $arr;
    }

    /**
     * 获取当前分类的子分类列表
     * @param int $cat_id
     * @return \Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getMobileCategoryChild($cat_id = 0)
    {
        $cache_id = 'get_mobile_cat_child_' . $cat_id;

        $arr = cache($cache_id);
        $arr = !is_null($arr) ? $arr : false;

        if ($arr === false) {
            $arr = Category::where('is_show', 1)
                ->where('parent_id', $cat_id)
                ->orderBy('sort_order')
                ->orderBy('cat_id');

            $arr = $arr->with([
                'getGoods' => function ($query) {
                    $query = $query->where('is_on_sale', 1)
                        ->where('is_show', 1)
                        ->where('is_alone_sale', 1)
                        ->where('is_delete', 0);

                    if ($this->config['review_goods']) {
                        $query = $query->where('review_status', '>', 2);
                    }

                    $query->orderBy('goods_id', 'desc');
                }
            ]);

            $arr = $this->baseRepository->getToArrayGet($arr);

            if ($arr) {
                foreach ($arr as $key => $v) {
                    $arr[$key]['cat_name'] = (isset($v['cat_alias_name']) && !empty($v['cat_alias_name'])) ? $v['cat_alias_name'] : $v['cat_name'];
                    $arr[$key]['cat_icon'] = $this->dscRepository->getImagePath($v['cat_icon']);
                    $arr[$key]['touch_catads'] = $this->dscRepository->getImagePath($v['touch_catads']);

                    if ($v['touch_icon']) {
                        $arr[$key]['touch_icon'] = $this->dscRepository->getImagePath($v['touch_icon']);
                    } else {
                        $arr[$key]['touch_icon'] = $this->dscRepository->getImagePath($v['get_goods']['goods_thumb'] ?? '');
                    }

                    unset($arr[$key]['get_goods']);

                    $arr[$key]['child'] = [];
                    if ($cat_id > 0) {
                        $arr[$key]['child'] = $this->getMobileCategoryChild($v['cat_id']);
                    }
                }
            }
            $arr = collect($arr)->values()->all();

            cache([$cache_id => $arr], Carbon::now()->addDays(7));
        }

        return $arr;
    }

    /**
     * 获取分类详情
     * @param int $cat_id
     * @return \Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getCategory($cat_id = 0)
    {
        $cache_id = 'get_cat_' . $cat_id;

        $arr = cache($cache_id);
        $arr = !is_null($arr) ? $arr : false;

        if ($arr === false) {
            $arr = Category::where('is_show', 1)
                ->where('cat_id', $cat_id)
                ->first();

            $arr = $arr ? $arr->toArray() : [];

            cache([$cache_id => $arr], Carbon::now()->addDays(7));
        }

        return $arr;
    }

    /**
     * 多维数组转一维数组【分类】
     *
     * @param int $parent_id
     * @return array|mixed|string
     * @throws \Exception
     */
    public function getMerchantsCatListChildren($parent_id = 0)
    {
        //顶级分类页分类显示
        $cat_list = read_static_cache('get_merchants_cat_list_children' . $parent_id);

        //将数据写入缓存文件
        if ($cat_list === false) {
            $cat_list = MerchantsCategory::getList($parent_id)
                ->where('is_show', 1)
                ->orderBy('sort_order')
                ->orderBy('cat_id')
                ->get();

            $cat_list = $cat_list ? $cat_list->toArray() : [];

            if ($cat_list) {
                $cat_list = $this->dscRepository->getCatVal($cat_list);
                $cat_list = $this->baseRepository->getFlatten($cat_list);

                $cat_list = !empty($parent_id) ? collect($cat_list)->prepend($parent_id)->all() : $cat_list;
            } else {
                $cat_list = [$parent_id];
            }

            $cat_list = collect($cat_list)->values()->all();

            write_static_cache('get_merchants_cat_list_children' . $parent_id, $cat_list);
        }

        return $cat_list;
    }

    /**
     * 获得分类列表
     *
     * @param int $cat_id
     * @param int $ru_id
     * @return array|bool|\Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getMerchantsCatList($cat_id = 0, $ru_id = 0)
    {

        //顶级分类页分类显示
        $cache_name = 'get_merchants_cat_list' . $cat_id . "_" . $ru_id;

        $arr = cache($cache_name);
        $arr = !is_null($arr) ? $arr : [];

        //将数据写入缓存文件
        if (empty($arr)) {
            $categories_child = MerchantsCategory::getList($cat_id)
                ->where('user_id', $ru_id)
                ->where('is_show', 1)
                ->orderBy('sort_order')
                ->orderBy('cat_id')
                ->get();
            $categories_child = $categories_child->toArray();

            $arr = [];
            if ($categories_child) {
                foreach ($categories_child as $key => $val) {
                    $arr[$val['cat_id']] = $val;
                }
            }

            $arr = collect($arr)->values()->all();

            cache()->forever($cache_name, $arr);
        }

        return $arr;
    }


    /**
     * 获得当前店铺下的分类
     *
     * @param int $parent_id
     * @param int $ru_id
     * @return array
     */
    public function getShopCat($parent_id = 0, $ru_id = 0)
    {
        $res = MerchantsCategory::select('cat_id', 'cat_name')->where('parent_id', $parent_id)->where('user_id', $ru_id)->get();
        $res = $res ? $res->toArray() : [];

        $arr = [];
        foreach ($res as $key => $row) {
            $arr[$key]['cat_id'] = $row['cat_id'];
            $arr[$key]['cat_name'] = $row['cat_name'];
            $arr[$key]['child'] = $this->getShopCat($row['cat_id'], $ru_id);
        }
        return $arr;
    }

    /**
     * 获取指定分类的顶级分类
     *
     * @param int $cat_id
     * @return array|string
     */
    public function getTopparentCat($cat_id = 0)
    {
        //静态数组
        static $cat_list = '';

        $cat_info = Category::select(['cat_id', 'cat_name', 'parent_id'])->where('cat_id', $cat_id);

        $cat_info = $cat_info->first();

        $cat_info = $cat_info ? $cat_info->toArray() : [];

        if (!empty($cat_info['parent_id'])) {
            $this->getTopparentCat($cat_info['parent_id']);
        } else {
            $cat_list = $cat_info;
        }

        return $cat_list;
    }

    /**
     * 递归获取父级ID
     *
     * @param int $cat
     * @param string $type
     * @return array
     */
    public function parentsCatList($cat = 0)
    {
        $arr = Category::select('cat_id', 'parent_id')->where('is_show', 1)->whereIn('cat_id', $cat);

        $arr = $arr->with([
            'catParentList'
        ]);

        $arr = $arr->get();

        $arr = $arr ? $arr->toArray() : [];

        $arr = $this->baseRepository->getFlatten($arr);

        $list = [];
        if ($arr) {
            foreach ($arr as $val) {
                if (is_numeric($val)) {
                    $list[] = $val;
                }
            }
        }

        $list = $list ? array_unique($list) : [];

        return $list;
    }

    /**
     * 处理优惠券可用分类id 含子分类
     *
     * @param string $cat
     * @return string
     */
    protected function getCouChildren($cat = '')
    {
        $catlist = '';
        if ($cat) {
            $cat = explode(",", $cat);
            foreach ($cat as $key => $row) {
                $row = intval($row);

                $list = $this->getCatListChildren($row);
                $list = $this->baseRepository->getImplode($list);
                $catlist .= $list . ",";
            }
        }

        return $catlist;
    }

    /**
     * 插入首页推荐扩展分类
     *
     * @access  public
     * @param array $recommend_type 推荐类型
     * @param integer $cat_id 分类ID
     *
     * @return void
     */
    public function getInsertCatRecommend($recommend_type, $cat_id)
    {
        //检查分类是否为首页推荐
        if (!empty($recommend_type)) {
            //取得之前的分类
            $recommend_res = CatRecommend::where('cat_id', $cat_id);
            $recommend_res = $this->baseRepository->getToArrayGet($recommend_res);

            if ($recommend_res) {
                $old_data = [];
                foreach ($recommend_res as $data) {
                    $old_data[] = $data['recommend_type'];
                }
                $delete_array = array_diff($old_data, $recommend_type);
                if (!empty($delete_array)) {
                    CatRecommend::where('cat_id', $cat_id)
                        ->whereIn('recommend_type', $delete_array)
                        ->delete();
                }
                $insert_array = array_diff($recommend_type, $old_data);
                if (!empty($insert_array)) {
                    foreach ($insert_array as $data) {
                        $data = intval($data);
                        CatRecommend::insert([
                            'cat_id' => $cat_id,
                            'recommend_type' => $data
                        ]);
                    }
                }
            } else {
                foreach ($recommend_type as $data) {
                    $data = intval($data);
                    CatRecommend::insert([
                        'cat_id' => $cat_id,
                        'recommend_type' => $data
                    ]);
                }
            }
        } else {
            CatRecommend::where('cat_id', $cat_id)
                ->delete();
        }
    }

    /**
     * @param int $cat_id 分类的ID
     * @param int $type 查子分类
     * @param int $getrid 去掉其它，保留分类ID
     * @param string $table 表名称
     * @param array $seller_shop_cat 商家分类集
     * @param int $cat_level 层级
     * @param int $user_id 商家ID
     * @return array|bool|\Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function catList($cat_id = 0, $type = 0, $getrid = 0, $table = 'category', $seller_shop_cat = [], $cat_level = 0, $user_id = 0)
    {
        $cache_name = 'main_cat_ist' . $cat_id . '_' . $getrid . '_' . $type . '_' . $user_id . '_' . $table;
        $arr = cache($cache_name);
        $arr = !is_null($arr) ? $arr : false;

        if ($seller_shop_cat) {
            if ($seller_shop_cat['parent'] && $seller_shop_cat['parent'] && $cat_level < 3) {
                $seller_shop_cat['parent'] = $this->dscRepository->delStrComma($seller_shop_cat['parent']);
            }
        }

        $seller_parent = $seller_shop_cat['parent'] ?? '';

        if (!empty($seller_parent)) {

            if (is_array($seller_parent)) {
                $cache_cat = implode(',', $seller_parent);
            } else {
                $cache_cat = $seller_parent;
            }

            $cache_name = 'main_cat_ist' . $cat_id . '_' . $getrid . '_' . $type . '_' . $user_id . '_' . $table . '_' . $cache_cat;
            $arr = cache($cache_name);
            $arr = !is_null($arr) ? $arr : false;
        }

        if ($arr === false || !empty($seller_parent)) {

            if ($table == 'merchants_category') {
                $res = MerchantsCategory::whereRaw(1);
                if ($user_id > 0) {
                    $res = $res->where('user_id', $user_id);
                }
            } elseif ($table == 'presale_cat') {
                $res = PresaleCat::whereRaw(1);
            } else {
                $res = Category::whereRaw(1);
            }

            if ($table != 'presale_cat') {
                $res = $res->where('is_show', 1);
            }

            $res = $res->where('parent_id', $cat_id);

            if ($seller_parent) {
                $seller_parent = $this->baseRepository->getExplode($seller_parent);
                $res = $res->whereIn('cat_id', $seller_parent);
            }

            $res = $res->orderBy('sort_order')->orderBy('cat_id');

            $res = $this->baseRepository->getToArrayGet($res);

            $arr = [];
            if ($res) {
                foreach ($res as $key => $row) {
                    if ($getrid == 0) {
                        $row['cat_name'] = htmlspecialchars(addslashes(str_replace("\r\n", "", $row['cat_name'])), ENT_QUOTES);//特殊字符处理
                        $row['level'] = 0;
                        $row['select'] = str_repeat('&nbsp;', $row['level'] * 4);
                        $arr[$row['cat_id']] = $row;

                        if ($table == 'merchants_category') {
                            $build_uri = [
                                'cid' => $row['cat_id'],
                                'urid' => $row['user_id'],
                                'append' => $row['cat_name']
                            ];

                            $domain_url = $this->merchantCommonService->getSellerDomainUrl($row['user_id'], $build_uri);
                            $arr[$row['cat_id']]['url'] = $domain_url['domain_name'];
                        } else {
                            $arr[$row['cat_id']]['url'] = $this->dscRepository->buildUri('category', ['cid' => $row['cat_id']], $row['cat_name']);
                        }
                    } else {
                        $arr[$row['cat_id']]['cat_id'] = $row['cat_id'];
                    }

                    if ($type) {
                        $arr[$row['cat_id']]['child_tree'] = $this->getChildTreePro($row['cat_id'], 0, $table, $getrid, $user_id);
                    }

                    //图标
                    if ($getrid == 0 && $table == 'category') {
                        $arr[$row['cat_id']]['cat_icon'] = $row['cat_icon'];
                        $arr[$row['cat_id']]['style_icon'] = $row['style_icon'];
                    }
                }
            }

            cache()->forever($cache_name, $arr);
        }

        return $arr;
    }

    /**
     * 树形分类列表
     *
     * @param int $tree_id
     * @param int $level
     * @param string $table
     * @param int $getrid
     * @param int $user_id
     * @return array
     * @throws \Exception
     */
    public function getChildTreePro($tree_id = 0, $level = 0, $table = 'category', $getrid = 0, $user_id = 0)
    {
        $three_arr = [];
        if ($table == 'merchants_category') {
            $res = MerchantsCategory::where('parent_id', $tree_id)
                ->where('is_show', 1);

            if ($user_id > 0) {
                $res = $res->where('user_id', $user_id);
            }

            $cat_id = $res->value('cat_id');
        } elseif ($table == 'wholesale_cat') {
            $res = WholesaleCat::where('parent_id', $tree_id)
                ->where('is_show', 1);

            $cat_id = $res->value('cat_id');
        } else {
            $res = Category::where('parent_id', $tree_id)
                ->where('is_show', 1);

            $cat_id = $res->value('cat_id');
        }

        if ($cat_id || $tree_id == 0) {
            if ($table == 'merchants_category') {
                $res = MerchantsCategory::where('parent_id', $tree_id)
                    ->where('is_show', 1);

                if ($user_id > 0) {
                    $res = $res->where('user_id', $user_id);
                }

                $res = $res->orderBy('sort_order')->orderBy('cat_id');
            } elseif ($table == 'wholesale_cat') {
                $res = WholesaleCat::where('parent_id', $tree_id)
                    ->where('is_show', 1);
            } else {
                $res = Category::where('parent_id', $tree_id)
                    ->where('is_show', 1);

                $res = $res->orderBy('sort_order')->orderBy('cat_id');
            }

            $res = $this->baseRepository->getToArrayGet($res);

            if ($res) {
                foreach ($res as $row) {
                    $three_arr[$row['cat_id']]['id'] = $row['cat_id'];

                    if ($getrid == 0) {
                        $three_arr[$row['cat_id']]['name'] = htmlspecialchars(addslashes(str_replace("\r\n", "", $row['cat_name'])), ENT_QUOTES); //特殊字符处理

                        if ($table == 'merchants_category') {
                            $build_uri = [
                                'cid' => $row['cat_id'],
                                'urid' => $row['user_id'],
                                'append' => $row['cat_name']
                            ];

                            $domain_url = $this->merchantCommonService->getSellerDomainUrl($row['user_id'], $build_uri);
                            $three_arr[$row['cat_id']]['url'] = $domain_url['domain_name'];
                        } else {
                            $three_arr[$row['cat_id']]['url'] = $this->dscRepository->buildUri('category', ['cid' => $row['cat_id']], $row['cat_name']);
                        }

                        if ($table == 'merchants_category') {
                            $three_arr[$row['cat_id']]['ru_id'] = $row['user_id'];
                            $three_arr[$row['cat_id']]['seller_name'] = $this->merchantCommonService->getShopName($row['user_id'], 1);
                        }

                        if ($row['parent_id'] != 0) {
                            $three_arr[$row['cat_id']]['level'] = $level + 1;
                        } else {
                            $three_arr[$row['cat_id']]['level'] = $level;
                        }

                        $three_arr[$row['cat_id']]['select'] = str_repeat('&nbsp;', $three_arr[$row['cat_id']]['level'] * 4);
                    }

                    if (isset($row['cat_id']) != null) {
                        if ($row['parent_id'] != 0) {
                            $three_arr[$row['cat_id']]['cat_id'] = $this->getChildTreePro($row['cat_id'], $level + 1, $table, $getrid);
                        } else {
                            $three_arr[$row['cat_id']]['cat_id'] = $this->getChildTreePro($row['cat_id'], $level, $table, $getrid);
                        }
                    }

                    if (!$three_arr[$row['cat_id']]['cat_id'] && $getrid) {
                        unset($three_arr[$row['cat_id']]['cat_id']);
                    }
                }
            }
        }

        return $three_arr;
    }

    /**
     * 分类一维数组
     *
     * @param int $cat_id
     * @param int $type
     * @param string $table
     * @return array|bool|\Illuminate\Cache\CacheManager|mixed|string
     * @throws \Exception
     */
    public function getArrayKeysCat($cat_id = 0, $type = 0, $table = 'category')
    {
        $list = $this->catList($cat_id, 1, 1, $table);
        $list = $this->baseRepository->getFlatten($list);

        if ($type == 1) {
            if ($list) {
                $list = implode(',', $list);
                $list = $this->dscRepository->delStrComma($list);
            }
        }

        return $list;
    }


    /**
     * 自定义导航调取分类
     *
     * @param int $cat_id
     * @return array|bool|\Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getCategoriesTreeXaphp($cat_id = 0)
    {
        $cache_name = "get_categories_tree_xaphp_" . $cat_id;
        $cat_arr = cache($cache_name);
        $cat_arr = !is_null($cat_arr) ? $cat_arr : false;

        if ($cat_arr === false) {
            /* 获取当前分类及其子分类 */
            $res = Category::where('parent_id', $cat_id)
                ->where('is_show', 1)
                ->orderBy('sort_order')
                ->orderBy('cat_id');

            $res = $this->baseRepository->getToArrayGet($res);

            $cat_arr = [];
            if ($res) {
                foreach ($res as $row) {
                    $cat_arr[$row['cat_id']]['id'] = $row['cat_id'];
                    $cat_arr[$row['cat_id']]['name'] = $row['cat_name'];
                    $cat_arr[$row['cat_id']]['url'] = $this->baseRepository->buildUri('category', ['cid' => $row['cat_id']], $row['cat_name']);

                    if (isset($row['cat_id']) != null) {
                        $cat_arr[$row['cat_id']]['cat_id'] = $this->getChildTree($row['cat_id']);
                    }
                }
            }

            cache()->forever($cache_name, $cat_arr);
        }

        return $cat_arr;
    }


    /**
     * 子分类
     *
     * @param int $tree_id
     * @param int $ru_id
     * @return array|bool|\Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getChildTree($tree_id = 0, $ru_id = 0)
    {
        $cache_name = "get_child_tree_" . $tree_id . '_' . $ru_id;
        $three_arr = cache($cache_name);
        $three_arr = !is_null($three_arr) ? $three_arr : false;

        if ($three_arr === false) {
            $three_arr = [];

            $count = Category::where('parent_id', $tree_id)
                ->count();

            if ($count || $tree_id == 0) {
                $res = Category::where('parent_id', $tree_id)
                    ->where('is_show', 1)
                    ->orderByRaw("sort_order, cat_id asc");
                $res = $this->baseRepository->getToArrayGet($res);

                if ($res) {
                    foreach ($res as $row) {
                        if ($row['is_show']) {
                            $three_arr[$row['cat_id']]['id'] = $row['cat_id'];
                        }

                        $three_arr[$row['cat_id']]['name'] = $row['cat_name'];

                        if ($ru_id) {
                            $build_uri = [
                                'cid' => $row['cat_id'],
                                'urid' => $ru_id,
                                'append' => $row['cat_name']
                            ];

                            $domain_url = $this->merchantCommonService->getSellerDomainUrl($ru_id, $build_uri);
                            $three_arr[$row['cat_id']]['url'] = $domain_url['domain_name'];
                        } else {
                            $three_arr[$row['cat_id']]['url'] = $this->dscRepository->buildUri('category', ['cid' => $row['cat_id']], $row['cat_name']);
                        }

                        if (isset($row['cat_id']) != null) {
                            $three_arr[$row['cat_id']]['cat_id'] = $this->getChildTree($row['cat_id']);
                        }
                    }
                }
            }

            cache()->forever($cache_name, $three_arr);
        }

        return $three_arr;
    }

    /**
     * 页面分类树导航顶级分类专题模块
     *
     * @param int $cat_id
     * @return array
     */
    public function getCategoryTopic($cat_id = 0)
    {
        $category_topic = Category::where('cat_id', $cat_id)->value('category_topic');
        $category_topic = $category_topic ? $category_topic : '';

        $arr = [];
        if ($category_topic) {
            if ($category_topic) {
                $category_topic_arr = explode("\r\n", $category_topic);
                foreach ($category_topic_arr as $key => $row) {
                    if ($row) {
                        $row = explode("|", $row);
                        $arr[$key]['topic_name'] = $row[0];
                        $arr[$key]['topic_link'] = $row[1];
                    }
                }
            }
        }

        return $arr;
    }
}
