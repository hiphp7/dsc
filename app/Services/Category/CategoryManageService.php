<?php

namespace App\Services\Category;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\CatRecommend;
use App\Models\Goods;
use App\Models\GoodsCat;
use App\Models\MerchantsDocumenttitle;
use App\Models\Nav;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Common\CommonManageService;

class CategoryManageService
{
    protected $baseRepository;
    protected $categoryService;
    protected $dscRepository;
    protected $timeRepository;
    protected $commonManageService;

    public function __construct(
        BaseRepository $baseRepository,
        CategoryService $categoryService,
        DscRepository $dscRepository,
        TimeRepository $timeRepository,
        CommonManageService $commonManageService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->categoryService = $categoryService;
        $this->dscRepository = $dscRepository;
        $this->timeRepository = $timeRepository;
        $this->commonManageService = $commonManageService;
    }

    /**
     * ajax分类列表
     *
     * @param int $parent_id
     * @param int $level
     * @return mixed
     */
    public function getCatLevel($parent_id = 0, $level = 0)
    {
        $res = Category::where('parent_id', $parent_id)
            ->orderByRaw('sort_order, cat_id asc');

        $res = $this->baseRepository->getToArrayGet($res);


        if ($res) {
            foreach ($res as $k => $row) {
                $children = $this->categoryService->getCatListChildren($row['cat_id']);

                /* 分类商品数量 */
                $goods_num = Goods::where('is_delete', 0)
                    ->where('extension_code', '')
                    ->where('review_status', '>', 2)
                    ->whereIn('cat_id', $children)
                    ->count();

                /* 扩展分类商品数量 */
                $goods_cat_num = GoodsCat::whereIn('cat_id', $children)
                    ->withCount([
                        'getGoods as goods_cat_num' => function ($query) {
                            $query->where('is_delete', 0)
                                ->where('extension_code', '');
                        }
                    ])->value('goods_cat_num');

                $goods_cat_num = $goods_cat_num ? $goods_cat_num : 0;

                $res[$k]['goods_num'] = $goods_num + $goods_cat_num;

                $res[$k]['level'] = $level;
            }
        }

        return $res;
    }

    /**
     * 分类信息
     *
     * @param int $cat_id
     * @return array
     */
    public function getCategoryInfo($cat_id = 0)
    {
        $info = Category::where('cat_id', $cat_id);
        $info = $this->baseRepository->getToArrayFirst($info);

        if ($info) {
            if ($info['cat_icon']) {
                $info['cat_icon'] = $this->dscRepository->getImagePath($info['cat_icon']);
            }

            if ($info['touch_icon']) {
                $info['touch_icon'] = $this->dscRepository->getImagePath($info['touch_icon']);
            }

            if ($info['touch_catads']) {
                $info['touch_catads'] = $this->dscRepository->getImagePath($info['touch_catads']);
            }
        }

        return $info;
    }

    /**
     * 批量添加分类
     *
     * @param $cat_name
     * @param $cat
     */
    public function getBacthCategory($cat_name, $cat)
    {
        for ($i = 0; $i < count($cat_name); $i++) {
            if (!empty($cat_name)) {
                $cat['cat_name'] = $cat_name[$i];

                //删除数组中多余变量
                if (isset($cat['level'])) {
                    unset($cat['level']);
                }
                if (isset($cat['cat_recommend'])) {
                    $cat_recommend = $cat['cat_recommend'];
                    unset($cat['cat_recommend']);
                }
                if (isset($cat['is_show_merchants'])) {
                    unset($cat['is_show_merchants']);
                }


                $cat_id = Category::insertGetId($cat);

                if ($cat_id > 0) {
                    if ($cat['show_in_nav'] == 1) {
                        $vieworder = Nav::where('type', 'middle')->max('vieworder');
                        $vieworder = $vieworder ? $vieworder : 0;

                        $vieworder += 2;

                        //显示在自定义导航栏中
                        $other = [
                            'name' => $cat['cat_name'],
                            'ctype' => 'c',
                            'cid' => $cat_id,
                            'ifshow' => 1,
                            'vieworder' => $vieworder,
                            'opennew' => 0,
                            'url' => $this->dscRepository->buildUri('category', ['cid' => $cat_id], $cat['cat_name']),
                            'type' => 'middle'
                        ];
                        Nav::insert($other);
                    }

                    $this->categoryService->getInsertCatRecommend($cat_recommend, $cat_id);

                    admin_log($cat['cat_name'], 'add', 'category');   // 记录管理员操作
                }
            }
        }
    }

