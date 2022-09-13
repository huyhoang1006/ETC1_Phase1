<?php

use App\Services\CAWebServices;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\Machines\Http\Controllers\HighPressureController;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// get data HighPressure
if( !function_exists('getDataHighPressure') ) {
    function getDataHighPressure($request)
    {
        $items = [];
        $arrayClassTypes = [
            '10.1 BBTN Máy cắt 1 buồng cắt 1 bộ truyền động',
            '10.2. BBTN Máy cắt 1 buồng cắt 3 bộ truyền động',
            '10.3. BBTN Máy cắt 2 buồng cắt 3 bộ truyền động',
        ];
        if($request->deviceType){
            $items = filterDevice([array_flip(config('constant.device_statistical'))['Máy cắt']], $request, $arrayClassTypes);
            // filter assoc device unset device when null result
            if (!empty($request->zdongdiendinhmuc) || !empty($request->zdienapdinhmuc)) {
                foreach($items as $index => $item){
                    if($request->zdongdiendinhmuc || $request->zdienapdinhmuc){
                        $whereClauseDevice = " id = U'".$item['id']."' ";
                        if($request->zdongdiendinhmuc){
                            $whereClauseDevice .= " AND zdongdiendinhmuc LIKE '%".$request->zdongdiendinhmuc."%' ";
                        }
                        if($request->zdienapdinhmuc){
                            $whereClauseDevice .= " AND zdienapdinhmuc LIKE '%".$request->zdienapdinhmuc."%'";
                        }
                        $device = getDataFromService('doSelect', 'zETC_Device', [], $whereClauseDevice);
                        if( empty($device) ){
                            unset($items[$index]);
                        }
                    }
                }
            }
            $now = \DateTime::createFromFormat('U.u', microtime(true));
            Log::info($now->format("m-d-Y H:i:s.u") . ' => 3. The device information has been obtained, ready to get the report information');

            $classType = [ getTypeOfCuttingMachine($request->deviceType) ];
            foreach ($classType as $key => $val) {
                if ($key == 0) {
                    $whereClauseBase = " class.type IN (";
                }
                $whereClauseBase .= "'" . $val . "'";
                if ($key < count($classType) - 1) {
                    $whereClauseBase .= ",";
                } else {
                    $whereClauseBase .= ")";
                }
            }
            if( $request->start_date ){
                $whereClauseBase .= " AND zlaboratoryDate >= " . strtotime($request->start_date);
            }

            if( $request->end_date ){
                $whereClauseBase .= " AND zlaboratoryDate < " . strtotime("+1 day", strtotime($request->end_date));
            }
            if($request->ztestType){
                foreach ($request->ztestType as $key => $val) {
                    if ($key == 0) {
                        $whereClauseBase .= " AND ztestType.zsym IN (";
                    }
                    $whereClauseBase .= "'" . $val . "'";
                    if ($key < count($request->ztestType) - 1) {
                        $whereClauseBase .= ",";
                    } else {
                        $whereClauseBase .= ")";
                    }
                }
            }
            $attributes = [
                'id',
                'name',
                'zlaboratoryDate',
                'zphase.zsym'
            ];
            $now = \DateTime::createFromFormat('U.u', microtime(true));
            Log::info($now->format("m-d-Y H:i:s.u") . ' => 4. Prepared to get report information, prepare to get report information');

            $count = 1;
            $num = 1;
            foreach($items as $index => $item){
                $whereClauseGroup = " id=U'".$item['id']."' ";
                $group = getDataFromService('doSelect', 'zETC_Device', ['id', 'zcagroupmc'], $whereClauseGroup);

                $now = \DateTime::createFromFormat('U.u', microtime(true));
                Log::info($now->format("m-d-Y H:i:s.u") . ' => 5.' . $count . '.' . $num . '. Get extended device information ' . $count);
                $num++;

                $items[$index]['group'] = @$group['zcagroupmc'];
                if(array_key_exists('name', $item)){
                    $whereClause = $whereClauseBase . " AND zCI_Device.name = '".$item['name']."' AND class.zetc_type = 1";
                    $data = getDataFromService('doSelect', 'nr', $attributes, $whereClause, 150);
                    $items[$index]['class.type'] = $data;
                }

                $now = \DateTime::createFromFormat('U.u', microtime(true));
                Log::info($now->format("m-d-Y H:i:s.u") . ' => 5.' . $count . '.' . $num . '. Get device report information ' . $count);
                if ($num == 2) {
                    $num = 1;
                }
                $count++;
            }
            $now = \DateTime::createFromFormat('U.u', microtime(true));
            Log::info($now->format("m-d-Y H:i:s.u") . ' => 6. End of data processing');
        }
        return $items;
    }
}

if( !function_exists('getTypeOfCuttingMachine') ){
    function getTypeOfCuttingMachine($id){
        $type = '';
        switch ($id) {
            case config('constant.device_mc.3_bo'):
                $type = '10.2. BBTN Máy cắt 1 buồng cắt 3 bộ truyền động';
                break;
            case config('constant.device_mc.1_bo_1_buong'):
                $type = '10.1 BBTN Máy cắt 1 buồng cắt 1 bộ truyền động';
                break;
            case config('constant.device_mc.1_bo_2_buong'):
                $type = '10.3. BBTN Máy cắt 2 buồng cắt 3 bộ truyền động';
                break;
            default:
                break;
        }
        return $type;
    }
}

// get data high pressure cutting time report dot one
if( !function_exists('getDataCuttingTimeHighPressure') ){
    function getDataCuttingTimeHighPressure(array $ids){
        $items = [];
        $attributes = [
            'name',
            'class.type',
            'zlaboratoryDate',
            'id'
        ];
        $attrsChild = [
            // trip 1
            'z49ott1panum',
            'z50ott1pbnum',
            'z51ott1pcnum',
            // trip 2
            'z53ott2panum',
            'z53ott2pbnum',
            'z54ott2pcnum',
        ];

        foreach($ids as $id){
            $whereClause = " id = U'".$id."' ";
            $item = getDataFromService('doSelect', 'nr', $attributes, $whereClause);
            if( !empty($item) ){
                $objectType =  $objectType = 'zCA'.join('_', explode('.', substr($item['class.type'], 0, 4)));

                $data = getDataFromService('doSelect', $objectType, $attrsChild, $whereClause);
                $item['data']['Cuộn cắt 1 (Trip 1)'] = [
                    @$data['z49ott1panum'] ?? '',
                    @$data['z50ott1pbnum'] ?? '',
                    @$data['z51ott1pcnum'] ?? '',
                ];
                $item['data']['Cuộn cắt 2 (Trip 2)'] = [
                    @$data['z53ott2panum'] ?? '',
                    @$data['z53ott2pbnum'] ?? '',
                    @$data['z54ott2pcnum'] ?? '',
                ];
                array_push($items, $item);
            }
        }
        $items = collect($items)->sortBy('zlaboratoryDate')->toArray();
        foreach($items as $index => $item){
            if(array_key_exists('zlaboratoryDate', $item)){
                $items[$index]['zlaboratoryDate'] = Carbon::createFromTimestamp($item['zlaboratoryDate'])->format('d/m/Y');
            }
        }
        return $items;
    }
}
// get data high pressure cutting time report dot two
if( !function_exists('getDataCuttingTimeHighPressureDotTwo') ){
    function getDataCuttingTimeHighPressureDotTwo(array $ids){
        $items = [];
        $attributes = [
            'name',
            'class.type',
            'zlaboratoryDate',
            'zphase.zsym'
        ];
        $attrsChild = [
            // trip 1
            'z49ott1panum',
            // trip 2
            'z53ott2panum',
        ];
        foreach($ids as $id){
            $whereClause = " id = U'".$id."' ";
            $item = getDataFromService('doSelect', 'nr', $attributes, $whereClause);
            if( !empty($item) ){
                $objectType =  $objectType = 'zCA'.join('_', explode('.', substr($item['class.type'], 0, 4)));
                $data = getDataFromService('doSelect', $objectType, $attrsChild, $whereClause);
                $item['data']['Cuộn cắt 1 (Trip 1)'] = @$data['z49ott1panum'] ?? '';
                $item['data']['Cuộn cắt 2 (Trip 2)'] = @$data['z53ott2panum'] ?? '';
                array_push($items, $item);
            }
        }
        $items = collect($items)->sortBy('zlaboratoryDate')->sortBy(function($item){
            return @$item['zphase.zsym'];
        })->toArray();
        foreach($items as $index => $item){
            if(array_key_exists('zlaboratoryDate', $item)){
                $items[$index]['zlaboratoryDate'] = Carbon::createFromTimestamp($item['zlaboratoryDate'])->format('d/m/Y');
            }
        }
        $items = collect($items)->groupBy('zlaboratoryDate')->toArray();
        return $items;
    }
}
// get data high pressure cutting time report dot three
if( !function_exists('getDataCuttingTimeHighPressureDotThree') ){
    function getDataCuttingTimeHighPressureDotThree(array $ids){
        $items = [];
        $attributes = [
            'name',
            'class.type',
            'zlaboratoryDate',
            'zphase.zsym'
        ];
        $attrsChild = [
            // Cuộn cắt 1
            'z44ott11num',
            'z45ott12num',
            // Cuộn cắt 2
            'z46ott21num',
            'z47ott22num',
        ];
        foreach($ids as $id){
            $whereClause = " id = U'".$id."' ";
            $item = getDataFromService('doSelect', 'nr', $attributes, $whereClause);
            if( !empty($item) ){
                $objectType =  $objectType = 'zCA'.join('_', explode('.', substr($item['class.type'], 0, 4)));
                $data = getDataFromService('doSelect', $objectType, $attrsChild, $whereClause);
                $item['data']['Cuộn cắt 1 (Trip 1)'] = @$data['z44ott11num'] . '_' .@$data['z45ott12num'];
                $item['data']['Cuộn cắt 2 (Trip 2)'] = @$data['z46ott21num'] . '_' .@$data['z47ott22num'];
                array_push($items, $item);
            }
        }
        $items = collect($items)->sortBy('zlaboratoryDate')->sortBy(function($item){
            return @$item['zphase.zsym'];
        })->toArray();

        foreach($items as $index => $item){
            if( array_key_exists('zlaboratoryDate', $item) ){
                $items[$index]['zlaboratoryDate'] = Carbon::createFromTimestamp($item['zlaboratoryDate'])->format('d/m/Y');
            }
        }
        $items = collect($items)->groupBy('zlaboratoryDate')->toArray();
        return $items;
    }
}
// validate report dot two, three (follow specs 3->13)
if( !function_exists('validateReportDotTwoThree') ){
    function validateReportDotTwoThree(array $ids){
        $count = count($ids);
        $devices = [];
        $dates = [];
        $attributes = [
            'name',
            'class.type',
            'id',
            'zCI_Device.name',
            'zCI_Device',
            'zlaboratoryDate',
            'zphase.zsym'
        ];
        $items = [];
        foreach($ids as $id){
            $whereClause = " id = U'".$id."' ";
            $item = getDataFromService('doSelect', 'nr', $attributes, $whereClause);
            $whereClauseDevice =  " id = U'".$item['zCI_Device']."' ";
            $device = getDataFromService('doSelect', 'zETC_Device', ['id', 'zcagroupmc'], $whereClauseDevice);
            array_push($devices, $item['zCI_Device']);
            if( array_key_exists('zlaboratoryDate', $item ) ){
                $item['zlaboratoryDate'] = Carbon::createFromTimestamp($item['zlaboratoryDate'])->format('d/m/Y');
                array_push($dates, $item['zlaboratoryDate']);
            }
            $item['groupDevice'] = @$device['zcagroupmc'];
            array_push($items, $item);
        }
        // get total phase one day
        $arrayPhase = [];
        if( count($dates) == 0 ){
            return ['Các biên bản đã chọn cần có cùng ngày thí nghiệm!'];
        }
        $phases = collect($items)->groupBy('zlaboratoryDate');
        $flagCountDate = false;
        foreach($phases as $item){
            array_push($arrayPhase, $item->count());
            if($item->count() > 3){
                $flagCountDate = true;
            }
        }
        $arrayPhase = count(array_unique($arrayPhase));
        // check exist 3 phase(A, B, C) in one date
        $arrayPhaseCheck = [];
        foreach($phases as $date => $phase){
            $arrayPhaseCheck[$date] = [];
            foreach($phase as $item){
                if( array_key_exists('zphase.zsym', $item) && !is_null($item['zphase.zsym']) ){
                    array_push($arrayPhaseCheck[$date], $item['zphase.zsym']);
                }
            }
        }
        foreach($arrayPhaseCheck as $date => $item){
            $arrayPhaseCheck[$date] = count(array_unique($item));
        }
        $flag = false;
        foreach($arrayPhaseCheck as $val){
            if($val != 3){
                $flag = true;
            }
        }
        $items = collect($items)->groupBy('groupDevice');
        $countGroup = $items->count();
        $countDate = count(array_unique($dates));
        $countDevice = count(array_unique($devices));
        // số biên bản
        $lengthOriginal = $count / 3;
        $lengthMode = $count % 3;
        // số ngày thiết bị theo biên bản
        $lengthDeviceOriginal = $countDevice / 3;
        $lengthDeviceMode = $countDevice % 3;
        // số ngày theo biên bản
        $lengthDateOriginal = $countDate;
        $errors = [];
        if($countGroup != 1 || $countGroup == 1 && empty($items->keys()->first()) ) {
            $errors[] = 'Các biên bản của các thiết bị đã chọn cần nằm trong cùng một group!';
        }else if( ($lengthOriginal < 1 && $lengthMode != 0) || ($lengthDeviceMode != 0 && $lengthDeviceOriginal < 1) || ($lengthOriginal == 1 && $lengthDateOriginal > 1 && $arrayPhase > 1 && $flag && $flagCountDate)){
            $errors[] = 'Cần chọn đủ 3 biên bản của 3 thiết bị và phải có cùng ngày thí nghiệm để xuất báo cáo!';
        }else if( ($lengthOriginal > 1 && $lengthMode != 0) || ($lengthDeviceMode != 0 && $lengthDeviceOriginal > 1) || $lengthDateOriginal > $lengthOriginal || $arrayPhase > 1 || $flag || $flagCountDate){
            $errors[] = 'Cần chọn đúng tiêu chí(thuộc 1 group, thuộc 3 thiết bị đầy đủ 3 pha và cùng ngày thí nghiệm) cho báo cáo qua các ngày thí nghiệm!';
        }
        return $errors;
    }
}
// validate report dot one (follow specs 3->13)
if( !function_exists('validateReportDotOne') ){
    function validateReportDotOne($ids){
        $devices = $errors = [];
        $attributes = [
            'name',
            'class.type',
            'id',
            'zCI_Device.name',
            'zCI_Device',
            'zlaboratoryDate',
        ];
        foreach($ids as $id){
            $whereClause = " id = U'".$id."' ";
            $item = getDataFromService('doSelect', 'nr', $attributes, $whereClause);
            array_push($devices, $item['zCI_Device']);
        }
        $countDevice = count(array_unique($devices));

        if($countDevice > 1){
            $errors[] = 'Chỉ được chọn các biên bản trong 1 thiết bị!';
        }
        return $errors;
    }
}
// remove sheet and hidden chart when export multi report
if( !function_exists('removeSheetAndHiddenChart') ){
    function removeSheetAndHiddenChart($spreadsheet, $sheetBD, $sheetName, $startIndexChart, $endIndexChart){
        libxml_disable_entity_loader(false);
        $sheetIndex = $spreadsheet->getIndex(
            $spreadsheet->getSheetByName($sheetName)
        );
        $spreadsheet->removeSheetByIndex($sheetIndex);
        // hidden row wrapper BD1 sheet $sheetBD
        for ($i = $startIndexChart; $i >= $endIndexChart; $i--) {
            $sheetBD->getRowDimension($i)->setVisible(false);
        }
    }
}

