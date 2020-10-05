<?php

/**
 * 商品属性
 */

namespace App\Services\Goods;

use App\Models\Attribute;
use App\Models\AttributeImg;
use App\Models\Goods;
use App\Models\GoodsAttr;
use App\Models\GoodsType;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Models\StoreProducts;
use App\Models\WarehouseAreaAttr;
use App\Models\WarehouseAttr;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Erp\JigonManageService;

class GoodsAttrService
{
    protected $baseRepository;
    protected $commonRepository;
    protected $dscRepository;
    protected $config;

    public function __construct(
        BaseRepository $baseRepository,
        CommonRepository $commonRepository,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->commonRepository = $commonRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }


    /**
     * 商品属性  名称查询
     *
     * @param int $attrId
     * @return mixed
     */
    public function getAttrNameById($attrId = 0)
    {
        $attrId = empty($attrId) ? [] : $attrId;

        if ($attrId) {
            $goodsAttr = GoodsAttr::whereRaw(1);

            if (is_array($attrId)) {
                $goodsAttr = $goodsAttr->wherein('goods_attr_id', $attrId);
                $goodsAttr = $goodsAttr->with([
                    'getGoodsAttribute'
                ]);

                $goodsAttr = $this->baseRepository->getToArrayGet($goodsAttr);

                if ($goodsAttr) {
                    foreach ($goodsAttr as $key => $value) {
                        $goodsAttr[$key]['attr_name'] = $value['get_goods_attribute']['attr_name'] ?? '';
                    }
                }

            } elseif (is_int($attrId)) {
                $goodsAttr = $goodsAttr->where('goods_attr_id', $attrId);
                $goodsAttr = $goodsAttr->with([
                    'getGoodsAttribute'
                ]);

                $goodsAttr = $this->baseRepository->getToArrayFirst($goodsAttr);

                if ($goodsAttr) {
                    $goodsAttr['attr_name'] = $goodsAttr['get_goods_attribute']['attr_name'] ?? '';
                }
            }
        } else {
            $goodsAttr = [];
        }

        return $goodsAttr;
    }


