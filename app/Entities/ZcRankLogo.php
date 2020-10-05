<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ZcRankLogo
 */
class ZcRankLogo extends Model
{
    protected $table = 'zc_rank_logo';

    public $timestamps = false;

    protected $fillable = [
        'logo_name',
        'img',
        'logo_intro'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getLogoName()
    {
        return $this->logo_name;
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
    public function getLogoIntro()
    {
        return $this->logo_intro;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLogoName($value)
    {
        $this->logo_name = $value;
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
    public function setLogoIntro($value)
    {
        $this->logo_intro = $value;
        return $this;
    }
}
