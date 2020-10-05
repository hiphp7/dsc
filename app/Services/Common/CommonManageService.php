<?php

namespace App\Services\Common;

use App\Models\AdminUser;
use App\Models\GiftGardLog;
use App\Models\MerchantsRegionArea;
use App\Models\MerchantsRegionInfo;
use App\Models\OrderInfo;
use App\Models\SellerShopinfo;
use App\Models\StoreUser;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Merchant\MerchantCommonService;

class CommonManageService
{
    protected $dscRepository;
    protected $baseRepository;
    protected $timeRepository;
    protected $commonRepository;
    protected $merchantCommonService;

    public function __construct(
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        CommonRepository $commonRepository,
        MerchantCommonService $merchantCommonService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->commonRepository = $commonRepository;
        $this->merchantCommonService = $merchantCommonService;
    }

    /**
     * 重组属性数组
     *
     * @param array $attribute_list
     * @return array
     */
    public function getNewGoodsAttr($attribute_list = [])
    {
        $arr = [];
        $arr['attr'] = [];  //属性
        $arr['spec'] = [];  //规格

        if ($attribute_list) {
            foreach ($attribute_list as $key => $val) {
                if ($val['attr_type'] == 0) {
                    $arr['attr'][$key] = $val;
                } else {
                    $arr['spec'][$key] = $val;
                }
            }

            $arr['attr'] = !empty($arr['attr']) ? array_values($arr['attr']) : [];
            $arr['spec'] = !empty($arr['spec']) ? array_values($arr['spec']) : [];
        }

        return $arr;
    }

    /**
     * 店铺信息
     *
     * @return array
     * @throws \Exception
     */
    public function getSellerInfo()
    {
        $admin_user = $this->getAdminIdSeller(); // seller info (管理员)

        $seller_info = [];
        if ($admin_user) {
            $seller_info = SellerShopinfo::where('ru_id', $admin_user['ru_id']);
            $seller_info = $this->baseRepository->getToArrayFirst($seller_info);
        }

        /* 商家信息 */
        if ($seller_info) {
            $admin_user['admin_user_img'] = $this->dscRepository->getImagePath($admin_user['admin_user_img']);

            $seller_info = $this->baseRepository->getArrayMerge($admin_user, $seller_info);

            //转换时间;
            $seller_info['last_login'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $seller_info['last_login']);
            $seller_info['shopName'] = $this->merchantCommonService->getShopName($seller_info['ru_id'], 1);

            if ($seller_info['logo_thumb']) {
                $seller_info['logo_thumb'] = str_replace('../', '', $seller_info['logo_thumb']);
                $seller_info['logo_thumb'] = $this->dscRepository->getImagePath($seller_info['logo_thumb']);
            }
        }

        return $seller_info;
    }

    /**
     * 获取入驻商家的前台会员ID
     *
     * @return array
     */
    public function getAdminIdSeller()
    {
        $admin_id = $this->getAdminId();

        $res = AdminUser::where('user_id', $admin_id);
        $res = $this->baseRepository->getToArrayFirst($res);

        return $res;
    }

    /**
     * 后台管理员ID (admin : 平台后台, seller : 店铺后台, supply : 供应商后台)
     *
     * @return int
     */
    public function getAdminId()
    {
        $self = explode("/", substr(request()->getRequestUri(), 1));
        $count = count($self);

        $admin_id = 0;
        if ($count > 1) {
            $real_path = $self['0'];
            if ($real_path == ADMIN_PATH) {
                $admin_id = intval(session('admin_id', 0));
            } elseif ($real_path == SELLER_PATH) {
                $admin_id = intval(session('seller_id', 0));
            } elseif ($real_path == STORES_PATH) {
                $admin_id = intval(session('stores_id', 0));
            } elseif ($real_path == SUPPLLY_PATH) {
                $supplier_enabled = $this->commonRepository->judgeSupplierEnabled();
                if ($supplier_enabled) {
                    $admin_id = intval(session('supply_id', 0));
                }
            }
        }

        return $admin_id;
    }

