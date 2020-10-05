<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserMembershipCard
 */
class UserMembershipCard extends Model
{
    protected $table = 'user_membership_card';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'type',
        'description',
        'background_img',
        'background_color',
        'receive_value',
        'expiry_type',
        'expiry_date',
        'enable',
        'add_time',
        'update_time',
        'sort',
        'user_rank_id'
    ];

    protected $guarded = [];


}
