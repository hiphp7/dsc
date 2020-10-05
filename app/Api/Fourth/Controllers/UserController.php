<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Api\Fourth\Transformers\UserTransformer;
use App\Custom\Distribute\Services\DistributeService;
use App\Models\UserOrderNum;
use App\Models\Users;
use App\Models\UsersReal;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Article\ArticleCommonService;
use App\Services\Order\OrderService;
use App\Services\User\UserCommonService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class UserController
 * @package App\Api\Fourth\Controllers
 */
class UserController extends Controller
{
    protected $userCommonService;
    protected $userTransformer;
    protected $timeRepository;
    protected $baseRepository;
    protected $config;
    protected $orderService;
    protected $dscRepository;
    protected $articleCommonService;

    public function __construct(
        UserCommonService $userCommonService,
        UserTransformer $userTransformer,
        TimeRepository $timeRepository,
        BaseRepository $baseRepository,
        OrderService $orderService,
        DscRepository $dscRepository,
        ArticleCommonService $articleCommonService
    )
    {
        $this->userCommonService = $userCommonService;
        $this->userTransformer = $userTransformer;
        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;
        $this->orderService = $orderService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->articleCommonService = $articleCommonService;
    }

    /**
     * 返回用户资料
     * @return JsonResponse
     * @throws Exception
     */
    public function profile()
    {
        $time = $this->timeRepository->getGmTime();

        $user = Users::where('user_id', $this->uid);

        // 用户不存在返回
        if (!$user->first()) {
            return $this->setStatusCode(102)->failed('User not found');
        }

        $user = $user->with([
            'getUserBonusList' => function ($query) use ($time) {
                $query->select('user_id', 'bonus_id')
                    ->where('bonus_type_id', '>', 0)
                    ->where('used_time', '=', 0)
                    ->whereHas('getBonusType', function ($query) use ($time) {
                        $query->where('use_start_date', '<', $time)->where('use_end_date', '>', $time);
                    });
            },
            'getCouponsUserList' => function ($query) use ($time) {
                $query->select('user_id', 'uc_id')
                    ->where('cou_id', '>', 0)
                    ->where('is_use', '=', 0)
                    ->where('is_use_time', '=', 0)
                    ->whereHas('getCoupons', function ($query) use ($time) {
                        $query->where('cou_start_time', '<', $time)->where('cou_end_time', '>', $time);
                    });
            },
            'getValueCard' => function ($query) use ($time) {
                $query->select('user_id', 'vid')
                    ->where('bind_time', '>', 0)
                    ->where('end_time', '>', $time);
            }
        ]);

        $user = $this->baseRepository->getToArrayFirst($user);

        // 统计订单数量
        $orderNum = UserOrderNum::where('user_id', $this->uid)->first();
        $orderNum = $orderNum ? $orderNum->toArray() : [];

        $orderCount = [
            'all' => $orderNum['order_all_num'] ?? 0, //订单数量
            'nopay' => $orderNum['order_nopay'] ?? 0, //待付款订单数量
            'nogoods' => $orderNum['order_nogoods'] ?? 0, //待收货订单数量
            'isfinished' => $orderNum['order_isfinished'] ?? 0, //已完成订单数量
            'isdelete' => $orderNum['order_isdelete'] ?? 0, //回收站订单数量
            'team_num' => $orderNum['order_team_num'] ?? 0, //拼团订单数量
            'not_comment' => $orderNum['order_not_comment'] ?? 0,  //待评价订单数量
            'return_count' => $orderNum['order_return_count'] ?? 0 //待同意状态退换货申请数量
        ];

        // 会员等级
        $user_rank = $this->userCommonService->getUserRankByUid($this->uid);
        $user['user_rank'] = $user_rank['rank_name'];

        $res = $this->userTransformer->transform(array_merge($user, $orderCount));
        $res['avatar'] = $this->dscRepository->getImagePath($res['avatar']);
        $res['use_value_card'] = $this->config['use_value_card'] ?? '';

        // 返回是否实名认证
        $res['user_real'] = UsersReal::where('user_id', $this->uid)
            ->where('user_type', 0)
            ->count();

        //微信浏览器判断
        $res['is_wechat_browser'] = is_wechat_browser() ? 1 : 0;

        //是否显示我的微店
        $res['is_drp'] = file_exists(MOBILE_DRP) ? 1 : 0;
        if ($res['is_drp'] == 1) {
            // 是否已申请分销商
            $drp_shop = app(DistributeService::class)->drp_shop_info($this->uid, ['audit', 'status', 'apply_channel', 'membership_card_id', 'membership_status']);
            $res['drp_shop'] = empty($drp_shop) ? 0 : $drp_shop;
        }

        //是否显示待拼团
        $res['is_team'] = file_exists(MOBILE_TEAM) ? 1 : 0;

        //是否显示我的砍价
        $res['is_bargain'] = file_exists(MOBILE_BARGAIN) ? 1 : 0;

        // 是否显示供应链
        if (file_exists(SUPPLIERS) && isset($this->config['wholesale_user_rank']) && $this->config['wholesale_user_rank'] != 0) {
            $res['is_suppliers'] = 1;
        } else {
            $res['is_suppliers'] = 0;
        }

        //是否显示推荐分成
        $affiliate = $this->config['affiliate'] ?? '';
        $share = empty($affiliate) ? '' : unserialize($affiliate);
        $res['is_share'] = ($share && $share['on'] == 1) ? 1 : 0;

        return $this->succeed($res);
    }

    /**
     * 返回用户脱敏数据
     * @param Request $request
     * @return JsonResponse
     */
    public function basicProfileByMobile(Request $request)
    {
        $name = $request->get('name');

        $user = $this->userCommonService->getUserByName($name);

        if (is_null($user)) {
            return $this->failed('User not found');
        }

        $user = $this->userTransformer->transform($user);

        $user['avatar'] = $user['avatar'] ? $user['avatar'] : asset('img/user_default.png');

        $res = [
            'username' => $user['username'],
            'avatar' => $this->dscRepository->getImagePath($user['avatar'])
        ];

        return $this->succeed($res);
    }

    /**
     * 保存用户资料
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            //'name' => 'required|max:16',
            'sex' => 'required',
            'birthday' => 'required',
        ]);

        $id = $this->authorization();

        if (empty($id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $user = Users::find($id);

        $user->nick_name = $request->get('name');
        $user->sex = $request->get('sex');
        $user->birthday = $request->get('birthday');
        $user->save();

        $res = $this->userTransformer->transform($user);

        return $this->succeed($res);
    }

    /**
     * 设置头像
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function avatar(Request $request)
    {
        $this->validate($request, []);

        $id = $this->authorization();

        if (empty($id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }
        $user_picture = $request->get('pic', '');

        $user = Users::find($id);
        $user->user_picture = $user_picture;
        $user->save();

        $res = $this->userTransformer->transform($user);
        $res['avatar'] = asset($res['avatar']);

        return $this->succeed($res);
    }

    /**
     * 返回ECJia Hash
     * @return JsonResponse
     * @throws Exception
     */
    public function ecjiaHash()
    {
        $user_id = $this->authorization();

        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $user = Users::where('user_id', $user_id);
        $user = $this->baseRepository->getToArrayFirst($user);

        if (empty($user)) {
            return $this->failed('User not found');
        }

        $res = $this->userCommonService->ecjiaHash($user);

        return $this->succeed($res);
    }

    /**
     * 帮助中心
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function help(Request $request)
    {
        $user_id = $this->authorization();

        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $res = $this->articleCommonService->helpinfo();

        return $this->succeed($res);
    }
}
