<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SearchKeyword
 */
class SearchKeyword extends Model
{
    protected $table = 'search_keyword';

    protected $primaryKey = 'keyword_id';

    public $timestamps = false;

    protected $fillable = [
        'keyword',
        'pinyin',
        'is_on',
        'count',
        'addtime',
        'pinyin_keyword',
        'result_count'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * @return mixed
     */
    public function getPinyin()
    {
        return $this->pinyin;
    }

    /**
     * @return mixed
     */
    public function getIsOn()
    {
        return $this->is_on;
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
    public function getAddtime()
    {
        return $this->addtime;
    }

    /**
     * @return mixed
     */
    public function getPinyinKeyword()
    {
        return $this->pinyin_keyword;
    }

    /**
     * @return mixed
     */
    public function getResultCount()
    {
        return $this->result_count;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setKeyword($value)
    {
        $this->keyword = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPinyin($value)
    {
        $this->pinyin = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsOn($value)
    {
        $this->is_on = $value;
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
    public function setAddtime($value)
    {
        $this->addtime = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPinyinKeyword($value)
    {
        $this->pinyin_keyword = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setResultCount($value)
    {
        $this->result_count = $value;
        return $this;
    }
}
