<?php

namespace App\Services\PayCard;

use App\Models\BonusType;
use App\Models\Goods;
use App\Models\PayCard;
use App\Models\PayCardType;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Merchant\MerchantCommonService;

class PayCardManageService
{
    protected $baseRepository;
    protected $timeRepository;
    protected $merchantCommonService;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
    }

    /**
     * 获取充值卡类型列表
     * @access  public
     * @return void
     */
    public function getTypeList()
    {
        /* 获得所有充值卡类型的发放数量 */
        $res = PayCard::selectRaw('c_id, COUNT(*) AS sent_count')->groupBy('c_id');
        $res = $this->baseRepository->getToArrayGet($res);

        $sent_arr = [];
        foreach ($res as $row) {
            $sent_arr[$row['c_id']] = $row['sent_count'];
        }

        /* 获得所有充值卡类型的发放数量 */

        $res = PayCard::selectRaw('c_id, COUNT(*) AS used_count')
            ->where('used_time', '<>', 0)
            ->groupBy('c_id');
        $res = $this->baseRepository->getToArrayGet($res);

        $used_arr = [];
        foreach ($res as $row) {
            $used_arr[$row['c_id']] = $row['used_count'];
        }

        /* 查询条件 */
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'type_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $filter['record_count'] = PayCardType::count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        $arr = [];
        $res = PayCardType::orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])
            ->limit($filter['page_size']);
        $res = $this->baseRepository->getToArrayGet($res);

        foreach ($res as $row) {
            if (isset($row['send_type']) && !empty($row['send_type'])) {
                $row['send_by'] = $GLOBALS['_LANG']['send_by'][$row['send_type']];
            } else {
                $row['send_by'] = '';
            }
            $row['send_count'] = isset($sent_arr[$row['type_id']]) ? $sent_arr[$row['type_id']] : 0;
            $row['use_count'] = isset($used_arr[$row['type_id']]) ? $used_arr[$row['type_id']] : 0;

            $arr[] = $row;
        }

        $arr = ['item' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }


    /**
     * 查询充值卡类型的商品列表
     *
     * @access  public
     * @param integer $type_id
     * @return  array
     */
    public function getBonusGoods($type_id)
    {
        $res = Goods::where('bonus_type_id', $type_id);
        $row = $this->baseRepository->getToArrayGet($res);

        return $row;
    }

    /**
     * 获取充值卡红包列表
     * @access  public
     * @param   $page_param
     * @return void
     */
    public function getBonusList()
    {
        /* 查询条件 */
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        //$filter['bonus_type'] = empty($_REQUEST['bonus_type']) ? 0 : intval($_REQUEST['bonus_type']);

        $res = PayCard::whereRaw(1);
        if (isset($_GET['tid']) && !empty($_GET['tid'])) {
            $res = $res->where('c_id', $_GET['tid']);
        }
        //$where .= empty($filter['bonus_type']) ? '' : " AND c_id='$filter[bonus_type]'";

        $filter['record_count'] = $res->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        $res = $res->with(['getPayCardType', 'getUsers']);

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])
            ->limit($filter['page_size']);

        $row = $this->baseRepository->getToArrayGet($res);

        foreach ($row as $key => $val) {
            $val['type_name'] = $val['get_pay_card_type']['type_name'] ?? '';
            $val['type_money'] = $val['get_pay_card_type']['type_money'] ?? '';

            $val['user_name'] = $val['get_users']['user_name'] ?? '';
            $val['email'] = $val['get_users']['email'] ?? '';

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                $val['user_name'] = $this->dscRepository->stringToStar($val['user_name']);
                $val['email'] = $this->dscRepository->stringToStar($val['email']);
            }

            $val['used_time'] = $val['used_time'] == 0 ?
                $GLOBALS['_LANG']['no_use'] : $this->timeRepository->getLocalDate($GLOBALS['_CFG']['date_format'], $val['used_time']);
            if (isset($val['emailed']) && !empty($val['emailed'])) {
                $val['emailed'] = $GLOBALS['_LANG']['mail_status'][$val['emailed']];
            } else {
                $val['emailed'] = '';
            }
            $row[$key] = $val;
        }

        $arr = ['item' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
        return $arr;
    }

    /**
     * 取充值卡类型信息
     * @param int $bonus_type_id 充值卡类型id
     * @return  array
     */
    public function bonusTypeInfo($bonus_type_id)
    {
        $res = BonusType::where('type_id', $bonus_type_id);
        $row = $this->baseRepository->getToArrayFirst($res);
        return $row;
    }
}
