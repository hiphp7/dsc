<?php

namespace App\Plugins\Market\Wall;

use App\Http\Controllers\Wechat\PluginController;
use App\Libraries\Form;
use App\Models\WechatMarketing;
use App\Models\WechatPrize;
use App\Models\WechatWallMsg;
use App\Models\WechatWallUser;
use App\Repositories\Common\DscRepository;
use Endroid\QrCode\QrCode;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * 微信墙后台模块
 * Class Admin
 * @package App\Plugins\Market\Wall
 */
class Admin extends PluginController
{
    protected $marketing_type = ''; // 活动类型
    protected $wechat_id = 0; // 微信通ID
    protected $wechat_ru_id = 0; // 商家ID
    protected $page_num = 10; // 分页数量
    protected $dscRepository;

    // 配置
    protected $cfg = [];

    public function __construct($cfg = [])
    {
        parent::__construct();

        $this->cfg = $cfg;
        $this->cfg['plugin_path'] = 'Market';
        $this->plugin_name = $this->marketing_type = $cfg['keywords'];
        $this->wechat_id = isset($cfg['wechat_id']) ? $cfg['wechat_id'] : 0;
        $this->wechat_ru_id = isset($cfg['wechat_ru_id']) ? $cfg['wechat_ru_id'] : 0;
        $this->page_num = isset($cfg['page_num']) ? $cfg['page_num'] : 10;

        $this->plugin_assign('wechat_ru_id', $this->wechat_ru_id);

        if ($this->wechat_ru_id > 0) {
            // 查询商家管理员
            $this->assign('admin_info', $this->cfg['seller']);
            $this->assign('ru_id', $this->cfg['seller']['ru_id']);
            $this->assign('seller_name', $this->cfg['seller']['user_name']);

            //判断编辑个人资料权限
            $this->assign('privilege_seller', $this->cfg['privilege_seller']);
            // 商家菜单列表
            $this->assign('seller_menu', $this->cfg['menu']);
            // 当前选择菜单
            $this->assign('menu_select', $this->cfg['menu_select']);
            // 当前位置
            $this->assign('postion', $this->cfg['postion']);
        }

        $this->plugin_assign('page_num', $this->page_num);
        $this->plugin_assign('config', $this->cfg);

        $this->dscRepository = app(DscRepository::class);
    }

    /**
     * 活动列表
     */
    public function marketList()
    {
        $filter['type'] = $this->marketing_type;
        $offset = $this->pageLimit($this->wechat_ru_id > 0 ? route('seller/wechat/market_list', $filter) : route('admin/wechat/market_list', $filter), $this->page_num);

        $total = WechatMarketing::where(['marketing_type' => $this->marketing_type, 'wechat_id' => $this->wechat_id])->count();

        $list = WechatMarketing::select('id', 'name', 'command', 'starttime', 'endtime', 'status')
            ->where(['marketing_type' => $this->marketing_type, 'wechat_id' => $this->wechat_id])
            ->orderBy('addtime', 'DESC')
            ->orderBy('id', 'DESC')
            ->offset($offset['start'])
            ->limit($offset['limit'])
            ->get();
        $list = $list ? $list->toArray() : [];

        if ($list) {
            foreach ($list as $k => $v) {
                $list[$k]['starttime'] = $this->timeRepository->getLocalDate('Y-m-d', $v['starttime']);
                $list[$k]['endtime'] = $this->timeRepository->getLocalDate('Y-m-d', $v['endtime']);
                $res = $this->get_user_msg_count($v['id']);
                $list[$k]['user_count'] = $res['user_count'];  // 参与人数
                $list[$k]['msg_count'] = $res['msg_count'];  // 上墙信息
                $status = $this->wechatService->get_status($v['starttime'], $v['endtime']); // 活动状态 0未开始,1正在进行,2已结束
                if ($status == 0) {
                    $list[$k]['status'] = L('no_start');
                } elseif ($status == 1) {
                    $list[$k]['status'] = L('start');
                } elseif ($status == 2) {
                    $list[$k]['status'] = L('over');
                }
            }
        }

        $this->plugin_assign('page', $this->pageShow($total));
        $this->plugin_assign('list', $list);
        return $this->plugin_display('market_list', $this->_data);
    }

