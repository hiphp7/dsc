<?php

namespace App\Plugins\Dscapi\app\model;

use App\Plugins\Dscapi\app\func\common;
use App\Plugins\Dscapi\app\func\base;
use App\Plugins\Dscapi\languages\goodsLang;

abstract class goodsModel extends common
{
    private $alias_config;

    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    public function __construct()
    {
        $this->goodsModel();
    }

    /**
     * 构造函数
     *
     * @access  public
     * @param   integer $goods_id 商品ID
     * @return  bool
     */
    public function goodsModel($table = '')
    {
        $this->alias_config = array(
            'goods' => 'g',                             //商品表
            'warehouse_goods' => 'wg',                  //商品仓库模式表
            'warehouse_area_goods' => 'wag',            //商品地区模式表
            'goods_gallery' => 'gll',                   //商品相册表
            'goods_attr' => 'ga',                       //商品属性表

            //商品运费模板表
            'goods_transport' => 'gtt',
            'goods_transport_express' => 'gtes',
            'goods_transport_extend' => 'gted',
        );

        if ($table) {
            return $this->alias_config[$table];
        } else {
            return $this->alias_config;
        }
    }

    /**
     * 查询条件
     *
     * @access  public
     * @param   string where    查询条件
     * @return  string
     */
    public function get_where($val = array(), $alias = '')
    {
        $where = 1;

        /* 商品ID */
        $where .= base::get_where($val['goods_id'], $alias . 'goods_id');

        /* 商品货号 */
        $where .= base::get_where($val['goods_sn'], $alias . 'goods_sn');

        /* 商品回收站 */
        $where .= base::get_where($val['is_delete'], $alias . 'is_delete');

        /* 商品条形码 */
        $where .= base::get_where($val['bar_code'], $alias . 'bar_code');

        /* 商品分类ID */
        $where .= base::get_where($val['cat_id'], $alias . 'cat_id');

        /* 商品品牌ID */
        $where .= base::get_where($val['brand_id'], $alias . 'brand_id');

        /* 商家商品分类ID */
        $where .= base::get_where($val['user_cat'], $alias . 'user_cat');

        /* 商家ID */
        if ($val['seller_type'] > 0) {
            $where .= base::get_where($val['seller_id'], $alias . 'ru_id');
        } else {
            $where .= base::get_where($val['seller_id'], $alias . 'user_id');
        }

        /* 商品仓库ID */
        $where .= base::get_where($val['w_id'], $alias . 'w_id');

        /* 商品仓库地区ID */
        $where .= base::get_where($val['a_id'], $alias . 'a_id');

        /* 仓库地区ID */
        $where .= base::get_where($val['region_id'], $alias . 'region_id');

        /* 仓库地区省级名称 */
        $where .= base::get_where($val['province_name'], 'r1.province_name');

        /* 仓库地区市级名称 */
        $where .= base::get_where($val['city_name'], 'r1.city_name');

        /* 商品仓库\地区货号 */
        $where .= base::get_where($val['region_sn'], $alias . 'region_sn');

        /* 商品相册ID */
        $where .= base::get_where($val['img_id'], $alias . 'img_id');

        /* 属性类型 */
        $where .= base::get_where($val['attr_id'], $alias . 'attr_id');

        /* 商品属性ID */
        $where .= base::get_where($val['goods_attr_id'], $alias . 'goods_attr_id');

        /* 商品运费模板ID */
        $where .= base::get_where($val['tid'], $alias . 'tid');

        return $where;
    }

