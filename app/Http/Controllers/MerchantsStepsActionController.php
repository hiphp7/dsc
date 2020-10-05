<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\MerchantsShopInformation;
use App\Models\MerchantsStepsFields;
use App\Services\CrossBorder\CrossBorderService;

/**
 * 购物流程
 */
class MerchantsStepsActionController extends InitController
{
    public function index()
    {

        /* ------------------------------------------------------ */
        //-- 判断是否存在缓存，如果存在则调用缓存，反之读取相应内        容
        /* ------------------------------------------------------ */

        $user_id = session('user_id', 0);

        $step = htmlspecialchars(trim(request()->input('step', '')));

        $sid = (int)request()->input('sid', 1);
        //协议
        $agreement = (int)request()->input('agreement', 0);
        //KEY传值
        $pid_key = (int)request()->input('pid_key', 0);
        //为空则显示品牌列表，否则添加或编辑品牌信息
        $brandView = htmlspecialchars(trim(request()->input('brandView', '')));
        $brandId = (int)request()->input('brandId', 0);

        $search_brandType = htmlspecialchars(request()->input('search_brandType', ''));
        $searchBrandZhInput = htmlspecialchars(trim(request()->input('searchBrandZhInput', '')));
        $searchBrandZhInput = !empty($searchBrandZhInput) ? addslashes($searchBrandZhInput) : '';
        $searchBrandEnInput = htmlspecialchars(trim(request()->input('searchBrandEnInput', '')));
        $searchBrandEnInput = !empty($searchBrandEnInput) ? addslashes($searchBrandEnInput) : '';

        if (CROSS_BORDER === true) // 跨境多商户
        {
            // cbec
            $huoyuan = trim(request()->input('huoyuan', ''));
        }

        if ($user_id <= 0) {
            return show_message($GLOBALS['_LANG']['steps_UserLogin'], $GLOBALS['_LANG']['UserLogin'], 'user.php');
        }

        $sf_agreement = MerchantsStepsFields::where('user_id', $user_id)->value('agreement');

        if ($sf_agreement != 1) {
            if ($agreement == 1) {
                $parent = [
                    'user_id' => $user_id,
                    'agreement' => $agreement
                ];

                MerchantsStepsFields::insert($parent);
            }
        } else {
            $shopTime_term = (int)request()->input('shopTime_term', 0);
            if ($pid_key == 2 && $step == 'stepTwo') {
                $parent = [
                    'shopTime_term' => $shopTime_term
                ];
                MerchantsStepsFields::where('user_id', $user_id)->update($parent);
            }

            $process_list = get_root_steps_process_list($sid);
            $process = isset($process_list[$pid_key]) && $process_list[$pid_key] ? $process_list[$pid_key] : '';


            $noWkey = $pid_key - 1;
            $noWprocess = $process_list[$noWkey];
            $form = get_steps_title_insert_form($noWprocess['id']);

            $parent = isset($form['formName']) ? get_setps_form_insert_date($form['formName']) : '';

            $parent['site_process'] = isset($parent['site_process']) && !empty($parent['site_process']) ? addslashes($parent['site_process']) : '';

            MerchantsStepsFields::where('user_id', $user_id)->update($parent);

            if (CROSS_BORDER === true) // 跨境多商户
            {
                $web = app(CrossBorderService::class)->webExists();

                if (!empty($web)) {
                    $web->updateSource($user_id, $huoyuan);
                }
            }

            if ($step == 'stepTwo') {
                if (!is_array($process)) {
                    $step = 'stepThree';
                    $pid_key = 0;
                } else {
                    $step = 'stepTwo';
                    $pid_key = $pid_key;
                }
            } elseif ($step == 'stepThree') {
                if (!is_array($process)) {
                    $ec_rz_shopName = addslashes(trim(request()->input('ec_rz_shopName', '')));
                    $ec_hopeLoginName = addslashes(trim(request()->input('ec_hopeLoginName', '')));

                    $user = MerchantsShopInformation::where('rz_shopName', $ec_rz_shopName)->where('user_id', '<>', $user_id)->value('user_id');
                    if ($user) {
                        return show_message($GLOBALS['_LANG']['Settled_Prompt'], $GLOBALS['_LANG']['Return_last_step'], "merchants_steps.php?step=" . $step . "&pid_key=" . $noWkey);
                    } else {
                        MerchantsShopInformation::where('user_id', $user_id)->update(['steps_audit' => 1, 'merchants_audit' => 0]);

                        $step = 'stepSubmit';
                        $pid_key = 0;
                    }

                    $user = AdminUser::where('user_name', $ec_hopeLoginName)->where('ru_id', '<>', $user_id)->value('user_id');
                    if ($user) {
                        return show_message($GLOBALS['_LANG']['Settled_Prompt_name'], $GLOBALS['_LANG']['Return_last_step'], "merchants_steps.php?step=" . $step . "&pid_key=" . $noWkey);
                    } else {
                        MerchantsShopInformation::where('user_id', $user_id)->update(['steps_audit' => 1]);

                        $step = 'stepSubmit';
                        $pid_key = 0;
                    }
                }
            }
        }

        if (empty($step)) {
            $step = 'stepOne';
        }

        //操作品牌 start
        $act = '';
        if ($brandView == "brandView") {
            $pid_key -= 1;
        } elseif ($brandView == "add_brand") { //添加新品牌
            if ($brandId > 0) {
                $act .= "&brandId=" . $brandId . '&search_brandType=' . $search_brandType;
            }

            if ($searchBrandZhInput != '') {
                $act .= "&searchBrandZhInput=" . $searchBrandZhInput;
            }

            if ($searchBrandEnInput != '') {
                $act .= "&searchBrandEnInput=" . $searchBrandEnInput;
            }


            $act .= "&brandView=brandView";
        }
        //操作品牌 end

        $steps_site = "merchants_steps.php?step=" . $step . "&pid_key=" . $pid_key . $act;
        $site_process = MerchantsStepsFields::where('user_id', $user_id)->value('site_process');
        $site_process = $site_process ? $site_process : '';

        $strpos = $site_process ? strpos($site_process, $steps_site) : false;
        if ($strpos === false) { //不存在
            if (!empty($site_process)) {
                $site_process .= ',' . $steps_site;
            } else {
                $site_process = $steps_site;
            }

            $other = [
                'steps_site' => $steps_site,
                'site_process' => $site_process
            ];
            MerchantsStepsFields::where('user_id', $user_id)->update($other);
        }

        if (CROSS_BORDER === true) // 跨境多商户
        {
            $web = app(CrossBorderService::class)->webExists();

            if (!empty($web)) {
                $web->smartyAssign();
            }
        }
        return dsc_header("Location: " . $steps_site . "\n");
    }
}
