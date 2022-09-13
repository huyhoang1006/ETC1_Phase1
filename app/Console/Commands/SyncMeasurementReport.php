<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EquipmentUnderInspectionExport;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;
use Storage;
use File;
use Log;
use App\Services\CAWebServices;
use Exception;

class SyncMeasurementReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cmd:sync_measurement_report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        Log::info('---------------------------------------------------------');
        Log::info('Start get sync measurement report');

        // login CA
        $service = new CAWebServices(env('CA_WSDL_URL'));
        $sessionId = $service->getSessionID(env('CA_USERNAME'), env('CA_PASSWORD'));
        // get data
        $report = "class.type In('Máy biến điện áp','Máy biến dòng','Công tơ')";
        $request['type'] = $report;
        $request['attributes'] = ['id','class.type','name','zCI_Device_Type.zsym','zManufacturer.zsym','zrefnr_dvql.zsym'];
        $request['attributes_2'] = ['zkqkiemdinhsrel.zsym','zhankiemdinhdate'];
        $request['number'] = 4;
        $request['session_id'] = $sessionId;
        Log::info('Get item');
        try {
            // chết ở đây
            $items = getEquipmentUnderInspection($report, $request);
            Log::info('Get data');
            $items_3 = $items_2 = $items_1 = [];
            foreach($items as $key => $value){
                if($value['class.type'] == 'Công tơ'){
                    foreach($request['attributes'] as $bv){
                        $items_1[$key][$bv] = $value[$bv]??'';
                    }
                    for ($i=0; $i <= 5; $i++) {
                        $items_1[$key]['false_'.$i] = $value['false_'.$i]??'';
                    }
                    $name_type_1 = 'cong-to';
                }elseif($value['class.type'] == 'Máy biến điện áp'){
                    foreach($request['attributes'] as $bv){
                        $items_2[$key][$bv] = $value[$bv]??'';
                    }
                    for ($i=0; $i <= 5; $i++) {
                        $items_2[$key]['false_'.$i] = $value['false_'.$i]??'';
                    }
                    $name_type_2 = 'bien-ap-do-luong';
                }elseif($value['class.type'] == 'Máy biến dòng'){
                    foreach($request['attributes'] as $bv){
                        $items_3[$key][$bv] = $value[$bv]??'';
                    }
                    for ($i=0; $i <= 5; $i++) {
                        $items_3[$key]['false_'.$i] = $value['false_'.$i]??'';
                    }
                    $name_type_3 = 'bien-dong-do-luong';
                }
            }
            Log::info('Unset data dupicate and save as file excel');
            if (isset($name_type_1)) {
                saveFileSync($items_1, $name_type_1, $sessionId);

            }
            if (isset($name_type_2)) {
                saveFileSync($items_2, $name_type_2, $sessionId);

            }
            if (isset($name_type_3)) {
                saveFileSync($items_3, $name_type_3, $sessionId);

            }
            Log::info('Finish sync is complete');
            Log::info('---------------------------------------------------------');
            echo 'sync is complete';
        } catch(\Exception $ex){
            Log::error('Get get sync measurement report: Fail | Reason: '.$ex->getMessage().'. '.$ex->getFile().':'.$ex->getLine());
            echo 'Error sync is complete';
        }
    }
}
