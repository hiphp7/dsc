<?php

namespace App\Http\Controllers;

use App\Libraries\QRcode;
use App\Models\OrderInfo;
use App\Models\Payment;
use App\Models\Region;
use App\Models\SellerShopinfo;
use App\Models\Shipping;
use App\Models\UserAddress;
use App\Models\Users;
use App\Models\ZcFocus;
use App\Models\ZcGoods;
use App\Models\ZcProgress;
use App\Models\ZcProject;
use App\Models\ZcTopic;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Article\ArticleCommonService;
use App\Services\Cart\CartCommonService;
use App\Services\Common\AreaService;
use App\Services\CrowdFund\CrowdFundService;
use App\Services\Flow\FlowUserService;
use App\Services\Order\OrderCommonService;
use App\Services\User\UserAddressService;
use Illuminate\Support\Str;

/**
 * 众筹商品
 */
class CrowdfundingController extends InitController
{
    protected $areaService;
    protected $crowdFundService;
    protected $dscRepository;
    protected $cartCommonService;
    protected $articleCommonService;
    protected $userAddressService;
    protected $baseRepository;
    protected $flowUserService;
    protected $orderCommonService;

    public function __construct(
        AreaService $areaService,
        CrowdFundService $crowdFundService,
        DscRepository $dscRepository,
        CartCommonService $cartCommonService,
        ArticleCommonService $articleCommonService,
        UserAddressService $userAddressService,
        BaseRepository $baseRepository,
        FlowUserService $flowUserService,
        OrderCommonService $orderCommonService
    )
    {
        $this->areaService = $areaService;
        $this->crowdFundService = $crowdFundService;
        $this->dscRepository = $dscRepository;
        $this->cartCommonService = $cartCommonService;
        $this->articleCommonService = $articleCommonService;
        $this->userAddressService = $userAddressService;
        $this->baseRepository = $baseRepository;
        $this->flowUserService = $flowUserService;
        $this->orderCommonService = $orderCommonService;
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

        $cart_value = $this->cartCommonService->getCartValue();

        $affiliate = $GLOBALS['_CFG']['affiliate'] ? unserialize($GLOBALS['_CFG']['affiliate']) : [];
        $this->smarty->assign('affiliate', $affiliate);

        $zcgoods_id = (int)request()->input('id', 0);
        if ($zcgoods_id) {
            $Loaction = dsc_url('/#/crowdfunding/detail/' . $zcgoods_id);
        } else {
            $Loaction = dsc_url('/#/crowdfunding');
        }

        /* 跳转H5 start */
        $uachar = $this->dscRepository->getReturnMobile($Loaction);

        if ($uachar) {
            return $uachar;
        }
        /* 跳转H5 end */

        $user_id = session('user_id', 0);

        //输出页面操作 by wu
        $action = addslashes(trim(request()->input('act', 'default')));
        $action = $action ? $action : 'default';

        $this->smarty->assign('action', $action);

        //ecmoban模板堂 --zhuo end
        $this->smarty->assign('now_time', gmtime());           // 当前系统时间

        assign_template();

        $position = assign_ur_here(0, $GLOBALS['_LANG']['page_title']);
        $this->smarty->assign('page_title', $position['title']);    // 页面标题

        $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());       // 网店帮助
        $this->smarty->assign('feed_url', ($GLOBALS['_CFG']['rewrite'] == 1) ? "feed-typesnatch.xml" : 'feed.php?type=snatch'); // RSS URL
        /* 页面补充信息 */

        $gmtime = gmtime();

        /* 过滤 XSS 攻击和SQL注入 */
        get_request_filter();

        /* ------------------------------------------------------ */
        //-- PROCESSOR
        /* ------------------------------------------------------ */
        $act = addslashes(trim(request()->input('act', 'list')));
        $act = $act ? $act : 'list';

        if ($act == 'list') {
            $this->smarty->assign('zc_title', $GLOBALS['_LANG']['crowdfunding']);

            $cateParents = $this->crowdFundService->getZcCategoryParents();
            $cate_one = $cateParents['cate_one'];
            $cate_two = $cateParents['cate_two'];

            $zc_arr = $this->crowdFundService->getZcProjectList();

            $new_zc_arr = [];
            $i = 0;
            foreach ($zc_arr as $value) {
                if ($i >= 5) {
                    break;
                }
                $new_zc_arr[] = $value;
                $i++;
            }
            $this->smarty->assign('gengduo', count($zc_arr));
            $this->smarty->assign('sp_zc_list', $this->crowdFundService->getSpecialZcList(1));
            $this->smarty->assign('cate_one', $cate_one);
            $this->smarty->assign('cate_two', $cate_two);
            $this->smarty->assign('zc_arr', $new_zc_arr);

            //添加广告
            $zc_index_banner = '';
            for ($i = 1; $i <= $GLOBALS['_CFG']['auction_ad']; $i++) {
                $zc_index_banner .= "'zc_index_banner" . $i . ",";
            }
            $this->smarty->assign('zc_index_banner', $zc_index_banner);

            return $this->smarty->display('crowdfunding.dwt');
        }

        if ($act == 'quanbu') {
            $wenzi = addslashes(request()->input('wenzi', ''));

            $zc_arr = $this->crowdFundService->getZcProjectList($wenzi);

            $gengduo = count($zc_arr);
            $new_zc_arr = [];
            $i = 0;
            foreach ($zc_arr as $value) {
                if ($i >= 5) {
                    break;
                }
                $new_zc_arr[] = $value;
                $i++;
            }

            $this->smarty->assign('gengduo', $gengduo);
            $this->smarty->assign('zc_arr', $new_zc_arr);
            $result = $this->smarty->fetch("library/zc_filter.lbi");
            return response()->json($result);
        }

        if ($act == 'cate') {
            $code = (int)request()->input('code', 0);
            $wenzi = addslashes(request()->input('wenzi', ''));

            $cateParents = $this->crowdFundService->getZcCategoryParents($code, 1);
            $zc_arr = $this->crowdFundService->getZcProjectList($wenzi, $cateParents['str_id']);

            $gengduo = count($zc_arr);
            $new_zc_arr = [];
            $i = 0;
            foreach ($zc_arr as $value) {
                if ($i >= 5) {
                    break;
                }
                $new_zc_arr[] = $value;
                $i++;
            }

            $this->smarty->assign('gengduo', $gengduo);
            $this->smarty->assign('zc_arr', $new_zc_arr);
            $result = $this->smarty->fetch("library/zc_filter.lbi");
            return response()->json($result);
        }

        if ($act == 'cate_child') {
            $code = (int)request()->input('code', 0);
            $wenzi = addslashes(request()->input('wenzi', ''));

            $zc_arr = $this->crowdFundService->getZcProjectList($wenzi, $code);

            $gengduo = count($zc_arr);
            $new_zc_arr = [];
            $i = 0;
            foreach ($zc_arr as $value) {
                if ($i >= 5) {
                    break;
                }
                $new_zc_arr[] = $value;
                $i++;
            }

            $this->smarty->assign('gengduo', $gengduo);
            $this->smarty->assign('zc_arr', $new_zc_arr);
            $result = $this->smarty->fetch("library/zc_filter.lbi");
            return response()->json($result);
        }

        if ($act == 'gengduo_pid_zero') {
            $len = (int)request()->input('len', 0);
            $wenzi = addslashes(request()->input('wenzi', ''));

            $zc_arr = $this->crowdFundService->getZcProjectList($wenzi);

            $gengduo = count($zc_arr);
            $zx_tig = $gengduo - ($len + 3);

            $new_zc_arr = [];
            $i = 0;
            foreach ($zc_arr as $value) {
                if ($i >= ($len + 3)) {
                    break;
                }
                //每次点击，增加3个
                if ($i >= $len && $i < ($len + 3)) {
                    $new_zc_arr[] = $value;
                }
                $i++;
            }

            $this->smarty->assign('zx_tig', $zx_tig);
            $this->smarty->assign('zc_arr', $new_zc_arr);
            $result = $this->smarty->fetch("library/zc_more.lbi");
            return response()->json($result);
        }

