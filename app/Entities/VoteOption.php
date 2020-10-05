<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class VoteOption
 */
class VoteOption extends Model
{
    protected $table = 'vote_option';

    protected $primaryKey = 'option_id';

    public $timestamps = false;

    protected $fillable = [
        'vote_id',
        'option_name',
        'option_count',
        'option_order'
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
    public function getOptionName()
    {
        return $this->option_name;
    }

    /**
     * @return mixed
     */
    public function getOptionCount()
    {
        return $this->option_count;
    }

    /**
     * @return mixed
     */
    public function getOptionOrder()
    {
        return $this->option_order;
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
    public function setOptionName($value)
    {
        $this->option_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOptionCount($value)
    {
        $this->option_count = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOptionOrder($value)
    {
        $this->option_order = $value;
        return $this;
    }
}
