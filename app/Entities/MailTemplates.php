<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MailTemplates
 */
class MailTemplates extends Model
{
    protected $table = 'mail_templates';

    protected $primaryKey = 'template_id';

    public $timestamps = false;

    protected $fillable = [
        'template_code',
        'is_html',
        'template_subject',
        'template_content',
        'last_modify',
        'last_send',
        'type'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getTemplateCode()
    {
        return $this->template_code;
    }

    /**
     * @return mixed
     */
    public function getIsHtml()
    {
        return $this->is_html;
    }

    /**
     * @return mixed
     */
    public function getTemplateSubject()
    {
        return $this->template_subject;
    }

    /**
     * @return mixed
     */
    public function getTemplateContent()
    {
        return $this->template_content;
    }

    /**
     * @return mixed
     */
    public function getLastModify()
    {
        return $this->last_modify;
    }

    /**
     * @return mixed
     */
    public function getLastSend()
    {
        return $this->last_send;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTemplateCode($value)
    {
        $this->template_code = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsHtml($value)
    {
        $this->is_html = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTemplateSubject($value)
    {
        $this->template_subject = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTemplateContent($value)
    {
        $this->template_content = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLastModify($value)
    {
        $this->last_modify = $value;
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

    /**
     * @param $value
     * @return $this
     */
    public function setType($value)
    {
        $this->type = $value;
        return $this;
    }
}
