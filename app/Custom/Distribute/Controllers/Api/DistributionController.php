<?php

namespace App\Custom\Distribute\Controllers\Api;

use App\Api\Fourth\Controllers\DistributionController as FrontController;
use App\Custom\CustomView;
use App\Custom\Distribute\Services\DistributeService;
use Illuminate\Http\Request;


class DistributionController extends FrontController
{
    use CustomView;

    protected function initialize()
    {
        $this->load_helper('helpers');

        // 当前模块语言包
        $_lang = $this->load_lang(['common', 'drp']);
        L($_lang);
    }

    /**
     * 开发 分销商 分享统计甄选师
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function drp_invite_info(Request $request)
    {
        //返回用户ID
        $user_id = $this->authorization();

        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $drp_config = $this->drpService->drpConfig();

        $result = [];
        if (!empty($drp_config)) {
            $invite_welfare_content = isset($drp_config['invite_welfare_content']['value']) && !empty($drp_config['invite_welfare_content']['value']) ? html_out($drp_config['invite_welfare_content']['value']) : '';
            $result['invite_welfare_content'] = nl2br($invite_welfare_content);

            $invite_term_content = isset($drp_config['invite_term_content']['value']) && !empty($drp_config['invite_term_content']['value']) ? html_out($drp_config['invite_term_content']['value']) : '';
            $result['invite_term_content'] = nl2br($invite_term_content);

            // 是否有选择购买商品
            $is_buy_goods = $drp_config['buy_goods']['value'] ?? '';
            if (!empty($is_buy_goods)) {
                // 显示购买商品条件
                $goods_list = app(DistributeService::class)->selectGoodsList($user_id, $is_buy_goods);
                $result['goods_list'] = $goods_list;
            }
        }

        $drp_today_count = app(DistributeService::class)->drp_shop_count('D'); // 今日新增分销商人数
        $drp_month_count = app(DistributeService::class)->drp_shop_count('M'); // 本月新增分销商人数
        $drp_total_count = app(DistributeService::class)->drp_shop_count(); // 总分销商人数

        $result['drp_today_count'] = $drp_today_count;
        $result['drp_month_count'] = $drp_month_count;
        $result['drp_total_count'] = $drp_total_count;

        return $this->succeed($result);
    }

    /**
     * 下级分销商分成佣金列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function drp_invite_list(Request $request)
    {
        /**
         * 下级购买指定商品成为分销商 获取的佣金
         */

        $page = $request->input('page', 1);
        $size = $request->input('size', 10);
        $child_user_id = $request->input('child_user_id', 0);

        // 返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $user_id = $child_user_id > 0 ? $child_user_id : $user_id;

        $offset = [
            'start' => ($page - 1) * $size,
            'limit' => $size,
        ];
        $result = app(DistributeService::class)->drp_invite_list($user_id, $offset);

        return $this->succeed($result);
    }

    /**
     * 佣金转出页面
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function trans(Request $request)
    {
        $this->validate($request, [
        ]);

        // 返回用户ID
        $user_id = $this->authorization();

        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }
        $result = ['error' => 0];

        $money = $this->drpService->drpConfig('draw_money');
        $result['min_money'] = $money['value'];
        //店铺信息
        $shop_info = $this->drpService->shopInfo($user_id, 0);
        $result['max_money'] = $shop_info['shop_money'];

        // 填写银行卡信息
        $result['bank_info'] = app(DistributeService::class)->bank_info($user_id);
        // 微信企业付款所需 银行卡名称编号
        $result['bank_list'] = app(DistributeService::class)->bank_list();
        // 是否有关注公众号 来判断可否显示微信企业付款到零钱
        $result['openid'] = app(DistributeService::class)->get_openid($user_id);

        return $this->succeed($result);
    }

    /**
     * 分销商提现申请
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deposit_apply(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'amount' => 'required',
            'deposit_type' => 'required'
        ]);

        $amount = $request->input('amount', 0);
        $deposit_type = $request->input('deposit_type', 2); // 0 线下付款, 1 微信企业付款至零钱, 2 微信企业付款至银行卡

        if ($deposit_type == 2) {
            //数据验证
            $this->validate($request, [
                'enc_bank_no' => 'required|string',
                'enc_true_name' => 'required|string',
                'bank_code' => 'required|string'
            ]);
        }

        // 返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        // 最小提现金额
        $money = $this->drpService->drpConfig('draw_money');
        if (isset($money['value']) && $amount < $money['value']) {
            $result = [
                'error' => 1,
                'msg' => sprintf(lang('drp.ferred_money_no_less'), $money['value'])
            ];
            return $this->succeed($result);
        }

        // 判断分销商是否有足够的佣金
        $shop_info = $this->drpService->shopInfo($user_id, 0);
        if (empty($shop_info)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }
        if (!empty($shop_info['shop_money'])) {
            if ($amount > $shop_info['shop_money']) {
                $res['error'] = 1;
                $res['msg'] = L('shop_money_error');
                return $this->succeed($res);
            }
        }

        // 申请数据
        $data = [
            'user_id' => $user_id,
            'money' => $amount,
            'deposit_type' => $deposit_type,
            'trade_no' => get_trade_no(),
        ];

        if ($deposit_type == 2) {
            $enc_bank_no = $request->input('enc_bank_no', ''); // 银行卡号
            $enc_true_name = $request->input('enc_true_name', ''); // 银行卡开户名
            $bank_code = $request->input('bank_code', ''); // 开户行编号
            $bank_info = [
                'enc_bank_no' => $enc_bank_no,
                'enc_true_name' => $enc_true_name,
                'bank_code' => $bank_code
            ];
            $data['bank_info'] = \GuzzleHttp\json_encode($bank_info);
        }

        $result = app(DistributeService::class)->depositApply($user_id, $data);

        return $this->succeed($result);
    }

    /**
     * 分销商的提现申请记录
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deposit_apply_list(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer',
        ]);

        $deposit_status = $request->input('deposit_status', -1); // 默认 -1 全部, 0 未提现, 1 已提现

        $page = $request->input('page', 1);
        $size = $request->input('size', 10);

        // 返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $list = app(DistributeService::class)->depositApplyList($user_id, $deposit_status, $page, $size);

        return $this->succeed($list);
    }


}
