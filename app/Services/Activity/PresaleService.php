<?php

namespace App\Services\Activity;

use App\Models\CollectGoods;
use App\Models\Goods;
use App\Models\GoodsAttr;
use App\Models\OrderGoods;
use App\Models\PresaleActivity;
use App\Models\PresaleCat;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Goods\GoodsCommentService;

class PresaleService
{
    protected $config;
    protected $dscRepository;
    protected $timeRepository;
    protected $baseRepository;
    protected $goodsCommentService;

    public function __construct(
        DscRepository $dscRepository,
        TimeRepository $timeRepository,
        BaseRepository $baseRepository,
        GoodsCommentService $goodsCommentService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;
        $this->goodsCommentService = $goodsCommentService;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 取得某页的所有预售商品
     *
     * @param array $children
     * @param int $min
     * @param int $max
     * @param int $start_time
     * @param int $end_time
     * @param $sort
     * @param int $status
     * @param string $order
     * @return array
     */
    public function getPreGoods($children = [], $min = 0, $max = 0, $start_time = 0, $end_time = 0, $sort, $status = 0, $order = 'DESC', $keywords = '')
    {
        $now = $this->timeRepository->getGmTime();

        $res = Goods::where('is_on_sale', 0)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0);

        if ($min > 0) {
            $res = $res->where('shop_price', '>=', $min);
        }

        if ($max > 0) {
            $res = $res->where('shop_price', '<=', $max);
        }
        if ($keywords) {
            $res = $res->where('goods_name', 'like', '%' . $keywords . '%');
        }

        $wherePresale = [
            'status' => $status,
            'time' => $now,
            'children' => $children
        ];

        $res = $res->whereHas('getPresaleActivity', function ($query) use ($wherePresale) {
            //查询使用分类id不为0的条件
            $wherePresale['children'] = $wherePresale['children'] ?? [];
            if ($wherePresale['children']) {
                foreach ($wherePresale['children'] as $key => $v) {
                    if ($v == 0) {
                        unset($wherePresale['children'][$key]);
                    }
                }

                if ($wherePresale['children']) {
                    $query = $query->whereIn('cat_id', $wherePresale['children']);
                }
            }

            //1未开始，2进行中，3结束
            if ($wherePresale['status'] == 1) {
                $query = $query->where('start_time', '>', $wherePresale['time']);
            } elseif ($wherePresale['status'] == 2) {
                $query = $query->where('start_time', '<', $wherePresale['time'])->where('end_time', '>', $wherePresale['time']);
            } elseif ($wherePresale['status'] == 3) {
                $query = $query->where('end_time', '<', $wherePresale['time']);
            }

            $query->where('review_status', '>', 2);
        });


        $res = $res->with(['getPresaleActivity']);

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $row) {
                if (isset($row['get_presale_activity']) && $row['get_presale_activity']) {
                    $row = array_merge($row, $row['get_presale_activity']);
                }

                $res[$key] = $row;

                $res[$key]['goods_name'] = $row['goods_name'];
                $res[$key]['thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $res[$key]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $res[$key]['original_img'] = $this->dscRepository->getImagePath($row['original_img']);
                $res[$key]['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
                $res[$key]['url'] = route('presale', ['act' => 'view', 'id' => $row['act_id']]);

                $res[$key]['end_time_date'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $row['end_time']);
                $res[$key]['start_time_date'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $row['start_time']);

                $res[$key]['format_shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                $res[$key]['format_market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);

                if ($row['start_time'] >= $now) {
                    $res[$key]['no_start'] = 1;
                }
                if ($row['end_time'] <= $now) {
                    $res[$key]['already_over'] = 1;
                }
            }

            if ($order == 'DESC') {
                $res = collect($res)->sortByDesc($sort);
            } else {
                $res = collect($res)->sortBy($sort);
            }

            $res = $res->values()->all();
        }

        return $res;
    }

