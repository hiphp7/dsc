<?php

namespace App\Repositories\Common;

use App\Kernel\Repositories\Common\BaseRepository as Base;

/**
 * Class BaseRepository
 * @method getToArrayGet(array $query = []) 返回数组列表
 * @method getToArrayFirst(array $query = []) 返回一条数据数组
 * @method getSortBy($list = [], $sort = '', $order = 'asc') 按指定key排序，返回排序数组数据
 * @method getSort(array $query = []) 按数组值排序，返回排序数组数据[默认从小到大]
 * @method sortKeys($list = [], $order = 'asc') 按key值排序，返回排序数组数据
 * @method getTake($list = [], $mun = 0) 获取指定数组条数
 * @method getKeyPluck($list = [], $key = '') 获取指定键值数组数据
 * @method getGroupBy($list = [], $val = '') 获取组合数组的新数组
 * @method getFlatten($list = [], $mun = 0) 获取多维数组转为一维数组
 * @method getExplode($val = [], $str = ',') 获取字符串转数组
 * @method getImplode($val = [], $where = ['str' => '', 'replace' => ',', 'is_need' => 0]) 获取字符串转数组
 * @method getNonOneDimensionalArray($list = []) 判断是否非一维数组[【false：一维数组, true：多维数组】]
 * @method getArrayMerge($list = [], $row = []) 合并数组
 * @method getSum($list = [], $str = '') 获取数组计算指定键值数量
 * @method getWhere($list = [], $where = ['str' => '', 'estimate' => '', 'val' => '']) 获取数组计算指定键值数量
 * @method getArrayFlip($list = []) 交换数组中的键和值
 * @method getArrayIntersect($list = [], $columns = []) 数组的「键」进行比较，计算数组的交集
 * @method getArrayIntersectByKeys($list = [], $columns = []) 数组的「值」进行比较，计算数组的交集
 * @method getArrayDiff($list = [], $columns = []) 数组的「值」进行比较， 计算数组的差集
 * @method getArrayDiffKeys($list = [], $columns = []) 数组的「键」进行比较， 计算数组的差集
 * @method getArrayUnique($list = [], $key = '') 移除数组中重复的值
 * @method getCacheForeverlist($list = []) 存储缓存数组指定内容
 * @method getCacheForgetlist($list = []) 清除数组指定缓存
 * @method getBrowseUrl() 获取接口数据
 * @method getArrayExcept($list = [], $key = []) 数组内容移除指定键名项
 * @method setDiskForever($name = 'file', $data = []) 生成永久缓存文件
 * @method getDiskForeverData($name = '') 获取缓存文件内容
 * @method getDiskForeverDelete($name = '') 删除缓存文件
 * @method getDiskForeverExists($name = '') 判断缓存文件是否存在
 * @method getArrayMin($list = [], $str = '') 获取最小值
 * @method getArrayMax($list = [], $str = '') 获取最大值
 * @method getArrayCount($list = []) 计算数组数量
 * @method getArrayCrossJoin($list = [], $page = 1, $size = 0) 多数组交集组合新数组
 * @method getPaginate($list = [], $size = 15, $options = []) 分页
 * @method toSql($builder) 打印SQL语句
 * @method getArrayKeys($list = []) 返回以键名为集合的数组
 * @method getArrayPush($list = [], $push = '') 将值添加到数组
 * @method getArraySearch($list = [], $val = '', $bool = null) 查找指定值是否存在在数组中
 * @method getArrayfilterTable($other = [], $table = '') 过滤表字段数组
 * @method getDbRaw($list = []) 生成原生SQL
 * @method getArrayCollapse($list = []) 将多个数组合并成一个数组[$list = [$arr1, $arr2] = array_merge($arr1, $arr2) 方法的强化版]
 * @method dscUnlink($file = '', $path = '') 删除文件
 * @method getTrimUrl($url = '') 处理Url
 * @method getArrayAll($list = []) 返回该集合表示的底层数组
 * @method getArrayAvg($list = [], $key = '') 返回给定键的平均值，可选值 $key 指定数组键的数值平均值
 * @method getArrayChunk($list = [], $size = 0) 将集合拆成多个给定大小的小集合
 * @method getArrayCombine($key = [], $value = []) 将一个集合的值作为键，再将另一个数组或集合的值作为值合并成一个集合
 * @method getArrayConcat($list = [], $push = []) 将给定的 数组 或集合值追加到集合的末尾
 * @method getArrayContains($list = [], $value = '') 判断集合是否包含指定的集合项
 * @method getContainsTwoArray($list = [], $key = '', $value = '') 传递一组键 / 值对，可以判断该键 / 值对是否存在于集合中
 * @method getArrayOnly($list = [], $key = []) 返回集合中所有指定键的集合项
 * @method getArrayFilterData($list = []) 移除 null、false、''、[]， 0 等数据数组
 * @method getArrayFirst($list = []) 获取集合中的第一个元素
 * @method getArraySqlFirst($list = [], $sql = []) 二维数组仿SQL查询获取一条数据
 * @method getArraySqlGet($list = [], $sql = []) 二维数组仿SQL查询获取多条数据
 * @method getArrayForget($list = [], $key = '') 将通过指定的键来移除集合中对应的内容
 * @method arrayGet($list = [], $key = '', $default = '') 返回指定键的集合项，如果该键在集合中不存在，则返回空
 * @method getForPage($list = [], $page = 1, $size = 3, $type = 0) 返回一个含有指定页码数集合项的新集合。这个方法接受页码数作为其第一个参数，每页显示的项数作为其第二个参数
 * @method getHasKey($list = [], $key = []) 判断集合中是否存在指定键
 * @method getArrayLast($list = []) 返回集合中通过指定条件测试的最后一个元素
 * @method getArraySum($list = [], $key = '') 将集合传给指定的回调函数并返回运算总和结果
 * @method getArrayHierarchyNum($list = []) 获取数组循环的次数
 * @package App\Repositories\Common
 */
