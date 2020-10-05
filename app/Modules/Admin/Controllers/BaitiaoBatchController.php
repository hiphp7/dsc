<?php

namespace App\Modules\Admin\Controllers;

use App\Models\Baitiao;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;

/**
 * 商品批量上传、修改
 */
class BaitiaoBatchController extends InitController
{
    protected $baseRepository;

    public function __construct(
        BaseRepository $baseRepository
    )
    {
        $this->baseRepository = $baseRepository;
    }

    public function index()
    {

        /* 取得可选语言 */
        $lang_list = [
            'UTF8' => $GLOBALS['_LANG']['charset']['utf8'],
            'GB2312' => $GLOBALS['_LANG']['charset']['zh_cn'],
            'BIG5' => $GLOBALS['_LANG']['charset']['zh_tw'],
        ];

        $this->smarty->assign('lang_list', $lang_list);

        /* 参数赋值 */
        $ur_here = $GLOBALS['_LANG']['14_batch_add'];
        $this->smarty->assign('ur_here', $ur_here);

        /*------------------------------------------------------ */
        //-- 批量上传
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'add') {
            /* 检查权限 */
            admin_priv('commission_batch');

            $this->smarty->assign('full_page', 1);

            /* 显示模板 */
            return $this->smarty->display('baitiao_batch_add.dwt');
        }

        /*------------------------------------------------------ */
        //-- 批量修改：提交
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'baitiao_add') {

            $commission_list = [];
            if ($_FILES['file']['name']) {
                $line_number = 0;
                $field_list = array_keys($GLOBALS['_LANG']['upload_baitiao']); // 字段列表
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
                        $commission_list[] = $arr;
                    }
                }
            }

            $commission_list = $this->get_commission_list($commission_list);

            session([
                'baitiao_list' => $commission_list
            ]);

            $this->smarty->assign('full_page', 2);
            $this->smarty->assign('page', 1);

            /* 显示模板 */

            return $this->smarty->display('baitiao_batch_add.dwt');
        }

        /*------------------------------------------------------ */
        //-- 处理系统设置订单自动确认收货订单
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'ajax_insert') {
            /* 检查权限 */
            admin_priv('commission_batch');

            $result = ['list' => ''];

            $page = !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_size = isset($_REQUEST['page_size']) ? intval($_REQUEST['page_size']) : 1;

            /* 设置最长执行时间为5分钟 */
            @set_time_limit(300);

            if (session()->has('baitiao_list') && session('baitiao_list')) {
                $commission_list = session('baitiao_list');

                $commission_list = $this->dsc->page_array($page_size, $page, $commission_list);

                $result['list'] = isset($commission_list['list']) && $commission_list['list'] ? $commission_list['list'][0] : [];

                $result['page'] = $commission_list['filter']['page'] + 1;
                $result['page_size'] = $commission_list['filter']['page_size'];
                $result['record_count'] = $commission_list['filter']['record_count'];
                $result['page_count'] = $commission_list['filter']['page_count'];

                if (empty($result['list']['user_name'])) {
                    $result['list']['user_name'] = 0;
                }

                $result['is_stop'] = 1;
                if ($page > $commission_list['filter']['page_count']) {
                    $result['is_stop'] = 0;
                }

                if (isset($result['list']['user_id']) && $result['list']['user_id'] > 0) {
                    $baitiao_id = Baitiao::where('user_id', $result['list']['user_id'])->value('baitiao_id');
                    $baitiao_id = $baitiao_id ? $baitiao_id : 0;
                }

                if (!empty($baitiao_id)) {
                    $result['status_lang'] = $GLOBALS['_LANG']['already_show'];
                } else {
                    if ($result['is_stop']) {
                        $other = [
                            'user_id' => $result['list']['user_id'],
                            'amount' => $result['list']['amount'],
                            'repay_term' => $result['list']['repay_term'],
                            'over_repay_trem' => $result['list']['over_repay_trem'],
                            'add_time' => gmtime(),
                        ];

                        $baitiao_id = Baitiao::insertGetId($other);

                        if ($baitiao_id) {
                            $result['status_lang'] = $GLOBALS['_LANG']['status_succeed'];
                        } else {
                            $result['status_lang'] = $GLOBALS['_LANG']['status_failure'];
                        }
                    }
                }
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 下载文件
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'download') {
            /* 检查权限 */
            admin_priv('commission_batch');

            // 文件标签
            // header("Content-type: application/octet-stream");
            header("Content-type: application/vnd.ms-excel; charset=utf-8");
            header("Content-Disposition: attachment; filename=baitiao_list.csv");

            // 下载
            if ($_GET['charset'] != $GLOBALS['_CFG']['lang']) {
                $lang_file = app_path('Modules/Admin/Languages/' . $_GET['charset'] . '/baitiao_batch.php');
                if (file_exists($lang_file)) {
                    unset($GLOBALS['_LANG']['upload_baitiao']);
                    require($lang_file);
                }
            }

            if (isset($GLOBALS['_LANG']['upload_baitiao'])) {
                /* 创建字符集转换对象 */
                if ($_GET['charset'] == 'zh-CN' || $_GET['charset'] == 'zh-TW') {
                    $to_charset = $_GET['charset'] == 'zh-CN' ? 'GB2312' : 'BIG5';
                    echo dsc_iconv(EC_CHARSET, $to_charset, join(',', $GLOBALS['_LANG']['upload_baitiao']));
                } else {
                    echo join(',', $GLOBALS['_LANG']['upload_baitiao']);
                }
            } else {
                echo 'error: ' . $GLOBALS['_LANG']['upload_baitiao'] . ' not exists';
            }
        }
    }


    private function get_commission_list($commission_list)
    {
        if ($commission_list) {
            foreach ($commission_list as $key => $rows) {
                $commission_list[$key]['amount'] = $rows['amount'];

                $users = Users::where('user_name', $rows['user_name']);
                $users = $this->baseRepository->getToArrayFirst($users);

                $commission_list[$key]['user_id'] = $users['user_id'] ?? 0;
                $commission_list[$key]['repay_term'] = $rows['repay_term'];
                $commission_list[$key]['over_repay_trem'] = $rows['over_repay_trem'];

                if (!$commission_list[$key]['user_id']) {
                    unset($commission_list[$key]);
                }
            }
        }

        return $commission_list;
    }
}
