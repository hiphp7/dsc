<?php

namespace App\Plugins\Dscapi\app\func;

use App\Repositories\Common\DscRepository;

class base
{

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
    }

    /**
     * 查询字段
     *
     * @access  public
     * @param   integer $select 字段
     * @return  bool
     */
    public static function get_select_field($select = array(), $alias = '')
    {
        if ($select && is_array($select)) {
            if ($alias) {
                foreach ($select as $key => $row) {
                    $select[$key] = $alias . $row;
                }
            }

            $select = implode(', ', $select);
        } elseif (empty($select)) {
            $select = $alias . "*";
        }

        return $select;
    }

    /**
     * 查询条件
     *
     * @access  public
     * @param   integer $val 值
     * @param   string $field 字段
     * @return  bool
     */
    public static function get_where($val = 0, $field = '', $conditions = "")
    {
        $where = "";

        if ($val != -1) {
            $is_val = 1;
            if (!$val) {
                $is_val = 0;

                if (is_numeric($val)) {
                    $is_val = 1;
                }
            }

            if ($is_val == 1 && $field) {
                $where .= " AND " . $field . Base::db_create_in($val);
            }
        }

        $where .= $conditions;

        return $where;
    }

    /**
     * 查询条件
     * 时间戳
     *
     * @access  public
     * @param   integer $val 值
     * @param   string $field 字段
     * @param   string $time_type 条件类型 0:大于或等于, 1:小于或等于
     * @return  bool
     */
    public static function get_where_time($val = 0, $field = '', $time_type = 0, $conditions = "")
    {
        $where = "";

        if ($val != -1) {
            if ($val && $field) {
                if ($time_type == 1) {
                    //结束时间
                    $where .= " AND " . $field . " <= '$val'";
                } else {
                    //开始时间
                    $where .= " AND " . $field . " >= '$val'";
                }
            }
        }

        $where .= $conditions;

        return $where;
    }

    /**
     * 多表关联查询
     * 组合表别名
     * $table array
     * return array
     */
    public static function get_join_on($join_on, $alias)
    {
        $alias = explode(",", $alias);
        $arr = array();
        foreach ($join_on as $key => $row) {
            if ($key > 0) {
                $row = explode("|", $row);
                $arr[$key] = " ON " . $alias[$key - 1] . "." . $row[0] . " =" . $alias[$key] . "." . $row[1];
            } else {
                $arr[$key] = '';
            }
        }

        return $arr;
    }

    /**
     * 表数组分组
     * $table array
     */
    public static function get_join_table($table = '', $join_on = '', $select = '', $where = 1, $result_type = 0)
    {
        $alias = '';
        $left = '';
        foreach ($table as $key => $row) {
            if ($key == 0) {
                $left .= $GLOBALS['dsc']->table($row['table']) . " AS " . $row['alias'] . ",";
            } else {
                $left .= " LEFT JOIN " . $GLOBALS['dsc']->table($row['table']) . " AS " . $row['alias'] . ",";
            }

            $alias .= $row['alias'] . ",";
        }

        $join_on = Base::get_join_on($join_on, $alias);
        $left = explode(",", substr($left, 0, -1));
        $alias = explode(",", substr($alias, 0, -1));

        $sql = '';
        foreach ($left as $key => $row) {
            foreach ($join_on as $akey => $arow) {
                if ($key == $akey) {
                    $sql .= $row . $arow;
                }
            }
        }

        if ($select == '*') {
            $select = '';
            foreach ($alias as $key => $row) {
                $select .= $row . ".*" . ",";
            }

            $select = substr($select, 0, -1);
        }

        $sql = "SELECT $select FROM " . $sql . " WHERE $where";

        if ($result_type == 1) {
            return $GLOBALS['db']->getAll($sql);
        } elseif ($result_type == 2) {
            return $GLOBALS['db']->getRow($sql);
        } else {
            return $GLOBALS['db']->getOne($sql);
        }
    }

    /**
     * 表数组分组
     * $table array
     */
    public static function get_alias_table($table = '', $k = 0)
    {
        $alias = '';
        foreach ($table as $key => $row) {
            $alias .= substr($row, 0, 1);
        }

        return $alias . $k;
    }

    /**
     * 过滤整型数组
     *
     * @access  public
     * @param   integer id
     * @return  bool
     */
    public static function get_intval($id)
    {
        if (isset($id) && !empty($id)) {
            $exid = explode(",", $id);

            if (count($exid) > 1) {
                $id = self::addslashes_deep($exid);
            } else {
                $id = intval($id);
            }
        } else {
            $id = 0;
        }

        return $id;
    }

    /**
     * 过滤字符串数组
     *
     * @access  public
     * @param   integer id
     * @return  bool
     */
    public static function get_addslashes($id)
    {
        if (isset($id) && !empty($id)) {
            $exid = explode(",", $id);

            if (count($exid) > 1) {
                $id = self::addslashes_deep($exid);
            } else {
                $id = addslashes($id);
            }
        } else {
            $id = 0;
        }

        return $id;
    }

    /**
     * 递归方式的对变量中的特殊字符进行转义
     *
     * @access  public
     * @param   mix $value
     *
     * @return  mix
     */
    public static function addslashes_deep($value)
    {
        if (empty($value)) {
            return $value;
        } else {
            return is_array($value) ? array_map('addslashes_deep', $value) : addslashes($value);
        }
    }

    /**
     * 过滤 $_REQUEST
     * 解决跨站脚本攻击（XSS）
     * script脚本
     */
    public static function get_request_filter($get = '', $type = 0)
    {
        if ($get && $type) {
            foreach ($get as $key => $row) {
                $preg = "/<script[\s\S]*?<\/script>/i";
                if ($row && !is_array($row)) {
                    $lower_row = strtolower($row);
                    $lower_row = !empty($lower_row) ? preg_replace($preg, "", stripslashes($lower_row)) : '';

                    if (strpos($lower_row, "</script>") !== false) {
                        $get[$key] = compile_str($lower_row);
                    } elseif (strpos($lower_row, "alert") !== false) {
                        $get[$key] = '';
                    } elseif (strpos($lower_row, "updatexml") !== false || strpos($lower_row, "extractvalue") !== false || strpos($lower_row, "floor") !== false) {
                        $get[$key] = '';
                    } else {
                        $get[$key] = make_semiangle($row);
                    }
                } else {
                    $get[$key] = $row;
                }
            }
        } else {
            if ($_REQUEST) {
                foreach ($_REQUEST as $key => $row) {
                    $preg = "/<script[\s\S]*?<\/script>/i";
                    if ($row && !is_array($row)) {
                        $lower_row = strtolower($row);
                        $lower_row = !empty($lower_row) ? preg_replace($preg, "", stripslashes($lower_row)) : '';

                        if (strpos($lower_row, "</script>") !== false) {
                            $_REQUEST[$key] = compile_str($lower_row);
                        } elseif (strpos($lower_row, "alert") !== false) {
                            $_REQUEST[$key] = '';
                        } elseif (strpos($lower_row, "updatexml") !== false || strpos($lower_row, "extractvalue") !== false || strpos($lower_row, "floor") !== false) {
                            $_REQUEST[$key] = '';
                        } else {
                            $_REQUEST[$key] = make_semiangle($row);
                        }
                    } else {
                        $_REQUEST[$key] = $row;
                    }
                }
            }
        }

        if ($get && $type == 1) {
            $_POST = $get;
            return $_POST;
        } elseif ($get && $type == 2) {
            $_GET = $get;
            return $_GET;
        } else {
            return $_REQUEST;
        }
    }

    /**
     * 查询关联品牌ID
     */
    public static function get_link_seller_brand($brand_id = 0, $type = 0)
    {
        if ($type == 1) {
            /**
             * 商家品牌ID
             */
            $sql = "SELECT GROUP_CONCAT(bid) AS brand_id FROM " . $GLOBALS['dsc']->table('link_brand') . " WHERE brand_id = '$brand_id'";
        } else {
            /**
             * 平台品牌ID
             */
            $sql = "SELECT GROUP_CONCAT(brand_id) AS brand_id FROM " . $GLOBALS['dsc']->table('link_brand') . " WHERE bid = '$brand_id'";
        }

        return $GLOBALS['db']->getRow($sql);
    }

    /**
     * 创建像这样的查询: "IN('a','b')";
     *
     * @access   public
     * @param    mix $item_list 列表数组或字符串
     * @param    string $field_name 字段名称
     *
     * @return   void
     */
    public static function db_create_in($item_list, $field_name = '', $not = '')
    {
        if (!empty($not)) {
            $not = " " . $not;
        }

        if (empty($item_list)) {
            return $field_name . $not . " IN ('') ";
        } else {
            if (!is_array($item_list)) {
                $item_list = explode(',', $item_list);
            }
            $item_list = array_unique($item_list);
            $item_list_tmp = '';
            foreach ($item_list as $item) {
                if ($item !== '') {
                    $item = addslashes($item);
                    $item_list_tmp .= $item_list_tmp ? ",'$item'" : "'$item'";
                }
            }
            if (empty($item_list_tmp)) {
                return $field_name . $not . " IN ('') ";
            } else {
                return $field_name . $not . ' IN (' . $item_list_tmp . ') ';
            }
        }
    }

    /**
     * 接口文件名
     * $array
     */
    public static function get_interface_file($dirname, $interface)
    {
        $arr = array();
        foreach ($interface as $key => $row) {
            $arr[$key] = $dirname . "/interface/" . $row . ".php";
        }

        return $arr;
    }

    public static function __callStatic($method, $arguments)
    {
        return call_user_func_array(array(self, $method), $arguments);
    }
}
