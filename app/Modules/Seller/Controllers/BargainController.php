<?php

namespace App\Modules\Seller\Controllers;


use App\Models\ActivityGoodsAttr;
use App\Models\BargainGoods;
use App\Models\BargainStatistics;
use App\Models\BargainStatisticsLog;
use App\Models\Goods;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Common\CommonManageService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Merchant\MerchantCommonService;

/**
 * 管理中心砍价商品管理
 */
class BargainController extends InitController
{
    protected $commonManageService;
    protected $baseRepository;
    protected $goodsAttrService;
    protected $merchantCommonService;
    protected $config;
    protected $dscRepository;
    protected $timeRepository;

    public function __construct(
        CommonManageService $commonManageService,
        BaseRepository $baseRepository,
        GoodsAttrService $goodsAttrService,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository,
        TimeRepository $timeRepository
    )
    {
        $this->commonManageService = $commonManageService;
        $this->baseRepository = $baseRepository;
        $this->goodsAttrService = $goodsAttrService;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->timeRepository = $timeRepository;
    }

    public function index()
    {
        load_helper(['goods', 'order']);
        load_helper('comment', 'seller');

        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('action_type', "team");
        /* 检查权限 */
        //admin_priv('group_by');

        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        /* act操作项的初始化 */
        $act = addslashes(trim(request()->input('act', 'list')));
        $act = $act ? $act : 'list';

        $this->smarty->assign('controller', basename(PHP_SELF, '.php'));
        $this->smarty->assign('menu_select', ['action' => '02_promotion', 'current' => '19_bargain']);

        /*------------------------------------------------------ */
        //-- 砍价活动列表
        /*------------------------------------------------------ */

        if ($act == 'list') {
            $page = request()->get('page', 1);
            /* 模板赋值 */
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['bargain_goods_list']);//标题
            $this->smarty->assign('action_link', ['href' => 'bargain.php?act=add', 'text' => $GLOBALS['_LANG']['add_bargain_goods'], 'class' => 'icon-plus']);

            //页面分菜单 end
            $list = $this->bargain_goods_list($adminru['ru_id']);
            //分页
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('bargain_goods_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            return $this->smarty->display('bargain_goods_list.dwt');

        } elseif ($act == 'query') {
            $page = request()->get('page', 1);
            $list = $this->bargain_goods_list($adminru['ru_id']);

            //分页
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('bargain_goods_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('bargain_goods_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑砍价商品
        /*------------------------------------------------------ */

        elseif ($act == 'add' || $act == 'edit') {
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);
            set_default_filter(0, 0, $adminru['ru_id']);
            $goods['tc_id'] = 0;
            /* 初始化/取得砍价商品信息 */
            if ($act == 'add') {
                $goods = [
                    'start_time' => date('Y-m-d H:i:s', time()),
                    'end_time' => date('Y-m-d H:i:s', time() + 4 * 86400),
                    'min_price' => 1,
                    'max_price' => 10
                ];
            } else {
                $id = request()->get('id', 0);
                if ($id <= 0) {
                    return 'invalid param';
                }
                $goods = $this->bargain_goods_info($id);
            }

            $this->smarty->assign('goods', $goods);

            //分类列表 by wu
            $select_category_html = '';
            $select_category_html .= insert_select_category(0, 0, 0, 'category', 1);
            $this->smarty->assign('select_category_html', $select_category_html);

            /* 模板赋值 */
            if ($act == 'edit') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit_bargain_goods']);//标题
            } else {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_bargain_goods']);//标题
            }

            $this->smarty->assign('action_link', $this->list_link($act == 'add'));
            $this->smarty->assign('brand_list', get_brand_list());//品牌列表
            $this->smarty->assign('ru_id', $adminru['ru_id']);//店铺id

            /* 显示模板 */

            return $this->smarty->display('bargain_goods_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑砍价商品的提交
        /*------------------------------------------------------ */

        elseif ($act == 'insert_update') {
            /* 取得砍价列表id */
            $id = request()->get('id', 0);
            $goods = request()->get('data', '');
            $goods['start_time'] = local_strtotime($goods['start_time']);
            $goods['end_time'] = local_strtotime($goods['end_time']);
            $goods['goods_id'] = request()->get('goods_id', 0);

            $target_price = request()->get('target_price', '');      //目标价格
            $product_id = request()->get('product_id', '');          //货品ID
            $activity_goods_attr = request()->get('bargain_id', ''); //活动商品属性列表id

            if ($goods['goods_id'] <= 0) {
                return sys_msg($GLOBALS['_LANG']['please_add_bargain_goods'], 0);
            }

            if ($goods['start_time'] >= $goods['end_time']) {
                return sys_msg($GLOBALS['_LANG']['start_time_no_large_end_time'], 0);
            }

            if ($goods['min_price'] < 1) {
                return sys_msg(lang('admin/bargain.bargain_section_less_than_zero'), 0);
            }

            if ($goods['min_price'] > $goods['max_price']) {
                return sys_msg(lang('admin/bargain.small_cant_greater_than_big'), 0);
            }

            if ($goods['target_price'] > $goods['goods_price']) {
                return sys_msg(lang('admin/bargain.target_price_greater_than_goods_price'), 0);
            }

            /* 清除缓存 */
            clear_cache_files();
            /* 保存数据 */
            if ($id > 0) {
                /* update */
                $this->db->autoExecute($this->dsc->table('bargain_goods'), $goods, 'UPDATE', "id = '$id'");
                if ($product_id) {
                    foreach ($product_id as $key => $value) {
                        $attr_data['target_price'] = $target_price[$key];
                        $this->db->autoExecute($this->dsc->table('activity_goods_attr'), $attr_data, 'UPDATE', "id = '$activity_goods_attr[$key]'");
                    }
                }
                /* 提示信息 */
                $links = [
                    ['href' => 'bargain.php?act=list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['return_bargain_list']]
                ];
                return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $links);
            } else {
                $sql = "SELECT count(goods_id) as num  FROM " . $this->dsc->table('bargain_goods') . " WHERE goods_id = '" . $goods['goods_id'] . "' and status = '0' ";
                $res = $this->db->getRow($sql);
                if ($res['num'] >= 1) {
                    /* 提示信息 */
                    $links = [
                        ['href' => 'bargain.php?act=add', 'text' => $GLOBALS['_LANG']['continue_add_bargain_goods']]
                    ];
                    return sys_msg($GLOBALS['_LANG']['cant_add_before_bargin_end'], 0, $links);
                }

                /* insert */
                $this->db->autoExecute($this->dsc->table('bargain_goods'), $goods, 'INSERT');
                $bargain_id = $this->db->insert_id();

                if ($bargain_id) {
                    if ($product_id) {
                        foreach ($product_id as $key => $value) {
                            $attr_data['bargain_id'] = $bargain_id;
                            $attr_data['goods_id'] = $goods['goods_id'];
                            $attr_data['product_id'] = $value;
                            $attr_data['target_price'] = $target_price[$key];
                            $attr_data['type'] = 'bargain';
                            $this->db->autoExecute($this->dsc->table('activity_goods_attr'), $attr_data, 'INSERT');
                        }
                    }
                }
                /* 提示信息 */
                $links = [
                    ['href' => 'bargain.php?act=add', 'text' => $GLOBALS['_LANG']['continue_add_bargain_goods']],
                    ['href' => 'bargain.php?act=list', 'text' => $GLOBALS['_LANG']['return_bargain_list']]
                ];
                return sys_msg($GLOBALS['_LANG']['success_add_bargain_goods'], 0, $links);
            }
        }


        /*------------------------------------------------------ */
        //-- 删除砍价商品
        /*------------------------------------------------------ */

        elseif ($act == 'remove') {
            $check_auth = check_authz_json('bargain_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = request()->get('id', 0);
            BargainGoods::where('id', $id)->update(['is_delete' => 1]);

            //清除缓存
            clear_cache_files();

            $url = 'bargain.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }
        /*------------------------------------------------------ */
        //-- 关闭砍价商品
        /*------------------------------------------------------ */

        elseif ($act == 'remove_down') {
            $check_auth = check_authz_json('bargain_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = request()->get('id', 0);
            BargainGoods::where('id', $id)->update(['status' => 1]);

            //清除缓存
            clear_cache_files();

            $url = 'bargain.php?act=query&' . str_replace('act=remove_down', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }


        /*------------------------------------------------------ */
        //-- 参与砍价活动列表
        /*------------------------------------------------------ */

        if ($act == 'bargain_log') {
            $page = request()->get('page', 1);
            /* 模板赋值 */
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['act_detail']);

            //页面分菜单 by wu start
            $tab_menu = [];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['bargain_goods_list'], 'href' => 'bargain.php?act=list'];
            $tab_menu[] = ['curr' => 1, 'text' => $GLOBALS['_LANG']['act_detail'], 'href' => 'bargain.php?act=bargain_log'];
            $this->smarty->assign('tab_menu', $tab_menu);
            $bargain_id = request()->get('id', 0);
            $list = $this->bargain_log_list($bargain_id);

            $list['filter']['bargain_id'] = $bargain_id ?? 0;
            //分页
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('bargain_log_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            /* 显示商品列表页面 */

            return $this->smarty->display('bargain_log_list.dwt');
        } elseif ($act == 'bargain_log_query') {
            $page = request()->get('page', 1);
            $bargain_id = request()->get('bargain_id', 0);
            $list = $this->bargain_log_list($bargain_id);

            $list['filter']['bargain_id'] = $bargain_id ?? 0;
            //分页
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('bargain_log_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('bargain_log_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }


        /*------------------------------------------------------ */
        //-- 亲友帮
        /*------------------------------------------------------ */

        if ($act == 'bargain_statistics_list') {
            $page = request()->get('page', 1);
            /* 模板赋值 */
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['friends_and_relatives']);

            //页面分菜单 by wu start
            $tab_menu = [];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['bargain_goods_list'], 'href' => 'bargain.php?act=list'];
            $tab_menu[] = ['curr' => 1, 'text' => $GLOBALS['_LANG']['friends_and_relatives'], 'href' => 'bargain.php?act=bargain_statistics_list'];
            $this->smarty->assign('tab_menu', $tab_menu);

            $id = request()->get('id', 0);
            $list = $this->bargain_statistics_list($id);
            //分页
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('bargain_statistics_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            /* 显示商品列表页面 */

            return $this->smarty->display('bargain_statistics_list.dwt');
        } elseif ($act == 'bargain_query') {
            $page = request()->get('page', 1);
            $list = $this->bargain_statistics_list($adminru['ru_id']);

            //分页
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('bargain_statistics_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('bargain_statistics_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }


        /*------------------------------------------------------ */
        //-- 搜索单条商品信息
        /*------------------------------------------------------ */

        elseif ($act == 'group_goods') {
            $check_auth = check_authz_json('team_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $filter = dsc_decode(request()->input('JSON', ''));

            $goods_id = $filter->goods_id;
            $row = Goods::where('goods_id', $goods_id)
                ->first();

            $row = $row ? $row->toArray() : [];

            return make_json_result($row);
        }

        /*------------------------------------------------------ */
        //-- 筛选搜索商品
        /*------------------------------------------------------ */

        elseif ($act == 'search_goods') {
            $check_auth = check_authz_json('bargain_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $filter = dsc_decode(request()->input('JSON', ''));
            $arr = get_goods_list($filter);

            return make_json_result($arr);
        }


        /*------------------------------------------------------ */
        //-- 获取选中商品详情
        /*------------------------------------------------------ */

        elseif ($act == 'goods_info') {
            $check_auth = check_authz_json('bargain_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $ru_id = $adminru['ru_id'];
            $goods_id = request()->get('goods_id', 0);
            $where = "  is_on_sale = 1 AND is_alone_sale = 1 AND is_delete = 0 AND review_status>2 and goods_id = $goods_id ";

            /* 取得数据 */
            $sql = "SELECT shop_price,goods_type,model_attr FROM " . $this->dsc->table('goods') . "  where $where ";
            $row = $this->db->getRow($sql);
            $goods_type = $row['goods_type'];
            $goods_model = $row['model_attr'];

            //获取属性列表
            $sql = " SELECT a.attr_id, a.attr_name, a.attr_input_type, a.attr_type, a.attr_values " .
                " FROM " . $this->dsc->table('attribute') . " AS a " .
                " WHERE a.cat_id = " . intval($goods_type) . " AND a.cat_id <> 0 " .
                " ORDER BY a.sort_order, a.attr_type, a.attr_id ";
            $attribute_list = $this->db->getAll($sql);

            //获取商品属性
            $sql = " SELECT v.attr_id, v.attr_value, v.attr_price, v.attr_sort, v.attr_checked " .
                " FROM " . $this->dsc->table('goods_attr') . " AS v " .
                " WHERE goods_id = '$goods_id' ORDER BY v.attr_sort, v.goods_attr_id";
            $attr_list = $this->db->getAll($sql);

            foreach ($attribute_list as $key => $val) {
                $is_selected = 0; //属性是否被选择
                $this_value = ""; //唯一属性的值

                if ($val['attr_type'] > 0) {
                    if ($val['attr_values']) {
                        $attr_values = preg_replace("/\r\n/", ",", $val['attr_values']); //替换空格回车换行符为英文逗号
                        $attr_values = explode(',', $attr_values);
                    } else {
                        $sql = "SELECT attr_value FROM " . $this->dsc->table('goods_attr') . " WHERE goods_id = '$goods_id' AND attr_id = '" . $val['attr_id'] . "' ORDER BY attr_sort, goods_attr_id";
                        $attr_values = $this->db->getAll($sql);

                        $values_list = $this->baseRepository->getKeyPluck($attr_values, 'attr_value');

                        $attribute_list[$key]['attr_values'] = $values_list;
                        $attr_values = $attribute_list[$key]['attr_values'];
                    }

                    $attr_values_arr = [];
                    if ($attr_values) {
                        for ($i = 0; $i < count($attr_values); $i++) {
                            $goods_attr = $this->db->getRow("SELECT goods_attr_id, attr_price, attr_sort FROM " . $this->dsc->table('goods_attr') . " WHERE goods_id = '$goods_id' AND attr_value = '" . $attr_values[$i] . "' AND attr_id = '" . $val['attr_id'] . "' LIMIT 1");
                            $attr_values_arr[$i] = ['is_selected' => 0, 'goods_attr_id' => $goods_attr['goods_attr_id'], 'attr_value' => $attr_values[$i], 'attr_price' => $goods_attr['attr_price'], 'attr_sort' => $goods_attr['attr_sort']];
                        }
                    }

                    $attribute_list[$key]['attr_values_arr'] = $attr_values_arr;
                }

                foreach ($attr_list as $k => $v) {
                    if ($val['attr_id'] == $v['attr_id']) {
                        $is_selected = 1;
                        if ($val['attr_type'] == 0) {
                            $this_value = $v['attr_value'];
                        } else {
                            foreach ($attribute_list[$key]['attr_values_arr'] as $a => $b) {
                                if ($goods_id) {
                                    if ($b['attr_value'] == $v['attr_value']) {
                                        $attribute_list[$key]['attr_values_arr'][$a]['is_selected'] = 1;
                                    }
                                } else {
                                    if ($b['attr_value'] == $v['attr_value']) {
                                        $attribute_list[$key]['attr_values_arr'][$a]['is_selected'] = 1;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
                $attribute_list[$key]['is_selected'] = $is_selected;
                $attribute_list[$key]['this_value'] = $this_value;
                if ($val['attr_input_type'] == 1) {
                    $attribute_list[$key]['attr_values'] = preg_split('/\r\n/', $val['attr_values']);
                }
            }

            $attribute_list = $this->commonManageService->getNewGoodsAttr($attribute_list);

            $GLOBALS['smarty']->assign('goods_id', $goods_id);
            $GLOBALS['smarty']->assign('goods_model', $goods_model);

            $GLOBALS['smarty']->assign('attribute_list', $attribute_list);
            $goods_attribute = $GLOBALS['smarty']->fetch('library/bargain_goods_attribute.lbi');

            $attr_spec = $attribute_list['spec'];

            if ($attr_spec) {
                $arr['is_spec'] = 1;
            } else {
                $arr['is_spec'] = 0;
            }

            $result['goods_attribute'] = $goods_attribute;
            $result['goods_id'] = $goods_id;
            $result['shop_price'] = $row['shop_price'];

            return response()->json($result);
        } /* 设置属性表格 */
        elseif ($act == 'set_attribute_table' || $act == 'goods_attribute_query') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $goods_id = request()->get('goods_id', 0);
            $bargain_id = request()->get('bargain_id', 0);
            $goods_type = request()->get('goods_type', 0);

            $attr_id_arr = empty(request()->get('attr_id')) ? [] : explode(',', request()->get('attr_id'));
            $attr_value_arr = empty(request()->get('attr_value')) ? [] : explode(',', request()->get('attr_value'));
            $goods_model = request()->get('goods_model', 0); //商品模式
            $region_id = request()->get('region_id', 0); //地区id
            $result = ['error' => 0, 'message' => '', 'content' => ''];


            $group_attr = [
                'goods_id' => $goods_id,
                'goods_type' => $goods_type,
                'attr_id' => empty($attr_id_arr) ? '' : implode(',', $attr_id_arr),
                'attr_value' => empty($attr_value_arr) ? '' : implode(',', $attr_value_arr),
                'goods_model' => $goods_model,
                'region_id' => $region_id,
            ];

            $result['group_attr'] = json_encode($group_attr);

            //商品模式
            if ($goods_model == 0) {
                $model_name = "";
            } elseif ($goods_model == 1) {
                $model_name = lang('seller/common.warehouse');
            } elseif ($goods_model == 2) {
                $model_name = lang('seller/common.area');
            }
            $region_name = $this->db->getOne(" SELECT region_name FROM " . $this->dsc->table('region_warehouse') . " WHERE region_id ='$region_id' ");
            $this->smarty->assign('region_name', $region_name);
            $this->smarty->assign('goods_model', $goods_model);
            $this->smarty->assign('model_name', $model_name);

            //商品基本信息
            $goods_info = $this->db->getRow(" SELECT market_price, shop_price, model_attr FROM " . $this->dsc->table("goods") . " WHERE goods_id = '$goods_id' ");
            $this->smarty->assign('goods_info', $goods_info);
            $attr_arr = [];
            //将属性归类
            foreach ($attr_id_arr as $key => $val) {
                $attr_arr[$val][] = $attr_value_arr[$key];
            }

            $attr_spec = [];
            $attribute_array = [];

            if (count($attr_arr) > 0) {
                //属性数据
                $i = 0;
                foreach ($attr_arr as $key => $val) {
                    $sql = "SELECT attr_name, attr_type FROM " . $this->dsc->table('attribute') . " WHERE attr_id ='$key' LIMIT 1";
                    $attr_info = $this->db->getRow($sql);

                    $attribute_array[$i]['attr_id'] = $key;
                    $attribute_array[$i]['attr_name'] = $attr_info['attr_name'];
                    $attribute_array[$i]['attr_value'] = $val;
                    /* 处理属性图片 start */
                    $attr_values_arr = [];
                    foreach ($val as $k => $v) {
                        $where_select = [
                            'attr_id' => $key,
                            'attr_value' => $v,
                            'goods_id' => $goods_id
                        ];
                        $data = $this->goodsAttrService->getGoodsAttrId($where_select, [1, 2], 1);

                        if (!$data) {
                            $sql = "SELECT MAX(goods_attr_id) AS goods_attr_id FROM " . $this->dsc->table('goods_attr') . " WHERE 1 ";
                            $max_goods_attr_id = $this->db->getOne($sql);
                            $attr_sort = $max_goods_attr_id + 1;

                            $sql = " INSERT INTO " . $this->dsc->table('goods_attr') . " (goods_id, attr_id, attr_value, attr_sort, admin_id) " .
                                " VALUES " .
                                " ('$goods_id', '$key', '$v', '$attr_sort', '" . session('seller_id') . "') ";
                            $this->db->query($sql);
                            $data['goods_attr_id'] = $this->db->insert_id();
                            $data['attr_type'] = $attr_info['attr_type'];
                            $data['attr_sort'] = $attr_sort;
                        }

                        $data['attr_id'] = $key;
                        $data['attr_value'] = $v;
                        $data['is_selected'] = 1;
                        $attr_values_arr[] = $data;
                    }

                    $attr_spec[$i] = $attribute_array[$i];
                    $attr_spec[$i]['attr_values_arr'] = $attr_values_arr;

                    $attribute_array[$i]['attr_values_arr'] = $attr_values_arr;

                    if ($attr_info['attr_type'] == 2) {
                        unset($attribute_array[$i]);
                    }
                    /* 处理属性图片 end */
                    $i++;
                }

                //删除复选属性后重设键名
                $new_attribute_array = [];
                foreach ($attribute_array as $key => $val) {
                    $new_attribute_array[] = $val;
                }
                $attribute_array = $new_attribute_array;

                //删除复选属性
                $attr_arr = get_goods_unset_attr($goods_id, $attr_arr);

                //将属性组合
                if (count($attr_arr) == 1) {
                    foreach (reset($attr_arr) as $key => $val) {
                        $attr_group[][] = $val;
                    }
                } else {
                    $attr_group = attr_group($attr_arr);
                }

                //取得组合补充数据
                foreach ($attr_group as $key => $val) {
                    $group = [];
                    //货品信息
                    $product_info = $this->bargain_get_product_info_by_attr($bargain_id, $goods_id, $val, $goods_model, $region_id);
                    if (!empty($product_info)) {
                        $group = $product_info;
                    }

                    //组合信息
                    foreach ($val as $k => $v) {
                        $group['attr_info'][$k]['attr_id'] = $attribute_array[$k]['attr_id'];
                        $group['attr_info'][$k]['attr_value'] = $v;
                    }

                    $attr_group[$key] = $group;
                }

                $this->smarty->assign('attr_group', $attr_group);
                $this->smarty->assign('attribute_array', $attribute_array);
            }

            $this->smarty->assign('group_attr', $result['group_attr']);
            $this->smarty->assign('goods_attr_price', $GLOBALS['_CFG']['goods_attr_price']);

            $GLOBALS['smarty']->assign('goods_id', $goods_id);
            $GLOBALS['smarty']->assign('goods_type', $goods_type);

            $result['content'] = $this->smarty->fetch('library/bargain_attribute_table.lbi');

            return response()->json($result);
        }
    }

    //通过一组属性获取货品的相�    �信息 by wu
    private function bargain_get_product_info_by_attr($bargain_id = 0, $goods_id = 0, $attr_arr = [], $goods_model = 0, $region_id = 0)
    {
        if (!empty($attr_arr)) {
            $where = "";
            //判断商品类型
            if ($goods_model == 1) {
                $table = "products_warehouse";
                $where .= " AND warehouse_id = '$region_id' ";
            } elseif ($goods_model == 2) {
                $table = "products_area";
                $where .= " AND area_id = '$region_id' ";
            } else {
                $table = "products";
            }

            $where_select = ['goods_id' => $goods_id];

            if (empty($goods_id)) {
                $admin_id = get_admin_id();
                $where_select['admin_id'] = $admin_id;
            }

            //获取属性组合
            $attr = [];
            foreach ($attr_arr as $key => $val) {
                $where_select['attr_value'] = $val;
                $goods_attr_info = $this->goodsAttrService->getGoodsAttrId($where_select, 1, 1);
                $goods_attr_id = $goods_attr_info['goods_attr_id'] ?? 0;

                if ($goods_attr_id) {
                    $attr[] = $goods_attr_id;
                }
            }

            //获取货品信息
            $set = "";
            foreach ($attr as $key => $val) {
                $set .= " AND FIND_IN_SET('$val', REPLACE(goods_attr, '|', ',')) ";
            }
            $sql = " SELECT * FROM " . $this->dsc->table($table) . " WHERE 1 $set AND goods_id = '$goods_id' " . $where . " LIMIT 1 ";
            $product_info = $this->db->getRow($sql);
            if ($bargain_id > 0) {
                $sql = " SELECT * FROM " . $this->dsc->table('activity_goods_attr') . " WHERE bargain_id = '$bargain_id'  AND goods_id = '$goods_id' and product_id = '" . $product_info['product_id'] . "'  LIMIT 1 ";
                $attr_info = $this->db->getRow($sql);
                $product_info['goods_attr_id'] = $attr_info['id'];
                $product_info['target_price'] = $attr_info['target_price'];
            }
            return $product_info;
        } else {
            return false;
        }
    }

    /*
     * 取得砍价商品列表
     * @return   array
     */
    private function bargain_goods_list($ru_id)
    {

        /* 过滤条件 */
        $filter['keyword'] = request()->get('keyword', '');
        $filter['is_audit'] = request()->get('is_audit', 0);
        $filter['sort_by'] = empty(request()->get('sort_by')) ? 'bg.id' : trim(request()->get('sort_by'));
        $filter['sort_order'] = empty(request()->get('sort_order')) ? 'DESC' : trim(request()->get('sort_order'));

        $res = BargainGoods::where('is_delete', 0);

        if (!empty($filter['keyword'])) {
            $res = $res->whereHas('getGoods', function ($query) use ($filter) {
                $query->where('goods_name', 'like', '%' . $filter['keyword'] . '%')
                    ->orWhere('goods_sn', 'like', '%' . $filter['keyword'] . '%')
                    ->orWhere('keywords', 'like', '%' . $filter['keyword'] . '%');
            });
        }

        switch ($filter['is_audit']) {
            // 未审核
            case '3':
                $res = $res->where('is_audit', 0);
                break;
            // 已审核
            case '2':
                $res = $res->where('is_audit', 1);
                break;
            // 审核未通过
            case '1':
                $res = $res->where('is_audit', 2);
                break;
        }

        // 检测商品是否存在
        $res = $res->whereHas('getGoods', function ($query) use ($ru_id) {
            $query->where('user_id', $ru_id)
                ->where('is_alone_sale', 1)
                ->where('is_on_sale', 1)
                ->where('is_delete', 0)
                ->where('review_status', '>', 2);
        });

        $res = $res->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'user_id', 'goods_sn', 'goods_name', 'shop_price', 'market_price', 'goods_number', 'sales_volume', 'goods_img', 'goods_thumb', 'is_best', 'is_new');
            },
            'getBargainTargetPrice' => function ($query) {
                $query->select('bargain_id', 'target_price');
            }
        ]);

        $filter['record_count'] = $res->count();

        $filter = page_and_size($filter);

        $res = $res->offset($filter['start'])
            ->limit($filter['page_size'])
            ->orderBy('id', 'DESC');

        $res = $this->baseRepository->getToArrayGet($res);

        $list = [];
        $time = $this->timeRepository->getGmTime();
        foreach ($res as $row) {
            $arr = array_merge($row, $row['get_goods']);
            $arr['goods_name'] = $arr['goods_name'];
            $arr['user_name'] = $this->merchantCommonService->getShopName($arr['user_id'], 1);//商家名称
            $arr['shop_price'] = $this->dscRepository->getPriceFormat($arr['shop_price']);
            $arr['target_price'] = $this->dscRepository->getPriceFormat($arr['target_price']);
            if ($arr['get_bargain_target_price']) {//获取砍价商品属性最低价格
                $target_price = $this->baseRepository->getArrayMin($arr['get_bargain_target_price'], 'target_price');
                if ($target_price) {
                    $arr['target_price'] = $this->dscRepository->getPriceFormat($target_price);
                }
            }

            $arr['goods_number'] = $arr['goods_number'];
            $arr['sales_volume'] = $arr['sales_volume'];
            $arr['goods_img'] = $this->dscRepository->getImagePath($arr['goods_img']);
            $arr['goods_thumb'] = $this->dscRepository->getImagePath($arr['goods_thumb']);

            if ($arr['status'] > 0) {
                $status = $GLOBALS['_LANG']['act_closed'];
            } else {
                if ($time >= $arr['end_time']) {
                    $status = $GLOBALS['_LANG']['act_ended'];
                } else {
                    $status = $GLOBALS['_LANG']['act_ing'];
                }
            }
            $arr['is_status'] = $status;
            $arr['status'] = $arr['status'];

            if ($arr['is_audit'] == 1) {
                $is_audit = $GLOBALS['_LANG']['audited_not_adopt'];
            } elseif ($arr['is_audit'] == 2) {
                $is_audit = $GLOBALS['_LANG']['audited_yes_adopt'];
            } else {
                $is_audit = $GLOBALS['_LANG']['not_audited'];
            }

            $arr['is_audit'] = $is_audit;
            $arr['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $arr['start_time']);
            $arr['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $arr['end_time']);

            $list[] = $arr;
        }

        $arr = ['item' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /*
     * 取得砍价商品信息
     * @return   array
     */
    private function bargain_goods_info($id)
    {
        $goods = BargainGoods::with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'user_id', 'goods_name')
                    ->where('is_delete', 0);
            }
        ]);

        $goods = $goods->where(['id' => $id])
            ->first();
        $goods = $goods ? $goods->toArray() : [];

        $goods = array_merge($goods, $goods['get_goods']);

        $goods['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $goods['start_time']);
        $goods['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $goods['end_time']);

        return $goods;
    }

    /*
     * 参与砍价活动列表
     * @return   array
     */
    private function bargain_log_list($bargain_id)
    {
        $filter['sort_by'] = empty(request()->get('sort_by')) ? 'bs.add_time' : trim(request()->get('sort_by'));
        $filter['sort_order'] = empty(request()->get('sort_order')) ? 'DESC' : trim(request()->get('sort_order'));
        $filter['keyword'] = request()->get('keyword', '');

        $time = $this->timeRepository->getGmTime();

        $where = [
            'time' => $time,
            'bargain_id' => $bargain_id
        ];

        $res = BargainStatisticsLog::whereRaw(1);
        $res = $res->with([
            'getBargainGoods' => function ($query) {
                $query->select('id', 'goods_id', 'target_price', 'start_time', 'end_time');
            },
            'getUsers' => function ($query) {
                $query->select('user_id', 'user_name', 'nick_name');
            }
        ]);

        $res = $res->whereHas('getBargainGoods', function ($query) use ($where) {
            $query->where('id', $where['bargain_id']);
        });

        $filter['record_count'] = $res->count();
        $filter = page_and_size($filter);

        $res = $res->offset($filter['start'])
            ->limit($filter['page_size'])
            ->orderBy('add_time', 'DESC');

        $list = $this->baseRepository->getToArrayGet($res);

        if ($list) {
            $time_format = $this->config['time_format'];
            foreach ($list as $key => $val) {
                $val = $val['get_bargain_goods'] ? array_merge($val, $val['get_bargain_goods']) : $val;
                $val = $val['get_users'] ? array_merge($val, $val['get_users']) : $val;

                $list[$key]['add_time'] = $this->timeRepository->getLocalDate($time_format, $val['add_time']);
                //获取选中活动属性原价，底价
                if ($val['goods_attr_id']) {
                    $spec = explode(",", $val['goods_attr_id']);
                    $target_price = $this->bargain_target_price($val['bargain_id'], $val['goods_id'], $spec);//底价
                    $list[$key]['target_price'] = $this->dscRepository->getPriceFormat($target_price);
                } else {
                    $list[$key]['target_price'] = $this->dscRepository->getPriceFormat($val['target_price']);//底价
                }

                $list[$key]['final_price'] = $this->dscRepository->getPriceFormat($val['final_price']);
                $list[$key]['user_name'] = $val['nick_name'] ? $val['nick_name'] : $val['user_name'];
                $list[$key]['count_num'] = $val['count_num'];
                //团状态
                if ($val['status'] == 1) {
                    $list[$key]['status'] = $GLOBALS['_LANG']['act_success'];
                } elseif ($val['status'] != 1 and $time >= $val['start_time'] and $time <= $val['end_time']) {
                    $list[$key]['status'] = $GLOBALS['_LANG']['act_ing'];
                } elseif ($val['status'] != 1 and $time > $val['end_time']) {
                    $list[$key]['status'] = $GLOBALS['_LANG']['act_fail'];
                }
            }
        }

        $arr = ['item' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /*
     * 亲友帮
     * @return   array
     */
    private function bargain_statistics_list($id = 0)
    {
        $res = BargainStatistics::where('bs_id', $id);
        $res = $res->with([
            'getUsers' => function ($query) {
                $query->select('user_id', 'user_name', 'nick_name');
            }
        ]);

        $filter['record_count'] = $res->count();
        $filter = page_and_size($filter);

        $res = $res->offset($filter['start'])
            ->limit($filter['page_size'])
            ->orderBy('add_time', 'DESC');

        $res = $this->baseRepository->getToArrayGet($res);

        $list = [];
        $time_format = $this->config['time_format'];
        foreach ($res as $row) {
            $arr = $row['get_users'] ? array_merge($row, $row['get_users']) : $row;
            $arr['add_time'] = $this->timeRepository->getLocalDate($time_format, $arr['add_time']);
            $arr['subtract_price'] = price_format($arr['subtract_price']);
            $arr['user_name'] = $arr['nick_name'] ? $arr['nick_name'] : $arr['user_name'];

            $list[] = $arr;
        }

        $arr = ['item' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }


    /**
     * 获得指定商品属性活动最低价
     * @access  public
     * @param int $bargain_id
     * @param int $goods_id
     * @param int $attr_id
     * @return array
     */
    public function bargain_target_price($bargain_id = 0, $goods_id = 0, $attr_id = [], $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        if (empty($attr_id)) {
            return 0;
        }

        $model_attr = Goods::where('goods_id', $goods_id)->value('model_attr');
        //商品属性价格模式,货品模式
        if ($this->config['goods_attr_price'] == 1) {
            if ($model_attr == 1) {
                $product_price = ProductsWarehouse::select('product_id', 'product_price', 'product_promote_price', 'product_market_price')
                    ->where('goods_id', $goods_id)
                    ->where('warehouse_id', $warehouse_id);
            } elseif ($model_attr == 2) {
                $product_price = ProductsArea::select('product_id', 'product_price', 'product_promote_price', 'product_market_price')
                    ->where('goods_id', $goods_id)
                    ->where('area_id', $area_id);

                if ($this->config['area_pricetype'] == 1) {
                    $product_price = $product_price->where('city_id', $area_city);
                }

            } else {
                $product_price = Products::select('product_id', 'product_price', 'product_promote_price', 'product_market_price')
                    ->where('goods_id', $goods_id);
            }

            //获取货品信息
            if ($attr_id) {
                foreach ($attr_id as $key => $val) {
                    $product_price = $product_price->whereRaw("FIND_IN_SET('$val', REPLACE(goods_attr, '|', ','))");
                }
            }
            $product_price = $this->baseRepository->getToArrayFirst($product_price);

            // 获取砍价属性底价
            if ($product_price) {
                $res = ActivityGoodsAttr::where('bargain_id', $bargain_id)
                    ->where('goods_id', $goods_id)
                    ->where('product_id', $product_price['product_id']);
                $res = $this->baseRepository->getToArrayFirst($res);
                return $res['target_price'] ?? 0;
            } else {
                return 0;
            }
        }
    }

    /**
     * 列表链接
     * @param bool $is_add 是否添加（插入）
     * @return  array('href' => $href, 'text' => $text)
     */
    private function list_link($is_add = true)
    {
        $href = 'bargain.php?act=list';
        if (!$is_add) {
            $href .= '&' . list_link_postfix();
        }

        return ['href' => $href, 'text' => $GLOBALS['_LANG']['bargain_goods_list'], 'class' => 'icon-reply'];
    }
}