    /**
     * 活动添加与编辑
     * @return
     */
    public function marketEdit()
    {
        // 提交
        if (request()->isMethod('post')) {
            $id = request()->input('id', 0);
            $handler = request()->input('handler', '');
            $data = request()->input('data', '');
            $config = request()->input('config', '');

            $form = new Form();
            if (!$form->isEmpty($data['name'], 1)) {
                $json_result = ['error' => 1, 'msg' => L('market_name') . L('empty'), 'url' => ''];
                return response()->json($json_result);
            }
            $data['wechat_id'] = $this->wechat_id;
            $data['marketing_type'] = request()->input('marketing_type');
            $data['starttime'] = $this->timeRepository->getLocalStrtoTime($data['starttime']);
            $data['endtime'] = $this->timeRepository->getLocalStrtoTime($data['endtime']);

            $data['status'] = $this->wechatService->get_status($data['starttime'], $data['endtime']); // 活动状态
            $data['description'] = $data['description'] ?? '';
            $data['support'] = $data['support'] ?? '';

            $logo_path = request()->input('logo_path');
            $background_path = request()->input('background_path');

            // 默认logo、背景
            $logo_path = !empty($logo_path) ? $logo_path : asset('assets/wechat/wall/images/logo.png');
            $background_path = !empty($background_path) ? $background_path : asset('assets/wechat/wall/images/bg.png');

            // 编辑图片处理
            $logo_path = $this->dscRepository->editUploadImage($logo_path);
            $background_path = $this->dscRepository->editUploadImage($background_path);

            //上传logo, 背景图片
            $log_file = request()->file('logo');
            $background_file = request()->file('background');

            if (($log_file && $log_file->isValid()) || ($background_file && $background_file->isValid())) {
                // 验证文件大小
                if (($log_file && $log_file->getSize() > 5 * 1024 * 1024) || ($background_file && $background_file->getSize() > 5 * 1024 * 1024)) {
                    $json_result = ['error' => 1, 'msg' => L('file_size_limit'), 'url' => ''];
                    return response()->json($json_result);
                }
                // 验证文件格式
                if (($log_file && !in_array($log_file->getMimeType(), ['image/jpeg', 'image/png'])) || ($background_file && !in_array($background_file->getMimeType(), ['image/jpeg', 'image/png']))) {
                    $json_result = ['error' => 1, 'msg' => L('not_file_type'), 'url' => ''];
                    return response()->json($json_result);
                }

                $result = $this->upload('data/attached/wall', false);
                if (isset($result['logo']['error']) && $result['logo']['error'] > 0) {
                    $json_result = ['error' => 1, 'msg' => $result['logo']['message'], 'url' => ''];
                    return response()->json($json_result);
                }
                if (isset($result['background']['error']) && $result['background']['error'] > 0) {
                    $json_result = ['error' => 1, 'msg' => $result['background']['message'], 'url' => ''];
                    return response()->json($json_result);
                }
            }
            //处理logo
            if ($log_file && $log_file->isValid() && $result['logo']['file_name']) {
                $data['logo'] = 'data/attached/wall/' . $result['logo']['file_name'];
            } else {
                $data['logo'] = $logo_path;
            }
            //处理背景图片
            if ($background_file && $background_file->isValid() && $result['background']['file_name']) {
                $data['background'] = 'data/attached/wall/' . $result['background']['file_name'];
            } else {
                $data['background'] = $background_path;
            }

            if (!$form->isEmpty($data['logo'], 1)) {
                $json_result = ['error' => 1, 'msg' => L('please_upload'), 'url' => ''];
                return response()->json($json_result);
            }
            if (!$form->isEmpty($data['background'], 1)) {
                $json_result = ['error' => 1, 'msg' => L('please_upload'), 'url' => ''];
                return response()->json($json_result);
            }

            // oss图片处理
            if ($this->config['open_oss'] == 1) {
                $bucket_info = $this->dscRepository->getBucketInfo();
                $bucket_info['endpoint'] = empty($bucket_info['endpoint']) ? $bucket_info['outside_site'] : $bucket_info['endpoint'];
                $http = rtrim($bucket_info['endpoint'], '/') . '/';

                $data['logo'] = str_replace($http, '', $data['logo']);
                // 编辑时 删除oss图片
                $logo_path = str_replace($http, '', $logo_path);

                $data['background'] = str_replace($http, '', $data['background']);
                // 编辑时 删除oss图片
                $logo_path = str_replace($http, '', $logo_path);
            }
            // 路径转换
            if (strtolower(substr($data['logo'], 0, 4)) == 'http') {
                $data['logo'] = str_replace(url('/'), '', $data['logo']);
                // 编辑时 删除原图片
                $logo_path = str_replace(url('/'), '', $logo_path);
            }
            if (strtolower(substr($data['background'], 0, 4)) == 'http') {
                $data['background'] = str_replace(url('/'), '', $data['background']);
                // 编辑时 删除原图片
                $background_path = str_replace(url('/'), '', $background_path);
            }
            //配置
            if ($config) {
                // 奖品处理
                if (is_array($config['prize_level']) && is_array($config['prize_count']) && is_array($config['prize_name'])) {
                    foreach ($config['prize_level'] as $key => $val) {
                        $prize_arr[] = [
                            'prize_level' => $val,
                            'prize_name' => $config['prize_name'][$key],
                            'prize_count' => $config['prize_count'][$key],
                        ];
                    }
                }
                $data['config'] = isset($prize_arr) ? serialize($prize_arr) : '';
            }
            // 不保存默认空图片
            if (strpos($data['logo'], 'no_image') !== false || strpos($data['logo'], 'logo.png') !== false) {
                unset($data['logo']);
            }
            if (strpos($data['background'], 'no_image') !== false || strpos($data['background'], 'bg.png') !== false) {
                unset($data['background']);
            }
            //更新活动
            if ($id && $handler == 'edit') {
                // 删除原logo图片
                if (isset($data['logo']) && $logo_path != $data['logo']) {
                    $logo_path = strpos($logo_path, 'no_image') == false ? $logo_path : ''; // 不删除默认空图片
                    $this->remove($logo_path);
                }
                // 删除原背景图片
                if ($data['background'] && $background_path != $data['background']) {
                    $background_path = strpos($background_path, 'no_image') == false ? $background_path : '';  // 不删除默认空图片
                    $this->remove($background_path);
                }
                $where = [
                    'id' => $id,
                    'wechat_id' => $this->wechat_id
                ];

                WechatMarketing::where($where)->update($data);
                $json_result = ['error' => 0, 'msg' => L('market_edit') . L('success'), 'url' => $this->wechat_ru_id > 0 ? route('seller/wechat/market_list', ['type' => $data['marketing_type']]) : route('admin/wechat/market_list', ['type' => $data['marketing_type']])];
                return response()->json($json_result);
            } else {
                //添加活动
                $data['addtime'] = $this->timeRepository->getGmTime();
                WechatMarketing::insert($data);
                $json_result = ['error' => 0, 'msg' => L('market_add') . L('success'), 'url' => $this->wechat_ru_id > 0 ? route('seller/wechat/market_list', ['type' => $data['marketing_type']]) : route('admin/wechat/market_list', ['type' => $data['marketing_type']])];
                return response()->json($json_result);
            }
        }

        // 显示
        $nowtime = $this->timeRepository->getGmTime();
        $info = [];
        if (!empty($this->cfg['market_id'])) {
            $market_id = $this->cfg['market_id'];
            $info = WechatMarketing::select('id', 'name', 'command', 'logo', 'background', 'starttime', 'endtime', 'config', 'description', 'support')
                ->where(['id' => $market_id, 'marketing_type' => $this->marketing_type, 'wechat_id' => $this->wechat_id])
                ->first();
            $info = $info ? $info->toArray() : [];

            if ($info) {
                $info['starttime'] = isset($info['starttime']) ? $this->timeRepository->getLocalDate('Y-m-d H:i:s', $info['starttime']) : $this->timeRepository->getLocalDate('Y-m-d H:i:s', $nowtime);
                $info['endtime'] = isset($info['endtime']) ? $this->timeRepository->getLocalDate('Y-m-d H:i:s', $info['endtime']) : $this->timeRepository->getLocalDate('Y-m-d H:i:s', $this->timeRepository->getLocalStrtoTime("+1 months", $nowtime));
                $info['prize_arr'] = unserialize($info['config']);
                $info['logo'] = $this->wechatHelperService->get_wechat_image_path($info['logo']);
                $info['background'] = $this->wechatHelperService->get_wechat_image_path($info['background']);
            } else {
                return $this->message('数据不存在', $this->wechat_ru_id > 0 ? route('seller/wechat/market_list', ['type' => $this->marketing_type]) : route('admin/wechat/market_list', ['type' => $this->marketing_type]), 2, $this->wechat_ru_id);
            }
        } else {
            // 默认开始与结束时间
            $info['starttime'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $nowtime);
            $info['endtime'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $this->timeRepository->getLocalStrtoTime("+1 months", $nowtime));

            // 取得最新ID
            $last_id = WechatMarketing::where(['wechat_id' => $this->wechat_id])->orderBy('id', 'desc')->value('id');
            $market_id = !empty($last_id) ? $last_id + 1 : 1;
        }

        // 微信素材所需活动链接
        $info['url'] = route('wechat/market_show', ['type' => 'wall', 'function' => 'wall_user_wechat', 'wall_id' => $market_id, 'wechat_ru_id' => $this->wechat_ru_id]);

        $this->plugin_assign('info', $info);
        return $this->plugin_display('market_edit', $this->_data);
    }

