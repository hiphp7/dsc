<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Repositories\Common\BaseRepository;
use App\Services\User\InvoiceService;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class InvoiceController
 * @package App\Api\Fourth\Controllers
 */
class InvoiceController extends Controller
{
    private $invoiceService;
    private $userService;
    private $baseRepository;

    public function __construct(
        InvoiceService $invoiceService,
        UserService $userService,
        BaseRepository $baseRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->invoiceService = $invoiceService;
        $this->userService = $userService;
    }

    /**
     * 添加个人发票详情
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        $invoice_type = $request->input('invoice_type', 0); // 0 普通发票 1 增值发票

        //返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $info = $request->all();

        $result = [];
        if ($invoice_type == 0) {
            // 普通发票
            $this->validate($request, [
                'inv_payee' => 'required|string',//发票抬头
            ]);

            $inv_payee = $request->input('inv_payee', ''); // 发票抬头
            $tax_id = $request->input('tax_id', 0); // 纳税人识别号

            $result = $this->invoiceService->addOrderInvoice($user_id, $inv_payee, $tax_id);
        } elseif ($invoice_type == 1) {
            // 增值发票
            $this->validate($request, [
                'company_name' => 'required|string',//公司名称
                'tax_id' => 'required|string',//税号
                'company_address' => 'required|string',//公司地址
                'company_telephone' => 'required|string',//公司电话
                'bank_of_deposit' => 'required|string',//开户行
                'bank_account' => 'required|string',//银行卡号
                'consignee_name' => 'required|string',
                'consignee_mobile_phone' => 'required|string',
                'consignee_address' => 'required|string',
                'country' => 'required|integer',
                'province' => 'required|integer',
                'city' => 'required|integer',
                'district' => 'required|integer',
            ]);

            $info['user_id'] = $user_id;

            $result = $this->invoiceService->addUsersVatInvoices($info);
        }

        return $this->succeed($result);
    }

    /**
     * 个人发票详情
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function index(Request $request)
    {
        //数据验证
        $this->validate($request, [
        ]);

        //返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $info = $request->all();

        $info['user_id'] = $user_id;

        // 普通发票
        $order_invoice_info = $this->invoiceService->getOrderInvoice($user_id);

        // 增值发票
        $vat_invoice_info = $this->userService->getUsersVatInvoicesInfo($info);
        if (!empty($vat_invoice_info['id'])) {
            $region = $this->invoiceService->userVatConsigneeRegion($vat_invoice_info['id']);
            $vat_invoice_info['region'] = $region['region'] ?? '';
            $vat_invoice_info['province_name'] = $region['province_name'] ?? '';
            $vat_invoice_info['city_name'] = $region['city_name'] ?? '';
            $vat_invoice_info['district_name'] = $region['district_name'] ?? '';
        }

        $invoice = [
            'order_invoice_info' => $order_invoice_info,
            'user_vat_invoice' => $vat_invoice_info
        ];

        return $this->succeed($invoice);
    }

    /**
     * 删除个人发票
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function destroy(Request $request)
    {
        $invoice_type = $request->input('invoice_type', 0); // 0 普通发票 1 增值发票

        //返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $info = $request->all();

        $result = [];
        if ($invoice_type == 0) {
            // 普通发票
            $this->validate($request, [
                'invoice_id' => 'required|integer',
            ]);

            $invoice_id = $request->input('invoice_id', 0);

            $result = $this->invoiceService->orderInvoiceDestroy($invoice_id);
        }
        if ($invoice_type == 1) {
            // 增值发票
            $this->validate($request, [
                'id' => 'required|integer',
            ]);

            $info['user_id'] = $user_id;

            $result = $this->invoiceService->invoicesDestroy($info);
        }

        return $this->succeed($result);
    }

    /**
     * 更新个人发票
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request)
    {
        $invoice_type = $request->input('invoice_type', 0); // 0 普通发票 1 增值发票

        //返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $info = $request->all();

        $result = [];
        if ($invoice_type == 0) {
            // 普通发票
            $this->validate($request, [
                'invoice_id' => 'required|integer',
            ]);

            $invoice_id = $request->input('invoice_id', ''); // 发票id

            $inv_payee = $request->input('inv_payee', ''); // 发票抬头
            $tax_id = $request->input('tax_id', 0); // 纳税人识别号

            $data = [
                'inv_payee' => $inv_payee,
                'tax_id' => $tax_id
            ];
            $result = $this->invoiceService->updateOrderInvoice($invoice_id, $data);
        } elseif ($invoice_type == 1) {
            // 增值发票
            $this->validate($request, [
                'id' => 'required|integer',
                'company_name' => 'required|string',//公司名称
                'tax_id' => 'required|string',//税号
                'company_address' => 'required|string',//公司地址
                'company_telephone' => 'required|string',//公司电话
                'bank_of_deposit' => 'required|string',//开户行
                'bank_account' => 'required|string',//银行卡号
                'consignee_name' => 'required|string',
                'consignee_mobile_phone' => 'required|string',
                'consignee_address' => 'required|string',
                'province' => 'required|integer',
                'city' => 'required|integer',
                'district' => 'required|integer',
                'street' => 'integer',
            ]);

            $info['user_id'] = $user_id;
            $info['country'] = 1;
            $info['audit_status'] = 0;

            $result = $this->invoiceService->updateUsersVatInvoices($info);
        }

        return $this->succeed($result);
    }
}
