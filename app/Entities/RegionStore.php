<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class RegionStore
 */
class RegionStore extends Model
{
    protected $table = 'region_store';

    protected $primaryKey = 'rs_id';

    public $timestamps = false;

    protected $fillable = [
        'rs_name'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getRsName()
    {
        return $this->rs_name;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRsName($value)
    {
        $this->rs_name = $value;
        return $this;
    }
}
