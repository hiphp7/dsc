<?php

namespace App\Services\Services;

use App\Models\AdminUser;
use App\Models\Goods;
use App\Models\ImDialog;
use App\Models\ImMessage;
use App\Models\ImService;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Common\CommonManageService;
use App\Services\Merchant\MerchantCommonService;

class ServicesManageService
{

    protected $baseRepository;
    protected $dscRepository;
    protected $commonManageService;
    protected $merchantCommonService;
    protected $timeRepository;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        CommonManageService $commonManageService,
        MerchantCommonService $merchantCommonService,
        TimeRepository $timeRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->commonManageService = $commonManageService;
        $this->merchantCommonService = $merchantCommonService;
        $this->timeRepository = $timeRepository;
    }


    /** 客服列表 */
    public function servicesList()
    {
        $filter = [];
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['extension_code'] = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);
        $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);

        /** 获得总记录数据 */

        $res = ImService::where('id', '<>', 0);
        $res = $res->whereHas('getAdminUser', function ($query) use ($filter) {
            //按客服区分搜索
            if ($filter['extension_code'] == 'platform') {
                $query = $query->where('ru_id', 0);
            } elseif ($filter['extension_code'] == 'seller') {
                $query = $query->where('ru_id', '<>', 0);
            }
        });
        $res = $res->with(['getAdminUser' => function ($query) use ($filter) {
            $query = $query->with(['getSellerShopinfo' => function ($query) use ($filter) {
                //关键词搜索
                if ($filter['keyword'] != '') {
                    $query = $query->where(function ($query) use ($filter) {
                        $query->where('shop_name like', 'LIKE', '%' . $filter['keyword'] . '%');
                    });
                }
            }]);
        }]);

        //按客服区分搜索
        if ($filter['extension_code'] == 'deleted') {
            $res = $res->where('status', 0);
        } else {
            $res = $res->where('status', 1);
        }
        //关键词搜索
        if ($filter['keyword'] != '') {
            $res = $res->where(function ($query) use ($filter) {
                $query->where('nick_name', 'LIKE', '%' . $filter['keyword'] . '%');
            });
        }

        $filter['record_count'] = $res->count();

        $filter = page_and_size($filter);

        $list = [];

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])
            ->limit($filter['page_size']);
        $res = $this->baseRepository->getToArrayGet($res);

        foreach ($res as $rows) {
            $rows['shop_name'] = '';
            if (isset($rows['get_admin_user']['get_seller_shopinfo']['shop_name']) && !empty($rows['get_admin_user']['get_seller_shopinfo']['shop_name'])) {
                $rows['shop_name'] = $rows['get_admin_user']['get_seller_shopinfo']['shop_name'];
            }

            $rows['chat_status'] = ($rows['chat_status'] == 0) ? lang('admin/services.already_logged') : lang('admin/services.not_logged');
            $rows['avatar'] = isset($rows['avatar']) ? storage_public('data/images_user/' . $rows['avatar']) : storage_public('data/images_user/no_picture.jpg');
            $list[] = $rows;
        }
        return ['list' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    /** 管理员列表 */
    public function adminList($id = 0)
    {
        //查找商家
        if ($id === 0) {
            //后台管理员
            $res = AdminUser::where('user_id', session('admin_id'));
            $res = $res->with(['service']);
            $row = $this->baseRepository->getToArrayFirst($res);
            $ruId = $row['ru_id'] ?? 0;
            $id = $row['ru_id']['service']['id'] ?? 0;

        } else {
            //编辑商家客服
            $res = ImService::where('id', $id);
            $res = $res->with(['getAdminUser']);
            $res = $this->baseRepository->getToArrayFirst($res);
            $ruId = $res['get_admin_user']['ru_id'] ?? 0;
        }

        //end
        $user_id_array = ImService::where('status', 1)->where('id', '<>', $id)->select('user_id');
        $user_id_array = $this->baseRepository->getToArrayGet($user_id_array);
        $user_id_array = $this->baseRepository->getFlatten($user_id_array);

        $res = AdminUser::where('ru_id', $ruId)->whereNotIn('user_id', $user_id_array);
        $list = $this->baseRepository->getToArrayGet($res);

        return $list;
    }

    /** 接待人次统计 */
    public function statisticsReception($now = false)
    {
        $res = ImDialog::whereRaw(1);

        $nowTime = strtotime(date('Y-m-d', time()));
        if ($now) {
            $res = $res->where('start_time', '>', $nowTime);
        }

        $times = $res->count();
        return $times;
    }

    /** 接待人数统计 */
    public function statisticsReceptionCustomer($now = false)
    {
        $res = ImDialog::distinct();
        $nowTime = strtotime(date('Y-m-d', time()));
        if ($now) {
            $res = $res->where('start_time', '>', $nowTime);
        }
        $times = $res->count('customer_id');
        return $times;
    }

    /**
     * 会话记录
     * 以客户为单位
     */
    public function dialogList($id, $val = 0)
    {
        $res = ImDialog::where('services_id', $id);

        $time = 0;
        if ($val === 0) {
            $time = strtotime(date('Y-m-d', time()));
        } elseif ($val === 1) {
            $time = strtotime('-1 week');
        } elseif ($val === 2) {
            $time = strtotime('-1 month');
        }
        if (!empty($time)) {
            $res = $res->where('start_time', '>', $time);
        }
        $res = $res->orderBy('start_time', 'DESC');

        $res = $this->baseRepository->getToArrayGet($res);

        $temp = [];
        foreach ($res as $k => $v) {
            if (in_array($v['customer_id'], $temp)) {
                unset($res[$k]);
                continue;
            }
            $temp[] = $v['customer_id'];
        }

        foreach ($res as $k => $v) {
            $res[$k]['goods_thumb'] = asset('assets/chat/images/no_picture.jpg');

            if ($v['goods_id'] > 0) {
                $goods_res = Goods::where('goods_id', $v['goods_id']);
                $goods = $this->baseRepository->getToArrayFirst($goods_res);

                $res[$k]['goods_name'] = $goods['goods_name'];
                $res[$k]['goods_thumb'] = $this->dscRepository->getImagePath($goods['goods_thumb']);
            }

            $res[$k]['user_name'] = Users::where('user_id', $v['customer_id'])->value('user_name');
            $res[$k]['user_name'] = $res[$k]['user_name'] ? $res[$k]['user_name'] : '';

            $res[$k]['start_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $v['start_time']);
            $res[$k]['end_time'] = empty($v['end_time']) ? lang('admin/services.not_finished') : date('Y-m-d H:i:s', $v['end_time']);
        }

        return $res;
    }

    /** 会话信息 */
    public function dialog($id)
    {
        $res = ImDialog::where('id', $id);
        $res = $res->with(['getImService', 'getUser']);
        $res = $this->baseRepository->getToArrayFirst($res);
        $res['user_name'] = $res['get_user']['user_name'] ?? '';
        $res['nick_name'] = $res['get_im_service']['nick_name'] ?? '';

        $res['start_time'] = $res['start_time'] ?? 0;
        $res['start_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $res['start_time']);

        return $res;
    }

    /**
     * 消息列表
     *
     * @param $customer_id
     * @param $service_id
     * @param int $page
     * @param string $keyword
     * @param string $date
     * @return array
     */
    public function messageList($customer_id, $service_id, $page = 0, $keyword = '', $date = '')
    {
        $size = 10;
        $start = ($page - 1) * $size;
        $start = (intval($start) < 0) ? 0 : (int)$start;

        $where = [
            'customer_id' => $customer_id,
            'service_id' => $service_id
        ];
        $res = ImMessage::where(function ($query) use ($where) {
            $query = $query->where(function ($query) use ($where) {
                $query->where('from_user_id', $where['customer_id'])
                    ->where('to_user_id', $where['service_id']);
            });
            $query->orWhere(function ($query) use ($where) {
                $query->where('from_user_id', $where['service_id'])
                    ->where('to_user_id', $where['customer_id']);
            });
        });


        if (!empty($keyword)) {
            //关键词
            $res = $res->where(function ($query) use ($keyword) {
                $query->where('message', 'LIKE', '%' . $keyword . '%');
            });
        } elseif (!empty($date)) {
            //日期
            $res = $res->where(function ($query) use ($date) {
                $query->whereRaw("UNIX_TIMESTAMP(FROM_UNIXTIME(add_time, '%Y-%m-%d')) = ?", $date);
            });

        } elseif (!empty($page)) {
            $res = $res->orderBy('add_time');
        } else {
            $res = $res->orderBy('add_time');
        }
        if ($page != -1) {
            $res = $res->offset($start)->limit($size);
        }

        $count = $res->count();
        $res = $this->baseRepository->getToArrayGet($res);

        foreach ($res as $k => $v) {
            $res[$k]['message'] = htmlspecialchars_decode($v['message']);
            $res[$k]['add_time'] = $this->timeRepository->getLocalDate('Y-m-d', $v['add_time']);
        }

        return ['list' => $res, 'count' => ceil($count / $size)];
    }

    /** 已删除客服列表 */
    public function removedServicesList()
    {
        $filter = [];
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        /** 获得总记录数据 */
        $filter['record_count'] = ImService::where('status', 0)->count();

        $filter = page_and_size($filter);
        $list = [];

        $res = ImService::where('status', 0)
            ->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])
            ->limit($filter['page_size']);
        $res = $this->baseRepository->getToArrayGet($res);


        foreach ($res as $rows) {
            $rows['chat_status'] = ($rows['chat_status'] == 1) ? lang('admin/services.landing') : lang('admin/services.not_logged');
            $list[] = $rows;
        }

        return ['list' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    /** 恢复客服 */
    public function backToService($id)
    {
        $data = ['status' => 1];
        ImService::where('status', 0)->where('id', $id)->update($data);

    }
}
