<?php

namespace App\Custom\Guestbook\Controllers;

use App\Custom\Controller as FrontController;
use App\Custom\Guestbook\Services\GuestbookService;
use Illuminate\Http\Request;

class IndexController extends FrontController
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
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $guestbook_list = $this->guestbookService->content();

        $this->assign('guestbook_list', $guestbook_list);
        $this->assign('page_title', '留言板');
        return $this->display();
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

        $this->assign('page_title', '添加留言');
        return $this->display();
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

        // 页面跳转
        return redirect()->route('guestbook.index');
    }
}
