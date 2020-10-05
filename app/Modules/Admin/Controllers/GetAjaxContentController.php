<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Image;
use App\Models\AdminAction;
use App\Models\Article;
use App\Models\ArticleCat;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\FloorContent;
use App\Models\GalleryAlbum;
use App\Models\Goods;
use App\Models\GoodsArticle;
use App\Models\GoodsGallery;
use App\Models\GoodsLib;
use App\Models\GoodsLibGallery;
use App\Models\GoodsType;
use App\Models\GoodsTypeCat;
use App\Models\GroupGoods;
use App\Models\LinkAreaGoods;
use App\Models\LinkDescTemporary;
use App\Models\LinkGoods;
use App\Models\LinkGoodsDesc;
use App\Models\MerchantsPrivilege;
use App\Models\PicAlbum;
use App\Models\SeckillGoods;
use App\Models\SellerGrade;
use App\Models\SellerShopwindow;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Article\ArticleService;
use App\Services\Category\CategoryService;
use App\Services\Common\CommonManageService;
use App\Services\GetAjax\GetAjaxContentManageService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsManageService;

class GetAjaxContentController extends InitController
{
    protected $categoryService;
    protected $dscRepository;
    protected $goodsManageService;
    protected $commonManageService;
    protected $goodsCommonService;
    protected $baseRepository;
    protected $getAjaxContentManageService;
    protected $articleService;

    public function __construct(
        CategoryService $categoryService,
        DscRepository $dscRepository,
        GoodsManageService $goodsManageService,
        CommonManageService $commonManageService,
        GoodsCommonService $goodsCommonService,
        BaseRepository $baseRepository,
        GetAjaxContentManageService $getAjaxContentManageService,
        ArticleService $articleService
    )
    {
        $this->categoryService = $categoryService;
        $this->dscRepository = $dscRepository;
        $this->goodsManageService = $goodsManageService;
        $this->commonManageService = $commonManageService;
        $this->goodsCommonService = $goodsCommonService;
        $this->baseRepository = $baseRepository;
        $this->getAjaxContentManageService = $getAjaxContentManageService;
        $this->articleService = $articleService;
    }

    public function index()
    {
        load_helper('goods', 'admin');
        load_helper('visual');


        $admin_id = get_admin_id();

        $adminru = get_admin_ru_id();
        $_REQUEST['act'] = trim($_REQUEST['act']);

        $data = ['error' => 0, 'message' => '', 'content' => ''];

        /* ------------------------------------------------------ */
        //-- 获取下级分类列表
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == 'get_select_category') {
            $cat_id = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
            $child_cat_id = empty($_REQUEST['child_cat_id']) ? 0 : intval($_REQUEST['child_cat_id']);
            $cat_level = empty($_REQUEST['cat_level']) ? 0 : intval($_REQUEST['cat_level']);
            $select_jsId = empty($_REQUEST['select_jsId']) ? 'cat_parent_id' : trim($_REQUEST['select_jsId']);
            $type = empty($_REQUEST['type']) ? 0 : intval($_REQUEST['type']);

            $content = insert_select_category($cat_id, $child_cat_id, $cat_level, $select_jsId, $type);
            if (!empty($content)) {
                $data['error'] = 1;
                $data['content'] = $content;
            }

            return response()->json($data);
        }

