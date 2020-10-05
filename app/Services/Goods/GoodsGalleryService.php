<?php

namespace App\Services\Goods;

use App\Models\Goods;
use App\Models\GoodsGallery;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;

class GoodsGalleryService
{
    protected $baseRepository;
    protected $config;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 获得指定商品的相册
     *
     * @access  public
     * @param integer $goods_id
     * @return  array
     */
    public function getGoodsGallery($goods_id, $gallery_number = 0)
    {
        if (!$gallery_number) {
            $gallery_number = $this->config['goods_gallery_number'];
        }

        $row = GoodsGallery::where('goods_id', $goods_id)->orderBy('img_desc')->take($gallery_number);
        $row = $this->baseRepository->getToArrayGet($row);

        /* 格式化相册图片路径 */
        if ($row) {
            foreach ($row as $key => $gallery_img) {
                if (!empty($gallery_img['external_url'])) {
                    $row[$key]['img_url'] = $gallery_img['external_url'];
                    $row[$key]['thumb_url'] = $gallery_img['external_url'];
                } else {
                    $row[$key]['img_url'] = $this->dscRepository->getImagePath($gallery_img['img_url']);
                    $row[$key]['thumb_url'] = $this->dscRepository->getImagePath($gallery_img['thumb_url']);
                }
            }

            /* 商品无相册图调用商品图 */
            if (!$row) {
                $goods_thumb = Goods::where('goods_id', $goods_id)->value('goods_thumb');

                $row = [
                    [
                        'img_url' => $this->dscRepository->getImagePath($goods_thumb),
                        'thumb_url' => $this->dscRepository->getImagePath($goods_thumb)
                    ]
                ];
            }
        }

        return $row;
    }

    /**
     * 获取相册图库列表
     *
     * @param array $where
     * @return mixed
     */
    public function getGalleryList($where = [])
    {
        $img_list = GoodsGallery::whereRaw(1);

        if (isset($where['img_id']) && $where['img_id']) {
            $img_id = $this->baseRepository->getExplode($where['img_id']);
            if (count($img_id) > 1) {
                $img_list = $img_list->whereIn('img_id', $img_id);
            } else {
                $img_list = $img_list->where('img_id', $img_id);
            }
        }

        if (isset($where['goods_id'])) {
            $img_list = $img_list->where('goods_id', $where['goods_id']);
        }

        if (isset($where['single_id'])) {
            $img_list = $img_list->where('single_id', $where['single_id']);
        }

        $img_list = $img_list->orderBy('img_desc');

        $img_list = $this->baseRepository->getToArrayGet($img_list);

        if ($img_list) {
            foreach ($img_list as $key => $val) {
                $img_list[$key]['thumb_url'] = $this->dscRepository->getImagePath($val['thumb_url']);
                $img_list[$key]['img_original'] = $this->dscRepository->getImagePath($val['img_original']);
                $img_list[$key]['img_url'] = $this->dscRepository->getImagePath($val['img_url']);
            }
        }

        return $img_list;
    }
}
