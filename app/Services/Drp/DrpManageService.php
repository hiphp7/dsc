<?php

namespace App\Services\Drp;

use App\Custom\Distribute\Models\DrpAccountLog;
use App\Custom\Distribute\Services\DistributeService;
use App\Models\DrpLog;
use App\Models\DrpShop;
use App\Models\OrderInfo;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Merchant\MerchantCommonService;
use App\Services\User\UserRankService;
use App\Services\Wechat\WechatService;
use Illuminate\Support\Facades\Storage;


class DrpManageService
{
    protected $dscRepository;
    protected $timeRepository;
    protected $baseRepository;
    protected $merchantCommonService;
    protected $config;

    public function __construct(
        DscRepository $dscRepository,
        TimeRepository $timeRepository,
        BaseRepository $baseRepository,
        MerchantCommonService $merchantCommonService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 目录下图片
     * @param string $dir
     * @return array
     */
    public function drpQrcodeImageList($dir = 'data/attached/qrcode/themes/')
    {
        $imgList = [];
        $themes = storage_public($dir);
        if (!is_dir($themes)) {
            Storage::disk('public')->makeDirectory($dir);
        }

        $path = Storage::disk('public')->files($dir);

        $path = array_values(array_diff($path, ['..', '.'])); // 过滤

        $ext = ['png', 'jpg', 'jpeg'];
        foreach ($path as $item) {
            $extension = pathinfo($item, PATHINFO_EXTENSION);
            $extensions = strtolower($extension); // 文件扩展名

            if (in_array($extensions, $ext)) {
                $imgList[] = $item; //把符合条件的文件存入数组
            }
        }

        return $imgList;
    }

    /**
     * 获取用户生成的名片二维码
     * @param string $dir
     * @return array
     */
    public function drpUserQrcodeList($dir = '')
    {
        $imgList = [];
        $themes = storage_public($dir);
        if (is_dir($themes)) {

            $path = Storage::disk('public')->files($dir);

            $path = array_values(array_diff($path, ['..', '.'])); // 过滤

            $ext = ['png', 'jpg', 'jpeg'];
            foreach ($path as $item) {

                $extension = pathinfo($item, PATHINFO_EXTENSION);
                $extensions = strtolower($extension); // 文件扩展名

                if (preg_match('/drp_[0-9]+(.*)(.jpg|.png)/i', $item) && in_array($extensions, $ext)) {
                    $imgList[] = $item; //把符合条件的文件存入数组
                }
            }
        }

        return $imgList;
    }

    /**
     * 分销订单列表
     * @param int $ru_id
     * @param string $aff_day
     * @param array $offset
     * @param array $filter
     * @return array
     */
    public function drpOrderList($ru_id = 0, $aff_day = '', $offset = [], $filter = [])
    {
        $model = OrderInfo::where('main_count', 0)
            ->where('user_id', '>', 0)
            ->where('pay_status', '=', PS_PAYED);


        if (!empty($filter)) {
            // 按付款时间筛选
            if (!empty($filter['starttime']) && !empty($filter['endtime'])) {
                $model = $model->whereBetween('pay_time', [$filter['starttime'], $filter['endtime']]);
            }
        }

        $status = $filter['status'] ?? null;
        $able = $filter['able'] ?? null;
        if (isset($status)) {
            $model = $model->where(function ($query) use ($status) {
                $query->where('drp_is_separate', $status);
            });
            if (isset($able) && $able == 1) {
                $model = $model->where(function ($query) use ($aff_day) {
                    $query->where('pay_time', '<=', $aff_day);
                });
            }
            if (isset($able) && $able == 2) {
                $model = $model->where(function ($query) use ($aff_day) {
                    $query->where('pay_time', '>', $aff_day);
                });
            }
        }

        // 订单有有分成记录时才显示
        $log_type = $filter['log_type'] ?? 0;
        $model = $model->whereHas('getDrpLog', function ($query) use ($log_type) {
            $query->where('order_id', '>', 0)->where('log_type', $log_type);
        });

        if ($log_type == 2) {
            // 购买指定商品时，才显示
            $model = $model->whereHas('getOrderGoods', function ($query) {
                $query->select('membership_card_id')->where('membership_card_id', '>', 0);
            });
        } elseif ($log_type == 0) {
            // 订单有分销商品时，才显示
            $model = $model->whereHas('getOrderGoods', function ($query) {
                $query->select('drp_money')->where('drp_money', '>', 0);
            });
        }

        // 关联分销记录
        $model = $model->with([
            'getDrpLogs' => function ($query) {
                $query->select('log_id', 'order_id', 'user_id as suid', 'user_name as auser', 'money', 'point', 'separate_type');
            },
            'getUsers' => function ($query) {
                $query->select('user_id', 'user_name', 'nick_name');
            }
        ]);

        if ($ru_id > 0) {
            // 只显示对应商家分销订单
            $model = $model->where('ru_id', $ru_id);
        }

        if (!empty($filter)) {
            // 按订单号搜索
            $order_sn = $filter['order_sn'] ?? '';
            $model = $model->where(function ($query) use ($order_sn) {
                $query->where('order_sn', 'like', '%' . $order_sn . '%');
            });
        }

        $total = $model->count();

        if (!empty($offset)) {
            $model = $model->offset($offset['start'])->limit($offset['limit']);
        }

        $list = $model->orderBy('order_id', 'DESC')
            ->get();
        $list = $list ? $list->toArray() : [];

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 分销订单列表 分页
     * @param int $ru_id
     * @param string $aff_day
     * @param array $offset
     * @param array $filter
     * @return array
     */
    public function drpOrderListAll($ru_id = 0, $aff_day = '', $offset = [], $filter = [])
    {
        $result = $this->drpOrderList($ru_id, $aff_day, $offset, $filter);

        $list = $result['list'] ?? [];
        $total = $result['total'] ?? 0;

        $order_list = [];
        if (!empty($list)) {
            foreach ($list as $k => $rt) {
                $rt['shop_name'] = $this->merchantCommonService->getShopName($rt['ru_id'], 1);//商家名称

                // 订单已收货
                if ($aff_day >= $rt['pay_time'] && $rt['shipping_status'] == SS_RECEIVED) {
                    $rt['separate_able'] = 1;
                }

                $rt['add_time_format'] = $this->timeRepository->getLocalDate(config('shop.time_format'), $rt['add_time']);
                $rt['pay_time_format'] = $this->timeRepository->getLocalDate(config('shop.time_format'), $rt['pay_time']);

                if (isset($rt['get_users']) && !empty($rt['get_users'])) {
                    $rt['nick_name'] = $rt['get_users']['nick_name'] ?? '';
                    $rt['user_name'] = empty($rt['nick_name']) ? $rt['get_users']['user_name'] : $rt['nick_name'];

                    if (isset($this->config['show_mobile']) && $this->config['show_mobile'] == 0) {
                        $rt['user_name'] = $this->dscRepository->stringToStar($rt['user_name']);
                    }
                }

                if (!empty($rt['get_drp_logs'])) {
                    //在drp_log有记录
                    $affiliate_logs = [];
                    foreach ($rt['get_drp_logs'] as $key => $val) {

                        if (isset($this->config['show_mobile']) && $this->config['show_mobile'] == 0) {
                            $val['auser'] = $this->dscRepository->stringToStar($val['auser']);
                        }

                        //已被撤销
                        if ($val['separate_type'] == -1) {
                            $logs = sprintf(lang('admin/drp.drp_separate_info'), $val['suid'], $val['auser'], $val['money']);
                            $rt['drp_is_separate'] = 3;  // 撤销
                        } else {
                            $logs = sprintf(lang('admin/drp.drp_separate_info'), $val['suid'], $val['auser'], $val['money']);
                        }
                        $affiliate_logs[$key]['info'] = $logs;
                    }
                    unset($rt['get_drp_logs']);
                    $rt['info'] = $affiliate_logs;
                }
                $order_list[] = $rt;
            }
        }

        return ['list' => $order_list, 'total' => $total];
    }

    /**
     * 分销订单列表 excel导出
     * @param int $ru_id
     * @param string $aff_day
     * @param array $offset
     * @param array $filter
     * @return array
     */
    public function drpOrderListExcel($ru_id = 0, $aff_day = '', $offset = [], $filter = [])
    {
        $result = $this->drpOrderList($ru_id, $aff_day, $offset, $filter);

        $list = $result['list'] ?? [];
        $total = $result['total'] ?? 0;

        $order_list = [];
        if (!empty($list)) {
            $os = lang('admin/drp.order_stats');
            $ss = lang('admin/drp.ss');
            $ps = lang('admin/drp.ps');
            $sch_stats = lang('admin/drp.sch_stats');
            foreach ($list as $k => $rt) {

                $res['order_id'] = $rt['order_id'];
                $res['order_sn'] = $rt['order_sn'];
                $res['shop_name'] = $this->merchantCommonService->getShopName($rt['ru_id'], 1);//商家名称
                $res['order_status'] = $os[$rt['order_status']] . ',' . $ps[$rt['pay_status']] . ',' . $ss[$rt['shipping_status']];
                $res['sch_status'] = $sch_stats[$rt['drp_is_separate']];

                $rt['add_time_format'] = $this->timeRepository->getLocalDate(config('shop.time_format'), $rt['add_time']);
                $rt['pay_time_format'] = $this->timeRepository->getLocalDate(config('shop.time_format'), $rt['pay_time']);

                if (isset($rt['get_users']) && !empty($rt['get_users'])) {
                    $rt['nick_name'] = $rt['get_users']['nick_name'] ?? '';
                    $rt['user_name'] = empty($rt['nick_name']) ? $rt['get_users']['user_name'] : $rt['nick_name'];
                }

                if (!empty($rt['get_drp_logs'])) {
                    //在drp_log有记录
                    $logs = '';
                    foreach ($rt['get_drp_logs'] as $key => $val) {
                        if ($key < 2) {
                            $logs .= sprintf(lang('admin/drp.drp_separate_info'), $val['suid'], $val['auser'], $val['money']) . "\r\n";
                        } else {
                            $logs .= sprintf(lang('admin/drp.drp_separate_info'), $val['suid'], $val['auser'], $val['money']);
                        }
                    }
                    $res['info'] = $logs;
                } else {
                    $res['info'] = '';
                }

                $order_list[] = $res;
            }
        }

        return ['list' => $order_list, 'total' => $total];
    }

    /**
     * 分销付费购买分成列表
     * @param int $ru_id
     * @param string $aff_day
     * @param array $offset
     * @param array $filter
     * @param int $for_excel 是否导出Excel
     * @return array
     */
    public function drpOrderListBuy($ru_id = 0, $aff_day = '', $offset = [], $filter = [], $for_excel = 0)
    {
        $model = DrpAccountLog::query()->where('receive_type', 'buy')
            ->where('user_id', '>', 0)
            ->where('membership_card_id', '>', 0)
            ->where('is_paid', 1);


        if (!empty($filter)) {
            // 按记录分成（付款时间）筛选
            if (!empty($filter['starttime']) && !empty($filter['endtime'])) {
                $model = $model->whereBetween('paid_time', [$filter['starttime'], $filter['endtime']]);
            }
        }

        $status = $filter['status'] ?? null;
        $able = $filter['able'] ?? null;
        if (isset($status)) {
            $model = $model->where(function ($query) use ($status) {
                $query->where('drp_is_separate', $status);
            });
            if (isset($able) && $able == 1) {
                $model = $model->where(function ($query) use ($aff_day) {
                    $query->where('paid_time', '<=', $aff_day);
                });
            }
            if (isset($able) && $able == 2) {
                $model = $model->where(function ($query) use ($aff_day) {
                    $query->where('paid_time', '>', $aff_day);
                });
            }
        }

        // 有分成记录时才显示
        $log_type = $filter['log_type'] ?? 1;
        $model = $model->whereHas('getDrpLog', function ($query) use ($log_type) {
            $query->where('order_id', 0)->where('membership_card_id', '>', 0)->where('log_type', $log_type);
        });

        // 按用户名或手机号搜索
        $keywords = $filter['user_name'] ?? '';
        if (!empty($keywords)) {
            $model = $model->whereHas('getDrpShop', function ($query) use ($keywords) {
                $query = $query->where('shop_name', 'like', '%' . $keywords . '%')->orWhere('mobile', 'like', '%' . $keywords . '%');
                $query->orWhereHas('getUsers', function ($query) use ($keywords) {
                    $query->where('user_name', 'like', '%' . $keywords . '%')->orWhere('mobile_phone', 'like', '%' . $keywords . '%');
                });
            });
        }

        // 关联记录
        $model = $model->with([
            'getDrpShop' => function ($query) {
                $query = $query->select('user_id', 'shop_name', 'mobile', 'shop_portrait');
                $query->with([
                    'getUsers' => function ($query) {
                        $query->select('user_id', 'user_name', 'nick_name', 'mobile_phone', 'user_picture');
                    }
                ]);
            },
            'getDrpLog' => function ($query) {
                $query->select('log_id as drp_log_id', 'drp_account_log_id');
            },
            'getDrpLogs' => function ($query) {
                $query->select('log_id as drp_log_id', 'drp_account_log_id', 'user_id as suid', 'user_name as auser', 'money', 'point', 'separate_type', 'membership_card_id', 'log_type');
            }
        ]);

        $total = $model->count();

        if (!empty($offset)) {
            $model = $model->offset($offset['start'])->limit($offset['limit']);
        }

        $list = $model->orderBy('add_time', 'DESC')
            ->orderBy('id', 'DESC')
            ->get();
        $list = $list ? $list->toArray() : [];

        $order_list = [];
        if (!empty($list)) {

            $sch_stats = lang('admin/drp.sch_stats');

            foreach ($list as $k => $rt) {

                $rt = collect($rt)->merge($rt['get_drp_shop'])->except('get_drp_shop')->all();

                if (isset($this->config['show_mobile']) && $this->config['show_mobile'] == 0 && isset($rt['shop_name'])) {
                    $rt['shop_name'] = $this->dscRepository->stringToStar($rt['shop_name']);
                }

                $rt = collect($rt)->merge($rt['get_drp_log'])->except('get_drp_log')->all();

                if (isset($rt['get_users']) && $rt['get_users']) {
                    $rt['nick_name'] = $rt['get_users']['nick_name'] ?? '';
                    $rt['user_name'] = empty($rt['nick_name']) ? $rt['get_users']['user_name'] : $rt['nick_name'];
                    $rt['shop_portrait'] = $rt['get_users']['user_picture'] ?? '';

                    if (isset($this->config['show_mobile']) && $this->config['show_mobile'] == 0) {
                        $rt['user_name'] = $this->dscRepository->stringToStar($rt['user_name']);
                    }
                }

                // 分销商头像
                if (isset($rt['shop_portrait']) && $rt['shop_portrait']) {
                    $rt['shop_portrait'] = $this->dscRepository->getImagePath($rt['shop_portrait']);
                }

                if ($aff_day >= $rt['paid_time']) {
                    $rt['separate_able'] = 1;
                }

                $rt['is_paid_format'] = empty($rt['is_paid']) ? lang('admin/common.no_paid') : lang('admin/common.paid');
                $rt['pay_time_format'] = $this->timeRepository->getLocalDate(config('shop.time_format'), $rt['paid_time']);
                // 分成处理状态
                $rt['sch_status'] = $sch_stats[$rt['drp_is_separate']];

                $rt['info'] = '';
                if (!empty($rt['get_drp_logs'])) {
                    //在drp_log有记录
                    if ($for_excel == 1) {
                        $log_string = '';
                        foreach ($rt['get_drp_logs'] as $key => $val) {

                            if (isset($this->config['show_mobile']) && $this->config['show_mobile'] == 0) {
                                $val['auser'] = $this->dscRepository->stringToStar($val['auser']);
                            }

                            if ($key < 2) {
                                $log_string .= sprintf(lang('admin/drp.drp_separate_info'), $val['suid'], $val['auser'], $val['money']) . "\r\n";
                            } else {
                                $log_string .= sprintf(lang('admin/drp.drp_separate_info'), $val['suid'], $val['auser'], $val['money']);
                            }
                        }
                        unset($rt['get_drp_logs']);
                        $rt['info'] = $log_string;
                    } else {
                        $affiliate_logs = [];
                        foreach ($rt['get_drp_logs'] as $key => $val) {

                            if (isset($this->config['show_mobile']) && $this->config['show_mobile'] == 0) {
                                $val['auser'] = $this->dscRepository->stringToStar($val['auser']);
                            }

                            //已被撤销
                            if ($val['separate_type'] == -1) {
                                $logs = sprintf(lang('admin/drp.drp_separate_info'), $val['suid'], $val['auser'], $val['money']);
                                $rt['drp_is_separate'] = 3;  // 撤销
                            } else {
                                $logs = sprintf(lang('admin/drp.drp_separate_info'), $val['suid'], $val['auser'], $val['money']);
                            }
                            $affiliate_logs[$key]['info'] = $logs;
                        }
                        unset($rt['get_drp_logs']);
                        $rt['info'] = $affiliate_logs;
                    }
                }
                $order_list[] = $rt;
            }
        }

        return ['list' => $order_list, 'total' => $total];
    }

    /**
     * 分销排行
     * @param array $offset
     * @param array $filter
     * @return array
     */
    public function drpAffiliateList($offset = [], $filter = [])
    {
        $model = DrpShop::query()->where('audit', 1)->where('status', 1);

        $act = $filter['where'] ?? '';

        $time = 0;
        if ($act) {
            if ($act == 1) {
                //一年
                //$model = $model->where('log.time', '>=', $this->timeRepository->getLocalStrtoTime('-1 year'));
                $time = $this->timeRepository->getLocalStrtoTime('-1 year');
            } elseif ($act == 2) {
                //半年
                //$model = $model->where('log.time', '>=', $this->timeRepository->getLocalStrtoTime('-6 month'));
                $time = $this->timeRepository->getLocalStrtoTime('-6 month');
            } elseif ($act == 3) {
                //一月
                //$model = $model->where('log.time', '>=', $this->timeRepository->getLocalStrtoTime('-1 month'));
                $time = $this->timeRepository->getLocalStrtoTime('-1 month');
            }
        }

        // 关联分销日志
        $model = $model->with([
            'getDrpLogList' => function ($query) use ($time) {
                $query->select('user_id', 'time')->where('is_separate', 1)->where('separate_type', '<>', -1)->where('time', '>=', $time)->selectRaw('SUM(money) as total_money');
            },
            'userMembershipCard' => function ($query) {
                $query->select('id', 'name');
            }
        ]);

        $total = $model->count();

        if (!empty($offset)) {
            $model = $model->offset($offset['start'])->limit($offset['limit']);
        }

        $list = $model->groupBy('user_id')
            ->orderBy('id', 'DESC')
            ->get();

        $list = $list ? $list->toArray() : [];

        foreach ($list as $key => $val) {

            $list[$key]['total_money'] = 0;
            if (isset($val['get_drp_log_list'])) {
                // 合并二维数组为一维数组 保留键值
                $list[$key]['get_drp_log_list'] = collect($val['get_drp_log_list'])->collapse()->all();
                $list[$key]['total_money'] = $list[$key]['get_drp_log_list']['total_money'] ?? 0;
            }

            // 分销商等级
//            $res = app(DrpUserCreditService::class)->drpRankInfo($val['user_id']);
//            $list[$key]['credit_name'] = $res['credit_name'] ?? '';
            $list[$key]['card_name'] = $val['user_membership_card']['name'] ?? '';
            $list[$key]['name'] = !empty($val['shop_name']) ? $val['shop_name'] : $val['shop_name'];
            $list[$key]['create_time'] = $this->timeRepository->getLocalDate(config('shop.time_format'), $val['create_time']);

            if (isset($this->config['show_mobile']) && $this->config['show_mobile'] == 0) {
                $list[$key]['shop_name'] = $this->dscRepository->stringToStar($val['shop_name']);
                $list[$key]['mobile'] = $this->dscRepository->stringToStar($val['mobile']);
                $list[$key]['real_name'] = $this->dscRepository->stringToStar($val['real_name']);
                $list[$key]['name'] = $this->dscRepository->stringToStar($list[$key]['name']);
            }
        }

        // 数组排序--根据键 money 的值的数值重新排序
        $list = $this->baseRepository->getSortBy($list, 'total_money', 'DESC');

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 订单分成
     * @param int $order_id
     * @return array|bool
     */
    public function drpLogList($order_id = 0)
    {
        if (empty($order_id)) {
            return [];
        }

        $now = $this->timeRepository->getGmTime();

        // 取drp_log日志表 分成信息
        $model = DrpLog::query()->where('is_separate', 0)->where('order_id', $order_id);

        // 已支付、已收货订单
        $model = $model->whereHas('getOrder', function ($query) {
            $query->where('main_count', 0)->where('pay_status', PS_PAYED)->where('shipping_status', SS_RECEIVED)->where('drp_is_separate', 0);
        });

        $model = $model->whereHas('getUser');

        $model = $model->with([
            'getOrder' => function ($query) {
                $query->select('order_id', 'order_sn', 'money_paid', 'surplus')->where('drp_is_separate', 0);
            },
            'getUser' => function ($query) {
                $query->select('user_id', 'user_name');
            }
        ]);

        $result = $model->get();

        $result = $result ? $result->toArray() : [];

        if (!empty($result)) {
            foreach ($result as $key => $row) {
                if ($row['is_separate'] == 0) {

                    $row = collect($row)->merge($row['get_order'])->except('get_order')->all();
                    $row = collect($row)->merge($row['get_user'])->except('get_user')->all();

                    if (empty($row['user_id']) || empty($row['user_name'])) {
                        break;
                    } else {
                        $change_desc = sprintf(lang('admin/drp.drp_separate_info'), $row['user_name'], $row['order_sn'], $row['money'], $row['point']);

                        app(DistributeService::class)->drp_log_account_change($row['user_id'], $row['money'], 0, 0, $row['point'], $change_desc, ACT_SEPARATE);

                        //获得佣金，发送模版消息 start
                        if (file_exists(MOBILE_WECHAT)) {
                            // 订单应付金额
                            $row['amount'] = $row['surplus'] + $row['money_paid'];
                            $pushData = [
                                'first' => ['value' => lang('wechat.order_commission_first')], // 标题
                                'keyword1' => ['value' => $row['money']], // 佣金
                                'keyword2' => ['value' => $row['amount']], // 订单交易金额
                                'keyword3' => ['value' => $this->timeRepository->getLocalDate('Y-m-d H:i:s', $now)], //结算时间
                                'remark' => ['value' => lang('wechat.order_commission_remark'), 'color' => '#173177']
                            ];
                            $url = dsc_url('/#/drp/orderDetail', ['order_id' => $order_id]);

                            app(WechatService::class)->push_template('OPENTM409909643', $pushData, $url, $row['user_id']);
                        }
                        //获得佣金，发送模版消息 end
                    }
                    // 更新订单已分成状态
                    OrderInfo::where(['order_id' => $order_id])->update(['drp_is_separate' => 1]);

                    // 更新佣金分成记录
                    DrpLog::where(['order_id' => $order_id])->update(['is_separate' => 1, 'time' => $now]);
                }
            }
        }

        return false;
    }

    /**
     * 取消订单分成
     * @param int $order_id
     * @return bool
     */
    public function cancleDrpOrder($order_id = 0)
    {
        if (empty($order_id)) {
            return false;
        }

        $model = OrderInfo::where('order_id', $order_id)->first();
        if ($model) {
            if (empty($model->drp_is_separate)) {
                $model->drp_is_separate = 2;
                $model->save();

                return true;
            }
        }

        return false;
    }

    /**
     * 撤销某次分成  已丢弃
     * @param int $order_id
     * @return bool
     */
    public function rollbackDrpOrder($order_id = 0)
    {
        if (empty($order_id)) {
            return false;
        }

        $list = DrpLog::where('order_id', $order_id)->where('is_separate', 1)->get();
        $list = $list ? $list->toArray() : [];

        if ($list) {
            $flag = -1;
            foreach ($list as $key => $val) {
                $model = DrpLog::where('log_id', $val['log_id']);
                $stat = $model->first();
                $stat = $stat ? $stat->toArray() : [];
                if (!empty($stat)) {
                    app(DistributeService::class)->drp_log_account_change($stat['user_id'], -$stat['money'], 0, 0, -$stat['point'], lang('admin/drp.loginfo_cancel'), ACT_SEPARATE);

                    DrpLog::where(['order_id' => $stat['order_id'], 'user_id' => $stat['user_id']])->update(['separate_type' => $flag]);
                }
            }

            return true;
        }
    }


    /**
     * 付费购买订单分成
     * @param int $log_id
     * @return array|bool
     */
    public function drpLogListBuy($log_id = 0)
    {
        if (empty($log_id)) {
            return [];
        }

        $now = $this->timeRepository->getGmTime();

        // 取drp_log日志表 分成信息
        $model = DrpLog::query()->where('is_separate', 0)->where('log_id', $log_id);

        // 已支付，未分成订单
        $model = $model->whereHas('getDrpAccountLog', function ($query) {
            $query->where('is_paid', 1)->where('drp_is_separate', 0);
        });

        $model = $model->whereHas('getUser');

        $model = $model->with([
            'getDrpAccountLog' => function ($query) {
                $query->select('id', 'drp_account_log_id', 'user_id', 'amount')->where('drp_is_separate', 0);
            },
            'getUser' => function ($query) {
                $query->select('user_id', 'user_name');
            }
        ]);

        $result = $model->get();

        $result = $result ? $result->toArray() : [];

        if (!empty($result)) {
            foreach ($result as $key => $row) {
                $row = collect($row)->merge($row['get_drp_account_log'])->except('get_drp_account_log')->all();
                $row = collect($row)->merge($row['get_user'])->except('get_user')->all();

                if (empty($row['user_id']) || empty($row['user_name'])) {
                    break;
                } else {
                    $change_desc = sprintf(lang('admin/drp.drp_separate_info'), $row['user_name'], $row['id'], $row['money'], $row['point']);
                    app(DistributeService::class)->drp_log_account_change($row['user_id'], $row['money'], 0, 0, $row['point'], $change_desc, ACT_SEPARATE);

                    //获得佣金，发送模版消息 start
                    if (file_exists(MOBILE_WECHAT)) {
                        $pushData = [
                            'first' => ['value' => lang('wechat.order_commission_first')], // 标题
                            'keyword1' => ['value' => $row['money']], // 佣金
                            'keyword2' => ['value' => $row['amount']], // 订单交易金额
                            'keyword3' => ['value' => $this->timeRepository->getLocalDate('Y-m-d H:i:s', $now)], //结算时间
                            'remark' => ['value' => lang('wechat.order_commission_remark'), 'color' => '#173177']
                        ];
                        $url = dsc_url('/#/drp/drpinfo');

                        app(WechatService::class)->push_template('OPENTM409909643', $pushData, $url, $row['user_id']);
                    }
                    //获得佣金，发送模版消息 end
                }
                // 更新订单已分成状态
                DrpAccountLog::where(['id' => $row['drp_account_log_id']])->update(['drp_is_separate' => 1]);

                // 更新佣金分成记录
                DrpLog::where(['log_id' => $log_id])->update(['is_separate' => 1, 'time' => $now]);
            }
        }

        return false;
    }

    /**
     * 取消付费订单分成
     * @param int $account_log_id
     * @return bool
     */
    public function cancleDrpOrderBuy($account_log_id = 0)
    {
        if (empty($account_log_id)) {
            return false;
        }

        $model = DrpAccountLog::where('id', $account_log_id)->first();
        if ($model) {
            if (empty($model->drp_is_separate)) {
                $model->drp_is_separate = 2;
                $model->save();

                return true;
            }
        }

        return false;
    }

    /**
     * 分销统计总额
     * @param int $ru_id
     * @return array
     */
    public function drpStatistics($ru_id = 0)
    {
        // 统计分销商：分销商总量 + 分销商加入趋势图
        $drp_shop_count = DrpShop::where('audit', 1)->count();


        // 统计分销订单：分销订单总额 + 分销订单趋势图
        $model = OrderInfo::where('main_count', 0)
            ->where('user_id', '>', 0)
            ->where('pay_status', '=', PS_PAYED);

        // 关联用户
        $model = $model->where(function ($query) {
            $query->whereHas('getUsers', function ($query) {
                $query->where('drp_parent_id', '>', 0);
            });

            $query->where('drp_is_separate', 0)
                ->orWhere('drp_is_separate', '>', 0);
        });

        if ($ru_id > 0) {
            // 只显示对应商家分销订单
            $model = $model->where('ru_id', $ru_id);
        }

        // 订单有分销商品时，才显示
        $model = $model->whereHas('getOrderGoods', function ($query) {
            $query->select('drp_money')->where('drp_money', '>', 0);
        });

        $drp_order_count = $model->count();


        /**
         * 统计分销佣金：分销佣金总额 + 分销佣金趋势图
         */
        $model = OrderInfo::from('order_info as o')
            ->leftjoin('users as u', 'u.user_id', '=', 'o.user_id')
            ->leftjoin('drp_log as log', 'log.order_id', '=', 'o.order_id')
            ->where('o.user_id', '>', 0)
            ->where('o.pay_status', '=', PS_PAYED);

        $model = $model->where(function ($query) {
            $query->where('u.drp_parent_id', '>', 0)
                ->where('log.is_separate', 1)
                ->where('o.drp_is_separate', 0)
                ->orWhere('o.drp_is_separate', '>', 0);
        });
        $drp_sales_count = $model->sum('log.money');


        return [
            'drp_shop_count' => $drp_shop_count,
            'drp_order_count' => $drp_order_count,
            'drp_sales_count' => $drp_sales_count,
        ];
    }

    /**
     * 格式化 趋势图 数据
     * @param array $legend_data
     * @param array $xAxis_data
     * @param array $yAxis_data
     * @param array $series_data
     * @return mixed
     */
    public function transEcharts($legend_data = [], $xAxis_data = [], $yAxis_data = [], $series_data = [])
    {
        //图表公共数据 start
        $toolbox = [
            'show' => true,
            'orient' => 'vertical',
            'x' => 'right',
            'y' => '60',
            'feature' => [
                'magicType' => [
                    'show' => true,
                    'type' => ['line', 'bar']
                ],
                'saveAsImage' => ['show' => true]
            ]
        ];
        $tooltip = [
            'trigger' => 'axis',
            'axisPointer' => ['lineStyle' => ['color' => '#6cbd40']]
        ];
        $legend = [
            'data' => $legend_data['data'] ?? []
        ];
        $xAxis = [
            'type' => 'category',
            'boundaryGap' => false,
            'axisLine' => [
                'lineStyle' => ['color' => '#ccc', 'width' => 0]
            ],
            'data' => $xAxis_data['data'] ?? []
        ];
        $yAxis = [
            'type' => 'value',
            'axisLine' => [
                'lineStyle' => [
                    'color' => '#ccc',
                    'width' => 0
                ]
            ],
            'axisLabel' => ['formatter' => ''],
            'formatter' => $yAxis_data['formatter'] ?? '',
        ];
        $series = [
            [
                'name' => $series_data['name'] ?? '',
                'type' => 'line',
                'itemStyle' => [
                    'normal' => [
                        'color' => '#6cbd40',
                        'lineStyle' => ['color' => '#6cbd40']
                    ]
                ],
                'data' => $series_data['data'] ?? [],
                'markPoint' => [
                    'itemStyle' => [
                        'normal' => ['color' => '#6cbd40']
                    ],
                    'data' => [
                        ['type' => 'max', 'name' => lang('admin/drp.max_value')],
                        ['type' => 'min', 'name' => lang('admin/drp.min_value')]
                    ]
                ]
            ],
            [
                'type' => 'force',
                'name' => '',
                'draggable' => false,
                'nodes' => [
                    'draggable' => false
                ]
            ]
        ];

        // 组合数据

        $echarts['tooltip'] = $tooltip;
        $echarts['legend'] = $legend;
        $echarts['toolbox'] = $toolbox;
        $echarts['calculable'] = true;
        $echarts['xAxis'] = $xAxis;
        $echarts['yAxis'] = $yAxis;

        $echarts['series'] = $series;

        return $echarts;
    }

    /**
     * 获得分销商数据
     * @param string $date_start
     * @param string $date_end
     * @param string $time_diff
     * @return array
     */
    public function shop_series_data($date_start = '', $date_end = '', $time_diff = '')
    {
        $model = DrpShop::selectRaw("DATE_FORMAT(FROM_UNIXTIME(create_time + " . $time_diff . "),'%y-%m-%d') AS
             day , count(user_id) as count")
            ->where('audit', 1)
            ->whereBetween('create_time', [$date_start, $date_end]);

        $model = $model->groupBy('day')
            ->orderBy('day', 'ASC')
            ->get();
        $list = $model ? $model->toArray() : [];

        $shop_series_data = [];
        if (!empty($list)) {
            foreach ($list as $row) {
                //$row['day'] = isset($row['create_time']) ? $this->timeRepository->getLocalDate('y-m-d', $row['create_time'] + $time_diff) : '';
                $shop_series_data[$row['day']] = isset($row['count']) ? intval($row['count']) : 0;
            }
        }

        return $shop_series_data;
    }

    /**
     * 获取分销订单数据
     * @param int $ru_id
     * @param string $date_start
     * @param string $date_end
     * @param string $time_diff
     * @return array
     */
    public function orders_series_data($ru_id = 0, $date_start = '', $date_end = '', $time_diff = '')
    {
        $model = OrderInfo::where('main_count', 0)
            ->where('user_id', '>', 0)
            ->where('pay_status', '=', PS_PAYED)
            ->whereBetween('add_time', [$date_start, $date_end]);

        // 关联用户
        $model = $model->where(function ($query) {
            $query->whereHas('getUsers', function ($query) {
                $query->select('drp_parent_id as up')->where('drp_parent_id', '>', 0);
            });

            $query->where('drp_is_separate', 0)
                ->orWhere('drp_is_separate', '>', 0);
        });

        if ($ru_id > 0) {
            // 只显示对应商家分销订单
            $model = $model->where('ru_id', $ru_id);
        }

        // 订单有分销商品时，才显示
        $model = $model->whereHas('getOrderGoods', function ($query) {
            $query->select('drp_money')->where('drp_money', '>', 0);
        });

        $model = $model->selectRaw("DATE_FORMAT(FROM_UNIXTIME(add_time + " . $time_diff . "),'%y-%m-%d') AS
             day , count(order_id) as count")
            ->groupBy('day')
            ->orderBy('day', 'ASC')
            ->get();
        $list = $model ? $model->toArray() : [];

        $orders_series_data = [];
        if (!empty($list)) {
            foreach ($list as $row) {
                //$row['day'] = isset($row['day']) ? $this->timeRepository->getLocalDate('y-m-d', $row['day'] + $time_diff) : '';
                $orders_series_data[$row['day']] = isset($row['count']) ? intval($row['count']) : 0;
            }
        }

        return $orders_series_data;
    }


    /**
     * 获取分销佣金数据
     * @param string $date_start
     * @param string $date_end
     * @param string $time_diff
     * @return array
     */
    public function sales_series_data($date_start = '', $date_end = '', $time_diff = '')
    {
        // 已分成记录
        $model = DrpLog::where('is_separate', 1)->whereBetween('time', [$date_start, $date_end]);

        // 关联用户
        $model = $model->where(function ($query) {
            $query->whereHas('getUser', function ($query) {
                $query->where('drp_parent_id', '>', 0);
            });
        });

        // 关联订单
        $model = $model->with([
            'getOrder' => function ($query) {
                $query = $query->where('main_count', 0);

                $query->where('user_id', '>', 0)
                    ->where('pay_status', PS_PAYED)
                    ->where(function ($query) {
                        $query->where('drp_is_separate', 0)
                            ->orWhere('drp_is_separate', '>', 0);
                    });
            }
        ]);

        $model = $model->selectRaw("DATE_FORMAT(FROM_UNIXTIME(time + " . $time_diff . "),'%y-%m-%d') AS
             day , sum(money) as money")
            ->groupBy('day')
            ->orderBy('day', 'ASC')
            ->get();

        $list = $model ? $model->toArray() : [];

        $sales_series_data = [];
        if (!empty($list)) {
            foreach ($list as $val) {
//                $val['day'] = isset($val['time']) ? $this->timeRepository->getLocalDate('y-m-d', $val['time'] + $time_diff) : '';
                $sales_series_data[$val['day']] = isset($val['money']) ? floatval($val['money']) : 0;
            }
        }

        return $sales_series_data;
    }

    /**
     * 查询会员信息
     * @param string $user_name
     * @return array
     */
    public function checkUser($user_name = '')
    {
        if (empty($user_name)) {
            return [];
        }

        $model = Users::query()->where('user_name', $user_name)->orWhere('mobile_phone', $user_name);

        $model = $model->with([
            'getDrpShop' => function ($query) {
                $query->select('user_id');
            }
        ]);

        $model = $model->select('user_id', 'user_name', 'nick_name', 'user_picture', 'mobile_phone', 'user_rank')->first();

        $res = $model ? $model->toArray() : [];

        if (!empty($res)) {
            if (isset($res['get_drp_shop']) && !empty($res['get_drp_shop'])) {
                return ['error' => 1, 'msg' => lang('admin/drp.add_shop_exist')];
            }

            $res['user_picture'] = $this->dscRepository->getImagePath($res['user_picture']);
            $user_rank_info = app(UserRankService::class)->getUserRankInfo($res['user_id']);
            $res['rank_name'] = $user_rank_info['rank_name'] ?? '';
        }

        return $res;
    }

}
