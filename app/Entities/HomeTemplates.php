<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class HomeTemplates
 */
class HomeTemplates extends Model
{
    protected $table = 'home_templates';

    protected $primaryKey = 'temp_id';

    public $timestamps = false;

    protected $fillable = [
        'rs_id',
        'code',
        'is_enable',
        'theme'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getRsId()
    {
        return $this->rs_id;
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return mixed
     */
    public function getIsEnable()
    {
        return $this->is_enable;
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
    public function setRsId($value)
    {
        $this->rs_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCode($value)
    {
        $this->code = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsEnable($value)
    {
        $this->is_enable = $value;
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
