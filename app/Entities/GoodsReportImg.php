<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GoodsReportImg
 */
class GoodsReportImg extends Model
{
    protected $table = 'goods_report_img';

    protected $primaryKey = 'img_id';

    public $timestamps = false;

    protected $fillable = [
        'goods_id',
        'report_id',
        'user_id',
        'img_file'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getGoodsId()
    {
        return $this->goods_id;
    }

    /**
     * @return mixed
     */
    public function getReportId()
    {
        return $this->report_id;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->user_id;
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
    public function setGoodsId($value)
    {
        $this->goods_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReportId($value)
    {
        $this->report_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserId($value)
    {
        $this->user_id = $value;
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
