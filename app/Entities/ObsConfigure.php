<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ObsConfigure
 */
class ObsConfigure extends Model
{
    protected $table = 'obs_configure';

    public $timestamps = false;

    protected $fillable = [
        'bucket',
        'keyid',
        'keysecret',
        'is_cname',
        'endpoint',
        'regional',
        'port',
        'is_use'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * @return mixed
     */
    public function getKeyid()
    {
        return $this->keyid;
    }

    /**
     * @return mixed
     */
    public function getKeysecret()
    {
        return $this->keysecret;
    }

    /**
     * @return mixed
     */
    public function getIsCname()
    {
        return $this->is_cname;
    }

    /**
     * @return mixed
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * @return mixed
     */
    public function getRegional()
    {
        return $this->regional;
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return mixed
     */
    public function getIsUse()
    {
        return $this->is_use;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBucket($value)
    {
        $this->bucket = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setKeyid($value)
    {
        $this->keyid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setKeysecret($value)
    {
        $this->keysecret = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsCname($value)
    {
        $this->is_cname = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEndpoint($value)
    {
        $this->endpoint = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRegional($value)
    {
        $this->regional = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPort($value)
    {
        $this->port = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsUse($value)
    {
        $this->is_use = $value;
        return $this;
    }
}
