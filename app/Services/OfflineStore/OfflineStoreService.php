<?php

namespace App\Services\OfflineStore;

use App\Models\Cart;
use App\Models\OfflineStore;
use App\Models\StoreGoods;
use App\Models\StoreProducts;
use App\Models\Users;
use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsAttrService;

/**
 * Class OfflineStoreService
 * @package App\Services\OfflineStore
 */
class OfflineStoreService
{
    protected $config;
    protected $goodsAttrService;
    protected $dscRepository;

    public function __construct(
        GoodsAttrService $goodsAttrService,
        DscRepository $dscRepository
    )
    {
        $this->goodsAttrService = $goodsAttrService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 门店列表
     * Class User
     * @package App\Services
     */
    public function listOfflineStore($provinces_id, $city_id, $district_id, $store_id, $goods_id, $spec_arr, $page = 1, $size = 10, $num = 1, $rec_id = [])
    {

        if ($rec_id) {
            if (!is_array($rec_id)) {
                $rec_id = explode(',', $rec_id);
            }

            $cart = cart::whereIn('rec_id', $rec_id)->get();
            if ($cart) {
                $cart_list = collect($cart)->keyBy(function ($item) {
                    return $item['goods_id'] . '_' . $item['goods_attr_id'];
                })->toArray();
                //通过rec_id 获取goods_id
                $goods_id = array_keys($cart_list);
            }
        }

        $begin = ($page - 1) * $size;
        $store_list = OfflineStore::where('is_confirm', 1);

        if ($provinces_id > 0) {
            $store_list = $store_list->where('province', $provinces_id);
        }

        if ($city_id > 0) {
            $store_list = $store_list->where('city', $city_id);
        }

        if ($district_id > 0) {
            $store_list = $store_list->where('district', $district_id);
        }

        if ($store_id > 0) {
            $store_list = $store_list->where('id', $store_id);
        }

        if (is_array($goods_id) && $goods_id) {
            //1.4.2 购物车自提，多商品
            $store_list = $store_list->whereHas('getStoreGoods', function ($query) use ($goods_id) {
                $query->whereIn('goods_id', $goods_id);
            });
        } else {
            if ($goods_id > 0) {
                $store_list = $store_list->whereHas('getStoreGoods', function ($query) use ($goods_id) {
                    $query->where('goods_id', $goods_id);
                });
            }
        }

        $store_list = $store_list->with([
            'getRegionProvince' => function ($query) {
                $query->select('region_id', 'region_name');
            },
            'getRegionCity' => function ($query) {
                $query->select('region_id', 'region_name');
            },
            'getRegionDistrict' => function ($query) {
                $query->select('region_id', 'region_name');
            },
        ]);
        $store_list = $store_list->offset($begin)
            ->limit($size)->get();

        $store_list = $store_list ? $store_list->toArray() : [];

        if (!empty($store_list)) {
            foreach ($store_list as $k => $v) {
                if (isset($v['get_region_province']) && $v['get_region_province']) {
                    $province_name = $v['get_region_province']['region_name'];
                } else {
                    $province_name = '';
                }

                if (isset($v['get_region_city']) && $v['get_region_city']) {
                    $city_name = $v['get_region_city']['region_name'];
                } else {
                    $city_name = '';
                }

                if (isset($v['get_region_district']) && $v['get_region_district']) {
                    $district_name = $v['get_region_district']['region_name'];
                } else {
                    $district_name = '';
                }
                $store_list[$k]['address'] = $province_name . " " . $city_name . " " . $district_name;
                if ($v['id'] == $store_id) {
                    $store_list[$k]['checked'] = 1;
                }
                $store_list[$k]['is_stocks'] = 1;//1.4.3库存is_stocks，未使用stock废弃

                if (is_array($goods_id) && $goods_id && $cart_list) {
                    //1.4.2 购物车自提，多商品
                    $goods_list = StoreGoods::whereIn('goods_id', $goods_id)->where('store_id', $v['id'])->get();
                    if ($goods_list) {
                        //判断库存
                        foreach ($goods_list as $row) {

                            $row_cart_goods = collect($cart)->where('goods_id', $row['goods_id'])->toArray();
                            foreach ($row_cart_goods as $v_cart_goods) {
                                if ($v_cart_goods['goods_attr_id']) {
                                    //有属性
                                    $spec_arr = explode(',', $v_cart_goods['goods_attr_id']);
                                    if ($this->goodsAttrService->is_spec($spec_arr) == true) {
                                        rsort($spec_arr);
                                        $spec_arr = implode('|', $spec_arr);
                                        $products = $this->get_offline_num($row['goods_id'], $spec_arr, $v['ru_id'], $v['id']);
                                        if ($products == 0 || $v_cart_goods['goods_number'] > $products) {
                                            $store_list[$k]['is_stocks'] = 0;
                                            break(2);
                                        }
                                    }
                                } else {
                                    //无属性
                                    if ($row['goods_number'] < $v_cart_goods['goods_number']) {
                                        $store_list[$k]['is_stocks'] = 0;
                                        break(2);
                                    }
                                }
                            }
                        }
                    }

                    $backurl = url('mobile/#/cart/');
                } else {
                    $goods_number = StoreGoods::where('goods_id', $goods_id)->where('store_id', $v['id'])->value('goods_number');

                    if ($this->goodsAttrService->is_spec($spec_arr) == true) {
                        rsort($spec_arr);
                        $spec_arr = implode('|', $spec_arr);
                        $products = $this->get_offline_num($goods_id, $spec_arr, $v['ru_id'], $v['id']);
                        if ($products == 0 || $num > $products) {
                            $store_list[$k]['is_stocks'] = 0;
                        }
                    } else {
                        //1.4.2 修复，设置属性库存时，则调取属性库存信息，请忽略默认库存
                        if ($goods_number < $num) {
                            $store_list[$k]['is_stocks'] = 0;
                        }
                    }

                    $backurl = url('mobile/#/goods/' . $goods_id);
                }

                $address = $province_name . $city_name . $district_name . $store_list[$k]['stores_address'];

                $store_list[$k]['map_url'] = "http://apis.map.qq.com/tools/routeplan/eword=" . $address . "?referer=myapp&key=" . $this->config['tengxun_key'] . "&back=1&backurl=" . $backurl;

            }
        }
        return $store_list;
    }

    /**
     * 门店详情
     * Class User
     * @package App\Services
     */
    public function infoOfflineStore($store_id)
    {
        $store_info = OfflineStore::where('id', $store_id);

        $store_info = $store_info->with([
            'getRegionProvince' => function ($query) {
                $query->select('region_id', 'region_name');
            },
            'getRegionCity' => function ($query) {
                $query->select('region_id', 'region_name');
            },
            'getRegionDistrict' => function ($query) {
                $query->select('region_id', 'region_name');
            },
        ]);

        $store_info = $store_info->first();
        $store_info = $store_info ? $store_info->toArray() : [];

        if ($store_info) {
            if ($store_info['get_region_province']) {
                $region_name = $store_info['get_region_province']['region_name'];
                unset($store_info['get_region_province']);
            } else {
                $region_name = '';
            }
            if ($store_info['get_region_city']) {
                $city_name = $store_info['get_region_city']['region_name'];
                unset($store_info['get_region_city']);
            } else {
                $city_name = '';
            }
            if ($store_info['get_region_district']) {
                $district_name = $store_info['get_region_district']['region_name'];
                unset($store_info['get_region_district']);
            } else {
                $district_name = '';
            }

            $store_info['address'] = $region_name . " " . $city_name . " " . $district_name;
        }

        return $store_info;
    }

    public function get_offline_num($goods_id = 0, $spec_arr = '', $ru_id = 0, $store_id = 0)
    {
        if ($spec_arr) {
            $res = StoreProducts::select('product_number')->where([
                ['goods_id', '=', $goods_id],
                ['goods_attr', '=', $spec_arr],
                ['ru_id', '=', $ru_id],
                ['store_id', '=', $store_id]
            ])->first();

            $res = $res ? $res->toArray() : [];
        }
        $num = $res['product_number'] ?? 0;
        return $num;
    }

    //获取用户手机号
    public function getUserMobile($user_id = 0)
    {
        $mobile = Users::where('user_id', $user_id)->value('mobile_phone');
        return $mobile;
    }
}
