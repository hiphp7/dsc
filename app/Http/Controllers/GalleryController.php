<?php

namespace App\Http\Controllers;

use App\Models\Goods;
use App\Repositories\Common\DscRepository;
use App\Services\Goods\GoodsGalleryService;

/**
 * 商品相册
 */
class GalleryController extends InitController
{
    protected $goodsGalleryService;
    protected $dscRepository;

    public function __construct(
        GoodsGalleryService $goodsGalleryService,
        DscRepository $dscRepository
    )
    {
        $this->goodsGalleryService = $goodsGalleryService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {

        /* 参数 */
        // 商品编号
        $goods_id = (int)request()->input('id', 0);

        //模板缓存
        $cache_id = sprintf('%X', crc32($goods_id . '-' . session('user_rank', 0) . '_' . $this->config['lang']));
        $content = cache()->remember('gallery.dwt.' . $cache_id, $this->config['cache_time'], function () use ($goods_id) {
            /* 获得商品名称 */
            $goods_name = Goods::where('goods_id', $goods_id)->value('goods_name');

            /* 如果该商品不存在，返回首页 */
            if ($goods_name === false) {
                return dsc_header("Location: ./\n");
            }

            /* 获得所有的图片 */
            $where = [
                'goods_id' => $goods_id
            ];
            $img_list = $this->goodsGalleryService->getGalleryList($where);

            $img_count = count($img_list);

            $galleryInfo = ['goods_name' => htmlspecialchars($goods_name, ENT_QUOTES), 'list' => []];
            if ($img_count == 0) {
                /* 如果没有图片，返回商品详情页 */
                return dsc_header('Location: goods.php?id=' . $goods_id . "\n");
            } else {
                if ($img_list) {
                    foreach ($img_list as $key => $img) {
                        $galleryInfo['list'][] = [
                            'gallery_thumb' => $this->dscRepository->getImagePath($img_list[$key]['thumb_url']),
                            'gallery' => $this->dscRepository->getImagePath($img_list[$key]['img_url']),
                            'img_desc' => $img_list[$key]['img_desc']
                        ];
                    }
                }
            }

            $this->smarty->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
            $this->smarty->assign('watermark', str_replace('../', './', $GLOBALS['_CFG']['watermark']));
            $this->smarty->assign('gallery', $galleryInfo);

            return $this->smarty->display('gallery.dwt');
        });

        return $content;
    }
}