if( !function_exists('getDataExternalInspection') ){
    function getDataExternalInspection(array $ids){
        $items = [];
        $attributes = [
            'name',
            'class.type',
            'zlaboratoryDate'
        ];
        $attrsChild = [
            'z18cnochk',
            'z20cpichk',
            'z22cccchk',
            'z24cmbchk',
        ];
        foreach($ids as $id){
            $whereClause = " id = U'".$id."' ";
            $item = getDataFromService('doSelect', 'nr', $attributes, $whereClause);
            if( !empty($item) ){
                if( array_key_exists('zlaboratoryDate', $item) ){
                    $item['zlaboratoryDate'] = Carbon::createFromTimestamp($item['zlaboratoryDate'])->format('d/m/Y');
                }
                $objectType =  $objectType = 'zCA'.join('_', explode('.', substr($item['class.type'], 0, 4)));

                $data = getDataFromService('doSelect', $objectType, $attrsChild, $whereClause);
                $item['data'] = [
                    @$data['z18cnochk'] ? 'Pass' : 'Fail',
                    @$data['z20cpichk'] ? 'Pass' : 'Fail',
                    @$data['z22cccchk'] ? 'Pass' : 'Fail',
                    @$data['z24cmbchk'] ? 'Pass' : 'Fail',
                ];
                array_push($items, $item);
            }
        }

        return collect($items)->sortBy(function($item){
            return @$item['zlaboratoryDate'];
        })->toArray();
    }
}

if( !function_exists('getDataHighPressureShareReport') ){
    function getDataHighPressureShareReport(array $ids, string $title){
        $items = [];
        $attributes = [
            'name',
            'class.type',
            'id',
            'zlaboratoryDate'
        ];
        $attrsChild = [];
        switch ($title) {
            case 'dien-tro-tiep-xuc':
                $attrsChild = [
                    'z38mcrpanum',
                    'z39mcrpbnum',
                    'z40mcrpacnum',
                ];
                break;
            case 'thoi-gian-dong':
                $attrsChild = [
                    'z44ctmcpanum',
                    'z45ctmcpbnum',
                    'z46ctmcpcnum',
                ];
                break;
            case 'thoi-gian-tiep-xuc-o-che-do-co':
                $attrsChild = [
                    'z60osocpanum',
                    'z61osocpbnum',
                    'z62osocpcnum',
                ];
                break;
            default:
                $attrsChild = [
                    'z57osoccpanum',
                    'z58osoccpbnum',
                    'z59osoccpcnum',
                ];
                break;
        }

        foreach($ids as $id){
            $whereClause = " id = U'".$id."' ";
            $item = getDataFromService('doSelect', 'nr', $attributes, $whereClause);
            if( !empty($item) ){
                $objectType =  $objectType = 'zCA'.join('_', explode('.', substr($item['class.type'], 0, 4)));

                $data = getDataFromService('doSelect', $objectType, $attrsChild, $whereClause);
                $item['pha_a'] = @$data[$attrsChild[0]];
                $item['pha_b'] = @$data[$attrsChild[1]];
                $item['pha_c'] = @$data[$attrsChild[2]];
                array_push($items, $item);
            }
        }
        $items = collect($items)->sortBy('zlaboratoryDate')->toArray();
        foreach($items as $index => $item){
            if(array_key_exists('zlaboratoryDate', $item)) {
                $items[$index]['zlaboratoryDate'] = Carbon::createFromTimestamp($item['zlaboratoryDate'])->format('d/m/Y');
            }
        }
        return $items;
    }
}

if( !function_exists('getDataHighPressureShareReportDotTwo') ){
    function getDataHighPressureShareReportDotTwo(array $ids, string $title){
        $items = [];
        $attributes = [
            'name',
            'class.type',
            'id',
            'zlaboratoryDate',
            'zphase.zsym'
        ];
        $attrsChild = '';
        switch ($title) {
            case 'dien-tro-tiep-xuc':
                $attrsChild =  'z38mcrpastr';
                break;
            case 'thoi-gian-dong':
                $attrsChild =  'z38ctmcnum';
                break;
            case 'thoi-gian-tiep-xuc-o-che-do-co':
                $attrsChild =  'z60osocpanum';
                break;
            default:
                $attrsChild = 'z57osoccpanum';
                break;
        }

        foreach($ids as $id){
            $whereClause = " id = U'".$id."' ";
            $item = getDataFromService('doSelect', 'nr', $attributes, $whereClause);
            if( !empty($item) ){
                $objectType = 'zCA'.join('_', explode('.', substr($item['class.type'], 0, 4)));
                $data = getDataFromService('doSelect', $objectType, [ 'id', $attrsChild ], $whereClause);
                $item['phase'] = @$data[$attrsChild];
                array_push($items, $item);
            }
        }
        // sort by timestamp date and phase
        $items = collect($items)->sortBy('zlaboratoryDate')->sortBy(function($item){
            return @$item['zphase.zsym'];
        })->toArray();
        // Carbon convert timestamp to d/m/Y
        foreach($items as $index => $item){
            if( array_key_exists('zlaboratoryDate', $item) ){
                $items[$index]['zlaboratoryDate'] = Carbon::createFromTimestamp($item['zlaboratoryDate'])->format('d/m/Y');
            }
        }
        // group date after convert d/m/Y
        $items = collect($items)->groupBy('zlaboratoryDate')->toArray();
        $data = [];
        foreach($items as $date => $item){
            $array = [];
            $array['zlaboratoryDate'] = $date;
            foreach($item as $value){
                $array['phase']['phase_'.@$value['zphase.zsym'].'_'.Str::random(10)] = $value['phase'];
            }
            array_push($data, $array);
        }
        return $data;
    }
}

if( !function_exists('getDataContactTimeReportDotThree') ){
    function getDataContactTimeReportDotThree($ids){
        $items = [];
        $attributes = [
            'name',
            'class.type',
            'id',
            'zlaboratoryDate',
            'zphase.zsym'
        ];
        $attrsChild = [
            'z34mcr1num',
            'z35mcr2num',
            'z36mcr3num',
        ];

        foreach($ids as $id){
            $whereClause = " id = U'".$id."' ";
            $item = getDataFromService('doSelect', 'nr', $attributes, $whereClause);
            if( !empty($item) ){
                $objectType = 'zCA'.join('_', explode('.', substr($item['class.type'], 0, 4)));
                $data = getDataFromService('doSelect', $objectType, $attrsChild, $whereClause);
                $item['phase'] = @$data['z34mcr1num'].'_'.@$data['z35mcr2num'].'_'.@$data['z36mcr3num'];
                array_push($items, $item);
            }
        }
        $items = collect($items)->sortBy('zlaboratoryDate')->sortBy(function($item){
            return @$item['zphase.zsym'];
        })->toArray();

        foreach($items as $index => $item){
            if( array_key_exists('zlaboratoryDate', $item) ){
                $items[$index]['zlaboratoryDate'] = Carbon::createFromTimestamp($item['zlaboratoryDate'])->format('d/m/Y');
            }
        }
        $items = collect($items)->groupBy('zlaboratoryDate')->toArray();
        $data = [];
        foreach($items as $date => $item){
            $array = [];
            $array['zlaboratoryDate'] = $date;
            foreach($item as $value){
                $array['phase']['phase_'.@$value['zphase.zsym'].'_'.Str::random(10)] = $value['phase'];
            }
            array_push($data, $array);
        }
        return $data;
    }
}

// Fill data to sheet hight pressure report (follow specs 4.1, 6.1->8.1)
if( !function_exists('writeDataShareReportDotOne') ){
    function writeDataShareReportDotOne(array $data, $count, string $titleCategory, $spreadsheet){
        $sheetStatistical = $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
        $sheetChart1 = $spreadsheet->setActiveSheetIndexByName('DATA_BD1');
        $sheetChart2 = $spreadsheet->setActiveSheetIndexByName('DATA_BD2');
        $sheetBD = $spreadsheet->setActiveSheetIndexByName('BIEU_DO');
        $tableStatistical = $tableChart2 = ['B', 'D', 'E', 'F'];
        $tableChart1 = ['B', 'C', 'D', 'E'];
        $keyData = ['zlaboratoryDate', 'pha_a', 'pha_b', 'pha_c'];
        $startIndexRow = 5;
        foreach($data as $item){
            foreach($keyData as $index => $key){
                $sheetStatistical->getCell($tableStatistical[$index].$startIndexRow)->setValue(@$item[$key]);
                $sheetChart2->getCell($tableChart2[$index].$startIndexRow)->setValue(@$item[$key]);
                $sheetChart1->getCell($tableChart1[$index].$startIndexRow)->setValue(@$item[$key]);
            }
            $sheetStatistical->getCell("C{$startIndexRow}")->setValue($titleCategory);
            $startIndexRow++;
        }
        // set title chart
        $sheetBD->getChartByName('chart1')->setTitle(new \PhpOffice\PhpSpreadsheet\Chart\Title(@$data[0]['zlaboratoryDate']  . ' ' . $titleCategory));
        if($count > 1){
            removeSheetAndHiddenChart($spreadsheet, $sheetBD, 'DATA_BD1', 17, 4);
        }else{
            hiddenSheetsAndChart(['DATA_BD2'], $spreadsheet, $sheetBD, 33, 18);
        }
        alignmentExcel($sheetStatistical, 'C5:C1000');
    }
}

