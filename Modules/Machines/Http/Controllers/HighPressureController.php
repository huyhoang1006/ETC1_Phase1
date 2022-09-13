<?php

namespace Modules\Machines\Http\Controllers;

use App\Exports\StatisticalExperimentalExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Chart\Axis;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Layout;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpWord\Shared\ZipArchive;
class HighPressureController extends Controller
{
    const END_POINT_INSULATION_RESISTANCE = 100000;
    const END_POINT_PORCELAINTEST_TEST = 100000;
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    */
    public function indexShare(Request $request)
    {
        $now = \DateTime::createFromFormat('U.u', microtime(true));
        Log::info($now->format("m-d-Y H:i:s.u") . ' => 1. Get filter dropdown list information (Area, Manufacture, Type of experiment)');

        $areas = Cache::remember('all_area', 7200, function () {
            return collect(getDataFromService('doSelect', 'zArea', [], 'zsym IS NOT NULL AND active_flag = 1', 150))->pluck('zsym', 'id')->toArray();
        });
        asort($areas);
        $manufactures = collect(getDataFromService('doSelect', 'zManufacturer', ['id', 'zsym'], 'zsym IS NOT NULL AND zclass = 1002782', 300))->pluck('zsym', 'id')->toArray();
        asort($manufactures);
        $typeOfExperiments = Cache::remember('allExperimentType', 7200, function () {
            return collect(getDataFromService('doSelect', 'ztestType', ['id', 'zsym'], '', 150))->pluck('zsym')->unique()->toArray();
        });
        asort($typeOfExperiments);
        $now = \DateTime::createFromFormat('U.u', microtime(true));
        Log::info($now->format("m-d-Y H:i:s.u") . ' => 2. Start getting device data');
        $items = getDataHighPressure($request);
        return view('machines::indexShare', compact('areas', 'manufactures', 'items', 'typeOfExperiments'));
    }

    /**
     * Preview cutting time report
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function cuttingTimeReport(Request $request)
    {
        $template = '';
        $ids = explode(',', $request->ids);
        $numberReport = config('constant.high_pressure_report.report_cutting_time');
        $classType = $request->classType;
        try {
            switch ($classType) {
                case '10.2. BBTN Máy cắt 1 buồng cắt 3 bộ truyền động':
                    $count = count($ids);
                    $template = storage_path("templates_excel/cao-ap/may-cat/thoi-gian-cat/bao-cao-thoi-gian-cat-one-{$count}.xlsx");
                    break;
                case '10.1 BBTN Máy cắt 1 buồng cắt 1 bộ truyền động':
                    $count = count($ids) / 3;
                    if( is_int($count) ){
                        $template = storage_path("templates_excel/cao-ap/may-cat/thoi-gian-cat/bao-cao-thoi-gian-cat-two-{$count}.xlsx");
                    }
                    break;
                default:
                    $count = count($ids) / 3;
                    if( is_int($count) ){
                        $template = storage_path("templates_excel/cao-ap/may-cat/thoi-gian-cat/bao-cao-thoi-gian-cat-three-{$count}.xlsx");
                    }
                    break;
            }
            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);
            // Read Template Excel
            $spreadsheet = $reader->load($template);
            // issue https://github.com/PHPOffice/PhpSpreadsheet/issues/74
            libxml_disable_entity_loader(false);
            $result = checkTypeMachines($request, '', config('attributes.default_function_not_use'), config('attributes.cutting_time_report.function_get_data'), config('attributes.cutting_time_report.function_write_excel'));
            $data = $result['function_get_data']($ids);
            $function = $result['function_write_excel'];
            $this->$function($data, $count, $spreadsheet);
            // handle detached sheet BDG
            if( !empty($request->bdg) ){
                $formattedData = getCuttingTimeDataForEvaluationBoard($classType, $ids);
                if( array_key_exists('error', $formattedData) ){
                    return back()->with(['ids' => $ids, 'numberReport' => $numberReport, 'bdg' => $request->bdg ])->withErrors([$formattedData['error']]);
                }
                $sheetBDG = $spreadsheet->setActiveSheetIndexByName('BANG_DANH_GIA');
                writeDataSheetBDG($formattedData, $sheetBDG);
                fillSearchDataToExcelCuttingMachines($sheetBDG, $request);
                alignmentExcel($sheetBDG, 'C9:C1000');
                alignmentExcel($sheetBDG, 'F9:F1000');
                hiddenSheets($spreadsheet, ['BIEU_DO', 'THONG_KE_DU_LIEU', 'DATA_BD1', 'DATA_BD2']);
                $sheetBDG->getCell('B5')->setValue('Bảng đánh giá - Báo cáo thời gian cắt');
                $title = 'bao-cao-thoi-gian-cat-bang-danh-gia-'.time().'.xlsx';
            }else{
                fillSearchDataToExcelCuttingMachines($spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU'), $request);
                $spreadsheet->setActiveSheetIndexByName('BIEU_DO');
                hiddenSheets($spreadsheet, ['BANG_DANH_GIA']);
                $title = 'bao-cao-thoi-gian-cat-'.time().'.xlsx';
            }
            $url = writeExcel($spreadsheet, $title);
            return view('machines::cutting_time', compact('url'));
        } catch (\Exception $e) {
            Log::error('[Machines] Cutting time report: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return back()->with(['ids' => $ids, 'numberReport' => $numberReport, 'bdg' => $request->bdg ])->withErrors([$e->getMessage()]);
        }
    }

    /**
     * Fill data report cutting time dot one
     * @param array $data
     * @param $count
     * @param $spreadsheet
     */
    private function writeDataCuttingTimeReportDotOne(array $data, $count, $spreadsheet)
    {
        $sheetStatistical = $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
        $sheetChart2 = $spreadsheet->setActiveSheetIndexByName('DATA_BD2');
        $sheetChart1 = $spreadsheet->setActiveSheetIndexByName('DATA_BD1');
        $sheetBD = $spreadsheet->setActiveSheetIndexByName('BIEU_DO');
        // follow sheet THONG_KE_DU_LIEU
        $startIndexRow = 5;
        $table = ['D', 'E', 'F'];
        // follow sheet DATA_BD1_MULTI
        $startIndexRowChart = 5;
        $tableChart1 = ['C', 'D'];
        $tableChart2 = ['G', 'H'];
        $tableChart3 = ['K', 'L'];

        $columnDateChart2 = ['B', 'F', 'J'];

        foreach($data as $item){
            // get index column name $tableChart1, $tableChart2, $tableChart3
            $i = 0;
            $sheetStatistical->mergeCells("B{$startIndexRow}:B".($startIndexRow + 1))->getCell("B{$startIndexRow}")->setValue(@$item['zlaboratoryDate']);
            $sheetChart1->mergeCells("B{$startIndexRow}:B".($startIndexRow + 1))->getCell("B{$startIndexRow}")->setValue(@$item['zlaboratoryDate']);
            // fill data to sheet 'THOI_GIAN_CAT'
            foreach($item['data'] as $title => $value){
                $sheetStatistical->getCell("C{$startIndexRow}")->setValue($title);
                $sheetChart1->getCell("C{$startIndexRow}")->setValue($title);
                // variable help get the corresponding variable (vd: $tableChart1)
                $j = 1;
                foreach($value as $index => $val){
                    // fill data to sheet THOI_GIAN_CAT
                    $sheetStatistical->getCell($table[$index].$startIndexRow)->setValue($val);
                    $sheetChart1->getCell($table[$index].$startIndexRow)->setValue($val);
                    // fill data sheet DATA_BD1
                    // get paramater name
                    $tableChart = 'tableChart'.$j;
                    // $$ => $tableChart.$j
                    $sheetChart2->getCell($$tableChart[$i].$startIndexRowChart)->setValue($val);
                    $j++;
                }
                $startIndexRow++;
                $i++;
            }
            foreach($columnDateChart2 as $column){
                $sheetChart2->getCell($column.$startIndexRowChart)->setValue(@$item['zlaboratoryDate']);
            }
            $startIndexRowChart++;
        }
        // remove sheet DATA_BD1 and chart when export multi report
        if($count > 1){
            removeSheetAndHiddenChart($spreadsheet, $sheetBD, 'DATA_BD1', 17, 4);
        }else{
            hiddenSheetsAndChart(['DATA_BD2'], $spreadsheet, $sheetBD, 29, 18);
        }
    }

    /**
     * Fill data report cutting time dot two
     * @param array $data
     * @param $count
     * @param $spreadsheet
     */
    private function writeDataCuttingTimeReportDotTwo(array $data, $count, $spreadsheet)
    {
        $sheetStatistical =  $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
        $sheetChart1 =  $spreadsheet->setActiveSheetIndexByName('DATA_BD1');
        $sheetChart2 =  $spreadsheet->setActiveSheetIndexByName('DATA_BD2');
        $sheetBD =  $spreadsheet->setActiveSheetIndexByName('BIEU_DO');
        $sheetBDG = $spreadsheet->setActiveSheetIndexByName('BANG_DANH_GIA');
        $sheetBD->getChartByName('chart1')->setTitle(new \PhpOffice\PhpSpreadsheet\Chart\Title('Thời gian cắt - Trong một lần thí nghiệm'));
        // Follow sheet DATA_BD2
        $startIndexRowChartMulti = 5;
        $tableChart1 = ['C', 'D'];
        $tableChart2 = ['G', 'H'];
        $tableChart3 = ['K', 'L'];
        $columnDateChart2 = ['B', 'F', 'J'];
        // Fill data to sheet 'DATA_BD1'
        $startIndexRowChartOne = 4;
        $table = ['D', 'E', 'F'];


        $startIndexRow = 5;

        foreach($data as $date => $values){
            // Fill data to sheet 'THONG_KE_DU_LIEU'
            // merge cell one day => three phase
            // one phase => two categories
            // => merge 5 column
            $sheetStatistical->mergeCells("B{$startIndexRow}:B".($startIndexRow + 5))->getCell("B{$startIndexRow}")->setValue($date);
            foreach($values as $index => $value){
                // Fill data to sheet 'THONG_KE_DU_LIEU'
                $sheetStatistical->mergeCells("C{$startIndexRow}:C".($startIndexRow + 1))->getCell("C{$startIndexRow}")->setValue(@$value['zphase.zsym']);
                foreach($value['data'] as $title => $val){
                    $sheetStatistical->getCell("D{$startIndexRow}")->setValue($title);
                    $sheetStatistical->getCell("E{$startIndexRow}")->setValue($val);
                    $startIndexRow++;
                }
                // Fill data to sheet 'DATA_BD1'
                $startIndexRowChartOne = 4;
                $sheetChart1->getCell($table[$index].$startIndexRowChartOne)->setValue(@$value['zphase.zsym']);
                $startIndexRowChartOne = 5;
                // Fill data to sheet 'DATA_BD1'
                $sheetChart1->mergeCells('B'.$startIndexRowChartOne.':B'.($startIndexRowChartOne + 1))->getCell("B".$startIndexRowChartOne)->setValue(@$date);
                $sheetChart1->getCell("C".$startIndexRowChartOne )->setValue('Cuộn cắt 1(trip 1)');
                $sheetChart1->getCell("C".($startIndexRowChartOne + 1))->setValue('Cuộn cắt 2(trip 2)');
                // Fill data to sheet 'DATA_BD1'
                foreach($value['data'] as $title => $val){
                    $sheetChart1->getCell($table[$index].$startIndexRowChartOne)->setValue($val);
                    $startIndexRowChartOne++;
                }
                // Fill data to sheet 'DATA_BD2'
                $tableChart = 'tableChart'.($index + 1);
                $j = 0;
                foreach($value['data'] as $val){
                    $sheetChart2->getCell($$tableChart[$j].$startIndexRowChartMulti)->setValue($val);
                    $j++;
                }
            }
            // Fill data to sheet 'DATA_BD2'
            foreach($columnDateChart2 as $column){
                $sheetChart2->getCell($column.$startIndexRowChartMulti)->setValue($date);
            }
            $startIndexRowChartMulti++;
        }
        // remove sheet DATA_BD1 and chart when export multi report
        if($count > 1){
            removeSheetAndHiddenChart($spreadsheet, $sheetBD, 'DATA_BD1', 17, 4);
        }else{
            hiddenSheetsAndChart(['DATA_BD2'], $spreadsheet, $sheetBD, 29, 18);
        }
    }

