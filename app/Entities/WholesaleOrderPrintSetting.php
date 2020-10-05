<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WholesaleOrderPrintSetting
 */
class WholesaleOrderPrintSetting extends Model
{
    protected $table = 'wholesale_order_print_setting';

    public $timestamps = false;

    protected $fillable = [
        'suppliers_id',
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
    public function getSuppliersId()
    {
        return $this->suppliers_id;
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
    public function setSuppliersId($value)
    {
        $this->suppliers_id = $value;
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
