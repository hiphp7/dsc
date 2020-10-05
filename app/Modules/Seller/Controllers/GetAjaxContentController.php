<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Exchange;
use App\Libraries\Image;
use App\Models\Category;
use App\Models\GalleryAlbum;
use App\Repositories\Common\DscRepository;
use App\Services\Category\CategoryService;
use App\Services\Common\CommonManageService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsManageService;
use App\Services\Merchant\MerchantCommonService;

/*
 * 获取ajax数据
 */

class GetAjaxContentController extends InitController
{
    protected $categoryService;
    protected $dscRepository;
    protected $goodsManageService;
    protected $commonManageService;
    protected $goodsCommonService;
    protected $merchantCommonService;

    public function __construct(
        CategoryService $categoryService,
        DscRepository $dscRepository,
        GoodsManageService $goodsManageService,
        CommonManageService $commonManageService,
        GoodsCommonService $goodsCommonService,
        MerchantCommonService $merchantCommonService
    )
    {
        $this->categoryService = $categoryService;
        $this->dscRepository = $dscRepository;
        $this->goodsManageService = $goodsManageService;
        $this->commonManageService = $commonManageService;
        $this->goodsCommonService = $goodsCommonService;
        $this->merchantCommonService = $merchantCommonService;
    }

    public function index()
    {
        load_helper('goods', 'seller');
        load_helper('visual');

        $_REQUEST['act'] = trim($_REQUEST['act']);

        $data = ['error' => 0, 'message' => '', 'content' => ''];
        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);

        $admin_id = get_admin_id();
        $adminru = get_admin_ru_id();

        /*------------------------------------------------------ */
        //-- 获取下级分类列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'get_select_category') {
            $cat_id = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
            $child_cat_id = empty($_REQUEST['child_cat_id']) ? 0 : intval($_REQUEST['child_cat_id']);
            $cat_level = empty($_REQUEST['cat_level']) ? 0 : intval($_REQUEST['cat_level']);
            $select_jsId = empty($_REQUEST['select_jsId']) ? 'cat_parent_id' : trim($_REQUEST['select_jsId']);
            $type = empty($_REQUEST['type']) ? 0 : intval($_REQUEST['type']);
            $table = empty($_REQUEST['table']) ? 0 : intval($_REQUEST['table']);


            if ($table == 1) {
                $content = insert_seller_select_category($cat_id, $child_cat_id, $cat_level, $select_jsId, $type, 'merchants_category', [], $adminru['ru_id']);
            } else {
                $content = insert_select_category($cat_id, $child_cat_id, $cat_level, $select_jsId, $type);
            }

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
            $user_id = $adminru['ru_id'];
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $table = isset($_REQUEST['table']) && $_REQUEST['table'] != 'undefined' ? trim($_REQUEST['table']) : 'category';

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
                $result['content'] = $this->smarty->fetch('library/filter_category.lbi');
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 获取相册图片
        /*------------------------------------------------------ */
        /*获取相册图片*/
        elseif ($_REQUEST['act'] == 'get_albun_pic') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $is_vis = !empty($_REQUEST['is_vis']) ? intval($_REQUEST['is_vis']) : 0;
            $inid = !empty($_REQUEST['inid']) ? trim($_REQUEST['inid']) : 0;

            $pic_list = getAlbumList();

            $this->smarty->assign('pic_list', $pic_list['list']);
            $this->smarty->assign('filter', $pic_list['filter']);
            $this->smarty->assign('temp', 'ajaxPiclist');
            $this->smarty->assign('is_vis', $is_vis);
            $this->smarty->assign('inid', $inid);

