<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MerchantsPrivilege
 */
class MerchantsPrivilege extends Model
{
    protected $table = 'merchants_privilege';

    public $timestamps = false;

    protected $fillable = [
        'action_list',
        'grade_id'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getActionList()
    {
        return $this->action_list;
    }

    /**
     * @return mixed
     */
    public function getGradeId()
    {
        return $this->grade_id;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setActionList($value)
    {
        $this->action_list = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGradeId($value)
    {
        $this->grade_id = $value;
        return $this;
    }
}
