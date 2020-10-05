<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AdminAction
 */
class AdminAction extends Model
{
    protected $table = 'admin_action';

    protected $primaryKey = 'action_id';

    public $timestamps = false;

    protected $fillable = [
        'parent_id',
        'action_code',
        'relevance',
        'seller_show'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getParentId()
    {
        return $this->parent_id;
    }

    /**
     * @return mixed
     */
    public function getActionCode()
    {
        return $this->action_code;
    }

    /**
     * @return mixed
     */
    public function getRelevance()
    {
        return $this->relevance;
    }

    /**
     * @return mixed
     */
    public function getSellerShow()
    {
        return $this->seller_show;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setParentId($value)
    {
        $this->parent_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setActionCode($value)
    {
        $this->action_code = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRelevance($value)
    {
        $this->relevance = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSellerShow($value)
    {
        $this->seller_show = $value;
        return $this;
    }
}
