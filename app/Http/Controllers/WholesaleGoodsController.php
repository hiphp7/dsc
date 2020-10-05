<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\SellerShopinfo;
use App\Models\UserRank;
use App\Models\Wholesale;
use App\Models\WholesaleVolumePrice;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Common\CommonService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Wholesale\CategoryService;
use App\Services\Wholesale\GoodsService;
use App\Services\Wholesale\WholesaleService;
use App\Libraries\QRcode;

/**
 * 调查程序
 */
class WholesaleGoodsController extends InitController
{
    protected $categoryService;
    protected $wholesaleService;
    protected $goodsService;
    protected $baseRepository;
    protected $dscRepository;
    protected $merchantCommonService;
    protected $commonService;

    public function __construct(
        CategoryService $categoryService,
        WholesaleService $wholesaleService,
        GoodsService $goodsService,
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService,
        CommonService $commonService
    )
    {
        $this->categoryService = $categoryService;
        $this->wholesaleService = $wholesaleService;
        $this->goodsService = $goodsService;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->commonService = $commonService;
    }

    public function index()
    {
        load_helper(['order', 'wholesale', 'publicfunc']);

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

        $user_id = session('user_id', 0);
        $act = addslashes(trim(request()->input('act', '')));
        /* 过滤 XSS 攻击和SQL注入 */
        get_request_filter();

        /* ------------------------------------------------------ */
        //-- 改变属性、数量时重新计算商品价格
        /* ------------------------------------------------------ */

        if (!empty($act) && $act == 'get_select_record') {
            $result = array('error' => '', 'message' => 0, 'content' => '');

            //处理数据
            $goods_id = (int)request()->input('goods_id', 0);

            //判断商品是否设置属性
            $goods_type = Wholesale::where('goods_id', $goods_id)->value('goods_type');

            $properties = $this->goodsService->getWholesaleGoodsProperties($goods_id);
            if ($goods_type > 0 && $properties['spe']) { //有属性的时候
                $attr_array=request()->input('attr_array',[]);
                $num_array=request()->input('num_array',[]);

                $result['total_number'] = array_sum($num_array);

                //格式化属性数组
                $attr_num_array = array();

                if ($attr_array) {
                    foreach ($attr_array as $key => $val) {
                        $arr = array();
                        $arr['attr'] = $val;
                        $arr['num'] = $num_array[$key];
                        $attr_num_array[] = $arr;
                    }
                }

                //生成记录表格
                $record_data = $this->goodsService->getSelectRecordData($goods_id, $attr_num_array);
                $this->smarty->assign('record_data', $record_data);
                $result['record_data'] = $this->smarty->fetch('library/wholesale_select_record_data.lbi');
            } else {
                //无属性的时候
                //购买数量
                $result['total_number'] = (int)request()->input('goods_number', 0);
            }

            //计算价格
            $data = $this->goodsService->calculateGoodsPrice($goods_id, $result['total_number']);
            $result['data'] = $data;

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 改变属性、数量时重新计算商品价格
        /* ------------------------------------------------------ */

        if (!empty($act) && $act == 'price') {

            $res = array('err_msg' => '', 'err_no' => 0, 'result' => '', 'qty' => 1);

            $attr_id = request()->input('attr', []);
            $attr_id = $attr_id ? explode(',', $attr_id) : [];

            $goods_id = (int)request()->input('id', 0);
            //获取主属性列表
            $this->smarty->assign('goods', $this->goodsService->getWholesaleGoodsInfo($goods_id));
            $main_attr_list = $this->goodsService->getWholesaleMainAttrList($goods_id, $attr_id);
            $this->smarty->assign('main_attr_list', $main_attr_list);
            $res['main_attr_list'] = $this->smarty->fetch('library/wholesale_main_attr_list.lbi');

            return response()->json($res);
        }


        $goods_id = (int)request()->input('id', 0);

        /* 跳转H5 start */
        $Loaction = 'mobile#/supplier/detail/' . $goods_id;
        $uachar = $this->dscRepository->getReturnMobile($Loaction);

        if ($uachar) {
            return $uachar;
        }
        /* 跳转H5 end */

        assign_template();

        $position = '';
        $properties = [];
        $goods = $this->goodsService->getWholesaleGoodsInfo($goods_id);

        if (empty($goods)) {
            return redirect()->route('wholesale');
        }

        if (!empty($goods)) {
            $position = assign_ur_here($goods['cat_id'], $goods['goods_name'], array(), '', $goods['user_id']);
            $properties = $this->goodsService->getWholesaleGoodsProperties($goods['goods_id']);  // 获得商品的规格和属性
        }

        $wholesale_rank = [];
        if (isset($goods['rank_ids']) && !empty($goods['rank_ids'])) {
            $wholesale_rank = UserRank::whereRaw(1);
            $wholesale_rank = $wholesale_rank->whereIn('rank_id', $goods['rank_ids']);
            $wholesale_rank = $this->baseRepository->getToArrayGet($wholesale_rank);
        }

        $this->smarty->assign('wholesale_rank', $wholesale_rank);

        /*  @author-bylu 判断当前商家是否允许"在线客服" start */
        $shop_information = [];
        if (!empty($goods)) {
            $shop_information = $this->merchantCommonService->getShopName($goods['user_id']);
            if (isset($shop_information['business_practice']) && $shop_information['business_practice'] == 1) {
                $shop_information['business_practice'] = lang('wholesale.person_buy_sale');
            } else {
                $shop_information['business_practice'] = lang('wholesale.manufactor_sale');
            }
        }

        //判断当前商家是平台,还是入驻商家 bylu
        if (isset($goods['user_id']) && $goods['user_id'] == 0) {
            //判断平台是否开启了IM在线客服
            $kf_im_switch = SellerShopinfo::where('ru_id', 0);
            if ($kf_im_switch) {
                $shop_information['is_dsc'] = true;
            } else {
                $shop_information['is_dsc'] = false;
            }
        } else {
            $shop_information['is_dsc'] = false;
        }
        //@author guan start
        if ($GLOBALS['_CFG']['two_code'] && isset($goods['goods_id']) && $goods['goods_id'] > 0) {
            $goods_weixin_path = storage_public(IMAGE_DIR . "/weixin_img/");

            /* 生成目录 */
            if (!file_exists($goods_weixin_path)) {
                make_dir($goods_weixin_path);
            }

            $logo = empty($GLOBALS['_CFG']['two_code_logo']) ? '' : str_replace('../', '', $GLOBALS['_CFG']['two_code_logo']);

            if ($GLOBALS['_CFG']['open_oss'] == 1) {
                $logo = $logo ? $this->dscRepository->getImagePath($logo) : '';
            } else {
                $logo = $logo && (strpos($logo, 'http') === false) ? storage_public($logo) : $logo;
            }

            $size = '200x200';
            $url = url('/') . '/';
            $two_code_links = trim($GLOBALS['_CFG']['two_code_links']);

            $two_code_links = empty($two_code_links) ? $url : $two_code_links;
            $data = $two_code_links . 'wholesale_goods.php?id=' . $goods['goods_id'];
            $errorCorrectionLevel = 'H'; // 纠错级别：L、M、Q、H
            $matrixPointSize = 4; // 点的大小：1到10
            $image = IMAGE_DIR . '/weixin_img/weixin_wholesale_code_' . $goods['goods_id'] . '.png';
            $filename = storage_public($image);

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

            $this->dscRepository->getOssAddFile([$image]);

            $this->dscRepository->helpersLang('goods');
            $this->smarty->assign('lang', $GLOBALS['_LANG']);

            $this->smarty->assign('weixin_img_url', $this->dscRepository->getImagePath($image));
            $this->smarty->assign('weixin_img_text', trim($GLOBALS['_CFG']['two_code_mouse']));
            $this->smarty->assign('two_code', trim($GLOBALS['_CFG']['two_code']));
        }
        //@author guan end

        $get_wholsale_navigator = $this->wholesaleService->getWholsaleNavigator();
        $this->smarty->assign('get_wholsale_navigator', $get_wholsale_navigator);

        $this->smarty->assign('shop_information', $shop_information);
        $this->smarty->assign('kf_appkey', $shop_information['kf_appkey'] ?? ''); //应用appkey;
        $this->smarty->assign('im_user_id', 'dsc' . session('user_id')); //登入用户ID;
        /*  @author-bylu  end */
        $basic_info['province'] = isset($shop_information['province']) ? Region::where('region_id', $shop_information['province'])->value('region_name') : '';
        $basic_info['city'] = isset($shop_information['city']) ? Region::where('region_id', $shop_information['city'])->value('region_name') : '';

        $this->smarty->assign('basic_info', $shop_information);

        $shop_info = [];
        $adress = [];
        if (!empty($goods)) {
            $shop_info = get_merchants_shop_info($goods['user_id']);
            $shop_info['license_comp_adress'] = $shop_info['license_comp_adress'] ?? '';
            $adress = get_license_comp_adress($shop_info['license_comp_adress']);
        }

        $this->smarty->assign('shop_info', $shop_info);
        $this->smarty->assign('adress', $adress);

        $seller_recommend = [];
        if (!empty($goods)) {
            $seller_recommend = Wholesale::where('is_recommend', 1);
            $seller_recommend = $seller_recommend->whereHas('getSuppliers', function ($query) use ($goods) {
                $query->where('user_id', $goods['user_id']);
            });

            $seller_recommend = $this->baseRepository->getToArrayFirst($seller_recommend);
        }

        if ($seller_recommend) {
            $price = WholesaleVolumePrice::where('goods_id', $goods_id)->min('volume_price');

            if ($seller_recommend['price_model'] != 0) {
                $seller_recommend['price_model'] = $price;
            } else {
                $seller_recommend['price_model'] = $seller_recommend['goods_price'];
            }

            $seller_recommend['goods_url'] = $this->dscRepository->buildUri('wholesale_goods', array('aid' => $seller_recommend['goods_id']), $seller_recommend['goods_name']);
            $this->smarty->assign('seller_recommend', $seller_recommend);
        }

        //买家还在看
        $see_more_goods = [];
        if (!empty($goods)) {
            $see_more_goods = $this->see_more_wholesale($goods['suppliers_id'], $goods_id);
        }
        $this->smarty->assign('see_more_goods', $see_more_goods);

        $area = array(
            'region_id' => $warehouse_id, //仓库ID
            'province_id' => 0,
            'city_id' => 0,
            'district_id' => 0,
            'goods_id' => $goods_id,
            'user_id' => $user_id,
            'area_id' => $area_id,
            'merchant_id' => $goods['user_id'] ?? 0,
        );

        $cat_list = $this->categoryService->getCategoryList();
        $this->smarty->assign('cat_list', $cat_list);

        $this->smarty->assign('properties', $properties['pro'] ?? []);      // 商品规格
        $this->smarty->assign('specification', $properties['spe'] ?? []);      // 商品属性
        $this->smarty->assign('page_title', $position['title'] ?? '');      // 页面标题
        $this->smarty->assign('ur_here', $position['ur_here'] ?? '');    // 当前位置
        $this->smarty->assign('now_time', gmtime());             // 当前系统时间
        $this->smarty->assign('seller_id', 0);
        $this->smarty->assign('goods', $goods);
        $this->smarty->assign('cfg', $GLOBALS['_CFG']);
        $this->smarty->assign('goods_id', $goods_id);
        $this->smarty->assign('area', $area);
        $pictures = $this->goodsService->getGoodsGallery($goods_id);
        $this->smarty->assign('pictures', $pictures);                    // 商品相册

        //属性商品
        $this->smarty->assign('has_specification', empty($properties['spe']) ? 0 : 1);

        return $this->smarty->display('wholesale_goods.dwt');
    }

    /* 批发函数 */
    private function see_more_wholesale($suppliers_id, $goods_id)
    {
        $res = Wholesale::where('suppliers_id', $suppliers_id)
            ->where('goods_id', '<>', $goods_id)
            ->where('review_status', 3)
            ->where('is_delete', 0)
            ->where('enabled', 1);

        $res = $res->with([
            'getWholesaleVolumePriceList'
        ]);

        $res = $res->orderBy('goods_id', 'desc');

        $res = $res->take(5);

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $k => $v) {
                $volume_price = $this->baseRepository->getArrayMax($v['get_wholesale_volume_price_list'], 'volume_price');

                if ($v['price_model'] != 0) {
                    $res[$k]['price'] = $volume_price;
                } else {
                    $res[$k]['price'] = $v['goods_price'];
                }

                $res[$k]['goods_url'] = $this->dscRepository->buildUri('wholesale_goods', array('aid' => $v['goods_id']), $v['goods_name']);
                $res[$k]['goods_name'] = $v['goods_name'];
                $res[$k]['goods_thumb'] = $this->dscRepository->getImagePath($v['goods_thumb']); //处理图片地址
            }
        }

        return $res;
    }
}