    /**
     * 查询获取列表数据
     *
     * @access  public
     * @param   string $table 表名称
     * @param   string $select 查询字段
     * @param   string where    查询条件
     * @param   string $page_size 页码
     * @param   string $page 当前页
     * @return  string
     */
    public function get_select_list($table, $select, $where, $page_size, $page, $sort_by, $sort_order)
    {
        $left_join = '';
        $region_where = '';
        $region_select = '';
        if ($table == 'warehouse_area_goods') {
            if (strpos($where, 'province_name') !== false) {
                $region_where = ' AND r1.region_type = 1';
                $where = str_replace('province_name', 'region_name', $where);
            } elseif (strpos($where, 'city_name') !== false) {
                $region_where = ' AND r1.region_type = 2';
                $where = str_replace('city_name', 'region_name', $where);
            }

            if (!empty($region_where)) {
                $left_join = " LEFT JOIN " . $GLOBALS['dsc']->table('region_warehouse') . " AS r1 ON r1.region_id = wag.region_id " . $region_where;
            }

            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('warehouse_area_goods') . " AS wag " .
                $left_join .
                " WHERE " . $where;
            $result['record_count'] = $GLOBALS['db']->getOne($sql);

            if ($sort_by) {
                $where .= " ORDER BY wag.$sort_by $sort_order ";
            }
        } else {
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table($table) . " WHERE " . $where;
            $result['record_count'] = $GLOBALS['db']->getOne($sql);

            if ($sort_by) {
                $where .= " ORDER BY $sort_by $sort_order ";
            }
        }

        $where .= " LIMIT " . ($page - 1) * $page_size . ",$page_size";

        if ($table == 'warehouse_area_goods') {
            if (strpos($where, 'province_name') !== false) {
                $region_where = ' AND r1.region_type = 1';
                $where = str_replace('province_name', 'region_name', $where);
            } elseif (strpos($where, 'city_name') !== false) {
                $region_where = ' AND r1.region_type = 2';
                $where = str_replace('city_name', 'region_name', $where);
            }

            $sql = "SELECT wag.*, r1.region_name FROM " . $GLOBALS['dsc']->table('warehouse_area_goods') . " AS wag " .
                " LEFT JOIN " . $GLOBALS['dsc']->table('region_warehouse') . " AS r1 ON r1.region_id = wag.region_id " . $region_where .
                " WHERE " . $where;
            $result['list'] = $GLOBALS['db']->getAll($sql);
        } else {
            $sql = "SELECT " . $select . " FROM " . $GLOBALS['dsc']->table($table) . " WHERE " . $where;
            $result['list'] = $GLOBALS['db']->getAll($sql);
        }

        return $result;
    }

    /**
     * 多表关联查询
     * 查询获取列表数据
     *
     * @access  public
     * @param   string $table 表名称
     * @param   string $select 查询字段
     * @param   string where    查询条件
     * @param   string $page_size 页码
     * @param   string $page 当前页
     * @return  string
     */
    public function get_join_select_list($table, $select, $where, $join_on = array())
    {
        $result = base::get_join_table($table, $join_on, $select, $where, 1);

        return $result;
    }

    /**
     * 查询获取单条数据
     *
     * @access  public
     * @param   string $table 表名称
     * @param   string $select 查询字段
     * @param   string where    查询条件
     * @return  string
     */
    public function get_select_info($table, $select, $where)
    {
        $region_where = '';
        if ($table == 'warehouse_area_goods') {
            if (strpos($where, 'province_name') !== false) {
                $region_where = ' AND r1.region_type = 1';
                $where = str_replace('province_name', 'region_name', $where);
            } elseif (strpos($where, 'city_name') !== false) {
                $region_where = ' AND r1.region_type = 2';
                $where = str_replace('city_name', 'region_name', $where);
            }

            $sql = "SELECT wag.*, r1.region_name FROM " . $GLOBALS['dsc']->table('warehouse_area_goods') . " AS wag " .
                " LEFT JOIN " . $GLOBALS['dsc']->table('region_warehouse') . " AS r1 ON r1.region_id = wag.region_id " . $region_where .
                " WHERE " . $where . " LIMIT 1";
            $goods = $GLOBALS['db']->getRow($sql);
        } else {
            $sql = "SELECT " . $select . " FROM " . $GLOBALS['dsc']->table($table) . " WHERE " . $where . " LIMIT 1";
            $goods = $GLOBALS['db']->getRow($sql);
        }

        return $goods;
    }

    /**
     * 多表关联查询
     * 查询获取单条数据
     *
     * @access  public
     * @param   string $table 表名称
     * @param   string $select 查询字段
     * @param   string where    查询条件
     * @return  string
     */
    public function get_join_select_info($table, $select, $where, $join_on)
    {
        $goods = base::get_join_table($table, $join_on, $select, $where, 2);
        return $goods;
    }

    /**
     * 插入数据
     *
     * @access  public
     * @param   string where    查询条件
     * @return  string
     */
    public function get_insert($table, $select, $format)
    {
        $config = array_flip($this->goodsModel());

        $goodsLang = goodsLang::lang_goods_insert();

        $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table($table), $select, "INSERT");
        $id = $GLOBALS['db']->insert_id();

        $info = $select;

