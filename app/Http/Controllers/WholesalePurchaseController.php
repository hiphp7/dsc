<?php

namespace App\Http\Controllers;

use App\Libraries\Image;
use App\Models\TemporaryFiles;
use App\Models\WholesalePurchase;
use App\Models\WholesalePurchaseGoods;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Article\ArticleCommonService;
use App\Services\Common\CommonService;
use App\Services\Wholesale\CategoryService;
use App\Services\Wholesale\PurchaseService;
use App\Services\Wholesale\WholesaleService;

/**
 * 调查程序
 */
class WholesalePurchaseController extends InitController
{
    protected $categoryService;
    protected $wholesaleService;
    protected $purchaseService;
    protected $timeRepository;
    protected $dscRepository;
    protected $articleCommonService;
    protected $commonService;

    public function __construct(
        CategoryService $categoryService,
        WholesaleService $wholesaleService,
        PurchaseService $purchaseService,
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        ArticleCommonService $articleCommonService,
        CommonService $commonService
    )
    {
        $this->categoryService = $categoryService;
        $this->wholesaleService = $wholesaleService;
        $this->purchaseService = $purchaseService;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->articleCommonService = $articleCommonService;
        $this->commonService = $commonService;
    }

    public function index()
    {
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

        $action = addslashes(trim(request()->input('act', 'list')));
        $action = $action ? $action : 'list';
        $this->smarty->assign('action', $action);

        //求购单列表页
        if ($action == 'list') {

            /* 跳转H5 start */
            $Loaction = 'mobile#/supplier/buy';
            $uachar = $this->dscRepository->getReturnMobile($Loaction);

            if ($uachar) {
                return $uachar;
            }
            /* 跳转H5 end */

            $page_title = lang('wholesale_purchase.ask_buy_order');
            //求购列表
            $is_finished = (int)request()->input('is_finished', -1);
            $keyword = htmlspecialchars(stripcslashes(request()->input('keyword', '')));

            $filter_array = array();
            $filter_array['review_status'] = 1;
            $query_array = array();
            $query_array['act'] = 'list';
            if ($is_finished != -1) {
                $query_array['is_finished'] = $is_finished;
                $filter_array['is_finished'] = $is_finished;
            }
            if ($keyword) {
                $filter_array['keyword'] = $keyword;
                $query_array['keyword'] = $keyword;
            }

            $size = 6;
            $page = (int)request()->input('page', 1);
            $purchase_list = $this->purchaseService->getPurchaseList($filter_array, $size, $page);
            $pager = get_pager('wholesale_purchase.php', $query_array, $purchase_list['record_count'], $page, $size);
            $this->smarty->assign('pager', $pager);
            $this->smarty->assign('purchase_list', $purchase_list['purchase_list']);
            $this->smarty->assign('is_finished', $is_finished);

            $get_wholsale_navigator = $this->wholesaleService->getWholsaleNavigator();
            $this->smarty->assign('get_wholsale_navigator', $get_wholsale_navigator);

            //今日发布
            $date_time = $this->timeRepository->getLocalDate('Y-m-d');
            $time = $this->timeRepository->getGmTime();
            $today_start = $this->timeRepository->getLocalStrtoTime($date_time, $time);
            $today_end = $today_start + 86400;
            $today_count = WholesalePurchase::whereBetween('add_time', [$today_start, $today_end])->count();
            $this->smarty->assign('today_count', $today_count);

            //已成交求购
            $deal_count = WholesalePurchase::where('status', 1)->count();
            $this->smarty->assign('deal_count', $deal_count);
            $this->smarty->assign('buy', $action);
        }

        /* ------------------------------------------------------ */
        //-- 求购单详情页
        /* ------------------------------------------------------ */
        elseif ($action == 'info') {
            $page_title = lang('wholesale_purchase.issue_detail');
            $purchase_id = (int)request()->input('id', 0);

            if (empty($purchase_id)) {
                return dsc_header("Location: ./\n");
            }

            /* 跳转H5 start */
            $Loaction = 'mobile#/supplier/buyinfo?purchase_id=' . $purchase_id;
            $uachar = $this->dscRepository->getReturnMobile($Loaction);

            if ($uachar) {
                return $uachar;
            }
            /* 跳转H5 end */

            $purchase_info = $this->purchaseService->getPurchaseInfo($purchase_id);
            $this->smarty->assign('purchase_info', $purchase_info);
            //是否是商家
            $this->smarty->assign('is_merchant', $this->wholesaleService->getCheckUserIsMerchant($user_id));
        }

        /* ------------------------------------------------------ */
        //-- 求购单发布页
        /* ------------------------------------------------------ */
        elseif ($action == 'release') {
            $page_title = lang('wholesale_purchase.issue_buy');
            if (empty($user_id) || !$this->wholesaleService->getCheckUserIsMerchant($user_id)) {
                return show_message(lang('wholesale_purchase.not_seller_issue'), lang('wholesale_purchase.go_enter'), 'merchants.php', 'info');
            }

            $get_wholsale_navigator = $this->wholesaleService->getWholsaleNavigator();
            $this->smarty->assign('get_wholsale_navigator', $get_wholsale_navigator);

            $time = $this->timeRepository->getGmTime();
            $now = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $time);
            $this->smarty->assign('now', $now);
            $this->smarty->assign('country_list', get_regions());
            $this->smarty->assign('province_list', get_regions(1, 1));
        }

