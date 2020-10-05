<?php

namespace App\Modules\Seller\Controllers;

use App\Services\Goods\GoodsAttrService;

/**
 * 会员管理程序
 */
class GoodsAreaAttrController extends InitController
{
    protected $goodsAttrService;

    public function __construct(
        GoodsAttrService $goodsAttrService
    )
    {
        $this->goodsAttrService = $goodsAttrService;
    }

    public function index()
    {
        $adminru = get_admin_ru_id();

        /*------------------------------------------------------ */
        //-- 用户帐号列表
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'warehouse_list') {
            /* 检查权限 */
            admin_priv('goods_manage');
            $this->smarty->assign('ur_here');
            $goods_id = isset($_REQUEST['goods_id']) ? $_REQUEST['goods_id'] : 0;

            $date = ['goods_name'];

            $where = "goods_id = '$goods_id'";
            $goods_name = get_table_date('goods', $where, $date, 2);

            $this->smarty->assign('ur_here', $goods_name);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['01_goods_list'], 'href' => 'goods.php?act=list']);

            $area_list = $this->area_product_list($goods_id);

            session([
                'warehouse_goods_id' => $goods_id
            ]);

            $this->smarty->assign('goods_id', $goods_id);
            $this->smarty->assign('area_list', $area_list['area_list']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('sort_user_id', '<img src="__TPL__/images/sort_desc.gif">');


            $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => '01_goods_list']);
            return $this->smarty->display('goods_area_attr_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- ajax返回用户列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $area_list = $this->area_product_list();

            $this->smarty->assign('goods_id', session('warehouse_goods_id'));
            $this->smarty->assign('area_list', $area_list['area_list']);
            $this->smarty->assign('filter', $area_list['filter']);
            $this->smarty->assign('record_count', $area_list['record_count']);
            $this->smarty->assign('page_count', $area_list['page_count']);

            $sort_flag = sort_flag($area_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('goods_area_attr_list.dwt'), '', ['filter' => $area_list['filter'], 'page_count' => $area_list['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 货品列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'product_list') {
            admin_priv('goods_manage');

            $goods_id = isset($_REQUEST['goods_id']) ? $_REQUEST['goods_id'] : 0;
            $area_id = isset($_REQUEST['area_id']) ? $_REQUEST['area_id'] : 0;

            session([
                'product_area' => $area_id
            ]);

            /* 是否存在商品id */
            if (empty($goods_id)) {
                $link[] = ['href' => 'goods.php?act=list', 'text' => $GLOBALS['_LANG']['cannot_found_goods']];
                return sys_msg($GLOBALS['_LANG']['cannot_found_goods'], 1, $link);
            } else {
                $goods_id = intval($goods_id);
            }

            /* 取出商品信息 */
            $sql = "SELECT goods_sn, goods_name, goods_type, shop_price, model_attr FROM " . $this->dsc->table('goods') . " WHERE goods_id = '$goods_id'";
            $goods = $this->db->getRow($sql);
            if (empty($goods)) {
                $link[] = ['href' => 'goods.php?act=list', 'text' => $GLOBALS['_LANG']['01_goods_list']];
                return sys_msg($GLOBALS['_LANG']['cannot_found_goods'], 1, $link);
            }

            $this->smarty->assign('sn', sprintf($GLOBALS['_LANG']['good_goods_sn'], $goods['goods_sn']));
            $this->smarty->assign('price', sprintf($GLOBALS['_LANG']['good_shop_price'], $goods['shop_price']));
            $this->smarty->assign('goods_name', sprintf($GLOBALS['_LANG']['products_title'], $goods['goods_name']));
            $this->smarty->assign('goods_sn', sprintf($GLOBALS['_LANG']['products_title_2'], $goods['goods_sn']));
            $this->smarty->assign('model_attr', $goods['model_attr']);

            $region_name = get_table_date('region_warehouse', "region_id = '$area_id'", ['region_name'], 2);
            $this->smarty->assign('region_name', $region_name);
            $this->smarty->assign('area_id', $area_id);

            /* 获取商品规格列表 */
            $attribute = $this->get_goods_specifications_list($goods_id);
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
            $product = $this->product_area_list($goods_id, '', $area_id);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['18_product_list']);
            $this->smarty->assign('action_link', ['href' => 'goods.php?act=list', 'text' => $GLOBALS['_LANG']['01_goods_list']]);
            $this->smarty->assign('product_list', $product['product']);
            $this->smarty->assign('product_null', empty($product['product']) ? 0 : 1);
            $this->smarty->assign('use_storage', empty($GLOBALS['_CFG']['use_storage']) ? 0 : 1);
            $this->smarty->assign('goods_id', $goods_id);
            $this->smarty->assign('filter', $product['filter']);
            $this->smarty->assign('full_page', 1);


            $this->smarty->assign('product_php', 'goods_area_attr.php');
            $this->smarty->assign('batch_php', 'goods_produts_area_batch.php');

            /* 显示商品列表页面 */

            $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => '01_goods_list']);

            return $this->smarty->display('product_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 货品排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'product_query') {
            $area_id = isset($_REQUEST['area_id']) ? $_REQUEST['area_id'] : 0;

            /* 是否存在商品id */
            if (empty($_REQUEST['goods_id'])) {
                return make_json_error($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['cannot_found_goods']);
            } else {
                $goods_id = intval($_REQUEST['goods_id']);
            }

            /* 取出商品信息 */
            $sql = "SELECT goods_sn, goods_name, goods_type, shop_price FROM " . $this->dsc->table('goods') . " WHERE goods_id = '$goods_id'";
            $goods = $this->db->getRow($sql);
            if (empty($goods)) {
                return make_json_error($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['cannot_found_goods']);
            }
            $this->smarty->assign('sn', sprintf($GLOBALS['_LANG']['good_goods_sn'], $goods['goods_sn']));
            $this->smarty->assign('price', sprintf($GLOBALS['_LANG']['good_shop_price'], $goods['shop_price']));
            $this->smarty->assign('goods_name', sprintf($GLOBALS['_LANG']['products_title'], $goods['goods_name']));
            $this->smarty->assign('goods_sn', sprintf($GLOBALS['_LANG']['products_title_2'], $goods['goods_sn']));

            $region_name = get_table_date('region_warehouse', "region_id = '$area_id'", ['region_name'], 2);
            $this->smarty->assign('region_name', $region_name);
            $this->smarty->assign('area_id', $area_id);

            /* 获取商品规格列表 */
            $attribute = $this->get_goods_specifications_list($goods_id);
            if (empty($attribute)) {
                return make_json_error($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['cannot_found_goods']);
            }
            foreach ($attribute as $attribute_value) {
                //转换成数组
                $_attribute[$attribute_value['attr_id']]['attr_values'][] = $attribute_value['attr_value'];
                $_attribute[$attribute_value['attr_id']]['attr_id'] = $attribute_value['attr_id'];
                $_attribute[$attribute_value['attr_id']]['attr_name'] = $attribute_value['attr_name'];
            }
            $attribute_count = count($_attribute);

            $this->smarty->assign('attribute_count', $attribute_count);
            $this->smarty->assign('attribute', $_attribute);
            $this->smarty->assign('attribute_count_3', ($attribute_count + 10));
            $this->smarty->assign('product_sn', $goods['goods_sn'] . '_');
            $this->smarty->assign('product_number', $GLOBALS['_CFG']['default_storage']);

            /* 取商品的货品 */
            $product = $this->product_area_list($goods_id, '', $area_id);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['18_product_list']);
            $this->smarty->assign('action_link', ['href' => 'goods.php?act=list', 'text' => $GLOBALS['_LANG']['01_goods_list']]);
            $this->smarty->assign('product_list', $product['product']);
            $this->smarty->assign('use_storage', empty($GLOBALS['_CFG']['use_storage']) ? 0 : 1);
            $this->smarty->assign('goods_id', $goods_id);
            $this->smarty->assign('filter', $product['filter']);
            $this->smarty->assign('product_php', 'goods_area_attr.php');

            /* 排序标记 */
            $sort_flag = sort_flag($product['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('product_info.dwt'),
                '',
                ['filter' => $product['filter'], 'page_count' => $product['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 修改货品号
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_product_sn') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $product_id = intval($_REQUEST['id']);
            $product_sn = json_str_iconv(trim($_POST['val']));
            $product_sn = ($GLOBALS['_LANG']['n_a'] == $product_sn) ? '' : $product_sn;

            if ($this->check_product_area_sn_exist($product_sn, $product_id, $adminru['ru_id'])) {
                return make_json_error($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['exist_same_product_sn']);
            }

            /* 修改 */
            $sql = "UPDATE " . $this->dsc->table('products_area') . " SET product_sn = '$product_sn' WHERE product_id = '$product_id'";
            $result = $this->db->query($sql);
            if ($result) {
                clear_cache_files();
                return make_json_result($product_sn);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改条形码
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_bar_code') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $product_id = intval($_REQUEST['id']);
            $bar_code = json_str_iconv(trim($_POST['val']));

            if ($this->check_product_area_sn_exist($bar_code, $product_id, $adminru['ru_id'], 1)) {
                return make_json_error($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['exist_same_bar_code']);
            }

            /* 修改 */
            $sql = "UPDATE " . $this->dsc->table('products_area') . " SET bar_code = '$bar_code' WHERE product_id = '$product_id'";
            $result = $this->db->query($sql);
            if ($result) {
                clear_cache_files();
                return make_json_result($bar_code);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改货品库存
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_product_number') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $product_id = intval($_POST['id']);
            $product_number = intval($_POST['val']);

            /* 货品库存 */
            $product = get_product_area_info($product_id, 'product_number, area_id, goods_id');

            $add_number = true;
            if ($product['product_number'] != $product_number) {
                if ($product['product_number'] > $product_number) {
                    $number = $product['product_number'] - $product_number;

                    if ($number == 0) {
                        $add_number = false;
                    }

                    $number = "- " . $number;
                    $log_use_storage = 10;
                } else {
                    $number = $product_number - $product['product_number'];

                    if ($number == 0) {
                        $add_number = false;
                    }

                    $number = "+ " . $number;
                    $log_use_storage = 11;
                }

                $goods = get_admin_goods_info($product['goods_id']);

                if ($add_number == true) {

                    //库存日志
                    $logs_other = [
                        'goods_id' => $product['goods_id'],
                        'order_id' => 0,
                        'use_storage' => $log_use_storage,
                        'admin_id' => session('seller_id'),
                        'number' => $number,
                        'model_inventory' => $goods['model_inventory'],
                        'model_attr' => $goods['model_attr'],
                        'product_id' => $product_id,
                        'warehouse_id' => 0,
                        'area_id' => $product['area_id'],
                        'add_time' => gmtime()
                    ];

                    $this->db->autoExecute($this->dsc->table('goods_inventory_logs'), $logs_other, 'INSERT');
                }
            }

            /* 修改货品库存 */
            $sql = "UPDATE " . $this->dsc->table('products_area') . " SET product_number = '$product_number' WHERE product_id = '$product_id'";
            $result = $this->db->query($sql);

            if ($result) {
                clear_cache_files();
                return make_json_result($product_number);
            }
        }

        /*------------------------------------------------------ */
        //-- 货品删除
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'product_remove') {
            /* 检查权限 */
            $check_auth = check_authz_json('remove_back');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $product_id = intval($_REQUEST['id']);

            /* 是否存在商品id */
            if (empty($product_id)) {
                return make_json_error($GLOBALS['_LANG']['product_id_null']);
            } else {
                $product_id = intval($product_id);
            }

            /* 删除货品 */
            $sql = "DELETE FROM " . $this->dsc->table('products_area') . " WHERE product_id = '$product_id'";
            $result = $this->db->query($sql);

            if ($result) {
                $url = 'goods_warehouse_attr.php?act=product_query&warehouse_id=' . session('product_area') . '&' . str_replace('act=product_remove', '', request()->server('QUERY_STRING'));

                return dsc_header("Location: $url\n");
            }
        }

        /*------------------------------------------------------ */
        //-- 货品添加 执行
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'product_add_execute') {
            admin_priv('goods_manage');

            $product['goods_id'] = intval($_POST['goods_id']);
            $product['attr'] = $_POST['attr'];
            $product['product_sn'] = $_POST['product_sn'];
            $product['bar_code'] = $_POST['bar_code'];
            $product['product_price'] = $_POST['product_price'];
            $product['product_number'] = $_POST['product_number'];
            $product['area_id'] = $_POST['area_id'];

            /* 是否存在商品id */
            if (empty($product['goods_id'])) {
                return sys_msg($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['cannot_found_goods'], 1, [], false);
            }

            /* 判断是否为初次添加 */
            $insert = true;
            if ($this->product_warehouse_number_count($product['goods_id'], '', $product['area_id']) > 0) {
                $insert = false;
            }

            /* 取出商品信息 */
            $sql = "SELECT goods_sn, goods_name, goods_type, shop_price, model_inventory, model_attr FROM " . $this->dsc->table('goods') . " WHERE goods_id = '" . $product['goods_id'] . "'";
            $goods = $this->db->getRow($sql);
            if (empty($goods)) {
                return sys_msg($GLOBALS['_LANG']['sys']['wrong'] . $GLOBALS['_LANG']['cannot_found_goods'], 1, [], false);
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
                $goods_attr_id = $this->handle_goods_attr($product['goods_id'], $id_list, $is_spec_list, $value_price_list);

                /* 是否为重复规格的货品 */
                $goods_attr = $this->goodsAttrService->sortGoodsAttrIdArray($goods_attr_id);
                $goods_attr = implode('|', $goods_attr['sort']);
                if ($this->check_goods_attr_exist($goods_attr, $product['goods_id'], 0, $product['area_id'])) {
                    continue;
                }

                //货品号不为空
                if (!empty($value)) {
                    /* 检测：货品货号是否在商品表和货品表中重复 */
                    if ($this->check_goods_sn_exist($value)) {
                        continue;
                    }
                    if ($this->check_product_sn_exist($value)) {
                        continue;
                    }
                }

                /* 插入货品表 */
                $sql = "INSERT INTO " . $this->dsc->table('products_area') . " (goods_id, goods_attr, product_sn, bar_code, product_price, product_number, area_id)  VALUES ('" . $product['goods_id'] . "', '$goods_attr', '$value', '" . $product['bar_code'][$key] . "', '" . $product['product_price'][$key] . "', '" . $product['product_number'][$key] . "', '" . $product['area_id'] . "')";
                if (!$this->db->query($sql)) {
                    continue;
                }

                //库存日志
                $number = "+ " . $product['product_number'][$key];

                if ($product['product_number'][$key]) {
                    $logs_other = [
                        'goods_id' => $product['goods_id'],
                        'order_id' => 0,
                        'use_storage' => 9,
                        'admin_id' => session('seller_id'),
                        'number' => $number,
                        'model_inventory' => $goods['model_inventory'],
                        'model_attr' => $goods['model_attr'],
                        'product_id' => $this->db->insert_id(),
                        'warehouse_id' => 0,
                        'area_id' => $product['area_id'],
                        'add_time' => gmtime()
                    ];

                    $this->db->autoExecute($this->dsc->table('goods_inventory_logs'), $logs_other, 'INSERT');
                }

                //货品号为空 自动补货品号
                if (empty($value)) {
                    $sql = "UPDATE " . $this->dsc->table('products_area') . "
                    SET product_sn = '" . $goods['goods_sn'] . "g_p" . $this->db->insert_id() . "'
                    WHERE product_id = '" . $this->db->insert_id() . "'";
                    $this->db->query($sql);
                }

                /* 修改商品表库存 */
                $product_count = $this->product_warehouse_number_count($product['goods_id'], '', $product['area_id']);
                if ($this->update_warehouse_goods($product['goods_id'], 'goods_number', $product_count)) {
                    //记录日志
                    admin_log($product['goods_id'], 'update', 'goods');
                }
            }

            clear_cache_files();

            /* 返回 */
            if ($insert) {
                $link[] = ['href' => 'goods.php?act=add', 'text' => $GLOBALS['_LANG']['02_goods_add']];
                $link[] = ['href' => 'goods.php?act=list', 'text' => $GLOBALS['_LANG']['01_goods_list']];
                $link[] = ['href' => 'goods_area_attr.php?act=product_list&goods_id=' . $product['goods_id'] . '&area_id=' . $product['area_id'], 'text' => $GLOBALS['_LANG']['18_product_list']];
            } else {
                $link[] = ['href' => 'goods.php?act=list&uselastfilter=1', 'text' => $GLOBALS['_LANG']['01_goods_list']];
                $link[] = ['href' => 'goods.php?act=edit&goods_id=' . $product['goods_id'], 'text' => $GLOBALS['_LANG']['edit_goods']];
                $link[] = ['href' => 'goods_area_attr.php?act=product_list&goods_id=' . $product['goods_id'] . '&area_id=' . $product['area_id'], 'text' => $GLOBALS['_LANG']['18_product_list']];
            }
            return sys_msg($GLOBALS['_LANG']['save_products'], 0, $link);
        }
    }

    /**
     *  返回用户列表数据
     *
     * @access  public
     * @param
     *
     * @return void
     */
    private function area_product_list()
    {
        $result = get_filter();
        if ($result === false) {
            /* 过滤条件 */
            $filter['keywords'] = isset($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
            if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
                $filter['keywords'] = json_str_iconv($filter['keywords']);
            }

            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'region_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $ex_where = " where 1 and region_type = 1";
            if ($filter['keywords']) {
                $ex_where .= " AND region_name LIKE '%" . mysql_like_quote($filter['keywords']) . "%'";
            }

            $filter['record_count'] = $this->db->getOne("SELECT COUNT(*) FROM " . $this->dsc->table('region_warehouse') . $ex_where);

            /* 分页大小 */
            $filter = page_and_size($filter);
            $sql = "SELECT region_id, region_name " .
                " FROM " . $this->dsc->table('region_warehouse') . $ex_where;

            $filter['keywords'] = stripslashes($filter['keywords']);
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $area_list = $this->db->getAll($sql);

        $count = count($area_list);
        for ($i = 0; $i < $count; $i++) {
            $area_list[$i]['region_name'] = $area_list[$i]['region_name'];
        }


        /*  @author-bylu 取出各个地区的商品属性记录条数 start */
        foreach ($area_list as $k => $v) {
            $area_list[$k]['attr_typeNum'] = $this->db->getOne("SELECT COUNT(*) FROM " . $this->dsc->table('products_area') . " WHERE area_id='" . $v['region_id'] . "' ");
        }
        /*  @author-bylu  end */

        $arr = ['area_list' => $area_list, 'filter' => $filter,
            'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 获得商品已添加的规格列表
     *
     * @access      public
     * @params      integer         $goods_id
     * @return      array
     */
    private function get_goods_specifications_list($goods_id)
    {
        $where = "";
        $admin_id = get_admin_id();
        if (empty($goods_id)) {
            if ($admin_id) {
                $where .= " AND admin_id = '$admin_id'";
            } else {
                return [];  //$goods_id不能为空
            }
        }

        $sql = "SELECT g.goods_attr_id, g.attr_value, g.attr_id, a.attr_name
            FROM " . $this->dsc->table('goods_attr') . " AS g
                LEFT JOIN " . $this->dsc->table('attribute') . " AS a
                    ON a.attr_id = g.attr_id
            WHERE goods_id = '$goods_id'
            AND a.attr_type = 1" . $where .
            " ORDER BY a.sort_order, a.attr_id, g.goods_attr_id";
        $results = $this->db->getAll($sql);

        return $results;
    }

    /**
     * 获得商品的货品列表
     *
     * @access  public
     * @params  integer $goods_id
     * @params  string  $conditions
     * @return  array
     */
    private function product_area_list($goods_id, $conditions = '', $area_id)
    {
        /* 过滤条件 */
        $param_str = '-' . $goods_id;
        $result = get_filter($param_str);
        if ($result === false) {
            $day = getdate();
            $today = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);

            $filter['goods_id'] = $goods_id;
            $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);

            if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
                $filter['keyword'] = json_str_iconv($filter['keyword']);
            }
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'product_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);
            $filter['page_count'] = isset($filter['page_count']) ? $filter['page_count'] : 1;

            $where = '';

            /* 关键字 */
            if (!empty($filter['keyword'])) {
                $where .= " AND (product_sn LIKE '%" . $filter['keyword'] . "%')";
            }

            $where .= $conditions;
            $where .= " and area_id = '$area_id'";

            /* 记录总数 */
            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('products_area') . " AS p WHERE goods_id = $goods_id $where";
            $filter['record_count'] = $this->db->getOne($sql);

            $sql = "SELECT product_id, goods_id, goods_attr, product_sn, product_price, product_number, bar_code
                FROM " . $this->dsc->table('products_area') . " AS g
                WHERE goods_id = $goods_id $where
                ORDER BY $filter[sort_by] $filter[sort_order]";

            $filter['keyword'] = stripslashes($filter['keyword']);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }
        $row = $this->db->getAll($sql);

        /* 处理规格属性 */
        $goods_attr = $this->product_goods_attr_list($goods_id);
        foreach ($row as $key => $value) {
            $_goods_attr_array = explode('|', $value['goods_attr']);
            if (is_array($_goods_attr_array)) {
                $_temp = '';
                foreach ($_goods_attr_array as $_goods_attr_value) {
                    $_temp[] = $goods_attr[$_goods_attr_value];
                }
                $row[$key]['goods_attr'] = $_temp;
            }
        }

        return ['product' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    /**
     * 获得商品的规格属性值列表
     *
     * @access      public
     * @params      integer         $goods_id
     * @return      array
     */
    private function product_goods_attr_list($goods_id)
    {
        if (empty($goods_id)) {
            return [];  //$goods_id不能为空
        }

        $sql = "SELECT goods_attr_id, attr_value FROM " . $this->dsc->table('goods_attr') . " WHERE goods_id = '$goods_id'";
        $results = $this->db->getAll($sql);

        $return_arr = [];
        foreach ($results as $value) {
            $return_arr[$value['goods_attr_id']] = $value['attr_value'];
        }

        return $return_arr;
    }

    /**
     * 取货品信息
     *
     * @access  public
     * @param int $product_id 货品id
     * @param int $filed 字段
     * @return  array
     */
    private function get_product_warehouse_info($product_id, $filed = '')
    {
        $return_array = [];

        if (empty($product_id)) {
            return $return_array;
        }

        $filed = trim($filed);
        if (empty($filed)) {
            $filed = '*';
        }

        $sql = "SELECT $filed FROM  " . $this->dsc->table('products') . " WHERE product_id = '$product_id'";
        $return_array = $this->db->getRow($sql);

        return $return_array;
    }

    /**
     * 商品的货品货号是否重复
     *
     * @param string $product_sn 商品的货品货号；请在传入本参数前对本参数进行SQl脚本过滤
     * @param int $product_id 商品的货品id；默认值为：0，没有货品id
     * @return  bool                          true，重复；false，不重复
     */
    private function check_product_area_sn_exist($product_sn, $product_id = 0, $ru_id = 0, $type = 0)
    {
        $product_sn = trim($product_sn);
        $product_id = intval($product_id);
        if (strlen($product_sn) == 0) {
            return true;    //重复
        }

        if ($type == 1) {
            $sql = "SELECT g.goods_id FROM " . $this->dsc->table('goods') . " AS g WHERE g.bar_code='$product_sn' AND g.user_id = '$ru_id'";
            if ($this->db->getOne($sql)) {
                return true;    //重复
            }
        } else {
            $sql = "SELECT g.goods_id FROM " . $this->dsc->table('goods') . " AS g WHERE g.goods_sn='$product_sn' AND g.user_id = '$ru_id'";
            if ($this->db->getOne($sql)) {
                return true;    //重复
            }
        }

        $where = " AND (SELECT g.user_id FROM " . $this->dsc->table('goods') . " AS g WHERE g.goods_id = p.goods_id LIMIT 1) = '$ru_id'";

        if (empty($product_id)) {
            if ($type == 1) {
                $sql = "SELECT p.product_id FROM " . $this->dsc->table('products_area') . " AS p " . "
                    WHERE p.bar_code = '$product_sn'" . $where;
            } else {
                $sql = "SELECT p.product_id FROM " . $this->dsc->table('products_area') . " AS p " . "
                    WHERE p.product_sn = '$product_sn'" . $where;
            }
        } else {
            if ($type == 1) {
                $sql = "SELECT p.product_id FROM " . $this->dsc->table('products_area') . " AS p " . "
                    WHERE p.bar_code = '$product_sn'
                    AND p.product_id <> '$product_id'" . $where;
            } else {
                $sql = "SELECT p.product_id FROM " . $this->dsc->table('products_area') . " AS p " . "
                    WHERE p.product_sn = '$product_sn'
                    AND p.product_id <> '$product_id'" . $where;
            }
        }

        $res = $this->db->getOne($sql);

        if (empty($res)) {
            return false;    //不重复
        } else {
            return true;    //重复
        }
    }

    /**
     * 获得商品的货品总库存
     *
     * @access      public
     * @params      integer     $goods_id       商品id
     * @params      string      $conditions     sql条件，AND语句开头
     * @return      string number
     */
    private function product_warehouse_number_count($goods_id, $conditions = '', $area_id = 0)
    {
        if (empty($goods_id)) {
            return -1;  //$goods_id不能为空
        }

        $sql = "SELECT product_number
            FROM " . $this->dsc->table('products_area') . "
            WHERE goods_id = '$goods_id' and area_id = '$area_id'
            " . $conditions;
        $nums = $this->db->getOne($sql);
        $nums = empty($nums) ? 0 : $nums;

        return $nums;
    }

    /**
     * 修改商品某字段值
     * @param string $goods_id 商品编号，可以为多个，用 ',' 隔开
     * @param string $field 字段名
     * @param string $value 字段值
     * @return  bool
     */
    private function update_warehouse_goods($goods_id, $field, $value)
    {
        if ($goods_id) {
            /* 清除缓存 */
            clear_cache_files();

            $date = ['model_attr'];

            $where = "goods_id = '$goods_id'";
            $model_attr = get_table_date('goods', $where, $date, 2);

            if ($model_attr == 1) {
                $table = "warehouse_goods";
                $field = 'region_number';
            } elseif ($model_attr == 2) {
                $table = "warehouse_area_goods";
                $field = 'region_number';
            } else {
                $table = "goods";
            }

            $sql = "UPDATE " . $this->dsc->table($table) .
                " SET $field = '$value' , last_update = '" . gmtime() . "' " .
                "WHERE goods_id " . db_create_in($goods_id);
            return $this->db->query($sql);
        } else {
            return false;
        }
    }

    /**
     * 插入或更新商品属性
     *
     * @param int $goods_id 商品编号
     * @param array $id_list 属性编号数组
     * @param array $is_spec_list 是否规格数组 'true' | 'false'
     * @param array $value_price_list 属性值数组
     * @return  array                       返回受到影响的goods_attr_id数组
     */
    private function handle_goods_attr($goods_id, $id_list, $is_spec_list, $value_price_list)
    {
        $goods_attr_id = [];

        /* 循环处理每个属性 */
        foreach ($id_list as $key => $id) {
            $is_spec = $is_spec_list[$key];
            if ($is_spec == 'false') {
                $value = $value_price_list[$key];
                $price = '';
            } else {
                $value_list = [];
                $price_list = [];
                if ($value_price_list[$key]) {
                    $vp_list = explode(chr(13), $value_price_list[$key]);
                    foreach ($vp_list as $v_p) {
                        $arr = explode(chr(9), $v_p);
                        $value_list[] = $arr[0];
                        $price_list[] = $arr[1];
                    }
                }
                $value = join(chr(13), $value_list);
                $price = join(chr(13), $price_list);
            }

            // 插入或更新记录
            $sql = "SELECT goods_attr_id FROM " . $this->dsc->table('goods_attr') . " WHERE goods_id = '$goods_id' AND attr_id = '$id' AND attr_value = '$value' LIMIT 0, 1";
            $result_id = $this->db->getOne($sql);
            if (!empty($result_id)) {
                $sql = "UPDATE " . $this->dsc->table('goods_attr') . "
                    SET attr_value = '$value'
                    WHERE goods_id = '$goods_id'
                    AND attr_id = '$id'
                    AND goods_attr_id = '$result_id'";

                $goods_attr_id[$id] = $result_id;
            } else {
                $sql = "INSERT INTO " . $this->dsc->table('goods_attr') . " (goods_id, attr_id, attr_value, attr_price) " .
                    "VALUES ('$goods_id', '$id', '$value', '$price')";
            }

            $this->db->query($sql);

            if ($goods_attr_id[$id] == '') {
                $goods_attr_id[$id] = $this->db->insert_id();
            }
        }

        return $goods_attr_id;
    }

    /**
     * 商品的货品规格是否存在
     *
     * @param string $goods_attr 商品的货品规格
     * @param string $goods_id 商品id
     * @param int $product_id 商品的货品id；默认值为：0，没有货品id
     * @return  bool                          true，重复；false，不重复
     */
    private function check_goods_attr_exist($goods_attr, $goods_id, $product_id = 0, $area_id = 0)
    {
        $goods_id = intval($goods_id);
        if (strlen($goods_attr) == 0 || empty($goods_id)) {
            return true;    //重复
        }

        if (empty($product_id)) {
            $sql = "SELECT product_id FROM " . $this->dsc->table('products_area') . "
                WHERE goods_attr = '$goods_attr'
                AND goods_id = '$goods_id' and area_id = '$area_id'";
        } else {
            $sql = "SELECT product_id FROM " . $this->dsc->table('products_area') . "
                WHERE goods_attr = '$goods_attr'
                AND goods_id = '$goods_id'
                AND product_id <> '$product_id' and area_id = '$area_id'";
        }

        $res = $this->db->getOne($sql);

        if (empty($res)) {
            return false;    //不重复
        } else {
            return true;    //重复
        }
    }

    /**
     * 商品货号是否重复
     *
     * @param string $goods_sn 商品货号；请在传入本参数前对本参数进行SQl脚本过滤
     * @param int $goods_id 商品id；默认值为：0，没有商品id
     * @return  bool                        true，重复；false，不重复
     */
    private function check_goods_sn_exist($goods_sn, $goods_id = 0)
    {
        $goods_sn = trim($goods_sn);
        $goods_id = intval($goods_id);
        if (strlen($goods_sn) == 0) {
            return true;    //重复
        }

        if (empty($goods_id)) {
            $sql = "SELECT goods_id FROM " . $this->dsc->table('goods') . "
                WHERE goods_sn = '$goods_sn'";
        } else {
            $sql = "SELECT goods_id FROM " . $this->dsc->table('goods') . "
                WHERE goods_sn = '$goods_sn'
                AND goods_id <> '$goods_id'";
        }

        $res = $this->db->getOne($sql);

        if (empty($res)) {
            return false;    //不重复
        } else {
            return true;    //重复
        }
    }

    /**
     * 商品的货品货号是否重复
     *
     * @param string $product_sn 商品的货品货号；请在传入本参数前对本参数进行SQl脚本过滤
     * @param int $product_id 商品的货品id；默认值为：0，没有货品id
     * @return  bool                          true，重复；false，不重复
     */
    private function check_product_sn_exist($product_sn, $product_id = 0)
    {
        $product_sn = trim($product_sn);
        $product_id = intval($product_id);
        if (strlen($product_sn) == 0) {
            return true;    //重复
        }
        $sql = "SELECT goods_id FROM " . $this->dsc->table('goods') . "WHERE goods_sn='$product_sn'";
        if ($this->db->getOne($sql)) {
            return true;    //重复
        }


        if (empty($product_id)) {
            $sql = "SELECT product_id FROM " . $this->dsc->table('products_area') . "
                WHERE product_sn = '$product_sn'";
        } else {
            $sql = "SELECT product_id FROM " . $this->dsc->table('products_area') . "
                WHERE product_sn = '$product_sn'
                AND product_id <> '$product_id'";
        }

        $res = $this->db->getOne($sql);

        if (empty($res)) {
            return false;    //不重复
        } else {
            return true;    //重复
        }
    }
}
