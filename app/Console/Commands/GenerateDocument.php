<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateDocument extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:doc';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Database Document';

    /**
     * 所有文件
     *
     * @var array
     */
    protected $all = [];

    public function handle()
    {
        // $this->struct();

        $this->dict();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    protected function dict()
    {
        $tables = DB::select('show tables');

        $path = base_path('docs/');
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        file_put_contents($path . 'dict.md', "# 数据库字典(" . VERSION . ")\n");

        foreach ($tables as $table) {
            $table = current($table);
            $trueTable = str_replace(db_config('prefix'), '', $table);

            // 表注释
            $sql = "select * from information_schema.tables where table_schema = '" . env('DB_DATABASE') . "' and table_name = '" . $table . "' "; //查询表信息
            $arrTableInfo = DB::select($sql);

            // 各字段信息
            $sql = "select * from information_schema.columns where table_schema ='" . env('DB_DATABASE') . "' and table_name = '" . $table . "' "; //查询字段信息
            $arrColumnInfo = DB::select($sql);

            // 索引信息
            $sql = "show index from {$table}";
            $rs = DB::select($sql);
            if (count($rs) > 0) {
                $arrIndexInfo = $rs;
            } else {
                $arrIndexInfo = [];
            }

            $item = [
                'TABLE' => json_decode(json_encode($arrTableInfo[0]), true),
                'COLUMN' => json_decode(json_encode($arrColumnInfo), true),
                'INDEX' => $this->getIndexInfo($arrIndexInfo)
            ];

            $content = "\n### " . $trueTable;
            $content .= "\n" . $item['TABLE']['TABLE_COMMENT'];

            $content .= "\n| 字段名 | 类型 | 默认值 | 允许非空 | 索引/自增 | 备注(字段数:" . count($item['COLUMN']) . ") |";
            $content .= "\n|-------|:-------:|:-----:|:-------:|:--------:|:------:|";

            foreach ($item['COLUMN'] as $vo) {
                $content .= "\n|" . $vo['COLUMN_NAME'];
                $content .= '|' . $vo['COLUMN_TYPE'];
                $content .= '|' . $vo['COLUMN_DEFAULT'];
                $content .= '|' . $vo['IS_NULLABLE'];
                $content .= '|' . $vo['COLUMN_KEY'] . ' ' . $vo['EXTRA'];
                $content .= '|' . $vo['COLUMN_COMMENT'] . '|';
            }

            if (!empty($item['INDEX'])) {
                $content .= "\n\n##### 索引";
                $content .= "\n\n| 键名 | 字段 |";
                $content .= "\n|-------|--------:|";

                foreach ($item['INDEX'] as $indexName => $indexContent) {
                    $content .= "\n|" . $indexName;
                    $content .= '|' . $indexContent[0] . '|';
                }
            }

            $content .= "\n";

            file_put_contents($path . 'dict.md', $content, FILE_APPEND);
        }
    }

    protected function getIndexInfo($arrIndexInfo)
    {
        if (empty($arrIndexInfo)) {
            return [];
        }

        $arrIndexInfo = json_decode(json_encode($arrIndexInfo), true);

        $index = [];
        foreach ($arrIndexInfo as $v) {
            $unique = ($v['Non_unique'] == 0) ? '(unique)' : '';
            $index[$v['Key_name']][] = $v['Column_name'] . $unique;
        }

        return $index;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    protected function struct()
    {
        $this->generate();

        $names = array_column($this->all, 0);
        array_multisort($names, SORT_ASC, $this->all);

        $content = "# 目录结构\n\n";
        $content .= "| 文件路径 | 目录 | 描述 |\n";
        $content .= "| ------------ | ------------ | ------------ |\n";

        foreach ($this->all as $item) {
            $isDir = is_dir($item[0]) ? '是' : '否';

            $content .= "| " . str_replace(base_path() . '/', '', $item[0]) . " | {$isDir} | - |\n";
        }

        file_put_contents(base_path('docs/struct.md'), $content);
    }

    /**
     * 生成目录树
     *
     * @param null $path
     * @param int $level
     */
    protected function generate($path = null, $level = 0)
    {
        if (is_null($path)) {
            $path = base_path();
        }

        $res = glob($path . '/*');
        foreach ($res as $re) {
            if (in_array(basename($re), ['vendor', 'storage', 'database', 'resources', 'public', 'node_modules'])) {
                continue;
            }

            if (is_dir($re)) {
                $this->generate($re, $level + 1);
            }

            $this->all[] = [$re, $level];
        }
    }
}
