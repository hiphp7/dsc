<?php

namespace App\Services\UserRights;

use App\Custom\Distribute\Services\DistributeService;
use App\Models\Article;
use App\Models\DrpShop;
use App\Models\UserMembershipCard;
use App\Models\UserMembershipCardRights;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;

/**
 * 会员权益卡
 * Class RightsCardService
 * @package App\Services\UserRights
 */
class RightsCardService
{
    protected $baseRepository;
    protected $timeRepository;
    protected $dscRepository;
    protected $rightsCardCommonService;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        RightsCardCommonService $rightsCardCommonService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->rightsCardCommonService = $rightsCardCommonService;
    }

    /**
     * 分销会员权益卡展示入口
     * @param int $type 1 普通、2 分销权益卡
     * @param int $membership_card_id_repeat
     * @param int $limit
     * @return array
     */
    public function cardReceiveValue($type = 1, $membership_card_id_repeat = 0, $limit = 100)
    {
        $model = UserMembershipCard::query()->where('type', $type)->where('enable', 1);

        if ($membership_card_id_repeat > 0) {
            $model = $model->where('id', '<>', $membership_card_id_repeat);
        }

        $model = $model->limit($limit)->orderBy('sort', 'ASC')
            ->orderBy('add_time', 'DESC')
            ->get();

        $list = $model ? $model->toArray() : [];

        $new_list = [];
        if (!empty($list)) {
            foreach ($list as $val) {

                $val['receive_value'] = empty($val['receive_value']) ? '' : unserialize($val['receive_value']);

                if (!empty($val['receive_value'])) {
                    foreach ($val['receive_value'] as $k => $item) {
                        if ($item['type'] == 'order' || $item['type'] == 'buy') {
                            $val['receive_value'][$k]['value'] = floatval($item['value']);
                        } elseif ($item['type'] == 'integral') {
                            $val['receive_value'][$k]['value'] = intval($item['value']);
                        }
                    }

                    $new_list[] = $val['receive_value'];
                }
            }
        }

        $receive_value = $this->transformReceiveValue($new_list);

        return $receive_value;
    }

    /**
     * 领取条件满足任意一个即显示,同类型的取最小值 (付费购买、指定商品、消费金额累积、积分兑换)
     * @param array $receive_value
     * @return array
     */
    public function transformReceiveValue($receive_value = [])
    {
        if (empty($receive_value)) {
            return [];
        }

        $collection = collect($receive_value);
        // 多维合并成一唯
        $collapsed = $collection->collapse();

        // 按领取类型值分组
        $group = $collapsed->mapToGroups(function ($item, $key) {
            return [$item['type'] => $item['value']];
        });
        $group = $group->toArray();

        $list = [];
        if (!empty($group)) {
            // 取领取类型分组里的 最小值
            foreach ($group as $type => $value) {
                $min_value = collect($value)->min();
                $list[] = [
                    'type' => $type,
                    'value' => $min_value
                ];
            }
        }

        return $list;
    }

    /**
     * 会员权益卡信息
     * @param int $id
     * @return array
     */
    public function cardDetail($id = 0)
    {
        if (empty($id)) {
            return [];
        }

        $model = UserMembershipCard::query()->where('id', $id)->where('enable', 1);

        $model = $model->first();

        $val = $model ? $model->toArray() : [];

        $info = [];
        if (!empty($val)) {
            $info = $this->rightsCardCommonService->transFormRightsCardInfo($val);
        }

        return $info;
    }

    /**
     * 会员权益卡信息
     * @param int $id 会员权益卡id
     * @param string $receive_type
     * @return array
     */
    public function cardInfoById($id = 0, $receive_type = '')
    {
        if (empty($id) || empty($receive_type)) {
            return [];
        }

        $model = UserMembershipCard::query()->where('id', $id)->where('enable', 1);

        $model = $model->first();

        $val = $model ? $model->toArray() : [];

        $info = [];
        if (!empty($val)) {

            $info = $this->rightsCardCommonService->transFormRightsCardInfo($val);

            // 匹配领取条件的权益卡
            $code_arr = [];
            if (!empty($info['receive_value'])) {
                foreach ($info['receive_value'] as $item) {
                    $code_arr[$item['type']] = $item;
                }
            }
            if (isset($code_arr[$receive_type])) {
                $info = $val;
                $info['receive_value_arr'] = $code_arr;
            }

        }

        return $info;
    }

    /**
     * 会员权益卡列表
     * @param int $type 1 普通、2 分销权益卡
     * @param string $receive_type 权益卡 领取类型
     * @param int $user_id
     * @param int $membership_card_id_renew 续费权益卡id
     * @param int $membership_card_id_repeat 重新领取权益卡id
     * @param int $limit
     * @return array
     */
    public function cardList($type = 1, $receive_type = '', $user_id = 0, $membership_card_id_renew = 0, $membership_card_id_repeat = 0, $limit = 10)
    {
        if (empty($receive_type)) {
            return [];
        }

        $now = $this->timeRepository->getGmTime();

        $model = UserMembershipCard::query()->where('type', $type)->where('enable', 1);

        // 续费
        if ($membership_card_id_renew > 0 && empty($membership_card_id_repeat)) {
            $model = $model->where('id', $membership_card_id_renew);
        }
        // 重新领取
        if ($membership_card_id_repeat > 0 && empty($membership_card_id_renew)) {
            $model = $model->where('id', '<>', $membership_card_id_repeat);
        }

        $model = $model->with([
            'userMembershipCardRightsList' => function ($query) {
                $query->with([
                    'userMembershipRights' => function ($query) {
                        $query->select('id', 'name', 'code', 'icon', 'trigger_point', 'enable', 'rights_configure')->where('enable', 1);
                    }
                ]);
            }
        ]);

        $model = $model->limit($limit)->orderBy('sort', 'ASC')
            ->orderBy('add_time', 'DESC')
            ->get();

        $list = $model ? $model->toArray() : [];

        // 会员权益卡列表
        $new_list = [];
        if (!empty($list)) {
            foreach ($list as $k => $val) {

                $val = $this->rightsCardCommonService->transFormRightsCardInfo($val);

                // 匹配领取条件的权益卡
                $code_arr = [];
                if (!empty($val['receive_value'])) {
                    foreach ($val['receive_value'] as $item) {
                        $code_arr[$item['type']] = $item;
                    }
                }

                if (isset($code_arr[$receive_type])) {
                    $new_list[$k] = $val;
                    $new_list[$k]['receive_value_arr'] = $code_arr[$receive_type];

                    // 用于前端显示排序
                    $new_list[$k]['receive_value_sort'] = $code_arr[$receive_type]['value'];

                    // 权益卡领取有效期 过期不显示
                    if ($val['expiry_type'] == 'timespan') {
                        $expiry_date = $val['expiry_date'] ?? '';
                        if (!empty($expiry_date)) {

                            list($expiry_date_start, $expiry_date_end) = is_string($expiry_date) ? explode(',', $expiry_date) : $expiry_date;
                        }

                        // 当前会员权益卡领取有效期结束时间 小于当前时间 无法续费、可重新购买其他权益卡
                        if (empty($expiry_date) || (isset($expiry_date_end) && $now > $expiry_date_end)) {
                            unset($new_list[$k]);
                        }
                    } elseif ($val['expiry_type'] == 'days') {
                        $expiry_date = $val['expiry_date'] ?? '';

                        if (empty($expiry_date)) {
                            unset($new_list[$k]);
                        }
                    }
                }

                // 权益领取指定购买商品列表
                if (isset($code_arr[$receive_type]) && $receive_type == 'goods') {
                    $is_buy_goods = $code_arr[$receive_type]['value'] ?? '';
                    if (!empty($is_buy_goods)) {
                        // 显示购买商品条件
                        $new_list[$k]['goods_list'] = app(DistributeService::class)->selectGoodsList($user_id, $is_buy_goods);
                    }
                }
            }
        }

        // 会员权益卡下的权益列表
        if (!empty($new_list)) {
            foreach ($new_list as $i => $item) {
                $new_list[$i]['user_membership_card_rights_list'] = $this->rightsCardCommonService->transFormCardRightsList($item);
            }
        }

        $new_list = empty($new_list) ? [] : collect($new_list)->sortBy('receive_value_sort')->values()->all();

        return $new_list;
    }

    /**
     * 会员卡权益信息 by id
     * @param int $id
     * @return array
     */
    public function cardInfo($id = 0)
    {
        if (empty($id)) {
            return [];
        }

        $model = UserMembershipCard::query()->where('id', $id);

        $model = $model->with([
            'userMembershipCardRightsList' => function ($query) {
                $query->with([
                    'userMembershipRights' => function ($query) {
                        $query->select('id', 'name', 'code', 'icon', 'trigger_point', 'enable', 'rights_configure')->where('enable', 1);
                    }
                ]);
            }
        ]);

        $model = $model->first();

        $val = $model ? $model->toArray() : [];
        $info = [];
        if (!empty($val)) {
            $info = $this->rightsCardCommonService->transFormRightsCardInfo($val);

            $info['user_membership_card_rights_list'] = $this->rightsCardCommonService->transFormCardRightsList($val);
        }

        return $info;
    }

    /**
     * 会员卡权益信息
     * @param int $rights_id
     * @return array
     */
    public function cardRightsInfo($rights_id = 0)
    {
        if (empty($rights_id)) {
            return [];
        }

        $model = UserMembershipCardRights::query()->where('rights_id', $rights_id);

        $model = $model->whereHas('userMembershipRights', function ($query) {
            $query->where('enable', 1);
        });

        $model = $model->with([
            'userMembershipRights' => function ($query) {
                $query->where('enable', 1);
            }
        ]);

        $model = $model->first();

        $info = $model ? $model->toArray() : [];

        return $info;
    }

    /**
     * 会员权益卡下的权益列表
     * @param int $membership_card_id
     * @param int $user_id
     * @return array
     */
    public function cardRightsList($membership_card_id = 0, $user_id = 0)
    {
        if (empty($membership_card_id)) {
            return [];
        }

        $model = UserMembershipCardRights::query()->where('membership_card_id', $membership_card_id);

        $model = $model->with([
            'userMembershipRightsList' => function ($query) {
                $query->where('enable', 1);
            }
        ]);

        $model = $model->get();

        $list = $model ? $model->toArray() : [];

        $count = 0;
        if ($user_id > 0) {
            // 是否分销商
            $count = DrpShop::where('user_id', $user_id)->where('audit', 1)->count();
        }

        $new_list = [];
        if (!empty($list)) {
            foreach ($list as $k => $val) {
                $val = $this->rightsCardCommonService->transFormRightsList($val);

                if (empty($count) && $val[0]['code'] == 'customer') {
                    // 不是分销商 不显示贵宾专线 的电话号码
                    $val[0]['rights_configure']['0']['value'] = '';
                }

                $user_membership_rights_list[] = $val[0] ?? [];
            }

            $new_list['user_membership_card_rights_list'] = $user_membership_rights_list ?? [];
        }

        return $new_list;
    }

    /**
     * 修改会员等级
     * @param int $user_id
     * @param int $membership_card_id
     * @return bool
     */
    public function editUsersRank($user_id = 0, $membership_card_id = 0)
    {
        if (empty($user_id) || empty($membership_card_id)) {
            return false;
        }

        $rank_id = UserMembershipCard::where('id', $membership_card_id)->value('user_rank_id');
        if ($rank_id) {
            return Users::where('user_id', $user_id)->update(['user_rank' => $rank_id]);
        }
        return false;
    }

    /**
     * 还原会员等级
     * @param int $user_id
     * @return bool
     */
    public function restoreUsersRank($user_id = 0)
    {
        if (empty($user_id)) {
            return false;
        }

        return Users::where('user_id', $user_id)->update(['user_rank' => 0]);
    }

    /**
     * 检测过期时间
     * @param array $membership_card_info
     * @param array $shop_info
     * @return array|string
     */
    public function checkExpiryTime($membership_card_info = [], $shop_info = [])
    {
        if (empty($membership_card_info) || empty($shop_info)) {
            return [];
        }

        $expiry_type = $shop_info['expiry_type'] ?? '';
        $expiry_time = $shop_info['expiry_time'] ?? 0;
        $open_time = $shop_info['open_time'];

        $expiry_data = [
            'expiry_date' => $membership_card_info['expiry_date'] ?? '',
            'expiry_type' => !empty($expiry_type) ? $expiry_type : $membership_card_info['expiry_type'],
        ];

        $val = $expiry_data;

        /**
         * 权益卡有效期：
         *
         *  1. 永久有效 记录 expiry_time = 0
         *
         *  2. 领取时间几天内有效  记录 expiry_time = 几天后时间戳
         *
         *  3. 时间段、开始时间与结束时间  记录 expiry_time = 结束时间戳
         *
         */

        $expiry = [
            'expiry_type' => $val['expiry_type'],
            'expiry_status' => 0, // 当前会员权益卡过期状态 0 未过期、1 已过期、2 快过期
            'expiry_time_notice' => '', // 提示语 默认空
            'card_expiry_status' => $membership_card_info['enable'] ?? 0, // 权益卡状态 0 已停发、1 正常、2 已过期
            'card_status_notice' => '', // 权益卡状态 提示语 默认空
        ];

        if (empty($open_time)) {
            $expiry['expiry_status'] = 1;
            return $expiry;
        }

        if (isset($val['expiry_type']) && !empty($val['expiry_type'])) {
            $now = $this->timeRepository->getGmTime();
            $expiry_status = 0;
            $expiry_time_notice = '';

            $one_month = 30 * 24 * 60 * 60; // 提前一月（30天）提醒

            // 开始与结束时间
            if ($val['expiry_type'] == 'timespan') {
                $expiry_date = $val['expiry_date'] ?? '';
                if (!empty($expiry_date)) {

                    list($expiry_date_start, $expiry_date_end) = is_string($expiry_date) ? explode(',', $expiry_date) : $expiry_date;

                    $val['expiry_date_start'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $expiry_date_start);
                    $val['expiry_date_end'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $expiry_date_end);
                    $val['expiry_date_start_timestamp'] = $expiry_date_start;
                    $val['expiry_date_end_timestamp'] = $expiry_date_end;
                }

                // 领取结束时间过期 10.31 - 11.5 expiry_time = 11.5 结束时间戳
                if (!empty($expiry_time)) {
                    $remaining_time = $expiry_time - $now; // 有效期剩余时间

                    if ($remaining_time > 0 && $remaining_time <= $one_month) {
                        // 即将过期
                        $expiry_time_format = $this->timeRepository->getLocalDate('Y-m-d H:i', $expiry_time);

                        // 会员权益有效期
                        $expiry['expiry_time_format'] = $expiry_time_format;

                        $expiry_time_notice = lang('drp.expiry_time_notice_1', ['expiry_time_format' => $expiry_time_format]);
                        $expiry_status = 2;
                    }

                    // 当前时间 大于 会员权益领取结束时间
                    if ($now > $expiry_time) {
                        // 已过期
                        $expiry_time_notice = lang('drp.expiry_time_notice_0');
                        $expiry_status = 1;
                    }
                }

                // 当前会员权益卡领取有效期结束时间 小于当前时间 无法续费、可重新购买其他权益卡
                if (empty($expiry_date) || (isset($val['expiry_date_start_timestamp']) && $now > $val['expiry_date_start_timestamp'])) {
                    $expiry['card_expiry_status'] = 2;
                    $expiry['card_status_notice'] = lang('drp.card_status_notice_2');
                    $expiry_time_notice = '';
                }

            } elseif ($val['expiry_type'] == 'days') {
                $expiry_date = $val['expiry_date'] ?? '';

                // 领取几天后过期 expiry_time = 10.31 + 7 时间戳
                if (!empty($expiry_time)) {
                    $remaining_time = $expiry_time - $now; // 有效期剩余时间

                    if ($remaining_time > 0 && $remaining_time <= $one_month) {
                        // 即将过期
                        $expiry_time_format = $this->timeRepository->getLocalDate('Y-m-d H:i', $expiry_time);
                        $expiry_time_notice = lang('drp.expiry_time_notice_1', ['expiry_time_format' => $expiry_time_format]);
                        $expiry_status = 2;

                        // 会员权益有效期
                        $expiry['expiry_time_format'] = $expiry_time_format;
                    }

                    // 当前时间 大于 会员权益领取天数记录时间
                    if ($now > $expiry_time) {
                        // 已过期
                        $expiry_time_notice = lang('drp.expiry_time_notice_0');
                        $expiry_status = 1;
                    }
                }

                // 当前会员权益卡领取有效期天数 等于0 无法续费、可重新购买其他权益卡
                if (empty($expiry_date)) {
                    $expiry['card_expiry_status'] = 2;
                    $expiry['card_status_notice'] = lang('drp.card_status_notice_2');
                    $expiry_time_notice = '';
                }

            } elseif ($val['expiry_type'] == 'forever') {
                // 永久有效
                if (empty($expiry_time)) {
                    // 会员权益有效期
                    $expiry['expiry_time_format'] = '永久有效';
                    return $expiry;
                }
            }

            $expiry['expiry_status'] = $expiry_status;
            $expiry['expiry_time_notice'] = $expiry_time_notice;

            if ($expiry['card_expiry_status'] == 0) {
                // 当前会员权益卡已停发
                $expiry['card_status_notice'] = lang('drp.card_status_notice_0');
                $expiry['expiry_time_notice'] = '';
            }
        }

        return $expiry;
    }


    /**
     * 获取文章标题
     * @param int $article_id
     * @return string
     */
    public function getArticleTitle($article_id = 0)
    {
        if (empty($article_id)) {
            return '';
        }

        $article_title = Article::where('article_id', $article_id)->value('title');

        return $article_title;
    }
}
