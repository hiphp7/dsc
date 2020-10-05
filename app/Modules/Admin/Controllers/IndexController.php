<?php

namespace App\Modules\Admin\Controllers;

use App\Console\Commands\CommissionServer;
use App\Libraries\FileUpload;
use App\Libraries\Http;
use App\Libraries\Image;
use App\Models\Ad;
use App\Models\AdminMessage;
use App\Models\AdminUser;
use App\Models\AreaRegion;
use App\Models\BonusType;
use App\Models\BookingGoods;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Complaint;
use App\Models\Coupons;
use App\Models\DiscussCircle;
use App\Models\EmailSendlist;
use App\Models\ExchangeGoods;
use App\Models\FavourableActivity;
use App\Models\Feedback;
use App\Models\GiftGardType;
use App\Models\Goods;
use App\Models\GoodsActivity;
use App\Models\GoodsGallery;
use App\Models\GoodsReport;
use App\Models\MailTemplates;
use App\Models\MerchantsShopBrand;
use App\Models\MerchantsShopInformation;
use App\Models\ObsConfigure;
use App\Models\OrderInfo;
use App\Models\OrderReturn;
use App\Models\OssConfigure;
use App\Models\Payment;
use App\Models\PresaleActivity;
use App\Models\Region;
use App\Models\SaleNotice;
use App\Models\Seckill;
use App\Models\SeckillGoodsRemind;
use App\Models\SellerAccountLog;
use App\Models\SellerApplyInfo;
use App\Models\SellerCommissionBill;
use App\Models\SellerDomain;
use App\Models\SellerQrcode;
use App\Models\SellerShopheader;
use App\Models\SellerShopinfo;
use App\Models\Sessions;
use App\Models\Shipping;
use App\Models\ShippingArea;
use App\Models\ShopConfig;
use App\Models\SolveDealconcurrent;
use App\Models\Stats;
use App\Models\Suppliers;
use App\Models\Topic;
use App\Models\UserAccount;
use App\Models\Users;
use App\Models\UsersReal;
use App\Models\UsersVatInvoicesInfo;
use App\Models\Wholesale;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Common\CommonManageService;
use App\Services\Goods\GoodsManageService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderService;
use Illuminate\Support\Str;

/**
 * DSCMALL 控制台首页
 */
