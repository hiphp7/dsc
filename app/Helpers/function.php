<?php

use App\Libraries\Image;
use App\Models\AdminUser;
use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupons;
use App\Models\CouponsRegion;
use App\Models\CouponsUser;
use App\Models\Goods;
use App\Models\GoodsAttr;
use App\Models\GoodsTransport;
use App\Models\GoodsTransportExpress;
use App\Models\GoodsTransportExtend;
use App\Models\GoodsTransportTpl;
use App\Models\MerchantsCategory;
use App\Models\MerchantsCategoryTemporarydate;
use App\Models\MerchantsDocumenttitle;
use App\Models\MerchantsDtFile;
use App\Models\MerchantsRegionArea;
use App\Models\MerchantsRegionInfo;
use App\Models\MerchantsShopBrand;
use App\Models\MerchantsShopBrandfile;
use App\Models\MerchantsShopInformation;
use App\Models\MerchantsStepsFields;
use App\Models\MerchantsStepsFieldsCentent;
use App\Models\MerchantsStepsProcess;
use App\Models\MerchantsStepsTitle;
use App\Models\OrderGoods;
use App\Models\OrderInfo;
use App\Models\PayLog;
use App\Models\Region;
use App\Models\RegionWarehouse;
use App\Models\SellerShopinfo;
use App\Models\Shipping;
use App\Models\UserAddress;
use App\Models\UserBonus;
use App\Models\UsersType;
use App\Models\WarehouseAreaAttr;
use App\Models\WarehouseAreaGoods;
use App\Models\WarehouseFreight;
use App\Models\WarehouseFreightTpl;
use App\Models\WarehouseGoods;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Activity\CouponsService;
use App\Services\Brand\BrandService;
use App\Services\Cart\CartCommonService;
use App\Services\Category\CategoryService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsService;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderCommonService;
use App\Services\Order\OrderTransportService;
use App\Services\User\UserService;
use Illuminate\Support\Str;

/**
 * 处理序列化的支付、配送的配置参数
 * 返回一个以name为索引的数组
 *
 * @access  public
 * @param string $cfg
 * @return  void
 */
function sc_unserialize_config($cfg)
{
    if (is_string($cfg) && ($arr = unserialize($cfg)) !== false) {
        $config = [];

        foreach ($arr as $key => $val) {
            $config[$val['name']] = $val['value'];
        }

        return $config;
    } else {
        return false;
    }
}

/*
 * 删除一条字符串里面的多个字符
 * $strCnt 字符串内容
 * $re_str 删除字符串内容
 */
function get_del_in_val($strCnt, $re_str)
{
    $strCnt = explode(',', $strCnt);
    $re_str = explode(',', $re_str);
    $newstrCnt = $strCnt;

    for ($i = 0; $i < count($re_str); $i++) {
        for ($j = 0; $j < count($strCnt); $j++) {
            if ($re_str[$i] == $strCnt[$j]) {
                unset($newstrCnt[$j]);
            }
        }
    }

    $strCnt = implode(',', $newstrCnt);
    return $strCnt;
}

/*简化sql获取数据
 *$table 表名称
 *$where 查询条件 例子：$where = "goods_id = '$goods_id' and user_id = '$user_id'"
 *$date 传值数组方式
 *$sqlType 获取数据方式 0:取一维数组数据, 1:取二维数组数据 2:取单字段数据集
 */
function get_table_date($table = '', $where = 1, $date = [], $sqlType = 0)
{
    $date = implode(',', $date);

    if (!empty($date)) {
        if ($sqlType != 1) {
            $where .= " LIMIT 1";
        }

        $sql = "SELECT " . $date . " FROM " . $GLOBALS['dsc']->table($table) . " WHERE " . $where;

        if ($sqlType == 1) {
            return $GLOBALS['db']->getAll($sql);
        } elseif ($sqlType == 2) {
            return $GLOBALS['db']->getOne($sql);
        } else {
            return $GLOBALS['db']->getRow($sql);
        }
    }
}

/**
 * 取得最近的上级分类的grade值
 *
 * @access  public
 * @param int $cat_id //当前的cat_id
 *
 * @return int
 */
function get_store_parent_grade($cat_id)
{
    static $res = null;

    if ($res === null) {
        $data = read_static_cache('cat_parent_grade');
        if ($data === false) {
            $res = Category::whereRaw(1);
            $res = app(BaseRepository::class)->getToArrayGet($res);

            write_static_cache('cat_parent_grade', $res);
        } else {
            $res = $data;
        }
    }

    if (!$res) {
        return 0;
    }

    $parent_arr = [];
    $grade_arr = [];

    foreach ($res as $val) {
        $parent_arr[$val['cat_id']] = $val['parent_id'];
        $grade_arr[$val['cat_id']] = $val['grade'];
    }

    if (isset($parent_arr[$cat_id]) && $parent_arr[$cat_id]) {
        while ($parent_arr[$cat_id] > 0 && $grade_arr[$cat_id] == 0) {
            $cat_id = $parent_arr[$cat_id];
        }
    }

    return $grade_arr[$cat_id] ?? 0;
}

//数据打印
function get_print_r($arr)
{
    echo "<pre>";
    print_r($arr);
    echo "</pre>";
}

/**
 * 计算运费
 * @param string $shipping_code 配送方式代码
 * @param mix $shipping_config 配送方式配置信息
 * @param float $goods_weight 商品重量
 * @param float $goods_amount 商品金额
 * @param float $goods_number 商品数量
 * @return  float   运费
 */
function goods_shipping_fee($shipping_code, $shipping_config, $goods_weight, $goods_amount, $goods_number = '')
{
    if (!is_array($shipping_config)) {
        $shipping_config = unserialize($shipping_config);
    }

    $shipping_code = Str::studly($shipping_code);
    $shipping_code = '\\App\\Plugins\\Shipping\\' . $shipping_code . '\\' . $shipping_code;

    if (class_exists($shipping_code)) {
        if (!is_array($shipping_config)) {
            if (!$shipping_config) {
                $shipping_config = [];
            } else {
                $shipping_config = app(BaseRepository::class)->getExplode($shipping_config);
            }
        }

        $obj = app($shipping_code, $shipping_config);

        return $obj->calculate($goods_weight, $goods_amount, $goods_number);
    } else {
        return 0;
    }
}

/**
 * 获得指定国家的所有省份
 *
 * @access      public
 * @param int     country    国家的编号
 * @return      array
 */
function get_regions_steps($type = 0, $parent = 0)
{
    $res = Region::where('region_type', $type)->where('parent_id', $parent);
    $res = app(BaseRepository::class)->getToArrayGet($res);

    return $res;
}

//后台程序代码-------------------------------------

//添加或删除字段函数

/**
 * 新增、修改表字段
 */
function get_Add_Drop_fields($date, $newDate = '', $table = '', $type = 'insert', $dateType = 'VARCHAR', $length = '', $IntType = 'NOT NULL', $comment = '')
{
    $date = trim($date);
    $comment = trim($comment);
    $codingType = '';
    $coding = '';

    if (empty($newDate)) { //修改字段名称
        $newDate = $date;
    }

    //修改字段类型
    if ($dateType == 'VARCHAR') { //长字符串
        $length = empty($length) ? 255 : $length;

        $dateType = "VARCHAR( " . $length . " )";
    } elseif ($dateType == 'CHAR') { //短字符串
        $length = empty($length) ? 60 : $length;

        $dateType = "CHAR( " . $length . " )";
    } elseif ($dateType == 'INT') { //数据类型
        $length = empty($length) ? 11 : $length;

        $dateType = "INT( " . $length . " ) UNSIGNED";
    } elseif ($dateType == 'MEDIUMINT') { //数据类型
        $length = empty($length) ? 11 : $length;

        $dateType = "MEDIUMINT( " . $length . " ) UNSIGNED";
    } elseif ($dateType == 'SMALLINT') { //数据类型
        $length = empty($length) ? 11 : $length;

        $dateType = "SMALLINT( " . $length . " ) UNSIGNED";
    } elseif ($dateType == 'TINYINT') { //数据类型
        $length = empty($length) ? 1 : $length;

        $dateType = "TINYINT( " . $length . " ) UNSIGNED";
    } elseif ($dateType == 'TEXT') { //文本类型
        $length = '';
        $dateType = "TEXT";
    } elseif ($dateType == 'DECIMAL') { //保留几位数类型
        $length = empty($length) ? '10,2' : $length;

        $dateType = "DECIMAL( " . $length . " )";
    }

    //修改字段是否为空
    if ($IntType != 'NOT NULL') {
        $IntType = 'NULL';
    }

    if (!empty($comment)) {
        $comment = " COMMENT '" . $comment . "'";
    }

    if (!empty($table) && !empty($date)) {

        //字段操作 start
        if ($type == 'insert') {
            $sql = "ALTER TABLE " . $GLOBALS['dsc']->table($table) . " ADD `" . $date . "` " . $dateType . " " . $IntType . $comment;
        } elseif ($type == 'update') {
            $sql = "ALTER TABLE " . $GLOBALS['dsc']->table($table) . " CHANGE `" . $date . "` `" . $newDate . "` " . $dateType . " " . $codingType . " " . $IntType . " " . $comment;
        } elseif ($type == 'delete') {
            $sql = "ALTER TABLE " . $GLOBALS['dsc']->table($table) . " DROP `" . $date . "`";
        }
        //字段操作 end

        $res = $GLOBALS['db']->query($sql);

        if ($res == 1) {
            return 1;
        } else {
            return 3;
        }
    } else {
        return 2;
    }
}

/* 查询表字段是否存在 */
function get_table_file_name($table = '', $name = '')
{
    if ($table != '' && $name != '') {
        $field = $GLOBALS['db']->query("Describe $table $name");

        if ($field) {
            $bool = 1;
        } else {
            $bool = 0;
        }

        return ['field' => $field, 'bool' => $bool];
    } else {
        echo "表名称或表字段名称不能为空";
    }
}

/* 查询表字段的索引是否存在 */
function get_table_column_name($table = '', $name = '')
{
    if ($table != '' && $name != '') {
        $sql = "SHOW index FROM " . $table . " WHERE column_name LIKE '" . $name . "';";
        $field = $GLOBALS['db']->query($sql);
        $field = $GLOBALS['db']->fetch_array($field);

        if ($field) {
            $bool = 1;
        } else {
            $bool = 0;
        }

        return ['field' => $field, 'bool' => $bool];
    } else {
        echo "表名称或表字段名称不能为空";
    }
}

/**
 * 循环表字段增加修改
 */
function get_array_fields($date, $newDate, $table, $type, $dateType, $length)
{
    for ($i = 0; $i < count($date); $i++) {
        get_Add_Drop_fields($date[$i], $newDate[$i], $table, $type, $dateType[$i], $length[$i]);
    }
}

/******************文章函数 start************************/

//查找商家入驻文章列表
function get_merchants_article_menu($cat_id)
{
    $res = Article::where('cat_id', $cat_id)
        ->orderBy('article_id', 'desc');
    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    if ($res) {
        foreach ($res as $key => $row) {
            $arr[$key]['article_id'] = $row['article_id'];
            $arr[$key]['article_type'] = $row['article_type'];
            $arr[$key]['title'] = $row['title'];
            if ($row['open_type'] != 1) {
                $arr[$key]['url'] = app(DscRepository::class)->buildUri('merchants', ['mid' => $row['article_id']], $row['title']);
            } else {
                $arr[$key]['url'] = $row['file_url'];
            }
        }
    }

    return $arr;
}

//查找商家入驻文章内容
function get_merchants_article_info($article_id = 0)
{
    $res = Article::where('article_id', $article_id)
        ->orderBy('article_id', 'desc');
    $res = app(BaseRepository::class)->getToArrayFirst($res);

    return $res;
}

/******************文章函数 end************************/

/******************入驻流程函数 start************************/

function get_merchants_steps_fields_admin($table, $date, $dateType, $length, $notnull, $coding, $formName, $fields_sort, $tid)
{
    $arr = [
        'textFields' => '',
        'fieldsDateType' => '',
        'fieldsLength' => '',
        'fieldsNotnull' => '',
        'fieldsFormName' => '',
        'fieldsCoding' => '',
        'fields_sort' => '',
        'will_choose' => '',
    ];
    for ($i = 0; $i < count($date); $i++) {
        if (!empty($date[$i])) {
            $arr[$i]['date'] = $date[$i];
            $arr[$i]['dateType'] = $dateType[$i];
            $arr[$i]['length'] = $length[$i];
            $arr[$i]['notnull'] = $notnull[$i];
            $arr[$i]['formName'] = $formName[$i];
            $arr[$i]['coding'] = $coding[$i];
            $arr[$i]['fields_sort'] = $fields_sort[$i];

            $arr['textFields'] .= $date[$i] . ',';
            $arr['fieldsDateType'] .= $dateType[$i] . ',';
            $arr['fieldsLength'] .= $length[$i] . ',';
            $arr['fieldsNotnull'] .= $notnull[$i] . ',';
            $arr['fieldsFormName'] .= $formName[$i] . ',';
            $arr['fieldsCoding'] .= $coding[$i] . ',';
            $arr['fields_sort'] .= $fields_sort[$i] . ',';

            $_POST['will_choose_' . $i] = $_POST['will_choose_' . $i] ?? '';
            $arr['will_choose'] .= $_POST['will_choose_' . $i] . ',';

            if ($dateType[$i] == 'INT' || $dateType[$i] == 'TINYINT' || $dateType[$i] == 'DECIMAL' || $dateType[$i] == 'MEDIUMINT' || $dateType[$i] == 'SMALLINT') {
                $arr[$i]['coding'] = '';
            }

            //判断数据库表的字段是否存在
            $test = $GLOBALS['db']->query('Describe ' . $GLOBALS['dsc']->table($table) . $date[$i]);
            // $test = $GLOBALS['db']->fetch_array($test);

            $newDate = ''; //修改表名称
            if ($test && is_array($test)) { //表字段存在
                $type = 'update';
            } else { //表字段不存在
                $type = 'insert';
            }

            $failure = get_Add_Drop_fields($arr[$i]['date'], $newDate, $table, $type, $arr[$i]['dateType'], $arr[$i]['length'], $arr[$i]['notnull'], $arr[$i]['formName']);

            if ($failure == 2) {
                $pid = MerchantsStepsTitle::where('tid', $tid)->value('fields_steps');

                $link[] = ['text' => '返回一页', 'href' => 'merchants_steps.php?act=title_list&id=' . $pid];
                return sys_msg('表名称为空', 0, $link);
                break;
            }
        }
    }

    $arr['textFields'] = substr($arr['textFields'], 0, -1);
    $arr['fieldsDateType'] = substr($arr['fieldsDateType'], 0, -1);
    $arr['fieldsLength'] = substr($arr['fieldsLength'], 0, -1);
    $arr['fieldsNotnull'] = substr($arr['fieldsNotnull'], 0, -1);
    $arr['fieldsFormName'] = substr($arr['fieldsFormName'], 0, -1);
    $arr['fieldsCoding'] = substr($arr['fieldsCoding'], 0, -1);
    $arr['fields_sort'] = substr($arr['fields_sort'], 0, -1);
    $arr['will_choose'] = substr($arr['will_choose'], 0, -1);

    return $arr;
}

//选择表单类型
function get_steps_form_choose($form_array = [])
{
    $form = $form_array['form'];

    $arr = [
        'chooseForm' => ''
    ];
    for ($i = 0; $i < count($form); $i++) {
        $form[$i] = $form[$i] ?? '';
        $form_array['formName_special'][$i] = $form_array['formName_special'][$i] ?? '';

        if (!empty($form_array['formName_special'][$i])) {
            $formName_special = '+' . $form_array['formName_special'][$i];
        } else {
            $formName_special = '+' . ' ';
        }

        if ($form[$i] == 'input') {
            $form_array['formSize'][$i] = $form_array['formSize'][$i] ?? '';

            $arr[$i]['form'] = $form[$i] . ':' . $form_array['formSize'][$i] . $formName_special;
        } elseif ($form[$i] == 'textarea') {
            $form_array['rows'][$i] = $form_array['rows'][$i] ?? '';
            $form_array['cols'][$i] = $form_array['cols'][$i] ?? '';

            $arr[$i]['form'] = $form[$i] . ':' . $form_array['rows'][$i] . ',' . $form_array['cols'][$i] . $formName_special;
        } elseif ($form[$i] == 'radio') {
            $_POST['radio_checkbox_' . $i] = $_POST['radio_checkbox_' . $i] ?? '';
            $_POST['rc_sort_' . $i] = $_POST['rc_sort_' . $i] ?? '';

            $formType_arr = get_formType_arr($_POST['radio_checkbox_' . $i], $_POST['rc_sort_' . $i]);
            $radio_checkbox = $formType_arr ? implode(',', $formType_arr) : '';

            $arr[$i]['form'] = $form[$i] . ':' . $radio_checkbox . $formName_special;
        } elseif ($form[$i] == 'checkbox') {
            $_POST['radio_checkbox_' . $i] = $_POST['radio_checkbox_' . $i] ?? '';
            $_POST['rc_sort_' . $i] = $_POST['rc_sort_' . $i] ?? '';

            $formType_arr = get_formType_arr($_POST['radio_checkbox_' . $i], $_POST['rc_sort_' . $i]);
            $radio_checkbox = $formType_arr ? implode(',', $formType_arr) : '';

            $arr[$i]['form'] = $form[$i] . ':' . $radio_checkbox . $formName_special;
        } elseif ($form[$i] == 'select') {
            $_POST['select_' . $i] = $_POST['select_' . $i] ?? '';
            $select = implode(',', get_formType_arr($_POST['select_' . $i], '', 1));

            $arr[$i]['form'] = $form[$i] . ':' . implode(',', get_formType_arr($_POST['select_' . $i], '', 1)) . $formName_special;
        } elseif ($form[$i] == 'other') {
            $form_array['formOtherSize'][$i] = $form_array['formOtherSize'][$i] ?? '';

            if ($form_array['formOther'][$i] == 'dateTime') {
                $dateTimeText = ',' . $form_array['formOtherSize'][$i];
            } else {
                $dateTimeText = '';
            }

            $form_array['formOther'][$i] = $form_array['formOther'][$i] ?? '';
            $arr[$i]['form'] = $form[$i] . ':' . $form_array['formOther'][$i] . $dateTimeText . $formName_special;
        }

        $form_array['date'][$i] = $form_array['date'][$i] ?? '';
        $arr[$i]['form'] = $arr[$i]['form'] ?? '';

        if (!empty($form_array['date'][$i])) {
            $arr['chooseForm'] .= $arr[$i]['form'] . '|';
        }
    }

    $arr['chooseForm'] = substr($arr['chooseForm'], 0, -1);

    return $arr;
}

function get_formType_arr($formType, $rc_sort, $type = 0)
{
    $arr = [];

    if ($formType) {
        for ($i = 0; $i < count($formType); $i++) {
            if (!empty($formType[$i])) {
                if (isset($rc_sort[$i]) && !empty($rc_sort[$i]) && $type == 0) {
                    $arr[$i] = trim($formType[$i]) . '*' . trim($rc_sort[$i]);
                } else {
                    $arr[$i] = trim($formType[$i]);
                }
            }
        }
    }

    return $arr;
}

function get_merchants_steps_fields_centent_insert_update($textFields, $fieldsDateType, $fieldsLength, $fieldsNotnull, $fieldsFormName, $fieldsCoding, $fields_sort, $will_choose, $chooseForm, $tid)
{
    $parent = [
        'tid' => $tid,
        'textFields' => $textFields,
        'fieldsDateType' => $fieldsDateType,
        'fieldsLength' => $fieldsLength,
        'fieldsNotnull' => $fieldsNotnull,
        'fieldsFormName' => $fieldsFormName,
        'fieldsCoding' => $fieldsCoding,
        'fields_sort' => $fields_sort,
        'will_choose' => $will_choose,
        'fieldsForm' => $chooseForm
    ];

    $count = MerchantsStepsFieldsCentent::where('tid', $tid)->count();

    if ($count > 0) {
        MerchantsStepsFieldsCentent::where('tid', $tid)->update($parent);
    } else {
        MerchantsStepsFieldsCentent::insert($parent);
    }

    return true;
}

//添加或更新流程信息
function get_merchants_steps_title_insert_update($fields_steps, $fields_titles, $titles_annotation, $steps_style, $fields_special, $special_type, $handler_type = 'insert', $tid = 0)
{
    $res = MerchantsStepsTitle::where('fields_titles', $fields_titles);
    if ($handler_type == 'update') {
        $res = $res->where('tid', '<>', $tid);
    }

    $count = $res->count();

    if ($count > 0) {
        return false;
    } else {
        $parent = [
            'fields_steps' => $fields_steps,
            'fields_titles' => $fields_titles,
            'titles_annotation' => $titles_annotation,
            'steps_style' => $steps_style,
            'fields_special' => $fields_special,
            'special_type' => $special_type
        ];

        if ($handler_type == 'update') {
            MerchantsStepsTitle::where('tid', $tid)->update($parent);
            return true;
        } else {
            $tid = MerchantsStepsTitle::insertGetId($parent);

            $row = [
                'tid' => $tid,
                'true' => true
            ];

            return $row;
        }
    }
}

//字段循环生成数组
function get_fields_centent_info($id, $textFields, $fieldsDateType, $fieldsLength, $fieldsNotnull, $fieldsFormName, $fieldsCoding, $fieldsForm, $fields_sort, $will_choose, $webType = 'admin', $user_id = 0)
{
    if (!empty($textFields)) {

        $textFields = explode(',', $textFields);
        $textFields = array_filter($textFields);
        $fieldsDateType = explode(',', $fieldsDateType);
        $fieldsLength = explode(',', $fieldsLength);
        $fieldsNotnull = explode(',', $fieldsNotnull);
        $fieldsFormName = explode(',', $fieldsFormName);
        $fieldsCoding = explode(',', $fieldsCoding);
        $choose = explode('|', $fieldsForm);
        $fields_sort = explode(',', $fields_sort);
        $will_choose = explode(',', $will_choose);

        $arr = [];
        if ($textFields) {
            for ($i = 0; $i < count($textFields); $i++) {
                $arr[$i + 1]['id'] = $id;
                $arr[$i + 1]['textFields'] = $textFields[$i];
                $arr[$i + 1]['fieldsDateType'] = $fieldsDateType[$i] ?? 'VARCHAR';
                $arr[$i + 1]['fieldsLength'] = $fieldsLength[$i] ?? 255;
                $arr[$i + 1]['fieldsNotnull'] = $fieldsNotnull[$i] ?? '';
                $arr[$i + 1]['fieldsFormName'] = $fieldsFormName[$i] ?? '';
                $arr[$i + 1]['fieldsCoding'] = $fieldsCoding[$i] ?? 'UTF8';
                $arr[$i + 1]['fields_sort'] = $fields_sort[$i] ?? 0;
                $arr[$i + 1]['will_choose'] = isset($will_choose[$i]) ? $will_choose[$i] : '';
                $arr[$i + 1]['titles_centents'] = '';

                if ($user_id > 0) {
                    $arr[$i + 1]['titles_centents'] = MerchantsStepsFields::where('user_id', $user_id)->value($textFields[$i]);

                    if ($arr[$i + 1]['textFields'] === 'contactPhone' || $arr[$i + 1]['textFields'] === 'contactEmail') {
                        if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                            $arr[$i + 1]['titles_centents'] = app(DscRepository::class)->stringToStar($arr[$i + 1]['titles_centents']);
                        }
                    }

                    // 处理图片路径
                    if (strpos($textFields[$i], 'fileImg') !== false) {
                        $arr[$i + 1]['titles_centents'] = get_image_path($arr[$i + 1]['titles_centents']);
                    }
                    if ($textFields[$i] == 'business_term') {
                        $arr[$i + 1]['shopTime_term'] = MerchantsStepsFields::where('user_id', $user_id)->value('shopTime_term');
                    }
                }

                $chooseForm = explode(':', $choose[$i]);
                $arr[$i + 1]['chooseForm'] = $chooseForm[0];
                $form_special = explode('+', $chooseForm[1]);
                $arr[$i + 1]['formSpecial'] = $form_special[1] ?? ''; //表单注释


                if ($chooseForm[0] == 'input') {
                    $arr[$i + 1]['inputForm'] = $form_special[0];
                } elseif ($chooseForm[0] == 'textarea') {
                    $textareaForm = explode(',', $form_special[0]);
                    $arr[$i + 1]['rows'] = $textareaForm[0];
                    $arr[$i + 1]['cols'] = $textareaForm[1];
                } elseif ($chooseForm[0] == 'radio' || $chooseForm[0] == 'checkbox') {
                    if (!empty($form_special[0])) {
                        $radioCheckbox_sort = get_radioCheckbox_sort(explode(',', $form_special[0]));

                        if ($webType == 'root') {
                            $radioCheckbox_sort = get_array_sort($radioCheckbox_sort, 'rc_sort');
                        }

                        $arr[$i + 1]['radioCheckboxForm'] = $radioCheckbox_sort;
                    } else {
                        $arr[$i + 1]['radioCheckboxForm'] = [];
                    }
                } elseif ($chooseForm[0] == 'select') {
                    if (!empty($form_special[0])) {
                        $arr[$i + 1]['selectList'] = explode(',', $form_special[0]);
                    } else {
                        $arr[$i + 1]['selectList'] = [];
                    }
                } elseif ($chooseForm[0] == 'other') {
                    $otherForm = explode(',', $form_special[0]);
                    $arr[$i + 1]['otherForm'] = $otherForm[0];
                    if ($otherForm[0] == 'dateTime') { //日期
                        if ($webType == 'root') {
                            $arr[$i + 1]['dateTimeForm'] = get_dateTimeForm_arr(explode('--', $otherForm[1]), explode(',', $arr[$i + 1]['titles_centents']));
                        } else {
                            $arr[$i + 1]['dateTimeForm'] = $otherForm[1];
                        }
                    } elseif ($otherForm[0] == 'textArea') { //地区
                        if ($webType == 'root') {
                            if (!empty($arr[$i + 1]['titles_centents'])) {
                                $arr[$i + 1]['textAreaForm'] = get_textAreaForm_arr(explode(',', $arr[$i + 1]['titles_centents']));
                            } else {
                                $arr[$i + 1]['textAreaForm'] = [
                                    'country' => '',
                                    'province' => '',
                                    'city' => ''
                                ];
                            }

                            $arr[$i + 1]['province_list'] = get_regions_steps(1, $arr[$i + 1]['textAreaForm']['country']);
                            $arr[$i + 1]['city_list'] = get_regions_steps(2, $arr[$i + 1]['textAreaForm']['province']);
                            $arr[$i + 1]['district_list'] = get_regions_steps(3, $arr[$i + 1]['textAreaForm']['city']);
                        }
                    }
                }
            }
        }

        return $arr;
    } else {
        return [];
    }
}

