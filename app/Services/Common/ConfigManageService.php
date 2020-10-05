<?php

namespace App\Services\Common;


use App\Models\ShopConfig;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;

class ConfigManageService
{
    protected $config;
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 获得设置分组
     *
     * @param string $groups
     * @return array
     */
    public function getSettingGroups($groups = '')
    {
        $group_list = [];
        $list = $this->getUpSettings($groups);

        //将设置规组
        if ($list) {
            foreach ($list as $key => $val) {
                $group_list[$val['parent_id']]['vars'][] = $val;
            }
        }

        //补全组信息
        if ($group_list) {
            foreach ($group_list as $key => $val) {

                $data = ShopConfig::where('id', $key);
                $data = $this->baseRepository->getToArrayFirst($data);

                //处理数据
                $data['name'] = isset($GLOBALS['_LANG']['cfg_name'][$data['code']]) ? $GLOBALS['_LANG']['cfg_name'][$data['code']] : $data['code'];
                $data['desc'] = isset($GLOBALS['_LANG']['cfg_desc'][$data['code']]) ? $GLOBALS['_LANG']['cfg_desc'][$data['code']] : '';

                //合并数据
                $data = array_merge($data, $val);
                $group_list[$key] = $data;
            }
        }

        return $group_list;
    }

    /**
     * 获得设置信息
     *
     * @param string $groups 需要获得的设置组
     * @return array
     */
    public function getUpSettings($groups = '')
    {

        /* 取出全部数据：分组和变量 */
        $item_list = ShopConfig::where('parent_id', '>', 0)
            ->where('type', '<>', 'hidden');

        if (!empty($groups)) {
            $item_list = $item_list->where('shop_group', $groups);
        }

        $item_list = $item_list->orderByRaw("sort_order, parent_id, id asc");

        $item_list = $this->baseRepository->getToArrayGet($item_list);

        /* 整理数据 */
        $code_arr = [
            'shop_logo',
            'no_picture',
            'watermark',
            'shop_slagon',
            'wap_logo',
            'two_code_logo',
            'ectouch_qrcode',
            'ecjia_qrcode',
            'index_down_logo',
            'user_login_logo',
            'login_logo_pic',
            'business_logo',
            'no_brand'
        ];

        $group_list = [];
        if ($item_list) {
            foreach ($item_list as $key => $item) {
                $item['name'] = isset($GLOBALS['_LANG']['cfg_name'][$item['code']]) ? $GLOBALS['_LANG']['cfg_name'][$item['code']] : $item['code'];
                $item['desc'] = isset($GLOBALS['_LANG']['cfg_desc'][$item['code']]) ? $GLOBALS['_LANG']['cfg_desc'][$item['code']] : '';

                if ($item['code'] == 'sms_shop_mobile') {
                    $item['url'] = 1;
                }

                if ($item['store_range']) {
                    $item['store_options'] = explode(',', $item['store_range']);

                    foreach ($item['store_options'] as $k => $v) {
                        $item['display_options'][$k] = isset($GLOBALS['_LANG']['cfg_range'][$item['code']][$v]) ?
                            $GLOBALS['_LANG']['cfg_range'][$item['code']][$v] : $v;
                    }
                }
                if ($item) {
                    if ($item['type'] == 'file' && in_array($item['code'], $code_arr) && $item['value']) {
                        $item['del_img'] = 1;

                        $item['value'] = $this->dscRepository->getImagePath($item['value']);
                    } else {
                        $item['del_img'] = 0;
                    }
                }
                $group_list[] = $item;
            }
        }

        return $group_list;
    }
}