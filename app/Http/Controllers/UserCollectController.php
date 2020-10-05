<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\CollectBrand;
use App\Models\CollectGoods;
use App\Models\CollectStore;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Article\ArticleCommonService;
use App\Services\Common\CommonService;
use App\Services\User\UserCollectGoodsService;
use App\Services\User\UserCommonService;

class UserCollectController extends InitController
{
    protected $dscRepository;
    protected $userCommonService;
    protected $config;
    protected $articleCommonService;
    protected $commonRepository;
    protected $commonService;
    protected $userCollectGoodsService;

    public function __construct(
        DscRepository $dscRepository,
        UserCommonService $userCommonService,
        ArticleCommonService $articleCommonService,
        CommonRepository $commonRepository,
        CommonService $commonService,
        UserCollectGoodsService $userCollectGoodsService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->userCommonService = $userCommonService;
        $this->config = $this->dscRepository->dscConfig();
        $this->articleCommonService = $articleCommonService;
        $this->commonRepository = $commonRepository;
        $this->commonService = $commonService;
        $this->userCollectGoodsService = $userCollectGoodsService;
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

        $this->dscRepository->helpersLang(['user']);

        $user_id = session('user_id', 0);
        $action = addslashes(trim(request()->input('act', 'default')));
        $action = $action ? $action : 'default';

        $not_login_arr = $this->userCommonService->notLoginArr('collect');

        $ui_arr = $this->userCommonService->uiArr('collect');

        /* 未登录处理 */
        $requireUser = $this->userCommonService->requireLogin(session('user_id'), $action, $not_login_arr, $ui_arr);
        $action = $requireUser['action'];
        $require_login = $requireUser['require_login'];

        if ($require_login == 1) {
            //未登录提交数据。非正常途径提交数据！
            return dsc_header('location:' . $this->dscRepository->dscUrl('user.php'));
        }

        /* 区分登录注册底部样式 */
        $footer = $this->userCommonService->userFooter();
        if (in_array($action, $footer)) {
            $this->smarty->assign('footer', 1);
        }

        $is_apply = $this->userCommonService->merchantsIsApply($user_id);
        $this->smarty->assign('is_apply', $is_apply);

        $user_default_info = $this->userCommonService->getUserDefault($user_id);
        $this->smarty->assign('user_default_info', $user_default_info);

        /* 如果是显示页面，对页面进行相应赋值 */
        if (in_array($action, $ui_arr)) {
            assign_template();
            $position = assign_ur_here(0, $GLOBALS['_LANG']['user_core']);
            $this->smarty->assign('page_title', $position['title']); // 页面标题
            $categories_pro = get_category_tree_leve_one();
            $this->smarty->assign('categories_pro', $categories_pro); // 分类树加强版
            $this->smarty->assign('ur_here', $position['ur_here']);

            $this->smarty->assign('car_off', $this->config['anonymous_buy']);

            /* 是否显示积分兑换 */
            if (!empty($this->config['points_rule']) && unserialize($this->config['points_rule'])) {
                $this->smarty->assign('show_transform_points', 1);
            }

            $this->smarty->assign('helps', $this->articleCommonService->getShopHelp());        // 网店帮助
            $this->smarty->assign('data_dir', DATA_DIR);   // 数据目录
            $this->smarty->assign('action', $action);
            $this->smarty->assign('lang', $GLOBALS['_LANG']);

            $info = $user_default_info;

            if ($user_id) {
                //验证邮箱
                if (isset($info['is_validated']) && !$info['is_validated'] && $this->config['user_login_register'] == 1) {
                    $Location = url('/') . '/' . 'user.php?act=user_email_verify';
                    return dsc_header('location:' . $Location);
                }
            }

            $count = AdminUser::where('ru_id', session('user_id'))->count();
            if ($count) {
                $is_merchants = 1;
            } else {
                $is_merchants = 0;
            }

            $this->smarty->assign('is_merchants', $is_merchants);
            $this->smarty->assign('shop_reg_closed', $this->config['shop_reg_closed']);

            $this->smarty->assign('filename', 'user');
        } else {
            if (!in_array($action, $not_login_arr) || $user_id == 0) {
                $referer = '?back_act=' . urlencode(request()->server('REQUEST_URI'));
                $back_act = $this->dscRepository->dscUrl('user.php' . $referer);
                return dsc_header('location:' . $back_act);
            }
        }

        $supplierEnabled = $this->commonRepository->judgeSupplierEnabled();
        $wholesaleUse = $this->commonService->judgeWholesaleUse(session('user_id'), 1);
        $wholesale_use = $supplierEnabled && $wholesaleUse ? 1 : 0;

        $this->smarty->assign('wholesale_use', $wholesale_use);

        /* ------------------------------------------------------ */
        //-- 显示收藏商品列表
        /* ------------------------------------------------------ */
        if ($action == 'collection_list') {
            load_helper('clips');

            $page = (int)request()->input('page', 1);

            $record_count = CollectGoods::where('user_id', $user_id)->count();

            $collection_goods = $this->userCollectGoodsService->getCollectionGoods($user_id, $record_count, $page, 'collection_goods_gotoPage', 12, $warehouse_id, $area_id, $area_city);

            $this->smarty->assign('goods_list', $collection_goods['goods_list']);
            $this->smarty->assign('pager', $collection_goods['pager']);
            $this->smarty->assign('count', $collection_goods['record_count']);
            $this->smarty->assign('size', $collection_goods['size']);

            $this->smarty->assign('url', url('/') . '/');
            $lang_list = [
                'UTF8' => $GLOBALS['_LANG']['charset']['utf8'],
                'GB2312' => $GLOBALS['_LANG']['charset']['zh_cn'],
                'BIG5' => $GLOBALS['_LANG']['charset']['zh_tw'],
            ];
            $this->smarty->assign('lang_list', $lang_list);
            $this->smarty->assign('user_id', $user_id);

            return $this->smarty->display('user_clips.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 显示收藏店铺列表
        /* ------------------------------------------------------ */
        elseif ($action == 'store_list') {
            load_helper('clips');

            $page = (int)request()->input('page', 1);

            $record_count = CollectStore::where('user_id', $user_id)->count();

            $size = 5;
            $collection_store = get_collection_store($user_id, $record_count, $page, 'collection_store_gotoPage', $size, $warehouse_id, $area_id, $area_city);

            $this->smarty->assign('store_list', $collection_store['store_list']);
            $this->smarty->assign('pager', $collection_store['pager']);
            $this->smarty->assign('count', $collection_store['record_count']);
            $this->smarty->assign('size', $collection_store['size']);

            $this->smarty->assign('url', url('/') . '/');
            $lang_list = [
                'UTF8' => $GLOBALS['_LANG']['charset']['utf8'],
                'GB2312' => $GLOBALS['_LANG']['charset']['zh_cn'],
                'BIG5' => $GLOBALS['_LANG']['charset']['zh_tw'],
            ];
            $this->smarty->assign('lang_list', $lang_list);
            $this->smarty->assign('user_id', $user_id);

            return $this->smarty->display('user_clips.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 关注品牌
        /* ------------------------------------------------------ */
        elseif ($action == 'focus_brand') {
            load_helper('clips');

            $page = (int)request()->input('page', 1);
            $size = 5;

            $record_count = CollectBrand::where('user_id', $user_id)->count();
            $collection_brands = get_collection_brands($user_id, $record_count, $page, 'collection_brands_gotoPage', $size, $warehouse_id, $area_id, $area_city);

            $this->smarty->assign('collection_brands', $collection_brands['brand_list']);
            $this->smarty->assign('pager', $collection_brands['pager']);
            $this->smarty->assign('count', $collection_brands['record_count']);
            $this->smarty->assign('size', $collection_brands['size']);

            $this->smarty->assign('url', url('/') . '/');
            $lang_list = [
                'UTF8' => $GLOBALS['_LANG']['charset']['utf8'],
                'GB2312' => $GLOBALS['_LANG']['charset']['zh_cn'],
                'BIG5' => $GLOBALS['_LANG']['charset']['zh_tw'],
            ];
            $this->smarty->assign('lang_list', $lang_list);
            $this->smarty->assign('user_id', $user_id);

            return $this->smarty->display('user_clips.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 删除收藏的商品
        /* ------------------------------------------------------ */
        elseif ($action == 'delete_collection') {
            load_helper('clips');

            //与默认页收藏区分
            $type = (int)request()->input('type', 0);

            $collection_id = (int)request()->input('collection_id', 0);

            CollectGoods::where('rec_id', $collection_id)->where('user_id', $user_id)->delete();

            if ($type == 1) {
                return dsc_header("Location: user_collect.php?act=collection_list\n");
            } else {
                return dsc_header("Location: user.php\n");
            }
        }

        /* ------------------------------------------------------ */
        //-- 添加关注商品
        /* ------------------------------------------------------ */
        elseif ($action == 'add_to_attention') {
            $rec_id = (int)request()->input('rec_id', 0);
            $goods_id = (int)request()->input('goods_id', 0);

            if ($rec_id) {
                CollectGoods::where('rec_id', $rec_id)->where('user_id', $user_id)->update(['is_attention' => 1]);

                //获取关注此商品的会员数
                $attention_num = CollectGoods::where('goods_id', $goods_id)->where('is_attention', 1)->count();

                $num = ['goods_id' => $goods_id, 'user_attention_number' => $attention_num];
                update_attention_num($goods_id, $num);
            }

            return dsc_header("Location: user_collect.php?act=collection_list\n");
        }

        /* ------------------------------------------------------ */
        //-- 添加关注商品
        /* ------------------------------------------------------ */
        elseif ($action == 'del_attention') {
            $rec_id = (int)request()->input('rec_id', 0);
            $goods_id = (int)request()->input('goods_id', 0);

            if ($rec_id) {
                CollectGoods::where('rec_id', $rec_id)->where('user_id', $user_id)->update(['is_attention' => 0]);

                //获取关注此商品的会员数
                $attention_num = CollectGoods::where('goods_id', $goods_id)->where('is_attention', 1)->count();

                $num = ['goods_id' => $goods_id, 'user_attention_number' => $attention_num];
                update_attention_num($goods_id, $num);
            }
            return dsc_header("Location: user_collect.php?act=collection_list\n");
        }

        /* ------------------------------------------------------ */
        //-- 添加收藏商品(ajax)
        /* ------------------------------------------------------ */
        elseif ($action == 'collect') {
            $result = ['error' => 0, 'message' => '', 'url' => ''];

            $request = get_request_filter(request()->all(), 2);

            $goods_id = isset($request['id']) ? intval($request['id']) : 0;
            $cat_id = isset($request['cat_id']) ? intval($request['cat_id']) : 0;
            $merchant_id = isset($request['merchant_id']) ? intval($request['merchant_id']) : 0;
            $script_name = isset($request['script_name']) ? htmlspecialchars(trim($request['script_name'])) : '';
            $cur_url = isset($request['cur_url']) ? htmlspecialchars(trim($request['cur_url'])) : '';

            if (session('user_id') == 0) {
                if ($script_name != '') {
                    if ($script_name == 'category') {
                        $result['url'] = get_return_category_url($cat_id);
                    } elseif ($script_name == 'search' || $script_name == 'merchants_shop') {
                        $result['url'] = $cur_url;
                    } elseif ($script_name == 'merchants_store_shop') {
                        $result['url'] = get_return_store_shop_url($merchant_id);
                    }
                }

                $result['goods_url'] = $this->dscRepository->buildUri('goods', ['gid' => $goods_id], $script_name);

                $result['error'] = 2;
                $result['message'] = $GLOBALS['_LANG']['login_please'];
                return response()->json($result);
            } else {
                /* 检查是否已经存在于用户的收藏夹 */
                $count = CollectGoods::where('user_id', $user_id)->where('goods_id', $goods_id)->count();

                if ($count > 0) {
                    $result['error'] = 1;
                    $result['message'] = $GLOBALS['_LANG']['collect_existed'];
                    return response()->json($result);
                } else {
                    $time = gmtime();

                    $other = [
                        'user_id' => $user_id,
                        'goods_id' => $goods_id,
                        'add_time' => $time
                    ];
                    $rec_id = CollectGoods::insertGetId($other);

                    if ($rec_id) {
                        $user_id = session('user_id', 0);
                        $result['collect_count'] = CollectGoods::where('goods_id', $goods_id)->where('user_id', $user_id)->count();

                        $result['error'] = 0;
                        $result['message'] = $GLOBALS['_LANG']['collect_success'];
                        return response()->json($result);
                    } else {
                        $result['error'] = 1;
                        $result['message'] = $this->db->errorMsg();
                        return response()->json($result);
                    }
                }
            }
        }

    }
}
