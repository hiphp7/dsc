<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MerchantsStepsTitle
 */
class MerchantsStepsTitle extends Model
{
    protected $table = 'merchants_steps_title';

    protected $primaryKey = 'tid';

    public $timestamps = false;

    protected $fillable = [
        'fields_steps',
        'fields_titles',
        'steps_style',
        'titles_annotation',
        'fields_special',
        'special_type'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getFieldsSteps()
    {
        return $this->fields_steps;
    }

    /**
     * @return mixed
     */
    public function getFieldsTitles()
    {
        return $this->fields_titles;
    }

    /**
     * @return mixed
     */
    public function getStepsStyle()
    {
        return $this->steps_style;
    }

    /**
     * @return mixed
     */
    public function getTitlesAnnotation()
    {
        return $this->titles_annotation;
    }

    /**
     * @return mixed
     */
    public function getFieldsSpecial()
    {
        return $this->fields_special;
    }

    /**
     * @return mixed
     */
    public function getSpecialType()
    {
        return $this->special_type;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFieldsSteps($value)
    {
        $this->fields_steps = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFieldsTitles($value)
    {
        $this->fields_titles = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStepsStyle($value)
    {
        $this->steps_style = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTitlesAnnotation($value)
    {
        $this->titles_annotation = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFieldsSpecial($value)
    {
        $this->fields_special = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSpecialType($value)
    {
        $this->special_type = $value;
        return $this;
    }
}