    /**
     * 消息记录列表
     * @param status 审核消息状态
     * @return
     */
    public function marketMessages()
    {
        $market_id = $this->cfg['market_id'];

        $status = request()->input('status', '');

        $function = request()->input('function', '');

        $model = WechatWallMsg::where('wall_id', $market_id);

        $model = $model->with(['wechatWallUser' => function ($query) {
            $query->select('id', 'nickname');
        }]);

        $model = $model->whereHas('wechatMarketing', function ($query) use ($market_id) {
            $query->where('id', $market_id);
        });

        if (empty($status)) {
            $model = $model->where('status', 0);
        }

        $total = $model->count();

        //分页
        $filter['type'] = $this->marketing_type;
        $filter['function'] = $function;
        $filter['id'] = $market_id;
        $filter['status'] = $status;
        $offset = $this->pageLimit($this->wechat_ru_id > 0 ? route('seller/wechat/data_list', $filter) : route('admin/wechat/data_list', $filter), $this->page_num);

        $list = $model->select('id as msg_id', 'user_id', 'content', 'addtime', 'checktime', 'status')
            ->offset($offset['start'])
            ->limit($offset['limit'])
            ->orderBy('addtime', 'DESC')
            ->get();
        $list = $list ? $list->toArray() : [];

        if ($list) {
            foreach ($list as $k => $v) {
                $list[$k] = collect($v)->merge($v['wechat_wall_user'])->except('wechat_wall_user')->all(); // 合并且移除

                if ($v['status'] == 1) {
                    $list[$k]['status'] = L('is_checked');
                    $list[$k]['handler'] = '';
                } else {
                    $list[$k]['status'] = L('no_check');
                    $list[$k]['handler'] = '<a class="button btn-info bg-green check" data-href="' . route('admin/wechat/market_action', ['type' => $this->marketing_type, 'function' => 'messages', 'handler' => 'check', 'market_id' => $market_id, 'msg_id' => $v['msg_id'], 'user_id' => $v['user_id'], 'status' => $status]) . '" href="javascript:;" >' . L('check') . '</a>';
                }
                $list[$k]['addtime'] = $v['addtime'] ? $this->timeRepository->getLocalDate('Y-m-d H:i:s', $v['addtime']) : '';
                $list[$k]['checktime'] = $v['checktime'] ? $this->timeRepository->getLocalDate('Y-m-d H:i:s', $v['checktime']) : '';
            }
        }

        $this->cfg['status'] = $status;
        $this->plugin_assign('config', $this->cfg);
        $this->plugin_assign('page', $this->pageShow($total));
        $this->plugin_assign('list', $list);
        return $this->plugin_display('market_messages', $this->_data);
    }

