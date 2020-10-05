<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MerchantsDocumenttitle
 */
class MerchantsDocumenttitle extends Model
{
    protected $table = 'merchants_documenttitle';

    protected $primaryKey = 'dt_id';

    public $timestamps = false;

    protected $fillable = [
        'dt_title',
        'cat_id'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getDtTitle()
    {
        return $this->dt_title;
    }

    /**
     * @return mixed
     */
    public function getCatId()
    {
        return $this->cat_id;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDtTitle($value)
    {
        $this->dt_title = $value;
        return $this;
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
}
