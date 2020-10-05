<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class RegFields
 */
class RegFields extends Model
{
    protected $table = 'reg_fields';

    public $timestamps = false;

    protected $fillable = [
        'reg_field_name',
        'dis_order',
        'display',
        'type',
        'is_need'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getRegFieldName()
    {
        return $this->reg_field_name;
    }

    /**
     * @return mixed
     */
    public function getDisOrder()
    {
        return $this->dis_order;
    }

    /**
     * @return mixed
     */
    public function getDisplay()
    {
        return $this->display;
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
    public function getIsNeed()
    {
        return $this->is_need;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRegFieldName($value)
    {
        $this->reg_field_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDisOrder($value)
    {
        $this->dis_order = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDisplay($value)
    {
        $this->display = $value;
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
    public function setIsNeed($value)
    {
        $this->is_need = $value;
        return $this;
    }
}
