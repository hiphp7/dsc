<?php

namespace App\Modules\Admin\Controllers;

use App\Services\App\AppManageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Class AppController
 * @package App\Modules\Admin\Controllers
 */
class AppController extends BaseController
{
    // 分页数量
    protected $page_num = 0;

    protected $appManageService;

    /**
     * AppController constructor.
     * @param AppManageService $appManageService
     */
    public function __construct(
        AppManageService $appManageService
    )
    {
        $this->appManageService = $appManageService;
    }

    protected function initialize()
    {
        parent::initialize();

        $lang = lang('admin/app');
        L($lang);
        $this->assign('lang', array_change_key_case(L()));

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
     * app配置
     */
    public function index()
    {
        // 权限
        //$this->admin_priv('app_config');

        return $this->display();
    }

    /**
     * app 广告位管理
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function ad_position_list(Request $request)
    {
        $position_id = $request->input('position_id', 0); // 广告位id
        $keywords = $request->input('search_keyword', ''); // 搜索广告位

        // 分页
        $filter['position_id'] = $position_id;
        $offset = $this->pageLimit(route('admin/app/ad_position_list', $filter), $this->page_num);

        $list = $this->appManageService->adPositionList($position_id, $keywords, $offset);
        $position_list = $list['list'] ?? [];
        $total = $list['total'] ?? 0;

        $this->assign('position_list', $position_list);
        $this->assign('page', $this->pageShow($total));
        return $this->display();
    }

    /**
     * 广告位信息
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function ad_position_info(Request $request)
    {
        $position_id = $request->input('position_id', 0); // 广告位id

        $position_info = $this->appManageService->adPositionInfo($position_id);

        $this->assign('position_info', $position_info);
        return $this->display();
    }

    /**
     * 添加广告位
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function update_position(Request $request)
    {
        //数据验证
        $messages = [
            'required' => lang('admin/app.position_name_empty'),
        ];
        $validator = Validator::make($request->all(), [
            'position_name' => 'required|string'
        ], $messages);

        $errors = $validator->errors();

        if ($errors && $errors->has('position_name')) {
            return $this->message($errors->first('position_name'), null, 2);
        }

        $res = $this->appManageService->updateAdPostion($request->all());

        if ($res) {
            return $this->message(lang('admin/app.update') . lang('admin/common.success'), route('admin/app/ad_position_list'));
        }

        return $this->message(lang('admin/app.update') . lang('admin/common.fail'), route('admin/app/ad_position_list'));
    }

    /**
     * 删除广告位
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete_position(Request $request)
    {
        $position_id = $request->input('position_id', 0);

        // 查询广告位下是否有广告 如果有不能删除
        $exist = $this->appManageService->checkAd($position_id);
        if ($exist == true) {
            return response()->json(['error' => 1, 'msg' => lang('admin/app.forbid_delete_adp')]);
        }

        $res = $this->appManageService->deleteAdPosition($position_id);

        if ($res) {
            $json_result = ['error' => 0, 'msg' => lang('admin/app.delete') . lang('admin/common.success')];
        } else {
            $json_result = ['error' => 1, 'msg' => lang('admin/app.delete') . lang('admin/common.fail')];
        }

        return response()->json($json_result);
    }

    /**
     * app 广告列表管理
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function ads_list(Request $request)
    {
        $position_id = $request->input('position_id', 0); // 广告位id
        $keywords = $request->input('search_keyword', ''); // 搜索广告

        // 分页
        $filter['position_id'] = $position_id;
        $offset = $this->pageLimit(route('admin/app/ads_list', $filter), $this->page_num);

        $list = $this->appManageService->adList($position_id, $keywords, $offset);
        $ad_list = $list['list'] ?? [];
        $total = $list['total'] ?? 0;

        $this->assign('ad_list', $ad_list);
        $this->assign('position_id', $position_id);
        $this->assign('page', $this->pageShow($total));
        return $this->display();
    }

    /**
     * 广告信息
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function ads_info(Request $request)
    {
        $ad_id = $request->input('ad_id', 0); // 广告id
        $position_id = $request->input('position_id', 0); // 广告位id

        // 广告位
        $list = $this->appManageService->adPositionList();
        $position_list = $list['list'] ?? [];

        if (!empty($position_list)) {
            foreach ($position_list as $k => $value) {
                // 格式化广告位名称
                $position_list[$k]['position_name_format'] = addslashes($value['position_name']) . ' [' . $value['ad_width'] . 'x' . $value['ad_height'] . ']';
            }
        }

        // 广告
        $ads_info = $this->appManageService->adInfo($ad_id);

        // 远程图片
        $url_src = $ads_info['url_src'] ?? '';

        $this->assign('position_list', $position_list);
        $this->assign('position_id', $position_id);
        $this->assign('ads_info', $ads_info);
        $this->assign('url_src', $url_src);
        return $this->display();
    }

    /**
     * 添加广告
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function update_ads(Request $request)
    {
        //数据验证
        $messages = [
            'required' => lang('admin/app.ad_name_empty'),
        ];
        $validator = Validator::make($request->all(), [
            'ad_name' => 'required|string'
        ], $messages);

        $errors = $validator->errors();
        if ($errors && $errors->has('ad_name')) {
            return $this->message($errors->first('ad_name'), null, 2);
        }

        $data = $request->all();

        // 图片类型
        if (isset($data['media_type']) && $data['media_type'] == 0) {
            // 广告图片处理
            // 远程图片链接
            $img_url = $request->input('img_url');
            if (!is_null($img_url)) {
                $data['ad_code'] = $img_url;
            } else {
                $pic_path = $request->input('file_path');

                $file = $request->file('pic');
                if ($file && $file->isValid()) {
                    // 验证文件大小
                    if ($file->getSize() > 2 * 1024 * 1024) {
                        return $this->message(lang('admin/wechat.file_size_limit'), null, 2);
                    }
                    // 验证文件格式
                    if (!in_array($file->getMimeType(), ['image/jpeg', 'image/png'])) {
                        return $this->message(lang('admin/wechat.not_file_type'), null, 2);
                    }
                    $result = $this->upload('data/attached/app', true);
                    if ($result['error'] > 0) {
                        return $this->message($result['message'], null, 2);
                    }

                    $data['ad_code'] = 'data/attached/app/' . $result['file_name'];
                } else {
                    $data['ad_code'] = $pic_path;
                }

                // 路径转换
                if (strtolower(substr($data['ad_code'], 0, 4)) == 'http') {
                    $data['ad_code'] = str_replace(url('/'), '', $data['ad_code']);
                }
                if (strtolower(substr($pic_path, 0, 4)) == 'http') {
                    // 编辑时 删除原图片
                    $pic_path = str_replace(url('/'), '', $pic_path);
                }
                $data['ad_code'] = str_replace('storage/', '', ltrim($data['ad_code'], '/'));
                $pic_path = str_replace('storage/', '', ltrim($pic_path, '/'));
            }

            if (!empty($data['ad_id'])) {
                // 删除原图片
                if ($data['ad_code'] && isset($pic_path) && $pic_path != $data['ad_code']) {
                    $pic_path = strpos($pic_path, 'no_image') == false ? $pic_path : ''; // 不删除默认空图片
                    $this->remove($pic_path);
                }
            }
        }

        // 文字类型
        if (isset($data['media_type']) && $data['media_type'] == 3) {
            // TODO
        }

        $res = $this->appManageService->updateAd($data);

        if ($res) {
            return $this->message(lang('admin/app.update') . lang('admin/common.success'), route('admin/app/ads_list'));
        }

        return $this->message(lang('admin/app.update') . lang('admin/common.fail'), route('admin/app/ads_list'));
    }

    /**
     * 修改广告状态
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function change_ad_status(Request $request)
    {
        $ad_id = $request->input('ad_id', 0);
        $status = $request->input('status', 0);

        $res = $this->appManageService->updateAdStatus($ad_id, $status);

        if ($res) {
            $json_result = ['error' => 0, 'msg' => lang('admin/app.update') . lang('admin/common.success')];
        } else {
            $json_result = ['error' => 1, 'msg' => lang('admin/app.update') . lang('admin/common.fail')];
        }

        return response()->json($json_result);
    }

    /**
     * 删除广告
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete_ad(Request $request)
    {
        $ad_id = $request->input('ad_id', 0);

        $res = $this->appManageService->deleteAd($ad_id);

        if ($res) {
            $json_result = ['error' => 0, 'msg' => lang('admin/app.delete') . lang('admin/common.success')];
        } else {
            $json_result = ['error' => 1, 'msg' => lang('admin/app.delete') . lang('admin/common.fail')];
        }

        return response()->json($json_result);
    }

    /**
     * 获取配置信息
     */
    private function get_config()
    {
    }
}
