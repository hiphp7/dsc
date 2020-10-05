<?php

namespace App\Modules\Seller\Controllers;

use App\Models\Goods;
use App\Models\OrderInfo;
use App\Models\TeamCategory;
use App\Models\TeamGoods;
use App\Models\TeamLog;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;

/**
 * 管理中心拼团商品管理  BaseRepository
 */
class TeamController extends InitController
{
    protected $dscRepository;
    protected $baseRepository;
    protected $config;
    protected $timeRepository;

    public function __construct(
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        TimeRepository $timeRepository
    )
    {
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->timeRepository = $timeRepository;
    }

    public function index()
    {
        load_helper('goods');
        load_helper('order');
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
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        $this->smarty->assign('controller', basename(PHP_SELF, '.php'));
        $this->smarty->assign('menu_select', ['action' => '02_promotion', 'current' => '18_team']);

        /*------------------------------------------------------ */
        //-- 团购活动列表
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'list') {
            /* 模板赋值 */
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['team_goods_list']);//标题
            $this->smarty->assign('action_link', ['href' => 'team.php?act=add', 'text' => $GLOBALS['_LANG']['add_team_goods'], 'class' => 'icon-plus']);

            //页面菜单切换 start
            $tab_menu = [];
            $tab_menu[] = ['curr' => 1, 'text' => $GLOBALS['_LANG']['team_goods_list'], 'href' => 'team.php?act=list'];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['team_info'], 'href' => 'team.php?act=team_info'];
            $this->smarty->assign('tab_menu', $tab_menu);
            //页面分菜单 end
            $list = $this->team_goods_list($adminru['ru_id']);
            //分页
            $page = request()->input('page', 1);
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('team_goods_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            //拼团频道树形
            $team_list = $this->teamGetTree(0);
            $this->smarty->assign('team_list', $team_list);

            /* 显示商品列表页面 */
            return $this->smarty->display('team_goods_list.dwt');

        } elseif ($_REQUEST['act'] == 'query') {

            $list = $this->team_goods_list($adminru['ru_id']);

            //分页
            $page = request()->input('page', 1);
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('team_goods_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            return make_json_result(
                $this->smarty->fetch('team_goods_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑拼团商品
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);
            set_default_filter(0, 0, $adminru['ru_id']);
            $goods['tc_id'] = 0;
            /* 初始化/取得拼团商品信息 */
            if ($_REQUEST['act'] == 'edit') {
                $id = request()->input('id', 0);
                if ($id <= 0) {
                    return 'invalid param';
                }
                //拼团商品信息
                $model = TeamGoods::with([
                    'getGoods' => function ($query) {
                        $query->select('goods_id', 'user_id as ru_id', 'goods_name')
                            ->where('is_delete', 0);
                    }
                ]);
                $goods = $model->where(['id' => $id])
                    ->first();
                $goods = $goods ? $goods->toArray() : [];
                $goods = collect($goods)->merge($goods['get_goods'])->except('get_goods')->all();
            }

            $this->smarty->assign('goods', $goods);

            //分类列表 by wu
            $select_category_html = '';
            $select_category_html .= insert_select_category(0, 0, 0, 'category', 1);
            $this->smarty->assign('select_category_html', $select_category_html);

            /* 模板赋值 */
            if ($_REQUEST['act'] == 'edit') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit_team_goods']);//标题
            } else {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_team_goods']);//标题
            }

            $this->smarty->assign('action_link', $this->list_link($_REQUEST['act'] == 'add'));
            $this->smarty->assign('brand_list', get_brand_list());//品牌列表
            $this->smarty->assign('ru_id', $adminru['ru_id']);

            //拼团频道树形
            $team_list = $this->teamGetTree(0);
            $this->smarty->assign('team_list', $team_list);

            //写入虚拟已参团人数
            $this->smarty->assign('virtual_limit_nim', $this->config['virtual_limit_nim']);

            /* 显示模板 */
            return $this->smarty->display('team_goods_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑拼团商品的提交
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'insert_update') {
            /* 取得拼团列表id */
            $id = request()->input('id', 0);
            $goods = request()->input('data');
            $goods['goods_id'] = request()->input('goods_id', 0);
            $goods['team_price'] = request()->input('team_price', 0);
            $goods['team_num'] = request()->input('team_num', 0);
            $goods['validity_time'] = request()->input('validity_time', 0);
            $goods['astrict_num'] = request()->input('astrict_num', 0);
            $goods['tc_id'] = request()->input('tc_id', 0);
            $goods['is_audit'] = 0;
            $goods['is_team'] = 1;
            $goods['limit_num'] = $goods['limit_num'] ?? 0;
            $goods['team_desc'] = $goods['team_desc'] ?? '';

            if ($goods['goods_id'] <= 0) {
                /* 提示信息 */
                $links = [
                    ['href' => 'team.php?act=add&' . list_link_postfix(), 'text' => lang('seller/common.back')]
                ];
                return sys_msg(lang('seller/team.please_add_goods'), 0, $links);
            }

            if ($goods['team_num'] <= 1) {
                /* 提示信息 */
                $links = [
                    ['href' => 'team.php?act=add&' . list_link_postfix(), 'text' => lang('seller/common.back')]
                ];
                return sys_msg(lang('seller/team.team_num_not_less_than_one'), 0, $links);
            }

            $adminru = get_admin_ru_id();

            /* 清除缓存 */
            clear_cache_files();
            /* 保存数据 */
            if ($id > 0) { //修改
                TeamGoods::where(['id' => $id])->update($goods);
                /* 提示信息 */
                $links = [
                    ['href' => 'team.php?act=list&' . list_link_postfix(), 'text' => lang('seller/team.back_assemble_list')]
                ];
                return sys_msg(lang('seller/common.modify_success'), 0, $links);
            } else { // 添加

                $count = TeamGoods::where(['goods_id' => $goods['goods_id'], 'is_team' => '1'])->count();

                if ($count >= 1) {
                    /* 提示信息 */
                    $links = [
                        ['href' => 'team.php?act=add', 'text' => lang('seller/team.continue_add_goods')]
                    ];
                    return sys_msg(lang('seller/team.goods_existing'), 0, $links);
                }

                $insertGetId = TeamGoods::insertGetId($goods);

                /* 提示信息 */
                $links = [
                    ['href' => 'team.php?act=add', 'text' => lang('seller/team.continue_add_goods')],
                    ['href' => 'team.php?act=list', 'text' => lang('seller/team.back_assemble_goods_list')]
                ];
                return sys_msg(lang('seller/team.assemble_goods_add_success'), 0, $links);
            }
        }
        /*------------------------------------------------------ */
        //-- 批量删除拼团商品
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch_drop') {

            $checkboxes = request()->input('checkboxes', []);

            if ($checkboxes) {
                $del_count = 0; //初始化删除数量
                foreach ($checkboxes as $key => $id) {
                    // 删除拼团商品
                    TeamGoods::where('id', $id)->update(['is_team' => 0]);
                    $del_count++;
                }

                // 如果删除了拼团商品，清除缓存
                if ($del_count > 0) {
                    clear_cache_files();
                }

                $links[] = ['text' => lang('seller/team.back_assemble_goods_list'), 'href' => 'team.php?act=list'];
                return sys_msg(sprintf(lang('seller/team.assemble_goods_del_success'), $del_count), 0, $links);
            } else {
                $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'team.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['no_select_group_buy'], 0, $links);
            }
        }

        /*------------------------------------------------------ */
        //-- 删除拼团商品
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('team_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = request()->input('id', 0);

            // 删除拼团商品
            TeamGoods::where('id', $id)->update(['is_team' => 0]);

            //清除缓存
            clear_cache_files();
            $url = 'team.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }
        /*------------------------------------------------------ */
        //-- 团队信息列表
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'team_info') {

            /* 模板赋值 */
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['team_info']);

            //页面分菜单 by wu start
            $tab_menu = [];
            $tab_menu[] = ['curr' => 0, 'text' => lang('seller/team.assemble_list'), 'href' => 'team.php?act=list'];
            $tab_menu[] = ['curr' => 1, 'text' => lang('seller/team.team_info'), 'href' => 'team.php?act=team_info'];
            $this->smarty->assign('tab_menu', $tab_menu);

            $list = $this->team_info_list($adminru['ru_id']);
            //分页
            $page = request()->input('page', 1);
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('team_info_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            /* 显示商品列表页面 */

            return $this->smarty->display('team_info_list.dwt');
        } elseif ($_REQUEST['act'] == 'team_info_query') {

            $list = $this->team_info_list($adminru['ru_id']);
            //分页
            $page = request()->input('page', 1);
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('team_info_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            return make_json_result(
                $this->smarty->fetch('team_info_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 删除团队信息
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'remove_info') {

            $team_id = request()->input('id', 0);

            // 删除团队信息
            TeamLog::where('team_id', $team_id)->update(['is_show' => 0]);

            //清除缓存
            clear_cache_files();
            $url = 'team.php?act=team_info_query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 批量删除拼团商品
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove_team_info') {
            $checkboxes = request()->input('checkboxes', []);
            if ($checkboxes) {
                $del_count = 0; //初始化删除数量
                foreach ($checkboxes as $key => $id) {
                    // 删除拼团商品
                    TeamLog::where('team_id', $id)->update(['is_show' => 0]);
                    $del_count++;
                }

                /* 如果删除了拼团商品，清除缓存 */
                if ($del_count > 0) {
                    clear_cache_files();
                }

                $links[] = ['text' => lang('seller/team.back_assemble_goods_list'), 'href' => 'team.php?act=team_info'];
                return sys_msg(sprintf(lang('seller/team.assemble_goods_del_success'), $del_count), 0, $links);
            } else {
                $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'team.php?act=team_info'];
                return sys_msg($GLOBALS['_LANG']['no_select_group_buy'], 0, $links);
            }
        }


        /*------------------------------------------------------ */
        //-- 团队订单列表
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'team_order') {
            /* 模板赋值 */
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['team_order']);

            //页面分菜单 by wu start
            $tab_menu = [];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['team_goods_list'], 'href' => 'team.php?act=list'];
            $tab_menu[] = ['curr' => 0, 'text' => $GLOBALS['_LANG']['team_info'], 'href' => 'team.php?act=team_info'];
            $tab_menu[] = ['curr' => 1, 'text' => $GLOBALS['_LANG']['team_order'], 'href' => 'team.php?act=team_order'];
            $this->smarty->assign('tab_menu', $tab_menu);

            $team_id = $_REQUEST['team_id'];
            $list = $this->team_order_list($adminru['ru_id'], $team_id);
            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('team_order_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            /* 显示商品列表页面 */

            return $this->smarty->display('team_order_list.dwt');
        } elseif ($_REQUEST['act'] == 'team_order_query') {

            $list = $this->team_order_list($adminru['ru_id']);

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('team_order_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            return make_json_result(
                $this->smarty->fetch('team_order_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }


        /*------------------------------------------------------ */
        //-- 搜索单条商品信息
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'group_goods') {
            $check_auth = check_authz_json('team_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $filter = dsc_decode($_GET['JSON']);

            $goods_id = $filter->goods_id;

            $row = Goods::where('goods_id', $goods_id)
                ->first();

            $row = $row ? $row->toArray() : [];

            return make_json_result($row);
        }

        /*------------------------------------------------------ */
        //-- 筛选搜索商品
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'search_goods') {
            $check_auth = check_authz_json('team_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }


            $filter = dsc_decode($_GET['JSON']);
            $arr = get_goods_list($filter);

            return make_json_result($arr);
        }
    }


    /*
     * 取得拼团商品列表
     * @return   array
     */
    private function team_goods_list($ru_id)
    {
        /* 过滤条件 */
        $filter['keyword'] = request()->get('keyword', '');
        $filter['is_audit'] = request()->get('is_audit', 3);
        $filter['tc_id'] = request()->get('tc_id', 0);

        $model = TeamGoods::where('is_team', 1);

        if (!empty($filter['keyword'])) {
            $model = $model->whereHas('getGoods', function ($query) use ($filter) {
                $goods_name = $filter['keyword'];
                $query->where('goods_name', 'like', '%' . $goods_name . '%')
                    ->orWhere('goods_sn', 'like', '%' . $goods_name . '%')
                    ->orWhere('keywords', 'like', '%' . $goods_name . '%');
            });
        }

        if (isset($filter['is_audit']) && $filter['is_audit'] < 3) {
            $model = $model->where('is_audit', $filter['is_audit']);
        }

        if (isset($filter['tc_id']) && $filter['tc_id'] > 0) {
            $type = $this->getCategroyId($filter['tc_id']);
            $model = $model->whereIn('tc_id', $type);
        }

        // 检测商品是否存在
        $model = $model->whereHas('getGoods', function ($query) use ($ru_id) {
            $query->where('user_id', $ru_id)
                ->where('is_alone_sale', 1)
                ->where('is_on_sale', 1)
                ->where('is_delete', 0)
                ->where('review_status', '>', 2);;
        });

        // 商品信息
        $model = $model->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'user_id', 'goods_sn', 'goods_name', 'shop_price', 'market_price', 'goods_number', 'sales_volume', 'goods_img', 'goods_thumb', 'is_best', 'is_new', 'is_hot');
            }
        ]);

        $filter['record_count'] = $model->count();
        /* 分页大小 */
        $filter = page_and_size($filter);

        $model = $model->offset($filter['start'])
            ->limit($filter['page_size'])
            ->orderBy('id', 'DESC');

        $res = $this->baseRepository->getToArrayGet($model);

        $list = [];
        if ($res) {
            foreach ($res as $row) {
                $arr = array_merge($row, $row['get_goods']);
                $arr['shop_price'] = $this->dscRepository->getPriceFormat($arr['shop_price']);
                $arr['team_price'] = $this->dscRepository->getPriceFormat($arr['team_price']);
                if ($arr['is_audit'] == 1) {
                    $is_audit = lang('seller/common.audited_not_adopt');
                } elseif ($arr['is_audit'] == 2) {
                    $is_audit = lang('seller/common.audited_yes_adopt');
                } else {
                    $is_audit = lang('seller/common.not_audited');
                }
                $arr['is_audit'] = $is_audit;
                unset($row['get_goods']);
                $list[] = $arr;
            }
        }

        $arr = ['item' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }


    /*
     * 取得团队信息列表
     * @return   array
     */
    private function team_info_list($ru_id)
    {
        /* 过滤条件 */
        $filter['keyword'] = request()->get('keyword', '');
        $where = [
            'keyword' => $filter['keyword'],
            'ru_id' => $ru_id
        ];

        $model = TeamLog::where('is_show', 1);

        $model = $model->whereHas('getTeamGoods', function ($query) use ($where) {
            $query = $query->where('is_team', 1);
            $query->whereHas('getGoods', function ($query) use ($where) {
                $query = $query->where('user_id', $where['ru_id']);
                if (!empty($where['keyword'])) {
                    $query->where('goods_name', 'like', '%' . $where['keyword'] . '%');
                }
            });

        });

        $model = $model->with([
            'getTeamGoods' => function ($query) {
                $query->select('id', 'validity_time', 'team_num', 'team_price', 'limit_num', 'is_team');
            },
            'getGoods' => function ($query) {
                $query->select('goods_id', 'user_id', 'goods_name', 'goods_thumb', 'shop_price');
            }
        ]);

        $filter['record_count'] = $model->count();
        /* 分页大小 */
        $filter = page_and_size($filter);

        $model = $model->offset($filter['start'])
            ->limit($filter['page_size'])
            ->orderBy('start_time', 'DESC');

        $res = $this->baseRepository->getToArrayGet($model);

        $list = [];
        $time = $this->timeRepository->getGmTime();
        foreach ($res as $row) {
            $arr = array_merge($row, $row['get_team_goods']);
            $arr = array_merge($arr, $arr['get_goods']);
            $arr['surplus'] = $arr['team_num'] - $this->surplus_num($arr['team_id']);//还差几人
            //团状态
            if ($arr['status'] != 1 && $time < ($arr['start_time'] + ($arr['validity_time'] * 3600))) {//进项中
                $arr['status'] = lang('seller/team.loading');
            } elseif ($arr['status'] != 1 && $time > ($arr['start_time'] + ($arr['validity_time'] * 3600))) {//失败
                $arr['status'] = lang('seller/team.fail_group');
            } elseif ($arr['status'] == 1) {//成功
                $arr['status'] = lang('seller/team.success_group');
            }
            //剩余时间
            $endtime = $arr['start_time'] + $arr['validity_time'] * 3600;
            $cle = $endtime - $time; //得出时间戳差值
            $d = floor($cle / 3600 / 24);
            $h = floor(($cle % (3600 * 24)) / 3600);
            $m = floor((($cle % (3600 * 24)) % 3600) / 60);
            $arr['time'] = $d . lang('seller/common.tian') . $h . lang('seller/team.team_hour') . $m . lang('seller/team.team_minute');
            $arr['cle'] = $cle;
            $arr['start_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $arr['start_time']);
            $list[] = $arr;
        }
        $arr = ['item' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /*
     * 取得团队订单列表
     * @return   array
     */
    private function team_order_list($ru_id, $team_id = 0)
    {
        $res = OrderInfo::where('extension_code', 'team_buy')
            ->where('team_id', $team_id);

        $res = $res->whereHas('getOrderGoods', function ($query) use ($ru_id) {
            $query->where('ru_id', $ru_id);
        });

        $res = $res->with([
            'getOrderGoods' => function ($query) {
                $query->select('order_id', 'goods_id', 'goods_name', 'ru_id');
            }
        ]);

        $filter['record_count'] = $res->count();
        /* 分页大小 */
        $filter = page_and_size($filter);

        $res = $res->offset($filter['start'])
            ->limit($filter['page_size'])
            ->orderBy('add_time', 'DESC');

        $res = $this->baseRepository->getToArrayGet($res);

        $list = [];
        foreach ($res as $row) {
            $arr = array_merge($row, $row['get_order_goods']);
            unset($arr['get_order_goods']);
            $arr['add_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $arr['add_time']);
            $arr['status'] = $GLOBALS['_LANG']['os'][$arr['order_status']] . ',' . $GLOBALS['_LANG']['ps'][$arr['pay_status']] . ',' . $GLOBALS['_LANG']['ss'][$arr['shipping_status']];
            $list[] = $arr;
        }
        $arr = ['item' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 计算该拼团已参与人数
     */
    private function surplus_num($team_id = 0)
    {
        $res = OrderInfo::where('team_id', $team_id)
            ->where('extension_code', 'team_buy')
            ->where('pay_status', PS_PAYED);

        return $res->count();
    }


    /**
     * 获取拼团频道树形
     * @param int $tree_id
     * @return array
     */
    public function teamGetTree($tree_id = 0)
    {
        $three_arr = [];

        $count = TeamCategory::where('parent_id', 0)->where('status', 1)->count();
        if ($count > 0 || $tree_id == 0) {
            $res = TeamCategory::where('parent_id', $tree_id)
                ->where('status', 1)
                ->orderBy('sort_order', 'ASC')
                ->orderBy('id', 'ASC')
                ->get();

            $res = $res ? $res->toArray() : [];
            if ($res) {
                foreach ($res as $k => $row) {
                    $three_arr[$k]['tc_id'] = $row['id'];
                    $three_arr[$k]['name'] = $row['name'];
                    $child_tree = $this->teamGetTree($row['id']);
                    if ($child_tree) {
                        $three_arr[$k]['id'] = $child_tree;
                    }
                }
            }
        }

        return $three_arr;

    }

    /**
     * 获取频道id
     * @param int $tc_id
     * @return array
     */
    public function getCategroyId($tc_id = 0)
    {
        $res = TeamCategory::select('id')
            ->where('id', $tc_id)
            ->orWhere('parent_id', $tc_id)
            ->get();
        $res = $res ? $res->toArray() : [];

        $categroy = [];
        if ($res) {
            $categroy = $this->baseRepository->getFlatten($res);
        } else {
            $categroy[] = $tc_id;
        }

        return $categroy;
    }

    /**
     * 列表链接
     * @param bool $is_add 是否添加（插入）
     * @return  array('href' => $href, 'text' => $text)
     */
    private function list_link($is_add = true)
    {
        $href = 'team.php?act=list';
        if (!$is_add) {
            $href .= '&' . list_link_postfix();
        }

        return ['href' => $href, 'text' => $GLOBALS['_LANG']['team_goods_list'], 'class' => 'icon-reply'];
    }


}