if( !function_exists('writeDataShareReportDotTwo') ){
    function writeDataShareReportDotTwo(array $data, $count, string $titleCategory, $spreadsheet){
        $sheetStatistical = $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
        $sheetChart1 = $spreadsheet->setActiveSheetIndexByName('DATA_BD1');
        $sheetChart2 = $spreadsheet->setActiveSheetIndexByName('DATA_BD2');
        $sheetBD = $spreadsheet->setActiveSheetIndexByName('BIEU_DO');
        $tableChart = ['C', 'D', 'E'];
        $tableChart2 = ['D', 'E', 'F'];
        $startIndexRow = 5;
        $startIndexRowChart = 5;
        foreach($data as $item){
            // Fill data sheet 'THONG_KE_DU_LIEU'
            $sheetStatistical->mergeCells("B{$startIndexRow}:B".($startIndexRow + 2))->getCell("B{$startIndexRow}")->setValue(@$item['zlaboratoryDate']);
            // Fill data sheet 'DATA_BD1'
            $sheetChart1->getCell("B{$startIndexRowChart}")->setValue(@$item['zlaboratoryDate']);
            // Fill data sheet 'DATA_BD2'
            $sheetChart2->getCell("B{$startIndexRowChart}")->setValue(@$item['zlaboratoryDate']);
            $sheetChart2->getCell("C{$startIndexRowChart}")->setValue($titleCategory);
            // paramater get index $tableChart, $tableChart2
            $j = 0;
            foreach($item['phase'] as $phase => $val){
                // Fill data sheet 'THONG_KE_DU_LIEU'
                $sheetStatistical->getCell("C{$startIndexRow}")->setValue(@explode('_', $phase)[1] ?? '');
                $sheetStatistical->getCell("D{$startIndexRow}")->setValue($titleCategory);
                $sheetStatistical->getCell("E{$startIndexRow}")->setValue($val);
                // Fill data sheet 'DATA_BD1'
                $sheetChart1->getCell($tableChart[$j].$startIndexRowChart)->setValue($val);
                // Fill data sheet 'DATA_BD2'
                $sheetChart2->getCell($tableChart2[$j].$startIndexRowChart)->setValue($val);
                $j++;
                $startIndexRow++;
            }
            $startIndexRowChart++;
        }

        if( $count > 1 ){
            removeSheetAndHiddenChart($spreadsheet, $sheetBD, 'DATA_BD1', 17, 4);
        }else{
            hiddenSheetsAndChart(['DATA_BD2'], $spreadsheet, $sheetBD, 33, 18);
        }
        alignmentExcel($sheetStatistical, 'D5:D1000');
    }
}
// get data for report 6.3->8.3
if( !function_exists('getDataHighPressureShareReportDotThree') ){
    function getDataHighPressureShareReportDotThree(array $ids, $title){
        $items = [];
        $attributes = [
            'name',
            'class.type',
            'id',
            'zlaboratoryDate',
            'zphase.zsym'
        ];
        $attrsChild = [];
        switch ($title) {
            case 'thoi-gian-dong':
                $attrsChild =  [
                    'z40ctmc1num',
                    'z4ctmc2num'
                ];
                break;
            case 'thoi-gian-tiep-xuc-o-che-do-co':
                $attrsChild =  [
                    'z52osco1num',
                    'z53osco2num'
                ];
                break;
            default:
                $attrsChild =  [
                    'z50osoco1num',
                    'z51osoco2num'
                ];
                break;
        }

        foreach($ids as $id){
            $whereClause = " id = U'".$id."' ";
            $item = getDataFromService('doSelect', 'nr', $attributes, $whereClause);
            if( !empty($item) ){
                $objectType = 'zCA'.join('_', explode('.', substr($item['class.type'], 0, 4)));
                $data = getDataFromService('doSelect', $objectType, $attrsChild, $whereClause);
                $item['phase'] = @$data[$attrsChild[0]].'_'.@$data[$attrsChild[1]];
                array_push($items, $item);
            }
        }
        $items = collect($items)->sortBy('zlaboratoryDate')->sortBy(function($item){
            return @$item['zphase.zsym'];
        })->toArray();
        foreach($items as $index => $item){
            if( array_key_exists('zlaboratoryDate', $item) ){
                $items[$index]['zlaboratoryDate'] = Carbon::createFromTimestamp($item['zlaboratoryDate'])->format('d/m/Y');
            }
        }
        $items = collect($items)->groupBy('zlaboratoryDate')->toArray();
        $data = [];
        foreach($items as $date => $item){
            $array = [];
            $array['zlaboratoryDate'] = $date;
            foreach($item as $value){
                $array['phase']['phase_'.@$value['zphase.zsym'].'_'.Str::random(10)] = $value['phase'];
            }
            array_push($data, $array);
        }
        return $data;
    }
}

// Fill excel share to report 6.3->8.3
if( !function_exists('writeDataShareReportDotThree') ){
    function writeDataShareReportDotThree(array $data, $count, string $titleCategory, $spreadsheet){
        // Follow sheet 'THONG_KE_DU_LIEU'
        $sheetStatistical =  $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
        $columnTableStatistical = ['E', 'F'];
        $startIndexRow = 5;
        // Follow sheet 'DATA_BD1'
        $sheetChart1 =  $spreadsheet->setActiveSheetIndexByName('DATA_BD1');
        $startIndexRowChart1 = 5;
        $columnTableChart1 = ['D', 'E'];
        // Follow sheet DATA_BD2
        $sheetChart2 =  $spreadsheet->setActiveSheetIndexByName('DATA_BD2');
        $startIndexRowChart2 = 5;
        $columnDateChart2 = range('D', 'I');
        $sheetBD =  $spreadsheet->setActiveSheetIndexByName('BIEU_DO');

        foreach($data as $values){
            // Fill data to sheet 'THONG_KE_DU_LIEU'
            $sheetStatistical->mergeCells("B{$startIndexRow}:B".($startIndexRow + 2))->getCell("B{$startIndexRow}")->setValue(@$values['zlaboratoryDate']);
            // Fill data 'DATA_BD1'
            $sheetChart1->mergeCells('B'.$startIndexRowChart1.':B'.($startIndexRowChart1 + 2))->getCell("B".$startIndexRowChart1)->setValue(@$values['zlaboratoryDate']);
            // variable help get the corresponding variable (vd: $tableChart1)
            foreach($values['phase'] as $phase => $value)
            {
                $valuePhase = explode('_', $value);
                // Fill data to sheet 'THONG_KE_DU_LIEU'
                $sheetStatistical->getCell("C{$startIndexRow}")->setValue(@explode('_', $phase)[1]);
                $sheetStatistical->getCell("D{$startIndexRow}")->setValue($titleCategory);
                // Fill data to sheet 'DATA_BD1'
                $sheetChart1->getCell("C{$startIndexRowChart1}")->setValue(@explode('_', $phase)[1]);
                foreach($columnTableStatistical as $index => $column){
                    $sheetStatistical->getCell($column.$startIndexRow)->setValue(@$valuePhase[$index]);
                    // Fill data to sheet 'THONG_KE_DU_LIEU'
                    $sheetChart1->getCell($columnTableChart1[$index].$startIndexRowChart1)->setValue(@$valuePhase[$index]);
                    // Fill data to sheet 'DATA_BD1'
                }
                $startIndexRow++;
                $startIndexRowChart1++;
            }
            // Fill data to sheet 'DATA_BD2'
            $dataChart2 = explode('_', (join('_', $values['phase'])));
            $sheetChart2->getCell("B{$startIndexRowChart2}")->setValue(@$values['zlaboratoryDate']);
            foreach($columnDateChart2 as $index => $column){
                $sheetChart2->getCell($column.$startIndexRowChart2)->setValue(@$dataChart2[$index]);
            }
            $startIndexRowChart2++;
        }
        if($count > 1){
            removeSheetAndHiddenChart($spreadsheet, $sheetBD, 'DATA_BD1', 17, 4);
        }else{
            hiddenSheetsAndChart(['DATA_BD2'], $spreadsheet, $sheetBD, 33, 18);
        }
        alignmentExcel($sheetStatistical, 'D5:D1000');
    }
}

if( !function_exists('getDataInsulationResistanceReportDotOne') ){
    function getDataInsulationResistanceReportDotOne(array $ids){
        $items = [];
        $attributes = [
            'name',
            'class.type',
            'id',
            'zlaboratoryDate',
            'zphase.zsym'
        ];
        $attrsChild = config('attributes.insulation_resistance_report_dot_one');
        foreach($ids as $id){
            $whereClause = " id = U'".$id."' ";
            $item = getDataFromService('doSelect', 'nr', $attributes, $whereClause);
            if( !empty($item) ){
                $objectType = 'zCA'.join('_', explode('.', substr($item['class.type'], 0, 4)));
                $data = getDataFromService('doSelect', $objectType, $attrsChild, $whereClause);
                // data for sheet statistical
                $item['data'][config('attributes.insulation_resistance_report_title.truoc_khi_thu')] = [
                    config('attributes.insulation_resistance_report_title.trang_thai_dong_voi_dat') =>  @$data[$attrsChild[0]].'_'.@$data[$attrsChild[1]].'_'.@$data[$attrsChild[2]],
                    config('attributes.insulation_resistance_report_title.trang_thai_mo') => @$data[$attrsChild[3]].'_'.@$data[$attrsChild[4]].'_'.@$data[$attrsChild[5]],
                ];
                $item['data'][config('attributes.insulation_resistance_report_title.sau_khi_thu')] = [
                    config('attributes.insulation_resistance_report_title.trang_thai_dong_voi_dat') => @$data[$attrsChild[6]].'_'.@$data[$attrsChild[7]].'_'.@$data[$attrsChild[8]],
                    config('attributes.insulation_resistance_report_title.trang_thai_mo') =>  @$data[$attrsChild[9]].'_'.@$data[$attrsChild[10]].'_'.@$data[$attrsChild[11]],
                ];
                // data for sheet chart
                $item['dataChart']['dong_voi_dat'] = [
                    config('attributes.insulation_resistance_report_title.title_chart_truoc_khi_thu') =>  @$data[$attrsChild[0]].'_'.@$data[$attrsChild[1]].'_'.@$data[$attrsChild[2]],
                    config('attributes.insulation_resistance_report_title.title_chart_sau_khi_thu') => @$data[$attrsChild[6]].'_'.@$data[$attrsChild[7]].'_'.@$data[$attrsChild[8]],
                ];
                $item['dataChart']['mo'] = [
                    config('attributes.insulation_resistance_report_title.title_chart_truoc_khi_thu') =>  @$data[$attrsChild[3]].'_'.@$data[$attrsChild[4]].'_'.@$data[$attrsChild[5]],
                    config('attributes.insulation_resistance_report_title.title_chart_sau_khi_thu') => @$data[$attrsChild[9]].'_'.@$data[$attrsChild[10]].'_'.@$data[$attrsChild[11]],
                ];
                array_push($items, $item);
            }
        }
        $items = collect($items)->sortBy('zlaboratoryDate')->sortBy(function($item){
            return @$item['zphase.zsym'];
        })->toArray();

        foreach($items as $index => $item){
            if( array_key_exists('zlaboratoryDate', $item) ){
                $items[$index]['zlaboratoryDate'] = Carbon::createFromTimestamp($item['zlaboratoryDate'])->format('d/m/Y');
            }
        }
        return $items;
    }
}

if( !function_exists('getDataInsulationResistanceReportDotTwo') ){
    function getDataInsulationResistanceReportDotTwo(array $ids){
        $items = [];
        $attributes = [
            'name',
            'class.type',
            'zlaboratoryDate',
            'zphase.zsym'
        ];
        $attrsChild = [
            // trip 1
            'z29bhvtpastr',
            'z30bhvtpbstr',
            // trip 2
            'z87atcstr',
            'z88atcstr'
        ];
        foreach($ids as $id){
            $whereClause = " id = U'".$id."' ";
            $item = getDataFromService('doSelect', 'nr', $attributes, $whereClause);
            if( !empty($item) ){
                $objectType =  $objectType = 'zCA'.join('_', explode('.', substr($item['class.type'], 0, 4)));
                $data = getDataFromService('doSelect', $objectType, $attrsChild, $whereClause);
                $item['data'][config('attributes.insulation_resistance_report_title.truoc_khi_thu')] = [
                    config('attributes.insulation_resistance_report_title.trang_thai_dong_voi_dat') => @$data['z29bhvtpastr'],
                    config('attributes.insulation_resistance_report_title.trang_thai_mo') => @$data['z30bhvtpbstr'],
                ];
                $item['data'][config('attributes.insulation_resistance_report_title.sau_khi_thu')] = [
                    config('attributes.insulation_resistance_report_title.trang_thai_dong_voi_dat') => @$data['z87atcstr'],
                    config('attributes.insulation_resistance_report_title.trang_thai_mo') => @$data['z88atcstr'],
                ];
                array_push($items, $item);
            }
        }
        $items = collect($items)->sortBy('zlaboratoryDate')->sortBy(function($item){
            return @$item['zphase.zsym'];
        })->toArray();
        foreach($items as $index => $item){
            if(array_key_exists('zlaboratoryDate', $item)){
                $items[$index]['zlaboratoryDate'] = Carbon::createFromTimestamp($item['zlaboratoryDate'])->format('d/m/Y');
            }
        }
        $items = collect($items)->groupBy('zlaboratoryDate')->toArray();
        foreach($items as $date => $item){
            $phaseA = array_values($item[0]['data']);
            $phaseB = array_values($item[1]['data']);
            $phaseC = array_values($item[2]['data']);
            $items[$date]['dataChart']['dong_voi_dat'] = [
                config('attributes.insulation_resistance_report_title.title_chart_truoc_khi_thu') =>  array_values($phaseA[0])[0].'_'.array_values($phaseB[0])[0].'_'.array_values($phaseC[0])[0],
                config('attributes.insulation_resistance_report_title.title_chart_sau_khi_thu') => array_values($phaseA[1])[0].'_'.array_values($phaseB[1])[0].'_'.array_values($phaseC[1])[0]
            ];
            $items[$date]['dataChart']['mo'] = [
                config('attributes.insulation_resistance_report_title.title_chart_truoc_khi_thu') =>  array_values($phaseA[0])[1].'_'.array_values($phaseB[0])[1].'_'.array_values($phaseC[0])[1],
                config('attributes.insulation_resistance_report_title.title_chart_sau_khi_thu') => array_values($phaseA[1])[1].'_'.array_values($phaseB[1])[1].'_'.array_values($phaseC[1])[1],
            ];
        }
        return $items;
    }
}

