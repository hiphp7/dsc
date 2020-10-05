<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Repositories\Common\DscRepository;
use App\Services\Article\ArticleCommonService;
use App\Services\Brand\BrandService;
use App\Services\Category\CategoryAttributeService;
use App\Services\Category\CategoryBrandService;
use App\Services\Category\CategoryGoodsService;
use App\Services\Category\CategoryService;
use App\Services\Goods\GoodsGalleryService;
use App\Services\Goods\GoodsGuessService;
use App\Services\User\UserCommonService;

/**
 * 商品分类
 */
class CategoryController extends InitController
{
    protected $brandService;
    protected $categoryService;
    protected $dscRepository;
    protected $categoryGoodsService;
    protected $categoryBrandService;
    protected $categoryAttributeService;
    protected $userCommonService;
    protected $goodsGalleryService;
    protected $config;
    protected $articleCommonService;
    protected $goodsGuessService;

    public function __construct(
        BrandService $brandService,
        CategoryService $categoryService,
        DscRepository $dscRepository,
        CategoryGoodsService $categoryGoodsService,
        CategoryBrandService $categoryBrandService,
        CategoryAttributeService $categoryAttributeService,
        UserCommonService $userCommonService,
        GoodsGalleryService $goodsGalleryService,
        ArticleCommonService $articleCommonService,
        GoodsGuessService $goodsGuessService
    )
    {
        $this->brandService = $brandService;
        $this->categoryService = $categoryService;
        $this->dscRepository = $dscRepository;
        $this->categoryGoodsService = $categoryGoodsService;
        $this->categoryBrandService = $categoryBrandService;
        $this->categoryAttributeService = $categoryAttributeService;
        $this->userCommonService = $userCommonService;
        $this->goodsGalleryService = $goodsGalleryService;
        $this->config = $this->config();
        $this->articleCommonService = $articleCommonService;
        $this->goodsGuessService = $goodsGuessService;
    }

