<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Exchange;
use App\Models\EntryCriteria;
use App\Repositories\Common\BaseRepository;
use App\Services\EntryCriteria\EntryCriteriaManageService;

/*
 * 商家店铺等级 标准列表
 */

class EntryCriteriaController extends InitController
{
    protected $baseRepository;
    protected $entryCriteriaManageService;

    public function __construct(
        BaseRepository $baseRepository,
        EntryCriteriaManageService $entryCriteriaManageService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->entryCriteriaManageService = $entryCriteriaManageService;
    }

    public function index()
    {
        /*初始化数据交换对象 */
        $exc = new Exchange($this->dsc->table("entry_criteria"), $this->db, 'id', 'criteria_name');

        if ($_REQUEST['act'] == 'list') {
            admin_priv('seller_grade');//by kong
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['entry_criteria_list']);

            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['seller_garde_list'], 'href' => 'seller_grade.php?act=list']);
            $this->smarty->assign('action_link2', ['text' => $GLOBALS['_LANG']['add_entry_criteria'], 'href' => 'entry_criteria.php?act=add']);

            $parent_id = isset($_REQUEST['parent_id']) ? intval($_REQUEST['parent_id']) : 0;

            $articlecat = $this->entryCriteriaManageService->getCriteriaCatLevel($parent_id);

            foreach ($articlecat as $k => $v) {

                /*获取父级名称*/
                if ($v['parent_id'] > 0) {
                    $articlecat[$k]['parent_name'] = EntryCriteria::where('id', $v['parent_id'])->value('criteria_name');
                }

                switch ($v['type']) {
                    case 'text':
                        $articlecat[$k]['type'] = $GLOBALS['_LANG']['text'];
                        break;
                    case 'select':
                        $articlecat[$k]['type'] = $GLOBALS['_LANG']['select'];
                        break;
                    case 'textarea':
                        $articlecat[$k]['type'] = $GLOBALS['_LANG']['textarea'];
                        break;
                    case 'file':
                        $articlecat[$k]['type'] = $GLOBALS['_LANG']['file'];
                        break;
                    case 'charge':
                        $articlecat[$k]['type'] = $GLOBALS['_LANG']['charge'];
                        break;
                }
            }
            $this->smarty->assign('entry_criteria', $articlecat);
            $this->smarty->assign('parent_id', $parent_id);
            return $this->smarty->display("entry_criteria.dwt");
        } elseif ($_REQUEST['act'] == 'query') {
            admin_priv('seller_grade');//by kong

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['entry_criteria_list']);

            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['seller_garde_list'], 'href' => 'seller_grade.php?act=list']);
            $this->smarty->assign('action_link2', ['text' => $GLOBALS['_LANG']['add_entry_criteria'], 'href' => 'entry_criteria.php?act=add']);

            $parent_id = isset($_REQUEST['parent_id']) ? intval($_REQUEST['parent_id']) : 0;

            $articlecat = $this->entryCriteriaManageService->getCriteriaCatLevel($parent_id);
            foreach ($articlecat as $k => $v) {

                /*获取父级名称*/
                if ($v['parent_id'] > 0) {
                    $articlecat[$k]['parent_name'] = EntryCriteria::where('id', $v['parent_id'])->value('criteria_name');
                }

                switch ($v['type']) {
                    case 'text':
                        $articlecat[$k]['type'] = $GLOBALS['_LANG']['text'];
                        break;
                    case 'select':
                        $articlecat[$k]['type'] = $GLOBALS['_LANG']['select'];
                        break;
                    case 'textarea':
                        $articlecat[$k]['type'] = $GLOBALS['_LANG']['textarea'];
                        break;
                    case 'file':
                        $articlecat[$k]['type'] = $GLOBALS['_LANG']['file'];
                        break;
                    case 'charge':
                        $articlecat[$k]['type'] = $GLOBALS['_LANG']['charge'];
                        break;
                }
            }
            $this->smarty->assign('entry_criteria', $articlecat);
            $this->smarty->assign('parent_id', $parent_id);
            //跳转页面
            return make_json_result($this->smarty->fetch('entry_criteria.dwt'));
        } elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            admin_priv('seller_grade');//by kong

            $id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $parent_id = isset($_REQUEST['parent_id']) && !empty($_REQUEST['parent_id']) ? intval($_REQUEST['parent_id']) : 0;

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_entry_criteria']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['entry_criteria_list'], 'href' => 'entry_criteria.php?act=list']);
            $act = ($_REQUEST['act'] == 'add') ? 'insert' : 'update';
            $this->smarty->assign('act', $act);

            $res = EntryCriteria::where('parent_id', 0);
            $entry_criteria = $this->baseRepository->getToArrayGet($res);

            $this->smarty->assign('criteria', $entry_criteria);

            if ($id || $parent_id) {
                $res = EntryCriteria::whereRaw(1);
                if ($parent_id) {
                    $res = $res->where('id', $parent_id);
                } else {
                    $res = $res->where('id', $id);
                }
                $entry_criteria = $this->baseRepository->getToArrayFirst($res);

                $entry_criteria['option_value'] = explode(',', $entry_criteria['option_value']);
                $this->smarty->assign('entry_criteria', $entry_criteria);
            }

            return $this->smarty->display("entry_criteria_info.dwt");
        } elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            admin_priv('seller_grade');//by kong
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit_entry_criteria']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['entry_criteria_list'], 'href' => 'entry_criteria.php?act=list']);

            //获取所有上级
            $res = EntryCriteria::where('parent_id', 0);
            $entry_criteria = $this->baseRepository->getToArrayGet($res);

            $this->smarty->assign('criteria', $entry_criteria);

            $id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $criteria_name = !empty($_REQUEST['criteria_name']) ? $_REQUEST['criteria_name'] : '';
            $parent_id = !empty($_REQUEST['parent_id']) ? intval($_REQUEST['parent_id']) : 0;
            $charge = !empty($_REQUEST['charge']) ? round($_REQUEST['charge'], 2) : 0.00;
            $type = !empty($_REQUEST['type']) ? $_REQUEST['type'] : '';
            $is_mandatory = !empty($_REQUEST['is_mandatory']) ? $_REQUEST['is_mandatory'] : 0;
            $is_cumulative = !empty($_REQUEST['is_cumulative']) ? $_REQUEST['is_cumulative'] : 0;
            $data_type = !empty($_REQUEST['data_type']) ? intval($_REQUEST['data_type']) : 0;

            $option_value = !empty($_REQUEST['option_value']) ? implode(',', array_unique($_REQUEST['option_value'])) : '';
            if ($_REQUEST['act'] == 'update') {
                /*检查标准是否重复*/
                $is_only = EntryCriteria::where('criteria_name', $criteria_name)->where('id', '<>', $id)->count();
                if ($is_only > 0) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['criteria_name_repeat'], stripslashes($criteria_name)), 1);
                }

                $data = ['criteria_name' => $criteria_name,
                    'parent_id' => $parent_id,
                    'charge' => $charge,
                    'type' => $type,
                    'is_mandatory' => $is_mandatory,
                    'option_value' => $option_value,
                    'is_cumulative' => $is_cumulative,
                    'data_type' => $data_type
                ];

                $res = EntryCriteria::where('id', $id)->update($data);
                if ($res > 0) {
                    $link[0]['text'] = $GLOBALS['_LANG']['bank_list'];
                    $link[0]['href'] = 'entry_criteria.php?act=list';
                    $lang = $GLOBALS['_LANG']['edit_succeed'];
                }
            } elseif ($_REQUEST['act'] == 'insert') {
                /*检查标准是否重复*/
                $is_only = EntryCriteria::where('criteria_name', $criteria_name)->count();
                if ($is_only > 0) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['criteria_name_repeat'], stripslashes($criteria_name)), 1);
                }

                $data = ['criteria_name' => $criteria_name,
                    'parent_id' => $parent_id,
                    'charge' => $charge,
                    'type' => $type,
                    'is_mandatory' => $is_mandatory,
                    'option_value' => $option_value,
                    'is_cumulative' => $is_cumulative,
                    'data_type' => $data_type
                ];

                $res = EntryCriteria::insert($data);
                if ($res > 0) {
                    $link[0]['text'] = $GLOBALS['_LANG']['GO_add'];
                    $link[0]['href'] = 'entry_criteria.php?act=add';

                    $link[1]['text'] = $GLOBALS['_LANG']['bank_list'];
                    $link[1]['href'] = 'entry_criteria.php?act=list';

                    $lang = $GLOBALS['_LANG']['add_succeed'];
                }
            }
            clear_cache_files(); // 清除相关的缓存文件
            return sys_msg($lang, 0, $link);
        } /*删除*/
        elseif ($_REQUEST['act'] == 'remove') {
            $id = intval($_GET['id']);


            /* 删除原来的文件 */
            $count = EntryCriteria::where('parent_id', $id)->count();
            if ($count > 0) {
                /* 还有子分类，不能删除 */
                return make_json_error($GLOBALS['_LANG']['is_fullentry']);
            }
            EntryCriteria::where('id', $id)->delete();
            clear_cache_files(); // 清除相关的缓存文件
            $url = 'entry_criteria.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        } elseif ($_REQUEST['act'] == 'edit_charge') {
            $check_auth = check_authz_json('seller_grade');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $id = intval($_POST['id']);
            $order = json_str_iconv(trim($_POST['val']));

            if ($exc->edit("charge = '$order'", $id)) {
                clear_cache_files();
                return make_json_result(stripslashes($order));
            } else {
                return make_json_error($this->db->error());
            }
        } elseif ($_REQUEST['act'] == 'toggle_show') {
            $check_auth = check_authz_json('seller_grade');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $id = intval($_POST['id']);
            $order = json_str_iconv(trim($_POST['val']));
            if ($exc->edit("is_mandatory = '$order'", $id)) {
                clear_cache_files();
                return make_json_result(stripslashes($order));
            } else {
                return make_json_error($this->db->error());
            }
        }
    }


}
