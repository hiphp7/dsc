<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserRank
 */
class UserRank extends Model
{
    protected $table = 'user_rank';

    protected $primaryKey = 'rank_id';

    public $timestamps = false;

    protected $fillable = [
        'rank_name',
        'min_points',
        'max_points',
        'discount',
        'show_price',
        'special_rank'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getRankName()
    {
        return $this->rank_name;
    }

    /**
     * @return mixed
     */
    public function getMinPoints()
    {
        return $this->min_points;
    }

    /**
     * @return mixed
     */
    public function getMaxPoints()
    {
        return $this->max_points;
    }

    /**
     * @return mixed
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * @return mixed
     */
    public function getShowPrice()
    {
        return $this->show_price;
    }

    /**
     * @return mixed
     */
    public function getSpecialRank()
    {
        return $this->special_rank;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRankName($value)
    {
        $this->rank_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMinPoints($value)
    {
        $this->min_points = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMaxPoints($value)
    {
        $this->max_points = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDiscount($value)
    {
        $this->discount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShowPrice($value)
    {
        $this->show_price = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSpecialRank($value)
    {
        $this->special_rank = $value;
        return $this;
    }
}
