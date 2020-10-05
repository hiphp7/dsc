<?php

namespace App\Modules\Admin\Controllers;

use App\Models\SellerGrade;
use App\Models\SellerShopheader;
use App\Models\SellerShopinfo;
use App\Models\SellerShopslide;
use App\Models\SellerShopwindow;
use App\Repositories\Common\BaseRepository;
use App\Services\Merchant\MerchantsTemplateManageService;

/**
 * 管理中心入驻商家店铺模板管理程序
 */
class MerchantsTemplateController extends InitController
{

    protected $baseRepository;
    protected $merchantsTemplateManageService;

    public function __construct(
        BaseRepository $baseRepository,
        MerchantsTemplateManageService $merchantsTemplateManageService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->merchantsTemplateManageService = $merchantsTemplateManageService;
    }

    public function index()
    {
        load_helper('template', 'admin');

        //获得商家店铺模板信息
        $adminru = get_admin_ru_id();
        $adminru['ru_id'] = 0; //暂为0 by wu

        $res = SellerShopinfo::where('ru_id', $adminru['ru_id']);
        $shop_info = $this->baseRepository->getToArrayFirst($res);

        $shop_id = SellerShopinfo::where('ru_id', $adminru['ru_id'])->count();
        if ($shop_id < 1) {
            $lnk[] = ['text' => $GLOBALS['_LANG']['set_store_info'], 'href' => 'index.php?act=merchants_first'];
            return sys_msg($GLOBALS['_LANG']['set_store_info_alt'], 0, $lnk);
        }

        /*------------------------------------------------------ */
        //-- 店铺橱窗列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            admin_priv('seller_store_other');//by kong
            /* 获得当前的模版的信息 */
            $curr_template = $shop_info['seller_theme'];
            $curr_style = $shop_info['store_style'];

            /*获取商家等级封顶商品数 by kong grade*/
            if ($adminru['ru_id'] > 0) {
                $ru_id = $adminru['ru_id'];
                $seller_temp = SellerGrade::whereHas('getMerchantsGrade', function ($query) use ($ru_id) {
                    $query->where('ru_id', $ru_id);
                })->value('seller_temp');


                $this->smarty->assign('seller_temp', $seller_temp);
            }

            /* 获得可用的模版 */
            $available_templates = [];
            $template_dir = @opendir(storage_public('seller_themes/'));
            while ($file = @readdir($template_dir)) {
                if ($file != '.' && $file != '..' && is_dir(storage_public('seller_themes/' . $file)) && $file != '.svn' && $file != 'index.dwt') {
                    $available_templates[] = $this->merchantsTemplateManageService->getSellerTemplateInfo($file);
                }
            }

            $available_templates = get_array_sort($available_templates, 'sort');

            @closedir($template_dir);

            /* 获得可用的模版的可选风格数组 */
            $templates_style = [];
            if (count($available_templates) > 0) {
                foreach ($available_templates as $value) {
                    $templates_style[$value['code']] = $this->merchantsTemplateManageService->readTplStyle($value['code'], 2);
                }
            }

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['template_manage']);
            $this->smarty->assign('curr_tpl_style', $curr_style);
            $this->smarty->assign('template_style', $templates_style);
            $this->smarty->assign('curr_template', $this->merchantsTemplateManageService->getSellerTemplateInfo($curr_template, $curr_style));
            $this->smarty->assign('available_templates', $available_templates);
            return $this->smarty->display('merchants_template_list.dwt');
        }
        /*------------------------------------------------------ */
        //-- 安装        模版
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'install') {
            $tpl_name = trim($_GET['tpl_name']);
            $tpl_fg = 0;
            $tpl_fg = trim($_GET['tpl_fg']);

            $custom_dirname = $this->dsc->url();

            //正则去掉js代码
            $preg = "/<script[\s\S]*?<\/script>/i";

            $template_info = $this->merchantsTemplateManageService->getSellerTemplateInfo($tpl_name);
            $data = [
                'seller_theme' => $tpl_name,
                'store_style' => $tpl_fg,
                'win_goods_type' => $template_info['win_goods_type']
            ];
            $step_install = SellerShopinfo::where('ru_id', $adminru['ru_id'])->update($data);

            SellerShopheader::where('seller_theme', $tpl_name)->where('ru_id', $adminru['ru_id'])->delete();

            //安装模板装修代码
            //店铺头部装修
            //检测是否设置过此模板的头部
            $res = SellerShopheader::where('seller_theme', $tpl_name)->where('ru_id', $adminru['ru_id']);
            $header_info = $this->baseRepository->getToArrayFirst($res);
            $header_info['count'] = count($header_info);
            if ($header_info['count'] == 0) {
                $header_path = storage_public('seller_themes/' . $tpl_name . '/header.txt');

                if (file_exists($header_path)) {
                    $content = file_get_contents($header_path);

                    $header_content = !empty($content) ? preg_replace($preg, "", stripslashes($content)) : '';
                    $header_content = addslashes(iconv("GB2312", "UTF-8", $header_content));

                    if (strlen($header_content) >= 3) {//默认有3个字符
                        $patterns = [];
                        $patterns[0] = '/themes/';
                        $replacements = [];
                        $replacements[0] = $custom_dirname . 'themes';

                        $header_content = preg_replace($patterns, $replacements, $header_content);

                        $data = [
                            'content' => $header_content,
                            'seller_theme' => $tpl_name,
                            'ru_id' => $adminru['ru_id']
                        ];

                        SellerShopheader::insert($data);
                    }
                }
            } elseif ($header_info['content'] == '') {
                $header_path = storage_public('seller_themes/' . $tpl_name . '/header.txt');
                if (file_exists($header_path)) {
                    $content = file_get_contents($header_path);

                    $header_content = !empty($content) ? preg_replace($preg, "", stripslashes($content)) : '';
                    $header_content = addslashes(iconv("GB2312", "UTF-8", $header_content));

                    if (strlen($header_content) >= 3) {//默认有3个字符
                        $patterns = [];
                        $patterns[0] = '/themes/';
                        $replacements = [];
                        $replacements[0] = $custom_dirname . 'themes';

                        $header_content = preg_replace($patterns, $replacements, $header_content);
                        $data = ['content' => $header_content];
                        SellerShopheader::where('seller_theme', $tpl_name)->where('ru_id', $adminru['ru_id'])->update($data);


                    }
                }
            }

            //模板幻灯片安装
            //检测是否设置过此模板的幻灯片
            $count = SellerShopslide::where('seller_theme', $tpl_name)
                ->where('ru_id', $adminru['ru_id'])
                ->count();

            if ($count == 0) {
                $silde_path = storage_public('seller_themes/' . $tpl_name . '/slides.txt');

                if (file_exists($silde_path)) {
                    $str = $this->merchantsTemplateManageService->mcReadTxt($silde_path);
                    $str = $this->merchantsTemplateManageService->getPregReplace($str);
                    $slide_arr = explode(',', $str);
                    if ($slide_arr) {
                        foreach ($slide_arr as $key => $val) {
                            $val = addslashes($val);
                            $data = [
                                'ru_id' => $adminru['ru_id'],
                                'img_url' => $val,
                                'img_link' => '',
                                'img_desc' => '',
                                'is_show' => 1,
                                'seller_theme' => $tpl_name,
                                'install_img' => 1
                            ];
                            SellerShopslide::insert($data);
                        }
                    }
                }
            }

            //橱窗自定义装饰代码
            //检测是否设置过此模板的自定义区域
            $count = SellerShopwindow::where('seller_theme', $tpl_name)
                ->where('win_type', 0)
                ->where('ru_id', $adminru['ru_id'])
                ->count();
            if ($count == 0) {
                $custom_path = storage_public('seller_themes/' . $tpl_name . '/custom/');

                $dir = @opendir($custom_path);
                while ($file = @readdir($dir)) {
                    $file = iconv("GB2312", "UTF-8", $file);
                    if ($file != '.' && $file != '..' && !is_dir(storage_public('seller_themes/' . $file))) {
                        $content_path = storage_public('seller_themes/' . $tpl_name . '/custom/' . $file);

                        $ext = pathinfo($content_path);
                        $cus_name = substr($file, 0, strrpos($file, '.')); //文件名作为自定义区域的名称录入数据库
                        $win_order = str_replace('custom', '', $cus_name);

                        if ($ext['extension'] == 'txt') {
                            $content_path = iconv("UTF-8", "GB2312", $content_path);
                            $content = file_get_contents($content_path, true);

                            $custom_content = !empty($content) ? preg_replace($preg, "", stripslashes($content)) : '';
                            $custom_content = addslashes(iconv("GB2312", "UTF-8", $custom_content));

                            if (strlen($custom_content) >= 3) {//默认有3个字符
                                $patterns = [];
                                $patterns[0] = '/themes/';
                                $replacements = [];
                                $replacements[0] = $custom_dirname . 'themes';

                                $custom_content = preg_replace($patterns, $replacements, $custom_content);
                                $data = [
                                    'win_type' => 0,
                                    'win_name' => $cus_name,
                                    'win_order' => $win_order,
                                    'ru_id' => $adminru['ru_id'],
                                    'is_show' => 1,
                                    'win_custom' => $custom_content,
                                    'seller_theme' => $tpl_name
                                ];
                                SellerShopwindow::insert($data);
                            }
                        }
                    }
                }
                @closedir($custom_path);
            }

            if ($step_install) {
                clear_all_files(); //清除模板编译文件

                $error_msg = '';
                return make_json_result($this->merchantsTemplateManageService->readStyleAndTpl($tpl_name, $tpl_fg), $GLOBALS['_LANG']['template_install_success']);
            } else {
                return make_json_error($this->db->error());
            }
        }

        /*------------------------------------------------------ */
        //-- 设置使用店铺默认模板
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'user_default') {
            $adminru = get_admin_ru_id();

            $data = ['seller_theme' => ''];
            SellerShopinfo::where('ru_id', $adminru['ru_id'])->update($data);

            return make_json_result('', $GLOBALS['_LANG']['default_template_set_success']);
        }
    }
}
