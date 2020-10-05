<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ComplaintTalk
 */
class ComplaintTalk extends Model
{
    protected $table = 'complaint_talk';

    protected $primaryKey = 'talk_id';

    public $timestamps = false;

    protected $fillable = [
        'complaint_id',
        'talk_member_id',
        'talk_member_name',
        'talk_member_type',
        'talk_content',
        'talk_state',
        'admin_id',
        'talk_time',
        'view_state'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getComplaintId()
    {
        return $this->complaint_id;
    }

    /**
     * @return mixed
     */
    public function getTalkMemberId()
    {
        return $this->talk_member_id;
    }

    /**
     * @return mixed
     */
    public function getTalkMemberName()
    {
        return $this->talk_member_name;
    }

    /**
     * @return mixed
     */
    public function getTalkMemberType()
    {
        return $this->talk_member_type;
    }

    /**
     * @return mixed
     */
    public function getTalkContent()
    {
        return $this->talk_content;
    }

    /**
     * @return mixed
     */
    public function getTalkState()
    {
        return $this->talk_state;
    }

    /**
     * @return mixed
     */
    public function getAdminId()
    {
        return $this->admin_id;
    }

    /**
     * @return mixed
     */
    public function getTalkTime()
    {
        return $this->talk_time;
    }

    /**
     * @return mixed
     */
    public function getViewState()
    {
        return $this->view_state;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setComplaintId($value)
    {
        $this->complaint_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTalkMemberId($value)
    {
        $this->talk_member_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTalkMemberName($value)
    {
        $this->talk_member_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTalkMemberType($value)
    {
        $this->talk_member_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTalkContent($value)
    {
        $this->talk_content = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTalkState($value)
    {
        $this->talk_state = $value;
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

    /**
     * @param $value
     * @return $this
     */
    public function setTalkTime($value)
    {
        $this->talk_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setViewState($value)
    {
        $this->view_state = $value;
        return $this;
    }
}
