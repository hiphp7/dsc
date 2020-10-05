<?php

namespace App\Models;

use App\Entities\WechatTemplateLog as Base;

/**
 * Class WechatTemplateLog
 */
class WechatTemplateLog extends Base
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function wechatTemplate()
    {
        return $this->hasOne('App\Models\WechatTemplate', 'open_id', 'unionid');
    }
}
