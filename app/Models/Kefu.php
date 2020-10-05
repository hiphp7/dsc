<?php

namespace App\Models;

use App\Models\Users as User;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use Illuminate\Database\Eloquent\Model;

class Kefu extends Model
{

    /**
     * 获取管理员信息
     */
    public static function getAdmin($admin = 0)
    {
        if (empty($admin)) {
            return;
        }

        $adminUser = AdminUser::where('user_id', $admin)
            ->with(['service' => function ($query) {
                $query->addSelect('id', 'user_id', 'login_time')->where('status', 1);
            }])
            ->first();

        return $adminUser ? $adminUser->toArray() : [];
    }

    /**
     * 获取客服信息
     * 根据  admin_id
     */
    public static function getService($id)
    {
        $service = ImService::where('user_id', $id)
            ->where('status', 1)
            ->select('id', 'nick_name', 'chat_status')
            ->first();

        return $service ? $service->toArray() : [];
    }

    /**
     * 获取客服信息
     * 根据客服ID
     */
    public static function getServiceById($id)
    {
        $service = ImService::where('id', $id)
            ->where('status', 1)
            ->select('id', 'nick_name', 'chat_status', 'user_id')
            ->first();

        return $service ? $service->toArray() : [];
    }

    /**
     * 获取客服列表
     */
    public static function getServiceList($ruId, $sid)
    {
        if (empty($ruId) && $ruId !== 0) {
            return;
        }

        $adminUser = AdminUser::where('ru_id', $ruId)
            ->select(['user_id'])
            ->with(['service' => function ($query) {
                $query->where('status', 1);
            }])
            ->get();

        $adminUser = $adminUser ? $adminUser->toArray() : [];

        foreach ($adminUser as $k => $v) {
            if ($v['service']['id'] == $sid || is_null($v['service'])) {
                unset($adminUser[$k]);
                continue;
            }
        }

        $adminUser = array_map(function ($v) {
            if (!empty($v['service'])) {
                $v['id'] = $v['service']['id'];
                $v['name'] = $v['service']['nick_name'];
            }
            unset($v['service']);
            unset($v['user_id']);
            return $v;
        }, $adminUser);

        return $adminUser;
    }

    /**
     * 获取待接入列表
     */
    public static function getWait($ru_id = 0)
    {
        $waitMessage = ImDialog::select('id', 'customer_id', 'services_id', 'origin', 'goods_id', 'store_id', 'start_time')
            ->where('services_id', 0)
            ->where('store_id', $ru_id)
            ->where('status', 1)
            ->orderBy('start_time', 'DESC')
            ->groupby('customer_id')
            ->get();

        $waitMessage = $waitMessage ? $waitMessage->toArray() : [];

        if (empty($waitMessage)) {
            return null;
        }

        $total = 0;

        $waitMessageDataList = [];
        foreach ($waitMessage as $k => $v) {
            $waitMessage[$k]['add_time'] = app(TimeRepository::class)->getLocalDate('Y-m-d H:i:s', $v['start_time']);
            $waitMessage[$k]['origin'] = ($v['origin'] == 1) ? 'PC' : 'H5';
            $res = ImMessage::where('from_user_id', $v['customer_id'])
                ->where('to_user_id', 0)
                ->where('status', 1)
                ->orderBy('add_time', 'desc')
                ->get();

            $res = $res ? $res->toArray() : [];

            if (empty($res)) {
                unset($waitMessage[$k]);
                continue;
            }

            //查出多个消息
            $message = [];
            foreach ($res as $rk => $rv) {
                array_push($message, htmlspecialchars_decode($rv['message']));
            }
            $waitMessageDataList[$v['customer_id']] = array_reverse($message);
            $temp = $res[count($res) - 1];
            unset($res);
            $res = $temp;

            $waitMessage[$k]['num'] = ImMessage::where('from_user_id', $v['customer_id'])
                ->where('to_user_id', 0)
                ->where('status', 1)
                ->orderBy('add_time', 'desc')
                ->count();
            $total += $waitMessage[$k]['num'];

            $waitMessage[$k]['fid'] = $res['from_user_id'];
            $waitMessage[$k]['message'] = htmlspecialchars_decode($res['message']);
            $waitMessage[$k]['message_list'] = $message;
            $waitMessage[$k]['dialog_id'] = $res['dialog_id'];

            $user = User::where('user_id', $v['customer_id'])
                ->select('user_name', 'user_picture', 'nick_name')
                ->first();

            $user = $user ? $user->toArray() : [];

            if (!empty($user)) {
                $waitMessage[$k]['user_name'] = !empty($user['nick_name']) ? $user['nick_name'] : $user['user_name'];
                $waitMessage[$k]['avatar'] = empty($user['user_picture']) ? '' : app(DscRepository::class)->getImagePath($user['user_picture']);
            } else {
                unset($waitMessage[$k]);
            }
        }

        return [
            'waitMessage' => $waitMessage,
            'waitMessageDataList' => $waitMessageDataList,
            'total' => $total
        ];
    }

