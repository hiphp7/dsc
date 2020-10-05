<?php

namespace App\Services\Goods;

use App\Models\Goods;
use App\Models\GoodsAttr;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Models\WarehouseAreaGoods;
use App\Models\WarehouseGoods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;

class GoodsProdutsService
{
    protected $baseRepository;
    protected $dscRepository;
    protected $config;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 取商品的货品列表
     *
     * @param int $goods_id 单个商品id；多个商品id数组；以逗号分隔商品id字符串
     * @param int $product_id
     * @return array
     */
    public function getGoodProducts($goods_id = 0, $product_id = 0)
    {
        if (empty($goods_id)) {
            return [];
        }

        $model_attr = Goods::where('goods_id', $goods_id)->value('model_attr');

        $gettype = gettype($goods_id);

        /* 取货品 */
        if ($model_attr == 1) {
            $result_products = ProductsWarehouse::whereRaw(1);
        } elseif ($model_attr == 2) {
            $result_products = ProductsArea::whereRaw(1);
        } else {
            $result_products = Products::whereRaw(1);
        }


        if ($gettype == 'integer') {
            $result_products = $result_products->where('goods_id', $goods_id);
        } elseif ($gettype == 'array' || $gettype == 'string') {
            if ($goods_id) {
                $goods_id = !is_array($goods_id) ? explode(",", $goods_id) : $goods_id;
                $result_products = $result_products->whereIn('goods_id', $goods_id);
            }
        }

        if ($product_id) {
            $result_products = $result_products->where('product_id', $product_id);
        }

        $result_products = $result_products->get();

        $result_products = $result_products ? $result_products->toArray() : [];

        /* 取商品属性 */
        $result_goods_attr = GoodsAttr::whereRaw(1);

        if ($gettype == 'integer') {
            $result_goods_attr = $result_goods_attr->where('goods_id', $goods_id);
        } elseif ($gettype == 'array' || $gettype == 'string') {
            if ($goods_id) {
                $goods_id = !is_array($goods_id) ? explode(",", $goods_id) : $goods_id;
                $result_goods_attr = $result_goods_attr->whereIn('goods_id', $goods_id);
            }
        }

        $result_goods_attr = $result_goods_attr->get();

        $result_goods_attr = $result_goods_attr ? $result_goods_attr->toArray() : [];

        if ($result_products) {
            $_goods_attr = [];
            foreach ($result_goods_attr as $value) {
                $_goods_attr[$value['goods_attr_id']] = $value['attr_value'];
            }

            $goods_attr_str = '';

            /* 过滤货品 */
            foreach ($result_products as $key => $value) {
                $goods_attr_array = explode('|', $value['goods_attr']);
                if (is_array($goods_attr_array)) {
                    $goods_attr = [];
                    foreach ($goods_attr_array as $_attr) {
                        if (isset($_goods_attr[$_attr]) && $_goods_attr[$_attr]) {
                            $goods_attr[] = $_goods_attr[$_attr];
                        }
                    }

                    $goods_attr_str = !empty($goods_attr[0]) ? implode(',', $goods_attr) : '';
                }

                $result_products[$key]['goods_attr_str'] = $goods_attr_str;
            }
        }

        return $result_products;
    }

    /**
     * 商品属性价格、促销价格
     * @param  $goods_id
     * @param  $attr_id
     * @param  $warehouse_id
     * @param  $area_id
     * @param  $field 调用字段
     * @return $attr_price
     */
    public function goodsPropertyPrice($goods_id, $attr_id, $warehouse_id = 0, $area_id = 0, $area_city = 0, $field = '')
    {
        $goods = Goods::where('goods_id', $goods_id)->first();
        $goods = $goods ? $goods->toArray() : [];

        $attr_price = 0;
        if ($goods) {
            $products = $this->getProductsAttrPrice($goods_id, $attr_id, $warehouse_id, $area_id, $area_city, $goods['model_attr']); //获取有属性价格
            $prod = $this->goodsWarehousePrice($goods_id, $warehouse_id, $area_id, $goods['model_attr']);//无属性价格

            if ($field == 'product_promote_price') {
                if (empty($products) || $products['product_promote_price'] < 0) {
                    if (empty($prod) || $prod['product_promote_price'] < 0) {
                        $attr_price = !empty($goods['promote_price']) ? $goods['promote_price'] : 0;
                    } else {
                        $attr_price = $prod['product_promote_price'];
                    }
                } else {
                    $attr_price = $products['product_promote_price'];
                }
            } else {
                if (empty($products) || $products['product_price'] < 0) {
                    if (empty($prod) || $prod['product_price'] < 0) {
                        $attr_price = !empty($goods['shop_price']) ? $goods['shop_price'] : 0;
                    } else {
                        $attr_price = $prod['product_price'];
                    }
                } else {
                    $attr_price = $products['product_price'];
                }
            }
        }

        return !empty($attr_price) ? $attr_price : 0;
    }

