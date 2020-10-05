<?php

namespace App\Modules\Admin\Controllers;

use App\Models\Goods;
use App\Models\TeamCategory;
use App\Models\TeamGoods;
use App\Models\TeamLog;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Team\TeamManageService;

/**
 * 拼团模块
 * Class TeamController
 * @package App\Modules\Admin\Controllers
 */
class TeamController extends BaseController
{
    // 分页数量
    protected $page_num = 1;

    protected $timeRepository;
    protected $config;
    protected $dscRepository;
    protected $manageService;

    public function __construct(
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        TeamManageService $manageService
    )
    {
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->manageService = $manageService;
    }

    protected function initialize()
    {
        parent::initialize();
        L(require(resource_path('lang/' . config('shop.lang') . '/admin/team.php')));
        $this->assign('lang', array_change_key_case(L()));
        $files = [
            'clips',
            'payment',
            'transaction',
            'ecmoban'
        ];
        load_helper($files);

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
     * 拼团商品列表
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

        $this->admin_priv('team_manage');

        $goods_name = html_in(request()->input('keyword', ''));
        $audit = request()->input('is_audit', 3);
        $tc_id = request()->input('tc_id', 0);

        $filter = [
            'goods_name' => $goods_name,
            'audit' => $audit,
            'tc_id' => $tc_id,
            'type' => 'list'
        ];

        $offset = $this->pageLimit(route('admin/team/index', $filter), $this->page_num);

        $result = $this->manageService->getTeamGoodsList($filter, $offset);

        $list = $result['list'] ?? [];
        $total = $result['total'] ?? 0;

        $this->assign('audit', $audit);
        $this->assign('tc_id', $tc_id);
        $this->assign('list', $list);

        $team_list = $this->manageService->teamGetTree(0);
        $this->assign('team_list', $team_list);//拼团频道树形

        $this->assign('page', $this->pageShow($total));

        return $this->display();
    }

    /**
     * 添加拼团商品
     */
    public function addgoods()
    {
        $this->admin_priv('team_manage');

        if (request()->isMethod('POST')) {
            $data['team_price'] = request()->input('team_price', 0);
            $data['team_num'] = request()->input('team_num', 0);
            $data['validity_time'] = request()->input('validity_time', 0);
            $data['astrict_num'] = request()->input('astrict_num', 0);
            $data['tc_id'] = request()->input('tc_id', 0);
            $data['is_audit'] = request()->input('is_audit', 0);
            $data['team_desc'] = request()->input('team_desc', '');
            $data['isnot_aduit_reason'] = request()->input('isnot_aduit_reason', '');
            $data['limit_num'] = request()->input('limit_num', 0);

            if (empty($data['team_desc'])) {
                $data['team_desc'] = '';
            }
            if ($data['team_num'] <= 1) {
                return sys_msg(lang('admin/team.team_num_not_less_than_one'));
            }

            if ($data['tc_id'] <= 0) {
                return sys_msg(lang('admin/team.please_team_category'));
            }

            if (is_numeric($data['validity_time']) != true) {
                return sys_msg(lang('admin/team.please_number'));
            }
            if ($data['validity_time'] > 24) {
                return sys_msg(lang('admin/team.team_validity_time_24hour'));
            }

            $id = request()->input('id', '');
            $data['goods_id'] = request()->input('goods_id', 0);
            if ($id > 0) {
                //修改
                if ($data['is_audit'] != 1) {
                    $data['isnot_aduit_reason'] = '';
                }
                TeamGoods::where(['id' => $id])->update($data);

                /* 提示信息 */
                $links = [
                    ['href' => route('admin/team/index'), 'text' => lang('admin/team.back_list')]
                ];
                return sys_msg(lang('admin/team.modify_success'), 0, $links);

            } else {
                //添加
                $count = TeamGoods::where(['goods_id' => $data['goods_id'], 'is_team' => '1'])->count();
                if ($count >= 1) {
                    return sys_msg(lang('admin/team.team_exist'));
                }
                $insertGetId = TeamGoods::insertGetId($data);
                if ($insertGetId) {
                    /* 提示信息 */
                    $links = [
                        ['href' => route('admin/team/index'), 'text' => lang('admin/team.back_list')]
                    ];
                    return sys_msg(lang('admin/team.add_success'), 0, $links);
                } else {
                    return sys_msg(lang('admin/team.add_fail'));
                }
            }
        }

        $id = request()->input('id', '');

        if ($id) {
            //拼团商品信息
            $model = TeamGoods::with([
                'getGoods' => function ($query) {
                    $query->select('goods_id', 'user_id as ru_id', 'goods_name')
                        ->where('is_delete', 0);
                }
            ]);
            $info = $model->where(['id' => $id])
                ->first();
            $info = $info ? $info->toArray() : [];

            $info = collect($info)->merge($info['get_goods'])->except('get_goods')->all();
            $this->assign('info', $info);
        }
        $filter = set_default_filter_new(); //设置默认 分类，品牌列表 筛选

        $this->assign('filter_category_level', 1); //分类等级 默认1
        $this->assign('filter_category_navigation', $filter['filter_category_navigation']);
        $this->assign('filter_category_list', $filter['filter_category_list']);
        $this->assign('filter_brand_list', $filter['filter_brand_list']);

        //写入虚拟已参团人数
        $this->assign('virtual_limit_nim', $this->config['virtual_limit_nim']);

        //频道列表
        $team_list = $this->manageService->teamGetTree(0);
        $this->assign('team_list', $team_list);//拼团频道树形
        return $this->display();
    }