    /**
     * 获取用户信息
     */
    public static function userInfo($uid)
    {
        $res = User::where('user_id', $uid)
            ->select('user_name', 'user_picture', 'nick_name')
            ->first();

        $res = $res ? $res->toArray() : [];

        if (!empty($res)) {
            $res['avatar'] = empty($res['user_picture']) ? '' : app(DscRepository::class)->getImagePath($res['user_picture']);
            $res['user_name'] = !empty($res['nick_name']) ? $res['nick_name'] : $res['user_name'];
        } else {
            $res['user_name'] = '';
            $res['avatar'] = '';
        }
        return $res;
    }

    /**
     * 获取聊天记录
     */
    public static function getChatLog($service)
    {
        // 只显示未结束的
        $messageList = ImDialog::select('id', 'customer_id', 'services_id', 'origin', 'goods_id', 'store_id', 'status')
            ->where('services_id', $service['id'])
            ->where('status', 1);

        $messageList = $messageList->whereHas('getUser');

        $messageList = $messageList->with([
            'getUser'
        ]);

        $messageList = $messageList->orderBy('start_time', 'DESC')
            ->get();

        $messageList = $messageList ? $messageList->toArray() : [];

        if (empty($messageList)) {
            $messageList[0] = [
                'id' => '',
                'customer_id' => '',
                'services_id' => '',
                'origin' => '',
                'goods_id' => '',
                'store_id' => '',
                'message' => '',
                'add_time' => '',
                'user_name' => '',
                'user_picture' => '',
            ];

            return $messageList;
        }

        // 去重
        $temp = [];
        foreach ($messageList as $k => $v) {
            if (in_array($v['customer_id'], $temp)) {
                unset($messageList[$k]);
                continue;
            }
            $temp[] = $v['customer_id'];
        }

        $rootPath = url('/');

        foreach ($messageList as $k => $v) {
            //消息属性
            $where = "((from_user_id = {$v['services_id']} AND to_user_id = {$v['customer_id']}) OR (from_user_id = {$v['customer_id']} AND to_user_id = {$v['services_id']}))";

            $res = ImMessage::whereRaw($where)
                ->orderBy('add_time', 'desc')
                ->select('message', 'add_time', 'user_type', 'status')
                ->first();
            $res = $res ? $res->toArray() : [];

            $messageList[$k]['message'] = isset($res['message']) ? htmlspecialchars_decode($res['message']) : '';
            $messageList[$k]['add_time'] = isset($res['add_time']) ? app(TimeRepository::class)->getLocalDate('Y-m-d H:i:s', $res['add_time']) : '';
            $messageList[$k]['origin'] = ($v['origin'] == 1) ? 'PC' : 'H5';
            $messageList[$k]['user_type'] = $res['user_type'] ?? '';
            $messageList[$k]['status'] = ($v['status'] == 1) ? '未结束' : '结束';
            $messageList[$k]['goods']['goods_name'] = '';
            $messageList[$k]['goods']['shop_price'] = '';
            $messageList[$k]['goods']['goods_thumb'] = '';
            // 未读消息列表
            $res = ImMessage::where('dialog_id', $v['id'])
                ->where('status', 1)
                ->select('message')
                ->orderBy('add_time', 'DESC')
                ->get();

            $res = $res ? $res->toArray() : [];

            if (!empty($res)) {
                $temp = [];
                foreach ($res as $msg) {
                    $temp[] = htmlspecialchars_decode($msg['message']);
                }
                $messageList[$k]['message'] = $temp;
                $messageList[$k]['message_sum'] = count($temp);
            }

            //商品属性 OR 店铺属性
            if ($messageList[$k]['goods_id'] > 0) {
                $res = Goods::where('goods_id', $v['goods_id'])
                    ->select('goods_name', 'goods_thumb', 'shop_price')
                    ->first();

                $res = $res ? $res->toArray() : [];

                if (!empty($res)) {
                    $messageList[$k]['goods']['goods_id'] = $v['goods_id'];
                    $messageList[$k]['goods']['goods_name'] = $res['goods_name'];
                    $messageList[$k]['goods']['shop_price'] = app(DscRepository::class)->getPriceFormat($res['shop_price']);
                    $messageList[$k]['goods']['url'] = $rootPath . '/goods.php?id=' . $v['goods_id'];
                    $messageList[$k]['goods']['goods_thumb'] = empty($res['goods_thumb']) ? '' : app(DscRepository::class)->getImagePath($res['goods_thumb']);
                }
            }

            //用户属性
            /**$res = User::where('user_id', $v['customer_id'])
             * ->select('user_name', 'user_picture', 'nick_name')
             * ->first();**/

            $users = $v['get_user'] ?? [];

            if (!empty($users)) {
                $messageList[$k]['user_name'] = !empty($users['nick_name']) ? $users['nick_name'] : $users['user_name'];
                $messageList[$k]['user_picture'] = empty($users['user_picture']) ? '' : app(DscRepository::class)->getImagePath($users['user_picture']);
            } else {
                unset($messageList[$k]);
            }
        }

        if (empty($messageList)) {
            $messageList[0] = [
                'id' => '',
                'customer_id' => '',
                'services_id' => '',
                'origin' => '',
                'goods_id' => '',
                'store_id' => '',
                'message' => '',
                'add_time' => '',
                'user_name' => '',
                'user_picture' => '',
            ];
        }

        return $messageList;
    }