//单选或多选表单数据
function get_radioCheckbox_sort($radioCheckbox_sort)
{
    $arr = [];
    for ($i = 0; $i < count($radioCheckbox_sort); $i++) {
        $rc_sort = explode('*', $radioCheckbox_sort[$i]);
        $arr[$i]['radioCheckbox'] = $rc_sort[0];
        $arr[$i]['rc_sort'] = $rc_sort[1];
    }

    return $arr;
}

//日期表单数据
function get_dateTimeForm_arr($dateTime, $date_centent)
{
    $dateTime[0] = $dateTime[0] ?? [];
    $dateTime[1] = $dateTime[1] ?? '';
    $arr = [];

    if ($dateTime[0]) {
        for ($i = 0; $i < $dateTime[0]; $i++) {
            $arr[$i]['dateSize'] = $dateTime[1];
            $arr[$i]['dateCentent'] = $date_centent[$i] ?? '';
        }
    }

    return $arr;
}

//地区表单数据
function get_textAreaForm_arr($textArea)
{
    $arr['country'] = $textArea[0] ?? 0;
    $arr['province'] = $textArea[1] ?? 0;
    $arr['city'] = $textArea[2] ?? 0;
    $arr['district'] = $textArea[3] ?? 0;

    return $arr;
}

//查找字段数据 start
function get_fields_date_title_remove($tid, $objName, $type = 0)
{
    $row = MerchantsStepsFieldsCentent::where('tid', $tid);
    $row = app(BaseRepository::class)->getToArrayFirst($row);

    $arr = [];
    if ($row) {
        $textFields = explode(',', $row['textFields']);
        $fieldsDateType = explode(',', $row['fieldsDateType']);
        $fieldsLength = explode(',', $row['fieldsLength']);
        $fieldsNotnull = explode(',', $row['fieldsNotnull']);
        $fieldsFormName = explode(',', $row['fieldsFormName']);
        $fieldsCoding = explode(',', $row['fieldsCoding']);
        $fieldsForm = explode('|', $row['fieldsForm']);

        for ($i = 0; $i < count($textFields); $i++) {
            if ($type == 1) {
                if ($textFields[$i] != $objName) {
                    $arr[$i]['textFields'] = $textFields[$i];
                    $arr[$i]['fieldsDateType'] = $fieldsDateType[$i];
                    $arr[$i]['fieldsLength'] = $fieldsLength[$i];
                    $arr[$i]['fieldsNotnull'] = $fieldsNotnull[$i];
                    $arr[$i]['fieldsFormName'] = $fieldsFormName[$i];
                    $arr[$i]['fieldsCoding'] = $fieldsCoding[$i];
                    $arr[$i]['fieldsForm'] = $fieldsForm[$i];
                }
            } else {
                $arr[$i]['textFields'] = $textFields[$i];
            }
        }
    }

    return $arr;
}

function get_title_remove($tid, $fields, $objName)
{ //$objName 删除字段
    $fields = array_values($fields);
    for ($i = 0; $i < count($fields); $i++) {
        $arr[$i] = $fields[$i];
        $arr['textFields'] .= $fields[$i]['textFields'] . ',';
        $arr['fieldsDateType'] .= $fields[$i]['fieldsDateType'] . ',';
        $arr['fieldsLength'] .= $fields[$i]['fieldsLength'] . ',';
        $arr['fieldsNotnull'] .= $fields[$i]['fieldsNotnull'] . ',';
        $arr['fieldsFormName'] .= $fields[$i]['fieldsFormName'] . ',';
        $arr['fieldsCoding'] .= $fields[$i]['fieldsCoding'] . ',';
        $arr['fieldsForm'] .= $fields[$i]['fieldsForm'] . '|';
    }

    $arr['textFields'] = substr($arr['textFields'], 0, -1);
    $arr['fieldsDateType'] = substr($arr['fieldsDateType'], 0, -1);
    $arr['fieldsLength'] = substr($arr['fieldsLength'], 0, -1);
    $arr['fieldsNotnull'] = substr($arr['fieldsNotnull'], 0, -1);
    $arr['fieldsFormName'] = substr($arr['fieldsFormName'], 0, -1);
    $arr['fieldsCoding'] = substr($arr['fieldsCoding'], 0, -1);
    $arr['fieldsForm'] = substr($arr['fieldsForm'], 0, -1);

    $parent = [
        'textFields' => $arr['textFields'],
        'fieldsDateType' => $arr['fieldsDateType'],
        'fieldsLength' => $arr['fieldsLength'],
        'fieldsNotnull' => $arr['fieldsNotnull'],
        'fieldsFormName' => $arr['fieldsFormName'],
        'fieldsCoding' => $arr['fieldsCoding'],
        'fieldsForm' => $arr['fieldsForm'],
    ];

    MerchantsStepsFieldsCentent::where('tid', $tid)->update($parent);

    get_Add_Drop_fields($objName, '', 'merchants_steps_fields', 'delete');

    return $arr;
}

//查找字段数据 end

/* * ****************入驻流程函数 end*********************** */

/**
 * 后台管理员ID
 */
function get_admin_id()
{
    $self = explode("/", substr(request()->getRequestUri(), 1));
    $count = count($self);

    $admin_id = 0;
    if ($count > 1) {
        $real_path = $self['0'];
        if ($real_path == ADMIN_PATH) {
            $admin_id = session('admin_id', 0);
        } elseif (($real_path == SELLER_PATH || $real_path == STORES_PATH) && session()->has('seller_id')) {
            $admin_id = session('seller_id', 0);
        } elseif ($real_path == SUPPLLY_PATH) {
            $supplierEnabled = app(CommonRepository::class)->judgeSupplierEnabled();
            if ($supplierEnabled) {
                $admin_id = session('supply_id');
            }
        }
    }

    return $admin_id;
}

//获取入驻商家的前台会员ID
function get_admin_ru_id()
{
    $admin_id = get_admin_id();

    $res = AdminUser::where('user_id', $admin_id);
    $res = app(BaseRepository::class)->getToArrayFirst($res);

    return $res;
}

//获取入驻商家的可用分类权限 start
function get_user_category($options, $shopMain_category, $ru_id = 0, $admin_type = 0)
{
    if ($ru_id > 0) {
        $shopMain_category = get_category_child_tree($shopMain_category);
        $arr = [];
        if (!empty($shopMain_category)) {
            $category = explode(',', $shopMain_category);
            foreach ($options as $key => $row) {
                if ($row['level'] < 3) {
                    for ($i = 0; $i < count($category); $i++) {
                        if ($key == $category[$i]) {
                            $arr[$key] = $row;
                        }
                    }
                } else {
                    $uc_id = MerchantsCategory::where('cat_id', $row['cat_id'])
                        ->where('user_id', $ru_id)
                        ->value('cat_id');

                    if ($admin_type == 0) {
                        if ($uc_id > 0) {
                            $arr[$key] = $row;
                        }
                    }
                }
            }
        }

        return $arr;
    } else {
        return $options;
    }
}

function get_category_child_tree($shopMain_category, $ru_id = 0, $type = 0)
{
    $category = explode('-', $shopMain_category);

    for ($i = 0; $i < count($category); $i++) {
        $category[$i] = explode(':', $category[$i]);

        if ($category[$i][0]) {
            $cat_info = MerchantsCategory::catInfo($category[$i][0]);
            $cat_info = app(BaseRepository::class)->getToArrayFirst($cat_info);

            $category[$i]['id'] = $category[$i][0];
            $category[$i]['name'] = $cat_info['cat_name'];
            $category[$i]['nolinkname'] = $cat_info['cat_name'];
            $category[$i]['cat_id'] = $category[$i][0];
            $category[$i]['cat_alias_name'] = $cat_info['cat_alias_name'];

            $twoChild = explode(',', $category[$i][1]);
            for ($j = 0; $j < count($twoChild); $j++) {
                if ($type == 0) {
                    $threeChild = Category::where('parent_id', $twoChild[$j]);
                    $threeChild = app(BaseRepository::class)->getToArrayGet($threeChild);

                    $category[$i]['three_' . $twoChild[$j]] = get_category_three_child($threeChild);
                    $category[$i]['three'] .= $category[$i][0] . ',' . $category[$i][1] . ',' . $category[$i]['three_' . $twoChild[$j]]['threeChild'] . ',';
                } elseif ($type == 1) {
                    if ($category[$i][1]) {
                        $category[$i][1] = app(DscRepository::class)->delStrComma($category[$i][1]);

                        $cat_id = explode(",", $category[$i][1]);
                        $child_tree = Category::whereIn('cat_id', $cat_id);
                        $child_tree = app(BaseRepository::class)->getToArrayGet($child_tree);

                        if ($child_tree) {
                            foreach ($child_tree as $key => $row) {
                                $category[$i]['child_tree'][$key]['id'] = $row['cat_id'];
                                $category[$i]['child_tree'][$key]['name'] = $row['cat_name'];

                                $build_uri = [
                                    'cid' => $row['cat_id'],
                                    'urid' => $ru_id,
                                    'append' => $row['cat_name']
                                ];

                                $domain_url = app(MerchantCommonService::class)->getSellerDomainUrl($ru_id, $build_uri);

                                if ($ru_id) {
                                    $category[$i]['child_tree'][$key]['url'] = $domain_url['domain_name'];
                                } else {
                                    $category[$i]['child_tree'][$key]['url'] = app(DscRepository::class)->buildUri('category', ['cid' => $row['cat_id']], $row['cat_name']);
                                }

                                $category[$i]['child_tree'][$key]['cat_id'] = app(CategoryService::class)->getChildTree($row['cat_id'], $ru_id);
                            }
                        }
                    }
                }
            }

            if ($type == 0) {
                $category[$i]['three'] = substr($category[$i]['three'], 0, -1);
            }
        }
    }

    if ($type == 0) {
        $category = get_link_cat_id($category);
        $category = $category['all_cat'];
    }

    return $category;
}

function get_category_three_child($threeChild)
{
    for ($i = 0; $i < count($threeChild); $i++) {
        if (!empty($threeChild[$i]['cat_id'])) {
            $threeChild['threeChild'] .= $threeChild[$i]['cat_id'] . ",";
        }
    }

    $threeChild['threeChild'] = substr($threeChild['threeChild'], 0, -1);

    return $threeChild;
}

function get_link_cat_id($category)
{
    for ($i = 0; $i < count($category); $i++) {
        if (!empty($category[$i]['three'])) {
            $category['all_cat'] .= $category[$i]['three'] . ',';
        }
    }

    $category['all_cat'] = substr($category['all_cat'], 0, -1);

    return $category;
}

//获取入驻商家的可用分类权限 end

//前端程序代码-------------------------------------

//协议信息
function get_root_directory_steps($sid)
{
    $row = MerchantsStepsProcess::where('process_steps', $sid);
    $row = app(BaseRepository::class)->getToArrayFirst($row);

    if ($row['process_article'] > 0) {
        $row['article_centent'] = Article::where('article_id', $row['process_article'])->value('content');
    }

    return $row;
}

//申请步骤列表
function get_root_steps_process_list($sid)
{
    $res = MerchantsStepsProcess::where('process_steps', $sid)
        ->where('is_show', 1)
        ->orderBy('steps_sort');
    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    if ($res) {
        foreach ($res as $key => $row) {
            $arr[$key]['id'] = $row['id'];
            $arr[$key]['process_title'] = $row['process_title'];
            $arr[$key]['fields_next'] = $row['fields_next'];
        }
    }

    return $arr;
}

//流程信息列表
function get_root_merchants_steps_title($pid = 0, $user_id = 0)
{
    $brand = app(BrandService::class);
    $image = app(Image::class, [$GLOBALS['_CFG']['bgcolor']]);

    //自定义表单数据插入 start
    $brandId = isset($_REQUEST['brandId']) ? intval($_REQUEST['brandId']) : 0;
    $search_brandType = isset($_REQUEST['search_brandType']) ? htmlspecialchars($_REQUEST['search_brandType']) : '';
    $searchBrandZhInput = isset($_REQUEST['searchBrandZhInput']) ? htmlspecialchars(trim($_REQUEST['searchBrandZhInput'])) : '';
    $searchBrandZhInput = !empty($searchBrandZhInput) ? addslashes($searchBrandZhInput) : '';
    $searchBrandEnInput = isset($_REQUEST['searchBrandEnInput']) ? htmlspecialchars(trim($_REQUEST['searchBrandEnInput'])) : '';
    $searchBrandEnInput = !empty($searchBrandEnInput) ? addslashes($searchBrandEnInput) : '';

    $ec_shop_bid = isset($_REQUEST['ec_shop_bid']) ? intval($_REQUEST['ec_shop_bid']) : 0;
    $ec_shoprz_type = isset($_POST['ec_shoprz_type']) ? intval($_POST['ec_shoprz_type']) : 0;
    $ec_subShoprz_type = isset($_POST['ec_subShoprz_type']) ? intval($_POST['ec_subShoprz_type']) : 0;
    $ec_shop_expireDateStart = isset($_POST['ec_shop_expireDateStart']) ? htmlspecialchars(trim($_POST['ec_shop_expireDateStart'])) : '';
    $ec_shop_expireDateEnd = isset($_POST['ec_shop_expireDateEnd']) ? htmlspecialchars(trim($_POST['ec_shop_expireDateEnd'])) : '';
    $ec_shop_permanent = isset($_POST['ec_shop_permanent']) ? intval($_POST['ec_shop_permanent']) : 0;
    $ec_shop_categoryMain = isset($_POST['ec_shop_categoryMain']) ? intval($_POST['ec_shop_categoryMain']) : 0;

    //品牌基本信息
    $bank_name_letter = isset($_POST['ec_bank_name_letter']) ? htmlspecialchars(trim($_POST['ec_bank_name_letter'])) : $searchBrandEnInput;
    $brandName = isset($_POST['ec_brandName']) ? htmlspecialchars(trim($_POST['ec_brandName'])) : $searchBrandZhInput;
    $brandFirstChar = isset($_POST['ec_brandFirstChar']) ? htmlspecialchars(trim($_POST['ec_brandFirstChar'])) : substr($searchBrandEnInput, 0, 1);

    $brandLogo = '';
    if (isset($_FILES['ec_brandLogo']) && !empty($_FILES['ec_brandLogo'])) {
        $brandLogo = $image->upload_image($_FILES['ec_brandLogo'], 'septs_image');  //图片存放地址 -- data/septs_image

        app(DscRepository::class)->getOssAddFile([$brandLogo]);
    }

    $text_brandLogo = isset($_POST['text_brandLogo']) ? trim($_POST['text_brandLogo']) : '';
    $brandType = isset($_POST['ec_brandType']) ? intval($_POST['ec_brandType']) : 0;
    $brand_operateType = isset($_POST['ec_brand_operateType']) ? intval($_POST['ec_brand_operateType']) : 0;
    $brandEndTime = isset($_POST['ec_brandEndTime']) ? trim($_POST['ec_brandEndTime']) : '';
    $brandEndTime_permanent = isset($_POST['ec_brandEndTime_permanent']) ? intval($_POST['ec_brandEndTime_permanent']) : 0;

    //品牌资质证件
    $qualificationNameInput = isset($_POST['ec_qualificationNameInput']) ? $_POST['ec_qualificationNameInput'] : [];
    $qualificationImg = isset($_FILES['ec_qualificationImg']) ? $_FILES['ec_qualificationImg'] : '';
    $expiredDateInput = isset($_POST['ec_expiredDateInput']) ? $_POST['ec_expiredDateInput'] : [];
    $b_fid = isset($_POST['b_fid']) ? $_POST['b_fid'] : [];

    //店铺命名信息
    $ec_shoprz_brandName = isset($_POST['ec_shoprz_brandName']) ? htmlspecialchars(trim($_POST['ec_shoprz_brandName'])) : '';
    $ec_shop_class_keyWords = isset($_POST['ec_shop_class_keyWords']) ? htmlspecialchars(trim($_POST['ec_shop_class_keyWords'])) : '';
    $ec_shopNameSuffix = isset($_POST['ec_shopNameSuffix']) ? htmlspecialchars(trim($_POST['ec_shopNameSuffix'])) : '';
    $ec_rz_shopName = isset($_POST['ec_rz_shopName']) ? htmlspecialchars(trim($_POST['ec_rz_shopName'])) : '';
    $ec_hopeLoginName = isset($_POST['ec_hopeLoginName']) ? htmlspecialchars(trim($_POST['ec_hopeLoginName'])) : '';
    $region_id = isset($_POST['rs_city_id']) ? intval($_POST['rs_city_id']) : 0; //卖场-入驻地区

    $brand_m = [];
    $brand_info = [];

    $shop_info = MerchantsShopInformation::where('user_id', session('user_id'));
    $shop_info = app(BaseRepository::class)->getToArrayFirst($shop_info);

    //入驻品牌
    if ($ec_shop_bid > 0) {
        $brand_info = MerchantsShopBrand::where('bid', $ec_shop_bid)->where('user_id', session('user_id'));
        $brand_info = app(BaseRepository::class)->getToArrayFirst($brand_info);
    } else {
        if ($brandId > 0) {
            if ($search_brandType == 'm_bran') {
                $search_brandType = 'merchants_brands';
            } else {
                $search_brandType = '';
            }

            $brand_info = $brand->getBrandInfo($brandId, $search_brandType);

            $bank_name_letter = $brand_info['brand_letter'];
            $brandName = $brand_info['brand_name'];
            $brandFirstChar = substr($brand_info['brand_letter'], 0, 1);

            if ($search_brandType != 'merchants_brands') {
                $brandLogo = get_image_path(DATA_DIR . '/brandlogo/' . $brand_info['brand_logo']);
            } else {
                $brandLogo = $brand_info['brand_logo'];
                $brand_m = $brand->getBrandInfo($brand_info['brand_name'], $search_brandType, 1);
            }
        }
    }

    $res = MerchantsStepsTitle::where('fields_steps', $pid);
    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    if ($res) {
        foreach ($res as $key => $row) {
            $shop_id = MerchantsShopInformation::where('user_id', session('user_id'))->value('shop_id');

            $arr[$key]['tid'] = $row['tid'];
            $arr[$key]['fields_titles'] = $row['fields_titles'];
            $arr[$key]['titles_annotation'] = $row['titles_annotation'];
            $arr[$key]['steps_style'] = $row['steps_style'];
            $arr[$key]['fields_special'] = $row['fields_special'];
            $arr[$key]['special_type'] = $row['special_type'];

            $centent = MerchantsStepsFieldsCentent::where('tid', $row['tid']);
            $centent = app(BaseRepository::class)->getToArrayFirst($centent);

            $cententFields = [];
            if ($centent) {
                $cententFields = get_fields_centent_info($centent['id'], $centent['textFields'], $centent['fieldsDateType'], $centent['fieldsLength'], $centent['fieldsNotnull'], $centent['fieldsFormName'], $centent['fieldsCoding'], $centent['fieldsForm'], $centent['fields_sort'], $centent['will_choose'], 'root', $user_id);
            }

            $arr[$key]['cententFields'] = isset($cententFields) ? get_array_sort($cententFields, 'fields_sort') : $cententFields;

            if ($row['steps_style'] == 1) {
                $ec_authorizeFile_path = $_FILES['ec_authorizeFile'] ?? '';
                $ec_shop_hypermarketFile_path = $_FILES['ec_shop_hypermarketFile'] ?? '';

                $ec_authorizeFile = $image->upload_image($ec_authorizeFile_path, 'septs_image');  //图片存放地址 -- data/septs_image
                $ec_shop_hypermarketFile = $image->upload_image($ec_shop_hypermarketFile_path, 'septs_image');  //图片存放地址 -- data/septs_image

                app(DscRepository::class)->getOssAddFile([$ec_authorizeFile, $ec_shop_hypermarketFile]);

                $ec_shop_hypermarketFile = empty($ec_shop_hypermarketFile) ? $shop_info['shop_hypermarketFile'] : $ec_shop_hypermarketFile;
                $ec_authorizeFile = empty($ec_authorizeFile) ? $shop_info['authorizeFile'] : $ec_authorizeFile;

                if ($ec_shop_permanent != 1) {
                    $ec_shop_expireDateStart = empty($ec_shop_expireDateStart) ? ($shop_info['shop_expireDateStart'] ?? '') : $ec_shop_expireDateStart;
                    $ec_shop_expireDateEnd = empty($ec_shop_expireDateEnd) ? ($shop_info['shop_expireDateEnd'] ?? '') : $ec_shop_expireDateEnd;

                } else {
                    $ec_shop_expireDateStart = '';
                    $ec_shop_expireDateEnd = '';
                }

                //判断数据是否存在，如果存在则引用 start
                if ($ec_shoprz_type == 0) {
                    $ec_shoprz_type = $shop_info['shoprz_type'] ?? '';
                }
                if ($ec_subShoprz_type == 0) {
                    $ec_subShoprz_type = $shop_info['subShoprz_type'] ?? '';
                }
                if ($ec_shop_categoryMain == 0) {
                    $ec_shop_categoryMain = $shop_info['shop_categoryMain'] ?? '';
                }
                //判断数据是否存在，如果存在则引用 end

                $parent = [//店铺类型数据插入
                    'user_id' => session('user_id'),
                    'shoprz_type' => $ec_shoprz_type,
                    'subShoprz_type' => $ec_subShoprz_type,
                    'shop_expireDateStart' => $ec_shop_expireDateStart,
                    'shop_expireDateEnd' => $ec_shop_expireDateEnd,
                    'shop_permanent' => $ec_shop_permanent,
                    'authorizeFile' => $ec_authorizeFile,
                    'shop_hypermarketFile' => $ec_shop_hypermarketFile,
                    'shop_categoryMain' => $ec_shop_categoryMain
                ];
                if (session('user_id') > 0) {
                    if ($shop_id > 0) {
                        if ($parent['shop_expireDateStart'] == '' || $parent['shop_expireDateEnd'] == '') {
                            if ($ec_shop_permanent != 1) {
                                if ($shop_info['shop_permanent'] == 1) {
                                    $parent['shop_permanent'] = $shop_info['shop_permanent'];
                                }
                            }
                        }

                        if (empty($parent['authorizeFile'])) {
                            $parent['shop_permanent'] = 0;
                        } else {
                            if ($parent['shop_expireDateStart'] == '' || $parent['shop_expireDateEnd'] == '') {
                                $parent['shop_permanent'] = 1;
                                $parent['shop_expireDateStart'] = '';
                                $parent['shop_expireDateEnd'] = '';
                            }
                        }
                        $parent['update_time'] = gmtime();
                        MerchantsShopInformation::where('user_id', session('user_id'))->update($parent);
                    } else {
                        $parent['add_time'] = gmtime();
                        MerchantsShopInformation::insert($parent);
                    }
                }

            } elseif ($row['steps_style'] == 2) { //一级类目列表
                //2014-11-19 start
                if (session('user_id') > 0) {
                    if ($shop_id < 1) {
                        $parent['user_id'] = session('user_id');
                        $parent['shop_categoryMain'] = $ec_shop_categoryMain;

                        $parent['add_time'] = gmtime();
                        MerchantsShopInformation::insert($parent);
                    }
                }
                //2014-11-19 end

                $arr[$key]['first_cate'] = get_first_cate_list(0, 0, [], session('user_id'));
                $catId_array = get_catId_array();

                $parent['user_shopMain_category'] = implode('-', $catId_array);

                //2014-11-19 start
                if ($ec_shop_categoryMain == 0) {
                    $ec_shop_categoryMain = $shop_info['shop_categoryMain'] ?? '';
                    $parent['shop_categoryMain'] = $ec_shop_categoryMain;
                }
                $parent['shop_categoryMain'] = $ec_shop_categoryMain;
                if (empty($parent['brandEndTime'])) {
                    unset($parent['brandEndTime']);
                }
                //2014-11-19 end

                MerchantsShopInformation::where('user_id', session('user_id'))->update($parent);

                if (!empty($parent['user_shopMain_category'])) {
                    get_update_temporarydate_isAdd($catId_array, session('user_id'));
                }
                get_update_temporarydate_isAdd($catId_array, session('user_id'), 1);
            } elseif ($row['steps_style'] == 3) {
                //品牌列表
                $arr[$key]['brand_list'] = get_septs_shop_brand_list(session('user_id')); //品牌列表

                if ($ec_shop_bid > 0 && (isset($brand_m['brand_id']) && $brand_m['brand_id'] > 0)) { //更新品牌数据
                    $bank_name_letter = empty($bank_name_letter) ? $brand_info['bank_name_letter'] : $bank_name_letter;
                    $brandName = empty($brandName) ? $brand_info['brandName'] : $brandName;
                    $brandFirstChar = empty($brandFirstChar) ? $brand_info['brandFirstChar'] : $brandFirstChar;
                    $brandLogo = empty($brandLogo) ? $brand_info['brandLogo'] : $brandLogo;
                    $brandType = empty($brandType) ? $brand_info['brandType'] : $brandType;
                    $brand_operateType = empty($brand_operateType) ? $brand_info['brand_operateType'] : $brand_operateType;
                    $brandEndTime = empty($brandEndTime) ? $brand_info['brandEndTime'] : local_strtotime($brandEndTime);
                    $brandEndTime_permanent = empty($brandEndTime_permanent) ? $brand_info['brandEndTime_permanent'] : $brandEndTime_permanent;

                    $brandfile_list = get_shop_brandfile_list($ec_shop_bid);
                    $arr[$key]['brandfile_list'] = $brandfile_list;

                    app(DscRepository::class)->getOssAddFile([$brandLogo]);

                    $parent = [
                        'user_id' => session('user_id'),
                        'bank_name_letter' => $bank_name_letter,
                        'brandName' => $brandName,
                        'brandFirstChar' => $brandFirstChar,
                        'brandLogo' => get_image_path($brandLogo),
                        'brandType' => $brandType,
                        'brand_operateType' => $brand_operateType,
                        'brandEndTime' => $brandEndTime,
                        'brandEndTime_permanent' => $brandEndTime_permanent
                    ];

                    if (!empty($parent['brandEndTime'])) {
                        $arr[$key]['parentType']['brandEndTime'] = $parent['brandEndTime']; //输出
                    }
                    if (session('user_id') > 0) {
                        if ($parent['brandEndTime_permanent'] == 1) {
                            $parent['brandEndTime'] = '';
                        }
                        if (session('user_id') == $brand_info['user_id']) {
                            MerchantsShopBrand::where('user_id', session('user_id'))->where('bid', $ec_shop_bid)->update($parent);
                            get_shop_brand_file($qualificationNameInput, $qualificationImg, $expiredDateInput, $b_fid, $ec_shop_bid); //品牌资质文件上传
                        }
                    }
                } else { //插入品牌数据
                    if (session('user_id') > 0 && !isset($_REQUEST['searchBrandZhInput']) && !isset($_REQUEST['searchBrandEnInput'])) {
                        if (empty($brandLogo)) {
                            $brandLogo = $text_brandLogo;
                        }
                        $parent = [
                            'user_id' => session('user_id'),
                            'bank_name_letter' => $bank_name_letter,
                            'brandName' => $brandName,
                            'brandFirstChar' => $brandFirstChar,
                            'brandLogo' => $brandLogo,
                            'brandType' => $brandType,
                            'brand_operateType' => $brand_operateType,
                            'brandEndTime' => $brandEndTime,
                            'brandEndTime_permanent' => $brandEndTime_permanent,
                            'add_time' => gmtime()
                        ];
                        if (!empty($brandName)) {
                            $bRes = MerchantsShopBrand::where('brandName', $brandName)->where('user_id', session('user_id'))->value('bid');
                            if ($bRes > 0) {
                                MerchantsShopBrand::where('user_id', session('user_id'))->where('bid', $bRes)->update($parent);
                                get_shop_brand_file($qualificationNameInput, $qualificationImg, $expiredDateInput, $b_fid, $bRes); //品牌资质文件上传

                                $back_pid_key = $row['steps_style'] - 1;
                                $back_url = "merchants_steps.php?step=stepThree&pid_key=" . $back_pid_key;
                                return dsc_header("Location: " . $back_url . "\n");
                            } else {
                                $bid = MerchantsShopBrand::insertGetId($parent);
                                get_shop_brand_file($qualificationNameInput, $qualificationImg, $expiredDateInput, $b_fid, $bid); //品牌资质文件上传
                            }
                        }
                    }
                }
                // 查看品牌logo
                $parent['brandLogo'] = isset($parent['brandLogo']) ? get_image_path($parent['brandLogo']) : '';
            } elseif ($row['steps_style'] == 4) {
                $brand_list = MerchantsShopBrand::where('user_id', session('user_id'));
                $brand_list = app(BaseRepository::class)->getToArrayGet($brand_list);

                $arr[$key]['brand_list'] = $brand_list;

                $ec_shoprz_brandName = empty($ec_shoprz_brandName) ? $shop_info['shoprz_brandName'] : $ec_shoprz_brandName;
                $ec_shop_class_keyWords = empty($ec_shop_class_keyWords) ? $shop_info['shop_class_keyWords'] : $ec_shop_class_keyWords;
                $ec_shopNameSuffix = empty($ec_shopNameSuffix) ? $shop_info['shopNameSuffix'] : $ec_shopNameSuffix;
                $ec_rz_shopName = empty($ec_rz_shopName) ? $shop_info['rz_shopName'] : $ec_rz_shopName;
                $ec_hopeLoginName = empty($ec_hopeLoginName) ? $shop_info['hopeLoginName'] : $ec_hopeLoginName;
                $region_id = empty($region_id) ? $shop_info['region_id'] : $region_id; //卖场-入驻地区
                //卖场-入驻地区
                $belong_region = [];
                $belong_region['region_id'] = $region_id;
                $belong_region['region_level'] = get_region_level($region_id);

                $city_id = isset($belong_region['region_level']) && !empty($belong_region['region_level']) ? $belong_region['region_level'][1] : 0;
                $belong_region['country_list'] = get_regions_steps();
                $belong_region['province_list'] = get_regions_steps(1, 1);
                $belong_region['city_list'] = get_regions_steps(2, $city_id);
                $arr[$key]['belong_region'] = $belong_region;

                if (!empty($ec_rz_shopName)) {
                    $parent = [
                        'user_id' => session('user_id'),
                        'shoprz_brandName' => $ec_shoprz_brandName,
                        'shop_class_keyWords' => $ec_shop_class_keyWords,
                        'shopNameSuffix' => $ec_shopNameSuffix,
                        'rz_shopName' => $ec_rz_shopName,
                        'hopeLoginName' => $ec_hopeLoginName,
                        'region_id' => $region_id //卖场-入驻地区
                    ];

                    if (session('user_id') > 0) {
                        if ($shop_id > 0) {
                            $parent['update_time'] = gmtime();
                            MerchantsShopInformation::where('user_id', session('user_id'))->update($parent);
                        } else {
                            $parent['add_time'] = gmtime();
                            MerchantsShopInformation::insert($parent);
                        }
                    }
                }

                $parent['shoprz_type'] = $shop_info['shoprz_type'];
            }

            $parent['brandEndTime'] = isset($arr[$key]['parentType']) && $arr[$key]['parentType'] ? $arr[$key]['parentType']['brandEndTime'] : ''; //品牌使用时间
            $arr[$key]['parentType'] = $parent; //自定义显示
            //自定义表单数据插入 end
        }
    }

    return $arr;
}

