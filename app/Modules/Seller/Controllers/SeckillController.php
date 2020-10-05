<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Exchange;
use App\Models\Goods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Category\CategoryService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Store\StoreCommonService;

/**
 * 秒杀活动的处理
 */
class SeckillController extends InitController
{
    protected $categoryService;
    protected $dscRepository;
    protected $baseRepository;
    protected $goodsCommonService;
    protected $storeCommonService;

    public function __construct(
        CategoryService $categoryService,
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        GoodsCommonService $goodsCommonService,
        StoreCommonService $storeCommonService
    )
    {
        $this->categoryService = $categoryService;
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
        $this->goodsCommonService = $goodsCommonService;
        $this->storeCommonService = $storeCommonService;
    }

    public function index()
    {
        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        $menus = session()->has('menus') ? session('menus') : '';
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('action_type', "seckill");

        /* 初始化$exc对象 */
        $exc = new Exchange($this->dsc->table('seckill'), $this->db, 'sec_id', 'acti_title');
        $exc_sg = new Exchange($this->dsc->table('seckill_goods'), $this->db, 'id', 'sec_id');
        $this->smarty->assign('controller', basename(PHP_SELF, '.php'));

        /*------------------------------------------------------ */
        //-- 活动列表页
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'list') {
            admin_priv('seckill_manage');

            /* 模板赋值 */
            $this->smarty->assign('menu_select', ['action' => '02_promotion', 'current' => '03_seckill_list']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['03_seckill_list']);
            $this->smarty->assign('action_link', ['href' => 'seckill.php?act=add', 'text' => $GLOBALS['_LANG']['seckill_add'], 'class' => 'icon-plus']);

            $list = $this->get_seckill_list($adminru['ru_id']);

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('seckill_list', $list['seckill']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            /* 显示商品列表页面 */

            return $this->smarty->display('seckill_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 分页、排序、查询
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'query') {
            $list = $this->get_seckill_list($adminru['ru_id']);

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('seckill_list', $list['seckill']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('seckill_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 秒杀商品 翻页、排序
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'sg_query') {
            load_helper('goods', 'seller');
            $sec_id = empty($_REQUEST['sec_id']) ? 0 : intval($_REQUEST['sec_id']);
            $tb_id = empty($_REQUEST['tb_id']) ? 0 : intval($_REQUEST['tb_id']);
            $list = get_add_seckill_goods($sec_id, $tb_id);

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('cat_goods', $list['cat_goods']);
            $this->smarty->assign('seckill_goods', $list['seckill_goods']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            return make_json_result($this->smarty->fetch('seckill_set_goods_info.dwt'), '', ['filter' => $list['filter'], 'goods_ids' => $list['cat_goods'], 'page_count' => $list['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 删除
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('seckill_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $sec_id = intval($_REQUEST['id']);
            if ($sec_id) {
                $res = $exc->drop($sec_id);
                if ($res) {
                    $this->db->query(" DELETE FROM " . $this->dsc->table('seckill_goods') . " WHERE sec_id='$sec_id' ");
                }
            }
            $url = 'seckill.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 删除秒杀商品
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'sg_remove') {
            $id = intval($_REQUEST['id']);
            $sql = " SELECT sec_id, tb_id FROM " . $this->dsc->table("seckill_goods") . " WHERE id = '$id' ";
            $res = $this->db->getRow($sql);
            $sec_id = $res['sec_id'];
            $tb_id = $res['tb_id'];
            if ($id) {
                $res = $exc_sg->drop($id);
            }
            $url = 'seckill.php?act=sg_query&sec_id=' . $sec_id . '&tb_id=' . $tb_id . str_replace('act=sg_remove', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        }


        /*------------------------------------------------------ */
        //-- 添加、编辑
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            /* 检查权限 */
            admin_priv('seckill_manage');

            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);
            $this->smarty->assign('menu_select', ['action' => '02_promotion', 'current' => '03_seckill_list']);
            $this->smarty->assign('action_link', ['href' => 'seckill.php?act=list', 'text' => $GLOBALS['_LANG']['seckill_list'], 'class' => 'icon-reply']);

            $sec_id = !empty($_GET['sec_id']) ? intval($_GET['sec_id']) : 1;

            if ($_REQUEST['act'] == 'add') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['seckill_add']);
                $this->smarty->assign('form_act', 'insert');
                $tomorrow = local_strtotime('+1 days');
                $next_week = local_strtotime('+8 days');
                $seckill_arr['begin_time'] = local_date('Y-m-d', $tomorrow);
                $seckill_arr['acti_time'] = local_date('Y-m-d', $next_week);
            } else {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['seckill_edit']);
                $this->smarty->assign('form_act', 'update');
                $seckill_arr = $this->get_seckill_info();
            }

            $this->smarty->assign('sec', $seckill_arr);

            return $this->smarty->display('seckill_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加、编辑后提交
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            /* 检查权限 */
            admin_priv('seckill_manage');

            /* 获得日期信息 */
            $sec_id = empty($_REQUEST['sec_id']) ? '' : intval($_REQUEST['sec_id']);
            $acti_title = $_REQUEST['acti_title'] ? trim($_REQUEST['acti_title']) : '';
            $begin_time = local_strtotime($_REQUEST['begin_time']);
            $acti_time = local_strtotime($_REQUEST['acti_time']);
            $is_putaway = empty($_REQUEST['is_putaway']) ? 0 : intval($_REQUEST['is_putaway']);
            $add_time = gmtime();//添加时间;
            $ru_id = $adminru['ru_id'];
            $review_status = 1; //商家操作秒杀活动改变审核状态为未审核

            if ($_REQUEST['act'] == 'insert') {
                /*检查名称是否重复*/
                $is_only = $exc->is_only('acti_title', $_REQUEST['acti_title'], 0);
                if (!$is_only) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['title_exist'], stripslashes($_REQUEST['acti_title'])), 1);
                }
                /* 插入数据库。 */
                $sql = "INSERT INTO " . $this->dsc->table('seckill') . " (acti_title, begin_time, acti_time, is_putaway, add_time, ru_id, review_status)
		VALUES ('$acti_title', '$begin_time', '$acti_time', '$is_putaway', '$add_time', '$ru_id', $review_status)";

                if ($this->db->query($sql)) {
                    /* 提示信息 */
                    $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                    $link[0]['href'] = 'seckill.php?act=list';

                    return sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . $_POST['acti_title'] . "&nbsp;" . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
                } else {
                    return sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . $_POST['acti_title'] . "&nbsp;" . $GLOBALS['_LANG']['attradd_failed'], 1);
                }
            } else {
                /*检查名称是否重复*/
                $is_only = $exc->is_only('acti_title', $_POST['acti_title'], 0, "sec_id != '$sec_id'");
                if (!$is_only) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['title_exist'], stripslashes($_REQUEST['acti_title'])), 1);
                }

                /* 修改入库。 */
                $sql = "UPDATE " . $this->dsc->table('seckill') . " SET " .
                    " acti_title       = '$acti_title', " .
                    " begin_time       = '$begin_time', " .
                    " acti_time        = '$acti_time', " .
                    " is_putaway       = '$is_putaway', " .
                    " review_status    = '$review_status' " .
                    " WHERE sec_id     = '$sec_id'";

                $this->db->query($sql);

                /* 清除缓存 */
                clear_cache_files();

                /* 提示信息 */
                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'seckill.php?act=list';

                return sys_msg($GLOBALS['_LANG']['edit'] . "&nbsp;" . $_POST['acti_title'] . "&nbsp;" . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
            }
        }

