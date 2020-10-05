<?php

namespace App\Libraries;

/**
 * 用于与数据库数据进行交换
 * Class exchange
 * @package App\Services
 */
class Exchange
{
    public $table;
    public $db;
    public $id;
    public $name;
    public $error_msg;

    /**
     * 构造函数
     *
     * @access  public
     * @param string $table 数据库表名
     * @param dbobject $db aodb的对象
     * @param string $id 数据表主键字段名
     * @param string $name 数据表重要段名
     *
     * @return void
     */
    public function __construct($table, &$db, $id, $name)
    {
        $this->table = $table;
        $this->db = &$db;
        $this->id = $id;
        $this->name = $name;
        $this->error_msg = '';
    }

    /**
     * 判断表中某字段是否重复，若重复则中止程序，并给出错误信息
     *
     * @access  public
     * @param string $col 字段名
     * @param string $name 字段值
     * @param integer $id
     *
     * @return void
     */ //ecmoban模板堂 --zhuo
    public function is_only($col, $name, $id = 0, $where = '', $table = '', $idType = '')
    {
        if (!empty($table)) {
            $table = $GLOBALS['dsc']->table($table);
        } else {
            $table = $this->table;
        }

        if (empty($idType)) {
            $idType = $this->id;
        }

        $sql = 'SELECT COUNT(*) FROM ' . $table . " WHERE $col = '$name'";
        $sql .= empty($id) ? '' : ' AND ' . $idType . " <> '$id'";
        $sql .= empty($where) ? '' : ' AND ' . $where;

        return ($GLOBALS['db']->getOne($sql) == 0);
    }

    /**
     * 返回指定名称记录再数据表中记录个数
     *
     * @access  public
     * @param string $col 字段名
     * @param string $name 字段内容
     *
     * @return   int        记录个数
     */
    public function num($col, $name, $id = 0, $where = '')
    {
        $sql = 'SELECT COUNT(*) FROM ' . $this->table . " WHERE $col = '$name'";
        $sql .= empty($id) ? '' : ' AND ' . $this->id . " != '$id' ";
        $sql .= empty($where) ? '' : ' AND ' . $where;

        return $this->db->getOne($sql);
    }

    /**
     * 编辑某个字段
     *
     * @access  public
     * @param string $set 要更新集合如" col = '$name', value = '$value'"
     * @param int $id 要更新的记录编号
     *
     * @return bool     成功或失败
     */ //ecmoban模板堂 --zhuo
    public function edit($set, $id, $table = '', $idType = '')
    {
        if (empty($table)) {
            $table = $this->table;
        } else {
            $table = $GLOBALS['dsc']->table($table);
        }

        if (empty($idType)) {
            $idType = $this->id;
        }

        $sql = 'UPDATE ' . $table . ' SET ' . $set . " WHERE " . $idType . " = '$id'";
        $res = $GLOBALS['db']->query($sql);
        if ($res || $res === 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 取得某个字段的值
     *
     * @access  public
     * @param int $id 记录编号
     * @param string $id 字段名
     *
     * @return string   取出的数据
     */
    public function get_name($id, $name = '')
    {
        if (empty($name)) {
            $name = $this->name;
        }

        $sql = "SELECT `$name` FROM " . $this->table . " WHERE $this->id = '$id'";

        return $this->db->getOne($sql);
    }

    /**
     * 删除条记录
     *
     * @access  public
     * @param int $id 记录编号
     *
     * @return bool
     */
    public function drop($id, $table = '', $idType = '')
    {
        if (empty($table)) {
            $table = $this->table;
        } else {
            $table = $GLOBALS['dsc']->table($table);
        }

        if (empty($idType)) {
            $idType = $this->id;
        }

        $sql = 'DELETE FROM ' . $table . " WHERE " . $idType . " = '$id'";

        return $this->db->query($sql);
    }
}
