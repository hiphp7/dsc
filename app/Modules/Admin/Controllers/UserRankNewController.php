<?php

namespace App\Modules\Admin\Controllers;

use App\Models\UserRank;
use App\Models\Users;
use App\Repositories\Common\DscRepository;
use App\Services\User\UserRankService;
use App\Services\Common\PluginManageService;
use App\Services\UserRights\RightsCardManageService;
use App\Services\UserRights\UserRightsManageService;
use Illuminate\Http\Request;

/**
 * 会员等级管理程序
 */
class UserRankNewController extends BaseController
{
    protected $userRankService;
    protected $dscRepository;
    protected $userRightsManageService;

    public function __construct(
        DscRepository $dscRepository,
        UserRankService $userRankService,
        UserRightsManageService $userRightsManageService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->userRankService = $userRankService;
        $this->userRightsManageService = $userRightsManageService;
    }

    public function edit(Request $request)
    {
        $this->admin_priv('user_rank');

        $id = $request->input('id', 0);

        if (empty($id)) {
            return $this->message(lang('admin/common.illegal_operate'), '/admin/user_rank.php?act=list', 2);
        }

        if ($request->isMethod('POST')) {
            $min_points = !empty($_POST['min_points']) ? intval($_POST['min_points']) : 0;
            $max_points = 0;
            $special_rank = !empty($_POST['special_rank']) ? intval($_POST['special_rank']) : 0;
            $rank_name = empty($_POST['rank_name']) ? '' : trim($_POST['rank_name']);
            $show_price = isset($_POST['show_price']) ? intval($_POST['show_price']) : 0;

            /* 检查是否存在重名的会员等级 */
            $count = $this->userRankService->checkName($rank_name, $id);
            if ($count) {
                return $this->message(sprintf(lang('admin/user_rank.rank_name_exists'), $rank_name), null, 2);
            }

            /* 特殊等级会员组不判断积分限制 */
            /* 检查下限制有无重复 */
            if (UserRank::where('min_points', $min_points)->where('rank_id', '<>', $id)->where('special_rank', 0)->count()) {
                return $this->message(sprintf(lang('admin/user_rank.integral_min_exists'), $min_points), null, 2);
            }

            $data = [
                'rank_name' => $rank_name,
                'min_points' => $min_points,
                'show_price' => $show_price,
                'max_points' => 0,
                'special_rank' => $special_rank
            ];
            $this->userRankService->updateUserRank($id, $data);

            /* 管理员日志 */
            admin_log($rank_name, 'edit', 'user_rank');
            clear_cache_files();

            return $this->message(lang('admin/common.edit') . lang('admin/common.success'), '/admin/user_rank.php?act=list');

        }

        $rank = $this->userRankService->getRankInfo($id);
        if (empty($rank)) {
            return $this->message(lang('admin/common.illegal_operate'), '/admin/user_rank.php?act=list', 2);
        }

        //绑定的权益列表
        $info = $this->userRankService->userRankRightInfo($id);

        $rights_list = $this->userRankService->transFormRightsList($info);

        $this->assign('rights_list', $rights_list);

        $this->assign('rank', $rank);
        $this->assign('action_link', ['text' => $GLOBALS['_LANG']['05_user_rank_list'], 'href' => '/admin/user_rank.php?act=list']);
        $this->assign('form_action', "update");

        return $this->display();
    }

