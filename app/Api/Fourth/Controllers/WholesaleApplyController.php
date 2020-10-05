<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Repositories\Common\DscRepository;
use App\Services\Wholesale\SuppliersService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 供应商入驻api
 * Class WholesaleApplyController
 * @package App\Api\Fourth\Controllers
 */
class WholesaleApplyController extends Controller
{
    protected $suppliersService;
    protected $dscRepository;

    public function __construct(
        SuppliersService $suppliersService,
        DscRepository $dscRepository
    )
    {
        $this->suppliersService = $suppliersService;
        $this->dscRepository = $dscRepository;
    }

    /**
     * 供应商入驻详情
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function apply(Request $request)
    {
        $user_id = $this->authorization();
        if (empty($user_id)) {
            return $this->setStatusCode(102)->failed(lang('common.not_login_user'));
        }

        $suppliers = $this->suppliersService->suppliersInfo($user_id);

        $region_level = [];
        if ($suppliers) {
            $data['supplier'] = $suppliers;

            $region_level = $this->suppliersService->get_region_level($suppliers['region_id']);
        }

        $data['region_level'] = $region_level;

        return $this->succeed($data);
    }

    /**
     * 供应商入驻提交
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function do_apply(Request $request)
    {
        $user_id = $this->authorization();
        if (empty($user_id)) {
            return $this->setStatusCode(102)->failed(lang('common.not_login_user'));
        }

        $data = $request->except(['act', '_token']);

        $data['user_id'] = $user_id;
        $data['region_id'] = empty($data['district']) ? '' : addslashes(trim($data['district']));

        if (empty($data['suppliers_name'])) {
            return $this->succeed(['code' => 1, 'msg' => lang('seller/merchants_upgrade.js_languages.input_supplier_name')]);
        }
        if (empty($data['real_name'])) {
            return $this->succeed(['code' => 1, 'msg' => lang('seller/merchants_upgrade.js_languages.input_name')]);
        }
        if (empty($data['self_num'])) {
            return $this->succeed(['code' => 1, 'msg' => lang('seller/merchants_upgrade.js_languages.input_user_id')]);
        }
        if (empty($data['mobile_phone'])) {
            return $this->succeed(['code' => 1, 'msg' => lang('seller/merchants_upgrade.js_languages.input_iphone')]);
        }
        if (empty($data['email'])) {
            return $this->succeed(['code' => 1, 'msg' => lang('seller/merchants_upgrade.js_languages.input_email')]);
        }
        if (empty($data['company_name'])) {
            return $this->succeed(['code' => 1, 'msg' => lang('seller/merchants_upgrade.js_languages.input_company_name')]);
        }
        if (empty($data['company_address'])) {
            return $this->succeed(['code' => 1, 'msg' => lang('seller/merchants_upgrade.js_languages.input_company_address')]);
        }

        // 上传图片处理
        $file_arr = [
            'front_of_id_card' => $data['front_of_id_card'],
            'reverse_of_id_card' => $data['reverse_of_id_card'],
            'suppliers_logo' => $data['suppliers_logo'],
        ];
        $file_arr = $this->dscRepository->transformOssFile($file_arr);

        $data['front_of_id_card'] = $file_arr['front_of_id_card'];
        $data['reverse_of_id_card'] = $file_arr['reverse_of_id_card'];
        $data['suppliers_logo'] = $file_arr['suppliers_logo'];

        $suppliers = $this->suppliersService->suppliersInfo($user_id);
        if (!empty($suppliers)) {

            $front_of_id_card_path = $this->dscRepository->editUploadImage($suppliers['front_of_id_card']);
            $reverse_of_id_card_path = $this->dscRepository->editUploadImage($suppliers['reverse_of_id_card']);
            $suppliers_logo_path = $this->dscRepository->editUploadImage($suppliers['suppliers_logo']);

            // 删除原图片
            if ($data['front_of_id_card'] && $front_of_id_card_path != $data['front_of_id_card']) {
                $front_of_id_card_path = strpos($front_of_id_card_path, 'no_image') == false ? $front_of_id_card_path : ''; // 不删除默认空图片
                $this->remove($front_of_id_card_path);
            }
            if ($data['reverse_of_id_card'] && $reverse_of_id_card_path != $data['reverse_of_id_card']) {
                $reverse_of_id_card_path = strpos($reverse_of_id_card_path, 'no_image') == false ? $reverse_of_id_card_path : ''; // 不删除默认空图片
                $this->remove($reverse_of_id_card_path);
            }
            if ($data['suppliers_logo'] && $suppliers_logo_path != $data['suppliers_logo']) {
                $suppliers_logo_path = strpos($suppliers_logo_path, 'no_image') == false ? $suppliers_logo_path : ''; // 不删除默认空图片
                $this->remove($suppliers_logo_path);
            }

            // 更新
            $result = $this->suppliersService->updateSuppliers($user_id, $data);
            if ($result) {
                return $this->succeed(['code' => 0, 'msg' => lang('seller/merchants_upgrade.info_edit_success_wait')]);
            }

        } else {
            // 新增
            $result = $this->suppliersService->createSuppliers($data);
            if ($result) {
                return $this->succeed(['code' => 0, 'msg' => lang('seller/merchants_upgrade.apply_success_wait')]);
            }
        }

        return $this->succeed(['code' => 1, 'msg' => 'fail']);
    }
}
