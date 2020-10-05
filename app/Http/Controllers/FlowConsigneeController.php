<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use App\Models\Users;
use App\Services\Cart\CartCommonService;
use App\Services\Common\AreaService;
use App\Services\User\UserAddressService;

/**
 * 提交投票
 */
class FlowConsigneeController extends InitController
{
    protected $areaService;
    protected $cartCommonService;
    protected $userAddressService;

    public function __construct(
        AreaService $areaService,
        CartCommonService $cartCommonService,
        UserAddressService $userAddressService
    )
    {
        $this->areaService = $areaService;
        $this->cartCommonService = $cartCommonService;
        $this->userAddressService = $userAddressService;
    }

    public function index()
    {
        load_helper('order');

        $user_id = session('user_id', 0);
        $step = addslashes(request()->input('step', ''));

        $cart_value = $this->cartCommonService->getCartValue();

        $this->smarty->assign('user_id', $user_id);

        if ($step == 'edit_Consignee') {
            $result = ['message' => '', 'result' => '', 'qty' => 1];

            $address_id = (int)request()->input('address_id', 0);
            if ($address_id == 0) {
                $consignee['country'] = 1;
                $consignee['province'] = 0;
                $consignee['city'] = 0;
            }

            $consignee = $this->userAddressService->getUpdateFlowConsignee($address_id, $user_id);
            $this->smarty->assign('consignee', $consignee);

            /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
            $this->smarty->assign('country_list', get_regions());

            $this->smarty->assign('please_select', $GLOBALS['_LANG']['please_select']);

            $province_list = $this->areaService->getRegionsLog(1, $consignee['country']);
            $city_list = $this->areaService->getRegionsLog(2, $consignee['province']);
            $district_list = $this->areaService->getRegionsLog(3, $consignee['city']);

            $this->smarty->assign('province_list', $province_list);
            $this->smarty->assign('city_list', $city_list);
            $this->smarty->assign('district_list', $district_list);

            //有存在虚拟和实体商品 start
            get_goods_flow_type($cart_value);
            //有存在虚拟和实体商品 end

            if ($user_id <= 0) {
                $result['error'] = 2;
                $result['message'] = '您尚未登录，请登录您的账号！';
            } else {
                $result['error'] = 0;
                $result['content'] = $this->smarty->fetch("library/consignee_new.lbi");
            }

            return response()->json($result);
        } elseif ($step == 'insert_Consignee') {
            $result = ['message' => '', 'result' => '', 'error' => 0];

            $csg = json_str_iconv(request()->input('csg', ''));
            $csg = dsc_decode($csg);

            $consignee = [
                'address_id' => empty($csg->address_id) ? 0 : intval($csg->address_id),
                'consignee' => empty($csg->consignee) ? '' : compile_str(trim($csg->consignee)),
                'country' => empty($csg->country) ? '' : intval($csg->country),
                'province' => empty($csg->province) ? '' : intval($csg->province),
                'city' => empty($csg->city) ? '' : intval($csg->city),
                'district' => empty($csg->district) ? '' : intval($csg->district),
                'email' => empty($csg->email) ? '' : compile_str($csg->email),
                'address' => empty($csg->address) ? '' : compile_str($csg->address),
                'zipcode' => empty($csg->zipcode) ? '' : compile_str(make_semiangle(trim($csg->zipcode))),
                'tel' => empty($csg->tel) ? '' : compile_str(make_semiangle(trim($csg->tel))),
                'mobile' => empty($csg->mobile) ? '' : compile_str(make_semiangle(trim($csg->mobile))),
                'sign_building' => empty($csg->sign_building) ? '' : compile_str($csg->sign_building),
                'best_time' => empty($csg->best_time) ? '' : compile_str($csg->best_time),
            ];

            if ($result['error'] == 0) {
                if ($user_id > 0) {
                    load_helper('transaction');

                    $row = UserAddress::where('consignee', $consignee['consignee'])
                        ->where('user_id', $user_id);

                    if ($consignee['address_id'] > 0) {
                        $row = $row->where('address_id', '<>', $consignee['address_id']);
                    }

                    $row = $row->count();

                    if ($row > 0) {
                        $result['error'] = 4;
                        $result['message'] = $GLOBALS['_LANG']['Distribution_exists'];
                    } else {
                        $result['error'] = 0;

                        if ($user_id > 0) {
                            /* 如果用户已经登录，则保存收货人信息 */
                            $consignee['user_id'] = $user_id;
                            $this->userAddressService->saveConsignee($consignee, true);
                        }

                        $user_address_id = Users::where('user_id', $user_id)->value('address_id');

                        if ($user_address_id > 0) {
                            $consignee['address_id'] = $user_address_id;
                        }

                        if ($consignee['address_id'] > 0) {
                            Users::where('user_id', $consignee['user_id'])->update(['address_id' => $consignee['address_id']]);

                            session([
                                'flow_consignee' => $consignee
                            ]);

                            $result['message'] = $GLOBALS['_LANG']['edit_success_two'];
                        } else {
                            $result['message'] = $GLOBALS['_LANG']['add_success_two'];
                        }
                    }

                    $user_address = $this->userAddressService->getUserAddressList($user_id);
                    $this->smarty->assign('user_address', $user_address);
                    $this->smarty->assign('consignee', $consignee);

                    $result['content'] = $this->smarty->fetch("library/consignee_flow.lbi");
                } else {
                    $result['error'] = 2;
                    $result['message'] = $GLOBALS['_LANG']['lang_crowd_not_login'];
                }
            }

            return response()->json($result);
        } elseif ($step == 'delete_Consignee') {

            $result = ['message' => '', 'error' => 0, 'result' => '', 'qty' => 1];

            $address_id = (int)request()->input('address_id', 0);

            UserAddress::where('address_id', $address_id)->where('user_id', $user_id)->delete();

            $consignee = session('flow_consignee');
            $this->smarty->assign('consignee', $consignee);

            $user_address = $this->userAddressService->getUserAddressList($user_id);
            $this->smarty->assign('user_address', $user_address);

            $result['content'] = $this->smarty->fetch("library/consignee_flow.lbi");

            return response()->json($result);
        }
    }
}
