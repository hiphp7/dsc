<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WholesalePurchase
 */
class WholesalePurchase extends Model
{
    protected $table = 'wholesale_purchase';

    protected $primaryKey = 'purchase_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'status',
        'subject',
        'type',
        'contact_name',
        'contact_gender',
        'contact_phone',
        'contact_email',
        'supplier_company_name',
        'supplier_contact_phone',
        'add_time',
        'end_time',
        'need_invoice',
        'invoice_tax_rate',
        'consignee_region',
        'consignee_address',
        'description',
        'review_status',
        'review_content'
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
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getContactName()
    {
        return $this->contact_name;
    }

    /**
     * @return mixed
     */
    public function getContactGender()
    {
        return $this->contact_gender;
    }

    /**
     * @return mixed
     */
    public function getContactPhone()
    {
        return $this->contact_phone;
    }

    /**
     * @return mixed
     */
    public function getContactEmail()
    {
        return $this->contact_email;
    }

    /**
     * @return mixed
     */
    public function getSupplierCompanyName()
    {
        return $this->supplier_company_name;
    }

    /**
     * @return mixed
     */
    public function getSupplierContactPhone()
    {
        return $this->supplier_contact_phone;
    }

    /**
     * @return mixed
     */
    public function getAddTime()
    {
        return $this->add_time;
    }

    /**
     * @return mixed
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * @return mixed
     */
    public function getNeedInvoice()
    {
        return $this->need_invoice;
    }

    /**
     * @return mixed
     */
    public function getInvoiceTaxRate()
    {
        return $this->invoice_tax_rate;
    }

    /**
     * @return mixed
     */
    public function getConsigneeRegion()
    {
        return $this->consignee_region;
    }

    /**
     * @return mixed
     */
    public function getConsigneeAddress()
    {
        return $this->consignee_address;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function getReviewStatus()
    {
        return $this->review_status;
    }

    /**
     * @return mixed
     */
    public function getReviewContent()
    {
        return $this->review_content;
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
    public function setStatus($value)
    {
        $this->status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSubject($value)
    {
        $this->subject = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setType($value)
    {
        $this->type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setContactName($value)
    {
        $this->contact_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setContactGender($value)
    {
        $this->contact_gender = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setContactPhone($value)
    {
        $this->contact_phone = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setContactEmail($value)
    {
        $this->contact_email = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSupplierCompanyName($value)
    {
        $this->supplier_company_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSupplierContactPhone($value)
    {
        $this->supplier_contact_phone = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAddTime($value)
    {
        $this->add_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEndTime($value)
    {
        $this->end_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setNeedInvoice($value)
    {
        $this->need_invoice = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setInvoiceTaxRate($value)
    {
        $this->invoice_tax_rate = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setConsigneeRegion($value)
    {
        $this->consignee_region = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setConsigneeAddress($value)
    {
        $this->consignee_address = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDescription($value)
    {
        $this->description = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReviewStatus($value)
    {
        $this->review_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReviewContent($value)
    {
        $this->review_content = $value;
        return $this;
    }
}
