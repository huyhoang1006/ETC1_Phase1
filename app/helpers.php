<?php

use App\Services\CAWebServices;
use GreenCape\Xml\Converter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\Machines\Http\Controllers\HighPressureController;

if (!function_exists('exceptionHandle')) {
    function exceptionHandle(Exception $exception) {
        Log::error('Exception');
        Log::error('File: '. $exception->getFile());
        Log::error('Line: '. $exception->getLine());
        Log::error('Message: '. $exception->getMessage());
        Log::error('===========================================' . PHP_EOL);
    }
}

if (!function_exists('getDataFromService')) {
    function getDataFromService($functionName, $object, $attrs = [], $whereClause = '', $maxRows = 1)
    {
        // Get User Login Session
        $user = session()->get(env('AUTH_SESSION_KEY'));
        // Get Url Web Service
        $service = new CAWebServices(env('CA_WSDL_URL'));

        // Query Data Service
        $data = [];
        switch ($functionName) {
            case 'doSelect':
                $payload['objectType'] = $object;
                break;
            case 'getObjectValues':
                $payload['objectHandle'] = $object;
                break;
            default:
                return $data;
        }
        $payload['sid'] = $user['ssid'];
        $payload['whereClause'] = $whereClause;
        $payload['maxRows'] = $maxRows;
        $payload['attributes'] = $attrs;
        // Return Data Web Service
        $resp = $service->callFunction($functionName, $payload);

        if( isset($resp->detail->ErrorCode) && $resp->detail->ErrorCode == 1013 ){
            return response()->json([
                'code' => $resp->detail->ErrorCode,
                'message' => $resp->detail->ErrorMessage,
            ]);
        }
        // Convert Data To Object
        $functionReturn = $functionName.'Return';
        $parser = new Converter($resp->$functionReturn);
        // Writing Data Into Array
        if ($functionName == 'doSelect') {
            if (!isset($parser->data['UDSObjectList'][0])) {
                $parser->data['UDSObjectList'] = [$parser->data['UDSObjectList']];
            }
            foreach ($parser->data['UDSObjectList'] as $obj) {
                if (!$obj) continue;
                if ($maxRows > 1) {
                    $item = [];
                    $item['handle_id'] = $obj['UDSObject'][0]['Handle'];
                    foreach ($obj['UDSObject'][1]['Attributes'] as $attr) {
                        if ($attr['Attribute'][1]['AttrValue']) {
                            $item[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                        }
                    }
                    array_push($data, $item);
                } else {
                    $data['handle_id'] = $obj['UDSObject'][0]['Handle'];
                    foreach ($obj['UDSObject'][1]['Attributes'] as $attr) {
                        if ($attr['Attribute'][1]['AttrValue']) {
                            $data[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                        }
                    }
                }
            }
        }
        if ($functionName == 'getObjectValues') {
            foreach ($parser->data['UDSObject'][1]['Attributes'] as $attr) {
                if ($attr['Attribute'][1]['AttrValue']) {
                    $data[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                }
            }
        }
        return $data;
    }
}

if (!function_exists('getDevice')) {
    function getDevice($request, $extendAttrs = [], $data = [])
    {
        if (empty($data)) {
            $data = $request->all();
        }

        $whereClause = "delete_flag = 0 AND class.zetc_type != 1";

        if (!empty($data['type'])) {
            $whereClause .= " AND class.type LIKE '%" . addslashes($data['type']) . "%'";
        }

        if (!empty($data['type_id'])) {
            $whereClause .= " AND class = " . $data['type_id'];
        }

        if (!empty($data['types'])) {
            $string = '';
            foreach ($data['types'] as $key => $val) {
                if ($key == 0) {
                    $string .= '(';
                }

                $string .= "'" . $val . "'";

                if ($key < count($data['types']) - 1) {
                    $string .= ',';
                } else {
                    $string .= ')';
                }
            }
            $whereClause .= " AND class.type IN " . $string;
        }

        if (!empty($data['zrefnr_dvql'])) {
            $whereClause .= " AND zrefnr_dvql.zsym LIKE '%" . addslashes($data['zrefnr_dvql']) . "%'";
        }

        if (!empty($data['zrefnr_dvql_ids']) && !empty($data['zrefnr_dvql_ids'][0])) {
            $subWhereClause = '';
            foreach ($data['zrefnr_dvql_ids'] as $key => $val) {
                if ($key == 0) {
                    $subWhereClause .= "(zrefnr_dvql = " . $val;
                } else {
                    $subWhereClause .= " OR zrefnr_dvql = " . $val;
                }
            }
            $subWhereClause .= ')';
            $whereClause .= ' AND ' . $subWhereClause;
        }

        if (!empty($data['zrefnr_td'])) {
            $whereClause .= " AND zrefnr_td.zsym LIKE '%" . addslashes($data['zrefnr_td']) . "%'";
        }

        if (!empty($data['zCI_Device_Kind'])) {
            $whereClause .= " AND zCI_Device_Kind.zsym LIKE '%" . addslashes($data['zCI_Device_Kind']) . "%'";
        }

        if (!empty($data['zCI_Device_Type'])) {
            $whereClause .= " AND zCI_Device_Type.zsym LIKE '%" . addslashes($data['zCI_Device_Type']) . "%'";
        }

        if (!empty($data['zCI_Device_Type_id'])) {
            $whereClause .= " AND zCI_Device_Type = " . $data['zCI_Device_Type_id'];
        }

        if (!empty($data['zCI_Device_Types'])) {
            $string = '';
            foreach ($data['zCI_Device_Types'] as $key => $val) {
                if ($key == 0) {
                    $string .= '(';
                }

                $string .= "'" . $val . "'";

                if ($key < count($data['zCI_Device_Types']) - 1) {
                    $string .= ',';
                } else {
                    $string .= ')';
                }
            }
            $whereClause .= " AND zCI_Device_Type.zsym IN " . $string;
        }

        if( !empty($data['td_name']) ){
            $whereClause .= " AND zrefnr_td.zsym LIKE '%".$data['td_name']."%'";
        }

        if (!empty($data['zYear_of_Manafacture'])) {
            $whereClause .= " AND zYear_of_Manafacture.zsym LIKE '%" . addslashes($data['zYear_of_Manafacture']) . "%'";
        }
        if(env('FLAG_CACHE')){
            return Cache::remember('list_measurance_device__' . md5($whereClause), 7200, function () use ($whereClause, $request, $extendAttrs) {
                return getDeviceCache($extendAttrs, $whereClause);
            });
        }
        return getDeviceCache($extendAttrs, $whereClause);
    }
}
// handle for function getDevice
if( !function_exists('getDeviceCache') ){
    function getDeviceCache($extendAttrs = [], $whereClause){
        $devices = getAllDataFromService('nr', [
            'id',
            'zrefnr_dvql.zsym',
            'zArea.zsym',
            'zrefnr_td.zsym',
            'zrefnr_nl.zsym',
            'name',
            'zCI_Device_Type.zsym',
            'zManufacturer.zsym',
            'zCI_Device_Kind.zsym',
            'serial_number',
            'zYear_of_Manafacture.zsym',
            'zCountry.name',
            'zphieuchinhdinh'
        ], $whereClause);
        $devices = array_values(collect($devices)->sortBy(function($device){
            return @$device['name'];
        })->toArray());

        $deviceArr = [];
        foreach ($devices as $key => $device) {
            $deviceInfo = getDataFromService('doSelect', 'zETC_Device', $extendAttrs, "id = U'" . $device['id'] . "'");
            $device['info'] = $deviceInfo;
            $deviceArr[$key] = $device;
        }
        return $deviceArr;
    }
}

if (!function_exists('setChartData')) {
    function setChartData($spreadsheet, $chartData, $dataSheetName)
    {
        if (!empty($chartData)) {
            $r = 9;
            foreach ($chartData as $val) {
                $count = 0;
                for ($c = 'C'; $c != 'G'; ++$c) {
                    $spreadsheet->setActiveSheetIndexByName($dataSheetName)->getCell($c . $r)->setValue($val[$count]);
                    $count++;
                }
                $r++;
            }
            for ($i = $r; $i <= 25; $i++) {
                for ($c = 'A'; $c != 'G'; ++$c) {
                    $spreadsheet->setActiveSheetIndexByName($dataSheetName)->getCell($c . $i)->setValue('');
                }
            }
        }
    }
}

if (!function_exists('getReportFromDevice')) {
    function getReportFromDevice($deviceItem, $from = null, $to = null)
    {
        try {
            $service = new CAWebServices(env('CA_WSDL_URL'));
            $sessionId = $service->getSessionID(env('CA_USERNAME'), env('CA_PASSWORD'));
            $experiments = [];
            $checkTime = false;
            if (!empty($deviceItem['id'])) {
                $payload = [
                    'sid' => $sessionId,
                    'objectType' => 'nr',
                    'whereClause' => "class.zetc_type = 1 AND family.zETC_dept = 400001 AND (class.type = 'Biên Bản Thí Nghiệm Tín Hiệu Giữa Thiết Bị Và Gateway/RTU' OR class.type = 'Biên Bản Thí Nghiệm Tín Hiệu Giữa Trạm Và Trung Tâm' OR class.type = 'Biên Bản Bảo Trì Máy Tính Điều Khiển Giám Sát' OR class.type = 'Biên Bản Thử Nghiệm Chức Năng Truyền Thông Phương Tiện Đo' OR class.type = 'Biên Bản Khai Báo Cấu Hình Và Kiểm Tra Dữ Liệu Tại Trạm Và Trung Tâm Điều Khiển' OR class.type = 'Biên Bản Thử Nghiệm Chức Năng Truyền Thông Transducers') AND zCI_Device = U'" . $deviceItem['id'] . "'",
                    'maxRows' => 50,
                    'attributes' => [
                        'id',
                        'zlaboratoryDate',
                        'zExperimenter.first_name',
                        'zExperimenter.middle_name',
                        'zExperimenter.last_name',
                        'zNotes',
                        'zManufacturer.zsym',
                        'zArea.zsym',
                        'zrefnr_td.zsym',
                        'zrefnr_nl.zsym',
                        'zCI_Device.name',
                        'serial_number',
                    ]
                ];
                $resp = $service->callFunction('doSelect', $payload);
                if (empty($resp)) {
                    $deviceItem['report'] = [];
                    return $deviceItem;
                }
                $parser = new Converter($resp->doSelectReturn);
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
                            if ($attr['Attribute'][0]['AttrName'] == 'zlaboratoryDate') {
                                if (!empty($from) && empty($to)) {
                                    $checkTime = (int)$item['zlaboratoryDate'] >= strtotime($from);
                                } elseif (empty($from) && !empty($to)) {
                                    $checkTime = (int)$item['zlaboratoryDate'] <= strtotime($to);
                                } elseif (!empty($from) && !empty($to)) {
                                    $checkTime = (int)$item['zlaboratoryDate'] >= strtotime($from) && (int)$item['zlaboratoryDate'] <= strtotime($to);
                                }
                            }
                        }
                    }

                    // Get experiment info from zTDH
                    $payload = [
                        'sid' => $sessionId,
                        'objectType' => 'zTDH',
                        'whereClause' => "id = U'" . $item['id'] . "'",
                        'maxRows' => 1,
                        'attributes' => [
                            'ztestType.zsym',
                            'id'
                        ]
                    ];
                    $resp = $service->callFunction('doSelect', $payload);
                    $parser = new Converter($resp->doSelectReturn);
                    if (!isset($parser->data['UDSObjectList'][0])) {
                        $parser->data['UDSObjectList'] = [$parser->data['UDSObjectList']];
                    }
                    $item['experiment_type'] = $parser->data['UDSObjectList'][0]['UDSObject'][1]['Attributes'][0]['Attribute'][1]['AttrValue'] ?? null;
                    array_push($experiments, $item);
                }
                if ((!empty($from) || !empty($to)) && !$checkTime) {
                    $deviceItem['report'] = [];
                    return $deviceItem;
                }
                usort($experiments, function ($item1, $item2) {
                    return $item2['zlaboratoryDate'] <=> $item1['zlaboratoryDate'];
                });
            }
            // get 10 item sort by date
            if(count($experiments) > 10){
                $experiments = array_slice($experiments, 0, 10);
            }
            $deviceItem['report'] = $experiments;
            return $deviceItem;
        } catch (\Exception $ex) {
            Log::error('Get report from device error | Reason: ' . $ex->getMessage() . '. ' . $ex->getFile() . ':' . $ex->getLine());
        }
    }
}

