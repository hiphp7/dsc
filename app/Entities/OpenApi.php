<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OpenApi
 */
class OpenApi extends Model
{
    protected $table = 'open_api';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'app_key',
        'action_code',
        'is_open',
        'add_time'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getAppKey()
    {
        return $this->app_key;
    }

    /**
     * @return mixed
     */
    public function getActionCode()
    {
        return $this->action_code;
    }

    /**
     * @return mixed
     */
    public function getIsOpen()
    {
        return $this->is_open;
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
    public function setName($value)
    {
        $this->name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAppKey($value)
    {
        $this->app_key = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setActionCode($value)
    {
        $this->action_code = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsOpen($value)
    {
        $this->is_open = $value;
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
