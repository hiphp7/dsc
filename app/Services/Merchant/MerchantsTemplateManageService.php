<?php

namespace App\Services\Merchant;

use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Commission\CommissionService;
use App\Services\Order\OrderService;


class MerchantsTemplateManageService
{
    protected $baseRepository;
    protected $merchantCommonService;
    protected $orderService;
    protected $commissionService;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        OrderService $orderService,
        MerchantCommonService $merchantCommonService,
        CommissionService $commissionService,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->orderService = $orderService;
        $this->commissionService = $commissionService;
        $this->dscRepository = $dscRepository;
    }


    /**
     * 读取模板风格列表
     *
     * @access  public
     * @param string $tpl_name 模版名称
     * @param int $flag 1，AJAX数据；2，Array
     * @return
     */
    public function readTplStyle($tpl_name, $flag = 1)
    {
        if (empty($tpl_name) && $flag == 1) {
            return 0;
        }

        /* 获得可用的模版 */
        $available_templates = [];
        $dir = storage_public('seller_themes/' . $tpl_name . '/');
        $tpl_style_dir = @opendir($dir);
        while ($file = readdir($tpl_style_dir)) {
            if ($file != '.' && $file != '..' && is_file($dir . $file) && $file != '.svn' && $file != 'index.dwt') {
                if (preg_match("/^(style|style_)(.*)*/i", $file)) { // 取模板风格缩略图
                    $start = strpos($file, '.');
                    $temp = substr($file, 0, $start);
                    $temp = explode('_', $temp);
                    if (count($temp) == 2) {
                        $available_templates[] = $temp[1];
                    }
                }
            }
        }
        @closedir($tpl_style_dir);

        if ($flag == 1) {
            $ec = '<table border="0" width="100%" cellpadding="0" cellspacing="0" class="colortable" onMouseOver="javascript:onSOver(0, this);" onMouseOut="onSOut(this);" onclick="javascript:setupTemplateFG(0);"  bgcolor="#FFFFFF"><tr><td>&nbsp;</td></tr></table>';
            if (count($available_templates) > 0) {
                foreach ($available_templates as $value) {
                    $tpl_info = get_template_info($tpl_name, $value);

                    $ec .= '<table border="0" width="100%" cellpadding="0" cellspacing="0" class="colortable" onMouseOver="javascript:onSOver(\'' . $value . '\', this);" onMouseOut="onSOut(this);" onclick="javascript:setupTemplateFG(\'' . $value . '\');"  bgcolor="' . $tpl_info['type'] . '"><tr><td>&nbsp;</td></tr></table>';

                    unset($tpl_info);
                }
            } else {
                $ec = '0';
            }

            return $ec;
        } elseif ($flag == 2) {
            $templates_temp = [''];
            if (count($available_templates) > 0) {
                foreach ($available_templates as $value) {
                    $templates_temp[] = $value;
                }
            }

            return $templates_temp;
        }
    }

    /**
     * 读取当前风格信息与当前模板风格列表
     *
     * @access  public
     * @param string $tpl_name 模版名称
     * @param string $tpl_style 模版风格名
     * @return
     */
    public function readStyleAndTpl($tpl_name, $tpl_style)
    {
        $style_info = [];
        $style_info = $this->getSellerTemplateInfo($tpl_name, $tpl_style);

        $tpl_style_info = [];
        $tpl_style_info = $this->readTplStyle($tpl_name, 2);
        $tpl_style_list = '';
        if (count($tpl_style_info) > 1) {
            foreach ($tpl_style_info as $value) {
                $tpl_style_list .= '<span style="cursor:pointer;" onMouseOver="javascript:onSOver(\'screenshot\', \'' . $value . '\', this);" onMouseOut="onSOut(\'screenshot\', this, \'' . $style_info['screenshot'] . '\');" onclick="javascript:setupTemplateFG(\'' . $tpl_name . '\', \'' . $value . '\', \'\');" id="templateType_' . $value . '"><img src="../themes/' . $tpl_name . '/images/type' . $value . '_';

                if ($value == $tpl_style) {
                    $tpl_style_list .= '1';
                } else {
                    $tpl_style_list .= '0';
                }
                $tpl_style_list .= '.gif" border="0"></span>&nbsp;';
            }
        }
        $style_info['tpl_style'] = $tpl_style_list;

        return $style_info;
    }

    /**
     * 获得商家店铺模版的信息 wang店铺模板选择
     *
     * @access  public
     * @param string $template_name 模版名
     * @param string $template_style 模版风格名
     * @return  array
     */
    public function getSellerTemplateInfo($template_name, $template_style = '')
    {
        if (empty($template_style) || $template_style == '') {
            $template_style = '';
        }

        $info = [];
        $ext = ['png', 'gif', 'jpg', 'jpeg'];

        $info['code'] = $template_name;
        $info['screenshot'] = '';
        $info['stylename'] = $template_style;

        if ($template_style == '') {
            foreach ($ext as $val) {
                if (file_exists('../seller_themes/' . $template_name . "/screenshot.$val")) {
                    $info['screenshot'] = '../seller_themes/' . $template_name . "/screenshot.$val";

                    break;
                }
            }
        } else {
            foreach ($ext as $val) {
                if (file_exists('../seller_themes/' . $template_name . "/screenshot_$template_style.$val")) {
                    $info['screenshot'] = '../seller_themes/' . $template_name . "/screenshot_$template_style.$val";

                    break;
                }
            }
        }

        $info_path = '../seller_themes/' . $template_name . '/tpl_info.txt';
        if ($template_style != '') {
            $info_path = '../seller_themes/' . $template_name . "/tpl_info_$template_style.txt";
        }
        if (file_exists($info_path) && !empty($template_name)) {
            $custom_content = addslashes(iconv("GB2312", "UTF-8", $info_path));
            $arr = array_slice(file($info_path), 0, 9);

            //ecmoban模板堂 --zhuo start
            $arr[1] = addslashes(iconv("GB2312", "UTF-8", $arr[1]));
            $arr[2] = addslashes(iconv("GB2312", "UTF-8", $arr[2]));
            $arr[3] = addslashes(iconv("GB2312", "UTF-8", $arr[3]));
            $arr[4] = addslashes(iconv("GB2312", "UTF-8", $arr[4]));
            $arr[5] = addslashes(iconv("GB2312", "UTF-8", $arr[5]));
            $arr[6] = addslashes(iconv("GB2312", "UTF-8", $arr[6]));
            $arr[7] = addslashes(iconv("GB2312", "UTF-8", $arr[7]));
            $arr[8] = addslashes(iconv("GB2312", "UTF-8", $arr[8]));
            //ecmoban模板堂 --zhuo end

            $template_name = explode('：', $arr[1]);
            $template_uri = explode('：', $arr[2]);
            $template_desc = explode('：', $arr[3]);
            $template_version = explode('：', $arr[4]);
            $template_author = explode('：', $arr[5]);
            $author_uri = explode('：', $arr[6]);
            $tpl_dwt_code = explode('：', $arr[7]);
            $win_goods_type = explode('：', $arr[8]);

            $info['name'] = isset($template_name[1]) ? trim($template_name[1]) : '';
            $info['uri'] = isset($template_uri[1]) ? trim($template_uri[1]) : '';
            $info['desc'] = isset($template_desc[1]) ? trim($template_desc[1]) : '';
            $info['version'] = isset($template_version[1]) ? trim($template_version[1]) : '';
            $info['author'] = isset($template_author[1]) ? trim($template_author[1]) : '';
            $info['author_uri'] = isset($author_uri[1]) ? trim($author_uri[1]) : '';
            $info['dwt_code'] = isset($tpl_dwt_code[1]) ? trim($tpl_dwt_code[1]) : '';
            $info['win_goods_type'] = isset($win_goods_type[1]) ? trim($win_goods_type[1]) : '';
            $info['sort'] = substr($info['code'], -1, 1);
        } else {
            $info['name'] = '';
            $info['uri'] = '';
            $info['desc'] = '';
            $info['version'] = '';
            $info['author'] = '';
            $info['author_uri'] = '';
            $info['dwt_code'] = '';
            $info['sort'] = '';
        }

        return $info;
    }

    //回车替换
    public function getPregReplace($str, $type = '|')
    {
        $str = preg_replace("/\r\n/", ",", $str); //替换空格回车换行符 为 英文逗号
        $str = $this->getStrTrim($str);
        $str = $this->getStrTrim($str, $type);

        return $str;
    }

    public function getStrTrim($str, $type = ',')
    {
        $str = explode($type, $str);
        $str2 = '';

        for ($i = 0; $i < count($str); $i++) {
            $str2 .= trim($str[$i]) . $type;
        }

        return substr($str2, 0, -1);
    }

    //读取文件内    容
    public function mcReadTxt($file)
    {
        $pathfile = $file;
        if (!file_exists($pathfile)) {
            return false;
        }
        $fs = fopen($pathfile, "r+");
        $content = fread($fs, filesize($pathfile));//读文件
        fclose($fs);

        if (!$content) {
            return false;
        }
        return $content;
    }
}
