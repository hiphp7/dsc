<?php

namespace App\Custom\Distribute\Services;

use App\Custom\Distribute\Repositories\CategoryRepository;
use App\Repositories\Common\TimeRepository;


class PublicCategoryService
{
    protected $timeRepository;
    protected $categoryRepository;

    public function __construct(
        TimeRepository $timeRepository,
        CategoryRepository $categoryRepository
    )
    {
        $this->timeRepository = $timeRepository;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * 分类信息
     * @param array $arr
     * @param string $table
     * @return array|bool
     */
    public function get_array_category_info($arr = [], $table = 'category')
    {
        return $this->categoryRepository->get_array_category_info($arr, $table);
    }

    /**
     * 商家分类 获取当级分类列表
     * @param int $cat_id
     * @param int $relation 关系 0:自己 1:上级 2:下级
     * @param int $user_id
     * @return mixed
     */
    public function get_seller_category_list($cat_id = 0, $relation = 0, $user_id = 0)
    {
        return $this->categoryRepository->get_seller_category_list($cat_id, $relation, $user_id);
    }

    /**
     * 商家入驻分类
     * @param int $user_id 商家id
     * @return array
     */
    public function seller_shop_cat($user_id = 0)
    {
        return $this->categoryRepository->seller_shop_cat($user_id);
    }
}