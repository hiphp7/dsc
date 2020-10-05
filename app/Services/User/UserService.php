<?php

namespace App\Services\User;

use App\Libraries\Image;
use App\Models\AccountLog;
use App\Models\Article;
use App\Models\Category;
use App\Models\CommentImg;
use App\Models\ComplaintImg;
use App\Models\ComplaintTalk;
use App\Models\CouponsUser;
use App\Models\GoodsReport;
use App\Models\GoodsReportImg;
use App\Models\GoodsReportTitle;
use App\Models\GoodsReportType;
use App\Models\MerchantsStepsFields;
use App\Models\OrderInfo;
use App\Models\Payment;
use App\Models\PresaleActivity;
use App\Models\RegFields;
use App\Models\Region;
use App\Models\SeckillGoods;
use App\Models\SellerShopinfo;
use App\Models\UserAccount;
use App\Models\Users;
use App\Models\UsersLog;
use App\Models\UsersPaypwd;
use App\Models\UsersReal;
use App\Models\UsersVatInvoicesInfo;
use App\Models\ValueCard;
use App\Models\ValueCardType;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Activity\BonusService;
use App\Services\Merchant\MerchantCommonService;

/**
 * 商城会员
 * Class UserService
 * @package App\Services\User
 */
class UserService
{
    protected $baseRepository;
    protected $config;
    protected $timeRepository;
    protected $dscRepository;
    protected $image;
    protected $merchantCommonService;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        Image $image,
        MerchantCommonService $merchantCommonService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->image = $image;
        $this->merchantCommonService = $merchantCommonService;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 取得用户信息
     *
     * @param array $where
     * @return array
     */
    public function userInfo($where = [])
    {
        $user = Users::whereRaw(1);

        if (isset($where['user_id'])) {
            $user = $user->where('user_id', $where['user_id']);
        }

        $user = $user->first();

        $user = $user ? $user->toArray() : [];

        /* 格式化帐户余额 */
        if ($user) {
            unset($user['question']);
            unset($user['answer']);

            if (isset($this->config['show_mobile']) && $this->config['show_mobile'] == 0) {
                $user['mobile_phone'] = $this->dscRepository->stringToStar($user['mobile_phone']);
                $user['user_name'] = $this->dscRepository->stringToStar($user['user_name']);
                $user['email'] = $this->dscRepository->stringToStar($user['email']);
            }

            $user['formated_user_money'] = $this->dscRepository->getPriceFormat($user['user_money'], false);
            $user['formated_frozen_money'] = $this->dscRepository->getPriceFormat($user['frozen_money'], false);
        }

        return $user;
    }

    // 判断有没有开通手机验证、邮箱验证、支付密码
    public function GetValidateInfo($user_id)
    {
        $res = Users::where('user_id', $user_id)
            ->with([
                'getUsersPaypwd' => function ($query) {
                    $query->select('user_id', 'paypwd_id', 'pay_password');
                },
                'getUsersReal' => function ($query) {
                    $query->select('user_id', 'bank_mobile', 'real_name', 'bank_card', 'bank_name', 'review_status')->where('user_type', 0);
                }
            ]);

        $res = $res->first();

        $res = $res ? $res->toArray() : [];

        $res = $res && $res['get_users_paypwd'] ? array_merge($res, $res['get_users_paypwd']) : $res;
        $res = $res && $res['get_users_real'] ? array_merge($res, $res['get_users_real']) : $res;

        return $res;
    }

    // 用户中心 安�    �评级 qin
    public function SecurityRating()
    {
        $user_id = session('user_id', 0);

        $count = 2;
        $count_info = '';
        $Percentage = 0;

        $res = Users::where('user_id', $user_id);

        $res = $res->with([
            'getUsersPaypwd' => function ($query) {
                $query->select('user_id', 'paypwd_id', 'pay_password');
            },
            'getUsersReal' => function ($query) {
                $query->select('user_id', 'real_id', 'bank_mobile', 'real_name', 'bank_card', 'bank_name', 'review_status')
                    ->where('user_type', 0);
            }
        ]);

        $res = $this->baseRepository->getToArrayFirst($res);

        $res = $res && $res['get_users_paypwd'] ? array_merge($res, $res['get_users_paypwd']) : $res;
        $res = $res && $res['get_users_real'] ? array_merge($res, $res['get_users_real']) : $res;

        if ($res) {
            if (isset($res['is_validated']) && $res['is_validated']) {
                // 邮箱
                $count++;
            }

            if (isset($res['mobile_phone']) && $res['mobile_phone']) {
                // 手机
                $count++;
            }

            if (isset($res['pay_password']) && $res['pay_password']) {
                // 支付密码
                $count++;
            }

            if (isset($res['real_id']) && $res['real_id']) {
                // 实名认证
                $count++;
            }

            switch ($count) {
                case 1:
                    $count_info = lang('user.Risk_rating.0');
                    $Percentage = 15;
                    break;
                case 2:
                    $count_info = lang('user.Risk_rating.1');
                    $Percentage = 30;
                    break;
                case 3:
                    $count_info = lang('user.Risk_rating.2');
                    $Percentage = 45;
                    break;
                case 4:
                    $count_info = lang('user.Risk_rating.3');
                    $Percentage = 60;
                    break;
                case 5:
                    $count_info = lang('user.Risk_rating.4');
                    $Percentage = 80;
                    break;
                case 6:
                    $count_info = lang('user.Risk_rating.5');
                    $Percentage = 100;
                    break;

                default:
                    break;
            }
        }

        return ['count' => $count, 'count_info' => $count_info, 'Percentage' => $Percentage];
    }

