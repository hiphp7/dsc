<?php

namespace App\Services\PictureBatch;

use App\Libraries\Image;
use App\Models\Goods;
use App\Models\GoodsGallery;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Goods\GoodsManageService;

class PictureBatchManageService
{
    protected $baseRepository;
    protected $timeRepository;
    protected $goodsManageService;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        GoodsManageService $goodsManageService,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->goodsManageService = $goodsManageService;
        $this->dscRepository = $dscRepository;
    }

    /**
     * 图片处理函数
     *
     * @param int $page
     * @param int $page_size
     * @param int $type
     * @param bool $thumb 是否生成缩略图
     * @param bool $watermark 是否生成水印图
     * @param bool $change true 生成新图，删除旧图 false 用新图覆盖旧图
     * @param bool $silent 是否执行能忽略错误
     * @param array $goods_id
     * @param int $cat_id
     * @param int $brand_id
     */
    public function processImage($page = 1, $page_size = 100, $type = 0, $thumb = true, $watermark = true, $change = false, $silent = true, $goods_id = [], $cat_id = 0, $brand_id = 0)
    {
        if ($type == 0) {
            $res = Goods::where('original_img', '<>', '');
            if (empty($goods_id)) {
                if (!empty($cat_id)) {
                    $cat_id_list = $this->baseRepository->getExplode($cat_id);
                    $res = $res->whereIn('cat_id', $cat_id_list);
                }
                if (!empty($brand_id)) {
                    $res = $res->where('brand_id', $brand_id);
                }
            } else {
                $goods_id_list = $this->baseRepository->getExplode($goods_id);
                $res = $res->whereIn('goods_id', $goods_id_list);
            }

            $res = $res->offset(($page - 1) * $page_size)->limit($page_size);
            $res = $this->baseRepository->getToArrayGet($res);

            foreach ($res as $row) {
                /* 水印 */
                if ($watermark) {
                    /* 获取加水印图片的目录 */
                    if (empty($row['goods_img'])) {
                        $dir = dirname(storage_public($row['original_img'])) . '/';
                    } else {
                        $dir = dirname(storage_public() . $row['goods_img']) . '/';
                    }

                    $image = app(Image::class)->make_thumb(storage_public($row['original_img']), $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height'], $dir); //先生成缩略图

                    if (!$image) {
                        //出错返回
                        $msg = sprintf($GLOBALS['_LANG']['error_pos'], $row['goods_id']) . "\n" . app(Image::class)->error_msg();
                        if ($silent) {
                            $GLOBALS['err_msg'][] = $msg;
                            continue;
                        } else {
                            return make_json_error($msg);
                        }
                    }

                    $image = app(Image::class)->add_watermark($image, '', $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']);

                    if (!$image) {
                        //出错返回
                        $msg = sprintf($GLOBALS['_LANG']['error_pos'], $row['goods_id']) . "\n" . app(Image::class)->error_msg();
                        if ($silent) {
                            $GLOBALS['err_msg'][] = $msg;
                            continue;
                        } else {
                            return make_json_error($msg);
                        }
                    }

                    /* 重新格式化图片名称 */
                    $image = $this->goodsManageService->reformatImageName('goods', $row['goods_id'], $image, 'goods');
                    if ($change || empty($row['goods_img'])) {
                        /* 要生成新链接的处理过程 */
                        if ($image != $row['goods_img']) {

                            $data = ['goods_img' => $image];
                            Goods::where('goods_id', $row['goods_id'])->update($data);

                            /* 防止原图被删除 */
                            if ($row['goods_img'] != $row['original_img']) {
                                @unlink(storage_public($row['goods_img']));
                            }
                        }
                    } else {
                        $this->replaceImage($image, $row['goods_img'], $row['goods_id'], $silent);
                    }
                }

                /* 缩略图 */
                if ($thumb) {
                    if (empty($row['goods_thumb'])) {
                        $dir = dirname(storage_public($row['original_img'])) . '/';
                    } else {
                        $dir = dirname(storage_public($row['goods_thumb'])) . '/';
                    }

                    $goods_thumb = app(Image::class)->make_thumb(storage_public($row['original_img']), $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height'], $dir);

                    /* 出错处理 */
                    if (!$goods_thumb) {
                        $msg = sprintf($GLOBALS['_LANG']['error_pos'], $row['goods_id']) . "\n" . app(Image::class)->error_msg();
                        if ($silent) {
                            $GLOBALS['err_msg'][] = $msg;
                            continue;
                        } else {
                            return make_json_error($msg);
                        }
                    }
                    /* 重新格式化图片名称 */
                    $goods_thumb = $this->goodsManageService->reformatImageName('goods_thumb', $row['goods_id'], $goods_thumb, 'thumb');
                    if ($change || empty($row['goods_thumb'])) {
                        if ($row['goods_thumb'] != $goods_thumb) {

                            $data = ['goods_thumb' => $goods_thumb];
                            Goods::where('goods_id', $row['goods_id'])->update($data);

                            /* 防止原图被删除 */
                            if ($row['goods_thumb'] != $row['original_img']) {
                                @unlink(storage_public($row['goods_thumb']));
                            }
                        }
                    } else {
                        $this->replaceImage($goods_thumb, $row['goods_thumb'], $row['goods_id'], $silent);
                    }
                }
            }
        } else {
            /* 遍历商品相册 */
            $res = GoodsGallery::where('img_original', '<>', '');

            if (empty($goods_id)) {
                if (!empty($cat_id) || !empty($brand_id)) {
                    $res = $res->whereHas('getGoods', function ($query) use ($cat_id, $brand_id) {
                        if (!empty($cat_id)) {
                            $cat_id_list = $this->baseRepository->getExplode($cat_id);
                            $query = $query->whereIn('cat_id', $cat_id_list);
                        }
                        if (!empty($brand_id)) {
                            $query->where('brand_id', $brand_id);
                        }
                    });
                }
            } else {
                $res = $res->whereHas('getGoods', function ($query) use ($goods_id) {
                    $goods_id_list = $this->baseRepository->getExplode($goods_id);
                    $query->whereIn('goods_id', $goods_id_list);
                });
            }

            $res = $res->offset(($page - 1) * $page_size)->limit($page_size);
            $res = $this->baseRepository->getToArrayGet($res);

            foreach ($res as $row) {
                /* 水印 */
                if ($watermark && file_exists(storage_public($row['img_original']))) {
                    if (empty($row['img_url'])) {
                        $dir = dirname(storage_public($row['img_original'])) . '/';
                    } else {
                        $dir = dirname(storage_public($row['img_url'])) . '/';
                    }

                    $file_name = app(Image::class)->unique_name($dir);
                    $file_name .= app(Image::class)->get_filetype(empty($row['img_url']) ? $row['img_original'] : $row['img_url']);

                    copy(storage_public($row['img_original']), $dir . $file_name);
                    $image = app(Image::class)->add_watermark($dir . $file_name, '', $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']);
                    if (!$image) {
                        @unlink($dir . $file_name);
                        $msg = sprintf($GLOBALS['_LANG']['error_pos'], $row['goods_id']) . "\n" . app(Image::class)->error_msg();
                        if ($silent) {
                            $GLOBALS['err_msg'][] = $msg;
                            continue;
                        } else {
                            return make_json_error($msg);
                        }
                    }
                    /* 重新格式化图片名称 */
                    $image = $this->goodsManageService->reformatImageName('gallery', $row['goods_id'], $image, 'goods');
                    if ($change || empty($row['img_url']) || $row['img_original'] == $row['img_url']) {
                        if ($image != $row['img_url']) {

                            $data = ['img_url' => $image];
                            GoodsGallery::where('img_id', $row['img_id'])->update($data);

                            if ($row['img_original'] != $row['img_url']) {
                                @unlink(storage_public($row['img_url']));
                            }
                        }
                    } else {
                        $this->replaceImage($image, $row['img_url'], $row['goods_id'], $silent);
                    }
                }

                /* 缩略图 */
                if ($thumb) {
                    if (empty($row['thumb_url'])) {
                        $dir = dirname(storage_public($row['img_original'])) . '/';
                    } else {
                        $dir = dirname(storage_public($row['thumb_url'])) . '/';
                    }

                    $thumb_url = app(Image::class)->make_thumb(storage_public($row['img_original']), $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height'], $dir);

                    if (!$thumb_url) {
                        $msg = sprintf($GLOBALS['_LANG']['error_pos'], $row['goods_id']) . "\n" . app(Image::class)->error_msg();
                        if ($silent) {
                            $GLOBALS['err_msg'][] = $msg;
                            continue;
                        } else {
                            return make_json_error($msg);
                        }
                    }
                    /* 重新格式化图片名称 */
                    $thumb_url = $this->goodsManageService->reformatImageName('gallery_thumb', $row['goods_id'], $thumb_url, 'thumb');
                    if ($change || empty($row['thumb_url'])) {
                        if ($thumb_url != $row['thumb_url']) {

                            $data = ['thumb_url' => $thumb_url];
                            GoodsGallery::where('img_id', $row['img_id'])->update($data);

                            @unlink(storage_public($row['thumb_url']));
                        }
                    } else {
                        $this->replaceImage($thumb_url, $row['thumb_url'], $row['goods_id'], $silent);
                    }
                }
            }
        }
    }

    /**
     *  图片处理函数
     *
     * @param int $page
     * @param int $page_size
     * @param int $type
     * @param bool $thumb 是否生成缩略图
     * @param bool $watermark 是否生成水印图
     * @param bool $change 生成新图，删除旧图 false 用新图覆盖旧图
     * @param bool $silent 是否执行能忽略错误
     */
    public function processImageEx($page = 1, $page_size = 100, $type = 0, $thumb = true, $watermark = true, $change = false, $silent = true)
    {
        if ($type == 0) {
            $res = Goods::where('original_img', '<>', '')
                ->offset(($page - 1) * $page_size)
                ->limit($page_size);
            $res = $this->baseRepository->getToArrayGet($res);

            foreach ($res as $row) {
                if ($thumb) {
                    $this->dscRepository->getImagePath('');
                }
                if ($watermark) {
                    $this->dscRepository->getImagePath('');
                }
            }
        } else {
            $res = GoodsGallery::where('img_original', '<>', '');

            if (empty($goods_id)) {
                if (!empty($cat_id) || !empty($brand_id)) {
                    $res = $res->whereHas('getGoods', function ($query) use ($cat_id, $brand_id) {
                        if (!empty($cat_id)) {
                            $cat_id_list = $this->baseRepository->getExplode($cat_id);
                            $query = $query->whereIn('cat_id', $cat_id_list);
                        }
                        if (!empty($brand_id)) {
                            $query->where('brand_id', $brand_id);
                        }
                    });
                }
            } else {
                $res = $res->whereHas('getGoods', function ($query) use ($goods_id) {
                    $goods_id_list = $this->baseRepository->getExplode($goods_id);
                    $query->whereIn('goods_id', $goods_id_list);
                });
            }
            $res = $res->offset(($page - 1) * $page_size)
                ->limit($page_size);
            $res = $this->baseRepository->getToArrayGet($res);

            foreach ($res as $row) {
                if ($thumb) {
                    $this->dscRepository->getImagePath($row['img_original']);
                }
                if ($watermark) {
                    $this->dscRepository->getImagePath($row['img_original']);
                }
            }
        }
    }

    /**
     *  用新图片替换指定图片
     *
     * @access  public
     * @param string $new_image 新图片
     * @param string $old_image 旧图片
     * @param string $goods_id 商品图片
     * @param boolen $silent 是否使用静态函数
     *
     * @return void
     */
    public function replaceImage($new_image, $old_image, $goods_id, $silent)
    {
        $error = false;
        if (file_exists(storage_public($old_image))) {
            @rename(storage_public($old_image), storage_public($old_image) . '.bak');
            if (!@rename(storage_public($new_image), storage_public($old_image))) {
                $error = true;
            }
        } else {
            if (!@rename(storage_public($new_image), storage_public($old_image))) {
                $error = true;
            }
        }
        if ($error === true) {
            if (file_exists(storage_public($old_image) . '.bak')) {
                @rename(storage_public($old_image) . '.bak', storage_public($old_image));
            }
            $msg = sprintf($GLOBALS['_LANG']['error_pos'], $goods_id) . "\n" . sprintf($GLOBALS['_LANG']['error_rename'], $new_image, $old_image);
            if ($silent) {
                $GLOBALS['err_msg'][] = $msg;
            } else {
                return make_json_error($msg);
            }
        } else {
            if (file_exists(storage_public($old_image) . '.bak')) {
                @unlink(storage_public($old_image) . '.bak');
            }
            return;
        }
    }

    public function getGoodsCount($goods_id, $cat_id, $brand_id)
    {
        $res = Goods::whereRaw(1);
        if (empty($goods_id)) {
            if (!empty($cat_id)) {
                $cat_id_list = $this->baseRepository->getExplode($cat_id);
                $res = $res->whereIn('cat_id', $cat_id_list);
            }
            if (!empty($brand_id)) {
                $res = $res->where('brand_id', $brand_id);
            }
        } else {
            $goods_id_list = $this->baseRepository->getExplode($goods_id);
            $res = $res->whereIn('goods_id', $goods_id_list);
        }

        $count = $res->where('original_img', '<>', '')->count();

        return $count;
    }

    public function getGoodsGallery($goods_id, $cat_id, $brand_id)
    {
        $res = GoodsGallery::where('img_original', '<>', '');

        if (empty($goods_id)) {
            if (!empty($cat_id) || !empty($brand_id)) {
                $res = $res->whereHas('getGoods', function ($query) use ($cat_id, $brand_id) {
                    if (!empty($cat_id)) {
                        $cat_id_list = $this->baseRepository->getExplode($cat_id);
                        $query = $query->whereIn('cat_id', $cat_id_list);
                    }
                    if (!empty($brand_id)) {
                        $query->where('brand_id', $brand_id);
                    }
                });
            }
        } else {
            $res = $res->whereHas('getGoods', function ($query) use ($goods_id) {
                $goods_id_list = $this->baseRepository->getExplode($goods_id);
                $query->whereIn('goods_id', $goods_id_list);
            });
        }

        $count = $res->count();

        return $count;
    }
}