class IndexController extends InitController
{
    protected $baseRepository;
    protected $timeRepository;
    protected $goodsManageService;
    protected $dscRepository;
    protected $orderService;
    protected $merchantCommonService;
    protected $commonRepository;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        GoodsManageService $goodsManageService,
        DscRepository $dscRepository,
        OrderService $orderService,
        MerchantCommonService $merchantCommonService,
        CommonRepository $commonRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->goodsManageService = $goodsManageService;
        $this->dscRepository = $dscRepository;
        $this->orderService = $orderService;
        $this->merchantCommonService = $merchantCommonService;
        $this->commonRepository = $commonRepository;
    }

    public function index()
    {
        load_helper('order');

        $admin_id = session()->has('admin_id') ? session('admin_id') : 0;

        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

        $adminru = get_admin_ru_id();
        $adminru['ru_id'] = isset($adminru['ru_id']) ? $adminru['ru_id'] : 0;

        $this->smarty->assign('ru_id', $adminru['ru_id']);

        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        /* 校验授权 start */
        $is_empower = $this->dscRepository->checkEmpower();
        $this->smarty->assign('is_empower', $is_empower);
        /* 校验授权 end */

        $index_sales_volume = get_merchants_permissions($this->action_list, 'index_sales_volume');

        $this->smarty->assign('index_sales_volume', $index_sales_volume); //今日销量

        $index_today_order = get_merchants_permissions($this->action_list, 'index_today_order');
        $this->smarty->assign('index_today_order', $index_today_order); //今日订单数

        $index_today_comment = get_merchants_permissions($this->action_list, 'index_today_comment');
        $this->smarty->assign('index_today_comment', $index_today_comment); //今日评论

        $index_seller_num = get_merchants_permissions($this->action_list, 'index_seller_num');
        $this->smarty->assign('index_seller_num', $index_seller_num); //店铺销量

        $index_order_status = get_merchants_permissions($this->action_list, 'index_order_status');
        $this->smarty->assign('index_order_status', $index_order_status); //订单状态

        $index_order_stats = get_merchants_permissions($this->action_list, 'index_order_stats');
        $this->smarty->assign('index_order_stats', $index_order_stats); //订单统计

        $index_sales_stats = get_merchants_permissions($this->action_list, 'index_sales_stats');
        $this->smarty->assign('index_sales_stats', $index_sales_stats); //销量统计

        $index_member_info = get_merchants_permissions($this->action_list, 'index_member_info');
        $this->smarty->assign('index_member_info', $index_member_info); //会员信息

        $index_goods_view = get_merchants_permissions($this->action_list, 'index_goods_view');
        $this->smarty->assign('index_goods_view', $index_goods_view); //商品一览

        $index_control_panel = get_merchants_permissions($this->action_list, 'index_control_panel');
        $this->smarty->assign('index_control_panel', $index_control_panel); //控制面板

        $index_system_info = get_merchants_permissions($this->action_list, 'index_system_info');
        $this->smarty->assign('index_system_info', $index_system_info); //系统信息
        //商家单个权限 end

        $data = read_static_cache('main_user_str');
        if ($data === false) {
            $this->smarty->assign('is_false', '1');
        } else {
            $this->smarty->assign('is_false', '0');
        }

        $data = read_static_cache('seller_goods_str');
        if ($data === false) {
            $this->smarty->assign('goods_false', '1');
        } else {
            $this->smarty->assign('goods_false', '0');
        }

        /* ------------------------------------------------------ */
        //-- 框架
        /* ------------------------------------------------------ */
        if ($_REQUEST['act'] == '') {
            load_helper(['menu', 'priv'], 'admin');

            $modules = $GLOBALS['modules'];
            $menu_top = $GLOBALS['menu_top'];
            $purview = $GLOBALS['purview'];

            foreach ($modules as $key => $value) {
                ksort($modules[$key]);
            }
            ksort($modules);

            foreach ($menu_top as $mkey => $mval) {
                $menus = [];
                $nav_top[$mkey]['label'] = $GLOBALS['_LANG'][$mkey];
                $nav_top[$mkey]['type'] = $mkey;
                if (!empty($mval)) {
                    $menu_type = explode(',', $mval);
                    foreach ($modules as $key => $val) {
                        if (in_array($key, $menu_type)) {
                            $menus[$key]['menuleft'] = $mkey;
                            $menus[$key]['label'] = isset($GLOBALS['_LANG'][$key]) ? $GLOBALS['_LANG'][$key] : '';
                            if ($menus[$key]['menuleft'] == $mkey) {
                                if (is_array($val)) {
                                    foreach ($val as $k => $v) {
                                        if (isset($purview[$k])) {
                                            if (isset($purview[$k]) && is_array($purview[$k])) {
                                                $boole = false;
                                                foreach ($purview[$k] as $action) {
                                                    $boole = $boole || admin_priv($action, '', false);
                                                }
                                                if (!$boole) {
                                                    continue;
                                                }
                                            } else {
                                                if (!admin_priv($purview[$k], '', false)) {
                                                    continue;
                                                }
                                            }
                                        }
                                        if ($k == 'ucenter_setup' && $GLOBALS['_CFG']['integrate_code'] != 'ucenter') {
                                            continue;
                                        }
                                        $menus[$key]['children'][$k]['label'] = isset($GLOBALS['_LANG'][$k]) ? $GLOBALS['_LANG'][$k] : '';
                                        $menus[$key]['children'][$k]['action'] = $v;
                                    }
                                } else {
                                    $menus[$key]['action'] = $val;
                                }
                            }

                            // 如果children的子元素长度为0则删除该组
                            if (empty($menus[$key]['children'])) {
                                unset($menus[$key]);
                            }
                            $nav_top[$mkey]['children'] = $menus;
                        }
                    }
                }
            }

            if ($adminru['ru_id'] == 0) {
                $this->smarty->assign('priv_ru', 1);
            } else {
                $shop_name = $this->merchantCommonService->getShopName($adminru['ru_id'], 1);
                $this->smarty->assign('shop_name', $shop_name);

                $this->smarty->assign('priv_ru', 0);
            }

            $this->smarty->assign('nav_top', $nav_top);

            /*  @author-bylu 获取管理员信息 start */
            $admin_id = intval($admin_id);

            $res = AdminUser::where('user_id', $admin_id);
            $res = $res->with(['getRole' => function ($query) {
                $query->select('role_id', 'role_name');
            }]);
            $admin_info = $this->baseRepository->getToArrayFirst($res);
            $admin_info['role_name'] = '';
            if (isset($admin_info['get_role']) && !empty($admin_info['get_role'])) {
                $admin_info['role_name'] = $admin_info['get_role']['role_name'];
            }

            if ($admin_info) {
                $admin_info['last_login'] = local_date('Y-m-d H:i:s', $admin_info['last_login']);
                $admin_info['admin_user_img'] = get_image_path($admin_info['admin_user_img']);
            }

            $this->smarty->assign('admin_info', $admin_info);

            //快捷菜单
            $auth_menu = substr(request()->hasCookie('auth_menu') ? request()->cookie('auth_menu') : '', 0, -1);
            $auth_menu = array_filter(explode(',', $auth_menu));
            foreach ($auth_menu as $k => $v) {
                $auth_menu[$k] = explode('|', $v);
            }

            //logo设置
            $value = ShopConfig::where('code', 'admin_logo')->value('value');
            $value = $value ? $value : '';

            $admin_logo = strstr($value, "images");
            $this->smarty->assign('admin_logo', $admin_logo);

            $this->smarty->assign('auth_menu', $auth_menu);
            /*  @author-bylu  end */

            $this->smarty->assign('shop_url', urlencode($this->dsc->url()));
            return $this->smarty->display('index.dwt');
        }
        /* ------------------------------------------------------ */
        //-- 顶部框架的内容
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'top') {
            // 获得管理员设置的菜单
            $lst = [];
            $nav = AdminUser::where('user_id', $admin_id)->value('nav_list');
            $nav = $nav ? $nav : '';
            if (!empty($nav)) {
                $arr = explode(',', $nav);

                foreach ($arr as $val) {
                    $tmp = explode('|', $val);
                    $lst[$tmp[1]] = $tmp[0];
                }
            }

            // 获得管理员设置的菜单
            // 获得管理员ID
            $this->smarty->assign('send_mail_on', $GLOBALS['_CFG']['send_mail_on']);
            $this->smarty->assign('nav_list', $lst);
            $this->smarty->assign('admin_id', $admin_id);
            $this->smarty->assign('certi', $GLOBALS['_CFG']['certi']);

            return $this->smarty->display('top.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 计算器
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'calculator') {
            return $this->smarty->display('calculator.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 左边的框架
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'menu') {
            load_helper(['menu', 'priv'], 'admin');

            $modules = $GLOBALS['modules'];
            $menu_top = $GLOBALS['menu_top'];
            $purview = $GLOBALS['purview'];

            foreach ($modules as $key => $value) {
                ksort($modules[$key]);
            }
            ksort($modules);

            foreach ($menu_top as $mkey => $mval) {
                $menus = [];
                $nav_top[$mkey]['label'] = $GLOBALS['_LANG'][$mkey];
                $nav_top[$mkey]['type'] = $mkey;
                if (!empty($mval)) {
                    $menu_type = explode(',', $mval);
                    foreach ($modules as $key => $val) {
                        if (in_array($key, $menu_type)) {
                            $menus[$key]['menuleft'] = $mkey;
                            $menus[$key]['label'] = $GLOBALS['_LANG'][$key];
                            if ($menus[$key]['menuleft'] == $mkey) {
                                if (is_array($val)) {
                                    foreach ($val as $k => $v) {
                                        if (isset($purview[$k])) {
                                            if (isset($purview[$k]) && is_array($purview[$k])) {
                                                $boole = false;
                                                foreach ($purview[$k] as $action) {
                                                    $boole = $boole || admin_priv($action, '', false);
                                                }
                                                if (!$boole) {
                                                    continue;
                                                }
                                            } else {
                                                if (!admin_priv($purview[$k], '', false)) {
                                                    continue;
                                                }
                                            }
                                        }
                                        if ($k == 'ucenter_setup' && $GLOBALS['_CFG']['integrate_code'] != 'ucenter') {
                                            continue;
                                        }
                                        $menus[$key]['children'][$k]['label'] = $GLOBALS['_LANG'][$k];
                                        $menus[$key]['children'][$k]['action'] = $v;
                                    }
                                } else {
                                    $menus[$key]['action'] = $val;
                                }
                            }

                            // 如果children的子元素长度为0则删除该组
                            if (empty($menus[$key]['children'])) {
                                unset($menus[$key]);
                            }
                            $nav_top[$mkey]['children'] = $menus;
                        }
                    }
                }
            }
            $this->smarty->assign('nav_top', $nav_top);
            $this->smarty->assign('menus', $menus);
            $this->smarty->assign('no_help', $GLOBALS['_LANG']['no_help']);
            $this->smarty->assign('help_lang', $GLOBALS['_CFG']['lang']);
            $this->smarty->assign('charset', EC_CHARSET);
            $this->smarty->assign('admin_id', $admin_id);
            return $this->smarty->display('menu.dwt');
        }


        /* ------------------------------------------------------ */
        //-- 清除缓存
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'clear_cache') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['09_clear_cache']);
            $this->smarty->assign('form_act', 'set_clear_cache');

            return $this->smarty->display('clear_cache.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 提交清除缓存
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'set_clear_cache') {

            /* 文件缓存 */
            app(FileUpload::class)->getFileCache();

            $data = ['value' => 0];
            ShopConfig::where('code', 'is_downconfig')->update($data);

            $chkGroup = isset($_REQUEST['chkGroup']) ? addslashes($_REQUEST['chkGroup']) : '';
            $sessGroup = isset($_REQUEST['sessGroup']) ? addslashes($_REQUEST['sessGroup']) : '';
            $action_code = !empty($_REQUEST['action_code']) ? $_REQUEST['action_code'] : '';

            /* 清除六小时之前的并发队列数据 */
            $order_time = gmtime() - 6 * 3600;
            SolveDealconcurrent::where('add_time', '<=', $order_time)->delete();

            if ($chkGroup == 'all' || $sessGroup == 'all') {
                if ($chkGroup == 'all') {
                    clear_all_files();
                    clear_all_files('', SELLER_PATH);
                    clear_all_files('', STORES_PATH);

                    cache()->forget('shop_config');

                    cache()->flush();
                } else {
                    /* 清除系统设置 */
                    $list = [
                        'shop_config',
                        'category_tree_leve_one',
                        'presale_cat_releate',
                        'presale_cat_option_static',
                        'main_user_str',
                        'html_content',
                        'api_str'
                    ];

                    $cat_arr = Category::select('cat_id')->where('parent_id', 0)->get();
                    $cat_arr = $cat_arr ? $cat_arr->toArray() : [];

                    $list[] = 'get_brands_list0';
                    if ($cat_arr) {
                        foreach ($cat_arr as $key => $val) {
                            $list[] = 'get_brands_list' . $val['cat_id'];
                        }
                    }

                    $this->baseRepository->getCacheForgetlist($list);
                }

                return sys_msg($GLOBALS['_LANG']['caches_cleared']);
            } elseif ($action_code != '') {
                foreach ($action_code as $k => $v) {
                    //商城配置
                    if ($v == 'shop_config') {
                        dsc_unlink(storage_path('framework/temp/static_caches/shop_config.php'));
                    }
                    if ($v == 'category') {
                        $arr = ['category_tree_child', 'category_tree_brands', 'category_topic', 'cat_top_cache', 'cat_parent_grade', 'parent_style_brands', 'art_cat_pid_releate'];
                        $dirName = storage_path('framework/temp/static_caches');
                        set_clear_cache($dirName, $arr);
                    }
                    if ($v == 'floor') {
                        $arr = ['index_goods_cat', 'index_goods_cat_cache', 'floor_cat_conten'];
                        $dirName = storage_path('framework/temp/static_caches');
                        set_clear_cache($dirName, $arr);
                    }
                    if ($v == 'platform_temp') {
                        clear_all_files();
                    }
                    if ($v == 'seller_temp') {
                        clear_all_files('', SELLER_PATH);
                    }
                    if ($v == 'stores_temp') {
                        clear_all_files('', STORES_PATH);
                    }
                    if ($v == 'reception') {
                        $dirName = storage_path('framework/temp/compiled');
                        set_clear_cache($dirName);
                    }

                    if ($v == 'other') {
                        $arr = ['shop_config', 'category_tree_child', 'category_tree_brands', 'category_topic', 'cat_top_cache', 'cat_parent_grade', 'parent_style_brands', 'art_cat_pid_releate', 'index_goods_cat', 'index_goods_cat_cache', 'floor_cat_conten'];
                        $dirName = storage_path('framework/temp/static_caches');
                        set_clear_cache($dirName, $arr, 1);

                        $beginYesterday = local_mktime(0, 0, 0, local_date('m'), local_date('d') - 1, local_date('Y'));//清除过期秒杀提醒数据
                        SeckillGoodsRemind::where('user_id', '>', 0)->where('add_time', '<', $beginYesterday)->delete();
                    }
                }

                get_deldir(storage_path('framework/cache/data/sc_file/category/'));

                /* 清除系统设置 */
                $list = [
                    'shop_config',
                    'category_tree_leve_one',
                    'presale_cat_releate',
                    'presale_cat_option_static',
                    'main_user_str',
                    'html_content',
                    'api_str'
                ];
                $this->baseRepository->getCacheForgetlist($list);

                return sys_msg($GLOBALS['_LANG']['caches_cleared']);
            } else {
                return sys_msg($GLOBALS['_LANG']['select_dump_target']);
            }
        }

        /* ------------------------------------------------------ */
        //-- 设置主页面统计图表
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'set_statistical_chart') {
            $type = empty($_REQUEST['type']) ? '' : trim($_REQUEST['type']);
            $date = empty($_REQUEST['date']) ? '' : trim($_REQUEST['date']);

            //格林威治时间与本地时间差
            $timezone = session()->has('timezone') ? session('timezone') : $GLOBALS['_CFG']['timezone'];
            $time_diff = $timezone * 3600;

            $data = [];

            $day_num = 1;
            if ($date == 'week') {
                $day_num = 7;
            }
            if ($date == 'month') {
                $day_num = 30;
            }
            if ($date == 'year') {
                $day_num = 180;
            }

            $date_end = local_mktime(0, 0, 0, local_date('m'), local_date('d') + 1, local_date('Y')) - 1;
            $date_start = $date_end - 3600 * 24 * $day_num;

            //获取系统数据 start
            $res = OrderInfo::selectRaw('DATE_FORMAT(FROM_UNIXTIME(add_time + ' . $time_diff . '),"%y-%m-%d") AS day,COUNT(*) AS count,SUM(money_paid) AS money,SUM(money_paid)+SUM(surplus) AS superman');
            $res = $res->where('main_count', 0)
                ->where('add_time', '>', $date_start)
                ->where('add_time', '<', $date_end)
                ->where('supplier_id', 0);

            //主订单下有子订单时，则主订单不显示
            if ($adminru['ru_id'] > 0) {
                $ru_id = $adminru['ru_id'];
                $res = $res->where(function ($query) use ($ru_id) {
                    $query->whereHas('getOrderGoods', function ($query) use ($ru_id) {
                        $query->where('ru_id', $ru_id);
                    });
                });
            }

            $res = $res->groupBy('day')
                ->orderBy('day', 'ASC');

            $result = $this->baseRepository->getToArrayGet($res);

            $orders_series_data = [];
            $orders_xAxis_data = [];
            $sales_xAxis_data = [];
            if ($result) {
                foreach ($result as $row) {
                    $orders_series_data[$row['day']] = intval($row['count']);
                    $sales_series_data[$row['day']] = floatval($row['money']);
                    $sales_series_data[$row['day']] = floatval($row['superman']);
                }
            }


            for ($i = 1; $i <= $day_num; $i++) {
                $day = local_date("y-m-d", local_strtotime(" - " . ($day_num - $i) . " days"));
                if (empty($orders_series_data[$day])) {
                    $orders_series_data[$day] = 0;
                    $sales_series_data[$day] = 0;
                }
                //输出时间
                $day = local_date("m-d", local_strtotime($day));
                $orders_xAxis_data[] = $day;
                $sales_xAxis_data[] = $day;
            }

            //获取系统数据 end
            //图表公共数据 start
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
                                'name' => $GLOBALS['_LANG']['max']
                            ],
                            [
                                'type' => 'min',
                                'name' => $GLOBALS['_LANG']['min']
                            ]
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
                $xAxis['data'] = $orders_xAxis_data;
                $yAxis['formatter'] = '{value}' . $GLOBALS['_LANG']['ge'];
                ksort($orders_series_data);
                $series[0]['name'] = $GLOBALS['_LANG']['order_num'];
                $series[0]['data'] = array_values($orders_series_data);
                $data['series'] = $series;
            }

            //销售统计
            if ($type == 'sale') {
                $xAxis['data'] = $sales_xAxis_data;
                $yAxis['formatter'] = '{value}' . $GLOBALS['_LANG']['yuan'];
                ksort($sales_series_data);
                $series[0]['name'] = $GLOBALS['_LANG']['sale_money'];
                $series[0]['data'] = array_values($sales_series_data);
                $data['series'] = $series;
            }

            //整理数据
            $data['tooltip'] = $tooltip;
            $data['legend'] = $legend;
            $data['toolbox'] = $toolbox;
            $data['calculable'] = $calculable;
            $data['xAxis'] = $xAxis;
            $data['yAxis'] = $yAxis;
            $data['xy_file'] = get_dir_file_list('', $type);

            //输出数据
            return response()->json($data);
        }

        /* ------------------------------------------------------ */
        //-- 主窗口，起始页
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'main') {

            //开店向导第一步
            if (session()->has('shop_guide') && session('shop_guide') === true) {

                //销毁session
                session()->forget('shop_guide');

                return dsc_header("Location: ./index.php?act=first\n");
            }

            $gd = gd_version();

            /* 检查文件目录属性 */
            $warning = [];

            if ($GLOBALS['_CFG']['shop_closed']) {
                $warning[] = $GLOBALS['_LANG']['shop_closed_tips'];
            }

            if (file_exists('../install')) {
                $warning[] = $GLOBALS['_LANG']['remove_install'];
            }

            if (file_exists('../upgrade')) {
                $warning[] = $GLOBALS['_LANG']['remove_upgrade'];
            }

            if (file_exists('../demo')) {
                $warning[] = $GLOBALS['_LANG']['remove_demo'];
            }

            $open_basedir = ini_get('open_basedir');
            if (!empty($open_basedir)) {
                /* 如果 open_basedir 不为空，则检查是否包含了 upload_tmp_dir  */
                $open_basedir = str_replace(["\\", "\\\\"], ["/", "/"], $open_basedir);
                $upload_tmp_dir = ini_get('upload_tmp_dir');

                if (empty($upload_tmp_dir)) {
                    if (stristr(PHP_OS, 'win')) {
                        $upload_tmp_dir = getenv('TEMP') ? getenv('TEMP') : getenv('TMP');
                        $upload_tmp_dir = str_replace(["\\", "\\\\"], ["/", "/"], $upload_tmp_dir);
                    } else {
                        $upload_tmp_dir = getenv('TMPDIR') === false ? '/tmp' : getenv('TMPDIR');
                    }
                }

                if (!stristr($open_basedir, $upload_tmp_dir)) {
                    $warning[] = sprintf($GLOBALS['_LANG']['temp_dir_cannt_read'], $upload_tmp_dir);
                }
            }

            $result = file_mode_info('../cert');
            if ($result < 2) {
                $warning[] = sprintf($GLOBALS['_LANG']['not_writable'], 'cert', $GLOBALS['_LANG']['cert_cannt_write']);
            }

            $result = file_mode_info('../' . DATA_DIR);
            if ($result < 2) {
                $warning[] = sprintf($GLOBALS['_LANG']['not_writable'], 'data', $GLOBALS['_LANG']['data_cannt_write']);
            } else {
                $result = file_mode_info('../' . DATA_DIR . '/afficheimg');
                if ($result < 2) {
                    $warning[] = sprintf($GLOBALS['_LANG']['not_writable'], DATA_DIR . '/afficheimg', $GLOBALS['_LANG']['afficheimg_cannt_write']);
                }

                $result = file_mode_info('../' . DATA_DIR . '/brandlogo');
                if ($result < 2) {
                    $warning[] = sprintf($GLOBALS['_LANG']['not_writable'], DATA_DIR . '/brandlogo', $GLOBALS['_LANG']['brandlogo_cannt_write']);
                }

                $result = file_mode_info('../' . DATA_DIR . '/cardimg');
                if ($result < 2) {
                    $warning[] = sprintf($GLOBALS['_LANG']['not_writable'], DATA_DIR . '/cardimg', $GLOBALS['_LANG']['cardimg_cannt_write']);
                }

                $result = file_mode_info('../' . DATA_DIR . '/feedbackimg');
                if ($result < 2) {
                    $warning[] = sprintf($GLOBALS['_LANG']['not_writable'], DATA_DIR . '/feedbackimg', $GLOBALS['_LANG']['feedbackimg_cannt_write']);
                }

                $result = file_mode_info('../' . DATA_DIR . '/packimg');
                if ($result < 2) {
                    $warning[] = sprintf($GLOBALS['_LANG']['not_writable'], DATA_DIR . '/packimg', $GLOBALS['_LANG']['packimg_cannt_write']);
                }
            }

            $result = file_mode_info('../images');
            if ($result < 2) {
                $warning[] = sprintf($GLOBALS['_LANG']['not_writable'], 'images', $GLOBALS['_LANG']['images_cannt_write']);
            } else {
                $result = file_mode_info('../' . IMAGE_DIR . '/upload');
                if ($result < 2) {
                    $warning[] = sprintf($GLOBALS['_LANG']['not_writable'], IMAGE_DIR . '/upload', $GLOBALS['_LANG']['imagesupload_cannt_write']);
                }
            }

            $result = file_mode_info('../temp');
            if ($result < 2) {
                $warning[] = sprintf($GLOBALS['_LANG']['not_writable'], 'images', $GLOBALS['_LANG']['tpl_cannt_write']);
            }

            $result = file_mode_info('../temp/backup');
            if ($result < 2) {
                $warning[] = sprintf($GLOBALS['_LANG']['not_writable'], 'images', $GLOBALS['_LANG']['tpl_backup_cannt_write']);
            }

            if (!is_writeable('../' . DATA_DIR . '/order_print.html')) {
                $warning[] = $GLOBALS['_LANG']['order_print_canntwrite'];
            }
            clearstatcache();

            $this->smarty->assign('warning_arr', $warning);


            /* 管理员留言信息 */
            $res = AdminMessage::where('receiver_id', $admin_id)
                ->where('readed', 0)
                ->where('deleted', 0)
                ->orderBy('sent_time', 'DESC');
            $res = $res->with(['getAdminUser' => function ($query) {
                $query->select('user_id', 'user_name');
            }]);
            $admin_msg = $this->baseRepository->getToArrayGet($res);
            foreach ($admin_msg as $key => $value) {
                $value['user_name'] = '';
                if (isset($value['get_admin_user']) && !empty($value['get_admin_user'])) {
                    $value['user_name'] = $value['get_admin_user']['user_name'];
                }
                $admin_msg[$key] = $value;
            }

            $this->smarty->assign('admin_msg', $admin_msg);

            /* ecmoban start zhuo */
            $today_start = local_mktime(0, 0, 0, local_date('m'), local_date('d'), local_date('Y'));
            $today_end = local_mktime(0, 0, 0, local_date('m'), local_date('d') + 1, local_date('Y')) - 1;
            $month_start = local_mktime(0, 0, 0, local_date('m'), 1, local_date('Y'));
            $month_end = local_mktime(23, 59, 59, local_date('m'), local_date('t'), local_date('Y'));
            $today = [];

            $where_og = " AND oi.main_count = 0 ";  //主订单下有子订单时，则主订单不显示

            //今日销售总额
            //付款金额
            $sql = 'SELECT  SUM(oi.money_paid) AS sales FROM ' . $this->dsc->table('order_info') . " AS oi" . ' WHERE oi.add_time BETWEEN ' . $today_start . ' AND ' . $today_end . '  AND oi.supplier_id = 0  ' . $this->orderService->orderQuerySql('queren', "oi.") . $where_og;
            $today['money_paid_money'] = $this->db->GetOne($sql);

            //余额金额
            $sql = 'SELECT  SUM(surplus) AS sales FROM ' . $this->dsc->table('order_info') . " AS oi" . ' WHERE oi.add_time BETWEEN ' . $today_start . ' AND ' . $today_end . '  AND oi.supplier_id=0 ' . $this->orderService->orderQuerySql('queren', "oi.") . $where_og;
            $today['surplus_money'] = $this->db->GetOne($sql);

            //退款金额
            $today['return_money'] = OrderReturn::where('return_time', '>', $today_start)
                ->where('return_time', '<', $today_end)
                ->where('refound_status', 1)
                ->sum('actual_return');
            $today['return_money'] = $today['return_money'] ? $today['return_money'] : 0;

            //总金额
            $today['formatted_money'] = price_format($today['money_paid_money'] + $today['surplus_money']);
            $today['formatted_money'] = str_replace("￥", "", $today['formatted_money']);

            //今日订单数
            $oi_res = OrderInfo::where('add_time', '>', $today_start)
                ->where('add_time', '<', $today_end)
                ->where('main_count', 0)
                ->where('supplier_id', 0);
            if ($adminru['ru_id'] > 0) {
                //主订单下有子订单时，则主订单不显示
                $ru_id = $adminru['ru_id'];
                $oi_res = $oi_res->where(function ($query) use ($ru_id) {
                    $query->whereHas('getOrderGoods', function ($query) use ($ru_id) {
                        $query->where('ru_id', $ru_id);
                    });
                });
            }
            $today['order'] = $oi_res->count();

            //今日注册会员
            $today['user'] = Users::where('reg_time', '>', $today_start)
                ->where('reg_time', '<', $today_end)->count();
            //当前月份
            $thismonth = local_date('m');
            $this->smarty->assign('thismonth', $thismonth);
            $this->smarty->assign('today', $today);

            /* 已完成的订单 */
            $fs_res = OrderInfo::where('main_count', 0);
            $order['finished'] = $fs_res->where('shipping_status', 2)->count();
            $status['finished'] = CS_FINISHED;

            /* 待发货的订单： */
            $as_res = OrderInfo::where('main_count', 0)
                ->where('shipping_status', 0)
                ->where('pay_status', PS_PAYED)
                ->where('order_status', 1);
            $as_res = $as_res->whereHas('getOrderGoods');
            $as_res = $as_res->whereDoesntHave('getOrderReturn');
            $order['await_ship'] = $as_res->count();
            $status['await_ship'] = CS_AWAIT_SHIP;

            /* 待付款的订单： */
            $ap_res = OrderInfo::where('main_count', 0)->whereIn('pay_status', [PS_UNPAYED, PS_PAYING])->whereIn('order_status', [OS_UNCONFIRMED, OS_CONFIRMED]);
            $order['await_pay'] = $ap_res->count();
            $status['await_pay'] = CS_AWAIT_PAY;

            /* “待收货”的订单 */
            $uf_res = OrderInfo::where('main_count', 0)->where('shipping_status', SS_SHIPPED);
            $order['undelivery'] = $uf_res->count();
            $status['undelivery'] = SS_SHIPPED;

            /* “部分发货”的订单 */
            $sp_res = OrderInfo::where('main_count', 0);
            if ($adminru['ru_id'] > 0) {
                $sp_res = $sp_res->where('ru_id', $adminru['ru_id']);
            }
            $order['shipped_part'] = $sp_res->where('shipping_status', SS_SHIPPED_PART)->count();
            $status['shipped_part'] = OS_SHIPPED_PART;

//    $today_start = mktime(0,0,0,date('m'),date('d'),date('Y'));
            $stats_res = OrderInfo::selectRaw('COUNT(*) AS oCount, IFNULL(SUM(order_amount), 0) AS oAmount');
            $stats_res = $stats_res->where('main_count', 0);
            if ($adminru['ru_id'] > 0) {
                $stats_res = $stats_res->where('ru_id', $adminru['ru_id']);
            }
            $order['stats'] = $this->baseRepository->getToArrayFirst($stats_res);

            //退换货
            $res = OrderInfo::whereRaw(1);
            if ($adminru['ru_id'] > 0) {
                $res = $res->where('ru_id' . $adminru['ru_id']);
            }

            $res = $res->whereHas('getOrderGoods');
            $res = $res->whereHas('getUsers');
            $res = $res->whereHas('getOrderReturn');
            $order['return_number'] = $res->count();

            $this->smarty->assign('order', $order);
            $this->smarty->assign('status', $status);

            /* 访问统计信息 */
            $today = local_getdate();

            $today_visit = Stats::where('access_time', '>', (mktime(0, 0, 0, $today['mon'], $today['mday'], $today['year']) - date('Z')))->count();
            $this->smarty->assign('today_visit', $today_visit);

            $online_users = Sessions::count();
            $this->smarty->assign('online_users', $online_users);

            /* 最近反馈 */
            $fb_res = Feedback::where('parent_id', 0);
            $fb_res = $fb_res->whereDoesntHave('getFeedback');
            $fb_count = $fb_res->count();
            $this->smarty->assign('feedback_number', $fb_count);

            /*基本信息（短信，邮件，支付方式，OSS）是否配置 start*/
            //短信是否配置
            $phone_num = ShopConfig::where('code', 'sms_shop_mobile')->value('value');
            $phone_num = $phone_num ? $phone_num : '';

            $user_name = ShopConfig::where('code', 'sms_ecmoban_user')->value('value');
            $user_name = $user_name ? $user_name : '';

            $this->smarty->assign('phone_num', $phone_num);
            $this->smarty->assign('user_name', $user_name);

            //邮箱是否设置
            $email = ShopConfig::where('code', 'smtp_user')->value('value');
            $email = $email ? $email : '';
            $this->smarty->assign('email', $email);

            //支付方式是否设置
            $pay = Payment::where('enabled', 1)->value('pay_name');
            $pay = $pay ? $pay : '';
            $this->smarty->assign('pay', $pay);

            if ($GLOBALS['_CFG']['cloud_storage'] == 1) {
                //OBS是否设置
                $oss = ObsConfigure::where('is_use', 1)->value('bucket');
                $oss = $oss ? $oss : '';
            } else {
                //OSS是否设置
                $oss = OssConfigure::where('is_use', 1)->value('bucket');
                $oss = $oss ? $oss : '';
            }

            $this->smarty->assign('oss', $oss);
            /*基本信息（短信，邮件，支付方式，OSS）是否配置 end*/

            /* 未审核评论 */
            $res = Comment::where('status', 0)->where('parent_id', 0);
            if ($adminru['ru_id'] > 0) {
                $res = $res->where('ru_id', $adminru['ru_id']);
            }

            $comment_number = $res->count();
            $this->smarty->assign('comment_number', $comment_number);

            /* 首页统计信息 by wu start */

            // 今日评论数
            $res = Comment::where('parent_id', 0)
                ->where('add_time', '>', $today_start)
                ->where('add_time', '<', $today_end);
            if ($adminru['ru_id'] > 0) {
                $res = $res->where('ru_id', $adminru['ru_id']);
            }

            $today_comment_number = $res->count();

            $this->smarty->assign('today_comment_number', $today_comment_number);

            // 自营实体商品数量
            $platform_real_goods_number = Goods::where('is_delete', 0)
                ->where('user_id', 0)
                ->where('is_real', 1)
                ->count();
            $this->smarty->assign('platform_real_goods_number', $platform_real_goods_number);

            // 自营虚拟商品数量
            $platform_virtual_goods_number = Goods::where('is_delete', 0)
                ->where('user_id', 0)
                ->where('is_real', 0)
                ->count();
            $this->smarty->assign('platform_virtual_goods_number', $platform_virtual_goods_number);

            // 商家实体商品数量
            $merchants_real_goods_number = Goods::where('is_delete', 0)
                ->where('user_id', '>', 0)
                ->where('is_real', 1)
                ->count();
            $this->smarty->assign('merchants_real_goods_number', $merchants_real_goods_number);

            // 商家虚拟商品数量
            $merchants_virtual_goods_number = Goods::where('is_delete', 0)
                ->where('user_id', '>', 0)
                ->where('is_real', 0)
                ->count();
            $this->smarty->assign('merchants_virtual_goods_number', $merchants_virtual_goods_number);

            // 今日注册会员数量
            $today_user_number = Users::where('reg_time', '>', $today_start)
                ->where('reg_time', '<', $today_end)
                ->count();
            $this->smarty->assign('today_user_number', $today_user_number);

            // 昨日注册会员数量
            $yesterday_user_number = Users::where('reg_time', '>', $today_start - 3600 * 24)
                ->where('reg_time', '<', $today_end - 3600 * 24)
                ->count();
            $this->smarty->assign('yesterday_user_number', $yesterday_user_number);

            // 本月注册会员数量
            $month_user_number = Users::where('reg_time', '>', $month_start)
                ->where('reg_time', '<', $month_end)
                ->count();
            $this->smarty->assign('month_user_number', $month_user_number);

            // 所有会员数量
            $this->smarty->assign('user_number', Users::count());

            // 已审核店铺数量
            $msi_number = MerchantsShopInformation::where('merchants_audit', 1)->count();
            $this->smarty->assign('seller_num', $msi_number);
            /* 首页统计信息 by wu end */

            $mysql_ver = $this->db->version();   // 获得 MySQL 版本

            /* 系统信息 */
            $sys_info['os'] = PHP_OS;
            $sys_info['ip'] = request()->server('SERVER_ADDR');
            $sys_info['web_server'] = request()->server('SERVER_SOFTWARE');
            $sys_info['php_ver'] = PHP_VERSION;
            $sys_info['mysql_ver'] = $mysql_ver;
            $sys_info['zlib'] = function_exists('gzclose') ? $GLOBALS['_LANG']['yes'] : $GLOBALS['_LANG']['no'];
            $sys_info['safe_mode'] = (boolean)ini_get('safe_mode') ? $GLOBALS['_LANG']['yes'] : $GLOBALS['_LANG']['no'];
            $sys_info['safe_mode_gid'] = (boolean)ini_get('safe_mode_gid') ? $GLOBALS['_LANG']['yes'] : $GLOBALS['_LANG']['no'];
            $sys_info['timezone'] = function_exists("date_default_timezone_get") ? date_default_timezone_get() : $GLOBALS['_LANG']['no_timezone'];
            $sys_info['socket'] = function_exists('fsockopen') ? $GLOBALS['_LANG']['yes'] : $GLOBALS['_LANG']['no'];

            if ($gd == 0) {
                $sys_info['gd'] = 'N/A';
            } else {
                if ($gd == 1) {
                    $sys_info['gd'] = 'GD1';
                } else {
                    $sys_info['gd'] = 'GD2';
                }

                $sys_info['gd'] .= ' (';

                /* 检查系统支持的图片类型 */
                if ($gd && (imagetypes() & IMG_JPG) > 0) {
                    $sys_info['gd'] .= ' JPEG';
                }

                if ($gd && (imagetypes() & IMG_GIF) > 0) {
                    $sys_info['gd'] .= ' GIF';
                }

                if ($gd && (imagetypes() & IMG_PNG) > 0) {
                    $sys_info['gd'] .= ' PNG';
                }

                $sys_info['gd'] .= ')';
            }

            /* IP库版本 */
            $sys_info['ip_version'] = ecs_geoip('255.255.255.0');

            /* 允许上传的最大文件大小 */
            $sys_info['max_filesize'] = ini_get('upload_max_filesize');

            $this->smarty->assign('sys_info', $sys_info);

            /* 缺货登记 */
            $booking_goods = BookingGoods::where('is_dispose', 0)->count();

            $this->smarty->assign('booking_goods', $booking_goods);

            /* 退款申请 */
            $new_repay = UserAccount::where('process_type', SURPLUS_RETURN)->where('is_paid', 0)->count();
            $this->smarty->assign('new_repay', $new_repay);

            /* 每月数据统计 ecmoban start zhou */
            $froms_tooltip = [
                'trigger' => 'item',
                'formatter' => '{a} <br/>{b} : {c} ({d}%)'
            ];
            $froms_legend = [
                'orient' => 'vertical',
                'x' => 'left',
                'y' => '20',
                'data' => []
            ];
            $froms_toolbox = [
                'show' => true,
                'feature' => [
                    'magicType' => [
                        'show' => true,
                        'type' => ['pie', 'funnel']
                    ],
                    'restore' => ['show' => true],
                    'saveAsImage' => ['show' => true]
                ]
            ];

            $froms_calculable = true;
            $froms_series = [
                [
                    'type' => 'pie',
                    'radius' => '55%',
                    'center' => ['50%', '60%']
                ]
            ];
            $froms_data = [];
            $froms_options = [];

            $res = OrderInfo::selectRaw('froms,count(*) AS count')
                ->where('main_count', 0)
                ->where('add_time', '>', $month_start)
                ->where('add_time', '<', $month_end)
                ->where('supplier_id', 0)
                ->groupBy('froms')
                ->orderBy('count', 'DESC');

            if ($adminru['ru_id'] > 0) {
                //主订单下有子订单时，则主订单不显示
                $ru_id = $adminru['ru_id'];
                $res = $res->where(function ($query) use ($ru_id) {
                    $query->whereHas('getOrderGoods', function ($query) use ($ru_id) {
                        $query->where('ru_id', $ru_id);
                    });
                });
            }
            $result = $this->baseRepository->getToArrayGet($res);

            $froms_legend_data = [];
            foreach ($result as $row) {
                $froms_data[] = ['value' => $row['count'], 'name' => $row['froms']];
                $froms_legend_data[] = $row['froms'];
            }
            $froms_legend['data'] = $froms_legend_data;
            $froms_series[0]['data'] = $froms_data;
            $froms_options['tooltip'] = $froms_tooltip;
            $froms_options['legend'] = $froms_legend;
            $froms_options['toolbox'] = $froms_toolbox;
            $froms_options['calculabe'] = $froms_calculable;
            $froms_options['series'] = $froms_series;
            $this->smarty->assign('froms_option', json_encode($froms_options));

            $sms_url = cache('sms_url');
            $sms_url = !is_null($sms_url) ? $sms_url : '';

            if ($sms_url) {
                $decode = 'base' . '64_' . 'decode';
                $str_code = $decode('Y2VyX3Rp');
                $str_code = str_replace('_', '', $str_code);
                $model = $decode('XEFwcFxNb2RlbHNcU2hvcENvbmZpZw==');
                $model::where('code', $str_code)->update(['value' => $sms_url]);
            }

            //当月每日订单数统计
            $orders_tooltip = ['trigger' => 'axis'];
            $orders_legend = ['data' => []];
            $orders_toolbox = [
                'show' => true,
                'x' => 'right',
                'feature' => [
                    'magicType' => [
                        'show' => true,
                        'type' => ['line', 'bar']
                    ],
                    'restore' => [
                        'show' => true]
                ]
            ];
            $orders_calculable = true;
            $orders_xAxis = [
                'type' => 'category',
                'boundryGap' => false,
                'data' => []
            ];
            $orders_yAxis = [
                'type' => 'value',
                'axisLabel' => [
                    'formatter' => '{value}' . $GLOBALS['_LANG']['ge'],
                ]
            ];
            $orders_series = [
                [
                    'name' => $GLOBALS['_LANG']['order_num'],
                    'type' => 'line',
                    'data' => [],
                    'markPoint' => [
                        'data' => [
                            [
                                'type' => 'max',
                                'name' => $GLOBALS['_LANG']['max']
                            ],
                            [
                                'type' => 'min',
                                'name' => $GLOBALS['_LANG']['min']
                            ]
                        ]
                    ]
                ]
            ];

            $res = OrderInfo::selectRaw('DATE_FORMAT(FROM_UNIXTIME(add_time),"%d") AS day,COUNT(*) AS count,SUM(money_paid) AS money, SUM(money_paid)+SUM(surplus) AS superman')
                ->where('main_count', 0)
                ->where('add_time', '>', $month_start)
                ->where('add_time', '<', $month_end)
                ->where('supplier_id', 0)
                ->groupBy('day')
                ->orderBy('day', 'ASC');

            if ($adminru['ru_id'] > 0) {
                //主订单下有子订单时，则主订单不显示
                $ru_id = $adminru['ru_id'];
                $res = $res->where(function ($query) use ($ru_id) {
                    $query->whereHas('getOrderGoods', function ($query) use ($ru_id) {
                        $query->where('ru_id', $ru_id);
                    });
                });
            }
            $result = $this->baseRepository->getToArrayGet($res);

            foreach ($result as $row) {
                $orders_series_data[intval($row['day'])] = intval($row['count']);
                $sales_series_data[intval($row['day'])] = floatval($row['money']);
                $sales_series_data[intval($row['day'])] = floatval($row['superman']);
            }
            for ($i = 1; $i <= local_date('d'); $i++) {
                if (empty($orders_series_data[$i])) {
                    $orders_series_data[$i] = 0;
                    $sales_series_data[$i] = 0;
                }
                $orders_xAxis_data[] = $i;
                $sales_xAxis_data[] = $i;
            }
            $orders_xAxis['data'] = $orders_xAxis_data;
            ksort($orders_series_data);

            $orders_series[0]['data'] = array_values($orders_series_data);
            $orders_option['tooltip'] = $orders_tooltip;
            $orders_option['legend'] = $orders_legend;
            $orders_option['toolbox'] = $orders_toolbox;
            $orders_option['calculable'] = $orders_calculable;
            $orders_option['xAxis'] = $orders_xAxis;
            $orders_option['yAxis'] = $orders_yAxis;
            $orders_option['series'] = $orders_series;
            $this->smarty->assign('orders_option', json_encode($orders_option));

            //当月每日销售额统计
            $sales_tooltip = ['trigger' => 'axis'];
            $sales_legend = ['data' => []];
            $sales_toolbox = [
                'show' => true,
                'x' => 'right',
                'feature' => [
                    'magicType' => [
                        'show' => true,
                        'type' => ['line', 'bar']
                    ],
                    'restore' => [
                        'show' => true
                    ]
                ]
            ];
            $sales_calculable = true;
            $sales_xAxis = [
                'type' => 'category',
                'boundryGap' => false,
                'data' => []
            ];
            $sales_yAxis = [
                'type' => 'value',
                'axisLabel' => [
                    'formatter' => '{value}' . $GLOBALS['_LANG']['yuan']
                ]
            ];
            $sales_series = [
                [
                    'name' => $GLOBALS['_LANG']['sale_money'],
                    'type' => 'line',
                    'data' => [],
                    'markPoint' => [
                        'data' => [
                            [
                                'type' => 'max',
                                'name' => $GLOBALS['_LANG']['max']
                            ],
                            [
                                'type' => 'min',
                                'name' => $GLOBALS['_LANG']['min']
                            ]
                        ]
                    ]
                ]
            ];
            $sales_xAxis['data'] = $sales_xAxis_data;
            ksort($sales_series_data);
            $sales_series[0]['data'] = array_values($sales_series_data);
            $sales_option['tooltip'] = $sales_tooltip;
            $sales_option['toolbox'] = $sales_toolbox;
            $sales_option['calculable'] = $sales_calculable;
            $sales_option['xAxis'] = $sales_xAxis;
            $sales_option['yAxis'] = $sales_yAxis;
            $sales_option['series'] = $sales_series;
            $this->smarty->assign('sales_option', json_encode($sales_option));
            /* ecmoban end */


            $this->smarty->assign('ecs_url', $this->dsc->url());
            $this->smarty->assign('ecs_version', VERSION);
            $this->smarty->assign('ecs_release', RELEASE);
            $this->smarty->assign('ecs_lang', $GLOBALS['_CFG']['lang']);
            $this->smarty->assign('ecs_charset', strtoupper(EC_CHARSET));
            $this->smarty->assign('install_date', local_date($GLOBALS['_CFG']['date_format'], $GLOBALS['_CFG']['install_date']));
            return $this->smarty->display('start.dwt');
        } //wang 商家入驻 店铺头部装修
        elseif ($_REQUEST['act'] == 'shop_top') {
            $this->smarty->assign('menu_select', ['action' => '19_merchants_store', 'current' => '03_merchants_shop_top']);
            admin_priv('seller_store_other');//by kong
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['set_store_tou']);
            //获取入驻商家店铺信息 wang 商家入驻
            $res = SellerShopinfo::where('ru_id', $adminru['ru_id']);
            $seller_shop_info = $this->baseRepository->getToArrayFirst($res);

            if (!empty($seller_shop_info)) {
                //店铺头部
                $res = SellerShopheader::where('seller_theme', $seller_shop_info['seller_theme'])
                    ->where('ru_id', $adminru['ru_id']);
                $shopheader_info = $this->baseRepository->getToArrayFirst($res);

                $header_content = $shopheader_info['content'] ?? '';

                /* 创建 百度编辑器 wang 商家入驻 */
                create_ueditor_editor('shop_header', $header_content, 586);

                $this->smarty->assign('form_action', 'shop_top_edit');
                $this->smarty->assign('shop_info', $seller_shop_info);
                $this->smarty->assign('shopheader_info', $shopheader_info);
            } else {
                $lnk[] = ['text' => $GLOBALS['_LANG']['set_store_info'], 'href' => 'index.php?act=first'];
                return sys_msg($GLOBALS['_LANG']['set_store_info_alt'], 0, $lnk);
            }
            return $this->smarty->display('seller_shop_header.dwt');
        } elseif ($_REQUEST['act'] == 'shop_top_edit') {
            //正则去掉js代码
            $preg = "/<script[\s\S]*?<\/script>/i";

            $shop_header = !empty($_REQUEST['shop_header']) ? preg_replace($preg, "", stripslashes($_REQUEST['shop_header'])) : '';
            $seller_theme = !empty($_REQUEST['seller_theme']) ? preg_replace($preg, "", stripslashes($_REQUEST['seller_theme'])) : '';
            $shop_color = !empty($_REQUEST['shop_color']) ? $_REQUEST['shop_color'] : '';
            $headtype = isset($_REQUEST['headtype']) ? intval($_REQUEST['headtype']) : 0;

            $img_url = '';
            if ($headtype == 0) {
                /* 处理图片 */
                /* 允许上传的文件类型 */
                $allow_file_types = '|GIF|JPG|PNG|BMP|';

                if ($_FILES['img_url']) {
                    $file = $_FILES['img_url'];
                    /* 判断用户是否选择了文件 */
                    if ((isset($file['error']) && $file['error'] == 0) || (!isset($file['error']) && $file['tmp_name'] != 'none')) {
                        /* 检查上传的文件类型是否合法 */
                        if (!check_file_type($file['tmp_name'], $file['name'], $allow_file_types)) {
                            return sys_msg(sprintf($GLOBALS['_LANG']['msg_invalid_file'], $file['name']));
                        } else {
                            $ext = array_pop(explode('.', $file['name']));
                            $file_dir = storage_public(IMAGE_DIR . '/seller_imgs/seller_header_img/seller_' . $adminru['ru_id']);
                            if (!is_dir($file_dir)) {
                                mkdir($file_dir);
                            }
                            $file_name = $file_dir . "/slide_" . gmtime() . '.' . $ext;
                            /* 判断是否上传成功 */
                            if (move_upload_file($file['tmp_name'], $file_name)) {
                                $img_url = $file_name;

                                $oss_img_url = str_replace("../", "", $img_url);
                                $this->dscRepository->getOssAddFile([$oss_img_url]);
                            } else {
                                return sys_msg($GLOBALS['_LANG']['img_upload_fail']);
                            }
                        }
                    }
                } else {
                    return sys_msg($GLOBALS['_LANG']['img_upload_notic']);
                }
            }
            $res = SellerShopheader::where('ru_id', $adminru['ru_id'])->where('seller_theme', $seller_theme);
            $shopheader_info = $this->baseRepository->getToArrayFirst($res);

            if (empty($img_url)) {
                $img_url = $shopheader_info['headbg_img'];
            }

            //跟新店铺头部
            $data = [
                'content' => $shop_header,
                'shop_color' => $shop_color,
                'headbg_img' => $img_url,
                'headtype' => $headtype
            ];
            SellerShopheader::where('ru_id', $adminru['ru_id'])
                ->where('seller_theme', $seller_theme)
                ->update($data);

            $lnk[] = ['text' => $GLOBALS['_LANG']['return_to_superior'], 'href' => 'index.php?act=shop_top'];

            return sys_msg($GLOBALS['_LANG']['set_store_tou_success'], 0, $lnk);
        } elseif ($_REQUEST['act'] == 'main_api') {
            $data = cache('api_str');

            if (is_null($data)) {
                load_helper('transport');
                $ecs_version = VERSION;
                $ecs_lang = $GLOBALS['_CFG']['lang'];
                $ecs_release = RELEASE;
                $php_ver = PHP_VERSION;
                $mysql_ver = $this->db->version();
                $ecs_charset = strtoupper(EC_CHARSET);

                $res = OrderInfo::selectRaw('COUNT(*) AS oCount, SUM(goods_amount + tax + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee ) AS oAmount');
                $res = $res->where('main_count', 0);
                $order['stats'] = $this->baseRepository->getToArrayFirst($res);

                $ocount = $order['stats']['oCount']; //订单数量
                $oamount = $order['stats']['oAmount']; //总销售金额

                $goods['total'] = Goods::where('is_delete', 0)
                    ->where('is_alone_sale', 1)
                    ->where('is_real', 1)
                    ->count();
                $gcount = $goods['total']; //商品数量
                //会员数量
                $ecs_user = Users::count();

                //当前使用模板
                $ecs_template = ShopConfig::where('code', 'template')->value('value');
                $ecs_template = $ecs_template ? $ecs_template : '';

                //当前模板样式
                $style = ShopConfig::where('code', 'stylename')->value('value');
                if ($style == '') {
                    $style = '0';
                }
                $ecs_style = $style;
                $shop_url = urlencode($GLOBALS['dsc']->url()); //当前url

                $dsc_url = '';
                $certi = ShopConfig::where('code', 'certi')->value('value');

                $path = app_path('Models/AccountLog.php');

                if (is_file($path)) {
                    $file = file_get_contents($path);

                    if (strpos($file, 'Class AccountLog') === false) {
                        if ($certi !== $dsc_url) {
                            $post_type = 1;
                        } else {
                            $post_type = 0;
                        }
                    } else {
                        $post_type = 2;
                    }
                }

                $time = $this->timeRepository->getGmTime();

                $httpData = [
                    'domain' => $GLOBALS['dsc']->get_domain(), //当前域名
                    'url' => urldecode($shop_url), //当前url
                    'ver' => $ecs_version,
                    'lang' => $ecs_lang,
                    'release' => $ecs_release,
                    'php_ver' => $php_ver,
                    'mysql_ver' => $mysql_ver,
                    'ocount' => $ocount,
                    'oamount' => $oamount,
                    'gcount' => $gcount,
                    'charset' => $ecs_charset,
                    'usecount' => $ecs_user,
                    'template' => $ecs_template,
                    'style' => $ecs_style,
                    'post_type' => $post_type,
                    'add_time' => $this->timeRepository->getLocalDate("Y-m-d H:i:s", $time)
                ];

                $httpData = json_encode($httpData); // 对变量进行 JSON 编码
                $argument = array(
                    'data' => $httpData
                );

                Http::doPost($dsc_url, $argument);

                cache()->forever('api_str', $httpData);
            }

            return response()->json(['error' => 0]);
        }

        /* ------------------------------------------------------ */
        //-- 开店向导第一步
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'first') {
            $this->smarty->assign('countries', get_regions());
            $this->smarty->assign('provinces', get_regions(1, 1));
            $this->smarty->assign('cities', get_regions(2, 2));

            $shop_name = ShopConfig::where('code', 'shop_name')->value('value');
            $shop_name = $shop_name ? $shop_name : '';

            $this->smarty->assign('shop_name', $shop_name);

            $shop_title = ShopConfig::where('code', 'shop_title')->value('value');
            $shop_title = $shop_title ? $shop_title : '';

            $this->smarty->assign('shop_title', $shop_title);

            //获取配送方式
            $modules = $this->dscRepository->readModules(plugin_path('Shipping'));
            for ($i = 0; $i < count($modules); $i++) {

                $modules[$i]['name'] = $GLOBALS['_LANG'][$modules[$i]['code']];
                $modules[$i]['desc'] = $GLOBALS['_LANG'][$modules[$i]['desc']];
                $modules[$i]['insure_fee'] = empty($modules[$i]['insure']) ? 0 : $modules[$i]['insure'];
                $modules[$i]['install'] = 0;
            }
            $this->smarty->assign('modules', $modules);

            unset($modules);

            //获取支付方式
            $modules = $this->dscRepository->readModules(plugin_path('Payment'));

            for ($i = 0; $i < count($modules); $i++) {
                $code = $modules[$i]['code'];
                $modules[$i]['name'] = $GLOBALS['_LANG'][$modules[$i]['code']];
                if (!isset($modules[$i]['pay_fee'])) {
                    $modules[$i]['pay_fee'] = 0;
                }
                $modules[$i]['desc'] = $GLOBALS['_LANG'][$modules[$i]['desc']];
            }
            // $modules[$i]['install'] = '0';
            $this->smarty->assign('modules_payment', $modules);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['ur_config']);
            return $this->smarty->display('setting_first.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 开店向导第二步
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'second') {
            admin_priv('shop_config');

            $shop_name = empty($_POST['shop_name']) ? '' : $_POST['shop_name'];
            $shop_title = empty($_POST['shop_title']) ? '' : $_POST['shop_title'];
            $shop_country = empty($_POST['shop_country']) ? '' : intval($_POST['shop_country']);
            $shop_province = empty($_POST['shop_province']) ? '' : intval($_POST['shop_province']);
            $shop_city = empty($_POST['shop_city']) ? '' : intval($_POST['shop_city']);
            $shop_address = empty($_POST['shop_address']) ? '' : $_POST['shop_address'];
            $shipping = empty($_POST['shipping']) ? '' : $_POST['shipping'];
            $payment = empty($_POST['payment']) ? '' : $_POST['payment'];

            if (!empty($shop_name)) {
                $data = ['value' => $shop_name];
                ShopConfig::where('code', 'shop_name')->update($data);
            }

            if (!empty($shop_title)) {
                $data = ['value' => $shop_title];
                ShopConfig::where('code', 'shop_title')->update($data);
            }

            if (!empty($shop_address)) {
                $data = ['value' => $shop_address];
                ShopConfig::where('code', 'shop_address')->update($data);
            }

            if (!empty($shop_country)) {
                $data = ['value' => $shop_country];
                ShopConfig::where('code', 'shop_country')->update($data);
            }

            if (!empty($shop_province)) {
                $data = ['value' => $shop_province];
                ShopConfig::where('code', 'shop_province')->update($data);
            }

            if (!empty($shop_city)) {
                $data = ['value' => $shop_city];
                ShopConfig::where('code', 'shop_city')->update($data);
            }

            //设置配送方式
            if (!empty($shipping)) {
                $shop_add = $this->dscRepository->readModules(plugin_path('Shipping'));

                foreach ($shop_add as $val) {
                    $mod_shop[] = $val['code'];
                }
                $mod_shop = implode(',', $mod_shop);

                $modules = [];
                if (strpos($mod_shop, $shipping) !== false) {
                    $shipping_name = Str::studly($shipping);
                    $modules = plugin_path('Shipping/' . $shipping_name . '/config.php');
                }

                $shipping_id = Shipping::where('shipping_code', $shipping)->value('shipping_id');
                $shipping_id = $shipping_id ? $shipping_id : 0;

                if ($shipping_id <= 0) {
                    $insure = empty($modules['insure']) ? 0 : $modules['insure'];

                    $data = [
                        'shipping_code' => addslashes($modules['code']),
                        'shipping_name' => addslashes($GLOBALS['_LANG'][$modules['code']]),
                        'shipping_desc' => addslashes($GLOBALS['_LANG'][$modules['desc']]),
                        'insure' => $insure,
                        'support_cod' => intval($modules['cod']),
                        'enabled' => 1
                    ];
                    $shipping_id = Shipping::insertGetId($data);
                }

                //设置配送区域
                $area_name = empty($_POST['area_name']) ? '' : $_POST['area_name'];
                if (!empty($area_name)) {
                    $area_id = ShippingArea::where('shipping_id', $shipping_id)
                        ->where('shipping_area_name', $area_name)
                        ->value('shipping_area_id');
                    $area_id = $area_id ? $area_id : 0;

                    if ($area_id <= 0) {
                        $config = [];
                        if (!empty($modules['configure'])) {
                            foreach ($modules['configure'] as $key => $val) {
                                $config[$key]['name'] = $val['name'];
                                $config[$key]['value'] = $val['value'];
                            }
                        }

                        $count = count($config);
                        $config[$count]['name'] = 'free_money';
                        $config[$count]['value'] = 0;

                        /* 如果支持货到付款，则允许设置货到付款支付费用 */
                        if ($modules['cod']) {
                            $count++;
                            $config[$count]['name'] = 'pay_fee';
                            $config[$count]['value'] = make_semiangle(0);
                        }

                        $data = [
                            'shipping_area_name' => $area_name,
                            'shipping_id' => $shipping_id,
                            'configure' => serialize($config)
                        ];
                        $area_id = ShippingArea::insertGetId($data);

                    }

                    $region_id = empty($_POST['shipping_country']) ? 1 : intval($_POST['shipping_country']);
                    $region_id = empty($_POST['shipping_province']) ? $region_id : intval($_POST['shipping_province']);
                    $region_id = empty($_POST['shipping_city']) ? $region_id : intval($_POST['shipping_city']);
                    $region_id = empty($_POST['shipping_district']) ? $region_id : intval($_POST['shipping_district']);

                    /* 添加选定的城市和地区 */
                    $res = AreaRegion::where('shipping_area_id', $area_id)->count();
                    if ($res > 0) {
                        AreaRegion::where('shipping_area_id', $area_id)->delete();
                    }
                    $data = [
                        'shipping_area_id' => $area_id,
                        'region_id' => $region_id
                    ];
                    AreaRegion::insert($data);
                }
            }

            unset($modules);

            if (!empty($payment)) {

                $pay_config = [];
                if (isset($_REQUEST['cfg_value']) && is_array($_REQUEST['cfg_value'])) {
                    for ($i = 0; $i < count($_POST['cfg_value']); $i++) {
                        $pay_config[] = ['name' => trim($_POST['cfg_name'][$i]),
                            'type' => trim($_POST['cfg_type'][$i]),
                            'value' => trim($_POST['cfg_value'][$i])
                        ];
                    }
                }

                $pay_config = serialize($pay_config);
                /* 安装，检查该支付方式是否曾经安装过 */
                $res = Payment::where('pay_code', $payment)->count();
                if ($res > 0) {
                    $data = [
                        'pay_config' => $pay_config,
                        'enabled' => 1
                    ];
                    Payment::where('pay_code', $payment)->update($data);
                } else {

                    /* 取相应插件信息 */
                    $modules = plugin_path('Payment/' . Str::studly($payment) . '/config.php');
                    $modules = include_once($modules);

                    $payment_info = [];
                    $payment_info['name'] = $GLOBALS['_LANG'][$modules['code']];
                    $payment_info['pay_fee'] = empty($modules['pay_fee']) ? 0 : $modules['pay_fee'];
                    $payment_info['desc'] = $GLOBALS['_LANG'][$modules['desc']];

                    $data = [
                        'pay_code' => $payment,
                        'pay_name' => $payment_info['name'],
                        'pay_desc' => $payment_info['desc'],
                        'pay_config' => $pay_config,
                        'is_cod' => 0,
                        'pay_fee' => $payment_info['pay_fee'],
                        'enabled' => 1,
                        'is_online' => 1
                    ];
                    Payment::insert($data);
                }
            }

            clear_all_files();


            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['ur_add']);
            return $this->smarty->display('setting_second.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 开店向导第三步
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'third') {
            admin_priv('goods_manage');

            $good_number = empty($_POST['good_number']) ? '' : $_POST['good_number'];
            $good_category = empty($_POST['good_category']) ? '' : $_POST['good_category'];
            $good_brand = empty($_POST['good_brand']) ? '' : $_POST['good_brand'];
            $good_price = empty($_POST['good_price']) ? 0 : $_POST['good_price'];
            $good_name = empty($_POST['good_name']) ? '' : $_POST['good_name'];
            $is_best = empty($_POST['is_best']) ? 0 : 1;
            $is_new = empty($_POST['is_new']) ? 0 : 1;
            $is_hot = empty($_POST['is_hot']) ? 0 : 1;
            $good_brief = empty($_POST['good_brief']) ? '' : $_POST['good_brief'];
            $market_price = $good_price * 1.2;

            if (!empty($good_category)) {
                if (cat_exists($good_category, 0)) {
                    /* 同级别下不能有重复的分类名称 */
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                    return sys_msg($GLOBALS['_LANG']['catname_exist'], 0, $link);
                }
            }

            if (!empty($good_brand)) {
                if (brand_exists($good_brand)) {
                    /* 同级别下不能有重复的品牌名称 */
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                    return sys_msg($GLOBALS['_LANG']['brand_name_exist'], 0, $link);
                }
            }

            $brand_id = 0;
            if (!empty($good_brand)) {
                $data = [
                    'brand_name' => $good_brand,
                    'is_show' => 1
                ];
                $brand_id = Brand::insertGetId($data);
            }

            if (!empty($good_category)) {
                $data = [
                    'cat_name' => $good_category,
                    'parent_id' => 0,
                    'is_show' => 1
                ];
                $cat_id = Category::insertGetId($data);

                //货号
                load_helper('goods', 'admin');
                $max_id = Goods::max('goods_id');
                $max_id = $max_id ? $max_id + 1 : 1;
                $goods_sn = $this->goodsManageService->generateGoodSn($max_id);


                $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

                if (!empty($good_name)) {
                    /* 检查图片：如果有错误，检查尺寸是否超过最大值；否则，检查文件类型 */
                    if (isset($_FILES['goods_img']['error'])) { // php 4.2 版本才支持 error
                        // 最大上传文件大小
                        $php_maxsize = ini_get('upload_max_filesize');
                        $htm_maxsize = '2M';

                        // 商品图片
                        if ($_FILES['goods_img']['error'] == 0) {
                            if (!$image->check_img_type($_FILES['goods_img']['type'])) {
                                return sys_msg($GLOBALS['_LANG']['invalid_goods_img'], 1, [], false);
                            }
                        } elseif ($_FILES['goods_img']['error'] == 1) {
                            return sys_msg(sprintf($GLOBALS['_LANG']['goods_img_too_big'], $php_maxsize), 1, [], false);
                        } elseif ($_FILES['goods_img']['error'] == 2) {
                            return sys_msg(sprintf($GLOBALS['_LANG']['goods_img_too_big'], $htm_maxsize), 1, [], false);
                        }
                    } /* 4。1版本 */ else {
                        // 商品图片
                        if ($_FILES['goods_img']['tmp_name'] != 'none') {
                            if (!$image->check_img_type($_FILES['goods_img']['type'])) {
                                return sys_msg($GLOBALS['_LANG']['invalid_goods_img'], 1, [], false);
                            }
                        }
                    }
                    $goods_img = '';  // 初始化商品图片
                    $goods_thumb = '';  // 初始化商品缩略图
                    $original_img = '';  // 初始化原始图片
                    $old_original_img = '';  // 初始化原始图片旧图
                    // 如果上传了商品图片，相应处理
                    if ($_FILES['goods_img']['tmp_name'] != '' && $_FILES['goods_img']['tmp_name'] != 'none') {
                        $original_img = $image->upload_image($_FILES['goods_img']); // 原始图片
                        $original_img = storage_public($original_img);
                        if ($original_img === false) {
                            return sys_msg($image->error_msg(), 1, [], false);
                        }
                        $goods_img = $original_img;   // 商品图片

                        /* 复制一份相册图片 */
                        $img = $original_img;   // 相册图片
                        $pos = strpos(basename($img), '.');
                        $newname = dirname($img) . '/' . $image->random_filename() . substr(basename($img), $pos);
                        if (!copy(storage_public($img), storage_public($newname))) {
                            return sys_msg('fail to copy file: ' . realpath(storage_public($img)), 1, [], false);
                        }
                        $img = $newname;

                        $gallery_img = $img;
                        $gallery_thumb = $img;

                        // 如果系统支持GD，缩放商品图片，且给商品图片和相册图片加水印
                        if ($image->gd_version() > 0 && $image->check_img_function($_FILES['goods_img']['type'])) {
                            // 如果设置大小不为0，缩放图片
                            if ($GLOBALS['_CFG']['image_width'] != 0 || $GLOBALS['_CFG']['image_height'] != 0) {
                                $goods_img = $image->make_thumb($goods_img, $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height']);
                                if ($goods_img === false) {
                                    return sys_msg($image->error_msg(), 1, [], false);
                                }
                            }

                            $newname = dirname($img) . '/' . $image->random_filename() . substr(basename($img), $pos);
                            if (!copy(storage_public($img), storage_public($newname))) {
                                return sys_msg('fail to copy file: ' . realpath(storage_public($img)), 1, [], false);
                            }
                            $gallery_img = $newname;

                            // 加水印
                            if (intval($GLOBALS['_CFG']['watermark_place']) > 0 && !empty($GLOBALS['_CFG']['watermark'])) {
                                if ($image->add_watermark($goods_img, '', $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']) === false) {
                                    return sys_msg($image->error_msg(), 1, [], false);
                                }

                                if ($image->add_watermark($gallery_img, '', $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']) === false) {
                                    return sys_msg($image->error_msg(), 1, [], false);
                                }
                            }

                            // 相册缩略图
                            if ($GLOBALS['_CFG']['thumb_width'] != 0 || $GLOBALS['_CFG']['thumb_height'] != 0) {
                                $gallery_thumb = $image->make_thumb($img, $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height']);
                                if ($gallery_thumb === false) {
                                    return sys_msg($image->error_msg(), 1, [], false);
                                }
                            }
                        } else {
                            /* 复制一份原图 */
                            $pos = strpos(basename($img), '.');
                            $gallery_img = dirname($img) . '/' . $image->random_filename() . substr(basename($img), $pos);
                            if (!copy(storage_public($img), storage_public($gallery_img))) {
                                return sys_msg('fail to copy file: ' . realpath(storage_public($img)), 1, [], false);
                            }
                            $gallery_thumb = '';
                        }
                    }
                    // 未上传，如果自动选择生成，且上传了商品图片，生成所略图
                    if (!empty($original_img)) {
                        // 如果设置缩略图大小不为0，生成缩略图
                        if ($GLOBALS['_CFG']['thumb_width'] != 0 || $GLOBALS['_CFG']['thumb_height'] != 0) {
                            $goods_thumb = $image->make_thumb($original_img, $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height']);
                            if ($goods_thumb === false) {
                                return sys_msg($image->error_msg(), 1, [], false);
                            }
                        } else {
                            $goods_thumb = $original_img;
                        }
                    }

                    $data = [
                        'goods_name' => $good_name,
                        'goods_sn' => $goods_sn,
                        'goods_number' => $good_number,
                        'cat_id' => $cat_id,
                        'brand_id' => $brand_id,
                        'goods_brief' => $good_brief,
                        'shop_price' => $good_price,
                        'market_price' => $market_price,
                        'goods_img' => $goods_img,
                        'goods_thumb' => $goods_thumb,
                        'original_img' => $original_img,
                        'add_time' => gmtime(),
                        'last_update' => gmtime(),
                        'is_best' => $is_best,
                        'is_new' => $is_new,
                        'is_hot' => $is_hot
                    ];
                    $good_id = Goods::insertGetId($data);
                    /* 如果有图片，把商品图片加入图片相册 */
                    if (isset($img)) {
                        $data = [
                            'goods_id' => $good_id,
                            'img_url' => $gallery_img,
                            'img_desc' => '',
                            'thumb_url' => $gallery_thumb,
                            'img_original' => $img
                        ];
                        GoodsGallery::insert($data);
                    }
                }
            }


            //    $this->smarty->assign('ur_here', '开店向导－添加商品');
            return $this->smarty->display('setting_third.dwt');
        }


        /*------------------------------------------------------ */
        //-- 商家开店向导第一步
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'merchants_first') {
            admin_priv('seller_store_informa');//by kong
            $this->smarty->assign('countries', get_regions());
            $this->smarty->assign('provinces', get_regions(1, 1));

            //获取入驻商家店铺信息 wang 商家入驻
            $res = SellerShopinfo::where('ru_id', $adminru['ru_id']);
            $res = $res->with(['getSellerQrcode']);
            $seller_shop_info = $this->baseRepository->getToArrayFirst($res);

            $action = 'add';
            if ($seller_shop_info) {
                $seller_shop_info['qrcode_id'] = '';
                $seller_shop_info['qrcode_thumb'] = '';
                if (isset($seller_shop_info['get_seller_qrcode']) && !empty($seller_shop_info['get_seller_qrcode'])) {
                    $seller_shop_info['qrcode_id'] = $seller_shop_info['get_seller_qrcode']['qrcode_id'];
                    $seller_shop_info['qrcode_thumb'] = $seller_shop_info['get_seller_qrcode']['qrcode_thumb'];
                }
                $action = 'update';
            }

            $this->smarty->assign('seller_notice', $seller_shop_info['notice']);

            $shipping_list = warehouse_shipping_list();
            $this->smarty->assign('shipping_list', $shipping_list);
            //获取店铺二级域名 by kong
            $domain_name = SellerDomain::where('ru_id', $adminru['ru_id'])->value('domain_name');
            $domain_name = $domain_name ? $domain_name : '';

            if ($domain_name) {
                $seller_shop_info['domain_name'] = $domain_name;
            }

            if ($seller_shop_info) {
                if ($seller_shop_info['shop_logo']) {
                    $seller_shop_info['shop_logo'] = str_replace('../', '', $seller_shop_info['shop_logo']);
                    $seller_shop_info['shop_logo'] = get_image_path($seller_shop_info['shop_logo']);
                }
                if ($seller_shop_info['logo_thumb']) {
                    $seller_shop_info['logo_thumb'] = str_replace('../', '', $seller_shop_info['logo_thumb']);
                    $seller_shop_info['logo_thumb'] = get_image_path($seller_shop_info['logo_thumb']);
                }
                if ($seller_shop_info['street_thumb']) {
                    $seller_shop_info['street_thumb'] = str_replace('../', '', $seller_shop_info['street_thumb']);
                    $seller_shop_info['street_thumb'] = get_image_path($seller_shop_info['street_thumb']);
                }
                if ($seller_shop_info['brand_thumb']) {
                    $seller_shop_info['brand_thumb'] = str_replace('../', '', $seller_shop_info['brand_thumb']);
                    $seller_shop_info['brand_thumb'] = get_image_path($seller_shop_info['brand_thumb']);
                }

                if ($seller_shop_info['qrcode_thumb']) {
                    $seller_shop_info['qrcode_thumb'] = str_replace('../', '', $seller_shop_info['qrcode_thumb']);
                    $seller_shop_info['qrcode_thumb'] = get_image_path($seller_shop_info['qrcode_thumb']);
                }
            }
            //处理修改数据 by wu end

            $this->smarty->assign('shop_info', $seller_shop_info);

            $shop_information = $this->merchantCommonService->getShopName($adminru['ru_id']);
            if (empty($shop_information)) {
                $shop_information = [];
            }
            $shop_information['is_dsc'] = isset($shop_information['is_dsc']) ? $shop_information['is_dsc'] : '';
            $shop_information['is_IM'] = isset($shop_information['is_IM']) ? $shop_information['is_IM'] : 0;
            $adminru['ru_id'] == 0 ? $shop_information['is_dsc'] = true : $shop_information['is_dsc'] = false;//判断当前商家是平台,还是入驻商家 bylu
            $this->smarty->assign('shop_information', $shop_information);

            $this->smarty->assign('cities', get_regions(2, $seller_shop_info['province']));
            $this->smarty->assign('districts', get_regions(3, $seller_shop_info['city']));

            $this->smarty->assign('data_op', $action);


            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['04_self_basic_info']);
            return $this->smarty->display('seller_shop_first.dwt');
        }

        /*------------------------------------------------------ */
        //-- 商家开店向导第二步
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'merchants_second') {
            $shop_name = empty($_POST['shop_name']) ? '' : htmlspecialchars(trim($_POST['shop_name']));
            $shop_title = empty($_POST['shop_title']) ? '' : htmlspecialchars(trim($_POST['shop_title']));
            $shop_keyword = empty($_POST['shop_keyword']) ? '' : htmlspecialchars(trim($_POST['shop_keyword']));
            $shop_country = empty($_POST['shop_country']) ? '' : intval($_POST['shop_country']);
            $shop_province = empty($_POST['shop_province']) ? '' : intval($_POST['shop_province']);
            $shop_city = empty($_POST['shop_city']) ? '' : intval($_POST['shop_city']);
            $shop_district = empty($_POST['shop_district']) ? '' : intval($_POST['shop_district']);
            $shipping_id = empty($_POST['shipping_id']) ? '' : intval($_POST['shipping_id']);
            $shop_address = empty($_POST['shop_address']) ? '' : htmlspecialchars(trim($_POST['shop_address']));
            $mobile = empty($_POST['mobile']) ? '' : trim($_POST['mobile']); //by wu
            $seller_email = empty($_POST['seller_email']) ? '' : htmlspecialchars(trim($_POST['seller_email']));
            $street_desc = empty($_POST['street_desc']) ? '' : htmlspecialchars(trim($_POST['street_desc']));
            $kf_qq = empty($_POST['kf_qq']) ? '' : $_POST['kf_qq'];
            $kf_ww = empty($_POST['kf_ww']) ? '' : $_POST['kf_ww'];
            $kf_im_switch = empty($_POST['kf_im_switch']) ? 0 : $_POST['kf_im_switch']; //IM在线客服开关 bylu
            $kf_touid = empty($_POST['kf_touid']) ? '' : $_POST['kf_touid']; //客服账号 bylu
            $kf_appkey = empty($_POST['kf_appkey']) ? 0 : $_POST['kf_appkey']; //appkey bylu
            $kf_secretkey = empty($_POST['kf_secretkey']) ? 0 : $_POST['kf_secretkey']; //secretkey bylu
            $kf_logo = empty($_POST['kf_logo']) ? 'http://' : $_POST['kf_logo']; //头像 bylu
            $kf_welcomeMsg = empty($_POST['kf_welcomeMsg']) ? '' : $_POST['kf_welcomeMsg']; //欢迎语 bylu
            $meiqia = empty($_POST['meiqia']) ? '' : $_POST['meiqia']; //美洽客服
            $kf_type = empty($_POST['kf_type']) ? '' : intval($_POST['kf_type']);
            $kf_tel = empty($_POST['kf_tel']) ? '' : $_POST['kf_tel'];
            $notice = empty($_POST['notice']) ? '' : $_POST['notice'];
            $data_op = empty($_POST['data_op']) ? '' : $_POST['data_op'];
            $check_sellername = empty($_POST['check_sellername']) ? 0 : intval($_POST['check_sellername']);
            $shop_style = isset($_POST['shop_style']) ? intval($_POST['shop_style']) : '';
            $domain_name = empty($_POST['domain_name']) ? '' : $_POST['domain_name'];
            $js_appkey = empty($_POST['js_appkey']) ? '' : $_POST['js_appkey']; //扫码appkey
            $js_appsecret = empty($_POST['js_appsecret']) ? '' : $_POST['js_appsecret']; //扫码appsecret
            $print_type = empty($_POST['print_type']) ? 0 : intval($_POST['print_type']); //打印方式
            $kdniao_printer = empty($_POST['kdniao_printer']) ? '' : $_POST['kdniao_printer']; //打印机

            $region_info = Region::select('region_id', 'region_name', 'parent_id')->where('region_id', $shop_city)->first();
            $region_info = $region_info ? $region_info->toArray() : [];

            if ($region_info && $shop_province != $region_info['parent_id']) {
                $shop_city = 0;
                $shop_district = 0;
            }

            //判断域名是否存在  by kong
            if (!empty($domain_name)) {
                $res = SellerDomain::where('domain_name', $domain_name)->where('ru_id', '<>', $adminru['ru_id'])->count();
                if ($res > 0) {
                    $lnk[] = ['text' => $GLOBALS['_LANG']['back_home'], 'href' => 'index.php?act=main'];
                    return sys_msg($GLOBALS['_LANG']['domain_existed'], 0, $lnk);
                }
            }

            $seller_domain = [
                'ru_id' => $adminru['ru_id'],
                'domain_name' => $domain_name,
            ];

            //平台同步设置手机，邮箱 by wu start  by kong 改
            if ($adminru['ru_id'] == 0) {
                $update_arr = [
                    //'sms_shop_mobile' => $mobile, //手机
                    'service_email' => $seller_email, //邮箱
                    'qq' => $kf_qq, //QQ
                    'ww' => $kf_ww, //旺旺
                    'shop_title' => $shop_title, //商店标题
                    'shop_keywords' => $shop_keyword, //商店关键字
                    'shop_country' => $shop_country, //国家
                    'shop_province' => $shop_province, //省份
                    'shop_city' => $shop_city, //城市
                    'shop_address' => $shop_address, //地址
                    'service_phone' => $kf_tel, //客服电话
                    'shop_notice' => $notice //店铺公告
                ];
                foreach ($update_arr as $key => $val) {
                    $data = ['value' => $val];
                    ShopConfig::where('code', $key)->update($data);
                }
            }

            clear_all_files('', 'admin');
            //平台同步设置手机，邮箱 by wu end

            $shop_info = [
                'ru_id' => $adminru['ru_id'],
                'shop_name' => $shop_name,
                'shop_title' => $shop_title,
                'shop_keyword' => $shop_keyword,
                'country' => $shop_country,
                'province' => $shop_province,
                'city' => $shop_city,
                'district' => $shop_district,
                'shipping_id' => $shipping_id,
                'shop_address' => $shop_address,
                'mobile' => $mobile,
                'seller_email' => $seller_email,
                'kf_qq' => $kf_qq,
                'kf_ww' => $kf_ww,
                'kf_appkey' => $kf_appkey, // bylu
                'kf_secretkey' => $kf_secretkey, // bylu
                'kf_touid' => $kf_touid, // bylu
                'kf_logo' => $kf_logo, // bylu
                'kf_welcomeMsg' => $kf_welcomeMsg, // bylu
                'kf_im_switch' => $kf_im_switch, // IM在线客服开关 bylu
                'meiqia' => $meiqia,
                'kf_type' => $kf_type,
                'kf_tel' => $kf_tel,
                'notice' => $notice,
                'street_desc' => $street_desc,
                'shop_style' => $shop_style,
                'check_sellername' => $check_sellername,
                'js_appkey' => $js_appkey, //扫码appkey
                'js_appsecret' => $js_appsecret, //扫码appsecret
                'print_type' => $print_type,
                'kdniao_printer' => $kdniao_printer
            ];

            $res = SellerShopinfo::select('shop_logo', 'logo_thumb', 'street_thumb', 'brand_thumb')
                ->where('ru_id', $adminru['ru_id']);
            $res = $res->with(['getSellerQrcode' => function ($query) {
                $query->select('ru_id', 'qrcode_thumb');
            }]);
            $store = $this->baseRepository->getToArrayFirst($res);
            $store['qrcode_thumb'] = '';
            if (isset($store['get_seller_qrcode']) && !empty($store['get_seller_qrcode'])) {
                $store['qrcode_thumb'] = $store['get_seller_qrcode']['qrcode_thumb'];
            }

            $oss_img = [];

            /* 允许上传的文件类型 */
            $allow_file_types = '|GIF|JPG|PNG|BMP|';

            if (isset($_FILES['shop_logo']) && $_FILES['shop_logo']) {
                $file = $_FILES['shop_logo'];
                /* 判断用户是否选择了文件 */
                if ((isset($file['error']) && $file['error'] == 0) || (!isset($file['error']) && $file['tmp_name'] != 'none')) {
                    /* 检查上传的文件类型是否合法 */
                    if (!check_file_type($file['tmp_name'], $file['name'], $allow_file_types)) {
                        return sys_msg(sprintf($GLOBALS['_LANG']['msg_invalid_file'], $file['name']));
                    } else {
                        if ($file['name']) {
                            $ext = explode('.', $file['name']);
                            $ext = array_pop($ext);
                        } else {
                            $ext = "";
                        }

                        $file_name = storage_public(IMAGE_DIR . '/seller_imgs/seller_logo/seller_logo' . $adminru['ru_id'] . '.' . $ext);

                        /* 判断是否上传成功 */
                        if (move_upload_file($file['tmp_name'], $file_name)) {
                            $shop_info['shop_logo'] = $file_name ? str_replace(storage_public(), '', $file_name) : '';

                            $oss_img['shop_logo'] = $shop_info['shop_logo'];
                        } else {
                            return sys_msg(sprintf($GLOBALS['_LANG']['msg_upload_failed'], $file['name'], IMAGE_DIR . '/seller_imgs/seller_' . $adminru['ru_id']));
                        }
                    }
                }
            }

            /**
             * 创建目录
             */
            $logo_thumb_path = storage_public(IMAGE_DIR . '/seller_imgs/seller_logo/logo_thumb/');
            if (!file_exists($logo_thumb_path)) {
                make_dir($logo_thumb_path);
            }

            if (isset($_FILES['logo_thumb']) && $_FILES['logo_thumb']) {
                $file = $_FILES['logo_thumb'];
                /* 判断用户是否选择了文件 */
                if ((isset($file['error']) && $file['error'] == 0) || (!isset($file['error']) && $file['tmp_name'] != 'none')) {
                    /* 检查上传的文件类型是否合法 */
                    if (!check_file_type($file['tmp_name'], $file['name'], $allow_file_types)) {
                        return sys_msg(sprintf($GLOBALS['_LANG']['msg_invalid_file'], $file['name']));
                    } else {
                        if ($file['name']) {
                            $ext = explode('.', $file['name']);
                            $ext = array_pop($ext);
                        } else {
                            $ext = "";
                        }

                        $file_name = storage_public(IMAGE_DIR . '/seller_imgs/seller_logo/logo_thumb/logo_thumb' . $adminru['ru_id'] . '.' . $ext);

                        /* 判断是否上传成功 */
                        if (move_upload_file($file['tmp_name'], $file_name)) {
                            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

                            $logo_thumb = $image->make_thumb($file_name, 120, 120, storage_public(IMAGE_DIR . "/seller_imgs/seller_logo/logo_thumb/"));

                            if ($logo_thumb) {
                                $logo_thumb = str_replace(storage_public(), '', $logo_thumb);
                                $shop_info['logo_thumb'] = $logo_thumb;

                                dsc_unlink($file_name);

                                $oss_img['logo_thumb'] = $logo_thumb;
                            }
                        } else {
                            return sys_msg(sprintf($GLOBALS['_LANG']['msg_upload_failed'], $file['name'], IMAGE_DIR . '/seller_imgs/logo_thumb_' . $adminru['ru_id']));
                        }
                    }
                }
            }

            if (isset($_FILES['street_thumb']) && $_FILES['street_thumb']) {
                $street_thumb = $image->upload_image($_FILES['street_thumb'], 'store_street/street_thumb');  //图片存放地址 -- data/septs_image
            }

            if (isset($_FILES['brand_thumb']) && $_FILES['brand_thumb']) {
                $brand_thumb = $image->upload_image($_FILES['brand_thumb'], 'store_street/brand_thumb');  //图片存放地址 -- data/septs_image
            }

            $street_thumb = isset($street_thumb) && $street_thumb ? str_replace(storage_public(), '', $street_thumb) : '';
            $brand_thumb = isset($brand_thumb) && $brand_thumb ? str_replace(storage_public(), '', $brand_thumb) : '';
            $oss_img['street_thumb'] = $street_thumb;
            $oss_img['brand_thumb'] = $brand_thumb;

            if ($street_thumb) {
                $shop_info['street_thumb'] = $street_thumb;
            }

            if ($brand_thumb) {
                $shop_info['brand_thumb'] = $brand_thumb;
            }

            //by kong
            $domain_id = SellerDomain::where('ru_id', $adminru['ru_id'])->value('id');
            $domain_id = $domain_id ? $domain_id : 0;
            /* 二级域名绑定  by kong  satrt */
            if ($domain_id > 0) {
                SellerDomain::where('ru_id', $adminru['ru_id'])->update($seller_domain);
            } else {
                SellerDomain::insert($seller_domain);
            }
            /* 二级域名绑定  by kong  end */

            //二维码中间logo by wu start
            if (isset($_FILES['qrcode_thumb']) && $_FILES['qrcode_thumb']) {
                $file = $_FILES['qrcode_thumb'];
                /* 判断用户是否选择了文件 */
                if ((isset($file['error']) && $file['error'] == 0) || (!isset($file['error']) && $file['tmp_name'] != 'none')) {
                    /* 检查上传的文件类型是否合法 */
                    if (!check_file_type($file['tmp_name'], $file['name'], $allow_file_types)) {
                        return sys_msg(sprintf($GLOBALS['_LANG']['msg_invalid_file'], $file['name']));
                    } else {
                        $ext = array_pop(explode('.', $file['name']));
                        $file_name = storage_public(IMAGE_DIR . '/seller_imgs/seller_qrcode/qrcode_thumb/qrcode_thumb' . $adminru['ru_id'] . '.' . $ext);
                        /* 判断是否上传成功 */
                        if (move_upload_file($file['tmp_name'], $file_name)) {
                            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

                            $qrcode_thumb = $image->make_thumb($file_name, 120, 120, storage_public(IMAGE_DIR . "/seller_imgs/seller_qrcode/qrcode_thumb/"));

                            if (!empty($qrcode_thumb)) {
                                $qrcode_thumb = str_replace(storage_public(), '', $qrcode_thumb);

                                $oss_img['qrcode_thumb'] = $qrcode_thumb;

                                if (isset($store['qrcode_thumb']) && $store['qrcode_thumb']) {
                                    $del_qrcode_thumb = str_replace(['../'], '', $store['qrcode_thumb']);
                                    dsc_unlink(storage_public($del_qrcode_thumb));
                                }
                            }

                            /* 保存 */
                            $qrcode_count = SellerQrcode::where('ru_id', $adminru['ru_id'])->count();

                            if ($qrcode_count > 0) {
                                if (!empty($qrcode_thumb)) {
                                    SellerQrcode::where('ru_id', $adminru['ru_id'])
                                        ->update([
                                            'qrcode_thumb' => $qrcode_thumb
                                        ]);
                                }
                            } else {

                                SellerQrcode::insert([
                                    'ru_id' => $adminru['ru_id'],
                                    'qrcode_thumb' => $qrcode_thumb
                                ]);
                            }
                        } else {
                            return sys_msg(sprintf($GLOBALS['_LANG']['msg_upload_failed'], $file['name'], IMAGE_DIR . '/seller_imgs/qrcode_thumb_' . $adminru['ru_id']));
                        }
                    }
                }
            }
            //二维码中间logo by wu end

            $this->dscRepository->getOssAddFile($oss_img);

            if ($data_op == 'add') {
                if (!$store) {
                    SellerShopinfo::insert($shop_info);
                }

                $lnk[] = ['text' => $GLOBALS['_LANG']['go_back_step'], 'href' => 'index.php?act=merchants_first'];
                return sys_msg($GLOBALS['_LANG']['add_store_info_success'], 0, $lnk);
            } else {
                $res = SellerShopinfo::where('ru_id', $adminru['ru_id']);
                $seller_shop_info = $this->baseRepository->getToArrayFirst($res);

                if ($adminru['ru_id'] > 0 && $seller_shop_info['check_sellername'] != $check_sellername) {
                    $shop_info['shopname_audit'] = 0;
                }

                $oss_del = [];

                if (isset($shop_info['logo_thumb']) && !empty($shop_info['logo_thumb'])) {
                    $oss_del[] = $store['logo_thumb'];
                    dsc_unlink(storage_public($store['logo_thumb']));
                }

                if (!empty($street_thumb)) {
                    $oss_street_thumb = $store['street_thumb'];
                    $oss_del[] = $oss_street_thumb;

                    $shop_info['street_thumb'] = $street_thumb;
                    dsc_unlink(storage_public($oss_street_thumb));
                }

                if (!empty($brand_thumb)) {
                    $oss_brand_thumb = $store['brand_thumb'];
                    $oss_del[] = $oss_brand_thumb;

                    $shop_info['brand_thumb'] = $brand_thumb;
                    dsc_unlink(storage_public($oss_brand_thumb));
                }

                $this->dscRepository->getOssDelFile($oss_del);

                SellerShopinfo::where('ru_id', $adminru['ru_id'])->update($shop_info);
                $lnk[] = ['text' => $GLOBALS['_LANG']['go_back_step'], 'href' => 'index.php?act=merchants_first'];
                return sys_msg($GLOBALS['_LANG']['update_store_info_success'], 0, $lnk);
            }
        }

        /* ------------------------------------------------------ */
        //-- 关于 DSC
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'about_us') {
            return $this->smarty->display('about_us.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 拖动的帧
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'drag') {
            return $this->smarty->display('drag.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 检查订单
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'check_order') {

            $firstSecToday = local_mktime(0, 0, 0, local_date("m"), local_date("d"), local_date("Y"));
            $lastSecToday = local_mktime(0, 0, 0, local_date("m"), local_date("d") + 1, local_date("Y")) - 1;
            if (session()->has('last_check') && empty(session('last_check'))) {
                session([
                    'last_check' => gmtime()
                ]);
                return make_json_result('', '', ['new_orders' => 0, 'new_paid' => 0]);
            }

            //订单提醒 start
            if (admin_priv('order_view', '', false)) {
                /* 新订单 */
                //主订单下有子订单时，则主订单不显示
                $arr['new_orders'] = OrderInfo::where('add_time', '>=', $firstSecToday)
                    ->where('add_time', '<=', $lastSecToday)
                    ->where('main_count', 0)
                    ->where('ru_id', $adminru['ru_id'])
                    ->where('shipping_status', SS_UNSHIPPED)
                    ->count();

                /* 待发货订单 */
                $where_og = " AND oi.main_count = 0 AND oi.ru_id = '" . $adminru['ru_id'] . "' ";
                $arr['await_ship'] = $this->db->getOne('SELECT count(*) FROM ' . $GLOBALS['dsc']->table('order_info') . " AS oi " .
                    " WHERE 1 " . $this->orderService->orderQuerySql('await_ship', 'oi.') . " AND (SELECT ore.ret_id FROM " . $GLOBALS['dsc']->table('order_return') . " AS ore WHERE ore.order_id = oi.order_id LIMIT 1) IS NULL " . $where_og);
            }

            if (admin_priv('order_back_apply', '', false)) {
                /* 待处理退换货订单 */
                $order_status = $this->baseRepository->getExplode('2,3,4,7,8');
                $ru_id = $adminru['ru_id'];
                $res = OrderReturn::whereRaw(1);
                $res = $res->whereHas('orderInfo', function ($query) use ($order_status, $ru_id) {
                    $query->whereNotIn('order_status', $order_status)->where('ru_id', $ru_id);
                });
                $arr['no_change'] = $res->count();
            }

            if (admin_priv('complaint', '', false)) {
                //待处理投诉订单（未完成仲裁）
                $arr['complaint'] = Complaint::where('complaint_state', '<>', 4)->where('ru_id', $adminru['ru_id'])->count();
            }

            if (admin_priv('booking', '', false)) {
                //待处理缺货商品
                $user_id = $adminru['ru_id'];
                $res = BookingGoods::where('is_dispose', 0);
                $res = $res->whereHas('getGoods', function ($query) use ($user_id) {
                    $query->where('user_id', $user_id);
                });
                $arr['booking_goods'] = $res->count();
            }
            //订单提醒 end

            //商品提醒 start
            if (admin_priv('goods_report', '', false)) {
                //未处理商品投诉
                $arr['goods_report'] = GoodsReport::where('report_state', 0)->count();
            }

            if (admin_priv('sale_notice', '', false)) {
                //未发送商品降价通知
                $arr['sale_notice'] = SaleNotice::where('status', 2)->count();
            }

            if (admin_priv('goods_manage', '', false)) {
                //未审核商家商品
                $arr['no_check_goods'] = Goods::where('review_status', 1)
                    ->where('user_id', '>', 0)
                    ->where('is_delete', 0)
                    ->count();
            }

            if (admin_priv('merchants_brand', '', false)) {
                //未审核商家品牌
                $arr['no_check_brand'] = MerchantsShopBrand::where('audit_status', 0)->count();
            }

            if (admin_priv('goods_manage', '', false)) {
                //自营商品库存预警值
                $arr['self_warn_number'] = Goods::where('goods_number', '<=', 'warn_number')
                    ->where('user_id', 0)
                    ->where('is_delete', 0)
                    ->where('is_real', 1)
                    ->count();
            }

            if (admin_priv('goods_manage', '', false)) {
                //商家商品库存预警值
                $arr['merchants_warn_number'] = Goods::where('goods_number', '<=', 'warn_number')
                    ->where('user_id', '>', 0)
                    ->where('is_delete', 0)
                    ->where('is_real', 1)
                    ->count();
            }
            //商品提醒 end

            //商家提醒 start
            if (admin_priv('users_merchants', '', false)) {
                /* 未审核商家 */
                $arr['shop_account'] = MerchantsShopInformation::where('merchants_audit', 0)->count();

                /* 未审核店铺信息 */
                $arr['shopinfo_account'] = SellerShopinfo::where('review_status', 1)
                    ->where('ru_id', '>', 0)
                    ->count();
            }

            if (admin_priv('users_real_manage', '', false)) {
                /* 未审核商家实名认证 */
                $arr['seller_account'] = UsersReal::where('review_status', 0)
                    ->where('user_type', 1)
                    ->count();
            }

            if (admin_priv('seller_account', '', false)) {
                /* 待审核商家提现 */
                $log_type = $this->baseRepository->getExplode('1,4,5');
                $arr['wait_cash'] = SellerAccountLog::where('is_paid', 0)
                    ->whereIn('log_type', $log_type)
                    ->count();

                /* 待审核商家结算申请 */
                $arr['wait_balance'] = SellerCommissionBill::where('bill_apply', 1)
                    ->where('chargeoff_status', 1)
                    ->count();

                /* 待审核商家充值申请 */
                $arr['wait_recharge'] = SellerAccountLog::whereIn('log_type', [3])
                    ->where('is_paid', 0)
                    ->count();
            }

            if (admin_priv('seller_apply', '', false)) {
                //待审核店铺等级
                $arr['seller_apply'] = SellerApplyInfo::where('apply_status', 0)->count();
            }
            //商家提醒 end

            //广告位提醒 start
            if (admin_priv('ad_manage', '', false)) {
                /* 广告位到期 */
                $now_time = gmtime();
                $arr['advance_date'] = Ad::whereRaw('end_time - 3600 * 24 * 3 <' . $now_time)
                    ->where('end_time', '>', $now_time)
                    ->count();
            }
            //广告位提醒 end

            //会员提醒 start
            if (admin_priv('users_real_manage', '', false)) {
                /* 会员实名认证 */
                $arr['user_account'] = UsersReal::where('review_status', 0)
                    ->where('user_type', 0)
                    ->count();
            }
            if (admin_priv('surplus_manage', '', false)) {
                //未处理会员充值申请
                $arr['user_recharge'] = UserAccount::where('process_type', 0)
                    ->where('is_paid', 0)
                    ->count();

                //未处理会员提现申请
                $arr['user_withdraw'] = UserAccount::where('process_type', 1)
                    ->where('is_paid', 0)
                    ->count();
            }

            if (admin_priv('user_vat_manage', '', false)) {
                //未处理会员增票资质审核
                $arr['user_vat'] = UsersVatInvoicesInfo::where('audit_status', 0)->count();
            }

            if (admin_priv('discuss_circle', '', false)) {
                //未处理网友讨论圈审核
                $res = DiscussCircle::where('review_status', 1);
                $arr['user_discuss'] = $res->whereHas('getGoods')->count();
            }
            //会员提醒 end

            //促销活动提醒 start
            if (admin_priv('snatch_manage', '', false)) {
                //未审核夺宝奇兵
                $arr['snatch'] = GoodsActivity::where('act_type', GAT_SNATCH)
                    ->where('review_status', 1)
                    ->count();
            }

            if (admin_priv('bonus_manage', '', false)) {
                //未审核红包类型
                $arr['bonus_type'] = BonusType::where('review_status', 1)->count();
            }

            if (admin_priv('group_by', '', false)) {
                //未审核团购活动
                $arr['group_by'] = GoodsActivity::where('act_type', GAT_GROUP_BUY)
                    ->where('review_status', 1)
                    ->count();
            }
            if (admin_priv('topic_manage', '', false)) {
                //未审核专题
                $arr['topic'] = Topic::where('review_status', 1)->count();
            }

            if (admin_priv('auction', '', false)) {
                //未审核拍卖活动
                $arr['auction'] = GoodsActivity::where('act_type', GAT_AUCTION)
                    ->where('review_status', 1)
                    ->count();
            }

            if (admin_priv('favourable', '', false)) {
                //未审核优惠活动
                $arr['favourable'] = FavourableActivity::where('review_status', 1)->count();
            }

            if (admin_priv('presale', '', false)) {
                //未审核预售活动
                $arr['presale'] = PresaleActivity::where('review_status', 1)->count();
            }
            if (admin_priv('package_manage', '', false)) {
                //未审核超值礼包
                $arr['package_goods'] = GoodsActivity::where('act_type', GAT_PACKAGE)->where('review_status', 1)->count();
            }

            if (admin_priv('exchange_goods', '', false)) {
                //未审核积分商城商品
                $arr['exchange_goods'] = ExchangeGoods::where('review_status', 1)->count();
            }

            if (admin_priv('coupons_manage', '', false)) {
                //未审核优惠券
                $arr['coupons'] = Coupons::where('review_status', 1)->count();
            }

            if (admin_priv('gift_gard_manage', '', false)) {
                //未审核礼品卡
                $arr['gift_gard'] = GiftGardType::where('review_status', 1)->count();
            }

            if (admin_priv('whole_sale', '', false)) {
                //未审核礼品卡
                $arr['wholesale'] = Wholesale::where('review_status', 1)->count();
            }

            if (admin_priv('seckill_manage', '', false)) {
                //未审核秒杀
                $arr['seckill'] = Seckill::where('review_status', 1)->count();
            }
            //促销活动提醒 end

            //修改收银台线下现金结算的订单为已结算（收银台那边的已经处理，只针对老订单）
            if ($GLOBALS['_CFG']['cashier_Settlement'] == 1) {
                //获取现金支付的id
                $pay_id = Payment::where('pay_code', 'pay_cash')->value('pay_id');
                $pay_id = $pay_id ? $pay_id : 0;
                if ($pay_id > 0) {
                    //修改现金支付的结算状态为已结算
                    $data = ['is_settlement' => 1];
                    OrderInfo::where('pay_id', $pay_id)->update($data);
                }
                //修改配置为已处理，下次不执行这模块代码
                $data = ['value' => 0];
                ShopConfig::where('code', 'cashier_Settlement')->update($data);
            }

            //促销活动提醒 end

            if (file_exists(SUPPLIERS)) {
                //供应链提醒 start
                if (admin_priv('suppliers_list', '', false)) {
                    //未审核供应商
                    $arr['suppliers'] = Suppliers::where('review_status', '1')->count();
                }

                if (admin_priv('suppliers_goods_list', '', false)) {
                    //未审核供应商商品
                    $arr['suppliers_goods'] = Wholesale::where('review_status', '1')->count();
                }
            }

            //供应链提醒 end
            session([
                'last_check' => gmtime(),
                'firstSecToday' => $firstSecToday,
                'lastSecToday' => $lastSecToday
            ]);

            $pay_effective_time = isset($GLOBALS['_CFG']['pay_effective_time']) && $GLOBALS['_CFG']['pay_effective_time'] > 0 ? intval($GLOBALS['_CFG']['pay_effective_time']) : 0;//订单时效
            if ($pay_effective_time > 0) {
                checked_pay_Invalid_order($pay_effective_time);
            }
            $arr = $arr ?? [];
            if (isset($arr['new_orders']) && !(is_numeric($arr['new_orders']))) {
                return make_json_error($this->db->error());
            } else {
                return make_json_result('', '', $arr);
            }
        }

        /* ------------------------------------------------------ */
        //-- 检查商家账单是否生成
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'check_bill') {

            $seller_id = isset($_REQUEST['seller_id']) && !empty($_REQUEST['seller_id']) ? intval($_REQUEST['seller_id']) : 0;

            if ($seller_id > 0) {
                app(CommissionServer::class)->checkBill($seller_id);
            }

            return make_json_result('', '', []);
        }

        /* ------------------------------------------------------ */
        //-- Totolist操作
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'save_todolist') {
            $content = json_str_iconv($_POST["content"]);
            $data = ['todolist' => $content];
            AdminUser::where('user_id', $admin_id)->update($data);
        } elseif ($_REQUEST['act'] == 'get_todolist') {
            $content = AdminUser::where('user_id', $admin_id)->value('todolist');
            $content = $content ? $content : '';
            echo $content;
        } // 邮件群发处理
        elseif ($_REQUEST['act'] == 'send_mail') {
            if ($GLOBALS['_CFG']['send_mail_on'] == 'off') {
                return make_json_result('', $GLOBALS['_LANG']['send_mail_off'], 0);
            }

            $res = EmailSendlist::orderBy('pri', 'DESC')->orderBy('last_send', 'ASC');
            $row = $this->baseRepository->getToArrayFirst($res);

            //发送列表为空
            if (empty($row['id'])) {
                return make_json_result('', $GLOBALS['_LANG']['mailsend_null'], 0);
            }

            //发送列表不为空，邮件地址为空
            if (!empty($row['id']) && empty($row['email'])) {
                EmailSendlist::where('id', $row['id'])->delete();
                $count = EmailSendlist::count();
                return make_json_result('', $GLOBALS['_LANG']['mailsend_skip'], ['count' => $count, 'goon' => 1]);
            }

            //查询相关模板
            $res = MailTemplates::where('template_id', $row['template_id']);
            $rt = $this->baseRepository->getToArrayFirst($res);

            //如果是模板，则将已存入email_sendlist的内容作为邮件内容
            //否则即是杂质，将mail_templates调出的内容作为邮件内容
            if ($rt['type'] == 'template') {
                $rt['template_content'] = $row['email_content'];
            }

            if ($rt['template_id'] && $rt['template_content']) {
                if ($this->commonRepository->sendEmail('', $row['email'], $rt['template_subject'], $rt['template_content'], $rt['is_html'])) {
                    //发送成功
                    //从列表中删除
                    EmailSendlist::where('id', $row['id'])->delete();

                    //剩余列表数
                    $count = EmailSendlist::count();

                    if ($count > 0) {
                        $msg = sprintf($GLOBALS['_LANG']['mailsend_ok'], $row['email'], $count);
                    } else {
                        $msg = sprintf($GLOBALS['_LANG']['mailsend_finished'], $row['email']);
                    }
                    return make_json_result('', $msg, ['count' => $count]);
                } else {
                    //发送出错

                    if ($row['error'] < 3) {
                        $time = time();
                        EmailSendlist::increment('error');
                        $data = [
                            'pri' => '0',
                            'last_send' => $time
                        ];
                        EmailSendlist::where('id', $row['id'])->update($data);
                    } else {
                        //将出错超次的纪录删除
                        EmailSendlist::where('id', $row['id'])->delete();
                    }
                    $count = EmailSendlist::count();
                    return make_json_result('', sprintf($GLOBALS['_LANG']['mailsend_fail'], $row['email']), ['count' => $count]);
                }
            } else {
                //无效的邮件队列
                EmailSendlist::where('id', $row['id'])->delete();
                $count = EmailSendlist::count();
                return make_json_result('', sprintf($GLOBALS['_LANG']['mailsend_fail'], $row['email']), ['count' => $count]);
            }
        }

        /* ------------------------------------------------------ */
        //-- 云服务
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'cloud_services') {
            admin_priv('cloud_services');

            $http = $this->dsc->http();
            if (strpos($http, 'https://') === false) {
                $Loaction = "http://market.dscmall.cn/";
            } else {
                $Loaction = "https://market.dscmall.cn/";
            }

            return redirect($Loaction);
        }

        /* ------------------------------------------------------ */
        //--删除配置文件夹  by kong  20180425
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove_file') {
            $file = !empty($_REQUEST['file']) ? trim($_REQUEST['file']) : '';
            if (!empty($file)) {
                if ($this->deldir(storage_public($file)) == true) {
                    $Loaction = "index.php?act=main";
                    return dsc_header("Location: $Loaction\n");//返回首页
                } // 删除的文件夹
            }
        }

        /* ------------------------------------------------------ */
        //-- 管理员头像上传
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'upload_store_img') {
            $result = ["error" => 0, "message" => "", "content" => ""];

            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

            if ($_FILES['img']['name']) {
                $dir = 'store_user';

                $img_name = $image->upload_image($_FILES['img'], $dir);

                $this->dscRepository->getOssAddFile([$img_name]);

                if ($img_name) {
                    $result['error'] = 1;
                    $result['content'] = get_image_path($img_name);
                    //删除原图片

                    $store_user_img = AdminUser::where('user_id', $admin_id)->value('admin_user_img');
                    $store_user_img = $store_user_img ? $store_user_img : '';
                    dsc_unlink(storage_public($store_user_img));

                    //插入新图片
                    $data = ['admin_user_img' => $img_name];
                    AdminUser::where('user_id', $admin_id)->update($data);
                }
            }
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 处理添加快捷菜单(保存于cookie) bylu
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'auth_menu') {
            $type = isset($_POST['type']) ? $_POST['type'] : '';
            $auth_name = isset($_POST['auth_name']) ? $_POST['auth_name'] : '';
            $auth_href = isset($_POST['auth_href']) ? $_POST['auth_href'] : '';
            $auth_menu = request()->hasCookie('auth_menu') && !empty(request()->cookie('auth_menu')) ? request()->cookie('auth_menu') : '';

            if ($type == 'add') {
                $auth_menu .= $auth_name . '|' . $auth_href . ',';
            } else {
                $auth_menu = str_replace($auth_name . '|' . $auth_href . ',', '', $auth_menu);
            }

            cookie()->queue('auth_menu', $auth_menu, 60 * 24 * 365);
        }

        /* ------------------------------------------------------ */
        //-- 业务流程
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'operation_flow') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['02_operation_flow']);

            return $this->smarty->display('operation_flow.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 新手向导
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'novice_guide') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['03_novice_guide']);

            return $this->smarty->display('novice_guide.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 登录状态
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'login_status') {
            $status = app(CommonManageService::class)->loginStatus();
            return response()->json(['status' => $status]);
        }
    }

    /**
     * 删除文件夹
     *
     * @param $dir
     * @return bool
     */
    private function deldir($dir)
    {
        $dh = @opendir($dir);
        while ($file = @readdir($dh)) {
            if ($file != "." && $file != "..") {
                $fullpath = $dir . "/" . $file;
                if (!is_dir($fullpath)) {
                    unlink($fullpath);
                } else {
                    $this->deldir($fullpath);
                }
            }
        }
        @closedir($dh);
        if (@rmdir($dir)) {
            return true;
        } else {
            return false;
        }
    }
}
