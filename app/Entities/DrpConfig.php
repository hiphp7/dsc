<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DrpConfig
 */
class DrpConfig extends Model
{
    protected $table = 'drp_config';

    public $timestamps = false;

    protected $fillable = [
        'code',
        'type',
        'store_range',
        'value',
        'name',
        'warning',
        'sort_order',
        'group'
    ];

    protected $guarded = [];


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
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getStoreRange()
    {
        return $this->store_range;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

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
    public function getWarning()
    {
        return $this->warning;
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
    public function getGroup()
    {
        return $this->group;
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
    public function setType($value)
    {
        $this->type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStoreRange($value)
    {
        $this->store_range = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
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
    public function setWarning($value)
    {
        $this->warning = $value;
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
    public function setGroup($value)
    {
        $this->group = $value;
        return $this;
    }
}
