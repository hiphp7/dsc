<?php

namespace App\Models;

use App\Entities\VoteOption as Base;

/**
 * Class VoteOption
 */
class VoteOption extends Base
{
    /**
     * 关联调查
     *
     * @access  public
     * @return array
     */
    public function getVote()
    {
        return $this->hasOne('App\Models\Vote', 'vote_id', 'vote_id');
    }
}
