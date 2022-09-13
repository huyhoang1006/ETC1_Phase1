<?php

namespace Modules\Electromechanical\Http\Controllers;

use App\Services\CAWebServices;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class ElectromechanicalSupplierController extends Controller
{
    /**
     * Index View Supplier Report
     *
     * @return void
     */
    public function supplierQuality()
    {
        return view('electromechanical::supplierReport.index');
    }

    /**
     * Get request get data CA return file excel
     *
     * @param Request $request
     * @return void
     */
    public function supplierQualityExport(Request $request)
    {
        try {
            $startYear = $request->start_year;
            $endYear = $request->end_year;
            if($startYear > $endYear)
            {
                return response()->json([
                    'error' => ['Thời gian kết thúc phải lớn hơn hoặc bằng thời gian bắt đầu!']
                ]);
            }
            // All report type
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
            $whereClause .= " AND zlaboratoryDate >= " . strtotime($startYear . "-01-01")
                            . " AND zlaboratoryDate <= " . ( strtotime($endYear ."-12-31") + 86399)
                            ."AND zManufacturer.zsym IS NOT NULL";

            $whereClause .= ' AND class.zetc_type = 1';
            $attributes = [
                'zManufacturer.zsym',
                'zlaboratoryDate',
                'z13userstr',
                'zresultEnd_chk',
                'class.type'
            ];
            $reports = getDataFromService('doSelect', 'nr', $attributes, $whereClause, 100);

            foreach ($reports as $key => $value) {
                $reports[$key]['zlaboratoryDate'] = Carbon::createFromTimestamp($value['zlaboratoryDate'])->year;
            }

            // Group By Year Data
            $groupByYear = collect($reports)->sortBy('zlaboratoryDate')->groupBy('zlaboratoryDate');

            foreach ($groupByYear as $year => $collect) {
                $groupByYear[$year] = $collect->groupBy(function($item){
                    return $item['zManufacturer.zsym'];
                });
                $achieved = $notAchieved = $totalAchieve = $percentAchieved = 0;
                foreach($groupByYear[$year] as $manufacturerName => $manufacturerCollect){

                    $achieved = $manufacturerCollect->filter(function($manufacturer) use ($year, $manufacturerName){
                        return array_key_exists('zresultEnd_chk', $manufacturer);
                    })->count();

                    $notAchieved = $manufacturerCollect->filter(function($manufacturer) use ($year, $manufacturerName){
                        return !array_key_exists('zresultEnd_chk', $manufacturer);
                    })->count();

                    $totalAchieve = $manufacturerCollect->count();
                    if($totalAchieve != 0)
                    {
                        $percentAchieved = round($achieved / $totalAchieve * 100, 2);
                    }

                    $dataAchieve = collect([
                        'achieved' => $achieved,
                        'notAchieved' => $notAchieved,
                        'totalAchieve' => $totalAchieve,
                        'percentAchieved' => $percentAchieved
                    ]);
                    $groupByYear[$year][$manufacturerName] = $manufacturerCollect->merge($dataAchieve);

                    $groupByYear[$year] = $groupByYear[$year]->sortByDesc('percentAchieved');
                }
            }

            // Get All NameManufacturer Unique
            $listNameManufacturer = [];

            foreach($groupByYear as $year => $manufacturerCollect)
            {
                $listNameManufacturer = array_unique (array_merge ($listNameManufacturer, $manufacturerCollect->keys()->all()));
            }

            // Calculate total achieved, not achieved each manufacturer
            foreach($listNameManufacturer as $index => $name)
            {
                $totalAchieved = $totalNotAchieved = $totalAchieve = $percentAchieved = 0;

                foreach($groupByYear as $year => $manufacturerCollect)
                {
                    if( isset($manufacturerCollect[$name]) ){
                        $totalAchieved += $manufacturerCollect[$name]['achieved'];
                        $totalNotAchieved += $manufacturerCollect[$name]['notAchieved'];
                        $totalAchieve += $manufacturerCollect[$name]['totalAchieve'];
                    }
                }
                if($totalAchieve != 0)
                {
                    $percentAchieved = round($totalAchieved / $totalAchieve * 100 , 2);
                }
                $listNameManufacturer[$index] = [
                    'name' => $name,
                    'totalAchieved' => $totalAchieved,
                    'totalNotAchieved' => $totalNotAchieved,
                    'totalAchieve' => $totalAchieve,
                    'percentAchieved' => $percentAchieved
                ];
            }

            // Get the distance between years to include the corresponding excel file
            $yearBetween = $endYear - $startYear + 1;

            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);
            // Read Template Excel
            $countListNameManufacturer = count($listNameManufacturer);
            $template = storage_path("templates_excel/phan_xuong_co_dien/bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-theo-nha-cung-cap/bao-cao-so-sanh-chat-luong-nha-cung-cap-{$countListNameManufacturer}.xlsx");
            $spreadsheet = $reader->load($template);

            $spreadsheet->setActiveSheetIndexByName('KET_QUA_THONG_KE')
            ->getCell("A6")
            ->setValue("Dữ liệu Biểu đồ phần trăm các nhà sản xuất mỗi năm");

            $spreadsheet->setActiveSheetIndexByName('DATA_BĐ1')
            ->getCell("A4")
            ->setValue("Dữ liệu Biểu đồ tổng phần trăm các nhà sản xuất qua các năm");

            $spreadsheet->setActiveSheetIndexByName('BIEU_DO')
            ->getCell("B5")
            ->setValue("Dữ liệu Biểu đồ tổng phần trăm các nhà sản xuất qua các năm");

            // Set title for chart
            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $chartNames = $worksheet->getChartNames();
                if(in_array('chart1', $chartNames))
                {
                    $chart = $worksheet->getChartByName('chart1');

                    $titleChar = new \PhpOffice\PhpSpreadsheet\Chart\Title("Dữ liệu Biểu đồ tổng phần trăm các nhà sản xuất qua các năm");

                    $chart->setTitle($titleChar);
                }
            }

            // Write data to table chart
            $this->drawChartToSheet($listNameManufacturer, $spreadsheet);

            // Write data to table statistical
            $this->writeDataToSheet($groupByYear, $spreadsheet);

            $columns = ['C', 'D'];
            $inputs = [
                'Năm bắt đầu: ' => 'start_year',
                'Năm kết thúc: ' => 'end_year',
            ];
            fillSearchRequestToExcel($spreadsheet->setActiveSheetIndexByName('KET_QUA_THONG_KE'), $request, $columns, $inputs, 1);
            alignmentExcel($spreadsheet->setActiveSheetIndexByName('KET_QUA_THONG_KE'), 'C10:C1000');
            $spreadsheet->setActiveSheetIndexByName('KET_QUA_THONG_KE')->getStyle('A7')->getAlignment()->setWrapText(false);
            $spreadsheet->setActiveSheetIndexByName("BIEU_DO");

            $title = 'bao-cao-so-sanh-chat-luong-nha-cung-cap'.'-'.time().'.xlsx';
            $url = writeExcel($spreadsheet, $title);

            return response()->json([
                'success' => true,
                'url' => $url,
            ]);
        } catch (\Exception $e) {
            Log::error('[Electromechanical] Supplier quality comparison report fail: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));

            return response()->json([
                'error' => [$e->getMessage()]
            ]);
        }

    }

    // Draw Chart supplierQualityExport
    private function drawChartToSheet($data, $spreadsheet)
    {
        $stt = 1;
        $startIndexSheet = 7;

        foreach($data as $item)
        {
            $spreadsheet->setActiveSheetIndexByName('DATA_BĐ1')
                ->getCell("A{$startIndexSheet}")
                ->setValue($stt);

            $spreadsheet->setActiveSheetIndexByName('DATA_BĐ1')
                ->getCell("B{$startIndexSheet}")
                ->setValue($item['name']);

            $spreadsheet->setActiveSheetIndexByName('DATA_BĐ1')
                ->getCell("C{$startIndexSheet}")
                ->setValue($item['percentAchieved']);
            $stt++;
            $startIndexSheet++;
        }
    }

    // Write Chart supplierQualityExport
    private function writeDataToSheet($data, $spreadsheet)
    {
        $style = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ];

        $spreadsheet->getDefaultStyle()->applyFromArray($style);
        // Flowing template excel
        $startIndexSheet = $endIndexSheet = 9;
        $stt = 1;

        foreach($data as $year => $collect)
        {
            $count = $collect->count();
            $endIndexSheet = $startIndexSheet + $count - 1;

            $spreadsheet->setActiveSheetIndexByName('KET_QUA_THONG_KE')
            ->mergeCells("B{$startIndexSheet}:B{$endIndexSheet}")
            ->getCell("B{$startIndexSheet}")
            ->setValue($year);

            $indexColumn = $startIndexSheet;


            foreach($collect as $manufacturer => $item)
            {
                $spreadsheet->setActiveSheetIndexByName('KET_QUA_THONG_KE')
                    ->getCell("A{$indexColumn}")
                    ->setValue($stt);

                $spreadsheet->setActiveSheetIndexByName('KET_QUA_THONG_KE')
                    ->getCell("C{$indexColumn}")
                    ->setValue($manufacturer);

                $spreadsheet->setActiveSheetIndexByName('KET_QUA_THONG_KE')
                    ->getCell("D{$indexColumn}")
                    ->setValue($item['achieved']);

                $spreadsheet->setActiveSheetIndexByName('KET_QUA_THONG_KE')
                    ->getCell("E{$indexColumn}")
                    ->setValue($item['notAchieved']);

                $spreadsheet->setActiveSheetIndexByName('KET_QUA_THONG_KE')
                    ->getCell("F{$indexColumn}")
                    ->setValue($item['totalAchieve']);

                $spreadsheet->setActiveSheetIndexByName('KET_QUA_THONG_KE')
                    ->getCell("G{$indexColumn}")
                    ->setValue($item['percentAchieved']);

                $indexColumn++;
                $stt++;
            }

            $startIndexSheet += $count;
        }

    }

    public function protectionRelayReport()
    {
        $devices = getDataFromService('doSelect', 'grc', [
            'id',
            'type',
        ], "zetc_type = 0 AND type LIKE '%Rơ le bảo vệ%' AND delete_flag != 1", 100);
        sortData($devices, 'asc', 'type');
        return view('electromechanical::protectionRelay.index', compact('devices'));
    }

    public function protectionRelayExport(Request $request)
    {
        try {
            $year = $request->year;

            $startYear = $endYear = $year;

            if ($endYear < now()->year) {
                $endYear = strtotime($endYear . "-12-31") + 86399;
            } else {
                $endYear = strtotime(now());
            }

            $startYear = strtotime($startYear . "-01-01");

            $arrayTypes = [
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

            foreach ($arrayTypes as $key => $val) {
                if ($key == 0) {
                    $whereClauseBase = "class.type IN (";
                }
                $whereClauseBase .= "'" . $val . "'";
                if ($key < count($arrayTypes) - 1) {
                    $whereClauseBase .= ",";
                } else {
                    $whereClauseBase .= ")";
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

            $whereClauseBase .= " AND zlaboratoryDate >= " . $startYear
            . " AND zlaboratoryDate <= " . $endYear
            . " AND zManufacturer.zsym IS NOT NULL "
            . " AND class.zetc_type = 1";

            $attributes = [
                'zCI_Device_Type.zsym',
                'class.type',
                'zManufacturer.zsym',
                'zlaboratoryDate'
            ];
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

            // Format zlaboratoryDate to year
            foreach ($reports as $key => $value) {
                $reports[$key]['zlaboratoryDate'] = Carbon::createFromTimestamp($value['zlaboratoryDate'])->year;
            }


            // count all data pay about for percentity Manufacturer
            $count = count($reports);

            // Group by Manufacturer
            $groupByManufacturer = collect($reports)->groupBy(function($item){
                return $item['zManufacturer.zsym'];
            });

            // Count total item each one Manufacturer
            foreach($groupByManufacturer as $manufacturerName => $manufacturerCollect)
            {
                $data = collect([
                    'totalCollectManufacturer' => $manufacturerCollect->count()
                ]);
                $groupByManufacturer[$manufacturerName] = $manufacturerCollect->merge($data);
            }

            // Group By Year Data
            $groupByYear = collect($reports)->groupBy('zlaboratoryDate');


            // Group by Manufacturer and count by Manufacturer
            foreach ($groupByYear as $year => $collect) {
                $groupByYear[$year] = $collect->groupBy(function ($item) {
                    return $item['zManufacturer.zsym'];
                });
                $totalAchieve = 0;
                foreach ($groupByYear[$year] as $manufacturerName => $manufacturerCollect) {
                    $totalAchieve = $manufacturerCollect->count();

                    $dataAchieve = collect([
                        'totalAchieve' => $totalAchieve
                    ]);
                    $groupByYear[$year][$manufacturerName] = $manufacturerCollect->merge($dataAchieve);
                }
            }

            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);


            // count
            $countListNameManufacturer = $groupByManufacturer->count();
            // Read Template Excel
            $template = storage_path("templates_excel/phan_xuong_co_dien/bao-cao-thong-ke-he-thong-bao-ve-cac-ngan-lo-trung-ap/bao-cao-so-luong-role-bao-ve-{$countListNameManufacturer}.xlsx");
            $spreadsheet = $reader->load($template);

            // Config style for sheet
            $style = [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ];

            // Set Style for Sheet
            $spreadsheet->getDefaultStyle()->applyFromArray($style);

            $sheetThongKe = $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
            $sheetDataBD = $spreadsheet->setActiveSheetIndexByName('DATA_BD');

            // Write data to sheet 'THONG_KE_DU_LIEU' Protection Relay
            $this->writeDataSheetProtectionRelay($count, $groupByYear, $sheetThongKe);
            // Write data to sheet 'DATA_BD' Protection Relay
            $this->writeChartDataProtectionRelay($count, $groupByManufacturer, $sheetDataBD);


            // Set title Sheet
            $sheetThongKe->getCell('C3')
            ->setValue("Báo cáo số lượng Rơ le bảo vệ quá dòng theo các hãng lắp trên lưới điện trung áp trong tổng công ty");

            $sheetDataBD->getCell('C3')
            ->setValue("Báo cáo số lượng Rơ le bảo vệ quá dòng theo các hãng lắp trên lưới điện trung áp trong tổng công ty");

            $spreadsheet->setActiveSheetIndexByName("BIEU_DO")->getCell('C3')
            ->setValue("Báo cáo số lượng Rơ le bảo vệ quá dòng theo các hãng lắp trên lưới điện trung áp trong tổng công ty");
            
            $columns = ['D', 'E'];
            $inputs = [
                'Loại thiết bị: ' => 'devices',
                'Năm báo cáo: ' => 'year',
            ];
            fillSearchRequestToExcel($sheetThongKe, formatRequest($request), $columns, $inputs, 1);
            $sheetThongKe->getStyle('E13:E1000')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $sheetThongKe->getStyle('C10')->getAlignment()->setWrapText(false);
            // Set active sheet BIEU_DO when view file
            $sheetBD = $spreadsheet->setActiveSheetIndexByName("BIEU_DO");
            $sheetBD->getStyle('A1:Z1000')->applyFromArray([
                'font' => [
                    'name' => 'Times New Roman',
                ]
            ]);
            $title = 'bao-cao-so-luong-ro-le-bao-ve-'.time().'.xlsx';
            $url = writeExcel($spreadsheet, $title);
            return response()->json([
                'success' => true,
                'url' => $url,
            ]);

        } catch (\Exception $e) {
            Log::error('[Electromechanical] Report failed electromechanical workshop protection relay: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));

            return response()->json([
                'error' => [$e->getMessage()]
            ]);
        }
    }

    private function writeDataSheetProtectionRelay($count, $data, $spreadsheet)
    {
        // Set width column E
        $spreadsheet->getColumnDimension('E')->setWidth(50);
        // if length data is 0
        if($count == 0)
        {
            return;
        }

        // Follow strat column write data in excel file
        $startIndexSheet = $endIndexSheet = 12;

        foreach($data as $year => $collect)
        {
            $count = $collect->count();
            $endIndexSheet = $startIndexSheet + $count - 1;

            $spreadsheet->mergeCells("D{$startIndexSheet}:D{$endIndexSheet}")
            ->getCell("D{$startIndexSheet}")
            ->setValue($year);

            $indexColumn = $startIndexSheet;


            foreach($collect as $manufacturer => $item)
            {
                $spreadsheet->getCell("E{$indexColumn}")
                    ->setValue("Rơle bảo vệ quá dòng hãng sản xuất: {$manufacturer}");

                $spreadsheet->getCell("F{$indexColumn}")
                    ->setValue($item['totalAchieve']);

                $indexColumn++;
            }

            $startIndexSheet += $count;
        }
    }

    private function writeChartDataProtectionRelay($count, $data, $spreadsheet)
    {
        // if length data is 0
        if($count == 0)
        {
            return;
        }
        $startIndexSheet = 10;
        foreach($data as $manufacturerName => $manufacturerCollect)
        {
            // Follow strat column write data in excel file
            $spreadsheet->getCell("C{$startIndexSheet}")
                ->setValue($manufacturerName);
            $spreadsheet->getCell("D{$startIndexSheet}")
                ->setValue(round($manufacturerCollect['totalCollectManufacturer'] / $count * 100, 2));
            $startIndexSheet++;
        }
    }

    public function indexShare($title)
    {

        $meta_title = $title_head = $id_report  = "";
        switch ($title) {
            case 'may-cat-bao-cao-so-luong-may-cat-trung-ap-da-thuc-hien':
                $meta_title = 'PXCĐ - Báo cáo thí nghiệm nhất thứ - Máy cắt - Báo cáo số lượng máy cắt trung áp đã thí nghiệm';
                $title_head = 'Báo cáo thí nghiệm nhất thứ - Máy cắt - Báo cáo số lượng máy cắt trung áp đã thí nghiệm';
                $id_report = '28';
                break;
            case 'may-bien-dong-dien-bao-cao-so-luong-da-thi-nghiem':
                $meta_title = 'PXCĐ - Báo cáo thí nghiệm nhất thứ - Máy biến dòng điện - Báo cáo số lượng máy biến dòng điện đã thí nghiệm';
                $title_head = 'Báo cáo thí nghiệm nhất thứ - Máy biến dòng điện - Báo cáo số lượng máy biến dòng điện đã thí nghiệm';
                $id_report = '29';
                break;
            case 'cap-luc-bao-cao-so-luong-da-thi-nghiem':
                $meta_title = 'PXCĐ - Báo cáo thí nghiệm nhất thứ - Cáp lực - Báo cáo số lượng cáp lực đã thí nghiệm';
                $title_head = 'Báo cáo thí nghiệm nhất thứ - Cáp lực - Báo cáo số lượng cáp lực đã thí nghiệm';
                $id_report = '30';
                break;
            case 'may-bien-ap-phan-phoi-bao-cao-so-luong-da-thi-nghiem':
                $meta_title = 'PXCĐ - Báo cáo thí nghiệm nhất thứ - Máy biến áp phân phối - Báo cáo số lượng máy biến áp phân phối đã thí nghiệm';
                $title_head = 'Báo cáo thí nghiệm nhất thứ - Máy biến áp phân phối - Báo cáo số lượng máy biến áp phân phối đã thí nghiệm';
                $id_report = '31';
                break;
            case 'may-bien-dien-ap-bao-cao-so-luong-da-thi-nghiem':
                $meta_title = 'PXCĐ - Báo cáo thí nghiệm nhất thứ - Máy biến điện áp - Báo cáo số lượng máy biến điện áp đã thí nghiệm';
                $title_head = 'Báo cáo thí nghiệm nhất thứ - Máy biến điện áp - Báo cáo số lượng máy biến điện áp đã thí nghiệm';
                $id_report = '32';
                break;
            default:
                break;
        }
        return view('electromechanical::indexShare.index', compact('meta_title', 'id_report', 'title_head'));
    }
    /**
     * export 28-29-30-31-32 Báo cáo số lượng đã thí nghiệm
     * @return Renderable
     */
    public function cuttingMachinesExperimentedExport(Request $request)
    {
        if($request['id_report'] == 28){
            $title_log = 'Report the number of cutters tested';
            $title_report = 'Báo cáo số lượng máy cắt đã thí nghiệm từ năm';
            $title_export = 'bao-cao-so-luong-may-cat-trung-ap-da-thi-nghiem-';
            $arrayTypes = [
                '10.1 BBTN Máy cắt 1 buồng cắt 1 bộ truyền động',
                '10.2. BBTN Máy cắt 1 buồng cắt 3 bộ truyền động',
                '10.3. BBTN Máy cắt 2 buồng cắt 3 bộ truyền động'
            ];
            $title = 'máy cắt';
        }elseif($request['id_report'] == 29){
            $title_log = 'Report current transformer experiment';
            $title_report = 'Báo cáo số lượng máy biến dòng điện đã thí nghiệm từ năm';
            $title_export = 'bao-cao-so-luong-may-bien-dong-dien-da-thi-nghiem-';
            $arrayTypes = [
                '9.1. BBTN Máy biến dòng điện 3 cuộn 3 tỷ số',
                '9.2. BBTN Máy biến dòng điện 5 cuộn 5 tỷ số',
            ];
            $title = 'máy biến dòng điện';
        }elseif($request['id_report'] == 30){
            $title_log = 'Cable test report';
            $title_report = 'Báo cáo số lượng cáp lực đã thí nghiệm từ năm';
            $title_export = 'bao-cao-so-luong-cap-luc-da-thi-nghiem-';
            $arrayTypes = [
                '2.1. BBTN Cáp lực 1 lõi',
                '2.2. BBTN Cáp lực 3 lõi',
            ];
            $title = 'cáp lực';
        }elseif($request['id_report'] == 31){
            $title_log = 'Distribution transformer export report';
            $title_report = 'Báo cáo số lượng máy biến áp phân phối đã thí nghiệm từ năm';
            $title_export = 'bao-cao-so-luong-may-bien-ap-phan-phoi-da-thi-nghiem-';
            $arrayTypes = [
                '13.8. BBTN Máy biến áp phân phối 1 pha',
                '13.9. BBTN Máy biến áp phân phối 3 pha',
            ];
            $title = 'máy biến áp phân phối';
        }elseif($request['id_report'] == 32){
            $title_log = 'Voltage transformers test report';
            $title_report = 'Báo cáo số lượng máy biến điện áp đã thí nghiệm từ năm';
            $title_export = 'bao-cao-so-luong-may-bien-dien-ap-da-thi-nghiem-';
            $arrayTypes = [
                '8.1. BBTN Máy biến điện áp 4 cuộn dây',
            ];
            $title = 'máy biến điện áp';
        }
        try {
            $startYear = ($request->year) - 1;
            $endYear = $request->year;
            // total month flow $startYear data
            $arrayMonth = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
            $prevYear = strtotime($startYear.'-1-1'.'T00:00');
            $curenctYear = strtotime($endYear.'-12-31'.'T23:59');

            foreach ($arrayTypes as $key => $val) {
                if ($key == 0) {
                    $whereClauseBase = "class.type IN (";
                }
                $whereClauseBase .= "'" . $val . "'";
                if ($key < count($arrayTypes) - 1) {
                    $whereClauseBase .= ",";
                } else {
                    $whereClauseBase .= ")";
                }
            }
            $whereClauseBase .= " AND zlaboratoryDate >= " . $prevYear
                . " AND zlaboratoryDate <= " . $curenctYear
                . " AND zCI_Device.name IS NOT NULL"
                . " AND class.zetc_type = 1";
            $attributes = [
                'id',
                'class.type',
                'zlaboratoryDate',
                'zresultEnd_chk',
            ];
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
                return response()->json([
                    'error' => ['Không có dữ liệu!']
                ]);
            }
            foreach ($reports as $key => $value)
            {
                // Set the default value for each item to be counted is 0
                $reports[$key] = array_merge($value, $this->addDefaultValue());
                if( !empty($value['zresultEnd_chk']) ){
                   $reports[$key]['pass'] = 1;
                   $reports[$key]['fail'] = 0;
                }else{
                   $reports[$key]['pass'] = 0;
                   $reports[$key]['fail'] = 1;
                }
                $date = Carbon::createFromTimestamp($value['zlaboratoryDate']);
                $reports[$key]['zlaboratoryDate'] = $date->year;
                $reports[$key]['monthGroup'] = $date->month;
            }
            // Format zlaboratoryDate to year and add month to each array
            foreach ($reports as $key => $value) {
                $reports[$key]['zlaboratoryDate'] = $value['zlaboratoryDate'];
            }
            // Group By Year Data
            $groupByYear = collect($reports)->groupBy('zlaboratoryDate');
            // get data sheet THONG_KE_DU_LIEU
            $data_sync = [];
            foreach ($groupByYear as $key => $value){
                foreach ($value as $val){
                    if(empty($data_sync[$key][$val['monthGroup']]['pass'])){
                        $data_sync[$key][$val['monthGroup']]['pass'] = 0;
                    }
                    if(empty($data_sync[$key][$val['monthGroup']]['fail'])){
                        $data_sync[$key][$val['monthGroup']]['fail'] = 0;
                    }
                    if(empty($data_sync[$key][$val['monthGroup']]['total'])){
                        $data_sync[$key][$val['monthGroup']]['total'] = 0;
                    }
                    if(!empty($val['pass'])){
                        $data_sync[$key][$val['monthGroup']]['pass'] += 1;
                    }
                    if(!empty($val['fail'])){
                        $data_sync[$key][$val['monthGroup']]['fail'] += 1;
                    }
                    $data_sync[$key][$val['monthGroup']]['total'] += 1;
                }
            }
            if(empty($data_sync[$startYear])){
                $data_sync[$startYear] = [];
            }
            if(empty($data_sync[$endYear])){
                $data_sync[$endYear] = [];
            }
            // oder by year
            ksort($data_sync);
            foreach ($data_sync as $key => $value){
                for($i = 1; $i <= 12; $i++) {
                    if(empty($value[$i])){
                        $data_sync[$key][$i]['pass'] = 0;
                        $data_sync[$key][$i]['fail'] = 0;
                    }
                    if(empty($value[$i]['total'])){
                        $data_sync[$key][$i]['total'] = 0;
                    }
                }
                // order by month
                ksort($data_sync[$key]);
            }
            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);
            // Read Template Excel
            $template = storage_path("templates_excel/phan_xuong_co_dien/bao-cao-thi-nghiem-nhat-thu/may-cat/bao-cao-so-luong-may-cat-trung-ap-da-thi-nghiem.xlsx");
            $spreadsheet = $reader->load($template);
            // Config style for sheet
            $style = [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ];
            // Set Style for Sheet
            $spreadsheet->getDefaultStyle()->applyFromArray($style);
            $sheetThongKe = $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
            $sheetDataBD = $spreadsheet->setActiveSheetIndexByName('DATA_BD');
            $sheetBD = $spreadsheet->setActiveSheetIndexByName('BIEU_DO');
            // get title
            $sheetThongKe->getCell("B4")->setValue('Báo cáo số lượng '.$title.' đã thí nghiệm từ năm '.$startYear.' đến '.$endYear);
            $sheetDataBD->getCell("B2")->setValue('Báo cáo số lượng '.$title.' đã thí nghiệm từ năm '.$startYear.' đến '.$endYear);
            $sheetBD->getCell("D3")->setValue('Biểu đồ số lượng '.$title.' đã thí nghiệm từ năm '.$startYear.' đến '.$endYear);

            // Write data to sheet 'THONG_KE_DU_LIEU' Cutting Machines Experimented
            $this->writeDataSheetCuttingMachinesExperimented($data_sync, $sheetThongKe);
            // Write data to sheet 'DATA_BD' Cutting Machines Experimented
            $this->writeChartDataCuttingMachinesExperimented($data_sync, $sheetDataBD);
            // Set active sheet BIEU_DO when view file

            $columns = ['E'];
            $inputs = [
                'Năm cần thống kê: ' => 'year'
            ];
            fillSearchRequestToExcel($sheetThongKe, $request, $columns, $inputs, 1);
            $spreadsheet->setActiveSheetIndexByName("BIEU_DO");
            $title = $title_export.time().'.xlsx';
            $url = writeExcel($spreadsheet, $title);
            return response()->json([
                'success' => true,
                'url' => $url,
            ]);
        } catch (\Exception $e) {
            Log::error('[Electromechanical] '.$title_log.': Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));

            return response()->json([
                'error' => [$e->getMessage()]
            ]);
        }
    }

    private function writeDataSheetCuttingMachinesExperimented($data, $spreadsheet)
    {
        $year_number = 0;
        foreach($data as $year => $value)
        {
            $year_number++;
            $table_title = $table_value = [];
            if($year_number == 1){
                $table_title = ['D8','H8'];
                $table_value = ['D','E'];
            }elseif($year_number == 2){
                $table_title = ['F8','I8'];
                $table_value = ['F','G'];
            }
            // set value title
            foreach ($table_title as $val){
                $spreadsheet->getCell($val)->setValue('Năm '.$year);
            }
            $startIndexSheet = 10;
            foreach($value as $item){
                $spreadsheet->getCell("$table_value[0]{$startIndexSheet}")->setValue($item['pass']??'0');
                $spreadsheet->getCell("$table_value[1]{$startIndexSheet}")->setValue($item['fail']??'0');
                $startIndexSheet++;
            }
        }
    }

    private function writeChartDataCuttingMachinesExperimented($data, $spreadsheet)
    {
        $year_number = 0;
        foreach($data as $year => $value)
        {
            $year_number++;
            $table_title = $table_value = [];
            if($year_number == 1){
                $table_title = 'D6';
                $colum = 'D';
            }elseif($year_number == 2){
                $table_title = 'E6';
                $colum = 'E';
            }
            // set value title
            $spreadsheet->getCell($table_title)->setValue('Năm '.$year);
            $startIndexSheet = 7;
            foreach($value as $item){
                $spreadsheet->getCell("$colum{$startIndexSheet}")->setValue($item['total']??'0');
                $startIndexSheet++;
            }
        }
    }

    /**
     * index report 28-29-30-31-32 Báo cáo số lượng theo các hãng sản xuất
     * @return Renderable
     */
    public function indexShareReportTwo($title)
    {
        $meta_title = $title_head = $id_report  = "";
        switch ($title) {
            case 'bao-cao-so-luong-may-cat-trung-ap-theo-cac-hang-san-xuat':
                $meta_title = 'PXCĐ - Báo cáo thí nghiệm nhất thứ - Máy cắt - Báo cáo số lượng máy cắt trung áp theo các hãng sản xuất';
                $title_head = 'Báo cáo thí nghiệm nhất thứ - Máy cắt - Báo cáo số lượng máy cắt trung áp theo các hãng sản xuất';
                $id_report = '28';
                break;
            case 'bao-cao-so-luong-may-bien-dong-dien-theo-hang-san-xuat':
                $meta_title = 'PXCĐ - Báo cáo thí nghiệm nhất thứ - Máy biến dòng điện - Báo cáo số lượng máy biến dòng điện theo các hãng sản xuất';
                $title_head = 'Báo cáo thí nghiệm nhất thứ - Máy biến dòng điện - Báo cáo số lượng máy biến dòng điện theo các hãng sản xuất';
                $id_report = '29';
                break;
            case 'bao-cao-so-luong-cap-luc-theo-hang-san-xuat':
                $meta_title = 'PXCĐ - Báo cáo thí nghiệm nhất thứ - Cáp lực - Báo cáo số lượng cáp lực theo các hãng sản xuất';
                $title_head = 'Báo cáo thí nghiệm nhất thứ - Cáp lực - Báo cáo số lượng cáp lực theo các hãng sản xuất';
                $id_report = '30';
                break;
            case 'bao-cao-so-luong-may-bien-ap-phan-phoi-theo-hang-san-xuat':
                $meta_title = 'PXCĐ - Báo cáo thí nghiệm nhất thứ - Máy biến áp phân phối - Báo cáo số lượng máy biến áp phân phối theo các hãng sản xuất';
                $title_head = 'Báo cáo thí nghiệm nhất thứ - Máy biến áp phân phối - Báo cáo số lượng máy biến áp phân phối theo các hãng sản xuất';
                $id_report = '31';
                break;
            case 'bao-cao-so-luong-may-bien-dien-ap-theo-hang-san-xuat':
                $meta_title = 'PXCĐ - Báo cáo thí nghiệm nhất thứ - Máy biến điện áp - Báo cáo số lượng máy biến điện áp theo các hãng sản xuất';
                $title_head = 'Báo cáo thí nghiệm nhất thứ - Máy biến điện áp - Báo cáo số lượng máy biến điện áp theo các hãng sản xuất';
                $id_report = '32';
                break;
            default:
                break;
        }
        return view('electromechanical::indexShareReportTwo.index', compact('meta_title', 'id_report', 'title_head'));
    }
    /**
     * export 28-29-30-31-32 Báo cáo số lượng theo các hãng sản xuất
     * @return Renderable
     */
    public function cuttersByManufacturerExport(Request $request)
    {
        if($request['id_report'] == 28){
            $title_log = 'Report the number of medium voltage circuit breakers by manufacturers';
            $title_export = 'bao-cao-so-luong-may-cat-trung-ap-theo-cac-hang-san-xuat-';
            $arrayTypes = [
                '10.1 BBTN Máy cắt 1 buồng cắt 1 bộ truyền động',
                '10.2. BBTN Máy cắt 1 buồng cắt 3 bộ truyền động',
                '10.3. BBTN Máy cắt 2 buồng cắt 3 bộ truyền động'
            ];
            $title = 'máy cắt';
        }elseif($request['id_report'] == 29){
            $title_log = 'Report the number of current transformers by manufacturer';
            $title_export = 'bao-cao-so-luong-may-bien-dong-dien-theo-cac-hang-san-xuat-';
            $arrayTypes = [
                '9.1. BBTN Máy biến dòng điện 3 cuộn 3 tỷ số',
                '9.2. BBTN Máy biến dòng điện 5 cuộn 5 tỷ số',
            ];
            $title = 'máy biến dòng điện';
        }elseif($request['id_report'] == 30){
            $title_log = 'Power Cable reports by manufacturer';
            $title_export = 'bao-cao-so-luong-cap-luc-theo-cac-hang-san-xuat-';
            $arrayTypes = [
                '2.1. BBTN Cáp lực 1 lõi',
                '2.2. BBTN Cáp lực 3 lõi',
            ];
            $title = 'cáp lực';
        }elseif($request['id_report'] == 31){
            $title_log = 'Report number of distribution transformers by manufacturer';
            $title_export = 'bao-cao-so-luong-may-bien-ap-phan-phoi-theo-cac-hang-san-xuat-';
            $arrayTypes = [
                '13.8. BBTN Máy biến áp phân phối 1 pha',
                '13.9. BBTN Máy biến áp phân phối 3 pha',
            ];
            $title = 'máy biến áp phân phối';
        }elseif($request['id_report'] == 32){
            $title_log = 'Report voltage transformer quantity by manufacturer';
            $title_export = 'bao-cao-so-luong-may-bien-dien-ap-theo-cac-hang-san-xuat-';
            $arrayTypes = [
                '8.1. BBTN Máy biến điện áp 4 cuộn dây',
            ];
            $title = 'máy biến điện áp';
        }
        try {
            $startYear = $request->start_year;
            $endYear = $request->end_year;
            if($startYear > $endYear)
            {
                return response()->json([
                    'error' => ['Thời gian kết thúc phải lớn hơn hoặc bằng thời gian bắt đầu!']
                ]);
            }

            if ($endYear < now()->year) {
                $endYear = strtotime($endYear . "-12-31") + 86399;
            } else {
                $endYear = strtotime(now());
            }

            $startYear = strtotime($startYear . "-01-01");

            foreach ($arrayTypes as $key => $val) {
                if ($key == 0) {
                    $whereClauseBase = "class.type IN (";
                }
                $whereClauseBase .= "'" . $val . "'";
                if ($key < count($arrayTypes) - 1) {
                    $whereClauseBase .= ",";
                } else {
                    $whereClauseBase .= ")";
                }
            }
            $whereClauseBase .= " AND zlaboratoryDate >= " . $startYear
                . " AND zlaboratoryDate <= " . $endYear
                . " AND zManufacturer.zsym IS NOT NULL "
                . " AND class.zetc_type = 1";

            $attributes = [
                'id',
                'class.type',
                'zlaboratoryDate',
                'zManufacturer.zsym',
            ];
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

            if(empty($reports)){
                return response()->json([
                    'error' => ['Không có kết quả nào thỏa mãn!']
                ]);
            }
            // convert zlaboratoryDate timespace to year
            foreach ($reports as $key => $value) {
                $reports[$key]['zlaboratoryDate'] = Carbon::createFromTimestamp($value['zlaboratoryDate'])->year;
            }
            // Group By Year Data
            $groupByYear = collect($reports)->groupBy('zlaboratoryDate');
            // group by Manufacturer
            foreach($groupByYear as $year => $collect)
            {
                $groupByYear[$year] = $groupByYear[$year]->merge(collect([
                    'totalInYear' => $collect->count()
                ]));
                $groupByYear[$year] = $collect->groupBy(function($item){
                    return $item['zManufacturer.zsym'];
                });
                foreach($groupByYear[$year] as $manufacturerName => $manufacturerCollect){

                    $dataAchieve = collect([
                        'totalAchieve' => $manufacturerCollect->count(),
                    ]);
                    $groupByYear[$year][$manufacturerName] = $manufacturerCollect->merge($dataAchieve);
                }
            }
            // Get All NameManufacturer Unique
            $listNameManufacturer = [];
            foreach($groupByYear as $year => $manufacturerCollect)
            {
                $listNameManufacturer = array_unique (array_merge ($listNameManufacturer, $manufacturerCollect->keys()->all()));
            }
            // Calculate total achieved each manufacturer
            foreach($listNameManufacturer as $index => $name)
            {
                $totalAchieve = 0;
                foreach($groupByYear as $year => $manufacturerCollect)
                {
                    if( isset($manufacturerCollect[$name]) ){
                        $totalAchieve += $manufacturerCollect[$name]['totalAchieve'];
                    }
                }
                $listNameManufacturer[$index] = [
                    'name' => $name,
                    'totalAchieve' => $totalAchieve,
                ];
            }
            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);
            // count all Manufacturer to import the corresponding excel file
            $totalManufacturer = count($listNameManufacturer);
            // Read Template Excel
            $template = storage_path("templates_excel/phan_xuong_co_dien/bao-cao-thi-nghiem-nhat-thu/may-cat/bao-cao-so-luong-theo-cac-hang-san-xuat-{$totalManufacturer}.xlsx");
            $spreadsheet = $reader->load($template);
            // Config style for sheet
            $style = [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ];
            // Set Style for Sheet
            $spreadsheet->getDefaultStyle()->applyFromArray($style);
            $sheetThongKe = $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
            $sheetDataBD = $spreadsheet->setActiveSheetIndexByName('DATA_BD');
            $sheetBD = $spreadsheet->setActiveSheetIndexByName('BIEU_DO');
            // get title
            $sheetThongKe->getCell("C3")->setValue('Báo cáo số lượng '.$title.' theo các hãng sản xuất từ năm '.$request->start_year.' đến '.$request->end_year);
            $sheetDataBD->getCell("C3")->setValue('Báo cáo số lượng '.$title.' theo các hãng sản xuất từ năm '.$request->start_year.' đến '.$request->end_year);
            $sheetBD->getCell("C3")->setValue('Biểu đồ '.$title.' theo các hãng sản xuất từ năm '.$request->start_year.' đến '.$request->end_year);

            // Write data to sheet 'THONG_KE_DU_LIEU' Cutters By Manufacturer
            $this->writeDataSheetCuttersByManufacturer($groupByYear, $listNameManufacturer, $sheetThongKe);
            // Write data to sheet 'DATA_BD' Cutters By Manufacturer
            $this->writeChartDataCuttersByManufacturer($listNameManufacturer, $sheetDataBD);

            $columns = ['D', 'E'];
            $inputs = [
                'Năm bắt đầu: ' => 'start_year',
                'Năm kết thúc: ' => 'end_year',
            ];
            fillSearchRequestToExcel($sheetThongKe, $request, $columns, $inputs, 1);
            // Set active sheet BIEU_DO when view file
            $spreadsheet->setActiveSheetIndexByName("BIEU_DO");
            $title = $title_export.time().'.xlsx';
            $url = writeExcel($spreadsheet, $title);
            return response()->json([
                'success' => true,
                'url' => $url,
            ]);
        } catch (\Exception $e) {
            Log::error('[Electromechanical] '.$title_log.': Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return response()->json([
                'error' => [$e->getMessage()]
            ]);
        }
    }

    private function writeDataSheetCuttersByManufacturer($data, $listManufacturer, $spreadsheet)
    {
        if(count($listManufacturer) == 0)
        {
            return;
        }
        // total testing of all manufacturers
        $total = collect($listManufacturer)->sum('totalAchieve');
        // Follow strat column write data in excel file
        $startIndexSheet = $endIndexSheet = 7;
        $number = $indexColumn = 0;
        foreach($data as $year => $collect)
        {
            $totalReportPerYear = $collect->sum(function($item){
                return $item['totalAchieve'];
            });

            $count = $collect->count();
            $endIndexSheet = $startIndexSheet + $count - 1;
            $spreadsheet->mergeCells("D{$startIndexSheet}:D{$endIndexSheet}")
                ->getCell("D{$startIndexSheet}")
                ->setValue($year);
            $indexColumn = $startIndexSheet;
            foreach($collect as $manufacturer => $item)
            {
                $number++;
                $spreadsheet->getCell("C{$indexColumn}")->setValue($number);
                $spreadsheet->getCell("E{$indexColumn}")
                    ->setValue($manufacturer);
                $spreadsheet->getCell("F{$indexColumn}")
                    ->setValue($item['totalAchieve']);
                $spreadsheet->getCell("G{$indexColumn}")
                    ->setValue(round($item['totalAchieve'] / $total * 100, 2));
                $indexColumn++;
            }
            $startIndexSheet += $count;
        }
        if($indexColumn != 0){
            $spreadsheet->getCell("C{$indexColumn}")->setValue($number + 1);
            $spreadsheet->mergeCells("D{$startIndexSheet}:E{$startIndexSheet}")->getCell("D{$indexColumn}")->setValue('Tổng số');
            $spreadsheet->getCell("F{$indexColumn}")->setValue($total);
            $spreadsheet->getCell("G{$indexColumn}")->setValue('100');
        }
    }

    private function writeChartDataCuttersByManufacturer($listManufacturer, $spreadsheet)
    {
        if(count($listManufacturer) == 0)
        {
            return;
        }
        // total testing of all manufacturers
        $total = collect($listManufacturer)->sum('totalAchieve');
        // Follow file excel
        $startIndexsheet = 8;
        foreach($listManufacturer as $value)
        {
            $spreadsheet->getCell("D{$startIndexsheet}")
            ->setValue($value['name']);

            $spreadsheet->getCell("E{$startIndexsheet}")
            ->setValue(round($value['totalAchieve'] / $total * 100, 2));

            $startIndexsheet++;
        }
    }
    /**
     * index report 28-29-30-31-32 Báo cáo hạng mục thí nghiệm không đạt theo hãng sản xuất
     * @return Renderable
     */
    public function indexShareReportThree($title)
    {
        $meta_title = $title_head = $route_export  = "";
        $arrayTypes = [];
        switch ($title) {
            case 'may-cat-bao-cao-hang-muc-thi-nghiem-khong-dat-theo-hang-san-xuat':
                $meta_title = 'PXCĐ - Báo cáo thí nghiệm nhất thứ - Máy cắt - Báo cáo hạng mục thí nghiệm không đạt theo hãng sản xuất';
                $title_head = 'Báo cáo thí nghiệm nhất thứ - Máy cắt - Báo cáo các hạng mục thí nghiệm máy cắt không đạt theo hãng sản xuất';
                $route_export = route('admin.cuttingMachineCategoryComparisonExport');
                $arrayTypes = [
                    '10.1 BBTN Máy cắt 1 buồng cắt 1 bộ truyền động',
                    '10.2. BBTN Máy cắt 1 buồng cắt 3 bộ truyền động',
                    '10.3. BBTN Máy cắt 2 buồng cắt 3 bộ truyền động'
                ];
                break;
            case 'may-bien-dong-dien-bao-cao-hang-muc-thi-nghiem-khong-dat-theo-hang-san-xuat':
                $meta_title = 'PXCĐ - Báo cáo thí nghiệm nhất thứ - Máy biến dòng điện - Báo cáo hạng mục thí nghiệm không đạt theo hãng sản xuất';
                $title_head = 'Báo cáo thí nghiệm nhất thứ - Máy biến dòng điện - Báo cáo các hạng mục thí nghiệm máy biến dòng điện không đạt theo từng hãng sản xuất';
                $route_export = route('admin.cuttingTranferMachineCategoryComparisonExport');
                $arrayTypes = [
                    '9.1. BBTN Máy biến dòng điện 3 cuộn 3 tỷ số',
                    '9.2. BBTN Máy biến dòng điện 5 cuộn 5 tỷ số',
                ];
                break;
            case 'may-bien-ap-phan-phoi-bao-cao-hang-muc-thi-nghiem-khong-dat-theo-hang-san-xuat':
                $meta_title = 'PXCĐ - Báo cáo thí nghiệm nhất thứ - Máy biến áp phân phối - Báo cáo hạng mục thí nghiệm không đạt theo hãng sản xuất';
                $title_head = 'Báo cáo thí nghiệm nhất thứ - Máy biến áp phân phối - Báo cáo các hạng mục thí nghiệm MBA tự phân phối (MBA tự dùng)  không đạt theo từng hãng sản xuất';
                $route_export = route('admin.distributionTransformerExport');
                $arrayTypes = [
                    '13.8. BBTN Máy biến áp phân phối 1 pha',
                    '13.9. BBTN Máy biến áp phân phối 3 pha',
                ];
                break;
            case 'cap-luc-bao-cao-hang-muc-thi-nghiem-khong-dat-theo-hang-san-xuat':
                $meta_title = 'PXCĐ - Báo cáo thí nghiệm nhất thứ - Cáp lực - Báo cáo hạng mục thí nghiệm không đạt theo hãng sản xuất';
                $title_head = 'Báo cáo thí nghiệm nhất thứ - Cáp lực -  Báo cáo các hạng mục thí nghiệm cáp lực  không đạt theo từng hãng sản xuất';
                $route_export = route('admin.capbleCategoryComparisonExport');
                $arrayTypes = [
                    '2.1. BBTN Cáp lực 1 lõi',
                    '2.2. BBTN Cáp lực 3 lõi',
                ];
                break;
            case 'may-bien-dien-ap-bao-cao-hang-muc-thi-nghiem-khong-dat-theo-hang-san-xuat':
                $meta_title = 'PXCĐ - Báo cáo thí nghiệm nhất thứ - Máy biến điện áp - Báo cáo hạng mục thí nghiệm không đạt theo hãng sản xuất';
                $title_head = 'Báo cáo thí nghiệm nhất thứ - Máy biến điện áp - Báo cáo các hạng mục thí nghiệm máy biến điện áp không đạt theo từng hãng sản xuất';
                $route_export = route('admin.voltageCategoryComparisonExport');
                $arrayTypes = [
                    '8.1. BBTN Máy biến điện áp 4 cuộn dây'
                ];
                break;
            default:
                break;
        }

        foreach ($arrayTypes as $key => $val) {
            if ($key == 0) {
                $whereClauseBase = "class.type IN (";
            }
            $whereClauseBase .= "'" . $val . "'";
            if ($key < count($arrayTypes) - 1) {
                $whereClauseBase .= ",";
            } else {
                $whereClauseBase .= ")";
            }
        }

        $whereClauseBase .= " AND  zManufacturer.zsym IS NOT NULL "
            . " AND class.zetc_type = 1";

        $attributes = ['zManufacturer.zsym', 'class.type'];

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
        
        $service = new CAWebServices(env('CA_WSDL_URL'));
        $user = session()->get(env('AUTH_SESSION_KEY'));
        $manufactures = $service->getManufactures($user['ssid'])->unique('zsym')->pluck('zsym')->toArray();
        return view('electromechanical::cuttingMachineCategoryComparison.index', compact('manufactures','meta_title','title_head','route_export'));
    }

    public function cuttingMachineCategoryComparisonExport(Request $request)
    {
        try {

            $currentYear = $request->year;
            $prevYear = $currentYear - 1;

            if ($currentYear < now()->year) {
                $currentYear = strtotime($currentYear . "-12-31") + 86399;
            } else {
                $currentYear = strtotime(now());
            }
            $prevYear = strtotime($prevYear  . "-01-01");
            $arrayTypes = [
                '10.1 BBTN Máy cắt 1 buồng cắt 1 bộ truyền động',
                '10.2. BBTN Máy cắt 1 buồng cắt 3 bộ truyền động',
                '10.3. BBTN Máy cắt 2 buồng cắt 3 bộ truyền động'
            ];

            foreach ($arrayTypes as $key => $val) {
                if ($key == 0) {
                    $whereClauseBase = "class.type IN (";
                }
                $whereClauseBase .= "'" . $val . "'";
                if ($key < count($arrayTypes) - 1) {
                    $whereClauseBase .= ",";
                } else {
                    $whereClauseBase .= ")";
                }
            }

            if($request->manufacturer)
            {
                foreach ($request->manufacturer as $key => $val) {
                    if ($key == 0) {
                        $whereClauseBase .= " AND zManufacturer.zsym IN (";
                    }
                    $whereClauseBase .= "'". $val ."'";
                    if ($key < count($request->manufacturer) - 1) {
                        $whereClauseBase .= ",";
                    } else {
                        $whereClauseBase .= ")";
                    }
                }
            }else{
                $whereClauseBase .= " AND zManufacturer.zsym IS NOT NULL ";
            }

            $whereClauseBase .=  " AND zlaboratoryDate >= " . $prevYear
            . " AND zlaboratoryDate <= " . $currentYear
            . " AND class.zetc_type = 1";

            $attributes = [
                'id',
                'class.type',
                'zlaboratoryDate',
                'name',
                'zManufacturer.zsym'
            ];

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
            // Loop fetch data assoc and hanlde assoc data
            foreach($reports as $index => $report)
            {
                // Set the default value for each item to be counted is 0
                $reports[$index] = array_merge($report, $this->addDefaultValue());

                // substr, explode, join classtype get name objectType
                $objectType = 'zCA'.join('_', explode('.', substr($report['class.type'], 0, 4)));
                $attributesChild = $this->getAttributeByType($report['class.type']);
                $where = "id = U'". $report['id'] ."'";
                $data = getDataFromService('doSelect', $objectType, $attributesChild, $where, 100);

                // Get each attribute according to the report items
                $attrExternalCheck = $this->attributeExternalCheck(); // $countAttr1
                $attributeInsulation = $this->attributeInsulation();// $countAttr2
                $attributeContactResistance = $this->attributeContactResistanceByType($report['class.type']);
                $attributeClosingTime = $this->attributeClosingTimeByType($report['class.type']);
                $attributeSwingHigh = $this->attributeSwingHighByType($report['class.type']);
                $attributeSwitchingCoilResistance = $this->attributeSwitchingCoilResistanceByType($report['class.type']);
                $attributeSwitchingCoilInsulationResistance = $this->attributeSwitchingCoilInsulationResistanceByType($report['class.type']);
                $attributeOther = $this->attributeOtherByType($report['class.type']);

                if(count($data) == 0)
                {
                    $reports[$index]['other_not_achieved'] = count($attributeOther);
                }else{
                    if($this->arrayKeyExistArray($data, $attrExternalCheck)){
                        $reports[$index]['external_check'] = 1;
                    }

                    if($this->arrayKeyExistArray($data, $attributeInsulation)){
                        $reports[$index]['insulation'] = 1;
                    }

                    if($this->arrayKeyExistArray($data, $attributeContactResistance)){
                        $reports[$index]['contact_resistance'] = 1;
                    }

                    if($this->arrayKeyExistArray($data, $attributeClosingTime)){
                        $reports[$index]['closing_time'] = 1;
                    }

                    if($this->arrayKeyExistArray($data, $attributeSwingHigh)){
                        $reports[$index]['swing_high'] = 1;
                    }

                    if($this->arrayKeyExistArray($data, $attributeSwitchingCoilResistance)){
                        $reports[$index]['switching_coil_resistance'] = 1;
                    }

                    if($this->arrayKeyExistArray($data, $attributeSwitchingCoilInsulationResistance)){
                        $reports[$index]['switching_coil_insulation_resistance'] = 1;
                    }
                }

                foreach($data as $key => $value)
                {
                    // attributeOther
                    foreach($attributeOther as $attr)
                    {
                        if(array_key_exists($attr, $value) && $value[$attr] == "1")
                        {
                            $reports[$index]['other_achieved'] += 1;
                        }else{
                            $reports[$index]['other_not_achieved'] += 1;
                        }
                    }
                }

                // convert timestamp to year
                $reports[$index]['zlaboratoryDate'] = Carbon::createFromTimestamp($report['zlaboratoryDate'])->year;
            }

            // Group By Year Data
            $groupByYear = collect($reports)->groupBy('zlaboratoryDate');

            // Group By Manufacturer
            foreach($groupByYear as $year => $collectYear)
            {
                $groupByYear[$year] = $collectYear->groupBy(function($item){
                    return $item['zManufacturer.zsym'];
                })->sortKeys();
            }

            $endYear = Carbon::createFromTimestamp($currentYear)->year;
            $startYear = Carbon::createFromTimestamp($prevYear)->year;

            // add index year to collection $groupByYear and add each manufacturer when query not found
            for($i = $startYear; $i <= $endYear; $i++)
            {
                if(!$groupByYear->has($i))
                {
                    $groupByYear[$i] = collect();
                }
                foreach($groupByYear as $year => $collectManufacturer)
                {

                    foreach($collectManufacturer as $manufacturerName => $manufacturerCollect)
                    {
                        if( !$groupByYear[$i]->has($manufacturerName) )
                        {
                            $groupByYear[$i][$manufacturerName] = collect([ $this->addDefaultValue() ]);
                        }
                    }
                }
            }

            // sort by year
            $groupByYear = $groupByYear->sortKeys();

            // Final data format according to excel file
            $dataFormated = [];
            foreach($groupByYear as $year => $collect)
            {
                $dataConvert[$year] = [];
                foreach($collect as $manufacturerName => $manufacturerCollect)
                {
                    $manufacturerData = [
                        'external_check_achieved' => 0,
                        'external_check_not_achieved' => 0,
                        'insulation_achieved' => 0,
                        'insulation_not_achieved' => 0,
                        'contact_resistance_achieved' => 0,
                        'contact_resistance_not_achieved' => 0,
                        'closing_time_achieved' => 0,
                        'closing_time_not_achieved' => 0,
                        'swing_high_achieved' => 0,
                        'swing_high_not_achieved' => 0,
                        'switching_coil_resistance_achieved' => 0,
                        'switching_coil_resistance_not_achieved' => 0,
                        'switching_coil_insulation_resistance_achieved' => 0,
                        'switching_coil_insulation_resistance_not_achieved' => 0,
                        'other_achieved' => 0,
                        'other_not_achieved' => 0,
                        'total_achieved' => 0,
                        'total_not_achieved' => 0
                    ];
                    // length of the collection when there was no maker that year. the value marked above and has a length of 9
                    if(count($manufacturerCollect[0]) > 9)
                    {
                        $arrayKeysCheck = ['external_check', 'insulation', 'contact_resistance', 'closing_time', 'swing_high', 'switching_coil_resistance', 'switching_coil_insulation_resistance'];
                        // check each record by 1 to pass the "Other" exclusion
                        foreach($manufacturerCollect as $item)
                        {
                            foreach($arrayKeysCheck as $key)
                            {
                                if($item[$key] == 1)
                                {
                                    $manufacturerData["{$key}_achieved"] += 1;
                                    $manufacturerData['total_achieved'] += 1;

                                }else{

                                    $manufacturerData["{$key}_not_achieved"] += 1;
                                    $manufacturerData['total_not_achieved'] += 1;
                                }
                            }
                            // Other Attr
                            $manufacturerData['other_achieved'] += $item['other_achieved'];
                            $manufacturerData['other_not_achieved'] += $item['other_not_achieved'];

                            // Total achieved and total not achieved
                            $manufacturerData['total_achieved'] += $item['other_achieved'];
                            $manufacturerData['total_not_achieved'] += $item['other_not_achieved'];
                        }
                    }
                    $dataConvert[$year] += [
                        $manufacturerName => $manufacturerData
                    ];

                }
                // add dataConvert keeping the same key as $year
                $dataFormated += $dataConvert;
            }


            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);

            $listYear = array_keys($dataFormated);
            if(count($listYear) > 0)
            {
                $listManufacturer = array_keys($dataFormated[$listYear[0]]);
            }else{
                $listManufacturer = [];
            }

            // count the number of years to report
            $count = count($listManufacturer);
            // Read Template Excel
            $template = storage_path("templates_excel/phan_xuong_co_dien/bao-cao-thi-nghiem-nhat-thu/may-cat/bao-cao-hang-muc-thi-nghiem-khong-dat-theo-hang-san-xuat-{$count}.xlsx");
            $spreadsheet = $reader->load($template);
            // Config style for sheet
            $style = [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ];

            // Set Style for Sheet
            $spreadsheet->getDefaultStyle()->applyFromArray($style);

            $sheetThongKe = $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
            $sheetDataBD = $spreadsheet->setActiveSheetIndexByName('DATA_BD');

            // get array year in array format fill to sheet
            $listYear = array_keys($dataFormated);
            $sheetThongKe->getCell('E7')->setValue($listYear[0]);
            $sheetThongKe->getCell('I7')->setValue($listYear[0]);
            $sheetThongKe->getCell('G7')->setValue($listYear[1]);
            $sheetThongKe->getCell('K7')->setValue($listYear[1]);

            $sheetDataBD->getCell('D3')->setValue("BÁO CÁO SO SÁNH GIỮA CÁC HẠNG MỤC THÍ NGHIỆM MÁY CẮT KHÔNG ĐẠT TỪ NĂM {$listYear[0]} - {$listYear[1]}");
            $sheetThongKe->getCell('C3')->setValue("BÁO CÁO SO SÁNH KẾT QUẢ CÁC HẠNG MỤC THÍ NGHIỆM CỦA MÁY CẮT THEO TỪNG HÃNG SẢN XUẤT");
            $spreadsheet->setActiveSheetIndexByName("BIEU_DO")->getCell('C3')->setValue("BIỂU ĐỒ SO SÁNH NGUYÊN NHÂN MÁY CẮT KHÔNG ĐẠT CỦA HÃNG SẢN XUẤT");

            $indexManufacturer = 0;
            // dynamic chart title when reporting by year sheet "BIEU_DO_MOI_NAM"
            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $chartNames = $worksheet->getChartNames();
                // Exclude chart 1 in sheet BIEU_DO
                foreach($chartNames as $chartName)
                {
                    if(count($listManufacturer) > 0)
                    {
                        $chart = $worksheet->getChartByName($chartName);
                        $titleChar = new \PhpOffice\PhpSpreadsheet\Chart\Title("So sánh nguyên nhân máy cắt hãng {$listManufacturer[$indexManufacturer]} không đạt giữa các năm");
                        $chart->setTitle($titleChar);
                        $indexManufacturer++;
                    }
                }
            }

            // Write data to sheet 'THONG_KE_DU_LIEU' Cutting Machine Category Comparison
            $this->writeDataSheetCuttingMachineCategoryComparison($dataFormated, $sheetThongKe);

            // Write data to sheet 'DATA_BD' Cutting Machine Category Comparison
            $this->writeChartSheetCuttingMachineCategoryComparison($dataFormated, $sheetDataBD);
            $this->fillSearchDataShareReport($sheetThongKe, $request);
            // Set active sheet BIEU_DO when view file
            $spreadsheet->setActiveSheetIndexByName("BIEU_DO");

            $title = 'bao-cao-cac-hang-muc-thi-nghiem-may-cat-khong-dat-theo-tung-hang-san-xuat-'.time().'.xlsx';
            $url = writeExcel($spreadsheet, $title);
            return response()->json([
                'success' => true,
                'url' => $url,
            ]);

        } catch (\Exception $e) {
            Log::error('[Electromechanical] Cutting machines report of failed test items by manufacturer: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));

            return response()->json([
                'error' => [$e->getMessage()]
            ]);
        }
    }

    private function writeDataSheetCuttingMachineCategoryComparison($data, $spreadsheet)
    {
        if(count($data) == 0)
        {
            return;
        }
        // Flowing template excel
        $startIndexSheet = $endIndexSheet = 9;
        $listYear = array_keys($data);
        $listManufacturer = array_keys($data[$listYear[0]]);

        foreach($listManufacturer as $manufacturer)
        {
            // Calculate the number of ending lines to merge cell
            $endIndexSheet = $startIndexSheet + 6;
            // Merge cell 7 column
            $spreadsheet->mergeCells("C{$startIndexSheet}:C{$endIndexSheet}")
            ->getCell("C{$startIndexSheet}")
            ->setValue($manufacturer);

            // column order to write data
            $indexColumn = $startIndexSheet;
            // There are 7 attributes that need statistics
            for($i = 1; $i < 8; $i++)
            {

                $infoCase = $this->hanldeWriteDataSheetCuttingMachineCategoryComparison($i);

                $title = $infoCase['title'];
                $attrAchieved = $infoCase['attrAchieved'];
                $attrNotAchieved = $infoCase['attrNotAchieved'];

                $totalPrevYear = $data[$listYear[0]][$manufacturer][$attrAchieved] + $data[$listYear[0]][$manufacturer][$attrNotAchieved];

                $totalCurrentYear = $data[$listYear[1]][$manufacturer][$attrAchieved] + $data[$listYear[1]][$manufacturer][$attrNotAchieved];

                $spreadsheet->getCell("D{$indexColumn}")
                    ->setValue($title);
                $spreadsheet->getCell("E{$indexColumn}")
                    ->setValue($data[$listYear[0]][$manufacturer][$attrAchieved]);
                $spreadsheet->getCell("F{$indexColumn}")
                    ->setValue($data[$listYear[0]][$manufacturer][$attrNotAchieved]);
                $spreadsheet->getCell("G{$indexColumn}")
                    ->setValue($data[$listYear[1]][$manufacturer][$attrAchieved]);
                $spreadsheet->getCell("H{$indexColumn}")
                    ->setValue($data[$listYear[1]][$manufacturer][$attrNotAchieved]);

                if($totalPrevYear > 0)
                {
                    $spreadsheet->getCell("I{$indexColumn}")
                    ->setValue(round($data[$listYear[0]][$manufacturer][$attrAchieved] / $totalPrevYear * 100, 2));

                    $spreadsheet->getCell("J{$indexColumn}")
                    ->setValue(round($data[$listYear[0]][$manufacturer][$attrNotAchieved] / $totalPrevYear * 100, 2));


                }else{
                    $spreadsheet->getCell("I{$indexColumn}")
                    ->setValue(0);
                    $spreadsheet->getCell("J{$indexColumn}")
                    ->setValue(0);
                }

                if($totalCurrentYear > 0)
                {
                    $spreadsheet->getCell("K{$indexColumn}")
                    ->setValue(round($data[$listYear[1]][$manufacturer][$attrAchieved] / $totalCurrentYear * 100, 2));
                    $spreadsheet->getCell("L{$indexColumn}")
                    ->setValue(round($data[$listYear[1]][$manufacturer][$attrNotAchieved] / $totalCurrentYear * 100, 2));
                }else{
                    $spreadsheet->getCell("K{$indexColumn}")
                    ->setValue(0);
                    $spreadsheet->getCell("L{$indexColumn}")
                    ->setValue(0);
                }

                $indexColumn++;

            }

            // There are 7 statistical properties/year so merge cell
            $startIndexSheet += 7;
        }
    }

    private function hanldeWriteDataSheetCuttingMachineCategoryComparison($index)
    {
        $data = [];
        switch ($index) {
            case 1:
                $data = [
                    'title' => 'Kiểm tra bên ngoài',
                    'attrAchieved' => 'external_check_achieved',
                    'attrNotAchieved' => 'external_check_not_achieved',
                ];
                break;
            case 2:
                $data = [
                    'title' => 'Điện trở cách điện',
                    'attrAchieved' => 'insulation_achieved',
                    'attrNotAchieved' => 'insulation_not_achieved',
                ];
                break;
            case 3:
                $data = [
                    'title' => 'Điện trở tiếp xúc',
                    'attrAchieved' => 'contact_resistance_achieved',
                    'attrNotAchieved' => 'contact_resistance_not_achieved',
                ];
                break;
            case 4:
                $data = [
                    'title' => 'Thời gian đóng cắt',
                    'attrAchieved' => 'closing_time_achieved',
                    'attrNotAchieved' => 'closing_time_not_achieved',
                ];
                break;
            case 5:
                $data = [
                    'title' => 'Thử điện áp xoay chiều tăng cao tần số công nghiệp',
                    'attrAchieved' => 'swing_high_achieved',
                    'attrNotAchieved' => 'swing_high_not_achieved',
                ];
                break;
            case 6:
                $data = [
                    'title' => 'Điện trở một chiều cuộn đóng cắt',
                    'attrAchieved' => 'switching_coil_resistance_achieved',
                    'attrNotAchieved' => 'switching_coil_resistance_not_achieved',
                ];
                break;
            case 7:
                $data = [
                    'title' => 'Điện trở cách điện cuộn đóng/cắt',
                    'attrAchieved' => 'switching_coil_insulation_resistance_achieved',
                    'attrNotAchieved' => 'switching_coil_insulation_resistance_not_achieved',
                ];
                break;
            default:
                break;
        }
        return $data;
    }

    private function writeChartSheetCuttingMachineCategoryComparison($data, $spreadsheet)
    {

        if(count($data) == 0)
        {
            return;
        }

        $startIndexSheet = 6;

        $listYear = array_keys($data);
        $listManufacturer = array_keys($data[$listYear[0]]);

        foreach($listManufacturer as $manufacturer)
        {
            for($i = 0; $i < 4; $i++)
            {
                switch ($i) {
                    case 0:
                        $spreadsheet->getCell("D{$startIndexSheet}")
                            ->setValue($manufacturer);
                        $startIndexSheet++;
                        break;
                    case 1:
                        $spreadsheet->getCell("D{$startIndexSheet}")
                            ->setValue("Hạng mục");
                        $spreadsheet->getCell("E{$startIndexSheet}")
                            ->setValue("Điện trở tiếp xúc");
                        $spreadsheet->getCell("F{$startIndexSheet}")
                            ->setValue("Thời gian đóng cắt");
                        $spreadsheet->getCell("G{$startIndexSheet}")
                            ->setValue("Điện trở cách điện");
                        $spreadsheet->getCell("H{$startIndexSheet}")
                            ->setValue("Thử điện áp xoay chiều tăng cao tần số công nghiệp");
                        $spreadsheet->getCell("I{$startIndexSheet}")
                            ->setValue("Điện trở một chiều cuộn đóng cắt");
                        $spreadsheet->getCell("J{$startIndexSheet}")
                            ->setValue("Điện trở cách điện cuộn đóng, cắt, động cơ tích năng");
                        $spreadsheet->getCell("K{$startIndexSheet}")
                            ->setValue("Khác");
                        $spreadsheet->getCell("L{$startIndexSheet}")
                            ->setValue("Tổng");
                        $startIndexSheet++;
                        break;
                    case 2;
                        $spreadsheet->getCell("D{$startIndexSheet}")
                            ->setValue("Năm {$listYear[0]}");
                        $spreadsheet->getCell("E{$startIndexSheet}")
                            ->setValue($data[$listYear[0]][$manufacturer]['contact_resistance_not_achieved']);
                        $spreadsheet->getCell("F{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['closing_time_not_achieved']);
                        $spreadsheet->getCell("G{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['insulation_not_achieved']);
                        $spreadsheet->getCell("H{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['swing_high_not_achieved']);
                        $spreadsheet->getCell("I{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['switching_coil_resistance_not_achieved']);
                        $spreadsheet->getCell("J{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['switching_coil_insulation_resistance_not_achieved']);
                        $spreadsheet->getCell("K{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['other_not_achieved'] +  $data[$listYear[0]][$manufacturer]['external_check_not_achieved']);
                        $spreadsheet->getCell("L{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['total_not_achieved']);
                        $startIndexSheet++;
                        break;
                    case 3;
                        $spreadsheet->getCell("D{$startIndexSheet}")
                            ->setValue("Năm {$listYear[1]}");
                        $spreadsheet->getCell("E{$startIndexSheet}")
                            ->setValue($data[$listYear[1]][$manufacturer]['contact_resistance_not_achieved']);
                        $spreadsheet->getCell("F{$startIndexSheet}")
                                ->setValue($data[$listYear[1]][$manufacturer]['closing_time_not_achieved']);
                        $spreadsheet->getCell("G{$startIndexSheet}")
                                ->setValue($data[$listYear[1]][$manufacturer]['insulation_not_achieved']);
                        $spreadsheet->getCell("H{$startIndexSheet}")
                                ->setValue($data[$listYear[1]][$manufacturer]['swing_high_not_achieved']);
                        $spreadsheet->getCell("I{$startIndexSheet}")
                                ->setValue($data[$listYear[1]][$manufacturer]['switching_coil_resistance_not_achieved']);
                        $spreadsheet->getCell("J{$startIndexSheet}")
                                ->setValue($data[$listYear[1]][$manufacturer]['switching_coil_insulation_resistance_not_achieved']);
                        $spreadsheet->getCell("K{$startIndexSheet}")
                                ->setValue($data[$listYear[1]][$manufacturer]['other_not_achieved'] + $data[$listYear[1]][$manufacturer]['external_check_not_achieved']);
                        $spreadsheet->getCell("L{$startIndexSheet}")
                                ->setValue($data[$listYear[1]][$manufacturer]['total_not_achieved']);
                        $startIndexSheet++;
                        break;
                    default:
                        break;
                }
            }
        }
    }

    private function addDefaultValue()
    {
        return [
            'external_check' => 0,
            'insulation' => 0,
            'contact_resistance' => 0,
            'closing_time' => 0,
            'swing_high' => 0,
            'switching_coil_resistance' => 0,
            'switching_coil_insulation_resistance' => 0,
            'other_achieved' => 0,
            'other_not_achieved' => 0
        ];
    }

    private function getAttributeByType($type)
    {
        return array_merge(
            $this->attributeExternalCheck(),
            $this->attributeInsulation(),
            $this->attributeContactResistanceByType($type),
            $this->attributeClosingTimeByType($type),
            $this->attributeSwingHighByType($type),
            $this->attributeSwitchingCoilResistanceByType($type),
            $this->attributeSwitchingCoilInsulationResistanceByType($type),
            $this->attributeOtherByType($type)
        );
    }

    // Minor attributes in each major test item
    private function attributeExternalCheck()
    {
        // All records of this attribute are the same
        return [
            'z18cnochk',
            'z20cpichk',
            'z22cccchk',
            'z24cmbchk',
        ];
    }

    private function attributeInsulation()
    {
        // All records of this attribute are the same
        return [
            'z26irmchk'
        ];
    }

    private function attributeContactResistanceByType($type)
    {
        $attr = [];
        switch ($type) {
            case '10.1 BBTN Máy cắt 1 buồng cắt 1 bộ truyền động':
                $attr = [
                    'z35mcrmchk'
                ];
                break;
            case '10.2. BBTN Máy cắt 1 buồng cắt 3 bộ truyền động':
            case '10.3. BBTN Máy cắt 2 buồng cắt 3 bộ truyền động':
                $attr = [
                    'z31mcrmchk'
                ];
                break;
            default:
                break;
        }
        return $attr;
    }

    private function attributeClosingTimeByType($type)
    {
        $attr = [];
        switch ($type) {
            case '10.1 BBTN Máy cắt 1 buồng cắt 1 bộ truyền động':
            case '10.2. BBTN Máy cắt 1 buồng cắt 3 bộ truyền động':
                $attr = [
                    'z41otmchk'
                ];
                break;
            case '10.3. BBTN Máy cắt 2 buồng cắt 3 bộ truyền động':
                $attr = [
                    'z37otmchk'
                ];
                break;
            default:
                break;
        }
        return $attr;
    }

    private function attributeSwingHighByType($type)
    {
        $attr = [];
        switch ($type) {
            case '10.1 BBTN Máy cắt 1 buồng cắt 1 bộ truyền động':
                $attr = [
                    'z84achvwtmchk'
                ];
                break;
            case '10.2. BBTN Máy cắt 1 buồng cắt 3 bộ truyền động':
                $attr = [
                    'z68achvwtchk'
                ];
                break;
            case '10.3. BBTN Máy cắt 2 buồng cắt 3 bộ truyền động':
                $attr = [
                    'z75achvwtchk'
                ];
                break;
            default:
                break;
        }
        return $attr;
    }

    private function attributeSwitchingCoilResistanceByType($type)
    {
        $attr = [];
        switch ($type) {
            case '10.1 BBTN Máy cắt 1 buồng cắt 1 bộ truyền động':
                $attr = [
                    'z65irdcrcocmchk'
                ];
                break;
            case '10.2. BBTN Máy cắt 1 buồng cắt 3 bộ truyền động':
                $attr = [
                    'z49irdcrchk'
                ];
                break;
            case '10.3. BBTN Máy cắt 2 buồng cắt 3 bộ truyền động':
                $attr = [
                    'z56irdcrcchk'
                ];
                break;
            default:
                break;
        }
        return $attr;
    }

    private function attributeSwitchingCoilInsulationResistanceByType($type)
    {
        $attr = [];
        switch ($type) {
            case '10.1 BBTN Máy cắt 1 buồng cắt 1 bộ truyền động':
                $attr = [
                    'z76irdcrmmchk'
                ];
                break;
            case '10.2. BBTN Máy cắt 1 buồng cắt 3 bộ truyền động':
                $attr = [
                    'z60irdcrmmchk'
                ];
                break;
            case '10.3. BBTN Máy cắt 2 buồng cắt 3 bộ truyền động':
                $attr = [
                    'z67irdcrmmchk'
                ];
                break;
            default:
                break;
        }
        return $attr;
    }

    private function attributeOtherByType($type)
    {
        $attr = [];
        switch ($type) {
            case '10.1 BBTN Máy cắt 1 buồng cắt 1 bộ truyền động':
                $attr = [
                    'z63cgpgchk',
                    'z83cmchk',
                    'z99fttchk',
                    'z105dcrmchk',
                    'z108ctmmchk',
                    'z111pdmchk'
                ];
                break;
            case '10.2. BBTN Máy cắt 1 buồng cắt 3 bộ truyền động':
                $attr = [
                    'z47cgpgchk',
                    'z67cmchk',
                    'z75fttchk',
                    'z79dcrmchk',
                    'z82ctmmchk',
                    'z84pdmchk'
                ];
                break;
            case '10.3. BBTN Máy cắt 2 buồng cắt 3 bộ truyền động':
                $attr = [
                    'z54cgpgchk',
                    'z74cmchk',
                    'z82fttchk',
                    'z86dcrmchk',
                    'z89ctmmchk',
                    'z92pdmchk'
                ];
                break;
            default:
                break;
        }
        return $attr;
    }
    /**
     * export report 29 - Máy biến dòng điện - Báo cáo số lượng máy Máy biến dòng điện đã thí nghiệm
     * @return Renderable
     */
    public function cuttingTranferMachineCategoryComparisonExport(Request $request)
    {
        try {

            $currentYear = $request->year;
            $prevYear = $currentYear - 1;

            if ($currentYear < now()->year) {
                $currentYear = strtotime($currentYear . "-12-31") + 86399;
            } else {
                $currentYear = strtotime(now());
            }
            $prevYear = strtotime($prevYear  . "-01-01");
            $arrayTypes = [
                '9.1. BBTN Máy biến dòng điện 3 cuộn 3 tỷ số',
                '9.2. BBTN Máy biến dòng điện 5 cuộn 5 tỷ số',
            ];

            foreach ($arrayTypes as $key => $val) {
                if ($key == 0) {
                    $whereClauseBase = "class.type IN (";
                }
                $whereClauseBase .= "'" . $val . "'";
                if ($key < count($arrayTypes) - 1) {
                    $whereClauseBase .= ",";
                } else {
                    $whereClauseBase .= ")";
                }
            }

            if($request->manufacturer)
            {
                foreach ($request->manufacturer as $key => $val) {
                    if ($key == 0) {
                        $whereClauseBase .= " AND zManufacturer.zsym IN (";
                    }
                    $whereClauseBase .= "'". $val ."'";
                    if ($key < count($request->manufacturer) - 1) {
                        $whereClauseBase .= ",";
                    } else {
                        $whereClauseBase .= ")";
                    }
                }
            }else{
                $whereClauseBase .= " AND zManufacturer.zsym IS NOT NULL ";
            }

            $whereClauseBase .= " AND zlaboratoryDate >= " . $prevYear
            . " AND zlaboratoryDate <= " . $currentYear
            . " AND class.zetc_type = 1";

            $attributes = [
                'id',
                'class.type',
                'zlaboratoryDate',
                'name',
                'zManufacturer.zsym'
            ];

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

            // Loop fetch data assoc and hanlde assoc data
            foreach($reports as $index => $report)
            {
                // Set the default value for each item to be counted is 0
                $reports[$index] = array_merge($report, $this->addDefaultValueCurrentTransformer2());
                // substr, explode, join classtype get name objectType
                $objectType = 'zCA'.join('_', explode('.', substr($report['class.type'], 0, 3)));
                $attributesChild = $this->getAttributeCurrentTransformerByType($report['class.type']);
                $where = "id = U'". $report['id'] ."'";
                $data = getDataFromService('doSelect', $objectType, $attributesChild, $where, 100);
                // Get each attribute according to the report items
                $attrExternalCheck = $this->getAttributeExternalCheckCurrentTransformerByType($report['class.type']); // $countAttr1
                $attributeInsulation = $this->getAttributeInsulationCurrentTransformerByType($report['class.type']);// $countAttr2
                $attributeDCResistance = $this->getAttributeDCResistanceCurrentTransformerByType($report['class.type']);
                $attributeRatioMeasurement = $this->getAttributeRatioMeasurementCurrentTransformerByType($report['class.type']);
                $attributeSwingHigh = $this->attributeSwingHighCurrentTransformerByType($report['class.type']);
                $attributeMagnetizationProperties = $this->getAttributeMagnetizationPropertiesCurrentTransformerByType($report['class.type']);
                $attributeOther = $this->getAttributeOtherCurrentTransformerByType($report['class.type']);

                if(count($data) == 0)
                {
                    // add 1 is external check item $attrExternalCheck
                    $reports[$index]['other_not_achieved'] = count($attributeOther) + 1;
                }else{
                    if($this->arrayKeyExistArray($data, $attrExternalCheck))
                    {
                        $reports[$index]['other_achieved'] += 1;
                    }else{
                        $reports[$index]['other_not_achieved'] += 1;
                    }

                    if($this->arrayKeyExistArray($data, $attributeInsulation))
                    {
                        $reports[$index]['insulation'] = 1;
                    }

                    if($this->arrayKeyExistArray($data, $attributeDCResistance))
                    {
                        $reports[$index]['dc_resistor'] = 1;
                    }

                    if($this->arrayKeyExistArray($data, $attributeRatioMeasurement))
                    {
                        $reports[$index]['ratio_measurement'] = 1;
                    }

                    if($this->arrayKeyExistArray($data, $attributeInsulation))
                    {
                        $reports[$index]['insulation'] = 1;
                    }

                    if($this->arrayKeyExistArray($data, $attributeSwingHigh))
                    {
                        $reports[$index]['swing_high'] = 1;
                    }

                    if($this->arrayKeyExistArray($data, $attributeMagnetizationProperties))
                    {
                        $reports[$index]['magnetization_properties'] = 1;
                    }

                    foreach($data as $key => $value)
                    {
                        // attributeOther
                        foreach($attributeOther as $attr)
                        {
                            if(array_key_exists($attr, $value) && $value[$attr] == "1")
                            {
                                $reports[$index]['other_achieved'] += 1;
                            }else{
                                $reports[$index]['other_not_achieved'] += 1;
                            }
                        }
                    }
                }
                // convert timestamp to year
                $reports[$index]['zlaboratoryDate'] = Carbon::createFromTimestamp($report['zlaboratoryDate'])->year;
            }
            // Group By Year Data
            $groupByYear = collect($reports)->groupBy('zlaboratoryDate');
            // Group By Manufacturer
            foreach($groupByYear as $year => $collectYear)
            {
                $groupByYear[$year] = $collectYear->groupBy(function($item){
                    return $item['zManufacturer.zsym'];
                })->sortKeys();
            }

            $endYear = Carbon::createFromTimestamp($currentYear)->year;
            $startYear = Carbon::createFromTimestamp($prevYear)->year;
            // add index year to collection $groupByYear and add each manufacturer when query not found
            for($i = $startYear; $i <= $endYear; $i++)
            {
                if(!$groupByYear->has($i))
                {
                    $groupByYear[$i] = collect();
                }
                foreach($groupByYear as $year => $collectManufacturer)
                {
                    foreach($collectManufacturer as $manufacturerName => $manufacturerCollect)
                    {
                        if( !$groupByYear[$i]->has($manufacturerName) )
                        {
                            $groupByYear[$i][$manufacturerName] = collect([ $this->addDefaultValueCurrentTransformer2() ]);
                        }
                    }
                }
            }
            // sort by year
            $groupByYear = $groupByYear->sortKeys();
            // Final data format according to excel file
            $dataFormated = [];
            foreach($groupByYear as $year => $collect)
            {
                $dataConvert[$year] = [];
                foreach($collect as $manufacturerName => $manufacturerCollect)
                {
                    $manufacturerData = [
                        'dc_resistor_achieved' => 0,
                        'dc_resistor_not_achieved' => 0,
                        'insulation_achieved' => 0,
                        'insulation_not_achieved' => 0,
                        'ratio_measurement_achieved' => 0,
                        'ratio_measurement_not_achieved' => 0,
                        'swing_high_achieved' => 0,
                        'swing_high_not_achieved' => 0,
                        'magnetization_properties_achieved' => 0,
                        'magnetization_properties_not_achieved' => 0,
                        'other_achieved' => 0,
                        'other_not_achieved' => 0,
                        'total_achieved' => 0,
                        'total_not_achieved' => 0
                    ];

                    // length of the collection when there was no maker that year. the value marked above and has a length of 7
                    if(count($manufacturerCollect[0]) > 7)
                    {
                        $arrayKeysCheck = ['dc_resistor', 'insulation', 'ratio_measurement', 'swing_high', 'magnetization_properties'];
                        // check each record by 1 to pass the "Other" exclusion
                        foreach($manufacturerCollect as $item)
                        {
                            foreach($arrayKeysCheck as $key)
                            {
                                if($item[$key] == 1){
                                    $manufacturerData["{$key}_achieved"] += 1;
                                    $manufacturerData['total_achieved'] += 1;
                                }else{
                                    $manufacturerData["{$key}_not_achieved"] += 1;
                                    $manufacturerData['total_not_achieved'] += 1;
                                }
                            }

                            // Other Attr
                            $manufacturerData['other_achieved'] += $item['other_achieved'];
                            $manufacturerData['other_not_achieved'] += $item['other_not_achieved'];

                            // Total achieved and total not achieved
                            $manufacturerData['total_achieved'] += $item['other_achieved'];
                            $manufacturerData['total_not_achieved'] += $item['other_not_achieved'];
                        }
                    }
                    $dataConvert[$year] += [
                        $manufacturerName => $manufacturerData
                    ];
                }
                // add dataConvert keeping the same key as $year
                $dataFormated += $dataConvert;
            }
            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);

            $listYear = [$startYear, $endYear];
            if(count($listYear) > 0)
            {
                $listManufacturer = array_keys($dataFormated[$listYear[0]]);
            }else{
                $listManufacturer = [];
            }

            // count the number of years to report
            $count = count($listManufacturer);
            // Read Template Excel
            $template = storage_path("templates_excel/phan_xuong_co_dien/bao-cao-thi-nghiem-nhat-thu/may-bien-dong-dien/bao-cao-hang-muc-thi-nghiem-may-bien-dong-dien-khong-dat-theo-hang-san-xuat-{$count}.xlsx");
            $spreadsheet = $reader->load($template);
            // Config style for sheet
            $style = [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ];

            // Set Style for Sheet
            $spreadsheet->getDefaultStyle()->applyFromArray($style);

            $sheetThongKe = $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
            $sheetDataBD = $spreadsheet->setActiveSheetIndexByName('DATA_BD');

             // get array year in array format fill to sheet
             $listYear = array_keys($dataFormated);
             $sheetThongKe->getCell('E7')->setValue($listYear[0]);
             $sheetThongKe->getCell('I7')->setValue($listYear[0]);
             $sheetThongKe->getCell('G7')->setValue($listYear[1]);
             $sheetThongKe->getCell('K7')->setValue($listYear[1]);

             $sheetDataBD->getCell('C3')->setValue("BÁO CÁO SO SÁNH GIỮA CÁC HẠNG MỤC THÍ NGHIỆM MÁY BIẾN DÒNG ĐIỆN KHÔNG ĐẠT TỪ NĂM {$listYear[0]} - {$listYear[1]}");
             $sheetThongKe->getCell('C3')->setValue("BÁO CÁO SO SÁNH KẾT QUẢ CÁC HẠNG MỤC THÍ NGHIỆM CỦA MÁY BIẾN DÒNG ĐIỆN THEO TỪNG HÃNG SẢN XUẤT");
             $spreadsheet->setActiveSheetIndexByName("BIEU_DO")->getCell('C3')->setValue("BIỂU ĐỒ SO SÁNH NGUYÊN NHÂN MÁY BIẾN DÒNG KHÔNG ĐẠT CỦA CÁC HÃNG SẢN XUẤT GIỮA CÁC NĂM");

            $indexManufacturer = 0;
            // dynamic chart title when reporting by year sheet "BIEU_DO_MOI_NAM"
            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $chartNames = $worksheet->getChartNames();
                // Exclude chart 1 in sheet BIEU_DO
                foreach($chartNames as $chartName)
                {
                    if(count($listManufacturer) > 0)
                    {
                        $chart = $worksheet->getChartByName($chartName);
                        $titleChar = new \PhpOffice\PhpSpreadsheet\Chart\Title("So sánh nguyên nhân máy biến dòng điện hãng {$listManufacturer[$indexManufacturer]} không đạt giữa các năm");
                        $chart->setTitle($titleChar);
                        $indexManufacturer++;
                    }
                }
            }

            // Write data to sheet 'THONG_KE_DU_LIEU' Cutting Machine Category Comparison
            $this->writeDataSheetCuttingTranferMachineCategoryComparison($dataFormated, $sheetThongKe);

            // Write data to sheet 'DATA_BD' Cutting Machine Category Comparison
            $this->writeChartSheetCuttingTranferMachineCategoryComparison($dataFormated, $sheetDataBD);
            $this->fillSearchDataShareReport($sheetThongKe, $request);
            $sheetBD = $spreadsheet->setActiveSheetIndexByName('BIEU_DO');
            $sheetBD->getStyle('A1:Z1000')->applyFromArray([
                'font' => [
                    'name' => 'Times New Roman'
                ]
            ]);
            $title = 'bao-cao-cac-hang-muc-thi-nghiem-may-bien-dong-dien-khong-dat-theo-tung-hang-san-xuat-'.time().'.xlsx';
            $url = writeExcel($spreadsheet, $title);
            return response()->json([
                'success' => true,
                'url' => $url,
            ]);

        } catch (\Exception $e) {
            Log::error('[Electromechanical] Cutting tranfer machines report of failed test items by manufacturer: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));

            return response()->json([
                'error' => [$e->getMessage()]
            ]);
        }
    }

    private function writeDataSheetCuttingTranferMachineCategoryComparison($data, $spreadsheet)
    {
        if(count($data) == 0)
        {
            return;
        }
        // Flowing template excel
        $startIndexSheet = $endIndexSheet = 9;
        $listYear = array_keys($data);
        $listManufacturer = array_keys($data[$listYear[0]]);

        foreach($listManufacturer as $manufacturer)
        {
            // Calculate the number of ending lines to merge cell
            $endIndexSheet = $startIndexSheet + 4;
            // Merge cell 7 column
            $spreadsheet->mergeCells("C{$startIndexSheet}:C{$endIndexSheet}")
            ->getCell("C{$startIndexSheet}")
            ->setValue($manufacturer);

            // column order to write data
            $indexColumn = $startIndexSheet;

            // There are 7 attributes that need statistics
            for($i = 1; $i < 6; $i++)
            {
                $infoCase = $this->hanldeWriteDataSheetCuttingTranferMachineCategoryComparison($i);

                $title = $infoCase['title'];
                $attrAchieved = $infoCase['attrAchieved'];
                $attrNotAchieved = $infoCase['attrNotAchieved'];

                $totalPrevYear = $data[$listYear[0]][$manufacturer][$attrAchieved] + $data[$listYear[0]][$manufacturer][$attrNotAchieved];
                $totalCurrentYear = $data[$listYear[1]][$manufacturer][$attrAchieved] + $data[$listYear[1]][$manufacturer][$attrNotAchieved];

                $spreadsheet->getCell("D{$indexColumn}")
                    ->setValue($title);
                $spreadsheet->getCell("E{$indexColumn}")
                    ->setValue($data[$listYear[0]][$manufacturer][$attrAchieved]);
                $spreadsheet->getCell("F{$indexColumn}")
                    ->setValue($data[$listYear[0]][$manufacturer][$attrNotAchieved]);
                $spreadsheet->getCell("G{$indexColumn}")
                    ->setValue($data[$listYear[1]][$manufacturer][$attrAchieved]);
                $spreadsheet->getCell("H{$indexColumn}")
                    ->setValue($data[$listYear[1]][$manufacturer][$attrNotAchieved]);

                if($totalPrevYear > 0 )
                {
                    $spreadsheet->getCell("I{$indexColumn}")
                    ->setValue(round($data[$listYear[0]][$manufacturer][$attrAchieved] / $totalPrevYear * 100, 2));

                    $spreadsheet->getCell("J{$indexColumn}")
                    ->setValue(round($data[$listYear[0]][$manufacturer][$attrNotAchieved] / $totalPrevYear * 100, 2));


                }else{
                    $spreadsheet->getCell("I{$indexColumn}")
                    ->setValue(0);
                    $spreadsheet->getCell("J{$indexColumn}")
                    ->setValue(0);
                }

                if($totalCurrentYear > 0)
                {
                    $spreadsheet->getCell("K{$indexColumn}")
                    ->setValue(round($data[$listYear[1]][$manufacturer][$attrAchieved] / $totalCurrentYear * 100, 2));
                    $spreadsheet->getCell("L{$indexColumn}")
                    ->setValue(round($data[$listYear[1]][$manufacturer][$attrNotAchieved] / $totalCurrentYear * 100, 2));
                }else{
                    $spreadsheet->getCell("K{$indexColumn}")
                    ->setValue(0);
                    $spreadsheet->getCell("L{$indexColumn}")
                    ->setValue(0);
                }

                $indexColumn++;

            }

            // There are 7 statistical properties/year so merge cell
            $startIndexSheet += 5;
        }
    }

    private function hanldeWriteDataSheetCuttingTranferMachineCategoryComparison($index)
    {
        $data = [];
        switch ($index) {
            case 1:
                $data = [
                    'title' => 'Điện trở một chiều',
                    'attrAchieved' => 'dc_resistor_achieved',
                    'attrNotAchieved' => 'dc_resistor_not_achieved',
                ];
                break;
            case 2:
                $data = [
                    'title' => 'Điện trở cách điện',
                    'attrAchieved' => 'insulation_achieved',
                    'attrNotAchieved' => 'insulation_not_achieved',
                ];
                break;
            case 3:
                $data = [
                    'title' => 'Tỷ số biến',
                    'attrAchieved' => 'ratio_measurement_achieved',
                    'attrNotAchieved' => 'ratio_measurement_not_achieved',
                ];
                break;
            case 4:
                $data = [
                    'title' => 'Thử điện áp xoay chiều tăng cao',
                    'attrAchieved' => 'swing_high_achieved',
                    'attrNotAchieved' => 'swing_high_not_achieved',
                ];
                break;
            case 5:
                $data = [
                    'title' => 'Đặc tính từ hóa',
                    'attrAchieved' => 'magnetization_properties_achieved',
                    'attrNotAchieved' => 'magnetization_properties_not_achieved',
                ];
                break;
            default:
                break;
        }
        return $data;
    }

    private function writeChartSheetCuttingTranferMachineCategoryComparison($data, $spreadsheet)
    {

        if(count($data) == 0)
        {
            return;
        }

        $startIndexSheet = 6;

        $listYear = array_keys($data);
        $listManufacturer = array_keys($data[$listYear[0]]);

        foreach($listManufacturer as $manufacturer)
        {
            for($i = 0; $i < 4; $i++)
            {
                switch ($i) {
                    case 0:
                        $spreadsheet->getCell("D{$startIndexSheet}")
                            ->setValue($manufacturer);
                        $startIndexSheet++;
                        break;
                    case 1:
                        $spreadsheet->getCell("D{$startIndexSheet}")
                            ->setValue("Hạng mục");
                        $spreadsheet->getCell("E{$startIndexSheet}")
                            ->setValue("Điện trở cách điện");
                        $spreadsheet->getCell("F{$startIndexSheet}")
                            ->setValue("Đặc tính từ hóa");
                        $spreadsheet->getCell("G{$startIndexSheet}")
                            ->setValue("Thử điện áp xoay chiều tăng cao tần số công nghiệp");
                        $spreadsheet->getCell("H{$startIndexSheet}")
                            ->setValue("Điện trở một chiều");
                        $spreadsheet->getCell("I{$startIndexSheet}")
                            ->setValue("Tỷ số biến");
                        $spreadsheet->getCell("J{$startIndexSheet}")
                            ->setValue("Khác");
                        $spreadsheet->getCell("K{$startIndexSheet}")
                            ->setValue("Tổng");
                        $startIndexSheet++;
                        break;
                    case 2;
                        $spreadsheet->getCell("D{$startIndexSheet}")
                            ->setValue("Năm {$listYear[0]}");
                        $spreadsheet->getCell("E{$startIndexSheet}")
                            ->setValue($data[$listYear[0]][$manufacturer]['insulation_not_achieved']);
                        $spreadsheet->getCell("F{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['magnetization_properties_not_achieved']);
                        $spreadsheet->getCell("G{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['swing_high_not_achieved']);
                        $spreadsheet->getCell("H{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['dc_resistor_not_achieved']);
                        $spreadsheet->getCell("I{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['ratio_measurement_not_achieved']);
                        $spreadsheet->getCell("J{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['other_not_achieved']);
                        $spreadsheet->getCell("K{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['total_not_achieved']);
                        $startIndexSheet++;
                        break;
                    case 3;
                        $spreadsheet->getCell("D{$startIndexSheet}")
                            ->setValue("Năm {$listYear[1]}");
                        $spreadsheet->getCell("E{$startIndexSheet}")
                            ->setValue($data[$listYear[1]][$manufacturer]['insulation_not_achieved']);
                        $spreadsheet->getCell("F{$startIndexSheet}")
                            ->setValue($data[$listYear[1]][$manufacturer]['magnetization_properties_not_achieved']);
                        $spreadsheet->getCell("G{$startIndexSheet}")
                            ->setValue($data[$listYear[1]][$manufacturer]['swing_high_not_achieved']);
                        $spreadsheet->getCell("H{$startIndexSheet}")
                            ->setValue($data[$listYear[1]][$manufacturer]['dc_resistor_not_achieved']);
                        $spreadsheet->getCell("I{$startIndexSheet}")
                            ->setValue($data[$listYear[1]][$manufacturer]['ratio_measurement_not_achieved']);
                        $spreadsheet->getCell("J{$startIndexSheet}")
                            ->setValue($data[$listYear[1]][$manufacturer]['other_not_achieved']);
                        $spreadsheet->getCell("K{$startIndexSheet}")
                            ->setValue($data[$listYear[1]][$manufacturer]['total_not_achieved']);
                        $startIndexSheet++;
                        break;
                    default:
                        break;
                }
            }
        }
    }

    private function addDefaultValueCurrentTransformer2()
    {
        return [
            // Điện trở 1 chiều
            'dc_resistor' => 0,
            // Điện trở cách điện
            'insulation' => 0,
            // Tỷ số biến
            'ratio_measurement' => 0,
            // Thử điện áp xoay chiều tăng cao tần số công nghiệp
            'swing_high' => 0,
            // Đặc tính từ hóa
            'magnetization_properties' => 0,
            // Hạng mục khác
            'other_achieved' => 0,
            'other_not_achieved' => 0
        ];
    }

    private function getAttributeExternalCheckCurrentTransformerByType($type)
    {
        $attributes = [];
        switch($type){
            case '9.1. BBTN Máy biến dòng điện 3 cuộn 3 tỷ số':
                $attributes = [
                    'z16spichk',
                    'z18clochk',
                    'z20cgcchk',
                    'z22ccsbchk',
                    'z24csbchk',
                ];
                break;
            case '9.2. BBTN Máy biến dòng điện 5 cuộn 5 tỷ số':
                $attributes = [
                    'z16check',
                    'z17check',
                    'z18check',
                    'z19check',
                    'z20check',
                ];
                break;
            default:
                break;
        }

        return $attributes;
    }

    private function getAttributeInsulationCurrentTransformerByType($type)
    {
        $attributes = [];
        switch($type){
            case '9.1. BBTN Máy biến dòng điện 3 cuộn 3 tỷ số':
                $attributes = [
                    'z26irmchk',
                ];
                break;
            case '9.2. BBTN Máy biến dòng điện 5 cuộn 5 tỷ số':
                $attributes = [
                    'z21check',
                ];
                break;
            default:
                break;
        }

        return $attributes;
    }

    private function getAttributeDCResistanceCurrentTransformerByType($type)
    {
        $attributes = [];
        switch($type){
            case '9.1. BBTN Máy biến dòng điện 3 cuộn 3 tỷ số':
                $attributes = [
                    'z70dcwrmchk',
                ];
                break;
            case '9.2. BBTN Máy biến dòng điện 5 cuộn 5 tỷ số':
                $attributes = [
                    'z79check',
                ];
                break;
            default:
                break;
        }

        return $attributes;
    }

    private function getAttributeRatioMeasurementCurrentTransformerByType($type)
    {
        $attributes = [];
        switch($type){
            case '9.1. BBTN Máy biến dòng điện 3 cuộn 3 tỷ số':
                $attributes = [
                    'z58rmchk',
                ];
                break;
            case '9.2. BBTN Máy biến dòng điện 5 cuộn 5 tỷ số':
                $attributes = [
                    'z53check',
                ];
                break;
            default:
                break;
        }

        return $attributes;
    }
    private function attributeSwingHighCurrentTransformerByType($type)
    {
        $attributes = [];
        switch($type){
            case '9.1. BBTN Máy biến dòng điện 3 cuộn 3 tỷ số':
                $attributes = [
                    'z82achvwtchk',
                ];
                break;
            case '9.2. BBTN Máy biến dòng điện 5 cuộn 5 tỷ số':
                $attributes = [
                    'z105check',
                ];
                break;
            default:
                break;
        }

        return $attributes;
    }

    private function getAttributeMagnetizationPropertiesCurrentTransformerByType($type)
    {
        $attributes = [];
        switch($type){
            case '9.1. BBTN Máy biến dòng điện 3 cuộn 3 tỷ số':
                $attributes = [
                    'z44ecmchk'
                ];
                break;
            case '9.2. BBTN Máy biến dòng điện 5 cuộn 5 tỷ số':
                $attributes = [
                    'z39check'
                ];
                break;
            default:
                break;
        }

        return $attributes;
    }

    private function getAttributeOtherCurrentTransformerByType($type)
    {
        $attributes = [];
        switch($type){
            case '9.1. BBTN Máy biến dòng điện 3 cuộn 3 tỷ số':
                $attributes = [
                    'z37cddfmchk',
                    'z47vtmchk',
                    'z88tachk',
                    'z353dacucchk',
                    'z369litchk',
                    'z371ddtnchk',
                    'z382pdmchk',
                    'zniemphong_chk'
                ];
                break;
            case '9.2. BBTN Máy biến dòng điện 5 cuộn 5 tỷ số':
                $attributes = [
                    'z34check',
                    'z40check',
                    'z127check',
                    'z863check',
                    'z884check',
                    'z900check',
                    'z909check',
                    'zniemphong_chk'
                ];
                break;
            default:
                break;
        }

        return $attributes;
    }

    private function getAttributeCurrentTransformerByType($type)
    {
        return array_merge(
            $this->getAttributeExternalCheckCurrentTransformerByType($type),
            $this->getAttributeInsulationCurrentTransformerByType($type),
            $this->getAttributeDCResistanceCurrentTransformerByType($type),
            $this->getAttributeRatioMeasurementCurrentTransformerByType($type),
            $this->attributeSwingHighCurrentTransformerByType($type),
            $this->getAttributeMagnetizationPropertiesCurrentTransformerByType($type),
            $this->getAttributeOtherCurrentTransformerByType($type),
        );
    }

    // Attribute Capble Repport
    private function getAllAttributeCapbleByType($type)
    {
        return array_merge(
            $this->getAtributeCapbleInsulation(),
            $this->getAttributeSwingHighCapbleByType($type),
            $this->getAttributeExternalCheckCapble(),
            $this->getAttributeOtherCapbleByType($type)
        );
    }
    private function getAtributeCapbleInsulation()
    {
        return [
            'z22irchk',
        ];
    }
    private function getAttributeSwingHighCapbleByType($type)
    {
        $attr = [];
        switch ($type) {
            case '2.1. BBTN Cáp lực 1 lõi':
                $attr = [
                    'z38highchk'
                ];
                break;
            case '2.2. BBTN Cáp lực 3 lõi':
                $attr = [
                    'z52techk'
                ];
                break;
            default:
                break;
        }
        return $attr;
    }
    private function getAttributeExternalCheckCapble()
    {
        return  [
            'z19terminalchk',
            'z20cccchk',
            'z21scchk',
        ];
    }

    private function getAttributeOtherCapbleByType($type)
    {
        $attr = [];
        switch ($type) {
            case '2.1. BBTN Cáp lực 1 lõi':
                $attr = [
                    'z28dchighchk',
                    'z45meachk',
                    'z61parchk',
                    'z64locchk',
                    'z67mechk',
                ];
                break;
            case '2.2. BBTN Cáp lực 3 lõi':
                $attr = [
                    'z31highchk',
                    'z67mechk',
                    'z115dochk',
                    'z118malchk',
                    'z121dauchk',
                ];
                break;
            default:
                break;
        }
        return $attr;
    }

    // Capble - Report of failed test items by manufacturer
    public function capbleCategoryComparisonExport(Request $request)
    {
        try {

            $currentYear = $request->year;
            $prevYear = $currentYear - 1;

            if ($currentYear < now()->year) {
                $currentYear = strtotime($currentYear . "-12-31") + 86399;
            } else {
                $currentYear = strtotime(now());
            }
            $prevYear = strtotime($prevYear  . "-01-01");
            $arrayTypes = [
                '2.1. BBTN Cáp lực 1 lõi',
                '2.2. BBTN Cáp lực 3 lõi',
            ];

            foreach ($arrayTypes as $key => $val) {
                if ($key == 0) {
                    $whereClauseBase = "class.type IN (";
                }
                $whereClauseBase .= "'" . $val . "'";
                if ($key < count($arrayTypes) - 1) {
                    $whereClauseBase .= ",";
                } else {
                    $whereClauseBase .= ")";
                }
            }

            if($request->manufacturer)
            {
                foreach ($request->manufacturer as $key => $val) {
                    if ($key == 0) {
                        $whereClauseBase .= " AND zManufacturer.zsym IN (";
                    }
                    $whereClauseBase .= "'". $val ."'";
                    if ($key < count($request->manufacturer) - 1) {
                        $whereClauseBase .= ",";
                    } else {
                        $whereClauseBase .= ")";
                    }
                }
            }else{
                $whereClauseBase .= " AND zManufacturer.zsym IS NOT NULL ";
            }

            $whereClauseBase .= " AND zlaboratoryDate >= " . $prevYear
            . " AND zlaboratoryDate <= " . $currentYear
            . " AND class.zetc_type = 1";

            $attributes = [
                'id',
                'class.type',
                'zlaboratoryDate',
                'name',
                'zManufacturer.zsym'
            ];

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

            // Loop fetch data assoc and hanlde assoc data
            foreach($reports as $index => $report)
            {
                // Set the default value for each item to be counted is 0
                $reports[$index] = array_merge($report, [
                    'insulation' => 0,
                    'swing_high' => 0,
                    'other_achieved' => 0,
                    'other_not_achieved' => 0
                ]);

                // substr, explode, join classtype get name objectType
                $objectType = 'zCA'.join('_', explode('.', substr($report['class.type'], 0, 3)));
                $attributesChild = $this->getAllAttributeCapbleByType($report['class.type']);
                $where = "id = U'". $report['id'] ."'";
                $data = getDataFromService('doSelect', $objectType, $attributesChild, $where, 100);

                // Get each attribute according to the report items
                $attrExternalCheck = $this->getAttributeExternalCheckCapble(); // $countAttr1
                $attributeInsulation = $this->getAtributeCapbleInsulation();// $countAttr2
                $attributeSwingHigh = $this->getAttributeSwingHighCapbleByType($report['class.type']);
                $attributeOther = $this->getAttributeOtherCapbleByType($report['class.type']);

                if($this->arrayKeyExistArray($data, $attrExternalCheck))
                {
                    $reports[$index]['other_achieved'] += 1;
                }else{
                    $reports[$index]['other_not_achieved'] += 1;
                }

                if($this->arrayKeyExistArray($data, $attributeInsulation))
                {
                    $reports[$index]['insulation'] = 1;
                }

                if($this->arrayKeyExistArray($data, $attributeSwingHigh))
                {
                    $reports[$index]['swing_high'] = 1;
                }
                if(count($data) == 0)
                {
                    // 1 is the plus of the external test item
                    $reports[$index]['other_not_achieved'] = count($attributeOther) + 1;
                }else{
                    foreach($data as $key => $value)
                    {
                        // attributeOther
                        foreach($attributeOther as $attr)
                        {
                            if(array_key_exists($attr, $value) && $value[$attr] == "1")
                            {
                                $reports[$index]['other_achieved'] += 1;
                            }else{
                                $reports[$index]['other_not_achieved'] += 1;
                            }
                        }
                    }
                }
                // convert timestamp to year
                $reports[$index]['zlaboratoryDate'] = Carbon::createFromTimestamp($report['zlaboratoryDate'])->year;
            }

            // Group By Year Data
            $groupByYear = collect($reports)->groupBy('zlaboratoryDate');

            // Group By Manufacturer
            foreach($groupByYear as $year => $collectYear)
            {
                $groupByYear[$year] = $collectYear->groupBy(function($item){
                    return $item['zManufacturer.zsym'];
                })->sortKeys();
            }

            $endYear = Carbon::createFromTimestamp($currentYear)->year;
            $startYear = Carbon::createFromTimestamp($prevYear)->year;

            // add index year to collection $groupByYear and add each manufacturer when query not found
            for($i = $startYear; $i <= $endYear; $i++)
            {
                if(!$groupByYear->has($i))
                {
                    $groupByYear[$i] = collect();
                }
                foreach($groupByYear as $year => $collectManufacturer)
                {

                    foreach($collectManufacturer as $manufacturerName => $manufacturerCollect)
                    {
                        if( !$groupByYear[$i]->has($manufacturerName) )
                        {
                            $groupByYear[$i][$manufacturerName] = collect([[
                                "insulation" => 0,
                                "swing_high" => 0,
                                "other_achieved" => 0,
                                "other_not_achieved" => 0,
                            ]]);
                        }
                    }
                }
            }

            // sort by year
            $groupByYear = $groupByYear->sortKeys();

            // Final data format according to excel file
            $dataFormated = [];
            foreach($groupByYear as $year => $collect)
            {
                $dataConvert[$year] = [];
                foreach($collect as $manufacturerName => $manufacturerCollect)
                {
                    $manufacturerData = [
                        'insulation_achieved' => 0,
                        'insulation_not_achieved' => 0,
                        'swing_high_achieved' => 0,
                        'swing_high_not_achieved' => 0,
                        'other_achieved' => 0,
                        'other_not_achieved' => 0,
                        'total_achieved' => 0,
                        'total_not_achieved' => 0
                    ];
                    // length of the collection when there was no maker that year. the value marked above and has a length of 9
                    if(count($manufacturerCollect[0]) > 4)
                    {
                        // check each record by 1 to pass the "Other" exclusion
                        foreach($manufacturerCollect as $item)
                        {
                            if($item['insulation'] == 1)
                            {
                                $manufacturerData['insulation_achieved'] += 1;
                                $manufacturerData['total_achieved'] += 1;

                            }else{
                                $manufacturerData['insulation_not_achieved'] += 1;
                                $manufacturerData['total_not_achieved'] += 1;
                            }

                            if($item['swing_high'] == 1)
                            {
                                $manufacturerData['swing_high_achieved'] += 1;
                                $manufacturerData['total_achieved'] += 1;

                            }else{
                                $manufacturerData['swing_high_not_achieved'] += 1;
                                $manufacturerData['total_not_achieved'] += 1;
                            }
                            // Other Attr
                            $manufacturerData['other_achieved'] += $item['other_achieved'];
                            $manufacturerData['other_not_achieved'] += $item['other_not_achieved'];

                            // Total achieved and total not achieved
                            $manufacturerData['total_achieved'] += $item['other_achieved'];
                            $manufacturerData['total_not_achieved'] += $item['other_not_achieved'];
                        }
                    }
                    $dataConvert[$year] += [
                        $manufacturerName => $manufacturerData
                    ];

                }
                // add dataConvert keeping the same key as $year
                $dataFormated += $dataConvert;

            }

            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);

            $listYear = [$startYear, $endYear];
            if(count($listYear) > 0)
            {
                $listManufacturer = array_keys($dataFormated[$listYear[0]]);
            }else{
                $listManufacturer = [];
            }

            // count the number of years to report
            $count = count($listManufacturer);
            // Read Template Excel
            $template = storage_path("templates_excel/phan_xuong_co_dien/bao-cao-thi-nghiem-nhat-thu/cap-luc/bao-cao-hang-muc-thi-nghiem-cap-luc-khong-dat-theo-hang-san-xuat-{$count}.xlsx");
            $spreadsheet = $reader->load($template);
            // Config style for sheet
            $style = [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ];

            // Set Style for Sheet
            $spreadsheet->getDefaultStyle()->applyFromArray($style);

            $sheetThongKe = $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
            $sheetDataBD = $spreadsheet->setActiveSheetIndexByName('DATA_BD');

            // get array year in array format fill to sheet
            $listYear = array_keys($dataFormated);
            $sheetThongKe->getCell('E7')->setValue($listYear[0]);
            $sheetThongKe->getCell('I7')->setValue($listYear[0]);
            $sheetThongKe->getCell('G7')->setValue($listYear[1]);
            $sheetThongKe->getCell('K7')->setValue($listYear[1]);

            $sheetDataBD->getCell('D3')->setValue("BÁO CÁO SO SÁNH GIỮA CÁC HẠNG MỤC THÍ NGHIỆM MÁY CÁP LỰC KHÔNG ĐẠT TỪ NĂM {$listYear[0]} - {$listYear[1]}");
            $sheetThongKe->getCell('C3')->setValue("BÁO CÁO SO SÁNH KẾT QUẢ CÁC HẠNG MỤC THÍ NGHIỆM CỦA CÁP LỰC THEO TỪNG HÃNG SẢN XUẤT");
            $spreadsheet->setActiveSheetIndexByName("BIEU_DO")->getCell('C3')->setValue("BIỂU ĐỒ SO SÁNH NGUYÊN NHÂN CÁP LỰC KHÔNG ĐẠT CỦA CÁC HÃNG SẢN XUẤT GIỮA CÁC NĂM");

            $indexManufacturer = 0;
            // dynamic chart title when reporting by year sheet "BIEU_DO_MOI_NAM"
            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $chartNames = $worksheet->getChartNames();
                // Exclude chart 1 in sheet BIEU_DO
                foreach($chartNames as $chartName)
                {
                    if(count($listManufacturer) > 0)
                    {
                        $chart = $worksheet->getChartByName($chartName);
                        $titleChar = new \PhpOffice\PhpSpreadsheet\Chart\Title("So sánh nguyên nhân cáp lực hãng {$listManufacturer[$indexManufacturer]} không đạt giữa các năm");
                        $chart->setTitle($titleChar);
                        $indexManufacturer++;
                    }
                }
            }

            // Write data to sheet 'THONG_KE_DU_LIEU' Cutting Machine Category Comparison
            $this->writeDataSheetCapbleCategoryComparison($dataFormated, $sheetThongKe);

            // Write data to sheet 'DATA_BD' Cutting Machine Category Comparison
            $this->writeChartSheetCapbleCategoryComparison($dataFormated, $sheetDataBD);
            $this->fillSearchDataShareReport($sheetThongKe, $request);
            // Set active sheet BIEU_DO when view file
            $spreadsheet->setActiveSheetIndexByName("BIEU_DO");

            $title = 'bao-cao-cac-hang-muc-thi-nghiem-cap-luc-khong-dat-theo-tung-hang-san-xuat-'.time().'.xlsx';
            $url = writeExcel($spreadsheet, $title);
            return response()->json([
                'success' => true,
                'url' => $url,
            ]);


        } catch (\Exception $e) {
            Log::error('[Electromechanical] Cutting machines report of failed test items by manufacturer: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));

            return response()->json([
                'error' => [$e->getMessage()]
            ]);
        }
    }

    private function writeDataSheetCapbleCategoryComparison($data, $spreadsheet)
    {
        if(count($data) == 0)
        {
            return;
        }
        // Flowing template excel
        $startIndexSheet = $endIndexSheet = 9;
        $listYear = array_keys($data);
        $listManufacturer = array_keys($data[$listYear[0]]);

        foreach($listManufacturer as $manufacturer)
        {
            // Calculate the number of ending lines to merge cell
            $endIndexSheet = $startIndexSheet + 1;
            // Merge cell 7 column
            $spreadsheet->mergeCells("C{$startIndexSheet}:C{$endIndexSheet}")
            ->getCell("C{$startIndexSheet}")
            ->setValue($manufacturer);

            // column order to write data
            $indexColumn = $startIndexSheet;

            // There are 7 attributes that need statistics
            for($i = 1; $i < 3; $i++)
            {
                $infoCase = $this->hanldeWriteDataSheetCapbleCategoryComparison($i);

                $title = $infoCase['title'];
                $attrAchieved = $infoCase['attrAchieved'];
                $attrNotAchieved = $infoCase['attrNotAchieved'];

                $totalPrevYear = $data[$listYear[0]][$manufacturer][$attrAchieved] + $data[$listYear[0]][$manufacturer][$attrNotAchieved];
                $totalCurrentYear = $data[$listYear[1]][$manufacturer][$attrAchieved] + $data[$listYear[1]][$manufacturer][$attrNotAchieved];

                $spreadsheet->getCell("D{$indexColumn}")
                    ->setValue($title);
                $spreadsheet->getCell("E{$indexColumn}")
                    ->setValue($data[$listYear[0]][$manufacturer][$attrAchieved]);
                $spreadsheet->getCell("F{$indexColumn}")
                    ->setValue($data[$listYear[0]][$manufacturer][$attrNotAchieved]);
                $spreadsheet->getCell("G{$indexColumn}")
                    ->setValue($data[$listYear[1]][$manufacturer][$attrAchieved]);
                $spreadsheet->getCell("H{$indexColumn}")
                    ->setValue($data[$listYear[1]][$manufacturer][$attrNotAchieved]);

                if($totalPrevYear > 0)
                {
                    $spreadsheet->getCell("I{$indexColumn}")
                    ->setValue(round($data[$listYear[0]][$manufacturer][$attrAchieved] / $totalPrevYear * 100, 2));

                    $spreadsheet->getCell("J{$indexColumn}")
                    ->setValue(round($data[$listYear[0]][$manufacturer][$attrNotAchieved] / $totalPrevYear * 100, 2));


                }else{
                    $spreadsheet->getCell("I{$indexColumn}")
                    ->setValue(0);
                    $spreadsheet->getCell("J{$indexColumn}")
                    ->setValue(0);
                }

                if($totalCurrentYear > 0)
                {
                    $spreadsheet->getCell("K{$indexColumn}")
                    ->setValue(round($data[$listYear[1]][$manufacturer][$attrAchieved] / $totalCurrentYear * 100, 2));
                    $spreadsheet->getCell("L{$indexColumn}")
                    ->setValue(round($data[$listYear[1]][$manufacturer][$attrNotAchieved] / $totalCurrentYear * 100, 2));
                }else{
                    $spreadsheet->getCell("K{$indexColumn}")
                    ->setValue(0);
                    $spreadsheet->getCell("L{$indexColumn}")
                    ->setValue(0);
                }

                $indexColumn++;

            }

            // There are 7 statistical properties/year so merge cell
            $startIndexSheet += 2;
        }
    }

    private function hanldeWriteDataSheetCapbleCategoryComparison($index)
    {
        $data = [];
        switch ($index) {
            case 1:
                $data = [
                    'title' => 'Điện trở cách điện',
                    'attrAchieved' => 'insulation_achieved',
                    'attrNotAchieved' => 'insulation_not_achieved',
                ];
                break;
            case 2:
                $data = [
                    'title' => 'Thử điện áp xoay chiều tăng cao tần số công nghiệp',
                    'attrAchieved' => 'swing_high_achieved',
                    'attrNotAchieved' => 'swing_high_not_achieved',
                ];
                break;
            default:
                break;
        }
        return $data;
    }

    private function writeChartSheetCapbleCategoryComparison($data, $spreadsheet)
    {

        if(count($data) == 0)
        {
            return;
        }

        $startIndexSheet = 6;

        $listYear = array_keys($data);
        $listManufacturer = array_keys($data[$listYear[0]]);

        foreach($listManufacturer as $manufacturer)
        {
            for($i = 0; $i < 4; $i++)
            {
                switch ($i) {
                    case 0:
                        $spreadsheet->getCell("D{$startIndexSheet}")
                            ->setValue($manufacturer);
                        $startIndexSheet++;
                        break;
                    case 1:
                        $spreadsheet->getCell("D{$startIndexSheet}")
                            ->setValue("Hạng mục");
                        $spreadsheet->getCell("E{$startIndexSheet}")
                            ->setValue("Điện trở cách điện");
                        $spreadsheet->getCell("F{$startIndexSheet}")
                            ->setValue("Thử điện áp xoay chiều tăng cao tần số công nghiệp");
                        $spreadsheet->getCell("G{$startIndexSheet}")
                            ->setValue("Khác");
                        $spreadsheet->getCell("H{$startIndexSheet}")
                            ->setValue("Tổng");
                        $startIndexSheet++;
                        break;
                    case 2;
                        $spreadsheet->getCell("D{$startIndexSheet}")
                            ->setValue("Năm {$listYear[0]}");
                        $spreadsheet->getCell("E{$startIndexSheet}")
                            ->setValue($data[$listYear[0]][$manufacturer]['insulation_not_achieved']);
                        $spreadsheet->getCell("F{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['swing_high_not_achieved']);
                        $spreadsheet->getCell("G{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['other_not_achieved']);
                        $spreadsheet->getCell("H{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['total_not_achieved']);
                        $startIndexSheet++;
                        break;
                    case 3;
                        $spreadsheet->getCell("D{$startIndexSheet}")
                            ->setValue("Năm {$listYear[1]}");
                        $spreadsheet->getCell("E{$startIndexSheet}")
                            ->setValue($data[$listYear[1]][$manufacturer]['insulation_not_achieved']);
                        $spreadsheet->getCell("F{$startIndexSheet}")
                                ->setValue($data[$listYear[1]][$manufacturer]['swing_high_not_achieved']);
                        $spreadsheet->getCell("G{$startIndexSheet}")
                                ->setValue($data[$listYear[1]][$manufacturer]['other_not_achieved']);
                        $spreadsheet->getCell("H{$startIndexSheet}")
                                ->setValue($data[$listYear[1]][$manufacturer]['total_not_achieved']);
                        $startIndexSheet++;
                        break;
                    default:
                        break;
                }
            }
        }
    }

    private function arrayKeyExistArray(array $array, array $keys)
    {
        $count = 0;
        foreach ($array as $key => $value) {
            foreach($keys as $key){
                if(array_key_exists($key, $value))
                {
                    $count++;
                }
            }
        }

        return count($keys) == $count;
    }

        /**
     * export report 31
     * @return Renderable
     */
    public function distributionTransformerExport(Request $request)
    {
        try {
            $currentYear = $request->year;
            $prevYear = $currentYear - 1;
            if ($currentYear < now()->year) {
                $currentYear = strtotime($currentYear . "-12-31") + 86399;
            } else {
                $currentYear = strtotime(now());
            }
            $prevYear = strtotime($prevYear  . "-01-01");
            $arrayTypes = [
                '13.8. BBTN Máy biến áp phân phối 1 pha',
                '13.9. BBTN Máy biến áp phân phối 3 pha',
            ];
            $whereClauseBase = '';
            foreach ($arrayTypes as $key => $val) {
                if ($key == 0) {
                    $whereClauseBase = "class.type IN (";
                }
                $whereClauseBase .= "'" . $val . "'";
                if ($key < count($arrayTypes) - 1) {
                    $whereClauseBase .= ",";
                } else {
                    $whereClauseBase .= ")";
                }
            }
            if($request->manufacturer)
            {
                foreach ($request->manufacturer as $key => $val) {
                    if ($key == 0) {
                        $whereClauseBase .= " AND zManufacturer.zsym IN (";
                    }
                    $whereClauseBase .= "'". $val ."'";
                    if ($key < count($request->manufacturer) - 1) {
                        $whereClauseBase .= ",";
                    } else {
                        $whereClauseBase .= ")";
                    }
                }
            }else{
                $whereClauseBase .= " AND zManufacturer.zsym IS NOT NULL ";
            }
            $whereClauseBase .= " AND zlaboratoryDate >= " . $prevYear
                . " AND zlaboratoryDate <= " . $currentYear
                . " AND class.zetc_type = 1";
            $attributes = [
                'id',
                'class.type',
                'zlaboratoryDate',
                'name',
                'zManufacturer.zsym',
                'class.type',
            ];
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
            // Loop fetch data assoc and hanlde assoc data
            foreach($reports as $index => $report) {
                // Set the default value for each item to be counted is 0
                $reports[$index] = array_merge($report, $this->addDefaultValueCurrentTransformer3());
                // substr, explode, join classtype get name objectType
                $objectType = $this->getObjectTypedistribution($report['class.type']);
                $attributesChild = $this->getAttributeDistribution($report['class.type']);
                $where = "id = U'" . $report['id'] . "'";
                $data = getDataFromService('doSelect', $objectType, $attributesChild, $where, 100);

                $attr1 = $this->getAttributeExternalCheckCurrentDistribution($report['class.type']); // $countAttr1
                $attr2 = $this->getAttributeInsulationCurrentDistribution($report['class.type']);// $countAttr2
                $attr3 = $this->getAttributeDCResistanceCurrentDistribution($report['class.type']);
                $attr4 = $this->getAttributeRatioMeasurementCurrentDistribution($report['class.type']);
                $attr5 = $this->attributeSwingHighCurrentDistribution($report['class.type']);
                $attr6 = $this->attributeOrtherCurrentDistribution($report['class.type']);
                $attr7 = $this->attributeOrther1CurrentDistribution($report['class.type']);

                // number of subcategories in each major category
                $countAttrbute1 = count($attr1);
                $countAttrbute2 = count($attr2);
                $countAttrbute3 = count($attr3);
                $countAttrbute4 = count($attr4);
                $countAttrbute5 = count($attr5);
                $countAttrbute6 = count($attr6);
                $countAttrbute7 = count($attr7);

                // The variable that counts the number of sub-categories that pass so that the larger category can be inferred
                $countAttr1 = $countAttr2 = $countAttr3 = $countAttr4 = $countAttr5 = $countAttr6 = $countAttr7 = 0;
                foreach($data as $key => $value)
                {
                    // Điện trở một chiều
                    foreach($attr1 as $attr)
                    {
                        if(array_key_exists($attr, $value) && $value[$attr] == "1")
                        {
                            $countAttr1++;
                        }
                    }
                    // Điện trở cách điện
                    foreach($attr2 as $attr)
                    {
                        if(array_key_exists($attr, $value) && $value[$attr] == "1")
                        {
                            $countAttr2++;
                        }
                    }
                    // Tỷ số biến
                    foreach($attr3 as $attr)
                    {
                        if(array_key_exists($attr, $value) && $value[$attr] == "1")
                        {
                            $countAttr3++;
                        }
                    }
                    // Tổ đấu dây
                    foreach($attr4 as $attr)
                    {
                        if(array_key_exists($attr, $value) && $value[$attr] == "1")
                        {
                            $countAttr4++;
                        }
                    }
                    // Dòng điện và tổn hao không tải
                    foreach($attr5 as $attr)
                    {
                        if(array_key_exists($attr, $value) && $value[$attr] == "1")
                        {
                            $countAttr5++;
                        }
                    }
                    // Dòng điện và tổn hao không tải
                    foreach($attr7 as $attr)
                    {
                        if(array_key_exists($attr, $value) && $value[$attr] == "1")
                        {
                            $countAttr7++;
                        }
                    }
                    // Khác
                    foreach($attr6 as $attr)
                    {
                        if(array_key_exists($attr, $value) && $value[$attr] == "1")
                        {
                            $reports[$index]['other_achieved'] += 1;
                        }else{
                            $reports[$index]['other_not_achieved'] += 1;
                        }
                    }

                }
                // If the count of each attribute is equal to the length of the attribute array, it passes
                if($countAttrbute1 == $countAttr1)
                {
                    $reports[$index]['dc_resistor'] = 1;
                }

                if($countAttrbute2 == $countAttr2)
                {
                    $reports[$index]['insulation'] = 1;
                }

                if($countAttrbute3 == $countAttr3)
                {
                    $reports[$index]['ratio_measurement'] = 1;
                }

                if($countAttrbute4 == $countAttr4)
                {
                    $reports[$index]['wiring_team'] = 1;
                }

                if($countAttrbute5 == $countAttr5)
                {
                    $reports[$index]['power_line'] = 1;
                }

                if($countAttrbute7 == $countAttr7)
                {
                    $reports[$index]['other_achieved'] += 1;
                }else{
                    $reports[$index]['other_not_achieved'] += 1;
                }
                // convert timestamp to year
                $reports[$index]['zlaboratoryDate'] = Carbon::createFromTimestamp($report['zlaboratoryDate'])->year;
            }
            // Group By Year Data
            $groupByYear = collect($reports)->groupBy('zlaboratoryDate');
            // Group By Manufacturer
            foreach($groupByYear as $year => $collectYear)
            {
                $groupByYear[$year] = $collectYear->groupBy(function($item){
                    return $item['zManufacturer.zsym'];
                })->sortKeys();
            }
            $endYear = Carbon::createFromTimestamp($currentYear)->year;
            $startYear = Carbon::createFromTimestamp($prevYear)->year;

            // add index year to collection $groupByYear and add each manufacturer when query not found
            for($i = $startYear; $i <= $endYear; $i++)
            {
                if(!$groupByYear->has($i))
                {
                    $groupByYear[$i] = collect();
                }
                foreach($groupByYear as $year => $collectManufacturer)
                {

                    foreach($collectManufacturer as $manufacturerName => $manufacturerCollect)
                    {
                        if( !$groupByYear[$i]->has($manufacturerName) )
                        {
                            $groupByYear[$i][$manufacturerName] = collect([[
                                "dc_resistor" => 0,
                                "insulation" => 0,
                                "ratio_measurement" => 0,
                                "wiring_team" => 0,
                                "power_line" => 0,
                                "other_achieved" => 0,
                                "other_not_achieved" => 0,
                            ]]);
                        }
                    }
                }
            }
            // sort by year
            $groupByYear = $groupByYear->sortKeys();

            // Final data format according to excel file
            $dataFormated = [];
            foreach($groupByYear as $year => $collect)
            {
                $dataConvert[$year] = [];
                foreach($collect as $manufacturerName => $manufacturerCollect)
                {
                    $manufacturerData = [
                        'dc_resistor_achieved' => 0,
                        'dc_resistor_not_achieved' => 0,
                        'insulation_achieved' => 0,
                        'insulation_not_achieved' => 0,
                        'ratio_measurement_achieved' => 0,
                        'ratio_measurement_not_achieved' => 0,
                        'wiring_team_achieved' => 0,
                        'wiring_team_not_achieved' => 0,
                        'power_line_achieved' => 0,
                        'power_line_not_achieved' => 0,
                        'other_achieved' => 0,
                        'other_not_achieved' => 0,
                        'total_achieved' => 0,
                        'total_not_achieved' => 0
                    ];
                    // length of the collection when there was no maker that year. the value marked above and has a length of 9
                    if(count($manufacturerCollect[0]) > 9)
                    {
                        // check each record by 1 to pass the "Other" exclusion
                        foreach($manufacturerCollect as $item)
                        {
                            // Điện trở một chiều
                            if($item['dc_resistor'] == 1)
                            {
                                $manufacturerData['dc_resistor_achieved'] += 1;
                                $manufacturerData['total_achieved'] += 1;
                            }else{
                                $manufacturerData['dc_resistor_not_achieved'] += 1;
                                $manufacturerData['total_not_achieved'] += 1;
                            }
                            // Điện trở cách điện
                            if($item['insulation'] == 1)
                            {
                                $manufacturerData['insulation_achieved'] += 1;
                                $manufacturerData['total_achieved'] += 1;
                            }else{
                                $manufacturerData['insulation_not_achieved'] += 1;
                                $manufacturerData['total_not_achieved'] += 1;
                            }
                            // Tỷ số biến
                            if($item['ratio_measurement'] == 1)
                            {
                                $manufacturerData['ratio_measurement_achieved'] += 1;
                                $manufacturerData['total_achieved'] += 1;
                            }else{
                                $manufacturerData['ratio_measurement_not_achieved'] += 1;
                                $manufacturerData['total_not_achieved'] += 1;
                            }
                            // Tổ đấu dây
                            if($item['wiring_team'] == 1 )
                            {
                                $manufacturerData['wiring_team_achieved'] += 1;
                                $manufacturerData['total_achieved'] += 1;
                            }else{
                                $manufacturerData['wiring_team_not_achieved'] += 1;
                                $manufacturerData['total_not_achieved'] += 1;
                            }
                            // Dòng điện và tổn hao không tải
                            if($item['power_line'] == 1)
                            {
                                $manufacturerData['power_line_achieved'] += 1;
                                $manufacturerData['total_achieved'] += 1;
                            }else{
                                $manufacturerData['power_line_not_achieved'] += 1;
                                $manufacturerData['total_not_achieved'] += 1;
                            }
                            // Other Attr
                            $manufacturerData['other_achieved'] += $item['other_achieved'];
                            $manufacturerData['other_not_achieved'] += $item['other_not_achieved'];
                            // Total achieved and total not achieved
                            $manufacturerData['total_achieved'] += $item['other_achieved'];
                            $manufacturerData['total_not_achieved'] += $item['other_not_achieved'];
                        }
                    }
                    $dataConvert[$year] += [
                        $manufacturerName => $manufacturerData
                    ];

                }
                // add dataConvert keeping the same key as $year
                $dataFormated += $dataConvert;
            }

            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);
            $listYear = array_keys($dataFormated);
            if(count($listYear) > 0)
            {
                $listManufacturer = array_keys($dataFormated[$listYear[0]]);
            }else{
                $listManufacturer = [];
            }
            // count the number of years to report
            $count = count($listManufacturer);
            // Read Template Excel
            $template = storage_path("templates_excel/phan_xuong_co_dien/bao-cao-thi-nghiem-nhat-thu/may-bien-dong-dien/bao-cao-hang-muc-thi-nghiem-may-bien-dong-dien-khong-dat-theo-hang-san-xuat-{$count}.xlsx");
            $spreadsheet = $reader->load($template);
            // Config style for sheet
            $style = [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ];

            // Set Style for Sheet
            $spreadsheet->getDefaultStyle()->applyFromArray($style);

            $sheetThongKe = $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
            $sheetDataBD = $spreadsheet->setActiveSheetIndexByName('DATA_BD');
            $sheetBD = $spreadsheet->setActiveSheetIndexByName('BIEU_DO');

            // get array year in array format fill to sheet
            $listYear = array_keys($dataFormated);
            $sheetThongKe->getCell('E7')->setValue($listYear[0]);
            $sheetThongKe->getCell('I7')->setValue($listYear[0]);
            $sheetThongKe->getCell('G7')->setValue($listYear[1]);
            $sheetThongKe->getCell('K7')->setValue($listYear[1]);
            $title_report = "BÁO CÁO SO SÁNH KẾT QUẢ CÁC HẠNG MỤC THÍ NGHIỆM CỦA MÁY BIẾN ÁP PHÂN PHỐI THEO TỪNG HÃNG SẢN XUẤT";
            $sheetThongKe->getCell('C3')->setValue($title_report);
            $sheetDataBD->getCell('C3')->setValue($title_report);
            $sheetBD->getCell('C3')->setValue('BIỂU ĐỒ SO SÁNH NGUYÊN NHÂN MÁY BIẾN ÁP KHÔNG ĐẠT CỦA TỪNG HÃNG SẢN XUẤT GIỮA CÁC NĂM');

            $indexManufacturer = 0;
            // dynamic chart title when reporting by year sheet "BIEU_DO_MOI_NAM"
            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $chartNames = $worksheet->getChartNames();
                // Exclude chart 1 in sheet BIEU_DO
                foreach($chartNames as $chartName)
                {
                    if(count($listManufacturer) > 0)
                    {
                        $chart = $worksheet->getChartByName($chartName);
                        $titleChar = new \PhpOffice\PhpSpreadsheet\Chart\Title("So sánh nguyên nhân máy biến áp phân phối hãng {$listManufacturer[$indexManufacturer]} không đạt giữa các năm");
                        $chart->setTitle($titleChar);
                        $indexManufacturer++;
                    }
                }
            }

            // Write data to sheet 'THONG_KE_DU_LIEU' Cutting Machine Category Comparison
            $this->writeDataSheetCuttingDistribution($dataFormated, $sheetThongKe);

            // Write data to sheet 'DATA_BD' Cutting Machine Category Comparison
            $this->writeChartSheetCuttingDistribution($dataFormated, $sheetDataBD);
            $this->fillSearchDataShareReport($sheetThongKe, $request);
            $sheetBD->getStyle('A1:Z1000')->applyFromArray([
                'font' => [
                    'name' => 'Times New Roman'
                ]
            ]);
            $title = 'bao-cao-cac-hang-muc-thi-nghiem-may-bien-ap-phan-phoi-khong-dat-theo-tung-hang-san-xuat-'.time().'.xlsx';
            $url = writeExcel($spreadsheet, $title);
            return response()->json([
                'success' => true,
                'url' => $url,
            ]);
        } catch (\Exception $e) {
            Log::error('[Electromechanical] Cutting tranfer machines report of failed test items by manufacturer: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));

            return response()->json([
                'error' => [$e->getMessage()]
            ]);
        }
    }

    private function hanldeWriteDataSheetCuttingDistribution($index)
    {
        $data = [];
        switch ($index) {
            case 1:
                $data = [
                    'title' => 'Điện trở một chiều',
                    'attrAchieved' => 'dc_resistor_achieved',
                    'attrNotAchieved' => 'dc_resistor_not_achieved',
                ];
                break;
            case 2:
                $data = [
                    'title' => 'Điện trở cách điện',
                    'attrAchieved' => 'insulation_achieved',
                    'attrNotAchieved' => 'insulation_not_achieved',
                ];
                break;
            case 3:
                $data = [
                    'title' => 'Tỷ số biến',
                    'attrAchieved' => 'ratio_measurement_achieved',
                    'attrNotAchieved' => 'ratio_measurement_not_achieved',
                ];
                break;
            case 4:
                $data = [
                    'title' => 'Tổ đấu dây',
                    'attrAchieved' => 'wiring_team_achieved',
                    'attrNotAchieved' => 'wiring_team_not_achieved',
                ];
                break;
            case 5:
                $data = [
                    'title' => 'Dòng điện và tổn hao không tải',
                    'attrAchieved' => 'power_line_achieved',
                    'attrNotAchieved' => 'power_line_not_achieved',
                ];
                break;
            default:
                break;
        }
        return $data;
    }

    private function writeDataSheetCuttingDistribution($data, $spreadsheet)
    {
        if(count($data) == 0)
        {
            return;
        }
        // Flowing template excel
        $startIndexSheet = $endIndexSheet = 9;
        $listYear = array_keys($data);
        $listManufacturer = array_keys($data[$listYear[0]]);

        foreach($listManufacturer as $manufacturer)
        {
            // Calculate the number of ending lines to merge cell
            $endIndexSheet = $startIndexSheet + 4;
            // Merge cell 7 column
            $spreadsheet->mergeCells("C{$startIndexSheet}:C{$endIndexSheet}")
            ->getCell("C{$startIndexSheet}")
            ->setValue($manufacturer);

            // column order to write data
            $indexColumn = $startIndexSheet;

            // There are 7 attributes that need statistics
            for($i = 1; $i < 6; $i++)
            {
                $infoCase = $this->hanldeWriteDataSheetCuttingDistribution($i);

                $title = $infoCase['title'];
                $attrAchieved = $infoCase['attrAchieved'];
                $attrNotAchieved = $infoCase['attrNotAchieved'];

                $totalPrevYear = $data[$listYear[0]][$manufacturer][$attrAchieved] + $data[$listYear[0]][$manufacturer][$attrNotAchieved];
                $totalCurrentYear = $data[$listYear[1]][$manufacturer][$attrAchieved] + $data[$listYear[1]][$manufacturer][$attrNotAchieved];

                $spreadsheet->getCell("D{$indexColumn}")
                    ->setValue($title);
                $spreadsheet->getCell("E{$indexColumn}")
                    ->setValue($data[$listYear[0]][$manufacturer][$attrAchieved]);
                $spreadsheet->getCell("F{$indexColumn}")
                    ->setValue($data[$listYear[0]][$manufacturer][$attrNotAchieved]);
                $spreadsheet->getCell("G{$indexColumn}")
                    ->setValue($data[$listYear[1]][$manufacturer][$attrAchieved]);
                $spreadsheet->getCell("H{$indexColumn}")
                    ->setValue($data[$listYear[1]][$manufacturer][$attrNotAchieved]);

                if($totalPrevYear > 0 )
                {
                    $spreadsheet->getCell("I{$indexColumn}")
                    ->setValue(round($data[$listYear[0]][$manufacturer][$attrAchieved] / $totalPrevYear * 100, 2));

                    $spreadsheet->getCell("J{$indexColumn}")
                    ->setValue(round($data[$listYear[0]][$manufacturer][$attrNotAchieved] / $totalPrevYear * 100, 2));


                }else{
                    $spreadsheet->getCell("I{$indexColumn}")
                    ->setValue(0);
                    $spreadsheet->getCell("J{$indexColumn}")
                    ->setValue(0);
                }

                if($totalCurrentYear > 0)
                {
                    $spreadsheet->getCell("K{$indexColumn}")
                    ->setValue(round($data[$listYear[1]][$manufacturer][$attrAchieved] / $totalCurrentYear * 100, 2));
                    $spreadsheet->getCell("L{$indexColumn}")
                    ->setValue(round($data[$listYear[1]][$manufacturer][$attrNotAchieved] / $totalCurrentYear * 100, 2));
                }else{
                    $spreadsheet->getCell("K{$indexColumn}")
                    ->setValue(0);
                    $spreadsheet->getCell("L{$indexColumn}")
                    ->setValue(0);
                }

                $indexColumn++;

            }

            // There are 7 statistical properties/year so merge cell
            $startIndexSheet += 5;
        }
    }

    private function writeChartSheetCuttingDistribution($data, $spreadsheet)
    {

        if(count($data) == 0)
        {
            return;
        }

        $startIndexSheet = 6;

        $listYear = array_keys($data);
        $listManufacturer = array_keys($data[$listYear[0]]);
        foreach($listManufacturer as $manufacturer)
        {
            for($i = 0; $i < 4; $i++)
            {
                switch ($i) {
                    case 0:
                        $spreadsheet->getCell("D{$startIndexSheet}")
                            ->setValue($manufacturer);
                        $startIndexSheet++;
                        break;
                    case 1:
                        $spreadsheet->getCell("D{$startIndexSheet}")
                            ->setValue("Hạng mục");
                        $spreadsheet->getCell("E{$startIndexSheet}")
                            ->setValue("Điện trở một chiều");
                        $spreadsheet->getCell("F{$startIndexSheet}")
                            ->setValue("Điện trở cách điện");
                        $spreadsheet->getCell("G{$startIndexSheet}")
                            ->setValue("Tỷ số biến");
                        $spreadsheet->getCell("H{$startIndexSheet}")
                            ->setValue("Tổ đấu dây");
                        $spreadsheet->getCell("I{$startIndexSheet}")
                            ->setValue("Dòng điện và tổn hao không tải");
                        $spreadsheet->getCell("J{$startIndexSheet}")
                            ->setValue("Khác");
                        $spreadsheet->getCell("K{$startIndexSheet}")
                            ->setValue("Tổng");
                        $startIndexSheet++;
                        break;
                    case 2;
                        $spreadsheet->getCell("D{$startIndexSheet}")
                            ->setValue("Năm {$listYear[0]}");
                        $spreadsheet->getCell("E{$startIndexSheet}")
                            ->setValue($data[$listYear[0]][$manufacturer]['dc_resistor_not_achieved']);
                        $spreadsheet->getCell("F{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['insulation_not_achieved']);
                        $spreadsheet->getCell("G{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['ratio_measurement_not_achieved']);
                        $spreadsheet->getCell("H{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['wiring_team_not_achieved']);
                        $spreadsheet->getCell("I{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['power_line_not_achieved']);
                        $spreadsheet->getCell("J{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['other_not_achieved']);
                        $spreadsheet->getCell("K{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['total_not_achieved']);
                        $startIndexSheet++;
                        break;
                    case 3;
                        $spreadsheet->getCell("D{$startIndexSheet}")
                            ->setValue("Năm {$listYear[1]}");
                        $spreadsheet->getCell("E{$startIndexSheet}")
                            ->setValue($data[$listYear[1]][$manufacturer]['dc_resistor_not_achieved']);
                        $spreadsheet->getCell("F{$startIndexSheet}")
                            ->setValue($data[$listYear[1]][$manufacturer]['insulation_not_achieved']);
                        $spreadsheet->getCell("G{$startIndexSheet}")
                            ->setValue($data[$listYear[1]][$manufacturer]['ratio_measurement_not_achieved']);
                        $spreadsheet->getCell("H{$startIndexSheet}")
                            ->setValue($data[$listYear[1]][$manufacturer]['wiring_team_not_achieved']);
                        $spreadsheet->getCell("I{$startIndexSheet}")
                            ->setValue($data[$listYear[1]][$manufacturer]['power_line_not_achieved']);
                        $spreadsheet->getCell("J{$startIndexSheet}")
                            ->setValue($data[$listYear[1]][$manufacturer]['other_not_achieved']);
                        $spreadsheet->getCell("K{$startIndexSheet}")
                            ->setValue($data[$listYear[1]][$manufacturer]['total_not_achieved']);
                        $startIndexSheet++;
                        break;
                    default:
                        break;
                }
            }
        }
    }

    private function addDefaultValueCurrentTransformer3()
    {
        return [
            // Điện trở một chiều
            'dc_resistor' => 0,
            // Điện trở cách điện
            'insulation' => 0,
            // Tỷ số biến
            'ratio_measurement' => 0,
            // Tổ đấu dây
            'wiring_team' => 0,
            // Dòng điện và tổn hao không tải
            'power_line' => 0,
            // Hạng mục khác
            'other_achieved' => 0,
            'other_not_achieved' => 0
        ];
    }

    private function getObjectTypedistribution($type)
    {
        $objType = '';
        switch ($type) {
            case '13.8. BBTN Máy biến áp phân phối 1 pha':
                $objType = 'zCA13_8';
                break;
            case '13.9. BBTN Máy biến áp phân phối 3 pha':
                $objType = 'zCA13_9';
                break;
            default:
                break;
        }
        return $objType;
    }

    private function getAttributeDistribution($type)
    {
        return array_merge(
            // điện trở 1 chiều
            $this->getAttributeExternalCheckCurrentDistribution($type),
            // điện trở cách điện
            $this->getAttributeInsulationCurrentDistribution($type),
            // tỷ số biến
            $this->getAttributeDCResistanceCurrentDistribution($type),
            // tổ đấu dây
            $this->getAttributeRatioMeasurementCurrentDistribution($type),
            // Dòng điện và tổn hao không tải
            $this->attributeSwingHighCurrentDistribution($type),
            // khác
            $this->attributeOrtherCurrentDistribution($type),
            $this->attributeOrther1CurrentDistribution($type),
        );
    }

    private function attributeOrther1CurrentDistribution($type)
    {
        $attributes = [];
        switch($type){
            case '13.8. BBTN Máy biến áp phân phối 1 pha':
                $attributes = [
                    'z16check',
                    'z17check',
                    'z18check',
                ];
                break;
            case '13.9. BBTN Máy biến áp phân phối 3 pha':
                $attributes = [
                    'z18intergritychk',
                    'z19csbchk',
                    'z20check',
                ];
                break;
            default:
                break;
        }

        return $attributes;
    }

    private function attributeOrtherCurrentDistribution($type)
    {
        $attributes = [];
        switch($type){
            case '13.8. BBTN Máy biến áp phân phối 1 pha':
                $attributes = [
                    'z31check',
                    'z86check',
                    'z96check',
                    'z109check',
                    'z121check',
                    'z126check',
                    'z140check',
                ];
                break;
            case '13.9. BBTN Máy biến áp phân phối 3 pha':
                $attributes = [
                    'z53nlclmchk',
                    'z185achvwtchk',
                    'z151scvllmtchk',
                    'z196litchk',
                    'z212trtchk',
                    'z220nlmchk',
                    'z237pdmchk',
                ];
                break;
            default:
                break;
        }
        return $attributes;
    }

    private function attributeSwingHighCurrentDistribution($type)
    {
        $attributes = [];
        switch($type){
            case '13.8. BBTN Máy biến áp phân phối 1 pha':
                $attributes = [
                    'z44check',
                ];
                break;
            case '13.9. BBTN Máy biến áp phân phối 3 pha':
                $attributes = [
                    'z42nlclmchk',
                ];
                break;
            default:
                break;
        }

        return $attributes;
    }

    private function getAttributeRatioMeasurementCurrentDistribution($type)
    {
        $attributes = [];
        switch($type){
            case '13.8. BBTN Máy biến áp phân phối 1 pha':
                $attributes = [
                   'z95check',
                ];
                break;
            case '13.9. BBTN Máy biến áp phân phối 3 pha':
                $attributes = [
                    'z182cvgchk',
                ];
                break;
            default:
                break;
        }

        return $attributes;
    }

    private function getAttributeDCResistanceCurrentDistribution($type)
    {
        $attributes = [];
        switch($type){
            case '13.8. BBTN Máy biến áp phân phối 1 pha':
                $attributes = [
                    'z63check',
                ];
                break;
            case '13.9. BBTN Máy biến áp phân phối 3 pha':
                $attributes = [
                    'z79check',
                ];
                break;
            default:
                break;
        }

        return $attributes;
    }
    // điện trở cách điện
    private function getAttributeInsulationCurrentDistribution($type)
    {
        $attributes = [];
        switch($type){
            case '13.8. BBTN Máy biến áp phân phối 1 pha':
                $attributes = [
                    'z20check',
                ];
                break;
            case '13.9. BBTN Máy biến áp phân phối 3 pha':
                $attributes = [
                    'z24irwmtchk',
                ];
                break;
            default:
                break;
        }
        return $attributes;
    }

    // điện trở 1 chiều
    private function getAttributeExternalCheckCurrentDistribution($type)
    {
        $attributes = [];
        switch($type){
            case '13.8. BBTN Máy biến áp phân phối 1 pha':
                $attributes = [
                    'z75check',
                ];
                break;
            case '13.9. BBTN Máy biến áp phân phối 3 pha':
                $attributes = [
                    'z113wdcrmtchk',
                ];
                break;
            default:
                break;
        }
        return $attributes;
    }

    // Attribue Voltage transformers report
    private function getAllAttributeVoltage()
    {
        return array_merge(
            $this->getAttributeDCResistorVoltage(),
            $this->getAttributeInsulationVoltage(),
            $this->getAttributeRatioMeasurementVoltage(),
            $this->getAttributeTerminalMarkingsVoltage(),
            $this->getAttributeNoLoadTestVoltage(),
            $this->getAttributeInducedACVoltage(),
            $this->getAttributeSwingHighVoltage(),
            $this->getAttributeExternalCheckVoltage(),
            $this->getAttributeOtherVoltage(),
        );
    }
    private function getAttributeDCResistorVoltage()
    {
        return [
            'z32dcwinchk'
        ];
    }
    private function getAttributeInsulationVoltage()
    {
        return [
            'z19ddtckchk'
        ];
    }
    private function getAttributeRatioMeasurementVoltage()
    {
        return [
            'z40rmchk'
        ];
    }
    private function getAttributeTerminalMarkingsVoltage()
    {
        return [
            'z47votmchk'
        ];
    }
    private function getAttributeNoLoadTestVoltage()
    {
        return [
            'z70noloadchek'
        ];
    }
    private function getAttributeInducedACVoltage()
    {
        return [
            'z77iacwvt'
        ];
    }
    private function getAttributeSwingHighVoltage()
    {
        return [
            'z84achvchk'
        ];
    }
    private function getAttributeExternalCheckVoltage()
    {
        return  [
            'z14bmscdchk',
            'z15ktmdscachk',
            'z16cgcchk',
            'z17ccosbchk',
            'z18csbchk',
        ];
    }
    private function getAttributeOtherVoltage()
    {
        return [
            'z60ddftchk',
            'z103tachk',
            'z176litck',
            'z193trtchk',
            'z206pdmchk',
            'z103tachk'
        ];
    }
    // Voltage transformers - Report of failed test items by manufacturer

    public function voltageCategoryComparisonExport(Request $request)
    {
        try {

            $currentYear = $request->year;
            $prevYear = $currentYear - 1;

            if ($currentYear < now()->year) {
                $currentYear = strtotime($currentYear . "-12-31") + 86399;
            } else {
                $currentYear = strtotime(now());
            }
            $prevYear = strtotime($prevYear  . "-01-01");
            $arrayTypes = [
                '8.1. BBTN Máy biến điện áp 4 cuộn dây'
            ];

            foreach ($arrayTypes as $key => $val) {
                if ($key == 0) {
                    $whereClauseBase = "class.type IN (";
                }
                $whereClauseBase .= "'" . $val . "'";
                if ($key < count($arrayTypes) - 1) {
                    $whereClauseBase .= ",";
                } else {
                    $whereClauseBase .= ")";
                }
            }

            if($request->manufacturer)
            {
                foreach ($request->manufacturer as $key => $val) {
                    if ($key == 0) {
                        $whereClauseBase .= " AND zManufacturer.zsym IN (";
                    }
                    $whereClauseBase .= "'". $val ."'";
                    if ($key < count($request->manufacturer) - 1) {
                        $whereClauseBase .= ",";
                    } else {
                        $whereClauseBase .= ")";
                    }
                }
            }else{
                $whereClauseBase .= " AND zManufacturer.zsym IS NOT NULL ";
            }

            $whereClauseBase .= " AND zlaboratoryDate >= " . $prevYear
            . " AND zlaboratoryDate <= " . $currentYear
            . " AND class.zetc_type = 1";

            $attributes = [
                'id',
                'class.type',
                'zlaboratoryDate',
                'name',
                'zManufacturer.zsym'
            ];

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

            // Loop fetch data assoc and hanlde assoc data
            foreach($reports as $index => $report)
            {
                // Set the default value for each item to be counted is 0
                $reports[$index] = array_merge($report, $this->initDefaultValueVoltage());

                // substr, explode, join classtype get name objectType
                $objectType = 'zCA'.join('_', explode('.', substr($report['class.type'], 0, 3)));
                $attributesChild = $this->getAllAttributeVoltage();
                $where = "id = U'". $report['id'] ."'";
                $data = getDataFromService('doSelect', $objectType, $attributesChild, $where, 100);

                // Get each attribute according to the report items
                $attrExternalCheck = $this->getAttributeExternalCheckVoltage();
                $attributeInsulation = $this->getAttributeInsulationVoltage();
                $attributeDCResistance = $this->getAttributeDCResistorVoltage();
                $attributeRatioMeasurement = $this->getAttributeRatioMeasurementVoltage();
                $attributeTerminalMarkings = $this->getAttributeTerminalMarkingsVoltage();
                $attributeNoLoadTest = $this->getAttributeNoLoadTestVoltage();
                $attributeInducedAC = $this->getAttributeInducedACVoltage();
                $attributeSwingHigh = $this->getAttributeSwingHighVoltage();
                $attributeOther = $this->getAttributeOtherVoltage();

                if($this->arrayKeyExistArray($data, $attributeInsulation))
                {
                    $reports[$index]['insulation'] = 1;
                }

                if($this->arrayKeyExistArray($data, $attributeDCResistance))
                {
                    $reports[$index]['dc_resistor'] = 1;
                }

                if($this->arrayKeyExistArray($data, $attributeRatioMeasurement))
                {
                    $reports[$index]['ratio_measurement'] = 1;
                }

                if($this->arrayKeyExistArray($data, $attributeTerminalMarkings))
                {
                    $reports[$index]['terminal_markings'] = 1;
                }

                if($this->arrayKeyExistArray($data, $attributeNoLoadTest))
                {
                    $reports[$index]['no_load_test'] = 1;
                }

                if($this->arrayKeyExistArray($data, $attributeInducedAC))
                {
                    $reports[$index]['induced_ac'] = 1;
                }

                if($this->arrayKeyExistArray($data, $attributeSwingHigh))
                {
                    $reports[$index]['swing_high'] = 1;
                }

                // increment index 'other_achieved' or 'other_not_achieved'

                if($this->arrayKeyExistArray($data, $attrExternalCheck))
                {
                    $reports[$index]['other_achieved'] += 1;
                }else{
                    $reports[$index]['other_not_achieved'] += 1;
                }

                if(count($data) == 0){
                    // 1 is the plus of the external test item
                    $reports[$index]['other_not_achieved'] = count($attributeOther) + 1;
                }else{
                    foreach($data as $key => $value)
                    {
                        // attributeOther
                        foreach($attributeOther as $attr)
                        {
                            if(array_key_exists($attr, $value) && $value[$attr] == "1")
                            {
                                $reports[$index]['other_achieved'] += 1;
                            }else{
                                $reports[$index]['other_not_achieved'] += 1;
                            }
                        }
                    }
                }
                // convert timestamp to year
                $reports[$index]['zlaboratoryDate'] = Carbon::createFromTimestamp($report['zlaboratoryDate'])->year;
            }

            // Group By Year Data
            $groupByYear = collect($reports)->groupBy('zlaboratoryDate');

            // Group By Manufacturer
            foreach($groupByYear as $year => $collectYear)
            {
                $groupByYear[$year] = $collectYear->groupBy(function($item){
                    return $item['zManufacturer.zsym'];
                })->sortKeys();
            }

            $endYear = Carbon::createFromTimestamp($currentYear)->year;
            $startYear = Carbon::createFromTimestamp($prevYear)->year;

            // add index year to collection $groupByYear and add each manufacturer when query not found
            for($i = $startYear; $i <= $endYear; $i++)
            {
                if(!$groupByYear->has($i))
                {
                    $groupByYear[$i] = collect();
                }
                foreach($groupByYear as $year => $collectManufacturer)
                {

                    foreach($collectManufacturer as $manufacturerName => $manufacturerCollect)
                    {
                        if( !$groupByYear[$i]->has($manufacturerName) )
                        {
                            $groupByYear[$i][$manufacturerName] = collect([ $this->initDefaultValueVoltage() ]);
                        }
                    }
                }
            }

            // sort by year
            $groupByYear = $groupByYear->sortKeys();

            // Final data format according to excel file
            $dataFormated = [];
            foreach($groupByYear as $year => $collect)
            {
                $dataConvert[$year] = [];
                foreach($collect as $manufacturerName => $manufacturerCollect)
                {
                    $manufacturerData = [
                        'dc_resistor_achieved' => 0,
                        'dc_resistor_not_achieved' => 0,
                        'insulation_achieved' => 0,
                        'insulation_not_achieved' => 0,
                        'ratio_measurement_achieved' => 0,
                        'ratio_measurement_not_achieved' => 0,
                        'terminal_markings_achieved' => 0,
                        'terminal_markings_not_achieved' => 0,
                        'no_load_test_achieved' => 0,
                        'no_load_test_not_achieved' => 0,
                        'induced_ac_achieved' => 0,
                        'induced_ac_not_achieved' => 0,
                        'swing_high_achieved' => 0,
                        'swing_high_not_achieved' => 0,
                        'other_achieved' => 0,
                        'other_not_achieved' => 0,
                        'total_achieved' => 0,
                        'total_not_achieved' => 0
                    ];
                    // length of the collection when there was no maker that year. the value marked above and has a length of 9
                    if(count($manufacturerCollect[0]) > 9)
                    {
                        $arrayKeysCheck = [
                            'dc_resistor', 'insulation', 'ratio_measurement', 'terminal_markings', 'no_load_test', 'induced_ac', 'swing_high'
                        ];
                        // check each record by 1 to pass the "Other" exclusion
                        foreach($manufacturerCollect as $item)
                        {
                            foreach($arrayKeysCheck as $key)
                            {
                                if($item[$key] == 1)
                                {
                                    $manufacturerData["{$key}_achieved"] += 1;
                                    $manufacturerData['total_achieved'] += 1;

                                }else{

                                    $manufacturerData["{$key}_not_achieved"] += 1;
                                    $manufacturerData['total_not_achieved'] += 1;
                                }
                            }
                            // Other Attr
                            $manufacturerData['other_achieved'] += $item['other_achieved'];
                            // Total achieved and total not achieved
                            $manufacturerData['total_achieved'] += $item['other_achieved'];
                        }
                    }
                    $dataConvert[$year] += [
                        $manufacturerName => $manufacturerData
                    ];

                }
                // add dataConvert keeping the same key as $year
                $dataFormated += $dataConvert;

            }
            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);

            $listYear = [$startYear, $endYear];
            if(count($listYear) > 0)
            {
                $listManufacturer = array_keys($dataFormated[$listYear[0]]);
            }else{
                $listManufacturer = [];
            }

            // count the number of years to report
            $count = count($listManufacturer);
            // Read Template Excel
            $template = storage_path("templates_excel/phan_xuong_co_dien/bao-cao-thi-nghiem-nhat-thu/may-bien-dien-ap/bao-cao-hang-muc-thi-nghiem-may-bien-dien-khong-dat-theo-hang-san-xuat-{$count}.xlsx");
            $spreadsheet = $reader->load($template);
            // Config style for sheet
            $style = [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ];

            // Set Style for Sheet
            $spreadsheet->getDefaultStyle()->applyFromArray($style);

            $sheetThongKe = $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');
            $sheetDataBD = $spreadsheet->setActiveSheetIndexByName('DATA_BD');

            // get array year in array format fill to sheet
            $listYear = array_keys($dataFormated);
            $sheetThongKe->getCell('E7')->setValue($listYear[0]);
            $sheetThongKe->getCell('I7')->setValue($listYear[0]);
            $sheetThongKe->getCell('G7')->setValue($listYear[1]);
            $sheetThongKe->getCell('K7')->setValue($listYear[1]);

            $sheetDataBD->getCell('D3')->setValue("BÁO CÁO SO SÁNH GIỮA CÁC HẠNG MỤC THÍ NGHIỆM MÁY BIẾN ĐIỆN ÁP KHÔNG ĐẠT TỪ NĂM {$listYear[0]} - {$listYear[1]}");
            $sheetThongKe->getCell('C3')->setValue("BÁO CÁO SO SÁNH KẾT QUẢ CÁC HẠNG MỤC THÍ NGHIỆM CỦA MÁY BIẾN ĐIỆN ÁP THEO TỪNG HÃNG SẢN XUẤT");
            $spreadsheet->setActiveSheetIndexByName("BIEU_DO")->getCell('C3')->setValue("BIỂU ĐỒ SO SÁNH NGUYÊN NHÂN MÁY BIẾN ĐIỆN ÁP KHÔNG ĐẠT CỦA CÁC HÃNG SẢN XUẤT GIỮA CÁC NĂM");

            $indexManufacturer = 0;
            // dynamic chart title when reporting by year sheet "BIEU_DO_MOI_NAM"
            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $chartNames = $worksheet->getChartNames();
                // Exclude chart 1 in sheet BIEU_DO
                foreach($chartNames as $chartName)
                {
                    if(count($listManufacturer) > 0)
                    {
                        $chart = $worksheet->getChartByName($chartName);
                        $titleChar = new \PhpOffice\PhpSpreadsheet\Chart\Title("So sánh nguyên nhân máy biến điện áp hãng {$listManufacturer[$indexManufacturer]} không đạt giữa các năm");
                        $chart->setTitle($titleChar);
                        $indexManufacturer++;
                    }
                }
            }

            // Write data to sheet 'THONG_KE_DU_LIEU' Cutting Machine Category Comparison
            $this->writeDataSheetVoltageCategoryComparison($dataFormated, $sheetThongKe);

            // Write data to sheet 'DATA_BD' Cutting Machine Category Comparison
            $this->writeChartSheetCuttingVoltageCategoryComparison($dataFormated, $sheetDataBD);
            $this->fillSearchDataShareReport($sheetThongKe, $request);
            // Set active sheet BIEU_DO when view file
            $spreadsheet->setActiveSheetIndexByName("BIEU_DO");

            $title = 'bao-cao-cac-hang-muc-thi-nghiem-may-bien-dien-ap-khong-dat-theo-tung-hang-san-xuat-'.time().'.xlsx';
            $url = writeExcel($spreadsheet, $title);
            return response()->json([
                'success' => true,
                'url' => $url,
            ]);

        } catch (\Exception $e) {
            Log::error('[Electromechanical] Report of voltage transformer test items failed by manufacturer: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));

            return response()->json([
                'error' => [$e->getMessage()]
            ]);
        }
    }

    private function initDefaultValueVoltage()
    {
        return [
            'dc_resistor' => 0,
            'insulation' => 0,
            'ratio_measurement' => 0,
            'terminal_markings' => 0,
            'no_load_test' => 0,
            'induced_ac' => 0,
            'swing_high' => 0,
            'other_achieved' => 0,
            'other_not_achieved' => 0
        ];
    }

    private function writeDataSheetVoltageCategoryComparison($data, $spreadsheet)
    {
        if(empty($data))
        {
            return;
        }
        // Flowing template excel
        $startIndexSheet = $endIndexSheet = 9;
        $listYear = array_keys($data);
        $listManufacturer = array_keys($data[$listYear[0]]);

        foreach($listManufacturer as $manufacturer)
        {
            // Calculate the number of ending lines to merge cell
            $endIndexSheet = $startIndexSheet + 6;
            // Merge cell 7 column
            $spreadsheet->mergeCells("C{$startIndexSheet}:C{$endIndexSheet}")
            ->getCell("C{$startIndexSheet}")
            ->setValue($manufacturer);

            // column order to write data
            $indexColumn = $startIndexSheet;

            // There are 7 attributes that need statistics
            for($i = 1; $i < 8; $i++)
            {
                $infoCase = $this->hanldeWriteDataSheetVoltageCategoryComparison($i);

                $title = $infoCase['title'];
                $attrAchieved = $infoCase['attrAchieved'];
                $attrNotAchieved = $infoCase['attrNotAchieved'];

                $totalPrevYear = $data[$listYear[0]][$manufacturer][$attrAchieved] + $data[$listYear[0]][$manufacturer][$attrNotAchieved];
                $totalCurrentYear = $data[$listYear[1]][$manufacturer][$attrAchieved] + $data[$listYear[1]][$manufacturer][$attrNotAchieved];

                $spreadsheet->getCell("D{$indexColumn}")
                    ->setValue($title);
                $spreadsheet->getCell("E{$indexColumn}")
                    ->setValue($data[$listYear[0]][$manufacturer][$attrAchieved]);
                $spreadsheet->getCell("F{$indexColumn}")
                    ->setValue($data[$listYear[0]][$manufacturer][$attrNotAchieved]);
                $spreadsheet->getCell("G{$indexColumn}")
                    ->setValue($data[$listYear[1]][$manufacturer][$attrAchieved]);
                $spreadsheet->getCell("H{$indexColumn}")
                    ->setValue($data[$listYear[1]][$manufacturer][$attrNotAchieved]);

                if($totalPrevYear > 0)
                {
                    $spreadsheet->getCell("I{$indexColumn}")
                    ->setValue(round($data[$listYear[0]][$manufacturer][$attrAchieved] / $totalPrevYear * 100, 2));

                    $spreadsheet->getCell("J{$indexColumn}")
                    ->setValue(round($data[$listYear[0]][$manufacturer][$attrNotAchieved] / $totalPrevYear * 100, 2));

                }else{
                    $spreadsheet->getCell("I{$indexColumn}")
                    ->setValue(0);
                    $spreadsheet->getCell("J{$indexColumn}")
                    ->setValue(0);
                }

                if($totalCurrentYear > 0)
                {
                    $spreadsheet->getCell("K{$indexColumn}")
                    ->setValue(round($data[$listYear[1]][$manufacturer][$attrAchieved] / $totalCurrentYear * 100, 2));
                    $spreadsheet->getCell("L{$indexColumn}")
                    ->setValue(round($data[$listYear[1]][$manufacturer][$attrNotAchieved] / $totalCurrentYear * 100, 2));
                }else{
                    $spreadsheet->getCell("K{$indexColumn}")
                    ->setValue(0);
                    $spreadsheet->getCell("L{$indexColumn}")
                    ->setValue(0);
                }

                $indexColumn++;

            }

            // There are 7 statistical properties/year so merge cell
            $startIndexSheet += 7;
        }
    }
    private function hanldeWriteDataSheetVoltageCategoryComparison($index)
    {
        $data = [];
        switch ($index) {
            case 1:
                $data = [
                    'title' => 'Điện trở một chiều cuộn dây',
                    'attrAchieved' => 'dc_resistor_achieved',
                    'attrNotAchieved' => 'dc_resistor_not_achieved',
                ];
                break;
            case 2:
                $data = [
                    'title' => 'Điện trở cách điện',
                    'attrAchieved' => 'insulation_achieved',
                    'attrNotAchieved' => 'insulation_not_achieved',
                ];
                break;
            case 3:
                $data = [
                    'title' => 'Tỷ số biến',
                    'attrAchieved' => 'ratio_measurement_achieved',
                    'attrNotAchieved' => 'ratio_measurement_not_achieved',
                ];
                break;
            case 4:
                $data = [
                    'title' => 'kiểm tra ký hiệu đầu cực tính các pha',
                    'attrAchieved' => 'terminal_markings_achieved',
                    'attrNotAchieved' => 'terminal_markings_not_achieved',
                ];
                break;
            case 5:
                $data = [
                    'title' => 'Dòng điện không tải',
                    'attrAchieved' => 'no_load_test_achieved',
                    'attrNotAchieved' => 'no_load_test_not_achieved',
                ];
                break;
            case 6:
                $data = [
                    'title' => 'Thí nghiệm cách điện vòng dây',
                    'attrAchieved' => 'induced_ac_achieved',
                    'attrNotAchieved' => 'induced_ac_not_achieved',
                ];
                break;
            case 7:
                $data = [
                    'title' => 'Thí nghiệm điện áp xoay chiều tăng cao tần số công nghiệp',
                    'attrAchieved' => 'swing_high_achieved',
                    'attrNotAchieved' => 'swing_high_not_achieved',
                ];
                break;
            default:
                break;
        }
        return $data;
    }

    private function writeChartSheetCuttingVoltageCategoryComparison($data, $spreadsheet)
    {

        if(count($data) == 0)
        {
            return;
        }

        $startIndexSheet = 6;

        $listYear = array_keys($data);
        $listManufacturer = array_keys($data[$listYear[0]]);

        foreach($listManufacturer as $manufacturer)
        {
            for($i = 0; $i < 4; $i++)
            {
                switch ($i) {
                    case 0:
                        $spreadsheet->getCell("D{$startIndexSheet}")
                            ->setValue($manufacturer);
                        $startIndexSheet++;
                        break;
                    case 1:
                        $spreadsheet->getCell("D{$startIndexSheet}")
                            ->setValue("Hạng mục");
                        $spreadsheet->getCell("E{$startIndexSheet}")
                            ->setValue("Điện trở một chiều cuộn dây");
                        $spreadsheet->getCell("F{$startIndexSheet}")
                            ->setValue("Điện trở cách điện");
                        $spreadsheet->getCell("G{$startIndexSheet}")
                            ->setValue("Tỷ số biến");
                        $spreadsheet->getCell("H{$startIndexSheet}")
                            ->setValue("Kiểm tra ký hiệu đầu cực tính các pha");
                        $spreadsheet->getCell("I{$startIndexSheet}")
                            ->setValue("Dòng điện không tải");
                        $spreadsheet->getCell("J{$startIndexSheet}")
                            ->setValue("Thí nghiệm cách điện vòng dây");
                        $spreadsheet->getCell("K{$startIndexSheet}")
                            ->setValue("Thí nghiệm điện áp xoay chiều tăng cao tần số công nghiệp");
                        $spreadsheet->getCell("L{$startIndexSheet}")
                            ->setValue("Tổng");
                        $startIndexSheet++;
                        break;
                    case 2;
                        $spreadsheet->getCell("D{$startIndexSheet}")
                            ->setValue("Năm {$listYear[0]}");
                        $spreadsheet->getCell("E{$startIndexSheet}")
                            ->setValue($data[$listYear[0]][$manufacturer]['dc_resistor_not_achieved']);
                        $spreadsheet->getCell("F{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['insulation_not_achieved']);
                        $spreadsheet->getCell("G{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['ratio_measurement_not_achieved']);
                        $spreadsheet->getCell("H{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['terminal_markings_not_achieved']);
                        $spreadsheet->getCell("I{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['no_load_test_not_achieved']);
                        $spreadsheet->getCell("J{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['induced_ac_not_achieved']);
                        $spreadsheet->getCell("K{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['swing_high_not_achieved']);
                        $spreadsheet->getCell("L{$startIndexSheet}")
                                ->setValue($data[$listYear[0]][$manufacturer]['total_not_achieved']);
                        $startIndexSheet++;
                        break;
                    case 3;
                        $spreadsheet->getCell("D{$startIndexSheet}")
                            ->setValue("Năm {$listYear[1]}");
                        $spreadsheet->getCell("E{$startIndexSheet}")
                            ->setValue($data[$listYear[1]][$manufacturer]['dc_resistor_not_achieved']);
                        $spreadsheet->getCell("F{$startIndexSheet}")
                                ->setValue($data[$listYear[1]][$manufacturer]['insulation_not_achieved']);
                        $spreadsheet->getCell("G{$startIndexSheet}")
                                ->setValue($data[$listYear[1]][$manufacturer]['ratio_measurement_not_achieved']);
                        $spreadsheet->getCell("H{$startIndexSheet}")
                                ->setValue($data[$listYear[1]][$manufacturer]['terminal_markings_not_achieved']);
                        $spreadsheet->getCell("I{$startIndexSheet}")
                                ->setValue($data[$listYear[1]][$manufacturer]['no_load_test_not_achieved']);
                        $spreadsheet->getCell("J{$startIndexSheet}")
                                ->setValue($data[$listYear[1]][$manufacturer]['induced_ac_not_achieved']);
                        $spreadsheet->getCell("K{$startIndexSheet}")
                                ->setValue($data[$listYear[1]][$manufacturer]['swing_high_not_achieved']);
                        $spreadsheet->getCell("L{$startIndexSheet}")
                                ->setValue($data[$listYear[1]][$manufacturer]['total_not_achieved']);
                        $startIndexSheet++;
                        break;
                    default:
                        break;
                }
            }
        }
    }
    private function fillSearchDataShareReport($sheetActive, $request)
    {
        $columns = ['E', 'F'];
        $inputs = [
            'Năm báo cáo: ' => 'year',
            'Hãng sản xuất: ' => 'manufacturer',
        ];
        fillSearchRequestToExcel($sheetActive, $request, $columns, $inputs, 1);
        $sheetActive->getStyle('D10:D1000')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
    }
}