//更新临时表中的数据为插入
function get_update_temporarydate_isAdd($catId_array = [], $user_id = 0, $type = 0)
{
    $arr = [];

    if ($catId_array) {
        if ($type == 0) {
            for ($i = 0; $i < count($catId_array); $i++) {
                $parentChild = explode(':', $catId_array[$i]);
                $arr[$i] = explode(',', $parentChild[1]);

                for ($j = 0; $j < count($arr[$i]); $j++) {
                    MerchantsCategoryTemporarydate::where('cat_id', $arr[$i][$j])->update(['is_add' => 1]);
                }
            }
        } else {
            for ($i = 0; $i < count($catId_array); $i++) {
                $parentChild = explode(':', $catId_array[$i]);
                $arr[$i] = explode(',', $parentChild[1]);

                $cat_id = $_POST['permanentCat_id_' . $parentChild[0]] ?? [];
                $dt_id = $_POST['permanent_title_' . $parentChild[0]] ?? [];
                $permanentFile['name'] = $_FILES['permanentFile_' . $parentChild[0]]['name'] ?? '';
                $permanentFile['type'] = $_FILES['permanentFile_' . $parentChild[0]]['type'] ?? '';
                $permanentFile['tmp_name'] = $_FILES['permanentFile_' . $parentChild[0]]['tmp_name'] ?? '';
                $permanentFile['error'] = $_FILES['permanentFile_' . $parentChild[0]]['error'] ?? '';
                $permanentFile['size'] = $_FILES['permanentFile_' . $parentChild[0]]['size'] ?? '';
                $permanent_date = $_POST['categoryId_date_' . $parentChild[0]] ?? [];

                if (count($cat_id) > 0) { //操作一级类目证件插入或更新数据
                    get_merchants_dt_file_insert_update($cat_id, $dt_id, $permanentFile, $permanent_date, $user_id);
                }
            }
        }
    }

    return $arr;
}

//类目证件插入或更新数据函数
function get_merchants_dt_file_insert_update($cat_id, $dt_id, $permanentFile, $permanent_date, $user_id)
{
    $user_id = session('user_id', 0);

    $image = app(Image::class, [$GLOBALS['_CFG']['bgcolor']]);

    for ($i = 0; $i < count($cat_id); $i++) {
        $row = MerchantsDtFile::where('cat_id', $cat_id[$i])
            ->where('dt_id', $dt_id[$i])
            ->where('user_id', $user_id);
        $row = app(BaseRepository::class)->getToArrayFirst($row);

        $pFile = $image->upload_image([], 'septs_image', '', 1, $permanentFile['name'][$i], $permanentFile['type'][$i], $permanentFile['tmp_name'][$i], $permanentFile['error'][$i], $permanentFile['size'][$i]);  //图片存放地址 -- data/septs_image

        if (empty($pFile)) {
            $pFile = $row['permanent_file'] ?? '';
        }

        app(DscRepository::class)->getOssAddFile([$pFile]);

        if (!empty($permanent_date[$i])) {
            $permanent_date[$i] = local_strtotime(trim($permanent_date[$i]));
        } else {
            $permanent_date[$i] = '';
        }

        if (!empty($pFile)) {
            if (!empty($permanent_date[$i])) {
                $catPermanent = 0;
            } else {
                $catPermanent = 1;
            }
        } else {
            $catPermanent = 0;
        }

        $parent = [
            'cat_id' => intval($cat_id[$i]),
            'dt_id' => intval($dt_id[$i]),
            'user_id' => $user_id,
            'permanent_file' => $pFile,
            'permanent_date' => $permanent_date[$i],
            'cate_title_permanent' => $catPermanent
        ];

        if ($row && $row['dtf_id'] > 0) {
            MerchantsDtFile::where('cat_id', $cat_id[$i])
                ->where('dt_id', $dt_id[$i])
                ->where('user_id', $user_id)
                ->update($parent);
        } else {
            MerchantsDtFile::insert($parent);
        }
    }
}

//入驻品牌列表 start
function get_septs_shop_brand_list($user_id = 0)
{
    $res = MerchantsShopBrand::where('user_id', $user_id)->orderBy('bid');
    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    if ($res) {
        foreach ($res as $key => $row) {
            $key = $key + 1;
            $arr[$key]['bid'] = $row['bid'];
            $arr[$key]['bank_name_letter'] = $row['bank_name_letter'];
            $arr[$key]['brandName'] = $row['brandName'];
            $arr[$key]['brandFirstChar'] = $row['brandFirstChar'];
            $arr[$key]['brandLogo'] = get_image_path($row['brandLogo']);
            $arr[$key]['brandType'] = $row['brandType'];
            $arr[$key]['brand_operateType'] = $row['brand_operateType'];
            $arr[$key]['brandEndTime'] = (isset($row['brandEndTime']) && !empty($row['brandEndTime'])) ? $row['brandEndTime'] : '';
        }
    }

    return $arr;
}

//复制店铺品牌
function copy_septs_shop_brand_list($user_id = 0)
{
    $row = MerchantsShopBrand::where('user_id', $user_id);
    $row = app(BaseRepository::class)->getToArrayFirst($row);

    if ($row) {
        unset($row['bid']);

        /* 获取表字段 */
        $row_other = app(BaseRepository::class)->getArrayfilterTable($row, 'merchants_shop_brand');

        MerchantsShopBrand::insert($row_other);
    }
}

//品牌资质文件上传
function get_shop_brand_file($qInput, $qImg, $eDinput, $b_fid, $ec_shop_bid)
{
    $image = app(Image::class, [$GLOBALS['_CFG']['bgcolor']]);

    for ($i = 0; $i < count($qInput); $i++) {
        if (isset($qImg['name'][$i]) && $qImg['name'][$i]) {
            $qInput[$i] = trim($qInput[$i]);
            $qImg[$i] = $image->upload_image([], 'septs_image', '', 1, $qImg['name'][$i], $qImg['type'][$i], $qImg['tmp_name'][$i], $qImg['error'][$i], $qImg['size'][$i]);  //图片存放地址 -- data/septs_image

            $eDinput[$i] = trim($eDinput[$i]);

            if (empty($qImg[$i])) { //证件是否永久有效
                $qPermanent = 0;
            } else {
                if (!empty($eDinput[$i])) {
                    $qPermanent = 0;
                } else {
                    $qPermanent = 1;
                }
            }

            if (!empty($eDinput[$i])) {
                $eDinput[$i] = local_strtotime($eDinput[$i]);
            } else {
                $eDinput[$i] = '';
            }

            app(DscRepository::class)->getOssAddFile([$qImg[$i]]);

            if (!empty($qInput[$i])) {
                $parent = [
                    'bid' => $ec_shop_bid,
                    'qualificationNameInput' => $qInput[$i],
                    'expiredDateInput' => $eDinput[$i],
                    'expiredDate_permanent' => $qPermanent
                ];

                if ($qImg[$i]) {
                    $parent['qualificationImg'] = $qImg[$i];
                }

                if (!empty($b_fid[$i])) {
                    MerchantsShopBrandfile::where('bid', $ec_shop_bid)
                        ->where('b_fid', $b_fid[$i])
                        ->update($parent);
                } else {
                    MerchantsShopBrandfile::insert($parent);
                }
            }
        }
    }
}

function get_shop_brandfile_list($ec_shop_bid = 0)
{
    $res = MerchantsShopBrandfile::where('bid', $ec_shop_bid)->orderBy('b_fid');
    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    if ($res) {
        foreach ($res as $key => $row) {
            $arr[] = $row;
            $arr[$key]['expiredDateInput'] = local_date("Y-m-d H:i", $row['expiredDateInput']);
            $arr[$key]['qualificationImg'] = get_image_path($row['qualificationImg']);
        }
    }

    return $arr;
}

//入驻品牌列表 end

//会员申请商家入驻表单填写数据插入 start
function get_steps_title_insert_form($pid = 0)
{
    $steps_title = get_root_merchants_steps_title($pid);

    $formName = '';
    if ($steps_title) {
        for ($i = 0; $i < count($steps_title); $i++) {
            if (is_array($steps_title[$i]['cententFields'])) {
                $cententFields = $steps_title[$i]['cententFields'];
                for ($j = 1; $j <= count($cententFields); $j++) {
                    $formName .= $cententFields[$j]['textFields'] . ',';
                }
            }
        }
    }

    $arr['formName'] = $formName ? substr($formName, 0, -1) : '';

    return $arr;
}

//返回插入基本信息字段数据
function get_setps_form_insert_date($formName)
{
    $image = app(Image::class, [$GLOBALS['_CFG']['bgcolor']]);

    $arr = [];

    if ($formName) {
        $formName = explode(',', $formName);
        for ($i = 0; $i < count($formName); $i++) {

            //如果上传文件字段是图片或者压缩包 字段命名必须是 ******Img 格式 (自定义的上传文件)
            if (substr($formName[$i], -3) == 'Img') {
                $septs_image = isset($_FILES[$formName[$i]]) ? $_FILES[$formName[$i]] : '';

                //图片存放地址 -- data/septs_image
                $setps_thumb = $image->upload_image($septs_image, 'septs_image');

                app(DscRepository::class)->getOssAddFile([$setps_thumb]);
                //文本隐藏域数据
                $textImg = $_POST['text_' . $formName[$i]] ?? '';
                if (empty($setps_thumb)) {
                    if (!empty($textImg)) {
                        $setps_thumb = $textImg;
                    }
                }

                if ($setps_thumb) {
                    $arr[$formName[$i]] = $setps_thumb;
                }
            } else {
                $name = isset($_POST[$formName[$i]]) && $_POST[$formName[$i]] ? $_POST[$formName[$i]] : '';

                if ($name) {
                    $arr[$formName[$i]] = $name;
                }
            }

            if (isset($arr[$formName[$i]]) && $arr[$formName[$i]] && is_array($arr[$formName[$i]])) {
                $arr[$formName[$i]] = implode(',', $arr[$formName[$i]]);
            }
        }
    }
    if (isset($_POST['ec_shop_permanent']) && !empty($_POST['ec_shop_permanent'])) {
        $arr['shopTime_term'] = $_POST['ec_shop_permanent'];
    } else {
        $arr['shopTime_term'] = 0;
    }

    return $arr;
}

//会员申请商家入驻表单填写数据插入 end

//一级类目列表
function get_first_cate_list($parent_id = 0, $type = 0, $catarr = [], $user_id = 0)
{
    if ($type == 1) {
        if ($catarr) {
            for ($i = 0; $i < count($catarr); $i++) {
                if (!empty($catarr[$i])) {
                    MerchantsCategoryTemporarydate::where('cat_id', $catarr[$i])
                        ->where('user_id', $user_id)
                        ->delete();
                }
            }
        }

        return [];
    } else {
        $res = Category::where('parent_id', $parent_id);
        $res = app(BaseRepository::class)->getToArrayGet($res);

        return $res;
    }
}

//查询二级类目详细信息 start //ajax返回类目数组
function get_child_category($cat)
{
    $arr = ['cat_id' => ''];
    for ($i = 0; $i < count($cat); $i++) {
        if (!empty($cat[$i])) {
            $arr[$i] = $cat[$i];
            $arr['cat_id'] .= intval($cat[$i]) . ',';
        }
    }

    $arr['cat_id'] = substr($arr['cat_id'], 0, -1);

    return $arr;
}

//二级类目数据插入临时数据表
function get_add_childCategory_info($cat_id = [], $user_id = 0)
{
    if (empty($cat_id)) {
        return [];
    }

    $cat_id = !is_array($cat_id) ? explode(",", $cat_id) : $cat_id;
    $res = Category::whereIn('cat_id', $cat_id)->orderBy('cat_id');
    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    if ($res) {
        foreach ($res as $key => $row) {
            $key = $key + 1;
            $arr[$key]['cat_id'] = $row['cat_id'];
            $arr[$key]['cat_name'] = $row['cat_name'];

            $arr[$key]['parent_name'] = Category::where('cat_id', $row['parent_id'])->value('cat_name');

            $parent = [
                'user_id' => $user_id,
                'cat_id' => $row['cat_id'],
                'parent_id' => $row['parent_id'],
                'cat_name' => $row['cat_name'],
                'parent_name' => $arr[$key]['parent_name']
            ];

            if ($cat_id != 0) {
                $ct_id = MerchantsCategoryTemporarydate::where('cat_id', $row['cat_id'])
                    ->where('user_id', $user_id)
                    ->value('ct_id');

                if ($ct_id <= 0) {
                    MerchantsCategoryTemporarydate::insert($parent);
                }
            }
        }
    }

    return $arr;
}

//查询临时数据表中的数据
function get_fine_category_info($cat_id, $user_id)
{
    if ($cat_id != 0) {
        get_add_childCategory_info($cat_id, $user_id);
    }

    $res = MerchantsCategoryTemporarydate::where('user_id', $user_id)
        ->orderBy('cat_id');
    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    if ($res) {
        foreach ($res as $key => $row) {
            $key = $key + 1;
            $arr[$key]['ct_id'] = $row['ct_id'];
            $arr[$key]['cat_id'] = $row['cat_id'];
            $arr[$key]['cat_name'] = $row['cat_name'];
            $arr[$key]['parent_name'] = $row['parent_name'];
            $arr[$key]['parent_id'] = $row['parent_id'];
        }
    }

    return $arr;
}

function get_permanent_parent_cat_id($user_id = 0, $type = 0)
{
    $res = MerchantsCategoryTemporarydate::select('cat_id')
        ->where('user_id', $user_id)
        ->with([
            'getCategory' => function ($query) {
                $query->select('cat_id', 'parent_id');
            }
        ]);
    $res = app(BaseRepository::class)->getToArrayGet($res);

    if ($res) {
        foreach ($res as $key => $row) {
            $row = $row['get_category'] ? array_merge($row, $row['get_category']) : $row;

            $res[$key] = $row;
        }

        if ($type == 1) {
            $res = app(BaseRepository::class)->getGroupBy($res, 'parent_id');
        }
    }

    return $res;
}

//组合父ID的下级分类数组
function get_catId_array($user_id = 0)
{
    if ($user_id <= 0) {
        $user_id = session('user_id');
    }

    $res = get_permanent_parent_cat_id($user_id);

    $arr = [];
    if ($res) {
        foreach ($res as $key => $row) {
            @$arr[$row['parent_id']] .= $row['cat_id'] . ',';
        }

        $arr = get_explode_array($arr);
    }

    return $arr;
}

function get_explode_array($arr)
{
    $newArr = [];
    $i = 0;
    foreach ($arr as $key => $row) {
        $newArr[$i] = substr($key . ":" . $row, 0, -1);
        $i++;
    }

    return $newArr;
}

//查询类目证件标题列表
function get_category_permanent_list($user_id)
{
    $res = get_permanent_parent_cat_id($user_id, 1);

    $arr = [];
    if ($res) {
        $parentId = app(BaseRepository::class)->getArrayKeys($res);

        $res = MerchantsDocumenttitle::whereRaw(1);

        if ($parentId) {
            $res = $res->whereIn('cat_id', $parentId);
        }

        $res = $res->orderBy('dt_id');
        $res = app(BaseRepository::class)->getToArrayGet($res);

        $arr = [];
        if ($res) {
            foreach ($res as $key => $row) {
                $arr[$key]['dt_id'] = $row['dt_id'];
                $arr[$key]['dt_title'] = $row['dt_title'];
                $arr[$key]['cat_id'] = $row['cat_id'];

                $arr[$key]['cat_name'] = Category::where('cat_id', $row['cat_id'])->value('cat_name');

                $dtFile = MerchantsDtFile::where('cat_id', $row['cat_id'])
                    ->where('dt_id', $row['dt_id'])
                    ->where('user_id', $user_id);

                $dtFile = app(BaseRepository::class)->getToArrayFirst($dtFile);

                if ($dtFile) {
                    $arr[$key]['permanent_file'] = app(DscRepository::class)->getImagePath($dtFile['permanent_file']);
                    $arr[$key]['cate_title_permanent'] = $dtFile['cate_title_permanent'];
                    if (!empty($row['permanent_date'])) {
                        $arr[$key]['permanent_date'] = local_date("Y-m-d H:i", $dtFile['permanent_date']);
                    }
                }
            }
        }
    }

    return $arr;
}

