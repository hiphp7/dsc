<?php

namespace App\Services\Package;

use App\Models\Goods;
use App\Models\GoodsAttr;
use App\Models\PackageGoods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsProdutsService;

class PackageGoodsService
{
    protected $baseRepository;
    protected $dscRepository;
    protected $goodsProdutsService;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        GoodsProdutsService $goodsProdutsService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->goodsProdutsService = $goodsProdutsService;
    }

    /**
     * 检查礼包内商品的库存
     *
     * @param int $package_id
     * @param int $package_num
     * @return bool
     */
    public function judgePackageStock($package_id = 0, $package_num = 1)
    {
        $package_num = $package_num ? intval($package_num) : 0;

        $row = PackageGoods::select(['goods_id', 'product_id', 'goods_number'])->where('package_id', $package_id);
        $row = $this->baseRepository->getToArrayGet($row);

        if (empty($row)) {
            return true;
        }

        /* 分离货品与商品 */
        $goods = ['product_ids' => '', 'goods_ids' => ''];
        foreach ($row as $key => $value) {
            if ($value['product_id'] > 0) {
                if ($key > 0) {
                    $goods['product_ids'] .= ',' . $value['product_id'];
                } else {
                    $goods['product_ids'] = $value['product_id'];
                }
                continue;
            }

            if ($key > 0) {
                $goods['goods_ids'] .= ',' . $value['goods_id'];
            } else {
                $goods['goods_ids'] = $value['goods_id'];
            }
        }

        $goods_id = isset($row[0]['goods_id']) && !empty($row[0]['goods_id']) ? $row[0]['goods_id'] : 0;

        $model_attr = Goods::where('goods_id', $goods_id)->value('model_attr');

        /* 检查货品库存 */

        $goods['product_ids'] = trim($goods['product_ids'], ',');

        $product_ids = !is_array($goods['product_ids']) ? explode(",", $goods['product_ids']) : $goods['product_ids'];

        $row = PackageGoods::where('package_id', $package_id);

        $where = [
            'product_id' => $product_ids,
            'package_num' => $package_num
        ];

        if ($model_attr == 1) {
            $row = $row->with([
                'getProductsWarehouse' => function ($query) use ($where) {
                    $query->select('product_id', 'product_number')->whereIn('product_id', $where['product_id']);
                }
            ]);
        } elseif ($model_attr == 2) {
            $row = $row->with([
                'getProductsArea' => function ($query) use ($where) {
                    $query->select('product_id', 'product_number')->whereIn('product_id', $where['product_id']);
                }
            ]);
        } else {
            if ($goods['product_ids']) {
                $row = $row->with([
                    'getProducts' => function ($query) use ($where) {
                        $query->select('product_id', 'product_number')->whereIn('product_id', $where['product_id']);
                    }
                ]);
            }

            if ($goods['goods_ids']) {
                $row = $row->with([
                    'getGoods' => function ($query) use ($goods) {
                        $query->select('goods_id', 'goods_number')->whereIn('goods_id', explode(',', $goods['goods_ids']));
                    }
                ]);
            }
        }

        $row = $this->baseRepository->getToArrayGet($row);

        if ($row) {
            foreach ($row as $key => $val) {
                if ($model_attr == 1) {
                } elseif ($model_attr == 1) {
                } else {
                    if (isset($val['get_products']) && $val['get_products']) {
                        $product_number = ($val && isset($val['get_products']['product_number'])) ? $val['get_products']['product_number'] : 0;
                        $goods_number = $val['goods_number'] * $package_num;
                        if ($goods_number < $product_number) {
                            //库存大于需求删除数组键值
                            unset($row[$key]);
                        }
                    } elseif (isset($val['get_goods']) && $val['get_goods']) {
                        $product_number = ($val && isset($val['get_goods']['goods_number'])) ? $val['get_goods']['goods_number'] : 0;
                        $goods_number = $val['goods_number'] * $package_num;
                        if ($goods_number < $product_number) {
                            //库存大于需求删除数组键值
                            unset($row[$key]);
                        }
                    }
                }
            }
        }
        if (!empty($row)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获得指定礼包的商品
     *
     * @param $package_id
     * @param int $seller_id
     * @param int $type
     * @return array
     */
    public function getPackageGoods($package_id, $seller_id = 0, $type = 0)
    {
        $where = [
            'package_id' => $package_id,
            'admin_id' => session('admin_id', 0),
            'seller_id' => session('seller_id', 0),
            'seller_path' => $seller_id
        ];

        $resource = PackageGoods::where('package_id', $package_id);

        $resource = $resource->whereHas('getGoods');

        if ($package_id == 0 && $seller_id == 0) {
            $resource = $resource->where('admin_id', $where['admin_id']);
        } elseif ($package_id == 0 && $seller_id > 0) {
            $resource = $resource->where('admin_id', $where['seller_id']);
        }

        $resource = $resource->with([
            'getGoods',
            'getProducts',
            'getProductsWarehouse',
            'getProductsArea'
        ]);

        $resource = $this->baseRepository->getToArrayGet($resource);

        if (!$resource) {
            return [];
        }

        $row = [];

        /* 生成结果数组 取存在货品的商品id 组合商品id与货品id */
        $good_product_str = '';

        if ($resource) {
            foreach ($resource as $_row) {

                $goods = $_row['get_goods'];

                $_row['goods_name'] = $goods['goods_name'];
                $_row['goods_weight'] = $goods['goods_weight'];
                $_row['goods_thumb'] = $goods['goods_thumb'];
                $_row['shop_price'] = $goods['shop_price'];

                $_row['goods_thumb'] = $this->dscRepository->getImagePath($_row['goods_thumb']);

                /* 商品重量 */
                $_row['goodsweight'] = $_row['goods_weight'];

                if ($_row['product_id'] > 0) {
                    /* 取存商品id */
                    $good_product_str .= ',' . $_row['goods_id'];

                    /* 组合商品id与货品id */
                    $_row['g_p'] = $_row['goods_id'] . '_' . $_row['product_id'];
                    $_row['old_product_id'] = $_row['product_id'];
                } else {
                    /* 组合商品id与货品id */
                    $_row['g_p'] = $_row['goods_id'];
                }

                $_row['url'] = $this->dscRepository->buildUri('goods', ['gid' => $_row['goods_id']], $_row['goods_name']);
                $_row['shop_price'] = $this->dscRepository->getPriceFormat($_row['shop_price']);

                if ($type == 1) {
                    $_row['products'] = $this->goodsProdutsService->getGoodProducts($_row['goods_id']);
                }
                //生成结果数组
                $row[] = $_row;
            }
        }

        $good_product_str = trim($good_product_str, ',');

        /* 释放空间 */
        unset($resource, $_row, $sql);

        $_goods_attr = [];

        /* 取商品属性 */
        if ($good_product_str) {

            $good_product_str = $this->baseRepository->getExplode($good_product_str);

            $result_goods_attr = GoodsAttr::select('goods_attr_id', 'attr_value')->whereIn('goods_id', $good_product_str);
            $result_goods_attr = $this->baseRepository->getToArrayGet($result_goods_attr);

            if ($result_goods_attr) {
                foreach ($result_goods_attr as $value) {
                    $_goods_attr[$value['goods_attr_id']] = $value['attr_value'];
                }
            }
        }

        /* 过滤货品 */
        $format[0] = '%s[%s]--[%d]';
        $format[1] = '%s--[%d]';
        foreach ($row as $key => $value) {
            $row[$key]['goods_name_pack'] = $value['goods_name'];
            if (isset($value['goods_attr']) && !empty($value['goods_attr'])) {
                $goods_attr_array = explode('|', $value['goods_attr']);

                $goods_attr = [];
                foreach ($goods_attr_array as $_attr) {
                    $goods_attr[] = $_goods_attr ? $_goods_attr[$_attr] : '';
                }

                $goods_attr = $goods_attr ? implode('，', $goods_attr) : '';

                $row[$key]['goods_name'] = sprintf($format[0], $value['goods_name'], $goods_attr, $value['goods_number']);
            } else {
                $row[$key]['goods_name'] = sprintf($format[1], $value['goods_name'], $value['goods_number']);
            }
        }

        return $row;
    }
}
