<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use Closure;

class SellerAdminPriv
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string $priv_str 权限名称code
     * @return mixed
     */
    public function handle($request, Closure $next, $priv_str)
    {
        $seller_id = $request->session()->get('seller_id', 0);

        $action_list = AdminUser::where('user_id', $seller_id)->value('action_list');

        if ($action_list == 'all') {
            return $next($request);
        }

        if (strpos(',' . $action_list . ',', ',' . $priv_str . ',') === false) {
            return redirect()->route('seller/base/message')->with('msg', lang('admin/common.priv_error'))->with('type', 2);
        }

        return $next($request);
    }
}
