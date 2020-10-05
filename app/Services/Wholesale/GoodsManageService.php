<?php

namespace App\Services\Wholesale;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\MerchantsShopInformation;
use App\Models\SuppliersGoodsGallery;
use App\Models\Wholesale;
use App\Models\WholesaleGoodsAttr;
use App\Models\WholesaleProducts;
use App\Models\WholesaleVolumePrice;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Common\CommonManageService;

/**
 * 后台批发商品管理服务
 *
 * Class GoodsManageService
 * @package App\Services\Wholesale
 */
class GoodsManageService
{
    protected $baseRepository;
    protected $timeRepository;
    protected $dscRepository;
    protected $categoryService;
    protected $commonManageService;

    public function __construct(
        BaseRepository $baseRepository,
        CategoryService $categoryService,
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        CommonManageService $commonManageService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->categoryService = $categoryService;
        $this->commonManageService = $commonManageService;
    }

    /**
     * 取得批发活动列表
     *
     * @param int $is_delete
     * @param array $seller
     * @return array
     */
    public function getWholesaleList($is_delete = 0, $seller = [])
    {
        $adminru = get_admin_ru_id();

        /* 过滤条件 */
        $day = $this->timeRepository->getLocalGetDate();
        $today = $this->timeRepository->getLocalMktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);