    /**
     * 搜索商品
     */
    public function searchgoods()
    {
        $cat_id = request()->input('cat_id', 0);
        $brand_id = request()->input('brand_id', 0);
        $keywords = html_in(request()->input('keyword', ''));

        $model = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->where('review_status', '>', 2)
            ->where('user_id', 0);

        if ($cat_id > 0) {
            $cat_arr = get_children_new($cat_id);
            $model = $model->whereIn('cat_id', $cat_arr);
        }
        if ($brand_id > 0) {
            $model = $model->where('brand_id', $brand_id);
        }
        if ($keywords) {
            $model = $model->where('goods_name', 'like', '%' . $keywords . '%')
                ->orWhere('goods_sn', 'like', '%' . $keywords . '%')
                ->orWhere('keywords', 'like', '%' . $keywords . '%');
        }

        $row = $model->select('goods_id', 'goods_name', 'shop_price')
            ->limit(50)
            ->orderBy('goods_id', 'DESC')
            ->get();
        $row = $row ? $row->toArray() : [];

        return response()->json(['content' => array_values($row)]);
    }

    /**
     * ajax 点击分类获取下级分类列表
     */
    public function filtercategory()
    {
        $result = ['error' => 0, 'message' => '', 'content' => ''];

        $cat_id = request()->input('cat_id', 0);

        //上级分类列表
        $parent_cat_list = get_select_category($cat_id, 1, true);
        $filter_category_navigation = get_array_category_info($parent_cat_list);
        $cat_nav = "";
        if ($filter_category_navigation) {
            foreach ($filter_category_navigation as $key => $val) {
                if ($key == 0) {
                    $cat_nav .= $val['cat_name'];
                } elseif ($key > 0) {
                    $cat_nav .= " > " . $val['cat_name'];
                }
            }
        } else {
            $cat_nav = lang('admin/team.please_category');
        }
        $result['cat_nav'] = $cat_nav;

        //分类级别
        $filter_category_level = count($parent_cat_list);
        if ($filter_category_level <= 3) {
            $filter_category_list = get_category_list($cat_id, 2);
        } else {
            $filter_category_list = get_category_list($cat_id, 0);
            $filter_category_level -= 1;
        }
        $this->assign('filter_category_level', $filter_category_level); //分类等级
        $this->assign('filter_category_navigation', $filter_category_navigation);
        $this->assign('filter_category_list', $filter_category_list);

        $lang = lang('admin/team');
        $data = compact('filter_category_navigation', 'filter_category_list', 'filter_category_level', 'lang');
        $result['content'] = view('admin.team.filter_team_category', $data)->render();

        return response()->json($result);
    }

