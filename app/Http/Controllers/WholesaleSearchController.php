<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\GoodsAttr;
use App\Models\GoodsType;
use App\Models\Keywords;
use App\Models\Tag;
use App\Models\Wholesale;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Article\ArticleCommonService;
use App\Services\Common\CommonService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Wholesale\CategoryService;
use App\Services\Wholesale\OrderService;
use App\Services\Wholesale\WholesaleService;
use App\Services\Category\CategoryService as Category;

/**
 * 调查程序
 */
class WholesaleSearchController extends InitController
{
    protected $categoryService;
    protected $wholesaleService;
    protected $orderService;
    protected $baseRepository;
    protected $category;
    protected $dscRepository;
    protected $merchantCommonService;
    protected $articleCommonService;
    protected $commonService;

    public function __construct(
        CategoryService $categoryService,
        WholesaleService $wholesaleService,
        OrderService $orderService,
        BaseRepository $baseRepository,
        Category $category,
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService,
        ArticleCommonService $articleCommonService,
        CommonService $commonService
    )
    {
        load_helper(['suppliers']);
        $this->categoryService = $categoryService;
        $this->wholesaleService = $wholesaleService;
        $this->orderService = $orderService;
        $this->baseRepository = $baseRepository;
        $this->category = $category;
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->articleCommonService = $articleCommonService;
        $this->commonService = $commonService;
    }

