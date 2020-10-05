<?php

namespace App\Services\User;


use App\Models\EntryCriteria;
use App\Models\Payment;
use App\Models\SellerApplyInfo;
use App\Models\SellerGrade;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Merchant\MerchantCommonService;

class UserMerchantService
{
    protected $baseRepository;
    protected $dscRepository;
    protected $merchantCommonService;
    protected $timeRepository;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService,
        TimeRepository $timeRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->timeRepository = $timeRepository;
    }

    /**
     * 获取申请等级的入驻标准
     *
     * @param string $entry_criteria
     * @return array|string
     */
    public function getEntryCriteria($entry_criteria = '')
    {
        $entry_criteria = unserialize($entry_criteria);//反序列化等级入驻标准
        $res = '';
        if (!empty($entry_criteria)) {
            $entry_criteria = !is_array($entry_criteria) ? explode(",", $entry_criteria) : $entry_criteria;

            $res = EntryCriteria::whereIn('id', $entry_criteria);

            $res = $res->with([
                'getEntryCriteriaChildList'
            ]);

            $res = $this->baseRepository->getToArrayGet($res);

            if ($res) {
                foreach ($res as $k => $v) {
                    $child = $v['get_entry_criteria_child_list'] ?? [];

                    $res['count_charge'] = 0;
                    $res['no_cumulative_price'] = 0;
                    if ($child) {
                        foreach ($child as $key => $val) {
                            if ($val['type'] == 'select' && $val['option_value'] != '') {
                                $child[$key]['option_value'] = explode(',', $val['option_value']);
                            }

                            $res['count_charge'] += $val['charge'];

                            if ($val['is_cumulative'] == 0) {
                                $res['no_cumulative_price'] += $val['charge'];
                            }

                            if (isset($val['charge'])) {
                                $child[$key]['format_charge'] = $this->dscRepository->getPriceFormat($val['charge']);
                            }
                        }
                        $res[$k]['child'] = $child;
                    }

                    $res['format_count_charge'] = $this->dscRepository->getPriceFormat($res['count_charge']);
                    $res['format_no_cumulative_price'] = $this->dscRepository->getPriceFormat($res['no_cumulative_price']);
                }
            }
        }

        return $res;
    }

    /**
     * 获取等级列表
     *
     * @param int $num 列表最大数量
     * @param int $start 列表起始位置
     * @return array
     */
    public function getSellerGradeInfo($num = 10, $start = 0)
    {
        $res = SellerGrade::where('is_open', 1);

        $res = $res->orderBy('id');

        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($num > 0) {
            $res = $res->take($num);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $k => $v) {
                if ($v['entry_criteria']) {
                    $entry_criteria = unserialize($v['entry_criteria']);

                    foreach ($entry_criteria as $key => $val) {
                        $criteria_name = EntryCriteria::where('id', $val)->value('criteria_name');

                        if ($criteria_name) {
                            $entry_criteria[$key] = $criteria_name;
                        }
                    }

                    $res[$k]['grade_img'] = $this->dscRepository->getImagePath($v['grade_img']);
                    $res[$k]['entry_criteria'] = implode(" , ", $entry_criteria);
                }
            }
        }

        return $res;
    }

    /**
     * 获取等级等级申请记录
     *
     * @param $user_id 会员ID
     * @param int $num 列表最大数量
     * @param int $start 列表起始位置
     * @return array
     * @throws \Exception
     */
    public function getMerchantsUpGradeLog($user_id, $num = 10, $start = 0)
    {

        $row = SellerApplyInfo::where('ru_id', $user_id)
            ->orderBy('add_time');

        if ($start > 0) {
            $row = $row->skip($start);
        }

        if ($num > 0) {
            $row = $row->take($num);
        }

        $row = $this->baseRepository->getToArrayGet($row);

        if ($row) {
            foreach ($row as $k => $v) {
                $row[$k]['shop_name'] = $this->merchantCommonService->getShopName($v['ru_id'], 1);

                $grade_name = SellerGrade::where('id', $v['grade_id'])->value('grade_name');

                $row[$k]['grade_name'] = $grade_name;
                $row[$k]['add_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $v['add_time']);
                if ($v['pay_id'] > 0) {
                    $pay_name = Payment::where('pay_id', $v['pay_id'])->value('pay_name');
                    $row[$k]['pay_name'] = $pay_name;
                }

                /* 判断支付状态 */
                switch ($v['pay_status']) {
                    case '0':
                        $row[$k]['status_paid'] = lang('user.also_pay.not_pay');
                        break;
                    case '1':
                        $row[$k]['status_paid'] = lang('user.also_pay.is_pay');
                        break;
                }
                /* 判断申请状态 */
                switch ($v['apply_status']) {
                    case '0':
                        $row[$k]['status_apply'] = lang('user.is_confirm.0');
                        break;
                    case '1':
                        $row[$k]['status_apply'] = lang('user.is_confirm.1');
                        break;
                    case '2':
                        $row[$k]['status_apply'] = lang('user.is_confirm.2');
                        break;
                    case '3':
                        $row[$k]['status_apply'] = "<span style='color:red'>" . lang('user.invalid_input') . "</span>";
                        break;
                }
            }
        }

        return $row;
    }
}