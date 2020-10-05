<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CatRecommend
 */
class CatRecommend extends Model
{
    protected $table = 'cat_recommend';

    public $timestamps = false;

    protected $fillable = [
        'cat_id',
        'recommend_type'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getCatId()
    {
        return $this->cat_id;
    }

    /**
     * @return mixed
     */
    public function getRecommendType()
    {
        return $this->recommend_type;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCatId($value)
    {
        $this->cat_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRecommendType($value)
    {
        $this->recommend_type = $value;
        return $this;
    }
}
