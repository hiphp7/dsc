<?php

namespace App\Modules\Admin\Controllers;

use App\Repositories\Common\DscRepository;
use App\Services\Payment\PaymentManageService;
use Illuminate\Support\Str;

/**
 * DSCMALL 支付方式管理程序
 */
class PaymentController extends InitController
{
    protected $dscRepository;
    protected $paymentManageService;

    public function __construct(
        DscRepository $dscRepository,
        PaymentManageService $paymentManageService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->paymentManageService = $paymentManageService;
    }

    public function index()
    {
        $act = request()->input('act', 'list');
        $code = request()->input('code', '');

        /*------------------------------------------------------ */
        //-- 支付方式列表 ?act=list
        /*------------------------------------------------------ */

        if ($act == 'list') {
            /* 查询数据库中启用的支付方式 */
            $pay_list = [];
            $res = $this->paymentManageService->paymentList();
            if (!empty($res)) {
                foreach ($res as $row) {
                    $pay_list[$row['pay_code']] = $row;
                }
            }

            $tenpayc2c = '';

            /* 取得插件文件中的支付方式 */
            $modules = $this->dscRepository->readModules(plugin_path('Payment'));
            for ($i = 0; $i < count($modules); $i++) {

                $modules[$i]['pay_code'] = $modules[$i]['code'];
                // 如果数据库中有，取数据库中的名称和描述
                if (isset($pay_list[$modules[$i]['code']])) {
                    $modules[$i]['name'] = $pay_list[$modules[$i]['code']]['pay_name'];
                    $modules[$i]['pay_fee'] = $pay_list[$modules[$i]['code']]['pay_fee'];
                    $modules[$i]['is_cod'] = $pay_list[$modules[$i]['code']]['is_cod'];
                    $modules[$i]['desc'] = $pay_list[$modules[$i]['code']]['pay_desc'];
                    $modules[$i]['pay_order'] = $pay_list[$modules[$i]['code']]['pay_order'];
                    $modules[$i]['install'] = '1';
                } else {

                    $code = isset($modules[$i]['code']) && !empty($modules[$i]['code']) ? Str::snake($modules[$i]['code']) : '';

                    $modules[$i]['name'] = isset($GLOBALS['_LANG'][$code]) ? $GLOBALS['_LANG'][$code] : '';
                    if (!isset($modules[$i]['pay_fee'])) {
                        $modules[$i]['pay_fee'] = 0;
                    }
                    $modules[$i]['desc'] = $GLOBALS['_LANG'][$modules[$i]['desc']];
                    $modules[$i]['install'] = '0';
                }

                if ($modules[$i]['pay_code'] == 'tenpayc2c') {
                    $tenpayc2c = $modules[$i];
                }
            }

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['02_payment_list']);
            $this->smarty->assign('modules', $modules);
            $this->smarty->assign('tenpayc2c', $tenpayc2c);
            return $this->smarty->display('payment_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 安装支付方式 ?act=install&code=".$code."
        /*------------------------------------------------------ */

        elseif ($act == 'install') {
            admin_priv('payment');

            if (empty($code)) {
                return 'invalid parameter';
            }

            /* 调用相应的支付方式文件 */
            $pay_name = Str::studly($code);
            $modules = plugin_path('Payment/' . $pay_name . '/config.php');

            if (file_exists($modules)) {
                $data = include_once($modules);
            }

            /* 对支付费用判断。如果data['pay_fee']为false无支付费用，为空则说明以配送有关，其它可以修改 */
            if (isset($data['pay_fee'])) {
                $data['pay_fee'] = trim($data['pay_fee']);
            } else {
                $data['pay_fee'] = 0;
            }

            $pay['pay_code'] = $data['code'];
            $pay['pay_name'] = $GLOBALS['_LANG'][Str::snake($data['code'])];
            $pay['pay_desc'] = $GLOBALS['_LANG'][$data['desc']];
            $pay['is_cod'] = $data['is_cod'];
            $pay['pay_fee'] = $data['pay_fee'];
            $pay['is_online'] = $data['is_online'];
            $pay['pay_config'] = [];

            foreach ($data['config'] as $key => $value) {
                $config_desc = (isset($GLOBALS['_LANG'][$value['name'] . '_desc'])) ? $GLOBALS['_LANG'][$value['name'] . '_desc'] : '';
                $pay['pay_config'][$key] = $value +
                    ['label' => $GLOBALS['_LANG'][$value['name']], 'value' => $value['value'], 'desc' => $config_desc];

                if ($pay['pay_config'][$key]['type'] == 'select' || $pay['pay_config'][$key]['type'] == 'radiobox') {
                    $pay['pay_config'][$key]['range'] = $GLOBALS['_LANG'][$pay['pay_config'][$key]['name'] . '_range'];
                }
            }

            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['02_payment_list'], 'href' => 'payment.php?act=list']);
            $this->smarty->assign('pay', $pay);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['install']);

