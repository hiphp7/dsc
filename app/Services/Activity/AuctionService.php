<?php

namespace App\Services\Activity;

use App\Libraries\Pager;
use App\Models\AuctionLog;
use App\Models\Cart;
use App\Models\Category;
use App\Models\CollectGoods;
use App\Models\Goods;
use App\Models\GoodsActivity;
use App\Models\GoodsAttr;
use App\Models\GoodsCat;
use App\Models\OrderInfo;
use App\Models\Products;
use App\Models\SellerShopinfo;
use App\Models\Users;
use App\Repositories\Activity\ActivityRepository;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Category\CategoryService;
use App\Services\Common\TemplateService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Merchant\MerchantCommonService;

/**
 * 活动 ->【拍卖】
 */
class AuctionService
{
    protected $config;
    protected $categoryService;
    protected $timeRepository;
    protected $dscRepository;
    protected $merchantCommonService;
    protected $goodsCommonService;
    protected $activityRepository;
    protected $templateService;
    protected $baseRepository;

    public function __construct(
        TimeRepository $timeRepository,
        CategoryService $categoryService,
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService,
        GoodsCommonService $goodsCommonService,
        ActivityRepository $activityRepository,
        TemplateService $templateService,
        BaseRepository $baseRepository
    )
    {
        $this->timeRepository = $timeRepository;
        $this->categoryService = $categoryService;
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->goodsCommonService = $goodsCommonService;
        $this->config = $this->dscRepository->dscConfig();
        $this->activityRepository = $activityRepository;
        $this->templateService = $templateService;
        $this->baseRepository = $baseRepository;
    }

