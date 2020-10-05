<?php

namespace App\Services\Activity;

use App\Libraries\Pager;
use App\Models\BonusType;
use App\Models\UserBonus;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Merchant\MerchantCommonService;
use Illuminate\Support\Facades\DB;

/**
 * 活动 ->【红包】
 */
class BonusService
{
    protected $baseRepository;
    protected $timeRepository;
    protected $merchantCommonService;
    protected $dscRepository;
    protected $config;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        TimeRepository $timeRepository,
        MerchantCommonService $merchantCommonService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 红包信息
     *
     * @param int $type_id
     * @return array
     */
    public function getBonusInfo($type_id = 0)
    {
        $row = BonusType::where('type_id', $type_id);
        $row = $this->baseRepository->getToArrayFirst($row);

        return $row;
    }

    /**  //ecmoban模板堂 --zhuo 可用、即将到期、已使用
     *
     * @access  public
     * @param int $user_id 用户ID
     * @param int $num 列表显示条数
     * @param int $start 显示起始位置
     *
     * @return  array       $arr             数量
     */
    public function getUserBounsNewCount($user_id = 0, $type = 0, $cart_ru_id = -1)
    {
        $day = $this->timeRepository->getLocalGetDate();
        $cur_date = $this->timeRepository->getLocalMktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);
        $before_date = $this->timeRepository->getLocalMktime(0, 0, 0, $day['mon'], $day['mday'], $day['year']) - 2 * 24 * 3600; //前三天时间

        $time = $this->timeRepository->getGmTime();
        //获取表前缀
        $prefix = DB::connection()->getTablePrefix();

        $res = UserBonus::from('user_bonus as u')
            ->leftjoin('bonus_type as b', 'u.bonus_type_id', '=', 'b.type_id')
            ->where('u.user_id', $user_id)
            ->where('b.review_status', 3);

        if ($cart_ru_id && $cart_ru_id > -1) {
            $res = $res->whereRaw("IF(" . $prefix . "b.usebonus_type > 0, 1, " . $prefix . "b.user_id IN(" . $cart_ru_id . "))");
        }

        if ($type == 0 || $type == 1) {
            $res = $res->where('u.order_id', 0);
        } elseif ($type == 2) {
            $res = $res->where('u.order_id', '>', 0);
        }
        //0:可用
        //1即将到期
        //2已使用
        //3已过期

        if ($type == 0) {
            $res = $res->where(function ($query) use ($time, $prefix) {
                $query = $query->where('b.use_end_date', '>', $time);
                $query->whereRaw("if(" . $prefix . "b . date_type = 1," . $prefix . "u . bind_time > $time - (60 * 60 * 24 * " . $prefix . "b . valid_period),1=1)");
            });
            $res = $res->where('u.used_time', '');
        } elseif ($type == 1) {
            $res = $res->where('b.use_start_date', '>=', $before_date)
                ->where('b.use_end_date', '>', $cur_date);

        } elseif ($type == 3) {
            //如果时间类型:红包有效期
            //就加上条件:绑定用户的时间大于|小于,当前时间戳减去红包有效期的时间戳
            $res = $res->where(function ($query) use ($time, $prefix) {
                $query = $query->where('b.use_end_date', '<', $time);
                $query->orWhereRaw("if(" . $prefix . "b . date_type = 1," . $prefix . "u . bind_time < $time - (60 * 60 * 24 * " . $prefix . "b . valid_period),'')");
            });
            $res = $res->where('u.used_time', '');
        }

        $res = $res->count();

