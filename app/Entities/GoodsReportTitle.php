<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GoodsReportTitle
 */
class GoodsReportTitle extends Model
{
    protected $table = 'goods_report_title';

    protected $primaryKey = 'title_id';

    public $timestamps = false;

    protected $fillable = [
        'type_id',
        'title_name',
        'is_show'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getTypeId()
    {
        return $this->type_id;
    }

    /**
     * @return mixed
     */
    public function getTitleName()
    {
        return $this->title_name;
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
    public function setTypeId($value)
    {
        $this->type_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTitleName($value)
    {
        $this->title_name = $value;
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
