<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TemporaryFiles
 */
class TemporaryFiles extends Model
{
    protected $table = 'temporary_files';

    public $timestamps = false;

    protected $fillable = [
        'type',
        'path',
        'add_time',
        'identity',
        'user_id'
    ];

    protected $guarded = [];


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
    public function getPath()
    {
        return $this->path;
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
    public function getIdentity()
    {
        return $this->identity;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->user_id;
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
    public function setPath($value)
    {
        $this->path = $value;
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
    public function setIdentity($value)
    {
        $this->identity = $value;
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
}