        /* ------------------------------------------------------ */
        //-- 求购单提交
        /* ------------------------------------------------------ */
        elseif ($action == 'do_release') {

            $end_time = request()->input('end_time', '');
            $end_time = $end_time ? strtotime($end_time) : gmtime();
            //求购数据
            $data = array();
            $data['user_id'] = $user_id;

            $data['subject'] = addslashes(trim(request()->input('subject', '')));
            $data['type'] = (int)request()->input('type', 0);
            $data['contact_name'] = addslashes(trim(request()->input('contact_name', '')));
            $data['contact_gender'] = addslashes(trim(request()->input('contact_gender', '')));
            $data['contact_phone'] = addslashes(trim(request()->input('contact_phone', '')));
            $data['contact_email'] = addslashes(trim(request()->input('contact_email', '')));
            $data['add_time'] = gmtime();
            $data['end_time'] = $end_time;
            $data['need_invoice'] = (int)request()->input('need_invoice', 0);
            $data['invoice_tax_rate'] = addslashes(trim(request()->input('invoice_tax_rate', '')));
            $data['consignee_address'] = addslashes(trim(request()->input('consignee_address', '')));
            $data['description'] = addslashes(trim(request()->input('description', '')));
            //处理收货地区
            $consignee_region = 0;
            if (request()->exists('district')) {
                $consignee_region = (int)request()->input('district', 0);
            } elseif (request()->exists('city')) {
                $consignee_region = (int)request()->input('city', 0);
            } elseif (request()->exists('province')) {
                $consignee_region = (int)request()->input('province', 0);
            } elseif (request()->exists('country')) {
                $consignee_region = (int)request()->input('country', 0);
            }

            $data['consignee_region'] = $consignee_region;

            $purchase_id = WholesalePurchase::insertGetId($data);

            //保存求购
            if ($purchase_id > 0) {

                //商品数据
                if (request()->has('goods_name')) {
                    $goods_name = request()->input('goods_name', []);
                    $cat_id = request()->input('cat_id', []);
                    $goods_number = request()->input('goods_number', []);
                    $goods_price = request()->input('goods_price', []);
                    $remarks = request()->input('remarks', []);
                    $pictures = request()->input('pictures', []);

                    for ($i = 0; $i < count($goods_name); $i++) {
                        $row = array();
                        $row['purchase_id'] = $purchase_id;
                        $row['goods_name'] = $goods_name[$i] ?? '';

                        $row['cat_id'] = $cat_id[$i] ?? 0;
                        $row['goods_number'] = $goods_number[$i] ?? 0;
                        $row['goods_price'] = isset($goods_price[$i]) ? floatval($goods_price[$i]) : 0;
                        $row['remarks'] = isset($remarks[$i]) ? trim($remarks[$i]) : '';
                        //处理图片
                        if (isset($pictures) && !empty($pictures[$i])) {
                            $files = trim($pictures[$i]);
                            $goods_img = $this->wholesaleService->moveTemporaryFiles($files, 'data/purchase');
                            $row['goods_img'] = serialize($goods_img);
                        }

                        WholesalePurchaseGoods::insert($row);
                    }
                }

                return show_message(lang('wholesale_purchase.buy_issue_succeed'), lang('wholesale_purchase.go_home'), 'wholesale_purchase.php?act=list', 'info');
            } else {
                return show_message(lang('wholesale_purchase.buy_issue_failed'), lang('wholesale_purchase.go_page_back'), 'javascript:history.go(-1);', 'info');
            }
        }

        /* ------------------------------------------------------ */
        //-- 求购单发布页图片上传
        /* ------------------------------------------------------ */
        elseif ($action == 'upload_pic') {
            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

            $result = array('error' => 0, 'message' => '', 'id' => '', 'path' => '');
            $type = "purchase"; //图片类型
            if ($_FILES['file']['tmp_name'] != '' && $_FILES['file']['tmp_name'] != 'none') {
                $dir = "temporary_files/$type";
                $path = $image->upload_image($_FILES['file'], $dir);

                //插入数据库
                $data = array();
                $data['type'] = $type;
                $data['path'] = $path;
                $data['add_time'] = gmtime();
                $data['identity'] = 0; //会员
                $data['user_id'] = $user_id; //会员id
                $id = TemporaryFiles::insertGetId($data);

                //返回数据
                $result['id'] = $id;
                $result['path'] = get_image_path($path);
                //上传图片到oss
                $this->dscRepository->getOssAddFile([$path]);
            } else {
                $result['error'] = '1';
                $result['message'] = lang('wholesale_purchase.upload_failed');
            }

            return response()->json($result);
        }

        $cat_list = $this->categoryService->getCategoryList();
        $this->smarty->assign('cat_list', $cat_list);

        //页面基本信息
        assign_template();
        $position = assign_ur_here(0, $page_title);
        $this->smarty->assign('page_title', $position['title']);    // 页面标题
        $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置

        $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助

        return $this->smarty->display('wholesale_purchase.dwt');
    }
}
