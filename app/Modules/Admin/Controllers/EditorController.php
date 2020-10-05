<?php

namespace App\Modules\Admin\Controllers;

/**
 * 编辑器
 */
class EditorController extends InitController
{
    public function index()
    {
        $item = isset($_GET['item']) ? htmlspecialchars($_GET['item']) : '';

        $this->smarty->assign('item', $item);
        $this->smarty->assign('lang', $GLOBALS['_CFG']['lang']);

        /* 显示商品信息页面 */
        return $this->smarty->display('editor.dwt');
    }
}