        if ($act == 'gengduo_pid') {
            $pid = (int)request()->input('id', 0);
            $len = (int)request()->input('len', 0);
            $wenzi = addslashes(request()->input('wenzi', ''));

            $cateParents = $this->crowdFundService->getZcCategoryParents($pid, 1);
            $zc_arr = $this->crowdFundService->getZcProjectList($wenzi, $cateParents['str_id']);

            $gengduo = count($zc_arr);
            $zx_tig = $gengduo - ($len + 3);

            $new_zc_arr = [];
            $i = 0;
            foreach ($zc_arr as $value) {
                if ($i >= ($len + 3)) {
                    break;
                }
                //每次点击，增加3个
                if ($i >= $len && $i < ($len + 3)) {
                    $new_zc_arr[] = $value;
                }
                $i++;
            }

            $this->smarty->assign('zx_tig', $zx_tig);
            $this->smarty->assign('zc_arr', $new_zc_arr);
            $result = $this->smarty->fetch("library/zc_more.lbi");
            return response()->json($result);
        }

        if ($act == 'gengduo_tid') {
            $tid = (int)request()->input('id', 0);
            $len = (int)request()->input('len', 0);
            $wenzi = addslashes(request()->input('wenzi', ''));

            $zc_arr = $this->crowdFundService->getZcProjectList($wenzi, $tid);

            $gengduo = count($zc_arr);
            $zx_tig = $gengduo - ($len + 3);

            $new_zc_arr = [];
            $i = 0;
            foreach ($zc_arr as $value) {
                if ($i >= ($len + 3)) {
                    break;
                }
                //每次点击，增加3个
                if ($i >= $len && $i < ($len + 3)) {
                    $new_zc_arr[] = $value;
                }
                $i++;
            }

            $this->smarty->assign('zx_tig', $zx_tig);
            $this->smarty->assign('zc_arr', $new_zc_arr);
            $result = $this->smarty->fetch("library/zc_more.lbi");
            return response()->json($result);
        }

        if ($act == 'paixu_pid_zero') {
            $pid = (int)request()->input('id', 0);
            $len = (int)request()->input('len', 0);
            $sig = addslashes(request()->input('sig', ''));
            $wenzi = addslashes(request()->input('wenzi', ''));

            $zc_arr = $this->crowdFundService->getZcProjectList($wenzi, 0, $sig);

            //默认是2个，如果是3就加3
            $gengduo = count($zc_arr) - $len + 5;
            $new_zc_arr = [];
            $i = 0;
            foreach ($zc_arr as $value) {
                if ($i >= $len) {
                    break;
                }
                $new_zc_arr[] = $value;
                $i++;
            }

            $this->smarty->assign('gengduo', $gengduo);
            $this->smarty->assign('zc_arr', $new_zc_arr);
            $result = $this->smarty->fetch("library/zc_filter.lbi");
            return response()->json($result);
        }

        if ($act == 'paixu_pid') {
            $pid = (int)request()->input('id', 0);
            $len = (int)request()->input('len', 0);
            $sig = addslashes(request()->input('sig', ''));
            $wenzi = addslashes(request()->input('wenzi', ''));

            $cateParents = $this->crowdFundService->getZcCategoryParents($pid, 1);
            $zc_arr = $this->crowdFundService->getZcProjectList($wenzi, $cateParents['str_id'], $sig);

            //默认是2个，如果是3就加3
            $gengduo = count($zc_arr) - $len + 5;
            $new_zc_arr = [];
            $i = 0;
            foreach ($zc_arr as $value) {
                if ($i >= $len) {
                    break;
                }
                $new_zc_arr[] = $value;
                $i++;
            }

            $this->smarty->assign('gengduo', $gengduo);
            $this->smarty->assign('zc_arr', $new_zc_arr);
            $result = $this->smarty->fetch("library/zc_filter.lbi");
            return response()->json($result);
        }

        if ($act == 'paixu_tid') {
            $tid = (int)request()->input('id', 0);
            $len = (int)request()->input('len', 0);
            $sig = addslashes(request()->input('sig', ''));
            $wenzi = addslashes(request()->input('wenzi', ''));

            $zc_arr = $this->crowdFundService->getZcProjectList($wenzi, $tid, $sig);

            //默认是2个，如果是3就加3
            $gengduo = count($zc_arr) - $len + 5;
            $new_zc_arr = [];
            $i = 0;
            foreach ($zc_arr as $value) {
                if ($i >= $len) {
                    break;
                }
                $new_zc_arr[] = $value;
                $i++;
            }

            $this->smarty->assign('gengduo', $gengduo);
            $this->smarty->assign('zc_arr', $new_zc_arr);
            $result = $this->smarty->fetch("library/zc_filter.lbi");
            return response()->json($result);
        }