    /**
     * Fill data report cutting time dot three
     * @param array $data
     * @param $count
     * @param $spreadsheet
     */
    private function writeDataCuttingTimeOneReportDotThree(array $data, $count, $spreadsheet)
    {
        $sheetStatistical =  $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
        $sheetChart1 =  $spreadsheet->setActiveSheetIndexByName('DATA_BD1');
        $sheetChart2 =  $spreadsheet->setActiveSheetIndexByName('DATA_BD2');
        $sheetBD =  $spreadsheet->setActiveSheetIndexByName('BIEU_DO');
        $sheetBDG = $spreadsheet->setActiveSheetIndexByName('BANG_DANH_GIA');
        // Follow sheet DATA_BD2
        $startIndexRowChart2 = 5;
        $tableChart1 = ['C', 'D', 'E', 'F'];
        $tableChart2 = ['I', 'J', 'K', 'L'];
        $tableChart3 = ['O', 'P', 'Q', 'R'];
        $columnDateChart2 = ['B', 'H', 'N'];
        // Fill data to sheet 'DATA_BD1'
        $shartIndexRowChart1 = 5;
        $columnTableChart1 = ['D', 'E', 'F', 'G'];
        $startIndexRow = 5;
        foreach($data as $date => $values){
            // Fill data to sheet 'THONG_KE_DU_LIEU'
            $sheetStatistical->mergeCells("B{$startIndexRow}:B".($startIndexRow + 5))->getCell("B{$startIndexRow}")->setValue($date);
            // Fill data 'DATA_BD1'
            $sheetChart1->mergeCells('B'.$shartIndexRowChart1.':B'.($shartIndexRowChart1 + 2))->getCell("B".$shartIndexRowChart1)->setValue(@$date);

            foreach($values as $index => $value){
                // Fill data to sheet 'THONG_KE_DU_LIEU'
                $sheetStatistical->mergeCells("C{$startIndexRow}:C".($startIndexRow + 1))->getCell("C{$startIndexRow}")->setValue(@$value['zphase.zsym']);
                foreach($value['data'] as $title => $val){
                    $sheetStatistical->getCell("D{$startIndexRow}")->setValue($title);
                    $sheetStatistical->getCell("E{$startIndexRow}")->setValue(explode('_', $val)[0]);
                    $sheetStatistical->getCell("F{$startIndexRow}")->setValue(explode('_', $val)[1]);
                    $startIndexRow++;
                }
                // Fill data 'DATA_BD1'
                $sheetChart1->getCell("C".$shartIndexRowChart1)->setValue(@$value['zphase.zsym']);
                $dataPlase = join('_', $value['data']); // result => 1.0_2.0_3.0_4.0
                foreach($columnTableChart1 as $index2 => $columnKey){
                    $sheetChart1->getCell($columnKey.$shartIndexRowChart1)->setValue(explode('_', $dataPlase)[$index2]);
                }
                $shartIndexRowChart1++;
                // Fill data 'DATA_BD2'
                $tableChart = 'tableChart'.($index + 1); // index = 0 => tableChart1 => $tableChart1
                $dataPlase = join('_', $value['data']); // result => 1.0_2.0_3.0_4.0
                foreach($$tableChart as $index2 => $columnKey){
                    $sheetChart2->getCell($columnKey.$startIndexRowChart2)->setValue(explode('_', $dataPlase)[$index2]);
                }
            }
            // Fill data 'DATA_BD2'
            foreach($columnDateChart2 as $column){
                $sheetChart2->getCell($column.$startIndexRowChart2)->setValue($date);
            }
            $startIndexRowChart2++;
        }
        // remove sheet DATA_BD1 and chart when export multi report
        if($count > 1){
            removeSheetAndHiddenChart($spreadsheet, $sheetBD, 'DATA_BD1', 17, 4);
        }else{
            hiddenSheetsAndChart(['DATA_BD2'], $spreadsheet, $sheetBD, 29, 18);
        }
    }
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function externalInspectionReport(Request $request)
    {
        try {
            $ids = explode(',', $request->ids);
            $items = getDataExternalInspection($ids);
            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);

            // Read Template Excel
            $template = storage_path("templates_excel/cao-ap/may-cat/kiem-tra-ben-ngoai.xlsx");
            $spreadsheet = $reader->load($template);

            $sheetStatistical = $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
            $columnTitle = config('attributes.column_title_external_inspection_high_pressure');
            $startIndexRow = 6;
            foreach($items as $item){
                $sheetStatistical->mergeCells("B{$startIndexRow}:B".($startIndexRow + 3))->getCell("B{$startIndexRow}")->setValue(@$item['zlaboratoryDate']);
                foreach($item['data'] as $index => $value){
                    $sheetStatistical->getCell("C{$startIndexRow}")->setValue(@$columnTitle[$index]);
                    $sheetStatistical->getCell("D{$startIndexRow}")->setValue(@$value);
                    $startIndexRow++;
                }
            }

            fillSearchDataToExcelCuttingMachines($sheetStatistical, $request, 5, range('B', 'D'));

            $titleExcel = 'kiem-tra-ben-ngoai-'.time().'.xlsx';
            $url = writeExcel($spreadsheet, $titleExcel);
            return view('machines::external_inspection_report', compact('url'));
        } catch (\Exception $e) {
            Log::error('[Machines] External Inspection Report: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return back()->withInput()->withErrors([$e->getMessage()]);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function contactTimeReport(Request $request)
    {
        $url = $template = '';
        $ids = explode(',', $request->ids);
        $count = 1;
        $numberReport = config('constant.high_pressure_report.report_contact_time');
        $classType = $request->classType;
        try {
            switch ($classType) {
                case '10.2. BBTN Máy cắt 1 buồng cắt 3 bộ truyền động':
                    $count = count($ids);
                    $template = storage_path("templates_excel/cao-ap/may-cat/dien-tro-tiep-xuc/bao-cao-dien-tro-tiep-xuc-one-{$count}.xlsx");
                    break;
                case '10.1 BBTN Máy cắt 1 buồng cắt 1 bộ truyền động':
                    $count = count($ids) / 3;
                    if( is_int($count) ){
                        $template = storage_path("templates_excel/cao-ap/may-cat/dien-tro-tiep-xuc/bao-cao-dien-tro-tiep-xuc-two-{$count}.xlsx");
                    }
                    break;
                default:
                    $count = count($ids) / 3;
                    if( is_int($count) ){
                        $template = storage_path("templates_excel/cao-ap/may-cat/dien-tro-tiep-xuc/bao-cao-dien-tro-tiep-xuc-three-{$count}.xlsx");
                    }
                    break;
            }
            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);
            // Read Template Excel
            $spreadsheet = $reader->load($template);
            // issue https://github.com/PHPOffice/PhpSpreadsheet/issues/74
            libxml_disable_entity_loader(false);

            $result = checkTypeMachines($request, '', config('attributes.default_function_not_use'), config('attributes.contact_time_report.function_get_data'), config('attributes.contact_time_report.function_write_excel'));
            $data = $result['function_get_data']($ids, 'dien-tro-tiep-xuc');
            $function = $result['function_write_excel'];
            if($result['in_helper']){
                $function($data, $count, 'Điện trở tiếp xúc (μΩ)(Main contact resistance)', $spreadsheet);
            }else{
                $this->$function($data, $count, $spreadsheet);
            }
            // handle detached sheet BDG
            if( !empty($request->bdg) ){
                switch ($classType) {
                    case '10.2. BBTN Máy cắt 1 buồng cắt 3 bộ truyền động':
                        $formattedData = getDataForEvaluationBoardV2($ids, 'zCA10_2', ['z38mcrpanum', 'z39mcrpbnum', 'z40mcrpacnum'], ['400151' => 'Điện trở tiếp xúc'], ['zre_min_rtx', 'zre_max_rtx'], 3);
                        break;
                    case '10.1 BBTN Máy cắt 1 buồng cắt 1 bộ truyền động':
                        $formattedData = getDataForEvaluationBoardV1($ids, 'zCA10_1', ['z38mcrpastr', 'id'], ['400151' => 'Điện trở tiếp xúc'], ['zre_min_rtx', 'zre_max_rtx'], 3);
                        break;
                    default:
                        $formattedData = getDataForEvaluationBoardV3($ids, 'zCA10_3', ['z34mcr1num', 'z35mcr2num'], ['400151' => 'Điện trở tiếp xúc'], ['zre_min_rtx', 'zre_max_rtx'], 3);
                        break;
                }
                $sheetBDG = $spreadsheet->setActiveSheetIndexByName('BANG_DANH_GIA');
                $sheetBDG->getCell('D5')->setValue('1 buồng cắt 1 bộ truyền động');
                writeDataSheetBDG($formattedData, $sheetBDG);
                hiddenSheets($spreadsheet, ['BIEU_DO', 'THONG_KE_DU_LIEU', 'DATA_BD1', 'DATA_BD2']);
                fillSearchDataToExcelCuttingMachines($sheetBDG, $request);
                alignmentExcel($sheetBDG, 'C9:D1000');
                alignmentExcel($sheetBDG, 'F9:F1000');
                $sheetBDG->getCell('B5')->setValue('Bảng đánh giá - Báo cáo điện trở tiếp xúc');
                $title = 'bao-cao-dien-tro-tiep-xuc-bang-danh-gia-'.time().'.xlsx';
            }else{
                $sheetStatistical = $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
                hiddenSheets($spreadsheet, ['BANG_DANH_GIA']);
                fillSearchDataToExcelCuttingMachines($sheetStatistical, $request);
                for($i = 8; $i <= 1000; $i++){
                    $sheetStatistical->getRowDimension($i)->setRowHeight(35);
                }
                $spreadsheet->setActiveSheetIndexByName('BIEU_DO');
                $title = 'bao-cao-dien-tro-tiep-xuc-'.time().'.xlsx';
            }
            $url = writeExcel($spreadsheet, $title);
            return view('machines::contact_time', compact('url'));
        } catch (\Exception $e) {
            Log::error('[Machines] Contact time report: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return back()->with(['numberReport' => $numberReport ])->withErrors([$e->getMessage()]);
        }
    }

    /**
     * @param array $data
     * @param $count
     * @param $spreadsheet
     */
    private function writeDataContactTimeReportDotThree(array $data, $count, $spreadsheet)
    {
        // Follow sheet 'THONG_KE_DU_LIEU'
        $sheetStatistical =  $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
        $columnTableStatistical = ['E', 'F', 'G'];
        $startIndexRow = 5;
        // Follow sheet 'DATA_BD1'
        $sheetChart1 =  $spreadsheet->setActiveSheetIndexByName('DATA_BD1');
        $startIndexRowChart1 = 5;
        $columnTableChart1 = ['D', 'E', 'F'];
        // Follow sheet DATA_BD2
        $sheetChart2 =  $spreadsheet->setActiveSheetIndexByName('DATA_BD2');
        $startIndexRowChart2 = 5;
        $tableChart1 = ['C', 'D', 'E'];
        $tableChart2 = ['H', 'I', 'J'];
        $tableChart3 = ['M', 'N', 'O'];
        $columnDateChart2 = ['B', 'G', 'L'];

        $sheetBD =  $spreadsheet->setActiveSheetIndexByName('BIEU_DO');

        foreach($data as $values){
            // Fill data to sheet 'THONG_KE_DU_LIEU'
            $sheetStatistical->mergeCells("B{$startIndexRow}:B".($startIndexRow + 2))->getCell("B{$startIndexRow}")->setValue(@$values['zlaboratoryDate']);
            // Fill data 'DATA_BD1'
            $sheetChart1->mergeCells('B'.$startIndexRowChart1.':B'.($startIndexRowChart1 + 2))->getCell("B".$startIndexRowChart1)->setValue(@$values['zlaboratoryDate']);
            // Fill data to sheet 'DATA_BD2'
            foreach($columnDateChart2 as $index => $column){
                $sheetChart2->getCell($column.$startIndexRowChart2)->setValue(@$values['zlaboratoryDate']);
            }
            // variable help get the corresponding variable (vd: $tableChart1)
            $j = 1;
            foreach($values['phase'] as $phase => $value)
            {
                $valuePhase = explode('_', $value);
                // Fill data to sheet 'THONG_KE_DU_LIEU'
                $sheetStatistical->getCell("C{$startIndexRow}")->setValue(@explode('_', $phase)[1]);
                $sheetStatistical->getCell("D{$startIndexRow}")->setValue('Điện trở tiếp xúc (μΩ) (Main contact resistance)');
                // Fill data to sheet 'DATA_BD1'
                $sheetChart1->getCell("C{$startIndexRowChart1}")->setValue(@explode('_', $phase)[1]);
                foreach($columnTableStatistical as $index => $column){
                    $sheetStatistical->getCell($column.$startIndexRow)->setValue(@$valuePhase[$index]);
                    // Fill data to sheet 'THONG_KE_DU_LIEU'
                    $sheetChart1->getCell($columnTableChart1[$index].$startIndexRowChart1)->setValue(@$valuePhase[$index]);
                    // Fill data to sheet 'DATA_BD1'
                }
                $tableChart = 'tableChart'.$j;
                foreach($$tableChart as $index => $column){
                    $sheetChart2->getCell($column.$startIndexRowChart2)->setValue(@$valuePhase[$index]);
                }
                $j++;
                $startIndexRow++;
                $startIndexRowChart1++;
            }
            $startIndexRowChart2++;
        }
        if($count > 1){
            removeSheetAndHiddenChart($spreadsheet, $sheetBD, 'DATA_BD1', 17, 4);
        }else{
            hiddenSheetsAndChart(['DATA_BD2'], $spreadsheet, $sheetBD, 32, 18);
        }
        alignmentExcel($sheetStatistical, 'D5:D1000');
    }
    /**
     * validate ajax before redirect page preview report
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxValidate(Request $request)
    {
        $ids = $request->ids;
        $classType = $request->classType;
        $errors = [];
        switch ($classType) {
            case '10.2. BBTN Máy cắt 1 buồng cắt 3 bộ truyền động':
                $errors = validateReportDotOne($ids);
                break;
            case '10.1 BBTN Máy cắt 1 buồng cắt 1 bộ truyền động':
                $errors = validateReportDotTwoThree($ids);
                break;
            default:
                $errors = validateReportDotTwoThree($ids);
                break;
        }
        if( !empty($errors) ){
            return response()->json([
                'success' => false,
                'errors' => $errors
            ]);
        }else{
            return response()->json([
                'success' => true,
            ]);
        }
    }
    /**
     * preview share report 6->8
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function shareReportPreview(Request $request)
    {
        $slug = $request->title;
        $ids = explode(',', $request->ids);
        $classType = $request->classType;
        try {
            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);
            switch ($slug) {
                case 'thoi-gian-tiep-xuc-o-che-do-co':
                    $numberReport = config('constant.high_pressure_report.report_contact_time_co');
                    $titleExport = 'thoi-gian-tiep-xuc-o-che-do-co-';
                    $folderName = 'thoi-gian-tiep-xuc-o-che-do-co';
                    $titleView = 'Báo cáo thời gian tiếp xúc ở chế độ CO';
                    $titleCategory = 'Thời gian tiếp xúc ở chế độ CO(ms)';
                    $attrs = [
                        'zCA10_1' => ['z60osocpanum', 'id'],
                        'zCA10_2' => ['z60osocpanum', 'z61osocpbnum', 'z62osocpcnum'],
                        'zCA10_3' => ['z52osco1num', 'z53osco2num'],
                        'label' => ['400103' => 'Thời gian tiếp xúc chế độ CO', '400105' => 'Độ không đồng thời chu trình CO'],
                        'min_max' =>  ['zre_min_std_tco', 'zre_max_std_tco']
                    ];
                    break;
                case 'thoi-gian-ngung-tiep-xuc-o-che-do-o-co':
                    $numberReport = config('constant.high_pressure_report.report_stop_contact_time_oco');
                    $titleExport = 'thoi-gian-ngung-tiep-xuc-o-che-do-o-co-';
                    $folderName = 'thoi-gian-ngung-tiep-xuc-o-che-do-o-co';
                    $titleView = 'Báo cáo thời gian ngừng tiếp xúc ở chế độ O-CO';
                    $titleCategory = 'Thời gian ngừng tiếp xúc ở chế độ O-CO(ms)';
                    $attrs = [
                        'zCA10_1' => ['z57osoccpanum', 'id'],
                        'zCA10_2' => ['z57osoccpanum', 'z58osoccpbnum', 'z59osoccpcnum'],
                        'zCA10_3' => ['z50osoco1num', 'z51osoco2num'],
                        'label' => ['400104' => 'Thời gian ngừng tiếp xúc chế độ O-CO', '400106' => 'Độ không đồng thời chu trình O-CO'],
                        'min_max' =>  ['zre_min_std_oco', 'zre_max_std_oco']
                    ];
                    break;
                default:
                    $numberReport = config('constant.high_pressure_report.report_close_time');
                    $titleExport = 'thoi-gian-dong-';
                    $folderName = 'thoi-gian-dong';
                    $titleView = 'Báo cáo thời gian đóng';
                    $titleCategory = 'Thời gian đóng tiếp điểm chính';
                    $attrs = [
                        'zCA10_1' => ['z38ctmcnum', 'id'],
                        'zCA10_2' => ['z44ctmcpanum', 'z45ctmcpbnum', 'z46ctmcpcnum'],
                        'zCA10_3' => ['z40ctmc1num', 'z4ctmc2num'],
                        'label' => [ '400101' => 'Thời gian đóng', '400102' => 'Độ không đồng thời thời gian đóng'],
                        'min_max' =>  ['zremin_tgd', 'zremax_tgd']
                    ];
                    break;
            }
            switch ($classType) {
                case '10.2. BBTN Máy cắt 1 buồng cắt 3 bộ truyền động':
                    $count = count($ids);
                    $template = storage_path("templates_excel/cao-ap/may-cat/{$folderName}/bao-cao-{$folderName}-one-{$count}.xlsx");
                    $spreadsheet = $reader->load($template);
                    libxml_disable_entity_loader(false);
                    $data = getDataHighPressureShareReport($ids, $folderName);
                    if( !empty($attrs) ){
                        $formattedData = getDataForEvaluationBoardV2($ids, 'zCA10_2', $attrs['zCA10_2'], $attrs['label'], $attrs['min_max']);
                    }
                    writeDataShareReportDotOne($data, $count, $titleCategory, $spreadsheet);
                    break;
                case '10.1 BBTN Máy cắt 1 buồng cắt 1 bộ truyền động':
                    $count = count($ids) / 3;
                    if( is_int($count) ){
                        $template = storage_path("templates_excel/cao-ap/may-cat/{$folderName}/bao-cao-{$folderName}-two-{$count}.xlsx");
                        $spreadsheet = $reader->load($template);
                        libxml_disable_entity_loader(false);
                        $data = getDataHighPressureShareReportDotTwo($ids, $folderName);
                        if( !empty($attrs) ){
                            $formattedData = getDataForEvaluationBoardV1($ids, 'zCA10_1', $attrs['zCA10_1'], $attrs['label'], $attrs['min_max']);
                        }
                        writeDataShareReportDotTwo($data, $count, $titleCategory, $spreadsheet);
                    }
                    break;
                default:
                    $count = count($ids) / 3;
                    if( is_int($count) ){
                        $template = storage_path("templates_excel/cao-ap/may-cat/{$folderName}/bao-cao-{$folderName}-three-{$count}.xlsx");
                        $spreadsheet = $reader->load($template);
                        libxml_disable_entity_loader(false);
                        $data = getDataHighPressureShareReportDotThree($ids, $folderName);
                        if( !empty($attrs) ){
                            $formattedData = getDataForEvaluationBoardV3($ids, 'zCA10_3', $attrs['zCA10_3'], $attrs['label'], $attrs['min_max']);
                        }
                        writeDataShareReportDotThree($data, $count, $titleCategory, $spreadsheet);
                    }
                    break;
            }
            // handle detached sheet BDG
            if( !empty($request->bdg) ){
                switch ($classType) {
                    case '10.2. BBTN Máy cắt 1 buồng cắt 3 bộ truyền động':
                        $formattedData = getDataForEvaluationBoardV2($ids, 'zCA10_2', $attrs['zCA10_2'], $attrs['label'], $attrs['min_max']);
                        break;
                    case '10.1 BBTN Máy cắt 1 buồng cắt 1 bộ truyền động':
                        $formattedData = getDataForEvaluationBoardV1($ids, 'zCA10_1', $attrs['zCA10_1'], $attrs['label'], $attrs['min_max']);
                        break;
                    default:
                            $formattedData = getDataForEvaluationBoardV3($ids, 'zCA10_3', $attrs['zCA10_3'], $attrs['label'], $attrs['min_max']);
                    break;
                }
                $sheetBDG = $spreadsheet->setActiveSheetIndexByName('BANG_DANH_GIA');
                writeDataSheetBDG($formattedData, $sheetBDG);
                fillSearchDataToExcelCuttingMachines($sheetBDG, $request);
                alignmentExcel($sheetBDG, 'C9:C1000');
                alignmentExcel($sheetBDG, 'F9:F1000');
                hiddenSheets($spreadsheet, ['BIEU_DO', 'THONG_KE_DU_LIEU', 'DATA_BD1', 'DATA_BD2']);
                $sheetBDG->getCell('B5')->setValue('Bảng đánh giá - '.$titleView);
                $title = $titleExport.'-bang-danh-gia'.time().'.xlsx';
            }else{
                fillSearchDataToExcelCuttingMachines($spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU'), $request);
                $spreadsheet->setActiveSheetIndexByName('BIEU_DO');
                hiddenSheets($spreadsheet, ['BANG_DANH_GIA']);
                $title = $titleExport.time().'.xlsx';
            }
            $url = writeExcel($spreadsheet, $title);
            return view('machines::share_report_preview', compact('url', 'titleView'));
        } catch (\Exception $e) {
            Log::error('[Machines] Cutting time report: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return back()->with(['ids' => $ids, 'numberReport' => $numberReport ])->withErrors([$e->getMessage()]);
        }
    }
    /**
     * preview insulation resistance report
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function insulationResistanceReport(Request $request)
    {
        $template = '';
        $ids = explode(',', $request->ids);
        $numberReport = config('constant.high_pressure_report.report_insulation_resistance');
        $classType = $request->classType;
        try {
            switch ($classType) {
                case '10.2. BBTN Máy cắt 1 buồng cắt 3 bộ truyền động':
                    $count = count($ids);
                    $template = storage_path("templates_excel/cao-ap/may-cat/dien-tro-cach-dien/bao-cao-dien-tro-cach-dien-one-{$count}.xlsx");
                    break;
                case '10.1 BBTN Máy cắt 1 buồng cắt 1 bộ truyền động':
                    $count = count($ids) / 3;
                    if( is_int($count) ){
                        $template = storage_path("templates_excel/cao-ap/may-cat/dien-tro-cach-dien/bao-cao-dien-tro-cach-dien-two-{$count}.xlsx");
                    }
                    break;
                default:
                    $count = count($ids) / 3;
                    if( is_int($count) ){
                        $template = storage_path("templates_excel/cao-ap/may-cat/dien-tro-cach-dien/bao-cao-dien-tro-cach-dien-three-{$count}.xlsx");
                    }
                    break;
            }
            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);
            // Read Template Excel
            $spreadsheet = $reader->load($template);
            // issue https://github.com/PHPOffice/PhpSpreadsheet/issues/74
            libxml_disable_entity_loader(false);
            $result = checkTypeMachines($request, '', config('attributes.default_function_not_use'), config('attributes.insulation_resistance_report.function_get_data'), config('attributes.insulation_resistance_report.function_write_excel'));
            $data = $result['function_get_data']($ids);
            $function = $result['function_write_excel'];
            $flag = $this->$function($data, $count, $spreadsheet);
            if($flag){
                return back()->with(['ids' => $ids, 'numberReport' => $numberReport ])->withErrors(['Dữ liệu không hợp lệ']);
            }

            fillSearchDataToExcelCuttingMachines($spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU'), $request);

            $spreadsheet->setActiveSheetIndexByName('BIEU_DO')->setSelectedCell('A1');
            $title = 'dien-tro-cach-dien-'.time().'.xlsx';
            $url = writeExcel($spreadsheet, $title);
            return view('machines::insulation_resistance', compact('url'));
        } catch (\Exception $e) {
            Log::error('[Machines] Cutting time report: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return back()->with(['ids' => $ids, 'numberReport' => $numberReport ])->withErrors([$e->getMessage()]);
        }
    }

    /**
     * @param array $data
     * @param $count
     * @param $spreadsheet
     */
    private function writeDataInsulationReportDotOne(array $data, $count, $spreadsheet)
    {
        $flag = false;
        // Follow sheet 'THONG_KE_DU_LIEU'
        $sheetStatistical =  $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
        $columnTableStatistical = ['E', 'F', 'G'];
        $startIndexRow = 5;
        $startIndexRowChart23 = 5;
        $sheetBD = $spreadsheet->setActiveSheetIndexByName('BIEU_DO');
        foreach($data as $item){
            // Fill data sheet 'THONG_KE_DU_LIEU'
            $sheetStatistical->mergeCells("B{$startIndexRow}:B".($startIndexRow + 3))->getCell("B{$startIndexRow}")->setValue(@$item['zlaboratoryDate']);
            foreach($item['data'] as $title => $values){
                $sheetStatistical->mergeCells("C{$startIndexRow}:C".($startIndexRow+1))->getCell("C{$startIndexRow}")->setValue($title);
                foreach($values as $title2 => $value){
                    $sheetStatistical->getCell("D{$startIndexRow}")->setValue($title2);
                    $phase = explode('_', $value);
                    foreach($columnTableStatistical as $index => $column){
                        if( checkValueIsString($phase[$index]) ){
                            $flag = true;
                        }
                        $sheetStatistical->getCell($column.$startIndexRow)->setValue(convertValueDataFromCA(@$phase[$index]));
                    }
                    $startIndexRow++;
                }
            }
            $this->writeDataChartInsulationReportDotOneTwo($item['dataChart'], $spreadsheet, @$item['zlaboratoryDate'], $startIndexRowChart23);
            $startIndexRowChart23++;
        }

        if($count > 1){
            removeSheetAndHiddenChart($spreadsheet, $sheetBD, 'DATA_BD1', 17, 4);
        }else{
            hiddenSheetsAndChart(['DATA_BD2', 'DATA_BD3'], $spreadsheet, $sheetBD, 45, 19);
        }
        alignmentExcel($sheetStatistical, 'C5:D1000');
        // apply style
        for($i = 5; $i <= 1000; $i++){
            $sheetStatistical->getRowDimension($i)->setRowHeight(65);
        }
        return $flag;
    }

    /**
     * @param array $data
     * @param $count
     * @param $spreadsheet
     */
    private function writeDataInsulationReportDotTwo(array $data, $count, $spreadsheet)
    {
        $flag = false;
        // Follow sheet 'THONG_KE_DU_LIEU'
        $sheetStatistical =  $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
        $startIndexRow = 5;
        $sheetBD = $spreadsheet->setActiveSheetIndexByName('BIEU_DO');
        $dataChart = [];
        $startIndexChart23 = 5;
        foreach($data as $date => $items){
            $dataChart = $items['dataChart'];
            unset($items['dataChart']);
            // Fill data sheet 'THONG_KE_DU_LIEU'
            $sheetStatistical->mergeCells("B{$startIndexRow}:B".($startIndexRow + 11))->getCell("B{$startIndexRow}")->setValue($date);
            foreach($items as $item){
                $sheetStatistical->mergeCells("C{$startIndexRow}:C".($startIndexRow + 3))->getCell("C{$startIndexRow}")->setValue($item['zphase.zsym']);
                foreach($item['data'] as $title => $values){
                    $sheetStatistical->mergeCells("D{$startIndexRow}:D".($startIndexRow+1))->getCell("D{$startIndexRow}")->setValue($title);
                    foreach($values as $title2 => $value){
                        if( checkValueIsString($value) ){
                            $flag = true;
                        }
                        $sheetStatistical->getCell("E{$startIndexRow}")->setValue($title2);
                        $sheetStatistical->getCell("F{$startIndexRow}")->setValue(convertValueDataFromCA($value));
                        $startIndexRow++;
                    }
                }
            }
            $this->writeDataChartInsulationReportDotOneTwo($dataChart, $spreadsheet, $date, $startIndexChart23);
            $startIndexChart23++;
        }

        if($count > 1){
            removeSheetAndHiddenChart($spreadsheet, $sheetBD, 'DATA_BD1', 17, 4);
        }else{
            hiddenSheetsAndChart(['DATA_BD2', 'DATA_BD3'], $spreadsheet, $sheetBD, 45, 19);
        }
        alignmentExcel($sheetStatistical, 'D5:E1000');
        // apply style
        for($i = 5; $i <= 1000; $i++){
            $sheetStatistical->getRowDimension($i)->setRowHeight(65);
        }
        return $flag;
    }

    /**
     * @param array $data
     * @param $spreadsheet
     * @param $date
     */
    private function writeDataChartInsulationReportDotOneTwo(array $data, $spreadsheet, $date, $startIndexChart23)
    {
        $flag = false;
        // Follow sheet 'DATA_BD1'
        $sheetChart1 =  $spreadsheet->setActiveSheetIndexByName('DATA_BD1');
        $columnChart1 = ['C', 'D', 'E', 'F'];
        $columnChart2 = ['I', 'J', 'K', 'L'];
        $columnDateChart1 = ['B', 'H'];
        $startIndexRowChart1 = 6;
        $startIndexRowChart2 = 6;
        // Follow sheet 'DATA_BD2'
        $sheetChart2 =  $spreadsheet->setActiveSheetIndexByName('DATA_BD2');
        $columnDateChart2 = ['B', 'F', 'J'];
        $columnChart3 = ['C', 'D'];
        $columnChart4 = ['G', 'H'];
        $columnChart5 = ['K', 'L'];
        $startIndexRowChart3 = 6;
        // Follow sheet 'DATA_BD3'
        $sheetChart3 =  $spreadsheet->setActiveSheetIndexByName('DATA_BD3');

        // Fill data sheet 'DATA_BD1'
        foreach($columnDateChart1 as $index => $column){
            $sheetChart1->mergeCells($column.$startIndexRowChart1.':'.$column.($startIndexRowChart1 + 1))
            ->getCell($column.$startIndexRowChart1)->setValue($date);
        }
        // parameter define get variable $columnChart1, $columnChart2
        $i = 1;
        $k = 2;
        foreach($data as $index => $values){
            $columnChart = 'columnChart'.$i;
            $startIndexRowChart = 'startIndexRowChart'.$i;
            foreach($values as $title => $value){
                $phaseData = explode('_', $value);
                foreach($phaseData as $val){
                    if( checkValueIsString($val) ){
                        $flag = true;
                    }
                }
                array_unshift($phaseData, $title);
                foreach($$columnChart as $index => $column){
                    $sheetChart1->getCell($column.$$startIndexRowChart)->setValue( convertValueDataFromCA($phaseData[$index]) );
                }
                $$startIndexRowChart++;
            }
            // get variable sheetChart by index $k ($sheetChart2, $sheetChart3)
            $sheetName = 'sheetChart'.$k;
            $phase = explode('_', join('_', $values));
            // parameter get value $phase
            $j = 0;
            for($n = 3; $n <= 5; $n++){
                if( checkValueIsString($phase[$j]) || checkValueIsString($phase[($j + 3)]) ){
                    $flag = true;
                }
                // get columnChart variable follow template excel 3 table
                $columnChart = 'columnChart'.$n;
                // template excel 2 column => index 0 and 1
                $$sheetName->getCell($$columnChart[0].$startIndexChart23)->setValue(convertValueDataFromCA($phase[$j]) );
                $$sheetName->getCell($$columnChart[1].$startIndexChart23)->setValue(convertValueDataFromCA($phase[($j + 3)]) );
                $j++;
            }
            $k++;
            $i++;
        }
        // Fill data sheet 'DATA_BD2'
        foreach($columnDateChart2 as $column){
            $sheetChart2->getCell($column.$startIndexChart23)->setValue($date);
            $sheetChart3->getCell($column.$startIndexChart23)->setValue($date);
        }
        return $flag;
    }

    /**
     * @param array $data
     * @param $count
     * @param $spreadsheet
     */
    private function writeDataChartInsulationReportDotThree(array $data, $count, $spreadsheet)
    {
        // Follow sheet 'THONG_KE_DU_LIEU'
        $sheetStatistical =  $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
        $startIndexRow = 5;
        // Follow sheet 'DATA_BD1'
        $sheetChart1 = $spreadsheet->setActiveSheetIndexByName('DATA_BD1');
        $startIndexRowChart1 = 6;
        $startIndexRowChart2 = 6;
        $columnsTableChart1 = range('C', 'I');
        $columnsTableChart2 = range('L', 'R');
        $columnsDateChart1 = ['B', 'K'];
        // Follow sheet 'DATA_BD2', 'DATA_BD3'
        $sheetChart2 = $spreadsheet->setActiveSheetIndexByName('DATA_BD2');
        $sheetChart3 = $spreadsheet->setActiveSheetIndexByName('DATA_BD3');
        $columnsTableChart3 = range('C', 'F');
        $columnsTableChart4 = range('I', 'L');
        $columnsTableChart5 = range('O', 'R');
        $columnsDateChart23 = ['B', 'H', 'N'];

        $sheetBD = $spreadsheet->setActiveSheetIndexByName('BIEU_DO');
        $startIndexRowChart23 = 5;
        foreach($data as $date => $items){
            $dataChart1 = $items['dataChart1'];
            $dataChart23 = $items['dataChart23'];
            unset($items['dataChart1']);
            unset($items['dataChart23']);
            // Fill data sheet 'THONG_KE_DU_LIEU'
            $sheetStatistical->mergeCells("B{$startIndexRow}:B".($startIndexRow + 11))->getCell("B{$startIndexRow}")->setValue($date);
            foreach($items as $item){
                $sheetStatistical->mergeCells("C{$startIndexRow}:C".($startIndexRow + 3))->getCell("C{$startIndexRow}")->setValue($item['zphase.zsym']);
                foreach($item['data'] as $title => $values){
                    $sheetStatistical->mergeCells("D{$startIndexRow}:D".($startIndexRow + 1))->getCell("D{$startIndexRow}")->setValue($title);
                    foreach($values as $title2 => $value){
                        $phaseData = explode('_', $value);
                        $sheetStatistical->getCell("E{$startIndexRow}")->setValue($title2);
                        $sheetStatistical->getCell("F{$startIndexRow}")->setValue($phaseData[0]);
                        $sheetStatistical->getCell("G{$startIndexRow}")->setValue($phaseData[1]);
                        $startIndexRow++;
                    }
                }
            }
            // Fill data sheet 'DATA_BD1'
            foreach($columnsDateChart1 as $column){
                $sheetChart1->mergeCells($column.$startIndexRowChart1.':'.$column.($startIndexRowChart1 + 1))->getCell($column.$startIndexRowChart1)->setValue($date);
            }
            $i = 1;
            foreach($dataChart1 as $item){
                $columnsChart = 'columnsTableChart'.$i;
                $indexRow = 'startIndexRowChart'.$i;
                foreach($item as $title => $value){
                    $dataPhase = explode('_', $value);
                    array_unshift($dataPhase, $title);
                    foreach($$columnsChart as $index => $column){
                        $sheetChart1->getCell($column.($$indexRow))->setValue($dataPhase[$index]);
                    }
                    $$indexRow++;
                }
                $i++;
            }
            // Fill data sheet 'DATA_BD2', 'DATA_BD3'
            $sheetIndex = 2;
            foreach($dataChart23 as $item){
                $sheetName = 'sheetChart'.$sheetIndex;
                foreach($columnsDateChart23 as $column){
                    $$sheetName->getCell($column.$startIndexRowChart23)->setValue($date);
                }
                $k = 3;
                foreach($item as $value){
                    $columnChart = 'columnsTableChart'.$k;
                    $dataPhase = explode('_', $value);
                    foreach($$columnChart as $index => $column){
                        $$sheetName->getCell($column.$startIndexRowChart23)->setValue($dataPhase[$index]);
                    }
                    $k++;
                }
                $sheetIndex++;
            }
            $startIndexRowChart23++;
        }

        if($count > 1){
            removeSheetAndHiddenChart($spreadsheet, $sheetBD, 'DATA_BD1', 18, 4);
        }else{
            hiddenSheetsAndChart(['DATA_BD2', 'DATA_BD3'], $spreadsheet, $sheetBD, 45, 19);
        }
        alignmentExcel($sheetStatistical, 'D5:E1000');
        // apply style
        for($i = 5; $i <= 1000; $i++){
            $sheetStatistical->getRowDimension($i)->setRowHeight(65);
        }
    }
    // report 14->17
    public function shareReportTable(Request $request)
    {
        try {
            $ids = explode(',', $request->ids);
            $classType = $request->classType;
            $title = $request->title;
            $result = getDataShareReport($ids, $classType, $title);
            $data = $result['data'];
            $attrs = $result['attrs'];
            switch ($title) {
                case 'dac-tinh-dien-tro-rong':
                    $titleChart = $titleView = 'Báo cáo đo điện trở động';
                    $titleExport = 'bao-cao-do-dien-tro-dong';
                    $numberReport = config('constant.high_pressure_report.report_wide_resistance');
                    break;
                case 'dac-tinh-hanh-trinh':
                    $titleChart = $titleView = 'Báo cáo đo đặc tính hành trình';
                    $titleExport = 'bao-cao-do-dac-tinh-hanh-trinh';
                    $numberReport = config('constant.high_pressure_report.report_cruise_characteristics');
                    break;
                case 'phong-dien-cuc-bo':
                    $titleChart = $titleView = 'Báo cáo đo phóng điện cục bộ';
                    $titleExport = 'bao-cao-do-phong-dien-cuc-bo';
                    $numberReport = config('constant.high_pressure_report.report_partial_discharge');
                    break;
                default:
                    $titleChart = $titleView = 'Báo cáo đo đặc tính first-trip';
                    $titleExport = 'bao-cao-do-dac-tinh-first-trip';
                    $numberReport = config('constant.high_pressure_report.report_first_trip');
            }
            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);
            $template = storage_path("templates_excel/cao-ap/may-cat/template-share-report.xlsx");
            $spreadsheet = $reader->load($template);
            $sheetStatistical = $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
            $sheetStatistical->getCell('B2')->setValue($titleChart);
            $columns = range('B', 'G');
            $startIndexRow = 5;
            foreach($data as $item){
                foreach($attrs as $index => $key){
                    $sheetStatistical->getCell($columns[$index].$startIndexRow)->setValue(@$item[$key]);
                }
                $startIndexRow++;
            }
            fillSearchDataToExcelCuttingMachines($sheetStatistical, $request);
            $title = $titleExport.time().'.xlsx';
            $url = writeExcel($spreadsheet, $title);
            return view('machines::share_report_table_preview', compact('url', 'titleView'));
        } catch (\Exception $e) {
            Log::error('[Machines] Report share table: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return back()->with(['ids' => $ids, 'numberReport' => $numberReport ])->withErrors([$e->getMessage()]);
        }
    }

    /**
     * get report by device id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxGetReport(Request $request)
    {
        try {
            $id = $request->get('id');
            $data =  getReportsHighPressureTransformers($id, $request);
            $html = view('machines::data-report')->with('items', $data)->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Exception $e) {
            Log::error('[Machines] Ajax get report by device id: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($id));
            return response()->json([
                'error' => [$e->getMessage()]
            ]);
        }
    }

    /**
     * get device by request
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxGetDevice(Request $request)
    {
        try {
            $data =  getDataHighPressureTransformers($request);
            if( empty($data) ){
                return response()->json([
                    'error' => ['Không tìm thấy thiết bị phù hợp!']
                ]);
            }
            usort($data, function($a, $b){
                return Str::slug(strtolower(str_replace(' ', '', $a['name']))) <=> Str::slug(strtolower(str_replace(' ', '', $b['name'])));
            });
            $html = view('machines::data-device')->with('items', $data)->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Exception $e) {
            Log::error('[Machines] Ajax filter device: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return response()->json([
                'error' => [$e->getMessage()]
            ]);
        }
    }

    /**
     * index high pressure transformers
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function indexMBA(Request $request)
    {
        $areas = Cache::remember('list_areas', 7200, function () {
            return getDataFromService('doSelect', 'zArea', [], ' zsym IS NOT NULL AND active_flag = 1', 200);
        });
        sortData($areas);
        $manufactures = collect(getDataFromService('doSelect', 'zManufacturer', ['id', 'zsym'], 'zsym IS NOT NULL AND zclass = 1002785', 300))->pluck('zsym', 'id')->toArray();
        asort($manufactures);
        $items = getDataHighPressureTransformers($request);
        sortData($items, 'asc', 'name');
        return view('machines::index_mba', compact('areas', 'manufactures', 'items'));
    }

    /**
     * overview check transformers report
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function overviewCheckReport(Request $request)
    {
        try {
            $numberReport = config('constant.high_pressure_transformers_report.report_overview_check');
            $ids = explode(',', $request->ids);
            $items = getDataHighPressureTransformersDevice($ids);
            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);
            $template = storage_path("templates_excel/bao_cao_cao_ap/may_bien_ap/kiem-tra-tong-quan.xlsx");
            $spreadsheet = $reader->load($template);
            $sheetStatistical = $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
            $startIndexRow = 5;
            foreach($items as $item){
                $sheetStatistical->mergeCells("B{$startIndexRow}:B".($startIndexRow + 17))->getCell("B{$startIndexRow}")->setValue($item['zlaboratoryDate']);
                unset($item['zlaboratoryDate']);
                unset($item['zCI_Device.zStage']);
                foreach($item as $value){
                    $value = explode('_', $value);
                    $sheetStatistical->getCell("C{$startIndexRow}")->setValue($value[0]);
                    $sheetStatistical->getCell("D{$startIndexRow}")->setValue($value[1]);
                    $sheetStatistical->mergeCells("E{$startIndexRow}:F{$startIndexRow}")->getCell("E{$startIndexRow}")->setValue($value[2]);
                    $startIndexRow++;
                }
                $sheetStatistical->mergeCells( 'C'.($startIndexRow - 18).':C'.($startIndexRow - 14));
                $sheetStatistical->mergeCells( 'C'.($startIndexRow - 13).':C'.($startIndexRow - 11));
                $sheetStatistical->mergeCells( 'C'.($startIndexRow - 10).':C'.($startIndexRow - 9));
                $sheetStatistical->mergeCells( 'C'.($startIndexRow - 8).':C'.($startIndexRow - 7));
                $sheetStatistical->mergeCells( 'C'.($startIndexRow - 6).':C'.($startIndexRow - 6));
                $sheetStatistical->mergeCells( 'C'.($startIndexRow - 5).':D'.($startIndexRow - 5));
                $sheetStatistical->mergeCells( 'C'.($startIndexRow - 4).':C'.($startIndexRow - 2));
                $sheetStatistical->mergeCells( 'C'.($startIndexRow - 1).':C'.($startIndexRow - 1));
            }

            fillSearchDataToExcelHighPressure($sheetStatistical, $request);

            $title = 'kiem-tra-tong-quan-'.time().'.xlsx';
            $url = writeExcel($spreadsheet, $title);
            return view('machines::overview_check', compact('url'));
        } catch (\Exception $e) {
            Log::error('[Machines MBA] Overview check report: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return back()->with(['ids' => $ids, 'numberReport' => $numberReport ])->withErrors([$e->getMessage()]);
        }
    }
    // ajax validate reports of one class.type before preview report
    public function ajaxValidateReportOneTypeOfRecord(Request $request)
    {
        try {
            $ids = $request->ids;
            $uniqueClassType = collect(getDataReportHighPressureTransformers($ids))->unique(function($item){
                return $item['class.type'];
            })->count();
            if( $uniqueClassType > 1 ){
                return response()->json([
                    'error' => ['Các biên bản để xuất báo cáo phải thuộc cùng 1 loại biên bản!']
                ]);
            }
            return response()->json([
                'success' => true
            ]);
        } catch (\Exception $e) {
            Log::error('[Machines MBA] Ajax validate report one type of record: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return response()->json([
                'error' => [ $e->getMessage() ]
            ]);
        }
    }
    /**
     * fill data transformers report 12 -> 21(follow spec)
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function writeDataShareReportTransformers(Request $request)
    {
        try {
            $ids = explode(',', $request->ids);
            $title = $request->title;
            $result = getDataShareReportTransformers($ids, $title);
            $data = $result['data'];
            $attrs = $result['attrs'];
            switch ($title) {
                case 'dac-tinh-dap-ung-tan-so-quet':
                    $titleChart = $titleView = 'Báo cáo đo đặc tính đáp ứng tần số quét';
                    $titleExport = 'bao-cao-dac-tinh-dap-ung-tan-so-quet-';
                    $numberReport = config('constant.high_pressure_transformers_report.report_sweep_frequency');
                    break;
                case 'do-ham-luong-am-trong-cach-dien-ran':
                    $titleChart = $titleView = 'Báo cáo Đo hàm lượng ẩm trong cách điện rắn';
                    $titleExport = 'bao-cao-do-ham-luong-am-trong-cach-dien-ran-';
                    $numberReport = config('constant.high_pressure_transformers_report.report_solid_insulation');
                    break;
                case 'do-phong-dien-cuc-bo-online':
                    $titleChart = $titleView = 'Báo cáo Đo phóng điện cục bộ online';
                    $titleExport = 'bao-cao-do-phong-dien-cuc-bo-online-';
                    $numberReport = config('constant.high_pressure_transformers_report.report_local_electricity_online');
                    break;
                case 'do-phong-dien-cuc-bo-offline':
                    $titleChart = $titleView = 'Báo cáo Đo phóng điện cục bộ offline';
                    $titleExport = 'bao-cao-do-phong-dien-cuc-bo-offline-';
                    $numberReport = config('constant.high_pressure_transformers_report.report_local_electricity_offline');
                    break;
                case 'thi-nghiem-bo-dieu-ap-duoi-tai':
                    $titleChart = $titleView = 'Báo cáo Thí nghiệm bộ điều áp dưới tải';
                    $titleExport = 'bao-cao-thi-nghiem-bo-dieu-ap-duoi-tai-';
                    $numberReport = config('constant.high_pressure_transformers_report.report_pressure_under_load');
                    break;
                case 'thi-nghiem-dien-ap-xoay-chieu-tang-cao':
                    $titleChart = $titleView = 'Báo cáo Thí nghiệm điện áp xoay chiều tăng cao';
                    $titleExport = 'bao-cao-thi-nghiem-dien-ap-xoay-chieu-tang-cao-';
                    $numberReport = config('constant.high_pressure_transformers_report.report_ac_voltage');
                    break;
                case 'thi-nghiem-dien-ap-ac-cam-ung':
                    $titleChart = $titleView = 'Báo cáo Thí nghiệm điện áp AC cảm ứng';
                    $titleExport = 'bao-cao-thi-nghiem-dien-ap-ac-cam-ung-';
                    $numberReport = config('constant.high_pressure_transformers_report.report_inductive_ac_voltage');
                    break;
                case 'do-ton-that-khong-tai-va-dong-dien-khong-tai-o-dien-ap-dinh-muc':
                    $titleChart = $titleView = 'Báo cáo Đo tổn thất không tải và dòng điện không tải ở điện áp định mức';
                    $titleExport = 'bao-cao-do-ton-that-khong-tai-va-dong-dien-khong-tai-o-dien-ap-dinh-muc-';
                    $numberReport = config('constant.high_pressure_transformers_report.report_no_load_loss');
                    break;
                case 'do-ton-that-co-tai-va-dien-ap-ngan-mach':
                    $titleChart = $titleView = 'Báo cáo Đo tổn thất có tải và điện áp ngắn mạch';
                    $titleExport = 'bao-cao-do-ton-that-co-tai-va-dien-ap-ngan-mach-';
                    $numberReport = config('constant.high_pressure_transformers_report.report_short_circuit_voltage');
                    break;
                case 'to-dau-day':
                    $titleChart = $titleView = 'Báo cáo Tổ đấu dây';
                    $titleExport = 'bao-cao-to-dau-day-';
                    $numberReport = config('constant.high_pressure_transformers_report.report_terminal_element');
                    break;
                default:
                    $titleChart = $titleView = 'Báo cáo trở kháng rò';
                    $titleExport = 'bao-cao-tro-khang-ro-';
                    $numberReport = config('constant.high_pressure_transformers_report.report_leakage_impedance');
                    break;
            }
            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);
            $template = storage_path("templates_excel/bao_cao_cao_ap/may_bien_ap/template-share-report-transformers.xlsx");
            $spreadsheet = $reader->load($template);
            $sheetStatistical = $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
            $sheetStatistical->getCell('B2')->setValue($titleChart);
            $columns = range('B', 'G');
            $startIndexRow = 5;
            foreach($data as $item){
                foreach($attrs as $index => $key){
                    $sheetStatistical->getCell($columns[$index].$startIndexRow)->setValue(@$item[$key]);
                }
                $startIndexRow++;
            }

            fillSearchDataToExcelHighPressure($sheetStatistical, $request);

            $title = $titleExport.time().'.xlsx';
            $url = writeExcel($spreadsheet, $title);
            return view('machines::report_transformers', compact('url', 'titleView'));
        } catch (\Exception $e) {
            Log::error('[Machines] Report transformers share table: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return back()->with(['ids' => $ids, 'numberReport' => $numberReport ])->withErrors([$e->getMessage()]);
        }
    }
    /**
     * report porcelain test transformers
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|string|string[]
     */
    public function reportPorcelainTest(Request $request)
    {
        try {
            $ids = explode(',', $request->ids);
            $data = getDataReportPorcelainTest($ids);
            $numberReport = config('constant.high_pressure_transformers_report.report_porcelain_test');
            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);
            $count = count($ids);
            $template = storage_path("templates_excel/bao_cao_cao_ap/may_bien_ap/bao-cao-phan-tich-ket-qua-thi-nghiem-cac-su-dau-vao-{$count}.xlsx");
            $spreadsheet = $reader->load($template);
            $sheetStatistical = $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
            $columns = range('F', 'K');
            $indexRow = 6;
            // Fill data sheet 'THONG_KE_DU_LIEU'
            foreach($data as $item){
                if($item['obj'] == 'zCA13_5'){
                    $sheetStatistical->mergeCells("B{$indexRow}:B".( $indexRow + 13))->getCell("B{$indexRow}")->setValue($item['zlaboratoryDate']);
                }else{
                    $sheetStatistical->mergeCells("B{$indexRow}:B".( $indexRow + 7))->getCell("B{$indexRow}")->setValue($item['zlaboratoryDate']);
                }
                foreach($item['data'] as $phase => $values){
                    $phaseData = explode('_', $phase);
                    $sheetStatistical->mergeCells("C{$indexRow}:C".($indexRow + 1))->getCell("C{$indexRow}")->setValue($phaseData[0]);
                    $sheetStatistical->mergeCells("D{$indexRow}:D".($indexRow + 1))->getCell("D{$indexRow}")->setValue($phaseData[1]);
                    $values = explode('_', $values);
                    $i = $j = 0;
                    foreach($values as $index => $value){
                        $sheetStatistical->getCell("E{$indexRow}")->setValue($i == 0 ? 'C1' : 'C2');
                        $sheetStatistical->getCell($columns[$j].$indexRow)->setValue(convertValueDataFromCA($value, self::END_POINT_PORCELAINTEST_TEST));
                        $j++;
                        if(($index + 1) % 6 == 0){
                            $indexRow++;
                            $j = 0;
                            $i = 1;
                        }
                    }
                }
            }
            $sheetChart1 = $spreadsheet->setActiveSheetIndexByName('DATA_BD1');
            $sheetChart2 = $spreadsheet->setActiveSheetIndexByName('DATA_BD2');
            $sheetChart3 = $spreadsheet->setActiveSheetIndexByName('DATA_BD3');
            $sheetChartMulti1 = $spreadsheet->setActiveSheetIndexByName('DATA_BD1_MULTI');
            $sheetChartMulti2 = $spreadsheet->setActiveSheetIndexByName('DATA_BD2_MULTI');
            $sheetChartMulti3 = $spreadsheet->setActiveSheetIndexByName('DATA_BD3_MULTI');
            $sheetBD = $spreadsheet->setActiveSheetIndexByName('BIEU_DO');
            hiddenSheets($spreadsheet, ['DO_LECH_DIEN_DUNG', 'BANG_DANH_GIA']);
            // single report
            if(count($ids) == 1){
                $columnsTable1 = ['D', 'E'];
                $columnsTable2 = ['H', 'I'];
                // Fill data to sheet chart one report
                writeDataToChartTranformersChartSingle($data, $columnsTable1, 'attrs_chart_1', 5, $sheetChart1);
                writeDataToChartTranformersChartSingle($data, $columnsTable2, 'attrs_chart_1', 5, $sheetChart1);
                writeDataToChartTranformersChartSingle($data, $columnsTable1, 'attrs_chart_2', 5, $sheetChart2);
                writeDataToChartTranformersChartSingle($data, $columnsTable2, 'attrs_chart_2', 5, $sheetChart2);
                writeDataToChartTranformersChartSingle($data, $columnsTable1, 'attrs_chart_3', 5, $sheetChart3);
                writeDataToChartTranformersChartSingle($data, $columnsTable2, 'attrs_chart_3', 5, $sheetChart3);
                $arrayColumnTable1 = ['C', 'D', 'E'];
                $arrayColumnTable2 = ['G', 'H', 'I'];
                // Hidden table data has phase Am, Bm, Cm
                if($data[0]['obj'] == 'zCA13_5'){
                    hiddenColumnAndChart($arrayColumnTable1, $sheetChart1, $sheetBD, 34, 21);
                    hiddenColumnAndChart($arrayColumnTable1, $sheetChart2, $sheetBD, 63, 50);
                    hiddenColumnAndChart($arrayColumnTable1, $sheetChart3, $sheetBD, 91, 79);
                }else{
                    // Hidden table data not has phase Am, Bm, Cm
                    hiddenColumnAndChart($arrayColumnTable2, $sheetChart1, $sheetBD, 20, 7);
                    hiddenColumnAndChart($arrayColumnTable2, $sheetChart2, $sheetBD, 49, 37);
                    hiddenColumnAndChart($arrayColumnTable2, $sheetChart3, $sheetBD, 78, 66);
                }
                // Hidden sheet multi report
                hiddenSheetsAndChart(['DATA_BD1_MULTI', 'DATA_BD2_MULTI', 'DATA_BD3_MULTI'], $spreadsheet, $sheetBD, 338, 93);
                // fill to title
                $sheetBD->getCell('D6')->setValue(trim($sheetBD->getCell('D6')->getValue()). ' (MΩ)');
                $sheetBD->getCell('D36')->setValue(trim($sheetBD->getCell('D36')->getValue()). ' (%)');
                $sheetBD->getCell('D65')->setValue(trim($sheetBD->getCell('D65')->getValue()). ' (pF)');
            }else{ // multi report
                for($i = 1; $i <= 3; $i++){
                    $sheetName = 'sheetChartMulti'.$i;
                    $attrsIndexNamePrefix = '';
                    // get name attrs prefix
                    switch ($i) {
                        case 1:
                            $attrsIndexNamePrefix = 'attrs_chart_insulation_phase_';
                            break;
                        case 2:
                            $attrsIndexNamePrefix = 'attrs_chart_dielectric_loss_phase_';
                            break;
                        default:
                            $attrsIndexNamePrefix = 'attrs_chart_capacitance_phase_';
                            break;
                    }
                    // Fill data sheet chart multi report
                    writeDataToChartTranformersChartMulti($data, ['B', 'C', 'D'], $attrsIndexNamePrefix.'a', 5, $$sheetName);
                    writeDataToChartTranformersChartMulti($data, ['F', 'G', 'H'], $attrsIndexNamePrefix.'b', 5, $$sheetName);
                    writeDataToChartTranformersChartMulti($data, ['J', 'K', 'L'], $attrsIndexNamePrefix.'c', 5, $$sheetName);
                    writeDataToChartTranformersChartMulti($data, ['N', 'O', 'P'], $attrsIndexNamePrefix.'n', 5, $$sheetName);
                    writeDataToChartTranformersChartMulti($data, ['R', 'S', 'T'], $attrsIndexNamePrefix.'am', 5, $$sheetName);
                    writeDataToChartTranformersChartMulti($data, ['V', 'W', 'X'], $attrsIndexNamePrefix.'bm', 5, $$sheetName);
                    writeDataToChartTranformersChartMulti($data, ['Z', 'AA', 'AB'], $attrsIndexNamePrefix.'cm', 5, $$sheetName);
                }
                // Hidden sheet one report
                hiddenSheetsAndChart(['DATA_BD1', 'DATA_BD2', 'DATA_BD3'], $spreadsheet, $sheetBD, 93, 4);
                // report type not has phase Am, Bm, Cm
                if($data[0]['obj'] != 'zCA13_5'){
                    $columnsHiddenMulltiChart = ['Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC'];
                    hiddenColumnAndChart($columnsHiddenMulltiChart, $sheetChartMulti1, $sheetBD, 176, 143);
                    hiddenColumnAndChart($columnsHiddenMulltiChart, $sheetChartMulti2, $sheetBD, 257, 224);
                    hiddenColumnAndChart($columnsHiddenMulltiChart, $sheetChartMulti3, $sheetBD, 339, 305);
                }
                // two report for fill data sheet 'DO_LECH_DIEN_DUNG' and 'BANG_DANH_GIA'
                if(count($ids) == 2){
                    $sheetShow = ['DO_LECH_DIEN_DUNG', 'BANG_DANH_GIA'];
                    foreach($sheetShow as $sheet){
                        $spreadsheet->getSheetByName($sheet)
                        ->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VISIBLE);
                    }
                    // check pass fail report data
                    $result = $this->validatePassFail($data);
                    if( $result ){
                        return back()->with(['ids' => $ids, 'numberReport' => $numberReport ])->withErrors(['Dữ liệu đầu vào không hợp lệ!']);
                    }
                    if($data[0]['obj'] != 'zCA13_5'){
                        $this->writeDataSheetCapacitanceDeviation($data, range('C', 'F'), 6, $spreadsheet);
                    }else{
                        $this->writeDataSheetCapacitanceDeviation($data, range('I', 'O'), 6, $spreadsheet);
                    }
                    $this->writeDataSheetBDG($data, $spreadsheet);
                }
                // fill to title
                $rowMW = [97, 110, 121, 132, 143, 155, 166];
                foreach($rowMW as $row){
                    $sheetBD->getCell('D'.$row)->setValue(trim($sheetBD->getCell('D'.$row)->getValue()). ' (MΩ)');
                }
                $rowPercent = [178, 191, 202, 213, 224, 236, 247];
                foreach($rowPercent as $row){
                    $sheetBD->getCell('D'.$row)->setValue(trim($sheetBD->getCell('D'.$row)->getValue()). ' (%)');
                }
                $rowPF = [259, 272, 283, 294, 305, 317, 328];
                foreach($rowPF as $row){
                    $sheetBD->getCell('D'.$row)->setValue(trim($sheetBD->getCell('D'.$row)->getValue()). ' (pF)');
                }
                // fill title chart "BIEU_DO"
                for($i = 6; $i <= 27; $i++){
                    $chart = $sheetBD->getChartByName('chart'.$i);
                    if( !is_null($chart) ){
                        $chart->setTitle(new \PhpOffice\PhpSpreadsheet\Chart\Title('Biểu đồ phân tích một pha qua các năm'));
                    }
                }
            }
            fillSearchDataToExcelHighPressure($sheetStatistical, $request);
            $spreadsheet->setActiveSheetIndexByName('BIEU_DO');
            $title = 'bao-cao-phan-tich-ket-qua-thi-nghiem-cac-su-dau-vao-'.time().'.xlsx';
            $url = writeExcel($spreadsheet, $title);
            $metaTitle = 'Báo cáo phân tích kết quả thí nghiệm các sứ đầu vào';
            return view('machines::preview_share', compact('url', 'metaTitle'));
        } catch (\Exception $e) {
            Log::error('[Machines] Report Porcelain test transformers : Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return back()->with(['ids' => $ids, 'numberReport' => $numberReport ])->withErrors([$e->getMessage()]);
        }
    }

    /**
     * validate data pass, fail for sheet BDG
     * @param array $data
     * @return bool
     */
    private function validatePassFail(array $data)
    {
        $obj = $data[0]['obj'];
        $attrs = config('highpressuretransformers.report_porcelain_test.'.$obj);
        foreach($attrs as $index => $attr){
            if( !is_array($attr) ){
                unset($attrs[$index]);
            }
        }
        $flag = false;
        $attrsFlatten = array_chunk(collect($attrs)->flatten()->toArray(), 6);
        foreach($attrsFlatten as $attr){
            $dataFirstDay = collect(getArrayValueByArrayKey($data[0], $attr))->filter(function($item){
                return !is_null($item) && $item !== '';
            })->toArray();
            $dataSecondDay = collect(getArrayValueByArrayKey($data[1], $attr))->filter(function($item){
                return !is_null($item) && $item !== '';
            })->toArray();
            if( (!empty($dataFirstDay) || !empty($dataSecondDay)) && count($dataFirstDay) != count($dataSecondDay) && (count($dataFirstDay) < 6 || count($dataSecondDay) < 6) ){
                $flag = true;
            }
        }
        return $flag;
    }
    // Fill data to sheet 'DO_LECH_DIEN_DUNG' report porcelain test
    private function writeDataSheetCapacitanceDeviation(array $data, array $columns, int $startIndex, $spreadsheet)
    {
        $sheetActive = $spreadsheet->setActiveSheetIndexByName('DO_LECH_DIEN_DUNG');
        $sheetActive->getCell('C4')->setValue("Độ lệch điện dung sứ giữa 2 lần thí nghiệm ngày {$data[0]['zlaboratoryDate']} và {$data[1]['zlaboratoryDate']}");
        $sheetActive->getCell('I4')->setValue("Độ lệch điện dung sứ giữa 2 lần thí nghiệm ngày {$data[0]['zlaboratoryDate']} và {$data[1]['zlaboratoryDate']}");
        $sheetActive->getCell('B2')->setValue(trim($sheetActive->getCell('B2')->getValue()) . ' (%)');
        $sheetActive->getCell('H2')->setValue(trim($sheetActive->getCell('H2')->getValue()) . ' (%)');
        $obj = $data[0]['obj'];
        $attrs = config('highpressuretransformers.report_porcelain_test.attrs_dien_dung.'.$obj);
        $nextRow = count($attrs) / 2;
        $valueBefore = array_values(getArrayValueByArrayKey($data[0], $attrs));
        $valueAfter = array_values(getArrayValueByArrayKey($data[1], $attrs));
        $values = [];
        // calculate differrence 2 report
        for($i = 0; $i < count($attrs); $i++){
            if( !$valueBefore[$i] || !$valueAfter[$i] || $valueBefore[$i] == 0){
                $values[$i] = '';
            }else{
                $values[$i] = ($valueAfter[$i] - $valueBefore[$i]) / $valueBefore[$i] * 100;
            }
        }
        // Fill data to sheet 'DO_LECH_DIEN_DUNG'
        $i = 0;
        foreach($values as $index => $value){
            $sheetActive->getCell($columns[$i].$startIndex)->setValue(!is_null($value) && $value !== '' ? $value.' %' : '');
            $i++;
            if(($index + 1) % $nextRow == 0){
                $startIndex++;
                $i = 0;
            }
        }
        // check report class.type 13.5
        if($data[0]['obj'] == 'zCA13_5'){
            hiddenColumnAndChart(range('A', 'G'), $sheetActive);
        }else{
            hiddenColumnAndChart(range('G', 'P'), $sheetActive);
        }
    }
    // Fill data to sheet 'BANG_DANH_GIA' report porcelain test
    private function writeDataSheetBDG(array $data, $spreadsheet)
    {
        $sheetBDG = $spreadsheet->setActiveSheetIndexByName('BANG_DANH_GIA');
        $obj = $data[0]['obj'];
        $statusDevice = @$data[0]['zCI_Device.zStage'];
        // attrs by obj
        $attrs = config('highpressuretransformers.report_porcelain_test.attrs_dien_dung_bdg.'.$obj);
        $attrsTg = config('highpressuretransformers.report_porcelain_test.attrs_tg.'.$obj);
        // get data report date before(index 0), after(index 1)
        $valueBefore = array_values(getArrayValueByArrayKey($data[0], $attrs));
        $valueAfter = array_values(getArrayValueByArrayKey($data[1], $attrs));
        $values = [];
        // calculate follow specs (before - after)/after * 100
        for($i = 0; $i < count($attrs); $i++){
            if(!$valueBefore[$i] || !$valueAfter[$i] || $valueBefore[$i] == 0){
                $values[$i] = '';
            }else{
                $values[$i] = ($valueAfter[$i] - $valueBefore[$i]) / $valueBefore[$i] * 100;
            }
        }
        $phase = [];
        // chunk array each phase has C1, C2 => chunk 2
        $values = array_chunk($values, 2);
        $valueTg = array_chunk(array_values(getArrayValueByArrayKey($data[1], $attrsTg)), 2);
        // 400002 is id status device 'Thí nghiệm sau lắp đặt'
        $percent = $statusDevice == '400002' ? 0.8 : 1.5;
        // check pass or fail
        foreach($values as $index => $value){
            $check = 0;
            if( $value[0] !== '' && $value[1] !== '' && $value[0] < 10 && $value[0] > -5 && $value[1] < 10 && $value[1] > -5 ){
                $check++;
            }
            if( $valueTg[$index][0] !== '' && $valueTg[$index][1] !== '' && $valueTg[$index][0] <= $percent && $valueTg[$index][1] <= $percent ){
                $check++;
            }
            $phase[$index] = 'Fail';
            if($check == 2){
                $phase[$index] = 'Pass';
            }
            // check value each phase C1 or C2
            if( ($value[0] !== '' &&  $value[1] === '' && $value[0] < 10 && $value[0] > -5) || ($value[0] === '' &&  $value[1] !== '' && $value[1] < 10 && $value[1] > -5) && $valueTg[$index][0] <= $percent && $valueTg[$index][1] <= $percent){
                $phase[$index] = 'Pass';
            }
            if( $value[0] === '' && $value[1] === '' && $valueTg[$index][0] === '' && $valueTg[$index][1] === ''){
                $phase[$index] = '';
            }
        }
        // Fill data to sheet 'BANG_DANH_GIA'
        $startIndexRow = 5;
        foreach($phase as $val){
            $sheetBDG->getCell("C{$startIndexRow}")->setValue($val);
            $startIndexRow++;
        }
        if($obj != 'zCA13_5'){
            for ($i = 11; $i >= 9; $i--) {
                $sheetBDG->getRowDimension($i)->setVisible(false);
            }
        }
    }
    /**
     * preview, handle report RateOfChange
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function rateOfChangeReport(Request $request)
    {
        try {
            $ids = explode(',', $request->ids);
            $numberReport = config('constant.high_pressure_transformers_report.report_rate_of_change');
            $reports = getDataRateOfChangeReport($ids);
            $obj = $reports[0]['obj'];
            $attrs = config('highpressuretransformers.report_rate_of_change.'.$obj.'.attrs');
            $templatePrefix = config('highpressuretransformers.report_rate_of_change.'.$obj.'.excel_file_prefix');
            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);
            $count = count($reports);
            if($count == 1){
                $template = storage_path("templates_excel/bao_cao_cao_ap/may_bien_ap/qua-mot-lan-thi-nghiem/{$templatePrefix}{$count}.xlsx");
            }else{
                $template = storage_path("templates_excel/bao_cao_cao_ap/may_bien_ap/qua-cac-lan-thi-nghiem/{$templatePrefix}{$count}.xlsx");
            }
            $spreadsheet = $reader->load($template);
            $sheetStatistical = $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
            $indexRow = 5;
            $count = count($reports);
            // Fill data sheet 'THONG_KE_DU_LIEU'
            foreach($attrs as $key => $attrsChild){
                // hiccup number of table
                $hiccupNumber = (int) explode('_',$key)[1];
                $titleHiccup = [];
                for($i = 1; $i <= $hiccupNumber; $i++){
                    $titleHiccup[] = 'Nấc '.$i;
                }
                // check table has row before title
                $checkHasFirstColumn = count($attrsChild) % 8 == 0 ? false : true;
                if($checkHasFirstColumn){
                    $firstAttr = array_shift($attrsChild);
                }
                foreach($reports as $indexReport => $report){
                    // has first row before title => set value and increment indexRow title(2) firstRow(1)
                    if($checkHasFirstColumn){
                        $value =  explode(':', $sheetStatistical->getCell("B{$indexRow}")->getValue())[0].':';
                        $sheetStatistical->getCell("B{$indexRow}")->setValue("{$value} ".($report[$firstAttr] ? ($report[$firstAttr] + 0) : ''));
                        $indexRow += 3;
                    }else{
                        // else increment indexRow title(2)
                        $indexRow += 2;
                    }
                    // index get hiccup number value
                    $j = 0;
                    // index get $columns name
                    $indexColumn = 0;
                    $columns = range('C', 'J');
                    foreach($attrsChild as $index => $attr){
                        $sheetStatistical->getCell("B{$indexRow}")->setValue($titleHiccup[$j]);
                        $sheetStatistical->getCell($columns[$indexColumn].$indexRow)->setValue(@$report[$attr]);
                        $indexColumn++;
                        // one row has 8 attrs and next row
                        if(($index +1) % 8 == 0){
                            $j++;
                            $indexRow++;
                            $indexColumn = 0;
                        }
                    }
                    // check length data and check loop to last element of data
                    // ex: length data = 3; template excel has 1 table default loop copy 2 table
                    if($count > 1 && ++$indexReport < $count){
                        $increntRow = $checkHasFirstColumn ? 3 : 2;
                        $spreadsheet->getActiveSheet()->insertNewRowBefore($indexRow, $hiccupNumber + $increntRow);
                        copyRows($sheetStatistical, $indexRow - $hiccupNumber - $increntRow, $indexRow, $hiccupNumber + $increntRow, 24);
                    }
                }
                // next table follow excel file increment 2 row
                $indexRow += 2;
            }
            $sheetBD = $spreadsheet->setActiveSheetIndexByName('BIEU_DO');
            $oldTitle = explode('-', $sheetBD->getCell('B2')->getValue());
            if($count == 1){
                $this->fillDataChartSheetRateOfChangeOneReport($reports, $obj, $spreadsheet);
                $sheetBD->getCell('B2')->setValue("Biểu đồ phân tích kết quả thí nghiệm tỷ số biến 3 pha trong 1 thí nghiệm - {$oldTitle[1]}");
                $this->drawChartForReportRateOfChange($reports, $sheetBD, $obj);
            }else{
                $sheetBD->getCell('B2')->setValue("Biểu đồ phân tích kết quả thí nghiệm tỷ số biến 3 pha qua các lần thí nghiệm thí nghiệm - {$oldTitle[1]}");
                $arrayAttrs = config('highpressuretransformers.report_rate_of_change.'.$obj.'.attrs_share');
                $this->fillDataSheetOverDayRateOfChange($reports, $arrayAttrs, $spreadsheet);
                $this->fillDataChartSheetRateOfChangeMultiReport($reports, $obj, $spreadsheet);
                $sheetRatioDeviation = $spreadsheet->setActiveSheetIndexByName('DO_LECH_TI_SO_GIUA_2_LAN');
                // default hidden sheet
                $sheetRatioDeviation->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);
                if($count == 2){
                    // visiable sheet
                    $sheetRatioDeviation->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VISIBLE);
                    $result = $this->fillDataTwoExperimentsRateOfChange($reports, $obj, $sheetRatioDeviation);
                    if($result === 'error'){
                        return back()->with(['ids' => $ids, 'numberReport' => $numberReport ])->withErrors(['Dữ liệu đầu vào không hợp lệ']);
                    }
                    // Create a new worksheet called "KET_QUA_DANH_GIA"
                    $sheetBDG = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'KET_QUA_DANH_GIA');
                    // Attach the "KET_QUA_DANH_GIA" worksheet as the first worksheet in the Spreadsheet object
                    $spreadsheet->addSheet($sheetBDG, 4);
                    $spreadsheet->setActiveSheetIndexByName('KET_QUA_DANH_GIA')->getStyle('A1:Z1000')->applyFromArray([
                        'font' => [
                            'name' => 'Times New Roman',
                            'size' => 16,
                            'bold' => true
                        ]
                    ]);
                    $spreadsheet->setActiveSheetIndexByName('KET_QUA_DANH_GIA')->getCell('A1')->setvalue('Kết quả đánh giá: '. ($result ? 'Pass' : 'Fail'));
                }
            }
            fillSearchDataToExcelHighPressure($sheetStatistical, $request);

            $spreadsheet->setActiveSheetIndexByName('BIEU_DO');
            $url = writeExcel($spreadsheet, 'bao-cao-phan-tich-ket-qua-thi-nghiem-ti-so-bien-'.time().'.xlsx');
            $metaTitle = 'Báo cáo phân tích kết quả thí nghiệm tỉ số biến';
            return view('machines::preview_share', compact('url', 'metaTitle'));
        } catch (\Exception $e) {
            Log::error('[Machines] Report rate of change: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return back()->with(['ids' => $ids, 'numberReport' => $numberReport ])->withErrors([$e->getMessage()]);
        }
    }
    /**
     * Fill data sheet 'DO_LECH_TI_SO_GIUA_2_LAN' when one report for report RateOfChange
     * @param array $data
     * @param $obj
     * @param $spreadsheet
     */
    private function fillDataChartSheetRateOfChangeOneReport(array $data, $obj, $spreadsheet){
        $arrayAttrs = config('highpressuretransformers.report_rate_of_change.'.$obj.'.attrs_share');
        // each $arrayAttrs array is 1 table
        foreach($data as $item){
            foreach($arrayAttrs as $sheetName => $attrs){
                $sheetChart = $spreadsheet->setActiveSheetIndexByName($sheetName);
                $indexRow = 6;
                $columns = range('D', 'F');
                // parameter get columns name
                $j = 0;
                // parameter increment hiccup number
                $i = 1;
                foreach($attrs as $index => $attr){
                    $sheetChart->getCell('C'.$indexRow)->setValue('Nấc '.$i);
                    $sheetChart->getCell($columns[$j].$indexRow)->setValue(@$item[$attr]);
                    $j++;
                    // Each hiccup has 3 phases
                    if( ($index + 1) % 3 == 0 ){
                        $indexRow++;
                        $j = 0;
                        $i++;
                    }
                }
            }
        }
    }
    /**
     * Fill data sheet 'DU_LIEU_QUA_CAC_NGAY' when multi report for report RateOfChange
     * @param array $data
     * @param array $arrayAttrs
     * @param $spreadsheet
     */
    private function fillDataSheetOverDayRateOfChange(array $data, array $arrayAttrs, $spreadsheet)
    {
        $sheetOverDay = $spreadsheet->setActiveSheetIndexByName('DU_LIEU_QUA_CAC_NGAY');
        $indexNext = 5;
        // each $arrayAttrs array is 1 table
        foreach($arrayAttrs as $attrs){
            $startColumns = 'B';
            foreach($data as $item){
                // create array $columns each $data
                $columns = [];
                for($i = 0; $i < 3; $i++){
                    $columns[$i] = ++$startColumns;
                }
                // indexRow follow indexNext when next loop $arrayAttrs
                $indexRow = $indexNext;
                $sheetOverDay->getCell($columns[0].$indexRow)->setValue(@$item['zlaboratoryDate']);
                // Next 1 row title -> increment 2
                $indexRow += 2;
                $i = 0;
                foreach($attrs as $index => $attr){
                    $sheetOverDay->getCell($columns[$i].$indexRow)->setValue(@$item[$attr]);
                    $i++;
                    // Each hiccup has 3 phases
                    if(($index + 1) % 3 == 0){
                        $i = 0;
                        $indexRow++;
                    }
                }
            }
            // Next table follow to excel file
            $indexNext = $indexRow + 2;
        }
    }
    /**
     * Fill data sheet chart when multi report for report RateOfChange
     * @param array $data
     * @param $obj
     * @param $spreadsheet
     */
    private function fillDataChartSheetRateOfChangeMultiReport(array $data, $obj, $spreadsheet)
    {
        $arrayAttrs = config('highpressuretransformers.report_rate_of_change.'.$obj.'.attrs_share');
        // each $arrayAttrs array is 1 table
        foreach($arrayAttrs as $sheetName => $attrs){
            $sheetActive = $spreadsheet->setActiveSheetIndexByName($sheetName);
            $indexRow = 6;
            foreach($data as $item){
                $startColumn = 'B';
                $sheetActive->getCell($startColumn.$indexRow)->setValue(@$item['zlaboratoryDate']);
                foreach($attrs as $index => $attr){
                    ++$startColumn;
                    $sheetActive->getCell($startColumn.$indexRow)->setValue(@$item[$attr]);
                    // Each hiccup has 3 phases and check loop end of $attrs
                    if( ($index + 1) % 3 == 0 && ($index + 1) < count($attrs)){
                        // next column when loop 3 phase -> column date (follow excel file)
                        ++$startColumn;
                        $sheetActive->getCell($startColumn.$indexRow)->setValue(@$item['zlaboratoryDate']);
                    }
                }
                $indexRow++;
            }
        }
    }

    /**
     * Fill data sheet 'DO_LECH_TI_SO_GIUA_2_LAN', calculate Pass - Fail when has 2 report for report RateOfChange
     * @param array $data
     * @param $obj
     * @param $sheetRatioDeviation
     * @return bool|string
     */
    private function fillDataTwoExperimentsRateOfChange(array $data, $obj, $sheetRatioDeviation)
    {
        $attrs = config('highpressuretransformers.report_rate_of_change.'.$obj.'.attrs_share');
        $attrsFlatten = collect($attrs)->flatten()->toArray();
        // check mapping data index 0 and 1
        $attrsChunk = array_chunk($attrsFlatten, 3);
        $flagCheckMap = false;
        foreach($attrsChunk as $attrChunk){
            $dataFirstDay = collect(getArrayValueByArrayKey($data[0], $attrChunk))->filter(function($item){
                return !is_null($item) && $item !== '';
            })->toArray();
            $dataSecondDay = collect(getArrayValueByArrayKey($data[1], $attrChunk))->filter(function($item){
                return !is_null($item) && $item !== '';
            })->toArray();
            if( (!empty($dataFirstDay) || !empty($dataSecondDay)) && count($dataFirstDay) != count($dataSecondDay) && (count($dataFirstDay) < 3 || count($dataSecondDay) < 3) ){
                $flagCheckMap = true;
            }
        }
        if( $flagCheckMap ){
            return 'error';
        }
        // Calculate delta phase between 2 experiment
        $result = [];
        foreach($attrsFlatten as $attr){
            if( is_null($data[1][$attr]) || is_null($data[0][$attr]) || (float) $data[0][$attr] == 0 ){
                $result[$attr] = '';
            }else{
                $result[$attr] = (float) (($data[1][$attr] - $data[0][$attr] ) / $data[0][$attr] * 100);
            }
        }
        // check result pass, fail
        $flag = true;
        $attrsRatioDeviation = config('highpressuretransformers.report_rate_of_change.'.$obj.'.attr_ratio_deviation');
        foreach($attrsRatioDeviation as $index => $attr){
            if( (!empty($data[0][$attr]) || !empty($data[1][$attr])) && ((float)$data[0][$attr] > 0.5 || (float)$data[1][$attr] > 0.5) ){
                $flag = false;
            }
        }
        // Fill data to sheet 'DO_LECH_TI_SO_GIUA_2_LAN'
        $indexRow = 5;
        $columns = range('C', 'E');
        foreach($attrs as $attr){
            // merge 3 column follow excel file => set value $columns[0]
            $currentValue = $sheetRatioDeviation->getCell($columns[0].$indexRow)->getValue();
            $sheetRatioDeviation->getCell($columns[0].$indexRow)->setValue($currentValue . ' Ngày '.@$data[0]['zlaboratoryDate'] . ' và ' .@$data[1]['zlaboratoryDate']);
            // next 2 row title => increment 3
            $indexRow += 3;
            $j = 0;
            foreach($attr as $index => $attrName){
                $sheetRatioDeviation->getCell($columns[$j].$indexRow)->setValue(@$result[$attrName]);
                $j++;
                if( ($index + 1) % 3 == 0  ){
                    $indexRow++;
                    $j = 0;
                }
                if( !empty($result[$attrName]) && (float)$result[$attrName] > 0.5 ){
                    $flag = false;
                }
            }
            // next loop and increment 2 row next fill to table follow excel file
            $indexRow += 2;
        }
        return $flag;
    }

    /**
     * index statisticalExperimental
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function statisticalExperimental(Request $request)
    {
        $items = filterDevice([array_flip(config('constant.device_statistical'))['Máy biến áp']], $request, [], true);
        sortData($items, 'asc', 'name');
        $dvqls = Cache::remember('unitList', 7200, function () {
            return getDataFromService('doSelect', 'zDVQL', ['id', 'zsym'], ' zsym IS NOT NULL AND active_flag = 1', 200);
        });
        sortData($dvqls);
        $tds = Cache::remember('list_td_mba', 7200, function () {
            return getDataFromService('doSelect', 'zTD', ['id', 'zsym'], '', 200);
        });
        sortData($tds);
        return view('machines::statisticalExperimental.index', compact('items', 'dvqls', 'tds'));
    }

    /**
     * ajax get device by request for index statisticalExperimental
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxFilterDevice(Request $request)
    {
        try {
            $items = filterDevice([array_flip(config('constant.device_statistical'))['Máy biến áp']], $request, [], true);
            sortData($items, 'asc', 'name');
            if(empty($items)){
                return response()->json([
                    'error' => ['Không tìm thấy dữ liệu!']
                ]);
            }
            $html = view('machines::statisticalExperimental.device')->with('items', $items)->render();
            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Exception $e) {
            Log::error('[Machines] Ajax get device MBA: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return response()->json([
                'error' => [$e->getMessage()]
            ]);
        }
    }

    /**
     * ajax get number experiments each device
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNumberOfExperiments(Request $request)
    {
        try {
            $items = getNumberOfExperiments($request);
            $html = view('machines::statisticalExperimental.report')->with('items', $items)->render();
            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Exception $e) {
            Log::error('[Machines] Ajax get number of experiments MBA: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return response()->json([
                'error' => [$e->getMessage()]
            ]);
        }
    }

    /**
     * export excel file statisticalExperimental
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function getNumberOfExperimentsExport(Request $request)
    {
        return Excel::download(new StatisticalExperimentalExport($request), 'Báo cáo thống kê công tác thí nghiệm.xlsx');
    }

    /**
     * index report statistical list and number device
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function statisticalListAndNumberDevice()
    {
        $devices = config('constant.device_statistical');
        asort($devices);
        $zStage = getDataFromService('doSelect', 'zETC_Status', ['id', 'zsym'], 'active_flag = 1', 100);
        sortData($zStage);
        $dvqls = Cache::remember('unitList', 7200, function () {
            return getDataFromService('doSelect', 'zDVQL', ['id', 'zsym'], 'zsym IS NOT NULL AND active_flag = 1', 200);
        });
        sortData($dvqls);
        $areas = Cache::remember('list_areas', 7200, function () {
            return getDataFromService('doSelect', 'zArea', ['id', 'zsym'], 'zsym IS NOT NULL AND active_flag = 1', 200);
        });
        sortData($areas);
        $years = getDataFromService('doSelect', 'zYear_of_Manafacture', ['zsym', 'id'], '', 100);
        sortData($years, 'desc');

        return view('machines::statisticalListAndNumberDevice.index', compact('devices', 'zStage', 'dvqls', 'areas', 'years'));
    }

    /**
     * ajax get zCI_Device_Type by deviceIds
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxGetDeviceType(Request $request)
    {
        try {
            $devices = config('constant.device_statistical');
            if(empty($request->devices)){
                $request->offsetSet('devices', array_keys($devices));
            }
            $deviceTypes =  getDataFromService('doSelect', 'zCI_Device_Type', ['id', 'zsym', 'zclass'], "zclass IN (" . implode(',', $request->devices) . ") AND active_flag = 1", 100);
            sortData($deviceTypes);
            return response()->json([
                'success' => true,
                'data' => $deviceTypes
            ]);
        } catch (\Exception $e) {
            Log::error('[Machines] Statistical list and number device report: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return response()->json([
                'error' => [$e->getMessage()]
            ]);
        }
    }

    /**
     * handle export report statistical list and number device
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function statisticalListAndNumberDeviceExport(Request $request)
    {
        try {
            if(empty($request->devices)){
                return response()->json([
                    'error' => ['Loại thiết bị không được để trống']
                ]);
            }
            $data = getDataStatisticalListAndNumberDevice($request);

            if(empty($data)){
                return response()->json([
                    'error' => ['Không tìm thấy dữ liệu phù hợp!']
                ]);
            }
            $chartTitle = '';
            // get data and template excel prefix
            switch ($request->reportType) {
                case 'ti-le-hang-tron':
                    $templatePrefix = 'ti-le-hang-tron-';
                    $result = calculateRatioStatisticalListAndNumberDevice($data, 'zManufacturer.zsym');
                    $sheetName = 'TY_LE_HANG';
                    $titleSheetChart = 'Tỷ lệ các hãng sản xuất trên tổng';
                    $titleSheetBD = 'Biểu đồ phần trăm các hãng theo tổng thiết bị';
                    break;
                case 'ti-le-hang-cot':
                    $result = calculateNumberDeviceForColumnChart($data, 'zrefnr_dvql.zsym', 'zManufacturer.zsym');
                    $templatePrefix = 'ti-le-hang-cot-';
                    $sheetName = 'SO_LUONG_HANG';
                    $titleSheetChart = 'Số lượng các hãng sản xuất theo tổng thiết bị';
                    $titleSheetBD = 'Biểu đồ số lượng các hãng trên tổng thiết bị';
                    $chartTitle = 'Số thiết bị';
                    break;
                case 'ti-le-kieu-tron':
                    $result = calculateRatioStatisticalListAndNumberDevice($data, 'zCI_Device_Kind.zsym');
                    $templatePrefix = 'ti-le-kieu-tron-';
                    $sheetName = 'TY_LE_KIEU';
                    $titleSheetChart = 'Tỷ lệ các kiểu thiết bị trên tổng thiết bị';
                    $titleSheetBD = 'Biểu đồ tỷ lệ các kiểu thiết bị trên tổng thiết bị';
                    break;
                default:
                    $result = calculateNumberDeviceForColumnChart($data, 'zManufacturer.zsym', 'zCI_Device_Kind.zsym');
                    $templatePrefix = 'ti-le-kieu-cot-';
                    $sheetName = 'SO_LUONG_KIEU';
                    $titleSheetChart = 'Số lượng các kiểu thiết bị trên tổng thiết bị';
                    $titleSheetBD = 'Biểu đồ số lượng các kiểu thiết bị trên tổng thiết bị';
                    $chartTitle = 'Số thiết bị';
                    break;
            }
            // count and chunk array if length result more than 30(template excel max 30 column data)
            $count = count($result);
            if($count > 30){
                $result = array_chunk($result, 30, true)[0];
                $count = 30;
            }
            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);
            $template = storage_path("templates_excel/thong_ke_cao_ap/thong-ke-so-luong-va-danh-sach-thiet-bi/{$templatePrefix}{$count}.xlsx");
            $spreadsheet = $reader->load($template);
            $sheetStatistical = $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
            $sheetChart = $spreadsheet->setActiveSheetIndexByName($sheetName);
            $sheetBD = $spreadsheet->setActiveSheetIndexByName('BIEU_DO');
            if($chartTitle != ''){
                $sheetBD->getChartByName('chart1')->setTitle(new \PhpOffice\PhpSpreadsheet\Chart\Title($chartTitle));
            }
            $columns = range('B', 'L');
            $keys = ['stt', 'zrefnr_dvql.zsym', 'zrefnr_td.zsym', 'zrefnr_nl.zsym', 'name', 'serial_number', 'zManufacturer.zsym', 'zCI_Device_Type.zsym', 'zCI_Device_Kind.zsym', 'zYear_of_Manafacture.zsym', 'zcapacity'];
            $indexRow = 5;
            foreach($data as $i => $item){
                $item['stt'] = ++$i;
                foreach($keys as $index => $keyName){
                    $sheetStatistical->getCell($columns[$index].$indexRow)->setValue(htmlspecialchars_decode(@$item[$keyName]));
                }
                $indexRow++;
            }
            switch ($request->reportType) {
                case 'ti-le-hang-tron':
                case 'ti-le-kieu-tron':
                    fillDataChartStatisticalListAndNumberDevice($result, $sheetChart);
                    break;
                default:
                    fillDataSheetColumnChartListAndNumberDevice($result, $sheetChart);
                    break;
            }
            $sheetChart->getCell('B2')->setValue($titleSheetChart);
            $sheetBD->getCell('B2')->setValue($titleSheetBD);

            $columns = range('C', 'G');
            $inputs = [
                'Thiết bị: ' => 'devices',
                'Chủng loại: ' => 'deviceTypes',
                'Khu vực: ' => 'area',
                'Tình trạng thí nghiệm: ' => 'zStage',
                'Đơn vị quản lý: ' => 'dvqls',
                'Hãng sản xuất: ' => 'manufacture_ids',
                'Kiểu thiết bị: ' => 'deviceKind',
                'Công suất: ' => 'zcapacity',
                'Tổ đấu dây: ' => 'ztodauday',
                'Điện áp định mức: ' => 'zdienapdinhmuc',
                'Dòng điện định mức: ' => 'zdongdiendinhmuc',
                'Số chế tạo: ' => 'serial_number',
                'Năm sản xuất(Bắt đầu): ' => 'startYear',
                'Năm sản xuất(Kết thúc): ' => 'endYear',
            ];
            $requestArr = formatRequest($request);
            fillSearchRequestToExcel($sheetStatistical, $requestArr, $columns, $inputs);
            alignmentExcel($sheetStatistical, 'C9:J1000');
            $spreadsheet->setActiveSheetIndexByName('BIEU_DO');
            $url = writeExcel($spreadsheet, 'bao-cao-thong-ke-so-luong-va-danh-sach-thiet-bi-'.time().'.xlsx');
            return response()->json([
                'success' => true,
                'url' => $url,
            ]);
        } catch (\Exception $e) {
            Log::error('[Machines] Statistical list and number device report: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return response()->json([
                'error' => [$e->getMessage()]
            ]);
        }
    }

    /**
     * View index for report experimental result device
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function experimentalResultsDevice()
    {
        $devices = config('constant.device_statistical');

        $zStage = getDataFromService('doSelect', 'zETC_Status', ['id', 'zsym'], '', 100);
        sortData($zStage);
        $dvqls = Cache::remember('unitList', 7200, function () {
            return getDataFromService('doSelect', 'zDVQL', ['id', 'zsym'], 'zsym IS NOT NULL AND active_flag = 1', 200);
        });
        sortData($dvqls);

        $areas = Cache::remember('list_areas', 7200, function () {
            return getDataFromService('doSelect', 'zArea', ['id', 'zsym'], 'zsym IS NOT NULL AND active_flag = 1', 200);
        });
        sortData($areas);
        $zQTs = getDataFromService('doSelect', 'zQT', ['id', 'zsym'], '', 100);
        sortData($zQTs);

        return view('machines::experimentalResultsDevice.index', compact('devices', 'zStage', 'dvqls', 'areas', 'zQTs'));
    }

    /**
     * Handle and export excel file for report experimental result device
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function experimentalResultsDeviceExport(Request $request)
    {
        try {
            if(empty($request->devices)){
                return response()->json([
                    'error' => ['Loại thiết bị không được để trống']
                ]);
            }
            $data = getDataExperimentalResultsDevice($request);
            if(empty($data)){
                return response()->json([
                    'error' => ['Không tìm thấy dữ liệu phù hợp!']
                ]);
            }
            $groupDvql = collect($data)->groupBy(function($item){
                return $item['zrefnr_dvql.zsym'];
            })->toArray();
            $groupManufacturer = collect($data)->groupBy(function($item){
                return $item['zManufacturer.zsym'];
            })->toArray();
            $groupYear = collect($data)->groupBy(function($item){
                return $item['year'];
            })->toArray();

            $countManufacturer = count($groupManufacturer);
            $countYear = count($groupYear);

            if( $countManufacturer > 30 ){
                $countManufacturer = 30;
                $groupManufacturer = array_chunk($groupManufacturer, 30, true)[0];
            }
            if( $countYear > 30 ){
                $countYear = 30;
                $groupYear = array_chunk($groupYear, 30, true)[0];
            }

            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);
            $template = storage_path("templates_excel/thong_ke_cao_ap/bao-cao-thong-ke-ket-qua-thi-nghiem-thiet-bi/thong-ke-ket-qua-thi-nghiem-thiet-bi-{$countYear}-nam-{$countManufacturer}-hang.xlsx");
            $spreadsheet = $reader->load($template);
            $sheetStatistical = $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
            $columns = range('B', 'L');
            $keys = ['stt', 'zlaboratoryDate', 'zrefnr_dvql.zsym', 'zrefnr_td.zsym', 'zContract_Number.sym', 'zrefnr_nl.zsym', 'zManufacturer.zsym', 'name', 'zresultEnd', 'pass', 'fail'];
            $indexRow = 5;
            foreach($data as $i => $item){
                $item['stt'] = ++$i;
                foreach($keys as $index => $keyName){
                    $sheetStatistical->getCell($columns[$index].$indexRow)->setValue(@$item[$keyName]);
                }
                $indexRow++;
            }
            // Fill data to sheet chart
            $this->fillDataSheetChartExperimentalResultsDevice($data, $groupYear, $groupManufacturer, $groupDvql, $spreadsheet);

            $columns = range('C', 'E');
            $inputs = [
                'Thời gian bắt đầu: ' => 'startDate',
                'Thời gian kết thúc: ' => 'endDate',
                'Thiết bị: ' => 'devices',
                'Chủng loại: ' => 'deviceTypes',
                'Khu vực: ' => 'area',
                'Đơn vị quản lý: ' => 'dvqls',
                'Tình trạng thí nghiệm: ' => 'zStage',
                'Quy trình thí nghiệm: ' => 'zqt',
                'Hãng sản xuất: ' => 'manufacture_ids',
            ];
            $requestArr = formatRequest($request);
            fillSearchRequestToExcel($sheetStatistical, $requestArr, $columns, $inputs, 3);
            alignmentExcel($sheetStatistical, 'D8:J1000');
            $sheetStatistical->getColumnDimension('F')->setWidth(50);
            $sheetStatistical->getColumnDimension('J')->setWidth(100);
            $spreadsheet->setActiveSheetIndexByName('BIEU_DO');
            $url = writeExcel($spreadsheet, 'bao-cao-thong-ke-ket-qua-thi-nghiem-thiet-bi-'.time().'.xlsx');
            return response()->json([
                'success' => true,
                'url' => $url,
            ]);
        } catch (\Exception $e) {
            Log::error('[Machines] Experimental results device report: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return response()->json([
                'error' => [$e->getMessage()]
            ]);
        }
    }

    /**
     * count report pass and fail for $data
     * @param array $data
     * @param string $type
     * @return int
     */
    private function countPassFailReportExperimentalResultsDevice(array $data, string $type){
        return count(array_filter($data, function($item) use($type) {
            return strtolower($item[$type]) == 'x';
        }));
    }

    /**
     * Fill data to sheet chart for report experimental result device
     * @param array $data
     * @param array $dataSheet1
     * @param array $dataSheet2
     * @param array $dataSheet3
     * @param $spreadsheet
     */
    private function fillDataSheetChartExperimentalResultsDevice(array $data, array $dataSheet1, array $dataSheet2, array $dataSheet3, $spreadsheet)
    {
        $sheetBD = $spreadsheet->setActiveSheetIndexByName('BIEU_DO');
        $sheetChart1 = $spreadsheet->setActiveSheetIndexByName('DATA_BD1');
        $sheetChart2 = $spreadsheet->setActiveSheetIndexByName('DATA_BD2');
        $sheetChart3 = $spreadsheet->setActiveSheetIndexByName('DATA_BD3');
        $sheetChart4 = $spreadsheet->setActiveSheetIndexByName('DATA_BD4');
        $sheetChart5 = $spreadsheet->setActiveSheetIndexByName('DATA_BD5');

        $dataSheet4 = [];
        // detach and format data
        foreach($dataSheet1 as $year => $items){
            $dataSheet1[$year] = $this->countPassFailReportExperimentalResultsDevice($items, 'pass') .'_'. $this->countPassFailReportExperimentalResultsDevice($items, 'fail');
        }
        foreach($dataSheet2 as $manufactureName => $items){
            $dataSheet2[$manufactureName] = $this->countPassFailReportExperimentalResultsDevice($items, 'pass') .'_'. $this->countPassFailReportExperimentalResultsDevice($items, 'fail');
            $dataSheet4[$manufactureName] = $this->countPassFailReportExperimentalResultsDevice($items, 'pass') .'_'. $this->countPassFailReportExperimentalResultsDevice($items, 'fail');
        }
        foreach($dataSheet3 as $dvql => $items){
            $dataSheet3[$dvql] = $this->countPassFailReportExperimentalResultsDevice($items, 'pass') .'_'. $this->countPassFailReportExperimentalResultsDevice($items, 'fail');
        }
        $dataSheet5 = [
            'Loại thiết bị' => $this->countPassFailReportExperimentalResultsDevice($data, 'pass') .'_'. $this->countPassFailReportExperimentalResultsDevice($data, 'fail')
        ];
        for($i = 1; $i <= 5; $i++){
            $sheetActive = 'sheetChart'.$i;
            $dataSheet = 'dataSheet'.$i;
            $indexRow = 5;
            foreach($$dataSheet as $key => $val){
                $$sheetActive->getCell("C{$indexRow}")->setValue($key);
                $$sheetActive->getCell("D{$indexRow}")->setValue(explode('_', $val)[0]);
                $$sheetActive->getCell("E{$indexRow}")->setValue(explode('_', $val)[1]);
                $indexRow++;
            }
        }
        // check length data and handle hidden worksheet and column wrapper chart
        if(count($dataSheet3) != 1 || count($dataSheet4) != 1){
            hiddenSheetsAndChart(['DATA_BD5'], $spreadsheet, $sheetBD, 73, 60);
        }else{
            hiddenSheetsAndChart(['DATA_BD2'], $spreadsheet, $sheetBD, 31, 18);
        }
        if(count($dataSheet3) == 1 && count($dataSheet4) != 1){
            hiddenSheetsAndChart(['DATA_BD4'], $spreadsheet, $sheetBD, 59, 46);
        }
        if(count($dataSheet3) != 1 && count($dataSheet4) == 1){
            hiddenSheetsAndChart(['DATA_BD3'], $spreadsheet, $sheetBD, 45, 32);
        }
        if(count($dataSheet3) != 1 && count($dataSheet4) != 1){
            hiddenSheetsAndChart(['DATA_BD3', 'DATA_BD4', 'DATA_BD5'], $spreadsheet, $sheetBD, 73, 32);
        }
    }

    /**
     * handle draw chart for report rate of change
     * @param array $data
     * @param $sheetBD
     * @param $obj
     * @return array
     */
    private function drawChartForReportRateOfChange(array $data, $sheetBD, $obj)
    {
        $config = config('highpressuretransformers.report_rate_of_change.'.$obj.'.draw_chart');
        $indexChartName = 5;
        foreach($config as $value){
            // get array value data chart and caculate min max axis
            $attrs = config('highpressuretransformers.report_rate_of_change.'.$obj.'.attrs_share.'.$value['sheet_excel']);
            $result = array_filter(getArrayValueByArrayKey($data[0], $attrs));
            $min = count($result) > 0 ? (float)min($result) : 0;
            $max = count($result) > 0 ? round((float)max($result)) + 0.1 : 10;

            $sheetChart = $value['sheet_excel'];
            // Set the X-Axis Labels
            $xAxisTickValues = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $sheetChart.'!$D$5:$F$5', null, 3),
            ];
            // Set the Labels for each data series we want to plot
            // Set the Data values for each data series we want to plot
            $dataSeriesValues = [];
            $dataSeriesLabels = [];
            for( $i = 6; $i <= 26; $i++ ){
                $dataSeriesValues[] =  new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, $sheetChart.'!$D$'.$i.':$F$'.$i, null, 3);
                $dataSeriesLabels[] = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $sheetChart.'!$C$'.$i);
            }
            // Build the dataseries
            $series = new DataSeries(
                DataSeries::TYPE_LINECHART, // plotType
                DataSeries::GROUPING_STANDARD, // plotGrouping
                range(0, count($dataSeriesValues) - 1), // plotOrder
                $dataSeriesLabels, // plotLabel
                $xAxisTickValues, // plotCategory
                $dataSeriesValues, // plotValues
            );
            // create layout chart with show value in line chart
            $layout = new Layout();
            $layout->setShowVal(true);

            // Set the series in the plot area
            $plotArea1 = new PlotArea($layout, [$series]);

            // Set axis Y min max dynamic
            $yaxis = new Axis();
            $yaxis->setAxisOptionsProperties('low', null, null, null, null, null, $min, $max, null, null);
            
            // set title for chart 
            $title1 = new Title('Biểu đồ phân tích giữa 3 pha tại 1 nấc');
            // Set the chart legend
            $legend1 = new Legend(Legend::POSITION_TOP, null, false);
            // Create the chart
            $chart1 = new Chart(
                'chart'.$indexChartName, // name
                $title1, // title
                $legend1, // legend
                $plotArea1, // plotArea
                false, // plotVisibleOnly
                DataSeries::EMPTY_AS_GAP, // displayBlanksAs
                null, // xAxisLabel
                null, // yAxisLabel
                null, // Axis X
                $yaxis, // Axis Y
            );
            // Set the position where the chart should appear in the worksheet
            $chart1->setTopLeftPosition($value['topLeftPosition'], 0, 0);
            $chart1->setBottomRightPosition($value['bottomRightPosition'], 0, 0);
            // Add the chart to the worksheet
            $data[] = $sheetBD->addChart($chart1);

            $indexChartName++;
        }
        return $data ?? [];
    }
}
