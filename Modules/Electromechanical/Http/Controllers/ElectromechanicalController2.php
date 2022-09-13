<?php

namespace Modules\Electromechanical\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Exports\ExperimentalStatisticsExport;
use File;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ElectromechanicalController2 extends Controller
{
    /**
     * Conductor statistics report
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function conductorStatisticsReport()
    {
        $management_unit = Cache::remember('unitList', 7200, function () {
            return getDataFromService('doSelect', 'zDVQL', ['id', 'zsym'], 'zsym IS NOT NULL AND active_flag = 1', 200);
        });
        sortData($management_unit);
        $type_conductor = ['Cáp bọc hạ áp', 'Cáp bọc trung áp', 'Cáp lực trung thế', 'Cáp vặn xoắn', 'Dây dẫn trần'];

        $manufactures = getDataFromService('doSelect', 'zManufacturer', ['id', 'zsym'], 'zsym IS NOT NULL AND zclass = 1002851', 300);
        sortData($manufactures);
        return view('electromechanical::conductor_report.index', compact('manufactures','management_unit','type_conductor'));
    }

    /**
     * Export conductor statistics report
     *
     * @param \Illuminate\Http\Request $request
     * @return false|\Illuminate\Http\JsonResponse|string
     */
    public function exportConductorStatisticsReport(Request $request)
    {
        try {
            // Get report type
            if (! empty($request['type'])) {
                $types = $request['type'];
            } else {
                $types = [
                    config('constant.electromechanical.device.type.cap_boc_ha_ap'),
                    config('constant.electromechanical.device.type.cap_boc_trung_ap'),
                    config('constant.electromechanical.device.type.cap_luc_trung_the'),
                    config('constant.electromechanical.device.type.cap_van_xoan'),
                    config('constant.electromechanical.device.type.day_dan_tran'),
                ];
            }
            $arrType = getReportFromType($types);
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

            if (! empty($request['from'])) {
                $whereClause .= " AND zlaboratoryDate >= ".strtotime($request['from']);
            }

            if (! empty($request['to'])) {
                $whereClause .= " AND zlaboratoryDate <= ".strtotime("+1 day", strtotime($request['to']));
            }

            if (! empty($request['management'])) {
                foreach ($request['management'] as $key => $val) {
                    if ($key == 0) {
                        $whereClause .= " AND zrefnr_dvql.zsym IN (";
                    }
                    $whereClause .= "'".$val."'";
                    if ($key < count($request['management']) - 1) {
                        $whereClause .= ",";
                    } else {
                        $whereClause .= ")";
                    }
                }
            }
            if (! empty($request['zManufacturer'])) {
                foreach ($request['zManufacturer'] as $key => $val) {
                    if ($key == 0) {
                        $whereClause .= " AND zManufacturer IN (";
                    }
                    $whereClause .= "'".$val."'";
                    if ($key < count($request['zManufacturer']) - 1) {
                        $whereClause .= ",";
                    } else {
                        $whereClause .= ")";
                    }
                }
            }

            $whereClause .= ' AND class.zetc_type = 1';
            $attributes = [
                'zManufacturer.zsym',
                'zlaboratoryDate',
                'zrefnr_dvql.zsym',
                'zresultEnd_chk',
                'class.type'
            ];
            $reports = getDataFromService('doSelect', 'nr', $attributes, $whereClause, 100);
            // get data type wire
            $reports = setWireType($reports, $attributes);
            if(empty($reports)) {
                return response()->json([
                    'error' => ['Không tìm thấy dữ liệu thống kê thỏa mãn']
                ]);
            }

            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);

            // Read Template Excel
            $template = storage_path('templates_excel/phan_xuong_co_dien/bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-tong-quan/bao-cao-tong-quan.xlsx');
            $spreadsheet = $reader->load($template);

            // Create New Writer
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->setIncludeCharts(true);
            $count = 0;

            $items_all = checkUniquePXCĐ1($reports, $attributes, 1);
            if (count($types) > 1) {
                $count = setValueType($items_all, $count, $spreadsheet, 'Bảng tổng hợp', 2, $request);
            }
            $items_type = checkUniquePXCĐ1($reports, $attributes, 2);
            // get data table all
            foreach ($items_type as $key => $value) {
                if (empty($value['class.type'])) {
                    continue;
                }

                // GET DATA THEO TỪNG LOẠI DÂY DẪN
                if (in_array($value['class.type'], ['1. BBTN PXCD1 - Cáp bọc hạ thế, 1 lõi', '2. BBTN PXCD2- Cáp bọc hạ thế - nhiều lõi', '3. BBTN PXCD3-Cáp bọc hạ thế, phân biệt các pha theo màu'])) {
                    foreach ($attributes as $data) {
                        $item_1[$key][$data] = $value[$data] ?? '';
                    }
                    $item_1[$key]['achieved'] = $value['achieved'] ?? '';
                    $item_1[$key]['not_achieved'] = $value['not_achieved'] ?? '';
                }

                if (in_array($value['class.type'], ['4. BBTN PXCD4-Cáp bọc trung áp ACSR - vỏ bọc HDPE', '5. BBTN PXCD5-Cáp bọc trung áp ACSR - vỏ bọc PVC', '6. BBTN PXCD6-Cáp bọc trung áp lõi CU hoặc Al'])) {
                    foreach ($attributes as $data) {
                        $item_2[$key][$data] = $value[$data] ?? '';
                    }
                    $item_2[$key]['achieved'] = $value['achieved'] ?? '';
                    $item_2[$key]['not_achieved'] = $value['not_achieved'] ?? '';
                }

                if (in_array($value['class.type'], ['7. BBTN PXCD7-Cáp lực trung thế 1 lõi, màn chắn bằng đồng', '8. BBTN PXCD8-Cáp lực trung thế 1 lõi, màn chắn sợi đồng', '9. BBTN PXCD9-Cáp lực trung thế 3 lõi, màn chắn bằng đồng', '10. BBTN PXCD10-Cáp lực trung thế 3 lõi, màn chắn sợi đồng'])) {
                    foreach ($attributes as $data) {
                        $item_3[$key][$data] = $value[$data] ?? '';
                    }
                    $item_3[$key]['achieved'] = $value['achieved'] ?? '';
                    $item_3[$key]['not_achieved'] = $value['not_achieved'] ?? '';
                }

                if (in_array($value['class.type'], ['11. BBTN PXCD11-Cáp vặn xoắn 2 lõi', '12. BBTN PXCD12-Cáp vặn xoắn 4 lõi'])) {
                    foreach ($attributes as $data) {
                        $item_4[$key][$data] = $value[$data] ?? '';
                    }
                    $item_4[$key]['achieved'] = $value['achieved'] ?? '';
                    $item_4[$key]['not_achieved'] = $value['not_achieved'] ?? '';
                }

                if (in_array($value['class.type'], ['13. BBTN PXCD13-Dây dẫn trần ACSR', '14. BBTN PXCD14-Dây siêu nhiệt', '15. BBTN PXCD15-Dây dẫn trần A630', '16. BBTN PXCD16-Dây dẫn trần TK'])) {
                    foreach ($attributes as $data) {
                        $item_5[$key][$data] = $value[$data] ?? '';
                    }
                    $item_5[$key]['achieved'] = $value['achieved'] ?? '';
                    $item_5[$key]['not_achieved'] = $value['not_achieved'] ?? '';
                }
            }

            if (empty($item_1) && empty($item_2) && empty($item_3) && empty($item_4) && empty($item_5)) {
                $spreadsheet->setActiveSheetIndexByName('Sheet1')->getCell('A'.('4'))->setValue('Không có kết quả nào!');
            } else {
                foreach ($types as $value) {
                    if ($value == 'Cáp bọc hạ áp' && ! empty($item_1)) {
                        $count = setValueType($item_1, $count, $spreadsheet, 'Cáp bọc hạ áp', '3', $request);
                    } elseif ($value == 'Cáp bọc trung áp' && ! empty($item_2)) {
                        $count = setValueType($item_2, $count, $spreadsheet, 'Cáp bọc trung áp', '3', $request);
                    } elseif ($value == 'Cáp lực trung thế' && ! empty($item_3)) {
                        $count = setValueType($item_3, $count, $spreadsheet, 'Cáp lực trung thế', '3', $request);
                    } elseif ($value == 'Cáp vặn xoắn' && ! empty($item_4)) {
                        $count = setValueType($item_4, $count, $spreadsheet, 'Cáp vặn xoắn', '3', $request);
                    } elseif ($value == 'Dây dẫn trần' && ! empty($item_5)) {
                        $count = setValueType($item_5, $count, $spreadsheet, 'Dây dẫn trần', '3', $request);
                    }
                }
            }

            $columns = range('C', 'E');
            $requestArr = formatRequest($request);
            $inputs = [
                'Ngày bắt đầu cần thống kế: ' => 'from',
                'Ngày kết thúc cần thống kê: ' => 'to',
                'Loại dây dẫn: ' => 'type',
                'Đơn vị sử dụng: ' => 'management',
                'Hãng sản xuất: ' => 'zManufacturer',
            ];
            fillSearchRequestToExcel($spreadsheet->setActiveSheetIndexByName('Sheet1'), $requestArr, $columns, $inputs, 3);
            $url = writeExcel($spreadsheet, 'bao-cao-tong-quan'.'-'.time().'.xlsx');

            return response()->json([
                'success' => true,
                'url' => $url
            ]);
        } catch (\Exception $ex) {
            Log::error('[Electromechanical] Export conductor statistics report: Fail | Reason: ' . $ex->getMessage() . '. ' . $ex->getFile() . ':' . $ex->getLine() . '| Params: ' . json_encode($request->all()));

            return response()->json([
                'error' => [$ex->getMessage()]
            ]);
        }
    }

    /**
     * Supplier quality report
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function supplierQualityReport()
    {
        $manufactures = getDataFromService('doSelect', 'zManufacturer', ['id', 'zsym'], 'zsym IS NOT NULL AND zclass = 1002851', 300);
        sortData($manufactures);

        $year = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now())->year;
        return view('electromechanical::supplier_quality.index', compact('year','manufactures'));
    }

    /**
     * export report 5
     * @return Renderable
     */
    public function exportSupplierQualityReport(Request $request)
    {
        // Get report type
        $types = [
            config('constant.electromechanical.device.type.cap_boc_ha_ap'),
            config('constant.electromechanical.device.type.cap_boc_trung_ap'),
            config('constant.electromechanical.device.type.cap_luc_trung_the'),
            config('constant.electromechanical.device.type.cap_van_xoan'),
            config('constant.electromechanical.device.type.day_dan_tran'),
        ];
        $arrType = getReportFromType($types);
        $whereClause = '';
        foreach ($arrType as $key => $val) {
            if ($key == 0) {
                $whereClause = "class.type IN (";
            }
            $whereClause .= "'". $val ."'";
            if ($key < count($arrType) - 1) {
                $whereClause .= ",";
            } else {
                $whereClause .= ")";
            }
        }
        if (!empty($request['year_from'])) {
            $year_from = $request['year_from'].'-1-1'.'T00:00';
            $whereClause .= " AND zlaboratoryDate >= " . strtotime($year_from) . "";
        }
        if (!empty($request['year_to'])) {
            $year_to = $request['year_to'].'-12-31'.'T23:59';
            $whereClause .= " AND zlaboratoryDate <= " . strtotime($year_to) . "";
        }
        if (!empty($request['filter_type'])) {
            foreach ($request['filter_type'] as $key => $val) {
                if ($key == 0) {
                    $whereClause .= " AND zManufacturer.zsym IN (";
                }
                $whereClause .= "'". $val ."'";
                if ($key < count($request['filter_type']) - 1) {
                    $whereClause .= ",";
                } else {
                    $whereClause .= ")";
                }
            }
        }
        $whereClause .= ' AND class.zetc_type = 1';
        // $whereClause .= " AND zManufacturer.zsym LIKE '%Toshiba%'";
        $attributes = ['zManufacturer.zsym','zlaboratoryDate','zresultEnd_chk','class.type'];
        $reports = getDataFromService('doSelect', 'nr', $attributes, $whereClause, 100);
        // get data type wire
        $data = setWireType($reports, $attributes);
        if (count($reports) == 0) {
            return json_encode(0);
        }
        // get list manufactures
        foreach($data as $key => $value){
            foreach($value as $k => $val){
                if(!empty($val['zManufacturer.zsym'])){
                    $manufactures[] = $val['zManufacturer.zsym']??'';
                }
            }
        }
        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);
        // Read Template Excel
        if(!empty($manufactures)){
            $manufactures = array_unique($manufactures);
            for ($i=1; $i <= 30; $i++) {
                if($i == count($manufactures)){
                    $key_template = $i;
                }elseif(count($manufactures) > 30){
                    $key_template = 30;
                }
            }
        }else{
            $key_template = 1;
        }
        $template = storage_path('templates_excel/phan_xuong_co_dien/bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-theo-ds-va-cl-nha-cung-cap/bao-cao-theo-doanh-so-va-chat-luong-nha-cung-cap-'.$key_template.'.xlsx');
        $spreadsheet = $reader->load($template);
        // Create New Writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);
        // get data sheet KET_QUA_THONG_KE
        $list_name_sheet = ['CAP_BOC_HA_AP','CAP_BOC_TRUNG_AP','CAP_LUC_TRUNG_THE','CAP_VAN_XOAN','DAY_DAN_TRAN'];
        if(!empty($manufactures)){
            // delete manufactures dupicate
            $manu = $data_all = [];
            // get data list manufactures
            foreach($manufactures as $key => $value){
                foreach($data as $key_2 => $valu){
                    foreach($valu as $key3 => $val){
                        if($val['zManufacturer.zsym'] == $value){
                            $manu[$value][$key3] = $val;
                        }
                    }
                }
            }
            // list attr will get data
            $attributes_items = ['zManufacturer.zsym','year','type'];
            // get data theo hãng
            foreach($manu as $key => $value){
                foreach($value as $ke => $val){
                    if (empty($val['zManufacturer.zsym']) || empty($val['year']) || empty($val['type'])) {
                        continue;
                    }
                    if (empty($data_all[$val['zManufacturer.zsym']][$val['year']][$val['type']])) {
                        $data_all[$val['zManufacturer.zsym']][$val['year']][$val['type']] = [
                            'manufactures' => $val['zManufacturer.zsym']??'',
                            'total' => 0,
                            'pass' => 0,
                            'failed' => 0,
                            'ratio' => 0,
                            'year' => $val['year']??'',
                            'type' => $val['type']??'',
                        ];
                    }
                    $data_all[$val['zManufacturer.zsym']][$val['year']][$val['type']]['total'] += 1;
                    if ( !empty($val['zresultEnd_chk']) ) {
                        $data_all[$val['zManufacturer.zsym']][$val['year']][$val['type']]['pass'] += 1;
                    } else {
                        $data_all[$val['zManufacturer.zsym']][$val['year']][$val['type']]['failed'] += 1;
                        $data_all[$val['zManufacturer.zsym']][$val['year']][$val['type']]['ratio'] = $data_all[$val['zManufacturer.zsym']][$val['year']][$val['type']]['failed'] / $data_all[$val['zManufacturer.zsym']][$val['year']][$val['type']]['total'] *100;
                    }
                }
            }
            // number
            $count = 0;
            // list atr get value
            $colum_name = ['number','manufactures','year','pass','failed','total','ratio'];
            foreach($data_all as $key => $value){
                ksort($value);
                $count_manu = count($value);
                $number = 0;
                foreach($value as $valu){
                    $check_count = 0;
                    foreach($valu as $val){
                        if(!empty($key_check[$val['year']])){
                            $key_check[$val['year']] += 1;
                        }else{
                            $key_check[$val['year']] = 1;
                        }
                    }
                    $check_dup = 0;
                    foreach($valu as $k => $val){
                        if($check_dup == 0){
                            $count++;
                        }
                        // data fill line, here plus 6 is because the data dump starts from the 7th line in the excel file. because $count++ will first have the ordinal number 1
                        $colum_key = $count + 6;
                        // check type to fill data in the correct column
                        if($val['type'] == 'Cáp bọc hạ áp'){
                            $table = ['A','B','C','D','E','F','G'];
                        }elseif($val['type'] == 'Cáp bọc trung áp'){
                            $table = ['A','B','C','H','I','J','K'];
                        }elseif($val['type'] == 'Cáp lực trung thế'){
                            $table = ['A','B','C','L','M','N','O'];
                        }elseif($val['type'] == 'Cáp vặn xoắn'){
                            $table = ['A','B','C','P','Q','R','S'];
                        }elseif($val['type'] == 'Dây dẫn trần'){
                            $table = ['A','B','C','T','U','V','W'];
                        }
                        // fill data to file excel, sheet KET_QUA_THONG_KE
                        foreach($colum_name as $k => $bv){
                            // get value
                            if($bv == 'number'){
                                $value = $count;
                            }else{
                                $value = $val[$bv]??'';
                            }
                            // check if the firm has more than 2 years, merge the cell with the corresponding number of years
                            if($bv == 'manufactures' && $count_manu > 1 && $number == 0){
                                $spreadsheet->setActiveSheetIndexByName('KET_QUA_THONG_KE')->mergeCells("B".($colum_key).":B".($colum_key + $count_manu - 1))->getCell($table[$k] . ($colum_key))->setValue($value);
                                $number++;
                            }else{
                                $spreadsheet->setActiveSheetIndexByName('KET_QUA_THONG_KE')->getCell($table[$k] . ($colum_key))->setValue($value);
                            }
                        }
                        if($key_check[$val['year']] > 1 && $check_dup == 0){
                            $check_dup++;
                        }
                    }
                }
            }
        }
        // delete data waiting in sheet data charts in excel file
        $table_sheet = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG'];
        for ($i=6; $i <= 40; $i++) {
            foreach($table_sheet as $k => $val){
                foreach($list_name_sheet as $bv){
                    if($val.$i != 'A6' && $val.$i != 'B6'){
                        $spreadsheet->setActiveSheetIndexByName($bv)->getCell($val . ($i))->setValue('');
                        $spreadsheet->setActiveSheetIndexByName('DATA_TONG_SL')->getCell($val . ($i))->setValue('');
                    }
                }
            }
        }
        // get data sheet chart
        $formattedArr = $arrAll = [];
        $count = 0;
        foreach ($data as $key => $value) {
            // set value data sheet total
            foreach ($data[$key] as $val) {
                if (empty($val['zManufacturer.zsym']) || empty($val['year'])) {
                    continue;
                }
                if (empty($arrAll[$val['zManufacturer.zsym'] . '__' . $val['year']])) {
                    $arrAll[$val['zManufacturer.zsym'] . '__' . $val['year']] = [
                        'manufactures' => $val['zManufacturer.zsym']??'',
                        'total' => 0,
                        'pass' => 0,
                        'failed' => 0,
                        'year' => $val['year']??'',
                    ];
                }
                $arrAll[$val['zManufacturer.zsym'] . '__' . $val['year']]['total'] += 1;
                if ( !empty($val['zresultEnd_chk']) ) {
                    $arrAll[$val['zManufacturer.zsym'] . '__' . $val['year']]['pass'] += 1;
                } else {
                    $arrAll[$val['zManufacturer.zsym'] . '__' . $val['year']]['failed'] += 1;
                }
            }
            // set data value type conductor
            foreach ($data[$key] as $val) {
                if($val['type'] == 'Cáp vặn xoắn'){
                    $name_sheet = 'CAP_VAN_XOAN';
                }elseif($val['type'] == 'Dây dẫn trần'){
                    $name_sheet = 'DAY_DAN_TRAN';
                }elseif($val['type'] == 'Cáp bọc trung áp'){
                    $name_sheet = 'CAP_BOC_TRUNG_AP';
                }elseif($val['type'] == 'Cáp bọc hạ áp'){
                    $name_sheet = 'CAP_BOC_HA_AP';
                }elseif($val['type'] == 'Cáp lực trung thế'){
                    $name_sheet = 'CAP_LUC_TRUNG_THE';
                }
                if (empty($val['zManufacturer.zsym']) || empty($val['year'])) {
                    continue;
                }
                if (empty($formattedArr[$name_sheet][$val['zManufacturer.zsym'] . '__' . $val['year']])) {
                    $formattedArr[$name_sheet][$val['zManufacturer.zsym'] . '__' . $val['year']] = [
                        'manufactures' => $val['zManufacturer.zsym']??'',
                        'total' => 0,
                        'pass' => 0,
                        'failed' => 0,
                        'year' => $val['year']??'',
                        'type' => $val['type']??'',
                    ];
                }
                $formattedArr[$name_sheet][$val['zManufacturer.zsym'] . '__' . $val['year']]['total'] += 1;
                if ( !empty($val['zresultEnd_chk']) ) {
                    $formattedArr[$name_sheet][$val['zManufacturer.zsym'] . '__' . $val['year']]['pass'] += 1;
                } else {
                    $formattedArr[$name_sheet][$val['zManufacturer.zsym'] . '__' . $val['year']]['failed'] += 1;
                }
            }
            $count++;
        }
        $arr_year_all = $manufac_all = $items_all = [];
        // set value chart all
        foreach($arrAll as $key_gen => $bv){
            $arr_year_all[] = $bv['year']??'';
        }
        // get data sheet DATA_TONG_SL
        foreach($arrAll as $key_gen => $bv){
            foreach($arr_year_all as $val){
                if($bv['year'] == $val){
                    $items_all[$val][$bv['manufactures']] = $bv['total'];
                    $manufac_all[] = $bv['manufactures'];
                }
            }
        }

        // format data sheet DATA_TONG_SL
        foreach($items_all as $key => $value){
            foreach($manufac_all as $k => $val){
                if(empty($value[$val])){
                    $items_all[$key][$val] = '';
                }else{
                    $items_all[$key][$val] = $value[$val];
                }
                ksort($items_all[$key]);
            }
        }
        ksort($items_all);
        $manufac_all = array_unique($manufac_all);
        $table_sheet = ['C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S'];
        $table_access = 7;
        // get data value manufac sheet DATA_TONG_SL
        $number_title = $number_year = 0;
        foreach($items_all as $key => $value){
            $spreadsheet->setActiveSheetIndexByName('DATA_TONG_SL')->getCell('A' . ($table_access))->setValue($number_year+1);
            $spreadsheet->setActiveSheetIndexByName('DATA_TONG_SL')->getCell('B' . ($table_access))->setValue($key);
            $number_value = 0;
            foreach($value as $k => $valu){
                if($number_year == 0 && !empty($table_sheet[$number_title])){
                    $spreadsheet->setActiveSheetIndexByName('DATA_TONG_SL')->getCell($table_sheet[$number_title] . ('6'))->setValue($k);
                    $number_title++;
                }
                if(!empty($table_sheet[$number_value])){
                    $spreadsheet->setActiveSheetIndexByName('DATA_TONG_SL')->getCell($table_sheet[$number_value] . ($table_access))->setValue($valu);
                    $number_value++;
                }
            }
            $number_year++;
            $table_access++;
        }
        // get value sheet type conductor
        foreach($formattedArr as $key_gen => $bv){
            $arr_year = $manufac = $items = [];
            foreach($bv as $valu){
                $arr_year[$valu['year']] = $valu['year'];
            }
            foreach($bv as $value){
                foreach($arr_year as $val){
                    if($value['year'] == $val){
                        $items[$val][$value['manufactures']] = $value['failed'];
                        $manufac[] = $value['manufactures'];
                    }
                }
            }
            // format data sheet DATA_TONG_SL
            foreach($items as $key => $value){
                foreach($manufac as $k => $val){
                    if(empty($value[$val])){
                        $items[$key][$val] = '0';
                    }else{
                        $items[$key][$val] = $value[$val];
                    }
                    ksort($items[$key]);
                }
            }
            // get data title type conductor
            $number = 0;
            $table_access = 7;
            ksort($items);
            foreach($items as $key => $value){
                $spreadsheet->setActiveSheetIndexByName($key_gen)->getCell('A' . ($table_access))->setValue($number+1);
                $spreadsheet->setActiveSheetIndexByName($key_gen)->getCell('B' . ($table_access))->setValue($key);
                $key_table_value = $key_table_title = 0;
                foreach($value as $k => $valu){
                    $spreadsheet->setActiveSheetIndexByName($key_gen)->getCell($table_sheet[$key_table_title] . ('6'))->setValue($k);
                    $key_table_title++;
                }
                foreach($value as $k => $valu){
                    if(!empty($table_sheet[$key_table_value])){
                        $spreadsheet->setActiveSheetIndexByName($key_gen)->getCell($table_sheet[$key_table_value] . ($table_access))->setValue($valu);
                        $key_table_value++;
                    }
                }
                $number++;
                $table_access++;
            }
        }

        $columns = range('B', 'D');
        $inputs = [
            'Năm bắt đầu cần thống kê: ' => 'year_from',
            'Năm kết thúc cần thống kê: ' => 'year_to',
            'Hãng sản xuất: ' => 'filter_type',
        ];
        fillSearchRequestToExcel($spreadsheet->setActiveSheetIndexByName('KET_QUA_THONG_KE'), $request, $columns, $inputs, 2);
        alignmentExcel($spreadsheet->setActiveSheetIndexByName('KET_QUA_THONG_KE'), 'B9:B1000');
        // set active to sheet BIEU_DO
        $spreadsheet->setActiveSheetIndexByName('BIEU_DO');
        // export file
        $title = 'bao-cao-theo-doanh-so-va-chat-luong-nha-cung-cap'.'-'.time().'.xlsx';
        $writer->save(public_path('export/').$title);
        $result = '/export/'.$title;
        $result = getenv('APP_URL').$result;
        return json_encode($result);
    }

    /**
     * index report 9
     * @return Renderable
     */
    public function perUnitReport(Request $request)
    {
        $year = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now())->year;
        return view('electromechanical::unit_report.index', compact('year'));
    }
    /**
     * export report 9
     * @return Renderable
     */
    public function exportPerUnitReport(Request $request)
    {
        // Get report type
        $types = [
            config('constant.electromechanical.device.type.cap_boc_ha_ap'),
            config('constant.electromechanical.device.type.cap_boc_trung_ap'),
            config('constant.electromechanical.device.type.cap_luc_trung_the'),
            config('constant.electromechanical.device.type.cap_van_xoan'),
            config('constant.electromechanical.device.type.day_dan_tran'),
        ];
        $arrType = getReportFromType($types);
        $whereClause = '';
        foreach ($arrType as $key => $val) {
            if ($key == 0) {
                $whereClause = "class.type IN (";
            }
            $whereClause .= "'". $val ."'";
            if ($key < count($arrType) - 1) {
                $whereClause .= ",";
            } else {
                $whereClause .= ")";
            }
        }
        if (!empty($request['year_from'])) {
            $year_from = $request['year_from'].'-1-1'.'T00:00';
            $whereClause .= " AND zlaboratoryDate >= " . strtotime($year_from) . "";
        }
        if (!empty($request['year_to'])) {
            $year_to = $request['year_to'].'-12-31'.'T23:59';
            $whereClause .= " AND zlaboratoryDate <= " . strtotime($year_to) . "";
        }
        $whereClause .= ' AND class.zetc_type = 1 AND zrefnr_dvql.zsym IS NOT NULL AND zManufacturer.zsym IS NOT NULL';
        $attributes = ['zrefnr_dvql.zsym','zlaboratoryDate','class.type','zManufacturer.zsym'];
        $reports = getDataFromService('doSelect', 'nr', $attributes, $whereClause, 100);
        if (count($reports) == 0) {
            return json_encode(0);
        }
        // get data type wire
        $data = setWireType($reports, $attributes);
        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);
        // Read Template Excel
        $template = storage_path('templates_excel/phan_xuong_co_dien/bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-theo-don-vi-su-dung/bao-bao-theo-don-vi-su-dung.xlsx');
        $spreadsheet = $reader->load($template);
        // Create New Writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);
        // get data
        $data_synthetic = [];
        $atr_custom = ['day_dan_tran','cap_van_xoan','cap_boc_ha_ap','cap_boc_trung_ap','cap_luc_trung_the'];
        $name_atr_custom = ['Dây dẫn trần','Cáp vặn xoắn','Cáp bọc hạ áp','Cáp bọc trung áp','Cáp lực trung thế'];
        foreach($data as $value){
            foreach($value as $key_gen => $val){
                // data sheet for summary data table
                if (empty($data_synthetic[$val['zrefnr_dvql.zsym']][$val['year']])) {
                    $data_synthetic[$val['zrefnr_dvql.zsym']][$val['year']] = [
                        'units_used' => $val['zrefnr_dvql.zsym'],
                        'year' => $val['year'],
                        'zManufacturer' => $val['zManufacturer.zsym'],
                        'day_dan_tran' => 0,
                        'cap_van_xoan' => 0,
                        'cap_boc_ha_ap' => 0,
                        'cap_boc_trung_ap' => 0,
                        'cap_luc_trung_the' => 0,
                    ];
                }
                foreach($name_atr_custom as $key => $item){
                    if($val['type'] == $item){
                        $data_synthetic[$val['zrefnr_dvql.zsym']][$val['year']][$atr_custom[$key]] += 1;
                    }
                }
            }
        }

        $table = ['A','B','C','D','F','H','J','L'];
        $atr_value = ['number','units_used','year','day_dan_tran','cap_van_xoan','cap_boc_ha_ap','cap_boc_trung_ap','cap_luc_trung_the'];
        $colum_key = 6;
        $count = 0;
        foreach($data_synthetic as $value){
            ksort($value);
            $count_manu = count($value);
            $number = 0;
            foreach($value as $val){
                $colum_key = $count + 6;
                $count++;
                foreach($atr_value as $key => $item){
                    if($item == 'number'){
                        $colum_value = $count;
                    }else{
                        $colum_value = $val[$item]??'';
                    }
                    // check if the firm has more than 2 years, merge the cell with the corresponding number of years
                    if($item == 'units_used' && $count_manu > 1 && $number == 0){
                        $spreadsheet->setActiveSheetIndexByName('DATA_1')->mergeCells("B".($colum_key).":B".($colum_key + $count_manu - 1))->getCell($table[$key] . ($colum_key))->setValue($colum_value);
                        $number++;
                    }else{
                        $spreadsheet->setActiveSheetIndexByName('DATA_1')->getCell($table[$key] . ($colum_key))->setValue($colum_value);
                    }
                }
            }
        }
        $columns = ['B', 'C'];
        $inputs = [
            'Năm bắt đầu cần thống kê: ' => 'year_from',
            'Năm kết thúc cần thống kê: ' => 'year_to',
        ];
        fillSearchRequestToExcel($spreadsheet->setActiveSheetIndexByName('DATA_1'), $request, $columns, $inputs, 2);
        alignmentExcel($spreadsheet->setActiveSheetIndexByName('DATA_1'), 'B8:B1000');
        // export file
        $title = 'bao-cao-theo-dơn-vi-su-dung-bang-so-lieu-tong-hop-'.'-'.time().'.xlsx';
        $writer->save(public_path('export/').$title);
        $result = '/export/'.$title;
        $result = getenv('APP_URL').$result;
        return json_encode($result);
    }

    /**
     * index report Statistical report of relay failure
     * @return Renderable
     */
    public function reportRelayFailureStatistics(Request $request)
    {
        // Get report type
        $arrType = config('attributes.where_in_report_34_pxcd');
        $whereClause = '';
        foreach ($arrType as $key => $val) {
            if ($key == 0) {
                $whereClause = "class.type IN (";
            }
            $whereClause .= "'". $val ."'";
            if ($key < count($arrType) - 1) {
                $whereClause .= ",";
            } else {
                $whereClause .= ")";
            }
        }
        $attributes = ['zManufacturer.zsym','zCI_Device_Type.zsym'];
        $reports = getDataFromService('doSelect', 'nr', $attributes, $whereClause, 100);
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
        return view('electromechanical::relay_failure.index', compact('allDeviceTypes', 'types'));
    }
    /**
     * export report Statistical report of relay failure
     * @return Renderable|\Illuminate\Http\JsonResponse
     */
    public function exportReportRelayFailureStatistics(Request $request)
    {
        try {
            // Get report type
            $arrType = config('attributes.where_in_report_34_pxcd');
            $whereClauseBase = '';
            foreach ($arrType as $key => $val) {
                if ($key == 0) {
                    $whereClauseBase = "class.type IN (";
                }
                $whereClauseBase .= "'". $val ."'";
                if ($key < count($arrType) - 1) {
                    $whereClauseBase .= ",";
                } else {
                    $whereClauseBase .= ")";
                }
            }
            if (!empty($request['from'])) {
                $from = strtotime($request['from'].'T00:00');
                $whereClauseBase .= " AND zlaboratoryDate >= " . $from . "";
            }
            if (!empty($request['to'])) {
                $to = strtotime($request['to'].'T23:59');
                $whereClauseBase .= " AND zlaboratoryDate <= " . $to . "";
            }
            if(!empty($request['filter_type'])){
                foreach ($request['filter_type'] as $key => $val) {
                    if ($key == 0) {
                        $whereClauseBase .= " AND zCI_Device_Type.zsym IN (";
                    }
                    $whereClauseBase .= "'" . $val . "'";
                    if ($key < count($request['filter_type']) - 1) {
                        $whereClauseBase .= ",";
                    } else {
                        $whereClauseBase .= ")";
                    }
                }
            }

            $types = getDataFromService('doSelect', 'grc', [
                'id',
                'type',
                'family',
            ], "zetc_type = 0 AND type LIKE '%Rơ le bảo vệ%' AND delete_flag != 1", 100);
            $typeIdArr = collect($types)->pluck('id')->toArray();
            if( !empty($request->devices) ){
                $typeIdArr = $request->devices;
            }
            $whereClauseBase .= " AND zCI_Device.class IN (". join(',', $typeIdArr) .")";
            $whereClauseBase .= " AND ztestType.zsym = 'Thay thế rơ le' AND zlaboratoryDate IS NOT NULL AND zCI_Device_Type.zsym IS NOT NULL AND zManufacturer.zsym IS NOT NULL AND class.zetc_type = 1";
            $attributes = config('attributes.arr_statistical_report');
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
            // if $reports = 0, return errol
            if (count($reports) == 0) {
                $data_result['error'] = 'Không có dữ liệu';
                return json_encode($data_result);
            }
            // get data assoc
            foreach ($reports as $key => $item) {
                $classTypes = ['26. BBTN BCU', '28. BBTN F50_F67Ns_81_BCU', '29. BBTN F50_F67Ns_BCU', '10. BBTN F50/51'];
                if( in_array($item['class.type'], $classTypes) ){
                    $atr = ['id','zReplacingRC.zsym'];
                    $atr_value = 'zReplacingRC.zsym';
                }else{
                    $atr = ['id','zrolereplace.zsym'];
                    $atr_value = 'zrolereplace.zsym';
                }
                $obj = getObjectTypeRelay($item['class.type']);
                $where = "id = U'" . $item['id'] . "'";
                $data_obj = getDataFromService('doSelect', $obj, $atr, $where, 150);
                $reports[$key]['zrolereplace'] = $data_obj[0][$atr_value]??'';
            }
            // count zrolereplace and manufacture get template
            $list_zrolereplace = $list_manufacture = [];
            // get list zrolereplace
            foreach($reports as $key => $value){
                if(!empty($value['zrolereplace'])){
                    $list_zrolereplace[] = $value['zrolereplace'];
                }
                if(!empty($value['zManufacturer.zsym'])){
                    $list_manufacture[] = $value['zManufacturer.zsym'];
                }
            }
            $list_zrolereplace = array_unique($list_zrolereplace);
            $list_manufacture = array_unique($list_manufacture);
            // check template
            if(count($list_zrolereplace) < count($list_manufacture) || count($list_zrolereplace) == count($list_manufacture)){
                $check_template = count($list_manufacture);
            }elseif(count($list_zrolereplace) > count($list_manufacture)){
                $check_template = count($list_zrolereplace);
            }
            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);
            // Read Template Excel
            if (!empty($request['filter_type']) && count($request['filter_type']) == 1) {
                $list_name_sheet = ['KET_QUA_THONG_KE','DATA_BĐ1','DATA_BĐ2','DATA_BĐ3'];
                if(count($list_zrolereplace) > 30){
                    $name_template = 'bao-cao-hu-hong-role-bd3/bao-cao-hu-hong-ro-le-bd3-30.xlsx';
                }else{
                    $name_template = 'bao-cao-hu-hong-role-bd3/bao-cao-hu-hong-ro-le-bd3-'.$check_template.'.xlsx';
                }
            }else{
                $list_name_sheet = ['KET_QUA_THONG_KE','DATA_BĐ1','DATA_BĐ2'];
                if(count($list_zrolereplace) > 30){
                    $name_template = 'bao-cao-hu-hong-role-khong-bd3/bao-cao-hu-hong-ro-le-khong-bd3-30.xlsx';
                }else{
                    $name_template = 'bao-cao-hu-hong-role-khong-bd3/bao-cao-hu-hong-ro-le-khong-bd3-'.$check_template.'.xlsx';
                }
            }
            $template = storage_path('templates_excel/phan_xuong_co_dien/bao-cao-thong-ke-he-thong-bao-ve-cac-ngan-lo-trung-ap/bc-hu-hong-ro-le/'.$name_template);
            $spreadsheet = $reader->load($template);
            // Create New Writer
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->setIncludeCharts(true);

            // delete data wait at template
            $table_sheet = ['A','B','C'];
            for ($i=5; $i <= 40; $i++) {
                foreach($table_sheet as $k => $val){
                    foreach($list_name_sheet as $bv){
                        $spreadsheet->setActiveSheetIndexByName($bv)->getCell($val . ($i))->setValue('');
                    }
                }
            }
            // fill data sheet KET_QUA_THONG_KE
            $table = ['A','B','C','E','F','G'];
            $table_value = ['number', 'zlaboratoryDate', 'zrolereplace', 'number', 'zlaboratoryDate', 'zManufacturer.zsym'];
            $number = 0;
            $colum_key = 4;
            foreach($reports as $key => $value){
                $number++;
                $colum_key = $number + 4;
                foreach($table_value as $k => $val){
                    if($val == 'number'){
                        $data_value = $number;
                    }elseif($val == 'zlaboratoryDate' && !empty($value['zlaboratoryDate'])){
                        $data_value = date('d/m/Y', $value['zlaboratoryDate']);
                    }else{
                        $data_value = $value[$val]??'';
                    }
                    $spreadsheet->setActiveSheetIndexByName('KET_QUA_THONG_KE')->getCell($table[$k] . ($colum_key))->setValue($data_value);
                }
            }
            // get data sheet DATA_BĐ1
            $table_value = ['number', 'zrolereplace', 'total'];
            getDataRelayFailureStatistics($reports, $table_value, $spreadsheet, 'zrolereplace', 'DATA_BĐ1');
            // get data sheet DATA_BĐ2
            $table_value = ['number', 'zManufacturer.zsym', 'total'];
            getDataRelayFailureStatistics($reports, $table_value, $spreadsheet, 'zManufacturer.zsym', 'DATA_BĐ2');
            if (!empty($request['filter_type']) && count($request['filter_type']) == 1) {
                // get data sheet DATA_BĐ3
                $data_sheet_3 = [];
                foreach($reports as $key => $value){
                    if (empty($data_sheet_3[$value['zrolereplace']])) {
                        $data_sheet_3[$value['zrolereplace']] = [
                            'total' => 0,
                            'zrolereplace' => $value['zrolereplace'],
                            'zCI_Device_Type' => $value['zCI_Device_Type.zsym'],
                        ];
                    }
                    $data_sheet_3[$value['zrolereplace']]['total'] += 1;
                }
                // fill data sheet DATA_BĐ3
                $table = ['A','B','C'];
                $table_value = ['number', 'zrolereplace', 'total'];
                $colum_key = 4;
                $number = 0;
                foreach($data_sheet_3 as $key => $value){
                    $number++;
                    $colum_key = $number + 4;
                    $spreadsheet->setActiveSheetIndexByName('DATA_BĐ3')->getCell('B' . ('3'))->setValue('Dữ liệu so sánh phần trăm hư hỏng Rơ le của loại '.$value['zCI_Device_Type']);
                    $spreadsheet->setActiveSheetIndexByName('BIEU_DO')->getCell('B' . ('60'))->setValue('Biểu đồ so sánh phần trăm hư hỏng Rơ le của loại '.$value['zCI_Device_Type']);
                    foreach($table_value as $k => $val){
                        if($val == 'number'){
                            $data_value = $number;
                        }else{
                            $data_value = $value[$val]??'';
                        }
                        $spreadsheet->setActiveSheetIndexByName('DATA_BĐ3')->getCell($table[$k] . ($colum_key))->setValue($data_value);
                    }
                }
            }

            $columns = ['B', 'C', 'D', 'E'];
            $inputs = [
                'Thời gian bắt đầu cần thống kê: ' => 'from',
                'Thời gian kết thúc cần thống kê: ' => 'to',
                'Loại thiết bị: ' => 'devices',
                'Chủng loại: ' => 'filter_type'
            ];
            fillSearchRequestToExcel($spreadsheet->setActiveSheetIndexByName('KET_QUA_THONG_KE'), formatRequest($request), $columns, $inputs, 1);
            $sheetBD = $spreadsheet->setActiveSheetIndexByName('BIEU_DO');
            $sheetBD->getCell('B5')->setValue('Biểu đồ so sánh phần trăm hư hỏng Rơ le do các nguyên nhân');
            // export file
            $title = 'bao-cao-ro-le-hu-hong'.'-'.time().'.xlsx';
            $writer->save(public_path('export/').$title);
            $result = '/export/'.$title;
            $result = getenv('APP_URL').$result;
            $data_result['link'] = $result;
            $data_result['error'] = '';
            return json_encode($data_result);
        } catch (\Exception $ex) {
            Log::error('[Electromechanical] Statistical report of relay failure: Fail | Reason: ' . $ex->getMessage() . '. ' . $ex->getFile() . ':' . $ex->getLine() . '| Params: ' . json_encode($request->all()));

            $data_result['error'] = $ex->getMessage();
            return json_encode($data_result);
        }
    }
    /**
     * index report 35
     * @return Renderable
     */
    public function experimentalStatisticsReport(Request $request)
    {
        $ztestTypes = getDataFromService('doSelect', 'ztestType', ['id', 'zsym'], '', 100);
        sortData($ztestTypes);
        return view('electromechanical::statistic_report.index', compact('ztestTypes'));
    }
    /**
     * preview report 35
     * @return Renderable
     */
    public function previewExperimentalStatisticsReport(Request $request)
    {
        try {
            $result = figuresExperimentalStatisticsData($request->all());
            if (!empty($result['error'])) {
                return response()->json([
                    'error' => 'Đã có lỗi xảy ra'
                ]);
            }
            $html = view('electromechanical::statistic_report.data')->with('dataArr', $result['dataArr'])->render();
            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Exception $ex) {
            Log::error('[Electromechanical] Get experimental statistics report preview: Fail | Reason: '.$ex->getMessage().'. '.$ex->getFile().':'.$ex->getLine().'| Params: '.json_encode($request->all()));
            return response()->json([
                'error' => [$ex->getMessage()]
            ]);
        }
    }
    /**
     * export report 35
     * @return Renderable
     */
    public function exportExperimentalStatisticsReport(Request $request)
    {
        return Excel::download(new ExperimentalStatisticsExport($request->all()), 'Báo cáo thống kê công tác thí nghiệm.xlsx');
    }
}
