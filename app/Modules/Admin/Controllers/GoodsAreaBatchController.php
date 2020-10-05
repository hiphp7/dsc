<?php

namespace App\Modules\Admin\Controllers;

use App\Models\Goods;
use App\Models\RegionWarehouse;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;

/**
 * 商品批量上传、修改
 */
class GoodsAreaBatchController extends InitController
{
    protected $dscRepository;
    protected $baseRepository;

    public function __construct(
        DscRepository $dscRepository,
        BaseRepository $baseRepository
    )
    {
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
    }

    public function index()
    {
        load_helper('goods', 'admin');

        $upload_area = $GLOBALS['_LANG']['upload_area'] ?? [];

        if ($GLOBALS['_CFG']['area_pricetype'] == 0) {
            unset($upload_area['city_name']);
        }

        /* ------------------------------------------------------ */
        //-- 批量上传
        /* ------------------------------------------------------ */

        if ($_REQUEST['act'] == 'add') {
            /* 检查权限 */
            admin_priv('goods_manage');
            $this->smarty->assign('menu_select', ['action' => '02_cat_and_goods', 'current' => 'back_area_batch_list']);
            $goods_id = isset($_REQUEST['goods_id']) && !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;

            if ($goods_id > 0) {
                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['goto_goods'], 'href' => 'goods.php?act=edit&goods_id=' . $goods_id . '&extension_code=']);
            }

            $lang_list = [
                'UTF8' => $GLOBALS['_LANG']['charset']['utf8'],
                'GB2312' => $GLOBALS['_LANG']['charset']['zh_cn'],
                'BIG5' => $GLOBALS['_LANG']['charset']['zh_tw'],
            ];

            /* 取得可选语言 */
            $download_list = $this->dscRepository->getDdownloadTemplate(resource_path('lang'));

            $this->smarty->assign('lang_list', $lang_list);
            $this->smarty->assign('download_list', $download_list);
            $this->smarty->assign('goods_id', $goods_id);

            $goods_name = Goods::where('goods_id', $goods_id)->value('goods_name');
            $goods_name = $goods_name ? $goods_name : '';

            $this->smarty->assign('goods_name', $goods_name);

            /* 参数赋值 */
            $ur_here = $GLOBALS['_LANG']['13_batch_add'];
            $this->smarty->assign('ur_here', $ur_here);

            /* 显示模板 */

