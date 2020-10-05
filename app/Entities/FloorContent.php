<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class FloorContent
 */
class FloorContent extends Model
{
    protected $table = 'floor_content';

    protected $primaryKey = 'fb_id';

    public $timestamps = false;

    protected $fillable = [
        'filename',
        'region',
        'id_name',
        'brand_id',
        'brand_name',
        'theme'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @return mixed
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @return mixed
     */
    public function getIdName()
    {
        return $this->id_name;
    }

    /**
     * @return mixed
     */
    public function getBrandId()
    {
        return $this->brand_id;
    }

    /**
     * @return mixed
     */
    public function getBrandName()
    {
        return $this->brand_name;
    }

    /**
     * @return mixed
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFilename($value)
    {
        $this->filename = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRegion($value)
    {
        $this->region = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIdName($value)
    {
        $this->id_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBrandId($value)
    {
        $this->brand_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBrandName($value)
    {
        $this->brand_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTheme($value)
    {
        $this->theme = $value;
        return $this;
    }
}
