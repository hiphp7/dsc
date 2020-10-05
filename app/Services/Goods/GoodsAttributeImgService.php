<?php

namespace App\Services\Goods;

use App\Models\AttributeImg;
use App\Repositories\Common\BaseRepository;

class GoodsAttributeImgService
{
    protected $baseRepository;

    public function __construct(
        BaseRepository $baseRepository
    )
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * 取得属性图片以及外链 替换 商品属性 图片以及外链
     *
     * @param int $attr_id
     * @param string $attr_value
     * @param array $img_site
     * @return mixed
     */
    public function getHasAttrInfo($attr_id = 0, $attr_value = '', $img_site = [])
    {
        $res = AttributeImg::where('attr_values', $attr_value)->where('attr_id', $attr_id);
        $res = $this->baseRepository->getToArrayFirst($res);

        if ($img_site && !empty($img_site['attr_img_flie'])) {
            $res['attr_img'] = $img_site['attr_img_flie'];
        }

        if ($img_site && !empty($img_site['attr_img_site'])) {
            $res['attr_site'] = $img_site['attr_img_site'];
        }

        return $res;
    }
}