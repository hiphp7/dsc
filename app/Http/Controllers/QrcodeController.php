<?php

namespace App\Http\Controllers;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;

/**
 * 动态输出二维码文件流
 *
 * Class QrcodeController
 * @package App\Http\Controllers
 */
class QrcodeController extends InitController
{
    public function index()
    {
        $code_url = request()->input('code_url', '');
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
}
