<?php

namespace App\Services\Wechat;

use App\Models\PicAlbum;
use App\Models\Users;
use App\Models\Wechat;
use App\Models\WechatCustomMessage;
use App\Models\WechatExtend;
use App\Models\WechatMarketing;
use App\Models\WechatMassHistory;
use App\Models\WechatMedia;
use App\Models\WechatMenu;
use App\Models\WechatPoint;
use App\Models\WechatPrize;
use App\Models\WechatQrcode;
use App\Models\WechatReply;
use App\Models\WechatRuleKeywords;
use App\Models\WechatShareCount;
use App\Models\WechatTemplate;
use App\Models\WechatUser;
use App\Models\WechatUserTag;
use App\Models\WechatUserTaglist;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\User\ConnectUserService;
use Illuminate\Support\Str;

/**
 * 微信通后台管理
 * Class WechatManageService
 * @package App\Services\Wechat
 */
class WechatManageService
{
    protected $baseRepository;
    protected $timeRepository;
    protected $config;
    protected $wechatService;
    protected $connectUserService;
    protected $wechatHelperService;
    protected $wechatMediaService;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        WechatService $wechatService,
        ConnectUserService $connectUserService,
        WechatHelperService $wechatHelperService,
        WechatMediaService $wechatMediaService,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->wechatService = $wechatService;
        $this->connectUserService = $connectUserService;
        $this->wechatHelperService = $wechatHelperService;
        $this->wechatMediaService = $wechatMediaService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 返回微信对象实例
     * @param array $wechat
     * @return \App\Libraries\Wechat|null
     */
    public function wechatInstance($wechat = [])
    {
        $weObj = null;

        if (!empty($wechat)) {
            // 获取配置信息
            $config = [
                'token' => $wechat['token'],
                'appid' => $wechat['appid'],
                'appsecret' => $wechat['appsecret']
            ];
            $weObj = new \App\Libraries\Wechat($config);
        }

        return $weObj;
    }

    /**
     * 默认平台微信配置信息
     * @return array
     */
    public function getWechatDefault()
    {
        return $this->wechatService->getWechatConfigDefault();
    }

    /**
     * 默认平台微信配置信息
     * @return array
     */
    public function getWechatConfigByRuId($ru_id)
    {
        return $this->wechatService->getWechatConfigByRuId($ru_id);
    }

    /**
     * 查询微信配置信息
     * @param int $wechat_id
     * @return array
     */
    public function getWechatInfo($wechat_id = 0)
    {
        return $this->wechatService->getWechatConfigById($wechat_id);
    }

    /**
     * 新增微信通
     * @param array $data
     * @return mixed
     */
    public function createWechat($data = [])
    {
        if (empty($data)) {
            return false;
        }

        $data['time'] = $this->timeRepository->getGmTime();

        return Wechat::create($data);
    }

