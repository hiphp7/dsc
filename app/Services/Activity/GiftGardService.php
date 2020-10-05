<?php

namespace App\Services\Activity;

use App\Models\GiftGardType;
use App\Models\Goods;
use App\Models\UserGiftGard;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Goods\GoodsCommonService;
use App\Services\User\UserCommonService;

class GiftGardService
{
    protected $baseRepository;
    protected $config;
    protected $goodsCommonService;
    protected $userCommonService;
    protected $dscRepository;
    protected $timeRepository;

    public function __construct(
        BaseRepository $baseRepository,
        GoodsCommonService $goodsCommonService,
        DscRepository $dscRepository,
        UserCommonService $userCommonService,
        TimeRepository $timeRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->goodsCommonService = $goodsCommonService;
        $this->userCommonService = $userCommonService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->timeRepository = $timeRepository;
    }

    /**
     * 获得礼品卡下的商品
     *
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param $size
     * @param int $page
     * @param int $user_id
     * @return array
     * @throws \Exception
     */
    public function giftGetGoods($warehouse_id = 0, $area_id = 0, $area_city = 0, $size, $page = 1, $user_id = 0)
    {
        $page = isset($_REQUEST['page']) && intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : $page;

        if (session('gift_sn')) {
            $gift_sn = session('gift_sn');
        } else {
            $cache_id = 'gift_gard' . '_' . $user_id;
            $gift_sn = cache($cache_id);
        }
        $config_goods = UserGiftGard::where('gift_sn', $gift_sn)->where('is_delete', 1)->first();

        $config_goods = $config_goods ? $config_goods->toArray() : [];

        $config_goods_arr = [];
        if ($config_goods && $config_goods['config_goods_id']) {
            $config_goods_arr = !is_array($config_goods['config_goods_id']) ? explode(',', $config_goods['config_goods_id']) : $config_goods['config_goods_id'];
        }

        $res = Goods::whereIn('goods_id', $config_goods_arr);

        if ($this->config['review_goods']) {
            $res = $res->where('review_status', '>', 2);
        }

        $res = $this->dscRepository->getAreaLinkGoods($res, $area_id, $area_city);

        $where = [
            'area_pricetype' => $this->config['area_pricetype'],
            'area_id' => $area_id,
            'area_city' => $area_city
        ];

        if (session('user_rank')) {
            $user_rank = session('user_rank');
            $discount = session('discount');
        } else {
            $rank = $this->userCommonService->getUserRankByUid($user_id);
            $user_rank = $rank['rank_id'] ? $rank['rank_id'] : 1;
            $discount = $rank['discount'] / 100;
        }

        $res = $res->with([
            'getMemberPrice' => function ($query) use ($user_rank) {
                $query->where('user_rank', $user_rank);
            },
            'getWarehouseGoods' => function ($query) use ($warehouse_id) {
                $query->where('region_id', $warehouse_id);
            },
            'getWarehouseAreaGoods' => function ($query) use ($where) {
                $query = $query->where('region_id', $where['area_id']);

                if ($where['area_pricetype'] == 1) {
                    $query->where('city_id', $where['area_city']);
                }
            }
        ]);

        $start = ($page - 1) * $size;

        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        $arr = [];
        if ($res) {
            foreach ($res as $row) {
                $price = [
                    'model_price' => isset($row['model_price']) ? $row['model_price'] : 0,
                    'user_price' => isset($row['get_member_price']['user_price']) ? $row['get_member_price']['user_price'] : 0,
                    'percentage' => isset($row['get_member_price']['percentage']) ? $row['get_member_price']['percentage'] : 0,
                    'warehouse_price' => isset($row['get_warehouse_goods']['warehouse_price']) ? $row['get_warehouse_goods']['warehouse_price'] : 0,
                    'region_price' => isset($row['get_warehouse_area_goods']['region_price']) ? $row['get_warehouse_area_goods']['region_price'] : 0,
                    'shop_price' => isset($row['shop_price']) ? $row['shop_price'] : 0,
                    'warehouse_promote_price' => isset($row['get_warehouse_goods']['warehouse_promote_price']) ? $row['get_warehouse_goods']['warehouse_promote_price'] : 0,
                    'region_promote_price' => isset($row['get_warehouse_area_goods']['region_promote_price']) ? $row['get_warehouse_area_goods']['region_promote_price'] : 0,
                    'promote_price' => isset($row['promote_price']) ? $row['promote_price'] : 0,
                    'wg_number' => isset($row['get_warehouse_goods']['region_number']) ? $row['get_warehouse_goods']['region_number'] : 0,
                    'wag_number' => isset($row['get_warehouse_area_goods']['region_number']) ? $row['get_warehouse_area_goods']['region_number'] : 0,
                    'goods_number' => isset($row['goods_number']) ? $row['goods_number'] : 0
                ];

                $price = $this->goodsCommonService->getGoodsPrice($price, $discount, $row);

                $row['shop_price'] = $price['shop_price'];
                $row['promote_price'] = $price['promote_price'];
                $row['goods_number'] = $price['goods_number'];

                if ($row['promote_price'] > 0) {
                    $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                } else {
                    $promote_price = 0;
                }

                $row['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
                $row['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $row['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
                $row['shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                $row['promote_price'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($promote_price) : '';
                $row['url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);

                $arr[] = $row;
            }
        }

        $arr = collect($arr)->values()->all();

        return $arr;
    }

    /**
     * 获得礼品卡总数
     *
     * @return int
     */
    public function getGiftGoodsCount()
    {
        $config_goods = UserGiftGard::where('gift_sn', session('gift_sn', ''))->where('is_delete', 1)->first();

        $config_goods = $config_goods ? $config_goods->toArray() : [];

        $config_goods_arr = [];
        if ($config_goods && $config_goods['config_goods_id']) {
            $config_goods_arr = !is_array($config_goods['config_goods_id']) ? explode(',', $config_goods['config_goods_id']) : $config_goods['config_goods_id'];
        }

        $count = 0;
        if ($config_goods_arr) {
            $count = Goods::whereIn('goods_id', $config_goods_arr)->count();
        }

        /* 返回商品总数 */
        return $count;
    }

    /**
     * 用户登录函数
     *
     * @param string $gift_sn
     * @param string $gift_pwd
     * @return array
     */
    public function getCheckGiftLogin($gift_sn = '', $gift_pwd = '')
    {
        if (empty($gift_pwd) || empty($gift_sn)) {
            return ['error' => 0];
        }

        $time = $this->timeRepository->getGmTime();
        $url = '';

        $gift_count = UserGiftGard::where('gift_sn', $gift_sn)
            ->where('goods_id', 0)
            ->where('is_delete', 1)
            ->count();

        if ($gift_count <= 0) {
            session([
                'gift_id' => 0,
                'gift_sn' => ''
            ]);

            $error = 0;
            $url = show_message($GLOBALS['_LANG']['not_gift_gard'], $GLOBALS['_LANG']['gift_gard_login'], 'gift_gard.php', 'error');

            return [
                'error' => $error,
                'url' => $url
            ];
        }

        $result = [];
        $row = [];
        if ($gift_sn) {
            $result = UserGiftGard::select(['gift_gard_id', 'gift_id'])
                ->where('gift_sn', $gift_sn)
                ->where('gift_password', $gift_pwd)
                ->where('is_delete', 1)
                ->first();
            $result = $result ? $result->toArray() : [];

            if (empty($result)) {
                $gift_sn = '';
                $error = 0;
                $url = show_message($GLOBALS['_LANG']['password_error'], $GLOBALS['_LANG']['back_gift_login'], 'gift_gard.php?act=gift_login', 'error');
            }

            if ($result) {
                $row = GiftGardType::where('review_status', 3)
                    ->where('gift_id', $result['gift_id']);
                $row = $this->baseRepository->getToArrayFirst($row);
            }
        }

        if ($row) {

            if ($row['gift_end_date'] <= $time) {
                $gift_sn = '';
                $error = 0;
                $url = show_message($GLOBALS['_LANG']['gift_gard_overdue_time'] . $this->timeRepository->getLocalDate('Y-m-d H:i:s', $row['gift_end_date']), $GLOBALS['_LANG']['back_gift_login'], 'gift_gard.php?act=gift_login', 'error');
            } elseif ($row['gift_start_date'] >= $time) {
                $gift_sn = '';
                $error = 0;
                $url = show_message($GLOBALS['_LANG']['gift_gard_use_time'] . $this->timeRepository->getLocalDate('Y-m-d H:i:s', $row['gift_start_date']), $GLOBALS['_LANG']['back_gift_login'], 'gift_gard.php?act=gift_login', 'error');
            }
        } else {
            $gift_sn = '';
            $error = 0;
            $url = show_message($GLOBALS['_LANG']['gift_gard_error'], $GLOBALS['_LANG']['back_gift_login'], 'gift_gard.php?act=gift_login', 'error');
        }

        if (empty($url)) {
            if ($result) {
                session([
                    'gift_id' => $gift_sn ? $result['gift_id'] : 0,
                    'gift_sn' => $gift_sn
                ]);

                cookie()->queue('gift_sn', $gift_sn, 60 * 24 * 15);

                $error = 1;
            } else {
                session([
                    'gift_id' => 0,
                    'gift_sn' => ''
                ]);

                $error = 0;
                $url = show_message($GLOBALS['_LANG']['not_gift_gard'], $GLOBALS['_LANG']['back_gift_login'], 'gift_gard.php?act=gift_login', 'error');
            }
        }

        return [
            'error' => $error,
            'url' => $url
        ];
    }

    /**
     * 用户登录函数
     *
     * @param string $gift_sn
     * @param string $gift_pwd
     * @param int $user_id
     * @return array
     * @throws \Exception
     */
    public function getWepCheckGiftLogin($gift_sn = '', $gift_pwd = '', $user_id = 0)
    {
        if (empty($gift_pwd) || empty($gift_sn)) {
            return ['error' => 1];
        }
        // 验证礼品卡是否存在
        $gift_count = UserGiftGard::where('gift_sn', $gift_sn)
            ->where('goods_id', 0)
            ->where('is_delete', 1)
            ->count();
        if ($gift_count <= 0) {
            return [
                'error' => 1,  // 礼品卡卡号不存在
                'msg' => lang('gift_gard.not_gift_gard')
            ];
        }
        $result = [];
        $row = [];
        if ($gift_sn) {
            $result = UserGiftGard::select(['gift_gard_id', 'gift_id'])
                ->where('gift_sn', $gift_sn)
                ->where('gift_password', $gift_pwd)
                ->where('is_delete', 1)
                ->first();
            $result = $result ? $result->toArray() : [];

            if (empty($result)) {
                return [
                    'error' => 1,  // 密码错误
                    'msg' => lang('gift_gard.password_error')
                ];
            }

            if ($result) {
                $row = GiftGardType::where('review_status', 3)
                    ->where('gift_id', $result['gift_id']);
                $row = $this->baseRepository->getToArrayFirst($row);
            }
        }

        if ($row) {
            $time = $this->timeRepository->getGmTime();
            if ($row['gift_end_date'] <= $time) {
                return [
                    'error' => 1,  // 礼品卡已过期, 有效期至
                    'msg' => lang('gift_gard.gift_gard_overdue_time') . $this->timeRepository->getLocalDate('Y-m-d H:i:s', $row['gift_end_date'])
                ];
            } elseif ($row['gift_start_date'] >= $time) {
                return [
                    'error' => 1,  // 礼品卡开始使用日期为
                    'msg' => lang('gift_gard.gift_gard_Use_time') . $this->timeRepository->getLocalDate('Y-m-d H:i:s', $row['gift_start_date'])
                ];
            }
        } else {
            return [
                'error' => 1,  // 礼品卡卡号或密码错误
                'msg' => lang('gift_gard.gift_gard_error')
            ];
        }

        if ($result) {
            // 记录缓存
            $cache_id = 'gift_gard' . '_' . $user_id;
            $res = cache($cache_id);
            if (is_null($res)) {
                cache()->forever($cache_id, $gift_sn);
            }

            return [
                'error' => 0,  // 登录礼品卡成功
                'msg' => lang('gift_gard.gift_login_success')
            ];
        } else {
            return [
                'error' => 1,  // 密码错误
                'msg' => lang('gift_gard.password_error')
            ];
        }
    }


    /**
     * 提货列表
     *
     * @param int $page
     * @param int $size
     * @param int $user_id
     * @return mixed
     */
    public function getTakeList($page = 1, $size = 1, $user_id = 0)
    {
        $begin = ($page - 1) * $size;

        $res = UserGiftGard::where('user_id', $user_id);

        $res = $res->whereHas('getGoods', function ($query) {
            $query->where('is_alone_sale', 1)
                ->where('is_on_sale', 1)
                ->where('is_delete', 0)
                ->where('review_status', '>', 2);
        });

        $res = $res->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_name', 'goods_thumb', 'goods_img');
            },
            'getGiftGardType' => function ($query) {
                $query->select('gift_id', 'gift_name');
            }
        ]);

        $res = $res->orderBy('user_time', 'desc')
            ->offset($begin)
            ->limit($size);
        $res = $this->baseRepository->getToArrayGet($res);

        $list = [];
        if ($res) {
            foreach ($res as $key => $val) {
                $val['gift_name'] = $val['get_gift_gard_type'] ? $val['get_gift_gard_type']['gift_name'] : '';
                $val['goods_name'] = $val['get_goods'] ? $val['get_goods']['goods_name'] : '';
                $val['goods_thumb'] = $this->dscRepository->getImagePath($val['get_goods']['goods_thumb']);
                $val['user_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', empty($val['user_time']) ? '' : $val['user_time']);
                unset($val['get_gift_gard_type']);
                unset($val['get_goods']);
                $list[] = $val;
            }
        }

        return $list;

    }

}
