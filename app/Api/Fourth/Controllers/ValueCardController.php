<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Services\ValueCard\ValueCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class ValueCardController
 * @package App\Api\Fourth\Controllers
 */
class ValueCardController extends Controller
{
    protected $valueCardService;

    public function __construct(
        ValueCardService $valueCardService
    )
    {
        $this->valueCardService = $valueCardService;
    }

    /**
     * 储值卡列表
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function index(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer',
        ]);

        $page = $request->get('page', 1);
        $size = $request->get('size', 10);

        $user_id = $this->authorization();
        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $data = $this->valueCardService->valueCardList($user_id, $page, $size);

        return $this->succeed($data);
    }

    /**绑定储值卡
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function addvaluecard(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'vc_num' => 'required',
            'vc_password' => 'required',
        ]);

        $vc_num = $request->post('vc_num', '');
        $vc_password = $request->post('vc_password', '');

        $user_id = $this->authorization();
        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $data = $this->valueCardService->addCard($user_id, $vc_num, $vc_password);

        return $this->succeed($data);
    }

    /**储值使用详情
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function detail(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'vc_id' => 'required|integer',
        ]);

        $vc_id = $request->get('vc_id', 1);

        $user_id = $this->authorization();
        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $data = $this->valueCardService->cardDetail($user_id, $vc_id);

        return $this->succeed($data);
    }

    /**充值卡绑定储值卡
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function deposit(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'vc_id' => 'required|integer',
            'vc_num' => 'required',
            'vc_password' => 'required',
        ]);
        $vc_num = $request->post('vc_num', '');
        $vc_password = $request->post('vc_password', '');
        $vc_id = $request->post('vc_id', 0);


        $user_id = $this->authorization();
        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $data = $this->valueCardService->deposit($user_id, $vc_id, $vc_num, $vc_password);

        return $this->succeed($data);
    }
}
