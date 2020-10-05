<?php

namespace App\Http\Controllers;

use App\Models\CollectStore;
use App\Models\PresaleActivity;
use App\Models\SellerShopinfo;
use App\Repositories\Common\DscRepository;
use App\Services\Article\ArticleCommonService;
use App\Services\Comment\CommentService;
use App\Services\Goods\GoodsService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\User\UserCommonService;

/**
 * 购物流程
 */
class CategoryDiscussController extends InitController
{
    protected $areaService;
    protected $goodsService;
    protected $merchantCommonService;
    protected $commentService;
    protected $userCommonService;
    protected $config;
    protected $articleCommonService;
    protected $dscRepository;

    public function __construct(
        GoodsService $goodsService,
        MerchantCommonService $merchantCommonService,
        CommentService $commentService,
        UserCommonService $userCommonService,
        ArticleCommonService $articleCommonService,
        DscRepository $dscRepository
    )
    {
        $this->goodsService = $goodsService;
        $this->merchantCommonService = $merchantCommonService;
        $this->commentService = $commentService;
        $this->userCommonService = $userCommonService;
        $this->config = $this->config();
        $this->articleCommonService = $articleCommonService;
        $this->dscRepository = $dscRepository;
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

        $user_id = session('user_id', 0);

        //正则去掉js代码
        $preg = "/<script[\s\S]*?<\/script>/i";

        $id = strtolower(request()->input('id', 0));
        $id = !empty($id) ? preg_replace($preg, "", stripslashes($id)) : 0;

        if (empty($id)) {
            /* 如果ID为0，则返回首页 */
            return redirect("/");
        }

        $goods_id = intval($id);


        $history_goods = get_history_goods($goods_id, $warehouse_id, $area_id, $area_city);
        $this->smarty->assign('history_goods', $history_goods);                                   // 商品浏览历史

        $where = [
            'goods_id' => $goods_id,
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city
        ];
        $goodsInfo = $this->goodsService->getGoodsInfo($where);

        $goodsInfo['goods_price'] = price_format($goodsInfo['goods_price']);

        //预售商品
        $presale = PresaleActivity::where('goods_id', $goods_id)->get();

        $presale = $presale ? $presale->toArray() : [];

        if ($presale) {
            foreach ($presale as $row) {
                $goodsInfo['goods_url'] = $this->dscRepository->buildUri('presale', ['act' => 'view', 'presaleid' => $row['act_id']]);
            }
            $this->smarty->assign('is_presale', $presale);
        }
        $this->smarty->assign('goodsInfo', $goodsInfo);

        //评分 start
        $comment_all = $this->commentService->getCommentsPercent($goods_id);

        $this->smarty->assign('comment_all', $comment_all);

        //模板缓存
        $cache_id = sprintf('%X', crc32($goods_id . '-' . session('user_rank', 0) . '-' . $this->config['lang']));
        $content = cache()->remember('category_discuss.dwt.' . $cache_id, $this->config['cache_time'], function () use ($goods_id, $goodsInfo, $user_id) {
            $this->smarty->assign('user_info', $this->userCommonService->getUserDefault(session('user_id')));
            $goods = $goodsInfo;

            //是否收藏店铺
            $rec_id = CollectStore::where('user_id', $user_id)->where('ru_id', $goods['user_id'])->value('rec_id');
            if ($rec_id > 0) {
                $goods['error'] = '1';
            } else {
                $goods['error'] = '2';
            }

            $this->smarty->assign('goods', $goods);

            if ($goods['user_id'] > 0) {
                $merchants_goods_comment = $this->commentService->getMerchantsGoodsComment($goods['user_id']); //商家所有商品评分类型汇总
                $this->smarty->assign('merch_cmt', $merchants_goods_comment);
            }

            if ($this->config['customer_service'] == 0) {
                $goods_user_id = 0;
            } else {
                $goods_user_id = $goods['user_id'];
            }

            $basic_info = get_shop_info_content($goods_user_id);

            /*  @author-bylu 判断当前商家是否允许"在线客服" start */
            $shop_information = $this->merchantCommonService->getShopName($goods_user_id);

            //判断当前商家是平台,还是入驻商家 bylu
            if ($goods_user_id == 0) {
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
            $this->smarty->assign('kf_appkey', $basic_info['kf_appkey']); //应用appkey;
            $this->smarty->assign('im_user_id', 'dsc' . session('user_id')); //登入用户ID;
            /*  @author-bylu  end */

            $rec_id = CollectStore::where('user_id', $user_id)->where('ru_id', $goods_user_id)->value('rec_id');
            if ($rec_id) {
                $this->smarty->assign('is_collected', true);
            }

            $this->smarty->assign('goods_id', $goods_id);

            assign_template();
            $position = assign_ur_here($goodsInfo['cat_id'], $goodsInfo['goods_name'], [], '', $goodsInfo['user_id']);
            $this->smarty->assign('page_title', $position['title']);    // 页面标题
            $this->smarty->assign('ur_here', $position['ur_here']);  // 当前位置

            /* meta information */
            $this->smarty->assign('keywords', htmlspecialchars($this->config['shop_keywords']));
            $this->smarty->assign('description', htmlspecialchars($this->config['shop_desc']));
            $this->smarty->assign('flash_theme', $this->config['flash_theme']);  // Flash轮播图片模板

            $this->smarty->assign('feed_url', ($this->config['rewrite'] == 1) ? 'feed.xml' : 'feed.php'); // RSS URL

            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助

            /* 验证码相关设置 */
            if ((intval($this->config['captcha']) & CAPTCHA_COMMENT) && gd_version() > 0) {
                $this->smarty->assign('enabled_captcha', 1);
                $this->smarty->assign('rand', mt_rand());
            }

            $this->smarty->assign('shop_notice', $this->config['shop_notice']);       // 商店公告

            $discuss_list = get_discuss_all_list($goods_id);
            $this->smarty->assign('discuss_list', $discuss_list);

            $t_count = get_discuss_type_count($goods_id, 1); //讨论帖总数
            $w_count = get_discuss_type_count($goods_id, 2); //问答帖总数
            $q_count = get_discuss_type_count($goods_id, 3); //圈子帖总数
            $s_count = get_commentImg_count($goods_id); //晒单帖总数
            $all_count = $t_count + $w_count + $q_count + $s_count; //帖子总数

            $this->smarty->assign('all_count', $all_count);
            $this->smarty->assign('t_count', $t_count);
            $this->smarty->assign('w_count', $w_count);
            $this->smarty->assign('q_count', $q_count);
            $this->smarty->assign('s_count', $s_count);

            //热门话题
            $discuss_hot = get_discuss_all_list($goods_id, 0, 1, 10, 0, 'dis_browse_num');
            $this->smarty->assign('hot_list', $discuss_hot);

            $this->smarty->assign('user_id', $user_id);
            return $this->smarty->display('category_discuss.dwt');
        });

        return $content;
    }
}
