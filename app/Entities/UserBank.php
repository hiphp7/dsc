<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserBank
 */
class UserBank extends Model
{
    protected $table = 'user_bank';

    public $timestamps = false;

    protected $fillable = [
        'bank_name',
        'bank_card',
        'bank_region',
        'bank_user_name',
        'user_id'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getBankName()
    {
        return $this->bank_name;
    }

    /**
     * @return mixed
     */
    public function getBankCard()
    {
        return $this->bank_card;
    }

    /**
     * @return mixed
     */
    public function getBankRegion()
    {
        return $this->bank_region;
    }

    /**
     * @return mixed
     */
    public function getBankUserName()
    {
        return $this->bank_user_name;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBankName($value)
    {
        $this->bank_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBankCard($value)
    {
        $this->bank_card = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBankRegion($value)
    {
        $this->bank_region = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBankUserName($value)
    {
        $this->bank_user_name = $value;
        return $this;
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
}