    /**
     * 无属性商品价格
     * @param $goods_id
     * @param $warehouse_id
     * @param $area_id
     * @param $model_attr
     * @return mixed
     */
    public function goodsWarehousePrice($goods_id, $warehouse_id, $area_id, $model_attr = 0)
    {
        if ($model_attr == 1) {
            $product_price = WarehouseGoods::select('warehouse_price as product_price', 'warehouse_promote_price as product_promote_price')
                ->where('goods_id', $goods_id)
                ->where('region_id', $warehouse_id)
                ->first();
        } elseif ($model_attr == 2) {
            $product_price = WarehouseAreaGoods::select('region_price as product_price', 'region_promote_price as product_promote_price')
                ->where('goods_id', $goods_id)
                ->where('region_id', $area_id)
                ->first();
        } else {
            $product_price = Goods::select('shop_price as product_price', 'promote_price as product_promote_price')
                ->where('goods_id', $goods_id)
                ->first();
        }

        if ($product_price === null) {
            return [];
        }

        return $product_price->toArray();
    }

    /**
     * 查询属性商品仓库价格
     *
     * @param int $goods_id
     * @param string $attr_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param int $model_attr
     * @return array
     */
    public function getProductsAttrPrice($goods_id = 0, $attr_id = '', $warehouse_id = 0, $area_id = 0, $area_city = 0, $model_attr = 0)
    {
        $attr_type_spec = '';
        if (empty($attr_id)) {
            $attr_id = [];
        } else {
            //去掉复选属性 start
            $attr_arr = $this->baseRepository->getExplode($attr_id);

            foreach ($attr_arr as $key => $val) {

                $goods_attr_id = $this->baseRepository->getExplode($val);
                $res = GoodsAttr::where('goods_id', $goods_id)
                    ->whereIn('goods_attr_id', $goods_attr_id);

                $res = $res->with([
                    'getGoodsAttribute'
                ]);

                $res = $this->baseRepository->getToArrayFirst($res);
                $attr_type = $res['get_goods_attribute']['attr_type'] ?? 0;

                if ($attr_type == 2 && $attr_arr[$key]) {
                    $attr_type_spec .= $attr_arr[$key] . ",";
                    unset($attr_arr[$key]);
                }
            }

            $attr_id = $attr_arr;
            //去掉复选属性 end
        }

        /* 复选价格 start */
        $attr_type_spec_price = 0;
        if ($attr_type_spec) {
            $attr_type_spec_price = app(GoodsAttrService::class)->getGoodsAttrPrice($goods_id, $model_attr, $attr_type_spec, $warehouse_id, $area_id, $area_city);
        }
        /* 复选价格 end */

        //商品属性价格模式,货品模式
        if ($this->config['goods_attr_price'] == 1) {
            if ($model_attr == 1) {
                $row = ProductsWarehouse::select('product_price', 'product_promote_price', 'product_market_price')
                    ->where('goods_id', $goods_id)
                    ->where('warehouse_id', $warehouse_id);
            } elseif ($model_attr == 2) {
                $row = ProductsArea::select('product_price', 'product_promote_price', 'product_market_price')
                    ->where('goods_id', $goods_id)
                    ->where('area_id', $area_id);

                if ($this->config['area_pricetype'] == 1) {
                    $row = $row->where('city_id', $area_city);
                }

            } else {
                $row = Products::select('product_price', 'product_promote_price', 'product_market_price')
                    ->where('goods_id', $goods_id);
            }

            //获取货品信息
            if ($attr_id) {
                foreach ($attr_id as $key => $val) {
                    $row = $row->whereRaw("FIND_IN_SET('$val', REPLACE(goods_attr, '|', ','))");
                }

                $product_price = $this->baseRepository->getToArrayFirst($row);

                //单选 + 复选
                if ($product_price) {
                    $product_price['product_price'] = $product_price['product_price'] + $attr_type_spec_price;
                    $product_price['product_promote_price'] = $product_price['product_promote_price'] + $attr_type_spec_price;
                    $product_price['product_market_price'] = $product_price['product_market_price'] + $attr_type_spec_price;
                }
            } elseif ($attr_type_spec) {
                //复选
                $product_price['product_price'] = $attr_type_spec_price;
                $product_price['product_promote_price'] = $attr_type_spec_price;
                $product_price['product_market_price'] = $attr_type_spec_price;
            } else {
                $product_price = $this->baseRepository->getToArrayFirst($row); // 无属性
            }

            return $product_price;
        }
    }
}