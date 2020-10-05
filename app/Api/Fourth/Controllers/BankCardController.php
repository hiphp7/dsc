<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Services\User\BankService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class BankCardController
 * @package App\Api\Fourth\Controllers
 */
class BankCardController extends Controller
{
    protected $bankService;

    public function __construct(
        BankService $bankService
    )
    {
        $this->bankService = $bankService;
    }

    /**
     * 银行卡详情
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
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        //银行卡详情
        $bank_info = $this->bankService->infoBank($user_id);

        return $this->succeed($bank_info);
    }

    /**
     * 新增银行卡
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'id' => 'required|integer',
            'bank_name' => 'required|string',
            'bank_card' => 'required|string',
            'bank_region' => 'required|string',
            'bank_user_name' => 'required|string',
        ]);
        //返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $info = $request->all();

        $info['user_id'] = $user_id;
        // 新增银行卡
        $bank = $this->bankService->updateBank($info);

        return $this->succeed($bank);
    }

    /**
     * 更新银行卡详情
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'id' => 'required|integer',
            'bank_name' => 'required|string',
            'bank_card' => 'required|string',
            'bank_region' => 'required|string',
            'bank_user_name' => 'required|string',
        ]);

        //返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $info = $request->all();

        $info['user_id'] = $user_id;
        // 更新银行卡
        $bank = $this->bankService->updateBank($info);

        return $this->succeed($bank);
    }
}
