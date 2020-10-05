<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WechatRuleKeywords
 */
class WechatRuleKeywords extends Model
{
    protected $table = 'wechat_rule_keywords';

    public $timestamps = false;

    protected $fillable = [
        'wechat_id',
        'rid',
        'rule_keywords'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getWechatId()
    {
        return $this->wechat_id;
    }

    /**
     * @return mixed
     */
    public function getRid()
    {
        return $this->rid;
    }

    /**
     * @return mixed
     */
    public function getRuleKeywords()
    {
        return $this->rule_keywords;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWechatId($value)
    {
        $this->wechat_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRid($value)
    {
        $this->rid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRuleKeywords($value)
    {
        $this->rule_keywords = $value;
        return $this;
    }
}
