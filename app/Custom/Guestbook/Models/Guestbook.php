<?php

namespace App\Custom\Guestbook\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Guestbook
 * @package App\Custom\Guestbook\Models
 */
class Guestbook extends Model
{
    protected $table = 'guestbook';

    protected $primaryKey = 'id';

    public $timestamps = false;
}