    /**
     * 查询商品属性
     * @param int $goods_id
     * @return array
     */
    public function goodsAttr($goods_id = 0)
    {

        /* 获得商品的规格 */
        $res = GoodsAttr::where('goods_id', $goods_id);

        $res = $res->with([
            'getGoodsAttribute'
        ]);

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $val) {
                $attribute = $val['get_goods_attribute'];

                $res[$key]['attr_id'] = $attribute['attr_id'];
                $res[$key]['attr_name'] = $attribute['attr_name'];
                $res[$key]['attr_group'] = $attribute['attr_group'];
                $res[$key]['is_linked'] = $attribute['is_linked'];
                $res[$key]['attr_type'] = $attribute['attr_type'];
                $res[$key]['sort_order'] = $attribute['sort_order'];
                $res[$key]['attr_img_flie'] = $val['attr_img_flie'] ? $this->dscRepository->getImagePath($val['attr_img_flie']) : '';
            }
        }

        if (is_null($res)) {
            return [];
        }

        $result = [];
        foreach ($res as $key => $value) {
            $result[$value['attr_name']][] = $value;
        }

        $ret = [];
        foreach ($result as $key => $value) {
            array_push($ret, $value);
        }
        $arr = [];
        foreach ($ret as $k => $v) {
            $arr[$k]['attr_id'] = $v[0]['attr_id'];
            $arr[$k]['attr_name'] = $v[0]['attr_name'];
            $arr[$k]['sort_order'] = $v[0]['sort_order'];

            $v = $this->baseRepository->getSortBy($v, 'attr_sort');

            $arr[$k]['attr_key'] = $v;
        }

        $arr = $this->baseRepository->getSortBy($arr, 'sort_order');

        return $arr;
    }

    /**
     * 查询商品参数规格
     *
     * @param int $goods_id
     * @return mixed
     */
    public function goodsAttrParameter($goods_id = 0)
    {
        $res = GoodsAttr::where('goods_id', $goods_id);

        $res = $res->whereHas('getGoodsAttribute', function ($query) {
            $query->where('attr_type', 0);
        });

        $res = $res->with([
            'getGoodsAttribute'
        ]);

        $res = $res->orderBy('attr_sort');

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $value) {
                $res[$key]['attr_name'] = $value['get_goods_attribute']['attr_name'] ?? '';
            }
        }

        return $res;
    }

    /**
     * 商品属性组
     *
     * @param int $goods_id
     * @return array
     */
    public function attrGroup($goods_id = 0)
    {
        $model = GoodsAttr::where('goods_id', $goods_id);

        $model = $model->with([
            'getGoodsType'
        ]);

        $model = $this->$this->baseRepository->getToArrayFirst($model);

        $attr_group = '';
        if ($model) {
            $attr_group = $model['get_goods_type']['attr_group'] ?? '';
        }

        return $attr_group;
    }

    /**
     * 是否存在规格
     *
     * @param array $goods_attr_id_array
     * @return array|bool
     */
    public function is_spec($goods_attr_id_array = [])
    {
        if (empty($goods_attr_id_array)) {
            return $goods_attr_id_array;
        }

        $goods_attr_id_array = $this->baseRepository->getExplode($goods_attr_id_array);

        $res = Attribute::where('attr_type', 1)
            ->whereHas('getGoodsAttr', function ($query) use ($goods_attr_id_array) {
                $query->whereIn('goods_attr_id', $goods_attr_id_array);
            });

        $res = $res->count();

        if ($res > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 取指定规格的货品信息
     *
     * @param $goods_id
     * @param $spec_goods_attr_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param int $store_id
     * @return array
     */
    public function getProductsInfo($goods_id, $spec_goods_attr_id, $warehouse_id = 0, $area_id = 0, $area_city = 0, $store_id = 0)
    {
        $model_attr = Goods::where('goods_id', $goods_id)->value('model_attr');

        $return_array = [];

        if (empty($spec_goods_attr_id) || !is_array($spec_goods_attr_id) || empty($goods_id)) {
            return $return_array;
        }

        $goods_attr_array = $this->sortGoodsAttrIdArray($spec_goods_attr_id);

        if (isset($goods_attr_array['sort']) && $goods_attr_array['sort']) {
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

            if (!empty($goods_attr_array['sort'])) {
                //获取货品信息
                foreach ($goods_attr_array['sort'] as $key => $val) {
                    $res = $res->whereRaw("FIND_IN_SET('$val', REPLACE(goods_attr, '|', ','))");
                }
            }

            $res = $res->orderBy('product_id', 'desc');

            $res = $res->first();

            $return_array = $res ? $res->toArray() : [];

            //贡云商品 获取贡云货品库存
            if (!empty($return_array)) {
                if (isset($return_array['cloud_product_id']) && $return_array['cloud_product_id'] > 0) {
                    $return_array['product_number'] = app(JigonManageService::class)->jigonGoodsNumber(['cloud_product_id' => $return_array['cloud_product_id']]);
                }
            }
        }

        return $return_array;
    }

    /**
     * 将 goods_attr_id 的序列按照 attr_id 重新排序
     *
     * @param array $goods_attr_id_array
     * @param string $sort
     * @return array
     */
    public function sortGoodsAttrIdArray($goods_attr_id_array = [], $sort = 'asc')
    {
        if (empty($goods_attr_id_array)) {
            return $goods_attr_id_array;
        }

        $goods_attr_id_array = !is_array($goods_attr_id_array) ? explode(",", $goods_attr_id_array) : $goods_attr_id_array;

        //重新排序
        $res = GoodsAttr::whereIn('goods_attr_id', $goods_attr_id_array);

        $res = $res->whereHas('getGoodsAttribute', function ($query) use ($goods_attr_id_array) {
            $query->where('attr_type', 1);
        });

        $res = $res->with(['getGoodsAttribute']);

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        $return_arr = [];
        if ($res) {
            foreach ($res as $key => $val) {
                $attribute = $val['get_goods_attribute'];

                $res[$key]['sort_order'] = $attribute['sort_order'];
                $res[$key]['attr_name'] = $attribute['attr_name'];
                $res[$key]['attr_id'] = $attribute['attr_id'];
            }

            $res = $this->baseRepository->getSortBy($res, 'attr_sort', $sort);

            $return_arr = [
                'sort',
                'row'
            ];
            foreach ($res as $value) {
                $goods_attr = $value['get_goods_attribute'];
                $value['attr_type'] = $goods_attr['attr_type'];

                $return_arr['sort'][] = $value['goods_attr_id'];
                $return_arr['row'][$value['goods_attr_id']] = $value;
            }

            $return_arr['row'] = $return_arr['row'] ? $this->baseRepository->getSortBy($return_arr['row'], 'attr_sort') : [];
        }

        return $return_arr;
    }

    /**
     * 获得指定的规格的价格
     *
     * @param $spec 规格ID的数组或者逗号分隔的字符串
     * @param int $goods_id
     * @param array $warehouse_area
     * @return float
     */
    public function specPrice($spec, $goods_id = 0, $warehouse_area = [])
    {
        if (!empty($spec)) {
            if (is_array($spec)) {
                foreach ($spec as $key => $val) {
                    $spec[$key] = addslashes($val);
                }
            } else {
                $spec = addslashes($spec);
            }

            $spec = $this->baseRepository->getExplode($spec);

            $warehouse_id = $warehouse_area['warehouse_id'] ?? 0;
            $area_id = $warehouse_area['area_id'] ?? 0;
            $area_city = $warehouse_area['area_city'] ?? 0;

            $model_attr = Goods::where('goods_id', $goods_id)->value('model_attr');

            $attr['price'] = 0;

            if ($this->config['goods_attr_price'] == 1) {
                $attr_type_spec = '';
                //去掉复选属性by wu start
                foreach ($spec as $key => $val) {
                    $where_select = [
                        'goods_id' => $goods_id,
                        'goods_attr_id' => $val
                    ];

                    $goods_attr_info = $this->getGoodsAttrId($where_select, 0, 1);
                    $attr_type = $goods_attr_info['attr_type'] ?? 0;

                    if ($attr_type == 2 && $spec[$key]) {
                        $attr_type_spec .= $spec[$key] . ",";
                        unset($spec[$key]);
                    }
                }

                /* 复选价格 start */
                $attr_type_spec_price = 0;

                if ($attr_type_spec) {
                    $attr_type_spec_price = $this->getGoodsAttrPrice($goods_id, $model_attr, $attr_type_spec, $warehouse_id, $area_id, $area_city);
                }
                /* 复选价格 end */
                //去掉复选属性by wu end

                /* 判断是否存在货品信息 */
                if ($model_attr == 1) {
                    $price = ProductsWarehouse::where('goods_id', $goods_id)->where('warehouse_id', $warehouse_id);
                } elseif ($model_attr == 2) {
                    $price = ProductsArea::where('goods_id', $goods_id)->where('area_id', $area_id);

                    if ($this->config['area_pricetype'] == 1) {
                        $price = $price->where('city_id', $area_city);
                    }
                } else {
                    $price = Products::where('goods_id', $goods_id);
                }

                //获取货品信息
                foreach ($spec as $key => $val) {
                    $price = $price->whereRaw("FIND_IN_SET('$val', REPLACE(goods_attr, '|', ','))");
                }

                $price = $price->value('product_price');

                $price += $attr_type_spec_price;
            } else {
                $price = $this->getGoodsAttrPrice($goods_id, $model_attr, $spec, $warehouse_id, $area_id, $area_city);
            }
        } else {
            $price = 0;
        }

        return floatval($price);
    }

    /**
     * 获取单一属性价格
     */
    public function getGoodsAttrPrice($goods_id, $model_attr, $spec, $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        $spec = $spec && !is_array($spec) ? explode(",", $spec) : [];

        if ($model_attr == 1) { //仓库属性
            $price = WarehouseAttr::where('goods_id', $goods_id)->where('warehouse_id', $warehouse_id);
        } elseif ($model_attr == 2) { //地区属性
            $price = WarehouseAreaAttr::where('goods_id', $goods_id)->where('area_id', $area_id);

            if ($this->config['area_pricetype'] == 1) {
                $price = $price->where('city_id', $area_city);
            }
        } else {
            $price = GoodsAttr::where('goods_id', $goods_id);
        }

        if ($spec) {
            $price = $price->whereIn('goods_attr_id', $spec);
        }

        $price = $price->sum('attr_price');

        return $price;
    }

    /**
     * @param array $where_select 查询条件
     * @param int $attr_type 唯一属性、单选属性、复选属性
     * @param int $retuen_db 返回值模式（0-单条、1-单组、2-多组）
     * @return mixed
     */
    public function getGoodsAttrId($where_select = [], $attr_type = 0, $retuen_db = 1)
    {

        if (isset($where_select['goods_attr_id'])) {
            $where_select['goods_attr_id'] = $this->baseRepository->getExplode($where_select['goods_attr_id']);
        }

        if ($retuen_db == 2) {
            $res = Attribute::whereRaw(1);

            if ($attr_type) {
                if (is_array($attr_type)) {
                    $res = $res->whereIn('attr_type', $attr_type);
                } else {
                    $res = $res->where('attr_type', $attr_type);
                }
            }

            $res = $res->whereHas('getGoodsAttr', function ($query) use ($where_select) {
                if (isset($where_select['goods_id'])) {
                    $query = $query->where('goods_id', $where_select['goods_id']);
                }

                if (isset($where_select['attr_value']) && !empty($where_select['attr_value'])) {
                    $query = $query->where('attr_value', $where_select['attr_value']);
                }

                if (isset($where_select['attr_id']) && !empty($where_select['attr_id'])) {
                    $query = $query->where('attr_id', $where_select['attr_id']);
                }

                if (isset($where_select['goods_attr_id']) && !empty($where_select['goods_attr_id'])) {
                    $query = $query->whereIn('goods_attr_id', $where_select['goods_attr_id']);
                }

                if (isset($where_select['admin_id']) && !empty($where_select['admin_id'])) {
                    $query->where('admin_id', $where_select['admin_id']);
                }
            });

            $res = $res->with([
                'getGoodsAttrList' => function ($query) use ($where_select) {
                    if (isset($where_select['goods_id'])) {
                        $query = $query->where('goods_id', $where_select['goods_id']);
                    }

                    if (isset($where_select['attr_value']) && !empty($where_select['attr_value'])) {
                        $query = $query->where('attr_value', $where_select['attr_value']);
                    }

                    if (isset($where_select['attr_id']) && !empty($where_select['attr_id'])) {
                        $query = $query->where('attr_id', $where_select['attr_id']);
                    }

                    if (isset($where_select['goods_attr_id']) && !empty($where_select['goods_attr_id'])) {
                        $query = $query->whereIn('goods_attr_id', $where_select['goods_attr_id']);
                    }

                    if (isset($where_select['admin_id']) && !empty($where_select['admin_id'])) {
                        $query = $query->where('admin_id', $where_select['admin_id']);
                    }

                    $query->orderBy('goods_attr_id');
                }
            ]);

            $res = $res->orderByRaw('sort_order, attr_id asc');

            $res = $this->baseRepository->getToArrayGet($res);

            $list = [];
            if ($res) {
                foreach ($res as $key => $row) {
                    foreach ($row['get_goods_attr_list'] as $idx => $val) {
                        $goods_attr_id = $val['goods_attr_id'];

                        $list[$goods_attr_id] = $val;
                        $list[$goods_attr_id]['attr_img_flie'] = $this->dscRepository->getImagePath($val['attr_img_flie']);
                        $list[$goods_attr_id]['attr_gallery_flie'] = $this->dscRepository->getImagePath($val['attr_gallery_flie']);

                        $list[$goods_attr_id]['attr_id'] = $row['attr_id'];
                        $list[$goods_attr_id]['cat_id'] = $row['cat_id'];
                        $list[$goods_attr_id]['attr_name'] = $row['attr_name'];
                        $list[$goods_attr_id]['attr_cat_type'] = $row['attr_cat_type'];
                        $list[$goods_attr_id]['attr_input_type'] = $row['attr_input_type'];
                        $list[$goods_attr_id]['attr_type'] = $row['attr_type'];
                        $list[$goods_attr_id]['attr_values'] = $row['attr_values'];
                        $list[$goods_attr_id]['color_values'] = $row['color_values'];
                        $list[$goods_attr_id]['attr_index'] = $row['attr_index'];
                        $list[$goods_attr_id]['sort_order'] = $row['sort_order'];
                        $list[$goods_attr_id]['is_linked'] = $row['is_linked'];
                        $list[$goods_attr_id]['attr_group'] = $row['attr_group'];
                        $list[$goods_attr_id]['attr_input_category'] = $row['attr_input_category'];
                    }
                }
            }
        } elseif ($retuen_db == 1) {
            $res = GoodsAttr::whereRaw(1);

            if (isset($where_select['goods_id'])) {
                $res = $res->where('goods_id', $where_select['goods_id']);
            }

            if (isset($where_select['attr_value']) && !empty($where_select['attr_value'])) {
                $res = $res->where('attr_value', $where_select['attr_value']);
            }

            if (isset($where_select['attr_id']) && !empty($where_select['attr_id'])) {
                $res = $res->where('attr_id', $where_select['attr_id']);
            }

            if (isset($where_select['goods_attr_id']) && !empty($where_select['goods_attr_id'])) {
                $res = $res->whereIn('goods_attr_id', $where_select['goods_attr_id']);
            }

            if (isset($where_select['admin_id']) && !empty($where_select['admin_id'])) {
                $res = $res->where('admin_id', $where_select['admin_id']);
            }

            if ($attr_type) {
                $attr_type = $attr_type && !is_array($attr_type) ? explode(",", $attr_type) : $attr_type;

                $res = $res->whereHas('getGoodsAttribute', function ($query) use ($attr_type) {
                    if (is_array($attr_type)) {
                        $query->whereIn('attr_type', $attr_type);
                    } else {
                        $query->where('attr_type', $attr_type);
                    }
                });
            }

            $res = $res->with(['getGoodsAttribute']);

            $res = $this->baseRepository->getToArrayFirst($res);

            if ($res) {
                $attribute = $res['get_goods_attribute'];
                $res['attr_id'] = $attribute['attr_id'];
                $res['cat_id'] = $attribute['cat_id'];
                $res['attr_name'] = $attribute['attr_name'];
                $res['attr_cat_type'] = $attribute['attr_cat_type'];
                $res['attr_input_type'] = $attribute['attr_input_type'];
                $res['attr_type'] = $attribute['attr_type'];
                $res['attr_values'] = $attribute['attr_values'];
                $res['color_values'] = $attribute['color_values'];
                $res['attr_index'] = $attribute['attr_index'];
                $res['sort_order'] = $attribute['sort_order'];
                $res['is_linked'] = $attribute['is_linked'];
                $res['attr_group'] = $attribute['attr_group'];
                $res['attr_input_category'] = $attribute['attr_input_category'];
                $res['attr_img_flie'] = $this->dscRepository->getImagePath($res['attr_img_flie']);
                $res['attr_gallery_flie'] = $this->dscRepository->getImagePath($res['attr_gallery_flie']);
            }

            return $res;
        }
    }

    /**
     * 获取属性设置默认值(如果无后台设置默认属性则选择id小的属性作为默认checked)
     *
     * @param array $attr
     * @return array
     */
    public function get_attr_end_checked($attr = [])
    {
        $attr_str = [];
        if ($attr) {
            foreach ($attr as $z => $v) {
                $select_key = 0;
                foreach ($v['attr_key'] as $key => $val) {
                    if ($val['attr_checked'] == 1) {
                        $select_key = $key;
                        break;
                    }
                }
                //默认选择第一个属性为checked
                if ($select_key == 0) {
                    $attr[$z]['attr_key'][0]['attr_checked'] = 1;
                }

                $attr_str[] = $v['attr_key'][$select_key]['goods_attr_id'];
            }
            if ($attr_str) {
                sort($attr_str);
            }
        }
        return $attr_str;
    }

    /**
     * 获得商品的属性和规格
     *
     * @param $goods_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param string $goods_attr_id
     * @param int $attr_type
     * @param int $model_attr
     * @param int $is_img
     * @return mixed
     */
    public function getGoodsProperties($goods_id, $warehouse_id = 0, $area_id = 0, $area_city = 0, $goods_attr_id = '', $attr_type = 0, $model_attr = -1, $is_img = 1)
    {
        $attr_array = [];
        if (!empty($goods_attr_id)) {
            $attr_array = $this->baseRepository->getExplode($goods_attr_id);
        }

        if ($model_attr < 0) {
            $model_attr = Goods::where('goods_id', $goods_id)->value('model_attr');
        }

        /* 对属性进行重新排序和分组 */
        $grp = GoodsType::whereRaw(1);

        $grp = $grp->whereHas('getGoods', function ($query) use ($goods_id) {
            $query->where('goods_id', $goods_id);
        });

        $grp = $grp->value('attr_group');

        if (!empty($grp)) {
            $groups = preg_replace(['/\r\n/', '/\n/', '/\r/'], ",", $grp); //替换空格回车换行符为英文逗号
            $groups = explode(',', $groups);
        }

        $where = [
            'goods_id' => $goods_id,
            'attr_type' => $attr_type,
            'goods_attr_id' => $goods_attr_id,
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id
        ];
        $res = Attribute::whereHas('getGoodsAttr', function ($query) use ($where) {
            $query->where('goods_id', $where['goods_id']);

            if ($where['attr_type'] == 1 && !empty($where['goods_attr_id'])) {
                $where['goods_attr_id'] = $this->baseRepository->getExplode($where['goods_attr_id']);
                $query->whereIn('goods_attr_id', $where['goods_attr_id']);
            }
        });

        $res = $res->with([
            'getGoodsAttrList' => function ($query) use ($where) {
                $query->where('goods_id', $where['goods_id']);

                if ($where['attr_type'] == 1 && !empty($where['goods_attr_id'])) {
                    $where['goods_attr_id'] = $this->baseRepository->getExplode($where['goods_attr_id']);
                    $query->whereIn('goods_attr_id', $where['goods_attr_id']);
                }

                $query = $query->with([
                    'getGoodsWarehouseAttr' => function ($query) use ($where) {
                        $query->where('warehouse_id', $where['warehouse_id']);
                    },
                    'getGoodsWarehouseAreaAttr' => function ($query) use ($where) {
                        $query->where('area_id', $where['area_id']);
                    }
                ]);

                $query->orderByRaw('attr_sort, goods_attr_id ASC');
            }
        ]);

        $res = $res->orderByRaw('sort_order, attr_id ASC');

        $res = $this->baseRepository->getToArrayGet($res);

        $arr['pro'] = [];     // 属性
        $arr['spe'] = [];     // 规格
        $arr['lnk'] = [];     // 关联的属性

        if ($res) {
            foreach ($res as $key => $row) {
                $goods_attr_list = $row['get_goods_attr_list'];

                if ($row['attr_type'] == 0) {
                    /* 唯一属性 */
                    $group = (isset($groups[$row['attr_group']])) ? $groups[$row['attr_group']] : '';
                    $arr['pro'][$group][$row['attr_id']]['name'] = $row['attr_name'];
                    $arr['pro'][$group][$row['attr_id']]['value'] = $goods_attr_list[0]['attr_value'] ?? '';
                } else {
                    if ($goods_attr_list) {
                        foreach ($goods_attr_list as $idx => $val) {
                            $warehouse_attr = $val['get_goods_warehouse_attr'];
                            $area_attr = $val['get_goods_warehouse_area_attr'];

                            $attributeImg = AttributeImg::where('attr_id', $val['attr_id'])
                                ->where('attr_values', $val['attr_value']);
                            $attributeImg = $this->baseRepository->getToArrayFirst($attributeImg);

                            if ($val['attr_img_flie']) {
                                $goods_attr_list[$idx]['img_flie'] = $this->dscRepository->getImagePath($val['attr_img_flie']);
                            } else {
                                $goods_attr_list[$idx]['img_flie'] = $attributeImg ? $this->dscRepository->getImagePath($attributeImg['attr_img']) : '';
                            }

                            if ($val['attr_img_site']) {
                                $goods_attr_list[$idx]['img_site'] = $val['attr_img_site'];
                            } else {
                                $goods_attr_list[$idx]['attr_site'] = $attributeImg ? $attributeImg['attr_site'] : '';
                            }

                            $attr_price = $val['attr_price'];

                            if ($model_attr == 1) {
                                $attr_price = $warehouse_attr ? $warehouse_attr['warehouse_attr_price'] : 0;
                            } elseif ($model_attr == 2) {
                                $attr_price = $area_attr ? $area_attr['area_attr_price'] : 0;
                            }

                            $attr_price = abs($attr_price);

                            $goods_attr_list[$idx]['label'] = $val['attr_value'];
                            $goods_attr_list[$idx]['checked'] = $val['attr_checked'];
                            $goods_attr_list[$idx]['combo_checked'] = $this->commonRepository->getComboGodosAttr($attr_array, $val['goods_attr_id']);
                            $goods_attr_list[$idx]['price'] = $attr_price;
                            $goods_attr_list[$idx]['format_price'] = $this->dscRepository->getPriceFormat($attr_price, false);
                            $goods_attr_list[$idx]['id'] = $val['goods_attr_id'];
                        }
                    }

                    /* 单选、复选属性 */
                    $arr['spe'][$row['attr_id']]['attr_type'] = $row['attr_type'];
                    $arr['spe'][$row['attr_id']]['name'] = $row['attr_name'];
                    $arr['spe'][$row['attr_id']]['values'] = $goods_attr_list;

                    $arr['spe'][$row['attr_id']]['is_checked'] = $this->commonRepository->getAttrValues($goods_attr_list);
                }

                if ($row['is_linked'] == 1) {
                    /* 如果该属性需要关联，先保存下来 */
                    if (count($goods_attr_list) > 1) {
                        $arr['lnk'][$row['attr_id']]['name'] = $row['attr_name'];
                        foreach ($goods_attr_list as $lk => $lv) {
                            $arr['lnk'][$row['attr_id']]['value'][$lk] = $lv['attr_value'];
                        }
                    } else {
                        $arr['lnk'][$row['attr_id']]['name'] = $row['attr_name'];
                        $arr['lnk'][$row['attr_id']]['value'] = $goods_attr_list[0]['attr_value'] ?? '';
                    }
                }
            }
        }

        return $arr;
    }

    /**
     * 获得指定的商品属性
     *
     * @param string $goods_attr_id 规格、属性ID数组
     * @param string $type 设置返回结果类型：pice，显示价格，默认；no，不显示价格
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return mixed|string
     */
    public function getGoodsAttrInfo($goods_attr_id = '', $type = 'pice', $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        $attr = '';

        if (!empty($goods_attr_id)) {

            if ($type == 'pice') {
                $fmt = "%s:%s[%s] \n";
            } else {
                $fmt = "%s:%s \n";
            }

            $goods_attr_id = $this->baseRepository->getExplode($goods_attr_id);

            $res = GoodsAttr::whereIn('goods_attr_id', $goods_attr_id);

            $res = $res->whereHas('getGoods');

            $res = $res->whereHas('getGoodsAttribute');

            $res = $res->with([
                'getGoods' => function ($query) {
                    $query->select('goods_id', 'model_attr');
                },
                'getGoodsAttribute' => function ($query) {
                    $query->select('attr_id', 'attr_name', 'sort_order');
                }
            ]);

            $res = $res->get();

            $res = $res ? $res->toArray() : [];

            if ($res) {
                foreach ($res as $key => $row) {
                    $row = $row['get_goods'] ? array_merge($row, $row['get_goods']) : $row;
                    $row = $row['get_goods_attribute'] ? array_merge($row, $row['get_goods_attribute']) : $row;

                    if ($row['model_attr'] == 1) {
                        $attr_price = WarehouseAttr::where('goods_id', $row['goods_id'])
                            ->where('warehouse_id', $warehouse_id)
                            ->where('goods_attr_id', $row['goods_id'])->value('attr_price');
                    } elseif ($row['model_attr'] == 2) {
                        $attr_price = WarehouseAreaAttr::where('goods_id', $row['goods_id'])
                            ->where('area_id', $area_id);

                        if ($this->config['area_pricetype']) {
                            $attr_price = $attr_price->where('city_id', $area_city);
                        }

                        $attr_price = $attr_price->where('goods_attr_id', $row['goods_id'])
                            ->value('attr_price');
                    } else {
                        $attr_price = $row['attr_price'];
                    }

                    $row['attr_price'] = $attr_price ? $attr_price : 0;

                    $res[$key] = $row;
                }

                $res = $this->baseRepository->getSortBy($res, 'sort_order');

                foreach ($res as $row) {
                    if ($this->config['goods_attr_price'] == 1) {
                        $attr_price = 0;
                    } else {
                        $attr_price = round(floatval($row['attr_price']), 2);
                        $attr_price = $this->dscRepository->getPriceFormat($attr_price, false);
                    }

                    if ($type == 'pice') {
                        $attr .= sprintf($fmt, $row['attr_name'], $row['attr_value'], $attr_price);
                    } else {
                        $attr .= sprintf($fmt, $row['attr_name'], $row['attr_value']);
                    }
                }

                $attr = str_replace('[0]', '', $attr);
            }
        }

        return $attr;
    }
}
