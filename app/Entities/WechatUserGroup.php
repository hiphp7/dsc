<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WechatUserGroup
 */
class WechatUserGroup extends Model
{
    protected $table = 'wechat_user_group';

    public $timestamps = false;

    protected $fillable = [
        'wechat_id',
        'group_id',
        'name',
        'count',
        'sort'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getWechatId()
    {
        return $this->wechat_id;
    }

    /**
     * @return mixed
     */
    public function getGroupId()
    {
        return $this->group_id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @return mixed
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWechatId($value)
    {
        $this->wechat_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGroupId($value)
    {
        $this->group_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setName($value)
    {
        $this->name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCount($value)
    {
        $this->count = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSort($value)
    {
        $this->sort = $value;
        return $this;
    }
}
