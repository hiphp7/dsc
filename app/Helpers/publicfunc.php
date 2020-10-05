<?php

use App\Models\AccountLog;
use App\Models\AdminUser;
use App\Models\Category;
use App\Models\GalleryAlbum;
use App\Models\Goods;
use App\Models\GoodsAttr;
use App\Models\GoodsChangeLog;
use App\Models\GoodsExtend;
use App\Models\GoodsGallery;
use App\Models\GoodsLib;
use App\Models\GoodsLibCat;
use App\Models\GoodsLibGallery;
use App\Models\GoodsTransport;
use App\Models\GoodsTransportTpl;
use App\Models\GoodsType;
use App\Models\GoodsTypeCat;
use App\Models\KdniaoCustomerAccount;
use App\Models\KdniaoEorderConfig;
use App\Models\LinkAreaGoods;
use App\Models\LinkGoodsDesc;
use App\Models\MerchantsAccountLog;
use App\Models\MerchantsCategory;
use App\Models\MerchantsRegionInfo;
use App\Models\MerchantsShopBrand;
use App\Models\MerchantsShopInformation;
use App\Models\MerchantsStepsFields;
use App\Models\OrderDelayed;
use App\Models\OrderInfo;
use App\Models\OrderReturn;
use App\Models\PicAlbum;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Models\Region;
use App\Models\SellerAccountLog;
use App\Models\SellerGrade;
use App\Models\SellerShopinfo;
use App\Models\SellerShopinfoChangelog;
use App\Models\Shipping;
use App\Models\StoreOrder;
use App\Models\Users;
use App\Models\VolumePrice;
use App\Models\WarehouseAreaAttr;
use App\Models\WarehouseAreaGoods;
use App\Models\WarehouseAttr;
use App\Models\WarehouseGoods;
use App\Models\WholesaleCat;
use App\Models\ZcCategory;
use App\Plugins\TpApi\Kdniao;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsGalleryService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Store\StoreService;
use Illuminate\Support\Str;

//插入仓库/地区属性价格数据
function get_warehouse_area_attr_price_insert($warehouse_area, $goods_id, $goods_attr_id, $table)
{
    if (is_array($warehouse_area)) {
        for ($i = 0; $i < count($warehouse_area); $i++) {
            if (!empty($warehouse_area[$i])) {
                $parent = [
                    'goods_id' => $goods_id,
                    'goods_attr_id' => $goods_attr_id
                ];

                if ($table == 'warehouse_attr') {
                    $res = WarehouseAttr::where('warehouse_id', $warehouse_area[$i]);

                    $parent['warehouse_id'] = $warehouse_area[$i];
                    $parent['attr_price'] = $_POST['attr_price_' . $warehouse_area[$i]];
                } elseif ($table == 'warehouse_area_attr') {
                    $res = WarehouseAreaAttr::where('area_id', $warehouse_area[$i]);

                    $parent['area_id'] = $warehouse_area[$i];
                    $parent['attr_price'] = $_POST['attrPrice_' . $warehouse_area[$i]];
                }

                if ($goods_id) {
                    $admin_id = get_admin_id();
                    $res = $res->where('admin_id', $admin_id);
                }

                $res = $res->where('goods_id', $goods_id)
                    ->where('goods_attr_id', $goods_attr_id);

                $data = $id = $res;

                $id = $id->value('id');

                if ($id > 0) {
                    $data->update($parent);
                } else {
                    $parent['admin_id'] = $admin_id;

                    if ($table == 'warehouse_attr') {
                        WarehouseAttr::insert($parent);
                    } elseif ($table == 'warehouse_area_attr') {
                        WarehouseAreaAttr::insert($parent);
                    }
                }
            }
        }
    } elseif (is_array($goods_attr_id)) {
        for ($i = 0; $i < count($goods_attr_id); $i++) {
            if (!empty($goods_attr_id[$i])) {
                $parent = [
                    'goods_id' => $goods_id,
                    'goods_attr_id' => $goods_attr_id[$i]
                ];

                if ($table == 'warehouse_attr') {
                    $res = WarehouseAttr::where('warehouse_id', $warehouse_area);

                    $parent['warehouse_id'] = $warehouse_area;
                    $parent['attr_price'] = $_POST['attr_price_' . $goods_attr_id[$i]];
                } elseif ($table == 'warehouse_area_attr') {
                    WarehouseAreaAttr::where('area_id', $warehouse_area);

                    $parent['area_id'] = $warehouse_area;
                    $parent['attr_price'] = $_POST['attrPrice_' . $goods_attr_id[$i]];
                }

                if ($goods_id) {
                    $admin_id = get_admin_id();
                    $res = $res->where('admin_id', $admin_id);
                }

                $res = $res->where('goods_id', $goods_id)
                    ->where('goods_attr_id', $goods_attr_id[$i]);

                $data = $id = $res;

                $id = $id->value('id');

                if ($id > 0) {
                    $data->update($parent);
                } else {
                    $parent['admin_id'] = $admin_id;

                    if ($table == 'warehouse_attr') {
                        WarehouseAttr::insert($parent);
                    } elseif ($table == 'warehouse_area_attr') {
                        WarehouseAreaAttr::insert($parent);
                    }
                }
            }
        }
    }
}

/* 获取当前商家的等级 */

function get_seller_grade_rank($ru_id)
{
    $res = SellerGrade::whereHas('getMerchantsGrade', function ($query) use ($ru_id) {
        $query->where('ru_id', $ru_id);
    });

    $res = app(BaseRepository::class)->getToArrayFirst($res);

    if ($res) {
        $res['favorable_rate'] = $res && ($res['favorable_rate'] >= 0) ? $res['favorable_rate'] / 100 : 1;
        $res['give_integral'] = $res && ($res['give_integral'] >= 0) ? $res['give_integral'] / 100 : 1;
        $res['rank_integral'] = $res && ($res['rank_integral'] >= 0) ? $res['rank_integral'] / 100 : 1;
        $res['pay_integral'] = $res && ($res['pay_integral'] >= 0) ? $res['pay_integral'] / 100 : 1;
    }

    return $res;
}

/**
 * 申请日志列表
 */
