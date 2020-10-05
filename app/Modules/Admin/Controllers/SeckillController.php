<?php

namespace App\Modules\Admin\Controllers;

use App\Models\Goods;
use App\Models\Seckill;
use App\Models\SeckillGoods;
use App\Models\SeckillTimeBucket;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Category\CategoryService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Seckill\SeckillManageService;
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
    protected $merchantCommonService;
    protected $seckillManageService;
    protected $storeCommonService;

    public function __construct(
        CategoryService $categoryService,
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        GoodsCommonService $goodsCommonService,
        MerchantCommonService $merchantCommonService,
        SeckillManageService $seckillManageService,
        StoreCommonService $storeCommonService
    )
    {
        $this->categoryService = $categoryService;
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
        $this->goodsCommonService = $goodsCommonService;
        $this->merchantCommonService = $merchantCommonService;
        $this->seckillManageService = $seckillManageService;
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
        //ecmoban模板堂 --zhuo start
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end
        /*------------------------------------------------------ */
        //-- 秒杀活动列表页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            admin_priv('seckill_manage');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['seckill_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['seckill_add'], 'href' => 'seckill.php?act=add']);
            $this->smarty->assign('action_link2', ['text' => $GLOBALS['_LANG']['seckill_time_bucket'], 'href' => 'seckill.php?act=time_bucket']);
            $this->smarty->assign('full_page', 1);

            $list = $this->seckillManageService->getSeckillList();

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $this->smarty->assign('seckill_list', $list['seckill']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            //区分自营和店铺
            self_seller(basename(request()->getRequestUri()));


            return $this->smarty->display('seckill_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 秒杀时间段设置
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'time_bucket') {
            admin_priv('seckill_manage');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['seckill_time_bucket']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['time_bucket_add'], 'href' => 'seckill.php?act=time_add']);
            $this->smarty->assign('action_link2', ['text' => $GLOBALS['_LANG']['seckill_list'], 'href' => 'seckill.php?act=list']);

            $list = $this->seckillManageService->getTimeBucketList();

            $this->smarty->assign('time_bucket', $list);
            $this->smarty->assign('full_page', 1);

            return $this->smarty->display('seckill_time_bucket.dwt');
        }

        /*------------------------------------------------------ */
        //-- 翻页、排序
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'query') {
            $list = $this->seckillManageService->getSeckillList();
            $this->smarty->assign('seckill_list', $list['seckill']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('seckill_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 秒杀时段 翻页、排序
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'tb_query') {
            $list = $this->seckillManageService->getTimeBucketList();
            $this->smarty->assign('time_bucket', $list);

            return make_json_result($this->smarty->fetch('seckill_time_bucket.dwt'), '', []);
        }

        /*------------------------------------------------------ */
        //-- 秒杀商品 翻页、排序
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'sg_query') {
            load_helper('goods', 'admin');
            $sec_id = empty($_REQUEST['sec_id']) ? 0 : intval($_REQUEST['sec_id']);
            $tb_id = empty($_REQUEST['tb_id']) ? 0 : intval($_REQUEST['tb_id']);
            $list = get_add_seckill_goods($sec_id, $tb_id);

            $this->smarty->assign('seckill_goods', $list['seckill_goods']);
            $this->smarty->assign('cat_goods', $list['cat_goods']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            return make_json_result($this->smarty->fetch('seckill_set_goods_info.dwt'), '', ['filter' => $list['filter'], 'goods_ids' => $list['cat_goods'], 'page_count' => $list['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 秒杀活动添加页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            admin_priv('seckill_manage');

            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('action_link', ['href' => 'seckill.php?act=list', 'text' => $GLOBALS['_LANG']['seckill_list']]);
            $this->smarty->assign('cfg_lang', $GLOBALS['_CFG']['lang']);
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
                $seckill_arr = $this->seckillManageService->getSeckillInfo();
            }

            $this->smarty->assign('sec', $seckill_arr);

            return $this->smarty->display('seckill_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 秒杀活动添加/编辑的处理
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            /* 获得日期信息 */
            $sec_id = empty($_REQUEST['sec_id']) ? '' : intval($_REQUEST['sec_id']);
            $acti_title = $_REQUEST['acti_title'] ? trim($_REQUEST['acti_title']) : '';
            $begin_time = local_strtotime($_REQUEST['begin_time']);
            $acti_time = local_strtotime($_REQUEST['acti_time']);
            $is_putaway = empty($_REQUEST['is_putaway']) ? 0 : intval($_REQUEST['is_putaway']);
            $add_time = gmtime();//添加时间;
            $ru_id = $adminru['ru_id'];
            $review_status = isset($_REQUEST['review_status']) ? intval($_REQUEST['review_status']) : 3;

            if ($_REQUEST['act'] == 'insert') {
                /*检查名称是否重复*/
                $is_only = Seckill::where('acti_title', $_REQUEST['acti_title'])->count();

                if ($is_only > 0) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['title_exist'], stripslashes($_REQUEST['acti_title'])), 1);
                }
                /* 插入数据库。 */
                $data = [
                    'ru_id' => $ru_id,
                    'acti_title' => $acti_title,
                    'begin_time' => $begin_time,
                    'acti_time' => $acti_time,
                    'is_putaway' => $is_putaway,
                    'add_time' => $add_time,
                    'review_status' => $review_status
                ];
                $res = Seckill::insert($data);
                if ($res > 0) {
                    /* 提示信息 */
                    $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                    $link[0]['href'] = 'seckill.php?act=list';

                    return sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . $_POST['acti_title'] . "&nbsp;" . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
                } else {
                    return sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . $_POST['acti_title'] . "&nbsp;" . $GLOBALS['_LANG']['attradd_failed'], 1);
                }
            } else {
                /*检查名称是否重复*/
                $is_only = Seckill::where('acti_title', $_POST['acti_title'])
                    ->where('sec_id', '<>', $sec_id)
                    ->count();

                if ($is_only > 0) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['title_exist'], stripslashes($_REQUEST['acti_title'])), 1);
                }

                /* 修改入库。 */
                $data = [
                    'acti_title' => $acti_title,
                    'begin_time' => $begin_time,
                    'acti_time' => $acti_time,
                    'is_putaway' => $is_putaway,
                    'review_status' => $review_status
                ];
                Seckill::where('sec_id', $sec_id)->update($data);

                /* 清除缓存 */
                clear_cache_files();

                /* 提示信息 */
                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'seckill.php?act=list';

                return sys_msg($GLOBALS['_LANG']['edit'] . "&nbsp;" . $_POST['acti_title'] . "&nbsp;" . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
            }
        }

        /*------------------------------------------------------ */
        //-- 秒杀时段添加页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'time_add' || $_REQUEST['act'] == 'time_edit') {
            admin_priv('seckill_manage');

            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('action_link', ['href' => 'seckill.php?act=time_bucket', 'text' => $GLOBALS['_LANG']['seckill_time_bucket']]);
            $this->smarty->assign('cfg_lang', $GLOBALS['_CFG']['lang']);
            $tb_id = !empty($_GET['tb_id']) ? intval($_GET['tb_id']) : 0;
            if ($_REQUEST['act'] == 'time_add') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['time_bucket_add']);
                $this->smarty->assign('form_act', 'time_insert');

                $tb_arr['begin_time '] = SeckillTimeBucket::max('end_time');
                $tb_arr['begin_time '] = $begin_time = $tb_arr['begin_time '] ? $tb_arr['begin_time '] : '';

                $tb_arr['begin_time '] = explode(':', $begin_time);

                if ($begin_time) {
                    $begin_second = $tb_arr['begin_time '][2] + 1;
                    $begin_minute = $tb_arr['begin_time '][1];
                    $begin_hour = $tb_arr['begin_time '][0];

                    if ($begin_second >= 60) {
                        $begin_second = 00;
                        $begin_minute = $begin_minute + 1;
                        if ($begin_minute >= 60) {
                            $begin_minute = 00;
                            $begin_hour = $begin_hour + 1;
                        }
                    }

                    $tb_arr['begin_second'] = $begin_second;
                    $tb_arr['begin_minute'] = $begin_minute;
                    $tb_arr['begin_hour'] = $begin_hour;
                }
            } else {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['seckill_edit']);
                $this->smarty->assign('form_act', 'time_update');
                $tb_arr = $this->seckillManageService->getTimeBucketInfo($tb_id);
            }

            $this->smarty->assign('tb', $tb_arr);

            return $this->smarty->display('seckill_time_bucket_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 秒杀时段添加/编辑的处理
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'time_insert' || $_REQUEST['act'] == 'time_update') {
            /* 获得日期信息 */
            $tb_id = empty($_REQUEST['tb_id']) ? '' : intval($_REQUEST['tb_id']);
            $title = $_REQUEST['title'] ? trim($_REQUEST['title']) : '';
            $begin_hour = $_REQUEST['begin_hour'] > 0 && $_REQUEST['begin_hour'] < 24 ? intval($_REQUEST['begin_hour']) : 0;
            $begin_minute = $_REQUEST['begin_minute'] > 0 && $_REQUEST['begin_minute'] < 60 ? intval($_REQUEST['begin_minute']) : 0;
            $begin_second = $_REQUEST['begin_second'] > 0 && $_REQUEST['begin_second'] < 60 ? intval($_REQUEST['begin_second']) : 0;
            $end_hour = $_REQUEST['end_hour'] > 0 && $_REQUEST['end_hour'] < 24 ? intval($_REQUEST['end_hour']) : 0;
            $end_minute = $_REQUEST['end_minute'] > 0 && $_REQUEST['end_minute'] < 60 ? intval($_REQUEST['end_minute']) : 0;
            $end_second = $_REQUEST['end_second'] > 0 && $_REQUEST['end_second'] < 60 ? intval($_REQUEST['end_second']) : 0;

            $begin_time = $begin_hour . ':' . $begin_minute . ':' . $begin_second;
            $end_time = $end_hour . ':' . $end_minute . ':' . $end_second;

            if (!$this->seckillManageService->contrastTime($begin_time, $end_time)) {
                return sys_msg($GLOBALS['_LANG']['end_lt_begin'], 1);
            }

            if ($_REQUEST['act'] == 'time_insert') {
                /*检查名称是否重复*/
                $is_only = SeckillTimeBucket::where('title', $title)->count();

                if ($is_only > 0) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['title_exist'], stripslashes($title)), 1);
                }
                /* 插入数据库。 */
                $data = [
                    'title' => $title,
                    'begin_time' => $begin_time,
                    'end_time' => $end_time
                ];
                $res = SeckillTimeBucket::insert($data);

                if ($res > 0) {
                    /* 提示信息 */
                    $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                    $link[0]['href'] = 'seckill.php?act=time_bucket';

                    return sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . $title . "&nbsp;" . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
                } else {
                    return sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . $title . "&nbsp;" . $GLOBALS['_LANG']['attradd_failed'], 1);
                }
            } else {
                /*检查名称是否重复*/
                $is_only = SeckillTimeBucket::where('title', $title)->where('id', '<>', $tb_id)->count();
                if ($is_only > 0) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['title_exist'], stripslashes($title)), 1);
                }

                /* 判断当前编辑的结束时间是否规范（必须大于当前时段开始时间且小于下一时间段结束时间） */
                $row = $this->seckillManageService->editEndTime($tb_id, $end_time);
                if (!$row) {
                    return sys_msg($GLOBALS['_LANG']['end_lt_next_end'], 1);
                }

                /* 修改入库 */
                $data = [
                    'title' => $title,
                    'begin_time' => $begin_time,
                    'end_time' => $end_time
                ];
                SeckillTimeBucket::where('id', $tb_id)->update($data);

                /* 清除缓存 */
                clear_cache_files();

                /* 提示信息 */
                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'seckill.php?act=time_bucket';

                return sys_msg($GLOBALS['_LANG']['edit'] . "&nbsp;" . $title . "&nbsp;" . $GLOBALS['_LANG']['attradd_succed'], 0, $link);
            }
        }

        /*------------------------------------------------------ */
        //-- 活动上下线
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_putaway') {
            $id = intval($_REQUEST['id']);
            $val = intval($_REQUEST['val']);

            /* 修改 */
            $data = ['is_putaway' => $val];
            $result = Seckill::where('sec_id', $id)->update($data);

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
            $sec_id = !empty($_GET['sec_id']) ? intval($_GET['sec_id']) : 0;
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['set_seckill_goods']);

            //商家店铺区分
            $ru_id = Seckill::where('sec_id', $sec_id)->value('ru_id');
            $ru_id = $ru_id ? $ru_id : 0;
            $list = '';
            if ($ru_id) {
                $list = "&seller_list=1";
            }

            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['seckill_list'], 'href' => 'seckill.php?act=list' . $list]);

            $list = $this->seckillManageService->getTimeBucketList();
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
            load_helper('goods', 'admin');

            $sec_id = !empty($_GET['sec_id']) ? intval($_GET['sec_id']) : 0;
            $tb_id = !empty($_GET['tb_id']) ? intval($_GET['tb_id']) : 0;
            set_default_filter(); //设置默认筛选


            //商家店铺区分
            $ru_id = Seckill::where('sec_id', $sec_id)->value('ru_id');
            $ru_id = $ru_id ? $ru_id : 0;

            $this->smarty->assign('ru_id', $ru_id);

            $list = get_add_seckill_goods($sec_id, $tb_id);
            $this->smarty->assign('seckill_goods', $list['seckill_goods']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);
            $this->smarty->assign('cat_goods', $list['cat_goods']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['seckill_goods_info']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['set_seckill_goods'], 'href' => "seckill.php?act=set_goods&sec_id=$sec_id"]);
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

            $res = SeckillGoods::where('id', $id);
            $res = $this->baseRepository->getToArrayFirst($res);

            $sec_id = $res['sec_id'] ?? 0;
            $tb_id = $res['tb_id'] ?? 0;
            if ($id) {
                SeckillGoods::where('id', $id)->delete();
            }
            $url = 'seckill.php?act=sg_query&sec_id=' . $sec_id . '&tb_id=' . $tb_id . str_replace('act=sg_remove', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 删除秒杀活动
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $sec_id = intval($_REQUEST['id']);
            if ($sec_id) {
                $res = Seckill::where('sec_id', $sec_id)->delete();
                if ($res > 0) {
                    SeckillGoods::where('sec_id', $sec_id)->delete();
                }
            }
            $url = 'seckill.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 批量操作
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch') {
            /* 检查权限 */
            admin_priv('seckill_manage');

            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['not_select_data'], 1);
            }
            $ids = !empty($_POST['checkboxes']) ? join(',', $_POST['checkboxes']) : 0;

            if (isset($_POST['type'])) {
                // 删除秒杀
                if ($_POST['type'] == 'batch_remove') {
                    $ids = $this->baseRepository->getExplode($ids);
                    $res = Seckill::whereIn('sec_id', $ids)->delete();

                    if ($res > 0) {
                        SeckillGoods::whereIn('sec_id', $ids)->delete();
                    }
                    /* 记日志 */
                    admin_log('', 'batch_remove', 'seckill_manage');

                    /* 清除缓存 */
                    clear_cache_files();

                    $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'seckill.php?act=list&' . list_link_postfix()];
                    return sys_msg($GLOBALS['_LANG']['batch_drop_ok'], 0, $links);
                } // 审核
                elseif ($_POST['type'] == 'review_to') {
                    // review_status = 3审核通过 2审核未通过
                    $review_status = $_POST['review_status'];
                    $review_content = !empty($_POST['review_content']) ? trim($_POST['review_content']) : '';

                    $ids = $this->baseRepository->getExplode($ids);

                    $data = ['review_status' => $review_status];
                    $res = Seckill::whereIn('sec_id', $ids)->update($data);

                    if ($res >= 0) {
                        $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'seckill.php?act=list&seller_list=1&' . list_link_postfix()];
                        return sys_msg($GLOBALS['_LANG']['seckill_adopt_status_success'], 0, $lnk);
                    }
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 删除秒杀时段
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'tb_remove') {
            $tb_id = intval($_REQUEST['id']);
            if ($tb_id) {
                SeckillTimeBucket::where('id', $tb_id)->delete();

                SeckillGoods::where('tb_id', $tb_id)->delete();
            }
            $url = 'seckill.php?act=tb_query&' . str_replace('act=tb_remove', '', request()->server('QUERY_STRING'));
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
                    $res = Goods::where('is_on_sale', 1)
                        ->where('is_delete', 0)
                        ->whereIn('goods_id', $goods_info);

                    //ecmoban模板堂 --zhuo start
                    if ($GLOBALS['_CFG']['review_goods'] == 1) {
                        $res = $res->where('review_status', '>', 2);
                    }
                    //ecmoban模板堂 --zhuo end
                    $goods_list = $this->baseRepository->getToArrayGet($res);

                    foreach ($goods_list as $k => $v) {
                        $goods_list[$k]['shop_price'] = price_format($v['shop_price']);
                    }

                    $this->smarty->assign('goods_list', $goods_list);
                    $this->smarty->assign('goods_count', count($goods_list));
                }
            }
            /* 取得分类列表 */
            //获取下拉列表 by wu start
            set_default_filter(0, $cat_id); //设置默认筛选
            $select_category_html = isset($select_category_html) ? $select_category_html : '';
            $this->smarty->assign('parent_category', get_every_category($cat_id)); //上级分类导航
            $this->smarty->assign('select_category_html', $select_category_html);
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
            $result['spec_attr'] = isset($_REQUEST['spec_attr']) && !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';
            if ($result['spec_attr']) {
                $result['spec_attr'] = strip_tags(urldecode($result['spec_attr']));
                $result['spec_attr'] = json_str_iconv($result['spec_attr']);
                if (!empty($result['spec_attr'])) {
                    $spec_attr = dsc_decode($result['spec_attr'], true);
                }
            }
            $sort_order = isset($_REQUEST['sort_order']) ? $_REQUEST['sort_order'] : 1;
            $cat_id = isset($_REQUEST['cat_id']) ? explode('_', $_REQUEST['cat_id']) : [];
            $brand_id = isset($_REQUEST['brand_id']) ? intval($_REQUEST['brand_id']) : 0;
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

            $result['mode'] = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';
            $this->smarty->assign('temp', $temp);

            $where = [
                'sort_order' => $sort_order,
                'brand_id' => $brand_id,
                'cat_id' => $cat_id,
                'type' => $type,
                'keyword' => $keyword,
                'goods_ids' => $result['goods_ids']
            ];

            if ($type == 1) {
                $where['is_page'] = 1;

                $list = $this->seckillManageService->getPisGoodsList($where);
                $goods_list = $list['list'];
                $filter = $list['filter'];
                $filter['cat_id'] = $cat_id[0];
                $filter['sort_order'] = $sort_order;
                $filter['keyword'] = $keyword;
                $this->smarty->assign('filter', $filter);
            } else {
                $where['is_page'] = 0;
                $goods_list = $this->seckillManageService->getPisGoodsList($where);
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

            $data = ['sec_price' => $sec_price];
            $res = SeckillGoods::where('id', $id)->update($data);
            if ($res >= 0) {
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

            $data = ['sec_num' => $sec_num];
            $res = SeckillGoods::where('id', $id)->update($data);

            if ($res >= 0) {
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

            $data = ['sec_limit' => $sec_limit];
            $res = SeckillGoods::where('id', $id)->update($data);

            if ($res >= 0) {
                clear_cache_files();
                return make_json_result($sec_limit);
            }
        }
    }
}
