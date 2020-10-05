<?php

namespace App\Modules\Seller\Controllers;

use App\Exports\AdsenseExport;
use App\Repositories\Common\DscRepository;
use App\Services\Order\OrderCommonService;
use Maatwebsite\Excel\Facades\Excel;

/**
 * 站外JS投放的统计程序
 */
class AdsenseController extends InitController
{
    protected $dscRepository;
    protected $orderCommonService;

    public function __construct(
        DscRepository $dscRepository,
        OrderCommonService $orderCommonService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->orderCommonService = $orderCommonService;
    }

    public function index()
    {
        load_helper('order');

        $this->dscRepository->helpersLang('ads', 'seller');

        /* act操作项的初始化 */
        $act = addslashes(trim(request()->input('act', 'list')));
        $act = $act ? $act : 'list';

        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['06_stats']);

        $this->smarty->assign('menu_select', ['action' => '06_stats', 'current' => 'z_clicks_stats']);

        /*------------------------------------------------------ */
        //-- 站外投放广告的统计
        /*------------------------------------------------------ */
        if ($act == 'list' || $act == 'download') {
            admin_priv('ad_manage');

            $this->smarty->assign('current', 'adsense_list');

            if ($act == 'download') {
                $filename = 'ad_statistics';
                return Excel::download(new AdsenseExport, $filename . '.xlsx');
            } else {
                $ads_stats = $this->orderCommonService->getAdsStats();
                $this->smarty->assign('ads_stats', $ads_stats);

                /* 站外JS投放商品的统计数据 */
                $goods_stats = $this->orderCommonService->getGoodsStats();
                $this->smarty->assign('goods_stats', $goods_stats);

                /* 赋值给模板 */
                $this->smarty->assign('action_link', ['href' => 'ads.php?act=list', 'text' => $GLOBALS['_LANG']['ad_list'], 'class' => 'icon-reply']);
                $this->smarty->assign('action_link2', ['href' => 'adsense.php?act=download', 'text' => $GLOBALS['_LANG']['download_ad_statistics'], 'class' => 'icon-download-alt']);
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['adsense_js_stats']);
                $this->smarty->assign('lang', $GLOBALS['_LANG']);

                /* 显示页面 */
                return $this->smarty->display('adsense.dwt');
            }
        }
    }
}
