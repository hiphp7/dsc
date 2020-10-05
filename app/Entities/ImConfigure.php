<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ImConfigure
 */
class ImConfigure extends Model
{
    protected $table = 'im_configure';

    public $timestamps = false;

    protected $fillable = [
        'ser_id',
        'type',
        'content',
        'is_on'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getSerId()
    {
        return $this->ser_id;
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
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return mixed
     */
    public function getIsOn()
    {
        return $this->is_on;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSerId($value)
    {
        $this->ser_id = $value;
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
    public function setContent($value)
    {
        $this->content = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsOn($value)
    {
        $this->is_on = $value;
        return $this;
    }
}
