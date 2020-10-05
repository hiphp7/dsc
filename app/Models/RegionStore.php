<?php

namespace App\Models;

use App\Entities\RegionStore as Base;

/**
 * Class RegionStore
 */
class RegionStore extends Base
{
    public function getRsRegion()
    {
        return $this->hasOne('App\Models\RsRegion', 'rs_id', 'rs_id');
    }
}
