<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Compile;
use App\Models\AdminUser;
use App\Models\PicAlbum;
use App\Models\TouchPageView;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Visual\VisualService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * 手机端可视化管理 - 商家后台
 */
class TouchVisualController extends BaseController
{
    // 商家ID
    protected $ru_id = 0;

    protected $visualService;
    protected $config;
    protected $dscRepository;

    public function __construct(
        VisualService $visualService,
        DscRepository $dscRepository
    )
    {
        $this->visualService = $visualService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    protected function initialize()
    {
        load_helper(['function', 'ecmoban']);

        //验证商家可视化权限
        $seller_id = request()->session()->get('seller_id', 0);
        $ru_id = AdminUser::where(['user_id' => $seller_id])->value('ru_id');
        $get_ru_id = request()->input('ru_id', 0);
        $this->ru_id = (!empty($ru_id) && $get_ru_id == $ru_id) ? $get_ru_id : $ru_id;
        // 专题id
        $this->topic_id = request()->input('topic_id', 0);
    }

    /**
     * 编辑控制台
     * @post /seller/touch_visual/index
     */
    public function index()
    {
        if (request()->isMethod('get')) {
            $shopInfo = json_encode(['ruid' => $this->ru_id, 'type' => 'admin']);
            $this->assign('shopInfo', $shopInfo);
            $topic = json_encode(['topic_id' => $this->topic_id]);
            $this->assign('topic', $topic);
            return $this->display();
        }
        if (request()->isMethod('post')) {
            $view = TouchPageView::where('ru_id', $this->ru_id)
                ->where('default', 0)
                ->first();
            $view = $view ? $view->toArray() : [];
            return $view;
        }
    }

    /**
     * 显示页面
     * @post /seller/touch_visual/view
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function view(Request $request)
    {
        $id = $request->input('id', 0);
        $type = $request->input('type', 'index');
        $default = $request->input('default', 0);
        $ru_id = $request->input('ru_id', $this->ru_id);
        $number = $request->input('number', 10);
        $page_id = $request->input('page_id', 0);

        $view = $this->visualService->View($id, $type, $default, $ru_id, $number, $page_id);

        return response()->json($view);
    }

    /**
     * 公告
     * @post /seller/touch_visual/article
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function article(Request $request)
    {
        $cat_id = $request->input('cat_id', 0);

        $data = $this->visualService->Article($cat_id);

        return response()->json($data);
    }

    /**
     * 公告分类数量
     * @post /seller/touch_visual/articlelist
     * @return \Illuminate\Http\JsonResponse
     */
    protected function article_list()
    {
        $list = $this->visualService->article_tree(0);
        return response()->json(['error' => 0, 'list' => $list]);
    }

    /**
     * 商品列表模块
     * @post /seller/touch_visual/product
     * @return \Illuminate\Http\JsonResponse
     */
    protected function product(Request $request)
    {
        $number = $request->input('number', 10);
        $type = $request->input('type', '');
        $ru_id = $request->input('ru_id', $this->ru_id);
        $cat_id = $request->input('cat_id', 0);

        $data = $this->visualService->Product(0, $cat_id, $type, $ru_id, $number);

        return response()->json($data);
    }

    /**
     * 选中的商品
     * @post /seller/touch_visual/checked
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function checked(Request $request)
    {
        $goods_id = $request->input('goods_id', 0);
        $ru_id = $request->input('ru_id', $this->ru_id);
        $warehouse_id = $request->input('warehouse_id', 0);
        $area_id = $request->input('area_id', 0);
        $area_city = $request->input('area_city', 0);

        $data = $this->visualService->Checked($goods_id, $ru_id, $warehouse_id, $area_id, $area_city);

        return response()->json($data);
    }

    /**
     * 显示分类
     * @post /seller/touch_visual/category
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function category(Request $request)
    {
        $ru_id = $request->input('ru_id', 0);
        $cat_id = $request->input('cat_id', 0);

        $cateory = $this->visualService->store_category($cat_id, $ru_id, 0);

        return response()->json(['error' => 0, 'category' => $cateory]);
    }

    /**
     * 显示品牌
     * @post /seller/touch_visual/brand
     * @return \Illuminate\Http\JsonResponse
     */
    protected function brand(Request $request)
    {
        $num = $request->input('num', 100);
        $brand = $this->visualService->brand_list($num);

        return response()->json(['error' => 0, 'brand' => $brand]);
    }

    /**
     * 相册或图片
     * @post /seller/touch_visual/thumb
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function thumb(Request $request)
    {
        $type = $request->input('type', '');
        $ru_id = $request->input('ru_id', $this->ru_id);
        $album_id = $request->input('album_id', 0);
        $pageSize = $request->input('pageSize', 10);
        $currentPage = $request->input('currentPage', 1);

        $data = $this->visualService->get_thumb($type, $ru_id, $album_id, $pageSize, $currentPage);

        if ($type == 'thumb') {
            // 左侧相册列表
            return response()->json(['error' => 0, 'msg' => 'success', 'thumb' => $data['thumb'], 'total' => $data['total'], 'totalPage' => $currentPage]);
        }
        if ($type == 'img') {
            // 图片列表
            return response()->json(['error' => 0, 'msg' => 'success', 'img' => $data['img'], 'total' => $data['total'], 'totalPage' => $currentPage]);
        }

        return response()->json(['error' => 1, 'msg' => 'fail']);
    }

    /**
     * 营销、活动、分类、文章页面超链接
     * @post /seller/touch_visual/geturl
     * @return \Illuminate\Http\JsonResponse
     */
    protected function geturl(Request $request)
    {
        $ru_id = $request->input('ru_id', $this->ru_id);
        $type = $request->input('type', '');
        $pageSize = $request->input('pageSize', 10);
        $currentPage = $request->input('currentPage', 1);

        $data = $this->visualService->get_seller_url($type, $ru_id, $pageSize, $currentPage);

        if ($data) {
            $url = $data['url'] ?? '';
            $total = $data['total'] ?? '';
            return response()->json(['error' => 0, 'msg' => 'success', 'url' => $url, 'page' => $currentPage, 'total' => $total]);
        }

        return response()->json(['error' => 1, 'msg' => 'fail']);
    }

    /**
     * 秒杀模块
     * @post /seller/touch_visual/seckill
     * @return \Illuminate\Http\JsonResponse
     */
    protected function seckill(Request $request)
    {
        $number = $request->input('number', 10);

        $data = $this->visualService->Seckill($number);

        if ($data) {
            return response()->json(['error' => 0, 'msg' => 'success', 'seckill' => $data]);
        }
        return response()->json(['error' => 1, 'msg' => 'fail']);
    }

    /**
     * 店铺街
     * @post /seller/touch_visual/store
     * @return \Illuminate\Http\JsonResponse
     */
    protected function store(Request $request)
    {
        $childrenNumber = $request->input('childrenNumber', 3);
        $number = $request->input('number', 10);

        $data = $this->visualService->Store($childrenNumber, $number);

        if ($data) {
            return response()->json(['error' => 0, 'msg' => 'success', 'store' => $data]);
        }

        return response()->json(['error' => 1, 'msg' => 'fail']);
    }

    /**
     * 店铺街详情
     * @post /seller/touch_visual/storeIn
     * @return \Illuminate\Http\JsonResponse
     */
    protected function storeIn(Request $request)
    {
        $ru_id = $request->input('ru_id', $this->ru_id);
        $uid = $request->input('uid', 0);

        $data = $this->visualService->StoreIn($ru_id, $uid);

        if ($data) {
            return response()->json(['error' => 0, 'msg' => 'success', 'store' => $data]);
        }

        return response()->json(['error' => 1, 'msg' => 'fail']);
    }

    /**
     * 店铺街详情底部
     * @post /seller/touch_visual/storeDown
     * @return \Illuminate\Http\JsonResponse
     */
    protected function storeDown(Request $request)
    {
        $ru_id = $request->input('ru_id', $this->ru_id);

        $data = $this->visualService->StoreDown($ru_id);

        if ($data) {
            return response()->json(['error' => 0, 'msg' => 'success', 'store' => $data]);
        }

        return response()->json(['error' => 1, 'msg' => 'fail']);
    }

    /**
     * 默认
     * @post /seller/touch_visual/default_index
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    protected function default_index(Request $request)
    {
        $ru_id = $request->input('ru_id', $this->ru_id);
        $type = $request->input('type', '');

        $data = $this->visualService->Default($ru_id, $type);

        return response()->json($data);
    }

    /**
     * 保存模块预览配置 - 文件
     * @post /seller/touch_visual/previewModule
     * @return \Illuminate\Http\JsonResponse
     */
    protected function previewModule()
    {
        $data = request()->input('data');
        if (!empty($data)) {
            $data = $this->visualService->transform($data);
            Compile::setModule('preview', $data);
            return response()->json(['error' => 0, 'data' => $data]);
        }
        return response()->json(['error' => 1, 'msg' => 'fail']);
    }

    /**
     * 保存模块配置 - 文件
     * @post /seller/touch_visual/saveModule
     * @return \Illuminate\Http\JsonResponse
     */
    protected function saveModule()
    {
        $data = request()->input('data');
        if (!empty($data)) {
            $data = $this->visualService->transform($data);
            Compile::setModule('index', $data);
            return response()->json(['error' => 0, 'data' => $data]);
        }
        return response()->json(['error' => 1, 'msg' => 'fail']);
    }

    /**
     * 清除模块已有配置 - 文件
     * @post /seller/touch_visual/cleanModule
     * @return \Illuminate\Http\JsonResponse
     */
    protected function cleanModule()
    {
        if (Compile::cleanModule()) {
            return response()->json(['error' => 0, 'msg' => 'success']);
        }
        return response()->json(['error' => 1, 'msg' => 'fail']);
    }

    /**
     * 还原模块配置 商家此接口无用
     * @post /seller/touch_visual/restore
     * @return \Illuminate\Http\JsonResponse
     */
    protected function restore()
    {
        $ru_id = request()->input('ru_id', $this->ru_id);
        $data = str_replace('<?php exit("no access");', '', file_get_contents(storage_path('app/diy/default.php')));
        if ($data) {
            $keep = [
                'type' => 'index',
                'title' => lang('seller/common.00_home'),
                'data' => $data,
            ];
            if ($ru_id == 0) {
                return response()->json(['error' => 0, 'keep' => $keep]);
            }
        }

        return response()->json(['error' => 1, 'msg' => 'fail']);
    }

    /**
     * 保存配置 - 数据库
     * @post: /seller/touch_visual/save
     * @return \Illuminate\Http\JsonResponse
     */
    protected function save()
    {
        $id = request()->input('id', 0);
        $data = request()->input('data', '');
        $pic = request()->input('pic', '');
        $model = TouchPageView::select('id')->where('ru_id', $this->ru_id);
        $res = $model->first();
        $res = $res ? $res->toArray() : [];
        if ($res) {
            $res = $this->visualService->save_page($res['id'], $data, $pic);
            if ($res == true) {
                Cache::flush();
                return response()->json(['error' => 0, 'msg' => 'success']);
            }

            return response()->json(['error' => 1, 'msg' => 'fail']);
        }

        return response()->json(['error' => 1, 'msg' => 'fail']);
    }

    /**
     * 删除配置 - 数据库
     * @post /seller/touch_visual/del
     * @return \Illuminate\Http\JsonResponse
     */
    protected function del()
    {
        $id = request()->input('id', 0);

        if ($id) {
            $res = $this->visualService->del_page($id);
            if ($res == true) {
                return response()->json(['error' => 0, 'msg' => 'success']);
            }

            return response()->json(['error' => 1, 'msg' => 'fail']);
        }

        return response()->json(['error' => 1, 'msg' => 'fail']);
    }

    /**
     * 创建默认相册
     * @post /seller/touch_visual/make_gallery
     * @param: $ru_id  商家ID
     * @param: $album_mame  相册名称
     * @return \Illuminate\Http\JsonResponse
     */
    protected function make_gallery()
    {
        $ru_id = request()->input('ru_id', $this->ru_id);
        $album_mame = request()->input('album_mame', lang('seller/touch_visual.platform_album'));

        $this->visualService->make_gallery_action($ru_id, $album_mame);

        return response()->json(['error' => 0, 'msg' => 'success']);
    }

    /**
     * 返回图库列表
     * @post /seller/touch_visual/picture
     * @return \Illuminate\Http\JsonResponse
     */
    protected function picture()
    {
        $ru_id = request()->input('ru_id', $this->ru_id);
        $album_id = request()->input('album_id', 0);

        $thumb = request()->input('thumb');
        $pageSize = request()->input('pageSize', 15); // 每页数量

        $data = $this->visualService->picture_list($ru_id, $album_id, $thumb, $pageSize);

        if ($data) {
            return response()->json(['error' => 0, 'msg' => 'success', 'total' => $data['total'], 'data' => $data['res']]);
        }
        return response()->json(['error' => 1, 'msg' => 'fail']);
    }

    /**
     * 图片删除
     * @post /admin/touch_visual/remove_picture
     * @return \Illuminate\Http\JsonResponse
     */
    protected function remove_picture()
    {
        $pic_id = request()->input('pic_id', 0);
        $ru_id = request()->input('ru_id', $this->ru_id);

        $condition = [
            'ru_id' => $ru_id,
            'pic_id' => $pic_id
        ];
        $picture = PicAlbum::where($condition)->first();
        $picture = $picture ? $picture->toArray() : [];

        if ($picture) {
            $picturePath = storage_public($picture['pic_file']);
            if (is_file($picturePath)) {
                $this->remove($picture['pic_file']);
                PicAlbum::where($condition)->delete();
                return response()->json(['error' => 0, 'msg' => 'success']);
            }
        }

        return response()->json(['error' => 1, 'msg' => 'fail']);
    }

    /**
     * 上传图片
     * @post /seller/touch_visual/pic_upload
     * @return \Illuminate\Http\JsonResponse
     */
    protected function pic_upload()
    {
        $album_id = request()->input('album_id', 0);
        $ru_id = request()->input('ruId', $this->ru_id);

        $res = $this->upload('data/gallery_album/original_img', true);
        if ($res['error'] === 0) {
            // 建立相册
            if (!empty($album_id)) {
                // 保存图片到数据库
                $data = [
                    'pic_name' => $res['file_name'],
                    'album_id' => $album_id,
                    'pic_file' => 'data/gallery_album/original_img/' . $res['file_name'],
                    'pic_thumb' => '',
                    'pic_size' => $res['size'],
                    'pic_spec' => '',
                    'ru_id' => $ru_id,
                    'add_time' => app(TimeRepository::class)->getGmTime(),
                ];

                if (empty($res['file_name'])) {
                    return response()->json(['error' => 1, 'msg' => 'please_upload']);
                }

                PicAlbum::create($data);

                return response()->json(['error' => 0, 'msg' => 'success', 'pic' => $res['url']]);
            }
        } else {
            return response()->json(['error' => 1, 'msg' => $res['error']]);
        }
    }

    /**
     * 单独新增页面 专题页
     * @post /seller/touch_visual/title
     * @return \Illuminate\Http\JsonResponse
     */
    protected function title(Request $request)
    {
        $id = $request->input('id', 0);
        $type = $request->input('type', 'topic');
        $ru_id = $request->input('ru_id', $this->ru_id);
        $page_id = $request->input('topicId', 0);

        $title = $request->input('title', '');
        $description = $request->input('description', '');

        $data = [];
        $file = request()->file('file');
        if ($file && $file->isValid()) {
            $result = $this->upload('data/gallery_album/original_img', true);
            if ($result['error'] > 0) {
                return response()->json(['error' => 1, 'msg' => $result['message']]);
            }
            $data['file'] = 'data/gallery_album/original_img/' . $result['file_name'];
            $data['file_name'] = $result['file_name'];
            $data['size'] = $result['size'];

            if (empty($result['file_name'])) {
                return response()->json(['error' => 1, 'msg' => 'please_upload']);
            }

            // oss图片处理
            if ($this->config['open_oss'] == 1) {
                $bucket_info = $this->dscRepository->getBucketInfo();
                $bucket_info['endpoint'] = empty($bucket_info['endpoint']) ? $bucket_info['outside_site'] : $bucket_info['endpoint'];
                $http = rtrim($bucket_info['endpoint'], '/') . '/';
                $data['file'] = str_replace($http, '', $data['file']);
            }
            // 路径转换
            if (strtolower(substr($data['file'], 0, 4)) == 'http') {
                $data['file'] = str_replace(url('/'), '', $data['file']);
            }
        }

        $res = $this->visualService->add_topic_page($id, $type, $ru_id, $page_id, $title, $description, $data);

        if ($res) {
            if ($res['status'] == 1) {
                return response()->json(['error' => 0, 'msg' => $res['msg'], 'page' => $res['page']]);
            }
            return response()->json(['error' => 0, 'msg' => 'success', 'pic_url' => $res['pic_url'], 'page' => $res['page']]);
        }

        return response()->json(['error' => 1, 'msg' => 'fail']);
    }

    /**
     * 搜索商品
     * @post /seller/touch_visual/search
     * @return \Illuminate\Http\JsonResponse
     */
    protected function search(Request $request)
    {
        $ru_id = $request->input('ru_id', $this->ru_id);
        $kwords = $request->input('keyword', '');
        $cat_id = $request->input('cat_id', 0);
        $brand_id = $request->input('brand_id', 0);
        $warehouse_id = $request->input('region_id', 0);
        $area_id = $request->input('area_id', 0);
        $area_city = $request->input('area_city', 0);
        $pageSize = $request->input('pageSize', 10);
        $currentPage = $request->input('currentPage', 1);

        $data = $this->visualService->search_goods($ru_id, $kwords, $cat_id, $brand_id, $warehouse_id, $area_id, $area_city, $pageSize, $currentPage);

        if ($data) {
            $list = $data['goods'] ?? [];
            $total = $data['total'] ?? [];
            return response()->json(['error' => 0, 'msg' => 'success', 'list' => $list, 'total' => $total]);
        }

        return response()->json(['error' => 1, 'msg' => 'fail']);
    }
}
