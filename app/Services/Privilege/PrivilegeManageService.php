<?php

namespace App\Services\Privilege;


use App\Models\AdminUser;
use App\Models\Agency;
use App\Models\Cart;
use App\Models\CartCombo;
use App\Models\Role;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Common\CommonManageService;
use App\Services\Merchant\MerchantCommonService;


class PrivilegeManageService
{

    protected $baseRepository;
    protected $timeRepository;
    protected $merchantCommonService;
    protected $dscRepository;
    protected $commonManageService;
    protected $admin_id = 0;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository,
        CommonManageService $commonManageService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
        $this->commonManageService = $commonManageService;
    }

    /* 获取管理员列表 */
    public function getAdminUserList($ru_id)
    {

        $admin_id = $this->commonManageService->getAdminId();

        /* 过滤信息 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        $filter['parent_id'] = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

        /* 分页大小 */
        $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);

        $page_size = request()->cookie('dsccp_page_size');
        if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0) {
            $filter['page_size'] = intval($_REQUEST['page_size']);
        } elseif (intval($page_size) > 0) {
            $filter['page_size'] = intval($page_size);
        } else {
            $filter['page_size'] = 15;
        }

        $res = AdminUser::whereRaw(1);
        if ($filter['keywords']) {
            $res = $res->where(function ($query) use ($filter) {
                $query->where('user_name', 'LIKE', '%' . mysql_like_quote($filter['keywords']) . '%');
            });
        }

        $user_type = $this->dscRepository->getAdminPathType();
        if ($user_type == 0) {
            $res = $this->getWhereRuId($res, $ru_id);
        } elseif ($user_type == 1) {
            $res->where('ru_id', '>', 0);
            if ($filter['parent_id']) {
                $res = $res->where('parent_id', $admin_id);
            } else {
                $res = $res->where('parent_id', '>', 0);
            }
        }

        /* 记录总数 */
        $record_count = $res->count();

        $filter['record_count'] = $record_count;
        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        $res = $res->orderBy('user_id', 'DESC')
            ->offset(($filter['page'] - 1) * $filter['page_size'])
            ->limit($filter['page_size']);
        $list = $this->baseRepository->getToArrayGet($res);

        if ($list) {
            foreach ($list as $key => $val) {
                $list[$key]['ru_name'] = $this->merchantCommonService->getShopName($val['ru_id'], 1);
                $list[$key]['add_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $val['add_time']);
                $list[$key]['last_login'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $val['last_login']);
                if ($val['agency_id']) {
                    $list[$key]['agency'] = $this->getAgencyUser($val['agency_id']);
                }

                $parent_name = AdminUser::where('user_id', $val['parent_id'])->value('user_name');
                $parent_name = $parent_name ? $parent_name : '';

                $list[$key]['parent_name'] = $parent_name;
            }
        }

        $arr = ['list' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /* 清除购物车中过期的数据 */
    public function clearCart()
    {
        /* 取得有效的session */
        $res = Cart::select('session_id')->distinct();
        $res = $res->whereHas('getSessions');
        $valid_sess = $this->baseRepository->getToArrayGet($res);
        $valid_sess = $this->baseRepository->getFlatten($valid_sess);

        if ($valid_sess) {
            // 删除cart中无效的数据
            Cart::whereNotIn('session_id', $valid_sess)->delete();
            // 删除cart_combo中无效的数据 by mike
            CartCombo::whereNotIn('session_id', $valid_sess)->delete();
        }
    }

    /* 获取角色列表 */
    public function getRoleList()
    {
        $res = Role::whereRaw(1);
        $res = $this->baseRepository->getToArrayGet($res);

        return $res;
    }

    /* 查询管理员所属办事处 */
    public function getAgencyUser($agency_id = 0)
    {
        $res = Agency::where('agency_id', $agency_id);
        $res = $this->baseRepository->getToArrayFirst($res);

        return $res;
    }

    /* 管理员查询的权限 -- 店铺查询 start */
    public function getWhereRuId($objects, $ru_id = 0)
    {

        $path = request()->path();

        $admin_id = $this->commonManageService->getAdminId();

        $filter['role_id'] = empty($_REQUEST['role_id']) ? 0 : intval($_REQUEST['role_id']);
        $filter['store_search'] = empty($_REQUEST['store_search']) ? 0 : intval($_REQUEST['store_search']);
        $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
        $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';

        $objects = $objects->where('ru_id', 0);

        if ($filter['role_id'] > 0) {
            $objects = $objects->where('role_id', $filter['role_id']);
        }

        //子管理员 start
        $action_list = AdminUser::where('user_id', $admin_id)->value('action_list');
        $action_list = $action_list ? $action_list : '';

        if ($action_list != 'all') {
            if (strpos($path, 'privilege_seller.php') !== false) {
                $objects = $objects->where('parent_id', $admin_id)
                    ->where('ru_id', $ru_id);
            } else {
                $objects = $objects->where('user_id', $admin_id);
            }
        } else {
            if (strpos($path, 'privilege_seller.php') !== false) {
                $objects = $objects->where('parent_id', $admin_id);
            }
        }
        //子管理员 end

        //管理员查询的权限 -- 店铺查询 start
        if ($filter['store_search'] != 0) {
            if ($ru_id == 0) {
                $store_type = isset($_REQUEST['store_type']) && !empty($_REQUEST['store_type']) ? intval($_REQUEST['store_type']) : 0;

                if ($filter['store_search'] == 1) {
                    $objects = $objects->where('ru_id', $filter['merchant_id']);
                }

                if ($filter['store_search'] > 1) {
                    $objects = $objects->where(function ($query) use ($filter, $store_type) {
                        $query->whereHas('getMerchantsShopInformation', function ($query) use ($filter, $store_type) {
                            if ($filter['store_search'] == 2) {
                                $query->where('rz_shopName', 'LIKE', '%' . mysql_like_quote($filter['store_keyword']) . '%');
                            } elseif ($filter['store_search'] == 3) {
                                $query = $query->where('shoprz_brandName', 'LIKE', '%' . mysql_like_quote($filter['store_keyword']) . '%');
                                if ($store_type) {
                                    $query->where('shopNameSuffix', $store_type);
                                }
                            }
                        });
                    });
                }
            }
        }
        //管理员查询的权限 -- 店铺查询 end

        return $objects;
    }
}