if( !function_exists('getDataInsulationResistanceReportDotThree') ){
    function getDataInsulationResistanceReportDotThree(array $ids) {
        $items = [];
        $attributes = [
            'name',
            'class.type',
            'zlaboratoryDate',
            'zphase.zsym'
        ];
        $attrsChild = config('attributes.insulation_resistance_report_dot_three');
        foreach($ids as $id){
            $whereClause = " id = U'".$id."' ";
            $item = getDataFromService('doSelect', 'nr', $attributes, $whereClause);
            if( !empty($item) ){
                $objectType =  $objectType = 'zCA'.join('_', explode('.', substr($item['class.type'], 0, 4)));
                $data = getDataFromService('doSelect', $objectType, $attrsChild, $whereClause);
                $item['data'][config('attributes.insulation_resistance_report_title.truoc_khi_thu')] = [
                    config('attributes.insulation_resistance_report_title.trang_thai_dong_voi_dat') => @$data['z29bhvtstr'].'_'.@$data['z95bhcstr'],
                    config('attributes.insulation_resistance_report_title.trang_thai_mo') => @$data['z30ahvtstr'].'_'.@$data['z96bhcstr'],
                ];
                $item['data'][config('attributes.insulation_resistance_report_title.sau_khi_thu')] = [
                    config('attributes.insulation_resistance_report_title.trang_thai_dong_voi_dat') => @$data['z97atcstr'].'_'.@$data['z98atcstr'],
                    config('attributes.insulation_resistance_report_title.trang_thai_mo') => @$data['z99atostr'].'_'.@$data['z100atostr'],
                ];
                array_push($items, $item);
            }
        }
        $items = collect($items)->sortBy('zlaboratoryDate')->sortBy(function($item){
            return @$item['zphase.zsym'];
        })->toArray();
        foreach($items as $index => $item){
            if(array_key_exists('zlaboratoryDate', $item)){
                $items[$index]['zlaboratoryDate'] = Carbon::createFromTimestamp($item['zlaboratoryDate'])->format('d/m/Y');
            }
        }
        $items = collect($items)->groupBy('zlaboratoryDate')->toArray();

        foreach($items as $date => $item){
            // phase A
            $phaseA = array_values($item[0]['data']);
            // Phase A before test
            $phaseABefore = array_values($phaseA[0]);
            // Phase A before test => index 0 => cutting charber 1, index 1 => cutting charber 2
            $phaseABeforeCuttingChamberClose = explode('_', $phaseABefore[0]);
            $phaseABeforeCuttingChamberOpen = explode('_', $phaseABefore[1]);
            // Phase A after test
            $phaseAAfter = array_values($phaseA[1]);
            // Phase A before test => index 0 => cutting charber 1, index 1 => cutting charber 2
            $phaseAAfterCuttingChamberClose = explode('_', $phaseAAfter[0]);
            $phaseAAfterCuttingChamberOpen = explode('_', $phaseAAfter[1]);

            // Phase B
            $phaseB = array_values($item[1]['data']);
            // Phase B before test
            $phaseBBefore = array_values($phaseB[0]);
            // Phase B before test (status close, open) => index 0 => cutting charber 1, index 1 => cutting charber 2
            $phaseBBeforeCuttingChamberClose = explode('_', $phaseBBefore[0]);
            $phaseBBeforeCuttingChamberOpen = explode('_', $phaseBBefore[1]);
            // Phase B after test
            $phaseBAfter = array_values($phaseB[1]);
            // Phase B after test (status close, open) => index 0 => cutting charber 1, index 1 => cutting charber 2
            $phaseBAfterCuttingChamberClose = explode('_', $phaseBAfter[0]);
            $phaseBAfterCuttingChamberOpen = explode('_', $phaseBAfter[1]);

            // Phase C
            $phaseC = array_values($item[2]['data']);
            // Phase C before test
            $phaseCBefore = array_values($phaseC[0]);
            // Phase C before test (status close, open) => index 0 => cutting charber 1, index 1 => cutting charber 2
            $phaseCBeforeCuttingChamberClose = explode('_', $phaseCBefore[0]);
            $phaseCBeforeCuttingChamberOpen = explode('_', $phaseCBefore[1]);
            // Phase C after test
            $phaseCAfter = array_values($phaseC[1]);
            // Phase C after test (status close, open) => index 0 => cutting charber 1, index 1 => cutting charber 2
            $phaseCAfterCuttingChamberClose = explode('_', $phaseCAfter[0]);
            $phaseCAfterCuttingChamberOpen = explode('_', $phaseCAfter[1]);

            // data chart 1
            $items[$date]['dataChart1']['dong_voi_dat'] = [
                'Trước khi thử cao áp(MΩ)' =>  $phaseABeforeCuttingChamberClose[0].'_'.$phaseBBeforeCuttingChamberClose[0].'_'.$phaseCBeforeCuttingChamberClose[0].'_'.$phaseABeforeCuttingChamberClose[1].'_'.$phaseBBeforeCuttingChamberClose[1].'_'.$phaseCBeforeCuttingChamberClose[1],
                'Sau khi thử cao áp(MΩ)' => $phaseAAfterCuttingChamberClose[0].'_'.$phaseBAfterCuttingChamberClose[0].'_'.$phaseCAfterCuttingChamberClose[0].'_'.$phaseAAfterCuttingChamberClose[1].'_'.$phaseBAfterCuttingChamberClose[1].'_'.$phaseCAfterCuttingChamberClose[1],
            ];
            $items[$date]['dataChart1']['mo'] = [
                'Trước khi thử cao áp(MΩ)' =>  $phaseABeforeCuttingChamberOpen[0].'_'.$phaseBBeforeCuttingChamberOpen[0].'_'.$phaseCBeforeCuttingChamberOpen[0].'_'.$phaseABeforeCuttingChamberOpen[1].'_'.$phaseBBeforeCuttingChamberOpen[1].'_'.$phaseCBeforeCuttingChamberOpen[1],
                'Sau khi thử cao áp(MΩ)' => $phaseAAfterCuttingChamberOpen[0].'_'.$phaseBAfterCuttingChamberOpen[0].'_'.$phaseCAfterCuttingChamberOpen[0].'_'.$phaseAAfterCuttingChamberOpen[1].'_'.$phaseBAfterCuttingChamberOpen[1].'_'.$phaseCAfterCuttingChamberOpen[1],
            ];
            // data chart 2,3
            $items[$date]['dataChart23']['dong_voi_dat'] = [
                'phase_a' => $phaseABeforeCuttingChamberClose[0].'_'.$phaseAAfterCuttingChamberClose[0].'_'.$phaseABeforeCuttingChamberClose[1].'_'.$phaseAAfterCuttingChamberClose[1],
                'phase_b' => $phaseBBeforeCuttingChamberClose[0].'_'.$phaseBAfterCuttingChamberClose[0].'_'.$phaseBBeforeCuttingChamberClose[1].'_'.$phaseBAfterCuttingChamberClose[1],
                'phase_c' =>  $phaseCBeforeCuttingChamberClose[0].'_'.$phaseCAfterCuttingChamberClose[0].'_'.$phaseCBeforeCuttingChamberClose[1].'_'.$phaseCAfterCuttingChamberClose[1],
            ];
            $items[$date]['dataChart23']['mo'] = [
                'phase_a' => $phaseABeforeCuttingChamberOpen[0].'_'.$phaseAAfterCuttingChamberOpen[0].'_'.$phaseABeforeCuttingChamberOpen[1].'_'.$phaseAAfterCuttingChamberOpen[1],
                'phase_b' => $phaseBBeforeCuttingChamberOpen[0].'_'.$phaseBAfterCuttingChamberOpen[0].'_'.$phaseBBeforeCuttingChamberOpen[1].'_'.$phaseBAfterCuttingChamberOpen[1],
                'phase_c' =>  $phaseCBeforeCuttingChamberOpen[0].'_'.$phaseCAfterCuttingChamberOpen[0].'_'.$phaseCBeforeCuttingChamberOpen[1].'_'.$phaseCAfterCuttingChamberOpen[1],
            ];
        }
        return $items;
    }
}
// write data sheet 'BANG_DANH_GIA' report 4->8
if( !function_exists('writeDataSheetBDG') ){
    function writeDataSheetBDG(array $data, $spreadsheet){
        // group by title category and count total row merge pre day
        foreach($data as $date => $item){
            $data[$date] = collect($item)->groupBy('category')->toArray();
            $data[$date]['mergeCell'] = count($item) - 1;
        }
        $startIndexRow = 6;
        $columns = range('D', 'K');
        foreach($data as $date => $items){
            $cellMerge = $items['mergeCell'];
            unset($items['mergeCell']);
            $spreadsheet->mergeCells("B{$startIndexRow}:B".($startIndexRow + $cellMerge))->getCell("B{$startIndexRow}")->setValue(is_string($date) ? $date : '');
            foreach($items as $title => $values){
                $spreadsheet->mergeCells("C{$startIndexRow}:C".($startIndexRow + count($values) - 1))->getCell("C{$startIndexRow}")->setValue($title);
                foreach($values as $value){
                    unset($value['category']);
                    $value = array_values($value);
                    foreach($columns as $index => $column){
                        $spreadsheet->getCell($column.$startIndexRow)->setValue($value[$index]);
                    }
                    $startIndexRow++;
                }
            }
        }
    }
}

if( !function_exists('getDataShareReport') ){
    function getDataShareReport(array $ids, $classType, $title){
        $attributes = [
            'zlaboratoryDate',
            'zrefnr_dvql.zsym',
            'zrefnr_td.zsym',
            'zCI_Device.name',
            'zCI_Device.zStage.zsym',
        ];
        switch ($title) {
            case 'dac-tinh-dien-tro-rong':
                $attrs = [
                    'zCA10_1' => 'z105dcrmchk',
                    'zCA10_2' => 'z79dcrmchk',
                    'zCA10_3' => 'z86dcrmchk',
                ];
                break;
            case 'dac-tinh-hanh-trinh':
                $attrs = [
                    'zCA10_1' => 'z108ctmmchk',
                    'zCA10_2' => 'z82ctmmchk',
                    'zCA10_3' => 'z89ctmmchk',
                ];
                break;
            case 'phong-dien-cuc-bo':
                $attrs = [
                    'zCA10_1' => 'z111pdmchk',
                    'zCA10_2' => 'z84pdmchk',
                    'zCA10_3' => 'z92pdmchk',
                ];
                break;
            default:
                $attrs = [
                    'zCA10_1' => 'z99fttchk',
                    'zCA10_2' => 'z75fttchk',
                    'zCA10_3' => 'z82fttchk',
                ];
                break;
        }
        $object = 'zCA'.join('_', explode('.', substr($classType, 0, 4)));
        $attrs2 = ['id', $attrs[$object]];
        $items = [];
        foreach($ids as $id){
            $whereClause = "id = U'".$id."'";
            $item = getDataFromService('doSelect', 'nr', $attributes, $whereClause);
            $assocData = getDataFromService('doSelect', $object, $attrs2, $whereClause);
            $item[$attrs[$object]] = @$assocData[$attrs[$object]] ? 'Pass' : 'Fail';
            array_push($items, $item);
        }
        $items = collect($items)->sortBy('zlaboratoryDate')->toArray();
        foreach($items as $index => $item){
            $items[$index]['zlaboratoryDate'] = !empty($item['zlaboratoryDate']) ? date('d/m/Y', $item['zlaboratoryDate']) : '';
        }

        array_push($attributes, $attrs[$object]);
        return [
            'data' => $items,
            'attrs' => $attributes
        ];
    }
}

