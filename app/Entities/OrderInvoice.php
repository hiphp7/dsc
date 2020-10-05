<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderInvoice
 */
class OrderInvoice extends Model
{
    protected $table = 'order_invoice';

    protected $primaryKey = 'invoice_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'inv_payee',
        'tax_id'
    ];

    protected $guarded = [];


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
    public function getInvPayee()
    {
        return $this->inv_payee;
    }

    /**
     * @return mixed
     */
    public function getTaxId()
    {
        return $this->tax_id;
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
    public function setInvPayee($value)
    {
        $this->inv_payee = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTaxId($value)
    {
        $this->tax_id = $value;
        return $this;
    }
}