        if ($id) {
            if ($table == $config['g']) {
                $info['goods_id'] = $id;
            } elseif ($table == $config['wg']) {
                $info['w_id'] = $id;
            } elseif ($table == $config['wag']) {
                $info['a_id'] = $id;
            } elseif ($table == $config['gll']) {
                $info['img_id'] = $id;
            } elseif ($table == $config['ga']) {
                $info['goods_attr_id'] = $id;
            } elseif ($table == $config['gtt']) {
                $info['tid'] = $id;
            } elseif ($table == $config['gtes']) {
                $info['id'] = $id;
            } elseif ($table == $config['gted']) {
                $info['id'] = $id;
            }
        }

        $common_data = array(
            'result' => "success",
            'msg' => $goodsLang['msg_success']['success'],
            'error' => $goodsLang['msg_success']['error'],
            'format' => $format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 多表循环
     * 插入数据
     *
     * @access  public
     * @param   string where    查询条件
     * @return  string
     */
    public function get_more_insert($table, $select, $format)
    {
        $goodsLang = goodsLang::lang_goods_insert();

        $first_table = $table[0];
        $first_select = $select[0];
        $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table($first_table), $first_select, "INSERT");
        $tid = $GLOBALS['db']->insert_id();

        $info = $select;
        $info['tid'] = $id;

        for ($i = 0; $i < count($table); $i++) {
            if ($i > 0 && $table[$i]) {
                if ($select[$i]) {
                    $select[$i]['tid'] = $tid;
                }
                $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table($table[$i]), $select[$i], "INSERT");
            }
        }