if( !function_exists('filterDevice') ){
    function filterDevice(array $deviceIds, $request, $classTypes = [], $flagFilterReport = false, $whereClauseAppend = '', $attrsCustom = [], $attrsExtend = [], $flagUnsetDevice = false){
        $whereClause = " class.zetc_type != 1 AND delete_flag = 0" .( $whereClauseAppend ?? '' );
        // single or array device id device
        if( !empty($deviceIds) ){
            foreach ($deviceIds as $key => $val) {
                if ($key == 0) {
                    $whereClause .= " AND class IN (";
                }
                $whereClause .= $val;
                if ($key < count($deviceIds) - 1) {
                    $whereClause .= ",";
                } else {
                    $whereClause .= ")";
                }
            }
        }

        // Mảng tên thiết bị
        if( $request->deviceNames ){
            foreach ($request->deviceNames as $key => $val) {
                if($key == 0){
                    $whereClause .= " AND ( name = '".$val."'";
                }
                if ($key < count($request->deviceNames) && $key != 0) {
                    $whereClause .= " OR name = '".$val."'";
                }
                if($key == count($request->deviceNames) -1 ){
                    $whereClause .= ")";
                }
            }
        }
        // Đơn vị quản lý
        if($request->dvql){
            $whereClause .= " AND zrefnr_dvql = ".$request->dvql;
        }
        // Khu vực
        if($request->area){
            $whereClause .= " AND zArea = " . $request->area;
        }
        // Trạng thái
        if($request->zStage){
            $whereClause .= " AND zStage = " . $request->zStage;
        }
        // Trạm/ Nhà máy
        if($request->td){
            $whereClause .= " AND zrefnr_td.zsym LIKE '%".$request->td."%'";
        }
        // Ngăn lộ, Hệ thống
        if($request->nl){
            $whereClause .= " AND zrefnr_nl.zsym LIKE '%".$request->nl."%'";
        }
        // Thiết bị
        if($request->device){
            $whereClause .= " AND name LIKE '%".$request->device."%'";
        }
        // Hãng sản xuất
        if($request->manufacture){
            $whereClause .= " AND zManufacturer.zsym = '".$request->manufacture."'";
        }
        if($request->manufacture_id){
            $whereClause .= " AND zManufacturer = " . $request->manufacture_id;
        }
        // multi id manufacturer
        if($request->manufacture_ids){
            foreach ($request->manufacture_ids as $key => $val) {
                if ($key == 0) {
                    $whereClause .= " AND zManufacturer IN (";
                }
                $whereClause .= $val;
                if ($key < count($request->manufacture_ids) - 1) {
                    $whereClause .= ",";
                } else {
                    $whereClause .= ")";
                }
            }
        }
        // Kiểu thiết bị
        if( $request->type ){
            $whereClause .= " AND zCI_Device_Kind.zsym LIKE '%".$request->type."%'";
        }
        // Số chế tạo
        if($request->serial_number){
            $whereClause .= " AND serial_number LIKE '%".$request->serial_number."%'";
        }
        // Năm sản xuất
        if($request->zYear_of_Manafacture){
            $whereClauseManafacture = " zsym = '".$request->zYear_of_Manafacture."'";
            $data = getDataFromService('doSelect', 'zYear_of_Manafacture', ['zsym', 'id'], $whereClauseManafacture);
            $whereClause .= " AND zYear_of_Manafacture = ".$data['id']."";
        }
        // Nước sản xuất
        if($request->country){
            $whereClause .= " AND zCountry.name LIKE '%".$request->country."%'";
        }
        // Chủng loại
        if( $request->deviceType ){
            $whereClause .= " AND zCI_Device_Type = ". $request->deviceType;
        }
        if( $request->deviceTypes && is_array($request->deviceTypes) ){
            $whereClause .= " AND zCI_Device_Type IN (". join(',', $request->deviceTypes).")";
        }

        if($request->startYear){
            $whereClause .= " AND zYear_of_Manafacture >= ". $request->startYear;
        }
        if($request->endYear){
            $whereClause .= " AND zYear_of_Manafacture <= ". $request->endYear;
        }

        if($request->dvqls){
            $whereClause .= " AND zrefnr_dvql IN (".join(',',$request->dvqls).")";
        }

        if($request->deviceKind){
            $whereClause .= " AND zCI_Device_Kind.zsym LIKE '%".$request->deviceKind."%'";
        }

        // Quy trình thí nghiệm

        $attributes = $attrsCustom ?: [
            'id',
            'name',
            'zCustomer.zsym',
            'zrefnr_td.zsym',
            'zrefnr_nl.zsym',
            'zManufacturer.zsym',
            'zCI_Device_Kind.zsym',
            'serial_number',
            'zYear_of_Manafacture.zsym',
            'zCountry.name',
            'zphase.zsym',
            'zCI_Device_Type.zsym',
            'zArea.zsym',
            'zrefnr_dvql.zsym',
        ];
        $items = getAllDataFromService('nr', $attributes, $whereClause);
        if( !empty($classTypes) ){
            $reports = collect(getAllReportsFreeListHanlde($classTypes, $request))->pluck('zCI_Device')->toArray();
            foreach($items as $index => $item){
                if( !in_array($item['id'], $reports) ){
                    unset($items[$index]);
                }
            }
        }else{
            // filter list device has report
            if($flagFilterReport && ($request->start_date || $request->end_date)){
                foreach($items as $index => $item){
                    $whereClauseReport = " zCI_Device = U'".$item['id']."' AND class.zetc_type = 1 AND delete_flag = 0";
                    if($request->start_date){
                        $whereClauseReport .= " AND zlaboratoryDate >= " . strtotime($request->start_date);
                    }
                    if($request->end_date){
                        $whereClauseReport .= " AND zlaboratoryDate < " . strtotime("+1 day", strtotime($request->end_date));
                    }
                    $result = getDataFromService('doSelect', 'nr', ['id', 'name'], $whereClauseReport);
                    if(empty($result)){
                        unset($items[$index]);
                    }
                }
            }
        }
        // multi name manufacturer
        if($request->manufactures){
            $items = [];
            foreach ($request->manufactures as $key => $val) {
                $whereClauseManufacture = $whereClause . " AND zManufacturer.zsym LIKE '%".$val."%'";
                $item = getDataFromService('doSelect', 'nr', $attributes, $whereClauseManufacture, 150);
                if( !empty($item) ){
                    $items = array_merge($items, $item);
                }
            }
        }
        // get data extend
        if( !empty($attrsExtend) ){
            foreach($items as $index => $item){
                $whereClauseDevice = " id = U'".$item['id']."' ";
                // công suất
                if($request->zcapacity){
                    $whereClauseDevice .= " AND zcapacity LIKE '%".$request->zcapacity."%'";
                }
                // tố đầu dây
                if($request->ztodauday){
                    $whereClauseDevice .= " AND zvector_group LIKE '%".$request->ztodauday."%'";
                }
                // Điện áp định mức
                if($request->zdienapdinhmuc){
                    $whereClauseDevice .= " AND zdienapdinhmuc LIKE '%".$request->zdienapdinhmuc."%'";
                }
                // Điện áp định mức
                if($request->zdongdiendinhmuc){
                    $whereClauseDevice .= " AND zdongdiendinhmuc = '".$request->zdongdiendinhmuc."'";
                }
                $device = getDataFromService('doSelect', 'zETC_Device', $attrsExtend, $whereClauseDevice);
                if($flagUnsetDevice && empty($device)){
                    unset($items[$index]);
                    continue;
                }
                foreach($attrsExtend as $attr){
                    $items[$index][$attr] = @$device[$attr];
                }
            }
        }
        return $items;
    }
}

if( !function_exists('getDataHighPressureTransformers') ){
    function getDataHighPressureTransformers($request){
        $classTypes = [
            '13.1. BBTN MBA 2 cuộn dây, 1 cuộn cân bằng',
            '13.2. BBTN MBA 2 cuộn dây',
            '13.3 BBTN MBA 3 cuộn dây, chuyên nấc hạ áp',
            '13.4. BBTN MBA 3 cuộn dây, chuyên nấc trung áp',
            '13.5. BBTN MBA 3 cuộn dây, tự ngẫu',
            '13.6. BBTN MBA 4 cuộn dây',
        ];
        $items = filterDevice([array_flip(config('constant.device_statistical'))['Máy biến áp']], $request, $classTypes, true);
        foreach($items as $index => $item){
            $whereClauseDevice = " id = U'".$item['id']."' ";
            // công suất
            if($request->zcapacity){
                $whereClauseDevice .= " AND zcapacity LIKE '%".$request->zcapacity."%'";
            }
            // tố đầu dây
            if($request->ztodauday){
                $whereClauseDevice .= " AND zvector_group LIKE '%".$request->ztodauday."%'";
            }
            // Điện áp định mức
            if($request->zdienapdinhmuc){
                $whereClauseDevice .= " AND zdienapdinhmuc = '".$request->zdienapdinhmuc."'";
            }
            $device = getDataFromService('doSelect', 'zETC_Device', ['zdienapdinhmuc', 'id'], $whereClauseDevice);
            if( empty($device) ){
                unset($items[$index]);
            }
        }
        return $items;
    }
}

if( !function_exists('getReportsHighPressureTransformers') ){
    function getReportsHighPressureTransformers($id, $request = null){
        $whereClauseBase = " zCI_Device = U'".$id."' AND class.zetc_type = 1 AND class.type LIKE '%13.4. BBTN MBA 3 cuộn dây, chuyên nấc trung áp%'";
        $classTypes = [
            '13.1. BBTN MBA 2 cuộn dây, 1 cuộn cân bằng',
            '13.2. BBTN MBA 2 cuộn dây',
            '13.3 BBTN MBA 3 cuộn dây, chuyên nấc hạ áp',
            // '13.4. BBTN MBA 3 cuộn dây, chuyên nấc trung áp',
            '13.5. BBTN MBA 3 cuộn dây, tự ngẫu',
            '13.6. BBTN MBA 4 cuộn dây',
        ];
        foreach ($classTypes as $key => $val) {
            if ($key == 0) {
                $whereClause = " class.type IN (";
            }
            $whereClause .= "'" . $val . "'";
            if ($key < count($classTypes) - 1) {
                $whereClause .= ",";
            } else {
                $whereClause .= ")";
            }
        }

        if( !is_null($request) && $request->startDate ){
            $whereClause .= " AND zlaboratoryDate >= " . strtotime($request->startDate);
            $whereClauseBase .= " AND zlaboratoryDate >= " . strtotime($request->startDate);
        }

        if( !is_null($request) && $request->endDate ){
            $whereClause .= " AND zlaboratoryDate < " . strtotime("+1 day", strtotime($request->endDate));
            $whereClauseBase .= " AND zlaboratoryDate < " . strtotime("+1 day", strtotime($request->endDate));
        }
        $whereClause .= " AND zCI_Device = U'".$id."' AND class.zetc_type = 1";
        $attributes = [
            'id',
            'class.type',
            'zlaboratoryDate',
            'name',
            'zCI_Device.name',
            'zArea.zsym',
            'zrefnr_td.zsym',
            'zrefnr_nl.zsym',
            'zCI_Device_Type.zsym',
            'zManufacturer.zsym',
            'zCI_Device.serial_number',
            'zYear_of_Manafacture.zsym',
            'zCountry.name',
            'zCI_Device.zCI_Device_Kind.zsym',
            'zCI_Device.zArea.zsym'
        ];
        $reports  =  getDataFromService('doSelect', 'nr', $attributes, $whereClause, 150);
        $data = getDataFromService('doSelect', 'nr', $attributes, $whereClauseBase, 100);
        $reports = array_merge($reports, $data);
        $reports = collect($reports)->sortBy('zlaboratoryDate')->toArray();
        foreach($reports as $index => $report){
            $reports[$index]['zlaboratoryDate'] = @$report['zlaboratoryDate'] ? date('d/m/Y', $report['zlaboratoryDate']) : '';
        }
        return $reports;
    }
}

if( !function_exists('getDataReportHighPressureTransformers') ){
    function getDataReportHighPressureTransformers(array $ids){
        $reports = [];
        $attributes = [
            'id',
            'class.type',
            'zlaboratoryDate',
            'zCI_Device.zStage'
        ];
        foreach($ids as $id){
            $whereClause = " id = U'".$id."' ";
            $report = getDataFromService('doSelect', 'nr', $attributes, $whereClause);
            array_push($reports, $report);
        }

        $reports = collect($reports)->sortBy('zlaboratoryDate')->toArray();
        foreach($reports as $index => $report){
            $reports[$index]['zlaboratoryDate'] = @$report['zlaboratoryDate'] ? date('d/m/Y', $report['zlaboratoryDate']) : '';
        }
        return $reports;
    }
}

if( !function_exists('getDataHighPressureTransformersDevice') ){
    function getDataHighPressureTransformersDevice($ids){
        $reports = getDataReportHighPressureTransformers($ids);
        foreach($reports as $index => $report){
            $objectType = 'zCA'.join('_', explode('.', substr($report['class.type'], 0, 4)));
            $attrsChild = config('attributes.attributes_overview_check_report.'.$objectType) ??  config('attributes.attributes_overview_check_report')[0];
            $attrs = getAttributesOverviewCheckReport($report['class.type']);
            $attrsChild = array_merge($attrsChild, $attrs);
            if($objectType == 'zCA13_6'){
                $objectType = 'nr';
            }
            $whereClauseReport = " id = U'".$report['id']."' ";
            $data = getDataFromService('doSelect', $objectType, $attrsChild, $whereClauseReport);
            unset($reports[$index]['handle_id']);
            unset($reports[$index]['id']);
            unset($reports[$index]['class.type']);
            array_push(
                $reports[$index],
                config('attributes.label_overview_check_report.label_1').'_'.config('attributes.label_overview_check_report.label_2').'_'.( @$data[$attrsChild[0]] ? 'Pass' : 'Fail'),
                config('attributes.label_overview_check_report.label_1').'_'.config('attributes.label_overview_check_report.label_3').'_'.( @$data[$attrsChild[1]] ? 'Pass' : 'Fail'),
                config('attributes.label_overview_check_report.label_1').'_'.(config('attributes.label_overview_check_report.label_4_'.$objectType) ?? config('attributes.label_overview_check_report.label_4')).'_'.( @$data[$attrsChild[2]] ? 'Pass' : 'Fail'),
                config('attributes.label_overview_check_report.label_1').'_'.config('attributes.label_overview_check_report.label_5').'_'.( @$data[$attrsChild[3]] ? 'Pass' : 'Fail'),
                config('attributes.label_overview_check_report.label_1').'_'.config('attributes.label_overview_check_report.label_6').'_'.( @$data[$attrsChild[4]] ? 'Pass' : 'Fail'),

                config('attributes.label_overview_check_report.label_7').'_'.config('attributes.label_overview_check_report.label_8').'_'.( @$data[$attrsChild[5]] ? 'Pass' : 'Fail'),
                config('attributes.label_overview_check_report.label_7').'_'.config('attributes.label_overview_check_report.label_9').'_'.( @$data[$attrsChild[6]] ? 'Pass' : 'Fail'),
                config('attributes.label_overview_check_report.label_7').'_'.config('attributes.label_overview_check_report.label_10').'_'.( @$data[$attrsChild[7]] ? 'Pass' : 'Fail'),

                config('attributes.label_overview_check_report.label_11').'_'.config('attributes.label_overview_check_report.label_12').'_'.( @$data[$attrsChild[8]] ? 'Pass' : 'Fail'),
                config('attributes.label_overview_check_report.label_11').'_'.config('attributes.label_overview_check_report.label_13').'_'.( @$data[$attrsChild[9]] ? 'Pass' : 'Fail'),

                config('attributes.label_overview_check_report.label_14').'_'.config('attributes.label_overview_check_report.label_15').'_'.( @$data[$attrsChild[10]] ? 'Pass' : 'Fail'),
                config('attributes.label_overview_check_report.label_14').'_'.config('attributes.label_overview_check_report.label_16').'_'.( @$data[$attrsChild[11]] ? 'Pass' : 'Fail'),

                config('attributes.label_overview_check_report.label_17').'_'.config('attributes.label_overview_check_report.label_18').'_'.( @$data[$attrs[0]] ? 'Pass' : 'Fail'),

                config('attributes.label_overview_check_report.label_19').'_'.config('attributes.label_overview_check_report.label_20').'_'.( @$data[$attrs[1]] ? 'Pass' : 'Fail'),

                config('attributes.label_overview_check_report.label_23').'_'.config('attributes.label_overview_check_report.label_24').'_'.( @$data[$attrsChild[12]] ? 'Pass' : 'Fail'),
                config('attributes.label_overview_check_report.label_23').'_'.config('attributes.label_overview_check_report.label_25').'_'.( @$data[$attrsChild[13]] ? 'Pass' : 'Fail'),
                config('attributes.label_overview_check_report.label_23').'_'.config('attributes.label_overview_check_report.label_26').'_'.( @$data[$attrsChild[14]] ? 'Pass' : 'Fail'),

                config('attributes.label_overview_check_report.label_27').'_'.config('attributes.label_overview_check_report.label_28').'_'.( @$data[$attrs[2]] ? 'Pass' : 'Fail'),
            );
        }
        return $reports;
    }
}

