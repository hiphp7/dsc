<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Image;
use App\Models\AdminAction;
use App\Models\AdminUser;
use App\Models\Goods;
use App\Models\MerchantsCategoryTemporarydate;
use App\Models\MerchantsDtFile;
use App\Models\MerchantsGrade;
use App\Models\MerchantsPercent;
use App\Models\MerchantsPrivilege;
use App\Models\MerchantsServer;
use App\Models\MerchantsShopBrand;
use App\Models\MerchantsShopBrandfile;
use App\Models\MerchantsShopInformation;
use App\Models\MerchantsStepsFields;
use App\Models\PresaleActivity;
use App\Models\Region;
use App\Models\SellerDomain;
use App\Models\SellerGrade;
use App\Models\SellerQrcode;
use App\Models\SellerShopinfo;
use App\Models\SellerShopinfoChangelog;
use App\Models\Suppliers;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Comment\CommentService;
use App\Services\CrossBorder\CrossBorderService;
use App\Services\Flow\FlowUserService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Merchant\MerchantsUsersListManageService;
use App\Services\Store\StoreCommonService;
use App\Services\Store\StoreService;

/**
 * 会员管理程序
 */
class MerchantsUsersListController extends InitController
{
    protected $storeService;
    protected $commonRepository;
    protected $merchantCommonService;
    protected $commentService;
    protected $dscRepository;
    protected $baseRepository;
    protected $merchantsUsersListManageService;
    protected $storeCommonService;
    protected $flowUserService;

    public function __construct(
        StoreService $storeService,
        CommonRepository $commonRepository,
        MerchantCommonService $merchantCommonService,
        CommentService $commentService,
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        MerchantsUsersListManageService $merchantsUsersListManageService,
        StoreCommonService $storeCommonService,
        FlowUserService $flowUserService
    )
    {
        $this->storeService = $storeService;
        $this->commonRepository = $commonRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->commentService = $commentService;
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
        $this->merchantsUsersListManageService = $merchantsUsersListManageService;
        $this->storeCommonService = $storeCommonService;
        $this->flowUserService = $flowUserService;
    }

    public function index()
    {
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        /*------------------------------------------------------ */
        //-- 申请流程列表
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'list') {
            /* 检查权限 */
            admin_priv('users_merchants');

            $this->smarty->assign('menu_select', ['action' => '17_merchants', 'current' => '02_merchants_users_list']);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['02_merchants_users_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['01_merchants_user_add'], 'href' => 'merchants_users_list.php?act=add_shop']);
            $this->smarty->assign('action_link2', ['text' => $GLOBALS['_LANG']['02_initialize_seller_rank'], 'href' => 'merchants_users_list.php?act=create_initialize_rank']);

            $users_list = $this->merchantsUsersListManageService->stepsUsersList();

            $this->smarty->assign('users_list', $users_list['users_list']);
            $this->smarty->assign('filter', $users_list['filter']);
            $this->smarty->assign('record_count', $users_list['record_count']);
            $this->smarty->assign('page_count', $users_list['page_count']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('sort_user_id', '<img src="' . __TPL__ . '/images/sort_desc.gif">');

            //获取未审核商家
            $shop_account = MerchantsShopInformation::where('merchants_audit', 0)->count();
            $this->smarty->assign('shop_account', $shop_account);

            /* 未审核店铺信息 */
            $res = MerchantsShopInformation::whereHas('getUsers', function ($query) use ($users_list) {
                if (isset($users_list['filter']) && !empty($users_list['filter']['user_name'])) {
                    $query->where('user_name', $users_list['filter']['user_name']);
                }
            });
            $res = $res->whereHas('getSellerShopinfo', function ($query) {
                $query->where('review_status', 1);
            });
            $shopinfo_account = $res->count();

            $this->smarty->assign('shopinfo_account', $shopinfo_account);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            return $this->smarty->display('merchants_users_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- ajax判断商家名称是否重复 by wu
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'check_shop_name') {

            //已设置的shop_name,店铺名如何处理的
            $shop_name = request()->input('shop_name', '');
            $adminru = request()->input('user_id', 0);

            $res = MerchantsShopInformation::where('rz_shopName', $shop_name);
            $shop_info = $this->baseRepository->getToArrayFirst($res);

            if (!empty($shop_info) && $shop_info['user_id'] != $adminru) {
                $data['error'] = 1;
            } else {
                $data['error'] = 2;
            }

            return response()->json($data);
        }

        /*------------------------------------------------------ */
        //-- ajax返回申请流程列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $users_list = $this->merchantsUsersListManageService->stepsUsersList();
            $this->smarty->assign('users_list', $users_list['users_list']);
            $this->smarty->assign('filter', $users_list['filter']);
            $this->smarty->assign('record_count', $users_list['record_count']);
            $this->smarty->assign('page_count', $users_list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $sort_flag = sort_flag($users_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('merchants_users_list.dwt'), '', ['filter' => $users_list['filter'], 'page_count' => $users_list['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 店铺详细信息
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add_shop' || $_REQUEST['act'] == 'edit_shop' || $_REQUEST['act'] == 'copy_shop') {
            /* 检查权限 */
            admin_priv('users_merchants');
            /*删除未绑定品牌 by kong*/
            MerchantsShopBrand::where(function ($quer) {
                $quer->where('user_id', 0)->orWhere('user_id', '');
            })->delete();
            $user_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            if (CROSS_BORDER === true) // 跨境多商户
            {
                $admin = app(CrossBorderService::class)->adminExists();

                if (!empty($admin)) {
                    $admin->smartyAssignSource($user_id);
                }
            }

            $shopInfo_list = $this->merchantsUsersListManageService->getStepsUserShopInfoList($user_id, 0, $_REQUEST['act']);
            $this->smarty->assign('shopInfo_list', $shopInfo_list);

            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['02_merchants_users_list'], 'href' => 'merchants_users_list.php?act=list']);

            /*获取商家等级  by kong  start*/
            $res = SellerGrade::whereRaw(1);
            $seller_grade_list = $this->baseRepository->getToArrayGet($res);
            $this->smarty->assign("seller_grade_list", $seller_grade_list);

            /*获取当前商家等级 by kong*/
            $res = MerchantsGrade::where('ru_id', $user_id);
            $res = $res->with(['getSellerGrade' => function ($query) {
                $query->select('id', 'grade_name');
            }]);
            $grade = $this->baseRepository->getToArrayFirst($res);
            $grade['grade_name'] = '';
            if (isset($grade['get_seller_grade']) && !empty(isset($grade['get_seller_grade']))) {
                $grade['grade_name'] = $grade['get_seller_grade']['grade_name'];
            }

            $this->smarty->assign("grade", $grade);

            $category_info = get_fine_category_info(0, $user_id); // 详细类目
            $this->smarty->assign('category_info', $category_info);

            $permanent_list = get_category_permanent_list($user_id);// 一级类目证件
            $this->smarty->assign('permanent_list', $permanent_list);

            $consignee = [
                'province' => '',
                'city' => '',
            ];

            $country_list = get_regions_steps();
            $province_list = get_regions_steps(1, 1);
            $city_list = get_regions_steps(2, $consignee['province']);
            $district_list = get_regions_steps(3, $consignee['city']);

            $res = MerchantsShopInformation::where('user_id', $user_id);
            $merchants = $this->baseRepository->getToArrayFirst($res);

            $this->smarty->assign('merchants', $merchants);

            $sn = 0;
            $this->smarty->assign('country_list', $country_list);
            $this->smarty->assign('province_list', $province_list);
            $this->smarty->assign('city_list', $city_list);
            $this->smarty->assign('district_list', $district_list);
            $this->smarty->assign('consignee', $consignee);
            $this->smarty->assign('sn', $sn);

            if ($_REQUEST['act'] == 'copy_shop') {
                $user_id = 0;
                $this->smarty->assign('copy_action', $_REQUEST['act']);
            }

            $this->smarty->assign('user_id', $user_id);

            if ($_REQUEST['act'] == 'edit_shop') {
                $seller_shopinfo = $this->merchantCommonService->getShopName($user_id, 2);
                $this->smarty->assign('seller_shopinfo', $seller_shopinfo);
                $this->smarty->assign('form_action', 'update_shop');
            } else {
                $res = Users::whereRaw(1);
                $user_list = $this->baseRepository->getToArrayGet($res);

                $this->smarty->assign('user_list', $user_list);
                $this->smarty->assign('form_action', 'insert_shop');
            }

            $this->smarty->assign('brand_ajax', 1);

            return $this->smarty->display('merchants_users_shopInfo.dwt');
        }

        /*------------------------------------------------------ */
        //-- 修改是否显示店铺街
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_is_street') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $shop_id = request()->input('id', 0);
            $is_street = request()->input('val', 0);

            if ($shop_id > 0) {
                $data = ['is_street' => $is_street];
                $res = MerchantsShopInformation::where('shop_id', $shop_id)->update($data);
                if ($res > 0) {
                    clear_cache_files();
                    return make_json_result($is_street);
                }
            }

            return make_json_error('invalid params');
        }

        /*------------------------------------------------------ */
        //-- 修改是否显示"在线客服" bylu
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_is_IM') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $shop_id = request()->input('id', 0);
            $is_IM = request()->input('val', 0);

            if ($shop_id > 0) {
                $data = ['is_IM' => $is_IM];
                $res = MerchantsShopInformation::where('shop_id', $shop_id)->update($data);
                if ($res > 0) {
                    clear_cache_files();
                    return make_json_result($is_IM);
                }
            }

            return make_json_error('invalid params');
        }

        /*------------------------------------------------------ */
        //-- 更新申请商家信息
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_shop' || $_REQUEST['act'] == 'update_shop') {
            /* 检查权限 */
            admin_priv('users_merchants');

            $copy_action = isset($_REQUEST['copy_action']) ? trim($_REQUEST['copy_action']) : 'update_shop';
            $brand_copy_id = isset($_REQUEST['brand_copy_id']) ? $_REQUEST['brand_copy_id'] : [];

            $user_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
            $merchants_audit = isset($_REQUEST['merchants_audit']) ? intval($_REQUEST['merchants_audit']) : 0;
            $merchants_allow = isset($_REQUEST['merchants_allow']) ? intval($_REQUEST['merchants_allow']) : 0;
            $merchants_message = isset($_REQUEST['merchants_message']) ? trim($_REQUEST['merchants_message']) : '';
            $review_goods = isset($_REQUEST['review_goods']) ? intval($_REQUEST['review_goods']) : 0;
            $shopname_audit = isset($_REQUEST['shopname_audit']) ? intval($_REQUEST['shopname_audit']) : 1; //审核使用店铺名称类型
            $old_merchants_audit = isset($_REQUEST['old_merchants_audit']) ? intval($_REQUEST['old_merchants_audit']) : 0; // by kong grade

            //获取默认等级
            $default_grade = SellerGrade::where('is_default', 1)->value('id');
            $default_grade = $default_grade ? $default_grade : 0;
            $grade_id = isset($_REQUEST['grade_id']) ? intval($_REQUEST['grade_id']) : $default_grade;
            $year_num = isset($_REQUEST['year_num']) ? intval($_REQUEST['year_num']) : 1;
            $self_run = isset($_REQUEST['self_run']) ? intval($_REQUEST['self_run']) : 0; //自营店铺
            $shop_close = isset($_REQUEST['shop_close']) ? intval($_REQUEST['shop_close']) : 1;

            if ($user_id == 0) {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'merchants_users_list.php?act=add_shop'];
                $centent = $GLOBALS['_LANG']['user_select_please'];
                return sys_msg($centent, 0, $link);
            }

            $form = $this->merchantsUsersListManageService->getAdminStepsTitleInsertForm($user_id);
            $parent = get_setps_form_insert_date($form['formName']);
            /* 判断审核状态是否改变 by kong grade */
            if ($old_merchants_audit != $merchants_audit) {
                //判断原来是否存在等级
                $grade = MerchantsGrade::where('ru_id', $user_id)->count();

                if ($merchants_audit == 1) {
                    if ($grade > 0) {
                        $data = [
                            'grade_id' => $grade_id,
                            'year_num' => $year_num
                        ];
                        MerchantsGrade::where('ru_id', $user_id)->update($data);

                    } else {
                        $add_time = gmtime();
                        $data = [
                            'ru_id' => $user_id,
                            'grade_id' => $grade_id,
                            'add_time' => $add_time,
                            'year_num' => $year_num
                        ];

                        MerchantsGrade::insert($data);

                    }
                    /* 跟新商家权限 */
                    $action_list = AdminUser::where('ru_id', $user_id)->value('action_list');
                    $action_list = $action_list ? $action_list : '';
                    if (empty($action_list)) {
                        $action_list = MerchantsPrivilege::where('grade_id', $grade_id)->value('action_list');
                        $action_list = $action_list ? $action_list : '';
                        $action = [
                            'action_list' => $action_list
                        ];
                        AdminUser::where('ru_id', $user_id)->update($action);

                    }
                } else {
                    if ($grade > 0) {
                        //审核未通过是删除该商家等级
                        MerchantsGrade::where('ru_id', $user_id)->delete();
                    }
                }
            }

            if (CROSS_BORDER === true) // 跨境多商户
            {
                $parent['source'] = isset($_REQUEST['huoyuan']) ? trim($_REQUEST['huoyuan']) : '国内仓库';
            }

            $res = MerchantsShopInformation::where('user_id', $user_id);
            $shop_info = $this->baseRepository->getToArrayFirst($res);

            $shop_info['allow_number'] = $shop_info['allow_number'] ?? 0;

            $allow_number = $shop_info['allow_number'];

            if ($_REQUEST['act'] == 'update_shop') { //更新数据

                if ($merchants_audit != 1) {
                    //审核未通过下架商家所有商品
                    $data = ['is_on_sale' => 0];
                    Goods::where('user_id', $user_id)->update($data);
                }

                //店铺关闭时，重新审核商家所有商品
                if ($shop_close != 1) {
                    //设置未审核
                    $data = ['review_status' => 1];
                    PresaleActivity::where('user_id', $user_id)->update($data);

                    //设置未审核
                    $data = ['review_status' => 1];
                    Goods::where('user_id', $user_id)->update($data);
                } else {
                    $shop_info['review_goods'] = $shop_info['review_goods'] ?? 0;
                    if ($GLOBALS['_CFG']['review_goods'] == 0 || $shop_info['review_goods'] == 0) {
                        //设置未审核
                        $data = ['review_status' => 3];
                        PresaleActivity::where('user_id', $user_id)->update($data);

                        //设置已审核通过
                        $data = ['review_status' => 3];
                        Goods::where('user_id', $user_id)->update($data);
                    }
                }

                MerchantsStepsFields::where('user_id', $user_id)->update($parent);
            } else { //插入数据
                $parent['user_id'] = $user_id;
                $parent['agreement'] = 1;

                $fid = MerchantsStepsFields::where('user_id', $user_id)->value('fid');
                $fid = $fid ? $fid : 0;

                if ($fid > 0) {
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'merchants_users_list.php?act=add_shop'];
                    $centent = $GLOBALS['_LANG']['insert_fail'];
                    return sys_msg($centent, 0, $link);
                } else {
                    MerchantsStepsFields::insert($parent);
                }
            }

