<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Phpzip;
use App\Models\GoodsAttr;
use App\Models\Region;
use App\Repositories\Common\BaseRepository;
use App\Services\Goods\GoodsExportManageService;

/**
 *
 */
class GoodsExportController extends InitController
{
    protected $baseRepository;
    protected $goodsExportManageService;

    public function __construct(
        BaseRepository $baseRepository,
        GoodsExportManageService $goodsExportManageService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->goodsExportManageService = $goodsExportManageService;
    }

    public function index()
    {
        load_helper('goods', 'admin');

        $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => '14_goods_export']);

        if ($_REQUEST['act'] == 'goods_export') {
            /* 检查权限 */
            admin_priv('goods_export');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['14_goods_export']);
            $this->smarty->assign('goods_type_list', goods_type_list(0, 0, 'array'));
            $goods_fields = my_array_merge($GLOBALS['_LANG']['custom'], $this->goodsExportManageService->getAttributes());
            $data_format_array = [
                'dscmall' => $GLOBALS['_LANG']['export_dscmall'],
                'taobao' => $GLOBALS['_LANG']['export_taobao'],
                'custom' => $GLOBALS['_LANG']['export_custom'],
            ];
            $this->smarty->assign('data_format', $data_format_array);
            $this->smarty->assign('goods_fields', $goods_fields);

            $goods_id = '';
            set_default_filter($goods_id); //设置默认筛选

            return $this->smarty->display('goods_export.dwt');
        } elseif ($_REQUEST['act'] == 'act_export_taobao') {
            /* 检查权限 */
            admin_priv('goods_export');

            $zip = new Phpzip;

            $where = $this->goodsExportManageService->getExportWhereSql($_POST);

            $goods_class = intval($_POST['goods_class']);
            $post_express = floatval($_POST['post_express']);
            $express = floatval($_POST['express']);
            $ems = floatval($_POST['ems']);

            $shop_province = '""';
            $shop_city = '""';
            if ($GLOBALS['_CFG']['shop_province'] || $GLOBALS['_CFG']['shop_city']) {
                $region_id_attr = $this->baseRepository->getExplode($GLOBALS['_CFG']['shop_province'] . "',  '" . $GLOBALS['_CFG']['shop_city']);
                $res = Region::select('region_id', 'region_name')->whereIn('region_id', $region_id_attr);
                $arr = $this->baseRepository->getToArrayGet($res);

                if ($arr) {
                    if (count($arr) == 1) {
                        if ($arr[0]['region_id'] == $GLOBALS['_CFG']['shop_province']) {
                            $shop_province = '"' . $arr[0]['region_name'] . '"';
                        } else {
                            $shop_city = '"' . $arr[0]['region_name'] . '"';
                        }
                    } else {
                        if ($arr[0]['region_id'] == $GLOBALS['_CFG']['shop_province']) {
                            $shop_province = '"' . $arr[0]['region_name'] . '"';
                            $shop_city = '"' . $arr[1]['region_name'] . '"';
                        } else {
                            $shop_province = '"' . $arr[1]['region_name'] . '"';
                            $shop_city = '"' . $arr[0]['region_name'] . '"';
                        }
                    }
                }
            }

            $sql = "SELECT g.goods_id, g.goods_name, g.shop_price, g.goods_number, g.goods_desc, g.goods_img " .
                " FROM " . $this->dsc->table('goods') . " AS g " . $where;

            $res = $this->db->query($sql);

            /* csv文件数组 */
            $goods_value = ['goods_name' => '""', 'goods_class' => $goods_class, 'shop_class' => 0, 'new_level' => 5, 'province' => $shop_province, 'city' => $shop_city, 'sell_type' => '"b"', 'shop_price' => 0, 'add_price' => 0, 'goods_number' => 0, 'die_day' => 14, 'load_type' => 1, 'post_express' => $post_express, 'ems' => $ems, 'express' => $express, 'pay_type' => 2, 'allow_alipay' => 1, 'invoice' => 0, 'repair' => 0, 'resend' => 1, 'is_store' => 0, 'window' => 0, 'add_time' => '"1980-1-1  0:00:00"', 'story' => '""', 'goods_desc' => '""', 'goods_img' => '""', 'goods_attr' => '""', 'group_buy' => 0, 'group_buy_num' => 0, 'template' => 0, 'discount' => 0, 'modify_time' => '""', 'upload_status' => 100, 'img_status' => 1];

            $content = implode(",", $GLOBALS['_LANG']['taobao']) . "\n";

            foreach ($res as $row) {
                $goods_value['goods_name'] = '"' . $row['goods_name'] . '"';
                $goods_value['shop_price'] = $row['shop_price'];
                $goods_value['goods_number'] = $row['goods_number'];
                $goods_value['goods_desc'] = $this->goodsExportManageService->replaceSpecialChar($row['goods_desc']);
                $goods_value['goods_img'] = '"' . $row['goods_img'] . '"';

                $content .= implode("\t", $goods_value) . "\n";

                /* 压缩图片 */
                if (!empty($row['goods_img']) && is_file(storage_public($row['goods_img']))) {
                    $zip->add_file(file_get_contents(storage_public($row['goods_img'])), $row['goods_img']);
                }
            }

            if (EC_CHARSET != 'utf-8') {
                $content = dsc_iconv(EC_CHARSET, 'utf-8', $content);
            }
            $zip->add_file("\xFF\xFE" . $this->goodsExportManageService->utf82u2($content), 'goods_list.csv');

            $filename = "goods_list.zip";
            return response()->streamDownload(function () use ($zip) {
                echo $zip->file();
            }, $filename);
        } elseif ($_REQUEST['act'] == 'act_export_taobao V4.3') {
            /* 检查权限 */
            admin_priv('goods_export');

            $zip = new Phpzip;

            $where = $this->goodsExportManageService->getExportWhereSql($_POST);

            $goods_class = intval($_POST['goods_class']);
            $post_express = floatval($_POST['post_express']);
            $express = floatval($_POST['express']);
            $ems = floatval($_POST['ems']);

            $shop_province = '""';
            $shop_city = '""';
            if ($GLOBALS['_CFG']['shop_province'] || $GLOBALS['_CFG']['shop_city']) {
                $region_id_attr = $this->baseRepository->getExplode($GLOBALS['_CFG']['shop_province'] . "',  '" . $GLOBALS['_CFG']['shop_city']);
                $res = Region::select('region_id', 'region_name')->whereIn('region_id', $region_id_attr);
                $arr = $this->baseRepository->getToArrayGet($res);

                if ($arr) {
                    if (count($arr) == 1) {
                        if ($arr[0]['region_id'] == $GLOBALS['_CFG']['shop_province']) {
                            $shop_province = '"' . $arr[0]['region_name'] . '"';
                        } else {
                            $shop_city = '"' . $arr[0]['region_name'] . '"';
                        }
                    } else {
                        if ($arr[0]['region_id'] == $GLOBALS['_CFG']['shop_province']) {
                            $shop_province = '"' . $arr[0]['region_name'] . '"';
                            $shop_city = '"' . $arr[1]['region_name'] . '"';
                        } else {
                            $shop_province = '"' . $arr[1]['region_name'] . '"';
                            $shop_city = '"' . $arr[0]['region_name'] . '"';
                        }
                    }
                }
            }

            $sql = "SELECT g.goods_id, g.goods_name, g.shop_price, g.goods_number, g.goods_desc, g.goods_img " .
                " FROM " . $this->dsc->table('goods') . " AS g " . $where;

            $res = $this->db->query($sql);

            /* csv文件数组 */
            $goods_value = ['goods_name' => '""', 'goods_class' => $goods_class, 'shop_class' => 0, 'new_level' => 5, 'province' => $shop_province, 'city' => $shop_city, 'sell_type' => '"b"', 'shop_price' => 0, 'add_price' => 0, 'goods_number' => 0, 'die_day' => 14, 'load_type' => 1, 'post_express' => $post_express, 'ems' => $ems, 'express' => $express, 'pay_type' => 2, 'allow_alipay' => 1, 'invoice' => 0, 'repair' => 0, 'resend' => 1, 'is_store' => 0, 'window' => 0, 'add_time' => '"1980-1-1  0:00:00"', 'story' => '""', 'goods_desc' => '""', 'goods_img' => '""', 'goods_attr' => '""', 'group_buy' => 0, 'group_buy_num' => 0, 'template' => 0, 'discount' => 0, 'modify_time' => '""', 'upload_status' => 100, 'img_status' => 1];

            $content = implode("\t", $GLOBALS['_LANG']['taobao']) . "\n";

            foreach ($res as $row) {
                $goods_value['goods_name'] = '"' . $row['goods_name'] . '"';
                $goods_value['shop_price'] = $row['shop_price'];
                $goods_value['goods_number'] = $row['goods_number'];
                $goods_value['goods_desc'] = $this->goodsExportManageService->replaceSpecialChar($row['goods_desc']);
                $goods_value['goods_img'] = '"' . $row['goods_img'] . '"';

                $content .= implode("\t", $goods_value) . "\n";

                /* 压缩图片 */
                if (!empty($row['goods_img']) && is_file(storage_public($row['goods_img']))) {
                    $zip->add_file(file_get_contents(storage_public($row['goods_img'])), $row['goods_img']);
                }
            }
            if (EC_CHARSET != 'utf-8') {
                $content = dsc_iconv(EC_CHARSET, 'utf-8', $content);
            }
            $zip->add_file("\xFF\xFE" . $this->goodsExportManageService->utf82u2($content), 'goods_list.csv');

            $filename = "goods_list.zip";
            return response()->streamDownload(function () use ($zip) {
                echo $zip->file();
            }, $filename);
        } /* 从淘宝导入数据 */
        elseif ($_REQUEST['act'] == 'import_taobao') {
            return $this->smarty->display('import_taobao.htm');
        } elseif ($_REQUEST['act'] == 'act_export_dscmall') {
            /* 检查权限 */
            admin_priv('goods_export');


            $zip = new Phpzip;

            /* 设置最长执行时间为5分钟 */
            @set_time_limit(300);


            $result = ['error' => 0, 'mark' => 0, 'message' => '', 'content' => '', 'done' => 2];
            $result['page_size'] = empty($_GET['page_size']) ? 10 : intval($_GET['page_size']);
            $result['page'] = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $result['total'] = isset($_GET['total']) ? intval($_GET['total']) : 1;

            if (isset($_POST) && !empty($_POST)) {
                $where = $this->goodsExportManageService->getExportWhereSql($_POST);
            } else {
                $filter = dsc_decode($_REQUEST['filter']);
                $arr = $this->goodsExportManageService->getExportStepWhereSql($filter);
                $where = $arr['where'];
            }

            $page_size = 50; // 默认50张/页
            $sql = "SELECT count(*) FROM " . $this->dsc->table('goods') . " AS g LEFT JOIN " . $this->dsc->table('brand') . " AS b " .
                "ON g.brand_id = b.brand_id" . $where;
            $count = $this->db->getOne($sql);

            /* 页数在许可范围内 */
            if ($result['page'] <= ceil($count / $result['page_size'])) {
                $start_time = gmtime(); //开始执行时间

                $sql = "SELECT g.*, b.brand_name as brandname " .
                    " FROM " . $this->dsc->table('goods') . " AS g LEFT JOIN " . $this->dsc->table('brand') . " AS b " .
                    "ON g.brand_id = b.brand_id" . $where;
                $res = $this->db->SelectLimit($sql, $result['page_size'], ($result['page'] - 1) * $result['page_size']);

                /* csv文件数组 */
                $goods_value = [];
                $goods_value['goods_name'] = '""';
                $goods_value['goods_sn'] = '""';
                $goods_value['brand_name'] = '""';
                $goods_value['market_price'] = 0;
                $goods_value['shop_price'] = 0;
                $goods_value['integral'] = 0;
                $goods_value['original_img'] = '""';
                $goods_value['goods_img'] = '""';
                $goods_value['goods_thumb'] = '""';
                $goods_value['keywords'] = '""';
                $goods_value['goods_brief'] = '""';
                $goods_value['goods_desc'] = '""';
                $goods_value['goods_weight'] = 0;
                $goods_value['goods_number'] = 0;
                $goods_value['warn_number'] = 0;
                $goods_value['is_best'] = 0;
                $goods_value['is_new'] = 0;
                $goods_value['is_hot'] = 0;
                $goods_value['is_on_sale'] = 1;
                $goods_value['is_alone_sale'] = 1;
                $goods_value['is_real'] = 1;
                $content = '"' . implode('","', $GLOBALS['_LANG']['dscmall']) . "\"\n";

                foreach ($res as $row) {
                    $goods_value['goods_name'] = '"' . $row['goods_name'] . '"';
                    $goods_value['goods_sn'] = '"' . $row['goods_sn'] . '"';
                    $goods_value['brand_name'] = '"' . $row['brandname'] . '"';
                    $goods_value['market_price'] = $row['market_price'];
                    $goods_value['shop_price'] = $row['shop_price'];
                    $goods_value['integral'] = $row['integral'];
                    $goods_value['original_img'] = '"' . $row['original_img'] . '"';
                    $goods_value['goods_img'] = '"' . $row['goods_img'] . '"';
                    $goods_value['goods_thumb'] = '"' . $row['goods_thumb'] . '"';
                    $goods_value['keywords'] = '"' . $row['keywords'] . '"';
                    $goods_value['goods_brief'] = '"' . htmlspecialchars($row['goods_brief']) . '"';
                    $goods_value['goods_desc'] = '"' . preg_replace("/\r\n/", '', str_replace('\r\n', '', htmlspecialchars($row['goods_desc']))) . '"';
                    $goods_value['goods_weight'] = $row['goods_weight'];
                    $goods_value['goods_number'] = $row['goods_number'];
                    $goods_value['warn_number'] = $row['warn_number'];
                    $goods_value['is_best'] = $row['is_best'];
                    $goods_value['is_new'] = $row['is_new'];
                    $goods_value['is_hot'] = $row['is_hot'];
                    $goods_value['is_on_sale'] = $row['is_on_sale'];
                    $goods_value['is_alone_sale'] = $row['is_alone_sale'];
                    $goods_value['is_real'] = $row['is_real'];

                    $content .= implode(",", $goods_value) . "\n";

                    /* 压缩图片 */
                    if (!empty($row['goods_img']) && is_file(storage_path($row['goods_img']))) {
                        $zip->add_file(file_get_contents(storage_path($row['goods_img'])), $row['goods_img']);
                    }
                    if (!empty($row['original_img']) && is_file(storage_path($row['original_img']))) {
                        $zip->add_file(file_get_contents(storage_path($row['original_img'])), $row['original_img']);
                    }
                    if (!empty($row['goods_thumb']) && is_file(storage_path($row['goods_thumb']))) {
                        $zip->add_file(file_get_contents(storage_path($row['goods_thumb'])), $row['goods_thumb']);
                    }
                }
                $charset = empty($_POST['charset']) ? 'UTF8' : trim($_POST['charset']);

                $zip->add_file(dsc_iconv(EC_CHARSET, $charset, $content), 'goods_list.csv');

                $filename = "goods_list.zip";
                return response()->streamDownload(function () use ($zip) {
                    echo $zip->file();
                }, $filename);
            }
        } elseif ($_REQUEST['act'] == 'act_export_step_search') {
            /* 检查权限 */
            admin_priv('goods_export');
            /* 设置最长执行时间为5分钟 */
            @set_time_limit(300);


            $filter = dsc_decode($_REQUEST['filter']);
            $arr = $this->goodsExportManageService->getExportStepWhereSql($filter);
            $where = $arr['where'];
            $page_size = 50; // 默认50张/页
            $sql = "SELECT count(*) FROM " . $this->dsc->table('goods') . " AS g LEFT JOIN " . $this->dsc->table('brand') . " AS b " .
                "ON g.brand_id = b.brand_id" . $where;
            $count = $this->db->getOne($sql);

            if (isset($_GET['start']) && $_GET['start'] == 1) {
                $title = $GLOBALS['_LANG']['goods_manage_date'];

                $silent = isset($silent) ? $silent : '';
                $data_cat = isset($data_cat) ? $data_cat : '';

                $result = ['error' => 0, 'mark' => 0, 'message' => '', 'content' => '', 'done' => 1, 'title' => $title, 'page_size' => $page_size,
                    'page' => 1, 'total' => 1, 'silent' => $silent, 'data_cat' => $data_cat,
                    'row' => ['new_page' => sprintf($GLOBALS['_LANG']['page_format'], 1),
                        'new_total' => sprintf($GLOBALS['_LANG']['total_format'], ceil($count / $page_size)),
                        'new_time' => $GLOBALS['_LANG']['wait'],
                        'cur_id' => 'time_1']];
                $result['total_page'] = ceil($count / $page_size);
                $result['filter'] = $arr['filter'];
                clear_cache_files();
                return response()->json($result);
            } else {
                $result = ['error' => 0, 'mark' => 0, 'message' => '', 'content' => '', 'done' => 2];
                $result['page_size'] = empty($_GET['page_size']) ? 50 : intval($_GET['page_size']);
                $result['page'] = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $result['total'] = isset($_GET['total']) ? intval($_GET['total']) : 1;
                $result['total_page'] = ceil($count / $result['page_size']);
                $result['row'] = ['new_page' => sprintf($GLOBALS['_LANG']['page_format'], 1),
                    'new_total' => sprintf($GLOBALS['_LANG']['total_format'], ceil($count / $page_size)),
                    'new_time' => $GLOBALS['_LANG']['wait'],
                    'cur_id' => 'time_1'];

                /* 页数在许可范围内 */
                if ($result['page'] <= ceil($count / $result['page_size'])) {
                    $start_time = gmtime(); //开始执行时间
                    $end_time = gmtime();
                    $result['row']['pre_id'] = 'time_' . $result['total'];
                    $result['row']['pre_time'] = ($end_time > $start_time) ? $end_time - $start_time : 1;
                    $result['row']['pre_time'] = sprintf($GLOBALS['_LANG']['time_format'], $result['row']['pre_time']);
                    $result['row']['cur_id'] = 'time_' . ($result['total'] + 1);
                    $result['page']++; // 新行
                    $result['row']['new_page'] = sprintf($GLOBALS['_LANG']['page_format'], $result['page']);
                    $result['row']['new_total'] = sprintf($GLOBALS['_LANG']['total_format'], ceil($count / $result['page_size']));
                    $result['row']['new_time'] = $GLOBALS['_LANG']['wait'];
                    $result['total']++;
                    /* 清除缓存 */
                    $result['filter'] = $arr['filter'];
                    clear_cache_files();
                    return response()->json($result);
                } else {
                    $result['mark'] = 1;
                    $result['content'] = $GLOBALS['_LANG']['download_success'];
                    return response()->json($result);
                }
            }
        } elseif ($_REQUEST['act'] == 'act_export_paipai') {
            /* 检查权限 */
            admin_priv('goods_export');


            $zip = new Phpzip;

            $where = $this->goodsExportManageService->getExportWhereSql($_POST);

            $post_express = floatval($_POST['post_express']);
            $express = floatval($_POST['express']);
            if ($post_express < 0) {
                $post_express = 10;
            }
            if ($express < 0) {
                $express = 20;
            }

            $shop_province = '""';
            $shop_city = '""';
            if ($GLOBALS['_CFG']['shop_province'] || $GLOBALS['_CFG']['shop_city']) {
                $region_id_attr = $this->baseRepository->getExplode($GLOBALS['_CFG']['shop_province'] . "',  '" . $GLOBALS['_CFG']['shop_city']);
                $res = Region::select('region_id', 'region_name')->whereIn('region_id', $region_id_attr);
                $arr = $this->baseRepository->getToArrayGet($res);
                if ($arr) {
                    if (count($arr) == 1) {
                        if ($arr[0]['region_id'] == $GLOBALS['_CFG']['shop_province']) {
                            $shop_province = '"' . $arr[0]['region_name'] . '"';
                        } else {
                            $shop_city = '"' . $arr[0]['region_name'] . '"';
                        }
                    } else {
                        if ($arr[0]['region_id'] == $GLOBALS['_CFG']['shop_province']) {
                            $shop_province = '"' . $arr[0]['region_name'] . '"';
                            $shop_city = '"' . $arr[1]['region_name'] . '"';
                        } else {
                            $shop_province = '"' . $arr[1]['region_name'] . '"';
                            $shop_city = '"' . $arr[0]['region_name'] . '"';
                        }
                    }
                }
            }

            $sql = "SELECT g.goods_id, g.goods_name, g.shop_price, g.goods_number, g.goods_desc, g.goods_img " .
                " FROM " . $this->dsc->table('goods') . " AS g " . $where;

            $res = $this->db->query($sql);


            $goods_value = [];
            $goods_value['id'] = -1;
            $goods_value['tree_node_id'] = -1;
            $goods_value['old_tree_node_id'] = -1;
            $goods_value['title'] = '""';
            $goods_value['id_in_web'] = '""';
            $goods_value['auctionType'] = '"b"';
            $goods_value['category'] = 0;
            $goods_value['shopCategoryId'] = '""';
            $goods_value['pictURL'] = '""';
            $goods_value['quantity'] = 0;
            $goods_value['duration'] = 14;
            $goods_value['startDate'] = '""';
            $goods_value['stuffStatus'] = 5;
            $goods_value['price'] = 0;
            $goods_value['increment'] = 0;
            $goods_value['prov'] = $shop_province;
            $goods_value['city'] = $shop_city;
            $goods_value['shippingOption'] = 1;
            $goods_value['ordinaryPostFee'] = $post_express;
            $goods_value['fastPostFee'] = $express;
            $goods_value['paymentOption'] = 5;
            $goods_value['haveInvoice'] = 0;
            $goods_value['haveGuarantee'] = 0;
            $goods_value['secureTradeAgree'] = 1;
            $goods_value['autoRepost'] = 1;
            $goods_value['shopWindow'] = 0;
            $goods_value['failed_reason'] = '""';
            $goods_value['pic_size'] = 0;
            $goods_value['pic_filename'] = '""';
            $goods_value['pic'] = '""';
            $goods_value['description'] = '""';
            $goods_value['story'] = '""';
            $goods_value['putStore'] = 0;
            $goods_value['pic_width'] = 80;
            $goods_value['pic_height'] = 80;
            $goods_value['skin'] = 0;
            $goods_value['prop'] = '""';


            $content = '"' . implode('","', $GLOBALS['_LANG']['paipai']) . "\"\n";

            foreach ($res as $row) {
                $goods_value['title'] = '"' . $row['goods_name'] . '"';
                $goods_value['price'] = $row['shop_price'];
                $goods_value['quantity'] = $row['goods_number'];
                $goods_value['description'] = $this->goodsExportManageService->replaceSpecialChar($row['goods_desc']);
                $goods_value['pic_filename'] = '"' . $row['goods_img'] . '"';

                $content .= implode(",", $goods_value) . "\n";

                /* 压缩图片 */
                if (!empty($row['goods_img']) && is_file(storage_public($row['goods_img']))) {
                    $zip->add_file(file_get_contents(storage_public($row['goods_img'])), $row['goods_img']);
                }
            }

            if (EC_CHARSET == 'utf-8') {
                $zip->add_file(dsc_iconv('UTF8', 'GB2312', $content), 'goods_list.csv');
            } else {
                $zip->add_file($content, 'goods_list.csv');
            }

            $filename = "goods_list.zip";
            return response()->streamDownload(function () use ($zip) {
                echo $zip->file();
            }, $filename);
        } elseif ($_REQUEST['act'] == 'act_export_paipai4') {
            /* 检查权限 */
            admin_priv('goods_export');


            $zip = new Phpzip;

            $where = $this->goodsExportManageService->getExportWhereSql($_POST);

            $post_express = floatval($_POST['post_express']);
            $express = floatval($_POST['express']);
            if ($post_express < 0) {
                $post_express = 10;
            }
            if ($express < 0) {
                $express = 20;
            }

            $shop_province = '""';
            $shop_city = '""';
            if ($GLOBALS['_CFG']['shop_province'] || $GLOBALS['_CFG']['shop_city']) {
                $region_id_attr = $this->baseRepository->getExplode($GLOBALS['_CFG']['shop_province'] . "',  '" . $GLOBALS['_CFG']['shop_city']);
                $res = Region::select('region_id', 'region_name')->whereIn('region_id', $region_id_attr);
                $arr = $this->baseRepository->getToArrayGet($res);

                if ($arr) {
                    if (count($arr) == 1) {
                        if ($arr[0]['region_id'] == $GLOBALS['_CFG']['shop_province']) {
                            $shop_province = '"' . $arr[0]['region_name'] . '"';
                        } else {
                            $shop_city = '"' . $arr[0]['region_name'] . '"';
                        }
                    } else {
                        if ($arr[0]['region_id'] == $GLOBALS['_CFG']['shop_province']) {
                            $shop_province = '"' . $arr[0]['region_name'] . '"';
                            $shop_city = '"' . $arr[1]['region_name'] . '"';
                        } else {
                            $shop_province = '"' . $arr[1]['region_name'] . '"';
                            $shop_city = '"' . $arr[0]['region_name'] . '"';
                        }
                    }
                }
            }

            $sql = "SELECT g.goods_id, g.goods_name, g.shop_price, g.goods_number, g.goods_desc, g.goods_img " .
                " FROM " . $this->dsc->table('goods') . " AS g " . $where;

            $res = $this->db->query($sql);


            $goods_value = [];
            $goods_value['id'] = -1;
            $goods_value['goods_name'] = '""';
            $goods_value['auctionType'] = '"b"';
            $goods_value['category'] = 0;
            $goods_value['shopCategoryId'] = '""';
            $goods_value['quantity'] = 0;
            $goods_value['duration'] = 14;
            $goods_value['startDate'] = '""';
            $goods_value['stuffStatus'] = 5;
            $goods_value['price'] = 0;
            $goods_value['increment'] = 0;
            $goods_value['prov'] = $shop_province;
            $goods_value['city'] = $shop_city;
            $goods_value['shippingOption'] = 1;
            $goods_value['ordinaryPostFee'] = $post_express;
            $goods_value['fastPostFee'] = $express;
            $goods_value['buyLimit'] = 0;
            $goods_value['paymentOption'] = 5;
            $goods_value['haveInvoice'] = 0;
            $goods_value['haveGuarantee'] = 0;
            $goods_value['secureTradeAgree'] = 1;
            $goods_value['autoRepost'] = 1;
            $goods_value['failed_reason'] = '""';
            $goods_value['pic_filename'] = '""';
            $goods_value['description'] = '""';
            $goods_value['shelfOption'] = 0;
            $goods_value['skin'] = 0;
            $goods_value['attr'] = '""';
            $goods_value['chengBao'] = '""';
            $goods_value['shopWindow'] = 0;

            $content = '"' . implode('","', $GLOBALS['_LANG']['paipai4']) . "\"\n";

            foreach ($res as $row) {
                $goods_value['goods_name'] = '"' . $row['goods_name'] . '"';
                $goods_value['price'] = $row['shop_price'];
                $goods_value['quantity'] = $row['goods_number'];
                $goods_value['description'] = $this->goodsExportManageService->replaceSpecialChar($row['goods_desc']);
                $goods_value['pic_filename'] = '"' . $row['goods_img'] . '"';

                $content .= implode(",", $goods_value) . "\n";

                /* 压缩图片 */
                if (!empty($row['goods_img']) && is_file(storage_public($row['goods_img']))) {
                    $zip->add_file(file_get_contents(storage_public($row['goods_img'])), $row['goods_img']);
                }
            }

            if (EC_CHARSET == 'utf-8') {
                $zip->add_file(dsc_iconv('UTF8', 'GB2312', $content), 'goods_list.csv');
            } else {
                $zip->add_file($content, 'goods_list.csv');
            }

            $filename = "goods_list.zip";
            return response()->streamDownload(function () use ($zip) {
                echo $zip->file();
            }, $filename);
        } /* 从拍拍网导入数据 */
        elseif ($_REQUEST['act'] == 'import_paipai') {
            return $this->smarty->display('import_paipai.htm');
        } /* 处理Ajax调用 */
        elseif ($_REQUEST['act'] == 'get_goods_fields') {
            $cat_id = isset($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;
            $goods_fields = my_array_merge($GLOBALS['_LANG']['custom'], $this->get_attributes($cat_id));
            return make_json_result($goods_fields);
        } elseif ($_REQUEST['act'] == 'act_export_custom') {
            /* 检查输出列 */
            if (empty($_POST['custom_goods_export'])) {
                return sys_msg($GLOBALS['_LANG']['custom_goods_field_not_null'], 1, [], false);
            }

            /* 检查权限 */
            admin_priv('goods_export');

            $zip = new Phpzip;

            $where = $this->goodsExportManageService->getExportWhereSql($_POST);

            $sql = "SELECT g.*, b.brand_name as brandname " .
                " FROM " . $this->dsc->table('goods') . " AS g LEFT JOIN " . $this->dsc->table('brand') . " AS b " .
                "ON g.brand_id = b.brand_id" . $where;

            $res = $this->db->query($sql);

            $goods_fields = explode(',', $_POST['custom_goods_export']);
            $goods_field_name = $this->goodsExportManageService->setGoodsFieldName($goods_fields, $GLOBALS['_LANG']['custom']);

            /* csv文件数组 */
            $goods_field_value = [];
            foreach ($goods_fields as $field) {
                if ($field == 'market_price' || $field == 'shop_price' || $field == 'integral' || $field == 'goods_weight' || $field == 'goods_number' || $field == 'warn_number' || $field == 'is_best' || $field == 'is_new' || $field == 'is_hot') {
                    $goods_field_value[$field] = 0;
                } elseif ($field == 'is_on_sale' || $field == 'is_alone_sale' || $field == 'is_real') {
                    $goods_field_value[$field] = 1;
                } else {
                    $goods_field_value[$field] = '""';
                }
            }

            $content = '"' . implode('","', $goods_field_name) . "\"\n";
            foreach ($res as $row) {
                $goods_value = $goods_field_value;
                isset($goods_value['goods_name']) && ($goods_value['goods_name'] = '"' . $row['goods_name'] . '"');
                isset($goods_value['goods_sn']) && ($goods_value['goods_sn'] = '"' . $row['goods_sn'] . '"');
                isset($goods_value['brand_name']) && ($goods_value['brand_name'] = $row['brandname']);
                isset($goods_value['market_price']) && ($goods_value['market_price'] = $row['market_price']);
                isset($goods_value['shop_price']) && ($goods_value['shop_price'] = $row['shop_price']);
                isset($goods_value['integral']) && ($goods_value['integral'] = $row['integral']);
                isset($goods_value['original_img']) && ($goods_value['original_img'] = '"' . $row['original_img'] . '"');
                isset($goods_value['keywords']) && ($goods_value['keywords'] = '"' . $row['keywords'] . '"');
                isset($goods_value['goods_brief']) && ($goods_value['goods_brief'] = '"' . $this->goodsExportManageService->replaceSpecialChar($row['goods_brief']) . '"');
                isset($goods_value['goods_desc']) && ($goods_value['goods_desc'] = '"' . $this->goodsExportManageService->replaceSpecialChar($row['goods_desc']) . '"');
                isset($goods_value['goods_weight']) && ($goods_value['goods_weight'] = $row['goods_weight']);
                isset($goods_value['goods_number']) && ($goods_value['goods_number'] = $row['goods_number']);
                isset($goods_value['warn_number']) && ($goods_value['warn_number'] = $row['warn_number']);
                isset($goods_value['is_best']) && ($goods_value['is_best'] = $row['is_best']);
                isset($goods_value['is_new']) && ($goods_value['is_new'] = $row['is_new']);
                isset($goods_value['is_hot']) && ($goods_value['is_hot'] = $row['is_hot']);
                isset($goods_value['is_on_sale']) && ($goods_value['is_on_sale'] = $row['is_on_sale']);
                isset($goods_value['is_alone_sale']) && ($goods_value['is_alone_sale'] = $row['is_alone_sale']);
                isset($goods_value['is_real']) && ($goods_value['is_real'] = $row['is_real']);

                $res = GoodsAttr::select('attr_id', 'attr_value')->where('goods_id', $row['goods_id']);
                $query = $this->baseRepository->getToArrayGet($res);
                foreach ($query as $attr) {
                    if (in_array($attr['attr_id'], $goods_fields)) {
                        $goods_value[$attr['attr_id']] = '"' . $attr['attr_value'] . '"';
                    }
                }

                $content .= implode(",", $goods_value) . "\n";

                /* 压缩图片 */
                if (!empty($row['goods_img']) && is_file(storage_public($row['goods_img']))) {
                    $zip->add_file(file_get_contents(storage_public($row['goods_img'])), $row['goods_img']);
                }
            }
            $charset = empty($_POST['charset_custom']) ? 'UTF8' : trim($_POST['charset_custom']);
            $zip->add_file(dsc_iconv(EC_CHARSET, $charset, $content), 'goods_list.csv');

            $filename = "goods_list.zip";
            return response()->streamDownload(function () use ($zip) {
                echo $zip->file();
            }, $filename);
        } elseif ($_REQUEST['act'] == 'get_goods_list') {
            $filters = dsc_decode($_REQUEST['JSON']);
            $arr = get_goods_list($filters);
            $opt = [];

            foreach ($arr as $key => $val) {
                $opt[] = ['goods_id' => $val['goods_id'],
                    'goods_name' => $val['goods_name']
                ];
            }
            return make_json_result($opt);
        } elseif ($_REQUEST['act'] == 'act_export_taobao V4.6') {
            /* 检查权限 */
            admin_priv('goods_export');

            $zip = new Phpzip;

            $where = $this->goodsExportManageService->getExportWhereSql($_POST);

            $goods_class = intval($_POST['goods_class']);
            $post_express = floatval($_POST['post_express']);
            $express = floatval($_POST['express']);
            $ems = floatval($_POST['ems']);

            $shop_province = '""';
            $shop_city = '""';
            if ($GLOBALS['_CFG']['shop_province'] || $GLOBALS['_CFG']['shop_city']) {
                $region_id_attr = $this->baseRepository->getExplode($GLOBALS['_CFG']['shop_province'] . "',  '" . $GLOBALS['_CFG']['shop_city']);
                $res = Region::select('region_id', 'region_name')->whereIn('region_id', $region_id_attr);
                $arr = $this->baseRepository->getToArrayGet($res);
                if ($arr) {
                    if (count($arr) == 1) {
                        if ($arr[0]['region_id'] == $GLOBALS['_CFG']['shop_province']) {
                            $shop_province = '"' . $arr[0]['region_name'] . '"';
                        } else {
                            $shop_city = '"' . $arr[0]['region_name'] . '"';
                        }
                    } else {
                        if ($arr[0]['region_id'] == $GLOBALS['_CFG']['shop_province']) {
                            $shop_province = '"' . $arr[0]['region_name'] . '"';
                            $shop_city = '"' . $arr[1]['region_name'] . '"';
                        } else {
                            $shop_province = '"' . $arr[1]['region_name'] . '"';
                            $shop_city = '"' . $arr[0]['region_name'] . '"';
                        }
                    }
                }
            }

            $sql = "SELECT g.goods_id, g.goods_name, g.shop_price, g.goods_number, g.goods_desc, g.goods_img " .
                " FROM " . $this->dsc->table('goods') . " AS g " . $where;

            $res = $this->db->query($sql);

            /* csv文件数组 */
            $goods_value = ['goods_name' => '', 'goods_class' => $goods_class, 'shop_class' => 0, 'new_level' => 0, 'province' => $shop_province, 'city' => $shop_city, 'sell_type' => '"b"', 'shop_price' => 0, 'add_price' => 0, 'goods_number' => 0, 'die_day' => 14, 'load_type' => 1, 'post_express' => $post_express, 'ems' => $ems, 'express' => $express, 'pay_type' => '', 'allow_alipay' => '', 'invoice' => 0, 'repair' => 0, 'resend' => 1, 'is_store' => 0, 'window' => 0, 'add_time' => '"1980-1-1  0:00:00"', 'story' => '', 'goods_desc' => '', 'goods_img' => '', 'goods_attr' => '', 'group_buy' => '', 'group_buy_num' => '', 'template' => 0, 'discount' => 0, 'modify_time' => '"2011-5-1  0:00:00"', 'upload_status' => 100, 'img_status' => 1, 'img_status' => '', 'rebate_proportion' => 0, 'new_goods_img' => '', 'video' => '', 'marketing_property_mix' => '', 'user_input_ID_numbers' => '', 'input_user_name_value' => '', 'sellers_code' => '', 'another_of_marketing_property' => '', 'charge_type' => '0', 'treasure_number' => '', 'ID_number' => '',];

            $content = implode("\t", $GLOBALS['_LANG']['taobao46']) . "\n";

            foreach ($res as $row) {

                /* 压缩图片 */
                if (!empty($row['goods_img']) && is_file(storage_public($row['goods_img']))) {
                    $row['new_goods_img'] = preg_replace("/(^images\/)+(.*)(.gif|.jpg|.jpeg|.png)$/", "\${2}.tbi", $row['goods_img']);
                    $new_goods_img = storage_public("images/" . $row['new_goods_img']);
                    @copy(storage_public($row['goods_img']), $new_goods_img);
                    if (is_file($new_goods_img)) {
                        $zip->add_file(file_get_contents($new_goods_img), $row['new_goods_img']);
                        unlink($new_goods_img);
                    }
                }
                $goods_value['goods_name'] = '"' . $row['goods_name'] . '"';
                $goods_value['shop_price'] = $row['shop_price'];
                $goods_value['goods_number'] = $row['goods_number'];
                $goods_value['goods_desc'] = $this->goodsExportManageService->replaceSpecialChar($row['goods_desc']);
                if (!empty($row['new_goods_img'])) {
                    $row['new_goods_img'] = str_ireplace('/', '\\', $row['new_goods_img'], $row['new_goods_img']);
                    $row['new_goods_img'] = str_ireplace('.tbi', '', $row['new_goods_img'], $row['new_goods_img']);
                    $goods_value['new_goods_img'] = '"' . $row['new_goods_img'] . ':0:0:|;' . '"';
                }

                $content .= implode("\t", $goods_value) . "\n";
            }
            if (EC_CHARSET != 'utf-8') {
                $content = dsc_iconv(EC_CHARSET, 'utf-8', $content);
            }
            $zip->add_file("\xFF\xFE" . $this->goodsExportManageService->utf82u2($content), 'goods_list.csv');

            $filename = "goods_list.zip";
            return response()->streamDownload(function () use ($zip) {
                echo $zip->file();
            }, $filename);
        }
    }


}