    /**
     * 获得所有模块的名称以及链接地址
     *
     * @access      public
     * @param string $directory 插件存放的目录
     * @return      array
     */
    public function readModules($directory = '.')
    {
        $modules = [];
        foreach (glob($directory . '/*/config.php') as $key => $val) {
            $modules[] = include_once($val);
        }

        return $modules;
    }

    /**
     * 获取URL文件名称
     *
     * @param int $type
     * @return bool|string
     */
    public function getPhpSelf($type = 0)
    {
        $php_self = substr(PHP_SELF, strrpos(PHP_SELF, '/') + 1);

        if ($type == 1) {
            $self = explode('.', $php_self);
            $php_self = $self[0];
        }

        return $php_self;
    }

    /**
     * 获取后台类型
     * 0、门店后台
     * 1、平台后台
     * 2、商家后台
     * 3、供应链后台
     *
     * @return int
     */
    public function isAdminSellerPath()
    {
        $supplierEnabled = $this->commonRepository->judgeSupplierEnabled();

        $return = 4;
        if (defined('PHP_SELF')) {
            $self = explode("/", substr(PHP_SELF, 1));
            $count = count($self);

            if ($count > 1) {
                $real_path = $self['0'];
                if ($real_path == ADMIN_PATH) {
                    $return = 1;
                } elseif ($real_path == SELLER_PATH) {
                    $return = 2;
                } elseif ($real_path == STORES_PATH) {
                    $return = 0;
                } elseif ($supplierEnabled && $real_path == SUPPLLY_PATH) {
                    $return = 3;
                }
            }
        }

        return $return;
    }

    /**
     * 礼品卡日志记录
     *
     * @param int $id
     * @param string $type
     * @return array
     * @throws \Exception
     */
    public function getGiftGardLog($id = 0, $type = 'gift_gard')
    {
        $filter['id'] = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : $id;

        $row = GiftGardLog::where('gift_gard_id', $filter['id'])
            ->where('handle_type', $type);

        $res = $record_count = $row;

        $filter['record_count'] = $record_count->count();

        $filter = page_and_size($filter);

        $res = $res->orderBy('addtime', 'desc');

        if ($filter['start'] > 0) {
            $res = $res->skip($filter['start']);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $k => $v) {
                $v['user_name'] = AdminUser::where('user_id', $v['admin_id'])->value('user_name');

                $res[$k] = $v;

                if ($v['addtime'] > 0) {
                    $res[$k]['add_time'] = $this->timeRepository->getLocalDate("Y-m-d  H:i:s", $v['addtime']);
                }

                if ($v['delivery_status'] == 0) {
                    $res[$k]['delivery_status'] = $GLOBALS['_LANG']['no_settlement'];
                } elseif ($v['delivery_status'] == 1) {
                    $res[$k]['delivery_status'] = $GLOBALS['_LANG']['is_settlement'];
                } elseif ($v['delivery_status'] == 2) {
                    $res[$k]['delivery_status'] = lang('manage/common.relieve_freeze');
                } elseif ($v['delivery_status'] == 3) {
                    $res[$k]['delivery_status'] = lang('manage/common.freeze');
                }

                if ($v['gift_gard_id']) {
                    $res[$k]['gift_sn'] = OrderInfo::where('order_id', $v['gift_gard_id'])->value('order_sn');
                }
            }
        }

        $arr = ['pzd_list' => $res, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
        return $arr;
    }

