<?php

namespace App\Services\UserRights;

use App\Models\DrpLog;
use App\Models\DrpShop;
use App\Models\UserMembershipCardRights;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;


class UserRightsService
{
    protected $baseRepository;
    protected $timeRepository;
    protected $dscRepository;
    protected $userRightsCommonService;
    protected $rightsCardCommonService;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        UserRightsCommonService $userRightsCommonService,
        RightsCardCommonService $rightsCardCommonService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->userRightsCommonService = $userRightsCommonService;
        $this->rightsCardCommonService = $rightsCardCommonService;
    }

    /**
     * 查询权益信息
     * @param string $code
     * @return array|mixed
     */
    public function userRightsInfo($code = '')
    {
        if (empty($code)) {
            return false;
        }

        $info = $this->userRightsCommonService->userRightsInfo($code);

        return $info;
    }

    /**
     * 会员权益卡 绑定的权益列表
     * @param int $user_id
     * @param int $rights_id
     * @return array
     */
    public function membershipCardInfoByUserId($user_id = 0, $rights_id = 0)
    {
        if (empty($user_id)) {
            return [];
        }

        $membership_card_id = DrpShop::where('user_id', $user_id)->where('audit', 1)->where('membership_status', 1)->value('membership_card_id');

        $info = $this->rightsCardCommonService->membershipCardInfoByRightsId($membership_card_id, $rights_id);

        $rights_list = [];
        if (!empty($info)) {
            $info = $this->rightsCardCommonService->transFormRightsCardInfo($info);

            // 绑定的权益列表
            $rights_list = $this->rightsCardCommonService->transFormCardRightsList($info);
        }

        return $rights_list;
    }

    /**
     * 获取当前会员分销商信息
     * @param int $user_id
     * @return array
     */
    public function getDrpShopUser($user_id = 0)
    {
        if (empty($user_id)) {
            return [];
        }

        $user_model = Users::query();
        $user_model = $user_model->whereHas('getDrpShop', function ($query) use ($user_id) {
            $query->where('user_id', $user_id)->where('audit', 1);
        });
        $user_model = $user_model->with([
            'getDrpShop' => function ($query) {
                $query->select('user_id', 'id as drp_shop_id');
            }
        ]);
        $user_model = $user_model->select('user_id', 'user_name');

        $user = $this->baseRepository->getToArrayFirst($user_model);

        return $user;
    }

    /**
     * 获取当前会员上级分销商信息
     * @param int $user_id
     * @return array
     */
    public function getParentDrpShopUser($user_id = 0)
    {
        if (empty($user_id)) {
            return [];
        }

        $user_model = Users::query()->where('user_id', $user_id);
        $user_model = $user_model->whereHas('getParentDrpShop', function ($query) {
            $query->where('audit', 1);
        });

        $user_model = $user_model->with([
            'getParentDrpShop' => function ($query) {
                $query = $query->select('user_id', 'id as drp_shop_id');
                $query->with([
                    'getUsers' => function ($query) {
                        $query->select('user_id', 'user_name');
                    }
                ]);
            }
        ]);

        $user_model = $user_model->select('user_id', 'drp_parent_id');
        $user = $this->baseRepository->getToArrayFirst($user_model);

        if (isset($user['get_parent_drp_shop']) && !empty($user['get_parent_drp_shop'])) {
            $user['get_parent_drp_shop'] = collect($user['get_parent_drp_shop'])->merge($user['get_parent_drp_shop']['get_users'])->except('get_users')->all();
        }

        return $user;
    }

    /**
     * 分成记录
     * @param int $order_id
     * @param int $drp_account_log_id
     * @param int $user_id
     * @param string $user_name
     * @param int $money 分成佣金
     * @param int $point
     * @param int $drp_level 分销等级
     * @param int $is_separate 是否分成
     * @param int $separate_type
     * @param int $membership_card_id 权益卡id
     * @param int $log_type 日志类型 0 正常订单分成、1 付费购买分成、 2 购买指定商品分成
     * @param int $level_percent 分成比例 去除%后的
     * @return mixed
     */
    public function writeDrpLog($order_id = 0, $drp_account_log_id = 0, $user_id = 0, $user_name = '', $money = 0, $point = 0, $drp_level = 0, $is_separate = 0, $separate_type = 0, $membership_card_id = 0, $log_type = 0, $level_percent = 0)
    {
        $time = $this->timeRepository->getGmTime();
        $drplog = [
            'order_id' => $order_id,
            'drp_account_log_id' => $drp_account_log_id,
            'user_id' => $user_id,
            'user_name' => $user_name,
            'time' => $time,
            'money' => $money,
            'point' => $point,
            'drp_level' => $drp_level,
            'is_separate' => $is_separate,
            'separate_type' => $separate_type,
            'membership_card_id' => $membership_card_id,
            'log_type' => $log_type, // 日志类型 0 正常订单分成、1 付费购买分成、 2 购买指定商品分成
            'level_percent' => $level_percent, // 分成比例
        ];

        $id = DrpLog::insertGetId($drplog);

        return $id;
    }

    /**
     * 权益配置信息
     * @param int $rights_id
     * @param int $limit
     * @return array
     */
    public function getCardRights($rights_id = 0, $limit = 100, $enable = 1)
    {
        if (empty($rights_id)) {
            return [];
        }

        $model = UserMembershipCardRights::query()->where('rights_id', $rights_id)->where('membership_card_id', '>', 0);

        $model = $model->whereHas('userMembershipRights', function ($query) use ($enable) {
            if (!is_null($enable)) {
                $query->where('enable', $enable);
            }
        });

        $model = $model->with([
            'userMembershipRights' => function ($query) use ($enable) {
                if (!is_null($enable)) {
                    $query->where('enable', $enable);
                }
            },
            'userMembershipCard' => function ($query) use ($enable) {
                if (!is_null($enable)) {
                    $query->where('enable', $enable);
                }
            },
        ]);

        $model = $model->limit($limit)->get();

        $list = $model ? $model->toArray() : [];

        if (!empty($list)) {
            foreach ($list as $key => $val) {

                if (empty($val['rights_configure'])) {
                    // 调取默认安装的权益配置
                    $val['rights_configure'] = $val['user_membership_rights']['rights_configure'] ?? [];
                }

                $list[$key]['rights_configure'] = empty($val['rights_configure']) ? [] : unserialize($val['rights_configure']);
            }
        }

        return $list;
    }

    /**
     * 会员权益卡信息
     * @param int $rights_id
     * @param array $receive_type_arr 按领取类型筛选
     * @param int $limit
     * @return array
     */
    public function getCardRightsByReceiveType($rights_id = 0, $receive_type_arr = [], $limit = 100)
    {
        if (empty($rights_id)) {
            return [];
        }

        $now = $this->timeRepository->getGmTime();

        $model = UserMembershipCardRights::query()->where('rights_id', $rights_id)->where('membership_card_id', '>', 0);

        $model = $model->whereHas('userMembershipRights', function ($query) {
            $query->where('enable', 1);
        });

        $model = $model->with([
            'userMembershipRights' => function ($query) {
                $query->where('enable', 1);
            },
            'userMembershipCard' => function ($query) {
                $query->where('enable', 1);
            },
        ]);

        $model = $model->limit($limit)->get();

        $list = $model ? $model->toArray() : [];

        $new_list = [];
        if (!empty($list)) {
            foreach ($list as $key => $val) {

                if (empty($val['rights_configure'])) {
                    // 调取默认安装的权益配置
                    $val['rights_configure'] = $val['user_membership_rights']['rights_configure'] ?? [];
                }

                $val['rights_configure'] = empty($val['rights_configure']) ? [] : unserialize($val['rights_configure']);

                // 权益卡信息
                if (isset($val['user_membership_card'])) {
                    $val['user_membership_card']['receive_value'] = empty($val['user_membership_card']['receive_value']) ? '' : unserialize($val['user_membership_card']['receive_value']);
                }

                // 匹配领取条件的权益卡
                $code_arr = [];
                if (isset($val['user_membership_card']['receive_value']) && !empty($val['user_membership_card']['receive_value'])) {
                    foreach ($val['user_membership_card']['receive_value'] as $k => $item) {
                        if (in_array($item['type'], $receive_type_arr)) {
                            if ($item['type'] == 'buy') {
                                $item['value'] = floatval($item['value']);
                            }
                            $code_arr[$item['type']] = $item;

                            $val['user_membership_card']['receive_value'] = $code_arr;

                            $new_list[$key] = $val;

                            // 权益卡领取有效期 过期不显示
                            if ($val['user_membership_card']['expiry_type'] == 'timespan') {
                                $expiry_date = $val['user_membership_card']['expiry_date'] ?? '';
                                if (!empty($expiry_date)) {

                                    list($expiry_date_start, $expiry_date_end) = is_string($expiry_date) ? explode(',', $expiry_date) : $expiry_date;
                                }

                                // 当前会员权益卡领取有效期结束时间 小于当前时间 无法续费、可重新购买其他权益卡
                                if (empty($expiry_date) || (isset($expiry_date_end) && $now > $expiry_date_end)) {
                                    unset($new_list[$k]);
                                }

                            } elseif ($val['user_membership_card']['expiry_type'] == 'days') {
                                $expiry_date = $val['user_membership_card']['expiry_date'] ?? '';

                                if (empty($expiry_date)) {
                                    unset($new_list[$k]);
                                }
                            }
                        }
                    }
                }
            }

            $new_list = collect($new_list)->values()->toArray();
        }

        return $new_list;
    }
}