    /**
     * 参与人员列表
     * @return
     */
    public function marketUsers()
    {
        $market_id = $this->cfg['market_id'];

        $user_id = request()->input('user_id', 0);
        $function = request()->input('function', '');

        // 所有会员
        if (empty($user_id)) {
            $orderby = request()->input('orderby', 'addtime');
            $sort = request()->input('sort', 'DESC');
            // 分页
            $filter['type'] = $this->marketing_type;
            $filter['function'] = $function;
            $filter['id'] = $market_id;
            $filter['orderby'] = $orderby;
            $filter['sort'] = $sort;
            $offset = $this->pageLimit($this->wechat_ru_id > 0 ? route('seller/wechat/data_list', $filter) : route('admin/wechat/data_list', $filter), $this->page_num);

            $model = WechatWallUser::distinct('openid')->where(['wall_id' => $market_id, 'wechat_id' => $this->wechat_id]);

            $total = $model->count();

            $list = $model->select('id', 'nickname', 'sex', 'headimg', 'status', 'addtime', 'checktime', 'wechatname', 'sign_number')
                ->offset($offset['start'])
                ->limit($offset['limit'])
                ->orderBy($orderby, $sort)
                ->orderBy('id', $sort)
                ->get();
            $list = $list ? $list->toArray() : [];

            if ($list) {
                foreach ($list as $k => $v) {
                    if ($v['sex'] == 1) {
                        $list[$k]['sex'] = '男';
                    } elseif ($v['sex'] == 2) {
                        $list[$k]['sex'] = '女';
                    } else {
                        $list[$k]['sex'] = '保密';
                    }
                    if ($v['status'] == 1) {
                        $list[$k]['status'] = L('is_checked');
                        $list[$k]['handler'] = '';
                    } else {
                        $list[$k]['status'] = L('no_check'); // 审核会员
                        $list[$k]['handler'] = '<a class="button btn-info bg-green check" data-href="' . route('admin/wechat/market_action', ['type' => $this->marketing_type, 'function' => 'users', 'handler' => 'check', 'market_id' => $market_id, 'user_id' => $v['id']]) . '" href="javascript:;" >' . L('check') . '</a>';
                    }
                    $list[$k]['nocheck'] = WechatWallMsg::where(['wall_id' => $market_id, 'wechat_id' => $this->wechat_id, 'status' => 0, 'user_id' => $v['id']])->count();
                    $list[$k]['addtime'] = $v['addtime'] ? $this->timeRepository->getLocalDate('Y-m-d H:i:s', $v['addtime']) : '';
                    $list[$k]['checktime'] = $v['checktime'] ? $this->timeRepository->getLocalDate('Y-m-d H:i:s', $v['checktime']) : '';
                }
            }
            $this->plugin_assign('page', $this->pageShow($total));
            $this->plugin_assign('list', $list);
            return $this->plugin_display('market_users', $this->_data);
        } else {
            // 单个会员信息
            // 分页
            $filter['type'] = $this->marketing_type;
            $filter['function'] = $function;
            $filter['wall_id'] = $market_id;
            $filter['user_id'] = $user_id;
            $offset = $this->pageLimit($this->wechat_ru_id > 0 ? route('seller/wechat/data_list', $filter) : route('admin/wechat/data_list', $filter), $this->page_num);

            $total = WechatWallMsg::where(['wall_id' => $market_id, 'wechat_id' => $this->wechat_id, 'user_id' => $user_id])->count();

            $list = WechatWallMsg::select('id', 'content', 'addtime', 'checktime', 'status')
                ->where(['wall_id' => $market_id, 'wechat_id' => $this->wechat_id, 'user_id' => $user_id])
                ->orderBy('addtime', 'DESC')
                ->offset($offset['start'])
                ->limit($offset['limit'])
                ->get();
            $list = $list ? $list->toArray() : [];

            if ($list) {
                foreach ($list as $k => $v) {
                    if ($v['status'] == 1) {
                        $list[$k]['status'] = L('is_checked');
                        $list[$k]['handler'] = '';
                    } else {
                        $list[$k]['status'] = L('no_check'); // 审核会员
                        $list[$k]['handler'] = '<a class="button btn-info bg-green check" data-href="' . route('admin/wechat/market_action', ['type' => $this->marketing_type, 'function' => 'users', 'handler' => 'check', 'market_id' => $market_id, 'msg_id' => $v['id'], 'user_id' => $user_id]) . '" href="javascript:;" >' . L('check') . '</a>';
                    }
                    $list[$k]['addtime'] = $v['addtime'] ? $this->timeRepository->getLocalDate('Y-m-d H:i:s', $v['addtime']) : '';
                    $list[$k]['checktime'] = $v['checktime'] ? $this->timeRepository->getLocalDate('Y-m-d H:i:s', $v['checktime']) : '';
                }
            }

            $this->cfg['user_id'] = $user_id;
            $this->plugin_assign('config', $this->cfg);
            $this->plugin_assign('page', $this->pageShow($total));
            $this->plugin_assign('list', $list);
            return $this->plugin_display('market_users_msg', $this->_data);
        }
    }

