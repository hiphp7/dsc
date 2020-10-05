<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Role
 */
class Role extends Model
{
    protected $table = 'role';

    protected $primaryKey = 'role_id';

    public $timestamps = false;

    protected $fillable = [
        'role_name',
        'action_list',
        'role_describe'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getRoleName()
    {
        return $this->role_name;
    }

    /**
     * @return mixed
     */
    public function getActionList()
    {
        return $this->action_list;
    }

    /**
     * @return mixed
     */
    public function getRoleDescribe()
    {
        return $this->role_describe;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRoleName($value)
    {
        $this->role_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setActionList($value)
    {
        $this->action_list = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRoleDescribe($value)
    {
        $this->role_describe = $value;
        return $this;
    }
}
