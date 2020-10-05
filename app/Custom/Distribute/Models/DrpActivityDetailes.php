<?php

namespace App\Custom\Distribute\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DrpActivityDetailes
 */
class DrpActivityDetailes extends Model
{
    protected $table = 'drp_activity_detailes';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'act_name',
        'act_dsc',
        'act_type',
        'start_time',
        'end_time',
        'text_info',
        'is_finish',
        'raward_money',
        'raward_type',
        'act_type_share',
        'act_type_place',
        'goods_id',
        'ru_id',
        'add_time'
    ];

    protected $guarded = [];

    /**
     * 关联活动奖励记录表
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function DrpRewardLog()
    {
        return $this->belongsTo('App\Custom\Distribute\Models\DrpRewardLog', 'activity_id', 'id');
    }

    /**
     * 关联商品表
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function Goods()
    {
        return $this->belongsTo('App\Models\Goods', 'goods_id', 'goods_id');
    }

    /**
     * 关联商户表
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function AdminUser()
    {
        return $this->belongsTo('App\Models\AdminUser', 'ru_id', 'ru_id');
    }

}
