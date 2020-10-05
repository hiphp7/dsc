<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Repositories\Common\DscRepository;

class SdController extends InitController
{
    protected $dscRepository;

    public function __construct(
        DscRepository $dscRepository
    )
    {
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        $user_id = session('user_id', 0);

        /* 生成目录 */
        $path = storage_public("data/images_user");
        if (!file_exists($path)) {
            make_dir($path);
        }

        if ($user_id > 0) {
            $somecontent1 = base64_decode($_POST['png1']);
            $somecontent2 = base64_decode($_POST['png2']);
            $somecontent3 = base64_decode($_POST['png3']);

            $arr = [
                "data/images_user/" . $user_id . "_120.jpg",
                "data/images_user/" . $user_id . "_48.jpg",
                "data/images_user/" . $user_id . "_24.jpg"
            ];

            $this->dscRepository->getOssAddFile($arr);

            $filename120 = storage_public("data/images_user/" . $user_id . "_120.jpg");
            $filename48 = storage_public("data/images_user/" . $user_id . "_48.jpg");
            $filename24 = storage_public("data/images_user/" . $user_id . "_24.jpg");

            $parent['user_picture'] = $filename120;

            Users::where('user_id', $user_id)->update($parent);

            if ($handle = fopen($filename120, "w+")) {
                if (!fwrite($handle, $somecontent1) == false) {
                    fclose($handle);
                }
            }
            if ($handle = fopen($filename48, "w+")) {
                if (!fwrite($handle, $somecontent2) == false) {
                    fclose($handle);
                }
            }
            if ($handle = fopen($filename24, "w+")) {
                if (!fwrite($handle, $somecontent3) == false) {
                    fclose($handle);
                }
            }
            echo "success=" . $GLOBALS['_LANG']['upload_success'];
        } else {
            echo "success=" . $GLOBALS['_LANG']['upload_fail'];
        }
    }
}