//删除类目时查找父级类目的含有数据数量
function get_temporarydate_ctId_catParent($ct_id = 0)
{
    $parent_id = MerchantsCategoryTemporarydate::where('ct_id', $ct_id)->value('parent_id');
    $num = MerchantsCategoryTemporarydate::where('parent_id', $parent_id)->count();

    $arr['parent_id'] = $parent_id;
    $arr['num'] = $num;

    return $arr;
}

//查询二级类目详细信息 end

//获取地区名称
function get_goods_region_name($region_id)
{
    return Region::where('region_id', $region_id)->value('region_name');
}

//获取商品商家信息 start
function get_merchants_shop_info($user_id = 0)
{
    $res = MerchantsStepsFields::where('user_id', $user_id);
    $res = app(BaseRepository::class)->getToArrayFirst($res);

    return $res;
}

function get_license_comp_adress($steps_adress)
{
    $province = 0;
    $city = 0;
    if ($steps_adress) {
        $adress = explode(',', $steps_adress);
        $province = $adress[1];
        $city = $adress[2];
    }

    $arr['province'] = '';
    $arr['city'] = '';
    $arr['province'] = get_goods_region_name($province);
    $arr['city'] = get_goods_region_name($city);

    if (!empty($arr['city'])) {
        $arr['city'] = $arr['city'];
    }

    return $arr;
}

//获取商品商家信息 end

//仓库 start
//----admin
/**
 * 获取地区仓库列表的函数。 ecmoban模板堂 --zhuo
 *
 * @access  public
 * @param int $region_id 上级地区id
 * @return  void
 */
function area_warehouse_list($region_id = 0)
{
    $res = RegionWarehouse::where('parent_id', $region_id);
    $res = $res->orderBy('region_id');

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $area_arr = [];
    if ($res) {
        foreach ($res as $i => $row) {
            $row['type'] = ($row['region_type'] == 0) ? $GLOBALS['_LANG']['country'] : '';
            $row['type'] .= ($row['region_type'] == 1) ? $GLOBALS['_LANG']['province'] : '';
            $row['type'] .= ($row['region_type'] == 2) ? $GLOBALS['_LANG']['city'] : '';
            $row['type'] .= ($row['region_type'] == 3) ? $GLOBALS['_LANG']['cantonal'] : '';

            $area_arr[$i]['region_code'] = isset($row['region_code']) ? $row['region_code'] : '';
            $area_arr[$i]['region_id'] = $row['region_id'];
            $area_arr[$i]['regionId'] = $row['regionId'];
            $area_arr[$i]['parent_id'] = $row['parent_id'];
            $area_arr[$i]['region_name'] = $row['region_name'];
            $area_arr[$i]['region_type'] = $row['region_type'];
            $area_arr[$i]['agency_id'] = $row['agency_id'];
            $area_arr[$i]['type'] = $row['type'];
        }
    }

    return $area_arr;
}

//获取配送方式列表
function warehouse_shipping_list($goods = [], $region_id = 0, $number = 1, $goods_region = [])
{
    $res = Shipping::whereRaw(1);
    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    if ($res) {
        foreach ($res as $key => $row) {
            if (substr($row['shipping_code'], 0, 5) == 'ship_') {
                unset($arr[$key]);
                continue;
            } else {
                $arr[$key]['shipping_id'] = $row['shipping_id'];
                $arr[$key]['shipping_name'] = $row['shipping_name'];
                $arr[$key]['shipping_code'] = $row['shipping_code'];

                if ($region_id > 0) {
                    $goods['ru_id'] = $goods['user_id'];
                    $shipping = get_goods_freight($goods, $region_id, $goods_region, $number, $row['shipping_code']);
                    $arr[$key]['shipping_fee'] = price_format($shipping['shipping_fee'], false);
                }
            }
        }
    }

    return $arr;
}

//查询地区运费
function get_warehouse_freight_type($region_id = 0)
{
    $adminru = get_admin_ru_id();
    if ($adminru['ru_id'] > 0) {
        $ru_id = $adminru['ru_id'];
    } else {
        $ru_id = 0;
    }

    $res = WarehouseFreight::where('region_id', $region_id)
        ->where('user_id', $ru_id);
    $res = $res->with([
        'getShipping' => function ($query) {
            $query->select('shipping_id', 'shipping_name', 'support_cod', 'shipping_code');
        },
        'getRegionWarehouseRegId' => function ($query) {
            $query->select('region_id', 'region_name as region_name1');
        },
        'getRegionWarehousereg' => function ($query) {
            $query->select('regionId', 'region_name as region_name2');
        }
    ]);
    $res = $res->groupBy('shipping_id')
        ->orderBy('id');
    $res = app(BaseRepository::class)->getToArrayGet($res);

    if ($res) {
        foreach ($res as $key => $row) {
            $row = $row['get_shipping'] ? array_merge($row, $row['get_shipping']) : $row;
            $row = $row['get_region_warehouse_reg_id'] ? array_merge($row, $row['get_region_warehouse_reg_id']) : $row;
            $row = $row['get_region_warehouse_reg'] ? array_merge($row, $row['get_region_warehouse_reg']) : $row;

            $res[$key] = $row;
        }
    }

    return $res;
}

//------root

//查询仓库
function get_warehouse_list_goods($region_type = 0)
{
    $res = RegionWarehouse::where('region_type', $region_type);
    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    if ($res) {
        foreach ($res as $key => $row) {
            $arr[$key]['region_id'] = $row['region_id'];
            $arr[$key]['region_name'] = $row['region_name'];
        }
    }

    return $arr;
}

//获取仓库名称或者ID
function get_warehouse_name_id($region_id = 0, $region_name = '')
{
    if (!empty($region_name)) {
        return RegionWarehouse::where('region_name', $region_name)->where('region_type', $region_id)->value('region_id');
    } else {
        return RegionWarehouse::where('region_id', $region_id)->value('region_name');
    }
}

//查询地区名称
function get_region_info($region_id)
{
    $res = Region::select('region_id', 'region_name', 'parent_id')->where('region_id', $region_id);
    $res = app(BaseRepository::class)->getToArrayFirst($res);

    return $res;
}

//查询会员的收货地址
function get_user_address_region($user_id)
{
    $res = UserAddress::where('user_id', $user_id);
    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    if ($res) {
        $region_address = '';
        foreach ($res as $key => $row) {
            $arr[$key]['address_id'] = $row['address_id'];
            $arr[$key]['province'] = $row['province'];
            $arr[$key]['city'] = $row['city'];
            $arr[$key]['district'] = $row['district'];

            $region_address .= $row['province'] . "," . $row['city'] . "," . $row['district'] . ",";
        }
        $arr['region_address'] = substr($region_address, 0, -1);
    }

    return $arr;
}

//查询用户订单
function get_user_order_area($user_id = 0)
{
    $row = OrderInfo::select('country', 'province', 'city', 'district')
        ->where('user_id', $user_id)
        ->orderBy('order_id', 'desc');

    $row = app(BaseRepository::class)->getToArrayFirst($row);

    return $row;
}

function get_user_area_reg($user_id)
{
    $row = UsersType::where('user_id', $user_id);
    $row = app(BaseRepository::class)->getToArrayFirst($row);

    return $row;
}

function get_province_id_warehouse($province_id)
{
    return RegionWarehouse::where('regionId', $province_id)->value('parent_id');
}

//查询地区region_id
function get_region_name_goods($region_type = 1, $region_name = '')
{
    $region_id = Region::where('region_name', $region_name)->where('region_type', $region_type)->value('region_id');
    return $region_id;
}

//查询子地区是否存在，有1个或者N个
function get_region_child_num($id = 0)
{
    $count = Region::select('region_id')->where('parent_id', $id)->count();
    return $count;
}

//查询配送地区所属仓库
function get_warehouse_goods_region($province_id)
{
    $res = RegionWarehouse::where('regionId', $province_id)
        ->with([
            'getRegionWarehouse' => function ($query) {
                $query->select('region_id', 'region_name');
            }
        ]);
    $res = app(BaseRepository::class)->getToArrayFirst($res);

    $warehouse = $res ? $res['get_region_warehouse'] : [];

    return $warehouse;
}

//查询商品的默认配送方式运费金额
function get_goods_freight($goods, $warehouse_id = 0, $goods_region = [], $buy_number = 1, $shipping_code)
{
    $shipping = Shipping::where('shipping_code', $shipping_code);
    $shipping = app(BaseRepository::class)->getToArrayFirst($shipping);

    $arr = [];
    if (empty($shipping)) {
        $arr['item_fee'] = price_format(0); /* 单件商品的配送价格（默认） */
        $arr['base_fee'] = price_format(0); /* N(500或1000克)克以内的价格 */
        $arr['step_fee'] = price_format(0); /* 续重每N(500或1000克)克增加的价格 */
        $arr['free_money'] = price_format(0); //免费额度
        $arr['pay_fee'] = price_format(0); //货到付款支付费用

        $arr['shipping_fee'] = 0;
        $arr['configure_price'] = price_format(0);
        $arr['shipping_name'] = '';
        $arr['shipping_code'] = $shipping_code;
        $arr['warehouse_id'] = 0;

        return $arr;
    }

    /* 商品单独设置运费价格 start */
    $goods_transport = [];
    if ($goods['goods_transport']) {
        $goods['goods_transport'] = substr($goods['goods_transport'], 0, -1);
        $goods['goods_transport'] = explode("-", $goods['goods_transport']);
        if ($goods['goods_transport']) {
            foreach ($goods['goods_transport'] as $key => $row) {
                $transport = explode("|", $row);

                $goods_transport[$key]['goods_id'] = $transport[0];
                $goods_transport[$key]['ru_id'] = $transport[1];
                $goods_transport[$key]['tid'] = $transport[2];
                $goods_transport[$key]['freight'] = $transport[3];
                $goods_transport[$key]['shipping_fee'] = $transport[4];
                $goods_transport[$key]['goods_number'] = $transport[5];
                $goods_transport[$key]['goods_weight'] = $transport[6];
                $goods_transport[$key]['shop_price'] = $transport[7];
            }
        }

        $order_transport = app(OrderTransportService::class)->getOrderTransport($goods_transport, $goods_region, $shipping['shipping_id'], $shipping_code);
    }
    /* 商品单独设置运费价格 end */

    if ($goods['goods_price']) {
        $street_configure = get_goods_freight_configure($goods, $warehouse_id, $goods_region['street'], $shipping_code);
        $district_configure = get_goods_freight_configure($goods, $warehouse_id, $goods_region['district'], $shipping_code);
        $city_configure = get_goods_freight_configure($goods, $warehouse_id, $goods_region['city'], $shipping_code);
        $province_configure = get_goods_freight_configure($goods, $warehouse_id, $goods_region['province'], $shipping_code);
        $default_configure = get_goods_default_configure($goods, $warehouse_id, $goods_region, $shipping_code); //by wu

        if ($street_configure) {
            $configure = $street_configure;
        } elseif (!empty($district_configure)) {
            $configure = $district_configure;
        } elseif (!empty($city_configure)) {
            $configure = $city_configure;
        } elseif (!empty($province_configure)) {
            $configure = $province_configure;
        } else {
            $configure = $default_configure;
        }

        $goods['number'] = empty($goods['number']) ? $buy_number : $goods['number'];

        $shipping_cfg = sc_unserialize_config($configure);
        $configure_price = goods_shipping_fee($shipping_code, unserialize($configure), $goods['weight'], $goods['goods_price'], $goods['number']);

        $arr['shipping_fee'] = $configure_price;
        $arr['configure_price'] = price_format($configure_price, false);
        $arr['shipping_name'] = $shipping['shipping_name'];
        $arr['shipping_code'] = $shipping['shipping_code'];

        $arr['item_fee'] = price_format($shipping_cfg['item_fee'], false); /* 单件商品的配送价格（默认） */
        $arr['base_fee'] = price_format($shipping_cfg['base_fee'], false); /* N(500或1000克)克以内的价格 */
        $arr['step_fee'] = price_format($shipping_cfg['step_fee'], false); /* 续重每N(500或1000克)克增加的价格 */
        $arr['free_money'] = price_format($shipping_cfg['free_money'], false); //免费额度
        $arr['fee_compute_mode'] = $shipping_cfg['fee_compute_mode']; //费用计算方式
        $arr['pay_fee'] = isset($shipping_cfg['pay_fee']) ? price_format($shipping_cfg['pay_fee'], false) : price_format(0); //货到付款支付费用
    } else {
        $arr['shipping_fee'] = 0;
    }

    if ($order_transport['freight']) {
        $arr['shipping_fee'] += $order_transport['sprice']; /* 有配送按配送区域计算运费 */
    } else {
        $arr['shipping_fee'] = $order_transport['sprice'];
    }

    $arr['configure_price'] = price_format($configure_price, false);
    $arr['shipping_name'] = $shipping['shipping_name'];
    $arr['shipping_code'] = $shipping['shipping_code'];
    $arr['warehouse_id'] = $warehouse_id;

    return $arr;
}

//查询商品设置配送地区运费数据
function get_goods_freight_configure($goods = [], $warehouse_id = 0, $region_id = 0, $shipping_code = '')
{
    $shipping_id = Shipping::where('shipping_code', $shipping_code)->value('shipping_id');

    $configure = '';
    if ($shipping_id) {
        $configure = WarehouseFreight::where('user_id', $goods['ru_id'])
            ->where('warehouse_id', $warehouse_id)
            ->where('shipping_id', $shipping_id)
            ->where('region_id', $region_id)
            ->value('configure');
    }

    return $configure;
}

//查询模板商品设置配送地区运费数据 by wu
function get_goods_default_configure($goods, $warehouse_id, $region_id, $shipping_code)
{
    $shipping_id = Shipping::where('shipping_code', $shipping_code)->value('shipping_id');

    $res = WarehouseFreightTpl::where('user_id', $goods['ru_id'])
        ->where('shipping_id', $shipping_id);
    $res = app(BaseRepository::class)->getToArrayGet($res);

    if ($res) {
        foreach ($res as $tpl) {
            $tpl_status_1 = array_intersect($region_id, explode(',', $tpl['region_id']));
            $tpl_status_2 = in_array($warehouse_id, explode(',', $tpl['warehouse_id']));
            if ($tpl_status_1 && $tpl_status_2) {
                return $tpl['configure'];
            }
        }
    } else {
        return '';
    }
}

//获取仓库运费模板列表 by wu
function get_ship_tpl_list($shipping_id = 0, $ru_id = 0)
{
    $res = WarehouseFreightTpl::where('user_id', $ru_id)
        ->where('shipping_id', $shipping_id);
    $res = app(BaseRepository::class)->getToArrayGet($res);

    //配送区域、仓库列表
    if ($res) {
        foreach ($res as $key => $value) {
            //配送区域
            if (!empty($value['region_id'])) {
                $region_id = app(BaseRepository::class)->getExplode($value['region_id']);
                $regions = Region::whereIn('region_id', $region_id);
                $regions = app(BaseRepository::class)->getToArrayGet($regions);
                $regions = app(BaseRepository::class)->getKeyPluck($regions, 'region_name');

                $res[$key]['regions'] = implode(',', $regions);
            }

            //仓库列表
            if (!empty($value['warehouse_id'])) {
                $warehouse_id = app(BaseRepository::class)->getExplode($value['warehouse_id']);
                $warehouses = RegionWarehouse::whereIn('region_id', $warehouse_id);
                $warehouses = app(BaseRepository::class)->getToArrayGet($warehouses);
                $warehouses = app(BaseRepository::class)->getKeyPluck($warehouses, 'region_name');

                $res[$key]['warehouses'] = implode(' | ', $warehouses);
            }
        }
    }

    return $res;
}

//获取仓库数组
function get_warehouse_list($type = 0)
{
    $res = RegionWarehouse::where('region_type', $type);
    $res = app(BaseRepository::class)->getToArrayGet($res);

    return $res;
}

//批量添加商品仓库库存
function get_insert_warehouse_goods($goods_id = 0, $warehouse_name = [], $warehouse_number = [], $warehouse_price = [], $warehouse_promote_price = [], $user_id = 0)
{
    $add_time = gmtime();
    for ($i = 0; $i < count($warehouse_name); $i++) {
        if (!empty($warehouse_name[$i])) {
            if ($warehouse_number[$i] == 0) {
                $warehouse_number[$i] = 1;
            }

            $w_id = WarehouseGoods::where('goods_id', $goods_id)
                ->where('region_id', $warehouse_name[$i])
                ->value('w_id');

            $parent = [
                'goods_id' => $goods_id,
                'region_id' => $warehouse_name[$i],
                'region_number' => intval($warehouse_number[$i]),
                'warehouse_price' => floatval($warehouse_price[$i]),
                'warehouse_promote_price' => floatval($warehouse_promote_price[$i]),
                'user_id' => $user_id,
                'add_time' => $add_time
            ];

            if ($w_id > 0) {
                $link[] = ['text' => lang('common.back_up_page'), 'href' => 'goods.php?act=edit&goods_id=' . $goods_id . '&extension_code='];
                return sys_msg(lang('common.warehouse_goods_exits'), 0, $link);
                break;
            } else {
                WarehouseGoods::insert($parent);
            }
        }
    }
}

//批量添加商品地区价格
function get_insert_warehouse_area_goods($goods_id = 0, $area_name = [], $region_number = [], $region_price = [], $region_promote_price = [], $user_id = 0)
{
    $add_time = gmtime();
    for ($i = 0; $i < count($area_name); $i++) {
        if (!empty($area_name[$i])) {
            $a_id = WarehouseAreaGoods::where('goods_id', $goods_id)
                ->where('region_id', $area_name[$i])
                ->value('a_id');

            if ($a_id > 0) {
                $link[] = ['text' => lang('common.back_up_page'), 'href' => 'goods.php?act=edit&goods_id=' . $goods_id . '&extension_code='];
                return sys_msg(lang('common.area_goods_exits'), 0, $link);
                break;
            } else {
                $parent = [
                    'goods_id' => $goods_id,
                    'region_id' => $area_name[$i],
                    'region_number' => $region_number[$i],
                    'region_price' => floatval($region_price[$i]),
                    'region_promote_price' => floatval($region_promote_price[$i]),
                    'user_id' => $user_id,
                    'add_time' => $add_time
                ];
                WarehouseAreaGoods::insert($parent);
            }
        }
    }
}

//查询仓库列表
function get_warehouse_goods_list($goods_id = 0)
{
    $res = WarehouseGoods::where('goods_id', $goods_id);
    $res = $res->with([
        'getRegionWarehouse' => function ($query) {
            $query->select('region_id', 'region_name', 'parent_id');
        }
    ]);

    $res = $res->orderBy('region_id');

    $res = app(BaseRepository::class)->getToArrayGet($res);

    if ($res) {
        foreach ($res as $key => $row) {
            $row = $row['get_region_warehouse'] ? array_merge($row, $row['get_region_warehouse']) : $row;

            $res[$key] = $row;
        }
    }

    return $res;
}

//查询仓库列表
function get_warehouse_area_goods_list($goods_id = 0)
{
    $res = WarehouseAreaGoods::where('goods_id', $goods_id);

    if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
        $res = $res->where('city_id', '>', 0);
    } else {
        $res = $res->where('city_id', 0);
    }

    $res = $res->with([
        'getRegionWarehouse' => function ($query) {
            $query->select('region_id', 'region_name', 'parent_id');
        }
    ]);

    $res = $res->orderByRaw('region_id, region_sort asc');

    $res = app(BaseRepository::class)->getToArrayGet($res);

    if ($res) {
        foreach ($res as $key => $row) {
            $row = $row['get_region_warehouse'] ? array_merge($row, $row['get_region_warehouse']) : $row;

            if (isset($row['parent_id']) && $row['parent_id']) {
                $region_name = RegionWarehouse::where('region_id', $row['parent_id'])->value('region_name');
            } else {
                $region_name = '';
            }

            $row['warehouse_name'] = $region_name;
            $row['city_name'] = RegionWarehouse::where('region_id', $row['city_id'])->value('region_name');

            $res[$key] = $row;
        }
    }

    return $res;
}

//批量添加货号 start
function get_produts_warehouse_list($goods_list)
{
    $arr = [];
    for ($i = 0; $i < count($goods_list); $i++) {
        $goods_where = [
            'name' => $goods_list[$i]['goods_name']
        ];

        $warehouse_where = [
            'name' => $goods_list[$i]['warehouse_id']
        ];

        $arr[$i]['goods_id'] = get_products_name($goods_where, 'goods');
        $arr[$i]['warehouse_id'] = get_products_name($warehouse_where, 'region_warehouse');
        $arr[$i]['goods_attr'] = $goods_list[$i]['goods_attr'];
        $arr[$i]['product_sn'] = $goods_list[$i]['product_sn'];
        $arr[$i]['product_number'] = $goods_list[$i]['product_number'];
    }

    return $arr;
}

/**
 * 批量添加货号(仓库模式)
 *
 * @param array $goods_list 数据列表
 * @param int $attr_num 属性个数
 * @return array 添加货号后属性
 */
function get_produts_warehouse_list2($goods_list = [], $attr_num = 0)
{
    $arr = [];
    for ($i = 0; $i < count($goods_list); $i++) {
        $goods_where = [
            'sn_name' => $goods_list[$i]['goods_sn']
        ];

        $warehouse_where = [
            'name' => $goods_list[$i]['warehouse_id']
        ];

        $arr[$i]['goods_id'] = get_products_name($goods_where, 'goods');
        $arr[$i]['warehouse_id'] = get_products_name($warehouse_where, 'region_warehouse');
        for ($j = 0; $j < $attr_num; $j++) {
            $attr_info = explode("-", $goods_list[$i]['goods_attr' . $j]);

            if (substr_count($goods_list[$i]['goods_attr' . $j], "-") > 1) {
                $attr_info[0] = $attr_info[0] . "-" . $attr_info[1];
                $attr_info[1] = $attr_info[count($attr_info) - 1];
                unset($attr_info[count($attr_info) - 1]);
            }

            $attr_value = isset($attr_info[0]) ? $attr_info[0] : '';
            $attr_id = isset($attr_info[1]) ? $attr_info[1] : 0;

            $where_select = [
                'goods_id' => $arr[$i]['goods_id'],
                'attr_id' => $attr_id,
                'attr_value' => $attr_value,
            ];

            if (empty($arr[$i]['goods_id'])) {
                $admin_id = get_admin_id();
                $where_select['admin_id'] = $admin_id;
            }

            $goods_attr_info = app(GoodsAttrService::class)->getGoodsAttrId($where_select, 1, 1);
            $goods_attr_id = $goods_attr_info['goods_attr_id'] ?? 0;

            if ($j == $attr_num - 1) {
                $attr_name[$j] = $goods_list[$i]['goods_attr' . $j]; //属性名称 bylu;
                $attr[$j] = $goods_attr_id; //属性id bylu;
            } else {
                $attr_name[$j] = $goods_list[$i]['goods_attr' . $j] . '|'; //属性名称 bylu;
                $attr[$j] = $goods_attr_id . '|'; //属性id bylu;
            }
        }
        $arr[$i]['goods_attr'] = implode('', $attr); //拼凑属性ID;
        $arr[$i]['goods_attr_name'] = implode('', $attr_name); //拼凑属性名称;

        if ($GLOBALS['_CFG']['goods_attr_price'] == 1) {
            if ($GLOBALS['_CFG']['add_shop_price'] == 0) {
                $arr[$i]['product_market_price'] = $goods_list[$i]['product_market_price'];
            }
            $arr[$i]['product_price'] = $goods_list[$i]['product_price'];
            if ($GLOBALS['_CFG']['add_shop_price'] == 0) {
                $arr[$i]['product_promote_price'] = $goods_list[$i]['product_promote_price'];
            }
        }

        $arr[$i]['product_number'] = $goods_list[$i]['product_number'];
        $arr[$i]['min_quantity'] = $goods_list[$i]['min_quantity'];
        $arr[$i]['product_warn_number'] = $goods_list[$i]['product_warn_number'];

        //如果货品编号为空,自动生成货品编号;
        if (empty($goods_list[$i]['product_sn'])) {
            $arr[$i]['product_sn'] = $goods_list[$i]['goods_sn'] . 'g_p' . $i;
        } else {
            $arr[$i]['product_sn'] = $goods_list[$i]['product_sn'];
        }

        $arr[$i]['bar_code'] = $goods_list[$i]['bar_code'] ?? '';
    }

    return $arr;
}

/**
 * 批量添加货号(默认模式) bylu
 * @param $goods_list 数据列表
 * @param $attr_nums 属性个数
 * @return array 添加货号后属性
 */