        $common_data = array(
            'result' => "success",
            'msg' => $goodsLang['msg_success']['success'],
            'error' => $goodsLang['msg_success']['error'],
            'format' => $format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 更新数据
     *
     * @access  public
     * @param   string where    查询条件
     * @return  string
     */
    public function get_update($table, $select, $where, $format, $info = [])
    {
        $goodsLang = goodsLang::lang_goods_update();

        $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table($table), $select, "UPDATE", $where);

        if ($info) {
            foreach ($info as $key => $row) {
                if (isset($select[$key])) {
                    $info[$key] = $select[$key];
                }
            }
        } else {
            $info = $select;
        }

        $common_data = array(
            'result' => "success",
            'msg' => $goodsLang['msg_success']['success'],
            'error' => $goodsLang['msg_success']['error'],
            'format' => $format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 多表循环
     * 插入数据
     *
     * @access  public
     * @param   string where    查询条件
     * @return  string
     */
    public function get_more_update($table, $select, $where, $format, $info = [])
    {
        $goodsLang = goodsLang::lang_goods_update();

        $first_table = $table[0];
        $first_select = $select[0];
        $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table($first_table), $first_select, "UPDATE", $where);

        for ($i = 0; $i < count($table); $i++) {
            if ($i > 0 && $table[$i]) {
                if ($select[$i]) {
                    $select[$i]['tid'] = $this->tid;
                }
                $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table($table[$i]), $select[$i], "UPDATE", $where);
            }
        }

        $common_data = array(
            'result' => "success",
            'msg' => $goodsLang['msg_success']['success'],
            'error' => $goodsLang['msg_success']['error'],
            'format' => $format,
            'info' => $info
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 数据删除
     *
     * @access  public
     * @param   string where    查询条件
     * @return  string
     */
    public function get_delete($table, $where, $format)
    {
        $goodsLang = goodsLang::lang_goods_delete();

        $return = false;
        if (strlen($where) != 1) {
            $sql = "DELETE FROM " . $GLOBALS['dsc']->table($table) . " WHERE " . $where;
            $GLOBALS['db']->query($sql);

            $return = true;
        } else {
            $error = goodsLang::DEL_NULL_PARAM_FAILURE;
        }

        $common_data = array(
            'result' => $return ? "success" : "failure",
            'msg' => $return ? $goodsLang['msg_success']['success'] : $goodsLang['msg_failure'][$error]['failure'],
            'error' => $return ? $goodsLang['msg_success']['error'] : $goodsLang['msg_failure'][$error]['error'],
            'format' => $format
        );

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 数据删除
     *
     * @access  public
     * @param   string where    查询条件
     * @return  string
     */
    public function get_more_delete($table, $where, $format)
    {
        $goodsLang = goodsLang::lang_goods_delete();

        if (strlen($where) != 1) {
            for ($i = 0; $i < count($table); $i++) {
                $sql = "DELETE FROM " . $GLOBALS['dsc']->table($table[$i]) . " WHERE " . $where;
                $GLOBALS['db']->query($sql);
            }

            $common_data = array(
                'result' => 'success',
                'msg' => $goodsLang['msg_success']['success'],
                'error' => $goodsLang['msg_success']['error'],
                'format' => $format
            );
        } else {
            $common_data = array(
                'result' => 'failure',
                'msg' => $goodsLang['where_failure']['failure'],
                'error' => $goodsLang['where_failure']['error'],
                'format' => $format
            );
        }

        common::common($common_data);
        return common::data_back();
    }

    /**
     * 格式化返回值
     *
     * @access  public
     * @return  string
     */
    public function get_list_common_data($result, $page_size, $page, $goodsLang, $format)
    {
        $common_data = array(
            'page_size' => $page_size,
            'page' => $page,
            'result' => empty($result['record_count']) ? "failure" : 'success',
            'msg' => empty($result['record_count']) ? $goodsLang['msg_failure']['failure'] : $goodsLang['msg_success']['success'],
            'error' => empty($result['record_count']) ? $goodsLang['msg_failure']['error'] : $goodsLang['msg_success']['error'],
            'format' => $format
        );

        common::common($common_data);
        $result = common::data_back($result, 1);

        return $result;
    }

    /**
     * 格式化返回值
     *
     * @access  public
     * @return  string
     */
    public function get_info_common_data_fs($goods, $goodsLang, $format)
    {
        $common_data = array(
            'result' => empty($goods) ? "failure" : 'success',
            'msg' => empty($goods) ? $goodsLang['msg_failure']['failure'] : $goodsLang['msg_success']['success'],
            'error' => empty($goods) ? $goodsLang['msg_failure']['error'] : $goodsLang['msg_success']['error'],
            'format' => $format
        );

        common::common($common_data);
        $goods = common::data_back($goods);

        return $goods;
    }

    /**
     * 格式化返回值
     *
     * @access  public
     * @return  string
     */
    public function get_info_common_data_f($goodsLang, $format)
    {
        $goods = array();

        $common_data = array(
            'result' => 'failure',
            'msg' => $goodsLang['where_failure']['failure'],
            'error' => $goodsLang['where_failure']['error'],
            'format' => $format
        );

        common::common($common_data);
        $goods = common::data_back($goods);

        return $goods;
    }

    /**
     * 批量插入商品
     *
     * @access  public
     * @param   string where    查询条件
     * @return  string
     */
    public function get_goods_batch_insert($table, $select, $format)
    {
        $goodsLang = goodsLang::lang_goods_batch_insert();

        //转换数据
        if (!empty($select)) {

            /* 是否处理缩略图 */
            $proc_thumb = (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0) ? false : true;

            //定义图片路径
            $admin_temp_dir = storage_public("/temp" . '/seller/' . "admin_0");

            // 如果目标目录不存在，则创建它
            if (!file_exists($admin_temp_dir)) {
                common::make_dir($admin_temp_dir);
            }
            //获取贡云属性类型id
            $sql = "SELECT cat_id FROM" . $GLOBALS['dsc']->table('goods_type') . "WHERE cat_name = 'cloud' LIMIT 1";
            $cat_id = $GLOBALS['db']->getOne($sql);

            //不存在插入
            if ($cat_id == 0 || $cat_id == '') {
                $goods_type = array(
                    'cat_name' => 'cloud',
                    'enabled' => '1'
                );
                $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('goods_type'), $goods_type, "INSERT");
                $cat_id = $GLOBALS['db']->insert_id();
            }

            if (isset($select)) {
                $select = is_array($select) ? $select : dsc_decode($select, true);
                foreach ($select as $k => $v) {
                    $goods = array();

                    //处理价格
                    $shop_price = !empty($v['suggestedPrice']) ? trim($v['suggestedPrice']) : 0;
                    //分转换为元
                    if ($shop_price > 0) {
                        $shop_price = $shop_price / 100;
                    }
                    $shop_price = floatval($shop_price);

                    $goods['goods_name'] = !empty($v['name']) ? trim(addslashes($v['name'])) : '';
                    $goods['bar_code'] = !empty($v['goodsCode']) ? trim($v['goodsCode']) : '';
                    $goods['shop_price'] = $shop_price;
                    $goods['goods_unit'] = !empty($v['unit']) ? trim($v['unit']) : '个';
                    $goods['cloud_id'] = intval($v['id']);
                    $goods['review_status'] = 3;
                    $goods['goods_type'] = $cat_id;
                    $goods['freight'] = 0;
                    $goods['cloud_goodsname'] = !empty($v['name']) ? trim(addslashes($v['name'])) : '';
                    $goods['goods_cause'] = '1,3';//仅支持退款和退货
                $goods['notification'] = '';//初始化商品下架原因
                $id = 0;

                    //判断是重新推送过来的商品
                    $sql = "SELECT goods_id FROM" . $GLOBALS['dsc']->table($table) . "WHERE cloud_id = '" . $v['id'] . "' LIMIT 1";
                    $goods_id = $GLOBALS['db']->getOne($sql);
                    //已经存在则更新商品信息
                    if ($goods_id > 0) {
                        $id = $goods_id;
                        $sql_where = " goods_id = '$id'";
                        $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table($table), $goods, "UPDATE", $sql_where);
                        //清空商品相册
                        $sql = "SELECT img_original , img_url , thumb_url FROM" . $GLOBALS['dsc']->table('goods_gallery') . "WHERE goods_id = '$id'";
                        $goods_gallery_list = $GLOBALS['db']->getAll($sql);
                        if (!empty($goods_gallery_list)) {
                            foreach ($goods_gallery_list as $gallery_list_key => $gallery_list_val) {
                                if ($gallery_list_val['img_original'] != '' && strpos($gallery_list_val['img_original'], 'http://') === false && strpos($gallery_list_val['img_original'], 'https://') === false) {
                                    dsc_unlink(storage_public($gallery_list_val['img_original']));
                                }
                                if ($gallery_list_val['img_url'] != '' && strpos($gallery_list_val['img_url'], 'http://') === false && strpos($gallery_list_val['img_url'], 'https://') === false) {
                                    dsc_unlink(storage_public($gallery_list_val['img_url']));
                                }
                                if ($gallery_list_val['thumb_url'] != '' && strpos($gallery_list_val['thumb_url'], 'http://') === false && strpos($gallery_list_val['thumb_url'], 'https://') === false) {
                                    dsc_unlink(storage_public($gallery_list_val['thumb_url']));
                                }
                            }
                        }
                        $sql = "DELETE FROM" . $GLOBALS['dsc']->table('goods_gallery') . "WHERE goods_id = '$id'";
                        $GLOBALS['db']->query($sql);
                    } else {
                        $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table($table), $goods, "INSERT");
                        $id = $GLOBALS['db']->insert_id();
                    }
                    //商品id为0时  跳出循环
                    if ($id == 0) {
                        continue;
                    }

                    //处理商品详情图片
                    if ($v['goodsDetailList']) {
                        $j = 1;
                        $goods_desc = '';
                        foreach ($v['goodsDetailList'] as $detail_key => $detail_val) {
                            if (!empty($detail_val['imagePath']) && ($detail_val['imagePath'] != 'http://') && (strpos($detail_val['imagePath'], 'http://') !== false || strpos($detail_val['imagePath'], 'https://') !== false)) {
                                if ($j == $detail_val['orderNo']) {
                                    $goods_desc .= '<p><img src="' . $detail_val['imagePath'] . '" title="' . basename($detail_val['imagePath']) . '"/></p>';
                                }
                                $j++;
                            }
                        }

                        $arr['goods_desc'] = $goods_desc;
                        $sql_where = " goods_id = '$id'";
                        $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table($table), $arr, "UPDATE", $sql_where);
                    }

                    //处理相册
                    if ($v['goodsNavigateList']) {
                        $i = 0;
                        foreach ($v['goodsNavigateList'] as $gallery_key=>$gallery_val) {
                            $i++;
                            if (!empty($gallery_val['navigateImage']) && ($gallery_val['navigateImage'] != 'http://') && (strpos($gallery_val['navigateImage'], 'http://') !== false || strpos($gallery_val['navigateImage'], 'https://') !== false)) {
                                $goods_gallery = array(
                                    'goods_id' => $id,
                                    'img_original' => $gallery_val['navigateImage'],
                                    'img_desc' => intval($gallery_val['orderNo']),
                                    'img_url' => $gallery_val['navigateImage'],
                                    'thumb_url' => $gallery_val['navigateImage']
                                );
                                
                                $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('goods_gallery'), $goods_gallery, "INSERT");
                                //把第一张相册图片保存为商品图片
                                if ($i == 1) {
                                    $goods_sn = common::generate_goods_sn($id);//生成货号
                                    
                                    $goods_arr = array(
                                        'original_img' => $gallery_val['navigateImage'],
                                        'goods_img' => $gallery_val['navigateImage'],
                                        'goods_thumb' => $gallery_val['navigateImage'],
                                        'goods_sn' => $goods_sn
                                    );
                                    $sql_where = " goods_id = '$id'";
                                    $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table($table), $goods_arr, "UPDATE", $sql_where);
                                }
                            }
                        }
                    }


                    //属性入库
                    if ($v['specDesc']) {
                        $specDesc = dsc_decode($v['specDesc'], true);
                        if (!empty($specDesc)) {
                            foreach ($specDesc as $specDesc_key => $specDesc_val) {
                                $specDesc_arr = array(
                                'cloud_attr_id' => intval($specDesc_val['specificationId']),
                                'attr_name' => addslashes($specDesc_val['name']),
                                'cat_id' => $cat_id,
                                'attr_type' => 1
                            );

                                //判断是否存在该类型
                                $sql = "SELECT attr_id FROM" . $GLOBALS['dsc']->table('attribute') . "WHERE (cloud_attr_id = '" . $specDesc_val['specificationId'] . "' OR attr_name = '" . addslashes($specDesc_val['name']) . "') AND cat_id = '$cat_id' LIMIT 1";
                                $attr_id = $GLOBALS['db']->getOne($sql);

                                if ($attr_id == 0 || $attr_id == '') {
                                    $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('attribute'), $specDesc_arr, "INSERT");
                                    $attr_id = $GLOBALS['db']->insert_id();
                                } else {
                                    $sql_where = " attr_id = '$attr_id'";
                                    $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('attribute'), $specDesc_arr, "UPDATE", $sql_where);
                                }

                                //规格入库

                                if (!empty($specDesc_val['value'])) {
                                    foreach ($specDesc_val['value'] as $value_key => $value_val) {
                                        $value_arr = array(
                                        'cloud_id' => intval($value_val['specificationDetailId']),
                                        'attr_value' => addslashes($value_val['detailName']),
                                        'attr_id' => $attr_id,
                                        'goods_id' => $id,
                                        'attr_gallery_flie' => $value_val['specificationDetailImage']
                                    );

                                        //判断是否存在该类型
                                        $sql = "SELECT goods_attr_id FROM" . $GLOBALS['dsc']->table('goods_attr') . "WHERE cloud_id = '" . $value_val['specificationDetailId'] . "' AND goods_id = '$id' LIMIT 1";
                                        $goods_attr_id = $GLOBALS['db']->getOne($sql);

                                        if ($goods_attr_id == 0 || $goods_attr_id == '') {
                                            $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('goods_attr'), $value_arr, "INSERT");
                                            $goods_attr_id = $GLOBALS['db']->insert_id();
                                        } else {
                                            $sql_where = " goods_attr_id = '$goods_attr_id'";
                                            $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('goods_attr'), $value_arr, "UPDATE", $sql_where);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $goods_number = 0;
                    $shop_pirce = 0;
                    //sku信息处理
                    if ($v['products']) {
                        $products = $v['products'];
                        foreach ($products as $products_key => $products_val) {
                            //格式插入数据
                            $insert_attr = array();
                            $insert_attr['goods_id'] = $id;
                            if ($products_val['specInfo']) {
                                //获取本地属性id
                                $sql = "SELECT goods_attr_id FROM" . $GLOBALS['dsc']->table('goods_attr') . "WHERE cloud_id in(" . $products_val['specInfo'] . ") AND goods_id = '$id' ";
                                $goods_attr_arr = $GLOBALS['db']->getCol($sql);
                                if ($goods_attr_arr) {

                                //清除货品表
                                    $sql = "DELETE FROM" . $GLOBALS['dsc']->table('products') . " WHERE goods_id = '$id' AND cloud_product_id = '" . intval($products_val['id']) . "'";
                                    $GLOBALS['db']->query($sql);

                                    //处理货品价格
                                    $product_price = !empty($products_val['inventory']['salePrice']) ? trim($products_val['inventory']['salePrice']) : 0;
                                    //分转换为元
                                    if ($product_price > 0) {
                                        $product_price = $product_price / 100;
                                    }
                                    $product_price = floatval($product_price);
                                    $insert_attr['product_price'] = $product_price;
                                    $insert_attr['goods_attr'] = implode('|', $goods_attr_arr);
                                    $insert_attr['product_sn'] = addslashes($products_val['productBn']);
                                    $insert_attr['product_number'] = intval($products_val['inventory']['inventoryNum']);
                                    $insert_attr['cloud_product_id'] = intval($products_val['id']);//贡云货品idid
                                $insert_attr['inventoryid'] = intval($products_val['inventory']['id']);//贡云库存id
                                $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('products'), $insert_attr, "INSERT");
                                    $goods_number += $insert_attr['product_number'];
                                    if ($shop_pirce == 0 || $product_price < $shop_price) {
                                        $shop_price = $product_price;
                                    }
                                }
                            }
                        }
                    }

                    //处理商品库存、本店价格
                    if ($goods_number || $shop_price) {
                        $arr['goods_number'] = empty($goods_number) ? 0 : $goods_number;
                        $arr['shop_price'] = empty($shop_price) ? 0 : $shop_price;
                        $sql_where = " goods_id = '$id'";
                        $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table($table), $arr, "UPDATE", $sql_where);
                    }
                }
            }
            //暂时注释
            $common_data = array(
                'code' => $goodsLang['msg_success']['code'],
                'message' => $goodsLang['msg_success']['message'],
                'format' => $format
            );
        } else {
            $common_data = array(
                'code' => $goodsLang['msg_failure']['code'],
                'message' => $goodsLang['msg_failure']['message'],
                'format' => $format
            );
        }
        common::common($common_data);

        return common::data_back('', 2);
    }

    /**
     * 批量插入商品
     *
     * @access  public
     * @param   string where    查询条件
     * @return  string
     */
    public function get_goodsnotification_update($table, $select, $format)
    {
        $goodsLang = goodsLang::lang_goods_batch_insert();
        $common_data = array();

        //转换数据
        if (!empty($select)) {
            //获取系统商品id
            $goods_id = 0;
            if ($select['goodsId'] > 0) {
                $sql = "SELECT goods_id FROM" . $GLOBALS['dsc']->table('goods') . "WHERE cloud_id = '" . $select['goodsId'] . "' LIMIT 1";
                $goods_id = $GLOBALS['db']->getOne($sql);
            }
            //获取订单id
            $order = array();
            if ($select['orderSn']) {
                $sql = "SELECT oi.order_id , oi.order_sn , oi.user_id,oi.surplus,oi.money_paid,oi.bonus_id,oi.integral_money,oi.bonus,oi.coupons FROM" . $GLOBALS['dsc']->table('order_goods')
                    . "AS og LEFT JOIN " . $GLOBALS['dsc']->table('order_cloud')
                    . "AS oc ON oc.rec_id = og.rec_id LEFT JOIN " . $GLOBALS['dsc']->table('order_info')
                    . " AS oi ON oi.order_id = og.order_id WHERE oc.parentordersn = '" . $select['orderSn'] . "'";
                $order = $GLOBALS['db']->getAll($sql);
            }

            switch ($select['mainType']) {
                case 101://商品上架通知
                    if ($goods_id > 0) {
                        $sql = "UPDATE " . $GLOBALS['dsc']->table('goods') .
                            " SET is_on_sale = '1' ,  last_update = '" . gmtime() . "' " .
                            "WHERE goods_id = '$goods_id'";
                        $GLOBALS['db']->query($sql);
                        $sql = "SELECT act_id FROM " . $GLOBALS['dsc']->table('presale_activity') . " WHERE goods_id ='$goods_id'";
                        if ($GLOBALS['db']->getOne($sql, true)) {
                            $GLOBALS['db']->query("DELETE FROM " . $GLOBALS['dsc']->table('presale_activity') . " WHERE goods_id = '$goods_id'");
                            $GLOBALS['db']->query("DELETE FROM " . $GLOBALS['dsc']->table('cart') . " WHERE goods_id = '$goods_id'");
                        }
                    } else {
                        $common_data = array(
                            'code' => $goodsLang['msg_failure']['code'],
                            'message' => $goodsLang['msg_failure']['message'],
                            'format' => $format
                        );
                    }
                    break;

                case 102://商品下架通知
                    if ($goods_id > 0) {
                        $sql = "UPDATE " . $GLOBALS['dsc']->table('goods') .
                            " SET is_on_sale = '0' ,  last_update = '" . gmtime() . "' " .
                            "WHERE goods_id = '$goods_id'";
                        $GLOBALS['db']->query($sql);
                        $sql = "DELETE FROM " . $GLOBALS['dsc']->table('cart') . " WHERE goods_id = '$goods_id'";
                        $GLOBALS['db']->query($sql);
                    } else {
                        $common_data = array(
                            'code' => $goodsLang['msg_failure']['code'],
                            'message' => $goodsLang['msg_failure']['message'],
                            'format' => $format
                        );
                    }
                    break;

                case 104://商品成本价变更改变状态为下架状态
                    if ($goods_id > 0) {
                        $sql = " UPDATE " . $GLOBALS['dsc']->table('goods') .
                            " SET is_on_sale = '0' ,  last_update = '" . gmtime() . "', notification = '成本价变更' " .
                            "WHERE goods_id = '$goods_id' ";
                        $GLOBALS['db']->query($sql);
                        $sql = " DELETE FROM " . $GLOBALS['dsc']->table('cart') . " WHERE goods_id = '$goods_id' ";
                        $GLOBALS['db']->query($sql);
                    } else {
                        $common_data = array(
                            'code' => $goodsLang['msg_failure']['code'],
                            'message' => $goodsLang['msg_failure']['message'],
                            'format' => $format
                        );
                    }
                    break;

                case 201://订单取消通知
                    if (!empty($order)) {
                        foreach ($order as $k => $v) {

                            /* 标记订单为“无效” */
                            $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('order_info'), array('order_status' => OS_INVALID), 'UPDATE', "order_id = '" . $v['order_id'] . "'");
                            /* 记录log */
                            $log_time = gmtime();
                            $sql = 'INSERT INTO ' . $GLOBALS['dsc']->table('order_action') .
                                ' (order_id, action_user, order_status, shipping_status, pay_status, action_place, action_note, log_time) ' .
                                'SELECT ' .
                                "order_id, '支付超时', '" . OS_INVALID . "', '" . SS_UNSHIPPED . "', '" . PS_UNPAYED . "', 0, '支付超时', '$log_time' " .
                                'FROM ' . $GLOBALS['dsc']->table('order_info') . " WHERE order_sn = '" . $v['order_sn'] . "'";
                            $GLOBALS['db']->query($sql);

                            $this->return_user_surplus_integral_bonus($v);
                        }
                    } else {
                        $common_data = array(
                            'code' => $goodsLang['msg_failure']['code'],
                            'message' => $goodsLang['msg_failure']['message'],
                            'format' => $format
                        );
                    }
                    break;
            }
        } else {
            $common_data = array(
                'code' => $goodsLang['msg_failure']['code'],
                'message' => $goodsLang['msg_failure']['message'],
                'format' => $format
            );
        }

        if (!empty($common_data)) {
            $common_data = array(
                'code' => $goodsLang['msg_success']['code'],
                'message' => $goodsLang['msg_success']['message'],
                'format' => $format
            );
        }
        common::common($common_data);

        return common::data_back('', 2);
    }

    /**
     * 退回余额、积分、红包（取消、无效、退货时），把订单使用余额、积分、红包、优惠券设为0
     *
     * @access  public
     * @param   string order    订单信息
     * @return  string
     */
    public function return_user_surplus_integral_bonus($order)
    {
        /* 处理余额、积分、红包 */
        if ($order['user_id'] > 0 && $order['surplus'] > 0) {
            $surplus = $order['money_paid'] < 0 ? $order['surplus'] + $order['money_paid'] : $order['surplus'];
            common::log_account_change($order['user_id'], $surplus, 0, 0, 0, "由于取消、无效或退货操作，退回支付订单 " . $order['order_sn'] . " 时使用的预付款", ACT_OTHER);
            $GLOBALS['db']->query("UPDATE " . $GLOBALS['dsc']->table('order_info') . " SET `order_amount` = '0' WHERE `order_id` =" . $order['order_id']);
        }

        if ($order['user_id'] > 0 && $order['integral'] > 0) {
            common::log_account_change($order['user_id'], 0, 0, 0, $order['integral'], "由于取消、无效或退货操作，退回支付订单" . $order['order_sn'] . " 时使用的积分", ACT_OTHER);
        }

        if ($order['bonus_id'] > 0) {
            $sql = "UPDATE " . $GLOBALS['dsc']->table('user_bonus') .
                " SET order_id = 0, used_time = 0 " .
                "WHERE bonus_id = '" . $order['bonus_id'] . "' LIMIT 1";
            $GLOBALS['db']->query($sql);
        }


        /*退优惠券*/
        if ($order['coupons'] > 0) {
            $sql = "UPDATE " . $GLOBALS['dsc']->table('coupons_user') .
                " SET order_id = 0, is_use_time = 0, is_use=0 " .
                "WHERE order_id = '" . $order['order_id'] . "' LIMIT 1";
            $GLOBALS['db']->query($sql);
        }

        /* 修改订单 */
        $arr = array(
            'bonus_id' => 0,
            'bonus' => 0,
            'integral' => 0,
            'integral_money' => 0,
            'surplus' => 0
        );
        $GLOBALS['db']->autoExecute($GLOBALS['dsc']->table('order_info'), $arr, 'UPDATE', "order_id = '" . $order['order_id'] . "'");
    }
}
