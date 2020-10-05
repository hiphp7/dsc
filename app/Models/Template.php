<?php

namespace App\Models;

use App\Entities\Template as Base;

/**
 * Class Template
 */
class Template extends Base
{
    /**
     * 关联分类查询
     *
     * @access  public
     * @param cat_id
     * @return  array
     */
    public function getFloorCategory()
    {
        return $this->hasOne("App\Models\Category", 'cat_id', 'id');
    }
}
