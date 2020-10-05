<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AppealImg
 */
class AppealImg extends Model
{
    protected $table = 'appeal_img';

    protected $primaryKey = 'img_id';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'complaint_id',
        'ru_id',
        'img_file'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * @return mixed
     */
    public function getComplaintId()
    {
        return $this->complaint_id;
    }

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
    public function getImgFile()
    {
        return $this->img_file;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrderId($value)
    {
        $this->order_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setComplaintId($value)
    {
        $this->complaint_id = $value;
        return $this;
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
    public function setImgFile($value)
    {
        $this->img_file = $value;
        return $this;
    }
}
