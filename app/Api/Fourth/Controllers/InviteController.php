<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Services\User\InviteService;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * 我的分享
 * Class InviteController
 * @package App\Api\Fourth\Controllers
 */
class InviteController extends Controller
{
    /**
     * @var InviteService
     */
    protected $inviteService;

    public function __construct(InviteService $inviteService)
    {
        $this->inviteService = $inviteService;
    }

    /**
     * 邀请
     * @param Request $request
     * @return JsonResponse
     * @throws FileNotFoundException
     * @throws ValidationException
     */
    public function index(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer',
        ]);

        $page = $request->input('page', 1);
        $size = $request->input('size', 10);
        $goods_id = $request->input('goods_id', 0);

        //获取会员id
        $user_id = $this->authorization();

        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        //邀请信息
        $invite_info = $this->inviteService->getInvite($user_id, $page, $size, $goods_id);

        if (isset($invite_info['file']) && $invite_info['file']) {
            // 同步镜像上传到OSS
            $this->ossMirror($invite_info['file'], true);
        }

        return $this->succeed($invite_info);
    }
}
