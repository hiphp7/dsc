<?php

namespace App\Custom\Distribute\Models;

use Illuminate\Database\Eloquent\Model;


class DrpAccountLog extends Model
{
    protected $table = 'drp_account_log';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'user_id',
        'admin_user',
        'amount',
        'pay_points',
        'deposit_fee',
        'add_time',
        'paid_time',
        'admin_note',
        'user_note',
        'account_type',
        'payment',
        'pay_id',
        'is_paid',
        'receive_type',
        'membership_card_id',
        'log_id',
        'drp_is_separate',
        'parent_id'
    ];

    protected $guarded = [];


    /**
     * 关联分销商
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getDrpShop()
    {
        return $this->hasOne('App\Models\DrpShop', 'user_id', 'user_id');
    }

    /**
     * 关联会员
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getUsers()
    {
        return $this->hasOne('App\Models\Users', 'user_id', 'user_id');
    }

    /**
     * 关联付费分成记录(单条)
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getDrpLog()
    {
        return $this->hasOne('App\Models\DrpLog', 'drp_account_log_id', 'id');
    }

    /**
     * 关联付费分成记录(多条)
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function getDrpLogs()
    {
        return $this->hasMany('App\Models\DrpLog', 'drp_account_log_id', 'id');
    }

    /**
     * 关联会员权益卡
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function userMembershipCard()
    {
        return $this->hasOne('App\Models\UserMembershipCard', 'id', 'membership_card_id');
    }

}