    /**
     * 中奖记录列表
     * @return
     */
    public function marketPrizes()
    {
        $market_id = request()->input('id', 0);
        $function = request()->input('function', '');

        // 分页
        $filter['type'] = $this->marketing_type;
        $filter['function'] = $function;
        $filter['id'] = $market_id;

        $offset = $this->pageLimit($this->wechat_ru_id > 0 ? route('seller/wechat/data_list', $filter) : route('admin/wechat/data_list', $filter), $this->page_num);

        $model = WechatPrize::where('market_id', $market_id)
            ->where('wechat_id', $this->wechat_id)
            ->where('activity_type', $this->marketing_type)
            ->where('prize_type', 1);

        $model = $model->with(['getWechatUser' => function ($query) {
            $query->select('openid', 'nickname')
                ->where('wechat_id', $this->wechat_id);
        }]);

        $total = $model->count();

        $list = $model->select('id', 'prize_name', 'issue_status', 'winner', 'dateline', 'openid')
            ->offset($offset['start'])
            ->limit($offset['limit'])
            ->orderBy('dateline', 'DESC')
            ->get();
        $list = $list ? $list->toArray() : [];

        if ($list) {
            foreach ($list as $key => $val) {
                $list[$key] = collect($val)->merge($val['get_wechat_user'])->except('get_wechat_user')->all(); // 合并且移除

                $list[$key]['winner'] = unserialize($val['winner']);
                $list[$key]['dateline'] = $this->timeRepository->getLocalDate(config('shop.time_format'), $val['dateline']);
                if ($val['issue_status'] == 1) {
                    $list[$key]['issue_status'] = L('is_sended');
                    $list[$key]['handler'] = '<a href="javascript:;"  data-href="' . route('admin/wechat/market_action', ['type' => $this->marketing_type, 'handler' => 'winner_issue', 'id' => $val['id'], 'cancel' => 1]) . '" class="btn_region winner_issue" ><i class="fa fa-send-o"></i>' . L('cancle_send') . '</a>';
                } else {
                    $list[$key]['issue_status'] = L('no_send');
                    $list[$key]['handler'] = '<a href="javascript:;"  data-href="' . route('admin/wechat/market_action', ['type' => $this->marketing_type, 'handler' => 'winner_issue', 'id' => $val['id']]) . '" class="btn_region winner_issue" ><i class="fa fa-send-o"></i>' . L('send') . '</a>';
                }
            }
        }

        $this->plugin_assign('page', $this->pageShow($total));
        $this->plugin_assign('list', $list);
        return $this->plugin_display('market_prizes', $this->_data);
    }

