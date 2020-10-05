<?php

namespace App\Http\Controllers\Install\Helpers;

class SqlExecutor
{
    public $dsn;
    public $db_user;
    public $db_pass;
    public $db = false;
    public $db_charset = 'utf8mb4';
    public $source_prefix = 'dsc_';
    public $target_prefix = 'dsc_';
    public $log_path = '';
    public $auto_match = false;
    public $ignored_errors = [];

    public function db()
    {
        $charset = array(\PDO::ATTR_PERSISTENT => true, \PDO::ATTR_ERRMODE => 2, \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $this->db_charset);
        $pdo = new \PDO($this->dsn, $this->db_user, $this->db_pass, $charset);

        $sql = "SHOW TABLES";
        $result = $pdo->query($sql);

        if ($result !== false) {
            $this->db = $pdo;
        }
    }

    /**
     * 执行所有SQL文件中所有的SQL语句
     *
     * @access  public
     * @param array $sql_files 文件绝对路径组成的一维数组
     * @return  boolean     执行成功返回true，失败返回false。
     */
    public function run_all($sql_files)
    {
        /* 如果传入参数不是数组，程序直接返回 */
        if (!is_array($sql_files)) {
            return false;
        }

        foreach ($sql_files as $sql_file) {
            $query_items = $this->parse_sql_file($sql_file);

            /* 如果解析失败，则跳过 */
            if (!$query_items) {
                continue;
            }

            foreach ($query_items as $key => $query_item) {
                /* 如果查询项为空，则跳过 */
                if (!$query_item) {
                    continue;
                }

                $query = $this->query($query_item);
                if ($query['boolean'] == false) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 获得分散的查询项
     *
     * @access  public
     * @param string $file_path 文件的绝对路径
     * @return  mixed       解析成功返回分散的查询项数组，失败返回false。
     */
    public function parse_sql_file($file_path)
    {
        /* 如果SQL文件不存在则返回false */
        if (!file_exists($file_path)) {
            return false;
        }

        /* 记录当前正在运行的SQL文件 */
        $this->current_file = $file_path;

        /* 读取SQL文件 */
        $sql = implode('', file($file_path));

        /* 删除SQL注释，由于执行的是replace操作，所以不需要进行检测。下同。 */
        $sql = $this->remove_comment($sql);

        /* 删除SQL串首尾的空白符 */
        $sql = trim($sql);

        /* 如果SQL文件中没有查询语句则返回false */
        if (!$sql) {
            return false;
        }

        /* 替换表前缀 */
        $sql = $this->replace_prefix($sql);

        /* 解析查询项 */
        $sql = str_replace("\r", '', $sql);
        $query_items = explode(";\n", $sql);

        return $query_items;
    }

    /**
     * 执行某一个查询项
     *
     * @access  public
     * @param string $query_item 查询项
     * @return  boolean     成功返回true，失败返回false。
     */
    public function query($query_item)
    {
        /* 删除查询项首尾的空白符 */
        $query_item = trim($query_item);

        /* 如果查询项为空则返回false */
        if (!$query_item) {
            return false;
        }

        /* 处理建表操作 */
        if (preg_match('/^\s*CREATE\s+TABLE\s*/i', $query_item)) {
            $create = $this->create_table($query_item);
            if ($create['boolean'] == false) {
                return $create;
            }
        } /* 处理ALTER TABLE语句，此时程序将对表的结构进行修改 */
        elseif ($this->auto_match && preg_match('/^\s*ALTER\s+TABLE\s*/i', $query_item)) {
            $alter = $this->alter_table($query_item);
            if ($alter['boolean'] == false) {
                return $alter;
            }
        } /* 处理其它修改操作，如数据添加、更新、删除等 */
        else {
            $doother = $this->do_other($query_item);
            if ($doother['boolean'] == false) {
                return $doother;
            }
        }

        $arr = [
            'boolean' => true
        ];
        return $arr;
    }

    /**
     * 概据MYSQL版本，创建数据表
     *
     * @access  public
     * @param string $query_item SQL查询项
     * @return  boolean     成功返回true，失败返回false。
     */
    public function create_table($query_item)
    {
        /* 获取建表主体串以及表属性声明串，不区分大小写，匹配换行符，且为贪婪匹配 */
        $pattern = '/^\s*(CREATE\s+TABLE[^(]+\(.*\))(.*)$/is';
        if (!preg_match($pattern, $query_item, $matches)) {
            return false;
        }
        $main = $matches[1];
        $postfix = $matches[2];

        /* 从表属性声明串中查找表的类型 */
        $pattern = '/.*(?:ENGINE|TYPE)\s*=\s*([a-z]+).*$/is';
        $type = preg_match($pattern, $postfix, $matches) ? $matches[1] : 'MYISAM';

        /* 从表属性声明串中查找自增语句 */
        $pattern = '/.*(AUTO_INCREMENT\s*=\s*\d+).*$/is';
        $auto_incr = preg_match($pattern, $postfix, $matches) ? $matches[1] : '';

        /* 重新设置表属性声明串 */
        $postfix = " ENGINE=$type DEFAULT CHARACTER SET " . $this->db_charset;
        $postfix .= ' ' . $auto_incr;

        /* 重新构造建表语句 */
        $sql = $main . $postfix;

        /* 开始创建表 */
        if (!$this->db->query($sql)) {
            $error = $this->db->errorInfo();
            $arr = [
                'annotation' => '开始创建表',
                'query_item' => $query_item,
                'code' => $error[0],
                'tag' => $error[1],
                'msg' => $error[2],
                'boolean' => false
            ];

            return $arr;
        }

        $arr = [
            'boolean' => true
        ];
        return $arr;
    }

    /**
     * 修改数据表的方法。算法设计思路：
     * 1. 先进行字段修改操作。CHANGE
     * 2. 然后进行字段移除操作。DROP [COLUMN]
     * 3. 接着进行字段添加操作。ADD [COLUMN]
     * 4. 进行索引移除操作。DROP INDEX
     * 5. 进行索引添加操作。ADD INDEX
     * 6. 最后进行其它操作。
     *
     * @access  public
     * @param string $query_item SQL查询项
     * @return  boolean     修改成功返回true，否则返回false
     */
    public function alter_table($query_item)
    {
        /* 获取表名 */
        $table_name = $this->get_table_name($query_item, 'ALTER');
        if (!$table_name) {
            $arr['boolean'] = false;
            return $arr;
        }

        /* 先把CHANGE操作提取出来执行，再过滤掉它们 */
        $result = $this->parse_change_query($query_item, $table_name);
        if ($result[0] && !$this->db->query($result[0])) {
            $error = $this->db->errorInfo();
            $arr = [
                'annotation' => '先把CHANGE操作提取出来执行，再过滤掉它们',
                'query_item' => $query_item,
                'code' => $error[0],
                'tag' => $error[1],
                'msg' => $error[2],
                'boolean' => false
            ];

            return $arr;
        }
        if (!$result[1]) {
            $arr['boolean'] = true;
            return $arr;
        }

        /* 把DROP [COLUMN]提取出来执行，再过滤掉它们 */
        $result = $this->parse_drop_column_query($result[1], $table_name);
        if ($result[0] && !$this->db->query($result[0])) {
            $error = $this->db->errorInfo();
            $arr = [
                'annotation' => '把DROP [COLUMN]提取出来执行，再过滤掉它们',
                'query_item' => $query_item,
                'code' => $error[0],
                'tag' => $error[1],
                'msg' => $error[2],
                'boolean' => false
            ];

            return $arr;
        }
        if (!$result[1]) {
            $arr['boolean'] = true;
            return $arr;
        }

        /* 把ADD [COLUMN]提取出来执行，再过滤掉它们 */
        $result = $this->parse_add_column_query($result[1], $table_name);
        if ($result[0] && !$this->db->query($result[0])) {
            $error = $this->db->errorInfo();
            $arr = [
                'annotation' => '把ADD [COLUMN]提取出来执行，再过滤掉它们',
                'query_item' => $query_item,
                'code' => $error[0],
                'tag' => $error[1],
                'msg' => $error[2],
                'boolean' => false
            ];

            return $arr;
        }
        if (!$result[1]) {
            $arr['boolean'] = true;
            return $arr;
        }

        /* 把DROP INDEX提取出来执行，再过滤掉它们 */
        $result = $this->parse_drop_index_query($result[1], $table_name);
        if ($result[0] && !$this->db->query($result[0])) {
            $error = $this->db->errorInfo();
            $arr = [
                'annotation' => '把DROP INDEX提取出来执行，再过滤掉它们',
                'query_item' => $query_item,
                'code' => $error[0],
                'tag' => $error[1],
                'msg' => $error[2],
                'boolean' => false
            ];

            return $arr;
        }
        if (!$result[1]) {
            $arr['boolean'] = true;
            return $arr;
        }

        /* 把ADD INDEX提取出来执行，再过滤掉它们 */
        $result = $this->parse_add_index_query($result[1], $table_name);
        if ($result[0] && !$this->db->query($result[0])) {
            $error = $this->db->errorInfo();
            $arr = [
                'annotation' => '把ADD INDEX提取出来执行，再过滤掉它们',
                'query_item' => $query_item,
                'code' => $error[0],
                'tag' => $error[1],
                'msg' => $error[2],
                'boolean' => false
            ];

            return $arr;
        }

        /* 执行其它的修改操作 */
        if ($result[1] && !$this->db->query($result[1])) {
            $error = $this->db->errorInfo();
            $arr = [
                'annotation' => '执行其它的修改操作',
                'query_item' => $query_item,
                'code' => $error[0],
                'tag' => $error[1],
                'msg' => $error[2],
                'boolean' => false
            ];

            return $arr;
        }

        $arr['boolean'] = true;
        return $arr;
    }

    /**
     * 处理其它的数据库操作
     *
     * @access  private
     * @param string $query_item SQL查询项
     * @return  boolean     成功返回true，失败返回false。
     */
    public function do_other($query_item)
    {
        if (!$this->db->query($query_item)) {
            $error = $this->db->errorInfo();
            $arr = [
                'annotation' => '处理其它的数据库操作',
                'query_item' => $query_item,
                'code' => $error[0],
                'tag' => $error[1],
                'msg' => $error[2],
                'boolean' => false
            ];

            return $arr;
        }

        $arr['boolean'] = true;
        return $arr;
    }

    /**
     * 获取表的名字。该方法只对下列查询有效：CREATE TABLE,
     * DROP TABLE, ALTER TABLE, UPDATE, REPLACE INTO, INSERT INTO
     *
     * @access  public
     * @param string $query_item SQL查询项
     * @param string $query_type 查询类型
     * @return  mixed       成功返回表的名字，失败返回false。
     */
    public function get_table_name($query_item, $query_type = '')
    {
        $pattern = '';
        $matches = array();
        $table_name = '';

        /* 如果没指定$query_type，则自动获取 */
        if (!$query_type && preg_match('/^\s*(\w+)/', $query_item, $matches)) {
            $query_type = $matches[1];
        }

        /* 获取相应的正则表达式 */
        $query_type = strtoupper($query_type);
        switch ($query_type) {
            case 'ALTER':
                $pattern = '/^\s*ALTER\s+TABLE\s*`?(\w+)/i';
                break;
            case 'CREATE':
                $pattern = '/^\s*CREATE\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?\s*`?(\w+)/i';
                break;
            case 'DROP':
                $pattern = '/^\s*DROP\s+TABLE(?:\s+IF\s+EXISTS)?\s*`?(\w+)/i';
                break;
            case 'INSERT':
                $pattern = '/^\s*INSERT\s+INTO\s*`?(\w+)/i';
                break;
            case 'REPLACE':
                $pattern = '/^\s*REPLACE\s+INTO\s*`?(\w+)/i';
                break;
            case 'UPDATE':
                $pattern = '/^\s*UPDATE\s*`?(\w+)/i';
                break;
            default:
                return false;
        }

        if (!preg_match($pattern, $query_item, $matches)) {
            return false;
        }
        $table_name = $matches[1];

        return $table_name;
    }

    /**
     * 解析出CHANGE操作
     *
     * @access  public
     * @param string $query_item SQL查询项
     * @param string $table_name 表名
     * @return  array       返回一个以CHANGE操作串和其它操作串组成的数组
     */
    public function parse_change_query($query_item, $table_name = '')
    {
        $result = array('', $query_item);

        if (!$table_name) {
            $table_name = $this->get_table_name($query_item, 'ALTER');
        }

        $matches = array();
        /* 第1个子模式匹配old_col_name，第2个子模式匹配column_definition，第3个子模式匹配new_col_name */
        $pattern = '/\s*CHANGE\s*`?(\w+)`?\s*`?(\w+)`?([^,(]+\([^,]+?(?:,[^,)]+)*\)[^,]+|[^,;]+)\s*,?/i';
        if (preg_match_all($pattern, $query_item, $matches, PREG_SET_ORDER)) {
            $fields = $this->get_fields($table_name);
            $num = count($matches);
            $sql = '';
            for ($i = 0; $i < $num; $i++) {
                /* 如果表中存在原列名 */
                if (in_array($matches[$i][1], $fields)) {
                    $sql .= $matches[$i][0];
                } /* 如果表中存在新列名 */
                elseif (in_array($matches[$i][2], $fields)) {
                    $sql .= 'CHANGE ' . $matches[$i][2] . ' ' . $matches[$i][2] . ' ' . $matches[$i][3] . ',';
                } else /* 如果两个列名都不存在 */ {
                    $sql .= 'ADD ' . $matches[$i][2] . ' ' . $matches[$i][3] . ',';
                    $sql = preg_replace('/(\s+AUTO_INCREMENT)/i', '\1 PRIMARY KEY', $sql);
                }
            }
            $sql = 'ALTER TABLE ' . $table_name . ' ' . $sql;
            $result[0] = preg_replace('/\s*,\s*$/', '', $sql);//存储CHANGE操作，已过滤末尾的逗号
            $result[0] = $this->insert_charset($result[0]);//加入字符集设置
            $result[1] = preg_replace($pattern, '', $query_item);//存储其它操作
            $result[1] = $this->has_other_query($result[1]) ? $result[1] : '';
        }

        return $result;
    }

    /**
     * 解析出DROP COLUMN操作
     *
     * @access  public
     * @param string $query_item SQL查询项
     * @param string $table_name 表名
     * @return  array       返回一个以DROP COLUMN操作和其它操作组成的数组
     */
    public function parse_drop_column_query($query_item, $table_name = '')
    {
        $result = array('', $query_item);

        if (!$table_name) {
            $table_name = $this->get_table_name($query_item, 'ALTER');
        }

        $matches = array();
        /* 子模式存储列名 */
        $pattern = '/\s*DROP(?:\s+COLUMN)?(?!\s+(?:INDEX|PRIMARY))\s*`?(\w+)`?\s*,?/i';
        if (preg_match_all($pattern, $query_item, $matches, PREG_SET_ORDER)) {
            $fields = $this->get_fields($table_name);
            $num = count($matches);
            $sql = '';
            for ($i = 0; $i < $num; $i++) {
                if (in_array($matches[$i][1], $fields)) {
                    $sql .= 'DROP ' . $matches[$i][1] . ',';
                }
            }
            if ($sql) {
                $sql = 'ALTER TABLE ' . $table_name . ' ' . $sql;
                $result[0] = preg_replace('/\s*,\s*$/', '', $sql);//过滤末尾的逗号
            }
            $result[1] = preg_replace($pattern, '', $query_item);//过滤DROP COLUMN操作
            $result[1] = $this->has_other_query($result[1]) ? $result[1] : '';
        }

        return $result;
    }

    /**
     * 解析出ADD [COLUMN]操作
     *
     * @access  public
     * @param string $query_item SQL查询项
     * @param string $table_name 表名
     * @return  array       返回一个以ADD [COLUMN]操作和其它操作组成的数组
     */
    public function parse_add_column_query($query_item, $table_name = '')
    {
        $result = array('', $query_item);

        if (!$table_name) {
            $table_name = $this->get_table_name($query_item, 'ALTER');
        }

        $matches = array();
        /* 第1个子模式存储列定义，第2个子模式存储列名 */
        $pattern = '/\s*ADD(?:\s+COLUMN)?(?!\s+(?:INDEX|UNIQUE|PRIMARY))\s*(`?(\w+)`?(?:[^,(]+\([^,]+?(?:,[^,)]+)*\)[^,]+|[^,;]+))\s*,?/i';
        if (preg_match_all($pattern, $query_item, $matches, PREG_SET_ORDER)) {
            $fields = $this->get_fields($table_name);
            $mysql_ver = $this->db->version();
            $num = count($matches);
            $sql = '';
            for ($i = 0; $i < $num; $i++) {
                if (in_array($matches[$i][2], $fields)) {
                    /* 如果为低版本MYSQL，则把非法关键字过滤掉 */
                    if ($mysql_ver < '4.0.1') {
                        $matches[$i][1] = preg_replace('/\s*(?:AFTER|FIRST)\s*.*$/i', '', $matches[$i][1]);
                    }
                    $sql .= 'CHANGE ' . $matches[$i][2] . ' ' . $matches[$i][1] . ',';
                } else {
                    $sql .= 'ADD ' . $matches[$i][1] . ',';
                }
            }
            $sql = 'ALTER TABLE ' . $table_name . ' ' . $sql;
            $result[0] = preg_replace('/\s*,\s*$/', '', $sql);//过滤末尾的逗号
            $result[0] = $this->insert_charset($result[0]);//加入字符集设置
            $result[1] = preg_replace($pattern, '', $query_item);//过滤ADD COLUMN操作
            $result[1] = $this->has_other_query($result[1]) ? $result[1] : '';
        }

        return $result;
    }

    /**
     * 解析出DROP INDEX操作
     *
     * @access  public
     * @param string $query_item SQL查询项
     * @param string $table_name 表名
     * @return  array       返回一个以DROP INDEX操作和其它操作组成的数组
     */
    public function parse_drop_index_query($query_item, $table_name = '')
    {
        $result = array('', $query_item);

        if (!$table_name) {
            $table_name = $this->get_table_name($query_item, 'ALTER');
        }

        /* 子模式存储键名 */
        $pattern = '/\s*DROP\s+(?:PRIMARY\s+KEY|INDEX\s*`?(\w+)`?)\s*,?/i';
        if (preg_match_all($pattern, $query_item, $matches, PREG_SET_ORDER)) {
            $indexes = $this->get_indexes($table_name);
            $num = count($matches);
            $sql = '';
            for ($i = 0; $i < $num; $i++) {
                /* 如果子模式为空，删除主键 */
                if (empty($matches[$i][1])) {
                    $sql .= 'DROP PRIMARY KEY,';
                } /* 否则删除索引 */
                elseif (in_array($matches[$i][1], $indexes)) {
                    $sql .= 'DROP INDEX ' . $matches[$i][1] . ',';
                }
            }
            if ($sql) {
                $sql = 'ALTER TABLE ' . $table_name . ' ' . $sql;
                $result[0] = preg_replace('/\s*,\s*$/', '', $sql);//存储DROP INDEX操作，已过滤末尾的逗号
            }
            $result[1] = preg_replace($pattern, '', $query_item);//存储其它操作
            $result[1] = $this->has_other_query($result[1]) ? $result[1] : '';
        }

        return $result;
    }

    /**
     * 解析出ADD INDEX操作
     *
     * @access  public
     * @param string $query_item SQL查询项
     * @param string $table_name 表名
     * @return  array       返回一个以ADD INDEX操作和其它操作组成的数组
     */
    public function parse_add_index_query($query_item, $table_name = '')
    {
        $result = array('', $query_item);

        if (!$table_name) {
            $table_name = $this->get_table_name($query_item, 'ALTER');
        }

        /* 第1个子模式存储索引定义，第2个子模式存储"PRIMARY KEY"，第3个子模式存储键名，第4个子模式存储列名 */
        $pattern = '/\s*ADD\s+((?:INDEX|UNIQUE|(PRIMARY\s+KEY))\s*(?:`?(\w+)`?)?\s*\(\s*`?(\w+)`?\s*(?:,[^,)]+)*\))\s*,?/i';
        if (preg_match_all($pattern, $query_item, $matches, PREG_SET_ORDER)) {
            $indexes = $this->get_indexes($table_name);
            $num = count($matches);
            $sql = '';
            for ($i = 0; $i < $num; $i++) {
                $index = !empty($matches[$i][3]) ? $matches[$i][3] : $matches[$i][4];
                if (!empty($matches[$i][2]) && in_array('PRIMARY', $indexes)) {
                    $sql .= 'DROP PRIMARY KEY,';
                } elseif (in_array($index, $indexes)) {
                    $sql .= 'DROP INDEX ' . $index . ',';
                }
                $sql .= 'ADD ' . $matches[$i][1] . ',';
            }
            $sql = 'ALTER TABLE ' . $table_name . ' ' . $sql;
            $result[0] = preg_replace('/\s*,\s*$/', '', $sql);//存储ADD INDEX操作，已过滤末尾的逗号
            $result[1] = preg_replace($pattern, '', $query_item);//存储其它的操作
            $result[1] = $this->has_other_query($result[1]) ? $result[1] : '';
        }

        return $result;
    }

    /**
     * 获取所有的indexes
     *
     * @access  public
     * @param string $table_name 数据表名
     * @return  array
     */
    public function get_indexes($table_name)
    {
        $indexes = array();

        $result = $this->db->query("SHOW INDEX FROM $table_name");

        if ($result) {
            while ($row = $this->db->fetchRow($result)) {
                $indexes[] = $row['Key_name'];
            }
        }

        return $indexes;
    }

    /**
     * 获取所有的fields
     *
     * @access  public
     * @param string $table_name 数据表名
     * @return  array
     */
    public function get_fields($table_name)
    {
        $fields = array();

        $result = $this->db->query("SHOW FIELDS FROM $table_name");

        if ($result) {
            while ($row = $this->db->fetchRow($result)) {
                $fields[] = $row['Field'];
            }
        }

        return $fields;
    }

    /**
     * 判断是否还有其它的查询
     *
     * @access  private
     * @param string $sql_string SQL查询串
     * @return  boolean     有返回true，否则返回false
     */
    public function has_other_query($sql_string)
    {
        return preg_match('/^\s*ALTER\s+TABLE\s*`\w+`\s*\w+/i', $sql_string);
    }

    /**
     * 在查询串中加入字符集设置
     *
     * @access  private
     * @param string $sql_string SQL查询串
     * @return  string     含有字符集设置的SQL查询串
     */
    public function insert_charset($sql_string)
    {
        if ($this->db->version() > '4.1') {
            $sql_string = preg_replace(
                '/(TEXT|CHAR\(.*?\)|VARCHAR\(.*?\))\s+/i',
                '\1 CHARACTER SET ' . $this->db_charset . ' ',
                $sql_string
            );
        }

        return $sql_string;
    }

    /**
     * 过滤SQL查询串中的注释。该方法只过滤SQL文件中独占一行或一块的那些注释。
     *
     * @access  public
     * @param string $sql SQL查询串
     * @return  string      返回已过滤掉注释的SQL查询串。
     */
    public function remove_comment($sql)
    {
        /* 删除SQL行注释，行注释不匹配换行符 */
        $sql = preg_replace('/^\s*(?:--|#).*/m', '', $sql);

        /* 删除SQL块注释，匹配换行符，且为非贪婪匹配 */
        //$sql = preg_replace('/^\s*\/\*(?:.|\n)*\*\//m', '', $sql);
        $sql = preg_replace('/^\s*\/\*.*?\*\//ms', '', $sql);

        return $sql;
    }

    /**
     * 替换查询串中数据表的前缀。该方法只对下列查询有效：CREATE TABLE,
     * DROP TABLE, ALTER TABLE, UPDATE, REPLACE INTO, INSERT INTO
     *
     * @access  public
     * @param string $sql SQL查询串
     * @return  string      返回已替换掉前缀的SQL查询串。
     */
    public function replace_prefix($sql)
    {
        $target_prefix = config('database.connections.mysql.prefix');
        if (empty($target_prefix)) {
            $target_prefix = $this->target_prefix;
        }

        $keywords = 'CREATE\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?|'
            . 'DROP\s+TABLE(?:\s+IF\s+EXISTS)?|'
            . 'ALTER\s+TABLE|'
            . 'UPDATE|'
            . 'REPLACE\s+INTO|'
            . 'DELETE\s+FROM|'
            . 'INSERT\s+INTO';

        $pattern = '/(' . $keywords . ')(\s*)`?' . $this->source_prefix . '(\w+)`?(\s*)/i';
        $replacement = '\1\2`' . $target_prefix . '\3`\4';
        $sql = preg_replace($pattern, $replacement, $sql);

        $pattern = '/(UPDATE.*?WHERE)(\s*)`?' . $this->source_prefix . '(\w+)`?(\s*\.)/i';
        $replacement = '\1\2`' . $target_prefix . '\3`\4';
        $sql = preg_replace($pattern, $replacement, $sql);

        return $sql;
    }

    //打印日志
    private function logResult($word = '')
    {
        $fp = fopen(storage_path('logs/installSqlLog.txt'), "a");
        flock($fp, LOCK_EX);
        fwrite($fp, "执行日期：" . strftime("%Y%m%d%H%M%S", time()) . "\n" . $word . "\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}
