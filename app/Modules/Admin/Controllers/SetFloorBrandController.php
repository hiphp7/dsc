<?php

namespace App\Modules\Admin\Controllers;

class SetFloorBrandController extends InitController
{
    public function index()
    {
        load_helper('template', 'admin');
        load_helper('goods', 'admin');

        $act = empty($_REQUEST['act']) ? 'list' : trim($_REQUEST['act']);

        /*------------------------------------------------------ */
        //-- 模版列表
        /*------------------------------------------------------ */
        if ($act == 'list') {
            admin_priv('template_select');

            $filename = empty($_REQUEST['filename']) ? 'index' : trim($_REQUEST['filename']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['floor_content_list']);

            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['floor_content_add'], 'href' => "set_floor_brand.php?act=add&filename=" . $filename]);

            // 获得当前的模版的信息
            $curr_template = $GLOBALS['_CFG']['template'];
            $floor_content = $this->get_floors($curr_template, $filename);
            $this->smarty->assign('floor_content', $floor_content);

            $this->smarty->assign('full_page', 1);
            return $this->smarty->display('floor_content_list.dwt');
        } elseif ($act == 'add') {
            admin_priv('template_select');

            $filename = empty($_REQUEST['filename']) ? 'index' : trim($_REQUEST['filename']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['floor_content_list'], 'href' => "set_floor_brand.php?act=list&filename=" . $filename]);
            /* 获得当前的模版的信息 */
            $curr_template = $GLOBALS['_CFG']['template'];
            $template = $this->get_template($curr_template, $filename, $GLOBALS['_LANG']['home_floor']);

            $this->smarty->assign('filename', $filename);
            $this->smarty->assign('template', $template);

            set_default_filter(); //设置默认筛选

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['set_floor']);
            return $this->smarty->display('floor_content_add.dwt');
        } elseif ($act == 'edit') {
            admin_priv('template_select');
            /* 获得当前的模版的信息 */

            $filename = !empty($_GET['filename']) ? trim($_GET['filename']) : '';
            $theme = !empty($_GET['theme']) ? trim($_GET['theme']) : '';
            $region = !empty($_GET['region']) ? trim($_GET['region']) : '';
            $cat_id = !empty($_GET['id']) ? intval($_GET['id']) : 0;

            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['floor_content_list'], 'href' => "set_floor_brand.php?act=list"]);

            $floor_content = $this->get_floor_content($theme, $filename, $cat_id, $region);
            $template = $this->get_template($theme, $filename, $GLOBALS['_LANG']['home_floor']);

            $this->smarty->assign('filename', $filename);
            $this->smarty->assign('template', $template);
            $this->smarty->assign('floor_content', $floor_content);

            $this->smarty->assign('cat_id', $cat_id);

            set_default_filter(0, $cat_id); //设置默认筛选

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['set_floor']);
            return $this->smarty->display('floor_content_add.dwt');
        } elseif ($act == 'remove') {
            $filename = !empty($_GET['filename']) ? trim($_GET['filename']) : 0;
            $theme = !empty($_GET['theme']) ? trim($_GET['theme']) : 0;
            $region = !empty($_GET['region']) ? trim($_GET['region']) : '';
            $cat_id = !empty($_GET['id']) ? intval($_GET['id']) : 0;

            $sql = "DELETE FROM " . $this->dsc->table('floor_content') . " WHERE filename = '$filename' AND theme = '$theme' AND id =  '$cat_id' AND region = '$region'";
            $this->db->query($sql);

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'set_floor_brand.php?filename=index'];
            return sys_msg($GLOBALS['_LANG']['remove_success'], 0, $link);
        }
    }

    private function get_floor_content($curr_template, $filename, $id = 0, $region = '')
    {
        $where = " where 1 ";
        if (!empty($id)) {
            $where .= " and id='$id'";
        }
        if (!empty($region)) {
            $where .= " and region='$region'";
        }
        $sql = "select * from " . $this->dsc->table('floor_content') . $where . " and filename='$filename' and theme='$curr_template'";
        $row = $this->db->getAll($sql);

        return $row;
    }

    private function get_floors($curr_template, $filename)
    {
        $sql = "select * from " . $this->dsc->table('floor_content') . " where filename='$filename' and theme='$curr_template' group by filename,theme,id";

        $row = $this->db->getAll($sql);
        foreach ($row as $key => $val) {
            $row[$key]['brand_list'] = $this->db->getAll("select b.brand_id, b.brand_name from " . $this->dsc->table('brand') . " AS b, " .
                $this->dsc->table('floor_content') . " AS fc " .
                " where fc.filename = '" . $val['filename'] . "' AND theme = '" . $val['theme'] . "' AND id = '" . $val['id'] . "' AND id = '" . $val['id'] . "' AND region = '" . $val['region'] . "' AND b.brand_id = fc.brand_id");
            $row[$key]['cat_name'] = $this->db->getOne("select cat_name from " . $this->dsc->table('category') . " where cat_id='$val[id]' limit 1");
        }

        return $row;
    }

    private function get_template($curr_template, $filename, $region)
    {
        $sql = "select region,id from " . $this->dsc->table('template') . " where filename='$filename' and theme='$curr_template' and region='$region'";
        $res = $this->db->getAll($sql);

        $arr = [];
        foreach ($res as $key => $row) {
            $arr[$key] = $row;
            $arr[$key]['filename'] = $filename;
            $arr[$key]['cat_name'] = $this->db->getOne("select cat_name from " . $this->dsc->table('category') . " where cat_id = '" . $row['id'] . "' limit 1");
        }

        return $arr;
    }
}
