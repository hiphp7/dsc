<?php

namespace App\Modules\Admin\Controllers;

use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Common\PluginManageService;
use App\Services\UserRights\RightsCardManageService;
use App\Services\UserRights\SyncDataService;
use App\Services\UserRights\UserRightsManageService;
use Illuminate\Http\Request;


/**
 * Class DrpCardController 微分销会员卡
 * @package App\Modules\Admin\Controllers
 */
class DrpCardController extends BaseController
{
    protected $ru_id = 0;

    // 分页数量
    protected $page_num = 1;

    protected $timeRepository;
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        TimeRepository $timeRepository,
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
    }

    protected function initialize()
    {
        parent::initialize();

        // 初始化 每页分页数量
        $this->init_params();
    }

    /**
     * 处理公共参数
     */
    private function init_params()
    {
        $page_num = request()->cookie('page_size');
        $this->page_num = is_null($page_num) ? 10 : $page_num;
        $this->assign('page_num', $this->page_num);
    }

    /**
     * 分销权益卡列表
     * @param Request $request
     * @param RightsCardManageService $rightsCardManageService
     * @return mixed
     */
    public function index(Request $request, RightsCardManageService $rightsCardManageService)
    {
        // 权限
        $this->admin_priv('drpcard_manage');

        $enable = $request->input('enable', 1); // 权益卡状态： 0 关闭、1 开启
        $type = $request->input('type', 2); // 权益卡类型：1 普通权益卡 2 分销权益卡
        $page = $request->input('page', 1);
        $size = $this->page_num;

        $offset = [
            'start' => ($page - 1) * $size,
            'limit' => $size
        ];
        $result = $rightsCardManageService->rightsCardList($type, $enable, $offset);

        $list = $result['list'] ?? [];
        $total_current = $result['total'] ?? 0;
        $this->assign('list', $list);

        // 显示发放中、已停发数量
        if ($enable == 1) {
            $total_another = $rightsCardManageService->rightsCardTotal($type, 0);

            $count = [
                'card_enable_1' => $total_current,
                'card_enable_0' => $total_another
            ];

        } else {
            $total_another = $rightsCardManageService->rightsCardTotal($type, 1);

            $count = [
                'card_enable_1' => $total_another,
                'card_enable_0' => $total_current
            ];
        }
        $this->assign('count', $count);
        // 显示总数量
        $total_all = $rightsCardManageService->rightsCardCount(2);
        $this->assign('total_all', $total_all);
        $this->assign('enable', $enable);
        return $this->display();
    }

    /**
     * 添加分销权益卡
     * @param Request $request
     * @param RightsCardManageService $rightsCardManageService
     * @return mixed
     */
    public function add(Request $request, RightsCardManageService $rightsCardManageService)
    {
        // 权限
        $this->admin_priv('drpcard_manage');

        if ($request->isMethod('POST')) {

            $data = $request->input('data');

            // 验证数据
            if (empty($data['name'])) {
                return $this->message(lang('admin/drpcard.drp_card_name_not_null'), null, 2);
            }

            $count = $rightsCardManageService->rightsCardCount(2);
            if ($count >= 100) {
                return $this->message(lang('admin/drpcard.drp_card_limit'), null, 2);
            }

            // 背景
            $background = $request->input('background', 0); // 0 颜色、1 背景图

            if ($background == 0) {
                $background_color = $request->input('background_color', '');
                $data['background_color'] = $background_color;
            } elseif ($background == 1) {
                // 背景图上传
                $file_path = $request->input('file_path', '');
                $background_img = $request->file('background_img');
                if ($background_img && $background_img->isValid()) {
                    // 验证文件大小
                    if ($background_img->getSize() > 2 * 1024 * 1024) {
                        return $this->message(lang('admin/wechat.file_size_limit'), null, 2);
                    }
                    // 验证文件格式
                    if (!in_array($background_img->getClientMimeType(), ['image/jpeg', 'image/png'])) {
                        return $this->message(lang('admin/wechat.not_file_type'), null, 2);
                    }
                    $result = $this->upload('data/attached/rights', true);
                    if ($result['error'] > 0) {
                        return $this->message($result['message'], null, 2);
                    }
                    $data['background_img'] = 'data/attached/rights/' . $result['file_name'];
                } else {
                    $data['background_img'] = $file_path;
                }

                // oss图片处理
                $file_arr = [
                    'background_img' => $data['background_img'],
                    'file_path' => $file_path,
                ];
                $file_arr = $this->dscRepository->transformOssFile($file_arr);
                $data['background_img'] = $file_arr['background_img'];
            }

            // 卡有效期
            if (isset($data['expiry_type']) && !empty($data['expiry_type'])) {
                // 开始与结束时间
                if ($data['expiry_type'] == 'timespan') {
                    $expiry_date = $request->input('expiry_date', '');

                    if (!empty($expiry_date)) {
                        $multiplied = collect($expiry_date)->map(function ($item, $key) {
                            return $this->timeRepository->getLocalStrtoTime($item);
                        });

                        $expiry_date = $multiplied->all();

                        $expiry_date = !empty($expiry_date) && is_array($expiry_date) ? implode(',', $expiry_date) : '';
                    }

                    $data['expiry_date'] = $expiry_date;
                } elseif ($data['expiry_type'] == 'forever') {
                    $data['expiry_date'] = '';
                }
            }

            // 领取类型配置 多选框
            $receive_type = $request->input('receive_type', []);
            $input_value = $request->input('input_value', []);
            $receive_value = [];
            if (!empty($receive_type) && is_array($receive_type)) {
                foreach ($receive_type as $i => $value) {
                    $receive_value[] = [
                        'type' => $value,
                        'value' => $input_value[$value] ?? ''
                    ];
                }
            }
            $data['receive_value'] = empty($receive_value) ? '' : \Opis\Closure\serialize($receive_value);

            // 检查名称是否重复
            $count = $rightsCardManageService->checkName($data['name']);
            if ($count) {
                return $this->message(lang('admin/drpcard.drp_card_name_repeat'), null, 2);
            }
            // 添加
            $membership_card_id = $rightsCardManageService->createRightsCard($data);
            if ($membership_card_id) {
                // 保存指定购买商品与分销权益卡绑定
                $rightsCardManageService->updateGoods($membership_card_id, $receive_value);

                // 自动生成一个特殊会员等级
                $rank_id = $rightsCardManageService->addUserRank($data['name']);

                if ($rank_id) {
                    $updata = [
                        'user_rank_id' => $rank_id
                    ];
                    $rightsCardManageService->updateRightsCard($membership_card_id, $updata);

                    return $this->message(lang('admin/common.add') . lang('admin/common.success'), route('admin/drp_card/index'));
                }
            }

            return $this->message(lang('admin/common.fail'), route('admin/drp_card/index'));
        }

        $type = $request->input('type', 2); // 权益卡类型：1 普通 2 分销

        // 默认领取设置类型 checkbox
        $receive_type_checkbox = [
            ['type' => 'buy', 'value' => ''],
            ['type' => 'goods', 'value' => ''],
            ['type' => 'order', 'value' => ''],
            ['type' => 'integral', 'value' => ''],
            ['type' => 'free', 'value' => '']
        ];
        $this->assign('receive_type_checkbox', $receive_type_checkbox);
        $this->assign('type', $type);
        return $this->display();
    }

    /**
     * 编辑分销权益卡
     * @param Request $request
     * @param RightsCardManageService $rightsCardManageService
     * @return mixed
     */
    public function edit(Request $request, RightsCardManageService $rightsCardManageService)
    {
        // 权限
        $this->admin_priv('drpcard_manage');

        if ($request->isMethod('POST')) {

            $membership_card_id = $request->input('id', 0); // 权益卡ID
            $data = $request->input('data');

            // 验证数据
            if (empty($data['name'])) {
                return $this->message(lang('admin/drpcard.drp_card_name_not_null'), null, 2);
            }

            // 背景
            $background = $request->input('background', 0); // 0 颜色、1 背景图

            if ($background == 0) {
                $background_color = $request->input('background_color', '');
                $data['background_color'] = $background_color;
            } elseif ($background == 1) {
                // 背景图上传
                $file_path = $request->input('file_path', '');
                $background_img = $request->file('background_img');
                if ($background_img && $background_img->isValid()) {
                    // 验证文件大小
                    if ($background_img->getSize() > 2 * 1024 * 1024) {
                        return $this->message(lang('admin/wechat.file_size_limit'), null, 2);
                    }
                    // 验证文件格式
                    if (!in_array($background_img->getClientMimeType(), ['image/jpeg', 'image/png'])) {
                        return $this->message(lang('admin/wechat.not_file_type'), null, 2);
                    }
                    $result = $this->upload('data/attached/rights', true);
                    if ($result['error'] > 0) {
                        return $this->message($result['message'], null, 2);
                    }
                    $data['background_img'] = 'data/attached/rights/' . $result['file_name'];
                } else {
                    $data['background_img'] = $file_path;
                }

                // oss图片处理
                $file_arr = [
                    'background_img' => $data['background_img'],
                    'file_path' => $file_path,
                ];
                $file_arr = $this->dscRepository->transformOssFile($file_arr);
                $data['background_img'] = $file_arr['background_img'];
                $file_path = $file_arr['file_path'];
            }

            // 卡有效期
            if (isset($data['expiry_type']) && !empty($data['expiry_type'])) {
                // 开始与结束时间
                if ($data['expiry_type'] == 'timespan') {
                    $expiry_date = $request->input('expiry_date', '');

                    if (!empty($expiry_date)) {
                        $multiplied = collect($expiry_date)->map(function ($item, $key) {
                            return $this->timeRepository->getLocalStrtoTime($item);
                        });

                        $expiry_date = $multiplied->all();

                        $expiry_date = !empty($expiry_date) && is_array($expiry_date) ? implode(',', $expiry_date) : '';
                    }

                    $data['expiry_date'] = $expiry_date;
                } elseif ($data['expiry_type'] == 'forever') {
                    $data['expiry_date'] = '';
                }
            }

            // 领取类型配置 多选框
            $receive_type = $request->input('receive_type', []);
            $input_value = $request->input('input_value', []);
            $input_value_old = $request->input('input_value_old', []);
            $receive_value = [];
            if (!empty($receive_type) && is_array($receive_type)) {
                foreach ($receive_type as $i => $value) {
                    $receive_value[] = [
                        'type' => $value,
                        'value' => $input_value[$value] ?? ''
                    ];
                }
            }
            $data['receive_value'] = empty($receive_value) ? '' : \Opis\Closure\serialize($receive_value);

            if (!empty($membership_card_id)) {
                // 检查名称是否重复
                $count = $rightsCardManageService->checkName($data['name'], $membership_card_id);
                if ($count) {
                    return $this->message(lang('admin/drpcard.drp_card_name_repeat'), null, 2);
                }

                // 删除原图片
                if (isset($data['background_img']) && isset($file_path) && $data['background_img'] && $file_path != $data['background_img']) {
                    $file_path = stripos($file_path, 'no_image') !== false ? '' : $file_path; // 不删除默认空图片
                    $this->remove($file_path);
                }

                // 修改
                $res = $rightsCardManageService->updateRightsCard($membership_card_id, $data);

                if ($res) {
                    //更新关联的等级名称
                    $rightsCardManageService->updateUserRankByCardId($membership_card_id, ['rank_name' => $data['name']]);

                    // 保存指定购买商品与分销权益卡绑定（取消）
                    $rightsCardManageService->updateGoods($membership_card_id, $receive_value, $input_value_old);

                    return $this->message(lang('admin/common.edit') . lang('admin/common.success'), route('admin/drp_card/index'));
                }
            }

            return $this->message(lang('admin/common.fail'), route('admin/drp_card/index'));
        }

        // 查询会员卡权益
        $id = $request->input('id', 0);
        $type = $request->input('type', 2); // 权益卡类型：1 普通权益卡 2 分销权益卡
        $enable = $request->input('enable', 1); // 权益卡状态： 0 关闭、1 开启

        $info = $rightsCardManageService->membershipCardInfo($id);

        $info = $rightsCardManageService->transFormRightsCardInfo($info);

        // 绑定的权益列表
        $rights_list = $rightsCardManageService->transFormCardRightsList($info);

        // 默认领取设置类型 checkbox
        $receive_type_checkbox = [
            ['type' => 'buy', 'value' => ''],
            ['type' => 'goods', 'value' => ''],
            ['type' => 'order', 'value' => ''],
            ['type' => 'integral', 'value' => ''],
            ['type' => 'free', 'value' => ''],
        ];

        // 已选择 checkbox 选中状态
        $type_arr = [];
        $new_receive_type_checkbox = [];
        if (!empty($info) && !empty($info['receive_value'])) {
            foreach ($info['receive_value'] as $value) {
                $type_arr[$value['type']] = $value;
            }
        }
        foreach ($receive_type_checkbox as $k => $v) {
            // 数据库已选择 checkbox 选中状态
            if (isset($type_arr[$v['type']])) {
                $receive_type_checkbox[$k]['is_checked'] = 1;
                $new_receive_type_checkbox[] = array_merge($receive_type_checkbox[$k], $type_arr[$v['type']]);
            } else {
                $new_receive_type_checkbox[] = $receive_type_checkbox[$k];
            }
        }

        $this->assign('receive_type_checkbox', $new_receive_type_checkbox);

        $this->assign('info', $info);
        $this->assign('rights_list', $rights_list);
        $this->assign('type', $type);

        // 查看
        if ($enable == 0) {
            return $this->display('admin/drpcard.info');
        }
        return $this->display();
    }

    /**
     * 删除分销权益卡
     * @param Request $request
     * @param RightsCardManageService $rightsCardManageService
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, RightsCardManageService $rightsCardManageService)
    {
        // 权限
        $this->admin_priv('drpcard_manage');

        if ($request->isMethod('POST')) {

            $id = $request->input('id', 0); // 权益卡id

            if (empty($id)) {
                return response()->json(['error' => 1, 'msg' => lang('admin/drpcard.bind_id_empty')]);
            }

            // 删除分销权益卡 已关联会员的卡不可删除  （含未付款）
            $can_delete = $rightsCardManageService->checkRightsCard($id);
            if ($can_delete == false) {
                return response()->json(['error' => 1, 'msg' => lang('admin/drpcard.cannot_drop_card')]);
            }

            // 删除关联的特殊会员等级
            $rank_id = $rightsCardManageService->getCardRankId($id);

            $res = $rightsCardManageService->deleteRightsCard($id);
            if ($res) {
                // 解除会员权益绑定
                $rightsCardManageService->deleteCardRightsByCardId($id);

                if ($rank_id) {
                    $rightsCardManageService->deleteUserRank($rank_id);
                }

                return response()->json(['error' => 0, 'msg' => lang('admin/common.drop') . lang('admin/common.success'), 'url' => route('admin/drp_card/index')]);
            }

            return response()->json(['error' => 1, 'msg' => lang('admin/common.drop') . lang('admin/common.fail')]);
        }
    }

    /**
     * 禁用分销权益卡
     * @param Request $request
     * @param RightsCardManageService $rightsCardManageService
     * @return \Illuminate\Http\JsonResponse
     */
    public function disabled(Request $request, RightsCardManageService $rightsCardManageService)
    {
        // 权限
        $this->admin_priv('drpcard_manage');

        if ($request->isMethod('POST')) {

            $id = $request->input('id', 0); // 权益卡id

            if (empty($id)) {
                return response()->json(['error' => 1, 'msg' => lang('admin/drpcard.bind_id_empty')]);
            }

            // 0 禁用 1 开启
            $data = [
                'enable' => 0
            ];
            $res = $rightsCardManageService->updateRightsCard($id, $data);
            if ($res) {
                return response()->json(['error' => 0, 'msg' => lang('admin/common.handler') . lang('admin/common.success'), 'url' => route('admin/drp_card/index')]);
            }

            return response()->json(['error' => 1, 'msg' => lang('admin/common.handler') . lang('admin/common.fail')]);
        }
    }

    /**
     * 删除img图片
     * @param Request $request
     * @param RightsCardManageService $rightsCardManageService
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove_img(Request $request, RightsCardManageService $rightsCardManageService)
    {
        $membership_card_id = $request->input('id', 0); // 权益卡ID

        $file_path = $request->input('file_path', ''); // 文件相对路径 data/filename

        if (empty($file_path)) {
            return response()->json(['error' => 1, 'msg' => lang('admin/common.fail')]);
        }

        // oss图片处理
        $file_arr = [
            'file_path' => $file_path,
        ];
        $file_arr = $this->dscRepository->transformOssFile($file_arr);
        $file_path = $file_arr['file_path'];

        // 删除图片
        $file_path = (stripos($file_path, 'no_image') !== false || stripos($file_path, 'assets') !== false) ? '' : $file_path; // 不删除默认空图片
        $this->remove($file_path);

        // 修改
        $rightsCardManageService->updateRightsCard($membership_card_id, ['background_img' => '']);

        return response()->json(['error' => 0, 'msg' => lang('admin/common.success')]);
    }

    /**
     * 搜索选择商品
     * @param Request $request
     * @param RightsCardManageService $rightsCardManageService
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|string
     */
    public function select_goods(Request $request, RightsCardManageService $rightsCardManageService)
    {
        // 权限
        $this->admin_priv('drpcard_manage');

        load_helper('mobile');

        // 搜索
        $keywords = $request->input('keyword', '');

        $cat_id = $request->input('category_id', 0);
        $brand_id = $request->input('brand_id', 0);

        // 已选择商品id
        $select_goods_id = $request->input('select_goods_id', '');

        // 当前分销权益卡id
        $membership_card_id = $request->input('membership_card_id', 0);

        $this->page_num = 20;

        // 分页
        $filter['cat_id'] = $cat_id;
        $filter['brand_id'] = $brand_id;
        $filter['keyword'] = $keywords;
        $filter['select_goods_id'] = $select_goods_id;
        $filter['membership_card_id'] = $membership_card_id;
        $offset = $this->pageLimit(route('admin/drp_card/select_goods', $filter), $this->page_num);

        $result = $rightsCardManageService->goodsListSearch($keywords, $cat_id, $brand_id, $offset, $filter);

        $total = $result['total'] ?? 0;
        $goods_list = $result['list'] ?? [];

        $this->assign('goods', $goods_list);

        $filter = set_default_filter_new(); //设置默认 分类，品牌列表 筛选

        $this->assign('filter_category_level', 1); //分类等级 默认1
        $this->assign('filter_category_navigation', $filter['filter_category_navigation']);
        $this->assign('filter_category_list', $filter['filter_category_list']);
        $this->assign('filter_brand_list', $filter['filter_brand_list']);

        $this->assign('page', $this->pageShow($total));
        $this->assign('page_num', $this->page_num);
        $this->assign('page_title', lang('admin/drpcard.select_goods_menu'));
        return $this->display();
    }

    /**
     * 返回商品列表
     * @param Request $request
     * @param RightsCardManageService $rightsCardManageService
     * @return \Illuminate\Http\JsonResponse
     */
    public function get_goods(Request $request, RightsCardManageService $rightsCardManageService)
    {
        $goods_id_string = $request->input('goods_id', ''); // 商品ID 多个,分隔

        if (empty($goods_id_string)) {
            return response()->json(['error' => 1]);
        }

        $list = $rightsCardManageService->getGoods($goods_id_string);

        return response()->json(['error' => 0, 'list' => $list]);
    }

    /**
     * 同步历史数据
     * @param Request $request
     * @param SyncDataService $syncDataService
     * @return \Illuminate\Http\JsonResponse
     */
    public function sync(Request $request, SyncDataService $syncDataService)
    {
        // 权限
        $this->admin_priv('drpcard_manage');

        $res = $syncDataService->syncData();
        if ($res) {
            return response()->json(['error' => 0, 'msg' => lang('admin/drpcard.sync_ok'), 'url' => route('admin/drp_card/index')]);
        }

        return response()->json(['error' => 1, 'msg' => lang('admin/common.fail')]);
    }

    /**
     * 绑定会员权益
     * @param Request $request
     * @param RightsCardManageService $rightsCardManageService
     * @param UserRightsManageService $userRightsManageService
     * @param PluginManageService $pluginManageService
     * @return mixed
     */
    public function bind_rights(Request $request, RightsCardManageService $rightsCardManageService, UserRightsManageService $userRightsManageService, PluginManageService $pluginManageService)
    {
        // 权限
        $this->admin_priv('drpcard_manage');

        if ($request->isMethod('POST')) {

            $membership_card_id = $request->input('membership_card_id', 0);

            if (empty($membership_card_id)) {
                return $this->message(lang('admin/drpcard.membership_card_id_empty'), route('admin/drp_card/edit', ['id' => $membership_card_id]));
            }

            $rights_data = $request->input('rights_id', ''); // 权益id 多选

            if (!empty($rights_data)) {

                // 绑定权益
                $res = $rightsCardManageService->bindCardRights($membership_card_id, $rights_data);
                if ($res) {
                    return $this->message(lang('admin/common.install') . lang('admin/common.success'), route('admin/drp_card/edit', ['id' => $membership_card_id]));
                }
            }

            return $this->message(lang('admin/common.fail'), route('admin/drp_card/edit', ['id' => $membership_card_id]));
        }

        // 已绑定权益列表
        $membership_card_id = $request->input('membership_card_id', 0);
        $type = $request->input('type', 2); // 权益卡类型：1 普通权益卡 2 分销权益卡

        if (empty($membership_card_id)) {
            return $this->message(lang('admin/drpcard.membership_card_id_empty'), route('admin/drp_card/edit', ['id' => $membership_card_id]));
        }

        $bindRightsList = $rightsCardManageService->bindCardRightsList($membership_card_id);

        $bind_arr = [];
        if (!empty($bindRightsList)) {
            foreach ($bindRightsList as $value) {
                $bind_arr[$value['rights_id']] = $value;
            }
        }

        // 已安装权益列表
        $list = $userRightsManageService->userRightsList();

        $code_arr = [];
        if (!empty($list)) {
            foreach ($list as $value) {
                $code_arr[$value['code']] = $value;

                if ($type == 2 && ($value['code'] == 'register' || $value['code'] == 'upgrade')) {
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

                if ($type == 2 && ($v['code'] == 'register' || $v['code'] == 'upgrade')) {
                    unset($plugins[$k]);
                }

                // 数据库中存在，用数据库的数据
                if (isset($code_arr[$v['code']])) {
                    // 已绑定权益 选中状态
                    $rights_id = $code_arr[$v['code']]['id'] ?? 0;
                    if (isset($bind_arr[$rights_id])) {
                        $plugins[$k]['is_checked'] = 1;
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
        $this->assign('membership_card_id', $membership_card_id);
        return $this->display('admin.drpcard.bindrights');
    }

    /**
     * 编辑会员权益
     * @param Request $request
     * @param RightsCardManageService $rightsCardManageService
     * @param UserRightsManageService $userRightsManageService
     * @param PluginManageService $pluginManageService
     * @return mixed
     */
    public function edit_rights(Request $request, RightsCardManageService $rightsCardManageService, UserRightsManageService $userRightsManageService, PluginManageService $pluginManageService)
    {
        // 权限
        $this->admin_priv('drpcard_manage');

        if ($request->isMethod('POST')) {

            $id = $request->input('id', 0); // 绑定权益id
            $membership_card_id = $request->input('membership_card_id', 0); // 权益卡id

            if (empty($id)) {
                return $this->message(lang('admin/drpcard.id_empty'), route('admin/drp_card/edit', ['id' => $membership_card_id]));
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
                    return $this->message(lang('admin/user_rank.notice_discount'), route('admin/drp_card/edit_rights', ['id' => $id]), 2);
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
                $res = $rightsCardManageService->updateCardRights($id, $data);
                if ($res) {
                    //特价权益 同步存储
                    if ($cfg_name[0] == 'user_discount') {
                        $rightsCardManageService->updateUserRankByRightId($id, ['discount' => $cfg_value[0]]);
                    }
                    return $this->message(lang('admin/common.editor') . lang('admin/common.success'), route('admin/drp_card/edit', ['id' => $membership_card_id]));
                }

                return $this->message(lang('admin/common.fail'), route('admin/drp_card/edit', ['id' => $membership_card_id]));
            }
        }

        $id = $request->input('id', 0); // 绑定权益id

        if (empty($id)) {
            return $this->message(lang('admin/drpcard.id_empty'));
        }

        $bind_info = $rightsCardManageService->bindCardRightsInfo($id);

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
        return $this->display('admin.drpcard.editrights');
    }

    /**
     * 解除绑定会员权益
     * @param Request $request
     * @param RightsCardManageService $rightsCardManageService
     * @return \Illuminate\Http\JsonResponse
     */
    public function unbind_rights(Request $request, RightsCardManageService $rightsCardManageService)
    {
        // 权限
        $this->admin_priv('drpcard_manage');

        if ($request->isMethod('POST')) {

            $id = $request->input('id', 0); // 绑定权益id

            if (empty($id)) {
                return response()->json(['error' => 1, 'msg' => lang('admin/drpcard.bind_id_empty')]);
            }

            $bind_info = $rightsCardManageService->bindCardRightsInfo($id);
            //特价权益 同步存储
            if (isset($bind_info['user_membership_rights']['code']) && $bind_info['user_membership_rights']['code'] == 'discount') {
                $rightsCardManageService->updateUserRankByRightId($id, ['discount' => 100]);
            }
            $res = $rightsCardManageService->unbindCardRights($id);

            if ($res) {
                return response()->json(['error' => 0, 'msg' => lang('admin/common.drop') . lang('admin/common.success')]);
            }

            return response()->json(['error' => 1, 'msg' => lang('admin/common.drop') . lang('admin/common.fail')]);
        }
    }
}
