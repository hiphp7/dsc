<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ReturnAction
 */
class ReturnAction extends Model
{
    protected $table = 'return_action';

    protected $primaryKey = 'action_id';

    public $timestamps = false;

    protected $fillable = [
        'ret_id',
        'action_user',
        'return_status',
        'refound_status',
        'action_place',
        'action_note',
        'log_time'
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
    public function getActionUser()
    {
        return $this->action_user;
    }

    /**
     * @return mixed
     */
    public function getReturnStatus()
    {
        return $this->return_status;
    }

    /**
     * @return mixed
     */
    public function getRefoundStatus()
    {
        return $this->refound_status;
    }

    /**
     * @return mixed
     */
    public function getActionPlace()
    {
        return $this->action_place;
    }

    /**
     * @return mixed
     */
    public function getActionNote()
    {
        return $this->action_note;
    }

    /**
     * @return mixed
     */
    public function getLogTime()
    {
        return $this->log_time;
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
    public function setActionUser($value)
    {
        $this->action_user = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReturnStatus($value)
    {
        $this->return_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRefoundStatus($value)
    {
        $this->refound_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setActionPlace($value)
    {
        $this->action_place = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setActionNote($value)
    {
        $this->action_note = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLogTime($value)
    {
        $this->log_time = $value;
        return $this;
    }
}
