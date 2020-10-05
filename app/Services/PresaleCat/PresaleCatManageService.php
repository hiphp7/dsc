<?php

namespace App\Services\PresaleCat;


use App\Models\PresaleCat;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Merchant\MerchantCommonService;


class PresaleCatManageService
{

    protected $baseRepository;
    protected $timeRepository;
    protected $merchantCommonService;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        MerchantCommonService $merchantCommonService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->merchantCommonService = $merchantCommonService;
    }

    /**
     * 检查分类是否已经存在
     *
     * @param $cat_name 分类名称
     * @param $parent_cat 上级分类
     * @param int $exclude 排除的分类ID
     * @return bool
     */
    public function presaleCatExists($cat_name, $parent_cat, $exclude = 0)
    {
        $res = PresaleCat::where('parent_id', $parent_cat)
            ->where('cat_name', $cat_name)
            ->where('cat_id', '<>', $exclude)
            ->count();

        return ($res > 0) ? true : false;
    }

    /**
     * 添加商品分类
     *
     * @param integer $cat_id
     * @param array $args
     *
     * @return  mix
     */
    public function catUpdate($cat_id, $args)
    {
        if (empty($args) || empty($cat_id)) {
            return false;
        }

        $res = PresaleCat::where('cat_id', $cat_id)->update($args);
        return $res;
    }

    /**
     * 检查分类是否已经存在
     *
     * @param string $cat_name 分类名称
     * @param integer $parent_cat 上级分类
     * @param integer $exclude 排除的分类ID
     *
     * @return  boolean
     */
    public function cnameExists($cat_name, $parent_cat, $exclude = 0)
    {
        $res = PresaleCat::where('parent_id', $parent_cat)
            ->where('cat_name', $cat_name)
            ->where('cat_id', '<>', $exclude)
            ->count();


        return ($res > 0) ? true : false;
    }

    /*预售商品下级分类*/
    public function presaleChildCat($pid)
    {
        $res = PresaleCat::where('parent_id', $pid);
        $row = $this->baseRepository->getToArrayGet($res);
        return $row;
    }
}
