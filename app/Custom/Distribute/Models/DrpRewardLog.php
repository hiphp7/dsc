<?php

namespace App\Custom\Distribute\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DrpRewardLog
 */
class DrpRewardLog extends Model
{

    protected $table = 'drp_reward_log';

    protected $primaryKey = 'reward_id';

    public $timestamps = false;

    protected $fillable = [
        'reward_id',
        'user_id',
        'activity_id',
        'award_money',
        'award_type',
        'activity_type',
        'awaed_status',
        'participation_status',
        'completeness_share',
        'completeness_place',
        'add_time',
        'credit_id'
    ];

    protected $guarded = [];

    /**
     * 关联分销商
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function Users()
    {
        return $this->hasOne('App\Models\users', 'user_id', 'user_id');
    }

    /**
     * 关联活动记录表
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function ActivityDetailes()
    {
        return $this->hasOne('App\Custom\Distribute\Models\DrpActivityDetailes', 'id', 'activity_id');
    }

}
