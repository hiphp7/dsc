<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SellerQrcode
 */
class SellerQrcode extends Model
{
    protected $table = 'seller_qrcode';

    protected $primaryKey = 'qrcode_id';

    public $timestamps = false;

    protected $fillable = [
        'ru_id',
        'qrcode_thumb'
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
    public function getQrcodeThumb()
    {
        return $this->qrcode_thumb;
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
    public function setQrcodeThumb($value)
    {
        $this->qrcode_thumb = $value;
        return $this;
    }
}
