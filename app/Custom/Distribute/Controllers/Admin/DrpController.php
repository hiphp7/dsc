<?php

namespace App\Custom\Distribute\Controllers\Admin;

use App\Custom\CustomView;
use App\Custom\Distribute\Models\DrpActivityDetailes;
use App\Custom\Distribute\Models\DrpRewardLog;
use App\Custom\Distribute\Models\DrpUpgradeCondition;
use App\Custom\Distribute\Models\DrpUpgradeValues;
use App\Custom\Distribute\Services\DistributeManageService;
use App\Models\DrpUserCredit;
use App\Models\Users;
use App\Modules\Admin\Controllers\DrpController as BaseController;
use App\Services\Common\OfficeService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;


class DrpController extends BaseController
{
    use CustomView;

    protected $page_num = 10;
    protected $ru_id = 0;

    protected function initialize()
    {
        parent::initialize();

        $this->load_helper('helpers');

        // 当前模块语言包
        $_lang = $this->load_lang(['common', 'drp']);
        L($_lang);
        $this->assign('lang', L());
    }

    /**
     * 搜索选择商品
     * @param Request $request
     * @param DistributeManageService $distributeManageService
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|string
     */
    public function select_goods(Request $request, DistributeManageService $distributeManageService)
    {
        $type = $request->input('type', 'buy_drp'); // 类型：buy_drp 购买成为分销商 upgrade 升级商品

        // 搜索
        $keywords = $request->input('keyword', '');

        $cat_id = $request->input('category_id', 0);
        $brand_id = $request->input('brand_id', 0);

        // 已选择商品id
        $select_goods_id = $request->input('select_goods_id', '');

        $this->page_num = 20;

        // 分页
        $filter['cat_id'] = $cat_id;
        $filter['brand_id'] = $brand_id;
        $filter['keyword'] = $keywords;
        $filter['select_goods_id'] = $select_goods_id;
        $offset = $this->pageLimit(route('distribute.admin.select_goods', $filter), $this->page_num);

        $result = $distributeManageService->goodsListSearch($keywords, $cat_id, $brand_id, $offset, $filter);

        $total = $result['total'] ?? 0;
        $goods_list = $result['list'] ?? [];

        $this->assign('type', $type);
        $this->assign('goods', $goods_list);

        $filter = set_default_filter_new(); //设置默认 分类，品牌列表 筛选

        $this->assign('filter_category_level', 1); //分类等级 默认1
        $this->assign('filter_category_navigation', $filter['filter_category_navigation']);
        $this->assign('filter_category_list', $filter['filter_category_list']);
        $this->assign('filter_brand_list', $filter['filter_brand_list']);

        $this->assign('page', $this->pageShow($total));
        $this->assign('page_num', $this->page_num);
        $this->assign('page_title', L('select_goods_menu'));
        return $this->display();
    }

