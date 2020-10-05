<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class EmailSendlist
 */
class EmailSendlist extends Model
{
    protected $table = 'email_sendlist';

    public $timestamps = false;

    protected $fillable = [
        'email',
        'template_id',
        'email_content',
        'error',
        'pri',
        'last_send'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

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
    public function getEmailContent()
    {
        return $this->email_content;
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return mixed
     */
    public function getPri()
    {
        return $this->pri;
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
    public function setEmail($value)
    {
        $this->email = $value;
        return $this;
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
    public function setEmailContent($value)
    {
        $this->email_content = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setError($value)
    {
        $this->error = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPri($value)
    {
        $this->pri = $value;
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