    /**
     * 导出中奖记录到Excel
     */
    public function marketExportPrizesLog()
    {
        $market_id = request()->input('id', 0);
        $function = request()->input('function', '');

        if (request()->isMethod('post')) {
            $starttime = request()->input('starttime', '');
            $endtime = request()->input('endtime', '');
            $market_id = request()->input('id', 0);
            if (empty($starttime) || empty($endtime)) {
                return $this->message('选择时间不能为空', null, 2, $this->wechat_ru_id);
            }
            $starttime = $this->timeRepository->getLocalStrtoTime($starttime);
            $endtime = $this->timeRepository->getLocalStrtoTime($endtime);
            if ($starttime > $endtime) {
                return $this->message('开始时间不能大于结束时间', null, 2, $this->wechat_ru_id);
            }

            $model = WechatPrize::where('market_id', $market_id)
                ->where('wechat_id', $this->wechat_id)
                ->where('activity_type', $this->marketing_type)
                ->where('prize_type', 1)
                ->whereBetween('dateline', [$starttime, $endtime]);

            $model = $model->with(['getWechatUser' => function ($query) {
                $query->select('openid', 'nickname')
                    ->where('wechat_id', $this->wechat_id)
                    ->where('subscribe', 1);
            }]);

            $list = $model->select('id', 'prize_name', 'issue_status', 'winner', 'dateline', 'openid')
                ->orderBy('dateline', 'DESC')
                ->get();
            $list = $list ? $list->toArray() : [];

            if ($list) {
                foreach ($list as $key => $val) {
                    $list[$key] = collect($val)->merge($val['get_wechat_user'])->except('get_wechat_user')->all(); // 合并且移除
                    $list[$key]['dateline'] = $this->timeRepository->getLocalDate('Y-m-d H:i', $val['dateline']);
                    $list[$key]['issue_status'] = ($val['issue_status'] == 1) ? '已发放' : '未发放';
                }
                $excel = new Spreadsheet();
                //设置单元格宽度
                $excel->getActiveSheet()->getDefaultColumnDimension()->setAutoSize(true);
                //设置表格的宽度  手动
                $excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
                $excel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
                //设置标题
                $rowVal = ['编号', '微信昵称', '奖品', '是否发放', '中奖时间'];
                foreach ($rowVal as $k => $r) {
                    $excel->getActiveSheet()->getStyleByColumnAndRow($k + 1, 1)->getFont()->setBold(true);//字体加粗
                    $excel->getActiveSheet()->getStyleByColumnAndRow($k + 1, 1)->getAlignment(); //文字居中
                    $excel->getActiveSheet()->setCellValueByColumnAndRow($k + 1, 1, $r);
                }
                //设置当前的sheet索引 用于后续内容操作
                $excel->setActiveSheetIndex(0);
                $objActSheet = $excel->getActiveSheet();
                //设置当前活动的sheet的名称
                $title = "中奖记录";
                $objActSheet->setTitle($title);
                //设置单元格内容
                foreach ($list as $k => $v) {
                    $num = $k + 2;
                    $excel->setActiveSheetIndex(0)
                        //Excel的第A列，uid是你查出数组的键值，下面以此类推
                        ->setCellValue('A' . $num, $v['id'])
                        ->setCellValue('B' . $num, $v['nickname'])
                        ->setCellValue('C' . $num, $v['prize_name'])
                        ->setCellValue('D' . $num, $v['issue_status'])
                        ->setCellValue('E' . $num, $v['dateline']);
                }
                $name = date('Y-m-d'); //设置文件名
                header("Content-Type: application/force-download");
                header("Content-Type: application/octet-stream");
                header("Content-Type: application/download");
                header("Content-Transfer-Encoding:utf-8");
                header("Pragma: no-cache");
                header('Content-Type: application/vnd.ms-e xcel');
                header('Content-Disposition: attachment;filename="' . $title . '_' . urlencode($name) . '.xls"');
                header('Cache-Control: max-age=0');
                $objWriter = IOFactory::createWriter($excel, 'Xls');
                $objWriter->save('php://output');
                exit;
            } else {
                return $this->message('该时间段没有要导出的数据', null, 2, $this->wechat_ru_id);
            }
        }

        return $this->wechat_ru_id > 0 ? redirect()->route('seller/wechat/data_list', ['type' => $this->marketing_type, 'function' => 'prizes']) : redirect()->route('admin/wechat/data_list', ['type' => $this->marketing_type, 'function' => 'prizes']);
    }

    /**
     * 活动二维码
     * @return
     */
    public function marketQrcode()
    {
        $market_id = request()->input('id', 0);

        if (!empty($market_id)) {
            $url = route('wechat/market_show', ['type' => 'wall', 'function' => 'wall_user_wechat', 'wall_id' => $market_id, 'wechat_ru_id' => $this->wechat_ru_id]);

            $wall = WechatMarketing::select('command', 'qrcode')
                ->where(['id' => $market_id, 'marketing_type' => $this->marketing_type, 'wechat_id' => $this->wechat_id])
                ->first();
            $wall = $wall ? $wall->toArray() : [];

            // 生成的文件位置
            $path = storage_public('data/attached/wall/');

            // 水印logo
            $water_logo = public_path('assets/mobile/img/shop_app_icon.png');

            // 输出二维码路径
            $qrcode = $path . 'wall_qrcode_' . $market_id . '.png';

            if (!is_dir($path)) {
                @mkdir($path);
            }

            if (!file_exists($qrcode)) {
                $qrCode = new QrCode($url);

                $qrCode->setSize(357);
                $qrCode->setMargin(15);
                $qrCode->setLogoPath($water_logo); // 默认居中
                $qrCode->setLogoWidth(60);
                $qrCode->writeFile($qrcode); // 保存二维码
            }

            // 同步镜像上传到OSS
            $this->ossMirror('data/attached/wall/' . basename($qrcode), true);

            $qrcode_url = $this->wechatHelperService->get_wechat_image_path('data/attached/wall/' . basename($qrcode)) . '?t=' . time();
            $this->cfg['qrcode_url'] = $qrcode_url;
            $this->cfg['command'] = $wall['command'];
        }

        $this->plugin_assign('config', $this->cfg);
        return $this->plugin_display('market_qrcode', $this->_data);
    }

