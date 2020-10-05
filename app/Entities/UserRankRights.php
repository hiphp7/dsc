<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserRank
 */
class UserRankRights extends Model
{
    protected $table = 'user_rank_rights';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'rights_id',
        'rights_configure',
        'user_rank_id',
        'add_time',
        'update_time'
    ];

    protected $guarded = [];


}
