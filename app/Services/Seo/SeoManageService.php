<?php

namespace App\Services\Seo;

use App\Models\Category;
use App\Models\Seo;
use App\Repositories\Common\BaseRepository;
use App\Services\Common\CommonManageService;
use App\Services\Merchant\MerchantCommonService;

class SeoManageService
{

    protected $baseRepository;
    protected $commonManageService;
    protected $merchantCommonService;

    public function __construct(
        BaseRepository $baseRepository,
        CommonManageService $commonManageService,
        MerchantCommonService $merchantCommonService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->commonManageService = $commonManageService;
        $this->merchantCommonService = $merchantCommonService;
    }

    public function getSeo()
    {
        $res = Seo::whereRaw(1);
        $res = $this->baseRepository->getToArrayGet($res);

        if (is_array($res)) {
            foreach ($res as $value) {
                $seo[$value['type']] = $value;
            }
        }
        return $seo;
    }

    /*
    * 获取 seo 分类信息
    */
    public function getSeoCatInfo()
    {
        $res = Category::whereRaw('trim(cate_title) <> ?', '')
            ->orWhereRaw('trim(cate_keywords) <> ?', '')
            ->orWhereRaw('trim(cate_description) <> ?', '');
        $row = $this->baseRepository->getToArrayFirst($res);

        return $row ? $row : [];
    }
}
