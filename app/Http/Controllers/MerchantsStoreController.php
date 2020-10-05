<?php

namespace App\Http\Controllers;

use App\Libraries\QRcode;
use App\Models\CollectStore;
use App\Models\CouponsUser;
use App\Models\MerchantsCategory;
use App\Models\MerchantsShopInformation;
use App\Models\SellerShopinfo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Activity\CouponsService;
use App\Services\Ads\AdsService;
use App\Services\Article\ArticleCommonService;
use App\Services\Brand\BrandService;
use App\Services\Category\CategoryAttributeService;
use App\Services\Category\CategoryBrandService;
use App\Services\Category\CategoryGoodsService;
use App\Services\Category\CategoryService;
use App\Services\Comment\CommentService;
use App\Services\Common\AreaService;
use App\Services\Common\CommonService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Store\StoreService;

/**
 * 购物流程
 */
class MerchantsStoreController extends InitController
{
    protected $areaService;
    protected $brandService;
    protected $categoryService;
    protected $storeService;
    protected $dscRepository;
    protected $couponsService;
    protected $baseRepository;
    protected $categoryGoodsService;
    protected $categoryBrandService;
    protected $categoryAttributeService;
    protected $merchantCommonService;
    protected $commentService;
    protected $adsService;
    protected $articleCommonService;
    protected $commonService;
    protected $sessionRepository;

    public function __construct(
        AreaService $areaService,
        BrandService $brandService,
        CategoryService $categoryService,
        CouponsService $couponsService,
        StoreService $storeService,
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        CategoryGoodsService $categoryGoodsService,
        CategoryBrandService $categoryBrandService,
        CategoryAttributeService $categoryAttributeService,
        MerchantCommonService $merchantCommonService,
        CommentService $commentService,
        AdsService $adsService,
        ArticleCommonService $articleCommonService,
        CommonService $commonService,
        SessionRepository $sessionRepository
    )
    {
        $this->areaService = $areaService;
        $this->brandService = $brandService;
        $this->categoryService = $categoryService;
        $this->couponsService = $couponsService;
        $this->storeService = $storeService;
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
        $this->categoryGoodsService = $categoryGoodsService;
        $this->categoryBrandService = $categoryBrandService;
        $this->categoryAttributeService = $categoryAttributeService;
        $this->merchantCommonService = $merchantCommonService;
        $this->commentService = $commentService;
        $this->adsService = $adsService;
        $this->articleCommonService = $articleCommonService;
        $this->commonService = $commonService;
        $this->sessionRepository = $sessionRepository;
    }

