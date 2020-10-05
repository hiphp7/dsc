<?php

namespace App\Services\Goods;

use App\Models\Goods;
use App\Models\GoodsAttr;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Models\StoreGoods;
use App\Models\StoreProducts;
use App\Models\WarehouseAreaGoods;
use App\Models\WarehouseGoods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;

class GoodsWarehouseService
{
    protected $baseRepository;
    protected $config;
    protected $dscRepository;

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
     * 查询属性商品仓库库存
     *
     * @param int $goods_id
     * @param string $attr_id
     * @param int $seller_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param string $model_attr
     * @param int $store_id
     * @return mixed
     */
    public function getWarehouseAttrNumber($goods_id = 0, $attr_id = '', $warehouse_id = 0, $area_id = 0, $area_city = 0, $model_attr = '', $store_id = 0)
    {
        if (empty($model_attr)) {
            $model_attr = Goods::where('goods_id', $goods_id)->value('model_attr');
        }

        $attr_arr = [];
        if (!empty($attr_id)) {

            $attr_id = is_array($attr_id) ? implode(',', $attr_id) : $attr_id;

            //去掉复选属性by wu start
            if (strpos($attr_id, '|') !== false) {
                $attr_arr = $this->baseRepository->getExplode($attr_id, '|');
            } else {
                $attr_arr = $this->baseRepository->getExplode($attr_id);
            }

            foreach ($attr_arr as $key => $val) {

                $goods_attr_id = $this->baseRepository->getExplode($val);
                $res = GoodsAttr::where('goods_id', $goods_id)
                    ->whereIn('goods_attr_id', $goods_attr_id);

                $res = $res->with([
                    'getGoodsAttribute'
                ]);

                $res = $this->baseRepository->getToArrayFirst($res);
                $attr_type = $res['get_goods_attribute']['attr_type'] ?? 0;

                if (($attr_type == 0 || $attr_type == 2) && $attr_arr[$key]) {
                    unset($attr_arr[$key]);
                }
            }
            //去掉复选属性by wu end
        }

        if ($store_id > 0) {
            /* 门店商品 */
            $res = StoreProducts::where('goods_id', $goods_id)
                ->where('store_id', $store_id);
        } else {
            /* 普通商品 */
            if ($model_attr == 1) {
                $res = ProductsWarehouse::where('goods_id', $goods_id)
                    ->where('warehouse_id', $warehouse_id);
            } elseif ($model_attr == 2) {
                $res = ProductsArea::where('goods_id', $goods_id)
                    ->where('area_id', $area_id);

                if ($this->config['area_pricetype'] == 1) {
                    $res = $res->where('city_id', $area_city);
                }
            } else {
                $res = Products::where('goods_id', $goods_id);
            }
        }

        if (!empty($attr_arr)) {
            //获取货品信息
            foreach ($attr_arr as $key => $val) {
                $res = $res->whereRaw("FIND_IN_SET('$val', REPLACE(goods_attr, '|', ','))");
            }
        }

        $res = $res->orderBy('product_id', 'desc');

        $res = $this->baseRepository->getToArrayFirst($res);

        return $res;
    }

    /**
     * 查询商品仓库货品信息
     *
     * @param int $goods_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param int $model_attr
     * @param int $store_id
     * @return array
     */
    public function getGoodsProductsProd($goods_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $model_attr = 0, $store_id = 0)
    {
        if ($store_id > 0) {
            $prod = StoreProducts::where('goods_id', $goods_id)
                ->where('store_id', $store_id);
        } else {
            if ($model_attr == 1) {
                $prod = ProductsWarehouse::where('goods_id', $goods_id)->where('warehouse_id', $warehouse_id);
            } elseif ($model_attr == 2) {
                $prod = ProductsArea::where('goods_id', $goods_id)->where('area_id', $area_id);

                if ($this->config['area_pricetype'] == 1) {
                    $prod = $prod->where('city_id', $area_city);
                }
            } else {
                $prod = Products::where('goods_id', $goods_id);
            }
        }

        $prod = $this->baseRepository->getToArrayFirst($prod);

        return $prod;
    }

    /**
     * 获取商品属性库存
     *
     * @param $goods_id
     * @param $attr_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param int $store_id
     * @return int|mixed
     */
    public function goodsAttrNumber($goods_id, $attr_id, $warehouse_id = 0, $area_id = 0, $area_city = 0, $store_id = 0)
    {
        $model_attr = Goods::where('goods_id', $goods_id)->value('model_attr');
        $model_attr = $model_attr ? $model_attr : 0;

        $products = $this->getWarehouseAttrNumber($goods_id, $attr_id, $warehouse_id, $area_id, $area_city, $model_attr, $store_id);
        if (empty($products)) {
            $goods_number = $this->goodsWarehouseNumber($goods_id, $warehouse_id, $area_id, $area_city, $model_attr, $store_id);//无属性库存
            $attr_number = $goods_number;
        } else {
            $attr_number = $products['product_number'];
        }

        return !empty($attr_number) ? $attr_number : 0;
    }

    /**
     * 商品库存
     *
     * @param int $goods_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param int $model_attr
     * @param int $store_id
     * @return array
     */
    public function goodsWarehouseNumber($goods_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $model_attr = 0, $store_id = 0)
    {
        if ($store_id > 0) {
            $goods_number = StoreGoods::select('goods_number')
                ->where('goods_id', $goods_id)
                ->where('store_id', $store_id)
                ->value('goods_number');
        } else {
            if ($model_attr == 1) {
                $goods_number = WarehouseGoods::select('region_number as goods_number')
                    ->where('goods_id', $goods_id)
                    ->where('region_id', $warehouse_id)
                    ->value('goods_number');
            } elseif ($model_attr == 2) {
                $goods_number = WarehouseAreaGoods::select('region_number as goods_number')
                    ->where('goods_id', $goods_id)
                    ->where('region_id', $area_id);

                if ($this->config['area_pricetype'] == 1) {
                    $goods_number = $goods_number->where('city_id', $area_city);
                }

                $goods_number = $goods_number->value('goods_number');
            } else {
                $goods_number = Goods::select('goods_number')
                    ->where('goods_id', $goods_id)
                    ->value('goods_number');
            }
        }

        $goods_number = $goods_number ? $goods_number : 0;

        return $goods_number;
    }
}