function get_account_log_list($ru_id, $type = 0)
{
    load_helper('order.php');

    $result = get_filter();
    if ($result === false) {

        /* 过滤条件 */
        $filter['keywords'] = !isset($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }

        $filter['order_sn'] = !isset($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
        $filter['out_up'] = !isset($_REQUEST['out_up']) ? 0 : intval($_REQUEST['out_up']);
        $filter['log_type'] = !isset($_REQUEST['log_type']) ? 0 : intval($_REQUEST['log_type']);
        $filter['handler'] = !isset($_REQUEST['handler']) ? 0 : intval($_REQUEST['handler']);
        $filter['rawals'] = !isset($_REQUEST['rawals']) ? 0 : intval($_REQUEST['rawals']);

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'log_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $filter['act_type'] = !isset($_REQUEST['act_type']) ? 'detail' : $_REQUEST['act_type'];
        $filter['ru_id'] = !isset($_REQUEST['ru_id']) ? $ru_id : intval($_REQUEST['ru_id']);

        $row = SellerAccountLog::whereRaw(1);

        //订单编号
        if ($filter['order_sn']) {
            $row = $row->where(function ($query) use ($filter) {
                $query = $query->where('apply_sn', $filter['order_sn']);
                $query->orWhere(function ($query) use ($filter) {
                    $query->whereHas('getOrder', function ($query) use ($filter) {
                        $query->where('order_sn', $filter['order_sn']);
                    });
                });
            });
        }

        //收入/支出
        if ($filter['out_up']) {
            if ($filter['out_up'] != 4) {
                if ($filter['out_up'] == 3) {
                    $row = $row->where('log_type', $filter['out_up']);
                }

                $row = $row->where(function ($query) use ($filter) {
                    $query->where('log_type', '>', $filter['out_up'])
                        ->orWhere('log_type', $filter['out_up']);
                });
            } else {
                $row = $row->where('log_type', $filter['out_up']);
            }
        }

        if ($filter['rawals'] == 1) {
            $type = [1];
        }

        //待处理
        if (isset($filter['handler'])) {
            if ($filter['handler'] == 1) {
                $row = $row->where('is_paid', 1);
            }
            if ($filter['handler'] == '2') {
                $row = $row->where('is_paid', 0);
            }
        }

        //类型
        if ($filter['log_type']) {
            $row = $row->where('log_type', $filter['log_type']);
        }

        //管理员查询的权限 -- 店铺查询 start
        $filter['store_search'] = empty($_REQUEST['store_search']) ? 0 : intval($_REQUEST['store_search']);
        $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
        $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';

        if ($filter['store_search'] != 0) {
            $where = [
                'filter' => $filter,
                'request' => $_REQUEST
            ];

            if ($filter['ru_id'] == 0) {
                $row = $row->whereHas('getMerchantsShopInformation', function ($query) use ($where) {
                    if ($where['filter']['store_search'] == 1) {
                        $query->where('user_id', $where['filter']['merchant_id']);
                    } elseif ($where['filter']['store_search'] == 2) {
                        $store_keyword = mysql_like_quote($where['filter']['store_keyword']);
                        $query = $query->where('rz_shopName', 'like', '%' . $store_keyword . '%');

                        if ($where['filter']['store_search'] > 1) {
                            $query->where('user_id', '>', 0);
                        }
                    } elseif ($where['filter']['store_search'] == 3) {
                        $store_keyword = mysql_like_quote($where['filter']['store_keyword']);
                        $query = $query->where('shoprz_brandName', 'like', '%' . $store_keyword . '%');

                        if ($where['request']['store_type']) {
                            $query = $query->where('shopNameSuffix', $where['request']['store_type']);
                        }

                        if ($where['filter']['store_search'] > 1) {
                            $query->where('user_id', '>', 0);
                        }
                    }
                });
            }
        }
        //管理员查询的权限 -- 店铺查询 end

        if ($filter['ru_id']) {
            $row = $row->where('ru_id', $filter['ru_id']);
        }

        $row = $row->whereIn('log_type', $type);

        $res = $count = $row;

        $filter['record_count'] = $count->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

        if ($filter['start'] > 0) {
            $res = $res->skip($filter['start']);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $filter['keywords'] = stripslashes($filter['keywords']);
    } else {
        $filter = $result['filter'];
    }

    $res = app(BaseRepository::class)->getToArrayGet($res);

    if ($res) {
        for ($i = 0; $i < count($res); $i++) {
            $res[$i]['shop_name'] = app(MerchantCommonService::class)->getShopName($res[$i]['ru_id'], 1);
            $order = order_info($res[$i]['order_id']);
            $res[$i]['order_sn'] = !empty($order['order_sn']) ? sprintf(lang('order.order_remark'), $order['order_sn']) : $res[$i]['apply_sn'];
            $res[$i]['amount'] = price_format($res[$i]['amount'], false);
            $res[$i]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $res[$i]['add_time']);
            $res[$i]['payment_info'] = payment_info($res[$i]['pay_id']);
            $res[$i]['apply_sn'] = sprintf($GLOBALS['_LANG']['01_apply_sn'], $res[$i]['apply_sn']);
            $res[$i]['certificate_img'] = get_image_path($res[$i]['certificate_img']);
        }
    }

    $arr = ['log_list' => $res, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

    return $arr;
}

/**
 * 申请日志详细信息
 */
function get_account_log_info($log_id)
{
    $StoreRep = app(StoreService::class);

    $res = SellerAccountLog::where('log_id', $log_id);
    $res = app(BaseRepository::class)->getToArrayFirst($res);

    if ($res) {
        $res['shop_name'] = app(MerchantCommonService::class)->getShopName($res['ru_id'], 1);
        $res['payment_info'] = payment_info($res['pay_id']);
        $res['certificate_img'] = get_image_path($res['certificate_img']);

        $info = $StoreRep->getShopInfo($res['ru_id']);
        $res['seller_money'] = $info['seller_money']; //商家可提现金额
        $res['seller_frozen'] = $info['frozen_money']; //商家冻结金额
    }

    return $res;
}

/**
 * 查询所有商家分类
 */
function get_seller_category()
{
    $res = read_static_cache('get_seller_category');

    if ($res === false) {
        $res = MerchantsCategory::whereRaw(1);
        $res = $res->with([
            'getParentCatInfo' => function ($query) {
                $query->select('cat_id', 'cat_name as parent_name');
            }
        ]);

        $res = app(BaseRepository::class)->getToArrayGet($res);

        write_static_cache('get_seller_category', $res);
    }

    $chid_level = 0;
    $level = 1;

    $arr = [];
    if ($res) {
        foreach ($res as $key => $row) {
            $row = app(BaseRepository::class)->getArrayMerge($row, $row['get_parent_cat_info']);
            $arr[$key] = $row;

            $arr[$key]['cat_id'] = $row['cat_id'];
            $arr[$key]['cat_name'] = $row['cat_name'];
            $arr[$key]['parent_id'] = $row['parent_id'];

            $arr[$key]['keywords'] = $row['keywords'];
            $arr[$key]['cat_desc'] = $row['cat_desc'];
            $arr[$key]['sort_order'] = $row['sort_order'];
            $arr[$key]['measure_unit'] = $row['measure_unit'];
            $arr[$key]['show_in_nav'] = $row['show_in_nav'];
            $arr[$key]['style'] = $row['style'];
            $arr[$key]['grade'] = $row['grade'];
            $arr[$key]['filter_attr'] = $row['filter_attr'];
            $arr[$key]['is_top_style'] = $row['is_top_style'];
            $arr[$key]['top_style_tpl'] = $row['top_style_tpl'];
            $arr[$key]['cat_icon'] = $row['cat_icon'];
            $arr[$key]['is_top_show'] = $row['is_top_show'];
            $arr[$key]['category_links'] = $row['category_links'];
            $arr[$key]['category_topic'] = $row['category_topic'];
            $arr[$key]['pinyin_keyword'] = $row['pinyin_keyword'];
            $arr[$key]['cat_alias_name'] = $row['cat_alias_name'];
            $arr[$key]['template_file'] = $row['template_file'];

            $arr[$key]['parent_name'] = $row['parent_name'];

            if ($row['get_parent_cat_info']) {
                $cat_level = get_seller_cat_level($row['parent_id']);
                if ($cat_level['parent_id'] != 0) {
                    $chid = get_seller_cat_level($cat_level['parent_id']);
                    if ($chid) {
                        $chid_level += 1;
                    }
                }

                $arr[$key]['level'] = $level + $chid_level;
            } else {
                $arr[$key]['level'] = 0;
            }

            $cat_level = ['一', '二', '三', '四', '五', '六', '气', '八', '九', '十'];
            $arr[$key]['belongs'] = $cat_level[$arr[$key]['level']] . "级";

            if ($arr[$key]['level'] == 0) {
                $row['parent_id'] = 0;
            }
        }
    }

    return $arr;
}

/**
 * 查询商家分类是否存在上一级
 */
function get_seller_cat_level($parent_id = 0, $level = 1)
{
    $row = MerchantsCategory::where('cat_id', $parent_id);
    $row = app(BaseRepository::class)->getToArrayFirst($row);

    return $row;
}

/*
 * 商家分类
 * 获取上下级分类列表 by wu
 * $cat_id      分类id
 * $relation    关系 0:自己 1:上级 2:下级
 * $self        是否包含自己 true:包含 false:不包含
 */

function get_seller_select_category($cat_id = 0, $relation = 0, $self = true, $user_id = 0)
{
    //静态数组
    static $cat_list = [];
    $cat_list[] = intval($cat_id);

    if ($relation == 0) {
        return $cat_list;
    } elseif ($relation == 1) {
        $parent_id = MerchantsCategory::where('cat_id', $cat_id);

        if ($user_id) {
            $parent_id = $parent_id->where('user_id', $user_id);
        }

        $parent_id = $parent_id->value('parent_id');

        if (!empty($parent_id)) {
            get_seller_select_category($parent_id, $relation, $self, $user_id);
        }
        //删除自己
        if ($self == false) {
            unset($cat_list[0]);
        }

        $cat_list[] = 0;

        //去掉重复，主要是0
        return array_reverse(array_unique($cat_list));
    } elseif ($relation == 2) {
        $child_id = MerchantsCategory::where('parent_id', $cat_id);

        if ($user_id) {
            $child_id = $child_id->where('user_id', $user_id);
        }

        $child_id = app(BaseRepository::class)->getToArrayGet($child_id);
        $child_id = app(BaseRepository::class)->getKeyPluck($child_id, 'cat_id');

        if (!empty($child_id)) {
            foreach ($child_id as $key => $val) {
                get_seller_select_category($val, $relation, $self, $user_id);
            }
        }
        //删除自己
        if ($self == false) {
            unset($cat_list[0]);
        }
        return $cat_list;
    }
}

/*
 * 平台分类
 * 获取当级分类列表 by wu
 * $cat_id      分类id
 * $relation    关系 0:自己 1:上级 2:下级
 */

function get_seller_category_list($cat_id = 0, $relation = 0, $user_id = 0)
{
    if ($relation == 0 || $relation == 1) {
        $res = MerchantsCategory::where('cat_id', $cat_id);

        if ($user_id) {
            $res = $res->where('user_id', $user_id);
        }

        $parent_id = $res->value('parent_id');
    } elseif ($relation == 2) {
        $parent_id = $cat_id;
    }

    $parent_id = empty($parent_id) ? 0 : $parent_id;
    $category_list = MerchantsCategory::where('parent_id', $parent_id);

    if ($user_id) {
        $category_list = $category_list->where('user_id', $user_id);
    }

    $category_list = app(BaseRepository::class)->getToArrayGet($category_list);

    if ($category_list) {
        foreach ($category_list as $key => $val) {
            if ($cat_id == $val['cat_id']) {
                $is_selected = 1;
            } else {
                $is_selected = 0;
            }
            $category_list[$key]['is_selected'] = $is_selected;
        }
    }

    return $category_list;
}

//设置默认筛选 by wu
function set_default_filter($goods_id = 0, $cat_id = 0, $user_id = 0, $cat_type_show = 0, $table = 'category')
{
    //分类导航
    if ($cat_id) {
        $parent_cat_list = get_select_category($cat_id, 1, true, $user_id, $table);
        $filter_category_navigation = get_array_category_info($parent_cat_list, $table);
        $GLOBALS['smarty']->assign('filter_category_navigation', $filter_category_navigation);
    }

    if ($user_id) {
        $seller_shop_cat = seller_shop_cat($user_id);
    } else {
        $seller_shop_cat = [];
    }

    $GLOBALS['smarty']->assign('table', $table);
    $GLOBALS['smarty']->assign('filter_category_list', get_category_list($cat_id, 0, $seller_shop_cat, $user_id, 2, $table)); //分类列表
    $GLOBALS['smarty']->assign('filter_brand_list', search_brand_list($goods_id, $user_id)); //品牌列表
    $GLOBALS['smarty']->assign('cat_type_show', $cat_type_show); //平台分类

    return true;
}

function set_seller_default_filter($goods_id = 0, $cat_id = 0, $user_id = 0)
{
    //分类导航
    if ($cat_id > 0) {
        $seller_parent_cat_list = get_seller_select_category($cat_id, 1, true, $user_id);
        $seller_filter_category_navigation = get_seller_array_category_info($seller_parent_cat_list);

        $GLOBALS['smarty']->assign('seller_filter_category_navigation', $seller_filter_category_navigation);
    }

    $GLOBALS['smarty']->assign('seller_filter_category_list', get_seller_category_list($cat_id, 0, $user_id)); //分类列表
    $GLOBALS['smarty']->assign('seller_cat_type_show', 1); //商家分类

    return true;
}

//给出cat_id,返回逐级分类 by wu
function get_seller_every_category($cat_id = 0)
{
    $parent_cat_list = get_seller_category_array($cat_id, 1, true);
    $filter_category_navigation = get_seller_array_category_info($parent_cat_list);
    $cat_nav = "";
    if ($filter_category_navigation) {
        foreach ($filter_category_navigation as $key => $val) {
            if ($key == 0) {
                $cat_nav .= $val['cat_name'];
            } elseif ($key > 0) {
                $cat_nav .= " > " . $val['cat_name'];
            }
        }
    }

    return $cat_nav;
}

//通过分类id，获取一个数组包含所有父级元素 by wu
function get_seller_category_array($cat_id = 0, $relation = 0, $self = true)
{
    $cat_list[] = intval($cat_id);

    if ($relation == 0) {
        return $cat_list;
    } elseif ($relation == 1) {
        do {
            $parent_id = MerchantsCategory::where('cat_id', $cat_id)->value('parent_id');

            if (!empty($parent_id)) {
                $cat_list[] = $parent_id;
                $cat_id = $parent_id;
            }
        } while (!empty($parent_id));

        //删除自己
        if ($self == false) {
            unset($cat_list[0]);
        }
        $cat_list[] = 0;
        //去掉重复，主要是0
        return array_reverse(array_unique($cat_list));
    } elseif ($relation == 2) {
    }
}

//获取数组中分类信息 by wu
function get_seller_array_category_info($arr = [])
{
    if ($arr) {
        $arr = app(BaseRepository::class)->getExplode($arr);
        $res = MerchantsCategory::whereIn('cat_id', $arr);
        $res = app(BaseRepository::class)->getToArrayGet($res);

        return $res;
    } else {
        return false;
    }
}

/**
 * 商家入驻分类
 */
function seller_shop_cat($user_id = 0)
{
    $seller_shop_cat = '';
    if ($user_id) {
        $seller_shop_cat = MerchantsShopInformation::where('user_id', $user_id)->value('user_shopMain_category');
    }

    $arr = [];
    $arr['parent'] = '';
    if ($seller_shop_cat) {
        $seller_shop_cat = explode("-", $seller_shop_cat);

        foreach ($seller_shop_cat as $key => $row) {
            if ($row) {
                $cat = explode(":", $row);
                $arr[$key]['cat_id'] = $cat[0];
                $arr[$key]['cat_tree'] = $cat[1];

                $arr['parent'] .= $cat[0] . ",";

                if ($cat[1]) {
                    $arr['parent'] .= $cat[1] . ",";
                }
            }
        }
    }

    $arr['parent'] = substr($arr['parent'], 0, -1);

    return $arr;
}

/**
 * 获取商品信息
 */
function get_admin_goods_info($goods_id = 0, $select = [])
{
    $row = Goods::where('goods_id', $goods_id);

    $row = app(BaseRepository::class)->getToArrayFirst($row);

    if ($row) {
        if (isset($row['user_cat']) && !empty($row['user_cat'])) {
            $cat_info = MerchantsCategory::catInfo($row['user_cat']);
            $cat_info = app(BaseRepository::class)->getToArrayFirst($cat_info);

            $cat_info['is_show_merchants'] = $cat_info['is_show'];
            $row['user_cat_name'] = $cat_info['cat_name'];
        }

        $row['goods_video_path'] = get_image_path($row['goods_video']);
        $row['shop_name'] = app(MerchantCommonService::class)->getShopName($row['user_id'], 1); //店铺名称
    }

    return $row;
}

//给出cat_id,返回逐级分类 by wu
function get_every_category($cat_id = 0, $table = 'category')
{
    $parent_cat_list = get_category_array($cat_id, 1, true, $table);
    $filter_category_navigation = get_array_category_info($parent_cat_list, $table);
    $cat_nav = "";
    if ($filter_category_navigation) {
        foreach ($filter_category_navigation as $key => $val) {
            if ($table == 'goods_type_cat') {
                $cat_nav = $val['cat_name'];
            } else {
                if ($key == 0) {
                    $cat_nav .= $val['cat_name'];
                } elseif ($key > 0) {
                    $cat_nav .= " > " . $val['cat_name'];
                }
            }
        }
    }

    return $cat_nav;
}

//通过分类id，获取一个数组包含所有父级元素 by wu
function get_category_array($cat_id = 0, $relation = 0, $self = true, $table = '')
{
    if ($table == 'goods_lib_cat') {
        $table = GoodsLibCat::whereRaw(1);
    } elseif ($table == 'zc_category') {
        $table = ZcCategory::whereRaw(1);
    } elseif ($table == 'goods_type_cat') {
        $table = GoodsTypeCat::whereRaw(1);
    } elseif ($table == 'wholesale_cat') {
        $table = WholesaleCat::whereRaw(1);
    } else {
        $table = Category::whereRaw(1);
    }

    $cat_list[] = intval($cat_id);

    if ($relation == 0) {
        return $cat_list;
    } elseif ($relation == 1) {
        do {
            $parent_id = $table->where('cat_id', $cat_id)->value('parent_id');

            if (!empty($parent_id)) {
                $cat_list[] = $parent_id;
                $cat_id = $parent_id;
            }
        } while (!empty($parent_id));

        //删除自己
        if ($self == false) {
            unset($cat_list[0]);
        }
        $cat_list[] = 0;
        //去掉重复，主要是0
        return array_reverse(array_unique($cat_list));
    } elseif ($relation == 2) {
    }
}

//获取数组中分类信息 by wu
function get_array_category_info($arr = [], $table = '')
{
    if ($table == 'goods_lib_cat') {
        $table = GoodsLibCat::whereRaw(1);
    } elseif ($table == 'zc_category') {
        $table = ZcCategory::whereRaw(1);
    } elseif ($table == 'goods_type_cat') {
        $table = GoodsTypeCat::whereRaw(1);
    } elseif ($table == 'wholesale_cat') {
        $table = WholesaleCat::whereRaw(1);
    } else {
        $table = Category::whereRaw(1);
    }

    if ($arr) {
        $arr = app(DscRepository::class)->delStrComma($arr);
        $arr = app(BaseRepository::class)->getExplode($arr);

        $category_list = $table->whereIn('cat_id', $arr);
        $category_list = app(BaseRepository::class)->getToArrayGet($category_list);

        foreach ($category_list as $key => $val) {
            $category_list[$key]['url'] = app(DscRepository::class)->buildUri($table, ['cid' => $val['cat_id']], $val['cat_name']);
        }
        return $category_list;
    } else {
        return false;
    }
}

/**
 * 商品详情分类
 */
function get_add_edit_goods_cat_list($goods_id = 0, $cat_id = 0, $table = 'category', $sin_prefix = '', $user_id = 0, $seller_shop_cat = [])
{

    //关联商品
    if (empty($sin_prefix)) {
        $select_category_rel = '';
        $select_category_rel .= insert_select_category(0, 0, 0, 'cat_id1', 1, $table, $seller_shop_cat);
        $GLOBALS['smarty']->assign($sin_prefix . 'select_category_rel', $select_category_rel);
    }

    //配件
    if (empty($sin_prefix)) {
        $select_category_pak = '';
        $select_category_pak .= insert_select_category(0, 0, 0, 'cat_id2', 1, $table, $seller_shop_cat);
        $GLOBALS['smarty']->assign($sin_prefix . 'select_category_pak', $select_category_pak);
    }

    /**
     * 商品分类
     * 添加商品
     */
    if ($_REQUEST['act'] == 'add') {
        $select_category_html = '';

        if ($sin_prefix) {
            $select_category_html .= insert_seller_select_category(0, 0, 0, 'user_cat', 0, $table, [], $user_id);
        } else {
            $select_category_html .= insert_select_category(0, 0, 0, 'cat_id', 0, $table, $seller_shop_cat);
        }

        $GLOBALS['smarty']->assign($sin_prefix . 'select_category_html', $select_category_html);
    } /**
     * 编辑商品
     */ elseif ($_REQUEST['act'] == 'edit' || $_REQUEST['act'] == 'copy') {
        $goods = get_admin_goods_info($goods_id);

        $select_category_html = '';

        if ($sin_prefix) {
            $parent_cat_list = get_seller_select_category($cat_id, 1, true, $user_id);
            $cat_id = $goods['user_cat'];
        } else {
            $parent_cat_list = get_select_category($cat_id, 1, true);
            $cat_id = $goods['cat_id'];
        }

        for ($i = 0; $i < count($parent_cat_list); $i++) {
            if ($sin_prefix) {
                $select_category_html .= insert_seller_select_category(pos($parent_cat_list), next($parent_cat_list), $i, 'user_cat', 0, $table, [], $user_id);
            } else {
                $select_category_html .= insert_select_category(pos($parent_cat_list), next($parent_cat_list), $i, 'cat_id', 0, $table, $seller_shop_cat);
            }
        }
        $GLOBALS['smarty']->assign($sin_prefix . 'select_category_html', $select_category_html);
        $parent_and_rank = empty($cat_id) ? '0_0' : $cat_id . '_' . (count($parent_cat_list) - 2);
        $GLOBALS['smarty']->assign($sin_prefix . 'parent_and_rank', $parent_and_rank);
    }
}

/**
 * 会员信息
 */
function get_admin_user_info($id = 0)
{
    $res = Users::where('user_id', $id);
    $res = app(BaseRepository::class)->getToArrayFirst($res);

    return $res;
}

/**
 * 查询当前商品属性
 */
function get_dialog_goods_attr_type($attr_id = 0, $goods_id = 0)
{
    $res = GoodsAttr::where('attr_id', $attr_id)
        ->where('goods_id', $goods_id);

    $res = app(BaseRepository::class)->getToArrayGet($res);

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
 * 商家评分列表
 */
function seller_grade_list()
{
    $res = MerchantsShopInformation::where('merchants_audit', 1)
        ->orderBy('user_id');

    $res = app(BaseRepository::class)->getToArrayGet($res);

    return $res;
}

/**
 * 重新组合首字地区列表
 */
function get_pin_regions()
{
    $arr = [];

    $letters = range('A', 'Z');
    $pin_regions = app(BaseRepository::class)->getDiskForeverData('forever_pin_regions');

    if ($pin_regions !== false) {
        foreach ($letters as $key => $row) {
            if ($pin_regions) {
                foreach ($pin_regions as $pk => $prow) {
                    if (isset($prow['initial']) && $row == $prow['initial']) {
                        $arr[$row][$pk] = $prow;
                    }
                }
            }

            if (isset($arr[$row]) && $arr[$row]) {
                $arr[$row] = get_array_sort($arr[$row], 'region_id');
            }
        }
    }

    ksort($arr);
    return $arr;
}

/**
 * 更新、删除商品属性
 */
function get_updel_goods_attr($goods_id = 0)
{
    $admin_id = get_admin_id();

    if ($admin_id) {
        if ($goods_id) {
            GoodsAttr::where('admin_id', $admin_id)
                ->where('goods_id', 0)
                ->update(['goods_id' => $goods_id]);
        } else {
            GoodsAttr::where('admin_id', $admin_id)
                ->where('goods_id', 0)
                ->delete();
        }
    }
}

//获取商品的属性ID
function get_goods_attr_nameId($goods_id = 0, $attr_id = 0, $attr_value = '', $select = 'goods_attr_id', $type = 0)
{
    if ($type == 1) {
        $res = GoodsAttr::where('goods_id', $goods_id)
            ->where('goods_attr_id', $attr_id)
            ->value($select);
    } else {
        $res = GoodsAttr::where('goods_id', $goods_id)
            ->where('attr_id', $attr_id)
            ->where('attr_value', $attr_value)
            ->value($select);
    }

    return $res;
}

/* 商家地区 */

function get_seller_region($region = [], $ru_id = 0)
{
    $name = '';
    /* 取得区域名 */
    if ($region) {
        $province_name = Region::where('region_id', $region['province'])->value('region_name');
        $city_name = Region::where('region_id', $region['city'])->value('region_name');
        $district_name = Region::where('region_id', $region['district'])->value('region_name');

        $name = $province_name . " " . $city_name . " " . $district_name;
        $name = trim($name);
    } else {
        $res = SellerShopinfo::where('ru_id', $ru_id);

        $res = $res->with([
            'getRegionProvince' => function ($query) {
                $query->select('region_id', 'region_name as province_name');
            },
            'getRegionCity' => function ($query) {
                $query->select('region_id', 'region_name as city_name');
            },
            'getRegionDistrict' => function ($query) {
                $query->select('region_id', 'region_name as district_name');
            }
        ]);

        $res = app(BaseRepository::class)->getToArrayFirst($res);
        $res = $res ? app(BaseRepository::class)->getArrayMerge($res, $res['get_region_province']) : $res;
        $res = $res ? app(BaseRepository::class)->getArrayMerge($res, $res['get_region_city']) : $res;
        $res = $res ? app(BaseRepository::class)->getArrayMerge($res, $res['get_region_district']) : $res;

        if ($res) {
            $province_name = isset($res['province_name']) && $res['province_name'] ? $res['province_name'] : '';
            $city_name = isset($res['city_name']) && $res['city_name'] ? $res['city_name'] : '';
            $district_name = isset($res['district_name']) && $res['district_name'] ? $res['district_name'] : '';

            $name = $province_name . " " . $city_name . " " . $district_name;
            $name = trim($name);
        }
    }

    return $name;
}

/**
 * 删除商品复选属性ID
 */
function get_goods_unset_attr($goods_id = 0, $attr_arr = [])
{
    $arr = [];

    if ($attr_arr) {
        $where_select = [];

        if (empty($goods_id)) {
            $admin_id = get_admin_id();
            $where_select['admin_id'] = $admin_id;
        }

        $where_select['goods_id'] = $goods_id;

        foreach ($attr_arr as $key => $row) {
            if ($row) {
                $where_select['attr_value'] = $row[0];
                $attr_info = app(GoodsAttrService::class)->getGoodsAttrId($where_select, 2);

                if ($attr_info && $row[0] == $attr_info['attr_value']) {
                    unset($row);
                } else {
                    $arr[$key] = $row;
                }
            }
        }
    }

    return $arr;
}

/**
 * 获取商品运费模板
 */
function get_goods_transport_info($tid, $table = 'goods_transport')
{
    //获得商品的扩展信息
    $res = GoodsTransport::where('tid', $tid);
    $res = app(BaseRepository::class)->getToArrayFirst($res);

    return $res;
}

/**
 * 获取商品扩展信息
 */
function get_goods_extend($goods_id)
{
    //获得商品的扩展信息
    $res = GoodsExtend::where('goods_id', $goods_id);
    $res = app(BaseRepository::class)->getToArrayFirst($res);

    return $res;
}

/**
 * 图库管理
 *
 * @param int $type
 * @param int $id
 * @param array $select
 * @param string $id_name
 * @param string $order
 * @return mixed
 */
function get_goods_gallery_album($type = 0, $id = 0, $select = [], $id_name = 'album_id', $order = '')
{
    if ($type == 2 && $select) {
        $res = GalleryAlbum::whereRaw(1);
    } else {
        $res = GalleryAlbum::whereRaw(1);
    }

    $album_list = [];
    if ($id) {
        $res = $res->where($id_name, $id);

        if ($type == 1) {
            $album_list = app(BaseRepository::class)->getToArrayGet($res);
            if ($album_list) {
                foreach ($album_list as $key => $row) {
                    $album_list[$key]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
                }
            }
        } elseif ($type == 2) {
            $album_list = app(BaseRepository::class)->getToArrayFirst($res);
            if ($album_list) {
                //格式化图片
                $album_list['album_cover'] = $album_list['album_cover'] ? get_image_path($album_list['album_cover']) : '';
            }
        } else {
            $res = app(BaseRepository::class)->getToArrayFirst($res);

            if (count($select) > 1) {
                if ($res && $select) {
                    foreach ($select as $key => $val) {
                        $album_list[$val] = $res[$val];
                    }
                } else {
                    $album_list = $res;
                }
            } else {
                $album_list = $res[$select[0]] ?? '';
            }
        }
    } else {
        if ($type == 1) {
            $res = $res->where('ru_id', $id);
            $album_list = app(BaseRepository::class)->getToArrayGet($res);
            if ($album_list) {
                foreach ($album_list as $key => $row) {
                    $album_list[$key]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
                }
            }
        }
    }

    return $album_list;
}

/**
 * 图库管理图片信息
 */
function gallery_pic_album($type = 0, $id = 0, $select = [], $id_name = 'pic_id', $order = '')
{
    $res = PicAlbum::whereRaw(1);

    if ($id) {
        $res = $res->where($id_name, $id);
    }

    if ($type == 1) {
        $pic_list = app(BaseRepository::class)->getToArrayGet($res);

        if ($pic_list) {
            foreach ($pic_list as $key => $row) {
                $pic_list[$key]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
            }
        }
    } elseif ($type == 2) {
        $pic_list = app(BaseRepository::class)->getToArrayFirst($res);
    } else {
        $pic_list = value($select);
    }

    return $pic_list;
}

/*
 * 处理商家后台的分页效果
 * param    array     $list   产品列表
 * param    string    $nowpage   当前页数
 * param    number    $show   显示分页数量 默认超过10页则显示前后共10页
 * return   array     $arr    分页数量
 */

function seller_page($list, $nowpage, $show = '10')
{
    $arr = [];

    if ($list) {
        if ($show > $list['page_count']) {
            $show = $list['page_count']; //如显示分页条数大于总条数，则等于总条数
        }

        if ($show % 2 == 0) { //判断奇偶数
            $begin = $nowpage - ceil($show / 2);
            $end = $nowpage + floor($show / 2);
        } else {
            $begin = $nowpage - floor($show / 2);
            $end = $nowpage + ceil($show / 2);
        }

        if ($show > 1) {//显示分页数量大于1时做处理
            if ($nowpage > (ceil($show / 2) + 1) && $nowpage <= ($list['page_count'] - ceil($show / 2))) {
                for ($i = $begin; $i < $end; $i++) {
                    $arr[$i] = $i;
                }
            } else {
                if ($nowpage > (ceil($show / 2) + 1) && $nowpage > ($list['page_count'] - ($show - 1))) {
                    for ($i = $list['page_count'] - ($show - 1); $i <= $list['page_count']; $i++) {
                        $arr[$i] = $i;
                    }
                } else {
                    for ($i = 1; $i <= $show; $i++) {
                        $arr[$i] = $i;
                    }
                }
            }
        } else {
            $arr[1] = 1;
        }
    } else {
        $arr[1] = 1;
    }

    return $arr;
}


/*
 * 获取选中的分类
 */
function get_choose_cat($ids)
{
    $ids = app(BaseRepository::class)->getExplode($ids);

    $res = Category::whereIn('cat_id', $ids);
    $res = app(BaseRepository::class)->getToArrayGet($res);

    return $res;
}

/*
 * 获取选中的商品
 */
function get_choose_goods($ids)
{
    $ids = app(BaseRepository::class)->getExplode($ids);

    $res = Goods::whereIn('goods_id', $ids);
    $res = app(BaseRepository::class)->getToArrayGet($res);

    return $res;
}

/**
 * 资金管理日志
 */
function get_seller_account_log()
{
    $adminru = get_admin_ru_id();

    $result = get_filter();
    if ($result === false) {
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'change_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        $filter['user_id'] = isset($_REQUEST['ru_id']) && !empty($_REQUEST['ru_id']) ? intval($_REQUEST['ru_id']) : 0;

        $row = MerchantsAccountLog::whereRaw(1);

        if ($adminru['ru_id'] > 0) {
            $row = $row->where('user_id', $adminru['ru_id']);
        } elseif ($filter['user_id'] > 0) {
            $row = $row->where('user_id', $filter['user_id']);
        } elseif ($filter['keywords']) {
            $keywords = mysql_like_quote($filter['keywords']);
            $seller = MerchantsShopInformation::where('rz_shopName', 'like', '%' . $keywords . '%')
                ->orWhere('shop_class_keyWords', 'like', '%' . $keywords . '%');

            $user_id = $seller->value('user_id');

            $row = $row->where('user_id', $user_id);
        }

        //管理员查询的权限 -- 店铺查询 start
        $filter['store_search'] = empty($_REQUEST['store_search']) ? 0 : intval($_REQUEST['store_search']);
        $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
        $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';

        if ($filter['store_search'] != 0) {
            if ($adminru['ru_id'] == 0) {
                $filter['store_type'] = $_REQUEST['store_type'];
                $row = $row->whereHas('getMerchantsShopInformation', function ($query) use ($filter) {
                    $query = $query->where('merchants_audit', 1);

                    $store_keyword = mysql_like_quote($filter['store_keyword']);

                    if ($filter['store_search'] == 1) {
                        $query->where('user_id', $filter['user_id']);
                    } elseif ($filter['store_search'] == 2) {
                        $query = $query->where('rz_shopName', '%' . $store_keyword . '%');
                        $query->where('user_id', '>', 0);
                    } elseif ($filter['store_search'] == 3) {
                        $query = $query->where('shoprz_brandName', '%' . $store_keyword . '%');

                        if ($filter['store_type']) {
                            $query = $query->where('shopNameSuffix', $filter['store_type']);
                        }

                        $query->where('user_id', '>', 0);
                    }
                });
            }
        }
        //管理员查询的权限 -- 店铺查询 end

        $res = $record_count = $row;

        $filter['record_count'] = $record_count->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

        if ($filter['start'] > 0) {
            $res = $res->skip($filter['start']);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $res = app(BaseRepository::class)->getToArrayGet($res);

        $filter['keywords'] = stripslashes($filter['keywords']);
    } else {
        $filter = $result['filter'];
        $res = [];
    }

    $arr = [];
    if ($res) {
        for ($i = 0; $i < count($res); $i++) {
            $res[$i]['shop_name'] = app(MerchantCommonService::class)->getShopName($res[$i]['user_id'], 1);
            $res[$i]['change_time'] = local_date($GLOBALS['_CFG']['time_format'], $res[$i]['change_time']);
        }
    }

    $arr = ['log_list' => $res, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

    return $arr;
}

/**
 * 商品信息
 * 进入添加或编辑商品时
 * 删除goods_id为0的商品相关表信息
 * admin_id 管理员ID
 * $type 0 删除 1 更新
 */
function get_del_update_goods_null($goods_id = 0, $type = 0)
{
    $admin_id = get_admin_id();
    $table_list = [
        'products' => Products::whereRaw(1), //商品货品表
        'products_warehouse' => ProductsWarehouse::whereRaw(1), //商品仓库货品表
        'products_area' => ProductsArea::whereRaw(1), //商品地区货品表
        'goods_attr' => GoodsAttr::whereRaw(1), //商品属性表
        'warehouse_attr' => WarehouseAttr::whereRaw(1), //商品仓库属性表
        'warehouse_area_attr' => WarehouseAreaAttr::whereRaw(1) //商品地区属性表
    ];

    foreach ($table_list as $key => $table) {
        if ($type) {
            $other['goods_id'] = $goods_id;

            $table->where('goods_id', 0)
                ->where('admin_id', $admin_id)
                ->update($other);
        } else {
            $table->where('goods_id', 0)
                ->where('admin_id', $admin_id)
                ->delete();
        }
    }
}

/**
 * 编辑商品
 * 删除添加商品的图片
 */
function get_del_edit_goods_img($goods_id, $result = [])
{
    /* 如果有上传图片，删除原来的商品图 */
    $row = Goods::select('goods_thumb', 'goods_img', 'original_img')
        ->where('goods_id', $goods_id);
    $row = app(BaseRepository::class)->getToArrayFirst($row);

    $arr_img = [];

    if ($row) {
        if ($result['data']['goods_thumb'] && $row['goods_thumb'] != $result['data']['goods_thumb'] && strpos($row['goods_thumb'], "data/gallery_album") === false) {
            if ($row['goods_thumb']) {
                dsc_unlink(storage_public($row['goods_thumb']));
                $arr_img[] = $row['goods_thumb'];
            }
        }

        if ($result['data']['goods_img'] && $row['goods_img'] != $result['data']['goods_img'] && strpos($row['goods_img'], "data/gallery_album") === false) {
            if ($row['goods_img']) {
                dsc_unlink(storage_public($row['goods_img']));
                $arr_img[] = $row['goods_img'];
            }
        }

        if ($result['data']['original_img'] && $row['original_img'] != $result['data']['original_img'] && strpos($row['original_img'], "data/gallery_album") === false) {
            if ($row['original_img']) {
                dsc_unlink(storage_public($row['original_img']));
                $arr_img[] = $row['original_img'];
            }
        }

        app(DscRepository::class)->getOssDelFile($arr_img);
    }
}

/**
 * 删除添加商品的图片
 * 商品并没有保存
 */
function get_del_goodsimg_null()
{
    $admin_id = get_admin_id();
    $goods_list = session()->get('goods.' . $admin_id);

    if (session()->has('goods.' . $admin_id) && $goods_list) {
        foreach ($goods_list as $key => $row) {
            $count = Goods::where('original_img', $row['original_img'])
                ->orWhere('goods_img', $row['goods_img'])
                ->orWhere('goods_thumb', $row['goods_img']);

            $count = $count->count();

            if ($key == 0 && !$count) {
                if ($row['original_img'] && strpos($row['original_img'], "data/gallery_album") === false) {
                    $arr_img[] = $row['original_img'];
                }
                if ($row['goods_img'] && strpos($row['goods_img'], "data/gallery_album") === false) {
                    $arr_img[] = $row['goods_img'];
                }
                if ($row['goods_thumb'] && strpos($row['goods_thumb'], "data/gallery_album") === false) {
                    $arr_img[] = $row['goods_thumb'];
                }

                $arr_img ? app(DscRepository::class)->getOssDelFile($arr_img) : [];
            }
        }

        session()->forget('goods.' . $admin_id);
    } else {
        if (session('goods') && empty(session('goods'))) {
            session()->forget('goods');
        }
    }
}

/**
 * 删除添加商品的相册图片
 * 商品并没有保存
 */
function get_del_goods_gallery()
{
    $admin_id = get_admin_id();

    if (session()->get('thumb_img_id' . $admin_id)) {

        $where = [
            'img_id' => session()->get('thumb_img_id' . $admin_id)
        ];
        $res = app(GoodsGalleryService::class)->getGalleryList($where);

        //删除图片
        if (!empty($res)) {
            foreach ($res as $k) {
                $arr_img = [];
                if ($k['img_url'] && strpos($k['img_url'], "data/gallery_album") === false) {
                    $arr_img[] = $k['img_url'];
                }
                if ($k['thumb_url'] && strpos($k['thumb_url'], "data/gallery_album") === false) {
                    $arr_img[] = $k['thumb_url'];
                }
                if ($k['img_original'] && strpos($k['img_original'], "data/gallery_album") === false) {
                    $arr_img[] = $k['img_original'];
                }

                $arr_img ? app(DscRepository::class)->getOssDelFile($arr_img) : [];
            }
        }

        GoodsGallery::where('goods_id', 0)
            ->whereIn('img_id', session()->get('thumb_img_id' . $admin_id))
            ->delete();

        session()->forget('thumb_img_id' . $admin_id);
    }
}

/**
 * 删除添加商品的主图视频
 * 商品并没有保存
 */
function get_del_goods_video()
{
    $admin_id = get_admin_id();
    $goods_video = session('goods_video.' . $admin_id);

    if ($goods_video) {

        $arr = [];
        foreach ($goods_video as $key => $val) {
            $arr[] = $val;
        }

        if ($arr) {
            app(DscRepository::class)->getOssDelFile($arr);
        }

        session()->forget('goods_video.' . $admin_id); //清楚临时
    }
}

/**
 * 检测商家或会员
 * 可用金额
 * 冻结金额
 * 消费积分
 * 等级积分
 * 是否满足条件
 * @param int $left_money
 * @param int $rigth_money
 * @return int
 */
function get_return_money($left_money = 0, $rigth_money = 0)
{
    $money = $left_money;
    if ($left_money <= 0) {
        if (!(strpos($left_money, "-") === false)) {
            $new_frozen_money = substr($left_money, 1);
            if ($rigth_money) {
                if ($rigth_money <= 0 || $new_frozen_money > $rigth_money) {
                    $money = 0;
                }
            }
        }
    }

    return $money;
}

//获取指定数组的下级分类
function get_type_cat_arr($cat_id = 0, $type = 0, $arr = 0, $ru_id = '')
{
    $adminru = get_admin_ru_id();

    if ($type == 2) {
        $cat_list = GoodsTypeCat::where('cat_id', $cat_id);
    } else {
        $cat_list = GoodsTypeCat::where('parent_id', $cat_id);
    }

    if ($cat_id == 0) {
        if (is_numeric($ru_id) && $GLOBALS['_CFG']['attr_set_up'] == 1) {
            $cat_list = $cat_list->where('user_id', $ru_id)
                ->where('suppliers_id', 0);
        } elseif ($ru_id == '' && $GLOBALS['_CFG']['attr_set_up'] == 1) {
            if ($adminru['suppliers_id'] > 0) {
                $cat_list = $cat_list->where('user_id', 0)
                    ->where('suppliers_id', $adminru['suppliers_id']);
            } else {
                $cat_list = $cat_list->where('user_id', $adminru['ru_id'])
                    ->where('suppliers_id', 0);
            }
        } elseif ($GLOBALS['_CFG']['attr_set_up'] == 0) {
            $cat_list = $cat_list->where('user_id', 0)
                ->where('suppliers_id', 0);
        }
    }

    if ($type == 2) {
        $cat_list = app(BaseRepository::class)->getToArrayFirst($cat_list);
    } else {
        $cat_list = app(BaseRepository::class)->getToArrayGet($cat_list);
    }

    if ($type == 1) {
        $cat_string = $cat_id . ',';
        if (!empty($cat_list)) {
            foreach ($cat_list as $k => $v) {
                $cat_string .= get_type_cat_arr($v['cat_id'], 1);
            }
        }

        if ($arr == 1) {
            $cat_string = substr($cat_string, 0, strlen($cat_string) - 1);
        }

        return $cat_string;
    } elseif ($type == 2) {
        if ($cat_list) {
            if ($cat_list['parent_id'] > 0) {
                $cat_tree = GoodsTypeCat::where('parent_id', $cat_list['parent_id']);

                if ($cat_id == 0) {
                    if (is_numeric($ru_id) && $GLOBALS['_CFG']['attr_set_up'] == 1) {
                        $cat_tree = $cat_tree->where('user_id', $ru_id);
                    } elseif ($ru_id == '' && $GLOBALS['_CFG']['attr_set_up'] == 1) {
                        $cat_tree = $cat_tree->where('user_id', $adminru['ru_id']);
                    } elseif ($GLOBALS['_CFG']['attr_set_up'] == 0) {
                        $cat_tree = $cat_tree->where('user_id', 0);
                    }
                }

                $cat_tree = app(BaseRepository::class)->getToArrayGet($cat_tree);

                return ['checked_id' => $cat_list['parent_id'], 'arr' => $cat_tree];
            } else {
                return ['checked_id' => $cat_id, 'arr' => ''];
            }
        }
    } else {
        return $cat_list;
    }
}

function getCatNun($cat_keys = [])
{
    $adminru = get_admin_ru_id();

    if (!empty($cat_keys)) {
        $cat_keys = app(BaseRepository::class)->getExplode($cat_keys);
        $count = GoodsType::whereIn('c_id', $cat_keys);

        if ($adminru['ru_id'] > 0) {
            $count = $count->where('user_id', $adminru['ru_id']);
        }

        if ($adminru['suppliers_id'] > 0) {
            $count = $count->where('user_id', 0)
                ->where('suppliers_id', $adminru['suppliers_id']);
        } else {
            if ($adminru['ru_id'] > 0) {
                $count = $count->where('user_id', $adminru['ru_id'])
                    ->where('suppliers_id', 0);
            }
        }

        $count = $count->count();
    } else {
        $count = 0;
    }

    return $count;
}

/**
 * 获得所有商品类型
 *
 * @access  public
 * @return  array
 */
function get_typecat($level = 1)
{
    $adminru = get_admin_ru_id();
    $result = get_filter();
    if ($result === false) {
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'cat_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['parent_id'] = empty($_REQUEST['parent_id']) ? 0 : intval($_REQUEST['parent_id']);
        $filter['level'] = $level;
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        $filter['seller_list'] = isset($_REQUEST['seller_list']) && !empty($_REQUEST['seller_list']) ? 1 : 0;  //商家和自营订单标识

        $row = GoodsTypeCat::where('level', $filter['level']);

        if ($adminru['suppliers_id'] > 0) {
            $row = $row->where('suppliers_id', $adminru['suppliers_id']);
        } else {
            if ($adminru['ru_id'] > 0 && $GLOBALS['_CFG']['attr_set_up'] == 1) {
                $row = $row->where('user_id', $adminru['ru_id']);
            } elseif ($GLOBALS['_CFG']['attr_set_up'] == 0) {
                $row = $row->where('user_id', 0);
            }
        }

        if ($filter['keywords']) {
            $keywords = mysql_like_quote($filter['keywords']);
            $row = $row->where('cat_name', '%' . $keywords . '%');
        }
        if ($filter['parent_id'] > 0) {
            $row = $row->where('parent_id', $filter['parent_id']);
        }

        if ($adminru['ru_id'] == 0) {
            if (!empty($filter['seller_list'])) {
                $row = $row->where('user_id', '>', 0);
            } else {
                $row = $row->where('user_id', 0);
            }
        }

        $row = $row->groupBy('cat_id');

        $res = $record_count = $row;

        /* 记录总数以及页数 */
        $record_count = app(BaseRepository::class)->getToArrayGet($record_count);
        $filter['record_count'] = $record_count ? collect($record_count)->count() : 0;

        $filter = page_and_size($filter);

        $res = $res->with([
            'getGoodsTypeCatParent' => function ($query) {
                $query->select('cat_id', 'cat_name');
            }
        ]);

        /* 查询记录 */

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

        if ($filter['start'] > 0) {
            $res = $res->skip($filter['start']);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $res = app(BaseRepository::class)->getToArrayGet($res);
    } else {
        $sql = $result['sql'];
        $filter = $result['filter'];
        $res = [];
    }

    if ($res) {
        foreach ($res as $key => $val) {
            $res[$key]['shop_name'] = app(MerchantCommonService::class)->getShopName($val['user_id'], 1);

            //获取父级分类
            $res[$key]['parent_name'] = $val['get_goods_type_cat_parent'] ? $val['get_goods_type_cat_parent']['cat_name'] : '';

            //获取分类下的所有子分类
            $cat_keys = get_type_cat_arr($val['cat_id'], 1, 1);
            $res[$key]['type_num'] = getCatNun($cat_keys);
        }
    }

    return ['type' => $res, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
}

/**
 * 获得店铺信息修改记录
 */
function get_seller_shopinfo_changelog($ru_id = 0)
{
    $changelog = SellerShopinfoChangelog::where('ru_id', $ru_id);
    $changelog = app(BaseRepository::class)->getToArrayGet($changelog);

    $diff_data = [];
    if ($changelog) {
        foreach ($changelog as $key => $val) {
            $diff_data[$val['data_key']] = $val['data_value'];
        }
    }

    return $diff_data;
}

/**
 * wang 商家入驻 生成百度编辑器
 * @param string  input_name  输入框名称
 * @param string  input_value 输入框值
 * @param string $input_height 输入大小
 * @param string $type 输出方式  0、assign输出，1、return输出
 */
function create_ueditor_editor($input_name, $input_value = '', $input_height = 486, $type = 0)
{
    if (session()->has('seller_id') && session('seller_id')) {
        $path = SELLER_PATH;
    } else {
        $path = ADMIN_PATH;
    }

    $FCKeditor = '<input type="hidden" id="' . $input_name . '" name="' . $input_name . '" value="' . htmlspecialchars($input_value) . '" /><iframe id="' . $input_name . '_frame" src="' . __ROOT__ . $path . "/" . "editor.php?item=" . $input_name . '" width="100%" height="' . $input_height . '" frameborder="0" scrolling="no"></iframe>';

    if ($type == 1) {
        return $FCKeditor;
    } else {
        $GLOBALS['smarty']->assign('FCKeditor', $FCKeditor);
    }
}

/**
 * 获取商品仓库价格
 */
function get_goods_warehouse_area_list($goods_id = 0, $model = 0, $warehouse_id = 0)
{
    $result = get_filter();
    if ($result === false) {
        /* 过滤条件 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }

        $filter['goods_id'] = !isset($_REQUEST['goods_id']) ? $goods_id : intval($_REQUEST['goods_id']);
        $filter['warehouse_id'] = !isset($_REQUEST['warehouse_id']) ? $warehouse_id : intval($_REQUEST['warehouse_id']);
        $filter['model'] = !isset($_REQUEST['model']) ? $model : intval($_REQUEST['model']);
        $filter['region_sn'] = !isset($_REQUEST['region_sn']) ? '' : addslashes(trim($_REQUEST['region_sn']));

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'region_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        if ($filter['model'] == 1) {
            $row = WarehouseGoods::where('goods_id', $filter['goods_id']);

            if ($filter['region_sn']) {
                $row = $row->where('region_sn', $filter['region_sn']);
            }

            $row = $row->whereHas('getRegionWarehouse', function ($query) {
                $query->where('region_type', 0);
            });

            $row = $row->with([
                'getRegionWarehouse'
            ]);
        } elseif ($filter['model'] == 2) {
            $row = WarehouseAreaGoods::where('goods_id', $filter['goods_id']);

            $row = $row->whereHas('getRegionWarehouse', function ($query) use ($filter) {
                $query->where('region_type', 1);

                if ($filter['warehouse_id']) {
                    $query->where('parent_id', $filter['warehouse_id']);
                }
            });

            if ($filter['region_sn']) {
                $row = $row->where('region_sn', $filter['region_sn']);
            }

            $row = $row->with([
                'getRegionWarehouse' => function ($query) {
                    $query->with('getRegionWarehouse');
                },
                'getRegionWarehouseCity'
            ]);
        }

        $res = $record_count = $row;

        $filter['record_count'] = $record_count->count();

        /* 分页大小 */
        $filter = page_and_size($filter, 1);

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

        if ($filter['start'] > 0) {
            $res = $res->skip($filter['start']);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $res = app(BaseRepository::class)->getToArrayGet($res);

        $filter['keywords'] = stripslashes($filter['keywords']);
    } else {
        $sql = $result['sql'];
        $filter = $result['filter'];
        $res = [];
    }

    if ($res) {
        foreach ($res as $key => $row) {
            $row = app(BaseRepository::class)->getArrayMerge($row, $row['get_region_warehouse']);

            $row['warehouse_name'] = isset($row['get_region_warehouse']['get_region_warehouse']) && $row['get_region_warehouse']['get_region_warehouse'] ? $row['get_region_warehouse']['get_region_warehouse']['region_name'] : '';
            $row['city_name'] = isset($row['get_region_warehouse_city']) && $row['get_region_warehouse_city'] ? $row['get_region_warehouse_city']['region_name'] : '';

            $res[$key] = $row;
        }
    }

    $arr = ['list' => $res, 'filter' => $filter,
        'page_count' => $filter['page_count'], 'record_count' => $filter['record_count'], 'query' => "goods_wa_query"];

    return $arr;
}

/**
 * 获取商品模式
 * model
 */
function get_goods_model($goods_id = 0)
{
    $res = Goods::select('goods_id', 'goods_sn', 'model_attr', 'user_id')
        ->where('goods_id', $goods_id);
    $res = app(BaseRepository::class)->getToArrayFirst($res);

    return $res;
}

/**
 * 获取商品货品SKU列表
 */
function get_goods_product_list($goods_id = 0, $model = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $is_pagging = true)
{
    $result = get_filter();
    if ($result === false) {
        /* 过滤条件 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }

        $filter['product_sn'] = !isset($_REQUEST['product_sn']) ? '' : addslashes(trim($_REQUEST['product_sn']));
        $filter['goods_id'] = !isset($_REQUEST['goods_id']) ? $goods_id : intval($_REQUEST['goods_id']);
        $filter['model'] = !isset($_REQUEST['model']) ? $model : intval($_REQUEST['model']);

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'product_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        if ($filter['model'] == 1) {
            $filter['warehouse_id'] = !isset($_REQUEST['warehouse_id']) ? $warehouse_id : intval($_REQUEST['warehouse_id']);

            $row = ProductsWarehouse::where('warehouse_id', $filter['warehouse_id']);
        } elseif ($filter['model'] == 2) {
            $filter['area_id'] = isset($_REQUEST['area_id']) && !empty($_REQUEST['area_id']) ? intval($_REQUEST['area_id']) : $area_id;
            $filter['city_id'] = isset($_REQUEST['city_id']) && !empty($_REQUEST['city_id']) ? intval($_REQUEST['city_id']) : $area_city;

            $row = ProductsArea::where('area_id', $filter['area_id']);

            if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
                $row = $row->where('city_id', $filter['city_id']);
            }
        } else {
            $row = Products::whereRaw(1);
        }

        if ($filter['product_sn']) {
            $row = $row->where('product_sn', $filter['product_sn']);
        }

        $row = $row->where('goods_id', $filter['goods_id']);

        $res = $record_count = $row;

        $filter['record_count'] = $record_count->count();

        /* 分页大小 */
        $filter = page_and_size($filter, 1);

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

        if ($is_pagging) {
            if ($filter['start'] > 0) {
                $res = $res->skip($filter['start']);
            }

            if ($filter['page_size'] > 0) {
                $res = $res->take($filter['page_size']);
            }
        }

        $res = app(BaseRepository::class)->getToArrayGet($res);

        $filter['keywords'] = stripslashes($filter['keywords']);
    } else {
        $filter = $result['filter'];
        $res = [];
    }

    $arr = [];
    if ($res) {
        for ($i = 0; $i < count($res); $i++) {
            if ($res[$i]['goods_attr']) {
                $goods_attr_id = str_replace('|', ',', $res[$i]['goods_attr']);
                $goods_attr_id = app(BaseRepository::class)->getExplode($goods_attr_id);

                $attr_value = GoodsAttr::select('attr_value')
                    ->where('goods_id', $res[$i]['goods_id'])->whereIn('goods_attr_id', $goods_attr_id);
                $attr_value = app(BaseRepository::class)->getToArrayGet($attr_value);

                $res[$i]['attr_value'] = get_goods_attr_value($attr_value);

                if (empty(trim($res[$i]['attr_value']))) {

                    if ($filter['model'] == 1) {
                        $delete = ProductsWarehouse::whereRaw(1);
                    } elseif ($filter['model'] == 2) {
                        $delete = ProductsArea::whereRaw(1);
                    } else {
                        $delete = Products::whereRaw(1);
                    }

                    $delete->where('goods_attr', $res[$i]['goods_attr'])
                        ->where('goods_id', $res[$i]['goods_id'])
                        ->delete();

                    unset($res[$i]);
                } else {
                    $arr[] = $res[$i];
                }
            }
        }
    }


    if ($is_pagging) {
        return [
            'product_list' => $res,
            'filter' => $filter,
            'page_count' => $filter['page_count'],
            'record_count' => $filter['record_count'],
            'query' => "sku_query"
        ];
    } else {
        return $arr;
    }
}

function get_goods_attr_value($attr_value)
{
    $str = "";
    if ($attr_value) {
        foreach ($attr_value as $key => $val) {
            $str .= "【" . $val['attr_value'] . "】";
        }
    }

    return $str;
}

/**
 * 检查图片网址是否合法
 *
 * @param string $url 网址
 *
 * @return boolean
 */
function goods_parse_url($url)
{
    $parse_url = @parse_url($url);
    return (!empty($parse_url['scheme']) && !empty($parse_url['host']));
}

/* 获取区域商品 */

function get_area_goods($goods_id)
{
    $res = LinkAreaGoods::select('region_id')
        ->where('goods_id', $goods_id);
    $res = $res->with('getRegionWarehouse');

    $res = app(BaseRepository::class)->getToArrayGet($res);

    if ($res) {
        foreach ($res as $key => $row) {
            $row = app(BaseRepository::class)->getArrayMerge($row, $row['get_region_warehouse']);

            $res[$key] = $row;
        }
    }

    return $res;
}

/**
 * 数组合并
 *
 * @param array $array1 数组1
 * @param array $array2 数组2
 *
 * @return array
 */
function my_array_merge($array1, $array2)
{
    $new_array = $array1;
    foreach ($array2 as $key => $val) {
        $new_array[$key] = $val;
    }
    return $new_array;
}

/* 获取管理员操作记录 */
function get_goods_change_logs($goods_id)
{
    load_helper('order');

    /* 过滤条件 */
    $result = get_filter();

    if ($result === false) {
        $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }

        $filter['start_time'] = empty($_REQUEST['start_time']) ? '' : trim($_REQUEST['start_time']);
        $filter['end_time'] = empty($_REQUEST['end_time']) ? '' : trim($_REQUEST['end_time']);

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'log_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['goodsId'] = empty($_REQUEST['goodsId']) ? 0 : intval($_REQUEST['goodsId']);

        $filter['operation_type'] = !isset($_REQUEST['operation_type']) ? -1 : intval($_REQUEST['operation_type']);

        //查询条件
        $where = " WHERE 1 ";

        $row = GoodsChangeLog::whereRaw(1);

        /* 商品ID */
        if (!empty($goods_id)) {
            $row = $row->where('goods_id', $goods_id);
        } else {
            return [];
        }

        $row = $row->whereHas('getGoods', function ($query) use ($filter) {

            /* 关键字 */
            if (!empty($filter['keyword'])) {
                $keyword = mysql_like_quote($filter['keyword']);

                $query->where('goods_name', '%' . $keyword . '%');
            }
        });

        /* 操作时间 */
        if (!empty($filter['start_time']) || !empty($filter['end_time'])) {
            $filter['start_time'] = local_strtotime($filter['start_time']);
            $filter['end_time'] = local_strtotime($filter['end_time']);

            $row = $row->where('handle_time', '>', $filter['start_time'])
                ->where('handle_time', '<', $filter['end_time']);
        }

        $res = $record_count = $row;

        /* 获得总记录数据 */
        $filter['record_count'] = $record_count->count();

        $filter = page_and_size($filter);

        /* 获取管理员日志记录 */
        $res = $res->with([
            'getAdminUser' => function ($query) {
                $query->select('user_id', 'user_name AS admin_name');
            }
        ]);

        $res = $res->groupBy('log_id');

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

        if ($filter['start'] > 0) {
            $res = $res->skip($filter['start']);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $res = app(BaseRepository::class)->getToArrayGet($res);

        $filter['keyword'] = stripslashes($filter['keyword']);
    } else {
        $filter = $result['filter'];
        $res = [];
    }

    $list = [];
    if ($res) {
        foreach ($res as $rows) {
            $rows = app(BaseRepository::class)->getArrayMerge($rows, $rows['get_admin_user']);

            $rows['shop_price'] = price_format($rows['shop_price']);
            $rows['shipping_fee'] = price_format($rows['shipping_fee']);
            $rows['handle_time'] = local_date($GLOBALS['_CFG']['time_format'], $rows['handle_time']);

            $list[] = $rows;
        }
    }

    return ['list' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
}

function is_distribution($ru_id = 0)
{
    if ($ru_id == 0) {
        return true; // 平台默认
    }

    $is_distribution = MerchantsStepsFields::where('user_id', $ru_id)->value('is_distribution');

    if (empty($is_distribution) || $is_distribution == '否') {
        return false;
    } elseif (isset($is_distribution) && $is_distribution == '是') {
        return true;
    }
}

//ecmoban模板堂 --zhuo end

function is_mer($goods_id = 0)
{
    $user_id = Goods::where('goods_id', $goods_id)->value('user_id');

    if ($user_id == 0) {
        return false;
    } else {
        return $user_id;
    }
}

/**
 * 修改商品库存
 * @param string $goods_id 商品编号，可以为多个，用 ',' 隔开
 * @param string $value 字段值
 * @return  bool
 */
function update_goods_stock($goods_id, $value, $warehouse_id = 0)
{
    if ($goods_id) {
        $time = gmtime();
        $result = WarehouseGoods::where('goods_id', $goods_id)
            ->where('region_id', $warehouse_id)
            ->increment('region_number', $value, ['last_update' => $time]);

        /* 清除缓存 */
        clear_cache_files();

        return $result;
    } else {
        return false;
    }
}

/**
 * 保存某商品的优惠价格
 * @param int $goods_id 商品编号
 * @param array $number_list 优惠数量列表
 * @param array $price_list 价格列表
 * @return  void
 */
function handle_volume_price($goods_id, $is_volume, $number_list, $price_list, $id_list)
{
    if ($is_volume) {
        /* 循环处理每个优惠价格 */
        foreach ($price_list as $key => $price) {
            /* 价格对应的数量上下限 */
            $volume_number = $number_list[$key];
            $volume_id = isset($id_list[$key]) && !empty($id_list[$key]) ? $id_list[$key] : 0;

            $where = [
                'volume_price' => $price,
                'volume_number' => $volume_number
            ];

            if (!empty($price)) {
                if ($volume_id) {
                    $other = [
                        'volume_number' => $volume_number,
                        'volume_price' => $price
                    ];
                    VolumePrice::where('id', $volume_id)
                        ->update($other);
                } else {
                    $count = VolumePrice::where('goods_id', $goods_id)
                        ->where(function ($query) use ($where) {
                            $query->where('volume_price', $where['volume_price'])
                                ->orWhere('volume_number', $where['volume_number']);
                        });

                    $count = $count->count();

                    if ($count <= 0) {
                        $other = [
                            'price_type' => 1,
                            'goods_id' => $goods_id,
                            'volume_number' => $volume_number,
                            'volume_price' => $price
                        ];

                        VolumePrice::insert($other);
                    }
                }
            }
        }
    } else {
        VolumePrice::where('price_type', 1)
            ->where('goods_id', $goods_id)
            ->delete();
    }
}

/*获取订单store_id*/
function get_store_id($order_id = 0)
{
    $store_id = StoreOrder::where('order_id', $order_id)
        ->value('store_id');

    return $store_id;
}

// 用户退货时  退回赠送积分  by kong  start
function return_integral_rank($ret_id = 0, $user_id = 0, $order_sn = 0, $rec_id = 0, $refound_pay_points = 0)
{
    if ($refound_pay_points > 0) {
        /* 插入帐户变动记录 */
        $account_log = [
            'user_id' => $user_id,
            'pay_points' => $refound_pay_points,
            'change_time' => gmtime(),
            'change_desc' => sprintf($GLOBALS['_LANG']['return_order_integral'], $order_sn),
            'change_type' => ACT_OTHER
        ];

        AccountLog::insert($account_log);

        $update_log = [
            'pay_points' => \Illuminate\Support\Facades\DB::raw("pay_points  + ('$refound_pay_points')")
        ];

        Users::where('user_id', $user_id)->update($update_log);
    }

    $res = OrderReturn::where('ret_id', $ret_id);

    $res = $res->whereHas('getOrderGoods', function ($query) use ($rec_id) {
        $query->where('rec_id', $rec_id);
    });

    $res = $res->with([
        'getGoods',
        'getOrderReturnExtend',
        'getOrderGoods'
    ]);

    $res = app(BaseRepository::class)->getToArrayFirst($res);

    if ($res) {
        $goods = $res['get_goods'] ? $res['get_goods'] : [];
        $return_extend = $res['get_order_return_extend'] ? $res['get_order_return_extend'] : [];
        $order_goods = $res['get_order_goods'] ? $res['get_order_goods'] : [];

        $return_extend['return_number'] = isset($return_extend['return_number']) ? $return_extend['return_number'] : 0;

        $give_integral = 0;
        if ($goods && $goods['give_integral'] != -1) {
            $give_integral = $goods['give_integral'] * $return_extend['return_number'];
        } elseif ($order_goods) {
            $give_integral = $order_goods['goods_price'] * $return_extend['return_number'];
        }

        $res['give_integral'] = $give_integral;

        $rank_integral = 0;
        if ($goods && $goods['rank_integral'] != -1) {
            $rank_integral = $goods['rank_integral'] * $return_extend['return_number'];
        } elseif ($order_goods) {
            $rank_integral = $order_goods['goods_price'] * $return_extend['return_number'];
        }

        $res['rank_integral'] = $rank_integral;
    }

    $give_integral = $res ? $res['give_integral'] : 0;
    $rank_integral = $res ? $res['rank_integral'] : 0;
//    if (!empty($return_integral)) {
    log_account_change($user_id, 0, 0, -1 * $rank_integral, -1 * $give_integral, sprintf($GLOBALS['_LANG']['return_order_gift_integral'], $order_sn), ACT_OTHER, 1);
    return;
//    }
}

// 用户退货时  退回赠送积分  by kong  end

function get_warehouse_area_goods($region_id = 0, $goods_id = 0, $table = 'warehouse_goods')
{
    if ($table = 'warehouse_goods') {
        $row = WarehouseGoods::whereRaw(1);
    } else {
        $row = WarehouseAreaGoods::whereRaw(1);
    }

    $row = $row->where('region_id', $region_id)
        ->where('goods_id', $goods_id);

    $region_number = $row->value('region_number');

    return $region_number;
}

/**
 * 编辑商品
 * 删除添加商品的图片
 */
function lib_get_del_edit_goods_img($goods_id)
{
    /* 如果有上传图片，删除原来的商品图 */
    $row = GoodsLib::where('goods_id', $goods_id);
    $row = app(BaseRepository::class)->getToArrayFirst($row);

    $arr_img = [];
    if ($row) {
        if ($row['goods_thumb'] && strpos($row['goods_thumb'], "data/gallery_album") === false) {
            dsc_unlink(storage_public($row['goods_thumb']));
            $arr_img[] = $row['goods_thumb'];
        }

        if ($row['goods_img'] && strpos($row['goods_img'], "data/gallery_album") === false) {
            dsc_unlink(storage_public($row['goods_img']));
            $arr_img[] = $row['goods_img'];
        }

        if ($row['original_img'] && strpos($row['original_img'], "data/gallery_album") === false) {
            dsc_unlink(storage_public($row['original_img']));
            $arr_img[] = $row['original_img'];
        }

        app(DscRepository::class)->getOssDelFile($arr_img);
    }
}

/**
 * 删除添加商品的图片
 * 商品并没有保存
 */
function lib_get_del_goodsimg_null()
{
    $admin_id = get_admin_id();
    $goods_lib = session()->get('goods_lib.' . $admin_id);

    if (session()->has('goods_lib.' . $admin_id) && $goods_lib) {

        foreach ($goods_lib as $key => $row) {
            $count = GoodsLib::where('original_img', $row['original_img'])
                ->orWhere('goods_img', $row['goods_img'])
                ->orWhere('goods_thumb', $row['goods_thumb'])
                ->count();

            if ($key == 0 && !$count) {
                if ($row['original_img'] && strpos($row['original_img'], "data/gallery_album") === false) {
                    dsc_unlink(storage_public($row['original_img']));
                    $arr_img[] = $row['original_img'];
                }
                if ($row['goods_img'] && strpos($row['goods_img'], "data/gallery_album") === false) {
                    dsc_unlink(storage_public($row['goods_img']));
                    $arr_img[] = $row['goods_img'];
                }
                if ($row['goods_thumb'] && strpos($row['goods_thumb'], "data/gallery_album") === false) {
                    dsc_unlink(storage_public($row['goods_thumb']));
                    $arr_img[] = $row['goods_thumb'];
                }

                app(DscRepository::class)->getOssDelFile($arr_img);
            }
        }

        session()->forget('goods_lib.' . $admin_id);
    } else {
        if (session()->has('goods_lib') && empty(session('goods_lib'))) {
            session()->forget('goods_lib');
        }
    }
}

/**
 * 删除添加商品的相册图片
 * 商品并没有保存
 */
function lib_get_del_goods_gallery()
{
    $admin_id = get_admin_id();

    if (session('thumb_img_id.' . session('admin_id'))) {

        /* 删除未绑定图片 */
        $res = GoodsLibGallery::where('goods_id', 0)
            ->whereIn('img_id', session('thumb_img_id.' . session('admin_id')));

        $res = app(BaseRepository::class)->getToArrayGet($res);

        //删除图片
        if (!empty($res)) {
            foreach ($res as $k) {
                if ($k['img_url'] && strpos($k['img_url'], "data/gallery_album") === false) {
                    dsc_unlink(storage_public($k['img_url']));
                    $arr_img[] = $k['img_url'];
                }
                if ($k['thumb_url'] && strpos($k['thumb_url'], "data/gallery_album") === false) {
                    dsc_unlink(storage_public($k['thumb_url']));
                    $arr_img[] = $k['thumb_url'];
                }
                if ($k['img_original'] && strpos($k['img_original'], "data/gallery_album") === false) {
                    dsc_unlink(storage_public($k['img_original']));
                    $arr_img[] = $k['img_original'];
                }

                app(DscRepository::class)->getOssDelFile($arr_img);
            }
        }

        GoodsLibGallery::where('goods_id', 0)
            ->whereIn('img_id', session('thumb_img_id.' . session('admin_id')))
            ->delete();

        session()->forget('thumb_img_id.' . session('admin_id'));
    }
}

/**
 * 获得指定相册下的子相册的数组
 *
 * @access  public
 * @param int $album_id 分类的ID
 * @param int $selected 当前选中分类的ID
 * @param boolean $re_type 返回的类型: 值为真时返回下拉列表,否则返回数组
 * @param int $level 限定返回的级数。为0时返回所有级数
 * @param boolean $is_show_all 如果为true显示所有分类，如果为false隐藏不可见分类。
 * @param int $ru_id 商家ID
 * @param int $suppliers_id ID
 * @return  array
 */
function gallery_cat_list($album_id = 0, $selected = 0, $re_type = true, $level = 0, $is_show_all = true, $ru_id = 0, $suppliers_id = 0)
{
    static $res = null;

    if ($res === null) {
        $res = GalleryAlbum::whereRaw(1);

        if ($album_id == 0) {
            if ($suppliers_id > 0) {
                $res = $res->where('suppliers_id', $suppliers_id);
            } else {
                $res = $res->where('ru_id', $ru_id)
                    ->where('suppliers_id', 0);
            }
        } else {
            $res = $res->where('album_id', $album_id);
        }

        $res = $res->withCount('galleryAlbumChild as gallery_album_child_count');

        $res = $res->orderByRaw("parent_album_id, sort_order asc");

        $res = app(BaseRepository::class)->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $row) {
                $res[$key]['has_children'] = $row['gallery_album_child_count'];
            }
        }
    }
    if (empty($res) == true) {
        return $re_type ? '' : [];
    }

    $options = gallery_cat_options($album_id, $res); // 获得指定分类下的子分类的数组

    $children_level = 99999; //大于这个分类的将被删除
    if ($is_show_all == false) {
        foreach ($options as $key => $val) {
            if ($val['level'] > $children_level) {
                unset($options[$key]);
            } else {
                $children_level = 99999; //恢复初始值
            }
        }
    }

    /* 截取到指定的缩减级别 */
    if ($level > 0) {
        if ($album_id == 0) {
            $end_level = $level;
        } else {
            $first_item = reset($options); // 获取第一个元素
            $end_level = $first_item['level'] + $level;
        }

        /* 保留level小于end_level的部分 */
        foreach ($options as $key => $val) {
            if ($val['level'] >= $end_level) {
                unset($options[$key]);
            }
        }
    }

    if ($re_type == true) {
        $select = '';
        foreach ($options as $var) {
            $select .= '<option value="' . $var['album_id'] . '" ';
            $select .= ($selected == $var['album_id']) ? "selected='true'" : '';
            $select .= '>';
            if ($var['level'] > 0) {
                $select .= str_repeat('&nbsp;', $var['level'] * 4);
            }
            $select .= htmlspecialchars(addslashes($var['cat_name']), ENT_QUOTES) . '</option>';
        }

        return $select;
    } else {
        return $options;
    }
}

/**
 * 过滤和排序所有相册，返回一个带有缩进级别的数组
 *
 * @access  private
 * @param int $spec_cat_id 上级分类ID
 * @param array $arr 含有所有分类的数组
 * @param int $level 级别
 * @return  void
 */
function gallery_cat_options($spec_cat_id, $arr)
{
    static $cat_options = [];

    if (isset($cat_options[$spec_cat_id])) {
        return $cat_options[$spec_cat_id];
    }
    $i = 0;

    if (!isset($cat_options[0])) {
        $level = $last_cat_id = 0;
        $options = $cat_id_array = $level_array = [];
        while (!empty($arr)) {
            foreach ($arr as $key => $value) {
                $album_id = $value['album_id'];
                if ($level == 0 && $last_cat_id == 0) {
                    if ($value['parent_album_id'] > 0) {
                        break;
                    }

                    $options[$album_id] = $value;
                    $options[$album_id]['level'] = $level;
                    $options[$album_id]['id'] = $album_id;
                    $options[$album_id]['name'] = $value['album_mame'];
                    unset($arr[$key]);

                    if ($value['has_children'] == 0) {
                        continue;
                    }
                    $last_cat_id = $album_id;
                    $cat_id_array = [$album_id];
                    $level_array[$last_cat_id] = ++$level;
                    continue;
                }

                if ($value['parent_album_id'] == $last_cat_id) {
                    $options[$album_id] = $value;
                    $options[$album_id]['level'] = $level;
                    $options[$album_id]['id'] = $album_id;
                    $options[$album_id]['name'] = $value['album_mame'];
                    unset($arr[$key]);

                    if ($value['has_children'] > 0) {
                        if (end($cat_id_array) != $last_cat_id) {
                            $cat_id_array[] = $last_cat_id;
                        }
                        $last_cat_id = $album_id;
                        $cat_id_array[] = $album_id;
                        $level_array[$last_cat_id] = ++$level;
                    }
                } elseif ($value['parent_album_id'] > $last_cat_id) {
                    break;
                }
            }

            $count = count($cat_id_array);
            if ($count > 1) {
                $last_cat_id = array_pop($cat_id_array);
            } elseif ($count == 1) {
                if ($last_cat_id != end($cat_id_array)) {
                    $last_cat_id = end($cat_id_array);
                } else {
                    $level = 0;
                    $last_cat_id = 0;
                    $cat_id_array = [];
                    continue;
                }
            }

            if ($last_cat_id && isset($level_array[$last_cat_id])) {
                $level = $level_array[$last_cat_id];
            } else {
                $level = 0;
            }
        }
        $cat_options[0] = $options;
    } else {
        $options = $cat_options[0];
    }

    if (!$spec_cat_id) {
        return $options;
    } else {
        if (empty($options[$spec_cat_id])) {
            return [];
        }

        $spec_cat_id_level = $options[$spec_cat_id]['level'];

        foreach ($options as $key => $value) {
            if ($key != $spec_cat_id) {
                unset($options[$key]);
            } else {
                break;
            }
        }

        $spec_cat_id_array = [];
        foreach ($options as $key => $value) {
            if (($spec_cat_id_level == $value['level'] && $value['id'] != $spec_cat_id) ||
                ($spec_cat_id_level > $value['level'])
            ) {
                continue;
            } else {
                $spec_cat_id_array[$key] = $value;
            }
        }
        $cat_options[$spec_cat_id] = $spec_cat_id_array;
        return $spec_cat_id_array;
    }
}

//获取子相册
function gallery_child_cat_list($parent_id, $ru_id = 0)
{
    $res = GalleryAlbum::where('parent_album_id', $parent_id);

    $res = $res->where('ru_id', $ru_id);

    $res = $res->orderBy('sort_order');

    $res = app(BaseRepository::class)->getToArrayGet($res);

    if ($res) {
        foreach ($res as $k => $v) {
            $res[$k]['album_cover'] = empty($v['album_cover']) ? '' : get_image_path($v['album_cover']);

            $res[$k]['album_mame'] = $v['album_mame'];
            $res[$k]['album_id'] = $v['album_id'];
        }
    }

    return $res;
}

//获取相册数量
function gallery_child_cat_num($parent_id = 0, $ru_id = 0)
{
    $res = GalleryAlbum::where('parent_album_id', $parent_id);

    $res = $res->where('ru_id', $ru_id);

    $count = $res->count();

    return $count;
}

/**
 * 导出csv文件
 * $data         array     欲导出数组
 * $filename     string    文件名
 * $prev         array     文档头部信息
 * $next         array     文档底部信息
 *
 */
function export_csv_pro($data = [], $filename = "", $prev = [], $next = [])
{
    if (empty($filename)) {
        $filename = local_date("YmdHis") . ".csv";
    } else {
        $filename = $filename . ".csv";
    }
    if (!empty($prev)) {
        $prev = implode(",", $prev) . "\n";
    } else {
        $prev = "";
    }
    if (!empty($next)) {
        $next = implode(",", $next) . "\n";
    } else {
        $next = "";
    }
    //头部设置
    header("Content-type:text/csv");
    header("Content-Disposition:attachment;filename=" . $filename);
    header("Cache-Control:must-revalidate,post-check=0,pre-check=0");
    header("Expires:0");
    header("Pragma:public");

    // 写入bom头 解决excel打开乱码问题
    $fp = fopen("php://output", 'w');
    fputs($fp, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

    //处理数组数据
    if (empty($data)) {
        echo idel(lang('admin/common.not_fuhe_date'));
    } else {
        $result = "";
        foreach ($data as $key => $value) {
            //处理数组内的','
            foreach ($value as $abc => $cba) {
                $value[$abc] = idel(str_replace(",", "", $cba));
            }
            $result .= implode(",", $value) . "\n";
        }
        echo $prev . $result . $next;
    }
}

//过滤字符串相关标签
function idel($strInput)
{
    $strInput = strip_tags($strInput);
    return $strInput;
}

//快递鸟、电子面单：获取打印内容
function get_kdniao_print_content($order_id = 0, $shipping_spec = [], $shipping_info = [])
{
    $order = order_info($order_id);
    $adminru = get_admin_ru_id();

    $shopinfo = SellerShopinfo::where('ru_id', $adminru['ru_id']);
    $shopinfo = app(BaseRepository::class)->getToArrayFirst($shopinfo);

    $result = read_static_cache('kdniao_eorder_' . $order['order_sn']);

    //判断是否存在面单缓存或即使存在但请求失败，则都可以再次请求
    if ($result === false || ($result !== false && $result["ResultCode"] != "100")) {
        //补充数据
        $Goodsquantity = 0;
        $GoodsWeight = 0;
        $GoodsName = [];
        $order_goods = order_goods($order_id);

        foreach ($order_goods as $goods) {
            $Goodsquantity += $goods['goods_number'];
            $GoodsWeight += $goods['goodsweight'];
            //处理商品名称：默认调用当前分类名
            $cat_id = Goods::where('goods_id', $goods['goods_id'])->value('cat_id');
            $cat_id = $cat_id ? $cat_id : 0;

            $cat_name = Category::where('cat_id', $cat_id)->value('cat_name');
            $cat_name = $cat_name ? $cat_name : '';

            if ($cat_name) {
                $GoodsName[] = $cat_name . '×' . $goods['goods_number'];
            }
        }

        //构造电子面单提交信息
        $eorder = [];
        $eorder["ShipperCode"] = isset($shipping_spec['kdniao_code']) ? $shipping_spec['kdniao_code'] : '';
        $eorder["OrderCode"] = $order['order_sn'];
        $eorder["PayType"] = $shipping_info['pay_type'];
        $eorder["ExpType"] = 1;
        $eorder["IsReturnPrintTemplate"] = 1;
        $eorder["CustomerName"] = isset($shipping_info['customer_name']) ? $shipping_info['customer_name'] : ''; //帐号
        $eorder["CustomerPwd"] = isset($shipping_info['customer_pwd']) ? $shipping_info['customer_pwd'] : ''; //密码
        $eorder["MonthCode"] = isset($shipping_info['month_code']) ? $shipping_info['month_code'] : ''; //月结编号
        $eorder["SendSite"] = isset($shipping_info['send_site']) ? $shipping_info['send_site'] : ''; //收件网点标识

        //寄方
        $sender = [];
        $sender["Name"] = $shopinfo['shop_name'];
        $sender["Mobile"] = $shopinfo['mobile'];
        $sender["Tel"] = $shopinfo['kf_tel'];
        $sender["PostCode"] = '';
        $sender["ProvinceName"] = get_goods_region_name($shopinfo['province']);
        $sender["CityName"] = get_goods_region_name($shopinfo['city']);
        $sender["ExpAreaName"] = get_goods_region_name($shopinfo['district']);
        $sender["Address"] = $shopinfo['shop_address'];

        //收方
        $receiver = [];
        $receiver["Name"] = $order['consignee'];
        $receiver["Mobile"] = $order['mobile'];
        $receiver["Tel"] = $order['tel'];
        $receiver["PostCode"] = $order['zipcode'];
        $receiver["ProvinceName"] = get_goods_region_name($order['province']);
        $receiver["CityName"] = get_goods_region_name($order['city']);
        $receiver["ExpAreaName"] = get_goods_region_name($order['district']);
        $receiver["Address"] = $order['address'];

        //其他
        $commodityOne = [];
        $commodityOne["GoodsName"] = empty($GoodsName) ? "其他" : implode(', ', $GoodsName);
        $commodityOne["Goodsquantity"] = empty($Goodsquantity) ? 1 : $Goodsquantity;
        $commodityOne["GoodsWeight"] = empty($GoodsWeight) ? 1 : $GoodsWeight;
        $commodity = [];
        $commodity[] = $commodityOne;

        //整合数据
        $eorder["Sender"] = $sender;
        $eorder["Receiver"] = $receiver;
        $eorder["Commodity"] = $commodity;

        //调用电子面单
        $kdniao = Kdniao::getInstance($GLOBALS['_CFG']['kdniao_client_id'], $GLOBALS['_CFG']['kdniao_appkey']);
        $jsonParam = json_encode($eorder, JSON_UNESCAPED_UNICODE);
        //$jsonParam = JSON($eorder);//兼容php5.2（含）以下
        $jsonResult = $kdniao->submitEOrder($jsonParam);

        //解析电子面单返回结果
        $result = dsc_decode($jsonResult, true);
        if ($result['Success']) {
            write_static_cache('kdniao_eorder_' . $order['order_sn'], $result);
        }
    }

    return $result;
}

//快递鸟、电子面单：获取打印方式
function get_print_type($ru_id = 0)
{
    $print_type = SellerShopinfo::where('ru_id', $ru_id)->value('print_type');
    return $print_type;
}

//读取快递文件
function get_shipping_spec($shipping_code = '')
{
    $lang_file = include_once(plugin_path('Shipping/' . Str::studly($shipping_code) . '/config.php'));

    return $lang_file;
}

//获取快递配置
function get_shipping_conf($shipping_id = 0, $ru_id = 0)
{
    $shipping_conf = KdniaoEorderConfig::where('shipping_id', $shipping_id)
        ->where('ru_id', $ru_id);
    $shipping_conf = app(BaseRepository::class)->getToArrayFirst($shipping_conf);

    return $shipping_conf;
}

//获取快递信息
function get_shipping_info($shipping_id = 0, $ru_id = 0)
{

    //判断帐号全局设置
    if ($GLOBALS['_CFG']['kdniao_account_use'] == 0) {
        $ru_id = 0;
    }

    $shipping_info = Shipping::where('shipping_id', $shipping_id);
    $shipping_info = app(BaseRepository::class)->getToArrayFirst($shipping_info);

    $shipping_conf = get_shipping_conf($shipping_id, $ru_id);
    if (!empty($shipping_conf) && is_array($shipping_conf)) {
        $shipping_info = array_merge($shipping_info, $shipping_conf);
    }
    return $shipping_info;
}

//获取客户号信息
function get_kdniao_customer_account($shipping_id = 0, $ru_id = 0)
{
    $data = KdniaoCustomerAccount::where('shipping_id', $shipping_id)
        ->where('ru_id', $ru_id);
    $data = app(BaseRepository::class)->getToArrayFirst($data);

    return $data;
}

/**
 * 获取商家品牌
 * 后台异步操作需要
 */
function get_seller_brand()
{
    $res = read_static_cache('get_seller_brand');

    if ($res === false) {
        $res = MerchantsShopBrand::whereRaw(1);

        $res = $res->with([
            'getLinkBrand' => function ($query) {
                $query->with([
                    'getBrand' => function ($query) {
                        $query->withCount('getGoods as goods_count');
                    }
                ]);
            }
        ]);

        $res = app(BaseRepository::class)->getToArrayGet($res);

        foreach ($res as $key => $row) {
            $res[$key]['seller_name'] = app(MerchantCommonService::class)->getShopName($row['user_id'], 1);

            $brand = $row['get_link_brand'] && $row['get_link_brand']['get_brand'] ? $row['get_link_brand']['get_brand'] : [];

            if ($brand) {
                $res[$key]['brand_id'] = $brand['brand_id'] ?? 0;
                $res[$key]['brand_name'] = $brand['brand_name'] ?? '';
                $res[$key]['goods_count'] = $brand['goods_count'] ?? 0;
            } else {
                unset($res[$key]);
            }
        }

        write_static_cache('get_seller_brand', $res);
    }

    return $res;
}

//获取统计数据
function get_statistical_data($start_date = 0, $end_date = 0, $type = 'order')
{
    $data = [];
    $adminru = get_admin_ru_id();

    //格林威治时间与本地时间差
    $timezone = session()->has('timezone') ? session('timezone') : $GLOBALS['_CFG']['timezone'];
    $time_diff = $timezone * 3600;
    $date_start = $start_date;
    $date_end = $end_date;
    $day_num = ceil($date_end - $date_start) / 86400;

    $result = OrderInfo::selectRaw("DATE_FORMAT(FROM_UNIXTIME(add_time + '" . $time_diff . "'), '%y-%m-%d') AS day, COUNT(*) AS count, SUM(money_paid) AS money, SUM(money_paid)+SUM(surplus) AS superman")
        ->where('main_count', 0)
        ->whereBetween('add_time', [$date_start, $date_end])
        ->where('supplier_id', 0);

    if ($adminru['rs_id'] > 0) {
        $result = app(DscRepository::class)->getWhereRsid($result, 'ru_id', $adminru['rs_id']);
    } else {
        $result = $result->where('ru_id', $adminru['ru_id']);
    }

    $result = $result->groupBy('day')
        ->orderBy('day');

    $result = app(BaseRepository::class)->getToArrayGet($result);

    $orders_series_data = [];
    $sales_series_data = [];
    $orders_xAxis_data = [];
    $sales_xAxis_data = [];
    $days = [];

    if ($result) {
        foreach ($result as $row) {
            $orders_series_data[$row['day']] = intval($row['count']);
            $sales_series_data[$row['day']] = floatval($row['money']);
            $sales_series_data[$row['day']] = floatval($row['superman']);
            $days[] = $row['day'];
        }
    }

    for ($i = 0; $i < count($days); $i++) {
        // $day = local_date("y-m-d", local_strtotime(" - " . ($day_num - $i) . " days"));
        if (empty($orders_series_data[$days[$i]])) {
            $orders_series_data[] = 0;
            $sales_series_data[] = 0;
        }
        //输出时间
        $day = local_date("m-d", local_strtotime($days[$i]));
        $orders_xAxis_data[] = $day;
        $sales_xAxis_data[] = $day;
    }

    //获取系统数据 end

    //图表公共数据 start
    $title = [
        'text' => '',
        'subtext' => ''
    ];

    $toolbox = [
        'show' => true,
        'orient' => 'vertical',
        'x' => 'right',
        'y' => '60',
        'feature' => [
            'magicType' => [
                'show' => true,
                'type' => ['line', 'bar']
            ],
            'saveAsImage' => [
                'show' => true
            ]
        ]
    ];
    $tooltip = ['trigger' => 'axis',
        'axisPointer' => [
            'lineStyle' => [
                'color' => '#6cbd40'
            ]
        ]
    ];
    $xAxis = [
        'type' => 'category',
        'boundaryGap' => false,
        'axisLine' => [
            'lineStyle' => [
                'color' => '#ccc',
                'width' => 0
            ]
        ],
        'data' => []];
    $yAxis = [
        'type' => 'value',
        'axisLine' => [
            'lineStyle' => [
                'color' => '#ccc',
                'width' => 0
            ]
        ],
        'axisLabel' => [
            'formatter' => '']];
    $series = [
        [
            'name' => '',
            'type' => 'line',
            'itemStyle' => [
                'normal' => [
                    'color' => '#6cbd40',
                    'lineStyle' => [
                        'color' => '#6cbd40'
                    ]
                ]
            ],
            'data' => [],
            'markPoint' => [
                'itemStyle' => [
                    'normal' => [
                        'color' => '#6cbd40'
                    ]
                ],
                'data' => [
                    [
                        'type' => 'max',
                        'name' => '最大值'],
                    [
                        'type' => 'min',
                        'name' => '最小值']
                ]
            ]
        ],
        [
            'type' => 'force',
            'name' => '',
            'draggable' => false,
            'nodes' => [
                'draggable' => false
            ]
        ]
    ];
    $calculable = true;
    $legend = ['data' => []];
    //图表公共数据 end

    //订单统计
    if ($type == 'order') {
        $title['text'] = lang('order.order_number');
        $xAxis['data'] = $orders_xAxis_data;
        $yAxis['formatter'] = '{value}' . lang('order.individual');
        ksort($orders_series_data);
        $series[0]['name'] = lang('order.order_individual_count');
        $series[0]['data'] = array_values($orders_series_data);
    }

    //销售统计
    if ($type == 'sale') {
        $title['text'] = lang('order.sale_money');
        $xAxis['data'] = $sales_xAxis_data;
        $yAxis['formatter'] = '{value}' . lang('order.money_unit');
        ksort($sales_series_data);
        $series[0]['name'] = lang('order.sale_money');
        $series[0]['data'] = array_values($sales_series_data);
    }

    //整理数据
    $data['title'] = $title;
    $data['series'] = $series;
    $data['tooltip'] = $tooltip;
    $data['legend'] = $legend;
    $data['toolbox'] = $toolbox;
    $data['calculable'] = $calculable;
    $data['xAxis'] = $xAxis;
    $data['yAxis'] = $yAxis;

    $data['xy_file'] = get_dir_file_list();
    return $data;
}

/**
 * 运费模板
 * 获取全国地区
 *
 */
function get_the_national($region_name = '中国')
{
    $regions = Region::where('region_type', 1);

    $regions = $regions->whereHas('getRegionParent', function ($query) use ($region_name) {
        $query->where('region_type', 0)
            ->where('region_name', $region_name);
    });

    $regions = app(BaseRepository::class)->getToArrayGet($regions);

    return $regions;
}

/**
 * 运费模板
 * 快递模板 - 快递列表
 */
function get_transport_shipping_list($tid = 0, $ru_id = 0)
{
    $where = [
        'user_id' => $ru_id,
        'tid' => $tid
    ];
    $shipping_tpl = Shipping::whereHas('getGoodsTransportTpl', function ($query) use ($where) {
        $query->where('user_id', $where['user_id'])
            ->where('tid', $where['tid']);
    });

    $shipping_tpl = $shipping_tpl->with([
        'getGoodsTransportTpl' => function ($query) use ($where) {
            $query->where('user_id', $where['user_id'])
                ->where('tid', $where['tid']);
        }
    ]);

    $shipping_tpl = app(BaseRepository::class)->getToArrayGet($shipping_tpl);

    if ($shipping_tpl) {
        foreach ($shipping_tpl as $k => $v) {
            $tpl = $v['get_goods_transport_tpl'] ? $v['get_goods_transport_tpl'] : [];

            if ($v['shipping_id']) {
                $shipping_tpl[$k]['area_list'] = get_transport_shipping_area_list($tpl['tid'], $v['shipping_id']);
                $shipping_tpl[$k]['area_count'] = count($shipping_tpl[$k]['area_list']);
            }
        }
    }

    return $shipping_tpl;
}

/**
 * 运费模板
 * 快递模板 - 快递地区列表
 */
function get_transport_shipping_area_list($tid = 0, $shipping_id = 0)
{
    $area_list = GoodsTransportTpl::where('tid', $tid)
        ->where('shipping_id', $shipping_id);

    $area_list = $area_list->orderBy('id');

    $area_list = app(BaseRepository::class)->getToArrayGet($area_list);

    if ($area_list) {
        foreach ($area_list as $k => $v) {
            if ($v['region_id']) {
                $region_id = app(BaseRepository::class)->getExplode($v['region_id']);
                $region_list = Region::whereIn('region_id', $region_id);
                $region_list = app(BaseRepository::class)->getToArrayGet($region_list);

                $area_list[$k]['region_list'] = $region_list;

                $list_name = app(BaseRepository::class)->getKeyPluck($region_list, 'region_name');
                $area_list[$k]['list_name'] = app(BaseRepository::class)->getImplode($list_name);
            }
        }
    }

    return $area_list;
}

//查询区域地区列表
function get_area_list($ra_id = 0, $region_ids = [])
{
    $res = MerchantsRegionInfo::select('region_id')
        ->where('ra_id', $ra_id);

    $res = $res->with('getRegion');

    $res = app(BaseRepository::class)->getToArrayGet($res);

    if (!empty($res)) {
        foreach ($res as $k => $v) {
            $v = app(BaseRepository::class)->getArrayMerge($v, $v['get_region']);
            $res[$k] = $v;

            if (!empty($region_ids) && in_array($v['region_id'], $region_ids)) {
                $res[$k]['is_checked'] = 1;
            } else {
                $res[$k]['is_checked'] = 0;
            }
        }
    }

    return $res;
}


/**
 * 统一商品详情
 * 列表
 */
function get_link_goods_desc_list($ru_id = 0)
{
    $result = get_filter();
    if ($result === false) {
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $filter['type'] = isset($_REQUEST['type']) && !empty($_REQUEST['type']) ? addslashes($_REQUEST['type']) : '';

        $filter['ru_id'] = $ru_id;

        $row = LinkGoodsDesc::whereRaw(1);

        if ($filter['type']) {
            $row = $row->where('ru_id', '>', 0);
        } else {
            $row = $row->where('ru_id', $filter['ru_id']);
        }

        $res = $record_count = $row;

        $record_count = $record_count->count();

        $filter['record_count'] = $record_count;
        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 分页大小 */
        $filter = page_and_size($filter);

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

        if ($filter['start'] > 0) {
            $res = $res->skip($filter['start']);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $res = app(BaseRepository::class)->getToArrayGet($res);
    } else {
        $sql = $result['sql'];
        $filter = $result['filter'];

        $res = [];
    }

    $arr = ['desc_list' => $res, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    return $arr;
}

//获取指定分类下的下级分类
function get_cat_child($cat_id = 0, $arr = [])
{
    $arr[] = $cat_id;
    $cat_list = GalleryAlbum::select('album_id')
        ->where('parent_album_id', $cat_id);
    $cat_list = app(BaseRepository::class)->getToArrayGet($cat_list);
    $cat_list = app(BaseRepository::class)->getKeyPluck($cat_list, 'album_id');

    if (!empty($cat_list)) {
        foreach ($cat_list as $v) {
            $arr = get_cat_child($v, $arr);
        }
    }

    return $arr;
}

/*
 * 延时收货申请列表
 */
function get_order_delayed_list($ru_id = 0)
{
    $adminru = get_admin_ru_id();
    $result = get_filter();
    if ($result === false) {
        /* 过滤条件 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        $filter['review_status'] = empty($_REQUEST['review_status']) ? '' : trim($_REQUEST['review_status']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
            $filter['review_status'] = json_str_iconv($filter['review_status']);
        }

        $filter['review_status'] = ($filter['review_status'] == -1) ? '' : intval($filter['review_status']);
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'delayed_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $row = OrderDelayed::whereRaw(1);

        $filter['ru_id'] = $adminru['ru_id'];

        $row = $row->whereHas('getOrder', function ($query) use ($filter) {
            if ($filter['ru_id'] > 0) {
                $query = $query->whereHas('getOrderGoods', function ($query) use ($filter) {
                    $query->where('ru_id', $filter['ru_id']);
                });
            }

            $query->whereHas('getUsers', function ($query) use ($filter) {
                if ($filter['keywords']) {
                    $keywords = mysql_like_quote($filter['keywords']);
                    $query->where('user_name', 'like', '%' . $keywords . '%');
                }
            });
        });

        if ($filter['review_status']) {
            $row = $row->where('review_status', $filter['review_status']);
        }

        $res = $record_count = $row;

        $filter['record_count'] = $record_count->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        $res = $res->with([
            'getOrder' => function ($query) {
                $query->select('order_id', 'order_sn', 'user_id')
                    ->with('getUsers');
            }
        ]);

        if ($filter['start'] > 0) {
            $res = $res->skip($filter['start']);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $order_delayed_list = app(BaseRepository::class)->getToArrayGet($res);

        $filter['keywords'] = stripslashes($filter['keywords']);
        //
    } else {
        //$sql    = $result['sql'];
        $filter = $result['filter'];

        $order_delayed_list = [];
    }

    if ($order_delayed_list) {
        foreach ($order_delayed_list as $key => $value) {
            $order_delayed_list[$key]['order_sn'] = $value['get_order'] ? $value['get_order']['order_sn'] : '';
            $order_delayed_list[$key]['user_name'] = $value['get_order'] && $value['get_order']['get_users'] ? $value['get_order']['get_users']['user_name'] : '';

            switch ($value['review_status']) {
                case 0:
                    $order_delayed_list[$key]['review_status_info'] = lang('order.not_audited');
                    break;
                case 1:
                    $order_delayed_list[$key]['review_status_info'] = lang('order.audited_yes_adopt');
                    break;
                case 2:
                    $order_delayed_list[$key]['review_status_info'] = lang('order.audited_not_adopt');
                    break;

                default:
                    break;
            }

            // 审核人
            $order_delayed_list[$key]['review_admin_user'] = '--';
            if ($value['review_admin']) {
                $review_admin_user = AdminUser::where('user_id', $value['review_admin'])->value('user_name');

                $order_delayed_list[$key]['review_admin_user'] = $review_admin_user;
            }

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                $order_delayed_list[$key]['user_name'] = app(DscRepository::class)->stringToStar($order_delayed_list[$key]['user_name']);
            }
        }
    }

    $arr = ['order_delay_list' => $order_delayed_list, 'filter' => $filter,
        'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

    return $arr;
}