    /**
     * 保存申请时的上传图片
     *
     * @param array $image_files 上传图片数组
     * @param array $file_id 图片对应的id数组
     * @param array $url
     * @return array|bool
     */
    public function UploadApplyFile($image_files = [], $file_id = [], $url = [])
    {
        /* 是否成功上传 */
        foreach ($file_id as $v) {
            $flag = false;
            if (isset($image_files['error'])) {
                if ($image_files['error'][$v] == 0) {
                    $flag = true;
                }
            } else {
                if ($image_files['tmp_name'][$v] != 'none' && $image_files['tmp_name'][$v]) {
                    $flag = true;
                }
            }
            if ($flag) {
                /*生成上传信息的数组*/
                $upload = [
                    'name' => $image_files['name'][$v],
                    'type' => $image_files['type'][$v],
                    'tmp_name' => $image_files['tmp_name'][$v],
                    'size' => $image_files['size'][$v],
                ];
                if (isset($image_files['error'])) {
                    $upload['error'] = $image_files['error'][$v];
                }

                $img_original = $this->image->upload_image($upload);
                if ($img_original === false) {
                    return $this->image->error_msg();
                }
                $img_url[$v] = $img_original;
                /*删除原文件*/
                if (!empty($url[$v])) {
                    @unlink(storage_public($url[$v]));
                    unset($url[$v]);
                }
            }
        }
        $return_file = [];
        if ($url) {
            foreach ($url as $k => $v) {
                if ($v == '') {
                    unset($url[$k]);
                }
            }
        }
        if (!empty($url) && !empty($img_url)) {
            $return_file = $url + $img_url;
        } elseif (!empty($url)) {
            $return_file = $url;
        } elseif (!empty($img_url)) {
            $return_file = $img_url;
        }
        if (!empty($return_file)) {
            return $return_file;
        } else {
            return false;
        }
    }

    public function CreatePassword($pw_length = 8)
    {
        $randpwd = '';
        for ($i = 0; $i < $pw_length; $i++) {
            $randpwd .= chr(mt_rand(33, 126));
        }

        return $randpwd;
    }