        if ($act == 'detail') {
            $cid = (int)request()->input('id', 0);
            $zhongchou = ZcProject::where('id', $cid)->first();
            $zhongchou = $zhongchou ? $zhongchou->toArray() : [];

            $this->smarty->assign('id', $cid);

            //如果没有数据，自动跳转到第一条 by wu
            if (empty($zhongchou)) {
                return dsc_header("Location: crowdfunding.php\n");
            }

            $init = $this->crowdFundService->getInitiatorInfo($cid);
            $this->smarty->assign('init', $init); //发起人信息

            if ($zhongchou) {

                $zhongchou['format_join_money'] = $this->dscRepository->getPriceFormat($zhongchou['join_money']);
                $zhongchou['format_join_money'] = str_replace(['<em>', '</em>'], ['<span>', '</span>'], $zhongchou['format_join_money']);

                $zhongchou['title_img'] = $this->dscRepository->getImagePath($zhongchou['title_img']);
                //如果关注量和赞大于三位数，则替代为千、万... by wu
                $zhongchou['focus_num'] = $this->crowdFundService->setNumberFormat($zhongchou['focus_num'], 3, 0, false);
                $zhongchou['prais_num'] = $this->crowdFundService->setNumberFormat($zhongchou['prais_num'], 3, 0, false);

                //项目状态 by wu
                if ($gmtime < $zhongchou['start_time']) {
                    $zhongchou['zc_status'] = 0;
                } elseif ($gmtime > $zhongchou['end_time']) {
                    $zhongchou['zc_status'] = 2;
                } else {
                    $zhongchou['zc_status'] = 1;
                }

                //项目成功与否 by wu
                if ($zhongchou['amount'] > $zhongchou['join_money'] && $zhongchou['zc_status'] == 2) {
                    $zhongchou['result'] = 1;
                } elseif ($zhongchou['amount'] < $zhongchou['join_money'] && $zhongchou['zc_status'] == 2) {
                    $zhongchou['result'] = 2;
                } else {
                    $zhongchou['result'] = 0;
                }

                //百分比
                $zhongchou['baifen_bi'] = round($zhongchou['join_money'] / $zhongchou['amount'], 2) * 100;

                $zhongchou['shenyu_time'] = ceil(($zhongchou['end_time'] - $gmtime) / 3600 / 24);
                $zhongchou['zw_end_time'] = local_date($GLOBALS['_LANG']['data'], $zhongchou['end_time']);
                $zhongchou['star_time'] = local_date('Y/m/d', $zhongchou['start_time']);
                $zhongchou['end_time'] = local_date('Y/m/d', $zhongchou['end_time']);

                if (!empty($zhongchou['img'])) {
                    $zhongchou['img'] = unserialize($zhongchou['img']);
                    if (!empty($zhongchou['img'])) {
                        foreach ($zhongchou['img'] as $k2 => $v2) {
                            $zhongchou['img'][$k2] = $this->dscRepository->getImagePath($v2);
                        }
                    }
                }
            }

            /* 浏览历史 */
            $history = $this->crowdFundService->getZcCateHistory();
            $this->smarty->assign('history', $history);

            $getZcGoods = $this->crowdFundService->getZcGoods($cid);

            $goods_arr = $getZcGoods['goods_arr'];
            $zong_zhichi = $getZcGoods['zong_zhichi'];
            //$zong_zhichi = $goods_arr = $getZcGoods['zong_zhichi'];

            if ($zong_zhichi == '') {
                $zong_zhichi = 0;
            }

            $this->smarty->assign('zhongchou', $zhongchou);
            $this->smarty->assign('goods_arr', $goods_arr);
            $this->smarty->assign('zong_zhichi', $zong_zhichi);

            //补充验证关注点赞状态 by wu start [1526]
            if ($user_id > 0) {
                $focus_status = ZcFocus::where('user_id', $user_id)->where('pid', $cid)->count('rec_id');
            }

            $this->smarty->assign('user_id', $user_id);
            $focus_status = empty($focus_status) ? 0 : 1;
            $this->smarty->assign('focus_status', $focus_status);
            $prais_status = empty(session()->get('REMOTE_ADDR_' . $user_id . '_' . $cid)) ? 0 : 1;
            $this->smarty->assign('prais_status', $prais_status);
            //补充验证关注点赞状态 by wu end

            //输出页面分享信息 by wu start [1526]
            $base_url = $GLOBALS['dsc']->http() . request()->server('SERVER_NAME') . request()->server('PHP_SELF');
            $page_url = $base_url . "?" . request()->server('QUERY_STRING');

            $title_img = $zhongchou['title_img'] ?? '';
            $title = $zhongchou['title'] ?? '';
            $img_url = str_replace(basename($base_url), '', $base_url) . $title_img;
            $this->smarty->assign('share_title', $title);
            $this->smarty->assign('share_url', $page_url);
            $this->smarty->assign('share_img', $img_url);
            //输出页面分享信息 by wu end

            $logo = empty($GLOBALS['_CFG']['two_code_logo']) ? '' : str_replace('../', '', $GLOBALS['_CFG']['two_code_logo']);

            if ($GLOBALS['_CFG']['open_oss'] == 1) {
                $logo = $logo ? $this->dscRepository->getImagePath($logo) : '';
            } else {
                $logo = $logo && (strpos($logo, 'http') === false) ? storage_public($logo) : $logo;
            }

            //分享二维码 by wu start [1526]
            $size = '200x200';
            $url = url('/') . '/';
            $data = $url . 'mobile/index.php?m=crowd_funding&a=info&id=' . $cid;
            //$data = $page_url;
            $errorCorrectionLevel = 'M'; // 纠错级别：L、M、Q、H
            $matrixPointSize = 3; // 点的大小：1到10

            if (!file_exists(storage_public(IMAGE_DIR . "/weixin_zc"))) {
                make_dir(storage_public(IMAGE_DIR . "/weixin_zc"));
            }

            $image = IMAGE_DIR . "/weixin_zc/zc_" . $cid . ".png";
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

            $this->smarty->assign('weixin_img_url', $this->dscRepository->getImagePath($image));
            $this->smarty->assign('weixin_img_text', $zhongchou['title']);
            //分享二维码 by wu end

            //众筹支持者 by wu start [1526]
            $this->smarty->assign('backer_num', $this->crowdFundService->getBackerNum($cid));
            $this->smarty->assign('backer_list', get_backer_list($cid, 1));
            //众筹支持者 by wu end

            //众筹话题 by wu start [1526]
            $this->smarty->assign('topic_num', $this->crowdFundService->getTopicNum($cid));
            $this->smarty->assign('topic_list', get_topic_list($cid, 1));
            //众筹话题 by wu end

            /*  @author-bylu 项目进度 start */
            $zc_evolve_list = ZcProgress::where('pid', $cid)->orderBy('add_time', 'desc')->get();
            $zc_evolve_list = $zc_evolve_list ? $zc_evolve_list->toArray() : [];

            if ($zc_evolve_list) {
                foreach ($zc_evolve_list as $k => &$v) {
                    $v['pro-day'] = floor(($gmtime - $v['add_time']) / 86400);
                    $v['img'] = unserialize($v['img']);
                    if (!empty($v['img'])) {
                        foreach ($v['img'] as $k2 => $v2) {
                            $v['img'][$k2] = $this->dscRepository->getImagePath($v2);
                        }
                    }
                }
            }

            $this->smarty->assign('zc_evolve_list_num', count($zc_evolve_list));
            $this->smarty->assign('zc_evolve_list', $zc_evolve_list);
            /*  @author-bylu 项目进度 end */

            /*  @author-bylu 判断当前商家是否允许"在线客服" start */
            //判断平台是否开启了IM在线客服
            $kf_im_switch = SellerShopinfo::where('ru_id', 0)->value('kf_im_switch');
            if ($kf_im_switch) {
                $shop_information['is_dsc'] = true;
            } else {
                $shop_information['is_dsc'] = false;
            }
            $this->smarty->assign('shop_information', $shop_information);
            /*  @author-bylu  end */

            $ecsCookie = request()->cookie('ECS');

            /* 记录浏览历史 ecmoban模板堂 --zhuo start 浏览列表插件 */
            if (isset($ecsCookie['zc_history']) && !empty($ecsCookie['zc_history'])) {
                $zc_history = explode(',', $ecsCookie['zc_history']);

                array_unshift($zc_history, $zcgoods_id);
                $zc_history = array_unique($zc_history);

                while (count($zc_history) > 100000) {
                    array_pop($zc_history);
                }

                cookie()->queue('ECS[zc_history]', implode(',', $zc_history), 60 * 24 * 30);
            } else {
                cookie()->queue('ECS[zc_history]', $zcgoods_id, 60 * 24 * 30);
            }
            /* 记录浏览历史 ecmoban模板堂 --zhuo end 浏览列表插件 */

            $this->smarty->assign('zc_title', $zhongchou['title']);
            return $this->smarty->display('crowdfunding.dwt');
        }

