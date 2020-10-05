<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MerchantsStepsProcess
 */
class MerchantsStepsProcess extends Model
{
    protected $table = 'merchants_steps_process';

    public $timestamps = false;

    protected $fillable = [
        'process_steps',
        'process_title',
        'process_article',
        'steps_sort',
        'is_show',
        'fields_next'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getProcessSteps()
    {
        return $this->process_steps;
    }

    /**
     * @return mixed
     */
    public function getProcessTitle()
    {
        return $this->process_title;
    }

    /**
     * @return mixed
     */
    public function getProcessArticle()
    {
        return $this->process_article;
    }

    /**
     * @return mixed
     */
    public function getStepsSort()
    {
        return $this->steps_sort;
    }

    /**
     * @return mixed
     */
    public function getIsShow()
    {
        return $this->is_show;
    }

    /**
     * @return mixed
     */
    public function getFieldsNext()
    {
        return $this->fields_next;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setProcessSteps($value)
    {
        $this->process_steps = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setProcessTitle($value)
    {
        $this->process_title = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setProcessArticle($value)
    {
        $this->process_article = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStepsSort($value)
    {
        $this->steps_sort = $value;
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

    /**
     * @param $value
     * @return $this
     */
    public function setFieldsNext($value)
    {
        $this->fields_next = $value;
        return $this;
    }
}
