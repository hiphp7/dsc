<?php

namespace App\Services\Brand;

use App\Models\Brand;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;

class BrandManageService
{
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
    }

    /**
     * 获取品牌列表
     *
     * @access  public
     * @return  array
     */
    public function getBrandList()
    {
        /* 分页大小 */
        $filter = [];

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'brand_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $row = Brand::whereRaw(1);

        $keyword = '';
        if (isset($_POST['brand_name'])) {
            if (strtoupper(EC_CHARSET) == 'GBK') {
                $keyword = iconv("UTF-8", "gb2312", $_POST['brand_name']);
            } else {
                $keyword = $_POST['brand_name'];
            }
        }

        $filter['keyword'] = !empty($keyword) ? addslashes($keyword) : '';

        if ($filter['keyword']) {
            $row = $row->where('brand_name', 'like', '%' . $filter['keyword'] . '%');
        }

        $res = $record_count = $row;

        /* 记录总数以及页数 */
        $filter['record_count'] = $record_count->count();

        $filter = page_and_size($filter);

        /* 查询记录 */
        $res = $res->with([
            'getBrandExtend'
        ]);

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

        if ($filter['start'] > 0) {
            $res = $res->skip($filter['start']);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        if ($res) {
            foreach ($res as $rows) {
                $rows['is_recommend'] = $rows['get_brand_extend']['is_recommend'] ?? 0;

                $site_url = empty($rows['site_url']) ? 'N/A' : '<a href="' . $rows['site_url'] . '" target="_brank">' . $rows['site_url'] . '</a>';

                $rows['brand_logo'] = $this->dscRepository->getImagePath(DATA_DIR . '/brandlogo/' . $rows['brand_logo']);
                $rows['site_url'] = $site_url;

                $arr[] = $rows;
            }
        }

        return ['brand' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    /**
     * 品牌列表
     *
     * @param int $brand_id
     * @return array
     */
    public function brandInfo($brand_id = 0)
    {
        $brand = Brand::where('brand_id', $brand_id);
        $brand = $brand->with([
            'getBrandExtend'
        ]);

        $brand = $this->baseRepository->getToArrayFirst($brand);

        if ($brand) {
            $brand['is_recommend'] = $brand['get_brand_extend']['is_recommend'] ?? 0;
            $brand['brand_logo'] = !empty($brand['brand_logo']) ? $this->dscRepository->getImagePath(DATA_DIR . '/brandlogo/' . $brand['brand_logo']) : '';
            $brand['index_img'] = !empty($brand['index_img']) ? $this->dscRepository->getImagePath(DATA_DIR . '/indeximg/' . $brand['index_img']) : '';
            $brand['brand_bg'] = !empty($brand['brand_bg']) ? $this->dscRepository->getImagePath(DATA_DIR . '/brandbg/' . $brand['brand_bg']) : '';
        }

        return $brand;
    }
}
