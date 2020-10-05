<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Services\Common\CommonService;
use App\Services\Wholesale\PurchaseService;
use App\Services\Wholesale\WholesaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class WholesalePurchaseController
 * @package App\Api\Fourth\Controllers
 */
class WholesalePurchaseController extends Controller
{
    protected $purchaseService;
    protected $wholesaleService;
    protected $commonService;

    public function __construct(
        PurchaseService $purchaseService,
        WholesaleService $wholesaleService,
        CommonService $commonService
    )
    {
        $this->purchaseService = $purchaseService;
        $this->wholesaleService = $wholesaleService;
        $this->commonService = $commonService;
    }

    /**求购信息列表
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function list(Request $request)
    {

        //数据验证
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer',
            'is_finished' => 'integer',
        ]);
        /**
         * 获取会员id
         */
        $user_id = $this->authorization();

        $wholesaleUse = $this->commonService->judgeWholesaleUse($user_id);

        if ($wholesaleUse['return']) {
            if ($user_id) {
                return $this->setStatusCode(102)->failed(lang('common.not_seller_user'));
            } else {
                return $this->setStatusCode(102)->failed(lang('common.not_login_user'));
            }
        }
        $page = $request->get('page') ? intval($request->get('page')) : 1;
        $size = $request->get('size') ? intval($request->get('size')) : 10;

        $data = [];
        $data['is_finished'] = $request->get('is_finished') ?? -1;
        $data['keyword'] = $request->post('keyword') ? $request->post('keyword') : '';
        $data['review_status'] = 1;

        //批发列表
        $WholesaleList = $this->purchaseService->getPurchaseList($data, $size, $page);
        $WholesaleList = $WholesaleList['purchase_list'];

        return $this->succeed($WholesaleList);
    }

    /**求购信息详情
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function info(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'id' => 'required|integer',
        ]);
        /**
         * 获取会员id
         */
        $user_id = $this->authorization();

        $wholesaleUse = $this->commonService->judgeWholesaleUse($user_id);
        if ($wholesaleUse['return']) {
            if ($user_id) {
                return $this->setStatusCode(102)->failed(lang('common.not_seller_user'));
            } else {
                return $this->setStatusCode(102)->failed(lang('common.not_login_user'));
            }
        }

        $id = $request->get('id') ? intval($request->get('id')) : 0;

        $is_merchant = 0;
        if ($user_id) {
            //是否是商家
            $is_merchant = $this->wholesaleService->getCheckUserIsMerchant($user_id);
        }

        //求购详情
        $info = $this->purchaseService->getPurchaseInfo($id);

        $data['purchase_info'] = $info;
        $data['is_merchant'] = $is_merchant;
        return $this->succeed($data);
    }
}
