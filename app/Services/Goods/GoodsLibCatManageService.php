<?php

namespace App\Services\Goods;

use App\Models\GoodsLib;
use App\Models\GoodsLibCat;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;

class GoodsLibCatManageService
{
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
    }


    /**
     * 添加商品分类
     *
     * @param integer $cat_id
     * @param array $args
     *
     * @return  mix
     */
    public function catUpdate($cat_id, $args)
    {
        if (empty($args) || empty($cat_id)) {
            return false;
        }

        return GoodsLibCat::where('cat_id', $cat_id)->update($args);
    }

    public function libGetCatLevel($parent_id = 0, $level = 0)
    {

        $res = GoodsLibCat::where('parent_id', $parent_id)->orderBy('sort_order')->orderBy('cat_id');
        $res = $this->baseRepository->getToArrayGet($res);

        foreach ($res as $k => $row) {
            //ecmoban模板堂 --zhuo 查询服分类下子分类下的商品数量 start
            $cat_id_str = $this->libGetClassNav($res[$k]['cat_id']);
            $res[$k]['cat_child'] = substr($cat_id_str['catId'], 0, -1);
            if (empty($cat_id_str['catId'])) {
                $res[$k]['cat_child'] = substr($res[$k]['cat_id'], 0, -1);
            }

            $res[$k]['cat_child'] = isset($res[$k]['cat_child']) && !empty($res[$k]['cat_child']) ? $this->dscRepository->delStrComma($res[$k]['cat_child']) : '';

            $goods_lib = GoodsLib::select('goods_id');
            if ($res[$k]['cat_child']) {
                $cat_child = $this->baseRepository->getExplode($res[$k]['cat_child']);
                $goods_lib = $goods_lib->whereIn('lib_cat_id', $cat_child);
            }
            $goodsNums = $this->baseRepository->getToArrayGet($goods_lib);
            $goods_ids = [];
            foreach ($goodsNums as $num_key => $num_val) {
                $goods_ids[] = $num_val['goods_id'];
            }

            $res[$k]['goods_num'] = count($goodsNums);// + $goodsCat;

            //$res[$k]['goodsCat'] = $goodsCat; //扩展商品数量
            $res[$k]['goodsNum'] = $goodsNums; //本身以及子分类的商品数量
            //ecmoban模板堂 --zhuo 查询服分类下子分类下的商品数量 end

            $res[$k]['level'] = $level;
        }

        return $res;
    }

    public function libGetClassNav($cat_id)
    {
        $res = GoodsLibCat::where('cat_id', $cat_id);
        $res = $this->baseRepository->getToArrayGet($res);
        $arr = [
            "catId" => ''
        ];
        foreach ($res as $key => $row) {
            $arr[$key]['cat_id'] = $row['cat_id'];
            $arr[$key]['cat_name'] = $row['cat_name'];
            $arr[$key]['parent_id'] = $row['parent_id'];

            $arr['catId'] .= $row['cat_id'] . ",";
            $arr[$key]['child'] = $this->libGetParentChild($row['cat_id']);

            if (empty($arr[$key]['child']['catId'])) {
                $arr['catId'] = $arr['catId'];
            } else {
                $arr['catId'] .= $arr[$key]['child']['catId'];
            }
        }

        return $arr;
    }

    public function libGetParentChild($parent_id = 0)
    {
        $res = GoodsLibCat::where('parent_id', $parent_id);
        $res = $this->baseRepository->getToArrayGet($res);
        $arr = [
            "catId" => ''
        ];
        foreach ($res as $key => $row) {
            $arr[$key]['cat_id'] = $row['cat_id'];
            $arr[$key]['cat_name'] = $row['cat_name'];
            $arr[$key]['parent_id'] = $row['parent_id'];

            $arr['catId'] .= $row['cat_id'] . ",";
            $arr[$key]['child'] = $this->libGetParentChild($row['cat_id']);

            $arr['catId'] .= $arr[$key]['child']['catId'];
        }

        return $arr;
    }

}
