<?php

/**
 * 生成密钥文件
 * @param string $file_path 目录
 * @param string $filename 文件名
 * @param string $content 内容
 */
function file_write($file_path, $filename, $content = '')
{
    if (!is_dir($file_path)) {
        @mkdir($file_path);
    }
    $fp = fopen($file_path . $filename, "w+"); // 读写，每次修改会覆盖原内容
    flock($fp, LOCK_EX);
    fwrite($fp, $content);
    flock($fp, LOCK_UN);
    fclose($fp);
}


/**
 * 生成商户订单交易号
 * @return  string
 */
function get_trade_no()
{
    $time = explode(" ", microtime());
    $time = $time[1] . ($time[0] * 1000);
    $time = explode(".", $time);
    $time = isset($time[1]) ? $time[1] : 0;
    $time = date('YmdHis') + $time;

    /* 选择一个随机的方案 */
    mt_srand((double)microtime() * 1000000);
    return $time . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

/**
 * 微信企业付款手续费
 * 每笔按付款金额收取手续费，按金额0.1%收取，最低1元，最高25元
 * @param int $money
 * @return int
 */
function deposit_fee($money = 0)
{
    $scale = 0.1;
    $fee = $money * $scale / 100;

    if ($fee > 0 && $fee < 1) {
        $fee = 1;
    }

    if ($fee > 25) {
        $fee = 25;
    }

    return round($fee, 2);
}