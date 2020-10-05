<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class EmailList
 */
class EmailList extends Model
{
    protected $table = 'email_list';

    public $timestamps = false;

    protected $fillable = [
        'email',
        'stat',
        'hash'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return mixed
     */
    public function getStat()
    {
        return $this->stat;
    }

    /**
     * @return mixed
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEmail($value)
    {
        $this->email = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStat($value)
    {
        $this->stat = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setHash($value)
    {
        $this->hash = $value;
        return $this;
    }
}