    /**
     * 删除
     * @param Request $request
     * @param RightsCardManageService $rightsCardManageService
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, RightsCardManageService $rightsCardManageService)
    {
        $this->admin_priv('user_rank');

        $id = $request->input('id', 0);

        //1.4.3 特殊等级不显示
        if (file_exists(MOBILE_DRP) && $id > 0) {
            // 检查会员等级是否绑定关联权益卡 且权益卡能否删除
            $can_delete = $rightsCardManageService->checkCard($id);
            if ($can_delete == false) {
                return response()->json(['error' => 1, 'msg' => lang('admin/users.user_rank_canot_remove')]);
            }
        }

        //等级下有会员不可删除
        if (Users::where('user_rank', $id)->count()) {
            return response()->json(['error' => 1, 'msg' => lang('admin/user_rank.user_rank_has_user')]);
        }

        $res = $this->userRankService->deleteUserRank($id);

        if ($res) {
            $rank_name = UserRank::where('rank_id', $id)->value('rank_name');

            admin_log(addslashes($rank_name), 'remove', 'user_rank');
            clear_cache_files();

            return response()->json(['error' => 0, 'msg' => lang('admin/common.drop') . lang('admin/common.success'), 'url' => '/admin/user_rank.php?act=list']);
        } else {
            return response()->json(['error' => 1, 'msg' => lang('admin/common.drop') . lang('admin/common.fail')]);
        }

    }

    /**
     * 绑定会员权益
     * @param Request $request
     * @param PluginManageService $pluginManageService
     * @param UserRankService $userRankService
     * @return mixed
     */
    public function bind_rights(Request $request, PluginManageService $pluginManageService, UserRankService $userRankService)
    {
        // 权限
        $this->admin_priv('user_rank');

        if ($request->isMethod('POST')) {

            $rank_id = $request->input('rank_id', 0);

            if (empty($rank_id)) {
                return $this->message(lang('admin/common.illegal_operate'), route('admin/user_rank/edit', ['id' => $rank_id]), 2);
            }

            $rights_data = $request->input('rights_id', ''); // 权益id 多选

            if (!empty($rights_data)) {

                // 绑定权益
                $res = $userRankService->bindCardRights($rank_id, $rights_data);
                if ($res) {
                    return $this->message(lang('admin/common.install') . lang('admin/common.success'), route('admin/user_rank/edit', ['id' => $rank_id]));
                }
            }

            return $this->message(lang('admin/common.fail'), route('admin/user_rank/edit', ['id' => $rank_id]), 2);
        }

        // 已绑定权益列表
        $rank_id = $request->input('rank_id', 0);
        $type = $request->input('type', 1); // 权益卡类型：1 普通权益卡

        if (empty($rank_id)) {
            return $this->message(lang('admin/common.illegal_operate'), route('admin/user_rank/edit', ['id' => $rank_id]), 2);
        }

        $bindRightsList = $userRankService->bindCardRightsList($rank_id);

        $bind_arr = [];
        if (!empty($bindRightsList)) {
            foreach ($bindRightsList as $value) {
                $bind_arr[$value['rights_id']] = $value;
            }
        }

        // 已安装权益列表
        $list = $this->userRightsManageService->userRightsList();

        $code_arr = [];
        if (!empty($list)) {
            foreach ($list as $value) {
                $code_arr[$value['code']] = $value;

                if ($type == 1 && ($value['code'] == 'drp' || $value['code'] == 'drp_goods')) {
                    unset($code_arr[$value['code']]);
                }
            }
        }

        $new_plugins = [];
        $plugins = $pluginManageService->readPlugins('UserRights');
        if (!empty($plugins)) {
            foreach ($plugins as $k => $v) {

                $plugins[$k]['name'] = $GLOBALS['_LANG'][$v['code']];
                $plugins[$k]['description'] = $GLOBALS['_LANG'][$v['description']];

                $plugins[$k]['icon'] = stripos($v['icon'], 'assets') !== false ? asset($v['icon']) : $this->dscRepository->getImagePath($v['icon']);

                if ($type == 1 && ($v['code'] == 'drp' || $v['code'] == 'drp_goods')) {
                    unset($plugins[$k]);
                }

                // 数据库中存在，用数据库的数据
                if (isset($code_arr[$v['code']])) {
                    // 已绑定权益 选中状态
                    $rights_id = $code_arr[$v['code']]['id'] ?? 0;
                    if (isset($bind_arr[$rights_id])) {
                        $plugins[$k]['is_checked'] = 1;
                    }

                    //普通会员等级，绑定权益时，不可绑定商品分销和会员卡分销
                    $rights_disableds = ['drp', 'drp_goods'];
                    if (in_array($v['code'], $rights_disableds)) {
                        $plugins[$k]['is_disabled'] = 1;
                    }

                    $new_plugins[] = array_merge($plugins[$k], $code_arr[$v['code']]);
                }
            }
        }

        $group_plugins = [];
        if (!empty($new_plugins)) {
            // 按sort排序
            $collection = collect($new_plugins)->sortBy('sort');

            // 按group分组
            $collection = $collection->mapToGroups(function ($item, $key) {
                return [$item['group'] => $item];
            });

            $group_plugins = $collection->toArray();
        }

        $this->assign('plugins', $group_plugins);
        $this->assign('rank_id', $rank_id);
        return $this->display();
    }