    /**
     * 将未读消息改为已读消息
     */
    public static function changeMessageStatus($serviceId, $customId)
    {
        ImMessage::where(['from_user_id' => $serviceId, 'to_user_id' => $customId])
            ->orWhere(['to_user_id' => $serviceId, 'from_user_id' => $customId])
            ->update(['status' => 0]);
    }

    /**
     * 获取聊天记录
     */
    public static function getHistory($uid, $tid, $keyword = '', $time = '', $page = 1, $size = 20)
    {
        $start = ($page - 1) * $size;

        //总数据量
        $where = "((from_user_id = {$uid} AND to_user_id = {$tid}) OR (from_user_id = {$tid} AND to_user_id = {$uid}))";

        if (!empty($keyword)) {
            $where .= " AND (message like '%{$keyword}%')";
        }

        if (!empty($time)) {
            $nowtime = strtotime($time);
            $tomotime = $nowtime + 3600 * 24;
            $where .= " AND (add_time > {$nowtime} AND add_time < {$tomotime})";
        }

        $count = ImMessage::whereRaw($where)->count();

        //查询列表
        $list = ImMessage::whereRaw($where)
            ->orderBy('add_time', 'DESC')
            ->orderBy('id', 'DESC')
            ->select('id', 'message', 'add_time', 'from_user_id', 'user_type')
            ->offset($start)
            ->limit($size)
            ->get();

        if ($list) {
            foreach ($list as $k => $v) {
                if ($v['user_type'] == 1) {
                    $nick_name = ImService::where('id', $v['from_user_id'])->value('nick_name');
                    $list[$k]['from_user_name'] = $nick_name ?? '';
                    $list[$k]['from_user_id'] = $v['from_user_id'];
                } elseif ($v['user_type'] == 2) {
                    $res = User::where('user_id', $v['from_user_id'])
                        ->select('user_name', 'nick_name')
                        ->first();

                    $users = $res ? $res->toArray() : [];

                    $list[$k]['from_user_name'] = !empty($users) && !empty($users['nick_name']) ? $users['nick_name'] : $users['user_name'];
                    $list[$k]['from_user_id'] = $v['from_user_id'];
                }
                $list[$k]['message'] = htmlspecialchars_decode($v['message']);
                $list[$k]['add_time'] = app(TimeRepository::class)->getLocalDate('Y-m-d H:i:s', $v['add_time']);
            }
        }

        return ['list' => $list, 'total' => ceil($count / $size)];
    }