    /**
     * 取得拍卖活动列表
     *
     * @param int $user_id
     * @param string $keywords
     * @param string $sort
     * @param string $order
     * @param int $page
     * @param int $size
     * @return array|mixed
     */
    public function auctionList($user_id = 0, $keywords = '', $sort = 'act_id', $order = 'desc', $page = 1, $size = 10)
    {
        // 排序
        $default_sort_order_method = $this->config['sort_order_method'] == 0 ? 'desc' : 'asc';
        $default_sort_order_type = $this->config['sort_order_type'] == 0 ? 'act_id' : ($this->config['sort_order_type'] == 1 ? 'start_time' : 'end_time');

        $sort = in_array($sort, ['act_id', 'start_time', 'end_time']) ? $sort : $default_sort_order_type;
        $order = in_array($order, ['asc', 'desc']) ? $order : $default_sort_order_method;


        $now = $this->timeRepository->getGmTime();
        $timeFormat = $this->config['time_format'];
        $begin = ($page - 1) * $size;

        $res = GoodsActivity::where('review_status', 3)
            ->where('act_type', GAT_AUCTION)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->where('is_finished', 0);

        $res = $res->whereHas('getGoods', function ($query) {
            $query->where('is_on_sale', 1)
                ->where('is_alone_sale', 1)
                ->where('is_delete', 0);
        });

        if ($keywords) {
            $res = $res->where(function ($query) use ($keywords) {
                $query->where('act_name', 'like', '%' . $keywords . '%');

                $query->orWhere(function ($query) use ($keywords) {
                    $query->whereHas('getGoods', function ($query) use ($keywords) {
                        $query->where('goods_name', 'like', '%' . $keywords . '%');
                    });
                });
            });
        }

        $res = $res->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_thumb');
            }
        ]);

        $res = $res->orderBy($sort, $order);

        if ($begin > 0) {
            $res = $res->skip($begin);
        }

        if ($size > 0) {
            $res = $res->take($size);
        };

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        $auction_list = [];
        if ($res) {
            $auction_list['under_way'] = [];
            $auction_list['finished'] = [];

            foreach ($res as $row) {
                if ($row['get_goods']) {
                    $row = array_merge($row, $row['get_goods']);
                }
                $ext_info = unserialize($row['ext_info']);

                $auction = array_merge($row, $ext_info);

                $auction['status_no'] = $this->activityRepository->getAuctionStatus($auction);

                $auction['start_time'] = $this->timeRepository->getLocalDate($timeFormat, $auction['start_time']);

                $auction['end_time'] = $this->timeRepository->getLocalDate($timeFormat, $auction['end_time']);
                $auction['formated_start_price'] = $this->dscRepository->getPriceFormat($auction['start_price']);
                $auction['formated_end_price'] = $this->dscRepository->getPriceFormat($auction['end_price']);
                $auction['formated_deposit'] = $this->dscRepository->getPriceFormat($auction['deposit']);
                $auction['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);

                $auction['current_time'] = $this->timeRepository->getLocalDate($timeFormat, $now);

                /* 查询已确认订单数 */
                if ($auction['status_no'] > 1) {
                    $auction['order_count'] = OrderInfo::where('extension_code', 'auction')
                        ->where('extension_id', $auction['act_id'])
                        ->whereIn('order_status', [OS_CONFIRMED, OS_UNCONFIRMED])
                        ->count();
                } else {
                    $auction['order_count'] = 0;
                }

                /* 查询出价用户数和最后出价 */
                $auction['bid_user_count'] = AuctionLog::where('act_id', $auction['act_id'])->count();

                if ($auction['bid_user_count'] > 0) {
                    $log = AuctionLog::where('act_id', $auction['act_id']);

                    $log = $log->whereHas('getUsers');

                    $log = $log->with([
                        'getUsers' => function ($query) {
                            $query->select('user_id', 'user_name');
                        }
                    ]);

                    $log = $log->orderBy('log_id', 'desc')->first();

                    $log = $log ? $log->toArray() : [];

                    if (!empty($log)) {
                        $log = array_merge($log, $log['get_users']);
                        $log['formated_bid_price'] = $this->dscRepository->getPriceFormat($log['bid_price'], false); //最后出价
                        $log['bid_time'] = $this->timeRepository->getLocalDate($timeFormat, $log['bid_time']);
                        $auction['last_bid'] = $log;
                    }
                }

                $auction['is_winner'] = 0;
                if (isset($auction['last_bid']['bid_user']) && $auction['last_bid']['bid_user']) {
                    if ($auction['status_no'] == FINISHED && $auction['last_bid']['bid_user'] == $user_id && $auction['order_count'] == 0) {
                        $auction['is_winner'] = 1;
                    }
                }

                $auction['s_user_id'] = $user_id;
                if ($auction['status_no'] < 2) {
                    $auction_list['under_way'][] = $auction;
                } else {
                    $auction_list['finished'][] = $auction;
                }
            }

            if (isset($auction_list['under_way']) && $auction_list['under_way']) {
                $auction_list = array_merge($auction_list['under_way'], $auction_list['finished']);
            } else {
                $auction_list = $auction_list['finished'];
            }
        }

        return $auction_list;
    }


    /**
     * 取得拍卖活动信息
     * @param int $act_id 活动id
     * @return  array
     */
    public function getAuctionInfo($act_id = 0, $config = false, $path = '')
    {
        $auction = GoodsActivity::where('act_id', $act_id);

        if (empty($path)) {
            $auction = $auction->where('review_status', 3);
        }

        $auction = $auction->first();

        $auction = $auction ? $auction->toArray() : [];

        if (!$auction) {
            return [];
        }

        $auction['endTime'] = $auction['end_time'];
        $auction['startTime'] = $auction['start_time'];
        if (isset($auction['act_type']) && $auction['act_type'] != GAT_AUCTION) {
            return [];
        }

        $timeFormat = $this->config['time_format'];
        $auction['status_no'] = $this->activityRepository->getAuctionStatus($auction);
        if ($config == true) {
            $auction['start_time'] = $this->timeRepository->getLocalDate($timeFormat, $auction['start_time']);
            $auction['end_time'] = $this->timeRepository->getLocalDate($timeFormat, $auction['end_time']);
        } else {
            $auction['start_time'] = $this->timeRepository->getLocalDate($timeFormat, $auction['start_time']);
            $auction['end_time'] = $this->timeRepository->getLocalDate($timeFormat, $auction['end_time']);
        }
        $ext_info = unserialize($auction['ext_info']);

        $auction = array_merge($auction, $ext_info);
        $auction['formated_start_price'] = $this->dscRepository->getPriceFormat($auction['start_price']);
        $auction['formated_end_price'] = $this->dscRepository->getPriceFormat($auction['end_price']);
        $auction['formated_amplitude'] = $this->dscRepository->getPriceFormat($auction['amplitude']);
        $auction['formated_deposit'] = $this->dscRepository->getPriceFormat($auction['deposit']);

        /* 查询出价用户数和最后出价 */
        $auction['bid_user_count'] = AuctionLog::where('act_id', $act_id)->count();

        if ($auction['bid_user_count'] > 0) {
            $row = AuctionLog::where('act_id', $act_id);

            $row = $row->whereHas('getUsers');

            $row = $row->with([
                'getUsers' => function ($query) {
                    $query->select('user_id', 'user_name');
                }
            ]);


            $row = $row->orderBy('log_id', 'desc');

            $row = $row->first();

            $row = $row ? $row->toArray() : [];

            if (!empty($row)) {
                if ($row['get_users']) {
                    $row = array_merge($row, $row['get_users']);
                }

                $row['formated_bid_price'] = $this->dscRepository->getPriceFormat($row['bid_price'], false);
                $row['bid_time'] = $this->timeRepository->getLocalDate($timeFormat, $row['bid_time']);
                $auction['last_bid'] = $row;
                $auction['bid_time'] = $row['bid_time'];
            }
        } else {
            $row['bid_time'] = $auction['end_time'];
        }


        /* 查询已确认订单数 */
        if ($auction['status_no'] > 1) {
            $auction['order_count'] = OrderInfo::where('extension_code', 'auction')
                ->where('extension_id', $act_id)
                ->whereIn('order_status', [OS_CONFIRMED, OS_UNCONFIRMED])
                ->count();
        } else {
            $auction['order_count'] = 0;
        }

        /* 当前价 */
        $auction['current_price'] = isset($auction['last_bid']) ? $auction['last_bid']['bid_price'] : $auction['start_price'];
        $auction['current_price_int'] = intval($auction['current_price']);
        $auction['formated_current_price'] = $this->dscRepository->getPriceFormat($auction['current_price'], false);

        return $auction;
    }

    /**
     * 查找我的收藏商品
     * @param $goodsId
     * @param $uid
     * @return array
     */
    public function findOne($goodsId, $uid)
    {
        $cg = CollectGoods::where('goods_id', $goodsId)
            ->where('user_id', $uid)
            ->first();

        if ($cg === null) {
            return [];
        }
        return $cg->toArray();
    }


    /**
     * 取得拍卖活动出价记录
     * @param int $act_id 活动id
     * @return  array
     */
    public function auction_log($act_id = 0, $type = 0)
    {
        if ($type == 1) {
            $log = AuctionLog::where('act_id', $act_id);

            $log = $log->whereHas('getUsers');

            $log = $log->count();
        } else {
            $res = AuctionLog::where('act_id', $act_id);

            $res = $res->whereHas('getUsers');

            $res = $res->with([
                'getUsers' => function ($query) {
                    $query->select('user_id', 'user_name');
                }
            ]);

            $res = $res->orderBy('log_id', 'desc');

            $res = $res->get();

            $res = $res ? $res->toArray() : [];

            $log = [];
            if ($res) {
                $timeFormat = $this->config['time_format'];
                foreach ($res as $row) {
                    if ($row['get_users']) {
                        $row = array_merge($row, $row['get_users']);
                    }
                    $row['user_name'] = isset($row['user_name']) ? setAnonymous($row['user_name']) : ''; //处理用户名 by wu
                    $row['bid_time'] = $this->timeRepository->getLocalDate($timeFormat, $row['bid_time']);
                    $row['formated_bid_price'] = $this->dscRepository->getPriceFormat($row['bid_price'], false);
                    $log[] = $row;
                }
            }
        }

        return $log;
    }

    /**
     * 推荐拍品
     * @param int $act_id 活动id
     * @return  array
     */
    public function recommend_goods($type = '')
    {
        $now = $this->timeRepository->getGmTime();

        $res = GoodsActivity::where('review_status', 3)
            ->where('act_type', GAT_AUCTION)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->where('is_finished', '<', 2);

        switch ($type) {
            case 'best':
                $res = $res->where('is_best', 1);
                break;
            case 'new':
                $res = $res->where('is_new', 1);
                break;
            case 'hot':
                $res = $res->where('is_hot', 1);
                break;
        }

        $res = $res->whereHas('getGoods', function ($query) {
            $query->where('is_on_sale', 1)
                ->where('is_alone_sale', 1)
                ->where('is_delete', 0);
        });

        $res = $res->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_thumb');
            }
        ]);

        $res = $res->limit(6)
            ->get();
        $res = $res ? $res->toArray() : [];

        $info = [];
        if ($res) {
            $timeFormat = $this->config['time_format'];
            foreach ($res as $row) {
                if ($row['get_goods']) {
                    $row = array_merge($row, $row['get_goods']);
                }
                $ext_info = unserialize($row['ext_info']);

                $auction = array_merge($row, $ext_info);

                $auction['status_no'] = $this->activityRepository->getAuctionStatus($auction);
                $auction['start_time'] = $this->timeRepository->getLocalDate($timeFormat, $auction['start_time']);
                $auction['end_time'] = $this->timeRepository->getLocalDate($timeFormat, $auction['end_time']);
                $auction['formated_start_price'] = $this->dscRepository->getPriceFormat($auction['start_price']);
                $auction['formated_end_price'] = $this->dscRepository->getPriceFormat($auction['end_price']);
                $auction['formated_deposit'] = $this->dscRepository->getPriceFormat($auction['deposit']);
                $auction['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $auction['current_time'] = $this->timeRepository->getLocalDate($timeFormat, $now);
                $info[] = $auction;
            }
        }

        return $info;
    }


    /**
     * 取商品的规格列表
     *
     * @param int $goods_id 商品id
     * @param string $conditions sql条件
     *
     * @return  array
     */
    public function get_specifications_list($goods_id)
    {
        /* 取商品属性 */

        $list = GoodsAttr::from('goods_attr as ga')
            ->select('ga.goods_attr_id', 'ga.attr_id', 'ga.attr_value', 'a.attr_name')
            ->leftjoin('attribute as a', 'ga.attr_id', '=', 'a.attr_id')
            ->where('ga.goods_id', $goods_id)
            ->get();

        if ($list === null) {
            return [];
        }

        $result = $list->toArray();

        $return_array = [];
        foreach ($result as $value) {
            $return_array[$value['goods_attr_id']] = $value;
        }

        return $return_array;
    }


    /**
     * 获取商品属性组
     * @return  int
     */
    public function getProducts($goods_id = 0, $product_id = 0)
    {
        $product = Products::select('goods_attr')
            ->where('goods_id', $goods_id)
            ->where('product_id', $product_id)
            ->get();

        return $product ? $product->toArray() : [];
    }

    /**
     * 会员信息
     * @return  int
     */
    public function userInfo($user_id)
    {
        $info = Users::where('user_id', $user_id)->first();

        return $info ? $info->toArray() : [];
    }

    /**
     * 插入出价记录
     * @return  int
     */
    public function addAuctionLog($auction_log)
    {
        $id = AuctionLog::insertGetId($auction_log);

        return $id;
    }

    /**
     * 修改活动状态
     * @return  int
     */
    public function updateGoodsActivity($act_id = 0)
    {
        GoodsActivity::where('act_id', $act_id)
            ->update(['is_finished' => 1]);
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

        /* 插入一条新记录 */
        $rec_id = Cart::insertGetId($arguments);
        return $rec_id;
    }

    /**
     * 店铺信息
     *
     * @access  public
     * @param array $address
     * @return  bool
     */
    public function getSellerShopinfo($ru_id)
    {
        $info = SellerShopinfo::select('province', 'city', 'kf_type', 'kf_ww', 'kf_qq', 'shop_name')
            ->where('ru_id', $ru_id)
            ->first();

        if ($info === null) {
            return [];
        }

        return $info->toArray();
    }




    /*-------------------*/

    /**
     * 取得拍卖活动数量
     * @return  int
     */
    public function getAuctionCount($keywords, $cats = [])
    {
        $now = $this->timeRepository->getGmTime();

        $res = GoodsActivity::where('review_status', 3)
            ->where('act_type', GAT_AUCTION)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->where('is_finished', '<', 2);

        $goodsWhere = [];
        if ($cats) {
            $cats = !is_array($cats) ? explode(",", $cats) : $cats;

            /* 查询扩展分类数据 */
            $extension_goods = GoodsCat::select('goods_id')->whereIn('cat_id', $cats)->get();
            $extension_goods = $extension_goods ? $extension_goods->toArray() : [];
            $extension_goods = $extension_goods ? collect($extension_goods)->flatten()->all() : [];

            $goodsWhere = [
                'cats' => $cats,
                'extension_goods' => $extension_goods
            ];
        }

        $res = $res->whereHas('getGoods', function ($query) use ($goodsWhere) {
            $query = $query->where('is_on_sale', 1)
                ->where('is_alone_sale', 1)
                ->where('is_delete', 0);

            if ($goodsWhere) {
                $query->where(function ($query) use ($goodsWhere) {
                    $query = $query->whereIn('cat_id', $goodsWhere['cats']);
                    $query->orWhereIn('goods_id', $goodsWhere['extension_goods']);
                });
            }
        });

        if ($keywords) {
            $res = $res->where(function ($query) use ($keywords) {
                $query->where('act_name', 'like', '%' . $keywords . '%');


                $query->orWhere(function ($query) use ($keywords) {
                    $query->whereHas('getGoods', function ($query) use ($keywords) {
                        $query->where('goods_name', 'like', '%' . $keywords . '%');
                    });
                });
            });
        }

        $res = $res->count();
        return $res;
    }

    /**
     * 取得某页的拍卖活动
     *
     * @param $keywords
     * @param string $sort
     * @param string $order
     * @param int $size
     * @param int $page
     * @param array $cats
     * @return array|mixed
     * @throws \Exception
     */
    public function getAuctionList($keywords, $sort = 'act_id', $order = 'desc', $size = 20, $page = 1, $cats = [])
    {
        $goods_num = isset($_REQUEST['goods_num']) && !empty($_REQUEST['goods_num']) ? intval($_REQUEST['goods_num']) : 0;

        $auction_list = [];
        $auction_list['finished'] = $auction_list['finished'] = [];

        $now = $this->timeRepository->getGmTime();

        $res = GoodsActivity::where('review_status', 3)
            ->where('act_type', GAT_AUCTION)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->where('is_finished', 0);

        $goodsWhere = [];
        if ($cats) {
            $cats = !is_array($cats) ? explode(",", $cats) : $cats;

            /* 查询扩展分类数据 */
            $extension_goods = GoodsCat::select('goods_id')->whereIn('cat_id', $cats)->get();
            $extension_goods = $extension_goods ? $extension_goods->toArray() : [];
            $extension_goods = $extension_goods ? collect($extension_goods)->flatten()->all() : [];

            $goodsWhere = [
                'cats' => $cats,
                'extension_goods' => $extension_goods
            ];
        }

        $res = $res->whereHas('getGoods', function ($query) use ($goodsWhere) {
            $query = $query->where('is_on_sale', 1)
                ->where('is_alone_sale', 1)
                ->where('is_delete', 0);

            if ($goodsWhere) {
                $query->where(function ($query) use ($goodsWhere) {
                    $query = $query->whereIn('cat_id', $goodsWhere['cats']);
                    $query->orWhereIn('goods_id', $goodsWhere['extension_goods']);
                });
            }
        });

        if ($keywords) {
            $res = $res->where(function ($query) use ($keywords) {
                $query->where('act_name', 'like', '%' . $keywords . '%');


                $query->orWhere(function ($query) use ($keywords) {
                    $query->whereHas('getGoods', function ($query) use ($keywords) {
                        $query->where('goods_name', 'like', '%' . $keywords . '%');
                    });
                });
            });
        }

        $res = $res->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_thumb');
            }
        ]);

        //瀑布流加载分类商品 by wu
        if ($goods_num) {
            $start = $goods_num;
        } else {
            $start = ($page - 1) * $size;
        }

        $res = $res->orderBy($sort, $order);

        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            $timeFormat = $this->config['time_format'];
            foreach ($res as $row) {
                if ($row['get_goods']) {
                    $row = array_merge($row, $row['get_goods']);
                }

                $ext_info = unserialize($row['ext_info']);
                $auction = array_merge($row, $ext_info);
                $auction['status_no'] = $this->activityRepository->getAuctionStatus($auction);

                $auction['start_time'] = $this->timeRepository->getLocalDate($timeFormat, $auction['start_time']);
                $auction['end_time'] = $this->timeRepository->getLocalDate($timeFormat, $auction['end_time']);
                $auction['start_price'] = isset($auction['start_price']) ? $auction['start_price'] : 0;
                $auction['formated_start_price'] = $this->dscRepository->getPriceFormat($auction['start_price']);
                $auction['formated_end_price'] = $this->dscRepository->getPriceFormat($auction['end_price']);
                $auction['formated_deposit'] = $this->dscRepository->getPriceFormat($auction['deposit']);
                $auction['goods_thumb'] = isset($row['goods_thumb']) ? $this->dscRepository->getImagePath($row['goods_thumb']) : '';
                $auction['url'] = $this->dscRepository->buildUri('auction', ['auid' => $auction['act_id']]);
                $auction['count'] = auction_log($auction['act_id'], 1);
                $auction['current_time'] = $this->timeRepository->getLocalDate($timeFormat);
                $auction['rz_shopName'] = $this->merchantCommonService->getShopName($row['user_id'], 1); //店铺名称
                /* 查询已确认订单数 */
                if ($auction['status_no'] > 1) {
                    $auction['order_count'] = OrderInfo::where('extension_code', 'auction')
                        ->where('extension_id', $auction['act_id'])
                        ->whereIn('order_status', [OS_CONFIRMED, OS_UNCONFIRMED])
                        ->count();
                } else {
                    $auction['order_count'] = 0;
                }

                /* 查询出价用户数和最后出价  qin */
                $auction['bid_user_count'] = AuctionLog::where('act_id', $auction['act_id'])->count();

                if ($auction['bid_user_count'] > 0) {
                    $row = AuctionLog::where('act_id', $auction['act_id']);

                    $row = $row->whereHas('getUsers');

                    $row = $row->with([
                        'getUsers' => function ($query) {
                            $query->where('user_id', 'user_name');
                        }
                    ]);

                    $row = $row->orderBy('log_id', 'desc');

                    $row = $row->first();

                    $row = $row ? $row->toArray() : [];

                    if (isset($row['get_users'])) {
                        $row = array_merge($row, $row['get_users']);
                    }
                    if ($row) {
                        $timeFormat = $this->config['time_format'];
                        $row['formated_bid_price'] = $this->dscRepository->getPriceFormat($row['bid_price'], false);
                        $row['bid_time'] = $this->timeRepository->getLocalDate($timeFormat, $row['bid_time']);
                    }
                    $auction['last_bid'] = $row;
                }

                $auction['is_winner'] = 0;
                if (isset($auction['last_bid']['bid_user']) && $auction['last_bid']['bid_user']) {
                    if ($auction['status_no'] == FINISHED && $auction['last_bid']['bid_user'] == session('user_id') && $auction['order_count'] == 0) {
                        $auction['is_winner'] = 1;
                    }
                }

                $auction['s_user_id'] = session('user_id');

                if ($auction['status_no'] < 2) {
                    $auction_list['under_way'][] = $auction;
                } else {
                    $auction_list['finished'][] = $auction;
                }
            }

            if (isset($auction_list['under_way']) && $auction_list['under_way']) {
                $auction_list = array_merge($auction_list['under_way'], $auction_list['finished']);
            } else {
                $auction_list = $auction_list['finished'];
            }
        }

        return $auction_list;
    }

    /**
     * 取得拍卖活动所有商品分类的顶级分类
     *
     * @return array
     */
    public function getTopCat()
    {
        $now = $this->timeRepository->getGmTime();

        $cat_list = Goods::select('cat_id')->where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0);

        $cat_list = $cat_list->whereHas('getGoodsActivity', function ($query) use ($now) {
            $query->where('review_status', 3)
                ->where('act_type', GAT_AUCTION)
                ->where('start_time', '<=', $now)
                ->where('end_time', '>=', $now)
                ->where('is_finished', '<', 2);
        });

        $cat_list = $cat_list->get();

        $cat_list = $cat_list ? $cat_list->toArray() : [];

        $cats = $this->baseRepository->getFlatten($cat_list);

        $parentsCatList = $this->categoryService->parentsCatList($cats);

        $parentsCatList = Category::whereIn('cat_id', $parentsCatList)
            ->where('parent_id', 0)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('cat_id', 'ASC');

        $cat_top_list = $this->baseRepository->getToArrayGet($parentsCatList);

        return $cat_top_list;
    }

    /**
     * 获得指定分类下的推荐商品
     *
     * @access  public
     * @param string $type 推荐类型，可以是 best, new, hot, promote
     * @param string $cats 分类的ID
     * @param integer $min 商品积分下限
     * @param integer $max 商品积分上限
     * @return  array
     */
    public function getAuctionRecommendGoods($type = '', $cats = '', $min = 0, $max = 0)
    {
        $now = $this->timeRepository->getGmTime();
        $order_type = $GLOBALS['_CFG']['recommend_order'];

        $type2lib = ['best' => 'auction_best', 'new' => 'auction_new', 'hot' => 'auction_hot'];
        $num = $this->templateService->getLibraryNumber($type2lib[$type], 'auction_list');

        $res = Goods::select(['goods_id', 'brand_id', 'goods_name', 'goods_name_style', 'goods_brief', 'goods_thumb', 'goods_img'])
            ->where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0);

        $cats = $cats && !is_array($cats) ? explode(",", $cats) : $cats;

        /* 查询扩展分类数据 */
        $extension_goods = GoodsCat::select('goods_id')->whereIn('cat_id', $cats)->get();
        $extension_goods = $extension_goods ? $extension_goods->toArray() : [];
        $extension_goods = $extension_goods ? collect($extension_goods)->flatten()->all() : [];

        if ($cats) {
            $goodsWhere = [
                'cats' => $cats,
                'extension_goods' => $extension_goods
            ];

            $res = $res->where(function ($query) use ($goodsWhere) {
                $query = $query->whereIn('cat_id', $goodsWhere['cats']);
                $query->orWhereIn('goods_id', $goodsWhere['extension_goods']);
            });
        }

        if ($min > 0) {
            $res = $res->where('shop_price', '>=', $min);
        }

        if ($max > 0) {
            $res = $res->where('shop_price', '<=', $max);
        }

        $activity = [
            'type' => $type,
            'act_type' => GAT_AUCTION,
            'time' => $now
        ];

        $res = $res->whereHas('getGoodsActivity', function ($query) use ($activity) {
            switch ($activity['type']) {
                case 'best':
                    $query = $query->where('is_best', 1);
                    break;
                case 'new':
                    $query = $query->where('is_new', 1);
                    break;
                case 'hot':
                    $query = $query->where('is_hot', 1);
                    break;
            }

            $query->where('act_type', $activity['act_type'])
                ->where('review_status', 3)
                ->where('start_time', '<=', $activity['time'])
                ->where('end_time', '>=', $activity['time'])
                ->where('is_finished', '<', 2);
        });

        $res = $res->with([
            'getGoodsActivity' => function ($query) {
                $query->select('goods_id', 'act_name', 'act_id', 'ext_info', 'start_time', 'end_time');
            },
            'getBrand' => function ($query) {
                $query->select('brand_id', 'brand_name');
            }
        ]);

        if ($order_type == 0) {
            $res = $res->orderByRaw('sort_order, last_update DESC');
        } else {
            $res = $res->orderByRaw('RAND()');
        }

        $res = $res->take($num);

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        $auction = [];
        if ($res) {
            $timeFormat = $this->config['time_format'];
            foreach ($res as $key => $row) {
                if ($row['get_goods_activity']) {
                    $row = array_merge($row, $row['get_goods_activity']);
                }

                if ($row['get_brand']) {
                    $row = array_merge($row, $row['get_brand']);
                }

                $auction[$key]['id'] = $row['goods_id'];
                $auction[$key]['name'] = $row['goods_name'];
                $auction[$key]['brief'] = $row['goods_brief'];
                $auction[$key]['brand_name'] = !empty($row['brand_name']) ? $row['brand_name'] : '';
                $auction[$key]['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                    $this->dscRepository->subStr($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
                $auction[$key]['thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $auction[$key]['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
                $auction[$key]['url'] = $this->dscRepository->buildUri('auction', ['auid' => $row['act_id'], $row['act_name']]);

                $auction[$key]['format_start_time'] = $this->timeRepository->getLocalDate($timeFormat, $row['start_time']);
                $auction[$key]['format_end_time'] = $this->timeRepository->getLocalDate($timeFormat, $row['end_time']);

                $ext_info = unserialize($row['ext_info']);
                $auction_info = array_merge($row, $ext_info);

                $auction_info['start_price'] = $auction_info['start_price'] ?? 0;
                $auction_info['formated_start_price'] = $this->dscRepository->getPriceFormat($auction_info['start_price']);

                $auction[$key]['auction'] = $auction_info;
                $auction[$key]['status_no'] = $this->activityRepository->getAuctionStatus($auction_info);
                $auction[$key]['start_price'] = $this->dscRepository->getPriceFormat($auction_info['start_price']);
                $auction[$key]['count'] = auction_log($row['act_id'], 1);

                $auction[$key]['short_style_name'] = $this->goodsCommonService->addStyle($auction[$key]['short_name'], $row['goods_name_style']);
            }
        }

        return $auction;
    }

    /**
     * 获取会员竞拍的拍卖活动的数量
     *
     * @param int $user_id 出价会员ID
     * @param string $auction 活动类型
     * @return mixed
     */
    public function getAllAuction($user_id = 0, $auction = '')
    {
        $where = [
            'user_id' => $user_id,
            'auction' => $auction
        ];

        $auction_count = GoodsActivity::searchKeyword($auction)
            ->whereHas("getAuctionLog", function ($query) use ($where) {
                if ($where['auction']) {
                    $query = $query->searchKeyword($where['auction']);
                }

                $query->where('bid_user', $where['user_id']);
            });

        $auction_count = $auction_count->count();

        return $auction_count;
    }

    /**
     * 获取会员竞拍的拍卖活动列表
     */
    public function getAuctionGoodsList($user_id, $record_count, $page, $list = [], $size = 10)
    {
        if ($list) {
            $idTxt = $list->idTxt;
            $keyword = $list->keyword;
            $action = $list->action;
            $type = $list->type;
            $status_keyword = isset($list->status_keyword) ? $list->status_keyword : '';
            $date_keyword = isset($list->date_keyword) ? $list->date_keyword : '';

            $id = '"';
            $id .= $user_id . "=";
            $id .= "idTxt@" . $idTxt . "|";
            $id .= "keyword@" . $keyword . "|";
            $id .= "action@" . $action . "|";
            $id .= "type@" . $type . "|";

            if ($status_keyword) {
                $id .= "status_keyword@" . $status_keyword . "|";
            }

            if ($date_keyword) {
                $id .= "date_keyword@" . $date_keyword;
            }

            $substr = substr($id, -1);
            if ($substr == "|") {
                $id = substr($id, 0, -1);
            }

            $id .= '"';
        } else {
            $id = $user_id;
        }

        $config = ['header' => $GLOBALS['_LANG']['pager_2'], "prev" => "<i><<</i>" . $GLOBALS['_LANG']['page_prev'], "next" => "" . $GLOBALS['_LANG']['page_next'] . "<i>>></i>", "first" => $GLOBALS['_LANG']['page_first'], "last" => $GLOBALS['_LANG']['page_last']];

        $pagerParams = [
            'total' => $record_count,
            'listRows' => $size,
            'id' => $id,
            'page' => $page,
            'funName' => 'user_auction_gotoPage',
            'pageType' => 1,
            'config_zn' => $config
        ];
        $user_auction = new Pager($pagerParams);
        $limit = $user_auction->limit;
        $pager = $user_auction->fpage([0, 4, 5, 6, 9]);

        $where = [
            'user_id' => $user_id,
            'auction' => $list
        ];

        /* 拍卖活动列表 */
        $res = GoodsActivity::searchKeyword($where['auction'])->whereHas("getAuctionLog", function ($query) use ($where) {
            if ($where['auction']) {
                $query = $query->searchKeyword($where['auction']);
            }

            $query->where('bid_user', $where['user_id']);
        });

        $res = $res->with([
            'getAuctionLog' => function ($query) {
                $query->select('act_id', 'bid_time', 'bid_price');
            },
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_thumb');
            }
        ]);

        $res = $res->orderBy('act_id', 'desc');

        $start = ($page - 1) * $size;

        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        $list = [];

        if ($res) {
            $timeFormat = $this->config['time_format'];
            foreach ($res as $row) {
                $row = $row['get_auction_log'] ? array_merge($row, $row['get_auction_log']) : $row;
                $row = $row['get_goods'] ? array_merge($row, $row['get_goods']) : $row;
                $arr['status_no'] = $this->activityRepository->getAuctionStatus($row);
                $arr['act_id'] = $row['act_id'];
                $arr['act_name'] = $row['act_name'];
                $arr['goods_thumb'] = empty($row['goods_thumb']) ? '' : $this->dscRepository->getImagePath($row['goods_thumb']);
                $arr['goods_name'] = $row['goods_name'];
                $arr['start_time'] = $row['start_time'];
                $arr['end_time'] = $row['end_time'];
                $arr['bid_time'] = $this->timeRepository->getLocalDate($timeFormat, $row['bid_time']);
                $arr['bid_price'] = $this->dscRepository->getPriceFormat($row['bid_price']);
                $arr['status'] = $GLOBALS['_LANG']['auction_staues'][$arr['status_no']];
                $list[] = $arr;
            }
        }

        $auction_list = ['auction_list' => $list, 'pager' => $pager, 'record_count' => $record_count];
        return $auction_list;
    }

    /**
     * 获取会员竞拍的拍卖活动列表
     */
    public function getAuctionBidGoodsList($user_id, $page, $list = [], $size = 10)
    {
        $where = [
            'user_id' => $user_id,
            'auction' => $list
        ];

        /* 拍卖活动列表 */
        $res = GoodsActivity::searchKeyword($where['auction'])->whereHas("getAuctionLog", function ($query) use ($where) {
            if ($where['auction']) {
                $query = $query->searchKeyword($where['auction']);
            }

            $query->where('bid_user', $where['user_id']);
        });

        $res = $res->with([
            'getAuctionLog' => function ($query) {
                $query->select('act_id', 'bid_time', 'bid_price');
            },
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_thumb');
            }
        ]);

        $res = $res->orderBy('act_id', 'desc');

        $start = ($page - 1) * $size;

        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        $list = [];

        if ($res) {
            $timeFormat = $this->config['time_format'];
            foreach ($res as $row) {
                $row = $row['get_auction_log'] ? array_merge($row, $row['get_auction_log']) : $row;
                $row = $row['get_goods'] ? array_merge($row, $row['get_goods']) : $row;
                $arr['status_no'] = $this->activityRepository->getAuctionStatus($row);
                $arr['act_id'] = $row['act_id'];
                $arr['act_name'] = $row['act_name'];
                $arr['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $arr['goods_name'] = $row['goods_name'];
                $arr['start_time'] = $row['start_time'];
                $arr['end_time'] = $row['end_time'];
                $arr['bid_time'] = $this->timeRepository->getLocalDate($timeFormat, $row['bid_time']);
                $arr['bid_price'] = $this->dscRepository->getPriceFormat($row['bid_price']);
                $arr['status'] = $GLOBALS['_LANG']['auction_staues'][$arr['status_no']];
                $list[] = $arr;
            }
        }

        return $list;

    }


}
