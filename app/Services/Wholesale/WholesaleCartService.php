<?php

namespace App\Services\Wholesale;

use App\Models\WholesaleCart;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Goods\GoodsAttrService;

/**
 * 商城商品订单
 * Class CrowdFund
 * @package App\Services
 */
class WholesaleCartService
{
    protected $goodsAttrService;
    protected $sessionRepository;
    protected $dscRepository;
    protected $config;

    public function __construct(
        GoodsAttrService $goodsAttrService,
        SessionRepository $sessionRepository,
        DscRepository $dscRepository
    )
    {
        $this->goodsAttrService = $goodsAttrService;
        $this->sessionRepository = $sessionRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 购物车商品
     *
     * @access  public
     * @param string $tpl
     * @return  void
     */
    public function getGoodsCartList()
    {
        $user_id = session('user_id', 0);

        if (!empty($user_id)) {
            $res = WholesaleCart::where('user_id', $user_id);
        } else {
            $session_id = $this->sessionRepository->realCartMacIp();
            $res = WholesaleCart::where('session_id', $session_id);
        }

        $res = $res->whereHas('getGoods');

        $res = $res->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_thumb');
            }
        ]);

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $k => $v) {
                $v = $v['get_goods'] ? array_merge($v, $v['get_goods']) : $v;

                $res[$k]['goods_thumb'] = $this->dscRepository->getImagePath($v['goods_thumb']);
                $res[$k]['short_name'] = $this->config['goods_name_length'] > 0 ? $this->dscRepository->subStr($v['goods_name'], $this->config['goods_name_length']) : $v['goods_name'];
                $res[$k]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $v['goods_id']], $v['goods_name']);
                $res[$k]['goods_number'] = $v['goods_number'];
                $res[$k]['goods_name'] = $v['goods_name'];
                $res[$k]['goods_price'] = $this->dscRepository->getPriceFormat($v['goods_price']);
                $res[$k]['warehouse_id'] = $v['warehouse_id'];
                $res[$k]['area_id'] = $v['area_id'];
                $res[$k]['rec_id'] = $v['rec_id'];
                $res[$k]['extension_code'] = $v['extension_code'];

                $properties = $this->goodsAttrService->getGoodsProperties($v['goods_id'], $v['warehouse_id'], $v['area_id'], $v['area_city'], $v['goods_attr_id'], 1);
                if ($properties['spe']) {
                    $res[$k]['spe'] = array_values($properties['spe']);
                } else {
                    $res[$k]['spe'] = [];
                }
            }
        }

        return $res;
    }

    /**
     * 购物车商品信息
     *
     * @access  public
     * @param array $where
     * @return  array
     */
    public function getCartInfo($where = [])
    {
        $user_id = session('user_id', 0);

        $row = WholesaleCart::selectRaw("COUNT(*) AS cart_number, SUM(goods_number) AS number, SUM(goods_price * goods_number) AS amount");

        if (isset($where['rec_id'])) {
            $row = $row->where('rec_id', $where['rec_id']);
        }

        if (!empty($user_id)) {
            $row = $row->where('user_id', $user_id);
        } else {
            $session_id = $this->sessionRepository->realCartMacIp();
            $row = $row->where('session_id', $session_id);
        }

        $row = $row->first();

        $row = $row ? $row->toArray() : [];

        return $row;
    }
}
