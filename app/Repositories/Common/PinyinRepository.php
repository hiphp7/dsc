<?php

namespace App\Repositories\Common;

use App\Kernel\Repositories\Common\PinyinRepository as Base;

/**
 * Class PinyinRepository
 * @method convertMode($string, $split = ' ') 文字转拼音
 * @method ucwordsStrtolower($str = '') 首字母转大写
 * @method convertModeFirst($string, $split = '') 获取拼音首字母
 * @package App\Repositories\Common
 */
class PinyinRepository extends Base
{

}
