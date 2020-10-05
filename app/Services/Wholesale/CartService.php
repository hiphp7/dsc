<?php

namespace App\Services\Wholesale;

use App\Models\Region;
use App\Models\SellerShopinfo;
use App\Models\Suppliers;
use App\Models\Users;
use App\Models\Wholesale;
use App\Models\WholesaleCart;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Merchant\MerchantCommonService;
use App\Services\User\UserAddressService;

class CartService
{
    protected $baseRepository;
    protected $timeRepository;
    protected $dscRepository;
    protected $goodsService;
    protected $config;
    protected $merchantCommonService;
    protected $sessionRepository;
    protected $userAddressService;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        GoodsService $goodsService,
        MerchantCommonService $merchantCommonService,
        SessionRepository $sessionRepository,
        UserAddressService $userAddressService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->goodsService = $goodsService;
        $this->config = $this->dscRepository->dscConfig();
        $this->merchantCommonService = $merchantCommonService;
        $this->sessionRepository = $sessionRepository;
        $this->userAddressService = $userAddressService;
    }

    /**
     * 获取商品购物车ID
     *
     * @param int $goods_id
     * @param array $attr
     * @param int $user_id
     * @return int
     */
    public function getFlowAddToCartRecId($goods_id = 0, $attr = [], $user_id = 0)
    {
        $user_id = session()->has('user_id') && session('user_id') ? session('user_id') : $user_id;

        $rec = WholesaleCart::where('goods_id', $goods_id);

        if ($attr) {
            foreach ($attr as $key => $val) {
                $val = intval($val);
                $rec = $rec->whereRaw("FIND_IN_SET('$val', REPLACE(goods_attr_id, ',', ','))");
            }
        }

        if ($user_id) {
            $rec = $rec->where('user_id', $user_id);
        } else {
            $session_id = $this->sessionRepository->realCartMacIp();
            $rec = $rec->where('session_id', $session_id);
        }

        $rec_id = $rec->value('rec_id');
        $rec_id = $rec_id ? $rec_id : 0;

        return $rec_id;
    }

    /**
     * 选择购物车商品
     * @param array $rec_ids 购物车ID
     */
    public function checked($uid, $rec_ids = [])
    {
        $user_id = session()->has('user_id') && session('user_id') ? session('user_id') : $uid;

        $model = WholesaleCart::whereRaw('1');

        if ($user_id) {
            $rec = $model->where('user_id', $user_id);
        } else {
            $session_id = $this->sessionRepository->realCartMacIp();
            $rec = $model->where('session_id', $session_id);
        }

        $res = $rec->update(['is_checked' => 0]);

        if ($rec_ids) {
            if (!is_array($rec_ids)) {
                $rec_ids = $this->baseRepository->getExplode($rec_ids);
            }
            $res = $rec->whereIn('rec_id', $rec_ids)->update(['is_checked' => 1]);
        }

        return $res;
    }

    /**
     * 获取批发购物车商品列表
     *
     * @param int $goods_id
     * @param string $rec_ids
     * @param int $is_checked
     * @return array
     */
    public function wholesaleCartGoods($goods_id = 0, $rec_ids = '', $is_checked = 0)
    {
        $session_id = $this->sessionRepository->realCartMacIp();
        $user_id = session('user_id', 0);

        $res = WholesaleCart::whereRaw(1);

        if (!empty($user_id)) {
            $res = $res->where('user_id', $user_id);
        } else {
            $res = $res->where('session_id', $session_id);
        }

        if (!empty($goods_id)) {
            $res = $res->where('goods_id', $goods_id);
        }

        if (!empty($rec_ids)) {
            $rec_ids = $this->baseRepository->getExplode($rec_ids);
            $res = $res->whereIn('rec_id', $rec_ids);
        }

        // 选中的
        if (!empty($is_checked)) {
            $res = $res->where('is_checked', $is_checked);
        }

        $res = $this->baseRepository->getToArrayGet($res);
        $suppliers_id = $this->baseRepository->getKeyPluck($res, 'suppliers_id');
        $suppliers_id = $suppliers_id ? array_unique($suppliers_id) : [];

        $cart_goods = [];
        if ($suppliers_id) {
            foreach ($suppliers_id as $key => $val) {
                $data = [];

                $suppliers_info = Suppliers::where('suppliers_id', $val);
                $suppliers_info = $this->baseRepository->getToArrayFirst($suppliers_info);

                $data['suppliers_id'] = $val;
                $data['ru_id'] = $suppliers_info['user_id'] ?? 0;
                $data['shop_name'] = $suppliers_info['suppliers_name'] ?? '';


                /* 客服部分 start */
                /*  @author-bylu 判断当前商家是否允许"在线客服" start */
                $shop_information = $this->merchantCommonService->getShopName($data['ru_id']); //通过ru_id获取到店铺信息;
                $data['is_IM'] = $shop_information['is_IM'] ?? 0; //平台是否允许商家使用"在线客服";

                //判断当前商家是平台,还是入驻商家
                if ($data['ru_id'] == 0) {
                    //判断平台是否开启了IM在线客服
                    $kf_im_switch = SellerShopinfo::where('ru_id', 0)->value('kf_im_switch');
                    if ($kf_im_switch) {
                        $data['is_dsc'] = true;
                    } else {
                        $data['is_dsc'] = false;
                    }
                } else {
                    $data['is_dsc'] = false;
                }

                $basic_info = $shop_information;

                $chat = $this->dscRepository->chatQq($basic_info);
                $data['kf_type'] = $chat['kf_type'];
                $data['kf_ww'] = $chat['kf_ww'];
                $data['kf_qq'] = $chat['kf_qq'];

                //区分商品
                $goods_ids = WholesaleCart::where('suppliers_id', $val);

                if (!empty($user_id)) {
                    $goods_ids = $goods_ids->where('user_id', $user_id);
                } else {
                    $goods_ids = $goods_ids->where('session_id', $session_id);
                }

                if (!empty($goods_id)) {
                    $goods_ids = $goods_ids->where('goods_id', $goods_id);
                }

                if (!empty($rec_ids)) {
                    $rec_ids = $this->baseRepository->getExplode($rec_ids);
                    $goods_ids = $goods_ids->whereIn('rec_id', $rec_ids);
                }

                $goods_ids = $this->baseRepository->getToArrayGet($goods_ids);
                $goods_ids = $this->baseRepository->getKeyPluck($goods_ids, 'goods_id');
                $goods_ids = $goods_ids ? array_unique($goods_ids) : [];


                if ($goods_ids) {
                    foreach ($goods_ids as $a => $g) {

                        //先更新购物车数据
                        $this->calculateCartGoodsPrice($g, $rec_ids);

                        //查询购物车数据
                        $res = WholesaleCart::where('suppliers_id', $val)
                            ->where('goods_id', $g);

                        if (!empty($user_id)) {
                            $res = $res->where('user_id', $user_id);
                        } else {
                            $res = $res->where('session_id', $session_id);
                        }

                        // 选中的
                        if (!empty($is_checked)) {
                            $res = $res->where('is_checked', $is_checked);
                        }

                        $res = $res->orderBy('goods_attr_id');

                        $res = $this->baseRepository->getToArrayGet($res);

                        //商品属性数据
                        $total_number = 0;
                        $total_price = 0;
                        $goods_attr_text = '';
                        if ($res) {
                            foreach ($res as $k => $v) {
                                $res[$k]['goods_price_formatted'] = $this->dscRepository->getPriceFormat($v['goods_price']);
                                $res[$k]['total_price'] = $v['goods_price'] * $v['goods_number'];
                                $res[$k]['total_price_formatted'] = $this->dscRepository->getPriceFormat($res[$k]['total_price']);
                                $res[$k]['goods_attr'] = $this->goodsService->getGoodsAttrArray($v['goods_id'], $v['goods_attr_id']);
                                $goods_attr = $this->goodsService->getGoodsAttrInfo($v['goods_id'], $v['goods_attr_id']);
                                $goods_attr_text .= $goods_attr;
                                //统计数量和价格
                                $total_number += $v['goods_number'];
                                $total_price += $res[$k]['total_price'];
                            }
                        }

                        //补充商品数据
                        $goods_data = Wholesale::where('goods_id', $g);
                        $goods_data = $this->baseRepository->getToArrayFirst($goods_data);

                        if (isset($goods_data) && !empty($goods_data)) {
                            $goods_data['goods_thumb'] = isset($goods_data['goods_thumb']) && !empty($goods_data['goods_thumb']) ? $this->dscRepository->getImagePath($goods_data['goods_thumb']) : '';
                            $goods_data['total_number'] = $total_number;
                            $goods_data['total_price'] = $total_price;
                            $goods_data['goods_attr_text'] = $goods_attr_text;
                            $goods_data['cart_goods_price'] = $total_price / $total_number;
                            $goods_data['cart_goods_price_formatted'] = $this->dscRepository->getPriceFormat($goods_data['cart_goods_price']);
                            $goods_data['goods_price_formatted'] = isset($goods_data['goods_price']) && !empty($goods_data['goods_price']) ? $this->dscRepository->getPriceFormat($goods_data['goods_price']) : '';
                            $goods_data['total_price_formatted'] = $this->dscRepository->getPriceFormat($goods_data['total_price']);

                            if (empty($goods_data['price_model'])) {
                                if ($total_number >= $goods_data['moq']) {
                                    $goods_data['is_reached'] = 1;
                                }
                            } else {
                                $goods_data['volume_price'] = $this->goodsService->getWholesaleVolumePrice($g, $total_number);
                            }

                            $goods_data['list'] = $res;
                            $goods_data['count'] = count($res); //记录数量
                            $goods_data['url'] = $this->dscRepository->buildUri('wholesale_goods', array('aid' => $goods_data['goods_id']), $goods_data['goods_name']); //链接地址
                        }
                        $data['goods_list'][] = $goods_data;
                    }
                }

                $cart_goods[] = $data;
            }
        }

        return $cart_goods;
    }

    /**
     * 获取购物车商品数量和价格
     *
     * @param int $goods_id
     * @param string $rec_ids
     * @param string $is_checked
     * @return array
     */
    public function wholesaleCartInfo($goods_id = 0, $rec_ids = '', $is_checked = 0)
    {
        $user_id = session('user_id', 0);

        $res = WholesaleCart::whereRaw(1);

        if (!empty($user_id)) {
            $res = $res->where('user_id', $user_id);
        } else {
            $session_id = $this->sessionRepository->realCartMacIp();
            $res = $res->where('session_id', $session_id);
        }

        if (!empty($goods_id)) {
            $res = $res->where('goods_id', $goods_id);
        }

        if (!empty($rec_ids)) {
            $rec_ids = $this->baseRepository->getExplode($rec_ids);
            $res = $res->whereIn('rec_id', $rec_ids);
        }

        // 选中的
        if (!empty($is_checked)) {
            $res = $res->where('is_checked', $is_checked);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        $cart_info = array(
            'rec_count' => 0,
            'total_number' => 0,
            'total_price' => 0.00,
            'total_price_formatted' => ''
        );

        if ($res) {
            foreach ($res as $key => $val) {
                $cart_info['rec_count'] += 1;
                $cart_info['total_number'] += $val['goods_number'];
                $total_price = $val['goods_number'] * $val['goods_price'];
                $cart_info['total_price'] += $total_price;
            }
        }

        $cart_info['total_price_formatted'] = $this->dscRepository->getPriceFormat($cart_info['total_price']);
        /*判断余额是否足够*/
        $cart_info['use_surplus'] = 0;

        if ($this->config['use_surplus'] == 1) {
            $use_surplus = Users::where('user_id', $user_id)->value('user_money');

            if ($use_surplus >= $cart_info['total_price']) {
                $cart_info['use_surplus'] = 1;
            }
        }

        return $cart_info;
    }

    /**
     * 刷新购物车商品价格
     *
     * @param int $goods_id
     * @param string $rec_ids
     * @param int $user_id
     */
    public function calculateCartGoodsPrice($goods_id = 0, $rec_ids = '', $user_id = 0)
    {
        if ($goods_id) {
            $user_id = session()->has('user_id') && session('user_id') ? session('user_id') : $user_id;
            $res = WholesaleCart::whereRaw(1);

            if (!empty($user_id)) {
                $res = $res->where('user_id', $user_id);
            } else {
                $session_id = $this->sessionRepository->realCartMacIp();
                $res = $res->where('session_id', $session_id);
            }

            if (!empty($goods_id)) {
                $res = $res->where('goods_id', $goods_id);
            }

            if (!empty($rec_ids)) {
                $rec_ids = $this->baseRepository->getExplode($rec_ids);
                $res = $res->whereIn('rec_id', $rec_ids);
            }

            $update = $res;

            //重新计算价格并更新价格
            $total_number = $res->sum('goods_number');

            $price_info = $this->goodsService->calculateGoodsPrice($goods_id, $total_number);

            $update->update([
                'goods_price' => $price_info['unit_price']
            ]);
        }
    }

    /**
     * 清除购物车
     *
     * @param int $user_id
     * @param int $rec_id
     */
    public function clearWholesaleCart($user_id = 0, $rec_id = 0)
    {
        $user_id = session()->has('user_id') && session('user_id') ? session('user_id') : $user_id;

        $res = WholesaleCart::whereRaw(1);

        if (!empty($user_id)) {
            $res = $res->where('user_id', $user_id);
        } else {
            $session_id = $this->sessionRepository->realCartMacIp();
            $res = $res->where('session_id', $session_id);
        }

        if ($rec_id) {
            $rec_id = $this->baseRepository->getExplode($rec_id);
            $res = $res->whereIn('user_id', $rec_id);
        }

        $res->delete();
    }

    /**
     * 检查购物车中是否有商品
     *
     * @param int $user_id
     * @param int $rec_type
     * @return mixed
     */
    public function getHasCartGoods($user_id = 0, $rec_type = 0)
    {
        $user_id = session()->has('user_id') && session('user_id') ? session('user_id') : $user_id;

        $res = WholesaleCart::whereRaw(1);

        if (!empty($user_id)) {
            $res = $res->where('user_id', $user_id);
        } else {
            $session_id = $this->sessionRepository->realCartMacIp();
            $res = $res->where('session_id', $session_id);
        }

        $res = $res->where('rec_type', $rec_type);

        return $res->count();
    }


    /**
     * 检查购物车中是否有商品
     *
     * @param int $user_id
     * @param int $rec_type
     * @return mixed
     */
    public function getConsignee($user_id = 0)
    {
        /*收货人地址*/
        $list['consignee'] = $this->userAddressService->getDefaultByUserId($user_id);
        if (!isset($list['consignee']['province'])) {
            $msg['error'] = 'address';
            $msg['msg'] = '请填写收货地址';
            return $msg;
        }

        $list['consignee']['province'] = $list['consignee']['province'] ?? 0;
        $list['consignee']['city'] = $list['consignee']['city'] ?? 0;
        $list['consignee']['district'] = $list['consignee']['district'] ?? 0;

        $list['consignee']['province_name'] = $this->DeliveryArea($list['consignee']['province']);
        $list['consignee']['city_name'] = $this->DeliveryArea($list['consignee']['city']);
        $list['consignee']['district_name'] = $this->DeliveryArea($list['consignee']['district']);

        return $list['consignee'];
    }

    /*根据地区ID获取具体配送地区*/
    public function DeliveryArea($region_id)
    {
        $res = Region::where('region_id', $region_id)
            ->value('region_name');
        return $res;
    }

    /**
     * 获取选中的购物车商品rec_id
     *
     * @param int $is_select 获取类型：0|session , 1|查询表数据
     * @param int $user_id 会员ID
     * @return int|mixed|void 返回值
     */
    public function getWholesaleCartValue($is_select = 0, $user_id = 0)
    {
        if ($is_select) {

            if ($user_id > 0) {
                $list = WholesaleCart::where('user_id', $user_id);
            } else {
                $session_id = $this->sessionRepository->realCartMacIp();
                $list = WholesaleCart::where('session_id', $session_id);
            }

            $list = $list->where('is_checked', 1);
            $list = $this->baseRepository->getToArrayGet($list);
            $cart_value = $this->baseRepository->getKeyPluck($list, 'rec_id');
            $cart_value = $cart_value ? $cart_value : 0;
        } else {
            $cart_value = 0;
            if (session()->exists('wholesale_cart_value')) {
                $cart_value = session('wholesale_cart_value');
            }
        }

        return $cart_value;
    }

    /**
     * 存储获取购物车商品rec_id
     *
     * @param array $cart_value 购物车ID
     */
    public function pushWholesaleCartValue($cart_value = [])
    {
        if ($cart_value) {
            $cart_value = $this->baseRepository->getExplode($cart_value);
            session(['wholesale_cart_value' => $cart_value]);
        } else {
            session(['wholesale_cart_value' => '']);
        }
    }
}
