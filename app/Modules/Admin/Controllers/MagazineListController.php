<?php

namespace App\Modules\Admin\Controllers;

use App\Models\EmailList;
use App\Models\EmailSendlist;
use App\Models\MailTemplates;
use App\Models\UserRank;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Magazine\MagazineListManageService;

/**
 * 程序说明
 */
class MagazineListController extends InitController
{
    protected $dscRepository;
    protected $baseRepository;
    protected $magazineListManageService;

    public function __construct(
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        MagazineListManageService $magazineListManageService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
        $this->magazineListManageService = $magazineListManageService;
    }

    public function index()
    {
        admin_priv('magazine_list');
        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['04_magazine_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['add_new'], 'href' => 'magazine_list.php?act=add']);
            $this->smarty->assign('full_page', 1);

            $magazinedb = $this->magazineListManageService->getMagazine();

            $this->smarty->assign('magazinedb', $magazinedb['magazinedb']);
            $this->smarty->assign('filter', $magazinedb['filter']);
            $this->smarty->assign('record_count', $magazinedb['record_count']);
            $this->smarty->assign('page_count', $magazinedb['page_count']);

            $special_ranks = get_rank_list();
            $send_rank[SEND_LIST . '_0'] = $GLOBALS['_LANG']['email_user'];
            $send_rank[SEND_USER . '_0'] = $GLOBALS['_LANG']['user_list'];
            foreach ($special_ranks as $rank_key => $rank_value) {
                $send_rank[SEND_RANK . '_' . $rank_key] = $rank_value;
            }
            $this->smarty->assign('send_rank', $send_rank);


            return $this->smarty->display('magazine_list.dwt');
        } elseif ($_REQUEST['act'] == 'query') {
            $magazinedb = $this->magazineListManageService->getMagazine();
            $this->smarty->assign('magazinedb', $magazinedb['magazinedb']);
            $this->smarty->assign('filter', $magazinedb['filter']);
            $this->smarty->assign('record_count', $magazinedb['record_count']);
            $this->smarty->assign('page_count', $magazinedb['page_count']);

            $sort_flag = sort_flag($magazinedb['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('magazine_list.dwt'), '', ['filter' => $magazinedb['filter'], 'page_count' => $magazinedb['page_count']]);
        } elseif ($_REQUEST['act'] == 'add') {
            if (empty($_POST['step'])) {
                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['go_list'], 'href' => 'magazine_list.php?act=list']);
                $this->smarty->assign(['ur_here' => $GLOBALS['_LANG']['04_magazine_list'], 'act' => 'add']);
                create_html_editor('magazine_content');

                return $this->smarty->display('magazine_list_add.dwt');
            } elseif ($_POST['step'] == 2) {
                $magazine_name = trim($_POST['magazine_name']);
                $magazine_content = trim($_POST['magazine_content']);

                if (strpos($magazine_content, "https://") === false && strpos($magazine_content, "http://") === false) {
                    $magazine_content = str_replace('src=\"', 'src=\"http://' . request()->server('HTTP_HOST'), $magazine_content);
                }

                $time = gmtime();
                $data = [
                    'template_code' => md5($magazine_name . $time),
                    'is_html' => 1,
                    'template_subject' => $magazine_name,
                    'template_content' => $magazine_content,
                    'last_modify' => $time,
                    'type' => 'magazine'
                ];
                MailTemplates::insert($data);
                $links[] = ['text' => $GLOBALS['_LANG']['04_magazine_list'], 'href' => 'magazine_list.php?act=list'];
                $links[] = ['text' => $GLOBALS['_LANG']['add_new'], 'href' => 'magazine_list.php?act=add'];
                return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
            }
        } elseif ($_REQUEST['act'] == 'edit') {
            $id = intval($_REQUEST['id']);
            if (empty($_POST['step'])) {
                $res = MailTemplates::where('type', 'magazine')->where('template_id', $id);
                $rt = $this->baseRepository->getToArrayFirst($res);

                $this->smarty->assign(['id' => $id, 'act' => 'edit', 'magazine_name' => $rt['template_subject'], 'magazine_content' => $rt['template_content']]);
                $this->smarty->assign(['ur_here' => $GLOBALS['_LANG']['04_magazine_list'], 'act' => 'edit']);
                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['go_list'], 'href' => 'magazine_list.php?act=list']);

                if ($GLOBALS['_CFG']['open_oss'] == 1) {
                    $bucket_info = $this->dscRepository->getBucketInfo();
                    $endpoint = $bucket_info['endpoint'];
                } else {
                    $endpoint = url('/');
                }

                if ($rt['template_content']) {
                    $desc_preg = get_goods_desc_images_preg($endpoint, $rt['template_content']);
                    $rt['template_content'] = $desc_preg['goods_desc'];
                }

                create_html_editor('magazine_content', $rt['template_content']);

                return $this->smarty->display('magazine_list_add.dwt');
            } elseif ($_POST['step'] == 2) {
                $magazine_name = trim($_POST['magazine_name']);
                $magazine_content = trim($_POST['magazine_content']);

                if (strpos($magazine_content, "https://") === false && strpos($magazine_content, "http://") === false) {
                    $magazine_content = str_replace('src=\"', 'src=\"http://' . request()->server('HTTP_HOST'), $magazine_content);
                }

                $time = gmtime();
                $data = [
                    'is_html' => 1,
                    'template_subject' => $magazine_name,
                    'template_content' => $magazine_content,
                    'last_modify' => $time
                ];
                MailTemplates::where('type', 'magazine')->where('template_id', $id)->update($data);

                $links[] = ['text' => $GLOBALS['_LANG']['04_magazine_list'], 'href' => 'magazine_list.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
            }
        } elseif ($_REQUEST['act'] == 'del') {
            $id = intval($_REQUEST['id']);
            MailTemplates::where('type', 'magazine')->where('template_id', $id)->delete();
            $links[] = ['text' => $GLOBALS['_LANG']['04_magazine_list'], 'href' => 'magazine_list.php?act=list'];
            return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
        } elseif ($_REQUEST['act'] == 'addtolist') {
            $id = intval($_REQUEST['id']);
            $pri = !empty($_REQUEST['pri']) ? 1 : 0;
            $start = empty($_GET['start']) ? 0 : (int)$_GET['start'];
            $send_rank = $_REQUEST['send_rank'];
            $rank_array = explode('_', $send_rank);

            $template_id = MailTemplates::where('type', 'magazine')->where('template_id', $id)->value('template_id');
            $template_id = $template_id ? $template_id : 0;

            if (!empty($template_id)) {
                if (SEND_LIST == $rank_array['0']) {
                    $count = EmailList::where('stat', 1)->count();
                    if ($count > $start) {
                        $res = EmailList::where('stat', 1)->offset($start)->limit(100);
                        $query = $this->baseRepository->getToArrayGet($res);

                        $i = 0;
                        foreach ($query as $rt) {
                            $time = time();
                            $rt['email'] = $rt['email'] ?? '';
                            $data = [
                                'email' => $rt['email'],
                                'template_id' => $id,
                                'pri' => $pri,
                                'last_send' => $time
                            ];
                            EmailSendlist::insert($data);
                            $i++;
                        }

                        if ($i == 100) {
                            $start = $start + 100;
                        } else {
                            $start = $start + $i;
                        }
                        $links[] = ['text' => sprintf($GLOBALS['_LANG']['finish_list'], $start), 'href' => "magazine_list.php?act=addtolist&id=$id&pri=$pri&start=$start&send_rank=$send_rank"];
                        return sys_msg($GLOBALS['_LANG']['finishing'], 0, $links);
                    } else {
                        $data = ['last_send' => time()];
                        MailTemplates::where('type', 'magazine')->where('template_id', $id)->update($data);
                        $links[] = ['text' => $GLOBALS['_LANG']['04_magazine_list'], 'href' => 'magazine_list.php?act=list'];
                        return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
                    }
                } else {

                    $res = UserRank::where('rank_id', $rank_array['1']);
                    $row = $this->baseRepository->getToArrayFirst($res);
                    if (SEND_USER == $rank_array['0']) {
                        $count_sql = 'SELECT COUNT(*) FROM ' . $this->dsc->table('users') . 'WHERE is_validated = 1';
                        $email_sql = 'SELECT email FROM ' . $this->dsc->table('users') . "WHERE is_validated = 1 LIMIT $start,100";
                    } else/*if ($row['special_rank'])*/ {
                        $count_sql = 'SELECT COUNT(*) FROM ' . $this->dsc->table('users') . 'WHERE is_validated = 1 AND user_rank = ' . $rank_array['1'];
                        $email_sql = 'SELECT email FROM ' . $this->dsc->table('users') . 'WHERE is_validated = 1 AND user_rank = ' . $rank_array['1'] . " LIMIT $start,100";
//                    } else {
//                        $count_sql = 'SELECT COUNT(*) ' .
//                            'FROM ' . $this->dsc->table('users') . ' AS u LEFT JOIN ' . $this->dsc->table('user_rank') . ' AS ur ' .
//                            "  ON ur.special_rank = '0' AND ur.min_points <= u.rank_points AND ur.max_points >= u.rank_points" .
//                            " WHERE ur.rank_id = '" . $rank_array['1'] . "' AND u.is_validated = 1";
//                        $email_sql = 'SELECT u.email ' .
//                            'FROM ' . $this->dsc->table('users') . ' AS u LEFT JOIN ' . $this->dsc->table('user_rank') . ' AS ur ' .
//                            "  ON ur.special_rank = '0' AND ur.min_points <= u.rank_points AND ur.max_points >= u.rank_points" .
//                            " WHERE ur.rank_id = '" . $rank_array['1'] . "' AND u.is_validated = 1 LIMIT $start,100";
                    }

                    $count = $this->db->getOne($count_sql);
                    if ($count > $start) {
                        $query = $this->db->query($email_sql);

                        $i = 0;
                        foreach ($query as $rt) {
                            $time = time();

                            $rt['email'] = $rt['email'] ?? '';
                            $data = [
                                'email' => $rt['email'],
                                'template_id' => $id,
                                'pri' => $pri,
                                'last_send' => $time
                            ];
                            EmailSendlist::insert($data);

                            $i++;
                        }
                        if ($i == 100) {
                            $start = $start + 100;
                        } else {
                            $start = $start + $i;
                        }
                        $links[] = ['text' => sprintf($GLOBALS['_LANG']['finish_list'], $start), 'href' => "magazine_list.php?act=addtolist&id=$id&pri=$pri&start=$start&send_rank=$send_rank"];
                        return sys_msg($GLOBALS['_LANG']['finishing'], 0, $links);
                    } else {
                        $data = ['last_send' => time()];
                        MailTemplates::where('type', 'magazine')->where('template_id', $id)->update($data);

                        $links[] = ['text' => $GLOBALS['_LANG']['04_magazine_list'], 'href' => 'magazine_list.php?act=list'];
                        return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
                    }
                }
            } else {
                $links[] = ['text' => $GLOBALS['_LANG']['04_magazine_list'], 'href' => 'magazine_list.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
            }
        }
    }
}
