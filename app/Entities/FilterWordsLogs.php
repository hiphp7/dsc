<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class FilterWordsLogs
 */
class FilterWordsLogs extends Model
{
    protected $table = 'filter_words_logs';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'filter_words',
        'note',
        'url',
        'user_name'
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
    public function getFilterWords()
    {
        return $this->filter_words;
    }

    /**
     * @return mixed
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return mixed
     */
    public function getUserName()
    {
        return $this->user_name;
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
    public function setFilterWords($value)
    {
        $this->filter_words = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setNote($value)
    {
        $this->note = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUrl($value)
    {
        $this->url = $value;
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
}
