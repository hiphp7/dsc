<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class FriendLink
 */
class FriendLink extends Model
{
    protected $table = 'friend_link';

    protected $primaryKey = 'link_id';

    public $timestamps = false;

    protected $fillable = [
        'link_name',
        'link_url',
        'link_logo',
        'show_order'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getLinkName()
    {
        return $this->link_name;
    }

    /**
     * @return mixed
     */
    public function getLinkUrl()
    {
        return $this->link_url;
    }

    /**
     * @return mixed
     */
    public function getLinkLogo()
    {
        return $this->link_logo;
    }

    /**
     * @return mixed
     */
    public function getShowOrder()
    {
        return $this->show_order;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLinkName($value)
    {
        $this->link_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLinkUrl($value)
    {
        $this->link_url = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLinkLogo($value)
    {
        $this->link_logo = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShowOrder($value)
    {
        $this->show_order = $value;
        return $this;
    }
}
