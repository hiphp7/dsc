<?php

namespace App\Models;

use App\Entities\WechatReply as Base;

/**
 * Class WechatReply
 */
class WechatReply extends Base
{
    /**
     * 关联关键词规则表
     * @param rid
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function wechatRuleKeywords()
    {
        return $this->hasOne('App\Models\WechatRuleKeywords', 'rid', 'id');
    }

    /**
     * 关联关键词规则表 一对多
     * @param rid
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function wechatRuleKeywordsList()
    {
        return $this->hasMany('App\Models\WechatRuleKeywords', 'rid', 'id');
    }
}
