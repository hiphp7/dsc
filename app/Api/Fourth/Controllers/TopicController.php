<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Services\Activity\TopicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class TopicController
 * @package App\Api\Fourth\Controllers
 */
class TopicController extends Controller
{
    protected $topicService;

    /**
     * TopicController constructor.
     * @param TopicService $topicService
     */
    public function __construct(TopicService $topicService)
    {
        $this->topicService = $topicService;
    }

    /**
     * 专题列表
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function index(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer',
        ]);

        $page = $request->get('page', 1);
        $size = $request->get('size', 10);

        //专题列表
        $topicList = $this->topicService->getTopicList($page, $size);

        return $this->succeed($topicList);
    }

    /**
     * 专题详情
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function detail(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'topic_id' => 'required|integer',
        ]);

        $topic_id = $request->get('topic_id', 0);

        $data = $this->topicService->getDetail($this->uid, $topic_id, $this->warehouse_id, $this->area_id, $this->area_city);

        return $this->succeed($data);
    }
}
