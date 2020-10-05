<?php

namespace App\Services\Goods;

use App\Models\GoodsActivity;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Activity\GroupBuyService;
use App\Services\Merchant\MerchantCommonService;

class GroupBuyManageService
{
    protected $baseRepository;
    protected $groupBuyService;
    protected $merchantCommonService;
    protected $dscRepository;
    protected $timeRepository;

    public function __construct(
        BaseRepository $baseRepository,
        GroupBuyService $groupBuyService,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository,
        TimeRepository $timeRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->groupBuyService = $groupBuyService;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
        $this->timeRepository = $timeRepository;
    }


    /*
     * 取得团购活动列表
     * @return   array
     */
    public function groupBuyList($ru_id)
    {
        /* 过滤条件 */
        $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'act_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['review_status'] = empty($_REQUEST['review_status']) ? 0 : intval($_REQUEST['review_status']);
        $filter['seller_list'] = isset($_REQUEST['seller_list']) && !empty($_REQUEST['seller_list']) ? 1 : 0;  //商家和自营订单标识

        //卖场 start
        $filter['rs_id'] = empty($_REQUEST['rs_id']) ? 0 : intval($_REQUEST['rs_id']);
        $adminru = get_admin_ru_id();
        if ($adminru['rs_id'] > 0) {
            $filter['rs_id'] = $adminru['rs_id'];
        }
        //卖场 end
        $res = GoodsActivity::whereRaw(1);

        if (!empty($filter['keyword'])) {
            $keyword = mysql_like_quote($filter['keyword']);
            $res = $res->where(function ($query) use ($keyword) {
                $query->where('goods_name', 'LIKE', '%' . $keyword . '%');
            });
        }

        //ecmoban模板堂 --zhuo start
        if ($ru_id > 0) {
            $res = $res->where('user_id', $ru_id);
        }
        //ecmoban模板堂 --zhuo end

        if ($filter['review_status']) {
            $res = $res->where('review_status', $filter['review_status']);
        }

        //卖场
        $res = $this->dscRepository->getWhereRsid($res, 'user_id', $filter['rs_id']);

        //管理员查询的权限 -- 店铺查询 start
        $filter['store_search'] = !isset($_REQUEST['store_search']) ? -1 : intval($_REQUEST['store_search']);
        $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
        $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';

        if ($filter['store_search'] > -1) {
            if ($ru_id == 0) {
                if ($filter['store_search'] > 0) {
                    $store_type = isset($_REQUEST['store_type']) && !empty($_REQUEST['store_type']) ? intval($_REQUEST['store_type']) : 0;

                    if ($filter['store_search'] == 1) {
                        $res = $res->where('user_id', $filter['merchant_id']);
                    }

                    if ($filter['store_search'] > 1) {
                        $res = $res->where(function ($query) use ($filter, $store_type) {
                            $query->whereHas('getMerchantsShopInformation', function ($query) use ($filter, $store_type) {
                                if ($filter['store_search'] == 2) {
                                    $query = $query->where('rz_shopName', 'LIKE', '%' . mysql_like_quote($filter['store_keyword']) . '%');
                                } elseif ($filter['store_search'] == 3) {
                                    $query = $query->where('shoprz_brandName', 'LIKE', '%' . mysql_like_quote($filter['store_keyword']) . '%');
                                    if ($store_type) {
                                        $query = $query->where('shopNameSuffix', $store_type);
                                    }
                                }
                            });
                        });
                    }
                } else {
                    $res = $res->where('user_id', 0);
                }
            }
        }
        //管理员查询的权限 -- 店铺查询 end
        //区分商家和自营
        if (!empty($filter['seller_list'])) {
            $res = $res->where('user_id', '>', 0);
        } else {
            $res = $res->where('user_id', 0);
        }

        $res = $res->where('act_type', GAT_GROUP_BUY);
        $filter['record_count'] = $res->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 查询 */
        $res = $res->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])
            ->limit($filter['page_size']);

        $res = $this->baseRepository->getToArrayGet($res);
        $list = [];
        foreach ($res as $row) {
            $ext_info = unserialize($row['ext_info']);
            $stat = $this->groupBuyService->getGroupBuyStat($row['act_id'], $ext_info['deposit']);
            $arr = array_merge($row, $stat, $ext_info);

            /* 处理价格阶梯 */
            $price_ladder = $arr['price_ladder'];
            if (!is_array($price_ladder) || empty($price_ladder)) {
                $price_ladder = [['amount' => 0, 'price' => 0]];
            } else {
                foreach ($price_ladder as $key => $amount_price) {
                    $price_ladder[$key]['formated_price'] = $this->dscRepository->getPriceFormat($amount_price['price']);
                }
            }

            /* 计算当前价 */
            $cur_price = $price_ladder[0]['price'];    // 初始化
            $cur_amount = $stat['valid_goods'];         // 当前数量
            foreach ($price_ladder as $amount_price) {
                if ($cur_amount >= $amount_price['amount']) {
                    $cur_price = $amount_price['price'];
                } else {
                    break;
                }
            }

            $arr['cur_price'] = $cur_price;

            $status = $this->groupBuyService->getGroupBuyStatus($arr);

            $arr['start_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['date_format'], $arr['start_time']);
            $arr['end_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['date_format'], $arr['end_time']);
            $arr['cur_status'] = $GLOBALS['_LANG']['gbs'][$status];

            $arr['user_name'] = $this->merchantCommonService->getShopName($arr['user_id'], 1);

            $list[] = $arr;
        }
        $arr = ['item' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /**
     * 取得某商品的团购活动
     * @param int $goods_id 商品id
     * @return  array
     */
    public function goodsGroupBuy($goods_id)
    {
        $time = $this->timeRepository->getGmTime();

        $res = GoodsActivity::where('goods_id', $goods_id)
            ->where('act_type', GAT_GROUP_BUY)
            ->where('start_time', '<=', $time)
            ->where('end_time', '>=', $time);
        $res = $this->baseRepository->getToArrayFirst($res);
        return $res;
    }

    /**
     * 列表链接
     * @param bool $is_add 是否添加（插入）
     * @return  array('href' => $href, 'text' => $text)
     */
    public function listLink($is_add = true)
    {
        $href = 'group_buy.php?act=list';
        if (!$is_add) {
            $href .= '&' . list_link_postfix();
        }

        return ['href' => $href, 'text' => $GLOBALS['_LANG']['group_buy_list']];
    }
}
