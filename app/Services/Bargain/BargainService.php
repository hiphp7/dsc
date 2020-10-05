<?php

namespace App\Services\Bargain;

use App\Models\ActivityGoodsAttr;
use App\Models\BargainGoods;
use App\Models\BargainStatistics;
use App\Models\BargainStatisticsLog;
use App\Models\Cart;
use App\Models\Goods;
use App\Models\GoodsAttr;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Ads\AdsService;
use App\Services\Goods\GoodsProdutsService;


/**
 * 砍价
 * Class CrowdFund
 * @package App\Services
 */
class BargainService
{
    protected $timeRepository;
    protected $config;
    protected $dscRepository;
    protected $baseRepository;
    protected $goodsProdutsService;

    public function __construct(
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        GoodsProdutsService $goodsProdutsService
    )
    {
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->baseRepository = $baseRepository;
        $this->goodsProdutsService = $goodsProdutsService;
    }


    /**
     * 砍价首页广告位
     *
     * @access  public
     * @param integer $position_id 广告位ID
     * @param integer $num 数量
     */
    public function bargainPositions($position_id = 0, $num = 3)
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
     * 查询砍价商品列表
     * @param string $type
     * @param integer $size
     * @return mixed
     */
    public function GoodsList($page = 1, $size = 10, $type = '')
    {
        $time = $this->timeRepository->getGmTime();
        $begin = ($page - 1) * $size;

        $goods = BargainGoods::where('status', 0)
            ->where('is_audit', 2)
            ->where('is_delete', 0)
            ->where("start_time", '<=', $time)
            ->where("end_time", '>=', $time);

        $goods = $goods->whereHas('getGoods', function ($query) {
            $query->where('is_alone_sale', 1)
                ->where('is_on_sale', 1)
                ->where('is_delete', 0)
                ->where('review_status', '>', 2);
        });

        $goods = $goods->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_name', 'shop_price', 'market_price', 'goods_thumb');
            },
            'getBargainTargetPrice' => function ($query) {
                $query->select('bargain_id', 'target_price');
            }

        ]);

        // 类型
        if ($type == 'is_hot') {
            $goods = $goods->where('is_hot', 1);
        }

        $goods = $goods->orderBy('id', 'desc')
            ->offset($begin)
            ->limit($size);

        $list = $this->baseRepository->getToArrayGet($goods);

        if ($list) {
            foreach ($list as $key => $val) {
                $val = $val['get_goods'] ? array_merge($val, $val['get_goods']) : $val;
                $list[$key]['goods_name'] = $val['goods_name'] ?? '';
                $list[$key]['goods_thumb'] = $this->dscRepository->getImagePath($val['goods_thumb']);
                $list[$key]['shop_price'] = $this->dscRepository->getPriceFormat($val['shop_price'], false);
                $list[$key]['target_price'] = $this->dscRepository->getPriceFormat($val['target_price'], false);
                if ($val['get_bargain_target_price']) {//获取砍价商品属性最低价格
                    $target_price = $this->baseRepository->getArrayMin($val['get_bargain_target_price'], 'target_price');
                    if ($target_price) {
                        $list[$key]['target_price'] = $this->dscRepository->getPriceFormat($target_price, false);
                    }
                }

            }
        }

        return $list;
    }

    /**
     * 商品详情
     * @param $bargain_id
     * @return mixed
     */
    public function goodsInfo($bargain_id = 0)
    {
        $goods = BargainGoods::where('id', $bargain_id)
            ->where('status', 0)
            ->where('is_audit', 2)
            ->where('is_delete', 0);

        $goods = $goods->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'user_id', 'goods_sn', 'goods_name', 'is_real', 'is_shipping', 'is_on_sale', 'shop_price', 'market_price', 'goods_thumb', 'goods_img', 'goods_number', 'goods_desc', 'desc_mobile', 'goods_type', 'goods_brief', 'model_attr', 'review_status', 'freight', 'tid', 'shipping_fee');
            },
            'getBargainTargetPrice' => function ($query) {
                $query->select('bargain_id', 'target_price');
            }
        ]);

        $goods = $goods->whereHas('getGoods', function ($query) {
            $query->where('is_alone_sale', 1)
                ->where('is_on_sale', 1)
                ->where('is_delete', 0)
                ->where('review_status', '>', 2);
        });

        $info = $this->baseRepository->getToArrayFirst($goods);

        if ($info) {
            $info = $info['get_goods'] ? array_merge($info, $info['get_goods']) : $info;
            //获取砍价商品属性最低价格
            if ($info['get_bargain_target_price']) {
                $target_price = $this->baseRepository->getArrayMin($info['get_bargain_target_price'], 'target_price');
                if ($target_price) {
                    $info['target_price'] = $target_price;
                }
            }

            // 商品详情图 PC
            if (empty($info['desc_mobile']) && !empty($info['goods_desc'])) {
                $desc_preg = $this->dscRepository->descImagesPreg($info['goods_desc']);
                $info['goods_desc'] = $desc_preg['goods_desc'];
            }

            if (!empty($info['desc_mobile'])) {
                // 处理手机端商品详情 图片（手机相册图） data/gallery_album/
                $desc_preg = $this->dscRepository->descImagesPreg($info['desc_mobile'], 'desc_mobile', 1);
                $info['desc_mobile'] = $desc_preg['desc_mobile'];
                $info['goods_desc'] = $desc_preg['desc_mobile'];
            }

            unset($info['get_goods']);
            unset($info['get_bargain_target_price']);

        }

        return $info;

    }

    /**
     * 验证是否参与当前活动
     * @param $bargain_id 砍价活动id
     * @param $user_id  会员id
     * @param $bs_id  参与砍价id
     * @return mixed
     */
    public function isAddBargain($bargain_id = 0, $user_id, $bs_id = 0)
    {
        $info = BargainStatisticsLog::where('bargain_id', $bargain_id)
            ->where('user_id', $user_id)
            ->where('status', 0);

        if ($bs_id > 0) {
            $info = $info->where('id', $bs_id);
        }

        $tatistics_log = $this->baseRepository->getToArrayFirst($info);

        return $tatistics_log;

    }

    /**
     * 获取参与砍价信息
     * @param $bs_id  参与砍价id
     * @return mixed
     */
    public function bargainStatisticsLog($bs_id = 0)
    {
        $info = BargainStatisticsLog::where('id', $bs_id)
            ->where('status', 0);

        $tatistics_log = $this->baseRepository->getToArrayFirst($info);

        return $tatistics_log;

    }

    /**
     * 验证已砍价信息
     * @param $bs_id 参与砍价id
     * @param $user_id
     * @return mixed
     */
    public function isBargainJoin($bs_id = 0, $user_id)
    {
        $bargain_info = BargainStatistics::where('bs_id', $bs_id)
            ->where('user_id', $user_id);

        $bargain_info = $this->baseRepository->getToArrayFirst($bargain_info);

        return $bargain_info;
    }


    /**
     * 排行榜
     * @param string $type
     * @param integer $size
     * @return mixed
     */
    public function getBargainRanking($bargain_id = 0)
    {
        $res = BargainStatistics::selectRaw('bs_id, SUM(subtract_price) AS money');

        $res = $res->whereHas('getBargainStatisticsLog', function ($query) use ($bargain_id) {
            $query->where('bargain_id', $bargain_id);
        });

        $res = $res->with([
            'getBargainStatisticsLog' => function ($query) {
                $query = $query->select('id', 'user_id');
                $query->with([
                    'getUsers' => function ($query) {
                        $query->select('user_id', 'user_name', 'nick_name', 'user_picture');
                    }
                ]);
            }
        ]);

        $res = $res->groupBy('bs_id')
            ->orderBy('money', 'desc');

        $list = $this->baseRepository->getToArrayGet($res);

        $info = [];
        if ($list) {
            foreach ($list as $key => $val) {
                $val = array_merge($val, $val['get_bargain_statistics_log']);
                $val = array_merge($val, $val['get_users']);
                $info[$key]['user_id'] = $val['user_id'];
                $info[$key]['rank'] = $key + 1;
                $info[$key]['money'] = $this->dscRepository->getPriceFormat($val['money']);
                $user_name = $val['nick_name'] ? $val['nick_name'] : $val['user_name'];
                $info[$key]['user_name'] = setAnonymous($user_name);
                $info[$key]['user_picture'] = $this->dscRepository->getImagePath($val['user_picture']);
                unset($val['get_bargain_statistics_log']);
                unset($val['get_users']);

            }
        }

        return $info;
    }


    /**
     * 参与活动记录
     * @param $bargain_id
     * @return mixed
     */
    public function bargainLog($bs_id = 0)
    {
        $log = BargainStatisticsLog::where('id', $bs_id);

        $bargain_log = $this->baseRepository->getToArrayFirst($log);

        return $bargain_log;
    }


    /**
     * 亲友帮
     * @param string $type
     * @param integer $size
     * @return mixed
     */
    public function getBargainStatistics($bs_id = 0)
    {
        $res = BargainStatistics::where('bs_id', $bs_id);
        $res = $res->with([
            'getUsers' => function ($query) {
                $query->select('user_id', 'user_name', 'nick_name', 'user_picture');
            }
        ]);

        $res = $res->orderBy('add_time', 'desc')
            ->get();

        $res = $res ? $res->toArray() : [];

        $timeFormat = $this->config['time_format'];
        $list = [];
        if ($res) {
            foreach ($res as $key => $val) {
                $val = array_merge($val, $val['get_users']);
                $list[$key]['subtract_price'] = $this->dscRepository->getPriceFormat($val['subtract_price'], false);
                $list[$key]['add_time'] = $this->timeRepository->getLocalDate($timeFormat, $val['add_time']);
                //用户名、头像
                $user_name = !empty($val['nick_name']) ? $val['nick_name'] : $val['user_name'];
                $list[$key]['user_name'] = setAnonymous($user_name);
                $list[$key]['user_picture'] = $this->dscRepository->getImagePath($val['user_picture']);
            }
        }

        return $list;
    }


    /**
     * 取得商品最终使用价格
     *
     * @param string $goods_id 商品编号
     * @param string $goods_num 购买数量
     * @param boolean $is_spec_price 是否加入规格价格
     * @param mix $property 规格ID的数组或者逗号分隔的字符串
     *
     * @return  商品最终购买价格
     */
    public function getFinalPrice($goods_id, $goods_num = '1', $is_spec_price = false, $property = [], $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        $final_price = 0; //商品最终购买价格
        $spec_price = 0;

        //如果需要加入规格价格
        if ($is_spec_price) {
            if (!empty($property)) {
                $spec_price = $this->goodsProdutsService->goodsPropertyPrice($goods_id, $property, $warehouse_id, $area_id, $area_city);
            }
        }

        $goods = Goods::where('goods_id', $goods_id)->first();
        $goods = $goods ? $goods->toArray() : [];

        //如果需要加入规格价格
        if ($is_spec_price) {
            if ($this->config['add_shop_price'] == 1) {
                $final_price = $goods['shop_price'];
                $final_price += $spec_price;
            }
        }

        if ($this->config['add_shop_price'] == 0) {
            //返回商品属性价
            $final_price = $spec_price;
        }

        //返回商品最终购买价格
        return $final_price;
    }

    /**
     * 获得指定商品属性活动最低价
     *
     * @param int $bargain_id 砍价活动id
     * @param int $goods_id 商品编号
     * @param array $attr_id 规格ID的数组或者逗号分隔的字符串
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param int $model_attr
     * @return int
     */
    public function bargainTargetPrice($bargain_id = 0, $goods_id = 0, $attr_id = [], $warehouse_id = 0, $area_id = 0, $area_city = 0, $model_attr = 0)
    {
        if (empty($attr_id)) {
            $attr_id = [];
        } else {
            //去掉复选属性 start
            $attr_arr = $this->baseRepository->getExplode($attr_id);
            foreach ($attr_arr as $key => $val) {

                $goods_attr_id = $this->baseRepository->getExplode($val);
                $res = GoodsAttr::where('goods_id', $goods_id)
                    ->whereIn('goods_attr_id', $goods_attr_id);

                $res = $res->with([
                    'getGoodsAttribute'
                ]);

                $res = $this->baseRepository->getToArrayFirst($res);
                $attr_type = $res['get_goods_attribute']['attr_type'] ?? 0;

                if (($attr_type == 0 || $attr_type == 2) && $attr_arr[$key]) {
                    unset($attr_arr[$key]);
                }
            }
            $attr_id = $attr_arr;
            //去掉复选属性 end
        }

        //商品属性价格模式,货品模式
        if ($this->config['goods_attr_price'] == 1) {
            if ($model_attr == 1) {
                $product_price = ProductsWarehouse::select('product_id', 'product_price', 'product_promote_price', 'product_market_price')
                    ->where('goods_id', $goods_id)
                    ->where('warehouse_id', $warehouse_id);
            } elseif ($model_attr == 2) {
                $product_price = ProductsArea::select('product_id', 'product_price', 'product_promote_price', 'product_market_price')
                    ->where('goods_id', $goods_id)
                    ->where('area_id', $area_id);

                if ($this->config['area_pricetype'] == 1) {
                    $product_price = $product_price->where('city_id', $area_city);
                }

            } else {
                $product_price = Products::select('product_id', 'product_price', 'product_promote_price', 'product_market_price')
                    ->where('goods_id', $goods_id);
            }

            //获取货品信息
            if ($attr_id) {
                foreach ($attr_id as $key => $val) {
                    $product_price = $product_price->whereRaw("FIND_IN_SET('$val', REPLACE(goods_attr, '|', ','))");
                }
            }
            $product_price = $this->baseRepository->getToArrayFirst($product_price);

            // 获取砍价属性底价
            if ($product_price) {
                $res = ActivityGoodsAttr::where('bargain_id', $bargain_id)
                    ->where('goods_id', $goods_id)
                    ->where('product_id', $product_price['product_id']);
                $res = $this->baseRepository->getToArrayFirst($res);
                return $res['target_price'] ?? 0;
            } else {
                return 0;
            }
        }
    }


    /**
     * 已砍价总额
     * @param $bs_id
     */
    public function subtractPriceSum($bs_id = 0)
    {
        $subtract_price = BargainStatistics::where('bs_id', $bs_id)
            ->sum('subtract_price');
        if ($subtract_price === null) {
            return 0;
        }

        return $subtract_price;
    }


    /**
     * 我要参与
     * @param $params
     * @return bool
     */
    public function addBargain($params)
    {
        $add = BargainStatisticsLog::insertGetId($params);
        if ($add) {
            $result['msg'] = lang('bargain.bargain_participate_success');
            $result['bs_id'] = $add;
            $result['error'] = 0;
            return $result;
        }
    }


    /**
     * 更新活动参与人数
     * @param $params
     * @return bool
     */
    public function updateBargain($bargain_id = 0)
    {
        BargainGoods::where('id', $bargain_id)->increment('total_num', 1);
    }

    /**
     * 验证参与砍价次数
     * @param $bargain_id
     * @return mixed
     */
    public function bargainLogNumber($bs_id = 0, $user_id = 0)
    {
        return BargainStatistics::where('bs_id', $bs_id)
            ->where('user_id', $user_id)
            ->count();
    }

    /**
     * 去砍价
     * @param $params
     * @return bool
     */
    public function addBargainStatistics($params)
    {
        return $add = BargainStatistics::insertGetId($params);
    }

    /**
     * 更新参与砍价人数 和砍后最终购买价
     * @param $params
     * @return bool
     */
    public function updateBargainStatistics($bs_id = 0, $count_num = 0, $final_price = 0)
    {
        BargainStatisticsLog::where('id', $bs_id)
            ->update(['count_num' => $count_num, 'final_price' => $final_price]);
    }


    /**
     *  添加到购物车
     *
     * @access  public
     * @param array $address
     * @return  bool
     */
    public function addGoodsToCart($arguments)
    {
        $address_id = Cart::insertGetId($arguments);

        return $address_id;
    }


    /**
     * 修改砍价活动状态
     * @param $params
     * @return bool
     */
    public function updateStatus($bs_id = 0)
    {
        BargainStatisticsLog::where('id', $bs_id)
            ->update(['status' => 1]);
    }

    /**
     * 我de砍价列表
     * @param $user_id
     * @param int $page
     * @param int $size
     * @return mixed
     */
    public function myBargain($user_id = 0, $page = 1, $size = 10)
    {
        $begin = ($page - 1) * $size;

        $res = BargainStatisticsLog::where('user_id', $user_id);

        $res = $res->whereHas('getBargainGoods', function ($query) {
            $query->whereHas('getGoods', function ($query) {
                $query->where('is_alone_sale', 1)
                    ->where('is_on_sale', 1)
                    ->where('is_delete', 0)
                    ->where('review_status', '>', 2);
            });
        });

        $res = $res->with([
            'getBargainGoods' => function ($query) {
                $query = $query->select('id', 'goods_id', 'target_price', 'total_num');
                $query->with([
                    'getGoods' => function ($query) {
                        $query->select('goods_id', 'goods_name', 'shop_price', 'goods_thumb', 'goods_img');
                    },
                    'getBargainTargetPrice' => function ($query) {
                        $query->select('bargain_id', 'target_price');
                    }
                ]);
            }

        ]);

        $res = $res->orderby('add_time', 'desc')
            ->offset($begin)
            ->limit($size)
            ->get();

        $res = $res ? $res->toArray() : [];

        $list = [];
        if ($res) {
            foreach ($res as $key => $v) {
                $v = array_merge($v, $v['get_bargain_goods']);
                $v = array_merge($v, $v['get_goods']);

                $list[$key]['id'] = $v['bargain_id'];
                $list[$key]['goods_name'] = $v['goods_name'];
                $list[$key]['goods_thumb'] = $this->dscRepository->getImagePath($v['goods_thumb']);
                $list[$key]['goods_img'] = $this->dscRepository->getImagePath($v['goods_img']);
                $list[$key]['shop_price'] = $this->dscRepository->getPriceFormat($v['shop_price'], false);
                $list[$key]['target_price'] = $this->dscRepository->getPriceFormat($v['target_price'], false);

                if ($v['get_bargain_target_price']) {//获取砍价商品属性最低价格
                    $target_price = $this->baseRepository->getArrayMin($v['get_bargain_target_price'], 'target_price');
                    if ($target_price) {
                        $list[$key]['target_price'] = $this->dscRepository->getPriceFormat($target_price, false);
                    }
                }

                $list[$key]['total_num'] = $v['total_num'];
            }
        }

        return $list;
    }
}
