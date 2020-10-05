<?php

namespace App\Custom\Distribute\Models;

use App\Models\DrpTransferLog as Base;

/**
 * Class DrpTransferLog
 */
class DrpTransferLog extends Base
{

    /**
     * 关联分销商
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function drpShop()
    {
        return $this->hasOne('App\Models\DrpShop', 'user_id', 'user_id');
    }

}
