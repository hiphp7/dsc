<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MerchantsDtFile
 */
class MerchantsDtFile extends Model
{
    protected $table = 'merchants_dt_file';

    protected $primaryKey = 'dtf_id';

    public $timestamps = false;

    protected $fillable = [
        'cat_id',
        'dt_id',
        'user_id',
        'permanent_file',
        'permanent_date',
        'cate_title_permanent'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getCatId()
    {
        return $this->cat_id;
    }

    /**
     * @return mixed
     */
    public function getDtId()
    {
        return $this->dt_id;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @return mixed
     */
    public function getPermanentFile()
    {
        return $this->permanent_file;
    }

    /**
     * @return mixed
     */
    public function getPermanentDate()
    {
        return $this->permanent_date;
    }

    /**
     * @return mixed
     */
    public function getCateTitlePermanent()
    {
        return $this->cate_title_permanent;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCatId($value)
    {
        $this->cat_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDtId($value)
    {
        $this->dt_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserId($value)
    {
        $this->user_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPermanentFile($value)
    {
        $this->permanent_file = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPermanentDate($value)
    {
        $this->permanent_date = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCateTitlePermanent($value)
    {
        $this->cate_title_permanent = $value;
        return $this;
    }
}
