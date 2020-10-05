<?php

use App\Libraries\Scws;

/**
 * 中文分词处理方法
 *
 * @param stirng $string 要处理的字符串
 * @param boolers $sort =false 根据value进行倒序
 * @param Numbers $top =0 返回指定数量，默认返回全部
 * @return void
 */
function scws($text, $top = 5, $return_array = false, $sep = ',')
{
    $cws = app(Scws::class);
    $cws->set_charset('utf-8');
    $cws->set_dict(resource_path('scws/dict.utf8.xdb'));
    $cws->set_rule(resource_path('scws/rules.utf8.ini'));
    $cws->set_ignore(true);
    $cws->send_text($text);
    $ret = $cws->get_tops($top, 'r,v,p');
    $result = null;
    foreach ($ret as $value) {
        if (false === $return_array) {
            $result .= $sep . $value['word'];
        } else {
            $result[] = $value['word'];
        }
    }
    return false === $return_array ? substr($result, 1) : $result;
}