    /*
    * 判断预售商品是否处在尾款结算状态 liu
    */
    public function PresaleSettleStatus($extension_id)
    {
        $time = $this->timeRepository->getGmTime();
        $row = PresaleActivity::where('act_id', $extension_id)
            ->where('review_status', 3)
            ->first();

        $row = $row ? $row->toArray() : [];

        $result = [];
        $result['info'] = [];
        if ($row) {
            $result['info'] = $row;

            if ($row['pay_start_time'] <= $time && $row['pay_end_time'] >= $time) {
                $result['start_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $row['pay_start_time']);
                $result['end_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $row['pay_end_time']);
                $result['settle_status'] = 1; //在支付尾款时间段内
            } elseif ($row['pay_end_time'] < $time) {
                $result['start_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $row['pay_start_time']);
                $result['end_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $row['pay_end_time']);
                $result['settle_status'] = -1; //超出支付尾款时间
            } else {
                $result['start_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $row['pay_start_time']);
                $result['end_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $row['pay_end_time']);
                $result['settle_status'] = 0; //未到付款时间
            }
        }

        return $result;
    }

    /* 取得储值卡使用限制说明 */
    public function GetExplain($vid)
    {
        $rz_shopName = [];
        $arr = [];
        $row = ValueCardType::whereHas('getValueCard', function ($query) use ($vid) {
            $query->where('vid', $vid);
        });

        $row = $row->first();

        $row = $row ? $row->toArray() : [];

        if ($row['use_condition'] == 0) {
            $explain = $GLOBALS['_LANG']['all_goods_explain'];
        } elseif ($row['use_condition'] == 1) {
            $res = [];
            if ($row['spec_cat']) {
                $spec_cat = !is_array($row['spec_cat']) ? explode(",", $row['spec_cat']) : $row['spec_cat'];
                $res = Category::whereIn('cat_id', $spec_cat)->get();
                $res = $res ? $res->toArray() : [];
            }

            $explain = str_replace('%', $this->cat_format($res), $GLOBALS['_LANG']['spec_cat_explain']);
        } elseif ($row['use_condition'] == 2) {
            $explain['explain'] = str_replace('%', $row['spec_goods'], $GLOBALS['_LANG']['spec_goods_explain']);
            $explain['goods_ids'] = $row['spec_goods'];
        } else {
            $explain = '';
        }
        $other_explain = '';
        if ($row['use_merchants'] == 'all') {
            $other_explain = ' | ' . $GLOBALS['_LANG']['all_merchants'];
        } elseif ($row['use_merchants'] == 'self') {
            $other_explain = ' | ' . $GLOBALS['_LANG']['self_merchants'];
        } elseif (!empty($row['use_merchants'])) {
            $ru_ids = explode(',', $row['use_merchants']);
            if (!empty($ru_ids)) {
                foreach ($ru_ids as $k => $v) {
                    $shop_name = [];
                    $shop_name['shop_name'] = $this->merchantCommonService->getShopName($v, 1);
                    $build_uri = [
                        'urid' => $v,
                        'append' => $shop_name['shop_name']
                    ];

                    $domain_url = $this->merchantCommonService->getSellerDomainUrl($v, $build_uri);
                    $shop_name['shop_url'] = $domain_url['domain_name'];
                    $rz_shopName[] = $shop_name;
                }
            }
            $other_explain = ' | ' . $GLOBALS['_LANG']['assign_merchants'];
        }
        $arr['rz_shopNames'] = $rz_shopName;
        if ($other_explain) {
            $arr['explain'] = $explain; //. $other_explain;
            $arr['other_explain'] = $other_explain;
        } else {
            $arr['explain'] = $explain;
        }

        return $arr;
    }

    /**
     *  获取举报列表
     *
     * @access  public
     * @param int $num 列表最大数量
     * @param int $start 列表起始位置
     * @return  array       $order_list     列表
     */
    public function getGoodsReportList($num = 10, $start = 0)
    {
        $user_id = session('user_id', 0);

        $row = GoodsReport::where('user_id', $user_id)
            ->where('report_state', '<', 3);

        $row = $row->with([
            'getGoods'
        ]);

        $row = $row->orderBy('add_time', 'desc');

        if ($start > 0) {
            $row = $row->skip($start);
        }

        if ($num > 0) {
            $row = $row->take($num);
        }

        $row = $row->get();

        $row = $row ? $row->toArray() : [];

        if ($row) {
            foreach ($row as $k => $v) {
                if ($v['title_id'] > 0) {
                    $row[$k]['title_name'] = GoodsReportTitle::where('title_id', $v['title_id'])->value('title_name');
                }

                if ($v['type_id'] > 0) {
                    $row[$k]['type_name'] = GoodsReportType::where('type_id', $v['type_id'])->value('type_name');
                }
                if ($v['add_time'] > 0) {
                    $row[$k]['add_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $v['add_time']);
                }

                $row[$k]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $v['goods_id']], $v['goods_name']);

                $ru_id = $v['get_goods']['user_id'] ?? 0;
                $row[$k]['shop_name'] = $this->merchantCommonService->getShopName($ru_id, 1);

                $row[$k]['goods_thumb'] = $this->dscRepository->getImagePath($v['goods_image']);
            }
        }

        return $row;
    }

    /**
     *  获取订单投诉数量
     *
     * @access  public
     * @return  array
     */
    public function GetComplaintCount($user_id = 0, $dealy_time = 0, $is_complaint = 0, $keyword = '')
    {
        $time = $this->timeRepository->getGmTime();

        $record_count = OrderInfo::where('main_count', 0)
            ->where('user_id', $user_id)
            ->where('is_delete', 0);

        if (!empty($keyword)) {
            $record_count = $record_count->where('order_sn', 'like', '%' . $keyword . '%');
        }

        $record_count = $record_count->where('is_zc_order', 0);

        $record_count = $record_count->where(function ($query) use ($is_complaint) {
            $query->complaintCount($is_complaint);
        });

        if ($is_complaint == 0) {

            //获取已确认，已分单，部分分单，已付款，已发货或者已确认收货15天内的订单
            $record_count = $record_count->whereIn('order_status', [OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART]);

            $where_confirmed = "IF(pay_status = " . PS_PAYED . ", IF(shipping_status = " . SS_RECEIVED . ", shipping_status = '" . SS_RECEIVED . "' AND ('$time'- confirm_take_time) < '$dealy_time', shipping_status <> " . SS_UNSHIPPED . ")";
            $where_confirmed .= " AND pay_status = " . PS_PAYED . ", IF(shipping_status = " . SS_RECEIVED . ", shipping_status = " . SS_RECEIVED . " AND ('$time'- confirm_take_time) < '$dealy_time', shipping_status <> " . SS_UNSHIPPED . "))";

            $record_count = $record_count->whereRaw($where_confirmed);
        }

        $record_count = $record_count->count();

        return $record_count;
    }

    /**
     *  获取订单投诉列表
     *
     * @access  public
     * @param int $num 列表最大数量
     * @param int $start 列表起始位置
     * @return  array       $order_list     列表
     */
    public function GetComplaintList($user_id = 0, $dealy_time = 0, $num = 10, $start = 0, $is_complaint = 0, $keyword = '')
    {
        $time = $this->timeRepository->getGmTime();

        $res = OrderInfo::selectRaw("*, (goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax - discount) AS total_fee")
            ->where('main_count', 0)
            ->where('user_id', $user_id)->where('is_delete', 0);

        if (!empty($keyword)) {
            $res = $res->where('order_sn', 'like', '%' . $keyword . '%');
        }

        $res = $res->where('is_zc_order', 0);

        $res = $res->where(function ($query) use ($is_complaint) {
            $query->complaintCount($is_complaint);
        });

        if ($is_complaint == 0) {
            //获取已确认，已分单，部分分单，已付款，已发货或者已确认收货15天内的订单
            $res = $res->whereIn('order_status', [OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART]);
            $where_confirmed = "IF(pay_status = " . PS_PAYED . ", IF(shipping_status = " . SS_RECEIVED . ", shipping_status = '" . SS_RECEIVED . "' AND ('$time'- confirm_take_time) < '$dealy_time', shipping_status <> " . SS_UNSHIPPED . ")";
            $where_confirmed .= " AND pay_status = " . PS_PAYED . ", IF(shipping_status = " . SS_RECEIVED . ", shipping_status = " . SS_RECEIVED . " AND ('$time'- confirm_take_time) < '$dealy_time', shipping_status <> " . SS_UNSHIPPED . "))";

            $res = $res->whereRaw($where_confirmed);
        }

        $res = $res->with([
            'getComplaint' => function ($query) {
                $query->selectRaw("order_id, IFNULL(complaint_id, 0) AS is_complaint, complaint_state, complaint_active");
            },
            'getOrderGoods' => function ($query) {
                $query->select('ru_id', 'goods_id');
            }
        ]);

        $res = $res->orderBy('add_time', 'desc');

        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($num > 0) {
            $res = $res->take($num);
        }

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        $arr = [];
        if ($res) {
            foreach ($res as $row) {
                $row = $this->baseRepository->getArrayMerge($row, $row['get_complaint']);

                $order_goods = $row['get_order_goods'];
                $row['ru_id'] = $order_goods ? $order_goods['ru_id'] : 0;
                $row['goods_id'] = $order_goods ? $order_goods['goods_id'] : 0;
                $row['order_goods'] = get_order_goods_toInfo($row['order_id']);
                $row['shop_name'] = $this->merchantCommonService->getShopName($row['ru_id'], 1);
                $row['shop_ru_id'] = $row['ru_id'];

                $build_uri = [
                    'urid' => $row['ru_id'],
                    'append' => $row['shop_name']
                ];

                $domain_url = $this->merchantCommonService->getSellerDomainUrl($row['ru_id'], $build_uri);
                $row['shop_url'] = $domain_url['domain_name'];

                $basic_info = SellerShopinfo::where('ru_id', $row['ru_id'])->first();
                $basic_info = $basic_info ? $basic_info->toArray() : [];

                $chat = $this->dscRepository->chatQq($basic_info);

                //IM or 客服
                if ($this->config['customer_service'] == 0) {
                    $ru_id = 0;
                } else {
                    $ru_id = $row['ru_id'];
                }

                $shop_information = $this->merchantCommonService->getShopName($ru_id); //通过ru_id获取到店铺信息;

                //判断当前商家是平台,还是入驻商家 bylu
                if ($ru_id == 0) {
                    //判断平台是否开启了IM在线客服
                    $kf_im_switch = SellerShopinfo::where('ru_id', 0)->value('kf_im_switch');
                    if ($kf_im_switch) {
                        $row['is_dsc'] = true;
                    } else {
                        $row['is_dsc'] = false;
                    }
                } else {
                    $row['is_dsc'] = false;
                }

                $row['has_talk'] = 0;

                //获取是否存在未读信息
                if (isset($row['complaint_state']) && $row['complaint_state'] > 1) {
                    $talk_list = ComplaintTalk::where('complaint_id', $row['is_complaint'])
                        ->orderBy('talk_time', 'desc')
                        ->get();

                    $talk_list = $talk_list ? $talk_list->toArray() : [];

                    if ($talk_list) {
                        foreach ($talk_list as $k => $v) {
                            if ($v['view_state']) {
                                $view_state = explode(',', $v['view_state']);
                                if (!in_array('user', $view_state)) {
                                    $row['has_talk'] = 1;
                                    break;
                                }
                            }
                        }
                    }
                }

                $arr[] = ['order_id' => $row['order_id'],
                    'order_sn' => $row['order_sn'],
                    'order_time' => $this->timeRepository->getLocalDate($this->config['time_format'], $row['add_time']),
                    'is_IM' => $shop_information['is_IM'], //平台是否允许商家使用"在线客服";
                    'is_dsc' => $row['is_dsc'],
                    'ru_id' => $row['ru_id'],
                    'shop_name' => $row['shop_name'], //店铺名称	,
                    'shop_url' => $row['shop_url'], //店铺名称	,
                    'order_goods' => $row['order_goods'],
                    'no_picture' => $this->config['no_picture'],
                    'kf_type' => $chat['kf_type'],
                    'kf_ww' => $chat['kf_ww'],
                    'kf_qq' => $chat['kf_qq'],
                    'total_fee' => isset($row['total_fee']) ? $this->dscRepository->getPriceFormat($row['total_fee'], false) : 0,
                    'is_complaint' => isset($row['is_complaint']) ? $row['is_complaint'] : 0,
                    'complaint_state' => isset($row['complaint_state']) ? $row['complaint_state'] : 0,
                    'complaint_active' => isset($row['complaint_active']) ? $row['complaint_active'] : '',
                    'has_talk' => $row['has_talk']
                ];
            }
        }

        return $arr;
    }

    /**
     * 违规举报图片
     */
    public function ReportImagesList($where = [])
    {
        $img_list = $this->getGoodsReportImgList($where);

        if ($img_list) {
            foreach ($img_list as $key => $row) {
                $img_list[$key]['comment_img'] = $this->dscRepository->getImagePath($row['comment_img']);
            }
        }

        return $img_list;
    }

    /**
     * 获取会员操作日志列表
     *
     * @param int $user_id
     * @param int $num
     * @param int $start
     * @return array
     */
    public function GetUsersLogList($user_id = 0, $num = 10, $start = 0)
    {
        $row = UsersLog::where('change_type', '<>', 9)
            ->where('user_id', $user_id);

        $row = $row->with([
            'getAdminUser'
        ]);

        if ($start > 0) {
            $row = $row->skip($start);
        }

        if ($num > 0) {
            $row = $row->take($num);
        }

        $row = $row->get();

        $row = $row ? $row->toArray() : [];

        if ($row) {
            foreach ($row as $k => $v) {
                if ($v['change_time'] > 0) {
                    $row[$k]['change_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $v['change_time']);
                }

                if ($v['admin_id'] > 0) {
                    $user_name = $v['get_admin_user']['user_name'] ?? '';
                    $row[$k]['admin_name'] = sprintf(lang('user.admin_user'), $user_name);
                }
            }
        }

        return $row;
    }

    /**
     * 判断秒杀活动是否失效
     *
     * @access  public
     * @param int $goods_id 秒杀商品ID
     * @return
     */
    public function is_invalid($goods_id = 0)
    {
        $row = SeckillGoods::select('sec_id')->where('id', $goods_id);
        $row = $row->with([
            'getSeckill' => function ($query) {
                $query->select('sec_id', 'is_putaway', 'acti_time');
            }
        ]);
        $row = $row->first();

        $row = $row ? $row->toArray() : [];

        $row = $row && $row['get_seckill'] ? array_merge($row, $row['get_seckill']) : $row;

        $time = $this->timeRepository->getGmTime();
        if ($row && ($row['is_putaway'] == 0 || $row['acti_time'] < $time)) {
            return true; //失效
        } else {
            return false; //有效
        }
    }

    /* 用户注册 计算用户名长度 */

    public function DealJsStrlen($str)
    {
        $strlen = strlen($str);

        //汉字长度
        $zhcn_len = 0;
        $pattern = '/[^\x00-\x80]+/';
        if (preg_match_all($pattern, $str, $matches)) {
            $words = $matches[0];
            foreach ($words as $word) {
                $zhcn_len += strlen($word);
            }
        }
        //剩余长度
        $left_len = $strlen - $zhcn_len;
        //转换长度
        $deal_len = $left_len + $zhcn_len / 3 * 2;
        return $deal_len;
    }

    //获取余额记录总数
    public function GetUserAccountLogCount($user_id = 0, $account_type = '')
    {
        /* 获取记录条数 */
        $record_count = AccountLog::where('user_id', $user_id)
            ->where($account_type, '<>', 0)
            ->count();

        return $record_count;
    }

    //获取余额记录
    public function GetUserAccountLogList($user_id = 0, $account_type = '', $pager = [])
    {
        $res = AccountLog::where('user_id', $user_id)
            ->where($account_type, '<>', 0);

        $res = $res->orderBy('log_id', 'desc');

        if ($pager) {
            if ($pager['start'] > 0) {
                $res = $res->skip($pager['start']);
            }

            if ($pager['size'] > 0) {
                $res = $res->take($pager['size']);
            }
        }

        $res = $res->get();

        $res = $res ? $res->toArray() : [];
        $account_log = [];
        if ($res) {
            foreach ($res as $row) {
                $row['change_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $row['change_time']);
                $row['type'] = $row[$account_type] > 0 ? lang('user.account_inc') : lang('user.account_dec');
                $row['user_money'] = $this->dscRepository->getPriceFormat(abs($row['user_money']), false);
                $row['frozen_money'] = $this->dscRepository->getPriceFormat(abs($row['frozen_money']), false);
                $row['rank_points'] = abs($row['rank_points']);
                $row['pay_points'] = abs($row['pay_points']);
                $row['short_change_desc'] = $this->dscRepository->subStr($row['change_desc'], 60);
                $row['amount'] = $row[$account_type];
                $account_log[] = $row;
            }
        }

        return $account_log;
    }

    /**
     *  获取上传凭证图片列表
     *
     * @access  public
     * @param array $where
     * @return  bool
     */
    public function getGoodsReportImgList($where = [])
    {
        $img_list = GoodsReportImg::selectRaw('*, img_file as comment_img, img_id as id');

        if (isset($where['user_id'])) {
            $img_list = $img_list->where('user_id', $where['user_id']);
        }

        if (isset($where['goods_id'])) {
            $img_list = $img_list->where('goods_id', $where['goods_id']);
        }

        if (isset($where['report_id'])) {
            $img_list = $img_list->where('report_id', $where['report_id']);
        }

        $img_list = $img_list->orderBy('id', 'desc');

        $img_list = $img_list->get();

        $img_list = $img_list ? $img_list->toArray() : [];

        if ($img_list) {
            foreach ($img_list as $key => $row) {
                $img_list[$key]['comment_img'] = $this->dscRepository->getImagePath($row['img_file']);
            }
        }

        return $img_list;
    }

    /**
     *  获取上传投诉图片列表
     *
     * @access  public
     * @param array $where
     * @return  bool
     */
    public function getComplaintImgList($where = [])
    {
        $img_list = ComplaintImg::selectRaw('*, img_id as id ,img_file as comment_img');

        if (isset($where['user_id'])) {
            $img_list = $img_list->where('user_id', $where['user_id']);
        }

        if (isset($where['order_id'])) {
            $img_list = $img_list->where('order_id', $where['order_id']);
        }

        if (isset($where['complaint_id'])) {
            $img_list = $img_list->where('complaint_id', $where['complaint_id']);
        }

        $img_list = $img_list->orderBy('id', 'desc');

        $img_list = $img_list->get();

        $img_list = $img_list ? $img_list->toArray() : [];

        if ($img_list) {
            foreach ($img_list as $key => $row) {
                $img_list[$key]['img_file'] = $this->dscRepository->getImagePath($row['img_file']);
                $img_list[$key]['comment_img'] = $this->dscRepository->getImagePath($row['comment_img']);
            }
        }

        return $img_list;
    }

    /**
     *  获取会员优惠券数量与金额
     *
     * @access  public
     * @param array $where
     * @return  array
     */
    public function getUserCouMoney($where = [])
    {
        $cou = CouponsUser::where('user_id', $where['user_id'])->where('is_use', 0)->where('is_use_time', 0);

        $time = $this->timeRepository->getGmTime();

        $cou = $cou->whereHas('getCoupons', function ($query) use ($time) {
            $query->where('cou_start_time', '<', $time)->where('cou_end_time', '>', $time);
        });

        $cou = $cou->with([
            'getCoupons'
        ]);

        $coupon_num = $cou->count();

        $cou = $cou->get();

        $cou = $cou ? $cou->toArray() : [];

        $money = 0;
        if ($cou) {
            foreach ($cou as $key => $row) {
                $cou_money = $row && $row['get_coupons'] ? $row['get_coupons']['cou_money'] : 0;
                $money += $cou_money;
            }
        }

        return ['num' => $coupon_num, 'money' => $money];
    }

    /**
     *  获取会员储值卡信息
     *
     * @access  public
     * @param array $where
     * @return  bool
     */
    public function getValueCardInfo($where = [])
    {
        if (isset($where['user_id'])) {
            $res = ValueCard::selectRaw("COUNT(*) AS num, SUM(card_money) AS money")
                ->whereRaw(1);
        } else {
            $res = ValueCard::whereRaw(1);
        }


        if (isset($where['user_id'])) {
            $res = $res->where('user_id', $where['user_id']);
        }

        $res = $res->first();

        $res = $res ? $res->toArray() : [];

        return $res;
    }

    /**
     *  获取会员注册字段列表
     *
     * @access  public
     * @param array $where
     * @return  bool
     */
    public function getRegFieldsList($where = [])
    {
        $res = RegFields::whereRaw(1);

        if (isset($where['type'])) {
            if (is_array($where['type'])) {
                $res = $res->where('type', $where['type'][0], $where['type'][1]);
            } else {
                $res = $res->where('type', $where['type']);
            }
        }

        if (isset($where['display'])) {
            $res = $res->where('display', $where['display']);
        }

        if (isset($where['sort']) && isset($where['order'])) {
            if (is_array($where['sort'])) {
                $where['sort'] = implode(",", $where['sort']);
                $res = $res->orderByRaw($where['sort'] . " " . $where['order']);
            } else {
                $res = $res->orderBy($where['sort'], $where['order']);
            }
        }

        $res = $res->get();

        $res = $res->toArray();

        return $res;
    }

    /**
     *  获取会员增值发票信息
     *
     * @access  public
     * @param array $where
     * @return  bool
     */
    public function getUsersVatInvoicesInfo($where = [])
    {
        $res = UsersVatInvoicesInfo::whereRaw(1);

        if (isset($where['id'])) {
            $res = $res->where('id', $where['id']);
        }

        if (isset($where['user_id'])) {
            $res = $res->where('user_id', $where['user_id']);
        }

        $res = $res->first();

        $res = $res ? $res->toArray() : [];

        if (!empty($res['id'])) {
            $res['province_name'] = !empty($res['province']) ? $this->getRegionName($res['province']) : '';
            $res['city_name'] = !empty($res['city']) ? $this->getRegionName($res['city']) : '';
            $res['district_name'] = !empty($res['district']) ? $this->getRegionName($res['district']) : '';
        }

        return $res;
    }

    /**
     *  获取会员留言图片
     *
     * @access  public
     * @param array $where
     * @return  bool
     */
    public function getCommentImgList($where = [])
    {
        $res = CommentImg::whereRaw(1);

        if (isset($where['user_id'])) {
            $res = $res->where('user_id', $where['user_id']);
        }

        if (isset($where['comment_id'])) {
            $res = $res->where('comment_id', $where['comment_id']);
        }

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        return $res;
    }

    /**
     *  获取会员优惠券总数和金额
     *
     * @access  public
     * @param array $where
     * @return  bool
     */
    public function getCouponsUserTotal($user_id = 0)
    {
        $time = $this->timeRepository->getGmTime();
        $user = Users::where('user_id', $user_id);
        $user = $user->with([
            'getCouponsUserList' => function ($query) use ($time) {
                $query->select('user_id', 'uc_id', 'cou_money')
                    ->where('cou_id', '>', 0)
                    ->where('is_use', '=', 0)
                    ->where('is_use_time', '=', 0)
                    ->whereHas('getCoupons', function ($query) use ($time) {
                        $query->where('cou_start_time', '<', $time)->where('cou_end_time', '>', $time);
                    });
            }
        ]);

        $user = $this->baseRepository->getToArrayFirst($user);

        $num = 0;
        $money = 0;
        if ($user) {
            foreach ($user['get_coupons_user_list'] as $row) {
                $num += 1;
                $money += $row['cou_money'] ?? 0;
            }
        }

        $cou = [
            'num' => $num,
            'money' => $money
        ];

        return $cou;
    }

    /**
     *  获取会员订单分成数量
     *
     * @access  public
     * @param array $where
     * @return  bool
     */
    public function getUserOrderAffiliateCount($where = [])
    {
        $sqlcount = OrderInfo::where('main_count', 0)
            ->where('user_id', '>', 0)
            ->where(function ($query) use ($where) {
                $query = $query->where(function ($query) use ($where) {
                    $query->whereHas('getUsers', function ($query) use ($where) {
                        $where['all_uid'] = !is_array($where['all_uid']) ? explode(",", $where['all_uid']) : $where['all_uid'];

                        $query->whereIn('parent_id', $where['all_uid'])->where('is_separate', 0);
                    });
                });

                $query->orWhere(function ($query) use ($where) {
                    $query->whereHas('getAffiliateLog', function ($query) use ($where) {
                        $query->where('user_id', $where['user_id'])->where('is_separate', '>', 0);
                    });
                });
            });

        $sqlcount = $sqlcount->where(function ($query) {
            $query->whereHas('getOrderGoods', function ($query) {
                $query->sellerCount();
            });
        });

        $sqlcount = $sqlcount->count();

        return $sqlcount;
    }

    /**
     * 获取会员订单分成数量
     *
     * @param array $where
     * @param int $page
     * @param int $size
     * @return array
     */
    public function getUserOrderAffiliateList($where = [], $page = 1, $size = 10)
    {
        $res = OrderInfo::where('main_count', 0)
            ->where('user_id', '>', 0)
            ->where(function ($query) use ($where) {
                $query = $query->where(function ($query) use ($where) {
                    $query->whereHas('getUsers', function ($query) use ($where) {
                        $where['all_uid'] = !is_array($where['all_uid']) ? explode(",", $where['all_uid']) : $where['all_uid'];

                        $query->whereIn('parent_id', $where['all_uid'])->where('is_separate', 0);
                    });
                });

                $query->orWhere(function ($query) use ($where) {
                    $query->whereHas('getAffiliateLog', function ($query) use ($where) {
                        $query->where('user_id', $where['user_id'])->where('is_separate', '>', 0);
                    });
                });
            });

        $res = $res->where(function ($query) {
            $query->whereHas('getOrderGoods', function ($query) {
                $query->sellerCount();
            });
        });

        $res = $res->orderBy('order_id', 'desc');

        $start = ($page - 1) * $size;

        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        return $res;
    }

    /**
     *  获取会员跟踪包裹数量
     *
     * @access  public
     * @param array $where
     * @return  bool
     */
    public function getTrackPackagesCount($user_id = 0)
    {
        $record_count = OrderInfo::where('main_count', 0)
            ->where('user_id', $user_id)
            ->whereIn('shipping_status', [SS_SHIPPED, SS_RECEIVED]);

        $record_count = $record_count->count();

        return $record_count;
    }

    /**
     *  获取会员跟踪包裹列表
     *
     * @access  public
     * @param array $where
     * @return  bool
     */
    public function getTrackPackagesList($user_id = 0, $page = 1, $size = 10)
    {
        $res = OrderInfo::where('main_count', 0)
            ->where('user_id', $user_id)
            ->whereIn('shipping_status', [SS_SHIPPED, SS_RECEIVED]);

        $res = $res->orderBy('order_id', 'desc');

        $start = ($page - 1) * $size;
        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        return $res;
    }

    public function cat_format($res)
    {
        if ($res) {
            $result = '';
            foreach ($res as $v) {
                $result .= '<a href="category.php?id=' . $v['cat_id'] . '" style="color:red;">' . $v['cat_name'] . '</a>' . '，';
            }
            $result = rtrim($result, '，');
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 查询会员余额的操作记录
     *
     * @access  public
     * @param int $user_id 会员ID
     * @param int $size 每页显示数量
     * @param int $start 开始显示的条数
     * @return  array
     */
    public function getAccountLog($user_id, $size, $start)
    {
        $res = UserAccount::where('user_id', $user_id)
            ->whereIn('process_type', [SURPLUS_SAVE, SURPLUS_RETURN]);

        $res = $res->orderBy('add_time', 'desc');

        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = app(BaseRepository::class)->getToArrayGet($res);

        $account_log = [];
        if ($res) {
            foreach ($res as $rows) {
                $rows['add_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $rows['add_time']);
                $rows['admin_note'] = nl2br(htmlspecialchars($rows['admin_note']));
                $rows['short_admin_note'] = ($rows['admin_note'] > '') ? $this->dscRepository->subStr($rows['admin_note'], 30) : 'N/A';
                $rows['user_note'] = nl2br(htmlspecialchars($rows['user_note']));
                $rows['short_user_note'] = ($rows['user_note'] > '') ? $this->dscRepository->subStr($rows['user_note'], 30) : 'N/A';
                $rows['pay_status'] = ($rows['is_paid'] == 0) ? $GLOBALS['_LANG']['un_confirmed'] : $GLOBALS['_LANG']['is_confirmed'];
                $rows['amount'] = $this->dscRepository->getPriceFormat(abs($rows['amount']), false);

                /* 会员的操作类型： 冲值，提现 */
                if ($rows['process_type'] == 0) {
                    $rows['type'] = $GLOBALS['_LANG']['surplus_type_0'];
                } else {
                    $rows['type'] = $GLOBALS['_LANG']['surplus_type_1'];
                }

                /* 支付方式的ID */
                if ($rows['pay_id'] > 0) {
                    $pid = $rows['pay_id'];
                } else {
                    $pid = Payment::where('pay_name', $rows['payment'])->where('enabled')->value('pay_id');
                }

                /* 如果是预付款而且还没有付款, 允许付款 */
                if (($rows['is_paid'] == 0) && ($rows['process_type'] == 0)) {
                    $rows['handle'] = '<a href="user.php?act=pay&id=' . $rows['id'] . '&pid=' . $pid . '" class="ftx-01">' . $GLOBALS['_LANG']['pay'] . '</a>';
                }

                $account_log[] = $rows;
            }
        }

        return $account_log;
    }

    /**获取用户消费积分记录
     * @param $user_id
     * @param int $page
     * @param int $size
     * @param string $type
     * @return array
     */
    public function getUserPayPoints($user_id, $page = 1, $size = 10, $type)
    {
        $res = AccountLog::where('user_id', $user_id)
            ->where($type, '<>', 0)
            ->orderBy('log_id', 'desc')
            ->offset(($page - 1) * $size)
            ->limit($size)
            ->get();
        $res = $res ? $res->toArray() : [];
        $account_log = [];
        if ($res) {
            foreach ($res as $k => $row) {
                $row['change_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $row['change_time']);
                $row['type'] = $row[$type] > 0 ? '+' : '-';
                $row['user_money'] = $this->dscRepository->getPriceFormat(abs($row['user_money']), false);
                $row['frozen_money_format'] = $this->dscRepository->getPriceFormat(abs($row['frozen_money']), false);
                $row['rank_points'] = abs($row['rank_points']);
                $row['pay_points'] = abs($row['pay_points']);
                $row['short_change_desc'] = $this->dscRepository->subStr($row['change_desc'], 60);
                $temp = explode(',', $row['short_change_desc']);
                if (count($temp) == 2) {
                    $row['short_change_desc_part1'] = $temp[0];
                    $row['short_change_desc_part2'] = $temp[1];
                }
                $row['amount'] = $row[$type];
                $account_log[] = $row;
            }
        }
        return $account_log;
    }


    /**
     * 查询用户是否实名认证
     *
     * @param int $user_id
     * @param int $user_type 0 会员实名认证 1 ，商家实名认证
     * @return int
     */
    public function userReal($user_id = 0, $user_type = 0)
    {
        $count = UsersReal::where('user_id', $user_id)->where('user_type', $user_type)->count();

        return empty($count) ? 0 : 1;
    }

    /**
     * 当前会员是否是商家
     *
     * @param int $user_id
     * @return int
     */
    public function isSeller($user_id = 0)
    {
        $is_jurisdiction = 0;
        if ($user_id > 0) {
            // 判断是否是商家
            $seller_count = SellerShopinfo::where('ru_id', $user_id)->count();

            $is_jurisdiction = $seller_count > 0 ? 1 : 0;

            //判断是否是厂商
            $is_chang_count = MerchantsStepsFields::where('user_id', $user_id)->where('company_type', '厂商')->count();

            if ($is_chang_count > 0) {
                $is_jurisdiction = 0;
            }
        }
        return $is_jurisdiction;
    }

    /**
     * 用户支付密码
     * @param int $user_id
     * @return array
     */
    public function getPaypwd($user_id = 0)
    {
        $result = UsersPaypwd::where('user_id', $user_id)->first();

        return $result ? $result->toArray() : [];
    }

    public function getUserHelpart()
    {
        $article_id = $this->config['user_helpart'];
        $arr = [];

        $new_article = substr($article_id, -1);
        if ($new_article == ',') {
            $article_id = substr($article_id, 0, -1);
        }

        if (!empty($article_id)) {
            $article_id = !is_array($article_id) ? explode(",", $article_id) : $article_id;

            $res = Article::whereIn('article_id', $article_id)
                ->orderBy('article_id', 'desc');

            $res = $this->baseRepository->getToArrayGet($res);

            if ($res) {
                foreach ($res as $key => $row) {
                    $arr[$key]['article_id'] = $row['article_id'];
                    $arr[$key]['title'] = $row['title'];
                    $arr[$key]['url'] = $this->dscRepository->buildUri('article', ['aid' => $row['article_id']], $row['title']);
                }
            }
        }

        return $arr;
    }

    /**获取地区名称
     * @param $regionId
     * @return string
     */
    public function getRegionName($regionId)
    {
        $regionName = Region::where('region_id', $regionId)
            ->pluck('region_name')
            ->toArray();
        if (empty($regionName)) {
            return '';
        }

        return $regionName[0];
    }


    /**
     * 判断会员是否有虚拟资产
     * @param int $user_id 用户id
     * @return array          虚拟资产信息
     */
    public function getUserAccount($user_id)
    {
        $result = [
            'type' => false, //是否有资产
            'data' => [
                'money' => ['text' => $GLOBALS['_LANG']['virtual_assets'][0], 'num' => 0], //会员余额
                'frozen' => ['text' => $GLOBALS['_LANG']['virtual_assets'][1], 'num' => 0], //冻结金额
                'point' => ['text' => $GLOBALS['_LANG']['virtual_assets'][2], 'num' => 0], //消费积分
                'coupons' => ['text' => $GLOBALS['_LANG']['virtual_assets'][3], 'num' => 0], //优惠券数量
                'bonus' => ['text' => $GLOBALS['_LANG']['virtual_assets'][4], 'num' => 0], //红包数量
                'card' => ['text' => $GLOBALS['_LANG']['virtual_assets'][5], 'num' => 0] //储值卡余额
            ]
        ];

        $time = $this->timeRepository->getGmTime();

        //查询出 会员余额 、 消费积分 、 冻结余额
        $user = Users::select('user_money', 'frozen_money', 'pay_points')->where('user_id', $user_id);
        $user = $this->baseRepository->getToArrayFirst($user);
        $result['data']['money']['num'] = $user['user_money'];
        $result['data']['frozen']['num'] = $user['frozen_money'];
        $result['data']['point']['num'] = $user['pay_points'];
        if ($user['user_money'] != 0 || $user['frozen_money'] > 0 || $user['pay_points'] > 0) {
            $result['type'] = true;
        }

        //查询出会员优惠券数量
        $coupons_user = CouponsUser::select('cou_money')
            ->where('order_id', 0)
            ->where('user_id', $user_id)
            ->where('is_use', 0);

        $coupons_user = $coupons_user->whereHas('getCoupons', function ($query) use ($time) {
            $query->where('review_status', 3)
                ->where('cou_start_time', '<', $time)
                ->where('cou_end_time', '>', $time);
        });

        $coupons_user = $coupons_user->with(['getCoupons']);
        $coupons_user = $coupons_user->groupBy('uc_id');
        $coupons_user = $this->baseRepository->getToArrayGet($coupons_user);
        $result['data']['coupons']['num'] = count($coupons_user);
        if ($result['data']['coupons']['num'] > 0) {
            $result['type'] = true;
        }

        //查询出会员红包数量
        $result['data']['bonus']['num'] = app(BonusService::class)->getUserBounsNewCount($user_id, 0);
        if ($result['data']['bonus']['num'] > 0) {
            $result['type'] = true;
        }

        //查询出会员储值卡数量
        $result['data']['card']['num'] = ValueCard::where('user_id', $user_id)
            ->where('end_time', '>', $time)
            ->where('card_money', '>', 0)
            ->sum('card_money');
        if ($result['data']['card']['num'] > 0) {
            $result['type'] = true;
        }

        return $result;
    }
}
