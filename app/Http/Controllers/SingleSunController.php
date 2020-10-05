<?php

namespace App\Http\Controllers;

use App\Libraries\CaptchaVerify;
use App\Libraries\Pager;
use App\Models\CollectStore;
use App\Models\DiscussCircle;
use App\Models\SellerShopinfo;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Article\ArticleCommonService;
use App\Services\Comment\CommentService;
use App\Services\Goods\GoodsService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\User\UserCommonService;

/**
 * 晒单页
 */
class SingleSunController extends InitController
{
    protected $commentService;
    protected $goodsService;
    protected $baseRepository;
    protected $dscRepository;
    protected $merchantCommonService;
    protected $userCommonService;
    protected $config;
    protected $articleCommonService;

    public function __construct(
        CommentService $commentService,
        GoodsService $goodsService,
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService,
        UserCommonService $userCommonService,
        ArticleCommonService $articleCommonService
    )
    {
        $this->commentService = $commentService;
        $this->goodsService = $goodsService;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->userCommonService = $userCommonService;
        $this->config = $this->config();
        $this->articleCommonService = $articleCommonService;
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

        /* 初始化分页信息 */
        $page = (int)request()->input('page', 1);
        $act = addslashes(request()->input('act', ''));
        $affiliate = $this->config['affiliate'] ? unserialize($this->config['affiliate']) : [];
        $this->smarty->assign('affiliate', $affiliate);

        assign_template();

        $did = (int)request()->input('did', 0);
        //dis_type = 4 晒单
        $dis_type = (int)request()->input('dis_type', 0);

        $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());        // 网店帮助
        $this->smarty->assign('data_dir', DATA_DIR);   // 数据目录
        $this->smarty->assign('lang', $GLOBALS['_LANG']);

        $history_goods = get_history_goods(0, $warehouse_id, $area_id, $area_city);
        $this->smarty->assign('history_goods', $history_goods);                                   // 商品浏览历史

        if ($dis_type == 4) {
            $comment = $this->commentService->getCommentInfo($did);
            $goods_id = isset($comment['id_value']) ? $comment['id_value'] : 0;
        } else {
            $goods_id = DiscussCircle::where('dis_id', $did)
                ->where('review_status', 3)
                ->value('goods_id');
        }

        $where = [
            'goods_id' => $goods_id,
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city
        ];
        $goodsInfo = $this->goodsService->getGoodsInfo($where);

        if (!empty($goodsInfo)) {
            $goodsInfo['goods_price'] = price_format($goodsInfo['goods_price']);
        }
        $this->smarty->assign('goodsInfo', $goodsInfo);

        //评分 start
        $comment_all = $this->commentService->getCommentsPercent($goods_id);

        $this->smarty->assign('comment_all', $comment_all);

        /**
         * 评论
         */
        if ($act == 'check_comm') {
            $dis_id = (int)request()->input('dis_id', 0);
            $quote_id = (int)request()->input('quote_id', 0);
            $nick_user = (int)request()->input('nick_user', 0);

            $content = htmlspecialchars(request()->input('comment_content', ''));

            $user_name = session('user_name');
            $user_id = session('user_id');
            $addtime = gmtime();

            $res = ['error' => 0, 'err_msg' => '', 'dis_id' => $dis_id];

            if (empty(session('user_id'))) {
                $res['error'] = 2;
                return response()->json($res);
            }

            if (session('user_id') == $nick_user) {
                $err_msg = $GLOBALS['_LANG']['comment_self'];

                $res['error'] = 1;
                $res['err_msg'] = $err_msg;
                return response()->json($res);
            }

            $count = DiscussCircle::where('dis_text', $content)->count();
            if ($count) {
                $err_msg = $GLOBALS['_LANG']['repeat_comment'];

                $res['error'] = 1;
                $res['err_msg'] = $err_msg;
                return response()->json($res);
            }

            $count = DiscussCircle::where('parent_id', $dis_id)->where('user_id', session('user_id'))->count();
            if ($count > 3) {
                $err_msg = $GLOBALS['_LANG']['More_comment'];

                $res['error'] = 1;
                $res['err_msg'] = $err_msg;
                return response()->json($res);
            }

            $other = [
                'goods_id' => 0,
                'parent_id' => $dis_id,
                'quote_id' => $quote_id,
                'user_id' => $user_id,
                'user_name' => $user_name,
                'dis_text' => $content,
                'add_time' => $addtime
            ];

            $dis_id = DiscussCircle::insertGetId($other);
            if ($dis_id) {
                $err_msg = $GLOBALS['_LANG']['comment_Success'];

                $res['error'] = 1;
                $res['err_msg'] = $err_msg;
                return response()->json($res);
            } else {
                $err_msg = $GLOBALS['_LANG']['comment_fail'];

                $res['error'] = 1;
                $res['err_msg'] = $err_msg;
                return response()->json($res);
            }
        }

