<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ReturnCause
 */
class ReturnCause extends Model
{
    protected $table = 'return_cause';

    protected $primaryKey = 'cause_id';

    public $timestamps = false;

    protected $fillable = [
        'cause_name',
        'parent_id',
        'sort_order',
        'is_show'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getCauseName()
    {
        return $this->cause_name;
    }

    /**
     * @return mixed
     */
    public function getParentId()
    {
        return $this->parent_id;
    }

    /**
     * @return mixed
     */
    public function getSortOrder()
    {
        return $this->sort_order;
    }

    /**
     * @return mixed
     */
    public function getIsShow()
    {
        return $this->is_show;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCauseName($value)
    {
        $this->cause_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setParentId($value)
    {
        $this->parent_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSortOrder($value)
    {
        $this->sort_order = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsShow($value)
    {
        $this->is_show = $value;
        return $this;
    }
}
