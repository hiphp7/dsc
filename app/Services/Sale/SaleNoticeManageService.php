<?php

namespace App\Services\Sale;

use App\Models\SaleNotice;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Merchant\MerchantCommonService;

class SaleNoticeManageService
{
    protected $baseRepository;
    protected $timeRepository;
    protected $merchantCommonService;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository
    )
    {

        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
    }

    /**
     * 获取列表
     * @access  public
     * @return  array
     */
    public function saleNoticeList($ru_id = 0)
    {
        /* 查询条件 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? 0 : trim($_REQUEST['keywords']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['send_status'] = empty($_REQUEST['send_status']) ? '' : intval($_REQUEST['send_status']);
        $filter['seller_list'] = isset($_REQUEST['seller_list']) && !empty($_REQUEST['seller_list']) ? 1 : 0;  //商家和自营订单标识

        $res = SaleNotice::whereRaw(1);
        if (!empty($filter['keywords'])) {
            $res = $res->where('email', 'LIKE', '%' . mysql_like_quote($filter['keywords']) . '%');
        }

        if (!empty($filter['send_status'])) {
            $res = $res->where('status', $filter['send_status']);
        }

        $res = $res->whereHas('getGoods', function ($query) use ($ru_id, $filter) {
            if ($ru_id > 0) {
                $query = $query->where('user_id', $ru_id);
            }

            //区分商家和自营
            if (!empty($filter['seller_list'])) {
                $query->where('user_id', '>', 0);
            } else {
                $query->where('user_id', 0);
            }
        });
        //修复记录与数量不一致
        $filter['record_count'] = $res->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 获取数据 */
        $res = $res->with([
            'getUsers',
            'getGoods'
        ]);

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])
            ->limit($filter['page_size']);
        $res = $this->baseRepository->getToArrayGet($res);

        $statusArr = [1 => $GLOBALS['_LANG']['has_been_sent'], 2 => $GLOBALS['_LANG']['unsent'], 3 => $GLOBALS['_LANG']['system_send_fail']];
        $send_typeArr = [1 => $GLOBALS['_LANG']['mail'], 2 => $GLOBALS['_LANG']['short_message']];

        $arr = [];
        if (!empty($res)) {
            foreach ($res as $row) {
                $row['user_name'] = $row['get_users']['user_name'] ?? '';

                if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                    $row['user_name'] = $this->dscRepository->stringToStar($row['user_name']);
                    $row['cellphone'] = $this->dscRepository->stringToStar($row['cellphone']);
                    $row['email'] = $this->dscRepository->stringToStar($row['email']);
                }

                $row['goods_name'] = $row['get_goods']['goods_name'] ?? '';
                $row['shop_price'] = $row['get_goods']['shop_price'] ?? 0;
                $row['ru_id'] = $row['get_goods']['user_id'] ?? 0;


                $row['status'] = $statusArr[$row['status']];
                $row['send_type'] = isset($send_typeArr[$row['send_type']]) ? $send_typeArr[$row['send_type']] : '';
                $row['add_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $row['add_time']);
                $row['shop_name'] = $this->merchantCommonService->getShopName($row['ru_id'], 1);

                $arr[] = $row;
            }
        }

        $filter['keywords'] = stripslashes($filter['keywords']);

        return ['item' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }
}
