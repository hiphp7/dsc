<?php

namespace App\Modules\Admin\Controllers;

use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Wholesale\GoodsManageService;

/**
 * 地区切换程序
 */
class WholesaleGoodsProdutsBatchController extends InitController
{
    protected $dscRepository;
    protected $goodsManageService;
    protected $merchantCommonService;

    public function __construct(
        DscRepository $dscRepository,
        GoodsManageService $goodsManageService,
        MerchantCommonService $merchantCommonService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->goodsManageService = $goodsManageService;
        $this->merchantCommonService = $merchantCommonService;
    }

    public function index()
    {
        load_helper(['goods', 'wholesale']);

        /* ------------------------------------------------------ */
        //-- 批量上传
        /* ------------------------------------------------------ */

        if ($_REQUEST['act'] == 'add') {
            /* 检查权限 */
            admin_priv('goods_manage');

            $this->smarty->assign('menu_select', array('action' => '02_cat_and_goods', 'current' => 'produts_batch'));
            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $model = isset($_REQUEST['model']) ? intval($_REQUEST['model']) : 0;
            $warehouse_id = isset($_REQUEST['warehouse_id']) ? intval($_REQUEST['warehouse_id']) : 0;

            if ($goods_id > 0) {
                $this->smarty->assign('action_link', array('text' => lang('admin/wholesale_goods_produts_batch.back_details'), 'href' => 'goods.php?act=product_list&goods_id=' . $goods_id));
            }

            $lang_list = array(
                'UTF8' => $GLOBALS['_LANG']['charset']['utf8'],
                'GB2312' => $GLOBALS['_LANG']['charset']['zh_cn'],
                'BIG5' => $GLOBALS['_LANG']['charset']['zh_tw'],
            );

            /* 取得可选语言 */
            $download_list = $this->dscRepository->getDdownloadTemplate(resource_path('lang'));

            $this->smarty->assign('lang_list', $lang_list);
            $this->smarty->assign('download_list', $download_list);
            $this->smarty->assign('goods_id', $goods_id);
            $this->smarty->assign('model', $model);
            $this->smarty->assign('warehouse_id', $warehouse_id);

            $attribute_list = $this->get_attribute_list($goods_id);
            $this->smarty->assign('attribute_list', $attribute_list);

            $goods_date = array('goods_name');
            $where = "goods_id = '$goods_id'";
            $goods_name = get_table_date('goods', $where, $goods_date, 2);
            $this->smarty->assign('goods_name', $goods_name);

            /* 参数赋值 */
            $ur_here = lang('admin/wholesale_goods_produts_batch.batch_upload');
            $this->smarty->assign('ur_here', $ur_here);

            /* 显示模板 */
            return $this->smarty->display('wholesale_goods_produts_batch.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 批量上传：处理
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'upload') {
            /* 检查权限 */
            admin_priv('goods_manage');

            $this->smarty->assign('menu_select', array('action' => '02_cat_and_goods', 'current' => 'produts_area_batch'));

            $goods_list = [];
            $attr_names = [];
            //ecmoban模板堂 --zhuo start 仓库
            if ($_FILES['file']['name']) {

                //获得属性的个数;
                $attr_names = file($_FILES['file']['tmp_name']);
                $attr_names = explode(',', $attr_names[0]);
                $end = -2;

                $attr_names = array_slice($attr_names, 6, $end);
                foreach ($attr_names as $k => $v) {
                    $attr_names[$k] = dsc_iconv('GBK', 'UTF8', $v);
                }

                $attr_num = count($attr_names);

                $line_number = 0;
                $field_list = array_keys($GLOBALS['_LANG']['upload_product']); // 字段列表
                for ($i = 0; $i < $attr_num; $i++) {
                    $field_list[] = 'goods_attr' . $i;
                }

                $field_list[] = 'product_number';
                $field_list[] = 'product_sn';

                $_POST['charset'] = 'GB2312';
                $data = file($_FILES['file']['tmp_name']);

                if (count($data) > 0) {
                    foreach ($data as $line) {
                        // 跳过第一行
                        if ($line_number == 0) {
                            $line_number++;
                            continue;
                        }

                        // 转换编码
                        if (($_POST['charset'] != 'UTF8') && (strpos(strtolower(EC_CHARSET), 'utf') === 0)) {
                            $line = dsc_iconv($_POST['charset'], 'UTF8', $line);
                        }

                        // 初始化
                        $arr = array();
                        $buff = '';
                        $quote = 0;
                        $len = strlen($line);
                        for ($i = 0; $i < $len; $i++) {
                            $char = $line[$i];

                            if ('\\' == $char) {
                                $i++;
                                $char = $line[$i];

                                switch ($char) {
                                    case '"':
                                        $buff .= '"';
                                        break;
                                    case '\'':
                                        $buff .= '\'';
                                        break;
                                    case ',':
                                        $buff .= ',';
                                        break;
                                    default:
                                        $buff .= '\\' . $char;
                                        break;
                                }
                            } elseif ('"' == $char) {
                                if (0 == $quote) {
                                    $quote++;
                                } else {
                                    $quote = 0;
                                }
                            } elseif (',' == $char) {
                                if (0 == $quote) {
                                    if (!isset($field_list[count($arr)])) {
                                        continue;
                                    }
                                    $field_name = $field_list[count($arr)];
                                    $arr[$field_name] = trim($buff);
                                    $buff = '';
                                    $quote = 0;
                                } else {
                                    $buff .= $char;
                                }
                            } else {
                                $buff .= $char;
                            }

                            if ($i == $len - 1) {
                                if (!isset($field_list[count($arr)])) {
                                    continue;
                                }
                                $field_name = $field_list[count($arr)];
                                $arr[$field_name] = trim($buff);
                            }
                        }
                        $goods_list[] = $arr;
                    }

                    //格式化商品数据 bylu;
                    $goods_list = $this->goodsManageService->getWholesaleProdutsList2($goods_list, $attr_num);
                }
            }

            session([
                'goods_list' => $goods_list
            ]);

            $this->smarty->assign('full_page', 2);
            $this->smarty->assign('page', 1);
            $this->smarty->assign('attr_names', $attr_names); //属性名称;

            /* 显示模板 */
            $this->smarty->assign('ur_here', lang('admin/wholesale_goods_produts_batch.batch_upload'));
            return $this->smarty->display('wholesale_goods_produts_batch_add.dwt');
        }


        /* ------------------------------------------------------ */
        //-- 动态添加数据入库;
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'ajax_insert') {
            /* 检查权限 */
            admin_priv('goods_manage');

            $result = array('list' => array(), 'is_stop' => 0);
            $page = !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_size = isset($_REQUEST['page_size']) ? intval($_REQUEST['page_size']) : 1;

            /* 设置最长执行时间为5分钟 */
            @set_time_limit(300);

            if (session()->has('goods_list') && session('goods_list')) {
                $goods_list = session('goods_list');
                $goods_list = $this->dsc->page_array($page_size, $page, $goods_list);

                $result['list'] = $goods_list['list'][0];
                $result['page'] = $goods_list['filter']['page'] + 1;
                $result['page_size'] = $goods_list['filter']['page_size'];
                $result['record_count'] = $goods_list['filter']['record_count'];
                $result['page_count'] = $goods_list['filter']['page_count'];

                $result['is_stop'] = 1;
                if ($page > $goods_list['filter']['page_count']) {
                    $result['is_stop'] = 0;
                }

                $other['goods_id'] = $result['list']['goods_id'];
                $other['goods_attr'] = $result['list']['goods_attr'];

                $other['product_number'] = $result['list']['product_number'];
                $other['product_sn'] = $result['list']['product_sn'];

                //查询数据是否已经存在;
                $sql = "SELECT product_id FROM " . $GLOBALS['dsc']->table('wholesale_products') . " WHERE goods_id = '" . $result['list']['goods_id'] . "'" .
                    " AND goods_attr = '" . $result['list']['goods_attr'] . "'";

                if ($GLOBALS['db']->getOne($sql, true)) {
                    $this->db->autoExecute($this->dsc->table('wholesale_products'), $other, 'UPDATE', "goods_id = '" . $result['list']['goods_id'] . "' AND goods_attr = '" . $result['list']['goods_attr'] . "'");
                    $result['status_lang'] = '<span style="color: red;">' . lang('admin/wholesale_goods_produts_batch.update_success') . '</span>';
                } else {
                    $other['admin_id'] = session('admin_id');

                    $this->db->autoExecute($this->dsc->table('wholesale_products'), $other, 'INSERT');
                    $result['status_lang'] = '<span style="color: red;">' . lang('admin/wholesale_goods_produts_batch.add_success') . '</span>';
                }
            }
            return response()->json($result);
        }

        /* ------------------------------------------------------ */
        //-- 下载文件
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'download') {
            /* 检查权限 */
            admin_priv('goods_manage');

            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
            $model = isset($_REQUEST['model']) ? intval($_REQUEST['model']) : 0;
            $warehouse_id = isset($_REQUEST['warehouse_id']) ? intval($_REQUEST['warehouse_id']) : 0;
            $goods_attr = isset($_REQUEST['goods_attr']) ? explode(',', $_REQUEST['goods_attr']) : array();

            // 文件标签
            // Header("Content-type: application/octet-stream");
            header("Content-type: application/vnd.ms-excel; charset=utf-8");
            Header("Content-Disposition: attachment; filename=goods_produts_list" . $goods_id . ".csv");

            // 下载
            if ($_GET['charset'] != $GLOBALS['_CFG']['lang']) {
                $lang_file = '../languages/' . $_GET['charset'] . '/admin/goods_produts_warehouse_batch.php';
                if (file_exists($lang_file)) {
                    unset($GLOBALS['_LANG']['upload_product']);
                    require($lang_file);
                }
            }
            if (isset($GLOBALS['_LANG']['upload_product'])) {
                /* 创建字符集转换对象 */
                if ($_GET['charset'] == 'zh-CN' || $_GET['charset'] == 'zh-TW') {
                    $to_charset = $_GET['charset'] == 'zh-CN' ? 'GB2312' : 'BIG5';
                    $data = join(',', $GLOBALS['_LANG']['upload_product']);

                    /* 获取商品规格列表 */
                    $attribute = $this->goodsManageService->wholesaleGoodsSpecificationsList($goods_id);
                    if (empty($attribute)) {
                        $link[] = array('href' => 'goods.php?act=edit&goods_id=' . $goods_id, 'text' => $GLOBALS['_LANG']['edit_goods']);
                        return sys_msg($GLOBALS['_LANG']['not_exist_goods_attr'], 1, $link);
                    }

                    foreach ($attribute as $attribute_value) {
                        //转换成数组
                        $_attribute[$attribute_value['attr_id']]['attr_values'][] = $attribute_value['attr_value'];
                        $_attribute[$attribute_value['attr_id']]['attr_id'] = $attribute_value['attr_id'];
                        $_attribute[$attribute_value['attr_id']]['attr_name'] = $attribute_value['attr_name'];
                    }

                    //获取属性名称 bylu;
                    foreach ($_attribute as $k => $v) {
                        $data .= ',' . $v['attr_name'];
                    }

                    $data .= lang('admin/wholesale_goods_produts_batch.stock');
                    $data .= lang('admin/wholesale_goods_produts_batch.item_number');

                    if ($goods_id) {
                        $goods_info = get_admin_goods_info($goods_id);
                        $goods_info['shop_name'] = $this->merchantCommonService->getShopName($goods_info['user_id'], 1);
                    } else {
                        $adminru = get_admin_ru_id();

                        $goods_info['user_id'] = $adminru['ru_id'];
                        $goods_info['shop_name'] = $this->merchantCommonService->getShopName($adminru['ru_id'], 1);
                    }

                    $attr_info = $this->get_list_download($goods_info['goods_sn'], '', $_attribute, count($_attribute), $model);
                    foreach ($attr_info as $k => $v) {
                        $data .= $goods_id . ',';
                        $data .= $goods_info['goods_name'] . ',';
                        $data .= $goods_info['goods_sn'] . ',';
                        $data .= $goods_info['shop_name'] . ',';
                        $data .= $goods_info['user_id'] . ',';
                        $data .= $attr_info[$k]['region_name'] . ',';
                        $data .= implode(',', $v['attr_value']) . ',';

                        $data .= $attr_info[$k]['product_number'] . ',';
                        $data .= $attr_info[$k]['product_sn'] . "\t\n";
                    }

                    echo dsc_iconv(EC_CHARSET, $to_charset, $data);
                } else {
                    echo join(',', $GLOBALS['_LANG']['upload_product']);
                }
            } else {
                echo 'error: ' . $GLOBALS['_LANG']['upload_product'] . ' not exists';
            }
        }
    }

    private function get_list_download($goods_sn = '', $warehouse_info = array(), $attr_info, $attr_num, $model = 0)
    {
        $arr = array();
        //0:默认模式 1:仓库模式 2:地区模式
        if ($model == 0) {
            //格式化数组;
            if ($attr_info) {
                foreach ($attr_info as $k => $v) {
                    foreach ($v as $k2 => $v2) {
                        if ($k2 == 'attr_values') {
                            $attr[] = $v2;
                        }
                    }
                }
            }

            if ($attr) {
                $comb = combination(array_keys($attr), $attr_num);
                $res = array();
                foreach ($comb as $r) {
                    $t = array();
                    foreach ($r as $k) {
                        $t[] = $attr[$k];
                    }
                    $res = array_merge($res, attr_group($t));
                }
                //组合数据;
                foreach ($res as $k => $v) {
                    $arr[$k]['goods_sn'] = $goods_sn;
                    $arr[$k]['region_name'] = '默认模式';
                    $arr[$k]['attr_value'] = $v;

                    if ($GLOBALS['_CFG']['goods_attr_price'] == 1) {
                        $arr[$k]['product_price'] = '';
                    }

                    $arr[$k]['product_number'] = '';
                    $arr[$k]['product_warn_number'] = '';
                    $arr[$k]['product_sn'] = '';
                    $arr[$k]['bar_code'] = '';
                }
            }
        }

        return $arr;
    }

    private function get_attribute_list($goods_id = 0)
    {
        $sql = "SELECT a.attr_id, a.attr_name FROM " . $GLOBALS['dsc']->table('goods_attr') . " AS ga " .
            " LEFT JOIN " . $GLOBALS['dsc']->table('attribute') . " as a ON ga.attr_id = a.attr_id" .
            " WHERE ga.goods_id = '$goods_id' group by ga.attr_id ORDER BY a.sort_order, ga.attr_id ASC";
        $res = $GLOBALS['db']->getAll($sql);

        $arr = array();
        foreach ($res as $key => $row) {
            $arr[$key]['attr_name'] = $row['attr_name'];
            $arr[$key]['goods_attr'] = $this->get_goods_attr_list($row['attr_id'], $goods_id);
        }

        return $arr;
    }

    private function get_goods_attr_list($attr_id = 0, $goods_id = 0)
    {
        $sql = "select goods_attr_id, attr_value from " . $GLOBALS['dsc']->table('goods_attr') . " where goods_id = '$goods_id' and attr_id = '$attr_id' order by goods_attr_id asc";
        $res = $GLOBALS['db']->getAll($sql);

        $arr = array();
        foreach ($res as $key => $row) {
            $arr[$key]['goods_attr_id'] = $row['goods_attr_id'];
            $arr[$key]['attr_value'] = $row['attr_value'];
        }

        return $arr;
    }
}