    /**
     * 上墙信息，参与人数数量
     * @param  $wall_id
     * @return
     */
    private function get_user_msg_count($wall_id)
    {
        $model = WechatWallUser::from('wechat_wall_user as u')
            ->leftJoin('wechat_wall_msg as m', 'm.user_id', '=', 'u.id')
            ->where('m.wall_id', $wall_id)
            ->where('u.wechat_id', $this->wechat_id);

        $msg_count = $model->count('m.id');
        $user_count = $model->distinct()->count('u.id');

        return ['msg_count' => $msg_count, 'user_count' => $user_count];
    }

    /**
     * 行为操作
     * @param handler 例如 审核 删除
     */
    public function executeAction()
    {
        if (request()->isMethod('post')) {
            $json_result = ['error' => 0, 'msg' => '', 'url' => ''];

            $handler = request()->input('handler', '');
            $function = request()->input('function', '');
            $market_id = request()->input('market_id', 0);

            $msg_id = request()->input('msg_id', 0);
            $user_id = request()->input('user_id', 0);
            // 审核消息
            if (request()->isMethod('post') && $handler && $handler == 'check') {
                $checktime = $this->timeRepository->getGmTime();
                $data = ['status' => 1, 'checktime' => $checktime];
                //用户审核
                if (!empty($market_id) && !empty($user_id) && empty($msg_id)) {
                    WechatWallUser::where(['wall_id' => $market_id, 'wechat_id' => $this->wechat_id, 'id' => $user_id, 'status' => 0])->update($data);
                    $json_result['msg'] = '用户审核成功';
                    $json_result['url'] = $this->wechat_ru_id > 0 ? route('seller/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function, 'id' => $market_id]) : route('admin/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function, 'id' => $market_id]);
                    return response()->json($json_result);
                }

                //留言审核
                if (!empty($market_id) && !empty($user_id) && !empty($msg_id)) {
                    WechatWallMsg::where(['wall_id' => $market_id, 'wechat_id' => $this->wechat_id, 'user_id' => $user_id, 'id' => $msg_id, 'status' => 0])->update($data);
                    //审核用户
                    WechatWallUser::where(['wall_id' => $market_id, 'wechat_id' => $this->wechat_id, 'id' => $user_id, 'status' => 0])->update($data);

                    $json_result['msg'] = '留言审核成功';
                    if (isset($_GET['status'])) {
                        $status = request()->input('status');
                        $json_result['url'] = $this->wechat_ru_id > 0 ? route('seller/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function, 'id' => $market_id, 'status' => $status]) : route('admin/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function, 'id' => $market_id, 'status' => $status]);
                    }
                    return response()->json($json_result);
                }
            }

            // 移除审核
            if (request()->isMethod('post') && $handler && $handler == 'move') {
                $data = ['status' => 0, 'checktime' => 0];

                // 移除用户审核
                if (!empty($market_id) && !empty($user_id) && empty($msg_id)) {
                    WechatWallUser::where(['wall_id' => $market_id, 'wechat_id' => $this->wechat_id, 'id' => $user_id, 'status' => 1])->update($data);
                    $json_result['msg'] = '移除审核成功';
                    $json_result['url'] = $this->wechat_ru_id > 0 ? route('seller/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function, 'id' => $market_id]) : route('admin/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function, 'id' => $market_id]);
                    return response()->json($json_result);
                }

