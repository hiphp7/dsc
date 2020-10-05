<?php

namespace App\Plugins\Wechat\Bonus;

use App\Http\Controllers\Wechat\PluginController;
use App\Models\BonusType;
use App\Models\UserBonus;

/**
 * Class Bonus 注册送红包
 * @package App\Plugins\Wechat\Bonus
 */
class Bonus extends PluginController
{
    // 插件名称
    protected $plugin_name = '';
    // 配置
    protected $cfg = [];

    /**
     * 构造方法
     *
     * @param array $cfg
     */
    public function __construct($cfg = [])
    {
        parent::__construct();
        $this->plugin_name = strtolower(basename(__FILE__, '.php'));
        $this->cfg = $cfg;
        //红包数据，线下发放类型
        $bonus = BonusType::select('type_id', 'type_name', 'type_money')
            ->where('send_type', 3)
            ->where('send_end_date', '>', $this->timeRepository->getGmTime())
            ->get();
        $bonus = $bonus ? $bonus->toArray() : [];

        $this->cfg['bonus'] = $bonus;

        $this->plugin_assign('config', $this->cfg);
    }

    /**
     * 安装
     */
    public function install()
    {
        return $this->plugin_display('install', $this->_data);
    }

    /**
     * 获取数据
     *
     * @param string $fromusername
     * @param array $info
     * @return array|bool
     */
    public function returnData($fromusername = '', $info = [])
    {
        $articles = ['type' => 'text', 'content' => ''];
        if (!empty($info)) {
            // 配置信息
            $config = isset($info['config']) ? unserialize($info['config']) : [];
            //开启红包赠送
            if (isset($config['bonus_status']) && $config['bonus_status'] == 1 && !empty($this->cfg['bonus'])) {
                //用户第一次关注赠送红包并且设置了赠送的红包
                $users = $this->wechatUserService->get_wechat_user_id($fromusername);
                if (empty($users) || empty($users['mobile_phone'])) {
                    $articles = ['type' => 'text', 'content' => lang('wechat.bonus_mobile_phone_empty')];
                    return $articles;
                }
                $uid = empty($users['user_id']) ? 0 : $users['user_id'];
                if (!empty($uid) && !empty($config['bonus'])) {
                    $time = $this->timeRepository->getGmTime();

                    $where = [
                        'type_id' => $config['bonus'],
                        'send_end_date' => $time,
                    ];
                    $model = UserBonus::where('user_id', $uid);
                    $model = $model->whereHas('getBonusType', function ($query) use ($where) {
                        $query->where('send_type', 3)
                            ->where('type_id', $where['type_id'])
                            ->where('send_end_date', '>', $where['send_end_date']);
                    });
                    $bonus_num = $model->count();

                    if ($bonus_num && $bonus_num > 0) {
                        $articles['content'] = lang('wechat.bouns_exist');
                    } else {
                        $data['bonus_type_id'] = $config['bonus'];
                        $data['bonus_sn'] = 0;
                        $data['user_id'] = $uid;
                        $data['used_time'] = 0;
                        $data['order_id'] = 0;
                        $data['emailed'] = 0;
                        UserBonus::create($data);

                        $where2 = [
                            'send_type' => 3,
                            'type_id' => $config['bonus']
                        ];
                        $type_money = BonusType::where($where2)->value('type_money');
                        $articles['content'] = lang('wechat.bouns_send_success', ['type_money' => $type_money]);
                        // 积分赠送
                        $this->wechatPluginService->updatePoint($fromusername, $info);
                    }
                    return $articles;
                }
            }
        }

        return false;
    }


    /**
     * 行为操作
     */
    public function executeAction()
    {
    }
}
