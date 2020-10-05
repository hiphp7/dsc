<?php

namespace App\Models;

use App\Entities\ReturnCause as Base;

/**
 * Class ReturnCause
 */
class ReturnCause extends Base
{
    /**
     * 关联父级
     *
     * @access  public
     * @param parent_id
     * @return  array
     */
    public function getReturnCauseParent()
    {
        return $this->hasOne('App\Models\ReturnCause', 'cause_id', 'parent_id');
    }

    /**
     * 关联子级
     *
     * @access  public
     * @param cause_id
     * @return  array
     */
    public function getReturnCauseChild()
    {
        return $this->hasOne('App\Models\ReturnCause', 'parent_id', 'cause_id');
    }
}
