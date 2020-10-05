<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Compile;
use App\Models\PicAlbum;
use App\Models\TouchPageView;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Store\StoreCommonService;
use App\Services\Visual\VisualService;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 手机端可视化管理
 * Class TouchVisualController
 * @package App\Modules\Admin\Controllers
 */
class TouchVisualController extends BaseController
{
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
    }

    protected function initialize()
    {
        load_helper(['function', 'ecmoban']);

        load_helper(['main'], 'admin');

        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 编辑控制台
     * @post /admin/touch_visual/index
     * @param Request $request
     * @return array|Factory|View
     */
    public function index(Request $request)
    {
        if (request()->isMethod('POST')) {
            $view = TouchPageView::where('ru_id', 0)
                ->where('default', 1)
                ->first();
            $view = $view ? $view->toArray() : [];
            return $view;
        }

        if (request()->isMethod('GET')) {

            // 专题id
            $this->topic_id = $request->input('topic_id', 0);

            $shopInfo = json_encode(['ruid' => 0, 'type' => 'admin']);
            $this->assign('shopInfo', $shopInfo);
            $topic = json_encode(['topic_id' => $this->topic_id]);
            $this->assign('topic', $topic);
            return $this->display();
        }
    }

    /**
     * 显示页面
     * @post /admin/touch_visual/view
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    protected function view(Request $request)
    {
        $id = $request->input('id', 0);
        $type = $request->input('type', 'index');
        $default = $request->input('default', 0);
        $ru_id = $request->input('ru_id', 0);
        $number = $request->input('number', 10);
        $page_id = $request->input('page_id', 0);

        $view = $this->visualService->View($id, $type, $default, $ru_id, $number, $page_id);

        return response()->json($view);
    }

    /**
     * 公告
     * @post /admin/touch_visual/article
     * @param Request $request
     * @return JsonResponse
     */
    protected function article(Request $request)
    {
        $cat_id = $request->input('cat_id', 0);

        $data = $this->visualService->Article($cat_id);

        return response()->json($data);
    }

    /**
     * 公告分类数量
     * @post /admin/touch_visual/article_list
     * @return JsonResponse
     */
    protected function article_list()
    {
        $list = $this->visualService->article_tree(0);
        return response()->json(['error' => 0, 'list' => $list]);
    }

    /**
     * 商品列表模块
     * @post /admin/touch_visual/product
     * @return JsonResponse
     * @throws Exception
     */
    protected function product(Request $request)
    {
        $number = $request->input('number', 10);
        $type = $request->input('type', '');
        $ru_id = $request->input('ru_id', 0);
        $cat_id = $request->input('cat_id', 0);
        $brand_id = $request->input('brand_id', 0);
        $user_id = 0;

        $cache_id = md5(serialize($request->all()) . $user_id);
        $data = cache()->rememberForever('visual.product' . $cache_id, function () use ($user_id, $cat_id, $type, $ru_id, $number, $brand_id) {
            return $this->visualService->Product($user_id, $cat_id, $type, $ru_id, $number, $brand_id);
        });

        return response()->json($data);
    }

    /**
     * 选中的商品
     * @post /admin/touch_visual/checked
     * @param Request $request
     * @return JsonResponse
     */
    protected function checked(Request $request)
    {
        $goods_id = $request->input('goods_id', 0);
        $ru_id = $request->input('ru_id', 0);
        $warehouse_id = $request->input('warehouse_id', 0);
        $area_id = $request->input('area_id', 0);
        $area_city = $request->input('area_city', 0);

        $data = $this->visualService->Checked($goods_id, $ru_id, $warehouse_id, $area_id, $area_city);

        return response()->json($data);
    }

    /**
     * 显示分类
     * @post /admin/touch_visual/category
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    protected function category(Request $request)
    {
        $cat_id = $request->input('cat_id', 0);

        $cache_id = md5(serialize($request->all()));
        $data = cache()->rememberForever('visual.category' . $cache_id, function () use ($cat_id) {
            return $this->visualService->cat_list($cat_id, 1);
        });

        return response()->json(['error' => 0, 'category' => $data['category']]);
    }

    /**
     * 显示品牌
     * @post /admin/touch_visual/brand
     * @return JsonResponse
     * @throws Exception
     */
    protected function brand(Request $request)
    {
        $num = $request->input('num', 100);

        $cache_id = md5(serialize($request->all()));
        $data = cache()->rememberForever('visual.brand' . $cache_id, function () use ($num) {
            return $this->visualService->brand_list($num);
        });

        return response()->json(['error' => 0, 'brand' => $data]);
    }

    /**
     * 相册或图片
     * @post /admin/touch_visual/thumb
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    protected function thumb(Request $request)
    {
        $type = $request->input('type', '');
        $ru_id = (int)$request->input('ru_id', 0);
        $album_id = (int)$request->input('album_id', 0);
        $pageSize = (int)$request->input('pageSize', 10);
        $currentPage = (int)$request->input('currentPage', 1);

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
     * @post /admin/touch_visual/geturl
     * @return JsonResponse
     */
    protected function geturl(Request $request)
    {
        $type = $request->input('type', '');
        $pageSize = $request->input('pageSize', 10);
        $currentPage = $request->input('currentPage', 1);

        $data = $this->visualService->get_url($type, $pageSize, $currentPage);

        if ($data) {
            $url = $data['url'] ?? '';
            $total = $data['total'] ?? '';
            return response()->json(['error' => 0, 'msg' => 'success', 'url' => $url, 'page' => $currentPage, 'total' => $total]);
        }

        return response()->json(['error' => 1, 'msg' => 'fail']);
    }

    /**
     * 秒杀模块
     * @post /admin/touch_visual/seckill
     * @return JsonResponse
     * @throws Exception
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
     * @post /admin/touch_visual/store
     * @return JsonResponse
     * @throws Exception
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
     * @post /admin/touch_visual/storeIn
     * @return JsonResponse
     */
    protected function storeIn(Request $request)
    {
        $ru_id = $request->input('ru_id', 0);
        $uid = $request->input('uid', 0);

        $data = $this->visualService->StoreIn($ru_id, $uid);

        if ($data) {
            return response()->json(['error' => 0, 'msg' => 'success', 'store' => $data]);
        }

        return response()->json(['error' => 1, 'msg' => 'fail']);
    }

    /**
     * 店铺街详情底部
     * @post /admin/touch_visual/storeDown
     * @return JsonResponse
     */
    protected function storeDown(Request $request)
    {
        $ru_id = $request->input('ru_id', 0);

        $data = $this->visualService->StoreDown($ru_id);

        if ($data) {
            return response()->json(['error' => 0, 'msg' => 'success', 'store' => $data]);
        }

        return response()->json(['error' => 1, 'msg' => 'fail']);
    }

    /**
     * 默认
     * @post /admin/touch_visual/default_index
     * @param Request $request
     * @return JsonResponse
     */
    protected function default_index(Request $request)
    {
        $ru_id = $request->input('ru_id', 0);
        $type = $request->input('type', '');

        $data = $this->visualService->Default($ru_id, $type);

        return response()->json($data);
    }

    /**
     * 保存模块预览配置 - 文件
     * @post /admin/touch_visual/previewModule
     * @return JsonResponse
     */
    protected function previewModule(Request $request)
    {
        $data = $request->input('data');
        if (!empty($data)) {
            $data = $this->visualService->transform($data);
            Compile::setModule('preview', $data);
            return response()->json(['error' => 0, 'data' => $data]);
        }
        return response()->json(['error' => 1, 'msg' => 'fail']);
    }

    /**
     * 保存模块配置 - 文件
     * @post /admin/touch_visual/saveModule
     * @return JsonResponse
     */
    protected function saveModule(Request $request)
    {
        $data = $request->input('data');
        if (!empty($data)) {
            $data = $this->visualService->transform($data);
            Compile::setModule('index', $data);
            return response()->json(['error' => 0, 'data' => $data]);
        }
        return response()->json(['error' => 1, 'msg' => 'fail']);
    }

    /**
     * 清除模块已有配置 - 文件
     * @post /admin/touch_visual/cleanModule
     * @return JsonResponse
     */
    protected function cleanModule()
    {
        if (Compile::cleanModule()) {
            return response()->json(['error' => 0, 'msg' => 'success']);
        }
        return response()->json(['error' => 1, 'msg' => 'fail']);
    }

    /**
     * 还原模块配置 - 文件
     * @post /admin/touch_visual/restore
     * @return JsonResponse
     * @throws Exception
     */
    protected function restore(Request $request)
    {
        $ru_id = $request->input('ru_id', 0);
        $data = str_replace('<?php exit("no access");', '', file_get_contents(storage_path('app/diy/default.php')));
        if ($data) {
            $keep = [
                'type' => 'index',
                'title' => lang('admin/touch_visual.home'),
                'data' => $this->visualService->pageDataReplace($data),
            ];
            if ($ru_id == 0) {
                return response()->json(['error' => 0, 'keep' => $keep]);
            }
        }

        return response()->json(['error' => 1, 'msg' => 'fail']);
    }

    /**
     * 保存配置 - 数据库
     * @post: /admin/touch_visual/save
     * @return JsonResponse
     */
    protected function save(Request $request)
    {
        $id = $request->input('id', 0);
        $data = $request->input('data', '');
        $pic = $request->input('pic', '');

        if ($id) {
            $res = $this->visualService->save_page($id, $data, $pic);
            if ($res == true) {
                return response()->json(['error' => 0, 'msg' => 'success']);
            }

            return response()->json(['error' => 1, 'msg' => 'fail']);
        }

        return response()->json(['error' => 1, 'msg' => 'fail']);
    }

    /**
     * 删除配置 - 数据库
     * @post /admin/touch_visual/del
     * @return JsonResponse
     */
    protected function del(Request $request)
    {
        $id = $request->input('id', 0);

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
     * @post /admin/touch_visual/make_gallery
     * @param: $ru_id  商家ID
     * @param: $album_mame  相册名称
     * @return JsonResponse
     * @throws Exception
     */
    protected function make_gallery(Request $request)
    {
        $ru_id = $request->input('ru_id', 0);
        $album_mame = $request->input('album_mame', lang('admin/touch_visual.album'));

        $this->visualService->make_gallery_action($ru_id, $album_mame);

        return response()->json(['error' => 0, 'msg' => 'success']);
    }

    /**
     * 返回图库列表
     * @post /admin/touch_visual/picture
     * @return JsonResponse
     * @throws Exception
     */
    protected function picture(Request $request)
    {
        $ru_id = $request->input('ru_id', 0);
        $album_id = $request->input('album_id', 0);

        $thumb = $request->input('thumb');
        $pageSize = $request->input('pageSize', 15); // 每页数量

        $cache_id = md5(serialize($request->all()));
        $data = cache()->rememberForever('visual.picture' . $cache_id, function () use ($ru_id, $album_id, $thumb, $pageSize) {
            return $this->visualService->picture_list($ru_id, $album_id, $thumb, $pageSize);
        });

        if ($data) {
            return response()->json(['error' => 0, 'msg' => 'success', 'total' => $data['total'], 'data' => $data['res']]);
        }
        return response()->json(['error' => 1, 'msg' => 'fail']);
    }

    /**
     * 图片删除
     * @post /admin/touch_visual/remove_picture
     * @return JsonResponse
     */
    protected function remove_picture(Request $request)
    {
        $pic_id = $request->input('pic_id', 0);
        $ru_id = $request->input('ru_id', 0);

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
     * @post /admin/touch_visual/pic_upload
     * @return JsonResponse
     */
    protected function pic_upload(Request $request)
    {
        $album_id = $request->input('album_id', 0);
        $ru_id = $request->input('ruId', 0);

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
     * @post /admin/touch_visual/title
     * @return JsonResponse
     */
    protected function title(Request $request)
    {
        $id = $request->input('id', 0);
        $type = $request->input('type', 'topic');
        $ru_id = $request->input('ru_id', 0);
        $page_id = $request->input('topicId', 0);

        $title = $request->input('title', ''); // 标题
        $description = $request->input('description', '');

        $data = [];
        $file = $request->file('file');
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
     * @post /admin/touch_visual/search
     * @return JsonResponse
     */
    protected function search(Request $request)
    {
        $ru_id = (int)$request->input('ru_id', 0);
        $keywords = $request->input('keyword', '');
        $cat_id = (int)$request->input('cat_id', 0);
        $brand_id = (int)$request->input('brand_id', 0);
        $warehouse_id = (int)$request->input('region_id', 0);
        $area_id = (int)$request->input('area_id', 0);
        $area_city = (int)$request->input('area_city', 0);
        $pageSize = (int)$request->input('pageSize', 10);
        $currentPage = (int)$request->input('currentPage', 1);

        $data = $this->visualService->search_goods($ru_id, $keywords, $cat_id, $brand_id, $warehouse_id, $area_id, $area_city, $pageSize, $currentPage);

        $store_list = app(StoreCommonService::class)->getCommonStoreList();//店铺列表
        if ($data) {
            $list = $data['goods'] ?? [];
            $total = $data['total'] ?? [];
            $store_list = $store_list ?? [];
            return response()->json(['error' => 0, 'msg' => 'success', 'list' => $list, 'total' => $total, 'store_list' => $store_list]);
        }

        return response()->json(['error' => 1, 'msg' => 'fail']);
    }
}
