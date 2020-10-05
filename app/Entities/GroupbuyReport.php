<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GroupbuyReport
 */
class GroupbuyReport extends Model
{
    protected $table = 'groupbuy_report';

    protected $primaryKey = 'report_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'order_id',
        'user_name',
        'report_object_id',
        'report_object_name',
        'title_id',
        'type_id',
        'inform_content',
        'add_time',
        'report_type',
        'report_img_files',
        'report_state',
        'handle_type',
        'handle_message',
        'handle_time',
        'admin_id'
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
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * @return mixed
     */
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * @return mixed
     */
    public function getReportObjectId()
    {
        return $this->report_object_id;
    }

    /**
     * @return mixed
     */
    public function getReportObjectName()
    {
        return $this->report_object_name;
    }


    /**
     * @return mixed
     */
    public function getTitleId()
    {
        return $this->title_id;
    }

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
    public function getInformContent()
    {
        return $this->inform_content;
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
    public function getReportType()
    {
        return $this->report_type;
    }

    /**
     * @return mixed
     */
    public function getReportImgFiles()
    {
        return $this->report_img_files;
    }

    /**
     * @return mixed
     */
    public function getReportState()
    {
        return $this->report_state;
    }

    /**
     * @return mixed
     */
    public function getHandleType()
    {
        return $this->handle_type;
    }

    /**
     * @return mixed
     */
    public function getHandleMessage()
    {
        return $this->handle_message;
    }

    /**
     * @return mixed
     */
    public function getHandleTime()
    {
        return $this->handle_time;
    }

    /**
     * @return mixed
     */
    public function getAdminId()
    {
        return $this->admin_id;
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
    public function setOrderId($value)
    {
        $this->order_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserName($value)
    {
        $this->user_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReportObjectId($value)
    {
        $this->report_object_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReportObjectName($value)
    {
        $this->report_object_name = $value;
        return $this;
    }


    /**
     * @param $value
     * @return $this
     */
    public function setTitleId($value)
    {
        $this->title_id = $value;
        return $this;
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
    public function setInformContent($value)
    {
        $this->inform_content = $value;
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
     * @return mixed
     */
    public function setReportType($value)
    {
        $this->report_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReportImgFiles($value)
    {
        $this->report_img_files = $value;
        return $this;
    }


    /**
     * @param $value
     * @return $this
     */
    public function setReportState($value)
    {
        $this->report_state = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setHandleType($value)
    {
        $this->handle_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setHandleMessage($value)
    {
        $this->handle_message = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setHandleTime($value)
    {
        $this->handle_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAdminId($value)
    {
        $this->admin_id = $value;
        return $this;
    }
}
