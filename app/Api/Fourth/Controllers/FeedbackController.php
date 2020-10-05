<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Repositories\Common\DscRepository;
use App\Services\User\FeedbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * 留言
 * Class FeedbackController
 * @package App\Api\Fourth\Controllers
 */
class FeedbackController extends Controller
{
    protected $feedback;
    protected $dscRepository;

    public function __construct(
        FeedbackService $feedback,
        DscRepository $dscRepository
    )
    {
        $this->feedback = $feedback;
        $this->dscRepository = $dscRepository;
    }

    /**
     * 留言列表
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function index(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer',
        ]);
        /**
         * 获取会员id
         */
        $user_id = $this->authorization();

        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $page = $request->get('page', 1);
        $size = $request->get('size', 10);
        $order_id = $request->get('order_id', 0);

        //留言列表
        $message_list = $this->feedback->getMessageList($user_id, $page, $size, $order_id);

        return $this->succeed($message_list);
    }

    /**
     * 提交留言
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'msg_title' => 'required|string',
        ]);

        // 获取会员id
        $user_id = $this->authorization();

        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $data = [
            'msg_type' => $request->post('msg_type') ? intval($request->post('msg_type')) : 0,
            'msg_title' => $request->post('msg_title') ? trim($request->post('msg_title')) : '',
            'msg_content' => $request->post('msg_title') ? trim($request->post('msg_title')) : '',
            'order_id' => empty($request->post('order_id')) ? 0 : intval($request->post('order_id'))
        ];

        $this->dscRepository->helpersLang('comment');

        //提交留言
        $result = $this->feedback->addFeedBack($user_id, $data);

        return $this->succeed($result);
    }
}
