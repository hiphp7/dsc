<?php

namespace App\Services\CrowdFund;

use App\Models\OrderInfo;
use App\Models\Region;
use App\Models\UserAddress;
use App\Models\Users;
use App\Models\ZcCategory;
use App\Models\ZcFocus;
use App\Models\ZcGoods;
use App\Models\ZcInitiator;
use App\Models\ZcProgress;
use App\Models\ZcProject;
use App\Models\ZcRankLogo;
use App\Models\ZcTopic;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;

/**
 * 众筹商品
 * Class CrowdFund
 * @package App\Services
 */
class CrowdFundService
{
    protected $timeRepository;
    protected $config;
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        TimeRepository $timeRepository,
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 查询众筹父级分类
     *
     * @access  public
     * @return  void
     */
    public function getZcCategoryParents($parent_id = 0, $type = 0)
    {
        $cate_one = ZcCategory::where('parent_id', $parent_id)->get();

        $cate_one = $cate_one ? $cate_one->toArray() : [];

        if ($parent_id) {
            $str_id = $parent_id . ',';
        } else {
            $str_id = '';
        }

        $cate_two = [];
        if ($cate_one) {
            foreach ($cate_one as $c_val) {
                if ($type == 1) {
                    if ($c_val['cat_id']) {
                        $str_id .= $c_val['cat_id'] . ',';
                    }
                }

                $cate = ZcCategory::where('parent_id', $c_val['cat_id'])->get();
                $cate = $cate ? $cate->toArray() : [];

                $cate_two[$c_val['cat_id']] = $cate;

                foreach ($cate_two[$c_val['cat_id']] as $ct_val) {
                    $str_id .= $ct_val['cat_id'] . ',';
                }
            }
        }

        $arr = [
            'cate_one' => $cate_one,
            'cate_two' => $cate_two,
            'str_id' => $this->dscRepository->delStrComma($str_id)
        ];

        return $arr;
    }

    /**
     * 查询众筹项目列表
     *
     * @access  public
     * @return  void
     */
    public function getZcProjectList($keyword = '', $str_id = '', $order = 'id', $page = 1, $size = 10, $type = '')
    {
        $gmtime = $this->timeRepository->getGmTime();
        $begin = ($page - 1) * $size;

        $zc_arr = ZcProject::selectRaw('*, (end_time-unix_timestamp(now())) as shenyu_time')->whereRaw(1);

        if (!empty($str_id)) {
            $str_id = !is_array($str_id) ? explode(",", $str_id) : $str_id;

            $zc_arr = $zc_arr->whereIn('cat_id', $str_id);
        }

        // 推荐项目
        if (!empty($type)) {
            $zc_arr = $zc_arr->where('is_best', 1);
        }

        if (!empty($keyword)) {
            $zc_arr = $zc_arr->where('title', 'like', '%' . $keyword . '%');
        }

        $zc_arr = $zc_arr->where('start_time', '<=', $gmtime)
            ->where('end_time', '>', $gmtime);

        switch ($order) {
            // 默认
            case 'id':
                $zc_arr = $zc_arr->orderBy('id', 'asc');
                break;
            // 新品
            case 'new':
                $zc_arr = $zc_arr->orderBy('start_time', 'desc');
                break;
            // 金额最多
            case 'amount':
                $zc_arr = $zc_arr->orderBy('amount', 'desc');
                break;
            // 支持最多
            case 'join_num':
                $zc_arr = $zc_arr->orderBy('join_num', 'desc');
                break;
        }

        $zc_arr = $zc_arr->offset($begin)
            ->limit($size)
            ->get();

        $zc_arr = $zc_arr ? $zc_arr->toArray() : [];

        $timeFormat = $this->config['time_format'];
        $list = [];
        $lang_crowd_preheat = lang('common.lang_crowd_preheat', [], $this->config['lang']);
        $lang_crowd_of = lang('common.lang_crowd_of', [], $this->config['lang']);
        $lang_crowd_succeed = lang('common.lang_crowd_succeed', [], $this->config['lang']);
        if ($zc_arr) {
            foreach ($zc_arr as $k => $z_val) {
                $list[$k]['id'] = $z_val['id'];
                $list[$k]['title'] = $z_val['title'];

                if ($z_val['start_time'] > $gmtime) {
                    $list[$k]['zc_status'] = $lang_crowd_preheat;
                } elseif ($gmtime >= $z_val['start_time'] && $gmtime <= $z_val['end_time']) {
                    $list[$k]['zc_status'] = $lang_crowd_of;
                } elseif ($gmtime > $z_val['end_time']) {
                    $list[$k]['zc_status'] = $lang_crowd_succeed;
                }

                $list[$k]['star_time'] = $this->timeRepository->getLocalDate($timeFormat, $z_val['start_time']);
                $list[$k]['end_time'] = $this->timeRepository->getLocalDate($timeFormat, $z_val['end_time']);
                $list[$k]['amount'] = $z_val['amount'];
                $list[$k]['amount_formated'] = $this->dscRepository->getPriceFormat($z_val['amount'], false);
                $list[$k]['join_money'] = $z_val['join_money'];
                $list[$k]['join_money_formated'] = $this->dscRepository->getPriceFormat($z_val['join_money'], false);
                $min_price = $this->plan_min_price($z_val['id']);
                $list[$k]['min_price'] = $this->dscRepository->getPriceFormat($min_price, false);
                $list[$k]['join_num'] = $z_val['join_num'];
                $list[$k]['shenyu_time'] = ceil($z_val['shenyu_time'] / 3600 / 24);
                $list[$k]['baifen_bi'] = intval(round($z_val['join_money'] / $z_val['amount'], 2) * 100);
                $list[$k]['title_img'] = $this->dscRepository->getImagePath($z_val['title_img']);

            }
        }

        return $list;
    }

