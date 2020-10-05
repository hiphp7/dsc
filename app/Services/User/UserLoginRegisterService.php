<?php

namespace App\Services\User;


use App\Libraries\Error;
use App\Models\AdminUser;
use App\Models\Users;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Cart\CartCommonService;
use App\Services\Drp\DrpService;
use App\Services\Wechat\WechatService;

class UserLoginRegisterService
{
    protected $cartCommonService;
    protected $userCommonService;
    protected $error;
    protected $dscRepository;
    protected $config;
    protected $timeRepository;
    protected $userAccountService;
    protected $userCouponsService;
    protected $userAffiliateService;

    public function __construct(
        CartCommonService $cartCommonService,
        UserCommonService $userCommonService,
        Error $error,
        DscRepository $dscRepository,
        TimeRepository $timeRepository,
        UserAccountService $userAccountService,
        UserCouponsService $userCouponsService,
        UserAffiliateService $userAffiliateService
    )
    {
        $this->cartCommonService = $cartCommonService;
        $this->userCommonService = $userCommonService;
        $this->error = $error;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->timeRepository = $timeRepository;
        $this->userAccountService = $userAccountService;
        $this->userCouponsService = $userCouponsService;
        $this->userAffiliateService = $userAffiliateService;
    }

    /**
     * 用户注册
     *
     * @param string $username 注册用户名
     * @param string $password 用户密码
     * @param string $email 注册email
     * @param array $other 注册的其他信息
     * @return bool
     * @throws \Exception
     */
    public function register($username = '', $password = '', $email = '', $other = [])
    {
        /* 检查注册是否关闭 */
        if (!empty($this->config['shop_reg_closed'])) {
            $this->error->add(lang('common.shop_register_closed'));
            return false;
        }
        /* 检查username */
        if (empty($username)) {
            $this->error->add(lang('user.username_empty'));
            return false;
        } else {
            if (preg_match('/\'\/^\\s*$|^c:\\\\con\\\\con$|[%,\\*\\"\\s\\t\\<\\>\\&\'\\\\]/', $username)) {
                $this->error->add(sprintf(lang('user.username_invalid'), htmlspecialchars($username)));
                return false;
            }
        }

        /* 检查email */
        if (!empty($email)) {
            if (!is_email($email)) {
                $this->error->add(sprintf(lang('user.email_invalid'), htmlspecialchars($email)));
                return false;
            }
        }

        if ($GLOBALS['err']->error_no() > 0) {
            return false;
        }

        $adminCount = AdminUser::where('user_name', $username)->count();

        /* 检查是否和管理员重名 */
        if ($adminCount) {
            $this->error->add(sprintf(lang('user.username_exist'), $username));
            return false;
        }

        $other['is_validated'] = isset($other['is_validated']) ? $other['is_validated'] : 0;

        //用户注册方式信息
        $user_registerMode = ['email' => $email, 'mobile_phone' => $other['mobile_phone'], 'is_validated' => $other['is_validated']];

        if (!$GLOBALS['user']->add_user($username, $password, $user_registerMode)) {
            if ($GLOBALS['user']->error == ERR_INVALID_USERNAME) {
                $this->error->add(sprintf(lang('user.username_invalid'), $username));
            } elseif ($GLOBALS['user']->error == ERR_USERNAME_NOT_ALLOW) {
                $this->error->add(sprintf(lang('user.username_not_allow'), $username));
            } elseif ($GLOBALS['user']->error == ERR_USERNAME_EXISTS) {
                $this->error->add(sprintf(lang('username_exist'), $username));
            } elseif ($GLOBALS['user']->error == ERR_INVALID_EMAIL) {
                $this->error->add(sprintf(lang('user.email_invalid'), $email));
            } elseif ($GLOBALS['user']->error == ERR_EMAIL_NOT_ALLOW) {
                $this->error->add(sprintf(lang('user.email_not_allow'), $email));
            } elseif ($GLOBALS['user']->error == ERR_EMAIL_EXISTS) {
                $this->error->add(sprintf(lang('email_exist'), $email));
            } else {
                $this->error->add('UNKNOWN ERROR!');
            }

            // 注册失败
            return false;
        } else {
            // 注册成功
            $user = $this->userCommonService->getUserByName($username);
            $user_id = $user->user_id;

            /* 注册送积分 */
            if (!empty($this->config['register_points'])) {
                $this->userAccountService->logAccountChange($user_id, 0, 0, $this->config['register_points'], $this->config['register_points'], lang('user.register_points'));
            }

            /* 注册送优惠券 */
            $this->userCouponsService->registerCoupons($user_id);

            /*推荐处理*/
            $affiliate = unserialize($this->config['affiliate']);
            if (isset($affiliate['on']) && $affiliate['on'] == 1) {
                // 推荐开关开启
                $parent_id = $other['parent_id'] ?? 0;
                $up_uid = $this->userAffiliateService->getAffiliate($parent_id);
                empty($affiliate) && $affiliate = [];
                $affiliate['config']['level_register_all'] = intval($affiliate['config']['level_register_all']);
                $affiliate['config']['level_register_up'] = intval($affiliate['config']['level_register_up']);
                if ($up_uid > 0 && $user_id != $up_uid) {
                    if (!empty($affiliate['config']['level_register_all'])) {
                        if (!empty($affiliate['config']['level_register_up'])) {
                            $rank_points = Users::where('user_id', $up_uid)->value('rank_points');

                            if ($rank_points + $affiliate['config']['level_register_all'] <= $affiliate['config']['level_register_up']) {
                                $this->userAccountService->logAccountChange($up_uid, 0, 0, $affiliate['config']['level_register_all'], 0, sprintf(lang('user.register_affiliate'), $user_id, $username));
                            }
                        } else {
                            $this->userAccountService->logAccountChange($up_uid, 0, 0, $affiliate['config']['level_register_all'], 0, lang('user.register_affiliate'));
                        }
                    }

                    //设置推荐人
                    Users::where('user_id', $user_id)->update(['parent_id' => $up_uid, 'drp_parent_id' => $up_uid]);
                }
            }

            //定义other合法的变量数组
            $other_key_array = ['msn', 'qq', 'nick_name', 'office_phone', 'home_phone'];
            $update_data['reg_time'] = $this->timeRepository->getLocalStrtoTime($this->timeRepository->getLocalDate('Y-m-d H:i:s'));
            if ($other) {
                $date = $other;
                foreach ($date as $key => $val) {
                    //删除非法key值
                    if (!in_array($key, $other_key_array)) {
                        unset($date[$key]);
                    } else {
                        $date[$key] = htmlspecialchars(trim($val)); //防止用户输入javascript代码
                    }
                }
                $update_data = array_merge($update_data, $date);
            }

            $update_data['nick_name'] = isset($other['nick_name']) ? $other['nick_name'] : "nick" . rand(10000, 99999999);
            $update_data['user_picture'] = $other['user_picture'] ?? '';
            Users::where('user_id', $user_id)->update($update_data);

            $this->userCommonService->updateUserInfo($user_id, 'mobile');      // 更新用户信息
            $this->cartCommonService->recalculatePriceMobileCart($user_id);     // 重新计算购物车中的商品价格

            /*推荐分销商处理*/
            if (file_exists(MOBILE_DRP)) {
                $drp_config = app(DrpService::class)->drpConfig();
                $drp_affiliate = $drp_config['drp_affiliate_on']['value'] ?? 0;
                if (isset($drp_affiliate) && $drp_affiliate == 1) {
                    // 分销开关开启
                    $drp_parent_id = $other['drp_parent_id'] ?? 0;
                    $up_drpid = app(DrpService::class)->getDrpAffiliate($drp_parent_id);
                    if ($up_drpid > 0 && $user_id != $up_drpid) {
                        //设置推荐人
                        Users::where('user_id', $user_id)->update([
                            'parent_id' => $up_drpid,
                            'drp_parent_id' => $up_drpid,
                            'drp_bind_time' => $this->timeRepository->getGmTime(),//绑定时间，1.4.3客户关系有效期
                            'drp_bind_update_time' => $this->timeRepository->getGmTime()
                        ]);

                        $issend = $drp_config['issend']['value'] ?? 0;
                        if ($issend == 1 && file_exists(MOBILE_WECHAT)) {
                            //模板消息
                            $time = $this->timeRepository->getGmTime();
                            $pushData = [
                                'keyword1' => ['value' => $user_id, 'color' => '#173177'],
                                'keyword2' => ['value' => $this->timeRepository->getLocalDate('Y-m-d', $time), 'color' => '#173177'],
                                'remark' => ['value' => $update_data['nick_name'] . lang('user.new_user_join'), 'color' => '#173177']
                            ];
                            $url = dsc_url('/#/drp');
                            app(WechatService::class)->push_template('OPENTM202967310', $pushData, $url, $up_drpid);
                        }
                    }

                }
            }

            return true;
        }
    }

    /**
     * 返回错误
     * @return Error
     */
    public function getError()
    {
        return $this->error;
    }
}
