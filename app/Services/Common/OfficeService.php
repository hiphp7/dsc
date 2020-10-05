<?php

namespace App\Services\Common;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/**
 * Class OfficeService
 * @package App\Services\Common
 */
class OfficeService
{
    protected $spreadsheet;

    public function __construct()
    {
        $this->spreadsheet = $this->getInstance();
    }

    /**
     * 返回对象实例
     * @return Spreadsheet
     */
    protected function getInstance()
    {
        return new Spreadsheet();
    }

    /**
     * 设置全局样式
     * @param array $style
     */
    public function setDefaultStyle($style = [])
    {
        // 设置默认文字居左，上下居中
        $styleArray['alignment'] = [
            'horizontal' => Alignment::HORIZONTAL_LEFT,
            'vertical' => Alignment::VERTICAL_CENTER,
        ];
        if (!empty($style)) {
            $styleArray = array_merge($styleArray, $style);
        }
        $this->spreadsheet->getDefaultStyle()->applyFromArray($styleArray);
    }

    /**
     * 导出Excel
     * @param string $title 表名
     * @param array $head 表头，接受一个一维数组
     * @param array $fields $data中对应表头的键的数组，接受一个一维数组
     * @param array $data 要导出excel表的数据，接受一个二维数组
     * @param array $options 配置参数  eg: ['savePath' => 'www/file']
     * @param string $version excel 版本： Excel5 、Excel2007
     * @return bool
     */
    public function outdata($title = '测试表', $head = [], $fields = [], $data = [], $options = [], $version = 'Excel5')
    {
        if (empty($data)) {
            return false;
        }

        // 设置当前的sheet索引 用于后续内容操作
        $this->spreadsheet->setActiveSheetIndex(0);

        // 当前 sheet
        $sheet = $this->spreadsheet->getActiveSheet();

        //设置单元格宽度
        $sheet->getDefaultColumnDimension()->setAutoSize(true);

        // 设置表头标题
        foreach ($head as $k => $r) {
            $sheet->getStyleByColumnAndRow($k + 1, 1)->getFont()->setBold(true);//字体加粗
            $sheet->getStyleByColumnAndRow($k + 1, 1)->getAlignment(); //文字居中
            $head_name = explode('|', $r);
            $sheet->setCellValueByColumnAndRow($k + 1, 1, $head_name['0']);
        }

        //设置表格的列宽  手动
        $headWidthArr = $this->getHeaderWidth($head);
        foreach ($headWidthArr as $k => $v) {
            $val = explode('|', $v);
            $char = $val['0'] ?? '';
            $width = $val['1'] ?? '10'; // 设置宽度
            $auto = $val['2'] ?? 'false'; // 是否自动换行
            $num = $val['3'] ?? '';//是否使用科学计数法  num 使用  空  不使用

            if ($char) {
                $sheet->getColumnDimension($char)->setWidth($width);
                if ($auto == true) {
                    // 单元格内换行
                    $sheet->getStyle($char)->getAlignment()->setWrapText(true);
                }
                // 单元格文字居中
                $sheet->getStyle($char)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_JUSTIFY);
                $sheet->getStyle($char)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                if ($num != 'num') {
                    // 单元格文字 不显示科学计数法
                    $sheet->getStyle($char)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
                }
            }
        }

        //获取列字母,设置第一行表头
        $headCharArr = $this->getHeaderChar($head); //A-Z AA-ZZ ...

        // 循环设置单元格内容
        foreach ($data as $key => $item) {
            $row = intval($key) + 2; // $key+2,因为第一行是表头，所以写到表格时   从第二行开始写
            foreach ($headCharArr as $k => $v) {
                $pCoordinate = $headCharArr[$k] . $row;
                $sheet->setCellValueExplicit($pCoordinate, $item[$fields[$k]], DataType::TYPE_STRING); // 指定单元格内容 为 文本类型
            }
        }

        //设置当前活动的sheet的名称
        $sheet->setTitle($title);

        //设置文件名、判断后缀名
        $suffix = ($version == 'Excel2007') ? '.xlsx' : '.xls';
        $fileName = $title . '_' . date('Y-m-d') . $suffix;

        $writerType = ($version == 'Excel2007') ? 'Xlsx' : 'Xls';

