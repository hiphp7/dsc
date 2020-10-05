<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class PicAlbum
 */
class PicAlbum extends Model
{
    protected $table = 'pic_album';

    protected $primaryKey = 'pic_id';

    public $timestamps = false;

    protected $fillable = [
        'pic_name',
        'album_id',
        'pic_file',
        'pic_thumb',
        'pic_image',
        'pic_size',
        'pic_spec',
        'ru_id',
        'add_time'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getPicName()
    {
        return $this->pic_name;
    }

    /**
     * @return mixed
     */
    public function getAlbumId()
    {
        return $this->album_id;
    }

    /**
     * @return mixed
     */
    public function getPicFile()
    {
        return $this->pic_file;
    }

    /**
     * @return mixed
     */
    public function getPicThumb()
    {
        return $this->pic_thumb;
    }

    /**
     * @return mixed
     */
    public function getPicImage()
    {
        return $this->pic_image;
    }

    /**
     * @return mixed
     */
    public function getPicSize()
    {
        return $this->pic_size;
    }

    /**
     * @return mixed
     */
    public function getPicSpec()
    {
        return $this->pic_spec;
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
    public function getAddTime()
    {
        return $this->add_time;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPicName($value)
    {
        $this->pic_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAlbumId($value)
    {
        $this->album_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPicFile($value)
    {
        $this->pic_file = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPicThumb($value)
    {
        $this->pic_thumb = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPicImage($value)
    {
        $this->pic_image = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPicSize($value)
    {
        $this->pic_size = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPicSpec($value)
    {
        $this->pic_spec = $value;
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
    public function setAddTime($value)
    {
        $this->add_time = $value;
        return $this;
    }
}
