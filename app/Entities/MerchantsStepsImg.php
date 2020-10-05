<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MerchantsStepsImg
 */
class MerchantsStepsImg extends Model
{
    protected $table = 'merchants_steps_img';

    protected $primaryKey = 'gid';

    public $timestamps = false;

    protected $fillable = [
        'tid',
        'steps_img'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getTid()
    {
        return $this->tid;
    }

    /**
     * @return mixed
     */
    public function getStepsImg()
    {
        return $this->steps_img;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTid($value)
    {
        $this->tid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStepsImg($value)
    {
        $this->steps_img = $value;
        return $this;
    }
}
