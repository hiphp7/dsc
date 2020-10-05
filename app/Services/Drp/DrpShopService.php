<?php

namespace App\Services\Drp;

use App\Models\DrpShop;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\User\UserRankService;


class DrpShopService
{
    protected $baseRepository;
    protected $timeRepository;
    protected $dscRepository;
    protected $config;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 分销商列表
     * @param int $user_id
     * @param array $offset
     * @param array $filter
     * @return array
     */
    public function getList($user_id = 0, $offset = [], $filter = [])
    {
        $model = DrpShop::query();

        if (!empty($filter)) {
            // 按开店时间筛选
            if (!empty($filter['starttime']) && !empty($filter['endtime'])) {
                $model = $model->whereBetween('create_time', [$filter['starttime'], $filter['endtime']]);
            }
            //类型
            $time = $this->timeRepository->getGmTime();
            if (!empty($filter['status'])) {
                if ($filter['status'] == 'wait') {
                    //待审核
                    $model = $model->where('audit', 0)->where('membership_status', 1);
                } elseif ($filter['status'] == 'active') {
                    //使用中
                    $model = $model->where('audit', 1)->where('open_time', '>', 0)->where('membership_status', 1)->where(function ($query) use ($time) {
                        $query->where('expiry_time', '>=', $time)
                            ->orWhere('expiry_time', 0);
                    });
                } elseif ($filter['status'] == 'expired') {
                    //过期
                    $model = $model->where('expiry_time', '<', $time)->where('expiry_time', '<>', 0)->orWhere('membership_status', 0);
                }
            }
            if (!empty($filter['membership_card_id'])) {
                $model = $model->where('membership_card_id', $filter['membership_card_id']);
            }
        }

        $keyword = $filter['keyword'] ?? '';
        if (!empty($keyword)) {
            $model = $model->where(function ($query) use ($keyword) {
                $query = $query->where('shop_name', 'like', '%' . $keyword . '%')
                    ->orWhere('real_name', 'like', '%' . $keyword . '%')
                    ->orWhere('mobile', 'like', '%' . $keyword . '%');

                $query->orWhereHas('getUsers', function ($query) use ($keyword) {
                    $query->Where('user_name', 'like', '%' . $keyword . '%');
                });
            });
        }

        $model = $model->whereHas('getUsers', function ($query) use ($user_id) {
            if ($user_id > 0) {
                $query->where('drp_parent_id', $user_id);
            }
        });

        $model = $model->with([
            'getUsers' => function ($query) {
                $query = $query->select('user_id', 'user_name', 'user_picture', 'drp_parent_id', 'nick_name');
                $query->with([
                    'getDrpParent' => function ($query) {
                        $query->select('user_id', 'user_name as parent_name');
                    }
                ]);
            },
            'userMembershipCard' => function ($query) {
                $query->select('id', 'name');
            },
        ]);

        $total = $model->count();

        if (!empty($offset)) {
            $model = $model->offset($offset['start'])->limit($offset['limit']);
        }

        $list = $model->orderBy('create_time', 'DESC')
            ->orderBy('id', 'DESC')
            ->get();
        $list = $list ? $list->toArray() : [];

        if (!empty($list)) {
            foreach ($list as $key => $val) {
                $list[$key] = collect($val)->merge($val['get_users'])->except('get_users')->all();

                // 父级会员名称
                $list[$key]['parent_name'] = '';
                if ($list[$key]['drp_parent_id'] > 0 && !empty($list[$key]['get_drp_parent'])) {
                    $list[$key]['parent_name'] = $list[$key]['get_drp_parent']['parent_name'] ?? '';

                    if (isset($this->config['show_mobile']) && $this->config['show_mobile'] == 0 && !empty($list[$key]['parent_name'])) {
                        $list[$key]['parent_name'] = $this->dscRepository->stringToStar($list[$key]['parent_name']);
                    }
                }

                // 分销商等级名称
                $list[$key]['credit_name'] = '';
                if ($list[$key]['membership_card_id'] > 0 && !empty($list[$key]['user_membership_card'])) {
                    $list[$key]['credit_name'] = $list[$key]['user_membership_card']['name'] ?? '';
                }

                // 审核状态
                $list[$key]['audit_format'] = $val['audit'] == 1 ? lang('admin/drp.already_audit') : ($val['audit'] == 2 ? lang('admin/drp.refuse_audit') : lang('admin/drp.no_audit'));
                $list[$key]['status_format'] = $val['status'] == 1 ? lang('admin/drp.already_open') : lang('admin/drp.already_close');

                // 申请时间
                $list[$key]['apply_time_format'] = $this->timeRepository->getLocalDate(config('shop.time_format'), $val['apply_time']);
                // 店铺开店时间
                $list[$key]['create_time_format'] = $this->timeRepository->getLocalDate(config('shop.time_format'), $val['create_time']);
                // 权益开始时间
                $list[$key]['open_time_format'] = $this->timeRepository->getLocalDate(config('shop.time_format'), $val['open_time']);

                $list[$key]['user_name'] = !empty($list[$key]['nick_name']) ? $list[$key]['nick_name'] : $list[$key]['user_name'];

                if (isset($this->config['show_mobile']) && $this->config['show_mobile'] == 0 && isset($val['get_users']) && !empty($val['get_users'])) {
                    $list[$key]['user_name'] = $this->dscRepository->stringToStar($list[$key]['user_name']);
                    $list[$key]['mobile'] = $this->dscRepository->stringToStar($list[$key]['mobile']);
                    $list[$key]['shop_name'] = $this->dscRepository->stringToStar($list[$key]['shop_name']);
                }

                // 分销商头像
                if (isset($val['shop_portrait']) && $val['shop_portrait']) {
                    $list[$key]['shop_portrait'] = $this->dscRepository->getImagePath($val['shop_portrait']);
                } else {
                    $list[$key]['shop_portrait'] = $this->dscRepository->getImagePath($list[$key]['user_picture']);
                }
            }
        }

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 分销商名称
     * @param int $user_id
     * @return mixed
     */
    public function drpShopName($user_id = 0)
    {
        return $shop_name = DrpShop::where('user_id', $user_id)->value('shop_name');
    }

    /**
     * 更新分销商信息
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateDrpShop($id = 0, $data = [])
    {
        if (empty($data)) {
            return false;
        }

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = ($val === null) ? '' : $val;
        });

        return DrpShop::where('id', $id)->update($data);
    }

    /**
     * 获取数量
     * @param int $user_id
     * @param array $filter
     * @return mixed
     */
    public function getCount($user_id = 0, $filter = [])
    {
        $model = DrpShop::query();

        if (!empty($filter)) {
            // 按开店时间筛选
            if (!empty($filter['starttime']) && !empty($filter['endtime'])) {
                $model = $model->whereBetween('create_time', [$filter['starttime'], $filter['endtime']]);
            }

            if (!empty($filter['membership_card_id'])) {
                $model = $model->where('membership_card_id', $filter['membership_card_id']);
            }
        }

        $keyword = $filter['keyword'] ?? '';
        if (!empty($keyword)) {
            $model = $model->where(function ($query) use ($keyword) {
                $query = $query->where('shop_name', 'like', '%' . $keyword . '%')
                    ->orWhere('real_name', 'like', '%' . $keyword . '%')
                    ->orWhere('mobile', 'like', '%' . $keyword . '%');

                $query->orWhereHas('getUsers', function ($query) use ($keyword) {
                    $query->Where('user_name', 'like', '%' . $keyword . '%');
                });
            });
        }

        $model = $model->whereHas('getUsers', function ($query) use ($user_id) {
            if ($user_id > 0) {
                $query->where('drp_parent_id', $user_id);
            }
        });

        $time = $this->timeRepository->getGmTime();

        $total = $model->selectRaw('SUM(audit = 0 and membership_status = 1) AS wait,
         SUM(audit = 1 and open_time > 0 and (expiry_time >= ' . $time . ' or expiry_time = 0) and membership_status = 1) AS active,
         SUM(expiry_time < ' . $time . ' and expiry_time <> 0 or membership_status = 0) AS expired')
            ->first();

        $total = $total ? $total->toArray() : [];

        return $total;
    }

    /**
     * 获取分销商信息
     * @param int $id
     * @return array
     */
    public function getDrpShop($id = 0)
    {
        if (empty($id)) {
            return [];
        }

        $model = DrpShop::query()->where('id', $id);

        $model = $model->with([
            'getUsers' => function ($query) {
                $query->select('user_id', 'user_name', 'nick_name', 'user_picture', 'mobile_phone', 'user_rank');
            }
        ]);

        $model = $model->first();

        $res = $model ? $model->toArray() : [];

        if (!empty($res)) {
            $res = collect($res)->merge($res['get_users'])->except('get_users')->all();

            $res['user_picture'] = $this->dscRepository->getImagePath($res['user_picture']);
            $user_rank_info = app(UserRankService::class)->getUserRankInfo($res['user_id']);
            $res['rank_name'] = $user_rank_info['rank_name'] ?? '';
        }

        return $res;
    }

}
