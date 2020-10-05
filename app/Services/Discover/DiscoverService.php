<?php

namespace App\Services\Discover;

use App\Models\Comment;
use App\Models\CommentImg;
use App\Models\DiscussCircle;
use App\Models\Goods;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\User\UserService;

/**
 * Class DiscoverService
 * @package App\Services\Discover
 */
class DiscoverService
{
    protected $timeRepository;
    protected $config;
    protected $userServices;
    protected $baseRepository;
    protected $dscRepository;
    protected $userService;

    public function __construct(
        TimeRepository $timeRepository,
        UserService $userService,
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $files = [
            'clips',
            'common',
            'main',
            'order',
            'function',
            'base',
            'goods',
            'ecmoban'
        ];
        load_helper($files);
        $this->timeRepository = $timeRepository;
        $this->userService = $userService;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 发帖
     *
     * @param $uid
     * @param int $goods_id
     * @param $dis_type
     * @param string $title
     * @param string $content
     * @return string
     */
    public function Create($uid, $goods_id = 0, $dis_type, $title = '', $content = '')
    {
        if (empty($goods_id)) {
            return '关联商品不能为空';
        }
        if (empty($dis_type)) {
            return '请选择帖子主题';
        }
        if (empty($title)) {
            return '请填写标题';
        }
        if (empty($content)) {
            return '请填写帖子内容';
        }

        $res = Users::select('nick_name', 'user_name')
            ->where('user_id', $uid);

        $res = $this->baseRepository->getToArrayFirst($res);

        $user_name = isset($res['nick_name']) ? $res['nick_name'] : $res['user_name'];

        $time = $this->timeRepository->getGmTime();

        $other = [
            'user_id' => $uid,
            'dis_type' => $dis_type,
            'dis_title' => $title,
            'goods_id' => $goods_id,
            'user_name' => $user_name,
            'dis_text' => $content,
            'add_time' => $time
        ];
        DiscussCircle::insert($other);

        return '发帖成功, 等待管理员审核...';
    }

    /**
     * 发帖信息
     * @param $uid
     * @param $goods_id
     * @return mixed
     */
    public function Show($uid = 0, $goods_id = 0)
    {
        $goods = Goods::select('goods_thumb', 'goods_name', 'goods_id')
            ->where('goods_id', $goods_id);

        $goods = $this->baseRepository->getToArrayFirst($goods);

        if (empty($goods)) {
            return [];
        }

        $order = [];
        $order['is_login'] = 0;
        if ($uid > 0) {
            $order['is_login'] = 1;
        }
        $order['goods_id'] = $goods['goods_id'];
        $order['goods_name'] = $goods['goods_name'];
        $order['goods_thumb'] = $this->dscRepository->getImagePath($goods['goods_thumb']);

        return $order;
    }

    /**
     * 首页
     * @param $uid
     * @return mixed
     */
    public function Index($uid)
    {
        $res = [];
        $res['tao'] = ['num' => $this->community_num(1), 'has_new' => $this->community_has_new($uid, 1)];//讨论贴
        $res['wen'] = ['num' => $this->community_num(2), 'has_new' => $this->community_has_new($uid, 2)];//问答帖
        $res['quan'] = ['num' => $this->community_num(3), 'has_new' => $this->community_has_new($uid, 3)];//圈子帖
        $res['sun'] = ['num' => $this->sd_count(), 'has_new' => $this->community_has_new($uid, 4, 1)];//晒单贴
        return $res;
    }

    /**
     * 列表
     *
     * @param int $uid
     * @param int $dis_type
     * @param int $page
     * @param int $size
     * @param string $from
     * @return mixed
     */
    public function List($uid = 0, $dis_type = 0, $page = 1, $size = 5, $from = '')
    {
        if ($dis_type == 1) {
            //讨论贴
        } elseif ($dis_type == 2) {
            //问答帖;
        } elseif ($dis_type == 3) {
            //圈子帖;
        } elseif ($dis_type == 4) {
            //晒单贴;
            $uid = $from == 'mylist' ? $uid : 0;
            $list = $this->comment_list($uid, $page, $size);
            return $list;
        }

        $uid = $from == 'mylist' ? $uid : 0;
        $list = $this->community_list($uid, $dis_type, $page, $size);

        return $list;
    }

    /**
     * 帖子详情
     *
     * @param int $uid
     * @param int $dis_type
     * @param int $dis_id
     * @return array|\Illuminate\Contracts\Translation\Translator|null|string
     * @throws \Exception
     */
    public function Detail($uid = 0, $dis_type = 0, $dis_id = 0)
    {
        if ($dis_type == 4) {
            $res = Comment::where('comment_id', $dis_id)
                ->where('parent_id', 0);
            $res = $this->baseRepository->getToArrayFirst($res);
        } else {
            $res = DiscussCircle::where('dis_id', $dis_id)
                ->where('parent_id', 0);
            $res = $this->baseRepository->getToArrayFirst($res);
        }

        if (empty($res)) {
            return lang('discover.not_exist');
        }
        if (!empty($res) && $dis_type == 4 && $res['status'] == 0) {
            return lang('discover.no_show');
        }
        if (!empty($res) && $dis_type != 4 && $res['review_status'] != 3) {
            return lang('discover.now_audit');
        }
        if ($res) {
            if ($dis_type == 4) {
                $res['dis_text'] = $res['content'];
                $res['dis_id'] = $res['comment_id'];
                $res['dis_type'] = 4;
            }
            $res['add_time'] = $this->mdate($res['add_time']);

            $users = $this->get_wechat_user_info($res['user_id']);

            $res['user_name'] = isset($users['nick_name']) ? $this->encrypt_username($users['nick_name']) : '';
            $res['user_picture'] = $this->dscRepository->getImagePath($users['user_picture'], '', asset('img/user_default.png'));

            //帖子浏览量+1
            if ($dis_type == 4) {
                $click = Comment::where('comment_id', $dis_id)->where('parent_id', 0)->where('status', 1)->first();
                $click->update(['dis_browse_num' => $click->dis_browse_num + 1]);
            } else {
                $click = DiscussCircle::where('dis_id', $dis_id)->where('parent_id', 0)->where('review_status', 3)->first();
                $click->update(['dis_browse_num' => $click->dis_browse_num + 1]);
            }

            if ($dis_type == 4) {
                $img_list = $this->get_img_list($res['id_value'], $res['comment_id']);
                if ($img_list) {
                    foreach ($img_list as $key => $list) {
                        $img_list[$key]['comment_img'] = $this->dscRepository->getImagePath($list['comment_img']);
                    }
                }
                $res['img_list'] = $img_list;

                $link_good = Goods::select('goods_id', 'goods_thumb', 'goods_name')
                    ->where('goods_id', $res['id_value']);
                $link_good = $this->baseRepository->getToArrayFirst($link_good);
            } else {
                $link_good = Goods::select('goods_id', 'goods_thumb', 'goods_name')
                    ->where('goods_id', $res['goods_id']);
                $link_good = $this->baseRepository->getToArrayFirst($link_good);
            }
            if (!empty($link_good)) {
                $link_good['goods_thumb'] = $this->dscRepository->getImagePath($link_good['goods_thumb']);
                $link_good['url'] = dsc_url('/#/goods/' . $link_good['goods_id']);
                $link_good['app_page'] = config('route.goods.detail') . $link_good['goods_id'];
            }

            $res['link_good'] = $link_good;

            if ($dis_type == 4) {
                $reply_count = Comment::where('status', 1)
                    ->where('parent_id', $dis_id)
                    ->where('user_id', '>', 0)
                    ->count();
            } else {
                $reply_count = DiscussCircle::where('parent_id', $dis_id)
                    ->where('user_id', '>', 0)
                    ->count();
            }
        }
        $res['reply_count'] = $reply_count;
        // 当前登录用户头像
        $users = $this->get_wechat_user_info($uid);
        if ($users) {
            $res['avatar'] = $this->dscRepository->getImagePath($users['user_picture'], '', asset('img/user_default.png'));
        }
        $res['goods_id'] = $res['goods_id'] ?? $res['id_value'];
        $res['dis_type'] = $dis_type;
        $res['dis_id'] = $dis_id;
        $res['user_comment'] = [];

        if ($res['goods_id']) {
            if ($dis_type == 4) {
                $re = Comment::select('add_time', 'user_id', 'comment_id as dis_id', 'comment_type', 'content as dis_text')
                    ->where('id_value', $res['goods_id'])
                    ->where('status', 1)
                    ->where('parent_id', $dis_id)
                    ->where('status', 1)
                    ->orderBy('add_time', 'desc');
                $dis_comment = $this->baseRepository->getToArrayGet($re);

                foreach ($dis_comment as $k => $v) {
                    $dis_comment[$k]['add_time'] = $this->mdate($v['add_time']);
                    $usersnick = $this->get_wechat_user_info($v['user_id']);
                    $dis_comment[$k]['user_name'] = $v['user_id'] == 0 ? '管理员' : (isset($usersnick['nick_name']) ? $this->encrypt_username($usersnick['nick_name']) : '');
                    $dis_comment[$k]['user_picture'] = $this->dscRepository->getImagePath($usersnick['user_picture'], '', asset('img/user_default.png'));
                    $dis_comment[$k]['next_com'] = $dis_id > 0 ? $this->get_comment_reply($v['dis_id'], $dis_id, $res['goods_id']) : [];
                    $dis_comment[$k]['quote_count'] = isset($v['quote']) ? count($dis_comment[$k]['quote']) : 0;
                }
            } else {
                $re = DiscussCircle::where('parent_id', $dis_id)
                    ->where('review_status', 3)
                    ->orderBy('add_time', 'desc');
                $dis_comment = $this->baseRepository->getToArrayGet($re);
                foreach ($dis_comment as $k => $v) {
                    $dis_comment[$k]['add_time'] = $this->mdate($v['add_time']);
                    $usersnick = $this->get_wechat_user_info($v['user_id']);
                    $dis_comment[$k]['user_name'] = isset($usersnick['nick_name']) ? $this->encrypt_username($usersnick['nick_name']) : '';
                    $dis_comment[$k]['user_picture'] = $this->dscRepository->getImagePath($usersnick['user_picture'], '', asset('img/user_default.png'));
                    $dis_comment[$k]['next_com'] = $dis_id > 0 ? $this->get_quote_reply($v['dis_id'], $dis_id) : [];
                    $dis_comment[$k]['quote_count'] = isset($v['quote']) ? count($dis_comment[$k]['quote']) : 0;

                    if ($v['quote_id']) {
                        unset($dis_comment[$k]);
                    }
                }
            }

            $res['user_comment'] = collect($dis_comment)->values()->all();
        }

        if (isset($res['dis_text']) && $res['dis_text']) {
            $res['dis_text'] = $this->html_out($res['dis_text']);
        }

        return $res;
    }

    /**
     * 回复评论的评论
     *
     * @param int $dis_id
     * @param $dis_type
     * @return array|void
     */
    public function get_more_comm($dis_id = 0, $dis_type)
    {
        $result = [];
        if ($dis_id) {
            $comment_type = Comment::whereRaw(1);
            $discuss_type = DiscussCircle::whereRaw(1);
            $res = $dis_type == 4 ? $comment_type : $discuss_type;
            $res = $res->where('parent_id', $dis_id);
            $res = $dis_type == 4 ? $res->where('status', 1) : $res->where('review_status', 3);
            $result = $this->baseRepository->getToArrayGet($res);

            if ($result) {
                foreach ($result as $key => $v) {
                    $usersnick = $this->get_wechat_user_info($v['user_id']);
                    $result[$key]['user_name'] = isset($usersnick['nick_name']) ? $this->encrypt_username($usersnick['nick_name']) : '';
                }
            }
        }
        return $result;
    }

    /**
     * 评论列表
     * @return
     */
    public function CommentList($type = 0, $page = 1, $size = 10, $user_id = 0, $goods_id = 0)
    {
        $res = DiscussCircle::where([
            ['review_status', '=', 3],
            ['user_id', '<>', 0],
            ['parent_id', '=', 0],
            ['goods_id', '<>', 0],
            ['dis_type', '<>', 4]]);
        if ($goods_id > 0) {
            $res = $res->where('goods_id', $goods_id);
        }
        if ($type) {
            if ($type == 'all') {
                $res = $res->whereIn('dis_type', [1, 2, 3]);
            } else {
                $res = $res->where('dis_type', $type);
            }
        }
        $res = $res->with([
            'getGoods' => function ($query) {
                $query->select('goods_id', 'goods_name');
            },
            'getUsers' => function ($query) {
                $query->select('user_id', 'user_name', 'nick_name', 'user_picture');
            }
        ]);

        if ($user_id > 0) {
            $res = $res->whereHas('getUsers', function ($query) use ($user_id) {
                $query->where('user_id', $user_id);
            });
        }

        $res = $res->orderBy('add_time', 'desc')
            ->offset(($page - 1) * $size)
            ->limit($size);

        $list = $this->baseRepository->getToArrayGet($res);
        if ($list) {
            foreach ($list as $k => $v) {
                $v = array_merge($v, $v['get_goods'], $v['get_users']);
                unset($list[$k]['get_goods']);
                unset($list[$k]['get_users']);
                $list[$k]['add_time'] = $this->mdate($v['add_time']);
                $users = $this->get_wechat_user_info($v['user_id']);
                $list[$k]['user_name'] = isset($users['nick_name']) ? $this->encrypt_username($users['nick_name']) : '';
                $list[$k]['user_picture'] = $this->dscRepository->getImagePath($users['user_picture'], '', asset('img/user_default.png'));
                $list[$k]['community_num'] = $this->community_num(0, $v['dis_id']);
                $list[$k]['delete_com'] = ($user_id == $v['user_id']) ? 1 : 0; // 是否显示删除按钮
                $list[$k]['dis_text'] = $this->dscRepository->subStr(strip_tags($this->html_out($v['dis_text'])), 50);
            }
        }

        return $list;
    }

    /**
     * 提交评论
     */
    public function Commnet($dis_type, $parent_id = 0, $quote_id = 0, $dis_text = '', $user_id = 0, $goods_id = 0, $reply_type = '')
    {
        if ($this->checkDistype($dis_type) == false) {
            return ['error' => 1, 'msg' => lang('discover.type_error')];
        }
        if (empty($dis_text)) {
            return ['error' => 1, 'msg' => lang('discover.content_not_null')];
        }

        $usersnick = $this->get_wechat_user_info($user_id);
        $nick_name = isset($usersnick['nick_name']) ? $this->encrypt_username($usersnick['nick_name']) : '';

        $time = $this->timeRepository->getGmTime();

        $res = DiscussCircle::select('user_id')->where('dis_id', $parent_id);
        $res = $this->baseRepository->getToArrayFirst($res);
        //不能回复自己的帖子
        if (isset($res['user_id']) && $res['user_id'] == $user_id && $quote_id == 0) {
            return ['error' => 1, 'msg' => lang('discover.not_allow_comment')];
        }

        // 回复晒单
        if ($dis_type == 4) {
            // 回复其他
            if ($reply_type == 'reply_other') {
                $parent_id = $quote_id;
            }

            $data = [
                'comment_type' => 2,
                'id_value' => $goods_id,
                'content' => $dis_text,
                'parent_id' => $parent_id,
                'status' => 1,
                'user_id' => $user_id,
                'user_name' => $nick_name,
                'add_time' => $time
            ];
            $res = Comment::insert($data);
        } else {
            // 回复其他
            if ($reply_type == 'reply_other') {
                $data['quote_id'] = $quote_id;
            }
            $data['dis_text'] = $dis_text;
            $data['user_id'] = $user_id;
            $data['user_name'] = $nick_name;
            $data['parent_id'] = $parent_id;
            $data['add_time'] = $time;
            $data['quote_id'] = $quote_id;
            $data['dis_type'] = 0;
            $data['goods_id'] = 0;

            $res = DiscussCircle::insert($data);
        }
        if ($res) {
            return ['error' => 0, 'msg' => lang('discover.success')];
        } else {
            return ['error' => 1, 'msg' => lang('discover.underfind')];
        }
    }

    /**
     * 我的帖子
     *
     * @param int $dis_type
     * @param int $page
     * @param int $size
     * @param $uid
     * @return array
     * @throws \Exception
     */
    public function My($dis_type = 1, $page = 1, $size = 10, $uid)
    {
        if ($dis_type == 4) {
            $res['list'] = $this->comment_list($uid, $page, $size);
        } else {
            $res['list'] = $this->community_list($uid, $dis_type, $page, $size);
        }
        //用户信息
        $where['user_id'] = $uid;
        $arr = $this->userService->userInfo($where);

        $res = [];
        $res['user_name'] = setAnonymous(isset($arr['nick_name']) ? $arr['nick_name'] : $arr['user_name']);
        $res['avatar'] = $this->dscRepository->getImagePath($arr['user_picture'], '', asset('img/user_default.png'));
        $res['has_new'] = $this->reply_has_new($uid);
        $res['type1_num'] = $this->community_num(1, 0, $uid);
        $res['type2_num'] = $this->community_num(2, 0, $uid);
        $res['type3_num'] = $this->community_num(3, 0, $uid);
        $res['type4_num'] = $this->sd_count($uid);
        $res['dis_type'] = $dis_type;
        $res['page_title'] = lang('discover.my_discover');

        return $res;
    }

    /**
     *回复我的
     */
    public function Reply($uid, $page, $size)
    {
        $res = DiscussCircle::from('discuss_circle as dc')
            ->select('dc.user_id', 'dc.dis_text', 'dc.add_time', 'dc.parent_id', 'dc2.dis_type', 'dc2.dis_title as main_title')
            ->leftjoin('discuss_circle as dc2', 'dc.parent_id', 'dc2.dis_id')
            ->leftjoin('users as u', 'dc2.user_id', 'u.user_id')
            ->where('dc.user_id', '<>', $uid)
            ->where('dc.parent_id', '<>', 0)
            ->where('dc.dis_type', 0)
            ->where('dc.review_status', 3)
            ->orderBy('dc.add_time', 'DESC')
            ->offset(($page - 1) * $size)
            ->limit($size);

        $list = $this->baseRepository->getToArrayGet($res);
        if ($list) {
            foreach ($list as $k => $v) {
                $usersnick = $this->get_wechat_user_info($v['user_id']);
                $list[$k]['user_name'] = isset($usersnick['nick_name']) ? $this->encrypt_username($usersnick['nick_name']) : '';
                $list[$k]['user_picture'] = $this->dscRepository->getImagePath($usersnick['user_picture'], '', asset('img/user_default.png'));
                $list[$k]['add_time'] = $this->mdate($v['add_time']);
            }
        }

        return $list;
    }

    /**
     * 删除帖子
     */
    public function DeleteMycom($uid, $dis_type, $dis_id)
    {
        $result = [];

        if (!empty($dis_type) && !empty($dis_id)) {
            if ($dis_type == 4) {
                //如果是晒单贴删除是改变审核状态为不可见
                $data['status'] = 0;
                $condition['comment_id'] = $dis_id;
                $condition['user_id'] = $uid;
                $res = Comment::select('*')
                    ->where('comment_id', $dis_id)
                    ->where('user_id', $uid);
                $res = $this->baseRepository->getToArrayFirst($res);
                if (!$res) {
                    $result['error'] = 1;
                    $result['msg'] = lang('discover.no_delete');
                } else {
                    Comment::where('comment_id', $dis_id)
                        ->where('user_id', $uid)
                        ->update(['status' => 0]);
                    $result['error'] = '0';
                    $result['msg'] = lang('discover.delete_success');
                }
            } else {
                //不可以删除别人的帖子
                $condition['dis_id'] = $dis_id;
                $condition['user_id'] = $uid;
                $res = DiscussCircle::select('*')
                    ->where('dis_id', $dis_id)
                    ->where('user_id', $uid);
                $res = $this->baseRepository->getToArrayFirst($res);

                if (!$res) {
                    $result['error'] = 1;
                    $result['msg'] = lang('discover.no_delete');
                } else {
                    //如果是其他贴是物理删除 及其子回帖
                    $res = DiscussCircle::where('dis_id', $dis_id)
                        ->orWhere('parent_id', $dis_id)
                        ->delete();
                    if ($res) {
                        $result['error'] = 0;
                        $result['msg'] = lang('discover.delete_success');
                    }
                }
            }
            return $result;
        }
    }

    /**
     * 点赞帖子
     *
     * @param int $user_id
     * @param int $dis_type
     * @param int $dis_id
     * @return array
     * @throws \Exception
     */
    public function like($user_id = 0, $dis_type = 0, $dis_id = 0)
    {
        if ($dis_type) {
            $comment_type = Comment::whereRaw(1);
            $discuss_type = DiscussCircle::whereRaw(1);
            $cache_id = $dis_type == 4 ? 'comment_likenum' . '_' . $user_id . '_' . $dis_id : 'discusscircle_likenum' . '_' . $user_id . '_' . $dis_id;
            $result = cache($cache_id);
            if ($result) {
                $likenum = $dis_type == 4 ? $comment_type->where('comment_id', $dis_id)->value('like_num') : $discuss_type->where('dis_id', $dis_id)->value('like_num');
                $date = [
                    'error' => 0,
                    'like_num' => $likenum,
                    'is_like' => 1,
                    'msg' => lang('discover.is_like_success')
                ];
            } else {
                $res = $dis_type == 4 ? $comment_type->select('like_num')->where('comment_id', $dis_id) : $discuss_type->select('like_num')->where('dis_id', $dis_id);
                $result = $this->baseRepository->getToArrayFirst($res);
                $res->update(['like_num' => $result['like_num'] + 1]);
                //写入缓存
                $result = ['dis_id' => $dis_id, 'like_num' => 1];
                cache()->forever($cache_id, $result);
                $likenum = $dis_type == 4 ? $comment_type->where('comment_id', $dis_id)->value('like_num') : $discuss_type->where('dis_id', $dis_id)->value('like_num');
                $date = [
                    'error' => 0,
                    'like_num' => $likenum,
                    'is_like' => 1,
                    'msg' => lang('discover.like_success')
                ];
            }

            return $date;
        }
    }

    /**
     * 讨论贴 $type = 1 问答帖 $type = 2 圈子帖 $type = 3
     * @param
     */
    public function community_list($uid, $type = 0, $page = 1, $size = 10, $goods_id = 0)
    {
        $res = DiscussCircle::from('discuss_circle as d')
            ->select('d.*', 'u.user_id', 'u.user_name', 'u.nick_name', 'u.user_picture', 'g.goods_name')
            ->leftjoin('users as u', 'd.user_id', 'u.user_id')
            ->leftjoin('goods as g', 'd.goods_id', 'g.goods_id')
            ->where('d.parent_id', 0)
            ->where('d.user_id', '<>', 0)
            ->where('d.goods_id', '<>', 0)
            ->where('d.dis_type', '<>', 4)
            ->where('d.review_status', 3)
            ->where('g.is_on_sale', 1)
            ->where('g.is_alone_sale', 1)
            ->where('g.is_delete', 0)
            ->where('g.is_show', 1);

        if ($type) {
            if ($type == 'all') {
                $res = $res->whereIn('d.dis_type', [1, 2, 3]);
            } else {
                $res = $res->where('d.dis_type', $type);
            }
        }
        if ($uid > 0) {
            $res = $res->where('u.user_id', $uid);
        }

        if ($goods_id > 0) {
            $res = $res->where('d.goods_id', $goods_id);
        }

        $start = ($page - 1) * $size;
        $res = $res->offset($start)->limit($size)->orderBy('d.add_time', 'desc');

        $list = $this->baseRepository->getToArrayGet($res);
        $total = $this->community_num($type, 0, $uid, $goods_id);
        if ($list) {
            foreach ($list as $k => $v) {
                $list[$k]['add_time'] = $this->mdate($v['add_time']);
                $users = $this->get_wechat_user_info($v['user_id']);
                $users['user_picture'] = $users['user_picture'] ?? '';
                $list[$k]['user_name'] = isset($users['nick_name']) ? $this->encrypt_username($users['nick_name']) : '';
                $list[$k]['user_picture'] = $this->dscRepository->getImagePath($users['user_picture'], '', asset('img/user_default.png'));
                $list[$k]['community_num'] = $this->community_num(0, $v['dis_id']);
                $list[$k]['delete_com'] = ($uid == $v['user_id']) ? 1 : 0; // 是否显示删除按钮
                $list[$k]['dis_text'] = $this->dscRepository->subStr(strip_tags($this->html_out($v['dis_text'])), 50);
            }
        }
        return $list;
    }

    /**
     * 晒单列表
     */
    public function comment_list($uid, $page = 1, $size = 10)
    {
        $res = Comment::from('comment as cmt')
            ->select('cmt.like_num', 'cmt.comment_id as new_dis_id', 'cmt.id_value', 'cmt.useful', 'cmt.parent_id', 'cmt.content', 'cmt.order_id', 'cmt.add_time', 'cmt.user_id', 'cmt2.comment_img', 'cmt.dis_browse_num', 'cmt2.goods_id')
            ->leftjoin('comment_img as cmt2', 'cmt2.comment_id', 'cmt.comment_id')
            ->leftjoin('users as u', 'cmt.user_id', 'u.user_id')
            ->where('cmt2.comment_img', '<>', '')
            ->where('cmt.comment_id', '<>', 0)
            ->where('cmt.status', 1);
        if ($uid) {
            $res = $res->where('cmt.user_id', $uid);
        }

        $res = $res->groupBy('new_dis_id')->offset(($page - 1) * $size)->limit($size);
        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $k => $v) {
                $res[$k]['add_time'] = $this->mdate($v['add_time']);
                $res[$k]['dis_browse_num'] = $v['dis_browse_num'] ? $v['dis_browse_num'] : 0;
                $users = $this->get_wechat_user_info($v['user_id']);
                $res[$k]['user_name'] = isset($users['nick_name']) ? $this->encrypt_username($users['nick_name']) : '';
                $res[$k]['user_picture'] = $this->dscRepository->getImagePath($users['user_picture'], '', asset('img/user_default.png'));
                $res[$k]['dis_type'] = 4;
                $res[$k]['community_num'] = $this->comment_num(4, $v['new_dis_id']);
                $res[$k]['dis_title'] = $this->dscRepository->subStr($v['content'], 20); //晒单贴没有标题，从内容截取
                $res[$k]['delete_com'] = ($uid == $v['user_id']) ? 1 : 0; // 是否显示删除按钮
                $res[$k]['dis_text'] = $this->dscRepository->subStr(strip_tags($this->html_out($v['content'])), 50);
                $res[$k]['dis_id'] = $v['new_dis_id'];
            }
        }

