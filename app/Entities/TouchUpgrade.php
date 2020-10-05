<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TouchUpgrade
 */
class TouchUpgrade extends Model
{
    protected $table = 'touch_upgrade';

    public $timestamps = false;

    protected $fillable = [
        'upgrade',
        'time'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getUpgrade()
    {
        return $this->upgrade;
    }

    /**
     * @return mixed
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUpgrade($value)
    {
        $this->upgrade = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTime($value)
    {
        $this->time = $value;
        return $this;
    }
}
