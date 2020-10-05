<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TouchAuth
 */
class TouchAuth extends Model
{
    protected $table = 'touch_auth';

    public $timestamps = false;

    protected $fillable = [
        'auth_config',
        'type',
        'sort',
        'status'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getAuthConfig()
    {
        return $this->auth_config;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAuthConfig($value)
    {
        $this->auth_config = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setType($value)
    {
        $this->type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSort($value)
    {
        $this->sort = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStatus($value)
    {
        $this->status = $value;
        return $this;
    }
}