    /**
     * 获取聊天记录
     */
    public static function getSearchHistory($mid)
    {
        $message = ImMessage::where('id', $mid)
            ->select('from_user_id', 'to_user_id')
            ->first();

        $message = $message ? $message->toArray() : [];

        $where = " id < {$mid} AND ((from_user_id = {$message['from_user_id']} AND to_user_id = {$message['to_user_id']}) OR (from_user_id = {$message['to_user_id']} AND to_user_id = {$message['from_user_id']}))";

        //查询列表
        $list = ImMessage::whereRaw($where)
            ->get();

        $list = $list ? $list->toArray() : [];

        if (!empty($lisst)) {
            foreach ($list as $k => $v) {
                if ($v['user_type'] == 1) {
                    $nick_name = ImService::where('id', $v['from_user_id'])->value('nick_name');

                    $list[$k]['from_user_name'] = $nick_name ?? '';
                    $list[$k]['from_user_id'] = $v['from_user_id'];
                } elseif ($v['user_type'] == 2) {
                    $res = User::where('user_id', $v['from_user_id'])
                        ->select('user_name', 'nick_name')
                        ->first();

                    $users = $res ? $res->toArray() : [];

                    $list[$k]['from_user_name'] = !empty($users) && !empty($users['nick_name']) ? $users['nick_name'] : $users['user_name'];
                    $list[$k]['from_user_id'] = $v['from_user_id'];
                }
                $list[$k]['message'] = htmlspecialchars_decode($v['message']);
                $list[$k]['add_time'] = app(TimeRepository::class)->getLocalDate('Y-m-d H:i:s', $v['add_time']);

                if ($mid == $v['id']) {
                    $list[$k]['current'] = 1;
                }
            }
        }

        return ['list' => $list];
    }

    /**
     * 获取快捷回复
     */
    public static function getReply($id, $type)
    {
        $reply = ImConfigure::where('ser_id', $id)
            ->where('type', $type);

        if ($type == 1) {
            $reply = $reply->get();
        } elseif ($type == 2 || $type == 3) {
            $reply = $reply->first();
        }

        return $reply ? $reply->toArray() : [];
    }

    /**
     * 检查会话是否存在
     */
    public static function isDialog($data)
    {
        $dialog = ImDialog::where('customer_id', $data['customer_id'])
            ->where('services_id', $data['services_id'])
            ->where('goods_id', $data['goods_id'])
            ->where('store_id', $data['store_id'])
            ->where('status', 1)
            ->orderBy('id', 'DESC')
            ->first();

        if (is_null($dialog)) {
            return false;
        }

        $dialog->end_time = app(TimeRepository::class)->getGmTime();
        $dialog->save();
        return $dialog->id;
    }

    /**
     * 添加会话表
     */
    public static function addDialog($data)
    {
        $dialog = new ImDialog();
        $dialog->customer_id = $data['customer_id'];
        $dialog->services_id = $data['services_id'];
        $dialog->goods_id = $data['goods_id'];
        $dialog->store_id = $data['store_id'];
        $dialog->start_time = $data['start_time'];
        $dialog->origin = $data['origin'];
        $dialog->save();

        return $dialog->id;
    }

    /**
     * 查找最近会话
     */
    public static function getRecentDialog($fid, $cid)
    {
        $dialog = ImDialog::where('customer_id', $cid)
            ->where('services_id', $fid)
            ->orderBy('id', 'DESC')
            ->first();

        return $dialog ? $dialog->toArray() : [];
    }

