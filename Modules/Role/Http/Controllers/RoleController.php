<?php

namespace Modules\Role\Http\Controllers;


use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\CAWebServices;

class RoleController extends Controller
{
    /**
     * Get equipment manufacturer statistics report
     *
     * @return Application|Factory|View
     */
    public function equipmentManufacturerStatisticsReport()
    {
        $types = getDataFromService('doSelect', 'grc', [
            'id',
            'type',
        ], "zetc_type = 0 AND type LIKE '%Rơ le bảo vệ%' AND delete_flag != 1", 100);
        $typeIdArr = [];
        foreach ($types as $type) {
            $typeIdArr[] = $type['id'];
        }

        $deviceTypes = collect(getDataFromService('doSelect', 'zCI_Device_Type', [], "zclass IN (" . implode(',', $typeIdArr) . ") AND active_flag = 1", 100))->unique('zsym')->toArray();
        $managementUnit = getDataFromService('doSelect', 'zDVQL', [], '', 1000);

        return view('role::equipment_manufacturer_statistics', [
            'managementUnit' => $managementUnit,
            'types' => $types,
            'deviceTypes' => $deviceTypes
        ]);
    }

    /**
     * Preview equipment manufacturer statistics report
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function equipmentManufacturerStatisticsReportPreview(Request $request)
    {
        try {
            $request->request->add(['deviceTypes_name' => $request->zCI_Device_Types ?? '']);
            $request->request->add(['devices_name' => $request->types ?? '']);
            $arrIds = [];
            if (empty($request['types'])) {
                $types = getDataFromService('doSelect', 'grc', [
                    'id',
                    'type',
                    'family',
                ], "zetc_type = 0 AND type LIKE '%Rơ le bảo vệ%' AND delete_flag != 1", 100);
                $arr = [];
                foreach ($types as $type) {
                    array_push($arrIds, $type['id']);
                    array_push($arr, $type['type']);
                }
                $request->offsetSet('types', $arr);
            }else{
                $arr = [];
                foreach($request->types as $type){
                    $type = getDataFromService('doSelect', 'grc', [
                        'id',
                        'type',
                        'family',
                    ], "zetc_type = 0 AND type = '".$type."' AND delete_flag != 1");
                    array_push($arrIds, $type['id']);
                    array_push($arr, $type['type']);
                }
                $request->offsetSet('types', $arr);
            }
            $extendAttrs = [
                'zIP',
                'id',
            ];
            $deviceArr = getDevice($request, $extendAttrs);
            // Format device data
            $arr = $manufactureArr = [];
            foreach ($deviceArr as $key => $device) {
                $arr[$key][] = $key + 1;
                $arr[$key][] = !empty($device['zrefnr_dvql.zsym']) ? $device['zrefnr_dvql.zsym'] : '';
                $arr[$key][] = !empty($device['zArea.zsym']) ? $device['zArea.zsym'] : '';
                $arr[$key][] = !empty($device['zrefnr_td.zsym']) ? $device['zrefnr_td.zsym'] : '';
                $arr[$key][] = !empty($device['zrefnr_nl.zsym']) ? $device['zrefnr_nl.zsym'] : '';
                $arr[$key][] = !empty($device['name']) ? $device['name'] : '';
                $arr[$key][] = !empty($device['zCI_Device_Type.zsym']) ? $device['zCI_Device_Type.zsym'] : '';
                $arr[$key][] = !empty($device['zManufacturer.zsym']) ? $device['zManufacturer.zsym'] : '';
                $arr[$key][] = !empty($device['zCI_Device_Kind.zsym']) ? $device['zCI_Device_Kind.zsym'] : '';
                $arr[$key][] = !empty($device['serial_number']) ? $device['serial_number'] : '';
                $arr[$key][] = !empty($device['zYear_of_Manafacture.zsym']) ? $device['zYear_of_Manafacture.zsym'] : '';
                $arr[$key][] = !empty($device['zCountry.name']) ? $device['zCountry.name'] : '';
                $arr[$key][] = !empty($device['info']['zIP']) ? $device['info']['zIP'] : '';
                $arr[$key][] = !empty($device['zphieuchinhdinh']) ? $device['zphieuchinhdinh'] : '';
                array_push($manufactureArr, !empty($device['zManufacturer.zsym']) ? $device['zManufacturer.zsym'] : '');
            }
            $percentageManufactureArr = array_count_values(array_filter($manufactureArr));
            $manufactureArr = array_values(array_unique(array_filter($manufactureArr)));
            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);

            if (count($manufactureArr) > 30) {
                return response()->json([
                    'error' => ['Không thể tải được tệp excel']
                ]);
            }

            if (count($manufactureArr) == 0) {
                return response()->json([
                    'error' => ['Không tìm thấy kết quả nào thỏa mãn']
                ]);
            }

            // Read Template Excel
            $template = storage_path('templates_excel/role/bao_cao_thong_ke_hang_thiet_bi/bao_cao_thong_ke_hang_thiet_bi_' . count($manufactureArr) . '.xlsx');
            $spreadsheet = $reader->load($template);

            // Sheet data
            $statisticsSheet = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THONG_KE");
            // set with column B->Z sheet "KET_QUA_THONG_KE"
            for($c = 'C'; $c <= 'Z'; ++$c){
                $statisticsSheet->getColumnDimension($c)->setWidth(30);
            }
            $percentageSheet = $spreadsheet->setActiveSheetIndexByName("THONG_KE_THEO_HANG");

            // Insert data to statistics sheet
            foreach ($arr as $key => $val) {
                $row = $key + 5;
                $count = 0;
                for ($c = 'B'; $c != 'P'; ++$c) {
                    $statisticsSheet->getCell($c . $row)->setValue($val[$count]);
                    $count++;
                }
            }

            // Insert data to chart statistics sheet
            $startInsertRow = 3;
            foreach ($percentageManufactureArr as $key => $val) {
                $percentageSheet->getCell('B' . $startInsertRow)->setValue($key);
                $percentageSheet->getCell('C' . $startInsertRow)->setValue(round($val / array_sum($percentageManufactureArr) * 100, 2));
                $startInsertRow++;
            }
            fillSearchRequestToExcel($statisticsSheet, formatRequest($request), ['C', 'D', 'E'], [
                'Thiết bị: ' => 'devices_name',
                'Chủng loại: ' => 'deviceTypes_name',
                'Đơn vị quản lý: ' => 'zrefnr_dvql_ids',
            ], 1);
            alignmentExcel($statisticsSheet, 'C6:O1000');
            $spreadsheet->setActiveSheetIndexByName("BIEU_DO")->setSelectedCell('A1');
            $url = writeExcel($spreadsheet, 'bao-cao-thong-ke-hang-thiet-bi-' . time() . '.xlsx');

            return response()->json([
                'success' => true,
                'url' => $url
            ]);
        } catch (\Exception $ex) {
            Log::error('[Role] Get equipment manufacturer statistics report preview: Fail | Reason: ' . $ex->getMessage() . '. ' . $ex->getFile() . ':' . $ex->getLine() . '| Params: ' . json_encode($request->all()));

            return response()->json([
                'error' => [$ex->getMessage()]
            ]);
        }
    }

    /**
     * Return view report relay test manifest system
     * @return view()
     */
    public function reportRelayTestManifestSystem()
    {
        $types = getDataFromService('doSelect', 'grc', [
            'id',
            'type',
            'family',
        ], "zetc_type = 0 AND type LIKE '%Rơ le bảo vệ%' AND delete_flag != 1", 100);

        $typeIdArr = [];
        foreach ($types as $type) {
            $typeIdArr[] = $type['id'];
        }

        $allDeviceTypes = collect(getDataFromService('doSelect', 'zCI_Device_Type', [], "zclass IN (" . implode(',', $typeIdArr) . ") AND active_flag = 1", 100))->unique('zsym')->pluck('zsym')->toArray();

        $allManagementUnits = collect(getDataFromService('doSelect', 'zDVQL', ['zsym','id'], '', 100))->pluck('zsym')->toArray();

        $reports = getDataFromService('doSelect', 'nr', [
            'id',
            'ztestType.zsym'
        ], "ztestType.zsym IS NOT NULL AND class.zetc_type = 1", 150);

        $typeOfExperiments = [];
        foreach($reports as $report){
            if(array_key_exists('ztestType.zsym', $report)){
                array_push($typeOfExperiments, $report['ztestType.zsym']);
            }
        }
        $typeOfExperiments = array_unique($typeOfExperiments);
        asort($typeOfExperiments);
        return view('role::reportRelayTestManifestSystem.index', compact('allDeviceTypes', 'allManagementUnits', 'typeOfExperiments', 'types'));
    }

