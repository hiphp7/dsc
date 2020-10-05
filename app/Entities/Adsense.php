<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Adsense
 */
class Adsense extends Model
{
    protected $table = 'adsense';

    public $timestamps = false;

    protected $fillable = [
        'from_ad',
        'referer',
        'clicks'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getFromAd()
    {
        return $this->from_ad;
    }

    /**
     * @return mixed
     */
    public function getReferer()
    {
        return $this->referer;
    }

    /**
     * @return mixed
     */
    public function getClicks()
    {
        return $this->clicks;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFromAd($value)
    {
        $this->from_ad = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReferer($value)
    {
        $this->referer = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setClicks($value)
    {
        $this->clicks = $value;
        return $this;
    }
}