    /**
     * ajax 点击获取品牌列表
     */
    public function searchbrand()
    {
        $result = ['error' => 0, 'message' => '', 'content' => ''];

        $goods_id = request()->input('goods_id', 0);

        $filter_brand_list = search_brand_list($goods_id);
        $this->assign('filter_brand_list', $filter_brand_list);

        $lang = lang('admin/team');
        $data = compact('filter_brand_list', 'lang');
        $result['content'] = view('admin.team.team_brand_list', $data)->render();

        return response()->json($result);
    }

    /**
     * ajax 修改商品新品，热销状态
     */
    public function editgoods()
    {
        $result = [
            'error' => 0,
            'message' => '',
            'content' => lang('admin/team.modify_fail')
        ];

        $goods_id = request()->input('goods_id', 0);
        $type = request()->input('type', ''); // is_best, is_hot, is_new

        if (in_array($type, ['is_best', 'is_hot', 'is_new'])) {
            $model = Goods::where('goods_id', $goods_id);
            $type_value = $model->value($type);

            if ($type_value == 1) {
                $model->update([$type => 0]);
            } else {
                $model->update([$type => 1]);
            }
            $result = [
                'error' => 2,
                'message' => '',
                'content' => lang('admin/team.modify_success')
            ];
        }

        return response()->json($result);
    }

    /**
     * 删除拼团商品
     */
    public function removegoods()
    {
        $this->admin_priv('team_manage');

        if (request()->isMethod('POST')) {
            $group_id = request()->input('group', 0);
            $id = request()->input('id');

            if ($group_id == 1) {
                TeamGoods::whereIn('id', $id)->update(['is_team' => 0]);
            } else {
                $goods_id = TeamGoods::whereIn('id', $id)->pluck('goods_id');
                $goods_id = $goods_id ? $goods_id->toArray() : [];

                if ($group_id == 2) {
                    Goods::whereIn('goods_id', $goods_id)->update(['is_best' => 0]);
                } elseif ($group_id == 3) {
                    Goods::whereIn('goods_id', $goods_id)->update(['is_new' => 0]);
                } else {
                    Goods::whereIn('goods_id', $goods_id)->update(['is_hot' => 0]);
                }
            }
            return response()->json(['status' => 0, 'url' => route('admin/team/index')]);
        }

        $id = request()->input('id', 0);

        if (empty($id)) {
            return $this->message(lang('admin/team.please_select_team_goods'), null, 2);
        }
        TeamGoods::where('id', $id)->update(['is_team' => 0]);

        return redirect()->route('admin/team/index');
    }

    /**
     * 拼团频道列表
     */
    public function category()
    {
        $this->admin_priv('team_manage');
        $tc_id = request()->input('tc_id', 0);

        $list = TeamCategory::where('parent_id', $tc_id)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->get();

        $list = $list ? $list->toArray() : [];

        foreach ($list as $key => $val) {
            $list[$key]['goods_number'] = $this->manageService->getCategroyNumber($val['id']);
        }

        $this->assign('list', $list);
        return $this->display();
    }

