<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserAccountFields
 */
class UserAccountFields extends Model
{
    protected $table = 'user_account_fields';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'account_id',
        'bank_number',
        'real_name'
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
    public function getAccountId()
    {
        return $this->account_id;
    }

    /**
     * @return mixed
     */
    public function getBankNumber()
    {
        return $this->bank_number;
    }

    /**
     * @return mixed
     */
    public function getRealName()
    {
        return $this->real_name;
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
    public function setAccountId($value)
    {
        $this->account_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBankNumber($value)
    {
        $this->bank_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRealName($value)
    {
        $this->real_name = $value;
        return $this;
    }
}
