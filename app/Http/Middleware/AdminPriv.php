<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use Closure;

class AdminPriv
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
        $admin_id = $request->session()->get('admin_id', 0);

        $action_list = AdminUser::where('user_id', $admin_id)->value('action_list');

        if ($action_list == 'all') {
            return $next($request);
        }

        if (strpos(',' . $action_list . ',', ',' . $priv_str . ',') === false) {
            return redirect()->route('admin/base/message')->with('msg', lang('admin/common.priv_error'))->with('type', 2);
        }

        return $next($request);
    }
}
