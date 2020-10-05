<?php

namespace App\Services\Goods;

use App\Models\BookingGoods;
use App\Models\MerchantsShopInformation;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Merchant\MerchantCommonService;

class GoodsBookingManageService
{
    protected $baseRepository;
    protected $merchantCommonService;
    protected $dscRepository;
    protected $timeRepository;

    public function __construct(
        BaseRepository $baseRepository,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository,
        TimeRepository $timeRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
        $this->timeRepository = $timeRepository;
    }

    /**
     * 获取订购信息
     *
     * @access  public
     *
     * @return array
     */
    public function getBooKingList()
    {

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        //ecmoban模板堂 --zhuo end

        /* 查询条件 */
        $filter['keywords'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }

        $filter['seller_list'] = isset($_REQUEST['seller_list']) && !empty($_REQUEST['seller_list']) ? 1 : 0;  //商家和自营订单标识
        $filter['dispose'] = empty($_REQUEST['dispose']) ? 0 : intval($_REQUEST['dispose']);
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'rec_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        //卖场 start
        $filter['rs_id'] = empty($_REQUEST['rs_id']) ? 0 : intval($_REQUEST['rs_id']);
        if ($adminru['rs_id'] > 0) {
            $filter['rs_id'] = $adminru['rs_id'];
        }
        //卖场 end

        //ecmoban模板堂 --zhuo start
        $res = MerchantsShopInformation::select('user_id');
        $res = $res->where('shoprz_brandName', 'LIKE', '%' . mysql_like_quote($filter['keywords']) . '%')
            ->orWhere('shopNameSuffix', 'LIKE', '%' . mysql_like_quote($filter['keywords']) . '%');
        $row = $this->baseRepository->getToArrayGet($res);

        if (empty($row)) {
            $user_id = 0;
        } else {
            foreach ($row as $v) {
                $user[] = $v['user_id'];
            }
            $user_id = implode(',', $user);
        }

        //ecmoban模板堂 --zhuo end

        $res = BookingGoods::whereRaw(1);
        if (!empty($_REQUEST['dispose'])) {
            $res = $res->where('is_dispose', $filter['dispose']);
        }

        //卖场
        $res = $this->dscRepository->getWhereRsid($res, 'user_id', $filter['rs_id']);
        $ru_id = $adminru['ru_id'];
        $res = $res->whereHas('getGoods', function ($query) use ($filter, $user_id, $ru_id) {
            if ($ru_id > 0) {
                $query = $query->where('user_id', $ru_id);
            }
            if (!empty($filter['keywords'])) {
                $query->where('goods_name', 'LIKE', '%' . mysql_like_quote($filter['keywords']) . '%');
                if ($user_id) {
                    $user_id = $this->baseRepository->getExplode($user_id);
                    $query = $query->orWhere(function ($query) use ($user_id) {
                        $query->whereIn('user_id', $user_id);
                    });
                }
            }
            if ($filter['seller_list']) {
                $query = $query->where('user_id', '>', '0');
            } else {
                $query = $query->where('user_id', 0);
            }
        });

        $filter['record_count'] = $res->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 获取活动数据 */
        $res = $res->select('rec_id', 'link_man', 'goods_number', 'booking_time', 'is_dispose', 'goods_id');
        $res = $res->with(['getGoods' => function ($query) use ($filter) {
            $query->select('goods_id', 'goods_name', 'user_id');
        }]);
        $res = $res->offset($filter['start'])->limit($filter['page_size']);

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

        $row = $this->baseRepository->getToArrayGet($res);

        if ($row) {
            foreach ($row as $key => $val) {
                $val['goods_name'] = '';
                $val['user_id'] = 0;
                if (isset($val['get_goods']) && !empty($val['get_goods'])) {
                    $val['goods_name'] = $val['get_goods']['goods_name'];
                    $val['user_id'] = $val['get_goods']['user_id'];
                }
                $val['booking_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $val['booking_time']);
                $val['user_name'] = $rows['shop_name'] = $this->merchantCommonService->getShopName($val['user_id'], 1);
                $row[$key] = $val;
            }
        }

        $filter['keywords'] = stripslashes($filter['keywords']);
        $arr = ['item' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 获得缺货登记的详细信息
     *
     * @param integer $id
     *
     * @return  array
     */
    public function getBooKingInfo($id)
    {
        $res = BookingGoods::where('rec_id', $id);
        $res = $res->with(['getGoods' => function ($query) {
            $query->select('goods_id', 'goods_name');
        }]);
        $res = $res->with(['getUsers' => function ($query) {
            $query->select('user_id', 'user_name');
        }]);
        $res = $this->baseRepository->getToArrayFirst($res);

        $res['goods_name'] = '';
        $res['user_name'] = $GLOBALS['_LANG']['guest_user'];
        if (isset($res['get_goods']) && !empty($res['get_goods'])) {
            $res['goods_name'] = $res['get_goods']['goods_name'];
        }
        if (isset($res['get_users']) && !empty($res['get_users'])) {
            $res['user_name'] = $res['get_users']['user_name'];

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                $res['user_name'] = $this->dscRepository->stringToStar($res['user_name']);
            }
        }

        if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
            $res['email'] = $this->dscRepository->stringToStar($res['email']);
            $res['tel'] = $this->dscRepository->stringToStar($res['tel']);
        }

        /* 格式化时间 */
        $res['booking_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $res['booking_time']);
        if (!empty($res['dispose_time'])) {
            $res['dispose_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $res['dispose_time']);
        }

        return $res;
    }
}