    /**
     * 加入购买分销商品
     * @param Request $request
     * @param DistributeManageService $distributeManageService
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit_goods(Request $request, DistributeManageService $distributeManageService)
    {
        if ($request->isMethod('POST')) {
            $goods_id = $request->input('goods_id', 0);
            $buy_drp_show = $request->input('buy_drp_show', 1);

            if (empty($goods_id)) {
                return response()->json(['status' => 1, 'msg' => L('please_select')]);
            }

            // 加入购买分销商品 隐藏商品显示
            $is_show = empty($buy_drp_show) ? 1 : 0;
            $data = [
                'buy_drp_show' => $buy_drp_show,
                'is_show' => $is_show
            ];
            $distributeManageService->editGoods($goods_id, $data);

            $json_result['status'] = 0;
            $json_result['msg'] = lang('admin/common.success');
            return response()->json($json_result);
        }
    }


    /**
     * 编辑会员升级条件
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\JsonResponse|\Illuminate\View\View|string
     * @throws \Exception
     */
    public function drp_user_credit_condition(Request $request)
    {
        // 分销商管理权限
        $this->admin_priv('drp_shop');

        if ($request->isMethod('POST')) {
            $id = $request->input('id', 0);
            $data = $request->input('data');
            if ($id) {
                //获取所有的规则以及规则ID进行数组化处理
                $new_upgrade_name = array();
                $new_upgrade_id = array();
                $condition_id = array();
                $all_upgrade_condition = DrpUpgradeCondition::get()->toArray();
                foreach ($all_upgrade_condition as $key => $val) {
                    $new_upgrade_id["{$val['name']}"] = $val['id'];
                    $new_upgrade_name["{$val['id']}"] = $val['name'];
                }
                // 将数组null值转为空
                array_walk_recursive($data, function (&$val, $key) {
                    $val = ($val === null) ? '' : $val;
                });

                foreach ($data as $key => $val) {
                    if ($key == 'buy_goods' && !empty($data['buy_goods'])) {
                        $value_id = $this->drp_upgrade_add($id, $new_upgrade_id['goods_id'], $data['buy_goods'], $data['goods_id_num'], $data['goods_id_status']);
                        $condition_id[] = array('condition_id' => $new_upgrade_id['goods_id'], 'value_id' => $value_id);
                    }
                    if (in_array($key, $new_upgrade_name) && isset($data[$key]) && !empty($data[$key])) {
                        $value_id = $this->drp_upgrade_add($id, $new_upgrade_id[$key], $data[$key], $data[$key . '_num'], $data[$key . '_status']);
                        $condition_id[] = array('condition_id' => $new_upgrade_id[$key], 'value_id' => $value_id);
                    }
                }

                if (isset($condition_id) && empty($condition_id)) {
                    $drp_user_credi = DrpUserCredit::where(['id' => $id])->first();
                    if (isset($drp_user_credi) && !empty($drp_user_credi['condition_id'])) {
                        DrpUserCredit::where(['id' => $id])->update(array('condition_id' => ''));
                        return response()->json(['error' => 0, 'msg' => lang('admin/drp.update_success')]);
                    }
                }

                if (isset($condition_id) && !empty($condition_id)) {
                    $condition_id = serialize($condition_id);
                    DrpUserCredit::where(['id' => $id])->update(array('condition_id' => $condition_id));
                    return response()->json(['error' => 0, 'msg' => lang('admin/drp.update_success')]);
                }

                return response()->json(['error' => 1, 'msg' => lang('admin/drp.update_defeated')]);
            }
            return response()->json(['error' => 1, 'msg' => lang('admin/drp.choice')]);
        }
        // 显示
        $id = $request->input('id', 0);
        $info = DrpUserCredit::where(['id' => $id])->first();

        $new_condition = array();
        if (!empty($info['condition_id'])) {
            $condition = unserialize($info['condition_id']);
            foreach ($condition as $key => $val) {
                $condition_name = DrpUpgradeCondition::where('id', $val['condition_id'])->value('name');
                $condition_value = DrpUpgradeValues::where('id', $val['value_id'])->first();
                if (isset($condition_value) && !empty($condition_value)) {
                    $condition_value = $condition_value->toArray();
                    $new_condition[$condition_name]['value'] = $condition_value['value'];
                    $new_condition[$condition_name]['award_num'] = $condition_value['award_num'];
                    $new_condition[$condition_name]['type'] = $condition_value['type'];
                }

            }
        }

        $info = $info ? $info->toArray() : [];
        $this->assign('new_condition', $new_condition);
        $this->assign('info', $info);
        return $this->display();
    }

