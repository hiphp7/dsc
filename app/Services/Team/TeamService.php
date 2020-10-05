<?php

namespace App\Services\Team;

use App\Models\Cart;
use App\Models\CollectGoods;
use App\Models\OrderAction;
use App\Models\OrderInfo;
use App\Models\TeamCategory;
use App\Models\TeamGoods;
use App\Models\TeamLog;
use App\Models\TouchAdPosition;
use App\Models\UserOrderNum;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Ads\AdsService;
use App\Services\Goods\GoodsProdutsService;
use App\Services\Wechat\WechatService;
use Illuminate\Support\Facades\DB;

/**
 * 拼团
 * Class CrowdFund
 * @package App\Services
 */
class TeamService
{
    protected $timeRepository;
    protected $config;
    protected $wechatService;
    protected $dscRepository;
    protected $baseRepository;
    protected $sessionRepository;
    protected $goodsProdutsService;

    public function __construct(
        TimeRepository $timeRepository,
        WechatService $wechatService,
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        SessionRepository $sessionRepository,
        GoodsProdutsService $goodsProdutsService
    )
    {
        $this->timeRepository = $timeRepository;
        $this->wechatService = $wechatService;
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
        $this->sessionRepository = $sessionRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->goodsProdutsService = $goodsProdutsService;
    }


    /**
     * 拼团首页广告位
     *
     * @param int $position_id
     * @param int $num
     * @return array
     */
    public function teamPositions($position_id = 0, $num = 10)
    {
        $banner_ads = app(AdsService::class)->getTouchAds($position_id, $num);

        $ads = [];
        if ($banner_ads) {
            foreach ($banner_ads as $row) {
                $ads[] = [
                    'pic' => $row['ad_code'],
                    'adsense_id' => $row['ad_id'],
                    'link' => $row['ad_link'],
                ];
            }
        }

        return $ads;
    }

