<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ComplainTitle
 */
class ComplainTitle extends Model
{
    protected $table = 'complain_title';

    protected $primaryKey = 'title_id';

    public $timestamps = false;

    protected $fillable = [
        'title_name',
        'title_desc',
        'is_show'
    ];

    protected $guarded = [];


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
    public function getTitleDesc()
    {
        return $this->title_desc;
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
    public function setTitleName($value)
    {
        $this->title_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTitleDesc($value)
    {
        $this->title_desc = $value;
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