function get_produts_list2($goods_list, $attr_num = 0)
{
    $arr = [];
    for ($i = 0; $i < count($goods_list); $i++) {
        $goods_where = [
            'id' => $goods_list[$i]['goods_id'],
            'name' => $goods_list[$i]['goods_name'],
            'sn_name' => $goods_list[$i]['goods_sn'],
            'seller_id' => $goods_list[$i]['seller_id'],
        ];

        $arr[$i]['goods_id'] = get_products_name($goods_where, 'goods');
        $arr[$i]['warehouse_id'] = 0;

        for ($j = 0; $j < $attr_num; $j++) {
            $attr_info = explode("-", $goods_list[$i]['goods_attr' . $j]);

            if (substr_count($goods_list[$i]['goods_attr' . $j], "-") > 1) {
                $attr_info[0] = $attr_info[0] . "-" . $attr_info[1];
                $attr_info[1] = $attr_info[count($attr_info) - 1];
                unset($attr_info[count($attr_info) - 1]);
            }

            $attr_value = isset($attr_info[0]) ? $attr_info[0] : '';
            $attr_id = isset($attr_info[1]) ? $attr_info[1] : 0;

            $where_select = [
                'goods_id' => $arr[$i]['goods_id'],
                'attr_id' => $attr_id,
                'attr_value' => $attr_value,
            ];

            if (empty($arr[$i]['goods_id'])) {
                $admin_id = get_admin_id();
                $where_select['admin_id'] = $admin_id;
            }

            $goods_attr_info = app(GoodsAttrService::class)->getGoodsAttrId($where_select, 1, 1);
            $goods_attr_id = $goods_attr_info['goods_attr_id'] ?? 0;

            if ($j == $attr_num - 1) {
                $attr_name[$j] = $attr_value; //属性名称 bylu;
                $attr[$j] = $goods_attr_id; //属性id bylu;
            } else {
                $attr_name[$j] = !empty($attr_value) ? $attr_value . '|' : ''; //属性名称 bylu;
                $attr[$j] = !empty($goods_attr_id) ? $goods_attr_id . '|' : ''; //属性id bylu;
            }
        }

        $arr[$i]['goods_attr'] = implode('', $attr);//拼凑属性ID;
        $arr[$i]['goods_attr_name'] = implode('', $attr_name);//拼凑属性名称;

        if ($GLOBALS['_CFG']['goods_attr_price'] == 1) {
            if ($GLOBALS['_CFG']['add_shop_price'] == 0) {
                $arr[$i]['product_market_price'] = $goods_list[$i]['product_market_price'];
            }
            $arr[$i]['product_price'] = $goods_list[$i]['product_price'];

            if ($GLOBALS['_CFG']['add_shop_price'] == 0) {
                $arr[$i]['product_promote_price'] = $goods_list[$i]['product_promote_price'];
            }
        }

        $arr[$i]['product_number'] = $goods_list[$i]['product_number'];
        $arr[$i]['product_warn_number'] = $goods_list[$i]['product_warn_number'];

        //如果货品编号为空,自动生成货品编号;
        if (empty($goods_list[$i]['product_sn'])) {
            $arr[$i]['product_sn'] = $goods_list[$i]['goods_sn'] . 'g_p' . $i;
        } else {
            $arr[$i]['product_sn'] = $goods_list[$i]['product_sn'];
        }

        $arr[$i]['bar_code'] = $goods_list[$i]['bar_code'];
    }

    return $arr;
}

/**
 * 批量添加货号(地区模式) bylu
 * @param $goods_list 数据列表
 * @param $attr_nums 属性个数
 * @return array 添加货号后属性
 */
function get_produts_area_list2($goods_list, $attr_num = 0)
{
    $arr = [];
    for ($i = 0; $i < count($goods_list); $i++) {
        $goods_where = [
            'sn_name' => $goods_list[$i]['goods_sn']
        ];

        $warehouse_where = [
            'name' => $goods_list[$i]['area_id']
        ];

        $arr[$i]['goods_id'] = get_products_name($goods_where, 'goods');
        $arr[$i]['area_id'] = get_products_name($warehouse_where, 'region_warehouse');

        $city_where = [
            'name' => $goods_list[$i]['city_id']
        ];

        $arr[$i]['city_id'] = get_products_name($city_where, 'region_warehouse', 2);

        for ($j = 0; $j < $attr_num; $j++) {
            $attr_info = explode("-", $goods_list[$i]['goods_attr' . $j]);

            if (substr_count($goods_list[$i]['goods_attr' . $j], "-") > 1) {
                $attr_info[0] = $attr_info[0] . "-" . $attr_info[1];
                $attr_info[1] = $attr_info[count($attr_info) - 1];
                unset($attr_info[count($attr_info) - 1]);
            }

            $attr_value = isset($attr_info[0]) ? $attr_info[0] : '';
            $attr_id = isset($attr_info[1]) ? $attr_info[1] : 0;

            $where_select = [
                'goods_id' => $arr[$i]['goods_id'],
                'attr_id' => $attr_id,
                'attr_value' => $attr_value,
            ];

            if (empty($arr[$i]['goods_id'])) {
                $admin_id = get_admin_id();
                $where_select['admin_id'] = $admin_id;
            }

            $goods_attr_info = app(GoodsAttrService::class)->getGoodsAttrId($where_select, 1, 1);
            $goods_attr_id = $goods_attr_info['goods_attr_id'] ?? 0;

            if ($j == $attr_num - 1) {
                $attr_name[$j] = $goods_list[$i]['goods_attr' . $j]; //属性名称 bylu;
                $attr[$j] = $goods_attr_id; //属性id bylu;
            } else {
                $attr_name[$j] = $goods_list[$i]['goods_attr' . $j] . '|'; //属性名称 bylu;
                $attr[$j] = $goods_attr_id . '|'; //属性id bylu;
            }
        }
        $arr[$i]['goods_attr'] = implode('', $attr); //拼凑属性ID;
        $arr[$i]['goods_attr_name'] = implode('', $attr_name); //拼凑属性名称;

        if ($GLOBALS['_CFG']['goods_attr_price'] == 1) {
            $arr[$i]['product_market_price'] = $goods_list[$i]['product_market_price'];
            $arr[$i]['product_price'] = $goods_list[$i]['product_price'];
            $arr[$i]['product_promote_price'] = $goods_list[$i]['product_promote_price'];
        }

        $arr[$i]['product_number'] = $goods_list[$i]['product_number'];
        $arr[$i]['min_quantity'] = $goods_list[$i]['min_quantity'];
        $arr[$i]['product_warn_number'] = $goods_list[$i]['product_warn_number'];

        //如果货品编号为空,自动生成货品编号;
        if (empty($goods_list[$i]['product_sn'])) {
            $arr[$i]['product_sn'] = $goods_list[$i]['goods_sn'] . 'g_p' . $i;
        } else {
            $arr[$i]['product_sn'] = $goods_list[$i]['product_sn'];
        }

        $arr[$i]['bar_code'] = $goods_list[$i]['bar_code'] ?? '';
    }

    return $arr;
}

function get_produts_warehouse_attr_list($goods_attr = '', $goods_id = 0)
{
    $arr = [
        'goods_attr' => ''
    ];

    if ($goods_attr) {
        $goods_attr = explode(',', $goods_attr);

        for ($i = 0; $i < count($goods_attr); $i++) {
            $row = GoodsAttr::where('goods_id')
                ->where('attr_value', $goods_attr[$i]);
            $row = app(BaseRepository::class)->getToArrayFirst($row);

            $arr[$i]['goods_attr_id'] = $row['goods_attr_id'];
            $arr[$i]['attr_value'] = $row['attr_value'];
        }

        $goods_attr_id = app(BaseRepository::class)->getKeyPluck($arr, 'goods_attr_id');
        $arr['goods_attr'] = app(BaseRepository::class)->getImplode($goods_attr_id, ['replace' => '|']);
    }

    return $arr;
}

//查找商品ID
function get_products_name($where = [], $table = '', $type = 0)
{
    if ($table === 'goods') {
        $res = Goods::where('is_delete', 0);

        if (isset($where['name'])) {
            $res = $res->where('goods_name', $where['name']);
        }

        if (isset($where['sn_name'])) {
            $res = $res->where('goods_sn', $where['sn_name']);
        }

        if (isset($where['seller_id'])) {
            $res = $res->where('user_id', $where['seller_id']);
        }

        $id = $res->value('goods_id');
    } else {
        $res = RegionWarehouse::whereRaw(1);

        if (isset($where['name'])) {
            $res = $res->where('region_name', $where['name']);

            if ($type > 0) {
                $res = $res->where('region_type', $type);
            }
        }

        $id = $res->value('region_id');
    }

    if (isset($where['id']) && !empty($where['id'])) {
        return $where['id'];
    } else {
        return $id;
    }
}

//批量添加货号 end

//批量添加商品仓库 start
function get_goods_bacth_warehouse_list($goods_list)
{
    $arr = [];
    if ($goods_list) {
        for ($i = 0; $i < count($goods_list); $i++) {

            $goods_id = $goods_list[$i]['goods_id'] ?? 0;

            if (empty($goods_id)) {
                $goods_id = Goods::where('goods_name', $goods_list[$i]['goods_name'])->value('goods_id');
                $ru_id = Goods::where('goods_id', $goods_id)->value('user_id');
            } else {
                $ru_id = Goods::where('goods_id', $goods_id)->value('user_id');
            }

            $arr[$i]['goods_id'] = $goods_id ? $goods_id : 0;
            $arr[$i]['user_id'] = $ru_id ? $ru_id : 0;

            $region_id = RegionWarehouse::where('region_name', $goods_list[$i]['warehouse_name'])->value('region_id');
            $arr[$i]['warehouse_id'] = $region_id ? $region_id : 0;

            $arr[$i]['region_number'] = $goods_list[$i]['warehouse_number'] ? $goods_list[$i]['warehouse_number'] : 0;
            $arr[$i]['warehouse_price'] = $goods_list[$i]['warehouse_price'] ? $goods_list[$i]['warehouse_price'] : 0;
            $arr[$i]['warehouse_promote_price'] = $goods_list[$i]['warehouse_promote_price'] ? $goods_list[$i]['warehouse_promote_price'] : 0;
            $arr[$i]['give_integral'] = $goods_list[$i]['give_integral'] ? $goods_list[$i]['give_integral'] : 0;
            $arr[$i]['rank_integral'] = $goods_list[$i]['rank_integral'] ? $goods_list[$i]['rank_integral'] : 0;
            $arr[$i]['pay_integral'] = $goods_list[$i]['pay_integral'] ? $goods_list[$i]['pay_integral'] : 0;
            $arr[$i]['add_time'] = gmtime();
        }
    }

    return $arr;
}

function get_insert_bacth_warehouse($goods_list)
{
    $return = 0;
    if ($goods_list) {
        for ($i = 0; $i < count($goods_list); $i++) {
            if ($goods_list[$i]['goods_id'] > 0) {
                if (empty($goods_list[$i]['warehouse_price'])) {
                    $goods_list[$i]['warehouse_price'] = 0;
                }

                if (empty($goods_list[$i]['warehouse_promote_price'])) {
                    $goods_list[$i]['warehouse_promote_price'] = 0;
                }

                $other['user_id'] = intval($goods_list[$i]['user_id']);
                $other['goods_id'] = intval($goods_list[$i]['goods_id']);
                $other['region_id'] = intval($goods_list[$i]['warehouse_id']);
                $other['region_number'] = intval($goods_list[$i]['region_number']);
                $other['warehouse_price'] = floatval($goods_list[$i]['warehouse_price']);
                $other['warehouse_promote_price'] = floatval($goods_list[$i]['warehouse_promote_price']);
                $other['give_integral'] = intval($goods_list[$i]['give_integral']);
                $other['rank_integral'] = intval($goods_list[$i]['rank_integral']);
                $other['pay_integral'] = intval($goods_list[$i]['pay_integral']);
                $other['add_time'] = $goods_list[$i]['add_time'];

                $count = WarehouseGoods::where('user_id', $other['user_id'])
                    ->where('goods_id', $other['goods_id'])
                    ->where('region_id', $other['region_id'])
                    ->count();

                if ($count > 0) {
                    $return = 1;

                    WarehouseGoods::where('user_id', $other['user_id'])
                        ->where('goods_id', $other['goods_id'])
                        ->where('region_id', $other['region_id'])
                        ->update($other);
                } else {
                    WarehouseGoods::insert($other);
                }
            }
        }
    }

    return $return;
}

//批量添加商品仓库 end

//批量添加商品地区 start
function get_goods_bacth_area_list($goods_list)
{
    $arr = [];
    for ($i = 0; $i < count($goods_list); $i++) {

        $goods_id = $goods_list[$i]['goods_id'] ?? 0;

        if (empty($goods_id)) {
            $goods_id = Goods::where('goods_name', $goods_list[$i]['goods_name'])->value('goods_id');
            $goods_id = $goods_id ? $goods_id : 0;
            $ru_id = Goods::where('goods_id', $goods_id)->value('user_id');
        } else {
            $ru_id = Goods::where('goods_id', $goods_id)->value('user_id');
        }

        $arr[$i]['goods_id'] = $goods_id ? $goods_id : 0;
        $arr[$i]['user_id'] = $ru_id ? $ru_id : 0;

        $warehouse_id = RegionWarehouse::where('region_name', $goods_list[$i]['warehouse_name'])
            ->where('region_type', 0)
            ->value('region_id');
        $arr[$i]['warehouse_id'] = $warehouse_id ? $warehouse_id : 0;

        $region_id = RegionWarehouse::where('region_name', $goods_list[$i]['area_name'])
            ->where('region_type', 1)
            ->value('region_id');
        $arr[$i]['area_id'] = $region_id ? $region_id : 0;

        if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
            $city_id = RegionWarehouse::where('region_name', $goods_list[$i]['city_name'])
                ->where('region_type', 2)
                ->value('region_id');
            $arr[$i]['city_id'] = $city_id ? $city_id : 0;
        }

        $arr[$i]['region_number'] = $goods_list[$i]['region_number'] ? $goods_list[$i]['region_number'] : 0;
        $arr[$i]['region_price'] = $goods_list[$i]['region_price'] ? $goods_list[$i]['region_price'] : 0;
        $arr[$i]['region_promote_price'] = $goods_list[$i]['region_promote_price'] ? $goods_list[$i]['region_promote_price'] : 0;
        $arr[$i]['give_integral'] = $goods_list[$i]['give_integral'] ? $goods_list[$i]['give_integral'] : 0;
        $arr[$i]['rank_integral'] = $goods_list[$i]['rank_integral'] ? $goods_list[$i]['rank_integral'] : 0;
        $arr[$i]['pay_integral'] = $goods_list[$i]['pay_integral'] ? $goods_list[$i]['pay_integral'] : 0;
        $arr[$i]['region_sort'] = $goods_list[$i]['region_sort'] ? $goods_list[$i]['region_sort'] : 0;
        $arr[$i]['add_time'] = gmtime();
    }

    return $arr;
}

function get_insert_bacth_area($goods_list)
{
    $arr = [];
    for ($i = 0; $i < count($goods_list); $i++) {
        if ($goods_list[$i]['goods_id'] > 0) {
            if (empty($goods_list[$i]['region_price'])) {
                $goods_list[$i]['region_price'] = 0;
            }

            if (empty($goods_list[$i]['region_promote_price'])) {
                $goods_list[$i]['region_promote_price'] = 0;
            }

            $other['user_id'] = intval($goods_list[$i]['user_id']);
            $other['goods_id'] = intval($goods_list[$i]['goods_id']);
            $other['region_id'] = intval($goods_list[$i]['area_id']);

            if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
                $other['city_id'] = intval($goods_list[$i]['city_id']);
            }

            $other['region_number'] = intval($goods_list[$i]['region_number']);
            $other['region_price'] = floatval($goods_list[$i]['region_price']);
            $other['region_promote_price'] = floatval($goods_list[$i]['region_promote_price']);
            $other['give_integral'] = intval($goods_list[$i]['give_integral']);
            $other['rank_integral'] = intval($goods_list[$i]['rank_integral']);
            $other['pay_integral'] = intval($goods_list[$i]['pay_integral']);
            $other['add_time'] = $goods_list[$i]['add_time'];
            $other['region_sort'] = intval($goods_list[$i]['region_sort']);

            $count = WarehouseAreaGoods::where('user_id', $other['user_id'])
                ->where('goods_id', $other['goods_id']);

            if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
                $count = $count->where('region_id', $other['region_id'])
                    ->where('city_id', $other['city_id']);
            } else {
                $count = $count->where('region_id', $other['region_id']);
            }

            $count = $count->count();

            $arr['goods_id'] = $other['goods_id'];
            if ($count > 0) {
                $arr['return'] = 1;
                $return = $arr;

                $update = WarehouseAreaGoods::where('user_id', $other['user_id'])
                    ->where('goods_id', $other['goods_id']);

                if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
                    $update = $update->where('region_id', $other['region_id'])
                        ->where('city_id', $other['city_id']);
                } else {
                    $update = $update->where('region_id', $other['region_id']);
                }

                $update->update($other);
            } else {
                $arr['return'] = 0;
                $return = $arr;

                WarehouseAreaGoods::insert($other);
            }
        }
    }

    return $return;
}

//批量添加商品地区 end

//批量添加商品地区属性 start

/**
 * 商品属性单一模式
 * 商品属性列表
 * 更新价格
 */
function get_goods_bacth_warehouse_attr_list($goods_list)
{
    for ($i = 0; $i < count($goods_list); $i++) {
        if ($goods_list[$i]['attr_name']) {
            if (empty($goods_list[$i]['goods_id'])) {
                $goods_id = Goods::where('goods_name', $goods_list[$i]['goods_name'])
                    ->where('user_id', $goods_list[$i]['seller_id'])
                    ->value('goods_id');
            } else {
                $goods_id = $goods_list[$i]['goods_id'];
            }

            $arr[$i]['goods_id'] = !empty($goods_id) ? $goods_id : 0;
            $arr[$i]['goods_name'] = !empty($goods_list[$i]['goods_name']) ? $goods_list[$i]['goods_name'] : '';
            $arr[$i]['shop_name'] = $goods_list[$i]['shop_name'];
            $arr[$i]['region_name'] = $goods_list[$i]['region_name'];
            $arr[$i]['attr_name'] = $goods_list[$i]['attr_name'];
            $arr[$i]['warehouse_id'] = RegionWarehouse::where('region_name', $goods_list[$i]['region_name'])->value('region_id');
            $arr[$i]['goods_attr_id'] = GoodsAttr::where('attr_value', $goods_list[$i]['attr_name'])->where('goods_id', $goods_id)->value('goods_attr_id');
            $arr[$i]['attr_price'] = $goods_list[$i]['attr_price'];
        }
    }

    return $arr;
}

function get_goods_bacth_area_attr_list($goods_list)
{
    for ($i = 0; $i < count($goods_list); $i++) {
        if ($goods_list[$i]['attr_name']) {
            if (empty($goods_list[$i]['goods_id'])) {
                $goods_id = Goods::where('goods_name', $goods_list[$i]['goods_name'])
                    ->where('user_id', $goods_list[$i]['seller_id'])
                    ->value('goods_id');
            } else {
                $goods_id = $goods_list[$i]['goods_id'];
            }

            $arr[$i]['goods_id'] = !empty($goods_id) ? $goods_id : 0;
            $arr[$i]['goods_name'] = !empty($goods_list[$i]['goods_name']) ? $goods_list[$i]['goods_name'] : '';
            $arr[$i]['shop_name'] = $goods_list[$i]['shop_name'];
            $arr[$i]['region_name'] = $goods_list[$i]['region_name'];
            $arr[$i]['attr_name'] = $goods_list[$i]['attr_name'];
            $arr[$i]['area_id'] = RegionWarehouse::where('region_name', $goods_list[$i]['region_name'])->value('region_id');
            $arr[$i]['goods_attr_id'] = GoodsAttr::where('attr_value', $goods_list[$i]['attr_name'])->where('goods_id', $goods_id)->value('goods_attr_id');

            $arr[$i]['attr_price'] = $goods_list[$i]['attr_price'];
        }
    }

    return $arr;
}

function get_insert_bacth_area_attr($goods_list)
{
    $arr = [];
    for ($i = 0; $i < count($goods_list); $i++) {
        if ($goods_list[$i]['goods_id'] > 0) {
            if (empty($goods_list[$i]['attr_price'])) {
                $goods_list[$i]['attr_price'] = 0;
            }

            $other['goods_id'] = $goods_list[$i]['goods_id'];
            $other['area_id'] = $goods_list[$i]['area_id'];
            $other['goods_attr_id'] = $goods_list[$i]['goods_attr_id'];
            $other['attr_price'] = $goods_list[$i]['attr_price'];
            $other['attrNumber'] = $goods_list[$i]['attr_number'];

            $count = WarehouseAreaAttr::where('goods_id', $other['goods_id'])
                ->where('area_id', $other['area_id'])
                ->where('goods_attr_id', $other['goods_attr_id'])
                ->count();


            $arr['goods_id'] = $other['goods_id'];
            if ($count > 0) {
                $arr['return'] = 1;
                $return = $arr;

                WarehouseAreaAttr::where('goods_id', $other['goods_id'])
                    ->where('area_id', $other['area_id'])
                    ->where('goods_attr_id', $other['goods_attr_id'])
                    ->update($other);
            } else {
                $arr['return'] = 0;
                $return = $arr;

                WarehouseAreaAttr::insert($other);
            }
        }
    }

    return $return;
}

//批量添加商品地区属性 end

//计算会员下订单的商品总运费
function get_goods_order_shipping_fee($goods = [], $region = '', $shipping_id = 0)
{
    $arr = [];
    $arr['shipping_fee'] = 0;

    //订单总运费计算
    $cart_goods = get_warehouse_cart_goods_info($goods, 1, $region, $shipping_id);
    $arr['shipping_fee'] = $cart_goods['shipping']['shipping_fee'];
    $arr['ru_list'] = $cart_goods['ru_list'];
    return $arr;
}

//获取仓库共有多少个地区数量
function get_all_warehouse_area_count()
{
    $res = RegionWarehouse::where('parent_id', 0);
    $res = app(BaseRepository::class)->getToArrayGet($res);

    $count = 0;
    if ($res) {
        $region_id = app(BaseRepository::class)->getKeyPluck($res);

        if (!empty($region_id)) {
            $count = RegionWarehouse::whereIn('parent_id', $region_id)->count();
        }
    }


    return $count;
}

//查询仓库地区列表
function get_warehouse_area_list($warehouse_id = 0, $type = 0, $goods_id = 0, $ru_id = 0)
{
    $res = RegionWarehouse::where('parent_id', $warehouse_id);

    /* 不显示商家有商品地区 */
    if ($type) {
        $where = [
            'user_id' => $ru_id,
            'goods_id' => $goods_id
        ];

        if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
            $res = $res->with([
                'getWarehouseAreaGoodsCity' => function ($query) use ($where) {
                    $query->where('user_id', $where['user_id'])
                        ->where('goods_id', $where['goods_id']);
                }
            ]);
        } else {
            $res = $res->with([
                'getWarehouseAreaGoods' => function ($query) use ($where) {
                    $query->where('user_id', $where['user_id'])
                        ->where('goods_id', $where['goods_id']);
                }
            ]);
        }
    }

    $res = app(BaseRepository::class)->getToArrayGet($res);

    /* 不显示商家有商品地区 */
    if ($type) {
        foreach ($res as $key => $row) {

            if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
                $area_goods = $row['get_warehouse_area_goods_city'] ?? [];
            } else {
                $area_goods = $row['get_warehouse_area_goods'] ?? [];
            }

            $goods = $area_goods ? 1 : 0;

            if ($goods) {
                unset($res[$key]);
            }
        }
    }

    return $res;
}

//操作新数组attr_id --应用后台 start
function get_new_goods_attribute($goods_id, $_attribute = [])
{
    $arr = [];
    foreach ($_attribute as $key => $row) {
        $arr[$key] = $row;
        $arr[$key]['attr_valuesId'] = get_goods_attr_values_id($row['attr_values'], $row['goods_attr_id']);
        $arr[$key]['goods_attr'] = get_attribute_goods_attr($row['attr_id']);
        $arr[$key]['goods_attr'] = product_list($goods_id, '', $arr[$key]['goods_attr']['goods_attr_id']);
    }

    return $arr;
}

function get_attribute_goods_attr($attr_id = 0)
{
    $res = GoodsAttr::select('goods_attr_id')
        ->where('attr_id', $attr_id);

    $res = app(BaseRepository::class)->getToArrayGet($res);
    $goods_attr_id = app(BaseRepository::class)->getKeyPluck($res);
    $goods_attr_id = app(BaseRepository::class)->getImplode($res);

    $arr = [
        'goods_attr_id' => $goods_attr_id
    ];

    return $arr;
}

