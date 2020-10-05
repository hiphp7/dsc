<?php

namespace App\Http\Controllers;


/**
 * 地区切换程序
 */
class RegionController extends InitController
{
    public function index()
    {
        header('Content-type: text/html; charset=' . EC_CHARSET);

        $type = (int)request()->input('type', 0);
        $parent = (int)request()->input('parent', 0);
        $action = addslashes(trim(request()->input('act', '')));

        $arr['regions'] = get_regions($type, $parent);
        if ($action == 'consigne') {
            $arr['type'] = $type + 1;
            $this->smarty->assign('type', $arr['type']);
            $this->smarty->assign('regions_list', $arr['regions']);
            $arr['content'] = $this->smarty->fetch('library/dialog.lbi');
        } else {
            $arr['type'] = $type;
            $arr['target'] = !empty($_REQUEST['target']) ? stripslashes(trim($_REQUEST['target'])) : '';
            $arr['target'] = htmlspecialchars($arr['target']);
        }

        return json_encode($arr);
    }
}
