<?php

namespace App\Http\Controllers;

use App\Services\Goods\GoodsService;

class AffiliateController extends InitController
{
    protected $goodsService;
    protected $config;

    public function __construct(
        GoodsService $goodsService
    )
    {
        $this->goodsService = $goodsService;
        $this->config = $this->config();
    }

    public function index()
    {
        /**
         * Start
         *
         * @param $warehouse_id 仓库ID
         * @param $area_id 省份ID
         * @param $area_city 城市ID
         */
        $warehouse_id = $this->warehouseId();
        $area_id = $this->areaId();
        $area_city = $this->areaCity();
        /* End */

        $display_mode = addslashes(request()->input('display_mode', 'javascript'));

        if ($display_mode == 'javascript') {
            $charset = 'UTF8';
            header('content-type: application/x-javascript; charset=' . ($charset == 'UTF8' ? 'utf-8' : $charset));
        }

        $goodsid = intval(request()->input('gid', 0));
        $userid = intval(request()->input('u', 0));
        $type = intval(request()->input('type', 0));

        $tpl = storage_public(DATA_DIR . '/affiliate.html');

        $cache_id = sprintf('%X', crc32($tpl . '_' . $userid . '_' . $warehouse_id . '_' . $area_id . '_' . $area_city . '_' . $type . '-' . session('user_rank', 0) . '_' . $this->config['lang']));
        $content = cache()->remember('affiliate.html.' . $cache_id, $this->config['cache_time'], function () use ($tpl, $userid, $goodsid, $warehouse_id, $area_id, $area_city, $type) {
            $goods_url = url('/') . '/' . "goods.php?u=$userid&id=";

            $where = [
                'goods_id' => $goodsid,
                'warehouse_id' => $warehouse_id,
                'area_id' => $area_id,
                'area_city' => $area_city
            ];
            $goods = $this->goodsService->getGoodsInfo($where);

            $goods['goods_thumb'] = (strpos($goods['goods_thumb'], 'http://') === false && strpos($goods['goods_thumb'], 'https://') === false) ? asset('/') . $goods['goods_thumb'] : $goods['goods_thumb'];
            $goods['goods_img'] = (strpos($goods['goods_img'], 'http://') === false && strpos($goods['goods_img'], 'https://') === false) ? asset('/') . $goods['goods_img'] : $goods['goods_img'];
            $goods['shop_price'] = price_format($goods['shop_price']);

            $this->smarty->assign('goods', $goods);
            $this->smarty->assign('userid', $userid);
            $this->smarty->assign('type', $type);

            $this->smarty->assign('url', url('/') . '/');
            $this->smarty->assign('goods_url', $goods_url);

            return $this->smarty->fetch($tpl);
        });

        $output = str_replace("\r", '', $content);
        $output = str_replace("\n", '', $output);

        if ($display_mode == 'javascript') {
            echo "document.write('$output');";
        } elseif ($display_mode == 'iframe') {
            echo $output;
        }
    }
}
