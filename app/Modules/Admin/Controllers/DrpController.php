<?php

namespace App\Modules\Admin\Controllers;

use App\Custom\Distribute\Services\DistributeManageService;
use App\Models\DrpShop;
use App\Models\UserMembershipCard;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Common\OfficeService;
use App\Services\Drp\DrpConfigService;
use App\Services\Drp\DrpManageService;
use App\Services\Drp\DrpService;
use App\Services\Drp\DrpShopService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\UserRights\RightsCardManageService;
use Illuminate\Http\Request;

/**
 * Class DrpController
 * @package App\Modules\Admin\Controllers
 */
class DrpController extends BaseController
{
    protected $ru_id = 0;

    // 分页数量
    protected $page_num = 10;

    protected $timeRepository;
    protected $baseRepository;
    protected $dscRepository;
    protected $merchantCommonService;
    protected $drpManageService;
    protected $config;

    public function __construct(
        TimeRepository $timeRepository,
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService,
        DrpManageService $drpManageService
    )
    {
        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->drpManageService = $drpManageService;
        $this->config = $this->dscRepository->dscConfig();
    }

    protected function initialize()
    {
        parent::initialize();

        L(lang('admin/drp'));
        $this->assign('lang', L());

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
     * 修改分页
     */
    public function index()
    {
        if (request()->isMethod('POST')) {
            //修改每页数量
            $page_num = request()->has('page_num') ? request()->input('page_num') : 0;
            if ($page_num > 0) {
                cookie()->queue('page_size', $page_num, 24 * 60 * 30);
                return response()->json(['status' => 1]);
            }
        }
    }

    /**
     * 分销设置
     * @param Request $request
     * @param DrpConfigService $drpConfigService
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function config(Request $request, DrpConfigService $drpConfigService)
    {
        // 分销设置权限
        $this->admin_priv('drp_config');

        if ($request->isMethod('POST')) {
            $group = $request->input('group', ''); // 分组

            $data = $request->input('data');
            if (empty($data)) {
                return $this->message(lang('admin/drp.request_error'), null, 2);
            }

            $drpConfigService->updateDrpAllConfig($data);

            // 清除缓存
            cache()->forget('drp_config');

            return redirect()->route('admin/drp/config', ['group' => $group]);
        }

        $group = $request->input('group', ''); // 分组

        $config_list = $drpConfigService->getDrpConfigList('', $group);

        if (!empty($group) && $group == 'show') {
            $default_list = [];
            $content_list = [];
            if (!empty($config_list)) {
                foreach ($config_list as $key => $value) {
                    if ($value['type'] == 'radio' || $value['type'] == 'select') {
                        $range_list = explode(',', $value['store_range']);

                        if (!empty($range_list)) {
                            foreach ($range_list as $k => $range) {

                                if ($value['code'] == 'count_commission') {
                                    // 自定义状态
                                    if ($range == 0) {
                                        $range_list[$k] = lang('admin/drp.week');
                                    } elseif ($range == 1) {
                                        $range_list[$k] = lang('admin/drp.month');
                                    } elseif ($range == 2) {
                                        $range_list[$k] = lang('admin/drp.year');
                                    }
                                } else {
                                    // 默认状态 启用与禁用
                                    if ($range == 0) {
                                        $range_list[$k] = lang('admin/common.disabled');
                                    } elseif ($range == 1) {
                                        $range_list[$k] = lang('admin/common.enabled');
                                    }
                                }
                            }

                            // 从大到小 按键名逆序排序
                            krsort($range_list);
                            $value['range_list'] = $range_list;
                        }
                    }

                    if ($value['type'] == 'text' || $value['type'] == 'textarea') {
                        $content_list[] = $value;
                    } else {
                        $default_list[] = $value;
                    }
                }
            }
            $this->assign('list', $default_list); // 默认显示
            $this->assign('content_list', $content_list); // 自定义内容
            $this->assign('group', $group);
            return $this->display('admin.drp.drpshowconfig');
        } else {
            if (!empty($config_list)) {
                foreach ($config_list as $key => $value) {
                    if ($value['type'] == 'radio' || $value['type'] == 'select') {
                        $range_list = explode(',', $value['store_range']);

                        if (!empty($range_list)) {
                            foreach ($range_list as $k => $range) {

                                if ($value['code'] == 'drp_affiliate_mode' || $value['code'] == 'isdistribution') {
                                    // 自定义状态
                                    $range_list[$k] = lang('admin/drp.radio_' . $range . '_' . $value['code']);
                                    // 自定义提示信息
                                    $value['warning'] = '';
                                } else {
                                    // 默认状态 启用与禁用
                                    if ($range == 0) {
                                        $range_list[$k] = lang('admin/common.disabled');
                                    } elseif ($range == 1) {
                                        $range_list[$k] = lang('admin/common.enabled');
                                    }
                                }
                            }

                            // 从大到小 按键名逆序排序
                            krsort($range_list);
                            $value['range_list'] = $range_list;
                        }
                    }

                    $config_list[$key] = $value;
                }
            }
        }

        $this->assign('list', $config_list);
        $this->assign('group', $group);
        return $this->display();
    }

    /**
     * 结算规则设置
     * @param Request $request
     * @param DrpConfigService $drpConfigService
     * @return mixed
     */
    public function drp_scale_config(Request $request, DrpConfigService $drpConfigService)
    {
        // 分销设置权限
        $this->admin_priv('drp_config');

        if ($request->isMethod('POST')) {

            $data = $request->input('data');
            if (empty($data)) {
                return $this->message(lang('admin/drp.data_not_null'), null, 2);
            }

            // 验证
            if (isset($data['settlement_time']) && $data['settlement_time'] < 7) {
                return $this->message(lang('admin/drp.time_not_less_than_seven'), null, 2);
            }

            $drpConfigService->updateDrpAllConfig($data);

            // 清除缓存
            cache()->forget('drp_config');

            return redirect()->route('admin/drp/drp_scale_config');
        }

        // 结算分组
        $group = $request->input('group', 'scale'); // 分组

        $list = $drpConfigService->getDrpConfigList('', $group);

        $settlement_rules = [];
        $withdraw_list = [];
        if (!empty($list)) {
            foreach ($list as $k => $value) {
                if (stripos($value['code'], 'settlement') !== false) {
                    $settlement_rules[] = $value;
                } else {
                    $withdraw_list[] = $value;
                }
            }
        }

        $this->assign('settlement_rules', $settlement_rules); // 结算规则
        $this->assign('withdraw_list', $withdraw_list); // 提现设置
        $this->assign('group', $group);
        return $this->display();
    }

    /**
     * 名片二维码设置
     * @param Request $request
     * @param DrpConfigService $drpConfigService
     * @return mixed
     */
    public function drp_set_qrcode(Request $request, DrpConfigService $drpConfigService)
    {
        if ($request->isMethod('POST')) {

            $data = $request->input('data');
            $pic_path = html_in($request->input('file_path', ''));
            $pic_path = ltrim($pic_path, '/');

            // 验证
            if (empty($data)) {
                return response()->json(['error' => 1, 'msg' => lang('admin/drp.data_not_null')]);
            }

            if (!empty($data['description']) && strlen($data['description']) > 100) {
                return response()->json(['error' => 1, 'msg' => lang('admin/drp.literal_excess')]);
            }

            $pic_path = $this->dscRepository->editUploadImage($pic_path);

            // 判断图片宽高
            if (config('shop.open_oss') == 0 && !empty($pic_path)) {
                if (strtolower(substr($pic_path, 0, 4)) == 'http') {
                    $pic_file_path = $pic_path;
                } else {
                    // 默认背景图
                    if (stripos($pic_path, 'drp_bg.png') !== false) {
                        $pic_file_path = public_path('img/drp_bg.png');
                    } else {
                        $pic_file_path = storage_public($pic_path);
                    }
                }
                $img_info = getimagesize($pic_file_path);
                if ($img_info[0] != 640 || $img_info[1] != 1136) {
                    return response()->json(['error' => 1, 'msg' => lang('admin/drp.img_excess')]);
                }
            }
            $file = $request->file('pic');
            $zipfile = $request->file('zip_pic');

            // 如果有 则处理压缩后上传的图片
            if ($file && $zipfile) {
                $file = $zipfile;
                unset($zipfile);
            }
            // 处理上传图片

            if ($file && $file->isValid()) {
                // 验证文件格式
                if (!in_array($file->getClientMimeType(), ['image/jpeg', 'image/png'])) {
                    return response()->json(['error' => 1, 'msg' => L('not_file_type')]);
                }
                // 验证文件大小
                if ($file->getSize() > 2 * 1024 * 1024) {
                    return response()->json(['error' => 1, 'msg' => L('file_size_limit')]);
                }

                $result = $this->upload('data/attached/qrcode/themes', true);
                if ($result['error'] > 0) {
                    return response()->json(['error' => 1, 'msg' => $result['message']]);
                }

                $data['file'] = 'data/attached/qrcode/themes/' . $result['file_name'];

                // 下载OSS图片到本地
                if (config('shop.open_oss') == 1 && !file_exists(storage_public($data['file']))) {
                    $this->BatchDownloadOss(['0' => $data['file']]);
                }
            } else {
                $data['file'] = $pic_path;
            }

            if (empty($data['file'])) {
                $json_result = ['error' => 0, 'msg' => lang('admin/wechat.please_upload')];
                return response()->json($json_result);
            }

            // oss图片处理
            $file_arr = [
                'file' => $data['file'],
                'pic_path' => $pic_path,
            ];
            $file_arr = $this->dscRepository->transformOssFile($file_arr);

            $data['file'] = $file_arr['file'];
            $pic_path = $file_arr['pic_path'];

            // 删除原图片
            if ($data['file'] && $pic_path != $data['file']) {
                $pic_path = (stripos($pic_path, 'drp_bg') == false) ? $pic_path : ''; // 不删除默认背景图
                $this->remove($pic_path);
            }

            $drpConfigService->updateDrpQrcodeConfig($data);

            // 清除缓存
            cache()->forget('drp_config');

            return response()->json(['error' => 0, 'msg' => lang('admin/drp.edit_success')]);
        }


        // 显示
        $info = $drpConfigService->getQrcodeConfig();
        if (!empty($info)) {
            $info['backbround'] = $this->dscRepository->getImagePath($info['backbround']);

            // 图片不存在或被删除 显示默认背景图
            if (stripos($info['backbround'], 'no_image') !== false || stripos($info['backbround'], 'drp_bg.png') !== false) {
                $info['backbround'] = asset('img/drp_bg.png');
            }
        }

        // 预览文字效果
        $show_text_desc = isset($info['description']) && !empty($info['description']) ? nl2br(str_replace(['\r\n', '\n', '\r'], '<br />', htmlspecialchars($info['description']))) : '';
        $this->assign('show_text_desc', $show_text_desc);

        // 获得目录下所有图片列表
        $dir = 'data/attached/qrcode/themes/';
        $imgList = $this->drpManageService->drpQrcodeImageList($dir);
        if (!empty($imgList)) {
            foreach ($imgList as $key => $val) {
                $imgList[$key] = $this->dscRepository->getImagePath($val);
            }
        }
        // 添加默认背景图至开头
        $default_bg = asset('img/drp_bg.png');
        $imgList = collect($imgList)->prepend($default_bg)->all();

        $this->assign('imglist', $imgList);
        $this->assign('info', $info);
        $this->assign('time', $this->timeRepository->getGmTime());
        return $this->display();
    }

    /**
     * 重置名片二维码默认数据
     * @param Request $request
     * @param DrpConfigService $drpConfigService
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset_qrconfig(Request $request, DrpConfigService $drpConfigService)
    {
        if ($request->isMethod('POST')) {

            $drpConfigService->resetQrcode();

            // 清除缓存
            cache()->forget('drp_config');

            $result = ['error' => 0, 'msg' => lang('admin/drp.reset_qrconfig_success')];
            return response()->json($result);
        }
    }

    /**
     * 删除指定背景图
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove_bg(Request $request)
    {
        if ($request->isMethod('POST')) {
            $pic_path = html_in($request->input('path', ''));
            if (!empty($pic_path)) {
                // oss图片处理
                $file_arr = [
                    'pic_path' => $pic_path,
                ];
                $file_arr = $this->dscRepository->transformOssFile($file_arr);

                $pic_path = $file_arr['pic_path'];

                $pic_path = (stripos($pic_path, 'drp_bg') == false) ? $pic_path : ''; // 不删除默认背景图
                $rs = $this->remove($pic_path);
                if ($rs) {
                    $result = ['error' => 0, 'msg' => lang('admin/drp.delete_success')];
                    return response()->json($result);
                }
            }
            $result = ['error' => 1, 'msg' => lang('admin/drp.delete_fail')];
            return response()->json($result);
        }
    }

    /**
     * 同步OSS图片 含上传、下载
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function synchro_images(Request $request)
    {
        if ($request->isMethod('POST')) {
            $type = $request->input('type', 0); // 0.上传  1.下载

            $dir = 'data/attached/qrcode/themes/';
            $imgList = $this->drpManageService->drpQrcodeImageList($dir);

            if (empty($imgList)) {
                $result = ['error' => 1, 'msg' => lang('admin/drp.upload_img_null')];
                return response()->json($result);
            }

            if (!empty($imgList)) {
                // 同步上传
                if ($type == 0) {
                    $res = $this->BatchUploadOss($imgList);
                    if ($res == true) {
                        $result = ['error' => 0, 'msg' => lang('admin/drp.upload_success')];
                    } else {
                        $result = ['error' => 1, 'msg' => lang('admin/drp.upload_fail')];
                    }
                    return response()->json($result);
                }
                // 同步下载
                if ($type == 1) {
                    $res = $this->BatchDownloadOss($imgList);
                    if ($res == true) {
                        $result = ['error' => 0, 'msg' => lang('admin/drp.download_success')];
                    } else {
                        $result = ['error' => 1, 'msg' => lang('admin/drp.download_fail')];
                    }
                    return response()->json($result);
                }
            }
        }
    }

    /**
     * 删除所有用户名片二维码
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete_user_qrcode(Request $request)
    {
        if ($request->isMethod('POST')) {
            $dir = 'data/attached/qrcode/';
            $imgList = $this->drpManageService->drpUserQrcodeList($dir);

            if (empty($imgList)) {
                $result = ['error' => 1, 'msg' => 'empty'];
                return response()->json($result);
            }

            if (!empty($imgList)) {
                // 分块处理 每次1000
                foreach (collect($imgList)->chunk(1000) as $chunk) {
                    $chunk = $chunk ? $chunk->toArray() : [];
                    $this->remove($chunk);
                }

                $result = ['error' => 0, 'msg' => lang('admin/drp.delete_success')];
                return response()->json($result);
            }
        }
    }

    /**
     * 分销商管理
     * @param Request $request
     * @param DrpShopService $drpShopService
     * @param RightsCardManageService $rightsCardManageService
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function shop(Request $request, DrpShopService $drpShopService, RightsCardManageService $rightsCardManageService)
    {
        // 分销商管理权限
        $this->admin_priv('drp_shop');

        $membership_card_id = $request->input('card_id', 0);
        $user_id = $request->input('user_id', 0);
        $keyword = html_in($request->input('keyword', ''));

        if ($request->isMethod('POST')) {
            $user_id = $request->input('user_id', 0);
        }
        $filter['status'] = $request->input('status', 'active');
        $filter['user_id'] = $user_id;
        $filter['keyword'] = $keyword;
        $filter['membership_card_id'] = $membership_card_id;
        $offset = $this->pageLimit(route('admin/drp/shop', $filter), $this->page_num);

        $result = $drpShopService->getList($user_id, $offset, $filter);

        $total = $result['total'] ?? 0;
        $list = $result['list'] ?? [];

        $count = $drpShopService->getCount($user_id, $filter);
        $this->assign('count', $count);

        $card_list = $rightsCardManageService->cardList(2);
        $this->assign('card_list', $card_list);

        if ($membership_card_id) {
            $membership = $rightsCardManageService->membershipCardInfo($membership_card_id);
            $card_name = $membership['name'];
        }
        if ($user_id) {
            $shop_name = $drpShopService->drpShopName($user_id);
        }

        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);

        $this->assign('card_id', $membership_card_id);
        $this->assign('user_id', $user_id);
        $this->assign('keyword', $keyword);
        $this->assign('shop_name', $shop_name ?? '');
        $this->assign('card_name', $card_name ?? '');
        $this->assign('status', $filter['status']);
        return $this->display();
    }

    /**
     * 下线会员列表
     * @return
     */
    public function drp_aff_list()
    {
        // 分销商管理权限
        $this->admin_priv('drp_shop');

        $up_uid = request()->input('auid', 0);
        $level = request()->input('level', 1);

        $keyword = html_in(request()->input('keyword', '')); // 搜索 用户名

        $affiliate = unserialize(config('shop.affiliate'));
        empty($affiliate) && $affiliate = [];
        /*
        if (!isset($affiliate['on']) || $affiliate['on'] == 0) {
            return $this->message('请开启推荐设置', "../affiliate.php?act=list", 2);
        }*/
        $num = count($affiliate['item']);
        $select = [];
        for ($i = 1; $i <= $num; $i++) {
            $select[$i] = $i;
        }
        $this->assign('select', $select); // 推荐等级 选项卡
        $this->assign('current_level', $level);
        $this->assign('auid', $up_uid);

        $filter['auid'] = $up_uid;
        $filter['level'] = $level;
        $offset = $this->pageLimit(route('admin/drp/drp_aff_list', $filter), $this->page_num);

        $user_name = Users::where(['user_id' => $up_uid])->value('user_name');

        if (isset($this->config['show_mobile']) && $this->config['show_mobile'] == 0) {
            $user_name = $this->dscRepository->stringToStar($user_name);
        }

        $this->assign('user_name', $user_name);

        $all_count = 0;
        $user_list = [];
        $level_count = 0;

        for ($i = 1; $i <= $level; $i++) {
            $count = 0;
            if ($up_uid) {
                $up_uid = explode(',', $up_uid);

                $user_ids = Users::whereIn('drp_parent_id', $up_uid)->pluck('user_id');
                $user_ids = $user_ids ? $user_ids->toArray() : [];
                $up_uid = ''; // 下级会员id
                if ($user_ids) {
                    foreach ($user_ids as $user_id) {
                        $up_uid .= $up_uid ? "," . $user_id : $user_id;
                        $count++;
                    }
                }
            }

            $all_count += $count;
            if ($count && $level == $i) {

                $up_uid = explode(',', $up_uid);

                $users = Users::select('user_id', 'user_name', 'email', 'is_validated', 'user_money', 'frozen_money', 'rank_points', 'pay_points', 'reg_time')
                    ->selectRaw("$i level");

                $users = $users->whereIn('user_id', $up_uid);

                if (!empty($keyword)) {
                    $users = $users->where(function ($query) use ($keyword) {
                        $query->where('user_name', 'like', '%' . $keyword . '%');
                    });
                }

                $level_count = $users->count('user_id');

                $users = $users->offset($offset['start'])
                    ->limit($offset['limit'])
                    ->orderBy('level', 'ASC')
                    ->get();

                $user_list = $users ? $users->toArray() : [];
            }
        }

        if (!empty($user_list)) {
            foreach ($user_list as $k => $val) {

                if (isset($this->config['show_mobile']) && $this->config['show_mobile'] == 0) {
                    $user_list[$k]['user_name'] = $this->dscRepository->stringToStar($val['user_name']);
                    $user_list[$k]['email'] = $this->dscRepository->stringToStar($val['email']);
                }

                $user_list[$k]['reg_time'] = $this->timeRepository->getLocalDate(config('shop.date_format'), $val['reg_time']);
                $user_list[$k]['edit_url'] = "../users.php?act=edit&id=" . $val['user_id'];
                $user_list[$k]['address_list'] = "../users.php?act=address_list&id=" . $val['user_id'];
                $user_list[$k]['order_list'] = "../order.php?act=list&user_id=" . $val['user_id'];
                $user_list[$k]['account_log'] = "../account_log.php?act=list&user_id=" . $val['user_id'];

                if ($val['level'] != $level) {
                    unset($user_list[$k]); // 只显示当前等级数据
                }
            }
        }

        // $all_count 所有推荐等级 总记录数
        // $level_count 当前推荐等级 总记录数
        $this->assign('page', $this->pageShow($level_count));
        $this->assign('user_list', $user_list);
        return $this->display();
    }

    /**
     * 添加分销商
     * @param Request $request
     * @param DrpService $drpService
     * @param RightsCardManageService $rightsCardManageService
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function add_shop(Request $request, DrpService $drpService, RightsCardManageService $rightsCardManageService)
    {
        if ($request->isMethod('POST')) {
            // 搜索会员
            $handle = $request->input('handle', '');
            if ($handle == 'search_user') {
                $user_name = $request->input('user_name', '');

                if (empty($user_name)) {
                    return response()->json(['error' => 1, 'msg' => lang('admin/drp.search_user_name_not')]);
                }

                // 验证手机号格式
                if (is_mobile($user_name) == false) {
                    return response()->json(['error' => 1, 'msg' => lang('user.bind_mobile_error')]);
                }

                $user = $this->drpManageService->checkUser($user_name);

                if (empty($user)) {
                    return response()->json(['error' => 1, 'msg' => lang('admin/drp.user_name_not')]);
                } elseif (isset($user['error']) && $user['error'] > 0) {
                    return response()->json(['error' => $user['error'], 'msg' => $user['msg']]);
                }

                return response()->json(['error' => 0, 'msg' => 'success', 'data' => $user]);
            }

            // 添加
            $user_id = $request->input('user_id', 0);
            $data = $request->input('data');

            if (empty($user_id)) {
                return $this->message(lang('admin/drp.search_user_name_not'), null, 2);
            }

            $membership_card_id = $data['membership_card_id'] ?? 0;
            $status_check = $data['status'] ?? 0;

            if (empty($membership_card_id)) {
                return $this->message(lang('admin/drp.membership_card_empty'), null, 2);
            }

            // 查询分销商
            $count = DrpShop::query()->where('user_id', $user_id)->count();
            if ($count > 0) {
                return $this->message(lang('admin/drp.add_shop_exist'), null, 2);
            }

            $res = $drpService->specifyUpdateDrpShop($user_id, $membership_card_id, $status_check);
            if ($res) {

                return $this->message(lang('admin/common.add') . lang('admin/common.success'), route('admin/drp/shop'));
            }

            return $this->message(lang('admin/common.add') . lang('admin/common.fail'), route('admin/drp/shop'));
        }

        $enable = $request->input('enable', 1); // 权益卡状态： 0 关闭、1 开启
        $card_list = $rightsCardManageService->cardList(2, $enable);
        $this->assign('card_list', $card_list);
        return $this->display();
    }

    /**
     * 编辑分销商 更改权益卡
     * @param Request $request
     * @param DrpShopService $drpShopService
     * @param DrpService $drpService
     * @param RightsCardManageService $rightsCardManageService
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit_shop(Request $request, DrpShopService $drpShopService, DrpService $drpService, RightsCardManageService $rightsCardManageService)
    {
        // 提交
        if ($request->isMethod('POST')) {

            $user_id = $request->input('user_id', 0);
            $data = $request->input('data');

            if (empty($user_id)) {
                return $this->message(lang('admin/drp.data_not_null'), null, 2);
            }

            $membership_card_id = $data['membership_card_id'] ?? 0;
            $status_check = $data['status'] ?? 0;

            if (empty($membership_card_id)) {
                return $this->message(lang('admin/drp.membership_card_empty'), null, 2);
            }

            $res = $drpService->specifyUpdateDrpShop($user_id, $membership_card_id, $status_check);
            if ($res) {

                return $this->message(lang('admin/common.edit') . lang('admin/common.success'), route('admin/drp/shop'));
            }

            return $this->message(lang('admin/common.edit') . lang('admin/common.fail'), route('admin/drp/shop'));
        }

        // 显示
        $id = $request->input('id', 0);

        if (empty($id)) {
            return $this->message(lang('admin/drp.select_shop'), null, 2);
        }

        $info = $drpShopService->getDrpShop($id);
        $this->assign('info', $info);

        $enable = $request->input('enable', 1); // 权益卡状态： 0 关闭、1 开启
        $card_list = $rightsCardManageService->cardList(2, $enable);
        $this->assign('card_list', $card_list);
        return $this->display();
    }

    /**
     * 审核分销商状态
     * @param Request $request
     * @param DistributeManageService $distributeManageService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function set_shop(Request $request, DistributeManageService $distributeManageService)
    {
        // 分销商管理权限
        $this->admin_priv('drp_shop');

        $id = $request->input('id', 0);
        $audit = $request->input('audit', 0);
        $status = $request->input('status', 0);
        $user_note = $request->input('user_note', '');

        if (empty($id)) {
            return $this->message(lang('admin/drp.select_shop'), null, 2);
        }

        $data = [];

        // 店铺开关
        $data['status'] = $status == 1 ? 1 : 0;

        $now = $this->timeRepository->getGmTime();

        // 审核
        if ($audit == 1) {
            $data['status'] = 1;
            $data['audit'] = 1;
            // 审核通过 记录权益开始时间
            $data['open_time'] = $now;
        } elseif ($audit == 2) {
            $data['status'] = 0;
            $data['audit'] = 2;
        }

        if (!empty($data)) {
            $model = DrpShop::query()->where('id', $id)->first();

            if ($model->audit == 0 && empty($model->open_time)) {
                // 权益卡领取类型 days 未审核需要审核时 重新更新权益有效期时间 new_expiry_time = now + (expiry_time - apply_time)
                if (empty($model->expiry_type)) {
                    $expiry_type = UserMembershipCard::where('id', $model->membership_card_id)->value('expiry_type');
                } else {
                    $expiry_type = $model->expiry_type;
                }

                if ($expiry_type == 'days' && !empty($model->apply_time)) {
                    $data['expiry_time'] = $now + ($model->expiry_time - $model->apply_time);
                }
            }

            $res = $model->update($data);

            if ($res && $audit == 2) {
                // 拒绝审核 分销商后续
                $user_note = empty($user_note) ? lang('drp.return_pay_point_change_desc') : $user_note;
                $distributeManageService->refuse_drp_after($id, $user_note);
            }

            if ($status == 1) {
                $drp_shop_user_id = $model->user_id;
                $distributeManageService->drp_upgrade_main_con($drp_shop_user_id);
            }
        }

        return redirect()->route('admin/drp/shop');
    }

    /**
     * 导出分销商
     * @param Request $request
     * @param DrpShopService $drpShopService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function export_shop(Request $request, DrpShopService $drpShopService)
    {
        // 分销商管理权限
        $this->admin_priv('drp_shop');

        if ($request->isMethod('POST')) {
            $membership_card_id = $request->input('card_id', 0);
            $starttime = $request->input('starttime', '');
            $endtime = $request->input('endtime', '');
            $user_id = $request->input('user_id', 0);

            if (empty($starttime) || empty($endtime)) {
                return $this->message(lang('admin/drp.select_start_end_time'), null, 2);
            }
            $starttime = $this->timeRepository->getLocalStrtoTime($starttime);
            $endtime = $this->timeRepository->getLocalStrtoTime($endtime);
            if ($starttime > $endtime) {
                return $this->message(lang('admin/drp.start_lt_end_time'), null, 2);
            }

            $filter = [
                'starttime' => $starttime,
                'endtime' => $endtime,
            ];
            $filter['membership_card_id'] = $membership_card_id;
            $result = $drpShopService->getList($user_id, [], $filter);

            $list = $result['list'] ?? [];

            if ($list) {
                // 设置 表头标题、列宽 默认10 格式：列名|10
                $head = [
                    lang('admin/drp.shop_number'),
                    lang('admin/drp.user_name') . '|20',
                    lang('admin/drp.shop_name') . '|20',
                    lang('admin/drp.parent_name') . '|20',
                    lang('admin/drp.mobile') . '|20',
                    lang('admin/drp.drp_shop_name') . '|20',
                    lang('admin/drp.create_time') . '|20',
                    lang('admin/drp.check_status') . '|20',
                    lang('admin/drp.open_time') . '|20',
                    lang('admin/drp.shop_state') . '|20',
                ];
                // 导出字段
                $fields = [
                    'id',
                    'user_name',
                    'shop_name',
                    'parent_name',
                    'mobile',
                    'credit_name',
                    'create_time_format',
                    'audit_format',
                    'open_time_format',
                    'status_format',
                ];
                // 文件名
                $title = lang('admin/drp.distribution_information');

                $spreadsheet = new OfficeService();

                $spreadsheet->outdata($title, $head, $fields, $list);
                return;
            } else {
                return $this->message(lang('admin/drp.data_null'), null, 2);
            }
        }
        return redirect()->route('admin/drp/shop');
    }

    /**
     * 分销排行
     */
    public function drp_list(Request $request)
    {
        // 分销排行权限
        $this->admin_priv('drp_list');

        $act = $request->input('where');

        $filter['where'] = $act;
        $offset = $this->pageLimit(route('admin/drp/drp_list', $filter), $this->page_num);

        $result = $this->drpManageService->drpAffiliateList($offset, $filter);

        $list = $result['list'] ?? [];
        $total = $result['total'] ?? 0;

        $this->assign('list', $list);
        $this->assign('act', $act);
        $this->assign('page', $this->pageShow($total));
        return $this->display();
    }

    /**
     * 分销订单列表
     * @param Request $request
     * @param DrpConfigService $drpConfigService
     * @return mixed
     */
    public function drp_order_list(Request $request, DrpConfigService $drpConfigService)
    {
        // 分销订单权限
        $this->admin_priv('drp_order_list');

        $log_type = $request->input('log_type', 0); // 分成类型 0 普通订单  2 指定商品购买

        $status = $request->input('status');
        $able = $request->input('able');
        $order_sn = $request->input('order_sn', '');

        // 分销配置 1.4.1 edit
        $drp_config = $drpConfigService->drpConfig();
        $drp_affiliate = $drp_config['drp_affiliate_on']['value'] ?? 0;
        $able_day = $drp_config['settlement_time']['value'] ?? 7;
        $aff_day = $this->timeRepository->getLocalStrtoTime(-$able_day . 'day');

        $filter['log_type'] = $log_type;
        $filter['status'] = $status;
        $filter['able'] = $able;
        $filter['order_sn'] = $order_sn;
        $offset = $this->pageLimit(route('admin/drp/drp_order_list', $filter), $this->page_num);

        $list = [];
        $total = 0;
        if ($drp_affiliate == 1) {
            // 普通订单分成
            $result = $this->drpManageService->drpOrderListAll($this->ru_id, $aff_day, $offset, $filter);

            $list = $result['list'] ?? [];
            $total = $result['total'] ?? 0;
        }

        $this->assign('status', $status);
        $this->assign('able', $able);
        $this->assign('on', $drp_affiliate);
        $this->assign('able_day', $able_day);
        $this->assign('drp_config', $drp_config);

        $this->assign('filter', $filter);
        $this->assign('list', $list);
        $this->assign('page', $this->pageShow($total));
        return $this->display();
    }

    /**
     * 导出订单列表
     * @param Request $request
     * @param DrpConfigService $drpConfigService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function export_order(Request $request, DrpConfigService $drpConfigService)
    {
        // 分销商管理权限
        $this->admin_priv('drp_order_list');

        if ($request->isMethod('POST')) {
            $starttime = $request->input('starttime', '');
            $endtime = $request->input('endtime', '');

            if (empty($starttime) || empty($endtime)) {
                return $this->message(lang('admin/drp.select_start_end_time'), null, 2);
            }
            $starttime = $this->timeRepository->getLocalStrtoTime($starttime);
            $endtime = $this->timeRepository->getLocalStrtoTime($endtime);
            if ($starttime > $endtime) {
                return $this->message(lang('admin/drp.start_lt_end_time'), null, 2);
            }

            $status = $request->input('status');
            $able = $request->input('able');
            $order_sn = $request->input('order_sn', '');

            $drp_config = $drpConfigService->drpConfig();
            $drp_affiliate = $drp_config['drp_affiliate_on']['value'] ?? 0;
            $able_day = $drp_config['settlement_time']['value'] ?? 7;
            $aff_day = $this->timeRepository->getLocalStrtoTime(-$able_day . 'day');

            $list = [];
            if ($drp_affiliate == 1) {
                $filter = [
                    'starttime' => $starttime,
                    'endtime' => $endtime,
                ];
                $filter['status'] = $status;
                $filter['able'] = $able;
                $filter['order_sn'] = $order_sn;
                $result = $this->drpManageService->drpOrderListExcel($this->ru_id, $aff_day, [], $filter);

                $list = $result['list'] ?? [];
            }

            if ($list) {
                // 设置 表头标题、列宽 默认10, true 是否自动换行  格式：列名|10|true
                $head = [
                    lang('admin/drp.order_id'),
                    lang('admin/drp.order_sn') . '|30',
                    lang('admin/drp.user_name'),
                    lang('admin/drp.drp_ru_name'),
                    lang('admin/drp.order_stats.name') . '|30',
                    lang('admin/drp.pay_time'),
                    lang('admin/drp.drp_info') . '|50|true',
                    lang('admin/drp.sch_stats.name') . '|20'
                ];
                // 导出字段
                $fields = [
                    'order_id',
                    'user_name',
                    'order_sn',
                    'shop_name',
                    'order_status',
                    'pay_time_format',
                    'info',
                    'sch_status'
                ];
                // 文件名
                $title = lang('admin/drp.order_info');

                $spreadsheet = new OfficeService();

                $spreadsheet->outdata($title, $head, $fields, $list);
                return;
            } else {
                return $this->message(lang('admin/drp.data_null'), null, 2);
            }
        }
        return redirect()->route('admin/drp/drp_order_list');
    }

    /**
     * 分成(含批量)
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function separate_drp_order(Request $request)
    {
        // 分销订单权限
        $this->admin_priv('drp_order_list');

        $oid = $request->input('oid');

        if (is_array($oid)) {
            $oid_arr = $oid;
        } else {
            $oid_arr[] = $oid;
        }

        if (!empty($oid_arr) && is_array($oid_arr)) {
            foreach ($oid_arr as $order_id) {
                // 取drp_log日志表 分成信息
                $this->drpManageService->drpLogList($order_id);
            }
        }

        // 批量分成 操作
        if ($request->isMethod('POST')) {
            return response()->json(['url' => route('admin/drp/drp_order_list')]);
        } else {
            return redirect()->route('admin/drp/drp_order_list');
        }
    }

    /**
     * 取消分成，不再能对该订单进行分成
     **/
    public function del_drp_order(Request $request)
    {
        // 分销订单权限
        $this->admin_priv('drp_order_list');

        $order_id = $request->input('oid', 0);

        if ($order_id > 0) {

            $this->drpManageService->cancleDrpOrder($order_id);
        }
        return redirect()->route('admin/drp/drp_order_list');
    }

    /**
     * 撤销某次分成，将已分成的收回来 功能已丢弃
     **/
    public function rollback_drp_order(Request $request)
    {
        // 分销订单权限
        $this->admin_priv('drp_order_list');

        $order_id = $request->input('order_id', 0);

        if ($order_id) {
            $this->drpManageService->rollbackDrpOrder($order_id);
        }
        return redirect()->route('admin/drp/drp_order_list');
    }

    /**
     * 分销付费购买分成列表
     * @param Request $request
     * @param DrpConfigService $drpConfigService
     * @return mixed
     */
    public function drp_order_list_buy(Request $request, DrpConfigService $drpConfigService)
    {
        // 分销订单权限
        $this->admin_priv('drp_order_list');

        $log_type = 1; // 分成类型 1 付费购买

        $status = $request->input('status');
        $able = $request->input('able');

        // 搜索用户名
        $user_name = $request->input('user_name', '');

        // 分销配置 1.4.1 edit
        $drp_config = $drpConfigService->drpConfig();
        $drp_affiliate = $drp_config['drp_affiliate_on']['value'] ?? 0;
        $able_day = $drp_config['settlement_time']['value'] ?? 7;
        $aff_day = $this->timeRepository->getLocalStrtoTime(-$able_day . 'day');

        $filter['log_type'] = $log_type;
        $filter['status'] = $status;
        $filter['able'] = $able;
        $filter['user_name'] = $user_name;
        $offset = $this->pageLimit(route('admin/drp/drp_order_list_buy', $filter), $this->page_num);

        $list = [];
        $total = 0;
        if ($drp_affiliate == 1) {
            // 付费购买分成
            $result = $this->drpManageService->drpOrderListBuy($this->ru_id, $aff_day, $offset, $filter);

            $list = $result['list'] ?? [];
            $total = $result['total'] ?? 0;
        }

        $this->assign('status', $status);
        $this->assign('able', $able);
        $this->assign('on', $drp_affiliate);
        $this->assign('able_day', $able_day);

        $this->assign('filter', $filter);
        $this->assign('list', $list);
        $this->assign('page', $this->pageShow($total));
        return $this->display();
    }

    /**
     * 导出付费购买分成列表
     * @param Request $request
     * @param DrpConfigService $drpConfigService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function export_buy(Request $request, DrpConfigService $drpConfigService)
    {
        // 分销商管理权限
        $this->admin_priv('drp_order_list');

        if ($request->isMethod('POST')) {
            $starttime = $request->input('starttime', '');
            $endtime = $request->input('endtime', '');

            if (empty($starttime) || empty($endtime)) {
                return $this->message(lang('admin/drp.select_start_end_time'), null, 2);
            }
            $starttime = $this->timeRepository->getLocalStrtoTime($starttime);
            $endtime = $this->timeRepository->getLocalStrtoTime($endtime);
            if ($starttime > $endtime) {
                return $this->message(lang('admin/drp.start_lt_end_time'), null, 2);
            }

            $log_type = 1; // 分成类型 1 付费购买

            $status = $request->input('status');
            $able = $request->input('able');
            // 搜索用户名
            $user_name = $request->input('user_name', '');

            $drp_config = $drpConfigService->drpConfig();
            $drp_affiliate = $drp_config['drp_affiliate_on']['value'] ?? 0;
            $able_day = $drp_config['settlement_time']['value'] ?? 7;
            $aff_day = $this->timeRepository->getLocalStrtoTime(-$able_day . 'day');

            $list = [];
            if ($drp_affiliate == 1) {
                $filter = [
                    'starttime' => $starttime,
                    'endtime' => $endtime,
                ];
                $filter['log_type'] = $log_type;
                $filter['status'] = $status;
                $filter['able'] = $able;
                $filter['user_name'] = $user_name;
                // 付费购买分成
                $for_excel = 1;
                $result = $this->drpManageService->drpOrderListBuy($this->ru_id, $aff_day, [], $filter, $for_excel);

                $list = $result['list'] ?? [];
            }

            if ($list) {
                // 设置 表头标题、列宽 默认10, true 是否自动换行  格式：列名|10|true
                $head = [
                    lang('admin/drp.user_name'),
                    lang('admin/drp.shop_name'),
                    lang('admin/drp.order_stats.name') . '|30',
                    lang('admin/drp.pay_time'),
                    lang('admin/drp.drp_info') . '|50|true',
                    lang('admin/drp.sch_stats.name') . '|20'
                ];
                // 导出字段
                $fields = [
                    'user_name',
                    'shop_name',
                    'is_paid_format',
                    'pay_time_format',
                    'info',
                    'sch_status'
                ];
                // 文件名
                $title = lang('admin/drp.order_buy_info');

                $spreadsheet = new OfficeService();

                $spreadsheet->outdata($title, $head, $fields, $list);
                return;
            } else {
                return $this->message(lang('admin/drp.data_null'), null, 2);
            }
        }
        return redirect()->route('admin/drp/drp_order_list_buy');
    }

    /**
     * 付费购买分成(含批量)
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function separate_drp_order_buy(Request $request)
    {
        // 分销订单权限
        $this->admin_priv('drp_order_list');

        $drp_log_id = $request->input('drp_log_id');

        $id_arr = [];

        if (!empty($drp_log_id)) {
            if (is_array($drp_log_id)) {
                $id_arr = $drp_log_id;
            } else {
                $id_arr[] = $drp_log_id;
            }

            if (is_array($id_arr)) {
                foreach ($id_arr as $log_id) {
                    // 取drp_log日志表 分成信息
                    $this->drpManageService->drpLogListBuy($log_id);
                }
            }
        }

        // 批量分成 操作
        if ($request->isMethod('POST')) {
            return response()->json(['url' => route('admin/drp/drp_order_list_buy')]);
        } else {
            return redirect()->route('admin/drp/drp_order_list_buy');
        }
    }

    /**
     * 取消付费购买分成，不再能对该订单进行分成
     */
    public function del_drp_order_buy(Request $request)
    {
        // 分销订单权限
        $this->admin_priv('drp_order_list');

        $account_log_id = $request->input('account_log_id', 0);

        if ($account_log_id > 0) {

            $this->drpManageService->cancleDrpOrderBuy($account_log_id);
        }

        return redirect()->route('admin/drp/drp_order_list_buy');
    }

    /**
     * 分销统计
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function drp_count(Request $request)
    {
        // 分销统计权限
        $this->admin_priv('drp_count');

        if ($request->isMethod('POST')) {
            $type = $request->input('type', '');
            $date = $request->input('date', 'week');

            //格林威治时间与本地时间差
            $timezone = session()->has('timezone') ? session('timezone') : config('shop.timezone');
            $time_diff = $timezone * 3600;

            $day_num = 7;
            if ($date == 'week') {
                $day_num = 7;
            }
            if ($date == 'month') {
                $day_num = 30;
            }
            if ($date == 'year') {
                $day_num = 180;
            }

            $date_end = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
            $date_start = $date_end - 3600 * 24 * $day_num;

            $shop_series_data = [];
            $orders_series_data = [];
            $sales_series_data = [];

            // 获得分销商数据
            if ($type == 'shop') {
                $shop_series_data = $this->drpManageService->shop_series_data($date_start, $date_end, $time_diff);
            }

            // 获取分销订单数据
            if ($type == 'order') {
                $orders_series_data = $this->drpManageService->orders_series_data($this->ru_id, $date_start, $date_end, $time_diff);
            }

            // 获取分销佣金数据
            if ($type == 'sale') {
                $sales_series_data = $this->drpManageService->sales_series_data($date_start, $date_end, $time_diff);
            }

            // 趋势图 X 轴 时间
            $shop_xAxis_date = [];
            $orders_xAxis_date = [];
            $sales_xAxis_date = [];
            for ($i = 1; $i <= $day_num; $i++) {
                $day = $this->timeRepository->getLocalDate("y-m-d", $this->timeRepository->getLocalStrtoTime(" - " . ($day_num - $i) . " days"));
                if (empty($shop_series_data[$day])) {
                    $shop_series_data[$day] = 0;
                }
                if (empty($orders_series_data[$day])) {
                    $orders_series_data[$day] = 0;
                }
                if (empty($sales_series_data[$day])) {
                    $sales_series_data[$day] = 0;
                }
                //输出时间
                $day = $this->timeRepository->getLocalDate("m-d", $this->timeRepository->getLocalStrtoTime($day));
                $shop_xAxis_date[] = $day;
                $orders_xAxis_date[] = $day;
                $sales_xAxis_date[] = $day;
            }

            /**
             * 输出数据到 Echarts 趋势图
             */
            $echarts = [];
            //分销商统计
            if ($type == 'shop') {
                $legend['data'] = [lang('admin/drp.people_number')];
                $xAxis['data'] = $shop_xAxis_date;
                $yAxis['formatter'] = '{value}个';
                ksort($shop_series_data);

                $series_data['name'] = lang('admin/drp.people_number');
                $series_data['data'] = array_values($shop_series_data);

                $echarts = $this->drpManageService->transEcharts($legend, $xAxis, $yAxis, $series_data);
            }

            //订单统计
            if ($type == 'order') {
                $legend['data'] = [lang('admin/drp.order_number')];
                $xAxis['data'] = $orders_xAxis_date;
                $yAxis['formatter'] = '{value}个';
                ksort($orders_series_data);

                $series_data['name'] = lang('admin/drp.order_number');
                $series_data['data'] = array_values($orders_series_data);

                $echarts = $this->drpManageService->transEcharts($legend, $xAxis, $yAxis, $series_data);
            }

            //分销佣金统计
            if ($type == 'sale') {
                $legend['data'] = [lang('admin/drp.price_number')];
                $xAxis['data'] = $sales_xAxis_date;
                $yAxis['formatter'] = '{value}元';
                ksort($sales_series_data);
                $series_data['name'] = lang('admin/drp.price_number');
                $series_data['data'] = array_values($sales_series_data);

                $echarts = $this->drpManageService->transEcharts($legend, $xAxis, $yAxis, $series_data);
            }

            return response()->json($echarts);
        }

        // 统计总额
        $result = $this->drpManageService->drpStatistics($this->ru_id);

        // 统计分销商：分销商总量 + 分销商加入趋势图
        $drp_shop_count = $result['drp_shop_count'];

        // 统计分销订单：分销订单总额 + 分销订单趋势图
        $drp_order_count = $result['drp_order_count'];

        // 统计分销佣金：分销佣金总额 + 分销佣金趋势图
        $drp_sales_count = $result['drp_sales_count'];

        $this->assign('drp_shop_trend', 1);
        $this->assign('drp_shop_count', $drp_shop_count);

        $this->assign('drp_order_trend', 1);
        $this->assign('drp_order_count', $drp_order_count);

        $this->assign('drp_sales_trend', 1);
        $this->assign('drp_sales_count', $drp_sales_count);

        $this->assign('page_title', lang('admin/drp.drp_count'));
        return $this->display();
    }
}