    public function index($shop = '')
    {
        define('IN_ECS', true);

        load_helper('visual');

        /* 过滤 XSS 攻击和SQL注入 */
        get_request_filter();

        /**
         * Start
         *
         * @param $warehouse_id 仓库ID
         * @param $area_id 省份ID
         * @param $area_city 城市ID
         */
        $warehouse_id = $this->warehouseId();
        $area_id = $this->areaId();
        $area_city = $this->areaCity();
        /* End */

        $brand_ids = (int)request()->input('brand', 0);
        $act = addslashes(request()->input('act', ''));

        $session_id = $this->sessionRepository->realCartMacIp();
        $user_id = session('user_id', 0);

        $seller_domain = $this->merchantCommonService->getSellerDomain($shop);

        $store_param = '';
        if ($seller_domain) {
            $merchant_id = $seller_domain['ru_id'];
            $this->smarty->assign('is_jsonp', 1);
            $this->smarty->assign('shop_url', $this->dscRepository->sellerUrl($merchant_id));
            $store_param = config('app.store_param');
        } else {
            $merchant_id = (int)request()->input('merchant_id', 0);
            $this->smarty->assign('shop_url', 'merchants_store.php');
        }

        $this->smarty->assign('store_param', $store_param);

        /* 跳转H5 start */
        $Loaction = dsc_url('/#/shopHome/' . $merchant_id);
        $uachar = $this->dscRepository->getReturnMobile($Loaction);

        if ($uachar) {
            return $uachar;
        }
        /* 跳转H5 end */

        /*记录访问者IP*/
        $realip = $this->dscRepository->dscIp();
        modifyipcount($realip, $merchant_id);

        /* 初始化分页信息 */
        $page = (int)request()->input('page', 1);
        $size = isset($GLOBALS['_CFG']['page_size']) && intval($GLOBALS['_CFG']['page_size']) > 0 ? intval($GLOBALS['_CFG']['page_size']) : 10;
        $price_max = (int)request()->input('price_max', 0);
        $price_min = (int)request()->input('price_min', 0);
        $filter_attr_str = htmlspecialchars(trim(request()->input('filter_attr', 0)));
        $filter_attr_str = trim(urldecode($filter_attr_str));
        $filter_attr_str = preg_match('/^[\d\.]+$/', $filter_attr_str) ? $filter_attr_str : '';
        $filter_attr = empty($filter_attr_str) ? '' : explode('.', $filter_attr_str);

        /*模板名称*/
        $tem = addslashes(request()->input('tem', ''));

        //正则去掉js代码
        $preg = "/<script[\s\S]*?<\/script>/i";

        /* 排序、显示方式以及类型 */
        $default_display_type = $GLOBALS['_CFG']['show_order_type'] == '0' ? 'list' : ($GLOBALS['_CFG']['show_order_type'] == '1' ? 'grid' : 'text');
        $default_sort_order_method = $GLOBALS['_CFG']['sort_order_method'] == '0' ? 'DESC' : 'ASC';
        $default_sort_order_type = $GLOBALS['_CFG']['sort_order_type'] == '0' ? 'goods_id' : ($GLOBALS['_CFG']['sort_order_type'] == '1' ? 'shop_price' : 'last_update');

        $sort = request()->input('sort', '');
        $sort = in_array(trim(strtolower($sort)), ['goods_id', 'shop_price', 'last_update', 'sales_volume', 'comments_number']) ? trim($sort) : $default_sort_order_type;
        $order = request()->input('order', '');
        $order = in_array(trim(strtoupper($order)), ['ASC', 'DESC']) ? trim($order) : $default_sort_order_method;

        $display = (int)strtolower(request()->input('display', ''));
        $display = !empty($display) ? preg_replace($preg, "", stripslashes($display)) : '';

        $keywords = addslashes(trim(request()->input('keywords', '')));
        $keyword = htmlspecialchars(trim(request()->input('keyword', $keywords)));

        $temp_code = addslashes(trim(request()->input('temp_code', '')));

        $cat_id = 0;
        if (request()->exists('id')) {
            $cat_id = (int)request()->input('id', 0);
        } elseif (request()->exists('cat_id')) {
            $cat_id = (int)request()->input('cat_id', 0);
        }

        //商家不存则跳转回首页
        $mershop_info = MerchantsShopInformation::select('shop_id', 'shop_close')->where('user_id', $merchant_id);
        $mershop_info = $this->baseRepository->getToArrayFirst($mershop_info);

        $shop_id = !empty($mershop_info) ? $mershop_info['shop_id'] : 0;

        $preview = (int)request()->input('preview', 0);

        if (($merchant_id == 0 || $shop_id < 1) && $temp_code == '') {
            return dsc_header("Location: " . config('app.url') . "\n");
        }

        /*
        * 判断当前店铺是否有领券关注店铺
        */
        if ($user_id > 0) {
            $cou_data = $this->couponsService->getCouponsList([1, 2, 3, 4, 5], '', 'cou_id', 'desc', 0, 10, []);

            if (!empty($cou_data) && isset($cou_data)) {
                foreach ($cou_data as $key => $value) {
                    $cou_info['cou_id'] = $value['cou_id'];
                    $cou_info['ru_id'] = $value['ru_id'];
                }
                if ($cou_info['ru_id'] == $merchant_id) {
                    //当前用户之前是否有过该优惠券
                    $rec_id = CouponsUser::where('user_id', $user_id)->where(
                        'cou_id',
                        $cou_info['cou_id']
                    )->value('uc_id');
                    if (!empty($rec_id) && isset($rec_id)) {
                        $cou_info = [];
                    } else {
                        $cou_info = $cou_data;
                        //时间格式化
                        $cou_info[0]['cou_start_time'] = local_date('Y-m-d', $cou_info[0]['cou_start_time']);
                        $cou_info[0]['cou_end_time'] = local_date('Y-m-d', $cou_info[0]['cou_end_time']);
                        $cou_info[0]['format_cou_money'] = $this->dscRepository->getPriceFormat($cou_info[0]['cou_money']);
                    }
                }
            } else {
                $cou_info = [];
            }

            $this->smarty->assign('coupon_store_info', $cou_info);    // 关注店铺优惠券
        }

        //判断是否已经关注
        $rec_id = CollectStore::where('user_id', $user_id)->where('ru_id', $merchant_id)->value('rec_id');

        if (!empty($act) && $act == 'in_stock') {
            $res = ['err_msg' => '', 'result' => '', 'qty' => 1];

            $area = $this->areaService->areaCookie();

            $goods_id = (int)request()->input('id', 0);
            $province = (int)request()->input('province', $area['province'] ?? 0);
            $city = (int)request()->input('city', $area['city'] ?? 0);
            $district = (int)request()->input('district', $area['district'] ?? 0);
            $d_null = (int)request()->input('d_null', 0);

            $user_address = get_user_address_region($user_id);
            $user_address = explode(",", $user_address['region_address']);

            $street_info = Region::select('region_id')->where('parent_id', $district)->get();
            $street_info = $street_info ? $street_info->toArray() : [];
            $street_info = $street_info ? collect($street_info)->flatten()->all() : [];

            $street_list = 0;
            $street_id = 0;

            if ($street_info) {
                $street_id = $street_info[0];
                $street_list = implode(",", $street_info);
            }

            $res['d_null'] = $d_null;

            if ($d_null == 0) {
                if (in_array($district, $user_address)) {
                    $res['isRegion'] = 1;
                } else {
                    $res['message'] = $GLOBALS['_LANG']['region_message'];
                    $res['isRegion'] = 88; //原为0
                }
            } else {
                $district = '';
            }

            /* 删除缓存 */
            $this->areaService->getCacheNameForget('area_cookie');
            $this->areaService->getCacheNameForget('area_info');
            $this->areaService->getCacheNameForget('warehouse_id');

            $area_cache_name = $this->areaService->getCacheName('area_cookie');

            $area_cookie_cache = [
                'province' => $province,
                'city_id' => $city,
                'district' => $district,
                'street' => $street_id,
                'street_area' => $street_list
            ];

            cache()->forever($area_cache_name, $area_cookie_cache);

            $res['goods_id'] = $goods_id;

            return response()->json($res);
        } elseif (!empty($act) && $act == 'ajax_collect_store') { //Ajax取消/关注
            //修改 by tong
            $res = ['err_msg' => '', 'result' => '', 'error' => 0];

            $type = (int)request()->input('type', 0);
            $merchant_id = (int)request()->input('merchant_id', 0);
            $execute = (int)request()->input('execute', 0);

            if ((isset($user_id) && $user_id < 1) || !isset($user_id)) {
                $res['error'] = 2;
            } else {
                if ($execute == 1) {
                    // 弹出提示
                    if ($type == 0 || $type == 1) {
                        $res['error'] = 3;
                    } elseif ($type == 2) {
                        if ($rec_id < 1) {
                            $res['error'] = 3;
                        } else {
                            $res['error'] = 1;
                        }
                    }
                } else {
                    //取消关注
                    if ($type == 0 || $type == 1) {
                        if (!empty($merchant_id)) {
                            CollectStore::where('ru_id', $merchant_id)->delete();
                        }
                    }
                    //添加关注
                    if ($rec_id < 1) {
                        $other = [
                            'user_id' => $user_id,
                            'ru_id' => $merchant_id,
                            'add_time' => gmtime(),
                            'is_attention' => 1
                        ];
                        CollectStore::insert($other);
                    }
                }
            }

            $res['type'] = $type;
            $res['merchant_id'] = $merchant_id;

            return response()->json($res);
        } elseif ($act == 'merchants_licence') {
            assign_template();
            $shop_name = $this->merchantCommonService->getShopName($merchant_id, 1); //店铺名称
            $grade_info = get_seller_grade($merchant_id); //等级信息
            $store_info = $this->storeService->getMerchantsStoreInfo($merchant_id, 1);
            $position = assign_ur_here(0, $shop_name);
            $this->smarty->assign('page_title', $position['title']);    // 页面标题
            $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置
            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助
            $this->smarty->assign('store', $store_info); // 店铺背景
            $build_uri = [
                'urid' => $merchant_id,
                'append' => $shop_name
            ];

            $domain_url = $this->merchantCommonService->getSellerDomainUrl($merchant_id, $build_uri);
            $merchants_url = $domain_url['domain_name'];
            $this->smarty->assign('merchants_url', $merchants_url);  //网站域名

            if ($merchant_id > 0) {
                $merchants_goods_comment = $this->commentService->getMerchantsGoodsComment($merchant_id); //商家所有商品评分类型汇总
            }

            $this->smarty->assign('merch_cmt', $merchants_goods_comment);
            $this->smarty->assign('shop_name', $shop_name);
            $categories_pro = get_category_tree_leve_one();
            $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版

            //商家二维码 by wu start
            $basic_info = $this->storeService->getShopInfo($merchant_id, 3);
            $logo = $basic_info && $basic_info['logo_thumb'] ? str_replace('../', '', $basic_info['logo_thumb']) : '';

            if ($GLOBALS['_CFG']['open_oss'] == 1) {
                $logo = $logo ? $this->dscRepository->getImagePath($logo) : '';
            } else {
                $logo = $logo && (strpos($logo, 'http') === false) ? storage_public($logo) : $logo;
            }

            $basic_info['license_fileImg'] = isset($basic_info['license_fileImg']) && $basic_info['license_fileImg'] ? $this->dscRepository->getImagePath($basic_info['license_fileImg']) : '';
            //处理营业执照所在地
            if ($basic_info) {
                if ($basic_info['license_comp_adress']) {
                    $adress = explode(',', $basic_info['license_comp_adress']);
                    if (!empty($adress)) {
                        $license_comp_adress = '';
                        foreach ($adress as $v) {
                            $license_comp_adress .= get_table_date('region', "region_id='$v'", ['region_name'], 2);
                        }
                    }
                    $basic_info['license_comp_adress'] = $license_comp_adress;
                }
                //处理营业执照所在地
                if ($basic_info['company_located']) {
                    $adress = explode(',', $basic_info['company_located']);
                    if (!empty($adress)) {
                        $company_located = '';
                        foreach ($adress as $v) {
                            $company_located .= get_table_date('region', "region_id='$v'", ['region_name'], 2);
                        }
                    }
                    $company_located .= "&nbsp;&nbsp;" . $basic_info['company_adress'];
                    $basic_info['company_located'] = $company_located;
                }
                $basic_info['business_term'] = str_replace(',', '-', $basic_info['business_term']); //营业执照有限期
            }

            $url = url('/') . '/';
            $data = $url . "mobile/#/shopHome/" . $merchant_id;
            $errorCorrectionLevel = 'Q'; // 纠错级别：L、M、Q、H
            $matrixPointSize = 4; // 点的大小：1到10

            $image = IMAGE_DIR . "/seller_imgs/seller_qrcode/seller_qrcode_" . $merchant_id . ".png";
            $filename = storage_public($image);

            /* 生成目录 */
            if (!file_exists(storage_public(IMAGE_DIR . "/seller_imgs/seller_qrcode"))) {
                make_dir(storage_public(IMAGE_DIR . "/seller_imgs/seller_qrcode"));
            }

            if (!file_exists($filename)) {
                QRcode::png($data, $filename, $errorCorrectionLevel, $matrixPointSize);
                $QR = imagecreatefrompng($filename);

                $linkExists = $this->dscRepository->remoteLinkExists($logo);

                if ($linkExists) {
                    $logo = imagecreatefromstring(file_get_contents($logo));
                    $QR_width = imagesx($QR);
                    $QR_height = imagesy($QR);
                    $logo_width = imagesx($logo);
                    $logo_height = imagesy($logo);
                    // Scale logo to fit in the QR Code
                    $logo_qr_width = $QR_width / 5;
                    $scale = $logo_width / $logo_qr_width;
                    $logo_qr_height = $logo_height / $scale;
                    $from_width = ($QR_width - $logo_qr_width) / 2;
                    //echo $from_width;
                    imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
                }
                imagepng($QR, $filename);
                imagedestroy($QR);
            }

            $seller_qrcode_text = $basic_info && $basic_info['shop_name'] ? $basic_info['shop_name'] : '';

            $this->dscRepository->getOssAddFile([$image]);

            $this->smarty->assign('seller_qrcode_img', $this->dscRepository->getImagePath($image));
            $this->smarty->assign('seller_qrcode_text', $seller_qrcode_text);
            //商家二维码 by wu end
            $basic_info['shop_logo'] = $basic_info && $basic_info['shop_logo'] ? str_replace('../', '', $basic_info['shop_logo']) : '';
            $basic_info['shop_logo'] = $this->dscRepository->getImagePath($basic_info['shop_logo']);

            if ($GLOBALS['_CFG']['customer_service'] == 0) {
                $im_merchant_id = 0;
            } else {
                $im_merchant_id = $merchant_id;
            }

            /*  @author-bylu 判断当前商家是否允许"在线客服" start */
            $shop_information = $this->merchantCommonService->getShopName($im_merchant_id);

            //判断当前商家是平台，还是入驻商家
            if ($im_merchant_id == 0) {
                //判断平台是否开启了IM在线客服
                $kf_im_switch = SellerShopinfo::where('ru_id', 0)->value('kf_im_switch');

                if ($kf_im_switch) {
                    $shop_information['is_dsc'] = true;
                } else {
                    $shop_information['is_dsc'] = false;
                }
            } else {
                $shop_information['is_dsc'] = false;
            }
            $this->smarty->assign('shop_information', $shop_information);

            if ($basic_info) {
                // 统一客服
                if ($GLOBALS['_CFG']['customer_service'] == 0) {
                    $shop_info = $this->storeService->getShopInfo(0, 3);
                    $basic_info['kf_qq'] = $shop_info['kf_qq'];
                    $basic_info['kf_ww'] = $shop_info['kf_ww'];
                }
            }

            $chat = $this->dscRepository->chatQq($basic_info);
            $basic_info['kf_qq'] = $chat['kf_qq'];
            $basic_info['kf_ww'] = $chat['kf_ww'];

            $this->smarty->assign('basic_info', $basic_info);  //店铺详细信息
            $this->smarty->assign('grade_info', $grade_info);
            $this->smarty->assign('site_domain', asset('/'));  //网站域名
            $this->smarty->assign('licence_type', 1);  //店铺信息标识

            return $this->smarty->display('merchants_licence.dwt');
        }

        //获取seo start
        $seo = get_seo_words('shop');
        $store_info = $this->storeService->getMerchantsStoreInfo($merchant_id, 1);

        if ($seo) {
            foreach ($seo as $key => $value) {
                $seo[$key] = str_replace(['{sitename}', '{key}', '{shopname}', '{description}'], [$GLOBALS['_CFG']['shop_name'], $store_info['shop_keyword'], $store_info['shop_title'], $store_info['street_desc']], $value);
            }
        }

        if (isset($seo['keywords']) && !empty($seo['keywords'])) {
            $this->smarty->assign('keywords', htmlspecialchars($seo['keywords']));
        } else {
            $this->smarty->assign('keywords', htmlspecialchars($GLOBALS['_CFG']['shop_keywords']));
        }

        if (isset($seo['description']) && !empty($seo['description'])) {
            $this->smarty->assign('description', htmlspecialchars($seo['description']));
        } else {
            $this->smarty->assign('description', htmlspecialchars($GLOBALS['_CFG']['shop_desc']));
        }

        if (isset($seo['title']) && !empty($seo['title'])) {
            $this->smarty->assign('page_title', htmlspecialchars($seo['title']));
        }
        //获取seo end

        $this->smarty->assign("rec_id", $rec_id);
        $this->smarty->assign("collect_store", $rec_id);

        //判断商家使用模板
        if ($temp_code) {
            $tem = $temp_code;
        }

        $templates_mode = SellerShopinfo::where('ru_id', $merchant_id)->value('templates_mode');

        if ($templates_mode == 1 && $cat_id == 0 && $display == '' && $brand_ids == 0 && $filter_attr == '' && $keyword == '' && $price_max == '' && $price_min == '') {
            $previewController = app(PreviewController::class);

            $previewController->province_id = $this->province_id;
            $previewController->city_id = $this->city_id;
            $previewController->merchant_id = $merchant_id;
            $previewController->shop_id = $shop_id;
            $previewController->temp_code = $temp_code;
            $previewController->preview = $preview;
            $previewController->tem = $tem;
            $previewController->mershop_info = $mershop_info;
            $previewController->smarty = $this->smarty;
            $previewController->url = asset('/');
            $previewController->articleCommonService = $this->articleCommonService;

            return $previewController->index();
        } else {
            //判断店铺是否关闭
            if ($mershop_info && $mershop_info['shop_close'] == 0) {
                //关闭则跳转首页
                return dsc_header("Location: " . config('app.url') . "\n");
            }
        }

        //ecmoban模板堂 --zhuo end 仓库
        $ad_arr = '';
        for ($i = 1; $i <= $GLOBALS['_CFG']['auction_ad']; $i++) {
            $ad_arr .= "'users_a" . $i . ",";
        }

        $this->smarty->assign('adarr', $ad_arr); // 分类广告位

        $adarr_bott = '';
        for ($i = 1; $i <= $GLOBALS['_CFG']['auction_ad']; $i++) {
            $adarr_bott .= "'users_b" . $i . ",";
        }

        $this->smarty->assign('adarr_bott', $adarr_bott); // 分类广告位


        $shop_name = $this->merchantCommonService->getShopName($merchant_id, 1); //店铺名称
        $grade_info = get_seller_grade($merchant_id); //等级信息
        $store_conut = $this->storeService->getMerchantsStoreInfo($merchant_id);
        $store_info = $this->storeService->getMerchantsStoreInfo($merchant_id, 1);

        $is_dwt = 0;
        if ($cat_id > 0) {
            $is_dwt = 1;
        } elseif ($display != '') {
            $is_dwt = 1;
        } elseif ($brand_ids > 0) {
            $is_dwt = 1;
        } elseif ($filter_attr != '') {
            $is_dwt = 1;
        } elseif ($keyword != '') {
            $is_dwt = 1;
        } elseif ($price_max != '') {
            $is_dwt = 1;
        } elseif ($price_min != '') {
            $is_dwt = 1;
        } elseif ($cat_id > 0 && $sort != '') {
            $is_dwt = 1;
        }

        $is_cache = 1;
        if ($store_conut > 0 && !empty($store_info['seller_theme'])) {
            if ($is_dwt == 1) {
                $dwt = 'merchants_store.dwt';
            } else {
                $id_name = '_' . $merchant_id . "',";
                $str_ad = str_replace(',', $id_name, $ad_arr);
                $in_ad_arr = substr($str_ad, 0, strlen($str_ad) - 1);

                $ad_child = $this->adsService->getAdPostiChild($in_ad_arr);

                $this->smarty->assign('ad_child', $ad_child);

                $shopheader = get_store_header($merchant_id, $store_info['seller_theme']);

                $header_content = $shopheader['content'];
                $this->smarty->assign('header_content', $header_content); // 头部内容
                $this->smarty->assign('shopheader', $shopheader); // 头部信息

                $store_bg = get_store_bg($merchant_id, $store_info['seller_theme']);
                $this->smarty->assign('store_bg', $store_bg); // 店铺背景

                $this->smarty->assign('store', $store_info); // 店铺背景

                $is_cache = 0;
                $dwt = $store_info['seller_theme'] . '/seller_store.dwt';
            }
        } else {
            if ($is_dwt == 1) {
                $dwt = 'merchants_store.dwt';
            } else {
                $dwt = 'merchants_store.dwt';
            }
        }

        if ($is_cache) {
            $cache_id = sprintf('%X', crc32($cat_id . '-' . $merchant_id . '-' . $display . '-' . $sort . '-' . $order . '-' . $page . '-' . $size . '-' . session('user_rank') . '-' .
                $GLOBALS['_CFG']['lang'] . '-' . $brand_ids . '-' . $price_max . '-' . $price_min . '-' . $filter_attr_str . '-' . $keyword));
            $not = '';
        } else {
            $cache_id = '';
            $not = 'not';
        }

        if (!$this->smarty->is_cached($dwt, $cache_id)) {
            assign_template('', [], $merchant_id);

            $this->smarty->assign('area_id', $area_id); //仓库管理的地区ID
            //地区ID

            $this->smarty->assign('merchant_id', $merchant_id); // 商家ID
            $this->smarty->assign('cat_id', $cat_id); // 分类ID

            $parent_id = $this->storeService->getCategoryStoreParent($cat_id);
            $this->smarty->assign('parent_id', $parent_id); // 分类父级ID

            $cat = [
                'cat_name' => '',
                'cat_id' => ''
            ];

            //logo start
            $cat['name'] = $cat['cat_name'];
            $cat['id'] = $cat['cat_id'];
            $this->smarty->assign('cat', $cat);
            //logo end

            //筛选 start
            if ($cat_id > 0) {
                $cat = MerchantsCategory::catInfo($cat_id);
                $cat = $this->baseRepository->getToArrayFirst($cat);

                $children = $this->categoryService->getMerchantsCatListChildren($cat_id);
            } else {
                $cat = [];
                $children = [];
            }

            $this->smarty->assign('region_id', $warehouse_id); //仓库

            $insert_keyword = $keyword;

            $arr_keyword = [];
            if (!empty($insert_keyword)) {
                $scws_res = scws($insert_keyword, 20); //这里可以把关键词分词：诺基亚，耳机

                $arr = [];
                if ($scws_res) {
                    $arr = explode(',', $scws_res);
                }

                $arr1[] = $insert_keyword;

                if ($arr1 && $arr && is_array($arr)) {
                    $arr = array_merge($arr1, $arr);
                }

                $arr = !empty($arr) ? array_unique($arr) : [];

                $arr_keyword = $arr;
            }

            if ($cat_id > 0) {
                if (!empty($cat)) {
                    $this->smarty->assign('keywords', htmlspecialchars($cat['keywords']));
                    $this->smarty->assign('description', htmlspecialchars($cat['cat_desc']));
                    $this->smarty->assign('cat_style', htmlspecialchars($cat['style']));
                } else {
                    /* 如果分类不存在则返回首页 */
                    return dsc_header("Location: ./\n");
                }

                /* 获取价格分级 */
                if ($cat && $cat['grade'] == 0 && $cat['parent_id'] != 0) {
                    $cat['grade'] = get_store_parent_grade($cat_id); //如果当前分类级别为空，取最近的上级分类
                }

                if ($cat && $cat['grade'] > 1) {
                    /* 需要价格分级 */

                    /*
                      算法思路：
                      1、当分级大于1时，进行价格分级
                      2、取出该类下商品价格的最大值、最小值
                      3、根据商品价格的最大值来计算商品价格的分级数量级：
                      价格范围(不含最大值)    分级数量级
                      0-0.1                   0.001
                      0.1-1                   0.01
                      1-10                    0.1
                      10-100                  1
                      100-1000                10
                      1000-10000              100
                      4、计算价格跨度：
                      取整((最大值-最小值) / (价格分级数) / 数量级) * 数量级
                      5、根据价格跨度计算价格范围区间
                      6、查询数据库

                      可能存在问题：
                      1、
                      由于价格跨度是由最大值、最小值计算出来的
                      然后再通过价格跨度来确定显示时的价格范围区间
                      所以可能会存在价格分级数量不正确的问题
                      该问题没有证明
                      2、
                      当价格=最大值时，分级会多出来，已被证明存在
                     */

                    $row = $this->categoryGoodsService->getGoodsPriceMaxMin($children, $brand_ids, $warehouse_id, $area_id, $area_city, [], [], $arr_keyword, '', 'user_cat');

                    // 取得价格分级最小单位级数，比如，千元商品最小以100为级数
                    $price_grade = 0.0001;
                    for ($i = -2; $i <= log10($row['max']); $i++) {
                        $price_grade *= 10;
                    }

                    //跨度
                    $dx = ceil(($row['max'] - $row['min']) / ($cat['grade']) / $price_grade) * $price_grade;
                    if ($dx == 0) {
                        $dx = $price_grade;
                    }

                    for ($i = 1; $row['min'] > $dx * $i; $i++) ;

                    for ($j = 1; $row['min'] > $dx * ($i - 1) + $price_grade * $j; $j++) ;
                    $row['min'] = $dx * ($i - 1) + $price_grade * ($j - 1);

                    for (; $row['max'] >= $dx * $i; $i++) ;
                    $row['max'] = $dx * ($i) + $price_grade * ($j - 1);

                    $price_grade = $this->categoryGoodsService->getGoodsPriceGrade($row['list'], $row['min'], $dx);

                    if ($price_grade) {
                        foreach ($price_grade as $key => $val) {
                            $temp_key = $key + 1;
                            $price_grade[$temp_key]['goods_num'] = $val['goods_num'];
                            $price_grade[$temp_key]['start'] = $row['min'] + round($dx * $val['sn']);
                            $price_grade[$temp_key]['end'] = $row['min'] + round($dx * ($val['sn'] + 1));
                            $price_grade[$temp_key]['price_range'] = $price_grade[$temp_key]['start'] . '&nbsp;-&nbsp;' . $price_grade[$temp_key]['end'];
                            $price_grade[$temp_key]['formated_start'] = price_format($price_grade[$temp_key]['start']);
                            $price_grade[$temp_key]['formated_end'] = price_format($price_grade[$temp_key]['end']);

                            $build_uri = [
                                'cid' => $cat_id,
                                'urid' => $merchant_id,
                                'append' => $cat['cat_name'],
                                'brand_id' => $brand_ids,
                                'price_min' => $price_grade[$temp_key]['start'],
                                'price_max' => $price_grade[$temp_key]['end'],
                                'filter_attr' => $filter_attr_str
                            ];

                            $domain_url = $this->merchantCommonService->getSellerDomainUrl($merchant_id, $build_uri);
                            $price_grade[$temp_key]['url'] = $domain_url['domain_name'];
                            /* 判断价格区间是否被选中 */

                            if (request()->exists('price_min') && $price_grade[$temp_key]['start'] == $price_min && $price_grade[$temp_key]['end'] == $price_max) {
                                $price_grade[$temp_key]['selected'] = 1;
                            } else {
                                $price_grade[$temp_key]['selected'] = 0;
                            }
                        }
                    }

                    $price_grade[0]['start'] = 0;
                    $price_grade[0]['end'] = 0;
                    $price_grade[0]['price_range'] = $GLOBALS['_LANG']['all_attribute'];

                    $build_uri = [
                        'cid' => $cat_id,
                        'urid' => $merchant_id,
                        'append' => $cat['cat_name'],
                        'brand_id' => $brand_ids,
                        'price_min' => 0,
                        'price_max' => 0,
                        'filter_attr' => $filter_attr_str
                    ];

                    $domain_url = $this->merchantCommonService->getSellerDomainUrl($merchant_id, $build_uri);
                    $price_grade[0]['url'] = $domain_url['domain_name'];
                    $price_grade[0]['selected'] = empty($price_max) ? 1 : 0;

                    $this->smarty->assign('price_grade', $price_grade);
                }

                /* 品牌筛选 */
                $brands = $this->categoryBrandService->getCatBrand($cat_id, $children, $warehouse_id, $area_id, $area_city, $arr_keyword, [], 'asc', 0, 'user_cat', $merchant_id);
                if ($brands) {
                    foreach ($brands as $key => $val) {
                        $temp_key = $key + 1;
                        $brands[$temp_key]['brand_name'] = $val['brand_name'];
                        $brands[$temp_key]['brand_id'] = $val['brand_id'];

                        $build_uri = [
                            'cid' => $cat_id,
                            'urid' => $merchant_id,
                            'append' => $cat['cat_name'],
                            'bid' => $val['brand_id'],
                            'price_min' => $price_min,
                            'price_max' => $price_max,
                            'filter_attr' => $filter_attr_str
                        ];

                        $shop_url = $this->dscRepository->sellerUrl($merchant_id, $build_uri);
                        $brands[$temp_key]['url'] = $shop_url;

                        /* 判断品牌是否被选中 */
                        if ($brand_ids == $brands[$temp_key]['brand_id']) {
                            $brands[$temp_key]['selected'] = 1;
                        } else {
                            $brands[$temp_key]['selected'] = 0;
                        }
                    }
                }

                $brands[0]['brand_name'] = $GLOBALS['_LANG']['all_attribute'];

                $build_uri = [
                    'cid' => $cat_id,
                    'urid' => $merchant_id,
                    'append' => $cat['cat_name'],
                    'brand_id' => 0,
                    'price_min' => $price_min,
                    'price_max' => $price_max,
                    'filter_attr' => $filter_attr_str
                ];
                $shop_url = $this->dscRepository->sellerUrl($merchant_id, $build_uri);

                $brands[0]['url'] = $shop_url;
                $brands[0]['selected'] = empty($brand_ids) ? 1 : 0;

                $this->smarty->assign('brands', $brands);

                //商品查询条件扩展
                if ($cat['filter_attr'] > 0) {
                    $cat_filter_attr = explode(',', $cat['filter_attr']);       //提取出此分类的筛选属性
                    $all_attr_list = [];

                    foreach ($cat_filter_attr as $key => $value) {
                        $attributeInfo = $this->categoryAttributeService->getCatAttribute($value, $children, $warehouse_id, $area_id, $area_city, $arr_keyword, 'user_cat');

                        if ($attributeInfo) {
                            $all_attr_list[$key]['filter_attr_name'] = $attributeInfo['attr_name'];
                            $all_attr_list[$key]['attr_cat_type'] = $attributeInfo['attr_cat_type'];

                            $attr_list = $this->categoryAttributeService->getCatAttributeAttrList($value, $children, $warehouse_id, $area_id, $area_city, $arr_keyword, 'user_cat');

                            $temp_arrt_url_arr = [];

                            for ($i = 0; $i < count($cat_filter_attr); $i++) {        //获取当前url中已选择属性的值，并保留在数组中
                                $temp_arrt_url_arr[$i] = !empty($filter_attr[$i]) ? $filter_attr[$i] : 0;
                            }

                            $temp_arrt_url_arr[$key] = 0;                           //“全部”的信息生成
                            $temp_arrt_url = implode(' . ', $temp_arrt_url_arr);
                            $all_attr_list[$key]['attr_list'][0]['attr_value'] = $GLOBALS['_LANG']['all_attribute'];

                            $build_uri = [
                                'cid' => $cat_id,
                                'urid' => $merchant_id,
                                'append' => $cat['cat_name'],
                                'brand_id' => $brand_ids,
                                'price_min' => $price_min,
                                'price_max' => $price_max,
                                'filter_attr' => $temp_arrt_url
                            ];

                            $domain_url = $this->merchantCommonService->getSellerDomainUrl($merchant_id, $build_uri);
                            $all_attr_list[$key]['attr_list'][0]['url'] = $domain_url['domain_name'];
                            $all_attr_list[$key]['attr_list'][0]['selected'] = empty($filter_attr[$key]) ? 1 : 0;

                            foreach ($attr_list as $k => $v) {
                                $temp_key = $k + 1;
                                $temp_arrt_url_arr[$key] = $v['goods_id'];       //为url中代表当前筛选属性的位置变量赋值,并生成以‘.’分隔的筛选属性字符串
                                $temp_arrt_url = implode(' . ', $temp_arrt_url_arr);

                                $all_attr_list[$key]['attr_list'][$temp_key]['attr_value'] = $v['attr_value'];

                                $build_uri = [
                                    'cid' => $cat_id,
                                    'urid' => $merchant_id,
                                    'append' => $cat['cat_name'],
                                    'brand_id' => $brand_ids,
                                    'price_min' => $price_min,
                                    'price_max' => $price_max,
                                    'filter_attr' => $temp_arrt_url
                                ];

                                $domain_url = $this->merchantCommonService->getSellerDomainUrl($merchant_id, $build_uri);
                                $all_attr_list[$key]['attr_list'][$temp_key]['url'] = $domain_url['domain_name'];

                                if (!empty($filter_attr[$key]) and $filter_attr[$key] == $v['goods_id']) {
                                    $all_attr_list[$key]['attr_list'][$temp_key]['selected'] = 1;
                                } else {
                                    $all_attr_list[$key]['attr_list'][$temp_key]['selected'] = 0;
                                }
                            }
                        }
                    }

                    $this->smarty->assign('filter_attr_list', $all_attr_list);
                }
                //筛选 end
            }

            $cat_name = '';
            if ($cat_id > 0) {
                $cat_name = MerchantsCategory::where('cat_id', $cat_id)->value('cat_name');
                $cat_name = "-" . $cat_name;
            }

            $position = assign_ur_here(0, $shop_name . $cat_name);

            $this->smarty->assign('page_title', $position['title']);    // 页面标题
            $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置
            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助

            $categories_pro = get_category_tree_leve_one();
            $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版

            $this->smarty->assign('search_keywords', htmlspecialchars($keywords));
            $this->smarty->assign('keyword', htmlspecialchars($keyword));
            $this->smarty->assign('price_min', htmlspecialchars($price_min));
            $this->smarty->assign('price_max', htmlspecialchars($price_max));

            $all_cat_list = $this->categoryService->getMerchantsCatList(0, $merchant_id);
            $this->smarty->assign('all_cat_list', $all_cat_list);

            $cat_list = $this->categoryService->getMerchantsCatList($cat_id, $merchant_id);
            $this->smarty->assign('cat_store_list', $cat_list);

            if ($cat_list) {
                $size = 12;
            }

            $count = $this->storeService->getStoreGoodsCount($children, $merchant_id, $brand_ids, $warehouse_id, $area_id, $area_city, $price_min, $price_max, $filter_attr, $arr_keyword);
            $max_page = ($count > 0) ? ceil($count / $size) : 1;
            if ($page > $max_page) {
                $page = $max_page;
            }

            $goodslist = $this->storeService->getStoreGetGoods($children, $merchant_id, $brand_ids, $warehouse_id, $area_id, $area_city, $price_min, $price_max, $filter_attr, $arr_keyword, $size, $page, $sort, $order);

            $this->smarty->assign('goods_list', $goodslist);
            $this->smarty->assign('script_name', 'merchants_store');
            $this->smarty->assign('category', $cat_id);
            $this->smarty->assign('count', $count);

            $rec_id = CollectStore::where('user_id', $user_id)->where('ru_id', $merchant_id)->value('rec_id');
            $this->smarty->assign('collect_store', $rec_id);

            $where = [
                'children' => $children,
                'ru_id' => $merchant_id,
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city
            ];

            if ($merchant_id) {
                $where['store_hot'] = 1;
            }

            $goods_hot = $this->storeService->GetHotNewBestGoods($where);
            $this->smarty->assign('goods_hot', $goods_hot); //热销商品

            if ($merchant_id) {
                $where['store_new'] = 1;
            }

            $goods_new = $this->storeService->GetHotNewBestGoods($where);
            $this->smarty->assign('goods_new', $goods_new); //新品推荐

            assign_pager('merchants_store', $cat_id, $count, $size, $sort, $order, $page, '', $brand_ids, $price_min, $price_max, $display, $filter_attr_str, '', '', $merchant_id, $keyword); // 分页

            //获取可视化模板
            $seller_templates = SellerShopinfo::where('ru_id', $merchant_id)->value('seller_templates');

            if ($seller_templates) {
                $head_temp = get_seller_templates($merchant_id, 2, $seller_templates);
                $head_temp['out'] = str_replace(' ../data / ', $this->dscRepository->getImagePath(DATA_DIR . "/"), $head_temp['out'], $i);

                if ($GLOBALS['_CFG']['open_oss'] == 1) {
                    $bucket_info = $this->dscRepository->getBucketInfo();
                    $endpoint = $bucket_info['endpoint'];
                } else {
                    $endpoint = asset(' / ');
                }

                $desc_preg = get_goods_desc_images_preg($endpoint, $head_temp['out']);
                $head_temp['out'] = $desc_preg['goods_desc'];

                $this->smarty->assign('head_temp', $head_temp['out']);
            }

            /* 页面中的动态内容 */
            assign_dynamic('merchants_store');
        }

        if ($merchant_id > 0) {
            $merchants_goods_comment = $this->commentService->getMerchantsGoodsComment($merchant_id); //商家所有商品评分类型汇总
        }

        $this->smarty->assign('merch_cmt', $merchants_goods_comment);

        $store_category = get_user_store_category($merchant_id); //店铺导航栏
        $this->smarty->assign('store_category', $store_category);

        //商家二维码 by wu start
        $basic_info = $this->storeService->getShopInfo($merchant_id, 3);

        $logo = !empty($basic_info['logo_thumb']) ? str_replace(' ../', '', $basic_info['logo_thumb']) : '';

        if ($GLOBALS['_CFG']['open_oss'] == 1) {
            $logo = $logo ? $this->dscRepository->getImagePath($logo) : '';
        } else {
            $logo = $logo && (strpos($logo, 'http') === false) ? storage_public($logo) : $logo;
        }

        $url = url('/') . '/';
        $data = $url . "mobile/#/shopHome/" . $merchant_id;
        $errorCorrectionLevel = 'Q'; // 纠错级别：L、M、Q、H
        $matrixPointSize = 4; // 点的大小：1到10

        $image = IMAGE_DIR . "/seller_imgs/seller_qrcode/seller_qrcode_" . $merchant_id . ".png";
        $filename = storage_public($image);

        if (!file_exists(storage_public(IMAGE_DIR . "/seller_imgs/seller_qrcode"))) {
            make_dir(storage_public(IMAGE_DIR . "/seller_imgs/seller_qrcode"));
        }

        if (!file_exists(storage_public($filename))) {
            $QRcode = new QRcode();
            $QRcode->png($data, $filename, $errorCorrectionLevel, $matrixPointSize);
            $QR = imagecreatefrompng($filename);

            $linkExists = $this->dscRepository->remoteLinkExists($logo);

            if ($linkExists) {
                $logo = imagecreatefromstring(file_get_contents($logo));
                $QR_width = imagesx($QR);
                $QR_height = imagesy($QR);
                $logo_width = imagesx($logo);
                $logo_height = imagesy($logo);
                // Scale logo to fit in the QR Code
                $logo_qr_width = $QR_width / 5;
                $scale = $logo_width / $logo_qr_width;
                $logo_qr_height = $logo_height / $scale;
                $from_width = ($QR_width - $logo_qr_width) / 2;
                //echo $from_width;
                imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
            }
            imagepng($QR, $filename);
            imagedestroy($QR);
        }

        $this->dscRepository->getOssAddFile([$image]);

        $this->smarty->assign('seller_qrcode_img', $this->dscRepository->getImagePath($image));

        $basic_info['shop_name'] = $basic_info && isset($basic_info['shop_name']) ? $basic_info['shop_name'] : '';
        $this->smarty->assign('seller_qrcode_text', $basic_info['shop_name']);
        //商家二维码 by wu end
        $basic_info['shop_logo'] = $basic_info && isset($basic_info['shop_logo']) ? $basic_info['shop_logo'] : '';
        $basic_info['shop_logo'] = str_replace(' ../', '', $basic_info['shop_logo']);

        //二维码 by yan xin end
        $basic_info['logo_thumb'] = $basic_info && isset($basic_info['logo_thumb']) ? $basic_info['logo_thumb'] : '';
        $basic_info['logo_thumb'] = str_replace(' ../', '', $basic_info['logo_thumb']);//二维码

        $basic_info['shop_logo'] = $this->dscRepository->getImagePath($basic_info['shop_logo']);

        if ($GLOBALS['_CFG']['customer_service'] == 0) {
            $im_merchant_id = 0;
        } else {
            $im_merchant_id = $merchant_id;
        }

        /*  @author-bylu 判断当前商家是否允许"在线客服" start */
        $shop_information = $this->merchantCommonService->getShopName($im_merchant_id);

        //判断当前商家是平台,还是入驻商家
        if ($im_merchant_id == 0) {
            //判断平台是否开启了IM在线客服
            $kf_im_switch = SellerShopinfo::where('ru_id', 0)->value('kf_im_switch');
            if ($kf_im_switch) {
                $shop_information['is_dsc'] = true;
            } else {
                $shop_information['is_dsc'] = false;
            }
        } else {
            $shop_information['is_dsc'] = false;
        }

        $this->smarty->assign('shop_information', $shop_information);

        // 统一客服
        if ($GLOBALS['_CFG']['customer_service'] == 0) {
            $shop_info = $this->storeService->getShopInfo(0, 3);
            $basic_info['kf_qq'] = $shop_info['kf_qq'];
            $basic_info['kf_ww'] = $shop_info['kf_ww'];
        }

        /*处理客服QQ数组 by kong*/
        if ($basic_info['kf_qq']) {
            $kf_qq = array_filter(preg_split(' / \s +/', $basic_info['kf_qq']));
            $kf_qq = $kf_qq && $kf_qq[0] ? explode("|", $kf_qq[0]) : [];
            if (isset($kf_qq[1]) && !empty($kf_qq[1])) {
                $basic_info['kf_qq'] = $kf_qq[1];
            } else {
                $basic_info['kf_qq'] = "";
            }
        } else {
            $basic_info['kf_qq'] = "";
        }
        /*处理客服旺旺数组 by kong*/
        if ($basic_info['kf_ww']) {
            $kf_ww = array_filter(preg_split(' / \s +/', $basic_info['kf_ww']));
            $kf_ww = $kf_ww && $kf_ww[0] ? explode("|", $kf_ww[0]) : [];
            if (isset($kf_ww[1]) && !empty($kf_ww[1])) {
                $basic_info['kf_ww'] = $kf_ww[1];
            } else {
                $basic_info['kf_ww'] = "";
            }
        } else {
            $basic_info['kf_ww'] = "";
        }

        $this->smarty->assign('basic_info', $basic_info);  //店铺详细信息

        $banner_list = get_store_banner_list($merchant_id, $store_info['seller_theme']); //店铺首页轮播图
        $this->smarty->assign('banner_list', $banner_list);

        $win_list = get_store_win_list($merchant_id, $warehouse_id, $area_id, $area_city, $store_info['seller_theme']); //店铺橱窗
        $this->smarty->assign('win_list', $win_list);

        $this->smarty->assign('site_domain', url('/') . '/');  //网站域名
        $this->smarty->assign('shop_name', $shop_name);
        $this->smarty->assign('grade_info', $grade_info);

        $build_uri = [
            'urid' => $merchant_id,
            'append' => $shop_name
        ];

        $domain_url = $this->merchantCommonService->getSellerDomainUrl($merchant_id, $build_uri);
        $merchants_url = $domain_url['domain_name'];
        $this->smarty->assign('merchants_url', $merchants_url);  //网站域名

        $this->smarty->assign('filename', 'merchants_store');

        return $this->smarty->display($dwt, $cache_id, $not);
    }

