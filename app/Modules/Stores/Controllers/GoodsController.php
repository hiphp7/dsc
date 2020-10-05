<?php

namespace App\Modules\Stores\Controllers;

use App\Models\Goods;
use App\Models\StoreGoods;
use App\Models\StoreProducts;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Category\CategoryService;
use App\Services\Goods\GoodsAttrService;

/**
 * 商品管理程序
 */
class GoodsController extends InitController
{
    protected $categoryService;
    protected $dscRepository;
    protected $goodsAttrService;

    public function __construct(
        CategoryService $categoryService,
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        GoodsAttrService $goodsAttrService
    )
    {
        $this->categoryService = $categoryService;
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
        $this->goodsAttrService = $goodsAttrService;
    }

    public function index()
    {
        load_helper('goods', 'stores');

        //ecmoban模板堂 --zhuo start
        $adminru = get_store_ru_id();
        if ($adminru == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        $this->smarty->assign('review_goods', $GLOBALS['_CFG']['review_goods']);
        //ecmoban模板堂 --zhuo end

        $store_id = session()->has('stores_id') ? intval(session('stores_id')) : 0;
        $ru_id = $this->db->getOne(" SELECT ru_id FROM " . $this->dsc->table('offline_store') . " WHERE id = '$store_id'", true);
        $this->smarty->assign("app", "goods");

        //设置logo
        $sql = "SELECT value FROM " . $this->dsc->table('shop_config') . " WHERE code = 'stores_logo'";
        $stores_logo = strstr($this->db->getOne($sql), "images");
        $this->smarty->assign('stores_logo', $stores_logo);


        /*------------------------------------------------------ */
        //-- 商品列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            store_priv('goods_manage'); //检查权限
            $list = $this->store_goods_list($ru_id, $store_id);
            $this->smarty->assign('goods_list', $list['goods_list']);

            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);
            $this->smarty->assign('full_page', 1);

            $this->smarty->assign('brand_list', get_brand_list(0, 0, $adminru));
            set_default_filter(0, 0, $adminru); //设置默认筛选
            $this->smarty->assign('page_title', $GLOBALS['_LANG']['store_goods']);
            return $this->smarty->display('goods_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 查询
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'query') {
            $list = $this->store_goods_list($ru_id, $store_id);
            $this->smarty->assign('goods_list', $list['goods_list']);

            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('goods_list.dwt'), '', ['filter' => $list['filter'], 'page_count' => $list['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 商品库存
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'info') {
            store_priv('goods_manage'); //检查权限
            /* 是否存在商品id */
            if (empty($_GET['goods_id'])) {
                $link[] = ['href' => 'goods.php?act=list', 'text' => $GLOBALS['_LANG']['cannot_found_goods']];
                return sys_msg($GLOBALS['_LANG']['cannot_found_goods'], 1, $link);
            } else {
                $goods_id = intval($_GET['goods_id']);
            }

            /* 取出商品信息 */
            $sql = "SELECT goods_sn, goods_name, goods_type, shop_price, model_attr, goods_thumb FROM " . $this->dsc->table('goods') . " WHERE goods_id = '$goods_id'";
            $goods = $this->db->getRow($sql);

            //当前域名协议
            $http = $this->dsc->http();

            //图片显示
            if (isset($goods['goods_thumb'])) {
                $goods['goods_thumb'] = get_image_path($goods['goods_thumb']);
            }

            if (empty($goods)) {
                $link[] = ['href' => 'goods.php?act=list', 'text' => $GLOBALS['_LANG']['01_goods_list']];
                return sys_msg($GLOBALS['_LANG']['cannot_found_goods'], 1, $link);
            }
            $this->smarty->assign('goods', $goods);

            $this->smarty->assign('sn', sprintf($GLOBALS['_LANG']['good_goods_sn'], $goods['goods_sn']));
            $this->smarty->assign('price', sprintf($GLOBALS['_LANG']['good_shop_price'], $goods['shop_price']));
            $this->smarty->assign('goods_name', sprintf($GLOBALS['_LANG']['products_title'], $goods['goods_name']));
            $this->smarty->assign('goods_sn', sprintf($GLOBALS['_LANG']['products_title_2'], $goods['goods_sn']));
            $this->smarty->assign('model_attr', $goods['model_attr']);

            /* 检查是否有属性 */
            $have_goods_attr = $this->have_goods_attr($goods_id);
            $this->smarty->assign('have_goods_attr', $have_goods_attr);

            if ($have_goods_attr) {
                /* 获取商品规格列表 */
                $attribute = get_goods_specifications_list($goods_id);
                if (empty($attribute)) {
                    $link[] = ['href' => 'goods.php?act=edit&goods_id=' . $goods_id, 'text' => $GLOBALS['_LANG']['edit_goods']];
                    return sys_msg($GLOBALS['_LANG']['not_exist_goods_attr'], 1, $link);
                }
                foreach ($attribute as $attribute_value) {
                    //转换成数组
                    $_attribute[$attribute_value['attr_id']]['attr_values'][] = $attribute_value['attr_value'];
                    $_attribute[$attribute_value['attr_id']]['attr_id'] = $attribute_value['attr_id'];
                    $_attribute[$attribute_value['attr_id']]['attr_name'] = $attribute_value['attr_name'];
                }
                $attribute_count = count($_attribute);

                $this->smarty->assign('attribute_count', $attribute_count);
                $this->smarty->assign('attribute_count_3', ($attribute_count + 3));
                $this->smarty->assign('attribute', $_attribute);
                $this->smarty->assign('product_sn', $goods['goods_sn'] . '_');
                $this->smarty->assign('product_number', $GLOBALS['_CFG']['default_storage']);

                /* 取商品的货品 */
                $product = product_list($goods_id, " AND store_id = '$store_id' ");

                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['18_product_list']);
                $this->smarty->assign('action_link', ['href' => 'goods.php?act=list', 'text' => $GLOBALS['_LANG']['01_goods_list']]);
                $this->smarty->assign('product_list', $product['product']);
                $this->smarty->assign('product_null', empty($product['product']) ? 0 : 1);
                $this->smarty->assign('use_storage', empty($GLOBALS['_CFG']['use_storage']) ? 0 : 1);
                $this->smarty->assign('filter', $product['filter']);
                $this->smarty->assign('more_count', $product['filter']['record_count'] + 1); //by wu

                $this->smarty->assign('product_php', 'goods.php');
            }

            $this->smarty->assign('goods_number', $this->get_default_store_goods_number($goods_id, $store_id));
            $this->smarty->assign('full_page', 1);
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $this->smarty->assign('page', $page);
            $this->smarty->assign('goods_id', $goods_id);


            $this->smarty->assign('page_title', $GLOBALS['_LANG']['set_inventory']);
            return $this->smarty->display('goods_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 检查货号
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'check_products_goods_sn') {
            $goods_id = isset($_REQUEST['goods_id']) && !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $goods_sn = isset($_REQUEST['goods_sn']) && !empty($_REQUEST['goods_sn']) ? json_str_iconv(trim($_REQUEST['goods_sn'])) : '';

            $products_sn = $goods_sn ? explode('||', $goods_sn) : [];
            if (!is_array($products_sn)) {
                return make_json_result('');
            } else {
                if ($goods_sn) {
                    foreach ($products_sn as $val) {
                        if (empty($val)) {
                            continue;
                        }
                        if (is_array($int_arry)) {
                            if (in_array($val, $int_arry)) {
                                return make_json_error($val . $GLOBALS['_LANG']['goods_sn_exists']);
                            }
                        }
                        $int_arry[] = $val;
                        if (!$exc->is_only('goods_sn', $val, '0')) {
                            return make_json_error($val . $GLOBALS['_LANG']['goods_sn_exists']);
                        }
                        $sql = "SELECT goods_id FROM " . $this->dsc->table('store_products') . "WHERE product_sn='$val'";
                        if ($this->db->getOne($sql)) {
                            return make_json_error($val . $GLOBALS['_LANG']['goods_sn_exists']);
                        }
                    }
                }
            }

            /* 检查是否重复 */
            return make_json_result('');
        }

        /*------------------------------------------------------ */
        //-- 库存更新
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'product_add_execute') {
            store_priv('goods_manage'); //检查权限

            $goods_id = intval($_POST['goods_id']);
            $page = intval($_POST['page']);
            $goods_number = empty($_POST['goods_number']) ? 0 : intval($_POST['goods_number']);
            $have_goods_attr = $this->have_goods_attr($goods_id);
            $where = " AND goods_id = '$goods_id' AND store_id = '$store_id' ";

            /* 更新常规库存 start */
            $sql = " SELECT id FROM " . $this->dsc->table('store_goods') . " WHERE 1 " . $where;
            $have_data = $this->db->getOne($sql);
            if ($have_data) {
                $sql = " UPDATE " . $this->dsc->table('store_goods') . " SET goods_number = '$goods_number' WHERE 1 " . $where;
                $this->db->query($sql);
            } else {
                $sql = " INSERT INTO " . $this->dsc->table('store_goods') . " (id, goods_id, store_id, ru_id, goods_number) VALUES " .
                    " (NULL, '$goods_id', '$store_id', '$ru_id', '$goods_number') ";
                $this->db->query($sql);
            }
            /* 更新常规库存 end */

            if ($have_goods_attr) {
                $product['goods_id'] = intval($_POST['goods_id']);
                $product['attr'] = $_POST['attr'];
                $product['product_sn'] = $_POST['product_sn'];
                $product['product_number'] = $_POST['product_number'];

                /* 是否存在商品id */
                if (empty($product['goods_id'])) {
                    return make_json_response('', 0, $GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['cannot_found_goods']);
                }

                /* 取出商品信息 */
                $sql = "SELECT goods_sn, goods_name, goods_type, shop_price FROM " . $this->dsc->table('goods') . " WHERE goods_id = '" . $product['goods_id'] . "'";
                $goods = $this->db->getRow($sql);
                if (empty($goods)) {
                    return make_json_response('', 0, $GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['cannot_found_goods']);
                }

                /*  */
                foreach ($product['product_sn'] as $key => $value) {
                    //过滤
                    $product['product_number'][$key] = empty($product['product_number'][$key]) ? (empty($GLOBALS['_CFG']['use_storage']) ? 0 : $GLOBALS['_CFG']['default_storage']) : trim($product['product_number'][$key]); //库存
                    //获取规格在商品属性表中的id
                    foreach ($product['attr'] as $attr_key => $attr_value) {
                        /* 检测：如果当前所添加的货品规格存在空值或0 */
                        if (empty($attr_value[$key])) {
                            continue 2;
                        }

                        $is_spec_list[$attr_key] = 'true';

                        $value_price_list[$attr_key] = $attr_value[$key] . chr(9) . ''; //$key，当前

                        $id_list[$attr_key] = $attr_key;
                    }
                    $goods_attr_id = handle_goods_attr($product['goods_id'], $id_list, $is_spec_list, $value_price_list);

                    /* 是否为重复规格的货品 */
                    $goods_attr = $this->goodsAttrService->sortGoodsAttrIdArray($goods_attr_id);
                    $goods_attr = implode('|', $goods_attr['sort']);
                    if (check_goods_attr_exist($goods_attr, $product['goods_id'])) {
                        continue;
                    }
                    //货品号不为空
                    if (!empty($value)) {
                        /* 检测：货品货号是否在商品表和货品表中重复 */
                        if (check_goods_sn_exist($value)) {
                            continue;
                        }
                        if (check_product_sn_exist($value)) {
                            continue;
                        }
                    }

                    /* 插入货品表 */
                    $sql = "INSERT INTO " . $this->dsc->table('store_products') . " (goods_id, goods_attr, product_sn, product_number, ru_id, store_id)  VALUES ('" . $product['goods_id'] . "', '$goods_attr', '$value', '" . $product['product_number'][$key] . "', '$ru_id', '$store_id' )";
                    if (!$this->db->query($sql)) {
                        continue;
                    }

                    //货品号为空 自动补货品号
                    if (empty($value)) {
                        $sql = "UPDATE " . $this->dsc->table('store_products') . "
						SET product_sn = '" . $goods['goods_sn'] . "g_p" . $this->db->insert_id() . "'
						WHERE product_id = '" . $this->db->insert_id() . "'";
                        $this->db->query($sql);
                    }
                }

            } else {
                //清空属性库存
                $sql = "DELETE FROM" . $this->dsc->table('store_products') . "WHERE goods_id = '$goods_id' AND store_id = '$store_id'";
                $this->db->query($sql);
            }

            clear_cache_files();

            return make_json_response('', 1, $GLOBALS['_LANG']['edit_succeed'], ['url' => 'goods.php?act=list&page=' . $page, 'page' => $page]);
        }

        /*------------------------------------------------------ */
        //-- 货品删除
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'product_remove') {
            store_priv('goods_manage'); //检查权限

            $product_id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            /* 是否存在商品id */
            if (empty($product_id)) {
                return make_json_error($GLOBALS['_LANG']['product_id_null']);
            }

            /* 删除货品 */
            $sql = "DELETE FROM " . $this->dsc->table('store_products') . " WHERE product_id = '$product_id'";
            $result = $this->db->query($sql);

            if ($result) {
                $url = 'goods.php?act=product_query&' . str_replace('act=product_remove', '', request()->server('QUERY_STRING'));

                return dsc_header("Location: $url\n");
            }
        }

        /*------------------------------------------------------ */
        //-- 货品排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'product_query') {
            /* 是否存在商品id */
            if (empty($_REQUEST['goods_id'])) {
                return make_json_error($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['cannot_found_goods']);
            } else {
                $goods_id = intval($_REQUEST['goods_id']);
            }

            /* 检查是否有属性 */
            $have_goods_attr = $this->have_goods_attr($goods_id);
            $this->smarty->assign('have_goods_attr', $have_goods_attr);

            if ($have_goods_attr) {
                /* 获取商品规格列表 */
                $attribute = get_goods_specifications_list($goods_id);
                if (empty($attribute)) {
                    $link[] = ['href' => 'goods.php?act=edit&goods_id=' . $goods_id, 'text' => $GLOBALS['_LANG']['edit_goods']];
                    return sys_msg($GLOBALS['_LANG']['not_exist_goods_attr'], 1, $link);
                }

                $_attribute = [];
                if ($attribute) {
                    foreach ($attribute as $attribute_value) {
                        //转换成数组
                        $_attribute[$attribute_value['attr_id']]['attr_values'][] = $attribute_value['attr_value'];
                        $_attribute[$attribute_value['attr_id']]['attr_id'] = $attribute_value['attr_id'];
                        $_attribute[$attribute_value['attr_id']]['attr_name'] = $attribute_value['attr_name'];
                    }
                }

                $attribute_count = count($_attribute);

                $this->smarty->assign('attribute_count', $attribute_count);
                $this->smarty->assign('attribute_count_3', ($attribute_count + 3));
                $this->smarty->assign('attribute', $_attribute);
                $this->smarty->assign('product_number', $GLOBALS['_CFG']['default_storage']);

                /* 取商品的货品 */
                $product = product_list($goods_id, " AND store_id = '$store_id' ");

                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['18_product_list']);
                $this->smarty->assign('action_link', ['href' => 'goods.php?act=list', 'text' => $GLOBALS['_LANG']['01_goods_list']]);
                $this->smarty->assign('product_list', $product['product']);
                $this->smarty->assign('product_null', empty($product['product']) ? 0 : 1);
                $this->smarty->assign('use_storage', empty($GLOBALS['_CFG']['use_storage']) ? 0 : 1);
                $this->smarty->assign('filter', $product['filter']);
                $this->smarty->assign('more_count', $product['filter']['record_count'] + 1); //by wu
            }

