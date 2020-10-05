<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Wxapp;
use App\Models\WxappConfig;
use App\Models\WxappTemplate;
use App\Repositories\Common\TimeRepository;

class WxappController extends BaseController
{
    protected $weObj = null;
    // 分页数量
    protected $page_num = 10;

    protected $timeRepository;

    /**
     * WxappController constructor.
     * @param TimeRepository $timeRepository
     */
    public function __construct(
        TimeRepository $timeRepository
    )
    {
        $this->timeRepository = $timeRepository;
    }

    protected function initialize()
    {
        parent::initialize();

        L(lang('admin/wxapp'));
        $this->assign('lang', L());

        // 获取配置信息
        $this->get_config();
        // 初始化
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
     * 小程序设置
     */
    public function index()
    {
        // 权限
        $this->admin_priv('wxapp_wechat_config');

        // 提交处理
        if (request()->isMethod('POST')) {
            $id = request()->input('id', 0);
            $data = request()->input('data');
            // 将数组null值转为空
            array_walk_recursive($data, function (&$val, $key) {
                $val = ($val === null) ? '' : $val;
            });
            // 验证数据
            if (empty($data['wx_appid'])) {
                return $this->message(lang('admin/wxapp.must_appid'), null, 2);
            }
            if (empty($data['wx_appsecret'])) {
                return $this->message(lang('admin/wxapp.must_appsecret'), null, 2);
            }
            if (empty($data['wx_mch_id'])) {
                return $this->message(lang('admin/wxapp.must_mch_id'), null, 2);
            }
            if (empty($data['wx_mch_key'])) {
                return $this->message(lang('admin/wxapp.must_mch_key'), null, 2);
            }
            if (empty($data['token_secret'])) {
                return $this->message(lang('admin/wxapp.must_token_secret'), null, 2);
            }

            // 更新数据
            if (!empty($id)) {
                // 如果 wx_appsecret 包含 * 跳过不保存数据库
                if (stripos($data['wx_appsecret'], '*') !== false) {
                    unset($data['wx_appsecret']);
                }
                WxappConfig::where(['id' => $id])->update($data);
            } else {
                $data['add_time'] = $this->timeRepository->getGmTime();
                WxappConfig::create($data);
            }
            return $this->message(lang('admin/wechat.wechat_editor') . lang('admin/common.success'), route('admin/wxapp/index'));
        }

        // 查询
        $info = WxappConfig::first();
        $info = $info ? $info->toArray() : [];
        if (!empty($info)) {
            // 用*替换字符显示
            $info['wx_appsecret'] = string_to_star($info['wx_appsecret']);
        }
        $this->assign('data', $info);
        return $this->display();
    }

    /**
     * 新增小程序
     */
    public function append()
    {
    }

    /**
     * 删除小程序
     */
    public function delete()
    {
        $id = request()->input('id', 0);
        if ($id) {
            WxappConfig::where('id', $id)->delete();
        }
    }


    /**
     * 模板消息
     */
    public function template()
    {
        // 模板消息权限
        $this->admin_priv('wxapp_template');

        $list = WxappTemplate::orderBy('id', 'ASC')
            ->get();
        $list = $list ? $list->toArray() : [];

        if ($list) {
            foreach ($list as $key => $val) {
                $list[$key]['add_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i', $val['add_time']);
            }
        }
        $this->assign('list', $list);
        return $this->display();
    }


    /**
     * 编辑模板消息
     */
    public function edit_template()
    {
        // 模板消息权限
        $this->admin_priv('wxapp_template');

        if (request()->isMethod('POST')) {
            $id = request()->input('id');
            $data = request()->input('data');
            if ($id) {
                array_walk_recursive($data, function (&$val, $key) {
                    $val = ($val === null) ? '' : $val;
                });

                $time = $this->timeRepository->getGmTime();
                $data['add_time'] = $time;
                WxappTemplate::where('id', $id)->update($data);
                return response()->json(['status' => 1]);
            } else {
                return response()->json(['status' => 0, 'msg' => lang('admin/wechat.template_edit_fail')]);
            }
        }
        $id = request()->input('id', 0);
        $template = [];
        if ($id) {
            $template = WxappTemplate::where('id', $id)->first();
            $template = $template ? $template->toArray() : [];
        }
        $this->assign('template', $template);
        return $this->display();
    }

    /**
     * 启用或禁止模板消息
     */
    public function switch_template()
    {
        // 模板消息权限
        $this->admin_priv('wxapp_template');

        $id = request()->input('id', 0);
        $status = request()->input('status', 0);
        if (empty($id)) {
            return $this->message(lang('admin/wechat.empty'), null, 2);
        }
        $condition['id'] = $id;

        $data = [];

        $time = $this->timeRepository->getGmTime();
        $data['add_time'] = $time;

        // 启用模板消息
        if ($status == 1) {

            // 模板ID为空
            $template = WxappTemplate::select('wx_template_id', 'wx_code', 'wx_keyword_id')->where($condition)->first();
            $template = $template ? $template->toArray() : [];
            if (empty($template['wx_template_id'])) {
                $wx_keyword_id = explode(',', $template['wx_keyword_id']);
                $template_id = $this->weObj->wxaddTemplateMessage($template['wx_code'], $wx_keyword_id);
                // 已经存在模板ID
                if ($template_id) {
                    $data['wx_template_id'] = $template_id;
                    WxappTemplate::where($condition)->update($data);
                } else {
                    return $this->message($this->weObj->errMsg, null, 2);
                }
            }

            // 重新启用 更新状态status
            $data['status'] = 1;
            WxappTemplate::where($condition)->update($data);
        } else {
            // 禁用 更新状态status
            $data['status'] = 0;
            WxappTemplate::where($condition)->update($data);
        }
        return redirect()->route('admin/wxapp/template');
    }

    /**
     * 重置模板消息
     * @return
     */
    public function reset_template()
    {
        // 模板消息权限
        $this->admin_priv('wxapp_template');

        if (request()->isMethod('POST')) {
            $json_result = ['error' => 0, 'msg' => '', 'url' => ''];

            $id = request()->input('id', 0);
            if (!empty($id)) {
                $template = WxappTemplate::select('wx_template_id')->where('id', $id)->first();
                $template = $template ? $template->toArray() : [];
                if (!empty($template['wx_template_id'])) {
                    $rs = $this->weObj->wxDelTemplate($template['wx_template_id']);
                    WxappTemplate::where(['id' => $id])->update(['wx_template_id' => '', 'status' => 0]);
                    if (empty($rs)) {
                        $json_result['msg'] = lang('admin/wechat.errcode') . $this->weObj->errCode . lang('admin/wechat.errmsg') . $this->weObj->errMsg;
                        return response()->json($json_result);
                    }
                    $json_result['msg'] = lang('admin/common.reset') . lang('admin/common.success');
                    return response()->json($json_result);
                }
            }
            $json_result['error'] = 1;
            $json_result['msg'] = lang('admin/common.reset') . lang('admin/common.fail');
            return response()->json($json_result);
        }
    }

    /**
     * 获取配置信息
     */
    private function get_config()
    {
        $without = [
            'index',
            'append',
            'delete',
        ];

        if (!in_array(strtolower($this->getCurrentMethodName()), $without)) {
            // 小程序配置信息
            $wxapp = WxappConfig::select('wx_appid', 'wx_appsecret', 'status')->where('id', 1)->first();
            $wxapp = $wxapp ? $wxapp->toArray() : [];
            if ($wxapp) {
                if (isset($wxapp['status']) && $wxapp['status'] == 0) {
                    return $this->message(lang('admin/wxapp.open_wxapp'), route('admin/wxapp/index'), 2);
                }
                $config = [
                    'appid' => $wxapp['wx_appid'],
                    'secret' => $wxapp['wx_appsecret'],
                ];
                $this->weObj = new Wxapp($config);
            }
        }
    }
}
