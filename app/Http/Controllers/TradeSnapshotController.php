<?php

namespace App\Http\Controllers;

use App\Models\SellerShopinfo;
use App\Models\TradeSnapshot;
use App\Repositories\Common\DscRepository;
use App\Services\Article\ArticleCommonService;
use App\Services\Comment\CommentService;
use App\Services\Merchant\MerchantCommonService;

/**
 * 会员中心
 */
class TradeSnapshotController extends InitController
{
    protected $merchantCommonService;
    protected $commentService;
    protected $config;
    protected $articleCommonService;
    protected $dscRepository;

    public function __construct(
        MerchantCommonService $merchantCommonService,
        CommentService $commentService,
        ArticleCommonService $articleCommonService,
        DscRepository $dscRepository
    )
    {
        $this->merchantCommonService = $merchantCommonService;
        $this->commentService = $commentService;
        $this->config = $this->config();
        $this->articleCommonService = $articleCommonService;
        $this->dscRepository = $dscRepository;
    }


    public function index()
    {
        /* 过滤 XSS 攻击和SQL注入 */
        get_request_filter();

        $user_id = session('user_id', 0);
        $action = addslashes(request()->input('act', 'default'));
        $action = $action ? $action : 'default';
        if ($user_id == 0) {
            $user_id = (int)request()->input('user_id', 0);
        }
        //交易快照
        if ($action == 'trade') {
            assign_template();
            $tradeId = (int)request()->input('tradeId', 0);
            $snapshot = request()->exists('snapshot') ? true : false;

            $goods = TradeSnapshot::where('trade_id', $tradeId)
                ->where('user_id', $user_id)
                ->first();
            $goods = $goods ? $goods->toArray() : [];

            if (empty($goods)) {
                redirect("/");
            }

            //格式化时间戳
            $goods['snapshot_time'] = local_date('Y-m-d H:i:s', $goods['snapshot_time']);

            if ($goods['ru_id'] > 0) {
                $merchants_goods_comment = $this->commentService->getMerchantsGoodsComment($goods['ru_id']); //商家所有商品评分类型汇总
                $this->smarty->assign('merch_cmt', $merchants_goods_comment);
            }

            // 判断当前商家是否允许"在线客服" start
            $shop_information = $this->merchantCommonService->getShopName($goods['ru_id']);

            //判断当前商家是平台,还是入驻商家
            if ($goods['ru_id'] == 0) {
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

            if ($this->config['open_oss'] == 1) {
                $bucket_info = $this->dscRepository->getBucketInfo();
                $endpoint = $bucket_info['endpoint'];
            } else {
                $endpoint = url('/');
            }

            $desc_preg = get_goods_desc_images_preg($endpoint, $goods['goods_desc']);
            $goods['goods_desc'] = $desc_preg['goods_desc'];

            $goods['goods_img'] = empty($goods['goods_img']) ? '' : get_image_path($goods['goods_img']);

            $this->smarty->assign('shop_information', $shop_information);

            $this->smarty->assign('page_title', $goods['goods_name']);  // 页面标题
            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());     // 网店帮助
            $this->smarty->assign('snapshot', $snapshot);
            $this->smarty->assign('goods', $goods);
            return $this->smarty->display('trade_snapshot.dwt');
        }
    }
}
