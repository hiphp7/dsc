<?php

namespace App\Services\Wholesale;

use App\Models\AccountLog;
use App\Models\AdminUser;
use App\Models\PayLog;
use App\Models\Region;
use App\Models\SellerShopinfo;
use App\Models\Shipping;
use App\Models\Suppliers;
use App\Models\SuppliersAccountLogDetail;
use App\Models\TemporaryFiles;
use App\Models\TouchAd;
use App\Models\Users;
use App\Models\UsersReal;
use App\Models\Wholesale;
use App\Models\WholesaleCat;
use App\Models\WholesaleOrderAction;
use App\Models\WholesaleOrderInfo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Payment\PaymentService;
use App\Services\User\UserRankService;
use Illuminate\Support\Facades\DB;

class WholesaleService
{
    protected $baseRepository;
    protected $dscRepository;
    protected $timeRepository;
    protected $config;
    protected $paymentService;
    protected $flowMobileService;
    protected $userRankService;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        TimeRepository $timeRepository,
        PaymentService $paymentService,
        UserRankService $userRankService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->timeRepository = $timeRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->paymentService = $paymentService;
        $this->userRankService = $userRankService;
    }


    /**
     * 插入订单表
     *
     * @param array $order
     * @return int
     */
    public function AddWholesaleToOrder($order = [])
    {
        /* 获取表字段 */
        $order_other = $this->baseRepository->getArrayfilterTable($order, 'order_info');

        $count = OrderInfo::where('order_sn', $order['order_sn'])->count();

        if ($count <= 0) {
            $order_id = OrderInfo::insertGetId($order_other);

            return $order_id;
        } else {
            return 0;
        }
    }

    /**
     * 获取限时抢购
     *
     * @return mixed
     */
    public function getWholesaleLimit()
    {
        $now = $this->timeRepository->getGmTime();

        $res = Wholesale::where('enabled', 1)
            ->where('is_delete', 0)
            ->where('review_status', 3)
            ->where('is_promote', 1)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now);

        $res = $res->with([
            'getWholesaleVolumePriceList'
        ]);

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $row) {
                if ($row['get_wholesale_volume_price_list']) {
                    $row['volume_number'] = $this->baseRepository->getArrayMin($row['get_wholesale_volume_price_list'], 'volume_number');
                    $row['volume_price'] = $this->baseRepository->getArrayMax($row['get_wholesale_volume_price_list'], 'volume_price');
                } else {
                    $row['volume_number'] = 0;
                    $row['volume_price'] = 0;
                }
                $end_time = ($row['end_time'] - $now > 0) ? (floor(($row['end_time'] - $now) / 86400) > 0 ? floor(($row['end_time'] - $now) / 86400) : 1) : 0;
                $res[$key]['remaining_time'] = $end_time;
                $res[$key]['volume_number'] = $row['volume_number'];
                $res[$key]['volume_price'] = $row['volume_price'];

                $res[$key]['formated_end_date'] = $this->timeRepository->getLocalDate($this->config['date_format'], $row['end_time']);
                $res[$key]['small_time'] = $row['end_time'] - $now;
                $res[$key]['goods_name'] = $row['goods_name'];
                $res[$key]['goods_price'] = $row['goods_price'];
                $res[$key]['moq'] = $row['moq'];
                $res[$key]['volume_number'] = $row['volume_number'];
                $res[$key]['volume_price'] = $row['volume_price'];
                $res[$key]['goods_unit'] = $row['goods_unit'];
                $res[$key]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $res[$key]['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
                $res[$key]['url'] = $this->dscRepository->buildUri('wholesale_goods', array('aid' => $row['goods_id']), $row['goods_name']);
            }
        }

        return $res;
    }

    /**
     * 分类是否显示在导航上
     *
     * @return mixed
     */
    public function getWholsaleNavigator()
    {
        $cur_url = substr(strrchr(request()->server('REQUEST_URI'), '/'), 1);
        preg_match('/\d+/', $cur_url, $matches);

        $curr_id = $matches[0] ?? 0;

        $res = WholesaleCat::where('is_show', 1)
            ->where('show_in_nav', 1);

        $res = $res->orderBy('sort_order');

        $res = app(BaseRepository::class)->getToArrayGet($res);

        if ($res) {
            foreach ($res as $k => $row) {
                $res[$k]['url'] = $this->dscRepository->buildUri('wholesale_cat', array('act' => 'list', 'cid' => $row['cat_id']), $row['cat_name']);
                $res[$k]['active'] = $curr_id;
            }
        }

        return $res;
    }

    /**
     * 判断商家或者会员是否通过实名认证
     *
     * @param int $user_id
     * @param int $user_type
     * @return bool
     */
    public function getCheckUsersReal($user_id = 0, $user_type = 0)
    {
        $data = UsersReal::where('user_id', $user_id)
            ->where('user_type', $user_type)
            ->where('review_status', 1)
            ->count();

        if ($data) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 返回商家组合地区信息，如：江苏 南京
     *
     * @param int $ru_id
     * @param int $type 类型（暂时无用，后期可根据不同类型返回对应组合）
     * @return string
     */
    public function getSellerAreaInfo($ru_id = 0, $type = 0)
    {
        switch ($type) {
            case 0:
                $data = ['province', 'city'];
                break;
            default:
                $data = ['country', 'province', 'city'];
                break;
        }

        $area_info = SellerShopinfo::select($data)->where('ru_id', $ru_id);
        $area_info = $this->baseRepository->getToArrayFirst($area_info);

        $str = '';
        if ($area_info) {
            $area_info = array_values($area_info);
            $res = Region::whereIn('region_id', $area_info);
            $res = $this->baseRepository->getToArrayGet($res);
            $region_name = $this->baseRepository->getKeyPluck($res, 'region_name');


            if ($region_name) {
                return implode(' ', $region_name);
            } else {
                return $str;
            }
        }

        return $str;
    }

    /**
     * 返回地区逐级名称
     * 如：中国 上海 上海 普陀区 中山北路
     *
     * @param int $region_id
     * @return string
     */
    public function getEveryRegionName($region_id = 0)
    {
        $arr = array();
        $arr[] = $region_id;
        $parent_id = Region::where('region_id', $region_id)->value('parent_id');

        while ($parent_id) {
            $arr[] = $parent_id;
            $parent_id = Region::where('region_id', $parent_id)->value('parent_id');
        }

        $str = '';
        if ($arr) {
            krsort($arr);

            //获取地区
            $area_info = array_values($arr);
            $res = Region::whereIn('region_id', $area_info);
            $res = $this->baseRepository->getToArrayGet($res);
            $region_name = $this->baseRepository->getKeyPluck($res, 'region_name');

            if ($region_name) {
                return implode(' ', $region_name);
            } else {
                return $str;
            }
        }

        return $str;
    }

    /**
     * 创建分页信息
     *
     * @access  public
     * @param string $app 程序名称，如category
     * @param string $cat 分类ID
     * @param string $record_count 记录总数
     * @param string $size 每页记录数
     * @param string $sort 排序类型
     * @param string $order 排序顺序
     * @param string $page 当前页
     * @return  void
     */
    public function assignCatPager($app, $cat, $record_count, $keywords, $size, $sort, $order, $page = 1)
    {
        $page = intval($page);
        if ($page < 1) {
            $page = 1;
        }

        $page_count = $record_count > 0 ? intval(ceil($record_count / $size)) : 1;

        $pager['page'] = $page;
        $pager['size'] = $size;
        $pager['sort'] = $sort;
        $pager['order'] = $order;
        $pager['record_count'] = $record_count;
        $pager['page_count'] = $page_count;

        switch ($app) {
            case 'wholesale_cat':
                $uri_args = array('act' => 'list', 'cid' => $cat, 'sort' => $sort, 'order' => $order);
                break;
            case 'wholesale_suppliers':
                $uri_args = array('act' => 'list', 'sid' => $cat, 'sort' => $sort, 'order' => $order);
                break;
        }

        $page_prev = ($page > 1) ? $page - 1 : 1;
        $page_next = ($page < $page_count) ? $page + 1 : $page_count;

        $_pagenum = 10;     // 显示的页码
        $_offset = 2;       // 当前页偏移值
        $_from = $_to = 0;  // 开始页, 结束页
        if ($_pagenum > $page_count) {
            $_from = 1;
            $_to = $page_count;
        } else {
            $_from = $page - $_offset;
            $_to = $_from + $_pagenum - 1;
            if ($_from < 1) {
                $_to = $page + 1 - $_from;
                $_from = 1;
                if ($_to - $_from < $_pagenum) {
                    $_to = $_pagenum;
                }
            } elseif ($_to > $page_count) {
                $_from = $page_count - $_pagenum + 1;
                $_to = $page_count;
            }
        }

        if (!empty($url_format)) {
            $pager['page_first'] = ($page - $_offset > 1 && $_pagenum < $page_count) ? $url_format . 1 : '';
            $pager['page_prev'] = ($page > 1) ? $url_format . $page_prev : '';
            $pager['page_next'] = ($page < $page_count) ? $url_format . $page_next : '';
            $pager['page_last'] = ($_to < $page_count) ? $url_format . $page_count : '';
            $pager['page_kbd'] = ($_pagenum < $page_count) ? true : false;
            $pager['page_number'] = array();
            for ($i = $_from; $i <= $_to; ++$i) {
                $pager['page_number'][$i] = $url_format . $i;
            }
        } else {
            $pager['page_first'] = ($page - $_offset > 1 && $_pagenum < $page_count) ? $this->dscRepository->buildUri($app, $uri_args, '', 1, $keywords) : '';
            $pager['page_prev'] = ($page > 1) ? $this->dscRepository->buildUri($app, $uri_args, '', $page_prev, $keywords) : '';
            $pager['page_next'] = ($page < $page_count) ? $this->dscRepository->buildUri($app, $uri_args, '', $page_next, $keywords) : '';
            $pager['page_last'] = ($_to < $page_count) ? $this->dscRepository->buildUri($app, $uri_args, '', $page_count, $keywords) : '';
            $pager['page_kbd'] = ($_pagenum < $page_count) ? true : false;
            $pager['page_number'] = array();
            for ($i = $_from; $i <= $_to; ++$i) {
                $pager['page_number'][$i] = $this->dscRepository->buildUri($app, $uri_args, '', $i, $keywords);
            }
        }

        $GLOBALS['smarty']->assign('pager', $pager);
    }

    /**
     * 判断会员是否是商家
     *
     * @param int $user_id
     * @return int
     */
    public function getCheckUserIsMerchant($user_id = 0)
    {
        $count = AdminUser::where('ru_id', $user_id)->count();

        if ($count > 0) {
            $is_merchant = 1;
        } else {
            $is_merchant = 0;
        }

        return $is_merchant;
    }

    /*
     * 移动一组临时文件，并返回新路径数组
     * ids 临时文件id，字符串如：1,2,3...
     * dir 需要移动到的目录
     */
    public function moveTemporaryFiles($ids = "", $dir = "")
    {
        if (empty($ids) || empty($dir)) {
            return false;
        }

        $ids = $this->baseRepository->getExplode($ids);

        $path_list = TemporaryFiles::whereIn('id', $ids);
        $path_list = $this->baseRepository->getToArrayGet($path_list);
        $path_list = $this->baseRepository->getKeyPluck($path_list, 'path');

        $arr = [];
        if ($path_list) {
            foreach ($path_list as $key => $val) {
                $new_path = $this->moveFiles(storage_public($val), $dir);
                $arr[] = $new_path;
            }
        }

        TemporaryFiles::whereIn('id', $ids)->delete();

        return $arr;
    }

    /*
 * 移动文件，并返回新路径
 * path 临时文件路径
 * dir 	需要移动到的目录
 */

    public function moveFiles($path = "", $dir = "")
    {
        if (empty($path) || empty($dir)) {
            return false;
        }

        //创建目录
        if (!file_exists(storage_public($dir))) {
            make_dir(storage_public($dir));
        }

        //获取文件名
        $parts = explode('/', $path);
        $new_path = $dir . '/' . end($parts);
        @copy($path, storage_public($new_path));
        @unlink($path);
        return $new_path;
    }

    /**
     * 批发订单用户收货地址地区
     *
     * @param int $order_id
     * @return mixed
     */
    public function getFlowWholesaleUserRegion($order_id = 0)
    {

        /* 取得区域名 */
        $res = WholesaleOrderInfo::where('order_id', $order_id);

        $res = $res->with([
            'getRegionProvince' => function ($query) {
                $query->select('region_id', 'region_name as province_name');
            },
            'getRegionCity' => function ($query) {
                $query->select('region_id', 'region_name as city_name');
            },
            'getRegionDistrict' => function ($query) {
                $query->select('region_id', 'region_name as district_name');
            },
            'getRegionStreet' => function ($query) {
                $query->select('region_id', 'region_name as street_name');
            }
        ]);

        $res = $this->baseRepository->getToArrayFirst($res);

        $region = '';
        if ($res) {
            $res = $res['get_region_province'] ? array_merge($res, $res['get_region_province']) : $res;
            $res = $res['get_region_city'] ? array_merge($res, $res['get_region_city']) : $res;
            $res = $res['get_region_district'] ? array_merge($res, $res['get_region_district']) : $res;
            $res = $res['get_region_street'] ? array_merge($res, $res['get_region_street']) : $res;


            $province_name = isset($res['province_name']) && $res['province_name'] ? $res['province_name'] : '';
            $city_name = isset($res['city_name']) && $res['city_name'] ? $res['city_name'] : '';
            $district_name = isset($res['district_name']) && $res['district_name'] ? $res['district_name'] : '';
            $street_name = isset($res['street_name']) && $res['street_name'] ? $res['street_name'] : '';

            $region = $province_name . " " . $city_name . " " . $district_name . " " . $street_name;
            $region = trim($region);
        }

        return $region;
    }

    /**
     * 订单日志记录
     *
     * @param $order_sn
     * @param $order_status
     * @param $shipping_status
     * @param $pay_status
     * @param string $note
     * @param null $username
     * @param int $place
     * @param int $confirm_take_time
     */
    public function wholesaleOrderAction($order_sn, $order_status, $shipping_status, $pay_status, $note = '', $username = '', $place = 0, $confirm_take_time = 0)
    {
        if (!empty($confirm_take_time)) {
            $log_time = $confirm_take_time;
        } else {
            $log_time = $this->timeRepository->getGmTime();
        }

        $admin_id = get_admin_id();

        if (!empty($username)) {
            $username = AdminUser::where('user_id', $admin_id)->value('user_name');
        }

        $username = $username ? $username : '';

        $order_id = WholesaleOrderInfo::where('order_sn', $order_sn)->value('order_id');
        $order_id = $order_id ? $order_id : 0;

        WholesaleOrderAction::insert([
            'order_id' => $order_id,
            'action_user' => $username,
            'order_status' => $order_status,
            'shipping_status' => $shipping_status,
            'pay_status' => $pay_status,
            'action_place' => $place,
            'action_note' => $note,
            'log_time' => $log_time
        ]);
    }

    /**
     * 获取供应商结算信息
     *
     * @param int $suppliers_id
     * @return mixed
     */
    public function getSuppliersOrderValidRefund($suppliers_id = 0)
    {
        $suppliers_percent = Suppliers::where('suppliers_id', $suppliers_id)->value('suppliers_percent');
        $suppliers_percent = $suppliers_percent ? $suppliers_percent / 100 : 1;

        $res = WholesaleOrderInfo::mainOrderCount()
            ->where('suppliers_id', $suppliers_id)
            ->where('is_settlement', 0)
            ->where(function ($query) {
                $query = $query->where(function ($query) {
                    $query->where('pay_status', PS_PAYED)
                        ->where('order_status', 1);
                });

                $query->orWhere(function ($query) {
                    $query->where('pay_status', 5)
                        ->where('order_status', 4);
                });
            });

        $order_amount = $return_amount = $res;

        $res = $this->baseRepository->getToArrayFirst($res);

        $res['order_amount'] = $order_amount->sum('order_amount');
        $res['order_amount'] = $res['order_amount'] ? $res['order_amount'] : 0;

        $res['return_amount'] = $return_amount->sum('return_amount');
        $res['return_amount'] = $res['return_amount'] ? $res['return_amount'] : 0;

        //未结算金额计算   公式： （订单总额 - 退款金额）* 结算比例
        $amount = $res['order_amount'] - $res['return_amount'];
        $amount = $amount > 0 ? $amount : 0;

        $res['no_settlement_amout'] = $amount * $suppliers_percent;

        $detail = SuppliersAccountLogDetail::whereHas('getWholesaleOrderInfo', function ($query) use ($suppliers_id) {
            $query = $query->whereHas('getMainOrderId', function ($query) {
                $query->selectRaw("count(*) as count")->Having('count', 0);
            });

            $query = $query->where('is_settlement', 1)
                ->where('suppliers_id', $suppliers_id);

            $query = $query->where(function ($query) {
                $query->where('pay_status', PS_PAYED)
                    ->where('order_status', 1);
            });

            $query->orWhere(function ($query) {
                $query->where('pay_status', 5)
                    ->where('order_status', 4);
            });
        });

        //已结算金额  查找结算日志结算金额
        $res['is_settlement_amout'] = $detail->sum('amount');
        $res['is_settlement_amout'] = $res['is_settlement_amout'] ? $res['is_settlement_amout'] : 0;

        $res['suppliers_percent'] = $suppliers_percent * 100;//佣金比例
        $res['brokerage_amount'] = $res['is_settlement_amout'] + $res['no_settlement_amout'];
        $res['brokerage_amount'] = $this->dscRepository->getPriceFormat($res['brokerage_amount']);
        $res['formated_is_settlement_amout'] = $this->dscRepository->getPriceFormat($res['is_settlement_amout']);
        $res['formated_no_settlement_amout'] = $this->dscRepository->getPriceFormat($res['no_settlement_amout']);
        $res['formated_return_amount'] = $this->dscRepository->getPriceFormat($res['return_amount']);

        return $res;
    }

    /**
     * 轮播图
     */
    public function get_banner($type = 'supplier', $num)
    {
        $time = $this->timeRepository->getGmTime();
        $res = TouchAd::from('touch_ad as ta')
            ->select('ta.ad_id', 'ta.position_id', 'ta.media_type', 'ta.ad_link', 'ta.ad_code', 'ta.ad_name')
            ->leftjoin('touch_ad_position as tap', 'ta.position_id', '=', 'tap.position_id')
            ->where("ta.start_time", '<=', $time)
            ->where("ta.end_time", '>=', $time)
            ->where("tap.ad_type", $type)
            ->where("ta.enabled", 1)
            ->get();

        $res = $res ? $res->toArray() : [];

        $ads = [];
        if ($res) {
            foreach ($res as $row) {
                if (!empty($row['position_id'])) {
                    $src = (strpos($row['ad_code'], 'http://') === false && strpos($row['ad_code'], 'https://') === false) ?
                        "data/afficheimg/$row[ad_code]" : $row['ad_code'];
                    $ads[] = [
                        'pic' => $this->dscRepository->getImagePath($src),
                        'adsense_id' => $row['ad_id'],
                        'link' => $row['ad_link'],
                    ];
                }
            }
        }

        return $ads;
    }


    /**
     * 获取搜索商品列表
     *
     * @return mixed
     */
    public function PayCheck($uid, $order_sn = '')
    {
        $order = [];
        if ($order_sn) {
            $order_info = WholesaleOrderInfo::where('order_sn', $order_sn)
                ->where('user_id', $uid)
                ->first();
            $order_info = $order_info ? $order_info->toArray() : '';
        }


        $child_order = WholesaleOrderInfo::select('order_id')
            ->where('main_order_id', $order_info['order_id'])
            ->count();

        if ($child_order >= 1) {
            $order_res = WholesaleOrderInfo::where('main_order_id', $order_info
            ['order_id']);

            $child_order_info = app(BaseRepository::class)->getToArrayGet($order_res);
        }


        if ($order_info) {
            $order['order_sn'] = $order_info['order_sn'];

            $payment = $this->paymentService->getPaymentInfo(['pay_id' => $order_info['pay_id']]);

            $payment['pay_name'] = $payment['pay_name'] ?? '';
            $payment['pay_code'] = $payment['pay_code'] ?? '';
            $payment['is_online'] = $payment['is_online'] ?? 0;
            $payment['pay_desc'] = $payment['pay_desc'] ?? '';

            $order['pay_name'] = $payment['pay_name'];
            $order['pay_code'] = $payment['pay_code'];
            $order['pay_desc'] = $payment['pay_desc'];

            // 手机端 使用余额支付订单并且订单状态已付款
            $order['is_surplus'] = ($order_info['surplus'] > 0 && $order_info['pay_status']
                == PS_PAYED) ? 1 : 0;
            // 是否在线支付
            $order['is_online'] = ($payment['pay_code'] != 'balance') ? 1 : 0;
            $order['pay_result'] = ($order_info['pay_id'] == 0 && $order_info['pay_status']
                == PS_PAYED) ? 1 : 0; //余额支付订单并且订单状态已付款
            $order['cod_fee'] = 0;
            $shipping_id = explode('|', $order_info['shipping_id']);
            foreach ($shipping_id as $k => $v) {
                if ($v) {
                    $order['support_cod'] = Shipping::where('shipping_id', $v)->value('support_cod');
                }
            }
            $order['pay_status'] = $order_info['pay_status'];
            $order['order_id'] = $order_info['order_id'];
            $order['order_amount'] = $order_info['order_amount'];
            $order['order_amount_format'] = $this->dscRepository->getPriceFormat($order_info['order_amount']);
            $order['child_order'] = isset($child_order) ? $child_order : 0;
            $order['child_order_info'] = isset($child_order_info) ? $child_order_info : [];
            $order['extension_code'] = $order_info['extension_code'];
        }
        return $order;
    }

    /**
     * 切换支付方式
     *
     * @return mixed
     */
    public function change_payment($order_id = 0, $pay_id = 0, $user_id = 0)
    {
        if (empty($order_id)) {
            return ['code' => 1, 'msg' => "非法操作"];
        }
        /* 订单详情 */
        /* 计算订单各种费用之和的语句 */

        $order_id = intval($order_id);

        //@模板堂-bylu 这里连表查下支付方法表,获取到"pay_code"字段值;
        $order = WholesaleOrderInfo::where('order_id', $order_id);
        $order = $order->first();

        /* 订单详情 */
        $order = $order ? $order->toArray() : [];
        //获取log_id
        $log_id = PayLog::where('order_id', $order_id)->orderBy('log_id', 'DESC')->value('log_id');

        $order['log_id'] = $log_id ?? 0;

        $payment_info = $this->paymentService->getPaymentInfo(['pay_id' => $pay_id,
            'enabled' => 1]);

        //改变订单的支付名称和支付id
        $payData = [
            'pay_id' => $payment_info['pay_id'],
        ];

        WholesaleOrderInfo::where('order_id', $order_id)->update($payData);

        //是否有子订单
        $child_order_id_arr = WholesaleOrderInfo::select('order_id')->where('main_order_id', $order_id)->get();
        $child_order_id_arr = $child_order_id_arr ? $child_order_id_arr->toArray() : [];
        if ($order['main_order_id'] == 0 && count($child_order_id_arr) > 0 && $order_id >
            0
        ) {
            WholesaleOrderInfo::where('main_order_id', $order['order_id'])->update($payData);
        }

        $order['pay_desc'] = $payment_info['pay_desc'];

        $payment = $this->paymentService->getPayment($payment_info['pay_code']);

        if ($payment === false) {
            return [];
        }

        // 指定支付类型
        if (request()->has('type')) {
            $order['type'] = request()->get('type', '');
        }

        return $payment->get_code(
            $order,
            unserialize_config($payment_info['pay_config']),
            $user_id
        );
    }

    /**
     * 余额支付
     * @param $uid
     * @param $total
     * @return array
     */
    public function Balance($uid, $order_sn = '')
    {
        load_helper('payment');
        $order = [];
        if ($order_sn) {
            $order_info = WholesaleOrderInfo::where('order_sn', $order_sn)
                ->where('user_id', $uid)
                ->first();
            $order_info = $order_info ? $order_info->toArray() : '';
        }
        //获取支付方式信息
        $where = [
            'pay_id' => $order_info['pay_id']
        ];
        $payment_info = $this->paymentService->getPaymentInfo($where);

        $payment_info['pay_name'] = addslashes($payment_info['pay_name']);
        $payment_info['pay_code'] = addslashes($payment_info['pay_code']);
        $pay_fee = pay_fee($order_info['pay_id'], $order_info['order_amount'], 0); //获取手续费
        //数组处理
        $order['order_amount'] = $order_info['order_amount'] + $pay_fee;
        $order['pay_name'] = $payment_info['pay_name'];
        $order['pay_fee'] = $pay_fee;
        $order['user_id'] = $order_info['user_id'];

        $log_id = PayLog::where('order_id', $order_info['order_id'])
            ->where('order_type', PAY_WHOLESALE)
            ->value('log_id');
        $order['log_id'] = $log_id;
        $order['order_sn'] = $order_info['order_sn'];

        //子订单数量
        $child_order_info = WholesaleOrderInfo::where('main_order_id', $order_info['order_id']);
        $child_order_info = $this->baseRepository->getToArrayGet($child_order_info);

        $child_num = count($child_order_info);
        $result = [];

        if ($order_info['pay_status'] != 2) {
            if ($payment_info['pay_code'] == 'balance') {
                //查询出当前用户的剩余余额;
                $user_money = Users::where('user_id', $uid)->value('user_money');
                $user_money = $user_money ? $user_money : 0;

                //如果用户余额足够支付订单;
                if ($user_money > $order['order_amount']) {
                    $time = $this->timeRepository->getGmTime();

                    /* 修改申请的支付状态 */
                    WholesaleOrderInfo::where('order_id', $order_info['order_id'])->update([
                        'pay_status' => PS_PAYED,
                        'pay_time' => $time
                    ]);

                    /* 修改此次支付操作的状态为已付款 */
                    PayLog::where('order_id', $order_info['order_id'])
                        ->where('order_type', PAY_WHOLESALE)
                        ->update([
                            'is_paid' => 1
                        ]);

                    $this->LogAccountChange($order['user_id'], $order['order_amount'] * (-1), 0, 0, 0, sprintf('支付批发订单', $order['order_sn']));

                    //修改子订单状态为已付款
                    if ($child_num > 0) {
                        $order_res = WholesaleOrderInfo::where('main_order_id', $order_info['order_id']);
                        $order_res = $this->baseRepository->getToArrayGet($order_res);

                        $sms_send = [];
                        if ($order_res) {
                            foreach ($order_res as $row) {
                                /* 修改此次支付操作子订单的状态为已付款 */
                                PayLog::where('order_id', $row['order_id'])
                                    ->where('order_type', PAY_WHOLESALE)
                                    ->update([
                                        'is_paid' => 1
                                    ]);

                                $child_pay_fee = order_pay_fee($row['pay_id'], $row['order_amount']); //获取支付费用
                                //修改子订单支付状态
                                WholesaleOrderInfo::where('order_id', $row['order_id'])
                                    ->update([
                                        'pay_status' => 2,
                                        'pay_time' => $time,
                                        'pay_fee' => $child_pay_fee
                                    ]);

                                $suppliers = Suppliers::where('suppliers_id', $row['suppliers_id']);
                                $suppliers = $this->baseRepository->getToArrayFirst($suppliers);
                            }
                        }
                    }
                } else {
                    $result = [
                        'code' => 1,
                        'msg' => '您的余额已不足,请充值!',
                    ];
                }
            }
        } else {
            $result = [
                'code' => 1,
                'msg' => '您的订单已经支付完成',
            ];
        }

        return $result;
    }


    /**
     * 记录帐户变动
     * @param int $user_id 用户id
     * @param float $user_money 可用余额变动
     * @param float $frozen_money 冻结余额变动
     * @param int $rank_points 等级积分变动
     * @param int $pay_points 消费积分变动
     * @param string $change_desc 变动说明
     * @param int $change_type 变动类型：参见常量文件
     * @return  void
     */
    public function LogAccountChange($user_id, $user_money = 0, $frozen_money = 0, $rank_points = 0, $pay_points = 0, $change_desc = '', $change_type = ACT_OTHER, $order_type = 0, $deposit_fee = 0)
    {
        $is_go = true;
        $is_user_money = 0;
        $is_pay_points = 0;

        //控制只有后台执行，前台不操作以下程序
        if ($change_desc && $order_type) {
            $change_desc_arr = $change_desc ? explode(" ", $change_desc) : [];

            if (count($change_desc_arr) >= 2) {
                $order_sn = !empty($change_desc_arr[1]) ? $change_desc_arr[1] : '';

                if (!empty($order_sn)) {
                    $order_res = WholesaleOrderInfo::select(['order_id', 'main_order_id'])->where('order_sn', $order_sn);
                    $order_res = app(BaseRepository::class)->getToArrayFirst($order_res);
                } else {
                    $order_res = [];
                }

                if (empty($order_res)) {
                    $is_go = false;
                }

                if ($order_res) {
                    if ($order_res['main_order_id'] > 0) {  //操作无效或取消订单时，先查询该订单是否有主订单

                        $ordor_main = WholesaleOrderInfo::select('order_sn')->where('order_id', $order_res['main_order_id']);
                        $ordor_main = app(BaseRepository::class)->getToArrayFirst($ordor_main);

                        if ($ordor_main) {
                            $order_surplus_desc = sprintf(lang('user.return_order_surplus'), $ordor_main['order_sn']);
                            $order_integral_desc = sprintf(lang('user.return_order_integral'), $ordor_main['order_sn']);
                        } else {
                            $order_surplus_desc = '';
                            $order_integral_desc = '';
                        }

                        //查询该订单的主订单是否已操作过无效或取消订单
                        $change_desc = [$order_surplus_desc, $order_integral_desc];

                        $log_res = [];
                        if ($change_desc) {
                            $log_res = AccountLog::select('log_id')->whereIn('change_desc', $change_desc);
                            $log_res = app(BaseRepository::class)->getToArrayGet($log_res);
                        }

                        if ($log_res) {
                            $is_go = false;
                        }
                    } else {
                        if ($order_res && $order_res['order_id'] > 0) {
                            $main_order_res = WholesaleOrderInfo::select('order_id', 'order_sn')->where('main_order_id', $order_res['order_id']);
                            $main_order_res = app(BaseRepository::class)->getToArrayGet($main_order_res);

                            if ($main_order_res > 0) {
                                foreach ($main_order_res as $key => $row) {
                                    $order_surplus_desc = sprintf(lang('user.return_order_surplus'), $row['order_sn']);
                                    $order_integral_desc = sprintf(lang('user.return_order_integral'), $row['order_sn']);

                                    $main_change_desc = [$order_surplus_desc, $order_integral_desc];
                                    $parent_account_log = AccountLog::select(['user_money', 'pay_points'])->whereIn('change_desc', $main_change_desc);
                                    $parent_account_log = app(BaseRepository::class)->getToArrayGet($parent_account_log);

                                    if ($parent_account_log) {
                                        if ($user_money) {
                                            $is_user_money += $parent_account_log[0]['user_money'];
                                        }

                                        if ($pay_points) {
                                            $is_pay_points += $parent_account_log[1]['pay_points'];
                                        }
                                    }
                                }
                            }
                        }

                        if ($user_money) {
                            $user_money -= $is_user_money;
                        }

                        if ($pay_points) {
                            $pay_points -= $is_pay_points;
                        }
                    }
                }
            }
        } /**
         * 判断是否是支付订单操作
         * 【订单号不能为空】
         *
         */
        elseif ($change_desc) {
            if (strpos($change_desc, '：') !== false) {
                $change_desc_arr = explode("：", $change_desc);
            } else {
                $change_desc_arr = explode(" ", $change_desc);
            }

            if (count($change_desc_arr) >= 2) {
                if (!empty($change_desc_arr[0]) && ($change_desc_arr[0] == '支付订单' || $change_desc_arr[0] == '追加使用余额支付订单')) {
                    if (!empty($change_desc_arr[1])) {
                        $change_desc_arr[1] = trim($change_desc_arr[1]);
                    }

                    $order_sn = !empty($change_desc_arr[1]) ? $change_desc_arr[1] : '';

                    if ($order_sn) {
                        $order_res = WholesaleOrderInfo::where('order_sn', $order_sn);
                        $order_res = app(BaseRepository::class)->getToArrayFirst($order_res);
                    } else {
                        $order_res = [];
                    }

                    if (empty($order_res)) {
                        $is_go = false;
                    }
                }
            }
        }

        if ($is_go && ($user_money || $frozen_money || $rank_points || $pay_points)) {
            if (is_array($change_desc)) {
                $change_desc = implode('<br/>', $change_desc);
            }

            /* 插入帐户变动记录 */
            $account_log = [
                'user_id' => $user_id,
                'user_money' => $user_money,
                'frozen_money' => $frozen_money,
                'rank_points' => $rank_points,
                'pay_points' => $pay_points,
                'change_time' => $this->timeRepository->getGmTime(),
                'change_desc' => $change_desc,
                'change_type' => $change_type,
                'deposit_fee' => $deposit_fee
            ];

            AccountLog::insert($account_log);

            /* 更新用户信息 */
            $user_money = $user_money + $deposit_fee;
            $update_log = [
                'frozen_money' => DB::raw("frozen_money  + ('$frozen_money')"),
                'pay_points' => DB::raw("pay_points  + ('$pay_points')"),
                'rank_points' => DB::raw("rank_points  + ('$rank_points')")
            ];

            Users::where('user_id', $user_id)->increment('user_money', $user_money, $update_log);

            if (!$this->userRankService->judgeUserSpecialRank($user_id)) {

                /* 更新会员当前等级 start */
                $user_rank_points = Users::where('user_id', $user_id)->value('rank_points');
                $user_rank_points = $user_rank_points ? $user_rank_points : 0;

                $rank_row = [];
                if ($user_rank_points >= 0) {
                    //1.4.3 会员等级修改（成长值只有下限）
                    $rank_row = $this->userRankService->getUserRankByPoint($user_rank_points);
                }

                if ($rank_row) {
                    $rank_row['discount'] = $rank_row['discount'] / 100.00;
                } else {
                    $rank_row['discount'] = 1;
                    $rank_row['rank_id'] = 0;
                }

                /* 更新会员当前等级 end */
                Users::where('user_id', $user_id)->update(['user_rank' => $rank_row['rank_id']]);
//                Sessions::where('userid', $user_id)->where('adminid', 0)->update(['user_rank' => $rank_row['rank_id']]);
                $userRank = [
                    'user_rank' => $rank_row['rank_id'],
                    'discount' => $rank_row['discount']
                ];
                session($userRank);
            }
        }
    }
}
