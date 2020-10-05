<?php

namespace App\Services\User;

use App\Models\AccountLog;
use App\Models\UserMembershipRights;
use App\Models\UserRank;
use App\Models\UserRankRights;
use App\Models\Users;
use App\Models\UserMembershipCard;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Common\PluginManageService;
use App\Services\UserRights\UserRightsManageService;

class UserRankService
{
    protected $baseRepository;
    protected $timeRepository;
    protected $config;
    protected $dscRepository;
    protected $pluginManageService;
    protected $userRightsManageService;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        PluginManageService $pluginManageService,
        UserRightsManageService $userRightsManageService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->pluginManageService = $pluginManageService;
        $this->userRightsManageService = $userRightsManageService;
    }

    /**
     * 取得用户等级和折扣
     * @access  public
     * @param  $user_id 用户ID
     * @return  array
     */
    public function getUserRankInfo($user_id)
    {
        $info = [];
        $row = Users::select('user_rank', 'rank_points')
            ->where('user_id', $user_id);
        $row = $this->baseRepository->getToArrayFirst($row);

        if (is_null($row)) {
            return $info;
        }

        $rank_points = intval($row['rank_points']);
        /* 取得用户等级和折扣 */
        if (isset($row['user_rank']) && $row['user_rank'] == 0) {
            // 非特殊等级，根据等级积分计算用户等级（注意：不包括特殊等级）
            $row = $this->getUserRankByPoint($rank_points);
            if (!empty($row)) {
                $info['user_rank'] = $row['rank_id'];
                $info['rank_name'] = $row['rank_name'];
                $info['discount'] = $row['discount'] / 100.00;
                if (!empty($row['rank_id'])) {
                    Users::where('user_id', $user_id)->update(['user_rank' => $info['user_rank']]);
                }
            } else {
                $info['user_rank'] = 0;
                $info['discount'] = 1;
            }
        } else {
            if (isset($row['user_rank']) && $row['user_rank'] > 0) {
                // 特殊等级
                $row = UserRank::select('rank_id', 'rank_name', 'discount')
                    ->where('rank_id', $row['user_rank']);
                $row = $this->baseRepository->getToArrayFirst($row);

                if (!empty($row)) {
                    $info['user_rank'] = $row['rank_id'];
                    $info['rank_name'] = $row['rank_name'];
                    $info['discount'] = $row['discount'] / 100.00;
                } else {
                    $row = $this->getUserRankByPoint($rank_points);
                    if (!empty($row)) {
                        $info['user_rank'] = $row['rank_id'];
                        $info['rank_name'] = $row['rank_name'];
                        $info['discount'] = $row['discount'] / 100.00;
                    } else {
                        $info['user_rank'] = 0;
                        $info['discount'] = 1;
                    }
                }
            }
        }

        return $info;
    }

    /**
     * 获取用户的会员等级信息
     *
     * @param int $user_id
     * @return array
     */
    public function getUsersRankInfo($user_id = 0)
    {
        $data = [];
        if ($user_id) {
            $user_rank = UserRank::whereHas('getUsers', function ($query) use ($user_id) {
                $query->where('user_id', $user_id);
            });

            $user_rank = $this->baseRepository->getToArrayFirst($user_rank);

            //是否是特殊会员等级
            if ($user_rank) {
                $user_rank['special_rank'] = 1;
            } else {
                //用户是一般会员

                //是否开启等级积分清零
                if (isset($this->config['open_user_rank_set']) && $this->config['open_user_rank_set']) {
                    $clear_rank_point = $this->config['clear_rank_point'] ?? 12;
                    $clert_time = (!isset($clear_rank_point) || $clear_rank_point <= 0) ? 12 : intval($clear_rank_point);//默认12个月

                    $clear_start_time = $this->timeRepository->getLocalStrtoTime('-' . $clert_time . ' month');
                    $rank_points = AccountLog::where('user_id', $user_id)
                        ->where('change_time', '>=', $clear_start_time)
                        ->sum('rank_points');
                } else {
                    $rank_points = Users::where(['user_id' => $user_id])->value('rank_points');
                }


                $user_rank = [];
                if ($rank_points >= 0) {
                    //1.4.3 会员等级修改（成长值只有下限）
                    $user_rank = $this->getUserRankByPoint($rank_points);
                }
            }
            $data['special_rank'] = $user_rank['special_rank'] ?? 0;
            $data['rank_id'] = $user_rank['rank_id'] ?? 0;
            $data['rank_name'] = $user_rank['rank_name'] ?? '';
            $data['discount'] = $user_rank['discount'] ?? 100;
        }

        return $data;
    }

    /**
     * @param $rank_points
     */
    public function getUserRankByPoint($rank_points)
    {
        $user_rank = UserRank::where('special_rank', 0)
            ->where('min_points', '<=', $rank_points)
            ->orderBy('min_points', 'desc');
        $user_rank = $this->baseRepository->getToArrayFirst($user_rank);
        return $user_rank;
    }

    /**
     * 非特殊会员等级排序，由1到+∞
     *
     * @param int $rank_id
     * @param string $sort
     * @return bool|false|int|string
     */
    public function getUserRankSort($rank_id = 0, $sort = 'ASC')
    {
        $rank_ids = UserRank::select('rank_id')
            ->where('special_rank', 0)
            ->orderBy('min_points', $sort);

        $rank_ids = $this->baseRepository->getToArrayGet($rank_ids);

        /* 转化一维数组 */
        $rank_ids = $this->baseRepository->getFlatten($rank_ids);

        if (empty($rank_ids)) {
            return false;
        }

        $rank_sort = array_search($rank_id, $rank_ids);
        if ($rank_sort !== false) {
            return $rank_sort + 1;
        } else {
            return false;
        }
    }

    /**
     * 取得用户等级
     *
     * @param array $rank_ids
     * @return array
     * @throws \Exception
     */
    public function getUserRank($rank_ids = [])
    {

        $rank_ids = $this->baseRepository->getExplode($rank_ids);

        $user_rank_list = [];
        $user_rank_list[0] = lang('user.not_user');

        $res = UserRank::whereRaw(1);

        if ($rank_ids) {
            $res = $res->whereIn('rank_id', $rank_ids);
        }

        $res = $this->baseRepository->getToArrayGet($res);
        if ($res) {
            foreach ($res as $row) {
                $user_rank_list[$row['rank_id']] = $row['rank_name'];
            }
        }

        return $user_rank_list;
    }

    /**
     * 取得用户等级
     *
     * @param array $filter
     * @return array
     * @throws \Exception
     */
    public function getUserRankList($filter = [])
    {

        $rank_ids = isset($filter['rank_ids']) ? $filter['rank_ids'] : [];
        $special_rank = isset($filter['special_rank']) ? intval($filter['special_rank']) : null;
        $sort_by = isset($filter['sort_by']) ? trim($filter['sort_by']) : 'min_points';
        $sort_order = isset($filter['sort_order']) ? trim($filter['sort_order']) : 'asc';
        $membership_card_display = isset($filter['membership_card_display']) ? trim($filter['membership_card_display']) : null;

        $rank_ids = $this->baseRepository->getExplode($rank_ids);

        $res = UserRank::with([
            'userRankRights' => function ($query) {
                $query->with([
                    'userMembershipRights' => function ($query) {
                        $query->select('id', 'name', 'code', 'icon', 'trigger_point', 'enable', 'rights_configure')->where('enable', 1);
                    }
                ]);
            }
        ]);

        if ($rank_ids) {
            $res = $res->whereIn('rank_id', $rank_ids);
        }

        if (!is_null($special_rank)) {
            $res = $res->where('special_rank', $special_rank);
        }

        if (file_exists(MOBILE_DRP)) {
            //是否显示关联分销权益卡等级 1.4.4
            if ($membership_card_display && $membership_card_display == 'hide') {
                $rank_ids_link_card = collect(UserMembershipCard::select('user_rank_id')->get())->map(function ($item, $key) {
                    return $item['user_rank_id'];
                })->toArray();
                if ($rank_ids_link_card) {
                    $res = $res->whereNotIn('rank_id', $rank_ids_link_card);
                }
            }
        }

        $res = $res->orderBy($sort_by, $sort_order);

        $res = $this->baseRepository->getToArrayGet($res);

        if (!empty($res)) {
            foreach ($res as $key => $row) {
                $res[$key]['user_rank_rights_string'] = '';

                $res[$key]['user_rank_rights'] = $this->transFormRightsList($row);
                if (!empty($res[$key]['user_rank_rights'])) {
                    foreach ($res[$key]['user_rank_rights'] as $val) {
                        if (!empty($val['name'])) {
                            $res[$key]['user_rank_rights_string'] .= $val['name'] . ':' . ($val['rights_configure_format'] ?? '');
                        }
                    }
                }
            }
        }

        return $res;
    }

    /**
     * 判断会员是否为特殊会员等级
     *
     * @param int $user_id
     * @return mixed
     */
    public function judgeUserSpecialRank($user_id = 0)
    {
        $special_rank = UserRank::whereHas('getUsers', function ($query) use ($user_id) {
            $query->where('user_id', $user_id);
        });

        $special_rank = $special_rank->value('special_rank');

        return $special_rank;
    }

    public function getRankInfo($rank_id)
    {
        $res = UserRank::whereRaw(1);

        if ($rank_id) {
            $res = $res->where('rank_id', $rank_id);
        }

        $res = $this->baseRepository->getToArrayFirst($res);

        return $res;
    }

    /**
     * 绑定权益
     * @param int $membership_card_id
     * @param array $rights_data
     * @return bool
     */
    public function bindCardRights($rank_id = 0, $rights_data = [])
    {
        if (empty($rank_id) || empty($rights_data)) {
            return false;
        }

        $data = [];

        $now = $this->timeRepository->getGmTime();

        foreach ($rights_data as $k => $item) {
            $data[$k] = $this->baseRepository->getArrayfilterTable($item, 'user_rank_rights');

            $data[$k]['user_rank_id'] = $rank_id;
            $data[$k]['rights_id'] = $item;
            $data[$k]['add_time'] = $now;
        }

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = is_null($val) ? '' : $val;
        });

        return UserRankRights::insert($data);
    }

    /**
     * 绑定权益列表
     * @param int $membership_card_id
     * @return array
     */
    public function bindCardRightsList($rank_id = 0)
    {
        if (empty($rank_id)) {
            return [];
        }

        $model = UserRankRights::query()->where('user_rank_id', $rank_id)
            ->get();

        $list = $model ? $model->toArray() : [];

        return $list;
    }

    /**
     * 查询信息
     * @param int $id
     * @return array|mixed
     */
    public function bindCardRightsInfo($id = 0)
    {
        if (empty($id)) {
            return [];
        }

        $model = UserRankRights::query()->where('id', $id);

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
     * 编辑会员卡权益
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateCardRights($id = 0, $data = [])
    {
        if (empty($id) || empty($data)) {
            return false;
        }

        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'user_rank_rights');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = is_null($val) ? '' : $val;
        });

        $data['update_time'] = $this->timeRepository->getGmTime();

        return UserRankRights::where('id', $id)->update($data);
    }

    /**
     * 解除绑定权益
     * @param int $id
     * @return bool
     */
    public function unbindCardRights($id = 0)
    {
        if (empty($id)) {
            return false;
        }

        return UserRankRights::where('id', $id)->delete();
    }

    /**
     * 权益卡信息(包含权益卡绑定的权益列表)
     * @param int $id
     * @param int $rights_id
     * @return array
     */
    public function userRankRightInfo($id = 0, $rights_id = 0)
    {
        if (empty($id)) {
            return [];
        }

        $model = UserRank::where('rank_id', $id);

        $model = $model->with([
            'userRankRights' => function ($query) use ($rights_id) {
                if ($rights_id > 0) {
                    $query = $query->where('rights_id', $rights_id);
                }

                $query->with([
                    'userMembershipRights' => function ($query) {
                        $query->select('id', 'name', 'code', 'icon', 'trigger_point', 'enable', 'rights_configure');
                    }
                ]);
            }
        ]);

        $model = $model->first();

        $info = $model ? $model->toArray() : [];

        return $info;
    }

    /**
     * 统一处理 会员权益卡下的权益列表
     * @param array $item
     * @return array|bool|mixed
     */
    public function transFormRightsList($item = [])
    {
        if (empty($item)) {
            return [];
        }

        $list = $item['user_rank_rights'] ?? [];

        if (empty($list)) {
            return false;
        }

        foreach ($list as $k => $value) {
            // 权益内容
            if (!empty($value['rights_configure'])) {
                $list[$k]['rights_configure'] = unserialize($value['rights_configure']);
            } else {
                // 绑定权益配置为空 统一调用默认权益配置
                $list[$k]['rights_configure'] = empty($value['user_membership_rights']['rights_configure']) ? '' : unserialize($value['user_membership_rights']['rights_configure']);
            }

            if (isset($value['user_membership_rights']) && !empty($value['user_membership_rights'])) {
                $list[$k]['name'] = $value['user_membership_rights']['name'] ?? '';
                $list[$k]['icon'] = $value['user_membership_rights']['icon'] ?? '';
                $list[$k]['trigger_point'] = $value['user_membership_rights']['trigger_point'] ?? '';
                $list[$k]['icon'] = empty($list[$k]['icon']) ? '' : ((stripos($list[$k]['icon'], 'assets') !== false) ? asset($list[$k]['icon']) : $this->dscRepository->getImagePath($list[$k]['icon']));
                $list[$k]['trigger_point_format'] = empty($list[$k]['trigger_point']) ? '' : lang('admin/users.trigger_point_' . $list[$k]['trigger_point']);

                // 插件实例
                if (isset($value['user_membership_rights']['enable']) && $value['user_membership_rights']['enable']) {
                    //启用
                    $list[$k]['enable'] = 1;
                    $obj = $this->pluginManageService->pluginInstance($value['user_membership_rights']['code'], 'UserRights');

                    $plugin_info = [];
                    if (!is_null($obj)) {
                        // 插件配置
                        $cfg = $this->pluginManageService->getPluginConfig($value['user_membership_rights']['code'], 'UserRights', $list[$k]);
                        $obj->setPluginInfo($cfg);

                        $plugin_info = $obj->getPluginInfo();
                        if (empty($list[$k]['rights_configure'])) {
                            $list[$k]['rights_configure'] = $plugin_info['rights_configure'];
                        }
                    }

                    $rights_configure_format = '';
                    if (isset($plugin_info['rights_configure']) && $plugin_info['rights_configure']) {
                        foreach ($plugin_info['rights_configure'] as $row) {
                            if (isset($row['range'][$row['value']])) {
                                $rights_configure_format .= $row['label'] . ':' . $row['range'][$row['value']] . ';';
                            } else {
                                if (isset($row['value'])) {
                                    $rights_configure_format .= $row['label'] . ':' . $row['value'] . ';';
                                }
                            }
                        }
                    }
                } else {
                    //关闭
                    $list[$k]['enable'] = 0;
                    $rights_configure_format = lang('admin/user_rank.rights_close');
                }

                $list[$k]['rights_configure_format'] = $rights_configure_format;
            }
        }

        return $list;
    }

    /**
     * 检查名称是否重复
     * @param string $name
     * @param int $id
     * @return mixed
     */
    public function checkName($name = '', $id = 0)
    {
        if (empty($name)) {
            return false;
        }

        $model = UserRank::where('rank_name', $name);

        if (!empty($id)) {
            $model = $model->where('rank_id', '<>', $id);
        }

        $count = $model->count();

        return $count;
    }

    /**
     * 编辑等级
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateUserRank($id = 0, $data = [])
    {
        if (empty($id) || empty($data)) {
            return false;
        }

        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'user_rank');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = is_null($val) ? '' : $val;
        });

        return UserRank::where('rank_id', $id)->update($data);
    }

    public function deleteUserRank($id = 0)
    {
        if (empty($id)) {
            return false;
        }

        if (UserRank::where('rank_id', $id)->delete()) {
            //删除权益
            UserRankRights::where('user_rank_id', $id)->delete();

            /* 更新会员表的等级字段 */
