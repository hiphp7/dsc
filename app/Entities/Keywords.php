<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Keywords
 */
class Keywords extends Model
{
    protected $table = 'keywords';

    public $timestamps = false;

    protected $fillable = [
        'date',
        'searchengine',
        'keyword',
        'count'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return mixed
     */
    public function getSearchengine()
    {
        return $this->searchengine;
    }

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
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDate($value)
    {
        $this->date = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSearchengine($value)
    {
        $this->searchengine = $value;
        return $this;
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
    public function setCount($value)
    {
        $this->count = $value;
        return $this;
    }
}
