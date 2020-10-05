<?php

namespace App\Services\Wholesale;

use App\Models\Attribute;
use App\Models\GoodsType;
use App\Models\SellerShopinfo;
use App\Models\Suppliers;
use App\Models\SuppliersGoodsGallery;
use App\Models\Wholesale;
use App\Models\WholesaleCat;
use App\Models\WholesaleExtend;
use App\Models\WholesaleGoodsAttr;
use App\Models\WholesaleProducts;
use App\Models\WholesaleVolumePrice;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsAttributeImgService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Merchant\MerchantCommonService;

class GoodsService
{
    protected $baseRepository;
    protected $dscRepository;
    protected $categoryService;
    protected $orderService;
    protected $config;
    protected $commonRepository;
    protected $goodsAttrService;
    protected $merchantCommonService;
    protected $goodsAttributeImgService;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        CategoryService $categoryService,
        OrderService $orderService,
        CommonRepository $commonRepository,
        GoodsAttrService $goodsAttrService,
        MerchantCommonService $merchantCommonService,
        GoodsAttributeImgService $goodsAttributeImgService
    )
    {
        load_helper(['suppliers']);
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->categoryService = $categoryService;
        $this->orderService = $orderService;
        $this->config = $this->dscRepository->dscConfig();
        $this->commonRepository = $commonRepository;
        $this->goodsAttrService = $goodsAttrService;
        $this->merchantCommonService = $merchantCommonService;
        $this->goodsAttributeImgService = $goodsAttributeImgService;
    }

    /**
     * 获得所有批发下级分类属于指定分类的所有商品ID
     *
     * @param int $cat_id
     * @return array
     */
    public function getWholesaleExtensionGoods($children = [])
    {
        $goods_id = [];
        if ($children) {
            $res = Wholesale::whereIn('cat_id', $children)
                ->where('is_delete', 0);
            $res = $this->baseRepository->getToArrayGet($res);
            $goods_id = $this->baseRepository->getKeyPluck($res, 'goods_id');
        }

        return $goods_id;
    }

    /**
     * 获得批发分类商品
     *
     * @param int $parent_id
     * @return array
     */
    public function getWholesaleCatGoods($parent_id = 0)
    {
        $res = WholesaleCat::where('parent_id', $parent_id)
            ->orderBy('sort_order');

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $row) {
                $goods = $this->getBusinessGoods($row['cat_id']);

                $res[$key]['goods'] = $goods;
                $res[$key]['count_goods'] = count($goods);
                $res[$key]['cat_url'] = $this->dscRepository->buildUri('wholesale_cat', array('act' => 'list', 'cid' => $row['cat_id']), $row['cat_name']);
            }
        }

        return $res;
    }

    /**
     * 获取分类下批发商品，并且进行分组
     *
     * @param int $cat_id
     * @return mixed
     */
    public function getBusinessGoods($cat_id = 0)
    {
        $children = $this->categoryService->getWholesaleCatListChildren($cat_id);
        $extension_goods = $this->getWholesaleExtensionGoods($children);

        $res = Wholesale::from('wholesale as who')
            ->leftjoin('suppliers as su', 'who.suppliers_id', '=', 'su.suppliers_id')
            ->where('su.review_status', 3)
            ->where('who.enabled', 1)
            ->where('who.is_delete', 0)
            ->where('who.review_status', 3);

        if ($children) {
            $res = $res->whereIn('who.cat_id', $children);
        }

        if ($extension_goods) {
            $res = $res->whereIn('who.goods_id', $extension_goods);
        }

        $res = $res->with([
            'getWholesaleVolumePriceList',
            'getWholesaleExtend'
        ]);

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $row) {
                if ($row['get_wholesale_volume_price_list']) {
                    $row['volume_number'] = $this->baseRepository->getArrayMin($row['get_wholesale_volume_price_list'], 'volume_number');
                    $row['volume_price'] = $this->baseRepository->getArrayMax($row['get_wholesale_volume_price_list'], 'volume_price');
                } else {
                    $row['volume_number'] = 0;
                    $row['volume_price'] = 0;
                }

                $res[$key]['goods_extend'] = $row['get_wholesale_extend'];
                $res[$key]['volume_number'] = $row['volume_number'];
                $res[$key]['volume_price'] = $row['volume_price'];
                $res[$key]['goods_sale'] = $this->orderService->getGoodsOrderSale($row['goods_id']);
                $res[$key]['goods_price'] = $row['goods_price'];
                $res[$key]['moq'] = $row['moq'];
                $res[$key]['volume_number'] = $row['volume_number'];
                $res[$key]['volume_price'] = $row['volume_price'];
                $res[$key]['goods_unit'] = $row['goods_unit'] ? $row['goods_unit'] : '个';
                $res[$key]['goods_name'] = $row['goods_name'];
                $res[$key]['thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $res[$key]['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
                $res[$key]['url'] = $this->dscRepository->buildUri('wholesale_goods', array('aid' => $row['goods_id']), $row['goods_name']);
            }
        }

        return $res;
    }

    /**
     * 取得某页的批发商品
     * @param int $size 每页记录数
     * @param int $page 当前页
     * @param string $where 查询条件
     * @return  array
     */
    public function getWholesaleList($cat_id = 0, $size = 10, $page = 1, $sort, $order)
    {
        $res = Wholesale::where('enabled', 1)
            ->where('is_delete', 0)
            ->where('review_status', 3);

        if ($cat_id) {
            $children = $this->categoryService->getWholesaleCatListChildren($cat_id);
            $extension_goods = $this->getWholesaleExtensionGoods($children);

            if ($children) {
                $res = $res->whereIn('cat_id', $children);
            }

            if ($extension_goods) {
                $res = $res->whereIn('goods_id', $extension_goods);
            }
        }

        $res = $res->with([
            'getWholesaleVolumePriceList',
            'getWholesaleExtend',
            'getSuppliers'
        ]);

        $res = $res->orderBy($sort, $order);

        $start = ($page - 1) * $size;
        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        $list = [];
        if ($res) {
            foreach ($res as $key => $row) {
                $suppliers = $row['get_suppliers'];

                if ($row['get_wholesale_volume_price_list']) {
                    $row['volume_number'] = $this->baseRepository->getArrayMin($row['get_wholesale_volume_price_list'], 'volume_number');
                    $row['volume_price'] = $this->baseRepository->getArrayMax($row['get_wholesale_volume_price_list'], 'volume_price');
                } else {
                    $row['volume_number'] = 0;
                    $row['volume_price'] = 0;
                }

                $row['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']); //处理图片地址

                /*  判断当前商家是否允许"在线客服" start  */
                $shop_information = $this->merchantCommonService->getShopName($suppliers['user_id']); //通过ru_id获取到店铺信息;
                //$row['is_IM'] = $shop_information['is_IM'] ?? 0; //平台是否允许商家使用"在线客服";

                //判断当前商家是平台,还是入驻商家 bylu
                if ($suppliers['user_id'] == 0) {
                    $kf_im_switch = SellerShopinfo::where('ru_id', 0)->value('kf_im_switch');

                    //判断平台是否开启了IM在线客服
                    if ($kf_im_switch) {
                        $row['is_dsc'] = true;
                    } else {
                        $row['is_dsc'] = false;
                    }
                } else {
                    $row['is_dsc'] = false;
                }
                /* end  */

                $row['goods_url'] = $this->dscRepository->buildUri('wholesale_goods', array('aid' => $row['goods_id']), $row['goods_name']);

                $properties = $this->goodsAttrService->getGoodsProperties($row['goods_id']);
                $row['goods_attr'] = $properties['pro'];
                $row['goods_sale'] = $this->orderService->getGoodsOrderSale($row['goods_id']);
                $row['goods_extend'] = $row['get_wholesale_extend']; //获取批发商品标识
                $row['goods_price'] = $row['goods_price'];
                $row['moq'] = $row['moq'];
                $row['volume_number'] = $row['volume_number'];
                $row['volume_price'] = $row['volume_price'];
                $row['rz_shopName'] = $suppliers['suppliers_name'] ?? ''; //供应商名称
                $row['suppliers_url'] = $this->dscRepository->buildUri('wholesale_suppliers', array('sid' => $suppliers['suppliers_id']));
                $kf_qq = get_suppliers_kf($suppliers['suppliers_id']);  // 供应商获取配置客服QQ
                if ($kf_qq) {
                    $row['kf_qq'] = $kf_qq['kf_qq'];
                }
                $build_uri = array(
                    'urid' => $suppliers['user_id'],
                    'append' => $row['rz_shopName']
                );

                $domain_url = $this->merchantCommonService->getSellerDomainUrl($suppliers['user_id'], $build_uri);
                $row['store_url'] = $domain_url['domain_name'];
                $row['shop_price'] = $this->dscRepository->getPriceFormat($row['goods_price']);
                $list[] = $row;
            }
        }

        return $list;
    }

    /**
     * 供应商信息
     * @param int $suppliers_id 供应商id
     * @return  array
     */
    public function getSupplierHome($suppliers_id = 0)
    {
        $suppliers = Suppliers::where('suppliers_id', $suppliers_id);
        $row = $this->baseRepository->getToArrayFirst($suppliers);

        if (!empty($row)) {
            if ($row['kf_qq']) {
                $kf_qq = array_filter(preg_split('/\s+/', $row['kf_qq']));
                foreach ($kf_qq as $k => $v) {
                    $row['kf_qq_all'][] = explode("|", $v);
                }
                $kf_qq = $kf_qq && $kf_qq[0] ? explode("|", $kf_qq[0]) : [];
                if (isset($kf_qq[1]) && !empty($kf_qq[1])) {
                    $row['kf_qq'] = $kf_qq[1];
                } else {
                    $row['kf_qq'] = "";
                }
            } else {
                $row['kf_qq'] = "";
            }
            $row['suppliers_logo'] = $this->dscRepository->getImagePath($row['suppliers_logo']); //处理图片地址

            $shop_information = $this->merchantCommonService->getShopName($row['user_id']); //通过ru_id获取到店铺信息;
            $row['rz_shopName'] = $shop_information['shop_name'] ?? ''; //店铺名称
        }

        return $row;
    }


    /**
     * 供应商家商品列表
     * @param int $size 每页记录数
     * @param int $page 当前页
     * @param string $where 查询条件
     * @return  array
     */
    public function getSuppliersList($suppliers_id = 0, $size = 10, $page = 1, $sort, $order)
    {
        $res = Wholesale::where('enabled', 1)
            ->where('is_delete', 0)
            ->where('review_status', 3)
            ->where('suppliers_id', $suppliers_id);

        $res = $res->with([
            'getWholesaleVolumePriceList',
            'getWholesaleExtend',
            'getSuppliers'
        ]);

        $res = $res->orderBy($sort, $order);

        $start = ($page - 1) * $size;
        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        $list = [];
        if ($res) {
            foreach ($res as $key => $row) {
                $suppliers = $row['get_suppliers'];

                if ($row['get_wholesale_volume_price_list']) {
                    $row['volume_number'] = $this->baseRepository->getArrayMin($row['get_wholesale_volume_price_list'], 'volume_number');
                    $row['volume_price'] = $this->baseRepository->getArrayMax($row['get_wholesale_volume_price_list'], 'volume_price');
                } else {
                    $row['volume_number'] = 0;
                    $row['volume_price'] = 0;
                }

                $row['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']); //处理图片地址

                /*  判断当前商家是否允许"在线客服" start  */
                $shop_information = $this->merchantCommonService->getShopName($suppliers['user_id']); //通过ru_id获取到店铺信息;
                //$row['is_IM'] = $shop_information['is_IM'] ?? 0; //平台是否允许商家使用"在线客服";

                //判断当前商家是平台,还是入驻商家 bylu
                if ($suppliers['user_id'] == 0) {
                    $kf_im_switch = SellerShopinfo::where('ru_id', 0)->value('kf_im_switch');

                    //判断平台是否开启了IM在线客服
                    if ($kf_im_switch) {
                        $row['is_dsc'] = true;
                    } else {
                        $row['is_dsc'] = false;
                    }
                } else {
                    $row['is_dsc'] = false;
                }
                /* end  */

                $row['goods_url'] = $this->dscRepository->buildUri('wholesale_goods', array('aid' => $row['goods_id']), $row['goods_name']);

                $properties = $this->goodsAttrService->getGoodsProperties($row['goods_id']);
                $row['goods_attr'] = $properties['pro'];
                $row['goods_sale'] = $this->orderService->getGoodsOrderSale($row['goods_id']);
                $row['goods_extend'] = $row['get_wholesale_extend']; //获取批发商品标识
                $row['goods_price'] = $row['goods_price'];
                $row['moq'] = $row['moq'];
                $row['volume_number'] = $row['volume_number'];
                $row['volume_price'] = $row['volume_price'];
                $row['rz_shopName'] = $suppliers['suppliers_name'] ?? ''; //供应商名称
                $row['suppliers_url'] = $this->dscRepository->buildUri('wholesale_suppliers', array('sid' => $suppliers['suppliers_id']));
                // 供应商获取配置客服QQ
                $kf_qq = get_suppliers_kf($suppliers['suppliers_id']);
                if ($kf_qq) {
                    $row['kf_qq'] = $kf_qq['kf_qq'];
                }

                $build_uri = array(
                    'urid' => $suppliers['user_id'],
                    'append' => $row['rz_shopName']
                );

                $domain_url = $this->merchantCommonService->getSellerDomainUrl($suppliers['user_id'], $build_uri);
                $row['store_url'] = $domain_url['domain_name'];
                $row['shop_price'] = $this->dscRepository->getPriceFormat($row['goods_price']);
                $list[] = $row;
            }
        }

        return $list;
    }

    /**
     * 供应商家商品统计
     * @param array $children
     * @param int $cat_id
     * @param string $ext
     * @return mixed
     */
    public function getSuppliersCount($suppliers_id = 0)
    {
        $res = Wholesale::where('enabled', 1)
            ->where('is_delete', 0)
            ->where('review_status', 3)
            ->where('suppliers_id', $suppliers_id);

        return $res->count();
    }

    /**
     * @param array $children
     * @param int $cat_id
     * @param string $ext
     * @return mixed
     */
    public function getWholesaleCount($cat_id = 0)
    {
        $res = Wholesale::where('enabled', 1)
            ->where('is_delete', 0)
            ->where('review_status', 3);

        if ($cat_id) {
            $children = $this->categoryService->getWholesaleCatListChildren($cat_id);
            $extension_goods = $this->getWholesaleExtensionGoods($children);

            if ($children) {
                $res = $res->whereIn('cat_id', $children);
            }

            if ($extension_goods) {
                $res = $res->whereIn('goods_id', $extension_goods);
            }
        }

        return $res->count();
    }

    /**
     * 获得商品的属性和规格
     *
     * @access  public
     * @param integer $goods_id
     * @return  array
     */
    public function getWholesaleGoodsProperties($goods_id, $goods_attr_id = '', $attr_type = 0)
    {
        $attr_array = array();
        if (!empty($goods_attr_id)) {
            $attr_array = explode(',', $goods_attr_id);
        }

        /* 对属性进行重新排序和分组 */
        $grp = GoodsType::whereHas('getWholesale', function ($query) use ($goods_id) {
            $query->where('goods_id', $goods_id);
        });

        $grp = $grp->value('attr_group');

        if ($grp) {
            $groups = explode("\n", strtr($grp, "\r", ''));
        }

        $goods_type = Wholesale::where('goods_id', $goods_id)
            ->value('goods_type');
        $goods_type = $goods_type ? $goods_type : 0;

        /* 获得商品的规格 */
        $res = WholesaleGoodsAttr::where('goods_id', $goods_id);

        if ($attr_type == 1 && !empty($goods_attr_id)) {
            $res = $res->whereIn('goods_attr_id', $goods_attr_id);
        }

        $res = $res->whereHas('getGoodsAttribute', function ($query) use ($goods_type) {
            $query = $query->where('attr_type', '<>', 2);

            if ($goods_type) {
                $query->where('cat_id', $goods_type);
            }
        });

        $res = $res->with([
            'getGoodsAttribute' => function ($query) {
                $query->select('attr_id', 'attr_name', 'attr_group', 'is_linked', 'attr_type', 'sort_order');
            }
        ]);

        $res = $this->baseRepository->getToArrayGet($res);

        $counts = count(array_unique(array_column($res, 'attr_id')));


        if ($res) {
            $num = 0;
            foreach ($res as $key => $val) {

                $attribute = $val['get_goods_attribute'];
                if ($counts === 1 && $val['goods_attr_id'] > 0) {
                    $num = WholesaleProducts::where('goods_attr', $val['goods_attr_id'])->value('product_number');
                }
                $res[$key]['product_number'] = $num ?? 0;
                $res[$key]['attr_id'] = $attribute['attr_id'];
                $res[$key]['attr_name'] = $attribute['attr_name'];
                $res[$key]['attr_group'] = $attribute['attr_group'];
                $res[$key]['is_linked'] = $attribute['is_linked'];
                $res[$key]['attr_type'] = $attribute['attr_type'];
                $res[$key]['sort_order'] = $attribute['sort_order'];
            }
        }

        //重新排序
        if ($res) {
            $res = collect($res)->sortBy('goods_attr_id')->sortBy('attr_id')->sortBy('sort_order')->values()->all();
        }

        $arr['pro'] = [];     // 属性
        $arr['spe'] = [];     // 规格
        $arr['lnk'] = [];     // 关联的属性

        if ($res) {
            foreach ($res as $row) {
                $row['attr_value'] = str_replace("\n", '<br />', $row['attr_value']);

                if ($row['attr_type'] == 0) {
                    $group = (isset($groups[$row['attr_group']])) ? $groups[$row['attr_group']] : $GLOBALS['_LANG']['goods_attr'];

                    $arr['pro'][$group][$row['attr_id']]['name'] = $row['attr_name'];
                    $arr['pro'][$group][$row['attr_id']]['value'] = $row['attr_value'];
                } else {
                    $attr_price = $row['attr_price'];

                    $img_site = array(
                        'attr_img_flie' => $row['attr_img_flie'],
                        'attr_img_site' => $row['attr_img_site']
                    );

                    $attr_info = $this->goodsAttributeImgService->getHasAttrInfo($row['attr_id'], $row['attr_value'], $img_site);

                    $attr_info['attr_img'] = $attr_info['attr_img'] ?? '';
                    $row['img_flie'] = !empty($attr_info['attr_img']) ? $this->dscRepository->getImagePath($attr_info['attr_img']) : '';
                    $row['img_site'] = $attr_info['attr_site'] ?? '';

                    $arr['spe'][$row['attr_id']]['attr_type'] = $row['attr_type'];
                    $arr['spe'][$row['attr_id']]['name'] = $row['attr_name'];
                    $arr['spe'][$row['attr_id']]['values'][] = array(
                        'label' => $row['attr_value'],
                        'img_flie' => $row['img_flie'],
                        'img_site' => $row['img_site'],
                        'checked' => $row['attr_checked'],
                        'attr_sort' => $row['attr_sort'],
                        'combo_checked' => $this->commonRepository->getComboGodosAttr($attr_array, $row['goods_attr_id']),
                        'price' => $attr_price,
                        'format_price' => $this->dscRepository->getPriceFormat(abs($attr_price), false),
                        'id' => $row['goods_attr_id'],
                        'product_number' => $row['product_number']
                    );
                }

                if ($row['is_linked'] == 1) {
                    /* 如果该属性需要关联，先保存下来 */
                    $arr['lnk'][$row['attr_id']]['name'] = $row['attr_name'];
                    $arr['lnk'][$row['attr_id']]['value'] = $row['attr_value'];
                }

                if ($row['attr_type'] > 0) {
                    $arr['spe'][$row['attr_id']]['values'] = get_array_sort($arr['spe'][$row['attr_id']]['values'], 'attr_sort');
                    $arr['spe'][$row['attr_id']]['is_checked'] = $this->commonRepository->getAttrValues($arr['spe'][$row['attr_id']]['values']);
                }
            }
        }
        // 格式化键值
        foreach ($arr['pro'] as $key => $val) {
            $arr['pro'][$key] = collect($val)->values()->all();
        }
        $arr['spe'] = collect($arr['spe'])->values()->all();

        return $arr;
    }

    /**
     * 获取批发商品货品信息
     *
     * @param int $goods_id
     * @param array $attr
     */
    public function getWholesaleProductInfo($goods_id = 0, $attr = [])
    {
        //货品数据
        $product_info = WholesaleProducts::where('goods_id', $goods_id);

        if ($attr) {
            foreach ($attr as $key => $val) {
                $val = intval($val);
                $product_info = $product_info->whereRaw("FIND_IN_SET('$val', REPLACE(goods_attr, '|', ','))");
            }
        }

        $product_info = $this->baseRepository->getToArrayFirst($product_info);

        return $product_info;
    }

    /**
     * 处理属性，返回数组
     *
     * @param int $goods_id
     * @param string $goods_attr_id
     * @return array|bool
     */
    public function getGoodsAttrArray($goods_id = 0, $goods_attr_id = '')
    {
        if (empty($goods_attr_id)) {
            return false;
        }

        $goods_attr_id = $this->baseRepository->getExplode($goods_attr_id);

        $res = WholesaleGoodsAttr::whereIn('goods_attr_id', $goods_attr_id)
            ->where('goods_id', $goods_id);

        $res = $res->with('getGoodsAttribute');

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $key => $val) {
                $attribute = $val['get_goods_attribute'];

                $res[$key]['attr_id'] = $attribute['attr_id'];
                $res[$key]['attr_name'] = $attribute['attr_name'];
                $res[$key]['sort_order'] = $attribute['sort_order'];
            }
        }

        $res = $this->baseRepository->getSortBy($res, 'sort_order');

        $arr = [];
        if ($res) {
            foreach ($res as $key => $row) {
                $arr[$key]['attr_name'] = $row['attr_name'];
                $arr[$key]['attr_value'] = $row['attr_value'];
            }
        }

        return $arr;
    }

    /**
     * 处理属性 返回字符串
     */
    public function getGoodsAttrInfo($goods_id = 0, $goods_attr_id = '', $pice_type = 0)
    {
        $attr = '';
        $goods_attr = $this->getGoodsAttrArray($goods_id, $goods_attr_id);
        if (!empty($goods_attr)) {

            if ($pice_type == 1) {
                $fmt = "%s:%s[%s]  ";
            } else {
                $fmt = "%s:%s[%s] \n";
            }

            foreach ($goods_attr as $k => $value) {

                if ($GLOBALS['_CFG']['goods_attr_price'] == 1) {
                    $attr_price = 0;
                } else {
                    $attr_price = isset($value['attr_price']) ? round(floatval($value['attr_price']), 2) : 0;
                    $attr_price = $this->dscRepository->getPriceFormat($attr_price, false);
                }

                $attr .= sprintf($fmt, $value['attr_name'], $value['attr_value'], $attr_price);
            }

            $attr = str_replace('[0]', '', $attr);
        }

        return $attr;
    }

    /**
     * 处理属性，返回数组
     *
     * @param int $goods_id
     * @param array $attr_num_array
     * @return array
     */
    public function getSelectRecordData($goods_id = 0, $attr_num_array = array())
    {
        $record_data = [];
        if ($attr_num_array) {
            $new_array = [];
            foreach ($attr_num_array as $key => $val) {
                $arr = explode(',', $val['attr']); //转成数组
                $end_attr = end($arr); //取出最后一个属性
                array_pop($arr); //销毁最后一个元素
                $attr_key = implode(',', $arr); //生成一个不包含最后元素的键名
                $new_array[$attr_key][$end_attr] = $val['num']; //加入数组
            }

            if ($new_array) {
                foreach ($new_array as $key => $val) {
                    $data = array();
                    $data['main_attr'] = $this->getGoodsAttrArray($goods_id, $key); //获取除最后一个属性信息
                    foreach ($val as $k => $v) {
                        $a = array();
                        $a['attr_num'] = $v;
                        $b = $this->getGoodsAttrArray($goods_id, $k); //获取最后元素的属性
                        $c = $b[0]; //取第一条记录
                        $a = array_merge($a, $c); //合并数据
                        $data['end_attr'][] = $a;
                    }
                    $record_data[$key] = $data;
                }
            }
        }

        return $record_data;
    }


    /**
     * 获取批发阶梯价
     *
     * @param int $goods_id
     * @param int $goods_number
     * @return array
     */
    public function getWholesaleVolumePrice($goods_id = 0, $goods_number = 0)
    {
        $res = Wholesale::where('goods_id', $goods_id);
        $res = $this->baseRepository->getToArrayFirst($res);

        $res['volume_price'] = $res['volume_price'] ?? [];
        if ($res && $res['price_model']) {

            //按数量排序
            $volume_price = WholesaleVolumePrice::where('goods_id', $goods_id)
                ->orderBy('volume_number');
            $res['volume_price'] = $this->baseRepository->getToArrayGet($volume_price);

            //设置数量阶段
            if ($res['volume_price']) {
                foreach ($res['volume_price'] as $key => $val) {
                    if ($key < count($res['volume_price']) - 1) {
                        $range_number = $res['volume_price'][$key + 1]['volume_number'] - 1;
                        $res['volume_price'][$key]['range_number'] = $range_number;
                    }
                    if ($goods_number >= $val['volume_number']) {
                        $res['volume_price'][$key]['is_reached'] = 1; //当前达成
                        if (isset($res['volume_price'][$key - 1]['is_reached'])) {
                            unset($res['volume_price'][$key - 1]['is_reached']);
                        }
                    }
                }
            }
        }

        return $res['volume_price'];
    }

    /**
     * 计算价格
     *
     * @param int $goods_id
     * @param int $goods_number
     * @return mixed
     */
    public function calculateGoodsPrice($goods_id = 0, $goods_number = 0)
    {
        $data = Wholesale::where('goods_id', $goods_id);
        $data = $this->baseRepository->getToArrayFirst($data);

        $unit_price = 0;
        if ($data) {
            if ($data['price_model'] == 0) {
                $unit_price = $data['goods_price'];
            } elseif ($data['price_model'] == 1) {
                $unit_price = WholesaleVolumePrice::where('goods_id', $goods_id)
                    ->where('volume_number', '<=', $goods_number);

                $unit_price = $unit_price->min('volume_price');

                //如果不满足阶梯价的最小数量要求，则依然展示最大价格
                if (!$unit_price) {
                    $unit_price = WholesaleVolumePrice::where('goods_id', $goods_id)
                        ->max('volume_price');
                }

                $unit_price = $unit_price ? $unit_price : 0;
            }
        }

        $data['total_number'] = $goods_number;
        $data['unit_price'] = $unit_price;
        $data['unit_price_formatted'] = $this->dscRepository->getPriceFormat($data['unit_price']);
        $data['total_price'] = $unit_price * $goods_number;
        $data['total_price_formatted'] = sprintf('%0.2f', $data['total_price']);
        return $data;
    }

    /**
     * 通过批发ID获取批发商品详情
     *
     * @param int $goods_id
     * @return mixed
     * @throws \Exception
     */
    public function getWholesaleGoodsInfo($goods_id = 0)
    {
        $row = Wholesale::where('goods_id', $goods_id)->where('is_delete', 0)->where('enabled', 1);//->where('review_status', 3);

        $row = $row->with([
            'getWholesaleCat',
            'getSuppliers',
            'getWholesaleExtend'
        ]);

        $row = $this->baseRepository->getToArrayFirst($row);

        if ($row) {
            $row['cat_id'] = $row['get_wholesale_cat']['cat_id'] ?? 0;

            $row['suppliers_review_status'] = $row['get_suppliers']['review_status'] ?? 1;//供应商审核状态
            unset($row['get_suppliers']['review_status']);
            $row = $this->baseRepository->getArrayMerge($row, $row['get_suppliers']);
            $row = $this->baseRepository->getArrayExcept($row, 'get_suppliers');

            $row = $this->baseRepository->getArrayExcept($row, 'get_wholesale_cat');

            $row['suppliers_logo'] = isset($row['suppliers_logo']) ? $this->dscRepository->getImagePath($row['suppliers_logo']) : '';

            $volume_price = WholesaleVolumePrice::where('goods_id', $row['goods_id'])->max('volume_price');

            if ($volume_price && $row['price_model'] == 1) {
                $row['goods_price'] = $volume_price;
            }
            $row['goods_price_formatted'] = $this->dscRepository->getPriceFormat($row['goods_price']);
            $row['volume_price'] = $this->getWholesaleVolumePrice($row['goods_id']);

            if ($this->config['open_oss'] == 1) {
                $bucket_info = $this->dscRepository->getBucketInfo();
                $endpoint = $bucket_info['endpoint'];
            } else {
                $endpoint = url('/');
            }

            if ($row['goods_desc']) {
                $desc_preg = get_goods_desc_images_preg($endpoint, $row['goods_desc']);
                $row['goods_desc'] = $desc_preg['goods_desc'];
            }

            $kf_qq = get_suppliers_kf($row['suppliers_id']);  // 供应商获取配置客服QQ
            if ($kf_qq) {
                $row['kf_qq_all'] = $kf_qq['kf_qq_all'];
                $row['kf_qq'] = $kf_qq['kf_qq'];
            }

            $row['goods_extend'] = $row['get_wholesale_extend'] ?? []; //获取批发商品标识
            $row['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
            $row['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
            $row['original_img'] = $this->dscRepository->getImagePath($row['original_img']);
            $row['goods_weight'] = (intval($row['goods_weight']) > 0) ?
                $row['goods_weight'] . $GLOBALS['_LANG']['kilogram'] :
                ($row['goods_weight'] * 1000) . $GLOBALS['_LANG']['gram'];

            $brand_info = get_brand_url($row['brand_id']);
            $row['goods_brand_url'] = !empty($brand_info) ? $brand_info['url'] : '';
            $row['brand_thumb'] = !empty($brand_info) ? $brand_info['brand_logo'] : '';

            $row['user_id'] = $row['user_id'] ?? 0;

            $selle_info = $this->merchantCommonService->getShopName($row['user_id'], 3);

            $row['rz_shopName'] = $selle_info['shop_name'] ?? ''; //店铺名称

            $build_uri = array(
                'urid' => $row['user_id'],
                'append' => $row['rz_shopName']
            );

            $domain_url = $this->merchantCommonService->getSellerDomainUrl($row['user_id'], $build_uri);
            $row['store_url'] = $domain_url['domain_name'];
            $row['suppliers_url'] = $this->dscRepository->buildUri('wholesale_suppliers', array('sid' => $row['suppliers_id']));
        }

        return $row;
    }

    /**
     * 获取主属性列表
     *
     * @param int $goods_id
     * @param array $attr
     * @return bool
     */
    public function getWholesaleMainAttrList($goods_id = 0, $attr = array())
    {
        $goods_type = Wholesale::where('goods_id', $goods_id)->value('goods_type');
        $goods_type = $goods_type ? $goods_type : 0;

        //查出已勾选的属性id
        $other_attr = WholesaleGoodsAttr::where('goods_id', $goods_id)->whereIN('goods_attr_id', $attr);
        $other_attr = $this->baseRepository->getToArrayGet($other_attr);
        $other_attr = array_column($other_attr, 'attr_id');

        $attr_ids = WholesaleGoodsAttr::where('goods_id', $goods_id);
        //排除已选择的属性id
        if ($other_attr) {
            $attr_ids = $attr_ids->whereNotIN('attr_id', $other_attr);
        }
        $attr_ids = $this->baseRepository->getToArrayGet($attr_ids);
        $attr_ids = $this->baseRepository->getKeyPluck($attr_ids, 'attr_id');
        $attr_ids = $attr_ids ? array_unique($attr_ids) : [];

        if (!empty($attr_ids)) {
            //获取最后可选择的属性
            $attr_id = Attribute::where('cat_id', $goods_type)
                ->whereIn('attr_id', $attr_ids)
                ->where('attr_type', 1)
                ->orderByRaw('sort_order, attr_id desc')
                ->value('attr_id');

            $attr_id = $attr_id ? $attr_id : 0;

            $data = [];
            if ($attr_id) {
                $data = WholesaleGoodsAttr::where('goods_id', $goods_id)
                    ->where('attr_id', $attr_id)
                    ->orderBy('goods_attr_id');

                $data = $this->baseRepository->getToArrayGet($data);
            }

            //处理货品数据
            $list = [];
            if ($data) {
                foreach ($data as $key => $val) {
                    $new_arr = array_merge($attr, array($val['goods_attr_id']));

                    if ($new_arr) {
                        $val['attr_group'] = implode(',', $new_arr); //属性组合

                        $product_info = WholesaleProducts::where('goods_id', $goods_id);

                        foreach ($new_arr as $k => $v) {
                            $v = intval($v);
                            $product_info = $product_info->whereRaw("FIND_IN_SET('$v', REPLACE(goods_attr, '|', ','))");
                        }

                        $product_info = $this->baseRepository->getToArrayFirst($product_info);

                        $val['product_id'] = $product_info['product_id'] ?? 0;
                        $val['goods_attr'] = $product_info['goods_attr'] ?? '';
                        $val['product_sn'] = $product_info['product_sn'] ?? '';
                        $val['product_number'] = $product_info['product_number'] ?? 0;
                    }

                    $list [$key] = $val;
                }

                return $list;
            }
        }

        return false;
    }

    /**
     * 供应商品扩展表
     *
     * @param int $goods_id
     * @return array
     */
    public function getWholesaleExtend($goods_id = 0)
    {
        $row = WholesaleExtend::where('goods_id', $goods_id);
        $row = $this->baseRepository->getToArrayFirst($row);

        return $row;
    }

    /**
     * 供应商品相册
     *
     * @param int $goods_id
     * @return array
     */
    public function getGalleryList($goods_id = 0)
    {
        $res = SuppliersGoodsGallery::where('goods_id', $goods_id)
            ->orderBy('img_desc');
        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $k => $v) {
                $res[$k] = $this->dscRepository->getImagePath(($v['img_url']));
            }
        }
        return $res;
    }


    /**
     * 获取搜索商品列表
     *
     * @return mixed
     */
    public function getSearchList($keywords = '', $page = 1, $size = 10)
    {
        $res = Wholesale::where('enabled', 1)
            ->where('is_delete', 0)
            ->where('review_status', 3);
        // 关键词
        if (!empty($keywords)) {
            $res = $res->where('goods_name', 'like', '%' . $keywords . '%');
        }
        $res = $res->with([
            'getWholesaleVolumePriceList'
        ]);
        $begin = ($page - 1) * $size;
        $res = $res->orderby('goods_id', 'desc')
            ->offset($begin)
            ->limit($size);
        $res = $this->baseRepository->getToArrayGet($res);
        if ($res) {
            foreach ($res as $key => $row) {
                if ($row['get_wholesale_volume_price_list']) {
                    $row['volume_number'] = $this->baseRepository->getArrayMin($row['get_wholesale_volume_price_list'], 'volume_number');
                    $row['volume_price'] = $this->baseRepository->getArrayMax($row['get_wholesale_volume_price_list'], 'volume_price');
                } else {
                    $row['volume_number'] = 0;
                    $row['volume_price'] = 0;
                }
                $res[$key]['goods_sale'] = $this->orderService->getGoodsOrderSale($row['goods_id']);
                $res[$key]['volume_number'] = $row['volume_number'];
                $res[$key]['volume_price'] = $row['volume_price'];

                $res[$key]['goods_name'] = $row['goods_name'];
                $res[$key]['goods_price'] = $row['goods_price'];
                $res[$key]['moq'] = $row['moq'];
                $res[$key]['volume_number'] = $row['volume_number'];
                $res[$key]['volume_price'] = $row['volume_price'];
                $res[$key]['goods_unit'] = $row['goods_unit'];
                $res[$key]['thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $res[$key]['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
                $res[$key]['url'] = $this->dscRepository->buildUri('wholesale_goods', array('aid' => $row['goods_id']), $row['goods_name']);
            }
        }

        return $res;
    }

    /**
     * 获得指定商品的相册
     *
     * @access  public
     * @param integer $goods_id
     * @return  array
     */
    public function getGoodsGallery($goods_id, $gallery_number = 0)
    {
        if (!$gallery_number) {
            $gallery_number = $this->config['goods_gallery_number'];
        }

        $row = SuppliersGoodsGallery::where('goods_id', $goods_id)
            ->orderBy('img_desc')
            ->take($gallery_number);
        $row = $this->baseRepository->getToArrayGet($row);

        /* 格式化相册图片路径 */
        if ($row) {
            foreach ($row as $key => $gallery_img) {
                if (!empty($gallery_img['external_url'])) {
                    $row[$key]['img_url'] = $gallery_img['external_url'];
                    $row[$key]['thumb_url'] = $gallery_img['external_url'];
                } else {
                    $row[$key]['img_url'] = $this->dscRepository->getImagePath($gallery_img['img_url']);
                    $row[$key]['thumb_url'] = $this->dscRepository->getImagePath($gallery_img['thumb_url']);
                }
            }

            /* 商品无相册图调用商品图 */
            if (!$row) {
                $goods_thumb = Wholesale::where('goods_id', $goods_id)
                    ->value('goods_thumb');

                $row = [
                    [
                        'img_url' => $this->dscRepository->getImagePath($goods_thumb),
                        'thumb_url' => $this->dscRepository->getImagePath($goods_thumb)
                    ]
                ];
            }
        }

        return $row;
    }
}
