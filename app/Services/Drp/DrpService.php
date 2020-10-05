<?php

namespace App\Services\Drp;

use App\Custom\Distribute\Repositories\AccountLogRepository;
use App\Custom\Distribute\Services\DistributeService;
use App\Libraries\Http;
use App\Models\Article;
use App\Models\Category;
use App\Models\DrpLog;
use App\Models\DrpShop;
use App\Models\DrpType;
use App\Models\DrpUserCredit;
use App\Models\Goods;
use App\Models\OrderGoods;
use App\Models\OrderInfo;
use App\Models\OrderInfoMembershipCard;
use App\Models\PayLog;
use App\Models\TouchAdPosition;
use App\Models\Users;
use App\Models\Wechat;
use App\Plugins\UserRights\Drp\Services\DrpRightsService;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Ads\AdsService;
use App\Services\Order\OrderGoodsService;
use App\Services\UserRights\RightsCardService;
use App\Services\Wechat\WechatService;
use Endroid\QrCode\QrCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use think\Image;

/**
 * 分销
 * Class DrpService
 * @package App\Services\Drp
 */
class DrpService
{
    protected $timeRepository;
    protected $config;
    protected $baseRepository;
    protected $dscRepository;
    protected $accountLogRepository;
    protected $commonRepository;

    public function __construct(
        TimeRepository $timeRepository,
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        AccountLogRepository $accountLogRepository,
        CommonRepository $commonRepository
    )
    {
        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->accountLogRepository = $accountLogRepository;
        $this->commonRepository = $commonRepository;

        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 获取分销店铺配置
     * @param null $code 配置名称
     * @param bool $force 强制获取
     * @return array|\Illuminate\Cache\CacheManager|mixed|string
     */
    public function drpConfig($code = null, $force = false)
    {
        return app(DrpConfigService::class)->drpConfig($code, $force);
    }

    /**
     * 分销商等级分成比例
     * @return array|\Illuminate\Cache\CacheManager|mixed|string
     */
    public function drpAffiliate()
    {
        $res = $this->drpConfig('drp_affiliate');

        if ($res) {
            $res = unserialize($res['value']);
        }

        return $res;
    }

    /**
     * 获取用户订单总金额
     * @param int $user_id 会员id
     * @return bool
     */
    public function orderMoney($user_id = 0)
    {
        if (empty($user_id)) {
            return 0;
        }

        $res = OrderInfo::where('user_id', $user_id)
            ->where('pay_status', PS_PAYED)
            ->sum('goods_amount');
        return $res;
    }

    /**
     * 判断是否已经开店
     * @param int $user_id 会员id
     * @return array
     */
    public function isRegisterDrp($user_id = 0)
    {
        if (empty($user_id)) {
            return [];
        }

        $res = DrpShop::select('id')
            ->where('user_id', $user_id);
        $res = $this->baseRepository->getToArrayFirst($res);

        return $res;
    }

    /**
     * 判断店铺名
     * @param string $shopname 店铺名称
     * @return array|mixed
     */
    public function judgmentShop($shopname = '')
    {
        if (empty($shopname)) {
            return [];
        }

        $res = DrpShop::select('id')
            ->where('shop_name', $shopname);
        $res = $this->baseRepository->getToArrayFirst($res);

        return $res;
    }

    /**
     * 判断号码
     * @param string $mobile 手机号
     * @return array|mixed
     */
    public function judgmentMobile($mobile = '')
    {
        if (empty($mobile)) {
            return [];
        }

        $res = DrpShop::select('id')
            ->where('mobile', $mobile);
        $res = $this->baseRepository->getToArrayFirst($res);

        return $res;
    }

    /**
     * 添加 更新 --店铺
     *
     * @param array $shop_info
     * @return int
     */
    public function updateDrpShop($shop_info = [])
    {
        $shop_id = isset($shop_info['id']) ? intval($shop_info['id']) : 0;   //店铺id
        if ($shop_id > 0) {
            //更新
            $shop = DrpShop::where('id', $shop_id)->where('user_id', $shop_info['user_id'])->update($shop_info);
            if ($shop == 0) {
                $shop = 2;//无更新信息
            }

            if ($shop > 0 && $shop != 2) {
                // 更新分销商 重新绑定权益卡并且修改会员等级
                $membership_card_id = $shop_info['membership_card_id'] ?? 0;
                app(RightsCardService::class)->editUsersRank($shop_info['user_id'], $membership_card_id);
            }

            return $shop;
        } else {
            unset($shop_info['id']);
            $add = DrpShop::insertGetId($shop_info);
            if ($add) {
                // 成为分销商 绑定权益卡并且修改会员等级
                $membership_card_id = $shop_info['membership_card_id'] ?? 0;
                app(RightsCardService::class)->editUsersRank($shop_info['user_id'], $membership_card_id);

                return $add;
            }
        }
    }

    /**
     *  根据会员id 店铺id 获取店铺信息
     * @param int $user_id 会员id
     * @param int $shop_id 店铺id
     * @return array
     */
    public function shopInfo($user_id = 0, $shop_id = 0)
    {
        $res = DrpShop::query();
        //关联会员
        $res = $res->with([
            'getUsers' => function ($query) {
                $query->select('user_id', 'user_name', 'nick_name', 'user_picture');
            }
        ]);
        if ($user_id > 0) {
            $info = $res->where('user_id', $user_id);
        } else {
            $info = $res->where('id', $shop_id);
        }

        $info = $this->baseRepository->getToArrayFirst($info);

        if (empty($info)) {
            return [];
        }

        if (isset($info['get_users']) && $info['get_users']) {
            $info = array_merge($info, $info['get_users']);
        }
        $info['shop_img'] = $this->dscRepository->getImagePath($info['shop_img']);
        if ($info['shop_portrait']) {
            $info['headimgurl'] = $this->dscRepository->getImagePath($info['shop_portrait']);
        } else {
            //用户名、头像
            $info['headimgurl'] = $this->dscRepository->getImagePath($info['user_picture']);
        }
        $info['nickname'] = !empty($info['nick_name']) ? $info['nick_name'] : $info['user_name'];
        $info['create_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $info['create_time']);

        return $info;
    }

    /**
     * 代言商品列表
     * @param int $user_id 店铺会员id
     * @param int $page
     * @param int $size
     * @param int $status 商品  1全部  2上新  3促销 4热销
     * @param int $model 店铺商品模式  0全部  1分类  2商品
     * @param int $uid 当前会员id
     * @return array
     */
    public function showGoods($user_id, $page = 1, $size = 10, $status = 1, $model = 0, $uid = 0)
    {
        $corrent = ($page - 1) * $size;
        $time = $this->timeRepository->getGmTime();
        // 获取配置->是否显示佣金比例
        $count = DrpShop::where('user_id', $uid)->where('audit', 1)->where('status', 1)->count();
        $ischeck = 0;
        if ($count > 0) {
            $commission = $this->drpConfig('commission');
            $ischeck = $commission['value'];
        }

        $res = [];
        if ($model == 0) {
            // 全部
            $goods = Goods::select('goods_id', 'cat_id', 'goods_name', 'shop_price', 'goods_thumb', 'dis_commission');

            if ($status == 2) {
                $goods = $goods->where('is_new', 1);
            } elseif ($status == 3) {
                $goods = $goods->where('promote_price', '>', 0)->where('promote_start_date', '<=', $time)->where('promote_end_date', '>=', $time);
            } elseif ($status == 4) {
                $goods = $goods->where('is_hot', 1);
            }

            $res = $goods->where('is_real', 1)
                ->where('is_on_sale', 1)
                ->where('is_alone_sale', 1)
                ->where('review_status', '>', 2)
                ->where('is_show', 1)
                ->where('dis_commission', '>', 0)
                ->where('is_distribution', 1)
                ->where('is_delete', 0)
                ->offset($corrent)
                ->limit($size);

            $res = $this->baseRepository->getToArrayGet($res);

            if ($res) {
                foreach ($res as $k => $v) {
                    $res[$k]['commission'] = $ischeck;
                    $res[$k]['goods_thumb'] = $this->dscRepository->getImagePath($v['goods_thumb']);
                    $res[$k]['shop_price'] = $v['shop_price'];
                    $res[$k]['shop_price_formated'] = $this->dscRepository->getPriceFormat($v['shop_price'], true);
                    $res[$k]['url'] = dsc_url('/#/goods/' . $v['goods_id']) . '?' . http_build_query(['parent_id' => $user_id], '', '&');
                    $res[$k]['app_page'] = config('route.goods.detail') . $v['goods_id'];
                }
            }
        } elseif ($model == 1) {
            // 分类
            $list = DrpType::select('goods_id', 'cat_id')
                ->where('user_id', $user_id)
                ->where('type', $model);
            $list = $this->baseRepository->getToArrayGet($list);

            if ($list) {
                $cat_id = [];
                foreach ($list as $value) {
                    $cat_id[] = $value['cat_id'];
                }

                $goods = Goods::select('goods_id', 'cat_id', 'goods_name', 'shop_price', 'goods_thumb', 'dis_commission');
                if ($status == 2) {
                    $goods = $goods->where('is_new', 1);
                } elseif ($status == 3) {
                    $goods = $goods->where('promote_price', '>', 0)->where('promote_start_date', '<=', $time)->where('promote_end_date', '>=', $time);
                } elseif ($status == 4) {
                    $goods = $goods->where('is_hot', 1);
                }
                $res = $goods->where('is_real', 1)
                    ->where('is_on_sale', 1)
                    ->where('is_alone_sale', 1)
                    ->where('review_status', '>', 2)
                    ->where('is_show', 1)
                    ->where('dis_commission', '>', 0)
                    ->where('is_distribution', 1)
                    ->where('is_delete', 0)
                    ->whereIn('cat_id', $cat_id)
                    ->offset($corrent)
                    ->limit($size);

                $res = $this->baseRepository->getToArrayGet($res);

                if ($res) {
                    foreach ($res as $k => $v) {
                        $res[$k]['commission'] = $ischeck;
                        $res[$k]['goods_thumb'] = $this->dscRepository->getImagePath($v['goods_thumb']);
                        $res[$k]['shop_price'] = $v['shop_price'];
                        $res[$k]['shop_price_formated'] = $this->dscRepository->getPriceFormat($v['shop_price'], true);
                        $res[$k]['url'] = dsc_url('/#/goods/' . $v['goods_id']) . '?' . http_build_query(['parent_id' => $user_id], '', '&');
                        $res[$k]['app_page'] = config('route.goods.detail') . $v['goods_id'];
                    }
                }
            }

        } elseif ($model == 2) {
            // 商品
            $list = DrpType::select('goods_id', 'cat_id')
                ->where('user_id', $user_id)
                ->where('type', $model)
                ->offset($corrent)
                ->limit($size);

            $list = $this->baseRepository->getToArrayGet($list);

            if ($list) {
                $goods_id = [];
                foreach ($list as $value) {
                    $goods_id[] = $value['goods_id'];
                }

                $goods = Goods::select('goods_id', 'cat_id', 'goods_name', 'shop_price', 'goods_thumb', 'dis_commission');

                if ($status == 2) {
                    $goods = $goods->where('is_new', 1);
                } elseif ($status == 3) {
                    $goods = $goods->where('promote_price', '>', 0)->where('promote_start_date', '<=', $time)->where('promote_end_date', '>=', $time);
                } elseif ($status == 4) {
                    $goods = $goods->where('is_hot', 1);
                }

                $goods = $goods->where('is_real', 1)
                    ->where('is_on_sale', 1)
                    ->where('is_alone_sale', 1)
                    ->where('review_status', '>', 2)
                    ->where('is_show', 1)
                    ->where('is_delete', 0)
                    ->where('dis_commission', '>', 0)
                    ->where('is_distribution', 1)
                    ->whereIn('goods_id', $goods_id);

                $res = $this->baseRepository->getToArrayGet($goods);

                if ($res) {
                    foreach ($res as $k => $v) {
                        $res[$k]['commission'] = $ischeck;
                        $res[$k]['goods_thumb'] = $this->dscRepository->getImagePath($v['goods_thumb']);
                        $res[$k]['shop_price'] = $v['shop_price'];
                        $res[$k]['shop_price_formated'] = $this->dscRepository->getPriceFormat($v['shop_price'], true);
                        $res[$k]['url'] = dsc_url('/#/goods/' . $v['goods_id']) . '?' . http_build_query(['parent_id' => $user_id], '', '&');
                        $res[$k]['app_page'] = config('route.goods.detail') . $v['goods_id'];
                    }
                }
            }
        }

        return $res;
    }

    /**
     * 佣金统计中心
     * @param int $type 统计类型
     * @param $user_id
     * @return mixed
     */
    public function get_drp_money($type = 0, $user_id = 0)
    {
        if (empty($user_id)) {
            return 0;
        }
        if ($type === 0) {
            // 累计佣金
            $res = DrpLog::query()->where('is_separate', 1)->where('separate_type', '<>', '-1')
                ->where('user_id', $user_id)
                ->sum('money');
        } else {
            if ($type === 1) {
                // 今日收入
                $res = DrpLog::query()->where('is_separate', 1)->where('separate_type', '<>', '-1')
                    ->where('time', '>=', $this->timeRepository->getLocalMktime(0, 0, 0, date('m'), date('d'), date('Y')))
                    ->where('user_id', $user_id)
                    ->sum('money');
            } else {
                // 总销售额
                $res = OrderGoods::from('order_goods as o')
                    ->leftjoin('drp_log as a', 'o.order_id', '=', 'a.order_id')
                    ->where('a.is_separate', 1)
                    ->where('a.separate_type', '<>', '-1')
                    ->where('a.user_id', $user_id)
                    ->sum('goods_price');
            }
        }

        return $res;
    }

    /**
     * 分销商等级
     * @param int $user_id
     * @return mixed
     */
    public function drp_rank_info($user_id = 0)
    {
        //检测分销商是否是特殊等级,直接获取等级信息
//        $credit_id = DrpShop::where('user_id', $user_id)->value('credit_id');
        $drp_shop = DrpShop::with([
            'userMembershipCard' => function ($query) {
                $query->select('id', 'name');
            },
        ])->where(['user_id' => $user_id])->first();
        $credit_id = $drp_shop['credit_id'];

        if ($credit_id > 0) {
            $rank_info = DrpUserCredit::select('id', 'credit_name', 'min_money', 'max_money')
                ->where('id', $credit_id)
                ->first();
            $rank_info = $rank_info->toArray();
        } else {
            //统计分销商所属订单金额
            $totals = OrderGoods::from('order_goods as o')
                ->leftjoin('drp_log as a', 'o.order_id', '=', 'a.order_id')
                ->where('a.is_separate', 1)
                ->where('a.separate_type', '<>', -1)
                ->where('a.user_id', $user_id)
                ->sum('money');
            $goods_price = $totals ? $totals : 0;
            $rank_info = DrpUserCredit::select('id', 'credit_name', 'min_money', 'max_money')
                ->where('min_money', '<=', $goods_price)
                ->where('max_money', '>', $goods_price)
                ->first();
            $rank_info = $rank_info->toArray();
        }
        if (isset($drp_shop['user_membership_card']['name'])) {
            $rank_info['credit_name'] = $drp_shop['user_membership_card']['name'];
        }

        return $rank_info;
    }

    /**
     * 分销订单
     * @param int $user_id 会员id
     * @param int $status 2 全部 1分成 0未分成
     * @param array $offset
     * @return mixed
     */
    public function drpLogOrder($user_id = 0, $status = 2, $offset = [])
    {
        $model = DrpLog::query()->where('user_id', $user_id)->whereIn('is_separate', [0, 1]);

        $model = $model->whereHas('getOrder', function ($query) use ($status) {
            $query = $query->where('main_count', 0)->where('pay_status', PS_PAYED);
            if ($status != 2) {
                $query->where('drp_is_separate', $status);
            }
        })->orWhereHas('getDrpAccountLog', function ($query) use ($status) {
            $query = $query->where('is_paid', 1);
            if ($status != 2) {
                $query->where('drp_is_separate', $status);
            }
        });

        $model = $model->with([
            'getOrder' => function ($query) {
                $query = $query->select('order_id', 'order_sn', 'user_id', 'add_time', 'drp_is_separate', 'money_paid', 'surplus');
                $query = $query->with([
                    'getUsers' => function ($query) {
                        $query->select('user_id', 'user_name', 'nick_name');
                    }
                ]);
                $query->with([
                    'goods' => function ($query) {
                        $query = $query->select('order_id', 'goods_id', 'goods_price', 'goods_number', 'goods_name', 'goods_sn', 'ru_id', 'drp_money');
                        $query->with([
                            'getGoods' => function ($query) {
                                $query->select('goods_id', 'goods_thumb');
                            }
                        ]);
                    }
                ]);
            },
            'getDrpAccountLog' => function ($query) {
                $query = $query->select('id', 'user_id', 'add_time', 'drp_is_separate', 'amount', 'membership_card_id');
                $query = $query->with([
                    'getUsers' => function ($query) {
                        $query->select('user_id', 'user_name', 'nick_name');
                    }
                ]);
                $query->with([
                    'userMembershipCard' => function ($query) {
                        $query->select('id', 'name', 'background_img', 'background_color');
                    }
                ]);
            }
        ]);


        if (!empty($offset)) {
            $model = $model->offset($offset['start'])->limit($offset['limit']);
        }

        $model = $model->orderBy('order_id', 'DESC');

        $res = $this->baseRepository->getToArrayGet($model);

        return $res;
    }

    /**
     * 订单详情
     * @param int $user_id
     * @param int $order_id
     * @return array
     */
    public function orderDetail($user_id = 0, $order_id = 0)
    {
        if (empty($user_id) || empty($order_id)) {
            return [];
        }

        $model = DrpLog::query()->where('user_id', $user_id)->whereIn('log_type', [0, 2]);

        $model = $model->whereHas('getOrder', function ($query) use ($order_id) {
            $query->where('pay_status', PS_PAYED)->where('order_id', $order_id);
        });

        $model = $model->with([
            'getOrder' => function ($query) {
                $query = $query->select('order_id', 'order_sn', 'user_id', 'add_time', 'drp_is_separate', 'money_paid', 'surplus');
                $query = $query->with([
                    'getUsers' => function ($query) {
                        $query->select('user_id', 'user_name', 'nick_name');
                    }
                ]);
                $query->with([
                    'goods' => function ($query) {
                        $query = $query->select('order_id', 'goods_id', 'goods_price', 'goods_number', 'goods_name', 'goods_sn', 'ru_id', 'drp_money');
                        $query->with([
                            'getGoods' => function ($query) {
                                $query->select('goods_id', 'goods_thumb');
                            }
                        ]);
                    }
                ]);
            }
        ]);

        $res = $this->baseRepository->getToArrayFirst($model);

        return $res;
    }

    /**
     * 我的团队
     * @param int $user_id
     * @param int $page
     * @param int $size
     * @return array
     */
    public function teamInfo($user_id = 0, $page = 1, $size = 10)
    {
        if (empty($user_id)) {
            return [];
        }

        $corrent = ($page - 1) * $size;

        $res = Users::where('drp_parent_id', $user_id);

        $res = $res->whereHas('getDrpShop', function ($query) {
            $query->where('audit', 1); // ->where('status', 1)
        });

        $res = $res->with([
            'getDrpShop'
        ]);

        $res = $res->offset($corrent)
            ->limit($size)
            ->orderBy('reg_time', 'desc');

        $res = $this->baseRepository->getToArrayGet($res);

        return $res;
    }

    /**
     * 贡献佣金
     * @param int $user_id 当前会员id
     * @param int $child_user_id 团队下会员id
     * @param int $status 1 今日
     * @return mixed
     */
    public function moneyAffiliate($user_id = 0, $child_user_id = 0, $status = 0)
    {
        if (empty($user_id)) {
            return 0;
        }

        $model = DrpLog::query()->where('is_separate', 1)->where('user_id', $user_id)->where('separate_type', '<>', '-1');

        $model = $model->whereHas('getOrder', function ($query) use ($child_user_id) {
            $query->where('main_count', 0)->where('pay_status', PS_PAYED)->where('user_id', $child_user_id)->where('drp_is_separate', 1);
        })->orWhereHas('getDrpAccountLog', function ($query) use ($child_user_id) {
            $query->where('is_paid', 1)->where('user_id', $child_user_id)->where('drp_is_separate', 1);
        });

        if ($status == 1) {
            $model = $model->where('time', '>=', $this->timeRepository->getLocalMktime(0, 0, 0, date('m'), date('d'), date('Y')));
        }

        $res = $model->sum('money');

        return $res;
    }

    /**
     * 团队详情
     * @param int $user_id 会员id
     * @return mixed
     */
    public function teamDetail($user_id = 0)
    {
        if (empty($user_id)) {
            return [];
        }

        $res = Users::where('user_id', $user_id);

        $res = $res->whereHas('getDrpShop', function ($query) {
            $query->where('audit', 1); // ->where('status', 1)
        });

        $res = $res->with([
            'getDrpShop'
        ]);

        $res = $this->baseRepository->getToArrayFirst($res);

        return $res;
    }

    /**
     * 团队数量
     * @param int $user_id 会员id
     * @return mixed
     */
    public function drpNextNum($user_id = 0)
    {
        if (empty($user_id)) {
            return 0;
        }

        $model = Users::where('drp_parent_id', $user_id);

        $model = $model->whereHas('getDrpShop', function ($query) {
            $query->where('audit', 1); // ->where('status', 1)
        });

        $res = $model->count();

        return $res;
    }

    /**
     * 下线会员count
     * @param int $user_id 会员id
     * @return mixed
     */
    public function userNextNum($user_id = 0)
    {
        if (empty($user_id)) {
            return 0;
        }

        $res = Users::where('parent_id', $user_id)->count();

        return $res;
    }

    /**
     * 下线会员
     * @param int $user_id 会员id
     * @return mixed
     */
    public function offlineUser($user_id = 0, $page = 1, $size = 10)
    {
        if (empty($user_id)) {
            return [];
        }

        $corrent = ($page - 1) * $size;
        $res = Users::select('user_id', 'user_name', 'nick_name', 'user_picture', 'reg_time')
            ->where('drp_parent_id', $user_id)
            ->offset($corrent)
            ->limit($size)
            ->orderBy('reg_time', 'desc');

        $res = $this->baseRepository->getToArrayGet($res);

        return $res;
    }

    /**
     * 分销排行
     * @param array $offset
     * @return array
     */
    public function drpRankList($offset = [])
    {
        $model = DrpShop::query()->where('audit', 1); // ->where('status', 1)

        $model = $model->withCount([
            'getDrpLogList as money' => function ($query) {
                $query->select(DB::raw("sum(money) as money"))->where('is_separate', 1)->where('separate_type', '<>', -1);
            },
            'getChildUsers as user_child_num' => function ($query) {
                $query->select(DB::raw("count(user_id) as user_child_num"));
            }
        ]);

        $model = $model->with([
            'getUsers' => function ($query) {
                $query->select('user_id', 'user_name', 'nick_name', 'user_picture');
            }
        ]);

        if (!empty($offset)) {
            $model = $model->offset($offset['start'])->limit($offset['limit']);
        }

        $model = $model->orderBy('money', 'DESC')
            ->orderBy('user_child_num', 'DESC')
            ->orderBy('user_id', 'ASC');

        $res = $model->get();

        $res = $res ? $res->toArray() : [];

        $list = [];
        if (!empty($res)) {
            foreach ($res as $key => $val) {
                $val = collect($val)->merge($val['get_users'])->except('get_users')->all();

                $list[$key]['rank'] = $key + 1;
                $list[$key]['user_id'] = $val['user_id'];
                $list[$key]['user_name'] = !empty($val['nick_name']) ? $val['nick_name'] : $val['shop_name'];

                if (isset($val['shop_portrait']) && $val['shop_portrait']) {
                    $list[$key]['user_picture'] = $this->dscRepository->getImagePath($val['shop_portrait']);
                } else {
                    $list[$key]['user_picture'] = $this->dscRepository->getImagePath($val['user_picture']);
                }
                $list[$key]['money'] = $val['money'] ?? 0;
            }
        }

        return $list;
    }

    /**
     * 分销排行名次
     * @param int $user_id
     * @return int
     */
    public function drpRankNum($user_id = 0)
    {
        if (empty($user_id)) {
            return 0;
        }

        $model = DrpShop::query()->where('audit', 1); // ->where('status', 1)

        $model = $model->withCount([
            'getDrpLogList as money' => function ($query) {
                $query->select(DB::raw("sum(money) as money"))->where('is_separate', 1)->where('separate_type', '<>', -1);
            },
            'getChildUsers as user_child_num' => function ($query) {
                $query->select(DB::raw("count(user_id) as user_child_num"));
            }
        ]);

        $model = $model->orderBy('money', 'DESC')
            ->orderBy('user_child_num', 'DESC')
            ->orderBy('user_id', 'ASC');

        $res = $model->get();

        $res = $res ? $res->toArray() : [];

        $rank = 0;
        if (!empty($res)) {
            foreach ($res as $key => $val) {
                if ($val['user_id'] == $user_id) {
                    $rank = $key + 1;
                    break;
                }
            }
        }

        $rank = $rank ? $rank : '--';

        return $rank;
    }

    /**
     * 佣金明细
     * @param int $user_id
     * @param int $status 全部2  为分成0  已分成1
     * @param array $offset
     * @return mixed
     */
    public function drpLog($user_id = 0, $status = 2, $offset = [])
    {
        if (empty($user_id)) {
            return [];
        }

        $model = DrpLog::query()->where('user_id', $user_id);

        $model = $model->whereHas('getOrder', function ($query) use ($status) {
            $query = $query->where('main_count', 0)->where('pay_status', PS_PAYED);
            if ($status != 2) {
                $query->where('drp_is_separate', $status);
            }
        })->orWhereHas('getDrpAccountLog', function ($query) use ($status) {
            $query = $query->where('is_paid', 1);
            if ($status != 2) {
                $query->where('drp_is_separate', $status);
            }
        });

        $model = $model->with([
            'getOrder' => function ($query) {
                $query = $query->select('order_id', 'order_sn', 'user_id', 'add_time', 'drp_is_separate', 'money_paid', 'surplus');
                $query->with([
                    'getUsers' => function ($query) {
                        $query->select('user_id', 'user_name', 'nick_name');
                    }
                ]);
            },
            'getDrpAccountLog' => function ($query) {
                $query = $query->select('id', 'user_id', 'add_time', 'drp_is_separate', 'amount', 'membership_card_id');
                $query->with([
                    'getUsers' => function ($query) {
                        $query->select('user_id', 'user_name', 'nick_name');
                    }
                ]);
            }
        ]);

        if ($status < 2) {
            //已分成 OR 等待处理
            $model = $model->where('is_separate', $status);
        }

        if (!empty($offset)) {
            $model = $model->offset($offset['start'])->limit($offset['limit']);
        }

        $model = $model->orderBy('order_id', 'desc');

        $res = $this->baseRepository->getToArrayGet($model);

        $result = [];
        if ($res) {
            $timeFormat = $this->config['time_format'];

            foreach ($res as $key => $value) {
                // 普通分销订单
                if (isset($value['get_order']) && !empty($value['get_order'])) {
                    $value = collect($value)->merge($value['get_order'])->except('get_order')->all();

                    $nick_name = $value['get_users']['nick_name'] ?? '';
                    $result[$key]['buy_user_name'] = !empty($nick_name) ? $nick_name : $value['get_users']['user_name'] ?? '';
                }
                // 付费购买分销商订单
                if (isset($value['get_drp_account_log']) && !empty($value['get_drp_account_log'])) {
                    $value = collect($value)->merge($value['get_drp_account_log'])->except('get_drp_account_log')->all();

                    $nick_name = $value['get_users']['nick_name'] ?? '';
                    $result[$key]['buy_user_name'] = !empty($nick_name) ? $nick_name : $value['get_users']['user_name'] ?? '';
                }

                if (isset($this->config['show_mobile']) && $this->config['show_mobile'] == 0) {
                    $result[$key]['buy_user_name'] = $this->dscRepository->stringToStar($result[$key]['buy_user_name']);
                }

                $result[$key]['order_sn'] = $value['order_sn'] ?? '';
                $result[$key]['log_type'] = $value['log_type'] ?? 0;
                $result[$key]['log_id'] = $value['log_id'] ?? 0;
                $result[$key]['time_format'] = $this->timeRepository->getLocalDate($timeFormat, $value['time']);
                $result[$key]['money_format'] = $this->dscRepository->getPriceFormat($value['money'], true);

                $result[$key]['drp_is_separate'] = $value['drp_is_separate'] ?? 0;
                $result[$key]['is_separate_format'] = (isset($value['drp_is_separate']) && $value['drp_is_separate'] == '2') ? lang('drp.canceled') : ((isset($value['drp_is_separate']) && $value['is_separate'] == '1') ? lang('drp.isdivideinto') : lang('drp.await_dispose'));
                if ($value['separate_type'] == -1) {
                    $result[$key]['is_separate_format'] = lang('drp.undone');
                }
            }
        }

        return $result;
    }


    /**
     * 分销文章
     */
    public function news()
    {
        $list = [];
        // 分销配置指定分销文章分类
        $article = $this->drpConfig('articlecatid');

        if ($article) {
            $res = Article::where('is_open', 1)
                ->where('cat_id', $article['value'])
                ->orderby('add_time', 'desc');
            $res = $this->baseRepository->getToArrayGet($res);
            if ($res) {
                foreach ($res as $key => $val) {
                    $list[$key]['title'] = $val['title'];
                    // 过滤样式 手机自适应
                    $content = $this->dscRepository->contentStyleReplace($val['content']);
                    // 显示文章详情图片 （本地或OSS）
                    $content = $this->dscRepository->getContentImgReplace($content);
                    $list[$key]['content'] = $content;
                }
            }
        }

        return $list;
    }

    /**'
     * 更新分销用户佣金
     * @param int $user_id
     * @param int $shop_money
     * @return mixed
     */
    public function drpUpdateShopMoney($user_id = 0, $shop_money = 0)
    {
        if (empty($user_id)) {
            return false;
        }

        $update = DrpShop::where('user_id', $user_id)->update(['shop_money' => $shop_money]);

        return $update;
    }

    /**
     * 获取推荐分销商ID
     *
     * @param int $parent_id
     * @return int
     */
    public function getDrpAffiliate($parent_id = 0)
    {
        if ($parent_id > 0) {
            $user_id = DrpShop::where('user_id', $parent_id)->where('audit', 1)->value('user_id');
            return $user_id ?? 0;
        }

        return 0;
    }

    /**
     * 获取当前会员推荐分销商id
     * @param int $user_id
     * @return int
     */
    public function getUserDrpParent($user_id = 0)
    {
        if (empty($user_id)) {
            return 0;
        }

        // 获取当前会员 推荐分销商id
        $user_model = Users::query()->where('user_id', $user_id);
        $user_model = $user_model->whereHas('getParentDrpShop', function ($query) {
            $query->where('audit', 1);
        });

        $drp_parent_id = $user_model->value('drp_parent_id');

        return $drp_parent_id;
    }

    /**
     * 生成分销商名片
     *
     * @param int $user_id
     * @param string $type
     * @return array
     */
    public function createUserCard($user_id = 0, $type = 'url')
    {
        //店铺信息
        $info = $this->shopInfo($user_id, 0);
        $result['info'] = $info ?? [];

        // 路径设置
        $file_path = storage_public('data/attached/qrcode/');
        if (!file_exists($file_path)) {
            make_dir($file_path);
        }
        $avatar_file = storage_public('data/attached/avatar/');
        if (!file_exists($avatar_file)) {
            make_dir($avatar_file);
        }
        // 默认背景图
        $drp_bg = public_path('img/drp_bg.png');

        // 用户头像
        $avatar = $avatar_file . 'avatar_' . $user_id . '.png';
        // 二维码
        $qr_code = $file_path . 'drp_' . $user_id . '_qrcode.png';
        // 输出图片
        $out_img = $file_path . 'drp_' . $user_id . '.png';

        // 生成二维码条件
        $generate = false;
        if (file_exists($out_img)) {
            $lastmtime = filemtime($out_img) + 3600 * 24 * 20; // 20天有效期
            if (time() >= $lastmtime) {
                $generate = true;
            }
        }

        if (!file_exists($out_img) || $generate == true) {
            $url = '';
            if ($type == 'qrcode' && file_exists(MOBILE_WECHAT)) {

                $wechat = Wechat::where('default_wx', 1)->where('ru_id', 0)->first();
                $wechat = $wechat ? $wechat->toArray() : [];

                if (!empty($wechat)) {
                    // 获取配置信息
                    $config = [
                        'token' => $wechat['token'],
                        'appid' => $wechat['appid'],
                        'appsecret' => $wechat['appsecret']
                    ];
                    $weObj = new \App\Libraries\Wechat($config);

                    $scene_id = 'd=' . $user_id;
                    $weixinInfo = $weObj->getQRCode($scene_id, 2, 2592000); // 2:字符串型永久二维码

                    // 永久二维码超出限制 则更换为 用临时二维码
                    if (empty($weixinInfo) && $weObj->errCode == '45029') {
                        // 结果返回"errcode": 45029, "errmsg": "qrcode count out of limit"
                        $weixinInfo = $weObj->getQRCode($scene_id, 3, 2592000); // 3:字符串型临时二维码
                    }
                    $url = $weixinInfo['url'];
                }
            } elseif ($type == 'url') {
                // 店铺申请页面链接
                $shop_id = $info['id'];
                $url = dsc_url('/#/drp/register') . '?' . http_build_query(['parent_id' => $user_id, 'shop_id' => $shop_id], '', '&');
            }

            if ($url) {
                // 2.生成二维码
                $qrCode = new QrCode($url);

                $qrCode->setSize(266);
                $qrCode->setMargin(15);
                $qrCode->writeFile($qr_code); // 保存二维码
            }

            // 二维码自定义设置信息 start
            $qr_config = app(DrpConfigService::class)->getQrcodeConfig();

            // 背景图
            $drp_bg = $qr_config['backbround'] ?? $drp_bg;

            // 图片不存在或被删除 显示默认背景图
            if (strpos($drp_bg, 'no_image') !== false || strpos($drp_bg, 'drp_bg.png') !== false) {
                $drp_bg = public_path('img/drp_bg.png');
            } else {
                $drp_bg = storage_public($drp_bg);
            }

            $bg_width = Image::open($drp_bg)->width(); // 背景图宽
            $bg_height = Image::open($drp_bg)->height(); // 背景图高
            $logo_width = Image::open($qr_code)->width(); // logo图宽

            $qr_left = $qr_config['qr_left'] ?? ($bg_width - $logo_width) / 2; // 默认中间
            $qr_top = $qr_config['qr_top'] ?? 300;
            // 头像坐标
            $av_left = $qr_config['av_left'] ?? 100;
            $av_top = $qr_config['av_top'] ?? 24;

            // 文字
            $text_description = '';
            if (isset($qr_config['description']) && !empty($qr_config['description'])) {
                // 替换内容里的昵称
                $text_description = str_replace('[$nickname]', $info['nickname'], $qr_config['description']);
                // 换行
                $text_description = str_replace(['\r\n', '\n', '\r'], PHP_EOL, htmlspecialchars($text_description));
            }
            // 文字颜色、大小
            $text_color = $qr_config['color'] ?? '#0e0e0e';
            $font_size = 17;
            // 默认显示微信头像
            $is_show_avatar = $qr_config['avatar'] ?? 0;

            /**
             * 开始生成图片
             */
            // 生成背景图加二维码
            Image::open($drp_bg)->water($qr_code, [$qr_left, $qr_top], 100)->save($out_img);

            // 生成二维码+微信头像
            $user_picture = empty($info['headimgurl']) ? public_path('img/user_default.png') : $info['headimgurl'];
            // 生成微信头像缩略图
            if (!empty($user_picture)) {
                // 远程图片（非本站）
                if (strtolower(substr($user_picture, 0, 4)) == 'http' && strpos($user_picture, asset('/')) === false) {
                    $user_picture = Http::doGet($user_picture);
                    $avatar_open = $avatar;
                    file_put_contents($avatar_open, $user_picture);
                } else {
                    // 本站图片 带http 或 不带http
                    if (strtolower(substr($user_picture, 0, 4)) == 'http') {
                        $user_picture = str_replace(storage_url('/'), '', $user_picture);
                    }
                    // 默认图片
                    if (strpos($user_picture, 'user_default') !== false || strpos($user_picture, 'no_image') !== false) {
                        $avatar_open = $user_picture;
                    } else {
                        $avatar_open = storage_public($user_picture);
                    }
                }
                if (file_exists($avatar_open)) {
                    Image::open($avatar_open)->thumb(60, 60, Image::THUMB_FILLED)->save($avatar);
                }
            }

            // 字体路径
            $fonts_path = storage_public('data/attached/fonts/msyh.ttf');

            if ($is_show_avatar == 0) {
                // 生成背景图加二维码
                Image::open($out_img)->text($text_description, $fonts_path, $font_size, $text_color, [$av_left + 100 + 20, $av_top + 10])->save($out_img);
            } else {
                // 生成背景图加二维码+微信头像
                Image::open($out_img)->water($avatar, [$av_left, $av_top], 100)->text($text_description, $fonts_path, $font_size, $text_color, [$av_left + 100 + 20, $av_top + 10])->save($out_img);
            }
        }

        $image_name = 'data/attached/qrcode/' . basename($out_img);

        return [
            'file' => $image_name,
            'url' => $this->dscRepository->getImagePath($image_name) . '?v=' . Str::random(32)
        ];
    }

    /**
     * 获取当前分类的子分类列表
     * @param int $cat_id
     * @param int $user_id
     * @return \Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getMobileCategoryChild($cat_id = 0, $user_id = 0)
    {
        $arr = Category::where('is_show', 1)
            ->where('parent_id', $cat_id)
            ->orderBy('sort_order')
            ->orderBy('cat_id');

        $arr = $this->baseRepository->getToArrayGet($arr);

        if ($arr) {
            foreach ($arr as $key => $v) {
                $arr[$key]['cat_name'] = (isset($v['cat_alias_name']) && !empty($v['cat_alias_name'])) ? $v['cat_alias_name'] : $v['cat_name'];
                $arr[$key]['cat_icon'] = $this->dscRepository->getImagePath($v['cat_icon']);
                $arr[$key]['touch_icon'] = $this->dscRepository->getImagePath($v['touch_icon']);
                $arr[$key]['touch_catads'] = empty($v['touch_catads']) ? '' : $this->dscRepository->getImagePath($v['touch_catads']);

                if ($cat_id > 0) {
                    $arr[$key]['child'] = $this->getCategoryChild($v['cat_id'], $user_id);
                    if ($arr[$key]['child']) {
                        foreach ($arr[$key]['child'] as $ke => $val) {
                            if ($val['drp_type'] === true) {
                                $arr[$key]['drp_type'] = true;
                            } else {
                                $arr[$key]['drp_type'] = false;
                                break;
                            }
                        }
                    }
                }
            }
        }

        return $arr;
    }

    /**
     * 获取当前分类的子分类列表
     * @param int $cat_id
     * @param int $user_id
     * @return \Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getCategoryChild($cat_id = 0, $user_id = 0)
    {
        $arr = Category::where('is_show', 1)
            ->where('parent_id', $cat_id)
            ->orderBy('sort_order')
            ->orderBy('cat_id')
            ->get();

        $arr = $arr ? $arr->toArray() : [];

        if ($arr) {
            foreach ($arr as $k => $v) {
                $goods_thumb = Goods::where('cat_id', $v['cat_id'])
                    ->where('is_delete', 0)
                    ->where('is_on_sale', 1)
                    ->where('is_alone_sale', 1)
                    ->where('review_status', '>', 2)
                    ->orderBy('sort_order')
                    ->orderBy('goods_id', 'DESC')
                    ->value('goods_thumb');
                $type = DrpType::select('id')
                    ->where('cat_id', $v['cat_id'])
                    ->where('user_id', $user_id);
                $type = $this->baseRepository->getToArrayFirst($type);
                if (!empty($type)) {
                    $arr[$k]['drp_type'] = true;
                } else {
                    $arr[$k]['drp_type'] = false;
                }

                $v['touch_icon'] = empty($v['touch_icon']) ? $goods_thumb : $v['touch_icon'];

                $arr[$k]['touch_icon'] = $this->dscRepository->getImagePath($v['touch_icon']);
                $arr[$k]['cat_icon'] = $this->dscRepository->getImagePath($v['cat_icon']);
                $arr[$k]['cat_name'] = (isset($v['cat_alias_name']) && !empty($v['cat_alias_name'])) ? $v['cat_alias_name'] : $v['cat_name'];
            }
        }

        return $arr;
    }


    /**
     * 分类商品
     * @param int $uid
     * @param int $cat_id
     * @return array
     */
    public function getCategoryGetGoods($uid = 0, $cat_id = 0)
    {
        $list = Goods::select('goods_id', 'cat_id', 'goods_name', 'shop_price', 'goods_thumb', 'dis_commission')
            ->where('is_real', 1)
            ->where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('review_status', '>', 2)
            ->where('is_show', 1)
            ->where('is_delete', 0)
            ->where('is_distribution', 1)
            ->where('cat_id', $cat_id);
        $list = $this->baseRepository->getToArrayGet($list);

        // 获取配置->是否显示佣金比例
        $ischeck = $this->drpConfig('commission');

        foreach ($list as $k => $v) {
            $list[$k]['goods_thumb'] = $this->dscRepository->getImagePath($v['goods_thumb']);
            $list[$k]['shop_price_formated'] = $this->dscRepository->getPriceFormat($v['shop_price'], true);
            $list[$k]['commission'] = $ischeck['value'];
            $type = DrpType::select('id')
                ->where('goods_id', $v['goods_id'])
                ->where('user_id', $uid)
                ->count();
            if (!empty($type)) {
                $list[$k]['drp_type'] = true;
            } else {
                $list[$k]['drp_type'] = false;
            }
        }

        return $list;
    }

    /**
     * 添加代言分类
     * @param int $uid
     * @return mixed
     */
    public function addDrpCart($uid = 0, $id = [], $type = 0)
    {
        if (!is_array($id)) {
            $id = explode(',', $id);
        }
        $time = $this->timeRepository->getGmTime();
        foreach ($id as $k => $val) {
            if ($type == 1) {//全选增加
                $drptype = DrpType::select('id')
                    ->where('cat_id', $val)
                    ->where('user_id', $uid)
                    ->count();
                if (empty($drptype)) {
                    $res = new DrpType();
                    $res->user_id = $uid;
                    $res->cat_id = $val;
                    $res->goods_id = 0;
                    $res->add_time = $time;
                    $res->type = 1;
                    $res->save();
                }
            } elseif ($type == 2) {//全选删除
                DrpType::where('cat_id', $val)
                    ->where('user_id', $uid)
                    ->delete();
            } else {
                $drptype = DrpType::select('id')
                    ->where('cat_id', $val)
                    ->where('user_id', $uid)
                    ->count();
                if (!empty($drptype)) {
                    DrpType::where('cat_id', $val)
                        ->where('user_id', $uid)
                        ->delete();
                } else {
                    $res = new DrpType();
                    $res->user_id = $uid;
                    $res->cat_id = $val;
                    $res->goods_id = 0;
                    $res->add_time = $time;
                    $res->type = 1;
                    $res->save();
                }
            }
        }

        return true;
    }


    /**
     * 添加代言商品
     * @param int $uid
     * @return mixed
     */
    public function addDrpGoods($uid = 0, $goods_id = 0)
    {
        $time = $this->timeRepository->getGmTime();
        $type = DrpType::select('id')
            ->where('goods_id', $goods_id)
            ->where('user_id', $uid)
            ->first();
        if (!empty($type)) {
            DrpType::where('goods_id', $goods_id)
                ->where('user_id', $uid)
                ->delete();
        } else {
            $res = new DrpType();
            $res->user_id = $uid;
            $res->cat_id = 0;
            $res->goods_id = $goods_id;
            $res->add_time = $time;
            $res->type = 2;
            $res->save();
        }

        return true;
    }

    /**
     * 分销广告位
     * @param string $ad_type
     * @param string $tc_type
     * @param int $num
     * @return array|string
     */
    public function drpAds($ad_type = 'drp', $tc_type = 'banner', $num = 10)
    {
        $position = TouchAdPosition::where(['ad_type' => $ad_type, 'tc_type' => $tc_type])->orderBy('position_id', 'desc')->first();
        $ads = [];
        if (!empty($position)) {
            $banner_ads = app(AdsService::class)->getTouchAds($position->position_id, $num);
            if ($banner_ads) {
                foreach ($banner_ads as $row) {
                    $ads[] = [
                        'pic' => $row['ad_code'],
                        'adsense_id' => $row['ad_id'],
                        'link' => $row['ad_link'],
                    ];
                }
            }
        }

        return $ads;
    }

    /**
     * 订单分销条件
     * @param int $uid
     * @return array
     */
    public function orderAffiliate($uid = 0)
    {
        if (empty($uid)) {
            return [];
        }

        $is_distribution = 0;
        $parent_id = 0;

        // 分销配置 1.4.1 edit
        $drp_config = $this->drpConfig();
        $drp_affiliate = $drp_config['drp_affiliate_on']['value'] ?? 0;
        // 开启分销
        if ($drp_affiliate == 1) {
            // 分销业绩归属模式
            $drp_affiliate_mode = $drp_config['drp_affiliate_mode']['value'] ?? 1;
            // 分销内购模式
            $isdistribution = $drp_config['isdistribution']['value'] ?? 0;

            if ($isdistribution == 0) {
                /**
                 *  0. 禁用内购模式
                 *  mode 1: 业绩归属 上级分销商
                 *  mode 0: 业绩归属 推荐人或上级分销商
                 */
                if ($drp_affiliate_mode == 1) {
                    // 分销业绩归属:1 不用保存parent_id至订单
                    $drp_parent_id = $this->getUserDrpParent($uid); // 上级是否分销商
                    if ($drp_parent_id > 0) {
                        $is_distribution = 1;
                    }

                    $parent_id = 0;

                } else {
                    // 分销业绩归属:0 保存parent_id至订单;
                    $affiliate_drp_id = $this->commonRepository->getDrpShopAffiliate($uid); // 获取分享人 user_id 且必须是分销商
                    if (empty($affiliate_drp_id)) {
                        // 获取当前会员上级分销商
                        $parent_id = $drp_parent_id = $this->getUserDrpParent($uid);
                    } else {
                        $parent_id = $affiliate_drp_id;
                    }

                    if ($parent_id > 0) {
                        $is_distribution = 1;
                    } else {
                        $parent_id = 0;
                    }
                }

            } elseif ($isdistribution == 1) {
                /**
                 *  1. 内购模式
                 *  mode 1: 业绩归属 上级分销商 + 自己
                 *  mode 0: 业绩归属 推荐人或上级分销商 + 自己
                 */
                if ($drp_affiliate_mode == 1) {
                    $drp_shop = $this->getDrpAffiliate($uid); // 自己是否分销商
                    $drp_parent_id = $this->getUserDrpParent($uid); // 上级是否分销商
                    if ($drp_shop > 0 || $drp_parent_id > 0) {
                        $is_distribution = 1;
                    }

                    $parent_id = 0;

                } else {
                    // 分销业绩归属:0 保存parent_id至订单;
                    $affiliate_drp_id = $this->commonRepository->getDrpShopAffiliate($uid); // 获取分享人 user_id 且必须是分销商
                    $drp_shop = 0;
                    if (empty($affiliate_drp_id)) {
                        $drp_shop = $this->getDrpAffiliate($uid); // 自己是否分销商
                        $parent_id = $drp_parent_id = $this->getUserDrpParent($uid); // 上级是否分销商
                    } else {
                        $parent_id = $affiliate_drp_id;
                    }

                    if ($drp_shop > 0 || $parent_id > 0) {
                        $is_distribution = 1;
                    } else {
                        $parent_id = 0;
                    }
                }

            } elseif ($isdistribution == 2) {
                /**
                 *  2. 自动模式
                 *  mode 1: 业绩归属 上级分销商 + 自己（条件：推荐自己微店内商品或自己推荐的链接）
                 *  mode 0：业绩归属 推荐人或上级分销商 + 自己（条件：推荐自己微店内商品或自己推荐的链接）
                 */
                if ($drp_affiliate_mode == 1) {
                    $affiliate_drp_id = $this->commonRepository->getDrpShopAffiliate($uid);
                    if (empty($affiliate_drp_id)) {
                        // 当前不是分销商 获取当前会员上级分销商
                        $drp_parent_id = $this->getUserDrpParent($uid);
                        if ($drp_parent_id > 0) {
                            $is_distribution = 1;
                        }
                    } elseif ($affiliate_drp_id > 0) {
                        $is_distribution = 1;
                    }

                    $parent_id = 0;

                } else {
                    // 分销业绩归属:0 保存parent_id至订单;
                    $affiliate_drp_id = $this->commonRepository->getDrpShopAffiliate($uid); // 获取分享人 user_id 且必须是分销商
                    if (empty($affiliate_drp_id)) {
                        // 获取当前会员上级分销商
                        $parent_id = $drp_parent_id = $this->getUserDrpParent($uid);
                    } else {
                        $parent_id = $affiliate_drp_id;
                    }

                    if ($parent_id > 0) {
                        $is_distribution = 1;
                    } else {
                        $parent_id = 0;
                    }
                }
            }

        }

        return ['is_distribution' => $is_distribution, 'parent_id' => $parent_id];
    }

    /**
     * 购买指定金额成为分销商
     * @param array $pay_log
     * @return bool|int
     */
    public function buyUpdateDrpShop($pay_log = [])
    {
        if (empty($pay_log)) {
            return false;
        }

        $now = $this->timeRepository->getGmTime();

        $membership_card_id = $pay_log['membership_card_id'] ?? 0;
        $user_id = $pay_log['order_id'];

        // 分销配置
        $drp_config = $this->drpConfig();
        // 分销商自动开店
        $status_check = $drp_config['register']['value'] ?? 0;
        // 分销商审核
        $ischeck = $drp_config['ischeck']['value'] ?? 0;

        // 修改付费购买记录（订单）已支付
        $account_log = [
            'paid_time' => $now,
            'is_paid' => 1,
        ];
        $log_id = $pay_log['log_id'];
        app(DistributeService::class)->update_drp_account_log($log_id, $user_id, $account_log);
        // 通过支付日志 查找 account_log表id 作为 订单号id 保存至分成记录表 order_id
        $drp_account_log = app(DistributeService::class)->get_drp_account_log($log_id, $user_id);
        $drp_account_log_id = $drp_account_log['id'] ?? 0;
        $parent_id = $drp_account_log['parent_id'] ?? 0;

        // 查询分销商
        $model = DrpShop::query()->select('id', 'membership_status')->where('user_id', $user_id);

        $model = $model->first();

        $shop_info = $model ? $model->toArray() : [];

        // 首次购买成为分销商
        if (empty($shop_info)) {

            // 默认会员信息
            $user_info = app(DistributeService::class)->userInfo($user_id, ['user_name', 'mobile_phone']);

            $drp_shop = [];
            $drp_shop['user_id'] = $user_id;
            $drp_shop['shop_name'] = $user_info['user_name'] ?? '';
            $drp_shop['real_name'] = $user_info['user_name'] ?? '';
            $drp_shop['mobile'] = $user_info['mobile_phone'] ?? '';
            $drp_shop['apply_time'] = $now; // 申请时间
            $drp_shop['type'] = 0;
            $drp_shop['membership_status'] = 1;// 权益卡状态： 0 关闭，1 启用
            $drp_shop['membership_card_id'] = $membership_card_id;

            if ($ischeck == 1) {
                // 需要审核
                $drp_shop['audit'] = 0;
            } else {
                // 不需要审核
                $drp_shop['audit'] = 1;
                // 权益开始时间
                $drp_shop['open_time'] = $now;
            }

            // 是否自动开店
            if ($status_check == 1) {
                $drp_shop['create_time'] = $now; // 店铺开店时间
                $drp_shop['status'] = 1;
            } else {
                $drp_shop['status'] = 0;
            }

            $drp_shop['isbuy'] = 1;
            $drp_shop['apply_channel'] = 4; // 购买指定金额成为分销商

            // 1.4.1 add
            $cardInfo = app(RightsCardService::class)->cardDetail($membership_card_id);
            if (empty($cardInfo)) {
                return false;
            }

            if (!empty($cardInfo)) {
                // 记录领取有效期
                $expiry_type = $cardInfo['expiry_type'];
                $expiry_date = $cardInfo['expiry_date'] ?? '';
                if ($expiry_type == 'timespan') {
                    // 时间间隔类型  记录结束时间戳 作为 过期时间
                    $drp_shop['expiry_time'] = $cardInfo['expiry_date_end_timestamp'] ?? 0;
                } elseif ($expiry_type == 'days') {
                    // 领取时间几天后过期
                    $drp_shop['expiry_time'] = $now + intval($expiry_date) * 24 * 60 * 60;
                } else {
                    // 永久有效
                    $drp_shop['expiry_time'] = 0;
                }
                $drp_shop['expiry_type'] = $expiry_type;
            }

            $drp_shop_id = $this->updateDrpShop($drp_shop);
            if ($drp_shop_id) {

                // 微信模板消息 - 分销商申请成功通知
                $issend = $drp_config['issend']['value'] ?? 0;
                if ($issend == 1 && file_exists(MOBILE_WECHAT)) {
                    $drp_shop['apply_time_format'] = $this->timeRepository->getLocalDate($this->config['time_format'], $drp_shop['apply_time']);
                    $pushData = [
                        'keyword1' => ['value' => $drp_shop['shop_name'], 'color' => '#173177'],
                        'keyword2' => ['value' => $drp_shop['mobile'], 'color' => '#173177'],
                        'keyword3' => ['value' => $drp_shop['apply_time_format'], 'color' => '#173177']
                    ];
                    $url = dsc_url('/#/drp');
                    app(WechatService::class)->push_template('OPENTM207126233', $pushData, $url, $drp_shop['user_id']);
                }

                // 付费购买分成
                $money = $pay_log['order_amount'] ?? 0;
                app(DrpRightsService::class)->buyDrpLog('drp', $user_id, $drp_account_log_id, $parent_id, $money);
            }

            return $drp_shop_id;
        } else {

            // 续费
            if ($shop_info['membership_card_id'] == $membership_card_id) {

                return $this->drpRenew($shop_info, $membership_card_id);
            }

            // 重新付费购买
            if ($shop_info['membership_card_id'] != $membership_card_id && $shop_info['membership_status'] == 0) {

                $drp_shop = [];
                $drp_shop['id'] = $shop_info['id']; // 分销商id
                $drp_shop['user_id'] = $user_id;
                $drp_shop['membership_status'] = 1;// 权益卡状态： 0 关闭，1 启用
                $drp_shop['membership_card_id'] = $membership_card_id;

                if ($ischeck == 1) {
                    // 需要审核
                    $drp_shop['audit'] = 0;
                } else {
                    // 不需要审核
                    $drp_shop['audit'] = 1;
                    // 权益开始时间
                    $drp_shop['open_time'] = $now;
                }

                // 是否自动开店
                $drp_shop['status'] = $status_check == 1 ? 1 : 0;

                // 1.4.1 add
                $cardInfo = app(RightsCardService::class)->cardDetail($membership_card_id);
                if (empty($cardInfo)) {
                    return false;
                }
                if (!empty($cardInfo)) {
                    // 记录领取有效期
                    $expiry_type = $cardInfo['expiry_type'];
                    $expiry_date = $cardInfo['expiry_date'] ?? '';
                    if ($expiry_type == 'timespan') {
                        // 时间间隔类型  记录结束时间戳 作为 过期时间
                        $drp_shop['expiry_time'] = $cardInfo['expiry_date_end_timestamp'] ?? 0;
                    } elseif ($expiry_type == 'days') {
                        // 领取时间几天后过期
                        $drp_shop['expiry_time'] = $now + intval($expiry_date) * 24 * 60 * 60;
                    } else {
                        // 永久有效
                        $drp_shop['expiry_time'] = 0;
                    }
                    $drp_shop['expiry_type'] = $expiry_type;
                }

                $res = $this->updateDrpShop($drp_shop);
                if ($res) {
                    // 付费购买分成
                    $paid_amount = $pay_log['order_amount'] ?? 0;
                    app(DrpRightsService::class)->buyDrpLog('drp', $user_id, $drp_account_log_id, $parent_id, $paid_amount);
                }

                return $res;
            }

        }
        return false;
    }

    /**
     * 购买商品成为分销商
     * @param array $order
     * @return bool
     */
    public function buyGoodsUpdateDrpShop($order = [])
    {
        if (empty($order)) {
            return false;
        }

        $where = [
            'order_id' => $order['order_id']
        ];
        $order_goods = app(OrderGoodsService::class)->getOrderGoodsList($where);

        $membership_card_id = 0;
        if ($order_goods) {
            foreach ($order_goods as $val) {
                if (isset($val['membership_card_id']) && $val['membership_card_id'] > 0) {
                    $membership_card_id = $val['membership_card_id'];
                }
            }

            if ($membership_card_id > 0) {

                $now = $this->timeRepository->getGmTime();
                $user_id = $order['user_id'];

                // 分销配置
                $drp_config = $this->drpConfig();
                // 分销商自动开店
                $status_check = $drp_config['register']['value'] ?? 0;
                // 分销商审核
                $ischeck = $drp_config['ischeck']['value'] ?? 0;

                // 查询分销商
                $model = DrpShop::query()->select('id', 'membership_status')->where('user_id', $user_id);

                $model = $model->first();

                $shop_info = $model ? $model->toArray() : [];

                // 首次购买成为分销商
                if (empty($shop_info)) {

                    // 默认会员信息
                    $user_info = app(DistributeService::class)->userInfo($user_id, ['user_name', 'mobile_phone']);

                    $drp_shop = [];
                    $drp_shop['user_id'] = $user_id;
                    $drp_shop['shop_name'] = $user_info['user_name'] ?? '';
                    $drp_shop['real_name'] = $user_info['user_name'] ?? '';
                    $drp_shop['mobile'] = $user_info['mobile_phone'] ?? '';
                    $drp_shop['apply_time'] = $now; // 申请时间
                    $drp_shop['type'] = 0;
                    $drp_shop['membership_status'] = 1;// 权益卡状态： 0 关闭，1 启用
                    $drp_shop['membership_card_id'] = $membership_card_id;

                    if ($ischeck == 1) {
                        // 需要审核
                        $drp_shop['audit'] = 0;
                    } else {
                        // 不需要审核
                        $drp_shop['audit'] = 1;
                        // 权益开始时间
                        $drp_shop['open_time'] = $now;
                    }

                    // 是否自动开店
                    if ($status_check == 1) {
                        $drp_shop['create_time'] = $now; // 店铺开店时间
                        $drp_shop['status'] = 1;
                    } else {
                        $drp_shop['status'] = 0;
                    }

                    $drp_shop['apply_channel'] = 1; // 购买商品成为分销商

                    // 1.4.1 add
                    $cardInfo = app(RightsCardService::class)->cardDetail($membership_card_id);
                    if (empty($cardInfo)) {
                        return false;
                    }
                    if (!empty($cardInfo)) {
                        // 记录领取有效期
                        $expiry_type = $cardInfo['expiry_type'];
                        $expiry_date = $cardInfo['expiry_date'] ?? '';
                        if ($expiry_type == 'timespan') {
                            // 时间间隔类型  记录结束时间戳 作为 过期时间
                            $drp_shop['expiry_time'] = $cardInfo['expiry_date_end_timestamp'] ?? 0;
                        } elseif ($expiry_type == 'days') {
                            // 领取时间几天后过期
                            $drp_shop['expiry_time'] = $now + intval($expiry_date) * 24 * 60 * 60;
                        } else {
                            // 永久有效
                            $drp_shop['expiry_time'] = 0;
                        }

                        $drp_shop['expiry_type'] = $expiry_type;
                    }

                    $drp_shop_id = $this->updateDrpShop($drp_shop);
                    if ($drp_shop_id) {

                        // 微信模板消息 - 分销商申请成功通知
                        $issend = $drp_config['issend']['value'] ?? 0;
                        if ($issend == 1 && file_exists(MOBILE_WECHAT)) {
                            $drp_shop['apply_time_format'] = $this->timeRepository->getLocalDate($this->config['time_format'], $drp_shop['apply_time']);
                            $pushData = [
                                'keyword1' => ['value' => $drp_shop['shop_name'], 'color' => '#173177'],
                                'keyword2' => ['value' => $drp_shop['mobile'], 'color' => '#173177'],
                                'keyword3' => ['value' => $drp_shop['apply_time_format'], 'color' => '#173177']
                            ];
                            $url = dsc_url('/#/drp');
                            app(WechatService::class)->push_template('OPENTM207126233', $pushData, $url, $drp_shop['user_id']);
                        }

                        // 购买指定商品分成
                        $orderGoods = app(DistributeService::class)->getBuyGoodsOrder($drp_shop['user_id'], $membership_card_id);
                        if (!empty($orderGoods)) {
                            $paid_amount = $orderGoods['money_paid'] + $orderGoods['surplus']; // 订单实际支付金额
                            app(DrpRightsService::class)->goodsDrpLog('drp', $drp_shop['user_id'], $orderGoods['order_id'], $orderGoods['parent_id'], $paid_amount);
                        }
                    }

                    return $drp_shop_id;
                } else {

                    // 续费
                    if ($shop_info['membership_card_id'] == $membership_card_id) {

                        return $this->drpRenew($shop_info, $membership_card_id);
                    }

                    // 重新购买指定商品
                    if ($shop_info['membership_card_id'] != $membership_card_id && $shop_info['membership_status'] == 0) {

                        $drp_shop = [];
                        $drp_shop['id'] = $shop_info['id']; // 分销商id
                        $drp_shop['user_id'] = $shop_info['user_id'];
                        $drp_shop['membership_status'] = 1;// 权益卡状态： 0 关闭，1 启用
                        $drp_shop['membership_card_id'] = $membership_card_id;

                        if ($ischeck == 1) {
                            // 需要审核
                            $drp_shop['audit'] = 0;
                        } else {
                            // 不需要审核
                            $drp_shop['audit'] = 1;
                            // 权益开始时间
                            $drp_shop['open_time'] = $now;
                        }

                        // 是否自动开店
                        $drp_shop['status'] = $status_check == 1 ? 1 : 0;

                        // 1.4.1 add
                        $cardInfo = app(RightsCardService::class)->cardDetail($membership_card_id);
                        if (empty($cardInfo)) {
                            return false;
                        }

                        if (!empty($cardInfo)) {
                            // 记录领取有效期
                            $expiry_type = $cardInfo['expiry_type'];
                            $expiry_date = $cardInfo['expiry_date'] ?? '';
                            if ($expiry_type == 'timespan') {
                                // 时间间隔类型  记录结束时间戳 作为 过期时间
                                $drp_shop['expiry_time'] = $cardInfo['expiry_date_end_timestamp'] ?? 0;
                            } elseif ($expiry_type == 'days') {
                                // 领取时间几天后过期
                                $drp_shop['expiry_time'] = $now + intval($expiry_date) * 24 * 60 * 60;
                            } else {
                                // 永久有效
                                $drp_shop['expiry_time'] = 0;
                            }

                            $drp_shop['expiry_type'] = $expiry_type;
                        }

                        $res = $this->updateDrpShop($drp_shop);
                        if ($res) {
                            // 购买指定商品分成
                            $order = app(DistributeService::class)->getBuyGoodsOrder($drp_shop['user_id'], $membership_card_id);
                            if (!empty($order)) {
                                $paid_amount = $order['money_paid'] + $order['surplus']; // 订单实际支付金额
                                app(DrpRightsService::class)->goodsDrpLog('drp', $drp_shop['user_id'], $order['order_id'], $order['parent_id'], $paid_amount);
                            }
                        }

                        return $res;
                    }
                }
            }
        }

        return false;
    }

    /**
     * 续费权益卡
     * @param array $shop_info
     * @param int $membership_card_id
     * @return bool|int
     */
    public function drpRenew($shop_info = [], $membership_card_id = 0)
    {
        if (empty($shop_info) || empty($membership_card_id)) {
            return false;
        }

        $now = $this->timeRepository->getGmTime();

        $drp_shop = [];
        $drp_shop['id'] = $shop_info['id']; // 分销商id
        $drp_shop['user_id'] = $shop_info['user_id'];
        $drp_shop['membership_status'] = 1;// 权益卡状态： 0 关闭，1 启用
        $drp_shop['membership_card_id'] = $membership_card_id;

        // 不需要审核
        $drp_shop['audit'] = 1;
        // 权益开始时间
        $drp_shop['open_time'] = $now;
        // 是否自动开店
        $drp_shop['status'] = 1;

        // 1.4.1 add
        $cardInfo = app(RightsCardService::class)->cardDetail($membership_card_id);
        if (empty($cardInfo)) {
            return false;
        }

        if (!empty($cardInfo)) {

            // 记录领取有效期
            $expiry_type = $cardInfo['expiry_type'];
            $expiry_date = $cardInfo['expiry_date'] ?? '';
            if ($expiry_type == 'timespan') {
                // 时间间隔类型  记录结束时间戳 作为 过期时间
                $drp_shop['expiry_time'] = $cardInfo['expiry_date_end_timestamp'] ?? 0;
            } elseif ($expiry_type == 'days') {
                // 计算权益卡有效期 剩余时间
                $remaining_time = !empty($shop_info['expiry_time']) ? $shop_info['expiry_time'] - $now : 0;

                // 领取时间几天后过期
                $drp_shop['expiry_time'] = $remaining_time + $now + intval($expiry_date) * 24 * 60 * 60;
            } else {
                // 永久有效
                $drp_shop['expiry_time'] = 0;
            }

            $drp_shop['expiry_type'] = $expiry_type;
        }

        $res = $this->updateDrpShop($drp_shop);
        if ($res) {
            return $res;
        }

        return false;
    }

    /**
     * 消费积分兑换
     * @param int $user_id
     * @param int $pay_point
     * @param string $user_note 描述
     * @return array
     */
    public function cashPayPoint($user_id = 0, $pay_point = 0, $user_note = '')
    {
        if (empty($user_id) || empty($pay_point)) {
            return ['error' => 1, 'msg' => lang('common.illegal_operate')];
        }

        // 验证会员 消费积分是否足够
        $model = Users::where('user_id', $user_id)->select('user_id', 'pay_points')->first();
        if ($model) {
            $user_pay_point = $model->pay_points;

            // 会员消费积分 不足
            $pay_point = intval($pay_point);
            if (empty($user_pay_point) || $user_pay_point < $pay_point) {
                return ['error' => 2, 'msg' => lang('drp.user_pay_point_deficiency')];
            }

            // 抵扣消费积分 同时记录账户变动日志
            $log_id = $this->accountLogRepository->log_account_change($user_id, 0, 0, 0, $pay_point * (-1), $user_note);

            return ['error' => 0, 'msg' => 'success', 'log_id' => $log_id];
        }

        return ['error' => 1, 'msg' => lang('common.illegal_operate')];
    }

    /**
     * 如有上级推荐人（分销商），且关系在有效期内，更新推荐时间 1.4.3
     * @param int $user_id
     * @param int $parent_id
     * @return bool
     */
    public function updateBindTime($user_id = 0, $parent_id = 0)
    {
        if (empty($user_id) || empty($parent_id)) {
            return false;
        }

        Users::where('user_id', $user_id)->where('drp_parent_id', $parent_id)->update(['drp_bind_update_time' => $this->timeRepository->getGmTime()]);

        return true;
    }

    /**
     * 后台指定成为分销
     * @param int $user_id
     * @param int $membership_card_id
     * @param int $status_check 是否自动开店
     * @return bool|int
     */
    public function specifyUpdateDrpShop($user_id = 0, $membership_card_id = 0, $status_check = 0)
    {
        if (empty($user_id) || empty($membership_card_id)) {
            return false;
        }

        $now = $this->timeRepository->getGmTime();

        // 分销商自动开店
        $status_check = $status_check ?? 0;

        // 查询分销商
        $model = DrpShop::query()->select('id', 'membership_status', 'membership_card_id')->where('user_id', $user_id);

        $model = $model->first();

        $shop_info = $model ? $model->toArray() : [];

        // 首次成为分销商
        if (empty($shop_info)) {

            // 默认会员信息
            $user_info = app(DistributeService::class)->userInfo($user_id, ['user_name', 'mobile_phone']);

            $drp_shop = [];
            $drp_shop['user_id'] = $user_id;
            $drp_shop['shop_name'] = $user_info['user_name'] ?? '';
            $drp_shop['real_name'] = $user_info['user_name'] ?? '';
            $drp_shop['mobile'] = $user_info['mobile_phone'] ?? '';
            $drp_shop['apply_time'] = $now; // 申请时间
            $drp_shop['type'] = 0;
            $drp_shop['membership_status'] = 1;// 权益卡状态： 0 关闭，1 启用
            $drp_shop['membership_card_id'] = $membership_card_id;

            // 不需要审核
            $drp_shop['audit'] = 1;
            // 权益开始时间
            $drp_shop['open_time'] = $now;

            // 是否自动开店
            if ($status_check == 1) {
                $drp_shop['create_time'] = $now; // 店铺开店时间
                $drp_shop['status'] = 1;
            } else {
                $drp_shop['status'] = 0;
            }

            $drp_shop['apply_channel'] = 0; // 后台指定 默认为免费领取

            // 1.4.1 add
            $cardInfo = app(RightsCardService::class)->cardDetail($membership_card_id);
            if (empty($cardInfo)) {
                return false;
            }
            if (!empty($cardInfo)) {
                // 记录领取有效期
                $expiry_type = $cardInfo['expiry_type'];
                $expiry_date = $cardInfo['expiry_date'] ?? '';
                if ($expiry_type == 'timespan') {
                    // 时间间隔类型  记录结束时间戳 作为 过期时间
                    $drp_shop['expiry_time'] = $cardInfo['expiry_date_end_timestamp'] ?? 0;
                } elseif ($expiry_type == 'days') {
                    // 领取时间几天后过期
                    $drp_shop['expiry_time'] = $now + intval($expiry_date) * 24 * 60 * 60;
                } else {
                    // 永久有效
                    $drp_shop['expiry_time'] = 0;
                }

                $drp_shop['expiry_type'] = $expiry_type;
            }

            $drp_shop_id = $this->updateDrpShop($drp_shop);

            return $drp_shop_id;
        } else {

            // 更新
            $drp_shop = [];
            $drp_shop['id'] = $shop_info['id']; // 分销商id
            $drp_shop['user_id'] = $user_id;
            $drp_shop['membership_card_id'] = $membership_card_id;

            // 是否自动开店
            if ($status_check == 1) {
                $drp_shop['create_time'] = $now; // 店铺开店时间
                $drp_shop['status'] = 1;
            } else {
                $drp_shop['status'] = 0;
            }

            if ($shop_info['membership_card_id'] != $membership_card_id) {

                $drp_shop['membership_status'] = 1;// 权益卡状态： 0 关闭，1 启用

                // 不需要审核
                $drp_shop['audit'] = 1;
                // 权益开始时间
                $drp_shop['open_time'] = $now;

                // 1.4.1 add
                $cardInfo = app(RightsCardService::class)->cardDetail($membership_card_id);
                if (empty($cardInfo)) {
                    return false;
                }
                if (!empty($cardInfo)) {
                    // 记录领取有效期
                    $expiry_type = $cardInfo['expiry_type'];
                    $expiry_date = $cardInfo['expiry_date'] ?? '';
                    if ($expiry_type == 'timespan') {
                        // 时间间隔类型  记录结束时间戳 作为 过期时间
                        $drp_shop['expiry_time'] = $cardInfo['expiry_date_end_timestamp'] ?? 0;
                    } elseif ($expiry_type == 'days') {
                        // 领取时间几天后过期
                        $drp_shop['expiry_time'] = $now + intval($expiry_date) * 24 * 60 * 60;
                    } else {
                        // 永久有效
                        $drp_shop['expiry_time'] = 0;
                    }

                    $drp_shop['expiry_type'] = $expiry_type;
                }
            }

            $res = $this->updateDrpShop($drp_shop);

            return $res;
        }
    }

    /**
     * 判断分销商自己微店内商品
     * @param int $user_id
     * @param int $goods_id
     * @param int $cat_id
     * @return bool
     */
    public function isDrpTypeGoods($user_id = 0, $goods_id = 0, $cat_id = 0)
    {
        if (empty($user_id)) {
            return false;
        }

        $model = DrpType::query()->where('user_id', $user_id)->where(function ($query) use ($goods_id, $cat_id) {
            $query->where('goods_id', $goods_id)
                ->orWhere('cat_id', $cat_id);
        });

        $count = $model->count();

        if ($count > 0) {
            return true;
        }

        return false;
    }

    /**
     * 订单购买开通会员权益卡支付成功 成为分销商
     * @param int $user_id
     * @param int $order_id
     * @param array $pay_log
     * @return bool|int
     */
    public function buyOrderUpdateDrpShop($user_id = 0, $order_id = 0, $pay_log = [])
    {
        if (empty($user_id) || empty($order_id)) {
            return false;
        }

        $now = $this->timeRepository->getGmTime();

        $model = OrderInfoMembershipCard::query()->where('order_id', $order_id)
            ->where('user_id', $user_id);

        // 订单已支付
        $model = $model->whereHas('orderInfo', function ($query) {
            $query->where('pay_status', PS_PAYED);
        });

        $model = $model->first();

        $order_membership_card = $model ? $model->toArray() : [];

        if (empty($order_membership_card)) {
            return false;
        }

        $membership_card_id = $order_membership_card['membership_card_id'] ?? 0;

        if ($membership_card_id > 0) {

            // 分销配置
            $drp_config = $this->drpConfig();
            // 分销商自动开店
            $status_check = $drp_config['register']['value'] ?? 0;
            // 分销商审核
            $ischeck = $drp_config['ischeck']['value'] ?? 0;

            if (empty($pay_log)) {
                $pay_log = PayLog::query()->where('order_id', $order_id)->where('order_type', PAY_ORDER)->first();
                $pay_log = $pay_log ? $pay_log->toArray() : [];
            }

            if (empty($pay_log)) {
                return false;
            }

            // 修改付费购买记录（订单）已支付
            $account_log = [
                'paid_time' => $now,
                'is_paid' => 1,
            ];
            $log_id = $pay_log['log_id'] ?? 0;
            app(DistributeService::class)->update_drp_account_log($log_id, $user_id, $account_log);
            // 通过支付日志 查找 account_log表id 作为 订单号id 保存至分成记录表 order_id
            $drp_account_log = app(DistributeService::class)->get_drp_account_log($log_id, $user_id);
            $drp_account_log_id = $drp_account_log['id'] ?? 0;
            $parent_id = $drp_account_log['parent_id'] ?? 0;

            // 查询分销商
            $model = DrpShop::query()->select('id', 'membership_status')->where('user_id', $user_id);

            $model = $model->first();

            $shop_info = $model ? $model->toArray() : [];

            // 首次成为分销商
            if (empty($shop_info)) {

                // 默认会员信息
                $user_info = app(DistributeService::class)->userInfo($user_id, ['user_name', 'mobile_phone']);

                $drp_shop = [];
                $drp_shop['user_id'] = $user_id;
                $drp_shop['shop_name'] = $user_info['user_name'] ?? '';
                $drp_shop['real_name'] = $user_info['user_name'] ?? '';
                $drp_shop['mobile'] = $user_info['mobile_phone'] ?? '';
                $drp_shop['apply_time'] = $now; // 申请时间
                $drp_shop['type'] = 0;
                $drp_shop['membership_status'] = 1;// 权益卡状态： 0 关闭，1 启用
                $drp_shop['membership_card_id'] = $membership_card_id;

                if ($ischeck == 1) {
                    // 需要审核
                    $drp_shop['audit'] = 0;
                } else {
                    // 不需要审核
                    $drp_shop['audit'] = 1;
                    // 权益开始时间
                    $drp_shop['open_time'] = $now;
                }

                // 是否自动开店
                if ($status_check == 1) {
                    $drp_shop['create_time'] = $now; // 店铺开店时间
                    $drp_shop['status'] = 1;
                } else {
                    $drp_shop['status'] = 0;
                }

                $drp_shop['apply_channel'] = 5; // 订单开通购买成为分销商

                // 1.4.1 add
                $cardInfo = app(RightsCardService::class)->cardDetail($membership_card_id);
                if (empty($cardInfo)) {
                    return false;
                }
                if (!empty($cardInfo)) {
                    // 记录领取有效期
                    $expiry_type = $cardInfo['expiry_type'];
                    $expiry_date = $cardInfo['expiry_date'] ?? '';
                    if ($expiry_type == 'timespan') {
                        // 时间间隔类型  记录结束时间戳 作为 过期时间
                        $drp_shop['expiry_time'] = $cardInfo['expiry_date_end_timestamp'] ?? 0;
                    } elseif ($expiry_type == 'days') {
                        // 领取时间几天后过期
                        $drp_shop['expiry_time'] = $now + intval($expiry_date) * 24 * 60 * 60;
                    } else {
                        // 永久有效
                        $drp_shop['expiry_time'] = 0;
                    }

                    $drp_shop['expiry_type'] = $expiry_type;
                }

                $drp_shop_id = $this->updateDrpShop($drp_shop);
                if ($drp_shop_id) {

                    // 微信模板消息 - 分销商申请成功通知
                    $issend = $drp_config['issend']['value'] ?? 0;
                    if ($issend == 1 && file_exists(MOBILE_WECHAT)) {
                        $drp_shop['apply_time_format'] = $this->timeRepository->getLocalDate($this->config['time_format'], $drp_shop['apply_time']);
                        $pushData = [
                            'keyword1' => ['value' => $drp_shop['shop_name'], 'color' => '#173177'],
                            'keyword2' => ['value' => $drp_shop['mobile'], 'color' => '#173177'],
                            'keyword3' => ['value' => $drp_shop['apply_time_format'], 'color' => '#173177']
                        ];
                        $url = dsc_url('/#/drp');
                        app(WechatService::class)->push_template('OPENTM207126233', $pushData, $url, $drp_shop['user_id']);
                    }

                    // 开通购买权益卡分成
                    $money = $order_membership_card['membership_card_buy_money'] ?? 0; // 购买权益卡金额
                    app(DrpRightsService::class)->orderBuyDrpLog('drp', $user_id, $drp_account_log_id, $parent_id, $money);
                }

                return $drp_shop_id;
            }

            return false;
        }

        return false;
    }

}