if( !function_exists('getAttributesOverviewCheckReport') ){
    function getAttributesOverviewCheckReport($classType){
        $attrs = [];
        switch ($classType) {
            case '13.1. BBTN MBA 2 cuộn dây, 1 cuộn cân bằng':
            case '13.3 BBTN MBA 3 cuộn dây, chuyên nấc hạ áp':
            case '13.5. BBTN MBA 3 cuộn dây, tự ngẫu':
                $attrs = [ 'zGI_cosg', 'zGI_cocs', 'zGI_cocc' ];
                break;
            case '13.2. BBTN MBA 2 cuộn dây':
                $attrs = [ 'zGI_CSG_CSG_P', 'zGI_CCS_CFC_P', 'zcocccheck' ];
                break;
            case '13.4. BBTN MBA 3 cuộn dây, chuyên nấc trung áp':
                $attrs = [ 'zGI_CCS_POVOP_P', 'zGI_CCS_OPC_P', 'zGI_CSG_CSG_P' ];
                break;
            default:
                $attrs = [ 'z36dlcd', 'z35dlkt', 'z34dlcd' ];
                break;
        }
        return $attrs;
    }
}

if( !function_exists('getDataShareReportTransformers') ){
    function getDataShareReportTransformers(array $ids, $title){
        $attributes = [
            'class.type',
            'zlaboratoryDate',
            'zCI_Device.zrefnr_dvql.zsym',
            'zCI_Device.zrefnr_td.zsym',
            'zCI_Device.name',
            'zCI_Device.zStage.zsym',
        ];
        switch ($title) {
            case 'dac-tinh-dap-ung-tan-so-quet':
                $attrs = 'zMSFRA_Re';
                break;
            case 'do-ham-luong-am-trong-cach-dien-ran';
                $attrs = 'zMDFR_Re';
                break;
            case 'do-phong-dien-cuc-bo-online';
                $attrs = [
                    'zCA13_1' => 'zdpdcbcheck',
                    'zCA13_2' => 'zMPD_Re',
                    'zCA13_3' => 'zMPD_Re',
                    'zCA13_4' => 'zMPD_Re',
                    'zCA13_5' => 'zMPD_Re',
                    'zCA13_6' => 'zMPD_Re',
                ];
                break;
            case 'do-phong-dien-cuc-bo-offline';
                $attrs = [
                    'zCA13_1' => 'z508mopcheck',
                    'zCA13_2' => 'z508mopcheck',
                    'zCA13_3' => 'zMPDOff_Re',
                    'zCA13_4' => 'zMPDOff_Re',
                    'zCA13_5' => 'zMPDOFF_Re',
                    'zCA13_6' => 'zMPDOFF_Re',
                ];
                break;
            case 'thi-nghiem-bo-dieu-ap-duoi-tai';
                $attrs = [
                    'zCA13_1' => 'ztnbdcheck',
                    'zCA13_2' => 'zCOLTC_Re',
                    'zCA13_3' => 'zCOLTC_Re',
                    'zCA13_4' => 'zCOLTC_Re',
                    'zCA13_5' => 'zCOLTC_Re',
                    'zCA13_6' => 'zCOLTC_Re',
                ];
                break;
            case 'thi-nghiem-dien-ap-xoay-chieu-tang-cao';
                $attrs = [
                    'zCA13_1' => 'ztndaxctccheck',
                    'zCA13_2' => 'zCOLTC_Recheck',
                    'zCA13_3' => 'zCOLTC_Recheck',
                    'zCA13_4' => 'zCOLTC_Recheck',
                    'zCA13_5' => 'zAChvwcheck',
                    'zCA13_6' => 'zCOLTC_Recheck',
                ];
                break;
            case 'thi-nghiem-dien-ap-ac-cam-ung';
                $attrs = [
                    'zCA13_1' => 'z517ivcheck',
                    'zCA13_2' => 'z517ivcheck',
                    'zCA13_3' => 'zIVWT_RE',
                    'zCA13_4' => 'zIVWT_RE',
                    'zCA13_5' => 'zIVWT_RE',
                    'zCA13_6' => 'zIVWT_RE',
                ];
                break;
            case 'do-ton-that-khong-tai-va-dong-dien-khong-tai-o-dien-ap-dinh-muc';
                $attrs = [
                    'zCA13_1' => 'z526nlccheck',
                    'zCA13_2' => 'z526nlccheck',
                    'zCA13_3' => 'zLCLMRV_Re',
                    'zCA13_4' => 'zLCLMRV_Re',
                    'zCA13_5' => 'z750dttcheck',
                    'zCA13_6' => 'zLCLMRV_Re',
                ];
                break;
            case 'do-ton-that-co-tai-va-dien-ap-ngan-mach';
                $attrs = [
                    'zCA13_1' => 'z530scvcheck',
                    'zCA13_2' => 'z530scvcheck',
                    'zCA13_3' => 'zSCVLLM_Re',
                    'zCA13_4' => 'zSCVLLM_Re',
                    'zCA13_5' => 'z750dttcheck',
                    'zCA13_6' => 'zSCVLLM_Re',
                ];
                break;
            case 'to-dau-day';
                $attrs = 'zCVG_Re';
            break;
            default:
                $attrs = 'zMLI_Re';
                break;
        }
        $items = [];
        foreach($ids as $id){
            $whereClause = "id = U'".$id."'";
            $item = getDataFromService('doSelect', 'nr', $attributes, $whereClause);
            $object = 'zCA'.join('_', explode('.', substr($item['class.type'], 0, 4)));
            if( is_array($attrs) ){
                $attrs2 = ['id', $attrs[$object]];
            }else{
                $attrs2 = ['id', $attrs];
            }
            $assocData = getDataFromService('doSelect', $object, $attrs2, $whereClause);
            if( is_array($attrs) ){
                $item['result'] = @$assocData[$attrs[$object]] ? 'Pass' : 'Fail';
            }else{
                $item['result'] = @$assocData[$attrs] ? 'Pass' : 'Fail';
            }
            array_push($items, $item);
        }
        $items = collect($items)->sortBy('zlaboratoryDate')->toArray();
        foreach($items as $index => $item){
            $items[$index]['zlaboratoryDate'] = !empty($item['zlaboratoryDate']) ? date('d/m/Y', $item['zlaboratoryDate']) : '';
        }
        unset($attributes[0]);
        array_push($attributes, 'result');
        return [
            'data' => $items,
            'attrs' => array_values($attributes)
        ];
    }
}

if( !function_exists('getDataReportPorcelainTest') ){
    function getDataReportPorcelainTest(array $ids){
        $reports = getDataReportHighPressureTransformers($ids);
        foreach($reports as $index => $report){
            $whereClause = " id = U'".$report['id']."' ";
            $obj = 'zCA'.join('_', explode('.', substr($report['class.type'], 0, 4)));
            $attrs = config('highpressuretransformers.report_porcelain_test.'.$obj);
            $attrsFlatten = collect($attrs)->flatten()->all();
            $data = getDataFromService('doSelect', $obj, $attrsFlatten, $whereClause);
            foreach($attrsFlatten as $attr){
                $reports[$index][$attr] = @$data[$attr];
            }
            $reports[$index]['data'][ @$data[$attrs[0]].'_A' ] = join('_', getArrayValueByArrayKey($reports[$index], $attrs['attrs_1']));
            $reports[$index]['data'][ @$data[$attrs[1]].'_B' ] = join('_', getArrayValueByArrayKey($reports[$index], $attrs['attrs_2']));
            $reports[$index]['data'][ @$data[$attrs[2]].'_C' ] = join('_', getArrayValueByArrayKey($reports[$index], $attrs['attrs_3']));
            $reports[$index]['data'][ @$data[$attrs[3]].'_N' ] = join('_', getArrayValueByArrayKey($reports[$index], $attrs['attrs_4']));
            if($obj == 'zCA13_5'){
                $reports[$index]['data'][ @$data[$attrs[4]].'_Am' ] = join('_', getArrayValueByArrayKey($reports[$index], $attrs['attrs_5']));
                $reports[$index]['data'][ @$data[$attrs[5]].'_Bm' ] = join('_', getArrayValueByArrayKey($reports[$index], $attrs['attrs_6']));
                $reports[$index]['data'][ @$data[$attrs[6]].'_Cm' ] = join('_', getArrayValueByArrayKey($reports[$index], $attrs['attrs_7']));
            }
            $reports[$index]['obj'] = $obj;
        }
        return $reports;
    }
}
// write data to sheet chart one reprot for reportPorcelainTest
if( !function_exists('writeDataToChartTranformersChartSingle') ){
    function writeDataToChartTranformersChartSingle(array $data, array $columns, string $nameIndexAttrs, int $startIndexRow, $spreadsheet){
        foreach($data as $item){
            $attrs = config('highpressuretransformers.report_porcelain_test.attrs_chart_one_report.'.$item['obj'].'.'.$nameIndexAttrs);
            foreach($attrs as $index => $attr){
                $spreadsheet->getCell($columns[$index % 2].$startIndexRow)->setValue(convertValueDataFromCA(@$item[$attr], HighPressureController::END_POINT_PORCELAINTEST_TEST));
                if($index % 2 == 1){
                    $startIndexRow++;
                }
            }
        }
    }
}
// hidden sheet and chart
if( !function_exists('hiddenSheetsAndChart') ){
    function hiddenSheetsAndChart(array $sheetNames, $spreadsheet, $sheetBD, $startIndexChart, $endIndexChart){
        foreach($sheetNames as $sheetName){
            $spreadsheet->getSheetByName($sheetName)
                ->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);
        }
        for ($i = $startIndexChart; $i >= $endIndexChart; $i--) {
            $sheetBD->getRowDimension($i)->setVisible(false);
        }
    }
}
// hidden column and chart
if( !function_exists('hiddenColumnAndChart') ){
    function hiddenColumnAndChart(array $columns, $spreadsheetActive, $sheetBD = null, $startIndexChart = 0, $endIndexChart = 0){
        foreach($columns as $column){
            $spreadsheetActive->getColumnDimension($column)->setVisible(false);
        }
        if( !is_null($sheetBD) ){
            for ($i = $startIndexChart; $i >= $endIndexChart; $i--) {
                $sheetBD->getRowDimension($i)->setVisible(false);
            }
        }
    }
}
// get array value by array key
if( !function_exists('getArrayValueByArrayKey') ){
    function getArrayValueByArrayKey(array $data, array $keys){
        $value = [];
        foreach($keys as $key){
            $value[$key] = $data[$key] ?? '';
        }
        return $value;
    }
}
// write data to sheet chart multi report for reportPorcelainTest
if( !function_exists('writeDataToChartTranformersChartMulti') ){
    function writeDataToChartTranformersChartMulti(array $data, array $columns, string $nameIndexAttrs, int $startIndexRow, $spreadsheet){
        foreach($data as $item){
            $attrs = config('highpressuretransformers.report_porcelain_test.attrs_chart_multi_report.'.$item['obj'].'.'.$nameIndexAttrs) ?? [];
            foreach($attrs as $index => $attr){
                $spreadsheet->getCell($columns[$index].$startIndexRow)->setValue(convertValueDataFromCA(@$item[$attr], HighPressureController::END_POINT_PORCELAINTEST_TEST));
            }
            $startIndexRow++;
        }
    }
}

if( !function_exists('copyRows') ){
    /**
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param int $srcRow(startIndexRow)
     * @param int $dstRow(endIndexRow)
     * @param int $height( >= endIndexRow - startIndexRow)
     * @param int $width(Number of columns. Ex: 0 => A, 1 => A & B)
    */
    function copyRows(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, $srcRow, $dstRow, $height, $width) {
        // Cell format and value duplication
        for ($row = 0; $row < $height; $row++) {
            for ($col = 0; $col < $width; $col++) {
                $cell = $sheet->getCellByColumnAndRow($col, $srcRow + $row);
                $style = $sheet->getStyleByColumnAndRow($col, $srcRow + $row);

                $dstCell = Coordinate::stringFromColumnIndex($col) . (string)($dstRow + $row);
                $sheet->setCellValue($dstCell, $cell->getValue());
                $sheet->duplicateStyle($style, $dstCell);
            }
            // Row height replication.
            $h = $sheet->getRowDimension($srcRow + $row)->getRowHeight();
            $sheet->getRowDimension($dstRow + $row)->setRowHeight($h);
        }
        // Duplicate cell merge
        foreach ($sheet->getMergeCells() as $mergeCell) {
            $mc = explode(":", $mergeCell);
            $col_s = preg_replace("/[0-9]*/", "", $mc[0]);
            $col_e = preg_replace("/[0-9]*/", "", $mc[1]);
            $row_s = ((int)preg_replace("/[A-Z]*/", "", $mc[0])) - $srcRow;
            $row_e = ((int)preg_replace("/[A-Z]*/", "", $mc[1])) - $srcRow;
            // If the line range of the copy destination.
            if (0 <= $row_s && $row_s < $height) {
                $merge = $col_s . (string)($dstRow + $row_s) . ":" . $col_e . (string)($dstRow + $row_e);
                $sheet->mergeCells($merge);
            }
        }
    }
}