            /* 排序标记 */
            $sort_flag = sort_flag($product['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('goods_info.dwt'), '', ['filter' => $product['filter'], 'page_count' => $product['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 修改货品货号
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_product_sn') {
            $product_id = intval($_REQUEST['id']);

            $product_sn = json_str_iconv(trim($_POST['val']));
            $product_sn = ($GLOBALS['_LANG']['n_a'] == $product_sn) ? '' : $product_sn;

            if (check_product_sn_exist($product_sn, $product_id, $adminru['ru_id'], $store_id)) {
                return make_json_error($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['exist_same_product_sn']);
            }

            /* 修改 */
            $sql = "UPDATE " . $this->dsc->table('store_products') . " SET product_sn = '$product_sn' WHERE product_id = '$product_id'";
            $result = $this->db->query($sql);
            if ($result) {
                clear_cache_files();
                return make_json_result($product_sn);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改货品库存
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_product_number') {
            $product_id = intval($_POST['id']);
            $product_number = intval($_POST['val']);

            /* 货品库存 */
            $product = get_product_info($product_id, 'product_number, goods_id');

            /* 修改货品库存 */
            $sql = "UPDATE " . $this->dsc->table('store_products') . " SET product_number = '$product_number' WHERE product_id = '$product_id'";
            $result = $this->db->query($sql);

            if ($result) {
                clear_cache_files();
                return make_json_result($product_number);
            }
        } //ajax获取下级分类
        elseif ($_REQUEST['act'] == 'sel_cat_goodslist') {

            $res = ['error' => 0, 'message' => '', 'cat_level' => 0, 'content' => ''];

            $cat_id = !empty($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
            $cat_level = !empty($_GET['cat_level']) ? intval($_GET['cat_level']) : 0;

            if ($cat_id > 0) {
                $arr = $this->cat_list_one_new($cat_id, $cat_level);
            }
            $res['content'] = $arr;
            $res['parent_id'] = $cat_id;
            $res['cat_level'] = $cat_level;
            return response()->json($res);
        } //ajax获取下级分类
        elseif ($_REQUEST['act'] == 'batch_goods_number') {
            $checkboxes = !empty($_REQUEST['checkboxes']) ? $_REQUEST['checkboxes'] : '';
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;

            if (!empty($checkboxes)) {
                foreach ($checkboxes as $v) {
                    $sql = "SELECT goods_id,goods_sn FROM" . $this->dsc->table("goods") . " WHERE goods_id = '$v'";
                    $goods = $this->db->getRow($sql);
                    if ($goods['goods_id'] > 0) {
                        //清空默认库存
                        $sql = "DELETE FROM" . $this->dsc->table('store_goods') . "WHERE goods_id = '$v' AND store_id = '$store_id'";
                        $this->db->query($sql);
                        //清空属性库存
                        $sql = "DELETE FROM" . $this->dsc->table('store_products') . "WHERE goods_id = '$v' AND store_id = '$store_id'";
                        $this->db->query($sql);
                        //商品默认库存入库
                        $sql = "INSERT INTO" . $this->dsc->table('store_goods') . "(`goods_id`,`store_id`,`ru_id`,`goods_number`,`extend_goods_number`) SELECT '$v','$store_id','$ru_id',goods_number,'' FROM" . $this->dsc->table('goods') . "WHERE goods_id = '$v'";
                        $this->db->query($sql);
                        //商品属性库存入库
                        $sql = "SELECT * FROM" . $this->dsc->table('products') . "WHERE goods_id = '$v'";
                        $products = $this->db->getAll($sql);
                        if (!empty($products)) {
                            foreach ($products as $key => $val) {
                                $sql = "INSERT INTO" . $this->dsc->table('store_products') . "(`goods_id`,`store_id`,`ru_id`,`product_number`,`product_sn`,`goods_attr`) VALUES ('$v','$store_id','$ru_id','" . $val['product_number'] . "','','" . $val['goods_attr'] . "')";
                                $this->db->query($sql);
                                //货品号为空 自动补货品号
                                $product_id = $this->db->insert_id();
                                $sql = "UPDATE " . $this->dsc->table('store_products') . "
                                                        SET product_sn = '" . $goods['goods_sn'] . "g_p" . $product_id . "'
                                                        WHERE product_id = '$product_id'";
                                $this->db->query($sql);
                            }
                        }
                    } else {
                        continue;
                    }
                }
                return make_json_response('', 1, lang('stores/goods.synchronous_success'), ['url' => 'goods.php?act=list&page=' . $page, 'page' => $page]);
            } else {
                return make_json_response('', 2, lang('stores/goods.please_select_goods'), ['url' => 'goods.php?act=list&page=' . $page, 'page' => $page]);
            }
        }
    }

    /*------------------------------------------------------ */
    //-- 函数相�    �
    /*------------------------------------------------------ */
    /**
     * 组合 返回分类列表  图片批量处理和商品批量修改
     *
     */
    private function cat_list_one_new($cat_id = 0, $cat_level = 0)
    {
        if ($cat_id > 0) {
            $arr = $this->categoryService->catList($cat_id);

            foreach ($arr as $key => $value) {
                if ($key == $cat_id) {
                    unset($arr[$cat_id]);
                }
            }
            // 拼接字符串
            $str = '';
            if ($arr) {
                $cat_level++;

                $str .= '<div id="cat_id' . $cat_level . '" class="imitate_select w150 ml10"><div class="cite">' . lang('common.category') . '</div><ul>';
                $str .= '<li><a href="javascript:;" data-value="-1" data-level="' . $cat_level . '" class="ftx-01">' . lang('common.all_category') . '</a></li>';
                foreach ($arr as $key1 => $value1) {
                    $str .= '<li><a href="javascript:;" data-value="' . $value1['cat_id'] . '" data-level="' . $cat_level . '" class="ftx-01">' . $value1['cat_name'] . '</a></li>';
                }
                $str .= '</ul><input type="hidden" value="" id="cat_id_val' . $cat_level . '"></div>';
            }
            return $str;
        }
    }

    private function store_goods_list($ru_id = 0, $store_id = 0)
    {
        /* 过滤查询 */
        $filter = [];

        //ecmoban模板堂 --zhuo start
        $filter['keyword'] = !empty($_REQUEST['keyword']) ? trim($_REQUEST['keyword']) : '';
        $filter['cat_id'] = !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : -1;
        $filter['brand_id'] = !empty($_REQUEST['brand_id']) ? intval($_REQUEST['brand_id']) : -1;
        $filter['goods_type'] = !empty($_REQUEST['goods_type']) ? intval($_REQUEST['goods_type']) : -1;
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        //ecmoban模板堂 --zhuo end

        $filter['sort_by'] = 'goods_id';
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        /* 未删除、实体商品 */
        $goods = Goods::where('is_delete', 0)->where('is_real', 1);
        if ($filter['cat_id'] != -1) {
            $cats = $this->baseRepository->getExplode(get_children($filter['cat_id'], 2));
            $goods = $goods->whereIn('cat_id', $cats);
        }

        if ($filter['brand_id'] != -1) {
            $goods = $goods->where('brand_id', $filter['brand_id']);
        }

        /* 关键字 */
        if (!empty($filter['keyword'])) {
            $goods = $goods->where(function ($query) use ($filter) {
                $query->where('goods_sn', 'LIKE', '%' . mysql_like_quote($filter['keyword']) . '%')->orWhere('goods_name', 'LIKE', '%' . mysql_like_quote($filter['keyword']) . '%');
            });
        }

        /* 商家 */
        if ($ru_id > 0) {
            $goods = $goods->where('user_id', $ru_id);
        } else {
            $goods = $goods->where('user_id', 0);
        }
        if ($filter['goods_type'] != -1) {
            $goods_ids = $this->get_number_goods_id($store_id);

            if ($filter['goods_type'] == 1) {
                $goods = $goods->whereIn('goods_id', $goods_ids);
            } elseif ($filter['goods_type'] == 2) {
                $goods = $goods->whereNotIn('goods_id', $goods_ids);
            }
        }
        /* 获得总记录数据 */
        $filter['record_count'] = $goods->count();

        $filter = page_and_size($filter);

        /* 获得商品数据 */
        $arr = [];

        if (!empty($filter['sort_by'])) {
            $goods = $goods->orderBy($filter['sort_by'], $filter['sort_order']);
        }

        $goods = $goods->offset($filter['start'])->limit($filter['page_size']);

        $res = $this->baseRepository->getToArrayGet($goods);

        //当前域名协议
        $http = $this->dsc->http();

        $idx = 0;
        foreach ($res as $rows) {
            $rows['have_goods_attr'] = $this->have_goods_attr($rows['goods_id']);
            $rows['formated_shop_price'] = price_format($rows['shop_price']);
            $rows['store_goods_number'] = $this->get_store_goods_number($rows['goods_id'], $store_id);

            //图片显示
            $rows['goods_thumb'] = get_image_path($rows['goods_thumb']);
            $rows['url'] = $this->dscRepository->buildUri('goods', ['gid' => $rows['goods_id']], $rows['goods_name']);

            $arr[$idx] = $rows;
            $idx++;
        }

        return ['goods_list' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    //获取商品实际    总库存
    private function get_store_goods_number($goods_id = 0, $store_id = 0)
    {
        if ($store_id > 0 && $goods_id > 0) {
            $goods_info = StoreGoods::where('store_id', $store_id)->where('goods_id', $goods_id);
            $goods_info = $this->baseRepository->getToArrayFirst($goods_info);

            $product_number = $this->get_store_product_amount($goods_id, $store_id);
            if ($goods_info || $product_number) {
                if ($product_number != false) {
                    return $product_number;
                } else {
                    return $goods_info['goods_number'];
                }
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    //判断商品是否有属性
    private function have_goods_attr($goods_id = 0)
    {
        $where_select = ['goods_id' => $goods_id];
        $goods_attr_info = app(GoodsAttrService::class)->getGoodsAttrId($where_select, 1, 1);
        $goods_attr_id = $goods_attr_info['goods_attr_id'] ?? 0;

        return $goods_attr_id;
    }

    //判断商品是否有货品
    private function have_goods_products($goods_id = 0, $store_id = 0)
    {
        return StoreProducts::where('goods_id', $goods_id)->where('store_id', $store_id)->count();

    }

    private function get_number_goods_id($store_id)
    {
        $store_goods = StoreGoods::select('goods_id')->where('store_id', $store_id)->where('goods_number', '>', 0);
        $store_goods = $this->baseRepository->getToArrayGet($store_goods);

        $products_goods = StoreProducts::select('goods_id')->where('store_id', $store_id)->where('product_number', '>', 0);
        $products_goods = $this->baseRepository->getToArrayGet($products_goods);

        $store_goods_arr = [];
        $products_goods_arr = [];

        if ($store_goods) {
            $store_goods_arr = arr_foreach($store_goods);
        }
        if ($products_goods) {
            $products_goods_arr = arr_foreach($products_goods);
        }
        $arr = [];
        if (!empty($store_goods_arr) && empty($products_goods_arr)) {
            $arr = $store_goods_arr;
        } elseif (empty($store_goods_arr) && !empty($products_goods_arr)) {
            $arr = $products_goods_arr;
        } elseif (!empty($store_goods_arr) && !empty($products_goods_arr)) {
            $arr = array_unique(array_merge($store_goods_arr, $products_goods_arr));
        }

        return $arr ?? [];
    }

    //获取货品总库存
    private function get_store_product_amount($goods_id = 0, $store_id = 0)
    {
        if ($this->have_goods_products($goods_id, $store_id)) {
            $product_number = StoreProducts::where('goods_id', $goods_id)->where('store_id', $store_id)->sum('product_number');
            return !empty($product_number) ?? 0;
        } else {
            return false;
        }
    }

    //获取商品默认库存
    private function get_default_store_goods_number($goods_id = 0, $store_id = 0)
    {
        $goods_number = StoreGoods::where('store_id', $store_id)->where('goods_id', $goods_id)->value('goods_number');

        return !empty($goods_number) ?? 0;
    }
}