    public function index()
    {
        if (!function_exists("htmlspecialchars_decode")) {
            function htmlspecialchars_decode($string, $quote_style = ENT_COMPAT)
            {
                return strtr($string, array_flip(get_html_translation_table(HTML_SPECIALCHARS, $quote_style)));
            }
        }

        if (empty(request()->input('encode', ''))) {
            $string = stripslashes_deep(request()->all());
            $string['search_encode_time'] = time();
            $string = str_replace('+', '%2b', base64_encode(serialize($string)));

            header("Location: wholesale_search.php?encode=$string\n");

            exit;
        } else {
            $string = base64_decode(trim(request()->input('encode', '')));
            if ($string !== false) {
                $string = unserialize($string);
                if ($string !== false) {
                    /* 用户在重定向的情况下当作一次访问 */
                    if (!empty($string['search_encode_time'])) {
                        if (time() > $string['search_encode_time'] + 2) {
                            define('INGORE_VISIT_STATS', true);
                        }
                    } else {
                        define('INGORE_VISIT_STATS', true);
                    }
                } else {
                    $string = array();
                }
            } else {
                $string = array();
            }
        }

        load_helper(['wholesale']);
        $user_id = session('user_id', 0);

        //访问权限
        $wholesaleUse = $this->commonService->judgeWholesaleUse($user_id);

        if ($wholesaleUse['return']) {
            if ($user_id) {
                return show_message($GLOBALS['_LANG']['not_seller_user']);
            } else {
                return show_message($GLOBALS['_LANG']['not_login_user']);
            }
        }

        $request = array_merge(request()->all(), addslashes_deep($string));

        /* 跳转H5 start */
        $Loaction = 'mobile#/supplier/search';
        $uachar = $this->dscRepository->getReturnMobile($Loaction);

        if ($uachar) {
            return $uachar;
        }
        /* 跳转H5 end */

        /* 过滤 XSS 攻击和SQL注入 */
        get_request_filter();

        /* ------------------------------------------------------ */
        //-- 搜索结果
        /* ------------------------------------------------------ */

        $request['keywords'] = !empty($request['keywords']) ? strip_tags(htmlspecialchars(trim($request['keywords']))) : '';
        $request['keywords'] = !empty($request['keywords']) ? addslashes_deep(trim($request['keywords'])) : '';
        $request['category'] = !empty($request['category']) ? intval($request['category']) : 0;
        $goods_type = !empty($request['goods_type']) ? intval($request['goods_type']) : 0;
        $action = '';
        if (isset($request['action']) && $request['action'] == 'form') {
            /* 要显示高级搜索栏 */
            $adv_value['keywords'] = htmlspecialchars(stripcslashes($request['keywords']));
            $adv_value['category'] = $request['category'];

            $attributes = $this->get_seachable_attributes($goods_type);

            /* 将提交数据重新赋值 */
            foreach ($attributes['attr'] as $key => $val) {
                if (!empty($request['attr'][$val['id']])) {
                    if ($val['type'] == 2) {
                        $attributes['attr'][$key]['value']['from'] = !empty($request['attr'][$val['id']]['from']) ? htmlspecialchars(stripcslashes(trim($request['attr'][$val['id']]['from']))) : '';
                        $attributes['attr'][$key]['value']['to'] = !empty($request['attr'][$val['id']]['to']) ? htmlspecialchars(stripcslashes(trim($request['attr'][$val['id']]['to']))) : '';
                    } else {
                        $attributes['attr'][$key]['value'] = !empty($request['attr'][$val['id']]) ? htmlspecialchars(stripcslashes(trim($request['attr'][$val['id']]))) : '';
                    }
                }
            }

            $this->smarty->assign('adv_val', $adv_value);
            $this->smarty->assign('goods_type_list', $attributes['cate']);
            $this->smarty->assign('goods_attributes', $attributes['attr']);
            $this->smarty->assign('goods_type_selected', $goods_type);

            $this->smarty->assign('action', 'form');
            $this->smarty->assign('use_storage', $GLOBALS['_CFG']['use_storage']);

            $action = 'form';
        }

        $resWho = Wholesale::whereRaw(1)
            ->where('enabled', 1)
            ->where('is_delete', 0)
            ->where('who.review_status', 3);

        $insert_keyword = isset($request['keywords']) && !empty($request['keywords']) ? addslashes(trim($request['keywords'])) : '';

        /* 初始化搜索条件 */
        $keywords = '';
        if (!empty($insert_keyword)) {
            $scws_res = scws($request['keywords']); //这里可以把关键词分词：诺基亚，耳机
            $arr = explode(',', $scws_res);

            $arr1[] = $insert_keyword;

            if ($arr1 && is_array($arr)) {
                $arr = array_merge($arr1, $arr);
            }

            $goods_ids = array();
            $operator = '';
            foreach ($arr as $key => $val) {
                if ($key > 0 && $key < count($arr) && count($arr) > 1) {
                    $keywords .= $operator;
                }
                $val = mysql_like_quote(trim($val));

                $resWho = $resWho->where(function ($query) use ($val) {
                    $query->where('goods_name', 'like', '%' . $val . '%');
                });

                $res = Tag::where('tag_words', 'like', '%' . $val . '%');
                $res = $this->baseRepository->getToArrayGet($res);
                $goods_ids = $this->baseRepository->getKeyPluck($res, 'goods_id');
                $goods_ids = $goods_ids ? array_values($goods_ids) : [];

                $local_time = local_date('Y-m-d');
                $searchengine = 'dscmall';
                $loacl_keyword = addslashes(str_replace('%', '', $val));

                $count = Keywords::where('date', $local_time)
                    ->where('searchengine', $searchengine)
                    ->where('keyword', $loacl_keyword)
                    ->count();

                if ($count <= 0) {
                    $data = [
                        'date' => $local_time,
                        'searchengine' => $searchengine,
                        'keyword' => $loacl_keyword,
                        'count' => 1
                    ];
                    Keywords::insert($data);
                } else {
                    Keywords::where('date', $local_time)
                        ->where('searchengine', $searchengine)
                        ->where('keyword', $loacl_keyword)
                        ->increment('count');
                }
            }

            $goods_ids = array_unique($goods_ids);
            if (!empty($tag_where)) {
                $resWho = $resWho->orWhereIn('goods_id', $goods_ids);
            }
        }

        $category = !empty($request['category']) ? intval($request['category']) : 0;

        /* 排序、显示方式以及类型 */
        $default_display_type = $GLOBALS['_CFG']['show_order_type'] == '0' ? 'list' : ($GLOBALS['_CFG']['show_order_type'] == '1' ? 'grid' : 'text');
        $default_sort_order_method = $GLOBALS['_CFG']['sort_order_method'] == '0' ? 'DESC' : 'ASC';
        $default_sort_order_type = $GLOBALS['_CFG']['sort_order_type'] == '0' ? 'goods_id' : ($GLOBALS['_CFG']['sort_order_type'] == '1' ? 'shop_price' : 'last_update');

        $sort = (isset($request['sort']) && in_array(trim(strtolower($request['sort'])), array('goods_id', 'shop_price', 'last_update'))) ? trim($request['sort']) : $default_sort_order_type;
        $order = (isset($request['order']) && in_array(trim(strtoupper($request['order'])), array('ASC', 'DESC'))) ? trim($request['order']) : $default_sort_order_method;
        $display = (isset($request['display']) && in_array(trim(strtolower($request['display'])), array('list', 'grid', 'text'))) ? trim($request['display']) : session('display_search', $default_display_type);

        session([
            'display_search' => $display
        ]);

        $page = !empty($request['page']) && intval($request['page']) > 0 ? intval($request['page']) : 1;
        $size = !empty($GLOBALS['_CFG']['page_size']) && intval($GLOBALS['_CFG']['page_size']) > 0 ? intval($GLOBALS['_CFG']['page_size']) : 10;

        $intromode = '';    //方式，用于决定搜索结果页标题图片

        if (empty($ur_here)) {
            $ur_here = $GLOBALS['_LANG']['search_goods'];
        }

        /* ------------------------------------------------------ */
        //-- 属性检索
        /* ------------------------------------------------------ */
        $attr_in = '';
        $attr_num = 0;
        $attr_url = '';
        $attr_arg = [];
        $goods_id = [];

        if (!empty($request['attr'])) {
            $attrSql = GoodsAttr::selectRaw('goods_id, COUNT(*) AS num')
                ->whereRaw(1);

            $attr = isset($request['attr']) ? addslashes_deep($request['attr']) : [];


            $where = [
                'attr' => $attr,
                'attr_url' => $attr_url,
                'pickout' => $request['pickout'] ?? ''
            ];
            $attrSql = $attrSql->where(function ($query) use ($where) {
                foreach ($where['attr'] as $key => $val) {
                    if ($this->is_not_null($val) && is_numeric($key)) {
                        if (is_array($val)) {
                            $where['key'] = $key;
                            $where['val'] = $val;
                            $where['from'] = $val['from'];
                            $where['to'] = $val['to'];

                            $query->orWhere(function ($query) use ($where) {
                                $query = $query->where('attr_id', $where['key']);

                                if (!empty($where['from'])) {
                                    if (is_numeric($where['from'])) {
                                        $query = $query->where('attr_value', '>=', floatval($where['from']));
                                    } else {
                                        $query = $query->where('attr_value', '>=', $where['from']);
                                    }
                                }

                                if (!empty($where['to'])) {
                                    if (is_numeric($where['to'])) {
                                        $query->where('attr_value', '<=', floatval($where['to']));
                                    } else {
                                        $query->where('attr_value', '<=', $where['to']);
                                    }
                                }
                            });
                        } else {
                            /* 处理选购中心过来的链接 */
                            $query->orWhere(function ($query) use ($where) {
                                $query->where('attr_id', $where['key']);

                                if ($where['pickout']) {
                                    $query->where('attr_value', $where['val']);
                                } else {
                                    $query->where('attr_value', 'like', '%' . mysql_like_quote($where['val']) . '%');
                                }
                            });
                        }
                    }
                }
            });

            foreach ($where['attr'] as $key => $val) {
                if ($this->is_not_null($val) && is_numeric($key)) {
                    $attr_num++;

                    if (is_array($val)) {
                        if (!empty($val['from'])) {
                            $attr_arg["attr[$key][from]"] = $val['from'];
                            $attr_url .= "&amp;attr[$key][from]=$val[from]";
                        }

                        if (!empty($val['to'])) {
                            $attr_arg["attr[$key][to]"] = $val['to'];
                            $attr_url .= "&amp;attr[$key][to]=$val[to]";
                        }
                    } else {
                        /* 处理选购中心过来的链接 */
                        $attr_url .= "&amp;attr[$key]=$val";
                        $attr_arg["attr[$key]"] = $val;
                    }
                }
            }

            /* 如果检索条件都是无效的，就不用检索 */
            if ($attr_num > 0 || isset($request['pickout'])) {
                if ($attr_num > 0) {
                    $attrSql = $attrSql->gorupBy('goods_id')
                        ->having('num', $attr_num);
                }
                $attrSql = $this->baseRepository->getToArrayGet($attrSql);
                $goods_id = $this->baseRepository->getKeyPluck($attrSql, 'goods_id');

                $goods_id = $goods_id ? array_values($goods_id) : [];
            }
        }

        if ($goods_id) {
            $resWho = $resWho->whereIn('goods_id', $goods_id);
        }

        $resWho = $resWho->from('wholesale as who')
            ->where('who.enabled', 1)
            ->leftjoin('suppliers as su', 'who.suppliers_id', '=', 'su.suppliers_id')
            ->where('su.review_status', 3)
            ->where('who.review_status', '>', 2);

        if ($category > 0) {
            $children = $this->categoryService->getWholesaleCatListChildren($category);

            $resWho = $resWho->whereIn('who.cat_id', $children);
        }

        $res = $count = $resWho;

        /* 获得符合条件的商品总数 */
        $count = $count->count();

        $max_page = ($count > 0) ? ceil($count / $size) : 1;
        if ($page > $max_page) {
            $page = $max_page;
        }

        /* 查询商品 */
        $res = $res->with([
            'getWholesaleVolumePriceList',
            'getWholesaleExtend',
            'getSuppliers'
        ]);

        $res = $res->orderBy('goods_id', 'desc');

        $start = ($page - 1) * $size;
        if ($start > 0) {
            $res = $res->skip($start);
        }


        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        $arr = array();
        if ($res) {
            foreach ($res as $key => $row) {
                $row['volume_number'] = $row['get_wholesale_volume_price_list'] ? $this->baseRepository->getArrayMin($row['get_wholesale_volume_price_list'], 'volume_number') : 0;
                $row['volume_price'] = $row['get_wholesale_volume_price_list'] ? $this->baseRepository->getArrayMin($row['get_wholesale_volume_price_list'], 'volume_price') : 0;

                /* 处理商品水印图片 */
                $watermark_img = '';

                if ($watermark_img != '') {
                    $arr[$row['goods_id']]['watermark_img'] = $watermark_img;
                }

                $arr[$row['goods_id']]['goods_id'] = $row['goods_id'];
                if ($display == 'grid') {
                    $arr[$row['goods_id']]['goods_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ? $this->dscRepository->subStr($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
                } else {
                    $arr[$row['goods_id']]['goods_name'] = $row['goods_name'];
                }

                $suppliers = $row['get_suppliers'];

                $suppliers['user_id'] = $suppliers['user_id'] ?? 0;

                $arr[$row['goods_id']]['goods_extend'] = $row['get_wholesale_extend'];
                $arr[$row['goods_id']]['rz_shopName'] = $suppliers['suppliers_name']; //供应商名称
                $arr[$row['goods_id']]['suppliers_url'] = $this->dscRepository->buildUri('wholesale_suppliers', array('sid' => $suppliers['suppliers_id']));
                // 供应商获取配置客服QQ
                $kf_qq = get_suppliers_kf($suppliers['suppliers_id']);
                if ($kf_qq) {
                    $arr[$row['goods_id']]['kf_qq'] = $kf_qq['kf_qq'];
                }

                $build_uri = array(
                    'urid' => $suppliers['user_id'],
                    'append' => $arr[$row['goods_id']]['rz_shopName']
                );

                $domain_url = $this->merchantCommonService->getSellerDomainUrl($suppliers['user_id'], $build_uri);
                $arr[$row['goods_id']]['store_url'] = $domain_url['domain_name'];

                $arr[$row['goods_id']]['goods_name'] = $row['goods_name'];
                $arr[$row['goods_id']]['goods_price'] = $row['goods_price'];
                $arr[$row['goods_id']]['moq'] = $row['moq'];
                $arr[$row['goods_id']]['volume_number'] = $row['volume_number'];
                $arr[$row['goods_id']]['volume_price'] = $row['volume_price'];
                $arr[$row['goods_id']]['goods_sale'] = $this->orderService->getGoodsOrderSale($row['goods_id']);
                $arr[$row['goods_id']]['price_model'] = $row['price_model'];

                $arr[$row['goods_id']]['goods_thumb'] = get_image_path($row['goods_thumb']);
                $arr[$row['goods_id']]['goods_img'] = get_image_path($row['goods_id'], $row['goods_img']);
                $arr[$row['goods_id']]['url'] = $this->dscRepository->buildUri('wholesale_goods', array('aid' => $row['goods_id']), $row['goods_name']);
            }
        }

        if ($display == 'grid') {
            if (count($arr) % 2 != 0) {
                $arr[] = array();
            }
        }
        $get_wholsale_navigator = $this->wholesaleService->getWholsaleNavigator();
        $this->smarty->assign('get_wholsale_navigator', $get_wholsale_navigator);

        $cat_list = $this->categoryService->getCategoryList();
        $this->smarty->assign('cat_list', $cat_list);
        $this->smarty->assign('goods_list', $arr);
        $this->smarty->assign('category', $category);
        $this->smarty->assign('keywords', htmlspecialchars(stripslashes($request['keywords'])));
        $this->smarty->assign('search_keywords', stripslashes(htmlspecialchars_decode($request['keywords'])));

        $request['sc_ds'] = $request['sc_ds'] ?? '';

        /* 分页 */
        $url_format = "wholesale_search.php?category=$category&amp;keywords=" . urlencode(stripslashes($request['keywords'])) . "&amp;action=" . $action . "&amp;goods_type=" . $goods_type . "&amp;sc_ds=" . $request['sc_ds'];
        if (!empty($intromode)) {
            $url_format .= "&amp;intro=" . $intromode;
        }
        if (isset($request['pickout'])) {
            $url_format .= '&amp;pickout=1';
        }
        $url_format .= "&amp;sort=$sort";

        $url_format .= "$attr_url&amp;order=$order&amp;page=";
        $pager['search'] = array(
            'keywords' => stripslashes(urlencode($request['keywords'])),
            'category' => $category,
            'sort' => $sort,
            'order' => $order,
            'action' => $action,
            'goods_type' => $goods_type
        );
        $pager['search'] = array_merge($pager['search'], $attr_arg);

        $pager = get_pager('wholesale_search.php', $pager['search'], $count, $page, $size);
        $pager['display'] = $display;

        $this->smarty->assign('url_format', $url_format);
        $this->smarty->assign('pager', $pager);


        assign_template();
        assign_dynamic('search');
        $position = assign_ur_here(0, $ur_here . ($request['keywords'] ? '_' . $request['keywords'] : ''));
        $this->smarty->assign('page_title', $position['title']);    // 页面标题
        $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置
        $this->smarty->assign('intromode', $intromode);
        $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());      // 网店帮助
        $this->smarty->assign('promotion_info', get_promotion_info());

        return $this->smarty->display('wholesale_search.dwt');
    }

