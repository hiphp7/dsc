<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\UserAddress;
use App\Models\Users;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Article\ArticleCommonService;
use App\Services\Common\CommonService;
use App\Services\User\UserAddressService;
use App\Services\User\UserCommonService;

class UserAddressController extends InitController
{
    protected $dscRepository;
    protected $userCommonService;
    protected $config;
    protected $articleCommonService;
    protected $commonRepository;
    protected $commonService;
    protected $userAddressService;

    public function __construct(
        DscRepository $dscRepository,
        UserCommonService $userCommonService,
        ArticleCommonService $articleCommonService,
        CommonRepository $commonRepository,
        CommonService $commonService,
        UserAddressService $userAddressService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->userCommonService = $userCommonService;
        $this->config = $this->dscRepository->dscConfig();
        $this->articleCommonService = $articleCommonService;
        $this->commonRepository = $commonRepository;
        $this->commonService = $commonService;
        $this->userAddressService = $userAddressService;
    }

    public function index()
    {
        $this->dscRepository->helpersLang(['user']);

        $user_id = session('user_id', 0);
        $action = addslashes(request()->input('act', 'default'));
        $action = $action ? $action : 'default';

        $not_login_arr = $this->userCommonService->notLoginArr();

        $ui_arr = $this->userCommonService->uiArr('address');

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
        //-- 收货地址列表界面
        /* ------------------------------------------------------ */
        if ($action == 'address_list') {
            load_helper('transaction');

            $this->dscRepository->helpersLang('shopping_flow');

            $this->smarty->assign('lang', $GLOBALS['_LANG']);

            $from_flow = (int)request()->input('from_flow', 0);

            /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
            $this->smarty->assign('country_list', get_regions());
            $this->smarty->assign('shop_province_list', get_regions(1, $this->config['shop_country']));

            /* 获取默认收货ID */
            $address_id = Users::where('user_id', $user_id)->value('address_id');

            $new_consignee_list = get_new_consignee_list(session('user_id'));
            $this->smarty->assign('new_consignee_list', $new_consignee_list);
            $this->smarty->assign('count_consignee', count($new_consignee_list));

            //赋值于模板
            $this->smarty->assign('real_goods_count', 1);
            $this->smarty->assign('shop_country', $this->config['shop_country']);
            $this->smarty->assign('address', $address_id);
            $this->smarty->assign('currency_format', $this->config['currency_format']);
            $this->smarty->assign('integral_scale', $this->config['integral_scale']);
            $this->smarty->assign('name_of_region', [$this->config['name_of_region_1'], $this->config['name_of_region_2'], $this->config['name_of_region_3'], $this->config['name_of_region_4']]);

            if ($from_flow != 1) {
                session([
                    'browse_trace' => "user_address.php?act=address_list"
                ]);
            }

            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 查看收货地址
        /* ------------------------------------------------------ */
        elseif ($action == 'address') {
            load_helper('transaction');

            $address_id = (int)request()->input('aid', 0);

            /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
            $this->smarty->assign('country_list', get_regions()); //by wu
            $this->smarty->assign('province_list', get_regions(1, 1));
            $consignee = get_user_address_info($address_id, $user_id);

            if ($address_id > 0 && empty($consignee)) {
                return show_message($GLOBALS['_LANG']['no_address'], $GLOBALS['_LANG']['back_up_page'], 'user_address.php?act=address_list', 'error');
            }
            if ($address_id > 0 && $user_id > 0 && $consignee['user_id'] != $user_id) {
                return show_message($GLOBALS['_LANG']['no_priv_address'], $GLOBALS['_LANG']['back_up_page'], 'user_address.php?act=address_list', 'error');
            }

            //用户默认收货地址
            $default_address = Users::where('user_id', $user_id)->value('address_id');

            $new_consignee_list = get_new_consignee_list(session('user_id'));
            $this->smarty->assign('new_consignee_list', $new_consignee_list);
            $this->smarty->assign('count_consignee', count($new_consignee_list));

            if ($address_id) {
                $province_list = get_regions(1, 1);
                $city_list = get_regions(2, $consignee['province']);
                $district_list = get_regions(3, $consignee['city']);
                $street_list = get_regions(4, $consignee['district']);

                $this->smarty->assign('province_list', $province_list);
                $this->smarty->assign('city_list', $city_list);
                $this->smarty->assign('district_list', $district_list);
                $this->smarty->assign('street_list', $street_list);
            }
            $this->smarty->assign('consignee', $consignee);
            $this->smarty->assign('address_id', $address_id);
            $this->smarty->assign('default_address', $default_address);

            return $this->smarty->display('user_transaction.dwt');
        }

        /* ------------------------------------------------------ */
        //-- Ajax删除收货地址
        /* ------------------------------------------------------ */
        elseif ($action == 'ajax_del_address') {
            $res = ['err_msg' => '', 'result' => '', 'error' => 0];

            $address_id = (int)request()->input('address_id', 0);

            UserAddress::where('address_id', $address_id)
                ->where('user_id', $user_id)
                ->delete();

            $res['address_id'] = $address_id;

            return response()->json($res);
        }

        /* ------------------------------------------------------ */
        //-- Ajax编辑收货地址
        /* ------------------------------------------------------ */
        elseif ($action == 'ajax_update_address') {
            load_helper('transaction');

            $res = ['err_msg' => '', 'result' => '', 'error' => 0];

            $address_id = (int)request()->input('address_id', 0);

            $address = get_user_address_info($address_id, $user_id);

            if (empty($address)) {
                $res['err_msg'] = $GLOBALS['_LANG']['no_address'];
                $res['error'] = 1;

                return response()->json($res);
            }
            if ($user_id > 0 && $address['user_id'] != $user_id) {
                $res['err_msg'] = $GLOBALS['_LANG']['no_priv_address'];
                $res['error'] = 1;

                return response()->json($res);
            }

            $this->smarty->assign('address', $address);

            $new_province_list = get_regions(1, $address['country']);
            $new_city_list = get_regions(2, $address['province']);
            $new_district_list = get_regions(3, $address['city']);

            $this->smarty->assign('country_list', get_regions());
            $this->smarty->assign('new_province_list', $new_province_list);
            $this->smarty->assign('new_city_list', $new_city_list);
            $this->smarty->assign('new_district_list', $new_district_list);

            $res['content'] = $this->smarty->fetch("library/user_editaddress.lbi");

            return response()->json($res);
        }

        /* ------------------------------------------------------ */
        //-- Ajax添加收货地址
        /* ------------------------------------------------------ */
        elseif ($action == 'ajax_add_address') {
            load_helper('transaction');

            $this->dscRepository->helpersLang('shopping_flow');

            $user_address = strip_tags(urldecode(request()->input('user_address', 0)));
            $user_address = json_str_iconv($user_address);
            $result = ['error' => 0, 'message' => '', 'content' => ''];

            $user_address = dsc_decode($user_address);

            $address = [
                'user_id' => $user_id,
                'address_id' => intval($user_address->address_id),
                'country' => isset($user_address->country) ? intval($user_address->country) : 0,
                'province' => isset($user_address->province) ? intval($user_address->province) : 0,
                'city' => isset($user_address->city) ? intval($user_address->city) : 0,
                'district' => isset($user_address->district) ? intval($user_address->district) : 0,
                'address' => isset($user_address->address) ? compile_str(trim($user_address->address)) : '',
                'consignee' => isset($user_address->consignee) ? compile_str(trim($user_address->consignee)) : '',
                'email' => isset($user_address->email) ? compile_str(trim($user_address->email)) : '',
                'tel' => isset($user_address->tel) ? compile_str(make_semiangle(trim($user_address->tel))) : '',
                'mobile' => isset($user_address->mobile) ? compile_str(make_semiangle(trim($user_address->mobile))) : '',
                'best_time' => isset($user_address->best_time) ? compile_str(trim($user_address->best_time)) : '',
                'sign_building' => isset($user_address->sign_building) ? compile_str(trim($user_address->sign_building)) : '',
                'zipcode' => isset($user_address->zipcode) ? compile_str(make_semiangle(trim($user_address->zipcode))) : '',
            ];

            if (!$this->userAddressService->updateAddress($address)) {
                $result['error'] = 1;
                $result['edit_address_failure'] = $GLOBALS['_LANG']['update_address_error'];
            } else {
                $result['browse_trace'] = session('browse_trace');
            }

            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- Ajax设置默认收货地址
        /* ------------------------------------------------------ */
        elseif ($action == 'ajax_make_address') {

            $res = ['err_msg' => '', 'result' => '', 'error' => 0];

            $address_id = (int)request()->input('address_id', 0);

            Users::where('user_id', $user_id)->update(['address_id' => $address_id]);

            $res['address_id'] = $address_id;

            return response()->json($res);
        }

        /* ------------------------------------------------------ */
        //-- 添加/编辑收货地址的处理
        /* ------------------------------------------------------ */
        elseif ($action == 'act_edit_address') {
            load_helper('transaction');

            $this->dscRepository->helpersLang('shopping_flow');
            $this->smarty->assign('lang', $GLOBALS['_LANG']);

            $_POST = get_request_filter($_POST, 1);

            $default = (int)request()->input('default', 0);
            $time = gmtime();
            $address = [
                'user_id' => $user_id,
                'address_id' => (int)request()->input('address_id', 0),
                'country' => (int)request()->input('country', 1),
                'province' => (int)request()->input('province', 0),
                'city' => (int)request()->input('city', 0),
                'district' => (int)request()->input('district', 0),
                'street' => (int)request()->input('street', 0),
                'address' => compile_str(trim(request()->input('address', ''))),
                'consignee' => compile_str(trim(request()->input('consignee', ''))),
                'email' => compile_str(trim(request()->input('email', ''))),
                'tel' => compile_str(make_semiangle(trim(request()->input('tel', '')))),
                'mobile' => compile_str(make_semiangle(trim(request()->input('mobile', '')))),
                'best_time' => trim(request()->input('best_time', '')),
                'update_time' => $time,
                'sign_building' => compile_str(trim(request()->input('sign_building', ''))),
                'zipcode' => compile_str(make_semiangle(trim(request()->input('zipcode', '')))),
            ];

            if (!$address['user_id'] || !$address['province'] || !$address['mobile'] || !$address['address'] || !$address['consignee']) {
                return show_message($GLOBALS['_LANG']['address_perfect_error'], $GLOBALS['_LANG']['back_up_page'], '', 'error');
            }

            if ($this->userAddressService->updateAddress($address, $default)) {
                return dsc_header("Location: user_address.php?act=address_list\n");
            }
        }

        /* ------------------------------------------------------ */
        //-- 删除收货地址
        /* ------------------------------------------------------ */
        elseif ($action == 'drop_consignee') {
            load_helper('transaction');

            $consignee_id = (int)request()->input('id', 0);

            if ($this->userAddressService->dropConsignee($consignee_id, $user_id)) {
                return dsc_header("Location: user_address.php?act=address_list\n");
            } else {
                return show_message($GLOBALS['_LANG']['del_address_false']);
            }
        }

    }
}