    /**
     * 预售商品详情页预约人数
     *
     * @param int $goods_id
     * @return mixed
     */
    public function getPreNum($goods_id = 0)
    {
        $res = PresaleActivity::select('act_id', 'deposit')->where('goods_id', $goods_id);
        $res = $this->baseRepository->getToArrayFirst($res);
        $row = $this->presaleStat($res['act_id'], $res['deposit']);
        $pre_num = $row['total_order'] ?? 0;

        return $pre_num;
    }

    /**
     * 获得预售分类商品
     *
     * @param int $cat_id
     * @return array|bool|\Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getPreCat($cat_id = 0)
    {
        $cat_res = cache('presalecat' . $cat_id);
        $cat_res = !is_null($cat_res) ? $cat_res : false;

        if ($cat_res === false) {
            $cat_res = PresaleCat::where('parent_id', $cat_id)->orderBy('sort_order')->get();
            $cat_res = $cat_res ? $cat_res->toArray() : [];

            cache()->forever('presalecat' . $cat_id, $cat_res);
        }

        if ($cat_res) {
            foreach ($cat_res as $key => $row) {
                $cat_res[$key]['goods'] = $this->getCatGoods($row['cat_id']);
                $cat_res[$key]['count_goods'] = count($cat_res[$key]['goods']);
                $cat_res[$key]['cat_url'] = route('presale', ['act' => 'category', 'cat_id' => $row['cat_id']]);
            }
        }

        return $cat_res;
    }

    // 获取分类下商品并进行分组
    public function getCatGoods($cat_id = 0)
    {
        $now = $this->timeRepository->getGmTime();

        $res = cache('presale_cat_goods_' . $cat_id);
        $res = !is_null($res) ? $res : false;

        if ($res === false) {
            $res = Goods::where('is_on_sale', 0)
                ->where('is_alone_sale', 1)
                ->where('is_delete', 0);

            $res = $res->whereHas('getPresaleActivity', function ($query) use ($cat_id, $now) {
                $query->where('cat_id', $cat_id)->where('review_status', 3)->where('start_time', '<=', $now)->where('end_time', '>=', $now);
            });

            $res = $res->with(['getPresaleActivity', 'getSellerShopInfo']);

            $res = $res->get();

            $res = $res ? $res->toArray() : [];

            cache()->forever('presale_cat_goods_' . $cat_id, $res);
        }

        if ($res) {
            foreach ($res as $key => $row) {
                if ($row['get_presale_activity']) {
                    $row = array_merge($row, $row['get_presale_activity']);
                }

                if ($row['get_seller_shop_info']) {
                    $row = array_merge($row, $row['get_seller_shop_info']);
                }

                $res[$key] = $row;

                //预约调用已产生订单的数量
                if (!empty($row['act_id'])) {
                    $stat = $this->presaleStat($row['act_id'], $row['deposit']);
                    $res[$key]['pre_num'] = $stat['total_order'] ?? 0;
                }

                $res[$key]['goods_name'] = $row['goods_name'];
                $res[$key]['thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $res[$key]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $res[$key]['original_img'] = $this->dscRepository->getImagePath($row['original_img']);
                $res[$key]['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
                $res[$key]['url'] = route('presale', ['act' => 'view', 'id' => $row['act_id']]);
                $res[$key]['goods_desc'] = $this->dscRepository->getContentImgReplace($row['goods_desc']);
                $res[$key]['shop_url'] = route('merchants_store', ['merchant_id' => $row['ru_id']]);
                $res[$key]['end_time_date'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $row['end_time']);
                $res[$key]['start_time_date'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $row['start_time']);

                $res[$key]['format_shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                $res[$key]['format_market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);

                if ($row['start_time'] >= $now) {
                    $res[$key]['no_start'] = 1;
                }
                if ($row['end_time'] <= $now) {
                    $res[$key]['already_over'] = 1;
                }
            }
        }

        return $res;
    }

    /**
     * 获取预售导航信息
     *
     * @param int $cat_id
     * @return array
     */
    public function getPreNav($cat_id = 0)
    {
        $res = PresaleCat::where('parent_id', $cat_id)->orderBy('sort_order')->take(7);
        $res = $res->get();
        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $key => $row) {
                $res[$key]['cat_id'] = $row['cat_id'];
                $res[$key]['cat_name'] = $row['cat_name'];
                $res[$key]['url'] = route('presale', ['act' => 'category', 'cat_id' => $row['cat_id']]);
            }
        }

        return $res;
    }

    /*
     * 查询商品是否预售
     * 是，则返回预售结束时间
     */
    public function getPresaleTime($goods_id)
    {
        $res = PresaleActivity::select(['act_id', 'pay_end_time'])->where('goods_id', $goods_id)->where('review_status', 3)->first();

        $res = $res ? $res->toArray() : [];

        if ($res && $res['pay_end_time']) {
            $res['pay_end_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $res['pay_end_time']);

            if ($res['pay_end_time']) {
                $pay_end_time = explode(" ", $res['pay_end_time']);
                $atthe = explode(":", $pay_end_time[1]);
                $res['str_time'] = $pay_end_time[0] . " " . $atthe[0] . ":" . $atthe[1];
            } else {
                $res['str_time'] = $res['pay_end_time'];
            }
        }

        return $res;
    }

    /**
     * 获取商品ajax属性是否都选中
     *
     * @param $goods_id
     * @param $goods_attr
     * @param $goods_attr_id
     * @return array
     */
    public function getGoodsAttrAjax($goods_id, $goods_attr, $goods_attr_id)
    {
        $arr = [];
        if ($goods_attr) {
            $goods_attr = $goods_attr && !is_array($goods_attr) ? explode(",", $goods_attr) : $goods_attr;

            $res = GoodsAttr::select(['goods_attr_id', 'attr_id', 'attr_value'])->where('goods_id', $goods_id);

            $res = $res->whereIn('attr_id', $goods_attr);

            if ($goods_attr_id) {
                $goods_attr_id = $goods_attr_id && !is_array($goods_attr_id) ? explode(",", $goods_attr_id) : $goods_attr_id;

                $res = $res->whereIn('goods_attr_id', $goods_attr_id);
            }

            $res = $res->whereHas('getGoodsAttribute', function ($query) {
                $query->where('attr_type', '>', 0);
            });

            $res = $res->with([
                'getGoodsAttribute' => function ($query) {
                    $query->select('attr_id', 'sort_order');
                }
            ]);

            $res = $res->get();

            $res = $res ? $res->toArray() : [];

            if ($res) {
                foreach ($res as $key => $row) {
                    $attribute = $row['get_goods_attribute'];

                    $res[$key]['sort_order'] = $attribute['sort_order'];
                    $res[$key]['attr_id'] = $attribute['attr_id'];
                }

                $res = $this->baseRepository->getSortBy($res, 'sort_order');

                $arr['attr_id'] = collect($res)->pluck('attr_id')->all();

                foreach ($res as $key => $row) {
                    $arr[$row['attr_id']][$row['goods_attr_id']] = $row;
                }
            }
        }

        return $arr;
    }

    /*
     * 相关分类
     */
    public function getGoodsRelatedCat($cat_id)
    {
        $parent_id = PresaleCat::where('cat_id', $cat_id)->value('parent_id');

        $res = PresaleCat::where('parent_id', $parent_id)->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $key => $row) {
                $res[$key]['cat_id'] = $row['cat_id'];
                $res[$key]['cat_name'] = $row['cat_name'];
                $res[$key]['url'] = route('presale', ['act' => 'category', 'cat_id' => $row['cat_id']]);
            }
        }

        return $res;
    }

    //分类（新品、抢�    �订）
    public function getPreCategory($act = 'new', $status = 0, $parent_id = 0)
    {
        $res = read_static_cache('pre_category_' . $act . $status);
        if ($res === false) {
            $res = PresaleCat::where('parent_id', $parent_id)->orderBy('sort_order')->get();
            $res = $res ? $res->toArray() : [];

            write_static_cache('pre_category_' . $act . $status, $res);
        }

        if ($res) {
            foreach ($res as $key => $row) {
                $res[$key]['cat_id'] = $row['cat_id'];
                $res[$key]['cat_name'] = $row['cat_name'];
                $res[$key]['url'] = route('presale', ['act' => $act, 'cat_id' => $row['cat_id'], 'status' => $status]);
            }
        }

        return $res;
    }

    //预售链接
    public function getPresaleUrl($act, $cat_id, $status, $cat_name)
    {
        return $this->dscRepository->buildUri('presale', ['act' => $act, 'cid' => $cat_id, 'status' => $status], $cat_name);
    }

    /**
     * 多维数组转一维数组【分类】
     *
     * @param int $parent_id
     * @return array|bool|\Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getPresaleCatListChildren($parent_id = 0)
    {

        //顶级分类页分类显示
        $cache_name = 'get_presale_cat_children' . $parent_id;

        $cat_list = cache($cache_name);
        $cat_list = !is_null($cat_list) ? $cat_list : false;

        //将数据写入缓存文件
        if ($cat_list === false) {
            $cat_list = PresaleCat::getList($parent_id)
                // ->where('is_show', 1)
                ->orderByRaw('sort_order, cat_id desc')
                ->get();

            $cat_list = $cat_list ? $cat_list->toArray() : [];

            if ($cat_list) {
                $cat_list = $this->dscRepository->getCatVal($cat_list);

                $cat_list = collect($cat_list)->flatten()->all();

                $cat_list = !empty($parent_id) ? collect($cat_list)->prepend($parent_id)->all() : $cat_list;
            } else {
                $cat_list = [$parent_id];
            }

            cache()->forever($cache_name, $cat_list);
        }

        return $cat_list;
    }

    /**
     * 新品发布商品列表
     *
     * @param array $children
     * @param int $status
     * @return array
     */
    public function getNewGoodsList($children = [], $status = 0)
    {
        $res = Goods::where('is_on_sale', 0)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0);

        $now = $this->timeRepository->getGmTime();

        $wherePresale = [
            'status' => $status,
            'time' => $now,
            'children' => $children
        ];
        $res = $res->whereHas('getPresaleActivity', function ($query) use ($wherePresale) {
            $query = $query->whereIn('cat_id', $wherePresale['children']);

            //1未开始，2进行中，3结束
            if ($wherePresale['status'] == 1) {
                $query = $query->where('start_time', '>', $wherePresale['time']);
            } elseif ($wherePresale['status'] == 2) {
                $query = $query->where('start_time', '<', $wherePresale['time'])->where('end_time', '>', $wherePresale['time']);
            } elseif ($wherePresale['status'] == 3) {
                $query = $query->where('end_time', '<', $wherePresale['time']);
            }

            $query->where('review_status', 3);
        });

        $res = $res->with(['getPresaleActivity']);

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $key => $row) {
                if ($row['get_presale_activity']) {
                    $row = array_merge($row, $row['get_presale_activity']);
                }

                $res[$key] = $row;

                $res[$key]['thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $res[$key]['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);

                $res[$key]['url'] = route('presale', ['act' => 'view', 'id' => $row['act_id']]);

                $res[$key]['end_time_date'] = $this->timeRepository->getLocalDate("Y-m-d H:i:s", $row['end_time']);
                $res[$key]['end_time_day'] = $this->timeRepository->getLocalDate("Y-m-d", $row['end_time']);

                $res[$key]['start_time_date'] = $this->timeRepository->getLocalDate("Y-m-d H:i:s", $row['start_time']);
                $res[$key]['start_time_day'] = $this->timeRepository->getLocalDate("Y-m-d", $row['start_time']);

                $res[$key]['format_shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                $res[$key]['format_market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);

                if ($row['start_time'] >= $now) {
                    $res[$key]['no_start'] = 1;
                }
                if ($row['end_time'] <= $now) {
                    $res[$key]['already_over'] = 1;
                }
            }

            $res = collect($res)->sortByDesc(['end_time', 'start_time']);
            $res = $res->values()->all();
        }

        $date_result = [];
        // 按日期重新排序数据分组
        if ($res) {
            $date_array = [];
            foreach ($res as $key => $row) {
                $date_array[$row['end_time_day']][] = $row;
            }

            // 把日期键值替换成数字0、1、2...,日期楼层下商品归类
            $date_result = [];

            if ($date_array) {
                foreach ($date_array as $key => $value) {
                    $date_result[]['goods'] = $value;
                }
            }

            if ($date_result) {
                foreach ($date_result as $key => $value) {
                    $date_result[$key]['end_time_day'] = $value['goods'][0]['end_time_day'];
                    $date_result[$key]['end_time_y'] = $this->timeRepository->getLocalDate('Y', $this->timeRepository->getGmstrTime($value['goods'][0]['end_time_day']));
                    $date_result[$key]['end_time_m'] = $this->timeRepository->getLocalDate('m', $this->timeRepository->getGmstrTime($value['goods'][0]['end_time_day']));
                    $date_result[$key]['end_time_d'] = $this->timeRepository->getLocalDate('d', $this->timeRepository->getGmstrTime($value['goods'][0]['end_time_day']));
                    $date_result[$key]['count_goods'] = count($value['goods']);
                }
            }
        }

        return $date_result;
    }

    /**
     * 取得预售活动信息
     *
     * @param $presale_id
     * @param int $current_num 本次购买数量（计算当前价时要加上的数量）
     * @param int $user_id
     * @param string $path
     * @return array
     */
    public function presaleInfo($presale_id, $current_num = 0, $user_id = 0, $path = '')
    {
        $presale_id = intval($presale_id);

        /* 取得预售活动信息 */
        $goodsOther = [
            'goods_id',
            'shop_price',
            'goods_thumb',
            'goods_img',
            'goods_name',
            'shop_price',
            'market_price',
            'sales_volume',
            'user_id',
            'goods_desc',
            'goods_product_tag',
            'cat_id',
            'xiangou_start_date',
            'xiangou_end_date',
            'xiangou_num',
            'is_xiangou',
            'brand_id'
        ];

        $res = PresaleActivity::where('act_id', $presale_id);

        if (empty($path)) {
            $res = $res->where('review_status', 3);
        }

        $res = $res->whereHas('getGoods', function ($query) {
            $query->where('is_on_sale', 0)
                ->where('is_alone_sale', 1)
                ->where('is_delete', 0);
        });

        $res = $res->with([
            'getGoods' => function ($query) use ($goodsOther) {
                $query->select($goodsOther);
            }
        ]);

        $presale = $this->baseRepository->getToArrayFirst($res);

        if ($presale && $presale['get_goods']) {
            $presale['pa_catid'] = $presale['cat_id'];
            $presale = array_merge($presale, $presale['get_goods']);
            $presale['ru_id'] = $presale['user_id'];
        }

        /* 如果为空，返回空数组 */
        if (empty($presale)) {
            return [];
        }

        $presale['act_name'] = !empty($presale['act_name']) ? $presale['act_name'] : $presale['goods_name'];

        /* 格式化时间 */
        $presale['formated_start_date'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $presale['start_time']);
        $presale['formated_end_date'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $presale['end_time']);
        $presale['formated_pay_start_date'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $presale['pay_start_time']);
        $presale['formated_pay_end_date'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $presale['pay_end_time']);
        /* 格式化保证金 */
        $presale['formated_deposit'] = $this->dscRepository->getPriceFormat($presale['deposit'], false);
        /* 尾款 */
        $presale['final_payment'] = $presale['shop_price'] - $presale['deposit'];
        $presale['formated_final_payment'] = $this->dscRepository->getPriceFormat($presale['final_payment'], false);

        /* 统计信息 */
        $stat = $this->presaleStat($presale_id, $presale['deposit']);
        $presale = array_merge($presale, $stat);

        /* 状态 */
        $presale['status'] = $this->presaleStatus($presale);
        if (isset($GLOBALS['_LANG']['gbs'][$presale['status']])) {
            $presale['status_desc'] = $GLOBALS['_LANG']['gbs'][$presale['status']];
        }

        if (!$presale['act_desc']) {
            $presale['act_desc'] = $this->dscRepository->getContentImgReplace($presale['goods_desc']);
        }

        if ($this->config['open_oss'] == 1) {
            $bucket_info = $this->dscRepository->getBucketInfo();
            $endpoint = $bucket_info['endpoint'];
        } else {
            $endpoint = url('/');
        }
        $desc_preg = get_goods_desc_images_preg($endpoint, $presale['act_desc']);

        $presale['act_desc'] = $desc_preg['goods_desc'];

        $presale['start_time'] = $presale['formated_start_date'];
        $presale['end_time'] = $presale['formated_end_date'];
        $presale['pay_start_time'] = $presale['formated_pay_start_date'];
        $presale['pay_end_time'] = $presale['formated_pay_end_date'];

        //买家印象
        if ($presale['goods_product_tag']) {
            $impression_list = !empty($presale['goods_product_tag']) ? explode(',', $presale['goods_product_tag']) : '';
            foreach ($impression_list as $kk => $vv) {
                $tag[$kk]['txt'] = $vv;
                //印象数量
                $tag[$kk]['num'] = $this->goodsCommentService->commentGoodsTagNum($presale['goods_id'], $vv);
            }
            $presale['impression_list'] = $tag;
        }

        $collect_count = CollectGoods::where('goods_id', $presale['goods_id'])
            ->where('user_id', $user_id)
            ->count();

        $presale['collect_count'] = $collect_count;
        $presale['goods_img'] = $this->dscRepository->getImagePath($presale['goods_img']);
        $presale['goods_thumb'] = $this->dscRepository->getImagePath($presale['goods_thumb']);
        $presale['goods_desc'] = $this->dscRepository->getContentImgReplace($presale['goods_desc']);

        return $presale;
    }

    /*
     * 取得某预售活动统计信息
     * @param   int     $group_buy_id   预售活动id
     * @param   float   $deposit        保证金
     * @return  array   统计信息
     *                  total_order     总订单数
     *                  total_goods     总商品数
     *                  valid_order     有效订单数
     *                  valid_goods     有效商品数
     */
    public function presaleStat($presale_id, $deposit, $user_id = 0)
    {
        $presale_id = intval($presale_id);

        /* 取得预售活动商品 */
        $goods_id = PresaleActivity::where('act_id', $presale_id)
            ->where('review_status', 3)
            ->value('goods_id');

        /* 取得总订单数和总商品数 */
        $where = [
            'user_id' => $user_id,
            'extension_id' => $presale_id,
            'order_status' => [OS_CONFIRMED, OS_UNCONFIRMED]
        ];
        $stat = OrderGoods::selectRaw("COUNT(*) AS total_order, SUM(goods_number) AS total_goods")
            ->where('goods_id', $goods_id)
            ->whereHas('getOrder', function ($query) use ($where) {
                $query = $query->where('main_count', 0);

                if ($where['user_id']) {
                    $query = $query->where('user_id', $where['user_id']);
                }

                $query->where('extension_code', 'presale')
                    ->where('extension_id', $where['extension_id'])
                    ->whereIn('order_status', $where['order_status']);
            });

        $stat = $this->baseRepository->getToArrayFirst($stat);

        if ($stat) {
            if ($stat['total_order'] == 0) {
                $stat['total_goods'] = 0;
            }

            /* 取得有效订单数和有效商品数 */
            $deposit = floatval($deposit);
            if ($deposit > 0 && $stat['total_order'] > 0) {
                $where['deposit'] = $deposit;
                $row = OrderGoods::selectRaw("COUNT(*) AS total_order, SUM(goods_number) AS total_goods")
                    ->where('goods_id', $goods_id)
                    ->whereHas('getOrder', function ($query) use ($where) {
                        $query = $query->where('main_count', 0);

                        $query->where('extension_code', 'presale')
                            ->where('extension_id', $where['extension_id'])
                            ->whereIn('order_status', $where['order_status'])
                            ->whereRaw("(money_paid + surplus) >= '" . $where['deposit'] . "'");
                    });

                $row = $this->baseRepository->getToArrayFirst($row);

                $stat['valid_order'] = $row['total_order'];
                if ($stat['valid_order'] == 0) {
                    $stat['valid_goods'] = 0;
                } else {
                    $stat['valid_goods'] = $row['total_goods'];
                }
            } else {
                $stat['valid_order'] = $stat['total_order'];
                $stat['valid_goods'] = $stat['total_goods'];
            }
        }

        return $stat;
    }

    /**
     * 获得预售的状态
     *
     * @access  public
     * @param array
     * @return  integer
     */
    public function presaleStatus($presale)
    {
        $now = $this->timeRepository->getGmTime();
        if ($presale['is_finished'] == 0) {
            /* 未处理 */
            if ($now < $presale['start_time']) {
                $status = GBS_PRE_START;
            } elseif ($now > $presale['end_time']) {
                $status = GBS_FINISHED;
            } else {
                if ($presale['is_finished'] == 0) {
                    $status = GBS_UNDER_WAY;
                } else {
                    $status = GBS_FINISHED;
                }
            }
        } elseif ($presale['is_finished'] == GBS_SUCCEED) {
            /* 已处理，预售成功 */
            $status = GBS_SUCCEED;
        } elseif ($presale['is_finished'] == GBS_FAIL) {
            /* 已处理，预售失败 */
            $status = GBS_FAIL;
        }

        return $status;
    }

    public function getNewPreGoods($children = [], $min = 0, $max = 0, $start_time = 0, $end_time = 0, $sort, $status = 0, $order = 'DESC', $keywords = '')
    {
        $now = $this->timeRepository->getGmTime();

        $res = Goods::where('is_on_sale', 0)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0);

        if ($min > 0) {
            $res = $res->where('shop_price', '>=', $min);
        }

        if ($max > 0) {
            $res = $res->where('shop_price', '<=', $max);
        }
        if ($keywords) {
            $res = $res->where('goods_name', 'like', '%' . $keywords . '%');
        }

        $wherePresale = [
            'status' => $status,
            'time' => $now,
            'children' => $children
        ];

        $res = $res->whereHas('getPresaleActivity', function ($query) use ($wherePresale) {
            //查询使用分类id不为0的条件
            $wherePresale['children'] = $wherePresale['children'] ?? [];
            if ($wherePresale['children']) {
                foreach ($wherePresale['children'] as $key => $v) {
                    if ($v == 0) {
                        unset($wherePresale['children'][$key]);
                    }
                }

                if ($wherePresale['children']) {
                    $query = $query->whereIn('cat_id', $wherePresale['children']);
                }
            }

            //1未开始，2进行中，3结束
            if ($wherePresale['status'] == 1) {
                $query = $query->where('start_time', '>', $wherePresale['time']);
            } elseif ($wherePresale['status'] == 2) {
                $query = $query->where('start_time', '<', $wherePresale['time'])->where('end_time', '>', $wherePresale['time']);
            } elseif ($wherePresale['status'] == 3) {
                $query = $query->where('end_time', '<', $wherePresale['time']);
            }

            $query->where('review_status', 3);
        });


        $res = $res->with(['getPresaleActivity']);

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $row) {
                if (isset($row['get_presale_activity']) && $row['get_presale_activity']) {
                    $row = array_merge($row, $row['get_presale_activity']);
                }

                $res[$key] = $row;

                $res[$key]['goods_name'] = $row['goods_name'];
                $res[$key]['thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $res[$key]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $res[$key]['original_img'] = $this->dscRepository->getImagePath($row['original_img']);
                $res[$key]['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
                $res[$key]['url'] = route('presale', ['act' => 'view', 'id' => $row['act_id']]);

                $res[$key]['end_time_date'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $row['end_time']);
                $res[$key]['start_time_date'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $row['start_time']);

                $res[$key]['format_shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                $res[$key]['format_market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);

                if ($row['start_time'] >= $now) {
                    $res[$key]['no_start'] = 1;
                }
                if ($row['end_time'] <= $now) {
                    $res[$key]['already_over'] = 1;
                }
            }

            if ($order == 'DESC') {
                $res = collect($res)->sortByDesc($sort);
            } else {
                $res = collect($res)->sortBy($sort);
            }

            $res = $res->values()->all();
        }

        return $res;
    }
}