//            Users::where('user_rank', $id)->update(['user_rank' => 0]);
        }

        return true;
    }

    public function deleteUserRankRights($id = 0, $rank_id = 0)
    {
        $userRankRights = UserRankRights::whereRaw(1);
        if ($id) {
            $userRankRights->where('id', $id);
        }

        if ($rank_id) {
            $userRankRights->where('user_rank_id', $rank_id);
        }

        return $userRankRights->delete();
    }

    public function syncData()
    {
        //user_rank.discount有数据，user_rank_rights数据为空时处理
        $count = UserRankRights::count();
        if ($count) {
            return false;
        }

        $user_ranks = UserRank::where('special_rank', 0);
        $user_ranks = $this->baseRepository->getToArrayGet($user_ranks);

        if ($user_ranks) {
            foreach ($user_ranks as $rank) {
                if ($rank['discount']) {
                    $plugin_data = $this->installDiscount();
                    $rights_configure = '';
                    if ($rank['discount'] != $plugin_data['rights_configure'][0]['value']) {
                        $plugin_data['rights_configure'][0]['value'] = $rank['discount'];
                        $rights_configure = serialize($plugin_data['rights_configure']);
                    }
                    UserRankRights::insert([
                        'rights_id' => $plugin_data['id'],
                        'rights_configure' => $rights_configure,
                        'user_rank_id' => $rank['rank_id'],
                        'add_time' => $this->timeRepository->getGmTime(),
                        'update_time' => $this->timeRepository->getGmTime(),
                    ]);
                }
            }
        }

    }

    //安装会员特价权益
    public function installDiscount()
    {
        $code = 'discount';
        $plugin_discount = UserMembershipRights::where('code', $code)->first();
        $plugin_discount_id = $plugin_discount['id'] ?? 0;
        if (empty($plugin_discount_id)) {
            $config = $this->pluginManageService->getPluginConfig($code, 'UserRights');
            $config['rights_configure'][0]['value'] = 100;//默认折扣
            $config['rights_configure'] = serialize($config['rights_configure']);
            $config['trigger_point'] = 'direct';
            $config['enable'] = 1;
            $res = $this->userRightsManageService->createUserRights($code, $config);
            if ($res) {
                $res = $res->toArray();
                $plugin_discount_id = $res['id'];
                $plugin_discount['rights_configure'] = $res['rights_configure'];
            }
        }

        return [
            'id' => $plugin_discount_id,
            'rights_configure' => $plugin_discount['rights_configure'] ? unserialize($plugin_discount['rights_configure']) : ''
        ];
    }

    /**
     * 权益配置信息
     * @param int $rights_id
     * @param int $limit
     * @return array
     */
    public function getCardRightsRank($rights_id = 0, $limit = 100, $enable = 1)
    {
        if (empty($rights_id)) {
            return [];
        }

        $model = UserRankRights::query()->where('rights_id', $rights_id);

        $model = $model->with([
            'getUserRank' => function ($query) {
                $query->where('special_rank', 0);
            }
        ]);

        $model = $model->limit($limit)->get();

        $list = $model ? $model->toArray() : [];

        return $list;
    }

    /**
     * 修改关联会员特价等级（未设置价格 使用默认配置）的折扣价
     * @param int $rights_id
     * @param int $discount
     * @return bool
     */
    public function updateDefaultRightDiscount($rights_id = 0, $discount = 100)
    {
        if (empty($rights_id)) {
            return false;
        }

        $user_rank = UserRankRights::where('rights_id', $rights_id)->get();
        $user_rank = $user_rank ? $user_rank->toArray() : [];
        if ($user_rank) {
            $user_rank_ids = [];
            foreach ($user_rank as $row) {
                $user_rank_ids[] = $row['user_rank_id'];
            }
            return UserRank::whereIn('rank_id', $user_rank_ids)->update(['discount' => $discount]);
        }
        return true;
    }
}
