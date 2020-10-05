<?php

namespace App\Modules\Admin\Controllers;

use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\User\UserRankService;
use App\Services\Common\PluginManageService;
use App\Services\UserRights\UserRightsManageService;
use App\Services\UserRights\UserRightsService;
use Illuminate\Http\Request;


class UserRightsController extends BaseController
{
    protected $userRightsManageService;
    protected $timeRepository;
    protected $dscRepository;

    public function __construct(
        UserRightsManageService $userRightsManageService,
        TimeRepository $timeRepository,
        DscRepository $dscRepository
    )
    {
        $this->userRightsManageService = $userRightsManageService;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
    }

    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 已安装权益列表
     * @param Request $request
     * @param PluginManageService $pluginManageService
     * @return mixed
     */
    public function index(Request $request, PluginManageService $pluginManageService)
    {
        // 管理权限
        $this->admin_priv('user_rights');

        // 数据库
        $list = $this->userRightsManageService->userRightsList();

        $code_arr = [];
        if (!empty($list)) {
            foreach ($list as $value) {
                $code_arr[$value['code']] = $value;
            }
        }

        // 已安装列表
        $new_plugins = [];
        $plugins = $pluginManageService->readPlugins('UserRights');
        if (!empty($plugins)) {
            foreach ($plugins as $k => $v) {

                $plugins[$k]['name'] = $GLOBALS['_LANG'][$v['code']];
                $plugins[$k]['description'] = $GLOBALS['_LANG'][$v['description']];

                $plugins[$k]['icon'] = stripos($v['icon'], 'assets') !== false ? asset($v['icon']) : $this->dscRepository->getImagePath($v['icon']);

                // 数据库中存在，用数据库的数据
                if (isset($code_arr[$v['code']])) {
                    $new_plugins[] = array_merge($plugins[$k], $code_arr[$v['code']]);
                }
            }
        }

        if (!empty($new_plugins)) {
            // 按sort排序
            $collection = collect($new_plugins)->sortBy('sort');

            // 按group分组
            $collection = $collection->mapToGroups(function ($item, $key) {
                return [$item['group'] => $item];
            });

            $new_plugins = $collection->toArray();
        }

        $this->assign('plugins', $new_plugins);
        return $this->display();
    }

    /**
     * 全部权益列表
     * @param Request $request
     * @param PluginManageService $pluginManageService
     * @return mixed
     */
    public function list(Request $request, PluginManageService $pluginManageService)
    {
        // 管理权限
        $this->admin_priv('user_rights');

        // 数据库
        $list = $this->userRightsManageService->userRightsList();

        $code_arr = [];
        if (!empty($list)) {
            foreach ($list as $value) {
                $code_arr[$value['code']] = $value;
            }
        }

        // 所有列表
        $new_plugins = [];
        $plugins = $pluginManageService->readPlugins('UserRights');
        if (!empty($plugins)) {
            foreach ($plugins as $k => $v) {

                $plugins[$k]['name'] = $GLOBALS['_LANG'][$v['code']];
                $plugins[$k]['description'] = $GLOBALS['_LANG'][$v['description']];

                $plugins[$k]['icon'] = stripos($v['icon'], 'assets') !== false ? asset($v['icon']) : $this->dscRepository->getImagePath($v['icon']);

                // 数据库中存在，用数据库的数据
                if (isset($code_arr[$v['code']])) {
                    $new_plugins[] = array_merge($plugins[$k], $code_arr[$v['code']]);
                } else {
                    $new_plugins[] = $plugins[$k];
                }
            }
        }

        if (!empty($new_plugins)) {
            // 按sort排序
            $collection = collect($new_plugins)->sortBy('sort');

            // 按group分组
            $collection = $collection->mapToGroups(function ($item, $key) {
                return [$item['group'] => $item];
            });

            $new_plugins = $collection->toArray();
        }

        $this->assign('plugins', $new_plugins);
        return $this->display();
    }