    /**
     * 获取方案最低价格
     *
     * @access  public
     * @return  void
     */
    public function plan_min_price($pid = 0)
    {
        $price = ZcGoods::where('pid', $pid)->min('price');

        return $price;
    }


    /**
     * 查询众筹项目详情
     *
     * @access  public
     * @return  void
     */
    public function zcGoodsInfo($id = 0)
    {
        $gmtime = $this->timeRepository->getGmTime();

        $info = ZcProject::select('id', 'title', 'init_id', 'start_time', 'end_time', 'end_time', 'amount', 'join_money', 'join_num', 'focus_num', 'prais_num', 'title_img')->where('id', $id)->first();
        $info = $info ? $info->toArray() : [];

        if ($info) {
            $info['title_img'] = $this->dscRepository->getImagePath($info['title_img']);
            $info['shenyu_time'] = ceil(($info['end_time'] - $gmtime) / 3600 / 24);
            if ($gmtime > $info['end_time'] && $info['amount'] >= $info['join_money']) {
                $info['zc_status'] = lang('crowdfunding.project_failed');
                $info['result'] = 2;
                $info['shenyu_time'] = 0;
            }
            if ($gmtime > $info['end_time'] && $info['join_money'] >= $info['amount']) {
                $info['zc_status'] = lang('crowdfunding.project_succeed');
                $info['result'] = 2;
                $info['shenyu_time'] = 0;
            }
            //百分比
            $info['baifen_bi'] = intval(round($info['join_money'] / $info['amount'], 2) * 100);
        }

        return $info;
    }

    /**
     * 获取众筹方案列表
     *
     * @access  public
     * @return  void
     */
    public function getZcGoods($pid = 0)
    {
        $goods_arr = ZcGoods::selectRaw('*, (`limit`-`backer_num`) as shenyu_ren')->where('pid', $pid)->get();

        $goods_arr = $goods_arr ? $goods_arr->toArray() : [];

        $zong_zhichi = 0;
        if ($goods_arr) {
            foreach ($goods_arr as $key => $row) {
                $goods_arr[$key]['img'] = $this->dscRepository->getImagePath($row['img']);
                $goods_arr[$key]['wuxian'] = 0;
                if ($row['limit'] < 0) {
                    $goods_arr[$key]['wuxian'] = 1;
                }
                $zong_zhichi += $row['backer_num'];
            }
        }

        $arr = [
            'goods_arr' => $goods_arr,
            'zong_zhichi' => $zong_zhichi
        ];

        return $arr;
    }


    /**
     * 选中方案信息
     *
     * @access  public
     * @return  void
     */
    public function getZcGoodsInfo($pid = 0, $id = 0)
    {
        $goods_arr = ZcGoods::select('id', 'pid', 'limit', 'backer_num', 'price')
            ->where('id', $id)
            ->where('pid', $pid)
            ->first();

        $goods_arr = $goods_arr ? $goods_arr->toArray() : [];

        return $goods_arr;
    }


    /**
     * 获取众筹项目动态
     *
     * @access  public
     * @return  void
     */
    public function getZcProgress($pid = 0)
    {
        $progress = ZcProgress::where('pid', $pid)->get();

        $progress = $progress ? $progress->toArray() : [];

        $timeFormat = $this->config['time_format'];
        if ($progress) {
            foreach ($progress as $key => $row) {
                $progress[$key]['add_time'] = $this->timeRepository->getLocalDate($timeFormat, $row['add_time']);
                $row['img'] = unserialize($row['img']);
                if (!empty($row['img'])) {
                    foreach ($row['img'] as $k2 => $v2) {
                        $row['img'][$k2] = $this->dscRepository->getImagePath($v2);
                    }
                    $progress[$key]['img'] = $row['img'];
                }
            }
        }

        return $progress;
    }


