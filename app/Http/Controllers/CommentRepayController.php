<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Repositories\Common\DscRepository;
use App\Services\Article\ArticleCommonService;
use App\Services\Comment\CommentService;
use App\Services\Goods\GoodsService;
use App\Services\Order\OrderGoodsService;

/**
 * 晒单页
 */
class CommentRepayController extends InitController
{
    protected $commentService;
    protected $goodsService;
    protected $dscRepository;
    protected $config;
    protected $articleCommonService;
    protected $orderGoodsService;

    public function __construct(
        CommentService $commentService,
        GoodsService $goodsService,
        DscRepository $dscRepository,
        ArticleCommonService $articleCommonService,
        OrderGoodsService $orderGoodsService
    )
    {
        $this->commentService = $commentService;
        $this->goodsService = $goodsService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->config();
        $this->articleCommonService = $articleCommonService;
        $this->orderGoodsService = $orderGoodsService;
    }

    public function index()
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

        load_helper('goods', 'admin');

        $this->dscRepository->helpersLang('user');

        $comment_id = (int)request()->input('comment_id', 0);
        $act = addslashes(request()->input('act', ''));

        /*------------------------------------------------------ */
        //-- 商品回复类表
        /*------------------------------------------------------ */
        if ($act == 'repay') {

            $cache_id = $comment_id . '-' . $warehouse_id . '-' . $area_id . '-' . $area_city . '-' . session('user_rank') . '-' . $this->config['lang'];;
            $cache_id = sprintf('%X', crc32($cache_id));

            $content = cache()->remember('comment_repay.dwt.' . $cache_id, $this->config['cache_time'], function () use ($comment_id, $warehouse_id, $area_id, $area_city) {
                /* 初始化分页信息 */
                assign_template();

                $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());        // 网店帮助
                $this->smarty->assign('data_dir', DATA_DIR);   // 数据目录
                $this->smarty->assign('lang', $GLOBALS['_LANG']);

                $comment = $this->commentService->getCommentInfo($comment_id);
                $goods_id = $comment ? $comment['id_value'] : 0;

                //评分 start
                $comment_all = $this->commentService->getCommentsPercent($goods_id);

                $this->smarty->assign('comment_all', $comment_all);

                $categories_pro = get_category_tree_leve_one();
                $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版

                $where = [
                    'goods_id' => $goods_id,
                    'warehouse_id' => $warehouse_id,
                    'area_id' => $area_id,
                    'area_city' => $area_city
                ];
                $goodsInfo = $this->goodsService->getGoodsInfo($where);

                $goodsInfo['goods_price'] = price_format($goodsInfo['goods_price']);
                $this->smarty->assign('goodsInfo', $goodsInfo);

                if (empty($comment_id)) {
                    return dsc_header("Location: ./\n");
                }
                if (empty($comment)) {
                    return dsc_header("location: ./\n");
                }

                $user_picture = Users::where('user_id', $comment['user_id'])->value('user_picture');
                $this->smarty->assign('user_picture', $user_picture);

                $comment['add_time'] = local_date($this->config['time_format'], $comment['add_time']);
                $this->smarty->assign('comment', $comment);

                $buy_goods = $this->orderGoodsService->getUserBuyGoodsOrder($comment['id_value'], $comment['user_id'], $comment['order_id']);
                $this->smarty->assign('buy_goods', $buy_goods);

                $where = [
                    'goods_id' => $comment['id_value'],
                    'comment_id' => $comment['comment_id']
                ];
                $img_list = $this->commentService->getCommentImgList($where);
                $this->smarty->assign('img_list', $img_list);

                $position = assign_ur_here($goodsInfo['cat_id'], $goodsInfo['goods_name'], [$comment['content']], $goodsInfo['goods_url']);
                $this->smarty->assign('ip', $this->dscRepository->dscIp());
                $this->smarty->assign('page_title', $position['title']); // 页面标题
                $this->smarty->assign('ur_here', $position['ur_here']);

                $type = 0;
                $reply_page = 1;
                $libType = 1;
                $size = 10;
                $reply = $this->commentService->getReplyList($comment['id_value'], $comment['comment_id'], $type, $reply_page, $libType, $size);
                $this->smarty->assign('reply', $reply);

                $this->smarty->assign('now_time', gmtime());           // 当前系统时间

                return $this->smarty->display('comment_repay.dwt');
            });

            return $content;
        }
    }
}