            $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
            return response()->json($result);
        }
        /*------------------------------------------------------ */
        //-- 广告位模块
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'addmodule') {
            $result = ['error' => 0, 'message' => '', 'content' => '', 'mode' => ''];
            $result['spec_attr'] = !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';
            if ($_REQUEST['spec_attr']) {
                $_REQUEST['spec_attr'] = strip_tags(urldecode($_REQUEST['spec_attr']));
                $_REQUEST['spec_attr'] = json_str_iconv($_REQUEST['spec_attr']);
                if (!empty($_REQUEST['spec_attr'])) {
                    $spec_attr = dsc_decode($_REQUEST['spec_attr'], true);
                }
            }
            $pic_src = isset($spec_attr['pic_src']) ? $spec_attr['pic_src'] : [];
            $bg_color = isset($spec_attr['bg_color']) ? $spec_attr['bg_color'] : [];
            $link = (isset($spec_attr['link']) && $spec_attr['link'] != ',') ? explode(',', $spec_attr['link']) : [];
            $sort = isset($spec_attr['sort']) ? $spec_attr['sort'] : [];
            $result['mode'] = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';
            $is_li = isset($spec_attr['is_li']) ? intval($spec_attr['is_li']) : 0;
            $result['slide_type'] = isset($spec_attr['slide_type']) ? addslashes($spec_attr['slide_type']) : '';
            $result['itemsLayout'] = isset($spec_attr['itemsLayout']) ? addslashes($spec_attr['itemsLayout']) : '';
            $result['diff'] = isset($_REQUEST['diff']) ? intval($_REQUEST['diff']) : 0;
            $count = $pic_src ? COUNT($pic_src) : 0;//数组长度
            /*合并数组*/

            $arr = [];
            $sort_vals = [];
            if ($count) {
                for ($i = 0; $i < $count; $i++) {
                    $arr[$i]['pic_src'] = isset($pic_src[$i]) ? $pic_src[$i] : '';
                    if (isset($link[$i]) && $link[$i]) {
                        $arr[$i]['link'] = $this->dscRepository->setRewriteUrl($link[$i]);
                    } else {
                        $arr[$i]['link'] = isset($link[$i]) ? $link[$i] : '';
                    }
                    $arr[$i]['bg_color'] = isset($bg_color[$i]) ? $bg_color[$i] : '';
                    $arr[$i]['sort'] = isset($sort[$i]) ? $sort[$i] : 0;
                    $sort_vals[$i] = isset($sort[$i]) ? $sort[$i] : 0;
                }
            }

            if (!empty($arr)) {
                array_multisort($sort_vals, SORT_ASC, $arr);//数组重新排序
                $this->smarty->assign('img_list', $arr);
            }
            $this->smarty->assign('is_li', $is_li);
            $this->smarty->assign('temp', 'img_list');
            $this->smarty->assign('attr', $spec_attr);

            $this->smarty->assign('mode', $result['mode']);
            $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
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
            $result['spec_attr'] = !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';
            if (isset($_REQUEST['spec_attr']) && $_REQUEST['spec_attr']) {
                $_REQUEST['spec_attr'] = strip_tags(urldecode($_REQUEST['spec_attr']));
                $_REQUEST['spec_attr'] = json_str_iconv($_REQUEST['spec_attr']);
                if (!empty($_REQUEST['spec_attr'])) {
                    $spec_attr = dsc_decode($_REQUEST['spec_attr'], true);
                }
            }
            $sort_order = isset($_REQUEST['sort_order']) ? $_REQUEST['sort_order'] : 0;
            $cat_id = isset($_REQUEST['cat_id']) ? explode('_', $_REQUEST['cat_id']) : [];
            $brand_id = isset($_REQUEST['brand_id']) ? intval($_REQUEST['brand_id']) : 0;
            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $keyword = isset($_REQUEST['keyword']) ? addslashes($_REQUEST['keyword']) : '';
            $goodsAttr = isset($spec_attr['goods_ids']) ? explode(',', $spec_attr['goods_ids']) : '';
            $goods_ids = isset($_REQUEST['goods_ids']) ? explode(',', $_REQUEST['goods_ids']) : '';
            $result['goods_ids'] = !empty($goodsAttr) ? $goodsAttr : $goods_ids;
            $spec_attr['goods_ids'] = isset($spec_attr['goods_ids']) ? resetBarnd($spec_attr['goods_ids']) : '';//重置数据
            $result['cat_desc'] = isset($spec_attr['cat_desc']) ? addslashes($spec_attr['cat_desc']) : '';
            $result['cat_name'] = isset($spec_attr['cat_name']) ? addslashes($spec_attr['cat_name']) : '';
            $result['align'] = isset($spec_attr['align']) ? addslashes($spec_attr['align']) : '';
            $result['is_title'] = isset($spec_attr['is_title']) ? intval($spec_attr['is_title']) : 0;
            $result['itemsLayout'] = isset($spec_attr['itemsLayout']) ? addslashes($spec_attr['itemsLayout']) : '';
            $result['diff'] = isset($_REQUEST['diff']) ? intval($_REQUEST['diff']) : 0;
            $type = isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 0;
            $temp = isset($_REQUEST['temp']) ? $_REQUEST['temp'] : 'goods_list';
            $result['mode'] = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';
            $resetRrl = isset($_REQUEST['resetRrl']) ? intval($_REQUEST['resetRrl']) : 0;
            $this->smarty->assign('temp', $temp);

            $where = [
                'sort_order' => $sort_order,
                'search_type' => $search_type,
                'goods_id' => $goods_id,
                'brand_id' => $brand_id,
                'ru_id' => $adminru['ru_id'],
                'cat_id' => $cat_id,
                'type' => $type,
                'goods_ids' => $result['goods_ids'],
                'keyword' => $keyword,
                'is_on_sale' => 1,
            ];

            if ($type == 1) {
                $where['is_page'] = 1;

                $list = get_content_changed_goods($where);

                $goods_list = $list['list'];
                $filter = $list['filter'];
                $filter['cat_id'] = $cat_id[0];
                $filter['sort_order'] = $sort_order;
                $filter['keyword'] = $keyword;
                $filter['search_type'] = $search_type;
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
                    if ($v['goods_id'] > 0 && $result['goods_ids'] && in_array($v['goods_id'], $result['goods_ids']) && !empty($result['goods_ids'])) {
                        $goods_list[$k]['is_selected'] = 1;
                    }
                }
            }
            $this->smarty->assign("is_title", $result['is_title']);
            $this->smarty->assign('goods_count', count($goods_list));
            $this->smarty->assign('goods_list', $goods_list);
            $this->smarty->assign('attr', $spec_attr);
            $result['goods_ids'] = $result['goods_ids'] ? implode(',', $result['goods_ids']) : '';
            $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
            return response()->json($result);
        }
        /*------------------------------------------------------ */
        //-- 导航模块
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'navigator') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $result['mode'] = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';
            $result['diff'] = isset($_REQUEST['diff']) ? intval($_REQUEST['diff']) : 0;
            $result['spec_attr'] = !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';
            $_REQUEST['spec_attr'] = strip_tags(urldecode($_REQUEST['spec_attr']));
            $_REQUEST['spec_attr'] = json_str_iconv($_REQUEST['spec_attr']);
            if (!empty($_REQUEST['spec_attr'])) {
                $spec_attr = dsc_decode($_REQUEST['spec_attr'], true);
            }

            $result['navColor'] = $spec_attr['navColor'];

            /* 查询 */
            $where = ' where ru_id = ' . $adminru['ru_id'] . " AND ifshow = 1 ";
            $sql = "SELECT name, url " .
                " FROM " . $this->dsc->table('merchants_nav') . $where .
                " ORDER by vieworder";
            $navigator = $this->db->getAll($sql);
            foreach ($navigator as $k => $v) {
                if ($v['url']) {
                    $navigator[$k]['url'] = $this->dscRepository->setRewriteUrl($v['url']);
                }
            }
            $this->smarty->assign('navigator', $navigator);
            //获取商家二级域名
            $seller_domain = $this->merchantCommonService->getSellerDomainInfo($adminru['ru_id']);
            if ($seller_domain) {
                $index_url = $this->dscRepository->sellerUrl($adminru['ru_id']);
            } else {
                $index_url = "merchants_store.php?merchant_id=" . $adminru['ru_id'];
            }
            $this->smarty->assign('index_url', $index_url);
            $this->smarty->assign('temp', 'navigator');
            $this->smarty->assign('attr', $spec_attr);

            $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
            return response()->json($result);
        }
        /*------------------------------------------------------ */
        //-- 转移图片相册
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'album_move_back') {
            $result = ['content' => '', 'pic_id' => ''];
            $pic_id = isset($_REQUEST['pic_id']) ? intval($_REQUEST['pic_id']) : 0;
            $album_id = isset($_REQUEST['album_id']) ? intval($_REQUEST['album_id']) : 0;
            $sql = "UPDATE" . $this->dsc->table('pic_album') . " SET album_id = '" . $album_id . "' WHERE pic_id = '" . $pic_id . "' AND ru_id = '" . $adminru['ru_id'] . "'";
            $this->db->query($sql);
            $result['pic_id'] = $pic_id;
            return response()->json($result);
        }
        /*------------------------------------------------------ */
        //-- 添加相册
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_albun_pic') {
            $result = ['error' => '', 'pic_id' => '', 'content' => ''];

            $exc = new Exchange($this->dsc->table("gallery_album"), $this->db, 'album_id', 'album_mame');
            $allow_file_types = '|GIF|JPG|PNG|';
            $album_mame = isset($_REQUEST['album_mame']) ? addslashes($_REQUEST['album_mame']) : '';
            $parent_id = isset($_REQUEST['parent_id']) ? intval($_REQUEST['parent_id']) : 0;
            $album_desc = isset($_REQUEST['album_desc']) ? addslashes($_REQUEST['album_desc']) : '';
            $sort_order = isset($_REQUEST['sort_order']) ? intval($_REQUEST['sort_order']) : 50;
            /*检查是否重复*/
            $is_only = $exc->is_only('album_mame', $album_mame, 0, "ru_id = " . $adminru['ru_id']);
            if (!$is_only) {
                $result['error'] = 0;
                $result['content'] = $GLOBALS['_LANG']['album_exist'][0] . $album_mame . $GLOBALS['_LANG']['album_exist'][1];
                return response()->json($result);
            }
            /* 取得文件地址 */
            $file_url = '';
            if ((isset($_FILES['album_cover']['error']) && $_FILES['album_cover']['error'] == 0) || (!isset($_FILES['album_cover']['error']) && isset($_FILES['album_cover']['tmp_name']) && $_FILES['album_cover']['tmp_name'] != 'none')) {
                // 检查文件格式
                if (!check_file_type($_FILES['album_cover']['tmp_name'], $_FILES['album_cover']['name'], $allow_file_types)) {
                    $result['error'] = 0;
                    $result['content'] = $GLOBALS['_LANG']['album_cover_format_reupload'];
                    return response()->json($result);
                }

                // 复制文件
                $res = $this->upload_article_file($_FILES['album_cover']);
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
        /* ------------------------------------------------------ */
        //-- 获取品牌列表
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'search_brand_list') {
            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            if (isset($_REQUEST['ru_id']) && trim($_REQUEST['ru_id']) != 'undefined') {
                $this->smarty->assign('filter_brand_list', search_brand_list(0, intval($_REQUEST['ru_id'])));
            } else {
                $this->smarty->assign('filter_brand_list', search_brand_list($goods_id));
            }
            $result['content'] = $this->smarty->fetch('library/search_brand_list.lbi');
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 获取筛选商品列表
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'filter_list') {

            $search_type = empty($_REQUEST['search_type']) ? '' : trim($_REQUEST['search_type']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $limit['start'] = empty($_REQUEST['limit_start']) ? 0 : trim($_REQUEST['limit_start']);
            $limit['number'] = empty($_REQUEST['limit_number']) ? 50 : trim($_REQUEST['limit_number']);

            //筛选商品
            if ($search_type == "goods") {
                $cat_id = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
                $brand_id = empty($_REQUEST['brand_id']) ? 0 : intval($_REQUEST['brand_id']);
                $keyword = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);

                $filters['cat_id'] = $cat_id;
                $filters['brand_id'] = $brand_id;
                $filters['keyword'] = urlencode($keyword);
                $filters['sel_mode'] = 0;
                $filters['brand_keyword'] = "";
                $filters['exclude'] = "";

                $filters = dsc_decode(urldecode(json_encode($filters)));

                $arr = get_goods_list($filters, $limit);

                $opt = [];

                foreach ($arr as $key => $val) {
                    $opt[] = ['value' => $val['goods_id'],
                        'text' => $val['goods_name'],
                        'data' => $val['shop_price']];
                }
                $filter_list = $opt;

                $this->smarty->assign('filter_list', $filter_list);
                $result['content'] = $this->smarty->fetch('library/move_left.lbi');
                return response()->json($result);
            } //筛选文章
            elseif ($search_type == "article") {
                $title = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);

                $where = " WHERE cat_id > 0 ";
                if (!empty($title)) {
                    $where .= " AND title LIKE '%" . mysql_like_quote($title) . "%' ";
                }

                $sql = 'SELECT article_id, title FROM ' . $this->dsc->table('article') . $where .
                    'ORDER BY article_id DESC LIMIT 50';
                $res = $this->db->query($sql);
                $arr = [];

                foreach ($res as $row) {
                    $arr[] = ['value' => $row['article_id'], 'text' => $row['title'], 'data' => ''];
                }
                $filter_list = $arr;

                $this->smarty->assign('filter_list', $filter_list);
                $result['content'] = $this->smarty->fetch('library/move_left.lbi');
                return response()->json($result);
            } //筛选地区
            elseif ($search_type == "area") {
                $ra_id = empty($_REQUEST['keyword']) ? 0 : intval($_REQUEST['keyword']);

                $arr = $this->commonManageService->getAreaRegionInfoList($ra_id);
                $opt = [];

                foreach ($arr as $key => $val) {
                    $opt[] = ['value' => $val['region_id'],
                        'text' => $val['region_name'],
                        'data' => 0];
                }
                $filter_list = $opt;
            } //筛选商品类型
            elseif ($search_type == "goods_type") {
                $cat_id = empty($_REQUEST['keyword']) ? 0 : intval($_REQUEST['keyword']);

                $goods_fields = my_array_merge($GLOBALS['_LANG']['custom'], $this->get_attributes($cat_id));
                $opt = [];

                foreach ($goods_fields as $key => $val) {
                    $opt[] = ['value' => $key,
                        'text' => $val,
                        'data' => 0];
                }
                $filter_list = $opt;

                $this->smarty->assign('filter_list', $filter_list);
                $result['content'] = $this->smarty->fetch('library/move_left.lbi');
                return response()->json($result);
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

                $arr = $this->get_brandlist($filters);
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
        //-- 把商品加�        �商品柜
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_win_goods') {

            $linked_array = empty($_REQUEST['value']) ? [] : explode(",", $_REQUEST['value']);
            $id = empty($_REQUEST['win_id']) ? 0 : intval($_REQUEST['win_id']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $sql = "select win_goods from " . $this->dsc->table('seller_shopwindow') . " where id='$id'";
            $win_goods = $this->db->getOne($sql);

            foreach ($linked_array as $val) {
                if (!strstr($win_goods, $val) && !empty($val)) {
                    $win_goods .= !empty($win_goods) ? ',' . $val : $val;
                }
            }

            $sql = "update " . $this->dsc->table('seller_shopwindow') . " set win_goods='$win_goods' where id='$id'";
            $this->db->query($sql);

            $win_goods = $this->get_win_goods($id);

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

            $win_goods = $this->db->getOne("select win_goods from " . $this->dsc->table('seller_shopwindow') . " where id='$id'");
            $win_goods_arr = explode(',', $win_goods);

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

            $sql = "update " . $this->dsc->table('seller_shopwindow') . " set win_goods='$new_win_goods' where id='$id'";
            $this->db->query($sql);

            $win_goods = $this->get_win_goods($id);

            $this->smarty->assign('filter_result', $win_goods);
            $result['content'] = $this->smarty->fetch('library/move_right.lbi');

            clear_cache_files();
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 上传图片
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'upload_img') {
            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

            $act_type = empty($_REQUEST['type']) ? '' : trim($_REQUEST['type']);
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

            $result = ['error' => 0, 'pic' => '', 'name' => ''];
            $typeArr = ["jpg", "png", "gif", "jepg"]; //允许上传文件格式

            $bucket_info = $this->dscRepository->getBucketInfo();

            if (isset($_POST)) {
                $name = $_FILES['file']['name'];
                $size = $_FILES['file']['size'];
                $name_tmp = $_FILES['file']['tmp_name'];
                if (empty($name)) {
                    $result['error'] = $GLOBALS['_LANG']['no_select_album_yet'];
                }
                $type = strtolower(substr(strrchr($name, '.'), 1)); //获取文件类型
                if (!in_array($type, $typeArr)) {
                    $result['error'] = $GLOBALS['_LANG']['please_upload_jpg_gif_png'];
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
                    isset($_FILES['goods_thumb']['tmp_name']) && $_FILES['goods_thumb']['tmp_name'] != 'none'
                ) {
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
                    session()->put('goods.' . $admin_id . '.' . $goods_id, $result['data']);
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
                        $img = $original_img;
                        $gallery_img = $original_img;
                    }

                    $gallery_thumb = $this->goodsManageService->reformatImageName('gallery_thumb', $goods_id, $gallery_thumb, 'thumb');

                    $gallery_count = $this->db->getOne("SELECT MAX(img_desc) FROM " . $this->dsc->table('goods_gallery') . " WHERE goods_id = '$goods_id'");
                    $img_desc = $gallery_count + 1;

                    $gallery_images = [$gallery_img, $gallery_thumb, $img];

                    $sql = "INSERT INTO " . $this->dsc->table('goods_gallery') . " (goods_id, img_url, img_desc, thumb_url, img_original) " .
                        "VALUES ('$goods_id', '$gallery_img', $img_desc, '$gallery_thumb', '$img')";
                    $this->db->query($sql);
                    $thumb_img_id[] = $this->db->insert_id();
                    $this->dscRepository->getOssAddFile([$gallery_img, $gallery_thumb, $img]);
                    if (!empty(session('thumb_img_id' . session('seller_id')))) {
                        $thumb_img_id = array_merge($thumb_img_id, session('thumb_img_id' . session('seller_id')));
                    }

                    session()->put('thumb_img_id' . session('seller_id'), $thumb_img_id);

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

                $gallery_count = $this->goodsManageService->getGoodsGalleryCount($goods_id);
                $result['img_desc'] = $gallery_count + 1;

                $this->goodsManageService->handleGalleryImageAdd($goods_id, $_FILES['img_url'], $img_desc, $img_file, '', '', 'ajax', $result['img_desc']);

                clear_cache_files();

                if ($goods_id > 0) {
                    /* 图片列表 */
                    $sql = "SELECT * FROM " . $this->dsc->table('goods_gallery') . " WHERE goods_id = '$goods_id' ORDER BY img_desc ASC";
                } else {
                    $img_id = session('thumb_img_id' . session('seller_id'));
                    $where = '';
                    if ($img_id) {
                        $where = "AND img_id " . db_create_in($img_id) . "";
                    }
                    $sql = "SELECT * FROM " . $this->dsc->table('goods_gallery') . " WHERE goods_id='' $where ORDER BY img_desc ASC";
                }
                $img_list = $this->db->getAll($sql);
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
                $min_img_id = $this->db->getOne(" SELECT img_id   FROM " . $this->dsc->table("goods_gallery") . " WHERE goods_id = '$goods_id' AND img_desc = '$img_default' ORDER BY img_desc   LIMIT 1");
                $this->smarty->assign('goods', $goods);

                /* 结束处理 start */
                $this_img_info = $this->db->getRow(" SELECT * FROM " . $this->dsc->table('goods_gallery') . " WHERE goods_id = '$goods_id' ORDER BY img_id DESC LIMIT 1 ");
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
                $result['error'] = $GLOBALS['_LANG']['upload_error_check_server_config'];
            }
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
                    $sql = "INSERT INTO " . $this->dsc->table('link_goods') . " (goods_id, link_goods_id, is_double, admin_id) " .
                        "VALUES ('$val', '$goods_id', '$is_double', '" . session('seller_id') . "')";
                    $this->db->query($sql, 'SILENT');
                }

                $sql = "INSERT INTO " . $this->dsc->table('link_goods') . " (goods_id, link_goods_id, is_double, admin_id) " .
                    "VALUES ('$goods_id', '$val', '$is_double', '" . session('seller_id') . "')";
                $this->db->query($sql, 'SILENT');
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

            if (!$is_signle) {
                $sql = "DELETE FROM " . $this->dsc->table('link_goods') .
                    " WHERE link_goods_id = '$goods_id' AND goods_id " . $drop_goods_ids;
            } else {
                $sql = "UPDATE " . $this->dsc->table('link_goods') . " SET is_double = 0 " .
                    " WHERE link_goods_id = '$goods_id' AND goods_id " . $drop_goods_ids;
            }
            if ($goods_id == 0) {
                $sql .= " AND admin_id = '" . session('seller_id') . "'";
            }
            $this->db->query($sql);

            $sql = "DELETE FROM " . $this->dsc->table('link_goods') .
                " WHERE goods_id = '$goods_id' AND link_goods_id " . $drop_goods_ids;
            if ($goods_id == 0) {
                $sql .= " AND admin_id = '" . session('seller_id') . "'";
            }
            $this->db->query($sql);

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

        /* ------------------------------------------------------ */
        //-- 增加一个�        �件
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_group_goods') {
            //$fittings = empty($_REQUEST['value']) ? [] : explode(",", $_REQUEST['value']);
            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $goods_ids = empty($_REQUEST['goods_ids']) ? 0 : trim($_REQUEST['goods_ids']);
            $price = empty($_REQUEST['price2']) ? 0 : floatval($_REQUEST['price2']);
            $group_id = empty($_REQUEST['group2']) ? 1 : intval($_REQUEST['group2']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $sql = "select count(*) from " . $this->dsc->table('group_goods') . " where parent_id = '$goods_id' and group_id = '$group_id' and admin_id = '" . session('seller_id') . "'";
            $groupCount = $this->db->getOne($sql);

            $message = "";
            if ($groupCount < 1000) {
                if ($goods_ids) {
                    $goods_ids = explode(',', $goods_ids);
                }

                if (!empty($goods_ids)) {
                    foreach ($goods_ids as $key => $val) {
                        $sql = "SELECT id FROM " . $this->dsc->table('group_goods') . " WHERE parent_id = '$goods_id' AND goods_id = '$val'  LIMIT 1";
                        if (!$this->db->getOne($sql)) {
                            $price_goods = 0;
                            if ($price == 0) {
                                $price_goods = $this->db->getOne("SELECT shop_price FROM" . $this->dsc->table('goods') . " WHERE goods_id = '$val'");
                            } else {
                                $price_goods = $price;
                            }
                            $sql = "INSERT INTO " . $this->dsc->table('group_goods') . " (parent_id, goods_id, goods_price, admin_id, group_id) " .
                                "VALUES ('$goods_id', '$val', '$price_goods', '" . session('seller_id') . "', '$group_id')"; //by mike add
                            $this->db->query($sql, 'SILENT');
                        }
                    }
                }
                $error = 0;
            } else {
                $error = 1;
                $message = $GLOBALS['_LANG']['one_group_peijian_5_goods'];
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
        //-- 删除一个�        �件
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drop_group_goods') {
            $fittings = empty($_REQUEST['value']) ? [] : explode(",", $_REQUEST['value']);
            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $price = empty($_REQUEST['price2']) ? 0 : floatval($_REQUEST['price2']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $sql = "DELETE FROM " . $this->dsc->table('group_goods') .
                " WHERE parent_id='$goods_id' AND " . db_create_in($fittings, 'id');
            if ($goods_id == 0) {
                $sql .= " AND admin_id = '" . session('seller_id') . "'";
            }
            $this->db->query($sql);

            $arr = get_group_goods($goods_id);
            $opt = [];

            foreach ($arr as $val) {
                $opt[] = ['value' => $val['id'],
                    'text' => '[' . $val['group_name'] . ']' . $val['goods_name'],
                    'data' => ''];
            }

            $this->smarty->assign('filter_result', $opt);
            $result['content'] = $this->smarty->fetch('library/move_right.lbi');

            clear_cache_files();
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 添加�        �联文章
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_goods_article') {
            $articles = empty($_REQUEST['value']) ? [] : explode(",", $_REQUEST['value']);
            $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            foreach ($articles as $val) {
                $sql = "INSERT INTO " . $this->dsc->table('goods_article') . " (goods_id, article_id, admin_id) " .
                    "VALUES ('$goods_id', '$val', '" . session('seller_id') . "')";
                $this->db->query($sql);
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

            $sql = "DELETE FROM " . $this->dsc->table('goods_article') . " WHERE " . db_create_in($articles, "article_id") . " AND goods_id = '$goods_id'";
            $this->db->query($sql);

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

            if ($goods_id) {
                $sql = "SELECT user_id FROM " . $this->dsc->table('goods') . " WHERE goods_id = '$goods_id'";
                $ru_id = $this->db->getOne($sql);
            } else {
                $ru_id = $adminru['ru_id'];
            }

            foreach ($fittings as $val) {
                $sql = "INSERT INTO " . $this->dsc->table('link_area_goods') . " (goods_id, region_id, ru_id) " .
                    "VALUES ('$goods_id', '$val', '$ru_id')";
                $this->db->query($sql, 'SILENT');
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

            $sql = "DELETE FROM " . $this->dsc->table('link_area_goods') . " WHERE region_id" . $drop_goods_ids . " and goods_id = '$goods_id'";
            if ($goods_id == 0) {
                $ru_id = $adminru['ru_id'];

                $sql .= " AND ru_id = '$ru_id'";
            }
            $this->db->query($sql);

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
            $linked_goods = get_linked_goods_desc();

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
            $linked_goods = get_linked_goods_desc();

            $options = [];
            foreach ($linked_goods as $val) {
                $options[] = [
                    'value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => ''];
            }

            if (empty($linked_goods)) {
                $sql = "delete from " . $this->dsc->table('link_desc_temporary') . " where 1";
                $this->db->query($sql);
            }

            $this->smarty->assign('filter_result', $options);
            $content = $this->smarty->fetch('library/move_right.lbi');

            clear_cache_files();
            return make_json_result($content);
        } /*编辑导航名称*/
        elseif ($_REQUEST['act'] == 'edit_navname') {
            $result = ['error' => '', 'pic_id' => '', 'content' => ''];
            $exc = new Exchange($this->dsc->table("merchants_nav"), $this->db, 'id', 'name');
            $nav_name = isset($_REQUEST['val']) ? addslashes($_REQUEST['val']) : '';
            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            if ($id > 0 && !empty($nav_name)) {
                /*判断导航是否重复*/
                $is_only = $exc->is_only('name', $nav_name, 0, " ru_id = " . $adminru['ru_id']);
                if (!$is_only) {
                    $result['error'] = 0;
                    $result['content'] = $GLOBALS['_LANG']['nav_exist_nav'] . $nav_name . $GLOBALS['_LANG']['album_exist'][1];
                } else {
                    $sql = "UPDATE" . $this->dsc->table('merchants_nav') . " SET name = '$nav_name' WHERE id = '$id' AND ru_id = " . $adminru['ru_id'];
                    $this->db->query($sql);
                    $result['error'] = 1;
                    $result['content'] = $GLOBALS['_LANG']['edit_success'];
                }
            } else {
                $result['error'] = 0;
                $result['content'] = $GLOBALS['_LANG']['nav_not_exist_or_empty'];
            }
            return response()->json($result);
        } /*编辑导航链接*/
        elseif ($_REQUEST['act'] == 'edit_navurl') {
            $result = ['error' => '', 'pic_id' => '', 'content' => ''];
            $url = isset($_REQUEST['val']) ? addslashes($_REQUEST['val']) : '';
            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            if ($id > 0) {
                $sql = "UPDATE" . $this->dsc->table('merchants_nav') . " SET url = '$url' WHERE id = '$id' AND ru_id = " . $adminru['ru_id'];
                $this->db->query($sql);
                $result['error'] = 1;
                $result['content'] = $GLOBALS['_LANG']['edit_success'];
            } else {
                $result['error'] = 0;
                $result['content'] = $GLOBALS['_LANG']['js_languages']['jl_navbar_no_exist'];
            }
            return response()->json($result);
        } /*编辑导航排序*/
        elseif ($_REQUEST['act'] == 'edit_navvieworder') {
            $result = ['error' => '', 'pic_id' => '', 'content' => ''];
            $order = isset($_REQUEST['val']) ? intval($_REQUEST['val']) : '';
            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            if ($id > 0) {
                if (preg_match('/^\d+$/i', $order)) {
                    $sql = "UPDATE" . $this->dsc->table('merchants_nav') . " SET vieworder = '$order' WHERE id = '$id' AND ru_id = " . $adminru['ru_id'];
                    $this->db->query($sql);
                    $result['error'] = 1;
                    $result['content'] = $GLOBALS['_LANG']['edit_success'];
                } else {
                    $result['error'] = 0;
                    $result['content'] = $GLOBALS['_LANG']['sort_must_number'];
                }
            } else {
                $result['error'] = 0;
                $result['content'] = $GLOBALS['_LANG']['js_languages']['jl_navbar_no_exist'];
            }
            return response()->json($result);
        } /*编辑导航排序*/
        elseif ($_REQUEST['act'] == 'remove_nav') {
            $result = ['error' => '', 'pic_id' => '', 'content' => ''];
            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            if ($id > 0) {
                $row = $this->db->getRow("SELECT ctype,cid,type FROM " . $this->dsc->table('merchants_nav') . " WHERE id = '$id' LIMIT 1");

                if ($row['type'] == 'middle' && $row['ctype'] && $row['cid']) {
                    $this->set_show_in_nav($row['ctype'], $row['cid'], 0);
                }

                $sql = " DELETE FROM " . $this->dsc->table('merchants_nav') . " WHERE id='$id' LIMIT 1";
                $this->db->query($sql);
                $result['error'] = 1;
                $result['content'] = $GLOBALS['_LANG']['delete_success_alt'];
            } else {
                $result['error'] = 0;
                $result['content'] = $GLOBALS['_LANG']['js_languages']['jl_navbar_no_exist'];
            }
            return response()->json($result);
        } /*添加导航*/
        elseif ($_REQUEST['act'] == 'add_nav') {
            $result = ['error' => '', 'pic_id' => '', 'content' => ''];
            $exc = new Exchange($this->dsc->table("merchants_nav"), $this->db, 'id', 'name');
            $link = isset($_REQUEST['link']) ? addslashes($_REQUEST['link']) : '';
            $name = isset($_REQUEST['nav_name']) ? addslashes($_REQUEST['nav_name']) : '';
            if (!empty($name)) {
                /*判断导航是否重复*/
                $is_only = $exc->is_only('name', $name, 0, " ru_id = " . $adminru['ru_id']);
                if (!$is_only) {
                    $result['error'] = 0;
                    $result['content'] = $GLOBALS['_LANG']['nav_exist_nav'] . $name . $GLOBALS['_LANG']['album_exist'][1];
                } else {
                    $sql = "INSERT INTO" . $this->dsc->table("merchants_nav") . "(`name`,`url`,`ifshow`,`type`,`ru_id`,`vieworder`) VALUES('$name','$link',1,'middle','" . $adminru['ru_id'] . "',50)";
                    $this->db->query($sql);
                    $id = $this->db->insert_id();
                    $result['error'] = 1;
                    $html_id = "'" . $id . "'";
                    $html_act_name = "'edit_navname'";
                    $html_act_url = "'edit_navurl'";
                    $html_act_order = "'edit_navvieworder'";
                    $html_act_if_show = "'edit_ifshow'";
                    $html_act_type = "'1'";

                    $html = '<tr><td><input type="text" onchange = "edit_nav(this.value ,' . $html_id . ',' . $html_act_name . ')" value="' . $name . '"></td>';
                    $html .= '<td><input type="text" onchange = "edit_nav(this.value ,' . $html_id . ',' . $html_act_url . ')" value="' . $link . '"></td>';
                    $html .= '<td class="center"><input type="text" onchange = "edit_nav(this.value ,' . $html_id . ',' . $html_act_order . ')" class="small" value="50"></td>';
                    $html .= '<td class="center" id="nav_' . $id . '"><img onclick = "edit_nav(' . $html_act_type . ' ,' . $html_id . ',' . $html_act_if_show . ',' . $html_act_type . ')" src="' . __TPL__ . '/images/yes.gif"/></td>';
                    $html .= '<td class="center"><a href="javascript:void(0);" onclick="remove_nav(' . $html_id . ')" class="pic_del del">删除</a></td></tr>';
                    $result['content'] = $html;
                }
            } else {
                $result['error'] = 0;
                $result['content'] = $GLOBALS['_LANG']['nav_name_not_null'];
            }


            return response()->json($result);
        } /*切换导航显示*/
        elseif ($_REQUEST['act'] == 'edit_ifshow') {
            $result = ['error' => '', 'pic_id' => '', 'content' => ''];
            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $ifshow = isset($_REQUEST['val']) ? intval($_REQUEST['val']) : 0;
            if ($id > 0) {
                if ($ifshow == 0) {
                    $val = 1;
                } else {
                    $val = 0;
                }
                $sql = "UPDATE" . $this->dsc->table('merchants_nav') . " SET ifshow = '$val' WHERE id = '$id' AND ru_id = " . $adminru['ru_id'];
                $this->db->query($sql);
                $result['error'] = 1;
                $result['id'] = $id;
                $html_ifshow = "'" . $val . "'";
                $html_id = "'" . $id . "'";
                $html_act_if_show = "'edit_ifshow'";
                $html_act_type = "'1'";
                if ($val == 1) {
                    $src = __TPL__ . "/images/yes.gif";
                } else {
                    $src = __TPL__ . "/images/no.gif";
                }
                $html = '<img onclick = "edit_nav(' . $html_ifshow . ' ,' . $html_id . ',' . $html_act_if_show . ',' . $html_act_type . ')" src="' . $src . '"/>';
                $result['content'] = $html;
            } else {
                $result['error'] = 0;
                $result['content'] = $GLOBALS['_LANG']['js_languages']['jl_navbar_no_exist'];
            }
            return response()->json($result);
        } /*获取图库列表*/
        elseif ($_REQUEST['act'] == 'gallery_album_list') {
            $result = ['error' => 0, 'message' => '', 'log_type' => '', 'content' => ''];
            $album_id = !empty($_REQUEST['album_id']) ? intval($_REQUEST['album_id']) : 0;
            $inid = !empty($_REQUEST['inid']) ? addslashes($_REQUEST['inid']) : '';
            $sql = "SELECT album_id,ru_id,album_mame,album_cover,album_desc,sort_order FROM " . $this->dsc->table('gallery_album') . " "
                . " WHERE ru_id = '" . $adminru['ru_id'] . "' ORDER BY sort_order";
            $gallery_album_list = $this->db->getAll($sql);
            $this->smarty->assign('gallery_album_list', $gallery_album_list);
            $res = [];
            if ($gallery_album_list || $album_id > 0) {
                $album_id = ($album_id > 0) ? $album_id : $gallery_album_list[0]['album_id'];
                $sql = "SELECT album_mame FROM " . $this->dsc->table('gallery_album') . " "
                    . " WHERE ru_id = '" . $adminru['ru_id'] . "' AND  album_id = '" . $album_id . "'";
                $album_mame = $this->db->getOne($sql);
                $where = "";
                $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('pic_album') . " WHERE ru_id = '" . $adminru['ru_id'] . "' AND album_id = '" . $album_id . "'";
                $filter['record_count'] = $this->db->getOne($sql);
                $filter = page_and_size($filter, 2);
                $where = "LIMIT " . $filter['start'] . "," . $filter['page_size'];
                $sql = "SELECT * FROM " . $this->dsc->table('pic_album') . " WHERE ru_id = '" . $adminru['ru_id'] . "' AND album_id ='" . $album_id . "' ORDER BY pic_id ASC $where";
                $res = $this->db->getAll($sql);
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
            //初始化数据
            $thumb_img_id = [];
            $img_default = 1;
            $sql = "SELECT pic_file,pic_thumb,pic_image FROM" . $this->dsc->table('pic_album') . "WHERE pic_id in(" . $pic_id . ")";
            $img_list = $this->db->getAll($sql);

            if ($img_list) {
                $j = 0;
                foreach ($img_list as $key => $val) {
                    $j++;
                    $img_id = 0;
                    //获取排序
                    if ($inid == 'gallery_album') {
                        $img_desc_new = 1;
                        if ($goods_id > 0) {
                            $sql = "UPDATE" . $this->dsc->table('goods_gallery') . " SET img_desc = img_desc+1 WHERE goods_id = '$goods_id'";
                            $this->db->query($sql);
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
                    //入库
                    $sql = "INSERT INTO " . $this->dsc->table('goods_gallery') . " (goods_id, img_url, img_desc, thumb_url, img_original) " .
                        "VALUES ('$goods_id', '" . $val['pic_image'] . "', $img_desc_new, '" . $val['pic_thumb'] . "', '" . $val['pic_file'] . "')";
                    $this->db->query($sql);
                    $thumb_img_id[] = $img_id = $this->db->insert_id();

                    $this->dscRepository->getOssAddFile($result['data']);
                }
            }
            if (!empty(session()->get('thumb_img_id' . session('seller_id'))) && is_array($thumb_img_id) && is_array(session()->get('thumb_img_id' . session('seller_id')))) {
                $thumb_img_id = array_merge($thumb_img_id, session()->get('thumb_img_id' . session('seller_id')));
            }

            session()->put('thumb_img_id' . session('seller_id'), $thumb_img_id);

            if ($goods_id > 0) {
                /* 图片列表 */
                $sql = "SELECT img_desc,thumb_url,goods_id,external_url,img_id FROM " . $this->dsc->table('goods_gallery') . " WHERE goods_id = '$goods_id' ORDER BY img_desc ASC";
            } else {
                $where = '';
                if (!empty(session()->get('thumb_img_id' . session('seller_id')))) {
                    $where = "AND img_id " . db_create_in(session()->get('thumb_img_id' . session('seller_id'))) . "";
                }
                $sql = "SELECT img_desc,thumb_url,goods_id,external_url,img_id FROM " . $this->dsc->table('goods_gallery') . " WHERE goods_id='' $where ORDER BY img_desc ASC";
            }
            $goods_gallery_list = $this->db->getAll($sql);

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
        } //专题导航
        elseif ($_REQUEST['act'] == 'navInsert') {
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
            $this->smarty->assign('temp', 'navigator_home');
            $result['spec_attr'] = $arr;
            $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
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
            $result['floor_title'] = !empty($_REQUEST['floor_title']) ? trim($_REQUEST['floor_title']) : '';//楼层标题
            $spec_attr['hierarchy'] = !empty($_REQUEST['hierarchy']) ? intval($_REQUEST['hierarchy']) : '';
            $result['cat_goods'] = !empty($_REQUEST['cat_goods']) ? $_REQUEST['cat_goods'] : '';
            $result['moded'] = !empty($_REQUEST['mode']) ? trim($_REQUEST['mode']) : '';
            $result['cat_id'] = !empty($_REQUEST['Floorcat_id']) ? intval($_REQUEST['Floorcat_id']) : 0;//一级分类
            $result['cateValue'] = !empty($_REQUEST['cateValue']) ? $_REQUEST['cateValue'] : '';//二级分类
            $result['typeColor'] = !empty($_REQUEST['typeColor']) ? trim($_REQUEST['typeColor']) : '';//颜色
            $result['fontColor'] = !empty($_REQUEST['fontColor']) ? trim($_REQUEST['fontColor']) : ''; //字体颜色
            $result['floorMode'] = !empty($_REQUEST['floorMode']) ? intval($_REQUEST['floorMode']) : '';//模板

            $result['brand_ids'] = !empty($_REQUEST['brand_ids']) ? trim($_REQUEST['brand_ids']) : '';//楼层品牌

            $result['leftBanner'] = !empty($_REQUEST['leftBanner']) ? $_REQUEST['leftBanner'] : '';//左侧轮播
            $result['leftBannerLink'] = !empty($_REQUEST['leftBannerLink']) ? $_REQUEST['leftBannerLink'] : '';//左侧轮播链接
            $result['leftBannerSort'] = !empty($_REQUEST['leftBannerSort']) ? $_REQUEST['leftBannerSort'] : '';//左侧轮播排序

            $result['leftAdv'] = !empty($_REQUEST['leftAdv']) ? $_REQUEST['leftAdv'] : '';//左侧广告
            $result['leftAdvLink'] = !empty($_REQUEST['leftAdvLink']) ? $_REQUEST['leftAdvLink'] : '';//左侧广告链接
            $result['leftAdvSort'] = !empty($_REQUEST['leftAdvSort']) ? $_REQUEST['leftAdvSort'] : '';//左侧广告排序

            $result['rightAdv'] = !empty($_REQUEST['rightAdv']) ? $_REQUEST['rightAdv'] : '';//右侧广告
            $result['rightAdvLink'] = !empty($_REQUEST['rightAdvLink']) ? $_REQUEST['rightAdvLink'] : '';//右侧广告链接
            $result['rightAdvSort'] = !empty($_REQUEST['rightAdvSort']) ? $_REQUEST['rightAdvSort'] : '';//右侧广告排序
            $result['rightAdvTitle'] = !empty($_REQUEST['rightAdvTitle']) ? $_REQUEST['rightAdvTitle'] : '';//右侧广告主标题
            $result['rightAdvSubtitle'] = !empty($_REQUEST['rightAdvSubtitle']) ? $_REQUEST['rightAdvSubtitle'] : '';//右侧广告副标题

            $result['top_goods'] = !empty($_REQUEST['top_goods']) ? trim($_REQUEST['top_goods']) : '';//楼层分类商品字符串
            $spec_attr = $result;
            $result['lift'] = !empty($_REQUEST['lift']) ? trim($_REQUEST['lift']) : '';

            //初始化楼层图片数组
            $AdvNum = getAdvNum($result['moded'], $result['floorMode']);

            //处理轮播数组
            $AdvBanner = '';
            if (!empty($AdvNum['leftBanner']) && $AdvNum['leftBanner'] > 0) {
                if (!empty($result['leftBanner'])) {
                    foreach ($result['leftBanner'] as $k => $v) {
                        $AdvBanner[$k]['leftBanner'] = $result['leftBanner'][$k];
                        $AdvBanner[$k]['leftBannerSort'] = $result['leftBannerSort'][$k];
                        if ($result['leftBannerLink'][$k]) {
                            $AdvBanner[$k]['leftBannerLink'] = $this->dscRepository->setRewriteUrl($result['leftBannerLink'][$k]);
                        } else {
                            $AdvBanner[$k]['leftBannerLink'] = $result['leftBannerLink'][$k];
                        }
                        $sort_vals[$k] = isset($result['leftBannerSort'][$k]) ? $result['leftBannerSort'][$k] : 0;
                    }
                } else {
                    $AdvNum['leftBanner'] = 1;
                    for ($k = 0; $k < $AdvNum['leftBanner']; $k++) {
                        $AdvBanner[$k]['leftBanner'] = $result['leftBanner'][$k];
                        $AdvBanner[$k]['leftBannerSort'] = $result['leftBannerSort'][$k];
                        if ($result['leftBannerLink'][$k]) {
                            $AdvBanner[$k]['leftBannerLink'] = $this->dscRepository->setRewriteUrl($result['leftBannerLink'][$k]);
                        } else {
                            $AdvBanner[$k]['leftBannerLink'] = $result['leftBannerLink'][$k];
                        }
                        $sort_vals[$k] = isset($result['leftBannerSort'][$k]) ? $result['leftBannerSort'][$k] : 0;
                    }
                }
            }

            if ($AdvBanner) {
                array_multisort($sort_vals, SORT_ASC, $AdvBanner);//数组重新排序
            }
            $spec_attr['leftBanner'] = $AdvBanner;

            //处理左侧广告数组
            $AdvLeft = [];
            $sort_vals = [];
            if (!empty($AdvNum['leftAdv']) && $AdvNum['leftAdv'] > 0) {
                for ($k = 0; $k < $AdvNum['leftAdv']; $k++) {
                    $AdvLeft[$k]['leftAdv'] = isset($result['leftAdv'][$k]) ? $result['leftAdv'][$k] : '';
                    $AdvLeft[$k]['leftAdvSort'] = isset($result['leftAdvSort'][$k]) ? $result['leftAdvSort'][$k] : '';
                    if (isset($result['leftAdvLink'][$k])) {
                        $AdvLeft[$k]['leftAdvLink'] = $this->dscRepository->setRewriteUrl($result['leftAdvLink'][$k]);
                    } else {
                        $AdvLeft[$k]['leftAdvLink'] = isset($result['leftAdvLink'][$k]) ? $result['leftAdvLink'][$k] : '';
                    }
                    $sort_vals[$k] = isset($result['leftAdvSort'][$k]) ? $result['leftAdvSort'][$k] : 0;
                }
            }
            if ($AdvLeft) {
                array_multisort($sort_vals, SORT_ASC, $AdvLeft);//数组重新排序
            }

            $spec_attr['leftAdv'] = $AdvLeft;

            //处理右侧广告数组
            $AdvRight = '';
            $sort_vals = '';
            if (!empty($AdvNum['rightAdv']) && $AdvNum['rightAdv'] > 0) {
                for ($k = 0; $k < $AdvNum['rightAdv']; $k++) {
                    $AdvRight[$k]['rightAdv'] = isset($result['rightAdv'][$k]) ? $result['rightAdv'][$k] : '';
                    $AdvRight[$k]['rightAdvSort'] = isset($result['rightAdvSort'][$k]) ? $result['rightAdvSort'][$k] : '';
                    if (isset($result['leftBannerLink'][$k])) {
                        $AdvRight[$k]['rightAdvLink'] = $this->dscRepository->setRewriteUrl($result['rightAdvLink'][$k]);
                    } else {
                        $AdvRight[$k]['rightAdvLink'] = isset($result['rightAdvLink'][$k]) ? $result['rightAdvLink'][$k] : '';
                    }
                    $AdvRight[$k]['rightAdvTitle'] = isset($result['rightAdvTitle'][$k]) ? $result['rightAdvTitle'][$k] : '';
                    $AdvRight[$k]['rightAdvSubtitle'] = isset($result['rightAdvSubtitle'][$k]) ? $result['rightAdvSubtitle'][$k] : '';
                    $sort_vals[$k] = isset($result['rightAdvSort'][$k]) ? $result['rightAdvSort'][$k] : 0;
                }
            }
            if ($AdvRight) {
                array_multisort($sort_vals, SORT_ASC, $AdvRight);//数组重新排序
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
            }
            $storeThree = 0;
            //获得二级分类的所有信息
            if (!empty($result['cateValue'])) {
                $cat_tow = '';
                $i = 0;
                foreach ($result['cateValue'] as $k => $v) {
                    $i++;
                    if ($result['moded'] == 'storeThreeFloor1' && $result['floorMode'] == 4 && $i == 1) {
                        $storeThree = $v;//店铺第三套模板  默认第一位二级分类
                    }
                    $arr = [];
                    if ($v > 0) {
                        $sql = "SELECT cat_name,cat_id FROM " . $this->dsc->table('category') . " WHERE cat_id = '$v'";
                        $arr = $this->db->getRow($sql);
                        $arr['goods_id'] = $result['cat_goods'][$k];
                        $cat_tow[] = $arr;
                    }
                }
                $spec_attr['cateValue'] = $cat_tow;
            }

            $brand_list = '';
            if ($result['brand_ids']) {
                $where = ' WHERE 1';
                $brandId = $result['brand_ids'];
                $where .= " AND b.brand_id in ($brandId)";
                $sql = "SELECT b.brand_id,b.brand_name,b.brand_logo,b.site_url FROM " . $this->dsc->table('brand') . " as b left join " . $this->dsc->table('brand_extend') . " AS be on b.brand_id=be.brand_id " . $where;
                $brand_list = $this->db->getAll($sql);
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
                        $result['rightAdvTitle'][$k] = strFilter($v);
                    }
                }
            }
            if ($result['rightAdvSubtitle']) {
                foreach ($result['rightAdvSubtitle'] as $k => $v) {
                    if ($v) {
                        $result['rightAdvSubtitle'][$k] = strFilter($v);
                    }
                }
            }
            //获取商品
            if ($result['moded'] == 'homeFloorFour' || $result['moded'] == 'homeFloorFive' || $result['moded'] == 'homeFloorSeven' ||
                $result['moded'] == 'homeFloorEight' || $result['moded'] == 'homeFloorTen' || $result['moded'] == 'storeOneFloor1' ||
                $result['moded'] == 'storeTwoFloor1' || $result['moded'] == 'storeThreeFloor1' || $result['moded'] == 'storeFourFloor1' ||
                $result['moded'] == 'storeFiveFloor1' || $result['moded'] == 'topicOneFloor' || $result['moded'] == 'topicTwoFloor' ||
                $result['moded'] == 'topicThreeFloor' || ($result['moded'] == 'homeFloorSix' && $result['floorMode'] != 1)
            ) {
                $adminru = get_admin_ru_id();
                $where_goods = "WHERE 1 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND g.review_status > 2  AND g.user_id = '" . $adminru['ru_id'] . "'";
                if ($result['top_goods']) {
                    $where_goods .= " AND g.goods_id in (" . $result['top_goods'] . ")";
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

                    $where_goods .= " AND (g.cat_id " . db_create_in($children) . " OR g.goods_id " . db_create_in($extension_goods) . ")";
                }
                $limit = " LIMIT 8";
                $goods_num = -1;
                if ($result['moded'] == 'homeFloorFour') {
                    if ($result['floorMode'] == 1) {
                        $goods_num = 3;
                    } elseif ($result['floorMode'] == 2) {
                        $limit = " LIMIT 10";
                    } elseif ($result['floorMode'] == 3) {
                        $limit = " LIMIT 10";
                    } elseif ($result['floorMode'] == 4) {
                        $limit = " LIMIT 12";
                    }
                } elseif ($result['moded'] == 'homeFloorSix') {
                    if ($result['floorMode'] == 2) {
                        $limit = " LIMIT 4";
                    } elseif ($result['floorMode'] == 3) {
                        $limit = " LIMIT 6";
                    }
                } elseif ($result['moded'] == 'homeFloorSeven') {
                    $limit = " LIMIT 6";
                } elseif ($result['moded'] == 'storeOneFloor1') {
                    $limit = "LIMIT 6";
                    if ($result['floorMode'] == 3) {
                        $limit = " LIMIT 6";
                        if ($result['top_goods'] != '') {
                            $limit = '';
                        }
                    } elseif ($result['floorMode'] == 4) {
                        $limit = " LIMIT 8";
                        if ($result['top_goods'] != '') {
                            $limit = '';
                        }
                    }
                } elseif ($result['moded'] == 'storeTwoFloor1') {
                    if ($result['floorMode'] == 2) {
                        $limit = " LIMIT 6";
                    } elseif ($result['floorMode'] == 3) {
                        $limit = " LIMIT 8";
                    }

                    if ($result['top_goods'] != '') {
                        $limit = '';
                    }
                } elseif ($result['moded'] == 'storeThreeFloor1') {
                    if ($result['floorMode'] == 2) {
                        $limit = " LIMIT 6";
                    } elseif ($result['floorMode'] == 3) {
                        $limit = " LIMIT 10";
                    } elseif ($result['floorMode'] == 4) {
                        $limit = " LIMIT 8";
                    }
                } elseif ($result['moded'] == 'storeFourFloor1') {
                    if ($result['floorMode'] == 3) {
                        $limit = " LIMIT 6";
                    } elseif ($result['floorMode'] == 4) {
                        $limit = " LIMIT 12";
                    }
                } elseif ($result['moded'] == 'storeFiveFloor1') {
                    if ($result['floorMode'] == 1) {
                        $limit = " LIMIT 6";
                    } else {
                        $limit = " LIMIT 8";
                        if ($result['top_goods'] != '') {
                            $limit = '';
                        }
                    }
                } elseif ($result['moded'] == 'topicOneFloor') {
                    if ($result['floorMode'] == 2) {
                        $limit = " LIMIT 6";
                    } elseif ($result['floorMode'] == 3) {
                        $limit = " LIMIT 8";
                    }

                    if ($result['top_goods'] != '') {
                        $limit = '';
                    }
                } elseif ($result['moded'] == 'topicTwoFloor') {
                    $limit = " LIMIT 10";
                } elseif ($result['moded'] == 'topicThreeFloor') {
                    if ($result['floorMode'] == 1) {
                        $limit = " LIMIT 8";
                    } elseif ($result['floorMode'] == 3) {
                        $limit = " LIMIT 10";
                    }
                }
                $sql = "SELECT g.promote_start_date, g.promote_end_date, g.promote_price, g.goods_name, g.goods_id, g.goods_thumb, g.shop_price, g.market_price, g.original_img  FROM " .
                    $this->dsc->table('goods') . " AS g " . $where_goods . " ORDER BY g.sort_order, g.goods_id DESC " . $limit;

                $goods_list = $this->db->getAll($sql);

                $time = gmtime();
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
            $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 获取选择商品内        容模板
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'getsearchgoodsDiv') {
            $result = [];
            $goods_ids = !empty($_REQUEST['goods_ids']) ? trim($_REQUEST['goods_ids']) : '';
            $pbtype = !empty($_REQUEST['pbtype']) ? trim($_REQUEST['pbtype']) : '';
            $goods_list = [];
            $back_goods = '';
            if ($goods_ids) {
                $where = "WHERE g.is_delete=0 ";
                $where .= " AND g.goods_id in ($goods_ids)";
                $where .= " AND g.user_id = '" . $adminru['ru_id'] . "' ";
                //ecmoban模板堂 --zhuo start
                if ($GLOBALS['_CFG']['review_goods'] == 1) {
                    $where .= ' AND g.review_status > 2 ';
                }
                $sql = "SELECT  g.goods_name, g.goods_id  FROM " .
                    $this->dsc->table('goods') . " AS g " . $where;
                $goods_list = $this->db->getAll($sql);
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
            $result['content'] = $GLOBALS['smarty']->fetch('library/getsearchgoodsdiv.lbi');
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 添加秒杀商品
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_seckill_goods') {
            $goods_ids = empty($_REQUEST['goods_ids']) ? '' : trim($_REQUEST['goods_ids']);
            $sec_id = empty($_REQUEST['sec_id']) ? 0 : intval($_REQUEST['sec_id']);
            $tb_id = empty($_REQUEST['tb_id']) ? 0 : intval($_REQUEST['tb_id']);
            $result = ['error' => 0, 'message' => '', 'content' => '', 'goods_ids' => ''];
            if ($goods_ids) {
                $goods_ids_arr = explode(',', $goods_ids);
                foreach ($goods_ids_arr as $val) {
                    $sql = "SELECT id FROM " . $this->dsc->table('seckill_goods') . " WHERE goods_id = '$val' AND sec_id = '$sec_id' AND tb_id = '$tb_id'";
                    if (!$this->db->getOne($sql)) {
                        $sql = "INSERT INTO " . $this->dsc->table('seckill_goods') . " (sec_id, tb_id, goods_id) " .
                            "VALUES ('$sec_id','$tb_id', '$val')";
                        $this->db->query($sql);
                    }
                }
                $list = get_add_seckill_goods($sec_id, $tb_id);

                //获取秒杀商品ID列表
                $sql = " SELECT GROUP_CONCAT(goods_id) FROM " . $this->dsc->table("seckill_goods") . " WHERE sec_id = '$sec_id' AND tb_id = '$tb_id' ";
                $result['goods_ids'] = $this->db->getOne($sql);

                $this->smarty->assign('seckill_goods', $list['seckill_goods']);
                $this->smarty->assign('filter', $list['filter']);
                $this->smarty->assign('record_count', $list['record_count']);
                $this->smarty->assign('page_count', $list['page_count']);
                $result['content'] = $this->smarty->fetch('seckill_set_goods_info.dwt');
                return response()->json($result);
            }
        }
        /* ------------------------------------------------------ */
        //-- 添加属性分类
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_goods_type_cat') {
            $exc_cat = new Exchange($this->dsc->table("goods_type_cat"), $this->db, 'cat_id', 'cat_name');
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $cat_name = !empty($_REQUEST['cat_name']) ? trim($_REQUEST['cat_name']) : '';
            $parent_id = !empty($_REQUEST['attr_parent_id']) ? intval($_REQUEST['attr_parent_id']) : 0;
            $sort_order = !empty($_REQUEST['sort_order']) ? intval($_REQUEST['sort_order']) : 50;
            $user_id = $adminru['ru_id'];
            //获取入库分类的层级
            if ($parent_id > 0) {
                $sql = "SELECT level FROM" . $this->dsc->table("goods_type_cat") . " WHERE cat_id = '$parent_id' LIMIT 1";
                $level = ($this->db->getOne($sql)) + 1;
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
            $where = " user_id = '$user_id'";
            $is_only = $exc_cat->is_only('cat_name', $cat_name, 0, $where);
            if (!$is_only) {
                $result['error'] = 1;
                $result['message'] = sprintf($GLOBALS['_LANG']['exist_cat'], stripslashes($cat_name));
                return response()->json($result);
            }

            $this->db->autoExecute($this->dsc->table('goods_type_cat'), $cat_info, 'INSERT');
            $cat_id = $this->db->insert_id();

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
            $result['content'] = $GLOBALS['smarty']->fetch('library/type_cat_list.lbi');
            return response()->json($result);
        }
        /* ------------------------------------------------------ */
        //-- 商品类型商品库
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_goods_type') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $parent_id = !empty($_REQUEST['attr_parent_id']) ? intval($_REQUEST['attr_parent_id']) : 0;
            $goods_type['cat_name'] = $this->dscRepository->subStr($_POST['cat_name'], 60);
            $goods_type['attr_group'] = $this->dscRepository->subStr($_POST['attr_group'], 255);
            $goods_type['user_id'] = $adminru['ru_id'];
            $goods_type['c_id'] = $parent_id;
            $this->db->autoExecute($this->dsc->table('goods_type'), $goods_type);
            $result['type_id'] = $this->db->insert_id();
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

            $result['content'] = $GLOBALS['smarty']->fetch('library/type_cat_list.lbi');
            return response()->json($result);
        }
        /* ------------------------------------------------------ */
        //-- 商品类型�        �库
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'attribute_add') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $cat_id = isset($_REQUEST['cat_id']) && !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;
            $sort_order = isset($_POST['sort_order']) && !empty($_POST['sort_order']) ? $_POST['sort_order'] : 0;
            $exc = new Exchange($this->dsc->table("attribute"), $this->db, 'attr_id', 'attr_name');

            /* 检查名称是否重复 */
            $exclude = empty($_POST['attr_id']) ? 0 : intval($_POST['attr_id']);
            if (!$exc->is_only('attr_name', $_POST['attr_name'], $exclude, " cat_id = '$cat_id'")) {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['attr_name_repeat'];
                return response()->json($result);
            }
            /* 取得属性信息 */
            $attr = [
                'cat_id' => $cat_id,
                'attr_name' => $_POST['attr_name'],
                'attr_cat_type' => $_POST['attr_cat_type'], //by zhang
                'attr_index' => $_POST['attr_index'],
                'sort_order' => $sort_order,
                'attr_input_type' => $_POST['attr_input_type'],
                'is_linked' => $_POST['is_linked'],
                'attr_values' => isset($_POST['attr_values']) ? $_POST['attr_values'] : '',
                'attr_type' => empty($_POST['attr_type']) ? '0' : intval($_POST['attr_type']),
                'attr_group' => isset($_POST['attr_group']) ? intval($_POST['attr_group']) : 0
            ];
            $this->db->autoExecute($this->dsc->table('attribute'), $attr, 'INSERT');
            $attr_id = $this->db->insert_id();

            $sql = "SELECT MAX(sort_order) AS sort FROM " . $this->dsc->table('attribute') . " WHERE cat_id = '$cat_id'";
            $sort = $this->db->getOne($sql);

            if (empty($attr['sort_order']) && !empty($sort)) {
                $attr = [
                    'sort_order' => $attr_id,
                ];
                $this->db->autoExecute($this->dsc->table('attribute'), $attr, 'UPDATE', "attr_id = '$attr_id'");
            }
            $result['type_id'] = $cat_id;
            admin_log($_POST['attr_name'], 'add', 'attribute');

            return response()->json($result);
        }
    }

    /* 上传文件 */
    private function upload_article_file($upload, $file = '')
    {
        $file_dir = storage_public(DATA_DIR . "/gallery_album");
        if (!file_exists($file_dir)) {
            if (!make_dir($file_dir)) {
                /* 创建目录失败 */
                return false;
            }
        }

        $filename = app(Image::class)->random_filename() . substr($upload['name'], strpos($upload['name'], '.'));
        $path = storage_public(DATA_DIR . "/gallery_album/" . $filename);
        if (move_upload_file($upload['tmp_name'], $path)) {
            return DATA_DIR . "/gallery_album/" . $filename;
        } else {
            return false;
        }
    }

    //获取橱窗商品
    private function get_win_goods($id)
    {
        $adminru = get_admin_ru_id();
        $sql = "select id,win_goods from " . $this->dsc->table('seller_shopwindow') . " where id='$id' and ru_id='" . $adminru['ru_id'] . "'";

        $win_info = $this->db->getRow($sql);

        if ($win_info['id'] > 0) {
            $goods_ids = $win_info['win_goods'];
            $goods = [];
            if ($goods_ids) {
                $sql = "select goods_id,goods_name from " . $this->dsc->table('goods') . " where user_id='" . $adminru['ru_id'] . "' and goods_id in ($goods_ids)";
                $goods = $this->db->getAll($sql);
            }
            //return $goods;
            $opt = []; //by wu

            foreach ($goods as $val) {
                $opt[] = ['value' => $val['goods_id'],
                    'text' => $val['goods_name'],
                    'data' => ''];
            }
            return $opt;
        } else {
            return 'no_cc';
        }
    }

    /**
     * 获取商品类型属性
     */
    private function get_attributes($cat_id = 0)
    {
        $sql = "SELECT `attr_id`, `cat_id`, `attr_name` FROM " . $this->dsc->table('attribute') . " ";
        if (!empty($cat_id)) {
            $cat_id = intval($cat_id);
            $sql .= " WHERE `cat_id` = '{$cat_id}' ";
        }
        $sql .= " ORDER BY `cat_id` ASC, `attr_id` ASC ";
        $attributes = [];
        $query = $this->db->query($sql);
        foreach ($query as $row) {
            $attributes[$row['attr_id']] = $row['attr_name'];
        }
        return $attributes;
    }

    /*------------------------------------------------------ */
    //-- 设置是否显示
    /*------------------------------------------------------ */
    private function set_show_in_nav($type, $id, $val)
    {
        if ($type == 'c') {
            $tablename = $this->dsc->table('category');
        } else {
            $tablename = $this->dsc->table('article_cat');
        }
        $this->db->query("UPDATE $tablename SET show_in_nav = '$val' WHERE cat_id = '$id'");
        clear_cache_files();
    }

    /**
     * 获取品牌列表
     *
     * @access  public
     * @return  array
     */
    private function get_brandlist($filters)
    {
        $adminru = get_admin_ru_id();
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
        $where .= " AND g.user_id = '" . $adminru['ru_id'] . "' ";
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
