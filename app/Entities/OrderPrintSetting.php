<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderPrintSetting
 */
class OrderPrintSetting extends Model
{
    protected $table = 'order_print_setting';

    public $timestamps = false;

    protected $fillable = [
        'ru_id',
        'specification',
        'printer',
        'width',
        'is_default',
        'sort_order'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getRuId()
    {
        return $this->ru_id;
    }

    /**
     * @return mixed
     */
    public function getSpecification()
    {
        return $this->specification;
    }

    /**
     * @return mixed
     */
    public function getPrinter()
    {
        return $this->printer;
    }

    /**
     * @return mixed
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return mixed
     */
    public function getIsDefault()
    {
        return $this->is_default;
    }

    /**
     * @return mixed
     */
    public function getSortOrder()
    {
        return $this->sort_order;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRuId($value)
    {
        $this->ru_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSpecification($value)
    {
        $this->specification = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPrinter($value)
    {
        $this->printer = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWidth($value)
    {
        $this->width = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsDefault($value)
    {
        $this->is_default = $value;
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
}