    /**
     * 添加拼团频道
     */
    public function addcategory()
    {
        $this->admin_priv('team_manage');

        if (request()->isMethod('POST')) {
            $data = request()->input('data');
            $data['content'] = $data['content'] ?? '';
            $data['sort_order'] = $data['sort_order'] ?? 0;
            $parent_id1 = request()->input('parent_id1');
            //修改时频道不能选择自己成为顶级频道
            if ($data['parent_id'] == $data['id']) {
                return $this->message(lang('admin/team.team_category_no_top'), route('admin/team/addcategory', ['tc_id' => $data['id']]));
            }
            //修改时顶级频道不能改为二级频道
            if (!empty($data['id'])) {//修改
                if (!empty($data['parent_id']) && $parent_id1 == 0) {
                    return $this->message(lang('admin/team.team_category_is_top'), route('admin/team/addcategory', ['tc_id' => $data['id']]));
                }
            }
            if (empty($data['name'])) {
                return $this->message(lang('admin/team.team_name_empty'));
            }
            $data['tc_img'] = '';
            if ($data['parent_id'] > 0) {
                // 频道图片处理
                $file = request()->file('tc_img');
                $icon_path = '';
                if ($file && $file->isValid()) {
                    $result = $this->upload('data/team_img', true);
                    if ($result['error'] > 0) {
                        return $this->message($result['message']);
                    }
                    $data['tc_img'] = 'data/team_img/' . $result['file_name'];
                } else {
                    if ($data['id']) {
                        $icon_path = TeamCategory::where(['id' => $data['id']])->value('tc_img');
                        $data['tc_img'] = $icon_path;
                    }
                }

                // 验证
                if (empty($data['tc_img'])) {
                    return $this->message(lang('admin/team.team_tc_img')); // 频道小图标不能为空
                }
            }


            // oss图片处理
            if ($this->config['open_oss'] == 1) {
                $bucket_info = $this->dscRepository->getBucketInfo();
                $bucket_info['endpoint'] = empty($bucket_info['endpoint']) ? $bucket_info['outside_site'] : $bucket_info['endpoint'];
                $http = rtrim($bucket_info['endpoint'], '/') . '/';
                $data['tc_img'] = str_replace($http, '', $data['tc_img']);
                // 编辑时 删除oss图片
                $icon_path = str_replace($http, '', $icon_path);
            }
            // 路径转换
            if (strtolower(substr($data['tc_img'], 0, 4)) == 'http') {
                $data['tc_img'] = str_replace(url('/'), '', $data['tc_img']);
                // 编辑时 删除原图片
                $icon_path = str_replace(url('/'), '', $icon_path);
            }

            // 不保存默认空图片
            if (strpos($data['tc_img'], 'no_image') !== false) {
                unset($data['tc_img']);
            }

            if (empty($data['id'])) {
                //添加
                if ($data['parent_id'] > 0) {
                    $count = TeamCategory::where(['parent_id' => $data['parent_id']])->count();
                    if ($count >= 4) {
                        return $this->message(lang('admin/team.team_category_child_limit4'), route('admin/team/category'));
                    }
                }
                TeamCategory::create($data);
            } else {
                // 删除原图片
                if ($data['tc_img'] && $icon_path != $data['tc_img']) {
                    $icon_path = strpos($icon_path, 'no_image') == false ? $icon_path : '';  // 不删除默认空图片
                    $this->remove($icon_path);
                }
                //修改
                TeamCategory::where(['id' => $data['id']])->update($data);
            }
            return redirect()->route('admin/team/category');
        }

        $tc_id = request()->input('tc_id', 0);
        if ($tc_id > 0) {
            $team_category = TeamCategory::where(['id' => $tc_id])->first();
            $team_category = $team_category ? $team_category->toArray() : [];
            if ($team_category) {
                $team_category['tc_img'] = isset($team_category['tc_img']) ? get_image_path($team_category['tc_img']) : '';
            }
            $this->assign('cat_info', $team_category);
            $this->assign('page_title', lang('admin/team.team_category_edit'));
        } else {
            $this->assign('page_title', lang('admin/team.team_category_add'));
        }
        $parent_id = request()->input('parent_id', 0);
        if ($parent_id) {
            //新增下一级
            $cat_info['parent_id'] = $parent_id;
            $this->assign('cat_info', $cat_info);
        }
        //主频道
        $cat_select = TeamCategory::where(['parent_id' => 0])->get();

        $cat_select = $cat_select ? $cat_select->toArray() : [];

        $this->assign('cat_select', $cat_select);
        return $this->display();
    }

