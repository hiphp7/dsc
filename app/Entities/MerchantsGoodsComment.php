<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MerchantsGoodsComment
 */
class MerchantsGoodsComment extends Model
{
    protected $table = 'merchants_goods_comment';

    public $timestamps = false;

    protected $fillable = [
        'goods_id',
        'comment_start',
        'comment_end',
        'comment_last_percent'
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
    public function getCommentStart()
    {
        return $this->comment_start;
    }

    /**
     * @return mixed
     */
    public function getCommentEnd()
    {
        return $this->comment_end;
    }

    /**
     * @return mixed
     */
    public function getCommentLastPercent()
    {
        return $this->comment_last_percent;
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
    public function setCommentStart($value)
    {
        $this->comment_start = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCommentEnd($value)
    {
        $this->comment_end = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCommentLastPercent($value)
    {
        $this->comment_last_percent = $value;
        return $this;
    }
}
