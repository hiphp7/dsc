<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GiftGardType
 */
class GiftGardType extends Model
{
    protected $table = 'gift_gard_type';

    protected $primaryKey = 'gift_id';

    public $timestamps = false;

    protected $fillable = [
        'ru_id',
        'gift_name',
        'gift_menory',
        'gift_min_menory',
        'gift_start_date',
        'gift_end_date',
        'gift_number',
        'review_status',
        'review_content'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getRuId()
    {
        return $this->ru_id;
    }

    /**
     * @return mixed
     */
    public function getGiftName()
    {
        return $this->gift_name;
    }

    /**
     * @return mixed
     */
    public function getGiftMenory()
    {
        return $this->gift_menory;
    }

    /**
     * @return mixed
     */
    public function getGiftMinMenory()
    {
        return $this->gift_min_menory;
    }

    /**
     * @return mixed
     */
    public function getGiftStartDate()
    {
        return $this->gift_start_date;
    }

    /**
     * @return mixed
     */
    public function getGiftEndDate()
    {
        return $this->gift_end_date;
    }

    /**
     * @return mixed
     */
    public function getGiftNumber()
    {
        return $this->gift_number;
    }

    /**
     * @return mixed
     */
    public function getReviewStatus()
    {
        return $this->review_status;
    }

    /**
     * @return mixed
     */
    public function getReviewContent()
    {
        return $this->review_content;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRuId($value)
    {
        $this->ru_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGiftName($value)
    {
        $this->gift_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGiftMenory($value)
    {
        $this->gift_menory = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGiftMinMenory($value)
    {
        $this->gift_min_menory = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGiftStartDate($value)
    {
        $this->gift_start_date = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGiftEndDate($value)
    {
        $this->gift_end_date = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGiftNumber($value)
    {
        $this->gift_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReviewStatus($value)
    {
        $this->review_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReviewContent($value)
    {
        $this->review_content = $value;
        return $this;
    }
}
