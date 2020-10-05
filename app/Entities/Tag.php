<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Tag
 */
class Tag extends Model
{
    protected $table = 'tag';

    protected $primaryKey = 'tag_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'goods_id',
        'tag_words'
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
    public function getGoodsId()
    {
        return $this->goods_id;
    }

    /**
     * @return mixed
     */
    public function getTagWords()
    {
        return $this->tag_words;
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
    public function setGoodsId($value)
    {
        $this->goods_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTagWords($value)
    {
        $this->tag_words = $value;
        return $this;
    }
}
