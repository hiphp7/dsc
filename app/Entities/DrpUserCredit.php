<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DrpUserCredit
 */
class DrpUserCredit extends Model
{
    protected $table = 'drp_user_credit';

    public $timestamps = false;

    protected $fillable = [
        'credit_name',
        'min_money',
        'max_money',
        'condition_id'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getCreditName()
    {
        return $this->credit_name;
    }

    /**
     * @return mixed
     */
    public function getMinMoney()
    {
        return $this->min_money;
    }

    /**
     * @return mixed
     */
    public function getMaxMoney()
    {
        return $this->max_money;
    }

    /**
     * @return mixed
     */
    public function getConditionId()
    {
        return $this->condition_id;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCreditName($value)
    {
        $this->credit_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMinMoney($value)
    {
        $this->min_money = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMaxMoney($value)
    {
        $this->max_money = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setConditionId($value)
    {
        $this->condition_id = $value;
        return $this;
    }
}
