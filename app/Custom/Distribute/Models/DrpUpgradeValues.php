<?php

namespace App\Custom\Distribute\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DrpUpgradeValues
 */
class DrpUpgradeValues extends Model
{
    protected $table = 'drp_upgrade_values';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'value',
        'condition_id',
        'credit_id',
        'award_num',
        'type'
    ];

    protected $guarded = [];
}