    /**
     * Relay Test Manifest System Export
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function reportRelayTestManifestSystemExport(Request $request)
    {
        try {
            $startDate = $request->start_date;
            $endDate = $request->end_date;

            $whereClause = "zlaboratoryDate IS NOT NULL AND class.zetc_type = 1";

            if(!empty(!empty($startDate)) && !empty($endDate) && $startDate > $endDate) {
                return response()->json([
                    'error' => ['Thời gian kết thúc phải lớn hơn hoặc bằng thời gian bắt đầu!']
                ]);
            }

            if (!empty($startDate)) {
                $whereClause .= " AND zlaboratoryDate > " . strtotime($startDate);
            }

            if (!empty($endDate)) {
                $whereClause .= " AND zlaboratoryDate <= " . (strtotime($endDate) + 86399);
            }
            if (!empty($request['device_type_id'])) {
                foreach ($request['device_type_id'] as $key => $val) {
                    if ($key == 0) {
                        $whereClause .= " AND zCI_Device.class IN (";
                    }
                    $whereClause .= $val;
                    if ($key < count($request['device_type_id']) - 1) {
                        $whereClause .= ",";
                    } else {
                        $whereClause .= ")";
                    }
                }
            }

            $arrayTypes = arrayTypeRelay();

            foreach ($arrayTypes as $key => $val) {
                if ($key == 0) {
                    $whereClause .= " AND class.type IN (";
                }
                $whereClause .= "'" . $val . "'";
                if ($key < count($arrayTypes) - 1) {
                    $whereClause .= ",";
                } else {
                    $whereClause .= ")";
                }
            }

            if($request->get('ztestType')){
                $ztestTypes = $request->get('ztestType');
                foreach ($ztestTypes as $key => $val) {
                    if ($key == 0) {
                        $whereClause .= " AND ztestType.zsym IN (";
                    }
                    $whereClause .= "'" . $val . "'";
                    if ($key < count($ztestTypes) - 1) {
                        $whereClause .= ",";
                    } else {
                        $whereClause .= ")";
                    }
                }
            }

            if($request->get('deviceTypes')){
                $deviceTypes = $request->get('deviceTypes');
                foreach ($deviceTypes as $key => $val) {
                    if ($key == 0) {
                        $whereClause .= " AND zCI_Device_Type.zsym IN (";
                    }
                    $whereClause .= "'" . $val . "'";
                    if ($key < count($deviceTypes) - 1) {
                        $whereClause .= ",";
                    } else {
                        $whereClause .= ")";
                    }
                }
            }

            if($request->get('dvql')){
                $dvql = $request->get('dvql');
                foreach ($dvql as $key => $val) {
                    if ($key == 0) {
                        $whereClause .= " AND zrefnr_dvql.zsym IN (";
                    }
                    $whereClause .= "'" . $val . "'";
                    if ($key < count($dvql) - 1) {
                        $whereClause .= ",";
                    } else {
                        $whereClause .= ")";
                    }
                }
            }

            $arrayProtectedRelay = arrayProtectionRelay();
            foreach ($arrayProtectedRelay as $key => $val) {
                if ($key == 0) {
                    $whereClause .= " AND zCI_Device.class.type IN (";
                }
                $whereClause .= "'" . $val . "'";
                if ($key < count($arrayProtectedRelay) - 1) {
                    $whereClause .= ",";
                } else {
                    $whereClause .= ")";
                }
            }

            $attributes = [
                'name',
                'class.type',
                'zManufacturer.zsym',
                'ztestType.zsym',
                'zrefnr_dvql.zsym',
                'zrefnr_td.zsym',
                'zCI_Device.name',
                'zCI_Device_Kind.zsym',
                'zManufacturer.zsym',
                'zExperimenter.combo_name',
                'zlaboratoryDate',
                'zCI_Device_Type.zsym'
            ];

            $reports = getDataFromService('doSelect', 'nr', $attributes, $whereClause, 150);
            foreach($reports as $index => $value){
                $reports[$index]['zlaboratoryDate'] = Carbon::createFromTimestamp($value['zlaboratoryDate'])->format('d/m/Y');
            }

            // unset item not has key ztestType.zsym
            $dataChart = $reports;
            foreach($dataChart as $index => $value){
                if(!array_key_exists('ztestType.zsym', $value)){
                    unset($dataChart[$index]);
                }
            }
            // count all item has ztestType
            $countDataChart = count($dataChart);
            // group by ztestType
            $dataChart = collect($dataChart)->groupBy(function($item){
                return $item['ztestType.zsym'];
            });

            // count unique ztestType for import excel file
            $count = $dataChart->count();

            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);

            // Read Template Excel
            $template = storage_path("templates_excel/role/bao_cao_thong_ke_cong_tac_thi_nghiem/bao_cao_thong_ke_cong_tac_thi_nghiem_{$count}.xlsx");
            $spreadsheet = $reader->load($template);
            // Sheet data
            $statisticsSheet = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THONG_KE");
            $chartDataSheet = $spreadsheet->setActiveSheetIndexByName("DATA_BD");

            if (!empty($startDate) && !empty($endDate)) {
                $spreadsheet->setActiveSheetIndexByName("BIEU_DO")->getCell('B5')->setValue("Biểu đồ so sánh công tác thí nghiệm từ ngày " . date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate)));
            } elseif (empty($startDate) && !empty($endDate)) {
                $spreadsheet->setActiveSheetIndexByName("BIEU_DO")->getCell('B5')->setValue("Biểu đồ so sánh công tác thí nghiệm đến ngày " . date('d/m/Y', strtotime($endDate)));
            } elseif (!empty($startDate) && empty($endDate)) {
                $spreadsheet->setActiveSheetIndexByName("BIEU_DO")->getCell('B5')->setValue("Biểu đồ so sánh công tác thí nghiệm từ ngày " . date('d/m/Y', strtotime($startDate)));
            }

            $spreadsheet->setActiveSheetIndexByName("BIEU_DO");
            // Write data to sheet relay test manifest
            $this->writeDataSheetRelayTestManifestSystem($reports, $statisticsSheet);

            $columns = range('C', 'H');
            $inputs = [
                'Loại thiết bị: ' => 'device_type_id',
                'Chủng loại: ' => 'zCIDeviceType',
                'Đơn vị quản lý: ' => 'dvql',
                'Thời gian bắt đầu: ' => 'start_date',
                'Thời gian kết thúc: ' => 'end_date',
                'Loại hình thí nghiệm: ' => 'ztestType',
            ];
            $requestArr = formatRequest($request);
            fillSearchRequestToExcel($statisticsSheet, $requestArr, $columns, $inputs, 1);
            alignmentExcel($statisticsSheet, 'C6:I1000');
            // Write data to sheet chart relay test manifest
            $this->writeDataChartRelayTestManifestSystem($dataChart, $countDataChart, $chartDataSheet);
            $title = 'bao-cao-thong-ke-cong-tac-thi-nghiem-ro-le-'.time().'.xlsx';
            $url = writeExcel($spreadsheet, $title);
            return response()->json([
                'success' => true,
                'url' => $url,
            ]);
        } catch (\Exception $e) {
            Log::error('[Role] Statistical report of relay testing: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));

            return response()->json([
                'error' => [$e->getMessage()]
            ]);
        }
    }

    /**
     * Write data to sheet statistics for report relay test manifest system
     *
     * @param array $data
     * @param  PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $spreadsheet
     */
    private function writeDataSheetRelayTestManifestSystem($data, $spreadsheet)
    {
        if(count($data) == 0){
            return;
        }
        // attribute fill data
        $attributesFill = ['zrefnr_dvql.zsym', 'zrefnr_td.zsym', 'zCI_Device.name', 'ztestType.zsym', 'zCI_Device_Kind.zsym', 'zManufacturer.zsym', 'zExperimenter.combo_name', 'zlaboratoryDate'];

        $startIndexRow = 5;
        $stt = 1;
        foreach( $data as $item ){
            $spreadsheet->getCell("B{$startIndexRow}")->setValue($stt);
            $indexAttr = 0;
            for($c = 'C'; $c <= 'J'; ++$c){
                if(array_key_exists($attributesFill[$indexAttr], $item)){
                    $spreadsheet->getCell($c . $startIndexRow)->setValue($item[$attributesFill[$indexAttr]]);
                }else{
                    $spreadsheet->getCell($c . $startIndexRow)->setValue("");
                }
                $indexAttr++;
            }
            $stt++;
            $startIndexRow++;
        }

        // count unique list attribute fill last recode in sheet statistics
        $totalDvql = $totalTd = $totalDevice = $totalTestType = $totalDeviceKind = $totalManufacturer = $totalExperimental = [];
        foreach($data as $report){
            if(array_key_exists('zrefnr_dvql.zsym', $report)){
                array_push($totalDvql, $report['zrefnr_dvql.zsym']);
            }
            if(array_key_exists('zrefnr_td.zsym', $report)){
                array_push($totalTd, $report['zrefnr_td.zsym']);
            }
            if(array_key_exists('zCI_Device.name', $report)){
                array_push($totalDevice, $report['zCI_Device.name']);
            }
            if(array_key_exists('ztestType.zsym', $report)){
                array_push($totalTestType, $report['ztestType.zsym']);
            }
            if(array_key_exists('zCI_Device_Kind.zsym', $report)){
                array_push($totalDeviceKind, $report['zCI_Device_Kind.zsym']);
            }
            if(array_key_exists('zManufacturer.zsym', $report)){
                array_push($totalManufacturer, $report['zManufacturer.zsym']);
            }
            if(array_key_exists('zExperimenter.combo_name', $report)){
                array_push($totalExperimental, $report['zExperimenter.combo_name']);
            }
        }
        $dataRowLastSheetStatistics = [
            count(array_unique($totalDvql)),
            count(array_unique($totalTd)),
            count(array_unique($totalDevice)),
            count(array_unique($totalTestType)),
            count(array_unique($totalDeviceKind)),
            count(array_unique($totalManufacturer)),
            count(array_unique($totalExperimental)),
            collect($data)->unique('zlaboratoryDate')->count(),
        ];

        // insert last row in sheet
        $endIndexRow = 5 + count($data);
        $spreadsheet->getCell("B{$endIndexRow}")->setValue('Tổng số');

        $indexLastData = 0;
        for($c = 'C'; $c <= 'J'; ++$c){
            $spreadsheet->getCell($c . $endIndexRow)->setValue($dataRowLastSheetStatistics[$indexLastData]);
            $indexLastData++;
        }
    }

