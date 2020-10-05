<?php

namespace App\Services\Wechat;

use App\Models\Goods;
use App\Models\OrderInfo;
use App\Models\Shipping;
use App\Models\Users;
use App\Models\WechatPrize;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;

/**
 * 微信功能扩展
 * Class WechatPluginService
 * @package App\Services\Wechat
 */
class WechatPluginService
{
    protected $wechatPointService;
    protected $commonRepository;
    protected $dscRepository;
    protected $timeRepository;

    public function __construct(
        WechatPointService $wechatPointService,
        CommonRepository $commonRepository,
        DscRepository $dscRepository,
        TimeRepository $timeRepository
    )
    {
        $this->wechatPointService = $wechatPointService;
        $this->commonRepository = $commonRepository;
        $this->dscRepository = $dscRepository;
        $this->timeRepository = $timeRepository;
    }

    /**
     * 查询会员订单
     * @param int $user_id
     * @return array
     */
    public function userOrderInfo($user_id = 0)
    {
        /* 计算订单各种费用之和的语句 */
        $total_fee = " (goods_amount - discount + tax + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee) AS total_fee ";

        $model = OrderInfo::selectRaw("*, $total_fee")
            ->where('main_count', 0)
            ->where('user_id', $user_id)
            ->orderBy('add_time', 'DESC');

        $model = $model->with([
            'goods',
        ]);

        $order = $model->first();

        $order = $order ? $order->toArray() : [];

        if ($order) {
            if (empty($order['shipping_code'])) {
                $order['shipping_code'] = Shipping::where('shipping_id', $order['shipping_id'])->value('shipping_code');
            }
        }

        return $order;
    }

    /**
     * 功能扩展 积分赠送
     *
     * @param string $fromusername
     * @param array $info
     */
    public function updatePoint($fromusername = '', $info = [])
    {
        if (!empty($fromusername) && !empty($info)) {
            // 配置信息
            $config = isset($info['config']) ? unserialize($info['config']) : [];
            // 开启积分赠送
            if (isset($config['point_status']) && $config['point_status'] == 1) {
                $num = $this->wechatPointService->wechatPointCount($fromusername, $info['command'], $config['point_interval']);

                // 当前时间减去时间间隔得到的历史时间之后赠送的次数
                if ($num < $config['point_num']) {
                    $this->wechatPointService->do_point($fromusername, $info, intval($config['point_value']));
                }
            }
        }
    }

    /**
     * 返回配送方式实例
     * @param string $shipping_code
     * @return \Illuminate\Foundation\Application|mixed|null
     */
    public function shippingInstance($shipping_code = '')
    {
        $shipping = $this->commonRepository->shippingInstance($shipping_code);

        return $shipping;
    }


    /**
     * 推荐商品
     * @param array $where
     * @param int $limit
     * @param int $wechat_ru_id
     * @return array
     */
    public function recommendGoods($where = [], $limit = 5, $wechat_ru_id = 0)
    {
        $model = Goods::where('is_on_sale', 1)
            ->where('is_delete', 0)
            ->where('is_alone_sale', 1)
            ->where('review_status', '>', 2)
            ->where('user_id', $wechat_ru_id);

        // 推荐
        if (isset($where['is_best']) && $where['is_best'] == 1) {
            if ($wechat_ru_id > 0) {
                $model->where('store_best', 1);
            } else {
                $model->where('is_best', 1);
            }
        }

        // 热门
        if (isset($where['is_hot']) && $where['is_hot'] == 1) {
            if ($wechat_ru_id > 0) {
                $model->where('store_hot', 1);
            } else {
                $model->where('is_hot', 1);
            }
        }

        // 新品
        if (isset($where['is_new']) && $where['is_new'] == 1) {
            if ($wechat_ru_id > 0) {
                $model->where('store_new', 1);
            } else {
                $model->where('is_new', 1);
            }
        }

        $goods = $model->orderBy('sort_order', 'ASC')
            ->orderBy('goods_id', 'DESC')
            ->limit($limit)
            ->get();

        $goods = $goods ? $goods->toArray() : [];

        if (!empty($goods)) {
            foreach ($goods as $key => $value) {
                $goods[$key]['goods_img'] = !empty($value['goods_img']) ? $this->dscRepository->getImagePath($value['goods_img']) : '';
            }
        }

        return $goods;
    }

    /**
     * 返回绝对路径图片 默认 wap_logo
     * @param string $goods_img
     * @return string
     */
    public function getPicUrl($goods_img = '')
    {
        if (empty($goods_img)) {
            $picUrl = empty(config('shop.wap_logo')) ? '' : $this->dscRepository->getImagePath(config('shop.wap_logo'));
        } else {
            $picUrl = $this->dscRepository->getImagePath($goods_img);
        }

        return $picUrl;
    }

    /**
     * 用户信息
     * @param int $user_id
     * @return array
     */
    public function userInfo($user_id = 0)
    {
        $user = Users::select('rank_points', 'pay_points', 'user_money')->where(['user_id' => $user_id])->first();
        $user = $user ? $user->toArray() : [];

        if (!empty($user)) {
            $user['user_money_format'] = strip_tags($this->dscRepository->getPriceFormat($user['user_money'], false));
        }

        return $user;
    }

    /**
     * 用户中奖记录
     * @param int $wechat_id
     * @param string $openid
     * @param string $plugin_name
     * @param array $offset
     * @param array $condition
     * @return array
     */
    public function userPrizeList($wechat_id = 0, $openid = '', $plugin_name = '', $offset = [], $condition = '')
    {
        if (empty($plugin_name) || empty($openid)) {
            return [];
        }

        $model = WechatPrize::where('wechat_id', $wechat_id)
            ->where('openid', $openid)
            ->where('activity_type', $plugin_name)
            ->where('prize_type', 1);

        $model = $model->with([
            'getWechatUser' => function ($query) use ($wechat_id) {
                $query->select('openid', 'nickname')->where('subscribe', 1)->where('wechat_id', $wechat_id);
            }
        ]);

        //        if (!empty($offset)) {
        //            $model = $model->offset($offset['start'])->limit($offset['limit']);
        //        }
        //        $list = $model->orderBy('dateline', 'DESC')
        //            ->get();

        if (!empty($condition)) {
            if (!empty($condition['starttime']) && !empty($condition['endtime'])) {
                $model = $model->whereBetween('dateline', [$condition['starttime'], $condition['endtime']]);
            }
        }

        $model = $model->orderBy('dateline', 'DESC');

        if (!empty($offset)) {
            $model = $model->paginate($offset['limit'] ?? 10);

            if (isset($offset['path'])) {
                $model->withPath($offset['path']); // 自定义分页地址
            }
        }

        $list = $model ? $model->toArray() : [];

        if (!empty($list['data'])) {
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k] = collect($v)->merge($v['get_wechat_user'])->except('get_wechat_user')->all(); // 合并且移除

                $list['data'][$k]['dateline_format'] = $this->timeRepository->getLocalDate(config('shop.time_format'), $v['dateline']);
                $list['data'][$k]['issue_status_format'] = lang('wechat.issue_status_' . $v['issue_status']);
            }
        }

        return $list;
    }
}
