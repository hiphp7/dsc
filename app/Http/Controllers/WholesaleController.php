<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Article\ArticleCommonService;
use App\Services\Common\CommonService;
use App\Services\Wholesale\CategoryService;
use App\Services\Wholesale\GoodsService;
use App\Services\Wholesale\PurchaseService;
use App\Services\Wholesale\WholesaleService;

/**
 * 调查程序
 */
class WholesaleController extends InitController
{
    protected $baseRepository;
    protected $timeRepository;
    protected $categoryService;
    protected $wholesaleService;
    protected $goodsService;
    protected $purchaseService;
    protected $dscRepository;
    protected $articleCommonService;
    protected $commonService;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        CategoryService $categoryService,
        WholesaleService $wholesaleService,
        GoodsService $goodsService,
        PurchaseService $purchaseService,
        DscRepository $dscRepository,
        ArticleCommonService $articleCommonService,
        CommonService $commonService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->categoryService = $categoryService;
        $this->wholesaleService = $wholesaleService;
        $this->goodsService = $goodsService;
        $this->purchaseService = $purchaseService;
        $this->dscRepository = $dscRepository;
        $this->articleCommonService = $articleCommonService;
        $this->commonService = $commonService;
    }

    public function index()
    {
        $user_id = session('user_id', 0);

        //访问权限
        $wholesaleUse = $this->commonService->judgeWholesaleUse($user_id);

        if ($wholesaleUse === false) {
            if ($user_id) {
                return show_message($GLOBALS['_LANG']['not_seller_user']);
            } else {
                return show_message($GLOBALS['_LANG']['not_login_user']);
            }
        }

        /* 跳转H5 start */
        $Loaction = 'mobile#/supplier';
        $uachar = $this->dscRepository->getReturnMobile($Loaction);

        if ($uachar) {
            return $uachar;
        }
        /* 跳转H5 end */

        /* ------------------------------------------------------ */
        //-- act 操作项的初始化
        /* ------------------------------------------------------ */
        $act = addslashes(trim(request()->input('act', 'index')));
        $act = $act ? $act : 'index';

        //判断是否是商家
        $seller_id = AdminUser::where('ru_id', $user_id)->value('user_id');

        $this->smarty->assign('seller_id', $seller_id);
        $this->smarty->assign('cfg', $GLOBALS['_CFG']);

        /* ------------------------------------------------------ */
        //-- 批发活动列表
        /* ------------------------------------------------------ */
        if ($act == 'index') {

            /* 模板赋值 */
            assign_template();
            $position = assign_ur_here();
            $this->smarty->assign('page_title', $position['title']);    // 页面标题
            $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置
            $this->smarty->assign('index', $act);
            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助

            $cat_list = $this->categoryService->getCategoryList();
            $this->smarty->assign('cat_list', $cat_list);

            $wholesale_limit = $this->wholesaleService->getWholesaleLimit();
            $this->smarty->assign('wholesale_limit', $wholesale_limit);

            $wholesale_cat = $this->goodsService->getWholesaleCatGoods();
            $this->smarty->assign('wholesale_cat', $wholesale_cat);

            $purchase_list = $this->purchaseService->getPurchaseList();
            $this->smarty->assign('purchase', $purchase_list['purchase_list']);

            $get_wholsale_navigator = $this->wholesaleService->getWholsaleNavigator();
            $this->smarty->assign('get_wholsale_navigator', $get_wholsale_navigator);

            $wholesale_ad = '';
            $wholesale_cat_ad = '';

            /* 广告位 */
            for ($i = 1; $i <= $GLOBALS['_CFG']['auction_ad']; $i++) {
                $wholesale_ad .= "'wholesale_ad" . $i . ","; //轮播图
                $wholesale_cat_ad .= "'wholesale_cat_ad" . $i . ","; //楼层图
            }

            $this->smarty->assign('wholesale_ad', $wholesale_ad);
            $this->smarty->assign('wholesale_cat_ad', $wholesale_cat_ad);


            assign_dynamic('wholesale');

            /* 显示模板 */
            return $this->smarty->display('wholesale_list.dwt');
        }
    }
}