    /**
     * 编辑会员权益
     * @param Request $request
     * @param UserRightsManageService $userRightsManageService
     * @param PluginManageService $pluginManageService
     * @return mixed
     */
    public function edit_rights(Request $request, UserRightsManageService $userRightsManageService, PluginManageService $pluginManageService)
    {
        // 权限
        $this->admin_priv('user_rank');

        if ($request->isMethod('POST')) {

            $id = $request->input('id', 0); // 绑定权益id
            $rank_id = $request->input('rank_id', 0); // 权益卡id

            if (empty($id)) {
                return $this->message(lang('admin/common.illegal_operate'), route('admin/user_rank/edit_rights', ['id' => $id]), 2);
            }

            // 保存权益配置
            $cfg_value = $request->input('cfg_value', []);
            $cfg_name = $request->input('cfg_name', []);
            $cfg_type = $request->input('cfg_type', []);
            $cfg_range = $request->input('cfg_range', []);

            $rights_configure = [];

            //特价权益 同步存储
            if ($cfg_name[0] == 'user_discount') {
                if ($cfg_value[0] > 100 || $cfg_value[0] < 0) {
                    return $this->message(lang('admin/user_rank.notice_discount'), route('admin/user_rank/edit_rights', ['id' => $id]), 2);
                }
            }

            if (!empty($cfg_value) && is_array($cfg_value)) {
                for ($i = 0; $i < count($cfg_value); $i++) {
                    $rights_configure[] = [
                        'name' => trim($cfg_name[$i]),
                        'type' => trim($cfg_type[$i]),
                        'value' => trim($cfg_value[$i]),
                    ];
                }
            }

            $data['rights_configure'] = empty($rights_configure) ? '' : \Opis\Closure\serialize($rights_configure);

            if (!empty($id)) {
                // 编辑会员权益
                $res = $this->userRankService->updateCardRights($id, $data);
                if ($res) {
                    //特价权益 同步存储
                    if ($cfg_name[0] == 'user_discount') {
                        $this->userRankService->updateUserRank($rank_id, ['discount' => $cfg_value[0]]);
                    }
                    return $this->message(lang('admin/common.editor') . lang('admin/common.success'), route('admin/user_rank/edit', ['id' => $rank_id]));
                }

                return $this->message(lang('admin/common.fail'), route('admin/user_rank/edit_rights', ['id' => $id]), 2);
            }
        }

        $id = $request->input('id', 0); // 绑定权益id

        if (empty($id)) {
            return $this->message(lang('admin/common.illegal_operate'), null, 2);
        }

        $bind_info = $this->userRankService->bindCardRightsInfo($id);

        if (!empty($bind_info)) {

            if (isset($bind_info['user_membership_rights']) && !empty($bind_info['user_membership_rights'])) {

                // 绑定权益配置为空 统一调用默认权益配置
                $code = $bind_info['user_membership_rights']['code'];

                $default_info = [];
                if (empty($bind_info['rights_configure']) || is_null($bind_info['rights_configure'])) {
                    // 获取默认权益配置信息
                    $rights_info = $userRightsManageService->userRightsInfo($code);

                } else {
                    // 获取分销权益卡 独立权益配置
                    $bind_info['user_membership_rights']['rights_configure'] = $bind_info['rights_configure'];

                    $rights_info = $bind_info['user_membership_rights'];
                    $rights_info['rights_configure'] = empty($rights_info['rights_configure']) ? '' : unserialize($rights_info['rights_configure']);
                    $rights_info['icon'] = empty($rights_info['icon']) ? '' : ((stripos($rights_info['icon'], 'assets') !== false) ? asset($rights_info['icon']) : $this->dscRepository->getImagePath($rights_info['icon']));
                }

                // 插件实例
                $obj = $pluginManageService->pluginInstance($code, 'UserRights');
                if (!is_null($obj)) {
                    // 插件配置
                    $cfg = $pluginManageService->getPluginConfig($code, 'UserRights', $rights_info);
                    $obj->setPluginInfo($cfg);

                    $default_info = $obj->getPluginInfo();
                }

                $bind_info['user_membership_rights'] = $default_info;
            }
        }

        $this->assign('info', $bind_info);
        $this->assign('id', $id);
        return $this->display();
    }

    /**
     * 删除会员权益
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unbind_rights(Request $request)
    {
        // 权限
        $this->admin_priv('user_rank');

        if ($request->isMethod('POST')) {

            $id = $request->input('id', 0); // 绑定权益id

            if (empty($id)) {
                return response()->json(['error' => 1, 'msg' => lang('admin/common.illegal_operate')]);
            }

            $bind_info = $this->userRankService->bindCardRightsInfo($id);

            $res = $this->userRankService->unbindCardRights($id);

            if ($res) {
                //特价权益 同步存储
                if (isset($bind_info['user_membership_rights']['code']) && $bind_info['user_membership_rights']['code'] == 'discount' && $bind_info['user_rank_id']) {
                    $this->userRankService->updateUserRank($bind_info['user_rank_id'], ['discount' => 100]);
                }
                return response()->json(['error' => 0, 'msg' => lang('admin/common.drop') . lang('admin/common.success')]);
            }

            return response()->json(['error' => 1, 'msg' => lang('admin/common.drop') . lang('admin/common.fail')]);
        }
    }
}