class BaseRepository extends Base
{
    /**
     * 分页
     *
     * $path = asset('/') . 'user.php';
     * $pageName = 'a=1&b=2&page';
     * $options = [
     *      'path' => $path,
     *      'pageName' => $pageName
     * ];
     *
     * 示例：this->getPaginate(array, 2, ['path' => asset('/'), 'pageName' => 'a=1&b=2&page=1']);
     */

    /**
     * 二维数组仿SQL查询获取一条数据
     *
     * $sql = [
     *      'where' => [
     *           [
     *                'name' => 'dsc',
     *                'value' => 'b2b2c',
     *                'condition' => '>' //条件查询
     *           ]
     *      ]
     *      'whereBetween' => [
     *           [
     *               'name' => 'price',
     *               'value' => [1, 100]
     *           ]
     *      ],
     *      'whereNotBetween' => [
     *           [
     *               'name' => 'price',
     *               'value' => [1, 100]'
     *           ]
     *      ],
     *      'whereIn' => [
     *           [
     *                'name' => 'cat_id',
     *                'value' => [1, 3, 5, 7, 8, 10, 11]
     *           ]
     *      ],
     *      'whereNotIn' => [
     *           [
     *                'name' => 'cat_id',
     *                'value' => [1, 3, 5, 7, 8, 10, 11]
     *            ]
     *      ]
     * ]
     *
     * @param array $list
     * @param array $sql
     * @return array
     *
     * 示例：$this->getArraySqlFirst($list = [], $sql);
     */

    /**
     * 二维数组仿SQL查询获取多条数据
     *
     * $sql = [
     *      'where' => [
     *           [
     *                'name' => 'dsc',
     *                'value' => 'b2b2c',
     *                'condition' => '>' //条件查询
     *           ]
     *      ]
     *      'whereBetween' => [
     *           [
     *               'name' => 'price',
     *               'value' => [1, 100]
     *           ]
     *      ],
     *      'whereNotBetween' => [
     *           [
     *               'name' => 'price',
     *               'value' => [1, 100]'
     *           ]
     *      ],
     *      'whereIn' => [
     *           [
     *                'name' => 'cat_id',
     *                'value' => [1, 3, 5, 7, 8, 10, 11]
     *           ]
     *      ],
     *      'whereNotIn' => [
     *           [
     *                'name' => 'cat_id',
     *                'value' => [1, 3, 5, 7, 8, 10, 11]
     *            ]
     *      ]
     * ]
     *
     * @param array $list
     * @param array $sql
     * @return array
     *
     * 示例：$this->getArraySqlGet($list = [], $sql);
     */
}
