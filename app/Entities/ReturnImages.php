<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ReturnImages
 */
class ReturnImages extends Model
{
    protected $table = 'return_images';

    public $timestamps = false;

    protected $fillable = [
        'rg_id',
        'rec_id',
        'user_id',
        'img_file',
        'add_time'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getRgId()
    {
        return $this->rg_id;
    }

    /**
     * @return mixed
     */
    public function getRecId()
    {
        return $this->rec_id;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @return mixed
     */
    public function getImgFile()
    {
        return $this->img_file;
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
    public function setRgId($value)
    {
        $this->rg_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRecId($value)
    {
        $this->rec_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserId($value)
    {
        $this->user_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setImgFile($value)
    {
        $this->img_file = $value;
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