    /**
     * 首次接入更新会话
     */
    public static function updateDialog($cusId, $serId)
    {
        /** 修改消息表 */
        ImMessage::where('from_user_id', $cusId)->where('to_user_id', '')->where('user_type', 2)->update(['to_user_id' => $serId, 'status' => 0]);
        /** 查找会话表 */
        $dialog = ImDialog::where('customer_id', $cusId)
            ->where('services_id', 0)
            ->get();
        if (!empty($dialog)) {
            foreach ($dialog as $item) {
                $item->services_id = $serId;
                $item->end_time = app(TimeRepository::class)->getGmTime();
                $item->save();
            }
        }
    }

    /**
     * 切换客服更新会话
     */
    public static function updateNewDialog($cusId, $serId)
    {
        /** 修改消息表 */
        // ImMessage::where('from_user_id', $cusId)->where('to_user_id', '')->where('user_type', 2)->update(['to_user_id' => $serId, 'status' => 0]);
        // ImMessage::where('from_user_id', $cusId)->where('user_type', 2)->update(['to_user_id' => $serId, 'status' => 0]);
        // 更新最近一条消息
        $ImMessage = ImMessage::where('from_user_id', $cusId)
            ->where('user_type', 2)
            ->orderBy('add_time', 'DESC')
            ->first();

        if (!empty($ImMessage)) {
            $ImMessage->to_user_id = $serId;
            $ImMessage->status = 0;
            $ImMessage->save();
        }

        /** 查找会话表 */
        $dialog = ImDialog::where('customer_id', $cusId)
            ->get();

        if (!empty($dialog)) {
            foreach ($dialog as $item) {
                $item->services_id = $serId;
                $item->end_time = app(TimeRepository::class)->getGmTime();
                $item->save();
            }
        }
    }

    /**
     * 结束会话
     */
    public static function closeWindow($uid, $tid)
    {
        ImDialog::where('customer_id', $tid)
            ->where('services_id', $uid)
            ->orderBy('start_time', 'DESC')
            ->update(['end_time' => app(TimeRepository::class)->getGmTime(), 'status' => 2]);
    }

    /**
     * 结束会话
     * @条件： 超过一段时间没有对话
     * @param string $expire 有效时间
     */
    public static function closeOldWindow($expire)
    {
        $dialog = ImDialog::where('end_time', '<', app(TimeRepository::class)->getGmTime() - $expire)
            ->where('status', 1)
            ->where('end_time', '>', 0)
            ->where('services_id', '<>', 0)
            ->distinct()
            ->orderBy('start_time', 'DESC')
            ->get();

        $temp = [];
        if (isset($dialog)) {
            foreach ($dialog as $k => $v) {
                $v->status = 2;
                $v->end_time = app(TimeRepository::class)->getGmTime();
                $v->save();
                if (isset($temp[$v->customer_id])) {
                    continue;
                }

                $temp[$v->customer_id] = [
                    'cid' => $v->customer_id,
                    'ssid' => $v->services_id,
                    'sid' => $v->store_id
                ];
            }
        }

        return $temp;
    }

    /**
     * 获取商品信息
     */
    public static function getGoods($gid)
    {
        $goods = Goods::select('goods_id', 'goods_name', 'goods_thumb', 'shop_price')
            ->where('goods_id', $gid)
            ->first();

        $goods = $goods ? $goods->toArray() : [];

        if ($goods) {
            $goods['goods_thumb'] = empty($goods['goods_thumb']) ? '' : app(DscRepository::class)->getImagePath($goods['goods_thumb']);
        }

        return $goods;
    }

    /**
     * 获取店铺信息
     */
    public static function getStoreInfo($sid)
    {
        $store = SellerShopinfo::select('shop_name', 'logo_thumb')
            ->where('ru_id', $sid)
            ->first();

        $store = $store ? $store->toArray() : [];

        if (!empty($store)) {
            if (empty($store['logo_thumb'])) {
                $store['logo_thumb'] = self::format_pic('', 'service');
            }
            return $store;
        }

        return [
            'shop_name' => '',
            'logo_thumb' => self::format_pic('', 'service')
        ];
    }

