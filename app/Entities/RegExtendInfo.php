<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class RegExtendInfo
 */
class RegExtendInfo extends Model
{
    protected $table = 'reg_extend_info';

    protected $primaryKey = 'Id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'reg_field_id',
        'content'
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
    public function getRegFieldId()
    {
        return $this->reg_field_id;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
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
    public function setRegFieldId($value)
    {
        $this->reg_field_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setContent($value)
    {
        $this->content = $value;
        return $this;
    }
}