if( !function_exists('getDataRateOfChangeReport') ){
    function getDataRateOfChangeReport(array $ids){
        $reports = getDataReportHighPressureTransformers($ids);
        foreach($reports as $index => $report){
            $whereClause = "id = U'".$report['id']."'";
            $obj = 'zCA'.join('_', explode('.', substr($report['class.type'], 0, 4)));
            $attrs = collect(config('highpressuretransformers.report_rate_of_change.'.$obj.'.attrs'))->flatten()->toArray();
            $result = getDataFromService('doSelect', $obj, $attrs, $whereClause);
            foreach($attrs as $attr){
                $reports[$index][$attr] = @$result[$attr];
            }
            $reports[$index]['obj'] = $obj;
        }
        return $reports;
    }
}

// get data for report statisticalExperimental transformers
if( !function_exists('getNumberOfExperiments') ){
    function getNumberOfExperiments($request){
        $ids = explode(',', $request->ids);
        $items = [];
        $attrsDevice = [
            'zrefnr_dvql.zsym',
            'zrefnr_td.zsym',
            'name'
        ];
        foreach($ids as $id){
            $whereDevice = " id = U'".$id."' AND class.zetc_type != 1 ";
            $device = getDataFromService('doSelect', 'nr', $attrsDevice, $whereDevice);
            $whereClause = " zCI_Device = U'".$id."' AND class.zetc_type = 1 ";
            if($request->start_date){
                $whereClause .= " AND zlaboratoryDate >= " . strtotime($request->start_date);
            }
            if($request->end_date){
                $whereClause .= " AND zlaboratoryDate < " . strtotime("+1 day", strtotime($request->end_date));
            }
            $result = getDataFromService('doSelect', 'nr', ['id', 'name'], $whereClause, 100);
            $device['number'] = count($result);
            array_push($items, $device);
        }
        return $items;
    }
}
// sort data helper
if( !function_exists('sortData') ){
    function sortData(array &$data, string $sortBy = 'asc', string $sortKey = 'zsym'){
        if($sortBy == 'asc'){
            usort($data, function($a, $b) use($sortKey) {
                return Str::slug(strtolower(str_replace(' ', '', @$a[$sortKey]))) <=> Str::slug(strtolower(str_replace(' ', '', @$b[$sortKey])));
            });
            return TRUE;
        }
        usort($data, function($a, $b) use($sortKey) {
            return Str::slug(strtolower(str_replace(' ', '', @$b[$sortKey]))) <=> Str::slug(strtolower(str_replace(' ', '', @$a[$sortKey])));
        });
        return TRUE;
    }
}
// get list device for report statistical list and number device
if( !function_exists('getDataStatisticalListAndNumberDevice') ){
    function getDataStatisticalListAndNumberDevice($request){
        $attrs = [
            'id',
            'zArea.zsym',
            'zrefnr_dvql.zsym',
            'zrefnr_td.zsym',
            'zrefnr_nl.zsym',
            'name',
            'serial_number',
            'zManufacturer.zsym',
            'zCI_Device_Type.zsym',
            'zYear_of_Manafacture.zsym',
            'zCI_Device_Kind.zsym'
        ];
        $attrExtend = ['zdienapdinhmuc', 'zcapacity', 'zvector_group', 'zdongdiendinhmuc'];
        $items = filterDevice($request->devices, $request, [], false, " AND zManufacturer.zsym IS NOT NULL AND zCI_Device_Kind.zsym IS NOT NULL AND zrefnr_dvql.zsym IS NOT NULL ", $attrs, $attrExtend, true);
        return array_values($items);
    }
}
// get data for pie chart for report statistical list and number device
if( !function_exists('calculateRatioStatisticalListAndNumberDevice') ){
    function calculateRatioStatisticalListAndNumberDevice(array $data, string $keyName){
        $items = collect($data)->filter(function($item) use ($keyName){
            return array_key_exists($keyName, $item);
        });
        $count = $items->count();
        $items = $items->groupBy(function($item) use ($keyName){
            return $item[$keyName];
        })->toArray();
        if($count == 0){
            return [];
        }
        $data = [];
        foreach($items as $key => $item){
            $data[$key] = round(count($item) / $count * 100, 2);
        }

        return $data;
    }
}
// fill data sheet pie chart report statistical list and number device
if( !function_exists('fillDataChartStatisticalListAndNumberDevice') ){
    function fillDataChartStatisticalListAndNumberDevice(array $data, $sheetActive){
        $startIndex = 5;
        foreach($data as $key => $val){
            $sheetActive->getCell('C'.$startIndex)->setValue($key);
            $sheetActive->getCell('D'.$startIndex)->setValue($val);
            $startIndex++;
        }
    }
}
// fill data sheet column chart report statistical list and number device
if( !function_exists('fillDataSheetColumnChartListAndNumberDevice') ){
    function fillDataSheetColumnChartListAndNumberDevice(array $data, $sheetActive){
        $indexRow = 5;
        foreach($data as $key => $val){
            $sheetActive->getCell("B{$indexRow}")->setValue(explode('_', $key)[0]);
            $sheetActive->getCell("C{$indexRow}")->setValue(explode('_', $key)[1]);
            $sheetActive->getCell("D{$indexRow}")->setValue($val);
            $indexRow++;
        }
    }
}
// get data for column chart report statistical list and number device
if( !function_exists('calculateNumberDeviceForColumnChart') ){
    function calculateNumberDeviceForColumnChart(array $data, string $keyFirst, string $keySecond){
        $result = [];
        foreach($data as $item){
            if( empty($result["{$item[$keyFirst]}_{$item[$keySecond]}"]) ){
                $result["{$item[$keyFirst]}_{$item[$keySecond]}"] = 0;
            }
            $result["{$item[$keyFirst]}_{$item[$keySecond]}"]++;
        }
        return $result;
    }
}

if( !function_exists('getDataExperimentalResultsDevice') ){
    function getDataExperimentalResultsDevice($request){
        $attrs = [
            'id',
            'zArea.zsym',
            'zrefnr_dvql.zsym',
            'zrefnr_td.zsym',
            'zrefnr_nl.zsym',
            'name',
            'zManufacturer.zsym',
        ];
        $whereClauseAppend = "  AND zManufacturer.zsym IS NOT NULL AND zCI_Device_Kind.zsym IS NOT NULL AND zrefnr_dvql.zsym IS NOT NULL ";
        $items = filterDevice($request->devices, $request, [], false, $whereClauseAppend, $attrs);
        $data = [];
        foreach($items as $item){
            $whereClauseDevice = " zCI_Device = U'".$item['id']."' AND class.zetc_type = 1 AND zlaboratoryDate IS NOT NULL";
            if($request->startDate){
                $whereClauseDevice .= " AND zlaboratoryDate >= " . strtotime($request->startDate);
            }
            if($request->endDate ){
                $whereClauseDevice .= " AND zlaboratoryDate < " . strtotime("+1 day", strtotime($request->endDate));
            }
            if($request->zqt){
                $whereClauseDevice .= " AND zQT =".$request->zqt;
            }
            $reports = getDataFromService('doSelect', 'nr', ['zQT.zsym', 'zlaboratoryDate', 'zContract_Number.sym', 'zresultEnd', 'zresultEnd_chk', 'name', 'class.type'], $whereClauseDevice, 150);
            foreach($reports as $i => $report){
                $reports[$i]['zQT.zsym'] = @$report['zQT.zsym'];
                $reports[$i]['zlaboratoryDate'] = !empty($report['zlaboratoryDate']) ? date('d/m/Y', $report['zlaboratoryDate']) : '';
                $reports[$i]['year'] = !empty($report['zlaboratoryDate']) ? date('Y', $report['zlaboratoryDate']) : '';
                $reports[$i]['zContract_Number.sym'] = @$report['zContract_Number.sym'];
                $reports[$i]['zresultEnd'] = @$report['zresultEnd'];
                $reports[$i]['pass'] = @$report['zresultEnd_chk'] ? 'x' : '';
                $reports[$i]['fail'] = @$report['zresultEnd_chk'] ? '' : 'x';
                $reports[$i]['class.type'] = @$report['class.type'];
                array_push($data, array_merge($reports[$i], $item));
            }
        }
        return $data;
    }
}
// get all report has zCI_Device by array classTypes
if( !function_exists('getAllReportsFreeListHanlde') ){
    function getAllReportsFreeListHanlde(array $classTypes, $request){
        foreach ($classTypes as $key => $val) {
            if ($key == 0) {
                $whereClause = " class.type IN (";
            }
            $whereClause .= "'" . $val . "'";
            if ($key < count($classTypes) - 1) {
                $whereClause .= ",";
            } else {
                $whereClause .= ")";
            }
        }
        if($request->start_date){
            $whereClause .= " AND zlaboratoryDate >= " . strtotime($request->start_date);
        }
        if($request->end_date){
            $whereClause .= " AND zlaboratoryDate < " . strtotime("+1 day", strtotime($request->end_date));
        }
        $whereClause .= ' AND zCI_Device IS NOT NULL AND delete_flag = 0';
        $service = new CAWebServices(env('CA_WSDL_URL'));
        $sid = session()->get(env('AUTH_SESSION_KEY'))['ssid'];
        $listHandle = $service->getListHandle($sid, 'nr', $whereClause);
        $items = $service->getAllItemsFromListHandle($sid, $listHandle, ['id','name', 'zCI_Device']);
        $service->freeListHandles($sid, $listHandle->listHandle);
        return $items;
    }
}

if( !function_exists('filterReportAutomation') ){
    function filterReportAutomation(array $devices, $request){
        $deviceArr = [];

        if (!empty($devices)) {
            foreach ($devices as $device) {
                if (!empty($request['type']) && (empty($device['class.type']) || strpos($device['class.type'], $request['type']) === false)) {
                    continue;
                }
                if (!empty($request['year']) && (empty($device['zYear_of_Manafacture.zsym']) || strpos($device['zYear_of_Manafacture.zsym'], $request['year']) === false)) {
                    continue;
                }
                if (!empty($request['country']) && (empty($device['zCountry.name']) || strpos($device['zCountry.name'], $request['country']) === false)) {
                    continue;
                }

                if (!empty($request['zci_device_type_name']) && (empty($device['zCI_Device_Type.zsym']) || strpos($device['zCI_Device_Type.zsym'], $request['zci_device_type_name']) === false)) {
                    continue;
                }
                if( !empty($device['report']) ){
                    foreach ($device['report'] as $keyReport => $report) {
                        $checkTime = true;
                        if (!empty($request['from']) && empty($request['to'])) {
                            $checkTime = (int)$report['creation_date'] >= strtotime($request['from']);
                        } elseif (empty($request['from']) && !empty($request['to'])) {
                            $checkTime = (int)$report['creation_date'] <= strtotime($request['to']);
                        } elseif (!empty($request['from']) && !empty($request['to'])) {
                            $checkTime = (int)$report['creation_date'] >= strtotime($request['from']) && (int)$report['creation_date'] <= strtotime($request['to']);
                        }
                        if (!empty($request['area']) && (empty($report['zArea.zsym']) || strpos($report['zArea.zsym'], $request['area']) === false)) {
                            $checkTime = false;
                        }
                        if (!empty($request['area']) && (empty($report['zArea.zsym']) || strpos($report['zArea.zsym'], $request['area']) === false)) {
                            $checkTime = false;
                        }
                        if (!empty($request['factory']) && (empty($report['zrefnr_td.zsym']) || strpos($report['zrefnr_td.zsym'], $request['factory']) === false)) {
                            $checkTime = false;
                        }
                        if (!empty($request['system']) && (empty($report['zrefnr_nl.zsym']) || strpos($report['zrefnr_nl.zsym'], $request['system']) === false)) {
                            $checkTime = false;
                        }
                        if (!empty($request['manufacture']) && (empty($report['zManufacturer.zsym']) || strpos($report['zManufacturer.zsym'], $request['manufacture']) === false)) {
                            $checkTime = false;
                        }
                        if (!empty($request['series']) && (empty($report['serial_number']) || strpos($report['serial_number'], $request['series']) === false)) {
                            $checkTime = false;
                        }
                        if (!empty($request['name']) && (empty($report['name']) || strpos($report['name'], $request['name']) === false)) {
                            $checkTime = false;
                        }
                        if (!empty($request['zci_device_name']) && (empty($report['name']) || strpos($report['name'], $request['zci_device_name']) === false)) {
                            $checkTime = false;
                        }
                        if (!$checkTime) {
                            unset($device['report'][$keyReport]);
                        }
                    }
                }
                if (!empty($request['firmware']) || !empty($request['software']) || !empty($request['version']) || !empty($request['ip_address']) || !empty($request['installation_time'])) {
                    if (empty($device['info'])) {
                        continue;
                    }
                    if (!empty($request['firmware']) && (empty($device['info']['zhedieuhanh.zsym']) || strpos($device['info']['zhedieuhanh.zsym'], $request['firmware']) === false)) {
                        continue;
                    }
                    if (!empty($request['software']) && (empty($device['info']['zsoftware.zsym']) || strpos($device['info']['zsoftware.zsym'], $request['software']) === false)) {
                        continue;
                    }
                    if (!empty($request['version']) && (empty($device['info']['zversion']) || $device['info']['zversion'] != $request['version'])) {
                        continue;
                    }
                    if (!empty($request['ip_address']) && (empty($device['info']['zIP']) || $device['info']['zIP'] != $request['ip_address'])) {
                        continue;
                    }
                    if (!empty($request['installation_time']) && (empty($device['info']['zTGLD']) || date('d/m/Y', $device['info']['zTGLD']) != $request['installation_time'])) {
                        continue;
                    }
                }
                array_push($deviceArr, $device);
            }
        }
        return $deviceArr;
    }
}
// convert value from CA for report insulation resistance
if( !function_exists('convertValueDataFromCA') ){
    function convertValueDataFromCA($value, $endPoint = HighPressureController::END_POINT_INSULATION_RESISTANCE){
        $value = htmlspecialchars_decode($value);
        if((substr( $value, 0, 1 ) == ">" && (float)substr($value, 1) >= $endPoint) || (float)$value > $endPoint){
            return $endPoint;
        }
        return $value;
    }
}
// check value from CA is string report insulation resistance
if( !function_exists('checkValueIsString') ){
    function checkValueIsString($value, $endPoint = null){
        $value = htmlspecialchars_decode($value);
        $flag = false;
        if( 
            (strlen($value) == 1 && !is_numeric($value)) 
            || $value != '' && 
            ((substr( $value, 0, 1 ) != ">" && !is_numeric($value)) 
            || (substr( $value, 0, 1 ) == ">" && !is_numeric(substr( $value, 1 )) )
            || (!is_null($endPoint) && substr( $value, 0, 1 ) == ">" && is_numeric(substr( $value, 1 )) && (float)substr( $value, 1 ) < $endPoint )) ){
                $flag = true;
        }
        return $flag;
    }
}