    /**
     * 获取接入回复
     */
    public static function getServiceReply($serviceId)
    {
        $conf = ImConfigure::where('ser_id', $serviceId)
            ->where('is_on', 1)
            ->where('type', 2)
            ->first();

        return is_null($conf) ? '' : $conf->content;
    }

    /**
     * 过滤商品图片
     */
    public static function format_goods_pic($pic)
    {
        $rootPath = asset('/');

        if (empty($pic)) {
            return asset('img/no_image.jpg');
        }
        if (strpos($pic, 'http') !== false) {
            return $pic;
        }

        return $rootPath . 'storage/' . $pic;
    }

    /**
     * 过滤图片
     */
    public static function format_pic($pic, $who = '')
    {
        $rootPath = asset('/');

        if (strpos($pic, 'http') !== false) {
            return $pic;
        }

        if (empty($pic)) {
            if ($who == 'service') {
                $pic = 'service.png';
            } else {
                $pic = 'avatar.png';
            }
            return asset('assets/chat/images/' . $pic);
        }

        return $rootPath . 'storage/' . $pic;
    }

    /**
     * 处理链接消息
     * 1 有本站商品ID 返回 商品信息
     * 2 无商品 返回 可点击的原链接
     */
    public static function format_msg($text)
    {
        $text = htmlspecialchars_decode($text);
        //匹配URL
        $reg = "/http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(:\d+)?(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?+/i";
        preg_match_all($reg, $text, $links);
        if (!empty($links[0])) {
            foreach ($links[0] as $url) {
                $replace = '';
                if (preg_match('/^(http|https)/is', $url)) {
                    if (preg_match('/(goods|goods.php)/i', $url)) { //验证是否有goods 控制器
                        // 解析URL
                        $param = self::get_url_queryl($url);
                        if (isset($param) && !empty($param['id'])) {
                            $goods_id = $param['id']; //商品id
                        }
                        $goods_info = [];
                        if (!empty($goods_id)) {
                            //获取商品信息
                            $res = Goods::where('goods_id', $goods_id)
                                ->select('goods_name', 'goods_thumb', 'shop_price')
                                ->first();
                            if (!empty($res)) {
                                $goods_info = $res->toArray();
                            }
                        }
                        if (!empty($goods_info)) {
                            $shop_price = '￥' . $goods_info['shop_price'];
                            $goods_img = self::format_pic($goods_info['goods_thumb']);
                            $goods_name = app(DscRepository::class)->subStr($goods_info['goods_name'], 50);
                            $replace = '<div class="new_message_list" >' .
                                '<img src="' . $goods_img . '" >' .
                                '<a href="' . $url . '" target="_blank" >' .
                                '<div class="left_goods_info">' .
                                '<h4>' . $goods_name . '</h4>' .
                                '<span>' . $shop_price . '</span>' .
                                '</div>' .
                                '</a>' .
                                '</div>';
                        } else {
                            $replace = '<a href="' . $url . '" target="_blank">' . $url . '</a>';
                        }
                    } elseif (preg_match('/(.jpg|.jpeg|.png|.gif)/i', $url)) {
                        //验证是否图片
                        $replace = $url;
                    } else {
                        $replace = '<a href="' . $url . '" target="_blank">' . $url . '</a>';
                    }
                }

                $text = str_replace($url, $replace, $text);
            }
        }

        return escapeHtml($text);
    }

    /**
     * 获得URL参数
     * @param string $url URL表达式，格式：'http://www.a.com/index.php?参数1=值1&参数2=值2...'
     *  或 参数1=值1&参数2=值2...
     * @return array
     */
    protected static function get_url_queryl($url = '')
    {
        $params = [];
        // 解析URL
        $info = parse_url($url);
        // 判断参数 是否为url 或 path
        if (false == strpos($url, '?')) {
            if (isset($info['path'])) {
                // 解析地址里面path参数
                parse_str($info['path'], $params);
            }
        } elseif (isset($info['query'])) {
            // 解析地址里面query参数
            parse_str($info['query'], $params);
        }

        return $params;
    }
}
