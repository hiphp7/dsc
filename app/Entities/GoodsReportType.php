<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GoodsReportType
 */
class GoodsReportType extends Model
{
    protected $table = 'goods_report_type';

    protected $primaryKey = 'type_id';

    public $timestamps = false;

    protected $fillable = [
        'type_name',
        'type_desc',
        'is_show'
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
    public function getIsShow()
    {
        return $this->is_show;
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
    public function setIsShow($value)
    {
        $this->is_show = $value;
        return $this;
    }
}
