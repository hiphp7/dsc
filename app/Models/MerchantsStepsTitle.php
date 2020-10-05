<?php

namespace App\Models;

use App\Entities\MerchantsStepsTitle as Base;

/**
 * Class MerchantsStepsTitle
 */
class MerchantsStepsTitle extends Base
{
    public function getMerchantsStepsProcess()
    {
        return $this->hasOne('App\Models\MerchantsStepsProcess', 'id', 'fields_steps');
    }

}
