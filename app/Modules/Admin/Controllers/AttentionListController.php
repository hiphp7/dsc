<?php

namespace App\Modules\Admin\Controllers;

use App\Models\EmailSendlist;
use App\Models\MailTemplates;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Other\AttentionListManageService;

/**
 * 程序说明
 */
class AttentionListController extends InitController
{
    protected $attentionListManageService;
    protected $baseRepository;
    protected $timeRepository;
    protected $dscRepository;

    public function __construct(
        AttentionListManageService $attentionListManageService,
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        DscRepository $dscRepository
    )
    {
        $this->attentionListManageService = $attentionListManageService;
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        admin_priv('attention_list');

        if ($_REQUEST['act'] == 'list') {
            $goodsdb = $this->attentionListManageService->getAttenTion();
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['02_attention_list']);
            $this->smarty->assign('goodsdb', $goodsdb['goodsdb']);
            $this->smarty->assign('filter', $goodsdb['filter']);
            $this->smarty->assign('cfg_lang', $GLOBALS['_CFG']['lang']);
            $this->smarty->assign('record_count', $goodsdb['record_count']);
            $this->smarty->assign('page_count', $goodsdb['page_count']);

            return $this->smarty->display('attention_list.dwt');
        } elseif ($_REQUEST['act'] == 'query') {
            $goodsdb = $this->attentionListManageService->getAttenTion();
            $this->smarty->assign('goodsdb', $goodsdb['goodsdb']);
            $this->smarty->assign('filter', $goodsdb['filter']);
            $this->smarty->assign('record_count', $goodsdb['record_count']);
            $this->smarty->assign('page_count', $goodsdb['page_count']);
            return make_json_result(
                $this->smarty->fetch('attention_list.dwt'),
                '',
                ['filter' => $goodsdb['filter'], 'page_count' => $goodsdb['page_count']]
            );
        } elseif ($_REQUEST['act'] == 'addtolist') {
            $id = intval($_REQUEST['id']);
            $pri = (intval($_REQUEST['pri']) == 1) ? 1 : 0;
            $start = empty($_GET['start']) ? 0 : (int)$_GET['start'];

            $count = $this->attentionListManageService->getUserCollectGoodsCount($id);
            if ($count > $start) {
                $query = $this->attentionListManageService->getUserCollectGoodsList($id, 0, $start, 100);

                $template = MailTemplates::where('template_code', 'attention_list')
                    ->where('type', 'template');
                $template = $this->baseRepository->getToArrayFirst($template);

                $i = 0;
                if ($template) {
                    foreach ($query as $rt) {
                        $rt['user_name'] = $rt['get_users']['user_name'] ?? '';
                        $rt['email'] = $rt['get_users']['email'] ?? '';

                        $rt['goods_id'] = $rt['get_goods']['goods_id'] ?? 0;
                        $rt['goods_name'] = $rt['get_goods']['goods_name'] ?? '';

                        $time = $this->timeRepository->getGmTime();
                        $preg_replace = $this->dsc->url() . $this->dscRepository->buildUri('goods', ['gid' => $id], $rt['goods_name']);

                        $send_date = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $time);
                        $this->smarty->assign(['user_name' => $rt['user_name'], 'goods_name' => $rt['goods_name'], 'goods_url' => $preg_replace, 'shop_name' => $GLOBALS['_CFG']['shop_title'], 'send_date' => $send_date]);
                        $content = $this->smarty->fetch("str:$template[template_content]");

                        EmailSendlist::insert([
                            'email' => $rt['email'],
                            'template_id' => $template['template_id'],
                            'email_content' => $content,
                            'pri' => $pri,
                            'last_send' => $time
                        ]);

                        $i++;
                    }
                }

                if ($i == 100) {
                    $start = $start + 100;
                } else {
                    $start = $start + $i;
                }

                $links[] = ['text' => sprintf($GLOBALS['_LANG']['finish_list'], $start), 'href' => "attention_list.php?act=addtolist&id=$id&pri=$pri&start=$start"];
                return sys_msg($GLOBALS['_LANG']['finishing'], 0, $links);
            } else {
                $links[] = ['text' => $GLOBALS['_LANG']['02_attention_list'], 'href' => 'attention_list.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
            }
        } elseif ($_REQUEST['act'] == 'batch_addtolist') {
            $olddate = $_REQUEST['date'];
            $date = $this->timeRepository->getLocalStrtoTime(trim($_REQUEST['date']));
            $pri = (intval($_REQUEST['pri']) == 1) ? 1 : 0;
            $start = empty($_GET['start']) ? 0 : (int)$_GET['start'];

            $count = $this->attentionListManageService->getUserCollectGoodsCount(0, $date);

            if ($count > $start) {
                $query = $this->attentionListManageService->getUserCollectGoodsList(0, $date, $start, 100);

                $template = MailTemplates::where('template_code', 'attention_list')
                    ->where('type', 'template');
                $template = $this->baseRepository->getToArrayFirst($template);

                $i = 0;
                if ($template) {
                    foreach ($query as $rt) {
                        $rt['user_name'] = $rt['get_users']['user_name'] ?? '';
                        $rt['email'] = $rt['get_users']['email'] ?? '';

                        $rt['goods_id'] = $rt['get_goods']['goods_id'] ?? 0;
                        $rt['goods_name'] = $rt['get_goods']['goods_name'] ?? '';

                        $time = $this->timeRepository->getGmTime();

                        $preg_replace = $this->dsc->url() . $this->dscRepository->buildUri('goods', ['gid' => $rt['goods_id']], $rt['user_name']);

                        $this->smarty->assign(['user_name' => $rt['user_name'], 'goods_name' => $rt['goods_name'], 'preg_replace' => $preg_replace]);
                        $content = $this->smarty->fetch("str:$template[template_content]");

                        EmailSendlist::insert([
                            'email' => $rt['email'],
                            'template_id' => $template['template_id'],
                            'email_content' => $content,
                            'pri' => $pri,
                            'last_send' => $time
                        ]);

                        $i++;
                    }
                }

                if ($i == 100) {
                    $start = $start + 100;
                } else {
                    $start = $start + $i;
                }

                $links[] = ['text' => sprintf($GLOBALS['_LANG']['finish_list'], $start), 'href' => "attention_list.php?act=batch_addtolist&date=$olddate&pri=$pri&start=$start"];
                return sys_msg($GLOBALS['_LANG']['finishing'], 0, $links);
            } else {
                $links[] = ['text' => $GLOBALS['_LANG']['02_attention_list'], 'href' => 'attention_list.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
            }
        }
    }
}
