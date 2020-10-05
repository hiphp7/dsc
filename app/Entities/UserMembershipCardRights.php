<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserMembershipCardRights
 */
class UserMembershipCardRights extends Model
{
    protected $table = 'user_membership_card_rights';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'rights_id',
        'rights_configure',
        'membership_card_id',
        'add_time',
        'update_time'
    ];

    protected $guarded = [];


}
