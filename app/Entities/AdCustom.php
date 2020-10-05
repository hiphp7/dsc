<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AdCustom
 */
class AdCustom extends Model
{
    protected $table = 'ad_custom';

    protected $primaryKey = 'ad_id';

    public $timestamps = false;

    protected $fillable = [
        'ad_type',
        'ad_name',
        'add_time',
        'content',
        'url',
        'ad_status'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getAdType()
    {
        return $this->ad_type;
    }

    /**
     * @return mixed
     */
    public function getAdName()
    {
        return $this->ad_name;
    }

    /**
     * @return mixed
     */
    public function getAddTime()
    {
        return $this->add_time;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return mixed
     */
    public function getAdStatus()
    {
        return $this->ad_status;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAdType($value)
    {
        $this->ad_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAdName($value)
    {
        $this->ad_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAddTime($value)
    {
        $this->add_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setContent($value)
    {
        $this->content = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUrl($value)
    {
        $this->url = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAdStatus($value)
    {
        $this->ad_status = $value;
        return $this;
    }
}