function get_goods_attr_values_id($attr_values = [], $goods_attr_id = [])
{
    $arr = [];
    for ($i = 0; $i < count($attr_values); $i++) {
        $arr[$i]['attr_value'] = $attr_values[$i];
        $arr[$i]['goods_attr_id'] = $goods_attr_id[$i];
    }

    return $arr;
}

//操作新数组attr_id --应用后台 end

//获取所有的仓库地区列表
function get_fine_warehouse_area_all($parent_id = 0, $goods_id = 0, $goods_attr_id = 0)
{
    $res = RegionWarehouse::where('parent_id', $parent_id);

    $where = [
        'goods_id' => $goods_id,
        'goods_attr_id' => $goods_attr_id
    ];

    $admin_id = get_admin_id();
    if (empty($goods_id)) {
        $where['admin_id'] = $admin_id;
    }

    $res = $res->with([
        'getWarehouseAreaAttr' => function ($query) use ($where) {
            $query = $query->where('goods_id', $where['goods_id'])
                ->where('goods_attr_id', $where['goods_attr_id']);

            if (empty($where['goods_id'])) {
                $query->where('admin_id', $where['admin_id']);
            }
        }
    ]);

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    if ($res) {
        foreach ($res as $key => $row) {
            $arr[$key]['region_id'] = $row['region_id'];
            $arr[$key]['region_name'] = $row['region_name'];

            if ($row['parent_id'] == 0) {
                $arr[$key]['child'] = get_fine_warehouse_area_all($row['region_id'], $goods_id, $goods_attr_id, $admin_id);
            }

            $arr[$key]['area_attr'] = $row['get_warehouse_area_attr'] ? $row['get_warehouse_area_attr'] : [];
        }
    }

    return $arr;
}

//获取所有的仓库地区列表
function get_fine_warehouse_all($parent_id = 0, $goods_id = 0, $goods_attr_id = 0, $admin_id = 0)
{
    $res = RegionWarehouse::select('region_id', 'region_name')
        ->where('parent_id', $parent_id);

    $where = [
        'goods_id' => $goods_id,
        'goods_attr_id' => $goods_attr_id
    ];

    if (empty($goods_id)) {
        if ($admin_id <= 0) {
            $admin_id = get_admin_id();
        }

        $where['admin_id'] = $admin_id;
    }

    $res = $res->with([
        'getWarehouseAreaAttr' => function ($query) use ($where) {
            $query = $query->select('id', 'area_id', 'attr_price')
                ->where('goods_id', $where['goods_id'])
                ->where('goods_attr_id', $where['goods_attr_id']);

            if (empty($where['goods_id'])) {
                $query->where('admin_id', $where['admin_id']);
            }
        }
    ]);

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    if ($res) {
        foreach ($res as $key => $row) {
            $row = $row['get_warehouse_area_attr'] ? $row['get_warehouse_area_attr'] : $row;

            $arr[$key]['goods_attr_id'] = $goods_attr_id;
            $arr[$key]['region_id'] = $row['region_id'];
            $arr[$key]['region_name'] = isset($row['region_name']) ? $row['region_name'] : '';
            $arr[$key]['attr_price'] = isset($row['attr_price']) ? $row['attr_price'] : 0;
            $arr[$key]['id'] = $row['id'];
        }
    }

    return $arr;
}

//仓库 end

//订单分主订单和从订单 start

//获取主订单信息
function get_main_order_info($order_id = 0, $type = 0)
{
    $row = OrderInfo::where('order_id', $order_id);
    $row = app(BaseRepository::class)->getToArrayFirst($row);

    if ($row) {
        if ($type == 1) {


            $row['all_ruId'] = get_main_order_goods_info($order_id, 1); //订单中所有商品所属商家ID,0代表自营商品，其它商家商品

            $ru_id = explode(",", $row['all_ruId']['ru_id']);
            if (count($ru_id) > 1) {
                $row['order_goods'] = get_main_order_goods_info($order_id);
                $row['newInfo'] = get_new_ru_goods_info($row['all_ruId'], $row['order_goods']);
                $row['newOrder'] = get_new_order_info($row['newInfo']);
                $row['orderBonus'] = get_new_order_info($row['newInfo'], 1, $row['bonus_id']); //处理商家分单红包
                $row['orderFavourable'] = get_new_order_info($row['newInfo'], 2); //处理商家分单优惠活动
            }
        }
    }

    return $row;
}

//获取订单信息--或者--订单中所有商品所属商家ID,0代表自营商品，其它商家商品
function get_main_order_goods_info($order_id = 0, $type = 0)
{
    $res = OrderGoods::where('order_id', $order_id);
    $res = $res->with([
        'getGoods' => function ($query) {
            $query->select('goods_id', 'goods_weight as goodsweight', 'is_shipping');
        }
    ]);

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];

    if ($type == 1) {
        $arr['ru_id'] = '';
    }

    if ($res) {
        foreach ($res as $key => $row) {
            $row = $row['get_goods'] ? array_merge($row, $row['get_goods']) : $row;

            $shipping_type = OrderInfo::where('order_id', $row['order_id'])->value('shipping_type');
            $row['shipping_type'] = $shipping_type;

            if ($type == 0) {
                $arr[] = $row;
            } else {
                $arr['ru_id'] .= $row['ru_id'] . ',';
            }
        }

        if ($type == 1) {
            $arr['ru_id'] = explode(',', substr($arr['ru_id'], 0, -1));
            $arr['ru_id'] = array_unique($arr['ru_id']);
            $arr['ru_id'] = implode(',', $arr['ru_id']);
        }
    }

    return $arr;
}

//主次订单拆分新数组
function get_new_ru_goods_info($all_ruId = '', $order_goods = [])
{
    $all_ruId = $all_ruId['ru_id'];
    $arr = [];

    if (!empty($all_ruId)) {
        $all_ruId = explode(',', $all_ruId);
        $all_ruId = array_values($all_ruId);
    }

    if ($all_ruId) {
        for ($i = 0; $i < count($order_goods); $i++) {
            for ($j = 0; $j < count($all_ruId); $j++) {
                if ($order_goods[$i]['ru_id'] == $all_ruId[$j]) {
                    $arr[$all_ruId[$j]][$i] = $order_goods[$i];
                }
            }
        }
    }

    return $arr;
}

//运算分单后台每个订单商品总金额以及划分红包类型使用所属商家
function get_new_order_info($newInfo, $type = 0, $bonus_id = 0)
{
    $arr = [];

    if ($type == 0) {
        foreach ($newInfo as $key => $row) {
            $arr[$key]['goods_amount'] = 0;
            $arr[$key]['shopping_fee'] = 0;
            $arr[$key]['goods_id'] = 0;

            if (CROSS_BORDER === true) // 跨境多商户
            {
                $arr[$key]['rate_fee'] = 0;
            }

            $arr[$key]['ru_list'] = get_cart_goods_combined_freight($row, 2, '', $key); //计算商家运费

            $row = array_values($row);
            for ($j = 0; $j < count($row); $j++) {
                $arr[$key]['goods_id'] = $row[$j]['goods_id'];

                //ecmoban模板堂 --zhuo start 商品金额促销
                $goods_amount = $row[$j]['goods_price'] * $row[$j]['goods_number'];
                if ($goods_amount > 0) {
                    $goods_con = app(CartCommonService::class)->getConGoodsAmount($goods_amount, $row[$j]['goods_id'], 0, 0, $row[$j]['parent_id']);

                    $goods_con['amount'] = explode(',', $goods_con['amount']);
                    $amount = min($goods_con['amount']);

                    $arr[$key]['goods_amount'] += $amount;
                } else {
                    $arr[$key]['goods_amount'] += $row[$j]['goods_price'] * $row[$j]['goods_number']; //原始
                }

                if (CROSS_BORDER === true) // 跨境多商户
                {
                    $arr[$key]['rate_fee'] += $row[$j]['rate_price'];
                }

                $arr[$key]['shopping_fee'] = $arr[$key]['ru_list']['shipping_fee'];
                //ecmoban模板堂 --zhuo end 商品金额促销
            }
        }
    } elseif ($type == 1) { //红包
        foreach ($newInfo as $key => $row) {
            $arr[$key]['user_id'] = $key;
            $bonus = get_bonus_merchants($bonus_id, $key); //红包信息
            $arr[$key]['bonus'] = $bonus;
        }
    } elseif ($type == 2) { //优惠活动
        foreach ($newInfo as $key => $row) {
            $arr[$key]['user_id'] = $key;

            if ($key > 0) {
                $arr[$key]['compute_discount'] = compute_discount($type, $row, 1);
            } else {
                $arr[$key]['compute_discount'] = ['discount' => 0, 'name' => []];
            }
        }
    }

    return $arr;
}

//查询订单中所使用的红包等归属信息，所属商家(ID : bt.user_id)
function get_bonus_merchants($bonus_id = 0, $user_id = 0)
{
    $res = UserBonus::select('bonus_type_id')
        ->where('bonus_id', $bonus_id)
        ->whereHas('getBonusType', function ($query) use ($user_id) {
            $query->where('user_id', $user_id);
        });

    $res = $res->with([
        'getBonusType' => function ($query) {
            $query->select('type_id', 'user_id', 'type_money');
        }
    ]);

    $res = app(BaseRepository::class)->getToArrayFirst($res);

    $res = $res && $res['get_bonus_type'] ? array_merge($res, $res['get_bonus_type']) : $res;

    return $res;
}

//根据订单商品查询商品信息
function get_order_goods_toInfo($order_id = 0)
{
    $res = OrderGoods::select('goods_id', 'order_id', 'goods_name AS extension_name', 'goods_number', 'goods_price', 'goods_attr', 'extension_code as og_extension_code')
        ->where('order_id', $order_id);
    $res = $res->with([
        'getOrder' => function ($query) {
            $query->select('order_id', 'order_sn', 'extension_code as oi_extension_code', 'extension_id');
        },
        'getGoods' => function ($query) {
            $query->select('goods_id', 'goods_name', 'goods_thumb');
        }
    ]);

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    if ($res) {
        foreach ($res as $key => $row) {
            $row = app(BaseRepository::class)->getArrayMerge($row, $row['get_order']);
            $row = app(BaseRepository::class)->getArrayMerge($row, $row['get_goods']);

            $arr[$key]['goods_id'] = $row['goods_id'];
            if ($row['og_extension_code'] == 'package_buy') {
                $row['goods_name'] = $row['extension_name'];
                $activity = get_goods_activity_info($row['goods_id'], ['act_id', 'activity_thumb']);
                if ($activity) {
                    $row['goods_thumb'] = $activity['activity_thumb'];
                }
            }

            $row['goods_name'] = isset($row['goods_name']) ? $row['goods_name'] : $row['extension_name'];
            $arr[$key]['goods_name'] = $row['goods_name'];
            $arr[$key]['goods_number'] = $row['goods_number'];
            $arr[$key]['og_extension_code'] = $row['og_extension_code'];
            $arr[$key]['goods_price'] = price_format($row['goods_price'], false);
            $row['goods_thumb'] = $row['goods_thumb'] ?? '';
            $arr[$key]['goods_thumb'] = get_image_path($row['goods_thumb']);
            $arr[$key]['goods_attr'] = $row['goods_attr'];

            if ($row['og_extension_code'] == 'presale') {
                $arr[$key]['url'] = app(DscRepository::class)->buildUri('presale', ['act' => 'view', 'presaleid' => $row['extension_id']], $row['goods_name']);
            } elseif ($row['oi_extension_code'] == 'group_buy') {
                $arr[$key]['url'] = app(DscRepository::class)->buildUri('group_buy', ['gbid' => $row['extension_id']]);
            } elseif ($row['oi_extension_code'] == 'snatch') {
                $arr[$key]['url'] = app(DscRepository::class)->buildUri('snatch', ['sid' => $row['extension_id']]);
            } elseif ($row['oi_extension_code'] == 'seckill') {
                $arr[$key]['url'] = app(DscRepository::class)->buildUri('seckill', ['act' => "view", 'secid' => $row['extension_id']]);
            } elseif ($row['oi_extension_code'] == 'auction') {
                $arr[$key]['url'] = app(DscRepository::class)->buildUri('auction', ['auid' => $row['extension_id']]);
            } elseif ($row['oi_extension_code'] == 'exchange_goods') {
                $arr[$key]['url'] = app(DscRepository::class)->buildUri('exchange_goods', ['gid' => $row['extension_id']]);
            } else {
                $arr[$key]['url'] = app(DscRepository::class)->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
            }

            $arr[$key]['trade_id'] = app(OrderCommonService::class)->getFindSnapshot($row['order_sn'], $row['goods_id']);
            //超值礼包图片

            if ($row['og_extension_code'] == 'package_buy') {
                $activity = get_goods_activity_info($row['goods_id'], ['act_id', 'activity_thumb']);
                $arr[$key]['goods_thumb'] = $activity['goods_thumb'] ?? '';
            }
        }
    }

    return $arr;
}

//查询订单分单信息
function get_child_order_info($order_id)
{
    $res = OrderInfo::where('main_order_id', $order_id);
    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    if ($res) {
        foreach ($res as $key => $row) {
            $arr[$key]['order_sn'] = $row['order_sn'];
            $arr[$key]['order_id'] = $row['order_id'];
            $arr[$key]['shipping_name'] = $row['shipping_name'];

            $arr[$key]['order_amount'] = $row['order_amount'];
            $arr[$key]['amount_formated'] = price_format($row['order_amount'], false);
            $arr[$key]['shipping_fee_formated'] = price_format($row['shipping_fee'], false);

            $arr[$key]['pay_total'] = $row['money_paid'] + $row['surplus'];
            $arr[$key]['total_formated'] = price_format($row['money_paid'] + $row['surplus'], false);
        }
    }

    return $arr;
}

//订单分主订单和从订单 end

//获取列表商家
function get_merchants_user_list()
{
    $res = MerchantsShopInformation::whereRaw(1);

    $res = $res->with([
        'getUsers' => function ($query) {
            $query->select('user_id', 'user_name');
        }
    ]);

    $res = app(BaseRepository::class)->getToArrayGet($res);

    if ($res) {
        foreach ($res as $key => $row) {
            $row = $row['get_users'] ? array_merge($row, $row['get_users']) : $row;

            $res[$key] = $row;
        }
    }

    return $res;
}

//区域划分 start
function get_region_area_divide()
{
    $res = MerchantsRegionArea::whereRaw(1)
        ->orderBy('ra_sort, ra_id asc');

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    if ($res) {
        foreach ($res as $key => $row) {
            $arr[$key] = $row;
            $arr[$key]['area_list'] = get_to_area_list($row['ra_id']);
        }
    }

    return $arr;
}

function get_to_area_list($ra_id = 0)
{
    $res = MerchantsRegionInfo::select('ra_id', 'region_id')
        ->where('ra_id', $ra_id)
        ->orderBy('region_id');

    $res = $res->with([
        'getRegion' => function ($query) {
            $query->select('region_id', 'region_name');
        }
    ]);

    $res = app(BaseRepository::class)->getToArrayGet($res);

    if ($res) {
        foreach ($res as $key => $row) {
            $row = $row['get_region'] ? array_merge($row, $row['get_region']) : $row;

            $res[$key] = $row;
        }
    }

    return $res;
}

//区域划分 end

//独立店铺 start

//店铺导航
function get_user_store_category($ru_id = 0)
{
    $res = MerchantsCategory::where('user_id', $ru_id)
        ->where('is_show', 1)
        ->where('show_in_nav', 1);

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    if ($res) {
        foreach ($res as $key => $row) {
            $row['vieworder'] = $row['sort_order'];
            $arr[$key] = $row;

            $build_uri = [
                'cid' => $row['cat_id'],
                'urid' => $ru_id,
                'append' => $row['cat_name']
            ];

            $domain_url = app(MerchantCommonService::class)->getSellerDomainUrl($ru_id, $build_uri);
            $arr[$key]['url'] = $domain_url['domain_name'];
            $arr[$key]['opennew'] = 0;
        }
    }


    $navigator_list = get_merchants_navigator($ru_id);
    $arr = array_merge($navigator_list['middle'], $arr);

    return $arr;
}

//独立店铺 end

/**
 * 商品销量
 * 不建议使用
 */
function selled_count($goods_id, $type = '')
{
    $count = Goods::where('goods_id', $goods_id)->value('sales_volume');

    if ($count > 0) {
        return $count;
    } else {
        return 0;
    }
}

//查询一级与二级分类
function get_oneTwo_category($parent_id = 0)
{
    $res = Category::where('parent_id', $parent_id);

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    if ($res) {
        foreach ($res as $key => $row) {
            $arr[$key] = $row;
            $arr[$key]['child'] = get_oneTwo_category($row['cat_id']);

            if (empty($arr[$key]['child'])) {
                unset($arr[$key]['child']);
            }
        }
    }

    return $arr;
}

//通过地区ID查询地区名称
function get_order_region_name($region_id = 0)
{
    $region = Region::where('region_id', $region_id);
    $region = app(BaseRepository::class)->getToArrayFirst($region);

    return $region;
}

//获取购物选择商品最终金额
function get_cart_check_goods($cart_goods, $rec_id = '', $type = 0)
{
    $arr['subtotal_discount'] = 0;
    $arr['subtotal_amount'] = 0;
    $arr['subtotal_number'] = 0;
    $arr['save_amount'] = 0;
    if (!empty($rec_id)) {
        if ($cart_goods) {
            foreach ($cart_goods as $row) {
                $arr['subtotal_amount'] += $row['subtotal'];
                $arr['subtotal_number'] += $row['goods_number'];
                $arr['save_amount'] += $row['dis_amount'];
            }
        }
    }

    $arr['subtotal_amount'] = $arr['subtotal_amount'] - $arr['save_amount'];
    return $arr;
}

//ecmoban模板堂 --zhuo start 天猫属性

//获取商品区间价格的最小和最大值价格 start
function get_goods_minMax_price($goods_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $goods_price, $market_price, $type = 1)
{
    $GoodsLib = app(GoodsService::class);

    $res = GoodsAttr::select('goods_id', 'attr_id', 'attr_price')
        ->where('goods_id', $goods_id);

    $res = $res->whereHas('getGoods')
        ->whereHas('getGoodsAttribute');

    $where = [
        'warehouse_id' => $warehouse_id,
        'area_id' => $area_id,
        'area_city' => $area_city,
        'area_pricetype' => $GLOBALS['_CFG']['area_pricetype']
    ];

    $res = $res->with([
        'getGoods' => function ($query) {
            $query->select('goods_id', 'model_attr');
        },
        'getGoodsWarehouseAttr' => function ($query) use ($where) {
            $query->select('goods_id', 'attr_price')
                ->where('warehouse_id', $where['warehouse_id']);
        },
        'getGoodsWarehouseAreaAttr' => function ($query) use ($where) {
            $query = $query->select('goods_id', 'attr_price')
                ->where('area_id', $where['area_id']);

            if ($where['area_pricetype'] == 1) {
                $query->where('city_id', $where['area_city']);
            }
        }
    ]);

    $res = app(BaseRepository::class)->getToArrayGet($res);

    if (!empty($res)) {
        foreach ($res as $key => $row) {
            if ($row['get_goods']) {
                if ($row['get_goods']['model_attr'] == 1) {
                    $row['attr_price'] = $row['get_goods_warehouse_attr']['attr_price'];
                } elseif ($row['get_goods']['model_attr'] == 2) {
                    $row['attr_price'] = $row['get_goods_warehouse_area_attr']['attr_price'];
                }
            }

            $res[$key] = $row;
        }
    }

    $arr_res = $res;

    $arr_k = '';
    $k_res = [];
    if ($arr_res) {
        foreach ($arr_res as $val) {
            $arr_k .= $val['attr_id'] . '@';
        }
        $arr_k = rtrim($arr_k, '@');

        $k_res = explode('@', $arr_k);
        $k_res = array_flip(array_flip($k_res));
    }

    $new_arr = [];
    if (isset($k_res) && !empty($k_res)) {
        foreach ($k_res as $val) {
            foreach ($arr_res as $v) {
                if ($v['attr_id'] == $val) {
                    $new_arr[$val][] = $v['attr_price'];
                }
            }
        }
    }

    if ($type == 1) {
        $new_arr = get_unset_null_array($new_arr, 2);
    }

    $new_arr_res = [];
    $num_res_max = 0;
    $num_res_min = 0;

    if ($new_arr) {
        foreach ($new_arr as $k => $val) {
            $new_arr_res[$k]['max'] = $val[array_search(max($val), $val)];
            $new_arr_res[$k]['min'] = $val[array_search(min($val), $val)];
        }

        foreach ($new_arr_res as $val) {
            $num_res_max += $val['max'];
            $num_res_min += $val['min'];
        }
    }

    if ($GLOBALS['_CFG']['add_shop_price'] == 0) {
        $num_res_min = 0;
    } else {
        $num_res_max = 0;
    }

    //商品组合购买
    if ($type == 1) {
        $arr['goods_min'] = $goods_price + $num_res_min;
        $arr['goods_max'] = $goods_price + $num_res_max;

        $arr['market_min'] = $market_price + $num_res_min;
        $arr['market_max'] = $market_price + $num_res_max;
    } //商品普通购买
    elseif ($type == 2) {
        $where = [
            'goods_id' => $goods_id,
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city
        ];
        $goods = $GoodsLib->getGoodsInfo($where);

        if ($goods) {
            if (!empty($goods['format_promote_price'])) {
                $arr['promote_minPrice'] = price_format($goods['format_promote_price'] + $num_res_min);
                $arr['promote_maxPrice'] = price_format($goods['format_promote_price'] + $num_res_max);
            } else {
                $arr['promote_minPrice'] = $goods['format_promote_price'];
                $arr['promote_maxPrice'] = $goods['format_promote_price'];
            }

            $arr['shop_minPrice'] = price_format($goods['shop_price'] + $num_res_min);
            $arr['shop_maxPrice'] = price_format($goods['shop_price'] + $num_res_max);
        }
    }

    return $arr;
}

//删掉值为0的数组
/*
 * 1 = 一维数组
 * 2 = 二维数组
*/
function get_unset_null_array($arr = [], $type = 0)
{
    $arr = array_values($arr);

    $new_arr = [];
    if ($arr && $type == 2) {
        for ($i = 0; $i < count($arr); $i++) {
            for ($j = 0; $j < count($arr[$i]); $j++) {
                if ($arr[$i][$j] > 0) {
                    $new_arr[$i][$j] = $arr[$i][$j];
                }
            }
        }
    } elseif ($arr && $type == 1) {
        for ($i = 0; $i < count($arr); $i++) {
            if ($arr[$i] > 0) {
                $new_arr[$i] = $arr[$i];
            }
        }
    }

    return $new_arr;
}

