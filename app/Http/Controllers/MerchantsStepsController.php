<?php

namespace App\Http\Controllers;

use App\Models\MerchantsCategoryTemporarydate;
use App\Models\MerchantsDtFile;
use App\Models\MerchantsShopBrand;
use App\Models\MerchantsShopBrandfile;
use App\Models\MerchantsShopInformation;
use App\Models\MerchantsStepsFields;
use App\Models\MerchantsStepsProcess;
use App\Services\Article\ArticleCommonService;
use App\Services\CrossBorder\CrossBorderService;
use App\Services\Merchant\MerchantCommonService;

/**
 * 购物流程
 */
class MerchantsStepsController extends InitController
{
    protected $merchantCommonService;
    protected $articleCommonService;

    public function __construct(
        MerchantCommonService $merchantCommonService,
        ArticleCommonService $articleCommonService
    )
    {
        $this->merchantCommonService = $merchantCommonService;
        $this->articleCommonService = $articleCommonService;
    }

    public function index()
    {
        $brand_name = htmlspecialchars(trim(request()->input('searchBrandZhInput', '')));
        $brand_letter = htmlspecialchars(trim(request()->input('searchBrandEnInput', '')));

        if (CROSS_BORDER === true) // 跨境多商户
        {
            $web = app(CrossBorderService::class)->webExists();

            if (!empty($web)) {
                $web->smartyAssign();
            }
        }

        /*------------------------------------------------------ */
        //-- 判断是否存在缓存，如果存在则调用缓存，反之读取相应内容
        /*------------------------------------------------------ */
        //流程步骤
        $step = htmlspecialchars(trim(request()->input('step', '')));
        //流程步骤ID
        $sid = (int)request()->input('sid', 1);
        //当前步骤数组key
        $pid_key = (int)request()->input('pid_key', 0);
        //品牌ID
        $ec_shop_bid = (int)request()->input('ec_shop_bid', 0);

        //为空则显示品牌列表，否则添加或编辑品牌信息
        $brandView = htmlspecialchars(trim(request()->input('brandView', '')));
        $user_id = session('user_id', 0);
        $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助
        //品牌ID
        $brandId = (int)request()->input('brandId', 0);

        $this->smarty->assign('brandId', $brandId);       //品牌ID

        if (empty($sid)) {
            $sid = 1;
        }
        //ajax数据返回 start

        /**
         * 查找二级类目
         */
        if ($step == 'addChildCate') {
            $cat_id = (int)request()->input('cat_id', 0);
            $type = (int)request()->input('type', 0);

            $result = ['error' => 0, 'message' => '', 'content' => '', 'cat_id' => ''];

            if ($user_id > 0) {
                $catarr = [];
                if ($type == 1) { //取消二级类目
                    $cat = strip_tags(urldecode(request()->input('cateArr', '')));
                    $cat = json_str_iconv($cat);
                    $cat = dsc_decode($cat);

                    $catarr = $cat->cat_id;
                }

                $cate_list = get_first_cate_list($cat_id, $type, $catarr, $user_id);

                if (!$cat_id) {
                    $cate_list = [];
                }

                $this->smarty->assign('cate_list', $cate_list);
                $this->smarty->assign('cat_id', $cat_id);
                $result['content'] = $this->smarty->fetch("library/merchants_cate_list.lbi");

                if ($type == 1) { //取消二级类目
                    $result['type'] = $type;
                    $category_info = get_fine_category_info(0, $user_id);
                    $this->smarty->assign('category_info', $category_info);
                    $result['cate_checked'] = $this->smarty->fetch("library/merchants_cate_checked_list.lbi");

                    $permanent_list = get_category_permanent_list($user_id);
                    $this->smarty->assign('permanent_list', $permanent_list);
                    $result['catePermanent'] = $this->smarty->fetch("library/merchants_steps_catePermanent.lbi");
                }
            } else {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['login_again'];
            }

            return response()->json($result);
        } /**
         * 添加二级类目
         */
        elseif ($step == 'addChildCate_checked') {
            $result = ['error' => 0, 'message' => '', 'content' => '', 'cat_id' => ''];

            if ($user_id > 0) {
                $cat = strip_tags(urldecode(request()->input('cat_id', '')));
                $cat = json_str_iconv($cat);
                $cat = dsc_decode($cat);

                $child_category = get_child_category($cat->cat_id);
                $category_info = get_fine_category_info($child_category['cat_id'], $user_id);
                $this->smarty->assign('category_info', $category_info);
                $result['content'] = $this->smarty->fetch("library/merchants_cate_checked_list.lbi");

                $permanent_list = get_category_permanent_list($user_id);
                $this->smarty->assign('permanent_list', $permanent_list);
                $result['catePermanent'] = $this->smarty->fetch("library/merchants_steps_catePermanent.lbi");
            } else {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['login_again'];
            }

            return response()->json($result);
        } /**
         * 删除二级类目
         */
        elseif ($step == 'deleteChildCate_checked') {
            $ct_id = addslashes(trim(request()->input('ct_id', '')));

            $result = ['error' => 0, 'message' => '', 'content' => '', 'cat_id' => ''];

            if ($user_id > 0) {
                $catParent = get_temporarydate_ctId_catParent($ct_id);
                if ($catParent['num'] == 1) {
                    MerchantsDtFile::where('cat_id', $catParent['parent_id'])->delete();
                }

                MerchantsCategoryTemporarydate::where('ct_id', $ct_id)->delete();

                $category_info = get_fine_category_info(0, $user_id);
                $this->smarty->assign('category_info', $category_info);
                $result['content'] = $this->smarty->fetch("library/merchants_cate_checked_list.lbi");

                $permanent_list = get_category_permanent_list($user_id);
                $this->smarty->assign('permanent_list', $permanent_list);
                $result['catePermanent'] = $this->smarty->fetch("library/merchants_steps_catePermanent.lbi");
            } else {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['login_again'];
            }

            return response()->json($result);
        } /**
         * 搜索英文品牌名称
         */
        elseif ($step == 'brandSearch_cn_en') {
            $result = ['err_msg' => '', 'err_no' => 0, 'content' => ''];

            $type = (int)request()->input('type', 0);

            $value = htmlspecialchars(trim(request()->input('value', '')));
            $brand_list = get_merchants_search_brand($value, $type);

            $this->smarty->assign('type', $type);
            $this->smarty->assign('brand_list', $brand_list);

            if ($brand_list) {
                $result['err_no'] = 1;
            }
            $result['type'] = $type;
            $result['content'] = $this->smarty->fetch("library/brank_type_search.lbi");

            return response()->json($result);
        } /**
         * 搜索中文品牌名称
         */
        elseif ($step == 'brandSearch_info') {
            $result = ['err_msg' => '', 'err_no' => 0, 'content' => ''];
            $brand_id = (int)request()->input('brand_id', 0);

            $brand_type = htmlspecialchars(request()->input('brand_type', ''));
            $submit = htmlspecialchars(request()->input('submit', ''));

            $result = get_merchants_search_brand($brand_id, 2, $brand_type, $brand_name, $brand_letter);

            if (!empty($submit)) {
                if ($result) {
                    $result['brand_not'] = $GLOBALS['_LANG']['brand_in'];
                    $result['err_no'] = 1;
                } else {
                    $result['brand_not'] = $GLOBALS['_LANG']['brand_not'];
                    $result['err_no'] = 0;
                }
            }

            $result['brand_type'] = $brand_type;

            return response()->json($result);
        }

        //ajax数据返回 end

        if ($user_id <= 0) {
            return show_message($GLOBALS['_LANG']['steps_UserLogin'], $GLOBALS['_LANG']['UserLogin'], 'user.php');
        }

        $steps_audit = MerchantsShopInformation::where('user_id', $user_id)->value('steps_audit');

        /**
         * 会员已提交申请
         */
        if ($steps_audit == 1) {
            assign_template();

            $position = assign_ur_here();
            $this->smarty->assign('page_title', $position['title']);    // 页面标题
            $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置

            $step = 'stepSubmit';
            $this->smarty->assign('pid_key', 0);  // key值
            $this->smarty->assign('step', $step);  // 协议信息

            $shop_info = $this->merchantCommonService->getMerchantsShopInformation($user_id);

            $shop_info['rz_shopName'] = $shop_info && $shop_info['rz_shopName'] ? str_replace('|', '', $shop_info['rz_shopName']) : '';
            $shop_info['shop_name'] = $this->merchantCommonService->getShopName($user_id, 1); //店铺名称

            $this->smarty->assign('shop_info', $shop_info);

            return $this->smarty->display('merchants_steps.dwt');
        }

        /**
         * 删除商家品牌
         */
        $del = addslashes(request()->input('del', ''));
        if ($del == 'deleteBrand') {
            MerchantsShopBrand::where('bid', $ec_shop_bid)->delete();
        }

        //删除品牌资质证件信息 start
        $b_fid = (int)request()->input('del_bFid', 0);
        if ($b_fid > 0) {
            MerchantsShopBrandfile::where('b_fid', $b_fid)->delete();
        }
        //删除品牌资质证件信息 end

        $fid = MerchantsStepsFields::where('user_id', $user_id)->value('fid');
        $step = addslashes(request()->input('step', ''));
        if (isset($step) && $fid <= 0 && ($step == 'stepTwo' || $step == 'stepThree' || $step == 'stepSubmit')) {
            return dsc_header("Location: merchants.php\n");
        } else {
            if ($fid > 0) {
                if ($step != 'stepThree' && $step != 'stepSubmit') {
                    $step = 'stepTwo'; //跳过协议
                }
            }
        }

        if (!empty($step) && $step == 'stepTwo') {
            $sid = 2;
        } elseif (!empty($step) && $step == 'stepThree') {
            $sid = 3;
        } elseif (!empty($step) && $step == 'stepSubmit') {
            $sid = 4;

            $shop_info = $this->merchantCommonService->getMerchantsShopInformation($user_id);

            $shop_info['rz_shopName'] = $shop_info && $shop_info['rz_shopName'] ? str_replace('|', '', $shop_info['rz_shopName']) : '';

            $this->smarty->assign('shop_info', $shop_info);
        }

        if (!$this->smarty->is_cached('merchants_steps.dwt')) {
            assign_template();

            $position = assign_ur_here();
            $this->smarty->assign('page_title', $position['title']);    // 页面标题
            $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置

            $this->smarty->assign('step', $step);  // 记录流程
            $this->smarty->assign('sid', $sid);  // 记录流程ID

            if ($sid > 1 && $sid < 4) {

                //删除临时表数据
                MerchantsCategoryTemporarydate::where('user_id', $user_id)->where('is_add', 0)->delete();

                /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
                $consignee['country'] = 1;
                $consignee['province'] = 0;
                $consignee['city'] = 0;

                $country_list = get_regions_steps();
                $province_list = get_regions_steps(1, $consignee['country']);
                $city_list = get_regions_steps(2, $consignee['province']);
                $district_list = get_regions_steps(3, $consignee['city']);

                $sn = 0;
                $this->smarty->assign('country_list', $country_list);
                $this->smarty->assign('province_list', $province_list);
                $this->smarty->assign('city_list', $city_list);
                $this->smarty->assign('district_list', $district_list);
                $this->smarty->assign('consignee', $consignee);
                $this->smarty->assign('sn', $sn);

                $process_list = get_root_steps_process_list($sid);
                $process = isset($process_list[$pid_key]) && $process_list[$pid_key] ? $process_list[$pid_key] : [];

                if (!$process_list) {
                    $Location = "merchants_steps.php?step=stepThree&pid_key=" . $pid_key;
                    return dsc_header("Location: " . $Location . "\n");
                }

                //操作品牌流程 start
                if (isset($process['process_title']) && $process['process_title'] == $GLOBALS['_LANG']['07_brand_add']) {

                    //品牌操作 start
                    $this->smarty->assign('b_pidKey', $pid_key);  // 品牌操作
                    $this->smarty->assign('ec_shop_bid', $ec_shop_bid);  // 品牌操作类型 大于0则更新，否则为添加
                    //品牌操作 end

                    if ($brandView == 'brandView') {
                        $this->smarty->assign('pid_key', $pid_key + 1);  // key值
                    } else {
                        $this->smarty->assign('pid_key', $pid_key + 2);  // key值
                    }

                    if ($step == 'stepThree' && $pid_key == 2) {
                        $this->smarty->assign('brandKey', $pid_key + 1);  // key值 添加新品牌
                    }
                } elseif (isset($process['process_title']) && $process['process_title'] == $GLOBALS['_LANG']['new_brand']) {
                    $this->smarty->assign('pid_key', $pid_key - 1);  // key值
                } else {
                    $this->smarty->assign('pid_key', $pid_key + 1);  // key值
                }
                //操作品牌流程 end

                $this->smarty->assign('process', $process);  // 步骤信息
                $this->smarty->assign('brandView', $brandView);

                $this->smarty->assign('choose_process', $GLOBALS['_CFG']['choose_process']);
                if (isset($process['id']) && $process['id'] > 0) {
                    $category_info = get_fine_category_info(0, $user_id); // 详细类目
                    $this->smarty->assign('category_info', $category_info);
                    $this->smarty->assign('category_count', count($category_info));

                    $permanent_list = get_category_permanent_list($user_id); // 一级类目证件
                    $this->smarty->assign('permanent_list', $permanent_list);

                    $steps_title = get_root_merchants_steps_title($process['id'], $user_id);

                    $this->smarty->assign('steps_title', $steps_title);  // 流程表单信息

                    // 添加品牌是否显示
                    $is_brand = MerchantsStepsProcess::where('process_title', '添加品牌')
                        ->where('is_show', 1)->count();
                    $this->smarty->assign('is_brand', $is_brand);
                }
            } elseif ($sid == 1) {
                $merchants_steps = get_root_directory_steps($sid);  //申请流程信息
                $this->smarty->assign('steps', $merchants_steps);  // 协议信息
            }

            /* 页面中的动态内容 */
            assign_dynamic('merchants_steps');
        }

        $ec_brandFirstChar = !empty($brand_letter) ? strtoupper(substr($brand_letter, 0, 1)) : '';

        $this->smarty->assign('brand_name', $brand_name);
        $this->smarty->assign('brand_letter', $brand_letter);
        $this->smarty->assign('ec_brandFirstChar', $ec_brandFirstChar);

        return $this->smarty->display('merchants_steps.dwt');
    }
}