    /**
     * 安装、编辑权益
     * @param Request $request
     * @param PluginManageService $pluginManageService
     * @return mixed
     */
    public function edit(Request $request, PluginManageService $pluginManageService)
    {
        // 管理权限
        $this->admin_priv('user_rights');

        if ($request->isMethod('POST')) {

            $code = $request->input('code', '');
            $handler = $request->input('handler', ''); // edit\install

            $data = $request->input('data', []); // 基本配置

            if (empty($code) || empty($handler)) {
                return $this->message(lang('admin/users.code_empty'), route('admin/user_rights/index'));
            }

            // icon上传
            $file_path = $request->input('file_path', '');
            $rights_icon = $request->file('rights_icon');
            if ($rights_icon && $rights_icon->isValid()) {
                // 验证文件大小
                if ($rights_icon->getSize() > 2 * 1024 * 1024) {
                    return $this->message(lang('admin/wechat.file_size_limit'), null, 2);
                }
                // 验证文件格式
                if (!in_array($rights_icon->getClientMimeType(), ['image/jpeg', 'image/png'])) {
                    return $this->message(lang('admin/wechat.not_file_type'), null, 2);
                }
                $result = $this->upload('data/attached/rights', true);
                if ($result['error'] > 0) {
                    return $this->message($result['message'], null, 2);
                }
                $data['icon'] = 'data/attached/rights/' . $result['file_name'];
            } else {
                $data['icon'] = $file_path;
            }

            // oss图片处理
            $file_arr = [
                'icon' => $data['icon'],
                'file_path' => $file_path,
            ];
            $file_arr = $this->dscRepository->transformOssFile($file_arr);
            $data['icon'] = $file_arr['icon'];
            $file_path = $file_arr['file_path'];

            // 保存权益配置
            $cfg_value = $request->input('cfg_value', []);
            $cfg_label = $request->input('cfg_label', []);
            $cfg_name = $request->input('cfg_name', []);
            $cfg_type = $request->input('cfg_type', []);
            $cfg_range = $request->input('cfg_range', []);

            $rights_configure = [];

            if (!empty($cfg_value) && is_array($cfg_value)) {
                for ($i = 0; $i < count($cfg_value); $i++) {
                    $rights_configure[] = [
                        'name' => trim($cfg_name[$i]),
                        'type' => trim($cfg_type[$i]),
                        'value' => trim($cfg_value[$i]),
                        'label' => $cfg_label[$i] ?? '',
                    ];
                }
            }

            $data['rights_configure'] = empty($rights_configure) ? '' : \Opis\Closure\serialize($rights_configure);

            if (!empty($handler) && $handler == 'edit') {

                // 删除原图片
                if ($data['icon'] && $file_path != $data['icon']) {
                    $file_path = (stripos($file_path, 'no_image') !== false || stripos($file_path, 'assets') !== false) ? '' : $file_path; // 不删除默认空图片
                    $this->remove($file_path);
                }

                // 编辑
                $res = $this->userRightsManageService->updateUserRights($code, $data);
                if ($res) {
                    return $this->message(lang('admin/common.editor') . lang('admin/common.success'), route('admin/user_rights/list'));
                }
            } elseif (!empty($handler) && $handler == 'install') {
                // 安装
                $res = $this->userRightsManageService->createUserRights($code, $data);
                if ($res) {
                    return $this->message(lang('admin/common.install') . lang('admin/common.success'), route('admin/user_rights/list'));
                }
            }

            return $this->message(lang('admin/common.fail'), route('admin/user_rights/list'));
        }

        // 操作
        $code = $request->input('code', '');
        $handler = $request->input('handler', ''); // edit\install

        if (empty($code)) {
            return $this->message(lang('admin/users.code_empty'), route('admin/user_rights/index'));
        }

        // 获取配置信息
        $info = [];
        if (!empty($handler) && $handler == 'edit') {
            // 获取配置信息
            $info = $this->userRightsManageService->userRightsInfo($code);
        }

        // 插件实例
        $obj = $pluginManageService->pluginInstance($code, 'UserRights');

        if (!is_null($obj)) {
            // 插件配置
            $cfg = $pluginManageService->getPluginConfig($code, 'UserRights', $info);
            $obj->setPluginInfo($cfg);

            return $obj->install();
        }
    }

    /**
     * 卸载权益
     * @param Request $request
     * @param UserRightsService $userRightsService
     * @param UserRankService $userRankService
     * @return \Illuminate\Http\JsonResponse
     */
    public function uninstall(Request $request, UserRightsService $userRightsService, UserRankService $userRankService)
    {
        // 管理权限
        $this->admin_priv('user_rights');

        $code = $request->input('code', '');

        if (empty($code)) {
            return response()->json(['error' => 1, 'msg' => lang('admin/users.code_empty')]);
        }

        $info = $userRightsService->userRightsInfo($code);
        if (empty($info)) {
            return response()->json(['error' => 1, 'msg' => lang('admin/users.code_empty')]);
        }

        //先判断权益是否被关联使用，已关联使用的权益需先解除关联，【该权益有xxxx会员等级正在使用，需删除关联后方可卸载】
        //分销权益卡
        $member_card = $userRightsService->getCardRights($info['id'], 500, null);
        if ($member_card) {
            $member_card_names = '';
            foreach ($member_card as $row) {
                if ($row['user_membership_card']['name']) {
                    $member_card_names .= $row['user_membership_card']['name'] . ',';
                }
            }
            if ($member_card_names) {
                $member_card_names = substr($member_card_names, 0, -1);
                $member_card_names = '[' . $member_card_names . ']';
                return response()->json(['error' => 1, 'msg' => sprintf(lang('admin/users.user_rights_canot_remove'), $member_card_names)]);
            }
        }

        //会员等级
        $user_rank_list = $userRankService->getCardRightsRank($info['id'], 500, null);
        if ($user_rank_list) {
            $user_rank_names = '';
            foreach ($user_rank_list as $row) {
                if ($row['get_user_rank']['rank_name']) {
                    $user_rank_names .= $row['get_user_rank']['rank_name'] . ',';
                }
            }
            if ($user_rank_names) {
                $user_rank_names = substr($user_rank_names, 0, -1);
                $user_rank_names = '[' . $user_rank_names . ']';
                return response()->json(['error' => 1, 'msg' => sprintf(lang('admin/users.user_rights_canot_remove'), $user_rank_names)]);
            }
        }

        // 卸载
        $res = $this->userRightsManageService->uninstallUserRights($code);
        if ($res) {
            return response()->json(['error' => 0, 'msg' => lang('admin/common.uninstall') . lang('admin/common.success')]);
        }

        return response()->json(['error' => 1, 'msg' => lang('admin/common.uninstall') . lang('admin/common.fail')]);
    }
}
