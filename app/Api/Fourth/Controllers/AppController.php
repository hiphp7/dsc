<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Models\AppQrcode;
use App\Models\Users;
use App\Services\App\AppService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * APP 模块
 * Class AppController
 * @package App\Api\Fourth\Controllers
 */
class AppController extends Controller
{
    protected $appService;

    public function __construct(
        AppService $appService
    )
    {
        $this->appService = $appService;
    }

    /**
     * app扫码登录 回调信息
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function appUser(Request $request)
    {
        $user_id = 0;
        $sid = $request->input('sid', '');                // 唯一标识
        $type = $request->input('type', '');              // 登录类型
        $token = $request->input('token', '');            // 会员token
        $login_time = $request->input('login_time', '0'); // 回调时间

        if (empty($sid)) {
            $result = ['error' => 1001, 'message' => lang('app_qrcode.sid_empty')];
            return $this->succeed($result);
        }

        if (empty($token)) {
            $result = ['error' => 1002, 'message' => lang('app_qrcode.token_empty')];
            return $this->succeed($result);
        }

        if (empty($type)) {
            $result = ['error' => 1003, 'message' => lang('app_qrcode.login_type_empty')];
            return $this->succeed($result);
        }

        // 按照登录类型解析 user_id
        if ($type == 'app_login') {
            $user_id = $this->JWTDecode($token);
        }

        //检测会员是否存在
        $user = Users::where('user_id', $user_id)->first();
        $user = $user ? $user->toArray() : [];
        if (empty($user)) {
            $result = ['error' => 1004, 'message' => lang('app_qrcode.user_not_exist')];
            return $this->succeed($result);
        }

        $info = AppQrcode::where('sid', $sid)->first();
        $info = $info ? $info->toArray() : [];
        if ($info) {
            // 验证有效期（一分钟）
            $time = $info['add_time'] + 28860;
            if ($login_time > $time) {
                $result = ['error' => 6, 'message' => lang('app_qrcode.qrcode_invalid')];
                return $this->succeed($result);
            }
            $date = [
                'login_code' => $type,
                'user_id' => $user['user_id'],
                'user_name' => $user['user_name'],
                'token' => $token,
                'is_ok' => 2,
                'login_time' => $login_time
            ];
            AppQrcode::where('sid', $sid)->update($date);
            $result = ['error' => 1, 'message' => lang('app_qrcode.auth_success')];
            return $this->succeed($result);
        } else {
            $result = ['error' => 1005, 'message' => lang('app_qrcode.sid_error')];
            return $this->succeed($result);
        }
    }

    /**
     * app扫码登录 确认扫码
     * @return array
     * @throws Exception
     */
    public function scancode()
    {
        $sid = request()->input('sid', '');                // 唯一标识
        $login_time = request()->input('login_time', '0'); // 回调时间

        if (empty($sid)) {
            $result = ['error' => 1001, 'message' => lang('app_qrcode.sid_empty')];
            return $result;
        }

        $info = AppQrcode::where('sid', $sid)->first();
        $info = $info ? $info->toArray() : [];
        if ($info) {
            $date = [
                'is_ok' => 1,
                'login_time' => $login_time
            ];
            AppQrcode::where('sid', $sid)->update($date);
            $result = ['error' => 1, 'message' => lang('app_qrcode.ting_state.1')];
            return $result;
        } else {
            $result = ['error' => 1005, 'message' => lang('app_qrcode.sid_error')];
            return $result;
        }
    }

    /**
     * app扫码登录 取消授权登录
     * @return array
     * @throws Exception
     */
    public function cancel()
    {
        $sid = request()->input('sid', '');                // 唯一标识
        $login_time = request()->input('login_time', '0'); // 回调时间

        if (empty($sid)) {
            return ['error' => 1001, 'message' => lang('app_qrcode.sid_empty')];
        }

        $info = AppQrcode::where('sid', $sid)->first();
        $info = $info ? $info->toArray() : [];
        if ($info) {
            $date = [
                'is_ok' => 3,
                'login_time' => $login_time
            ];
            AppQrcode::where('sid', $sid)->update($date);
            return ['error' => 1, 'message' => lang('app_qrcode.auth_cancel')];
        } else {
            return ['error' => 1005, 'message' => lang('app_qrcode.sid_error')];
        }
    }

    /**
     * app 广告列表
     * @param Request $request
     * @return JsonResponse
     */
    public function ad_position(Request $request)
    {
        $type = $request->input('type', 'loading_screen');
        $page = $request->input('page', 1);
        $size = $request->input('size', 5);

        // 通过 广告位类型 查询 广告位id
        $position_id = $this->appService->adPositionInfoByType($type);
        // 广告列表
        $offset = [
            'start' => ($page - 1) * $size,
            'limit' => $size
        ];
        $list = $this->appService->adList($position_id, $offset);

        return $this->succeed($list);
    }
}
