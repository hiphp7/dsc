<?php

namespace App\Services\Goods;

use App\Models\Attribute;
use App\Repositories\Common\BaseRepository;

class GoodsExportManageService
{
    protected $baseRepository;

    public function __construct(
        BaseRepository $baseRepository

    )
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * 替换影响csv文件的字符
     *
     * @param $str string 处理字符串
     */
    public function replaceSpecialChar($str, $replace = true)
    {
        $str = str_replace("\r\n", "", $this->imagePathFormat($str));
        $str = str_replace("\t", "    ", $str);
        $str = str_replace("\n", "", $str);
        if ($replace == true) {
            $str = '"' . str_replace('"', '""', $str) . '"';
        }
        return $str;
    }

    /**
     *
     *
     * @access  public
     * @param
     *
     * @return string
     */
    public function imagePathFormat($content)
    {
        $prefix = 'http://' . request()->server('SERVER_NAME');
        $pattern = '/(background|src)=[\'|\"]((?!http:\/\/).*?)[\'|\"]/i';
        $replace = "$1='" . $prefix . "$2'";
        return preg_replace($pattern, $replace, $content);
    }

    /**
     * 生成商品导出过滤条件
     *
     * @param array $filter 过滤条件数组
     *
     * @return string
     */
    public function getExportWhereSql($filter)
    {
        if (isset($filter['filter']) && $filter['filter']) {
            $filter = dsc_decode($_REQUEST['filter'], true);
        }

        if (!empty($filter['goods_ids'])) {
            $goods_ids = explode(',', $filter['goods_ids']);
            if (is_array($goods_ids) && !empty($goods_ids)) {
                $goods_ids = array_unique($goods_ids);
                $goods_ids = "'" . implode("','", $goods_ids) . "'";
            } else {
                $goods_ids = "'0'";
            }
            $where = " WHERE g.is_delete = 0 AND g.goods_id IN (" . $goods_ids . ") ORDER BY goods_id ASC";
        } else {
            $_filter = app(\StdClass::class);
            $_filter->cat_id = $filter['cat_id'] ?? 0;
            $_filter->brand_id = $filter['brand_id'] ?? 0;
            $_filter->keyword = $filter['keyword'] ?? '';
            $where = get_where_sql_unpre($_filter);
        }
        return $where;
    }

    /**
     * 生成商品导出过滤条件--分卷导出
     *
     * @param array $filter 过滤条件数组
     *
     * @return string
     */
    public function getExportStepWhereSql($filter)
    {
        $arr = [];
        if (!empty($filter->goods_ids)) {
            $goods_ids = explode(',', $filter->goods_ids);
            if (is_array($goods_ids) && !empty($goods_ids)) {
                $goods_ids = array_unique($goods_ids);
                $goods_ids = "'" . implode("','", $goods_ids) . "'";
            } else {
                $goods_ids = "'0'";
            }
            $arr['where'] = " WHERE g.is_delete = 0 AND g.goods_id IN (" . $goods_ids . ") ";
        } else {
            $_filter = app(\stdClass::class);
            $_filter->cat_id = $filter->cat_id;
            $_filter->brand_id = $filter->brand_id;
            $_filter->keyword = $filter->keyword;
            $arr['where'] = get_where_sql_unpre($_filter);
        }
        $arr['filter']['cat_id'] = $filter->cat_id;
        $arr['filter']['brand_id'] = $filter->brand_id;
        $arr['filter']['keyword'] = $filter->keyword;
        $arr['filter']['goods_ids'] = $filter->goods_ids;
        return $arr;
    }

    /**
     * 设置导出商品字段名
     *
     * @param array $array 字段数组
     * @param array $lang 字段名
     *
     * @return array
     */
    public function setGoodsFieldName($array, $lang)
    {
        $tmp_fields = $array;
        foreach ($array as $key => $value) {
            if (isset($lang[$value])) {
                $tmp_fields[$key] = $lang[$value];
            } else {
                $tmp_fields[$key] = Attribute::where('attr_id', intval($value))->value('attr_name');
                $tmp_fields[$key] = $tmp_fields[$key] ?? 0;
            }
        }
        return $tmp_fields;
    }

    /**
     * 获取商品类型属性
     *
     * @param int $cat_id 商品类型ID
     *
     * @return array
     */
    public function getAttributes($cat_id = 0)
    {
        $res = Attribute::select('attr_id', 'cat_id', 'attr_name');
        if (!empty($cat_id)) {
            $cat_id = intval($cat_id);
            $res = $res->where('cat_id', $cat_id);
        }
        $res = $res->orderBy('cat_id', 'ASC')->orderBy('attr_id', 'ASC');
        $attributes = [];
        $query = $this->baseRepository->getToArrayGet($res);
        foreach ($query as $row) {
            $attributes[$row['attr_id']] = $row['attr_name'];
        }
        return $attributes;
    }

    /**
     *
     *
     * @access  public
     * @param
     *
     * @return void
     */
    public function utf82u2($str)
    {
        $len = strlen($str);
        $start = 0;
        $result = '';

        if ($len == 0) {
            return $result;
        }

        while ($start < $len) {
            $num = ord($str{$start});
            if ($num < 127) {
                $result .= chr($num) . chr($num >> 8);
                $start += 1;
            } else {
                if ($num < 192) {
                    /* 无效字节 */
                    $start++;
                } elseif ($num < 224) {
                    if ($start + 1 < $len) {
                        $num = (ord($str{$start}) & 0x3f) << 6;
                        $num += ord($str{$start + 1}) & 0x3f;
                        $result .= chr($num & 0xff) . chr($num >> 8);
                    }
                    $start += 2;
                } elseif ($num < 240) {
                    if ($start + 2 < $len) {
                        $num = (ord($str{$start}) & 0x1f) << 12;
                        $num += (ord($str{$start + 1}) & 0x3f) << 6;
                        $num += ord($str{$start + 2}) & 0x3f;

                        $result .= chr($num & 0xff) . chr($num >> 8);
                    }
                    $start += 3;
                } elseif ($num < 248) {
                    if ($start + 3 < $len) {
                        $num = (ord($str{$start}) & 0x0f) << 18;
                        $num += (ord($str{$start + 1}) & 0x3f) << 12;
                        $num += (ord($str{$start + 2}) & 0x3f) << 6;
                        $num += ord($str{$start + 3}) & 0x3f;
                        $result .= chr($num & 0xff) . chr($num >> 8) . chr($num >> 16);
                    }
                    $start += 4;
                } elseif ($num < 252) {
                    if ($start + 4 < $len) {
                        /* 不做处理 */
                    }
                    $start += 5;
                } else {
                    if ($start + 5 < $len) {
                        /* 不做处理 */
                    }
                    $start += 6;
                }
            }
        }

        return $result;
    }
}
