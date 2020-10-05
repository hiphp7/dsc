<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class FilterWordsRanks
 */
class FilterWordsRanks extends Model
{
    protected $table = 'filter_words_ranks';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'scenes',
        'action'
    ];

    protected $guarded = [];


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
    public function getScenes()
    {
        return $this->scenes;
    }

    /**
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
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
    public function setScenes($value)
    {
        $this->scenes = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAction($value)
    {
        $this->action = $value;
        return $this;
    }
}
