<?php

namespace App\Console\Commands;

use App\Services\CAWebServices;
use GreenCape\Xml\Converter;
use Illuminate\Console\Command;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AutomationListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cmd:get_automation_list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get automation device list';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            Log::info('---------------------------------------------------------');
            Log::info('Start get automation device list');

            $service = new CAWebServices(env('CA_WSDL_URL'));
            $sessionId = $service->getSessionID(env('CA_USERNAME'), env('CA_PASSWORD'));

            $whereClause = "delete_flag = 0 AND class.zetc_type != 1";

            // Get device type of automation department
            $payload = [
                'sid' => $sessionId,
                'objectType' => 'grc',
                'whereClause' => 'zTDH = 1 AND zetc_type = 0',
                'maxRows' => 300,
                'attributes' => ['id', 'type']
            ];
            $resp = $service->callFunction('doSelect', $payload);
            $typeArr = [];
            if (!empty($resp)) {
                $parser = new Converter($resp->doSelectReturn);
                if (!isset($parser->data['UDSObjectList'][0])) {
                    $parser->data['UDSObjectList'] = [$parser->data['UDSObjectList']];
                }
                foreach ($parser->data['UDSObjectList'] as $obj) {
                    if (!$obj) {
                        continue;
                    }
                    $info = [];
                    foreach ($obj['UDSObject'][1]['Attributes'] as $attr) {
                        if ($attr['Attribute'][1]['AttrValue']) {
                            $info[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                        }
                    }
                    array_push($typeArr, $info);
                }
            }

            if (!empty($typeArr)) {
                foreach ($typeArr as $key => $val) {
                    if ($key == 0) {
                        $whereClause .= " AND class IN (";
                    }
                    $whereClause .= $val['id'];
                    if ($key < count($typeArr) - 1) {
                        $whereClause .= ",";
                    } else {
                        $whereClause .= ")";
                    }
                }
            }

            $listHandle = $service->getListHandle($sessionId, 'nr', $whereClause);
            Log::info('Get list handle');

            $items = [];
            $perPage = 1000;
            $currentPage = 1;
            if ($listHandle && @$listHandle->listLength > 0) {
                $currentPage = LengthAwarePaginator::resolveCurrentPage();
                $startIndex = ($currentPage - 1) * $perPage;
                $endIndex = ($startIndex + $perPage) - 1;
                if ($endIndex > $listHandle->listLength) {
                    $endIndex = (int)$listHandle->listLength - 1;
                }

                $payload = [
                    'sid' => $sessionId,
                    'listHandle' => $listHandle->listHandle,
                    'startIndex' => $startIndex,
                    'endIndex' => $endIndex,
                    'attributeNames' => [
                        'id',
                        'name',
                        'zArea',
                        'zArea.zsym',
                        'zrefnr_td.zsym',
                        'zrefnr_nl.zsym',
                        'zManufacturer',
                        'zManufacturer.zsym',
                        'class',
                        'class.type',
                        'serial_number',
                        'zYear_of_Manafacture',
                        'zYear_of_Manafacture.zsym',
                        'zCountry',
                        'zCountry.name',
                        'zCI_Device_Type',
                        'zCI_Device_Type.zsym',
                        'zCI_Device_Kind.zsym',
                    ]
                ];
                $resp = $service->callFunction('getListValues', $payload);
                $parser = new Converter($resp->getListValuesReturn);

                if (!isset($parser->data['UDSObjectList'][0])) {
                    $parser->data['UDSObjectList'] = [$parser->data['UDSObjectList']];
                }
                foreach ($parser->data['UDSObjectList'] as $obj) {
                    if (!$obj) {
                        Log::info('Failed key 1 ' . $obj);
                        continue;
                    }
                    $item = [];
                    $item['handle_id'] = $obj['UDSObject'][0]['Handle'];
                    foreach ($obj['UDSObject'][1]['Attributes'] as $attr) {
                        if ($attr['Attribute'][1]['AttrValue']) {
                            $item[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                        }
                    }
                    array_push($items, $item);
                }
            }
            Log::info('Get item');
            $data = new LengthAwarePaginator($items, $listHandle->listLength, $perPage, $currentPage, [
                'path' => route('admin.automation'),
                'query' => [],
            ]);
            Log::info('Get data');

            $devices = [];
            foreach ($data as $key => $val) {
                if ($key != 0 && $key % 10 == 0) {
                    sleep(10);
                }
                $device = getReportFromDevice($val);
                $payload = [
                    'sid' => $sessionId,
                    'objectType' => 'zETC_Device',
                    'whereClause' => "id = U'" . str_replace('nr:', '', $val['handle_id']) . "'",
                    'maxRows' => 10,
                    'attributes' => [
                        'zhedieuhanh.zsym',
                        'zSoftware.zsym',
                        'zversion',
                        'zIP',
                        'zTGLD',
                    ]
                ];
                $resp = $service->callFunction('doSelect', $payload);
                if (empty($resp)) {
                    $deviceItem['info'] = [];
                    Log::info('Failed key ' . $key);
                    continue;
                }
                $parser = new Converter($resp->doSelectReturn);
                if (!isset($parser->data['UDSObjectList'][0])) {
                    $parser->data['UDSObjectList'] = [$parser->data['UDSObjectList']];
                }
                foreach ($parser->data['UDSObjectList'] as $obj) {
                    if (!$obj) {
                        Log::info('Failed key 2 ' . $key);
                        continue;
                    }
                    $info = [];
                    $info['handle_id'] = $obj['UDSObject'][0]['Handle'];
                    foreach ($obj['UDSObject'][1]['Attributes'] as $attr) {
                        if ($attr['Attribute'][1]['AttrValue']) {
                            $info[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                        }
                    }
                }
                if (!empty($info)) {
                    $device['info'] = $info;
                }
                array_push($devices, $device);
                Log::info('Get info of key ' . $key);
            }

            Log::info('Finish get automation device list');
            Log::info('---------------------------------------------------------');

            Cache::forget('automation_index_list');
            Cache::forever('automation_index_list', $devices);
        } catch (\Exception $ex) {
            Log::error('Get automation device list: Fail | Reason: '.$ex->getMessage().'. '.$ex->getFile().':'.$ex->getLine());
        }
    }
}
