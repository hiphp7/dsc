<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class MerchantsController
 * @package App\Api\Fourth\Controllers
 */
class MerchantsController extends Controller
{
    protected $step_id = 0; // 标识步骤

    protected $config;
    protected $merchantCommonService;
    protected $dscRepository;

    public function __construct(
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 入驻商家信息
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function index(Request $request)
    {
        $user_id = $this->uid;

        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $this->step_id = 1;

        // 验证商家是否申请   审核状态：  merchants_audit   0 正在审核， 1 审核通过， 2 审核未通过
        $shop = $this->merchantCommonService->getMerchantsShopInformation($user_id);
        if (!empty($shop)) {
            $step_id = $request->input('step_id', 6);
            $shop['step_id'] = $step_id;
            $result['shop'] = $shop;
        }

        // 验证PC商家入驻申请流程 - 公司信息认证
        $steps = $this->merchantCommonService->getMerchantsStepsFields($user_id);

        if (!empty($steps)) {
            $this->step_id = 2;
        }

        $result['step_id'] = $this->step_id;
        $result['steps'] = empty($steps) ? '' : $steps;

        // 是否存在跨境
        if (CROSS_BORDER === true) {
            $result['cross_border_version'] = true;
        }

        return $this->succeed($result);
    }

    /**
     * 入驻须知
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function guide(Request $request)
    {
        $this->step_id = $request->input('step', 1);

        $user_id = $this->uid;

        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $result = $this->merchantCommonService->getMerchantsStepsProcess($this->step_id);
        $result['step_id'] = $this->step_id;

        return $this->succeed($result);
    }

    /**
     * 同意协议
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function agree(Request $request)
    {
        // 数据验证
        $this->validate($request, [
            'agree' => 'required|integer'
        ]);

        $user_id = $this->uid;
        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $agree = $request->input('agree', 0); // 同意协议
        if (empty($agree)) {
            return $this->succeed(['code' => 1, 'msg' => lang('merchants.please_agree_agreement')]);
        }

        $data['agreement'] = $agree;

        $data['fid'] = $request->input('fid', 0);

        $data['contactXinbie'] = $request->input('contactXinbie', ''); // 性别
        $data['contactName'] = $request->input('contactName', ''); // 姓名
        $data['contactPhone'] = $request->input('contactPhone', ''); // 手机号
        $data['contactEmail'] = $request->input('contactEmail', ''); // 邮箱
        $data['license_adress'] = $request->input('license_adress', '');

        $data['companyName'] = $request->input('companyName', ''); // 公司名称
        $data['legal_person_fileImg'] = $request->input('legal_person_fileImg', ''); // 身份证照片
        $data['license_fileImg'] = $request->input('license_fileImg', ''); // 公司营业执照
        $data['company_contactTel'] = $request->input('company_contactTel', ''); // 公司联系电话

        $province_region_id = $request->input('province_region_id', 0);
        $city_region_id = $request->input('city_region_id', 0);
        $district_region_id = $request->input('district_region_id', 0);
        if (!empty($province_region_id)) {
            $data['company_located'] = $province_region_id . ',' . $city_region_id . ',' . $district_region_id;
        }
        if (empty($data['contactName'])) {
            return $this->succeed(['code' => 1, 'msg' => lang('merchants.msg_shop_owner_notnull')]);
        }
        if (empty($data['contactPhone'])) {
            return $this->succeed(['code' => 1, 'msg' => lang('merchants.mobile_not_null')]);
        }
        if ($data['contactPhone'] && !is_mobile($data['contactPhone'])) {
            return $this->succeed(['code' => 1, 'msg' => lang('merchants.mobile_phone_invalid')]);
        }
        $data['user_id'] = $user_id;

        // 图片处理
        $file_arr = [
            'legal_person_fileImg' => $data['legal_person_fileImg'],
            'license_fileImg' => $data['license_fileImg'],
        ];
        $file_arr = $this->dscRepository->transformOssFile($file_arr);
        $data['legal_person_fileImg'] = $file_arr['legal_person_fileImg'];
        $data['license_fileImg'] = $file_arr['license_fileImg'];

        if (!empty($data['fid'])) {
            $fields = $this->merchantCommonService->getMerchantsStepsFields($user_id);
            if (!empty($fields)) {
                $legal_person_fileImg_path = $this->dscRepository->editUploadImage($fields['legal_person_fileImg']);
                $license_fileImg_path = $this->dscRepository->editUploadImage($fields['license_fileImg']);

                // 删除原图片
                if ($data['legal_person_fileImg'] && $legal_person_fileImg_path != $data['legal_person_fileImg']) {
                    $legal_person_fileImg_path = strpos($legal_person_fileImg_path, 'no_image') == false ? $legal_person_fileImg_path : ''; // 不删除默认空图片
                    $this->remove($legal_person_fileImg_path);
                }
                if ($data['license_fileImg'] && $license_fileImg_path != $data['license_fileImg']) {
                    $license_fileImg_path = strpos($license_fileImg_path, 'no_image') == false ? $license_fileImg_path : ''; // 不删除默认空图片
                    $this->remove($license_fileImg_path);
                }
            }

            // 更新申请进度
            $this->merchantCommonService->updateMerchantsStepsFields($data['fid'], $user_id, $data);

            $result = ['code' => 0, 'msg' => lang('common.update_Success')];
        } else {
            // 新增申请进度
            $this->merchantCommonService->createMerchantsStepsFields($data);
            $result = ['code' => 0, 'msg' => lang('common.Submit_Success')];
        }

        // 是否存在跨境
        if (CROSS_BORDER === true) {
            $huoyuan = $request->input('huoyuan', '');  // cbec
            if (!empty($huoyuan)) {
                $cross = 'App\\Custom\\CrossBorder\\Controllers\\WebController';
                if (class_exists($cross)) {
                    // 更新货源信息
                    app($cross)->updateSource($user_id, $huoyuan);
                }
            }
        }

        return $this->succeed($result);
    }

    /**
     * 入驻店铺信息
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function shop(Request $request)
    {
        $user_id = $this->uid;

        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $this->step_id = 3;

        // 验证商家是否申请   审核状态：  merchants_audit   0 正在审核， 1 审核通过， 2 审核未通过
        $shop = $this->merchantCommonService->getMerchantsShopInformation($user_id);
        if (!empty($shop)) {
            $shop['step_id'] = $this->step_id;
            $result['shop'] = $shop;
        }

        if ($this->step_id > 1 && $this->step_id < 4) {
            //删除商家入驻流程填写分类临时信息
            $this->merchantCommonService->deleleMerchantsCategoryTemporarydate($user_id);
        }
        // 顶级分类
        $category = get_first_cate_list(0, 0);
        foreach ($category as $key => $value) {
            $category[$key]['cat_name'] = !empty($value['cat_alias_name']) ? $value['cat_alias_name'] : $value['cat_name'];
        }

        $result['step_id'] = $this->step_id;
        $result['category'] = $category;

        return $this->succeed($result);
    }

    /**
     * 提交入驻店铺信息
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function add_shop(Request $request)
    {
        $user_id = $this->uid;

        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $data = $request->input('data', []);

        if (empty($data['rz_shopName'])) {
            return $this->succeed(['code' => 1, 'msg' => lang('merchants.msg_shop_name_notnull')]);
        }
        if (empty($data['hopeLoginName'])) {
            return $this->succeed(['code' => 1, 'msg' => lang('merchants.msg_login_shop_name_notnull')]);
        }
        if (empty($data['shoprz_type'])) {
            return $this->succeed(['code' => 1, 'msg' => lang('merchants.msg_shoprz_type_notnull')]);
        }
        // 验证 旗舰店 子类型
        if ($data['shoprz_type'] && $data['shoprz_type'] == 1) {
            if (empty($data['subShoprz_type'])) {
                return $this->succeed(['code' => 1, 'msg' => lang('merchants.msg_sub_shoprz_type_notnull')]);
            }
        }
        if (empty($data['shop_categoryMain'])) {
            return $this->succeed(['code' => 1, 'msg' => lang('merchants.msg_shop_category_main_notnull')]);
        }

        $data['user_id'] = $user_id;

        // 检查店铺期望名是否使用
        $check_shopname = $this->merchantCommonService->checkMerchantsShopName($user_id, $data['rz_shopName']);
        if ($check_shopname) {
            return $this->succeed(['code' => 1, 'msg' => lang('merchants_steps_action.Settled_Prompt')]);
        }

        // 检查店铺登陆用户名是否使用
        $check_loginname = $this->merchantCommonService->checkMerchantsHopeLoginName($user_id, $data['hopeLoginName']);
        if ($check_loginname) {
            return $this->succeed(['code' => 1, 'msg' => lang('merchants_steps_action.Settled_Prompt_name')]);
        }

        // 更新子分类
        $catId_array = get_catId_array($user_id);
        $data['user_shopMain_category'] = implode('-', $catId_array);

        // 新增入驻商家信息
        $res = $this->merchantCommonService->createMerchantsShopInformation($data);
        if ($res == true) {
            // 更新临时类目表 商家入驻流程填写分类临时信息
            $this->merchantCommonService->updateMerchantsCategoryTemporarydate($user_id);

            // 成功入驻 等待审核
            $result = ['code' => 0, 'msg' => lang('merchants_steps.merchants_step_complete_one')];
        } else {
            $result = ['code' => 1, 'msg' => lang('common.Submit_fail')];
        }

        return $this->succeed($result);
    }

    /**
     * 获取下级类目
     * @param Request $request
     * @return JsonResponse
     */
    public function get_child_cate(Request $request)
    {
        $cat_id = $request->input('cat_id', 0);

        $childCate = [];
        if ($cat_id > 0) {
            $childCate = get_first_cate_list($cat_id, 0);
            if (!empty($childCate)) {
                foreach ($childCate as $key => $value) {
                    $childCate[$key]['cat_name'] = !empty($value['cat_alias_name']) ? $value['cat_alias_name'] : $value['cat_name'];
                }
            }
        }

        $result['childCate'] = collect($childCate)->values()->all();

        return $this->succeed($result);
    }

    /**
     * 添加详细类目 - 二级类目数据插入临时数据表
     * @param Request $request
     * @return JsonResponse
     */
    public function add_child_cate(Request $request)
    {
        $cat_id = $request->input('cat_id', 0);
        $child_cate_id = $request->input('child_cate_id', ''); // 子分类id 1,2,3

        $user_id = $this->uid;

        if (!empty($cat_id)) {
            // 删除主分类下子分类
            $this->merchantCommonService->deleleMerchantsCategoryTemporarydateByCateid($cat_id, $user_id);
        }

        $category_info = [];
        if (!empty($child_cate_id)) {
            $category_info = get_fine_category_info($child_cate_id, $user_id);
        }

        $result['category_info'] = collect($category_info)->values()->all();

        return $this->succeed($result);
    }

    /**
     * 删除详细类目
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function delete_child_cate(Request $request)
    {
        $ct_id = $request->input('ct_id', 0);

        if ($ct_id > 0) {
            $catParent = get_temporarydate_ctId_catParent($ct_id);
            if ($catParent && $catParent['num'] == 1) {
                // 删除商家入驻流程分类资质信息
                $this->merchantCommonService->deleteMerchantsDtFile($catParent['parent_id']);
            }

            // 删除商家入驻流程填写分类临时信息
            $this->merchantCommonService->deleleMerchantsCategoryTemporarydateByCtid($ct_id);

            $result = ['code' => 0, 'msg' => lang('common.delete_success'), 'ct_id' => $ct_id];
        } else {
            $result = ['code' => 1, 'msg' => lang('common.Submit_fail')];
        }

        return $this->succeed($result);
    }

    /**
     * 等待审核
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function audit(Request $request)
    {
        $user_id = $this->uid;

        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $this->step_id = 6;

        // 验证商家是否申请  审核状态：  merchants_audit   0 正在审核， 1 审核通过， 2 审核未通过
        $shop = $this->merchantCommonService->getMerchantsShopInformation($user_id);
        if (!empty($shop)) {
            $shop['step_id'] = $this->step_id;
            $result['shop'] = $shop;
        }

        return $this->succeed($result);
    }
}