            return $this->smarty->display('payment_edit.dwt');
        } elseif ($act == 'get_config') {
            $check_auth = check_authz_json('payment');
            if ($check_auth !== true) {
                return $check_auth;
            }

            if (empty($code)) {
                return 'invalid parameter';
            }

            /* 调用相应的支付方式文件 */
            $pay_name = Str::studly($code);
            $modules = plugin_path('Payment/' . $pay_name . '/config.php');

            $data = [];
            if (file_exists($modules)) {
                $data = include_once($modules);
            }

            $config = '<table>';
            $range = '';
            foreach ($data as $key => $value) {
                $config .= "<tr><td width=120><span class='label'>";
                $config .= $GLOBALS['_LANG'][$data[$key]['name']];
                $config .= "</span></td>";
                if ($data[$key]['type'] == 'text') {
                    if ($data[$key]['name'] == 'alipay_account') {
                        $config .= "<td><input name='cfg_value[]' type='text' value='" . $data[$key]['value'] . "' />&nbsp;&nbsp;<a href=\"https://www.alipay.com/himalayas/practicality.htm\" target=\"_blank\">" . $GLOBALS['_LANG']['alipay_look'] . "</a></td>";
                    } elseif ($data[$key]['name'] == 'tenpay_account') {
                        $config .= "<td><input name='cfg_value[]' type='text' value='" . $data[$key]['value'] . "' />" . $GLOBALS['_LANG']['penpay_register'] . "</td>";
                    } else {
                        $config .= "<td><input name='cfg_value[]' type='text' value='" . $data[$key]['value'] . "' /></td>";
                    }
                } elseif ($data[$key]['type'] == 'select') {
                    $range = $GLOBALS['_LANG'][$data[$key]['name'] . '_range'];
                    $config .= "<td><select name='cfg_value[]' class='select'>";
                    foreach ($range as $index => $val) {
                        $config .= "<option value='$index'>" . $range[$index] . "</option>";
                    }
                    $config .= "</select></td>";
                }
                $config .= "</tr>";
                //$config .= '<br />';
                $config .= "<input name='cfg_name[]' type='hidden' value='" . $data[$key]['name'] . "' />";
                $config .= "<input name='cfg_type[]' type='hidden' value='" . $data[$key]['type'] . "' />";
                $config .= "<input name='cfg_lang[]' type='hidden' value='" . $data[$key]['lang'] . "' />";
            }
            $config .= '</table>';

            return make_json_result($config);
        }

        /*------------------------------------------------------ */
        //-- 编辑支付方式 ?act=edit&code={$code}
        /*------------------------------------------------------ */
        elseif ($act == 'edit') {
            admin_priv('payment');

            /* 查询该支付方式内容 */
            if (empty($code)) {
                return 'invalid parameter';
            }

            $pay = $this->paymentManageService->getPaymentInfo(['pay_code' => $code, 'enabled' => 1]);
            if (empty($pay)) {
                $links[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'payment.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['payment_not_available'], 0, $links);
            }

            /* 调用相应的支付方式文件 */
            $modules = plugin_path('Payment/' . Str::studly($code) . '/config.php');

            $data = [];
            if (file_exists($modules)) {
                $data = include_once($modules);
            }

            /* 取得配置信息 */
            if (isset($pay['pay_config']) && is_string($pay['pay_config'])) {
                $store = unserialize($pay['pay_config']);

                /* 取出已经设置属性的code */
                $code_list = [];
                foreach ($store as $key => $value) {
                    $code_list[$value['name']] = $value['value'];
                }

                $pay['pay_config'] = [];

                /* 循环插件中所有属性 */
                if (isset($data['config']) && $data['config']) {
                    foreach ($data['config'] as $key => $value) {
                        $pay['pay_config'][$key]['desc'] = (isset($GLOBALS['_LANG'][$value['name'] . '_desc'])) ? $GLOBALS['_LANG'][$value['name'] . '_desc'] : '';
                        $pay['pay_config'][$key]['label'] = isset($GLOBALS['_LANG'][$value['name']]) ? $GLOBALS['_LANG'][$value['name']] : '';
                        $pay['pay_config'][$key]['name'] = $value['name'];
                        $pay['pay_config'][$key]['type'] = $value['type'];
                        // 是否加密处理
                        $pay['pay_config'][$key]['encrypt'] = $value['encrypt'] ?? false;

                        if (isset($code_list[$value['name']])) {
                            $pay['pay_config'][$key]['value'] = $code_list[$value['name']];
                        } else {
                            $pay['pay_config'][$key]['value'] = $value['value'];
                        }

                        if ($pay['pay_config'][$key]['type'] == 'select' || $pay['pay_config'][$key]['type'] == 'radiobox') {
                            $pay['pay_config'][$key]['range'] = isset($pay['pay_config'][$key]['name']) && $pay['pay_config'][$key]['name'] && isset($GLOBALS['_LANG'][$pay['pay_config'][$key]['name'] . '_range']) ? $GLOBALS['_LANG'][$pay['pay_config'][$key]['name'] . '_range'] : '';
                        }
                    }
                }

                if (!empty($pay['pay_config'])) {
                    // 循环配置插件中所有属性
                    foreach ($pay['pay_config'] as $k => $val) {
                        // 配置项 encrypt 以*加密处理
                        if (isset($val['encrypt']) && $val['encrypt'] == true) {
                            $pay['pay_config'][$k]['value'] = string_to_star($val['value']);
                        }
                    }
                }
            }

            /* 如果以前没设置支付费用，编辑时补上 */
            if (!isset($pay['pay_fee'])) {
                $pay['pay_fee'] = $data['pay_fee'] ?? 0;
            }

            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['02_payment_list'], 'href' => 'payment.php?act=list']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit'] . $GLOBALS['_LANG']['payment']);
            $this->smarty->assign('pay', $pay);
            return $this->smarty->display('payment_edit.dwt');
        }

        /*------------------------------------------------------ */
        //-- 提交支付方式 post
        /*------------------------------------------------------ */
        elseif ($act == 'update') {

            admin_priv('payment');

            $pay_id = isset($_POST['pay_id']) ? intval($_POST['pay_id']) : 0;
            $pay_name = !empty($_POST['pay_name']) ? strip_tags($_POST['pay_name']) : '';
            $pay_code = isset($_POST['pay_code']) ? addslashes($_POST['pay_code']) : '';
            $pay_desc = isset($_POST['pay_desc']) ? addslashes($_POST['pay_desc']) : '';
            $is_cod = isset($_POST['is_cod']) ? intval($_POST['is_cod']) : '';
            $is_online = isset($_POST['is_online']) ? intval($_POST['is_online']) : '';

            /* 检查输入 */
            if (empty($pay_name)) {
                return sys_msg($GLOBALS['_LANG']['payment_name'] . $GLOBALS['_LANG']['empty']);
            }

            // 检测是否重复安装
            $rep_count = $this->paymentManageService->checkPaymentRepeat($pay_code, $pay_id);
            if ($rep_count > 0) {
                return sys_msg($GLOBALS['_LANG']['payment_name'] . $GLOBALS['_LANG']['repeat'], 1);
            }

            /* 取得配置信息 */
            $cfg_value = request()->input('cfg_value', []);
            $cfg_name = request()->input('cfg_name', []);
            $cfg_type = request()->input('cfg_type', []);

            $pay_config = [];
            if (!empty($cfg_value) && is_array($cfg_value)) {
                for ($i = 0; $i < count($cfg_value); $i++) {

                    // 判断 cfg_value[1] 是否修改,若没修改取原值存入config
                    if (stripos($cfg_value[$i], '*') !== false) {
                        $old_pay_config = $this->paymentManageService->getPayConfig($pay_code);
                        $cfg_value[$i] = $old_pay_config[$i]['value'];
                    }

                    $pay_config[] = [
                        'name' => trim($cfg_name[$i]),
                        'type' => trim($cfg_type[$i]),
                        'value' => trim($cfg_value[$i])
                    ];
                }
            }

            if ($pay_code == 'wxpay') {
                // 生成微信证书
                $this->paymentManageService->makeWxpayCert($pay_code, $pay_config);
            }

            $pay_config = empty($pay_config) ? '' : serialize($pay_config);

            /* 取得和验证支付手续费 */
            $pay_fee = empty($_POST['pay_fee']) ? 0 : $_POST['pay_fee'];

            /* 检查是编辑还是安装 */
            $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'payment.php?act=list'];
            if ($pay_id) {
                /* 编辑 */
                $updata = [
                    'pay_name' => $pay_name,
                    'pay_desc' => $pay_desc,
                    'pay_config' => $pay_config,
                    'pay_fee' => $pay_fee,
                    'is_online' => $is_online
                ];
                $this->paymentManageService->updatePayment($pay_code, $updata);

                /* 记录日志 */
                admin_log($pay_name, 'edit', 'payment');

                return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $link);
            } else {
                /* 安装，检查该支付方式是否曾经安装过 */
                $count = $this->paymentManageService->checkPaymentCount($pay_code);
                if ($count > 0) {
                    /* 该支付方式已经安装过, 将该支付方式的状态设置为 enable */
                    $updata = [
                        'pay_name' => $pay_name,
                        'pay_desc' => $pay_desc,
                        'pay_config' => $pay_config,
                        'pay_fee' => $pay_fee,
                        'enabled' => 1,
                        'is_online' => $is_online
                    ];
                    $this->paymentManageService->updatePayment($pay_code, $updata);
                } else {
                    /* 该支付方式没有安装过, 将该支付方式的信息添加到数据库 */
                    $other = [
                        'pay_code' => $pay_code,
                        'pay_name' => $pay_name,
                        'pay_desc' => $pay_desc,
                        'pay_config' => $pay_config,
                        'is_cod' => $is_cod,
                        'pay_fee' => $pay_fee,
                        'enabled' => 1,
                        'is_online' => $is_online
                    ];
                    $this->paymentManageService->createPayment($other);
                }

                /* 记录日志 */
                admin_log($pay_name, 'install', 'payment');

                return sys_msg($GLOBALS['_LANG']['install_ok'], 0, $link);
            }
        }

        /*------------------------------------------------------ */
        //-- 卸载支付方式 ?act=uninstall&code={$code}
        /*------------------------------------------------------ */
        elseif ($act == 'uninstall') {
            admin_priv('payment');

            /* 把 enabled 设为 0 */
            if (empty($code)) {
                return 'invalid parameter';
            }

            $updata = [
                'enabled' => 0
            ];
            $this->paymentManageService->updatePayment($code, $updata);

            /* 记录日志 */
            admin_log($code, 'uninstall', 'payment');

            $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'payment.php?act=list'];
            return sys_msg($GLOBALS['_LANG']['uninstall_ok'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 修改支付方式描述
        /*------------------------------------------------------ */

        elseif ($act == 'edit_desc') {
            /* 检查权限 */
            $check_auth = check_authz_json('payment');
            if ($check_auth !== true) {
                return $check_auth;
            }

            /* 取得参数 */
            $pay_code = json_str_iconv(trim($_POST['id']));
            $desc = json_str_iconv(trim($_POST['val']));

            /* 更新描述 */
            $updata = [
                'pay_desc' => $desc
            ];
            $this->paymentManageService->updatePayment($pay_code, $updata);

            return make_json_result(stripcslashes($desc));
        }

        /*------------------------------------------------------ */
        //-- 修改支付方式排序
        /*------------------------------------------------------ */

        elseif ($act == 'edit_pay_order') {
            /* 检查权限 */
            $check_auth = check_authz_json('payment');
            if ($check_auth !== true) {
                return $check_auth;
            }

            /* 取得参数 */
            $pay_code = json_str_iconv(trim($_POST['id']));
            $pay_order = json_str_iconv(trim($_POST['val']));

            /* 更新排序 */
            $updata = [
                'pay_order' => $pay_order
            ];
            $this->paymentManageService->updatePayment($pay_code, $updata);

            return make_json_result(stripcslashes($pay_order));
        }
    }
}