    /**
     *
     *
     * @access public
     * @param
     *
     * @return void
     */
    private function is_not_null($value)
    {
        if (is_array($value)) {
            return (!empty($value['from'])) || (!empty($value['to']));
        } else {
            return !empty($value);
        }
    }

    /**
     * 获得可以检索的属性
     *
     * @access  public
     * @params  integer $cat_id
     * @return  void
     */
    private function get_seachable_attributes($cat_id = 0)
    {
        $attributes = array(
            'cate' => array(),
            'attr' => array()
        );

        /* 获得可用的商品类型 */
        $cat = GoodsType::where('enabled', 1)
            ->whereHas('getGoodsAttribute', function ($query) {
                $query->where('attr_index', '>', 0);
            });

        $cat = $this->baseRepository->getToArrayGet($cat);

        /* 获取可以检索的属性 */
        if (!empty($cat)) {
            foreach ($cat as $val) {
                $attributes['cate'][$val['cat_id']] = $val['cat_name'];
            }

            $res = Attribute::where('attr_index', '>', 0);

            if ($cat_id > 0) {
                $res = $res->where('cat_id', $cat_id);
            } else {
                $res = $res->where('cat_id', $cat[0]['cat_id']);
            }

            $res = $this->baseRepository->getToArrayGet($res);

            if ($res) {
                foreach ($res as $key => $row) {
                    if ($row['attr_index'] == 1 && $row['attr_input_type'] == 1) {
                        $row['attr_values'] = str_replace("\r", '', $row['attr_values']);
                        $options = explode("\n", $row['attr_values']);

                        $attr_value = array();
                        foreach ($options as $opt) {
                            $attr_value[$opt] = $opt;
                        }
                        $attributes['attr'][] = array(
                            'id' => $row['attr_id'],
                            'attr' => $row['attr_name'],
                            'options' => $attr_value,
                            'type' => 3
                        );
                    } else {
                        $attributes['attr'][] = array(
                            'id' => $row['attr_id'],
                            'attr' => $row['attr_name'],
                            'type' => $row['attr_index']
                        );
                    }
                }
            }
        }

        return $attributes;
    }
}
