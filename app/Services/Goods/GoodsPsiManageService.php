<?php

namespace App\Services\Goods;

use App\Models\Goods;
use App\Models\GoodsAttr;
use App\Models\GoodsInventoryLogs;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Models\RegionWarehouse;
use App\Models\Suppliers;
use App\Models\WarehouseAreaGoods;
use App\Models\WarehouseGoods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Category\CategoryService;
use App\Services\Merchant\MerchantCommonService;

class GoodsPsiManageService
{
    protected $baseRepository;
    protected $merchantCommonService;
    protected $categoryService;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        MerchantCommonService $merchantCommonService,
        CategoryService $categoryService,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->categoryService = $categoryService;
        $this->dscRepository = $dscRepository;
    }


    /**
     * 获取选中的入库商品
     *
     * @access  public
     * @param integer $goods_id
     * @return  array
     */
    public function getSelectPurchaseGoods($goods_ids = '')
    {
        $region_id = empty($_REQUEST['region_id']) ? 0 : intval($_REQUEST['region_id']);
        $area_id = empty($_REQUEST['area_id']) ? 0 : intval($_REQUEST['area_id']);
        $city_id = empty($_REQUEST['city_id']) ? 0 : intval($_REQUEST['city_id']);

        $res = Goods::whereRaw(1);
        if (!empty($goods_ids)) {
            $goods_ids = $this->baseRepository->getExplode($goods_ids);
            $res = $res->whereIn('goods_id', $goods_ids);
        }

        /* 获活动数据 */
        $row = $this->baseRepository->getToArrayGet($res);

        foreach ($row as $key => $val) {
            $goods = get_goods_model($val['goods_id']);
            if ($region_id && $goods['model_attr']) {
                if ($goods['model_attr'] == 1) {//仓库模式
                    $num = WarehouseGoods::where('region_id', $region_id)
                        ->where('goods_id', $val['goods_id'])
                        ->value('region_number');
                    $num = $num ? $num : 0;
                    $row[$key]['goods_number'] = $num;
                } elseif ($goods['model_attr'] == 2) {//地区模式
                    if (empty($area_id)) {
                        $goods_id = $val['goods_id'];
                        $res = RegionWarehouse::where('parent_id', $region_id)->where('region_type', 1);
                        $res = $res->whereHas('getWarehouseAreaGoods', function ($query) use ($goods_id) {
                            $query->where('goods_id', $goods_id);
                        });
                        $area_id = $res->value('region_id');
                        $area_id = $area_id ? $area_id : 0;

                    }

                    $num = WarehouseAreaGoods::where('region_id', $area_id)
                        ->where('goods_id', $val['goods_id'])
                        ->value('region_number');
                    $num = $num ? $num : 0;
                    $row[$key]['goods_number'] = $num;

                    $goods_id = $val['goods_id'];
                    $res = RegionWarehouse::where('parent_id', $region_id)->where('region_type', 1);
                    $res = $res->whereHas('getWarehouseAreaGoods', function ($query) use ($goods_id) {
                        $query->where('goods_id', $goods_id);
                    });
                    $res = $res->orderBy('region_id');
                    $area_list = $this->baseRepository->getToArrayGet($res);

                    if ($area_list) {
                        $row[$key]['area_list'] = $area_list;
                        $row[$key]['select_area'] = true;
                        $row[$key]['area_id'] = $area_id;
                    }
                }
            }

            $row[$key]['goods_thumb'] = $this->dscRepository->getImagePath($val['goods_thumb']);
            $row[$key]['product_list'] = get_goods_product_list($val['goods_id'], $goods['model_attr'], $region_id, $area_id, $city_id, false);// false 不分页
            //货品行数
            $rowspan = count($row[$key]['product_list']);
            $row[$key]['rowspan'] = empty($rowspan) ? 1 : $rowspan;
        }

        return $row;
    }

    /*获取库存日志*/
    public function getPsiLog()
    {
        /* 过滤查询 */
        $filter = [];

        //ecmoban模板堂 --zhuo start
        $filter['keyword'] = !empty($_REQUEST['keyword']) ? trim($_REQUEST['keyword']) : '';
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        //ecmoban模板堂 --zhuo end
        $filter['goods_id'] = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $res = GoodsInventoryLogs::whereRaw(1);
        /* 关键字 */
        if (!empty($filter['keyword'])) {
            $keyword = mysql_like_quote($filter['keyword']);
            $res = $res->where(function ($query) use ($keyword) {
                $query->where('batch_number', 'LIKE', '%' . $keyword . '%');
            });
        }

        if (!empty($filter['goods_id'])) {
            $res = $res->where('goods_id', $filter['goods_id']);
        }
        $res = $res->whereHas('getGoods');
        /* 获得总记录数据 */
        $filter['record_count'] = $res->count();

        $filter = page_and_size($filter);

        /* 获得记录数据 */
        $arr = [];
        $res = $res->with(['getGoods' => function ($query) {
            $query->select('goods_id', 'goods_name', 'goods_type', 'goods_number');
        }]);
        $res = $res->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])
            ->limit($filter['page_size']);
        $res = $this->baseRepository->getToArrayGet($res);
        $idx = 0;
        foreach ($res as $rows) {
            $rows['goods_name'] = '';
            $rows['goods_type'] = 0;
            $rows['goods_number'] = 0;
            if (isset($rows['get_goods']) && !empty($rows['get_goods'])) {
                $rows['goods_name'] = $rows['get_goods']['goods_name'];
                $rows['goods_type'] = $rows['get_goods']['goods_type'];
                $rows['goods_number'] = $rows['get_goods']['goods_number'];
            }
            //判断模式
            if (!empty($rows['area_id'])) {
                $region_id = $rows['area_id'];
                $goods_res = WarehouseAreaGoods::whereRaw(1);
                $products_res = ProductsArea::whereRaw(1);
            } elseif (!empty($rows['warehouse_id'])) {
                $region_id = $rows['warehouse_id'];
                $goods_res = WarehouseGoods::whereRaw(1);
                $products_res = ProductsWarehouse::whereRaw(1);
            } else {
                $region_id = 0;
                $goods_res = Goods::whereRaw(1);
                $products_res = Products::whereRaw(1);
            }
            //商品总数
            $rows['total_number'] = 0;
            if (!empty($rows['product_id'])) {
                //货品数据
                $product_info = $products_res->where('product_id', $rows['product_id']);
                $product_info = $this->baseRepository->getToArrayFirst($product_info);
                if (!empty($product_info)) {
                    $rows['total_number'] = $product_info['product_number'];
                    $goods_attr = explode('|', $product_info['goods_attr']);

                    $goods_attr = $this->baseRepository->getExplode($goods_attr);
                    $attr_value = GoodsAttr::select('attr_value')->whereIn('goods_attr_id', $goods_attr);
                    $rows['goods_attr'] = $this->baseRepository->getToArrayGet($attr_value);
                    $rows['goods_attr'] = $this->baseRepository->getFlatten($rows['goods_attr']);
                }
            } else {
                //商品总数
                if (empty($region_id)) {
                    $rows['total_number'] = $rows['goods_number'];
                } else {
                    $rows['total_number'] = $goods_res->where('goods_id', $rows['goods_id'])
                        ->where('region_id', $region_id)
                        ->value('region_number');
                    $rows['total_number'] = $rows['total_number'] ? $rows['total_number'] : 0;
                }
            }
            //仓库名称
            $rows['warehouse_name'] = RegionWarehouse::where('region_id', $rows['warehouse_id'])->value('region_name');
            $rows['warehouse_name'] = $rows['warehouse_name'] ? $rows['warehouse_name'] : '';
            //地区名称
            $rows['area_name'] = RegionWarehouse::where('region_id', $rows['area_id'])->value('region_name');
            $rows['area_name'] = $rows['area_name'] ? $rows['area_name'] : '';
            //供货商名称
            $rows['suppliers_name'] = Suppliers::where('suppliers_id', $rows['suppliers_id'])->value('suppliers_name');
            $rows['suppliers_name'] = $rows['suppliers_name'] ? $rows['suppliers_name'] : '';

            $arr[$idx] = $rows;
            $idx++;
        }

        return ['list' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }


    /*获取商品列表*/
    public function getPsiInventory()
    {
        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();

        //ecmoban模板堂 --zhuo end

        /* 过滤查询 */
        $filter = [];

        //ecmoban模板堂 --zhuo start
        $filter['keyword'] = !empty($_REQUEST['keyword']) ? trim($_REQUEST['keyword']) : '';
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        //ecmoban模板堂 --zhuo end

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'goods_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $res = Goods::whereRaw(1);

        /* 关键字 */
        if (!empty($filter['keyword'])) {
            $keyword = mysql_like_quote($filter['keyword']);
            $res = $res->where(function ($query) use ($keyword) {
                $query->where('goods_name', 'LIKE', '%' . $keyword . '%');
            });
        }

        //管理员查询的权限 -- 店铺查询 start
        $filter['store_search'] = !isset($_REQUEST['store_search']) ? -1 : intval($_REQUEST['store_search']);
        $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
        $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';

        if ($filter['store_search'] > -1) {
            if ($adminru['ru_id'] == 0) {
                if ($filter['store_search'] > 0) {
                    $store_type = isset($_REQUEST['store_type']) && !empty($_REQUEST['store_type']) ? intval($_REQUEST['store_type']) : 0;

                    if ($filter['store_search'] == 1) {
                        $res = $res->where('user_id', $filter['merchant_id']);
                    }

                    if ($filter['store_search'] > 1 && $filter['store_search'] != 4) {
                        $res = $res->where(function ($query) use ($filter, $store_type) {
                            $query->whereHas('getShopInfo', function ($query) use ($filter, $store_type) {
                                if ($filter['store_search'] == 2) {
                                    $query = $query->where('rz_shopName', 'LIKE', '%' . mysql_like_quote($filter['store_keyword']) . '%');
                                } elseif ($filter['store_search'] == 3) {
                                    if ($store_type) {
                                        $query = $query->where('shopNameSuffix', $store_type);
                                    }
                                    $query = $query->where('shoprz_brandName', 'LIKE', '%' . mysql_like_quote($filter['store_keyword']) . '%');
                                }
                            });
                        });

                    } else {
                        if ($filter['store_search'] == 4) {
                            $res = $res->where('user_id', 0);
                        }
                    }
                } else {
                    $res = $res->where('user_id', 0);
                }
            }
        }
        //管理员查询的权限 -- 店铺查询 end
        if ($adminru['ru_id'] > 0) {
            $res = $res->where('user_id', $adminru['ru_id']);
        }

        /* 获得总记录数据 */
        $filter['record_count'] = $res->count();

        $filter = page_and_size($filter);

        /* 获得商品数据 */
        $arr = [];
        $res = $res->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])->limit($filter['page_size']);
        $res = $this->baseRepository->getToArrayGet($res);

        $idx = 0;
        foreach ($res as $rows) {
            //判断模式
            if ($rows['model_inventory'] == 2) {
                $goods_res = WarehouseAreaGoods::whereRaw(1);
                $products_res = ProductsArea::whereRaw(1);
            } elseif ($rows['model_inventory'] == 1) {
                $goods_res = WarehouseGoods::whereRaw(1);
                $products_res = ProductsWarehouse::whereRaw(1);
            } else {
                $goods_res = Goods::whereRaw(1);
                $products_res = Products::whereRaw(1);
            }
            //其他数据
            $rows['user_name'] = $this->merchantCommonService->getShopName($rows['user_id'], 1);
            $rows['goods_thumb'] = $this->dscRepository->getImagePath($rows['goods_thumb']);
            //处理货品
            $rows['goods_take_number'] = 0;
            $rows['total_number'] = 0;
            if (!empty($rows['goods_type'])) {
                //货品总数
                $total_number = $products_res->where('goods_id', $rows['goods_id'])->sum('product_number');
                $rows['total_number'] = empty($total_number) ? 0 : $total_number;
            } else {
                //商品总数
                if (empty($rows['model_inventory'])) {
                    $rows['total_number'] = $rows['goods_number'];
                } else {
                    $rows['total_number'] = $goods_res->where('goods_id', $rows['goods_id'])->sum('region_number');
                    $rows['total_number'] = $rows['total_number'] ? $rows['total_number'] : 0;
                }
            }

            $arr[$idx] = $rows;
            $idx++;
        }

        return ['list' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    public function getPisGoodsList($where = [])
    {
        $where['goods_ids'] = isset($where['goods_ids']) ? $this->baseRepository->getExplode($where['goods_ids']) : $where['goods_ids'];

        $row = Goods::where('is_delete', 0)
            ->where('is_on_sale', 1);

        if ($GLOBALS['_CFG']['review_goods'] == 1) {
            $row = $row->where('review_status', '>', 2);
        }

        if (isset($where['cat_id']) && isset($where['cat_id'][0]) && $where['cat_id'][0] > 0) {
            $children = $this->categoryService->getCatListChildren($where['cat_id'][0]);

            if ($children) {
                $row = $row->whereIn('cat_id', $children);
            }
        }

        if (isset($where['cat_id']) && $where['brand_id'] > 0) {
            $row = $row->where('brand_id', $where['brand_id']);
        }

        if (isset($where['cat_id']) && $where['keyword']) {
            $row = $row->where('goods_name', 'like', '%' . $where['keyword'] . '%');
        }

        if (isset($where['goods_ids']) && isset($where['type']) && $where['goods_ids'] && $where['type'] == '0') {
            $row = $row->whereIn('goods_id', $where['goods_ids']);
        }

        if (isset($where['region']) && $where['region']) {
            $row = $row->where('model_price', '>', 0);
        } else {
            $row = $row->where('model_price', 0);
        }

        $res = $record_count = $row;

        if (isset($where['is_page']) && $where['is_page'] == 1) {
            $filter['record_count'] = $record_count->count();

            $filter = page_and_size($filter);
        }

        switch (isset($where['sort_order']) && $where['sort_order']) {
            case 1:
                $res = $res->orderBy("add_time");
                break;

            case 2:
                $res = $res->orderBy("add_time", 'desc');
                break;

            case 3:
                $res = $res->orderBy("sort_order");
                break;

            case 4:
                $res = $res->orderBy("sort_order", 'desc');
                break;

            case 5:
                $res = $res->orderBy("goods_name");
                break;

            case 6:
                $res = $res->orderBy("goods_name", 'desc');
                break;
            default:
                $res = $res->orderByRaw("sort_order, sales_volume desc");
                break;
        }

        if (isset($where['is_page']) && $where['is_page'] == 1) {
            if ($filter['start'] > 0) {
                $res = $res->skip($filter['start']);
            }

            if ($filter['page_size'] > 0) {
                $res = $res->take($filter['page_size']);
            }
        }

        $res = $this->baseRepository->getToArrayGet($res);

        if (isset($where['is_page']) && $where['is_page'] == 1) {
            $filter['page_arr'] = seller_page($filter, $filter['page']);
            return ['list' => $res, 'filter' => $filter];
        } else {
            return $res;
        }
    }

    /**
     *  供应商列表
     */
    public function psiSuppliersList()
    {
        /* 查询 */
        $res = Suppliers::whereRaw(1);
        $res = $this->baseRepository->getToArrayGet($res);

        return $res;
    }


}
