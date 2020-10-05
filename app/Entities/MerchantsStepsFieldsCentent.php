<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MerchantsStepsFieldsCentent
 */
class MerchantsStepsFieldsCentent extends Model
{
    protected $table = 'merchants_steps_fields_centent';

    public $timestamps = false;

    protected $fillable = [
        'tid',
        'textFields',
        'fieldsDateType',
        'fieldsLength',
        'fieldsNotnull',
        'fieldsFormName',
        'fieldsCoding',
        'fieldsForm',
        'fields_sort',
        'will_choose'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getTid()
    {
        return $this->tid;
    }

    /**
     * @return mixed
     */
    public function getTextFields()
    {
        return $this->textFields;
    }

    /**
     * @return mixed
     */
    public function getFieldsDateType()
    {
        return $this->fieldsDateType;
    }

    /**
     * @return mixed
     */
    public function getFieldsLength()
    {
        return $this->fieldsLength;
    }

    /**
     * @return mixed
     */
    public function getFieldsNotnull()
    {
        return $this->fieldsNotnull;
    }

    /**
     * @return mixed
     */
    public function getFieldsFormName()
    {
        return $this->fieldsFormName;
    }

    /**
     * @return mixed
     */
    public function getFieldsCoding()
    {
        return $this->fieldsCoding;
    }

    /**
     * @return mixed
     */
    public function getFieldsForm()
    {
        return $this->fieldsForm;
    }

    /**
     * @return mixed
     */
    public function getFieldsSort()
    {
        return $this->fields_sort;
    }

    /**
     * @return mixed
     */
    public function getWillChoose()
    {
        return $this->will_choose;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTid($value)
    {
        $this->tid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTextFields($value)
    {
        $this->textFields = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFieldsDateType($value)
    {
        $this->fieldsDateType = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFieldsLength($value)
    {
        $this->fieldsLength = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFieldsNotnull($value)
    {
        $this->fieldsNotnull = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFieldsFormName($value)
    {
        $this->fieldsFormName = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFieldsCoding($value)
    {
        $this->fieldsCoding = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFieldsForm($value)
    {
        $this->fieldsForm = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFieldsSort($value)
    {
        $this->fields_sort = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWillChoose($value)
    {
        $this->will_choose = $value;
        return $this;
    }
}
