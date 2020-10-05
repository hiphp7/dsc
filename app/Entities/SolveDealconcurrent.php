<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SolveDealconcurrent
 */
class SolveDealconcurrent extends Model
{
    protected $table = 'solve_dealconcurrent';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'orec_id',
        'add_time',
        'solve_type',
        'flow_type'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @return mixed
     */
    public function getOrecId()
    {
        return $this->orec_id;
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
    public function getSolveType()
    {
        return $this->solve_type;
    }

    /**
     * @return mixed
     */
    public function getFlowType()
    {
        return $this->flow_type;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserId($value)
    {
        $this->user_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrecId($value)
    {
        $this->orec_id = $value;
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
    public function setSolveType($value)
    {
        $this->solve_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFlowType($value)
    {
        $this->flow_type = $value;
        return $this;
    }
}
