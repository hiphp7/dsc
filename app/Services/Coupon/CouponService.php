<?php

namespace App\Services\Coupon;

use App\Models\CollectStore;
use App\Models\Coupons;
use App\Models\CouponsUser;
use App\Models\Goods;
use App\Models\Users;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Merchant\MerchantCommonService;
use App\Services\User\UserCommonService;

/**
 * 优惠券
 *
 * Class CouponService
 * @package App\Services\Coupon
 */
class CouponService
{
    protected $timeRepository;
    protected $dscRepository;
    protected $merchantCommonService;
    protected $userCommonService;
    private $lang;

    public function __construct(
        TimeRepository $timeRepository,
        UserCommonService $userCommonService,
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService
    )
    {
        $this->timeRepository = $timeRepository;
        $this->userCommonService = $userCommonService;
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;

        /* 加载语言 */
        $common = lang('common');
        $coupons = lang('coupons');

        $this->lang = array_merge($common, $coupons);
    }

    /**
     * 优惠券列表 全场券、会员券、免邮券
     *
     * @param int $user_id
     * @param int $status
     * @param int $page
     * @param int $size
     * @param int $cou_id
     * @return array
     * @throws \Exception
     */
    public function listCoupon($user_id = 0, $status = 0, $page = 1, $size = 10, $cou_id = 0)
    {
        $time = $this->timeRepository->getGmTime();
        $begin = ($page - 1) * $size;

        //取出所有优惠券(注册送、购物送除外)
        $res = Coupons::from('coupons as c')
            ->select('c.*', 'cu.user_id', 'cu.is_use')
            ->leftjoin('coupons_user as cu', 'c.cou_id', '=', 'cu.cou_id')
            ->where('c.review_status', 3)
            ->whereNotIn('c.cou_type', [1, 2])
            ->where('c.cou_end_time', '>', $time);

        if ($status == 0) {
            $res->where('c.cou_type', 3); // 全场券
        } elseif ($status == 1) {
            $res->where('c.cou_type', 4);// 会员券
        } elseif ($status == 2) {
            $res->where('c.cou_type', 5);// 免邮券
        }

        if ($cou_id > 0) {
            $res->where('c.cou_id', $cou_id);
        }

        $res = $res->groupBy('c.cou_id')
            ->orderBy('c.cou_id', 'desc')
            ->offset($begin)
            ->limit($size)
            ->get();
        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $k => $v) {
                $res[$k]['begintime'] = $this->timeRepository->getLocalDate("Y-m-d", $v['cou_start_time']);
                $res[$k]['endtime'] = $this->timeRepository->getLocalDate("Y-m-d", $v['cou_end_time']);
                $res[$k]['img'] = asset('images/coupons_default.png');

                //可使用的店铺;
                $res[$k]['store_name'] = sprintf($this->lang['use_limit'], $this->merchantCommonService->getShopName($v['ru_id'], 1));

                $res[$k]['cou_type_name'] = $v['cou_type'] == VOUCHER_ALL ? $this->lang['vouchers_all'] : ($v['cou_type'] == VOUCHER_USER ? $this->lang['vouchers_user'] : ($v['cou_type'] == VOUCHER_SHIPPING ? $this->lang['vouchers_shipping'] : $this->lang['unknown']));

                // 是否使用
                if ($user_id > 0) {
                    $is_use = CouponsUser::where(['cou_id' => $v['cou_id'], 'user_id' => $user_id])->value('is_use');
                    $res[$k]['is_use'] = empty($is_use) ? 0 : $is_use; //好券集市(用户登入了的话,重新获取用户优惠券的使用情况)
                }

                // 是否过期
                $res[$k]['is_overdue'] = $v['cou_end_time'] < $this->timeRepository->getGmTime() ? 1 : 0;

                //是否已经领取过了
                if ($user_id > 0) {
                    $user_num = CouponsUser::where('cou_id', $v['cou_id'])
                        ->where('user_id', $user_id)
                        ->count();
                    if ($user_num > 0 && $v['cou_user_num'] <= $user_num) {
                        $res[$k]['cou_is_receive'] = 1;
                    } else {
                        $res[$k]['cou_is_receive'] = 0;
                    }
                }

                // 能否领取 优惠劵总张数 1 不能 0 可以领取
                $cou_num = CouponsUser::where(['cou_id' => $v['cou_id']])->count();
                $res[$k]['enable_ling'] = (!empty($cou_num) && $cou_num >= $v['cou_total']) ? 1 : 0;
            }