    /**
     * 获取众筹项目支持者
     *
     * @access  public
     * @return  void
     */
    public function getBackerList($id = 0)
    {
        $gmtime = $this->timeRepository->getGmTime();
        $res = OrderInfo::from('order_info as o')
            ->select('o.add_time', 'zg.price', 'u.user_name', 'u.nick_name', 'u.user_picture')
            ->leftjoin('users as u', 'u.user_id', '=', 'o.user_id')
            ->leftjoin('zc_goods as zg', 'zg.id', '=', 'o.zc_goods_id')
            ->leftjoin('zc_project as zp', 'zp.id', '=', 'zg.pid')
            ->where('o.is_zc_order', 1)
            ->where('o.pay_status', 2)
            ->where('zp.id', $id)
            ->orderBy('order_id', 'desc')
            ->get();

        $list = $res ? $res->toArray() : 0;
        $backer = [];
        if ($list) {
            foreach ($list as $key => $value) {
                $backer[$key]['price'] = $value['price'];
                //用户名、头像
                $backer[$key]['user_name'] = !empty($value['nick_name']) ? $value['nick_name'] : $value['user_name'];
                $backer[$key]['user_picture'] = $this->dscRepository->getImagePath($value['user_picture'], '', asset('img/user_default.png'));
                $backer[$key]['add_time'] = $this->get_time_past($value['add_time'], $gmtime);
            }
        }

        return $backer;
    }

    /**
     * 众筹描述
     * @param int $id
     * @return array
     */
    public function getProperties($id = 0)
    {
        $res = ZcProject::select('risk_instruction', 'describe', 'details')
            ->where('id', $id)
            ->first();

        return $res ? $res->toArray() : [];
    }

    /**
     * 众筹项目话题
     *
     * @access  public
     * @return  void
     */
    public function getTopicList($pid = 0, $type = false, $page = 1, $size = 10)
    {
        $gmtime = $this->timeRepository->getGmTime();
        $res = ZcTopic::from('zc_topic as zt')
            ->select('zt.*', 'u.user_name', 'u.nick_name', 'u.user_picture')
            ->leftjoin('users as u', 'u.user_id', '=', 'zt.user_id')
            ->where('zt.pid', $pid)
            ->where('zt.parent_topic_id', 0)
            ->where('zt.topic_status', 1);
        if ($type) {
            $begin = ($page - 1) * $size;
            $res = $res->offset($begin)
                ->limit($size);
        } else {
            $res = $res->limit(2);
        }
        $res = $res->orderBy('zt.topic_id', 'desc')
            ->get();

        $list = $res ? $res->toArray() : [];

        $topic = [];
        if ($list) {
            foreach ($list as $key => $value) {
                $topic[$key]['topic_id'] = $value['topic_id'];
                $topic[$key]['content'] = $value['topic_content'];
                //用户名、头像
                $topic[$key]['user_name'] = !empty($value['nick_name']) ? $value['nick_name'] : $value['user_name'];
                $topic[$key]['user_picture'] = $this->dscRepository->getImagePath($value['user_picture'], '', asset('img/user_default.png'));
                $topic[$key]['add_time'] = $this->get_time_past($value['add_time'], $gmtime);

                // 子评论列表
                $red = ZcTopic::from('zc_topic as zt')
                    ->select('zt.*', 'u.user_name', 'u.nick_name', 'u.user_picture')
                    ->leftjoin('users as u', 'u.user_id', '=', 'zt.user_id')
                    ->where('zt.parent_topic_id', $value['topic_id'])
                    ->where('zt.topic_status', 1)
                    ->orderBy('zt.topic_id', 'desc')
                    ->get();

                $childlist = $red ? $red->toArray() : [];
                $child_topic = [];
                if ($childlist) {
                    foreach ($childlist as $k => $val) {
                        $child_topic[$k]['topic_id'] = $val['topic_id'];
                        $child_topic[$k]['content'] = $val['topic_content'];
                        //用户名、头像
                        $child_topic[$k]['user_name'] = !empty($val['nick_name']) ? $val['nick_name'] : $val['user_name'];
                        $child_topic[$k]['user_picture'] = $this->dscRepository->getImagePath($val['user_picture'], '', asset('img/user_default.png'));
                        $child_topic[$k]['add_time'] = $this->get_time_past($val['add_time'], $gmtime);
                    }
                }
                $topic[$key]['child_topic'] = $child_topic;
            }
        }

        return $topic;
    }

    /**
     * 众筹项目是否收藏
     * @param int $pid
     * @param int $user_id
     * @return mixed
     */
    public function getZcFocus($pid = 0, $user_id = 0)
    {
        return ZcFocus::where('pid', $pid)
            ->where('user_id', $user_id)
            ->count();
    }

