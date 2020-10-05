<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Sms
 */
class Sms extends Model
{
    protected $table = 'sms';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'code',
        'description',
        'sms_configure',
        'enable',
        'default',
        'add_time',
        'update_time',
        'sort'
    ];

    protected $guarded = [];

}
