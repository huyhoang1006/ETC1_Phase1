<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Middleware\PermissionCustom;
use App\Services\CAWebServices;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Http\Requests\LoginRequest;

class AuthController extends Controller
{
    public function showLoginForm() {
        return view('core::login');
    }

    public function processLogin(LoginRequest $request) {
        try {
            $service = new CAWebServices(env('CA_WSDL_URL'));
            $payload = ['username' => $request->get('username'), 'password' => $request->get('password')];
            $resp = $service->callFunction('login', $payload);            
            session([env('AUTH_SESSION_KEY') => [
                    'username' => $request->get('username'),
                    'password' => $request->get('password'),
                    'ssid' => $resp->loginReturn
                ]]
            );
            // get access_type from username
            $userInfo  = getDataFromService('doSelect', 'cnt', ['access_type', 'id', 'userid'], " userid = '". $request->get('username') ."' ");
            // get list access type
            $accessTypes = getDataFromService('doSelect', 'acctyp_role', ['id', 'role_obj', 'access_type'], ' access_type = '.$userInfo['access_type'], 150); 
            if($accessTypes instanceOf \Illuminate\Http\JsonResponse){
                return redirect()->back()->withInput()
                ->with('login_error', 'Thông tin đăng nhập không hợp lệ')
                ->with('ca_code', $accessTypes->getData()->code)
                ->with('ca_message', $accessTypes->getData()->message);
            }
            // get list role by list access type
            $roles = [];
            foreach( $accessTypes as $accessType){
                $role = getDataFromService('doSelect', 'role', ['name', 'id'], ' id = '. $accessType['role_obj']);
                array_push($roles, $role); 
            }
            // check user login is admin
            $isAdmin = collect($roles)->filter(function($role){
                return $role['id'] == '10002'; // 10002 id is admin
            })->count();
            // get permission from list roles
            $permissions = [];
            foreach($roles as $role){
                if(strpos($role['name'], '_')){
                    array_push($permissions, @explode('_', $role['name'])[1]);
                }else{
                    array_push($permissions, $role['name']);
                }
            }
            $permissions = array_filter(array_unique($permissions));
            // put role and permission to session 
            session([env('AUTH_SESSION_KEY') => [
                'username' => $request->get('username'),
                'password' => $request->get('password'),
                'ssid' => $resp->loginReturn,
                'role' => $isAdmin ? PermissionCustom::IS_ADMIN : 'not_admin',
                'permissions' => $permissions
            ]]);
            session(['info_user' => [
                'username' => $request->get('username'),
                'password' => $request->get('password'),
                'remember' => (bool) $request->get('remember'),
            ]]);
            return redirect(route('admin.dashboard.index'));
        } catch (\Exception $exception) {
            exceptionHandle($exception);
            return redirect()->back()->withInput()->with('login_error', 'Thông tin đăng nhập không hợp lệ');
        }
    }

    public function processLogout() {
        try {
            session()->remove(env('AUTH_SESSION_KEY'));
            return redirect(route('admin.dashboard.index'));
        } catch (\Exception $exception) {
            exceptionHandle($exception);
            return redirect()->back()->withInput()->with('logout_error', $exception->getMessage());
        }
    }
}
