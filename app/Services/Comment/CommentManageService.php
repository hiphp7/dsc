<?php

namespace App\Services\Comment;

use App\Models\Article;
use App\Models\Comment;
use App\Models\CommentSeller;
use App\Models\Goods;
use App\Models\MerchantsShopInformation;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Merchant\MerchantCommonService;

/**
 * 后台用户评论
 * Class Comment
 *
 * @package App\Services
 */
class CommentManageService
{
    protected $timeRepository;
    protected $baseRepository;
    protected $dscRepository;
    protected $merchantCommonService;

    public function __construct(
        TimeRepository $timeRepository,
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService
    )
    {
        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
    }

    /**
     * 获取评论列表
     *
     * @param $ru_id
     * @return array
     * @throws \Exception
     */
    public function getCommentList($ru_id)
    {
        /* 查询条件 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? 0 : addslashes(trim($_REQUEST['keywords']));
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'add_time' : addslashes(trim($_REQUEST['sort_by']));
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : addslashes(trim($_REQUEST['sort_order']));
        $filter['seller_list'] = isset($_REQUEST['seller_list']) && !empty($_REQUEST['seller_list']) ? 1 : 0;  //商家和自营订单标识

        $user_id = MerchantsShopInformation::where('shoprz_brandName', 'like', '%' . mysql_like_quote($filter['keywords']) . '%')
            ->orwhere('shopNameSuffix', 'like', '%' . mysql_like_quote($filter['keywords']) . '%')
            ->value('user_id');

        if (empty($user_id)) {
            $user_id = 0;
        }

        $filter['ru_id'] = $user_id;

        $row = Comment::whereRaw(1);

        if (!empty($filter['keywords'])) {
            $row = $row->where(function ($query) use ($filter) {
                $query = $query->where('content', 'like', '%' . mysql_like_quote($filter['keywords']) . '%');

                if ($filter['ru_id'] > 0) {
                    $query->orWhereIn('ru_id', $filter['ru_id']);
                }
            });
        }

        //管理员查询的权限 -- 店铺查询 start
        $filter['store_search'] = empty($_REQUEST['store_search']) ? 0 : intval($_REQUEST['store_search']);
        $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
        $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';

        if ($filter['store_search'] != 0) {
            if ($filter['ru_id'] == 0) {
                $store_type = isset($_REQUEST['store_type']) && !empty($_REQUEST['store_type']) ? intval($_REQUEST['store_type']) : 0;
                $filter['store_type'] = $store_type;

                if ($filter['store_search'] == 1) {
                    $row = $row->where('user_id', $filter['merchant_id']);
                }

                if ($filter['store_search'] > 1) {
                    $row = $row->whereHas('getMerchantsShopInformation', function ($query) use ($filter) {
                        if ($filter['store_search'] == 2) {
                            $query->where('rz_shopName', 'like', '%' . mysql_like_quote($filter['store_keyword']) . '%');
                        } elseif ($filter['store_search'] == 3) {
                            $query = $query->where('shoprz_brandName', 'like', '%' . mysql_like_quote($filter['store_keyword']) . '%');

                            if ($filter['store_type']) {
                                $query->where('shopNameSuffix', $filter['store_type']);
                            }
                        }
                    });
                }
            }
        }
        //管理员查询的权限 -- 店铺查询 end

        if ($ru_id > 0) {
            $row = $row->where('ru_id', $ru_id);
        }

        $row = $row->where(function ($query) {
            $query->where('parent_id', 0)->orWhere(function ($query) {
                $query->where('parent_id', '>', 0)
                    ->where('user_id', '>', 0);
            });
        });

        //区分商家和自营
        if (!empty($filter['seller_list'])) {
            $row = $row->where('ru_id', '>', 0);
        } else {
            $row = $row->where('ru_id', 0);
        }

        $res = $record_count = $row;

        $filter['record_count'] = $record_count->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 获取评论数据 */
        $res = $res->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])
            ->limit($filter['page_size'])
            ->get();

        $arr = [];
        if ($res) {
            foreach ($res as $row) {
                if ($row['comment_type'] == 2) {
                    $goods_name = Goods::where('goods_id', $row['id_value'])->value('goods_name');
                    $row['title'] = $goods_name . "<br/><font style='color:#1b9ad5;'>(" . $GLOBALS['_LANG']['goods_user_reply'] . ")</font>";
                } else if ($row['comment_type'] == 3) {
                    $row['title'] = Goods::where('goods_id', $row['id_value'])->value('goods_name');
                } else {
                    if ($row['comment_type'] == 0) {
                        $row['title'] = Goods::where('goods_id', $row['id_value'])->value('goods_name');
                    } else {
                        $row['title'] = Article::where('article_id', $row['id_value'])->value('title');
                    }
                }
                $row['add_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $row['add_time']);
                $row['ru_name'] = $this->merchantCommonService->getShopName($row['ru_id'], 1);

                if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                    $row['user_name'] = $this->dscRepository->stringToStar($row['user_name']);
                    $row['email'] = $this->dscRepository->stringToStar($row['email']);
                }

                $arr[] = $row;
            }
        }

        $filter['keywords'] = stripslashes($filter['keywords']);

        $arr = [
            'item' => $arr,
            'filter' => $filter,
            'page_count' => $filter['page_count'],
            'record_count' => $filter['record_count']
        ];
        return $arr;
    }

    /**
     * 商家满意度评分
     *
     * @return array
     * @throws \Exception
     */
    public function commentSellerList()
    {
        $adminru = get_admin_ru_id();

        /* 查询条件 */
        $filter['keywords'] = empty($_REQUEST['keywords']) ? 0 : trim($_REQUEST['keywords']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'add_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $row = CommentSeller::whereRaw(1);

        if (!empty($filter['keywords'])) {
            $row = $row->whereHas('getUsers', function ($query) use ($filter) {
                $query->where('user_name', 'like', '%' . mysql_like_quote($filter['keywords']) . '%');
            });
        }

        //管理员查询的权限 -- 店铺查询 start
        $filter['store_search'] = empty($_REQUEST['store_search']) ? 0 : intval($_REQUEST['store_search']);
        $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
        $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';

        if ($filter['store_search'] != 0) {
            if ($adminru['ru_id'] == 0) {
                $store_type = isset($_REQUEST['store_type']) && !empty($_REQUEST['store_type']) ? intval($_REQUEST['store_type']) : 0;
                $filter['store_type'] = $store_type;

                if ($filter['store_search'] == 1) {
                    $row = $row->where('user_id', $filter['merchant_id']);
                }

                if ($filter['store_search'] > 1) {
                    $row = $row->whereHas('getMerchantsShopInformation', function ($query) use ($filter) {
                        if ($filter['store_search'] == 2) {
                            $query->where('rz_shopName', 'like', '%' . mysql_like_quote($filter['store_keyword']) . '%');
                        } elseif ($filter['store_search'] == 3) {
                            $query = $query->where('shoprz_brandName', 'like', '%' . mysql_like_quote($filter['store_keyword']) . '%');

                            if ($filter['store_type']) {
                                $query->where('shopNameSuffix', $filter['store_type']);
                            }
                        }
                    });
                }
            }
        }
        //管理员查询的权限 -- 店铺查询 end

        $res = $record_count = $row;

        $filter['record_count'] = $record_count->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 获取评论数据 */
        $res = $res->with([
            'getUsers' => function ($query) {
                $query->select('user_id', 'user_name');
            },
            'getOrderInfo' => function ($query) {
                $query->select('order_id', 'order_sn');
            }
        ]);
        $res = $res->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])
            ->limit($filter['page_size']);

        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        if ($res) {
            foreach ($res as $row) {
                $row['user_name'] = $row['get_users']['user_name'];
                $row['order_sn'] = $row['get_order_info']['order_sn'] ?? '';
                $row['add_time'] = $this->timeRepository->getLocalDate($GLOBALS['_CFG']['time_format'], $row['add_time']);
                $row['ru_name'] = $this->merchantCommonService->getShopName($row['ru_id'], 1);
                $arr[] = $row;
            }
        }

        $filter['keywords'] = stripslashes($filter['keywords']);
        $arr = [
            'item' => $arr,
            'filter' => $filter,
            'page_count' => $filter['page_count'],
            'record_count' => $filter['record_count']
        ];
        return $arr;
    }
}
