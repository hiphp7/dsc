<?php

namespace App\Http\Controllers;

use App\Libraries\Image;
use App\Models\ReturnImages;
use App\Repositories\Common\DscRepository;
use App\Services\Order\OrderRefoundService;

/**
 * 浏览列表插件
 */
class ReturnImagesController extends InitController
{
    protected $orderRefoundService;
    protected $dscRepository;

    public function __construct(
        DscRepository $dscRepository,
        OrderRefoundService $orderRefoundService
    )
    {
        $this->orderRefoundService = $orderRefoundService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {

        $result = ['error' => 0, 'content' => ''];
        $rec_id = (int)request()->input('rec_id', 0);

        $rec_ids = request()->input('rec_ids', '');
        $rec_ids = $rec_ids ? explode("-", trim($rec_ids)) : '';

        $user_id = session('user_id', 0);
        $act = addslashes(request()->input('act', ''));

        if ($act == 'ajax_return_images') {
            $img_file = isset($_FILES['file']) ? $_FILES['file'] : [];
            $user_id = (int)request()->input('userId', 0);

            if (!empty($user_id)) {
                $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);
                $img_file = $image->upload_image($img_file, 'return_images');

                $this->dscRepository->getOssAddFile([$img_file]);

                if ($rec_id > 0) {
                    $return = [
                        'rec_id' => $rec_id,
                        'user_id' => $user_id,
                        'img_file' => $img_file,
                        'add_time' => gmtime()
                    ];

                    $img_count = ReturnImages::where('user_id', $user_id)->where('rec_id', $rec_id)->count();

                    if ($img_count < $GLOBALS['_CFG']['return_pictures']) {
                        ReturnImages::insert($return);
                    } else {
                        $result['error'] = 1;
                    }
                } elseif ($rec_ids) {
                    if ($rec_ids) {
                        foreach ($rec_ids as $val) {
                            $return = [
                                'rec_id' => $val,
                                'user_id' => $user_id,
                                'img_file' => $img_file,
                                'add_time' => gmtime()
                            ];

                            $img_count = ReturnImages::where('user_id', $user_id)->where('rec_id', $val)->count();

                            if ($img_count < $GLOBALS['_CFG']['return_pictures']) {
                                ReturnImages::insert($return);
                            } else {
                                $result['error'] = 1;
                            }
                            $rec_id = $val;
                        }
                    }
                }
            } else {
                $result['error'] = 2;
            }

            $where = [
                'user_id' => $user_id,
                'rec_id' => $rec_id
            ];
            $img_list = $this->orderRefoundService->getReturnImagesList($where);

            $this->smarty->assign('img_list', $img_list);
            $result['content'] = $this->smarty->fetch("library/return_goods_img.lbi");

            return response()->json($result);
        } elseif ($act == 'ajax_return_images_list') {
            $where = [
                'user_id' => $user_id,
                'rec_id' => $rec_id
            ];
            $img_list = $this->orderRefoundService->getReturnImagesList($where);

            if ($img_list) {
                $this->smarty->assign('img_list', $img_list);
                $result['content'] = $this->smarty->fetch("library/return_goods_img.lbi");
            } else {
                $result['error'] = 1;
            }

            return response()->json($result);
        } elseif ($act == 'clear_pictures') {
            if ($rec_ids) {
                foreach ($rec_ids as $rec_id) {
                    $where = [
                        'user_id' => $user_id,
                        'rec_id' => $rec_id
                    ];
                    $img_list = $this->orderRefoundService->getReturnImagesList($where);

                    if ($img_list) {
                        foreach ($img_list as $key => $row) {
                            $this->dscRepository->getOssDelFile([$row['img']]);
                            @unlink(storage_public($row['img']));
                        }
                    }

                    ReturnImages::where('user_id', $user_id)->where('rec_id', $rec_id)->delete();
                }
            } elseif ($rec_id) {
                $where = [
                    'user_id' => $user_id,
                    'rec_id' => $rec_id
                ];
                $img_list = $this->orderRefoundService->getReturnImagesList($where);

                if ($img_list) {
                    foreach ($img_list as $key => $row) {
                        $this->dscRepository->getOssDelFile([$row['img']]);
                        @unlink(storage_public($row['img']));
                    }
                }

                ReturnImages::where('user_id', $user_id)->where('rec_id', $rec_id)->delete();
            }
            return response()->json($result);
        }
    }
}
