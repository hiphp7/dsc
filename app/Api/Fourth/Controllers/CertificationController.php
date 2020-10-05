<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Services\User\VerifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class UserController
 * @package App\Api\Fourth\Controllers
 */
class CertificationController extends Controller
{
    protected $verifyService;

    public function __construct(
        VerifyService $verifyService
    )
    {
        $this->verifyService = $verifyService;
    }

    /**
     * 个人实名认证详情
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function index(Request $request)
    {
        //数据验证
        $this->validate($request, []);

        //返回用户ID
        $user_id = $this->authorization();

        //实名认证详情
        $verify_info = $this->verifyService->infoVerify($user_id);

        return $this->succeed($verify_info);
    }

    /**
     * 新增个人实名认证
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'real_id' => 'required|integer',
            'real_name' => 'required|string',
            'bank_mobile' => 'required|size:11',
            'bank_name' => 'required|string',
            'bank_card' => 'required|string',
            'self_num' => 'required|string',
            'front_of_id_card' => 'required|string',
            'reverse_of_id_card' => 'required|string',
        ]);

        //返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $info = $request->all();

        $info['user_id'] = $user_id;

        // 新增个人实名认证
        $verify = $this->verifyService->updateVerify($info);

        return $this->succeed($verify);
    }

    /**
     * 更新个人实名认证
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'real_id' => 'required|integer',
            'real_name' => 'required|string',
            'bank_mobile' => 'required|size:11',
            'bank_name' => 'required|string',
            'bank_card' => 'required|string',
            'self_num' => 'required|string',
            'front_of_id_card' => 'required|string',
            'reverse_of_id_card' => 'required|string',
        ]);

        //返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $info = $request->all();

        $info['user_id'] = $user_id;

        // 更新个人实名认证
        $verify = $this->verifyService->updateVerify($info);

        return $this->succeed($verify);
    }
}
