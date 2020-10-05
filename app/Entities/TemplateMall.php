<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TemplateMall
 */
class TemplateMall extends Model
{
    protected $table = 'template_mall';

    protected $primaryKey = 'temp_id';

    public $timestamps = false;

    protected $fillable = [
        'temp_file',
        'temp_mode',
        'temp_cost',
        'temp_code',
        'add_time',
        'sales_volume'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getTempFile()
    {
        return $this->temp_file;
    }

    /**
     * @return mixed
     */
    public function getTempMode()
    {
        return $this->temp_mode;
    }

    /**
     * @return mixed
     */
    public function getTempCost()
    {
        return $this->temp_cost;
    }

    /**
     * @return mixed
     */
    public function getTempCode()
    {
        return $this->temp_code;
    }

    /**
     * @return mixed
     */
    public function getAddTime()
    {
        return $this->add_time;
    }

    /**
     * @return mixed
     */
    public function getSalesVolume()
    {
        return $this->sales_volume;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTempFile($value)
    {
        $this->temp_file = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTempMode($value)
    {
        $this->temp_mode = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTempCost($value)
    {
        $this->temp_cost = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTempCode($value)
    {
        $this->temp_code = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAddTime($value)
    {
        $this->add_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSalesVolume($value)
    {
        $this->sales_volume = $value;
        return $this;
    }
}
