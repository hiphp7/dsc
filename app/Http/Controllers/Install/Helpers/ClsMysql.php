<?php

namespace App\Http\Controllers\Install\Helpers;

use Exception;
use Illuminate\Support\Facades\Log;

/**
 * MYSQL 公用类库
 * Class cls_mysql
 */
class ClsMysql
{

    /**
     * 数据库链接
     */
    public $dbhost;
    public $dbuser;
    public $dbpw;
    public $dbname;
    public $charset;
    public $pconnect;
    public $quiet;

    public $link_id = null;

    public $settings = array();

    public $queryCount = 0;
    public $queryTime = '';
    public $queryLog = array();

    public $max_cache_time = 300; // 最大的缓存时间，以秒为单位

    public $cache_data_dir = 'temp/query_caches/';
    public $root_path = '';

    public $error_message = array();
    public $platform = '';
    public $version = '';
    public $dbhash = '';
    public $starttime = 0;
    public $timeline = 0;
    public $timezone = 0;

    public $mysql_config_cache_file_time = 0;

    public $mysql_disable_cache_tables = array(); // 不允许被缓存的表，遇到将不会进行缓存=

    public function __construct()
    {
        $dbhost = $this->dbhost ?? '';
        $dbuser = $this->dbuser ?? '';
        $dbpw = $this->dbpw ?? '';
        $dbname = $this->dbname ?? '';
        $charset = $this->charset ?? 'gbk';
        $pconnect = $this->pconnect ?? 0;
        $quiet = $this->quiet ?? 0;

        if (defined('EC_CHARSET')) {
            $charset = strtolower(str_replace('-', '', EC_CHARSET));
        }

        if (!$this->root_path) {
            $this->root_path = storage_public();
        }

        if ($quiet) {
            $this->connect($dbhost, $dbuser, $dbpw, $dbname, $charset, $pconnect, $quiet);
        } else {
            $this->settings = array(
                'dbhost' => $dbhost,
                'dbuser' => $dbuser,
                'dbpw' => $dbpw,
                'dbname' => $dbname,
                'charset' => $charset,
                'pconnect' => $pconnect
            );
        }
    }

    public function connect($dbhost, $dbuser, $dbpw, $dbname = '', $charset = 'utf8', $pconnect = 0, $quiet = 0)
    {
        $hosts = explode(':', $dbhost);
        $db_host = isset($hosts[0]) ? $hosts[0] : 'localhost';
        $db_port = isset($hosts[1]) ? $hosts[1] : 3306;
        if ($pconnect) {
            if (!($this->link_id = new \mysqli('p:' . $db_host, $dbuser, $dbpw, $dbname, $db_port))) {
                if (!$quiet) {
                    $this->ErrorMsg("Can't pConnect MySQL Server($dbhost)!");
                }

                return false;
            }
        } else {
            $this->link_id = new \mysqli($db_host, $dbuser, $dbpw, $dbname, $db_port);

            if (!$this->link_id) {
                if (!$quiet) {
                    $this->ErrorMsg("Can't Connect MySQL Server($dbhost)!");
                }

                return false;
            }
        }

        $this->dbhash = md5($this->root_path . $dbhost . $dbuser . $dbpw . $dbname);
        $this->version = $this->link_id->server_info;

        /* 对字符集进行初始化 */
        $this->link_id->set_charset($charset);

        $this->link_id->query("SET sql_mode=''");

        $sqlcache_config_file = $this->root_path . $this->cache_data_dir . 'sqlcache_config_file_' . $this->dbhash . '.php';

        @include($sqlcache_config_file);

        $this->starttime = time();

        if ($this->max_cache_time && $this->starttime > $this->mysql_config_cache_file_time + $this->max_cache_time) {
            if ($dbhost != '.') {
                $result = $this->link_id->query("SHOW VARIABLES LIKE 'basedir'");
                $row = $result->fetch_array(MYSQLI_ASSOC);
                if (!empty($row['Value']{1}) && $row['Value']{1} == ':' && !empty($row['Value']{2}) && $row['Value']{2} == "\\") {
                    $this->platform = 'WINDOWS';
                } else {
                    $this->platform = 'OTHER';
                }
            } else {
                $this->platform = 'WINDOWS';
            }

            if ($this->platform == 'OTHER' &&
                ($dbhost != '.' && strtolower($dbhost) != 'localhost:3306' && $dbhost != '127.0.0.1:3306') || date_default_timezone_get() == 'UTC') {
                $result = $this->link_id->query("SELECT UNIX_TIMESTAMP() AS timeline, UNIX_TIMESTAMP('" . date('Y-m-d H:i:s', $this->starttime) . "') AS timezone");
                $row = $result->fetch_array(MYSQLI_ASSOC);

                if ($dbhost != '.' && strtolower($dbhost) != 'localhost:3306' && $dbhost != '127.0.0.1:3306') {
                    $this->timeline = $this->starttime - $row['timeline'];
                }

                if (date_default_timezone_get() == 'UTC') {
                    $this->timezone = $this->starttime - $row['timezone'];
                }
            }

            $content = '<' . "?php\r\n" .
                '$this->mysql_config_cache_file_time = ' . $this->starttime . ";\r\n" .
                '$this->timeline = ' . $this->timeline . ";\r\n" .
                '$this->timezone = ' . $this->timezone . ";\r\n" .
                '$this->platform = ' . "'" . $this->platform . "';\r\n?" . '>';

            @file_put_contents($sqlcache_config_file, $content);
        }
    }

