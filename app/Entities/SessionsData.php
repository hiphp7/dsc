<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SessionsData
 */
class SessionsData extends Model
{
    protected $table = 'sessions_data';

    protected $primaryKey = 'sesskey';

    public $timestamps = false;

    protected $fillable = [
        'expiry',
        'data'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getExpiry()
    {
        return $this->expiry;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setExpiry($value)
    {
        $this->expiry = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setData($value)
    {
        $this->data = $value;
        return $this;
    }
}
