<?php

namespace App\Services\UserRights;

use App\Models\UserMembershipCard;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Common\PluginManageService;

/**
 * 权益卡前后台公用
 * Class RightsCardCommonService
 * @package App\Services\UserRights
 */
class RightsCardCommonService
{
    protected $config;

    protected $baseRepository;
    protected $timeRepository;
    protected $dscRepository;
    protected $pluginManageService;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        PluginManageService $pluginManageService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->pluginManageService = $pluginManageService;
    }

    /**
     * 权益卡信息(包含权益卡绑定的权益列表)
     * @param int $id
     * @return array
     */
    public function membershipCardInfo($id = 0)
    {
        if (empty($id)) {
            return [];
        }

        $model = UserMembershipCard::where('id', $id);

        $model = $model->with([
            'userMembershipCardRightsList' => function ($query) {
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
     * 权益卡信息(包含权益卡绑定并且启用的权益列表)
     * @param int $id
     * @param int $rights_id
     * @return array
     */
    public function membershipCardInfoByRightsId($id = 0, $rights_id = 0)
    {
        if (empty($id)) {
            return [];
        }

        $model = UserMembershipCard::where('id', $id);

        $model = $model->with([
            'userMembershipCardRightsList' => function ($query) use ($rights_id) {
                if ($rights_id > 0) {
                    $query = $query->where('rights_id', $rights_id);
                }

                $query->with([
                    'userMembershipRights' => function ($query) {
                        $query->select('id', 'name', 'code', 'icon', 'trigger_point', 'enable', 'rights_configure')->where('enable', 1);
                    }
                ]);
            }
        ]);

        $model = $model->first();

        $info = $model ? $model->toArray() : [];

        return $info;
    }

    /**
     * 统一处理  查询权益卡信息
     * @param array $info
     * @return array
     */
    public function transFormRightsCardInfo($info = [])
    {
        if (empty($info)) {
            return [];
        }

        if (!empty($info)) {
            if (isset($info['background_img']) && !empty($info['background_img'])) {
                $info['background'] = 1; // 用于区分前端选中项 0 color 1 img
                $info['background_img'] = $this->dscRepository->getImagePath($info['background_img']);
            } else {
                $info['background'] = 0; // 用于区分前端选中项 0 color 1 img
            }

            $info['receive_value'] = empty($info['receive_value']) ? '' : unserialize($info['receive_value']);
            $info['receive_value_format'] = '';
            if (!empty($info['receive_value'])) {
                foreach ($info['receive_value'] as $k => $item) {
                    if ($item['type'] == 'order' || $item['type'] == 'buy') {
                        $info['receive_value'][$k]['value_format'] = $this->dscRepository->getPriceFormat($item['value'], true);
                    } else {
                        $info['receive_value'][$k]['value_format'] = $item['value'];
                    }
                    // 用于后台显示
                    if ($item['type'] == 'goods') {
                        $info['receive_value_format'] .= lang('admin/drpcard.receive_type_' . $item['type']) . "；<br/>";
                    } else {
                        $info['receive_value_format'] .= lang('admin/drpcard.receive_type_' . $item['type']) . "：" . $info['receive_value'][$k]['value_format'] . "；<br/>";
                    }

                }
            }

            // 卡有效期
            if (isset($info['expiry_type']) && !empty($info['expiry_type'])) {
                // 开始与结束时间
                if ($info['expiry_type'] == 'timespan') {
                    $expiry_date = $info['expiry_date'] ?? '';
                    if (!empty($expiry_date)) {

                        list($expiry_date_start, $expiry_date_end) = is_string($expiry_date) ? explode(',', $expiry_date) : $expiry_date;

                        $info['expiry_date_start'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $expiry_date_start);
                        $info['expiry_date_end'] =  $this->timeRepository->getLocalDate('Y-m-d H:i:s', $expiry_date_end);
                        $info['expiry_date_start_timestamp'] = $expiry_date_start;
                        $info['expiry_date_end_timestamp'] = $expiry_date_end;

                        $info['expiry_type_format'] = lang('admin/drpcard.expiry_date_start') . ':' . $info['expiry_date_start'] . '<br>' . lang('admin/drpcard.expiry_date_end') . ':' . $info['expiry_date_end'];
                    }

                } elseif ($info['expiry_type'] == 'days') {
                    $expiry_date = $info['expiry_date'] ?? '';
                    $info['expiry_type_format'] = lang('admin/drpcard.expiry_type_' . $info['expiry_type']) . $expiry_date . lang('admin/drpcard.expiry_type_' . $info['expiry_type'] . '_part');
                } else {
                    $info['expiry_type_format'] = lang('admin/drpcard.expiry_type_' . $info['expiry_type']);
                }
            }

            $info['add_time_format'] = empty($info['add_time']) ? '' : $this->timeRepository->getLocalDate('Y-m-d H:i:s', $info['add_time']);
        }

        return $info;
    }

    /**
     * 统一处理 会员权益卡下的权益列表
     * @param array $item
     * @return array|bool|mixed
     */
    public function transFormCardRightsList($item = [])
    {
        if (empty($item)) {
            return [];
        }

        $list = $item['user_membership_card_rights_list'] ?? [];

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
     * 统一处理 格式化会员权益信息
     * @param array $info
     * @return array
     */
    public function transFormRightsInfo($info = [])
    {
        if (empty($info)) {
            return [];
        }

        $info['rights_configure'] = empty($info['rights_configure']) ? [] : unserialize($info['rights_configure']);

        $info['icon'] = empty($info['icon']) ? '' : ((stripos($info['icon'], 'assets') !== false) ? asset($info['icon']) : $this->dscRepository->getImagePath($info['icon']));
        $info['trigger_point_format'] = empty($info['trigger_point']) ? '' : lang('admin/users.trigger_point_' . $info['trigger_point']);

        return $info;
    }


    /**
     * 统一处理 格式化会员权益列表信息
     * @param array $item
     * @return array|bool|mixed
     */
    public function transFormRightsList($item = [])
    {
        if (empty($item)) {
            return [];
        }

        $list = $item['user_membership_rights_list'] ?? [];

        if (empty($list)) {
            return false;
        }

        foreach ($list as $k => $value) {
            // 权益内容
            $list[$k]['rights_configure'] = empty($value['rights_configure']) ? [] : unserialize($value['rights_configure']);

            $list[$k]['icon'] = empty($value['icon']) ? '' : ((stripos($value['icon'], 'assets') !== false) ? asset($value['icon']) : $this->dscRepository->getImagePath($value['icon']));
            $list[$k]['trigger_point_format'] = empty($value['trigger_point']) ? '' : lang('admin/users.trigger_point_' . $value['trigger_point']);

            // 插件实例
            $obj = $this->pluginManageService->pluginInstance($value['code'], 'UserRights');
            if (!is_null($obj)) {
                // 插件配置
                $cfg = $this->pluginManageService->getPluginConfig($value['code'], 'UserRights', $list[$k]);
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
            $list[$k]['rights_configure_format'] = $rights_configure_format;
        }

        return $list;
    }


}
