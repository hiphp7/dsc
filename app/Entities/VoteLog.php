<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class VoteLog
 */
class VoteLog extends Model
{
    protected $table = 'vote_log';

    protected $primaryKey = 'log_id';

    public $timestamps = false;

    protected $fillable = [
        'vote_id',
        'ip_address',
        'vote_time'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getVoteId()
    {
        return $this->vote_id;
    }

    /**
     * @return mixed
     */
    public function getIpAddress()
    {
        return $this->ip_address;
    }

    /**
     * @return mixed
     */
    public function getVoteTime()
    {
        return $this->vote_time;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setVoteId($value)
    {
        $this->vote_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIpAddress($value)
    {
        $this->ip_address = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setVoteTime($value)
    {
        $this->vote_time = $value;
        return $this;
    }
}