    /**
     * 删除拼团频道
     */
    public function removecategory()
    {
        $this->admin_priv('team_manage');

        $tc_id = request()->input('tc_id', 0);

        if (empty($tc_id)) {
            return $this->message(lang('admin/team.please_team_category'), null, 2);
        }
        $tc_id = $this->manageService->getCategroyId($tc_id); //获取频道id

        TeamCategory::whereIn('id', $tc_id)->delete();

        return redirect()->route('admin/team/category');
    }

    /**
     * ajax 修改频道状态
     */
    public function editstatus()
    {
        $result = [
            'error' => 0,
            'message' => '',
            'content' => lang('admin/team.modify_fail')
        ];

        $cat_id = request()->input('cat_id', 0);

        if ($cat_id) {
            $model = TeamCategory::where('id', $cat_id);

            $status = $model->value('status');
            if ($status == 1) {
                $model->update(['status' => 0]);
            } else {
                $model->update(['status' => 1]);
            }
            $result = [
                'error' => 2,
                'message' => '',
                'content' => lang('admin/team.modify_success')
            ];
        }

        return response()->json($result);
    }

    /**
     * 团队信息列表
     */
    public function teaminfo()
    {
        $this->admin_priv('team_manage');

        $status = request()->input('status', 1);

        if (request()->isMethod('POST')) {
            $goods_name = request()->input('keyword', '');
        }

        $where = [
            'time' => $this->timeRepository->getGmTime(),
            'status' => $status,
            'goods_name' => $goods_name ?? '',
        ];

        $offset = $this->pageLimit(route('admin/team/teaminfo', ['status' => $status]), $this->page_num);

        $result = $this->manageService->getTeamInfo($offset, $where);

        $list = $result['list'] ?? [];
        $total = $result['total'] ?? 0;

        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        $this->assign('status', $status);

        return $this->display();
    }

    /**
     * 团队订单
     */
    public function teamorder()
    {
        $this->admin_priv('team_manage');

        $team_id = request()->input('team_id', 0);
        if (empty($team_id)) {
            return $this->message(lang('admin/team.please_select_team_goods'), null, 2);
        }

        $offset = $this->pageLimit(route('admin/team/teamorder'), $this->page_num);
        $result = $this->manageService->getTeamOrder($offset, $team_id);
        $list = $result['list'] ?? [];
        $total = $result['total'] ?? 0;

        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        return $this->display();
    }

    /**
     * 移除拼团信息
     */
    public function removeteam()
    {
        $this->admin_priv('team_manage');

        $team_id = request()->input('team_id', 0);

        if (empty($team_id)) {
            return $this->message(lang('admin/team.please_select_team_goods'), null, 2);
        }
        if ($team_id) {
            TeamLog::whereIn('team_id', $team_id)->update(['is_show' => 0]);
        }
        if (request()->isMethod('POST')) {
            return response()->json(['url' => route('admin/team/teaminfo')]);
        } else {
            return redirect()->route('admin/team/teaminfo');
        }
    }

    /**
     * 拼团商品回收站
     */
    public function teamrecycle()
    {
        $this->admin_priv('team_manage');

        $goods_name = html_in(request()->input('keyword', ''));

        $filter = [
            'goods_name' => $goods_name,
            'type' => 'recycle'
        ];

        $offset = $this->pageLimit(route('admin/team/teamrecycle'), $this->page_num);

        $result = $this->manageService->getTeamGoodsList($filter, $offset);

        $list = $result['list'] ?? [];
        $total = $result['total'] ?? 0;

        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        return $this->display();
    }

    /**
     * 恢复拼团商品
     */
    public function recycleegoods()
    {
        $this->admin_priv('team_manage');

        $id = request()->input('id', 0);

        if (empty($id)) {
            return $this->message(lang('admin/team.please_select_team_goods'), null, 2);
        }
        if ($id) {
            TeamGoods::whereIn('id', $id)->update(['is_team' => 1]);
        }
        if (request()->isMethod('POST')) {
            return response()->json(['url' => route('admin/team/teamrecycle')]);
        } else {
            return redirect()->route('admin/team/teamrecycle');
        }
    }

}
