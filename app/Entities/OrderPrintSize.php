<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderPrintSize
 */
class OrderPrintSize extends Model
{
    protected $table = 'order_print_size';

    public $timestamps = false;

    protected $fillable = [
        'type',
        'specification',
        'width',
        'height',
        'size',
        'description'
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
    public function getSpecification()
    {
        return $this->specification;
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
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return mixed
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
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
    public function setSpecification($value)
    {
        $this->specification = $value;
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
    public function setHeight($value)
    {
        $this->height = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSize($value)
    {
        $this->size = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDescription($value)
    {
        $this->description = $value;
        return $this;
    }
}
