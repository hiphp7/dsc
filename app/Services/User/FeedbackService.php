<?php

namespace App\Services\User;

use App\Models\Feedback;
use App\Models\OrderInfo;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Order\OrderService;

/**
 * 留言
 * Class Feedback
 * @package App\Services
 */
class FeedbackService
{
    protected $orderService;
    protected $config;
    protected $timeRepository;
    protected $userCommonService;
    protected $userPagerService;
    protected $dscRepository;
    protected $baseRepository;

    public function __construct(
        OrderService $orderService,
        TimeRepository $timeRepository,
        UserCommonService $userCommonService,
        UserPagerService $userPagerService,
        DscRepository $dscRepository,
        BaseRepository $baseRepository
    )
    {
        $this->orderService = $orderService;
        $this->timeRepository = $timeRepository;
        $this->userCommonService = $userCommonService;
        $this->userPagerService = $userPagerService;
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 留言列表
     *
     * @param int $user_id
     * @param int $page
     * @param int $size
     * @param int $order_id
     * @return array
     * @throws \Exception
     */
    public function getMessageList($user_id = 0, $page = 1, $size = 10, $order_id = 0)
    {
        $page = isset($page) ? intval($page) : 1;
        $order_id = empty($order_id) ? 0 : intval($order_id);

        $info = $this->userCommonService->getUserDefault($user_id);

        $info['user_picture'] = $this->dscRepository->getImagePath($info['user_picture']);
        $user_name = $info['username'];

        $order_info = [];

        /* 获取用户留言的数量 */
        if ($order_id) {
            $record_count = Feedback::where('parent_id', 0)->where('order_id', $order_id)->where('user_id', $user_id)->count();
            $order_info = OrderInfo::where('order_id', $order_id)->where('user_id', $user_id)->first();
            $order_info = !empty($order_info) ? $order_info->toArray() : [];
        } else {
            $record_count = Feedback::where('user_id', $user_id)->where('user_name', $user_name)->where('order_id', 0)->count();
        }

        $action = '';
        $act = ['act' => $action];

        if ($order_id != '') {
            $act['order_id'] = $order_id;
        }

        $pager = $this->userPagerService->getPager('user.php', $act, $record_count, $page, $size);

        $message_list = $this->getMessage($user_id, $pager['size'], $pager['start'], $order_id, $user_name);

        ksort($message_list);

        $arr = ['info' => $info, 'message_list' => $message_list, 'pager' => $pager, 'order_info' => $order_info];
        return $arr;
    }

    public function getMessage($user_id, $size = 0, $start = 0, $order_id = 0, $user_name = '')
    {
        /* 获取留言数据 */
        $res = Feedback::where('parent_id', 0)
            ->where('user_id', $user_id);

        if ($order_id) {
            $res = $res->where('order_id', $order_id);
        } else {
            $res = $res->where('order_id', 0);
            $res = $res->where('user_name', $user_name);
        }

        $res = $res->orderBy('msg_time', 'desc');

        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($size > 0) {
            $res = $res->take($size);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        $msg = [];
        if ($res) {
            $res = $this->baseRepository->getSortBy($res, 'msg_time');
            foreach ($res as $key => $rows) {
                $reply = Feedback::where('parent_id', $rows['msg_id']);
                $reply = $this->baseRepository->getToArrayFirst($reply);

                if ($reply) {
                    $msg[$key]['re_user_name'] = $reply['user_name'];
                    $msg[$key]['re_user_email'] = $reply['user_email'];
                    $msg[$key]['re_msg_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $reply['msg_time']);
                    $msg[$key]['re_msg_content'] = nl2br(htmlspecialchars($reply['msg_content']));
                }

                $msg[$key]['msg_content'] = nl2br(htmlspecialchars($rows['msg_content']));
                $msg[$key]['msg_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $rows['msg_time']);
                $msg[$key]['msg_type'] = nl2br(htmlspecialchars($rows['msg_type']));
                $msg[$key]['msg_title'] = nl2br(htmlspecialchars($rows['msg_title']));

                //判断上传的文件类型
                $message_type = pathinfo($rows['message_img'], PATHINFO_EXTENSION);
                if (in_array($message_type, ['gif', 'jpg', 'png'])) {
                    $msg[$key]['message_type'] = 1;
                }
                $msg[$key]['message_img'] = $rows['message_img'];
                $msg[$key]['order_id'] = $rows['order_id'];
                $msg[$key]['order_sn'] = isset($rows['order_sn']) ? $rows['order_sn'] : '';
            }
        }
        return $msg;
    }

    /**
     * 添加留言
     *
     * @param int $user_id
     * @param array $data
     * @return array
     */
    public function addFeedBack($user_id = 0, $data = [])
    {
        $user_info = Users::select('email', 'user_name', 'nick_name')->where('user_id', $user_id);
        $user_info = $this->baseRepository->getToArrayFirst($user_info);

        if ($user_info) {
            $data['user_email'] = $user_info['email'];
            $data['msg_time'] = $this->timeRepository->getGmTime();
            $data['user_name'] = $user_info['user_name'];
            $data['user_id'] = $user_id;
            Feedback::insert($data);
            $result = ['code' => 1, 'msg' => $GLOBALS['_LANG']['Add_success']];
            return $result;
        } else {
            $result = ['code' => 0, 'msg' => $GLOBALS['_LANG']['parameter_error']];
            return $result;
        }
    }
}
