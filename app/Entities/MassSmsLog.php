<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MassSmsLog
 */
class MassSmsLog extends Model
{
    protected $table = 'mass_sms_log';

    public $timestamps = false;

    protected $fillable = [
        'template_id',
        'user_id',
        'send_status',
        'last_send'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getTemplateId()
    {
        return $this->template_id;
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
    public function getSendStatus()
    {
        return $this->send_status;
    }

    /**
     * @return mixed
     */
    public function getLastSend()
    {
        return $this->last_send;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTemplateId($value)
    {
        $this->template_id = $value;
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
    public function setSendStatus($value)
    {
        $this->send_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLastSend($value)
    {
        $this->last_send = $value;
        return $this;
    }
}
