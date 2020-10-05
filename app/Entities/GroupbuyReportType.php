<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TeamCategory
 */
class GroupbuyReportType extends Model
{
    protected $table = 'groupbuy_report_type';

    protected $primaryKey = 'type_id';

    public $timestamps = false;

    protected $fillable = [
        'type_name',
        'type_desc',
        'status'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getTypeName()
    {
        return $this->type_name;
    }

    /**
     * @return mixed
     */
    public function getTypeDesc()
    {
        return $this->type_desc;
    }


    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTypeName($value)
    {
        $this->type_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTypeDesc($value)
    {
        $this->type_desc = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStatus($value)
    {
        $this->status = $value;
        return $this;
    }
}