// Fill search data to excel file for all analysis report high pressure transformers
if( !function_exists('fillSearchDataToExcelHighPressure') ){
    function fillSearchDataToExcelHighPressure($sheetActive, $request, $numberRowInsert = 3, $columns = []){
        $columns = !empty($columns) ? $columns : ['B', 'C', 'D', 'E', 'F'] ;
        $inputs = [
            'Ngày bắt đầu thí nghiệm: ' => 'start_date',
            'Ngày kết thúc thí nghiệm: ' => 'end_date',
            'Khu vực: ' => 'area',
            'Trạm/ Nhà máy: ' => 'td',
            'Ngăn lộ: ' => 'nl',
            'Thiết bị: ' => 'device',
            'Hãng sản xuất: ' => 'manufacture',
            'Kiểu: ' => 'type',
            'Số chế tạo: ' => 'serial_number',
            'Năm sản xuất: ' => 'zYear_of_Manafacture',
            'Nước sản xuất: ' => 'country',
            'Công suất: ' => 'zcapacity',
            'Tổ đấu dây: ' => 'ztodauday',
            'Điện áp định mức: ' => 'zdienapdinhmuc',
        ];
        $requestArr = formatRequest($request);
        fillSearchRequestToExcel($sheetActive, $requestArr, $columns, $inputs, $numberRowInsert);
    }
}

// Fill search data to excel file for all analysis report high pressure cutting machines
if( !function_exists('fillSearchDataToExcelCuttingMachines') ){
    function fillSearchDataToExcelCuttingMachines($sheetActive, $request, $numberRowInsert = 3, $columns = []){
        $columns = !empty($columns) ? $columns : ['B', 'C', 'D', 'E', 'F'] ;
        $inputs = [
            'Ngày bắt đầu thí nghiệm: ' => 'start_date',
            'Ngày kết thúc thí nghiệm: ' => 'end_date',
            'Khu vực: ' => 'area',
            'Trạm/ Nhà máy: ' => 'td',
            'Ngăn lộ/ Hệ thống: ' => 'nl',
            'Thiết bị: ' => 'device',
            'Hãng sản xuất: ' => 'manufacture_id',
            'Kiểu: ' => 'type',
            'Số chế tạo: ' => 'serial_number',
            'Năm sản xuất: ' => 'zYear_of_Manafacture',
            'Nước sản xuất: ' => 'country',
            'Dòng điện định mức: ' => 'zdongdiendinhmuc',
            'Điện áp định mức: ' => 'zdienapdinhmuc',
            'Loại hình thí nghiệm: ' => 'ztestType',
            'Chủng loại: ' => 'deviceType',
        ];
        $requestArr = formatRequest($request);
        fillSearchRequestToExcel($sheetActive, $requestArr, $columns, $inputs, $numberRowInsert);
    }
}

if( !function_exists('formatRequest') ){
    function formatRequest($request){
        $request = is_array($request) ? new \Illuminate\Http\Request($request) : (is_null($request) ? new \Illuminate\Http\Request([]) : $request );
        $requestArr = is_array($request) ? $request : $request->all();
        libxml_disable_entity_loader(false);
        // List thiết bị
        if( $request->devices && is_array($request->devices) || $request->device_type_id && is_array($request->device_type_id) ){
            $flag = true;
            foreach(($request->devices ?? $request->device_type_id) as $device){
                if( !is_numeric($device) ){
                    $flag = false;
                }
            }
            if( $flag ){
                $whereClause = " id IN(" . join(",", ($request->devices ?? $request->device_type_id)) .") ";
                $result = getDataFromService('doSelect', 'grc', ['id', 'type'], $whereClause, 20);
                $requestArr['devices'] = collect($result)->pluck('type')->toArray();
                $requestArr['device_type_id'] = collect($result)->pluck('type')->toArray();
            }
        }
        if( $request->devices && is_numeric($request->devices) || $request->type_device_id && is_numeric($request->type_device_id) || $request->type_id && is_numeric($request->type_id) || $request->device_type_id && is_numeric($request->device_type_id)){
            $result = getDataFromService('doSelect', 'grc', ['id', 'type'], 'id='.($request->devices ?? $request->type_device_id ?? $request->type_id ?? $request->device_type_id));
            $requestArr['devices'] = @$result['type'];
            $requestArr['type_device_id'] = @$result['type'];
            $requestArr['type_id'] = @$result['type'];
            $requestArr['device_type_id'] = @$result['type'];
        }
        // Chủng loại
        if( $request->deviceType || $request->zCI_Device_Type_id ){
            $result = getDataFromService('doSelect', 'zCI_Device_Type', ['id', 'zsym'], 'id='.($request->deviceType ?? $request->zCI_Device_Type_id));
            $requestArr['deviceType'] = @$result['zsym'];
            $requestArr['zCI_Device_Type_id'] = @$result['zsym'];
        }
        if( $request->deviceTypes && is_array($request->deviceTypes) ){
            $result = getDataFromService('doSelect', 'zCI_Device_Type', ['id', 'zsym'], " id IN (". join(',', $request->deviceTypes) .")", 100);
            $requestArr['deviceTypes'] = collect($result)->pluck('zsym')->toArray();
        }
        // Khu vực
        if( $request->area ){
            $result = getDataFromService('doSelect', 'zArea', ['id', 'zsym'], "id = {$request->area}");
            $requestArr['area'] = @$result['zsym'];
        }
        // Khu vực
        if( $request->zArea ){
            $result = getDataFromService('doSelect', 'zArea', ['id', 'zsym'], "id = {$request->zArea}");
            $requestArr['zArea'] = @$result['zsym'];
        }
        // Tình trạng thí nghiệm(trạng thái)
        if( $request->zStage ){
            $result = getDataFromService('doSelect', 'zETC_Status', ['id', 'zsym'], 'id='.$request->zStage);
            $requestArr['zStage'] = @$result['zsym'];
        }
        // Năm sản xuất(bắt đầu)
        if( $request->startYear){
            $result = getDataFromService('doSelect', 'zYear_of_Manafacture', ['id', 'zsym'], 'id='.$request->startYear);
            $requestArr['startYear'] = @$result['zsym'] ?? $request->startYear;
        }
        // Năm sản xuất(kết thúc)
        if( $request->endYear){
            $result = getDataFromService('doSelect', 'zYear_of_Manafacture', ['id', 'zsym'], 'id='.$request->endYear);
            $requestArr['endYear'] = @$result['zsym'] ?? $request->endYear;
        }
        // Đơn vị quản lý
        if( array_filter($request->dvqls ?? []) || array_filter($request->zrefnr_dvql_ids ?? []) || array_filter($request->zDVQL ?? []) ){
            $result = collect(getDataFromService('doSelect', 'zDVQL', ['id', 'zsym'], " id IN (".join(',', ($request->dvqls ?? $request->zrefnr_dvql_ids ?? $request->zDVQL)).")", 100))->pluck('zsym')->map(function($item){
                return htmlspecialchars_decode($item);
            })->toArray();
            $requestArr['dvqls'] = $result;
            $requestArr['zrefnr_dvql_ids'] = $result;
            $requestArr['zDVQL'] = $result;
        }
        if( $request->zrefnr_dvql && is_numeric($request->zrefnr_dvql) || $request->dvql_id && is_numeric($request->dvql_id) ){
            $result = getDataFromService('doSelect', 'zDVQL', ['id', 'zsym'], " id =".($request->zrefnr_dvql ?? $request->dvql_id));
            $requestArr['zrefnr_dvql'] = @$result['zsym'];
            $requestArr['dvql_id'] = @$result['zsym'];
        }
        
        if( $request->zdvquanlydiemdosrel && is_numeric($request->zdvquanlydiemdosrel) ){
            $result = getDataFromService('doSelect', 'zDVQL', ['id', 'zsym'], " id =".($request->zdvquanlydiemdosrel));
            $requestArr['zdvquanlydiemdosrel'] = @$result['zsym'];
        }

        // Trạm/Nhà máy
        if( $request->td_id && is_numeric($request->td_id) ){
            $result = getDataFromService('doSelect', 'zTD', ['id', 'zsym'], " id =".($request->td_id));
            $requestArr['td_id'] = @$result['zsym'];
        }
        // Ngăn lộ/Hệ thống
        if( $request->nl_id && is_numeric($request->nl_id) ){
            $result = getDataFromService('doSelect', 'zNL', ['id', 'zsym'], " id =".($request->nl_id));
            $requestArr['nl_id'] = @$result['zsym'];
        }
        // Tổ đấu dây
        if( $request->ztodauday && is_array($request->ztodauday) ){
            $result = getDataFromService('doSelect', 'ztodauday', ['id', 'zsym'], " id IN (".join(',', $request->ztodauday).")", 100);
            foreach($result as $val){
                $requestArr['ztodauday'][] = @$val['zsym'];
            }
        }
        // Quy trình
        if( $request->zqt && is_array($request->zqt) ){
            $result = getDataFromService('doSelect', 'zQT', ['id', 'zsym'], " id IN (".join(',', $request->zqt).")", 100);
            $requestArr['zqt'] = collect($result)->pluck('zsym')->toArray();
        }
         if( $request->zqt && is_numeric($request->zqt) ){
            $result = getDataFromService('doSelect', 'zQT', ['id', 'zsym'], " id =".$request->zqt);
            $requestArr['zqt'] = @$result['zsym'];
        }

        if( $request->species && is_numeric($request->species) ){
            $result = getDataFromService('doSelect', 'zCI_Device_Type', ['id', 'zsym'], " id =".$request->species);
            $requestArr['species'] = @$result['zsym'];
        }
        // Hãng sản xuất
        if( ($request->manufacture && is_numeric($request->manufacture)) || ($request->manufacture_id && is_numeric($request->manufacture_id)) || ($request->manufacturer && is_numeric($request->manufacturer)) || ($request->manufacturer_id && is_numeric($request->manufacturer_id)) ){
            $result = getDataFromService('doSelect', 'zManufacturer', ['id', 'zsym'], " id =".($request->manufacture ?? $request->manufacture_id ?? $request->manufacturer ?? $request->manufacturer_id));
            $requestArr['manufacture'] = htmlspecialchars_decode(@$result['zsym']);
            $requestArr['manufacture_id'] = htmlspecialchars_decode(@$result['zsym']);
            $requestArr['manufacturer'] = htmlspecialchars_decode(@$result['zsym']);
            $requestArr['manufacturer_id'] = htmlspecialchars_decode(@$result['zsym']);
        }
        if( $request->zManufacturer && is_array($request->zManufacturer) || $request->manufacture && is_array($request->manufacture) || $request->manufacture_ids && is_array($request->manufacture_ids) ) {
            $whereClause = " id IN( " . join(',', ($request->zManufacturer ?? $request->manufacture ?? $request->manufacture_ids)) . ")";
            $result = getDataFromService('doSelect', 'zManufacturer', ['id', 'zsym'], $whereClause, 100);
            $requestArr['zManufacturer'] = collect($result)->pluck('zsym')->toArray();
            $requestArr['manufacture'] = collect($result)->pluck('zsym')->toArray();
            $requestArr['manufacture_ids'] = collect($result)->pluck('zsym')->toArray();
        }
        // Loại hình thí nghiệm
        if( $request->ztestType_ids && is_array($request->ztestType_ids) ) {
            $whereClause = " id IN( " . join(',', $request->ztestType_ids) . ")";
            $result = getDataFromService('doSelect', 'ztestType', ['id', 'zsym'], $whereClause, 100);
            $requestArr['ztestType_ids'] = collect($result)->pluck('zsym')->toArray();
        }
        if(array_filter($request->zManufacturer_ids ?? [])){
            $result = collect(getDataFromService('doSelect', 'zManufacturer', ['id', 'zsym'], " id IN (".join(',', ($request->zManufacturer_ids)).")", 100))->pluck('zsym')->toArray();
            $requestArr['zManufacturer_ids'] = $result;
        }
        // Hợp đồng
        if( $request->zContract_Number && is_array($request->zContract_Number) ){
            $result = collect(getDataFromService('doSelect', 'svc_contract', ['id', 'sym'], " id IN( " . join(',', $request->zContract_Number) .")", 100))->pluck('sym')->toArray();
            $requestArr['zContract_Number'] = $result;
        }
        return $requestArr;
    }
}

if( !function_exists('alignmentExcel') ){
    function alignmentExcel($sheetActive, $range, $alignment = 'left'){
        $sheetActive->getStyle($range)->getAlignment()->setHorizontal($alignment);
    }
}

if( !function_exists('hiddenSheets') ){
    function hiddenSheets($spreadsheet, array $sheetsName){
        if( !empty($sheetsName) ){
            foreach($sheetsName as $sheetName){
                $sheet = $spreadsheet->getSheetByName($sheetName);
                if( !is_null($sheet) ){
                    $sheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);
                }
            }
        }
    }
}
