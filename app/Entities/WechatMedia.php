<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WechatMedia
 */
class WechatMedia extends Model
{
    protected $table = 'wechat_media';

    public $timestamps = false;

    protected $fillable = [
        'wechat_id',
        'title',
        'command',
        'author',
        'is_show',
        'digest',
        'content',
        'link',
        'file',
        'size',
        'file_name',
        'thumb',
        'add_time',
        'edit_time',
        'type',
        'article_id',
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
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return mixed
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @return mixed
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @return mixed
     */
    public function getIsShow()
    {
        return $this->is_show;
    }

    /**
     * @return mixed
     */
    public function getDigest()
    {
        return $this->digest;
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
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return mixed
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @return mixed
     */
    public function getFileName()
    {
        return $this->file_name;
    }

    /**
     * @return mixed
     */
    public function getThumb()
    {
        return $this->thumb;
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
    public function getEditTime()
    {
        return $this->edit_time;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getArticleId()
    {
        return $this->article_id;
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
    public function setTitle($value)
    {
        $this->title = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCommand($value)
    {
        $this->command = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAuthor($value)
    {
        $this->author = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsShow($value)
    {
        $this->is_show = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDigest($value)
    {
        $this->digest = $value;
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
    public function setLink($value)
    {
        $this->link = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFile($value)
    {
        $this->file = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSize($value)
    {
        $this->size = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFileName($value)
    {
        $this->file_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setThumb($value)
    {
        $this->thumb = $value;
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
    public function setEditTime($value)
    {
        $this->edit_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setType($value)
    {
        $this->type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setArticleId($value)
    {
        $this->article_id = $value;
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