    public function set_mysql_charset($charset)
    {
        /* 对字符集进行初始化 */
        if (in_array(strtolower($charset), array('gbk', 'big5', 'utf-8', 'utf8'))) {
            $charset = str_replace('-', '', $charset);
        }
        $this->link_id->set_charset($charset);
    }

    public function fetch_array($query, $result_type = MYSQLI_ASSOC)
    {
        $row = $query->fetch_array($result_type);
        return is_null($row) ? [] : $row;
    }

    public function query($sql, $type = '')
    {
        if ($this->link_id === null) {
            $this->connect($this->settings['dbhost'], $this->settings['dbuser'], $this->settings['dbpw'], $this->settings['dbname'], $this->settings['charset'], $this->settings['pconnect']);
            $this->settings = array();
        }

        if ($this->queryCount++ <= 99) {
            $this->queryLog[] = $sql;
        }
        if ($this->queryTime == '') {
            $this->queryTime = microtime(true);
        }

        /* 当当前的时间大于类初始化时间的时候，自动执行 ping 这个自动重新连接操作 */
        if (time() > $this->starttime + 1) {
            $this->link_id->ping();
        }

        if (!($query = $this->link_id->query($sql)) && $type != 'SILENT') {
            $this->error_message[]['message'] = 'MySQL Query Error';
            $this->error_message[]['sql'] = $sql;
            $this->error_message[]['error'] = $this->link_id->error;
            $this->error_message[]['errno'] = $this->link_id->errno;

            $this->ErrorMsg();

            return false;
        }

        if (defined('DEBUG_MODE') && (DEBUG_MODE & 8) == 8) {
            $logfilename = $this->root_path . DATA_DIR . '/mysql_query_' . $this->dbhash . '_' . date('Y_m_d') . '.log';
            $str = $sql . "\n\n";

            file_put_contents($logfilename, $str, FILE_APPEND);
        }

        return $query;
    }

    public function affected_rows()
    {
        return $this->link_id->affected_rows;
    }

    public function error()
    {
        return $this->link_id->error;
    }

    public function errno()
    {
        return $this->link_id->errno;
    }

    public function result($query, $row)
    {
        $query->data_seek($row);
        $result = $query->fetch_row();
        return is_null($result) ? '' : $result;
    }

    public function num_rows($query)
    {
        return $query->num_rows;
    }

    public function num_fields($query)
    {
        return $query->field_count;
    }

    public function free_result($query)
    {
        return $query->free();
    }

    public function insert_id()
    {
        return $this->link_id->insert_id;
    }

    public function fetchRow($query)
    {
        return $query->fetch_assoc();
    }

    public function fetch_fields($query)
    {
        return $query->fetch_field();
    }

    public function version()
    {
        return $this->version;
    }

    public function ping()
    {
        return $this->link_id->ping();
    }

    public function escape_string($unescaped_string)
    {
        return $this->link_id->real_escape_string($unescaped_string);
    }

    public function close()
    {
        return $this->link_id->close();
    }