    /**
     * 删除收藏
     *
     * @access  public
     * @return  void
     */
    public function deleteFocus($pid = 0, $user_id = 0)
    {
        ZcFocus::where('pid', $pid)
            ->where('user_id', $user_id)
            ->delete();
    }

    /**
     * 添加收藏
     * @param array
     * @return mixed
     */
    public function addFocus($arguments = [])
    {
        return ZcFocus::insertGetId($arguments);
    }

    /**
     * 发布话题
     * @param array $arguments
     * @return mixed
     */
    public function addTopic($arguments = [])
    {
        return ZcTopic::insertGetId($arguments);
    }


    /**
     * 会员信息
     * @param int $user_id
     * @return mixed
     */
    public function getUserInfo($user_id = 0)
    {
        $user = Users::where('user_id', $user_id)
            ->first();

        return $user ? $user->toArray() : [];
    }


    /**
     * 我的关注
     *
     * @access  public
     * @return  array
     */
    public function getMyFocus($user_id = 0, $status = 0, $page = 10, $size = 0)
    {
        $gmtime = $this->timeRepository->getGmTime();

        $begin = ($page - 1) * $size;
        $res = ZcFocus::from('zc_focus as zf')
            ->select('zp.id', 'zp.title', 'zp.start_time', 'zp.end_time', 'zp.amount', 'zp.join_money', 'zp.join_num', 'zp.title_img')
            ->leftjoin('zc_project as zp', 'zf.pid', '=', 'zp.id')
            ->where('zf.user_id', $user_id);

        switch ($status) {
            // 全部
            case '0':
                break;
            // 进行中
            case '1':
                $res = $res->where('zp.start_time', '<', $gmtime)
                    ->where('zp.end_time', '>', $gmtime);
                break;
            // 已完成
            case '2':
                $res = $res->where('zp.end_time', '<', $gmtime)
                    ->where('zp.join_money', '>=', 'zp.amount');
                break;
        }

        $res = $res->offset($begin)
            ->limit($size)
            ->orderBy('zf.add_time', 'desc')
            ->get();

        $res = $res ? $res->toArray() : [];

        $timeFormat = $this->config['time_format'];
        $list = [];
        if ($res) {
            foreach ($res as $k => $val) {
                $list[$k]['id'] = $val['id'];
                $list[$k]['title'] = $val['title'];
                $list[$k]['start_time'] = $this->timeRepository->getLocalDate($timeFormat, $val['start_time']);
                $list[$k]['end_time'] = $this->timeRepository->getLocalDate($timeFormat, $val['end_time']);
                $list[$k]['amount'] = $val['amount'];
                $list[$k]['amount_formated'] = $this->dscRepository->getPriceFormat($val['amount'], false);
                $list[$k]['join_money'] = $val['join_money'];
                $list[$k]['join_money_formated'] = $this->dscRepository->getPriceFormat($val['join_money'], false);
                $min_price = $this->plan_min_price($val['id']);
                $list[$k]['min_price'] = $this->dscRepository->getPriceFormat($min_price, false);
                $list[$k]['join_num'] = $val['join_num'];
                $shenyu_time = $val['end_time'] - $gmtime;
                $list[$k]['shenyu_time'] = ceil($shenyu_time / 3600 / 24);
                $list[$k]['baifen_bi'] = intval(round($val['join_money'] / $val['amount'], 2) * 100);
                $list[$k]['title_img'] = $this->dscRepository->getImagePath($val['title_img']);
            }
        }

        return $list;
    }


