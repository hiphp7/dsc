<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class QrpayTag
 */
class QrpayTag extends Model
{
    protected $table = 'qrpay_tag';

    public $timestamps = false;

    protected $fillable = [
        'ru_id',
        'tag_name',
        'self_qrpay_num',
        'fixed_qrpay_num',
        'add_time'
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
    public function getTagName()
    {
        return $this->tag_name;
    }

    /**
     * @return mixed
     */
    public function getSelfQrpayNum()
    {
        return $this->self_qrpay_num;
    }

    /**
     * @return mixed
     */
    public function getFixedQrpayNum()
    {
        return $this->fixed_qrpay_num;
    }

    /**
     * @return mixed
     */
    public function getAddTime()
    {
        return $this->add_time;
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
    public function setTagName($value)
    {
        $this->tag_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSelfQrpayNum($value)
    {
        $this->self_qrpay_num = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFixedQrpayNum($value)
    {
        $this->fixed_qrpay_num = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAddTime($value)
    {
        $this->add_time = $value;
        return $this;
    }
}