        $filter['suppliers_id'] = isset($_REQUEST['suppliers_id']) && !empty($_REQUEST['suppliers_id']) ? intval($_REQUEST['suppliers_id']) : 0;
        $filter['standard_goods'] = isset($_REQUEST['standard_goods']) && !empty($_REQUEST['standard_goods']) ? intval($_REQUEST['standard_goods']) : 0;
        $filter['cat_id'] = isset($_REQUEST['cat_id']) && !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;
        $filter['intro_type'] = isset($_REQUEST['intro_type']) && !empty($_REQUEST['intro_type']) ? trim($_REQUEST['intro_type']) : '';
        $filter['is_promote'] = isset($_REQUEST['is_promote']) && !empty($_REQUEST['is_promote']) ? intval($_REQUEST['is_promote']) : 0;
        $filter['stock_warning'] = isset($_REQUEST['stock_warning']) && !empty($_REQUEST['stock_warning']) ? intval($_REQUEST['stock_warning']) : 0;
        $filter['cat_type'] = isset($_REQUEST['cat_type']) && !empty($_REQUEST['cat_type']) ? addslashes($_REQUEST['cat_type']) : '';
        $filter['brand_id'] = isset($_REQUEST['brand_id']) && !empty($_REQUEST['brand_id']) ? intval($_REQUEST['brand_id']) : 0;
        $filter['brand_keyword'] = isset($_REQUEST['brand_keyword']) && !empty($_REQUEST['brand_keyword']) ? trim($_REQUEST['brand_keyword']) : '';
        $filter['keyword'] = isset($_REQUEST['keyword']) && !empty($_REQUEST['keyword']) ? trim($_REQUEST['keyword']) : '';
        $filter['is_on_sale'] = isset($_REQUEST['is_on_sale']) ? ((empty($_REQUEST['is_on_sale']) && $_REQUEST['is_on_sale'] === 0) ? '' : trim($_REQUEST['is_on_sale'])) : '';

        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }

        $filter['review_status'] = empty($_REQUEST['review_status']) ? 0 : intval($_REQUEST['review_status']); //ecmoban模板堂 --zhuo
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'goods_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['extension_code'] = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);
        $filter['is_delete'] = $is_delete;

        $res = Wholesale::selectRaw("*, IF(retail_price > 0, retail_price, goods_price) as shop_price, enabled as is_on_sale")
            ->where('is_delete', $filter['is_delete']);

        if ($filter['cat_id'] > 0) {
            $children = $this->categoryService->getWholesaleCatListChildren($filter['cat_id']);
            $res = $res->whereIn('cat_id', $children);
        }

        if ($adminru['suppliers_id'] > 0) {
            $res = $res->where('suppliers_id', $adminru['suppliers_id']);
        }

        if ($filter['brand_keyword']) {
            $brand = Brand::where('brand_name', 'like', '%' . $filter['brand_keyword'] . '%');
            $brand = $this->baseRepository->getToArrayGet($brand);
            $brand_id = $this->baseRepository->getKeyPluck($brand, 'brand_id');

            $res = $res->whereIn('brand_id', $brand_id);
        }

        if ($filter['brand_id']) {
            $res = $res->where('brand_id', $filter['brand_id']);
        }

        //管理员查询的权限 -- 店铺查询 start
        $filter['store_search'] = empty($_REQUEST['store_search']) ? 0 : intval($_REQUEST['store_search']);
        $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
        $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';

        if ($filter['store_search'] != 0) {
            if ($adminru['ru_id'] == 0) {
                if ($filter['store_search'] == 1) {
                    $res = $res->where('user_id', $filter['merchant_id']);
                } elseif ($filter['store_search'] > 1) {
                    $store_keyword = mysql_like_quote($filter['store_keyword']);

                    $shop = MerchantsShopInformation::whereRaw(1);

                    if ($filter['store_search'] == 2) {
                        $shop = $shop->where('rz_shopName', 'like', '%' . $store_keyword . '%');
                    } elseif ($filter['store_search'] == 3) {
                        $shop = $shop->where('shoprz_brandName', 'like', '%' . $store_keyword . '%');

                        if ($_REQUEST['store_type']) {
                            $shop = $shop->where('shopNameSuffix', $_REQUEST['store_type']);
                        }
                    }

                    $shop = $this->baseRepository->getToArrayGet($shop);
                    $user_id = $this->baseRepository->getKeyPluck($shop, 'user_id');

                    $res = $res->whereIn('user_id', $user_id);
                }
            }
        }
        //管理员查询的权限 -- 店铺查询 end

        /* 推荐类型 */
        switch ($filter['intro_type']) {
            case 'is_best':
                $res = $res->where('is_best', 1);
                break;
            case 'is_hot':
                $res = $res->where('is_hot', 1);
                break;
            case 'is_new':
                $res = $res->where('is_new', 1);
                break;
            case 'is_promote':
                $res = $res->where('is_promote', 1)
                    ->where('promote_price', '>', 0)
                    ->where('start_time', '<=', $today)
                    ->where('end_time', '>=', $today);
                break;
            case 'all_type':
                $res = $res->where(function ($query) use ($today) {
                    $query->where('is_best', 1)
                        ->orWhere('is_hot', 1)
                        ->orWhere('is_new', 1)
                        ->orWhere(function ($query) use ($today) {
                            $query->where('is_promote', 1)
                                ->where('promote_price', '>', 0)
                                ->where('start_time', '<=', $today)
                                ->where('end_time', '>=', $today);
                        });
                });
                break;
        }

        /* 库存警告 */
        if ($filter['stock_warning']) {
            $res = $res->whereRaw('goods_number <= warn_number');
        }

        /* 扩展 */
        if ($filter['extension_code']) {
            $res = $res->where('extension_code', $filter['extension_code']);
        }

        /* 关键字 */
        if (!empty($filter['keyword'])) {
            $keyword = mysql_like_quote($filter['keyword']);
            $res = $res->where(function ($query) use ($keyword) {
                $query->where('goods_sn', 'like', '%' . $keyword . '%')
                    ->orWhere('goods_name', 'like', '%' . $keyword . '%');
            });
        }

        /* 上架 */
        if ($filter['is_on_sale'] !== '' && $filter['is_on_sale'] !== '-1') {
            $res = $res->where('enabled', $filter['is_on_sale']);
        }

        /* 审核商品状态 ecmoban模板堂 --zhuo*/
        if ($filter['review_status'] > 0) {
            if ($filter['review_status'] == 3) {
                $res = $res->where('review_status', '>=', $filter['review_status']);
            } else {
                $res = $res->where('review_status', $filter['review_status']);
            }
        } else {
            $res = $res->where('review_status', '>', 0);
        }

        $res = $res->where('is_delete', $is_delete);

        if ($filter['suppliers_id'] > 0) {
            $res = $res->where('suppliers_id', $filter['suppliers_id']);
        }

        if ($filter['standard_goods'] == 1) {
            $res = $res->where('standard_goods', 1)
                ->where('review_status', 3);
            if ($adminru['ru_id'] > 0) {
                $res = $res->where('enabled', 1)
                    ->whereRaw("IF(export_type = 1 AND export_type_ext <> 'all' AND export_type_ext <> '', FIND_IN_SET('" . $seller['ru_id'] . "' ,export_type_ext), IF(export_type_ext <> 'all' AND export_type_ext <> '',FIND_IN_SET('" . $seller['grade_id'] . "',export_type_ext),1))");
            }
        }

        $list = $record_count = $res;

        /* 记录总数 */
        $filter['record_count'] = $record_count->count();

        $list = $list->with([
            'getSuppliers',
            'getWholesaleCat',
            'getWholesaleBrand',
            'getWholesaleExtend'
        ]);

        /* 分页大小 */
        $filter = page_and_size($filter);

        $list = $list->orderBy($filter['sort_by'], $filter['sort_order']);

        $list = $list->skip($filter['start'])
            ->take($filter['page_size']);

        $list = $this->baseRepository->getToArrayGet($list);

        if ($list) {
            foreach ($list as $key => $row) {
                $brand = $row['get_wholesale_brand'];
                $list[$key]['brand_name'] = $brand['brand_name'] ?? '';

                //商品扩展信息
                $list[$key]['goods_extend'] = $row['get_wholesale_extend'] ?? [];

                //处理商品链接
                $list[$key]['url'] = $this->dscRepository->buildUri('wholesale_goods', ['gid' => $row['goods_id']], $row['goods_name']);

                //补充处理 by wu
                $list[$key]['formated_shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                $list[$key]['formated_add_tim'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $row['add_time']);

                if ($row['freight'] == 2) {
                    $list[$key]['transport'] = get_goods_transport_info($row['tid'], 'suppliers_goods_transport');
                }

                //图片显示
                $list[$key]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);

                $suppliers = $row['get_suppliers'];
                $list[$key]['suppliers_name'] = $suppliers['suppliers_name'] ?? '';

                $cat = $row['get_wholesale_cat'];
                $list[$key]['lib_cat_name'] = $cat['cat_name'] ?? '';
            }
        }

        return array('goods' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
    }

    /**
     * 保存某商品的优惠价格
     *
     * @param int $goods_id 商品编号
     * @param array $number_list 优惠数量列表
     * @param array $price_list 价格列表
     * @return  void
     */
    public function handleWholesaleVolumePrice($goods_id = 0, $is_volume = 0, $number_list = [], $price_list = [], $id_list = [])
    {
        if ($is_volume) {
            /* 循环处理每个优惠价格 */
            foreach ($price_list as $key => $price) {
                /* 价格对应的数量上下限 */
                $volume_number = $number_list[$key];
                $volume_id = isset($id_list[$key]) && !empty($id_list[$key]) ? intval($id_list[$key]) : 0;
                if (!empty($price)) {
                    $where = [
                        'volume_price' => $price,
                        'volume_number' => $volume_number
                    ];

                    $count = WholesaleVolumePrice::where('goods_id', $goods_id)
                        ->where(function ($query) use ($where) {
                            $query->where('volume_price', $where['volume_price'])
                                ->orWhere('volume_number', $where['volume_number']);
                        })->count();

                    if ($volume_id) {
                        if ($count > 0) {
                            WholesaleVolumePrice::where('id', $volume_id)->update([
                                'volume_number' => $volume_number,
                                'volume_price' => $price
                            ]);
                        }
                    } else {
                        if ($count <= 0) {
                            WholesaleVolumePrice::insert([
                                'price_type' => 1,
                                'goods_id' => $goods_id,
                                'volume_number' => $volume_number,
                                'volume_price' => $price
                            ]);
                        }
                    }
                }
            }
        } else {
            WholesaleVolumePrice::where('price_type', 1)
                ->where('goods_id', $goods_id)
                ->delete();
        }
    }

    /**
     * 取得商品优惠价格列表
     *
     * @param string $goods_id 商品编号
     * @param string $price_type 价格类别(0为全店优惠比率，1为商品优惠价格，2为分类优惠比率)
     *
     * @return  优惠价格列表
     */
    public function getWholesaleVolumePriceList($goods_id, $price_type = '1')
    {
        $res = WholesaleVolumePrice::where('goods_id', $goods_id)
            ->where('price_type', $price_type)
            ->orderBy('volume_number');

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $k => $v) {
                $res[$k]['id'] = $v['id'];
                $res[$k]['number'] = $v['volume_number'];
                $res[$k]['price'] = $v['volume_price'];
                $res[$k]['format_price'] = $this->dscRepository->getPriceFormat($v['volume_price']);
            }
        }

        return $res;
    }

    /**
     * 批发商品属性
     *
     * @param int $goods_type
     * @param int $goods_id
     * @param int $goods_model
     * @return mixed
     */
    public function setWholesaleGoodsAttribute($goods_type = 0, $goods_id = 0, $goods_model = 0)
    {
        //获取属性列表
        $attribute_list = Attribute::where('cat_id', $goods_type)
            ->where('cat_id', '<>', 0)
            ->where('attr_type', '<>', 2)
            ->orderByRaw('sort_order, attr_type, attr_id asc');
        $attribute_list = $this->baseRepository->getToArrayGet($attribute_list);

        //获取商品属性
        $attr_list = WholesaleGoodsAttr::where('goods_id', $goods_id)
            ->orderByRaw('attr_sort, goods_attr_id asc');
        $attr_list = $this->baseRepository->getToArrayGet($attr_list);

        if ($attribute_list) {
            foreach ($attribute_list as $key => $val) {
                $is_selected = 0; //属性是否被选择
                $this_value = ""; //唯一属性的值

                if ($val['attr_type'] > 0) {
                    if ($val['attr_values']) {
                        $attr_values = preg_replace(['/\r\n/', '/\n/', '/\r/'], ",", $val['attr_values']); //替换空格回车换行符为英文逗号
                        $attr_values = explode(',', $attr_values);
                    } else {
                        $attr_values = WholesaleGoodsAttr::where('goods_id', $goods_id)
                            ->where('attr_id', $val['attr_id'])
                            ->orderByRaw('attr_sort, goods_attr_id asc');
                        $attr_values = $this->baseRepository->getToArrayGet($attr_values);
                        $values_list = $this->baseRepository->getKeyPluck($attr_values, 'attr_value');

                        $attribute_list[$key]['attr_values'] = $values_list;
                        $attr_values = $attribute_list[$key]['attr_values'];
                    }

                    $attr_values_arr = array();
                    if ($attr_values) {
                        for ($i = 0; $i < count($attr_values); $i++) {
                            $goods_attr = WholesaleGoodsAttr::where('goods_id', $goods_id)
                                ->where('attr_value', $attr_values[$i])
                                ->where('attr_id', $val['attr_id']);
                            $goods_attr = $this->baseRepository->getToArrayFirst($goods_attr);

                            $attr_values_arr[$i] = [
                                'is_selected' => 0,
                                'goods_attr_id' => $goods_attr['goods_attr_id'] ?? 0,
                                'attr_value' => $attr_values[$i],
                                'attr_price' => $goods_attr['attr_price'] ?? 0,
                                'attr_sort' => $goods_attr['attr_sort'] ?? 0
                            ];
                        }
                    }

                    $attribute_list[$key]['attr_values_arr'] = $attr_values_arr;
                }

                if ($attr_list) {
                    foreach ($attr_list as $k => $v) {
                        if ($val['attr_id'] == $v['attr_id']) {
                            $is_selected = 1;
                            if ($val['attr_type'] == 0) {
                                $this_value = $v['attr_value'];
                            } else {
                                foreach ($attribute_list[$key]['attr_values_arr'] as $a => $b) {
                                    if ($goods_id) {
                                        if ($b['attr_value'] == $v['attr_value']) {
                                            $attribute_list[$key]['attr_values_arr'][$a]['is_selected'] = 1;
                                        }
                                    } else {
                                        if ($b['attr_value'] == $v['attr_value']) {
                                            $attribute_list[$key]['attr_values_arr'][$a]['is_selected'] = 1;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                $attribute_list[$key]['is_selected'] = $is_selected;
                $attribute_list[$key]['this_value'] = $this_value;
                if ($val['attr_input_type'] == 1) {
                    $attr_values = preg_replace(['/\r\n/', '/\n/', '/\r/'], ",", $val['attr_values']); //替换空格回车换行符为英文逗号
                    $attribute_list[$key]['attr_values'] = explode(',', $attr_values);
                }
            }
        }


        $attribute_list = $this->commonManageService->getNewGoodsAttr($attribute_list);

        $GLOBALS['smarty']->assign('goods_id', $goods_id);
        $GLOBALS['smarty']->assign('goods_model', $goods_model);

        $GLOBALS['smarty']->assign('attribute_list', $attribute_list);
        $goods_attribute = $GLOBALS['smarty']->fetch('library/goods_attribute.lbi');

        $attr_spec = $attribute_list['spec'];

        if ($attr_spec) {
            $arr['is_spec'] = 1;
        } else {
            $arr['is_spec'] = 0;
        }

        $GLOBALS['smarty']->assign('attr_spec', $attr_spec);
        $GLOBALS['smarty']->assign('goods_attr_price', $GLOBALS['_CFG']['goods_attr_price']);
        $goods_attr_gallery = $GLOBALS['smarty']->fetch('library/goods_attr_gallery.lbi');

        $arr['goods_attribute'] = $goods_attribute;
        $arr['goods_attr_gallery'] = $goods_attr_gallery;

        return $arr;
    }

    /**
     * 单条数据
     * 获取商品属性ID
     * goods_attr_id
     * $where 查询条件=
     * $attr_type 唯一属性、单选属性、复选属性
     * $retuen_db 返回值模式（0-单条、1-单组、2-多组）
     */
    public function getWholesaleGoodsAttrId($where = [], $attr_type = 0, $retuen_db = 0)
    {
        $res = WholesaleGoodsAttr::whereRaw(1);

        if (isset($where['goods_id'])) {
            $res = $res->where('goods_id', $where['goods_id']);
        }

        if (isset($where['attr_value']) && !empty($where['attr_value'])) {
            $res = $res->where('attr_value', $where['attr_value']);
        }

        if (isset($where['attr_id']) && !empty($where['attr_id'])) {
            $res = $res->where('attr_id', $where['attr_id']);
        }

        if (isset($where['goods_attr_id']) && !empty($where['goods_attr_id'])) {
            $res = $res->where('goods_attr_id', $where['goods_attr_id']);
        }

        if (isset($where['admin_id']) && !empty($where['admin_id'])) {
            $res = $res->where('admin_id', $where['admin_id']);
        }

        if ($attr_type && is_array($attr_type)) {
            $attr_type = $this->baseRepository->getExplode($attr_type);

            $res = $res->whereHas('getGoodsAttribute', function ($query) use ($attr_type) {
                $query->where('attr_type', $attr_type);
            });
        } else {
            if ($attr_type) {
                $res = $res->whereHas('getGoodsAttribute', function ($query) use ($attr_type) {
                    $query->where('attr_type', $attr_type);
                });
            }
        }

        $res = $res->with(['getGoodsAttribute']);

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
            }
        }

        $res = $this->baseRepository->getSortBy($res, 'attr_sort');

        if ($retuen_db == 1) {
            $res = $res ? $res[0] : [];

            return $res;
        } else {
            $goods_attr_id = $res ? $res[0]['goods_attr_id'] : 0;

            return $goods_attr_id;
        }
    }

    /**
     * 通过一组属性获取货品的相关信息
     *
     * @param int $goods_id
     * @param array $attr_arr
     * @param int $goods_model
     * @param int $region_id
     * @return bool
     */
    public function getWholesaleProductInfoByAttr($goods_id = 0, $attr_arr = [])
    {
        $product_info = [];
        if (!empty($attr_arr)) {
            $where = array('goods_id' => $goods_id);

            if (empty($goods_id)) {
                $admin_id = get_admin_id();
                $where['admin_id'] = $admin_id;
            }

            //获取属性组合
            $attr = array();
            foreach ($attr_arr as $key => $val) {
                $where['attr_value'] = $val;
                $goods_attr_id = $this->getWholesaleGoodsAttrId($where, 1);

                if ($goods_attr_id) {
                    $attr[] = $goods_attr_id;
                }
            }

            $product_info = WholesaleProducts::where('goods_id', $goods_id);

            if ($attr) {
                foreach ($attr as $key => $val) {
                    $product_info = $product_info->whereRaw("FIND_IN_SET('$val', REPLACE(goods_attr, '|', ','))");
                }
            }

            $product_info = $this->baseRepository->getToArrayFirst($product_info);
        }

        return $product_info;
    }

    /**
     * 取货品信息
     *
     * @access  public
     * @param int $product_id 货品id
     * @param int $filed 字段
     * @param int $is_attr 属性组
     * @return  array
     */
    public function getWholesaleProductInfo($product_id, $is_attr = 0)
    {
        $return_array = array();

        if (empty($product_id)) {
            return $return_array;
        }

        $return_array = WholesaleProducts::where('product_id', $product_id);
        $return_array = $this->baseRepository->getToArrayGet($return_array);

        if ($is_attr == 1) {
            if ($return_array['goods_attr']) {
                $goods_attr_id = str_replace("|", ",", $return_array['goods_attr']);
                $return_array['goods_attr'] = $this->getWholesaleProductAttrList($goods_attr_id, $return_array['goods_id']);
            }
        }

        return $return_array;
    }

    /**
     * 批发商品属性列表
     *
     * @param int $goods_attr_id
     * @param int $goods_id
     * @return array
     */
    public function getWholesaleProductAttrList($goods_attr_id = 0, $goods_id = 0)
    {
        $goods_attr_id = $this->baseRepository->getExplode($goods_attr_id);

        $where = [
            'goods_attr_id' => $goods_attr_id,
            'goods_id' => $goods_id
        ];
        $res = Attribute::whereHas('getGoodsAttr', function ($query) use ($where) {
            if ($where['goods_attr_id']) {
                $query = $query->whereIn('goods_attr_id', $where['goods_attr_id']);
            }

            $query->where('goods_id', $where['goods_id']);
        });

        $res = $res->with([
            'getGoodsAttrList' => function ($query) use ($where) {
                if ($where['goods_attr_id']) {
                    $query = $query->whereIn('goods_attr_id', $where['goods_attr_id']);
                }

                $query->where('goods_id', $where['goods_id'])
                    ->orderBy('goods_attr_id');
            }
        ]);
        $res = $res->orderBy('sort_order', 'attr_id');

        $arr = [];
        if ($res) {
            foreach ($res as $key => $row) {
                foreach ($row['get_goods_attr_list'] as $idx => $val) {
                    $arr[] = $val;
                }
            }
        }

        return $arr;
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
    public function handleWholesaleGoodsAttr($goods_id, $id_list, $is_spec_list, $value_price_list)
    {
        $goods_attr_id = [];

        /* 循环处理每个属性 */
        if ($id_list) {
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
                $result_id = WholesaleGoodsAttr::where('goods_id', $goods_id)
                    ->where('attr_id', $id)
                    ->where('attr_value', $value)
                    ->value('goods_attr_id');

                $ids = 0;
                if (!empty($result_id)) {
                    WholesaleGoodsAttr::where('goods_id', $goods_id)
                        ->where('attr_id', $id)
                        ->where('goods_attr_id', $result_id)
                        ->update([
                            'attr_value' => $value
                        ]);

                    $goods_attr_id[$id] = $result_id;
                } else {
                    $ids = WholesaleGoodsAttr::insertGetId([
                        'goods_id' => $goods_id,
                        'attr_id' => $id,
                        'attr_value' => $value,
                        'attr_price' => $price
                    ]);
                }

                if ($goods_attr_id[$id] == '') {
                    $goods_attr_id[$id] = $ids;
                }
            }
        }

        return $goods_attr_id;
    }

    /**
     * 将 goods_attr_id 的序列按照 attr_id 重新排序
     *
     * 注意：非规格属性的id会被排除
     *
     * @access      public
     * @param array $goods_attr_id_array 一维数组
     * @param string $sort 序号：asc|desc，默认为：asc
     *
     * @return      string
     */
    public function sortWholesaleGoodsAttrIdArray($goods_attr_id_array = [], $sort = 'asc')
    {
        if (empty($goods_attr_id_array)) {
            return $goods_attr_id_array;
        }

        $goods_attr_id_array = $this->baseRepository->getExplode($goods_attr_id_array);

        $row = Attribute::where('attr_type', 1);

        $row = $row->whereHas('getWholesaleGoodsAttr', function ($query) use ($goods_attr_id_array) {
            $query->whereIn('goods_attr_id', $goods_attr_id_array);
        });

        $row = $row->with([
            'getWholesaleGoodsAttrList' => function ($query) use ($goods_attr_id_array) {
                $query->whereIn('goods_attr_id', $goods_attr_id_array)
                    ->orderBy('goods_attr_id');
            }
        ]);

        $row = $row->orderByRaw('sort_order, attr_id ' . $sort);

        $row = $this->baseRepository->getToArrayGet($row);

        $list = [];
        if ($row) {
            foreach ($row as $key => $value) {
                if ($value['get_wholesale_goods_attr_list']) {
                    foreach ($value['get_wholesale_goods_attr_list'] as $idx => $val) {
                        $val['attr_type'] = $value['attr_type'];

                        $list['sort'][] = $val['goods_attr_id'];
                        $list['row'][$val['goods_attr_id']] = $val;
                    }
                }
            }
        }

        return $list;
    }

    /**
     * 商品的货品规格是否存在
     *
     * @param string $goods_attr 商品的货品规格
     * @param string $goods_id 商品id
     * @param int $product_id 商品的货品id；默认值为：0，没有货品id
     * @return  bool                          true，重复；false，不重复
     */
    public function checkWholesaleGoodsAttrExist($goods_attr, $goods_id, $product_id = 0)
    {
        $goods_id = intval($goods_id);
        if (strlen($goods_attr) == 0 || empty($goods_id)) {
            return true;    //重复
        }

        if (empty($product_id)) {
            $product_id = WholesaleProducts::where('goods_attr', $goods_attr)
                ->where('goods_id', $goods_id)
                ->value('product_id');
        } else {
            $product_id = WholesaleProducts::where('goods_attr', $goods_attr)
                ->where('goods_id', $goods_id)
                ->where('product_id', '<>', $product_id)
                ->value('product_id');
        }

        $product_id = $product_id ? $product_id : 0;

        if (empty($product_id)) {
            return false;    //不重复
        } else {
            return true;    //重复
        }
    }

    /**
     * 查询当前商品属性
     *
     * @param int $attr_id
     * @param int $goods_id
     * @return mixed
     */
    public function dialogWholesaleGoodsAttrType($attr_id = 0, $goods_id = 0)
    {
        $res = WholesaleGoodsAttr::where('attr_id', $attr_id)
            ->where('goods_id', $goods_id)
            ->orderBy('attr_sort');

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $row) {
                if ($goods_id) {
                    $res[$key]['is_selected'] = 1;
                } else {
                    $res[$key]['is_selected'] = 0;
                }
            }
        }

        return $res;
    }

    /**
     * 获得商品已添加的规格列表
     *
     * @param int $goods_id
     * @return array
     */
    public function wholesaleGoodsSpecificationsList($goods_id = 0)
    {
        $admin_id = get_admin_id();
        if (empty($goods_id)) {
            if (!$admin_id) {
                return array();  //$goods_id不能为空
            }
        }

        $where = [
            'goods_id' => $goods_id,
            'admin_id' => $admin_id
        ];
        $res = Attribute::where('attr_type', 1);
        $res = $res->whereHas('getWholesaleGoodsAttr', function ($query) use ($where) {
            $query = $query->where('goods_id', $where['goods_id']);

            if (empty($where['goods_id'])) {
                $query->where('goods_id', $where['admin_id']);
            }
        });

        $res = $res->with([
            'getWholesaleGoodsAttrList' => function ($query) use ($where) {
                $query = $query->where('goods_id', $where['goods_id']);

                if (empty($where['goods_id'])) {
                    $query = $query->where('goods_id', $where['admin_id']);
                }

                $query->orderBy('goods_attr_id');
            }
        ]);

        $res = $res->orderByRaw('sort_order, attr_id asc');

        $list = [];
        if ($res) {
            foreach ($res as $key => $row) {
                if ($row['get_wholesale_goods_attr_list']) {
                    foreach ($row['get_wholesale_goods_attr_list'] as $idx => $val) {
                        $val['attr_name'] = $row['attr_name'];

                        $list[] = $val;
                    }
                }
            }
        }

        return $list;
    }

    /**
     * 批量添加货号(默认模式)
     *
     * @param array $goods_list 数据列表
     * @param int $attr_num 属性个数
     * @return array
     */
    public function getWholesaleProdutsList2($goods_list = [], $attr_num = 0)
    {
        $arr = [];
        if ($goods_list) {
            for ($i = 0; $i < count($goods_list); $i++) {
                $goods_where = array(
                    'id' => $goods_list[$i]['goods_id'],
                    'name' => $goods_list[$i]['goods_name'],
                    'sn_name' => $goods_list[$i]['goods_sn'],
                    'seller_id' => $goods_list[$i]['seller_id'],
                );

                $arr[$i]['goods_id'] = get_products_name($goods_where, 'goods');
                $arr[$i]['warehouse_id'] = 0;

                for ($j = 0; $j < $attr_num; $j++) {
                    if ($j == $attr_num - 1) {
                        $attr_name[$j] = $goods_list[$i]['goods_attr' . $j]; //属性名称;

                        $attr[$j] = WholesaleGoodsAttr::where('attr_value', $goods_list[$i]['goods_attr' . $j])
                            ->where('goods_id', $arr[$i]['goods_id'])
                            ->value('goods_attr_id');
                        $attr[$j] = $attr[$j] ? $attr[$j] : '';
                    } else {
                        $attr_name[$j] = !empty($goods_list[$i]['goods_attr' . $j]) ? $goods_list[$i]['goods_attr' . $j] . '|' : ''; //属性名称;

                        $attr[$j] = WholesaleGoodsAttr::where('attr_value', $goods_list[$i]['goods_attr' . $j])
                            ->where('goods_id', $arr[$i]['goods_id'])
                            ->value('goods_attr_id');
                        $attr[$j] = $attr[$j] ? $attr[$j] . '|' : '';
                    }
                }

                $arr[$i]['goods_attr'] = implode('', $attr); //拼凑属性ID;
                $arr[$i]['goods_attr_name'] = implode('', $attr_name); //拼凑属性名称;

                $arr[$i]['product_number'] = $goods_list[$i]['product_number'];

                //如果货品编号为空,自动生成货品编号;
                if (empty($goods_list[$i]['product_sn'])) {
                    $arr[$i]['product_sn'] = $goods_list[$i]['goods_sn'] . 'g_p' . $i;
                } else {
                    $arr[$i]['product_sn'] = $goods_list[$i]['product_sn'];
                }
            }
        }

        return $arr;
    }


    /**
     * 从回收站删除多个商品
     * @param mix $goods_id 商品id列表：可以逗号格开，也可以是数组
     * @return  void
     */
    public function deleteGoods($goods_id = 0)
    {
        if (empty($goods_id)) {
            return;
        }

        /* 取得有效商品id */
        $goods_id = $this->baseRepository->getExplode($goods_id);
        $wholesale = Wholesale::whereIn('goods_id', $goods_id)
            ->where('is_delete', 1);
        $wholesale = $this->baseRepository->getToArrayGet($wholesale);
        $goods_id = $this->baseRepository->getKeyPluck($wholesale, 'goods_id');

        if (empty($goods_id)) {
            return;
        }

        /* 删除商品图片和轮播图片文件 */
        if ($wholesale) {
            $goods_thumb = $this->baseRepository->getKeyPluck($wholesale, 'goods_thumb');
            $goods_img = $this->baseRepository->getKeyPluck($wholesale, 'goods_img');
            $original_img = $this->baseRepository->getKeyPluck($wholesale, 'original_img');

            $img = [
                $goods_thumb,
                $goods_img,
                $original_img
            ];

            $img = $this->baseRepository->getFlatten($img);

            $this->dscRepository->getOssDelFile($img);

            dsc_unlink($img, storage_public());
        }

        /* 删除商品 */
        Wholesale::whereIn('goods_id', $goods_id)->delete();

        /* 删除商品的货品记录 */
        WholesaleProducts::whereIn('goods_id', $goods_id)->delete();

        /* 删除商品相册的图片文件 */
        $res = SuppliersGoodsGallery::whereIn('goods_id', $goods_id);
        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            $img_url = $this->baseRepository->getKeyPluck($wholesale, 'img_url');
            $thumb_url = $this->baseRepository->getKeyPluck($wholesale, 'thumb_url');
            $img_original = $this->baseRepository->getKeyPluck($wholesale, 'img_original');

            $img = [
                $img_url,
                $thumb_url,
                $img_original
            ];

            $img = $this->baseRepository->getFlatten($img);

            $this->dscRepository->getOssDelFile($img);

            dsc_unlink($img, storage_public());
        }

        /* 删除商品相册 */
        SuppliersGoodsGallery::whereIn('goods_id', $goods_id)->delete();

        /* 删除相关表记录 */
        WholesaleGoodsAttr::whereIn('goods_id', $goods_id)->delete();

        /* 清除缓存 */
        clear_cache_files();
    }

    /**
     * 批发商品的货品货号是否重复
     *
     * @param string $product_sn 商品的货品货号；请在传入本参数前对本参数进行SQl脚本过滤
     * @param int $product_id 商品的货品id；默认值为：0，没有货品id
     * @return  bool                          true，重复；false，不重复
     */
    public function checkWholsaleProductSnExist($product_sn, $product_id = 0, $ru_id = 0)
    {
        $product_sn = trim($product_sn);
        $product_id = intval($product_id);
        if (strlen($product_sn) == 0) {
            return true;    //重复
        }

        $prod = WholesaleProducts::where('product_sn', $product_sn)
            ->where('admin_id', $ru_id);
        $prod = $prod->count();

        if ($prod) {
            return true;    //重复
        }

        $res = WholesaleProducts::where('product_sn', $product_sn)
            ->where('admin_id', $ru_id);

        if (!empty($product_id)) {
            $res = $res->where('product_id', '<>', $product_id);
        }

        $res = $res->whereHas('getWholesale', function ($query) use ($ru_id) {
            $query->where('suppliers_id', $ru_id);
        });

        $count = $res->count();

        if ($count > 0) {
            return true;    //重复
        } else {
            return false;    //不重复
        }
    }

    /**
     * 修改商品某字段值
     *
     * @param $goods_id
     * @param $other
     * @param int $suppliers_id
     * @return bool
     */
    public function updateWholesaleGoods($goods_id, $other, $suppliers_id = 0)
    {
        if ($goods_id) {
            $goods_id = $this->baseRepository->getExplode($goods_id);

            $last_update = $this->timeRepository->getGmTime();
            $other['last_update'] = $last_update;

            $res = Wholesale::whereIn('goods_id', $goods_id);

            if ($suppliers_id) {
                $res = $res->where('suppliers_id', $suppliers_id);
            }

            $res->update($other);
        } else {
            return false;
        }
    }
}
