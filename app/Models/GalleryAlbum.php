<?php

namespace App\Models;

use App\Entities\GalleryAlbum as Base;

/**
 * Class GalleryAlbum
 */
class GalleryAlbum extends Base
{
    /**
     * 关联图片相册子列表
     *
     * @access  public
     * @param parent_album_id
     * @return  array
     */
    public function galleryAlbumChild()
    {
        return $this->hasOne('App\Models\GalleryAlbum', 'parent_album_id', 'album_id');
    }

    /**
     * 关联相册图片列表
     *
     * @return int
     */
    public function picAlbum()
    {
        return $this->hasOne('App\Models\PicAlbum', 'album_id', 'album_id');
    }

    public function getMerchantsShopInformation()
    {
        return $this->hasOne('App\Models\MerchantsShopInformation', 'user_id', 'ru_id');
    }
}