    /**
     * 根据条件ID与等级ID 进行会员升级条件的更新
     * @param $credit_id
     * @param $condition_id
     * @param $value
     * @return mixed
     */
    public function drp_upgrade_add($credit_id, $condition_id, $value, $award_num = '', $type = 0)
    {
        $con_value = DrpUpgradeValues::where('credit_id', $credit_id)->where('condition_id', $condition_id)->first();
        if (isset($con_value) && !empty($con_value)) {
            //值已存在,执行修改操作
            DrpUpgradeValues::where('id', $con_value['id'])->update(['value' => $value, 'award_num' => $award_num, 'type' => $type]);
            return $con_value['id'];
        } else {
            //值不存在,执行添加
            return DrpUpgradeValues::insertGetId(['value' => $value, 'credit_id' => $credit_id, 'condition_id' => $condition_id, 'award_num' => empty($award_num) ? '' : $award_num, 'type' => $type]);
        }
    }

    /**
     * 分销商活动列表
     * @param Request $request
     * @param DistributeManageService $distributeManageService
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|string
     */
    public function activity_list(Request $request, DistributeManageService $distributeManageService)
    {
        // 搜索
        $keywords = $request->input('keyword', '');
        $seller_list = $request->input('seller_list', 0);//0为平台  1为店铺
        //分页信息
        $filter['keywords'] = $keywords;
        $filter['seller_list'] = $seller_list;
        $offset = $this->pageLimit(route('distribute.admin.activity_list', $filter), $this->page_num);

        $res = $distributeManageService->get_all_activity_list($filter, $offset);
        $total = $res['total'] ?? 0;
        $all_activity = $res['all_activity'] ?? [];

        $this->assign('page', $this->pageShow($total));
        $this->assign('seller_list', $seller_list);
        $this->assign('keywords', $keywords);
        $this->assign('all_activity', $all_activity);
        return $this->display();
    }

    /**
     * 分销商活动活动展示页
     * @param Request $request
     * @param DistributeManageService $distributeManageService
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|string
     */
    public function user_activity_list(Request $request, DistributeManageService $distributeManageService)
    {
        // 搜索
        $keywords = $request->input('keyword', '');
        $activity_type = $request->input('activity_type', 0);
        $seller_list = $request->input('seller_list', 0);//0为平台  1为店铺
        //分页信息
        $filter = [
            'keyword' => $keywords,
            'activity_type' => $activity_type,
            'seller_list' => $seller_list
        ];
        $offset = $this->pageLimit(route('distribute.admin.user_activity_list', $filter), $this->page_num);

        $all_users = Users::where('user_name', 'like', '%' . $keywords . '%')->get('user_id');
        $all_users = $all_users ? $all_users->toArray() : [];

        if (!empty($all_users)) {
            $all_users = Arr::dot($all_users);
        }
        //获取会员列表信息
        $res = $distributeManageService->get_all_user_activity_list($all_users, $filter, $offset);
        $total = $res['total'] ?? 0;
        $all_activity = $res['all_activity'] ?? [];

        $this->assign('seller_list', $seller_list);
        $this->assign('page', $this->pageShow($total));
        $this->assign('keywords', $keywords);
        $this->assign('all_activity', $all_activity);
        return $this->display();
    }

    /**
     * 修改分销商活动状态
     * @param Request $request
     * @return bool
     */
    public function activity_finish(Request $request)
    {
        if ($request->isMethod('POST')) {
            $id = $request->input('id', 0);
            if (empty($id)) {
                return false;
            }
            $activity_finish = DrpActivityDetailes::where('id', $id)->first(['is_finish', 'goods_id', 'end_time']);
            if (is_null($activity_finish)) {
                return false;
            }
            $present_time = $this->timeRepository->getGmTime();
            if ($activity_finish->end_time <= $present_time && $activity_finish->is_finish == 0) {
                //活动已过期,无法开启
                return response()->json(['status' => 0, 'msg' => L('activity_past_due')]);
            }
            $all_activicty_goods = DrpActivityDetailes::where('goods_id', $activity_finish->goods_id)->where('is_finish', 1)->count();
            if ($activity_finish->is_finish == 0 && $all_activicty_goods >= 1) {
                //该商品正在参与其他活动不能开启
                return response()->json(['status' => 0, 'msg' => L('activity_goods_reprtition')]);
            }

            if ($activity_finish->is_finish == 0) {
                $data = ['is_finish' => 1];
            } else {
                $data = ['is_finish' => 0];
            }
            return DrpActivityDetailes::where('id', $id)->update($data);
        }
        return false;
    }

