<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Image;
use App\Models\Brand;
use App\Models\Category;
use App\Models\FavourableActivity;
use App\Models\Goods;
use App\Models\RegionStore;
use App\Models\UserRank;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Category\CategoryService;
use App\Services\Favourable\FavourableManageService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Store\StoreCommonService;

/**
 * 管理中心优惠活动管理
 */
class FavourableController extends InitController
{
    protected $categoryService;
    protected $baseRepository;
    protected $merchantCommonService;
    protected $favourableManageService;
    protected $dscRepository;
    protected $storeCommonService;

    public function __construct(
        CategoryService $categoryService,
        FavourableManageService $favourableManageService,
        BaseRepository $baseRepository,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository,
        StoreCommonService $storeCommonService
    )
    {
        $this->categoryService = $categoryService;
        $this->baseRepository = $baseRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->favourableManageService = $favourableManageService;
        $this->dscRepository = $dscRepository;
        $this->storeCommonService = $storeCommonService;
    }

    public function index()
    {
        load_helper('goods');


        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

        $adminru = get_admin_ru_id();
        $admin_rs_id = $adminru['rs_id'];
        //ecmoban模板堂 --zhuo start
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end
        //卖场促销 liu
        if ($adminru['rs_id']) {
            $this->smarty->assign('is_rs', true);
        } else {
            $this->smarty->assign('is_rs', false);
        }
        /*------------------------------------------------------ */
        //-- 活动列表页
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'list') {
            admin_priv('favourable');

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['favourable_list']);
            $this->smarty->assign('action_link', ['href' => 'favourable.php?act=add', 'text' => $GLOBALS['_LANG']['add_favourable']]);

            $list = $this->favourableManageService->favourableList($adminru['ru_id'], $admin_rs_id);