        /* ------------------------------------------------------ */
        //-- 讨论圈详情页
        /* ------------------------------------------------------ */
        elseif ($act == 'discuss_show') {
            $this->smarty->assign('user_info', $this->userCommonService->getUserDefault(session('user_id')));

            //是否收藏店铺
            $goodsInfo['user_id'] = isset($goodsInfo['user_id']) ? $goodsInfo['user_id'] : 0;
            $rec_id = CollectStore::where('user_id', session('user_id'))->where('ru_id', $goodsInfo['user_id'])->value('rec_id');

            if ($rec_id > 0) {
                $goodsInfo['error'] = '1';
            } else {
                $goodsInfo['error'] = '2';
            }

            if ($goodsInfo['user_id'] > 0) {
                $merchants_goods_comment = $this->commentService->getMerchantsGoodsComment($goodsInfo['user_id']); //商家所有商品评分类型汇总
                $this->smarty->assign('merch_cmt', $merchants_goods_comment);
            }

            if ($this->config['customer_service'] == 0) {
                $goods_user_id = 0;
            } else {
                $goods_user_id = $goodsInfo['user_id'];
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

            $categories_pro = get_category_tree_leve_one();
            $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版

            $size = 5;
            $cache_id = $did . '-' . session('user_rank', 0) . '-' . $this->config['lang'];
            $cache_id = sprintf('%X', crc32($cache_id));
            if (!$this->smarty->is_cached('goods_discuss_show.dwt', $cache_id)) {
                if (empty($did)) {
                    return dsc_header("Location: ./\n");
                }

                if ($dis_type == 4) {
                    $where = [
                        'goods_id' => $comment['id_value'],
                        'comment_id' => $comment['comment_id']
                    ];
                    $img_list = $this->commentService->getCommentImgList($where);

                    $user_picture = Users::where('user_id', $comment['user_id'])->value('user_picture');

                    $discuss['user_name'] = $this->encrypt_username($comment['user_name']);
                    $discuss['dis_title'] = $comment['content'];
                    $discuss['dis_id'] = $comment['comment_id'];
                    $discuss['user_id'] = $comment['user_id'];
                    $discuss['add_time'] = local_date($this->config['time_format'], $comment['add_time']);

                    $this->smarty->assign('num', count($img_list));
                    $this->smarty->assign('img_list', $img_list);
                    $this->smarty->assign('photo', $img_list[0]['path_img_thumb']);
                } else {
                    $discuss = DiscussCircle::where('dis_id', $did)->where('parent_id', 0)->first();
                    $discuss = $discuss ? $discuss->toArray() : [];

                    if (empty($discuss)) {
                        return dsc_header("location: ./\n");
                    }

                    if (!empty($discuss) && $discuss['review_status'] != 3) {
                        return show_message($GLOBALS['_LANG']['sorry_single_sun'], $GLOBALS['_LANG']['back_page_up'], '', 'error');
                    }

                    $discuss['add_time'] = local_date($this->config['time_format'], $discuss['add_time']);

                    //上一条
                    $prev = DiscussCircle::where('dis_id', '<', $discuss['dis_id'])
                        ->where('parent_id', 0)
                        ->where('review_status', 3)
                        ->orderBy('dis_id', 'desc');

                    $prev = $this->baseRepository->getToArrayFirst($prev);

                    //下一条
                    $next = DiscussCircle::where('dis_id', '>', $discuss['dis_id'])
                        ->where('parent_id', 0)
                        ->where('review_status', 3)
                        ->orderBy('dis_id', 'desc');

                    $next = $this->baseRepository->getToArrayFirst($next);

                    $res = DiscussCircle::where('dis_id', $did)
                        ->where('review_status', 3)
                        ->where('parent_id', 0)
                        ->with('getUsers');

                    $res = $this->baseRepository->getToArrayFirst($res);

                    $user_picture = $res && $res['get_users'] ? $res['get_users']['user_picture'] : '';

                    //热门话题
                    $discuss_hot = get_discuss_all_list($goodsInfo['goods_id'], 0, 1, 10, 0, 'dis_browse_num', $did);
                    $this->smarty->assign('hot_list', $discuss_hot);
                }
                if (isset($discuss['dis_text']) && $discuss['dis_text']) {
                    $discuss['dis_text'] = $this->dscRepository->getContentImgReplace($discuss['dis_text']);
                }

                //会员昵称
                $info = Users::where('user_id', $discuss['user_id'])->first();
                $info = $info ? $info->toArray() : [];

                if ($info) {
                    $discuss['nick_name'] = !empty($info['nick_name']) ? $this->encrypt_username($info['nick_name']) : $this->encrypt_username($info['username']);
                } else {
                    $discuss['nick_name'] = 'N/A';
                }

                $this->smarty->assign('user_picture', get_image_path($user_picture));

                $position = assign_ur_here($goodsInfo['cat_id'], $goodsInfo['goods_name'], [$discuss['dis_title']], $goodsInfo['goods_url']);
                $this->smarty->assign('ip', $this->dscRepository->dscIp());
                $this->smarty->assign('goods', $goodsInfo);
                $this->smarty->assign('page_title', $position['title']); // 页面标题
                $this->smarty->assign('ur_here', $position['ur_here']);

                $reply_discuss = $this->get_reply_discuss_circle($discuss['dis_id'], $size, $page);
                $this->smarty->assign('reply_discuss', $reply_discuss);

                $this->smarty->assign('discuss', $discuss);
                $this->smarty->assign('act', $act);


                /* 更新点击次数 */
                DiscussCircle::where('dis_id', $did)
                    ->where('parent_id', 0)
                    ->where('review_status', 3)
                    ->increment('dis_browse_num', 1);

                $this->smarty->assign('now_time', gmtime());           // 当前系统时间
            }

            return $this->smarty->display('goods_discuss_show.dwt');
        } /* 插入晒单提交信息 */
        elseif ($act == 'add_discuss') {
            load_helper('transaction');

            $goods_id = (int)request()->input('good_id', 0);
            if (empty($goods_id)) {
                return redirect("/");
            }

            if (empty(session('user_id'))) {
                return dsc_header("Location: user.php\n");
            }
            if (request()->exists('captcha')) {
                /* 验证码检查 */
                if (empty(request()->input('captcha'))) {
                    return show_message($GLOBALS['_LANG']['invalid_captcha'], '', 'category_discuss.php?id=' . $goods_id, 'error');
                }

                $captcha_str = addslashes(request()->input('captcha', ''));
                $verify = app(CaptchaVerify::class);
                $captcha_code = $verify->check($captcha_str, 'captcha_common');

                if (!$captcha_code) {
                    return show_message($GLOBALS['_LANG']['invalid_captcha'], '', 'category_discuss.php?id=' . $goods_id, 'error');
                }
            }

            if (empty(request()->input('referenceType'))) {
                return show_message($GLOBALS['_LANG']['discuss_type'], $GLOBALS['_LANG']['back_page_up'], "category_discuss.php?id=$goods_id", 'error');
            }

            if (empty(request()->input('commentTitle'))) {
                return show_message($GLOBALS['_LANG']['title_Remarks'], $GLOBALS['_LANG']['back_page_up'], "category_discuss.php?id=$goods_id", 'error');
            }

            if (empty(request()->input('content', ''))) {
                return show_message($GLOBALS['_LANG']['content_null'], $GLOBALS['_LANG']['back_page_up'], "category_discuss.php?id=$goods_id", 'error');
            }

            $commentTitle = addslashes(request()->input('commentTitle', ''));
            $content = addslashes(request()->input('content', ''));
            $referenceType = request()->input('referenceType', 1);

            $user_name = Users::where('user_id', session('user_id'))->value('user_name');
            $user_name = $user_name ? $user_name : '';

            $time = gmtime();

            $other = [
                'goods_id' => $goods_id,
                'user_id' => session('user_id'),
                'dis_type' => $referenceType,
                'dis_title' => $commentTitle,
                'dis_text' => $content,
                'add_time' => $time,
                'user_name' => $user_name
            ];
            $dis_id = DiscussCircle::insertGetId($other);

            /* 处理相册图片 */
            if (!empty($dis_id)) {
                $img_desc = addslashes(request()->input('img_desc', ''));
                $img_file = addslashes(request()->input('img_file', ''));
                if (isset($_FILES['img_url']) && !empty($img_desc) && !empty($img_file)) {
                    handle_gallery_image(0, $_FILES['img_url'], $img_desc, $img_file, $dis_id, 1);
                }
                return show_message($GLOBALS['_LANG']['cmt_submit_wait'], $GLOBALS['_LANG']['back_page_up'], "category_discuss.php?id=$goods_id");
            } else {
                return show_message($GLOBALS['_LANG']['Submit_fail'], $GLOBALS['_LANG']['back_page_up'], "category_discuss.php?act=single_sun?id=$goods_id", 'error');
            }
        } /* 插入晒单提交信息 */
        elseif ($act == 'ajax_verify') {
            $error = true;

            $captcha_str = addslashes(request()->input('captcha', ''));
            $rec_id = (int)request()->input('rec_id', 0);

            /* 验证码检查 */
            if ((intval($this->config['captcha']) & CAPTCHA_COMMENT) && gd_version() > 0) {
                $verify = app(CaptchaVerify::class);
                $captcha_code = $verify->check($captcha_str, 'captcha_discuss', $rec_id);

                if (!$captcha_code) {
                    $error = false;
                }
            }
            return json_encode($error);
        }
    }

