<?php

namespace App\Services\User;


use App\Models\Feedback;
use App\Models\Goods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;

class UserMessageService
{
    protected $baseRepository;
    protected $dscRepository;
    protected $timeRepository;
    protected $config;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        TimeRepository $timeRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->timeRepository = $timeRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 获取留言的详细信息
     *
     * @param integer $num
     * @param integer $start
     *
     * @return  array
     */
    public function getMsgList($num, $start)
    {
        /* 获取留言数据 */
        $msg = [];

        $res = Feedback::where('parent_id', 0)
            ->where('msg_area', 1)
            ->where('msg_status', 1);

        if ($start > 0) {
            $res = $res->skip($start);
        }

        if ($num > 0) {
            $res = $res->take($num);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $rows) {
                $msg[$rows['msg_time']]['user_name'] = htmlspecialchars($rows['user_name']);
                $msg[$rows['msg_time']]['msg_content'] = str_replace('\r\n', '<br />', htmlspecialchars($rows['msg_content']));
                $msg[$rows['msg_time']]['msg_content'] = str_replace('\n', '<br />', $msg[$rows['msg_time']]['msg_content']);
                $msg[$rows['msg_time']]['msg_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $rows['msg_time']);
                $msg[$rows['msg_time']]['msg_type'] = $GLOBALS['_LANG']['message_type'][$rows['msg_type']];
                $msg[$rows['msg_time']]['msg_title'] = nl2br(htmlspecialchars($rows['msg_title']));
                $msg[$rows['msg_time']]['message_img'] = $rows['message_img'];
                $msg[$rows['msg_time']]['tablename'] = $rows['tablename'];

                if (isset($rows['order_id'])) {
                    $msg[$rows['msg_time']]['order_id'] = $rows['order_id'];
                }
                $msg[$rows['msg_time']]['comment_rank'] = $rows['comment_rank'];
                $msg[$rows['msg_time']]['id_value'] = $rows['id_value'];

                /* 如果id_value为true为商品评论,根据商品id取出商品名称 */
                if ($rows['id_value']) {
                    $goods_name = Goods::where('goods_id', $rows['id_value'])->value('goods_name');
                    $msg[$rows['msg_time']]['goods_name'] = $goods_name;

                    $msg[$rows['msg_time']]['goods_url'] = $this->dscRepository->buildUri('goods', ['gid' => $rows['id_value']], $rows['goods_name']);
                }

                $reply = Feedback::where('parent_id', $rows['msg_id']);
                $reply = $this->baseRepository->getToArrayFirst($reply);

                if ($reply) {
                    $msg[$rows['msg_time']]['re_name'] = $reply['user_name'];
                    $msg[$rows['msg_time']]['re_email'] = $reply['user_email'];
                    $msg[$rows['msg_time']]['re_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $reply['msg_time']);
                    $msg[$rows['msg_time']]['re_content'] = nl2br(htmlspecialchars($reply['msg_content']));
                }
            }
        }

        return $msg;
    }
}
