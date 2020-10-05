<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Article
 */
class Article extends Model
{
    protected $table = 'article';

    protected $primaryKey = 'article_id';

    public $timestamps = false;

    protected $fillable = [
        'cat_id',
        'title',
        'content',
        'author',
        'author_email',
        'keywords',
        'article_type',
        'is_open',
        'add_time',
        'file_url',
        'open_type',
        'link',
        'description',
        'sort_order'
    ];

    protected $guarded = [];


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
    public function getTitle()
    {
        return $this->title;
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
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @return mixed
     */
    public function getAuthorEmail()
    {
        return $this->author_email;
    }

    /**
     * @return mixed
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @return mixed
     */
    public function getArticleType()
    {
        return $this->article_type;
    }

    /**
     * @return mixed
     */
    public function getIsOpen()
    {
        return $this->is_open;
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
    public function getFileUrl()
    {
        return $this->file_url;
    }

    /**
     * @return mixed
     */
    public function getOpenType()
    {
        return $this->open_type;
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
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function getSortOrder()
    {
        return $this->sort_order;
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
    public function setTitle($value)
    {
        $this->title = $value;
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
    public function setAuthor($value)
    {
        $this->author = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAuthorEmail($value)
    {
        $this->author_email = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setKeywords($value)
    {
        $this->keywords = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setArticleType($value)
    {
        $this->article_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsOpen($value)
    {
        $this->is_open = $value;
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
    public function setFileUrl($value)
    {
        $this->file_url = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOpenType($value)
    {
        $this->open_type = $value;
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
    public function setDescription($value)
    {
        $this->description = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSortOrder($value)
    {
        $this->sort_order = $value;
        return $this;
    }
}
