<?php

namespace Modules\Device\Http\Controllers;

use App\Services\CAWebServices;
use GreenCape\Xml\Converter;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Str;
class DeviceController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        try {
            $service = new CAWebServices(env('CA_WSDL_URL'));
            $user = session()->get(env('AUTH_SESSION_KEY'));
            $manufactures = $service->getManufactures($user['ssid'])->unique('zsym');
            $years = $service->getYearOfManufactures($user['ssid'])->toArray();
            usort($years, function($a, $b) {
                return $b['zsym'] <=> $a['zsym'];
            });
            $cities = Cache::remember('all_cities', 7200, function () {
                return getDataFromService('doSelect', 'zCustomer', ['id', 'zsym'], '', 150);
            });
            $dvqls = Cache::remember('all_dvql', 7200, function () {
                return getDataFromService('doSelect', 'zDVQL', ['id', 'zsym'], '', 150);
            });
            $tds = Cache::remember('all_td', 7200, function () {
                return getDataFromService('doSelect', 'zTD', ['id', 'zsym'], '', 300);
            });
            $nls = Cache::remember('all_nl', 7200, function () {
                return getDataFromService('doSelect', 'zNL', ['id', 'zsym'], '', 300);
            });

            $whereClause = "class.zetc_type != 1 AND delete_flag = 0 AND name NOT LIKE '%biên bản%' AND name NOT LIKE '%bbtn%'";
            $nl = $request->get('nl');
            $td = $request->get('td');
            $dvql = $request->get('dvql');
            $city = $request->get('city');
            if ($nl) {
                $whereClause .= ' AND zrefnr_nl=' . $nl;
            }
            if ($td) {
                $whereClause .= ' AND zrefnr_td=' . $td;
            }
            if ($dvql) {
                $whereClause .= ' AND zrefnr_dvql=' . $dvql;
            }
            if ($city) {
                $whereClause .= ' AND zArea =' . $city;
            }
            if ($request->get('nl_form')) {
                $whereClause .= ' AND zrefnr_nl=' . $request->get('nl_form');
            }
            if ($request->get('td_form')) {
                $whereClause .= ' AND zrefnr_td=' . $request->get('td_form');
            }
            if ($request->get('dvql_form')) {
                $whereClause .= ' AND zrefnr_dvql=' . $request->get('dvql_form');
            }
            if ($request->has('name') && $request->get('name') != '') {
                $whereClause .= " AND name LIKE '%" . addslashes($request->get('name')) . "%'";
            }
            if ($request->has('series') && $request->get('series') != '') {
                $whereClause .= " AND serial_number LIKE '%" . addslashes($request->get('serial_number')) . "%'";
            }
            if ($request->has('manufacture') && $request->get('manufacture') != '') {
                $whereClause .= " AND zManufacturer.zsym = '".$request->get('manufacture')."'";
            }
            if ($request->has('year') && $request->get('year') != '') {
                $whereClause .= " AND zYear_of_Manafacture = " . (int) $request->get('year');
            }
            $listHandle = Cache::remember('list_device_handle__' . md5($whereClause), 7200, function () use ($service, $user, $whereClause) {
                return $service->getListHandle($user['ssid'], 'nr', $whereClause);
            });
            $items = [];
            $perPage = 10;
            $currentPage = $request->get('page') ?? 1;
            if ($listHandle && @$listHandle->listLength > 0) {
                $currentPage = $currentPage = LengthAwarePaginator::resolveCurrentPage();
                $startIndex = ($currentPage - 1) * $perPage;
                $endIndex = ($startIndex + $perPage) - 1;
                if ($endIndex > $listHandle->listLength) {
                    $endIndex = (int)$listHandle->listLength - 1;
                }
                $items = Cache::remember(sprintf('device_items_list_%s_start_%s_end_%s', $listHandle->listHandle, $startIndex, $endIndex), 3600, function () use ($service, $user, $listHandle, $startIndex, $endIndex) {
                    $payload = [
                        'sid' => $user['ssid'],
                        'listHandle' => $listHandle->listHandle,
                        'startIndex' => $startIndex,
                        'endIndex' => $endIndex,
                        'attributeNames' => [
                            'name',
                            'class.type',
                            'serial_number',
                            'zCountry.name',
                            'zManufacturer.zsym',
                            'location',
                            'zCI_Device.manufacturer',
                            'family.sym',
                            'zrefnr_dvql.zsym',
                            'zrefnr_td.zsym',
                            'zrefnr_nl.zsym',
                            'zCI_Device_Type.zsym',
                            'zYear_of_Manafacture.zsym',
                            'zCI_Device_Kind.zsym',
                        ]
                    ];
                    $resp = $service->callFunction('getListValues', $payload);
                    $parser = new Converter($resp->getListValuesReturn);
                    $items = [];
                    if (!isset($parser->data['UDSObjectList'][0])) {
                        $parser->data['UDSObjectList'] = [$parser->data['UDSObjectList']];
                    }
                    foreach ($parser->data['UDSObjectList'] as $obj) {
                        if (!$obj) continue;
                        $item['handle_id'] = $obj['UDSObject'][0]['Handle'];
                        foreach ($obj['UDSObject'][1]['Attributes'] as $attr) {
                            if ($attr['Attribute'][1]['AttrValue']) {
                                $item[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                            }
                        }
                        array_push($items, $item);
                    }
                    return $items;
                });

            }
            $items = collect($items)
            ->sortBy(function($item){
                return @$item['class.type'];
            })
            ->sortBy(function($item1){
                return @$item1['zrefnr_dvql.zsym'];
            })
            ->sortBy(function($item2){
                return @$item2['zrefnr_td.zsym'];
            })
            ->sortBy(function($item3){
                return @$item3['zrefnr_nl.zsym'];
            })
            ->toArray();
            $data = new LengthAwarePaginator($items, $listHandle->listLength, $perPage, $currentPage, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);
            return view('device::index', compact('data', 'manufactures', 'years', 'request', 'cities', 'dvqls', 'tds', 'nls'));
        } catch (\Exception $exception) {
            exceptionHandle($exception);
        }
    }

    public function deviceGetAll(Request $request)
    {
        return 1;
    }

    public function detail($handleId)
    {
        try {
            $user = session()->get(env('AUTH_SESSION_KEY'));
            $service = new CAWebServices(env('CA_WSDL_URL'));
            $resp = $service->callFunction('getObjectValues', [
                'sid' => $user['ssid'],
                'objectHandle' => $handleId,
                'attributes' => [
                    'id',
                    'name',
                    'class.type',
                    'serial_number',
                    'zCountry.name',
                    'location',
                    'zCI_Device.manufacturer',
                    'zCI_Device.zrefnr_dvql',
                    'family.sym',
                    'zrefnr_dvql.zsym',
                    'zrefnr_td.zsym',
                    'zrefnr_nl.zsym',
                    'zYear_of_Manafacture.zsym',
                    'zManufacturer.id',
                    'zManufacturer.zsym',
                    'zCI_Device_Type.zsym',
                    'zCustomer.zsym',
                    'zCI_Device_Kind.zsym',
                    'zCI_Device_Type',
                    'class',
                    'zStage.zsym',
                    'zArea.zsym',
                    'zphieuchinhdinh'
                ]
            ]);
            $parser = new Converter($resp->getObjectValuesReturn);
            foreach ($parser->data['UDSObject'][1]['Attributes'] as $attr) {
                if ($attr['Attribute'][1]['AttrValue']) {
                    $device[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                }
            }
            $payload = [
                'sid' => $user['ssid'],
                'objectType' => 'nr',
                'whereClause' => "class.zetc_type = 1 AND zCI_Device = U'" . $device['id'] . "'",
                'maxRows' => 100,
                'attributes' => [
                    'id',
                    'name',
                    'audit_userid',
                    'creation_date',
                    'zresultEnd',
                    'ztestType.zsym',
                    'zExperimenter.first_name',
                    'zExperimenter.middle_name',
                    'zExperimenter.last_name',
                    'zdirectorId.first_name',
                    'zdirectorId.middle_name',
                    'zdirectorId.last_name',
                    'zExperimental_Place.name',
                    'zHead_of_Department.last_name',
                    'class.type'
                ]
            ];
            $resp = $service->callFunction('doSelect', $payload);
            $parser = new Converter($resp->doSelectReturn);
            $experiments = [];
            if (!isset($parser->data['UDSObjectList'][0])) {
                $parser->data['UDSObjectList'] = [$parser->data['UDSObjectList']];
            }
            foreach ($parser->data['UDSObjectList'] as $obj) {
                if (!$obj) continue;
                $item = [];
                $item['handle_id'] = $obj['UDSObject'][0]['Handle'];
                foreach ($obj['UDSObject'][1]['Attributes'] as $attr) {
                    if ($attr['Attribute'][1]['AttrValue']) {
                        $item[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                    }
                }
                array_push($experiments, $item);
            }
            usort($experiments, function ($item1, $item2) {
                return $item2['creation_date'] <=> $item1['creation_date'];
            });

            // get assoc report
            $reportInfo = config('device.device_attributes.'.$device['class'].'.info_report') ?? [];
            $assocReport =  $labelReport = $attributesReport = [];
            if( !empty($reportInfo) && !empty($experiments) ){
                $reportLast = $experiments[0];
                $object = $this->getObjectType($reportLast['class.type']);
                $attributesReport = config('device.device_attributes.'.$device['class'].'.info_report.'.$object.'.attributes') ?? [];
                $labelReport = config('device.device_attributes.'.$device['class'].'.info_report.'.$object.'.label') ?? [];
                $whereClause = " id = U'".$reportLast['id']."' ";
                $assocReport = getDataFromService('doSelect', $object, $attributesReport, $whereClause);
            }
            // get assoc device
            $whereClause = " id = U'".$device['id']."' ";
            $attributes = config('device.device_attributes.'.$device['class'].'.attributes') ?? [];
            $label = config('device.device_attributes.'.$device['class'].'.label') ?? [];
            $lableDevice = config('device.device_attributes.'.$device['class'].'.info_device.label') ?? [];
            $attrsDevice = config('device.device_attributes.'.$device['class'].'.info_device.attributes') ?? [];
            $infoSingle = getDataFromService('doSelect', 'zETC_Device', $attributes, $whereClause);

            return view('device::detail', [
                'item' => $device,
                'experiments' => $experiments,
                'attrs' => $attributes,
                'label' => $label,
                'infoSingle' => $infoSingle,
                'lableDevice' => $lableDevice,
                'attrsDevice' => $attrsDevice,
                'assocReport' => $assocReport,
                'labelReport' => $labelReport,
                'attributesReport' => $attributesReport
            ]);
        } catch (\Exception $e) {
            Log::error('[Device] Detail device: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($handleId));
            return redirect()->back()->withErrors([$e->getMessage()]);
        }
    }

    private function getObjectType($classType)
    {
        $objectType = '';
        switch ($classType) {
            case '13.8. BBTN Máy biến áp phân phối 1 pha':
                $objectType = 'zCA13_8';
                break;
            case '13.9. BBTN Máy biến áp phân phối 3 pha':
                $objectType = 'zCA13_9';
                break;
            default:
                $objectType = 'zCA7';
                break;
        }
        return $objectType;
    }


    public function masterTree()
    {
        $data = file_get_contents(storage_path('jsons/parents.json'));
        return Response::json(json_decode($data));
    }

    public function getStations(Request $request)
    {
        $allStations = collect(json_decode(file_get_contents(storage_path('jsons/cities.json'))));
        $filtered = $allStations->filter(function ($item) use ($request) {
            return $item->cityId == $request->get('city');
        });
        return Response::json($filtered);
    }

    public function getBlocks(Request $request)
    {
        $items = collect(json_decode(file_get_contents(storage_path('jsons/blocks.json'))));
        $filtered = $items->filter(function ($item) use ($request) {
            return $item->stationId == $request->get('station');
        });
        return Response::json($filtered);
    }

    public function getDeviceByBlock(Request $request)
    {
        $items = collect(json_decode(file_get_contents(storage_path('jsons/devices.json'))));
        $filtered = $items->filter(function ($item) use ($request) {
            return $item->blockingRoadId == $request->get('block');
        });
        return Response::json($filtered);
    }

    public function getAllDevices()
    {
        $items = json_decode(file_get_contents(storage_path('jsons/devices.json')), true);
        return Response::json(array_splice($items, 0, 10));
    }
    // filter device by request
    public function ajaxFilterDevice(Request $request)
    {
        try {
            $data = $this->filterDevice($request);
            $html = view('device::data')->with('data', $data)->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Exception $exception) {
            Log::error('[Device] Ajax filter device: Fail | Reason: ' . $exception->getMessage() . '. ' . $exception->getFile() . ':' . $exception->getLine() . '| Params: ' . json_encode($request->all()));
            return response()->json([
                'error' => [ $exception->getMessage() ]
            ]);
        }
    }
    // handle query device by query
    private function filterDevice(Request $request)
    {
        $service = new CAWebServices(env('CA_WSDL_URL'));
        $user = session()->get(env('AUTH_SESSION_KEY'));

        $whereClause = "class.zetc_type != 1 AND delete_flag = 0";
        $nl = $request->get('nl');
        $td = $request->get('td');
        $dvql = $request->get('dvql');
        $city = $request->get('city');
        if ($nl) {
            $whereClause .= ' AND zrefnr_nl=' . $nl;
        }
        if ($td) {
            $whereClause .= ' AND zrefnr_td=' . $td;
        }
        if ($dvql) {
            $whereClause .= ' AND zrefnr_dvql=' . $dvql;
        }
        if ($city) {
            $whereClause .= ' AND zArea =' . $city;
        }
        if ($request->get('nl_form')) {
            $whereClause .= ' AND zrefnr_nl=' . $request->get('nl_form');
        }
        if ($request->get('td_form')) {
            $whereClause .= ' AND zrefnr_td=' . $request->get('td_form');
        }
        if ($request->get('dvql_form')) {
            $whereClause .= ' AND zrefnr_dvql=' . $request->get('dvql_form');
        }
        if ($request->has('name') && $request->get('name') != '') {
            $whereClause .= " AND name LIKE '%" . addslashes($request->get('name')) . "%'";
        }
        if ($request->has('series') && $request->get('series') != '') {
            $whereClause .= " AND serial_number LIKE '%" . addslashes($request->get('series')) . "%'";
        }
        if ($request->has('manufacture') && $request->get('manufacture') != '') {
            $whereClause .= " AND zManufacturer.zsym = '".$request->get('manufacture')."'";
        }
        if ($request->has('year') && $request->get('year') != '') {
            $whereClause .= " AND zYear_of_Manafacture = " . (int) $request->get('year');
        }
        if( env('FLAG_CACHE') ){
            $listHandle = Cache::remember('list_device_handle__' . md5($whereClause), 7200, function () use ($service, $user, $whereClause) {
                return $service->getListHandle($user['ssid'], 'nr', $whereClause);
            });
        }else{
            $listHandle = $service->getListHandle($user['ssid'], 'nr', $whereClause);
        }
        $items = [];
        $perPage = 15;
        $currentPage = $request->get('page') ?? 1;
        if ($listHandle && @$listHandle->listLength > 0) {
            $currentPage = $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $startIndex = ($currentPage - 1) * $perPage;
            $endIndex = ($startIndex + $perPage) - 1;
            if ($endIndex >= $listHandle->listLength) {
                $endIndex = (int)$listHandle->listLength - 1;
            }
            if( env('FLAG_CACHE') ){
                $items = Cache::remember(sprintf('device_items_list_%s_start_%s_end_%s', $listHandle->listHandle, $startIndex, $endIndex), 3600, function () use ($service, $user, $listHandle, $startIndex, $endIndex) {
                    return $this->handleCacheItemDevice($listHandle, $startIndex, $endIndex, $service, $user);
                });
            }else{
                $items = $this->handleCacheItemDevice($listHandle, $startIndex, $endIndex, $service, $user);
            }
        }
        $items = collect($items)
        ->sortBy(function($item){
            return @$item['class.type'];
        })
        ->sortBy(function($item1){
            return @$item1['zrefnr_dvql.zsym'];
        })
        ->sortBy(function($item2){
            return @$item2['zrefnr_td.zsym'];
        })
        ->sortBy(function($item3){
            return @$item3['zrefnr_nl.zsym'];
        })
        ->toArray();
        $data = new LengthAwarePaginator($items, $listHandle->listLength, $perPage, $currentPage, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);
        return $data;
    }

    /**
     * handle logic paginate device
     * @param $listHandle
     * @param $startIndex
     * @param $endIndex
     * @param $service
     * @param $user
     * @return array
     * @throws \ErrorException
     */
    private function handleCacheItemDevice($listHandle, $startIndex, $endIndex, $service, $user)
    {
        $items = [];
        $payload = [
            'sid' => $user['ssid'],
            'listHandle' => $listHandle->listHandle,
            'startIndex' => $startIndex,
            'endIndex' => $endIndex,
            'attributeNames' => [
                'name',
                'class.type',
                'serial_number',
                'zCountry.name',
                'zManufacturer.zsym',
                'location',
                'zCI_Device.manufacturer',
                'family.sym',
                'zrefnr_dvql.zsym',
                'zrefnr_td.zsym',
                'zrefnr_nl.zsym',
                'zCI_Device_Type.zsym',
                'zYear_of_Manafacture.zsym',
                'zrefnr_dvql.zdisplay.zsym',
                'zArea.zsym',
                'zCI_Device_Kind.zsym'
            ]
        ];
        $resp = $service->callFunction('getListValues', $payload);
        $parser = new Converter($resp->getListValuesReturn);
        if (!isset($parser->data['UDSObjectList'][0])) {
            $parser->data['UDSObjectList'] = [$parser->data['UDSObjectList']];
        }
        foreach ($parser->data['UDSObjectList'] as $obj) {
            if (!$obj) continue;
            $item = [];
            $item['handle_id'] = $obj['UDSObject'][0]['Handle'];
            foreach ($obj['UDSObject'][1]['Attributes'] as $attr) {
                if ($attr['Attribute'][1]['AttrValue']) {
                    $item[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                }
            }
            array_push($items, $item);
        }
        return $items;
    }
    // ajax get view dashboard device
    public function getViewDevice(Request $request)
    {
        try {
            $service = new CAWebServices(env('CA_WSDL_URL'));
            $user = session()->get(env('AUTH_SESSION_KEY'));
            $manufactures = $service->getManufactures($user['ssid'])->unique('zsym')->toArray();
            sortData($manufactures);
            $years = $service->getYearOfManufactures($user['ssid'])->toArray();
            usort($years, function($a, $b) {
                return $b['zsym'] <=> $a['zsym'];
            });
            $cities = Cache::remember('list_areas', 7200, function () {
                return getDataFromService('doSelect', 'zArea', ['id', 'zsym'], 'zsym IS NOT NULL AND active_flag = 1', 300);
            });
            sortData($cities);
            $dvqls = Cache::remember('all_dvql', 7200, function () {
                return getDataFromService('doSelect', 'zDVQL', ['id', 'zsym'], 'active_flag = 1', 300);
            });
            sortData($dvqls);
            $result = $this->filterDevice($request);
            $data = [
                'data' => $result,
                'manufactures' => $manufactures,
                'years' => $years,
                'cities' => $cities,
                'dvqls' => $dvqls,
            ];
            $html = view('device::view')->with($data)->render();
            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Exception $e) {
            Log::error('[Device] Get device view: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return response()->json([
                'error' => [ $e->getMessage() ]
            ]);
        }
    }
    // get list td by dvql id
    public function getTD(Request $request)
    {
        try {
            $data = [];
            if( $request->id ){
                $whereClause = " zref_dvql = $request->id ";
                $data = getDataFromService('doSelect', 'zTD', ['id', 'zsym'], $whereClause, 300);
                usort($data, function($a, $b){
                    return strcmp(strtolower($a['zsym']), strtolower($b['zsym']));
                });
            }
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('[Device] Ajax get TD by dvql id: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return response()->json([
                'error' => [ $e->getMessage() ]
            ]);
        }
    }
    // get list nl by td id
    public function getNL(Request $request)
    {
        try {
            $data = [];
            if( $request->id ){
                $whereClause = " zref_td = $request->id ";
                $data = getDataFromService('doSelect', 'zNL', ['id', 'zsym'], $whereClause, 300);
                usort($data, function($a, $b){
                    return strcmp(strtolower($a['zsym']), strtolower($b['zsym']));
                });
            }
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('[Device] Ajax get NL by td id: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return response()->json([
                'error' => [ $e->getMessage() ]
            ]);
        }
    }
}