        /*------------------------------------------------------ */
        //-- 活动上下线
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_putaway') {
            $id = intval($_REQUEST['id']);
            $val = intval($_REQUEST['val']);

            /* 修改 */
            $sql = "UPDATE " . $this->dsc->table('seckill') . " SET is_putaway = '$val' WHERE sec_id = '$id'";
            $result = $this->db->query($sql);
            if ($result) {
                clear_cache_files();
                return make_json_result($val);
            }
        }

        /*------------------------------------------------------ */
        //-- 设置秒杀商品列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'set_goods') {
            admin_priv('seckill_manage');

            $this->smarty->assign('menu_select', ['action' => '02_promotion', 'current' => '03_seckill_list']);
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['03_seckill_list']);
            $sec_id = !empty($_GET['sec_id']) ? intval($_GET['sec_id']) : 0;
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['set_seckill_goods']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['seckill_list'], 'href' => 'seckill.php?act=list', 'class' => 'icon-reply']);

            $list = $this->get_time_bucket_list();
            $this->smarty->assign('sec_id', $sec_id);
            $this->smarty->assign('time_bucket', $list);
            $this->smarty->assign('full_page', 1);

            return $this->smarty->display('seckill_set_goods.dwt');
        }

        /*------------------------------------------------------ */
        //-- 设置秒杀商品
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add_goods') {
            admin_priv('seckill_manage');
            load_helper('goods', 'seller');

            $sec_id = !empty($_GET['sec_id']) ? intval($_GET['sec_id']) : 0;
            $tb_id = !empty($_GET['tb_id']) ? intval($_GET['tb_id']) : 0;
            $this->smarty->assign('menu_select', ['action' => '02_promotion', 'current' => '03_seckill_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['seckill_time_bucket'], 'href' => 'seckill.php?act=set_goods&sec_id=' . $sec_id]);
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['03_seckill_list']);
            set_default_filter(); //设置默认筛选


            $list = get_add_seckill_goods($sec_id, $tb_id);

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('cat_goods', $list['cat_goods']);
            $this->smarty->assign('seckill_goods', $list['seckill_goods']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['seckill_goods_info']);
            $this->smarty->assign('sec_id', $sec_id);
            $this->smarty->assign('tb_id', $tb_id);
            $this->smarty->assign('full_page', 1);
            return $this->smarty->display('seckill_set_goods_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 删除秒杀商品
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'sg_remove') {
            $id = intval($_REQUEST['id']);
            $sql = " SELECT sec_id, tb_id FROM " . $this->dsc->table("seckill_goods") . " WHERE id = '$id' ";
            $res = $this->db->getRow($sql);
            $sec_id = $res['sec_id'];
            $tb_id = $res['tb_id'];
            if ($id) {
                $res = $exc_sg->drop($id);
            }
            $url = 'seckill.php?act=sg_query&sec_id=' . $sec_id . '&tb_id=' . $tb_id . str_replace('act=sg_remove', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        }

        /*--------------------------------------------------------*/
        //商品模块弹窗
        /*--------------------------------------------------------*/
        elseif ($_REQUEST['act'] == 'goods_info') {
            $result = ['content' => '', 'mode' => ''];
            /*处理数组*/
            $cat_id = !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;
            $goods_type = isset($_REQUEST['goods_type']) ? intval($_REQUEST['goods_type']) : 0;
            $_REQUEST['spec_attr'] = strip_tags(urldecode($_REQUEST['spec_attr']));
            $_REQUEST['spec_attr'] = json_str_iconv($_REQUEST['spec_attr']);
            $_REQUEST['spec_attr'] = !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';
            if (!empty($_REQUEST['spec_attr'])) {
                $spec_attr = dsc_decode(stripslashes($_REQUEST['spec_attr']), true);
            }
            $spec_attr['is_title'] = isset($spec_attr['is_title']) ? $spec_attr['is_title'] : 0;
            $spec_attr['itemsLayout'] = isset($spec_attr['itemsLayout']) ? $spec_attr['itemsLayout'] : 'row4';
            $result['mode'] = isset($_REQUEST['mode']) ? addslashes($_REQUEST['mode']) : '';
            $result['diff'] = isset($_REQUEST['diff']) ? intval($_REQUEST['diff']) : 0;
            $lift = isset($_REQUEST['lift']) ? trim($_REQUEST['lift']) : '';
            //取得商品列表
            if ($spec_attr['goods_ids']) {
                $goods_info = explode(',', $spec_attr['goods_ids']);
                foreach ($goods_info as $k => $v) {
                    if (!$v) {
                        unset($goods_info[$k]);
                    }
                }
                if (!empty($goods_info)) {
                    $where = " WHERE g.is_on_sale=1 AND g.is_delete=0 AND g.goods_id" . db_create_in($goods_info);

                    //ecmoban模板堂 --zhuo start
                    if ($GLOBALS['_CFG']['review_goods'] == 1) {
                        $where .= ' AND g.review_status > 2 ';
                    }
                    //ecmoban模板堂 --zhuo end

                    $sql = "SELECT g.goods_name,g.goods_id,g.goods_thumb,g.original_img,g.shop_price FROM " . $this->dsc->table('goods') . " AS g " . $where;
                    $goods_list = $this->db->getAll($sql);

                    foreach ($goods_list as $k => $v) {
                        $goods_list[$k]['shop_price'] = price_format($v['shop_price']);
                    }

                    $this->smarty->assign('goods_list', $goods_list);
                    $this->smarty->assign('goods_count', count($goods_list));
                }
            }
            /* 取得分类列表 */
            //获取下拉列表 by wu start
            set_default_filter(0, 0, $adminru['ru_id']); //by wu
            $this->smarty->assign('parent_category', get_every_category($cat_id)); //上级分类导航
            $this->smarty->assign('brand_list', get_brand_list());
            $this->smarty->assign('arr', $spec_attr);
            $this->smarty->assign("goods_type", $goods_type);
            $this->smarty->assign("mode", $result['mode']);
            $this->smarty->assign("cat_id", $cat_id);
            $this->smarty->assign("lift", $lift);
            $result['content'] = $GLOBALS['smarty']->fetch('library/add_seckill_goods.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 商品模块
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'changedgoods') {
            load_helper('goods');
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $spec_attr = [];
            $result['lift'] = isset($_REQUEST['lift']) ? trim($_REQUEST['lift']) : '';
            $result['spec_attr'] = !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';
            if (isset($_REQUEST['spec_attr']) && $_REQUEST['spec_attr']) {
                $_REQUEST['spec_attr'] = strip_tags(urldecode($_REQUEST['spec_attr']));
                $_REQUEST['spec_attr'] = json_str_iconv($_REQUEST['spec_attr']);
                if (!empty($_REQUEST['spec_attr'])) {
                    $spec_attr = dsc_decode($_REQUEST['spec_attr'], true);
                }
            }
            $sort_order = isset($_REQUEST['sort_order']) ? $_REQUEST['sort_order'] : 1;
            $cat_id = isset($_REQUEST['cat_id']) ? explode('_', $_REQUEST['cat_id']) : [];
            $brand_id = isset($_REQUEST['brand_id']) ? intval($_REQUEST['brand_id']) : 0;
            $sec_id = isset($_REQUEST['sec_id']) ? intval($_REQUEST['sec_id']) : 0;
            $tb_id = isset($_REQUEST['tb_id']) ? intval($_REQUEST['tb_id']) : 0;
            $keyword = isset($_REQUEST['keyword']) ? addslashes($_REQUEST['keyword']) : '';
            $goodsAttr = isset($spec_attr['goods_ids']) ? explode(',', $spec_attr['goods_ids']) : [];
            $goods_ids = isset($_REQUEST['goods_ids']) ? explode(',', $_REQUEST['goods_ids']) : [];
            $result['goods_ids'] = !empty($goodsAttr) ? $goodsAttr : $goods_ids;
            $result['cat_desc'] = isset($spec_attr['cat_desc']) ? addslashes($spec_attr['cat_desc']) : '';
            $result['cat_name'] = isset($spec_attr['cat_name']) ? addslashes($spec_attr['cat_name']) : '';
            $result['align'] = isset($spec_attr['align']) ? addslashes($spec_attr['align']) : '';
            $result['is_title'] = isset($spec_attr['is_title']) ? intval($spec_attr['is_title']) : 0;
            $result['itemsLayout'] = isset($spec_attr['itemsLayout']) ? addslashes($spec_attr['itemsLayout']) : '';
            $result['diff'] = isset($_REQUEST['diff']) ? intval($_REQUEST['diff']) : 0;
            $type = isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 0;
            $temp = isset($_REQUEST['temp']) ? $_REQUEST['temp'] : 'goods_list';
            $resetRrl = isset($_REQUEST['resetRrl']) ? intval($_REQUEST['resetRrl']) : 0;

            $result['mode'] = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';
            $this->smarty->assign('temp', $temp);

            $where = [
                'sort_order' => $sort_order,
                'brand_id' => $brand_id,
                'cat_id' => $cat_id,
                'type' => $type,
                'goods_ids' => $result['goods_ids'],
                'keyword' => $keyword,
                'user_id' => $adminru['ru_id']
            ];

            if ($type == 1) {
                $where['is_page'] = 1;
                $list = $this->getPisGoodsList($where);
                $goods_list = $list['list'];
                $filter = $list['filter'];
                $filter['cat_id'] = $cat_id[0];
                $filter['sort_order'] = $sort_order;
                $filter['keyword'] = $keyword;
                $this->smarty->assign('filter', $filter);
            } else {
                $where['is_page'] = 0;
                $goods_list = $this->getPisGoodsList($where);
            }

            if ($goods_list) {
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
                    if ($v['goods_id'] > 0 && in_array($v['goods_id'], $result['goods_ids']) && !empty($result['goods_ids'])) {
                        $goods_list[$k]['is_selected'] = 1;
                    }
                }
            }
            $this->smarty->assign("is_title", $result['is_title']);
            $this->smarty->assign('goods_list', $goods_list);

            $this->smarty->assign('goods_count', count($goods_list));
            $this->smarty->assign('attr', $spec_attr);
            $result['content'] = $GLOBALS['smarty']->fetch('library/seckill_goods_list.lbi');
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 修改秒杀商品价格
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_sec_price') {
            $check_auth = check_authz_json('seckill_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $sec_price = floatval($_POST['val']);

            if ($exc_sg->edit("sec_price = '$sec_price'", $id)) {
                clear_cache_files();
                return make_json_result($sec_price);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改秒杀商品数量
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_sec_num') {
            $check_auth = check_authz_json('seckill_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $sec_num = intval($_POST['val']);

            if ($exc_sg->edit("sec_num = '$sec_num'", $id)) {
                clear_cache_files();
                return make_json_result($sec_num);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改秒杀商品限购数量
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_sec_limit') {
            $check_auth = check_authz_json('seckill_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $sec_limit = intval($_POST['val']);

            if ($exc_sg->edit("sec_limit = '$sec_limit'", $id)) {
                clear_cache_files();
                return make_json_result($sec_limit);
            }
        }
    }

    /*
    *  秒杀活动商品列表
    */
    private function get_seckill_list($ru_id)
    {
        $result = get_filter();
        if ($result === false) {
            /* 查询条件 */
            $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
            if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
                $filter['keywords'] = json_str_iconv($filter['keywords']);
            }

            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'sec_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
            $where = " WHERE 1 ";
            $where .= empty($_REQUEST['sec_id']) ? '' : " AND sec_id = '" . trim($_REQUEST['sec_id']) . "' ";
            $where .= (!empty($filter['keywords'])) ? " AND acti_title like '%" . mysql_like_quote($filter['keywords']) . "%'" : '';
            $where .= empty($_REQUEST['review_status']) ? '' : " AND review_status = '" . intval($_REQUEST['review_status']) . "' ";

            if ($ru_id) {
                $where .= " AND ru_id = '$ru_id' ";
            }

            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('seckill') . $where;

            $filter['record_count'] = $this->db->getOne($sql);

            $filter = page_and_size($filter);

            /* 获活动数据 */
            $sql = "SELECT sec_id, begin_time, acti_title, is_putaway, acti_time, review_status " .
                " FROM " . $this->dsc->table('seckill') .
                $where . " ORDER by $filter[sort_by] $filter[sort_order] LIMIT " . $filter['start'] . ", " . $filter['page_size'];

            $filter['keywords'] = stripslashes($filter['keywords']);
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $row = $this->db->getAll($sql);

        foreach ($row as $key => $val) {
            $time = time();
            $row[$key]['begin_time'] = local_date("Y-m-d", $val['begin_time']);
            $row[$key]['acti_time'] = local_date("Y-m-d", $val['acti_time']);
            $start_time = local_strtotime($row[$key]['begin_time']);
            $end_time = local_strtotime($row[$key]['acti_time']);
            if ($time > $end_time) {
                $row[$key]['time'] = $GLOBALS['_LANG']['act_end'];
            } elseif ($time < $end_time && $time > $start_time) {
                $row[$key]['time'] = $GLOBALS['_LANG']['act_ing'];
            } else {
                $row[$key]['time'] = $GLOBALS['_LANG']['act_not_start'];
            }
        }

        $arr = ['seckill' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /*
    * 秒杀活动商品详情
    */
    private function get_seckill_info()
    {
        $sql = " SELECT sec_id, begin_time, acti_title, acti_time, is_putaway, ru_id, review_status FROM " . $this->dsc->table('seckill') .
            " WHERE sec_id = '" . intval($_REQUEST['sec_id']) . "' ";
        $arr = $this->db->getRow($sql);

        $arr['begin_time'] = local_date("Y-m-d", $arr['begin_time']);
        $arr['acti_time'] = local_date("Y-m-d", $arr['acti_time']);

        return $arr;
    }

    private function get_time_bucket_list()
    {
        $sql = " SELECT * FROM " . $this->dsc->table('seckill_time_bucket');
        $res = $this->db->getAll($sql);
        return $res;
    }

    private function getPisGoodsList($where = [])
    {
        $where['goods_ids'] = isset($where['goods_ids']) ? $this->baseRepository->getExplode($where['goods_ids']) : $where['goods_ids'];

        $row = Goods::where('is_delete', 0)
            ->where('is_on_sale', 1);

        if ($GLOBALS['_CFG']['review_goods'] == 1) {
            $row = $row->where('review_status', '>', 2);
        }

        if (isset($where['cat_id']) && isset($where['cat_id'][0]) && $where['cat_id'][0] > 0) {
            $children = $this->categoryService->getCatListChildren($where['cat_id'][0]);

            if ($children) {
                $row = $row->whereIn('cat_id', $children);
            }
        }

        if (isset($where['brand_id']) && $where['brand_id'] > 0) {
            $row = $row->where('brand_id', $where['brand_id']);
        }

        if (isset($where['user_id']) && $where['user_id'] > 0) {
            $row = $row->where('user_id', $where['user_id']);
        }

        if (isset($where['keyword']) && $where['keyword']) {
            $row = $row->where('goods_name', 'like', '%' . $where['keyword'] . '%');
        }

        if (isset($where['goods_ids']) && isset($where['type']) && $where['goods_ids'] && $where['type'] == '0') {
            $row = $row->whereIn('goods_id', $where['goods_ids']);
        }

        $res = $record_count = $row;

        if (isset($where['is_page']) && $where['is_page'] == 1) {
            $filter['record_count'] = $record_count->count();

            $filter = page_and_size($filter);
        }

        switch (isset($where['sort_order']) && $where['sort_order']) {
            case 1:
                $res = $res->orderBy("add_time");
                break;

            case 2:
                $res = $res->orderBy("add_time", 'desc');
                break;

            case 3:
                $res = $res->orderBy("sort_order");
                break;

            case 4:
                $res = $res->orderBy("sort_order", 'desc');
                break;

            case 5:
                $res = $res->orderBy("goods_name");
                break;

            case 6:
                $res = $res->orderBy("goods_name", 'desc');
                break;
            default:
                $res = $res->orderByRaw("sort_order, sales_volume desc");
                break;
        }

        if (isset($where['is_page']) && $where['is_page'] == 1) {
            if ($filter['start'] > 0) {
                $res = $res->skip($filter['start']);
            }

            if ($filter['page_size'] > 0) {
                $res = $res->take($filter['page_size']);
            }
        }

        $res = $this->baseRepository->getToArrayGet($res);

        if (isset($where['is_page']) && $where['is_page'] == 1) {
            $filter['page_arr'] = seller_page($filter, $filter['page']);
            return ['list' => $res, 'filter' => $filter];
        } else {
            return $res;
        }
    }
}
