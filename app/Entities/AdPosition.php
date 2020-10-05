<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AdPosition
 */
class AdPosition extends Model
{
    protected $table = 'ad_position';

    protected $primaryKey = 'position_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'position_name',
        'ad_width',
        'ad_height',
        'position_model',
        'position_desc',
        'position_style',
        'is_public',
        'theme'
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
    public function getPositionModel()
    {
        return $this->position_model;
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
    public function getPositionStyle()
    {
        return $this->position_style;
    }

    /**
     * @return mixed
     */
    public function getIsPublic()
    {
        return $this->is_public;
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
    public function setPositionModel($value)
    {
        $this->position_model = $value;
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
    public function setPositionStyle($value)
    {
        $this->position_style = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsPublic($value)
    {
        $this->is_public = $value;
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
