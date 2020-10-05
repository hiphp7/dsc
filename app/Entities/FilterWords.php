<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class FilterWords
 */
class FilterWords extends Model
{
    protected $table = 'filter_words';

    public $timestamps = false;

    protected $fillable = [
        'words',
        'rank',
        'admin_id',
        'click_count',
        'status'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getWords()
    {
        return $this->words;
    }

    /**
     * @return mixed
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * @return mixed
     */
    public function getAdminId()
    {
        return $this->admin_id;
    }

    /**
     * @return mixed
     */
    public function getClickCount()
    {
        return $this->click_count;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWords($value)
    {
        $this->words = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRank($value)
    {
        $this->rank = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAdminId($value)
    {
        $this->admin_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setClickCount($value)
    {
        $this->click_count = $value;
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
}
