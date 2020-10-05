<?php

namespace App\Services\Goods;

use App\Models\Goods;
use App\Models\GoodsAttr;
use App\Models\ProductsWarehouse;
use App\Models\RegionWarehouse;
use App\Models\WarehouseAreaGoods;
use App\Models\WarehouseGoods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\TimeRepository;

class GoodsWarehouseAttrManageService
{
    protected $baseRepository;
    protected $timeRepository;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
    }


    /**
     * 返回用户列表数据
     *
     * @param int $goods_id
     * @return array
     */
    public function warehouseProductList($goods_id = 0)
    {
        /* 过滤条件 */
        $filter['keywords'] = $_REQUEST['keywords'] ?? '';
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'region_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $res = RegionWarehouse::where('region_type', 0);
        if ($filter['keywords']) {
            $res = $res->where('region_name', 'LIKE', '%' . mysql_like_quote($filter['keywords']) . '%');
        }
        $filter['record_count'] = $res->count();

        /* 分页大小 */
        $filter = page_and_size($filter);
        $res = $res->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])->limit($filter['page_size']);

        $warehouse_list = $this->baseRepository->getToArrayGet($res);

        $count = count($warehouse_list);
        for ($i = 0; $i < $count; $i++) {
            $warehouse_list[$i]['region_name'] = $warehouse_list[$i]['region_name'];

            $warehouse_list[$i]['attr_num'] = ProductsWarehouse::where('goods_id', $goods_id)
                ->where('warehouse_id', $warehouse_list[$i]['region_id'])
                ->count();
        }

        $arr = ['warehouse_list' => $warehouse_list, 'filter' => $filter,
            'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 获得商品已添加的规格列表
     *
     * @access      public
     * @params      integer         $goods_id
     * @return      array
     */
    public function getGoodsSpecificationsList($goods_id)
    {
        $res = GoodsAttr::whereRaw(1);
        $admin_id = get_admin_id();
        if (empty($goods_id)) {
            if ($admin_id) {
                $res = $res->where('admin_id', $admin_id);
            } else {
                return [];  //$goods_id不能为空
            }
        }
        $res = $res->where('goods_id', $goods_id);
        $res = $res->with(['getGoodsAttribute' => function ($query) {
            $query->select('attr_id', 'attr_name')
                ->where('attr_type', 1)
                ->orderBy('sort_order')
                ->orderBy('attr_id');
        }]);
        $res = $res->orderBy('goods_attr_id');
        $results = $this->baseRepository->getToArrayGet($res);
        foreach ($results as $key => $value) {
            $value['attr_name'] = '';
            if (isset($value['get_goods_attribute']) && !empty($value['get_goods_attribute'])) {
                $value['attr_name'] = $value['get_goods_attribute']['attr_name'];
            }
            $results[$key] = $value;
        }
        return $results;
    }

    /**
     * 获得商品的货品列表
     *
     * @param int $goods_id
     * @param int $warehouse_id
     * @return array
     */
    public function productWarehouseList($goods_id = 0, $warehouse_id = 0)
    {
        /* 过滤条件 */
        $filter['goods_id'] = $goods_id;
        $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);

        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'product_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['page_count'] = isset($filter['page_count']) ? $filter['page_count'] : 1;

        $res = ProductsWarehouse::whereRaw(1);

        /* 关键字 */
        if (!empty($filter['keyword'])) {
            $keyword = $filter['keyword'];
            $res = $res->where(function ($query) use ($keyword) {
                $query->where('product_sn', 'LIKE', '%' . $keyword . '%');
            });
        }

        $res = $res->where('warehouse_id', $warehouse_id);

        /* 记录总数 */
        $res = $res->where('goods_id', $goods_id);
        $filter['record_count'] = $res->count();

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);
        $row = $this->baseRepository->getToArrayGet($res);
        /* 处理规格属性 */
        $goods_attr = $this->productGoodsAttrList($goods_id);
        foreach ($row as $key => $value) {
            $_goods_attr_array = explode('|', $value['goods_attr']);
            if (is_array($_goods_attr_array)) {
                $_temp = '';
                foreach ($_goods_attr_array as $_goods_attr_value) {
                    $_temp[] = $goods_attr[$_goods_attr_value];
                }
                $row[$key]['goods_attr'] = $_temp;
            }
        }

        return ['product' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    /**
     * 获得商品的规格属性值列表
     *
     * @access      public
     * @params      integer         $goods_id
     * @return      array
     */
    public function productGoodsAttrList($goods_id)
    {
        if (empty($goods_id)) {
            return [];  //$goods_id不能为空
        }

        $res = GoodsAttr::where('goods_id', $goods_id);
        $results = $this->baseRepository->getToArrayGet($res);

        $return_arr = [];
        foreach ($results as $value) {
            $return_arr[$value['goods_attr_id']] = $value['attr_value'];
        }

        return $return_arr;
    }

    /**
     * 取货品信息
     *
     * @access  public
     * @param int $product_id 货品id
     * @param int $filed 字段
     * @return  array
     */
    public function getProductWarehouseInfo($product_id, $filed = '')
    {
        $return_array = [];

        if (empty($product_id)) {
            return $return_array;
        }

        $filed = trim($filed);
        if (empty($filed)) {
            $filed = '*';
        }

        $res = ProductsWarehouse::where('product_id', $product_id);
        $return_array = $this->baseRepository->getToArrayFirst($res);
        return $return_array;
    }

    /**
     * 商品的货品货号是否重复
     *
     * @param string $product_sn 商品的货品货号；请在传入本参数前对本参数进行SQl脚本过滤
     * @param int $product_id 商品的货品id；默认值为：0，没有货品id
     * @return  bool                          true，重复；false，不重复
     */
    public function checkProductWarehouseSnExist($product_sn, $product_id = 0, $ru_id = 0, $type = 0)
    {
        $product_sn = trim($product_sn);
        $product_id = intval($product_id);
        if (strlen($product_sn) == 0) {
            return true;    //重复
        }

        if ($type == 1) {
            $res = Goods::where('bar_code', $product_sn)->where('user_id', $ru_id)->count();
            if ($res > 0) {
                return true;    //重复
            }
        } else {
            $res = Goods::where('goods_sn', $product_sn)->where('user_id', $ru_id)->count();
            if ($res > 0) {
                return true;    //重复
            }
        }

        $res = ProductsWarehouse::whereRaw(1);
        if (empty($product_id)) {
            if ($type == 1) {
                $res = $res->where('bar_code', $product_sn);
            } else {
                $res = $res->where('product_sn', $product_sn);
            }
        } else {
            if ($type == 1) {
                $res = $res->where('bar_code', $product_sn)->where('product_id', '<>', $product_id);
            } else {
                $res = $res->where('product_sn', $product_sn)->where('product_id', '<>', $product_id);
            }
        }

        $res = $res->where(function ($query) use ($ru_id) {
            $query->whereHas('getGoods', function ($query) use ($ru_id) {
                $query->where('user_id', $ru_id);
            });
        });

        $res = $res->count();

        if (empty($res)) {
            return false;    //不重复
        } else {
            return true;    //重复
        }
    }

    /**
     * 获得商品的货品总库存
     *
     * @access      public
     * @params      integer     $goods_id       商品id
     * @params      string      $conditions     sql条件，AND语句开头
     * @return      string number
     */
    public function productWarehouseNumberCount($goods_id, $conditions = '', $warehouse_id = 0)
    {
        if (empty($goods_id)) {
            return -1;  //$goods_id不能为空
        }

        $nums = ProductsWarehouse::where('goods_id', $goods_id)->where('warehouse_id', $warehouse_id)->value('product_number');
        $nums = empty($nums) ? 0 : $nums;

        return $nums;
    }

    /**
     * 修改商品某字段值
     * @param string $goods_id 商品编号，可以为多个，用 ',' 隔开
     * @param string $field 字段名
     * @param string $value 字段值
     * @return  bool
     */
    public function updateWarehouseGoods($goods_id, $field, $value)
    {
        if ($goods_id) {
            /* 清除缓存 */
            clear_cache_files();

            $model_attr = Goods::where('goods_id', $goods_id)->value('model_attr');
            $model_attr = $model_attr ? $model_attr : 0;

            if ($model_attr == 1) {
                $field = 'region_number';
                $res = WarehouseGoods::whereRaw(1);
            } elseif ($model_attr == 2) {
                $field = 'region_number';
                $res = WarehouseAreaGoods::whereRaw(1);
            } else {
                $res = Goods::whereRaw(1);
            }

            $goods_id = $this->baseRepository->getExplode($goods_id);
            $data = [
                $field => $value,
                'last_update' => $this->timeRepository->getGmTime()
            ];
            $res = $res->whereIn('goods_id', $goods_id)->update($data);

            return $res;
        } else {
            return false;
        }
    }

    /**
     * 插入或更新商品属性
     *
     * @param int $goods_id 商品编号
     * @param array $id_list 属性编号数组
     * @param array $is_spec_list 是否规格数组 'true' | 'false'
     * @param array $value_price_list 属性值数组
     * @return  array                       返回受到影响的goods_attr_id数组
     */
    public function handleGoodsAttr($goods_id, $id_list, $is_spec_list, $value_price_list)
    {
        $goods_attr_id = [];

        /* 循环处理每个属性 */
        foreach ($id_list as $key => $id) {
            $is_spec = $is_spec_list[$key];
            if ($is_spec == 'false') {
                $value = $value_price_list[$key];
                $price = '';
            } else {
                $value_list = [];
                $price_list = [];
                if ($value_price_list[$key]) {
                    $vp_list = explode(chr(13), $value_price_list[$key]);
                    foreach ($vp_list as $v_p) {
                        $arr = explode(chr(9), $v_p);
                        $value_list[] = $arr[0];
                        $price_list[] = $arr[1];
                    }
                }
                $value = join(chr(13), $value_list);
                $price = join(chr(13), $price_list);
            }

            // 插入或更新记录
            $result_id = GoodsAttr::where('goods_id', $goods_id)
                ->where('attr_id', $id)
                ->where('attr_value', $value)
                ->value('goods_attr_id');
            $result_id = $result_id ? $result_id : 0;
            if (!empty($result_id)) {
                $data = ['attr_value' => $value];
                GoodsAttr::where('goods_id', $goods_id)
                    ->where('attr_id', $id)
                    ->where('goods_attr_id', $result_id)
                    ->update($data);
                $goods_attr_id[$id] = $result_id;
            } else {
                $data = [
                    'goods_id' => $goods_id,
                    'attr_id' => $id,
                    'attr_value' => $value,
                    'attr_price' => $price
                ];
                $insert_id = GoodsAttr::insert($data);
            }

            if ($goods_attr_id[$id] == '') {
                $goods_attr_id[$id] = $insert_id;
            }
        }

        return $goods_attr_id;
    }

    /**
     * 商品的货品规格是否存在
     *
     * @param string $goods_attr 商品的货品规格
     * @param string $goods_id 商品id
     * @param int $product_id 商品的货品id；默认值为：0，没有货品id
     * @return  bool                          true，重复；false，不重复
     */
    public function checkGoodsAttrExist($goods_attr, $goods_id, $product_id = 0, $warehouse_id = 0)
    {
        $goods_id = intval($goods_id);
        if (strlen($goods_attr) == 0 || empty($goods_id)) {
            return true;    //重复
        }
        $res = ProductsWarehouse::whereRaw(1);
        if (empty($product_id)) {
            $res = $res->where('goods_attr', $goods_attr)
                ->where('goods_id', $goods_id)
                ->where('warehouse_id', $warehouse_id);
        } else {
            $res = $res->where('goods_attr', $goods_attr)
                ->where('goods_id', $goods_id)
                ->where('product_id', '<>', $product_id)
                ->where('warehouse_id', $warehouse_id);
        }

        $res = $res->count();

        if (empty($res)) {
            return false;    //不重复
        } else {
            return true;    //重复
        }
    }

    /**
     * 商品货号是否重复
     *
     * @param string $goods_sn 商品货号；请在传入本参数前对本参数进行SQl脚本过滤
     * @param int $goods_id 商品id；默认值为：0，没有商品id
     * @return  bool                        true，重复；false，不重复
     */
    public function checkGoodsSnExist($goods_sn, $goods_id = 0)
    {
        $goods_sn = trim($goods_sn);
        $goods_id = intval($goods_id);
        if (strlen($goods_sn) == 0) {
            return true;    //重复
        }

        $res = Goods::whereRaw(1);
        if (empty($goods_id)) {
            $res = $res->where('goods_sn', $goods_sn);
        } else {
            $res = $res->where('goods_sn', $goods_sn)
                ->where('goods_id', '<>', $goods_id);
        }

        $res = $res->count();

        if (empty($res)) {
            return false;    //不重复
        } else {
            return true;    //重复
        }
    }

    /**
     * 商品的货品货号是否重复
     *
     * @param string $product_sn 商品的货品货号；请在传入本参数前对本参数进行SQl脚本过滤
     * @param int $product_id 商品的货品id；默认值为：0，没有货品id
     * @return  bool                          true，重复；false，不重复
     */
    public function checkProductSnExist($product_sn, $product_id = 0)
    {
        $product_sn = trim($product_sn);
        $product_id = intval($product_id);
        if (strlen($product_sn) == 0) {
            return true;    //重复
        }
        $res = Goods::where('goods_sn', $product_sn)->count();
        if ($res > 0) {
            return true;    //重复
        }

        $res = ProductsWarehouse::whereRaw(1);
        if (empty($product_id)) {
            $res = $res->where('product_sn', $product_sn);
        } else {
            $res = $res->where('product_sn', $product_sn)
                ->where('product_id', '<>', $product_id);
        }

        $res = $res->count();

        if (empty($res)) {
            return false;    //不重复
        } else {
            return true;    //重复
        }
    }
}