        if (isset($options['savePath']) && !empty($options['savePath'])) {
            // 下载文件到目录
            $file_path = rtrim($options['savePath'], '/') . '/';
            $objWriter = IOFactory::createWriter($this->spreadsheet, $writerType);
            $objWriter->save($file_path . $fileName); //  /path/05featuredemo.xls
        } else {
            // 同步 浏览器输出
            $this->browser_export($fileName, $version);

            $objWriter = IOFactory::createWriter($this->spreadsheet, $writerType);
            $objWriter->save('php://output');
        }

        //删除清空：
        $this->spreadsheet->disconnectWorksheets();
        unset($this->spreadsheet);

        return true;
    }

    /**
     * 导出Excel 支持打印设置、下载文件到目录、支持导出生成图片
     * @param string $title 表名
     * @param array $head 表头，接受一个一维数组
     * @param array $fields $data中对应表头的键的数组，接受一个一维数组
     * @param array $data 要导出excel表的数据，接受一个二维数组
     * @param array $options 配置参数  eg: ['savePath' => 'www/file', 'print' => 1]
     * @param string $version excel 版本： Excel5 、Excel2007
     * @return bool
     */
    public function exportExcel($title = '测试表', $head = [], $fields = [], $data = [], $options = [], $version = 'Excel5')
    {
        if (empty($data)) {
            return false;
        }

        // 设置当前的sheet索引 用于后续内容操作
        $this->spreadsheet->setActiveSheetIndex(0);

        // 当前 sheet
        $sheet = $this->spreadsheet->getActiveSheet();

        // 打印设置
        if (isset($options['print']) && $options['print']) {
            /* 设置打印为A4效果 */
            $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
            /* 设置打印时边距 */
            $pValue = 1 / 2.54;
            $sheet->getPageMargins()->setTop($pValue / 2);
            $sheet->getPageMargins()->setBottom($pValue * 2);
            $sheet->getPageMargins()->setLeft($pValue / 2);
            $sheet->getPageMargins()->setRight($pValue / 2);
        }

        // 设置默认单元格宽度为自动
        $sheet->getDefaultColumnDimension()->setAutoSize(true);
        // 默认文本格式
        $pDataType = DataType::TYPE_STRING;

        // 转换数据 ['0' => ['name' => '表头'] ]   to   ['A' => ['name' => '表头']]
        $formatHeadCharData = $this->formatHeaderChar($head);

        // 设置表头标题 占用第一行
        foreach ($formatHeadCharData as $columnLetter => $val) {
            $pCoordinate = $columnLetter . 1; // A1、B1...
            $pValue = $val['column_name'] ?? '';
            $sheet->setCellValueExplicit($pCoordinate, $pValue, $pDataType);

            // 单元格 setting 配置 val
            $width = $val['width'] ?? ''; // 列宽 默认 自动
            if (!empty($width)) {
                // 自定义单元格宽度
                $sheet->getColumnDimension($columnLetter)->setWidth($width);
            }
            // 样式
            $sheet->getStyle($pCoordinate)->getFont()->setBold(true);//字体加粗
            //$sheet->getStyle($pCoordinate)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); //文字水平居中
        }

        /**
         * 1. 遍历列
         * 2. 遍历行
         */

        // 遍历列
        $k = 0;
        foreach ($formatHeadCharData as $columnLetter => $val) {
            // $columnLetter : A、B

            // 遍历行
            foreach ($data as $key => $item) {
                $row = $key + 2; // 从第二行开始 A2、B2...
                $pCoordinate = $columnLetter . $row;

                $pValue = $item[$fields[$k]] ?? ''; // 值

                if (!empty($pValue)) {
                    // 单元格 setting 配置 val
                    $width = $val['width'] ?? ''; // 列宽 默认 自动
                    $text_align = $val['text_align'] ?? ''; // 文字对齐方式 默认 left, right, center
                    $wrap_text = $val['wrap_text'] ?? 0; // 是否自动换行 0 否 1 是
                    $draw_img = $val['draw_img'] ?? 0; // 是否生成图片 0 否 1 是
                    $num = $val['num'] ?? 0; // 是否使用科学计数法 0 否 1 是

                    if ($wrap_text == 1) {
                        // 单元格内自动换行
                        $sheet->getStyle($pCoordinate)->getAlignment()->setWrapText(true);
                    }

                    if (!empty($text_align)) {
                        if ($text_align == 'center') {
                            // 单元格文字居中(水平、垂直)
                            $sheet->getStyle($pCoordinate)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_JUSTIFY)->setVertical(Alignment::VERTICAL_CENTER);
                        } elseif ($text_align == 'right') {
                            // 右对齐
                            $sheet->getStyle($pCoordinate)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        }
                    }

                    if ($num == 0) {
                        // 单元格文字 不显示科学计数法
                        $sheet->getStyle($pCoordinate)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
                        if (!is_array($pValue)) {
                            $pValue = $pValue . "\t";
                        }
                    }

                    // 是否生成图片
                    if ($draw_img == 1) {
                        $columnWidth = $pValue['width'] ?? '180';
                        $rowHeight = $pValue['height'] ?? '180';
                        $img_name = $pValue['name'] ?? '';
                        $img_desc = $pValue['desc'] ?? '';
                        $sheet->getRowDimension($row)->setRowHeight($rowHeight - 80); // 设置单元格高度
                        $this->drawing($sheet, $pValue['path'], $img_name, $img_desc, $pCoordinate, $columnWidth, $rowHeight);
                    } else {
                        $sheet->setCellValueExplicit($pCoordinate, $pValue, $pDataType);
                    }
                }
            }

            $k++;
        }

        //设置当前活动的sheet的名称
        $sheet->setTitle($title);

        //设置文件名、判断后缀名
        $suffix = ($version == 'Excel2007') ? '.xlsx' : '.xls';
        $fileName = $title . '_' . date('Ymd') . $suffix;

        $writerType = ($version == 'Excel2007') ? 'Xlsx' : 'Xls';

        if (isset($options['savePath']) && !empty($options['savePath'])) {
            // 下载文件到目录
            $file_path = rtrim($options['savePath'], '/') . '/';
            $objWriter = IOFactory::createWriter($this->spreadsheet, $writerType);
            $objWriter->save($file_path . $fileName); //  /path/05featuredemo.xls
        } else {
            // 同步 浏览器输出
            $this->browser_export($fileName, $version);

            $objWriter = IOFactory::createWriter($this->spreadsheet, $writerType);
            $objWriter->save('php://output');
        }

        return true;
    }

    /**
     * 删除清空 释放内存
     */
    public function disconnect()
    {
        // 删除清空 释放内存
        $this->spreadsheet->disconnectWorksheets();
        unset($this->spreadsheet);
    }

    /**
     * 获取excel列数字母
     * @param array $head 头部数据
     * @return array
     */
    private function getHeaderChar($head = [])
    {
        $index = 65; //A标签
        $char = '';
        $charArr = [];
        foreach ($head as $k => $v) {
            $charArr[$k] = $char . chr($index++);
            if ($index == 91) {
                $index = 65;
                $char .= 'A'; // 超出数量AA、AB...
            }
        }
        return $charArr;
    }

    /**
     * 获取excel列数字母 + 表头标题
     * eg: 转换格式 ['goods_id'] => ['A' => 'goods_id']
     * @param array $head 头部数据
     * @return array
     */
    public function formatHeaderChar($head = [])
    {
        $index = 65; // 字母A
        $char = '';
        $charArr = [];

        $new_arr = [];
        foreach ($head as $k => $v) {
            $charArr[$k] = $char . chr($index++);
            if ($index == 91) {
                $index = 65;
                $char .= 'A'; // 超出数量AA、AB...
            }

            // 重组新数组
            $new_arr[$charArr[$k]] = $v;
        }

        return $new_arr; // [A => val]
    }

    /**
     * 获取excel列数字母+ 配置 宽度等
     * @param array $head 头部数据
     * @return array
     */
    private function getHeaderWidth($head = [])
    {
        $index = 65; //A标签
        $char = '';
        $charArr = [];
        foreach ($head as $k => $v) {
            $val = explode('|', $v);
            $w = isset($val[1]) ? '|' . $val[1] : '';
            $a = isset($val[2]) ? '|' . $val[2] : '';
            $n = isset($val[3]) ? '|' . $val[3] : '';
            $charArr[$k] = $char . chr($index++) . $w . $a . $n;  // A|10
            if ($index == 91) {
                $index = 65;
                $char .= 'A'; // 超出数量AA、AB...
            }
        }
        return $charArr;
    }

    /**
     * 输出到浏览器
     * @param string $fileName 文件名 excel.xls
     * @param string $version excel 版本
     */
    private function browser_export($fileName = '', $version = 'Excel5')
    {
        ob_end_clean();//清除缓冲区,避免乱码

        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Transfer-Encoding:utf-8");
        header("Pragma: no-cache");

        if ($version == 'Excel2007') {
            //告诉浏览器输出Excel2007文件
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        } else {
            header('Content-Type: application/vnd.ms-excel;'); //告诉浏览器输出Excel2003文件
        }
        header('Content-Disposition: attachment;filename="' . iconv('utf-8', 'gb2312', $fileName) . '"'); //文件名
        header('Cache-Control: max-age=0'); //禁止浏览器缓存
    }

    /**
     * 导出生成图片至表格
     * @param $sheet
     * @param string $path 图片绝对路径 eg: __DIR__ . '/../images/officelogo.jpg'
     * @param string $name 名称
     * @param string $desc 描述
     * @param string $coordinates 单元格坐标
     * @param int $width 高度
     * @param int $height 高度
     * @param int $offsetX X轴偏移量
     * @param int $offsetY Y轴偏移量
     * @param int $rotation 旋转角度
     *
     */
    public function drawing($sheet, $path = '', $name = '', $desc = '', $coordinates = 'B15', $width = 280, $height = 280, $offsetX = 0, $offsetY = 0, $rotation = 0)
    {
        $drawing = new Drawing();
        $drawing->setName($name);
        $drawing->setDescription($desc);
        $drawing->setPath($path); // image path
        $drawing->setCoordinates($coordinates); // 单元格位置坐标
        $drawing->setOffsetX($offsetX); // X轴偏移量
        $drawing->setOffsetY($offsetY); // Y轴偏移量
        $drawing->setRotation($rotation); // 旋转角度

        if ($width && $height) {
            $drawing->setWidthAndHeight($width, $height); // 设置宽高
        }

        $drawing->getShadow()->setVisible(true);
        $drawing->getShadow()->setDirection(45);
        $drawing->getShadow()->setAlignment('ctr'); // 居中

        $drawing->setWorksheet($sheet);
    }

    /**
     * 导入文件通过上传的文件
     * @param array $format 格式：['名称'=>'name']  会把列名转化成name
     * @param string $inputName 上传输入框的名称name
     * @param string $sheetName 工作表名称，默认：Sheet1
     * @return array  放回指定的数据
     */
    public function importByInput($format = [], $inputName = 'excel', $sheetName = 'Sheet1')
    {
        $name = request()->file($inputName)->getFilename();// 原 $_FILES[$inputName]['tmp_name']
        $data = $this->importExcel($name, $sheetName);
        return $this->dealImportData($format, $data);
    }

    /**
     * 导入指定excel
     * @param array $format 格式：['名称'=>'name']  会把列名转化成name
     * @param string $fileName excel在本机的绝对路径
     * @param string $sheetName 工作表名称，默认：Sheet1
     * @return array
     */
    public function import($format = array(), $fileName = '', $sheetName = 'Sheet1')
    {
        $data = $this->importExcel($fileName, $sheetName);
        return $this->dealImportData($format, $data);
    }

    /**
     * 导入excel,返回原始二维数据
     * @param string $fileName 文件绝对路径
     * @param string $sheetName 工作表名称，默认：Sheet1
     * @return array
     */
    public function importExcel($fileName = '', $sheetName = 'Sheet1')
    {
        header("Content-Type:text/html;charset=utf-8");
        $inputFileType = IOFactory::identify($fileName);//自动获取文件的类型提供给phpexcel用
        $reader = IOFactory::createReader($inputFileType);//获取文件读取操作对象
        // 导入中文乱码 配合上一句 header需设置页面编码为utf-8
        $reader->setInputEncoding('GBK');

        $sheetName = [$sheetName];
        $reader->setLoadSheetsOnly($sheetName);//只加载指定的sheet

        $spreadsheet = $reader->load($fileName);//加载文件

        $key = [];
        $value = [];
        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {//循环取sheet
            foreach ($sheet->getRowIterator() as $row) {//逐行处理
                $temp = [];
                foreach ($row->getCellIterator() as $kk => $cell) {//逐列读取
                    if ($row->getRowIndex() < 2) {
                        $key[$kk] = $cell->getValue();
                    } else {
                        $data = $cell->getValue();//获取单元格数据
                        $temp[$kk] = $data;
                    }
                }
                if (!empty($temp)) {
                    $value[] = $temp;
                }
            }
        }

        $data = [];
        foreach ($value as $k => $v) {
            $temp = [];
            foreach ($v as $k1 => $v1) {
                $temp[$key[$k1]] = $v1;
            }
            $data[] = $temp;
        }
        return $data;
    }

    /**
     * 导出csv
     * @param string $fileName 输出csv文件名
     * @param array $headList 第一行,列名
     * @param array $data 导出数据 支持二维数组
     */
    public function exportCsv($fileName = 'test', $headList = array(), $data = array())
    {
        // 导出excel之前要清空缓存区
        ob_end_clean();

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $fileName . '.csv"');
        header('Cache-Control: max-age=0');

        //打开PHP文件句柄,php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');

        //输出Excel列名信息
        foreach ($headList as $key => $value) {
            //CSV的Excel支持GBK编码，一定要转换，否则乱码
            $headList[$key] = iconv('utf-8', 'gbk', $value);
        }

        //将数据通过fputcsv写到文件句柄
        fputcsv($fp, $headList);

        //计数器
        $num = 0;

        //每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
        $limit = 100000;

        //逐行取出数据，不浪费内存
        $count = count($data);
        for ($i = 0; $i < $count; $i++) {
            $num++;

            //刷新一下输出buffer，防止由于数据过多造成问题
            if ($limit == $num) {
                ob_flush();
                flush();
                $num = 0;
            }

            $row = $data[$i];
            foreach ($row as $key => $value) {
                $row[$key] = iconv('utf-8', 'gbk', $value);
            }

            fputcsv($fp, $row);
        }
    }

    /**
     * 导入Csv
     *
     * @param array $head 格式：['name', 'goods_id']  会把列名转化成name
     * @param string $fileName 文件绝对路径
     * @return array|bool
     */
    public function importCsv($head = array(), $fileName = '')
    {
        if (empty($head)) {
            return false;
        }

        header("Content-Type:text/html;charset=utf-8");
        $inputFileType = IOFactory::identify($fileName);//自动获取文件的类型提供给phpexcel用
        $reader = IOFactory::createReader($inputFileType);
        // 导入中文乱码 配合上一句 header需设置页面编码为utf-8
        $reader->setInputEncoding('GBK');

        $inputFileNames = [$fileName];
        $inputFileName = array_shift($inputFileNames);

        $spreadsheet = $reader->load($inputFileName);

        $spreadsheet->getActiveSheet()->setTitle(pathinfo($inputFileName, PATHINFO_BASENAME));
        foreach ($inputFileNames as $sheet => $inputFileName) {
            $reader->setSheetIndex($sheet + 1);
            $reader->loadIntoExisting($inputFileName, $spreadsheet);
            $spreadsheet->getActiveSheet()->setTitle(pathinfo($inputFileName, PATHINFO_BASENAME));
        }

        $loadedSheetNames = $spreadsheet->getSheetNames();
        $sheetData = [];
        foreach ($loadedSheetNames as $sheetIndex => $loadedSheetName) {
            $spreadsheet->setActiveSheetIndexByName($loadedSheetName);
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        }
        // $sheetData 全部数据

        // 转换格式 eg: ['goods_id'] => ['A' => 'goods_id']
        $format = $this->formatHeaderChar($head);

        /**
         * 返回格式化 所需要的列数据
         * $format = ['A' => 'id']
         */
        return $this->dealImportData($format, $sheetData);
    }

    /**
     * 处理导入数据
     * @param $format
     * @param $data
     * @return array
     */
    private function dealImportData($format, $data)
    {
        if (!$format) {
            return $data;
        } else {
            $newdata = array();
            foreach ($data as $k => $v) {
                $row = array();
                foreach ($v as $k2 => $v2) {
                    //$format[$k2]  获取key
                    if ($format[trim($k2)]) {//去除数据的两端空格
                        $row[$format[trim($k2)]] = trim($v2);
                    }
                }
                $newdata[] = $row;
            }
            return $newdata;
        }
    }
}
