<?php

namespace App\Plugins\Market\Redpack;

use App\Http\Controllers\Wechat\PluginController;
use App\Libraries\Form;
use App\Libraries\Wechat;
use App\Models\WechatMarketing;
use App\Models\WechatRedpackAdvertice;
use App\Models\WechatRedpackLog;
use App\Models\WechatUser;
use App\Repositories\Common\DscRepository;
use Endroid\QrCode\QrCode;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * 现金红包后台模块
 * Class Admin
 * @package App\Plugins\Market\Redpack
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

        load_helper(['payment']);

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
                $config = $this->get_market_config($v['id'], $this->marketing_type);
                $list[$k]['hb_type'] = $config['hb_type'] == 1 ? L('group_redpack') : L('normal_redpack');
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
        if (request()->isMethod('POST')) {
            $json_result = ['error' => 0, 'msg' => '', 'url' => '']; // 初始化通知信息

            $id = request()->input('id', 0);
            $data = request()->input('data', '');
            $config = request()->input('config', '');

            // 检查是否安装配置微信支付
            $payment = get_payment('wxpay');
            if (empty($payment)) {
                $json_result = ['error' => 1, 'msg' => '请先安装并配置微信支付'];
                return response()->json($json_result);
            }
            // act_name 字段必填,并且少于32个字符
            if (empty($data['name']) || strlen($data['name']) >= 32) {
                $json_result = ['error' => 1, 'msg' => '活动名称必填，并且须少于32个字符'];
                return response()->json($json_result);
            }
            // 红包金额必须在1元~200元之间
            if ($config['base_money'] < 1 || $config['base_money'] > 200) {
                $json_result = ['error' => 1, 'msg' => '红包金额必须在1元~200元之间，请重新填写'];
                return response()->json($json_result);
            }
            // 红包发放总人数 普通红包固定为1，裂变红包至少为3
            if ($config['hb_type'] == 0 && $config['total_num'] != 1) {
                $json_result = ['error' => 1, 'msg' => '红包发放总人数 普通红包固定为1人, 请重新填写'];
                return response()->json($json_result);
            }
            if ($config['hb_type'] == 1 && $config['total_num'] < 3) {
                $json_result = ['error' => 1, 'msg' => '红包发放总人数 裂变红包至少为3人, 请重新填写'];
                return response()->json($json_result);
            }
            // nick_name 字段必填，并且少于16字符
            if (empty($config['nick_name']) || strlen($config['nick_name']) >= 16) {
                $json_result = ['error' => 1, 'msg' => '提供方名称必填，并且须少于16个字符'];
                return response()->json($json_result);
            }
            // send_name 字段为必填，并且少于32字符
            if (empty($config['send_name']) || strlen($config['send_name']) >= 32) {
                $json_result = ['error' => 1, 'msg' => '红包发送方名称必填，并且须少于32个字符'];
                return response()->json($json_result);
            }
            $data['wechat_id'] = $this->wechat_id;
            $data['marketing_type'] = request()->input('marketing_type');
            $data['starttime'] = $this->timeRepository->getLocalStrtoTime($data['starttime']);
            $data['endtime'] = $this->timeRepository->getLocalStrtoTime($data['endtime']);

            $data['status'] = $this->wechatService->get_status($data['starttime'], $data['endtime']); // 活动状态

            $background_path = request()->input('background_path', '');
            // 编辑图片处理
            $background_path = $this->dscRepository->editUploadImage($background_path);

            // 上传背景图片
            $file = request()->file('background');
            if ($file && $file->isValid()) {
                // 验证文件大小
                if ($file->getSize() > 2 * 1024 * 1024) {
                    $json_result = ['error' => 1, 'msg' => L('file_size_limit')];
                    return response()->json($json_result);
                }
                // 验证文件格式
                if (!in_array($file->getMimeType(), ['image/jpeg', 'image/png'])) {
                    $json_result = ['error' => 1, 'msg' => L('not_file_type')];
                    return response()->json($json_result);
                }
                $result = $this->upload('data/attached/redpack', true);
                if ($result['error'] > 0) {
                    $json_result = ['error' => 1, 'msg' => $result['message']];
                    return response()->json($json_result);
                }
                $data['background'] = 'data/attached/redpack/' . $result['file_name'];
            } else {
                $data['background'] = $background_path;
            }

            // 验证
            $form = new Form();
            if (!$form->isEmpty($data['background'], 1)) {
                $json_result = ['error' => 1, 'msg' => L('please_upload')];
                return response()->json($json_result);
            }

            // oss图片处理
            if ($this->config['open_oss'] == 1) {
                $bucket_info = $this->dscRepository->getBucketInfo();
                $bucket_info['endpoint'] = empty($bucket_info['endpoint']) ? $bucket_info['outside_site'] : $bucket_info['endpoint'];
                $http = rtrim($bucket_info['endpoint'], '/') . '/';
                $data['background'] = str_replace($http, '', $data['background']);
                // 编辑时 删除oss图片
                $background_path = str_replace($http, '', $background_path);
            }
            // 路径转换
            if (strtolower(substr($data['background'], 0, 4)) == 'http') {
                $data['background'] = str_replace(url('/'), '', $data['background']);
                // 编辑时 删除原图片
                $background_path = str_replace(url('/'), '', $background_path);
            }

            // 生成证书
            if (!empty($payment) && $payment['sslcert'] && $payment['sslkey']) {
                $file_path = storage_path('app/certs/wxpay/');
                $this->file_write($file_path, "index.html", "");
                $this->file_write($file_path, md5($payment['wxpay_mchid']) . "_apiclient_cert.pem", $payment['sslcert']);
                $this->file_write($file_path, md5($payment['wxpay_mchid']) . "_apiclient_key.pem", $payment['sslkey']);
            }
            //配置
            if ($config) {
                $data['config'] = serialize($config);
            }
            // 不保存默认空图片
            if (strpos($data['background'], 'no_image') !== false) {
                unset($data['background']);
            }
            //更新活动
            if ($id) {
                // 删除原背景图片
                if ($data['background'] && $background_path != $data['background']) {
                    $background_path = strpos($background_path, 'no_image') == false ? $background_path : '';  // 且不删除默认空图片
                    $this->remove($background_path);
                }
                $where = [
                    'id' => $id,
                    'wechat_id' => $this->wechat_id,
                    'marketing_type' => $data['marketing_type']
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
        $market_id = isset($this->cfg['market_id']) ? $this->cfg['market_id'] : '';
        if (!empty($market_id)) {
            $info = WechatMarketing::select('id', 'name', 'command', 'logo', 'background', 'starttime', 'endtime', 'config', 'description', 'support')
                ->where(['id' => $market_id, 'marketing_type' => $this->marketing_type, 'wechat_id' => $this->wechat_id])
                ->first();
            $info = $info ? $info->toArray() : [];

            if ($info) {
                $info['starttime'] = isset($info['starttime']) ? $this->timeRepository->getLocalDate('Y-m-d H:i:s', $info['starttime']) : $this->timeRepository->getLocalDate('Y-m-d H:i:s', $nowtime);
                $info['endtime'] = isset($info['endtime']) ? $this->timeRepository->getLocalDate('Y-m-d H:i:s', $info['endtime']) : $this->timeRepository->getLocalDate('Y-m-d H:i:s', $this->timeRepository->getLocalStrtoTime("+1 months", $nowtime));
                $info['config'] = unserialize($info['config']);
                $info['background'] = $this->wechatHelperService->get_wechat_image_path($info['background']);
            } else {
                return $this->message('数据不存在', $this->wechat_ru_id > 0 ? route('seller/wechat/market_list', ['type' => $this->marketing_type]) : route('admin/wechat/market_list', ['type' => $this->marketing_type]), 2);
            }
        } else {
            // 默认开始与结束时间
            $info['starttime'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $nowtime);
            $info['endtime'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $this->timeRepository->getLocalStrtoTime("+1 months", $nowtime));

            $info['config']['hb_type'] = 0;
            $info['config']['money_extra'] = 0;
            $info['config']['total_num'] = 1;

            // 取得最新ID
            $last_id = WechatMarketing::where(['wechat_id' => $this->wechat_id])->orderBy('id', 'desc')->value('id');
            $market_id = !empty($last_id) ? $last_id + 1 : 1;
        }

        // 微信素材所需活动链接
        $info['url'] = route('wechat/market_show', ['type' => 'redpack', 'function' => 'activity', 'market_id' => $market_id, 'wechat_ru_id' => $this->wechat_ru_id]);

        $this->plugin_assign('info', $info);
        return $this->plugin_display('market_edit', $this->_data);
    }

    /**
     * 摇一摇广告记录列表
     * @param market_id 活动ID
     * @param function 访问类型 如 shake
     * @param handler 操作类型 如 编辑
     * @return
     */
    public function marketShake()
    {
        $market_id = $this->cfg['market_id'];

        $function = request()->input('function', '');
        $handler = request()->input('handler', '');

        // 添加与编辑广告
        if ($handler && $handler == 'edit') {
            // 提交
            if (request()->isMethod('POST')) {
                $json_result = ['error' => 0, 'msg' => '', 'url' => '']; // 初始化通知信息

                $id = request()->input('advertice_id', 0);
                $data = request()->input('advertice', '');
                $icon_path = request()->input('icon_path', '');
                // 验证数据
                $form = new Form();
                if (!$form->isEmpty($data['content'], 1)) {
                    $json_result = ['error' => 1, 'msg' => L('advertice_content')];
                    return response()->json($json_result);
                }
                // 验证url格式
                if (substr($data['url'], 0, 4) !== 'http') {
                    $json_result = ['error' => 1, 'msg' => L('link_err')];
                    return response()->json($json_result);
                }

                $icon_path = $this->dscRepository->editUploadImage($icon_path);

                // 上传图片处理
                $file = request()->file('icon');
                if ($file && $file->isValid()) {
                    // 验证文件大小
                    if ($file->getSize() > 2 * 1024 * 1024) {
                        $json_result = ['error' => 1, 'msg' => L('file_size_limit')];
                        return response()->json($json_result);
                    }
                    // 验证文件格式
                    if (!in_array($file->getMimeType(), ['image/jpeg', 'image/png'])) {
                        $json_result = ['error' => 1, 'msg' => L('not_file_type')];
                        return response()->json($json_result);
                    }
                    $result = $this->upload('data/attached/redpack', true);
                    if ($result['error'] > 0) {
                        $json_result = ['error' => 1, 'msg' => $result['message']];
                        return response()->json($json_result);
                    }
                    $data['icon'] = 'data/attached/redpack/' . $result['file_name'];
                    $data['file_name'] = $result['file_name'];
                    $data['size'] = $result['size'];
                } else {
                    $data['icon'] = $icon_path;
                }

                if (!$form->isEmpty($data['icon'], 1)) {
                    $json_result = ['error' => 1, 'msg' => L('please_upload')];
                    return response()->json($json_result);
                }

                // oss图片处理
                if ($this->config['open_oss'] == 1) {
                    $bucket_info = $this->dscRepository->getBucketInfo();
                    $bucket_info['endpoint'] = empty($bucket_info['endpoint']) ? $bucket_info['outside_site'] : $bucket_info['endpoint'];
                    $http = rtrim($bucket_info['endpoint'], '/') . '/';
                    $data['icon'] = str_replace($http, '', $data['icon']);
                    // 编辑时 删除oss图片
                    $icon_path = str_replace($http, '', $icon_path);
                }
                // 路径转换
                if (strtolower(substr($data['icon'], 0, 4)) == 'http') {
                    $data['icon'] = str_replace(url('/'), '', $data['icon']);
                    // 编辑时 删除原图片
                    $icon_path = str_replace(url('/'), '', $icon_path);
                }

                // 不保存默认空图片
                if (strpos($data['icon'], 'no_image') !== false) {
                    unset($data['icon']);
                }
                // 更新
                if ($id) {
                    // 删除原图片
                    if ($data['icon'] && $icon_path != $data['icon']) {
                        $icon_path = strpos($icon_path, 'no_image') == false ? $icon_path : '';  // 不删除默认空图片
                        $this->remove($icon_path);
                    }
                    $where = ['id' => $id, 'wechat_id' => $this->wechat_id];
                    WechatRedpackAdvertice::where($where)->update($data);

                    return response()->json(['error' => 0, 'msg' => L('wechat_editor') . L('success')]);
                } else {
                    $data['wechat_id'] = $this->wechat_id;
                    WechatRedpackAdvertice::insert($data);

                    return response()->json(['error' => 0, 'msg' => L('add') . L('success')]);
                }
            }
            // 显示单个广告信息
            $advertices_id = request()->input('advertice_id', 0);
            if ($advertices_id) {
                $condition = [
                    'id' => $advertices_id,
                    'wechat_id' => $this->wechat_id
                ];
                $info = WechatRedpackAdvertice::where($condition)->first();
                $info = $info ? $info->toArray() : [];
                if (empty($info)) {
                    return $this->message('数据不存在', $this->wechat_ru_id > 0 ? route('seller/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function, 'id' => $market_id]) : route('admin/wechat/data_list', ['type' => $this->marketing_type, 'function' => $function, 'id' => $market_id]), 2);
                }
                $info['icon'] = $this->wechatHelperService->get_wechat_image_path($info['icon']);
            }
            $where = [
                'id' => $market_id,
                'wechat_id' => $this->wechat_id,
                'marketing_type' => $this->marketing_type,
            ];
            $info['act_name'] = WechatMarketing::where($where)->value('name');
            $this->plugin_assign('act_name', $info['act_name']);

            $this->plugin_assign('info', $info);
            return $this->plugin_display('market_shake_edit', $this->_data);
        } else {
            // 广告列表显示
            // 分页
            $filter['type'] = $this->marketing_type;
            $filter['function'] = $function;
            $filter['id'] = $market_id;
            $offset = $this->pageLimit($this->wechat_ru_id > 0 ? route('seller/wechat/data_list', $filter) : route('admin/wechat/data_list', $filter), $this->page_num);

            $condition = [
                'market_id' => $market_id,
                'wechat_id' => $this->wechat_id
            ];
            $model = WechatRedpackAdvertice::where($condition);
            $total = $model->count();

            $list = $model->offset($offset['start'])
                ->limit($offset['limit'])
                ->orderBy('id', 'desc')
                ->get();
            $list = $list ? $list->toArray() : [];

            if ($list) {
                foreach ($list as $key => $value) {
                    $list[$key]['icon'] = $this->wechatHelperService->get_wechat_image_path($value['icon']);
                }
            }

            // 当前活动名称
            $where = [
                'id' => $market_id,
                'wechat_id' => $this->wechat_id,
                'marketing_type' => $this->marketing_type
            ];
            $act_name = WechatMarketing::where($where)->value('name');
            $this->plugin_assign('act_name', $act_name);

            $this->plugin_assign('page', $this->pageShow($total));
            $this->plugin_assign('list', $list);
            return $this->plugin_display('market_shake', $this->_data);
        }
    }

    /**
     * 活动记录
     * @return
     */
    public function marketLogList()
    {
        $market_id = $this->cfg['market_id'];

        $function = request()->input('function', '');
        $handler = request()->input('handler', '');

        if ($handler && $handler == 'info') {
            // 显示单条记录
            $log_id = request()->input('log_id', 0);
            $info = [];
            if ($log_id) {
                $condition = [
                    'id' => $log_id,
                    'wechat_id' => $this->wechat_id
                ];
                $info = WechatRedpackLog::where($condition)->first();
                $info = $info ? $info->toArray() : [];

                $info['nickname'] = WechatUser::where(['wechat_id' => $this->wechat_id, 'openid' => $info['openid']])->value('nickname');
                $info['time'] = !empty($info['time']) ? local_date('Y-m-d H:i:s', $info['time']) : '';

                // 接口查询更多详情
                if ($info['hassub'] == 1) {
                    $payment = get_payment('wxpay');
                    $options = [
                        'appid' => $payment['wxpay_appid'], //填写高级调用功能的app id
                        'mch_id' => $payment['wxpay_mchid'], //微信支付商户号
                        'key' => $payment['wxpay_key'] //微信支付API密钥
                    ];
                    $WxHongbao = new Wechat($options);
                    // 证书
                    $sslcert = storage_path('app/certs/wxpay/') . md5($options['mch_id']) . "_apiclient_cert.pem";
                    $sslkey = storage_path('app/certs/wxpay/') . md5($options['mch_id']) . "_apiclient_key.pem";
                    // 请求参数
                    $query_params = [
                        'mch_billno' => $info['mch_billno'],
                        'hb_type' => $info['hb_type'] == 1 ? 'GROUP' : 'NORMAL',
                    ];

                    $responseObj = $WxHongbao->QueryRedpack($query_params, $sslcert, $sslkey);
                    if ($responseObj) {
                        // logResult($responseObj);
                        $return_code = $responseObj->return_code;
                        $result_code = $responseObj->result_code;

                        if ($return_code == 'SUCCESS') {
                            if ($result_code == 'SUCCESS') {
                                // 显示返回的信息
                                $info['status'] = $responseObj->status; // 红包状态
                                $info['total_num'] = $responseObj->total_num; // 红包个数
                                $info['hb_type'] = $responseObj->hb_type; // 红包类型
                                $info['openid'] = $responseObj->openid; // 领取红包的Openid
                                $info['send_time'] = $responseObj->send_time; // 发送时间
                                $info['rcv_time'] = $responseObj->rcv_time;// 接收时间
                            } else {
                                // return $responseObj->return_msg;
                                // return response()->json(array('status' => 0, 'msg' => $responseObj->return_msg));
                            }
                        } else {
                            // return $responseObj->return_msg;
                            // return response()->json(array('status' => 0, 'msg' => $responseObj->return_msg));
                        }
                    }
                }

                $info['hb_type'] = $info['hb_type'] == 1 ? '裂变红包' : '普通红包';
                $info['hassub'] = $info['hassub'] == 1 ? '已领取' : '未领取';
            }

            $this->plugin_assign('info', $info);
            return $this->plugin_display('market_log_info', $this->_data);
        } else {
            // 记录列表
            // 分页
            $filter['type'] = $this->marketing_type;
            $filter['function'] = $function;
            $filter['id'] = $market_id;
            $offset = $this->pageLimit($this->wechat_ru_id > 0 ? route('seller/wechat/data_list', $filter) : route('admin/wechat/data_list', $filter), $this->page_num);
            $where = [
                'wechat_id' => $this->wechat_id,
                'market_id' => $market_id
            ];

            $model = WechatRedpackLog::where($where);

            $total = $model->count();
            $list = $model->offset($offset['start'])
                ->limit($offset['limit'])
                ->orderBy('id', 'desc')
                ->get();
            $list = $list ? $list->toArray() : [];

            if ($list) {
                foreach ($list as $key => $value) {
                    $list[$key]['nickname'] = WechatUser::where(['wechat_id' => $this->wechat_id, 'openid' => $value['openid']])->value('nickname');
                    $list[$key]['time'] = !empty($value['time']) ? local_date('Y-m-d H:i:s', $value['time']) : '';
                }
            }

            // 当前活动名称
            $where = [
                'id' => $market_id,
                'wechat_id' => $this->wechat_id,
                'marketing_type' => $this->marketing_type
            ];
            $act_name = WechatMarketing::where($where)->value('name');
            $this->plugin_assign('act_name', $act_name);

            $this->plugin_assign('market_id', $market_id);
            $this->plugin_assign('page', $this->pageShow($total));
            $this->plugin_assign('list', $list);
            return $this->plugin_display('market_log_list', $this->_data);
        }
    }

    /**
     * 导出记录到Excel
     */
    public function marketExportRedpackLog()
    {
        if (request()->isMethod('POST')) {
            $starttime = request()->input('starttime', '');
            $endtime = request()->input('endtime', '');
            $market_id = $this->cfg['market_id'];
            if (empty($starttime) || empty($endtime)) {
                return $this->message('选择时间不能为空', null, 2, $this->wechat_ru_id);
            }
            $starttime = $this->timeRepository->getLocalStrtoTime($starttime);
            $endtime = $this->timeRepository->getLocalStrtoTime($endtime);
            if ($starttime > $endtime) {
                return $this->message('开始时间不能大于结束时间', null, 2, $this->wechat_ru_id);
            }

            $model = WechatRedpackLog::where('market_id', $market_id)
                ->where('wechat_id', $this->wechat_id)
                ->whereBetween('time', [$starttime, $endtime]);

            $model = $model->with(['getWechatUser' => function ($query) {
                $query->select('openid', 'nickname')
                    ->where('wechat_id', $this->wechat_id);
            }]);

            $list = $model->select('id', 'openid', 'hb_type', 'hassub', 'money', 'time', 'notify_data')
                ->orderBy('time', 'DESC')
                ->get();
            $list = $list ? $list->toArray() : [];

            if ($list) {
                foreach ($list as $key => $val) {
                    $list[$key] = collect($val)->merge($val['get_wechat_user'])->except('get_wechat_user')->all(); // 合并且移除
                    $list[$key]['time'] = $this->timeRepository->getLocalDate('Y-m-d H:i', $val['time']);
                    $list[$key]['hassub'] = $val['hassub'] == 1 ? '已领取' : '未领取';
                    $list[$key]['hb_type'] = $val['hb_type'] == 0 ? '普通红包' : '裂变红包';
                }
                $excel = new Spreadsheet();
                //设置单元格宽度
                $excel->getActiveSheet()->getDefaultColumnDimension()->setAutoSize(true);
                //设置表格的宽度  手动
                $excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                $excel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
                $excel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
                $excel->getActiveSheet()->getColumnDimension('F')->setWidth(25);
                //设置标题
                $rowVal = [
                    0 => 'id',
                    1 => '微信昵称',
                    2 => '红包类型',
                    3 => '是否领取',
                    4 => '领取金额（元）',
                    5 => '领取时间',
                ];
                foreach ($rowVal as $k => $r) {
                    $excel->getActiveSheet()->getStyleByColumnAndRow($k + 1, 1)->getFont()->setBold(true);//字体加粗
                    $excel->getActiveSheet()->getStyleByColumnAndRow($k + 1, 1)->getAlignment(); //文字居中
                    $excel->getActiveSheet()->setCellValueByColumnAndRow($k + 1, 1, $r);
                }
                //设置当前的sheet索引 用于后续内容操作
                $excel->setActiveSheetIndex(0);
                $objActSheet = $excel->getActiveSheet();
                //设置当前活动的sheet的名称
                $title = "红包领取记录";
                $objActSheet->setTitle($title);
                //设置单元格内容
                foreach ($list as $k => $v) {
                    $num = $k + 2;
                    $excel->setActiveSheetIndex(0)
                        //Excel的第A列，uid是你查出数组的键值，下面以此类推
                        ->setCellValue('A' . $num, $v['id'])
                        ->setCellValue('B' . $num, $v['nickname'])
                        ->setCellValue('C' . $num, $v['hb_type'])
                        ->setCellValue('D' . $num, $v['hassub'])
                        ->setCellValue('E' . $num, $v['money'])
                        ->setCellValue('F' . $num, $v['time']);
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

        return $this->wechat_ru_id > 0 ? redirect()->route('seller/wechat/data_list', ['type' => $this->marketing_type, 'function' => 'log_list']) : redirect()->route('admin/wechat/data_list', ['type' => $this->marketing_type, 'function' => 'log_list']);
    }

    /**
     * 设置分享 功能待定
     * @return
     */
    public function marketShare_setting()
    {
        return $this->plugin_display('market_share_setting', $this->_data);
    }


    /**
     * 活动二维码
     * @return
     */
    public function marketQrcode()
    {
        $market_id = request()->input('id', 0);

        if (!empty($market_id)) {
            $url = route('wechat/market_show', ['type' => 'redpack', 'function' => 'activity', 'market_id' => $market_id, 'wechat_ru_id' => $this->wechat_ru_id]);

            $info = WechatMarketing::select('qrcode')
                ->where(['id' => $market_id, 'marketing_type' => $this->marketing_type, 'wechat_id' => $this->wechat_id])
                ->first();
            $info = $info ? $info->toArray() : [];

            // 生成二维码
            // 生成的文件位置
            $path = storage_public('data/attached/redpack/');
            // 水印logo
            $water_logo = public_path('assets/mobile/img/shop_app_icon.png');

            // 输出二维码路径
            $qrcode = $path . 'M8' . $market_id . '.png';

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
            $this->ossMirror('data/attached/redpack/' . basename($qrcode), true);

            $qrcode_url = $this->wechatHelperService->get_wechat_image_path('data/attached/redpack/' . basename($qrcode)) . '?t=' . time();
            $this->cfg['qrcode_url'] = $qrcode_url;
        }

        $this->plugin_assign('config', $this->cfg);
        return $this->plugin_display('market_qrcode', $this->_data);
    }

    /**
     * 将反序列化后的配置信息转换成数组格式
     * @param  [int] $id
     * @param  [string] $marketing_type
     * @return [array] array
     */
    public function get_market_config($id)
    {
        $info = WechatMarketing::select('config')
            ->where(['id' => $id, 'marketing_type' => $this->marketing_type, 'wechat_id' => $this->wechat_id])
            ->first();
        $info = $info ? $info->toArray() : [];
        return isset($info['config']) ? unserialize($info['config']) : '';
    }

    /**
     * 行为操作
     *
     */
    public function executeAction()
    {
        if (request()->isMethod('POST')) {
            $json_result = ['error' => 0, 'msg' => '', 'url' => ''];

            $handler = request()->input('handler', '');
            $market_id = request()->input('market_id', 0);

            // 删除日志记录
            if ($handler && $handler == 'log_delete') {
                $log_id = request()->input('log_id', 0);
                if (!empty($log_id)) {
                    WechatRedpackLog::where(['id' => $log_id, 'wechat_id' => $this->wechat_id, 'market_id' => $market_id])->delete();

                    $json_result['msg'] = '删除成功！';
                    return response()->json($json_result);
                } else {
                    $json_result['msg'] = '删除失败！';
                    return response()->json($json_result);
                }
            }

            // 搜索用户昵称
            if ($handler && $handler == 'search_nickname') {
                $keywords = request()->input('nickname', '');
                if (!empty($keywords)) {
                    $wechatUser = WechatUser::select('nickname', 'openid')
                        ->where('nickname', 'like', '%' . $keywords . '%')
                        ->where('wechat_id', $this->wechat_id)
                        ->where('subscribe', 1)
                        ->orderBy('uid', 'DESC')
                        ->get();
                    $wechatUser = $wechatUser ? $wechatUser->toArray() : [];

                    if (!empty($wechatUser)) {
                        $json_result['status'] = 0;
                        $json_result['result'] = $wechatUser;
                        return response()->json($json_result);
                    } else {
                        $json_result['status'] = 1;
                        $json_result['msg'] = '未搜索到结果';
                        return response()->json($json_result);
                    }
                }
            }

            // 给指定微信用户（已关注）发送现金红包
            if ($handler && $handler == 'appoint_send_redpack') {
                $market_id = request()->input('market_id', 0);
                $re_openid = request()->input('openid', '');

                //活动配置
                $data = WechatMarketing::select('name', 'starttime', 'endtime', 'config')->where(['id' => $market_id, 'marketing_type' => 'redpack', 'wechat_id' => $this->wechat_id])->first();
                $data = $data ? $data->toArray() : [];

                $redpackinfo['config'] = isset($data['config']) ? unserialize($data['config']) : '';

                $total_num = $redpackinfo['config']['total_num'];
                $status = $this->wechatService->get_status($data['starttime'], $data['endtime']); // 活动状态 0未开始,1正在进行,2已结束
                if ($status == 0) {
                    $json_result = [
                        'status' => 1,
                        'content' => '活动还没开始！',
                    ];
                    return response()->json($json_result);
                } elseif ($status == 2) {
                    $json_result = [
                        'status' => 1,
                        'content' => '活动已结束！',
                    ];
                    return response()->json($json_result);
                } else {
                    $payment = get_payment('wxpay');
                    // 调用红包类
                    $options = [
                        'appid' => $payment['wxpay_appid'],
                        'mch_id' => $payment['wxpay_mchid'],
                        'key' => $payment['wxpay_key']
                    ];
                    $WxHongbao = new Wechat($options); //new WxHongbao($configure);
                    // 证书
                    $sslcert = storage_path('app/certs/wxpay/') . md5($options['mch_id']) . "_apiclient_cert.pem";
                    $sslkey = storage_path('app/certs/wxpay/') . md5($options['mch_id']) . "_apiclient_key.pem";

                    // 设置参数
                    $mch_billno = $payment['wxpay_mchid'] . date('YmdHis') . rand(1000, 9999);
                    // 红包金额
                    $money = $redpackinfo['config']['base_money'] + rand(0, $redpackinfo['config']['money_extra']);
                    $money = $money * 100; // 转换为分
                    $hb_type = $redpackinfo['config']['hb_type'];
                    if ($hb_type == 0) {
                        $total_num = 1;
                    } else {
                        $total_num = $total_num > 3 ? $total_num : 3; // 裂变红包发放总人数，最小3人
                    }

                    $nick_name = $redpackinfo['config']['nick_name'];
                    $send_name = $redpackinfo['config']['send_name'];
                    $wishing = $redpackinfo['config']['wishing'];
                    $act_name = $redpackinfo['config']['act_name'];  //活动名称
                    $remark = $redpackinfo['config']['remark'];
                    // 场景ID
                    $scene_id = strtoupper($redpackinfo['config']['scene_id']);

                    if ($hb_type == 0) {
                        $parameters = [
                            'mch_billno' => $mch_billno,
                            'nick_name' => $nick_name,
                            'send_name' => $send_name,
                            're_openid' => $re_openid,
                            'total_amount' => $money,
                            'min_value' => $money,
                            'max_value' => $money,
                            'total_num' => $total_num,
                            'wishing' => $wishing,
                            'client_ip' => request()->getClientIp(),
                            'act_name' => $act_name,
                            'remark' => $remark,
                        ];
                    } elseif ($hb_type == 1) {
                        $parameters = [
                            'mch_billno' => $mch_billno,
                            'nick_name' => $nick_name,
                            'send_name' => $send_name,
                            're_openid' => $re_openid,
                            'total_amount' => $money,
                            'total_num' => $total_num,
                            'amt_type' => "ALL_RAND",
                            'wishing' => $wishing,
                            'act_name' => $act_name,
                            'remark' => $remark,
                        ];
                    }
                    // 发放红包使用场景，红包金额大于200时必传
                    if ($scene_id && $scene_id > 0) {
                        $parameters["scene_id"] = $scene_id;
                    }
                    $respond = $WxHongbao->CreatSendRedpack($parameters, $hb_type, $sslcert, $sslkey);

                    if ($respond['return_code'] == 'SUCCESS' && $respond['result_code'] == 'SUCCESS') {
                        // 发送成功
                        $where = [
                            'wechat_id' => $this->wechat_id,
                            'market_id' => $market_id,
                            'openid' => $re_openid,
                            'hassub' => 0,
                        ];
                        $count = WechatRedpackLog::where($where)->count();
                        if ($count == 0 || $count < 10) {
                            $total_amount = $respond['total_amount'] * 1.0 / 100;
                            $re_openid = $respond['re_openid'];
                            $mch_billno = $respond['mch_billno'];
                            $mch_id = $respond['mch_id'];
                            $wxappid = $respond['wxappid'];

                            // 返回成功 更新发送红包记录
                            $data = [
                                'wechat_id' => $this->wechat_id,
                                'market_id' => $market_id,
                                'hb_type' => $hb_type,
                                'openid' => $re_openid,
                                'hassub' => 1,
                                'money' => $total_amount,
                                'time' => $this->timeRepository->getGmTime(),
                                'mch_billno' => $mch_billno,
                                'mch_id' => $mch_id,
                                'wxappid' => $wxappid,
                                'bill_type' => 'MCHT',
                                'notify_data' => serialize($respond),
                            ];
                            WechatRedpackLog::where($where)->updateOrCreate($data);

                            if ($hb_type == 1) {
                                return "恭喜获得红包！金额随机，返回公众号可领取。";
                            }
                            return "恭喜获得红包！金额：" . $total_amount . "元！返回公众号可领取。";
                        }

                        return "单个用户可领取红包上线为10个/天,请明天再来！";
                    } else {
                        return response()->json($WxHongbao->errCode . ':' . $WxHongbao->errMsg);
                    }
                }
            }
        }
    }


    /**
     * 生成密钥文件
     * @param string $file_path 目录
     * @param string $filename 文件名
     * @param string $content 内容
     */
    protected function file_write($file_path, $filename, $content = '')
    {
        if (!is_dir($file_path)) {
            @mkdir($file_path);
        }
        $fp = fopen($file_path . $filename, "w+"); // 读写，每次修改会覆盖原内容
        flock($fp, LOCK_EX);
        fwrite($fp, $content);
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}