        //ajax获取支持者        列表 by wu
        if ($act == 'get_backer_list') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $zcid = (int)request()->input('zcid', 0);
            $page = (int)request()->input('page', 1);
            $result['content'] = get_backer_list($zcid, $page);
            return response()->json($result);
        }

        //ajax获取话题列表 by wu
        if ($act == 'get_topic_list') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $zcid = (int)request()->input('zcid', 0);
            $page = (int)request()->input('page', 1);
            $result['content'] = get_topic_list($zcid, $page);
            return response()->json($result);
        }

        //ajax发布评论回复 by wu
        if ($act == 'post_topic') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $topic_id = (int)request()->input('topic_id', 0);
            $type = (int)request()->input('type', 0);
            $parent_id = (int)request()->input('parent_id', 0);

            $topic_content = strip_tags(request()->input('topic_content', ''));
            $zcid = 0;
            if ($topic_id > 0) {
                $zcid = ZcTopic::where('topic_id', $topic_id)->value('pid');
            }

            if ($type != 2) {
                $parent_id = 0;
            }

            if ($user_id > 0) {
                if (!empty($topic_content)) {
                    $zcTopic = [
                        'parent_topic_id' => $topic_id,
                        'reply_topic_id' => $parent_id,
                        'topic_status' => 1,
                        'topic_content' => $topic_content,
                        'user_id' => $user_id,
                        'pid' => $zcid,
                        'add_time' => $gmtime
                    ];

                    $topic_id = ZcTopic::insertGetId($zcTopic);

                    if ($topic_id) {
                        $result['error'] = 1;
                        $result['message'] = $GLOBALS['_LANG']['lang_crowd_art_succeed'];
                    }
                }
            } else {
                $result['error'] = 9;
                $result['message'] = $GLOBALS['_LANG']['lang_crowd_login'];
            }

            return response()->json($result);
        }

        //ajax发布话题 by wu
        if ($act == 'submit_topic') {
            $result = [
                'error' => 0,
                'message' => '',
                'content' => [
                    'zc_topic_num' => 0
                ]
            ];

            $zcid = (int)request()->input('zcid', 0);
            $topic_content = strip_tags(request()->input('topic_content', ''));
            if ($user_id > 0) {
                if (!empty($topic_content)) {
                    /* 判断当前会员是否重复发布话题 */
                    $count = ZcTopic::where('user_id', $user_id)->where('pid', $zcid)->count();

                    if ($count <= 0) {
                        $zcTopic = [
                            'parent_topic_id' => 0,
                            'topic_status' => 1,
                            'topic_content' => $topic_content,
                            'user_id' => $user_id,
                            'pid' => $zcid,
                            'add_time' => $gmtime
                        ];

                        $topic_id = ZcTopic::insertGetId($zcTopic);

                        if ($topic_id) {
                            $result['error'] = 1;
                            $result['message'] = $GLOBALS['_LANG']['lang_crowd_art_succeed'];
                        }
                    } else {
                        $result['error'] = 8;
                        $result['message'] = $GLOBALS['_LANG']['lang_crowd_art_succeed_repeat'];
                    }

                    $result['content']['zc_topic_num'] = ZcTopic::where('pid', $zcid)->where('parent_topic_id', 0)->count();
                }
            } else {
                $result['error'] = 9;
                $result['message'] = $GLOBALS['_LANG']['lang_crowd_login'];
            }
            return response()->json($result);
        }

        if ($act == 'checkout') {

            $this->smarty->assign('zc_title', $GLOBALS['_LANG']['zc_order_info']);

            /*
             * 检查用户是否已经登录
             * 如果用户已经登录了则检查是否有默认的收货地址
             * 如果没有登录则跳转到登录和注册页面
             */
            if (empty($user_id)) {
                /* 用户没有登录且没有选定匿名购物，转向到登录页面 */
                return dsc_header("Location: user.php\n");
            }
            //获取收货地址 by wu
            if (session('address_id')) {
                $consignee = $this->userAddressService->getUserAddressInfo(session('address_id'));
            } else {
                $consignee = $this->flowUserService->getConsignee($user_id);
            }

            $b = [];
            $b['province'] = isset($consignee['province_name']) ? $consignee['province_name'] : '';
            $b['city'] = isset($consignee['city_name']) ? $consignee['city_name'] : '';
            $b['district'] = isset($consignee['district_name']) ? $consignee['district_name'] : '';

            $this->smarty->assign('b', $b);

            $gid = (int)request()->input('gid', 0);

            $goods_arr = $this->crowdFundService->getZcGoodsProject($gid);

            $shengyu = $goods_arr['limit'] - $goods_arr['backer_num'];
            if ($shengyu == 0) {
                return show_message($GLOBALS['_LANG']['Sold_out'], $GLOBALS['_LANG']['back_up_page'], 'javascript:history.back(-1)');
            }

            $g_title = ZcProject::where('id', $goods_arr['pid'])->value('title');

            $user_address = $this->userAddressService->getUserAddressList($user_id);

            session([
                'browse_trace' => "flow.php?step=checkout"
            ]);

            if (!$user_address && $consignee) {
                $province_name = Region::where('region_id', $consignee['province'])->value('region_name');
                $city_name = Region::where('region_id', $consignee['city'])->value('region_name');
                $district_name = Region::where('region_id', $consignee['district'])->value('region_name');

                $consignee['province_name'] = $province_name ? $province_name : '';
                $consignee['city_name'] = $city_name ? $city_name : '';
                $consignee['district_name'] = $district_name ? $district_name : '';
                $consignee['region'] = $consignee['province_name'] . "&nbsp;" . $consignee['city_name'] . "&nbsp;" . $consignee['district_name'];

                $user_address = [$consignee];
            }

            $this->smarty->assign('user_address', $user_address);

            $inv_content_list = explode("\n", str_replace("\r", '', $GLOBALS['_CFG']['invoice_content']));
            $this->smarty->assign('inv_content', $inv_content_list[0]);

            $this->smarty->assign('goods_arr', $goods_arr);
            $this->smarty->assign('g_title', $g_title);
            $this->smarty->assign('consignee', $consignee);
            return $this->smarty->display('crowdfunding.dwt');
        }

        if ($act == 'consignee') {
            /* ------------------------------------------------------ */
            //-- 收货人信息
            /* ------------------------------------------------------ */

            $this->dscRepository->helpersLang(['user', 'shopping_flow']);

            load_helper('transaction');
            load_helper('order');
            $this->smarty->assign('lang', $GLOBALS['_LANG']);

            if (request()->server('REQUEST_METHOD') == 'GET') {
                /* 收货人信息填写界面 */
                if (request()->exists('direct_shopping')) {
                    session([
                        'direct_shopping' => 1
                    ]);
                }

                /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
                $this->smarty->assign('country_list', get_regions());
                $this->smarty->assign('shop_country', $GLOBALS['_CFG']['shop_country']);
                $this->smarty->assign('shop_province_list', get_regions(1, $GLOBALS['_CFG']['shop_country']));

                /* 获得用户所有的收货人信息 */
                if ($user_id > 0) {
                    $consignee_list = $this->userAddressService->getUserAddressList($user_id, 5);

                    if (count($consignee_list) < 5) {
                        /* 如果用户收货人信息的总数小于 5 则增加一个新的收货人信息 */
                        $consignee_list[] = ['country' => $GLOBALS['_CFG']['shop_country'], 'email' => session('email', '')];
                    }
                } else {
                    if (session('flow_consignee')) {
                        $consignee_list = [session('flow_consignee')];
                    } else {
                        $consignee_list[] = ['country' => $GLOBALS['_CFG']['shop_country']];
                    }
                }
                $this->smarty->assign('name_of_region', [$GLOBALS['_CFG']['name_of_region_1'], $GLOBALS['_CFG']['name_of_region_2'], $GLOBALS['_CFG']['name_of_region_3'], $GLOBALS['_CFG']['name_of_region_4']]);
                $this->smarty->assign('consignee_list', $consignee_list);

                /* 取得每个收货地址的省市区列表 */
                $province_list = [];
                $city_list = [];
                $district_list = [];

                if ($consignee_list) {
                    foreach ($consignee_list as $region_id => $consignee) {
                        $consignee['country'] = isset($consignee['country']) ? intval($consignee['country']) : 0;
                        $consignee['province'] = isset($consignee['province']) ? intval($consignee['province']) : 0;
                        $consignee['city'] = isset($consignee['city']) ? intval($consignee['city']) : 0;

                        $province_list[$region_id] = get_regions(1, $consignee['country']);
                        $city_list[$region_id] = get_regions(2, $consignee['province']);
                        $district_list[$region_id] = get_regions(3, $consignee['city']);
                    }
                }

                $this->smarty->assign('province_list', $province_list);
                $this->smarty->assign('city_list', $city_list);
                $this->smarty->assign('district_list', $district_list);

                $this->smarty->assign('page_title', $GLOBALS['_LANG']['lang_crowd_page_title']); //页面标题 by wu
                $this->smarty->assign('step', 'consignee'); //页面步骤 by wu

                /* 返回收货人页面代码 */
                return $this->smarty->display('crowdfunding.dwt');
            } else {
                /* 保存收货人信息 */
                $consignee = [
                    'address_id' => (int)request()->input('address_id', 0),
                    'consignee' => addslashes(request()->input('consignee', '')),
                    'country' => addslashes(request()->input('country', '')),
                    'province' => addslashes(request()->input('province', '')),
                    'city' => addslashes(request()->input('city', '')),
                    'district' => addslashes(request()->input('district', '')),
                    'email' => addslashes(request()->input('email', '')),
                    'address' => addslashes(request()->input('address', '')),
                    'zipcode' => make_semiangle(trim(request()->input('zipcode', ''))),
                    'tel' => make_semiangle(trim(request()->input('tel', ''))),
                    'mobile' => make_semiangle(trim(request()->input('mobile', ''))),
                    'sign_building' => addslashes(request()->input('sign_building', '')),
                    'best_time' => addslashes(request()->input('best_time', '')),
                ];

                if ($user_id > 0) {

                    /* 如果用户已经登录，则保存收货人信息 */
                    $consignee['user_id'] = $user_id;
                    $this->userAddressService->saveConsignee($consignee, true);
                }

                /* 保存到session */
                session([
                    'flow_consignee' => stripslashes_deep($consignee)
                ]);

                $gid = (int)request()->input('gid', 0);
                Header("Location:crowdfunding.php?act=checkout&gid=" . $gid);
            }
        }

        if ($act == 'drop_consignee') {
            /* ------------------------------------------------------ */
            //-- 删除收货人信息
            /* ------------------------------------------------------ */

            $consignee_id = (int)request()->input('id', 0);
            $gid = (int)request()->input('gid', 0);
            if ($this->userAddressService->dropConsignee($consignee_id, $user_id)) {
                return dsc_header("Location: crowdfunding.php?act=consignee&gid=" . $gid . "\n");
            } else {
                return show_message($GLOBALS['_LANG']['not_fount_consignee']);
            }
        }

        //统计�        �注点赞 by wu
        if ($act == 'statistical') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $zcid = (int)request()->input('zcid', 0);
            $type = (int)request()->input('type', 0);

            if ($zcid > 0 && $type > 0) {
                //关注
                if ($type == 1) {
                    //只有登陆用户才能关注
                    if (empty($user_id)) {
                        $result['error'] = 9;
                        $result['message'] = $GLOBALS['_LANG']['lang_crowd_login_focus'];
                    } else {
                        $focus_status = ZcFocus::where('pid', $zcid)->where('user_id', $user_id)->value('rec_id');

                        if (empty($focus_status)) {
                            $ZcFocus = [
                                'user_id' => $user_id,
                                'pid' => $zcid,
                                'add_time' => $gmtime
                            ];
                            $rec_id = ZcFocus::insertGetId($ZcFocus);

                            if ($rec_id) {
                                ZcProject::where('id', $zcid)->increment('focus_num', 1);

                                $result['error'] = 2;
                                $result['message'] = $GLOBALS['_LANG']['lang_crowd_focus_succeed'];
                            }
                        } else {
                            $result['error'] = 3;
                            $result['message'] = $GLOBALS['_LANG']['lang_crowd_focus_repeat'];
                        }
                    }
                }
                //点赞
                if ($type == 2) {
                    if (empty(session('REMOTE_ADDR_' . $user_id . '_' . $zcid))) {
                        ZcProject::where('id', $zcid)->increment('prais_num', 1);

                        $result['error'] = 4;
                        $result['message'] = $GLOBALS['_LANG']['lang_crowd_like'];
                        session()->put('REMOTE_ADDR_' . $user_id . '_' . $zcid, request()->server('REMOTE_ADDR'));
                    } else {
                        $result['error'] = 5;
                        $result['message'] = $GLOBALS['_LANG']['lang_crowd_like_repeat'];
                    }
                }
            }
            return response()->json($result);
        }

        if ($act == 'confirmAddress') {
            $consignee_id = (int)request()->input('consignee_id', 0);
            $gid = (int)request()->input('gid', 0);

            $goods_arr = ZcGoods::where('id', $gid)->first();
            $goods_arr = $goods_arr ? $goods_arr->toArray() : [];

            $price = isset($goods_arr['price']) ? $goods_arr['price'] : 0;
            $yunfei = isset($goods_arr['yunfei']) ? $goods_arr['yunfei'] : '';
            $goods_content = isset($goods_arr['content']) ? $goods_arr['content'] : '';
            $goods_id = isset($goods_arr['goods_id']) ? $goods_arr['goods_id'] : 0;

            $g_title = ZcProject::where('id', $goods_arr['pid'])->value('title');

            $confirm_address = $this->crowdFundService->getOrderConfirmAddress($consignee_id);
            if (!$confirm_address && $consignee) {
                $consignee['province_name'] = get_goods_region_name($consignee['province']);
                $consignee['city_name'] = get_goods_region_name($consignee['city']);
                $consignee['district_name'] = get_goods_region_name($consignee['district']);
                $consignee['region'] = $consignee['province_name'] . "&nbsp;" . $consignee['city_name'] . "&nbsp;" . $consignee['district_name'];

                $confirm_address = [$consignee];
            }
            $confirm_address['mobile'] = $confirm_address['mobile'] ? $confirm_address['mobile'] : $confirm_address['tel'];
            $content = "<span>" . $confirm_address['consignee'] . "</span>" .
                "<span>" . $confirm_address['address'] . "</span>" .
                "<span>" . $confirm_address['mobile'] . "</span>" .
                "<span><a class='f_blue repeat' href='javascript:void(0);' id='editRepeat'>修改地址</a></span>";
            $common = " <div class='common_button' id='common_button' > " .
                "<form action='crowdfunding.php?act=done' method='post'> " .
                "<input type='hidden' name='country'  value=" . $confirm_address['country'] . ">" .
                "<input type='hidden' name='province' value=" . $confirm_address['province'] . ">" .
                "<input type='hidden' name='city' value=" . $confirm_address['city'] . ">" .
                "<input type='hidden' name='district' value=" . $confirm_address['district'] . ">" .
                "<input type='hidden' name='consignee' value=" . $confirm_address['consignee'] . ">" .
                "<input type='hidden' name='address' value=" . $confirm_address['address'] . ">" .
                "<input type='hidden' name='tel' value=" . $confirm_address['tel'] . ">" .
                "<input type='hidden' name='mobile' value=" . $confirm_address['mobile'] . ">" .
                "<input type='hidden' name='email' value=" . $confirm_address['email'] . ">" .
                "<input type='hidden' name='best_time' value=" . $confirm_address['best_time'] . ">" .
                "<input type='hidden' name='sign_building' value=" . $confirm_address['sign_building'] . ">" .
                "<input type='hidden' id='inv_payee' name='inv_payee' value=''>" .
                "<input type='hidden' id='liuyan' name='postscript' value=''>" .
                "<input type='hidden' name='goods_amount' value=" . $price . ">" .
                "<input type='hidden' name='shipping_fee' value=" . $yunfei . ">" .
                "<input type='hidden' name='order_amount' value=" . $price . ">" .
                "<input type='hidden' name='huibao' value=" . $goods_content . ">" .
                "<input type='hidden' name='g_title' value=" . $g_title . ">" .
                "<input type='hidden' name='xm_id' value=" . $goods_id . ">" .
                "<input type='hidden' name='gid' value=" . $gid . ">" .
                "<input type='submit' id='btn_sub' value='" . $GLOBALS['_LANG']['lang_crowd_next_step'] . "'>" .
                "<input type='hidden' name='_token' value='" . csrf_token() . "'>" .
                "</form>" .
                "</div>";
            $result = ['error' => 0, 'content' => $content, 'common' => $common];

            session([
                'address_id' => $consignee_id
            ]);

            return response()->json($result);
        };

        if ($act == 'add_Consignee') {
            $address_id = (int)request()->input('address_id', 0);
            if ($address_id == 0) {
                $consignee['country'] = 1;
                $consignee['province'] = 0;
                $consignee['city'] = 0;
            }

            if (!empty($cart_value)) {
                get_goods_flow_type($cart_value);
            }

            $consignee = $this->userAddressService->getUpdateFlowConsignee($address_id, $user_id);
            $this->smarty->assign('consignee', $consignee);

            /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
            $this->smarty->assign('country_list', get_regions());

            $this->smarty->assign('please_select', $GLOBALS['_LANG']['please_select']);

            $consignee['country'] = !empty($consignee['country']) ? $consignee['country'] : 1;
            $consignee['province'] = !empty($consignee['province']) ? $consignee['province'] : 0;
            $consignee['city'] = !empty($consignee['city']) ? $consignee['city'] : 0;
            $consignee['district'] = !empty($consignee['district']) ? $consignee['district'] : 0;

            $province_list = $this->areaService->getRegionsLog(1, $consignee['country']);
            $city_list = $this->areaService->getRegionsLog(2, $consignee['province']);
            $district_list = $this->areaService->getRegionsLog(3, $consignee['city']);
            $street_list = $this->areaService->getRegionsLog(4, $consignee['district']);

            $this->smarty->assign('province_list', $province_list);
            $this->smarty->assign('city_list', $city_list);
            $this->smarty->assign('district_list', $district_list);
            $this->smarty->assign('street_list', $street_list);

            $this->smarty->assign('gid', (int)request()->input('gid', 0));


            if ($user_id <= 0) {
                $result['error'] = 2;
                $result['message'] = $GLOBALS['_LANG']['lang_crowd_not_login'];
            } else {
                $result['error'] = 0;
                $result['content'] = $this->smarty->fetch("library/consignee_zc.lbi");
            }
            return response()->json($result);
        }

        if ($act == 'insert_Consignee') {
            $result = ['message' => '', 'result' => '', 'error' => 0];

            $csg = json_str_iconv(request()->input('csg', ''));
            $csg = dsc_decode($csg);

            $consignee = [
                'address_id' => empty($csg->address_id) ? 0 : intval($csg->address_id),
                'consignee' => empty($csg->consignee) ? '' : compile_str(trim($csg->consignee)),
                'country' => empty($csg->country) ? 0 : intval($csg->country),
                'province' => empty($csg->province) ? 0 : intval($csg->province),
                'city' => empty($csg->city) ? 0 : intval($csg->city),
                'district' => empty($csg->district) ? 0 : intval($csg->district),
                'street' => empty($csg->street) ? 0 : intval($csg->street),
                'address' => empty($csg->address) ? '' : compile_str($csg->address),
                'mobile' => empty($csg->mobile) ? '' : compile_str(make_semiangle(trim($csg->mobile))),
            ];

            if ($result['error'] == 0) {
                if ($user_id > 0) {
                    $row = $this->userAddressService->getUserAddressCount($user_id, $consignee);

                    if ($row > 0) {
                        $result['error'] = 4;
                        $result['message'] = $GLOBALS['_LANG']['shiping_in'];
                    } else {
                        $result['error'] = 0;

                        session([
                            'address_id' => $consignee['address_id']
                        ]);

                        if ($user_id) {
                            /* 如果用户已经登录，则保存收货人信息 */
                            $consignee['user_id'] = $user_id;
                            $this->userAddressService->saveConsignee($consignee, true);
                        }

                        $user_address_id = Users::where('user_id', $user_id)->value('address_id');

                        if ($user_address_id > 0) {
                            $consignee['address_id'] = $user_address_id;
                        }

                        if ($consignee['address_id'] > 0) {
                            Users::where('user_id', $user_id)->update(['address_id' => $consignee['address_id']]);

                            session([
                                'flow_consignee' => $consignee
                            ]);

                            $result['message'] = $GLOBALS['_LANG']['edit_success'];
                        } else {
                            $result['message'] = $GLOBALS['_LANG']['add_success'];
                        }
                    }

                    $user_address = $this->userAddressService->getUserAddressList($user_id);
                    $this->smarty->assign('user_address', $user_address);
                    $consignee['province_name'] = get_goods_region_name($consignee['province']);
                    $consignee['city_name'] = get_goods_region_name($consignee['city']);
                    $consignee['district_name'] = get_goods_region_name($consignee['district']);
                    $consignee['street_name'] = get_goods_region_name($consignee['street']);
                    $consignee['consignee_address'] = $consignee['province_name'] . $consignee['city_name'] . $consignee['district_name'] . $consignee['address'];

                    $this->smarty->assign('consignee', $consignee);

                    $result['content'] = $this->smarty->fetch("library/consignee_zcflow.lbi");

                    $this->smarty->assign('warehouse_id', $warehouse_id);
                    $this->smarty->assign('area_id', $area_id);

                    $once = UserAddress::where('user_id', $user_id)->count();

                    if ($once < 2) {
                        $result['once'] = true;
                        $result['gid'] = (int)request()->input('gid', 0);
                    }
                } else {
                    $result['error'] = 2;
                    $result['message'] = $GLOBALS['_LANG']['lang_crowd_not_login'];
                }
            }
            return response()->json($result);
        }

        if ($act == 'delete_Consignee') {
            load_helper('order');

            $gid = (int)request()->input('gid', 0);
            $result['error'] = 0;

            $address_id = (int)request()->input('address_id', 0);

            UserAddress::where('address_id', $address_id)->delete();

            $consignee = session()->has('flow_consignee') ? session('flow_consignee') : [];
            $this->smarty->assign('consignee', $consignee);

            $this->smarty->assign('warehouse_id', $warehouse_id);
            $this->smarty->assign('area_id', $area_id);

            $user_address = $this->userAddressService->getUserAddressList($user_id);
            $this->smarty->assign('user_address', $user_address);

            if (!$user_address) {
                $consignee = [
                    'province' => 0,
                    'city' => 0,
                    'district' => 0
                ];
                // 取得国家列表、商店所在国家、商店所在国家的省列表
                $this->smarty->assign('country_list', get_regions());
                $this->smarty->assign('please_select', $GLOBALS['_LANG']['please_select']);

                $province_list = $this->areaService->getRegionsLog(1, 1);
                $city_list = $this->areaService->getRegionsLog(2, $consignee['province']);
                $district_list = $this->areaService->getRegionsLog(3, $consignee['city']);
                $street_list = $this->areaService->getRegionsLog(4, $consignee['district']);

                $this->smarty->assign('province_list', $province_list);
                $this->smarty->assign('city_list', $city_list);
                $this->smarty->assign('district_list', $district_list);
                $this->smarty->assign('street_list', $street_list);
                $this->smarty->assign('consignee', $consignee);

                $result['error'] = 2;
                $result['gid'] = $gid;
            } else {
                $result['content'] = $this->smarty->fetch("library/consignee_zcflow.lbi");
            }
            return response()->json($result);
        }

        if ($act == 'done') {
            load_helper('clips');
            load_helper('payment');
            load_helper('order');
            $gid = (int)request()->input('gid', 0);
            $this->smarty->assign('zc_title', $GLOBALS['_LANG']['zc_order_submit']);

            //判断是否有收获地址 by wu
            $consignee = request()->input('consignee', '');
            if (empty($consignee)) {
                return show_message($GLOBALS['_LANG']['lang_crowd_not_address'], $GLOBALS['_LANG']['back_up_page'], 'javascript:history.back(-1)');
            }

            //判断是否重复提交订单 by wu start
            $zc_order_num = OrderInfo::where('user_id', $user_id)
                ->where('is_zc_order', 1)
                ->where('zc_goods_id', $gid)
                ->where('pay_status', 0)
                ->where('is_delete', 0)
                ->where('order_status', '<>', 2)
                ->count();

            if ($zc_order_num > 0) {
                return show_message($GLOBALS['_LANG']['lang_crowd_not_pay'], $GLOBALS['_LANG']['back_up_page'], 'user_crowdfund.php?act=crowdfunding');
            }
            //判断是否重复提交订单 by wu end

            $arr_shipping = Shipping::where('enabled', 1)->get();
            $arr_shipping = $arr_shipping ? $arr_shipping->toArray() : [];

            $shipping_id = $arr_shipping ? $arr_shipping[0]['shipping_id'] : 0;
            $shipping_name = $arr_shipping ? $arr_shipping[0]['shipping_name'] : '';

            $arr_payment = Payment::where('enabled', 1)->get();
            $arr_payment = $arr_payment ? $arr_payment->toArray() : [];

            $pay_id = $arr_payment ? $arr_payment[0]['pay_id'] : 0;
            $pay_name = $arr_payment ? $arr_payment[0]['pay_name'] : '';

            $order['country'] = (int)request()->input('country', 1);

            $order['province'] = (int)request()->input('province', 0);
            $order['city'] = (int)request()->input('city', 0);
            $order['district'] = (int)request()->input('district', 0);

            $order['consignee'] = trim(request()->input('consignee', ''));
            $order['address'] = trim(request()->input('address', ''));
            $order['tel'] = trim(request()->input('tel', 0));
            $order['mobile'] = trim(request()->input('mobile', 0));
            $order['email'] = trim(request()->input('email', ''));
            $order['best_time'] = trim(request()->input('best_time', ''));
            $order['sign_building'] = trim(request()->input('sign_building', ''));
            $order['zipcode'] = '';
            $order['inv_payee'] = trim(request()->input('inv_payee', ''));
            $order['postscript'] = trim(request()->input('postscript', ''));
            $order['shipping_id'] = $shipping_id;
            $order['shipping_name'] = $shipping_name;
            $order['pay_id'] = $pay_id;
            $order['pay_name'] = $pay_name;
            $order['how_oos'] = '';
            $order['how_surplus'] = '';
            $order['pack_name'] = '';
            $order['card_name'] = '';
            $order['card_message'] = '';
            $order['goods_amount'] = trim(request()->input('goods_amount', 0));
            $order['shipping_fee'] = trim(request()->input('shipping_fee', 0));
            $order['insure_fee'] = 0;
            $order['pay_fee'] = 0;
            $order['pack_fee'] = 0;
            $order['card_fee'] = 0;
            $order['money_paid'] = 0;
            $order['surplus'] = 0;
            $order['integral'] = 0;
            $order['integral_money'] = 0;
            $order['bonus'] = 0;
            $order['order_amount'] = $order['goods_amount'] + $order['shipping_fee'];
            $order['from_ad'] = 0;
            $order['referer'] = $GLOBALS['_LANG']['self_site'];
            $order['add_time'] = gmtime();
            $order['confirm_time'] = 0;
            $order['pay_time'] = 0;
            $order['shipping_time'] = 0;
            $order['pack_id'] = 0;
            $order['card_id'] = 0;
            $order['bonus_id'] = 0;
            $order['invoice_no'] = '';
            $order['extension_code'] = '';
            $order['extension_id'] = 0;
            $order['to_buyer'] = '';
            $order['pay_note'] = '';
            $order['agency_id'] = 0;
            $order['inv_type'] = '';
            $order['tax'] = 0;
            $order['is_separate'] = 0;
            $order['parent_id'] = 0;
            $order['discount'] = 0;
            $order['is_zc_order'] = 1;
            $order['zc_goods_id'] = (int)request()->input('gid', 0);
            $order['user_id'] = $user_id;
            $order['order_status'] = 0;
            $order['shipping_status'] = 0;
            $order['pay_status'] = 0;
            //纳税人识别号
            $order['tax_id'] = trim(request()->input('tax_id', ''));
            $order['inv_content'] = trim(request()->input('inv_content', ''));
            $order['invoice_type'] = trim(request()->input('invoice_type', 0));

            $order['order_sn'] = $this->orderCommonService->getOrderSn(); //获取新订单号

            $order_id = OrderInfo::insertGetId($order);

            $order['format_goods_amount'] = $this->dscRepository->getPriceFormat($order['goods_amount']);
            $order['format_shipping_fee'] = $this->dscRepository->getPriceFormat($order['shipping_fee']);
            $order['format_order_amount'] = $this->dscRepository->getPriceFormat($order['order_amount']);

            /* 插入支付日志 */
            $order['log_id'] = insert_pay_log($order_id, $order['order_amount'], PAY_ORDER);

            /* 在线支付代码 */
            $cod_fee = $cod_fee ?? 0;
            $payment_list = available_payment_list(0, $cod_fee);

            //取出所有在线支付方法(含按钮);
            foreach ($payment_list as $k => $v) {
                if ($v['is_online'] == 1 || $v['pay_code'] == 'balance') {
                    if ($v && strpos($v['pay_code'], 'pay_') === false) {
                        $pay_name = Str::studly($v['pay_code']);
                        $pay_obj = app('\\App\\Plugins\\Payment\\' . $pay_name . '\\' . $pay_name);

                        if (!is_null($pay_obj)) {

                            //$payment = payment_info($v['pay_id']);
                            $pay_online_button[$v['pay_code']] = <<<HTML
<div style='display:inline-block;' >
{$pay_obj->get_code($order, unserialize_config($v['pay_config']))}
</div>
HTML;
                            //判断已安装的支付方法中是否有"支付宝网银直连"方法;
                            if ($v['pay_code'] == 'alipay_bank') {
                                //重新赋值支付宝网银直连的支付按钮,将支付按钮列表中的删除;
                                $this->smarty->assign('is_alipay_bank', $pay_online_button['alipay_bank']);
                                unset($pay_online_button['alipay_bank']);
                            }
                            if ($v['pay_code'] == 'balance') {
                                $pay_online_button['balance'] = <<<HTML
		<a href="flow.php?step=done&act=balance&order_sn={$order['order_sn']}" id="balance" style="float: left;" order_sn="{$order['order_sn']}" flag="balance" >{$GLOBALS['_LANG']['balance_pay']}</a>
HTML;
                            }
                            //判断当前用户是否拥有白条支付授权(有的话才显示"白条支付按钮");
                            if (!empty($user_baitao_amount)) {
                                $this->smarty->assign('is_chunsejinrong', true);
                                if ($v['pay_code'] == 'chunsejinrong') {
                                    $pay_online_button['chunsejinrong'] = <<<HTML
				<a href="flow.php?step=done&act=chunsejinrong&order_sn={$order['order_sn']}" id="chunsejinrong" style="float: left;" order_sn="{$order['order_sn']}" flag="chunsejinrong" >{$GLOBALS['_LANG']['ious_pay']}</a>
HTML;
                                }
                            }
                        }
                    }
                }
            }
            $this->smarty->assign('pay_online_button', $pay_online_button); //在线支付按钮数组;
            $this->smarty->assign('is_onlinepay', true); //在线支付标记 by lu;
            /* 在线支付代码 */

            $user_info = Users::where('user_id', $user_id);
            $user_info = $this->baseRepository->getToArrayFirst($user_info);

            $user_info['user_money'] = $user_info['user_money'] ?? 0;
            $user_info['pay_points'] = $user_info['pay_points'] ?? 0;

            $balance_enabled = Payment::where('pay_code', 'balance')->value('enabled');

            /* 如果使用余额，取得用户余额 */
            $allow_use_surplus = 0;
            if ((!isset($this->config['use_surplus']) || $this->config['use_surplus'] == '1') && $user_id > 0 && $user_info['user_money'] > 0
            ) {
                if ($balance_enabled) { // 安装了"余额支付",才显示余额支付输入框 bylu;
                    // 能使用余额
                    $allow_use_surplus = 1;
                    $this->smarty->assign('your_surplus', $user_info['user_money']);
                }
            }
            $this->smarty->assign('allow_use_surplus', $allow_use_surplus);

            // 如果开启用户支付密码
            if ($this->config['use_paypwd'] == 1) {
                // 可使用余额，且用户有余额
                if ($allow_use_surplus == 1) {
                    $this->smarty->assign('open_pay_password', 1);
                    $this->smarty->assign('pay_pwd_error', 1);
                }
            }

            if (empty($order['address'])) {
                $b = [];
                $b['province'] = Region::where('region_id', $order['province'])->value('region_name');
                $b['city'] = Region::where('region_id', $order['city'])->value('region_name');
                $b['district'] = Region::where('region_id', $order['district'])->value('region_name');
                $this->smarty->assign('b', $b);
            }

            $xm_id = request()->input('xm_id', 0);
            $g_title = request()->input('g_title', '');
            $huibao = request()->input('huibao', '');

            $this->smarty->assign('xm_id', $xm_id);
            $this->smarty->assign('g_title', $g_title);
            $this->smarty->assign('huibao', $huibao);
            $this->smarty->assign('order', $order);

            return $this->smarty->display('crowdfunding.dwt');
        }

        if ($act == 'xm') {
            $this->smarty->assign('zc_title', $GLOBALS['_LANG']['zc_search']);

            $cateParents = $this->crowdFundService->getZcCategoryParents();
            $cate_one = $cateParents['cate_one'];
            $cate_two = $cateParents['cate_two'];

            $zc_arr = $this->crowdFundService->getZcProjectList();

            $gengduo = count($zc_arr);
            $new_zc_arr = [];
            $i = 0;
            foreach ($zc_arr as $value) {
                if ($i >= 12) {
                    break;
                }
                $new_zc_arr[] = $value;
                $i++;
            }

            $zong_page = ceil($gengduo / 12);
            $page_arr = [];
            for ($i = 0; $i < $zong_page; $i++) {
                $page_arr[] = $i + 1;
            }

            $this->smarty->assign('page_arr', $page_arr);
            $this->smarty->assign('cate_one', $cate_one);
            $this->smarty->assign('cate_two', $cate_two);
            $this->smarty->assign('zc_arr', $new_zc_arr);

            return $this->smarty->display('crowdfunding.dwt');
        }

        if ($act == 'search_quanbu') {
            $wenzi = addslashes(request()->input('wenzi', ''));

            $zc_arr = $this->crowdFundService->getZcProjectList($wenzi);

            $gengduo = count($zc_arr);
            $new_zc_arr = [];
            $i = 0;
            foreach ($zc_arr as $value) {
                if ($i >= 12) {
                    break;
                }
                $new_zc_arr[] = $value;
                $i++;
            }

            $zong_page = ceil($gengduo / 12);
            $page_arr = [];
            for ($i = 0; $i < $zong_page; $i++) {
                $page_arr[] = $i + 1;
            }

            $this->smarty->assign('page_arr', $page_arr);
            $this->smarty->assign('zc_arr', $new_zc_arr);
            $result = $this->smarty->fetch('library/zc_search.lbi');
            return response()->json($result);
        }

        if ($act == 'search_cate') {
            $code = (int)request()->input('code', 0);
            $wenzi = addslashes(request()->input('wenzi', ''));

            $cateParents = $this->crowdFundService->getZcCategoryParents($code, 1);
            $zc_arr = $this->crowdFundService->getZcProjectList($wenzi, $cateParents['str_id']);

            $gengduo = count($zc_arr);
            $new_zc_arr = [];
            $i = 0;
            foreach ($zc_arr as $value) {
                if ($i >= 12) {
                    break;
                }
                $new_zc_arr[] = $value;
                $i++;
            }

            $zong_page = ceil($gengduo / 12);
            $page_arr = [];
            for ($i = 0; $i < $zong_page; $i++) {
                $page_arr[] = $i + 1;
            }

            $this->smarty->assign('page_arr', $page_arr);
            $this->smarty->assign('zc_arr', $new_zc_arr);
            $result = $this->smarty->fetch('library/zc_search.lbi');
            return response()->json($result);
        }

        if ($act == 'search_cate_child') {
            $code = (int)request()->input('code', 0);
            $wenzi = addslashes(request()->input('wenzi', ''));

            $zc_arr = $this->crowdFundService->getZcProjectList($wenzi, $code);

            $gengduo = count($zc_arr);
            $new_zc_arr = [];
            $i = 0;
            foreach ($zc_arr as $value) {
                if ($i >= 12) {
                    break;
                }
                $new_zc_arr[] = $value;
                $i++;
            }

            $zong_page = ceil($gengduo / 12);
            $page_arr = [];
            for ($i = 0; $i < $zong_page; $i++) {
                $page_arr[] = $i + 1;
            }

            $this->smarty->assign('page_arr', $page_arr);
            $this->smarty->assign('zc_arr', $new_zc_arr);
            $result = $this->smarty->fetch('library/zc_search.lbi');
            return response()->json($result);
        }

        if ($act == 'search_paixu_tid') {
            $tid = (int)request()->input('id', 0);
            $sig = addslashes(request()->input('sig', ''));
            $wenzi = addslashes(request()->input('wenzi', ''));

            $zc_arr = $this->crowdFundService->getZcProjectList($wenzi, $tid, $sig);

            //默认是2个，如果是3就加3
            $gengduo = count($zc_arr);
            $new_zc_arr = [];
            $i = 0;
            foreach ($zc_arr as $value) {
                if ($i >= 12) {
                    break;
                }
                $new_zc_arr[] = $value;
                $i++;
            }

            $zong_page = ceil($gengduo / 12);
            $page_arr = [];
            for ($i = 0; $i < $zong_page; $i++) {
                $page_arr[] = $i + 1;
            }

            $this->smarty->assign('page_arr', $page_arr);
            $this->smarty->assign('zc_arr', $new_zc_arr);
            $result = $this->smarty->fetch('library/zc_search.lbi');
            return response()->json($result);
        }

        if ($act == 'search_paixu_pid_zero') {
            $sig = addslashes(request()->input('sig', ''));
            $wenzi = addslashes(request()->input('wenzi', ''));

            $zc_arr = $this->crowdFundService->getZcProjectList($wenzi, 0, $sig);

            //默认是2个，如果是3就加3
            $gengduo = count($zc_arr);
            $new_zc_arr = [];
            $i = 0;
            foreach ($zc_arr as $value) {
                if ($i >= 12) {
                    break;
                }
                $new_zc_arr[] = $value;
                $i++;
            }

            $zong_page = ceil($gengduo / 12);
            $page_arr = [];
            for ($i = 0; $i < $zong_page; $i++) {
                $page_arr[] = $i + 1;
            }

            $this->smarty->assign('page_arr', $page_arr);
            $this->smarty->assign('zc_arr', $new_zc_arr);
            $result = $this->smarty->fetch('library/zc_search.lbi');
            return response()->json($result);
        }

        if ($act == 'search_paixu_pid') {
            $pid = (int)request()->input('id', 0);
            $sig = addslashes(request()->input('sig', ''));
            $wenzi = addslashes(request()->input('wenzi', ''));

            $cateParents = $this->crowdFundService->getZcCategoryParents($pid, 1);
            $zc_arr = $this->crowdFundService->getZcProjectList($wenzi, $cateParents['str_id'], $sig);

            //默认是2个，如果是3就加3
            $gengduo = count($zc_arr);
            $new_zc_arr = [];
            $i = 0;
            foreach ($zc_arr as $value) {
                if ($i >= 12) {
                    break;
                }
                $new_zc_arr[] = $value;
                $i++;
            }

            $zong_page = ceil($gengduo / 12);
            $page_arr = [];
            for ($i = 0; $i < $zong_page; $i++) {
                $page_arr[] = $i + 1;
            }

            $this->smarty->assign('page_arr', $page_arr);
            $this->smarty->assign('zc_arr', $new_zc_arr);
            $result = $this->smarty->fetch('library/zc_search.lbi');
            return response()->json($result);
        }

        if ($act == 'page_tid') {
            $tid = (int)request()->input('id', 0);
            $sig = addslashes(request()->input('sig', ''));
            $wenzi = addslashes(request()->input('wenzi', ''));
            $page = (int)request()->input('page', 0);

            $zc_arr = $this->crowdFundService->getZcProjectList($wenzi, $tid, $sig);

            //默认是2个，如果是3就加3
            $gengduo = count($zc_arr);
            $new_zc_arr = [];
            $i = 0;
            $start_i = ($page - 1) * 12;
            $end_i = $start_i + 12;
            foreach ($zc_arr as $value) {
                if ($i >= $end_i) {
                    break;
                }

                if ($i >= $start_i) {
                    $new_zc_arr[] = $value;
                }
                $i++;
            }

            $zong_page = ceil($gengduo / 12);
            $page_arr = [];
            for ($i = 0; $i < $zong_page; $i++) {
                $page_arr[] = $i + 1;
            }

            $this->smarty->assign('page', $page);
            $this->smarty->assign('page_arr', $page_arr);
            $this->smarty->assign('zc_arr', $new_zc_arr);
            $result = $this->smarty->fetch('library/zc_search.lbi');
            return response()->json($result);
        }

        if ($act == 'page_pid_zero') {
            $pid = (int)request()->input('id', 0);
            $sig = addslashes(request()->input('sig', ''));
            $wenzi = addslashes(request()->input('wenzi', ''));
            $page = (int)request()->input('page', 0);

            $zc_arr = $this->crowdFundService->getZcProjectList($wenzi, 0, $sig);

            //默认是2个，如果是3就加3
            $gengduo = count($zc_arr);
            $new_zc_arr = [];
            $i = 0;
            $start_i = ($page - 1) * 12;
            $end_i = $start_i + 12;
            foreach ($zc_arr as $value) {
                if ($i >= $end_i) {
                    break;
                }

                if ($i >= $start_i) {
                    $new_zc_arr[] = $value;
                }
                $i++;
            }

            $zong_page = ceil($gengduo / 12);
            $page_arr = [];
            for ($i = 0; $i < $zong_page; $i++) {
                $page_arr[] = $i + 1;
            }

            $this->smarty->assign('page', $page);
            $this->smarty->assign('page_arr', $page_arr);
            $this->smarty->assign('zc_arr', $new_zc_arr);
            $result = $this->smarty->fetch('library/zc_search.lbi');
            return response()->json($result);
        }

        if ($act == 'page_pid') {
            $pid = (int)request()->input('id', 0);
            $sig = addslashes(request()->input('sig', ''));
            $wenzi = addslashes(request()->input('wenzi', ''));
            $page = (int)request()->input('page', 0);

            $cateParents = $this->crowdFundService->getZcCategoryParents($pid, 1);
            $zc_arr = $this->crowdFundService->getZcProjectList($wenzi, $cateParents['str_id'], $sig);

            //默认是2个，如果是3就加3
            $gengduo = count($zc_arr);
            $new_zc_arr = [];
            $i = 0;
            $start_i = ($page - 1) * 12;
            $end_i = $start_i + 12;
            foreach ($zc_arr as $value) {
                if ($i >= $end_i) {
                    break;
                }

                if ($i >= $start_i) {
                    $new_zc_arr[] = $value;
                }
                $i++;
            }

            $zong_page = ceil($gengduo / 12);
            $page_arr = [];
            for ($i = 0; $i < $zong_page; $i++) {
                $page_arr[] = $i + 1;
            }

            $this->smarty->assign('page', $page);
            $this->smarty->assign('page_arr', $page_arr);
            $this->smarty->assign('zc_arr', $new_zc_arr);
            $result = $this->smarty->fetch('library/zc_search.lbi');
            return response()->json($result);
        }

        if ($act == 'rm_focus') {
            $pid = (int)request()->input('id', 0);
            ZcFocus::where('pid', $pid)->delete();

            ZcProject::where('id', $pid)->increment('focus_num', -1);

            return show_message($GLOBALS['_LANG']['lang_crowd_focus_cancel'], $GLOBALS['_LANG']['back_up_page'], 'user_crowdfund.php?act=crowdfunding');
        }

        //删除浏览历史 by wu
        if ($act == 'delete_zc_history') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            cookie()->queue('ECS[zc_history]', $zcgoods_id, 60 * 24 * 30);

            $result['error'] = 1;
            return response()->json($result);
        }
    }
}
