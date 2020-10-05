<?php

namespace App\Custom\Distribute\Controllers\Seller;

use App\Custom\BaseSellerController as BaseController;
use App\Custom\CustomView;
use App\Custom\Distribute\Models\DrpActivityDetailes;
use App\Custom\Distribute\Models\DrpRewardLog;
use App\Custom\Distribute\Services\DistributeManageService;
use App\Models\Users;
use App\Repositories\Common\TimeRepository;
use App\Services\Common\OfficeService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;


class DrpController extends BaseController
{
    use CustomView;

    protected $page_num = 10;
    protected $ru_id = 0;

    protected $distributeManageService;
    protected $timeRepository;

    public function __construct(
        DistributeManageService $distributeManageService,
        TimeRepository $timeRepository
    )
    {
        $this->distributeManageService = $distributeManageService;
        $this->timeRepository = $timeRepository;
    }

    protected function initialize()
    {
        parent::initialize();

        $this->load_helper('helpers');

        // 当前模块语言包
        $_lang = $this->load_lang(['common', 'drp']);
        L($_lang);
        $this->assign('lang', array_change_key_case(L()));

        // 当前位置
        $postion = ['ur_here' => $this->menu_select['label'] ?? ''];
        $this->assign('postion', $postion);
    }

    /**
     * 分销商活动列表
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|string
     */
    public function activity_list(Request $request)
    {
        // 搜索
        $keywords = request()->input('keyword', '');
        $seller_list = request()->input('seller_list', 0);//0为平台  1为店铺

        //分页信息
        $filter['keywords'] = $keywords;
        $filter['seller_list'] = $seller_list;
        $filter['ru_id'] = $this->ru_id;
        $offset = $this->pageLimit(route('distribute.seller.activity_list', $filter), $this->page_num);

        $res = $this->distributeManageService->get_all_activity_list($filter, $offset);

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
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|string
     */
    public function user_activity_list(Request $request)
    {
        // 搜索
        $keywords = $request->input('keyword', '');
        $activity_type = $request->input('activity_type', 0);
        $seller_list = $request->input('seller_list', 0);//0为平台  1为店铺

        //分页信息
        $filter['keywords'] = $keywords;
        $filter['activity_type'] = $activity_type;
        $filter['seller_list'] = $seller_list;
        $filter['ru_id'] = $this->ru_id;
        $offset = $this->pageLimit(route('distribute.seller.user_activity_list', $filter), $this->page_num);

        $all_users = Users::where('user_name', 'like', '%' . $keywords . '%')->get('user_id');
        $all_users = $all_users ? $all_users->toArray() : [];

        if (!empty($all_users)) {
            $all_users = Arr::dot($all_users);
        }
        //获取会员列表信息
        $res = $this->distributeManageService->get_all_user_activity_list($all_users, $filter, $offset);
        $total = $res['total'];
        $all_activity = $res['all_activity'];

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
            $present_time = app(TimeRepository::class)->getGmTime();
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
        if ($request->isMethod('Get')) {
            $id = $request->input('id', 0);
            if (empty($id)) {
                return $this->message(L('error_delete'), null, 2);
            }

            if (empty($this->ru_id)) {
                return $this->message(L('error_delete'), null, 2);
            }
            $activity_finish = DrpActivityDetailes::where('id', $id)->value('act_name');
            if (empty($activity_finish)) {
                return $this->message(L('error_delete'), null, 2);
            }

            $res = DrpActivityDetailes::where('id', $id)->where('ru_id', $this->ru_id)->delete();
            if ($res) {
                return $this->message(L('success_delete'), route('distribute.seller.activity_list'), 1);
            } else {
                return $this->message(L('error_delete'), null, 2);
            }
        }
        return $this->message(L('error_delete'), null, 2);
    }

    /**
     * 分销商活动编辑页面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|string
     */
    public function activity_info(Request $request)
    {

        $id = $request->input('id', 0);

        $cat_id = $request->input('cate_id', 0);

        $letter = [];
        for ($i = 65; $i < 91; $i++) {
            $letter[] = strtoupper(chr($i));
        }
        $activity_detail = [];
        if (!empty($id)) {
            $activity_detail = DrpActivityDetailes::from('drp_activity_detailes as d')->leftjoin('goods as g', 'd.goods_id', '=', 'g.goods_id')->where('d.id', $id)->first();
            if (is_null($activity_detail)) {
                $activity_detail = [];
            } else {
                $activity_detail = $activity_detail->toArray();
                $activity_detail['start_time'] = app(TimeRepository::class)->getLocalDate('Y-m-d H:i:s', $activity_detail['start_time']);
                $activity_detail['end_time'] = app(TimeRepository::class)->getLocalDate('Y-m-d H:i:s', $activity_detail['end_time']);
            }
        }

        // 商品系统分类
        $parent_id = $cat_id ?? 0;
        if (!empty($parent_id)) {
            //上级分类名称导航
            $parent_category = $this->distributeManageService->get_every_category($parent_id, $this->ru_id);
            $this->assign('parent_category', $parent_category);

            // 设置默认 分类 筛选
            $filter = $this->distributeManageService->set_default_filter(0, $parent_id, $this->ru_id);
        } else {
            // 设置默认 分类 筛选
            $filter = $this->distributeManageService->set_default_filter(0, 0, $this->ru_id);
        }
        $this->assign('filter_category_level', 1); //分类等级 默认1
        $this->assign('filter_category_navigation', $filter['filter_category_navigation']);
        $this->assign('filter_category_list', $filter['filter_category_list']);
        $this->assign('letter', $letter);

        $this->assign('primary_cat', $GLOBALS['_LANG']['02_promotion']);
        $this->assign('ru_id', $this->ru_id);
        $this->assign('activity_detail', $activity_detail);
        //$this->assign('menu_select', ['action' => '02_promotion', 'current' => '06_drp_activity', 'current_2' => '06_drp_activity']);
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
            $ru_id = $this->ru_id;
            if (!isset($ru_id) || empty($ru_id)) {
                return $this->message(L('act_name_empty'), null, 2);
            }
            $data['ru_id'] = $ru_id;
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
                $data['start_time'] = app(TimeRepository::class)->getLocalStrtoTime($data['start_time']);
            }

            if (empty($data['end_time'])) {
                return $this->message(L('end_time_empty'), null, 2);
            } else {
                $data['end_time'] = app(TimeRepository::class)->getLocalStrtoTime($data['end_time']);
            }

//            if (empty($data['text_info'])) {
//                return $this->message(L('text_info_empty'), null, 2);
//            }

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
                $data['add_time'] = app(TimeRepository::class)->getGmTime();
                $data['is_finish'] = 1;
                $res = DrpActivityDetailes::insertGetId($data);
            } else {
                //修改活动
                $res = DrpActivityDetailes::where('id', $data['id'])->update($data);
            }

