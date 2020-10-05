<?php

namespace App\Models;

use App\Entities\QrpayLog as Base;

/**
 * Class QrpayLog
 */
class QrpayLog extends Base
{
    /**
     * 关联收款码列表
     * @param qrpay_id
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getQrpayManage()
    {
        return $this->hasOne('App\Models\QrpayManage', 'id', 'qrpay_id');
    }
}
