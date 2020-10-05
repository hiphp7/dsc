<?php

namespace App\Libraries;

use Illuminate\Support\Facades\DB;

/**
 * MYSQL 公用类库
 * Class Mysql
 * @package App\Services
 */
class Mysql
{
    public function version()
    {
        return $this->getOne("select version() as ver");
    }

    public function error()
    {
        return '1';
    }

    public function errno()
    {
        return false;
    }

    public function insert_id()
    {
        return $this->getOne('SELECT LAST_INSERT_ID()');
    }

    public function errorMsg()
    {
        return false;
    }

    public function query($sql)
    {
        $sql = str_replace(['{pre}', '__PREFIX__'], config('database.connections.mysql.prefix'), $sql);
        $m = strtolower(substr(ltrim(trim($sql), '('), 0, 6));
        if ($m == 'select' || substr($m, 0, 4) == 'desc' || substr($m, 0, 4) == 'show') {
            $result = DB::select($sql);
            if (empty($result)) {
                $res = $result;
            } else {
                foreach ($result as $vo) {
                    $res[] = get_object_vars($vo);
                }
            }
        } elseif ($m == 'update') {
            DB::update($sql);
            $res = true;
        } elseif ($m == 'insert') {
            $res = DB::insert($sql);
        } elseif ($m == 'delete') {
            $res = DB::delete($sql);
        } else {
            $res = DB::statement($sql);
        }

        return $res;
    }

    public function selectLimit($sql, $num, $start = 0)
    {
        if ($start == 0) {
            $sql .= ' LIMIT ' . $num;
        } else {
            $sql .= ' LIMIT ' . $start . ', ' . $num;
        }

        return $this->query($sql);
    }

    public function getOne($sql, $limited = false)
    {
        if ($limited == true) {
            $sql = trim($sql . ' LIMIT 1');
        }

        $res = $this->query($sql);
        if (!empty($res)) {
            return reset($res[0]);
        } else {
            return false;
        }
    }

    public function getAll($sql)
    {
        return $this->query($sql);
    }

    public function getRow($sql, $limited = false)
    {
        if ($limited == true) {
            $sql = trim($sql . ' LIMIT 1');
        }

        $res = $this->query($sql);
        if (!empty($res)) {
            return $res[0];
        } else {
            return false;
        }
    }

    public function getCol($sql)
    {
        $res = $this->query($sql);
        if (!empty($res)) {
            $arr = [];
            foreach ($res as $row) {
                $arr[] = reset($row);
            }

            return $arr;
        }

        return false;
    }

    public function getColCached($sql)
    {
        $cache_id = md5($sql);
        $res = cache($cache_id);
        if ($res === false) {
            $res = $this->getCol($sql);
            cache($cache_id, $res);
        }
        return $res;
    }

    public function autoExecute($table, $field_values, $mode = 'INSERT', $where = '')
    {
        $field_names = $this->getCol('DESC ' . $table);

        $sql = '';
        if ($mode == 'INSERT') {
            $fields = $values = [];
            foreach ($field_names as $value) {
                if (array_key_exists($value, $field_values) == true) {
                    $fields[] = $value;
                    $values[] = "'" . $field_values[$value] . "'";
                }
            }

            if (!empty($fields)) {
                $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
            }
        } else {
            $sets = [];
            foreach ($field_names as $value) {
                if (array_key_exists($value, $field_values) == true) {
                    $sets[] = $value . " = '" . $field_values[$value] . "'";
                }
            }

            if (!empty($sets)) {
                $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE ' . $where;
            }
        }

        if ($sql) {
            return $this->query($sql);
        } else {
            return false;
        }
    }

    public function autoReplace($table, $field_values, $update_values, $where = '')
    {
        $field_descs = $this->getAll('DESC ' . $table);

        $primary_keys = [];
        foreach ($field_descs as $value) {
            $field_names[] = $value['Field'];
            if ($value['Key'] == 'PRI') {
                $primary_keys[] = $value['Field'];
            }
        }

        $fields = $values = [];
        foreach ($field_names as $value) {
            if (array_key_exists($value, $field_values) == true) {
                $fields[] = $value;
                $values[] = "'" . $field_values[$value] . "'";
            }
        }

        $sets = [];
        foreach ($update_values as $key => $value) {
            if (array_key_exists($key, $field_values) == true) {
                if (is_int($value) || is_float($value)) {
                    $sets[] = $key . ' = ' . $key . ' + ' . $value;
                } else {
                    $sets[] = $key . " = '" . $value . "'";
                }
            }
        }

        $sql = '';
        if (empty($primary_keys)) {
            if (!empty($fields)) {
                $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
            }
        } else {
            if (!empty($fields)) {
                $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
                if (!empty($sets)) {
                    $sql .= 'ON DUPLICATE KEY UPDATE ' . implode(', ', $sets);
                }
            }
        }

        if ($sql) {
            return $this->query($sql);
        } else {
            return false;
        }
    }
}
