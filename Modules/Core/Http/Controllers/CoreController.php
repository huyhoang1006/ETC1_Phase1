<?php

namespace Modules\Core\Http\Controllers;

use App\Services\CAWebServices;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Http\Requests\UpdatePermissionRequest;

class CoreController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
//        dd(session()->get(env('AUTH_SESSION_KEY')));
        return view('core::dashboard');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('core::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('core::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('core::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }

    /**
     * index view all user
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function viewPermission()
    {
        // get list user from CA
        $users  = getDataFromService('doSelect', 'cnt', ['access_type', 'id', 'userid', 'domain'], " access_type != 10002 AND userid IS NOT NULL", 250);
        // each user get access name and domain name
        foreach($users as $index => $user){
            if( !empty($user['access_type']) ){
                $users[$index]['access_name'] = @getDataFromService('doSelect', 'acctyp', [], 'id='.$user['access_type'])['sym'];
            }
            if( !empty($user['domain']) ){
                $users[$index]['domain_name'] = @getDataFromService('doSelect', 'dmn', [], 'id='.$user['domain'])['sym'];
            }
        }
        return view('core::permission.index', compact('users'));
    }

    /**
     * view edit permission for userid
     * @param $userid
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function editPermission($userid)
    {
        $accessTypes = getDataFromService('doSelect', 'acctyp', ['id', 'sym'], " sym LIKE '%ETC%' AND sym NOT LIKE '%Admin%'", 100);
        $userInfo  = getDataFromService('doSelect', 'cnt', [], " userid = '".$userid."' ");
        if( !empty($userInfo['access_type']) ){
            $userInfo['accessType'] = getDataFromService('doSelect', 'acctyp', [], 'id='.$userInfo['access_type']);
        }
        return view('core::permission.edit', compact('accessTypes', 'userid', 'userInfo'));
    }

    /**
     * handle update permission for userid and permission choose
     * @param UpdatePermissionRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postEditPermission(UpdatePermissionRequest $request)
    {
        try {
            $service = new CAWebServices(env('CA_WSDL_URL'));
            // get user login session
            $user = session()->get(env('AUTH_SESSION_KEY'));
            // handle get userInfo, access_type and domain
            $userid = $request->get('userid');
            $accessInfo = explode('*', $request->get('access_type'));
            $accessInfoID = $accessInfo[0];
            $accessInfoName = $accessInfo[1];
            $domainId = getDataFromService('doSelect', 'dmn', [], "sym = '".$accessInfoName."'")['id'];
            // update cnt objectHandle
            $userInfo  = getDataFromService('doSelect', 'cnt', [], " userid = '".$userid."' ");
            $payload = [
                'sid' => $user['ssid'],
                'objectHandle' => $userInfo['handle_id'],
                'attributes' => [
                    'persistent_id',
                    'access_type',
                    'domain'
                ],
                'attrVals' => [
                    'access_type',
                    $accessInfoID,
                    'domain',
                    $domainId,
                ]
            ];
            $resp = $service->callFunction('updateObject', $payload);
            if( is_null($resp) || is_null($resp->updateObjectReturn) ){
                return back()->withInput()->withErrors(['Cập nhật quyền thất bại!']);
            }
            return redirect()->route('admin.permission')->with('update_success', 'Cập nhật quyền thành công!');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors([$e->getMessage()]);
        }
    }
}
