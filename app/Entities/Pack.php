<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Pack
 */
class Pack extends Model
{
    protected $table = 'pack';

    protected $primaryKey = 'pack_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'pack_name',
        'pack_img',
        'pack_fee',
        'free_money',
        'pack_desc'
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
    public function getPackName()
    {
        return $this->pack_name;
    }

    /**
     * @return mixed
     */
    public function getPackImg()
    {
        return $this->pack_img;
    }

    /**
     * @return mixed
     */
    public function getPackFee()
    {
        return $this->pack_fee;
    }

    /**
     * @return mixed
     */
    public function getFreeMoney()
    {
        return $this->free_money;
    }

    /**
     * @return mixed
     */
    public function getPackDesc()
    {
        return $this->pack_desc;
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
    public function setPackName($value)
    {
        $this->pack_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPackImg($value)
    {
        $this->pack_img = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPackFee($value)
    {
        $this->pack_fee = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFreeMoney($value)
    {
        $this->free_money = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPackDesc($value)
    {
        $this->pack_desc = $value;
        return $this;
    }
}
