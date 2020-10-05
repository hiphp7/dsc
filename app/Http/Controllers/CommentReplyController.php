<?php

namespace App\Http\Controllers;

use App\Services\Comment\CommentService;

/**
 * 提交用户评论
 */
class CommentReplyController extends InitController
{
    protected $commentService;

    public function __construct(
        CommentService $commentService
    )
    {
        $this->commentService = $commentService;
    }

    public function index()
    {
        $result = ['error' => 0, 'message' => '', 'content' => ''];

        $id = json_str_iconv(request()->input('id', 0));
        $type = (int)request()->input('type', 0);
        $page = (int)request()->input('page', 1);
        $libType = (int)request()->input('libType', 0);

        if ($result['error'] == 0) {
            $id = explode("|", $id);

            $goods_id = $id[0];
            $comment_id = $id[1];

            if ($libType == 1) {
                $comment_reply = $this->commentService->getReplyList($goods_id, $comment_id, $type, $page, $libType, 10);
            } else {
                $comment_reply = $this->commentService->getReplyList($goods_id, $comment_id, $type, $page, $libType);
            }

            $this->smarty->assign('comment_type', $type);
            $this->smarty->assign('goods_id', $goods_id);
            $this->smarty->assign('comment_id', $comment_id);
            $this->smarty->assign('reply_list', $comment_reply['reply_list']);
            $this->smarty->assign('reply_pager', $comment_reply['reply_pager']);
            $this->smarty->assign('reply_count', $comment_reply['reply_count']);
            $this->smarty->assign('reply_size', $comment_reply['reply_size']);

            $result['comment_id'] = $comment_id;

            if ($libType == 1) {
                $result['content'] = $this->smarty->fetch("library/comment_repay.lbi");
            } else {
                $result['content'] = $this->smarty->fetch("library/comment_reply.lbi");
            }
        }

        return response()->json($result);
    }
}
