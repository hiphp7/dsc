<?php

namespace App\Models;

use App\Entities\TouchAd as Base;

/**
 * Class TouchAd
 */
class TouchAd extends Base
{

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getTouchAdPosition()
    {
        return $this->hasOne('App\Models\TouchAdPosition', 'position_id', 'position_id');
    }
}
