<?php

namespace App\Modules\Admin\Controllers;

// 当chk='user' 时,,判断用户是否合法; 当 chk='int'时..判断为数字;
function mc_explode_str($str, $exp = ',', $chk = '')
{
    $str_arr = explode($exp, $str);
    if ($chk == 'user') {
        $user_arr = [];
        foreach ($str_arr as $key => $value) {
            if (strlen($value) >= 3) {
                $user_arr[] = $value;
            }
        }
        $str_arr = $user_arr;
    } elseif ($chk == 'int') {
        $id_arr = [];
        foreach ($str_arr as $key => $value) {
            if (is_numeric($value)) {
                $id_arr[] = $value;
            }
        }
        $str_arr = $id_arr;
    }
    return $str_arr;
}

function mc_read_txt($file)
{
    if (file_exists($file)) {
        $content = file_get_contents($file);//读文件
        $encode = mb_check_encoding($content, 'UTF-8');
        if ($encode === false) {
            $content = autoCharset($content);
        }
        if (empty($content)) {
            return false;
        } else {
            return $content;
        }
    }

    return false;
}

function uploadfile($upfile, $upload_path, $redirect, $f_size = "102400", $f_type = "txt,jpg|jpeg|gif|png")
{
    if (!file_exists($upload_path)) {
        mkdir($upload_path, 0777, true);
    }
    $file_name = $_FILES[$upfile]['name'];

    if (empty($file_name)) {
        return false;
    }

    $file_type = $_FILES[$upfile]['type'];
    $file_size = $_FILES[$upfile]['size'];
    $file_tmp = $_FILES[$upfile]['tmp_name'];
    $upload_dir = $upload_path;

    $ext = explode(".", $file_name);
    $sub = count($ext) - 1;
    $ext_type = strtolower($ext[$sub]);//转换成小写
    $up_type = explode("|", $f_type);
    if (!in_array($ext_type, $up_type)) {
        die("
		<script language=javascript>
			 alert('您上传的文件类型不符合要求！请重新上传！\\n\\n上传类型只能是" . $f_type . "。');
			 location.href='" . $redirect . "';
		</script>");
    }

    $file_names = time() . rand(1, 9999) . "." . $ext[$sub];
    $upload_file_name = $upload_dir . $file_names;
    $chk_file = move_uploaded_file($file_tmp, $upload_file_name);
    if ($chk_file) { //判断文件上传是否成功

        chmod($upload_file_name, 0777);//设置上传文件的权限
        unset($ext[$sub]);
        $file_name = implode(".", $ext);//先去除扩展名,后获取文件名
        return [$file_names, $file_size, $ext_type, $file_name];
    } else {
        return false;
    }
}

//ecmoban模板堂 --zhuo start
function get_str_trim($str, $type = ',')
{
    $str = explode($type, $str);
    $str2 = '';

    for ($i = 0; $i < count($str); $i++) {
        $str2 .= trim($str[$i]) . $type;
    }

    return substr($str2, 0, -1);
}

//回车替换
function get_preg_replace($str, $type = '|')
{
    $str = preg_replace("/\r\n/", ",", $str); //替换空格回车换行符 为 英文逗号
    $str = get_str_trim($str);
    $str = get_str_trim($str, $type);

    return $str;
}

//编码
function str_iconv($str)
{
    return iconv("gb2312", "UTF-8", $str);
}

//获取数据方式 1、单条数据 2、一维数组（多条数据）
function get_infoCnt($table = '', $slt = '', $where = '', $type = 1)
{
    $sql = "select " . $slt . " from " . $GLOBALS['dsc']->table($table) . " where " . $where;

    if ($type == 1) {
        return $GLOBALS['db']->getOne($sql);
    } else {
        return $GLOBALS['db']->getRow($sql);
    }
}

//获取随机数组值
function get_array_rand_return($arr)
{
    if (count($arr) < 1) {
        $arrNum = 1;
    } else {
        $arrNum = count($arr);
    }

    $rand_num = rand(1, $arrNum);

    $rand_key = array_rand($arr, $rand_num);
    $key = count($rand_key);

    if ($key == 1) {
        $newArr[] = $arr[rand(0, count($arr) - 1)];
    } else {
        $newArr = [];
        for ($i = 0; $i < $key; $i++) {
            $newArr[$i] = $arr[$rand_key[$i]];
        }
    }

    return $newArr;
}
//ecmoban模板堂 --zhuo end
