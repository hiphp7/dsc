<?php

namespace App\Http\Controllers;


/**
 * 提交用户评论
 */
class CommentSingleController extends InitController
{
    public function index()
    {
        $result = ['error' => 0, 'message' => '', 'content' => ''];

        $id = json_str_iconv(request()->input('id', 0));
        $type = (int)request()->input('type', 0);
        $page = (int)request()->input('page', 1);

        if ($result['error'] == 0) {
            $comments = assign_comments_single($id, $type, $page);

            $this->smarty->assign('comment_type', $type);
            $this->smarty->assign('id', $id);
            $this->smarty->assign('username', session('user_name'));
            $this->smarty->assign('email', session('email'));
            $this->smarty->assign('comments_single', $comments['comments']);
            $this->smarty->assign('single_pager', $comments['pager']);

            /* 验证码相关设置 */
            if ((intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_COMMENT) && gd_version() > 0) {
                $this->smarty->assign('enabled_captcha', 1);
                $this->smarty->assign('rand', mt_rand());
            }

            $result['message'] = $GLOBALS['_CFG']['comment_check'] ? $GLOBALS['_LANG']['cmt_submit_wait'] : $GLOBALS['_LANG']['cmt_submit_done'];
            $result['content'] = $this->smarty->fetch("library/comments_single_list.lbi");
        }

        return response()->json($result);
    }
}
