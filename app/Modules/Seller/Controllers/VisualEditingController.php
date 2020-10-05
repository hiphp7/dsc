<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Image;
use App\Libraries\Phpzip;
use App\Repositories\Common\DscRepository;
use Illuminate\Support\Str;

/**
 * 可视化编辑控制器
 */
class VisualEditingController extends InitController
{
    protected $dscRepository;

    public function __construct(
        DscRepository $dscRepository
    )
    {
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        load_helper('visual');

        /* 检查权限 */
        admin_priv('10_visual_editing');

        $this->smarty->assign('menu_select', ['action' => '19_merchants_store', 'current' => '10_visual_editing']);

        $adminru = get_admin_ru_id();
        $this->smarty->assign('ru_id', $adminru['ru_id']);

        $allow_file_types = '|PNG|JPG|GIF|GPEG|';

        if ($_REQUEST['act'] == 'first') {
            $code = isset($_REQUEST['code']) && !empty($_REQUEST['code']) ? trim($_REQUEST['code']) : '';

            if (empty($code)) {
                $sql = "SELECT seller_templates FROM " . $this->dsc->table('seller_shopinfo') . " WHERE ru_id = '" . $adminru['ru_id'] . "'";
                $code = $this->db->getOne($sql, true);
            }

            /**
             * 店铺可视化
             * 下载OSS模板文件
             */
            get_down_sellertemplates($adminru['ru_id'], $code);

            $pc_page = get_seller_templates($adminru['ru_id'], 0, $code, 1);//获取页面内容

            $domain = $this->dsc->seller_url();

            /*获取左侧储存值*/
            $head = getleft_attr("head", $adminru['ru_id'], $pc_page['tem']);
            $content = getleft_attr("content", $adminru['ru_id'], $pc_page['tem']);

            //判断是否是新模板
            $this->smarty->assign('theme_extension', 1);
            $this->smarty->assign('is_temp', $pc_page['is_temp']);
            $this->smarty->assign('pc_page', $pc_page);
            $this->smarty->assign('head', $head);
            $this->smarty->assign('content', $content);
            $this->smarty->assign('domain', $domain);
            $this->smarty->assign('vis_section', "vis_seller_store");
            return $this->smarty->display("visual_editing.dwt");
        } /*图片上传*/
        elseif ($_REQUEST['act'] == 'header_bg') {
            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);
            load_helper('goods', 'seller');
            $result = ['error' => 0, 'prompt' => '', 'content' => ''];
            $type = isset($_REQUEST['type']) ? addslashes($_REQUEST['type']) : '';
            $name = isset($_REQUEST['name']) ? addslashes($_REQUEST['name']) : '';
            $suffix = isset($_REQUEST['suffix']) ? addslashes($_REQUEST['suffix']) : 'store_tpl_1';
            $topic_type = isset($_REQUEST['topic_type']) ? addslashes($_REQUEST['topic_type']) : '';
            if ($_FILES[$name]) {
                $file = $_FILES[$name];
                /* 判断用户是否选择了文件 */
                if ((isset($file['error']) && $file['error'] == 0) || (!isset($file['error']) && $file['tmp_name'] != 'none')) {
                    /* 检查上传的文件类型是否合法 */
                    if (!check_file_type($file['tmp_name'], $file['name'], $allow_file_types)) {
                        $result['error'] = 1;
                        $result['prompt'] = $GLOBALS['_LANG']['upload_correct_format_img'];
                    } else {
                        if ($file['name']) {
                            $ext = explode('.', $file['name']);
                            $ext = array_pop($ext);
                        } else {
                            $ext = "";
                        }

                        $tem = '';
                        if ($type == 'headerbg') {
                            $tem = "/head";
                        } elseif ($type == 'contentbg') {
                            $tem = "/content";
                        }
                        if ($topic_type == 'topic_type') {
                            $file_dir = storage_public(DATA_DIR . '/topic/topic_' . $adminru['ru_id'] . "/" . $suffix . "/images" . $tem);
                        } else {
                            $file_dir = storage_public(DATA_DIR . '/seller_templates/seller_tem_' . $adminru['ru_id'] . "/" . $suffix . "/images" . $tem);
                        }
                        if (!is_dir($file_dir)) {
                            make_dir($file_dir);
                        }
                        $bgtype = '';
                        if ($type == 'headerbg') {
                            $bgtype = 'head';
                            $file_name = $file_dir . "/hdfile_" . gmtime() . '.' . $ext;//头部背景图
                            $back_name = "/hdfile_" . gmtime() . '.' . $ext;
                        } elseif ($type == 'contentbg') {
                            $bgtype = 'content';
                            $file_name = $file_dir . "/confile_" . gmtime() . '.' . $ext;//内容部分背景图
                            $back_name = "/confile_" . gmtime() . '.' . $ext;
                        } else {
                            $file_name = $file_dir . "/slide_" . gmtime() . '.' . $ext;//头部显示图片
                            $back_name = "/slide_" . gmtime() . '.' . $ext;
                        }
                        /* 判断是否上传成功 */
                        if (move_upload_file($file['tmp_name'], $file_name)) {
                            $url = $this->dsc->seller_url();
                            $content_file = $file_name;
                            //oss上传  需要的时候打开
                            $oss_img_url = str_replace(["../", storage_public()], "", $content_file);
                            $this->dscRepository->getOssAddFile([$oss_img_url]);
                            if ($bgtype) {
                                $theme = '';
                                $sql = "SELECT id ,img_file FROM" . $this->dsc->table('templates_left') . " WHERE ru_id = '" . $adminru['ru_id'] . "' AND seller_templates = '$suffix' AND type = '$bgtype' AND theme = '$theme'";
                                $templates_left = $this->db->getRow($sql);
                                //
                                if ($templates_left['id'] > 0) {
                                    if ($templates_left['img_file'] != '') {
                                        $old_oss_img_url = str_replace("../", "", $templates_left['img_file']);
                                        $this->dscRepository->getOssDelFile([$old_oss_img_url]);
                                        dsc_unlink(storage_public($templates_left['img_file']));
                                    }
                                    $sql = "UPDATE" . $this->dsc->table('templates_left') . " SET img_file = '$oss_img_url' WHERE ru_id = '" . $adminru['ru_id'] . "' AND seller_templates = '$suffix' AND id='" . $templates_left['id'] . "' AND type = '$bgtype' AND theme = '$theme'";
                                    $this->db->query($sql);
                                } else {
                                    $sql = "INSERT INTO" . $this->dsc->table('templates_left') . " (`ru_id`,`seller_templates`,`img_file`,`type`) VALUES ('" . $adminru['ru_id'] . "','$suffix','$oss_img_url','$bgtype')";
                                    $this->db->query($sql);
                                }
                            }

                            $result['error'] = 2;
                            if ($content_file) {
                                $content_file = str_replace('../', '', $content_file);
                                $content_file = str_replace(storage_public(), '', $content_file);
                                $content_file = get_image_path($content_file);
                            }
                            $result['content'] = $content_file;
                        } else {
                            $result['error'] = 1;
                            $result['prompt'] = $GLOBALS['_LANG']['system_error_reupload'];
                        }
                    }
                }
            } else {
                $result['error'] = 1;
                $result['prompt'] = $GLOBALS['_LANG']['select_upload_img'];
            }
            return response()->json($result);
        } /*生成缓存文件*/
        elseif ($_REQUEST['act'] == 'file_put_visual') {
            $result = ['suffix' => '', 'error' => ''];
            $topic_type = isset($_REQUEST['topic_type']) ? addslashes($_REQUEST['topic_type']) : '';
            /*后台缓存内容*/
            $content = isset($_REQUEST['content']) ? unescape($_REQUEST['content']) : '';
            $content = !empty($content) ? stripslashes($content) : '';
            /*前台缓存内容*/
            $content_html = isset($_REQUEST['content_html']) ? unescape($_REQUEST['content_html']) : '';
            $content_html = !empty($content_html) ? stripslashes($content_html) : '';

            /*前台头部缓存内容*/
            $head_html = isset($_REQUEST['head_html']) ? unescape($_REQUEST['head_html']) : '';
            $head_html = !empty($head_html) ? stripslashes($head_html) : '';

            $suffix = isset($_REQUEST['suffix']) ? addslashes($_REQUEST['suffix']) : 'store_tpl_1';
            $pc_page_name = "pc_page.php";
            $pc_html_name = "pc_html.php";
            $type = 0;

            if ($topic_type == 'topic_type') {
                /*前台导航缓存内容*/
                $nav_html = isset($_REQUEST['nav_html']) ? unescape($_REQUEST['nav_html']) : '';
                $nav_html = !empty($nav_html) ? stripslashes($nav_html) : '';
                $dir = storage_public(DATA_DIR . '/topic/topic_' . $adminru['ru_id'] . "/" . $suffix);
                $type = 1;
                $pc_nav_html = "nav_html.php";
                $nav_html = create_html($nav_html, $adminru['ru_id'], $pc_nav_html, $suffix, 1);
            } else {
                $dir = storage_public(DATA_DIR . '/seller_templates/seller_tem_' . $adminru['ru_id'] . "/" . $suffix);
                $pc_head_name = "pc_head.php";
                $create = create_html($head_html, $adminru['ru_id'], $pc_head_name, $suffix);
            }
            $create_html = create_html($content_html, $adminru['ru_id'], $pc_html_name, $suffix, $type);
            $create = create_html($content, $adminru['ru_id'], $pc_page_name, $suffix, $type);

            $result['error'] = 0;
            $result['suffix'] = $suffix;

            return response()->json($result);
        } /*发布*/
        elseif ($_REQUEST['act'] == 'release') {
            $result = ['error' => '', 'content' => ''];
            $suffix = isset($_REQUEST['suffix']) ? addslashes($_REQUEST['suffix']) : 'store_tpl_1';//模板名称
            $dir = storage_public(DATA_DIR . '/seller_templates/seller_tem_' . $adminru['ru_id'] . "/" . $suffix);//模板目录
            $temp_id = isset($_REQUEST['temp_id']) ? intval($_REQUEST['temp_id']) : 0;//模板id
            $type = isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 0;//操作类型  0、商家自己模板使用，1、使用平台默认模板
            $apply_id = isset($_REQUEST['apply_id']) ? intval($_REQUEST['apply_id']) : 0;//模板申请id

            $error = 1;
            /*判断商家是否存在该模板，没有则上传*/
            if ($type == 1) {
                $new_suffix = get_new_dir_name($adminru['ru_id']);
                $seller_template_apply = [];
                if ($apply_id > 0) {
                    $sql = "SELECT temp_id,pay_id FROM" . $this->dsc->table("seller_template_apply") . "WHERE apply_id = '$apply_id'";
                    $seller_template_apply = $this->db->getRow($sql);

                    $temp_id = $seller_template_apply['temp_id'];
                }
                $sql = "SELECT temp_mode,temp_cost,temp_code,temp_id FROM" . $this->dsc->table("template_mall") . "WHERE temp_id = '$temp_id'";
                $template_mall = $this->db->getRow($sql);
                if ($template_mall['temp_mode'] == 1 && $template_mall['temp_cost'] > 0) {
                    $template_mall['temp_cost_format'] = price_format($template_mall['temp_cost']);
                    $template_mall['pay_id'] = !empty($seller_template_apply['pay_id']) ? $seller_template_apply['pay_id'] : 0;
                    $seller_template_info = [];
                    if ($template_mall['temp_code']) {
                        $seller_template_info = get_seller_template_info($template_mall['temp_code']);
                    }
                    load_helper('order');
                    $pay = available_payment_list(0); //获取支付方式
                    $this->smarty->assign("pay", $pay);
                    $this->smarty->assign("template_mall", $template_mall);
                    $this->smarty->assign("temp", 'template_mall_done');
                    $this->smarty->assign("template", $seller_template_info);
                    $this->smarty->assign("apply_id", $apply_id);
                    $error = 2;
                    //判断是否已经购买过
                    $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('seller_template_apply') . "WHERE pay_status = 1 AND apply_status = 1 AND temp_id = '$temp_id' AND apply_id != '$apply_id' AND ru_id = '" . $adminru['ru_id'] . "'";
                    $tenp_count = $this->db->getOne($sql);
                    if ($tenp_count > 0) {
                        if ($GLOBALS['_CFG']['template_pay_type'] == 0) {
                            $error = 3;
                        } else {
                            $error = 4;
                        }
                    }
                    if ($error != 4) {
                        $result['error'] = $error;
                        $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
                        return response()->json($result);
                    }
                }
                Import_temp($suffix, $new_suffix, $adminru['ru_id']);
                $suffix = $new_suffix;
                if ($error != 4) {
                    //更新模板使用数量
                    $sql = "UPDATE" . $this->dsc->table('template_mall') . "SET sales_volume = sales_volume+1 WHERE temp_id = '$temp_id'";
                    $this->db->query($sql);
                }
            }
            if ($suffix && $type == 0) {
                $sql = "UPDATE" . $this->dsc->table('seller_shopinfo') . " SET seller_templates = '$suffix' WHERE ru_id = '" . $adminru['ru_id'] . "'";
                if ($this->db->query($sql) == true) {
                    $result['error'] = $error;
                } else {
                    $result['error'] = 0;
                    $result['content'] = $GLOBALS['_LANG']['system_error_refresh_wait_retry'];
                }
            } elseif ($type == 1) {
                $result['error'] = $error;
            } else {
                $result['error'] = 0;
                $result['content'] = $GLOBALS['_LANG']['please_select_tpl'];
            }
            $result['tem'] = $suffix;
            return response()->json($result);
        } /*支付处理*/
        elseif ($_REQUEST['act'] == 'purchase_temp') {
            $temp_id = isset($_REQUEST['temp_id']) ? intval($_REQUEST['temp_id']) : 0; //模板id
            $pay_id = isset($_REQUEST['pay_id']) ? intval($_REQUEST['pay_id']) : 0;
            $code = isset($_REQUEST['code']) ? trim($_REQUEST['code']) : '';
            $apply_id = $old_apply_id = !empty($_REQUEST['apply_id']) ? intval($_REQUEST['apply_id']) : 0;
            if ($pay_id > 0) {
                if ($temp_id > 0) {
                    load_helper('order');
                    load_helper('payment');
                    load_helper('clips');

                    //获取模板详细信息
                    $sql = "SELECT temp_mode,temp_cost,temp_code FROM" . $this->dsc->table("template_mall") . "WHERE temp_id = '$temp_id'";
                    $template_mall = $this->db->getRow($sql);

                    /* 获取支付信息 */
                    $payment_info = [];
                    $payment_info = payment_info($pay_id);
                    //计算支付手续费用
                    $payment_info['pay_fee'] = pay_fee($pay_id, $template_mall['temp_cost'], 0);
                    $apply_info['order_amount'] = $template_mall['temp_cost'] + $payment_info['pay_fee'];

                    //入库
                    if ($apply_id > 0) {
                        $sql = "UPDATE" . $this->dsc->table('seller_template_apply') . "SET pay_id = '$pay_id',total_amount = '" . $apply_info['order_amount'] . "',pay_fee = '" . $payment_info['pay_fee'] . "' WHERE apply_id = '$apply_id'";
                        $this->db->query($sql);
                        $apply_info['log_id'] = $this->db->getOne("SELECT log_id FROM" . $this->dsc->table('pay_log') . "WHERE order_id = '$apply_id' AND order_type = '" . PAY_APPLYTEMP . "' LIMIT 1");
                        $apply_sn = $this->db->getOne("SELECT apply_sn FROM" . $this->dsc->table('seller_template_apply') . "WHERE apply_id = '$apply_id'");
                    } else {
                        $apply_sn = get_order_sn(); //获取新订单号
                        $time = gmtime();
                        $key = "(`ru_id`,`temp_id`,`temp_code`,`pay_status`,`apply_status`,`total_amount`,`pay_fee`,`add_time`,`pay_id`,`apply_sn`)";
                        $value = "('" . $adminru['ru_id'] . "','" . $temp_id . "','" . $code . "',0,0,'" . $apply_info['order_amount'] . "','" . $payment_info['pay_fee'] . "','" . $time . "','" . $pay_id . "','$apply_sn')";
                        $sql = 'INSERT INTO' . $this->dsc->table("seller_template_apply") . $key . " VALUES" . $value;
                        $this->db->query($sql);
                        $apply_id = $this->db->insert_id();
                        $apply_info['log_id'] = insert_pay_log($apply_id, $apply_info['order_amount'], $type = PAY_APPLYTEMP, 0); //记录支付日志
                    }

                    return dsc_header("Location: visual_editing.php?act=temp_pay&apply_id=" . $apply_id . "\n");
                } else {
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'visual_editing.php?act=templates'];
                    return sys_msg($GLOBALS['_LANG']['system_error_retry'], 0, $link);
                }
            } else {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'visual_editing.php?act=templates'];
                return sys_msg($GLOBALS['_LANG']['please_select_payment'], 0, $link);
            }
        } elseif ($_REQUEST['act'] == 'temp_pay') {
            $apply_id = !empty($_REQUEST['apply_id']) ? intval($_REQUEST['apply_id']) : 0;

            if ($apply_id == 0) {
                return dsc_header("Location: visual_editing.php?act=template_apply_list\n");
            }
            load_helper('order');
            load_helper('payment');
            load_helper('clips');
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['10_visual_editing']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['tpl_pay']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['tpl_list'], 'href' => 'visual_editing.php?act=templates']);

            $sql = "SELECT apply_id,pay_fee, total_amount as order_amount ,ru_id,apply_sn as order_sn,pay_id ,temp_code,temp_id FROM" . $this->dsc->table('seller_template_apply') . "WHERE apply_id = '$apply_id'";
            $apply_info = $this->db->getRow($sql);

            $apply_info['surplus_amount'] = $apply_info['order_amount'];
            $apply_info['log_id'] = $this->db->getOne("SELECT log_id FROM" . $this->dsc->table('pay_log') . "WHERE order_id = '$apply_id' AND order_type = '" . PAY_APPLYTEMP . "' LIMIT 1");

            /* 获取支付信息 */
            $payment_info = [];
            $payment_info = payment_info($apply_info['pay_id']);
            $payment = unserialize_config($payment_info['pay_config']);

            if ($payment_info['pay_code'] == 'balance') {
                //查询出当前用户的剩余余额;
                $user_money = $this->db->getOne("SELECT user_money FROM " . $this->dsc->table('users') . " WHERE user_id='" . $adminru['ru_id'] . "'");
                //如果用户余额足够支付订单;
                if ($user_money > $apply_info['order_amount']) {
                    /* 修改申请的支付状态 */
                    $sql = " UPDATE " . $this->dsc->table('seller_template_apply') . " SET pay_status = 1 ,pay_time = '" . gmtime() . "'  , apply_status = 1 WHERE apply_id= '" . $apply_id . "'";
                    $this->db->query($sql);

                    //记录支付log
                    $sql = "UPDATE " . $this->dsc->table('pay_log') . "SET is_paid = 1 WHERE order_id = '" . $apply_id . "' AND order_type = '" . PAY_APPLYTEMP . "'";
                    $this->db->query($sql);
                    log_account_change($adminru['ru_id'], $apply_info['order_amount'] * (-1), 0, 0, 0, $GLOBALS['_LANG']['record_id'] . $apply_info['order_sn'] . lang('seller/visual_editing.buy_models'));

                    //导入已付款的模板
                    $new_suffix = get_new_dir_name($adminru['ru_id']); //获取新的模板
                    Import_temp($apply_info['temp_code'], $new_suffix, $adminru['ru_id']);

                    //更新模板使用数量
                    $sql = "UPDATE" . $this->dsc->table('template_mall') . "SET sales_volume = sales_volume+1 WHERE temp_id = '" . $apply_info['temp_id'] . "'";
                    $this->db->query($sql);
                    if ($apply_id > 0) {
                        $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'visual_editing.php?act=template_apply_list'];
                    } else {
                        $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'visual_editing.php?act=templates'];
                    }
                    return sys_msg($GLOBALS['_LANG']['pay_success_backup_first'], 0, $link);
                } else {
                    return sys_msg($GLOBALS['_LANG']['balance_no_enough_select_other_payment']);
                }
            } else {
                if ($payment_info && strpos($payment_info['pay_code'], 'pay_') === false) {
                    /* 调用相应的支付方式文件 */
                    $pay_name = Str::studly($payment_info['pay_code']);
                    $pay_obj = app('\\App\\Plugins\\Payment\\' . $pay_name . '\\' . $pay_name);

                    if (!is_null($pay_obj)) {


                        /* 取得在线支付方式的支付按钮 */
                        $payment_info['pay_button'] = $pay_obj->get_code($apply_info, $payment);
                    }
                }
            }
            $this->smarty->assign('pay_fee', price_format($payment_info['pay_fee'], false));
            $this->smarty->assign('amount', price_format($apply_info['order_amount'], false));
            $this->smarty->assign('payment', $payment_info);
            $this->smarty->assign('order', $apply_info);
            $this->smarty->assign('apply_id', $apply_id);
            return $this->smarty->display('seller_done.dwt');
        } /*
 * 微信支付改变状态
 */
        elseif ($_REQUEST['act'] == 'checkorder') {
            $apply_id = isset($_GET['apply_id']) ? intval($_GET['apply_id']) : 0;
            $sql = "SELECT pay_status, pay_id FROM " . $this->dsc->table('seller_template_apply') . " WHERE apply_id = '$apply_id' LIMIT 1";
            $order_info = $this->db->getRow($sql);

            //已付款
            if ($order_info && $order_info['pay_status'] == 1) {
                $json = ['code' => 1];
                return response()->json($json);
            } else {
                $json = ['code' => 0];
                return response()->json($json);
            }
        } //模板支付使用记录
        elseif ($_REQUEST['act'] == 'template_apply_list') {
            //页面赋值
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['visual_manage']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['tpl_pay_record']);
            //模板赋值
            $tab_menu[] = ['curr' => '', 'text' => $GLOBALS['_LANG']['temp_operation'], 'href' => 'visual_editing.php?act=templates'];
            $tab_menu[] = ['curr' => 1, 'text' => $GLOBALS['_LANG']['temp_paylist'], 'href' => 'visual_editing.php?act=template_apply_list'];
            $this->smarty->assign('tab_menu', $tab_menu);

            //获取数据
            $template_mall_list = get_template_apply_list();
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($template_mall_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('available_templates', $template_mall_list['list']);
            $this->smarty->assign('filter', $template_mall_list['filter']);
            $this->smarty->assign('record_count', $template_mall_list['record_count']);
            $this->smarty->assign('page_count', $template_mall_list['page_count']);

            $this->smarty->assign('full_page', 1);
            $this->smarty->assign("act_type", $_REQUEST['act']);


            return $this->smarty->display("template_apply_list.dwt");
        }
        /* ------------------------------------------------------ */
        //-- 排序、分页、查询
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'apply_query') {
            $template_mall_list = get_template_apply_list();
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($template_mall_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('available_templates', $template_mall_list['list']);
            $this->smarty->assign('filter', $template_mall_list['filter']);
            $this->smarty->assign('record_count', $template_mall_list['record_count']);
            $this->smarty->assign('page_count', $template_mall_list['page_count']);

            return make_json_result($this->smarty->fetch('template_apply_list.dwt'), '', ['filter' => $template_mall_list['filter'], 'page_count' => $template_mall_list['page_count']]);
        } /*选择模板*/
        elseif ($_REQUEST['act'] == 'templates') {

            //如果审核通过，判断店铺是否存在模板，不存在 导入默认模板
            $tpl_dir = storage_public(DATA_DIR . '/seller_templates/seller_tem_' . $adminru['ru_id']); //获取店铺模板目录
            $tpl_arr = get_dir_file_list($tpl_dir);
            if (empty($tpl_arr)) {
                $new_suffix = get_new_dir_name($adminru['ru_id']);
                $dir = storage_public(DATA_DIR . "/seller_templates/seller_tem/bucket_tpl"); //原目录
                $file = $tpl_dir . "/" . $new_suffix; //目标目录
                if (!empty($new_suffix)) {
                    //新建目录
                    if (!is_dir($file)) {
                        make_dir($file);
                    }
                    //recurse_copy($dir, $file, 1);
                    $result['error'] = 0;
                }
                $sql = "UPDATE " . $this->dsc->table('seller_shopinfo') . " SET seller_templates = '$new_suffix' WHERE ru_id=" . $adminru['ru_id'];
                $this->db->query($sql);
            }
            /*获取店铺正在使用的模板名称*/
            $sql = "SELECT seller_templates FROM " . $this->dsc->table('seller_shopinfo') . " WHERE ru_id=" . $adminru['ru_id'];
            $tem = $this->db->getOne($sql);
            /* 获得可用的模版 */
            $available_templates = [];
            $default_templates = [];

            //模板赋值
            $tab_menu[] = ['curr' => 1, 'text' => $GLOBALS['_LANG']['temp_operation'], 'href' => 'visual_editing.php?act=templates'];
            // $tab_menu[] = ['curr' => '', 'text' => $GLOBALS['_LANG']['temp_paylist'], 'href' => 'visual_editing.php?act=template_apply_list'];
            $this->smarty->assign('tab_menu', $tab_menu);
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['visual_manage']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['temp_operation']);


            //获取付费模板列表
            $template_mall_list = template_mall_list();
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($template_mall_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('default_templates', $template_mall_list['list']);
            $this->smarty->assign('filter', $template_mall_list['filter']);
            $this->smarty->assign('record_count', $template_mall_list['record_count']);
            $this->smarty->assign('page_count', $template_mall_list['page_count']);

            $this->smarty->assign('full_page', 1);
            /*店铺模板*/
            $seller_dir = storage_public(DATA_DIR . '/seller_templates/seller_tem_' . $adminru['ru_id'] . '/');
            $template_dir = @opendir($seller_dir);
            if ($template_dir) {
                while ($file = readdir($template_dir)) {
                    if ($file != '.' && $file != '..' && $file != '.svn' && $file != 'index.htm') {
                        $available_templates[] = get_seller_template_info($file, $adminru['ru_id']);
                    }
                }
                $available_templates = get_array_sort($available_templates, 'sort');
                @closedir($template_dir);
            }

            $this->smarty->assign('curr_template', get_seller_template_info($tem, $adminru['ru_id']));
            $this->smarty->assign('available_templates', $available_templates);
            $this->smarty->assign('default_tem', $tem);
            $this->smarty->assign("ru_id", $adminru['ru_id']);
            return $this->smarty->display("templates.dwt");
        }
        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $template_mall_list = template_mall_list();
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($template_mall_list, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('default_templates', $template_mall_list['list']);
            $this->smarty->assign('filter', $template_mall_list['filter']);
            $this->smarty->assign('record_count', $template_mall_list['record_count']);
            $this->smarty->assign('page_count', $template_mall_list['page_count']);
            $this->smarty->assign('template_type', 'seller');

            return make_json_result(
                $this->smarty->fetch('templates.dwt'),
                '',
                ['filter' => $template_mall_list['filter'], 'page_count' => $template_mall_list['page_count']]
            );
        } /*页面左侧属性*/
        elseif ($_REQUEST['act'] == 'generate') {
            $result = ['error' => '', 'content' => ''];

            $suffix = isset($_REQUEST['suffix']) ? addslashes($_REQUEST['suffix']) : 'store_tpl_1';
            $bg_color = isset($_REQUEST['bg_color']) ? stripslashes($_REQUEST['bg_color']) : '';
            $is_show = isset($_REQUEST['is_show']) ? intval($_REQUEST['is_show']) : 0;
            $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'hrad';
            $bgshow = isset($_REQUEST['bgshow']) ? addslashes($_REQUEST['bgshow']) : '';
            $bgalign = isset($_REQUEST['bgalign']) ? addslashes($_REQUEST['bgalign']) : '';
            $theme = '';

            $sql = "SELECT id  FROM" . $this->dsc->table('templates_left') . " WHERE ru_id = '" . $adminru['ru_id'] . "' AND seller_templates = '$suffix' AND type='$type' AND theme = '$theme'";
            $id = $this->db->getOne($sql);
            if ($id > 0) {
                $sql = "UPDATE " . $this->dsc->table('templates_left') . " SET seller_templates = '$suffix',bg_color = '$bg_color' ,if_show = '$is_show',bgrepeat='$bgshow',align= '$bgalign',type='$type' WHERE ru_id = '" . $adminru['ru_id'] . "' AND seller_templates = '$suffix' AND id='$id' AND type='$type' AND theme = '$theme'";
            } else {
                $sql = "INSERT INTO " . $this->dsc->table('templates_left') . " (`ru_id`,`seller_templates`,`bg_color`,`if_show`,`bgrepeat`,`align`,`type`) VALUES ('" . $adminru['ru_id'] . "','$suffix','$bg_color','$is_show','$bgshow','$bgalign','$type')";
            }
            if ($this->db->query($sql) == true) {
                $result['error'] = 1;
            } else {
                $result['error'] = 2;
                $result['content'] = $GLOBALS['_LANG']['system_error_retry'];
            }
            return response()->json($result);
        } /*删除图片*/
        elseif ($_REQUEST['act'] == 'remove_img') {
            $fileimg = isset($_REQUEST['fileimg']) ? addslashes($_REQUEST['fileimg']) : '';
            $suffix = isset($_REQUEST['suffix']) ? addslashes($_REQUEST['suffix']) : '';
            $type = isset($_REQUEST['type']) ? addslashes($_REQUEST['type']) : '';
            if ($fileimg != '') {
                @unlink($fileimg);
            }
            $sql = "UPDATE " . $this->dsc->table('templates_left') . " SET img_file = '' WHERE ru_id = '" . $adminru['ru_id'] . "' AND type = '$type' AND seller_templates = '$suffix' AND theme = ''";
            $this->db->query($sql);
        } /*编辑模板信息*/
        elseif ($_REQUEST['act'] == 'edit_information') {
            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);
            $id = $adminru['ru_id'];
            $tem = isset($_REQUEST['tem']) ? addslashes($_REQUEST['tem']) : '';
            $name = isset($_REQUEST['name']) ? "tpl name：" . addslashes($_REQUEST['name']) : 'tpl name：';
            $version = isset($_REQUEST['version']) ? "version：" . addslashes($_REQUEST['version']) : 'version：';
            $author = isset($_REQUEST['author']) ? "author：" . addslashes($_REQUEST['author']) : 'author：';
            $author_url = isset($_REQUEST['author_url']) ? "author url：" . $_REQUEST['author_url'] : 'author url：';
            $description = isset($_REQUEST['description']) ? "description：" . addslashes($_REQUEST['description']) : 'description：';

            $file_dir = storage_public(DATA_DIR . '/seller_templates/seller_tem_' . $id . "/" . $tem);

            if (!is_dir($file_dir)) {
                make_dir($file_dir);
            }

            $ext_cover = '';
            $file_url = '';
            if ((isset($_FILES['ten_file']['error']) && $_FILES['ten_file']['error'] == 0) || (!isset($_FILES['ten_file']['error']) && isset($_FILES['ten_file']['tmp_name']) && $_FILES['ten_file']['tmp_name'] != 'none')) {
                //检查文件格式
                if (!check_file_type($_FILES['ten_file']['tmp_name'], $_FILES['ten_file']['name'], $allow_file_types)) {
                    return sys_msg($GLOBALS['_LANG']['img_format_wrong']);
                }

                if ($_FILES['ten_file']['name']) {
                    $ext_cover = explode('.', $_FILES['ten_file']['name']);
                    $ext_cover = array_pop($ext_cover);
                } else {
                    $ext_cover = "";
                }

                $file_name = $file_dir . "/screenshot" . '.' . $ext_cover;//头部显示图片
                if (move_upload_file($_FILES['ten_file']['tmp_name'], $file_name)) {
                    $file_url = $file_name;
                }
            }
            if ($file_url == '') {
                $file_url = $_POST['textfile'] ?? '';
            }

            $ext_big = '';
            $big_file = '';
            if ((isset($_FILES['big_file']['error']) && $_FILES['big_file']['error'] == 0) || (!isset($_FILES['big_file']['error']) && isset($_FILES['big_file']['tmp_name']) && $_FILES['big_file']['tmp_name'] != 'none')) {
                //检查文件格式
                if (!check_file_type($_FILES['big_file']['tmp_name'], $_FILES['big_file']['name'], $allow_file_types)) {
                    return sys_msg($GLOBALS['_LANG']['img_format_wrong']);
                }

                if ($_FILES['big_file']['name']) {
                    $ext_big = explode('.', $_FILES['big_file']['name']);
                    $ext_big = array_pop($ext_big);
                } else {
                    $ext_big = "";
                }

                $file_name = $file_dir . "/template" . '.' . $ext_big;//头部显示图片
                if (move_upload_file($_FILES['big_file']['tmp_name'], $file_name)) {
                    $big_file = $file_name;
                }
            }

            $images_list = [$file_url, $big_file];

            foreach ($images_list as $key => $val) {
                if ($val) {
                    $images_list[$key] = str_replace(storage_public(), '', $val);
                } else {
                    unset($images_list[$key]);
                }
            }

            $this->dscRepository->getOssAddFile($images_list);

            $end = "------tpl_info------------";
            $tab = "\n";

            $html = $end . $tab . $name . $tab . "tpl url：" . $file_url . $tab . $description . $tab . $version . $tab . $author . $tab . $author_url . $tab . $end;
            $html = write_static_file_cache('tpl_info', iconv("UTF-8", "GB2312", $html), 'txt', $file_dir . '/');
            if ($html === false) {
                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'visual_editing.php?act=templates';
                return sys_msg("' . $file_dir . '/tpl_info.txt" . $GLOBALS['_LANG']['no_write_power_edit'], 1, $link);
            } else {
                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'visual_editing.php?act=templates';
                return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $link);
            }
        } /*删除模板*/
        elseif ($_REQUEST['act'] == 'removeTemplate') {
            $result = ['error' => '', 'content' => '', 'url' => ''];
            $code = isset($_REQUEST['code']) ? addslashes($_REQUEST['code']) : '';
            $ru_id = $adminru['ru_id'];
            /* 获取默认模板 */
            $sql = "SELECT seller_templates FROM" . $this->dsc->table('seller_shopinfo') . " WHERE ru_id=" . $adminru['ru_id'];
            $default_tem = $this->db->getOne($sql);
            //使用中的模板不能删除
            if ($default_tem == $code) {
                $result['error'] = 1;
                $result['content'] = $GLOBALS['_LANG']['using_cant_delete_want_change'];
            } else {
                $dir = storage_public(DATA_DIR . '/seller_templates/seller_tem_' . $ru_id . "/" . $code);//模板目录

                $file = [];
                $format = ['png', 'gif', 'jpg', 'jpeg'];
                foreach ($format as $key => $val) {
                    $fileDir = str_replace(storage_public(), '', $dir);

                    $file['screenshot'][$key] = $fileDir . '/screenshot.' . $val;
                    $file['template'][$key] = $fileDir . '/template.' . $val;
                }

                $this->dscRepository->getOssDelFile($file['screenshot']);
                $this->dscRepository->getOssDelFile($file['template']);

                $rmdir = getDelDirAndFile($dir);
                if ($rmdir == true) {
                    $result['error'] = 0;
                    /* 店铺模板 */
                    $seller_dir = storage_public(DATA_DIR . '/seller_templates/seller_tem_' . $adminru['ru_id'] . '/');
                    $template_dir = @opendir($seller_dir);
                    while ($file = readdir($template_dir)) {
                        if ($file != '.' && $file != '..' && $file != '.svn' && $file != 'index.htm') {
                            $available_templates[] = get_seller_template_info($file, $adminru['ru_id']);
                        }
                    }
                    $available_templates = get_array_sort($available_templates, 'sort');
                    @closedir($template_dir);
                    $this->smarty->assign('available_templates', $available_templates);
                    /* 获取店铺正在使用的模板名称 */
                    $sql = "SELECT seller_templates FROM" . $this->dsc->table('seller_shopinfo') . " WHERE ru_id=" . $adminru['ru_id'];
                    $tem = $this->db->getOne($sql);
                    $this->smarty->assign('default_tem', $tem);
                    $this->smarty->assign('temp', 'backupTemplates');
                    $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
                } else {
                    $result['error'] = 1;
                    $result['content'] = $GLOBALS['_LANG']['system_error_retry'];
                }
            }

            return response()->json($result);
        } /*恢复默认模板*/
        elseif ($_REQUEST['act'] == 'defaultTemplate') {
            $code = isset($_REQUEST['code']) ? addslashes($_REQUEST['code']) : '';
            $dir = storage_public(DATA_DIR . '/seller_templates/seller_tem_' . $adminru['ru_id'] . "/" . $code);//模板目录
            $file_html = storage_public(DATA_DIR . '/seller_templates/seller_tem/' . $code); //默认模板目录
            if (!is_dir($dir)) {
                make_dir($dir);
            }
            recurse_copy($file_html, $dir);
            return dsc_header("Location:visual_editing.php?act=templates\n");
        } /*备份*/
        elseif ($_REQUEST['act'] == 'backupTemplates') {
            $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

            $result = ['error' => '', 'content' => ''];
            $code = isset($_REQUEST['tem']) ? addslashes($_REQUEST['tem']) : '';
            $type = isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 0;
            $id = $adminru['ru_id'];
            $name = isset($_REQUEST['name']) ? "tpl name：" . addslashes($_REQUEST['name']) : 'tpl name：';
            $version = isset($_REQUEST['version']) ? "version：" . addslashes($_REQUEST['version']) : 'version：';
            $author = isset($_REQUEST['author']) ? "author：" . addslashes($_REQUEST['author']) : 'author：';
            $author_url = isset($_REQUEST['author_url']) ? "author url：" . $_REQUEST['author_url'] : 'author url：';
            $description = isset($_REQUEST['description']) ? "description：" . addslashes($_REQUEST['description']) : 'description：';
            $format = ['png', 'gif', 'jpg'];
            if ($code) {
                $file_html = storage_public(DATA_DIR . '/seller_templates/seller_tem_' . $adminru['ru_id'] . "/" . $code); //默认模板目录
                $new_dirName = get_new_dir_name($adminru['ru_id']);//获取新的文件名称
                $dir = storage_public(DATA_DIR . '/seller_templates/seller_tem_' . $adminru['ru_id'] . "/" . $new_dirName);//模板目录
                if (!is_dir($dir)) {
                    make_dir($dir);
                }
                recurse_copy($file_html, $dir);

                /*编辑模板信息*/
                $file_url = '';
                $file_dir = storage_public(DATA_DIR . '/seller_templates/seller_tem_' . $id . "/" . $new_dirName);
                if (!is_dir($file_dir)) {
                    make_dir($file_dir);
                }
                if ((isset($_FILES['ten_file']['error']) && $_FILES['ten_file']['error'] == 0) || (!isset($_FILES['ten_file']['error']) && isset($_FILES['ten_file']['tmp_name']) && $_FILES['ten_file']['tmp_name'] != 'none')) {
                    //检查文件格式
                    if (!check_file_type($_FILES['ten_file']['tmp_name'], $_FILES['ten_file']['name'], $allow_file_types)) {
                        return sys_msg($GLOBALS['_LANG']['img_format_wrong']);
                    }

                    if ($_FILES['ten_file']['name']) {
                        $ext_cover = explode('.', $_FILES['ten_file']['name']);
                        $ext_cover = array_pop($ext_cover);
                    } else {
                        $ext_cover = "";
                    }

                    $file_name = $file_dir . "/";//头部显示图片
                    $filename = "screenshot." . $ext_cover;
                    $goods_thumb = $image->make_thumb($_FILES['ten_file']['tmp_name'], 265, 388, $file_name, '', $filename);
                    if ($goods_thumb != false) {
                        $file_url = $goods_thumb;
                    }
                }
                if ($file_url == '') {
                    $file_url = $_POST['textfile'] ?? '';
                }
                if ((isset($_FILES['big_file']['error']) && $_FILES['big_file']['error'] == 0) || (!isset($_FILES['big_file']['error']) && isset($_FILES['big_file']['tmp_name']) && $_FILES['big_file']['tmp_name'] != 'none')) {
                    //检查文件格式
                    if (!check_file_type($_FILES['big_file']['tmp_name'], $_FILES['big_file']['name'], $allow_file_types)) {
                        return sys_msg($GLOBALS['_LANG']['img_format_wrong']);
                    }

                    if ($_FILES['big_file']['name']) {
                        $ext_big = explode('.', $_FILES['big_file']['name']);
                        $ext_big = array_pop($ext_big);
                    } else {
                        $ext_big = "";
                    }

                    $file_name = $file_dir . "/template" . '.' . $ext_big;//头部显示图片
                    if (move_upload_file($_FILES['big_file']['tmp_name'], $file_name)) {
                        $big_file = $file_name;
                    }
                }
                $template_dir_img = @opendir($file_dir);
                while ($file = readdir($template_dir_img)) {
                    foreach ($format as $val) {
                        if ($val != $ext_cover && $ext_cover != '') {
                            /*删除同名其他后缀名的模板封面*/
                            if (file_exists($file_dir . '/screenshot.' . $val)) {
                                @unlink($file_dir . '/screenshot.' . $val);
                            }
                        }
                        if ($val != $ext_big && $ext_big != '') {
                            /*删除同名其他后缀名的模板大图*/
                            if (file_exists($file_dir . '/template.' . $val)) {
                                @unlink($file_dir . '/template.' . $val);
                            }
                        }
                    }
                }
                @closedir($template_dir_img);
                $end = "------tpl_info------------";
                $tab = "\n";

                $html = $end . $tab . $name . $tab . "tpl url：" . $file_url . $tab . $description . $tab . $version . $tab . $author . $tab . $author_url . $tab . $end;

                write_static_file_cache('tpl_info', iconv("UTF-8", "GB2312", $html), 'txt', $file_dir . '/');

                /*店铺模板*/
                $seller_dir = storage_public(DATA_DIR . '/seller_templates/seller_tem_' . $adminru['ru_id'] . '/');
                $template_dir = @opendir($seller_dir);
                while ($file = readdir($template_dir)) {
                    if ($file != '.' && $file != '..' && $file != '.svn' && $file != 'index.htm') {
                        $available_templates[] = get_seller_template_info($file, $adminru['ru_id']);
                    }
                }
                $available_templates = get_array_sort($available_templates, 'sort');
                @closedir($template_dir);

                $this->smarty->assign('available_templates', $available_templates);
                /*获取店铺正在使用的模板名称*/
                $sql = "SELECT seller_templates FROM" . $this->dsc->table('seller_shopinfo') . " WHERE ru_id=" . $adminru['ru_id'];
                $tem = $this->db->getOne($sql);
                $this->smarty->assign('default_tem', $tem);
                $this->smarty->assign('temp', 'backupTemplates');
                $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
            } else {
                $result['error'] = 1;
                $result['content'] = $GLOBALS['_LANG']['please_backup_tpl'];
            }
            return response()->json($result);
        } /*导出*/
        elseif ($_REQUEST['act'] == 'export_tem') {
            $checkboxes = !empty($_REQUEST['checkboxes']) ? $_REQUEST['checkboxes'] : [];
            if (!empty($checkboxes)) {
                $zip = new Phpzip;
                $dir = storage_public(DATA_DIR . '/seller_templates/seller_tem_' . $adminru['ru_id'] . '/');
                $dir_zip = $dir;
                $file_mune = [];
                foreach ($checkboxes as $v) {
                    if ($v) {
                        $addfiletozip = $zip->get_filelist($dir_zip . $v);//获取所有目标文件
                        foreach ($addfiletozip as $k => $val) {
                            if ($v) {
                                $addfiletozip[$k] = $v . "/" . $val;
                            }
                        }
                        $file_mune = array_merge($file_mune, $addfiletozip);
                    }
                }
                /*写入压缩文件*/
                foreach ($file_mune as $v) {
                    if (file_exists($dir . "/" . $v)) {
                        $zip->add_file(file_get_contents($dir . "/" . $v), $v);
                    }
                }

                //下面是输出下载;
                $filename = "templates_list.zip";
                return response()->streamDownload(function () use ($zip) {
                    echo $zip->file();
                }, $filename);
            } else {
                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'visual_editing.php?act=templates';
                return sys_msg($GLOBALS['_LANG']['please_select_export_tpl'], 1, $link);
            }
        } //发布
        elseif ($_REQUEST['act'] == 'downloadModal') {
            $result = ['error' => '', 'message' => ''];
            $code = isset($_REQUEST['suffix']) ? trim($_REQUEST['suffix']) : '';
            $topic_type = isset($_REQUEST['topic_type']) ? trim($_REQUEST['topic_type']) : '';
            if ($topic_type == 'topic_type') {
                $dir = storage_public(DATA_DIR . "/topic/topic_" . $adminru['ru_id'] . "/" . $code . "/temp");//原目录
                $file = storage_public(DATA_DIR . "/topic/topic_" . $adminru['ru_id'] . "/" . $code);//目标目录
            } else {
                $dir = storage_public(DATA_DIR . '/seller_templates/seller_tem_' . $adminru['ru_id'] . '/' . $code . "/temp");//原模板目录
                $file = storage_public(DATA_DIR . '/seller_templates/seller_tem_' . $adminru['ru_id'] . '/' . $code);//原模板目录
            }
            if (!empty($code)) {
                //新建目录
                if (!is_dir($dir)) {
                    make_dir($dir);
                }
                recurse_copy($dir, $file, 1);//移动缓存文件
                getDelDirAndFile($dir);//删除缓存文件
                $result['error'] = 0;
            }

            /* 存入OSS start */
            if (!isset($GLOBALS['_CFG']['open_oss'])) {
                $sql = "SELECT value FROM " . $this->dsc->table('shop_config') . " WHERE code = 'open_oss'";
                $is_oss = $this->db->getOne($sql, true);
            } else {
                $is_oss = $GLOBALS['_CFG']['open_oss'];
            }

            if (!isset($GLOBALS['_CFG']['server_model'])) {
                $sql = 'SELECT value FROM ' . $GLOBALS['ecs']->table('shop_config') . " WHERE code = 'server_model'";
                $server_model = $GLOBALS['db']->getOne($sql, true);
            } else {
                $server_model = $GLOBALS['_CFG']['server_model'];
            }

            $pin_region_list = '';

            if ($is_oss && $server_model) {
                if ($topic_type == 'topic_type') {
                    $dir = storage_public(DATA_DIR . "/topic/topic_" . $adminru['ru_id'] . "/" . $code . "/");
                    $path = DATA_DIR . "/topic/topic_" . $adminru['ru_id'] . "/" . $code . "/";
                    $file_list = get_recursive_file_oss($dir, $path, true);

                    $this->dscRepository->getOssAddFile($file_list);

                    dsc_unlink(storage_public(DATA_DIR . '/sc_file/topic/topic_' . $adminru['ru_id'] . "/" . $code . ".php"));

                    $id_data = read_static_cache('urlip_list');

                    if ($pin_region_list !== false) {
                        $this->dscRepository->getDelVisualTemplates($id_data, $code, 'del_topictemplates', $adminru['ru_id']);
                    }
                } else {
                    $dir = storage_public(DATA_DIR . '/seller_templates/seller_tem_' . $adminru['ru_id'] . '/' . $code . "/");
                    $path = DATA_DIR . '/seller_templates/seller_tem_' . $adminru['ru_id'] . '/' . $code . "/";
                    $file_list = get_recursive_file_oss($dir, $path, true);

                    $this->dscRepository->getOssAddFile($file_list);

                    dsc_unlink(storage_public(DATA_DIR . '/sc_file/sellertemplates/seller_tem_' . $adminru['ru_id'] . '/' . $code . ".php"));

                    $id_data = read_static_cache('urlip_list');

                    if ($pin_region_list !== false) {
                        $this->dscRepository->getDelVisualTemplates($id_data, $code, 'del_sellertemplates', $adminru['ru_id']);
                    }
                }
            }
            /* 存入OSS end */

            return response()->json($result);
        } //还原
        elseif ($_REQUEST['act'] == 'backmodal') {
            $result = ['error' => '', 'message' => ''];
            $code = isset($_REQUEST['suffix']) ? trim($_REQUEST['suffix']) : '';
            $topic_type = isset($_REQUEST['topic_type']) ? trim($_REQUEST['topic_type']) : '';
            if ($topic_type == 'topic_type') {
                $dir = storage_public(DATA_DIR . "/topic/topic_" . $adminru['ru_id'] . "/" . $code . "/temp");//原目录
            } else {
                $dir = storage_public(DATA_DIR . '/seller_templates/seller_tem_' . $adminru['ru_id'] . '/' . $code . "/temp");//原模板目录
            }
            if (!empty($code)) {
                getDelDirAndFile($dir);//删除缓存文件
                $result['error'] = 0;
            }
            return response()->json($result);
        } //删除模板订单
        elseif ($_REQUEST['act'] == 'remove') {
            $apply_id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $sql = "DELETE FROM" . $this->dsc->table('seller_template_apply') . "WHERE apply_id = '$apply_id' AND ru_id = '" . $adminru['ru_id'] . "'AND pay_status = 0";
            $this->db->query($sql);
            $url = 'visual_editing.php?act=apply_query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        }
    }
}
