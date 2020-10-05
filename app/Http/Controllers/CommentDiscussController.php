<?php

namespace App\Http\Controllers;

/**
 * 提交用户评论
 * Class CommentDiscussController
 * @package App\Http\Controllers
 */
class CommentDiscussController extends InitController
{
    public function index()
    {
        if ($this->checkReferer() === false) {
            return response()->json(['error' => 1, 'message' => 'referer error']);
        }

        $result = ['error' => 0, 'message' => '', 'content' => ''];

        $id = json_str_iconv(request()->input('id'));

        $page = intval(request()->input('page', 1));

        if ($result['error'] == 0) {
            $id = explode("|", explode(' ', $id)[0]);

            $goods_id = isset($id[0]) ? $id[0] : 0;
            $dis_type = isset($id[1]) ? $id[1] : 0;
            $revType = isset($id[2]) ? $id[2] : 0;
            $sort = isset($id[3]) ? $id[3] : 'add_time';

            if ($revType) {
                $size = 10;
            } else {
                $size = 40;
            }

            if (!$sort) {
                $sort = 'add_time';
            }

            $discuss_list = get_discuss_all_list($goods_id, $dis_type, $page, $size, $revType, $sort);
            $this->smarty->assign('discuss_list', $discuss_list);

            if ($revType) {
                if ($dis_type == 4) {
                    $all_count = get_commentImg_count($goods_id); //帖子总数
                } else {
                    $all_count = get_discuss_type_count($goods_id, $revType); //帖子总数
                }
                $this->smarty->assign('all_count', $all_count);
                $this->smarty->assign('goods_id', $goods_id);
                $result['content'] = $this->smarty->fetch("library/comments_discuss_list1.lbi");
            } else {
                $result['content'] = $this->smarty->fetch("library/comments_discuss_list2.lbi");
            }
        }

        return response()->json($result);
    }
}
