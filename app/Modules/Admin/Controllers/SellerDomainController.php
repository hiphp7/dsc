<?php

namespace App\Modules\Admin\Controllers;


use App\Models\SellerDomain;
use App\Repositories\Common\BaseRepository;
use App\Services\Merchant\MerchantCommonService;
use App\Services\SellerDomain\SellerDomainManageService;

class SellerDomainController extends InitController
{
    protected $merchantCommonService;
    protected $baseRepository;
    protected $sellerDomainManageService;


    public function __construct(
        MerchantCommonService $merchantCommonService,
        BaseRepository $baseRepository,
        SellerDomainManageService $sellerDomainManageService
    )
    {
        $this->merchantCommonService = $merchantCommonService;
        $this->baseRepository = $baseRepository;
        $this->sellerDomainManageService = $sellerDomainManageService;
    }

    public function index()
    {
        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['seller_domain']);
            $adminru = get_admin_ru_id();

            $this->smarty->assign('ru_id', $adminru['ru_id']);
            $domain_list = $this->sellerDomainManageService->sellerDomainList();

            $this->smarty->assign('domain_list', $domain_list['domain_list']);
            $this->smarty->assign('filter', $domain_list['filter']);
            $this->smarty->assign('record_count', $domain_list['record_count']);
            $this->smarty->assign('page_count', $domain_list['page_count']);
            $this->smarty->assign('full_page', 1);
            return $this->smarty->display("seller_domain.dwt");
        }
        /* ------------------------------------------------------ */
        //-- 翻页，排序
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            //获取信息列表
            admin_priv('seller_dimain');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['seller_domain']);
            $domain_list = $this->sellerDomainManageService->sellerDomainList();
            $adminru = get_admin_ru_id();
            $this->smarty->assign('ru_id', $adminru['ru_id']);
            $this->smarty->assign('domain_list', $domain_list['domain_list']);
            $this->smarty->assign('filter', $domain_list['filter']);
            $this->smarty->assign('record_count', $domain_list['record_count']);
            $this->smarty->assign('page_count', $domain_list['page_count']);
            //跳转页面
            return make_json_result($this->smarty->fetch('seller_domain.dwt'), '', ['filter' => $domain_list['filter'], 'page_count' => $domain_list['page_count']]);
        } elseif ($_REQUEST['act'] == 'edit') {
            admin_priv('seller_dimain');
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['seller_domain_edit']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['seller_domain'], 'href' => 'seller_domain.php?act=list']);
            $id = !empty($_REQUEST['id']) ? $_REQUEST['id'] : '0';

            $res = SellerDomain::where('id', $id);
            $domain = $this->baseRepository->getToArrayFirst($res);

            if ($domain['validity_time'] == 0) {
                $domain['validity_time'] = '';
            } else {
                $domain['validity_time'] = local_date("Y-m-d H:i", $domain['validity_time']);
            }
            $this->smarty->assign('domian', $domain);

            $update = isset($_POST['sub']) ? $_POST['sub'] : '';
            if ($update) {
                $domain_name = !empty($_POST['domain_name']) ? $_POST['domain_name'] : '';
                $is_enable = !empty($_POST['is_enable']) ? $_POST['is_enable'] : '0';
                $validity_time = !empty($_POST['validity_time']) ? strtotime($_POST['validity_time']) : '0';

                $other = [
                    'domain_name' => $domain_name,
                    'is_enable' => $is_enable,
                    'validity_time' => $validity_time
                ];
                $res = SellerDomain::where('id', $id)->update($other);
                if ($res > 0) {
                    $links = [['href' => 'seller_domain.php?act=list', 'text' => $GLOBALS['_LANG']['seller_domain']]];
                    return sys_msg($GLOBALS['_LANG']['domain_edit'], 0, $links);
                }
            }
            return $this->smarty->display("seller_domain_info.dwt");
        } /*页面编辑*/

        elseif ($_REQUEST['act'] == 'is_enable') {
            admin_priv('seller_dimain');

            $id = intval(Request()->input('id', 0));

            $res = SellerDomain::where('id', $id);
            $seller = $this->baseRepository->getToArrayFirst($res);

            if (!empty($seller)) {
                $seller['is_enable'] = $seller['is_enable'] <> 1 ? 1 : 0;

                SellerDomain::where('id', $id)->update($seller);

                clear_cache_files();
                return make_json_result($seller['is_enable']);
            }
        }

        /*------------------------------------------------------ */
        //-- 放�        �回收站
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $id = intval($_REQUEST['id']);

            /* 检查权限 */
            $check_auth = check_authz_json('remove_back');
            if ($check_auth !== true) {
                return $check_auth;
            }

            SellerDomain::where('id', $id)->delete();

            $url = 'seller_domain.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }
    }
}
