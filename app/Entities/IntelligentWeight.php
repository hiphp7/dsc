<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class IntelligentWeight
 */
class IntelligentWeight extends Model
{
    protected $table = 'intelligent_weight';

    public $timestamps = false;

    protected $fillable = [
        'goods_id',
        'goods_number',
        'return_number',
        'user_number',
        'goods_comment_number',
        'merchants_comment_number',
        'user_attention_number'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getGoodsId()
    {
        return $this->goods_id;
    }

    /**
     * @return mixed
     */
    public function getGoodsNumber()
    {
        return $this->goods_number;
    }

    /**
     * @return mixed
     */
    public function getReturnNumber()
    {
        return $this->return_number;
    }

    /**
     * @return mixed
     */
    public function getUserNumber()
    {
        return $this->user_number;
    }

    /**
     * @return mixed
     */
    public function getGoodsCommentNumber()
    {
        return $this->goods_comment_number;
    }

    /**
     * @return mixed
     */
    public function getMerchantsCommentNumber()
    {
        return $this->merchants_comment_number;
    }

    /**
     * @return mixed
     */
    public function getUserAttentionNumber()
    {
        return $this->user_attention_number;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsId($value)
    {
        $this->goods_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsNumber($value)
    {
        $this->goods_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReturnNumber($value)
    {
        $this->return_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserNumber($value)
    {
        $this->user_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsCommentNumber($value)
    {
        $this->goods_comment_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMerchantsCommentNumber($value)
    {
        $this->merchants_comment_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserAttentionNumber($value)
    {
        $this->user_attention_number = $value;
        return $this;
    }
}
