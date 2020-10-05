<?php

namespace App\Services\Goods;

use App\Models\Attribute;
use App\Models\GoodsAttr;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Common\CommonManageService;

class AttributeManageService
{
    protected $commonManageService;
    protected $baseRepository;
    protected $commonRepository;
    protected $config;
    protected $dscRepository;

    public function __construct(
        CommonManageService $commonManageService,
        BaseRepository $baseRepository,
        CommonRepository $commonRepository,
        DscRepository $dscRepository
    )
    {
        $this->commonManageService = $commonManageService;
        $this->baseRepository = $baseRepository;
        $this->commonRepository = $commonRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 获取属性列表
     *
     * @return  array
     */
    public function getAttrlist()
    {
        $seller = $this->commonManageService->getAdminIdSeller();

        /* 查询条件 */
        $filter = array();
        $filter['goods_type'] = isset($_REQUEST['goods_type']) && !empty($_REQUEST['goods_type']) ? intval($_REQUEST['goods_type']) : 0;
        $filter['sort_by'] = isset($_REQUEST['sort_by']) && !empty($_REQUEST['sort_by']) ? trim($_REQUEST['sort_by']) : 'attr_id';
        $filter['sort_order'] = isset($_REQUEST['sort_order']) && !empty($_REQUEST['sort_order']) ? trim($_REQUEST['sort_order']) : 'ASC';

        $row = Attribute::whereRaw(1);

        if ((!empty($filter['goods_type']))) {
            $row = $row->where('cat_id', $filter['goods_type']);
        }

        $where['ru_id'] = $seller['ru_id'];
        $where['suppliers_id'] = $seller['suppliers_id'];
        $where['attr_set_up'] = $this->config['attr_set_up'];

        $row = $row->whereHas('goodsType', function ($query) use ($where) {
            if ($where['suppliers_id']) {
                $query->where('suppliers_id', $where['suppliers_id']);
            }

            if ($where['attr_set_up'] == 0) {
                if ($where['ru_id'] > 0) {
                    $query->where('user_id', 0);
                }
            } elseif ($where['attr_set_up'] == 1) {
                if ($where['ru_id'] > 0) {
                    $query->where('user_id', $where['ru_id']);
                }
            }
        });

        $res = $record_count = $row;

        $filter['record_count'] = $record_count->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 查询 */
        $res = $res->with([
            'goodsType'
        ]);

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

        if ($filter['start'] > 0) {
            $res = $res->skip($filter['start']);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        $res = $this->baseRepository->getToArrayGet($res);

        if ($res) {
            foreach ($res as $key => $val) {
                $res[$key]['cat_name'] = $val['goods_type']['cat_name'] ?? '';
                $res[$key]['attr_input_type_desc'] = $GLOBALS['_LANG']['value_attr_input_type'][$val['attr_input_type']];
                $res[$key]['attr_values'] = str_replace("\n", ", ", $val['attr_values']);
            }
        }

        return [
            'item' => $res,
            'filter' => $filter,
            'page_count' => $filter['page_count'],
            'record_count' => $filter['record_count']
        ];
    }

    /**
     * 插入属性信息
     *
     * @param array $other
     * @return mixed
     */
    public function setAttributeInsert($other = [])
    {
        return Attribute::insertGetId($other);
    }

    /**
     * 更新属性信息
     *
     * @param int $attr_id
     * @param array $other
     * @return mixed
     */
    public function setAttributeUpdate($other = [], $attr_id = 0)
    {
        return Attribute::where('attr_id', $attr_id)
            ->update($other);
    }

    /**
     * 获取最大值
     *
     * @param int $cat_id
     * @param string $filed
     * @return mixed
     */
    public function getAttributeMax($cat_id = 0, $filed = '')
    {
        return Attribute::where('cat_id', $cat_id)->max($filed);
    }

    /**
     * 获取属性颜色值
     *
     * @param int $attr_id
     * @return string
     */
    public function getAttributeColorValues($attr_id = 0)
    {
        $val = Attribute::where('attr_id', $attr_id)->value('color_values');
        $val = $val ? $val : '';

        return $val;
    }

    /**
     * 更新商品属性颜色信息
     *
     * @param array $other
     * @param int $attr_id
     * @return mixed
     */
    public function getGoodsAttrUpdateColorValue($other = [], $attr_id = 0, $attr_value = '')
    {
        return GoodsAttr::where('attr_id', $attr_id)
            ->where('attr_value', $attr_value)
            ->update($other);
    }

    /**
     * 更新属性类型颜色信息
     *
     * @param array $other
     * @param int $attr_id
     * @return mixed
     */
    public function getAttributeUpdateColorValue($other = [], $attr_id = 0)
    {
        return Attribute::where('attr_id', $attr_id)
            ->update($other);
    }

    /**
     * 删除属性类型
     *
     * @param array $attr_id
     * @return mixed
     */
    public function getAttributeDelete($attr_id = [])
    {
        if ($attr_id) {
            $attr_id = $this->baseRepository->getExplode($attr_id);
            return Attribute::whereIn('attr_id', $attr_id)->delete();
        } else {
            return false;
        }
    }

    /**
     * 删除属性类型
     *
     * @param array $attr_id
     * @return mixed
     */
    public function getGoodsAttrDelete($attr_id = [])
    {
        if ($attr_id) {
            $attr_id = $this->baseRepository->getExplode($attr_id);
            return GoodsAttr::whereIn('attr_id', $attr_id)->delete();
        } else {
            return false;
        }
    }

    /**
     * 获取属性类型分类ID
     *
     * @param int $attr_id
     * @return int
     */
    public function getAttributeCatId($attr_id = 0)
    {
        $cat_id = Attribute::where('attr_id', $attr_id)->value('cat_id');
        $cat_id = $cat_id ? $cat_id : 0;

        return $cat_id;
    }

    /**
     * 获取属性类型名称
     *
     * @param int $attr_id
     * @return int
     */
    public function getAttributeAttrName($attr_id = 0)
    {
        $attr_name = Attribute::where('attr_id', $attr_id)->value('attr_name');
        $attr_name = $attr_name ? $attr_name : '';

        return $attr_name;
    }

    /**
     * 查询是否已存在值
     *
     * @param array $where
     * @return bool
     */
    public function getAttributeIsOnly($where = [])
    {
        $object = Attribute::whereRaw(1);
        $result = $this->commonRepository->getManageIsOnly($object, $where);

        return $result;
    }

    /**
     * 获取商品属性数量
     *
     * @param int $id
     * @return mixed
     */
    public function getGoodsAttrNum($id = 0)
    {
        $res = GoodsAttr::where('attr_id', $id);

        $res = $res->whereHas('getGoods', function ($query) {
            $query->where('is_delete', 0);
        });

        $goods_num = $res->count();

        return $goods_num;
    }

    /**
     * 过滤属性换行空格问题
     *
     * @param array $attr_values
     * @return string
     */
    public function filterTextblank($attr_values = [])
    {
        $attr_values_str = '';
        if ($attr_values) {
            $attr_values = preg_replace(['/\r\n/', '/\n/', '/\r/'], ",", $attr_values);//替换空格回车换行符为英文逗号
            $attr_values = explode(',', $attr_values);

            foreach ($attr_values as $key => $val) {
                if ($key != count($attr_values) - 1) {
                    $attr_values_str .= trim($val) . "\r\n";
                } else {
                    $attr_values_str .= trim($val);
                }
            }
        }

        return $attr_values_str;
    }
}
