<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PermissionCustom
{
    const IS_ADMIN = 'admin';
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $permission)
    {
        $user = session()->get(env('AUTH_SESSION_KEY'));
        $permissions = @$user['permissions'] ?? [];
        $role = @$user['role'];
        if($role == self::IS_ADMIN){
            return $next($request);    
        }
        if( !in_array($permission, $permissions) ){
            return redirect()->route('admin.dashboard.index')->with(['unauthorized' => 'Access is not allowed!']);
        }
        return $next($request);
    }
}