            $this->smarty->assign('favourable_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);
            $this->smarty->assign('full_page', 1);

            /* 卖场列表 */
            $this->smarty->assign('region_store_list', get_region_store_list());

            //区分自营和店铺
            self_seller(basename(request()->getRequestUri()));

            /* 显示商品列表页面 */

            return $this->smarty->display('favourable_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 分页、排序、查询
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'query') {
            $list = $this->favourableManageService->favourableList($adminru['ru_id'], $admin_rs_id);

            $this->smarty->assign('favourable_list', $list['item']);
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $sort_flag = sort_flag($list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('favourable_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 删除
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('favourable');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);
            $favourable = favourable_info($id, 'seller');
            if (empty($favourable)) {
                return make_json_error($GLOBALS['_LANG']['favourable_not_exist']);
            }
            $name = $favourable['act_name'];
            $this->dscRepository->getDelBatch('', $id, ['activity_thumb'], 'act_id', FavourableActivity::whereRaw(1), 1); //删除图片

            FavourableActivity::where('act_id', $id)->delete();

            /* 记日志 */
            admin_log($name, 'remove', 'favourable');

            /* 清除缓存 */
            clear_cache_files();

            $url = 'favourable.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }


        /*------------------------------------------------------ */
        //-- 批量操作
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch') {
            /* 检查权限 */
            $check_auth = check_authz_json('favourable');
            if ($check_auth !== true) {
                return $check_auth;
            }

            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['not_select_data'], 1);
            }
            $ids = !empty($_POST['checkboxes']) ? $_POST['checkboxes'] : 0;

            if (isset($_POST['type'])) {
                // 删除
                if ($_POST['type'] == 'batch_remove') {
                    $this->dscRepository->getDelBatch($ids, '', ['activity_thumb'], 'act_id', FavourableActivity::whereRaw(1), 1);
                    /* 删除记录 */
                    $ids = $this->baseRepository->getExplode($ids);
                    FavourableActivity::whereIn('act_id', $ids)->delete();
                    /* 记日志 */
                    admin_log('', 'batch_remove', 'favourable');

                    /* 清除缓存 */
                    clear_cache_files();

                    $links[] = ['text' => $GLOBALS['_LANG']['back_favourable_list'], 'href' => 'favourable.php?act=list&' . list_link_postfix()];
                    return sys_msg($GLOBALS['_LANG']['batch_drop_ok'], 0, $links);
                } // 审核
                elseif ($_POST['type'] == 'review_to') {
                    // review_status = 3审核通过 2审核未通过
                    $review_status = $_POST['review_status'];

                    $data = ['review_status' => $review_status];
                    $ids = $this->baseRepository->getExplode($ids);

                    $res = FavourableActivity::whereIn('act_id', $ids)->update($data);
                    if ($res > 0) {
                        $lnk[] = ['text' => $GLOBALS['_LANG']['back_favourable_list'], 'href' => 'favourable.php?act=list&seller_list=1&' . list_link_postfix()];
                        return sys_msg($GLOBALS['_LANG']['coupons_adopt_status_set_success'], 0, $lnk);
                    }
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 修改排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_sort_order') {
            $check_auth = check_authz_json('favourable');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $data = ['sort_order' => $val];
            FavourableActivity::where('act_id', $id)->update($data);
            return make_json_result($val);
        }

        /*------------------------------------------------------ */
        //-- 添加、编辑
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            /* 检查权限 */
            admin_priv('favourable');

            /* 是否添加 */
            $is_add = $_REQUEST['act'] == 'add';
            $this->smarty->assign('form_action', $is_add ? 'insert' : 'update');

            /* 初始化、取得优惠活动信息 */
            if ($is_add) {
                $ru_id = $adminru['ru_id']; //ecmoban模板堂 --zhuo
                $favourable = [
                    'act_id' => 0,
                    'act_name' => '',
                    'start_time' => date('Y-m-d H:i:s', time() + 86400),
                    'end_time' => date('Y-m-d H:i:s', time() + 4 * 86400),
                    'user_rank' => '',
                    'act_range' => FAR_ALL,
                    'act_range_ext' => '',
                    'min_amount' => 0,
                    'max_amount' => 0,
                    'act_type' => FAT_GOODS,
                    'act_type_ext' => 0,
                    'activity_thumb' => '',
                    'user_id' => $ru_id, //ecmoban模板堂 --zhuo
                    'gift' => []
                ];
            } else {
                if (empty($_GET['id'])) {
                    return sys_msg('invalid param');
                }
                $id = intval($_GET['id']);
                $favourable = favourable_info($id, 'seller');
                if (empty($favourable)) {
                    return sys_msg($GLOBALS['_LANG']['favourable_not_exist']);
                } else {
                    if ($favourable['rs_id'] && $admin_rs_id) {
                        $favourable['can_not_audit'] = 1;
                    }
                }

                $ru_id = $favourable['user_id']; //ecmoban模板堂 --zhuo
            }

            $favourable['activity_thumb'] = get_image_path($favourable['activity_thumb']);

            $this->smarty->assign('favourable', $favourable);

            /* 取得用户等级 */
            $user_rank_list = [];

            $res = UserRank::select('rank_id', 'rank_name');
            $res = $this->baseRepository->getToArrayGet($res);

            foreach ($res as $row) {
                $row['checked'] = strpos(',' . $favourable['user_rank'] . ',', ',' . $row['rank_id'] . ',') !== false;
                $user_rank_list[] = $row;
            }

            if (empty($user_rank_list)) {
                $user_rank_list[] = [
                    'rank_id' => 0,
                    'rank_name' => $GLOBALS['_LANG']['not_user'],
                    'checked' => strpos(',' . $favourable['user_rank'] . ',', ',0,') !== false
                ];
            }

            $this->smarty->assign('user_rank_list', $user_rank_list);

            /* 取得优惠范围 */
            $act_range_ext = [];
            if ($favourable['act_range'] != FAR_ALL && !empty($favourable['act_range_ext'])) {
                $favourable['act_range_ext'] = $this->baseRepository->getExplode($favourable['act_range_ext']);
                if ($favourable['act_range'] == FAR_CATEGORY) {
                    $act_range_ext = Category::selectRaw('cat_id AS id, cat_name AS name')
                        ->whereIn('cat_id', $favourable['act_range_ext']);
                } elseif ($favourable['act_range'] == FAR_BRAND) {
                    $act_range_ext = Brand::selectRaw('brand_id AS id, brand_name AS name')
                        ->whereIn('brand_id', $favourable['act_range_ext']);
                } else {
                    $act_range_ext = Goods::selectRaw('goods_id AS id, goods_name AS name')
                        ->whereIn('goods_id', $favourable['act_range_ext']);
                }
                $act_range_ext = $this->baseRepository->getToArrayGet($act_range_ext);
            }

            $this->smarty->assign('act_range_ext', $act_range_ext);

            /* 赋值时间控件的语言 */
            $this->smarty->assign('cfg_lang', $GLOBALS['_CFG']['lang']);

            $this->smarty->assign('region_store_enabled', $GLOBALS['_CFG']['region_store_enabled']);

            /* 显示模板 */
            if ($is_add) {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_favourable']);
            } else {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit_favourable']);
            }
            $href = 'favourable.php?act=list';
            if (!$is_add) {
                $href .= '&' . list_link_postfix();
            }
            $this->smarty->assign('action_link', ['href' => $href, 'text' => $GLOBALS['_LANG']['favourable_list']]);

            return $this->smarty->display('favourable_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加、编辑后提交
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            /* 检查权限 */
            admin_priv('favourable');

            $ru_id = isset($_REQUEST['ru_id']) ? intval($_REQUEST['ru_id']) : 0;
            $userFav_type = isset($_POST['userFav_type']) ? intval($_POST['userFav_type']) : 0;

            if ($userFav_type) {
                $userFav_type_ext = '';
            } else {
                $userFav_type_ext = isset($_POST['ext_ids']) && !empty($_POST['ext_ids']) ? trim($_POST['ext_ids']) : '';
            }

            /* 是否添加 */
            $is_add = $_REQUEST['act'] == 'insert';

            // 验证商品是否只参与一个活动 by qin
            $now = gmtime();
            $act_id = intval($_POST['id']);
            $act_range = isset($_POST['act_range']) ? intval($_POST['act_range']) : 0;
            $act_name = isset($_POST['act_name']) ? addslashes($_POST['act_name']) : '';

            $act_range_ext = isset($_POST['act_range_ext']) && !empty($_POST['act_range_ext']) ? implode(",", $_POST['act_range_ext']) : '';
            if ($is_add) {
                $favourable_info['user_id'] = $ru_id;
            } else {
                $favourable_info = FavourableActivity::where('act_id', $act_id);
                $favourable_info = $this->baseRepository->getToArrayFirst($favourable_info);
            }

            // 按分类优惠活动包含的所有商品
            $act_range_ext_cat = $this->favourableManageService->getActRangeExt(FAR_CATEGORY, $act_id);
            $goods_list_cat = $this->favourableManageService->getRangeGoods(FAR_CATEGORY, $act_range_ext_cat, 'cat_id', $favourable_info['user_id']);

            // 按品牌优惠活动包含的所有商品
            $act_range_ext_brand = $this->favourableManageService->getActRangeExt(FAR_BRAND, $act_id);
            $goods_list_brand = $this->favourableManageService->getRangeGoods(FAR_BRAND, $act_range_ext_brand, 'brand_id', $favourable_info['user_id']);

            // 按商品优惠活动包含的所有商品
            $act_range_ext_goods = $this->favourableManageService->getActRangeExt(FAR_GOODS, $act_id);
            $goods_list_goods = $this->favourableManageService->getRangeGoods(FAR_GOODS, $act_range_ext_goods, 'goods_id', $favourable_info['user_id']);
            if ($GLOBALS['_CFG']['region_store_enabled']) {
                if ($is_add) {
                    $rs_id = $adminru['rs_id'];
                } else {
                    $rs_id = $favourable_info['rs_id'];
                }
                $mer_ids = get_favourable_merchants(intval($userFav_type), $userFav_type_ext, $rs_id, 1);
                if (!$mer_ids && $userFav_type_ext) {
                    return sys_msg($GLOBALS['_LANG']['rs_no_merchants_notice'], 1);
                }
            }

            /* 检查名称是否重复 */
            $act_name = $this->dscRepository->subStr($act_name, 255, false);
            $is_only = FavourableActivity::where('act_name', $act_name)->where('act_id', '<>', $act_id)->count();
            if ($is_only > 0) {
                return sys_msg($GLOBALS['_LANG']['act_name_exists'], 1);
            }

            /* 检查享受优惠的会员等级 */
            if (!isset($_POST['user_rank'])) {
                return sys_msg($GLOBALS['_LANG']['pls_set_user_rank'], 1);
            }

            /* 检查优惠范围扩展信息 */
            if (intval($_POST['act_range']) > 0 && !isset($_POST['act_range_ext'])) {
                return sys_msg($GLOBALS['_LANG']['pls_set_act_range'], 1);
            }

            /* 检查金额上下限 */
            $min_amount = floatval($_POST['min_amount']) >= 0 ? floatval($_POST['min_amount']) : 0;
            $max_amount = floatval($_POST['max_amount']) >= 0 ? floatval($_POST['max_amount']) : 0;
            if ($max_amount > 0 && $min_amount > $max_amount) {
                return sys_msg($GLOBALS['_LANG']['amount_error'], 1);
            }

            /* 取得赠品 */
            $gift = [];
            if (intval($_POST['act_type']) == FAT_GOODS && isset($_POST['gift_id'])) {
                foreach ($_POST['gift_id'] as $key => $id) {
                    $gift[] = ['id' => $id, 'name' => $_POST['gift_name'][$key], 'price' => $_POST['gift_price'][$key]];
                }
            }

            /* 提交值 */
            $favourable = [
                'act_name' => $act_name,
                'start_time' => local_strtotime($_POST['start_time']),
                'end_time' => local_strtotime($_POST['end_time']),
                'user_rank' => isset($_POST['user_rank']) ? join(',', $_POST['user_rank']) : '0',
                'act_range' => intval($_POST['act_range']),
                'act_range_ext' => $act_range_ext,
                'min_amount' => floatval($_POST['min_amount']),
                'max_amount' => floatval($_POST['max_amount']),
                'act_type' => intval($_POST['act_type']),
                'act_type_ext' => floatval($_POST['act_type_ext']),
                'gift' => serialize($gift),
                'review_status' => empty($admin_rs_id) ? 3 : 1
            ];

            $favourable['userFav_type'] = $userFav_type;
            $favourable['userFav_type_ext'] = $userFav_type_ext;

            if ($GLOBALS['_CFG']['region_store_enabled']) {
                if ($adminru['rs_id'] && $is_add) {
                    $favourable['rs_id'] = $adminru['rs_id'];
                }
            }

            if ($favourable['act_type'] == FAT_GOODS) {
                $favourable['act_type_ext'] = round($favourable['act_type_ext']);
            }

            $activity_thumb = $image->upload_image($_FILES['activity_thumb'], 'activity_thumb');  //图片存放地址

            $this->dscRepository->getOssAddFile([$activity_thumb]);

            /* 保存数据 */
            if ($is_add) {
                //ecmoban模板堂 -- zhuo
                $favourable['user_id'] = $adminru['ru_id'];
                $favourable['activity_thumb'] = $activity_thumb;
                $act_id = FavourableActivity::insertGetId($favourable);
            } else {
                if (isset($_POST['review_status'])) {
                    $review_status = !empty($_POST['review_status']) ? intval($_POST['review_status']) : 1;
                    $review_content = !empty($_POST['review_content']) ? addslashes(trim($_POST['review_content'])) : '';

                    $favourable['review_status'] = $review_status;
                    $favourable['review_content'] = $review_content;
                }
                if (!empty($activity_thumb)) {
                    $favourable['activity_thumb'] = $activity_thumb;
                }
                FavourableActivity::where('act_id', $act_id)->update($favourable);
            }

            /* 记日志 */
            if ($is_add) {
                admin_log($favourable['act_name'], 'add', 'favourable');
            } else {
                admin_log($favourable['act_name'], 'edit', 'favourable');
            }

            /* 清除缓存 */
            clear_cache_files();

            /* 提示信息 */
            if ($is_add) {
                $links = [
                    ['href' => 'favourable.php?act=add', 'text' => $GLOBALS['_LANG']['continue_add_favourable']],
                    ['href' => 'favourable.php?act=list', 'text' => $GLOBALS['_LANG']['back_favourable_list']]
                ];
                return sys_msg($GLOBALS['_LANG']['add_favourable_ok'], 0, $links);
            } else {
                $links = [
                    ['href' => 'favourable.php?act=edit&id=' . $act_id . "&ru_id=" . $ru_id, 'text' => $GLOBALS['_LANG']['edit_favourable']],
                    ['href' => 'favourable.php?act=list&' . list_link_postfix(), 'text' => $GLOBALS['_LANG']['back_favourable_list']]
                ];
                return sys_msg($GLOBALS['_LANG']['edit_favourable_ok'], 0, $links);
            }
        }

        /*------------------------------------------------------ */
        //-- 搜索商品
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'search') {
            /* 检查权限 */
            $check_auth = check_authz_json('favourable');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $filter = dsc_decode($_GET['JSON']);
            $filter->keyword = json_str_iconv($filter->keyword);
            $ru_id = $filter->ru_id; //ecmoban模板堂 --zhuo

            if ($filter->act_range == FAR_ALL) {
                $arr[0] = [
                    'id' => 0,
                    'name' => $GLOBALS['_LANG']['js_languages']['all_need_not_search']
                ];
            } elseif ($filter->act_range == FAR_CATEGORY) {
                if ($ru_id) {
                    $arr = $this->favourableManageService->getUserCatList($ru_id);
                    $arr = $this->favourableManageService->getUserCatSearch($ru_id, $filter->keyword, $arr);
                    $arr = array_values($arr);
                } else {
                    $arr = Category::selectRaw('cat_id AS id, cat_name AS name');
                    $arr = $arr->where('cat_name', 'LIKE', '%' . mysql_like_quote($filter->keyword) . '%');
                    if ($ru_id == 0) {
                        $arr = $arr->limit(50);
                    }
                    $arr = $this->baseRepository->getToArrayGet($arr);
                }
            } elseif ($filter->act_range == FAR_BRAND) {
                $arr = Brand::selectRaw('brand_id AS id, brand_name AS name');
                $arr = $arr->where('brand_name', 'LIKE', '%' . mysql_like_quote($filter->keyword) . '%');
                if ($ru_id == 0) {
                    $arr = $arr->limit(50);
                }
                $arr = $this->baseRepository->getToArrayGet($arr);
                if ($arr) {
                    foreach ($arr as $key => $row) {
                        if ($ru_id) {
                            $arr[$key]['is_brand'] = get_seller_brand_count($row['id'], $ru_id);
                        } else {
                            $arr[$key]['is_brand'] = 1;
                        }

                        if (!($arr[$key]['is_brand'] > 0)) {
                            unset($arr[$key]);
                        }
                    }

                    $arr = array_values($arr);
                }
            } else {
                $arr = Goods::selectRaw('goods_id AS id, goods_name AS name');
                if (!empty($filter->keyword)) {
                    $keyword = $filter->keyword;
                    $arr = $arr->where(function ($query) use ($keyword) {
                        $query->where('goods_name', 'LIKE', '%' . mysql_like_quote($keyword) . '%')
                            ->orWhere('goods_sn', 'LIKE', '%' . mysql_like_quote($keyword) . '%');
                    });
                }
                $arr = $arr->where('user_id', $ru_id)->limit(50);
                $arr = $this->baseRepository->getToArrayGet($arr);
            }
            if (empty($arr)) {
                $arr = [0 => [
                    'id' => 0,
                    'name' => $GLOBALS['_LANG']['search_result_empty']
                ]];
            }

            return make_json_result($arr);
        }

        /*--------------------------------------------------------*/
        // 设置使用范围 卖场促销 liu
        /*--------------------------------------------------------*/
        elseif ($_REQUEST['act'] == 'set_use_range') {
            $result = ['content' => '', 'mode' => ''];
            $rs_id = empty($_REQUEST['rs_id']) ? 0 : intval($_REQUEST['rs_id']);
            $admin_rs_id = empty($rs_id) ? $admin_rs_id : $rs_id;

            if ($admin_rs_id) {
                $range_list = $this->favourableManageService->getMerchantsList($admin_rs_id);
                $this->smarty->assign("is_rs", $admin_rs_id);
            } else {
                $range_list = $this->favourableManageService->getRsList();
            }

            $this->smarty->assign("range_list", $range_list);
            $result['content'] = $GLOBALS['smarty']->fetch('library/favourable_select_range.lbi');
            return response()->json($result);
        }

        /*--------------------------------------------------------*/
        // 设置可使用卖场 卖场促销 liu
        /*--------------------------------------------------------*/
        elseif ($_REQUEST['act'] == 'changedrs') {

            $keyword = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $rs_ids = isset($_REQUEST['rs_ids']) ? explode(',', $_REQUEST['rs_ids']) : '';
            $type = isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 0;

            $res = RegionStore::select('rs_name', 'rs_id');
            if ($keyword) {
                $res = $res->where('rs_name', 'LIKE', '%' . $keyword . '%');
            }
            if ($rs_ids && $type == '0') {
                $rs_ids = $this->baseRepository->getExplode($rs_ids);
                $res = $res->whereIn('rs_id', $rs_ids);
            }

            if ($type == 1) {
                $list = $this->favourableManageService->getRsListSearch($res);

                $rs_list = $list['list'];
                $filter = $list['filter'];
                $filter['keyword'] = $keyword;
                $this->smarty->assign('filter', $filter);
            } else {
                /* 获取会员列表信息 */
                $rs_list = $this->baseRepository->getToArrayGet($res);
            }

            if (!empty($rs_list)) {
                foreach ($rs_list as $k => $v) {
                    if ($v['rs_id'] > 0 && in_array($v['rs_id'], $rs_ids) && !empty($rs_ids)) {
                        $rs_list[$k]['is_selected'] = 1;
                    }
                }
            }

            $this->smarty->assign('goods_count', count($rs_list));
            $this->smarty->assign('rs_list', $rs_list);
            $result['content'] = $GLOBALS['smarty']->fetch('library/region_store_list.lbi');
            return response()->json($result);
        }

        /*--------------------------------------------------------*/
        // 设置可使用的商家 卖场促销 liu
        /*--------------------------------------------------------*/
        elseif ($_REQUEST['act'] == 'changedmer') {

            $keyword = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $mer_ids = isset($_REQUEST['mer_ids']) ? explode(',', $_REQUEST['mer_ids']) : '';
            $type = isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 0;
            $rs_id = empty($_REQUEST['rs_id']) ? 0 : intval($_REQUEST['rs_id']);
            $admin_rs_id = empty($rs_id) || $admin_rs_id ? $admin_rs_id : $rs_id;

            if ($type == 1) {
                $list = $this->favourableManageService->getMerList($mer_ids, $keyword, $admin_rs_id);
                $mer_list = $list['list'];
                $filter = $list['filter'];
                $filter['keyword'] = $keyword;
                $this->smarty->assign('filter', $filter);
            } else {
                /* 获取会员列表信息 */

                $res = RegionStore::select('rs_id');
                $res = $res->with(['getRsRegion' => function ($query) use ($mer_ids, $keyword, $type) {
                    $query->select('rs_id', 'region_id');
                    $query->with(['getMerchantsShopInformationList' => function ($msi_query) use ($mer_ids, $keyword, $type) {
                        $msi_query->select('region_id', 'shop_id', 'user_id');
                        if ($mer_ids && $type == '0') {
                            $mer_ids = $this->baseRepository->getExplode($mer_ids);
                            $msi_query->whereIn('user_id', $mer_ids);
                        }
                        if ($keyword) {
                            $msi_query->whereHas('sellershopinfo', function ($ssi_query) use ($keyword) {
                                $ssi_query->where('shop_name', 'LIKE', '%' . $keyword . '%');
                            });
                        }
                    }]);

                }]);
                if ($admin_rs_id) {
                    $res = $res->where('rs_id', $admin_rs_id);
                }
                $mer_list = $this->baseRepository->getToArrayFirst($res);
            }

            if (isset($mer_list) && !empty($mer_list['get_rs_region']['get_merchants_shop_information_list'])) {
                $mer_list = $mer_list['get_rs_region']['get_merchants_shop_information_list'];
            } else {
                $mer_list = [];
            }
            if (!empty($mer_list)) {
                foreach ($mer_list as $k => $v) {
                    $mer_list[$k]['shop_name'] = $this->merchantCommonService->getShopName($v['user_id'], 1);
                    if ($v['user_id'] > 0 && in_array($v['user_id'], $mer_ids) && !empty($mer_ids)) {
                        $mer_list[$k]['is_selected'] = 1;
                    }
                }
            }

            $this->smarty->assign('goods_count', count($mer_list));
            $this->smarty->assign('mer_list', $mer_list);
            $result['content'] = $GLOBALS['smarty']->fetch('library/rs_mer_list.lbi');
            return response()->json($result);
        }
        /*--------------------------------------------------------*/
        // 促销活动 营销中心 起始页
        /*--------------------------------------------------------*/
        elseif ($_REQUEST['act'] == 'marketing_center') {

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['02_marketing_center']);

            return $this->smarty->display('marketing_center.dwt');
        }
    }

}