    public function index()
    {
        load_helper('order');

        $this->dscRepository->helpersLang(['user']);

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

        $user_id = session('user_id', 0);

        /* ------------------------------------------------------ */
        //-- INPUT
        /* ------------------------------------------------------ */

        /* 获得请求的分类 ID */
        if (request()->exists('id')) {
            $cat_id = intval(request()->input('id'));
        } elseif (request()->exists('category')) {
            $cat_id = intval(request()->input('category'));
        } else {
            $cat_id = 0;
        }

        if ($cat_id) {
            $Loaction = 'mobile#/list/' . $cat_id;
        } else {
            $Loaction = 'mobile#/catalog';
        }

        /* 跳转H5 start */
        $uachar = $this->dscRepository->getReturnMobile($Loaction);

        if ($uachar) {
            return $uachar;
        } else {
            if ($cat_id <= 0) {
                /* 如果分类ID为0，则返回首页 */
                return dsc_header("Location: ./\n");
            }
        }
        /* 跳转H5 end */

        // Check Referer
        if ($this->checkReferer() === false && request()->has('script_name')) {
            return redirect('/');
        }

        /* 初始化分页信息 */
        $act = addslashes(trim(request()->input('act', '')));
        $page = intval(request()->input('page', 1));

        $size = isset($this->config['page_size']) && intval($this->config['page_size']) > 0 ? intval($this->config['page_size']) : 10;
        //过滤品牌参数
        $brands_id = $this->dsc->get_explode_filter((request()->input('brand', '')));

        //by wang
        $ship = intval(request()->input('ship'));
        $self = intval(request()->input('self'));
        $have = intval(request()->input('have'));

        $price_max = intval(request()->input('price_max'));
        $price_min = intval(request()->input('price_min'));

        $filter_attr_str = addslashes(trim(request()->input('filter_attr', 0)));

        $filter_attr_str = trim(urldecode($filter_attr_str));
        $filter_attr_str = preg_match('/^[\d,\.]+$/', $filter_attr_str) ? $filter_attr_str : ''; //by zhang

        $filter_attr = empty($filter_attr_str) ? '' : explode('.', $filter_attr_str);

        /* 排序、显示方式以及类型 */
        $default_display_type = $this->config['show_order_type'] == '0' ? 'list' : ($this->config['show_order_type'] == '1' ? 'grid' : 'text');
        $default_sort_order_method = $this->config['sort_order_method'] == '0' ? 'DESC' : 'ASC';
        $default_sort_order_type = $this->config['sort_order_type'] == '0' ? 'goods_id' : ($this->config['sort_order_type'] == '1' ? 'shop_price' : 'last_update');

        $sort = $default_sort_order_type;
        if (request()->exists('sort')) {
            $get_sort = request()->input('sort');
            if (in_array(trim(strtolower($get_sort)), ['goods_id', 'shop_price', 'last_update', 'sales_volume', 'comments_number'])) {
                $sort = $get_sort;
            }
        }

        $order = $default_sort_order_method;
        if (request()->exists('order')) {
            $get_order = request()->input('order');
            if (in_array(trim(strtoupper($get_order)), ['ASC', 'DESC'])) {
                $order = $get_order;
            }
        }

        $ecsCookie = request()->cookie('ECS');
        $display = isset($ecsCookie['display']) ? $ecsCookie['display'] : $default_display_type;
        if (request()->exists('display')) {
            $get_display = request()->input('display');
            if (in_array(trim(strtolower($get_display)), ['list', 'grid', 'text'])) {
                $display = $get_display;
            }
        }
        $display = in_array($display, ['list', 'grid', 'text']) ? $display : 'text';

        cookie()->queue('ECS[display]', $display, 60 * 24 * 7);

        $where_ext = [
            'self' => $self,
            'have' => $have,
            'ship' => $ship
        ];

        /* ------------------------------------------------------ */
        //-- PROCESSOR
        /* ------------------------------------------------------ */

        $this->smarty->assign('category', $cat_id);
        $this->smarty->assign('rewrite', $this->config['rewrite']);

        //是否开启页面区分地区商品
        $this->smarty->assign('open_area_goods', $this->config['open_area_goods']);

        //判断是否顶级分类调用不同模板
        $cat_row = Category::catInfo($cat_id)->first();
        $cat_row = $cat_row ? $cat_row->toArray() : [];

        if (empty($cat_row)) {
            return redirect()->route('home');
        }

        $cat_row['parent_id'] = isset($cat_row['parent_id']) ? $cat_row['parent_id'] : 0;
        $cat_row['keywords'] = isset($cat_row['keywords']) ? $cat_row['keywords'] : '';
        $cat_row['cat_desc'] = isset($cat_row['cat_desc']) ? $cat_row['cat_desc'] : '';
        $cat_row['style'] = isset($cat_row['style']) ? $cat_row['style'] : '';
        $cat_row['grade'] = isset($cat_row['grade']) ? $cat_row['grade'] : 0;
        $cat_row['cat_name'] = isset($cat_row['cat_name']) ? $cat_row['cat_name'] : '';
        $cat_row['filter_attr'] = isset($cat_row['filter_attr']) ? $cat_row['filter_attr'] : 0;
        $cat_row['top_style_tpl'] = isset($cat_row['top_style_tpl']) ? $cat_row['top_style_tpl'] : 0;

        if (request()->exists('brand')) {
            $cat_row['is_top_style'] = 0;
        }

        $category_top_banner = '';
        $top_style_elec_brand = '';
        $top_style_elec_banner = '';
        $top_style_food_banner = '';
        $top_style_food_hot = '';
        $category_top_default_brand = '';
        $category_top_default_best_head = '';
        $category_top_default_new_head = '';
        $category_top_default_best_left = '';
        $category_top_default_new_left = '';
        if ($cat_row['parent_id'] == 0 && $cat_row['is_top_style'] == 1) {
            for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                $category_top_banner .= "'category_top_banner" . $i . ","; //轮播图
                $top_style_elec_brand .= "'top_style_elec_brand" . $i . ","; //顶级分类品牌广告(家电模板) by wu
                $top_style_elec_banner .= "'top_style_elec_banner" . $i . ","; //顶级分类轮播广告(家电模板) by wu
                $top_style_food_banner .= "'top_style_food_banner" . $i . ","; //顶级分类轮播广告(食品模板) by wu
                $top_style_food_hot .= "'top_style_food_hot" . $i . ","; //顶级分类热门广告(食品模板) by wu

                $category_top_default_brand .= "'category_top_default_brand" . $i . ","; //顶级分类页（默认）品牌旗舰 by wu
                $category_top_default_best_head .= "'category_top_default_best_head" . $i . ","; //顶级分类页（默认）今日推荐头部广告 by wu
                $category_top_default_new_head .= "'category_top_default_new_head" . $i . ","; //顶级分类页（默认）新品到货头部广告 by wu
                $category_top_default_best_left .= "'category_top_default_best_left" . $i . ","; //顶级分类页（默认）今日推荐左侧广告 by wu
                $category_top_default_new_left .= "'category_top_default_new_left" . $i . ","; //顶级分类页（默认）新品到货左侧广告 by wu
            }

            $this->smarty->assign('category_top_banner', $category_top_banner);
            $this->smarty->assign('top_style_elec_brand', $top_style_elec_brand);
            $this->smarty->assign('top_style_elec_banner', $top_style_elec_banner);
            $this->smarty->assign('top_style_food_banner', $top_style_food_banner);
            $this->smarty->assign('top_style_food_hot', $top_style_food_hot);

            //顶级分类页顶部横幅广告(家电模板) by wu
            $top_style_elec_foot = "'top_style_elec_foot,";
            $this->smarty->assign('top_style_elec_foot', $top_style_elec_foot);

            $this->smarty->assign('category_top_default_brand', $category_top_default_brand);
            $this->smarty->assign('category_top_default_best_head', $category_top_default_best_head);
            $this->smarty->assign('category_top_default_new_head', $category_top_default_new_head);
            $this->smarty->assign('category_top_default_best_left', $category_top_default_best_left);
            $this->smarty->assign('category_top_default_new_left', $category_top_default_new_left);

            $dwt_name = 'category_top';
        } else {
            $category_top_ad = '';
            for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                $category_top_ad .= "'category_top_ad" . $i . ",";
            }

            $this->smarty->assign('category_top_ad', $category_top_ad);