    /**
     * Write data to sheet statistics for report relay test manifest system
     *
     * @param array $data
     * @param  PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $spreadsheet
     */
    private function writeDataChartRelayTestManifestSystem($dataChart, $countDataChart, $spreadsheet)
    {
        if($countDataChart == 0){
            return false;
        }
        $dataChartFormated = [];

        foreach($dataChart as $ztestTypeName => $ztestTypeCollect){
            $dataChartFormated[] = [
                'name' => $ztestTypeName,
                'total' => $ztestTypeCollect->count()
            ];
        }

        $startIndexRow = 3;
        foreach($dataChartFormated as $item){
            $spreadsheet->getCell("B{$startIndexRow}")->setValue($item['name']);
            $spreadsheet->getCell("C{$startIndexRow}")->setValue(round($item['total'] / $countDataChart * 100, 2));
            $startIndexRow++;
        }
    }

    /**
     * index report 2
     *
     * @param Request $request
     * @return Application|Factory|View|JsonResponse
     */
    public function reportDamagedRelay(Request $request)
    {
        try {
            // device
            $list_device = getDataFromService('doSelect', 'zDVQL', ['zsym','id'], '', 100);
            sortData($list_device);
            // type device
            $types = getDataFromService('doSelect', 'grc', [
                'id',
                'type',
                'family',
            ], "zetc_type = 0 AND type LIKE '%Rơ le bảo vệ%' AND delete_flag != 1", 100);
            $typeIdArr = [];
            foreach ($types as $type) {
                $typeIdArr[] = $type['id'];
            }
            $allDeviceTypes = collect(getDataFromService('doSelect', 'zCI_Device_Type', [], "zclass IN (" . implode(',', $typeIdArr) . ") AND active_flag = 1", 100))->unique('zsym')->pluck('zsym')->toArray();
            return view('role::damaged_relay.index', compact('list_device', 'allDeviceTypes', 'types'));
        } catch (\Exception $ex) {
            Log::error('[Role] index report 2: Fail | Reason: ' . $ex->getMessage() . '. ' . $ex->getFile() . ':' . $ex->getLine() . '| Params: ' . json_encode($request->all()));

            return response()->json([
                'error' => [$ex->getMessage()]
            ]);
        }
    }

