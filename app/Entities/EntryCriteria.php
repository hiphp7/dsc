<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class EntryCriteria
 */
class EntryCriteria extends Model
{
    protected $table = 'entry_criteria';

    public $timestamps = false;

    protected $fillable = [
        'parent_id',
        'criteria_name',
        'charge',
        'standard_name',
        'type',
        'is_mandatory',
        'option_value',
        'data_type',
        'is_cumulative'
    ];

    protected $guarded = [];


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
    public function getCriteriaName()
    {
        return $this->criteria_name;
    }

    /**
     * @return mixed
     */
    public function getCharge()
    {
        return $this->charge;
    }

    /**
     * @return mixed
     */
    public function getStandardName()
    {
        return $this->standard_name;
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
    public function getIsMandatory()
    {
        return $this->is_mandatory;
    }

    /**
     * @return mixed
     */
    public function getOptionValue()
    {
        return $this->option_value;
    }

    /**
     * @return mixed
     */
    public function getDataType()
    {
        return $this->data_type;
    }

    /**
     * @return mixed
     */
    public function getIsCumulative()
    {
        return $this->is_cumulative;
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
    public function setCriteriaName($value)
    {
        $this->criteria_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCharge($value)
    {
        $this->charge = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStandardName($value)
    {
        $this->standard_name = $value;
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
    public function setIsMandatory($value)
    {
        $this->is_mandatory = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOptionValue($value)
    {
        $this->option_value = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDataType($value)
    {
        $this->data_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsCumulative($value)
    {
        $this->is_cumulative = $value;
        return $this;
    }
}