    /**
     * 处理店铺二级域名跨域问题
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function crossDomain()
    {
        /**
         * Start
         *
         * @param $warehouse_id 仓库ID
         * @param $area_id 省份ID
         * @param $area_city 城市ID
         */
        $warehouse_id = $this->warehouseId();
        $area_id = $this->areaId();
        $area_city = $this->areaCity();
        /* End */

        $session_id = $this->sessionRepository->realCartMacIp();
        $user_id = session('user_id', 0);
        $act = request()->input('act', '');

        $result = [];
        if ($act == 'act_login') {
            $result = $this->commonService->actLogin();
        } elseif ($act == 'header_region_name') {
            $result = $this->commonService->headerRegionName($this->region_name);
        } elseif ($act == 'ajax_store_collect') {
            $result = $this->commonService->ajaxStoreCollect($user_id);
        } elseif ($act == 'cart_number') {
            $result = $this->commonService->cartNumber();
        } elseif ($act == 'getguessYouLike') {
            $result = $this->commonService->getguessYouLike();
        } elseif ($act == 'get_content') {
            $result = $this->commonService->getContent($user_id, $warehouse_id, $area_id, $area_city);
        } elseif ($act == 'cart_info') {
            $result = $this->commonService->domainCartInfo();
        } elseif ($act == 'ajax_update_cart') {
            load_helper('order');
            $result = $this->commonService->ajaxUpdateCart($user_id, $session_id, $warehouse_id, $area_id, $area_city);
        } elseif ($act == 'delete_cart') {
            $result = $this->commonService->ajaxDeleteCartGoods();
        } elseif ($act == 'coupons_receive') {
            $result = $this->commonService->ajaxCouponsReceive($user_id);
        }

        return response()->json($result);
    }
}
