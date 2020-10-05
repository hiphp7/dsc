<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderInfoMembershipCard
 */
class OrderInfoMembershipCard extends Model
{
    protected $table = 'order_info_membership_card';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'user_id',
        'order_amount',
        'membership_card_id',
        'membership_card_buy_money',
        'membership_card_discount_price',
        'add_time',
    ];

    protected $guarded = [];


}
