<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Comment
 */
class Comment extends Model
{
    protected $table = 'comment';

    protected $primaryKey = 'comment_id';

    public $timestamps = false;

    protected $fillable = [
        'comment_type',
        'id_value',
        'email',
        'user_name',
        'content',
        'comment_rank',
        'comment_server',
        'comment_delivery',
        'add_time',
        'ip_address',
        'status',
        'parent_id',
        'user_id',
        'ru_id',
        'single_id',
        'order_id',
        'rec_id',
        'goods_tag',
        'useful',
        'useful_user',
        'use_ip',
        'dis_id',
        'like_num',
        'dis_browse_num'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getCommentType()
    {
        return $this->comment_type;
    }

    /**
     * @return mixed
     */
    public function getIdValue()
    {
        return $this->id_value;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
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
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return mixed
     */
    public function getCommentRank()
    {
        return $this->comment_rank;
    }

    /**
     * @return mixed
     */
    public function getCommentServer()
    {
        return $this->comment_server;
    }

    /**
     * @return mixed
     */
    public function getCommentDelivery()
    {
        return $this->comment_delivery;
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
    public function getIpAddress()
    {
        return $this->ip_address;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getParentId()
    {
        return $this->parent_id;
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
    public function getRuId()
    {
        return $this->ru_id;
    }

    /**
     * @return mixed
     */
    public function getSingleId()
    {
        return $this->single_id;
    }

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
    public function getRecId()
    {
        return $this->rec_id;
    }

    /**
     * @return mixed
     */
    public function getGoodsTag()
    {
        return $this->goods_tag;
    }

    /**
     * @return mixed
     */
    public function getUseful()
    {
        return $this->useful;
    }

    /**
     * @return mixed
     */
    public function getUsefulUser()
    {
        return $this->useful_user;
    }

    /**
     * @return mixed
     */
    public function getUseIp()
    {
        return $this->use_ip;
    }

    /**
     * @return mixed
     */
    public function getDisId()
    {
        return $this->dis_id;
    }

    /**
     * @return mixed
     */
    public function getLikeNum()
    {
        return $this->like_num;
    }

    /**
     * @return mixed
     */
    public function getDisBrowseNum()
    {
        return $this->dis_browse_num;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCommentType($value)
    {
        $this->comment_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIdValue($value)
    {
        $this->id_value = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEmail($value)
    {
        $this->email = $value;
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
    public function setContent($value)
    {
        $this->content = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCommentRank($value)
    {
        $this->comment_rank = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCommentServer($value)
    {
        $this->comment_server = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCommentDelivery($value)
    {
        $this->comment_delivery = $value;
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
    public function setIpAddress($value)
    {
        $this->ip_address = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStatus($value)
    {
        $this->status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setParentId($value)
    {
        $this->parent_id = $value;
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
    public function setRuId($value)
    {
        $this->ru_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSingleId($value)
    {
        $this->single_id = $value;
        return $this;
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
    public function setRecId($value)
    {
        $this->rec_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsTag($value)
    {
        $this->goods_tag = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUseful($value)
    {
        $this->useful = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUsefulUser($value)
    {
        $this->useful_user = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUseIp($value)
    {
        $this->use_ip = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDisId($value)
    {
        $this->dis_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLikeNum($value)
    {
        $this->like_num = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDisBrowseNum($value)
    {
        $this->dis_browse_num = $value;
        return $this;
    }
}