            return $res;
        }
    }


    /**
     * 任务集市- 购物券
     *
     * @param int $user_id
     * @param int $page
     * @param int $size
     * @return array
     * @throws \Exception
     */
    public function get_coupons_goods_list($user_id = 0, $page = 1, $size = 10)
    {
        $time = $this->timeRepository->getGmTime();

        $start = ($page - 1) * $size;

        $model = Coupons::where('review_status', 3)
            ->where('cou_type', VOUCHER_SHOPING)
            ->where('cou_start_time', '<', $time)
            ->where('cou_end_time', '>', $time);

        $cou_goods = $count = $model;

        $cou_goods = $cou_goods->groupBy('cou_id')
            ->orderBy('cou_id', 'DESC')
            ->offset($start)
            ->limit($size)
            ->get();

        $cou_goods = $cou_goods ? $cou_goods->toArray() : [];

        foreach ($cou_goods as $k => $v) {
            $cou_goods[$k]['begintime'] = $this->timeRepository->getLocalDate("Y-m-d", $v['cou_start_time']);
            $cou_goods[$k]['endtime'] = $this->timeRepository->getLocalDate("Y-m-d", $v['cou_end_time']);

            //可使用的店铺;
            $cou_goods[$k]['store_name'] = sprintf($this->lang['use_limit'], $this->merchantCommonService->getShopName($v['ru_id'], 1));

            $cou_goods[$k]['cou_type_name'] = $v['cou_type'] == VOUCHER_SHOPING ? $this->lang['vouchers_shoping'] : '';

            //商品图片(没有指定商品时为默认图片)
            if ($v['cou_ok_goods']) {
                $cou_ok_goods = explode(',', $v['cou_ok_goods']);

                $goods = Goods::select('goods_id', 'goods_name', 'goods_thumb')->whereIn('goods_id', $cou_ok_goods)
                    ->get();
                $goods = $goods ? $goods->toArray() : [];

                if ($goods) {
                    foreach ($goods as $key => $value) {
                        $goods[$key]['goods_thumb'] = $this->dscRepository->getImagePath($value['goods_thumb']);
                    }
                }

                $cou_goods[$k]['cou_ok_goods_list'] = $goods;
            } else {
                $cou_goods[$k]['cou_ok_goods_list'][0]['goods_thumb'] = asset('images/coupons_default.png');
            }

            // 是否过期
            $cou_goods[$k]['is_overdue'] = $v['cou_end_time'] < $time ? 1 : 0;

            //是否已经领取过了
            if ($user_id) {
                $user_num = CouponsUser::where(['cou_id' => $v['cou_id'], 'user_id' => $user_id])->count();
                if ($user_num > 0 && $v['cou_user_num'] <= $user_num) {
                    $cou_goods[$k]['cou_is_receive'] = 1;
                } else {
                    $cou_goods[$k]['cou_is_receive'] = 0;
                }
            }

            // 能否领取 优惠劵总张数 1 不能 0 可以领取
            $cou_num = CouponsUser::where(['cou_id' => $v['cou_id']])->count();
            $cou_goods[$k]['enable_ling'] = (!empty($cou_num) && $cou_num >= $v['cou_total']) ? 1 : 0;
        }

        return $cou_goods;
    }

    /**
     * 领取优惠券
     *
     * @param int $user_id
     * @param int $cou_id
     * @return array
     * @throws \Exception
     */
    public function receiveCoupon($user_id = 0, $cou_id = 0)
    {
        if ($user_id > 0) {
            //会员等级
            $user_rank = Users::where('user_id', $user_id)->value('user_rank');

            $rest = Coupons::select('cou_type', 'cou_ok_user')->where('cou_id', $cou_id)->first();
            $rest = $rest ? $rest->toArray() : [];

            $type = $rest['cou_type'];      //优惠券类型
            $cou_rank = $rest['cou_ok_user'];  //可以使用优惠券的rank
            $ranks = explode(",", $cou_rank);

            if ($type == 2 || $type == 4 && $ranks != 0) {
                if (in_array($user_rank, $ranks)) {
                    $result = $this->getCoups($cou_id, $user_id);
                } else {
                    $result = [
                        'error' => 0,
                        'msg' => lang('coupons.notuser_notget'), //没有优惠券不能领取
                    ];
                }
            } else {
                $result = $this->getCoups($cou_id, $user_id);
            }
        } else {
            $result = [
                'error' => 0,
                'msg' => $this->lang['not_login'],
            ];
        }
        return $result;
    }

    /**
     * 获取优惠券
     *
     * @param int $cou_id
     * @param int $user_id
     * @return array
     */
    public function getCoups($cou_id = 0, $user_id = 0)
    {
        $time = $this->timeRepository->getGmTime();
        $result = [];
        $res = Coupons::from('coupons as c')
            ->select('c.*', 'cu.user_id', 'cu.is_use')
            ->leftjoin('coupons_user as cu', 'c.cou_id', '=', 'cu.cou_id')
            ->where('c.review_status', 3)
            ->where('c.cou_id', $cou_id)
            ->where('c.cou_end_time', '>', $time)
            ->groupBy('c.cou_id')
            ->first();
        $res = $res ? $res->toArray() : [];

        if (!empty($res)) {
            $num = CouponsUser:: where('user_id', $user_id)->where('cou_id', $cou_id)->count('cou_id');
            $res = Coupons::select('cou_user_num', 'cou_money', 'cou_type', 'ru_id', 'cou_total')->where('cou_id', $cou_id)->first();
            $res = $res ? $res->toArray() : [];

            //判断是否已经领取了,并且还没有使用(根据创建优惠券时设定的每人可以领取的总张数为准,防止超额领取)
            if ($res && $res['cou_user_num'] > $num) {

                //判断优惠券是否已经被领完了
                $cou_surplus = $res['cou_total'] - $num;
                if ($cou_surplus <= 0) {
                    return [
                        'error' => 0,
                        'msg' => $this->lang['lang_coupons_receive_failure'],
                    ];
                }

                if ($res['cou_type'] == VOUCHER_SHOP_CONLLENT) {
                    $is_collect = CollectStore::where('user_id', $user_id)->where('ru_id', $res['ru_id'])->count();
                    //添加关注
                    if ($is_collect < 1) {
                        $other = [
                            'user_id' => $user_id,
                            'ru_id' => $res['ru_id'],
                            'add_time' => $this->timeRepository->getGmTime(),
                            'is_attention' => 1
                        ];
                        CollectStore::insert($other);
                    }
                }
                //领取优惠券
                $data = [
                    'user_id' => $user_id,
                    'cou_id' => $cou_id,
                    'cou_money' => $res['cou_money'],
                    'uc_sn' => $time
                ];
                $insertGetId = CouponsUser::insertGetId($data);

                if ($insertGetId > 0) {
                    $result = [
                        'error' => 1,
                        'msg' => $this->lang['receive_success'],
                    ]; //领取成功！感谢您的参与，祝您购物愉快
                }
            } else {
                $result = [
                    'error' => 0,
                    'msg' => sprintf($this->lang['Coupon_redemption_limit'], $num),
                ];
            }
        } else {
            $result = [
                'error' => 0,
                'msg' => $this->lang['Coupon_redemption_failure'],
            ];
        }

        return $result;
    }

    /**
     * 会员中心优惠券列表
     *
     * @param int $user_id
     * @param int $page
     * @param int $size
     * @param int $type
     * @return array
     * @throws \Exception
     */
    public function userCoupons($user_id = 0, $page = 1, $size = 10, $type = 0)
    {
        $begin = ($page - 1) * $size;
        $time = $this->timeRepository->getGmTime();

        $res = CouponsUser::from('coupons_user as cu')
            ->select('c.*', 'cu.*', 'c.cou_money as cou_money', 'o.order_sn', 'o.add_time', 'cu.cou_money as uc_money', 'o.coupons as order_coupons')
            ->leftjoin('coupons as c', 'c.cou_id', '=', 'cu.cou_id')
            ->leftjoin('order_info as o', 'cu.order_id', '=', 'o.order_id')
            ->where('c.review_status', 3)
            ->where('cu.user_id', $user_id);

        if ($type == 0) {
            //领取的优惠券未使用
            $res->where('cu.is_use', 0)->where('c.cou_end_time', '>', $time);
        } elseif ($type == 1) {
            //已使用的
            $res->where('cu.is_use', 1);
        } elseif ($type == 2) {
            //过期
            $res->where('c.cou_end_time', '<', $time)->where('cu.is_use', 0);
        }

        $res = $res->offset($begin)
            ->limit($size)
            ->get();

        $res = $res ? $res->toArray() : [];

        foreach ($res as $k => $v) {
            $res[$k]['begintime'] = $this->timeRepository->getLocalDate("Y-m-d", $v['cou_start_time']);
            $res[$k]['endtime'] = $this->timeRepository->getLocalDate("Y-m-d", $v['cou_end_time']);
            $res[$k]['img'] = asset('images/coupons_default.png');

            $res[$k]['add_time'] = $this->timeRepository->getLocalDate('Y-m-d', $v['add_time']); //订单生成时间即算优惠券使用时间

            //如果指定了使用的优惠券的商品,取出允许使用优惠券的商品
            if (!empty($v['cou_goods'])) {
                $goods_ids = explode(',', $v['cou_goods']);
                $goods = Goods::select('goods_name')->whereIn('goods_id', $goods_ids)->get();
                $res[$k]['goods_list'] = $goods ? $goods->toArray() : [];
            }
            //获取店铺名称区分平台和店铺(平台发的全平台用,商家发的商家店铺用)
            $res[$k]['store_name'] = sprintf($this->lang['use_limit'], $this->merchantCommonService->getShopName($v['ru_id'], 1));

            //格式化类型名称
            $res[$k]['cou_type_name'] = $v['cou_type'] == VOUCHER_LOGIN ? $this->lang['vouchers_login'] : ($v['cou_type'] == VOUCHER_SHOPING ? $this->lang['vouchers_shoping'] : ($v['cou_type'] == VOUCHER_ALL ? $this->lang['vouchers_all'] : ($v['cou_type'] == VOUCHER_USER ? $this->lang['vouchers_user'] : ($v['cou_type'] == VOUCHER_SHIPPING ? $this->lang['vouchers_shipping'] : ($v['cou_type'] == VOUCHER_SHOP_CONLLENT ? $this->lang['vouchers_shop_conllent'] : $this->lang['unknown'])))));

            // 是否过期
            $res[$k]['is_overdue'] = $v['cou_end_time'] < $this->timeRepository->getGmTime() ? 1 : 0;
        }

        return $res;
    }

    /**
     * 商品详情优惠券列表
     *
     * @param int $user_id
     * @param int $goods_id
     * @param int $ru_id
     * @param int $size
     * @return array
     * @throws \Exception
     */
    public function goodsCoupons($user_id = 0, $goods_id = 0, $ru_id = 0, $size = 10)
    {
        //店铺优惠券 by wanglu
        $time = $this->timeRepository->getGmTime();

        $row = Coupons::select('*')
            ->where('review_status', 3)
            ->where('ru_id', $ru_id)
            ->where('cou_end_time', '>', $time)
            ->where(function ($query) {
                $query->orWhere('cou_type', VOUCHER_ALL)
                    ->orWhere('cou_type', VOUCHER_USER);
            })
            ->whereRaw("((instr(cou_goods, $goods_id)  or (cou_goods = 0)))");

        //获取会员等级id
        $user_rank = $this->userCommonService->getUserRankByUid($user_id);

        $user_rank = $user_rank['rank_id'] ?? 0;
        if ($user_rank > 0) {
            //获取符合会员等级的优惠券
            $row = $row->whereraw("CONCAT(',', cou_ok_user, ',') LIKE '%" . $user_rank . "%'");
        }

        $row = $row->with([
            'getCouponsUserList' => function ($query) use ($user_id) {
                $query->select('cou_id', 'uc_id')->where('user_id', $user_id);
            }
        ]);

        $res = $row->orderBy('cou_id', 'DESC')
            ->limit($size)
            ->get();

        $res = $res ? $res->toArray() : [];
        if ($res) {
            foreach ($res as $key => $value) {
                $res[$key]['cou_end_time'] = $this->timeRepository->getLocalDate('Y.m.d', $value['cou_end_time']);
                $res[$key]['cou_start_time'] = $this->timeRepository->getLocalDate('Y.m.d', $value['cou_start_time']);
                // 能否领取 优惠劵总张数 1 不能 0 可以领取
                $cou_num = CouponsUser::where('cou_id', $value['cou_id'])->count();
                $res[$key]['enable_ling'] = (!empty($cou_num) && $cou_num >= $value['cou_total']) ? 1 : 0;
                // 是否领取
                if ($user_id > 0) {
                    $user_num = !empty($value['get_coupons_user_list']) ? count($value['get_coupons_user_list']) : 0;
                    if ($user_num > 0 && $value['cou_user_num'] <= $user_num) {
                        $res[$key]['cou_is_receive'] = 1;
                        unset($res[$key]);
                    } else {
                        $res[$key]['cou_is_receive'] = 0;
                    }
                }
            }
            $res = collect($res)->values()->all();
        }

        return ['res' => $res, 'total' => count($res)];
    }

    /**
     * 获取优惠券详情
     * @param $cou_id
     * @return array
     */
    public function getDetail($cou_id)
    {
        $res = Coupons::where('review_status', 3)
            ->where('cou_id', $cou_id)
            ->first();
        $result = $res ? $res->toArray() : [];

        return $result;
    }

    /**
     * 购物车领取优惠券列表
     *
     * @param $user_id
     * @param int $ru_id
     * @param int $size
     * @return array
     * @throws \Exception
     */
    public function getCouponsList($user_id, $ru_id = 0, $size = 10)
    {
        //店铺优惠券 by wanglu
        $time = $this->timeRepository->getGmTime();

        $user_rank = $this->userCommonService->getUserRankByUid($user_id);
        $user_rank = $user_rank['rank_id'] ?? 0;

        $res = Coupons::select('*')
            ->where('review_status', 3)
            ->where('ru_id', $ru_id)
            ->where('cou_end_time', '>', $time)
            ->where(function ($query) {
                $query->orWhere('cou_type', VOUCHER_ALL)
                    ->orWhere('cou_type', VOUCHER_USER);
            })
            ->whereRaw("((instr(cou_ok_user, $user_rank)  or (cou_goods = 0)))");

        $res = $res->orderBy('cou_id', 'DESC')
            ->limit($size)
            ->get();

        $res = $res ? $res->toArray() : [];

        foreach ($res as $key => $value) {
            $res[$key]['cou_end_time'] = $this->timeRepository->getLocalDate('Y.m.d', $value['cou_end_time']);
            $res[$key]['cou_start_time'] = $this->timeRepository->getLocalDate('Y.m.d', $value['cou_start_time']);
            // 是否领取
            if ($user_id > 0) {
                $user_num = CouponsUser::where('cou_id', $value['cou_id'])
                    ->where('user_id', $user_id)
                    ->count();
                if ($user_num > 0 && $value['cou_user_num'] <= $user_num) {
                    $res[$key]['cou_is_receive'] = 1;
                } else {
                    $res[$key]['cou_is_receive'] = 0;
                }
            }
            // 能否领取 优惠劵总张数 1 不能 0 可以领取
            $cou_num = CouponsUser::where('cou_id', $value['cou_id'])->count();
            $res[$key]['enable_ling'] = (!empty($cou_num) && $cou_num >= $value['cou_total']) ? 1 : 0;
        }

        return $res;
    }
}
