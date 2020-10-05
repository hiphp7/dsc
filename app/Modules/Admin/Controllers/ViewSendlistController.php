<?php

namespace App\Modules\Admin\Controllers;

use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;

/**
 * 程序说明
 */
class ViewSendlistController extends InitController
{
    protected $commonRepository;
    protected $dscRepository;

    public function __construct(
        CommonRepository $commonRepository,
        DscRepository $dscRepository
    )
    {
        $this->commonRepository = $commonRepository;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        admin_priv('view_sendlist');
        if ($_REQUEST['act'] == 'list') {
            $listdb = $this->get_sendlist();
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['05_view_sendlist']);
            $this->smarty->assign('full_page', 1);

            $this->smarty->assign('listdb', $listdb['listdb']);
            $this->smarty->assign('filter', $listdb['filter']);
            $this->smarty->assign('record_count', $listdb['record_count']);
            $this->smarty->assign('page_count', $listdb['page_count']);


            return $this->smarty->display('view_sendlist.dwt');
        } elseif ($_REQUEST['act'] == 'query') {
            $listdb = $this->get_sendlist();
            $this->smarty->assign('listdb', $listdb['listdb']);
            $this->smarty->assign('filter', $listdb['filter']);
            $this->smarty->assign('record_count', $listdb['record_count']);
            $this->smarty->assign('page_count', $listdb['page_count']);

            $sort_flag = sort_flag($listdb['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('view_sendlist.dwt'), '', ['filter' => $listdb['filter'], 'page_count' => $listdb['page_count']]);
        } elseif ($_REQUEST['act'] == 'del') {
            $id = (int)$_REQUEST['id'];
            $sql = "DELETE FROM " . $this->dsc->table('email_sendlist') . " WHERE id = '$id' LIMIT 1";
            $this->db->query($sql);
            $links[] = ['text' => $GLOBALS['_LANG']['05_view_sendlist'], 'href' => 'view_sendlist.php?act=list'];
            return sys_msg($GLOBALS['_LANG']['del_ok'], 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 批量删除
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'batch_remove') {
            /* 检查权限 */
            if (isset($_POST['checkboxes'])) {
                $sql = "DELETE FROM " . $this->dsc->table('email_sendlist') . " WHERE id " . db_create_in($_POST['checkboxes']);
                $this->db->query($sql);

                $links[] = ['text' => $GLOBALS['_LANG']['05_view_sendlist'], 'href' => 'view_sendlist.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['del_ok'], 0, $links);
            } else {
                $links[] = ['text' => $GLOBALS['_LANG']['05_view_sendlist'], 'href' => 'view_sendlist.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['no_select'], 0, $links);
            }
        }

        /*------------------------------------------------------ */
        //-- 批量发送
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'batch_send') {
            /* 检查权限 */
            if (isset($_POST['checkboxes'])) {
                $sql = "SELECT * FROM " . $this->dsc->table('email_sendlist') . "WHERE id " . db_create_in($_POST['checkboxes']) . " ORDER BY pri DESC, last_send ASC LIMIT 1";
                $row = $this->db->getRow($sql);

                //发送列表为空
                if (empty($row['id'])) {
                    $links[] = ['text' => $GLOBALS['_LANG']['05_view_sendlist'], 'href' => 'view_sendlist.php?act=list'];
                    return sys_msg($GLOBALS['_LANG']['mailsend_null'], 0, $links);
                }

                $sql = "SELECT * FROM " . $this->dsc->table('email_sendlist') . "WHERE id " . db_create_in($_POST['checkboxes']) . " ORDER BY pri DESC, last_send ASC";
                $res = $this->db->query($sql);
                foreach ($res as $row) {
                    //发送列表不为空，邮件地址为空
                    if (!empty($row['id']) && empty($row['email'])) {
                        $sql = "DELETE FROM " . $this->dsc->table('email_sendlist') . " WHERE id = '$row[id]'";
                        $this->db->query($sql);
                        continue;
                    }

                    //查询相关模板
                    $sql = "SELECT * FROM " . $this->dsc->table('mail_templates') . " WHERE template_id = '$row[template_id]'";
                    $rt = $this->db->getRow($sql);

                    //如果是模板，则将已存入email_sendlist的内容作为邮件内容
                    //否则即是杂质，将mail_templates调出的内容作为邮件内容
                    if ($rt['type'] == 'template') {
                        $rt['template_content'] = $row['email_content'];
                    }

                    if ($rt['template_id'] && $rt['template_content']) {
                        if ($this->commonRepository->sendEmail('', $row['email'], $rt['template_subject'], $rt['template_content'], $rt['is_html'])) {
                            //发送成功

                            //从列表中删除
                            $sql = "DELETE FROM " . $this->dsc->table('email_sendlist') . " WHERE id = '$row[id]'";
                            $this->db->query($sql);
                        } else {
                            //发送出错

                            if ($row['error'] < 3) {
                                $time = time();
                                $sql = "UPDATE " . $this->dsc->table('email_sendlist') . " SET error = error + 1, pri = 0, last_send = '$time' WHERE id = '$row[id]'";
                            } else {
                                //将出错超次的纪录删除
                                $sql = "DELETE FROM " . $this->dsc->table('email_sendlist') . " WHERE id = '$row[id]'";
                            }
                            $this->db->query($sql);
                        }
                    } else {
                        //无效的邮件队列
                        $sql = "DELETE FROM " . $this->dsc->table('email_sendlist') . " WHERE id = '$row[id]'";
                        $this->db->query($sql);
                    }
                }

                $links[] = ['text' => $GLOBALS['_LANG']['05_view_sendlist'], 'href' => 'view_sendlist.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['mailsend_finished'], 0, $links);
            } else {
                $links[] = ['text' => $GLOBALS['_LANG']['05_view_sendlist'], 'href' => 'view_sendlist.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['no_select'], 0, $links);
            }
        }

        /*------------------------------------------------------ */
        //-- �        �部发送
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'all_send') {
            $sql = "SELECT * FROM " . $this->dsc->table('email_sendlist') . " ORDER BY pri DESC, last_send ASC LIMIT 1";
            $row = $this->db->getRow($sql);

            //发送列表为空
            if (empty($row['id'])) {
                $links[] = ['text' => $GLOBALS['_LANG']['05_view_sendlist'], 'href' => 'view_sendlist.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['mailsend_null'], 0, $links);
            }

            $sql = "SELECT * FROM " . $this->dsc->table('email_sendlist') . " ORDER BY pri DESC, last_send ASC";
            $res = $this->db->query($sql);
            foreach ($res as $row) {
                //发送列表不为空，邮件地址为空
                if (!empty($row['id']) && empty($row['email'])) {
                    $sql = "DELETE FROM " . $this->dsc->table('email_sendlist') . " WHERE id = '$row[id]'";
                    $this->db->query($sql);
                    continue;
                }

                //查询相关模板
                $sql = "SELECT * FROM " . $this->dsc->table('mail_templates') . " WHERE template_id = '$row[template_id]'";
                $rt = $this->db->getRow($sql);

                //如果是模板，则将已存入email_sendlist的内容作为邮件内容
                //否则即是杂质，将mail_templates调出的内容作为邮件内容
                if ($rt['type'] == 'template') {
                    $rt['template_content'] = $row['email_content'];
                }

                if ($rt['template_id'] && $rt['template_content']) {
                    if ($this->commonRepository->sendEmail('', $row['email'], $rt['template_subject'], $rt['template_content'], $rt['is_html'])) {
                        //发送成功

                        //从列表中删除
                        $sql = "DELETE FROM " . $this->dsc->table('email_sendlist') . " WHERE id = '$row[id]'";
                        $this->db->query($sql);
                    } else {
                        //发送出错

                        if ($row['error'] < 3) {
                            $time = time();
                            $sql = "UPDATE " . $this->dsc->table('email_sendlist') . " SET error = error + 1, pri = 0, last_send = '$time' WHERE id = '$row[id]'";
                        } else {
                            //将出错超次的纪录删除
                            $sql = "DELETE FROM " . $this->dsc->table('email_sendlist') . " WHERE id = '$row[id]'";
                        }
                        $this->db->query($sql);
                    }
                } else {
                    //无效的邮件队列
                    $sql = "DELETE FROM " . $this->dsc->table('email_sendlist') . " WHERE id = '$row[id]'";
                    $this->db->query($sql);
                }
            }

            $links[] = ['text' => $GLOBALS['_LANG']['05_view_sendlist'], 'href' => 'view_sendlist.php?act=list'];
            return sys_msg($GLOBALS['_LANG']['mailsend_finished'], 0, $links);
        }
    }

    private function get_sendlist()
    {
        $result = get_filter();
        if ($result === false) {
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $sql = "SELECT count(*) FROM " . $this->dsc->table('email_sendlist') . " e LEFT JOIN " . $this->dsc->table('mail_templates') . " m ON e.template_id = m.template_id";
            $filter['record_count'] = $this->db->getOne($sql);

            /* 分页大小 */
            $filter = page_and_size($filter);

            /* 查询 */
            $sql = "SELECT e.id, e.email, e.pri, e.error, FROM_UNIXTIME(e.last_send) AS last_send, m.template_subject, m.type FROM " . $this->dsc->table('email_sendlist') . " e LEFT JOIN " . $this->dsc->table('mail_templates') . " m ON e.template_id = m.template_id" .
                " ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] .
                " LIMIT " . $filter['start'] . ",$filter[page_size]";
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $listdb = $this->db->getAll($sql);

        if ($listdb) {
            foreach ($listdb as $key => $val) {
                if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                    $listdb[$key]['email'] = $this->dscRepository->stringToStar($val['email']);
                }
            }
        }

        $arr = ['listdb' => $listdb, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }
}
