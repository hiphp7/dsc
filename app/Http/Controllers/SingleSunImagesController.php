<?php

namespace App\Http\Controllers;

use App\Libraries\Image;
use App\Models\SingleSunImages;

/**
 * 浏览列表插件
 */
class SingleSunImagesController extends InitController
{
    public function index()
    {

        $result = ['error' => 0, 'content' => '', 'msg' => ''];

        $id = (int)request()->input('id', 0);

        $order_id = (int)request()->input('order_id', 0);
        $goods_id = (int)request()->input('goods_id', 0);
        $act = addslashes(request()->input('act', ''));

        if ($act == 'ajax_return_images') {
            $img_file = isset($_FILES['SWFUpload']) ? $_FILES['SWFUpload'] : [];

            if (!empty(session('user_id'))) {
                $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

                $img_file = $image->upload_image($img_file, 'single_img_temp'); //原图
                $img_thumb = $image->make_thumb($img_file, $GLOBALS['_CFG']['single_thumb_width'], $GLOBALS['_CFG']['single_thumb_height'], DATA_DIR . '/single_img_temp/thumb/'); //缩略图

                $return = [
                    'order_id' => $order_id,
                    'goods_id' => $goods_id,
                    'user_id' => session('user_id'),
                    'img_file' => $img_file,
                    'img_thumb' => $img_thumb
                ];

                $where = [
                    'user_id' => session('user_id'),
                    'order_id' => $order_id,
                    'goods_id' => $goods_id,
                ];
                $img_count = $this->getSingleSunImagesCount($where);

                if ($img_count < 10 && $img_file) {
                    SingleSunImages::insert($return);
                } else {
                    $result['error'] = 1;
                }
            } else {
                $result['error'] = 2;
            }

            $where = [
                'user_id' => session('user_id'),
                'order_id' => $order_id,
                'goods_id' => $goods_id,
            ];
            $img_list = $this->getSingleSunImagesList($where);

            $result['currentImg_path'] = $img_list ? $img_list[0]['img_thumb'] : '';
            $this->smarty->assign('img_list', $img_list);
            $result['content'] = $this->smarty->fetch("library/single_sun_img.lbi");

            return response()->json($result);
        } elseif ($act == 'ajax_return_images_list') {
            $where = [
                'user_id' => session('user_id'),
                'order_id' => $order_id,
                'goods_id' => $goods_id,
            ];
            $img_list = $this->getSingleSunImagesList($where);

            if ($img_list) {
                $this->smarty->assign('img_list', $img_list);
                $result['content'] = $this->smarty->fetch("library/single_sun_img.lbi");
            } else {
                $result['error'] = 1;
            }

            return response()->json($result);
        } elseif ($act == 'del_pictures') {
            if (empty(session('user_id'))) {
                $result['error'] = 1;
            }

            $where = [
                'user_id' => session('user_id')
            ];
            $img_list = $this->getSingleSunImagesList($where);

            if ($img_list) {
                foreach ($img_list as $key => $val) {
                    @unlink(storage_public($val['img_file']));
                    @unlink(storage_public($val['img_thumb']));

                    if ($id == $val['id']) {
                        SingleSunImages::where('id', $id)->delete();
                    } else {
                        SingleSunImages::where('user_id', session('user_id'))->delete();
                    }
                }
            }

            $this->smarty->assign('img_list', $img_list);
            $result['content'] = $this->smarty->fetch("library/single_sun_img.lbi");

            return response()->json($result);
        }
    }

    private function getSingleSunImagesCount($where = [])
    {
        if (empty($where)) {
            return 0;
        }

        $img_list = SingleSunImages::whereRaw(1);

        if (isset($where['user_id'])) {
            $img_list = $img_list->where('user_id', $where['user_id']);
        }

        if (isset($where['order_id'])) {
            $img_list = $img_list->where('order_id', $where['order_id']);
        }

        if (isset($where['goods_id'])) {
            $img_list = $img_list->where('goods_id', $where['goods_id']);
        }

        $img_list = $img_list->count();

        return $img_list;
    }

    private function getSingleSunImagesList($where = [])
    {
        if (empty($where)) {
            return [];
        }

        $img_list = SingleSunImages::whereRaw(1);

        if (isset($where['user_id'])) {
            $img_list = $img_list->where('user_id', $where['user_id']);
        }

        if (isset($where['order_id'])) {
            $img_list = $img_list->where('order_id', $where['order_id']);
        }

        if (isset($where['goods_id'])) {
            $img_list = $img_list->where('goods_id', $where['goods_id']);
        }

        $img_list = $img_list->orderBy('id', 'desc')
            ->get();

        $img_list = $img_list ? $img_list->toArray() : [];

        return $img_list;
    }
}