    /**
     * export report 2
     *
     * @param Request $request
     * @return JsonResponse|\string[][]
     */
    public function reportDamagedRelayPreview(Request $request)
    {
        try {
            // get user login session
            $user = session()->get(env('AUTH_SESSION_KEY'));
            $arrayTypes = arrayTypeRelay();
            $whereClause = '';
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
            $whereClause .= "AND ztestType.zsym = 'Thay thế rơ le' AND class.zetc_type = 1 AND zlaboratoryDate IS NOT NULL AND zCI_Device.class.type LIKE '%Rơ le bảo vệ%' AND class.type IS NOT NULL";
            if (!empty($request['device_type_id'])) {
                foreach ($request['device_type_id'] as $index => $item) {
                    if ($index == 0) {
                        $whereClause .= " AND zCI_Device.class IN (";
                    }
                    $whereClause .= "'" . $item . "'";
                    if ($index < count($request['device_type_id']) - 1) {
                        $whereClause .= ",";
                    } else {
                        $whereClause .= ")";
                    }
                }
            }

            // filter time
            if (!empty($request['from']) && !empty($request['to']) && $request['to'] < $request['from']) {
                return [
                    'error' => ['Thời gian kết thúc phải lớn hơn hoặc bằng thời gian bắt đầu!']
                ];
            }

            if(!empty($request['from'])) {
                $from = strtotime($request['from'].'00:00');
                $whereClause .= " AND zlaboratoryDate >= " . $from . "";
            }
            if(!empty($request['to'])) {
                $to = strtotime($request['to'].'23:59');
                $whereClause .= " AND zlaboratoryDate <= " . $to . "";
            }
            // filter đơn vị quản lý
            if(!empty($request['dvqls_name'])){
                foreach ($request['dvqls_name'] as $key => $val) {
                    if ($key == 0) {
                        $whereClause .= " AND zrefnr_dvql.zsym IN (";
                    }
                    $whereClause .= "'". $val ."'";
                    if ($key < count($request['dvqls_name']) - 1) {
                        $whereClause .= ",";
                    } else {
                        $whereClause .= ")";
                    }
                }
            }
            // filter type
            if(!empty($request['type'])){
                foreach ($request['type'] as $key => $val) {
                    if ($key == 0) {
                        $whereClause .= " AND zCI_Device_Type.zsym IN (";
                    }
                    $whereClause .= "'". $val ."'";
                    if ($key < count($request['type']) - 1) {
                        $whereClause .= ",";
                    } else {
                        $whereClause .= ")";
                    }
                }
            }
            // filter hãng sản xuất
            if(!empty($request['zManufacturer'])){
                foreach ($request['zManufacturer'] as $key => $val) {
                    if ($key == 0) {
                        $whereClause .= " AND zManufacturer IN (";
                    }
                    $whereClause .= "'". $val ."'";
                    if ($key < count($request['zManufacturer']) - 1) {
                        $whereClause .= ",";
                    } else {
                        $whereClause .= ")";
                    }
                }
            }
            $attributes = config('attributes.attribute_role_report_2_data');
            // get data
            $reports = getDataFromService('doSelect', 'nr', $attributes, $whereClause, 150);
            if( !empty($reports) < 1 ){
                return response()->json([
                    'error' => ['Không có dữ liệu!']
                ]);
            }
            // get data assoc
            $classTypeAssoc = ['4. BBTN F87B-U', '26. BBTN BCU', '28. BBTN F50_F67Ns_81_BCU', '29. BBTN F50_F67Ns_BCU', '10. BBTN F50/51'];
            foreach ($reports as $key => $item) {
                if( in_array($item['class.type'], $classTypeAssoc)){
                    $atr = ['id','zReplacingRC.zsym'];
                    $atr_value = 'zReplacingRC.zsym';
                } else {
                    $atr = ['id','zrolereplace.zsym'];
                    $atr_value = 'zrolereplace.zsym';
                }
                $obj = getObjectTypeRelay($item['class.type']);
                $where = "id = U'" . $item['id'] . "'";
                $data_obj = getDataFromService('doSelect', $obj, $atr, $where, 150);
                $reports[$key]['zrolereplace'] = $data_obj[0][$atr_value]??'';
            }
            // get data BD
            $data_bd_1 = $data_bd_2 = [];
            $data_bd_1 = $this->getDataSheetChart($reports, 'zrolereplace');
            $data_bd_2 = $this->getDataSheetChart($reports, 'zManufacturer.zsym');
            if(count($data_bd_1) > count($data_bd_2) || count($data_bd_1) == count($data_bd_2)){
                $number_template = count($data_bd_1);
            }elseif(count($data_bd_1) < count($data_bd_2)){
                $number_template = count($data_bd_2);
            }else{
                $number_template = 30;
            }
            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);
            // Read Template Excel
            $template = storage_path('templates_excel/role/bao-cao-thong-ke-hu-hong-ro-le/bao-cao-thong-ke-hu-hong-'.$number_template.'.xlsx');
            $spreadsheet = $reader->load($template);
            // Create New Writer
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->setIncludeCharts(true);

            // Fill title to sheet 'THONG_KE_DU_LIEU'
            $columns = range('A', 'R');
            $titles = config('attributes.report_damaged_relay_title_sheet');
            $indexTitle = 4; // follow template excel
            $sheetStatistical = $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
            foreach($columns as $index => $column){
                $sheetStatistical->getCell($column.$indexTitle)->setvalue($titles[$index]);
            }
            // Apply style for sheet
            $c = 'B';
            for($i = 4; $i <= 100; $i++){
                $sheetStatistical->getRowDimension($i)->setRowHeight(35);
                $sheetStatistical->getColumnDimension($c)->setWidth(35);
                ++$c;
            }
            $sheetStatistical->getStyle('A1:Z1000')->getAlignment()->setWrapText(true);

            // get time to sheet BIEU_DO
            $colum_time = ['6','32','58','84'];
            foreach ($colum_time as $table_number){
                $spreadsheet->setActiveSheetIndexByName('BIEU_DO')->getCell("E" . ($table_number))->setValue('');
            }
            // get data sheet KET_QUA_THONG_KE
            $colum_name = config('attributes.attribute_role_report_2');
            $this->getDataSheet($reports, $colum_name, $spreadsheet, 'THONG_KE_DU_LIEU',$request);
            // colum name chart
            $colum_name = ['number','type','percent'];
            // Get data sheet BD 1
            $this->getDataSheet($data_bd_1, $colum_name, $spreadsheet, 'DATA_BD1',$request);
            // Get data sheet BD 2
            $this->getDataSheet($data_bd_2, $colum_name, $spreadsheet, 'DATA_BD2',$request);
            // Get data sheet BD 3
            $this->getDataSheet($data_bd_1, $colum_name, $spreadsheet, 'DATA_BD3',$request);
            // Get data sheet BD 4
            $this->getDataSheet($data_bd_2, $colum_name, $spreadsheet, 'DATA_BD4',$request);

            $columns = range('B', 'G');
            $inputs = [
                'Loại thiết bị: ' => 'device_type_id',
                'Chủng loại: ' => 'type',
                'Đơn vị quản lý: ' => 'dvqls_name',
                'Thời gian bắt đầu cần thống kê: ' => 'from',
                'Thời gian kết thúc cần thống kê: ' => 'to',
                'Hãng sản xuất: ' => 'manufacture',
            ];
            $requestArr = formatRequest($request);
            fillSearchRequestToExcel($sheetStatistical, $requestArr, $columns, $inputs, 2);
            alignmentExcel($sheetStatistical, 'B7:R1000');
            // active sheet BIEU_DO
            $spreadsheet->setActiveSheetIndexByName('BIEU_DO');

            // Output Permission
            $outputDir = public_path('export');
            // Output path
            $output = $outputDir . '/' . 'bao-cao-thong-ke-hu-hong-ro-le'.'-'.time() .'-'.$user['ssid']. '.xlsx';
            if (is_file($output)) {
                unlink($output);
            }
            // Writer File Into Path
            $writer->save('export/bao-cao-thong-ke-hu-hong-ro-le' . '-'.time() .'-'.$user['ssid']. '.xlsx');
            // Return Link Download
            $url = str_replace(public_path(''), getenv('APP_URL'), $output);
            return response()->json([
                'success' => true,
                'url' => $url
            ]);
        } catch (\Exception $ex) {
            Log::error('[Role] export report 2: Fail | Reason: ' . $ex->getMessage() . '. ' . $ex->getFile() . ':' . $ex->getLine() . '| Params: ' . json_encode($request->all()));

            return response()->json([
                'error' => [$ex->getMessage()]
            ]);
        }
    }

    /**
     * get data export report 2
     *
     * @param $data
     * @param $colum_name
     * @param $spreadsheet
     * @param $name_sheet
     * @return void
     */
    private function getDataSheet($data, $colum_name, $spreadsheet, $name_sheet, $request)
    {
        // delete data wait of template
        $count = 0;
        $table = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R'];
        for ($i = 0; $i <= 34; $i++){
            $count++;
            $colum = $count + 4;
            foreach($colum_name as $ke => $val){
                $spreadsheet->setActiveSheetIndexByName($name_sheet)->getCell($table[$ke] . ($colum))->setValue('');
            }
        }
        if($name_sheet != 'THONG_KE_DU_LIEU'){
            $spreadsheet->setActiveSheetIndexByName($name_sheet)->getCell('C4')->setValue('Tỉ lệ %');
        }
        // get title chart 3
        if($name_sheet == 'DATA_BD3' && !empty($request['manufacture']) && count($request['manufacture']) == 1 && count($data) > 0) {
            $manufacturer = $request['manufacture'][0];
            if( is_numeric($manufacturer) ){
                libxml_disable_entity_loader(false);
                $manufacturer = @getDataFromService('doSelect', 'zManufacturer', ['id', 'zsym'], " id={$manufacturer} ")['zsym'];
            }
            $spreadsheet->setActiveSheetIndexByName($name_sheet)->getCell('A1')->setValue('SO SÁNH NGUYÊN NHÂN HƯ HỎNG TRONG HÃNG '.$manufacturer);
            $spreadsheet->setActiveSheetIndexByName('BIEU_DO')->getCell('B56')->setValue('SO SÁNH NGUYÊN NHÂN HƯ HỎNG TRONG HÃNG '.$manufacturer);
        }elseif($name_sheet == 'DATA_BD3'){
            $spreadsheet->setActiveSheetIndexByName($name_sheet)->getCell('A1')->setValue('SO SÁNH NGUYÊN NHÂN HƯ HỎNG TRONG HÃNG - DỮ LIỆU BIỂU ĐỒ KHÔNG HỢP LỆ');
            $spreadsheet->setActiveSheetIndexByName('BIEU_DO')->getCell('B56')->setValue('SO SÁNH NGUYÊN NHÂN HƯ HỎNG TRONG HÃNG - DỮ LIỆU BIỂU ĐỒ KHÔNG HỢP LỆ');
        }
        // get title chart 4
        if($name_sheet == 'DATA_BD4' && !empty($request['type']) && count($request['type']) == 1 && count($data) > 0) {
            $spreadsheet->setActiveSheetIndexByName($name_sheet)->getCell('A1')->setValue('SO SÁNH PHẦN TRĂM HƯ HỎNG RƠ LE CỦA CÁC HÃNG LOẠI '.$request['type'][0]);
            $spreadsheet->setActiveSheetIndexByName('BIEU_DO')->getCell('B82')->setValue('SO SÁNH PHẦN TRĂM HƯ HỎNG RƠ LE CỦA CÁC HÃNG LOẠI '.$request['type'][0]);
        }elseif($name_sheet == 'DATA_BD4'){
            $spreadsheet->setActiveSheetIndexByName($name_sheet)->getCell('A1')->setValue('SO SÁNH PHẦN TRĂM HƯ HỎNG RƠ LE CỦA CÁC HÃNG THEO LOẠI - DỮ LIỆU BIỂU ĐỒ KHÔNG HỢP LỆ');
            $spreadsheet->setActiveSheetIndexByName('BIEU_DO')->getCell('B82')->setValue('SO SÁNH PHẦN TRĂM HƯ HỎNG RƠ LE CỦA CÁC HÃNG THEO LOẠI - DỮ LIỆU BIỂU ĐỒ KHÔNG HỢP LỆ');
        }
        // get data chart
        if(
            $name_sheet == 'DATA_BD1'
            || $name_sheet == 'THONG_KE_DU_LIEU'
            || $name_sheet == 'DATA_BD2'
            || $name_sheet == 'DATA_BD3' && !empty($request['manufacture']) && count($request['manufacture']) == 1
            || $name_sheet == 'DATA_BD4' && !empty($request['type']) && count($request['type']) == 1
        ){
            // add data to template
            $number = 0;
            foreach($data as $key => $value){
                $number++;
                $colum_key = $number + 4;
                $value['number'] = $number;
                $value['type'] = $key;
                foreach($colum_name as $k => $val){
                    if($val != 'zlaboratoryDate'){
                        $spreadsheet->setActiveSheetIndexByName($name_sheet)->getCell($table[$k] . ($colum_key))->setValue($value[$val]??'');
                    }else{
                        $spreadsheet->setActiveSheetIndexByName($name_sheet)->getCell($table[$k] . ($colum_key))->setValue(date('d-m-Y', $value[$val]));
                    }
                }
            }
        }
    }

    /**
     * get data chart export report 2
     *
     * @param $data
     * @param $type sample: zrolereplace (Nguyên nhân hư hỏng)
     * @return array
     */
    private function getDataSheetChart($data, $type)
    {
        $data_bd = [];
        $count = 0;
        foreach ($data as $value){
            if(isset($value['zrolereplace']) && $value['zrolereplace'] == '' && $type == 'zrolereplace'){
                continue;
            }
            // break item not has index zManufacturer.zsym
            if( $type == 'zManufacturer.zsym' && !isset($value['zManufacturer.zsym']) || $value['zManufacturer.zsym'] == ''){
                continue;
            }
            if(empty($data_bd[$value[$type]]['total'])){
                $data_bd[$value[$type]]['total'] = 0;
                $data_bd[$value[$type]]['type'] = $value[$type];
            }
            $data_bd[$value[$type]]['total'] += 1;
            if($type == 'zrolereplace' && $value['zrolereplace'] || $type != 'zrolereplace'){
                $count += 1;
            }
        }
        foreach ($data_bd as $value){
            $data_bd[$value['type']]['percent'] = number_format((float)$value['total'] / $count * 100, 2, '.', '');
        }
        return $data_bd;
    }
}