    /**
     * 更新
     * @param int $wechat_id
     * @param array $data
     * @return bool
     */
    public function updateWechat($wechat_id = 0, $data = [])
    {
        if (empty($data)) {
            return false;
        }

        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'wechat');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = is_null($val) ? '' : $val;
        });

        $data['secret_key'] = md5($data['orgid'] . $data['appid']);// 生成自定义密钥

        $data = $this->transformWechatData($data);

        $res = Wechat::where('id', $wechat_id)->update($data);

        return $res;
    }

    /**
     * 处理微信保存数据
     * @param $data
     * @return array
     */
    protected function transformWechatData($data)
    {
        if (empty($data)) {
            return [];
        }

        if (stripos($data['appsecret'], '*') !== false) {
            unset($data['appsecret']);
        }

        return $data;
    }

    /**
     * 自定义菜单列表
     * @param int $wechat_id
     * @return array
     */
    public function wechatMenuList($wechat_id = 0)
    {
        $list = WechatMenu::where('wechat_id', $wechat_id)
            ->orderBy('sort', 'ASC')
            ->get();
        $list = $list ? $list->toArray() : [];

        $result = [];
        if ($list) {
            foreach ($list as $vo) {
                if ($vo['pid'] == 0) {
                    $vo['val'] = ($vo['type'] == 'click') ? $vo['key'] : $vo['url'];
                    $sub_button = [];
                    foreach ($list as $val) {
                        $val['val'] = ($val['type'] == 'click') ? $val['key'] : $val['url'];
                        if ($val['pid'] == $vo['id']) {
                            $sub_button[] = $val;
                        }
                    }
                    $vo['sub_button'] = $sub_button;
                    $result[] = $vo;
                }
            }
        }

        return $result;
    }

    /**
     * 顶级菜单列表
     * @param int $wechat_id
     * @param int $id
     * @return array
     */
    public function topMenuList($wechat_id = 0, $id = 0)
    {
        $model = WechatMenu::where('wechat_id', $wechat_id)
            ->where('pid', 0);

        if ($id > 0) {
            $model = $model->where('id', '<>', $id);
        }

        $top_menu = $model->get();

        return $top_menu ? $top_menu->toArray() : [];
    }

    /**
     * 菜单详情
     * @param int $id
     * @return array
     */
    public function wechatMenuInfo($id = 0)
    {
        if (empty($id)) {
            return [];
        }

        $info = WechatMenu::where('id', $id)->first();

        return $info ? $info->toArray() : [];
    }

    /**
     * 检查菜单数量
     * @param int $wechat_id
     * @param int $pid
     * @param int $id
     * @return mixed
     */
    public function checkMenuCount($wechat_id = 0, $pid = 0, $id = 0)
    {
        $model = WechatMenu::where('wechat_id', $wechat_id)
            ->where('pid', $pid);

        if ($id > 0) {
            $model = $model->where('id', '<>', $id);
        }

        $count = $model->count();

        return $count;
    }

    /**
     * 添加微信菜单
     * @param array $data
     * @return bool
     */
    public function createWechatMenu($data = [])
    {
        if (empty($data)) {
            return false;
        }

        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'wechat_menu');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = is_null($val) ? '' : $val;
        });

        // 添加
        return WechatMenu::create($data);
    }

    /**
     * 更新微信菜单
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateWechatMenu($id = 0, $data = [])
    {
        if (empty($data)) {
            return false;
        }

        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'wechat_menu');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = is_null($val) ? '' : $val;
        });

        // 更新
        return WechatMenu::where('id', $id)->update($data);
    }

    /**
     * 删除微信菜单
     * @param int $id
     * @return mixed
     */
    public function deleteWechatMenu($id = 0)
    {
        return WechatMenu::where('id', $id)->delete();
    }

    /**
     * 删除父级微信菜单
     * @param int $id
     * @return mixed
     */
    public function deleteWechatMenuPid($id = 0)
    {
        return WechatMenu::where('pid', $id)->delete();
    }

    /**
     * 所有显示的微信菜单
     * @param int $wechat_id
     * @return array
     */
    public function wechatMenuAll($wechat_id = 0)
    {
        $list = WechatMenu::where('wechat_id', $wechat_id)
            ->where('status', 1)
            ->orderBy('sort', 'ASC')
            ->get();

        return $list ? $list->toArray() : [];
    }

    /**
     * 转换微信菜单数组
     * @param array $list
     * @return array
     */
    public function transformWechatMenu($list = [])
    {
        if (empty($list)) {
            return [];
        }

        $data = [];
        if (is_array($list)) {
            foreach ($list as $val) {
                if ($val['pid'] == 0) {
                    $sub_button = [];
                    foreach ($list as $v) {
                        if ($v['pid'] == $val['id']) {
                            $sub_button[] = $v;
                        }
                    }
                    $val['sub_button'] = $sub_button;
                    $data[] = $val;
                }
            }
        }

        $menu_list = [];
        foreach ($data as $key => $val) {
            if (empty($val['sub_button'])) {
                $menu_list['button'][$key]['type'] = $val['type'];
                $menu_list['button'][$key]['name'] = $val['name'];
                if ('click' == $val['type']) {
                    $menu_list['button'][$key]['key'] = $val['key'];
                } elseif ('miniprogram' == $val['type']) {
                    $menu_list['button'][$key]['url'] = html_out($val['url']);
                    $menu_list['button'][$key]['pagepath'] = $val['pagepath'];
                    $menu_list['button'][$key]['appid'] = $val['appid'];
                } else {
                    $menu_list['button'][$key]['url'] = html_out($val['url']);
                }
            } else {
                $menu_list['button'][$key]['name'] = $val['name'];
                foreach ($val['sub_button'] as $k => $v) {
                    $menu_list['button'][$key]['sub_button'][$k]['type'] = $v['type'];
                    $menu_list['button'][$key]['sub_button'][$k]['name'] = $v['name'];
                    if ('click' == $v['type']) {
                        $menu_list['button'][$key]['sub_button'][$k]['key'] = $v['key'];
                    } elseif ('miniprogram' == $v['type']) {
                        $menu_list['button'][$key]['sub_button'][$k]['url'] = html_out($v['url']);
                        $menu_list['button'][$key]['sub_button'][$k]['pagepath'] = $v['pagepath'];
                        $menu_list['button'][$key]['sub_button'][$k]['appid'] = $v['appid'];
                    } else {
                        $menu_list['button'][$key]['sub_button'][$k]['url'] = html_out($v['url']);
                    }
                }
            }
        }

        return $menu_list;
    }

    /**
     * 微信粉丝列表
     * @param int $wechat_id
     * @param array $offset 分页
     * @param array $condition 条件
     * @return array
     */
    public function wechatUserList($wechat_id = 0, $offset = [], $condition = [])
    {
        $model = WechatUser::where('wechat_id', $wechat_id);

        if (!empty($condition)) {
            if (isset($condition['subscribe']) && $condition['subscribe'] == 1) {
                $model = $model->where('subscribe', 1);
            }
        }

        $total = $model->count();

        $list = $model->orderBy('subscribe_time', 'DESC');

        if (isset($offset['start']) && $offset['start'] > 0) {
            $list = $list->offset($offset['start']);
        }

        if (isset($offset['limit']) && $offset['limit'] > 0) {
            $list = $list->limit($offset['limit']);
        }

        $list = $this->baseRepository->getToArrayGet($list);

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 已关注微信粉丝列表
     * @param int $wechat_id
     * @param int $ru_id
     * @param array $offset
     * @return array
     */
    public function wechatUserListSubscribe($wechat_id = 0, $ru_id = 0, $offset = [])
    {
        $condition = [
            'subscribe' => 1
        ];
        $result = $this->wechatUserList($wechat_id, $offset, $condition);

        $total = $result['total'] ?? 0;
        $list = $result['list'] ?? [];

        if ($list) {
            foreach ($list as $key => $value) {
                $list[$key]['taglist'] = $this->getUserTagList($wechat_id, $value['openid']); // 粉丝所属标签
                $list[$key]['from'] = $this->wechatHelperService->get_wechat_user_from($value['from']);
                $list[$key]['subscribe_time_format'] = $this->timeRepository->getLocalDate($this->config['time_format'], $value['subscribe_time']);

                // 商家后台不显示
                if ($ru_id == 0) {
                    $users = $this->connectUserService->getConnectUserinfo($value['unionid'], 'wechat');
                    if (!empty($users)) {
                        $list[$key]['user_name'] = $users['user_name'];
                        $list[$key]['look_user_url'] = url('admin') . '/users.php?act=edit&id=' . $users['user_id']; // 查看商城会员
                    }
                }
            }
        }

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 搜索已关注微信粉丝列表
     * @param int $wechat_id
     * @param array $offset
     * @param string $keywords
     * @param int $group_id
     * @param int $tag_id
     * @return array
     */
    public function wechatUserListSearch($wechat_id = 0, $offset = [], $keywords = '', $group_id = 0, $tag_id = 0)
    {
        $model = WechatUser::from('wechat_user as u')->where(['u.wechat_id' => $wechat_id, 'u.subscribe' => 1]);

        if (!empty($keywords)) {
            $model = $model->where(function ($query) use ($keywords) {
                $query->where('u.nickname', 'like', '%' . $keywords . '%')
                    ->orWhere('u.city', 'like', '%' . $keywords . '%')
                    ->orWhere('u.country', 'like', '%' . $keywords . '%')
                    ->orWhere('u.province', 'like', '%' . $keywords . '%');
            });
            // 支持昵称 城市 国家 省份 关键字搜索
        }
        if (!empty($group_id)) {
            $model = $model->where(function ($query) use ($group_id) {
                $query->where('u.groupid', $group_id);
            });
        }
        if (!empty($tag_id)) {
            $model = $model->rightjoin('wechat_user_tag as t', 't.openid', '=', 'u.openid');
            $model = $model->where(function ($query) use ($tag_id) {
                $query->where('t.tag_id', $tag_id);
            });
        }

        $total = $model->count();

        $list = $model->orderBy('subscribe_time', 'DESC')
            ->offset($offset['start'])
            ->limit($offset['limit'])
            ->get();
        $list = $list ? $list->toArray() : [];

        foreach ($list as $key => $value) {
            $list[$key]['taglist'] = $this->getUserTagList($wechat_id, $value['openid']); // 粉丝所属标签
            $list[$key]['from'] = $this->wechatHelperService->get_wechat_user_from($value['from']);
            $list[$key]['subscribe_time_format'] = $this->timeRepository->getLocalDate($this->config['time_format'], $value['subscribe_time']);

            $users = $this->connectUserService->getConnectUserinfo($value['unionid'], 'wechat');
            if (!empty($users)) {
                $list[$key]['user_name'] = $users['user_name'];
                $list[$key]['look_user_url'] = url('admin') . '/users.php?act=edit&id=' . $users['user_id']; // 查看商城会员
            }
        }

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 所有微信粉丝列表
     * @param int $wechat_id
     * @param array $offset
     * @return array
     */
    public function wechatUserListAll($wechat_id = 0, $offset = [])
    {
        $result = $this->wechatUserList($wechat_id, $offset);

        $total = $result['total'] ?? 0;
        $list = $result['list'] ?? [];

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 所有标签列表
     * @param int $wechat_id
     * @param string $sort
     * @param string $order
     * @return array
     */
    public function getWechatTagList($wechat_id = 0, $sort = 'sort', $order = 'ASC')
    {
        $model = WechatUserTaglist::where('wechat_id', $wechat_id);

        $tag_list = $model->orderBy($sort, $order)
            ->orderBy('tag_id', 'ASC')
            ->get();

        return $tag_list ? $tag_list->toArray() : [];
    }

    /**
     * 获得关注粉丝的标签列表
     * @param int $wechat_id
     * @param string $openid
     * @return array
     */
    protected function getUserTagList($wechat_id = 0, $openid = '')
    {
        $tags = WechatUserTaglist::from('wechat_user_taglist as tl')->select('tl.tag_id', 'tl.name')
            ->leftJoin('wechat_user_tag as t', 't.tag_id', '=', 'tl.tag_id')
            ->leftJoin('wechat_user as u', 'u.openid', '=', 't.openid')
            ->where(['tl.wechat_id' => $wechat_id, 'u.subscribe' => 1])
            ->where('u.openid', $openid)
            ->get();

        return $tags ? $tags->toArray() : [];
    }

    /**
     * 删除公众号下所有粉丝标签列表
     * @param int $wechat_id
     * @return mixed
     */
    public function deleteWechatUserTagList($wechat_id = 0)
    {
        return WechatUserTaglist::where('wechat_id', $wechat_id)->delete();
    }

    /**
     * 删除一个粉丝标签列表
     * @param int $wechat_id
     * @param int $tag_id
     * @return mixed
     */
    public function deleteWechatUserTagListByTagid($wechat_id = 0, $tag_id = 0)
    {
        if (empty($tag_id)) {
            return false;
        }
        return WechatUserTaglist::where('wechat_id', $wechat_id)->where('tag_id', $tag_id)->delete();
    }

    /**
     * 创建用户标签
     * @param array $data
     * @return bool
     */
    public function createWechatUserTagList($data = [])
    {
        if (empty($data)) {
            return false;
        }
        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'wechat_user_taglist');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = is_null($val) ? '' : $val;
        });

        return WechatUserTaglist::create($data);
    }

    /**
     * 同步本地标签列表
     * @param int $wechat_id
     * @param array $list
     * @return bool
     */
    public function syncWechatUserTagList($wechat_id = 0, $list = [])
    {
        // 删除本地标签列表
        $this->deleteWechatUserTagList($wechat_id);
        // 更新微信标签列表到本地
        if (isset($list['tags']) && $list['tags']) {
            foreach ($list['tags'] as $key => $val) {
                if (isset($val['id']) && !empty($val['id'])) {
                    $data['wechat_id'] = $wechat_id;
                    $data['tag_id'] = $val['id'];
                    $data['name'] = $val['name'];
                    $data['count'] = $val['count'];
                    $this->createWechatUserTagList($data);
                }
            }

            return true;
        }

        return false;
    }

    /**
     * 更新微信标签列表
     * @param int $wechat_id
     * @param int $id 标签id
     * @param array $data
     * @return bool
     */
    public function updateWechatUserTagList($wechat_id = 0, $id = 0, $data = [])
    {
        if (empty($data)) {
            return false;
        }

        // 更新
        $model = WechatUserTaglist::where('wechat_id', $wechat_id)
            ->where('id', $id);

        return $model->update($data);
    }

    /**
     * 微信标签列表信息
     * @param int $wechat_id
     * @param int $id
     * @return array
     */
    public function getWechatUserTaglistInfo($wechat_id = 0, $id = 0)
    {
        $taginfo = WechatUserTaglist::where('wechat_id', $wechat_id)
            ->where('id', $id)
            ->first();

        return $taginfo ? $taginfo->toArray() : [];
    }

    /**
     * 删除粉丝本地标签 by openid
     * @param int $wechat_id
     * @param string $openid
     * @return bool
     */
    public function deleteWechatUserTagByopenid($wechat_id = 0, $openid = '')
    {
        if (empty($openid)) {
            return false;
        }

        return WechatUserTag::where('wechat_id', $wechat_id)->where('openid', $openid)->delete();
    }

    /**
     * 删除粉丝本地标签 by tag_id
     * @param int $wechat_id
     * @param int $tag_id
     * @return mixed
     */
    public function deleteWechatUserTagByTagid($wechat_id = 0, $tag_id = 0)
    {
        if (empty($tag_id)) {
            return false;
        }
        return WechatUserTag::where('wechat_id', $wechat_id)->where('tag_id', $tag_id)->delete();
    }

    /**
     * 删除粉丝本地标签 by tag_id + openid
     * @param int $wechat_id
     * @param int $tag_id
     * @param string $openid
     * @return mixed
     */
    public function deleteWechatUserTag($wechat_id = 0, $tag_id = 0, $openid = '')
    {
        if (empty($tag_id) || empty($openid)) {
            return false;
        }
        return WechatUserTag::where('wechat_id', $wechat_id)->where('tag_id', $tag_id)->where('openid', $openid)->delete();
    }

    /**
     * 插入粉丝本地标签
     * @param array $data
     * @return bool
     */
    public function createWechatUserTag($data = [])
    {
        if (empty($data)) {
            return false;
        }

        // 不能重复加入相同标签
        $tag_num = WechatUserTag::where($data)->count();
        if ($tag_num == 0) {
            return WechatUserTag::create($data);
        }

        return false;
    }

    /**
     * 已关注粉丝标签数量
     * @param int $wechat_id
     * @param string $openid
     * @return int
     */
    public function getWechatUserTagNum($wechat_id = 0, $openid = '')
    {
        if (empty($openid)) {
            return 0;
        }

        $model = WechatUserTag::where('wechat_id', $wechat_id);

        $model = $model->with([
            'wechatUser' => function ($query) use ($openid) {
                $query->where('subscribe', 1)->where('openid', $openid);
            }
        ]);

        return $model->count('openid');
    }

    /**
     * 插入微信粉丝
     * @param array $data
     * @return bool
     */
    public function createWechatUser($data = [])
    {
        if (empty($data)) {
            return false;
        }

        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'wechat_user');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = is_null($val) ? '' : $val;
        });

        // unionid 必须
        if (!empty($data['unionid'])) {
            $count = WechatUser::where('wechat_id', $data['wechat_id'])->where('unionid', $data['unionid'])->count();
            if (empty($count)) {
                return WechatUser::create($data);
            } else {
                // 兼容老公众号数据 通过unionid更新粉丝信息
                return $this->updateWechatUserByUnionid($data['wechat_id'], $data['unionid'], $data);
            }
        }

        return false;
    }

    /**
     * 更新微信粉丝 by unionid 唯一
     * @param int $wechat_id
     * @param string $unionid
     * @param array $data
     * @return bool
     */
    protected function updateWechatUserByUnionid($wechat_id = 0, $unionid = '', $data = [])
    {
        if (empty($data) || empty($unionid)) {
            return false;
        }

        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'wechat_user');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = is_null($val) ? '' : $val;
        });

        // 更新
        $model = WechatUser::where('wechat_id', $wechat_id)->where('unionid', $unionid);
        if ($model) {
            return $model->update($data);
        }
        return false;
    }

    /**
     * 更新微信粉丝
     * @param int $wechat_id
     * @param string $openid
     * @param array $data
     * @return bool
     */
    public function updateWechatUser($wechat_id = 0, $openid = '', $data = [])
    {
        if (empty($data)) {
            return false;
        }

        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'wechat_user');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = is_null($val) ? '' : $val;
        });

        // 更新
        $model = WechatUser::where('wechat_id', $wechat_id);

        if (!empty($openid)) {
            $model = $model->where('openid', $openid);
        }

        return $model->update($data);
    }

    /**
     * 公众号本地所有粉丝数量
     * @param int $wechat_id
     * @return mixed
     */
    public function wechatUserCount($wechat_id = 0)
    {
        $total = WechatUser::where('wechat_id', $wechat_id)->count();

        return $total;
    }

    /**
     * 微信粉丝信息
     * @param int $wechat_id
     * @param array $condition
     * @return mixed
     */
    public function wechatUserInfo($wechat_id = 0, $condition = [])
    {
        $model = WechatUser::where('wechat_id', $wechat_id);

        if (!empty($condition)) {
            if (isset($condition['openid']) && !empty($condition['openid'])) {
                $model = $model->where('openid', $condition['openid']);
            } elseif (isset($condition['uid']) && !empty($condition['uid'])) {
                $model = $model->where('uid', $condition['uid']);
            }
        }

        $info = $model->first();

        return $info ? $info->toArray() : [];
    }

    /**
     * 微信消息列表
     * @param int $wechat_id
     * @param array $offset
     * @param array $condition
     * @return array
     */
    public function wechatCustomMessageList($wechat_id = 0, $offset = [], $condition = [])
    {
        $model = WechatCustomMessage::where('wechat_id', $wechat_id);

        if (!empty($condition)) {
            $model = $model->where($condition);
        }

        $total = $model->count();

        if (!empty($offset)) {
            $model = $model->offset($offset['start'])
                ->limit($offset['limit']);
        }

        $list = $model->orderBy('send_time', 'DESC')
            ->orderBy('id', 'DESC')
            ->get();

        $list = $list ? $list->toArray() : [];

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 格式化倒序显示微信消息列表
     * @param int $wechat_id
     * @param array $offset
     * @param array $condition
     * @param array $info
     * @return array
     */
    public function transformWechatCustomMessageList($wechat_id = 0, $offset = [], $condition = [], $info = [])
    {
        $result = $this->wechatCustomMessageList($wechat_id, $offset, $condition);

        $list = $result['list'] ?? [];
        $total = $result['total'] ?? 0;

        if ($list) {
            $list = array_reverse($list); // 倒序显示
            foreach ($list as $key => $value) {
                $list[$key]['send_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $value['send_time']);
                $list[$key]['headimgurl'] = $info['headimgurl'];
                $list[$key]['wechat_headimgurl'] = asset('assets/mobile/img/shop_app_icon.png');
                $list[$key]['nickname'] = ($value['is_wechat_admin']) == 1 ? lang('wechat.administrator') : $info['nickname'];
            }
        }

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 插入微信消息
     * @param array $data
     * @return bool
     */
    public function createWechatCustomMessage($data = [])
    {
        if (empty($data)) {
            return false;
        }

        $data['send_time'] = $this->timeRepository->getGmTime();

        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'wechat_custom_message');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = is_null($val) ? '' : $val;
        });

        return WechatCustomMessage::create($data);
    }

    /**
     * 渠道二维码
     * @param int $wechat_id
     * @param array $offset
     * @return array
     */
    public function channelQrcodeList($wechat_id = 0, $offset = [])
    {
        $model = WechatQrcode::where('wechat_id', $wechat_id)->where('username', '');

        $total = $model->count();

        if (!empty($offset)) {
            $model = $model->offset($offset['start'])
                ->limit($offset['limit']);
        }

        $list = $model->orderBy('sort', 'ASC')
            ->orderBy('id', 'DESC')
            ->get();
        $list = $list ? $list->toArray() : [];

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 添加二维码
     * @param array $data
     * @return bool
     */
    public function createWechatQrcode($data = [])
    {
        if (empty($data)) {
            return false;
        }

        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'wechat_qrcode');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = is_null($val) ? '' : $val;
        });

        return WechatQrcode::create($data);
    }

    /**
     * 更新二维码
     * @param int $wechat_id
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateWechatQrcode($wechat_id = 0, $id = 0, $data = [])
    {
        if (empty($data)) {
            return false;
        }

        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'wechat_qrcode');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = is_null($val) ? '' : $val;
        });

        // 更新
        $model = WechatQrcode::where('wechat_id', $wechat_id);

        if (!empty($id)) {
            $model = $model->where('id', $id);

            return $model->update($data);
        }

        return false;
    }

    /**
     * 删除二维码
     * @param int $wechat_id
     * @param int $id
     * @return bool
     */
    public function deleteWechatQrcode($wechat_id = 0, $id = 0)
    {
        if (empty($id)) {
            return false;
        }

        return WechatQrcode::where('wechat_id', $wechat_id)->where('id', $id)->delete();
    }

    /**
     * 二维码数量
     * @param int $wechat_id
     * @param int $scene_id
     * @return mixed
     */
    public function getWechatQrcodeCount($wechat_id = 0, $scene_id = 0)
    {
        $num = WechatQrcode::where('wechat_id', $wechat_id)->where('scene_id', $scene_id)
            ->count();

        return $num;
    }

    /**
     * 二维码状态
     * @param int $wechat_id
     * @param int $id
     * @return mixed
     */
    public function getWechatQrcodeStatus($wechat_id = 0, $id = 0)
    {
        $status = WechatQrcode::where('wechat_id', $wechat_id)->where('id', $id)
            ->value('status');

        return $status;
    }

    /**
     * 二维码信息
     * @param int $wechat_id
     * @param int $id
     * @return array
     */
    public function getWechatQrcodeInfo($wechat_id = 0, $id = 0)
    {
        $info = WechatQrcode::where('wechat_id', $wechat_id)->where('id', $id)
            ->first();

        return $info ? $info->toArray() : [];
    }

    /**
     * 推荐二维码
     * @param int $wechat_id
     * @param array $offset
     * @return array
     */
    public function shareQrcodeList($wechat_id = 0, $offset = [])
    {
        $model = WechatQrcode::where('wechat_id', $wechat_id)->where('username', '<>', '');

        $model = $model->with([
            'affiliateLog' => function ($query) {
                $query->select('user_id')->selectRaw("SUM(money) AS share_account");
            }
        ]);

        $total = $model->count();

        if (!empty($offset)) {
            $model = $model->offset($offset['start'])
                ->limit($offset['limit']);
        }

        $list = $model->orderBy('sort', 'ASC')
            ->orderBy('id', 'DESC')
            ->get();
        $list = $list ? $list->toArray() : [];

        if (!empty($list)) {
            foreach ($list as $key => $val) {
                $list[$key]['share_account'] = isset($val['affiliate_log']['share_account']) ? $val['affiliate_log']['share_account'] : 0;
                $list[$key]['status'] = $this->wechatHelperService->return_qrcode_status($val['id'], $val['scene_id']);
            }
        }

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 通过用户名查 user_id
     * @param string $user_name
     * @return mixed
     */
    public function getUserIdByName($user_name = '')
    {
        $user_id = Users::where('user_name', $user_name)->value('user_id');

        return $user_id;
    }

    /**
     * 微信素材信息
     * @param int $media_id
     * @return array
     */
    public function getWechatMediaInfo($media_id = 0)
    {
        if (empty($media_id)) {
            return [];
        }

        return $this->wechatMediaService->wechatMediaInfo($media_id);
    }

    /**
     * 微信多图文素材信息
     * @param array $article_ids
     * @return array
     */
    public function getWechatMediaInfoByArticle($article_ids = [])
    {
        if (empty($article_ids)) {
            return [];
        }

        $article = $this->wechatMediaService->wechatMediaInfoByArticle($article_ids);

        if (!empty($article)) {
            foreach ($article as $key => $val) {
                $article[$key]['file'] = empty($val['file']) ? '' : $this->wechatHelperService->get_wechat_image_path($val['file']);
                $article[$key]['add_time'] = $this->timeRepository->getLocalDate('Y年m月d日', $val['add_time']);
                $article[$key]['content'] = empty($val['digest']) ? (empty($val['content']) ? '' : $this->dscRepository->subStr(strip_tags(html_out($val['content'])), 50)) : $val['digest'];
            }
        }

        return $article;
    }

    /**
     * 微信素材列表
     * @param int $wechat_id
     * @param array $offset
     * @param string $type
     * @return array
     */
    public function getWechatMediaList($wechat_id = 0, $offset = [], $type = '')
    {
        $result = $this->wechatMediaService->wechatMediaList($wechat_id, $offset, $type);

        $total = $result['total'] ?? 0;
        $list = $result['list'] ?? [];

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 微信单图文素材列表
     * @param int $wechat_id
     * @param array $offset
     * @return array
     */
    public function getWechatMediaArticle($wechat_id = 0, $offset = [])
    {
        $result = $this->wechatMediaService->wechatMediaArticle($wechat_id, $offset, 'news');

        $total = $result['total'] ?? 0;
        $list = $result['list'] ?? [];

        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['file'] = empty($v['file']) ? '' : $this->wechatHelperService->get_wechat_image_path($v['file']);
                $list[$k]['content'] = empty($v['digest']) ? (empty($v['content']) ? '' : $this->dscRepository->subStr(strip_tags(html_out($v['content'])), 50)) : $v['digest'];
            }
        }

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 微信多图文素材列表
     * @param int $wechat_id
     * @param array $offset
     * @return array
     */
    public function getWechatMediaArticleList($wechat_id = 0, $offset = [])
    {
        $result = $this->wechatMediaService->wechatMediaArticleList($wechat_id, $offset, 'news');

        $total = $result['total'] ?? 0;
        $list = $result['list'] ?? [];

        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['file'] = empty($v['file']) ? '' : $this->wechatHelperService->get_wechat_image_path($v['file']);
                $list[$k]['content'] = empty($v['digest']) ? (empty($v['content']) ? '' : $this->dscRepository->subStr(strip_tags(html_out($v['content'])), 50)) : $v['digest'];
            }
        }

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 微信图片素材列表
     * @param int $wechat_id
     * @param array $offset
     * @return array
     */
    public function getWechatMediaImageList($wechat_id = 0, $offset = [])
    {
        $result = $this->wechatMediaService->wechatMediaList($wechat_id, $offset, 'image');

        $total = $result['total'] ?? 0;
        $list = $result['list'] ?? [];

        if (!empty($list)) {
            foreach ($list as $key => $val) {
                if ($val['size'] > (1024 * 1024)) {
                    $list[$key]['size'] = round(($val['size'] / (1024 * 1024)), 1) . 'MB';
                } else {
                    $list[$key]['size'] = round(($val['size'] / 1024), 1) . 'KB';
                }
                $list[$key]['file'] = empty($val['file']) ? '' : $this->wechatHelperService->get_wechat_image_path($val['file']);
            }
        }

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 微信语音素材列表
     * @param int $wechat_id
     * @param array $offset
     * @return array
     */
    public function getWechatMediaVoiceList($wechat_id = 0, $offset = [])
    {
        $result = $this->wechatMediaService->wechatMediaList($wechat_id, $offset, 'voice');

        $total = $result['total'] ?? 0;
        $list = $result['list'] ?? [];

        if (!empty($list)) {
            foreach ($list as $key => $val) {
                if ($val['size'] > (1024 * 1024)) {
                    $list[$key]['size'] = round(($val['size'] / (1024 * 1024)), 1) . 'MB';
                } else {
                    $list[$key]['size'] = round(($val['size'] / 1024), 1) . 'KB';
                }
            }
        }

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 微信视频素材列表
     * @param int $wechat_id
     * @param array $offset
     * @return array
     */
    public function getWechatMediaVideoList($wechat_id = 0, $offset = [])
    {
        $result = $this->wechatMediaService->wechatMediaList($wechat_id, $offset, 'video');

        $total = $result['total'] ?? 0;
        $list = $result['list'] ?? [];

        if (!empty($list)) {
            foreach ($list as $key => $val) {
                if ($val['size'] > (1024 * 1024)) {
                    $list[$key]['size'] = round(($val['size'] / (1024 * 1024)), 1) . 'MB';
                } else {
                    $list[$key]['size'] = round(($val['size'] / 1024), 1) . 'KB';
                }
            }
        }

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 处理提交的素材数据
     * @param int $id
     * @param array $data
     * @return array
     */
    public function transformWechatMediaData($id = 0, $data = [])
    {
        if (empty($data)) {
            return [];
        }

        // 不保存默认空图片
        if (strpos($data['file'], 'no_image') !== false) {
            unset($data['file']);
        }

        // 自动截取内容50个字符做为描述
        if (empty($data['digest']) && !empty($data['content'])) {
            $data['digest'] = $this->dscRepository->subStr(strip_tags(html_out($data['content'])), 50);
        }

        $time = $this->timeRepository->getGmTime();

        // 编辑
        if (!empty($id)) {
            $data['edit_time'] = $time;
            // 重置活动链接
            $media = $this->getWechatMediaInfo($id);
            if (empty($data['link']) && !empty($media['command'])) {
                $data['link'] = route('wechat/plugin_show', ['name' => $media['command']]);
            }
        } else {
            // 添加
            $data['add_time'] = $time;
        }

        return $data;
    }

    /**
     * 更新微信素材
     * @param int $wechat_id
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateWechatMedia($wechat_id = 0, $id = 0, $data = [])
    {
        if (empty($data)) {
            return false;
        }

        $data['edit_time'] = $this->timeRepository->getGmTime();

        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'wechat_media');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = is_null($val) ? '' : $val;
        });

        return $this->wechatMediaService->updateWechatMedia($wechat_id, $id, $data);
    }

    /**
     * 添加微信素材
     * @param array $data
     * @return bool
     */
    public function createWechatMedia($data = [])
    {
        if (empty($data)) {
            return false;
        }

        $data['add_time'] = $this->timeRepository->getGmTime();

        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'wechat_media');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = is_null($val) ? '' : $val;
        });

        return $this->wechatMediaService->createWechatMedia($data);
    }

    /**
     * 删除素材 返回原图片
     * @param int $wechat_id
     * @param int $id
     * @return array
     */
    public function deleteWechatMedia($wechat_id = 0, $id = 0)
    {
        $result = $this->wechatMediaService->deleteWechatMedia($wechat_id, $id);

        return $result;
    }

    /**
     * 图片库列表
     * @param int $album_id
     * @param array $offset
     * @return array
     */
    public function picAlbumList($album_id = 0, $offset = [])
    {
        $model = PicAlbum::where('album_id', $album_id);

        $total = $model->count();

        if (!empty($offset)) {
            $model = $model->offset($offset['start'])
                ->limit($offset['limit']);
        }

        $list = $model->orderBy('pic_id', 'DESC')
            ->get();
        $list = $list ? $list->toArray() : [];

        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $list[$key]['pic_file'] = empty($value['pic_file']) ? '' : $this->wechatHelperService->get_wechat_image_path($value['pic_file']);
            }
        }

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 微信群发消息列表
     * @param int $wechat_id
     * @param array $offset
     * @param string $type
     * @return array
     */
    public function wechatMassHistoryList($wechat_id = 0, $offset = [], $type = '')
    {
        $model = WechatMassHistory::where('wechat_id', $wechat_id);

        if (!empty($type)) {
            $model = $model->where('type', $type);
        }

        $total = $model->count();

        if (!empty($offset)) {
            $model = $model->offset($offset['start'])
                ->limit($offset['limit']);
        }

        $list = $model->orderBy('send_time', 'DESC')
            ->get();

        $list = $list ? $list->toArray() : [];

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 格式化群发消息列表
     * @param array $list
     * @return array
     */
    public function transformWechatMassHistoryList($list = [])
    {
        if (empty($list)) {
            return [];
        }

        if ($list) {
            foreach ($list as $key => $val) {
                $media = $this->getWechatMediaInfo($val['media_id']);

                if (!empty($media['article_id'])) {
                    // 多图文
                    $artids = explode(',', $media['article_id']);
                    $artinfo = $this->getWechatMediaInfo($artids['0']);
                } else {
                    $artinfo = $media;
                }
                if ('news' == $val['type']) {
                    $artinfo['type'] = lang('wechat.artinfo_type');
                }
                $artinfo['file'] = empty($artinfo['file']) ? '' : $this->wechatHelperService->get_wechat_image_path($artinfo['file']);
                $artinfo['content'] = empty($artinfo['digest']) ? (empty($artinfo['content']) ? '' : $this->dscRepository->subStr(strip_tags(html_out($artinfo['content'])), 50)) : $artinfo['digest'];

                $list[$key]['artinfo'] = $artinfo;
            }
        }

        return $list;
    }

    /**
     * 群发消息信息
     * @param int $wechat_id
     * @param int $id
     * @return array
     */
    public function getWechatMassHistoryInfo($wechat_id = 0, $id = 0)
    {
        if (empty($id)) {
            return [];
        }

        $result = WechatMassHistory::where('wechat_id', $wechat_id)->where('id', $id)->first();

        return $result ? $result->toArray() : [];
    }

    /**
     * 最新群发消息信息
     * @param int $wechat_id
     * @return array
     */
    public function getWechatMassHistoryInfoLast($wechat_id = 0)
    {
        $result = WechatMassHistory::where('wechat_id', $wechat_id)->orderBy('id', 'DESC')->first();

        return $result ? $result->toArray() : [];
    }

    /**
     * 添加微信群发消息
     * @param array $data
     * @return bool
     */
    public function createWechatMassHistory($data = [])
    {
        if (empty($data)) {
            return false;
        }

        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'wechat_mass_history');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = is_null($val) ? '' : $val;
        });

        return WechatMassHistory::create($data);
    }

    /**
     * 查询微信自动回复
     * @param int $wechat_id
     * @param string $type
     * @return array
     */
    public function getWechatReply($wechat_id = 0, $type = '')
    {
        $result = $this->wechatMediaService->wechatReply($wechat_id, $type);

        return $result;
    }

    /**
     * 更新微信自动回复
     * @param int $wechat_id
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateWechatReply($wechat_id = 0, $id = 0, $data = [])
    {
        if (empty($data)) {
            return false;
        }

        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'wechat_reply');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = is_null($val) ? '' : $val;
        });

        return $this->wechatMediaService->updateWechatReply($wechat_id, $id, $data);
    }

    /**
     * 添加微信自动回复
     * @param array $data
     * @return bool
     */
    public function createWechatReply($data = [])
    {
        if (empty($data)) {
            return false;
        }

        $data['add_time'] = $this->timeRepository->getGmTime();

        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'wechat_reply');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = is_null($val) ? '' : $val;
        });

        return $this->wechatMediaService->createWechatReply($data);
    }

    /**
     * 删除自动回复
     * @param int $wechat_id
     * @param int $id
     * @return bool
     */
    public function deleteWechatReply($wechat_id = 0, $id = 0)
    {
        if (empty($id)) {
            return false;
        }

        return WechatReply::where('wechat_id', $wechat_id)->where('id', $id)->delete();
    }

    /**
     * 删除关键词规则表
     * @param int $wechat_id
     * @param int $id
     * @return bool
     */
    public function deleteWechatRuleKeywords($wechat_id = 0, $id = 0)
    {
        if (empty($id)) {
            return false;
        }

        return WechatRuleKeywords::where('wechat_id', $wechat_id)->where('rid', $id)->delete();
    }

    /**
     * 删除微信关键词自动回复
     * @param int $wechat_id
     * @param int $id
     * @return bool
     */
    public function deleteWechatReplyKeywords($wechat_id = 0, $id = 0)
    {
        if (empty($id)) {
            return false;
        }

        // 删除主表
        $res = $this->deleteWechatReply($wechat_id, $id);
        if ($res) {
            // 删除关键词从表
            $res = $this->deleteWechatRuleKeywords($wechat_id, $id);
        }

        return $res;
    }

    /**
     * 微信关键词自动回复
     * @param int $wechat_id
     * @param array $offset
     * @return array
     */
    public function wechatReplyKeywordsList($wechat_id = 0, $offset = [])
    {
        $list = $this->wechatMediaService->wechatReplyKeywordsList($wechat_id, $offset);

        if (!empty($list)) {
            foreach ($list as $key => $val) {
                // 内容不是文本
                if (!empty($val['media_id'])) {
                    $media = $this->getWechatMediaInfo($val['media_id']);

                    $media['file'] = empty($media['file']) ? '' : $this->wechatHelperService->get_wechat_image_path($media['file']);
                    $media['content'] = empty($media['digest']) ? (empty($media['content']) ? '' : $this->dscRepository->subStr(strip_tags(html_out($media['content'])), 50)) : $media['digest'];
                    if (!empty($media['article_id'])) {
                        $artids = explode(',', $media['article_id']);
                        foreach ($artids as $k => $v) {
                            $medias = $this->getWechatMediaInfo($v);
                            $list[$key]['medias'][] = $medias;
                            $list[$key]['medias'][$k]['file'] = empty($medias['file']) ? '' : $this->wechatHelperService->get_wechat_image_path($medias['file']);
                        }
                    } else {
                        $list[$key]['media'] = $media;
                    }
                }

                $keywords = isset($val['wechat_rule_keywords_list']) && !empty($val['wechat_rule_keywords_list']) ? $val['wechat_rule_keywords_list'] : [];
                $list[$key]['rule_keywords'] = $keywords;
                // 编辑关键词时显示
                if (!empty($keywords)) {
                    $rule_keywords = [];
                    foreach ($keywords as $k => $v) {
                        $rule_keywords[] = $v['rule_keywords'];
                    }
                    $rule_keywords = implode(',', $rule_keywords);
                    $list[$key]['rule_keywords_string'] = $rule_keywords;
                }
            }
        }

        return $list;
    }


    /**
     * 微信模板消息列表
     * @param int $wechat_id
     * @return array
     */
    public function getWechatTemplateList($wechat_id = 0)
    {
        $list = WechatTemplate::where('wechat_id', $wechat_id)
            ->orderBy('id', 'DESC')
            ->get();
        $list = $list ? $list->toArray() : [];

        $wechatCodeArr = [
            'OPENTM415293129', 'OPENTM204987032', 'OPENTM202243318', 'OPENTM401833445'
        ];
        $drpCodeArr = [
            'OPENTM207126233', 'OPENTM409909643', 'OPENTM202967310'
        ];
        $teamCodeArr = [
            'OPENTM407307456', 'OPENTM400048581', 'OPENTM407456411', 'OPENTM400940587'
        ];
        $barginCodeArr = [
            'OPENTM410292733'
        ];
        $wechat_file = app_path('Modules/Admin/Controllers/WechatController.php');
        $drp_file = app_path('Modules/Admin/Controllers/DrpController.php');
        $team_file = app_path('Modules/Admin/Controllers/TeamController.php');
        $bargain_file = app_path('Modules/Admin/Controllers/BargainController.php');

        if ($list) {
            foreach ($list as $key => $val) {
                $list[$key]['add_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $val['add_time']);

                if (!file_exists($wechat_file) && in_array($val['code'], $wechatCodeArr)) {
                    unset($list[$key]);
                }
                if (!file_exists($drp_file) && in_array($val['code'], $drpCodeArr)) {
                    unset($list[$key]);
                }
                if (!file_exists($team_file) && in_array($val['code'], $teamCodeArr)) {
                    unset($list[$key]);
                }
                if (!file_exists($bargain_file) && in_array($val['code'], $barginCodeArr)) {
                    unset($list[$key]);
                }
            }
        }

        return $list;
    }

    /**
     * 查询模板消息信息
     * @param int $wechat_id
     * @param int $id
     * @return array
     */
    public function getWechatTemplateInfo($wechat_id = 0, $id = 0)
    {
        if (empty($id)) {
            return [];
        }

        $template = WechatTemplate::where('wechat_id', $wechat_id)->where('id', $id)->first();

        return $template ? $template->toArray() : [];
    }

    /**
     * 更新模板消息信息
     * @param int $wechat_id
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateWechatTemplate($wechat_id = 0, $id = 0, $data = [])
    {
        if (empty($data)) {
            return false;
        }

        $data['add_time'] = $this->timeRepository->getGmTime();

        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'wechat_template');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = is_null($val) ? '' : $val;
        });

        return WechatTemplate::where('wechat_id', $wechat_id)->where('id', $id)->update($data);
    }

    /**
     * 微信分享统计列表
     * @param int $wechat_id
     * @param array $offset
     * @param string $keywords
     * @return array
     */
    public function getWechatShareCountList($wechat_id = 0, $offset = [], $keywords = '')
    {
        $model = WechatShareCount::from('wechat_share_count as sh')->where('sh.wechat_id', $wechat_id);

        if (!empty($keywords)) {
            $model = $model->where(function ($query) use ($keywords) {
                $query->where('u.nickname', 'like', '%' . $keywords . '%');
            });
            // 支持昵称搜索
        }

        $model = $model->leftJoin('wechat_user as u', 'u.openid', '=', 'sh.openid');

        $total = $model->count();

        $list = $model->select('sh.*', 'u.nickname')->orderBy('sh.share_time', 'DESC')
            ->offset($offset['start'])
            ->limit($offset['limit'])
            ->get();

        $list = $list ? $list->toArray() : [];

        foreach ($list as $key => $val) {
            $list[$key]['share_type'] = isset($val['share_type']) ? $this->wechatHelperService->get_share_type($val['share_type']) : '';
            $list[$key]['share_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $val['share_time']);
        }

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 删除分享统计
     * @param int $wechat_id
     * @param int $id
     * @return bool
     */
    public function deleteWechatShareCount($wechat_id = 0, $id = 0)
    {
        if (empty($id)) {
            return false;
        }

        return WechatShareCount::where('wechat_id', $wechat_id)->where('id', $id)->delete();
    }

    /**
     * 功能扩展列表
     * @param int $wechat_id
     * @param int $wechat_type
     * @param int $ru_id
     * @return array
     */
    public function wechatExtendList($wechat_id = 0, $wechat_type = 0, $ru_id = 0)
    {
        $extends = WechatExtend::where('wechat_id', $wechat_id)
            ->where('type', 'function')
            ->where('enable', 1)
            ->orderBy('id', 'DESC')
            ->get();
        $extends = $extends ? $extends->toArray() : [];

        $kw = [];
        if (!empty($extends)) {
            foreach ($extends as $key => $val) {
                $val['config'] = unserialize($val['config']);
                $kw[$val['command']] = $val;
            }
        }

        $plugins = $this->readPlugins('Wechat');
        if (!empty($plugins)) {
            foreach ($plugins as $k => $v) {
                $plugins[$k]['enable'] = 0;
                $ks = $v['command'];
                // 数据库中存在，用数据库的数据
                if (isset($kw[$v['command']])) {
                    $plugins[$k]['keywords'] = $kw[$ks]['keywords'];
                    $plugins[$k]['config'] = $kw[$ks]['config'];
                    $plugins[$k]['enable'] = $kw[$ks]['enable'];
                }
                if ($wechat_type == 0 || $wechat_type == 1) {
                    if ($plugins[$k]['command'] == 'bd' || $plugins[$k]['command'] == 'bonus' || $plugins[$k]['command'] == 'ddcx' || $plugins[$k]['command'] == 'jfcx' || $plugins[$k]['command'] == 'sign' || $plugins[$k]['command'] == 'wlcx' || $plugins[$k]['command'] == 'zjd' || $plugins[$k]['command'] == 'dzp' || $plugins[$k]['command'] == 'ggk') {
                        unset($plugins[$k]);
                    }
                }

                // 商家过滤不使用的功能
                if (!empty($ru_id) || $ru_id > 0) {
                    if ($plugins[$k]['command'] == 'zjd' || $plugins[$k]['command'] == 'bonus' || $plugins[$k]['command'] == 'ddcx' || $plugins[$k]['command'] == 'jfcx' || $plugins[$k]['command'] == 'sign' || $plugins[$k]['command'] == 'wlcx') {
                        unset($plugins[$k]);
                    }
                }
            }
        }

        return $plugins;
    }

    /**
     * 获取微信通插件配置
     * @param string $directory
     * @return array
     */
    public function readPlugins($directory = 'Wechat')
    {
        $directory = Str::studly($directory);
        $plugins = glob(plugin_path($directory . '/*/config.php'));
        $config = [];
        foreach ($plugins as $file) {
            $config[] = require_once($file);
        }

        return $config;
    }

    /**
     * 返回微信通插件实例
     * @param string $plugin_name
     * @param array $info
     * @param array $extend_config
     * @return \Illuminate\Foundation\Application|mixed|null
     */
    public function wechatPluginInstance($plugin_name = '', $info = [], $extend_config = [])
    {
        $object = null;
        if ($plugin_name) {
            $plugin = Str::studly($plugin_name);
            $class = '\\App\Plugins\\Wechat\\' . $plugin . '\\' . $plugin;

            if (class_exists($class)) {
                // 插件配置
                $config = $this->getWechatPluginConfig($plugin, $info, $extend_config);
                $object = new $class($config);
            }
        }

        return $object;
    }

    /**
     * 获取微信通插件配置信息
     * @param string $plugin
     * @param array $info
     * @param array $extend_config
     * @return array|mixed
     */
    protected function getWechatPluginConfig($plugin = '', $info = [], $extend_config = [])
    {
        if (empty($plugin)) {
            return [];
        }

        //编辑
        if (!empty($info)) {
            $config = $info;
            $config['handler'] = 'edit';
        } else {
            $config_file = plugin_path('Wechat/' . $plugin . '/config.php');
            $config = require_once($config_file);
        }

        if (!empty($extend_config)) {
            $config = array_merge($config, $extend_config);
        }

        // 设置初始起止时间 默认当前时间后一个月
        $current_time = $this->timeRepository->getGmTime();
        $config['config']['starttime'] = empty($config['config']['starttime']) ? date('Y-m-d', $current_time) : $config['config']['starttime'];
        $config['config']['endtime'] = empty($config['config']['endtime']) ? date('Y-m-d', strtotime("+1 months")) : $config['config']['endtime'];

        return $config;
    }

    /**
     * 功能扩展信息
     * @param int $wechat_id
     * @param string $command
     * @return array
     */
    public function wechatExtendInfo($wechat_id = 0, $command = '')
    {
        if (empty($wechat_id) || empty($command)) {
            return [];
        }

        $info = WechatExtend::where('wechat_id', $wechat_id)
            ->where('command', $command)
            ->first();

        return $info ? $info->toArray() : [];
    }

    /**
     * 更新微信功能扩展
     * @param int $wechat_id
     * @param string $command
     * @param array $data
     * @return bool
     */
    public function updateWechatExtend($wechat_id = 0, $command = '', $data = [])
    {
        if (empty($wechat_id) || empty($command) || empty($data)) {
            return false;
        }

        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'wechat_extend');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = is_null($val) ? '' : $val;
        });

        return WechatExtend::where('wechat_id', $wechat_id)->where('command', $command)->update($data);
    }

    /**
     * 安装微信功能扩展
     * @param array $data
     * @return bool
     */
    public function createWechatExtend($data = [])
    {
        if (empty($data)) {
            return false;
        }

        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'wechat_extend');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = is_null($val) ? '' : $val;
        });

        return WechatExtend::create($data);
    }

    /**
     * 格式化处理功能扩展安装信息
     * @param array $cfg_value
     * @return array
     */
    public function transformWechatExtendCfgValue($cfg_value = [])
    {
        if (empty($cfg_value)) {
            return [];
        }

        // 过滤奖品名称的特殊字符
        if (isset($cfg_value['prize_name']) && !empty($cfg_value['prize_name'])) {
            foreach ($cfg_value['prize_name'] as $k => $n) {
                $replace = ['&', '<', '>', '=', '"', "'", '“', '”“'];
                $cfg_value['prize_name'][$k] = str_replace($replace, '', strip_tags(html_out($n)));
            }
        }

        return $cfg_value;
    }

    /**
     * 功能扩展安装素材 返回素材id
     * @param int $wechat_id
     * @param string $plugin_name
     * @return array
     */
    public function installWechatMediaForExtend($wechat_id = 0, $plugin_name = '')
    {
        //安装sql(暂时只提供素材数据表)
        $sql_file = plugin_path('Wechat/' . Str::studly($plugin_name) . '/install.php');
        if (file_exists($sql_file)) {
            //添加素材
            $install_data = require_once($sql_file);
            if ($install_data) {
                $install_data['wechat_id'] = $wechat_id;
                $install_data['is_show'] = 1;
                $install_data['link'] = route('wechat/plugin_show', ['name' => $plugin_name]);
                $install_data['add_time'] = $this->timeRepository->getGmTime();
                $install_data['file'] = 'assets/wechat/' . $plugin_name . '/images/' . $install_data['file_name'];
                $media_id = WechatMedia::insertGetId($install_data);
            }

            //获取素材id
            $media_id = isset($media_id) ? $media_id : WechatMedia::where(['command' => $plugin_name, 'wechat_id' => $wechat_id])->value('id');

            return $media_id;
        }

        return [];
    }

    /**
     * 功能扩展卸载
     * @param int $wechat_id
     * @param string $plugin_name
     * @return bool
     */
    public function uninstallWechatExtend($wechat_id = 0, $plugin_name = '')
    {
        // 更新
        $data = ['enable' => 0];
        $this->updateWechatExtend($wechat_id, $plugin_name, $data);

        // 删除相关素材
        $media = WechatMedia::where(['command' => $plugin_name, 'wechat_id' => $wechat_id]);
        $media_count = $media->count();
        if ($media_count > 0) {
            $media->delete();
        }
        // 删除相关中奖记录
        $prizes = WechatPrize::where(['activity_type' => $plugin_name, 'wechat_id' => $wechat_id]);
        $prizes_count = $prizes->count();
        if ($prizes_count > 0) {
            $prizes->delete();
        }

        return true;
    }

    /**
     * 中奖记录列表
     * @param int $wechat_id
     * @param array $offset
     * @param string $plugin_name
     * @param array $condition
     * @return array
     */
    public function wechatPrizeList($wechat_id = 0, $offset = [], $plugin_name = '', $condition = [])
    {
        $model = WechatPrize::where('wechat_id', $wechat_id)
            ->where('prize_type', 1)
            ->where('activity_type', $plugin_name);

        if (!empty($condition)) {
            if (!empty($condition['starttime']) && !empty($condition['endtime'])) {
                $model = $model->whereBetween('dateline', [$condition['starttime'], $condition['endtime']]);
            }
        }

        $where = [
            'wechat_id' => $wechat_id,
            'keywords' => $condition['keywords'] ?? ''
        ];
        $model = $model->whereHas('getWechatUser', function ($query) use ($where) {
            $query->where('subscribe', 1)
                ->where('wechat_id', $where['wechat_id']);
            if (!empty($where['keywords'])) {
                $query->where('nickname', 'like', '%' . $where['keywords'] . '%'); // 支持昵称搜索
            }
        });

        $model = $model->with([
            'getWechatUser' => function ($query) use ($where) {
                $query->select('openid', 'nickname')
                    ->where('subscribe', 1)
                    ->where('wechat_id', $where['wechat_id']);
            }
        ]);

        $total = $model->count();

        if (!empty($offset)) {
            $model = $model->offset($offset['start'])
                ->limit($offset['limit']);
        }

        $list = $model->orderBy('dateline', 'DESC')
            ->get();
        $list = $list ? $list->toArray() : [];

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 中奖记录列表
     * @param int $wechat_id
     * @param array $offset
     * @param string $plugin_name
     * @param array $condition
     * @return array
     */
    public function transformWechatPrizeForList($wechat_id = 0, $offset = [], $plugin_name = '', $condition = [])
    {
        $result = $this->wechatPrizeList($wechat_id, $offset, $plugin_name, $condition);

        $total = $result['total'] ?? 0;
        $list = $result['list'] ?? [];

        if ($list) {

            foreach ($list as $key => $val) {
                $val = collect($val)->merge($val['get_wechat_user'])->except('get_wechat_user')->all();
                $list[$key] = $val;
                $list[$key]['winner'] = empty($val['winner']) ? '' : unserialize($val['winner']);
                $list[$key]['dateline'] = $this->timeRepository->getLocalDate($this->config['time_format'], $val['dateline']);
            }
        }

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 查询中奖记录总数
     * @param int $wechat_id
     * @param array $offset
     * @param string $plugin_name
     * @param array $condition
     * @return mixed
     */
    public function transformWechatPrizeTotal($wechat_id = 0, $offset = [], $plugin_name = '', $condition = [])
    {
        $model = WechatPrize::where('wechat_id', $wechat_id)
            ->where('prize_type', 1)
            ->where('activity_type', $plugin_name);

        if (!empty($condition)) {
            if (!empty($condition['starttime']) && !empty($condition['endtime'])) {
                $model = $model->whereBetween('dateline', [$condition['starttime'], $condition['endtime']]);
            }
        }

        $where = [
            'wechat_id' => $wechat_id,
            'keywords' => $condition['keywords'] ?? ''
        ];
        $model = $model->whereHas('getWechatUser', function ($query) use ($where) {
            $query->where('subscribe', 1)
                ->where('wechat_id', $where['wechat_id']);
            if (!empty($where['keywords'])) {
                $query->where('nickname', 'like', '%' . $where['keywords'] . '%'); // 支持昵称搜索
            }
        });

        $model = $model->with([
            'getWechatUser' => function ($query) use ($where) {
                $query->select('openid', 'nickname')
                    ->where('subscribe', 1)
                    ->where('wechat_id', $where['wechat_id']);
            }
        ]);

        $total = $model->count();

        return $total;
    }

    /**
     * 中奖记录列表导出excel
     *
     * @param int $wechat_id
     * @param array $offset
     * @param string $plugin_name
     * @param array $condition
     * @return array
     * @throws \Exception
     */
    public function transformWechatPrizeForExcel($wechat_id = 0, $offset = [], $plugin_name = '', $condition = [])
    {
        $result = $this->wechatPrizeList($wechat_id, $offset, $plugin_name, $condition);

        $total = $result['total'] ?? 0;
        $list = $result['list'] ?? [];

        if ($list) {

            foreach ($list as $key => $val) {
                $val = collect($val)->merge($val['get_wechat_user'])->except('get_wechat_user')->all();
                $list[$key] = $val;
                $winner = empty($val['winner']) ? '' : unserialize($val['winner']);
                $list[$key]['winner'] = empty($winner) ? '' : lang('wechat.user_name') . "：" . $winner['name'] . "\r\n" . lang('wechat.phone') . "：" . $winner['phone'] . "\r\n" . lang('common.address') . "：" . $winner['address']; // 表格内换行
                $list[$key]['issue_status'] = isset($val['issue_status']) && $val['issue_status'] == 1 ? lang('common.yes') : lang('common.no');
                $list[$key]['dateline'] = $this->timeRepository->getLocalDate($this->config['time_format'], $val['dateline']);
            }
        }

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 更新中奖记录
     * @param int $wechat_id
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateWechatPrize($wechat_id = 0, $id = 0, $data = [])
    {
        if (empty($data)) {
            return false;
        }

        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'wechat_prize');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = is_null($val) ? '' : $val;
        });

        return WechatPrize::where(['id' => $id, 'wechat_id' => $wechat_id])->update($data);
    }

    /**
     * 删除中奖记录
     * @param int $wechat_id
     * @param int $id
     * @return bool
     */
    public function deleteWechatPrize($wechat_id = 0, $id = 0)
    {
        if (empty($id)) {
            return false;
        }

        return WechatPrize::where(['id' => $id, 'wechat_id' => $wechat_id])->delete();
    }

    /**
     * 签到记录列表
     * @param int $wechat_id
     * @param array $offset
     * @param string $plugin_name
     * @param array $condition
     * @return array
     */
    public function wechatPointList($wechat_id = 0, $offset = [], $plugin_name = 'sign', $condition = [])
    {
        $model = WechatPoint::where('keywords', $plugin_name);

        if (!empty($condition)) {
            if (!empty($condition['starttime']) && !empty($condition['endtime'])) {
                $model = $model->whereBetween('createtime', [$condition['starttime'], $condition['endtime']]);
            }
        }

        $where = [
            'wechat_id' => $wechat_id,
            'keywords' => $condition['keywords'] ?? ''
        ];

        $model = $model->whereHas('getWechatUser', function ($query) use ($where) {
            $query->where('subscribe', 1)
                ->where('wechat_id', $where['wechat_id']);
            if (!empty($where['keywords'])) {
                $query->where('nickname', 'like', '%' . $where['keywords'] . '%'); // 支持昵称搜索
            }
        });

        $model = $model->with([
            'getWechatUser' => function ($query) use ($where) {
                $query->select('openid', 'nickname')
                    ->where('subscribe', 1)
                    ->where('wechat_id', $where['wechat_id']);
            },
            'getAccountLog'
        ]);

        $total = $model->count();

        if (!empty($offset)) {
            $model = $model->offset($offset['start'])
                ->limit($offset['limit']);
        }

        $list = $model->orderBy('createtime', 'DESC')
            ->get();
        $list = $list ? $list->toArray() : [];

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 删除签到记录
     * @param int $wechat_id
     * @param int $id
     * @return bool
     */
    public function deleteWechatPoint($wechat_id = 0, $id = 0)
    {
        if (empty($id)) {
            return false;
        }

        return WechatPoint::where('id', $id)->delete();
    }

    /**
     * 签到记录列表导出excel
     * @param int $wechat_id
     * @param array $offset
     * @param string $plugin_name
     * @param array $condition
     * @return array
     */
    public function transformWechatPointList($wechat_id = 0, $offset = [], $plugin_name = '', $condition = [])
    {
        $result = $this->wechatPointList($wechat_id, $offset, $plugin_name, $condition);

        $total = $result['total'] ?? 0;
        $list = $result['list'] ?? [];

        if ($list) {

            foreach ($list as $key => $val) {
                $val = collect($val)->merge($val['get_wechat_user'])->except('get_wechat_user')->all();
                $val = collect($val)->merge($val['get_account_log'])->except('get_account_log')->all();
                $list[$key] = $val;
                // 签到奖励
                $sign_prize = '';
                if (isset($list[$key]['rank_points']) && $list[$key]['rank_points']) {
                    $sign_prize = lang('user.rank_integral') . '+' . $list[$key]['rank_points'];
                }
                if (isset($list[$key]['pay_points']) && $list[$key]['pay_points']) {
                    $sign_prize .= ' ' . lang('user.consume_integral') . '+' . $list[$key]['pay_points'];
                }
                $list[$key]['change_desc'] = $list[$key]['change_desc'] ?? '';
                $list[$key]['sign_prize'] = $sign_prize;
                $list[$key]['createtime_format'] = $this->timeRepository->getLocalDate($this->config['time_format'], $val['createtime']);
            }
        }

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 返回微信通营销插件实例
     * @param string $market_name
     * @param array $extend_config
     * @return \Illuminate\Foundation\Application|mixed|null
     */
    public function wechatMarketAdminInstance($market_name = '', $extend_config = [])
    {
        $object = null;
        if ($market_name) {
            $market = Str::studly($market_name);
            $class = '\\App\Plugins\\Market\\' . $market . '\\' . 'Admin';

            if (class_exists($class)) {
                // 插件配置
                $config = $this->getWechatMarketConfig($market, $extend_config);
                $object = new $class($config);
            }
        }

        return $object;
    }

    /**
     * 获取微信通营销插件配置信息
     * @param string $market
     * @param array $extend_config
     * @return array|mixed
     */
    public function getWechatMarketConfig($market = '', $extend_config = [])
    {
        if (empty($market)) {
            return [];
        }

        $config_file = plugin_path('Market/' . $market . '/config.php');
        $config = require_once($config_file);

        if (!empty($extend_config)) {
            $config = array_merge($config, $extend_config);
        }

        return $config;
    }

    /**
     * 获取微信通营销最新一条数据
     * @param int $wechat_id
     * @param string $market_type
     * @return int
     */
    public function wechatMarketingLastId($wechat_id = 0, $market_type = '')
    {
        $key = WechatMarketing::where(['marketing_type' => $market_type, 'wechat_id' => $wechat_id])->count();
        $key = !empty($key) ? $key + 1 : 1;

        return $key;
    }

    /**
     * 删除微信通营销数据
     * @param int $wechat_id
     * @param string $id
     * @return bool
     */
    public function deleteWechatMarket($wechat_id = 0, $id = '')
    {
        if (empty($id)) {
            return false;
        }

        return WechatMarketing::where(['id' => $id, 'wechat_id' => $wechat_id])->delete();
    }

    /**
     * 待拉取openid列表
     * @param int $wechat_id
     * @param array $wechat_user_list
     * @return array
     */
    public function getPullList($wechat_id = 0, $wechat_user_list = [])
    {
        $openid_list = [];

        $user_list = $this->wechatUserListAll($wechat_id); // 本地所有粉丝数据 $wechat_user_list为公众号所有粉丝

        if (!empty($user_list)) {
            $openid_list = array_column($user_list, 'openid'); // 去二维数组指定字段
        }

        $diff_list = empty($wechat_user_list) ? [] : array_diff($wechat_user_list, $openid_list); // 返回差集 以 array1为主

        $new_list = [];
        if ($diff_list) {
            $chunk_list = array_chunk($diff_list, 100);

            foreach ($chunk_list as $k => $list) {
                foreach ($list as $key => $openid) {
                    $new_list[$k][$key]['openid'] = $openid;
                    $new_list[$k][$key]['lang'] = 'zh_CN';
                }
            }
        }
        return $new_list;
    }
}
