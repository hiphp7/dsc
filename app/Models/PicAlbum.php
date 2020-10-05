<?php

namespace App\Models;

use App\Entities\PicAlbum as Base;

/**
 * Class PicAlbum
 */
class PicAlbum extends Base
{
    /**
     * 关联相册信息
     *
     * @access  public
     * @param album_id
     * @return  array
     */
    public function getGalleryAlbum()
    {
        return $this->hasOne('App\Models\GalleryAlbum', 'album_id', 'album_id');
    }

    /**
     * 关联商品缩略图
     *
     * @access  public
     * @param goods_thumb
     * @return  array
     */
    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_thumb', 'pic_thumb');
    }

    /**
     * 关联商品相册缩略图
     *
     * @access  public
     * @param thumb_url
     * @return  array
     */
    public function getGoodsGallery()
    {
        return $this->hasOne('App\Models\GoodsGallery', 'thumb_url', 'pic_thumb');
    }
}
