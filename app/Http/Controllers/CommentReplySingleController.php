<?php

namespace App\Http\Controllers;

/**
 * 提交用户评论
 */
class CommentReplySingleController extends InitController
{
    public function index()
    {
        $result = ['error' => 0, 'message' => '', 'content' => ''];

        $id = json_str_iconv(request()->input('id', 0));
        $type = (int)request()->input('type', 0);
        $page = (int)request()->input('page', 1);
        $libType = (int)request()->input('libType', 0);

        if ($result['error'] == 0) {
            if ($libType == 1) {
                $reply_list = single_show_reply_list($id, $page);

                $this->smarty->assign('comment_list', $reply_list['comment_list']);
                $this->smarty->assign('reply_paper', $reply_list['reply_paper']);

                $result['content'] = $this->smarty->fetch("library/comment_reply_show.lbi");
            } else {
                $single_reply = assign_comments_single_reply($id, $type, $page);
                $reply_comments = $single_reply['reply_comments'];
                $reply_paper = $single_reply['reply_paper'];

                $this->smarty->assign('reply_comments', $reply_comments);
                $this->smarty->assign('reply_paper', $reply_paper);

                $result['comment_id'] = $id;
                $result['content'] = $this->smarty->fetch("library/comment_reply_list.lbi");
            }
        }

        return response()->json($result);
    }
}
