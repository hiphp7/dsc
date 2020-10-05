<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ArticleExtend
 */
class ArticleExtend extends Model
{
    protected $table = 'article_extend';

    public $timestamps = false;

    protected $fillable = [
        'article_id',
        'click',
        'likenum',
        'hatenum'
    ];

    protected $guarded = [];


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
    public function getClick()
    {
        return $this->click;
    }

    /**
     * @return mixed
     */
    public function getLikenum()
    {
        return $this->likenum;
    }

    /**
     * @return mixed
     */
    public function getHatenum()
    {
        return $this->hatenum;
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
    public function setClick($value)
    {
        $this->click = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLikenum($value)
    {
        $this->likenum = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setHatenum($value)
    {
        $this->hatenum = $value;
        return $this;
    }
}