        return $res;
    }


    /**
     * 晒单帖总条数
     */
    public function sd_count($user_id = '')
    {
        $res = Comment::from('comment as cmt')->selectRaw('count(*) as count')
            ->leftjoin('comment_img as cmt2', 'cmt2.comment_id', 'cmt.comment_id')
            ->leftjoin('users as u', 'cmt.user_id', 'u.user_id')
            ->where('cmt2.comment_img', '<>', '')
            ->where('cmt.comment_id', '<>', 0)
            ->where('cmt.status', 1)
            ->groupBy('cmt.comment_id');

        if ($user_id) {
            $res = $res->where('cmt.user_id', $user_id);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        return count($res);
    }

    /**
     * 查询三种类型帖子的数量
     *
     * @param int $type
     * @param int $parent_id
     * @param int $user_id
     * @param int $goods_id
     * @return mixed
     */
    public function community_num($type = 0, $parent_id = 0, $user_id = 0, $goods_id = 0)
    {
        $res = DiscussCircle::where('user_id', '<>', 0)
            ->where('parent_id', $parent_id)
            ->where('review_status', 3);
        if ($type) {
            $res = $res->where('goods_id', '<>', 0)->where('dis_type', $type);
        }

        if ($user_id > 0) {
            $res = $res->where('user_id', $user_id);
        }

        if ($goods_id > 0) {
            $res = $res->where('goods_id', $goods_id);
        }
        $res = $res->count();
        return $res;
    }

    /**
     * 是否有新帖子
     */
    public function community_has_new($uid, $type = 0, $comment = 0)
    {
        if ($uid) {
            //如果是晒单
            if ($comment) {
                $res = Comment::where('status', 1)
                    ->where('parent_id', 0);
            } else {
                $res = DiscussCircle::where('user_id', '<>', 0)
                    ->where('review_status', 3);
                if ($type) {
                    $res = $res->where('dis_type', $type);
                }
            }
            $res = $res->count();
            if ($res && $res > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * 格式化时间函数
     * @param  [type] $time 时间戳
     * @return [type]
     */
    public function mdate($time = null)
    {
        $now_time = $this->timeRepository->getGmTime();
        $time = $time === null || $time > $time ? $time : intval($time);
        $t = $now_time - $time; //时间差 （秒）
        $y = $this->timeRepository->getLocalDate('Y', $now_time) - $this->timeRepository->getLocalDate('Y', $time);//是否跨年
        switch ($t) {
            case $t == 0:
                $text = '刚刚';
                break;
            case $t < 60:
                $text = $t . '秒前'; // 一分钟内
                break;
            case $t < 60 * 60:
                $text = floor($t / 60) . '分钟前'; //一小时内
                break;
            case $t < 60 * 60 * 24:
                $text = floor($t / (60 * 60)) . '小时前'; // 一天内
                break;
            case $t < 60 * 60 * 24 * 3:
                $text = floor($time / (60 * 60 * 24)) == 1 ? '昨天 ' . $this->timeRepository->getLocalDate('H:i', $time) : '前天 ' . $this->timeRepository->getLocalDate('H:i', $time); //昨天和前天
                break;
            case $t < 60 * 60 * 24 * 30:
                $text = $this->timeRepository->getLocalDate('m月d日 H:i', $time); //一个月内
                break;
            case $t < 60 * 60 * 24 * 365 && $y == 0:
                $text = $this->timeRepository->getLocalDate('m月d日', $time); //一年内
                break;
            default:
                $text = $this->timeRepository->getLocalDate('Y年m月d日', $time); //一年以前
                break;
        }

        return $text;
    }

    /**
     * 获取微信用户信息数组
     *
     * @param int $id
     * @return array
     */
    public function get_wechat_user_info($id = 0)
    {
        $user = [];

        if ($id > 0) {
            $result = Users::select('user_name', 'nick_name', 'user_picture')
                ->where('user_id', $id);

            $result = $this->baseRepository->getToArrayFirst($result);

            $user['nick_name'] = isset($result['nick_name']) ? $result['nick_name'] : isset($result['user_name']) ? $result['user_name'] : '';
            $user['user_picture'] = isset($result['user_picture']) ? $result['user_picture'] : '';
        }

        return $user;
    }

    /**
     * 晒单帖回复数量
     *
     * @param int $type
     * @param int $parent_id
     * @param int $user_id
     * @param int $goods_id
     * @return mixed
     */
    public function comment_num($type = 0, $parent_id = 0, $user_id = 0, $goods_id = 0)
    {
        $res = Comment::where('status', 1)
            ->where('user_id', '>', 0)
            ->where('parent_id', $parent_id);

        if ($type) {
            $res = $res->where('comment_type', $type);
        }
        if ($user_id > 0) {
            $res = $res->where('user_id', $user_id);
        }
        if ($goods_id > 0) {
            $res = $res->where('id_value', $goods_id);
        }

        $res = $res->count();

        return $res;
    }

    /**
     * html代码输出
     *
     * @param string $str
     * @return string
     */
    public function html_out($str = '')
    {
        if (function_exists('htmlspecialchars_decode')) {
            $str = htmlspecialchars_decode($str);
        } else {
            $str = html_entity_decode($str);
        }
        $str = stripslashes($str);
        return $str;
    }

    /**
     * 晒单图片
     *
     * @param int $id
     * @param int $comment_id
     * @return mixed
     */
    public function get_img_list($id = 0, $comment_id = 0)
    {
        $res = CommentImg::select('comment_id', 'comment_img', 'img_thumb')
            ->where('goods_id', $id)
            ->where('comment_id', $comment_id);

        $res = $this->baseRepository->getToArrayGet($res);

        return $res;
    }

    /**
     * 晒单帖回复列表
     *
     * @param $comment_id
     * @param int $parent_id
     * @param int $goods_id
     * @return mixed
     */
    public function get_comment_reply($comment_id, $parent_id = 0, $goods_id = 0)
    {
        $res = Comment::select('add_time', 'user_id', 'comment_id as dis_id', 'comment_type', 'content as dis_text')
            ->where('parent_id', $comment_id)
            ->where('comment_type', 2)
            ->where('status', 1)
            ->orderBy('add_time', 'desc');

        $res = $this->baseRepository->getToArrayGet($res);

        foreach ($res as $k => $v) {
            $usersnick = $this->get_wechat_user_info($v['user_id']);
            $res[$k]['user_name'] = $v['user_id'] == 0 ? '管理员' : (isset($usersnick['nick_name']) ? $this->encrypt_username($usersnick['nick_name']) : '');
        }

        return $res;
    }

    /**
     * 圈子帖回复列表
     * @param integer $dis_id
     * @param integer $parent_id
     * @return
     */
    public function get_quote_reply($dis_id, $parent_id = 0)
    {
        $res = DiscussCircle::select('user_name', 'dis_text', 'user_id')
            ->where('parent_id', $parent_id)
            ->where('quote_id', $dis_id)
            ->where('review_status', 3)
            ->orderBy('add_time', 'desc');

        $res = $this->baseRepository->getToArrayGet($res);


        foreach ($res as $k => $v) {
            $usersnick = $this->get_wechat_user_info($v['user_id']);
            $res[$k]['user_name'] = isset($usersnick['nick_name']) ? $this->encrypt_username($usersnick['nick_name']) : '';
        }

        return $res;
    }

    /**
     * 检测帖子类型
     * @return
     */
    public function checkDistype($dis_type)
    {
        if (!in_array($dis_type, [0, 1, 2, 3, 4])) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 是否有新回复
     */
    public function reply_has_new($uid)
    {
        $time = $this->timeRepository->getGmTime();

        $community_reply = $time + 3600 * 24;

        $num = DiscussCircle::from('discuss_circle as dc')
            ->leftjoin('discuss_circle as dc2', 'dc.parent_id', 'dc2.dis_id')
            ->where('dc.user_id', '<>', $uid)
            ->where('dc2.user_id', $uid)
            ->where('dc.parent_id', '<>', 0)
            ->where('dc.add_time', '>', $community_reply)
            ->where('dc.review_status', 3)
            ->count();

        if ($num && $num > 0) {
            return true;
        }

        return false;
    }

    /**
     *
     * 重定义用户名
     * end
     */
    public function encrypt_username($username)
    {
        $username_start = mb_substr($username, 0, 1, 'utf-8');
        $username_end = mb_substr($username, -1, 1, 'utf-8');
        $username_new = $username_start . '****' . $username_end;
        return $username_new;
    }
}
