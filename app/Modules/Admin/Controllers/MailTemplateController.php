<?php

namespace App\Modules\Admin\Controllers;

use App\Entities\Plugins;
use App\Models\MailTemplates;
use App\Repositories\Common\BaseRepository;
use App\Services\Mail\MailTemplateManageService;

/**
 * 管理中心模版管理程序
 */
class MailTemplateController extends InitController
{
    protected $baseRepository;
    protected $mailTemplateManageService;

    public function __construct(
        BaseRepository $baseRepository,
        MailTemplateManageService $mailTemplateManageService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->mailTemplateManageService = $mailTemplateManageService;
    }

    public function index()
    {
        admin_priv('mail_template');

        /*------------------------------------------------------ */
        //-- 模版列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {

            /* 包含插件语言项 */
            $res = Plugins::whereRaw(1);
            $rs = $this->baseRepository->getToArrayGet($res);
            if ($rs) {
                foreach ($rs as $row) {
                    /* 取得语言项 */
                    $pluginslang = plugin_path($row['code'] . '/languages/common_' . $GLOBALS['_CFG']['lang'] . '.php');

                    if (file_exists($pluginslang)) {
                        include_once($pluginslang);
                    }
                }
            }

            /* 获得所有邮件模板 */
            $res = MailTemplates::where('type', 'template');
            $res = $this->baseRepository->getToArrayGet($res);
            $cur = null;

            $templates = [];
            if ($res) {
                foreach ($res as $row) {
                    if ($cur == null) {
                        $cur = $row['template_id'];
                    }

                    $len = strlen($GLOBALS['_LANG'][$row['template_code']]);
                    $templates[$row['template_id']] = $len < 18 ?
                        $GLOBALS['_LANG'][$row['template_code']] . str_repeat('&nbsp;', (18 - $len) / 2) . " [$row[template_code]]" :
                        $GLOBALS['_LANG'][$row['template_code']] . " [$row[template_code]]";
                }
            }


            $content = $this->mailTemplateManageService->loadTemplate($cur);

            /* 创建 html editor */
            create_html_editor2('content', 'content', $content['template_content']);

            $this->smarty->assign('tpl', $cur);
            $this->smarty->assign('cur', $cur);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['06_mail_template_manage']);
            $this->smarty->assign('templates', $templates);
            $this->smarty->assign('template', $content);
            $this->smarty->assign('full_page', 1);
            return $this->smarty->display('mail_template.dwt');
        }

        /*------------------------------------------------------ */
        //-- 载入指定模版
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'loat_template') {
            $tpl = intval($_GET['tpl']);
            $mail_type = isset($_GET['mail_type']) ? $_GET['mail_type'] : -1;

            /* 包含插件语言项 */
            $res = Plugins::whereRaw(1);
            $rs = $this->baseRepository->getToArrayGet($res);
            if ($rs) {
                foreach ($rs as $row) {
                    /* 取得语言项 */
                    $pluginslang = plugin_path($row['code'] . '/languages/common_' . $GLOBALS['_CFG']['lang'] . '.php');

                    if (file_exists($pluginslang)) {
                        include_once($pluginslang);
                    }
                }
            }

            /* 获得所有邮件模板 */
            $res = MailTemplates::where('type', 'template');
            $res = $this->baseRepository->getToArrayGet($res);

            $templates = [];
            if ($res) {
                foreach ($res as $row) {
                    $len = strlen($GLOBALS['_LANG'][$row['template_code']]);
                    $templates[$row['template_id']] = $len < 18 ?
                        $GLOBALS['_LANG'][$row['template_code']] . str_repeat('&nbsp;', (18 - $len) / 2) . " [" . $row['template_code'] . "]" :
                        $GLOBALS['_LANG'][$row['template_code']] . " [" . $row['template_code'] . "]";
                }
            }

            $content = $this->mailTemplateManageService->loadTemplate($tpl);

            if (($mail_type == -1 && $content['is_html'] == 1) || $mail_type == 1) {
                /* 创建 html editor */
                create_html_editor2('content', 'content', $content['template_content']);

                $content['is_html'] = 1;
            } elseif ($mail_type == 0) {
                $content['is_html'] = 0;
            }

            $this->smarty->assign('tpl', $tpl);
            $this->smarty->assign('cur', $tpl);
            $this->smarty->assign('templates', $templates);
            $this->smarty->assign('template', $content);

            return make_json_result($this->smarty->fetch('mail_template.dwt'));
        }

        /*------------------------------------------------------ */
        //-- 保存模板内容
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'save_template') {
            if (empty($_POST['subject'])) {
                return sys_msg($GLOBALS['_LANG']['subject_empty'], 1, [], false);
            } else {
                $subject = trim($_POST['subject']);
            }

            if (empty($_POST['content'])) {
                return sys_msg($GLOBALS['_LANG']['content_empty'], 1, [], false);
            } else {
                $content = trim($_POST['content']);
            }

            //$type   = intval($_POST['is_html']);
            $type = intval($_POST['mail_type']); //by li
            $tpl_id = intval($_POST['tpl']);

            $data = [
                'template_subject' => str_replace('\\\'\\\'', '\\\'', $subject),
                'template_content' => str_replace('\\\'\\\'', '\\\'', $content),
                'is_html' => $type,
                'last_modify' => gmtime()
            ];
            $res = MailTemplates::where('template_id', $tpl_id)->update($data);

            if ($res > 0) {
                $link[0] = ['href' => 'mail_template.php?act=list', 'text' => $GLOBALS['_LANG']['06_mail_template_manage']];
                return sys_msg($GLOBALS['_LANG']['update_success'], 0, $link);
            } else {
                return sys_msg($GLOBALS['_LANG']['update_failed'], 1, [], false);
            }
        }
    }
}
