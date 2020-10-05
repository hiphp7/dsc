<?php

namespace App\Services\Article;

use App\Models\ArticleCat;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;

/**
 * 商城文章分类
 * Class ArticleCatService
 * @package App\Services\Article
 */
class ArticleCatService
{
    protected $config;
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 获得分类的信息
     * @param $cat_id
     * @return array
     */
    public function getCatInfo($cat_id)
    {
        $res = ArticleCat::where('cat_id', $cat_id);
        $res = $this->baseRepository->getToArrayFirst($res);

        return $res;
    }

    /**
     * 获得指定分类同级的所有分类以及该分类下的子分类
     *
     * @access  public
     * @param integer $cat_id 分类编号为0时调用顶级分类
     * @param integer $cat_type 文章分类类型，默认为1自定义分类，2为系统保留分类 by wang
     * @return  array
     */
    public function getArticleCategoriesTree($cat_id = 0, $cat_type = 1)
    {
        if ($cat_id > 0) {
            $parent_id = ArticleCat::where('cat_id', $cat_id)->value('parent_id');
        } else {
            $parent_id = 0;
        }

        /*
          判断当前分类中全是是否是底级分类，
          如果是取出底级分类上级分类，
          如果不是取当前分类及其下的子分类
         */
        $count = ArticleCat::where('parent_id', $parent_id)->where('cat_type', $cat_type)->count();

        if ($count > 0) {
            /* 获取当前分类及其子分类 */
            $res = ArticleCat::getList($parent_id)->where('cat_type', $cat_type)->orderBy('sort_order');
        } else {
            /* 获取当前分类及其父分类 */
            $res = ArticleCat::getList($parent_id)->where('cat_type', 1)->orderBy('sort_order');
        }

        $res = $this->baseRepository->getToArrayGet($res);

        $cat_arr = [];

        if ($res) {
            foreach ($res as $row) {
                $cat_arr[$row['cat_id']]['id'] = $row['cat_id'];
                $cat_arr[$row['cat_id']]['name'] = $row['cat_name'];
                $cat_arr[$row['cat_id']]['url'] = $this->dscRepository->buildUri('article_cat', array('acid' => $row['cat_id']), $row['cat_name']);

                if (isset($row['cat_list'])) {
                    foreach ($row['cat_list'] as $val) {
                        $cat_arr[$row['cat_id']]['url'] = 'javascript:void(0)';
                        $cat_arr[$row['cat_id']]['children'][$val['cat_id']]['id'] = $val['cat_id'];
                        $cat_arr[$row['cat_id']]['children'][$val['cat_id']]['name'] = $val['cat_name'];
                        $cat_arr[$row['cat_id']]['children'][$val['cat_id']]['url'] = $val['url'];
                        $cat_arr[$row['cat_id']]['children'][$val['cat_id']]['children'] = $this->get_article_child_cats($val['cat_id']);
                    }
                }
            }
        }

        return $cat_arr;
    }

    /**
     * 获得指定文章分类的子分类by wang
     *
     * @access  public
     * @param integer $cat 分类编号
     * @return  array
     */
    public function get_article_child_cats($cat = 0)
    {
        $res = ArticleCat::select('cat_id', 'cat_name', 'cat_id', 'cat_name', 'sort_order')
            ->where('parent_id', $cat);
        $res = $res->orderBy('sort_order');
        $res = $this->baseRepository->getToArrayGet($res);

        $cat_arr = [];
        if ($res) {
            foreach ($res as $row) {
                $cat_arr[$row['cat_id']]['id'] = $row['cat_id'];
                $cat_arr[$row['cat_id']]['name'] = $row['cat_name'];
                $cat_arr[$row['cat_id']]['url'] = $this->dscRepository->buildUri('article_cat', array('acid' => $row['cat_id']), $row['cat_name']);
            }
        }

        return $cat_arr;
    }

    /**
     * 多维数组转一维数组【分类】
     *
     * @param int $parent_id
     * @return array|bool|\Illuminate\Cache\CacheManager|\Illuminate\Support\Collection|mixed
     * @throws \Exception
     */
    public function getCatListChildren($parent_id = 0)
    {
        //顶级分类页分类显示
        $cache_name = 'get_rticle_cat_list_children' . $parent_id;

        $cat_list = cache($cache_name);
        $cat_list = !is_null($cat_list) ? $cat_list : false;

        //将数据写入缓存文件
        if ($cat_list === false) {
            $cat_list = ArticleCat::getList($parent_id)
                ->orderBy('sort_order', 'desc')
                ->get();
            $cat_list = $cat_list ? $cat_list->toArray() : [];

            if ($cat_list) {
                $cat_list = $this->dscRepository->getCatVal($cat_list);
                $cat_list = collect($cat_list)->flatten();
                $cat_list = $cat_list->all();

                $cat_list = !empty($parent_id) ? collect($cat_list)->prepend($parent_id)->all() : $cat_list;
            } else {
                $cat_list = [$parent_id];
            }

            cache()->forever($cache_name, $cat_list);
        }


        return $cat_list;
    }

    /**
     * 获得文章所有分类
     *
     * @param int $cat_id
     * @param array $columns
     * @param int $size
     * @return mixed
     */
    public function articleCategoryAll($cat_id = 0, $columns = ['*'], $size = 20)
    {
        if (is_array($cat_id)) {
            $field = key($cat_id);
            $value = $cat_id[$field];
            $model = ArticleCat::where($field, '=', $value)->where('parent_id', 0);
        } else {
            $model = ArticleCat::where('parent_id', $cat_id);
        }
        $model = $model->where('show_in_nav', 1);

        $category = $model->orderBy('sort_order')
            ->orderBy('cat_id', 'DESC')
            ->paginate($size, $columns)
            ->toArray();

        if (!empty($category['data'])) {
            foreach ($category['data'] as $k => $value) {
                $category['data'][$k]['url'] = dsc_url('/#/article') . '?cat_id=' . $value['cat_id'];
            }
        }

        return $category['data'];
    }
}