    /**
     * 我的支持
     *
     * @access  public
     * @return  array
     */
    public function getCrowdBuy($user_id = 0, $status = 0, $page = 10, $size = 0)
    {
        $gmtime = $this->timeRepository->getGmTime();

        $begin = ($page - 1) * $size;
        $res = ZcGoods::from('zc_goods as zg')
            ->select('zp.id', 'zp.title', 'zp.start_time', 'zp.end_time', 'zp.amount', 'zp.join_money', 'zp.join_num', 'zp.title_img', 'oi.order_id', 'oi.pay_status', 'oi.shipping_status')
            ->leftjoin('zc_project as zp', 'zg.pid', '=', 'zp.id')
            ->leftjoin('order_info as oi', 'zg.id', '=', 'oi.zc_goods_id')
            ->where('oi.user_id', $user_id)
            ->where('oi.is_zc_order', 1);

        switch ($status) {
            // 全部
            case '0':
                break;
            // 进行中
            case '1':
                $res = $res->where('zp.start_time', '<', $gmtime)
                    ->where('zp.end_time', '>', $gmtime);
                break;
            // 已完成
            case '2':
                $res = $res->where('zp.end_time', '<', $gmtime)
                    ->where('zp.join_money', '>=', 'zp.amount');
                break;
        }

        $res = $res->offset($begin)
            ->limit($size)
            ->groupBy('zp.id')
            ->orderBy('oi.order_id', 'desc')
            ->get();

        $res = $res ? $res->toArray() : [];

        $timeFormat = $this->config['time_format'];
        $list = [];
        if ($res) {
            foreach ($res as $k => $val) {
                $list[$k]['id'] = $val['id'];
                $list[$k]['title'] = $val['title'];
                $list[$k]['start_time'] = $this->timeRepository->getLocalDate($timeFormat, $val['start_time']);
                $list[$k]['end_time'] = $this->timeRepository->getLocalDate($timeFormat, $val['end_time']);
                $list[$k]['amount'] = $val['amount'];
                $list[$k]['amount_formated'] = $this->dscRepository->getPriceFormat($val['amount'], false);
                $list[$k]['join_money'] = $val['join_money'];
                $list[$k]['join_money_formated'] = $this->dscRepository->getPriceFormat($val['join_money'], false);
                $min_price = $this->plan_min_price($val['id']);
                $list[$k]['min_price'] = $this->dscRepository->getPriceFormat($min_price, false);
                $list[$k]['join_num'] = $val['join_num'];
                $shenyu_time = $val['end_time'] - $gmtime;
                $list[$k]['shenyu_time'] = ceil($shenyu_time / 3600 / 24);
                $list[$k]['baifen_bi'] = intval(round($val['join_money'] / $val['amount'], 2) * 100);
                $list[$k]['title_img'] = $this->dscRepository->getImagePath($val['title_img']);
            }
        }

        return $list;
    }

    /**
     * 我的订单
     *
     * @access  public
     * @return  array
     */
    public function getOrderList($user_id = 0, $status = 0, $page = 10, $size = 0)
    {

        $begin = ($page - 1) * $size;

        $res = OrderInfo::where('user_id', $user_id)
            ->where('is_zc_order', 1);

        $res = $res->with([
            'getZcGoods' => function ($query) {
                $query = $query->select('id', 'pid', 'content', 'price');
                $query->with([
                    'getZcProject' => function ($query) {
                        $query->select('id', 'title', 'start_time', 'end_time', 'amount', 'join_money', 'join_num', 'title_img');
                    }
                ]);
            }
        ]);

        switch ($status) {
            // 全部
            case '0':
                break;
            // 待支付
            case '1':
                $res = $res->where('pay_status', PS_UNPAYED)
                    ->whereNotIn('order_status', [OS_CANCELED, OS_INVALID, OS_RETURNED]);
                break;
            // 代发货
            case '2':
                $res = $res->where('pay_status', PS_PAYED)
                    ->where('shipping_status', SS_UNSHIPPED);
                break;
            // 待收货
            case '3':
                $res = $res->where('pay_status', PS_PAYED)
                    ->where('shipping_status', SS_SHIPPED);
                break;
            // 已完成
            case '4':
                $res = $res->where('pay_status', PS_PAYED)
                    ->where('shipping_status', SS_RECEIVED);
                break;
        }

        $res = $res->orderBy('order_id', 'desc')
            ->offset($begin)
            ->limit($size)
            ->get();

        $res = $res ? $res->toArray() : [];

        $list = [];
        if ($res) {
            $timeFormat = $this->config['time_format'];
            $time = $this->timeRepository->getGmTime();
            // 加载语言包
            $oslang = lang('user.os');
            $pslang = lang('user.ps');
            $sslang = lang('user.ss');
            foreach ($res as $key => $row) {
                $row = array_merge($row, $row['get_zc_goods']);
                $row = array_merge($row, $row['get_zc_project']);
                //订单状态
                $row['orderstatus'] = $oslang[$row['order_status']] . ',' . $pslang[$row['pay_status']] . ',' . $sslang[$row['shipping_status']];
                $row['handler'] = 0;
                if ($row['order_status'] == OS_UNCONFIRMED || ($row['order_status'] == OS_CONFIRMED && $row['pay_status'] != PS_PAYED)) {
                    $row['handler'] = 1; //取消订单
                    $row['handler_this'] = ['order_id' => $row['order_id']];
                    $row['online_pay'] = ['order_sn' => $row['order_sn']];
                } elseif ($row['order_status'] == OS_CONFIRMED || $row['order_status'] == OS_SPLITED || $row['order_status'] == OS_SPLITING_PART) {
                    /* 对配送状态的处理 */
                    if ($row['shipping_status'] == SS_SHIPPED || $row['shipping_status'] == SS_SHIPPED_PART) {
                        $row['handler'] = 2; //确认收货
                        $row['handler_this'] = ['order_id' => $row['order_id']];
                    } elseif ($row['shipping_status'] == SS_RECEIVED) {
                        $row['handler'] = 4; // 已完成
                        $row['handler_this'] = ['order_id' => $row['order_id']];
                    } else {
                        if ($row['pay_status'] == PS_UNPAYED) {
                            $row['handler'] = 5; // 付款

                        }
                    }
                } elseif ($row['order_status'] == OS_CANCELED) {
                    $row['handler'] = 7; // 已取消
                } elseif ($row['order_status'] == OS_INVALID) {
                    $row['handler'] = 8; // 无效
                } else {
                    $row['handler'] = 6; // 已确认
                }

                //订单删除标识
                $row['order_del'] = 0;
                if ($row['order_status'] == 2 || ($row['order_status'] == 5 && $row['shipping_status'] == 2 && $row['pay_status'] == 2)) {
                    $row['order_del'] = 1;
                }

                //众筹活动状态
                if ($time > $row['start_time'] && $time < $row['end_time']) {
                    $zc_status = lang('crowdfunding.zc_project_progress');
                } elseif ($time > $row['end_time'] && $row['join_money'] >= $row['amount']) {
                    $zc_status = lang('crowdfunding.zc_project_success');
                } else {
                    $zc_status = lang('crowdfunding.zc_project_fail');
                }

                //众筹活动状态 end
                $list[] = [
                    'order_id' => $row['order_id'],
                    'order_sn' => $row['order_sn'],
                    'add_time' => $this->timeRepository->getLocalDate($timeFormat, $row['add_time']),
                    'orderstatus' => $row['orderstatus'],
                    'order_status' => $row['order_status'],
                    'pay_status' => $row['pay_status'],
                    'shipping_status' => $row['shipping_status'],
                    'order_del' => $row['order_del'],
                    'title' => $row['title'], //项目名称
                    'title_img' => $this->dscRepository->getImagePath($row['title_img']), //项目图片
                    'content' => $row['content'], //项目商品名称
                    'price' => $row['price'], //项目商品价格
                    'zc_status' => $zc_status, //众筹活动状态
                    'handler' => $row['handler'],
//                    'handler_this' => $row['handler_this'],
                    'total_fee' => $this->dscRepository->getPriceFormat($row['goods_amount'] + $row['shipping_fee'], false), // 总额
                    'total_amount' => $row['order_amount']
                ];
            }
        }

        return $list;
    }


