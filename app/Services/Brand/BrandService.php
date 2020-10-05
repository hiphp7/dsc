<?php

namespace App\Services\Brand;

use App\Models\Brand;
use App\Models\Category;
use App\Models\MerchantsShopBrand;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Category\CategoryService;
use App\Services\Common\AreaService;
use App\Services\Common\TemplateService;

/**
 * 商城品牌
 * Class Brand
 * @package App\Services
 */
class BrandService
{
    protected $baseRepository;
    protected $categoryService;
    protected $config;
    protected $timeRepository;
    protected $dscRepository;
    protected $city = 0;
    protected $templateService;

    public function __construct(
        BaseRepository $baseRepository,
        CategoryService $categoryService,
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        TemplateService $templateService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->categoryService = $categoryService;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->templateService = $templateService;

        $this->city = app(AreaService::class)->areaCookie();
    }

    /**
     * 获得指定品牌的详细信息
     *
     * @param $id_name
     * @param string $act
     * @param int $selType
     * @return array
     */
    public function getBrandInfo($id_name, $act = '', $selType = 0)
    {
        if ($act == 'merchants_brands') {
            $brand = MerchantsShopBrand::select('bid as brand_id', 'brandName as brand_name', 'bank_name_letter as brand_letter', 'brandLogo as brand_logo')->where('audit_status', 1);

            if ($selType == 1) {
                $brand = $brand->where('brand_name', $id_name);
            } else {
                $brand = $brand->where('bid', $id_name);
            }
        } else {
            if ($selType == 1) {
                $brand = Brand::where('brand_name', $id_name);
            } else {
                $brand = Brand::where('brand_id', $id_name);
            }

            $uid = session('user_id', 0);
            $brand = $brand->withCount([
                'getCollectBrand as collect_count' => function ($query) use ($uid) {
                    $query->where('user_id', $uid);
                }
            ]);
        }

        $brand = $brand->first();
        $brand = $brand ? $brand->toArray() : [];

        if ($brand) {
            $brand['brand_logo'] = !empty($brand['brand_logo']) ? $this->dscRepository->getImagePath(DATA_DIR . '/brandlogo/' . $brand['brand_logo']) : '';
            $brand['index_img'] = !empty($brand['index_img']) ? $this->dscRepository->getImagePath(DATA_DIR . '/indeximg/' . $brand['index_img']) : '';
            $brand['brand_bg'] = !empty($brand['brand_bg']) ? $this->dscRepository->getImagePath(DATA_DIR . '/brandbg/' . $brand['brand_bg']) : '';
        }

        return $brand;
    }

    /**
     * 获得某个分类下
     *
     * @param int $cat
     * @param string $app
     * @param int $num
     * @param int $page
     * @param int $page_size
     * @return bool|\Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getBrands($cat = 0, $app = 'brand', $num = 0, $page = 1, $page_size = 8)
    {
        $user_id = session('user_id', 0);

        $template = basename(PHP_SELF);
        $template = substr($template, 0, strrpos($template, '.'));
        static $static_page_libs = null;

        $children = [];
        if ($cat > 0) {
            $children = $this->categoryService->getCatListChildren($cat);
        }

        $row = Brand::where('is_show', 1);
        if ($cat > 0) {
            $row = $row->whereHas('getGoods', function ($query) use ($children) {
                $query = $query->where('is_on_sale', 1)
                    ->where('is_alone_sale', 1)
                    ->where('is_delete', 0);

                if ($children) {
                    $query->whereIn('cat_id', $children);
                }
            });
        }

        $row = $row->withCount([
            'getCollectBrand as is_collect' => function ($query) use ($user_id) {
                $query->where('user_id', $user_id);
            }
        ]);

        if (isset($static_page_libs[$template]['/library/brands.lbi'])) {
            $num = $this->templateService->getLibraryNumber("brands");
        }
        if ($num > 0) {
            $row = $row->take($num);
        }
        $row = $row->groupBy('brand_id')
            ->orderBy('sort_order');

        $row = $this->baseRepository->getToArrayGet($row);

        if ($row) {
            foreach ($row as $key => $val) {
                if ($val['site_url'] && strlen($val['site_url']) > 8) {
                    $row[$key]['url'] = $val['site_url'];
                } else {
                    $row[$key]['url'] = $this->dscRepository->buildUri($app, ['cid' => $cat, 'bid' => $val['brand_id']], $val['brand_name']);
                }
                $row[$key]['brand_desc'] = htmlspecialchars($val['brand_desc'], ENT_QUOTES);
                $row[$key]['brand_logo'] = $this->dscRepository->getImagePath(DATA_DIR . '/brandlogo/' . $val['brand_logo']);
                $row[$key]['index_img'] = $this->dscRepository->getImagePath(DATA_DIR . '/indeximg/' . $val['index_img']); //品牌专区大图 by wu
                $row[$key]['brand_bg'] = $this->dscRepository->getImagePath(DATA_DIR . '/brandbg/' . $val['brand_bg']); //品牌专区大图 by wu

                //获取是否收藏
                $row[$key]['is_collect'] = $val['is_collect'];
            }
        }

        $page_array = $this->dscRepository->pageArray($page_size, $page, $row);

        if ($page > $page_array['filter']['page']) {
            $row = [];
        } else {
            $row = $page_array ? $page_array['list'] : [];
        }

        return $row;
    }

    /**
     * 获得与指定品牌相关的分类
     *
     * @access  public
     * @param integer $brand
     * @return  array
     */
    public function getBrandRelatedCat($brand)
    {
        $arr[] = ['cat_id' => 0,
            'cat_name' => $GLOBALS['_LANG']['all_category'],
            'url' => $this->dscRepository->buildUri('brand', ['bid' => $brand], $GLOBALS['_LANG']['all_category'])];

        $res = Category::whereRaw(1);

        $res = $res->whereHas('getGoods', function ($query) use ($brand) {
            $query->where('brand_id', $brand);
        });

        $res = $res->orderBy('cat_id');

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $row) {
                $row['url'] = $this->dscRepository->buildUri('brand', ['cid' => $row['cat_id'], 'bid' => $brand], $row['cat_name']);
                $arr[] = $row;
            }
        }

        return $arr;
    }
}