        return $res;
    }

    /**
     * 可用、即将到期、已使用、已过期
     *
     * @param int $user_id
     * @param int $page
     * @param int $type
     * @param string $pageFunc
     * @param int $amount
     * @param int $size
     * @param int $cart_ru_id
     * @return Pager|array
     * @throws \Exception
     */
    public function getUserBounsNewList($user_id = 0, $page = 1, $type = 0, $pageFunc = '', $amount = 0, $size = 10, $cart_ru_id = -1)
    {
        $record_count = $this->getUserBounsNewCount($user_id, $type, $cart_ru_id);

        $day = $this->timeRepository->getLocalGetDate();
        $cur_date = $this->timeRepository->getLocalMktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);
        $before_date = $this->timeRepository->getLocalMktime(0, 0, 0, $day['mon'], $day['mday'], $day['year']) - 2 * 24 * 3600; //前三天时间

        $time = $this->timeRepository->getGmTime();
        //获取表前缀
        $prefix = DB::connection()->getTablePrefix();

        $res = UserBonus::from('user_bonus as u')
            ->select('u.*', 'b.*', 'b.user_id as ru_id')
            ->leftjoin('bonus_type as b', 'u.bonus_type_id', '=', 'b.type_id')
            ->where('u.user_id', $user_id)
            ->where('b.review_status', 3);

        if ($cart_ru_id && $cart_ru_id > -1) {
            $res = $res->whereRaw("IF(" . $prefix . "b.usebonus_type > 0, 1, " . $prefix . "b.user_id IN(" . $cart_ru_id . "))");
        }

        if ($type == 0 || $type == 1) {
            $res = $res->where('u.order_id', 0);
        } elseif ($type == 2) {
            $res = $res->where('u.order_id', '>', 0);
        }
        //0:可用
        //1即将到期
        //2已使用
        //3已过期

        if ($type == 0) {
            $res = $res->where(function ($query) use ($time, $prefix) {
                $query = $query->where('b.use_end_date', '>', $time);
                $query->whereRaw("if(" . $prefix . "b . date_type = 1," . $prefix . "u . bind_time > $time - (60 * 60 * 24 * " . $prefix . "b . valid_period),1=1)");
            });
            $res = $res->where('u.used_time', '');
        } elseif ($type == 1) {
            $res = $res->where('b.use_start_date', '>=', $before_date)
                ->where('b.use_end_date', '>', $cur_date);

        } elseif ($type == 3) {
            //如果时间类型:红包有效期
            //就加上条件:绑定用户的时间大于|小于,当前时间戳减去红包有效期的时间戳
            $res = $res->where(function ($query) use ($time, $prefix) {
                $query = $query->where('b.use_end_date', '<', $time);
                $query->orWhereRaw("if(" . $prefix . "b . date_type = 1," . $prefix . "u . bind_time < $time - (60 * 60 * 24 * " . $prefix . "b . valid_period),'')");
            });
            $res = $res->where('u.used_time', '');
        }

        $res = $res->orderBy('u.bonus_id', 'desc');

        if ($amount == 0) {
            $start = ($page - 1) * $size;

            if ($start > 0) {
                $res = $res->skip($start);
            }

            if ($size > 0) {
                $res = $res->take($size);
            }
        }

        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        $bouns_paper = '';
        if ($res) {
            foreach ($res as $key => $row) {
                $arr[$key]['bonus_id'] = $row['bonus_id'];

                /* 先判断是否被使用，然后判断是否开始或过期 */
                if ($type < 2) {
                    $arr[$key]['status'] = lang('common.not_use');
                } elseif ($type == 2) {
                    $arr[$key]['status'] = '<a href="user_order.php?act=order_detail&order_id=' . $row['order_id'] . '" >' . $GLOBALS['_LANG']['had_use'] . '</a>';
                }

                $arr[$key]['shop_name'] = $this->merchantCommonService->getShopName($row['ru_id'], 1); //店铺名称
                $arr[$key]['usebonus_type'] = $row['usebonus_type'];
                $arr[$key]['bonus_sn'] = $row['bonus_sn'];
                $arr[$key]['bouns_amount'] = $row['type_money'];
                $arr[$key]['type_money'] = $this->dscRepository->getPriceFormat($row['type_money']);
                $arr[$key]['min_goods_amount'] = $this->dscRepository->getPriceFormat($row['min_goods_amount']);

                if ($row['valid_period'] > 0) {
                    $add_time = $row['valid_period'] * 60 * 60 * 24;
                    $row['start_time'] = $row['bind_time'];
                    $row['end_time'] = $row['bind_time'] + $add_time;

                    $arr[$key]['use_startdate'] = $this->timeRepository->getLocalDate($this->config['time_format'], $row['start_time']);
                    $arr[$key]['use_enddate'] = $this->timeRepository->getLocalDate($this->config['time_format'], $row['end_time']);
                } else {
                    $arr[$key]['use_startdate'] = $this->timeRepository->getLocalDate($this->config['time_format'], $row['use_start_date']);
                    $arr[$key]['use_enddate'] = $this->timeRepository->getLocalDate($this->config['time_format'], $row['use_end_date']);
                }

                $arr[$key]['bind_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $row['bind_time']);
                $arr[$key]['type_name'] = $row['type_name'];
                $arr[$key]['min_goods_amount_old'] = $row['min_goods_amount'];
            }

            if ($amount == 0) {
                $pagerParams = [
                    'total' => $record_count,
                    'listRows' => $size,
                    'id' => $user_id,
                    'page' => $page,
                    'funName' => $pageFunc,
                    'pageType' => 1
                ];
                $bouns = new Pager($pagerParams);
                $bouns_paper = $bouns->fpage([0, 4, 5, 6, 9]);
            }
        }

        if ($type == 0) {
            $arrName = "available_list";
        } elseif ($type == 1) {
            $arrName = "expire_list";
        } elseif ($type == 2) {
            $arrName = "useup_list";
        } elseif ($type == 3) {
            $arrName = "Invalid_list";
        }


        $bouns = [$arrName => $arr, 'record_count' => $record_count, 'paper' => $bouns_paper];

        return $bouns;
    }

    //合算可用礼品卡总金额
    public function getBounsAmountList($bouns_list)
    {
        $bouns_amount = 0;
        foreach ($bouns_list['available_list'] as $key => $row) {
            $bouns_amount += $row['bouns_amount'];
        }

        return $this->dscRepository->getPriceFormat($bouns_amount);
    }

    /**
     * 取得用户当前可用红包
     *
     * @param int $user_id 用户id
     * @param int $goods_amount 订单商品金额
     * @param int $cart_value
     * @param array $seller_amount
     * @param int $cart_ru_id
     * @return array
     */
    public function getUserBonusInfo($user_id = 0, $goods_amount = 0, $cart_value = 0, $seller_amount = [], $cart_ru_id = -1)
    {
        $where = [
            'self_amount' => 0
        ];
        if (!empty($seller_amount)) {
            $where['self_amount'] = empty($seller_amount[0]) ? 0 : $seller_amount[0];//自营订单金额
        }


        $day = $this->timeRepository->getLocalGetDate();
        $today = $this->timeRepository->getLocalMktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);

        $bonus = [];

        if (count($seller_amount) > 0) {
            $arr = [];
            foreach ($seller_amount as $key => $row) {
                if ($key > 0) {
                    $arr[$key] = $this->getOrderUserFlowBonus($today, $row, $user_id, $where, $key);//查找出商家可用红包
                }
            }

            $arr[] = $this->getOrderUserFlowBonus($today, $goods_amount, $user_id, $where, 0);

            foreach ($arr as $key => $row) {
                if ($row) {
                    foreach ($row as $k => $r) {
                        $bonus[] = $r;
                    }
                }
            }
        } else {
            $bonus = $this->getOrderUserFlowBonus($today, $goods_amount, $user_id, $where);
        }

        return $bonus;
    }

    /**
     * 会员下单显示红包
     *
     * @param int $today
     * @param int $goods_amount
     * @param int $user_id
     * @param array $sqlWhere
     * @param int $ru_id
     * @return mixed
     */
    public function getOrderUserFlowBonus($today = 0, $goods_amount = 0, $user_id = 0, $sqlWhere = [], $ru_id = -1)
    {
        $time = $this->timeRepository->getGmTime();
        $where = [
            'today' => $today,
            'goods_amount' => $goods_amount,
            'ru_id' => $ru_id,
            'self_amount' => $sqlWhere['self_amount'],//平台订单金额
            'time' => $time
        ];

        $res = UserBonus::select('bonus_id', 'bonus_type_id', 'start_time', 'end_time')
            ->where('user_id', $user_id)
            ->where('order_id', 0)
            ->whereRaw("IF(date_type > 0, start_time <= '" . $where['today'] . "' AND end_time >= '" . $where['today'] . "', 1)");

        $res = $res->whereHas('getBonusType', function ($query) use ($where) {
            if ($where['ru_id'] > 0) {
                $query = $query->where('user_id', $where['ru_id'])->where('min_goods_amount', '<=', $where['goods_amount']);
            } else {
                $query = $query->where('user_id', 0);
                $query = $query->whereRaw("IF(usebonus_type > 0, usebonus_type = 1 AND " . 'min_goods_amount <= ' . $where['goods_amount'] . ", usebonus_type = 0 AND " . 'min_goods_amount <= ' . $where['self_amount'] . " )");
            }

            $query = $query->where('review_status', 3)->where('use_end_date', '>=', $where['time']);

            $query->whereRaw("IF(date_type > 0, use_start_date < '" . $where['today'] . "' AND use_end_date > '" . $where['today'] . "', 1)");
        });

        $res = $res->with([
            'getBonusType' => function ($query) {
                $query->select(
                    'type_id',
                    'type_name',
                    'type_money',
                    'use_start_date',
                    'use_end_date',
                    'min_goods_amount'
                );
            }
        ]);

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $val) {
                $val = $this->baseRepository->getArrayMerge($val, $val['get_bonus_type']);

                $res[$key] = $val;
            }
        }

        return $res;
    }
}
