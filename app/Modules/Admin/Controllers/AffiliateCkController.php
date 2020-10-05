<?php

namespace App\Modules\Admin\Controllers;

use App\Models\AffiliateLog;
use App\Models\OrderInfo;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Services\Other\AffiliateCkManageService;

/**
 * 程序说明
 */
class AffiliateCkController extends InitController
{
    protected $affiliateCkManageService;
    protected $baseRepository;

    public function __construct(
        AffiliateCkManageService $affiliateCkManageService,
        BaseRepository $baseRepository
    )
    {
        $this->affiliateCkManageService = $affiliateCkManageService;
        $this->baseRepository = $baseRepository;
    }

    public function index()
    {
        admin_priv('affiliate_ck');

        $affiliate = $GLOBALS['_CFG']['affiliate'] ? unserialize($GLOBALS['_CFG']['affiliate']) : [];
        empty($affiliate) && $affiliate = [];
        $separate_on = $affiliate['on'];

        /*------------------------------------------------------ */
        //-- 分成页
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $auid = isset($_GET['auid']) && !empty($_GET['auid']) ? intval($_GET['auid']) : 0;
            $logdb = $this->affiliateCkManageService->getAffiliatCk();
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['affiliate_ck']);
            $this->smarty->assign('on', $separate_on);
            $this->smarty->assign('logdb', $logdb['logdb']);
            $this->smarty->assign('filter', $logdb['filter']);
            $this->smarty->assign('record_count', $logdb['record_count']);
            $this->smarty->assign('page_count', $logdb['page_count']);
            if (!empty($auid)) {
                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['back_note'], 'href' => "users.php?act=edit&id=$auid"]);
            }

            return $this->smarty->display('affiliate_ck_list.dwt');
        }
        /*------------------------------------------------------ */
        //-- 分页
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $logdb = $this->affiliateCkManageService->getAffiliatCk();
            $this->smarty->assign('logdb', $logdb['logdb']);
            $this->smarty->assign('on', $separate_on);
            $this->smarty->assign('filter', $logdb['filter']);
            $this->smarty->assign('record_count', $logdb['record_count']);
            $this->smarty->assign('page_count', $logdb['page_count']);

            $sort_flag = sort_flag($logdb['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('affiliate_ck_list.dwt'), '', ['filter' => $logdb['filter'], 'page_count' => $logdb['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 取消分成，不再能对该订单进行分成
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'del') {
            $oid = isset($_REQUEST['oid']) && !empty($_REQUEST['oid']) ? intval($_REQUEST['oid']) : 0;
            $stat = OrderInfo::where('order_id', $oid)->value('is_separate');
            $stat = $stat ? $stat : 0;
            if (empty($stat)) {
                OrderInfo::where('order_id', $oid)
                    ->update([
                        'is_separate' => 2
                    ]);
            }
            $links[] = ['text' => $GLOBALS['_LANG']['affiliate_ck'], 'href' => 'affiliate_ck.php?act=list'];
            return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 撤销某次分成，将已分成的收回来
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'rollback') {
            $logid = isset($_REQUEST['logid']) && !empty($_REQUEST['logid']) ? intval($_REQUEST['logid']) : 0;

            $stat = $this->affiliateCkManageService->getAffiliateLog($logid);
            if (!empty($stat)) {
                if ($stat['separate_type'] == 1) {
                    //推荐订单分成
                    $flag = -2;
                } else {
                    //推荐注册分成
                    $flag = -1;
                }
                log_account_change($stat['user_id'], -$stat['money'], 0, -$stat['point'], 0, $GLOBALS['_LANG']['loginfo']['cancel']);

                AffiliateLog::where('log_id', $logid)->update([
                    'separate_type' => $flag
                ]);
            }
            $links[] = ['text' => $GLOBALS['_LANG']['affiliate_ck'], 'href' => 'affiliate_ck.php?act=list&uselastfilter=1'];
            return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 分成
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'separate') {
            load_helper('order');
            $affiliate = $GLOBALS['_CFG']['affiliate'] ? unserialize($GLOBALS['_CFG']['affiliate']) : [];
            empty($affiliate) && $affiliate = [];

            $separate_by = $affiliate['config']['separate_by'];

            $oid = isset($_REQUEST['oid']) && !empty($_REQUEST['oid']) ? intval($_REQUEST['oid']) : 0;

            $row = OrderInfo::where('order_id', $oid)
                ->whereHas('getUsers');
            $row = $this->baseRepository->getToArrayFirst($row);

            if ($row) {
                $row['goods_amount'] = $row['goods_amount'] - $row['discount'] - $row['coupons'] - $row['integral_money'] - $row['bonus'];
            }

            $order_sn = $row['order_sn'];

            if ($row && empty($row['is_separate'])) {
                $affiliate['config']['level_point_all'] = (float)$affiliate['config']['level_point_all'];
                $affiliate['config']['level_money_all'] = (float)$affiliate['config']['level_money_all'];
                if ($affiliate['config']['level_point_all']) {
                    $affiliate['config']['level_point_all'] /= 100;
                }
                if ($affiliate['config']['level_money_all']) {
                    $affiliate['config']['level_money_all'] /= 100;
                }

                $money = round($affiliate['config']['level_money_all'] * $row['goods_amount'], 2);
                $money = floatval($money);
                $integral = integral_to_give(['order_id' => $oid, 'extension_code' => '']);
                $point = round($affiliate['config']['level_point_all'] * intval($integral['rank_points']), 0);
                $point = floatval($point);

                if (empty($separate_by)) {
                    //推荐注册分成
                    $num = count($affiliate['item']);

                    for ($i = 0; $i < $num; $i++) {
                        $affiliate['item'][$i]['level_point'] = (float)$affiliate['item'][$i]['level_point'];
                        $affiliate['item'][$i]['level_money'] = (float)$affiliate['item'][$i]['level_money'];

                        if ($affiliate['item'][$i]['level_point']) {
                            $affiliate['item'][$i]['level_point'] /= 100;
                        }

                        if ($affiliate['item'][$i]['level_money']) {
                            $affiliate['item'][$i]['level_money'] /= 100;
                        }

                        $setmoney = round($money * $affiliate['item'][$i]['level_money'], 2);
                        $setpoint = round($point * $affiliate['item'][$i]['level_point'], 0);

                        $row = Users::where('user_id', $row['user_id'])
                            ->whereHas('getUserParent');

                        $row = $row->with(['getUserParent']);

                        $row = $this->baseRepository->getToArrayFirst($row);

                        if ($row) {
                            $row['user_id'] = $row['parent_id'];
                            $row['user_name'] = $row['get_user_parent']['user_name'] ?? '';
                        }

                        $row['user_name'] = $row['user_name'] ?? '';

                        $up_uid = $row['user_id'] ?? 0;
                        if (empty($up_uid) || empty($row['user_name'])) {
                            break;
                        } else {
                            $info = sprintf($GLOBALS['_LANG']['separate_info'], $order_sn, $setmoney, $setpoint);
                            log_account_change($up_uid, $setmoney, 0, $setpoint, 0, $info);
                            $this->affiliateCkManageService->writeAffiliateLog($oid, $up_uid, $row['user_name'], $setmoney, $setpoint, $separate_by);
                        }
                    }
                } else {

                    //推荐订单分成
                    $row = OrderInfo::where('order_id', $oid)
                        ->whereHas('getUsers');

                    $row = $row->with([
                        'getUsers'
                    ]);

                    $row = $this->baseRepository->getToArrayFirst($row);

                    if ($row) {
                        $row['user_name'] = $row['get_users']['user_name'];
                    }

                    $up_uid = $row['parent_id'] ?? 0;
                    $row['user_name'] = $row['user_name'] ?? '';

                    if (!empty($up_uid) && $up_uid > 0) {
                        $info = sprintf($GLOBALS['_LANG']['separate_info'], $order_sn, $money, $point);
                        log_account_change($up_uid, $money, 0, $point, 0, $info);
                        $this->affiliateCkManageService->writeAffiliateLog($oid, $up_uid, $row['user_name'], $money, $point, $separate_by);
                    } else {
                        $links[] = ['text' => $GLOBALS['_LANG']['affiliate_ck'], 'href' => 'affiliate_ck.php?act=list&uselastfilter=1'];
                        return sys_msg($GLOBALS['_LANG']['edit_fail'], 1, $links);
                    }
                }

                OrderInfo::where('order_id', $oid)
                    ->update([
                        'is_separate' => 1
                    ]);
            }

            $links[] = ['text' => $GLOBALS['_LANG']['affiliate_ck'], 'href' => 'affiliate_ck.php?act=list'];
            return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
        }
    }
}
