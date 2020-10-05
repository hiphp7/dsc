<?php

namespace App\Services\Wholesale;

use App\Models\Users;
use App\Models\WholesaleCat;
use App\Models\WholesalePurchase;
use App\Models\WholesalePurchaseGoods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Merchant\MerchantCommonService;

class PurchaseService
{
    protected $baseRepository;
    protected $timeRepository;
    protected $wholesaleService;
    protected $merchantCommonService;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        WholesaleService $wholesaleService,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->wholesaleService = $wholesaleService;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
    }

    /**
     * 获取求购单列表
     *
     * @param array $filter
     * @param int $size
     * @param int $page
     * @param string $sort
     * @param string $order
     * @return array
     */
    public function getPurchaseList($filter = [], $size = 10, $page = 1, $sort = "add_time", $order = "DESC")
    {
        $row = WholesalePurchase::whereRaw(1);

        //会员编号
        if (isset($filter['user_id'])) {
            $row = $row->where('user_id', $filter['user_id']);
        }

        //求购单操作状态
        if (isset($filter['status'])) {
            $row = $row->where('status', $filter['status']);
        }
        //求购单审核状态
        if (isset($filter['review_status'])) {
            $row = $row->where('review_status', $filter['review_status']);
        }

        //求购单结束状态
        if (isset($filter['is_finished'])) {
            $now = $this->timeRepository->getGmTime();
            if ($filter['is_finished'] == 0) {
                $row = $row->where('end_time', '>', $now);
            } elseif ($filter['is_finished'] == 1) {
                $row = $row->where('end_time', '<', $now);
            }
        }

        //关键词
        if (isset($filter['keyword']) && $filter['keyword']) {
            $row = $row->where('subject', 'like', '%' . $filter['keyword'] . '%');
        }

        //查询开始时间
        if (isset($filter['start_date'])) {
            $row = $row->where('add_time', '>', $filter['start_date']);
        }
        //查询结束时间
        if (isset($filter['end_date'])) {
            $row = $row->where('add_time', '<', $filter['end_date']);
        }

        $res = $count = $row;

        //总数
        $record_count = $count->count();

        $page_count = $record_count > 0 ? ceil($record_count / $size) : 1;

        $res = $res->orderBy($sort, $order);

        //分页
        $start = ($page - 1) * $size;
        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        $time = $this->timeRepository->getGmTime();

        //处理
        $arr = [];
        if ($res) {
            foreach ($res as $key => $row) {
                $add_time = $row['add_time'];
                $end_time = $row['end_time'];

                $row['left_day'] = ($end_time - $time > 0) ? (floor(($end_time - $time) / 86400) > 0 ? floor(($end_time - $time) / 86400) : '-1') : 0;
                $row['add_time'] = $this->timeRepository->getLocalDate('Y-m-d', $add_time);
                $row['add_time_complete'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $add_time);
                $row['end_time_complete'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $end_time);

                $number = WholesalePurchaseGoods::where('purchase_id', $row['purchase_id']);
                $number = $this->baseRepository->getToArrayGet($number);
                $goods_number = $this->baseRepository->getSum($number, 'goods_number');

                //所有商品数量之和
                $row['goods_number'] = $goods_number;

                //获取商品的图片
                $goods_img = WholesalePurchaseGoods::where('purchase_id', $row['purchase_id'])
                    ->where('goods_img', '<>', '')
                    ->orderBy('goods_id')
                    ->value('goods_img');

                if ($goods_img) {
                    $goods_img = unserialize($goods_img);
                    $row['img'] = $this->dscRepository->getImagePath(reset($goods_img));
                }

                //商家名称
                $row['shop_name'] = $this->merchantCommonService->getShopName($row['user_id'], 1);

                //是否认证
                $row['is_verified'] = $this->wholesaleService->getCheckUsersReal($row['user_id'], 1);

                //商家地区
                $row['area_info'] = $this->wholesaleService->getSellerAreaInfo($row['user_id']);
                $row['url'] = $this->dscRepository->buildUri('wholesale_purchase', array('gid' => $row['purchase_id'], 'act' => 'info'), $row['subject']);
                $arr[] = $row;
            }
        }

        return [
            'purchase_list' => $arr,
            'page_count' => $page_count,
            'record_count' => $record_count
        ];
    }

    /**
     * 获取求购单信息
     *
     * @param int $purchase_id
     * @return mixed
     */
    public function getPurchaseInfo($purchase_id = 0)
    {
        $purchase_info = WholesalePurchase::where('purchase_id', $purchase_id);
        $purchase_info = $this->baseRepository->getToArrayFirst($purchase_info);

        if ($purchase_info) {
            $goods_list = WholesalePurchaseGoods::where('purchase_id', $purchase_id);
            $goods_list = $this->baseRepository->getToArrayGet($goods_list);

            if ($goods_list) {
                foreach ($goods_list as $key => $val) {
                    if ($val['goods_img']) {
                        $goods_img = unserialize($val['goods_img']);

                        foreach ($goods_img as $k => $img) {
                            $goods_img[$k] = $this->dscRepository->getImagePath($img);
                        }

                        $goods_list[$key]['goods_img'] = $goods_img;
                    } else {
                        $goods_list[$key]['goods_img'] = [];
                    }

                    $cat_info = WholesaleCat::where('cat_id', $val['cat_id']);
                    $cat_info = $this->baseRepository->getToArrayFirst($cat_info);
                    $goods_list[$key]['cat_name'] = $cat_info['cat_name'] ?? '';
                }
            }

            $time = $this->timeRepository->getGmTime();

            $purchase_info['goods_list'] = $goods_list;
            $purchase_info['left_day'] = ($purchase_info['end_time'] - $time > 0) ? (floor(($purchase_info['end_time'] - $time) / 86400) > 0 ? floor(($purchase_info['end_time'] - $time) / 86400) : '-1') : 0;

            $user_name = Users::where('user_id', $purchase_info['user_id'])->value('user_name');
            $purchase_info['user_name'] = $user_name;

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                $purchase_info['mobile'] = $this->dscRepository->stringToStar($purchase_info['user_name']);
                $purchase_info['contact_phone'] = $this->dscRepository->stringToStar($purchase_info['contact_phone']);
                $purchase_info['contact_email'] = $this->dscRepository->stringToStar($purchase_info['contact_email']);
                $purchase_info['supplier_contact_phone'] = $this->dscRepository->stringToStar($purchase_info['supplier_contact_phone']);
            }

            //商家名称
            $purchase_info['shop_name'] = $this->merchantCommonService->getShopName($purchase_info['user_id'], 1);

            //是否认证
            $purchase_info['is_verified'] = $this->wholesaleService->getCheckUsersReal($purchase_info['user_id'], 1);

            //商家地区
            $purchase_info['area_info'] = $this->wholesaleService->getSellerAreaInfo($purchase_info['user_id']);

            //收货地区
            $purchase_info['consignee_region'] = $this->wholesaleService->getEveryRegionName($purchase_info['consignee_region']);
        }

        return $purchase_info;
    }
}
