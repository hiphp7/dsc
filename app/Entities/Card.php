<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Card
 */
class Card extends Model
{
    protected $table = 'card';

    protected $primaryKey = 'card_id';

    public $timestamps = false;

    protected $fillable = [
        'card_name',
        'user_id',
        'card_img',
        'card_fee',
        'free_money',
        'card_desc'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getCardName()
    {
        return $this->card_name;
    }

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
    public function getCardImg()
    {
        return $this->card_img;
    }

    /**
     * @return mixed
     */
    public function getCardFee()
    {
        return $this->card_fee;
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
    public function getCardDesc()
    {
        return $this->card_desc;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCardName($value)
    {
        $this->card_name = $value;
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

    /**
     * @param $value
     * @return $this
     */
    public function setCardImg($value)
    {
        $this->card_img = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCardFee($value)
    {
        $this->card_fee = $value;
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
    public function setCardDesc($value)
    {
        $this->card_desc = $value;
        return $this;
    }
}
