<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Stats
 */
class Stats extends Model
{
    protected $table = 'stats';

    public $timestamps = false;

    protected $fillable = [
        'access_time',
        'ip_address',
        'visit_times',
        'browser',
        'system',
        'language',
        'area',
        'referer_domain',
        'referer_path',
        'access_url'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getAccessTime()
    {
        return $this->access_time;
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
    public function getVisitTimes()
    {
        return $this->visit_times;
    }

    /**
     * @return mixed
     */
    public function getBrowser()
    {
        return $this->browser;
    }

    /**
     * @return mixed
     */
    public function getSystem()
    {
        return $this->system;
    }

    /**
     * @return mixed
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @return mixed
     */
    public function getArea()
    {
        return $this->area;
    }

    /**
     * @return mixed
     */
    public function getRefererDomain()
    {
        return $this->referer_domain;
    }

    /**
     * @return mixed
     */
    public function getRefererPath()
    {
        return $this->referer_path;
    }

    /**
     * @return mixed
     */
    public function getAccessUrl()
    {
        return $this->access_url;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAccessTime($value)
    {
        $this->access_time = $value;
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
    public function setVisitTimes($value)
    {
        $this->visit_times = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBrowser($value)
    {
        $this->browser = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSystem($value)
    {
        $this->system = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLanguage($value)
    {
        $this->language = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setArea($value)
    {
        $this->area = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRefererDomain($value)
    {
        $this->referer_domain = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRefererPath($value)
    {
        $this->referer_path = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAccessUrl($value)
    {
        $this->access_url = $value;
        return $this;
    }
}
