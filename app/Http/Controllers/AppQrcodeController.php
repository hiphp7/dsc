<?php

namespace App\Http\Controllers;

use App\Api\Foundation\Components\ApiResponse;
use App\Models\AppQrcode;
use App\Services\Cart\CartCommonService;
use App\Services\User\UserCommonService;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;

/**
 * app扫码登录
 *
 * Class QrcodeController
 * @package App\Http\Controllers
 */
class AppQrcodeController extends InitController
{
    use ApiResponse;

    protected $userCommonService;
    protected $cartCommonService;

    public function __construct(
        UserCommonService $userCommonService,
        CartCommonService $cartCommonService
    )
    {
        $this->userCommonService = $userCommonService;
        $this->cartCommonService = $cartCommonService;
    }

    /**
     * 扫码临时记录
     */
    public function index()
    {
        // 删除记录数据
        if (session('sid')) {
            AppQrcode::where('sid', session('sid'))->delete();
        }

        // 扫码唯一标识
        $sid = $this->get_dsc_token();
        session([
            'sid' => $sid
        ]);

        //插入临时记录
        $time = gmtime();
        AppQrcode::insert(['sid' => $sid, 'add_time' => $time]);

        $url = dsc_url('/#/app_qrcode/appuser');// 授权回调url
        $qrcode_info = [
            'sid' => $sid,
            'url' => $url
        ];
        //二维码信息
        $code_url = json_encode($qrcode_info);
        $size = request()->input('size', 188);
        $margin = request()->input('margin', 5);
        $level = request()->input('level', 'M'); // L, M, Q, H

        switch ($level) {
            case 'H':
                $level = ErrorCorrectionLevel::HIGH;
                break;
            case 'Q':
                $level = ErrorCorrectionLevel::QUARTILE;
                break;
            case 'M':
                $level = ErrorCorrectionLevel::MEDIUM;
                break;
            case 'L':
                $level = ErrorCorrectionLevel::LOW;
                break;
        }

        $qrCode = new QrCode($code_url);
        $qrCode->setSize($size);
        $qrCode->setMargin($margin);
        $qrCode->setErrorCorrectionLevel(new ErrorCorrectionLevel($level));
        header('Content-Type: ' . $qrCode->getContentType());

        return $qrCode->writeString();
    }

    /**
     * 获取二维码路径
     */
    public function getQrcode()
    {
        $rand = rand(100000, 999999);
        $url = asset('appqrcode') . "?t=$rand";
        return $url;
    }

    /**
     * 轮询前端探查
     *
     * @return array
     * @throws \Exception
     */
    public function getTing()
    {
        $sid = session('sid');

        // 检测数据
        $info = [];
        if ($sid) {
            $info = AppQrcode::where('sid', $sid)->first();
            $info = $info ? $info->toArray() : [];
        }

        $lang = lang('app_qrcode');

        if ($info) {
            if ($info['is_ok'] == 2 && !empty($info['user_name'])) {

                // 处理登录操作
                $GLOBALS['user']->set_session($info['user_name']);
                $GLOBALS['user']->set_cookie($info['user_name']);

                $this->userCommonService->updateUserInfo();
                $this->cartCommonService->recalculatePriceCart();

                $result = ['error' => 2, 'message' => $lang['ting_state'][2]];
            } elseif ($info['is_ok'] == 1) {
                $result = ['error' => 1, 'message' => $lang['ting_state'][1]];
            } elseif ($info['is_ok'] == 3) {
                $result = ['error' => 3, 'message' => $lang['ting_state'][3]];
            } else {
                $result = ['error' => 4, 'message' => $lang['ting_state'][4]];
            }
        } else {
            $result = ['error' => 5, 'message' => $lang['ting_state'][5]];
        }

        return $result;
    }

    /**
     * 校验是否非法操作
     * reg_token
     * $type 0:dwt 1:lib
     */
    public function get_dsc_token()
    {
        $sc_rand = rand(100000, 999999);
        $sc_guid = sc_guid();

        $dsc_token = MD5($sc_guid . "-" . $sc_rand);

        return $dsc_token;
    }
}
