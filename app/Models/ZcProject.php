<?php

namespace App\Models;

use App\Entities\ZcProject as Base;

/**
 * Class ZcProject
 */
class ZcProject extends Base
{
    public function getZcFocus()
    {
        return $this->hasOne('App\Models\ZcFocus', 'pid', 'id');
    }

    public function getZcGoods()
    {
        return $this->hasOne('App\Models\ZcGoods', 'pid', 'id');
    }

    public function getZcCategory()
    {
        return $this->hasOne('App\Models\ZcCategory', 'cat_id', 'cat_id');
    }
}
