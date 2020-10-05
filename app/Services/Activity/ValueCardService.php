<?php

namespace App\Services\Activity;

use App\Models\MerchantsShopInformation;
use App\Models\ValueCard as ValueCardModel;
use App\Models\ValueCardRecord;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\TimeRepository;

class ValueCardService
{
    protected $baseRepository;
    protected $timeRepository;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
    }

    /*
     * 获取当前用户订单可使用储值卡列表
     * @param user_id 用户ID
     * @param cart_category 购物车商品所属分类ID
     * @param cart_goods 购物车商品ID
     * @return
     */
    public function getUserValueCard($user_id, $cart_goods)
    {
        $arr = [];
        /* 判断用户可用储值卡的使用范围（店铺） */
        $use_merchants = ValueCardModel::select('vid', 'tid')
            ->where('user_id', $user_id)
            ->whereHas('getValueCardType');

        $use_merchants = $use_merchants->with([
            'getValueCardType' => function ($query) {
                $query->select('id', 'use_merchants');
            }
        ]);

        $use_merchants = $use_merchants->get();

        $use_merchants = $use_merchants ? $use_merchants->toArray() : [];

        if ($use_merchants) {
            foreach ($use_merchants as $key => $row) {
                $row = $row['get_value_card_type'] ? array_merge($row, $row['get_value_card_type']) : $row;
                $use_merchants[$key] = $row;
            }
        }

        $shop_ids = [];
        if ($use_merchants) {
            foreach ($use_merchants as $val) {
                if ($val['use_merchants'] == 'all') {
                    $res = MerchantsShopInformation::select('user_id')->where('merchants_audit', 1);
                    $res = $this->baseRepository->getToArrayGet($res);

                    $self_id = [['user_id' => 0]];
                    $res = $this->baseRepository->getArrayMerge($res, $self_id);

                    if ($res) {
                        foreach ($res as $v) {
                            $shop_ids[$val['vid']][] = $v['user_id'];
                        }
                    }
                } elseif ($val['use_merchants'] == 'self') {
                    $res = MerchantsShopInformation::select('user_id')->where('merchants_audit', 1)->where('self_run');

                    $res = $res->get();

                    $res = $res ? $res->toArray() : [];

                    $self_id = [['user_id' => 0]];
                    if ($res) {
                        $res = array_merge($res, $self_id);
                    } else {
                        $res = $self_id;
                    }

                    if ($res) {
                        foreach ($res as $v) {
                            $shop_ids[$val['vid']][] = $v['user_id'];
                        }
                    }
                } elseif ($val['use_merchants'] != '') {
                    $shop_ids[$val['vid']] = explode(',', $val['use_merchants']);
                } else {
                    $shop_ids[$val['vid']][] = 0;
                }
            }
        }

        //仅支持平台和指定商铺
        foreach ($cart_goods as $val) {
            foreach ($shop_ids as $k => $v) {
                if (!in_array($val['ru_id'], $v)) {
                    unset($shop_ids[$k]);
                }
            }
        }

        if (empty($shop_ids)) {
            return ['is_value_cart' => 0];
        } else {
            $value_card_ids = array_keys($shop_ids);
        }

        if ($user_id > 0) {
            $result = ValueCardModel::select('vid', 'tid', 'end_time', 'card_money', 'value_card_sn')
                ->where('user_id', $user_id)
                ->where('card_money', '>', 0)
                ->whereIn('vid', $value_card_ids)
                ->whereHas('getValueCardType');

            $result = $result->with([
                'getValueCardType' => function ($query) {
                    $query->select('id', 'name', 'use_condition', 'spec_goods', 'spec_cat');
                }
            ]);

            $result = $result->get();

            $result = $result ? $result->toArray() : [];

            if ($result) {
                foreach ($result as $k => $v) {
                    $v = $v['get_value_card_type'] ? array_merge($v, $v['get_value_card_type']) : $v;

                    if (empty($v['use_condition'])) {//全部自营
                        $arr[$k]['vc_id'] = $v['vid'];
                        $arr[$k]['name'] = $v['name'];
                        $arr[$k]['card_money'] = $v['card_money'];
                        $arr[$k]['value_card_sn'] = $v['value_card_sn'];
                        $arr[$k]['end_time'] = $this->timeRepository->getLocalDate("Y-m-d H:i:s", $v['end_time']);
                    } elseif ($v['use_condition'] == 1) {//指定分类
                        if (comparison_cat($cart_goods, $v['spec_cat'])) {
                            $arr[$k]['vc_id'] = $v['vid'];
                            $arr[$k]['name'] = $v['name'];
                            $arr[$k]['end_time'] = $this->timeRepository->getLocalDate("Y-m-d H:i:s", $v['end_time']);
                            $arr[$k]['card_money'] = $v['card_money'];
                            $arr[$k]['value_card_sn'] = $v['value_card_sn'];
                        }
                    } elseif ($v['use_condition'] == 2) {//指定商品
                        if (comparison_goods($cart_goods, $v['spec_goods'])) {
                            $arr[$k]['vc_id'] = $v['vid'];
                            $arr[$k]['name'] = $v['name'];
                            $arr[$k]['end_time'] = $this->timeRepository->getLocalDate("Y-m-d H:i:s", $v['end_time']);
                            $arr[$k]['card_money'] = $v['card_money'];
                            $arr[$k]['value_card_sn'] = $v['value_card_sn'];
                        }
                    }
                }
            }
        }

        return $arr;
    }

    /**
     * 获取发放储值卡信息
     *
     * @access  public
     * @param array $where
     * @return  array
     */
    public function getValueCardInfo($where = [])
    {
        if (empty($where)) {
            return [];
        }

        $value_card = ValueCardRecord::selectRaw('*, vc_dis AS vcdis')->whereRaw(1);

        if (isset($where['order_id'])) {
            $value_card = $value_card->where('order_id', $where['order_id']);
        }

        $value_card = $value_card->with(['getValueCardType']);

        $value_card = $value_card->first();

        $value_card = $value_card ? $value_card->toArray() : [];

        $value_card = $value_card && $value_card['get_value_card_type'] ? array_merge($value_card, $value_card['get_value_card_type']) : $value_card;

        if ($value_card) {
            if ($value_card['vcdis'] > 0) {
                $value_card['vc_dis'] = $value_card['vcdis'];
            } else {
                $value_card['vc_dis'] = isset($value_card['get_value_card_type']['vc_dis']) ? $value_card['get_value_card_type']['vc_dis'] : 0;
            }
        }

        return $value_card;
    }
}