    /**
     * 添加类目证件标题
     *
     * @param $dt_list
     * @param $cat_id
     * @param array $dt_id
     */
    public function setDocumentTitleInsertUpdate($dt_list, $cat_id, $dt_id = [])
    {
        if (!empty($dt_list)) {

            //删除二级类目表数据
            MerchantsDocumenttitle::where('cat_id', $cat_id)
                ->whereNotIn('dt_id', $dt_id)
                ->delete();

            for ($i = 0; $i < count($dt_list); $i++) {
                $dt_list[$i] = trim($dt_list[$i]);

                $catId = MerchantsDocumenttitle::where('dt_id', $dt_id[$i])->value('cat_id');

                if (!empty($dt_list[$i])) {
                    $parent = [
                        'cat_id' => $cat_id,
                        'dt_title' => $dt_list[$i]
                    ];

                    if ($catId > 0) {
                        MerchantsDocumenttitle::where('dt_id', $dt_id[$i])->update($parent);
                    } else {
                        MerchantsDocumenttitle::insert($parent);
                    }
                } else {
                    if ($catId > 0) {
                        //删除二级类目表数据
                        MerchantsDocumenttitle::where('dt_id', $dt_id[$i])->delete();
                    }
                }
            }
        }
    }

    /**
     * 分类首页推荐
     *
     * @param int $cat_id
     * @return array
     */
    public function getCatRecommendList($cat_id = 0)
    {
        $res = CatRecommend::where('cat_id', $cat_id);
        $res = $this->baseRepository->getToArrayGet($res);

        return $res;
    }

    /**
     * 类目证件标题
     *
     * @param int $cat_id
     * @return array
     */
    public function getMerchantsDocumenttitleList($cat_id = 0)
    {
        $res = MerchantsDocumenttitle::where('cat_id', $cat_id);
        $res = $this->baseRepository->getToArrayGet($res);

        return $res;
    }

    /**
     * 手机上传图片
     *
     * @param string $name
     * @param string $tmp_name
     * @param int $size
     * @param array $path
     * @param array $link
     * @return array|string
     */
    public function getTouchImagesUpload($name = '', $tmp_name = '', $size = 0, $path = [], $link = [])
    {
        $icon = '';
        if (!empty($name)) {
            if ($size > 200000) {
                return ['error' => 1, 'msg' => $GLOBALS['_LANG']['cat_prompt_file_size']];
            }

            $icon_name = explode('.', $name);
            $key = count($icon_name);
            $type = $icon_name[$key - 1];

            if ($type != 'jpg' && $type != 'png' && $type != 'gif' && $type != 'jpeg') {
                return ['error' => 1, 'msg' => $GLOBALS['_LANG']['cat_prompt_file_type']];
            }
            $imgNamePrefix = $this->timeRepository->getGmTime() . mt_rand(1001, 9999);

            //文件目录
            if (!file_exists($path[0])) {
                make_dir($path[0]);
            }

            //保存文件
            $imgName = $path[0] . "/" . $imgNamePrefix . '.' . $type;
            move_uploaded_file($tmp_name, $imgName);

            $icon = $path[1] . $imgNamePrefix . '.' . $type;//组合路径，并去掉storage前的路径
            $this->dscRepository->getOssAddFile([$icon]); //oss存储图片
        }

        return $icon;
    }

    /**
     * 获取属性列表
     *
     * @return array
     */
    public function getAttrList()
    {
        $seller = $this->commonManageService->getAdminIdSeller();

        $arr = Attribute::whereRaw(1);
        $arr = $arr->whereHas('goodsType', function ($query) use ($seller) {
            $query->where('enabled', 1)
                ->where('user_id', $seller['ru_id'])
                ->where('suppliers_id', $seller['suppliers_id']);
        });

        $arr = $arr->orderByRaw("cat_id, sort_order asc");

        $arr = $this->baseRepository->getToArrayGet($arr);

        $list = [];
        if ($arr) {
            foreach ($arr as $val) {
                $list[$val['cat_id']][] = [$val['attr_id'] => $val['attr_name']];
            }
        }

        return $list;
    }
}
