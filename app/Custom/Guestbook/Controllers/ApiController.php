<?php

namespace App\Custom\Guestbook\Controllers;

use App\Api\Foundation\Controllers\Controller as FrontController;
use App\Custom\Guestbook\Services\GuestbookService;
use Illuminate\Http\Request;

class ApiController extends FrontController
{
    protected $guestbookService;

    /**
     * IndexController constructor.
     * @param GuestbookService $guestbookService
     */
    public function __construct(
        GuestbookService $guestbookService
    )
    {
        $this->guestbookService = $guestbookService;
    }

    /**
     * 留言列表
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        $guestbook_list = $this->guestbookService->content();

        return $this->succeed($guestbook_list);
    }

    /**
     * 添加留言
     * @param Request $request
     * @return string
     */
    public function add(Request $request)
    {
        //验证数据
        $this->validate($request, [
            'id' => 'required|integer',
        ]);

        $data = [
            'page_title' => '留言板',
        ];
        return $this->succeed($data);
    }

    public function save(Request $request)
    {
        //验证数据
        $this->validate($request, [
            'content' => 'required|string',
        ]);

        $post = [
            'title' => request()->input('title'),
            'content' => request()->input('content')
        ];

        // 验证数据
        // todo

        // 保存数据
        // Guestbook::create($post);

        $result = ['data' => ''];
        return $this->succeed($result);
    }
}
