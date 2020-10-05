<?php

namespace App\Services\Wechat;

use App\Models\Users;
use App\Models\Wechat;
use App\Models\WechatUser;
use App\Services\User\ConnectUserService;

class WechatUserService
{
    protected $connectUserService;

    public function __construct(
        ConnectUserService $connectUserService
    )
    {
        $this->connectUserService = $connectUserService;
    }

    /**
     * 获取微信用户信息
     * @param int $user_id
     * @return array
     */
    public function get_wechat_user_info($user_id = 0)
    {
        //微信 wechat_users
        if (is_wechat_browser() && file_exists(MOBILE_WECHAT)) {
            $result = Users::where('user_id', $user_id);

            $result = $result->with([
                'getWechatUser' => function ($query) {
                    $query->select('ect_uid', 'headimgurl', 'nickname');
                }
            ]);
        } else {
            $result = Users::where('user_id', $user_id);
        }

        $result = $result ? $result->toArray() : [];

        if ($result) {
            if (isset($result['get_wechat_user'])) {
                $result = collect($result)->merge($result['get_wechat_user'])->except('get_wechat_user')->all();
            }
        }

        $user = [
            'nick_name' => !empty($result['nickname']) ? $result['nickname'] : (!empty($result['nick_name']) ? $result['nick_name'] : $result['user_name']),
            'user_picture' => !empty($result['headimgurl']) ? $result['headimgurl'] : ($result['user_picture'] ?? '')
        ];

        return $user;
    }

    /**
     * 更新微信用户信息
     * @param array $info
     * @param int $is_relation 是否关联
     * @return bool
     */
    public function update_wechat_user($info = [], $is_relation = 0)
    {
        if (empty($info)) {
            return false;
        }

        // 平台公众号id
        $wechat_id = Wechat::where(['status' => 1, 'default_wx' => 1])->value('id');
        if (!empty($wechat_id)) {
            // 组合数据
            $data = [
                'wechat_id' => $wechat_id,
                'openid' => $info['openid'] ?? '',
                'nickname' => !empty($info['nickname']) ? $info['nickname'] : '',
                'sex' => !empty($info['sex']) ? $info['sex'] : 0,
                'language' => !empty($info['language']) ? $info['language'] : '',
                'city' => !empty($info['city']) ? $info['city'] : '',
                'province' => !empty($info['province']) ? $info['province'] : '',
                'country' => !empty($info['country']) ? $info['country'] : '',
                'headimgurl' => !empty($info['headimgurl']) ? $info['headimgurl'] : '',
                'unionid' => $info['unionid'] ?? '',
                'ect_uid' => !empty($info['user_id']) ? $info['user_id'] : 0,
            ];
            // 帐号关联功能 不更新ect_uid
            if ($is_relation == 1) {
                unset($data['ect_uid']);
            }
            // unionid 微信开放平台唯一标识
            if (!empty($data['unionid'])) {
                // 查询
                $where = [
                    'unionid' => $info['unionid'],
                    'wechat_id' => $wechat_id
                ];
                $result = WechatUser::select('openid', 'unionid')->where($where)->count();
                if (empty($result)) {
                    // 保存推荐参数
                    if (file_exists(MOBILE_DRP)) {
                        $data['drp_parent_id'] = (isset($info['drp_parent_id']) && $info['drp_parent_id'] > 0) ? $info['drp_parent_id'] : 0;
                    }
                    $data['parent_id'] = (isset($info['parent_id']) && $info['parent_id'] > 0) ? $info['parent_id'] : 0;
                    // 新增记录
                    $data['from'] = $info['from'] ?? 1; // 微信粉丝注册来源 0 关注公众号, 1 授权登录注册, 2 微信扫码注册, 3 小程序注册
                    WechatUser::create($data);
                } else {
                    // 更新记录
                    WechatUser::where($where)->update($data);
                }

                return true;
            }
        }

        return false;
    }

    /**
     * 关联查询微信会员的会员ID 唯一条件unionid
     * @param string $openid
     * @return array
     */
    public function get_wechat_user_id($openid = '')
    {
        if (empty($openid)) {
            return [];
        }

        $unionid = WechatUser::where(['openid' => $openid])->orderBy('uid', 'DESC')->value('unionid');
        if (!empty($unionid)) {
            return $this->connectUserService->getConnectUserinfo($unionid, 'wechat');
        }
        return [];
    }

    /**
     * 查询openid
     * @param int $user_id
     * @return mixed
     */
    public function get_openid($user_id = 0)
    {
        if (empty($user_id)) {
            return '';
        }

        $openid = WechatUser::where('ect_uid', $user_id)->orderBy('uid', 'DESC')->value('openid');

        return $openid;
    }

    /**
     * 获得微信粉丝临时推荐人信息
     * @param $unionid
     * @return mixed
     */
    public function get_parent($unionid = '')
    {
        if (empty($unionid)) {
            return '';
        }

        $res = WechatUser::select('parent_id', 'drp_parent_id')
            ->where('unionid', $unionid)
            ->orderBy('uid', 'DESC')
            ->first();

        return $res;
    }

    /**
     * 用 openid 获取粉丝信息
     * @param string $openid
     * @return array
     */
    public function getWechatUserByOpenid($openid = '')
    {
        if (empty($openid)) {
            return [];
        }

        $res = WechatUser::where('openid', $openid)
            ->orderBy('uid', 'DESC')
            ->first();

        $res = $res ? $res->toArray() : [];

        return $res;
    }
}