    /**
     * 拼团频道页面广告位
     *
     * @param int $tc_id 拼团频道ID
     * @param string $type 广告位类型
     * @param int $num
     * @return array
     */
    public function categoriesAdsense($tc_id = 0, $type = 'banner', $num = 10)
    {
        $position = TouchAdPosition::where('tc_id', $tc_id)->where('tc_type', $type)->first();
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
     * 拼团主频道，获取子频道列表
     *
     * @param int $tc_id 拼团频道ID
     * @return array
     */
    public function teamCategories($tc_id = 0)
    {
        $team = TeamCategory::select('*');
        if ($tc_id > 0) {
            $team->where('parent_id', $tc_id);
        } else {
            $team->where('parent_id', 0);
        }
        $team = $team->where('status', 1)
            ->orderby('id', 'asc');

        $team_list = $this->baseRepository->getToArrayGet($team);

        $list = [];
        if ($team_list) {
            foreach ($team_list as $key => $val) {
                $list[$key]['tc_id'] = $val['id'];
                $list[$key]['name'] = $val['name'];
                $list[$key]['tc_img'] = $this->dscRepository->getImagePath($val['tc_img']);
            }
        }

        return $list;
    }

    /**
     * 拼团频道信息
     *
     * @param int $tc_id 拼团频道ID
     * @return array
     */
    public function teamCategoriesInfo($tc_id = 0)
    {
        return TeamCategory::where('id', $tc_id)->where('status', 1)->value('name');
    }

    /**
     * 获取随机用户信息
     *
     * @param int $user_id
     * @return array
     */
    public function virtualOrder($user_id = 0)
    {
        $list = [];

        if ($this->config['virtual_order'] == 1) {
            $info = Users::where('user_id', '<>', $user_id)
                ->orderBy(DB::raw('RAND()'))
                ->take(20);

            $info = $this->baseRepository->getToArrayGet($info);

            if ($info) {
                foreach ($info as $key => $value) {
                    $list[$key]['user_id'] = $value['user_id'];
                    $user_name = !empty($value['nick_name']) ? $value['nick_name'] : $value['user_name'];
                    $list[$key]['user_name'] = setAnonymous($user_name);
                    $list[$key]['user_picture'] = $this->dscRepository->getImagePath($value['user_picture']);
                    //随机秒数
                    $list[$key]['seconds'] = rand(1, 8) . "秒前";
                }
            }
        }

        return $list;
    }


    /**
     * 拼团首页商品列表,频道商品列表
     *
     * @param int $tc_id
     * @param int $page
     * @param int $size
     * @return array
     */
    public function getGoods($tc_id = 0, $page = 1, $size = 10)
    {
        $begin = ($page - 1) * $size;
        $type = [];
        if ($tc_id > 0) {
            $team_categories_child = $this->teamCategories($tc_id);  //获取拼团主频道

            if (!empty($team_categories_child)) {
                foreach ($team_categories_child as $key) {
                    $one_id[] = $key['tc_id'];
                }
                $type = $one_id;
            }
            $type[] = $tc_id;
        }
        $goods = TeamGoods::where('is_team', 1)
            ->where('is_audit', 2)
            ->whereHas('getGoods', function ($query) {
                $query->where('is_alone_sale', 1)
                    ->where('is_on_sale', 1)
                    ->where('is_delete', 0)
                    ->where('review_status', '>', 2);
            });

        $goods = $goods->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_name', 'shop_price', 'goods_number', 'goods_thumb', 'sales_volume');
            }
        ]);

        if (!empty($type)) {
            $goods = $goods->whereIn('tc_id', $type);
        }
        $goods = $goods->orderby('id', 'desc')
            ->offset($begin)
            ->limit($size);

        $team_list = $this->baseRepository->getToArrayGet($goods);
        $list = [];
        if ($team_list) {
            foreach ($team_list as $key => $val) {
                $val = $val['get_goods'] ? array_merge($val, $val['get_goods']) : $val;
                $list[$key]['id'] = $val['id'];
                $list[$key]['goods_id'] = $val['goods_id'];
                $list[$key]['goods_name'] = $val['goods_name'];
                $list[$key]['shop_price'] = $this->dscRepository->getPriceFormat($val['shop_price']);
                $list[$key]['goods_thumb'] = $this->dscRepository->getImagePath($val['goods_thumb']);
                $list[$key]['team_price'] = $this->dscRepository->getPriceFormat($val['team_price']);
                $list[$key]['team_num'] = $val['team_num'];
                $list[$key]['limit_num'] = $val['limit_num'];
            }
        }

        return $list;
    }

    /**
     * 拼团首页商品列表,频道商品列表
     *
     * @param int $tc_id 拼团频道ID
     * @param string $keywords 关键字
     * @param int $sortKey 排序 goods_id last_update  sales_volume  team_price
     * @param string $sortVal ASC DESC
     * @param int $page
     * @param int $size
     * @return array
     */
    public function getGoodsList($tc_id = 0, $keywords = '', $sortKey = 0, $sortVal = '', $page = 1, $size = 10)
    {
        $goods = TeamGoods::from('team_goods as tg')
            ->select('g.goods_id', 'g.goods_name', 'g.shop_price', 'g.goods_number', 'g.sales_volume', 'g.goods_thumb', 'tg.id', 'tg.team_price', 'tg.team_num', 'tg.limit_num')
            ->leftjoin('goods as g', 'g.goods_id', '=', 'tg.goods_id');

        if ($tc_id > 0) {
            $goods->where('tg.tc_id', $tc_id);
        }
        // 关键词
        if (!empty($keywords)) {
            $goods->where('goods_name', 'like', "%{$keywords}%");
        }

        // 排序
        $sort = ['ASC', 'DESC'];

        switch ($sortKey) {
            // 默认
            case '0':
                $goods->orderby('g.goods_id', in_array($sortVal, $sort) ? $sortVal : 'ASC');
                break;
            // 新品
            case '1':
                $goods->orderby('g.last_update', in_array($sortVal, $sort) ? $sortVal : 'ASC');
                break;
            // 销量
            case '2':
                $goods->orderby('g.sales_volume', in_array($sortVal, $sort) ? $sortVal : 'ASC');
                break;
            // 价格
            case '3':
                $goods->orderby('tg.team_price', in_array($sortVal, $sort) ? $sortVal : 'ASC');
                break;
        }
        $begin = ($page - 1) * $size;
        $team_list = $goods->where('tg.is_team', 1)
            ->where('tg.is_audit', 2)
            ->where('g.is_on_sale', 1)
            ->where('g.is_alone_sale', 1)
            ->where('g.is_delete', 0)
            ->where('g.review_status', '>', 2)
            ->offset($begin)
            ->limit($size)
            ->get();

        $team_list = $team_list ? $team_list->toArray() : [];

        $list = [];
        if ($team_list) {
            foreach ($team_list as $key => $val) {
                $list[$key]['id'] = $val['id'];
                $list[$key]['goods_id'] = $val['goods_id'];
                $list[$key]['goods_name'] = $val['goods_name'];
                $list[$key]['shop_price'] = $this->dscRepository->getPriceFormat($val['shop_price']);
                $list[$key]['goods_thumb'] = $this->dscRepository->getImagePath($val['goods_thumb']);
                $list[$key]['team_price'] = $this->dscRepository->getPriceFormat($val['team_price']);
                $list[$key]['team_num'] = $val['team_num'];
                $list[$key]['limit_num'] = $val['limit_num'];
            }
        }

        return $list;
    }


    /**
     * 拼团排行商品列表
     *
     * @param int $status
     * @param int $page
     * @param int $size
     * @return array
     */
    public function teamRankingList($status = 0, $page = 1, $size = 10)
    {
        $goods = TeamGoods::from('team_goods as tg')
            ->select('g.goods_id', 'g.goods_name', 'g.shop_price', 'g.goods_number', 'g.sales_volume', 'g.goods_thumb', 'tg.id', 'tg.team_price', 'tg.team_num', 'tg.limit_num')
            ->leftjoin('goods as g', 'g.goods_id', '=', 'tg.goods_id');

        switch ($status) {
            // 热门
            case '0':
                $goods->orderby('tg.limit_num', 'DESC');
                break;
            // 新品
            case '1':
                $goods->orderby('g.add_time', 'DESC');
                break;
            // 优选
            case '2':
                $goods->where('g.is_hot', 1);
                break;
            case '3':
                $goods->where('g.is_best', 1);
                break;
        }
        $begin = ($page - 1) * $size;
        $team_list = $goods->where('tg.is_team', 1)
            ->where('tg.is_audit', 2)
            ->where('g.is_on_sale', 1)
            ->where('g.is_alone_sale', 1)
            ->where('g.is_delete', 0)
            ->where('g.review_status', '>', 2)
            ->offset($begin)
            ->limit($size)
            ->get();

        $team_list = $team_list ? $team_list->toArray() : [];

        $list = [];
        if ($team_list) {
            foreach ($team_list as $key => $val) {
                $list[$key]['key'] = $key + 1;
                $list[$key]['id'] = $val['id'];
                $list[$key]['goods_id'] = $val['goods_id'];
                $list[$key]['goods_name'] = $val['goods_name'];
                $list[$key]['shop_price'] = $this->dscRepository->getPriceFormat($val['shop_price']);
                $list[$key]['goods_thumb'] = $this->dscRepository->getImagePath($val['goods_thumb']);
                $list[$key]['team_price'] = $this->dscRepository->getPriceFormat($val['team_price']);
                $list[$key]['team_num'] = $val['team_num'];
                $list[$key]['limit_num'] = $val['limit_num'];
                $list[$key]['status'] = $status;
            }
        }

        return $list;
    }


    /**
     * 商品信息
     *
     * @param int $goods_id
     * @param int $user_id
     * @return array
     */
    public function goodsDetail($goods_id = 0, $user_id = 0)
    {
        $res = TeamGoods::where('goods_id', $goods_id)
            ->where('is_team', 1)
            ->whereHas('getGoods');

        $res = $res->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'user_id', 'goods_sn', 'goods_name', 'is_real', 'is_shipping', 'is_on_sale', 'shop_price', 'market_price', 'goods_thumb', 'goods_img', 'goods_number', 'sales_volume', 'goods_desc', 'desc_mobile', 'goods_type', 'goods_brief', 'model_attr', 'review_status', 'freight', 'tid', 'shipping_fee');
            }
        ]);

        $res = $res->first();

        $res = $res ? $res->toArray() : [];

        if (empty($res)) {
            return [];
        }

        $res = array_merge($res, $res['get_goods']);

        unset($res['get_goods']);

        // 商品详情图 PC
        if (empty($res['desc_mobile']) && !empty($res['goods_desc'])) {
            $desc_preg = $this->dscRepository->descImagesPreg($res['goods_desc']);
            $res['goods_desc'] = $desc_preg['goods_desc'];
        }

        if (!empty($res['desc_mobile'])) {
            // 处理手机端商品详情 图片（手机相册图） data/gallery_album/
            $desc_preg = $this->dscRepository->descImagesPreg($res['desc_mobile'], 'desc_mobile', 1);
            $res['desc_mobile'] = $desc_preg['desc_mobile'];
            $res['goods_desc'] = $desc_preg['desc_mobile'];
        }


        $res['goods_thumb'] = $this->dscRepository->getImagePath($res['goods_thumb']);
        $res['goods_img'] = $this->dscRepository->getImagePath($res['goods_img']);
        if ($user_id) {
            $res['cart_number'] = Cart::where('user_id', $user_id)->where('rec_type', 0)
                ->sum('goods_number');
        } else {
            $session_id = $this->sessionRepository->realCartMacIp();
            $res['cart_number'] = Cart::where('session_id', $session_id)->where('rec_type', 0)
                ->sum('goods_number');
        }

        return $res;
    }


    /**
     * 查找我的收藏商品
     *
     * @param $goodsId
     * @param $uid
     * @return array
     */
    public function findOne($goodsId, $uid)
    {
        $cg = CollectGoods::where('goods_id', $goodsId)
            ->where('user_id', $uid);

        $cg = $this->baseRepository->getToArrayFirst($cg);

        return $cg;
    }

    /**
     * 验证参团活动信息
     *
     * @param int $team_id
     * @return array
     */
    public function teamIsFailure($team_id = 0)
    {

        $team = TeamLog::where('team_id', $team_id);
        $team = $team->with([
            'getTeamGoods' => function ($query) {
                $query->select('id', 'goods_id', 'validity_time', 'team_price', 'team_num', 'limit_num', 'astrict_num',
                    'is_team');
                $query->with([
                    'getGoods' => function ($query) {
                        $query->select('goods_id', 'goods_name');
                    }
                ]);
            }
        ]);

        $team = $this->baseRepository->getToArrayFirst($team);

        if ($team) {
            $team = $team['get_team_goods'] ? array_merge($team, $team['get_team_goods']) : $team;
            if (isset($team['get_team_goods'])) {
                unset($team['get_team_goods']);
            }
            $team = $team['get_goods'] ? array_merge($team, $team['get_goods']) : $team;
            if (isset($team['get_goods'])) {
                unset($team['get_goods']);
            }
        }

        return $team;
    }


    /**
     * 获取该商品已成功开团信息
     *
     * @access  public
     * @param integer $goods_id
     * @return mixed
     */
    public function teamGoodsLog($goods_id = 0)
    {
        $time = $this->timeRepository->getGmTime();

        $team = TeamLog::where('goods_id', $goods_id)
            ->where('status', 0)
            ->where('is_show', 1);

        $team = $team->whereHas('getOrderInfo', function ($query) {
            $query->where('extension_code', 'team_buy')->where('team_parent_id', '>', 0)->where('pay_status', PS_PAYED);
        });
        $team = $team->whereHas('getTeamGoods', function ($query) use ($time) {
            $query->whereRaw("$time < (start_time + validity_time * 3600)")
                ->where('is_show', 1)
                ->where('status', 0)
                ->where('is_team', 1);
        });

        $team = $team->with([
            'getOrderInfo' => function ($query) {
                $query->select('team_id', 'order_id', 'user_id');
                $query->with([
                    'getUsers' => function ($query) {
                        $query->select('user_id', 'user_name', 'nick_name', 'user_picture');
                    }
                ]);
            }
        ]);
        $team = $team->with([
            'getTeamGoods' => function ($query) {
                $query->select('id', 'goods_id', 'validity_time', 'team_price', 'team_num', 'limit_num', 'astrict_num', 'is_team');
            }
        ]);

        $team = $team->orderby('start_time', 'desc');

        $list = $this->baseRepository->getToArrayGet($team);

        return $list;
    }


    /**
     * 统计拼团中数量
     */
    public function teamOrderNum($user_id = 0)
    {
        $time = $this->timeRepository->getGmTime();
        $res = OrderInfo::where('user_id', $user_id)
            ->where('extension_code', 'team_buy')
            ->where('order_status', '<>', 2);

        $res = $res->whereHas('getTeamLog', function ($query) use ($time) {
            $query->whereHas(
                'getTeamGoods', function ($query) use ($time) {
                $query->whereRaw("$time < (start_time + validity_time * 3600)")->where('is_team', 1)
                    ->where('status', 0);
            }
            );
        });

        return $res->count();
    }


    /**
     * 统计该拼团已参与人数
     * @access  public
     * @param integer $team_id 拼团开团id
     * @return mixed
     */
    public function surplusNum($team_id = 0)
    {
        $num = OrderInfo::where('team_id', $team_id)
            ->where('extension_code', 'team_buy');
        $num = $num->where(function ($query) {
            $query->where('pay_status', PS_PAYED);
            $query->orWhere('order_status', 4);
        });
        return $num->count();
    }

    /**
     * 验证当前团是已否参与
     * @access  public
     * @param integer $team_id 拼团开团id
     * @return mixed
     */
    public function isTeamOrderNum($user_id = 0, $team_id = 0)
    {
        return OrderInfo::where('user_id', $user_id)
            ->where('team_id', $team_id)
            ->where('pay_status', PS_PAYED)
            ->where('extension_code', 'team_buy')
            ->count();

    }


    /**
     * 验证是否已经参团
     * @access  public
     * @param integer $user_id 会员id
     * @param integer $team_id 拼团开团id
     * @return mixed
     */
    public function teamJoin($user_id, $team_id = 0)
    {
        return OrderInfo::select('*')
            ->where('team_id', $team_id)
            ->where('user_id', $user_id)
            ->where('extension_code', 'team_buy')
            ->count();
    }

    /**
     * 获取拼团新品
     * @param string $type
     * @param integer $size
     * @return mixed
     */
    public function teamNewGoods($type = 'is_new', $user_id = 0, $size = 10)
    {
        $where = [
            'user_id' => $user_id,
            'type' => $type
        ];

        $goods = TeamGoods::where('is_team', 1)
            ->where('is_audit', 2);

        $goods = $goods->whereHas('getGoods', function ($query) use ($where) {
            if ($where['type'] == 'is_new') {
                $query->where('is_new', 1);
            }
            $query->where('is_alone_sale', 1)
                ->where('is_on_sale', 1)
                ->where('is_delete', 0)
                ->where('review_status', '>', 2)
                ->where('user_id', $where);
        });

        $goods = $goods->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_name', 'shop_price', 'goods_number', 'sales_volume', 'goods_thumb');
            }
        ]);

        $goods = $goods->orderby('id', 'desc')
            ->limit($size);

        $team_list = $this->baseRepository->getToArrayGet($goods);
        $list = [];
        if ($team_list) {
            foreach ($team_list as $key => $val) {
                $val = $val['get_goods'] ? array_merge($val, $val['get_goods']) : $val;
                $list[$key]['goods_id'] = $val['goods_id'];
                $list[$key]['goods_thumb'] = $this->dscRepository->getImagePath($val['goods_thumb']);
                $list[$key]['shop_price'] = $this->dscRepository->getPriceFormat($val['shop_price'], true);
                $list[$key]['team_price'] = $this->dscRepository->getPriceFormat($val['team_price'], true);
                $list[$key]['goods_name'] = $val['goods_name'];
            }
        }

        return $list;

    }

    /**
     * 取得商品最终使用价格
     *
     * @param $goods_id 商品编号
     * @param string $goods_num 购买数量
     * @param bool $is_spec_price 是否加入规格价格
     * @param array $property 规格ID的数组或者逗号分隔的字符串
     * @return int|mixed
     */
    public function getFinalPrice($goods_id, $goods_num = '1', $is_spec_price = false, $property = [])
    {
        $final_price = 0; //商品最终购买价格
        $spec_price = 0;

        //如果需要加入规格价格
        if ($is_spec_price) {
            if (!empty($property)) {
                $spec_price = $this->goodsProdutsService->goodsPropertyPrice($goods_id, $property);
            }
        }

        //商品信息
        $goods = $this->goodsDetail($goods_id);

        //如果需要加入规格价格
        if ($is_spec_price) {
            if ($this->config['add_shop_price'] == 1) {
                $final_price = $goods['team_price'];
                $final_price += $spec_price;
            }
        }

        if ($this->config['add_shop_price'] == 0) {
            //返回商品属性价
            $final_price = $goods['team_price'];
        }

        //返回商品最终购买价格
        return $final_price;
    }


    /**
     * 添加到购物车
     * @param $arguments
     * @return mixed
     */
    public function addGoodsToCart($arguments)
    {
        /* 插入一条新记录 */
        $cart_id = Cart::insertGetId($arguments);
        return $cart_id;
    }


    /**
     * 获取拼团信息
     * @param int $team_id
     * @return array
     */
    public function teamInfo($team_id = 0)
    {

        $res = TeamLog::where('team_id', $team_id);

        $res = $res->whereHas(
            'getOrderInfo', function ($query) {
            $query->where('extension_code', 'team_buy')
                ->where('team_parent_id', '>', 0);
        }
        );

        $res = $res->with([
            'getOrderInfo' => function ($query) {
                $query = $query->select('order_id', 'user_id', 'team_parent_id', 'team_id');
                $query->with([
                    'getUsers' => function ($query) {
                        $query->select('user_id', 'user_name', 'nick_name', 'user_picture');
                    }
                ]);
            },
            'getTeamGoods' => function ($query) {
                $query->select('id', 'validity_time', 'team_num', 'team_price', 'is_team', 'team_desc');
            },
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_thumb', 'goods_img', 'goods_name');
            }
        ]);

        $res = $res->first();

        $team_info = $res ? $res->toArray() : [];

        if ($team_info) {
            $time = $this->timeRepository->getGmTime();
            $team_info = array_merge($team_info, $team_info['get_order_info']);
            $team_info = array_merge($team_info, $team_info['get_users']);
            $team_info = array_merge($team_info, $team_info['get_team_goods']);
            $team_info = array_merge($team_info, $team_info['get_goods']);

            // 用户名、头像
            $team_info['user_name'] = !empty($team_info['nick_name']) ? setAnonymous($team_info['nick_name']) : setAnonymous($team_info['user_name']);

            if (empty($team_info['user_picture'])) {
                $team_info['user_picture'] = asset('img/user_default.png');
            }

            $team_info['user_picture'] = $this->dscRepository->getImagePath($team_info['user_picture']);

            $team_info['goods_thumb'] = $this->dscRepository->getImagePath($team_info['goods_thumb']);
            $team_info['team_price'] = $this->dscRepository->getPriceFormat($team_info['team_price']);
            // 当前时间
            $team_info['current_time'] = $time;
            $end_time = $team_info['start_time'] + ($team_info['validity_time'] * 3600);//剩余时间
            $team_info['end_time'] = $end_time; // + (8 * 3600);
            $team_num = $this->surplusNum($team_info['team_id']);  //统计几人参团
            $team_info['surplus'] = $team_info['team_num'] - $team_num;//还差几人
            $team_info['bar'] = round($team_num * 100 / $team_info['team_num'], 0);//百分比

            if ($team_info['status'] != 1 && $time < $end_time && $team_info['is_team'] == 1) {//进行中
                $team_info['status'] = 0;
            } elseif (($team_info['status'] != 1 && $time > $end_time) || $team_info['is_team'] != 1) {//失败
                $team_info['status'] = 2;
            } elseif ($team_info['status'] = 1) {//成功
                $team_info['status'] = 1;
            }

            unset($team_info['get_goods']);
            unset($team_info['get_order_info']);
            unset($team_info['get_team_goods']);
            unset($team_info['get_users']);
        }

        return $team_info;

    }

    /**
     * 获取拼团团员信息
     * @param int $team_id
     * @return array
     */
    public function teamUserList($team_id = 0)
    {
        $list = OrderInfo::select('add_time', 'team_id', 'user_id', 'team_parent_id', 'team_user_id')
            ->where('team_id', $team_id)
            ->where('extension_code', 'team_buy');

        $list = $list->where(function ($query) {
            $query->where('pay_status', PS_PAYED)
                ->orWhere('order_status', '>', 4);
        });

        $list = $list->with([
            'getUsers' => function ($query) {
                $query->select('user_id', 'user_name', 'nick_name', 'user_picture');
            }
        ]);

        $list = $list->orderby('add_time', 'asc');

        $list = $this->baseRepository->getToArrayGet($list);

        return $list;
    }

    /**
     * 我的拼团
     * @param $user_id
     * @param int $status 0
     * @param int $page
     * @param int $size
     * @return array
     */
    public function teamUserOrder($user_id, $status = 0, $page = 1, $size = 10)
    {
        $begin = ($page - 1) * $size;
        $where = [
            'time' => $this->timeRepository->getGmTime(),
            'status' => $status,
        ];
        $goods = OrderInfo::where('user_id', $user_id)
            ->where('extension_code', 'team_buy');
        if ($status == 0) {
            $goods = $goods->where('order_status', '<>', 2);
        }

        $goods = $goods->with([
            'getTeamLog' => function ($query) {
                $query->select('team_id', 'goods_id', 't_id', 'start_time', 'status');
                $query->with([
                    'getTeamGoods' => function ($query) {
                        $query->select('id', 'validity_time', 'team_num', 'team_price', 'limit_num');
                    },
                    'getGoods' => function ($query) {
                        $query->select('goods_id', 'goods_name', 'goods_thumb', 'shop_price');
                    }
                ]);
            }
        ]);

        $goods = $goods->whereHas('getTeamLog', function ($query) use ($where) {
            $query->whereHas(
                'getTeamGoods', function ($query) use ($where) {
                $time = $where['time'];
                switch ($where['status']) {
                    case '0'://拼团中
                        $query->whereRaw("$time < (start_time + validity_time * 3600)")
                            ->where('is_show', 1)
                            ->where('status', 0)
                            ->where('is_team', 1);
                        break;
                    case '1'://成功团
                        $query->where('status', 1)->where('is_show', 1);
                        break;
                    case '2'://失败团
                        $query = $query->where('status', 0)
                            ->where('is_show', 1);
                        $query->where(function ($query) use ($time) {
                            $query->whereRaw("$time > (start_time + validity_time * 3600)")
                                ->orWhere('is_team', '<>', 1);
                        });
                        break;
                }
            }
            );
        });

        $goods = $goods->orderby('add_time', 'desc')
            ->offset($begin)
            ->limit($size);

        $team_list = $this->baseRepository->getToArrayGet($goods);

        $list = [];
        if ($team_list) {
            foreach ($team_list as $key => $val) {
                $val = $val['get_team_log'] ? array_merge($val, $val['get_team_log']) : $val;
                $val = $val['get_team_goods'] ? array_merge($val, $val['get_team_goods']) : $val;
                $val = $val['get_goods'] ? array_merge($val, $val['get_goods']) : $val;

                $list[$key]['id'] = $val['id'];
                $list[$key]['team_id'] = $val['team_id'];
                $list[$key]['goods_id'] = $val['goods_id'];
                $list[$key]['order_id'] = $val['order_id'];
                $list[$key]['order_sn'] = $val['order_sn'];
                $list[$key]['order_status'] = $val['order_status'];
                $list[$key]['pay_status'] = $val['pay_status'];
                $list[$key]['user_id'] = $val['user_id'];
                $list[$key]['goods_name'] = $val['goods_name'];
                $list[$key]['shop_price'] = $this->dscRepository->getPriceFormat($val['shop_price']);
                $list[$key]['goods_thumb'] = $this->dscRepository->getImagePath($val['goods_thumb']);
                $list[$key]['team_price'] = $this->dscRepository->getPriceFormat($val['team_price']);
                $list[$key]['team_num'] = $val['team_num'];
                $team_num = $this->surplusNum($val['team_id']);  //统计几人参团
                $list[$key]['limit_num'] = $team_num;
                $list[$key]['status'] = $status;  // 活动状态
                $list[$key]['is_pay'] = 0;
                if ($val['pay_status'] == 2) {
                    $list[$key]['is_pay'] = 1;
                }
            }
        }

        return $list;

    }


    /**
     * 获取过期未退款的订单
     * @return array
     */
    public function teamUserOrderRefund()
    {
        $time = $this->timeRepository->getGmTime();
        $goods = OrderInfo::where('pay_status', PS_PAYED)
            ->where('extension_code', 'team_buy');

        $goods = $goods->with([
            'getTeamLog' => function ($query) {
                $query->select('team_id', 'goods_id', 't_id', 'start_time', 'status');
                $query->with([
                    'getTeamGoods' => function ($query) {
                        $query->select('id', 'validity_time', 'team_num', 'team_price', 'limit_num');
                    },
                    'getGoods' => function ($query) {
                        $query->select('goods_id', 'goods_name', 'goods_thumb', 'shop_price');
                    }
                ]);
            }
        ]);

        $goods = $goods->whereHas('getTeamLog', function ($query) use ($time) {
            $query->whereHas(
                'getTeamGoods', function ($query) use ($time) {
                $query = $query->where('status', 0)
                    ->where('is_show', 1);
                $query->where(function ($query) use ($time) {
                    $query->whereRaw("$time > (start_time + validity_time * 3600)")
                        ->orWhere('is_team', '<>', 1);
                });
            }
            );
        });

        $goods = $goods->orderby('add_time', 'desc');

        $team_list = $this->baseRepository->getToArrayGet($goods);
        $list = [];
        if ($team_list) {
            foreach ($team_list as $key => $val) {
                $val = $val['get_team_log'] ? array_merge($val, $val['get_team_log']) : $val;
                $val = $val['get_team_goods'] ? array_merge($val, $val['get_team_goods']) : $val;
                $val = $val['get_goods'] ? array_merge($val, $val['get_goods']) : $val;
                $val['shop_price'] = $this->dscRepository->getPriceFormat($val['shop_price']);
                $val['goods_thumb'] = $this->dscRepository->getImagePath($val['goods_thumb']);
                $val['team_price'] = $this->dscRepository->getPriceFormat($val['team_price']);
                $team_num = $this->surplusNum($val['team_id']);  //统计几人参团
                $val['limit_num'] = $team_num;

                $list[] = $val;

            }
        }

        return $list;
    }


    /**
     * 插入开团活动信息
     * @param $params
     * @return bool
     */
    public function addTeamLog($params)
    {
        $log_id = TeamLog::insertGetId($params);
        return $log_id;
    }

    /**
     * 更改拼团状态
     * @param $params
     * @return bool
     */
    public function updateTeamLogStatua($team_id)
    {
        TeamLog::where('team_id', $team_id)
            ->update(['status' => 1]);
    }


    /**
     * 更改拼团参团数量
     * @param $params
     * @return bool
     */
    public function updateTeamLimitNum($id = 0, $goods_id = 0, $limit_num = 0)
    {
        TeamGoods::where('id', $id)
            ->where('goods_id', $goods_id)
            ->update(['limit_num' => $limit_num]);
    }

    /**
     * 付款更新拼团信息记录
     * @param int $team_id
     * @param int $team_parent_id
     * @param int $user_id
     */
    public function updateTeamInfo($team_id = 0, $team_parent_id = 0, $user_id = 0)
    {
        if ($team_id > 0) {
            // 拼团信息
            $res = $this->teamIsFailure($team_id);

            //验证拼团是否成功
            $team_count = OrderInfo::where('team_id', $team_id)
                ->where('pay_status', PS_PAYED)
                ->where('extension_code', 'team_buy')
                ->count();

            if ($team_count >= $res['team_num']) {
                // 更新团状态（1成功）
                TeamLog::where('team_id', $team_id)
                    ->update(['status' => 1]);

                $team_order = OrderInfo::select('order_sn', 'user_id')
                    ->where('team_id', $team_id)
                    ->where('pay_status', PS_PAYED)
                    ->where('extension_code', 'team_buy')
                    ->get();
                $team_order = $team_order ? $team_order->toArray() : [];
                if ($team_order) {
                    // 拼团成功提示会员等待发货
                    if (is_wechat_browser() && file_exists(MOBILE_WECHAT)) {
                        foreach ($team_order as $key => $vo) {

                            $pushData = [
                                'keyword1' => ['value' => $vo['order_sn'], 'color' => '#173177'],
                                'keyword2' => ['value' => $res['goods_name'], 'color' => '#173177']
                            ];
                            $url = dsc_url('/#/team/wait') . '?' . http_build_query(['team_id' => $team_id], '', '&');
                            $this->wechatService->push_template('OPENTM407456411', $pushData, $url, $vo['user_id']);
                        }
                    }
                    /* 更新会员订单信息 */
                    foreach ($team_order as $key => $vo) {
                        $dbRaw = [
                            'order_team_num' => "order_team_num - 1",
                        ];
                        $dbRaw = $this->baseRepository->getDbRaw($dbRaw);
                        UserOrderNum::where('user_id', $vo['user_id'])->update($dbRaw);
                    }
                }

            }

            //统计增加拼团人数
            TeamGoods::where('id', $res['id'])->where('goods_id', $res['goods_id'])->increment('limit_num', 1);

            if (is_wechat_browser() && file_exists(MOBILE_WECHAT)) {
                // 开团成功提醒
                if ($team_parent_id > 0) {
                    $pushData = [
                        'keyword1' => ['value' => $res['goods_name'], 'color' => '#173177'],
                        'keyword2' => ['value' => $res['team_price'] . lang('team.yuan'), 'color' => '#173177'],
                        'keyword3' => ['value' => $res['team_num'], 'color' => '#173177'],
                        'keyword4' => ['value' => lang('team.ordinary'), 'color' => '#173177'],
                        'keyword5' => ['value' => $res['validity_time'] . lang('team.hours'), 'color' => '#173177']
                    ];
                    $url = dsc_url('/#/team/wait') . '?' . http_build_query(['team_id' => $team_id], '', '&');
                    $this->wechatService->push_template('OPENTM407307456', $pushData, $url, $user_id);
                } else {
                    // 参团成功通知
                    $pushData = [
                        'first' => ['value' => lang('team.team_success')],
                        'keyword1' => ['value' => $res['goods_name'], 'color' => '#173177'],
                        'keyword2' => ['value' => $res['team_price'] . lang('team.yuan'), 'color' => '#173177'],
                        'keyword3' => ['value' => $res['validity_time'] . lang('team.hours'), 'color' => '#173177']
                    ];
                    $url = dsc_url('/#/team/wait') . '?' . http_build_query(['team_id' => $team_id], '', '&');
                    $this->wechatService->push_template('OPENTM400048581', $pushData, $url, $user_id);
                }
            }
        }
    }


    /**
     * 记录修改订单状态
     * @param int $order_id 订单id
     * @param int $action_user 操作人员
     * @param array $order 订单信息
     * @param string $action_note 变动说明
     * @return  void
     */
    public function orderActionChange($order_id = 0, $action_user = 'admin', $order = [], $action_note = '')
    {
        $time = $this->timeRepository->getGmTime();

        $action_log = [
            'order_id' => $order_id,
            'action_user' => $action_user,
            'order_status' => $order['order_status'],
            'shipping_status' => $order['shipping_status'],
            'pay_status' => $order['pay_status'],
            'action_note' => $action_note,
            'log_time' => $time
        ];

        OrderAction::insertGetId($action_log);

        /* 更新订单信息 */
        OrderInfo::where('order_id', $order_id)->update($order);
    }
}