    /**
     * @param string $message
     * @param string $sql
     * @throws Exception
     */
    public function ErrorMsg($message = '', $sql = '')
    {
        if ($message) {
            $message = "<b>Dscmall info</b>: $message\n\n<br /><br />";
        } else {
            $message = "<b>MySQL server error report:";
        }

        Log::error($message, $this->error_message);

        throw new Exception($message);
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
        if ($res !== false) {
            $row = $res->fetch_row();
            if (!is_null($row)) {
                return $row[0];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function getOneCached($sql, $cached = 'FILEFIRST')
    {
        $sql = trim($sql . ' LIMIT 1');

        $cachefirst = ($cached == 'FILEFIRST' || ($cached == 'MYSQLFIRST' && $this->platform != 'WINDOWS')) && $this->max_cache_time;
        if (!$cachefirst) {
            return $this->getOne($sql, true);
        } else {
            $result = $this->getSqlCacheData($sql, $cached);
            if (empty($result['storecache']) == true) {
                return $result['data'];
            }
        }

        $arr = $this->getOne($sql, true);

        if ($arr !== false && $cachefirst) {
            $this->setSqlCacheData($result, $arr);
        }

        return $arr;
    }

    public function getAll($sql)
    {
        $res = $this->query($sql);
        if ($res !== false) {
            $arr = [];
            while ($row = $res->fetch_assoc()) {
                $arr[] = $row;
            }
            return $arr;
        } else {
            return false;
        }
    }

    public function getAllCached($sql, $cached = 'FILEFIRST')
    {
        $cachefirst = ($cached == 'FILEFIRST' || ($cached == 'MYSQLFIRST' && $this->platform != 'WINDOWS')) && $this->max_cache_time;
        if (!$cachefirst) {
            return $this->getAll($sql);
        } else {
            $result = $this->getSqlCacheData($sql, $cached);
            if (empty($result['storecache']) == true) {
                return $result['data'];
            }
        }

        $arr = $this->getAll($sql);

        if ($arr !== false && $cachefirst) {
            $this->setSqlCacheData($result, $arr);
        }

        return $arr;
    }

    public function getRow($sql, $limited = false)
    {
        if ($limited == true) {
            $sql = trim($sql . ' LIMIT 1');
        }

        $res = $this->query($sql);
        if ($res !== false) {
            $result = $res->fetch_assoc();
            return is_null($result) ? false : $result;
        } else {
            return false;
        }
    }

    public function getRowCached($sql, $cached = 'FILEFIRST')
    {
        $sql = trim($sql . ' LIMIT 1');

        $cachefirst = ($cached == 'FILEFIRST' || ($cached == 'MYSQLFIRST' && $this->platform != 'WINDOWS')) && $this->max_cache_time;
        if (!$cachefirst) {
            return $this->getRow($sql, true);
        } else {
            $result = $this->getSqlCacheData($sql, $cached);
            if (empty($result['storecache']) == true) {
                return $result['data'];
            }
        }

        $arr = $this->getRow($sql, true);

        if ($arr !== false && $cachefirst) {
            $this->setSqlCacheData($result, $arr);
        }

        return $arr;
    }

    public function getCol($sql)
    {
        $res = $this->query($sql);
        if ($res !== false) {
            $arr = [];
            while ($row = $res->fetch_row()) {
                $arr[] = $row[0];
            }
            return $arr;
        } else {
            return false;
        }
    }

    public function getColCached($sql, $cached = 'FILEFIRST')
    {
        $cachefirst = ($cached == 'FILEFIRST' || ($cached == 'MYSQLFIRST' && $this->platform != 'WINDOWS')) && $this->max_cache_time;
        if (!$cachefirst) {
            return $this->getCol($sql);
        } else {
            $result = $this->getSqlCacheData($sql, $cached);
            if (empty($result['storecache']) == true) {
                return $result['data'];
            }
        }

        $arr = $this->getCol($sql);

        if ($arr !== false && $cachefirst) {
            $this->setSqlCacheData($result, $arr);
        }

        return $arr;
    }

    public function autoExecute($table, $field_values, $mode = 'INSERT', $where = '', $querymode = '')
    {
        $field_names = $this->getCol('DESC ' . $table);

        $sql = '';
        if ($mode == 'INSERT') {
            $fields = $values = array();
            foreach ($field_names as $value) {
                if (array_key_exists($value, $field_values) == true) {
                    $fields[] = $value;
                    $values[] = "'" . $field_values[$value] . "'";
                }
            }

            if (!empty($fields)) {
                foreach ($values as $key => $row) {
                    if ($row) {
                        $values[$key] = "'" . str_replace("'", '', $row) . "'";
                    } else {
                        $values[$key] = $row;
                    }
                }

                $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
            }
        } else {
            $sets = array();
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
            return $this->query($sql, $querymode);
        } else {
            return false;
        }
    }

    public function autoReplace($table, $field_values, $update_values, $where = '', $querymode = '')
    {
        $field_descs = $this->getAll('DESC ' . $table);

        $primary_keys = array();
        foreach ($field_descs as $value) {
            $field_names[] = $value['Field'];
            if ($value['Key'] == 'PRI') {
                $primary_keys[] = $value['Field'];
            }
        }

        $fields = $values = array();
        foreach ($field_names as $value) {
            if (array_key_exists($value, $field_values) == true) {
                $fields[] = $value;
                $values[] = "'" . $field_values[$value] . "'";
            }
        }

        $sets = array();
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
            return $this->query($sql, $querymode);
        } else {
            return false;
        }
    }

    public function setMaxCacheTime($second)
    {
        $this->max_cache_time = $second;
    }

    public function getMaxCacheTime()
    {
        return $this->max_cache_time;
    }

    public function getSqlCacheData($sql, $cached = '')
    {
        $sql = trim($sql);

        $result = array();
        $result['filename'] = $this->root_path . $this->cache_data_dir . 'sqlcache_' . abs(crc32($this->dbhash . $sql)) . '_' . md5($this->dbhash . $sql) . '.php';

        $data = @file_get_contents($result['filename']);

        if (isset($data{23})) {
            $filetime = substr($data, 13, 10);
            $data = substr($data, 23);

            if (($cached == 'FILEFIRST' && time() > $filetime + $this->max_cache_time) || ($cached == 'MYSQLFIRST' && $this->table_lastupdate($this->get_table_name($sql)) > $filetime)) {
                $result['storecache'] = true;
            } else {
                $result['data'] = @unserialize($data);
                if ($result['data'] === false) {
                    $result['storecache'] = true;
                } else {
                    $result['storecache'] = false;
                }
            }
        } else {
            $result['storecache'] = true;
        }

        return $result;
    }

    public function setSqlCacheData($result, $data)
    {
        if ($result['storecache'] === true && $result['filename']) {
            @file_put_contents($result['filename'], '<?php exit;?>' . time() . serialize($data));

            clearstatcache();
        }
    }

    /* 获取 SQL 语句中最后更新的表的时间，有多个表的情况下，返回最新的表的时间 */
    public function table_lastupdate($tables)
    {
        if ($this->link_id === null) {
            $this->connect($this->settings['dbhost'], $this->settings['dbuser'], $this->settings['dbpw'], $this->settings['dbname'], $this->settings['charset'], $this->settings['pconnect']);
            $this->settings = array();
        }

        $lastupdatetime = '0000-00-00 00:00:00';

        $tables = str_replace('`', '', $tables);
        $this->mysql_disable_cache_tables = str_replace('`', '', $this->mysql_disable_cache_tables);

        foreach ($tables as $table) {
            if (in_array($table, $this->mysql_disable_cache_tables) == true) {
                $lastupdatetime = '2037-12-31 23:59:59';

                break;
            }

            if (strstr($table, '.') != null) {
                $tmp = explode('.', $table);
                $sql = 'SHOW TABLE STATUS FROM `' . trim($tmp[0]) . "` LIKE '" . trim($tmp[1]) . "'";
            } else {
                $sql = "SHOW TABLE STATUS LIKE '" . trim($table) . "'";
            }
            $result = $this->link_id->query($sql);

            $row = $result->fetch_array(MYSQLI_ASSOC);
            if ($row['Update_time'] > $lastupdatetime) {
                $lastupdatetime = $row['Update_time'];
            }
        }
        $lastupdatetime = strtotime($lastupdatetime) - $this->timezone + $this->timeline;

        return $lastupdatetime;
    }

    public function get_table_name($query_item)
    {
        $query_item = trim($query_item);
        $table_names = array();

        /* 判断语句中是不是含有 JOIN */
        if (stristr($query_item, ' JOIN ') == '') {
            /* 解析一般的 SELECT FROM 语句 */
            if (preg_match('/^SELECT.*?FROM\s*((?:`?\w+`?\s*\.\s*)?`?\w+`?(?:(?:\s*AS)?\s*`?\w+`?)?(?:\s*,\s*(?:`?\w+`?\s*\.\s*)?`?\w+`?(?:(?:\s*AS)?\s*`?\w+`?)?)*)/is', $query_item, $table_names)) {
                $table_names = preg_replace('/((?:`?\w+`?\s*\.\s*)?`?\w+`?)[^,]*/', '\1', $table_names[1]);

                return preg_split('/\s*,\s*/', $table_names);
            }
        } else {
            /* 对含有 JOIN 的语句进行解析 */
            if (preg_match('/^SELECT.*?FROM\s*((?:`?\w+`?\s*\.\s*)?`?\w+`?)(?:(?:\s*AS)?\s*`?\w+`?)?.*?JOIN.*$/is', $query_item, $table_names)) {
                $other_table_names = array();
                preg_match_all('/JOIN\s*((?:`?\w+`?\s*\.\s*)?`?\w+`?)\s*/i', $query_item, $other_table_names);

                return array_merge(array($table_names[1]), $other_table_names[1]);
            }
        }

        return $table_names;
    }

    /* 设置不允许进行缓存的表 */
    public function set_disable_cache_tables($tables)
    {
        if (!is_array($tables)) {
            $tables = explode(',', $tables);
        }

        foreach ($tables as $table) {
            $this->mysql_disable_cache_tables[] = $table;
        }

        array_unique($this->mysql_disable_cache_tables);
    }
}