            $info['merchants_audit'] = $merchants_audit;
            $info['review_goods'] = $review_goods;
            $info['self_run'] = $self_run;

            if ($merchants_allow == 1) {
                $info['steps_audit'] = 0;
                $info['allow_number'] = $allow_number + 1;
            } else {
                $ec_hopeLoginName = isset($_REQUEST['ec_hopeLoginName']) ? trim($_REQUEST['ec_hopeLoginName']) : '';
                $adminId = AdminUser::where('user_name', $ec_hopeLoginName)->where('ru_id', '<>', $user_id)->count();

                if ($adminId > 0) {
                    if ($_REQUEST['act'] == 'update_shop') {
                        $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'merchants_users_list.php?act=edit_shop&id=' . $user_id];
                    } else {
                        $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'merchants_users_list.php?act=add_shop'];
                    }

                    return sys_msg($GLOBALS['_LANG']['adminId_have'], 0, $link);
                }

                $info['steps_audit'] = 1;
            }

            $info['merchants_message'] = $merchants_message;
            $info['shop_close'] = $shop_close;

            MerchantsShopInformation::where('user_id', $user_id)->update($info);

            $seller_shopinfo = [
                'shopname_audit' => $shopname_audit,
                'shop_close' => $shop_close
            ];

            $shopinfo = $this->storeService->getShopInfo($user_id);

            if ($shopinfo) {
                SellerShopinfo::where('ru_id', $user_id)->update($seller_shopinfo);
            } else {
                if ($merchants_audit == 1) {
                    $field = MerchantsStepsFields::where('contactPhone', '<>', '')->count();
                    if ($field > 0) {
                        $steps_fields = $this->merchantCommonService->getMerchantsStepsFields($user_id);
                        $seller_shopinfo['mobile'] = $steps_fields['contactPhone'];
                    }

                    $field = MerchantsStepsFields::where('contactEmail', '<>', '')->count();
                    if ($field > 0) {
                        $steps_fields = $this->merchantCommonService->getMerchantsStepsFields($user_id);
                        $seller_shopinfo['seller_email'] = $steps_fields['contactEmail'];
                    }
                    $field = MerchantsStepsFields::where('company_adress', '<>', '')->count();
                    if ($field > 0) {
                        $steps_fields = $this->merchantCommonService->getMerchantsStepsFields($user_id);
                        $seller_shopinfo['shop_address'] = $steps_fields['company_adress'];
                    }

                    $field = MerchantsStepsFields::where('company_located', '<>', '')->count();
                    if ($field > 0) {
                        $steps_fields = $this->merchantCommonService->getMerchantsStepsFields($user_id);

                        if ($steps_fields['company_located']) {
                            $region = explode(",", $steps_fields['company_located']);

                            $seller_shopinfo['country'] = $region[0];
                            $seller_shopinfo['province'] = $region[1];
                            $seller_shopinfo['city'] = $region[2];
                            $seller_shopinfo['district'] = $region[3];
                        }
                    }

                    $field = MerchantsStepsFields::where('companyName', '<>', '')->count();
                    if ($field > 0) {
                        $steps_fields = $this->merchantCommonService->getMerchantsStepsFields($user_id);
                        $seller_shopinfo['shop_name'] = $steps_fields['companyName'];
                    }

                    $field = MerchantsStepsFields::where('company_contactTel', '<>', '')->count();
                    if ($field > 0) {
                        $steps_fields = $this->merchantCommonService->getMerchantsStepsFields($user_id);
                        $seller_shopinfo['kf_tel'] = $steps_fields['company_contactTel'];
                    }

                    $seller_shopinfo['ru_id'] = $user_id;
                    $seller_shopinfo['templates_mode'] = 1;
                    $this->db->autoExecute($this->dsc->table('seller_shopinfo'), $seller_shopinfo);
                }
            }

            if ($merchants_audit == 1) {

                //如果审核通过，判断店铺是否存在模板，不存在 导入默认模板
                $tpl_dir = storage_public(DATA_DIR . '/seller_templates/seller_tem_' . $user_id); //获取店铺模板目录
                $tpl_arr = get_dir_file_list($tpl_dir);
                if (empty($tpl_arr)) {
                    load_helper('visual');
                    $new_suffix = get_new_dir_name($user_id);
                    $dir = storage_public(DATA_DIR . "/seller_templates/seller_tem/bucket_tpl"); //原目录
                    if (!is_dir($dir)) {
                        make_dir($dir);
                    }
                    $file = $tpl_dir . "/" . $new_suffix; //目标目录
                    if (!empty($new_suffix)) {
                        //新建目录
                        if (!is_dir($file)) {
                            make_dir($file);
                        }
                        recurse_copy($dir, $file, 1);
                        $result['error'] = 0;
                    }
                    $data = ['seller_templates' => $new_suffix];
                    SellerShopinfo::where('ru_id', $user_id)->update($data);
                }

                $href = 'merchants_users_list.php?act=allot&user_id=' . $user_id;
            } else {
                $href = 'merchants_users_list.php?act=list';
            }

            if ($review_goods == 0 && $shop_close == 1) {
                $goods_date['review_status'] = 3;
                Goods::where('user_id', $user_id)->update($goods_date);
            }

            //复制店铺时  品牌入库
            if ($copy_action == 'copy_shop') {
                $brand_copy_id = $this->baseRepository->getExplode($brand_copy_id);
                $data = ['user_id' => $user_id];
                MerchantsShopBrand::whereIn('bid', $brand_copy_id)->update($data);
            }

            if ($_REQUEST['act'] == 'update_shop') {
                $centent = $GLOBALS['_LANG']['update_success'];
            } else {
                $centent = $GLOBALS['_LANG']['insert_success'];
            }

            $count = MerchantsServer::where('user_id', $user_id)->count();

            if ($count <= 0) {
                $percent_id = MerchantsPercent::where('percent_value', 100)->value('percent_id');
                $percent_id = $percent_id ? $percent_id : 0;
                if (!$percent_id) {
                    $percent_value = MerchantsPercent::selectRaw('max(percent_value) as percent')->value('percent');
                    $percent_id = MerchantsPercent::where('percent_value', $percent_value)->value('percent_id');
                }

                $other = [
                    'user_id' => $user_id,
                    'suppliers_percent' => $percent_id,
                    'cycle' => 3
                ];
                MerchantsServer::insert($other);
            }
            $Shopinfo_cache_name = 'SellerShopinfo_' . $user_id;

            cache()->forget($Shopinfo_cache_name);

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => $href];
            return sys_msg($centent, 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 商家分�        �权限
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'allot') {
            /* 检查权限 */
            admin_priv('users_merchants');

            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $user_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
            $login_name = request()->get('login_name');

            $user_id = $user_id > 0 ? $user_id : $id;

            /* 恢复商家默认权限 by wu start */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['allot_priv']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['restore_default_priv'], 'href' => 'merchants_users_list.php?act=restore_default_priv&user_id=' . $user_id]);
            /* 恢复商家默认权限 by wu end */

            $res = MerchantsShopInformation::where('user_id', $user_id);
            $merchants = $this->baseRepository->getToArrayFirst($res);

            $res = Users::where('user_id', $user_id);
            $user = $this->baseRepository->getToArrayFirst($res);

            if (empty($merchants['hopeLoginName'])) {
                $user_name = $user['user_name'];
            } else {
                $user_name = $merchants['hopeLoginName'];
            }

            //添加管理员 --start
            $ec_salt = rand(1, 9999);
            $pwd = $GLOBALS['_CFG']['merchants_prefix'] . $user_id;
            $password = md5(md5($pwd) . $ec_salt);

            /* 获取商家等级 by kong grade */
            $res = MerchantsGrade::where('ru_id', $user_id);
            $merchants_grade = $this->baseRepository->getToArrayFirst($res);

            $grade_id = $merchants_grade['grade_id'] > 0 ? $merchants_grade['grade_id'] : 0;

            //入驻默认初始权限
            $action_list = MerchantsPrivilege::where('grade_id', $grade_id)->value('action_list');
            $action_list = $action_list ? $action_list : '';

            $res = AdminUser::where('action_list', 'all');
            $row = $this->baseRepository->getToArrayFirst($res);

            $res = AdminUser::where('ru_id', $user_id)->where('user_name', $login_name)->where('parent_id', 0);
            $rows = $this->baseRepository->getToArrayFirst($res);

            if (isset($rows['action_list'])) {
                $action_list = $rows['action_list'];
            }

            $adminId = AdminUser::where('ru_id', $user_id)->where('suppliers_id', 0)->value('user_id');
            $adminId = $adminId ? $adminId : 0;

            if ($adminId > 0) {
                AdminUser::where('ru_id', $user_id)->where('parent_id', 0)->where('suppliers_id', 0)
                    ->update([
                        'user_name' => $user_name,
                        'nav_list' => $row['nav_list'],
                        'action_list' => $action_list
                    ]);
            } else {
                $other = [
                    'user_name' => $user_name,
                    'password' => $password,
                    'ec_salt' => $ec_salt,
                    'nav_list' => $row['nav_list'],
                    'action_list' => $action_list,
                    'ru_id' => $user_id
                ];
                AdminUser::insert($other);
            }
            //添加管理员 --end
            $res = AdminUser::where('user_name', $user_name);
            $user_priv = $this->baseRepository->getToArrayFirst($res);

            $admin_id = $user_priv['user_id'] ?? 0;
            $priv_str = $user_priv['action_list'] ?? '';

            if ($id == 0) {
                if ($adminId < 1) {
                    /* 取得当前管理员用户名 */
                    $current_admin_name = AdminUser::where('user_id', session('admin_id'))->value('user_name');
                    $current_admin_name = $current_admin_name ? $current_admin_name : '';

                    //商家名称
                    $shop_name = $this->merchantCommonService->getShopName($user_id, 1);

                    $field = MerchantsStepsFields::where('contactPhone', '<>', '')->count();
                    if ($field > 0) {
                        $res = MerchantsStepsFields::where('user_id', $user_id);
                        $shopinfo = $this->baseRepository->getToArrayFirst($res);
                        $shopinfo['mobile'] = $shopinfo['contactPhone'] ?? '';

                        /* 如果需要，发短信 */
                        if ($adminru['ru_id'] == 0 && $GLOBALS['_CFG']['sms_seller_signin'] == '1' && $shopinfo['mobile'] != '') {

                            //短信接口参数
                            $smsParams = [
                                'seller_name' => $shop_name,
                                'sellername' => $shop_name,
                                'login_name' => $user_name ? htmlspecialchars($user_name) : '',
                                'loginname' => $user_name ? htmlspecialchars($user_name) : '',
                                'password' => $pwd ? htmlspecialchars($pwd) : '',
                                'admin_name' => $current_admin_name ? $current_admin_name : '',
                                'adminname' => $current_admin_name ? $current_admin_name : '',
                                'edit_time' => local_date('Y-m-d H:i:s', gmtime()),
                                'edittime' => local_date('Y-m-d H:i:s', gmtime()),
                                'mobile_phone' => $shopinfo['mobile'] ? $shopinfo['mobile'] : '',
                                'mobilephone' => $shopinfo['mobile'] ? $shopinfo['mobile'] : ''
                            ];

                            $this->commonRepository->smsSend($shopinfo['mobile'], $smsParams, 'sms_seller_signin', false);
                        }
                    }

                    $field = MerchantsStepsFields::where('contactEmail', '<>', '')->count();
                    if ($field > 0) {
                        $res = MerchantsStepsFields::where('user_id', $user_id);
                        $shopinfo = $this->baseRepository->getToArrayFirst($res);
                        $shopinfo['seller_email'] = $shopinfo['contactEmail'] ?? '';

                        /* 发送邮件 */
                        $template = get_mail_template('seller_signin');
                        if ($adminru['ru_id'] == 0 && $template['template_content'] != '') {
                            if ($shopinfo['seller_email']) {
                                $this->smarty->assign('shop_name', $shop_name);
                                $this->smarty->assign('seller_name', $user_name);
                                $this->smarty->assign('seller_psw', $pwd);
                                $this->smarty->assign('site_name', $GLOBALS['_CFG']['shop_name']);
                                $this->smarty->assign('send_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));
                                $content = $this->smarty->fetch('str:' . $template['template_content']);

                                $this->commonRepository->sendEmail($user_name, $shopinfo['seller_email'], $template['template_subject'], $content, $template['is_html']);
                            }
                        }
                    }
                }
            }

            /* 获取权限的分组数据 */
            $res = AdminAction::where('parent_id', 0)->where('seller_show', 1);
            $res = $this->baseRepository->getToArrayGet($res);
            foreach ($res as $rows) {
                $priv_arr[$rows['action_id']] = $rows;
            }

            if ($priv_arr) {
                /* 按权限组查询底级的权限名称 */
                $res = AdminAction::whereIn('parent_id', array_keys($priv_arr))->where('seller_show', 1);
                $result = $this->baseRepository->getToArrayGet($res);

                foreach ($result as $priv) {
                    $priv_arr[$priv["parent_id"]]["priv"][$priv["action_code"]] = $priv;
                }

                // 将同一组的权限使用 "," 连接起来，供JS全选
                foreach ($priv_arr as $action_id => $action_group) {
                    if (isset($action_group['priv']) && $action_group['priv']) {
                        $priv_arr[$action_id]['priv_list'] = join(',', @array_keys($action_group['priv']));

                        foreach ($action_group['priv'] as $key => $val) {
                            $priv_arr[$action_id]['priv'][$key]['cando'] = (strpos($priv_str, $val['action_code']) !== false || $priv_str == 'all') ? 1 : 0;

                            /*if ((strpos($priv_str, $val['action_code']) !== false || $priv_str == 'all') == 0) {
                                unset($priv_arr[$action_id]['priv'][$key]);
                            }*/
                        }
                    }
                }
            }

            $this->smarty->assign('priv_arr', $priv_arr);
            $this->smarty->assign('form_action', 'update_allot');
            $this->smarty->assign('admin_id', $admin_id);
            $this->smarty->assign('user_id', $user_id);

            if (!empty($user_priv['user_name'])) {
                $user_name = $user_priv['user_name'];
            }
            $this->smarty->assign('user_name', $user_name);

            //链接基本信息
            $this->smarty->assign('users', get_table_date('merchants_shop_information', "user_id='$user_id'", ['user_id', 'hopeLoginName', 'merchants_audit']));
            $this->smarty->assign('menu_select', ['action' => 'seller_shopinfo', 'action' => 'templates', 'current' => 'allot']);


            return $this->smarty->display('merchants_user_allot.dwt');
        }

        /*------------------------------------------------------ */
        //-- 恢复商家默认权限 by wu
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'restore_default_priv') {
            /* 检查权限 */
            admin_priv('users_merchants');

            $user_id = !empty($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;

            if ($user_id > 0) {
                //获取管理员id
                $adminId = AdminUser::where('ru_id', $user_id)
                    ->where('parent_id', 0)
                    ->value('user_id');
                $adminId = $adminId ? $adminId : 0;

                //获取商家等级
                $grade_id = MerchantsGrade::where('ru_id', $user_id)->value('grade_id');;
                $grade_id = $grade_id ? $grade_id : 0;

                //入驻默认初始权限
                $action_list = MerchantsPrivilege::where('grade_id', $grade_id)->value('action_list');
                $action_list = $action_list ? $action_list : '';

                //更新权限
                $data = ['action_list' => $action_list];
                AdminUser::where('user_id', $adminId)->update($data);

                $update_success = $GLOBALS['_LANG']['update_success'];
            } else {
                $update_success = $GLOBALS['_LANG']['update_fail'];
            }
            $href = "merchants_users_list.php?act=list";
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => $href];
            return sys_msg($update_success, 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 修改商家密码和权限
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update_allot') {
            /* 检查权限 */
            admin_priv('users_merchants');

            $user_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
            $login_name = isset($_REQUEST['login_name']) ? trim($_REQUEST['login_name']) : '';
            $ec_salt = rand(1, 9999);

            $seller_psw = '';

            $login_password = !empty($_REQUEST['login_password']) ? trim($_REQUEST['login_password']) : ''; //默认密码

            if (!empty($login_password)) {
                $seller_psw = $login_password;
                $au_data['password'] = md5(md5($login_password) . $ec_salt);
                $au_data['ec_salt'] = $ec_salt;
                $au_data['login_status'] = '';
            }

            if (!empty($login_name)) {
                $res = AdminUser::where('user_name', $login_name)
                    ->where('ru_id', '<>', $user_id)
                    ->count();
                if ($res < 1) {
                    $data = ['hopeLoginName' => $login_name];
                    MerchantsShopInformation::where('user_id', $user_id)->update($data);

                    $seller_name = $login_name;
                    $au_data['user_name'] = $login_name;
                } else {
                    return sys_msg($GLOBALS['_LANG']['login_name_existent']);
                }
            } else {
                return sys_msg($GLOBALS['_LANG']['login_name_not_null']);
            }

            /* 更新管理员的权限 */
            $act_list = implode(',', $_POST['action_code']);

            $au_data['action_list'] = $act_list;
            AdminUser::where('ru_id', $user_id)
                ->where('parent_id', 0)
                ->where('suppliers_id', 0)
                ->update($au_data);

            /* 取得当前管理员用户名 */
            $current_admin_name = AdminUser::where('user_id', session('admin_id'))->value('user_name');
            $current_admin_name = $current_admin_name ? $current_admin_name : '';

            //商家名称
            $shop_name = $this->merchantCommonService->getShopName($user_id, 1);

            $res = SellerShopinfo::where('ru_id', $user_id);
            $shopinfo = $this->baseRepository->getToArrayFirst($res);

            if (empty($shopinfo['mobile'])) {
                $field = MerchantsStepsFields::where('contactPhone', '<>', '')->count();
                if ($field > 0) {
                    $res = MerchantsStepsFields::where('user_id', $user_id);
                    $shopinfo = $this->baseRepository->getToArrayFirst($res);
                    $shopinfo['mobile'] = $shopinfo['contactPhone'] ?? '';
                }
            }

            if ($seller_name && $seller_psw) {
                /* 如果需要，发短信 */
                if ($adminru['ru_id'] == 0 && $GLOBALS['_CFG']['sms_seller_signin'] == '1' && $shopinfo['mobile'] != '') {
                    //阿里大鱼短信接口参数
                    $smsParams = [
                        'seller_name' => $shop_name,
                        'sellername' => $shop_name,
                        'login_name' => $seller_name ? htmlspecialchars($seller_name) : '',
                        'loginname' => $seller_name ? htmlspecialchars($seller_name) : '',
                        'password' => $seller_psw ? htmlspecialchars($seller_psw) : '',
                        'admin_name' => $current_admin_name ? $current_admin_name : '',
                        'adminname' => $current_admin_name ? $current_admin_name : '',
                        'edit_time' => local_date('Y-m-d H:i:s', gmtime()),
                        'edittime' => local_date('Y-m-d H:i:s', gmtime()),
                        'mobile_phone' => $shopinfo['mobile'] ? $shopinfo['mobile'] : '',
                        'mobilephone' => $shopinfo['mobile'] ? $shopinfo['mobile'] : ''
                    ];

                    $this->commonRepository->smsSend($shopinfo['mobile'], $smsParams, 'sms_seller_signin', false);
                }

                $field = MerchantsStepsFields::where('contactEmail', '<>', '')->count();
                if ($field > 0) {
                    if (empty($shopinfo['seller_email'])) {
                        $res = MerchantsStepsFields::where('user_id', $user_id)->select('contactEmail AS seller_email');
                        $shopinfo = $this->baseRepository->getToArrayFirst($res);
                    }
                }

                /* 记录管理员操作 */
                admin_log(addslashes($current_admin_name), 'edit', 'merchants_users_list');

                /* 发送邮件 */
                $template = get_mail_template('seller_signin');
                if ($adminru['ru_id'] == 0 && $template['template_content'] != '') {
                    if (empty($shopinfo['seller_email'])) {
                        $field = MerchantsStepsFields::where('contactEmail', '<>', '')->count();
                        if ($field > 0) {
                            $seller_email = MerchantsStepsFields::where('user_id', $user_id)->value('contactEmail');
                            $seller_email = $seller_email ? $seller_email : '';
                            $shopinfo['seller_email'] = $seller_email;
                        }
                    }

                    if ($shopinfo['seller_email'] && ($seller_name != '' || $seller_psw != '')) {
                        $this->smarty->assign('shop_name', $shop_name);
                        $this->smarty->assign('seller_name', $seller_name);
                        $this->smarty->assign('seller_psw', $seller_psw);
                        $this->smarty->assign('site_name', $GLOBALS['_CFG']['shop_name']);
                        $this->smarty->assign('send_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));
                        $content = $this->smarty->fetch('str:' . $template['template_content']);

                        $this->commonRepository->sendEmail($seller_name, $shopinfo['seller_email'], $template['template_subject'], $content, $template['is_html']);
                    }
                }
            }

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => "merchants_users_list.php?act=list"];
            return sys_msg($GLOBALS['_LANG']['update_success'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 删除申请商家
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            /* 检查权限 */
            admin_priv('users_merchants_drop');

            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            /*
            * 如果存在供应商
            */
            if (is_dir(SUPPLIERS)) {
                $count = Suppliers::where('user_id', $id)->count();
                if ($count > 0) {
                    /* 提示信息 */
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'merchants_users_list.php?act=list'];
                    return sys_msg(lang('admin/common.is_suppliers'), 0, $link);
                }
            }
            MerchantsShopInformation::where('user_id', $id)->delete();

            MerchantsStepsFields::where('user_id', $id)->delete();

            if ($GLOBALS['_CFG']['delete_seller'] && $id) {
                get_delete_seller_info('seller_shopbg', "ru_id = '$id'"); //删除店铺背景
                get_delete_seller_info('seller_shopwindow', "ru_id = '$id'"); //删除店铺橱窗
                get_delete_seller_info('seller_shopheader', "ru_id = '$id'"); //删除店铺头部
                get_delete_seller_info('seller_shopslide', "ru_id = '$id'"); //删除店铺轮播图
                get_delete_seller_info('seller_shopinfo', "ru_id = '$id'"); //删除店铺基本信息
                get_delete_seller_info('seller_domain', "ru_id = '$id'"); //删除店铺二级域名
                get_delete_seller_info('admin_user', "ru_id = '$id'"); //删除商家管理员身份

                get_seller_delete_order_list($id); //删除商家订单
                get_seller_delete_goods_list($id); //删除商家商品

                get_delete_seller_info('merchants_category', "user_id = '$id'"); //删除商家店铺分类
            }

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'merchants_users_list.php?act=list'];
            return sys_msg($GLOBALS['_LANG']['carddrop_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 查找二级类目
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'addChildCate') {
            $check_auth = check_authz_json('users_merchants');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $filter = dsc_decode($_GET['JSON']);

            $cate_list = get_first_cate_list($filter->cat_id, 0, [], $filter->cat_id);
            $this->smarty->assign('cate_list', $cate_list);
            $this->smarty->assign('cat_id', $filter->cat_id);

            return make_json_result($this->smarty->fetch('merchants_cate_list.dwt'));
        }

        /*------------------------------------------------------ */
        //-- 添加二级类目
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'addChildCate_checked') {
            $check_auth = check_authz_json('users_merchants');
            if ($check_auth !== true) {
                return $check_auth;
            }


            $cat_id = request()->input('cat_id', 0);

            $cat_id = strip_tags(urldecode($cat_id));
            $cat_id = json_str_iconv($cat_id);

            $cat = dsc_decode($cat_id);

            $child_category = get_child_category($cat->cat_id);
            $category_info = get_fine_category_info($child_category['cat_id'], $cat->user_id);
            $this->smarty->assign('category_info', $category_info);

            return make_json_result($this->smarty->fetch("merchants_cate_checked_list.dwt"));

            $permanent_list = get_category_permanent_list($cat->user_id);
            $this->smarty->assign('permanent_list', $permanent_list);
            return make_json_result($this->smarty->fetch("merchants_steps_catePermanent.dwt"));
        }

        /*------------------------------------------------------ */
        //-- 删除二级类目
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'deleteChildCate_checked') {
            $check_auth = check_authz_json('users_merchants');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $ct_id = isset($_REQUEST['ct_id']) ? intval($_REQUEST['ct_id']) : '';
            $user_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;


            $catParent = get_temporarydate_ctId_catParent($ct_id);
            if ($catParent['num'] == 1) {
                MerchantsDtFile::where('cat_id', $catParent['parent_id'])->delete();
            }

            MerchantsCategoryTemporarydate::where('ct_id', $ct_id)->delete();

            $category_info = get_fine_category_info(0, $user_id);
            $this->smarty->assign('category_info', $category_info);
            return make_json_result($this->smarty->fetch("merchants_cate_checked_list.dwt"));

            $permanent_list = get_category_permanent_list($user_id);
            $this->smarty->assign('permanent_list', $permanent_list);
            return make_json_result($this->smarty->fetch("merchants_steps_catePermanent.dwt"));
        }

        /*------------------------------------------------------ */
        //-- 删除品牌
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'deleteBrand') {
            $check_auth = check_authz_json('users_merchants');
            if ($check_auth !== true) {
                return $check_auth;
            }


            $filter = dsc_decode($_GET['JSON']);
            $brand_list = [];
            if (!empty($filter)) {
                MerchantsShopBrand::where('bid', $filter->ct_id)->delete();

                $brand_list = get_septs_shop_brand_list($filter->user_id); //品牌列表
            }
            $this->smarty->assign('brand_list', $brand_list);

            return make_json_result($this->smarty->fetch('merchants_steps_brank_list.dwt'));
        }

        /*------------------------------------------------------ */
        //-- 编辑品牌
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'brand_edit') {
            $b_fid = isset($_REQUEST['del_bFid']) ? intval($_REQUEST['del_bFid']) : 0;
            if ($b_fid > 0) {
                MerchantsShopBrandfile::where('b_fid', $b_fid)->delete();
            }

            $ec_shop_bid = isset($_REQUEST['ec_shop_bid']) ? intval($_REQUEST['ec_shop_bid']) : 0;
            $user_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
            $brandView = isset($_REQUEST['brandView']) ? $_REQUEST['brandView'] : '';

            $shopInfo_list = $this->merchantsUsersListManageService->getStepsUserShopInfoList($user_id, $ec_shop_bid);
            $this->smarty->assign('shopInfo_list', $shopInfo_list);

            $category_info = get_fine_category_info(0, $user_id); // 详细类目
            $this->smarty->assign('category_info', $category_info);

            $permanent_list = get_category_permanent_list($user_id);// 一级类目证件
            $this->smarty->assign('permanent_list', $permanent_list);

            $country_list = get_regions_steps();
            $province_list = get_regions_steps(1);
            $city_list = get_regions_steps(2);
            $district_list = get_regions_steps(3);

            $sn = 0;
            $this->smarty->assign('country_list', $country_list);
            $this->smarty->assign('province_list', $province_list);
            $this->smarty->assign('city_list', $city_list);
            $this->smarty->assign('district_list', $district_list);
            $this->smarty->assign('sn', $sn);
            $this->smarty->assign('user_id', $user_id);
            $this->smarty->assign('brandView', $brandView);
            $this->smarty->assign('ec_shop_bid', $ec_shop_bid);
            $this->smarty->assign('form_action', 'update_shop');


            return $this->smarty->display('merchants_users_shopInfo.dwt');
        } elseif ($_REQUEST['act'] == 'addBrand') {
            load_helper('order');

            $result = ['content' => ''];
            $b_fid = isset($_REQUEST['del_bFid']) ? intval($_REQUEST['del_bFid']) : 0;
            if ($b_fid > 0) {
                MerchantsShopBrandfile::where('b_fid', $b_fid)->delete();
            }

            $ec_shop_bid = isset($_REQUEST['ec_shop_bid']) ? intval($_REQUEST['ec_shop_bid']) : 0;
            $user_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
            $brandView = isset($_REQUEST['brandView']) ? $_REQUEST['brandView'] : '';

            $shopInfo_list = $this->merchantsUsersListManageService->getStepsUserShopInfoList($user_id, $ec_shop_bid);

            foreach ($shopInfo_list as $k => $v) {
                foreach ($v['steps_title'] as $key => $val) {
                    if ($val['steps_style'] == 3 && $val['fields_titles'] == $GLOBALS['_LANG']['new_brand_info']) {
                        $title = $val;
                    }
                }
            }

            $this->smarty->assign("title", $title);
            $this->smarty->assign('shopInfo_list', $shopInfo_list);
            $category_info = get_fine_category_info(0, $user_id); // 详细类目
            $this->smarty->assign('category_info', $category_info);

            $permanent_list = get_category_permanent_list($user_id);// 一级类目证件
            $this->smarty->assign('permanent_list', $permanent_list);

            $consignee = $this->flowUserService->getConsignee($user_id);

            /* 初始化地区ID */
            $consignee['country'] = !isset($consignee['country']) && empty($consignee['country']) ? 0 : intval($consignee['country']);
            $consignee['province'] = !isset($consignee['province']) && empty($consignee['province']) ? 0 : intval($consignee['province']);
            $consignee['city'] = !isset($consignee['city']) && empty($consignee['city']) ? 0 : intval($consignee['city']);
            $consignee['district'] = !isset($consignee['district']) && empty($consignee['district']) ? 0 : intval($consignee['district']);
            $consignee['street'] = !isset($consignee['street']) && empty($consignee['street']) ? 0 : intval($consignee['street']);

            $country_list = get_regions_steps();
            $province_list = get_regions_steps(1, $consignee['country']);
            $city_list = get_regions_steps(2, $consignee['province']);
            $district_list = get_regions_steps(3, $consignee['city']);

            $sn = 0;
            $this->smarty->assign('country_list', $country_list);
            $this->smarty->assign('province_list', $province_list);
            $this->smarty->assign('city_list', $city_list);
            $this->smarty->assign('district_list', $district_list);
            $this->smarty->assign('consignee', $consignee);
            $this->smarty->assign('sn', $sn);
            $this->smarty->assign('user_id', $user_id);
            $this->smarty->assign('brandView', $brandView);
            $this->smarty->assign('ec_shop_bid', $ec_shop_bid);
            $this->smarty->assign('form_action', 'update_shop');
            $result['content'] = $GLOBALS['smarty']->fetch('merchants_bank_dialog.dwt');
            return response()->json($result);
        }
        /*------------------------------------------------------ */
        //-- 查询会员名称
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'get_user_name') {
            $check_auth = check_authz_json('goods_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $user_name = empty($_REQUEST['user_name']) ? '' : trim($_REQUEST['user_name']);

            /* 获取会员列表信息 */
            $res = Users::where('user_name', 'LIKE', '%' . $user_name . '%');
            $user_list = $this->baseRepository->getToArrayGet($res);

            $res = $this->merchantsUsersListManageService->getSearchUserList($user_list);

            clear_cache_files();
            return make_json_result($res);
        } //添加品牌  by kong
        elseif ($_REQUEST['act'] == 'addImg') {
            $result = ['content' => '', 'error' => 0, 'massege' => ''];
            $user_id = !empty($_REQUEST['user_id']) ? $_REQUEST['user_id'] : 0;
            $steps_title = $this->merchantsUsersListManageService->getAdminMerchantsStepsTitle($user_id, 'addImg');
            if (!empty($steps_title)) {
                $result['error'] = '2';
                if ($user_id > 0) {
                    $res = MerchantsShopBrand::where('user_id', $_REQUEST['user_id']);
                    $title['brand_list'] = $this->baseRepository->getToArrayGet($res);
                } else {
                    $res = MerchantsShopBrand::where('user_id', 0);
                    $title['brand_list'] = $this->baseRepository->getToArrayGet($res);
                }

                $brand_id = '';
                if (!empty($title['brand_list'])) {
                    foreach ($title['brand_list'] as $k => $v) {
                        $brand_id .= $v['bid'] . ",";
                    }
                }
                $brand_id = substr($brand_id, 0, strlen($brand_id) - 1);
                $this->smarty->assign("brand_id", $brand_id);
                $this->smarty->assign("title", $title);
                $result['content'] = $GLOBALS['smarty']->fetch('merchants_steps_brankType.dwt');
            } else {
                $result['error'] = '1';
                $result['massege'] = $GLOBALS['_LANG']['add_fail'];
            }
            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 修改商品排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_sort_order') {
            $check_auth = check_authz_json('users_merchants');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $shop_id = intval($_POST['id']);
            $sort_order = intval($_POST['val']);

            $data = ['sort_order' => $sort_order];
            $res = MerchantsShopInformation::where('shop_id', $shop_id)->update($data);
            if ($res > 0) {
                clear_cache_files();
                return make_json_result($sort_order);
            }
        }

        /*------------------------------------------------------ */
        //-- 初始化商家等级 start
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'create_initialize_rank') {
            admin_priv('users_merchants');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['create_seller_grade']);

            $seller_grade_list = seller_grade_list();
            $record_count = count($seller_grade_list);

            $this->smarty->assign('record_count', $record_count);
            $this->smarty->assign('page', 1);


            return $this->smarty->display('merchants_initialize_rank.dwt');
        }

        /*------------------------------------------------------ */
        //-- 初始化商家等级 end
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'ajax_initialize_rank') {

            $page = !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_size = isset($_REQUEST['page_size']) ? intval($_REQUEST['page_size']) : 1;

            $seller_grade_list = seller_grade_list();
            $grade_list = $this->dsc->page_array($page_size, $page, $seller_grade_list);

            $arr = [];

            $add_time = gmtime();
            if ($grade_list && $grade_list['list']) {
                foreach ($grade_list['list'] as $key => $row) {
                    $res = MerchantsGrade::where('ru_id', $row['user_id']);
                    $grade_row = $this->baseRepository->getToArrayFirst($res);

                    if ($grade_row) {
                        $res = SellerGrade::where('id', $grade_row['grade_id']);
                        $seller_grade = $this->baseRepository->getToArrayFirst($res);
                    } else {
                        $seller_temp = SellerGrade::min('seller_temp');
                        $seller_temp = $seller_temp ? $seller_temp : 0;
                        $res = SellerGrade::where('seller_temp', $seller_temp);
                        $seller_grade = $this->baseRepository->getToArrayFirst($res);

                        $data = [
                            'ru_id' => $row['user_id'],
                            'grade_id' => $seller_grade['id'],
                            'add_time' => $add_time,
                            'year_num' => 1
                        ];
                        MerchantsGrade::insert($data);
                    }

                    $seller_list[$key]['shop_name'] = $this->merchantCommonService->getShopName($row['user_id'], 1);
                    $arr = [
                        'user_id' => $row['user_id'], //商家ID
                        'shop_name' => $seller_list[$key]['shop_name'], //店铺名称
                        'grade_name' => $seller_grade['grade_name'], //等级名称
                    ];
                }
            }

            $result['list'] = $arr;

            $result['page'] = $grade_list['filter']['page'] + 1;
            $result['page_size'] = $grade_list['filter']['page_size'];
            $result['record_count'] = $grade_list['filter']['record_count'];
            $result['page_count'] = $grade_list['filter']['page_count'];

            $result['is_stop'] = 1;
            if ($page > $grade_list['filter']['page_count']) {
                $result['is_stop'] = 0;
            } else {
                $result['filter_page'] = $grade_list['filter']['page'];
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 商家评分 start
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'create_seller_grade') {
            admin_priv('users_merchants');

            $this->smarty->assign('menu_select', ['action' => '17_merchants', 'current' => '04_create_seller_grade']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['create_seller_grade']);

            $seller_grade_list = seller_grade_list();
            $record_count = count($seller_grade_list);

            $this->smarty->assign('record_count', $record_count);
            $this->smarty->assign('page', 1);

            return $this->smarty->display('merchants_grade.dwt');
        }

        /*------------------------------------------------------ */
        //-- 商家评分 end
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'ajax_seller_grade') {

            $page = !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_size = isset($_REQUEST['page_size']) ? intval($_REQUEST['page_size']) : 1;

            $seller_grade_list = seller_grade_list();
            $grade_list = $this->dsc->page_array($page_size, $page, $seller_grade_list);

            $arr = [];
            if ($grade_list && $grade_list['list']) {
                foreach ($grade_list['list'] as $key => $row) {
                    @unlink(storage_public(DATA_DIR . '/sc_file/seller_comment_' . $row['user_id'] . '.php'));

                    $seller_list[$key]['user_id'] = $row['user_id'];
                    $seller_list[$key]['shop_name'] = $this->merchantCommonService->getShopName($row['user_id'], 1);
                    $seller_list[$key]['seller_comment'] = $this->commentService->getMerchantsGoodsComment($row['user_id']);

                    $mc_all = isset($seller_list[$key]['seller_comment']['commentRank']['mc_all']) ? $seller_list[$key]['seller_comment']['commentRank']['mc_all'] : 0;

                    $desc = isset($seller_list[$key]['seller_comment']['cmt']['commentRank']['zconments']['score']) ? $seller_list[$key]['seller_comment']['cmt']['commentRank']['zconments']['score'] : 0;
                    $service = isset($seller_list[$key]['seller_comment']['cmt']['commentServer']['zconments']['score']) ? $seller_list[$key]['seller_comment']['cmt']['commentServer']['zconments']['score'] : 0;
                    $delivery = isset($seller_list[$key]['seller_comment']['cmt']['commentDelivery']['zconments']['score']) ? $seller_list[$key]['seller_comment']['cmt']['commentDelivery']['zconments']['score'] : 0;

                    write_static_cache('seller_comment_' . $row['user_id'], $seller_list[$key]);

                    $arr = [
                        'user_id' => $row['user_id'], //商家ID
                        'shop_name' => $seller_list[$key]['shop_name'], //店铺名称
                        'desc' => $desc, //商品描述相符
                        'service' => $service, //卖家服务态度
                        'delivery' => $delivery, //物流发货速度
                        'mc_all' => $mc_all, //订单商品评分数量
                    ];
                }
            }

            $result['list'] = $arr;

            $result['page'] = $grade_list['filter']['page'] + 1;
            $result['page_size'] = $grade_list['filter']['page_size'];
            $result['record_count'] = $grade_list['filter']['record_count'];
            $result['page_count'] = $grade_list['filter']['page_count'];

            $result['is_stop'] = 1;
            if ($page > $grade_list['filter']['page_count']) {
                $result['is_stop'] = 0;
            } else {
                $result['filter_page'] = $grade_list['filter']['page'];
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 店铺信息 by wu
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'seller_shopinfo') {
            admin_priv('users_merchants');

            //引入首页语言包
            $this->dscRepository->helpersLang('index', 'admin');

            $this->smarty->assign('lang', $GLOBALS['_LANG']);

            $data = read_static_cache('main_user_str');

            if ($data === false) {
                $this->smarty->assign('is_false', '1');
            } else {
                $this->smarty->assign('is_false', '0');
            }

            //链接基本信息
            $user_id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $this->smarty->assign('users', get_table_date('merchants_shop_information', "user_id='$user_id'", ['user_id', 'hopeLoginName', 'merchants_audit']));
            $this->smarty->assign('menu_select', ['current' => 'seller_shopinfo', 'action' => 'templates', 'action' => 'allot']);

            //店铺ru_id
            $adminru['ru_id'] = $user_id;
            if ($adminru['ru_id'] == 0) {
                $this->smarty->assign('priv_ru', 1);
            } else {
                $this->smarty->assign('priv_ru', 0);
            }
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['back'], 'href' => 'merchants_users_list.php?act=list']);
            $this->smarty->assign('ru_id', $adminru['ru_id']);

            /*源代码 start*/
            $this->smarty->assign('countries', get_regions());
            $this->smarty->assign('provinces', get_regions(1, 1));

            //获取入驻商家店铺信息 wang 商家入驻
            $res = SellerShopinfo::where('ru_id', $adminru['ru_id']);
            $res = $res->with(['getSellerQrcode']);
            $seller_shop_info = $this->baseRepository->getToArrayFirst($res);

            $seller_shop_info['qrcode_id'] = '';
            $seller_shop_info['qrcode_thumb'] = '';
            if (isset($seller_shop_info['get_seller_qrcode']) && !empty(isset($seller_shop_info['get_seller_qrcode']))) {
                $seller_shop_info['qrcode_id'] = $seller_shop_info['get_seller_qrcode']['qrcode_id'];
                $seller_shop_info['qrcode_thumb'] = $seller_shop_info['get_seller_qrcode']['qrcode_thumb'];
            }
            $action = 'add';
            if ($seller_shop_info) {
                $action = 'update';
            } else {
                $seller_shop_info = [
                    'shop_logo' => '',
                    'logo_thumb' => '',
                    'street_thumb' => '',
                    'brand_thumb' => '',
                    'qrcode_thumb' => '',
                    'notice' => '',
                ];
            }
            $seller_shop_info['notice'] = isset($seller_shop_info['notice']) && !empty($seller_shop_info['notice']) ? $seller_shop_info['notice'] : '';

            $this->smarty->assign('seller_notice', $seller_shop_info['notice']);

            $shipping_list = warehouse_shipping_list();
            $this->smarty->assign('shipping_list', $shipping_list);
            //获取店铺二级域名 by kong
            $domain_name = SellerDomain::where('ru_id', $adminru['ru_id'])->value('domain_name');
            $domain_name = $domain_name ? $domain_name : '';
            $seller_shop_info['domain_name'] = $domain_name;//by kong

            //处理修改数据 by wu start
            $diff_data = get_seller_shopinfo_changelog($adminru['ru_id']);
            $seller_shop_info = array_replace($seller_shop_info, $diff_data);
            //处理修改数据 by wu end

            if ($seller_shop_info) {
                $seller_shop_info = array_replace($seller_shop_info, $diff_data);
                if (isset($seller_shop_info['shop_logo']) && !empty($seller_shop_info['shop_logo'])) {
                    $seller_shop_info['shop_logo'] = str_replace('../', '', $seller_shop_info['shop_logo']);
                    $seller_shop_info['shop_logo'] = get_image_path($seller_shop_info['shop_logo']);
                }
                if (isset($seller_shop_info['logo_thumb']) && !empty($seller_shop_info['logo_thumb'])) {
                    $seller_shop_info['logo_thumb'] = str_replace('../', '', $seller_shop_info['logo_thumb']);
                    $seller_shop_info['logo_thumb'] = get_image_path($seller_shop_info['logo_thumb']);
                }
                if (isset($seller_shop_info['street_thumb']) && !empty($seller_shop_info['street_thumb'])) {
                    $seller_shop_info['street_thumb'] = str_replace('../', '', $seller_shop_info['street_thumb']);
                    $seller_shop_info['street_thumb'] = get_image_path($seller_shop_info['street_thumb']);
                }

                if (isset($seller_shop_info['brand_thumb']) && !empty($seller_shop_info['brand_thumb'])) {
                    $seller_shop_info['brand_thumb'] = str_replace('../', '', $seller_shop_info['brand_thumb']);
                    $seller_shop_info['brand_thumb'] = get_image_path($seller_shop_info['brand_thumb']);
                }
                if (isset($seller_shop_info['qrcode_thumb']) && !empty($seller_shop_info['qrcode_thumb'])) {
                    $seller_shop_info['qrcode_thumb'] = str_replace('../', '', $seller_shop_info['qrcode_thumb']);
                    $seller_shop_info['qrcode_thumb'] = get_image_path($seller_shop_info['qrcode_thumb']);
                }

            }
            //处理修改数据 by wu end

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0 && $seller_shop_info) {
                $seller_shop_info['mobile'] = $this->dscRepository->stringToStar($seller_shop_info['mobile']);
                $seller_shop_info['seller_email'] = $this->dscRepository->stringToStar($seller_shop_info['seller_email']);
                $seller_shop_info['kf_tel'] = $this->dscRepository->stringToStar($seller_shop_info['kf_tel']);
            }

            $this->smarty->assign('shop_info', $seller_shop_info);

            $shop_information = $this->merchantCommonService->getShopName($adminru['ru_id']);
            $adminru['ru_id'] == 0 ? $shop_information['is_dsc'] = true : $shop_information['is_dsc'] = false;//判断当前商家是平台,还是入驻商家 bylu
            $this->smarty->assign('shop_information', $shop_information);

            $province = isset($seller_shop_info['province']) ? $seller_shop_info['province'] : 0;
            $city = isset($seller_shop_info['city']) ? $seller_shop_info['city'] : 0;
            $this->smarty->assign('cities', get_regions(2, $province));
            $this->smarty->assign('districts', get_regions(3, $city));

            $this->smarty->assign('http', $this->dsc->http());
            $this->smarty->assign('data_op', $action);

            $host = $this->dscRepository->hostDomain();
            $this->smarty->assign('host', $host);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['04_self_basic_info']);
            return $this->smarty->display('seller_shopinfo.dwt');
        }

        /*------------------------------------------------------ */
        //-- 保存店铺信息 by wu
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'save_seller_shopinfo') {

            //基本信息
            $ru_id = isset($_REQUEST['ru_id']) && !empty($_REQUEST['ru_id']) ? intval($_REQUEST['ru_id']) : $adminru['ru_id'];

            if (empty($ru_id)) {
                $lnk[] = ['text' => $GLOBALS['_LANG']['go_back_step'], 'href' => 'merchants_users_list.php?act=seller_shopinfo&id=' . $ru_id];
                return sys_msg($GLOBALS['_LANG']['invalid_data'], 0, $lnk);
            }

            //图片
            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

            /*源代码 start*/
            $shop_name = empty($_POST['shop_name']) ? '' : addslashes(trim($_POST['shop_name']));
            $shop_title = empty($_POST['shop_title']) ? '' : addslashes(trim($_POST['shop_title']));
            $shop_keyword = empty($_POST['shop_keyword']) ? '' : addslashes(trim($_POST['shop_keyword']));
            $shop_country = empty($_POST['shop_country']) ? 0 : intval($_POST['shop_country']);
            $shop_province = empty($_POST['shop_province']) ? 0 : intval($_POST['shop_province']);
            $shop_city = empty($_POST['shop_city']) ? 0 : intval($_POST['shop_city']);
            $shop_district = empty($_POST['shop_district']) ? 0 : intval($_POST['shop_district']);
            $shipping_id = empty($_POST['shipping_id']) ? 0 : intval($_POST['shipping_id']);
            $shop_address = empty($_POST['shop_address']) ? '' : addslashes(trim($_POST['shop_address']));
            $mobile = empty($_POST['mobile']) ? '' : trim($_POST['mobile']); //by wu
            $seller_email = empty($_POST['seller_email']) ? '' : addslashes(trim($_POST['seller_email']));
            $street_desc = empty($_POST['street_desc']) ? '' : addslashes(trim($_POST['street_desc']));
            $kf_qq = empty($_POST['kf_qq']) ? '' : $_POST['kf_qq'];
            $kf_ww = empty($_POST['kf_ww']) ? '' : $_POST['kf_ww'];
            $kf_touid = empty($_POST['kf_touid']) ? '' : addslashes(trim($_POST['kf_touid'])); //客服账号 bylu
            $kf_appkey = empty($_POST['kf_appkey']) ? 0 : addslashes(trim($_POST['kf_appkey'])); //appkey bylu
            $kf_secretkey = empty($_POST['kf_secretkey']) ? 0 : addslashes(trim($_POST['kf_secretkey'])); //secretkey bylu
            $kf_logo = empty($_POST['kf_logo']) ? 'http://' : addslashes(trim($_POST['kf_logo'])); //头像 bylu
            $kf_welcomeMsg = empty($_POST['kf_welcomeMsg']) ? '' : addslashes(trim($_POST['kf_welcomeMsg'])); //欢迎语 bylu
            $meiqia = empty($_POST['meiqia']) ? '' : addslashes(trim($_POST['meiqia'])); //美洽客服
            $kf_type = empty($_POST['kf_type']) ? 0 : intval($_POST['kf_type']);
            $kf_tel = empty($_POST['kf_tel']) ? '' : addslashes(trim($_POST['kf_tel']));
            $notice = empty($_POST['notice']) ? '' : addslashes(trim($_POST['notice']));
            $data_op = empty($_POST['data_op']) ? '' : $_POST['data_op'];
            $check_sellername = empty($_POST['check_sellername']) ? 0 : intval($_POST['check_sellername']);
            $shop_style = isset($_POST['shop_style']) && !empty($_POST['shop_style']) ? intval($_POST['shop_style']) : 0;
            $domain_name = empty($_POST['domain_name']) ? '' : trim($_POST['domain_name']);
            $templates_mode = empty($_REQUEST['templates_mode']) ? 0 : intval($_REQUEST['templates_mode']);

            $tengxun_key = empty($_POST['tengxun_key']) ? '' : addslashes(trim($_POST['tengxun_key']));
            $longitude = empty($_POST['longitude']) ? '' : addslashes(trim($_POST['longitude']));
            $latitude = empty($_POST['latitude']) ? '' : addslashes(trim($_POST['latitude']));

            $js_appkey = empty($_POST['js_appkey']) ? '' : $_POST['js_appkey']; //扫码appkey
            $js_appsecret = empty($_POST['js_appsecret']) ? '' : $_POST['js_appsecret']; //扫码appsecret

            $print_type = empty($_POST['print_type']) ? 0 : intval($_POST['print_type']); //打印方式
            $kdniao_printer = empty($_POST['kdniao_printer']) ? '' : $_POST['kdniao_printer']; //打印机

            //判断域名是否存在  by kong
            if (!empty($domain_name)) {
                $res = SellerDomain::where('domain_name', $domain_name)
                    ->where('ru_id', '<>', $ru_id)
                    ->count();
                if ($res > 0) {
                    $lnk[] = ['text' => $GLOBALS['_LANG']['go_back_step'], 'href' => 'merchants_users_list.php?act=seller_shopinfo&id=' . $ru_id];
                    return sys_msg($GLOBALS['_LANG']['domain_existed'], 0, $lnk);
                }
            }
            $seller_domain = [
                'ru_id' => $ru_id,
                'domain_name' => $domain_name,
            ];


            $shop_info = [
                'ru_id' => $ru_id,
                'shop_name' => $shop_name,
                'shop_title' => $shop_title,
                'shop_keyword' => $shop_keyword,
                'country' => $shop_country,
                'province' => $shop_province,
                'city' => $shop_city,
                'district' => $shop_district,
                'shipping_id' => $shipping_id,
                'shop_address' => $shop_address,
                'mobile' => $mobile,
                'seller_email' => $seller_email,
                'kf_qq' => $kf_qq,
                'kf_ww' => $kf_ww,
                'kf_appkey' => $kf_appkey, // bylu
                'kf_secretkey' => $kf_secretkey, // bylu
                'kf_touid' => $kf_touid, // bylu
                'kf_logo' => $kf_logo, // bylu
                'kf_welcomeMsg' => $kf_welcomeMsg, // bylu
                'meiqia' => $meiqia,
                'kf_type' => $kf_type,
                'kf_tel' => $kf_tel,
                'notice' => $notice,
                'street_desc' => $street_desc,
                'shop_style' => $shop_style,
                'check_sellername' => $check_sellername,
                'templates_mode' => $templates_mode,
                'tengxun_key' => $tengxun_key,
                'longitude' => $longitude,
                'latitude' => $latitude,
                'js_appkey' => $js_appkey, //扫码appkey
                'js_appsecret' => $js_appsecret, //扫码appsecret
                'print_type' => $print_type,
                'kdniao_printer' => $kdniao_printer
            ];

            $res = SellerShopinfo::where('ru_id', $ru_id);
            $res = $res->with(['getSellerQrcode']);
            $store = $this->baseRepository->getToArrayFirst($res);
            $store['qrcode_thumb'] = '';
            if (isset($store['get_seller_qrcode']) && !empty($store['get_seller_qrcode'])) {
                $store['qrcode_thumb'] = $store['get_seller_qrcode']['qrcode_thumb'];
            }

            $oss_img = [];

            /* 允许上传的文件类型 */
            $allow_file_types = '|GIF|JPG|PNG|BMP|';

            if ($_FILES['shop_logo']) {
                $file = $_FILES['shop_logo'];
                /* 判断用户是否选择了文件 */
                if ((isset($file['error']) && $file['error'] == 0) || (!isset($file['error']) && $file['tmp_name'] != 'none')) {
                    /* 检查上传的文件类型是否合法 */
                    if (!check_file_type($file['tmp_name'], $file['name'], $allow_file_types)) {
                        return sys_msg(sprintf($GLOBALS['_LANG']['msg_invalid_file'], $file['name']));
                    } else {
                        if ($file['name']) {
                            $ext = explode('.', $file['name']);
                            $ext = array_pop($ext);
                        } else {
                            $ext = "";
                        }

                        $file_name = storage_public(IMAGE_DIR . '/seller_imgs/seller_logo/seller_logo' . $ru_id . '.' . $ext);

                        /* 判断是否上传成功 */
                        if (move_upload_file($file['tmp_name'], $file_name)) {
                            $shop_info['shop_logo'] = $file_name ? str_replace(storage_public(), '', $file_name) : '';

                            $oss_img['shop_logo'] = $shop_info['shop_logo'];
                        } else {
                            return sys_msg(sprintf($GLOBALS['_LANG']['msg_upload_failed'], $file['name'], IMAGE_DIR . '/seller_imgs/seller_' . $ru_id));
                        }
                    }
                }
            }

            /**
             * 创建目录
             */
            $logo_thumb_path = storage_public(IMAGE_DIR . '/seller_imgs/seller_logo/logo_thumb/');
            if (!file_exists($logo_thumb_path)) {
                make_dir($logo_thumb_path);
            }

            if ($_FILES['logo_thumb']) {
                $file = $_FILES['logo_thumb'];
                /* 判断用户是否选择了文件 */
                if ((isset($file['error']) && $file['error'] == 0) || (!isset($file['error']) && $file['tmp_name'] != 'none')) {
                    /* 检查上传的文件类型是否合法 */
                    if (!check_file_type($file['tmp_name'], $file['name'], $allow_file_types)) {
                        return sys_msg(sprintf($GLOBALS['_LANG']['msg_invalid_file'], $file['name']));
                    } else {
                        if ($file['name']) {
                            $ext = explode('.', $file['name']);
                            $ext = array_pop($ext);
                        } else {
                            $ext = "";
                        }

                        $file_name = storage_public(IMAGE_DIR . '/seller_imgs/seller_logo/logo_thumb/logo_thumb' . $ru_id . '.' . $ext);

                        /* 判断是否上传成功 */
                        if (move_upload_file($file['tmp_name'], $file_name)) {
                            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

                            $logo_thumb = $image->make_thumb($file_name, 120, 120, storage_public(IMAGE_DIR . "/seller_imgs/seller_logo/logo_thumb/"));

                            if ($logo_thumb) {
                                $logo_thumb = str_replace(storage_public(), '', $logo_thumb);

                                $shop_info['logo_thumb'] = $logo_thumb;

                                dsc_unlink($file_name);

                                $oss_img['logo_thumb'] = $logo_thumb;
                            }
                        } else {
                            return sys_msg(sprintf($GLOBALS['_LANG']['msg_upload_failed'], $file['name'], IMAGE_DIR . '/seller_imgs/logo_thumb_' . $ru_id));
                        }
                    }
                }
            }

            $street_thumb = $image->upload_image($_FILES['street_thumb'], 'store_street/street_thumb');  //图片存放地址 -- data/septs_image
            $brand_thumb = $image->upload_image($_FILES['brand_thumb'], 'store_street/brand_thumb');  //图片存放地址 -- data/septs_image

            $street_thumb = $street_thumb ? str_replace(storage_public(), '', $street_thumb) : '';
            $brand_thumb = $brand_thumb ? str_replace(storage_public(), '', $brand_thumb) : '';

            //$this->dscRepository->getOssAddFile([$street_thumb, $brand_thumb]);

            $oss_img['street_thumb'] = $street_thumb;
            $oss_img['brand_thumb'] = $brand_thumb;

            if ($street_thumb) {
                $shop_info['street_thumb'] = $street_thumb;
            }

            if ($brand_thumb) {
                $shop_info['brand_thumb'] = $brand_thumb;
            }

            //by kong
            $domain_id = SellerDomain::where('ru_id', $ru_id)->count();
            /* 二级域名绑定  by kong  satrt */
            if ($domain_id > 0) {
                SellerDomain::where('ru_id', $ru_id)->update($seller_domain);
            } else {
                SellerDomain::insert($seller_domain);
            }
            /* 二级域名绑定  by kong  end */

            /**
             * 创建目录
             */
            $seller_qrcode_path = storage_public(IMAGE_DIR . '/seller_imgs/seller_qrcode/');
            if (!file_exists($seller_qrcode_path)) {
                make_dir($seller_qrcode_path);
            }

            $qrcode_thumb_path = storage_public(IMAGE_DIR . '/seller_imgs/seller_qrcode/qrcode_thumb/');
            if (!file_exists($qrcode_thumb_path)) {
                make_dir($qrcode_thumb_path);
            }

            //二维码中间logo by wu start
            if ($_FILES['qrcode_thumb']) {
                $file = $_FILES['qrcode_thumb'];
                /* 判断用户是否选择了文件 */
                if ((isset($file['error']) && $file['error'] == 0) || (!isset($file['error']) && $file['tmp_name'] != 'none')) {
                    /* 检查上传的文件类型是否合法 */
                    if (!check_file_type($file['tmp_name'], $file['name'], $allow_file_types)) {
                        return sys_msg(sprintf($GLOBALS['_LANG']['msg_invalid_file'], $file['name']));
                    } else {
                        $name = explode('.', $file['name']);
                        $ext = array_pop($name);
                        $file_name = storage_public(IMAGE_DIR . '/seller_imgs/seller_qrcode/qrcode_thumb/qrcode_thumb' . $ru_id . '.' . $ext);
                        /* 判断是否上传成功 */
                        if (move_upload_file($file['tmp_name'], $file_name)) {
                            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

                            $qrcode_thumb = $image->make_thumb($file_name, 120, 120, storage_public(IMAGE_DIR . "/seller_imgs/seller_qrcode/qrcode_thumb/"));

                            if (!empty($qrcode_thumb)) {
                                $qrcode_thumb = str_replace(storage_public(), '', $qrcode_thumb);

                                $oss_img['qrcode_thumb'] = $qrcode_thumb;

                                if (isset($store['qrcode_thumb']) && $store['qrcode_thumb']) {
                                    $store['qrcode_thumb'] = str_replace(['../'], '', $store['qrcode_thumb']);
                                    dsc_unlink(storage_public($store['qrcode_thumb']));
                                }
                            }

                            /* 保存 */
                            $qrcode_count = SellerQrcode::where('ru_id', $adminru['ru_id'])->count();

                            if ($qrcode_count > 0) {
                                if (!empty($qrcode_thumb)) {
                                    SellerQrcode::where('ru_id', $ru_id)
                                        ->update([
                                            'qrcode_thumb' => $qrcode_thumb
                                        ]);
                                }
                            } else {
                                SellerQrcode::insert([
                                    'ru_id' => $ru_id,
                                    'qrcode_thumb' => $qrcode_thumb
                                ]);
                            }
                        } else {
                            return sys_msg(sprintf($GLOBALS['_LANG']['msg_upload_failed'], $file['name'], IMAGE_DIR . '/seller_imgs/qrcode_thumb_' . $ru_id));
                        }
                    }
                }
            }
            //二维码中间logo by wu end

            $this->dscRepository->getOssAddFile($oss_img);

            $admin_user = [
                'email' => $seller_email
            ];

            AdminUser::where('user_id', session('admin_id'))->update($admin_user);

            if ($data_op == 'add') {
                if (!$store) {
                    //处理修改数据 by wu start
                    $review_status = empty($_REQUEST['review_status']) ? 1 : intval($_REQUEST['review_status']);
                    $review_content = empty($_REQUEST['review_content']) ? '' : trim($_REQUEST['review_content']);
                    $review_data = ['review_status' => $review_status, 'review_content' => $review_content];
                    if ($review_status == 3) {
                        $diff_data = get_seller_shopinfo_changelog($ru_id);
                        $shop_info = array_replace($shop_info, $diff_data);

                        SellerShopinfo::insert($shop_info);
                        SellerShopinfoChangelog::where('ru_id', $ru_id)->delete();
                    } else {
                        $data = ['id' => null, 'ru_id' => $ru_id];
                        SellerShopinfo::insert($data);
                    }
                    SellerShopinfo::where('ru_id', $ru_id)->update($review_data);
                    //处理修改数据 by wu end
                }

                $lnk[] = ['text' => $GLOBALS['_LANG']['go_back_step'], 'href' => 'merchants_users_list.php?act=seller_shopinfo&id=' . $ru_id];
                return sys_msg($GLOBALS['_LANG']['add_store_info_success'], 0, $lnk);
            } else {
                $res = SellerShopinfo::where('ru_id', $ru_id);
                $seller_shop_info = $this->baseRepository->getToArrayFirst($res);
                $seller_shop_info['check_sellername'] = $seller_shop_info['check_sellername'] ?? 0;

                if ($seller_shop_info['check_sellername'] != $check_sellername) {
                    $shop_info['shopname_audit'] = 0;
                }

                $oss_del = [];

                if (isset($shop_info['logo_thumb']) && !empty($shop_info['logo_thumb'])) {
                    if (!empty($store['logo_thumb'])) {
                        $oss_del[] = $store['logo_thumb'];
                    }
                    dsc_unlink(storage_public($store['logo_thumb']));
                }

                if (!empty($street_thumb)) {
                    $oss_street_thumb = $store['street_thumb'];
                    if (!empty($oss_street_thumb)) {
                        $oss_del[] = $oss_street_thumb;
                    }

                    $shop_info['street_thumb'] = $street_thumb;
                    dsc_unlink(storage_public($oss_street_thumb));
                }

                if (!empty($brand_thumb)) {
                    $oss_brand_thumb = $store['brand_thumb'];
                    if (!empty($oss_brand_thumb)) {
                        $oss_del[] = $oss_brand_thumb;
                    }

                    $shop_info['brand_thumb'] = $brand_thumb;
                    dsc_unlink(storage_public($oss_brand_thumb));
                }

                $this->dscRepository->getOssDelFile($oss_del);

                //处理修改数据 by wu start
                $review_status = empty($_REQUEST['review_status']) ? 1 : intval($_REQUEST['review_status']);
                $review_content = empty($_REQUEST['review_content']) ? '' : trim($_REQUEST['review_content']);
                $review_data = ['review_status' => $review_status, 'review_content' => $review_content];
                if ($review_status == 3) {
                    $diff_data = get_seller_shopinfo_changelog($ru_id);
                    $shop_info = array_replace($shop_info, $diff_data);
                    SellerShopinfo::where('ru_id', $ru_id)->update($shop_info);

                    SellerShopinfoChangelog::where('ru_id', $ru_id)->delete();
                }
                SellerShopinfo::where('ru_id', $ru_id)->update($review_data);
                //处理修改数据 by wu end
                $Shopinfo_cache_name = 'SellerShopinfo_' . $ru_id;

                cache()->forget($Shopinfo_cache_name);

                $lnk[] = ['text' => $GLOBALS['_LANG']['go_back_step'], 'href' => 'merchants_users_list.php?act=seller_shopinfo&id=' . $ru_id];
                return sys_msg($GLOBALS['_LANG']['update_store_info_success'], 0, $lnk);
            }
            /*源代码 end*/
        }

        /* ------------------------------------------------------ */
        //-- 查看商家店铺信息
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'see_shopinfo') {
            admin_priv('users_merchants');

            $user_id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

            //店铺ru_id
            $adminru['ru_id'] = $user_id;
            if ($adminru['ru_id'] == 0) {
                $this->smarty->assign('priv_ru', 1);
            } else {
                $this->smarty->assign('priv_ru', 0);
            }
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['back'], 'href' => 'merchants_users_list.php?act=list']);
            $this->smarty->assign('ru_id', $adminru['ru_id']);

            $res = MerchantsShopInformation::whereHas('getUsers');
            $res = $res->whereHas('getSellerShopinfo', function ($query) use ($user_id) {
                $query->where('ru_id', $user_id);
            });

            $res = $res->with([
                'getUsers' => function ($query) {
                    $query->select('user_id', 'user_name', 'mobile_phone', 'email');
                }
            ]);

            $shop_information = $this->baseRepository->getToArrayFirst($res);

            $shop_information['is_dsc'] = $adminru['ru_id'] == 0 ? true : false; //判断当前商家是平台,还是入驻商家
            // 申请时间、开店时间、到期时间
            $shop_information['add_time'] = isset($shop_information['add_time']) ? local_date('Y-m-d H:i:s', $shop_information['add_time']) : '';

            if (isset($shop_information['get_users']) && !empty($shop_information['get_users'])) {
                $shop_information = collect($shop_information)->merge($shop_information['get_users'])->except('get_users')->all();

                if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                    $shop_information['mobile_phone'] = $this->dscRepository->stringToStar($shop_information['mobile_phone']);
                    $shop_information['user_name'] = $this->dscRepository->stringToStar($shop_information['user_name']);
                    $shop_information['email'] = $this->dscRepository->stringToStar($shop_information['email']);
                }
            }

            // 平台保证金
            $seller_apply_info = $this->merchantsUsersListManageService->getSellerApplyInfo($user_id);
            $shop_information['total_amount'] = $seller_apply_info['total_amount'] ?? 0;

            // 店铺等级
            $grade_info = get_seller_grade($user_id);
            if (isset($grade_info['add_time']) && $grade_info['add_time']) {
                $shop_information['grade_add_time'] = local_date('Y-m-d H:i:s', $grade_info['add_time']);
                $shop_information['grade_end_time'] = local_date('Y-m-d H:i:s', $grade_info['add_time'] + 365 * 24 * 60 * 60 * $grade_info['year_num']);
            }
            $shop_information['grade_img'] = $grade_info['grade_img'] ?? '';
            $shop_information['grade_name'] = $grade_info['grade_name'] ?? '';

            // 店铺评分
            $merch_cmt = $this->commentService->getMerchantsGoodsComment($user_id); //商家总体评分
            $this->smarty->assign('merch_cmt', $merch_cmt);

            $this->smarty->assign('shop_information', $shop_information);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['04_self_basic_info']);

            return $this->smarty->display('merchants_users_see_shopinfo.dwt');
        }
        /* ------------------------------------------------------ */
        //-- 商家店铺等级到期时间 短信或邮件提醒
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'ajax_send_message') {
            $check_auth = check_authz_json('users_merchants');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $user_id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']); // 商家店铺id
            $type = empty($_REQUEST['type']) ? 0 : intval($_REQUEST['type']); //type 0 短信 1 邮件

            /* 获取用户名和Email地址 */
            $res = Users::where('user_id', $user_id);
            $users = $this->baseRepository->getToArrayFirst($res);

            $to_email = $users['email'];
            $to_mobile = $users['mobile_phone'];
            $user_name = $users['user_name'];

            // 店铺等级
            $grade_info = get_seller_grade($user_id);
            $grade_end_time = local_date('Y-m-d H:i:s', $grade_info['add_time'] + 365 * 24 * 60 * 60 * $grade_info['year_num']);

            $shop_name = $GLOBALS['_CFG']['shop_name']; // 平台
            $send_date = local_date($GLOBALS['_CFG']['time_format'], gmtime()); // 发送时间

            $send_ok = 1;
            /* 邮件通知处理流程 */
            if ($type == 1 && !empty($to_email)) {
                /* 设置留言回复模板所需要的内容信息 */
                $template = [
                    'template_subject' => '店铺等级到期时间提醒',
                    'is_html' => 1,
                    'template_content' => '<p>亲爱的{$user_name}，你好！</p>

                <p>您的店铺等级到期时间快过期了，到期时间：“{$grade_end_time}”</p>

                如需继续使用，请您及时续费，以免造成不必要的损失。<br/><br/>
                {$shop_name}
                <p>{$send_date}</p>'
                ];

                $this->smarty->assign('user_name', $user_name);
                $this->smarty->assign('grade_end_time', $grade_end_time);
                $this->smarty->assign('shop_name', "<a href='" . url('/') . "'>" . $shop_name . '</a>');
                $this->smarty->assign('send_date', $send_date);

                $content = $this->smarty->fetch('str:' . $template['template_content']);

                /* 发送邮件 */
                if ($this->commonRepository->sendEmail($user_name, $to_email, $template['template_subject'], $content, $template['is_html'])) {
                    $send_ok = 0;
                } else {
                    $send_ok = 1;
                }
            }
            // 发送短信提醒
            if ($type == 0 && !empty($to_mobile)) {
                //普通订单->短信接口参数
                $pt_smsParams = [
                    'user_name' => $user_name,
                    'username' => $user_name,
                    'grade_end_time' => $grade_end_time,
                    'gradeendtime' => $grade_end_time,
                    'shop_name' => $shop_name,
                    'shopname' => $shop_name,
                    'send_date' => $send_date,
                    'senddate' => $send_date,
                    'mobile_phone' => $to_mobile,
                    'mobilephone' => $to_mobile
                ];

                $send_ok = $this->commonRepository->smsSend($to_mobile, $pt_smsParams, 'sms_seller_grade_time', false);
                $send_ok = ($send_ok === true) ? 0 : 1;
            }

            return make_json_response('', $send_ok);
        }
    }

}
