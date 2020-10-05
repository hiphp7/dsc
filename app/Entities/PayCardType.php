<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class PayCardType
 */
class PayCardType extends Model
{
    protected $table = 'pay_card_type';

    protected $primaryKey = 'type_id';

    public $timestamps = false;

    protected $fillable = [
        'type_name',
        'type_money',
        'type_prefix',
        'use_end_date'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getTypeName()
    {
        return $this->type_name;
    }

    /**
     * @return mixed
     */
    public function getTypeMoney()
    {
        return $this->type_money;
    }

    /**
     * @return mixed
     */
    public function getTypePrefix()
    {
        return $this->type_prefix;
    }

    /**
     * @return mixed
     */
    public function getUseEndDate()
    {
        return $this->use_end_date;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTypeName($value)
    {
        $this->type_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTypeMoney($value)
    {
        $this->type_money = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTypePrefix($value)
    {
        $this->type_prefix = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUseEndDate($value)
    {
        $this->use_end_date = $value;
        return $this;
    }
}
