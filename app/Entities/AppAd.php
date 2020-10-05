<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AppAd
 */
class AppAd extends Model
{
    protected $table = 'app_ad';

    protected $primaryKey = 'ad_id';

    public $timestamps = false;

    protected $fillable = [
        'position_id',
        'media_type',
        'ad_name',
        'ad_link',
        'ad_code',
        'click_count',
        'sort_order',
        'enabled'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getPositionId()
    {
        return $this->position_id;
    }

    /**
     * @return mixed
     */
    public function getMediaType()
    {
        return $this->media_type;
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
    public function getAdLink()
    {
        return $this->ad_link;
    }

    /**
     * @return mixed
     */
    public function getAdCode()
    {
        return $this->ad_code;
    }

    /**
     * @return mixed
     */
    public function getClickCount()
    {
        return $this->click_count;
    }

    /**
     * @return mixed
     */
    public function getSortOrder()
    {
        return $this->sort_order;
    }

    /**
     * @return mixed
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPositionId($value)
    {
        $this->position_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMediaType($value)
    {
        $this->media_type = $value;
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
    public function setAdLink($value)
    {
        $this->ad_link = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAdCode($value)
    {
        $this->ad_code = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setClickCount($value)
    {
        $this->click_count = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSortOrder($value)
    {
        $this->sort_order = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEnabled($value)
    {
        $this->enabled = $value;
        return $this;
    }
}
