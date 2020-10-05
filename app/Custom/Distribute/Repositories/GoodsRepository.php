<?php

namespace App\Custom\Distribute\Repositories;

use App\Models\Goods;
use App\Models\GoodsExtend;
use App\Models\MerchantsCategory;
use App\Models\TradeSnapshot;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Merchant\MerchantCommonService;

/**
 * Class GoodsRepository
 * @package App\Custom\Distribute\Repositories
 */
class GoodsRepository
{
    protected $timeRepository;
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        TimeRepository $timeRepository,
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
    }

    /**
     * 商品列表
     * @param int $seller_list 平台或店铺
     * @param string $keywords 搜索关键词
     * @param array $offset 分页
     * @param array $filter 过滤条件
     * @return array
     */
    public function goods_list($seller_list = 0, $keywords = '', $offset = [], $filter = [])
    {
        $model = Goods::where('is_real', 0)
            ->where('review_status', '>', 0);

        if ($seller_list > 0) {
            $model = $model->where('user_id', '>', 0);
        } else {
            $model = $model->where('user_id', 0);
        }

        $now = $this->timeRepository->getGmTime();

        if (!empty($filter)) {
            // 是否回收站
            $model = $model->where('is_delete', $filter['is_delete'] ?? 0);
            // 上下架
            $model = $model->where('is_on_sale', $filter['is_on_sale'] ?? 1);
            $model = $model->where('is_alone_sale', $filter['is_alone_sale'] ?? 1);

            if (isset($filter['review_status']) && $filter['review_status'] > 0) {
                if ($filter['review_status'] == 3) {
                    $model = $model->where('review_status', '>=', $filter['review_status']);
                } else {
                    $model = $model->where('review_status', $filter['review_status']);
                }
            }

            if (isset($filter['intro_type'])) {
                /* 推荐类型 */
                switch ($filter['intro_type']) {
                    case 'is_best':
                        $model = $model->where('is_best', 1);
                        break;
                    case 'is_hot':
                        $model = $model->where('is_hot', 1);
                        break;
                    case 'is_new':
                        $model = $model->where('is_new', 1);
                        break;
                    case 'is_promote':
                        $model = $model->where('is_promote', 1)->where('promote_price', '>', 0);
                        break;
                    case 'all_type':
                        $model = $model->where(function ($query) use ($now) {
                            $query->where('is_best', 1)->orWhere('is_hot', 1)->orWhere('is_new', 1);
                            $query->orWhere(function ($query) use ($now) {
                                $query->where('is_promote', 1)->where('promote_price', '>', 0)->where('promote_start_date', '<=', $now)->where('promote_end_date', '>=', $now);
                            });
                        });
                }
            }

            /* 库存警告 */
            if (isset($filter['stock_warning']) && $filter['stock_warning']) {
                $model = $model->whereColumn('goods_number', '<=', 'warn_number');
            }
        }


        $model = $model->with([
            // 关联分类
            'getCategory' => function ($query) {
                $query->select('cat_id', 'cat_name');
            },
            // 关联品牌
            'getBrand' => function ($query) {
                $query->select('brand_id', 'brand_name');
            },
            // 关联商品扩展
            'getGoodsExtend' => function ($query) {
                $query->select('goods_id', 'is_reality', 'is_return', 'is_fast');
            }
        ]);

        // 搜索
        if (!empty($keywords)) {
            $model = $model->where('goods_name', 'like', '%' . $keywords . '%')
                ->orWhere('goods_sn', 'like', '%' . $keywords . '%')
                ->orWhere('bar_code', 'like', '%' . $keywords . '%');
        }

        $total = $model->count();

        if (!empty($offset)) {
            $model = $model->offset($offset['start'])
                ->limit($offset['limit']);
        }

        $model = $model->groupBy('goods_id')
            ->orderBy('goods_id', 'DESC')
            ->get();

        $list = $model ? $model->toArray() : [];

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 商品信息
     * @param int $goods_id
     * @return array
     */
    public function goods_info($goods_id = 0)
    {
        $model = Goods::where('goods_id', $goods_id);

        $info = $this->baseRepository->getToArrayFirst($model);

        if ($info) {
            if (isset($info['user_cat']) && !empty($info['user_cat'])) {
                $cat_info = MerchantsCategory::catInfo($info['user_cat']);
                $cat_info = $this->baseRepository->getToArrayFirst($cat_info);

                $cat_info['is_show_merchants'] = $cat_info['is_show'];
                $info['user_cat_name'] = $cat_info['cat_name'];
            }

            $info['goods_video_path'] = $this->dscRepository->getImagePath($info['goods_video']);
            $info['shop_name'] = app(MerchantCommonService::class)->getShopName($info['user_id'], 1); //店铺名称

            $info['goods_thumb'] = empty($info['goods_thumb']) ? '' : $this->dscRepository->getImagePath($info['goods_thumb']);
        }

        return $info;
    }

    /**
     * 商品扩展
     * @param int $goods_id
     * @return array
     */
    public function goods_extend($goods_id = 0)
    {
        $model = GoodsExtend::where('goods_id', $goods_id)->first();

        return $model ? $model->toArray() : [];
    }

    /**
     * 查找是否存在快照
     * @param string $order_sn
     * @param int $goods_id
     * @return mixed
     */
    public function find_snapshot($order_sn = '', $goods_id = 0)
    {
        $trade_id = TradeSnapshot::where('order_sn', $order_sn)
            ->where('goods_id', $goods_id)
            ->value('trade_id');

        return $trade_id;
    }
}