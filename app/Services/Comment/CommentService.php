<?php

namespace App\Services\Comment;

use App\Libraries\Pager;
use App\Models\Comment;
use App\Models\CommentBaseline;
use App\Models\CommentImg;
use App\Models\CommentSeller;
use App\Models\Goods;
use App\Models\MerchantsShopInformation;
use App\Models\OrderGoods;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Goods\GoodsCommentService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderCommonService;
use App\Services\Order\OrderGoodsService;

/**
 * 商城评论
 * Class Comment
 * @package App\Services
 */
class CommentService
{
    protected $timeRepository;
    protected $baseRepository;
    protected $config;
    protected $dscRepository;
    protected $orderCommonService;
    protected $orderGoodsService;
    protected $goodsCommentService;
    protected $merchantCommonService;

    public function __construct(
        TimeRepository $timeRepository,
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        OrderCommonService $orderCommonService,
        OrderGoodsService $orderGoodsService,
        GoodsCommentService $goodsCommentService,
        MerchantCommonService $merchantCommonService
    )
    {
        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->orderCommonService = $orderCommonService;
        $this->orderGoodsService = $orderGoodsService;
        $this->goodsCommentService = $goodsCommentService;
        $this->merchantCommonService = $merchantCommonService;
    }

    /**
     * 待评论列表
     * @param $uid
     * @param int $sign
     * @param int $page
     * @param int $size
     * @return type
     */
    public function getCommentList($uid, $sign = 0, $page = 1, $size = 10)
    {
        // 剔除未保存晒单图
        CommentImg::where('user_id', $uid)
            ->where('comment_id', 0)
            ->delete();

        $start = ($page - 1) * $size;
        return $this->getUserOrderCommentList($uid, $sign, 0, $size, $start);
    }