    private function get_reply_discuss_circle($dis_id, $size = 5, $reply_page = 1)
    {
        $record_count = DiscussCircle::where('parent_id', $dis_id)
            ->where('review_status', 3)
            ->count();

        $pagerParams = [
            'total' => $record_count,
            'listRows' => $size,
            'id' => $dis_id,
            'page' => $reply_page,
            'funName' => 'reply_discuss_gotoPage'
        ];
        $reply_discuss = new Pager($pagerParams);
        $limit = $reply_discuss->limit;
        $pager = $reply_discuss->fpage([0, 4, 5, 6, 9]);

        $res = DiscussCircle::where('parent_id', $dis_id)
            ->where('review_status', 3)
            ->orderBy('add_time', 'desc');

        $start = ($reply_page - 1) * $size;

        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $key => $row) {
                $info = Users::where('user_id', $row['user_id'])->first();
                $info = $info ? $info->toArray() : [];

                $res[$key]['user_picture'] = $info ? $info['user_picture'] : '';
                $res[$key]['add_time'] = local_date($this->config['time_format'], $row['add_time']);
                $res[$key]['quote'] = $this->get_quote_reply($row['quote_id']);

                if ($info) {
                    $res[$key]['nick_name'] = !empty($info['nick_name']) ? $this->encrypt_username($info['nick_name']) : $this->encrypt_username($info['username']);
                } else {
                    $res[$key]['nick_name'] = '';
                }
            }
        }

        return ['list' => $res, 'pager' => $pager, 'record_count' => $record_count, 'size' => $size];
    }

    private function get_quote_reply($quote_id)
    {
        $row = DiscussCircle::where('dis_id', $quote_id)->first();

        $row = $row ? $row->toArray() : [];

        if ($row) {
            $info = Users::where('user_id', $row['user_id'])->first();
            $info = $info ? $info->toArray() : [];

            if ($info) {
                $row['nick_name'] = !empty($info['nick_name']) ? $this->encrypt_username($info['nick_name']) : $this->encrypt_username($info['username']);
            } else {
                $row['nick_name'] = '';
            }
        }

        return $row;
    }

    /**
     *
     * 重定义用户名
     * end
     */
    private function encrypt_username($username)
    {
        $username_start = mb_substr($username, 0, 1, 'utf-8');
        $username_end = mb_substr($username, -1, 1, 'utf-8');
        $username_new = $username_start . '****' . $username_end;
        return $username_new;
    }
}
