<?php

namespace App\Services\Drp;

use App\Models\DrpConfig;
use App\Repositories\Common\BaseRepository;


class DrpConfigService
{
    protected $baseRepository;

    public function __construct(
        BaseRepository $baseRepository
    )
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * 获取分销店铺配置
     * @param null $code 配置名称
     * @param bool $force 强制获取
     * @return array|\Illuminate\Cache\CacheManager|mixed|string
     */
    public function drpConfig($code = null, $force = false)
    {
        $arr = cache('drp_config');

        if (is_null($arr) || $force) {
            $res = DrpConfig::select('value', 'code');
            $res = $this->baseRepository->getToArrayGet($res);

            if ($res) {
                foreach ($res as $row) {
                    $arr[$row['code']] = $row;
                }
            } else {
                return [];
            }

            cache()->forever('drp_config', $arr);
        }

        return is_null($code) ? $arr : $arr[$code] ?? '';
    }

    /**
     * 更新配置
     * @param string $code
     * @param array $data
     * @return mixed
     */
    public function updateDrpConfig($code = '', $data = [])
    {
        if (empty($data)) {
            return false;
        }

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = ($val === null) ? '' : $val;
        });

        return DrpConfig::where('code', $code)->update($data);
    }

    /**
     * 更新所有配置
     * @param array $data
     * @return mixed
     */
    public function updateDrpAllConfig($data = [])
    {
        if (empty($data)) {
            return false;
        }

        foreach ($data as $code => $value) {
            $updata = [];
            $updata['value'] = $value;
            $this->updateDrpConfig($code, $updata);
        }

        return true;
    }

    /**
     * 配置
     * @param string $code
     * @param string $group
     * @return array
     */
    public function getDrpConfig($code = '', $group = '')
    {
        $model = DrpConfig::query()->where('group', $group);

        if (empty($code)) {
            $model = $model->where('type', '<>', 'hidden');//->where('code', '<>', 'drp_affiliate')
        } else {
            $model = $model->where('code', $code);
        }

        $model = $model->orderBy('sort_order', 'ASC')->orderBy('type')->get();

        $list = $model ? $model->toArray() : [];

        return $list;
    }

    /**
     * 后台分销配置列表
     * @param string $code
     * @param string $group
     * @return array
     */
    public function getDrpConfigList($code = '', $group = '')
    {
        $list = $this->getDrpConfig($code, $group);

        if (!empty($list)) {
            $_lang = lang('admin/drp');

            // 定义 code 的值 类型 为数字的样式  如 数字显示输出 number 默认 text文本
            $code_arr = [
                'draw_money',
                'articlecatid',
                'agreement_id',
                'settlement_time',
                'children_expiry_days',
            ];

            foreach ($list as $key => $value) {
                $list[$key]['name'] = isset($_lang['drp_cfg_name'][$value['code']]) ? $_lang['drp_cfg_name'][$value['code']] : $value['name'];
                $list[$key]['warning'] = isset($_lang['drp_cfg_notice'][$value['code']]) ? $_lang['drp_cfg_notice'][$value['code']] : $value['warning'];

                if ($value['type'] == 'text' && in_array($value['code'], $code_arr)) {
                    $list[$key]['style'] = 'number';
                    $unit = $this->transUnit($value['code']); // 单位
                    $list[$key]['unit'] = $_lang[$unit] ?? '';
                }
            }
        }

        return $list;
    }

    /**
     * 格式化 时间单位、金额单位 配合语言包显示 如 day 天 week 周 month 月 year 年  yuan 元
     * @param string $code
     * @return bool|string
     */
    private function transUnit($code = '')
    {
        if (empty($code)) {
            return '';
        }

        $unit = '';
        switch ($code) {
            case 'settlement_time':
            case 'children_expiry_days':
                $unit = 'day';
                break;
            case 'draw_money':
                $unit = 'yuan';
                break;
            default:
                break;
        }

        return $unit;
    }

    /**
     * 获得名片二维码设置信息
     * @return array|mixed
     */
    public function getQrcodeConfig()
    {
        $value = DrpConfig::where('code', 'qrcode')->value('value');

        $value = $value ? unserialize($value) : [];

        $config = $this->qrcodeData($value);

        return $config;
    }

    /**
     * 保存名片二维码配置
     * @param array $data
     * @return bool|mixed
     */
    public function updateDrpQrcodeConfig($data = [])
    {
        if (empty($data)) {
            return false;
        }

        $config = [
            'backbround' => $data['file'],
            'qr_left' => $data['qr_left'],
            'qr_top' => $data['qr_top'],
            'avatar' => $data['avatar'] ?? 0,
            'av_left' => $data['av_left'],
            'av_top' => $data['av_top'],
            'description' => $data['description'],
            'color' => $data['color'],
        ];
        $value = serialize($config);

        $qrcode = DrpConfig::where('code', 'qrcode')->count();
        if (empty($qrcode)) {
            $creatData = [
                'code' => 'qrcode',
                'type' => 'hidden',
                'value' => $config,
                'name' => lang('admin/drp.business_card_config'),
            ];
            DrpConfig::create($creatData);
        }

        return $this->updateDrpConfig('qrcode', ['value' => $value]);
    }

    /**
     * 二维码配置默认数据
     * @param array $data
     * @return array
     */
    public function qrcodeData($data = [])
    {
        // qr_left 二维码坐标 X
        // qr_top 二维码坐标 Y
        // av_left 头像坐标 X
        // av_top 头像坐标 Y
        // description 文字内容
        // color 文字颜色
        // avatar  显示微信头像

        $config = [
            'backbround' => $data['backbround'] ?? 'img/drp_bg.png',
            'qr_left' => $data['qr_left'] ?? '170',
            'qr_top' => $data['qr_top'] ?? '790',
            'avatar' => $data['avatar'] ?? 0,
            'av_left' => $data['av_left'] ?? '100',
            'av_top' => $data['av_top'] ?? '24',
            'description' => $data['description'] ?? '',
            'color' => $data['color'] ?? '#000000',
        ];

        return $config;
    }

    /**
     * 恢复二维码默认数据
     * @return mixed
     */
    public function resetQrcode()
    {
        $config = $this->qrcodeData();

        $value = serialize($config);

        return $this->updateDrpConfig('qrcode', ['value' => $value]);
    }

}