            $dwt_name = 'category';
        }

        /* 瀑布流 */
        if ($act == 'load_more_goods') {
            $seconds = 0;
        } else {
            $seconds = $this->config['cache_time'];
        }

        /* 页面的缓存ID */
        $cache_id = sprintf('%X', crc32($cat_id . '-' . $display . '-' . $sort . '-' . $order . '-' . $page . '-' . $size . '-' . session('user_rank', 0) . '-' .
            $this->config['lang'] . '-' . $brands_id . '-' . $price_max . '-' . $price_min . '-' . $filter_attr_str . '-' . $ship . '-' . $self . '-' . $have . '-' .
            $warehouse_id . '-' . $area_id . '-' . $area_city));

        $content = cache()->remember($dwt_name . '.dwt.' . $cache_id, $seconds, function () use ($cat_id, $cat_row, $dwt_name, $warehouse_id, $area_id, $area_city, $where_ext, $brands_id, $price_min, $price_max, $display, $user_id, $ship, $self, $have, $filter_attr, $filter_attr_str, $size, $page, $sort, $order) {

            $this->smarty->assign('cate_info', $cat_row);
            $this->smarty->assign('parent_id', $cat_row['parent_id']);

            //瀑布流加载分类商品 by wu start
            $this->smarty->assign('category_load_type', $this->config['category_load_type']);
            $this->smarty->assign('query_string', request()->server('QUERY_STRING'));

            $goods_num = intval(request()->input('goods_num'));
            $best_num = intval(request()->input('best_num'));

            $act = addslashes(trim(request()->input('act', '')));
            if ($act == 'load_more_goods') {
                $goods_floor = floor($goods_num / 4 * 8 / 5 - $best_num);

                if ($goods_floor < 0) {
                    $best_size = $best_num;
                } else {
                    $best_size = $goods_floor + 2;
                }
            } else {
                $best_num = 0;
                $best_size = 6;
            }

            $cat = $cat_row;

            if (!empty($brands_id)) { // by zhang
                $brand_info = $this->brandService->getBrandInfo($brands_id);
                $brand_name = $brand_info['brand_name'];
            } else {
                $brand_name = '';
            }

            $position = assign_ur_here($cat_id, $brand_name);

            $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置

            //获取seo start
            $seo = get_category_seo_words($cat_id);

            foreach ($seo as $key => $value) {
                $seo[$key] = str_replace(['{sitename}', '{name}'], [$this->config['shop_name'], $cat_row['cat_name']], $value);
            }

            if (!empty($seo['cate_keywords'])) {
                $this->smarty->assign('keywords', htmlspecialchars($seo['cate_keywords']));
            } else {
                $this->smarty->assign('keywords', htmlspecialchars($cat['keywords']));
            }

            if (!empty($seo['cate_description'])) {
                $this->smarty->assign('description', htmlspecialchars($seo['cate_description']));
            } else {
                $this->smarty->assign('description', htmlspecialchars($cat['cat_desc']));
            }

            if (!empty($seo['cate_title'])) {
                $this->smarty->assign('page_title', htmlspecialchars($seo['cate_title']));
            } else {
                $this->smarty->assign('page_title', $position['title']);
            }
            //获取seo end

            /* 如果页面没有被缓存则重新获取页面的内容 */
            $children = $this->categoryService->getCatListChildren($cat_id);

            if (!empty($cat)) {
                $this->smarty->assign('keywords', htmlspecialchars($cat['keywords']));
                $this->smarty->assign('description', htmlspecialchars($cat['cat_desc']));
                $this->smarty->assign('cat_style', htmlspecialchars($cat['style']));
            } else {
                /* 如果分类不存在则返回首页 */
                return dsc_header("Location: ./\n");
            }

            /* 获取价格分级 */
            if ($cat['grade'] == 0 && $cat['parent_id'] != 0) {
                $cat['grade'] = $this->categoryService->getParentGrade($cat_id); //如果当前分类级别为空，取最近的上级分类
            }

            //ecmoban模板堂 --zhuo start
            if (($cat_row['parent_id'] == 0 && $cat_row['is_top_style'] == 1) == false) { //非顶级分类页和设置为顶级分类样式

                $price_grade = [];

                if ($cat['grade'] > 1) {
                    /* 需要价格分级 */

                    /*
                      算法思路：
                      1、当分级大于1时，进行价格分级
                      2、取出该类下商品价格的最大值、最小值
                      3、根据商品价格的最大值来计算商品价格的分级数量级：
                      价格范围(不含最大值)    分级数量级
                      0-0.1                   0.001
                      0.1-1                   0.01
                      1-10                    0.1
                      10-100                  1
                      100-1000                10
                      1000-10000              100
                      4、计算价格跨度：
                      取整((最大值-最小值) / (价格分级数) / 数量级) * 数量级
                      5、根据价格跨度计算价格范围区间
                      6、查询数据库

                      可能存在问题：
                      1、
                      由于价格跨度是由最大值、最小值计算出来的
                      然后再通过价格跨度来确定显示时的价格范围区间
                      所以可能会存在价格分级数量不正确的问题
                      该问题没有证明
                      2、
                      当价格=最大值时，分级会多出来，已被证明存在
                     */

                    //获得当前分类下商品价格的最大值、最小值
                    $row = $this->categoryGoodsService->getGoodsPriceMaxMin($children, $brands_id, $warehouse_id, $area_id, $area_city, $where_ext);

                    // 取得价格分级最小单位级数，比如，千元商品最小以100为级数
                    $price_grade = 0.0001;
                    for ($i = -2; $i <= log10($row['max']); $i++) {
                        $price_grade *= 10;
                    }

                    //跨度
                    $dx = ceil(($row['max'] - $row['min']) / ($cat['grade']) / $price_grade) * $price_grade;
                    if ($dx == 0) {
                        $dx = $price_grade;
                    }

                    for ($i = 1; $row['min'] > $dx * $i; $i++) ;

                    for ($j = 1; $row['min'] > $dx * ($i - 1) + $price_grade * $j; $j++) ;
                    $row['min'] = $dx * ($i - 1) + $price_grade * ($j - 1);

                    for (; $row['max'] >= $dx * $i; $i++) ;
                    $row['max'] = $dx * ($i) + $price_grade * ($j - 1);

                    $price_grade = $this->categoryGoodsService->getGoodsPriceGrade($row['list'], $row['min'], $dx);

                    if ($price_grade) {
                        foreach ($price_grade as $key => $val) {
                            if ($val['sn'] != '') {
                                $temp_key = $key;
                                $price_grade[$temp_key]['goods_num'] = $val['goods_num'];
                                $price_grade[$temp_key]['start'] = $row['min'] + round($dx * $val['sn']);
                                $price_grade[$temp_key]['end'] = $row['min'] + round($dx * ($val['sn'] + 1));
                                $price_grade[$temp_key]['price_range'] = $price_grade[$temp_key]['start'] . '&nbsp;-&nbsp;' . $price_grade[$temp_key]['end'];
                                $price_grade[$temp_key]['formated_start'] = price_format($price_grade[$temp_key]['start']);
                                $price_grade[$temp_key]['formated_end'] = price_format($price_grade[$temp_key]['end']);
                                $price_grade[$temp_key]['url'] = $this->dscRepository->buildUri('category', ['cid' => $cat_id, 'bid' => $brands_id, 'price_min' => $price_grade[$temp_key]['start'], 'price_max' => $price_grade[$temp_key]['end'], 'filter_attr' => $filter_attr_str], $cat['cat_name']);

                                /* 判断价格区间是否被选中 */
                                if (request()->exists('price_min') && $price_grade[$temp_key]['start'] == $price_min && $price_grade[$temp_key]['end'] == $price_max) {
                                    $price_grade[$temp_key]['selected'] = 1;
                                } else {
                                    $price_grade[$temp_key]['selected'] = 0;
                                }
                            }
                        }

                        if ($price_min == 0 && $price_max == 0) {
                            asort($price_grade);
                            $this->smarty->assign('price_grade', $price_grade);
                        }
                    }
                }

                /* 平台品牌筛选 */
                $brands_list = $this->categoryBrandService->getCatBrand($cat_id, $children, $warehouse_id, $area_id, $area_city);

                $brands = [];
                foreach ($brands_list as $key => $val) {
                    $temp_key = $key; //by zhang

                    $brands[$temp_key]['brand_id'] = $val['brand_id'];
                    $brands[$temp_key]['brand_name'] = $val['brand_name'];

                    $bdimg_path = "data/brandlogo/";                   // 图片路径
                    $bd_logo = $val['brand_logo'] ? $val['brand_logo'] : ""; // 图片名称
                    if (empty($bd_logo)) {
                        $brands[$temp_key]['brand_logo'] = "";           // 获取品牌图片
                    } else {
                        $brands[$temp_key]['brand_logo'] = get_image_path($bdimg_path . $bd_logo);
                    }

                    $brands[$temp_key]['brand_letters'] = $val['brand_first_char'];  //获取品牌字母

                    $brands[$temp_key]['url'] = $this->dscRepository->buildUri('category', ['cid' => $cat_id, 'bid' => $val['brand_id'], 'price_min' => $price_min, 'price_max' => $price_max, 'filter_attr' => $filter_attr_str], $cat['cat_name']);

                    /* 判断品牌是否被选中 */ // by zhang
                    if (!strpos($brands_id, ",") && $brands_id == $brands_list[$key]['brand_id']) {
                        $brands[$temp_key]['selected'] = 1;
                    }
                    if (stripos($brands_id, ",")) {
                        $brand2 = explode(",", $brands_id);
                        for ($i = 0; $i < count($brand2); $i++) {
                            if ($brand2[$i] == $brands_list[$key]['brand_id']) {
                                $brands[$temp_key]['selected'] = 1;
                            }
                        }
                    }
                }

                $ubrand = intval(request()->input('ubrand'));

                $this->smarty->assign('ubrand', $ubrand);
                //ecmoban模板堂 --zhuo end
                // 分配字母 by zhang start
                $letter = range('A', 'Z');
                $this->smarty->assign('letter', $letter);

                // 为0或没设置的时候 加载模板
                if ($brands) {
                    $this->smarty->assign('brands', $brands);
                }

                $get_bd = [];
                $bd = "";
                $br_url = '';
                foreach ($brands as $key => $value) {
                    if (isset($value['selected']) && $value['selected'] == 1) {
                        $bd .= $value['brand_name'] . ",";
                        $get_bd[$key]['brand_id'] = $value['brand_id'];

                        $get_bd[$key]['url'] = '';
                        if ($this->config['rewrite']) {
                            $brand_id = "b" . $get_bd[$key]['brand_id'];
                            if (stripos($value['url'], $brand_id)) {
                                $get_bd[$key]['url'] = str_replace($brand_id, "b0", $value['url']);
                            }
                        } else {
                            $brand_id = "brand=" . $get_bd[$key]['brand_id'];
                            if (stripos($value['url'], $brand_id)) {
                                $get_bd[$key]['url'] = str_replace($brand_id, "brand=0", $value['url']);
                            }
                        }
                        $br_url = $get_bd[$key]['url'];
                    }
                }

                $get_brand['br_url'] = $br_url;
                $get_brand['bd'] = substr($bd, 0, -1);

                $this->smarty->assign('get_bd', $get_brand);               // 品牌已选模块
                //by zhang end

                $g_price = [];       // 选中的价格
                for ($i = 0; $i < count($price_grade); $i++) {
                    if (isset($price_grade[$i]['selected']) && $price_grade[$i]['selected'] == 1) {
                        $g_price[$i]['price_range'] = $price_grade[$i]['price_range'];
                        $g_price[$i]['url'] = $price_grade[$i]['url'];
                        $p_url = $g_price[$i]['url'];

                        $p_a = intval(request()->input('price_min', 0));
                        $p_b = intval(request()->input('price_max', 0));

                        $p_a = (string)$p_a;
                        $p_b = (string)$p_b;

                        if (stripos($p_url, $p_a) && stripos($p_url, $p_b)) {
                            if ($p_a > $p_b) {
                                $price = [$p_a, $p_b];
                                $p_a = $price[1];
                                $p_b = $price[0];
                            }

                            if ($p_a > 0 && $p_b > 0) {
                                $g_price[$i]['url'] = str_replace($p_b, 0, str_replace($p_a, 0, $p_url));
                            } elseif ($p_a == 0 && $p_b > 0) {
                                $g_price[$i]['url'] = str_replace($p_b, 0, $p_url);
                            }
                        }

                        break;
                    }
                }
                // 处理交换价格

                if (empty($g_price) && ($price_min > 0 || $price_max > 0)) {
                    if ($price_min > $price_max) {
                        $price = [$price_min, $price_max];
                        $price_min = $price[1];
                        $price_max = $price[0];
                    }

                    $parray = [];

                    $parray['purl'] = $this->dscRepository->buildUri('category', ['cid' => $cat_id, 'bid' => $brands_id, 'price_min' => 0, 'price_max' => 0, 'filter_attr' => $filter_attr_str], $cat['cat_name']);
                    $parray['min_max'] = $price_min . " - " . $price_max;
                    $this->smarty->assign('parray', $parray);     // 自定义价格恢复
                }

                $this->smarty->assign('g_price', $g_price);              // 价格已选模块
                $cat_filter_attr = [];
                /* 属性筛选 */
                if ($cat['filter_attr'] > 0) {
                    $cat_filter_attr = explode(',', $cat['filter_attr']);       //提取出此分类的筛选属性
                    $all_attr_list = [];

                    foreach ($cat_filter_attr as $key => $value) {
                        $attributeInfo = $this->categoryAttributeService->getCatAttribute($value, $children, $warehouse_id, $area_id, $area_city);

                        if ($attributeInfo) {
                            $all_attr_list[$key]['filter_attr_name'] = $attributeInfo['attr_name'];
                            $all_attr_list[$key]['attr_cat_type'] = $attributeInfo['attr_cat_type'];

                            $all_attr_list[$key]['filter_attr_id'] = $value; //by zhang

                            $attr_list = $this->categoryAttributeService->getCatAttributeAttrList($value, $children, $warehouse_id, $area_id, $area_city);

                            $temp_arrt_url_arr = [];

                            for ($i = 0; $i < count($cat_filter_attr); $i++) {        //获取当前url中已选择属性的值，并保留在数组中
                                $temp_arrt_url_arr[$i] = !empty($filter_attr[$i]) ? $filter_attr[$i] : 0;
                            }

                            //by zhang start
                            foreach ($attr_list as $k => $v) {
                                $temp_key = $k;
                                $temp_arrt_url_arr[$key] = $v['goods_id'];           //为url中代表当前筛选属性的位置变量赋值,并生成以‘.’分隔的筛选属性字符串
                                $temp_arrt_url = implode('.', $temp_arrt_url_arr);

                                if (!empty($v['color_value'])) {
                                    $arr_color2['c_value'] = $v['attr_value'];
                                    $arr_color2['c_url'] = "#" . $v['color_value'];
                                    $v['attr_value'] = $arr_color2;
                                }
                                $all_attr_list[$key]['attr_list'][$temp_key]['attr_value'] = $v['attr_value'];
                                $all_attr_list[$key]['attr_list'][$temp_key]['goods_id'] = $v['goods_id']; // 取分类ID
                                $all_attr_list[$key]['attr_list'][$temp_key]['url'] = $this->dscRepository->buildUri('category', ['cid' => $cat_id, 'bid' => $brands_id, 'price_min' => $price_min, 'price_max' => $price_max, 'filter_attr' => $temp_arrt_url], $cat['cat_name']);

                                if (!empty($filter_attr[$key])) {
                                    if (!stripos($filter_attr[$key], ",") && $filter_attr[$key] == $v['goods_id']) {
                                        $all_attr_list[$key]['attr_list'][$temp_key]['selected'] = 1;
                                    }

                                    if (stripos($filter_attr[$key], ",")) {
                                        $color_arr = explode(",", $filter_attr[$key]);
                                        for ($i = 0; $i < count($color_arr); $i++) {
                                            if ($color_arr[$i] == $v['goods_id']) {
                                                $all_attr_list[$key]['attr_list'][$temp_key]['selected'] = 1;
                                            }
                                        }
                                    }
                                }
                            }
                            //by zhang end
                        }
                    }

                    /*                     * -------------------多条件筛选数组处理 by zhang start-----------------------* */
                    // 颜色区块单独拿出
                    $c_array = [];
                    $color_list = [];
                    $g_array = [];        // 选中的分类( 不含颜色分类 )

                    if ($all_attr_list) {
                        for ($i = 0; $i < count($all_attr_list); $i++) {
                            if (isset($all_attr_list[$i]['attr_cat_type']) && !empty($all_attr_list[$i]['attr_cat_type']) && $all_attr_list[$i]['attr_cat_type'] == 1) {
                                for ($k = 0; $k < count($all_attr_list[$i]['attr_list']); $k++) {
                                    $array_color = $all_attr_list[$i]['attr_list'];
                                    $attr_value_count = is_array($array_color[$k]['attr_value']) ? $array_color[$k]['attr_value'] : [$array_color[$k]['attr_value']];
                                    if (count($attr_value_count) == 1) {
                                        $array['c_value'] = $array_color[$k]['attr_value'];
                                        $array['c_url'] = "#FFFFFF";
                                        $all_attr_list[$i]['attr_list'][$k]['attr_value'] = $array;
                                    }
                                }
                                $color_list = $all_attr_list[$i];
                                unset($all_attr_list[$i]);
                                continue;
                            }
                        }

                        $k = "";
                        if ($color_list) {
                            for ($i = 0; $i < count($color_list['attr_list']); $i++) {
                                if (isset($color_list['attr_list'][$i]['selected']) && $color_list['attr_list'][$i]['selected'] == 1) {
                                    $c_array[$i]['filter_attr_name'] = $color_list['filter_attr_name'];
                                    $c_array[$i]['attr_list']['attr_value'] = $color_list['attr_list'][$i]['attr_value']['c_value'];
                                    $c_array[$i]['attr_list']['goods_id'] = $color_list['attr_list'][$i]['goods_id'];
                                    $color_id = $c_array[$i]['attr_list']['goods_id'];
                                    $k .= $c_array[$i]['attr_list']['attr_value'] . ","; // 取选中的名称
                                    $color_url = $color_list['attr_list'][$i]['url'];
                                    $color_id = (string)$color_id;
                                    if (strpos($color_url, $color_id)) {
                                        $c_array[$i]['attr_list']['url'] = str_replace($color_id, 0, $color_url);
                                    }
                                    $c_url = $c_array[$i]['attr_list']['url'];         // 还原
                                }
                            }

                            // 颜色合并
                            $c_array = [];
                            $c_array['filter_attr_name'] = $color_list['filter_attr_name'];
                            $c_array['attr_value'] = substr($k, 0, -1);
                            if (isset($c_url)) {
                                $c_array['url'] = $c_url;
                            }

                            $this->smarty->assign('c_array', $c_array);              // 颜色已选模块
                        }

                        if ($all_attr_list) {
                            $all_attr_list = array_values($all_attr_list);
                            for ($i = 0; $i < count($all_attr_list); $i++) {
                                $k = "";
                                for ($j = 0; $j < count($all_attr_list[$i]['attr_list']); $j++) {
                                    if (isset($all_attr_list[$i]['attr_list'][$j]['selected']) && $all_attr_list[$i]['attr_list'][$j]['selected'] == 1) {
                                        $g_array[$i]['filter_attr_name'] = $all_attr_list[$i]['filter_attr_name'];
                                        $g_array[$i]['attr_list']['value'] = $all_attr_list[$i]['attr_list'][$j]['attr_value'];
                                        $g_array[$i]['attr_list']['goods_id'] = $all_attr_list[$i]['attr_list'][$j]['goods_id'];
                                        $g_url = $all_attr_list[$i]['attr_list'][$j]['url']; // 被选中的URL
                                        $sid = $g_array[$i]['attr_list']['goods_id'];     // 被选中的ID
                                        $sid = (string)$sid;
                                        if (strpos($g_url, $sid)) {
                                            $g_array[$i]['attr_list']['url'] = str_replace($sid, 0, $g_url);
                                        }
                                        $k .= $all_attr_list[$i]['attr_list'][$j]['attr_value'] . ",";
                                        $g_array[$i]['g_name'] = substr($k, 0, -1);
                                        if (isset($g_array[$i]['attr_list']['url'])) {
                                            $g_array[$i]['g_url'] = $g_array[$i]['attr_list']['url'];
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $this->smarty->assign('g_array', $g_array);              // 其他已选模块
                    $this->smarty->assign('color_search', $color_list);           // 颜色筛选模块
                    /*                     * -------------------多条件筛选数组处理 by zhang end-----------------------* */

                    $this->smarty->assign('filter_attr_list', $all_attr_list);
                }
            }

            assign_template('c', [$cat_id]);

            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());              // 网店帮助

            $this->smarty->assign('show_marketprice', $this->config['show_marketprice']);

            /* 周改 */
            $this->smarty->assign('brand_id', $brands_id);
            $this->smarty->assign('price_max', $price_max);
            $this->smarty->assign('price_min', $price_min);
            $this->smarty->assign('filter_attr', $filter_attr_str);
            $this->smarty->assign('feed_url', ($this->config['rewrite'] == 1) ? "feed-c$cat_id.xml" : 'feed.php?cat=' . $cat_id); // RSS URL

            if ($cat_row['parent_id'] == 0 && ($cat_row['top_style_tpl'] == 2 || $cat_row['top_style_tpl'] == 0)) {
                $cat_brand = $this->categoryBrandService->getCategoryBrandsAd($cat_id, $warehouse_id, $area_id, $area_city);
                $this->smarty->assign('cat_brand', $cat_brand); //分类品牌 by wu
            }

            /*             * 小图 start* */
            $recommend_merchants = '';
            for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                $recommend_merchants .= "'recommend_merchants" . $i . ","; //推荐店铺广告 liu
            }
            $this->smarty->assign('recommend_merchants', $recommend_merchants);
            $this->smarty->assign('cat_id', $cat_id);

            /*             * ***************************************************************************************** */
            //顶级分类页据输出部分by wang start
            if ($cat_row['parent_id'] == 0 && $cat_row['is_top_style'] == 1) {
                $categories_child = $this->categoryService->getCatList($cat_id);

                $cat_top_ad = '';
                $cat_top_new_ad = '';
                $cat_top_newt_ad = '';
                $cat_top_prom_ad = '';
                $top_style_right_banne = '';
                //顶级分类页的幻灯片
                for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                    $cat_top_ad .= "'cat_top_ad" . $i . ","; //首页楼层轮播图
                    $cat_top_new_ad .= "'cat_top_new_ad" . $i . ","; //首页新品首发左侧上广告图
                    $cat_top_newt_ad .= "'cat_top_newt_ad" . $i . ","; //首页新品首发左侧下广告图
                    $cat_top_prom_ad .= "'cat_top_prom_ad" . $i . ","; //首页幻灯片下优惠商品左侧广告
                    $top_style_right_banne .= "'top_style_right_banner" . $i . ",";
                }

                if ($categories_child) {
                    foreach ($categories_child as $key => $val) {
                        if ($val['cat_id'] > 0) {
                            $categories_child[$key]['cate_layer_elec_row'] = "'cate_layer_elec_row" . ",";
                        }
                    }
                }

                //顶级分类页（家电模板）轮播右侧广告
                $this->smarty->assign('top_style_right_banne', $top_style_right_banne);

                //顶级分类页（家电模板）品牌左侧广告
                $top_style_elec_brand_left = "'top_style_elec_brand_left" . ",";
                $this->smarty->assign('top_style_elec_brand_left', $top_style_elec_brand_left);

                if ($cat_row['top_style_tpl'] == 1) {
                    $topStyle = ['new' => 10, 'hot' => 5, 'best' => 5, 'promote' => 4];
                    $topStyle['promote'] = 5;
                } elseif ($cat_row['top_style_tpl'] == 2) {
                    $topStyle = ['new' => 10, 'hot' => 10, 'best' => 10, 'promote' => 10];
                } elseif ($cat_row['top_style_tpl'] == 3) {
                    $topStyle = ['new' => 5, 'hot' => 18, 'best' => 5, 'promote' => 7];
                } else {
                    $topStyle = ['new' => 10, 'hot' => 8, 'best' => 10, 'promote' => 5];
                }

                /**
                 * Start
                 *
                 * 商品推荐
                 * 【'best' ：精品, 'new' ：新品, 'hot'：热销, 'promote'：促销】
                 */
                /* 新品 */
                $cate_top_new_goods = $this->categoryGoodsService->getCategoryRecommendGoods($children, 'new', $brands_id, $warehouse_id, $area_id, $area_city, $where_ext, $price_min, $price_max, $topStyle['new']);

                /* 热卖 */
                $cate_top_hot_goods = $this->categoryGoodsService->getCategoryRecommendGoods($children, 'hot', $brands_id, $warehouse_id, $area_id, $area_city, $where_ext, $price_min, $price_max, $topStyle['hot']);

                /* 促销 */
                $cate_top_promote_goods = $this->categoryGoodsService->getCategoryRecommendGoods($children, 'promote', $brands_id, $warehouse_id, $area_id, $area_city, $where_ext, $price_min, $price_max, $topStyle['promote']);

                /* 精品 */
                $cate_top_best_goods = $this->categoryGoodsService->getCategoryRecommendGoods($children, 'best', $brands_id, $warehouse_id, $area_id, $area_city, $where_ext, $price_min, $price_max, $topStyle['best']);

                $this->smarty->assign('cate_top_new_goods', $cate_top_new_goods);
                $this->smarty->assign('cate_top_hot_goods', $cate_top_hot_goods);
                $this->smarty->assign('cate_top_promote_goods', $cate_top_promote_goods);
                $this->smarty->assign('cate_top_best_goods', $cate_top_best_goods);
                /* End */

                $havealook = $this->categoryGoodsService->getCategoryRecommendGoods($children, 'rand', $brands_id, $warehouse_id, $area_id, $area_city, $where_ext, $price_min, $price_max, 18);
                $this->smarty->assign('havealook', $havealook);
                $this->smarty->assign('cat_top_ad', $cat_top_ad);
                $this->smarty->assign('cat_top_new_ad', $cat_top_new_ad);
                $this->smarty->assign('cat_top_newt_ad', $cat_top_newt_ad);
                $this->smarty->assign('cat_top_prom_ad', $cat_top_prom_ad);
                $this->smarty->assign('categories_child', $categories_child); //获取当前顶级分类下的所有子分类
            } else {
                $categories_pro = get_category_tree_leve_one();
                $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版

                /**
                 * Start
                 *
                 * 猜你喜欢商品
                 */
                $where = [
                    'warehouse_id' => $warehouse_id,
                    'area_id' => $area_id,
                    'area_city' => $area_city,
                    'user_id' => $user_id,
                    'history' => 1,
                    'page' => 1,
                    'limit' => 7
                ];
                $guess_goods = $this->goodsGuessService->getGuessGoods($where);

                $this->smarty->assign('guess_goods', $guess_goods);
                /* End */

                /* 调查 */
                $vote = get_vote();
                if (!empty($vote)) {
                    $this->smarty->assign('vote_id', $vote['id']);
                    $this->smarty->assign('vote', $vote['content']);
                }

                /**
                 * Start
                 *
                 * 商品推荐
                 * 【'best' ：精品, 'new' ：新品, 'hot'：热销, 'promote'：促销】
                 */
                /* 推荐商品 */
                $best_goods = $this->categoryGoodsService->getCategoryRecommendGoods($children, 'best', $brands_id, $warehouse_id, $area_id, $area_city, $where_ext, $price_min, $price_max, $best_size, $best_num);

                if (empty($best_goods)) {
                    $size = 24;
                }

                /* 促销商品 */
                $promotion_goods = $this->categoryGoodsService->getCategoryRecommendGoods($children, 'promote', $brands_id, $warehouse_id, $area_id, $area_city, $where_ext, $price_min, $price_max, $best_size, $best_num);

                /* 热卖商品 */
                $hot_goods = $this->categoryGoodsService->getCategoryRecommendGoods($children, 'hot', $brands_id, $warehouse_id, $area_id, $area_city, $where_ext, $price_min, $price_max, $best_size, $best_num);

                $this->smarty->assign('best_goods', $best_goods);
                $this->smarty->assign('promotion_goods', $promotion_goods);
                $this->smarty->assign('hot_goods', $hot_goods);
                /* End */

                $count = $this->categoryGoodsService->getCagtegoryGoodsCount($children, $brands_id, $warehouse_id, $area_id, $area_city, $price_min, $price_max, $cat_filter_attr, $filter_attr, $where_ext);

                $max_page = ($count > 0) ? ceil($count / $size) : 1;
                if ($page > $max_page) {
                    $page = $max_page;
                }

                $goodslist = $this->categoryGoodsService->getCategoryGoodsList($children, $brands_id, $warehouse_id, $area_id, $area_city, $price_min, $price_max, $cat_filter_attr, $filter_attr, $where_ext, $goods_num, $size, $page, $sort, $order);

                if ($display == 'grid') {
                    if (count($goodslist) % 2 != 0) {
                        $goodslist[] = [];
                    }
                }

                $this->smarty->assign('goods_list', $goodslist);
                $this->smarty->assign('category', $cat_id);
                $this->smarty->assign('script_name', $dwt_name);

                //瀑布流加载分类商品 by wu
                if ($act == 'load_more_goods') {
                    $model = intval(request()->input('model', ''));
                    $this->smarty->assign('model', $model);
                    $result = ['error' => 0, 'message' => '', 'cat_goods' => '', 'best_goods' => ''];
                    $result['cat_goods'] = html_entity_decode($this->smarty->fetch('library/more_goods.lbi')); //分类商品
                    $result['best_goods'] = html_entity_decode($this->smarty->fetch('library/more_goods_best.lbi')); //推广商品
                    return response()->json($result);
                }
                assign_pager('category', $cat_id, $count, $size, $sort, $order, $page, '', $brands_id, $price_min, $price_max, $display, $filter_attr_str, '', '', '', '', $ubrand, '', $ship, $self, $have); // 分页
            }

            //顶级分类页据输出部分by wang end

            $this->smarty->assign('region_id', $warehouse_id);
            $this->smarty->assign('area_id', $area_id);
            $this->smarty->assign('area_city', $area_city);

            if ($rank = get_rank_info()) {
                $this->smarty->assign('rank_name', $rank['rank_name']);
            }

            $this->smarty->assign('info', $this->userCommonService->getUserDefault($user_id));

            assign_dynamic($dwt_name); // 动态内容

            return $this->smarty->display($dwt_name . '.dwt');
        });

        return $content;
    }
}
