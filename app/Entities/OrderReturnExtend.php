<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderReturnExtend
 */
class OrderReturnExtend extends Model
{
    protected $table = 'order_return_extend';

    public $timestamps = false;

    protected $fillable = [
        'ret_id',
        'return_number',
        'aftersn'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getRetId()
    {
        return $this->ret_id;
    }

    /**
     * @return mixed
     */
    public function getReturnNumber()
    {
        return $this->return_number;
    }

    /**
     * @return mixed
     */
    public function getAftersn()
    {
        return $this->aftersn;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRetId($value)
    {
        $this->ret_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReturnNumber($value)
    {
        $this->return_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAftersn($value)
    {
        $this->aftersn = $value;
        return $this;
    }
}