    /**
     * 获取评论图片
     *
     * @param array $where
     * @param array $order
     * @return mixed
     */
    public function getCommentImgList($where = [], $order = ['sort' => 'id', 'order' => 'desc'])
    {
        $res = CommentImg::whereRaw(1);

        if (isset($where['user_id'])) {
            $res = $res->where('user_id', $where['user_id']);
        }

        if (isset($where['order_id'])) {
            $res = $res->where('order_id', $where['order_id']);
        }

        if (isset($where['goods_id'])) {
            $res = $res->where('goods_id', $where['goods_id']);
        }

        if (isset($where['comment_id'])) {
            $res = $res->where('comment_id', $where['comment_id']);
        }

        $res = $res->orderBy($order['sort'], $order['order']);

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $key => $row) {
                $row['comment_img'] = $this->dscRepository->getImagePath($row['comment_img']);
                $row['img_thumb'] = $this->dscRepository->getImagePath($row['img_thumb']);

                $row['path_comment_img'] = $this->dscRepository->getImagePath($row['comment_img']);
                $row['path_img_thumb'] = $this->dscRepository->getImagePath($row['img_thumb']);

                $res[$key] = $row;
            }
        }

        return $res;
    }

    /**
     * 删除评论图片
     *
     * @access  public
     * @param int $user_id
     * @return  void
     */
    public function getCommentImgDel($where = [])
    {
        $res = CommentImg::where('user_id', $where['user_id']);

        if (isset($where['comment_id'])) {
            $res = $res->where('comment_id', $where['comment_id']);
        }

        $res->delete();
    }

    /**
     * 评论晒单
     * @param type $user_id
     * @param type $type count,list标识
     * @param type $sign 0：带评论 1：追加图片 2:已评论
     * @param type $size
     * @param type $start
     * @return type
     */
    public function getOrderCommentList($user_id, $sign = 0, $rec_id = 0)
    {
        $row = OrderGoods::select('rec_id', 'order_id', 'goods_id', 'goods_name', 'ru_id', 'goods_number', 'goods_price');

        if ($rec_id) {
            $row = $row->where('rec_id', $rec_id);
        }

        $orderWhere = [
            'user_id' => $user_id,
            'rec_id' => $rec_id
        ];
        $row = $row->whereHas('getOrder', function ($query) use ($orderWhere) {
            if (!$orderWhere['rec_id']) {
                $query = $query->where('main_count', 0);
            }

            $query->where('user_id', $orderWhere['user_id']);
        });

        $where = [
            'user_id' => $user_id,
            'sign' => $sign
        ];
        $row = $row->where(function ($query) use ($where) {
            $query->goodsCommentCount($where['user_id'], 0, $where['sign']);
        });

        $row = $row->with([
            'getOrder' => function ($query) {
                $query->select('order_id', 'add_time', 'order_sn');
            },
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_thumb', 'goods_product_tag');
            }
        ]);

        $row = $this->baseRepository->getToArrayFirst($row);

        if ($row) {
            $row = $row['get_order'] ? array_merge($row, $row['get_order']) : $row;
            $row = $row['get_goods'] ? array_merge($row, $row['get_goods']) : $row;

            $row['add_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $row['add_time']);
            $row['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
            $row['impression_list'] = !empty($row['goods_product_tag']) ? explode(',', $row['goods_product_tag']) : [];
            $row['goods_url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
            $row['goods_price'] = $this->dscRepository->getPriceFormat($row['goods_price']);

            //订单商品评论信息
            $row['comment'] = $this->getOrderGoodsComment($row['goods_id'], $row['rec_id'], $user_id);
        }

        return $row;
    }

    /**
     * 添加评论内容
     *
     * @param $cmt
     * @return mixed
     */
    public function addComment($cmt)
    {
        /* 评论是否需要审核 */
        $status = 1 - $this->config['comment_check'];

        $user_id = session('user_id', 0);
        $email = isset($cmt->email) && !empty($cmt->email) ? trim($cmt->email) : addslashes(session('email', ''));
        $user_name = empty($cmt->username) ? session('user_name', '') : '';
        $email = addslashes($email);
        $user_name = addslashes($user_name);

        $ru_id = Goods::where('goods_id', $cmt->id)->value('user_id');

        /* 保存评论内容 */
        $other = [
            'comment_type' => $cmt->type,
            'id_value' => $cmt->id,
            'email' => $email,
            'user_name' => $user_name,
            'content' => $cmt->content,
            'comment_rank' => $cmt->rank,
            'comment_server' => $cmt->server,
            'comment_delivery' => $cmt->delivery,
            'add_time' => $this->timeRepository->getGmTime(),
            'ip_address' => request()->getClientIp(),
            'status' => $status,
            'parent_id' => 0,
            'user_id' => $user_id,
            'ru_id' => $ru_id
        ];

        $comment_id = Comment::insertGetId($other);

        return $comment_id;
    }

    /**
     *  获取评论内容信息
     *
     * @access  public
     * @param int $comment_id
     * @return  array
     */
    public function getCommentInfo($comment_id = 0)
    {
        $res = Comment::where('comment_id', $comment_id)->first();

        return $res ? $res->toArray() : [];
    }

    /**
     * 获取商品评论数量
     * @param $goods_id
     * @return mixed
     */
    public function goodsCommentCount($goods_id)
    {
        $model = Comment::where('id_value', $goods_id)
            ->where('parent_id', 0)
            ->where('status', 1);
        $list['all'] = $model->whereIn('comment_rank', [0, 1, 2, 3, 4, 5])->count();// 全部评价

        $model = Comment::where('id_value', $goods_id)
            ->where('parent_id', 0)
            ->where('status', 1);
        $list['good'] = $model->whereIn('comment_rank', [4, 5])->count();//好评

        $model = Comment::where('id_value', $goods_id)
            ->where('parent_id', 0)
            ->where('status', 1);
        $list['in'] = $model->whereIn('comment_rank', [2, 3])->count();//中评

        $model = Comment::where('id_value', $goods_id)
            ->where('parent_id', 0)
            ->where('status', 1);
        $list['rotten'] = $model->whereIn('comment_rank', [0, 1])->count();//差评

        $list['img'] = Comment::from('comment as c')
            ->leftjoin('comment_img as ci', 'c.comment_id', '=', 'ci.comment_id')
            ->where('c.id_value', $goods_id)
            ->where('c.parent_id', 0)
            ->where('c.status', 1)
            ->whereIn('comment_rank', [0, 1, 2, 3, 4, 5])
            ->where('ci.comment_img', '!=', '')
            ->count();// 有图评价

        return $list;
    }

    /**
     * 商品评论
     *
     * @param int $uid
     * @param $goods_id
     * @param string $rank
     * @param int $page
     * @param int $size
     * @return array
     */
    public function GoodsComment($uid = 0, $goods_id, $rank = '', $page = 1, $size = 10)
    {
        $res = Comment::where('id_value', $goods_id)
            ->where('parent_id', 0)
            ->where('status', 1);

        if ($uid > 0) {
            $res = $res->where('user_id', $uid);
        }

        // 关联会员
        $res = $res->with([
            'user' => function ($query) {
                $query->select('user_id', 'user_name', 'nick_name', 'user_picture');
            }
        ]);
        // 关联图片
        $res = $res->with([
            'getCommentImg' => function ($query) {
                $query->select('comment_id', 'id', 'comment_img');
            }
        ]);

        $rank = !empty($rank) ? $rank : 'all';

        if ($rank == 'all') {
            $res = $res->whereIn('comment_rank', [0, 1, 2, 3, 4, 5]);// 全部评价
        } elseif ($rank == 'good') {
            $res = $res->whereIn('comment_rank', [4, 5]);//好评
        } elseif ($rank == 'in') {
            $res = $res->whereIn('comment_rank', [2, 3]);//中评
        } elseif ($rank == 'rotten') {
            $res = $res->whereIn('comment_rank', [0, 1]);//差评
        } elseif ($rank == 'img') {
            $res = $res->whereHas('getCommentImg', function ($query) {
                $query->where('comment_img', '!=', '');
            });
            $res = $res->whereIn('comment_rank', [0, 1, 2, 3, 4, 5]);
        }

        $res = $res->orderBy('add_time', 'desc')
            ->offset(($page - 1) * $size)
            ->limit($size)
            ->get();
        $res = $res ? $res->toArray() : [];

        $commentlist = [];
        if ($res) {
            foreach ($res as $k => $v) {
                $commentlist[$k]['add_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $v['add_time']);
                $commentlist[$k]['content'] = $v['content'];
                $commentlist[$k]['rank'] = $v['comment_rank'];
                $commentlist[$k]['user_name'] = $v['user']['nick_name'] ? setAnonymous($v['user']['nick_name']) : setAnonymous($v['user']['user_name']);

                if (isset($v['user']['user_picture']) && $v['user']['user_picture']) {
                    $commentlist[$k]['user_picture'] = $this->dscRepository->getImagePath($v['user']['user_picture']);
                } else {
                    $user_default = $this->dscRepository->dscUrl('img/user_default.png');
                    $commentlist[$k]['user_picture'] = $this->dscRepository->getImagePath($user_default);
                }

                // 商品订单信息
                $goods = OrderGoods::select('goods_attr', 'goods_id', 'goods_name')
                    ->where('rec_id', $v['rec_id'])
                    ->where('goods_id', $v['id_value'])
                    ->get();
                $goods = $goods ? $goods->toArray() : [];
                $commentlist[$k]['goods'] = $goods;

                // 回复评论
                $re = Comment::select('user_name', 'content', 'add_time')
                    ->where('parent_id', $v['comment_id'])
                    ->first();
                $re = $re ? $re->toArray() : [];
                if ($re) {
                    $commentlist[$k]['re_content'] = $re['content'];
                    $commentlist[$k]['re_username'] = $re['user_name'];
                    $commentlist[$k]['re_add_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $re['add_time']);
                }

                // 处理图片
                $commentlist[$k]['comment_img'] = '';
                if (isset($v['get_comment_img']) && $v['get_comment_img']) {
                    $img = [];
                    foreach ($v['get_comment_img'] as $key => $val) {
                        $img[$key] = $this->dscRepository->getImagePath($val['comment_img']);
                    }
                    $commentlist[$k]['comment_img'] = $img;
                } else {
                    if ($rank == 'img') {
                        unset($commentlist[$k]);
                    }
                }
            }

            ksort($commentlist);
        }

        return $commentlist;
    }

    /**
     * 评论晒单
     * @param int $user_id
     * @param int $sign 0：带评论 1：追加图片 2:已评论
     * @param int $order_id
     * @param int $size
     * @param int $start
     * @return
     */
    public function getUserOrderCommentCount($user_id, $sign = 0, $order_id = 0)
    {
        $where = [
            'user_id' => $user_id,
            'order_id' => $order_id,
            'order_status' => [OS_CONFIRMED, OS_SPLITED],
            'shipping_status' => SS_RECEIVED,
            'pay_status' => [PS_PAYED, PS_PAYING],
            'sign' => $sign
        ];

        $res = OrderGoods::whereHas('getOrder', function ($query) use ($where) {
            $query = $query->where('user_id', $where['user_id'])
                ->whereIn('order_status', $where['order_status'])
                ->where('shipping_status', $where['shipping_status'])
                ->whereIn('pay_status', $where['pay_status']);

            if (isset($where['order_id']) && $where['order_id']) {
                $query->where('order_id', $where['order_id']);
            } else {
                $query->where('main_count', 0);
            }
        });

        $res = $res->whereHas('getGoods');

        $res = $res->where(function ($query) use ($where) {
            $query->goodsCommentCount($where['user_id'], $where['order_id'], $where['sign']);
        });

        $res = $res->count();

        return $res;
    }

    /**
     * 评论晒单
     * @param int $user_id
     * @param int $sign 0：带评论 1：追加图片 2:已评论
     * @param int $order_id
     * @param int $size
     * @param int $start
     * @return
     */
    public function getUserOrderCommentList($user_id, $sign = 0, $order_id = 0, $size = 0, $start = 0)
    {
        $where = [
            'user_id' => $user_id,
            'order_id' => $order_id,
            'order_status' => [OS_CONFIRMED, OS_SPLITED],
            'shipping_status' => SS_RECEIVED,
            'pay_status' => [PS_PAYED, PS_PAYING],
            'sign' => $sign
        ];

        $res = OrderGoods::whereHas('getOrder', function ($query) use ($where) {
            $query = $query->where('user_id', $where['user_id'])
                ->whereIn('order_status', $where['order_status'])
                ->where('shipping_status', $where['shipping_status'])
                ->whereIn('pay_status', $where['pay_status']);

            if (isset($where['order_id']) && $where['order_id']) {
                $query->where('order_id', $where['order_id']);
            } else {
                $query->where('main_count', 0);
            }
        });

        $res = $res->whereHas('getGoods');

        $res = $res->where(function ($query) use ($where) {
            $query->goodsCommentCount($where['user_id'], $where['order_id'], $where['sign']);
        });

        $res = $res->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_thumb', 'goods_product_tag');
            },
            'getOrder' => function ($query) {
                $query->select('order_id', 'order_sn', 'add_time');
            }
        ]);

        $res = $res->orderBy('rec_id', 'desc');

        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $key => $row) {
                if ($row && $row['get_goods']) {
                    $row = array_merge($row, $row['get_goods']);
                }

                if ($row && $row['get_order']) {
                    $row = array_merge($row, $row['get_order']);
                }

                $row['add_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $row['add_time']);
                $row['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $row['impression_list'] = !empty($row['goods_product_tag']) ? explode(',', $row['goods_product_tag']) : [];
                $row['goods_price'] = $this->dscRepository->getPriceFormat($row['goods_price']);

                //订单商品评论信息

                $res[$key] = $row;
            }
        }

        return $res;
    }

    /**
     * 评论页商品
     * @param $rec_id
     * @return array
     */
    public function getOrderGoods($rec_id)
    {
        $ordergoods = OrderGoods::where('rec_id', $rec_id)
            ->with([
                'getGoods' => function ($query) {
                    $query->select('goods_id', 'shop_price', 'goods_thumb');
                }
            ]);

        $ordergoods = $this->baseRepository->getToArrayFirst($ordergoods);

        if ($ordergoods) {

            $goods = $ordergoods['get_goods'];

            if ($goods) {
                $ordergoods['shop_price'] = $this->dscRepository->getPriceFormat($goods['shop_price']);
                $ordergoods['goods_thumb'] = $this->dscRepository->getImagePath($goods['goods_thumb']);
            }
        }

        return $ordergoods;
    }

    /**
     * 添加评论内容
     * @param $user_id
     * @param $cmt
     * @param array $pic
     * @return mixed
     */
    public function getAddGoodsComment($user_id, $cmt, $pic = [])
    {
        /* 评论是否需要审核 */
        $status = 1 - $this->config['comment_check'];

        $user_info = Users::where('user_id', $user_id)
            ->first();
        $user_info = $user_info ? $user_info->toArray() : '';

        $user_name = isset($user_info['user_name']) ? addslashes($user_info['user_name']) : '';
        $email = isset($user_info['email']) ? addslashes($user_info['email']) : '';

        $ru_id = Goods::where('goods_id', $cmt['id'])->value('user_id');

        /* 保存评论内容 */
        $other = [
            'comment_type' => $cmt['type'],
            'id_value' => $cmt['id'],
            'email' => $email,
            'user_name' => $user_name,
            'content' => $cmt['content'] ? $cmt['content'] : '',
            'comment_rank' => $cmt['rank'],
            'comment_server' => $cmt['server'],
            'comment_delivery' => $cmt['delivery'],
            'add_time' => $this->timeRepository->getGmTime(),
            'ip_address' => request()->getClientIp(),
            'status' => $status,
            'parent_id' => 0,
            'user_id' => $user_id,
            'ru_id' => $ru_id,
            'order_id' => $cmt['order_id'],
            'rec_id' => $cmt['rec_id']
        ];

        $comment_id = Comment::insertGetId($other);

        if ($pic) {
            /* 保存评论内容 */
            foreach ($pic as $k => $v) {
                $other = [
                    'user_id' => $user_id,
                    'order_id' => $cmt['order_id'],
                    'rec_id' => $cmt['rec_id'],
                    'goods_id' => $cmt['id'],
                    'comment_id' => $comment_id,
                    'comment_img' => $v,
                    'img_thumb' => $v,
                    'cont_desc' => ''
                ];

                CommentImg::insertGetId($other);
            }
        }

        $this->orderCommonService->getUserOrderNumServer($user_id);

        $result['error'] = 0;
        $result['comment_id'] = $comment_id;
        $result['msg'] = 'success';

        return $result;
    }

    /**
     * 获得商品评论总条数
     *
     * @param $goods_id
     * @param string $type
     * @param int $count_type
     * @return int
     */
    public function mentsCountAll($goods_id, $type = 'comment_rank', $count_type = 0)
    {
        $count = Comment::where('id_value', $goods_id)
            ->where('status', 1)
            ->where('parent_id', 0)
            ->whereIn($type, [1, 2, 3, 4, 5])
            ->count();

        if ($count == 0) {
            if ($count_type == 0) {
                $count = 1;
            } else {
                $count = 0;
            }

            return $count;
        } else {
            return $count;
        }
    }

    /**
     * 获得商品评论-$num-颗星总条数
     *
     * @param $goods_id
     * @param $num
     * @param string $type
     * @return mixed
     */
    public function mentsCountRankNum($goods_id, $num, $type = 'comment_rank')
    {
        $count = Comment::where('id_value', $goods_id)
            ->where('status', 1)
            ->where('parent_id', 0)
            ->where($type, $num)
            ->count();

        return $count;
    }

    /**
     * 获得商品评论显示星星
     *
     * @param null $all
     * @param null $one
     * @param null $two
     * @param null $three
     * @param null $four
     * @param null $five
     * @param string $baseline
     * @return array
     */
    public function getConmentsStars($all = null, $one = null, $two = null, $three = null, $four = null, $five = null, $baseline = '')
    {
        $one_num = 1;
        $two_num = 2;
        $three_num = 3;
        $four_num = 4;
        $five_num = 5;
        $allNmu = $all * 5;                         //总星星数
        $oneAll = $one * $one_num;           //1颗总星星数
        $twoAll = $two * $two_num;           //2颗总星星数
        $threeAll = $three * $three_num;            //3颗总星星数
        $fourAll = $four * $four_num;          //4颗总星星数
        $fiveAll = $five * $five_num;         //5颗总星星数
        $allStars = $oneAll + $twoAll + $threeAll + $fourAll + $fiveAll;  //显示总星星数

        $badReview = $one / $all;          //差评条数
        $middleReview = ($two + $three) / $all;       //中评条数
        $goodReview = ($four + $five) / $all;        //好评条数

        $badmen = $one;            //差评人数
        $middlemen = $two + $three;          //中评人数
        $goodmen = $four + $five;          //好评人数
        $allmen = $one + $two + $three + $four + $five;      //全部评分人数

        $percentage = sprintf("%.2f", ($allStars / $allNmu * 100));

        $arr = [
            'score' => sprintf("%.2f", (round($percentage / 20, 2))), //分数
            'badReview' => round($badReview, 2) * 100, //差评百分比
            'middlReview' => round($middleReview, 2) * 100, //中评百分比
            'goodReview' => round($goodReview, 2) * 100, //好评百分比
            'allReview' => $percentage, //总体百分比
            'badmen' => $badmen, //差评人数
            'middlemen' => $middlemen, //中评人数
            'goodmen' => $goodmen, //好评人数
            'allmen' => $allmen, //全部评论人数
        ];

        if ($percentage >= 1 && $percentage < 40) {               //1颗星
            $arr['stars'] = 1;
        } elseif ($percentage >= 40 && $percentage < 60) {  //2颗星
            $arr['stars'] = 2;
        } elseif ($percentage >= 60 && $percentage < 80) {  //3颗星
            $arr['stars'] = 3;
        } elseif ($percentage >= 80 && $percentage < 100) {  //4颗星
            $arr['stars'] = 4;
        } elseif ($percentage == 100) {
            $arr['score'] = 5;
            $arr['stars'] = 5;
            $arr['badReview'] = 0;        //差评百分比
            $arr['middlReview'] = 0;        //中评百分比
            $arr['goodReview'] = 100;        //好评百分比
            $arr['allReview'] = 100;       //总体百分比
            return $arr;
        } else { //默认状态 --没有评论时
            $arr = [
                'score' => 5, //分数
                'stars' => 5, //星数
                'badReview' => 0, //差评百分比
                'middlReview' => 0, //中评百分比
                'goodReview' => 100, //好评百分比
                'allReview' => 100, //总体百分比
                'allmen' => 0, //全部评论人数
                'badmen' => 0, //差评人数
                'middlemen' => 0, //中评人数
                'goodmen' => 0, //好评人数
            ];
        }

        $review = $arr['badReview'] + $arr['middlReview'] + $arr['goodReview'];

        //计算判断是否超出100值，如有超出则按最大值减去超出值
        if ($review > 100) {
            $review = $review - 100;
            $maxReview = max($arr['badReview'], $arr['middlReview'], $arr['goodReview']);

            if ($maxReview == $arr['badReview']) {
                $arr['badReview'] = $arr['badReview'] - $review;
            } elseif ($maxReview == $arr['middlReview']) {
                $arr['middlReview'] = $arr['middlReview'] - $review;
            } elseif ($maxReview == $arr['goodReview']) {
                $arr['goodReview'] = $arr['goodReview'] - $review;
            }
        }

        $arr['left'] = $arr['stars'] * 18;

        if ($baseline) {
            $res = CommentBaseline::selectRaw($baseline)->whereRaw(1);
            $res = $this->baseRepository->getToArrayFirst($res);

            $baseline = $res && isset($res[$baseline]) ? $res[$baseline] : 0;

            $arr['up_down'] = $arr['goodReview'] - $baseline;

            if ($arr['up_down'] > $baseline) {
                $arr['is_status'] = 1; //高于
            } elseif ($arr['up_down'] < $baseline) {
                $arr['is_status'] = 0; //低于
                $arr['up_down'] = abs($arr['up_down']);
            } else {
                $arr['is_status'] = 2; //持平
            }
        }
        return $arr;
    }

    /**
     * 商品评论百分比，及数量统计
     *
     * @param $goods_id
     * @return array
     */
    public function getCommentsPercent($goods_id)
    {
        $arr = [
            'score' => 5, //分数
            'stars' => 5, //星数
            'badReview' => 0, //差评百分比
            'middlReview' => 0, //中评百分比
            'goodReview' => 100, //好评百分比
            'allReview' => 100, //总体百分比
            'allmen' => 0, //全部评论人数
            'badmen' => 0, //差评人数
            'middlemen' => 0, //中评人数
            'goodmen' => 0, //好评人数
        ];

        $count = Comment::where('id_value', $goods_id)
            ->where('status', 1)
            ->where('parent_id', 0)
            ->count();

        $arr['allmen'] = $count;

        if ($arr['allmen'] == 0) {
            return $arr;
        } else {
            $mc_one = $this->mentsCountRankNum($goods_id, 1);  //一颗星
            $mc_two = $this->mentsCountRankNum($goods_id, 2);     //两颗星
            $mc_three = $this->mentsCountRankNum($goods_id, 3);    //三颗星
            $mc_four = $this->mentsCountRankNum($goods_id, 4);  //四颗星
            $mc_five = $this->mentsCountRankNum($goods_id, 5);  //五颗星

            $arr['goodmen'] = $mc_four + $mc_five;
            $arr['middlemen'] = $mc_two + $mc_three;
            $arr['badmen'] = $mc_one;

            $arr['goodReview'] = round(($arr['goodmen'] / $arr['allmen']) * 100, 1);
            $arr['middlReview'] = round(($arr['middlemen'] / $arr['allmen']) * 100, 1);
            $arr['badReview'] = round(($arr['badmen'] / $arr['allmen']) * 100, 1);

            return $arr;
        }
    }

    /**
     * 获取商家所有商品评分类型汇总
     *
     * @param $ru_id
     * @return array
     * @throws \Exception
     */
    public function getMerchantsGoodsComment($ru_id)
    {

        $arr = [];
        if ($ru_id) {
            $cache_name = 'seller_comment_' . $ru_id;

            $seller_cmt = cache($cache_name);
            $arr = !is_null($seller_cmt) ? $seller_cmt : false;

            if ($arr === false) {
                $res = MerchantsShopInformation::where('user_id', $ru_id)->take(1);
                $res = $this->baseRepository->getToArrayGet($res);

                foreach ($res as $key => $row) {
                    $arr[$key] = $row;

                    //商品评分
                    $arr[$key]['mc_all_Rank'] = $this->sellerMentsCountAll($row['user_id'], 'desc_rank');       //总条数
                    $arr[$key]['mc_one_Rank'] = $this->sellerMentsCountRankNum($row['user_id'], 1, 'desc_rank');  //一颗星
                    $arr[$key]['mc_two_Rank'] = $this->sellerMentsCountRankNum($row['user_id'], 2, 'desc_rank');     //两颗星
                    $arr[$key]['mc_three_Rank'] = $this->sellerMentsCountRankNum($row['user_id'], 3, 'desc_rank');    //三颗星
                    $arr[$key]['mc_four_Rank'] = $this->sellerMentsCountRankNum($row['user_id'], 4, 'desc_rank');  //四颗星
                    $arr[$key]['mc_five_Rank'] = $this->sellerMentsCountRankNum($row['user_id'], 5, 'desc_rank');  //五颗星
                    //服务评分
                    $arr[$key]['mc_all_Server'] = $this->sellerMentsCountAll($row['user_id'], 'service_rank');       //总条数
                    $arr[$key]['mc_one_Server'] = $this->sellerMentsCountRankNum($row['user_id'], 1, 'service_rank');  //一颗星
                    $arr[$key]['mc_two_Server'] = $this->sellerMentsCountRankNum($row['user_id'], 2, 'service_rank');     //两颗星
                    $arr[$key]['mc_three_Server'] = $this->sellerMentsCountRankNum($row['user_id'], 3, 'service_rank');    //三颗星
                    $arr[$key]['mc_four_Server'] = $this->sellerMentsCountRankNum($row['user_id'], 4, 'service_rank');  //四颗星
                    $arr[$key]['mc_five_Server'] = $this->sellerMentsCountRankNum($row['user_id'], 5, 'service_rank');  //五颗星
                    //时效评分
                    $arr[$key]['mc_all_Delivery'] = $this->sellerMentsCountAll($row['user_id'], 'delivery_rank');       //总条数
                    $arr[$key]['mc_one_Delivery'] = $this->sellerMentsCountRankNum($row['user_id'], 1, 'delivery_rank');  //一颗星
                    $arr[$key]['mc_two_Delivery'] = $this->sellerMentsCountRankNum($row['user_id'], 2, 'delivery_rank');     //两颗星
                    $arr[$key]['mc_three_Delivery'] = $this->sellerMentsCountRankNum($row['user_id'], 3, 'delivery_rank');    //三颗星
                    $arr[$key]['mc_four_Delivery'] = $this->sellerMentsCountRankNum($row['user_id'], 4, 'delivery_rank');  //四颗星
                    $arr[$key]['mc_five_Delivery'] = $this->sellerMentsCountRankNum($row['user_id'], 5, 'delivery_rank');  //五颗星

                    $sid = CommentSeller::where('ru_id', $row['user_id'])->value('sid');

                    if ($sid > 0) {

                        //商品评分
                        @$arr['commentRank']['mc_all'] += $arr[$key]['mc_all_Rank'];
                        @$arr['commentRank']['mc_one'] += $arr[$key]['mc_one_Rank'];
                        @$arr['commentRank']['mc_two'] += $arr[$key]['mc_two_Rank'];
                        @$arr['commentRank']['mc_three'] += $arr[$key]['mc_three_Rank'];
                        @$arr['commentRank']['mc_four'] += $arr[$key]['mc_four_Rank'];
                        @$arr['commentRank']['mc_five'] += $arr[$key]['mc_five_Rank'];

                        //服务评分
                        @$arr['commentServer']['mc_all'] += $arr[$key]['mc_all_Server'];
                        @$arr['commentServer']['mc_one'] += $arr[$key]['mc_one_Server'];
                        @$arr['commentServer']['mc_two'] += $arr[$key]['mc_two_Server'];
                        @$arr['commentServer']['mc_three'] += $arr[$key]['mc_three_Server'];
                        @$arr['commentServer']['mc_four'] += $arr[$key]['mc_four_Server'];
                        @$arr['commentServer']['mc_five'] += $arr[$key]['mc_five_Server'];

                        //时效评分
                        @$arr['commentDelivery']['mc_all'] += $arr[$key]['mc_all_Delivery'];
                        @$arr['commentDelivery']['mc_one'] += $arr[$key]['mc_one_Delivery'];
                        @$arr['commentDelivery']['mc_two'] += $arr[$key]['mc_two_Delivery'];
                        @$arr['commentDelivery']['mc_three'] += $arr[$key]['mc_three_Delivery'];
                        @$arr['commentDelivery']['mc_four'] += $arr[$key]['mc_four_Delivery'];
                        @$arr['commentDelivery']['mc_five'] += $arr[$key]['mc_five_Delivery'];
                    }
                }

                @$arr['cmt']['commentRank']['zconments'] = $this->getConmentsStars($arr['commentRank']['mc_all'], $arr['commentRank']['mc_one'], $arr['commentRank']['mc_two'], $arr['commentRank']['mc_three'], $arr['commentRank']['mc_four'], $arr['commentRank']['mc_five'], 'goods');
                @$arr['cmt']['commentServer']['zconments'] = $this->getConmentsStars($arr['commentServer']['mc_all'], $arr['commentServer']['mc_one'], $arr['commentServer']['mc_two'], $arr['commentServer']['mc_three'], $arr['commentServer']['mc_four'], $arr['commentServer']['mc_five'], 'service');
                @$arr['cmt']['commentDelivery']['zconments'] = $this->getConmentsStars($arr['commentDelivery']['mc_all'], $arr['commentDelivery']['mc_one'], $arr['commentDelivery']['mc_two'], $arr['commentDelivery']['mc_three'], $arr['commentDelivery']['mc_four'], $arr['commentDelivery']['mc_five'], 'shipping');

                @$arr['cmt']['all_zconments']['score'] = sprintf("%.2f", ($arr['cmt']['commentRank']['zconments']['score'] + $arr['cmt']['commentServer']['zconments']['score'] + $arr['cmt']['commentDelivery']['zconments']['score']) / 3);
                @$arr['cmt']['all_zconments']['allReview'] = round((($arr['cmt']['commentRank']['zconments']['allReview'] + $arr['cmt']['commentServer']['zconments']['allReview'] + $arr['cmt']['commentDelivery']['zconments']['allReview']) / 3), 2);
                @$arr['cmt']['all_zconments']['position'] = 100 - $arr['cmt']['all_zconments']['allReview'] - 3;

                cache()->forever($cache_name, $arr);
            }
        }

        return $arr;
    }

    /**
     * 获得订单商品评论总条数
     *
     * @param $ru_id
     * @param $type
     * @return mixed
     */
    public function sellerMentsCountAll($ru_id, $type)
    {
        return CommentSeller::where('ru_id', $ru_id)->whereIn($type, [1, 2, 3, 4, 5])->count();
    }

    /**
     * 获得商品评论-$num-颗星总条数
     *
     * @param $ru_id
     * @param $num
     * @param $type
     * @return mixed
     */
    public function sellerMentsCountRankNum($ru_id, $num, $type)
    {
        return CommentSeller::where('ru_id', $ru_id)->where($type, $num)->count();
    }

    /**
     * 商品评分数
     *
     * @param int $goods_id
     * @return array
     */
    public function goodsZconments($goods_id = 0)
    {
        $mc_all = $this->mentsCountAll($goods_id);       //总条数
        $mc_one = $this->mentsCountRankNum($goods_id, 1);  //一颗星
        $mc_two = $this->mentsCountRankNum($goods_id, 2);     //两颗星
        $mc_three = $this->mentsCountRankNum($goods_id, 3);    //三颗星
        $mc_four = $this->mentsCountRankNum($goods_id, 4);  //四颗星
        $mc_five = $this->mentsCountRankNum($goods_id, 5);  //五颗星
        $zconments = $this->getConmentsStars($mc_all, $mc_one, $mc_two, $mc_three, $mc_four, $mc_five);

        return $zconments;
    }

    /**
     * 查询商品评论数
     *
     * @param $goods_id
     * @param int $cmtType
     * @return mixed
     */
    public function getGoodsCommentCount($goods_id, $cmtType = 0)
    {
        $count = Comment::where('id_value', $goods_id)
            ->where('comment_type', 0)
            ->where('status', 1)
            ->where('parent_id', 0);

        //好评
        if ($cmtType == 1) {
            $count = $count->whereIn('comment_rank', [5, 4]);
        } //中评
        elseif ($cmtType == 2) {
            $count = $count->whereIn('comment_rank', [3, 2]);
        } //差评
        elseif ($cmtType == 3) {
            $count = $count->where('comment_rank', 1);
        }

        $count = $count->count();

        return $count;
    }

    /**
     * 获取订单商品评论
     *
     * @param int $goods_id
     * @param int $rec_id
     * @param int $user_id
     * @return mixed
     */
    public function getOrderGoodsComment($goods_id = 0, $rec_id = 0, $user_id = 0)
    {

        $res = Comment::where('comment_type', 0)
            ->where('id_value', $goods_id)
            ->where('rec_id', $rec_id)
            ->where('parent_id', 0)
            ->where('user_id', $user_id);

        $res = $this->baseRepository->getToArrayFirst($res);

        if ($res) {
            $res['content'] = nl2br(str_replace('\n', '<br />', htmlspecialchars($res['content'])));

            $res['goods_tag'] = !empty($res['goods_tag']) ? explode(',', $res['goods_tag']) : [];

            $where = [
                'goods_id' => $goods_id,
                'comment_id' => $res['comment_id']
            ];
            $img_list = $this->getCommentImgList($where);

            $res['comment_id'] = isset($res['comment_id']) && !empty($res['comment_id']) ? $res['comment_id'] : 0;
            $res['img_list'] = $img_list;
        }

        return $res;
    }

    /**
     * 查询会员回复信息列表
     *
     * @param int $goods_id
     * @param int $comment_id
     * @param int $type
     * @param int $reply_page
     * @param int $libType
     * @param int $reply_size
     * @return array
     */
    public function getReplyList($goods_id = 0, $comment_id = 0, $type = 0, $reply_page = 1, $libType = 0, $reply_size = 2)
    {
        $reply_pager = [];
        $reply_count = 0;

        if ($type == 1) {
            $reply_list = Comment::where('id_value', $goods_id)
                ->where('parent_id', $comment_id)
                ->where('user_id', session('user_id'))
                ->where('status', 0)
                ->orderBy('comment_id', 'desc');

            $reply_list = $this->baseRepository->getToArrayGet($reply_list);
        } else {
            $reply_count = Comment::where('id_value', $goods_id)
                ->where('parent_id', $comment_id)
                ->where('user_id', '>', 0)
                ->where('status', 1)
                ->count();

            $id = '"' . $goods_id . "|" . $comment_id . '"';

            $pagerParams = [
                'total' => $reply_count,
                'listRows' => $reply_size,
                'id' => $id,
                'page' => $reply_page,
                'funName' => 'reply_comment_gotoPage',
                'pageType' => 1,
                'libType' => $libType,
                'cfigType' => 1
            ];
            $reply_comment = new Pager($pagerParams);

            $reply_pager = $reply_comment->fpage([0, 4, 5, 6, 9]);

            $reply_list = Comment::where('id_value', $goods_id)
                ->where('parent_id', $comment_id)
                ->where('user_id', '>', 0)
                ->where('status', 1)
                ->orderBy('comment_id', 'desc');

            $start = ($reply_page - 1) * $reply_size;

            if ($start > 0) {
                $reply_list = $reply_list->skip($start);
            }

            if ($reply_size > 0) {
                $reply_list = $reply_list->take($reply_size);
            }

            $reply_list = $this->baseRepository->getToArrayGet($reply_list);

            if ($reply_page == 1) {
                $floor = 0;
            } else {
                $floor = ($reply_page - 1) * $reply_size;
            }

            if ($reply_list) {
                foreach ($reply_list as $key => $row) {
                    $floor = $floor + 1;
                    $reply_list[$key]['floor'] = $floor;

                    $reply_list[$key]['add_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $row['add_time']);
                    $reply_list[$key]['content'] = nl2br(str_replace('\n', '<br />', htmlspecialchars($row['content'])));
                }
            }
        }

        $arr = ['reply_list' => $reply_list, 'reply_pager' => $reply_pager, 'reply_count' => $reply_count, 'reply_size' => $reply_size];

        return $arr;
    }

    /**
     * 文章评论列表
     * @param $article_id
     * @param int $page
     * @param int $size
     * @return array
     */
    public function getArticleCommentList($article_id, $page = 1, $size = 10)
    {
        $begin = ($page - 1) * $size;

        $comment_list = Comment::where('id_value', $article_id)
            ->where('comment_type', 1)
            ->where('status', 1);

        $comment_list = $comment_list->with([
            'user'
        ]);
        $comment_list = $comment_list->orderBy('add_time', 'desc')
            ->offset($begin)
            ->limit($size);

        $comment_list = $this->baseRepository->getToArrayGet($comment_list);

        if ($comment_list) {
            foreach ($comment_list as $key => $val) {
                $user_name = $val['user']['nick_name'] ?? '';

                //iconv_strlen计算有多少个字符,不是字节长度
                $name_len = ceil(iconv_strlen($user_name, 'UTF-8') / 3);
                if ($name_len > 2) {
                    $name_len = 3;
                } elseif ($name_len == 2) {
                    $name_len = 1;
                } else {
                    $user_name .= '*';
                    $name_len = 1;
                }

                $comment_list[$key]['user_name'] = $this->dscRepository->stringToStar($user_name, $name_len, 6);

                $comment_list[$key]['add_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $val['add_time']);
                $user_picture = $val['user']['user_picture'] ?? '';
                $comment_list[$key]['user_picture'] = $this->dscRepository->getImagePath($user_picture);
            }
        }

        return $comment_list;
    }


    /**
     * 查询评论内容
     *
     * @param $id
     * @param $type
     * @param int $page
     * @return array
     * @throws \Exception
     */
    public function getAssignArticleComment($id, $type, $page = 1)
    {
        $tag = [];

        /* 取得评论列表 */
        $count = Comment::where('id_value', $id)
            ->where('comment_type', $type)
            ->where('status', 1)
            ->where('parent_id', 0);

        $count = $count->count();

        $size = !empty($this->config['comments_number']) ? $this->config['comments_number'] : 5;

        $pagerParams = [
            'total' => $count,
            'listRows' => $size,
            'id' => $id,
            'page' => $page,
            'funName' => 'gotoPage',
            'pageType' => 1
        ];
        $comment = new Pager($pagerParams);
        $pager = $comment->fpage([0, 4, 5, 6, 9]);

        $res = Comment::where('id_value', $id)
            ->where('comment_type', $type)
            ->where('status', 1)
            ->where('parent_id', 0);

        $res = $res->with([
            'user'
        ]);

        $start = ($page - 1) * $size;
        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = $res->orderBy('add_time', 'desc');

        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        $ids = '';
        if ($res) {
            foreach ($res as $row) {
                //处理用户名 by wu
                //iconv_strlen计算有多少个字符,不是字节长度
                $name_len = ceil(iconv_strlen($row['user_name'], 'UTF-8') / 3);
                if ($name_len > 2) {
                    $name_len = 3;
                } elseif ($name_len == 2) {
                    $name_len = 1;
                } else {
                    $row['user_name'] .= '*';
                    $name_len = 1;
                }

                $row['user_name'] = $this->dscRepository->stringToStar($row['user_name'], $name_len, 3);

                $ids .= $ids ? ",$row[comment_id]" : $row['comment_id'];
                $arr[$row['comment_id']]['id'] = $row['comment_id'];
                $arr[$row['comment_id']]['email'] = $row['email'];
                $arr[$row['comment_id']]['username'] = $row['user_name'];
                $arr[$row['comment_id']]['user_id'] = $row['user_id'];
                $arr[$row['comment_id']]['id_value'] = $row['id_value'];
                $arr[$row['comment_id']]['useful'] = $row['useful'];
                $arr[$row['comment_id']]['status'] = $row['status'];
                $user_picture = $row['user']['user_picture'] ?? '';
                $arr[$row['comment_id']]['user_picture'] = $this->dscRepository->getImagePath($user_picture);

                $arr[$row['comment_id']]['content'] = nl2br(str_replace('\n', '<br />', htmlspecialchars($row['content'])));
                $arr[$row['comment_id']]['rank'] = $row['comment_rank'];
                $arr[$row['comment_id']]['server'] = $row['comment_server'];
                $arr[$row['comment_id']]['delivery'] = $row['comment_delivery'];
                $arr[$row['comment_id']]['add_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $row['add_time']);
                $arr[$row['comment_id']]['buy_goods'] = $this->orderGoodsService->getUserBuyGoodsOrder($row['id_value'], $row['user_id'], $row['order_id']);

                //商品印象
                if ($row['goods_tag']) {
                    $row['goods_tag'] = explode(",", $row['goods_tag']);
                    foreach ($row['goods_tag'] as $key => $val) {
                        $tag[$key]['txt'] = $val;
                        //印象数量
                        $tag[$key]['num'] = $this->goodsCommentService->commentGoodsTagNum($row['id_value'], $val);
                    }
                    $arr[$row['comment_id']]['goods_tag'] = $tag;
                }

                $reply = $this->getReplyList($row['id_value'], $row['comment_id']);
                $arr[$row['comment_id']]['reply_list'] = $reply['reply_list'];
                $arr[$row['comment_id']]['reply_count'] = $reply['reply_count'];
                $arr[$row['comment_id']]['reply_size'] = $reply['reply_size'];
                $arr[$row['comment_id']]['reply_pager'] = $reply['reply_pager'];

                $where = [
                    'goods_id' => $row['id_value'],
                    'comment_id' => $row['comment_id']
                ];
                $img_list = $this->getCommentImgList($where);

                $arr[$row['comment_id']]['img_list'] = $img_list;
                $arr[$row['comment_id']]['img_cont'] = count($img_list);

                $arr[$row['comment_id']]['user_picture'] = $this->dscRepository->getImagePath($arr[$row['comment_id']]['user_picture']);
            }
        }

        /* 取得已有回复的评论 */
        if ($ids) {
            $ids = !is_array($ids) ? explode(",", $ids) : $ids;
            $res = Comment::whereIn('parent_id', $ids)->get();
            $res = $res ? $res->toArray() : [];
            if ($res) {
                foreach ($res as $row) {
                    $arr[$row['parent_id']]['re_content'] = nl2br(str_replace('\n', '<br />', htmlspecialchars($row['content'])));
                    $arr[$row['parent_id']]['re_add_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $row['add_time']);
                    $arr[$row['parent_id']]['re_email'] = $row['email'];
                    $arr[$row['parent_id']]['re_username'] = $row['user_name'];
                    $shop_info = $this->merchantCommonService->getShopName($row['ru_id']);
                    $arr[$row['parent_id']]['shop_name'] = $shop_info['shop_name'];
                    $arr[$row['parent_id']]['re_status'] = $row['status'];
                }
            }
        }

        return ['comments' => $arr, 'pager' => $pager, 'count' => $count, 'size' => $size];
    }

    /**
     * 提交评论
     * @param $id
     * @param int $parent_id
     * @param string $content
     * @param int $user_id
     * @return bool
     */
    public function submitComment($id, $parent_id = 0, $content = '', $user_id = 0)
    {
        if (empty($content) || empty($user_id)) {
            return false;
        }

        $time = $this->timeRepository->getGmTime();

        $user_info = Users::where('user_id', $user_id);
        $user_info = $this->baseRepository->getToArrayFirst($user_info);

        //因为平台后台设置,如果需要审核 comment_check值为1
        //status:是否被管理员批准显示，1，是；0，未批准
        $status = $this->config['comment_check'] == 1 ? 0 : 1;

        $data = [
            'content' => $content,
            'user_id' => $user_id,
            'user_name' => isset($user_info['nick_name']) ? $user_info['nick_name'] : (isset($user_info['user_name']) ? $user_info['user_name'] : ''),
            'id_value' => $id,
            'comment_type' => 1,
            'parent_id' => $parent_id,
            'status' => $status,
            'add_time' => $time,
            'ip_address' => request()->getClientIp()
        ];

        return Comment::insertGetId($data);
    }
}