    /**
     * 订单详情
     *
     * @access  public
     * @return  array
     */
    public function getOrderDetail($user_id = 0, $order_id = 0)
    {
        $time = $this->timeRepository->getGmTime();

        $res = OrderInfo::where('user_id', $user_id)
            ->where('order_id', $order_id)
            ->where('is_zc_order', 1);

        $res = $res->with([
            'getZcGoods' => function ($query) {
                $query = $query->select('id', 'pid', 'content', 'price');
                $query->with([
                    'getZcProject' => function ($query) {
                        $query->select('id', 'title', 'start_time', 'end_time', 'amount', 'join_money', 'join_num', 'title_img');
                    }
                ]);
            }
        ]);

        $res = $res->first();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            $oslang = lang('user.os');
            $pslang = lang('user.ps');
            $sslang = lang('user.ss');
            $timeFormat = $this->config['time_format'];

            $res = array_merge($res, $res['get_zc_goods']);
            $res = array_merge($res, $res['get_zc_project']);
            //订单状态
            $res['orderstatus'] = $oslang[$res['order_status']] . ',' . $pslang[$res['pay_status']] . ',' . $sslang[$res['shipping_status']];
            $shenyu_time = $res['end_time'] - $time;
            $res['shenyu_time'] = 0;
            if ($shenyu_time > 0) {
                $res['shenyu_time'] = ceil($shenyu_time / 3600 / 24);
            }
            $res['baifen_bi'] = round($res['join_money'] / $res['amount'], 2) * 100;
            $res['add_time'] = $this->timeRepository->getLocalDate($timeFormat, $res['add_time']);
            $res['title_img'] = $this->dscRepository->getImagePath($res['title_img']); //项目图片
            if ($res['pay_time'] > 0) {
                $res['pay_time'] = $this->timeRepository->getLocalDate($timeFormat, $res['pay_time']);
            }
            // 收获地址
            $res['detail_address'] = '';
            $res['detail_address'] .= $this->get_region_name($res['province']);
            $res['detail_address'] .= $this->get_region_name($res['city']);
            $res['detail_address'] .= $this->get_region_name($res['district']);
            $res['detail_address'] .= $res['address'];

            // 格式化金额字段
            $res['formated_join_money'] = $this->dscRepository->getPriceFormat($res['join_money'], false);
            $res['formated_price'] = $this->dscRepository->getPriceFormat($res['price'], false);
            $res['formated_goods_amount'] = $this->dscRepository->getPriceFormat($res['goods_amount'], false);
            $res['formated_shipping_fee'] = $this->dscRepository->getPriceFormat($res['shipping_fee'], false);
            unset($res['get_zc_goods']);
            unset($res['get_zc_project']);
        }

