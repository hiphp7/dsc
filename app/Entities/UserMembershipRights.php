<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserMembershipRights
 */
class UserMembershipRights extends Model
{
    protected $table = 'user_membership_rights';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'code',
        'description',
        'icon',
        'rights_configure',
        'trigger_point',
        'trigger_configure',
        'enable',
        'add_time',
        'update_time',
        'sort'
    ];

    protected $guarded = [];


}
