<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ZcInitiator
 */
class ZcInitiator extends Model
{
    protected $table = 'zc_initiator';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'company',
        'img',
        'intro',
        'describe',
        'rank'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @return mixed
     */
    public function getImg()
    {
        return $this->img;
    }

    /**
     * @return mixed
     */
    public function getIntro()
    {
        return $this->intro;
    }

    /**
     * @return mixed
     */
    public function getDescribe()
    {
        return $this->describe;
    }

    /**
     * @return mixed
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setName($value)
    {
        $this->name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCompany($value)
    {
        $this->company = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setImg($value)
    {
        $this->img = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIntro($value)
    {
        $this->intro = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDescribe($value)
    {
        $this->describe = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRank($value)
    {
        $this->rank = $value;
        return $this;
    }
}
