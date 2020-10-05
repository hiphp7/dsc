<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AutoManage
 */
class AutoManage extends Model
{
    protected $table = 'auto_manage';

    public $timestamps = false;

    protected $fillable = [
        'item_id',
        'type',
        'starttime',
        'endtime'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getItemId()
    {
        return $this->item_id;
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
    public function getStarttime()
    {
        return $this->starttime;
    }

    /**
     * @return mixed
     */
    public function getEndtime()
    {
        return $this->endtime;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setItemId($value)
    {
        $this->item_id = $value;
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
    public function setStarttime($value)
    {
        $this->starttime = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEndtime($value)
    {
        $this->endtime = $value;
        return $this;
    }
}