        /* ------------------------------------------------------ */
        //-- 获取筛选分类列表
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'filter_category') {
            $cat_id = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
            $cat_type_show = empty($_REQUEST['cat_type_show']) ? 0 : intval($_REQUEST['cat_type_show']);
            $user_id = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);
            $lib = isset($_REQUEST['lib']) && !empty($_REQUEST['lib']) ? addslashes($_REQUEST['lib']) : 0;

            $result = ['error' => 0, 'message' => '', 'content' => ''];
            if ($lib && $lib == 'lib') {
                $table = 'goods_lib_cat';
            } else {
                $table = isset($_REQUEST['table']) && !empty($_REQUEST['table']) ? trim($_REQUEST['table']) : 'category';
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
            $cate_title = '';
            $cate_keywords = '';
            $cate_description = '';
            if ($filter_category_navigation) {
                foreach ($filter_category_navigation as $key => $val) {
                    if ($key == 0) {
                        $cat_nav .= $val['cat_name'];
                    } elseif ($key > 0) {
                        $cat_nav .= " > " . $val['cat_name'];
                    }
                    $cate_title = isset($val['cate_title']) ? $val['cate_title'] : '';
                    $cate_keywords = isset($val['cate_keywords']) ? $val['cate_keywords'] : '';
                    $cate_description = isset($val['cate_description']) ? $val['cate_description'] : '';
                }
            } else {
                $cat_nav = $GLOBALS['_LANG']['select_cat'];
                $cat_level = 1;
            }

            $result['cat_nav'] = $cat_nav;

            $result['cate_title'] = $cate_title;
            $result['cate_keywords'] = $cate_keywords;
            $result['cate_description'] = $cate_description;

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
                if ($user_id) {
                    $seller_shop_cat = seller_shop_cat($user_id);
                } else {
                    $seller_shop_cat = [];
                }

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
            } else {
                $this->smarty->assign('cat_type_show', $cat_type_show);
            }

            $this->smarty->assign('filter_category_level', $cat_level);
            $this->smarty->assign('lib', $lib);
            $this->smarty->assign('table', $table);
            $this->smarty->assign('filter_category_navigation', $filter_category_navigation);
            $this->smarty->assign('filter_category_list', $filter_category_list);

            if ($cat_type_show) {
                $result['content'] = $this->smarty->fetch('library/filter_category_seller.lbi');
            } else {
                $result['content'] = $this->smarty->fetch('library/filter_category.lbi');
            }

            $result['cat_level'] = $cat_level;

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 获取品牌列表
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'search_brand_list') {
            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $this->smarty->assign('filter_brand_list', search_brand_list($goods_id));
            $result['content'] = $this->smarty->fetch('library/search_brand_list.lbi');
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 获取筛选商品列表
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'filter_list') {

            $search_type = empty($_REQUEST['search_type']) ? '' : trim($_REQUEST['search_type']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            //筛选商品
            if ($search_type == "goods") {
                $cat_id = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
                $brand_id = empty($_REQUEST['brand_id']) ? 0 : intval($_REQUEST['brand_id']);
                $keyword = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
                $ru_id = empty($_REQUEST['ru_id']) ? 0 : trim($_REQUEST['ru_id']);

                $limit['start'] = empty($_REQUEST['limit_start']) ? 0 : trim($_REQUEST['limit_start']);
                $limit['number'] = empty($_REQUEST['limit_number']) ? 50 : trim($_REQUEST['limit_number']);

                $filters['cat_id'] = $cat_id;
                $filters['brand_id'] = $brand_id;
                $filters['keyword'] = urlencode($keyword);
                $filters['sel_mode'] = 0;
                $filters['brand_keyword'] = "";
                $filters['exclude'] = "";
                $filters['ru_id'] = $ru_id;

                $filters = dsc_decode(urldecode(json_encode($filters)));

                $arr = get_goods_list($filters, $limit);

                $opt = [];

                foreach ($arr as $key => $val) {
                    $opt[] = ['value' => $val['goods_id'],
                        'text' => $val['goods_name'] . "--" . price_format($val['shop_price']),
                        'data' => $val['shop_price']];
                }
                $filter_list = $opt;
            } //筛选文章
            elseif ($search_type == "article") {
                $title = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);

                $res = Article::select('article_id', 'title')->where('cat_id', '>', 0);
                if (!empty($title)) {
                    $res = $res->where('title', 'LIKE', '%' . mysql_like_quote($title) . '%');
                }
                $res = $res->orderBy('article_id', 'DESC')->limit(50);
                $res = $this->baseRepository->getToArrayGet($res);
                $arr = [];

                foreach ($res as $row) {
                    $arr[] = ['value' => $row['article_id'], 'text' => $row['title'], 'data' => ''];
                }
                $filter_list = $arr;
            } //筛选地区
            elseif ($search_type == "area") {
                $ra_id = empty($_REQUEST['keyword']) ? 0 : intval($_REQUEST['keyword']);

                $arr = $this->commonManageService->getAreaRegionInfoList($ra_id);
                $opt = [];

                foreach ($arr as $key => $val) {
                    $opt[] = [
                        'value' => $val['region_id'] ?? 0,
                        'text' => $val['region_name'] ?? '',
                        'data' => 0
                    ];
                }
                $filter_list = $opt;
            } //筛选商品类型
            elseif ($search_type == "goods_type") {
                $cat_id = empty($_REQUEST['keyword']) ? 0 : intval($_REQUEST['keyword']);

                $goods_fields = my_array_merge($GLOBALS['_LANG']['custom'], $this->getAjaxContentManageService->getAttributes($cat_id));
                $opt = [];

                foreach ($goods_fields as $key => $val) {
                    $opt[] = ['value' => $key,
                        'text' => $val,
                        'data' => 0];
                }
                $filter_list = $opt;
            }

            /* ------------------------------------------------------ */
            //-- 搜索内容
            /* ------------------------------------------------------ */
            elseif ($search_type == 'get_content') {
                $cat_id = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
                $brand_id = empty($_REQUEST['brand_id']) ? 0 : intval($_REQUEST['brand_id']);
                $keyword = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
                $FloorBrand = empty($_REQUEST['FloorBrand']) ? 0 : intval($_REQUEST['FloorBrand']);
                $brand_ids = empty($_REQUEST['brand_ids']) ? '' : trim($_REQUEST['brand_ids']);
                $is_selected = empty($_REQUEST['is_selected']) ? 0 : intval($_REQUEST['is_selected']);
                $filters['cat_id'] = $cat_id;
                $filters['brand_id'] = $brand_id;
                $filters['keyword'] = $keyword;
                $filters = dsc_decode(json_encode($filters));

                $arr = $this->getBrandList($filters);
                $opt = [];
                if ($FloorBrand == 1) {
                    if (!empty($arr)) {
                        $brand_ids = explode(',', $brand_ids);
                        foreach ($arr as $key => $val) {
                            $val['brand_logo'] = DATA_DIR . '/brandlogo/' . $val['brand_logo'];
                            $arr[$key]['brand_logo'] = get_image_path($val['brand_logo']);

                            if (!empty($brand_ids) && in_array($val['brand_id'], $brand_ids)) {
                                $arr[$key]['selected'] = 1;
                            } elseif ($is_selected == 1) {
                                unset($arr[$key]);
                            }
                        }
                    }
                    $this->smarty->assign("recommend_brands", $arr);
                    $this->smarty->assign('temp', 'brand_query');

                    $result['FloorBrand'] = $this->smarty->fetch('library/dialog.lbi');
                } else {
                    foreach ($arr as $key => $val) {
                        $opt[] = [
                            'value' => $val['brand_id'],
                            'text' => $val['brand_name'],
                            'data' => $val['brand_id']
                        ];
                    }
                }
                $filter_list = $opt;
            }

            $this->smarty->assign('filter_list', $filter_list);
            $result['content'] = $this->smarty->fetch('library/move_left.lbi');
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 增加一个楼层内        容
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_floor_content') {
            $fittings = empty($_REQUEST['value']) ? [] : explode(",", $_REQUEST['value']);
            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $value = empty($_REQUEST['group']) ? '' : trim($_REQUEST['group']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $filename = '';
            $cat_id = 0;
            $region = '';
            if ($value) {
                $value = explode("|", $value);
                $filename = $value[0];
                $cat_id = $value[1];
                $region = $value[2];
            }
            $curr_template = $GLOBALS['_CFG']['template'];

            $cat_name = Category::where('cat_id', $cat_id)->value('cat_name');
            $cat_name = $cat_name ? $cat_name : '';

            foreach ($fittings as $val) {
                $brand_name = Brand::where('brand_id', $val)->value('brand_name');
                $brand_name = $brand_name ? $brand_name : '';

                $res = FloorContent::where('brand_id', $val)
                    ->where('filename', $filename)
                    ->where('id', $cat_id)
                    ->where('region', $region)
                    ->where('theme', $curr_template);
                $res = $res->count();
                if ($res < 1) {
                    $data = [
                        'filename' => $filename,
                        'region' => $region,
                        'id' => $cat_id,
                        'id_name' => $cat_name,
                        'brand_id' => $val,
                        'brand_name' => $brand_name,
                        'theme' => $curr_template
                    ];
                    FloorContent::insert($data);
                }
            }

            $arr = $this->getAjaxContentManageService->getGloorContent($curr_template, $filename, $cat_id, $region);
            $options = [];

            foreach ($arr as $val) {
                $options[] = [
                    'value' => $val['fb_id'],
                    'text' => '[' . $val['id_name'] . ']' . $val['brand_name'],
                    'data' => $val['id']
                ];
            }

            $this->smarty->assign('filter_result', $options);
            $result['content'] = $this->smarty->fetch('library/move_right.lbi');

            clear_cache_files();
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 删除一个楼层内        容
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_floor_content') {
            $fb_id = empty($_REQUEST['value']) ? [] : explode(",", $_REQUEST['value']);
            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $curr_template = $GLOBALS['_CFG']['template'];
            $options = [];

            if (count($fb_id) > 0) {
                $fb_id = $this->baseRepository->getExplode($fb_id);
                $res = FloorContent::whereIn('fb_id', $fb_id);
                $floor_info = $this->baseRepository->getToArrayFirst($res);

                FloorContent::whereIn('fb_id', $fb_id)->delete();

                $arr = $this->getAjaxContentManageService->getGloorContent($curr_template, $floor_info['filename'], $floor_info['id'], $floor_info['region']);

                foreach ($arr as $val) {
                    $options[] = [
                        'value' => $val['fb_id'],
                        'text' => '[' . $val['id_name'] . ']' . $val['brand_name'],
                        'data' => $val['id']
                    ];
                }
            }

            $this->smarty->assign('filter_result', $options);
            $result['content'] = $this->smarty->fetch('library/move_right.lbi');

            clear_cache_files();
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 把商品加�        ��        �联
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_link_goods') {
            $linked_array = empty($_REQUEST['value']) ? [] : explode(",", $_REQUEST['value']);
            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $is_double = empty($_REQUEST['is_single']) ? 1 : 0;
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            foreach ($linked_array as $val) {
                if ($is_double) {
                    /* 双向关联 */
                    $data = [
                        'goods_id' => $val,
                        'link_goods_id' => $goods_id,
                        'is_double' => $is_double,
                        'admin_id' => session('admin_id')
                    ];
                    LinkGoods::insert($data);
                }
                $data = [
                    'goods_id' => $goods_id,
                    'link_goods_id' => $val,
                    'is_double' => $is_double,
                    'admin_id' => session('admin_id')
                ];
                LinkGoods::insert($data);
            }

            $linked_goods = get_linked_goods($goods_id);
            $options = [];

            foreach ($linked_goods as $val) {
                $options[] = ['value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => ''];
            }

            $this->smarty->assign('filter_result', $options);
            $result['content'] = $this->smarty->fetch('library/move_right.lbi');

            clear_cache_files();
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 删除�        �联商品
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_link_goods') {
            $drop_goods = empty($_REQUEST['value']) ? [] : explode(",", $_REQUEST['value']);
            $drop_goods_ids = db_create_in($drop_goods);
            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $is_signle = empty($_REQUEST['is_single']) ? 0 : 1;
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $res = LinkGoods::whereRaw(1);
            $drop_goods = $this->baseRepository->getExplode($drop_goods);

            if ($goods_id == 0) {
                $res = $res->where('admin_id', session('admin_id'));
            }

            if (!$is_signle) {
                $res = $res->where('link_goods_id', $goods_id)->whereIn('goods_id', $drop_goods);
                $res->delete();
            } else {
                $res = $res->where('link_goods_id', $goods_id)->whereIn('goods_id', $drop_goods);
                $data = ['is_double' => 0];
                $res->update($data);
            }

            $res = LinkGoods::where('goods_id', $goods_id)->whereIn('link_goods_id', $drop_goods);
            if ($goods_id == 0) {
                $res = $res->where('admin_id', session('admin_id'));
            }
            $res->delete();

            $linked_goods = get_linked_goods($goods_id);
            $options = [];

            foreach ($linked_goods as $val) {
                $options[] = [
                    'value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => ''];
            }

            $this->smarty->assign('filter_result', $options);
            $result['content'] = $this->smarty->fetch('library/move_right.lbi');

            clear_cache_files();
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 将文章商品添加�        �联
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_link_artic_goods') {
            $add_ids = empty($_REQUEST['value']) ? [] : explode(",", $_REQUEST['value']);
            $article_id = empty($_REQUEST['article_id']) ? 0 : intval($_REQUEST['article_id']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];


            if ($article_id == 0) {
                $article_id = Article::selectRaw('MAX(article_id)+1 AS article_id')->value('article_id');
                $article_id = $article_id ? $article_id : 1;
            }

            foreach ($add_ids as $key => $val) {
                $data = ['goods_id' => $val, 'article_id' => $article_id];
                GoodsArticle::insert($data);
            }

            /* 重新载入 */
            $arr = $this->getAjaxContentManageService->getArticleGoods($article_id);
            $opt = [];

            foreach ($arr as $key => $val) {
                $val['goods_name'] = '';
                if (isset($val['get_goods']) && !empty($val['get_goods'])) {
                    $val['goods_name'] = $val['get_goods']['goods_name'];
                }
                $opt[] = [
                    'value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => ''
                ];
            }

            $this->smarty->assign('filter_result', $opt);
            $result['content'] = $this->smarty->fetch('library/move_right.lbi');
            clear_cache_files();
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 将文章商品删除�        �联
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_link_artic_goods') {
            $drop_goods = empty($_REQUEST['value']) ? [] : explode(",", $_REQUEST['value']);
            $article_id = empty($_REQUEST['article_id']) ? 0 : intval($_REQUEST['article_id']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];


            if ($article_id == 0) {
                $article_id = Article::selectRaw('MAX(article_id)+1 AS article_id')->value('article_id');
                $article_id = $article_id ? $article_id : 1;
            }

            $drop_goods = $this->baseRepository->getExplode($drop_goods);
            GoodsArticle::where('article_id', $article_id)->whereIn('goods_id', $drop_goods)->delete();


            /* 重新载入 */
            $arr = $this->getAjaxContentManageService->getArticleGoods($article_id);
            $opt = [];

            foreach ($arr as $key => $val) {
                $val['goods_name'] = '';
                if (isset($val['get_goods']) && !empty($val['get_goods'])) {
                    $val['goods_name'] = $val['get_goods']['goods_name'];
                }
                $opt[] = [
                    'value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => ''
                ];
            }

            $this->smarty->assign('filter_result', $opt);
            $result['content'] = $this->smarty->fetch('library/move_right.lbi');
            clear_cache_files();
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 增加一个�        �件
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_group_goods') {
            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $goods_ids = empty($_REQUEST['goods_ids']) ? 0 : trim($_REQUEST['goods_ids']);
            $price = empty($_REQUEST['price2']) ? 0 : floatval($_REQUEST['price2']);
            $group_id = empty($_REQUEST['group2']) ? 1 : intval($_REQUEST['group2']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $groupCount = GroupGoods::where('parent_id', $goods_id)->where('group_id', $group_id)->where('admin_id', session('admin_id'))->count();

            $message = "";
            if ($groupCount < 1000) {
                if ($goods_ids) {
                    $goods_ids = explode(',', $goods_ids);
                }

                if (!empty($goods_ids)) {
                    foreach ($goods_ids as $key => $val) {
                        $res = GroupGoods::where('parent_id', $goods_id)->where('goods_id', $val)->count();
                        if ($res < 1) {
                            $price_goods = 0;
                            if ($price == 0) {
                                $price_goods = Goods::where('goods_id', $val)->value('shop_price');
                                $price_goods = $price_goods ? $price_goods : 0;
                            } else {
                                $price_goods = $price;
                            }
                            $data = [
                                'parent_id' => $goods_id,
                                'goods_id' => $val,
                                'goods_price' => $price_goods,
                                'admin_id' => session('admin_id'),
                                'group_id' => $group_id,
                            ];
                            GroupGoods::insert($data);
                        }
                    }
                }
                $error = 0;
            } else {
                $error = 1;
                $message = $GLOBALS['_LANG']['add_group_goods_notic'];
            }
            //获取全部的配件
            $arr = get_group_goods($goods_id);
            $this->smarty->assign('group_goods_list', $arr);
            //获取人气组合 by kong
            if ($GLOBALS['_CFG']['group_goods']) {
                $group_goods_arr = explode(',', $GLOBALS['_CFG']['group_goods']);
                $arr = [];
                foreach ($group_goods_arr as $k => $v) {
                    $arr[$k + 1] = $v;
                }
                $this->smarty->assign('group_goods_arr', $arr);
            }
            $result['content'] = $this->smarty->fetch('library/group_goods_list.lbi');
            $result['error'] = $error;
            $result['message'] = $message;
            clear_cache_files();
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 添加秒杀商品
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_seckill_goods') {

            $this->dscRepository->helpersLang(['seckill'], 'admin');

            $goods_ids = empty($_REQUEST['goods_ids']) ? '' : trim($_REQUEST['goods_ids']);
            $sec_id = empty($_REQUEST['sec_id']) ? 0 : intval($_REQUEST['sec_id']);
            $tb_id = empty($_REQUEST['tb_id']) ? 0 : intval($_REQUEST['tb_id']);
            $result = ['error' => 0, 'message' => '', 'content' => '', 'goods_ids' => ''];
            if ($goods_ids) {
                $goods_ids_arr = explode(',', $goods_ids);
                foreach ($goods_ids_arr as $val) {
                    $res = SeckillGoods::where('goods_id', $val)->where('sec_id', $sec_id)->where('tb_id', $tb_id)->count();
                    if ($res < 1) {
                        $data = [
                            'sec_id' => $sec_id,
                            'tb_id' => $tb_id,
                            'goods_id' => $val
                        ];
                        SeckillGoods::insert($data);
                    }
                }
                $list = get_add_seckill_goods($sec_id, $tb_id);

                //获取秒杀商品ID列表
                $res = SeckillGoods::selectRaw('GROUP_CONCAT(goods_id) as goods_id')
                    ->where('sec_id', $sec_id)
                    ->where('tb_id', $tb_id);
                $result['goods_ids'] = $res->value('goods_id');
                $result['goods_ids'] = $result['goods_ids'] ? $result['goods_ids'] : '';

                $this->smarty->assign('seckill_goods', $list['seckill_goods']);
                $this->smarty->assign('filter', $list['filter']);
                $this->smarty->assign('record_count', $list['record_count']);
                $this->smarty->assign('page_count', $list['page_count']);
                $this->smarty->assign('lang', $GLOBALS['_LANG']);
                $result['content'] = $this->smarty->fetch('seckill_set_goods_info.dwt');
                return response()->json($result);
            }
        }

        /* ------------------------------------------------------ */
        //-- 添加�        �联文章
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_goods_article') {
            $articles = empty($_REQUEST['value']) ? [] : explode(",", $_REQUEST['value']);
            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            foreach ($articles as $val) {
                $data = [
                    'goods_id' => $goods_id,
                    'article_id' => $val,
                    'admin_id' => session('admin_id')
                ];
                GoodsArticle::insert($data);
            }

            $arr = get_goods_articles($goods_id);
            $opt = [];

            foreach ($arr as $val) {
                $opt[] = ['value' => $val['article_id'],
                    'text' => $val['title'],
                    'data' => ''];
            }

            $this->smarty->assign('filter_result', $opt);
            $result['content'] = $this->smarty->fetch('library/move_right.lbi');

            clear_cache_files();
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 删除�        �联文章
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_goods_article') {
            $articles = empty($_REQUEST['value']) ? [] : explode(",", $_REQUEST['value']);
            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $articles = $this->baseRepository->getExplode($articles);
            GoodsArticle::whereIn('article_id', $articles)->where('goods_id', $goods_id)->delete();

            $arr = get_goods_articles($goods_id);
            $opt = [];

            foreach ($arr as $val) {
                $opt[] = ['value' => $val['article_id'],
                    'text' => $val['title'],
                    'data' => ''];
            }

            $this->smarty->assign('filter_result', $opt);
            $result['content'] = $this->smarty->fetch('library/move_right.lbi');

            clear_cache_files();
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 增加一个�        �联地区
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_area_goods') {
            $fittings = empty($_REQUEST['value']) ? [] : explode(",", $_REQUEST['value']);
            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $ru_id = Goods::where('goods_id', $goods_id)->value('user_id');
            $ru_id = $ru_id ? $ru_id : 0;

            foreach ($fittings as $val) {
                $data = [
                    'goods_id' => $goods_id,
                    'region_id' => $val,
                    'ru_id' => $ru_id
                ];
                LinkAreaGoods::insert($data);
            }

            $arr = get_area_goods($goods_id);
            $opt = [];

            foreach ($arr as $val) {
                $opt[] = ['value' => $val['region_id'],
                    'text' => $val['region_name'],
                    'data' => 0];
            }

            $this->smarty->assign('filter_result', $opt);
            $result['content'] = $this->smarty->fetch('library/move_right.lbi');

            clear_cache_files();
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 删除一个�        �联地区
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_area_goods') {
            $drop_goods = empty($_REQUEST['value']) ? [] : explode(",", $_REQUEST['value']);
            $drop_goods_ids = db_create_in($drop_goods);
            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $drop_goods = $this->baseRepository->getExplode($drop_goods);
            $res = LinkAreaGoods::whereIn('region_id', $drop_goods)->where('goods_id', $goods_id);
            if ($goods_id == 0) {
                $adminru = get_admin_ru_id();
                $ru_id = $adminru['ru_id'];

                $res = $res->where('ru_id', $ru_id);
            }
            $res->delete();

            $arr = get_area_goods($goods_id);
            $opt = [];

            foreach ($arr as $val) {
                $opt[] = ['value' => $val['region_id'],
                    'text' => $val['region_name'],
                    'data' => 0];
            }

            $this->smarty->assign('filter_result', $opt);
            $result['content'] = $this->smarty->fetch('library/move_right.lbi');

            clear_cache_files();
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 把商品加�        �商品柜
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_win_goods') {

            $linked_array = empty($_REQUEST['value']) ? [] : explode(",", $_REQUEST['value']);
            $id = empty($_REQUEST['win_id']) ? 0 : intval($_REQUEST['win_id']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $win_goods = SellerShopwindow::where('id', $id)->value('win_goods');
            $win_goods = $win_goods ? $win_goods : '';

            foreach ($linked_array as $val) {
                if (!strstr($win_goods, $val) && !empty($val)) {
                    $win_goods .= !empty($win_goods) ? ',' . $val : $val;
                }
            }

            $data = ['win_goods' => $win_goods];
            SellerShopwindow::where('id', $id)->update($data);
            $win_goods = $this->getAjaxContentManageService->getWinGoods($id);

            $this->smarty->assign('filter_result', $win_goods);
            $result['content'] = $this->smarty->fetch('library/move_right.lbi');

            clear_cache_files();
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 把商品移除商品柜
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_win_goods') {

            $drop_goods = empty($_REQUEST['value']) ? [] : explode(",", $_REQUEST['value']);
            $id = empty($_REQUEST['win_id']) ? 0 : intval($_REQUEST['win_id']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $win_goods = SellerShopwindow::where('id', $id)->value('win_goods');
            $win_goods = $win_goods ? $win_goods : '';
            $win_goods_arr = $this->baseRepository->getExplode($win_goods);

            foreach ($drop_goods as $val) {
                if (strstr($win_goods, $val) && !empty($val)) {
                    $key = array_search($val, $win_goods_arr);
                    if ($key !== false) {
                        array_splice($win_goods_arr, $key, 1);
                    }
                }
            }
            $new_win_goods = '';
            foreach ($win_goods_arr as $val) {
                if (!strstr($new_win_goods, $val) && !empty($val)) {
                    $new_win_goods .= !empty($new_win_goods) ? ',' . $val : $val;
                }
            }

            $data = ['win_goods' => $new_win_goods];
            SellerShopwindow::where('id', $id)->update($data);
            $win_goods = $this->getAjaxContentManageService->getWinGoods($id);

            $this->smarty->assign('filter_result', $win_goods);
            $result['content'] = $this->smarty->fetch('library/move_right.lbi');

            clear_cache_files();
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 添加统一详情        商品
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_link_desc') {

            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $linked_array = empty($_REQUEST['value']) ? [] : explode(",", $_REQUEST['value']);
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

            get_add_edit_link_desc($linked_array, 0, $id);

            $ru_id = $adminru['ru_id'];

            if ($id) {
                $ru_id = LinkGoodsDesc::where('id', $id)->value('ru_id');
                $ru_id = $ru_id ? $ru_id : 0;
            }

            $linked_goods = get_linked_goods_desc(0, $ru_id);

            $options = [];
            foreach ($linked_goods as $val) {
                $options[] = ['value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => ''];
            }

            $this->smarty->assign('filter_result', $options);
            $content = $this->smarty->fetch('library/move_right.lbi');

            clear_cache_files();
            return make_json_result($content);
        }

        /* ------------------------------------------------------ */
        //-- 删除统一详情        商品
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_link_desc') {

            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $drop_goods = empty($_REQUEST['value']) ? [] : explode(",", $_REQUEST['value']);
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

            get_add_edit_link_desc($drop_goods, 1, $id);

            $ru_id = $adminru['ru_id'];

            if ($id) {
                $ru_id = LinkGoodsDesc::where('id', $id)->value('ru_id');
                $ru_id = $ru_id ? $ru_id : 0;
            }

            $linked_goods = get_linked_goods_desc(0, $ru_id);

            $options = [];
            foreach ($linked_goods as $val) {
                $options[] = [
                    'value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => ''];
            }

            if (empty($linked_goods)) {
                LinkDescTemporary::where('ru_id', $adminru['ru_id'])->delete();
            }

            $this->smarty->assign('filter_result', $options);
            $content = $this->smarty->fetch('library/move_right.lbi');

            clear_cache_files();
            return make_json_result($content);
        }

        /* ------------------------------------------------------ */
        //-- 上传图片
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'upload_img') {
            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);
            load_helper('goods', 'admin');

            $bucket_info = $this->dscRepository->getBucketInfo();

            $act_type = empty($_REQUEST['type']) ? '' : trim($_REQUEST['type']);
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

            $is_lib = !empty($_REQUEST['is_lib']) ? intval($_REQUEST['is_lib']) : 0;
            /* 商品库和普通商品相册 */
            $res = GoodsGallery::whereRaw(1);
            if ($is_lib) {
                $res = GoodsLibGallery::whereRaw(1);
            }

            $result = ['error' => 0, 'pic' => '', 'name' => ''];
            $typeArr = ["jpg", "png", "gif", "jpeg"]; //允许上传文件格式

            if (isset($_POST)) {
                $name = $_FILES['file']['name'];
                $size = $_FILES['file']['size'];
                $name_tmp = $_FILES['file']['tmp_name'];
                if (empty($name)) {
                    $result['error'] = $GLOBALS['_LANG']['not_select_img'];
                }
                $type = strtolower(substr(strrchr($name, '.'), 1)); //获取文件类型
                if (!in_array($type, $typeArr)) {
                    $result['error'] = $GLOBALS['_LANG']['cat_prompt_file_type'];
                }
            }

            $upload_status = 0;

            if ($act_type == 'goods_img') {
                /* 开始处理 start */
                $_FILES['goods_img'] = $_FILES['file'];
                $proc_thumb = (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0) ? false : true;
                $_POST['auto_thumb'] = 1; //自动生成缩略图
                $_REQUEST['goods_id'] = $id;
                $goods_id = $id;
                /* 开始处理 end */

                /* 处理商品图片 */
                $goods_img = '';  // 初始化商品图片
                $goods_thumb = '';  // 初始化商品缩略图
                $original_img = '';  // 初始化原始图片
                $old_original_img = '';  // 初始化原始图片旧图

                // 如果上传了商品图片，相应处理
                if ($_FILES['goods_img']['tmp_name'] != '' && $_FILES['goods_img']['tmp_name'] != 'none') {
                    if (empty($is_url_goods_img)) {
                        $original_img = $image->upload_image($_FILES['goods_img'], ['type' => 1]); // 原始图片
                        $original_img = storage_public($original_img);
                    }

                    $goods_img = $original_img;   // 商品图片

                    /* 复制一份相册图片 */
                    /* 添加判断是否自动生成相册图片 */
                    if ($GLOBALS['_CFG']['auto_generate_gallery']) {
                        $img = $original_img;   // 相册图片
                        $pos = strpos(basename($img), '.');
                        $newname = dirname($img) . '/' . $image->random_filename() . substr(basename($img), $pos);
                        copy($img, $newname);
                        $img = $newname;

                        $gallery_img = $img;
                        $gallery_thumb = $img;
                    }

                    // 如果系统支持GD，缩放商品图片，且给商品图片和相册图片加水印
                    if ($proc_thumb && $image->gd_version() > 0 && $image->check_img_function($_FILES['goods_img']['type']) || $is_url_goods_img) {
                        if (empty($is_url_goods_img)) {
                            // 如果设置大小不为0，缩放图片
                            if ($GLOBALS['_CFG']['image_width'] != 0 || $GLOBALS['_CFG']['image_height'] != 0) {
                                $goods_img = $image->make_thumb(['img' => $goods_img, 'type' => 1], $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height']);//问题出在这
                            }

                            /* 添加判断是否自动生成相册图片 */
                            if ($GLOBALS['_CFG']['auto_generate_gallery']) {
                                $newname = dirname($img) . '/' . $image->random_filename() . substr(basename($img), $pos);
                                copy($img, $newname);
                                $gallery_img = $newname;
                            }

                            // 加水印
                            if (intval($GLOBALS['_CFG']['watermark_place']) > 0 && !empty($GLOBALS['_CFG']['watermark'])) {
                                if ($image->add_watermark($goods_img, '', $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']) === false) {
                                    return sys_msg($image->error_msg(), 1, [], false);
                                }
                                /* 添加判断是否自动生成相册图片 */
                                if ($GLOBALS['_CFG']['auto_generate_gallery']) {
                                    if ($image->add_watermark($gallery_img, '', $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']) === false) {
                                        return sys_msg($image->error_msg(), 1, [], false);
                                    }
                                }
                            }
                        }

                        // 相册缩略图
                        /* 添加判断是否自动生成相册图片 */
                        if ($GLOBALS['_CFG']['auto_generate_gallery']) {
                            if ($GLOBALS['_CFG']['thumb_width'] != 0 || $GLOBALS['_CFG']['thumb_height'] != 0) {
                                $gallery_thumb = $image->make_thumb(['img' => $img, 'type' => 1], $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height']);
                            }
                        }
                    }
                }


                // 是否上传商品缩略图
                if (isset($_FILES['goods_thumb']) && $_FILES['goods_thumb']['tmp_name'] != '' &&
                    isset($_FILES['goods_thumb']['tmp_name']) && $_FILES['goods_thumb']['tmp_name'] != 'none') {
                    // 上传了，直接使用，原始大小
                    $goods_thumb = $image->upload_image($_FILES['goods_thumb'], ['type' => 1]);
                } else {
                    // 未上传，如果自动选择生成，且上传了商品图片，生成所略图
                    if ($proc_thumb && isset($_POST['auto_thumb']) && !empty($original_img)) {
                        // 如果设置缩略图大小不为0，生成缩略图
                        if ($GLOBALS['_CFG']['thumb_width'] != 0 || $GLOBALS['_CFG']['thumb_height'] != 0) {
                            $goods_thumb = $image->make_thumb(['img' => $original_img, 'type' => 1], $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height']);
                        } else {
                            $goods_thumb = $original_img;
                        }
                    }
                }

                /* 重新格式化图片名称 */
                $original_img = $this->goodsManageService->reformatImageName('goods', $goods_id, $original_img, 'source');
                $goods_img = $this->goodsManageService->reformatImageName('goods', $goods_id, $goods_img, 'goods');
                $goods_thumb = $this->goodsManageService->reformatImageName('goods_thumb', $goods_id, $goods_thumb, 'thumb');
                //将数据保存返回 by wu
                $result['data'] = [
                    'original_img' => $original_img,
                    'goods_img' => $goods_img,
                    'goods_thumb' => $goods_thumb
                ];

                if (empty($goods_id)) {
                    session()->put('goods.' . $admin_id . "." . $goods_id, $result['data']);
                } else {
                    get_del_edit_goods_img($goods_id, $result);
                    $this->db->autoExecute($this->dsc->table('goods'), $result['data'], 'UPDATE', "goods_id = '$goods_id'");
                }

                $this->dscRepository->getOssAddFile($result['data']);

                $gallery_images = [];
                /* 如果有图片，把商品图片加入图片相册 */
                if ($img) {
                    /* 重新格式化图片名称 */
                    if (empty($is_url_goods_img)) {
                        $img = $this->goodsManageService->reformatImageName('gallery', $goods_id, $img, 'source');
                        $gallery_img = $this->goodsManageService->reformatImageName('gallery', $goods_id, $gallery_img, 'goods');
                    } else {
                        $img = $url_goods_img;
                        $gallery_img = $url_goods_img;
                    }

                    $gallery_thumb = $this->goodsManageService->reformatImageName('gallery_thumb', $goods_id, $gallery_thumb, 'thumb');

                    $img_desc = $res->selectRaw('MAX(img_desc)+1 as img_desc')->where('goods_id', $goods_id)->value('img_desc');
                    $img_desc = $img_desc ? $img_desc : 1;
                    $gallery_images = [$gallery_img, $gallery_thumb, $img];

                    $data = [
                        'goods_id' => $goods_id,
                        'img_url' => $gallery_img,
                        'img_desc' => $img_desc,
                        'thumb_url' => $gallery_thumb,
                        'img_original' => $img
                    ];
                    $thumb_img_id[] = $res->insertGetId($data);

                    $this->dscRepository->getOssAddFile([$gallery_img, $gallery_thumb, $img]);
                    if (!empty(session('thumb_img_id' . session('admin_id')))) {
                        $thumb_img_id = array_merge($thumb_img_id, session('thumb_img_id' . session('admin_id')));
                    }

                    session(['thumb_img_id' . session('admin_id') => $thumb_img_id]);

                    $result['img_desc'] = $img_desc;
                }

                /* 删除本地商品图片 start */
                if ($GLOBALS['_CFG']['open_oss'] == 1 && $bucket_info['is_delimg'] == 1) {
                    if ($result['data']) {
                        $goods_images = [$result['data']['original_img'], $result['data']['goods_img'], $result['data']['goods_thumb']];
                        dsc_unlink($goods_images);
                    }

                    if ($gallery_images) {
                        dsc_unlink($gallery_images);
                    }
                }
                /* 删除本地商品图片 start */

                /* 结束处理 start */
                $pic_name = "";

                $goods_img = get_image_path($goods_img);

                $pic_url = $goods_img;

                $upload_status = 1;
                /* 结束处理 end */
            } elseif ($act_type == 'gallery_img') {
                /* 开始处理 start */
                $_FILES['img_url'] = [
                    'name' => [$_FILES['file']['name']],
                    'type' => [$_FILES['file']['type']],
                    'tmp_name' => [$_FILES['file']['tmp_name']],
                    'error' => [$_FILES['file']['error']],
                    'size' => [$_FILES['file']['size']]
                ];
                $_REQUEST['goods_id_img'] = $id;
                $_REQUEST['img_desc'] = [['']];
                $_REQUEST['img_file'] = [['']];
                /* 开始处理 end */

                $goods_id = isset($_REQUEST['goods_id_img']) && !empty($_REQUEST['goods_id_img']) ? intval($_REQUEST['goods_id_img']) : 0;
                $img_desc = isset($_REQUEST['img_desc']) && !empty($_REQUEST['img_desc']) ? $_REQUEST['img_desc'] : [];
                $img_file = isset($_REQUEST['img_file']) && !empty($_REQUEST['img_file']) ? $_REQUEST['img_file'] : [];
                $php_maxsize = ini_get('upload_max_filesize');
                $htm_maxsize = '2M';
                if ($_FILES['img_url']) {
                    foreach ($_FILES['img_url']['error'] as $key => $value) {
                        if ($value == 0) {
                            if (!$image->check_img_type($_FILES['img_url']['type'][$key])) {
                                $result['error'] = '1';
                                $result['massege'] = sprintf($GLOBALS['_LANG']['invalid_img_url'], $key + 1);
                            } else {
                                $goods_pre = 1;
                            }
                        } elseif ($value == 1) {
                            $result['error'] = '1';
                            $result['massege'] = sprintf($GLOBALS['_LANG']['img_url_too_big'], $key + 1, $php_maxsize);
                        } elseif ($_FILES['img_url']['error'] == 2) {
                            $result['error'] = '1';
                            $result['massege'] = sprintf($GLOBALS['_LANG']['img_url_too_big'], $key + 1, $htm_maxsize);
                        }
                    }
                }

                $gallery_count = $this->goodsManageService->getGoodsGalleryCount($goods_id, $is_lib);
                $result['img_desc'] = $gallery_count + 1;

                $this->goodsManageService->handleGalleryImageAdd($goods_id, $_FILES['img_url'], $img_desc, $img_file, '', '', 'ajax', $result['img_desc'], $is_lib);

                clear_cache_files();

                if ($goods_id > 0) {
                    /* 图片列表 */
                    $res = $res->where('goods_id', $goods_id)->orderBy('img_desc', 'ASC');
                } else {
                    $img_id = session('thumb_img_id' . session('admin_id'));
                    if ($img_id) {
                        $img_id = $this->baseRepository->getExplode($img_id);
                        $res = $res->whereIn('img_id', $img_id);
                    }
                    $res = $res->where('goods_id', '')->orderBy('img_desc', 'ASC');
                }

                $img_list = $this->baseRepository->getToArrayGet($res);
                /* 格式化相册图片路径 */
                if (isset($GLOBALS['shop_id']) && ($GLOBALS['shop_id'] > 0)) {
                    foreach ($img_list as $key => $gallery_img) {

                        /* 删除本地商品图片 start */
                        if ($GLOBALS['_CFG']['open_oss'] == 1 && $bucket_info['is_delimg'] == 1) {
                            $gallery_images = [$gallery_img['img_url'], $gallery_img['thumb_url'], $gallery_img['img_original']];
                            dsc_unlink($gallery_images);
                        }
                        /* 删除本地商品图片 start */

                        //图片显示
                        $gallery_img['img_original'] = get_image_path($gallery_img['img_original']);

                        $img_list[$key]['img_url'] = $gallery_img['img_original'];

                        $gallery_img['thumb_url'] = get_image_path($gallery_img['thumb_url']);

                        $img_list[$key]['thumb_url'] = $gallery_img['thumb_url'];
                    }
                } else {
                    foreach ($img_list as $key => $gallery_img) {

                        /* 删除本地商品图片 start */
                        if ($GLOBALS['_CFG']['open_oss'] == 1 && $bucket_info['is_delimg'] == 1) {
                            $gallery_images = [$gallery_img['img_url'], $gallery_img['thumb_url'], $gallery_img['img_original']];
                            dsc_unlink($gallery_images);
                        }
                        /* 删除本地商品图片 start */

                        $gallery_img['thumb_url'] = get_image_path($gallery_img['thumb_url']);

                        $img_list[$key]['thumb_url'] = $gallery_img['thumb_url'];
                    }
                }
                $goods['goods_id'] = $goods_id;
                $this->smarty->assign('img_list', $img_list);
                $img_desc = [];
                foreach ($img_list as $k => $v) {
                    $img_desc[] = $v['img_desc'];
                }
                $img_default = min($img_desc);
                $min_img_id = $res->where('goods_id', $goods_id)->where('img_desc', $img_default)->orderBy('img_desc')->value('img_id');
                $min_img_id = $min_img_id ? $min_img_id : 1;
                $this->smarty->assign('goods', $goods);

                /* 结束处理 start */
                $res = $res->where('goods_id', $goods_id)->orderBy('img_id', 'DESC');
                $this_img_info = $this->baseRepository->getToArrayFirst($res);

                $result['img_id'] = $this_img_info['img_id'];
                $result['min_img_id'] = $min_img_id;
                $pic_name = "";

                $this_img_info['thumb_url'] = get_image_path($this_img_info['thumb_url']);

                $pic_url = $this_img_info['thumb_url'];

                $upload_status = 1;
                /* 结束处理 end */

                $result['external_url'] = "";
                $result['content'] = $this->smarty->fetch('library/gallery_img.lbi');
            }

            if ($upload_status) { //临时文件转移到目标文件夹
                $result['error'] = 0;
                $result['pic'] = $pic_url;
                $result['name'] = $pic_name;
            } else {
                $result['error'] = $GLOBALS['_LANG']['upload_error_server_set'];
            }
            return response()->json($result);
        }
        /*------------------------------------------------------ */
        //-- AJAX刷新管理员的权限
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'ajax_allot') {

            $this->dscRepository->helpersLang('priv_action', 'admin');

            $check_auth = check_authz_json('users_merchants_priv');
            if ($check_auth !== true) {
                return $check_auth;
            }

            /*获取默认等级ID*/
            $default_grade_id = SellerGrade::where('is_default', 1)->value('id');
            $default_grade_id = $default_grade_id ? $default_grade_id : 0;


            $grade_id = isset($_REQUEST['grade_id']) && !empty($_REQUEST['grade_id']) ? intval($_REQUEST['grade_id']) : $default_grade_id;
            $this->smarty->assign("grade_id", $grade_id);

            /*获取全部等级*/
            $seller_grade = SellerGrade::whereRaw(1);
            $seller_grade = $this->baseRepository->getToArrayGet($seller_grade);
            $this->smarty->assign("seller_grade", $seller_grade);

            $priv_str = MerchantsPrivilege::where('grade_id', $grade_id)->value('action_list');
            $priv_str = $priv_str ? $priv_str : '';

            /* 获取权限的分组数据 */
            $res = AdminAction::where('parent_id', 0)->where('seller_show', 1);
            $res = $this->baseRepository->getToArrayGet($res);
            foreach ($res as $rows) {
                $priv_arr[$rows['action_id']] = $rows;
            }

            if ($priv_arr) {
                $db_create_in = array_keys($priv_arr);
            } else {
                $db_create_in = '';
            }

            /* 按权限组查询底级的权限名称 */
            $db_create_in = $this->baseRepository->getExplode($db_create_in);
            $res = AdminAction::whereIn('parent_id', $db_create_in)->where('seller_show', 1);
            $result = $this->baseRepository->getToArrayGet($res);
            foreach ($result as $priv) {
                $priv_arr[$priv["parent_id"]]["priv"][$priv["action_code"]] = $priv;
            }

            if ($priv_arr) {
                // 将同一组的权限使用 "," 连接起来，供JS全选 ecmoban模板堂 --zhuo
                foreach ($priv_arr as $action_id => $action_group) {
                    if ($action_group['priv']) {
                        $priv = @array_keys($action_group['priv']);
                        $priv_arr[$action_id]['priv_list'] = join(',', $priv);
                        if (!empty($action_group['priv'])) {
                            foreach ($action_group['priv'] as $key => $val) {
                                $priv_arr[$action_id]['priv'][$key]['cando'] = (strpos($priv_str, $val['action_code']) !== false || $priv_str == 'all') ? 1 : 0;
                            }
                        }
                    }
                }
            } else {
                $priv_arr = [];
            }

            /* 赋值 */
            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['allot_priv']);
            $this->smarty->assign('priv_arr', $priv_arr);
            $this->smarty->assign('form_act', 'update_allot');

            $content = $this->smarty->fetch('library/ajax_allot.lbi');
            return response()->json($content);
        }
        /*------------------------------------------------------ */
        //-- 转移图片相册
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'album_move_back') {
            $result = ['content' => '', 'pic_id' => ''];
            $pic_id = isset($_REQUEST['pic_id']) ? intval($_REQUEST['pic_id']) : 0;
            $album_id = isset($_REQUEST['album_id']) ? intval($_REQUEST['album_id']) : 0;
            $data = ['album_id' => $album_id];
            PicAlbum::where('pic_id', $pic_id)->update($data);
            $result['pic_id'] = $pic_id;
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 添加相册
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_albun_pic') {
            $result = ['error' => '', 'pic_id' => '', 'content' => ''];

            $allow_file_types = '|GIF|JPG|PNG|';
            $album_mame = isset($_REQUEST['album_mame']) ? addslashes($_REQUEST['album_mame']) : '';
            $parent_id = isset($_REQUEST['parent_id']) ? intval($_REQUEST['parent_id']) : 0;
            $album_desc = isset($_REQUEST['album_desc']) ? addslashes($_REQUEST['album_desc']) : '';
            $sort_order = isset($_REQUEST['sort_order']) ? intval($_REQUEST['sort_order']) : 50;
            /* 检查是否重复 */
            $is_only = GalleryAlbum::where('album_mame', $album_mame)->where('ru_id', $adminru['ru_id'])->count();
            if ($is_only > 0) {
                $result['error'] = 0;
                $result['content'] = $GLOBALS['_LANG']['album'] . $album_mame . $GLOBALS['_LANG']['existence'];
                return response()->json($result);
            }
            /* 取得文件地址 */
            $file_url = '';
            if ((isset($_FILES['album_cover']['error']) && $_FILES['album_cover']['error'] == 0) || (!isset($_FILES['album_cover']['error']) && isset($_FILES['album_cover']['tmp_name']) && $_FILES['album_cover']['tmp_name'] != 'none')) {
                // 检查文件格式
                if (!check_file_type($_FILES['album_cover']['tmp_name'], $_FILES['album_cover']['name'], $allow_file_types)) {
                    $result['error'] = 0;
                    $result['content'] = $GLOBALS['_LANG']['album_prompt_file_type'];
                    return response()->json($result);
                }

                // 复制文件
                $res = $this->getAjaxContentManageService->uploadArticleFile($_FILES['album_cover']);
                if ($res != false) {
                    $file_url = $res;
                }
            }
            if ($file_url == '') {
                $file_url = $_POST['file_url'] ?? '';
            }
            $time = gmtime();
            $data = [
                'album_mame' => $album_mame,
                'parent_album_id' => $parent_id,
                'album_cover' => $file_url,
                'album_desc' => $album_desc,
                'sort_order' => $sort_order,
                'add_time' => $time,
                'ru_id' => $adminru['ru_id']
            ];
            $result['pic_id'] = GalleryAlbum::insertGetId($data);
            $result['error'] = 1;


            $album_list = get_goods_gallery_album(1, $adminru['ru_id'], ['album_id', 'album_mame'], 'ru_id');

            $html = '<li><a href="javascript:;" data-value="0" class="ftx-01">' . $GLOBALS['_LANG']['select_please'] . '</a></li>';
            if (!empty($album_list)) {
                foreach ($album_list as $v) {
                    $html .= '<li><a href="javascript:;" data-value="' . $v['album_id'] . '" class="ftx-01">' . $v['album_mame'] . '</a></li>';
                }
            }
            $result['content'] = $html;
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 获取相册图片
        /*------------------------------------------------------ */
        /*获取相册图片*/
        elseif ($_REQUEST['act'] == 'get_albun_pic') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $is_vis = isset($_REQUEST['is_vis']) && !empty($_REQUEST['is_vis']) ? intval($_REQUEST['is_vis']) : 0;
            $inid = isset($_REQUEST['inid']) && !empty($_REQUEST['inid']) ? trim($_REQUEST['inid']) : 0;
            $pic_list = getAlbumList();

            $this->smarty->assign('pic_list', $pic_list['list']);
            $this->smarty->assign('filter', $pic_list['filter']);
            $this->smarty->assign('temp', 'ajaxPiclist');
            $this->smarty->assign('is_vis', $is_vis);
            $this->smarty->assign('inid', $inid);
            $result['content'] = $this->smarty->fetch('library/dialog.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 广告位模块
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'addmodule') {

            $result = array('error' => 0, 'message' => '', 'content' => '', 'mode' => '');
            $result['spec_attr'] = !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';
            if ($_REQUEST['spec_attr']) {
                $_REQUEST['spec_attr'] = strip_tags(urldecode($_REQUEST['spec_attr']));
                $_REQUEST['spec_attr'] = json_str_iconv($_REQUEST['spec_attr']);
                if (!empty($_REQUEST['spec_attr'])) {
                    $spec_attr = dsc_decode($_REQUEST['spec_attr']);
                    $spec_attr = $this->dscRepository->objectToArray($spec_attr);
                }
            }
            $pic_src = isset($spec_attr['pic_src']) ? $spec_attr['pic_src'] : array();
            $bg_color = isset($spec_attr['bg_color']) ? $spec_attr['bg_color'] : array();
            $link = (isset($spec_attr['link']) && $spec_attr['link'] != ',') ? explode(',', $spec_attr['link']) : array();
            $sort = isset($spec_attr['sort']) ? $spec_attr['sort'] : array();
            $title = isset($spec_attr['title']) ? $spec_attr['title'] : array();
            $subtitle = isset($spec_attr['subtitle']) ? $spec_attr['subtitle'] : array();
            $result['homeAdvBg'] = !empty($spec_attr['homeAdvBg']) ? trim($spec_attr['homeAdvBg']) : '';
            $result['mode'] = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';
            $is_li = isset($spec_attr['is_li']) ? intval($spec_attr['is_li']) : 0;
            $result['slide_type'] = isset($spec_attr['slide_type']) ? addslashes($spec_attr['slide_type']) : '';
            $result['itemsLayout'] = isset($spec_attr['itemsLayout']) ? addslashes($spec_attr['itemsLayout']) : '';
            $result['diff'] = isset($_REQUEST['diff']) ? intval($_REQUEST['diff']) : 0;
            $result['navColor'] = isset($spec_attr['navColor']) ? trim($spec_attr['navColor']) : '';
            $result['toptitle'] = isset($spec_attr['toptitle']) ? trim($spec_attr['toptitle']) : '';//模块标题
            $result['toptitle_url'] = isset($spec_attr['toptitle_url']) ? trim($spec_attr['toptitle_url']) : '';//模块标题链接
            $result['hierarchy'] = isset($spec_attr['hierarchy']) ? trim($spec_attr['hierarchy']) : '';//模块标题链接
            $result['lift'] = isset($spec_attr['lift']) ? trim($spec_attr['lift']) : '';
            $result['activity_goods'] = isset($spec_attr['activity_goods']) ? trim($spec_attr['activity_goods']) : '';//活动商品id
            $result['activity_type'] = isset($spec_attr['activity_type']) ? trim($spec_attr['activity_type']) : 'snatch';//活动类型

            //初始化模块标识
            $temp = "img_list";

            if ($result['mode'] == 'advImg1') {
                $result['picWidth'] = isset($spec_attr['picWidth']) ? intval($spec_attr['picWidth']) : '1200';
            }
            if ($result['hierarchy'] < 3) {
                $count = COUNT($pic_src); //数组长度
                /* 合并数组 */

                $arr = array();
                $sort_vals = array();
                for ($i = 0; $i < $count; $i++) {
                    $arr[$i]['pic_src'] = $pic_src[$i];

                    if (isset($link[$i]) && !empty($link[$i])) {
                        $arr[$i]['link'] = $this->dscRepository->setRewriteUrl($link[$i]);
                    } else {
                        $arr[$i]['link'] = '';
                    }
                    $arr[$i]['bg_color'] = $bg_color[$i] ?? 0;
                    $arr[$i]['sort'] = $sort[$i] ?? 0;
                    $arr[$i]['title'] = $title[$i] ?? '';
                    $arr[$i]['subtitle'] = $subtitle[$i] ?? '';
                    $sort_vals[$i] = $sort[$i] ?? 0;
                }

                if (!empty($arr)) {
                    array_multisort($sort_vals, SORT_ASC, $arr); //数组重新排序
                    $this->smarty->assign('img_list', $arr);
                }
            }

            $search = '';
            $leftjoin = '';
            $where = '';
            $limit = '';
            if ($result['hierarchy'] == 3) {

                //获取促销商品列表
                $goods_list = array();
                $time = gmtime();
                $where = " WHERE 1 ";
                //  start 获取卖场筛选条件bargain_price
                $adminru = get_admin_ru_id();
                if ($adminru['rs_id'] > 0) {
                    $where .= get_rs_goods_where('g.user_id', $adminru['rs_id']);
                }
                // end
                if (!empty($result['activity_goods'])) {
                    $where .= " AND g.goods_id" . db_create_in($result['activity_goods']);
                }
                $limit = " LIMIT 6";
                if ($result['activity_type'] == 'exchange') {
                    $search = ', ga.exchange_integral';
                    $leftjoin = " LEFT JOIN " . $this->dsc->table('exchange_goods') . " AS ga ON ga.goods_id=g.goods_id ";

                    $where .= " AND ga.review_status = 3 AND ga.is_exchange = 1 AND g.is_delete = 0";
                } elseif ($result['activity_type'] == 'presale') {

                    $search = ',ga.act_id, ga.act_name, ga.end_time, ga.start_time ';
                    $leftjoin = " LEFT JOIN " . $this->dsc->table('presale_activity') . " AS ga ON ga.goods_id=g.goods_id ";
                    $where .= " AND ga.review_status = 3 AND ga.start_time <= '$time' AND ga.end_time >= '$time' AND g.goods_id <> '' AND ga.is_finished =0";
                } elseif ($result['activity_type'] == 'is_new') {
                    $where .= " AND g.is_new = 1 AND  g.is_on_sale=1 AND g.is_delete=0";
                } elseif ($result['activity_type'] == 'is_best') {
                    $where .= " AND g.is_best = 1 AND  g.is_on_sale=1 AND g.is_delete=0 ";
                } elseif ($result['activity_type'] == 'is_hot') {
                    $where .= " AND g.is_hot = 1  AND  g.is_on_sale=1 AND g.is_delete=0";
                } else {
                    if ($result['activity_type'] == 'snatch') {
                        $act_type = GAT_SNATCH;
                    } elseif ($result['activity_type'] == 'auction') {

                        $act_type = GAT_AUCTION;
                    } elseif ($result['activity_type'] == 'group_buy') {

                        $act_type = GAT_GROUP_BUY;
                    }
                    $search = ',ga.act_id, ga.act_name, ga.end_time, ga.start_time, ga.ext_info';
                    $leftjoin = " LEFT JOIN " . $this->dsc->table('goods_activity') . " AS ga ON ga.goods_id=g.goods_id ";

                    $where .= " AND ga.review_status = 3 AND ga.start_time <= '$time' AND ga.end_time >= '$time' AND g.goods_id <> '' AND ga.is_finished =0 AND ga.act_type=" . $act_type;
                }
                $sql = "SELECT g.promote_start_date, g.promote_end_date, g.promote_price, g.goods_name, g.goods_id, g.goods_thumb, g.shop_price, g.market_price, g.original_img $search FROM " .
                    $this->dsc->table('goods') . " AS g " . $leftjoin . $where . $limit;
                $goods_list = $this->db->getAll($sql);

                if (!empty($goods_list)) {
                    foreach ($goods_list as $key => $val) {
                        if ($val['promote_price'] > 0 && $time >= $val['promote_start_date'] && $time <= $val['promote_end_date']) {
                            $goods_list[$key]['promote_price'] = price_format($this->goodsCommonService->getBargainPrice($val['promote_price'], $val['promote_start_date'], $val['promote_end_date']));
                        } else {
                            $goods_list[$key]['promote_price'] = '';
                        }
                        $goods_list[$key]['market_price'] = price_format($val['market_price']);
                        $goods_list[$key]['shop_price'] = price_format($val['shop_price']);
                        $goods_list[$key]['goods_thumb'] = get_image_path($val['goods_thumb']);
                        $goods_list[$key]['original_img'] = get_image_path($val['original_img']);

                        if ($result['activity_type'] == 'snatch') {
                            $goods_list[$key]['url'] = $this->dscRepository->buildUri('snatch', array('sid' => $val['act_id']));
                        } elseif ($result['activity_type'] == 'auction') {
                            $goods_list[$key]['url'] = $this->dscRepository->buildUri('auction', array('auid' => $val['act_id']));
                            $ext_info = unserialize($val['ext_info']);
                            $auction = array_merge($val, $ext_info);
                            $goods_list[$key]['promote_price'] = price_format($auction['start_price']);
                        } elseif ($result['activity_type'] == 'group_buy') {
                            $ext_info = unserialize($val['ext_info']);
                            $group_buy = array_merge($val, $ext_info);
                            $goods_list[$key]['promote_price'] = price_format($group_buy['price_ladder'][0]['price']);
                            $goods_list[$key]['url'] = $this->dscRepository->buildUri('group_buy', array('gbid' => $val['act_id']));;
                        } elseif ($result['activity_type'] == 'exchange') {
                            $goods_list[$key]['url'] = $this->dscRepository->buildUri('exchange_goods', array('gid' => $val['goods_id']), $val['goods_name']);
                            $goods_list[$key]['exchange_integral'] = $_LANG['label_integral'] . $val['exchange_integral'];
                        } elseif ($result['activity_type'] == 'presale') {
                            $goods_list[$key]['url'] = $this->dscRepository->buildUri('presale', array('act' => 'view', 'presaleid' => $val['act_id']), $val['goods_name']);
                        } else {
                            $goods_list[$key]['url'] = $this->dscRepository->buildUri('goods', array('gid' => $val['goods_id']), $val['goods_name']);
                        }

                        $goods_list[$key]['goods_name'] = !empty($val['act_name']) ? $val['act_name'] : $val['goods_name'];
                    }
                }

                $this->smarty->assign('goods_list', $goods_list);
            }
            $this->smarty->assign('is_li', $is_li);
            $this->smarty->assign('temp', $temp);
            $this->smarty->assign('attr', $spec_attr);
            $this->smarty->assign('mode', $result['mode']);
            $this->smarty->assign('homeAdvBg', $result['homeAdvBg']);

            $result['content'] = $this->smarty->fetch('library/dialog.lbi');

            return response()->json($result);
        }
        /*------------------------------------------------------ */
        //-- 商品模块
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'changedgoods') {
            load_helper('goods');
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $spec_attr = [];
            $search_type = isset($_REQUEST['search_type']) ? trim($_REQUEST['search_type']) : '';
            $result['lift'] = isset($_REQUEST['lift']) ? trim($_REQUEST['lift']) : '';
            $result['spec_attr'] = isset($_REQUEST['spec_attr']) && !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';
            $_REQUEST['spec_attr'] = isset($_REQUEST['spec_attr']) ? $_REQUEST['spec_attr'] : '';
            if ($_REQUEST['spec_attr']) {
                $_REQUEST['spec_attr'] = strip_tags(urldecode($_REQUEST['spec_attr']));
                $_REQUEST['spec_attr'] = json_str_iconv($_REQUEST['spec_attr']);
                if (!empty($_REQUEST['spec_attr'])) {
                    $spec_attr = dsc_decode($_REQUEST['spec_attr'], true);
                }
            }
            $sort_order = isset($_REQUEST['sort_order']) ? $_REQUEST['sort_order'] : 0;
            $cat_id = isset($_REQUEST['cat_id']) ? explode('_', $_REQUEST['cat_id']) : [];
            $brand_id = isset($_REQUEST['brand_id']) ? intval($_REQUEST['brand_id']) : 0;
            $keyword = isset($_REQUEST['keyword']) ? addslashes($_REQUEST['keyword']) : '';
            $goodsAttr = isset($spec_attr['goods_ids']) ? explode(',', $spec_attr['goods_ids']) : '';
            $goods_ids = isset($_REQUEST['goods_ids']) ? explode(',', $_REQUEST['goods_ids']) : '';
            $ru_id = (isset($_REQUEST['ru_id']) && $_REQUEST['ru_id'] != 'undefined') ? trim($_REQUEST['ru_id']) : -1;
            $result['goods_ids'] = !empty($goodsAttr) ? $goodsAttr : $goods_ids;
            $spec_attr['goods_ids'] = isset($spec_attr['goods_ids']) ? resetBarnd($spec_attr['goods_ids']) : ''; //重置数据
            $result['cat_desc'] = isset($spec_attr['cat_desc']) ? addslashes($spec_attr['cat_desc']) : '';
            $result['cat_name'] = isset($spec_attr['cat_name']) ? addslashes($spec_attr['cat_name']) : '';
            $result['align'] = isset($spec_attr['align']) ? addslashes($spec_attr['align']) : '';
            $result['is_title'] = isset($spec_attr['is_title']) ? intval($spec_attr['is_title']) : 0;
            $result['itemsLayout'] = isset($spec_attr['itemsLayout']) ? addslashes($spec_attr['itemsLayout']) : '';
            $result['diff'] = isset($_REQUEST['diff']) ? intval($_REQUEST['diff']) : 0;
            $type = isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 0;
            $temp = isset($_REQUEST['temp']) && !empty($_REQUEST['temp']) ? trim($_REQUEST['temp']) : 'goods_list';
            $resetRrl = isset($_REQUEST['resetRrl']) ? intval($_REQUEST['resetRrl']) : 0;
            $goods_id = isset($_REQUEST['goods_id']) && !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;

            $result['mode'] = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';
            $this->smarty->assign('temp', $temp);

            $where = [
                'sort_order' => $sort_order,
                'search_type' => $search_type,
                'goods_id' => $goods_id,
                'brand_id' => $brand_id,
                'seller_id' => $adminru['ru_id'],
                'ru_id' => $ru_id,
                'cat_id' => $cat_id,
                'type' => $type,
                'keyword' => $keyword,
                'goods_ids' => $result['goods_ids']
            ];

            if ($type == 1) {
                $where['is_page'] = 1;

                $list = get_content_changed_goods($where);

                $goods_list = $list['list'];
                $filter = $list['filter'];
                $filter['cat_id'] = $cat_id[0];
                $filter['sort_order'] = $sort_order;
                $filter['keyword'] = $keyword;
                $filter['ru_id'] = $ru_id;
                $filter['search_type'] = $search_type;
                $filter['goods_id'] = $goods_id;
                $this->smarty->assign('filter', $filter);
            } else {
                $where['is_page'] = 0;

                $goods_list = get_content_changed_goods($where);
            }
            if (!empty($goods_list)) {
                foreach ($goods_list as $k => $v) {
                    $goods_list[$k]['goods_thumb'] = get_image_path($v['goods_thumb']);
                    $goods_list[$k]['original_img'] = get_image_path($v['original_img']);
                    $goods_list[$k]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $v['goods_id']], $v['goods_name']);
                    $goods_list[$k]['shop_price'] = price_format($v['shop_price']);
                    if ($v['promote_price'] > 0) {
                        $goods_list[$k]['promote_price'] = $this->goodsCommonService->getBargainPrice($v['promote_price'], $v['promote_start_date'], $v['promote_end_date']);
                    } else {
                        $goods_list[$k]['promote_price'] = 0;
                    }
                    if (is_array($result['goods_ids'])) {
                        if ($v['goods_id'] > 0 && in_array($v['goods_id'], $result['goods_ids']) && !empty($result['goods_ids'])) {
                            $goods_list[$k]['is_selected'] = 1;
                        }
                    }
                }
            }
            $this->smarty->assign("is_title", $result['is_title']);
            $this->smarty->assign('goods_list', $goods_list);
            $this->smarty->assign('goods_count', count($goods_list));
            $this->smarty->assign('attr', $spec_attr);
            if (is_array($result['goods_ids'])) {
                $result['goods_ids'] = implode(',', $result['goods_ids']);
            } else {
                $result['goods_ids'] = '';
            }
            $result['content'] = $this->smarty->fetch('library/dialog.lbi');
            return response()->json($result);
        } /*获取图库列表*/
        elseif ($_REQUEST['act'] == 'gallery_album_list') {
            $result = ['error' => 0, 'message' => '', 'log_type' => '', 'content' => ''];
            $album_id = !empty($_REQUEST['album_id']) ? intval($_REQUEST['album_id']) : 0;
            $inid = !empty($_REQUEST['inid']) ? addslashes($_REQUEST['inid']) : '';

            $res = GalleryAlbum::where('ru_id', $adminru['ru_id'])->orderBy('sort_order');
            $gallery_album_list = $this->baseRepository->getToArrayGet($res);

            $this->smarty->assign('gallery_album_list', $gallery_album_list);
            $res = [];
            if ($gallery_album_list || $album_id > 0) {
                $album_id = ($album_id > 0) ? $album_id : $gallery_album_list[0]['album_id'];

                $album_mame = GalleryAlbum::where('ru_id', $adminru['ru_id'])->where('album_id', $album_id)->value('album_mame');
                $album_mame = $album_mame ? $album_mame : '';

                $filter['record_count'] = PicAlbum::where('ru_id', $adminru['ru_id'])->where('album_id', $album_id)->count();

                $filter = page_and_size($filter, 2);

                $res = PicAlbum::where('ru_id', $adminru['ru_id'])->where('album_id', $album_id);
                $res = $res->orderBy('pic_id', 'ASC')->offset($filter['start'])->limit($filter['page_size']);
                $res = $this->baseRepository->getToArrayGet($res);

                if (!empty($res)) {
                    foreach ($res as $k => $v) {
                        if ($v['pic_size'] > 0) {
                            $res[$k]['pic_size'] = number_format($v['pic_size'] / 1024, 2) . 'k';
                        }
                    }
                }
            }
            $this->smarty->assign('pic_album_list', $res);
            $filter['page_arr'] = seller_page($filter, $filter['page'], 14);
            $this->smarty->assign('filter', $filter);
            $this->smarty->assign('album_id', $album_id);
            $this->smarty->assign('inid', $inid);
            $this->smarty->assign('album_mame', $album_mame);
            $this->smarty->assign('image_type', 1);
            $result['content'] = $this->smarty->fetch('library/album_dialog.lbi');

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 商品选择相册图
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_goodsImg') {
            $result = ['error' => 0, 'message' => ''];
            $pic_id = !empty($_REQUEST['pic_id']) ? trim($_REQUEST['pic_id']) : '';
            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $inid = !empty($_REQUEST['inid']) ? trim($_REQUEST['inid']) : '';
            $is_lib = !empty($_REQUEST['is_lib']) ? intval($_REQUEST['is_lib']) : 0;
            //初始化数据
            $thumb_img_id = [];
            $img_default = 1;

            $img_list = [];
            if ($pic_id) {

                $pic_id = $this->baseRepository->getExplode($pic_id);

                $img_list = PicAlbum::whereIn('pic_id', $pic_id)->get();
                $img_list = $img_list ? $img_list->toArray() : [];
            }

            /* 商品库和普通商品相册 */
            if ($img_list) {
                $j = 0;
                foreach ($img_list as $key => $val) {
                    $j++;
                    $img_id = 0;
                    //获取排序
                    if ($inid == 'gallery_album') {
                        $img_desc_new = 1;
                        if ($goods_id > 0) {
                            if ($is_lib) {
                                GoodsLibGallery::where('goods_id', $goods_id)
                                    ->increment('img_desc', 1);
                            } else {
                                GoodsGallery::where('goods_id', $goods_id)
                                    ->increment('img_desc', 1);
                            }
                        }
                    } else {
                        $gallery_count = $this->goodsManageService->getGoodsGalleryCount($goods_id);
                        $img_desc_new = $gallery_count + 1;
                    }
                    //处理链接
                    $val['pic_file'] = str_replace(' ', "", $val['pic_file'], $i);
                    $val['pic_image'] = str_replace(' ', "", $val['pic_image'], $i);
                    $val['pic_thumb'] = str_replace(' ', "", $val['pic_thumb'], $i);
                    //商品图片初始化
                    if ($j == 1) {
                        $result['data'] = [
                            'original_img' => $val['pic_file'],
                            'goods_img' => $val['pic_image'],
                            'goods_thumb' => $val['pic_thumb']
                        ];
                    } else {
                        $result['data'] = [];
                    }

                    $other = [
                        'goods_id' => $goods_id,
                        'img_url' => $val['pic_image'],
                        'img_desc' => $img_desc_new,
                        'thumb_url' => $val['pic_thumb'],
                        'img_original' => $val['pic_file']
                    ];

                    if ($is_lib) {
                        $thumb_img_id[] = GoodsLibGallery::insertGetId($other);
                    } else {
                        $thumb_img_id[] = GoodsGallery::insertGetId($other);
                    }

                    $this->dscRepository->getOssAddFile($result['data']);
                }
            }
            if (!empty(session('thumb_img_id' . session('admin_id'))) && is_array($thumb_img_id) && is_array(session('thumb_img_id' . session('admin_id')))) {
                $thumb_img_id = array_merge($thumb_img_id, session('thumb_img_id' . session('admin_id')));
            }

            session([
                'thumb_img_id' . session('admin_id') => $thumb_img_id
            ]);

            if ($goods_id > 0) {

                /* 图片列表 */
                if ($is_lib) {
                    $goods_gallery_list = GoodsLibGallery::where('goods_id', $goods_id);
                } else {
                    $goods_gallery_list = GoodsGallery::where('goods_id', $goods_id);
                }

            } else {
                $where = '';
                if (!empty(session('thumb_img_id' . session('admin_id')))) {
                    $where = "AND img_id " . db_create_in(session('thumb_img_id' . session('admin_id'))) . "";
                }

                if ($is_lib) {
                    $goods_gallery_list = GoodsLibGallery::where('goods_id', $goods_id);
                } else {
                    $goods_gallery_list = GoodsGallery::where('goods_id', $goods_id);
                }

                $thumb_img_id = session()->has('thumb_img_id' . session('admin_id')) ? session('thumb_img_id' . session('admin_id')) : [];
                $thumb_img_id = $this->baseRepository->getExplode($thumb_img_id);

                if (!empty($thumb_img_id)) {
                    $goods_gallery_list = $goods_gallery_list->whereIn('img_id', $thumb_img_id);
                }
            }

            $goods_gallery_list = $goods_gallery_list->orderBy('img_desc', 'asc')->get();

            $goods_gallery_list = $goods_gallery_list ? $goods_gallery_list->toArray() : [];

            if (!empty($goods_gallery_list)) {
                foreach ($goods_gallery_list as $key => $val) {
                    if ($val['thumb_url']) {
                        $goods_gallery_list[$key]['thumb_url'] = get_image_path($val['thumb_url']);
                    }
                }
            }
            $this->smarty->assign('img_list', $goods_gallery_list);

            if (isset($result['data']['goods_thumb']) && $result['data']['goods_thumb']) {
                $result['data']['thumb'] = get_image_path($result['data']['goods_thumb']);
            } else {
                $result['data']['thumb'] = '';
            }

            $result['content'] = $this->smarty->fetch('library/gallery_img.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 商品编辑器
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'getFCKeditor') {
            $result = ['goods_desc' => 0];
            $content = isset($_REQUEST['content']) ? stripslashes($_REQUEST['content']) : '';

            $img_src = isset($_REQUEST['img_src']) ? trim($_REQUEST['img_src']) : '';

            if ($img_src) {
                $img_src = explode(',', $img_src);

                if (!empty($img_src)) {
                    foreach ($img_src as $v) {
                        $content .= "<p><img src='" . $v . "' /></p>";
                    }
                }
            }

            if (!empty($content)) {
                create_html_editor('goods_desc', trim($content));
                $result['goods_desc'] = $this->smarty->get_template_vars('FCKeditor');
            }
            return response()->json($result);
        }

        /*--------------------------------------------------------*/
        //头部导航
        /*--------------------------------------------------------*/
        elseif ($_REQUEST['act'] == 'nav_mode') {
            load_helper('goods');
            $result = [];
            $result['mode'] = !empty($_REQUEST['mode']) ? trim($_REQUEST['mode']) : '';
            $navname = !empty($_REQUEST['navname']) ? $_REQUEST['navname'] : '';
            $navurl = !empty($_REQUEST['navurl']) ? $_REQUEST['navurl'] : '';
            $navvieworder = !empty($_REQUEST['navvieworder']) ? $_REQUEST['navvieworder'] : '';
            $count = COUNT($navname);//数组长度
            /*合并数组*/
            $arr = [];
            $sort_vals = [];

            if (!empty($navname)) {
                for ($i = 0; $i < $count; $i++) {
                    $arr[$i]['name'] = trim($navname[$i]);
                    $arr[$i]['url'] = $navurl[$i];
                    $arr[$i]['opennew'] = 1;
                    $arr[$i]['navvieworder'] = isset($navvieworder[$i]) ? intval($navvieworder[$i]) : 0;
                    $sort_vals[$i] = isset($navvieworder[$i]) ? intval($navvieworder[$i]) : 0;
                }
            }
            if (!empty($arr)) {
                array_multisort($sort_vals, SORT_ASC, $arr);//数组重新排序
            }
            $this->smarty->assign('navigator', $arr);
            $this->smarty->assign('temp', 'navigator_home');
            $result['spec_attr'] = $arr;
            $result['content'] = $this->smarty->fetch('library/dialog.lbi');
            return response()->json($result);
        } //信息栏
        elseif ($_REQUEST['act'] == 'insertVipEdit') {

            $attr = [];
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $result['mode'] = isset($_REQUEST['mode']) && !empty($_REQUEST['mode']) ? trim($_REQUEST['mode']) : '';
            $result['suffixed'] = isset($_REQUEST['suffix']) && !empty($_REQUEST['suffix']) ? trim($_REQUEST['suffix']) : '';
            $result['spec_attr'] = isset($_REQUEST['spec_attr']) && !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';
            $_REQUEST['spec_attr'] = strip_tags(urldecode($_REQUEST['spec_attr']));
            $_REQUEST['spec_attr'] = json_str_iconv($_REQUEST['spec_attr']);
            if (isset($_REQUEST['spec_attr']) && !empty($_REQUEST['spec_attr'])) {
                $spec_attr = dsc_decode($_REQUEST['spec_attr'], true);
            }
            $quick_url = isset($spec_attr['quick_url']) ? explode(',', $spec_attr['quick_url']) : [];
            $quick_name = isset($spec_attr['quick_name']) ? $spec_attr['quick_name'] : [];
            $index_article_cat = isset($spec_attr['index_article_cat']) ? trim($spec_attr['index_article_cat']) : '';
            $style_icon = isset($spec_attr['style_icon']) ? $spec_attr['style_icon'] : [];
            //获取快捷入口数组
            $count = COUNT($quick_url);//数组长度
            /*合并数组*/
            $arr = [];
            $name_count = 0;
            for ($i = 0; $i < $count; $i++) {
                if ($quick_url[$i]) {
                    $arr[$i]['quick_url'] = $this->dscRepository->setRewriteUrl($quick_url[$i]);
                } else {
                    $arr[$i]['quick_url'] = $quick_url[$i];
                }
                $arr[$i]['quick_name'] = $quick_name[$i];
                if ($quick_name[$i]) {
                    $name_count++;
                }
                $arr[$i]['style_icon'] = $style_icon[$i];
            }

            //获取热门文章数组
            //首页文章栏目
            if (!empty($index_article_cat)) {
                load_helper('main');
                load_helper('article');
                $index_article_arr = [];
                $index_article_cat_arr = explode(',', $index_article_cat);

                foreach ($index_article_cat_arr as $key => $val) {
                    $index_article_arr[] = $this->articleService->getAssignArticles($val, 3);
                }

                $this->smarty->assign('index_article_cat', $index_article_arr);
            }
            $this->smarty->assign("name_count", $name_count);
            $this->smarty->assign('attr', $arr);
            $this->smarty->assign('temp', $_REQUEST['act']);
            $this->smarty->assign("suffix", $result['suffixed']);
            $result['content'] = $this->smarty->fetch('library/dialog.lbi');
            return response()->json($result);
        } //广告栏信息处理
        elseif ($_REQUEST['act'] == 'homeAdvInsert') {
            $attr = [];
            $allow_file_types = '|GIF|JPG|PNG|';
            $result = ['error' => 0, 'message' => '', 'content' => ''];


            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);
            //初始化数据
            $result['moded'] = isset($_REQUEST['mode']) && !empty($_REQUEST['mode']) ? trim($_REQUEST['mode']) : '';
            $title = isset($_REQUEST['title']) && !empty($_REQUEST['title']) ? $_REQUEST['title'] : '';
            $subtitle = isset($_REQUEST['subtitle']) && !empty($_REQUEST['subtitle']) ? $_REQUEST['subtitle'] : '';
            $original_img = isset($_REQUEST['original_img']) && !empty($_REQUEST['original_img']) ? $_REQUEST['original_img'] : '';
            $homeAdvBg = isset($_REQUEST['homeAdvBg']) && !empty($_REQUEST['homeAdvBg']) ? $_REQUEST['homeAdvBg'] : '';
            $masterTitle = isset($_REQUEST['masterTitle']) && !empty($_REQUEST['masterTitle']) ? $_REQUEST['masterTitle'] : '';
            $url = isset($_REQUEST['url']) && !empty($_REQUEST['url']) ? $_REQUEST['url'] : '';
            $lift = isset($_REQUEST['lift']) && !empty($_REQUEST['lift']) ? trim($_REQUEST['lift']) : '';
            $needColor = isset($_REQUEST['needColor']) && !empty($_REQUEST['needColor']) ? trim($_REQUEST['needColor']) : '';
            $arr = [];
            $count = $original_img ? count($original_img) : 0;
            for ($i = 0; $i < $count; $i++) {
                //处理返回数组
                $arr[$i]['title'] = $title[$i];
                $arr[$i]['subtitle'] = $subtitle[$i];
                if ($url[$i]) {
                    $arr[$i]['url'] = $this->dscRepository->setRewriteUrl($url[$i]);
                } else {
                    $arr[$i]['url'] = $url[$i];
                }
                $arr[$i]['original_img'] = $original_img[$i];
                $arr[$i]['homeAdvBg'] = $homeAdvBg[$i];
            }
            $this->smarty->assign('spec_attr', $arr);
            $this->smarty->assign('masterTitle', $masterTitle);
            $this->smarty->assign('temp', $result['moded']);
            $this->smarty->assign('needColor', $needColor);
            $result['masterTitle'] = $masterTitle;
            $arr['needColor'] = $needColor;
            $result['lift'] = $lift;
            $result['spec_attr'] = $arr;
            $result['content'] = $this->smarty->fetch('library/dialog.lbi');
            return response()->json($result);
        } //获取指定分类的下级分类
        elseif ($_REQUEST['act'] == 'getChildCat') {
            $result = ['content' => ''];
            $cat_id = !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;
            $cat_list = $this->categoryService->catList($cat_id);

            $html = '';
            $html_child = '';
            if ($cat_list) {
                $html = '<div class="imitate_select select_w220" id="cat_id1"><div class="cite">' . $GLOBALS['_LANG']['select_please'] . '</div><ul>';
                foreach ($cat_list as $k => $v) {
                    $html_child .= '<li><a href="javascript:void(0);" data-value="' . $v['cat_id'] . '">' . $v['cat_name'] . '</a></li>';
                }
                $html .= $html_child . '</ul><input type="hidden" value="" id="cat_id_val1"></div> ';
            }
            $result['content'] = $html;
            $result['contentChild'] = $html_child;
            return response()->json($result);
        } /*楼层*/
        elseif ($_REQUEST['act'] == 'homeFloor') {
            $result = ['content' => ''];
            $spec_attr = [];
            $result['floor_title'] = isset($_REQUEST['floor_title']) && !empty($_REQUEST['floor_title']) ? trim($_REQUEST['floor_title']) : ''; //楼层标题
            $result['sub_title'] = isset($_REQUEST['sub_title']) && !empty($_REQUEST['sub_title']) ? trim($_REQUEST['sub_title']) : ''; //楼层标题
            $spec_attr['hierarchy'] = isset($_REQUEST['hierarchy']) && !empty($_REQUEST['']) ? intval($_REQUEST['hierarchy']) : '';
            $result['cat_goods'] = isset($_REQUEST['cat_goods']) && !empty($_REQUEST['cat_goods']) ? $_REQUEST['cat_goods'] : '';
            $result['moded'] = isset($_REQUEST['mode']) && !empty($_REQUEST['mode']) ? trim($_REQUEST['mode']) : '';
            $result['cat_id'] = isset($_REQUEST['Floorcat_id']) && !empty($_REQUEST['Floorcat_id']) ? intval($_REQUEST['Floorcat_id']) : 0; //一级分类
            $result['cateValue'] = isset($_REQUEST['cateValue']) && !empty($_REQUEST['cateValue']) ? $_REQUEST['cateValue'] : ''; //二级分类
            $result['typeColor'] = isset($_REQUEST['typeColor']) && !empty($_REQUEST['typeColor']) ? trim($_REQUEST['typeColor']) : ''; //颜色
            $result['fontColor'] = isset($_REQUEST['fontColor']) && !empty($_REQUEST['fontColor']) ? trim($_REQUEST['fontColor']) : ''; //字体颜色
            $result['floorMode'] = isset($_REQUEST['floorMode']) && !empty($_REQUEST['floorMode']) ? intval($_REQUEST['floorMode']) : ''; //模板

            $result['brand_ids'] = isset($_REQUEST['brand_ids']) && !empty($_REQUEST['brand_ids']) ? trim($_REQUEST['brand_ids']) : ''; //楼层品牌

            $result['leftBanner'] = isset($_REQUEST['leftBanner']) && !empty($_REQUEST['leftBanner']) ? $_REQUEST['leftBanner'] : ''; //左侧轮播
            $result['leftBannerLink'] = isset($_REQUEST['leftBannerLink']) && !empty($_REQUEST['leftBannerLink']) ? $_REQUEST['leftBannerLink'] : ''; //左侧轮播链接
            $result['leftBannerSort'] = isset($_REQUEST['leftBannerSort']) && !empty($_REQUEST['leftBannerSort']) ? $_REQUEST['leftBannerSort'] : ''; //左侧轮播排序
            $result['leftBannerTitle'] = isset($_REQUEST['leftBannerTitle']) && !empty($_REQUEST['leftBannerTitle']) ? $_REQUEST['leftBannerTitle'] : ''; //左侧轮播住标题
            $result['leftBannerSubtitle'] = isset($_REQUEST['leftBannerSubtitle']) && !empty($_REQUEST['leftBannerSubtitle']) ? $_REQUEST['leftBannerSubtitle'] : ''; //左侧轮播副标题

            $result['leftAdv'] = isset($_REQUEST['leftAdv']) && !empty($_REQUEST['leftAdv']) ? $_REQUEST['leftAdv'] : ''; //左侧广告
            $result['leftAdvLink'] = isset($_REQUEST['leftAdvLink']) && !empty($_REQUEST['leftAdvLink']) ? $_REQUEST['leftAdvLink'] : ''; //左侧广告链接
            $result['leftAdvSort'] = isset($_REQUEST['leftAdvSort']) && !empty($_REQUEST['leftAdvSort']) ? $_REQUEST['leftAdvSort'] : ''; //左侧广告排序

            $result['rightAdv'] = isset($_REQUEST['rightAdv']) && !empty($_REQUEST['rightAdv']) ? $_REQUEST['rightAdv'] : ''; //右侧广告
            $result['rightAdvLink'] = isset($_REQUEST['rightAdvLink']) && !empty($_REQUEST['rightAdvLink']) ? $_REQUEST['rightAdvLink'] : ''; //右侧广告链接
            $result['rightAdvSort'] = isset($_REQUEST['rightAdvSort']) && !empty($_REQUEST['rightAdvSort']) ? $_REQUEST['rightAdvSort'] : ''; //右侧广告排序
            $result['rightAdvTitle'] = isset($_REQUEST['rightAdvTitle']) && !empty($_REQUEST['rightAdvTitle']) ? $_REQUEST['rightAdvTitle'] : ''; //右侧广告主标题
            $result['rightAdvSubtitle'] = isset($_REQUEST['rightAdvSubtitle']) && !empty($_REQUEST['rightAdvSubtitle']) ? $_REQUEST['rightAdvSubtitle'] : ''; //右侧广告副标题

            $result['top_goods'] = isset($_REQUEST['top_goods']) && !empty($_REQUEST['top_goods']) ? trim($_REQUEST['top_goods']) : ''; //楼层分类商品字符串
            $spec_attr = $result;
            $result['lift'] = isset($_REQUEST['lift']) && !empty($_REQUEST['lift']) ? trim($_REQUEST['lift']) : '';

            //初始化楼层图片数组
            $AdvNum = getAdvNum($result['moded'], $result['floorMode']);

            //处理轮播数组
            $AdvBanner = [];
            $sort_vals = [];
            if (isset($AdvNum['leftBanner']) && $AdvNum['leftBanner'] > 0) {
                if (isset($result['leftBanner']) && !empty($result['leftBanner'])) {
                    foreach ($result['leftBanner'] as $k => $v) {
                        $AdvBanner[$k]['leftBanner'] = isset($result['leftBanner'][$k]) ? $result['leftBanner'][$k] : '';
                        $AdvBanner[$k]['leftBannerSort'] = isset($result['leftBannerSort'][$k]) ? $result['leftBannerSort'][$k] : '';
                        if (isset($result['leftBannerLink'][$k]) && $result['leftBannerLink'][$k]) {
                            $AdvBanner[$k]['leftBannerLink'] = $this->dscRepository->setRewriteUrl($result['leftBannerLink'][$k]);
                        } else {
                            $AdvBanner[$k]['leftBannerLink'] = isset($result['leftBannerLink'][$k]) ? $result['leftBannerLink'][$k] : '';
                        }
                        $AdvBanner[$k]['leftBannerTitle'] = isset($result['leftBannerTitle'][$k]) ? $result['leftBannerTitle'][$k] : '';
                        $AdvBanner[$k]['leftBannerSubtitle'] = isset($result['leftBannerSubtitle'][$k]) ? $result['leftBannerSubtitle'][$k] : '';
                        $sort_vals[$k] = isset($result['leftBannerSort'][$k]) ? $result['leftBannerSort'][$k] : 0;
                    }
                } else {
                    $AdvNum['leftBanner'] = 1;
                    if (isset($AdvNum['leftBanner']) && $AdvNum['leftBanner']) {
                        for ($k = 0; $k < $AdvNum['leftBanner']; $k++) {
                            $AdvBanner[$k]['leftBanner'] = isset($result['leftBanner'][$k]) ? $result['leftBanner'][$k] : '';
                            $AdvBanner[$k]['leftBannerSort'] = isset($result['leftBannerSort'][$k]) ? $result['leftBannerSort'][$k] : '';
                            if (isset($result['leftBannerLink'][$k]) && $result['leftBannerLink'][$k]) {
                                $AdvBanner[$k]['leftBannerLink'] = $this->dscRepository->setRewriteUrl($result['leftBannerLink'][$k]);
                            } else {
                                $AdvBanner[$k]['leftBannerLink'] = isset($result['leftBannerLink'][$k]) ? $result['leftBannerLink'][$k] : '';
                            }
                            $AdvBanner[$k]['leftBannerTitle'] = isset($result['leftBannerTitle'][$k]) ? $result['leftBannerTitle'][$k] : '';
                            $AdvBanner[$k]['leftBannerSubtitle'] = isset($result['leftBannerSubtitle'][$k]) ? $result['leftBannerSubtitle'][$k] : '';
                            $sort_vals[$k] = isset($result['leftBannerSort'][$k]) ? $result['leftBannerSort'][$k] : 0;
                        }
                    }
                }
            }

            if ($sort_vals && $AdvBanner) {
                array_multisort($sort_vals, SORT_ASC, $AdvBanner); //数组重新排序
            }
            $spec_attr['leftBanner'] = $AdvBanner;


            //处理左侧广告数组
            $AdvLeft = [];
            $sort_vals = [];
            if (isset($AdvNum['leftAdv']) && $AdvNum['leftAdv'] > 0) {
                for ($k = 0; $k < $AdvNum['leftAdv']; $k++) {
                    $AdvLeft[$k]['leftAdv'] = isset($result['leftAdv'][$k]) ? $result['leftAdv'][$k] : '';
                    $AdvLeft[$k]['leftAdvSort'] = isset($result['leftAdvSort'][$k]) ? $result['leftAdvSort'][$k] : '';
                    if (isset($result['leftAdvLink'][$k]) && $result['leftAdvLink'][$k]) {
                        $AdvLeft[$k]['leftAdvLink'] = $this->dscRepository->setRewriteUrl($result['leftAdvLink'][$k]);
                    } else {
                        $AdvLeft[$k]['leftAdvLink'] = isset($result['leftAdvLink'][$k]) ? $result['leftAdvLink'][$k] : '';
                    }
                    $sort_vals[$k] = isset($result['leftAdvSort'][$k]) ? $result['leftAdvSort'][$k] : 0;
                }
            }
            if ($sort_vals && $AdvLeft) {
                array_multisort($sort_vals, SORT_ASC, $AdvLeft); //数组重新排序
            }

            $spec_attr['leftAdv'] = $AdvLeft;

            //处理右侧广告数组
            $AdvRight = [];
            $sort_vals = [];
            if (isset($AdvNum['rightAdv']) && $AdvNum['rightAdv'] > 0) {
                for ($k = 0; $k < $AdvNum['rightAdv']; $k++) {
                    $AdvRight[$k]['rightAdv'] = isset($result['rightAdv'][$k]) ? $result['rightAdv'][$k] : '';
                    $AdvRight[$k]['rightAdvSort'] = isset($result['rightAdvSort'][$k]) ? $result['rightAdvSort'][$k] : '';
                    if (isset($result['leftBannerLink'][$k]) && $result['leftBannerLink'][$k]) {
                        $AdvRight[$k]['rightAdvLink'] = $this->dscRepository->setRewriteUrl($result['rightAdvLink'][$k]);
                    } else {
                        $AdvRight[$k]['rightAdvLink'] = isset($result['rightAdvLink'][$k]) ? $result['rightAdvLink'][$k] : '';
                    }
                    $AdvRight[$k]['rightAdvTitle'] = isset($result['rightAdvTitle'][$k]) ? $result['rightAdvTitle'][$k] : '';
                    $AdvRight[$k]['rightAdvSubtitle'] = isset($result['rightAdvSubtitle'][$k]) ? $result['rightAdvSubtitle'][$k] : '';
                    $sort_vals[$k] = isset($result['rightAdvSort'][$k]) && !empty($result['rightAdvSort'][$k]) ? $result['rightAdvSort'][$k] : 10;
                }
            }
            if ($sort_vals && $AdvRight) {
                array_multisort($sort_vals, SORT_ASC, $AdvRight); //数组重新排序
            }
            $spec_attr['rightAdv'] = $AdvRight;
            if ($result['cat_id'] > 0) {
                $cat_info = Category::catInfo($result['cat_id'])->first();
                $cat_info = $cat_info ? $cat_info->toArray() : [];

                if ($cat_info['cat_alias_name']) {
                    $spec_attr['cat_alias_name'] = $cat_info['cat_alias_name'];
                } else {
                    $spec_attr['cat_alias_name'] = $cat_info['cat_name'];
                }
                $spec_attr['cat_name'] = $cat_info['cat_name'];
                $spec_attr['style_icon'] = $cat_info['style_icon'];
                $spec_attr['cat_icon'] = get_image_path($cat_info['cat_icon']);
            }
            $storeThree = 0;
            //获得二级分类的所有信息
            if (!empty($result['cateValue'])) {
                $cat_tow = [];
                $i = 0;
                foreach ($result['cateValue'] as $k => $v) {
                    $i++;
                    if ($result['moded'] == 'storeThreeFloor1' && $result['floorMode'] == 4 && $i == 1) {
                        $storeThree = $v;//店铺第三套模板  默认第一位二级分类
                    }
                    $arr = [];
                    if ($v > 0) {
                        $res = Category::where('cat_id', $v);
                        $arr = $this->baseRepository->getToArrayFirst($res);

                        $arr['goods_id'] = $result['cat_goods'][$k];
                        $arr['url'] = $this->dscRepository->buildUri('category', ['cid' => $arr['cat_id']], $arr['cat_name']);
                        $cat_tow[] = $arr;
                    }
                }
                $spec_attr['cateValue'] = $cat_tow;
            }
            $brand_list = '';

            if ($result['brand_ids']) {

                $result['brand_ids'] = $this->baseRepository->getExplode($result['brand_ids']);
                $res = Brand::whereIn('brand_id', $result['brand_ids'])->whereHas('getBrandExtend');
                $brand_list = $this->baseRepository->getToArrayGet($res);

                if (!empty($brand_list)) {
                    foreach ($brand_list as $key => $val) {
                        if ($val['site_url'] && strlen($val['site_url']) > 8) {
                            $brand_list[$key]['url'] = $val['site_url'];
                        } else {
                            $brand_list[$key]['url'] = $this->dscRepository->buildUri('brandn', ['bid' => $val['brand_id']], $val['brand_name']);
                        }
                        $val['brand_logo'] = DATA_DIR . '/brandlogo/' . $val['brand_logo'];
                        $brand_list[$key]['brand_logo'] = get_image_path($val['brand_logo']);
                    }
                }

                $this->smarty->assign("brand_list", $brand_list);
            }
            //初始化图片数量
            $advNumber = 6;
            if ($spec_attr['floorMode'] == '5') {
                $advNumber = 5;
            } elseif ($spec_attr['floorMode'] == '6' || $spec_attr['floorMode'] == '7') {
                $advNumber = 4;
            } elseif ($spec_attr['floorMode'] == '8') {
                $advNumber = 3;
            }

            //处理标题特殊字符
            if ($result['rightAdvTitle']) {
                foreach ($result['rightAdvTitle'] as $k => $v) {
                    if ($v) {
                        $result['rightAdvTitle'][$k] = strFilter($v, 'rightAdvTitle');
                    }
                }
            }

            if ($result['rightAdvSubtitle']) {
                foreach ($result['rightAdvSubtitle'] as $k => $v) {
                    if ($v) {
                        $result['rightAdvSubtitle'][$k] = strFilter($v, 'rightAdvSubtitle');
                    }
                }
            }

            //获取楼层4的默认商品$result['top_goods']
            if ($result['moded'] == 'homeFloorFour' || $result['moded'] == 'homeFloorFive' || $result['moded'] == 'homeFloorSeven' ||
                $result['moded'] == 'homeFloorEight' || $result['moded'] == 'homeFloorTen' || $result['moded'] == 'storeOneFloor1' ||
                $result['moded'] == 'storeTwoFloor1' || $result['moded'] == 'storeThreeFloor1' || $result['moded'] == 'storeFourFloor1' ||
                $result['moded'] == 'storeFiveFloor1' || $result['moded'] == 'topicOneFloor' || $result['moded'] == 'topicTwoFloor' ||
                $result['moded'] == 'topicThreeFloor' || ($result['moded'] == 'homeFloorSix' && $result['floorMode'] != 1)) {
                $res = Goods::select(
                    'promote_start_date',
                    'promote_end_date',
                    'promote_price',
                    'goods_name',
                    'goods_id',
                    'goods_thumb',
                    'shop_price',
                    'market_price',
                    'original_img'
                );
                $res = $res->where('is_on_sale', 1)
                    ->where('is_alone_sale', 1)
                    ->where('is_delete', 0)
                    ->where('review_status', '>', 2);

                if ($result['top_goods']) {
                    $top_goods = $this->baseRepository->getExplode($result['top_goods']);
                    $res = $res->whereIn('goods_id', $top_goods);
                }

                if ($result['cat_id'] > 0) {
                    load_helper('goods');
                    $search_cat = $result['cat_id'];
                    //店铺第三套模板  默认第一位二级分类
                    if ($result['moded'] == 'storeThreeFloor1' && $result['floorMode'] == 4) {
                        $search_cat = $storeThree;
                    }

                    $children = $this->categoryService->getCatListChildren($search_cat);
                    $extension_goods = get_extension_goods($children);

                    $res = $res->where(function ($query) use ($children, $extension_goods) {
                        $children = $this->baseRepository->getExplode($children);
                        $extension_goods = $this->baseRepository->getExplode($extension_goods);
                        $query->whereIn('cat_id', $children)->orWhereIn('goods_id', $extension_goods);

                    });
                }
                //  start 获取卖场筛选条件
                $adminru = get_admin_ru_id();
                if ($adminru['rs_id'] > 0) {
                    $res = $this->dscRepository->getWhereRsid($res, 'user_id', $adminru['rs_id']);
                }
                // end
                $limit = 8;
                $goods_num = -1;
                if ($result['moded'] == 'homeFloorFour') {
                    if ($result['floorMode'] == 1) {
                        $goods_num = 3;
                    } elseif ($result['floorMode'] == 2) {
                        $limit = 10;
                    } elseif ($result['floorMode'] == 3) {
                        $limit = 10;
                    } elseif ($result['floorMode'] == 4) {
                        $limit = 12;
                    }
                } elseif ($result['moded'] == 'homeFloorSix') {
                    if ($result['floorMode'] == 2) {
                        $limit = 4;
                    } elseif ($result['floorMode'] == 3) {
                        $limit = 6;
                    }
                } elseif ($result['moded'] == 'homeFloorSeven') {
                    $limit = 6;
                } elseif ($result['moded'] == 'homeFloorEight') {
                    if ($result['floorMode'] == 2) {
                        $limit = 6;
                    } elseif ($result['floorMode'] == 3) {
                        $limit = 8;
                    } elseif ($result['floorMode'] == 4) {
                        $limit = 4;
                    } elseif ($result['floorMode'] == 5) {
                        $limit = 4;
                    }
                } elseif ($result['moded'] == 'homeFloorTen') {
                    if ($result['floorMode'] == 1 || $result['floorMode'] == 2) {
                        $limit = 8;
                    } elseif ($result['floorMode'] == 3) {
                        $limit = 6;
                    } elseif ($result['floorMode'] == 4) {
                        $limit = 10;
                    }
                } elseif ($result['moded'] == 'storeOneFloor1') {
                    $limit = 6;
                    if ($result['floorMode'] == 3) {
                        $limit = 6;
                        if ($result['top_goods'] != '') {
                            $limit = '';
                        }
                    } elseif ($result['floorMode'] == 4) {
                        $limit = 8;
                        if ($result['top_goods'] != '') {
                            $limit = '';
                        }
                    }
                } elseif ($result['moded'] == 'storeTwoFloor1') {
                    if ($result['floorMode'] == 2) {
                        $limit = 6;
                    } elseif ($result['floorMode'] == 3) {
                        $limit = 8;
                    }

                    if ($result['top_goods'] != '') {
                        $limit = '';
                    }
                } elseif ($result['moded'] == 'storeThreeFloor1') {
                    if ($result['floorMode'] == 2) {
                        $limit = 6;
                    } elseif ($result['floorMode'] == 3) {
                        $limit = 10;
                    } elseif ($result['floorMode'] == 4) {
                        $limit = 8;
                    }
                } elseif ($result['moded'] == 'storeFourFloor1') {
                    if ($result['floorMode'] == 3) {
                        $limit = 6;
                    } elseif ($result['floorMode'] == 4) {
                        $limit = 12;
                    }
                } elseif ($result['moded'] == 'storeFiveFloor1') {
                    if ($result['floorMode'] == 1) {
                        $limit = 6;
                    } else {
                        $limit = 8;
                        if ($result['top_goods'] != '') {
                            $limit = '';
                        }
                    }
                } elseif ($result['moded'] == 'topicOneFloor') {
                    if ($result['floorMode'] == 2) {
                        $limit = 6;
                    } elseif ($result['floorMode'] == 3) {
                        $limit = 8;
                    }

                    if ($result['top_goods'] != '') {
                        $limit = '';
                    }
                } elseif ($result['moded'] == 'topicTwoFloor') {
                    $limit = 10;
                } elseif ($result['moded'] == 'topicThreeFloor') {
                    if ($result['floorMode'] == 1) {
                        $limit = 8;
                    } elseif ($result['floorMode'] == 3) {
                        $limit = 10;
                    }
                }

                $res = $res->orderByRaw('sort_order,goods_id DESC');
                if ($limit) {
                    $res = $res->limit($limit);
                }
                $goods_list = $this->baseRepository->getToArrayGet($res);

                if (!empty($goods_list)) {
                    foreach ($goods_list as $key => $val) {
                        if ($val['promote_price'] > 0 && $time >= $val['promote_start_date'] && $time <= $val['promote_end_date']) {

                            $promote_price = $this->goodsCommonService->getBargainPrice($val['promote_price'], $val['promote_start_date'], $val['promote_end_date']);

                            $goods_list[$key]['promote_price'] = price_format($promote_price);
                        } else {
                            $goods_list[$key]['promote_price'] = '';
                        }
                        $goods_list[$key]['shop_price'] = price_format($val['shop_price']);
                        $goods_list[$key]['goods_thumb'] = get_image_path($val['goods_thumb']);
                        $goods_list[$key]['original_img'] = get_image_path($val['original_img']);

                        if ((strpos($goods_list[$key]['goods_thumb'], 'http://') === false && strpos($goods_list[$key]['goods_thumb'], 'https://') === false)) {
                            $goods_list[$key]['goods_thumb'] = $this->dsc->url() . $goods_list[$key]['goods_thumb'];
                        }

                        if ((strpos($goods_list[$key]['original_img'], 'http://') === false && strpos($goods_list[$key]['original_img'], 'https://') === false)) {
                            $goods_list[$key]['original_img'] = $this->dsc->url() . $goods_list[$key]['original_img'];
                        }
                        $goods_list[$key]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $val['goods_id']], $val['goods_name']);
                    }
                }
                $this->smarty->assign("goods_list", $goods_list);
                $this->smarty->assign("goods_num", $goods_num);
            }
            $this->smarty->assign("advNumber", $advNumber);
            $this->smarty->assign("spec_attr", $spec_attr);
            $this->smarty->assign("temp", $result['moded']);
            $result['spec_attr'] = $result;
            $result['content'] = $this->smarty->fetch('library/dialog.lbi');
            return response()->json($result);
        } //CMS频道数据处理
        elseif ($_REQUEST['act'] == 'edit_cmsarti') {
            $result = ['content' => ''];
            $spec_attr = '';
            $result['sort'] = isset($_REQUEST['sort']) && !empty($_REQUEST['sort']) ? $_REQUEST['sort'] : []; //分类排序
            $result['article_id'] = isset($_REQUEST['article_id']) && !empty($_REQUEST['article_id']) ? $_REQUEST['article_id'] : []; //文章id
            $result['moded'] = isset($_REQUEST['mode']) && !empty($_REQUEST['mode']) ? trim($_REQUEST['mode']) : '';
            $result['def_article_id'] = isset($_REQUEST['def_article_id']) && !empty($_REQUEST['def_article_id']) ? $_REQUEST['def_article_id'] : []; //默认首位文章id数组
            $arr = [];
            $sort_vals = [];
            //处理数组
            if (!empty($result['sort'])) {
                foreach ($result['sort'] as $k => $v) {
                    $arr[$k]['cat_id'] = $k;
                    $arr[$k]['article_id'] = $result['article_id'][$k];
                    $arr[$k]['cat_name'] = ArticleCat::where('cat_id', $k)->value('cat_name');
                    $arr[$k]['cat_name'] = $arr[$k]['cat_name'] ? $arr[$k]['cat_name'] : '';

                    $arr[$k]['article_list'] = [];
                    $arr[$k]['first_article_list'] = [];//默认首位文章
                    $condition = get_article_children($k); //如果是头条则只显示cat_id为6和9栏目下的信息

                    $res = Article::select('article_id', 'content', 'title', 'add_time', 'description', 'file_url', 'open_type');
                    $res = $res->whereRaw($condition);
                    if ($result['article_id'][$k]) {
                        $article_id = $this->baseRepository->getExplode($result['article_id'][$k]);
                        $res = $res->whereIn('article_id', $article_id);
                    }

                    $res = $res->where('is_open', 1)->where('article_type', 0);
                    $res = $res->orderBy('article_type', 'DESC')
                        ->orderBy('sort_order', 'ASC')
                        ->orderBy('article_id', 'DESC')
                        ->limit(5);
                    $article_list = $this->baseRepository->getToArrayGet($res);

                    if ($article_list) {
                        foreach ($article_list as $key => $val) {
                            if ($val['file_url']) {
                                $article_list[$key]['file_url'] = get_image_path($val['file_url']);
                            } else {
                                $article_list[$key]['file_url'] = get_image_path('images/article_default.jpg');
                            }
                            $article_list[$key]['add_time'] = local_date('Y.m.d', $val['add_time']);
                            $article_list[$key]['url'] = $val['open_type'] != 1 ?
                                $this->dscRepository->buildUri('article', ['aid' => $val['article_id']], $val['title']) : trim($val['file_url']);
                            if ($result['def_article_id'][$k] == $val['article_id']) {
                                $arr[$k]['first_article_list'] = $article_list[$key];
                                unset($article_list[$key]);
                            }
                        }
                        if (empty($arr[$k]['first_article_list'])) {
                            $arr[$k]['first_article_list'] = $article_list[0];
                            unset($article_list[0]);
                        }
                    }
                    $arr[$k]['article_list'] = $article_list;
                    $arr[$k]['sort'] = $result['sort'][$k];
                    $sort_vals[$k] = isset($result['sort'][$k]) ? $result['sort'][$k] : 0;
                }
            }

            if ($arr && !empty($sort_vals)) {
                array_multisort($sort_vals, SORT_ASC, $arr); //数组重新排序
            }
            $this->smarty->assign("spec_attr", $arr);
            $this->smarty->assign("temp", $result['moded']);
            $result['spec_attr'] = $result;
            $result['content'] = $this->smarty->fetch('library/dialog.lbi');
            return response()->json($result);
        } //CMS频道 商品模块
        elseif ($_REQUEST['act'] == 'edit_cmsgoods') {
            $result = ['content' => ''];
            $spec_attr = [];
            $spec_attr['hierarchy'] = isset($_REQUEST['hierarchy']) && !empty($_REQUEST['hierarchy']) ? intval($_REQUEST['hierarchy']) : '';
            $result['moded'] = isset($_REQUEST['mode']) && !empty($_REQUEST['mode']) ? trim($_REQUEST['mode']) : '';
            $result['goods_ids'] = isset($_REQUEST['goods_ids']) && !empty($_REQUEST['goods_ids']) ? trim($_REQUEST['goods_ids']) : ''; //商品id
            $result['article_ids'] = isset($_REQUEST['select_article_ids']) && !empty($_REQUEST['select_article_ids']) ? trim($_REQUEST['select_article_ids']) : ''; //文章id
            $result['article_title'] = isset($_REQUEST['article_title']) && !empty($_REQUEST['article_title']) ? trim($_REQUEST['article_title']) : $GLOBALS['_LANG']['recent_hot']; //文章标题
            $result['goods_title'] = isset($_REQUEST['goods_title']) && !empty($_REQUEST['goods_title']) ? trim($_REQUEST['goods_title']) : $GLOBALS['_LANG']['best_recommend']; //商品标题
            $result['cat_id'] = isset($_REQUEST['cat_id']) && !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : '0'; //商品分类
            $result['articat_id'] = isset($_REQUEST['articat_id']) && !empty($_REQUEST['articat_id']) ? intval($_REQUEST['articat_id']) : '0'; //商品分类
            $result['def_article'] = isset($_REQUEST['def_article']) && !empty($_REQUEST['def_article']) ? intval($_REQUEST['def_article']) : '0'; //商品分类

            //获取选中商品
            $res = Goods::select(
                'goods_name',
                'goods_id',
                'goods_thumb',
                'shop_price',
                'original_img'
            );
            $res = $res->where('is_on_sale', 1)
                ->where('is_alone_sale', 1)
                ->where('is_delete', 0)
                ->where('review_status', '>', 2);

            if ($result['goods_ids']) {
                $result['goods_ids'] = $this->baseRepository->getExplode($result['goods_ids']);
                $res = $res->whereIn('goods_id', $result['goods_ids']);
            }
            $res = $res->orderByRaw('sort_order, goods_id DESC')->limit(4);
            $goods_list = $this->baseRepository->getToArrayGet($res);

            if (!empty($goods_list)) {
                foreach ($goods_list as $key => $val) {
                    $goods_list[$key]['goods_thumb'] = get_image_path($val['goods_thumb']);

                    if ((strpos($goods_list[$key]['goods_thumb'], 'http://') === false && strpos($goods_list[$key]['goods_thumb'], 'https://') === false)) {
                        $goods_list[$key]['goods_thumb'] = $this->dsc->url() . $goods_list[$key]['goods_thumb'];
                    }

                    $goods_list[$key]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $val['goods_id']], $val['goods_name']);
                }
            }
            $this->smarty->assign("goods_list", $goods_list);

            //获取选择文章
            $res = Article::select('article_id', 'content', 'title', 'add_time', 'description', 'file_url', 'open_type');
            $res = $res->where('is_open', 1)->where('article_type', 0);

            if ($result['article_ids']) {
                $result['article_ids'] = $this->baseRepository->getExplode($result['article_ids']);
                $res = $res->whereIn('article_id', $result['article_ids']);
            }
            $res = $res->orderBy('article_type', 'DESC')->orderBy('sort_order', 'ASC')->limit(5);
            $article_list = $this->baseRepository->getToArrayGet($res);

            $def_article_list = [];
            if ($article_list) {
                foreach ($article_list as $key => $val) {
                    if ($val['file_url']) {
                        $article_list[$key]['file_url'] = get_image_path($val['file_url']);
                    } else {
                        $article_list[$key]['file_url'] = get_image_path('images/article_default.jpg');
                    }
                    $article_list[$key]['add_time'] = local_date('Y.m.d', $val['add_time']);
                    $article_list[$key]['url'] = $val['open_type'] != 1 ?
                        $this->dscRepository->buildUri('article', ['aid' => $val['article_id']], $val['title']) : trim($val['file_url']);
                    if ($result['def_article'] == $val['article_id']) {
                        $def_article_list = $article_list[$key];
                        unset($article_list[$key]);
                    }
                }
            }
            $this->smarty->assign("def_article_list", $def_article_list);
            $this->smarty->assign("article_list", $article_list);
            $this->smarty->assign("temp", $result['moded']);
            $this->smarty->assign("spec_attr", $result);
            $result['spec_attr'] = $result;
            $result['content'] = $this->smarty->fetch('library/dialog.lbi');
            return response()->json($result);
        } //CMS频道头部广告
        elseif ($_REQUEST['act'] == 'edit_cmsIn') {
            $result = ['content' => ''];
            $spec_attr = [];
            $spec_attr['hierarchy'] = isset($_REQUEST['hierarchy']) && !empty($_REQUEST['hierarchy']) ? intval($_REQUEST['hierarchy']) : '';
            $result['moded'] = isset($_REQUEST['mode']) && !empty($_REQUEST['mode']) ? trim($_REQUEST['mode']) : '';
            $result['floorMode'] = isset($_REQUEST['floorMode']) && !empty($_REQUEST['floorMode']) ? intval($_REQUEST['floorMode']) : ''; //模板

            $result['leftBanner'] = isset($_REQUEST['leftBanner']) && !empty($_REQUEST['leftBanner']) ? $_REQUEST['leftBanner'] : ''; //左侧轮播
            $result['leftBannerLink'] = isset($_REQUEST['leftBannerLink']) && !empty($_REQUEST['leftBannerLink']) ? $_REQUEST['leftBannerLink'] : ''; //左侧轮播链接
            $result['leftBannerSort'] = isset($_REQUEST['leftBannerSort']) && !empty($_REQUEST['leftBannerSort']) ? $_REQUEST['leftBannerSort'] : ''; //左侧轮播排序
            $result['leftBannerTitle'] = isset($_REQUEST['leftBannerTitle']) && !empty($_REQUEST['leftBannerTitle']) ? $_REQUEST['leftBannerTitle'] : ''; //左侧轮播，描述

            $result['leftAdv'] = isset($_REQUEST['leftAdv']) && !empty($_REQUEST['leftAdv']) ? $_REQUEST['leftAdv'] : ''; //左侧广告
            $result['leftAdvLink'] = isset($_REQUEST['leftAdvLink']) && !empty($_REQUEST['leftAdvLink']) ? $_REQUEST['leftAdvLink'] : ''; //左侧广告链接
            $result['leftAdvSort'] = isset($_REQUEST['leftAdvSort']) && !empty($_REQUEST['leftAdvSort']) ? $_REQUEST['leftAdvSort'] : ''; //左侧广告排序
            $result['leftAdvTitle'] = isset($_REQUEST['leftAdvTitle']) && !empty($_REQUEST['leftAdvTitle']) ? $_REQUEST['leftAdvTitle'] : ''; //左侧广告描述

            $spec_attr = $result;
            $result['lift'] = isset($_REQUEST['lift']) && !empty($_REQUEST['lift']) ? trim($_REQUEST['lift']) : '';

            //初始化楼层图片数组
            $AdvNum = getAdvNum($result['moded'], $result['floorMode']);

            //处理轮播数组
            $AdvBanner = [];
            $sort_vals = [];
            if (isset($AdvNum['leftBanner']) && $AdvNum['leftBanner']) {
                if ($AdvNum['leftBanner'] > 0) {
                    if (isset($result['leftBanner']) && !empty($result['leftBanner'])) {
                        foreach ($result['leftBanner'] as $k => $v) {
                            $AdvBanner[$k]['leftBanner'] = isset($result['leftBanner'][$k]) ? $result['leftBanner'][$k] : '';
                            $AdvBanner[$k]['leftBannerSort'] = isset($result['leftBannerSort'][$k]) ? $result['leftBannerSort'][$k] : '';
                            if (isset($result['leftBannerLink'][$k]) && $result['leftBannerLink'][$k]) {
                                $AdvBanner[$k]['leftBannerLink'] = $this->dscRepository->setRewriteUrl($result['leftBannerLink'][$k]);
                            } else {
                                $AdvBanner[$k]['leftBannerLink'] = isset($result['leftBannerLink'][$k]) ? $result['leftBannerLink'][$k] : '';
                            }
                            $AdvBanner[$k]['leftBannerTitle'] = isset($result['leftBannerTitle'][$k]) ? $result['leftBannerTitle'][$k] : '';
                            $sort_vals[$k] = isset($result['leftBannerSort'][$k]) ? $result['leftBannerSort'][$k] : 0;
                        }
                    } else {
                        $AdvNum['leftBanner'] = 1;
                        for ($k = 0; $k < $AdvNum['leftBanner']; $k++) {
                            $AdvBanner[$k]['leftBanner'] = isset($result['leftBanner'][$k]) ? $result['leftBanner'][$k] : '';
                            $AdvBanner[$k]['leftBannerSort'] = isset($result['leftBannerSort'][$k]) ? $result['leftBannerSort'][$k] : '';
                            if ($result['leftBannerLink'][$k]) {
                                $AdvBanner[$k]['leftBannerLink'] = $this->dscRepository->setRewriteUrl($result['leftBannerLink'][$k]);
                            } else {
                                $AdvBanner[$k]['leftBannerLink'] = isset($result['leftBannerLink'][$k]) ? $result['leftBannerLink'][$k] : '';
                            }
                            $AdvBanner[$k]['leftBannerTitle'] = isset($result['leftBannerTitle'][$k]) ? $result['leftBannerTitle'][$k] : '';
                            $sort_vals[$k] = isset($result['leftBannerSort'][$k]) ? $result['leftBannerSort'][$k] : 0;
                        }
                    }
                }
            }

            if ($sort_vals && $AdvBanner) {
                array_multisort($sort_vals, SORT_ASC, $AdvBanner); //数组重新排序
            }
            $spec_attr['leftBanner'] = $AdvBanner;


            //处理左侧广告数组
            $AdvLeft = [];
            $sort_vals = [];
            if (isset($AdvNum['leftAdv']) && $AdvNum['leftAdv'] > 0) {
                for ($k = 0; $k < $AdvNum['leftAdv']; $k++) {
                    $AdvLeft[$k]['leftAdv'] = isset($result['leftAdv'][$k]) ? $result['leftAdv'][$k] : '';
                    $AdvLeft[$k]['leftAdvSort'] = isset($result['leftAdvSort'][$k]);
                    if (isset($result['leftAdvLink'][$k]) && $result['leftAdvLink'][$k]) {
                        $AdvLeft[$k]['leftAdvLink'] = $this->dscRepository->setRewriteUrl($result['leftAdvLink'][$k]);
                    } else {
                        $AdvLeft[$k]['leftAdvLink'] = isset($result['leftAdvLink'][$k]);
                    }
                    $AdvLeft[$k]['leftAdvTitle'] = isset($result['leftAdvTitle'][$k]) ? $result['leftAdvTitle'][$k] : '';
                    $sort_vals[$k] = isset($result['leftAdvSort'][$k]) ? $result['leftAdvSort'][$k] : 0;
                }
            }
            if ($sort_vals && $AdvLeft) {
                array_multisort($sort_vals, SORT_ASC, $AdvLeft); //数组重新排序
            }
            //处理标题特殊字符
            if (isset($result['leftBannerTitle']) && $result['leftBannerTitle']) {
                foreach ($result['leftBannerTitle'] as $k => $v) {
                    if ($v) {
                        $result['leftBannerTitle'][$k] = strFilter($v);
                    }
                }
            }
            if (isset($result['leftAdvTitle']) && $result['leftAdvTitle']) {
                foreach ($result['leftAdvTitle'] as $k => $v) {
                    if ($v) {
                        $result['leftAdvTitle'][$k] = strFilter($v);
                    }
                }
            }
            $spec_attr['leftAdv'] = $AdvLeft;
            $this->smarty->assign("spec_attr", $spec_attr);
            $this->smarty->assign("temp", $result['moded']);
            $result['spec_attr'] = $result;
            $result['content'] = $this->smarty->fetch('library/dialog.lbi');
            return response()->json($result);
        } //获取指定分类的下级分类
        elseif ($_REQUEST['act'] == 'get_childcat') {
            $result = ['content' => ''];

            $cat_id = !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;
            $level = !empty($_REQUEST['level']) ? intval($_REQUEST['level']) : 0;
            $level = $level + 1;//重定义获取分类层级

            $res = ArticleCat::where('parent_id', $cat_id);
            $article_cat = $this->baseRepository->getToArrayGet($res);

            $this->smarty->assign('cat_select', $article_cat);//获取文章分类
            $this->smarty->assign("temp", $_REQUEST['act']);
            $this->smarty->assign("level", $level);
            $result['level'] = $level;
            $result['content'] = $this->smarty->fetch('library/dialog.lbi');
            return response()->json($result);
        } //获取指定文章分类下所有分类的文章
        elseif ($_REQUEST['act'] == 'getcat_atr') {
            $result = ['content' => ''];

            $type = !empty($_REQUEST['type']) ? intval($_REQUEST['type']) : 0;
            $cat_id = !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;
            $page = !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 0;
            $old_article = !empty($_REQUEST['old_article']) ? trim($_REQUEST['old_article']) : '';
            $def_article = !empty($_REQUEST['def_article']) ? trim($_REQUEST['def_article']) : '';

            $article_list = ['list' => [], 'filter' => []];


            $where = [
                'cat_id' => $cat_id
            ];

            if ($type == 1) {
                $where['article_id'] = $old_article;
            }

            if ($type == 0 || ($old_article && $type == 1)) {
                $article_list = getcat_atr($where);
            }

            $article = $article_list['list'];
            $filter = $article_list['filter'];
            if ($old_article && !empty($article)) {
                $old_article_arr = explode(',', $old_article);
                foreach ($article as $k => $v) {
                    if (in_array($v['article_id'], $old_article_arr)) {
                        $article[$k]['selected'] = 1;
                    } else {
                        $article[$k]['selected'] = 0;
                    }
                }
            }
            $filter['old_article'] = $old_article;
            $this->smarty->assign('article_list', $article);
            $this->smarty->assign('def_article', $def_article);
            $this->smarty->assign('filter', $filter);
            $this->smarty->assign("temp", $_REQUEST['act']);
            $this->smarty->assign("cat_id", $cat_id);
            if ($page > 0) {
                $this->smarty->assign('full_page', 1);
            }
            $result['content'] = $this->smarty->fetch('library/dialog.lbi');
            return response()->json($result);
        } //品牌
        elseif ($_REQUEST['act'] == 'homeBrand') {
            $result = ['content' => ''];
            $spec_attr = '';
            $result['moded'] = !empty($_REQUEST['mode']) ? trim($_REQUEST['mode']) : '';
            $result['suffixed'] = !empty($_REQUEST['suffix']) ? trim($_REQUEST['suffix']) : '';
            $result['barndAdv'] = !empty($_REQUEST['barndAdv']) ? $_REQUEST['barndAdv'] : '';
            $result['brand_ids'] = !empty($_REQUEST['brand_ids']) ? trim($_REQUEST['brand_ids']) : '';
            $result['barndAdvLink'] = !empty($_REQUEST['barndAdvLink']) ? $_REQUEST['barndAdvLink'] : ''; //广告链接
            $result['barndAdvSort'] = !empty($_REQUEST['barndAdvSort']) ? $_REQUEST['barndAdvSort'] : ''; //广告排序
            $result['barndAdvTitle'] = !empty($_POST['barndAdvTitle']) ? $_POST['barndAdvTitle'] : ''; //主标题
            $result['barndAdvSubtitle'] = !empty($_REQUEST['barndAdvSubtitle']) ? $_REQUEST['barndAdvSubtitle'] : ''; //副标题

            $lift = !empty($_REQUEST['lift']) ? trim($_REQUEST['lift']) : '';
            $bandnumber = !empty($_REQUEST['bandnumber']) ? intval($_REQUEST['bandnumber']) : 17;

            $spec_attr = $result;
            $adv = [];
            $sort_vals = [];
            if ($result['barndAdv']) {
                foreach ($result['barndAdv'] as $k => $v) {
                    $adv[$k]['barndAdv'] = isset($result['barndAdv'][$k]) ? $result['barndAdv'][$k] : '';
                    $adv[$k]['barndAdvSort'] = isset($result['barndAdvSort'][$k]) ? $result['barndAdvSort'][$k] : '';
                    if (isset($result['barndAdvLink'][$k]) && $result['barndAdvLink'][$k]) {
                        $adv[$k]['barndAdvLink'] = $this->dscRepository->setRewriteUrl($result['barndAdvLink'][$k]);
                    } else {
                        $adv[$k]['barndAdvLink'] = isset($result['barndAdvLink'][$k]) ? $result['barndAdvLink'][$k] : '';
                    }

                    $adv[$k]['barndAdvTitle'] = isset($result['barndAdvTitle'][$k]) ? $result['barndAdvTitle'][$k] : '';
                    $adv[$k]['barndAdvSubtitle'] = isset($result['barndAdvSubtitle'][$k]) ? $result['barndAdvSubtitle'][$k] : '';
                    $sort_vals[$k] = isset($result['barndAdvSort'][$k]) ? $result['barndAdvSort'][$k] : 0;
                }
            }

            if ($sort_vals && $adv) {
                array_multisort($sort_vals, SORT_ASC, $adv);//数组重新排序
            }
            $brand_list = '';

            if ($spec_attr['brand_ids']) {

                $brandId = $spec_attr['brand_ids'];
                $brandId = $this->baseRepository->getExplode($brandId);

                $res = Brand::whereIn('brand_id', $brandId);
                $res = $res->whereHas('getBrandExtend', function ($query) {
                    $query->where('is_recommend', 1);
                });

                $res = $res->withCount([
                    'getCollectBrand as collect_count'
                ]);

                $res = $res->limit($bandnumber);
                $brand_list = $this->baseRepository->getToArrayGet($res);

                if (!empty($brand_list)) {
                    foreach ($brand_list as $key => $val) {
                        if ($val['site_url'] && strlen($val['site_url']) > 8) {
                            $brand_list[$key]['url'] = $val['site_url'];
                        } else {
                            $brand_list[$key]['url'] = $this->dscRepository->buildUri('brandn', ['bid' => $val['brand_id']], $val['brand_name']);
                        }
                        $val['brand_logo'] = DATA_DIR . '/brandlogo/' . $val['brand_logo'];
                        $brand_list[$key]['brand_logo'] = get_image_path($val['brand_logo']);
                    }
                }
            }
            $this->smarty->assign("brand_list", $brand_list);
            $this->smarty->assign("barndAdv", $adv);
            $this->smarty->assign("brand_ids", $spec_attr['brand_ids']);
            $this->smarty->assign("temp", $result['moded']);
            $this->smarty->assign("suffix", $result['suffixed']);
            $result['spec_attr'] = $result;
            $result['lift'] = $lift;
            $result['content'] = $this->smarty->fetch('library/dialog.lbi');
            return response()->json($result);
        } //限时促销
        elseif ($_REQUEST['act'] == 'honePromo') {
            load_helper('goods');
            $spec_attr = [];
            $spec_attr['recommend'] = isset($_REQUEST['recommend']) && !empty($_REQUEST['recommend']) ? intval($_REQUEST['recommend']) : 0;
            $spec_attr['moded'] = isset($_REQUEST['mode']) && !empty($_REQUEST['mode']) ? trim($_REQUEST['mode']) : '';
            $time_bucket = !empty($_REQUEST['time_bucket']) ? intval($_REQUEST['time_bucket']) : 0;

            $spec_attr['suffixed'] = '';
            if ($spec_attr['moded'] == 'h-seckill') {
                $goods_ids = isset($_REQUEST['goods_ids']) && !empty($_REQUEST['goods_ids']) ? $_REQUEST['goods_ids'] : [];
                $spec_attr['goods_ids'][$time_bucket] = $goods_ids ? $goods_ids[$time_bucket] : [];

                $spec_attr['suffixed'] = isset($_REQUEST['suffix']) && !empty($_REQUEST['suffix']) ? trim($_REQUEST['suffix']) : '';
            } else {
                $spec_attr['goods_ids'] = isset($_REQUEST['goods_ids']) && !empty($_REQUEST['goods_ids']) ? trim($_REQUEST['goods_ids']) : '';
            }

            $spec_attr['lift'] = isset($_REQUEST['lift']) && !empty($_REQUEST['lift']) ? trim($_REQUEST['lift']) : '';
            $spec_attr['title'] = isset($_REQUEST['title']) && !empty($_REQUEST['title']) ? trim($_REQUEST['title']) : '';
            $spec_attr['subtitle'] = isset($_REQUEST['subtitle']) && !empty($_REQUEST['subtitle']) ? trim($_REQUEST['subtitle']) : '';
            $spec_attr['navColor'] = isset($_REQUEST['navColor']) && !empty($_REQUEST['navColor']) ? trim($_REQUEST['navColor']) : '';
            $spec_attr['diff'] = isset($_REQUEST['diff']) ? intval($_REQUEST['diff']) : 0;
            $PromotionType = isset($_REQUEST['PromotionType']) && !empty($_REQUEST['PromotionType']) ? trim($_REQUEST['PromotionType']) : '';
            $spec_attr['PromotionType'] = $PromotionType;

            $result['lift'] = $spec_attr['lift'];
            //获取促销商品列表
            $goods_list = '';
            $time = gmtime();
            $where = " WHERE 1 ";
            //  start 获取卖场筛选条件
            $adminru = get_admin_ru_id();
            if ($adminru['rs_id'] > 0) {
                $where .= get_rs_goods_where('g.user_id', $adminru['rs_id']);
            }
            // end
            if (!empty($spec_attr['goods_ids'])) {
                if (!empty($PromotionType)) {
                    $where .= " AND g.goods_id" . db_create_in($spec_attr['goods_ids']);
                    if ($PromotionType == 'exchange') {
                        if (empty($spec_attr['title'])) {
                            $spec_attr['title'] = $GLOBALS['_LANG']['integral_mall'];
                        }
                        $search = ', ga.exchange_integral';
                        $leftjoin = " LEFT JOIN " . $this->dsc->table('exchange_goods') . " AS ga ON ga.goods_id=g.goods_id ";

                        $where .= " AND ga.review_status = 3 AND ga.is_exchange = 1 AND g.is_delete = 0";
                    } elseif ($PromotionType == 'presale') {
                        if (empty($spec_attr['title'])) {
                            $spec_attr['title'] = $GLOBALS['_LANG']['16_presale'];
                        }
                        $search = ',ga.act_id, ga.act_name, ga.end_time, ga.start_time ';
                        $leftjoin = " LEFT JOIN " . $this->dsc->table('presale_activity') . " AS ga ON ga.goods_id=g.goods_id ";
                        $where .= " AND ga.review_status = 3 AND ga.start_time <= '$time' AND ga.end_time >= '$time' AND g.goods_id <> '' AND ga.is_finished =0";
                    } else {
                        if ($PromotionType == 'snatch') {
                            if (empty($spec_attr['title'])) {
                                $spec_attr['title'] = $GLOBALS['_LANG']['02_snatch_list'];
                            }
                            $act_type = GAT_SNATCH;
                        } elseif ($PromotionType == 'auction') {
                            if (empty($spec_attr['title'])) {
                                $spec_attr['title'] = $GLOBALS['_LANG']['10_auction'];
                            }
                            $act_type = GAT_AUCTION;
                        } elseif ($PromotionType == 'group_buy') {
                            if (empty($spec_attr['title'])) {
                                $spec_attr['title'] = $GLOBALS['_LANG']['08_group_buy'];
                            }
                            $act_type = GAT_GROUP_BUY;
                        }
                        $search = ',ga.act_id, ga.act_name, ga.end_time, ga.start_time, ga.ext_info';
                        $leftjoin = " LEFT JOIN " . $this->dsc->table('goods_activity') . " AS ga ON ga.goods_id=g.goods_id ";

                        $where .= " AND ga.review_status = 3 AND ga.start_time <= '$time' AND ga.end_time >= '$time' AND g.goods_id <> '' AND ga.is_finished =0 AND ga.act_type=" . $act_type;
                    }
                    $sql = "SELECT g.promote_start_date, g.promote_end_date, g.promote_price, g.goods_name, g.goods_id, g.goods_thumb, g.shop_price, g.market_price, g.original_img $search FROM " .
                        $this->dsc->table('goods') . " AS g " . $leftjoin . $where;
                } elseif ($spec_attr['moded'] == 'h-seckill') {
                    $where = " WHERE 1 ";
                    $where .= " AND s.review_status = 3 AND s.is_putaway = 1 AND s.begin_time < '$time' AND  s.acti_time > '$time' AND sg.tb_id = '$time_bucket'";
                    $goods_id = 0;
                    if ($time_bucket > 0) {
                        $goods_ids = $spec_attr['goods_ids'][$time_bucket];
                    } else {
                        $goods_ids = current($spec_attr['goods_ids']);
                        $time_bucket = array_search($goods_id, $spec_attr['goods_ids']);
                    }
                    $spec_attr['time_bucket'] = $time_bucket;
                    $where .= " AND sg.id" . db_create_in($goods_ids);

                    $where .= " AND g.is_on_sale=1 AND g.is_delete=0 ";
                    if ($GLOBALS['_CFG']['review_goods'] == 1) {
                        $where .= ' AND g.review_status > 2 ';
                    }

                    $sql = "SELECT g.promote_start_date, g.promote_end_date, g.promote_price, g.goods_name, g.goods_id, g.goods_thumb,"
                        . " g.shop_price, g.market_price, g.original_img , sg.sec_price,sg.id FROM" . $this->dsc->table('goods') . " AS g "
                        . "LEFT JOIN " . $this->dsc->table('seckill_goods') . " AS sg ON sg.goods_id=g.goods_id "
                        . "LEFT JOIN " . $this->dsc->table('seckill') . "AS s ON s.sec_id=sg.sec_id" . $where;
                } else {
                    if (empty($spec_attr['title'])) {
                        $spec_attr['title'] = $GLOBALS['_LANG']['time_limit_promotion'];
                    }
                    $where .= " AND g.is_on_sale=1 AND g.is_delete=0 ";
                    if ($GLOBALS['_CFG']['review_goods'] == 1) {
                        $where .= ' AND g.review_status > 2 ';
                    }
                    $where .= " AND promote_start_date <= '$time' AND promote_end_date >=  '$time' AND promote_price > 0";
                    $where .= " AND g.goods_id" . db_create_in($spec_attr['goods_ids']);
                    $sql = "SELECT g.promote_start_date, g.promote_end_date, g.promote_price, g.goods_name, g.goods_id, g.goods_thumb, " .
                        " g.shop_price, g.market_price, g.original_img FROM " .
                        $this->dsc->table('goods') . " AS g " . $where;
                }
                $goods_list = $this->db->getAll($sql);
                $recommend = '';
                if (!empty($goods_list)) {
                    if ($spec_attr['recommend'] == 0) {
                        $spec_attr['recommend'] = $goods_list[0]['goods_id'];
                    }

                    foreach ($goods_list as $key => $val) {
                        if ($val['promote_price'] > 0 && $time >= $val['promote_start_date'] && $time <= $val['promote_end_date']) {

                            $promote_price = $this->goodsCommonService->getBargainPrice($val['promote_price'], $val['promote_start_date'], $val['promote_end_date']);

                            $goods_list[$key]['promote_price'] = price_format($promote_price);
                        } else {
                            $goods_list[$key]['promote_price'] = '';
                        }

                        $val['sec_price'] = isset($val['sec_price']) ? $val['sec_price'] : 0;

                        $goods_list[$key]['market_price'] = price_format($val['market_price']);
                        $goods_list[$key]['sec_price'] = price_format($val['sec_price']);
                        $goods_list[$key]['shop_price'] = price_format($val['shop_price']);
                        $goods_list[$key]['goods_thumb'] = get_image_path($val['goods_thumb']);
                        $goods_list[$key]['original_img'] = get_image_path($val['original_img']);

                        if (!empty($PromotionType)) {
                            if ($PromotionType == 'snatch') {
                                $goods_list[$key]['url'] = $this->dscRepository->buildUri('snatch', ['sid' => $val['act_id']]);
                            } elseif ($PromotionType == 'auction') {
                                $goods_list[$key]['url'] = $this->dscRepository->buildUri('auction', ['auid' => $val['act_id']]);
                                $ext_info = unserialize($val['ext_info']);
                                $auction = array_merge($val, $ext_info);
                                $goods_list[$key]['promote_price'] = price_format($auction['start_price']);
                            } elseif ($PromotionType == 'group_buy') {
                                $ext_info = unserialize($val['ext_info']);
                                $group_buy = array_merge($val, $ext_info);
                                $goods_list[$key]['promote_price'] = price_format($group_buy['price_ladder'][0]['price']);
                                $goods_list[$key]['url'] = $this->dscRepository->buildUri('group_buy', ['gbid' => $val['act_id']]);
                            } elseif ($PromotionType == 'exchange') {
                                $goods_list[$key]['url'] = $this->dscRepository->buildUri('exchange_goods', ['gid' => $val['goods_id']], $val['goods_name']);
                                $goods_list[$key]['exchange_integral'] = $GLOBALS['_LANG']['label_integral'] . $val['exchange_integral'];
                            } elseif ($PromotionType == 'presale') {
                                $goods_list[$key]['url'] = $this->dscRepository->buildUri('presale', ['act' => 'view', 'presaleid' => $val['act_id']], $val['goods_name']);
                            }
                            if ($PromotionType != 'exchange' && empty($PromotionType)) {
                                $goods_list[$key]['goods_name'] = !empty($val['act_name']) ? $val['act_name'] : $val['goods_name'];
                            }
                            if ($spec_attr['recommend'] > 0 && $spec_attr['recommend'] == $val['goods_id']) {
                                $recommend = $goods_list[$key];
                                unset($goods_list[$key]);
                            }
                        } elseif ($spec_attr['moded'] == 'h-seckill') {
                            $goods_list[$key]['url'] = $this->dscRepository->buildUri('seckill', ['act' => "view", 'secid' => $val['id']], $val['goods_name']);
                            $goods_list[$key]['list_url'] = $this->dscRepository->buildUri('seckill', ['act' => "list", 'secid' => $val['id']], $val['goods_name']);
                        } else {
                            $goods_list[$key]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $val['goods_id']], $val['goods_name']);
                            $goods_list[$key]['formated_end_date'] = local_date($GLOBALS['_CFG']['time_format'], $val['promote_end_date']);
                        }
                    }
                }
                $this->smarty->assign("recommend", $recommend);
            }
            $result['spec_attr'] = $spec_attr;
            $spec_attr['goods_list'] = $goods_list;
            $this->smarty->assign('goods_count', count($goods_list));
            $this->smarty->assign("temp", $spec_attr['moded']);
            $this->smarty->assign("suffix", $spec_attr['suffixed']);
            $this->smarty->assign("spec_attr", $spec_attr);
            $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
            $result['goods_ids'] = $spec_attr['goods_ids'];
            return response()->json($result);
        } //专题导航
        elseif ($_REQUEST['act'] == 'navInsert') {
            load_helper('goods');
            $result = [];
            $result['mode'] = !empty($_REQUEST['mode']) ? trim($_REQUEST['mode']) : '';
            $navname = !empty($_REQUEST['navname']) ? $_REQUEST['navname'] : '';
            $navurl = !empty($_REQUEST['navurl']) ? $_REQUEST['navurl'] : '';
            $navvieworder = !empty($_REQUEST['navvieworder']) ? $_REQUEST['navvieworder'] : '';
            $adminpath = !empty($_REQUEST['adminpath']) ? trim($_REQUEST['adminpath']) : '';
            $count = COUNT($navname);//数组长度
            /*合并数组*/
            $arr = [];
            $sort_vals = [];
            for ($i = 0; $i < $count; $i++) {
                $arr[$i]['name'] = trim($navname[$i]);
                $arr[$i]['url'] = $navurl[$i];
                $arr[$i]['opennew'] = 1;
                $arr[$i]['navvieworder'] = isset($navvieworder[$i]) ? intval($navvieworder[$i]) : 0;
                $sort_vals[$i] = isset($navvieworder[$i]) ? intval($navvieworder[$i]) : 0;
            }

            if (!empty($arr)) {
                array_multisort($sort_vals, SORT_ASC, $arr);//数组重新排序
            }
            $this->smarty->assign('navigator', $arr);
            if ($adminpath == 'admin') {
                $this->smarty->assign('temp', 'navigator');
            } else {
                $this->smarty->assign('temp', 'navigator_home');
            }
            $result['spec_attr'] = $arr;
            $result['content'] = $this->smarty->fetch('library/dialog.lbi');
            return response()->json($result);
        }
        /* ------------------------------------------------------ */
        //-- 图片库商品上传图片
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'lib_upload_img') {
            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);
            load_helper('goods', 'admin');

            $act_type = empty($_REQUEST['type']) ? '' : trim($_REQUEST['type']);
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

            $result = ['error' => 0, 'pic' => '', 'name' => ''];
            $typeArr = ["jpg", "png", "gif", "jpeg"]; //允许上传文件格式

            if (isset($_POST)) {
                $name = $_FILES['file']['name'];
                $size = $_FILES['file']['size'];
                $name_tmp = $_FILES['file']['tmp_name'];
                if (empty($name)) {
                    $result['error'] = $GLOBALS['_LANG']['not_select_img'];
                }
                $type = strtolower(substr(strrchr($name, '.'), 1)); //获取文件类型
                if (!in_array($type, $typeArr)) {
                    $result['error'] = $GLOBALS['_LANG']['cat_prompt_file_type'];
                }
            }

            $upload_status = 0;

            if ($act_type == 'goods_img') {
                /* 开始处理 start */
                $_FILES['goods_img'] = $_FILES['file'];
                $proc_thumb = (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0) ? false : true;
                $_POST['auto_thumb'] = 1; //自动生成缩略图
                $_REQUEST['goods_id'] = $id;
                $goods_id = $id;
                /* 开始处理 end */

                /* 处理商品图片 */
                $goods_img = '';  // 初始化商品图片
                $goods_thumb = '';  // 初始化商品缩略图
                $original_img = '';  // 初始化原始图片
                $old_original_img = '';  // 初始化原始图片旧图

                // 如果上传了商品图片，相应处理
                if ($_FILES['goods_img']['tmp_name'] != '' && $_FILES['goods_img']['tmp_name'] != 'none') {
                    if (empty($is_url_goods_img)) {
                        $original_img = $image->upload_image($_FILES['goods_img'], ['type' => 1]); // 原始图片
                        $original_img = storage_public($original_img);
                    }

                    $goods_img = $original_img;   // 商品图片

                    /* 复制一份相册图片 */
                    /* 添加判断是否自动生成相册图片 */
                    if ($GLOBALS['_CFG']['auto_generate_gallery']) {
                        $img = $original_img;   // 相册图片
                        $pos = strpos(basename($img), '.');
                        $newname = dirname($img) . '/' . $image->random_filename() . substr(basename($img), $pos);
                        copy($img, $newname);
                        $img = $newname;
                        $gallery_img = $img;
                        $gallery_thumb = $img;
                    }

                    // 如果系统支持GD，缩放商品图片，且给商品图片和相册图片加水印
                    if ($proc_thumb && $image->gd_version() > 0 && $image->check_img_function($_FILES['goods_img']['type']) || $is_url_goods_img) {
                        if (empty($is_url_goods_img)) {
                            // 如果设置大小不为0，缩放图片
                            if ($GLOBALS['_CFG']['image_width'] != 0 || $GLOBALS['_CFG']['image_height'] != 0) {
                                $goods_img = $image->make_thumb(['img' => $goods_img, 'type' => 1], $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height']);
                            }

                            /* 添加判断是否自动生成相册图片 */
                            if ($GLOBALS['_CFG']['auto_generate_gallery']) {
                                $newname = dirname($img) . '/' . $image->random_filename() . substr(basename($img), $pos);
                                copy($img, $newname);
                                $gallery_img = $newname;
                            }

                            // 加水印
                            if (intval($GLOBALS['_CFG']['watermark_place']) > 0 && !empty($GLOBALS['_CFG']['watermark'])) {
                                if ($image->add_watermark($goods_img, '', $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']) === false) {
                                    return sys_msg($image->error_msg(), 1, [], false);
                                }
                                /* 添加判断是否自动生成相册图片 */
                                if ($GLOBALS['_CFG']['auto_generate_gallery']) {
                                    if ($image->add_watermark($gallery_img, '', $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']) === false) {
                                        return sys_msg($image->error_msg(), 1, [], false);
                                    }
                                }
                            }
                        }

                        // 相册缩略图
                        /* 添加判断是否自动生成相册图片 */
                        if ($GLOBALS['_CFG']['auto_generate_gallery']) {
                            if ($GLOBALS['_CFG']['thumb_width'] != 0 || $GLOBALS['_CFG']['thumb_height'] != 0) {
                                $gallery_thumb = $image->make_thumb(['img' => $img, 'type' => 1], $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height']);
                            }
                        }
                    }
                }


                // 是否上传商品缩略图
                if (isset($_FILES['goods_thumb']) && $_FILES['goods_thumb']['tmp_name'] != '' &&
                    isset($_FILES['goods_thumb']['tmp_name']) && $_FILES['goods_thumb']['tmp_name'] != 'none') {
                    // 上传了，直接使用，原始大小
                    $goods_thumb = $image->upload_image($_FILES['goods_thumb'], ['type' => 1]);
                } else {
                    // 未上传，如果自动选择生成，且上传了商品图片，生成所略图
                    if ($proc_thumb && isset($_POST['auto_thumb']) && !empty($original_img)) {
                        // 如果设置缩略图大小不为0，生成缩略图
                        if ($GLOBALS['_CFG']['thumb_width'] != 0 || $GLOBALS['_CFG']['thumb_height'] != 0) {
                            $goods_thumb = $image->make_thumb(['img' => $original_img, 'type' => 1], $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height']);
                        } else {
                            $goods_thumb = $original_img;
                        }
                    }
                }

                /* 重新格式化图片名称 */
                $original_img = $this->goodsManageService->reformatImageName('goods', $goods_id, $original_img, 'source');
                $goods_img = $this->goodsManageService->reformatImageName('goods', $goods_id, $goods_img, 'goods');
                $goods_thumb = $this->goodsManageService->reformatImageName('goods_thumb', $goods_id, $goods_thumb, 'thumb');
                //将数据保存返回 by wu
                $result['data'] = [
                    'original_img' => $original_img,
                    'goods_img' => $goods_img,
                    'goods_thumb' => $goods_thumb
                ];

                if (empty($goods_id)) {
                    session()->put('goods_lib.' . $admin_id . '.' . $goods_id, $result['data']);
                } else {
                    lib_get_del_edit_goods_img($goods_id);
                    GoodsLib::where('goods_id', $goods_id)->update($result['data']);
                }

                $this->dscRepository->getOssAddFile($result['data']);

                /* 如果有图片，把商品图片加入图片相册 */
                if ($img) {
                    /* 重新格式化图片名称 */
                    if (empty($is_url_goods_img)) {
                        $img = $this->goodsManageService->reformatImageName('gallery', $goods_id, $img, 'source');
                        $gallery_img = $this->goodsManageService->reformatImageName('gallery', $goods_id, $gallery_img, 'goods');
                    } else {
                        $img = $original_img;
                        $gallery_img = $original_img;
                    }

                    $gallery_thumb = $this->goodsManageService->reformatImageName('gallery_thumb', $goods_id, $gallery_thumb, 'thumb');

                    $gallery_count = $this->goodsManageService->getGoodsGalleryCount($goods_id);
                    $img_desc = $gallery_count + 1;

                    $data = [
                        'goods_id' => $goods_id,
                        'img_url' => $gallery_img,
                        'img_desc' => $img_desc,
                        'thumb_url' => $gallery_thumb,
                        'img_original' => $img
                    ];
                    $thumb_img_id[] = GoodsLibGallery::insertGetId($data);

                    $this->dscRepository->getOssAddFile([$gallery_img, $gallery_thumb, $img]);
                    if (!empty(session('thumb_img_id' . session('admin_id')))) {
                        $thumb_img_id = array_merge($thumb_img_id, session('thumb_img_id' . session('admin_id')));
                    }

                    session([
                        'thumb_img_id' . session('admin_id') => $thumb_img_id
                    ]);

                    $result['img_desc'] = $img_desc;
                }

                /* 结束处理 start */
                $pic_name = "";

                $goods_img = get_image_path($goods_img);

                $pic_url = $goods_img;

                $upload_status = 1;
                /* 结束处理 end */
            } elseif ($act_type == 'gallery_img') {
                /* 开始处理 start */
                $_FILES['img_url'] = [
                    'name' => [$_FILES['file']['name']],
                    'type' => [$_FILES['file']['type']],
                    'tmp_name' => [$_FILES['file']['tmp_name']],
                    'error' => [$_FILES['file']['error']],
                    'size' => [$_FILES['file']['size']]
                ];
                $_REQUEST['goods_id_img'] = $id;
                $_REQUEST['img_desc'] = [['']];
                $_REQUEST['img_file'] = [['']];
                /* 开始处理 end */

                $goods_id = !empty($_REQUEST['goods_id_img']) ? intval($_REQUEST['goods_id_img']) : 0;
                $img_desc = !empty($_REQUEST['img_desc']) ? $_REQUEST['img_desc'] : [];
                $img_file = !empty($_REQUEST['img_file']) ? $_REQUEST['img_file'] : [];
                $php_maxsize = ini_get('upload_max_filesize');
                $htm_maxsize = '2M';
                if ($_FILES['img_url']) {
                    foreach ($_FILES['img_url']['error'] as $key => $value) {
                        if ($value == 0) {
                            if (!$image->check_img_type($_FILES['img_url']['type'][$key])) {
                                $result['error'] = '1';
                                $result['massege'] = sprintf($GLOBALS['_LANG']['invalid_img_url'], $key + 1);
                            } else {
                                $goods_pre = 1;
                            }
                        } elseif ($value == 1) {
                            $result['error'] = '1';
                            $result['massege'] = sprintf($GLOBALS['_LANG']['img_url_too_big'], $key + 1, $php_maxsize);
                        } elseif ($_FILES['img_url']['error'] == 2) {
                            $result['error'] = '1';
                            $result['massege'] = sprintf($GLOBALS['_LANG']['img_url_too_big'], $key + 1, $htm_maxsize);
                        }
                    }
                }

                clear_cache_files();

                /* 格式化相册图片路径 */
                foreach ($img_list as $key => $gallery_img) {
                    $gallery_img['thumb_url'] = get_image_path($gallery_img['goods_id'], $gallery_img['thumb_url'], true);
                    $img_list[$key]['thumb_url'] = $gallery_img['thumb_url'];
                }

                $goods['goods_id'] = $goods_id;
                $this->smarty->assign('img_list', $img_list);
                $img_desc = [];
                foreach ($img_list as $k => $v) {
                    $img_desc[] = $v['img_desc'];
                }
                $img_default = min($img_desc);
                $this->smarty->assign('goods', $goods);

                $upload_status = 1;
                $result['external_url'] = "";
            }

            if ($upload_status) { //临时文件转移到目标文件夹
                $result['error'] = 0;
                $result['pic'] = $pic_url;
                $result['name'] = $pic_name;
            } else {
                $result['error'] = $GLOBALS['_LANG']['upload_error_server_set'];
            }
            return response()->json($result);
        }
        /* ------------------------------------------------------ */
        //-- 获取选择商品内        容模板
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'getsearchgoodsDiv') {
            $result = [];
            $goods_ids = !empty($_REQUEST['goods_ids']) ? trim($_REQUEST['goods_ids']) : '';
            $pbtype = !empty($_REQUEST['pbtype']) ? trim($_REQUEST['pbtype']) : '';
            $ru_id = !empty($_REQUEST['ru_id']) ? intval($_REQUEST['ru_id']) : 0;
            $goods_list = [];
            $back_goods = '';

            if ($goods_ids) {
                $goods_ids = $this->baseRepository->getExplode($goods_ids);
                $res = Goods::select('goods_name', 'goods_id');
                $res = $res->where('is_delete', 0)
                    ->whereIn('goods_id', $goods_ids)
                    ->where('user_id', $ru_id);

                //ecmoban模板堂 --zhuo start
                if ($GLOBALS['_CFG']['review_goods'] == 1) {
                    $res = $res->where('review_status', '>', 2);
                }

                $goods_list = $this->baseRepository->getToArrayGet($res);

                //重定义goods_id
                if (!empty($goods_list)) {
                    foreach ($goods_list as $k) {
                        if ($back_goods) {
                            $back_goods .= "," . $k['goods_id'];
                        } else {
                            $back_goods .= $k['goods_id'];
                        }
                    }
                }
            }

            $this->smarty->assign('goods_list', $goods_list);
            $this->smarty->assign('pbtype', $pbtype);
            $result['back_goods'] = $back_goods;
            $result['content'] = $this->smarty->fetch('library/getsearchgoodsdiv.lbi');
            return response()->json($result);
        }
        /* ------------------------------------------------------ */
        //-- 添加属性分类
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_goods_type_cat') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $cat_name = !empty($_REQUEST['cat_name']) ? trim($_REQUEST['cat_name']) : '';
            $parent_id = !empty($_REQUEST['attr_parent_id']) ? intval($_REQUEST['attr_parent_id']) : 0;
            $sort_order = !empty($_REQUEST['sort_order']) ? intval($_REQUEST['sort_order']) : 50;
            $user_id = !empty($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : $adminru['ru_id'];
            //获取入库分类的层级

            if ($parent_id > 0) {
                $level = GoodsTypeCat::where('cat_id', $parent_id)->value('level');
                $level = $level ? $level : 0;
                $level = $level + 1;
            } else {
                $level = 1;
            }
            //处理入库数组
            $cat_info = [
                'cat_name' => $cat_name,
                'parent_id' => $parent_id,
                'level' => $level,
                'user_id' => $user_id,
                'sort_order' => $sort_order
            ];
            /*检查是否重复*/
            $is_only = GoodsTypeCat::where('cat_name', $cat_name)->where('user_id', $user_id)->count();
            if ($is_only > 0) {
                $result['error'] = 1;
                $result['message'] = sprintf($GLOBALS['_LANG']['exist_cat'], stripslashes($cat_name));
                return response()->json($result);
            }

            $cat_id = GoodsTypeCat::insertGetId($cat_info);

            //获取分类数组
            $type_level = get_type_cat_arr(0, 0, 0, $user_id);
            $this->smarty->assign('type_level', $type_level);
            $cat_tree = get_type_cat_arr($cat_id, 2, 0, $user_id);
            $cat_tree1 = ['checked_id' => $cat_tree['checked_id']];

            if ($cat_tree['checked_id'] > 0) {
                $cat_tree1 = get_type_cat_arr($cat_tree['checked_id'], 2, 0, $user_id);
            }
            $this->smarty->assign("type_c_id", $cat_id);
            $this->smarty->assign("cat_tree", $cat_tree);
            $this->smarty->assign("cat_tree1", $cat_tree1);
            $result['cat_id'] = $cat_id;
            $result['content'] = $this->smarty->fetch('library/type_cat_list.lbi');
            return response()->json($result);
        }
        /* ------------------------------------------------------ */
        //-- 商品类型�        �库
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_goods_type') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $parent_id = !empty($_REQUEST['attr_parent_id']) ? intval($_REQUEST['attr_parent_id']) : 0;
            $goods_type['cat_name'] = $this->dscRepository->subStr($_POST['cat_name'], 60);
            $goods_type['attr_group'] = $this->dscRepository->subStr($_POST['attr_group'], 255);
            $goods_type['user_id'] = !empty($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : $adminru['ru_id'];
            $goods_type['c_id'] = $parent_id;

            $result['type_id'] = GoodsType::insertGetId($goods_type);

            $result['cat_id'] = $parent_id;

            //获取分类数组
            $type_level = get_type_cat_arr(0, 0, 0, $goods_type['user_id']);
            $this->smarty->assign('type_level', $type_level);
            $cat_tree = get_type_cat_arr($parent_id, 2, 0, $goods_type['user_id']);
            $cat_tree1 = ['checked_id' => $cat_tree['checked_id']];

            if ($cat_tree['checked_id'] > 0) {
                $cat_tree1 = get_type_cat_arr($cat_tree['checked_id'], 2, 0, $goods_type['user_id']);
            }
            $this->smarty->assign("type_c_id", $parent_id);
            $this->smarty->assign("cat_tree", $cat_tree);
            $this->smarty->assign("cat_tree1", $cat_tree1);

            $result['content'] = $this->smarty->fetch('library/type_cat_list.lbi');
            return response()->json($result);
        }
        /* ------------------------------------------------------ */
        //-- 商品类型�        �库
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'attribute_add') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $cat_id = isset($_REQUEST['cat_id']) && !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;
            $sort_order = isset($_POST['sort_order']) && !empty($_POST['sort_order']) ? $_POST['sort_order'] : 0;
            $attr_name = isset($_POST['attr_name']) && !empty($_POST['attr_name']) ? trim($_POST['attr_name']) : '';

            /* 检查名称是否重复 */
            $exclude = empty($_POST['attr_id']) ? 0 : intval($_POST['attr_id']);
            $is_only = Attribute::where('attr_name', $attr_name)
                ->where('attr_id', '<>', $exclude)
                ->where('cat_id', $cat_id)
                ->count();
            if ($is_only > 0) {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['attr_name_repeat'];
                return response()->json($result);
            }
            /* 取得属性信息 */
            $attr = [
                'cat_id' => $cat_id,
                'attr_name' => $attr_name,
                'attr_cat_type' => $_POST['attr_cat_type'], //by zhang
                'attr_index' => $_POST['attr_index'],
                'sort_order' => $sort_order,
                'attr_input_type' => $_POST['attr_input_type'],
                'is_linked' => $_POST['is_linked'],
                'attr_values' => isset($_POST['attr_values']) ? $_POST['attr_values'] : '',
                'attr_type' => empty($_POST['attr_type']) ? '0' : intval($_POST['attr_type']),
                'attr_group' => isset($_POST['attr_group']) ? intval($_POST['attr_group']) : 0
            ];

            $attr_id = Attribute::insertGetId($attr);

            $sort = Attribute::selectRaw('MAX(sort_order) AS sort')->where('cat_id', $cat_id)->value('sort');
            $sort = $sort ? $sort : 0;
            if (empty($attr['sort_order']) && !empty($sort)) {
                $attr = [
                    'sort_order' => $attr_id,
                ];
                Attribute::where('attr_id', $attr_id)->update($attr);
            }
            $result['type_id'] = $cat_id;
            admin_log($attr_name, 'add', 'attribute');

            return response()->json($result);
        }
    }

    /**
     * 获取品牌列表
     *
     * @access  public
     * @return  array
     */
    private function getBrandList($filters)
    {
        $cat_id = !empty($filters->cat_id) ? intval($filters->cat_id) : 0;
        $keyword = !empty($filters->keyword) ? trim($filters->keyword) : '';
        $brand_id = !empty($filters->brand_id) ? intval($filters->brand_id) : 0;

        $children = $this->categoryService->catList($cat_id, 1);
        $children = arr_foreach($children);

        if ($children) {
            $children = implode(",", $children) . "," . $cat_id;
            $children = get_children($children, 0, 1);
        } else {
            $children = "g.cat_id IN ($cat_id)";
        }

        $where = '1';
        if (!empty($keyword)) {
            if (strtoupper(EC_CHARSET) == 'GBK') {
                $keyword = iconv("UTF-8", "gb2312", $keyword);
            }

            $where .= " AND brand_name like '%$keyword%'";
        }

        if (!empty($brand_id)) {
            $where .= " AND b.brand_id = '$brand_id' ";
        } else {

            /**
             * 当前分类下的所有子分类
             * 返回一维数组
             */
            $cat_keys = $this->categoryService->getArrayKeysCat($cat_id);

            $where .= " AND $children OR " . 'gc.cat_id ' . db_create_in(array_unique(array_merge([$cat_id], $cat_keys)));
        }

        /* 获取分类下平台品牌 */
        $sql = "SELECT b.brand_id, b.brand_name,b.brand_logo, COUNT(*) AS goods_num " .
            "FROM " . $this->dsc->table('brand') . "AS b " .
            " LEFT JOIN " . $this->dsc->table('goods') . " AS g ON g.brand_id = b.brand_id AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 " .
            " LEFT JOIN " . $this->dsc->table('goods_cat') . " AS gc ON g.goods_id = gc.goods_id " .
            " WHERE $where AND b.is_show = 1 " .
            "GROUP BY b.brand_id HAVING goods_num > 0 ORDER BY b.sort_order, b.brand_id ASC";
        $brands_list = $this->db->getAll($sql);

        $brands = [];
        foreach ($brands_list as $key => $val) {
            $brands[$key]['brand_id'] = $val['brand_id'];
            $brands[$key]['brand_name'] = $val['brand_name'];
            $brands[$key]['brand_logo'] = $val['brand_logo'];
        }

        return $brands;
    }
}
