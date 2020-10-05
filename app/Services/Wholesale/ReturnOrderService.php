<?php

namespace App\Services\Wholesale;

use App\Models\Brand;
use App\Models\ReturnCause;
use App\Models\Wholesale;
use App\Models\WholesaleOrderGoods;
use App\Models\WholesaleOrderInfo;
use App\Models\WholesaleOrderReturn;
use App\Models\WholesaleOrderReturnExtend;
use App\Models\WholesaleReturnGoods;
use App\Models\WholesaleReturnImages;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Common\AreaService;
use App\Services\Flow\FlowUserService;
use App\Services\Goods\GoodsCommonService;

class ReturnOrderService
{
    protected $baseRepository;
    protected $config;
    protected $timeRepository;
    protected $orderManageService;
    protected $goodsService;
    protected $orderService;
    protected $dscRepository;
    protected $areaService;
    protected $goodsCommonService;
    protected $flowUserService;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        OrderManageService $orderManageService,
        GoodsService $goodsService,
        OrderService $orderService,
        DscRepository $dscRepository,
        AreaService $areaService,
        GoodsCommonService $goodsCommonService,
        FlowUserService $flowUserService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->orderManageService = $orderManageService;
        $this->goodsService = $goodsService;
        $this->orderService = $orderService;
        $this->dscRepository = $dscRepository;
        $this->areaService = $areaService;
        $this->goodsCommonService = $goodsCommonService;
        $this->config = $this->dscRepository->dscConfig();
        $this->flowUserService = $flowUserService;
    }

    /**供应链订单商品
     * @param int $order_id
     * @return array
     */
    public function getwholesalegoodsorder($order_id = 0)
    {
        load_helper(['wholesale', 'transaction', 'order', 'suppliers']);
        $data = [];
        /* 订单商品 */
        $goods_list = wholesale_order_goods($order_id);
        foreach ($goods_list as $key => $value) {
            if ($value['extension_code'] != 'package_buy') {
                $price[] = $value['subtotal'];
                $goods_list[$key]['market_price'] = $this->dscRepository->getPriceFormat($value['market_price'], false);
                $goods_list[$key]['goods_price'] = $this->dscRepository->getPriceFormat($value['goods_price'], false);
                $goods_list[$key]['subtotal'] = $this->dscRepository->getPriceFormat($value['subtotal'], false);
                $goods_list[$key]['is_refound'] = $this->getIsRefound($value['rec_id']);   //判断是否退换货过
                $goods_list[$key]['goods_attr'] = str_replace(' ', '&nbsp;&nbsp;&nbsp;&nbsp;', $value['goods_attr']);
                $goods_list[$key]['goods_cause'] = $this->goodsCommonService->getGoodsCause($value['goods_cause']);
            } else {
                unset($goods_list[$key]);
                $data['package_buy'] = true;
            }
        }
        $data['formated_goods_amount'] = $this->dscRepository->getPriceFormat(array_sum($price), false);
        $data['order_id'] = $order_id;
        $data['goods_list'] = $goods_list;

        return $data;
    }

    /**供应链申请退换货详情
     * @param int $rec_id
     * @param int $order_id
     * @param int $user_id
     * @return array
     */
    public function applyreturn($rec_id = 0, $order_id = 0, $user_id = 0)
    {
        load_helper(['transaction', 'order', 'suppliers']);
        $data = [];
        $data['order'] = $this->orderManageService->wholesaleOrderInfo($order_id);

        /* 退货权限 by wu */
        $return_allowable = WholesaleOrderInfo::select('order_id')->where('order_id', $order_id)->where('shipping_status', '>', 0)->value('order_id');
        $data['return_allowable'] = $return_allowable ? $return_allowable : 0;

        /* 订单商品 */
        $data['goods_info'] = $this->orderService->wholesaleRecGoods($rec_id);

        $data['goods_return'] = $data['goods_info'];
        $data['consignee'] = $this->flowUserService->getConsignee($user_id);
        $data['show_goods_thumb'] = $this->config['show_goods_in_cart'];
        $data['show_attr_in_cart'] = $this->config['show_attr_in_cart'];
        $data['order_id'] = $order_id;
        $data['order_sn'] = $data['order']['order_sn'];

        $data['country_list'] = $this->areaService->getRegionsLog();
        $data['province_list'] = $this->areaService->getRegionsLog(1, $data['consignee']['country']);
        $data['city_list'] = $this->areaService->getRegionsLog(2, $data['consignee']['province']);
        $data['district_list'] = $this->areaService->getRegionsLog(2, $data['consignee']['province']);
        $data['street_list'] = $this->areaService->getRegionsLog(4, $data['consignee']['district']);

        /* 退换货标志列表 */
        $goods_cause = Wholesale::where('goods_id', $data['goods_info']['goods_id'])->value('goods_cause');
        $goods_cause = $goods_cause ? $goods_cause : '';

        $data['goods_cause'] = $this->goodsCommonService->getGoodsCause($goods_cause);

        //图片列表
        $img_list = WholesaleReturnImages::where('user_id', $user_id)
            ->where('rec_id', $rec_id)
            ->orderBy('id', 'desc');
        $data['img_list'] = $this->baseRepository->getToArrayGet($img_list);
        $data['return_pictures'] = $this->config['return_pictures'];

        $data['parent_cause'] = $this->getReturnCause(0, 1);

        return $data;
    }

    /**提交退换货
     * @param $user_id
     * @param $rec_id
     * @param $last_option
     * @param string $return_remark
     * @param string $return_brief
     * @param int $chargeoff_status
     * @param array $info
     * @return array
     */
    public function submitReturn($user_id, $info = [])
    {
        load_helper(['transaction', 'order']);

        $result = [];

        //判断是否重复提交申请退换货
        $rec_id = empty($info['rec_id']) ? 0 : intval($info['rec_id']);
        $last_option = !isset($info['last_option']) ? $info['parent_id'] : $info['last_option'];
        $return_remark = !isset($info['return_remark']) ? '' : addslashes(trim($info['return_remark']));
        $return_brief = !isset($info['return_brief']) ? '' : addslashes(trim($info['return_brief']));
        $chargeoff_status = !isset($info['chargeoff_status']) && empty($info['chargeoff_status']) ? 0 : intval($info['chargeoff_status']);
        $return_num = empty($info['return_number']) ? 0 : intval($info['return_number']); //换货数量

        if ($rec_id > 0) {
            $num = WholesaleOrderReturn::where('rec_id', $rec_id)->count();
            if ($num > 0) {
                $result['error'] = 1;
                $result['msg'] = lang('user.Repeated_submission');
                return $result;
            }
            $order_return_num = WholesaleOrderGoods::where('rec_id', $rec_id)->value('goods_number');
            if ($return_num > $order_return_num) {
                $result['error'] = 1;
                $result['msg'] = lang('user.more_than_order_goods_num');
                return $result;
            }
        } else {
            $result['error'] = 1;
            $result['msg'] = lang('user.Return_abnormal');
            return $result;
        }

        $order_goods = WholesaleOrderGoods::where('rec_id', $rec_id)
            ->with([
                'getWholesale',
                'getWholesaleOrderInfo' => function ($query) {
                    $query->with([
                        'getRegionProvince' => function ($query) {
                            $query->select('region_id', 'region_name');
                        },
                        'getRegionCity' => function ($query) {
                            $query->select('region_id', 'region_name');
                        },
                        'getRegionDistrict' => function ($query) {
                            $query->select('region_id', 'region_name');
                        },
                        'getRegionStreet' => function ($query) {
                            $query->select('region_id', 'region_name');
                        }
                    ]);
                }
            ]);

        $order_goods = $this->baseRepository->getToArrayFirst($order_goods);

        if ($order_goods) {
            /* 取得区域名 */
            $province = $order_goods['get_wholesale_order_info']['get_region_province']['region_name'] ?? '';
            $city = $order_goods['get_wholesale_order_info']['get_region_city']['region_name'] ?? '';
            $district = $order_goods['get_wholesale_order_info']['get_region_district']['region_name'] ?? '';
            $street = $order_goods['get_wholesale_order_info']['get_region_street']['region_name'] ?? '';
            $order_goods['region'] = $province . ' ' . $city . ' ' . $district . ' ' . $street;
        }
        $wholesale = $order_goods['get_wholesale'] ?? [];

        $order_goods['goods_sn'] = $wholesale['goods_sn'] ?? '';
        $order_goods['brand_id'] = $wholesale['brand_id'] ?? 0;

        $maintain_number = empty($info['maintain_number']) ? 0 : intval($info['maintain_number']); //换货数量
        $back_number = empty($info['attr_num']) ? 0 : intval($info['attr_num']); //退货数量
        $goods_number = empty($info['return_g_number']) ? 0 : intval($info['return_g_number']); //仅退款退换所有商品

        $return_type = intval($info['return_type']); //退换货类型
        $maintain = 0;
        $return_status = 0;

        if ($return_type == 1) {
            $back = 1;
            $exchange = 0;
            $return_number = $return_num;
        } elseif ($return_type == 2) {
            $back = 0;
            $exchange = 2;
            $return_number = $back_number;
        } elseif ($return_type == 3) {
            $back = 0;
            $exchange = 0;
            $return_number = $goods_number;
            $return_status = -1;
        } else {
            $back = 0;
            $exchange = 0;
            $return_number = $maintain_number;
        }

        $attr_val = isset($info['attr_val']) ? $info['attr_val'] : array(); //获取属性ID数组
        $return_attr_id = !empty($attr_val) ? implode(',', $attr_val) : '';
        $attr_val = get_wholesale_goods_attr_info_new($attr_val, 'pice');

        $order_return = array(
            'rec_id' => $rec_id,
            'goods_id' => $order_goods['goods_id'],
            'order_id' => $order_goods['order_id'],
            'order_sn' => $order_goods['goods_sn'],
            'chargeoff_status' => $chargeoff_status,
            'return_type' => $return_type, //唯一标识
            'maintain' => $maintain, //维修标识
            'back' => $back, //退货标识
            'exchange' => $exchange, //换货标识
            'user_id' => $user_id,
            'goods_attr' => $order_goods['goods_attr'] ?? '',   //换出商品属性
            'attr_val' => $attr_val,
            'return_brief' => $return_brief,
            'remark' => $return_remark,
            'credentials' => !isset($info['credentials']) ? 0 : intval($info['credentials']),
            'country' => empty($info['country']) ? 0 : intval($info['country']),
            'province' => empty($info['province']) ? 0 : intval($info['province']),
            'city' => empty($info['city']) ? 0 : intval($info['city']),
            'district' => empty($info['district']) ? 0 : intval($info['district']),
            'street' => empty($info['street']) ? 0 : intval($info['street']),
            'cause_id' => $last_option, //退换货原因
            'apply_time' => $this->timeRepository->getGmTime(),
            'actual_return' => '',
            'address' => empty($info['return_address']) ? '' : addslashes(trim($info['return_address'])),
            'zipcode' => empty($info['code']) ? '' : intval($info['code']),
            'addressee' => empty($info['addressee']) ? '' : addslashes(trim($info['addressee'])),
            'phone' => empty($info['mobile']) ? '' : addslashes(trim($info['mobile'])),
            'return_status' => $return_status
        );

        if (in_array($return_type, array(1, 3))) {
            $return_info = get_wholesale_return_refound($order_return['order_id'], $order_return['rec_id'], $return_number);
            $order_return['should_return'] = $return_info['return_price'];
            $order_return['return_shipping_fee'] = $return_info['return_shipping_fee'];
        } else {
            $order_return['should_return'] = 0;
            $order_return['return_shipping_fee'] = 0;
        }

        $order_return['return_sn'] = get_order_sn(); //获取新订单号

        $ret_id = 0;
        $return_count = WholesaleOrderReturn::where('return_sn', $order_return['return_sn'])->count();
        if ($return_count <= 0) {
            /* 获取表字段 */
            $other = $this->baseRepository->getArrayfilterTable($order_return, 'wholesale_order_return');

            $ret_id = WholesaleOrderReturn::insertGetId($other);
        }

        if ($ret_id) {

            /* 记录log */
            return_whole_action($ret_id, lang('user.Apply_refund'), '', $order_return['remark'], lang('common.buyer'));

            $brand_name = '';
            if (isset($order_goods['brand_id']) && $order_goods['brand_id']) {
                $brand_name = Brand::where('brand_id', $order_goods['brand_id'])->value('brand_name');
            }

            $return_goods['rec_id'] = $order_return['rec_id'];
            $return_goods['ret_id'] = $ret_id;
            $return_goods['goods_id'] = $order_goods['goods_id'];
            $return_goods['goods_name'] = $order_goods['goods_name'];
            $return_goods['brand_name'] = $brand_name;
            $return_goods['product_id'] = $order_goods['product_id'];
            $return_goods['goods_sn'] = $order_goods['goods_sn'];
            $return_goods['is_real'] = $order_goods['is_real'];
            $return_goods['goods_attr'] = $attr_val;  //换货的商品属性名称
            $return_goods['attr_id'] = $return_attr_id; //换货的商品属性ID值
            $return_goods['refound'] = $order_goods['goods_price'];

            //添加到退换货商品表中
            $return_goods['return_type'] = $return_type; //退换货
            $return_goods['return_number'] = $return_number; //退换货数量

            if ($return_type == 1) { //退货
                $return_goods['out_attr'] = '';
            } elseif ($return_type == 2) { //换货
                $return_goods['out_attr'] = $attr_val;
                $return_goods['return_attr_id'] = $return_attr_id;
            } else {
                $return_goods['out_attr'] = '';
            }

            /* 获取表字段 */
            $goodsOther = $this->baseRepository->getArrayfilterTable($return_goods, 'wholesale_return_goods');

            WholesaleReturnGoods::insertGetId($goodsOther);

            // 保存退换货图片
            if (isset($info['return_images']) && !empty($info['return_images'])) {
                $time = $this->timeRepository->getGmTime();
                foreach ($info['return_images'] as $k => $v) {
                    if (strtolower(substr($v, 0, 4)) == 'http') {
                        $v = str_replace(asset('/'), '', $v);
                    }
                    $img_file = str_replace('storage/', '', ltrim($v, '/'));
                    $data = [
                        'rec_id' => $rec_id,
                        'rg_id' => $order_goods['goods_id'],
                        'user_id' => $user_id,
                        'img_file' => $img_file,
                        'add_time' => $time
                    ];
                    WholesaleReturnImages::insert($data);
                }
            }

            //退货数量插入退货表扩展表  by kong
            $order_return_extend = array(
                'ret_id' => $ret_id,
                'return_number' => $return_number
            );

            WholesaleOrderReturnExtend::insert($order_return_extend);

            $address_detail = $order_goods['region'] . ' ' . $order_return['address'];
            $order_return['address_detail'] = $address_detail;
            $order_return['apply_time'] = $this->timeRepository->getLocalDate("Y-m-d H:i:s", $order_return['apply_time']);

            $result['error'] = 0;
            $result['msg'] = lang('user.Apply_Success_Prompt');
        } else {
            $result['error'] = 1;
            $result['msg'] = lang('user.Apply_abnormal');
        }
        return $result;
    }

    public function deleteReturn($user_id, $ret_id)
    {
        $result = [];
        if (!$ret_id) {
            $result['error'] = 1;
            $result['msg'] = '';
        }
        $delete = WholesaleOrderReturn::where('user_id', $user_id)
            ->where('ret_id', $ret_id)
            ->delete();

        if ($delete) {
            $result['error'] = 0;
            $result['msg'] = lang('common.delete_success');
        } else {
            $result['error'] = 1;
            $result['msg'] = lang('common.unknown_error');
        }
        return $result;
    }

    /**
     * 退换货原因
     * @param int $parent_id
     * @return array
     */
    protected function getReturnCause($parent_id = 0, $level = 0)
    {
        $res = ReturnCause::where('parent_id', $parent_id)
            ->where('is_show', 1)
            ->orderBy('sort_order')
            ->get();

        $res = $res ? $res->toArray() : [];

        $three_arr = [];
        foreach ($res as $k => $row) {
            $three_arr[$k]['cause_id'] = $row['cause_id'];
            $three_arr[$k]['cause_name'] = $row['cause_name'];
            $three_arr[$k]['parent_id'] = $row['parent_id'];
            $three_arr[$k]['haschild'] = 0;

            $three_arr[$k]['level'] = $level;
            //$three_arr[$k]['select'] = str_repeat('&nbsp;', $three_arr[$k]['level'] * 4);

            if (isset($row['cause_id']) && $level > 0) {
                $child_tree = $this->getReturnCause($row['cause_id'], $level + 1);
                if ($child_tree) {
                    $three_arr[$k]['child_tree'] = $child_tree;
                    $three_arr[$k]['haschild'] = 1;
                }
            }
        }

        return $three_arr;
    }

    /**供应链退换货订单列表
     * @param array $data
     * @return array
     */
    public function getReturnOrdersList($data = [])
    {
        $activation_number_type = (intval($this->config['activation_number_type']) > 0) ? intval($this->config['activation_number_type']) : 2;

        $user_id = isset($data['user_id']) && !empty($data['user_id']) ? intval($data['user_id']) : 0;
        $res = WholesaleOrderReturn::where('user_id', $user_id);

        $res = $res->with([
            'getWholesale'
        ])->with(['getWholesaleReturnGoods']);

        $res = $res->orderBy('ret_id', 'desc');
        $start = ($data['page'] - 1) * $data['size'];
        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($data['size'] > 0) {
            $res = $res->take($data['size']);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        $goods_list = [];
        if ($res) {
            foreach ($res as $key => $row) {
                $wholesale = $row['get_wholesale'];
                $wholesale_goods = $row['get_wholesale_return_goods'];

                $row['goods_name'] = $wholesale_goods['goods_name'];
                $row['return_number'] = $wholesale_goods['return_number'];
                $row['refound_money'] = $this->dscRepository->getPriceFormat($wholesale_goods['refound'], false);

                $row['goods_thumb'] = $wholesale['goods_thumb'];
                $row['goods_thumb'] = $row['goods_thumb'] ? $this->dscRepository->getImagePath($row['goods_thumb']) : '';

                $row['apply_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $row['apply_time']);
                $row['should_return'] = $this->dscRepository->getPriceFormat($row['should_return'], false);
                $row['edit_shipping'] = '<a href="user_order.php?act=return_detail&ret_id=' . $row['ret_id'] . "&order_id=" . $row['order_id'] . '" style="margin-left:5px;" >查看</a>';

                $row['order_status'] = '';
                if ($row['return_status'] == 0 && $row['refound_status'] == 0) {
                    //  提交退换货后的状态 由用户寄回
                    $row['order_status'] .= "<span>" . $GLOBALS['_LANG']['user_return'] . "</span>";
                    $row['handler'] = '<a href="user_order.php?act=cancel_return&ret_id=' . $row['ret_id'] . '" style="margin-left:5px;" onclick="if (!confirm(' . "'你确认取消该退换货申请吗？'" . ')) return false;"  >取消</a>';
                } elseif ($row['return_status'] == 1) {
                    //退换商品收到
                    $row['order_status'] .= "<span>" . $GLOBALS['_LANG']['get_goods'] . "</span>";
                } elseif ($row['return_status'] == 2) {
                    //换货商品寄出 （分单）
                    $row['order_status'] .= "<span>" . $GLOBALS['_LANG']['send_alone'] . "</span>";
                } elseif ($row['return_status'] == 3) {
                    //换货商品寄出
                    $row['order_status'] .= "<span>" . $GLOBALS['_LANG']['send'] . "</span>";
                } elseif ($row['return_status'] == 4) {
                    //完成
                    $row['order_status'] .= "<span>" . $GLOBALS['_LANG']['complete'] . "</span>";
                } elseif ($row['return_status'] == 6) {
                    //被拒
                    $row['order_status'] .= "<span>" . $GLOBALS['_LANG']['rf'][$row['return_status']] . "</span>";
                }

                //维修-退款-换货状态
                if ($row['return_type'] == 0) {
                    if ($row['return_status'] == 4) {
                        $row['reimburse_status'] = $GLOBALS['_LANG']['ff'][FF_MAINTENANCE];
                    } else {
                        $row['reimburse_status'] = $GLOBALS['_LANG']['ff'][FF_NOMAINTENANCE];
                    }
                } elseif ($row['return_type'] == 1) {
                    if ($row['refound_status'] == 1) {
                        $row['reimburse_status'] = $GLOBALS['_LANG']['ff'][FF_REFOUND];
                    } else {
                        $row['reimburse_status'] = $GLOBALS['_LANG']['ff'][FF_NOREFOUND];
                    }
                } elseif ($row['return_type'] == 2) {
                    if ($row['return_status'] == 4) {
                        $row['reimburse_status'] = $GLOBALS['_LANG']['ff'][FF_EXCHANGE];
                    } else {
                        $row['reimburse_status'] = $GLOBALS['_LANG']['ff'][FF_NOEXCHANGE];
                    }
                } elseif ($row['return_type'] == 3) {
                    if ($row['refound_status'] == 1) {
                        $row['reimburse_status'] = $GLOBALS['_LANG']['ff'][FF_REFOUND];
                    } else {
                        $row['reimburse_status'] = $GLOBALS['_LANG']['ff'][FF_NOREFOUND];
                    }
                }

                $row['activation_type'] = 0;

                //判断是否支持激活
                if ($row['return_status'] == 6) {
                    if ($row['activation_number'] < $activation_number_type) {
                        $row['activation_type'] = 1;
                    }
                }
                unset($row['get_wholesale']);
                unset($row['get_wholesale_return_goods']);
                $goods_list[] = $row;
            }
        }

        return $goods_list;
    }

    /**
     * 供应链商品是否有退换货记录 by Leah
     * @param int $rec_id
     * @return
     */
    protected function getIsRefound($rec_id)
    {
        $count = WholesaleOrderReturn::where('rec_id', $rec_id)->count();
        if ($count > 0) {
            $is_refound = 1;
        } else {
            $is_refound = 0;
        }

        return $is_refound;
    }

    /**供应链退换货商品详情
     * @param array $data
     * @return array
     */
    public function getReturnOrdersDetail($data = [])
    {
        $result = [];
        $order = $this->orderService->wholesaleReturnOrderInfo($data['ret_id']);

        if (empty($order)) {
            return $result;
        }

        unset($order['get_wholesale_return_goods']);
        unset($order['get_wholesale']);
        unset($order['get_wholesale_order_info']);
        //修正退货单状态
        if ($order['return_type'] == 0) {
            if ($order['return_status1'] == 4) {
                $order['refound_status1'] = FF_MAINTENANCE;
            } else {
                $order['refound_status1'] = FF_NOMAINTENANCE;
            }
        } elseif ($order['return_type'] == 1) {
            if ($order['refound_status1'] == 1) {
                $order['refound_status1'] = FF_REFOUND;
            } else {
                $order['refound_status1'] = FF_NOREFOUND;
            }
        } elseif ($order['return_type'] == 2) {
            if ($order['return_status1'] == 4) {
                $order['refound_status1'] = FF_EXCHANGE;
            } else {
                $order['refound_status1'] = FF_NOEXCHANGE;
            }
        }

        return $order;
    }

    /**激活退换货订单
     * @param array $data
     * @return array
     */
    public function activationReturnOrder($data = [])
    {
        $result = ['error' => 0, 'msg' => ''];

        $ret_id = $data['ret_id'];

        if (empty($ret_id)) {
            $result['error'] = 1;
            $result['msg'] = lang('common.unknown_error');
            return $result;
        }
        $activation_number_type = (intval($this->config['activation_number_type']) > 0) ? intval($this->config['activation_number_type']) : 2;

        $activation_number = WholesaleOrderReturn::where('ret_id', $ret_id)->value('activation_number');
        $activation_number = $activation_number ? $activation_number : 0;

        if ($activation_number_type > $activation_number) {
            WholesaleOrderReturn::where('ret_id', $ret_id)->increment('activation_number', 1, [
                'return_status' => 0
            ]);
            $result['msg'] = lang('user.had_activated');
        } else {
            $result['error'] = 1;
            $result['msg'] = sprintf(lang('user.activation_number_msg'), $activation_number_type);
        }

        return $result;
    }
}
