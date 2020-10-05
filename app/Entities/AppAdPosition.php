<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AppAdPosition
 */
class AppAdPosition extends Model
{
    protected $table = 'app_ad_position';

    protected $primaryKey = 'position_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'position_name',
        'ad_width',
        'ad_height',
        'position_desc',
        'location_type',
        'position_type'
    ];

    protected $guarded = [];


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
    public function getPositionName()
    {
        return $this->position_name;
    }

    /**
     * @return mixed
     */
    public function getAdWidth()
    {
        return $this->ad_width;
    }

    /**
     * @return mixed
     */
    public function getAdHeight()
    {
        return $this->ad_height;
    }

    /**
     * @return mixed
     */
    public function getPositionDesc()
    {
        return $this->position_desc;
    }

    /**
     * @return mixed
     */
    public function getLocationType()
    {
        return $this->location_type;
    }

    /**
     * @return mixed
     */
    public function getPositionType()
    {
        return $this->position_type;
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
    public function setPositionName($value)
    {
        $this->position_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAdWidth($value)
    {
        $this->ad_width = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAdHeight($value)
    {
        $this->ad_height = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPositionDesc($value)
    {
        $this->position_desc = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLocationType($value)
    {
        $this->location_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPositionType($value)
    {
        $this->position_type = $value;
        return $this;
    }
}