if (!function_exists('getEnergyReport')) {
    function getEnergyReport($type, $request)
    {
        // get user login session
        $user = session()->get(env('AUTH_SESSION_KEY'));
        // get url webservice
        $service = new CAWebServices(env('CA_WSDL_URL'));

        $whereClause = "class.type = '" . $type . "'";

        if (!empty($request['from'])) {
            $whereClause .= " AND zlaboratoryDate >= " . strtotime($request['from']) . "";
        }

        if (!empty($request['to'])) {
            $whereClause .= " AND zlaboratoryDate <= " . strtotime($request['to']) . "";
        }

        if (!empty($request['devices'])) {
            $whereClause .= ' AND zCI_Device.class = ' . $request['devices'];
        }

        if (!empty($request['species'])) {
            $whereClause .= ' AND zCI_Device_Type = ' . $request['species'];
        }

        if( !empty($request['dvql_id']) ){
            $whereClause .= " AND zrefnr_dvql =" . $request['dvql_id'];
        }

        if( !empty($request['td_id']) ){
            $whereClause .= " AND zrefnr_td =" . $request['td_id'];
        }

        if( !empty($request['nl_id']) ){
            $whereClause .= " AND zrefnr_nl =" . $request['nl_id'];
        }

        // query data webservice
        $payload = [
            'sid' => $user['ssid'],
            'objectType' => 'nr',
            'whereClause' => $whereClause,
            'maxRows' => 150,
            'attributes' => [
                'id',
                'zlaboratoryDate',
                'zCI_Device',
                'zCI_Device.name',
                'zCI_Device.class.type',
                'zCI_Device.zCI_Device_Kind.zsym',
                'zCI_Device.zCI_Device_Type.zsym',
                'zCI_Device.zManufacturer.zsym',
            ],
        ];
        // return data webservice
        $resp = $service->callFunction('doSelect', $payload);
        // convert data to object
        $data = new Converter($resp->doSelectReturn);
        $items = [];
        if (empty($data->data['UDSObjectList'])) {
            return $items;
        }

        // writing data into array
        if (!empty($data->data['UDSObjectList']) && count($data->data['UDSObjectList']) == 1) {
            $item =[];
            if (!empty($data->data['UDSObjectList']['UDSObject'][1]['Attributes'])) {
                foreach ($data->data['UDSObjectList']['UDSObject'][1]['Attributes'] as $attr) {
                    if ($attr['Attribute'][1]['AttrValue']) {
                        $item[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                    }
                }
            }
            array_push($items, $item);
        } else {
            foreach ($data->data['UDSObjectList'] as $obj) {
                $item =[];
                if (empty($obj['UDSObject'][1]['Attributes'])) continue;
                foreach ($obj['UDSObject'][1]['Attributes'] as $attr) {
                    if ($attr['Attribute'][1]['AttrValue']) {
                        $item[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                    }
                }
                array_push($items, $item);
            }
        }

        $items = array_values(collect($items)->sortBy(function($item){
            return @$item['zCI_Device.name'];
        })->toArray());

        foreach ($items as $key => $val) {
            if (!empty($val['zCI_Device'])) {
                // Get info
                $payload = [
                    'sid' => $user['ssid'],
                    'objectType' => 'zETC_Device',
                    'whereClause' => "id = U'" . $val['zCI_Device'] . "'",
                    'maxRows' => 10,
                    'attributes' => [
                        'zusefuel.zsym',
                        'zdesefficien',
                        'zpower_capacity'
                    ]
                ];
                $resp = $service->callFunction('doSelect', $payload);
                $parser = new Converter($resp->doSelectReturn);
                if (!isset($parser->data['UDSObjectList'][0])) {
                    $parser->data['UDSObjectList'] = [$parser->data['UDSObjectList']];
                }
                $info = [];
                foreach ($parser->data['UDSObjectList'] as $obj) {
                    if (!$obj) continue;
                    $info['handle_id'] = $obj['UDSObject'][0]['Handle'];
                    foreach ($obj['UDSObject'][1]['Attributes'] as $attr) {
                        if ($attr['Attribute'][1]['AttrValue']) {
                            $info[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                        }
                    }
                }
                if (!empty($request['use_fuel']) && (empty($info['zusefuel.zsym']) || stripos($info['zusefuel.zsym'], $request['use_fuel']) === false)) {
                    unset($items[$key]);
                    continue;
                }
                $items[$key]['info'] = $info;

                // Get experiment info
                $payload = [
                    'sid' => $user['ssid'],
                    'objectType' => $request['experiment_type'],
                    'whereClause' => "id = U'" . $val['id'] . "'",
                    'maxRows' => 10,
                    'attributes' => [
                        'zOutput_Tets',
                        'zEfficiency'
                    ]
                ];
                $resp = $service->callFunction('doSelect', $payload);
                $parser = new Converter($resp->doSelectReturn);
                if (!isset($parser->data['UDSObjectList'][0])) {
                    $parser->data['UDSObjectList'] = [$parser->data['UDSObjectList']];
                }
                $experiment = [];
                foreach ($parser->data['UDSObjectList'] as $obj) {
                    if (!$obj) continue;
                    $experiment['handle_id'] = $obj['UDSObject'][0]['Handle'];
                    foreach ($obj['UDSObject'][1]['Attributes'] as $attr) {
                        if ($attr['Attribute'][1]['AttrValue']) {
                            $experiment[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                        }
                    }
                }
                $items[$key]['experiment'] = $experiment;
            } elseif (!empty($request['use_fuel'])) {
                unset($items[$key]);
            }
        }
        return array_values($items);
    }
}

if (!function_exists('getEnergyIndex')) {
    function getEnergyIndex($type, $request)
    {
        // get user login session
        $user = session()->get(env('AUTH_SESSION_KEY'));
        // get url webservice
        $service = new CAWebServices(env('CA_WSDL_URL'));

        $whereClause = "class.type = '" . $type . "'";

        if (!empty($request['from'])) {
            $whereClause .= " AND zlaboratoryDate >= " . strtotime($request['from']) . "";
        }

        if (!empty($request['to'])) {
            $whereClause .= " AND zlaboratoryDate <= " . strtotime($request['to']) . "";
        }

        if (!empty($request['devices'])) {
            $whereClause .= ' AND zCI_Device.class = ' . $request['devices'];
        }

        if( !empty($request['dvql_id']) ){
            $whereClause .= " AND zrefnr_dvql =" . $request['dvql_id'];
        }

        if( !empty($request['td_id']) ){
            $whereClause .= " AND zrefnr_td =" . $request['td_id'];
        }

        if( !empty($request['nl_id']) ){
            $whereClause .= " AND zrefnr_nl =" . $request['nl_id'];
        }

        if (!empty($request['species'])) {
            $whereClause .= ' AND zCI_Device_Type = ' . $request['species'];
        }

        if (!empty($request['manufacturer'])) {
            $whereClause .= ' AND zManufacturer = ' . $request['manufacturer'];
        }

        if( !empty($request['equipment']) ){
            $whereClause .= " AND zCI_Device.name LIKE '%".$request['equipment']."%'";
        }
        // query data webservice
        $payload = [
            'sid' => $user['ssid'],
            'objectType' => 'nr',
            'whereClause' => $whereClause,
            'maxRows' => 150,
            'attributes' => [
                'id',
                'name',
                'class.type',
                'zlaboratoryDate',
                'zExperimenter.first_name',
                'zExperimenter.middle_name',
                'zExperimenter.last_name',
                'zExperimenter1.first_name',
                'zExperimenter1.middle_name',
                'zExperimenter1.last_name',
                'zExperimenter2.first_name',
                'zExperimenter2.middle_name',
                'zExperimenter2.last_name',
                'zExperimenter3.first_name',
                'zExperimenter3.middle_name',
                'zExperimenter3.last_name',
                'zCI_Device.zrefnr_nl.zsym',
                'zArea.zsym',
                'zCI_Device.class',
                'zCI_Device.name',
                'zCI_Device_Type.zsym',
                'zCI_Device.zrefnr_dvql.zsym',
                'zCI_Device.zrefnr_td.zsym',
                'zCI_Device.class.type',
                'zCI_Device.zCI_Device_Kind.zsym',
                'zManufacturer.zsym',
                'zvitrilapdat',
                'zCI_Device.serial_number',
                'zExperimenter.combo_name',
                'zExperimenter1.combo_name',
                'zExperimenter2.combo_name',
                'zExperimenter3.combo_name'
            ],
        ];
        // return data webservice
        $resp = $service->callFunction('doSelect', $payload);
        // convert data to object
        $data = new Converter($resp->doSelectReturn);
        $items = [];
        if (empty($data->data['UDSObjectList'])) {
            return $items;
        }

        // writing data into array
        if (!empty($data->data['UDSObjectList']) && count($data->data['UDSObjectList']) == 1) {
            $item =[];
            if (!empty($data->data['UDSObjectList']['UDSObject'][1]['Attributes'])) {
                foreach ($data->data['UDSObjectList']['UDSObject'][1]['Attributes'] as $attr) {
                    if ($attr['Attribute'][1]['AttrValue']) {
                        $item[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                    }
                }
            }
            array_push($items, $item);
        } else {
            foreach ($data->data['UDSObjectList'] as $obj) {
                $item =[];
                if (empty($obj['UDSObject'][1]['Attributes'])) continue;
                foreach ($obj['UDSObject'][1]['Attributes'] as $attr) {
                    if ($attr['Attribute'][1]['AttrValue']) {
                        $item[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                    }
                }
                array_push($items, $item);
            }
        }
        $items = array_values(collect($items)->sortBy(function($item){
            return @$item['zCI_Device.name'];
        })->toArray());
        foreach ($items as $key => $val) {
            $experimenters = '';
            for($i = 0; $i <= 3; $i++){
                if( $i == 0 ){
                    $experimenters .= @$val["zExperimenter.last_name"].' '.@$val["zExperimenter.first_name"].' '.@$val["zExperimenter.middle_name"] . ' ';
                }else{
                    $experimenters .= @$val["zExperimenter{$i}.last_name"].' '.@$val["zExperimenter{$i}.first_name"].' '.@$val["zExperimenter{$i}.middle_name"] . ' ';
                }
            }
            $experimenters = Str::lower(trim($experimenters));
            $experimenter = Str::lower(@$request['experimenter']);
            if (
                !empty($request['device_type']) && (empty($val['zCI_Device.zCI_Device_Kind.zsym']) || stripos(Str::lower($val['zCI_Device.zCI_Device_Kind.zsym']), Str::lower($request['device_type'])) === false)
                || !empty($request['manufacturing_number']) && (empty($val['zCI_Device.serial_number']) || stripos($val['zCI_Device.serial_number'], $request['manufacturing_number']) === false)
                || !empty($request['installation_location']) && (empty($val['zvitrilapdat']) || stripos($val['zvitrilapdat'], $request['installation_location']) === false)
                || !empty($experimenter) && (empty($experimenters) || mb_stripos($experimenters, $experimenter) === false)
            ) {
                unset($items[$key]);
                continue;
            }

            if (!empty($request['zTest_Run'])) {
                // Get experiment info
                $payload = [
                    'sid' => $user['ssid'],
                    'objectType' => $request['experiment_type'],
                    'whereClause' => "id = U'" . $val['id'] . "'",
                    'maxRows' => 10,
                    'attributes' => [
                        'id',
                        'zTest_Run.zsym'
                    ]
                ];
                $resp = $service->callFunction('doSelect', $payload);
                $parser = new Converter($resp->doSelectReturn);
                if (!isset($parser->data['UDSObjectList'][0])) {
                    $parser->data['UDSObjectList'] = [$parser->data['UDSObjectList']];
                }
                foreach ($parser->data['UDSObjectList'] as $obj) {
                    if (!$obj || (count($obj['UDSObject'][1]['Attributes']) < 2)) continue;
                    foreach ($obj['UDSObject'][1]['Attributes'] as $attr) {
                        if ($attr['Attribute'][0]['AttrName'] == 'zTest_Run.zsym' && stripos($attr['Attribute'][1]['AttrValue'], $request['zTest_Run']) === false) {
                            unset($items[$key]);
                        }
                    }
                }
            }
        }
        sortData($items, 'asc', 'zCI_Device.name');
        return array_values($items);
    }
}

if (!function_exists('formatInputDataEnergyReport')) {
    function formatInputDataEnergyReport($arrData)
    {
        $formattedArrData = [];
        if (!empty($arrData)) {
            foreach ($arrData as $key => $val) {
                switch ($key) {
                    case 'arrDesignValueExtendType':
                        $mainExtend = 'Design_Value';
                        $resultExtend = 'Result';
                        break;
                    case 'arrValueExtendType':
                        $mainExtend = 'Value';
                        $resultExtend = 'Result';
                        break;
                    default:
                        $mainExtend = 'DV';
                        $resultExtend = 'Result';
                }
                foreach ($val as $index => $item) {
                    $formattedArrData[$index] = [
                        'key' => $item,
                        'main_extend' => $mainExtend,
                        'result_extend' => $resultExtend
                    ];
                }
            }
        }
        ksort($formattedArrData);
        return array_values($formattedArrData);
    }
}

if (!function_exists('exportEnergyReport')) {
    function exportEnergyReport($request, $oldRequest)
    {
        // Get User Login Session
        $user = session()->get(env('AUTH_SESSION_KEY'));

        $handleIds = explode(',', $request['ids']);
        $arr = [];

        foreach ($handleIds as $key => $val) {
            $nrObj = getDataFromService('getObjectValues', 'nr:' . $val, [
                'zlaboratoryDate',
                'zCI_Device.zrefnr_dvql.zsym',
                'zCI_Device.zArea.zsym',
                'zCI_Device.zrefnr_td.zsym',
                'zCI_Device.zrefnr_nl.zsym',
                'zCI_Device.name',
                'zCI_Device.zCI_Device_Type.zsym',
                'zCI_Device.zManufacturer.zsym',
                'zCI_Device.zCI_Device_Kind.zsym',
                'zCI_Device.serial_number',
                'zCI_Device.zYear_of_Manafacture.zsym',
                'zCI_Device.zCountry.name',
                'zCI_Device',
                'zCI_Device.class',
                'zCI_Device.zYear_of_Manafacture',
                'zCI_Device.zStage.zsym',
            ]);
            if (empty($nrObj)) {
                return redirect()->back()->withErrors(['Báo cáo chưa có dữ liệu']);
            }

            $deviceInfo = getDataFromService('doSelect', 'zETC_Device', [
                'zplace',
                'zstage.zsym'
            ], "id = U'" . $nrObj['zCI_Device'] . "'");

            $zCNNLObjAttr = [
                'zTGTNHC_From',
                'zTGTNHC_To',
                'zTest_Run.zsym'
            ];
            for ($i = 0; $i <= 4; $i++) {
                foreach ($request['statistics'] as $statistic) {
                    if ($i == 0) {
                        $index = $statistic['key'] == 'zTest_RO' ? 'zTest_Rate_Output' : $statistic['key'];
                        array_push($zCNNLObjAttr, $index.'_Sym.zsym');
                        array_push($zCNNLObjAttr, $index.'_Unit.zsym');
                        array_push($zCNNLObjAttr, $statistic['key'] . '_' . $statistic['main_extend']);
                    } else {
                        array_push($zCNNLObjAttr, $statistic['key'] . '_' . $statistic['result_extend'] . $i);
                    }
                }
            }
            $zCNNLObj = getDataFromService('getObjectValues', $request['report_type'] . ':' . $val, $zCNNLObjAttr);

            // Data nrObj
            $arr[$key]['zlaboratoryDate'] = !empty($nrObj['zlaboratoryDate']) ? $nrObj['zlaboratoryDate'] : '';
            $arr[$key]['zCI_Device.zrefnr_dvql.zsym'] = !empty($nrObj['zCI_Device.zrefnr_dvql.zsym']) ? $nrObj['zCI_Device.zrefnr_dvql.zsym'] : '';
            $arr[$key]['zCI_Device.zArea.zsym'] = !empty($nrObj['zCI_Device.zArea.zsym']) ? $nrObj['zCI_Device.zArea.zsym'] : '';
            $arr[$key]['zCI_Device.zrefnr_td.zsym'] = !empty($nrObj['zCI_Device.zrefnr_td.zsym']) ? $nrObj['zCI_Device.zrefnr_td.zsym'] : '';
            $arr[$key]['zCI_Device.zrefnr_nl.zsym'] = !empty($nrObj['zCI_Device.zrefnr_nl.zsym']) ? $nrObj['zCI_Device.zrefnr_nl.zsym'] : '';
            $arr[$key]['zCI_Device.name'] = !empty($nrObj['zCI_Device.name']) ? $nrObj['zCI_Device.name'] : '';
            $arr[$key]['zCI_Device.zCI_Device_Type.zsym'] = !empty($nrObj['zCI_Device.zCI_Device_Type.zsym']) ? $nrObj['zCI_Device.zCI_Device_Type.zsym'] : '';
            $arr[$key]['zCI_Device.zManufacturer.zsym'] = !empty($nrObj['zCI_Device.zManufacturer.zsym']) ? $nrObj['zCI_Device.zManufacturer.zsym'] : '';
            $arr[$key]['zCI_Device.zCI_Device_Kind.zsym'] = !empty($nrObj['zCI_Device.zCI_Device_Kind.zsym']) ? $nrObj['zCI_Device.zCI_Device_Kind.zsym'] : '';
            $arr[$key]['zCI_Device.serial_number'] = !empty($nrObj['zCI_Device.serial_number']) ? $nrObj['zCI_Device.serial_number'] : '';
            $arr[$key]['zCI_Device.zYear_of_Manafacture.zsym'] = !empty($nrObj['zCI_Device.zYear_of_Manafacture.zsym']) ? $nrObj['zCI_Device.zYear_of_Manafacture.zsym'] : '';
            $arr[$key]['zCI_Device.zStage.zsym'] = !empty($nrObj['zCI_Device.zStage.zsym']) ? $nrObj['zCI_Device.zStage.zsym'] : '';
            $arr[$key]['zCI_Device.zCountry.name'] = !empty($nrObj['zCI_Device.zCountry.name']) ? $nrObj['zCI_Device.zCountry.name'] : '';
            $arr[$key]['zplace'] = !empty($deviceInfo['zplace']) ? $deviceInfo['zplace'] : '';
            $arr[$key]['other_info'][$request['other_info']] = $arr[$key][$request['other_info']];
            $arr[$key]['other_info']['zCI_Device.zrefnr_dvql.zsym'] = $arr[$key]['zCI_Device.zrefnr_dvql.zsym'];
            $arr[$key]['other_info']['experimental_time'] = (!empty($zCNNLObj['zTGTNHC_From']) ? date('d/m/Y', $zCNNLObj['zTGTNHC_From']) : 'dd/mm/yyyy') . ' - ' . (!empty($zCNNLObj['zTGTNHC_To']) ? date('d/m/Y', $zCNNLObj['zTGTNHC_To']) : 'd/mm/yyyy');
            $arr[$key]['other_info']['zTest_Run.zsym'] = !empty($zCNNLObj['zTest_Run.zsym']) ? $zCNNLObj['zTest_Run.zsym'] : '';

            // Data zCNNLObj
            $symbolsArr = $unitArr = [];
            for ($i = 0; $i <= 4; $i++) {
                // Statistics data
                foreach ($request['statistics'] as $statistic) {
                    if ($i == 0) {
                        $index = $statistic['key'] == 'zTest_RO' ? 'zTest_Rate_Output' : $statistic['key'];
                        $symbolsArr[$index . '_Sym.zsym'] = !empty($zCNNLObj[$index . '_Sym.zsym']) ? $zCNNLObj[$index . '_Sym.zsym'] : '';
                        $unitArr[$index . '_Unit.zsym'] = !empty($zCNNLObj[$index . '_Unit.zsym']) ? $zCNNLObj[$index . '_Unit.zsym'] : '';
                    }
                    $arr[$key]['statistics'][$i][$statistic['key'] . '_' . ($i == 0 ? $statistic['main_extend'] : ($statistic['result_extend'] . $i))] = !empty($zCNNLObj[$statistic['key'] . '_' . ($i == 0 ? $statistic['main_extend'] : ($statistic['result_extend'] . $i))]) ? $zCNNLObj[$statistic['key'] . '_' . ($i == 0 ? $statistic['main_extend'] : ($statistic['result_extend'] . $i))] : '';
                }

                // Chart data
                if ($i > 0) {
                    foreach ($request['chart_data'] as $chartData) {
                        $arr[$key]['chart'][$chartData['key']][] = !empty($zCNNLObj[$chartData['key'] . '_' . ($chartData['result_extend'] . $i)]) ? $zCNNLObj[$chartData['key'] . '_' . ($chartData['result_extend'] . $i)] : '';
                    }
                }
            }
            array_unshift($arr[$key]['statistics'], $symbolsArr, $unitArr);
        }
        usort($arr, function ($item1, $item2) {
            return $item1['zlaboratoryDate'] <=> $item2['zlaboratoryDate'];
        });
        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);

        // Read Template Excel
        $template = storage_path('templates_excel/cong_nghe_nang_luong/' . $request['excel_template'] . '/' . $request['excel_template'] . '_' . count($arr) . '.xlsx');
        $spreadsheet = $reader->load($template);

        $chartDataArr = [];
        $startChartDataIndex = 9;
        $startDuplicationIndex = max([$request['excel_item_info_height'] + $request['excel_item_other_info_height'] + 2, $request['excel_item_statistics_height']]) + 2;
        foreach ($arr as $key => $val) {
            // Sheet data
            $activeSheet = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM");
            if ($key > 0) {
                // Duplicate style for first table
                $activeSheet->duplicateStyle($spreadsheet->getActiveSheet()->getStyle('A' . $request['start_row'] . ':A' . ($request['start_row'] + $request['excel_item_info_height'])),'A' . ($request['start_row'] + $key * ($startDuplicationIndex)) . ':A' . ($request['start_row'] + $request['excel_item_info_height'] + $key * ($startDuplicationIndex)));
                $activeSheet->duplicateStyle($spreadsheet->getActiveSheet()->getStyle('B' . $request['start_row'] . ':D' . $request['start_row']),'B' . ($request['start_row'] + $key * ($startDuplicationIndex)) . ':D' . ($request['start_row'] + $key * ($startDuplicationIndex)));
                $activeSheet->getStyle('C' . ($request['start_row'] + $key * ($startDuplicationIndex)))->applyFromArray([
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ]
                ]);
                $activeSheet->duplicateStyle($spreadsheet->getActiveSheet()->getStyle('B' . ($request['start_row'] + 1) . ':B' . ($request['start_row'] + $request['excel_item_info_height'])),'B' . ($request['start_row'] + 1 + $key * ($startDuplicationIndex)) . ':B' . ($request['start_row'] + $request['excel_item_info_height'] + $key * ($startDuplicationIndex)));
                $activeSheet->duplicateStyle($spreadsheet->getActiveSheet()->getStyle('C' . ($request['start_row'] + 1) . ':D' . ($request['start_row'] + $request['excel_item_info_height'])),'C' . ($request['start_row'] + 1 + $key * ($startDuplicationIndex)) . ':D' . ($request['start_row'] + $request['excel_item_info_height'] + $key * ($startDuplicationIndex)));

                // Duplicate style for second table
                $activeSheet->duplicateStyle($spreadsheet->getActiveSheet()->getStyle('F' . $request['start_row'] . ':N' . $request['start_row']),'F' . ($request['start_row'] + $key * ($startDuplicationIndex)) . ':N' . ($request['start_row'] + $key * ($startDuplicationIndex)));
                $activeSheet->duplicateStyle($spreadsheet->getActiveSheet()->getStyle('F' . ($request['start_row'] + 1) . ':F' . ($request['start_row'] + $request['excel_item_statistics_height'])),'F' . ($request['start_row'] + 1 + $key * ($startDuplicationIndex)) . ':F' . ($request['start_row'] + $request['excel_item_statistics_height'] + $key * ($startDuplicationIndex)));
                $activeSheet->duplicateStyle($spreadsheet->getActiveSheet()->getStyle('G' . ($request['start_row'] + 1) . ':G' . ($request['start_row'] + $request['excel_item_statistics_height'])),'G' . ($request['start_row'] + 1 + $key * ($startDuplicationIndex)) . ':G' . ($request['start_row'] + $request['excel_item_statistics_height'] + $key * ($startDuplicationIndex)));
                $activeSheet->duplicateStyle($spreadsheet->getActiveSheet()->getStyle('H' . ($request['start_row'] + 1) . ':N' . ($request['start_row'] + $request['excel_item_statistics_height'])),'H' . ($request['start_row'] + 1 + $key * ($startDuplicationIndex)) . ':N' . ($request['start_row'] + $request['excel_item_statistics_height'] + $key * ($startDuplicationIndex)));

                // Duplicate style for third table
                $activeSheet->duplicateStyle($spreadsheet->getActiveSheet()->getStyle('B' . $request['second_start_row'] . ':B' . ($request['second_start_row'] + $request['excel_item_other_info_height'])),'B' . ($request['second_start_row'] + $key * ($startDuplicationIndex)) . ':B' . ($request['second_start_row'] + $request['excel_item_other_info_height'] + $key * ($startDuplicationIndex)));
                $activeSheet->duplicateStyle($spreadsheet->getActiveSheet()->getStyle('C' . $request['second_start_row'] . ':D' . ($request['second_start_row'] + $request['excel_item_other_info_height'])),'C' . ($request['second_start_row'] + $key * ($startDuplicationIndex)) . ':D' . ($request['second_start_row'] + $request['excel_item_other_info_height'] + $key * ($startDuplicationIndex)));

                // Duplicate content info table
                for ($c = 'B'; $c != 'D'; ++$c) {
                    for ($r = $request['start_row']; $r <= $request['start_row'] + $request['excel_item_info_height']; $r++) {
                        $activeSheet->getCell($c . ($r + $key * ($startDuplicationIndex)))->setValue($activeSheet->getCell($c . $r)->getValue());
                    }

                    // Duplicate content other info table
                    for ($r = $request['second_start_row']; $r <= $request['second_start_row'] + $request['excel_item_other_info_height']; $r++) {
                        $activeSheet->getCell($c . ($r + $key * ($startDuplicationIndex)))->setValue($activeSheet->getCell($c . $r)->getValue());
                    }
                }
                $activeSheet->mergeCells('A' . ($request['start_row'] + $key * ($startDuplicationIndex)) . ":A" . ($request['start_row'] + $request['excel_item_info_height'] + $key * ($startDuplicationIndex)));
                $activeSheet->mergeCells('C' . ($request['start_row'] + $key * ($startDuplicationIndex)) . ":D" . ($request['start_row'] + $key * ($startDuplicationIndex)));

                // Duplicate content statistic table
                for ($c = 'F'; $c != 'H'; ++$c) {
                    for ($r = $request['start_row'] + 1; $r <= $request['start_row'] + $request['excel_item_statistics_height']; $r++) {
                        $activeSheet->getCell($c . ($r + $key * ($startDuplicationIndex)))->setValue($activeSheet->getCell($c . $r)->getValue());
                    }
                }
                for ($c = 'H'; $c != 'L'; ++$c) {
                    $activeSheet->getCell($c . ($request['start_row'] + $key * ($startDuplicationIndex)))->setValue($activeSheet->getCell($c . $request['start_row'])->getValue());
                }
                $activeSheet->mergeCells('K' . ($request['start_row'] + $key * ($startDuplicationIndex)) . ":N" . ($request['start_row'] + $key * ($startDuplicationIndex)));
                $activeSheet->getCell('F' . ($request['start_row'] + $key * ($startDuplicationIndex)))->setValue('STT');
                $activeSheet->getCell('G' . ($request['start_row'] + $key * ($startDuplicationIndex)))->setValue('THÔNG SỐ');
            }

            $count = 0;
            $activeSheet->getCell('A' . ($request['start_row'] + $key * ($startDuplicationIndex)))->setValue($request['excel_item_info_title'] . ' CỦA NGÀY '. (!empty($val['zlaboratoryDate']) ? date('d/m/Y', $val['zlaboratoryDate']) : ''));

            // Set value for info table
            foreach ($val as $item) {
                if ($count > $request['excel_item_info_height']) {
                    continue;
                }
                $activeSheet->getCell('D' . ($count + $request['start_row'] + $key * ($startDuplicationIndex)))->setValue($item);
                $count++;
            }

            foreach ($val as $index => $item) {
                // Set value for other info table
                if ($index == 'other_info') {
                    $count = 0;
                    foreach ($item as $otherInfoVal) {
                        if ($count > $request['excel_item_other_info_height']) {
                            continue;
                        }
                        $activeSheet->getCell('D' . ($count + $request['second_start_row'] + $key * ($startDuplicationIndex)))->setValue($otherInfoVal);
                        $count++;
                    }
                }

                // Set value for statistics table
                if ($index == 'statistics') {
                    $col = 0;
                    for ($c = 'H'; $c != 'O'; ++$c) {
                        $row = 0;
                        foreach ($item[$col] as $statistic) {
                            $activeSheet->getCell($c . ($row + $request['statistics_first_insertion_row'] + $key * ($startDuplicationIndex)))->setValue($statistic);
                            $row++;
                        }
                        $col++;
                    }
                }
            }

            // Set value for chart tables
            for ($i = 1; $i <= $request['number_chart_data']; $i++) {
                $spreadsheet->setActiveSheetIndexByName("DATA_BĐ" . $i)->getCell('A' . $startChartDataIndex)->setValue(!empty($val['zlaboratoryDate']) ? date('d/m/Y', $val['zlaboratoryDate']) : '');
            }

            foreach ($val['chart'] as $chartKey => $chart) {
                foreach ($request['chart_data'] as $chartData) {
                    if ($chartKey == $chartData['key']) {
                        $chartDataArr[$chartKey][] = $chart;
                    }
                }
            }
            $startChartDataIndex++;
        }

        // Write chart data
        $valid = true;
        if (count($chartDataArr[$request['chart_data'][0]['key']]) > 1) {
            $firstArr = $chartDataArr[$request['chart_data'][0]['key']][0];
            for ($i = 1; $i < count($chartDataArr[$request['chart_data'][0]['key']]); $i++) {
                $result = array_diff($firstArr, $chartDataArr[$request['chart_data'][0]['key']][$i]);
                if (!empty($result)) {
                    $valid = false;
                }
            }
        }

        if (!$valid) {
            for ($i = $request['chart_first_insertion_row']; $i <= $request['chart_last_insertion_row']; $i++) {
                for ($c = 'A'; $c != 'G'; ++$c) {
                    for ($j = 1; $j <= $request['number_chart_data']; $j++) {
                        $spreadsheet->setActiveSheetIndexByName("DATA_BĐ" . $j)->getCell($c . $i)->setValue('');
                    }
                }
            }
            foreach ($request['chart_data_invalid_input_msg_index'] as $val) {
                $spreadsheet->setActiveSheetIndexByName("BIEU_DO")->getCell($val)->setValue($spreadsheet->setActiveSheetIndexByName("BIEU_DO")->getCell($val)->getValue() . ' - Dữ liệu biểu đồ không hợp lệ');
            }
        } else {
            $countChart = -1;
            foreach ($chartDataArr as $chartData) {
                $countChart++;
                if ($countChart == 0) {
                    $count = 0;
                    for ($c = 'C'; $c != 'G'; ++$c) {
                        for ($i = 1; $i <= $request['number_chart_data']; $i++) {
                            $spreadsheet->setActiveSheetIndexByName("DATA_BĐ" . $i)->getCell($c . $request['chart_first_insertion_row'])->setValue($chartData[0][$count]);
                        }
                        $count++;
                    }
                    continue;
                }
                setChartData($spreadsheet, $chartData, "DATA_BĐ" . $countChart);
            }
        }

        $sheetActive = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM");
        $inputs = [
            'Ngày bắt đầu: ' => 'from',
            'Ngày kết thúc: ' => 'to',
            'Thiết bị: ' => 'equipment',
            'Đơn vị quản lý: ' => 'dvql_id',
            'Trạm/Nhà máy: ' => 'td_id',
            'Ngăn lộ/Hệ thống: ' => 'nl_id',
            'Kiểu thiết bị: ' => 'device_type',
            'Số chế tạo: ' => 'manufacturing_number',
            'Vị trí lắp đặt: ' => 'installation_location',
            'Loại thiết bị: ' => 'devices',
            'Hãng sản xuất: ' => 'manufacturer',
            'Người thí nghiệm: ' => 'experimenter',
            'Chủng loại: ' => 'species',
            'Loại hình thí nghiệm: ' => 'zTest_Run',
        ];
        $requestArr = formatRequest($oldRequest);
        $columns = ['C', 'D', 'E', 'F', 'G'];
        fillSearchRequestToExcel($sheetActive, $requestArr, $columns, $inputs);
        $sheetActive->getStyle('A7:Z7')->getAlignment()->setWrapText(false);
        $spreadsheet->setActiveSheetIndexByName("BIEU_DO");
        // Return Link Download
        return writeExcel($spreadsheet, 'bao-cao-cong-nghe-nang-luong' . '-' . time() .'-' . $user['ssid'] . '.xlsx');
    }
}

if (!function_exists('writeExcel')) {
    function writeExcel($spreadsheet, $filename)
    {
        // Create New Writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);

        // Output Permission
        $outputDir = public_path('export');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        // Output path
        $output = $outputDir . '/' . $filename;
        if (is_file($output)) {
            unlink($output);
        }
        // Writer File Into Path
        $writer->save('export/' . $filename);

        // Return Link Download
        return str_replace(public_path(''), getenv('APP_URL'), $output);
    }
}

// function to check for duplicate values of tests
if (!function_exists('checkValueChart')) {
    function checkValueChart($arr)
    {
        // If 2 records are selected, they will be checked.
        // If the results of experiment 1, experiment 2, experiment 3, experiment 4 are the same, the chart data will be output, if different, the chart will not be output
        if(count($arr) > 1){
            for ($j=0; $j < count($arr); $j++) {
                for ($i=1; $i <= 4; $i++) {
                    $data_check[] = $arr[$j]['zRate_Output_Test_Result'.$i]??'';
                }
            }
            $data_check = array_unique($data_check);
            if(count($data_check) == 4){
                $check = 1;
            }else{
                $check = 0;
            }
        }else{
            $check = 1;
        }
        return $check;
    }
}


if (!function_exists('showOilIndex')) {
    function showOilIndex($type, $request)
    {
        // get user login session
        $user = session()->get(env('AUTH_SESSION_KEY'));
        // get url webservice
        $service = new CAWebServices(env('CA_WSDL_URL'));

        $whereClauseBase = "class.type = '" . $type . "' AND class.zetc_type = 1 ";
        if (!empty($request['from'])) {
            $whereClauseBase .= " AND zlaboratoryDate >= " . strtotime($request['from']) . "";
        }
        if (!empty($request['to'])) {
            $whereClauseBase .= " AND zlaboratoryDate <= " . strtotime($request['to']) . "";
        }
        // Ngày lấy mẫu
        if (!empty($request['from_create'])) {
            $whereClauseBase .= " AND zsamplingDate >= " . strtotime($request['from_create']) . "";
        }
        if (!empty($request['to_create'])) {
            $whereClauseBase .= " AND zsamplingDate <= " . strtotime($request['to_create']) . "";
        }
        // Thiết bị
        if (!empty($request['equipment'])) {
            $whereClauseBase .= " AND zCI_Device.name LIKE '%". $request['equipment'] ."%'";
        }
        // Số chế tạo
        if (!empty($request['manufacturing_number'])) {
            $whereClauseBase .= " AND zCI_Device.serial_number = '" . $request['manufacturing_number'] . "'";
        }
        // Ngăn lộ
        if (!empty($request['nl'])) {
            $whereClauseBase .= " AND zrefnr_nl.zsym = '" . $request['nl'] . "'";
        }
        // Trạm điện
        if (!empty($request['td'])) {
            $whereClauseBase .= " AND zrefnr_td.zsym LIKE '%" . $request['td'] . "%'";
        }
        // Đơn vị quản lý
        if (!empty($request['dvql'])) {
            $whereClauseBase .= " AND zrefnr_dvql.zsym LIKE '%" . $request['dvql'] . "%'";
        }

        $payload = [
            'sid' => $user['ssid'],
            'objectType' => 'nr',
            'whereClause' => $whereClauseBase,
            'maxRows' => 150,
            'attributes' => [
                'name',
                'zlaboratoryDate',
                'id',
                'zExperimenter.first_name',
                'zExperimenter.middle_name',
                'zExperimenter.last_name',
            ],
        ];

        $items = [];
        if( !empty($request['experimenter']) )
        {
            $arrayKeys = ['first_name', 'last_name', 'middle_name'];
            foreach($arrayKeys as $key){
                $whereClause = '';
                $whereClause .= $whereClauseBase;
                $whereClause .= " AND zExperimenter.".$key." LIKE '%" . $request['experimenter'] . "%'";

                $payload['whereClause'] = $whereClause;

                // return data webservice
                $resp = $service->callFunction('doSelect', $payload);
                // convert data to object
                $data = new Converter($resp->doSelectReturn);
                $item = formatData($data);
                $items = array_merge($items, $item);
            }
        }else{
            $resp = $service->callFunction('doSelect', $payload);
            $data = new Converter($resp->doSelectReturn);
            $item = formatData($data);
            $items = array_merge($items, $item);
        }

        $items = collect($items)->unique('id')->toArray();
        foreach ($items as $index =>  $item) {
            $zHDObj = [];
            $resp = $service->callFunction('getObjectValues', [
                'sid' => $user['ssid'],
                'objectHandle' => 'zHD:' . $item['id'],
                'attributes' => []
            ]);
            $parser = new Converter($resp->getObjectValuesReturn);
            foreach ($parser->data['UDSObject'][1]['Attributes'] as $attr) {
                if (!$attr['Attribute'][1]['AttrValue']) {
                    continue;
                }
                $zHDObj[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
            }
            $item['zhd'] = [
                'H2' => @$zHDObj['zHydro'],
                'CH4' => @$zHDObj['zMetan'],
                'C2H4' => @$zHDObj['zEtylen'],
                'C2H6' => @$zHDObj['zEtan'],
                'C2H2' => @$zHDObj['zAcetylen'],
                'CO' => @$zHDObj['zMonoxyd_Cacbon'],
                'CO2' => @$zHDObj['zDioxyd_Cacbon'],
            ];
            $items[$index] = $item;
        }
        return array_values($items);
    }
}

if(!function_exists('formatData')){
    function formatData($data){
        $items = [];
        if($data->data['UDSObjectList']){
            // writing data into array
            foreach ($data->data['UDSObjectList'] as $obj) {
                $item =[];
                if( !empty($obj['UDSObject']) ) {
                    foreach ($obj['UDSObject'][1]['Attributes'] as $attr) {
                        if ($attr['Attribute'][1]['AttrValue']) {
                            $item[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                        }
                    }
                }
                if(!empty($obj[1]) && $obj[1]['Attributes'])
                {
                    foreach ($obj[1]['Attributes'] as $attr) {
                        if ($attr['Attribute'][1]['AttrValue']) {
                            $item[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                        }
                    }
                }
                if(!empty($item)){
                    array_push($items, $item);
                }
            }
        }
        return $items;
    }
}

if (!function_exists('getEquipmentUnderInspection')) {
    function getEquipmentUnderInspection($type, $request)
    {

        // get user login session
        $user = session()->get(env('AUTH_SESSION_KEY'));
        if(!empty($request['session_id'])){
            $user['ssid'] = $request['session_id'];
        }
        // get url webservice
        $service = new CAWebServices(env('CA_WSDL_URL'));
        $whereClause = $type;
        $whereClause .= " AND class.zetc_type != 1 AND delete_flag = 0";

        if (!empty($request['type_device'])) {
            $whereClause .= " AND class.type LIKE '%" . addslashes($request['type_device']) . "%'";
        }

        if (!empty($request['type_device_id'])) {
            $whereClause .= " AND class = " . $request['type_device_id'];
        }

        if (!empty($request['management_unit'])) {
            $whereClause .= " AND zrefnr_dvql.zsym LIKE '%" . addslashes($request['management_unit']) . "%'";
        }

        if (!empty($request['manufacturer'])) {
            $whereClause .= " AND zManufacturer.zsym LIKE '%" . addslashes($request['manufacturer']) . "%'";
        }

        if (!empty($request['manufacturer_id'])) {
            $whereClause .= " AND zManufacturer = " . $request['manufacturer_id'];
        }

        if  (!empty($request['year_from']) || !empty($request['year_to'])) {
            $yearFrom = !empty($request['year_from']) ? (int) $request['year_from'] : 1995;
            $yearTo = !empty($request['year_to']) ? (int) $request['year_to'] : (int) date('Y');
            $count = 0;

            for ($i = $yearFrom; $i <= $yearTo; $i++) {
                if ($count == 0) {
                    $whereClause .= " AND (zYear_of_Manafacture.zsym = '" . $i . "'";
                } else {
                    $whereClause .= " OR zYear_of_Manafacture.zsym = '" . $i . "'";
                }
                if ($count == $yearTo - $yearFrom) {
                    $whereClause .= ")";
                }
                $count++;
            }
        }

        if (!empty($request['zrefnr_dvql'])) {
            $whereClause .= " AND zrefnr_dvql = " . $request['zrefnr_dvql'];
        }

        if (!empty($request['zArea'])) {
            $whereClause .= " AND zArea = " . $request['zArea'];
        }
        // get all records webservice
        $items = getAllDataFromService('nr', $request['attributes'] ?? [], $whereClause);
        foreach($items as $index => $item){
            $items[$index]['count'] = 1;
        }
        $items = array_values(collect($items)->sortBy(function($item){
            return @$item['name'];
        })->toArray());
        // get data assoc
        if(!empty($request['attributes_2'])){
            // get data assoc
            foreach($items as $key => $value){
                $payload_2 = [
                    'sid' => $user['ssid'],
                    'objectType' => 'zETC_Device',
                    'whereClause' => "id = U'" . $value['id'] . "'",
                    'maxRows' => 1,
                    'attributes' => $request['attributes_2']
                ];
                $resp_2 = $service->callFunction('doSelect', $payload_2);
                $parser_2 = new Converter($resp_2->doSelectReturn);
                foreach ($parser_2->data['UDSObjectList']['UDSObject'][1]['Attributes'] as $attr_2) {
                    $zCNNLObj[$attr_2['Attribute'][0]['AttrName']??''] = $attr_2['Attribute'][1]['AttrValue']??'';
                }
                foreach ($request['attributes_2'] as $key_2 => $val){
                    $items[$key][$val] = $zCNNLObj[$val]??'';
                }
            }
        }

        // if report 1, check zhankiemdinhdate
        if(!empty($request['number']) && $request['number'] == 1){
            if(!empty($request['from'])){
                $from = strtotime($request['from']);
            }
            if(!empty($request['to'])){
                $to = strtotime($request['to']) + 86400;
            }
            foreach ($items as $key => $val) {
                if ((!empty($from) && empty($val['zhankiemdinhdate'])) || (!empty($from) && $val['zhankiemdinhdate'] < $from) || (!empty($to) && empty($val['zhankiemdinhdate'])) || (!empty($to) && $val['zhankiemdinhdate'] > $to) || empty($val['zhankiemdinhdate'])) {
                    unset($items[$key]);
                    continue;
                }
                if( !empty($request['zdvquanlydiemdosrel']) && (empty($val['zdvquanlydiemdosrel']) || $val['zdvquanlydiemdosrel'] != $request['zdvquanlydiemdosrel']) ){
                    unset($items[$key]);
                    continue;
                }
            }
        }
        // If it's report number 2, check the device for duplicates
        if(!empty($request['number']) && $request['number'] == 2 && count($items) > 0){
            // String concatenation to check for duplicate values
            foreach($items as $key => $value){
                $text = '';
                if(!empty($value['class.type'])){
                    $text = $text.$value['class.type'];
                }
                if(!empty($value['zCI_Device_Type.zsym'])){
                    $text = $text.'_'.$value['zCI_Device_Type.zsym'];
                }
                if(!empty($value['zrefnr_dvql.zsym'])){
                    $text = $text.'_'.$value['zrefnr_dvql.zsym'];
                }
                if(!empty($value['zManufacturer.zsym'])){
                    $text = $text.'_'.$value['zManufacturer.zsym'];
                }
                if(!empty($value['zYear_of_Manafacture.zsym'])){
                    $text = $text.'_'.$value['zYear_of_Manafacture.zsym'];
                }
                if($text == $value['class.type']){
                    $text = $text.'_'.$key;
                }
                $data_items[$key] = $text;
                $items[$key]['unique'] = $text;
            }
            // unset array duplicate
            $data_items = array_unique($data_items);

            // get key array no duplicate
            foreach($data_items as $key => $val){
                $key_check[$key] = $key;
            }
            $items_sync = [];
            // foreach get data items new
            foreach($items as $key => $val){
                // if $key equals the unique key, get data normal
                if(!empty($key_check[$key]) || $key == 0){
                    foreach($request['attributes'] as $bv){
                        $items_sync[$key][$bv] = $val[$bv]??'';
                    }
                    $items_sync[$key]['unique'] = $val['unique']??'';
                    $items_sync[$key]['count'] = 1;
                }else{
                    // If it does not match the unique key, it will find the record with the same unique and add the number
                    $unique = $items[$key]['unique'];
                    foreach($items_sync as $key_2 => $it){
                        if($it['unique'] == $unique){
                            $items_sync[$key_2]['count'] = $it['count'] + 1;
                        }
                    }
                }
            }
            $items = $items_sync;
        }
        // If it's report number 4, check the device for duplicates
        if(!empty($request['number']) && $request['number'] == 4 && count($items) > 0){
            // String concatenation to check for duplicate values
            foreach($items as $key => $value){
                $text = '';
                if(!empty($value['class.type'])){
                    $text = $text.$value['class.type'];
                }
                if(!empty($value['zCI_Device_Type.zsym'])){
                    $text = $text.'_'.$value['zCI_Device_Type.zsym'];
                }
                if(!empty($value['zManufacturer.zsym'])){
                    $text = $text.'_'.$value['zManufacturer.zsym'];
                }
                $data_items[$key] = $text;
                $items[$key]['unique'] = $text;
            }
            // unset array duplicate
            $data_items = array_unique($data_items);

            // get key array no duplicate
            foreach($data_items as $key => $val){
                $key_check[$key] = $key;
            }
            $items_sync = [];
            $result = ['Đạt','Không đạt cấp chính xác','Hư hỏng mạch dòng','Hư hỏng mạch áp','Hư hỏng màn hình','Nguyên nhân khác'];
            // foreach get data items new
            foreach($items as $key => $val){
                // if $key equals the unique key, get data normal
                if(!empty($key_check[$key]) || $key == 0){
                    foreach($request['attributes'] as $bv){
                        $items_sync[$key][$bv] = $val[$bv]??'';
                    }
                    $items_sync[$key]['unique'] = $val['unique']??'';
                    foreach($result as $key_2 => $rs){
                        if(!empty($val['zkqkiemdinhsrel.zsym']) && $val['zkqkiemdinhsrel.zsym'] == $rs){
                            $items_sync[$key]['false_'.$key_2] = 1;
                        }
                    }
                }else{
                    // If it does not match the unique key, it will find the record with the same unique and add the number
                    $unique = $items[$key]['unique']??'';
                    foreach($items_sync as $key_3 => $it){
                        if(!empty($it['unique']) && $it['unique'] == $unique){
                            foreach($result as $key_4 => $rs){
                                if(!empty($val['zkqkiemdinhsrel.zsym']) && $val['zkqkiemdinhsrel.zsym'] == $rs){
                                    if(!empty($it['false_'.$key_4])){
                                        $items_sync[$key_3]['false_'.$key_4] = $it['false_'.$key_4] + 1;
                                    }else{
                                        $items_sync[$key_3]['false_'.$key_4] = 1;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $items = $items_sync;
        }
        return array_values($items);
    }
}

if (!function_exists('saveFileSync')) {
    function saveFileSync($data, $title, $sessionId)
    {
        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);
        // Read Template Excel
        $template = storage_path('templates_excel/do_luong/template_report_4.xlsx');
        $spreadsheet = $reader->load($template);
        // Create New Writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);
        $count = 0;
        foreach($data as $key => $value){
            $count++;
            $colum_key = $count + 4;
            $table = ['A','B','C','D','E','F','G','H','I','J'];
            $colum_name = ['','class.type','zCI_Device_Type.zsym','zManufacturer.zsym','false_0','false_1','false_2','false_3','false_4','false_5'];
            foreach($colum_name as $k => $val){
                if($k == 0){
                    $spreadsheet->setActiveSheetIndexByName('Sheet1')->getCell($table[$k] . ($colum_key))->setValue($count);
                }else{
                    $spreadsheet->setActiveSheetIndexByName('Sheet1')->getCell($table[$k] . ($colum_key))->setValue( htmlspecialchars_decode($value[$val] ?? '') );
                }
                $spreadsheet->getActiveSheet()->getStyle($table[$k] . ($colum_key))->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);
            }
        }
        // Writer File Into Path
        $year = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now())->year;
        $month = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now())->month;
        if($month < 10){
            $month = '0'.$month;
        }
        $time = $month.'-'.$year;

        $title = 'bao-cao-so-luong-su-co-theo-hang'.'_'.$title.'_'.$time;
        // create foder export
        try {
            File::makeDirectory(public_path('export/'));
        } catch(Exception $e){

        }
        // create foder do-luong
        try {
            File::makeDirectory(public_path('export/do-luong/'));
        } catch(Exception $e){

        }
        // create foder year
        try {
            File::makeDirectory(public_path('export/do-luong/'.$year.'/'));
        } catch(Exception $e){

        }
        $writer->save(public_path('export/do-luong/'.$year.'/').$title.'.xlsx');
    }
}

if (!function_exists('countDeviceMeasure')) {
    function countDeviceMeasure($data, $attributes, $request, $filter_search, $name_table)
    {
        if(!empty($data)){
            $items_sync = [];
            // if table 1, check unique
            if($name_table == 1){
                foreach($data as $key => $value){
                    $text = '';
                    foreach($attributes as $k => $val){
                        $data[$key][$val] = $value[$val]??'';
                        if(!empty($value[$val])){
                            $text .= '_'.$value[$val];
                        }
                    }
                    if($text == ''){
                        $data_items = [];
                        unset($data[$key]);
                        continue;
                    }else{
                        $data_items[$key] = $text;
                        $data[$key]['unique'] = $text;
                    }
                }
                // unset array duplicate
                $data_items = array_unique($data_items);
                // get key array no duplicate
                foreach($data_items as $key => $val){
                    $key_check[$key] = $key;
                }

                // unique by array attrs config('attributes.attribute_measure_report_5_1')
                $data_unique = collect($data)->unique('unique')->toArray();
                // count device in unique
                foreach($data_unique as $key => $val){
                    $val['count'] = 0;
                    foreach($data as $val2){
                        if($val2['unique'] == $val['unique']){
                            $val['count']++;
                        }
                    }
                    array_push($items_sync, $val);
                }
            }else{
                // foreach get data item_1 new
                foreach($data as $key => $val){
                    if( empty($val['zCI_Device1.name']) || (!empty($val['zCI_Device1.name']) && $val['zCI_Device1.name'] == '') ){
                        unset($data[$key]);
                        continue;
                    }
                    // if $key equals the unique key, get data normal
                    foreach($attributes as $bv){
                        $items_sync[$key][$bv] = $val[$bv]??'';
                    }
                }
                // get assoc zkqkiemdinhsrel for device1
                foreach($items_sync as $index => $val){
                    $whereClause = " id=U'".$val['zCI_Device1']."' ";
                    $result = getDataFromService('doSelect', 'zETC_Device', ['id', 'zkqkiemdinhsrel.zsym'], $whereClause);
                    $items_sync[$index]['zkqkiemdinhsrel1.zsym'] = $result['zkqkiemdinhsrel.zsym'] ?? '';
                }
            }
            // unset item the search request is not satisfied
            foreach($items_sync as $key => $value){
                if(
                    !empty($request['data_management']) && empty($value[$filter_search[0]])
                    || !empty($request['data_area']) && empty($value[$filter_search[1]])
                    || !empty($request['data_management']) && stripos($value[$filter_search[0]], $request['data_management']) === false
                    || !empty($request['data_area']) && stripos($value[$filter_search[1]], $request['data_area']) === false
                ){
                    unset($items_sync[$key]);
                    continue;
                }
            }
        }else{
            $items_sync = [];
        }
        return $items_sync;
    }
}

// Electromechanical
if (!function_exists('getReportFromType')) {
    function getReportFromType($types)
    {
        $arrType = [];
        if (in_array(config('constant.electromechanical.device.type.cap_boc_ha_ap'), $types)) {
            array_push($arrType, '1. BBTN PXCD1 - Cáp bọc hạ thế, 1 lõi', '2. BBTN PXCD2- Cáp bọc hạ thế - nhiều lõi', '3. BBTN PXCD3-Cáp bọc hạ thế, phân biệt các pha theo màu');
        }
        if (in_array(config('constant.electromechanical.device.type.cap_boc_trung_ap'), $types)) {
            array_push($arrType, '4. BBTN PXCD4-Cáp bọc trung áp ACSR - vỏ bọc HDPE', '5. BBTN PXCD5-Cáp bọc trung áp ACSR - vỏ bọc PVC', '6. BBTN PXCD6-Cáp bọc trung áp lõi CU hoặc Al');
        }
        if (in_array(config('constant.electromechanical.device.type.cap_luc_trung_the'), $types)) {
            array_push($arrType, '7. BBTN PXCD7-Cáp lực trung thế 1 lõi, màn chắn bằng đồng', '8. BBTN PXCD8-Cáp lực trung thế 1 lõi, màn chắn sợi đồng', '9. BBTN PXCD9-Cáp lực trung thế 3 lõi, màn chắn bằng đồng', '10. BBTN PXCD10-Cáp lực trung thế 3 lõi, màn chắn sợi đồng');
        }
        if (in_array(config('constant.electromechanical.device.type.cap_van_xoan'), $types)) {
            array_push($arrType, '11. BBTN PXCD11-Cáp vặn xoắn 2 lõi', '12. BBTN PXCD12-Cáp vặn xoắn 4 lõi');
        }
        if (in_array(config('constant.electromechanical.device.type.day_dan_tran'), $types)) {
            array_push($arrType, '13. BBTN PXCD13-Dây dẫn trần ACSR', '14. BBTN PXCD14-Dây siêu nhiệt', '15. BBTN PXCD15-Dây dẫn trần A630', '16. BBTN PXCD16-Dây dẫn trần TK');
        }

        return $arrType;
    }
}

if (!function_exists('getTypeFromReport')) {
    function getTypeFromReport($report)
    {
        $type = '';
        switch ($report) {
            case '1. BBTN PXCD1 - Cáp bọc hạ thế, 1 lõi':
            case '2. BBTN PXCD2- Cáp bọc hạ thế - nhiều lõi':
            case '3. BBTN PXCD3-Cáp bọc hạ thế, phân biệt các pha theo màu':
                $type = config('constant.electromechanical.device.type.cap_boc_ha_ap');
                break;
            case '4. BBTN PXCD4-Cáp bọc trung áp ACSR - vỏ bọc HDPE':
            case '5. BBTN PXCD5-Cáp bọc trung áp ACSR - vỏ bọc PVC':
            case '6. BBTN PXCD6-Cáp bọc trung áp lõi CU hoặc Al':
                $type = config('constant.electromechanical.device.type.cap_boc_trung_ap');
                break;
            case '7. BBTN PXCD7-Cáp lực trung thế 1 lõi, màn chắn bằng đồng':
            case '8. BBTN PXCD8-Cáp lực trung thế 1 lõi, màn chắn sợi đồng':
            case '9. BBTN PXCD9-Cáp lực trung thế 3 lõi, màn chắn bằng đồng':
            case '10. BBTN PXCD10-Cáp lực trung thế 3 lõi, màn chắn sợi đồng':
                $type = config('constant.electromechanical.device.type.cap_luc_trung_the');
                break;
            case '11. BBTN PXCD11-Cáp vặn xoắn 2 lõi':
            case '12. BBTN PXCD12-Cáp vặn xoắn 4 lõi':
                $type = config('constant.electromechanical.device.type.cap_van_xoan');
                break;
            case '13. BBTN PXCD13-Dây dẫn trần ACSR':
            case '14. BBTN PXCD14-Dây siêu nhiệt':
            case '15. BBTN PXCD15-Dây dẫn trần A630':
            case '16. BBTN PXCD16-Dây dẫn trần TK':
                $type = config('constant.electromechanical.device.type.day_dan_tran');
                break;
        }

        return $type;
    }
}

if (!function_exists('getAttrFromReportType')) {
    function getAttrFromReportType($type)
    {
        $attr = [];
        switch ($type) {
            case '1. BBTN PXCD1 - Cáp bọc hạ thế, 1 lõi':
                $attr = [
                    'z18COCchk',
                    'z27DCRCchk',
                    'z29IRAchk',
                    'z31PFWchk',
                    'z33MPIchk',
                    'z40MPPchk',
                ];
                break;
            case '2. BBTN PXCD2- Cáp bọc hạ thế - nhiều lõi':
                $attr = [
                    'z18COCchk',
                    'z69DCRchk',
                    'z89IRAchk',
                    'z110PFWchk',
                    'z111MPIchk',
                    'z6_check',
                ];
                break;
            case '3. BBTN PXCD3-Cáp bọc hạ thế, phân biệt các pha theo màu':
                $attr = [
                    'z18COCchk',
                    'z56DCRCchk',
                    'z116COCchk',
                    'z78PFWVchk',
                    'z79MPIchk',
                    'z100MPPVCchk',
                ];
                break;
            case '4. BBTN PXCD4-Cáp bọc trung áp ACSR - vỏ bọc HDPE':
                $attr = [
                    'z18COCchk',
                    'z29MPCchk',
                    'z36zPLCchk',
                    'z40NBAWchk',
                    'z42DCRCchk',
                    'z55PFWVchk',
                    'z43TMPIchk',
                    'z49TMPDchk'
                ];
                break;
            case '5. BBTN PXCD5-Cáp bọc trung áp ACSR - vỏ bọc PVC':
                $attr = [
                    'z50COCchk',
                    'z51MPCchk',
                    'z52zLCchk',
                    'z53NBAWchk',
                    'z55DCROCchk',
                    'z58PFWVchk',
                    'z56TMPIchk',
                    'z57TMPVchk'
                ];
                break;
            case '6. BBTN PXCD6-Cáp bọc trung áp lõi CU hoặc Al':
                $attr = [
                    'z18COCchk',
                    '',
                    '',
                    '',
                    'z2_check',
                    'z3_check',
                    'z28MPIchk',
                    'z24MPHDPEchk'
                ];
                break;
            case '7. BBTN PXCD7-Cáp lực trung thế 1 lõi, màn chắn bằng đồng':
                $attr = [
                    'z18COCchk',
                    'z2_check',
                    'z30RSSLchk',
                    'z34WPTchk',
                    'z36PFWVchk',
                    'z37MPIXLPEchk',
                    'z42MPOPVCchk'
                ];
                break;
            case '8. BBTN PXCD8-Cáp lực trung thế 1 lõi, màn chắn sợi đồng':
                $attr = [
                    'z18COCchk',
                    'z28DCRCchk',
                    'z29RSSLchk',
                    'z33WPTchk',
                    'z35PFWVchk',
                    'z36MPIXLPEchk',
                    'z41MPOPVCchk'
                ];
                break;
            case '9. BBTN PXCD9-Cáp lực trung thế 3 lõi, màn chắn bằng đồng':
                $attr = [
                    'z19abcchk',
                    'z44dcrchk',
                    'z49ros3chk',
                    'z56wpt39chk',
                    'z59pfwchk',
                    'z60mpinum',
                    'z85mpochk'
                ];
                break;
            case '10. BBTN PXCD10-Cáp lực trung thế 3 lõi, màn chắn sợi đồng':
                $attr = [
                    'z18cocchk',
                    'z39dcrchk',
                    'z45rsc3chk',
                    'z52wpt4chk',
                    'z55pfw5chk',
                    'z56mpi6chk',
                    'z81mpo7chk'
                ];
                break;
            case '11. BBTN PXCD11-Cáp vặn xoắn 2 lõi':
                $attr = [
                    'z18cocchk',
                    'z44tbs2chk',
                    'z57drc5chk',
                    'z49ira3chk',
                    'z54pfw4chk',
                    'z60mpt6chk'
                ];
                break;
            case '12. BBTN PXCD12-Cáp vặn xoắn 4 lõi':
                $attr = [
                    'z18coc_chk',
                    'z59tbscrw1_chk',
                    'z67rcrw1_chk',
                    'z59iratrw2_chk',
                    'z65pfwv_chk',
                    'z74mpi_chk'
                ];
                break;
            case '13. BBTN PXCD13-Dây dẫn trần ACSR':
                $attr = [
                    'z18abcchk',
                    'z55mpcchk',
                    'z68zplc18ck',
                    'z75nob14chk',
                    'z81mtg17chk',
                    'z84mdp19chk',
                    'z78dcr15chk'
                ];
                break;
            case '14. BBTN PXCD14-Dây siêu nhiệt':
                $attr = [
                    'z17coschk',
                    '',
                    '',
                    '',
                    '',
                    '',
                    'z38dcrnum'
                ];
                break;
            case '15. BBTN PXCD15-Dây dẫn trần A630':
                $attr = [
                    'z18coschk',
                    'z39mpchk',
                    '',
                    'z3_check',
                    '',
                    '',
                    'z4_check'
                ];
                break;
            case '16. BBTN PXCD16-Dây dẫn trần TK':
                $attr = [
                    'z18coschk',
                    'z23cpcchk',
                    'z38zplcchk',
                    '',
                    '',
                    '',
                    ''
                ];
                break;
        }

        return $attr;
    }
}

// get value type
// Function get data according to conductor type || PXCĐ
// Param 1: Data,
// Param 2: Current ordinal number to start returning data to excel
// Param 3: spreadsheet
// Param 4: Name type conductor
// Param 5: Type report (1 => is to take all data and return it, not filter by conductor type)
            // 2 => get all data & get input data file excel
            // 3 => get data by type

if (!function_exists('setValueType')) {
    function setValueType($data, $count, $spreadsheet, $name, $type_data, $request)
    {
        $colum_name = ['','zManufacturer.zsym','zlaboratoryDate','zrefnr_dvql.zsym','achieved','not_achieved'];
        // If it is a type, then export by type
        if($count != 0){
            $count = $count + 5;
            $number_title_type = $count + 6;
        }else{
            $number_title_type = $count + 6;
        }
        $stt = 0;
        $spreadsheet->setActiveSheetIndexByName('Sheet1')->getStyle("A".($count + 5).':H'.($count + 5 + count($data)))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                ]
            ]
        ]);
        $startColumn = 0;
        foreach(array_values($data) as $key => $value){
            $count++;
            $stt++;
            $table = ['A','B','C','D','E','F','G','H'];
            $title_table = ['STT','Hãng sản xuất','Thời gian','Đơn vị sử dụng','Đạt','Không đạt','Tổng số','Tỷ lệ %'];
            // type_data is the board type, If not a pivot table, enter the title & dupicate style title
            if($type_data != 2){
                $spreadsheet->setActiveSheetIndexByName("Sheet1")->duplicateStyle($spreadsheet->getActiveSheet()->getStyle('A' . (4) . ':H' . (4)),'A' . ($number_title_type - 2) . ':H' .($number_title_type - 2));
                $spreadsheet->setActiveSheetIndexByName('Sheet1')
                ->mergeCells("A".($number_title_type - 2).":H".($number_title_type - 2))
                ->getCell('A' . ($number_title_type - 2))
                ->setValue($name);
                // If the wire filter has only 1 type, the line excel starts to dump the data as line 6 .
                if(!empty($request['filter_type']) && count($request['filter_type']) == 1){
                    $colum_key = $count + 5;
                }else{
                    //If the string filter has only more than 1 type, the line excel starts to dump the data as line 7.
                    $colum_key = $count + 5;
                }
                // dupicate style title
                $spreadsheet->setActiveSheetIndexByName("Sheet1")->duplicateStyle($spreadsheet->getActiveSheet()->getStyle('A' . (4) . ':H' . (4)),'A' . (4 + $number_title_type - 5) . ':H' .(4 + $number_title_type - 5));
                // get value title
                foreach($table as $i => $item){
                    $spreadsheet->setActiveSheetIndexByName('Sheet1')
                    ->getCell($item . ($number_title_type - 1))
                    ->setValue($title_table[$i]);
                }
            }else{
                $colum_key = $count + 5;
            }
            if($key == 0){
                $startColumn = $colum_key;
            }
            foreach($colum_name as $k => $val){
                if($k == 0){
                    $spreadsheet->setActiveSheetIndexByName('Sheet1')->getCell($table[$k] . ($colum_key))->setValue($stt);
                }elseif($k == 2){
                    if($val == 'zlaboratoryDate'){
                        $date = '';
                        if(!empty($request['from'])){
                            $date_from = date('d/m/Y', strtotime($request['from']));
                            $date = $date . $date_from;
                        }
                        if(!empty($request['to'])){
                            if($date != ''){
                                $date = $date.'-';
                            }
                            $date_to = date('d/m/Y', strtotime($request['to']));
                            $date = $date . $date_to;
                        }
                    }
                    $spreadsheet->setActiveSheetIndexByName('Sheet1')->getCell($table[$k] . ($colum_key))->setValue($date);
                }else{
                    $spreadsheet->setActiveSheetIndexByName('Sheet1')->getCell($table[$k] . ($colum_key))->setValue($value[$val]??'');
                }
            }
            $count_all = 0;
            if(!empty($value['achieved'])){
                $count_all = $count_all + $value['achieved'];
            }
            if(!empty($value['not_achieved'])){
                $count_all = $count_all + $value['not_achieved'];
                $ratio = $value['not_achieved'] / $count_all *100;
                $ratio = substr($ratio, 0, 4);
            }else{
                $ratio = 0;
            }
            $spreadsheet->setActiveSheetIndexByName('Sheet1')->getCell($table[6] . ($colum_key))->setValue($count_all??'');
            $spreadsheet->setActiveSheetIndexByName('Sheet1')->getCell($table[7] . ($colum_key))->setValue($ratio??'');
        }
        alignmentExcel($spreadsheet->setActiveSheetIndexByName('Sheet1'), "B{$startColumn}:B{$colum_key}");
        alignmentExcel($spreadsheet->setActiveSheetIndexByName('Sheet1'), "D{$startColumn}:D{$colum_key}");
        $spreadsheet->setActiveSheetIndexByName('Sheet1')->getCell('A3')->setValue('');
        return $count;
    }
}

if (!function_exists('setWireType')) {
    function setWireType($data, $attributes)
    {
        $item = [];
        // get data according to conductor type
        foreach($data as $key => $value){
            // Cáp bọc hạ áp
            if(
                !empty($value['class.type']) && $value['class.type'] == '1. BBTN PXCD1 - Cáp bọc hạ thế, 1 lõi'
                || !empty($value['class.type']) && $value['class.type'] == '2. BBTN PXCD2- Cáp bọc hạ thế - nhiều lõi'
                || !empty($value['class.type']) && $value['class.type'] == '3. BBTN PXCD3-Cáp bọc hạ thế, phân biệt các pha theo màu'
            ){
                foreach($attributes as $k => $data){
                    $item[0][$key][$data] = $value[$data]??'';
                }
                if(!empty($value['zlaboratoryDate'])){
                    $item[0][$key]['year'] = date('Y', $value['zlaboratoryDate']);
                }else{
                    $item[0][$key]['year'] = '';
                }
                $item[0][$key]['type'] = 'Cáp bọc hạ áp';
            }
            // Cáp bọc trung áp
            if(
                !empty($value['class.type']) && $value['class.type'] == '4. BBTN PXCD4-Cáp bọc trung áp ACSR - vỏ bọc HDPE'
                || !empty($value['class.type']) && $value['class.type'] == '5. BBTN PXCD5-Cáp bọc trung áp ACSR - vỏ bọc PVC'
                || !empty($value['class.type']) && $value['class.type'] == '6. BBTN PXCD6-Cáp bọc trung áp lõi CU hoặc Al'
            ){
                foreach($attributes as $k => $data){
                    $item[1][$key][$data] = $value[$data]??'';
                }
                if(!empty($value['zlaboratoryDate'])){
                    $item[1][$key]['year'] = date('Y', $value['zlaboratoryDate']);
                }else{
                    $item[1][$key]['year'] = '';
                }
                $item[1][$key]['type'] = 'Cáp bọc trung áp';
            }
            // Cáp lực trung thế
            if(
                !empty($value['class.type']) && $value['class.type'] == '7. BBTN PXCD7-Cáp lực trung thế 1 lõi, màn chắn bằng đồng'
                || !empty($value['class.type']) && $value['class.type'] == '8. BBTN PXCD8-Cáp lực trung thế 1 lõi, màn chắn sợi đồng'
                || !empty($value['class.type']) && $value['class.type'] == '9. BBTN PXCD9-Cáp lực trung thế 3 lõi, màn chắn bằng đồng'
                || !empty($value['class.type']) && $value['class.type'] == '10. BBTN PXCD10-Cáp lực trung thế 3 lõi, màn chắn sợi đồng'
            ){
                foreach($attributes as $k => $data){
                    $item[2][$key][$data] = $value[$data]??'';
                }
                if(!empty($value['zlaboratoryDate'])){
                    $item[2][$key]['year'] = date('Y', $value['zlaboratoryDate']);
                }else{
                    $item[2][$key]['year'] = '';
                }
                $item[2][$key]['type'] = 'Cáp lực trung thế';
            }
            // Cáp vặn xoắn
            if(
                !empty($value['class.type']) && $value['class.type'] == '11. BBTN PXCD11-Cáp vặn xoắn 2 lõi'
                || !empty($value['class.type']) && $value['class.type'] == '12. BBTN PXCD12-Cáp vặn xoắn 4 lõi'
            ){
                foreach($attributes as $k => $data){
                    $item[3][$key][$data] = $value[$data]??'';
                }
                if(!empty($value['zlaboratoryDate'])){
                    $item[3][$key]['year'] = date('Y', $value['zlaboratoryDate']);
                }else{
                    $item[3][$key]['year'] = '';
                }
                $item[3][$key]['type'] = 'Cáp vặn xoắn';
            }
            // Dây dẫn trần
            if(
                !empty($value['class.type']) && $value['class.type'] == '13. BBTN PXCD13-Dây dẫn trần ACSR'
                || !empty($value['class.type']) && $value['class.type'] == '14. BBTN PXCD14-Dây siêu nhiệt'
                || !empty($value['class.type']) && $value['class.type'] == '15. BBTN PXCD15-Dây dẫn trần A630'
                || !empty($value['class.type']) && $value['class.type'] == '16. BBTN PXCD16-Dây dẫn trần TK'
            ){
                foreach($attributes as $data){
                    $item[4][$key][$data] = $value[$data]??'';
                }
                if(!empty($value['zlaboratoryDate'])){
                    $item[4][$key]['year'] = date('Y', $value['zlaboratoryDate']);
                }else{
                    $item[4][$key]['year'] = '';
                }
                $item[4][$key]['type'] = 'Dây dẫn trần';
            }
        }
        return $item;
    }
}

if (!function_exists('figuresForEachUnitData')) {
    function figuresForEachUnitData($request)
    {
        if (empty($request['startYear']) || empty($request['endYear'])) {
            return [
                'error' => ['Thời gian cần thống kê không được để trống']
            ];
        }

        if ($request['endYear'] < $request['startYear']) {
            return [
                'error' => ['Thời gian kết thúc phải lớn hơn hoặc bằng thời gian bắt đầu']
            ];
        }

        // Get report type
        $types = [
            config('constant.electromechanical.device.type.cap_boc_ha_ap'),
            config('constant.electromechanical.device.type.cap_boc_trung_ap'),
            config('constant.electromechanical.device.type.cap_luc_trung_the'),
            config('constant.electromechanical.device.type.cap_van_xoan'),
            config('constant.electromechanical.device.type.day_dan_tran'),
        ];
        $arrType = getReportFromType($types);

        // Get report
        $whereClause = '';
        foreach ($arrType as $key => $val) {
            if ($key == 0) {
                $whereClause = "class.type IN (";
            }
            $whereClause .= "'".$val."'";
            if ($key < count($arrType) - 1) {
                $whereClause .= ",";
            } else {
                $whereClause .= ")";
            }
        }

        $whereClause .= ' AND class.zetc_type = 1 AND zrefnr_dvql IS NOT NULL AND zManufacturer.zsym IS NOT NULL AND zlaboratoryDate >= '.strtotime($request['startYear'].'-01-01').' AND zlaboratoryDate < '.strtotime("+1 years", strtotime($request['endYear'].'-01-01'));

        if (! empty($request['zDVQL'])) {
            foreach ($request['zDVQL'] as $key => $val) {
                if ($key == 0) {
                    $whereClause .= " AND zrefnr_dvql IN (";
                }
                $whereClause .= "'".$val."'";
                if ($key < count($request['zDVQL']) - 1) {
                    $whereClause .= ",";
                } else {
                    $whereClause .= ")";
                }
            }
        }

        $reports = getAllDataFromService('nr', [
            'zrefnr_dvql.zsym',
            'zrefnr_dvql',
            'zlaboratoryDate',
            'zManufacturer.zsym',
            'zManufacturer',
            'class.type',
            'class',
        ], $whereClause);

        if(empty($reports)) {
            return [
                'error' => ['Không tìm thấy dữ liệu thống kê thỏa mãn']
            ];
        }

        $dataArr = [];
        foreach ($reports as $key => $report) {
            $type = getTypeFromReport($report['class.type']);
            $reports[$key]['zlaboratoryDate'] = date('Y', $report['zlaboratoryDate']);

            if (empty($dataArr[$report['zrefnr_dvql.zsym']])) {
                $dataArr[$report['zrefnr_dvql.zsym']] = [];
            }

            if (empty($dataArr[$report['zrefnr_dvql.zsym']][$reports[$key]['zlaboratoryDate'].'__'.$report['zManufacturer.zsym']])) {
                $dataArr[$report['zrefnr_dvql.zsym']][$reports[$key]['zlaboratoryDate'].'__'.$report['zManufacturer.zsym']] = [
                    config('constant.electromechanical.device.type.cap_boc_ha_ap') => 0,
                    config('constant.electromechanical.device.type.cap_boc_trung_ap') => 0,
                    config('constant.electromechanical.device.type.cap_luc_trung_the') => 0,
                    config('constant.electromechanical.device.type.cap_van_xoan') => 0,
                    config('constant.electromechanical.device.type.day_dan_tran') => 0,
                ];
            }

            if ($type == config('constant.electromechanical.device.type.cap_boc_ha_ap')) {
                $dataArr[$report['zrefnr_dvql.zsym']][$reports[$key]['zlaboratoryDate'].'__'.$report['zManufacturer.zsym']][config('constant.electromechanical.device.type.cap_boc_ha_ap')] += 1;
            }

            if ($type == config('constant.electromechanical.device.type.cap_boc_trung_ap')) {
                $dataArr[$report['zrefnr_dvql.zsym']][$reports[$key]['zlaboratoryDate'].'__'.$report['zManufacturer.zsym']][config('constant.electromechanical.device.type.cap_boc_trung_ap')] += 1;
            }

            if ($type == config('constant.electromechanical.device.type.cap_luc_trung_the')) {
                $dataArr[$report['zrefnr_dvql.zsym']][$reports[$key]['zlaboratoryDate'].'__'.$report['zManufacturer.zsym']][config('constant.electromechanical.device.type.cap_luc_trung_the')] += 1;
            }

            if ($type == config('constant.electromechanical.device.type.cap_van_xoan')) {
                $dataArr[$report['zrefnr_dvql.zsym']][$reports[$key]['zlaboratoryDate'].'__'.$report['zManufacturer.zsym']][config('constant.electromechanical.device.type.cap_van_xoan')] += 1;
            }

            if ($type == config('constant.electromechanical.device.type.day_dan_tran')) {
                $dataArr[$report['zrefnr_dvql.zsym']][$reports[$key]['zlaboratoryDate'].'__'.$report['zManufacturer.zsym']][config('constant.electromechanical.device.type.day_dan_tran')] += 1;
            }
        }

        return [
            'success' => true,
            'dataArr' => $dataArr
        ];
    }
}

if (!function_exists('numberOfExperimentsReportData')) {
    function numberOfExperimentsReportData($request)
    {
        if (empty($request['startYear']) || empty($request['endYear'])) {
            return [
                'error' => ['Thời gian cần thống kê không được để trống']
            ];
        }

        if ($request['endYear'] < $request['startYear']) {
            return [
                'error' => ['Thời gian kết thúc phải lớn hơn hoặc bằng thời gian bắt đầu']
            ];
        }

        // Define class and type arr of devices
        $arrClassType = [
            [
                'class' => 'Máy biến điện áp',
                'name' => 'Thí nghiệm, kiểm định TU trung áp',
                'unit' => 'quả',
                'type' => 'Máy biến điện áp trung áp',
                'id_type' => '401001'
            ],
            [
                'class' => 'Máy biến dòng',
                'name' => 'Thí nghiệm, kiểm định TI trung áp',
                'unit' => 'quả',
                'type' => 'Máy biến dòng điện trung áp',
                'id_type' => '401002'
            ],
            [
                'class' => 'Máy biến dòng',
                'name' => 'Thí nghiệm, kiểm định TI hạ thế',
                'unit' => 'quả',
                'type' => 'Máy biến dòng điện hạ áp',
                'id_type' => '401003'
            ],
            [
                'class' => 'Voltmeter',
                'name' => 'Thí nghiệm Voltmeter, Ampemeter',
                'unit' => 'cái',
                'type' => '',
                'id_type' => ''
            ],
            [
                'class' => 'Ampemeter',
                'name' => 'Thí nghiệm Voltmeter, Ampemeter',
                'unit' => 'cái',
                'type' => '',
                'id_type' => ''
            ],
            [
                'class' => 'Máy cắt hạ thế',
                'name' => 'Thí nghiệm MC hạ thế',
                'unit' => 'cái',
                'type' => '',
                'id_type' => ''
            ],
            [
                'class' => 'Aptomat',
                'name' => 'Thí nghiệm Aptomat các loại',
                'unit' => 'cái',
                'type' => '',
                'id_type' => ''
            ],
            [
                'class' => 'Contactor',
                'name' => 'Thí nghiệm Contactor, Rơle nhiệt',
                'unit' => 'cái',
                'type' => '',
                'id_type' => ''
            ],
            [
                'class' => 'Rơ le nhiệt',
                'name' => 'Thí nghiệm Contactor, Rơle nhiệt',
                'unit' => 'cái',
                'type' => '',
                'id_type' => ''
            ],
            [
                'class' => 'Tụ điện',
                'name' => 'Thí nghiệm tụ điện hạ áp',
                'unit' => 'bộ',
                'type' => 'Tụ điện hạ áp',
                'id_type' => '401004'
            ],
            [
                'class' => 'Chống sét van',
                'name' => 'Thí nghiệm chống sét van hạ áp',
                'unit' => 'chiếc',
                'type' => 'Chống sét van hạ áp',
                'id_type' => '401005'
            ],
            [
                'class' => 'Sứ cách điện',
                'name' => 'Thí nghiệm sứ cách điện',
                'unit' => 'bát',
                'type' => '',
                'id_type' => ''
            ],
            [
                'class' => 'Công tơ',
                'name' => 'Kiểm định công tơ điện tử 1 pha',
                'unit' => 'cái',
                'type' => 'Công tơ điện tử 1 pha',
                'id_type' => '400452'
            ],
            [
                'class' => 'Công tơ',
                'name' => 'Kiểm định công tơ điện tử 3 pha',
                'unit' => 'cái',
                'type' => 'Công tơ điện tử 3 pha',
                'id_type' => '400402'
            ],
            [
                'class' => 'Công tơ',
                'name' => 'Kiểm định công tơ cảm ứng 1 pha',
                'unit' => 'cái',
                'type' => 'Công tơ cảm ứng 1 pha',
                'id_type' => '400453'
            ],
            [
                'class' => 'Công tơ',
                'name' => 'Kiểm định công tơ cảm ứng 3 pha',
                'unit' => 'cái',
                'type' => 'Công tơ cảm ứng 3 pha',
                'id_type' => '400454'
            ],
            [
                'class' => 'Dụng cụ an toàn',
                'name' => 'Thí nghiệm dụng cụ an toàn',
                'unit' => 'TB',
                'type' => '',
                'id_type' => ''
            ],
        ];

        // Get report
        $whereClause = "class.zetc_type = 1 AND zlaboratoryDate >= " . strtotime($request['startYear'] . '-01-01') . " AND zlaboratoryDate < " . strtotime("+1 years", strtotime($request['endYear'] . "-01-01"));

        $subClause = "";
        foreach ($arrClassType as $key => $val) {
            if (!empty($val['type'])) {
                $subClause .= " OR (zCI_Device.class.type LIKE '%" . $val['class'] . "%' AND zCI_Device_Type = " . $val['id_type'] . ")";
            } else {
                $subClause .= " OR (zCI_Device.class.type LIKE '%" . $val['class'] . "%')";
            }
            $arrClassType[$key]['count'] = 0;
        }

        if (!empty($subClause)) {
            $whereClause .= " AND (" . substr($subClause, 4) . ")";
        }

        $reports = [];
        for ($i = 0; $i <= 3; $i++) {
            $whereClause .= " AND zExperimenter" . ($i != 0 ? $i : "") . ".dept = 1000001";

            $reportsByDepartment = getDataFromService('doSelect', 'nr', [
                'zCI_Device_Type',
                'zCI_Device_Type.zsym',
                'zCI_Device_Type.active_flag',
                'zlaboratoryDate',
                'class.type',
                'zCI_Device.class.type',
                'name'
            ], $whereClause, 100);

            $reports = array_merge($reports, $reportsByDepartment);
        }

        if(empty($reports)) {
            return [
                'error' => ['Không tìm thấy dữ liệu thống kê thỏa mãn']
            ];
        }

        foreach ($reports as $report) {
            foreach ($arrClassType as $key => $val) {
                if ((!empty($report['zCI_Device_Type']) && $report['zCI_Device_Type'] == $val['id_type']) || (empty($val['id_type']) && $report['zCI_Device.class.type'] == $val['class'])) {
                    $arrClassType[$key]['count'] += 1;
                }
            }
        }

        return [
            'success' => true,
            'dataArr' => $arrClassType
        ];
    }
}

/**
 * Get data for petrochemical
 */
if (!function_exists('getPetrochemicalData')) {
    function getPetrochemicalData($request)
    {
        $string = '';
        $arrayTypes = getDeviceByType($request['types']);
        foreach ($arrayTypes as $key => $val) {
            if ($key == 0) {
                $string .= '(';
            }

            $string .= "'" . $val . "'";

            if ($key < count($arrayTypes) - 1) {
                $string .= ',';
            } else {
                $string .= ')';
            }
        }

        // Get report
        $types = collect(getDataFromService('doSelect', 'grc', [], "zetc_type = 0 AND type IN " . $string . " AND delete_flag != 1", 100))->pluck('id')->toArray();

        $whereClause = "class.zetc_type != 1 AND zManufacturer IS NOT NULL AND delete_flag != 1 AND zManufacturer.zsym IS NOT NULL AND class IN (" . implode(',', $types) . ")";
        return getAllDataFromService('nr', [
            'name',
            'zCI_Device_Type',
            'zCI_Device_Type.zsym',
            'class.type',
            'zManufacturer',
            'zManufacturer.zsym',
        ], $whereClause);
    }
}

/**
 * Get data for petrochemical manufactures report
 */
if (!function_exists('petrochemicalManufacturesData')) {
    function petrochemicalManufacturesData($request)
    {
        $reports = getPetrochemicalData($request);
        if(empty($reports)) {
            return [
                'error' => ['Không tìm thấy dữ liệu thống kê thỏa mãn']
            ];
        }
        $dataArr = [];
        foreach ($request['types'] as $val) {
            $arrayDeviceTypes = getArrayDevice(config('constant.report.device.'. (Str::slug($val, '_')) ));
            $dataArr[$val] = [];
            foreach($reports as $report){
                if (!in_array($report['zManufacturer.zsym'], $dataArr[$val]) && in_array($report['class.type'], $arrayDeviceTypes)) {
                    $dataArr[$val][] = $report['zManufacturer.zsym'];
                }
            }
        }
        return [
            'success' => true,
            'dataArr' => $dataArr
        ];
    }
}

/**
 * Get data for number of devices by manufacture report
 */
if (!function_exists('numberOfDevicesByManufactureData')) {
    function numberOfDevicesByManufactureData($request)
    {
        if (empty($request['types'])) {
            $request['types'] = ['Máy biến áp', 'OLTC', 'Máy cắt'];
        }

        $reports = getPetrochemicalData($request);

        if(empty($reports)) {
            return [
                'error' => ['Không tìm thấy dữ liệu thống kê thỏa mãn']
            ];
        }

        $dataArr = [];
        foreach($request['types'] as $type){
            // get array device
            $arrayDeviceTypes = getArrayDevice(config('constant.report.device.'. (Str::slug($type, '_')) ));
            foreach ($reports as $report) {
                if (empty($dataArr[$report['zManufacturer.zsym'] . '_' . $type])) {
                    $dataArr[$report['zManufacturer.zsym'] . '_' . $type] = 0;
                }
                if (in_array($report['class.type'], $arrayDeviceTypes) ) {
                    $dataArr[$report['zManufacturer.zsym'] . '_' . $type]++;
                }
            }
        }
        return [
            'success' => true,
            'dataArr' => array_filter($dataArr) // filter index value = 0()
        ];
    }
}

/**
 * Get data for quantity percentage by manufacturer report
 */
if (!function_exists('quantityPercentageByManufacturerData')) {
    function quantityPercentageByManufacturerData($request)
    {
        if (empty($request['types'])) {
            $request['types'] = ['Máy biến áp', 'OLTC', 'Máy cắt'];
        }

        $reports = getPetrochemicalData($request);

        if(empty($reports)) {
            return [
                'error' => ['Không tìm thấy dữ liệu thống kê thỏa mãn']
            ];
        }

        $dataArr = [];
        foreach($request['types'] as $type){
            // get array device
            $arrayDeviceTypes = getArrayDevice(config('constant.report.device.'. (Str::slug($type, '_')) ));
            foreach ($reports as $report) {
                if (empty($dataArr[$report['zManufacturer.zsym'] . '_' . $type])) {
                    $dataArr[$report['zManufacturer.zsym'] . '_' . $type] = 0;
                }
                if (in_array($report['class.type'], $arrayDeviceTypes) ) {
                    $dataArr[$report['zManufacturer.zsym'] . '_' . $type]++;
                }
            }
        }

        return [
            'success' => true,
            'dataArr' => array_filter($dataArr) // filter index value = 0()
        ];
    }
}

if (!function_exists('checkUniquePXCĐ1')) {
    function checkUniquePXCĐ1($list_data, $attributes, $type_table)
    {
        $items_sync = [];
        if(!empty($list_data)){
            foreach($list_data as $key => $value){
                foreach($value as $ke => $valu){
                    // if(empty($valu['zManufacturer.zsym']) || empty($valu['zrefnr_dvql.zsym']) || empty($valu['class.type'])){
                    //     continue;
                    // }
                    $text = '';
                    foreach($attributes as $k => $val){
                        $data[$ke.'_'.$valu['type'].'_'.$valu['zrefnr_dvql.zsym']][$val] = $valu[$val]??'';
                        if($type_table == 1){
                            $text = $valu['zManufacturer.zsym'].'_'.$valu['zrefnr_dvql.zsym'];
                        }else{
                            $text = $valu['zManufacturer.zsym'].'_'.$valu['zrefnr_dvql.zsym'].'_'.$valu['type'];
                        }
                    }
                    $data_items[$ke.'_'.$valu['type'].'_'.$valu['zrefnr_dvql.zsym']] = $text;
                    $data[$ke.'_'.$valu['type'].'_'.$valu['zrefnr_dvql.zsym']]['unique'] = $text;
                }
            }

            // unset array duplicate
            $data_items = array_unique($data_items);
            $key_check = [];
            // get key array no duplicate
            foreach($data_items as $key => $val){
                $key_check[$key] = $key;
            }
            $data_old = $data;
            // foreach get data item_1 new
            foreach($data as $key => $val){
                // if $key equals the unique key, get data normal
                if(!empty($key_check[$key])){
                    foreach($attributes as $bv){
                        $items_sync[$key][$bv] = $val[$bv]??'';
                    }
                    $items_sync[$key]['unique'] = $val['unique']??'';
                    if( array_key_exists('zresultEnd_chk', $val) && $val['zresultEnd_chk'] != '' ){
                        $items_sync[$key]['achieved'] = 1;
                    }else{
                        $items_sync[$key]['not_achieved'] = 1;
                    }
                }else{
                    // If it does not match the unique key, it will find the record with the same unique and add the number
                    $unique = $data_old[$key]['unique'];
                    $result = @$data_old[$key]['zresultEnd_chk'] ? 'Đạt' : '';
                    foreach($items_sync as $key_2 => $it){
                        if($it['unique'] == $unique){
                            if($result == 'Đạt'){
                                if(!empty($it['achieved'])){
                                    $items_sync[$key_2]['achieved'] = $it['achieved'] + 1;
                                }else{
                                    $items_sync[$key_2]['achieved'] = 1;
                                }
                            }else{
                                if(!empty($it['not_achieved'])){
                                    $items_sync[$key_2]['not_achieved'] = $it['not_achieved'] + 1;
                                }else{
                                    $items_sync[$key_2]['not_achieved'] = 1;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $items_sync;
    }
}

if (!function_exists('getDataRelayFailureStatistics')) {
    function getDataRelayFailureStatistics($list_data, $table_value, $spreadsheet, $type, $name_sheet)
    {
        $data = [];
        foreach($list_data as $key => $value){
            if(isset($value['zrolereplace']) && $value['zrolereplace'] == '' && $type == 'zrolereplace'){
                continue;
            }
            if (empty($data[$value[$type]])) {
                $data[$value[$type]] = [
                    'total' => 0,
                    $type => $value[$type],
                ];
            }
            $data[$value[$type]]['total'] += 1;
        }
        // fill data sheet
        $table = ['A','B','C'];
        $number = 0;
        $colum_key = 4;
        foreach($data as $key => $value){
            $number++;
            $colum_key = $number + 4;
            foreach($table_value as $k => $val){
                if($val == 'number'){
                    $data_value = $number;
                }else{
                    $data_value = $value[$val]??'';
                }
                $spreadsheet->setActiveSheetIndexByName($name_sheet)->getCell($table[$k] . ($colum_key))->setValue($data_value);
            }
        }
    }
}

if (!function_exists('figuresExperimentalStatisticsData')) {
    function figuresExperimentalStatisticsData($request)
    {
        if (!empty($request['from']) && !empty($request['to']) && $request['to'] < $request['from']) {
            return [
                'error' => ['Thời gian kết thúc phải lớn hơn hoặc bằng thời gian bắt đầu']
            ];
        }
        $whereClauseBase = '';
        $whereClauseBase .= "zrefnr_dvql.zsym IS NOT NULL AND zlaboratoryDate IS NOT NULL AND class.zetc_type = 1";
        if (!empty($request['ztestType_ids'])) {
            $whereClauseBase .= " AND ztestType IN (".join(',', $request['ztestType_ids']).")";
        }
        if(!empty($request['from'])) {
            $from = strtotime($request['from'].'T00:00');
            $whereClauseBase .= " AND zlaboratoryDate >= " . $from . "";
        }
        if(!empty($request['to'])) {
            $to = strtotime($request['to'].'T23:59');
            $whereClauseBase .= " AND zlaboratoryDate <= " . $to . "";
        }
        $attributes = config('attributes.arr_figures_experimental');
        $reports = [];
        for ( $i = 0; $i < 4; $i++) {
            $whereClause = '';
            $whereClause .= $whereClauseBase;
            if($i == 0){
                $whereClause .= " AND zExperimenter.dept = 1000001 ";
            }else{
                $whereClause .= " AND zExperimenter{$i}.dept = 1000001 ";
            }
            $report = getDataFromService('doSelect', 'nr', $attributes, $whereClause, 150);
            $reports = array_merge($reports, $report);
        }
        if(count($reports) < 1){
            $data['error'] = 'Không có dữ liệu';
        }
        $data['dataArr'] = $reports;
        return $data;
    }
}

if( !function_exists('getObjectTypeRelay') ){
    function getObjectTypeRelay($type){
        $obj = '';
        switch ($type) {
            case '1. BBTN F87G':
                $obj = 'zRL1';
                break;
            case '2. BBTN F87T':
                $obj = 'zRL2';
                break;
            case '3. BBTN F87B-M':
                $obj = 'zRL3';
                break;
            case '4. BBTN F87B-U':
                $obj = 'zRL4';
                break;
            case '5. BBTN F87B-S':
                $obj = 'zRL5';
                break;
            case '6. BBTN F87R':
                $obj = 'zRL6';
                break;
            case '7. BBTN F87L':
                $obj = 'zRL7';
                break;
            case '8. BBTN F21':
                $obj = 'zRL8';
                break;
            case '9. BBTN F67':
                $obj = 'zRL9';
                break;
            case '10. BBTN F50/51':
            case '14. BBTN F50/BCU - F50/F81/BCU':
                $obj = 'zRL1014';
                break;
            case '11. BBTN F25/79':
                $obj = 'zRL11';
                break;
            case '12. BBTN F27/59 F81':
            case '13. BBTN F50/F81':
                $obj = 'zRL13';
                break;
            case '16. BBTN F81/BCU':
            case '15. BBTN F50/F81/BCU':
                $obj = 'zRL16';
                break;
            case '17. BBTN F50/F81/BCU':
                $obj = 'zRL15_17';
                break;
            case '18. BBTN F50BF':
                $obj = 'zRL18';
                break;
            case '19. BBTN F25/79/50BF':
                $obj = 'zRL19';
                break;
            case '20. BBTN F32':
                $obj = 'zRL20';
                break;
            case '21. BBTN F64R':
                $obj = 'zRL21';
                break;
            case '22. BBTN F90':
                $obj = 'zRL22';
                break;
            case '23. BBTN FR':
                $obj = 'zRL23';
                break;
            case '25. BBTN F67Ns':
                $obj = 'zRL25';
                break;
            case '27. BBTN F87':
            case '26. BBTN BCU':
                $obj = 'zRL26';
                break;
            case '28. BBTN F50_F67Ns_81_BCU':
            case '29. BBTN F50_F67Ns_BCU':
                $obj = 'zRL2829';
                break;
            case '30. BM F50_F67Ns':
                $obj = 'zRL58';
                break;
            case '31. Hệ thống mạch ngăn lộ':
                $obj = 'zRL28';
                break;
            case '32. Hệ thống mạch ngăn MBA':
                $obj = 'zRL29';
                break;
            case '33. Hệ thống mạch Scada':
                $obj = 'zRL30';
                break;
            case '34. Hệ thống mạch máy phát điện':
                $obj = 'zRL31';
                break;
            case '35. Hệ thống mạch PLC':
                $obj = 'zRL32';
                break;
            case '36. Hệ thống mạch kích từ':
                $obj = 'zRL33';
                break;
            case '37. BBTN F86':
                $obj = 'zRL34';
                break;
            case '38. BBTN F74':
                $obj = 'zRL35';
                break;
            case '39. BBTN RLTG':
                $obj = 'zRL36';
                break;
            case '40. Khóa chuyển mạch':
                $obj = 'zRL37';
                break;
            case '41. Khối đèn tín hiệu':
                $obj = 'zRL38';
                break;
            case '42. Nút bấm':
                $obj = 'zRL39';
                break;
            case '43. Tủ nạp':
                $obj = 'zRL40';
                break;
            case '44. Giàn ắc quy':
                $obj = 'zRL41';
                break;
            case '45. Cầu chỉnh lưu':
                $obj = 'zRL42';
                break;
            case '46. Hợp bộ điều khiển hệ thống kích từ':
                $obj = 'zRL43';
                break;
            case '47. Hệ thống PLC':
                $obj = 'zRL44';
                break;
            case '48. Volmet, Ampemeter, Varmeter, Wattmeter':
                $obj = 'zRL45';
                break;
            case '49. Multimeter':
                $obj = 'zRL46';
                break;
            case '50. Đồng hồ chỉ nấc phân áp':
                $obj = 'zRL47';
                break;
            case '51. Đồng hồ nhiệt độ dầu':
                $obj = 'zRL48';
                break;
            case '52. Đồng hồ nhiệt độ cuộn dây':
                $obj = 'zRL49';
                break;
            case '53. Transducer':
                $obj = 'zRL50';
                break;
            case '54. Khởi động tổ máy':
                $obj = 'zRL51';
                break;
            case '55. Đặc tính P-Q':
                $obj = 'zRL52';
                break;
            case '56. Đo chất lượng điện năng':
                $obj = 'zRL53';
                break;
            case '57. Thử nghiệm ngắn mạch, dưới tải':
                $obj = 'zRL54';
                break;
            case '58. Kiểm soát trước khi đóng điện':
                $obj = 'zRL55';
                break;
            case '59. Đồng vị pha':
                $obj = 'zRL56';
                break;
            default:
                break;
        }
        return $obj;
    }
}

if( !function_exists('arrayTypeRelay') ){
    function arrayTypeRelay(){
        return [
            '1. BBTN F87G',
            '2. BBTN F87T',
            '3. BBTN F87B-M',
            '4. BBTN F87B-U',
            '5. BBTN F87B-S',
            '6. BBTN F87R',
            '7. BBTN F87L',
            '8. BBTN F21',
            '9. BBTN F67',
            '10. BBTN F50/51',
            '11. BBTN F25/79',
            '12. BBTN F27/59 F81',
            '13. BBTN F50/F81',
            '14. BBTN F50/BCU - F50/F81/BCU',
            '15. BBTN F50/F81/BCU',
            '16. BBTN F81/BCU',
            '17. BBTN F50/F81/BCU',
            '18. BBTN F50BF',
            '19. BBTN F25/79/50BF',
            '20. BBTN F32',
            '21. BBTN F64R',
            '22. BBTN F90',
            '23. BBTN FR',
            '25. BBTN F67Ns',
            '26. BBTN BCU',
            '27. BBTN F87',
            '28. BBTN F50_F67Ns_81_BCU',
            '29. BBTN F50_F67Ns_BCU',
            '30. BM F50_F67Ns',
            '31. Hệ thống mạch ngăn lộ',
            '32. Hệ thống mạch ngăn MBA',
            '33. Hệ thống mạch Scada',
            '34. Hệ thống mạch máy phát điện',
            '35. Hệ thống mạch PLC',
            '36. Hệ thống mạch kích từ',
            '37. BBTN F86',
            '38. BBTN F74',
            '39. BBTN RLTG',
            '40. Khóa chuyển mạch',
            '41. Khối đèn tín hiệu',
            '42. Nút bấm',
            '43. Tủ nạp',
            '44. Giàn ắc quy',
            '45. Cầu chỉnh lưu',
            '46. Hợp bộ điều khiển hệ thống kích từ',
            '47. Hệ thống PLC',
            '48. Volmet, Ampemeter, Varmeter, Wattmeter',
            '49. Multimeter',
            '50. Đồng hồ chỉ nấc phân áp',
            '51. Đồng hồ nhiệt độ dầu',
            '52. Đồng hồ nhiệt độ cuộn dây',
            '53. Transducer',
            '54. Khởi động tổ máy',
            '55. Đặc tính P-Q',
            '56. Đo chất lượng điện năng',
            '57. Thử nghiệm ngắn mạch, dưới tải',
            '58. Kiểm soát trước khi đóng điện',
            '59. Đồng vị pha'
        ];
    }
}

if( !function_exists('arrayProtectionRelay') ){
    function arrayProtectionRelay(){
        return [
            'Rơ le bảo vệ - BCU',
            'Rơ le bảo vệ - F21',
            'Rơ le bảo vệ - F25/79',
            'Rơ le bảo vệ - F25/79/50BF',
            'Rơ le bảo vệ - F27/59',
            'Rơ le bảo vệ - F32',
            'Rơ le bảo vệ - F50/51',
            'Rơ le bảo vệ - F50/67Ns',
            'Rơ le bảo vệ - F50/67Ns/81/BCU',
            'Rơ le bảo vệ - F50/67NS/BCU',
            'Rơ le bảo vệ - F50/BCU',
            'Rơ le bảo vệ - F50/F81',
            'Rơ le bảo vệ - F50/F81/BCU',
            'Rơ le bảo vệ - F50BF',
            'Rơ le bảo vệ - F64F',
            'Rơ le bảo vệ - F67',
            'Rơ le bảo vệ - F67Ns',
            'Rơ le bảo vệ - F81',
            'Rơ le bảo vệ - F81/BCU',
            'Rơ le bảo vệ - F87',
            'Rơ le bảo vệ - F87B-M',
            'Rơ le bảo vệ - F87B-S',
            'Rơ le bảo vệ - F87B-U',
            'Rơ le bảo vệ - F87G',
            'Rơ le bảo vệ - F87L',
            'Rơ le bảo vệ - F87R',
            'Rơ le bảo vệ - F87T',
            'Rơ le bảo vệ - F90',
            'Rơ le bảo vệ - FL',
            'Rơ le bảo vệ - FR',
        ];
    }
}

// get Oil quality report
if( !function_exists(' getDataOilQualityReport') ) {
    function getDataOilQualityReport($request)
    {
        $whereClause = "class.zetc_type = 1 AND zCI_Device.name IS NOT NULL AND zsamplingDate IS NOT NULL AND delete_flag = 0";
        $arrType = $request['arrType'];
        // filter type
        foreach ($arrType as $key => $val) {
            if ($key == 0) {
                $whereClause .= " AND class.type IN (";
            }
            $whereClause .= "'" . $val . "'";
            if ($key < count($arrType) - 1) {
                $whereClause .= ",";
            } else {
                $whereClause .= ")";
            }
        }
        if (!empty($request['from'])) {
            $from = strtotime($request['from'] . '00:00');
            $whereClause .= " AND zsamplingDate >= " . $from . "";
        }
        if (!empty($request['to'])) {
            $to = strtotime($request['to'] . '23:59');
            $whereClause .= " AND zsamplingDate <= " . $to . "";
        }
        if (!empty($request['dvqls'])) {
            $whereClause .= " AND zrefnr_dvql IN (" . join(',', $request['dvqls']) . ")";
        }
        if (!empty($request['devices_name'])) {
            foreach ($request['devices_name'] as $key => $val) {
                if ($key == 0) {
                    $whereClause .= " AND zCI_Device.class.type IN (";
                }
                $whereClause .= "'" . $val . "'";
                if ($key < count($request['devices_name']) - 1) {
                    $whereClause .= ",";
                } else {
                    $whereClause .= ")";
                }
            }
        }
        if (!empty($request['listDeviceName'])) {
            foreach ($request['listDeviceName'] as $key => $val) {
                if ($key == 0) {
                    $whereClause .= " AND zCI_Device.name IN (";
                }
                $whereClause .= "'" . $val . "'";
                if ($key < count($request['listDeviceName']) - 1) {
                    $whereClause .= ",";
                } else {
                    $whereClause .= ")";
                }
            }
        }
        $attr = config('attributes.arr_oil_quality_report_data');
        $reports = getAllDataFromService('nr', $attr, $whereClause);
        $attr = config('attributes.arr_oil_quality_report_data_assoc');
        foreach ($reports as $key => $value) {
            $where = "id = U'" . $value['id'] . "'";
            $data_obj = getDataFromService('doSelect', 'zHD', $attr, $where, 150);
            if (!empty($value['zsamplingDate'])) {
                $reports[$key]['year'] = date('Y', $value['zsamplingDate']);
                $reports[$key]['zsamplingDate_sync'] = date('d-m-Y', $value['zsamplingDate']);
            }
            foreach ($attr as $val) {
                $reports[$key][$val] = $data_obj[0][$val] ?? '';
            }
        }
        sortData($reports, 'asc', 'zsamplingDate');
        return $reports;
    }
}
// Report
if (!function_exists('getDeviceByType')) {
    function getDeviceByType($types)
    {
        $arrType = [];
        if (in_array(config('constant.report.device.may_cat'), $types)) {
            $arrType = array_merge($arrType, getArrayDevice(config('constant.report.device.may_cat')));
        }
        if (in_array(config('constant.report.device.oltc'), $types)) {
            $arrType = array_merge($arrType, getArrayDevice(config('constant.report.device.oltc')));
        }
        if (in_array(config('constant.report.device.may_bien_ap'), $types)) {
            $arrType = array_merge($arrType, getArrayDevice(config('constant.report.device.may_bien_ap')));
        }
        return $arrType;
    }
}
// Device Array
if (!function_exists('getArrayDevice')) {
    function getArrayDevice($type)
    {
        $devices = [];
        switch ($type) {
            case config('constant.report.device.may_cat'):
                $devices = [
                    'Máy cắt', 'Máy cắt hạ thế', 'Máy cắt hóa'
                ];
                break;
            case config('constant.report.device.oltc'):
                $devices = [
                    'OLTC'
                ];
                break;
            case config('constant.report.device.may_bien_ap'):
                $devices = [
                    'Máy biến áp', 'Máy biến áp phân phối', 'Thùng dầu chính MBA - Máy biến áp', 'Thùng dầu chính MBA - MBA Hóa'
                ];
                break;
            default:
                break;
        }
        return $devices;
    }
}


// get data experimental by region report
if( !function_exists('getDataExperimentalByRegionReport') ){
    function getDataExperimentalByRegionReport($request){
        $data = [];
        $startDate = $request->startDate;
        $endDate = $request->endDate;

        if( !is_null($startDate) && !is_null($endDate) && $startDate > $endDate) {
            return [
                'error' => ['Thời gian kết thúc phải lớn hơn hoặc bằng thời gian bắt đầu!']
            ];
        }

        $arrayTypes = [
            'Biên Bản Thử Nghiệm Khí Hoà Tan Trong Dầu Cách Điện',
            'Biên Bản Thí Nghiệm Dầu Cách Điện - OLTC',
            'Biên Bản Thí Nghiệm Dầu Cách Điện - Dầu máy biến áp',
            'Biên Bản Thí Nghiệm Dầu Cách Điện - Dầu téc',
            'Biên Bản Thử Nghiệm Khí SF6',
            'Biên bản thử nghiệm Furan Methanol - BM06',
        ];

        foreach ($arrayTypes as $key => $val) {
            if ($key == 0) {
                $whereClause = " class.type IN (";
            }
            $whereClause .= "'" . $val . "'";
            if ($key < count($arrayTypes) - 1) {
                $whereClause .= ",";
            } else {
                $whereClause .= ")";
            }
        }

        // Get report type
        $arrayDeviceType = $request->device;
        if (empty($arrayDeviceType)) {
            $arrayDeviceType = [
                config('constant.report.device.may_cat'),
                config('constant.report.device.oltc'),
                config('constant.report.device.may_bien_ap'),
            ];
        }
        $deviceTypes = getDeviceByType($arrayDeviceType);

        foreach ($deviceTypes as $key => $val) {
            if ($key == 0) {
                $whereClause .= " AND zCI_Device.class.type IN (";
            }
            $whereClause .= "'" . $val . "'";
            if ($key < count($deviceTypes) - 1) {
                $whereClause .= ",";
            } else {
                $whereClause .= ")";
            }
        }

        if( $request->dvqls ){
            $whereClause .= " AND zrefnr_dvql IN(". join(',', $request->dvqls) .")";
        }

        if( !is_null($startDate) ){
            $whereClause .= " AND zsamplingDate >= " . strtotime($startDate);
        }
        if( !is_null($endDate) ){
            $whereClause .= " AND zsamplingDate < " . strtotime("+1 day", strtotime($endDate));
        }

        $whereClause .=" AND class.zetc_type = 1 ";
        $attributes = [
            'id',
            'class.type',
            'zCI_Device.class.type',
            'zCI_Device.zArea.zsym',
            'zCI_Device.zrefnr_td.zsym',
            'zCI_Device.zrefnr_nl.zsym',
            'zsamplingDate',
            'zresultEnd',
            'zlaboratoryDate'
        ];
        $data = getAllDataFromService('nr', $attributes, $whereClause);
        if( empty($data) ){
            return [
                'error' => ['Không tìm thấy dữ liệu']
            ];
        }
        foreach($data as $index => $report){
            if( $request->areas_name && ( empty($report['zCI_Device.zArea.zsym']) || !in_array(@$report['zCI_Device.zArea.zsym'], $request->areas_name) ) ){
                unset($data[$index]);
                continue;
            }
            // format zlaboratoryDate, zsamplingDate timestamp to date
            if(array_key_exists('zsamplingDate', $report)){
                $data[$index]['zsamplingDate'] = Carbon::createFromTimestamp($report['zsamplingDate'])->format('d/m/Y');
            }
            if(array_key_exists('zlaboratoryDate', $report)){
                $data[$index]['zlaboratoryDate'] = Carbon::createFromTimestamp($report['zlaboratoryDate'])->format('d/m/Y');
            }
        }
        return array_values($data);
    }
}

// get data statistical periodically petrochemical report
if( !function_exists('getDataStatisticalPeriodicallyPetrochemicalReport') )
{
    function getDataStatisticalPeriodicallyPetrochemicalReport($request){
        $startDate = $request->startDate;
        $endDate = $request->endDate;
        if( !is_null($startDate) && !is_null($endDate) && $startDate > $endDate) {
            return [
                'error' => ['Thời gian kết thúc phải lớn hơn hoặc bằng thời gian bắt đầu!']
            ];
        }

        $arrayTypes = [
            'Biên Bản Thử Nghiệm Khí Hoà Tan Trong Dầu Cách Điện',
            'Biên Bản Thí Nghiệm Dầu Cách Điện - OLTC',
            'Biên Bản Thí Nghiệm Dầu Cách Điện - Dầu máy biến áp',
            'Biên Bản Thí Nghiệm Dầu Cách Điện - Dầu téc',
            'Biên Bản Thử Nghiệm Khí SF6',
        ];

        foreach ($arrayTypes as $key => $val) {
            if ($key == 0) {
                $whereClause = " class.type IN (";
            }
            $whereClause .= "'" . $val . "'";
            if ($key < count($arrayTypes) - 1) {
                $whereClause .= ",";
            } else {
                $whereClause .= ")";
            }
        }

        // Get report type
        $arrayDeviceType = $request->device;
        if (empty($arrayDeviceType)) {
            $arrayDeviceType = [
                config('constant.report.device.may_cat'),
                config('constant.report.device.oltc'),
                config('constant.report.device.may_bien_ap'),
            ];
        }
        $deviceTypes = getDeviceByType($arrayDeviceType);
        foreach ($deviceTypes as $key => $val) {
            if ($key == 0) {
                $whereClause .= " AND zCI_Device.class.type IN (";
            }
            $whereClause .= "'" . $val . "'";
            if ($key < count($deviceTypes) - 1) {
                $whereClause .= ",";
            } else {
                $whereClause .= ")";
            }
        }

        if( $request->dvqls ){
            $whereClause .= " AND zrefnr_dvql IN(".join(',', $request->dvqls).")";
        }

        if( !is_null($startDate) ){
            $whereClause .= " AND zsamplingDate >= " . strtotime($startDate);
        }

        if( !is_null($endDate) ){
            $whereClause .= " AND zsamplingDate < " . strtotime('+1 day', strtotime($endDate));
        }

        $whereClause .= " AND class.zetc_type = 1 ";

        $attributes = config('attributes.attribute_statistical_periodically_petrochemical');
        $reports = getDataFromService('doSelect', 'nr', $attributes, $whereClause, 150);

        if(empty($reports)){
            return [
                'error' => ['Không tìm thấy dữ liệu']
            ];
        }
        // get assoc data object zHD
        foreach($reports as $index => $report){
            $attsChild = getChildAttributeStatisticalPeriodicallyPetrochemicalReport($report['class.type']);
            $reports[$index]['pass'] =  0;
            if( array_key_exists('no_attributes', $attsChild) ){
                if( array_key_exists('zresultEnd_chk', $report) ){
                    $reports[$index]['pass'] =  1;
                }
            }else{
                $whereClause = " id = U'". $report['id'] ."' ";
                $data = getDataFromService('doSelect', 'zHD', $attsChild, $whereClause);
                // count items need(getChildAttributeStatisticalPeriodicallyPetrochemicalReport())
                // to be checked and 1 total result(zresultEnd_chk) must be achieved
                // check length data return equal length attsChild + 1(data return has index hanlde_id)
                if( array_key_exists('zresultEnd_chk', $report) && count($data) == (count($attsChild) + 1)){
                    $reports[$index]['pass'] =  1;
                }
            }
        }
        // group by Area(attr zArea.zsym)
        $reports = collect($reports)->groupBy(function($item){
            return @$item['zCI_Device.zArea.zsym'];
        });
        $data = [];
        // group by zrefnr_td
        foreach($reports as $areaName => $collect){
            // format data each area, zrefnr_td, nl_name and group data class.type, zCI_Device.class.type
            $array = [];
            $array['area_name'] = $areaName;
            $reports[$areaName] = $collect->groupBy(function($item){
                return @$item['zrefnr_td.zsym'];
            });
            // group by zrefnr_nl
            foreach($reports[$areaName] as $powerStationName => $powerStationCollect){
                $array['td_name'] = $powerStationName;
                $reports[$areaName][$powerStationName] = $powerStationCollect->groupBy(function($item1){
                    return @$item1['zrefnr_nl.zsym'];
                });
                // group by class.type
                foreach($reports[$areaName][$powerStationName] as $classType => $collect1){
                    $array['nl_name'] = $classType;
                    $result = caculateDataStatisticalPeriodicallyPetrochemical($collect1);
                    $array = array_merge($array, $result);
                    $data[] = $array;
                }
            }
        }
        return $data;
    }
}

if( !function_exists('getChildAttributeStatisticalPeriodicallyPetrochemicalReport') ){
    function getChildAttributeStatisticalPeriodicallyPetrochemicalReport($type){
        $attrs = [];
        switch ($type) {
            case 'Biên Bản Thử Nghiệm Khí SF6':
                $attrs = config('attributes.attribute_statistical_periodically_petrochemical_check_sf6');
                break;
            case 'Biên Bản Thí Nghiệm Dầu Cách Điện - OLTC':
            case 'Biên Bản Thí Nghiệm Dầu Cách Điện - Dầu máy biến áp':
            case 'Biên Bản Thí Nghiệm Dầu Cách Điện - Dầu téc':
                $attrs = config('attributes.attribute_statistical_periodically_petrochemical_check_mba');
                break;
            case 'Biên Bản Thử Nghiệm Khí Hoà Tan Trong Dầu Cách Điện':
                $attrs = ['no_attributes' => 'no_attributes'];
                break;
            default:
                break;
        }
        return $attrs;
    }
}

if(!function_exists('caculateDataStatisticalPeriodicallyPetrochemical')){
    function caculateDataStatisticalPeriodicallyPetrochemical($collect){
        $deviceMBA = getArrayDevice(config('constant.report.device.may_bien_ap'));
        $deviceOLTC = getArrayDevice(config('constant.report.device.oltc'));
        $deviceMC = getArrayDevice(config('constant.report.device.may_cat'));
        $dcdMBA = $khtMBA = $dcdOLTC = $khtMBA = $khtOLTC = $sf6MC = [];
        // MBA
        foreach($deviceMBA as $val){
            array_push(
                $dcdMBA,
                'Biên Bản Thí Nghiệm Dầu Cách Điện - OLTC + '.$val,
                'Biên Bản Thí Nghiệm Dầu Cách Điện - Dầu máy biến áp + '.$val,
                'Biên Bản Thí Nghiệm Dầu Cách Điện - Dầu téc + '.$val,
            );
            array_push(
                $khtMBA,
                'Biên Bản Thử Nghiệm Khí Hoà Tan Trong Dầu Cách Điện + '.$val,
            );
        }
        // OLTC
        foreach($deviceOLTC as $val){
            array_push(
                $dcdOLTC,
                'Biên Bản Thí Nghiệm Dầu Cách Điện - OLTC + '.$val,
                'Biên Bản Thí Nghiệm Dầu Cách Điện - Dầu máy biến áp + '.$val,
                'Biên Bản Thí Nghiệm Dầu Cách Điện - Dầu téc + '.$val,
            );
            array_push(
                $khtOLTC,
                'Biên Bản Thử Nghiệm Khí Hoà Tan Trong Dầu Cách Điện + '.$val,
            );
        }
        // MC
        foreach($deviceMC as $val){
            array_push(
                $sf6MC,
                'Biên Bản Thử Nghiệm Khí SF6 + '.$val,
            );
        }
        $dataKeyCheck = [
            'dcd_mba' => $dcdMBA,
            'dcd_oltc' => $dcdOLTC,
            'kht_mba' => $khtMBA,
            'kht_oltc' => $khtOLTC,
            'sf6_mc' => $sf6MC,
            // KH chưa hiển thị
            // 'dcd_mbatd' => [
            //     'Biên Bản Thí Nghiệm Dầu Cách Điện - OLTC + Máy biến áp',
            //     'Biên Bản Thí Nghiệm Dầu Cách Điện - Dầu máy biến áp + Máy biến áp',
            //     'Biên Bản Thí Nghiệm Dầu Cách Điện - Dầu téc + Máy biến áp'
            // ]
        ];
        // init data
        $data =  initDataStatisticalPeriodicallyPetrochemical();
        // get array key to each map initDataStatisticalPeriodicallyPetrochemical
        $keys = array_keys($dataKeyCheck);

        foreach($collect as $item){
            // concat string check type
            $type = $item['class.type'] . ' + ' .  $item['zCI_Device.class.type'];
            foreach($keys as $key){
                // check case in $dataKeyCheck
                if( in_array($type, $dataKeyCheck[$key]) ){
                    // add value 0 if null
                    if($data["{$key}_achieved"] == null){
                        $data["{$key}_achieved"] = 0;
                    }
                    if($data["{$key}_not_achieved"] == null){
                        $data["{$key}_not_achieved"] = 0;
                    }
                    // has index key = zresultEnd_chk => achieved(else)
                    if( $item['pass'] == 1 ){
                        $data["{$key}_achieved"] += 1;
                    }else{
                        $data["{$key}_not_achieved"] += 1;
                    }
                }
            }
        }
        return $data;
    }
}

if( !function_exists('initDataStatisticalPeriodicallyPetrochemical') ){
    function initDataStatisticalPeriodicallyPetrochemical(){
        return [
            // Dầu cách điện(MBA)
            'dcd_mba_achieved' => null,
            'dcd_mba_not_achieved' => null,
            // Dầu cách điện(OLTC)
            'dcd_oltc_achieved' => null,
            'dcd_oltc_not_achieved' => null,
            // Khí hòa tan trong dầu(MBA)
            'kht_mba_achieved' => null,
            'kht_mba_not_achieved' => null,
            // Khí hòa tan trong dầu(OLTC)
            'kht_oltc_achieved' => null,
            'kht_oltc_not_achieved' => null,
            // Dầu cách điện(Máy cắt)
            'sf6_mc_achieved' => null,
            'sf6_mc_not_achieved' => null,
            // Dầu cách điện(Máy biến áp TD)
            // 'dcd_mbatd_achieved' => null,
            // 'dcd_mbatd_not_achieved' => null,
        ];
    }
}

if( !function_exists('getDataDeviceReport') ){
    function getDataDeviceReport($request){
        $startYear = $request->startYear;
        $endYear = $request->endYear;

        if($startYear && $endYear && $startYear > $endYear) {
            return [
                'error' => ['Năm kết thúc phải lớn hơn hoặc bằng năm bắt đầu']
            ];
        }
        // Get report type
        $arrayDeviceType = [$request->device];
        if (is_null($request->device)) {
            $arrayDeviceType = [
                config('constant.report.device.may_cat'),
                config('constant.report.device.oltc'),
                config('constant.report.device.may_bien_ap'),
            ];
        }
        $deviceTypes = getDeviceByType($arrayDeviceType);
        foreach ($deviceTypes as $key => $val) {
            if ($key == 0) {
                $whereClause = " class.type IN (";
            }
            $whereClause .= "'". $val ."'";
            if ($key < count($deviceTypes) - 1) {
                $whereClause .= ",";
            } else {
                $whereClause .= ")";
            }
        }
        // get id of object  zYear_of_Manafacture by year
        $arrayManufactures = [];
        if($startYear && !$endYear){
            $endYear = (int) date('Y');
        }
        if(!$startYear && $endYear){
            $startYear = 1970;
        }
        for($i = $startYear; $i <= $endYear; $i++){
            $whereClauseManafacture = " zsym = '".$i."'";
            $data = getDataFromService('doSelect', 'zYear_of_Manafacture', ['zsym', 'id'], $whereClauseManafacture);
            if(!empty($data)){
                array_push($arrayManufactures, $data['id']);
            }
        }
        foreach ($arrayManufactures as $key => $val) {
            if ($key == 0) {
                $whereClause .= " AND zYear_of_Manafacture IN (";
            }
            $whereClause .= "'". $val ."'";
            if ($key < count($arrayManufactures) - 1) {
                $whereClause .= ",";
            } else {
                $whereClause .= ")";
            }
        }
        $whereClause .= " AND zManufacturer.zsym IS NOT NULL AND zYear_of_Manafacture.zsym IS NOT NULL "
                        ." AND class.zetc_type != 1 ";

        $attributes = [
            'id',
            'zManufacturer.zsym',
            'zYear_of_Manafacture.zsym',
            'class.type'
        ];
        $reports = getAllDataFromService('nr', $attributes, $whereClause);

        if(empty($reports)){
            return [
                'error' => ['Không tìm thấy dữ liệu']
            ];
        }

        $reports = collect($reports)->sortByDesc(function($item){
            return $item['zYear_of_Manafacture.zsym'];
        });
        // group by Manufacturer report for sheet chart data
        $groupByManufacturer = $reports->groupBy(function($item){
            return $item['zManufacturer.zsym'];
        });
        // group year of manafacture
        $groupByManafactureYear = $reports->groupBy(function($item){
            return $item['zYear_of_Manafacture.zsym'];
        });
        // loop year and group manufacturer
        foreach($groupByManafactureYear as $year => $collect){
            $groupByManafactureYear[$year] = $collect->groupBy(function($item){
                return $item['zManufacturer.zsym'];
            });
            foreach($groupByManafactureYear[$year] as $manufacturerName => $manufacturerCollect){
                $groupByManafactureYear[$year][$manufacturerName] = countDeviceData($manufacturerCollect);
            }
        }
        return [
            'groupByManafactureYear' => $groupByManafactureYear,
            'groupByManufacturer' => $groupByManufacturer,
        ];
    }
}

if( !function_exists('countDeviceData') ){
    function countDeviceData($collectData){
        $data = [
            'Máy biến áp' => 0,
            'OLTC' => 0,
            'Máy cắt' => 0,
        ];
        $deviceMBA = getArrayDevice(config('constant.report.device.may_bien_ap'));
        $deviceOLTC = getArrayDevice(config('constant.report.device.oltc'));
        $deviceMC = getArrayDevice(config('constant.report.device.may_cat'));
        foreach($collectData as $item) {
            if( in_array($item['class.type'], $deviceMBA) ){
                $data['Máy biến áp'] += 1;
            }
            if( in_array($item['class.type'], $deviceOLTC) ){
                $data['OLTC'] += 1;
            }
            if( in_array($item['class.type'], $deviceMC) ){
                $data['Máy cắt'] += 1;
            }
        }
        return collect($data);
    }
}

if( !function_exists('checkTypeMachines') ){
    function checkTypeMachines($request, $title_report, $type_data, $functionGetData = [], $functionWriteExcel = []){
        switch ($request->classType) {
            case '10.2. BBTN Máy cắt 1 buồng cắt 3 bộ truyền động':
                $data['title_function'] = $type_data[0];
                $data['type'] = 3;
                $data['title_report'] = $title_report.' - máy cắt 3 pha 1 bộ truyền động';
                $data['function_get_data'] = $functionGetData[0]??'';
                $data['function_write_excel'] = $functionWriteExcel[0]??'';
                $data['in_helper'] = true;
                break;
            case '10.1 BBTN Máy cắt 1 buồng cắt 1 bộ truyền động':
                $data['title_function'] = $type_data[1];
                $data['type'] = 1;
                $data['title_report'] = $title_report.' - Máy cắt 1 pha 1 bộ truyền động 1 buồng cắt';
                $data['function_get_data'] = $functionGetData[1]??'';
                $data['function_write_excel'] = $functionWriteExcel[1]??'';
                $data['in_helper'] = true;
                break;
            default:
                $data['title_function'] = $type_data[2];
                $data['type'] = 2;
                $data['title_report'] = $title_report.' - Máy cắt 1 pha 1 bộ truyền động 2 buồng cắt';
                $data['function_get_data'] = $functionGetData[2]??'';
                $data['function_write_excel'] = $functionWriteExcel[2]??'';
                $data['in_helper'] = false;
                break;
        }
        return $data;
    }
}

if( !function_exists('fillDataChart') ){
    function fillDataChart($data ,$spreadsheet, $nameSheet, $tableValue, $valueTitle = '', $tableTitle = '', $flagSTT = true, $optionalEndPoint = null){
        $table = range("A", "Z");
        $startIndexData = 4;
        $number = 1;
        // set title chart
        if($valueTitle != ''){
            if(empty($tableTitle)){
                $tableTitle = range("C", "Z");
            }
            foreach ($valueTitle as $k => $val){
                $spreadsheet->setActiveSheetIndexByName($nameSheet)->getCell($tableTitle[$k].'3')->setValue($val??'');
            }
        }
        foreach ($data as $key => $value){
            $value['date_sync'] = $key;
            $value['number'] = $number;
            if(!$flagSTT){
                $value['number'] = '';
            }
            foreach ($tableValue as $k => $val){
                if( !is_null($optionalEndPoint) ){
                    $spreadsheet->setActiveSheetIndexByName($nameSheet)->getCell($table[$k].$startIndexData)->setValue(convertValueDataFromCA($value[$val] ?? '', $optionalEndPoint));
                }else{
                    $spreadsheet->setActiveSheetIndexByName($nameSheet)->getCell($table[$k].$startIndexData)->setValue($value[$val]??'');
                }
            }
            $startIndexData++;
            $number++;
        }
    }
}
// get all data from service buy obj and whereClause
if( !function_exists('getAllDataFromService') ){
    function getAllDataFromService($object, $attrs = [], $whereClause = '')
    {
        // Get Url Web Service
        $service = new CAWebServices(env('CA_WSDL_URL'));
        // Get User Login Session
        $user = session()->get(env('AUTH_SESSION_KEY'));
        $ssid = @$user['ssid'];
        if( is_null($ssid) ){
            $ssid = $service->getSessionID(env('CA_USERNAME'), env('CA_PASSWORD'));
        }
        // Get list handle by $object, $whereClause
        $listHandle = $service->getListHandle($ssid, $object, $whereClause);
        // Get all items by $listHandle
        $items = $service->getAllItemsFromListHandle($ssid, $listHandle, $attrs);
        $service->freeListHandles($ssid, $listHandle->listHandle);
        return $items;
    }
}

if( !function_exists('fillSearchRequestToExcel') ){
    function fillSearchRequestToExcel($sheetActive, $request, $columns = [], $inputs = [], $numberRowInsert = 4, $flagSheetChart = false)
    {
        // convert request obj when $request is array
        $requestObj = new \Illuminate\Http\Request();
        if( is_array($request) ){
            $request = $requestObj->replace($request);
        }
        if( is_null($request) ){
            $request = $requestObj->replace([]);
        }
        // insert new row in top
        if( !$flagSheetChart ){
            $sheetActive->insertNewRowBefore(1, $numberRowInsert);
        }
        // array columns display
        $columns = !empty($columns) ? $columns : ['C', 'D', 'E', 'F', 'G'];
        // inputs request key: label => value: name request
        $inputs = !empty($inputs) ? $inputs : [
            'Ngày bắt đầu: ' => 'from',
            'Ngày kết thúc: ' => 'to',
            'Thiết bị: ' => 'equipment',
            'Đơn vị quản lý: ' => 'dvql_id',
            'Trạm/Nhà máy: ' => 'td_id',
            'Ngăn lộ/Hệ thống: ' => 'nl_id',
            'Kiểu thiết bị: ' => 'device_type',
            'Số chế tạo: ' => 'manufacturing_number',
            'Vị trí lắp đặt: ' => 'installation_location',
            'Loại thiết bị: ' => 'devices',
            'Hãng sản xuất: ' => 'manufacturer',
            'Người thí nghiệm: ' => 'experimenter',
            'Chủng loại: ' => 'species',
        ];
        // fill to excel
        $indexRow = 1;
        $labels = array_keys($inputs);
        $inputs = array_values($inputs);
        $indexColumn = 0;
        foreach($inputs as $index => $input){
            // column date -> format d/m/y
            if( in_array($input, ['from', 'to', 'start_date', 'end_date', 'startDate', 'endDate', 'startTime', 'endTime', 'installation_time']) ){
                $value = $request->get($input) ? date('d/m/Y', strtotime($request->get($input))) : '';
                $sheetActive->getCell($columns[$indexColumn].$indexRow)->setValue($labels[$index]. $value );
            }
            else if( is_array($request->get($input)) ){
                $sheetActive->getCell($columns[$indexColumn].$indexRow)->setValue($labels[$index]. join(', ', $request->get($input)) );
            }else{
                $sheetActive->getCell($columns[$indexColumn].$indexRow)->setValue($labels[$index].$request->get($input, ''));
            }
            $indexColumn++;
            if(($index + 1) % count($columns) == 0){
                $indexRow++;
                $indexColumn = 0;
            }
        }
        // set with columns
        foreach($columns as $column){
            $sheetActive->getColumnDimension($column)->setWidth(40);
        }
        // apply style
        for($i = 1; $i <= $numberRowInsert; $i++){
            $sheetActive->getRowDimension($i)->setRowHeight(35);
        }

        $styleArray = array(
            'font'  => array(
                'size'  => 12,
                'bold' => true,
            ));
        $fontFamily = [
            'font' => [
                'name' => 'Times New Roman'
            ]
        ];
        $sheetActive->getStyle('A1:Z1000')->getAlignment()->setWrapText(true);
        $sheetActive->getStyle('A1:Z1000')->applyFromArray($fontFamily);
        $sheetActive->getStyle('A1:Z'.$numberRowInsert)->applyFromArray($styleArray);
        $sheetActive->getStyle('A1:Z'.$numberRowInsert)
                    ->getAlignment()->setWrapText(true)
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
    }
}