//查询已选择组合购买商品的区间价格
function get_choose_goods_combo_cart($fittings, $number = 1)
{
    $arr = [];

    $arr['fittings_min'] = 0;
    $arr['fittings_max'] = 0;
    $arr['market_min'] = 0;
    $arr['market_max'] = 0;
    $arr['save_price'] = '';
    $arr['collocation_number'] = 0;
    $arr['save_minPrice'] = 0;
    $arr['save_maxPrice'] = 0;
    $arr['fittings_price'] = 0;
    $arr['fittings_market_price'] = 0;
    $arr['save_price_amount'] = 0;
    $arr['groupId'] = 0;
    $arr['all_price_ori'] = 0;
    $arr['return_attr'] = 0;
    $arr['all_market_price'] = 0;

    if ($fittings) {
        foreach ($fittings as $key => $row) {
            $arr[$key]['goods_id'] = $row['goods_id'];
            $arr[$key]['market_price'] = $row['market_price'] + $row['attr_price']; //实际市场价
            $arr[$key]['fittings_minPrice'] = $row['fittings_minPrice'];        //配件区间价格 min
            $arr[$key]['fittings_maxPrice'] = $row['fittings_maxPrice'];        //配件区间价格 max
            $arr[$key]['market_minPrice'] = $row['market_minPrice'];        //市场区间价格 min
            $arr[$key]['market_maxPrice'] = $row['market_maxPrice'];        //市场区间价格 max
            $arr[$key]['shop_price_ori'] = $row['shop_price_ori'];            //商品原价
            $arr[$key]['fittings_price_ori'] = $row['fittings_price_ori'];        //配件价格
            $arr[$key]['attr_price'] = $row['attr_price'];            //配件商品属性金额
            $arr[$key]['spare_price_ori'] = $row['spare_price_ori'];        //商品原价 - 配件价格 = 节省价
            $arr[$key]['group_id'] = !empty($row['group_id']) ? $row['group_id'] : 0;                //组ID
            $arr[$key]['is_attr'] = get_cart_combo_goods_product_list($row['goods_id']);

            $arr[$key]['shop_price'] = $row['get_goods']['shop_price'] ?? $row['shop_price_ori'];

            if ($GLOBALS['_CFG']['add_shop_price'] == 0) {
                $row['attr_price'] = 0;
            }

            if ($arr[$key]['group_id'] == 0) {
                $arr[$key]['price_ori'] = $row['shop_price_ori'] + $row['attr_price'];
            } else {
                $arr[$key]['price_ori'] = $row['fittings_price_ori'] + $row['attr_price'];
            }

            $arr['save_price_amount'] += $row['spare_price_ori']; //配件商品节省总金额
            $arr['fittings_price'] += $arr[$key]['price_ori']; //配件商品总金额
            $arr['fittings_market_price'] += $row['market_price']; //配件商品市场价总金额

            $arr['save_price'] .= $row['spare_price_ori'] . ",";

            if (!empty($row['group_id'])) {
                $arr['groupId'] .= $row['group_id'] . ",";
            }
        }

        $arr['collocation_number'] = count($fittings) - 1;

        $arr['save_price'] = substr($arr['save_price'], 0, -1);
        $arr['save_price'] = explode(',', $arr['save_price']);
        $arr['save_price'] = get_unset_null_array($arr['save_price'], 1);

        $arr['save_minPrice'] = !empty($arr['save_price']) ? min($arr['save_price']) : 0;
        $arr['save_maxPrice'] = get_save_maxPrice($arr['save_price']);

        $arr['groupId'] = substr($arr['groupId'], 1, -1);
        $arr['groupId'] = explode(',', $arr['groupId']);
        $arr['groupId'] = array_unique($arr['groupId']);
        $arr['groupId'] = implode(',', $arr['groupId']);

        $minmax_values = get_min_or_max_values($arr);

        $arr['fittings_min'] = $minmax_values['fittings_minPrice'];
        $arr['fittings_max'] = $minmax_values['fittings_maxPrice'];
        $arr['market_min'] = $minmax_values['market_minPrice'];
        $arr['market_max'] = $minmax_values['market_maxPrice'];

        $arr['return_attr'] = $minmax_values['return_attr']; //判断配件商品是否有属性
        $arr['all_price_ori'] = $minmax_values['all_price_ori'];
        $arr['all_market_price'] = $minmax_values['all_market_price'];
    }

    return $arr;
}

//获取数组里面最小值和最大值
function get_min_or_max_values($arr)
{
    $unsetStr = "fittings_min,fittings_max,market_min,market_max,save_price,collocation_number,save_minPrice,save_maxPrice,fittings_price,save_price_amount,groupId,all_price_ori,return_attr,fittings_market_price";
    $unsetStr = explode(',', $unsetStr);

    foreach ($unsetStr as $str) {
        unset($arr[$str]);
    }

    $newArr = [];
    $newArr['fittings_minPrice'] = '';
    $newArr['fittings_maxPrice'] = '';
    $newArr['market_minPrice'] = '';
    $newArr['market_maxPrice'] = '';
    $newArr['is_attr'] = '';
    $shop_price = 0;
    $market_price = 0;
    $newArr['all_price_ori'] = 0;
    $newArr['return_attr'] = 0;
    $newArr['all_market_price'] = 0;

    foreach ($arr as $key => $row) {

        if ($key > 0) {
            $newArr['all_price_ori'] += $row['price_ori'];
            $newArr['all_market_price'] += $row['shop_price'];
            $newArr['fittings_minPrice'] .= $row['fittings_minPrice'] . ',';
            $newArr['fittings_maxPrice'] .= $row['fittings_maxPrice'] . ',';
            $newArr['market_minPrice'] .= $row['shop_price'] . ',';
            $newArr['market_maxPrice'] .= $row['shop_price'] . ',';

            if ($row['is_attr']) {
                $newArr['is_attr'] .= $row['is_attr'] . ',';
            }
        }
    }

    if ($newArr['is_attr']) {
        $newArr['is_attr'] = app(DscRepository::class)->delStrComma($newArr['is_attr']);
        $is_attr = explode(",", $newArr['is_attr']);

        foreach ($is_attr as $key => $row) {
            if ($row) {
                $row = floatval($row);
                $newArr['return_attr'] += $row;
            }
        }
    }

    $newArr['fittings_minPrice'] = app(DscRepository::class)->delStrComma($newArr['fittings_minPrice']);
    $newArr['market_minPrice'] = app(DscRepository::class)->delStrComma($newArr['market_minPrice']);

    $newArr['fittings_maxPrice'] = app(DscRepository::class)->delStrComma($newArr['fittings_maxPrice']);
    $newArr['market_maxPrice'] = app(DscRepository::class)->delStrComma($newArr['market_maxPrice']);

    $fittings_minPrice = !empty($newArr['fittings_minPrice']) ? explode(",", $newArr['fittings_minPrice']) : [];
    $market_minPrice = !empty($newArr['market_minPrice']) ? explode(",", $newArr['market_minPrice']) : [];

    $fittings_maxPrice = !empty($newArr['fittings_maxPrice']) ? explode(",", $newArr['fittings_maxPrice']) : [];
    $market_maxPrice = !empty($newArr['market_maxPrice']) ? explode(",", $newArr['market_maxPrice']) : [];

    foreach ($fittings_maxPrice as $key => $shop) {
        $shop_price += $shop;
    }

    $newArr['fittings_maxPrice'] = $shop_price;

    foreach ($market_maxPrice as $key => $market) {
        $market_price += $market;
    }

    $newArr['market_maxPrice'] = $market_price;

    array_push($fittings_minPrice, $arr[0]['fittings_minPrice']);
    array_push($fittings_maxPrice, $arr[0]['fittings_maxPrice']);

    array_push($market_minPrice, $arr[0]['shop_price']);
    array_push($market_maxPrice, $arr[0]['shop_price']);

    $newArr['fittings_minPrice'] = min($fittings_minPrice);
    $newArr['fittings_maxPrice'] = max($fittings_maxPrice);

    $newArr['market_minPrice'] = min($market_minPrice);
    $newArr['market_maxPrice'] = max($market_maxPrice);

    $newArr['all_price_ori'] = $arr[0]['price_ori'] + $newArr['all_price_ori']; //实际搭配价
    $newArr['all_market_price'] = $arr[0]['shop_price'] + $arr[0]['attr_price'] + $newArr['all_market_price']; //实际搭配市场价

    return $newArr;
}

//查询组合购买里面的配件商品是否有货品
function get_cart_combo_goods_product_list($goods_id)
{
    $attr_list = GoodsAttr::where('goods_id', $goods_id)->count();

    if ($attr_list) { //当商品没有货品时
        return 1;
    } else {
        return 0;
    }
}

function get_save_maxPrice($save_price)
{
    $save_maxPrice = 0;
    if ($save_price) {
        foreach ($save_price as $key => $row) {
            $save_maxPrice += $row;
        }
    }

    return $save_maxPrice;
}

//查询商品属性类型列表
function get_goods_attr_type_list($goods_id = 0, $type = 0)
{
    $attr_list = GoodsAttr::where('goods_id', $goods_id);

    $attr_list = $attr_list->whereHas('getGoodsAttribute');

    if ($type == 1) {
        $attr_list = $attr_list->count();
    } else {
        $attr_list = $attr_list->with([
            'getGoodsAttribute' => function ($query) {
                $query->select('attr_id', 'attr_name', 'sort_order');
            }
        ]);

        $attr_list = app(BaseRepository::class)->getToArrayGet($attr_list);

        if ($attr_list) {
            foreach ($attr_list as $key => $row) {
                $row = $row['get_goods_attribute'] ? array_merge($row, $row['get_goods_attribute']) : $row;

                $attr_list[$key] = $row;
            }

            $attr_list = app(BaseRepository::class)->getSortBy($attr_list, 'sort_order');
        }
    }

    return $attr_list;
}

//获取商品区间价格的最小和最大值价格 end

//数组字符串排序
function get_arr_str_key($arr = [])
{
    $str = '';
    if ($arr) {
        $new_arr = [];
        foreach ($arr as $key => $row) {
            $strlen = mb_strlen($row, 'utf8');
            $new_arr[$key]['len'] = $strlen;
            $new_arr[$key]['val'] = $row;
        }

        $new_arr = get_array_sort($new_arr, 'len', 'desc');
    }

    return $str;
}

//查询商家设置运费方式
function get_seller_shipping_type($ru_id)
{
    $res = SellerShopinfo::where('ru_id', $ru_id);

    $res = $res->whereHas('getShipping');

    $res = $res->with([
        'getShipping' => function ($query) {
            $query->select('shipping_id', 'shipping_name', 'shipping_code');
        }
    ]);

    $res = app(BaseRepository::class)->getToArrayFirst($res);

    $res = $res && $res['get_shipping'] ? array_merge($res, $res['get_shipping']) : $res;

    return $res;
}

//获取所有城市信息 by wang
function get_city_region()
{
    $res = Region::where('region_type', 2)->where('parent_id', '>', 0);

    $res = $res->with([
        'getRegionWarehouse' => function ($query) {
            $query->select('region_id', 'regionId');
        }
    ]);

    $res = app(BaseRepository::class)->getToArrayGet($res);

    if ($res) {
        foreach ($res as $key => $row) {
            if ($row['get_region_warehouse']) {
                $row['is_has'] = 1;
            } else {
                $row['is_has'] = 0;
            }

            $res[$key] = $row;
        }
    }

    return $res;
}

function get_search_city_true($region_id = 0)
{
    $res = Region::where('region_id', $region_id)
        ->where('region_type', 2);

    $res = app(BaseRepository::class)->getToArrayGet($res);

    return $res;
}

//by wang获得推荐品牌信息
function get_recommend_brands($num = 0)
{
    $res = Brand::where('is_show', 1)
        ->whereHas('getBrandExtend', function ($query) {
            $query->where('is_recommend', 1);
        });

    if ($num > 0) {
        $res->take($num);
    }

    $res = $res->orderBy('sort_order');

    $res = app(BaseRepository::class)->getToArrayGet($res);

    return $res;
}

//批量删除运费
function get_freight_batch_remove($id)
{
    if ($id) {
        for ($i = 0; $i < count($id); $i++) {
            WarehouseFreight::where('id', intval($id[$i]))->delete();
        }
    }
}

//商品运费by wu start
function goodsShippingFee($goods_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0, $region = [], $seckill_price = '')
{
    $transport_info = [];

    $config = cache('shop_config');
    $config = !is_null($config) ? $config : false;
    if ($config === false) {
        $config = app(\App\Services\Common\ConfigService::class)->getConfig();
    }

    //初始运费信息
    $shippingInfo = [
        'shipping_id' => 0,
        'shipping_code' => '',
        'shipping_name' => '',
        'shipping_type' => $config['freight_model'] ?? 0,
        'shipping_fee' => '',
        'shipping_fee_formated' => '',
        'free_money' => '',
        'is_shipping' => 0 //是否支持配送 0:不支持，1:支持
    ];

    //初始运费
    $shippingFee = 0;


    //获取商品信息
    $goodsInfo = Goods::select(['goods_id', 'freight', 'user_id', 'tid', 'is_shipping', 'shipping_fee', 'goods_weight', 'shop_price', 'model_price'])
        ->where('goods_id', $goods_id);

    $area = [
        'warehouse_id' => $warehouse_id,
        'area_id' => $area_id,
        'area_city' => $area_city,
        'area_pricetype' => $config['area_pricetype']
    ];

    $user_rank = session('user_rank', 1);
    $goodsInfo = $goodsInfo->with([
        'getMemberPrice' => function ($query) use ($user_rank) {
            $query->where('user_rank', $user_rank);
        },
        'getWarehouseGoods' => function ($query) use ($area) {
            $query->where('region_id', $area['warehouse_id']);
        },
        'getWarehouseAreaGoods' => function ($query) use ($area) {
            $query = $query->where('region_id', $area['area_id']);

            if ($area['area_pricetype'] == 1) {
                $query->where('city_id', $area['area_city']);
            }
        }
    ]);

    $goodsInfo = app(BaseRepository::class)->getToArrayFirst($goodsInfo);

    if ($goodsInfo) {
        $goodsInfo['ru_id'] = $goodsInfo['user_id'];

        $price = [
            'model_price' => isset($goodsInfo['model_price']) ? $goodsInfo['model_price'] : 0,
            'user_price' => isset($goodsInfo['get_member_price']['user_price']) ? $goodsInfo['get_member_price']['user_price'] : 0,
            'percentage' => isset($goodsInfo['get_member_price']['percentage']) ? $goodsInfo['get_member_price']['percentage'] : 0,
            'warehouse_price' => isset($goodsInfo['get_warehouse_goods']['warehouse_price']) ? $goodsInfo['get_warehouse_goods']['warehouse_price'] : 0,
            'region_price' => isset($goodsInfo['get_warehouse_area_goods']['region_price']) ? $goodsInfo['get_warehouse_area_goods']['region_price'] : 0,
            'shop_price' => isset($goodsInfo['shop_price']) ? $goodsInfo['shop_price'] : 0,
            'warehouse_promote_price' => isset($goodsInfo['get_warehouse_goods']['warehouse_promote_price']) ? $goodsInfo['get_warehouse_goods']['warehouse_promote_price'] : 0,
            'region_promote_price' => isset($goodsInfo['get_warehouse_area_goods']['region_promote_price']) ? $goodsInfo['get_warehouse_area_goods']['region_promote_price'] : 0,
            'promote_price' => isset($goodsInfo['promote_price']) ? $goodsInfo['promote_price'] : 0,
            'wg_number' => isset($goodsInfo['get_warehouse_goods']['region_number']) ? $goodsInfo['get_warehouse_goods']['region_number'] : 0,
            'wag_number' => isset($goodsInfo['get_warehouse_area_goods']['region_number']) ? $goodsInfo['get_warehouse_area_goods']['region_number'] : 0,
            'goods_number' => isset($goodsInfo['goods_number']) ? $goodsInfo['goods_number'] : 0
        ];

        $price = app(GoodsCommonService::class)->getGoodsPrice($price, session('discount', 1), $goodsInfo);

        $goodsInfo['shop_price'] = $price['shop_price'];
        $goodsInfo['goods_price'] = $price['shop_price'];
        $goodsInfo['promote_price'] = $price['promote_price'];
        $goodsInfo['goods_number'] = $price['goods_number'];
    }

    $is_shipping = 0;


    if ($goodsInfo) {
        /**
         * 商品
         * 运费模板
         */
        if ($goodsInfo['freight'] == 2) {
            if (is_numeric($seckill_price)) {
                $goodsInfo['shop_price'] = $seckill_price;
            }

            //查询商家设置送方式
            $sellerShippingInfo = get_seller_shipping_type($goodsInfo['user_id']);

            $is_where = 0;
            if ($sellerShippingInfo) {
                $shippingInfo['shipping_id'] = $sellerShippingInfo['shipping_id'];
                $shippingInfo['shipping_code'] = $sellerShippingInfo['shipping_code'];
                $shippingInfo['shipping_name'] = $sellerShippingInfo['shipping_name'];
                $is_where = 1;
            }

            $transport_info = get_goods_transport($goodsInfo['tid']);

            $val = [];
            if ($transport_info) {
                $where = [
                    'shipping_id' => $sellerShippingInfo && $sellerShippingInfo['shipping_id'] ? $sellerShippingInfo['shipping_id'] : 0,
                    'is_where' => $is_where
                ];

                if ($transport_info['freight_type'] == 1) {
                    //获取配送区域
                    $val = GoodsTransportTpl::select('shipping_id')
                        ->where('user_id', $goodsInfo['user_id'])
                        ->where('tid', $goodsInfo['tid'])
                        ->whereRaw("(FIND_IN_SET('" . $region[1] . "', region_id) OR FIND_IN_SET('" . $region[2] . "', region_id) OR FIND_IN_SET('" . $region[3] . "', region_id) OR FIND_IN_SET('" . $region[4] . "', region_id))");

                    $val = $val->with([
                        'getShipping' => function ($query) use ($where) {
                            $query = $query->select('shipping_id', 'shipping_code', 'shipping_name', 'shipping_desc', 'insure', 'support_cod')
                                ->where('enabled', 1);

                            if ($where['is_where'] == 1) {
                                $query->where('shipping_id', $where['shipping_id']);
                            }
                        }
                    ]);

                    $val = app(BaseRepository::class)->getToArrayFirst($val);

                    $val = $val && $val['get_shipping'] ? array_merge($val, $val['get_shipping']) : $val;
                } else {
                    $val = GoodsTransportExtend::where('tid', $goodsInfo['tid'])->where('ru_id', $goodsInfo['user_id']);

                    $val = $val->whereHas('getGoodsTransportExpress', function ($query) use ($goodsInfo) {
                        $query->where('ru_id', $goodsInfo['user_id']);
                    });

                    $val = $val->whereRaw("((FIND_IN_SET('" . $region[1] . "', top_area_id)) OR (FIND_IN_SET('" . $region[2] . "', area_id) OR FIND_IN_SET('" . $region[3] . "', area_id) OR FIND_IN_SET('" . $region[4] . "', area_id)))");

                    $val = $val->with('getGoodsTransportExpress');

                    $val = app(BaseRepository::class)->getToArrayFirst($val);

                    $val = $val && $val['get_goods_transport_express'] ? array_merge($val, $val['get_goods_transport_express']) : $val;
                    $gt_shipping_id = $val ? app(BaseRepository::class)->getExplode($val['shipping_id']) : '';

                    $shipping = Shipping::where('enabled', 1);

                    if ($where['is_where'] == 1) {
                        $shipping = $shipping->where('shipping_id', $where['shipping_id']);
                    }

                    if ($gt_shipping_id) {
                        $shipping = $shipping->whereIn('shipping_id', $gt_shipping_id);
                    }

                    $shipping = app(BaseRepository::class)->getToArrayFirst($shipping);

                    $val = $shipping ? array_merge($val, $shipping) : $val;
                }
            }

            if (!empty($val)) {
                $is_shipping = 1;
            } else {
                $shippinOrderInfo = [
                    'ru_id' => $goodsInfo['user_id'],
                    'freight' => $goodsInfo['freight'],
                    'tid' => $goodsInfo['tid']
                ];
                $shipping_list = available_shipping_list($region, $shippinOrderInfo, 1);
                if ($shipping_list) {
                    $is_shipping = 1;

                    if ($sellerShippingInfo && $sellerShippingInfo['shipping_id']) {
                        $cfg = [
                            ['name' => 'item_fee', 'value' => 0],
                            ['name' => 'base_fee', 'value' => 0],
                            ['name' => 'step_fee', 'value' => 0],
                            ['name' => 'free_money', 'value' => 100000]
                        ];

                        if (!isset($sellerShippingInfo['configure']) && empty($sellerShippingInfo['configure'])) {
                            $sellerShippingInfo['configure'] = serialize($cfg);
                        }
                    }
                }
            }

            if ($goodsInfo['is_shipping']) {
                $shippingFee = 0;
            } else {
                if (!empty($goodsInfo['freight'])) {
                    $transportInfo = get_goods_transport($goodsInfo['tid']);

                    if ($transportInfo) {
                        if ($transportInfo['freight_type']) {
                            /**
                             * 商品
                             * 运费模板
                             * 快递模板
                             */
                            $transport_tpl = get_goods_transport_tpl($goodsInfo, $region);
                            $shippingFee = $transport_tpl['shippingFee'];
                            $is_shipping = $transport_tpl['is_shipping'];
                        } else {
                            /**
                             * 商品
                             * 运费模板
                             * 自定义
                             */
                            $transport = ['top_area_id', 'area_id', 'tid', 'ru_id', 'sprice'];
                            $goods_transport = GoodsTransportExtend::select($transport)
                                ->where('ru_id', $goodsInfo['user_id'])
                                ->where('tid', $goodsInfo['tid']);

                            $goods_transport = $goods_transport->whereRaw("(FIND_IN_SET('" . $region[2] . "', area_id))");
                            $goods_transport = app(BaseRepository::class)->getToArrayFirst($goods_transport);

                            $shippingFee = $goods_transport ? $goods_transport['sprice'] : 0;
                        }
                    } else {
                        $is_shipping = 0;
                    }
                }
            }
        } /**
         * 商品
         * 固定运费
         */
        elseif ($goodsInfo['freight'] == 1) {
            if ($goodsInfo['is_shipping']) {
                $shippingFee = 0;
            } else {
                $shippingFee = $goodsInfo['shipping_fee'];
            }
            $is_shipping = 1;
        }
    }

    $goodsInfo['tid'] = $goodsInfo['tid'] ?? 0;

    //查询是否为运费到付
    $fpd_shipping = GoodsTransportExpress::where('tid', $goodsInfo['tid']);
    $fpd_shipping = $fpd_shipping->with([
        'getShipping' => function ($query) {
            $query->select('shipping_id', 'shipping_code', 'shipping_name')
                ->where('shipping_code', 'fpd');
        }
    ]);

    $fpd_shipping = app(BaseRepository::class)->getToArrayFirst($fpd_shipping);
    $fpd_shipping = isset($fpd_shipping['get_shipping']) ? app(BaseRepository::class)->getArrayMerge($fpd_shipping, $fpd_shipping['get_shipping']) : $fpd_shipping;
    if (!empty($fpd_shipping)) {
        $shippingInfo['shipping_id'] = $fpd_shipping['shipping_id'];
        $shippingInfo['shipping_code'] = isset($fpd_shipping['shipping_code']) ? $fpd_shipping['shipping_code'] : '';
        $shippingInfo['shipping_name'] = isset($fpd_shipping['shipping_name']) ? $fpd_shipping['shipping_name'] : '';
        if (isset($transport_info['freight_type']) && $transport_info['freight_type'] == 0) {
            $shippingFee += $fpd_shipping['shipping_fee'];
        }
    }

    $shippingInfo['shipping_title'] = isset($transport_info['shipping_title']) ? $transport_info['shipping_title'] : '';
    $shippingInfo['shipping_fee'] = $shippingFee ?? 0;
    $shippingInfo['shipping_fee_formated'] = price_format($shippingFee, false);
    $shippingInfo['is_shipping'] = $is_shipping; //是否支持配送

    return $shippingInfo;
}

/**
 * 商品地区运费模板
 */
function get_goods_transport_tpl($goodsInfo = [], $region = [], $shippingInfo = [], $goods_number = 1)
{
    $goodsInfo['goods_weight'] = isset($goodsInfo['goods_weight']) ? $goodsInfo['goods_weight'] : $goodsInfo['goodsweight'];
    $goodsInfo['shop_price'] = isset($goodsInfo['shop_price']) ? $goodsInfo['shop_price'] : $goodsInfo['goods_price'];

    if (empty($shippingInfo)) {
        $is_goods = 1;

        /**
         * 商品详情显示
         */
        //查询商家设置送方式
        $shippingInfo = get_seller_shipping_type($goodsInfo['user_id']);
        if (!$shippingInfo) {
            $tpl_shipping = get_goods_transport_tpl_shipping($goodsInfo['tid'], 0, $region);
            if ($tpl_shipping) {
                $shippingInfo = $tpl_shipping;
            }
        } else {
            $shippingInfo = get_goods_transport_tpl_shipping($goodsInfo['tid'], $shippingInfo['shipping_id'], $region);
        }
    } else {
        $is_goods = 0;

        /**
         * 购物车显示/订单分单
         */
        $shippingInfo = get_goods_transport_tpl_shipping($goodsInfo['tid'], $shippingInfo['shipping_id'], $region);
    }

    if (!($shippingInfo && $shippingInfo['shipping_id'])) {
        $shippingInfo = get_goods_transport_tpl_shipping($goodsInfo['tid'], 0, $region, $is_goods);

        if ($shippingInfo) {
            if ($is_goods == 1) {
                $shippingInfo = isset($shippingInfo[0]) ? $shippingInfo[0] : [];
            }
        }
    }

    //获取配送区域
    $val = GoodsTransportTpl::where('user_id', $goodsInfo['user_id'])->where('tid', $goodsInfo['tid']);

    $val = $val->whereRaw("(FIND_IN_SET('" . $region[1] . "', region_id) OR FIND_IN_SET('" . $region[2] . "', region_id) OR FIND_IN_SET('" . $region[3] . "', region_id) OR FIND_IN_SET('" . $region[4] . "', region_id))");

    $val = $val->whereHas('getShipping', function ($query) use ($shippingInfo) {
        $query = $query->where('enabled', 1);

        if ($shippingInfo && $shippingInfo['shipping_id']) {
            $query->where('shipping_id', $shippingInfo['shipping_id']);
        }
    });

    $val = $val->with([
        'getShipping' => function ($query) {
            $query->select('shipping_id', 'shipping_code', 'shipping_name', 'shipping_desc', 'insure', 'support_cod');
        }
    ]);

    $val = app(BaseRepository::class)->getToArrayFirst($val);

    if ($val) {
        $val = isset($val['get_shipping']) ? array_merge($val, $val['get_shipping']) : $val;
    }

    //是否支持配送
    $is_shipping = 0;
    if ($val) {
        $is_shipping = 1;
    }

    if (!$shippingInfo) {
        $shippingInfo = [
            'shipping_id' => 0,
            'shipping_code' => '',
            'configure' => '',
        ];
    }

    $shippingFee = 0;
    $free_money = 0;
    if ($is_shipping) {
        if (empty($shippingInfo) || $shippingInfo && empty($shippingInfo['shipping_id'])) {
            $shippingInfo = $val;
        }

        $goods_weight = $goodsInfo['goods_weight'] * $goods_number;
        $shop_price = $goodsInfo['shop_price'] * $goods_number;
        $shippingFee = app(DscRepository::class)->shippingFee($shippingInfo['shipping_code'], $shippingInfo['configure'], $goods_weight, $shop_price, $goods_number);
        $shippingCfg = unserialize_config($shippingInfo['configure']);
        $free_money = $shippingCfg['free_money'];
    }

    $arr = [
        'shippingFee' => $shippingFee,
        'free_money' => $free_money,
        'shipping_fee_formated' => price_format($shippingFee, false),
        'free_money_formated' => price_format($free_money, false),
        'is_shipping' => $is_shipping,
        'shipping_id' => $shippingInfo['shipping_id']  //购物流程需要
    ];

    return $arr;
}