            if (empty($res)) {
                return $this->message(L('update_error'), null, 2);
            } else {
                return $this->message(L('update_success'), route('distribute.seller.activity_list'), 1);
            }
        } else {
            return $this->message(L('data_empty'), null, 2);
        }

    }

    /**
     * 活动详情   展示活动的详细信息以及参与用户信息
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View|string
     */
    public function activity_details(Request $request)
    {
        $id = $request->input('id', '0');
        if (empty($id)) {
            return redirect()->route('distribute.seller.activity_list');
        }
        //获取活动详情
        $activity_res = DrpActivityDetailes::with([
            'AdminUser' => function ($query) {
                $query->select('user_id', 'user_name');
            }])
            ->where('id', $id)->first();
        $activity_res = $activity_res ? $activity_res->toArray() : [];
        if (!empty($activity_res)) {
            $activity_res['start_time_format'] = empty($activity_res['start_time']) ? '' : app(TimeRepository::class)->getLocalDate('Y-m-d H:i:s', $activity_res['start_time']);
            $activity_res['end_time_format'] = empty($activity_res['end_time']) ? '' : app(TimeRepository::class)->getLocalDate('Y-m-d H:i:s', $activity_res['end_time']);
        }

        //分页信息
        $offset = $this->pageLimit(route('distribute.seller.activity_details', []), $this->page_num);
        //获取参与活动的分销商详情
        $all_activity_user = DrpRewardLog::from('drp_reward_log as d')->leftjoin('users as u', 'd.user_id', '=', 'u.user_id')->where('activity_id', $id)->where('activity_type', 0)->offset($offset['start'])
            ->limit($offset['limit'])->get();
        $all_activity_user = $all_activity_user ? $all_activity_user->toArray() : [];
        $all_order_statistics = $this->distributeManageService->statistics_order_activity_message($id, $activity_res['goods_id']);
        $all_activity_user = $this->sort_unfinish_array($all_order_statistics, $all_activity_user);
        $this->assign('all_order_statistics', $all_order_statistics);
        $this->assign('all_activity_user', $all_activity_user);
        $this->assign('page', $this->pageShow(count($all_activity_user)));
        $this->assign('activity_res', $activity_res);
        $this->assign('menu_select', ['action' => '02_promotion', 'current' => 'seller_activity_list', 'current_2' => 'seller_activity_list']);

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
     * 分销商活动统计记录表
     * @param Request $request
     * @return bool
     */
    public function activity_details_export(Request $request)
    {
        // 导出
        if ($request->isMethod('POST')) {
            $id = $request->input('id', '0');

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

            $all_activity_user_log = $this->distributeManageService->set_activity_user_log($id, $condition);

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

        }
        return $this->message(L('error_list_export'), null, 2);
    }


}