            return $this->smarty->display('goods_area_batch_add.dwt');
        }

        /* ------------------------------------------------------ */
        //-- 批量上传：处理
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'upload') {
            /* 检查权限 */
            admin_priv('goods_manage');

            $goods_id = isset($_REQUEST['goods_id']) && !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;

            //ecmoban模板堂 --zhuo start 仓库
            if ($_FILES['file']['name']) {
                $line_number = 0;
                $goods_list = [];
                $field_list = array_keys($upload_area); // 字段列表
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
                        $arr = [];
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

                    $goods_list = get_goods_bacth_area_list($goods_list);
                    get_insert_bacth_area($goods_list);

                    $link[] = ['href' => 'goods.php?act=list', 'text' => $GLOBALS['_LANG']['01_goods_list']];
                    if ($goods_id) {
                        $link[] = ['href' => 'goods.php?act=edit&goods_id=' . $goods_id . '&extension_code=', 'text' => $GLOBALS['_LANG']['03_goods_edit']];
                        $link[] = ['href' => 'goods_area_batch.php?act=add&goods_id=' . $goods_id, 'text' => $GLOBALS['_LANG']['add_area_batch']];
                    } else {
                        $link[] = ['href' => 'goods_area_batch.php?act=add', 'text' => $GLOBALS['_LANG']['add_area_batch']];
                    }

                    return sys_msg($GLOBALS['_LANG']['save_products'], 0, $link);
                }
            }
        }

        /* ------------------------------------------------------ */
        //-- 下载文件
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'download') {

            /* 检查权限 */
            admin_priv('goods_manage');

            $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;

            // 文件标签
            // Header("Content-type: application/octet-stream");
            header("Content-type: application/vnd.ms-excel; charset=utf-8");
            Header("Content-Disposition: attachment; filename=area_info_list.csv");

            if ($upload_area) {
                /* 创建字符集转换对象 */
                if ($_GET['charset'] == 'zh-CN' || $_GET['charset'] == 'zh-TW') {
                    $to_charset = $_GET['charset'] == 'zh-CN' ? 'GB2312' : 'BIG5';
                    $data = join(',', $upload_area) . "\n";

                    $goods = Goods::where('goods_id', $goods_id);
                    $goods = $this->baseRepository->getToArrayFirst($goods);

                    $area_info = $this->getRegionList();
                    $area_info = $this->get_list_download($goods, $area_info);

                    if (count($area_info) > 0) {
                        for ($i = 0; $i < count($area_info); $i++) {
                            $data .= $area_info[$i]['goods_id'] . ",";
                            $data .= $area_info[$i]['goods_name'] . ",";
                            $data .= $area_info[$i]['warehouse_name'] . ",";
                            $data .= $area_info[$i]['area_name'] . ",";

                            if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
                                $data .= $area_info[$i]['city_name'] . ",";
                            }

                            $data .= $area_info[$i]['number'] . ",";
                            $data .= $area_info[$i]['price'] . ",";
                            $data .= $area_info[$i]['promote_price'] . ",";
                            $data .= $area_info[$i]['give_integral'] . ",";
                            $data .= $area_info[$i]['rank_integral'] . ",";
                            $data .= $area_info[$i]['pay_integral'] . ",";
                            $data .= $area_info[$i]['region_sort'] . "\n";
                        }
                    }

                    echo dsc_iconv(EC_CHARSET, $to_charset, $data);
                } else {
                    echo join(',', $upload_area);
                }
            } else {
                echo 'error: ' . $upload_area . ' not exists';
            }
        }
    }

    private function get_list_download($goods = [], $area_info = [])
    {
        if (count($area_info) > 0) {
            $arr = [];

            for ($i = 0; $i < count($area_info); $i++) {
                $arr[$i]['goods_id'] = $goods['goods_id'] ?? 0;
                $arr[$i]['goods_name'] = $goods['goods_name'] ?? '';
                $arr[$i]['warehouse_name'] = $area_info[$i]['warehouse_name'];
                $arr[$i]['area_name'] = $area_info[$i]['region_name'];

                if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
                    $arr[$i]['city_name'] = $area_info[$i]['city_name'];
                }

                $arr[$i]['number'] = '';
                $arr[$i]['price'] = '';
                $arr[$i]['promote_price'] = '';
                $arr[$i]['give_integral'] = '';
                $arr[$i]['rank_integral'] = '';
                $arr[$i]['pay_integral'] = '';
                $arr[$i]['region_sort'] = $i;
            }

            return $arr;
        } else {
            return [];
        }
    }

    private function getRegionList()
    {
        $area_pricetype = $GLOBALS['_CFG']['area_pricetype'];

        if ($area_pricetype == 1) {
            $region_type = 2;
        } else {
            $region_type = 1;
        }

        $area_info = RegionWarehouse::where('region_type', $region_type);
        $area_info = $this->baseRepository->getToArrayGet($area_info);

        if ($area_info) {
            foreach ($area_info as $key => $val) {
                if ($area_pricetype == 1) {

                    $region = RegionWarehouse::where('region_id', $val['parent_id']);
                    $region = $this->baseRepository->getToArrayFirst($region);

                    $area_info[$key]['warehouse_name'] = RegionWarehouse::where('region_id', $region['parent_id'])->value('region_name');
                    $area_info[$key]['area_name'] = $region['region_name'];
                    $area_info[$key]['city_name'] = $val['region_name'];

                } else {
                    $area_info[$key]['warehouse_name'] = RegionWarehouse::where('region_id', $val['parent_id'])->value('region_name');
                    $area_info[$key]['city_name'] = '';
                }
            }
        }

        return $area_info;
    }
}