                // 留言移除审核
                if (!empty($market_id) && !empty($user_id) && !empty($msg_id)) {
                    WechatWallMsg::where(['wall_id' => $market_id, 'wechat_id' => $this->wechat_id, 'user_id' => $user_id, 'id' => $msg_id, 'status' => 1])->update($data);
                    // 移除审核用户
                    WechatWallUser::where(['wall_id' => $market_id, 'wechat_id' => $this->wechat_id, 'id' => $user_id, 'status' => 1])->update($data);

                    $json_result['msg'] = '移除审核成功';
                    if (isset($_GET['status'])) {
                        $status = request()->input('status');
                        $json_result['url'] = $this->wechat_ru_id > 0 ? route('seller/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function, 'id' => $market_id, 'status' => $status]) : route('admin/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function, 'id' => $market_id, 'status' => $status]);
                        return response()->json($json_result);
                    }
                    return response()->json($json_result);
                }
            }

            // 删除消息、会员数据
            if (request()->isMethod('post') && $handler && $handler == 'data_delete') {

                // 删除消息记录
                if (!empty($market_id) && !empty($msg_id)) {
                    WechatWallMsg::where(['wall_id' => $market_id, 'wechat_id' => $this->wechat_id, 'id' => $msg_id])->delete();
                    $json_result['msg'] = '删除消息成功';
                    return response()->json($json_result);
                }
                // 删除会员以及消息数据
                if (!empty($market_id) && !empty($user_id) && empty($msg_id)) {
                    WechatWallUser::where(['wall_id' => $market_id, 'wechat_id' => $this->wechat_id, 'id' => $user_id])->delete();
                    WechatWallMsg::where(['wall_id' => $market_id, 'wechat_id' => $this->wechat_id, 'user_id' => $user_id])->delete();
                    $json_result['msg'] = '删除会员以及消息成功';
                    return response()->json($json_result);
                }
            }

            // 批量处理消息、会员、会员消息 的审核、移除
            if (request()->isMethod('post') && $handler && $handler == 'batch_checking') {
                $check_id = request()->input('check_id', 0); // 0 移除，1 审核
                $messagelist = request()->input('id');
                $status = request()->input('status', '');

                $where_status = ($check_id == 1) ? 0 : 1;
                $checktime = ($check_id == 1) ? $this->timeRepository->getGmTime() : 0;
                $data = ['status' => $check_id, 'checktime' => $checktime];
                // 批量消息
                if ($function == 'messages' && !empty($messagelist) && is_array($messagelist)) {
                    // 批量处理一次不能超过50
                    $num = count($messagelist);
                    if ($num > 50) {
                        return $this->message('批量处理数量一次不能超过50', null, 2, $this->wechat_ru_id);
                    }
                    foreach ($messagelist as $v) {
                        $where = [
                            'wall_id' => $market_id,
                            'wechat_id' => $this->wechat_id,
                            'id' => $v,
                            'status' => $where_status
                        ];
                        WechatWallMsg::where($where)->update($data);
                    }
                    return $this->message('批量处理成功', $this->wechat_ru_id > 0 ? route('seller/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function, 'id' => $market_id, 'status' => $status]) : route('admin/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function, 'id' => $market_id, 'status' => $status]));
                }
                // 批量会员
                $userlist = request()->input('user_id');
                if ($function == 'users' && !empty($userlist) && is_array($userlist)) {
                    // 批量处理一次不能超过50
                    $num = count($userlist);
                    if ($num > 50) {
                        return $this->message('批量处理数量一次不能超过50', null, 2, $this->wechat_ru_id);
                    }
                    foreach ($userlist as $v) {
                        $where = [
                            'wall_id' => $market_id,
                            'wechat_id' => $this->wechat_id,
                            'id' => $v,
                            'status' => $where_status
                        ];
                        WechatWallUser::where($where)->update($data);
                    }
                    return $this->message('批量处理成功', $this->wechat_ru_id > 0 ? route('seller/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function, 'id' => $market_id]) : route('admin/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function, 'id' => $market_id]));
                }


                // 批量单一会员下消息
                $usermsglist = request()->input('user_msg_id');
                if ($function == 'users' && !empty($user_id) && !empty($usermsglist) && is_array($usermsglist)) {
                    // 批量处理一次不能超过50
                    $num = count($usermsglist);
                    if ($num > 50) {
                        return $this->message('批量处理数量一次不能超过50', null, 2, $this->wechat_ru_id);
                    }
                    foreach ($usermsglist as $v) {
                        $where = [
                            'wall_id' => $market_id,
                            'wechat_id' => $this->wechat_id,
                            'id' => $v,
                            'status' => $where_status,
                            'user_id' => $user_id
                        ];
                        WechatWallMsg::where($where)->update($data);
                    }
                    return $this->message('批量处理成功', $this->wechat_ru_id > 0 ? route('seller/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function, 'id' => $market_id, 'user_id' => $user_id]) : route('admin/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function, 'id' => $market_id, 'user_id' => $user_id]));
                }
            }

            // 发放奖品标记
            if (request()->isMethod('post') && $handler && $handler == 'winner_issue') {
                $id = request()->input('id', 0);
                $cancel = request()->input('cancel');

                if (!empty($id)) {
                    if (!empty($cancel)) {
                        $data['issue_status'] = 0;
                        WechatPrize::where(['id' => $id, 'wechat_id' => $this->wechat_id])->data($data);
                        $json_result['msg'] = '已取消';
                        return response()->json($json_result);
                    } else {
                        $data['issue_status'] = 1;
                        WechatPrize::where(['id' => $id, 'wechat_id' => $this->wechat_id])->update($data);
                        $json_result['msg'] = '发放标记成功';
                        return response()->json($json_result);
                    }
                }
            }

            // 删除中奖记录
            if (request()->isMethod('post') && $handler && $handler == 'winner_del') {
                $id = request()->input('id', 0);
                if (!empty($id)) {
                    WechatPrize::where(['id' => $id, 'wechat_id' => $this->wechat_id])->delete();
                    $json_result['msg'] = '删除成功';
                    return response()->json($json_result);
                }
            }

            exit();
        }
    }
}
