<?php

namespace App\Modules\Admin\Controllers;


use App\Models\EntryCriteria;
use App\Models\SellerGrade;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\SellerGrade\SellerGradeManageService;

/*
 * 商家店铺等级
 */

class SellerGradeController extends InitController
{
    protected $dscRepository;
    protected $baseRepository;
    protected $sellerGradeManageService;

    public function __construct(
        DscRepository $dscRepository,
        BaseRepository $baseRepository,
        SellerGradeManageService $sellerGradeManageService
    )
    {
        $this->dscRepository = $dscRepository;
        $this->baseRepository = $baseRepository;
        $this->sellerGradeManageService = $sellerGradeManageService;
    }

    public function index()
    {
        $act = request()->input('act', 'list');

        /* 允许上传的文件类型 */
        $allow_file_types = '|GIF|JPG|PNG|BMP|SWF|DOC|XLS|PPT|MID|WAV|ZIP|RAR|PDF|CHM|RM|TXT|';

        /*------------------------------------------------------ */
        //-- 等级列表
        /*------------------------------------------------------ */
        if ($act == 'list') {
            admin_priv('seller_grade');

            $this->smarty->assign('menu_select', ['action' => '17_merchants', 'current' => '10_seller_grade']);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['seller_garde_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['add_seller_garde'], 'href' => 'seller_grade.php?act=add']);
            $this->smarty->assign('action_link2', ['text' => $GLOBALS['_LANG']['entry_criteria'], 'href' => 'entry_criteria.php?act=list']);

            $seller_garde = $this->sellerGradeManageService->getPzdList();

            $this->smarty->assign('garde_list', $seller_garde['pzd_list']);
            $this->smarty->assign('filter', $seller_garde['filter']);
            $this->smarty->assign('record_count', $seller_garde['record_count']);
            $this->smarty->assign('page_count', $seller_garde['page_count']);
            $this->smarty->assign('full_page', 1);

            return $this->smarty->display("seller_grade_list.dwt");
        }

        /*------------------------------------------------------ */
        //-- 等级查询列表
        /*------------------------------------------------------ */
        elseif ($act == 'query') {
            admin_priv('seller_grade');
            $seller_garde = $this->sellerGradeManageService->getPzdList();

            $this->smarty->assign('garde_list', $seller_garde['pzd_list']);
            $this->smarty->assign('filter', $seller_garde['filter']);
            $this->smarty->assign('record_count', $seller_garde['record_count']);
            $this->smarty->assign('page_count', $seller_garde['page_count']);

            //跳转页面
            return make_json_result($this->smarty->fetch('seller_grade_list.dwt'), '', ['filter' => $seller_garde['filter'], 'page_count' => $seller_garde['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 等级添加/编辑
        /*------------------------------------------------------ */
        elseif ($act == 'add' || $act == 'edit') {
            admin_priv('seller_grade');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_seller_garde']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['seller_garde_list'], 'href' => 'seller_grade.php?act=list']);

            //获取所有第一级标准
            $res = EntryCriteria::where('parent_id', 0);
            $criteria = $this->baseRepository->getToArrayGet($res);

            $id = request()->input('id', 0);
            if ($id > 0) {
                $res = SellerGrade::where('id', $id);
                $seller_grade = $this->baseRepository->getToArrayFirst($res);

                $entry_criteria = isset($seller_grade['entry_criteria']) && !empty($seller_grade['entry_criteria']) ? unserialize($seller_grade['entry_criteria']) : [];
                /*判断是否选中*/
                if (!empty($entry_criteria)) {
                    foreach ($criteria as $k => $v) {
                        foreach ($entry_criteria as $val) {
                            if ($val == $v['id']) {
                                $criteria[$k]['in_check'] = 1;
                            }
                        }
                    }
                    $this->smarty->assign('criteria', $criteria);
                }
                $seller_grade['grade_img'] = isset($seller_grade['grade_img']) ? get_image_path($seller_grade['grade_img']) : '';

                $this->smarty->assign("seller_grade", $seller_grade);
            }
            $act = ($act == 'add') ? 'insert' : 'update';
            $this->smarty->assign('act', $act);

            return $this->smarty->display("seller_grade_info.dwt");
        }

        /*------------------------------------------------------ */
        //-- 等级插入/更新数据
        /*------------------------------------------------------ */
        elseif ($act == 'insert' || $act == 'update') {
            admin_priv('seller_grade');

            $grade_name = request()->input('grade_name', '');
            $goods_sun = request()->input('goods_sun', '');
            $seller_temp = request()->input('seller_temp', '');
            $grade_introduce = request()->input('grade_introduce', '');
            $top_amount = request()->input('top_amount', '');
            $top_deal_num = request()->input('top_deal_num', '');

            $entry_criteria = request()->input('entry_criteria', []);
            $is_open = request()->input('is_open', 0);
            $is_default = request()->input('is_default', 0);
            $favorable_rate = request()->input('favorable_rate', 0);
            $give_integral = request()->input('give_integral', 0);
            $rank_integral = request()->input('rank_integral', 0);
            $pay_integral = request()->input('pay_integral', 0);
            $white_bar = request()->input('white_bar', 0);

            /* 如果默认则取消其他默认 */
            if ($is_default == 1) {
                $data = ['is_default' => 0];
                SellerGrade::where('is_default', 1)->update($data);
            }
            if ($give_integral > 100 || $rank_integral > 100 || $pay_integral > 100) {

                return sys_msg($GLOBALS['_LANG']['give_integral_rate_prompt']);
            }
            if ($give_integral < 0 || $rank_integral < 0 || $pay_integral < 0) {

                return sys_msg($GLOBALS['_LANG']['give_integral_rate_prompt']);
            }

            if ($act == 'update') {
                $id = request()->input('id', 0);
                /*检查是否重复*/
                $is_only = SellerGrade::where('grade_name', $grade_name)
                    ->where('id', '<>', $id)
                    ->count();

                if ($is_only > 0) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['title_exist'], stripslashes($grade_name)), 1);
                }

                /* 取得文件地址 */
                $file_url = '';
                if ((isset($_FILES['file']['error']) && $_FILES['file']['error'] == 0) || (!isset($_FILES['file']['error']) && isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name'] != 'none')) {
                    //检查文件格式
                    if (!check_file_type($_FILES['file']['tmp_name'], $_FILES['file']['name'], $allow_file_types)) {
                        return sys_msg($GLOBALS['_LANG']['invalid_file']);
                    }

                    //复制文件
                    $res = $this->sellerGradeManageService->uploadArticleFile($_FILES['file']);
                    if ($res != false) {
                        $file_url = $res;
                    }
                }

                if ($file_url == '') {
                    $request_file_url = request()->input('file_url', '');
                    if (!empty($request_file_url)) {
                        $return_content = $this->sellerGradeManageService->httpGetData($request_file_url);
                        $path = DATA_DIR . "/seller_grade/" . basename($request_file_url);
                        $fp = @fopen('../' . $path, "a"); //将文件绑定到流
                        @fwrite($fp, $return_content); //写入文件
                        $file_url = $path;
                    }
                    //$file_url = $_POST['file_url'];
                }

                /* 如果 file_url 跟以前不一样，且原来的文件是本地文件，删除原来的文件 */

                $old_url = SellerGrade::where('id', $id)->value('grade_img');
                $old_url = $old_url ? $old_url : '';

                if ($old_url != '' && $old_url != $file_url && strpos($old_url, 'http: ') === false && strpos($old_url, 'https: ') === false) {
                    @unlink(storage_public($old_url));
                }

                $this->dscRepository->getOssAddFile([$file_url]);

                $entry_criteria = !empty($entry_criteria) && is_array($entry_criteria) ? serialize($entry_criteria) : '';

                $data = [
                    'favorable_rate' => $favorable_rate,
                    'white_bar' => $white_bar,
                    'give_integral' => $give_integral,
                    'rank_integral' => $rank_integral,
                    'pay_integral' => $pay_integral,
                    'grade_name' => $grade_name,
                    'is_default' => $is_default,
                    'goods_sun' => $goods_sun,
                    'seller_temp' => $seller_temp,
                    'grade_introduce' => $grade_introduce,
                    'entry_criteria' => $entry_criteria,
                    'grade_img' => $file_url,
                    'is_open' => $is_open
                ];

                SellerGrade::where('id', $id)->update($data);

                $link[0]['text'] = $GLOBALS['_LANG']['bank_list'];
                $link[0]['href'] = 'seller_grade.php?act=list&' . list_link_postfix();

                clear_cache_files();
                return sys_msg($GLOBALS['_LANG']['edit_succeed'], 0, $link);

            } elseif ($act == 'insert') {
                /* 检查是否重复 */
                $is_only = SellerGrade::where('grade_name', $grade_name)->count();
                if ($is_only > 0) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['title_exist'], stripslashes($grade_name)), 1);
                }
                /* 取得文件地址 */
                $file_url = '';
                if ((isset($_FILES['file']['error']) && $_FILES['file']['error'] == 0) || (!isset($_FILES['file']['error']) && isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name'] != 'none')) {
                    // 检查文件格式
                    if (!check_file_type($_FILES['file']['tmp_name'], $_FILES['file']['name'], $allow_file_types)) {
                        return sys_msg($GLOBALS['_LANG']['invalid_file']);
                    }

                    // 复制文件
                    $res = $this->sellerGradeManageService->uploadArticleFile($_FILES['file']);
                    if ($res != false) {
                        $file_url = $res;
                    }
                }

                if ($file_url == '') {
                    $request_file_url = request()->input('file_url', '');
                    if (!empty($request_file_url)) {
                        $return_content = $this->sellerGradeManageService->httpGetData($request_file_url);
                        $path = DATA_DIR . "/seller_grade/" . basename($request_file_url);
                        $fp = @fopen($path, "w"); //将文件绑定到流
                        @fwrite($fp, $return_content); //写入文件
                        $file_url = $path;
                    }
                }

                $this->dscRepository->getOssAddFile([$file_url]);
                $entry_criteria = !empty($entry_criteria) && is_array($entry_criteria) ? serialize($entry_criteria) : '';
                $other = [
                    'grade_name' => $grade_name,
                    'goods_sun' => $goods_sun,
                    'seller_temp' => $seller_temp,
                    'grade_introduce' => $grade_introduce ?? '',
                    'entry_criteria' => $entry_criteria,
                    'grade_img' => $file_url,
                    'is_open' => $is_open,
                    'is_default' => $is_default,
                    'favorable_rate' => $favorable_rate,
                    'give_integral' => $give_integral,
                    'rank_integral' => $rank_integral,
                    'pay_integral' => $pay_integral,
                    'white_bar' => $white_bar,
                ];

                $seller_garde_id = SellerGrade::insert($other);

                if ($seller_garde_id > 0) {
                    $link[0]['text'] = $GLOBALS['_LANG']['GO_add'];
                    $link[0]['href'] = 'seller_grade.php?act=add';

                    $link[1]['text'] = $GLOBALS['_LANG']['bank_list'];
                    $link[1]['href'] = 'seller_grade.php?act=list';

                    clear_cache_files(); // 清除相关的缓存文件

                    return sys_msg($GLOBALS['_LANG']['add_succeed'], 0, $link);
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 等级介绍
        /*------------------------------------------------------ */
        elseif ($act == 'edit_grade_introduce') {
            $id = request()->input('id', 0);
            $val = request()->input('val', '');

            if ($id > 0 && !empty($val)) {
                $order = json_str_iconv(trim($val));

                $data = ['grade_introduce' => $order];
                $res = SellerGrade::where('id', $id)->update($data);

                if ($res > 0) {
                    clear_cache_files();
                    return make_json_result(stripslashes($order));
                } else {
                    return make_json_error('error');
                }
            }

            return make_json_error('error');
        }

        /*------------------------------------------------------ */
        //-- 等级优惠比例
        /*------------------------------------------------------ */
        elseif ($act == 'edit_favorable_rate') {
            $check_auth = check_authz_json('seller_grade');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = request()->input('id', 0);
            $val = request()->input('val', '');

            if ($id > 0 && !empty($val)) {
                $order = json_str_iconv(trim($val));

                $data = ['favorable_rate' => $order];
                $res = SellerGrade::where('id', $id)->update($data);

                if ($res > 0) {
                    clear_cache_files();
                    return make_json_result(stripslashes($order));
                } else {
                    return make_json_error('error');
                }
            }

            return make_json_error('error');
        }

        /*------------------------------------------------------ */
        //-- 等级是否开启
        /*------------------------------------------------------ */
        elseif ($act == 'toggle_show') {
            $check_auth = check_authz_json('seller_grade');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = request()->input('id', 0);
            $val = request()->input('val', '');

            if ($id > 0) {
                $order = json_str_iconv(trim($val));

                $data = ['is_open' => $order];
                $res = SellerGrade::where('id', $id)->update($data);

                if ($res > 0) {
                    clear_cache_files();
                    return make_json_result(stripslashes($order));
                } else {
                    return make_json_error('error');
                }
            }

            return make_json_error('error');
        }

        /*------------------------------------------------------ */
        //-- 等级发布商品数量
        /*------------------------------------------------------ */
        elseif ($act == 'edit_goods_sun') {
            $check_auth = check_authz_json('seller_grade');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = request()->input('id', 0);
            $val = request()->input('val', '');

            if ($id > 0 && !empty($val)) {
                $order = json_str_iconv(trim($val));

                $data = ['goods_sun' => $order];
                $res = SellerGrade::where('id', $id)->update($data);

                if ($res > 0) {
                    clear_cache_files();
                    return make_json_result(stripslashes($order));
                } else {
                    return make_json_error('error');
                }
            }

            return make_json_error('error');
        }

        /*------------------------------------------------------ */
        //-- 等级店铺模板数量
        /*------------------------------------------------------ */
        elseif ($act == 'edit_seller_temp') {
            $check_auth = check_authz_json('seller_grade');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = request()->input('id', 0);
            $val = request()->input('val', '');

            if ($id > 0 && !empty($val)) {
                $order = json_str_iconv(trim($val));

                $data = ['seller_temp' => $order];
                $res = SellerGrade::where('id', $id)->update($data);

                if ($res > 0) {
                    clear_cache_files();
                    return make_json_result(stripslashes($order));
                } else {
                    return make_json_error('error');
                }
            }

            return make_json_error('error');
        }

        /*------------------------------------------------------ */
        //-- 删除
        /*------------------------------------------------------ */
        elseif ($act == 'remove') {
            $check_auth = check_authz_json('seller_grade');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = request()->input('id', 0);

            if ($id > 0) {
                /* 删除原来的文件 */

                $old_url = SellerGrade::where('id', $id)->value('grade_img');
                $old_url = $old_url ? $old_url : '';

                if ($old_url != '' && strpos($old_url, 'http://') === false && strpos($old_url, 'https://') === false) {
                    @unlink(storage_public($old_url));
                }

                $this->dscRepository->getOssDelFile([$old_url]);

                SellerGrade::where('id', $id)->delete();

                admin_log(addslashes(session('admin_name')), 'remove', 'business');
            }

            $url = 'seller_grade.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        }
    }
}
