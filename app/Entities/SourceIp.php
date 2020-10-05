<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SourceIp
 */
class SourceIp extends Model
{
    protected $table = 'source_ip';

    protected $primaryKey = 'ipid';

    public $timestamps = false;

    protected $fillable = [
        'storeid',
        'ipdata',
        'iptime'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getStoreid()
    {
        return $this->storeid;
    }

    /**
     * @return mixed
     */
    public function getIpdata()
    {
        return $this->ipdata;
    }

    /**
     * @return mixed
     */
    public function getIptime()
    {
        return $this->iptime;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStoreid($value)
    {
        $this->storeid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIpdata($value)
    {
        $this->ipdata = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIptime($value)
    {
        $this->iptime = $value;
        return $this;
    }
}
