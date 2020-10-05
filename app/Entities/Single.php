<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Single
 */
class Single extends Model
{
    protected $table = 'single';

    protected $primaryKey = 'single_id';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'single_name',
        'single_description',
        'single_like',
        'user_name',
        'is_audit',
        'order_sn',
        'addtime',
        'goods_name',
        'goods_id',
        'user_id',
        'order_time',
        'comment_id',
        'single_ip',
        'cat_id',
        'integ',
        'single_browse_num',
        'cover'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * @return mixed
     */
    public function getSingleName()
    {
        return $this->single_name;
    }

    /**
     * @return mixed
     */
    public function getSingleDescription()
    {
        return $this->single_description;
    }

    /**
     * @return mixed
     */
    public function getSingleLike()
    {
        return $this->single_like;
    }

    /**
     * @return mixed
     */
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * @return mixed
     */
    public function getIsAudit()
    {
        return $this->is_audit;
    }

    /**
     * @return mixed
     */
    public function getOrderSn()
    {
        return $this->order_sn;
    }

    /**
     * @return mixed
     */
    public function getAddtime()
    {
        return $this->addtime;
    }

    /**
     * @return mixed
     */
    public function getGoodsName()
    {
        return $this->goods_name;
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
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @return mixed
     */
    public function getOrderTime()
    {
        return $this->order_time;
    }

    /**
     * @return mixed
     */
    public function getCommentId()
    {
        return $this->comment_id;
    }

    /**
     * @return mixed
     */
    public function getSingleIp()
    {
        return $this->single_ip;
    }

    /**
     * @return mixed
     */
    public function getCatId()
    {
        return $this->cat_id;
    }

    /**
     * @return mixed
     */
    public function getInteg()
    {
        return $this->integ;
    }

    /**
     * @return mixed
     */
    public function getSingleBrowseNum()
    {
        return $this->single_browse_num;
    }

    /**
     * @return mixed
     */
    public function getCover()
    {
        return $this->cover;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrderId($value)
    {
        $this->order_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSingleName($value)
    {
        $this->single_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSingleDescription($value)
    {
        $this->single_description = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSingleLike($value)
    {
        $this->single_like = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserName($value)
    {
        $this->user_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsAudit($value)
    {
        $this->is_audit = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrderSn($value)
    {
        $this->order_sn = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAddtime($value)
    {
        $this->addtime = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsName($value)
    {
        $this->goods_name = $value;
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
    public function setUserId($value)
    {
        $this->user_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrderTime($value)
    {
        $this->order_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCommentId($value)
    {
        $this->comment_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSingleIp($value)
    {
        $this->single_ip = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCatId($value)
    {
        $this->cat_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setInteg($value)
    {
        $this->integ = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSingleBrowseNum($value)
    {
        $this->single_browse_num = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCover($value)
    {
        $this->cover = $value;
        return $this;
    }
}