    /**
     * 删除活动
     * @param Request $request
     * @return bool|\Illuminate\Http\JsonResponse
     */
    public function activity_remove(Request $request)
    {
        if ($request->isMethod('POST')) {
            $id = $request->input('id', 0);

            if (empty($id)) {
                return false;
            }

            $res = DrpActivityDetailes::where('id', $id)->delete();
            if ($res) {
                return response()->json(['status' => 1, 'msg' => L('success_delete'), 'url' => route('distribute.admin.activity_list')]);
            } else {
                return response()->json(['status' => 0, 'msg' => L('error_delete')]);
            }
        }
        return false;
    }

    /**
     * 删除用户参与活动记录
     * @param Request $request
     * @return bool|\Illuminate\Http\JsonResponse
     */
    public function user_activity_remove(Request $request)
    {
        if ($request->isMethod('POST')) {
            $id = $request->input('id', 0);

            if (empty($id)) {
                return false;
            }

            $res = DrpRewardLog::where('reward_id', $id)->delete();
            if ($res) {
                return response()->json(['status' => 1, 'msg' => L('success_delete'), 'url' => route('distribute.admin.user_activity_list')]);
            } else {
                return response()->json(['status' => 0, 'msg' => L('error_delete')]);
            }
        }
        return false;
    }

