<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Vote
 */
class Vote extends Model
{
    protected $table = 'vote';

    protected $primaryKey = 'vote_id';

    public $timestamps = false;

    protected $fillable = [
        'vote_name',
        'start_time',
        'end_time',
        'can_multi',
        'vote_count'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getVoteName()
    {
        return $this->vote_name;
    }

    /**
     * @return mixed
     */
    public function getStartTime()
    {
        return $this->start_time;
    }

    /**
     * @return mixed
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * @return mixed
     */
    public function getCanMulti()
    {
        return $this->can_multi;
    }

    /**
     * @return mixed
     */
    public function getVoteCount()
    {
        return $this->vote_count;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setVoteName($value)
    {
        $this->vote_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStartTime($value)
    {
        $this->start_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEndTime($value)
    {
        $this->end_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCanMulti($value)
    {
        $this->can_multi = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setVoteCount($value)
    {
        $this->vote_count = $value;
        return $this;
    }
}
