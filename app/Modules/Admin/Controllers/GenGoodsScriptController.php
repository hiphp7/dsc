<?php

namespace App\Modules\Admin\Controllers;

/**
 * 生成显示商品的js代码
 */
class GenGoodsScriptController extends InitController
{
    public function index()
    {

        /*------------------------------------------------------ */
        //-- 生成代码
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'setup') {
            /* 检查权限 */
            admin_priv('gen_goods_script');

            /* 编码 */
            $lang_list = [
                'UTF8' => $GLOBALS['_LANG']['charset']['utf8'],
                'GB2312' => $GLOBALS['_LANG']['charset']['zh_cn'],
                'BIG5' => $GLOBALS['_LANG']['charset']['zh_tw'],
            ];

            /* 参数赋值 */
            $ur_here = $GLOBALS['_LANG']['16_goods_script'];
            $this->smarty->assign('ur_here', $ur_here);
            $this->smarty->assign('brand_list', get_brand_list());
            $this->smarty->assign('intro_list', $GLOBALS['_LANG']['intro']);
            $this->smarty->assign('url', $this->dsc->url());
            $this->smarty->assign('lang_list', $lang_list);

            set_default_filter(); //设置默认筛选

            /* 显示模板 */

            return $this->smarty->display('gen_goods_script.dwt');
        }
    }
}
