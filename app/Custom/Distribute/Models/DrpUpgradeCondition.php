<?php

namespace App\Custom\Distribute\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DrpUpgradeCondition
 */
class DrpUpgradeCondition extends Model
{
    protected $table = 'drp_upgrade_condition';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'dsc'
    ];

    protected $guarded = [];
}