/**
 * 获取商品运费模板的运费方式
 */
function get_goods_transport_tpl_shipping($tid = 0, $shipping_id = 0, $region = [], $type = 0, $limit = 0)
{
    $res = GoodsTransportTpl::where('tid', $tid);

    if ($shipping_id) {
        $res = $res->where('shipping_id', $shipping_id);
    }

    $res = $res->whereHas('getShipping', function ($query) {
        $query->where('enabled', 1);
    });

    $res = $res->with([
        'getShipping' => function ($query) {
            $query->select('shipping_id', 'shipping_name', 'shipping_code');
        }
    ]);

    if ($limit > 0) {
        $res = $res->take($limit);
    }

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $arr = [];
    if ($type == 1) {
        if ($res) {
            foreach ($res as $key => $row) {
                $row = isset($row['get_shipping']) ? array_merge($row, $row['get_shipping']) : $row;

                $region_id = !empty($row['region_id']) ? explode(",", $row['region_id']) : [];

                if ($region) {
                    foreach ($region as $rk => $rrow) {
                        if ($region_id && in_array($rrow, $region_id)) {
                            $arr[] = $row;
                        } else {
                            continue;
                        }
                    }
                }
            }
        }
    } else {
        if ($res) {
            foreach ($res as $key => $row) {
                $row = isset($row['get_shipping']) ? array_merge($row, $row['get_shipping']) : $row;

                $region_id = !empty($row['region_id']) ? explode(",", $row['region_id']) : [];

                if ($region) {
                    foreach ($region as $rk => $rrow) {
                        if ($region_id && in_array($rrow, $region_id)) {
                            return $row;
                        }
                    }
                }
            }
        }
    }

    return $arr;
}

/**
 * 运费模板信息
 */
function get_goods_transport($tid = 0)
{
    $res = GoodsTransport::where('tid', $tid);
    $res = app(BaseRepository::class)->getToArrayFirst($res);

    return $res;
}

/**
 * 处理序列化的支付、配送的配置参数
 * 返回一个以name为索引的数组
 *
 * @access  public
 * @param string $cfg
 * @return  void
 */
function unserialize_config($cfg)
{
    if (is_string($cfg) && ($arr = unserialize($cfg)) !== false) {
        $config = [];

        foreach ($arr as $key => $val) {
            $config[$val['name']] = $val['value'];
        }

        return $config;
    } else {
        return false;
    }
}


/**
 * 在线客服 bylu
 */
function IM($appkey, $secretkey)
{
    app(\App\Plugins\AliYunIm\AliIm::class)->index();

    $user_id = session('user_id', 0);

    load_helper(['order']);

    date_default_timezone_set('Asia/Shanghai');

    $c = app(TopClient::class);
    $c->appkey = $appkey;
    $c->secretKey = $secretkey;
    $req = app(OpenimUsersAddRequest::class);

    //判断用户是否登入,登入了就用登入的用户名,未登入就使用匿名;
    if ($user_id > 0) {
        $userObjet = app(UserService::class);

        $where = [
            'user_id' => $user_id
        ];
        $user_info = $userObjet->userInfo($where);

        $userinfos = app(Userinfos::class);
        $userinfos->nick = $user_info['user_name'];
        $userinfos->icon_url = $user_info['user_picture'];
        $userinfos->email = $user_info['email'];
        $userinfos->mobile = $user_info['mobile_phone'];
        $userinfos->userid = 'dsc' . $user_info['user_id'];
        $userinfos->password = 'dsc' . $user_info['user_id'];
        $userinfos->career = lang('online.did_not_fill');
        $userinfos->address = lang('online.did_not_fill');
        $userinfos->name = $user_info['user_name'];
        $userinfos->gender = $user_info['sex'] == 1 ? 'M' : ($user_info['sex'] == 2 ? 'F' : '');
        $userinfos->wechat = lang('online.did_not_fill');
        $userinfos->qq = $user_info['qq'];
        $userinfos->weibo = lang('online.did_not_fill');
        $req->setUserinfos(json_encode($userinfos));
        $c->execute($req);
    } else {
        $user_info['user_id'] = 'ni' . time() . mt_rand(0, 9);

        session([
            'user_ni_id' => $user_info['user_id']
        ]);

        $user_info['user_name'] = lang('online.anonymous') . '_' . $user_info['user_id'];
        $userinfos = app(Userinfos::class);
        $userinfos->nick = $user_info['user_name'];
        $userinfos->userid = $user_info['user_id'];
        $userinfos->password = $user_info['user_id'];
        $userinfos->name = $user_info['user_name'];
        $userinfos->remark = $user_info['user_id'];
        $req->setUserinfos(json_encode($userinfos));
        $c->execute($req);
    }
}


/**
 * 商品属性组合 bylu
 */
function attr_group_backup()
{
    $t = func_get_args();
    if (func_num_args() == 1) {
        return call_user_func_array(__FUNCTION__, $t[0]);
    }
    $a = array_shift($t);
    if (!is_array($a)) {
        $a = [$a];
    }
    $a = array_chunk($a, 1);
    do {
        $r = [];
        $b = array_shift($t);
        if (!is_array($b)) {
            $b = [$b];
        }
        foreach ($a as $p) {
            foreach (array_chunk($b, 1) as $q) {
                $r[] = array_merge($p, $q);
            }
        }
        $a = $r;
    } while ($t);
    return $r;
}

function combination($arr, $num = 0)
{
    $len = count($arr);
    if ($num == 0) {
        $num = $len;
    }
    $res = [];
    for ($i = 1, $n = pow(2, $len); $i < $n; ++$i) {
        $tmp = str_pad(base_convert($i, 10, 2), $len, '0', STR_PAD_LEFT);
        $t = [];
        for ($j = 0; $j < $len; ++$j) {
            if ($tmp{$j} == '1') {
                $t[] = $arr[$j];
            }
        }
        if (count($t) == $num) {
            $res[] = $t;
        }
    }
    return $res;
}

/***获取优惠券类型信息(带分页) bylu
 * @param string $cou_type 优惠券类型 1:注册送,2:购物送,3:全场送,4:会员送  默认返回所有类型数据
 * @param string $ru_id 商家ID,默认显示所有商家和平台发放的 优惠券;
 * @return array
 */
function get_coupons_type_info_res($filter = [], $cou_type)
{
    $res = Coupons::whereRaw(1);

    if ($cou_type) {
        $res = $res->whereIn('cou_type', $cou_type);
    }

    if (!empty($filter['ru_id'])) {
        $res = $res->where('ru_id', $filter['ru_id']);
    }

    if (!empty($filter['cou_name'])) {
        $res = $res->where('cou_name', 'like', '%' . $filter['cou_name'] . '%');
    }

    if ($filter['ru_id'] == 0) {
        //区分商家和自营
        if (!empty($filter['seller_list'])) {
            $res = $res->where('ru_id', '>', 0);
        } else {
            $res = $res->where('ru_id', 0);
        }
    }

    if ($filter['review_status']) {
        $res = $res->where('review_status', $filter['review_status']);
    }

    //卖场
    if ($filter['rs_id'] > 0) {
        $res = app(DscRepository::class)->getWhereRsid($res, 'ru_id', $filter['rs_id']);
    }

    return $res;
}

function get_coupons_type_info($cou_type = "1,2,3,4,5,6", $ru_id = 0)
{
    $result = get_filter();

    if ($result === false) {
        /* 过滤条件 */

        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'cou_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['seller_list'] = isset($_REQUEST['seller_list']) && !empty($_REQUEST['seller_list']) ? 1 : 0;  //商家和自营订单标识
        $filter['review_status'] = empty($_REQUEST['review_status']) ? 0 : intval($_REQUEST['review_status']);
        $filter['cou_name'] = isset($_REQUEST['cou_name']) && $_REQUEST['cou_name'] ? addslashes(trim($_REQUEST['cou_name'])) : '';
        $filter['cou_type'] = isset($_REQUEST['cou_type']) && $_REQUEST['cou_type'] ? addslashes_deep($_REQUEST['cou_type']) : $cou_type;
        $filter['ru_id'] = isset($_REQUEST['ru_id']) && !empty($_REQUEST['ru_id']) ? intval($_REQUEST['ru_id']) : $ru_id;

        $cou_type = app(BaseRepository::class)->getExplode($filter['cou_type']);

        //卖场 start
        $filter['rs_id'] = empty($_REQUEST['rs_id']) ? 0 : intval($_REQUEST['rs_id']);
        $adminru = get_admin_ru_id();
        if ($adminru['rs_id'] > 0) {
            $filter['rs_id'] = $adminru['rs_id'];
        }
        //卖场 end

        //管理员查询的权限 -- 店铺查询 start
        $filter['store_search'] = !isset($_REQUEST['store_search']) ? -1 : intval($_REQUEST['store_search']);
        $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
        $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';

        $record_count = get_coupons_type_info_res($filter, $cou_type);
        $filter['record_count'] = $record_count->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        $res = get_coupons_type_info_res($filter, $cou_type);

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order']);

        if ($filter['start'] > 0) {
            $res = $res->skip($filter['start']);
        }

        if ($filter['page_size'] > 0) {
            $res = $res->take($filter['page_size']);
        }

        //set_filter($filter);
    } else {
        //
        $filter = $result['filter'];
    }

    $res = app(BaseRepository::class)->getToArrayGet($res);

    $lang_coupons = trans('coupons');
    $arr = [];
    if ($res) {
        $CouponsLib = app(CouponsService::class);

        foreach ($res as $row) {
            $row['type'] = $row['cou_type'];
            $row['cou_type'] = $row['cou_type'] == 1 ? '<span class="green"> ' . $lang_coupons['vouchers_login'] . '</span>' : ($row['cou_type'] == 2 ? '<span class="blue">' . $lang_coupons['vouchers_shoping'] . '</span>' : ($row['cou_type'] == 3 ? '<span class="red">' . $lang_coupons['vouchers_all'] . '</span>' : ($row['cou_type'] == 4 ? '<span class="org">' . $lang_coupons['vouchers_user'] . '</span>' : ($row['cou_type'] == 5 ? '<span class="yellow">' . $lang_coupons['vouchers_shipping'] . '</span>' : ($row['cou_type'] == 6 ? '<span class="green">' . $lang_coupons['vouchers_shop_conllent'] . '</span>' : '')))));
            $row['user_name'] = app(MerchantCommonService::class)->getShopName($row['ru_id'], 1);
            $row['cou_start_time'] = local_date('Y-m-d', $row['cou_start_time']);
            $row['cou_is_time'] = $row['cou_end_time'] > gmtime() ? '<span class="green"> ' . $lang_coupons['not_overdue'] . '</span>' : '<span class="red">' . $lang_coupons['overdue'] . '</span>';
            $row['cou_end_time'] = local_date('Y-m-d', $row['cou_end_time']);
            $row['cou_is_use'] = (isset($row['cou_is_use']) && $row['cou_is_use'] == 0) ? '<span class="green">' . $lang_coupons['not_use'] . '</span>' : '<span class="red">' . $lang_coupons['had_use'] . '</span>';

            $region_arr = $CouponsLib->getCouponsRegionList($row['cou_id']);
            $row['free_value_name'] = $region_arr['free_value_name'];

            $arr[] = $row;
        }
    }

    $arr = ['item' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

    return $arr;
}

/**
 * 获取当前优惠券的不包邮地区
 * @param $cou_id 优惠券ID
 * @return $cou_region 不包邮地区
 */
function get_coupons_region($cou_id = 0)
{
    $region_list = CouponsRegion::where('cou_id', $cou_id)->value('region_list');
    return $region_list;
}

/**
 * 获取当前商品可用的优惠券
 * @param $goods_id 商品ID
 */
function get_goods_coupons_list($goods_id = 0)
{
    if (empty($goods_id)) {
        return [];
    }

    $goods_id = intval($goods_id);

    $time = gmtime();

    $ru_id = Goods::where('goods_id', $goods_id)->value('user_id');

    $where = [
        'goods_id' => $goods_id,
        'time' => $time,
        'ru_id' => $ru_id
    ];
    $res = CouponsUser::whereHas('getCoupons', function ($query) use ($where) {
        $query = $query->where('cou_end_time', '>', $where['time'])
            ->where(function ($query) use ($where) {
                $query->where('cou_goods', 0)
                    ->orWhereRaw("FIND_IN_SET(" . $where['goods_id'] . ", cou_goods)");
            });

        $query->whereIn('cou_type', [3, 4])
            ->where('ru_id', $where['ru_id']);
    });

    $res = $res->with('getCoupons');

    $res = $res->groupBy('cou_id');

    $res = app(BaseRepository::class)->getToArrayGet($res);

    if ($res) {
        foreach ($res as $key => $row) {
            $row = app(BaseRepository::class)->getArrayMerge($row['get_coupons'], $row);

            $res[$key] = $row;
        }
    }

    return $res;
}

/*
  * 获取当前商品可用的优惠券
 * @param $goods_id 商品ID
 */
function get_new_coup($user_id, $goods_id, $ru_id, $size = 10)
{

    //店铺优惠券 by wanglu
    $time = gmtime();

    $row = Coupons::select('*')
        ->where('review_status', 3)
        ->where('ru_id', $ru_id)
        ->where('cou_end_time', '>', $time)
        ->where(function ($query) {
            $query->orWhere('cou_type', 3)
                ->orWhere('cou_type', 4);
        })
        ->whereRaw("((instr(cou_goods, $goods_id)  or (cou_goods = 0)))");

    //获取会员等级id
    $rank_id = session('user_rank', 0);
    if ($rank_id > 0) {
        //设置优惠券的领取会员等级
        $row = $row->whereraw("CONCAT(',', cou_ok_user, ',') LIKE '%" . $rank_id . "%'");
    }

    $row = $row->with([
        'getCouponsUser' => function ($query) use ($user_id) {
            $query->selectRaw('cou_id, count(*) as user_num')->where('user_id', $user_id);
        }
    ]);

    $res = $row->orderBy('cou_id', 'DESC')
        ->limit($size)
        ->get();

    $res = $res ? $res->toArray() : [];

    if ($res) {
        foreach ($res as $key => $value) {
            $res[$key]['cou_end_time'] = local_date('Y.m.d', $value['cou_end_time']);
            $res[$key]['cou_start_time'] = local_date('Y.m.d', $value['cou_start_time']);
            // 能否领取 优惠劵总张数 1 不能 0 可以领取
            $cou_num = CouponsUser::where('cou_id', $value['cou_id'])->count();
            $res[$key]['enable_ling'] = (!empty($cou_num) && $cou_num >= $value['cou_total']) ? 1 : 0;
            // 是否领取
            if ($user_id > 0) {
                $user_num = $value['get_coupons_user']['user_num'] ?? 0;
                if ($user_num > 0 && $value['cou_user_num'] <= $user_num) {
                    $res[$key]['cou_is_receive'] = 1;
                    unset($res[$key]);
                } else {
                    $res[$key]['cou_is_receive'] = 0;
                }
            }
        }
        $res = collect($res)->values()->all();
    }
    return $res;
}

//计算运费总金额
function available_shipping_fee($ru_list)
{
    $shipping_fee = 0;
    if ($ru_list) {
        foreach ($ru_list as $k => $v) {
            $shipping_fee += $v['shipping']['shipping_fee'];
        }
    }

    $arr['shippingFee'] = $shipping_fee;
    $arr['shipping_fee'] = price_format($shipping_fee, false);
    return $arr;
}

/*
* 比对购物车内商品与储值卡使用条件是否一致
*/
function comparison_goods($cart_goods, $spec_goods)
{
    $error = 0;
    if ($cart_goods && $spec_goods) {
        $spec_goods = explode(',', $spec_goods);
        foreach ($cart_goods as $v) {
            if (!in_array($v['goods_id'], $spec_goods)) {
                $error += 1;
            }
        }
    }

    if ($error > 0) {
        return false;
    } else {
        return true;
    }
}

/*
* 比对购物车内商品分类与储值卡使用条件是否一致
*/
function comparison_cat($cart_goods, $spec_cat)
{
    $CategoryLib = app(CategoryService::class);

    $error = 0;

    if ($spec_cat) {
        $spec_cat = explode(',', $spec_cat);
        foreach ($spec_cat as $v) {
            $cat_keys = $CategoryLib->getCatListChildren($v);
            $cat[] = array_unique(array_merge([$v], $cat_keys));
        }

        foreach ($cat as $v) {
            foreach ($v as $val) {
                $arr[] = $val;
            }
        }

        $arr = array_unique($arr);

        foreach ($cart_goods as $v) {
            $v['cat_id'] = $v['cat_id'] ?? [];
            if ($v['cat_id'] && !in_array($v['cat_id'], $arr)) {
                $error += 1;
            }
        }
    }

    if ($error > 0) {
        return false;
    } else {
        return true;
    }
}

/**
 * 后台管理员ID
 */
function get_admin_name()
{
    $self = explode("/", substr(PHP_SELF, 1));
    $count = count($self);

    $admin_name = '';
    if ($count > 1) {
        $real_path = $self[$count - 2];
        if ($real_path == ADMIN_PATH) {
            $admin_name = session('admin_name');
        } elseif ($real_path == SELLER_PATH) {
            $admin_name = session('seller_name');
        } elseif ($real_path == SUPPLLY_PATH) {
            $supplierEnabled = app(CommonRepository::class)->judgeSupplierEnabled();
            if ($supplierEnabled) {
                $admin_name = session('supply_name');
            }
        }
    }

    return $admin_name;
}

/**
 * 优惠活动
 * 查询全场通用优惠活动品牌值列表
 */
function act_range_ext_brand($act_range_ext, $userFav_type, $act_range)
{
    if ($act_range_ext) {
        if ($userFav_type == 1 && $act_range == FAR_BRAND) {
            $id_list = explode(',', $act_range_ext);

            $brand = Brand::selectRaw('GROUP_CONCAT(brand_id) AS brand_id')->whereIn('brand_id', $id_list);
            $brand = app(BaseRepository::class)->getToArrayFirst($brand);

            $brand = $brand ? $brand['brand_id'] : 0;

            $id_list = !empty($brand) ? array_merge($id_list, $brand) : '';
            $id_list = array_unique($id_list);
            $act_range_ext = implode(",", $id_list);
        }
    }

    return $act_range_ext;
}

/**
 * 检测
 * 购物流程商品配送方式
 */
function get_flowdone_goods_list($cart_goods_list, $tmp_shipping_id_arr)
{
    if ($cart_goods_list && $tmp_shipping_id_arr) {
        foreach ($cart_goods_list as $key => $val) {
            foreach ($tmp_shipping_id_arr as $k => $v) {
                if ($v[1] > 0 && $val['ru_id'] == $v[0]) {
                    $cart_goods_list[$key]['tmp_shipping_id'] = $v[1];
                }
            }
        }
    }

    return $cart_goods_list;
}

/**
 * 订单线上付款日志
 *
 * @param int $id
 * @param int $type
 * @param null $is_paid
 * @return mixed
 */
function get_pay_log($id = 0, $type = 0, $is_paid = null)
{
    $res = PayLog::whereRaw(1);

    if ($type == 1) {
        $res = $res->where('order_id', $id)->where('order_type', PAY_ORDER);
    } else {
        $res = $res->where('log_id', $id);
    }

    if (is_null($is_paid) && !is_array($is_paid)) {
        $res = $res->where('is_paid', 0);
    } elseif (!is_null($is_paid) && !is_array($is_paid)) {
        $res = $res->where('is_paid', $is_paid);
    }

    $res = app(BaseRepository::class)->getToArrayFirst($res);

    return $res;
}

/*
* 配送区域ID start
*/
function region_parent($region_id)
{
    $result = '';
    $parent_id = Region::where('region_id', $region_id)->value('parent_id');

    if ($parent_id) {
        if ($parent_id <= 1) {
            $result .= $parent_id . ",";
        } else {
            $result .= $parent_id . ",";
            $result .= region_parent($parent_id);
        }

        $result = app(DscRepository::class)->delStrComma($result);
    }

    return $result;
}

function region_children($region_id)
{
    $result = '';
    $res = Region::select('region_id')
        ->where('parent_id', $region_id);
    $res = app(BaseRepository::class)->getToArrayGet($res);

    $result = false;
    if (!empty($res)) {
        foreach ($res as $v) {
            $result .= $v['region_id'] . ',';
            $result .= region_children($v['region_id']);
        }

        $result = app(DscRepository::class)->delStrComma($result);
    }

    return $result;
}

/*
* 配送区域ID end
*/

/**
 * 重写获取属性组合函数(效率较attr_group稍高，且处理了一组属性无法分组的问题)
 * @return array
 */
function attr_group($arr = [])
{
    $group = []; //最终数组

    if (!empty($arr)) {
        $fresh_arr = [];
        foreach ($arr as $key => $val) {
            $fresh_arr[] = $val;
        }
        $arr = $fresh_arr;
        $t = 0; //计数器
        $num = 1; //元素组合可能性
        $pin = []; //指针
        $con = []; //子组成员数量
        foreach ($arr as $key => $val) {
            $pin[$key] = 0;
            $con[$key] = count($val) - 1;
            $num *= count($val);
        }
        $init = count($arr) - 1; //初始长度
        while ($t < $num) {
            //加入一组数据
            $new = [];
            foreach ($arr as $key => $val) {
                $new[] = $val[$pin[$key]];
            }
            $group[] = $new;
            //下一组
            $i = $init;
            $pin[$i] += 1;
            while ($pin[$i] > $con[$i]) {
                $pin[$i] = 0;
                if ($i > 0 && isset($pin[$i])) {
                    $i--;
                    $pin[$i] += 1;
                }
            }
            $t++;
        }
    }

    return $group;
}

/**
 * 获取卖场筛选条件（包含平台）
 * $field  查询表字段
 * $rs_id  卖场id
 * 是否满足条件
 */
function get_rs_goods_where($field = '', $rs_id = 0)
{
    //判断是否是卖场，是筛选商品
    $where = '';
    if ($rs_id > 0 && $field != '') {
        $region_id = RsRegion::where('rs_id', $rs_id)->value('region_id');

        if (!empty($region_id)) {
            $user_ids = MerchantsShopInformation::select('user_id')
                ->where('region_id', $region_id);
            $user_ids = app(BaseRepository::class)->getToArrayGet($user_ids);
            $user_ids = app(BaseRepository::class)->getKeyPluck($user_ids, 'user_id');

            if (!empty($user_ids)) {
                $where = " AND ($field " . db_create_in($user_ids) . " OR {$field} = 0 ) ";
            } else {
                $where = " AND $field = 0 ";
            }
        } else {
            $where = " AND $field = 0 ";
        }
    }
    return $where;
}

/* 获取卖场筛选条件（排除平台） */
function get_rs_null_where($field = '', $rs_id = 0)
{
    $where = '';
    if ($rs_id > 0 && $field != '') {
        $region_id = RsRegion::where('rs_id', $rs_id)->value('region_id');

        if (!empty($region_id)) {
            $user_ids = MerchantsShopInformation::select('user_id')
                ->where('region_id', $region_id);
            $user_ids = app(BaseRepository::class)->getToArrayGet($user_ids);
            $user_ids = app(BaseRepository::class)->getKeyPluck($user_ids, 'user_id');

            if (!empty($user_ids)) {
                $where .= " AND $field " . db_create_in($user_ids);
            } else {
                $where .= " AND $field IS NULL ";
            }
        } else {
            $where .= " AND $field IS NULL ";
        }
    }
    return $where;
}
