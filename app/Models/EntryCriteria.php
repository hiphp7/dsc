<?php

namespace App\Models;

use App\Entities\EntryCriteria as Base;

/**
 * Class EntryCriteria
 */
class EntryCriteria extends Base
{
    /**
     * 查询子菜单数量
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getEntryCriteriaCount()
    {
        return $this->hasOne('App\Models\EntryCriteria', 'parent_id', 'id');
    }

    /**
     * 查询子菜单列表
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function getEntryCriteriaChildList()
    {
        return $this->hasMany('App\Models\EntryCriteria', 'parent_id', 'id');
    }
}
