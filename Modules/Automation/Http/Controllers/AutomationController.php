<?php

namespace Modules\Automation\Http\Controllers;

use App\Exports\AutomationExport;
use App\Services\CAWebServices;
use GreenCape\Xml\Converter;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class AutomationController extends Controller
{
    /**
     * CA Service
     *
     * @return array
     */
    public function CAService()
    {
        // Get User Login Session
        $user = session()->get(env('AUTH_SESSION_KEY'));

        // Get Url Web Service
        $service = new CAWebServices(env('CA_WSDL_URL'));

        // Query Data Web Service
        $payload = [
            'sid' => $user['ssid'],
            'objectType' => 'nr',
            'whereClause' => "class.type = '10. BBTN Cao Áp - Máy Cắt'",
            'maxRows' => 150,
            'attributes' => [
                'id',
                'name',
                'zReport_Date',
                'zExperimenter.first_name',
                'zExperimenter.middle_name',
                'zExperimenter.last_name',
                'zCustomer',
                'zArea.zsym',
                'zCI_Device.name',
                'zCI_Device.zCI_Device_Type'
            ],
        ];

        // Return Data Web Service
        $resp = $service->callFunction('doSelect', $payload);
        //dd($resp);

        // Convert Data To Object
        $data = new Converter($resp->doSelectReturn);

        $items = [];

        // Writing Data Into Array
        foreach ($data->data['UDSObjectList'] as $obj) {
            $item =[];
            foreach ($obj['UDSObject'][1]['Attributes'] as $attr) {
                if ($attr['Attribute'][1]['AttrValue']) {
                    $item[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                }
            }
            array_push($items, $item);
        }
        return $items;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        $zCountry = Cache::remember('countriesList', 7200, function () {
            return collect(getDataFromService('doSelect', 'country', ['id', 'name'], '', 300))->pluck('name', 'id')->unique()->toArray();
        });
        asort($zCountry);
        $zArea = Cache::remember('areasList', 7200, function () {
            return collect(getDataFromService('doSelect', 'zArea', ['id', 'zsym'], '', 300))->pluck('zsym', 'id')->unique()->toArray();
        });
        asort($zArea);
        $types = collect(getDataFromService('doSelect', 'grc', ['id', 'type'], 'zTDH = 1 AND zetc_type = 0', 300))->pluck('type', 'id')->unique()->toArray();
        $manufactures = !empty($request['devices']) ? collect(getDataFromService('doSelect', 'zManufacturer', ['id', 'zsym'], 'zsym IS NOT NULL AND zclass = ' . $request['devices'], 300))->pluck('zsym', 'id')->toArray() : [];
        asort($manufactures);
        $deviceTypes = !empty($request['devices']) ? collect(getDataFromService('doSelect', 'zCI_Device_Type', ['id', 'zsym'], 'zsym IS NOT NULL AND zclass = ' . $request['devices'], 300))->pluck('zsym', 'id')->toArray() : [];
        asort($deviceTypes);

        $tds = Cache::remember('all_td', 7200, function () {
            return getDataFromService('doSelect', 'zTD', ['id', 'zsym'], '', 300);
        });
        usort($tds, function($a, $b){
            return strcmp(strtolower($a['zsym']), strtolower($b['zsym']));
        });
        $nls = $request->td_names ? $this->getNL($request) : [];
        $devices = Cache::get('automation_index_list');

        $devices = array_values(collect($devices)->sortBy(function($device){
            return @$device['name'];
        })->toArray());

        $deviceArr = [];

        if (!empty($devices)) {
            foreach ($devices as $device) {
                if (!empty($request['area']) && (empty($device['zArea']) || stripos($device['zArea'], $request['area']) === false)) {
                    continue;
                }
                if (!empty($request['td_names']) && (empty($device['zrefnr_td.zsym']) || !in_array($device['zrefnr_td.zsym'], $request['td_names']))) {
                    continue;
                }
                if (!empty($request['nl_names']) && (empty($device['zrefnr_nl.zsym']) || !in_array($device['zrefnr_nl.zsym'], $request['nl_names']))) {
                    continue;
                }
                if (!empty($request['name']) && (empty($device['name']) || stripos($device['name'], $request['name']) === false)) {
                    continue;
                }
                if (!empty($request['devices']) && (empty($device['class']) || stripos($device['class'], $request['devices']) === false)) {
                    continue;
                }
                if (!empty($request['manufacture']) && (empty($device['zManufacturer.zsym']) || stripos($device['zManufacturer.zsym'], $request['manufacture']) === false)) {
                    continue;
                }
                if (!empty($request['type']) && (empty($device['zCI_Device_Kind.zsym']) || stripos($device['zCI_Device_Kind.zsym'], $request['type']) === false)) {
                    continue;
                }
                if (!empty($request['series']) && (empty($device['serial_number']) || stripos($device['serial_number'], $request['series']) === false)) {
                    continue;
                }
                if (!empty($request['year']) && (empty($device['zYear_of_Manafacture.zsym']) || stripos($device['zYear_of_Manafacture.zsym'], $request['year']) === false)) {
                    continue;
                }
                if (!empty($request['country']) && (empty($device['zCountry']) || stripos($device['zCountry'], $request['country']) === false)) {
                    continue;
                }
                if (!empty($request['zCI_Device_Type']) && (empty($device['zCI_Device_Type']) || stripos($device['zCI_Device_Type'], $request['zCI_Device_Type']) === false)) {
                    continue;
                }
                if (!empty($request['from']) || !empty($request['to'])) {
                    if (empty($device['report'])) {
                        continue;
                    }
                    foreach ($device['report'] as $keyReport => $report) {
                        $checkTime = true;
                        if (!empty($request['from']) && empty($request['to'])) {
                            $checkTime = (int)$report['creation_date'] >= strtotime($request['from']);
                        } elseif (empty($request['from']) && !empty($request['to'])) {
                            $checkTime = (int)$report['creation_date'] <= strtotime($request['to']);
                        } elseif (!empty($request['from']) && !empty($request['to'])) {
                            $checkTime = (int)$report['creation_date'] >= strtotime($request['from']) && (int)$report['creation_date'] <= strtotime($request['to']);
                        }
                        if (!$checkTime) {
                            unset($device['report'][$keyReport]);
                        }
                    }
                    if (empty($device['report'])) {
                        continue;
                    }
                }
                if (!empty($request['firmware']) || !empty($request['software']) || !empty($request['version']) || !empty($request['ip_address']) || !empty($request['installation_time'])) {
                    if (empty($device['info'])) {
                        continue;
                    }
                    if (!empty($request['firmware']) && (empty($device['info']['zhedieuhanh.zsym']) || stripos($device['info']['zhedieuhanh.zsym'], $request['firmware']) === false)) {
                        continue;
                    }
                    if (!empty($request['software']) && (empty($device['info']['zSoftware.zsym']) || stripos($device['info']['zSoftware.zsym'], $request['software']) === false)) {
                        continue;
                    }
                    if (!empty($request['version']) && (empty($device['info']['zversion']) || $device['info']['zversion'] != $request['version'])) {
                        continue;
                    }
                    if (!empty($request['ip_address']) && (empty($device['info']['zIP']) || $device['info']['zIP'] != $request['ip_address'])) {
                        continue;
                    }
                    if (!empty($request['installation_time']) && (empty($device['info']['zTGLD']) || date('Y-m-d', $device['info']['zTGLD']) != $request['installation_time'])) {
                        continue;
                    }
                }
                array_push($deviceArr, $device);
            }
        }

        return view('automation::index', [
            'zCountry' => $zCountry,
            'zArea' => $zArea,
            'manufactures' => $manufactures,
            'devices' => $deviceArr,
            'types' => $types,
            'deviceTypes' => $deviceTypes,
            'request' => $request->all(),
            'tds' => $tds,
            'nls' => $nls,
        ]);
    }

    /**
     * get NL when has request list td names
     * @param $request
     * @return array
     */
    private function getNL($request)
    {
        $data = [];
        if($request->td_names){
            foreach ($request->td_names as $key => $val) {
                if ($key == 0) {
                    $whereClause = " zref_td.zsym IN (";
                }
                $whereClause .= "'" . $val . "'";
                if ($key < count($request->td_names) - 1) {
                    $whereClause .= ",";
                } else {
                    $whereClause .= ")";
                }
            }
            $data = collect(getAllDataFromService('zNL', ['id', 'zsym'], $whereClause))->unique('zsym')->toArray();
            usort($data, function($a, $b){
                return strcmp(strtolower($a['zsym']), strtolower($b['zsym']));
            });
        }
        return $data;
    }

    /**
     * ajax get NL by list td names
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxGetNL(Request $request)
    {
        try {
            $data = $this->getNL($request);
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('[Automation] Ajax get nl: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return response()->json([
                'error' => [$e->getMessage()]
            ]);
        }
    }

    /**
     * Export csv
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        return Excel::download(new AutomationExport($request->data), 'Báo cáo thống kê chung công tác thí nghiệm.xlsx');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('automation::create');
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
        return view('automation::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('automation::edit');
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
}
