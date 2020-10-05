<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UsersReal
 */
class UsersReal extends Model
{
    protected $table = 'users_real';

    protected $primaryKey = 'real_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'real_name',
        'bank_mobile',
        'bank_name',
        'bank_card',
        'self_num',
        'add_time',
        'review_content',
        'review_status',
        'review_time',
        'user_type',
        'front_of_id_card',
        'reverse_of_id_card'
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
    public function getRealName()
    {
        return $this->real_name;
    }

    /**
     * @return mixed
     */
    public function getBankMobile()
    {
        return $this->bank_mobile;
    }

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
    public function getSelfNum()
    {
        return $this->self_num;
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
    public function getReviewContent()
    {
        return $this->review_content;
    }

    /**
     * @return mixed
     */
    public function getReviewStatus()
    {
        return $this->review_status;
    }

    /**
     * @return mixed
     */
    public function getReviewTime()
    {
        return $this->review_time;
    }

    /**
     * @return mixed
     */
    public function getUserType()
    {
        return $this->user_type;
    }

    /**
     * @return mixed
     */
    public function getFrontOfIdCard()
    {
        return $this->front_of_id_card;
    }

    /**
     * @return mixed
     */
    public function getReverseOfIdCard()
    {
        return $this->reverse_of_id_card;
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
    public function setRealName($value)
    {
        $this->real_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBankMobile($value)
    {
        $this->bank_mobile = $value;
        return $this;
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
    public function setSelfNum($value)
    {
        $this->self_num = $value;
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
    public function setReviewContent($value)
    {
        $this->review_content = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReviewStatus($value)
    {
        $this->review_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReviewTime($value)
    {
        $this->review_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserType($value)
    {
        $this->user_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFrontOfIdCard($value)
    {
        $this->front_of_id_card = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReverseOfIdCard($value)
    {
        $this->reverse_of_id_card = $value;
        return $this;
    }
}
