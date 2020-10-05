<?php

namespace App\Services\Goods;

use App\Models\GoodsAttr;
use App\Repositories\Common\BaseRepository;

class GoodsProdutsAreaBatchManageService
{
    protected $baseRepository;

    public function __construct(
        BaseRepository $baseRepository

    )
    {
        $this->baseRepository = $baseRepository;
    }


    //商品属性 start
    public function getAttributeList($goods_id = 0)
    {
        $res = GoodsAttr::where('goods_id', $goods_id);
        $res = $res->whereHas('getGoodsAttribute');
        $res = $res->groupBy('attr_id')->orderBy('attr_id');
        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        foreach ($res as $key => $row) {
            $arr[$key]['attr_name'] = $row['attr_name'];
            $arr[$key]['goods_attr'] = $this->getGoodsAttrList($row['attr_id'], $goods_id);
        }

        return $arr;
    }

    public function getGoodsAttrList($attr_id = 0, $goods_id = 0)
    {
        $res = GoodsAttr::where('goods_id', $goods_id)
            ->where('attr_id', $attr_id)
            ->orderBy('goods_attr_id');
        $res = $this->baseRepository->getToArrayGet($res);
        $arr = [];
        foreach ($res as $key => $row) {
            $arr[$key]['goods_attr_id'] = $row['goods_attr_id'];
            $arr[$key]['attr_value'] = $row['attr_value'];
        }

        return $arr;
    }

    //商品属性 end

    public function getListDownload($goods_sn = '', $warehouse_info = [], $attr_info, $attr_num, $model = 0)
    {
        $arr = [];
        $attr = [];

        //0:默认模式 1:仓库模式 2:地区模式
        if (count($warehouse_info) > 0 && $model == 2) {
            //格式化数组;
            if ($attr_info) {
                foreach ($attr_info as $k => $v) {
                    if ($v) {
                        foreach ($v as $k2 => $v2) {
                            if ($k2 == 'attr_values') {
                                foreach ($v2 as $kid => $rid) {
                                    $v2[$kid] = $rid . "-" . $v['attr_id'];
                                }

                                $attr[] = $v2;
                            }
                        }
                    }
                }
            }

            if ($attr) {
                $comb = combination(array_keys($attr), $attr_num);
                $res = [];
                foreach ($comb as $r) {
                    $t = [];
                    foreach ($r as $k) {
                        $t[] = $attr[$k];
                    }
                    $res = array_merge($res, attr_group($t));
                }

                //组合数据;
                foreach ($res as $k => $v) {
                    $arr[$k]['goods_sn'] = $goods_sn;
                    $arr[$k]['region_name'] = $warehouse_info[0];
                    $arr[$k]['attr_value'] = $v;

                    if ($GLOBALS['_CFG']['goods_attr_price'] == 1) {
                        if ($GLOBALS['_CFG']['add_shop_price'] == 0) {
                            $arr[$k]['product_market_price'] = '';
                        }
                        $arr[$k]['product_price'] = '';
                        if ($GLOBALS['_CFG']['add_shop_price'] == 0) {
                            $arr[$k]['product_promote_price'] = '';
                        }
                    }

                    $arr[$k]['product_number'] = '';
                    $arr[$k]['product_warn_number'] = '';
                    $arr[$k]['product_sn'] = '';
                    $arr[$k]['bar_code'] = '';
                }
            }
        }

        return $arr;
    }
}