        return $res;
    }

    /**
     * 获取地区名称
     * @param int $region_id
     * @return mixed
     */
    public function get_region_name($region_id = 0)
    {
        return Region::where('region_id', $region_id)->value('region_name');
    }


    //将超    过n位的数字化为个、十、百、千、万等 by wu
    public function setNumberFormat($number = 0, $limit = 0, $point = 0, $round = true)
    {
        $number = intval($number);
        $result = $number;
        $count = strlen($number);

        switch ($count - 1) {
            case 0:
                $dividend = 1;
                $name = lang('crowdfunding.Opportunity.0');
                break;
            case 1:
                $dividend = 10;
                $name = lang('crowdfunding.Opportunity.1');
                break;
            case 2:
                $dividend = 100;
                $name = lang('crowdfunding.Opportunity.2');
                break;
            case 3:
                $dividend = 1000;
                $name = lang('crowdfunding.Opportunity.3');
                break;
            case 4:
                $dividend = 10000;
                $name = $name = lang('crowdfunding.Opportunity.4');
                break;
            case 5:
                $dividend = 100000;
                $name = lang('crowdfunding.Opportunity.5');
                break;
            case 6:
                $dividend = 1000000;
                $name = lang('crowdfunding.Opportunity.6');
                break;
            case 7:
                $dividend = 10000000;
                $name = lang('crowdfunding.Opportunity.7');
                break;
            case 8:
                $dividend = 100000000;
                $name = lang('crowdfunding.Opportunity.8');
                break;
            default:
                $dividend = 1;
                $name = lang('crowdfunding.Opportunity.0');
                break;
        }

        //如果数字长度大于限制长度，则进行处理
        if ($count > $limit) {
            if ($round) {
                $result = round($number / $dividend, $point) . $name;
            } else {
                $symbol = empty($point) ? '' : '.'; //小数点
                $prev = floor($number / $dividend); //小数前
                $next = substr($number - $prev * $dividend, 0, $point); //小数后
                $result = $prev . $symbol . $next . $name;
            }
        }

        return $result;
    }


    /**
     * 将时间转化为刚刚、几分钟前等等
     *
     * @access  public
     * @return  time
     * @return  now
     */
    public function get_time_past($time = 0, $now = 0)
    {
        $time_past = "";

        if ($now >= $time) {
            //相差时间
            $diff = $now - $time;

            //一分钟内：刚刚
            if ($diff > 0 && $diff <= 60) {
                $time_past = lang('crowdfunding.Opportunity.0');
            } //一小时内：n分钟前
            elseif ($diff > 60 && $diff <= 3600) {
                $time_past = floor($diff / 60) . lang('crowdfunding.Opportunity.1');
            } //一天内：n小时前
            elseif ($diff > 3600 && $diff <= 86400) {
                $time_past = floor($diff / 3600) . lang('crowdfunding.Opportunity.2');
            } //一月内：n天前
            elseif ($diff > 86400 && $diff <= 2592000) {
                $time_past = floor($diff / 86400) . lang('crowdfunding.Opportunity.3');
            } //一年内：n月前
            elseif ($diff > 2592000 && $diff <= 31536000) {
                $time_past = floor($diff / 2592000) . lang('crowdfunding.Opportunity.4');
            } //一年后：n年前
            elseif ($diff > 31536000) {
                $time_past = floor($diff / 31536000) . lang('crowdfunding.Opportunity.5');
            }
        } else {
            $time_past = lang('crowdfunding.Opportunity.6');
        }

        return $time_past;
    }

    //获取众筹项目列表
    public function getSpecialZcList($type = 0, $num = 5, $sort = 'id', $order = "DESC")
    {
        $now = $this->timeRepository->getGmTime();

        $res = ZcProject::where('start_time', '<', $now)->where('end_time', '>', $now);

        if ($type == 1) {
            $res = $res->where('is_best', 1);
        }

        $res = $res->orderBy($sort, $order);

        $res = $res->take($num);

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $k => $val) {
                $res[$k]['title_img'] = $this->dscRepository->getImagePath($val['title_img']);
            }
        }

        return $res;
    }

    //获取支持者    数量 by wu
    public function getBackerNum($zcid = 0)
    {
        return ZcProject::where('id', $zcid)->value('join_num');
    }

    //获取话题数量 by wu
    public function getTopicNum($zcid = 0)
    {
        return ZcTopic::where('pid', $zcid)->where('topic_status', 1)->where('parent_topic_id', 0)->count();
    }

    //取得当前项目发起人信息
    public function getInitiatorInfo($cid)
    {
        $id = $this->getInitiatorId($cid);

        $row = ZcInitiator::where('id', $id)->first();
        $row = $row ? $row->toArray() : [];

        if ($row) {
            $row['img'] = $this->dscRepository->getImagePath($row['img']);
            //不存在图片，则输出默认图片
            if ($this->dscRepository->remoteLinkExists($row['img']) === false) {
                $row['img'] = $this->dscRepository->getImagePath('');
            }
            //处理等级标识
            $logo = explode(',', $row['rank']);
            if ($logo) {
                foreach ($logo as $val) {
                    $row['logo'][] = $this->getRankLogo($val);
                }
            }
        }

        $count = ZcProject::where('init_id', $id)->count();
        $row['start_count'] = isset($count) ? $count : 1;

        return $row;
    }

    //取得等级身份标识
    public function getRankLogo($id)
    {
        $row = ZcRankLogo::where('id', $id)->first();

        $row = $row ? $row->toArray() : [];
        if ($row) {
            $row['img'] = $this->dscRepository->getImagePath($row['img']);
            //不存在图片，则输出默认图片
            if ($this->dscRepository->remoteLinkExists($row['img']) === false) {
                $row['img'] = $this->dscRepository->getImagePath('');
            }
        }

        return $row;
    }

    //取得发起人ID
    public function getInitiatorId($cid)
    {
        $init_id = ZcProject::where('id', $cid)->value('init_id');
        return $init_id;
    }

    /* 浏览历史 */

    public function getZcCateHistory($limit = 5)
    {
        $arr = [];

        if (!empty(request()->cookie('zc_history'))) {
            $string = request()->cookie('zc_history'); //按照浏览时间排序用 liu
            $zc_history = !is_array($string) ? explode(",", $string) : $string;

            $res = ZcProject::select(['id', 'title', 'title_img'])
                ->whereIn('id', $zc_history)
                ->orderBy('id', 'DESC')
                ->take($limit)
                ->get();

            $res = $res ? $res->toArray() : [];

            if ($res) {
                foreach ($res as $row) {
                    $arr[$row['id']]['id'] = $row['id'];
                    $arr[$row['id']]['title'] = $row['title'];
                    $arr[$row['id']]['title_img'] = $this->dscRepository->getImagePath($row['title_img']);
                }
            }
        }

        return $arr;
    }

    public function getOrderConfirmAddress($address_id)
    {
        $row = UserAddress::where('address_id', $address_id)->first();

        $row = $row ? $row->toArray() : [];

        return $row;
    }


    public function getZcGoodsProject($id = 0)
    {
        $goods_arr = ZcGoods::where('id', $id);

        $goods_arr = $goods_arr->with('getZcProject');

        $goods_arr = $goods_arr->first();

        $goods_arr = $goods_arr ? $goods_arr->toArray() : [];

        if ($goods_arr['get_zc_project']) {
            $goods_arr = array_merge($goods_arr, $goods_arr['get_zc_project']);
        }

        return $goods_arr;
    }


    public function getUserZcProjectList($user_id = 0)
    {
        $res = ZcProject::whereHas('getZcFocus', function ($query) use ($user_id) {
            $query->where('user_id', $user_id);
        });

        $res = $res->with([
            'getZcGoods' => function ($query) {
                $query->selectRaw("pid, SUM(backer_num) AS zhichi_num");
            }
        ]);

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $key => $row) {
                $row['zhichi_num'] = $row['get_zc_goods'] ? $row['get_zc_goods']['zhichi_num'] : 0;
                $row['title_img'] = $this->dscRepository->getImagePath($row['title_img']);
                $row['format_join_money'] = $this->dscRepository->getPriceFormat($row['join_money']);

                $res[$key] = $row;
            }
        }

        return $res;
    }

    public function getUserZcGoodsList($user_id = 0)
    {
        $res = ZcGoods::from('zc_goods as zg')
            ->select('zg.content', 'zg.price', 'zp.id', 'zp.title', 'zp.start_time', 'zp.end_time', 'zp.amount', 'zp.join_money', 'zp.join_num', 'zp.title_img', 'oi.order_id', 'oi.order_sn', 'oi.order_status', 'oi.pay_status', 'oi.shipping_status', 'oi.goods_amount', 'oi.shipping_fee', 'oi.add_time')
            ->leftjoin('zc_project as zp', 'zg.pid', '=', 'zp.id')
            ->leftjoin('order_info as oi', 'zg.id', '=', 'oi.zc_goods_id')
            ->where('oi.user_id', $user_id)
            ->where('oi.is_zc_order', 1)
            ->orderBy('oi.order_id', 'desc');

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $key => $row) {
                $res[$key]['title_img'] = $this->dscRepository->getImagePath($row['title_img']);
                $res[$key]['format_join_money'] = $this->dscRepository->getPriceFormat($row['join_money']);
                $res[$key]['format_goods_amount'] = $this->dscRepository->getPriceFormat($row['goods_amount']);
            }
        }

        return $res;
    }
}
