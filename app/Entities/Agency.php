<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Agency
 */
class Agency extends Model
{
    protected $table = 'agency';

    protected $primaryKey = 'agency_id';

    public $timestamps = false;

    protected $fillable = [
        'agency_name',
        'agency_desc'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getAgencyName()
    {
        return $this->agency_name;
    }

    /**
     * @return mixed
     */
    public function getAgencyDesc()
    {
        return $this->agency_desc;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAgencyName($value)
    {
        $this->agency_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAgencyDesc($value)
    {
        $this->agency_desc = $value;
        return $this;
    }
}
