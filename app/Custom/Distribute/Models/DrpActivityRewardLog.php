<?php

namespace App\Custom\Distribute\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DrpActivityRewardLog
 */
class DrpActivityRewardLog extends Model
{
    protected $table = 'drp_activity_reward_log';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'activity_id',
        'user_id',
        'drp_user_id',
        'order_id',
        'is_effect',
        'add_time'
    ];

    protected $guarded = [];

    /**
     * 关联活动记录表
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function DrpActivityDetailes()
    {
        return $this->belongsTo('App\Custom\Distribute\Models\DrpActivityDetailes', 'id', 'activity_id');
    }

    /**
     * 关联订单表
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function OrderInfo()
    {
        return $this->belongsTo('App\Models\OrderInfo', 'order_id', 'order_id');
    }

    /**
     * 关联用户表 --user_id
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function Users()
    {
        return $this->belongsTo('App\Models\Users', 'user_id', 'user_id');
    }

    /**
     * 关联用户表 --drp_user_id
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function DrpUsers()
    {
        return $this->belongsTo('App\Models\Users', 'user_id', 'drp_user_id');
    }

}