    /**
     * 分销商活动编辑页面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|string
     */
    public function activity_info(Request $request)
    {
        $id = $request->input('id', 0);

        if (!empty($id)) {
            $activity_detail = DrpActivityDetailes::from('drp_activity_detailes as d')->leftjoin('goods as g', 'd.goods_id', '=', 'g.goods_id')->where('d.id', $id)->first();

            $activity_detail = $activity_detail ? $activity_detail->toArray() : [];

            if (!empty($activity_detail)) {
                $activity_detail['start_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $activity_detail['start_time']);
                $activity_detail['end_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $activity_detail['end_time']);
            }

            $this->assign('activity_detail', $activity_detail);
        }
        return $this->display();
    }

    /**
     * 添加/编辑分销商活动操作
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function activity_info_add(Request $request)
    {
        // 提交
        if ($request->isMethod('POST')) {
            $data = $request->input('data', '');
            if (empty($data['act_name'])) {
                return $this->message(L('act_name_empty'), null, 2);
            }

            if (empty($data['goods_id'])) {
                return $this->message(L('goods_id_empty'), null, 2);
            }

            if (empty($data['raward_money'])) {
                return $this->message(L('raward_money_empty'), null, 2);
            }

            if (empty($data['act_dsc'])) {
                return $this->message(L('act_dsc_empty'), null, 2);
            }

            if (empty($data['start_time'])) {
                return $this->message(L('start_time_empty'), null, 2);
            } else {
                $data['start_time'] = $this->timeRepository->getLocalStrtoTime($data['start_time']);
            }

            if (empty($data['end_time'])) {
                return $this->message(L('end_time_empty'), null, 2);
            } else {
                $data['end_time'] = $this->timeRepository->getLocalStrtoTime($data['end_time']);
            }

//            if (empty($data['text_info'])) {
//                return $this->message(L('text_info_empty'), null, 2);
//            }
            $data['act_type_share'] = $data['act_type_share'] ?? 0;
            $data['act_type_place'] = $data['act_type_place'] ?? 0;
            if (empty($data['act_type_share']) && empty($data['act_type_place'])) {
                return $this->message(L('act_type_empty'), null, 2);
            }

            if (empty($data['id'])) {
                $all_activity_log = DrpActivityDetailes::where('is_finish', 1)->where('goods_id', $data['goods_id'])->first();
                if (!empty($all_activity_log)) {
                    return $this->message(L('activity_repetition_goods'), null, 2);
                }
                //添加活动
                $data = Arr::except($data, ['id']);
                $data['add_time'] = $this->timeRepository->getGmTime();
                $data['is_finish'] = 1;
                $res = DrpActivityDetailes::insertGetId($data);
            } else {
                //修改活动
                $res = DrpActivityDetailes::where('id', $data['id'])->update($data);
            }

            if (empty($res)) {
                return $this->message(L('update_error'), null, 2);
            } else {
                return $this->message(L('update_success'), route('distribute.admin.activity_list'), 1);
            }
        } else {
            return $this->message(L('data_empty'), null, 2);
        }

    }

    /**
     * 活动详情   展示活动的详细信息以及参与用户信息
     * @param DistributeManageService $distributeManageService
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View|string
     */
    public function activity_details(Request $request, DistributeManageService $distributeManageService)
    {
        $id = $request->input('id', '0');

        if (empty($id)) {
            return redirect()->route('distribute.admin.activity_list');
        }

        //获取活动详情
        $activity_res = DrpActivityDetailes::with([
            'AdminUser' => function ($query) {
                $query->select('ru_id', 'user_name');
            }])
            ->where('id', $id)->first();
        $activity_res = $activity_res ? $activity_res->toArray() : [];

        if (!empty($activity_res)) {
            $activity_res['start_time_format'] = empty($activity_res['start_time']) ? '' : $this->timeRepository->getLocalDate('Y-m-d H:i:s', $activity_res['start_time']);
            $activity_res['end_time_format'] = empty($activity_res['end_time']) ? '' : $this->timeRepository->getLocalDate('Y-m-d H:i:s', $activity_res['end_time']);
        }

        //分页信息
        $offset = $this->pageLimit(route('distribute.admin.activity_details', []), $this->page_num);

        //获取参与活动的分销商详情
        $all_activity_user = DrpRewardLog::from('drp_reward_log as d')->leftjoin('users as u', 'd.user_id', '=', 'u.user_id')->where('activity_id', $id)->where('activity_type', 0)->offset($offset['start'])
            ->limit($offset['limit'])->get();

        $all_activity_user = $all_activity_user ? $all_activity_user->toArray() : [];

        $all_order_statistics = $distributeManageService->statistics_order_activity_message($id, $activity_res['goods_id']);
        $all_activity_user = $this->sort_unfinish_array($all_order_statistics, $all_activity_user);

        $this->assign('all_order_statistics', $all_order_statistics);
        $this->assign('all_activity_user', $all_activity_user);
        $this->assign('page', $this->pageShow(count($all_activity_user)));
        $this->assign('activity_res', $activity_res);

        return $this->display();
    }

    /**
     *对分销商活动订单未完成订单进行重排序
     * @param array $order_statistics
     * @param array $activity_user
     * @return array
     */
    public function sort_unfinish_array($order_statistics = [], $activity_user = [])
    {
        if (empty($order_statistics['unfinish_order_num']) || empty($activity_user)) {
            return $activity_user;
        }
        //对所有的订单未完成数量进行排序
        $new_all_order_num = array_reverse(Arr::sort($order_statistics['unfinish_order_num']), true);
        if (empty($new_all_order_num)) {
            return $activity_user;
        }
        $new_activity_user_user_id = [];
        foreach ($activity_user as $key => $val) {
            $new_activity_user_user_id[$val['user_id']] = $val;
            if (!isset($new_all_order_num[$val['user_id']])) {
                $new_all_order_num[$val['user_id']] = 0;
            }
        }

        $new_activity_user = [];
        foreach ($new_all_order_num as $key => $val) {
            if (isset($new_activity_user_user_id[$key])) {
                $new_activity_user[] = $new_activity_user_user_id[$key];
            }
        }
        return $new_activity_user;
    }

    /**
     * 分销商提现申请记录
     * @param Request $request
     * @param DistributeManageService $distributeManageService
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|string
     */
    public function transfer_log(Request $request, DistributeManageService $distributeManageService)
    {
        // 搜索
        $keywords = $request->input('keywords', '');

        // 分页
        $filter['keywords'] = $keywords;
        $offset = $this->pageLimit(route('distribute.admin.transfer_log', $filter), $this->page_num);

        $result = $distributeManageService->transferLogList($keywords, $offset);

        $total = $result['total'] ?? 0;
        $log_list = $result['list'] ?? [];

        $this->assign('list', $log_list);

        $this->assign('page', $this->pageShow($total));
        $this->assign('page_num', $this->page_num);
        $this->assign('page_title', L('transfer_log_menu'));
        return $this->display();
    }

    /**
     * 审核且在线转账
     * @param Request $request
     * @param DistributeManageService $distributeManageService
     * @return mixed
     */
    public function transfer_log_check(Request $request, DistributeManageService $distributeManageService)
    {
        // 提交
        if ($request->isMethod('POST')) {

            $id = $request->input('id', 0);
            $user_id = $request->input('user_id', 0);
            if (empty($id)) {
                return response()->json(['status' => 1, 'msg' => L('please_select')]);
            }

            $data = $request->input('data', '');
            if (empty($data)) {
                return response()->json(['status' => 1, 'msg' => L('empty')]);
            }

            $check_status = $data['check_status'] ?? 0; // 审核状态

            // 同意审核
            if ($check_status == 1) {
                // 提现方式 0 线下付款 1 微信企业付款至零钱 2 微信企业付款至银行卡
                $deposit_type = $data['deposit_type'] ?? 0;
                if ($deposit_type > 0) {
                    // 如果扣除的佣金多于此分销商拥有的佣金，提示
                    $shop = $distributeManageService->getDrpShopAccount($user_id);
                    if (!empty($shop)) {
                        if ($data['money'] > $shop['frozen_money']) {
                            return response()->json(['status' => 1, 'msg' => L('frozen_money_error')]);
                        }
                    }
                    // 微信企业付款
                    $res = $distributeManageService->transferLogDeposit($id, $deposit_type, $data);

                    if ($res == true) {
                        return response()->json(['status' => 0, 'msg' => lang('admin/common.success'), 'url' => route('distribute.admin.transfer_log')]);
                    }
                }

                return response()->json(['status' => 1, 'msg' => lang('admin/common.fail')]);

            } else {
                // 仅修改 审核状态
                $distributeManageService->transferLogCheck($id, $check_status, $data);

                return response()->json(['status' => 0, 'msg' => lang('admin/common.success'), 'url' => route('distribute.admin.transfer_log')]);
            }
        }

        // 显示
        $id = $request->input('id', '');

        $info = app(DistributeManageService::class)->transferLogInfo($id);

        $this->assign('info', $info);
        $this->assign('page_title', L('transfer_log_menu'));
        return $this->display();
    }

    /**
     * 查看提现记录
     * @param Request $request
     * @param DistributeManageService $distributeManageService
     * @return mixed
     */
    public function transfer_log_see(Request $request, DistributeManageService $distributeManageService)
    {
        $id = $request->input('id', '');
        if (empty($id)) {
            return response()->json(['status' => 1, 'msg' => L('please_select')]);
        }

        $info = $distributeManageService->transferQuery($id);

        $json_result['status'] = 0;
        $json_result['title'] = L('transfer_see');
        $json_result['info'] = $info;
        $json_result['url'] = route('distribute.admin.transfer_log');
        return response()->json($json_result);
    }

    /**
     * 删除记录
     * @param Request $request
     * @param DistributeManageService $distributeManageService
     * @return mixed
     */
    public function transfer_log_delete(Request $request, DistributeManageService $distributeManageService)
    {
        $id = $request->input('id', '');
        if (empty($id)) {
            return response()->json(['status' => 1, 'msg' => L('please_select')]);
        }

        $distributeManageService->transferLogDelete($id);

        $json_result['status'] = 0;
        $json_result['msg'] = lang('admin/common.success');
        $json_result['url'] = route('distribute.admin.transfer_log');
        return response()->json($json_result);
    }

    /**
     * 分销商活动统计记录表
     * @param Request $request
     * @param DistributeManageService $distributeManageService
     * @return mixed
     */
    public function activity_details_export(Request $request, DistributeManageService $distributeManageService)
    {
        // 导出
        if ($request->isMethod('POST')) {
            $id = $request->input('id', 0);

            if (empty($id)) {
                return $this->message(L('empty_list_export'), null, 2);
            }

            $starttime = $request->input('start_time', '');
            $endtime = $request->input('end_time', '');

            if (empty($starttime) || empty($endtime)) {
                return $this->message(lang('admin/wechat.select_start_end_time'), null, 2);
            }

            $starttime = $this->timeRepository->getLocalStrtoTime($starttime);
            $endtime = $this->timeRepository->getLocalStrtoTime($endtime);

            if ($starttime > $endtime) {
                return $this->message(lang('admin/wechat.start_lt_end_time'), null, 2);
            }

            // 按时间导出
            $condition = [
                'starttime' => $starttime,
                'endtime' => $endtime
            ];

            $all_activity_user_log = $distributeManageService->set_activity_user_log($id, $condition);

            if (empty($all_activity_user_log)) {
                return $this->message(L('empty_list_export'), null, 2);
            }
            // 设置 表头标题、列宽 默认10, true 是否自动换行  格式：列名|10|true
            $head = [
                L('activity_log_id'),
                L('details_user_name') . '|25',
                L('activity_mobile_phone') . '|25',
                L('activity_name') . '|25',
                L('activity_start_end_time') . '|50',
                L('activity_goods_name') . '|50',
                L('activity_reward_money') . '|25',
                L('activity_reward_type') . '|25',
                L('details_user_num_one') . '|25',
                L('details_user_num_two') . '|25',
                L('yet_delivery_num_one') . '|25',
                L('not_yet_delivery_num_one') . '|25',
                L('activity_reward_status') . '|25',

            ];

            // 导出字段
            $fields = [
                'id',
                'user_name',
                'mobile_phone',
                'act_name',
                'start_end_time',
                'goods_name',
                'award_money',
                'raward_type',
                'completeness_share',
                'completeness_place',
                'finish_order_num',
                'unfinish_order_num',
                'award_status'
            ];
            // 文件名
            $title = L('user_activity_details_menu');

            $spreadsheet = new OfficeService();

            $spreadsheet->outdata($title, $head, $fields, $all_activity_user_log);
            return;
        }
        return $this->message(L('error_list_export'), null, 2);
    }

    /**
     * 分销商活动导出会员数据
     * @param Request $request
     * @param DistributeManageService $distributeManageService
     * @return mixed
     */
    public function user_activity_list_export(Request $request, DistributeManageService $distributeManageService)
    {
        // 导出
        if ($request->isMethod('POST')) {
            $keyword = $request->input('keyword', '');


            $all_activity = $distributeManageService->set_user_activity($keyword);

            if (empty($all_activity)) {
                return $this->message(L('empty_list_export'), null, 2);
            }

            // 设置 表头标题、列宽 默认10, true 是否自动换行  格式：列名|10|true
            $head = [
                L('details_user_id'),
                L('details_user_name') . '|25',
                L('activity_name') . '|25',
                L('activity_reward_money') . '|25',
                L('activity_reward_type') . '|25',
                L('details_user_type') . '|25',
                L('details_user_time') . '|30',

            ];

            // 导出字段
            $fields = [
                'reward_id',
                'user_name',
                'act_name',
                'raward_money',
                'raward_type',
                'award_status',
                'add_time'
            ];
            // 文件名
            $title = L('user_activity_list_menu');

            $spreadsheet = new OfficeService();

            $spreadsheet->outdata($title, $head, $fields, $all_activity);
            return;
        }
        return $this->message(L('error_list_export'), null, 2);
    }

    /**
     * 导出Excel
     * @param Request $request
     * @param DistributeManageService $distributeManageService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function export_transfer_log(Request $request, DistributeManageService $distributeManageService)
    {
        // 导出
        if ($request->isMethod('POST')) {
            $starttime = $request->input('starttime', '');
            $endtime = $request->input('endtime', '');

            if (empty($starttime) || empty($endtime)) {
                return $this->message(lang('admin/wechat.select_start_end_time'), null, 2);
            }

            $starttime = $this->timeRepository->getLocalStrtoTime($starttime);
            $endtime = $this->timeRepository->getLocalStrtoTime($endtime);

            if ($starttime > $endtime) {
                return $this->message(lang('admin/wechat.start_lt_end_time'), null, 2);
            }

            // 按时间导出
            $condition = [
                'starttime' => $starttime,
                'endtime' => $endtime
            ];
            $result = $distributeManageService->transferLogList('', [], $condition);

            $total = $result['total'] ?? 0;
            $log_list = $result['list'] ?? [];

            if ($log_list) {

                // 设置 表头标题、列宽 默认10, true 是否自动换行  格式：列名|10|true
                $head = [
                    L('record_id'),
                    L('shop_name') . '|25',
                    L('trans_money') . '|25',
                    L('add_time') . '|25',
                    L('check_status') . '|25',
                    L('deposit_type') . '|30',
                    L('deposit_status') . '|30'
                ];

                // 导出字段
                $fields = [
                    'id',
                    'shop_name',
                    'money',
                    'add_time_format',
                    'check_status_format',
                    'deposit_type_format',
                    'deposit_status_format'
                ];
                // 文件名
                $title = L('transfer_log_menu');

                $spreadsheet = new OfficeService();

                $spreadsheet->outdata($title, $head, $fields, $log_list);
                return;
            }
        }

        return $this->message(L('error_list_export'), null, 2);
    }

    /**
     * 发放分销商活动奖励
     * @param Request $request
     * @param DistributeManageService $distributeManageService
     * @return mixed
     */
    public function activity_grant_award(Request $request, DistributeManageService $distributeManageService)
    {
        if ($request->isMethod('POST')) {
            $id = $request->input('id', '0');
            if (empty($id)) {
                return $this->message(L('error_grant_award'), null, 2);
            }
            //获取所有应该发放奖励的记录
            $reward_log = $distributeManageService->get_reward_log($id);
            if (empty($reward_log)) {
                return $this->message(L('empty_grant_reward_log'), null, 2);
            }
            $all_reward_user = [];
            foreach ($reward_log as $key => $val) {
                //发放用户奖励
                $award_res = $distributeManageService->operate_user_money($val['user_id'], $val['award_money'], $val['award_type']);
                if ($award_res) {
                    $all_reward_user[] = $val['reward_id'];
                }
            }

            if (empty($all_reward_user)) {
                return $this->message(L('update_error'), null, 2);
            }
            //修改记录状态
            $reward_res = $distributeManageService->update_all_reward_status($all_reward_user);
            if (!$reward_res) {
                foreach ($reward_log as $key => $val) {
                    //记录修改失败,扣除金额
                    $distributeManageService->operate_user_money($val['user_id'], $val['award_money'], $val['award_type'], -1);
                }
                return $this->message(L('update_error'), null, 2);
            }
            //奖励发放完毕,修改活动状态
            $distributeManageService->update_activity_status($id);
            return $this->message(L('update_success'), null, 1);
        }
    }


}
