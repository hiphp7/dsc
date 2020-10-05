<?php

namespace App\Modules\Admin\Controllers;

use App\Repositories\Common\DscRepository;
use App\Services\Other\AdsenseManageService;

/**
 * 站外JS投放的统计程序
 */
class AdsenseController extends InitController
{
    protected $adsenseManageService;
    protected $dscRepository;

    public function __construct(
        AdsenseManageService $adsenseManageService,
        DscRepository $dscRepository
    )
    {
        $this->adsenseManageService = $adsenseManageService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        load_helper('order');

        $this->dscRepository->helpersLang('ads', 'admin');

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }

        /*------------------------------------------------------ */
        //-- 站外投放广告的统计
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list' || $_REQUEST['act'] == 'download') {
            admin_priv('ad_manage');

            $ads_stats = $this->adsenseManageService->getAdsStats();
            $this->smarty->assign('ads_stats', $ads_stats);

            /* 站外JS投放商品的统计数据 */
            $goods_stats = $this->adsenseManageService->getGoodsStats();
            if ($_REQUEST['act'] == 'download') {

                header("Content-type: application/vnd.ms-excel; charset=utf-8");
                header("Content-Disposition: attachment; filename=ad_statistics.xls");
                $data = $GLOBALS['_LANG']['adsense_name'] . "\t" .
                    $GLOBALS['_LANG']['cleck_referer'] . "\t" .
                    $GLOBALS['_LANG']['click_count'] . "\t" .
                    $GLOBALS['_LANG']['confirm_order'] . "\t" .
                    $GLOBALS['_LANG']['gen_order_amount'] . "\n";

                $res = array_merge($goods_stats, $ads_stats);
                foreach ($res as $row) {
                    $data .= "$row[ad_name]\t$row[referer]\t$row[clicks]\t$row[order_confirm]\t$row[order_num]\n";
                }
                echo dsc_iconv(EC_CHARSET, 'GB2312', $data);
                exit;
            }

            $this->smarty->assign('goods_stats', $goods_stats);

            /* 赋值给模板 */
            $this->smarty->assign('action_link', ['href' => 'ads.php?act=list', 'text' => $GLOBALS['_LANG']['ad_list']]);
            $this->smarty->assign('action_link2', ['href' => 'adsense.php?act=download', 'text' => $GLOBALS['_LANG']['download_ad_statistics']]);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['adsense_js_stats']);
            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('full_page', 1);

            /* 显示页面 */
            return $this->smarty->display('adsense.dwt');
        }
    }
}
