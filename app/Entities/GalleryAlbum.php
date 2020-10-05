<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GalleryAlbum
 */
class GalleryAlbum extends Model
{
    protected $table = 'gallery_album';

    protected $primaryKey = 'album_id';

    public $timestamps = false;

    protected $fillable = [
        'parent_album_id',
        'ru_id',
        'album_mame',
        'album_cover',
        'album_desc',
        'sort_order',
        'add_time',
        'suppliers_id'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getParentAlbumId()
    {
        return $this->parent_album_id;
    }

    /**
     * @return mixed
     */
    public function getRuId()
    {
        return $this->ru_id;
    }

    /**
     * @return mixed
     */
    public function getAlbumMame()
    {
        return $this->album_mame;
    }

    /**
     * @return mixed
     */
    public function getAlbumCover()
    {
        return $this->album_cover;
    }

    /**
     * @return mixed
     */
    public function getAlbumDesc()
    {
        return $this->album_desc;
    }

    /**
     * @return mixed
     */
    public function getSortOrder()
    {
        return $this->sort_order;
    }

    /**
     * @return mixed
     */
    public function getAddTime()
    {
        return $this->add_time;
    }

    /**
     * @return mixed
     */
    public function getSuppliersId()
    {
        return $this->suppliers_id;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setParentAlbumId($value)
    {
        $this->parent_album_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRuId($value)
    {
        $this->ru_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAlbumMame($value)
    {
        $this->album_mame = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAlbumCover($value)
    {
        $this->album_cover = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAlbumDesc($value)
    {
        $this->album_desc = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSortOrder($value)
    {
        $this->sort_order = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAddTime($value)
    {
        $this->add_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSuppliersId($value)
    {
        $this->suppliers_id = $value;
        return $this;
    }
}
