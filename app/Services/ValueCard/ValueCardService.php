<?php

namespace App\Services\ValueCard;


use App\Models\PayCard;
use App\Models\PayCardType;
use App\Models\ValueCard;
use App\Models\ValueCardRecord;
use App\Models\ValueCardType;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;

class ValueCardService
{
    protected $timeRepository;
    protected $config;
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        TimeRepository $timeRepository,
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**用户储值卡列表
     * @param int $user_id
     * @param int $page
     * @param int $size
     */
    public function valueCardList($user_id, $page = 1, $size = 10)
    {
        $res = ValueCard::where('user_id', $user_id);
        $res = $res->with([
            'getValueCardType' => function ($query) {
                $query->select('id', 'name', 'use_condition', 'is_rec');
            }
        ]);

        $res = $res->orderBy('vid', 'desc')
            ->offset(($page - 1) * $size)
            ->limit($size);

        $res = $this->baseRepository->getToArrayGet($res);
        $now = $this->timeRepository->getGmTime();
        if ($res) {
            foreach ($res as $key => $row) {
                $row = $row['get_value_card_type'] ? array_merge($row, $row['get_value_card_type']) : $row;
                unset($row['get_value_card_type']);
                $res[$key] = $row;
                if ($now > $row['end_time']) {
                    $res[$key]['status'] = false;
                } else {
                    $res[$key]['status'] = true;
                }
                /* 先判断是否被使用，然后判断是否开始或过期 */
                $res[$key]['vc_value'] = $this->dscRepository->getPriceFormat($row['vc_value']);
                $res[$key]['use_condition'] = condition_format($row['use_condition']);
                $res[$key]['card_money'] = $this->dscRepository->getPriceFormat($row['card_money']);
                $res[$key]['bind_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $row['bind_time']);
                $res[$key]['end_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $row['end_time']);
            }
        }
        return $res;
    }

    /**绑定储值卡
     * @param $user_id
     * @param string $vc_num
     * @param string $vc_password
     */
    public function addCard($user_id, $vc_num = '', $vc_password = '')
    {
        /* 查询储值卡序列号是否已经存在 */
        $row = ValueCard::where('value_card_sn', $vc_num)
            ->where('value_card_password', $vc_password);
        $row = $this->baseRepository->getToArrayFirst($row);
        $result = [];
        if ($row) {
            $now = $this->timeRepository->getGmTime();
            if ($row['user_id'] == 0) {

                //储值卡未被绑定
                $vc_type = ValueCardType::where('id', $row['tid']);
                $vc_type = $this->baseRepository->getToArrayFirst($vc_type);

                $other = [
                    'user_id' => $user_id,
                    'bind_time' => $now
                ];

                if ($row['end_time']) {
                    if ($now > $row['end_time']) {
                        $result['error'] = 1;
                        $result['msg'] = lang('user.vc_use_expire');
                        return $result;
                    }
                } else {
                    $other['end_time'] = $this->timeRepository->getLocalStrtoTime("+" . $vc_type['vc_indate'] . " months ");
                }

                $limit = ValueCard::where('user_id', $user_id)
                    ->where('tid', $row['tid'])
                    ->count();

                if ($limit >= $vc_type['vc_limit']) {
                    $result['error'] = 1;
                    $result['msg'] = lang('user.vc_limit_expire');
                    return $result;
                }

                $res = ValueCard::where('vid', $row['vid'])
                    ->update($other);

                if ($res) {
                    $result['error'] = 0;
                    $result['msg'] = lang('user.add_value_card_sucess');
                } else {
                    $result['error'] = 1;
                    $result['msg'] = lang('user.unknow_error');
                }
            } else {
                if ($row['user_id'] == $user_id) {
                    //储值卡已添加。
                    $result['error'] = 1;
                    $result['msg'] = lang('user.vc_is_used');
                } else {
                    //储值卡已被绑定。
                    $result['error'] = 1;
                    $result['msg'] = lang('user.vc_is_used_by_other');
                }
            }
        } else {
            //储值卡不存在
            $result['error'] = 1;
            $result['msg'] = lang('user.not_exist');
        }

        return $result;
    }

    /**详情
     * @param $user_id
     * @param int $vc_id
     * @return array
     */
    public function cardDetail($user_id, $vc_id = 0)
    {
        $arr = [];
        if ($vc_id) {
            $res = ValueCardRecord::where('vc_id', $vc_id);

            $res = $res->with([
                'getOrder' => function ($query) {
                    $query->select('order_id', 'order_sn')
                        ->where('main_count', 0);
                }
            ]);

            $res = $res->orderBy('rid', 'desc');
            $res = $this->baseRepository->getToArrayGet($res);

            if ($res) {
                foreach ($res as $key => $row) {
                    if (!empty($row['get_order']) || $row['order_id'] == 0) {
                        if ($row['use_val'] > 0 && $row['add_val'] > 0) {
                            $row['add_val'] = 0;
                            $arr[$key]['use_val'] = $row['use_val'] > 0 ? '+' . $this->dscRepository->getPriceFormat($row['use_val']) : $this->dscRepository->getPriceFormat($row['use_val']);
                        } else {
                            $arr[$key]['use_val'] = $row['use_val'] > 0 ? '-' . $this->dscRepository->getPriceFormat($row['use_val']) : $this->dscRepository->getPriceFormat($row['use_val']);
                        }

                        $arr[$key]['add_val'] = $row['add_val'] > 0 ? '+' . $this->dscRepository->getPriceFormat($row['add_val']) : $this->dscRepository->getPriceFormat($row['add_val']);

                        $arr[$key]['rid'] = $row['rid'];
                        $arr[$key]['order_sn'] = isset($row['get_order']['order_sn']) ? $row['get_order']['order_sn'] : '';
                        $arr[$key]['record_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $row['record_time']);
                    }
                }
            }
        }
        return $arr;
    }

    /**充值绑定储值卡
     * @param $user_id
     * @param $vid
     * @param $pay_card
     * @param $password
     * @return bool
     */
    public function deposit($user_id, $vid, $pay_card, $password)
    {
        /* 查询储值卡序列号是否已经存在 */
        $row = PayCard::where('card_number', $pay_card)
            ->where('card_psd', $password);

        $row = $row->with([
            'getPayCardType' => function ($query) {
                $query->select('type_id', 'type_money');
            }
        ]);

        $row = $this->baseRepository->getToArrayFirst($row);

        if ($row) {
            $row = $this->baseRepository->getArrayMerge($row, $row['get_pay_card_type']);
        }

        $valueCardType = ValueCardType::select('is_rec', 'vc_dis')
            ->whereHas('getValueCard', function ($query) use ($vid) {
                $query->where('vid', $vid);
            });

        $valueCardType = $this->baseRepository->getToArrayFirst($valueCardType);

        $is_rec = $valueCardType ? $valueCardType['is_rec'] : 0;

        $vc_dis = $valueCardType ? $valueCardType['vc_dis'] : 0;
        $result = [];

        if ($row) {
            if ($is_rec == 0) {
                $result['error'] = 1;
                $result['msg'] = lang('user.vc_add_error');
                return $result;
            }
            if ($row['user_id'] == 0 && $is_rec) {
                //储值卡未被绑定
                $use_end_date = PayCardType::where('type_id', $row['c_id'])->value('use_end_date');
                $now = $this->timeRepository->getGmTime();

                if ($now > $use_end_date) {
                    $result['error'] = 1;
                    $result['msg'] = lang('user.vc_money_expire');
                    return $result;
                }

                $other = [
                    'user_id' => $user_id,
                    'used_time' => $now
                ];

                $pay = PayCard::where('id', $row['id'])
                    ->update($other);

                if ($pay) {
                    $res = ValueCard::where('vid', $vid)->increment('card_money', $row['type_money']);

                    if ($res) {
                        $other = [
                            'vc_id' => $vid,
                            'add_val' => $row['type_money'],
                            'vc_dis' => $vc_dis,
                            'record_time' => $now
                        ];

                        ValueCardRecord::insert($other);
                        $result['error'] = 0;
                        $result['msg'] = lang('user.vc_money_use_success');
                        return $result;
                    } else {
                        $other = [
                            'user_id' => 0,
                            'used_time' => 0
                        ];
                        PayCard::where('id', $row['id'])
                            ->update($other);
                        $result['error'] = 1;
                        $result['msg'] = lang('user.unknow_error');
                        return $result;
                    }
                } else {
                    $result['error'] = 1;
                    $result['msg'] = lang('user.unknow_error');
                }
            } else {
                //充值卡已使用或改储值卡无法被充值
                $result['error'] = 1;
                $result['msg'] = lang('user.vc_money_is_used');
            }
        } else {
            //储值卡不存在
            $result['error'] = 1;
            $result['msg'] = lang('user.vc_money_not_exist');
        }
        return $result;
    }
}