    /**
     * 获取区域管理地区列表
     *
     * @param int $ra_id
     * @return array
     */
    public function getAreaRegionInfoList($ra_id = 0)
    {
        $res = MerchantsRegionInfo::select('region_id')->whereRaw(1);

        if ($ra_id > 0) {
            $res = $res->where('ra_id', $ra_id);
        }

        $res = $res->with('getRegionWarehouse');

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $row) {
                $row = $this->baseRepository->getArrayMerge($row, $row['get_region_warehouse']);

                $res[$key] = $row;
            }
        }

        return $res;
    }

    /**
     * 区域管理列表
     *
     * @return array
     * @throws \Exception
     */
    public function getAreaRegionList()
    {
        $cache_name = 'get_area_region_list';

        $res = cache($cache_name);

        if (is_null($res)) {
            $res = MerchantsRegionArea::whereRaw(1)->orderBy('ra_sort');
            $res = $this->baseRepository->getToArrayGet($res);

            cache()->forever($cache_name, $res);
        }

        $arr = [];
        if ($res) {
            foreach ($res as $key => $row) {
                $arr[$key]['ra_id'] = $row['ra_id'];
                $arr[$key]['ra_name'] = $row['ra_name'];
                $arr[$key]['area'] = $this->getAreaRegionInfoList($row['ra_id']);
            }
        }

        return $arr;
    }

    /**
     * 后台管理员ID
     *
     * @return \Illuminate\Session\SessionManager|\Illuminate\Session\Store|mixed|string
     */
    public function getAdminName()
    {
        $self = explode("/", substr(PHP_SELF, 1));
        $count = count($self);

        $admin_name = '';
        if ($count > 1) {
            $real_path = $self[$count - 2];
            if ($real_path == ADMIN_PATH) {
                $admin_name = session('admin_name', '');
            } elseif ($real_path == SELLER_PATH) {
                $admin_name = session('seller_name', '');
            } elseif ($real_path == SUPPLLY_PATH) {
                $supplierEnabled = $this->commonRepository->judgeSupplierEnabled();
                if ($supplierEnabled) {
                    $admin_name = session('supply_name', '');
                }
            }
        }

        return $admin_name;
    }

    //页面编码为utf-8时使用，否则导出的中文为乱码
    public function i($strInput)
    {
        if ($strInput && !is_array($strInput)) {
            return iconv('utf-8', 'gb2312//TRANSLIT//IGNORE', $strInput);//页面编码为utf-8时使用，否则导出的中文为乱码
        } else {
            if ($strInput) {
                if (is_array($strInput)) {
                    return '';
                }
            } else {
                return '';
            }
        }
    }

    /*
    * 检查登录状态
    */
    public function loginStatus($table = '')
    {
        if (!empty($table)) {
            if ($table == 'store') {
                $admin_id = session('stores_id');
                $user = StoreUser::where('store_id', $admin_id);
            } else { // 参数错误则不检查
                return 0;
            }
        } else {
            // 状态异常时退出登录
            $admin_id = get_admin_id();
            $user = AdminUser::where('user_id', $admin_id);
        }

        $status = $user->value('login_status');

        $time = $this->timeRepository->getGmTime();
        $login_status = 1;

        if (!empty($status)) {
            $status_arr = explode(',', $status);
            $old_count = count($status_arr);

            foreach ($status_arr as $key => $hash_row) {
                $row = explode('|', $hash_row);

                if (empty($row[0]) && empty($row[1])) {
                    unset($status_arr[$key]);
                    continue;
                }

                $validate = $row[1] + 24 * 60 * 60; // 有效时间24小时

                if ($time > $validate) {
                    unset($status_arr[$key]);
                    continue;
                }

                if ($row[0] != session('admin_login_hash') && $row[0] != session('seller_login_hash') && $row[0] != session('store_login_hash') && $row[0] != session('supplier_login_hash')) {
                    continue;
                }

                $login_status = 0;
            }

            $new_count = count($status_arr);

            if ($old_count != $new_count) {
                $user->update(['login_status' => implode(',', $status_arr)]);
            }
        }

        return $login_status;
    }

    /*
    * 插入登录hash
    */
    public function updateLoginStatus($login_hash, $table = '')
    {
        $time = $this->timeRepository->getGmTime();
        if (!empty($table)) {
            if ($table == 'store') {
                $admin_id = session('stores_id');
                $user = StoreUser::where('store_id', $admin_id);
            } else { // 参数错误则不检查
                return 0;
            }
        } else {
            $admin_id = get_admin_id();
            $user = AdminUser::where('user_id', $admin_id);
        }

        $status = $user->value('login_status');

        if (empty($status)) {
            $login_status = $login_hash . '|' . $time;

            $user->update(['login_status' => $login_status]);
        } else {
            $login_status = $status . ',' . $login_hash . '|' . $time;
            $user->update(['login_status' => $login_status]);
        }
    }

}
