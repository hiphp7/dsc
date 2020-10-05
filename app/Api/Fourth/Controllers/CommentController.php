<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Services\Comment\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class CommentController
 * @package App\Api\Fourth\Controllers
 */
class CommentController extends Controller
{
    protected $commentService;

    public function __construct(
        CommentService $commentService
    )
    {
        $this->commentService = $commentService;
    }

    /**
     * 商品评论数量
     * @param Request $request
     * @return JsonResponse
     */
    public function title(Request $request)
    {
        $goods_id = $request->input('goods_id');

        $data = $this->commentService->goodsCommentCount($goods_id);

        return $this->succeed($data);
    }

    /**
     * 商品评论列表
     * @param Request $request
     * @return JsonResponse
     */
    public function goods(Request $request)
    {
        $goods_id = $request->input('goods_id', 0);

        $rank = $request->input('rank', 'all');
        $page = (int)$request->input('page', 1);
        $size = (int)$request->input('size', 10);

        $data = $this->commentService->GoodsComment(0, $goods_id, $rank, $page, $size);

        return $this->succeed($data);
    }

    /**
     * 待评论列表
     * @param Request $request
     * @return JsonResponse
     */
    public function commentlist(Request $request)
    {
        $sign = $request->input('sign', 0); // 0：待评论 1：追加图片 2:已评论
        $page = $request->input('page', 1);
        $size = $request->input('size', 10);

        $uid = $this->authorization();

        $data = $this->commentService->getCommentList($uid, $sign, $page, $size);

        return $this->succeed($data);
    }

    /**
     * 商品评论页
     * @param Request $request
     * @return JsonResponse
     */
    public function ordergoods(Request $request)
    {
        $rec_id = $request->input('rec_id');

        $uid = $this->authorization();

        $data = $this->commentService->getOrderGoods($rec_id);

        return $this->succeed($data);
    }

    /**
     * 评论订单
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function addGoodsComment(Request $request)
    {
        //验证数据
        $this->validate($request, [
            'type' => 'required|integer',     // 评论类型
            'id' => 'required|integer',       // 商品id
            'rank' => 'required|integer',
            'server' => 'required|integer',
            'delivery' => 'required|integer',
            'order_id' => 'required|integer', // 订单id
            'rec_id' => 'required|integer',   // 订单商品id
        ]);

        $cmt = $request->all();
        $pic = $request->input('pic');

        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $data = $this->commentService->getAddGoodsComment($user_id, $cmt, $pic);

        return $this->succeed($data);
    }
}
