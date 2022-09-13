<?php

namespace Modules\Electromechanical\Http\Controllers;

use App\Exports\FiguresForEachUnitExport;
use App\Exports\NumberOfExperimentsExport;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class ElectromechanicalController extends Controller
{
    /**
     * Monthly report
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function monthlyReport()
    {
        return view('electromechanical::monthlyReport.index');
    }

    /**
     * Quarterly report
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function quarterlyReport()
    {
        return view('electromechanical::quarterlyReport.index');
    }

    /**
     * Annually report
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function annuallyReport()
    {
        return view('electromechanical::annuallyReport.index');
    }

    /**
     * Time Period report preview
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function timePeriodReportPreview(Request $request)
    {
        try {
            if (empty($request['time'])) {
                return response()->json([
                    'error' => ['Thời gian cần thống kê không được để trống']
                ]);
            }

            // Format request time data
            $currentYear = $request['time_type'] == 'quarter' ? (int)$request['time'] : (int)date('Y', strtotime($request['time']));
            $prevYear = $currentYear - 1;

            $monthOfPreviousYearsArr = [];
            for ($i = 1; $i <= 12; $i++) {
                array_push($monthOfPreviousYearsArr, ($i < 10 ? '0' . $i : $i) . '/' . $prevYear);
            }

            // Get report type
            $types = $request['type'];
            if (empty($request['type'])) {
                $types = [
                    config('constant.electromechanical.device.type.cap_boc_ha_ap'),
                    config('constant.electromechanical.device.type.cap_boc_trung_ap'),
                    config('constant.electromechanical.device.type.cap_luc_trung_the'),
                    config('constant.electromechanical.device.type.cap_van_xoan'),
                    config('constant.electromechanical.device.type.day_dan_tran'),
                ];
            }
            $arrType = getReportFromType($types);

            // Get report
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

            if ($request['time_type'] == 'quarter') {
                $selectedYear = strtotime($request['time'] . '-01-01');
                $startYear = strtotime("-1 years", $selectedYear);
                $endYear = strtotime("+1 years", $selectedYear);
                $whereClause .= ' AND zlaboratoryDate >= ' . $startYear . ' AND zlaboratoryDate < ' . $endYear;
            } else {
                $whereClause .= ' AND zlaboratoryDate < ' . strtotime("+1 months", strtotime($request['time']));
            }

            $whereClause .= ' AND class.zetc_type = 1';
            $reports = getDataFromService('doSelect', 'nr', [
                'zresultEnd_chk',
                'zlaboratoryDate',
                'class.type',
            ], $whereClause, 100);

            if(empty($reports)) {
                return response()->json([
                    'error' => ['Không tìm thấy dữ liệu thống kê thỏa mãn']
                ]);
            }

            $dataArr = [];
            foreach ($types as $type) {
                $arr = getReportFromType([$type]);
                $convertReportArr = [];
                foreach ($reports as $key => $report) {
                    if (!in_array($report['class.type'], $arr)) {
                        continue;
                    }

                    $reports[$key]['zlaboratoryDate'] = !empty($report['zlaboratoryDate']) ? date('m/Y', $report['zlaboratoryDate']) : '';
                    $reports[$key]['zresultEnd_chk'] = @$report['zresultEnd_chk'] ? 'Đạt' : '';

                    if (!isset($convertReportArr[$reports[$key]['zlaboratoryDate']])) {
                        $convertReportArr[$reports[$key]['zlaboratoryDate']] = [
                            'zlaboratoryDate' => $reports[$key]['zlaboratoryDate'],
                            'total' => 0,
                            'pass' => 0,
                            'failed' => 0
                        ];
                    }
                    $convertReportArr[$reports[$key]['zlaboratoryDate']]['total'] += 1;
                    if ($reports[$key]['zresultEnd_chk'] == 'Đạt') {
                        $convertReportArr[$reports[$key]['zlaboratoryDate']]['pass'] += 1;
                    } else {
                        $convertReportArr[$reports[$key]['zlaboratoryDate']]['failed'] += 1;
                    }
                }

                foreach ($monthOfPreviousYearsArr as $val) {
                    $monthOfCurrentYear = substr($val, 0, 2) . '/' . $currentYear;

                    $dataArr[$type][$val]['last_year_pass'] = $convertReportArr[$val]['pass'] ?? 0;
                    $dataArr[$type][$val]['last_year_failed'] = $convertReportArr[$val]['failed'] ?? 0;

                    $dataArr[$type][$val]['current_year_pass'] = $convertReportArr[$monthOfCurrentYear]['pass'] ?? 0;
                    $dataArr[$type][$val]['current_year_failed'] = $convertReportArr[$monthOfCurrentYear]['failed'] ?? 0;

                    $dataArr[$type][$val]['last_year_total'] = $convertReportArr[$val]['total'] ?? 0;
                    $dataArr[$type][$val]['current_year_total'] = $convertReportArr[$monthOfCurrentYear]['total'] ?? 0;
                }
            }

            // Format data if report is quarterly
            if ($request['time_type'] == 'quarter') {
                $arr = [];
                foreach ($dataArr as $key => $val) {
                    $count = 1;
                    $quarterArr = [
                        'last_year_pass' => 0,
                        'last_year_failed' => 0,
                        'current_year_pass' => 0,
                        'current_year_failed' => 0,
                        'last_year_total' => 0,
                        'current_year_total' => 0,
                    ];
                    foreach ($val as $item) {
                        if ($count % 3 == 1) {
                            $quarterArr = [
                                'last_year_pass' => 0,
                                'last_year_failed' => 0,
                                'current_year_pass' => 0,
                                'current_year_failed' => 0,
                                'last_year_total' => 0,
                                'current_year_total' => 0,
                            ];
                        }

                        $quarterArr['last_year_pass'] += $item['last_year_pass'];
                        $quarterArr['last_year_failed'] += $item['last_year_failed'];
                        $quarterArr['current_year_pass'] += $item['current_year_pass'];
                        $quarterArr['current_year_failed'] += $item['current_year_failed'];
                        $quarterArr['last_year_total'] += $item['last_year_total'];
                        $quarterArr['current_year_total'] += $item['current_year_total'];

                        if ($count % 3 == 0) {
                            $arr[$key][ceil($count / 3)] = $quarterArr;
                        }
                        $count++;
                    }
                }
                $dataArr = $arr;
            }

            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);

            // Read Template Excel
            $template = $request['time_type'] == 'quarter' ? storage_path('templates_excel/phan_xuong_co_dien/bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-theo-thoi-gian/bao-cao-theo-quy.xlsx') : storage_path('templates_excel/phan_xuong_co_dien/bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-theo-thoi-gian/bao-cao-theo-thang.xlsx');
            $spreadsheet = $reader->load($template);

            // Sheet data
            $statisticsSheet = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THONG_KE");
            $oneYearStatisticsSheet = $spreadsheet->setActiveSheetIndexByName("DATA_BĐ1");
            $twoYearStatisticsSheet = $spreadsheet->setActiveSheetIndexByName("DATA_BĐ2");
            $chartSheet = $spreadsheet->setActiveSheetIndexByName("BIEU_DO");

            // Insert label
            $chartSheet->getCell('B5')->setValue('Biểu đồ so sánh số liệu trong cùng 1 năm ' . $currentYear);
            $chartSheet->getCell('B31')->setValue('Biểu đồ so sánh số liệu trong 2 năm liền kề (' . $prevYear . ' - ' . $currentYear . ')');

            $twoYearStatisticsSheet->getCell('C6')->setValue('Đạt năm ' . $prevYear);
            $twoYearStatisticsSheet->getCell('D6')->setValue('Không đạt năm ' . $prevYear);
            $twoYearStatisticsSheet->getCell('E6')->setValue('Tổng năm  ' . $prevYear);
            $twoYearStatisticsSheet->getCell('F6')->setValue('Đạt năm ' . $currentYear);
            $twoYearStatisticsSheet->getCell('G6')->setValue('Không đạt năm ' . $currentYear);
            $twoYearStatisticsSheet->getCell('H6')->setValue('Tổng năm ' . $currentYear);

            // Insert statistics data and calculate chart data
            $twoYearStatisticsItemArr = [];
            $startInsertCell = 7;
            $stepBetweenTable = $request['time_type'] == 'quarter' ? 9 : 17;
            $typeIndex = 0;
            foreach ($dataArr as $key => $val) {
                $count = 0;

                $statisticsSheet->getCell('A' . ($startInsertCell - 4 + $stepBetweenTable * $typeIndex))->setValue($key);
                $statisticsSheet->getCell('C' . ($startInsertCell - 2 + $stepBetweenTable * $typeIndex))->setValue($prevYear);
                $statisticsSheet->getCell('E' . ($startInsertCell - 2 + $stepBetweenTable * $typeIndex))->setValue($currentYear);
                $statisticsSheet->getCell('G' . ($startInsertCell - 2 + $stepBetweenTable * $typeIndex))->setValue($prevYear);
                $statisticsSheet->getCell('H' . ($startInsertCell - 2 + $stepBetweenTable * $typeIndex))->setValue($currentYear);

                foreach ($val as $statisticsKey => $statisticsItem) {
                    // Calculate chart data
                    $twoYearStatisticsItemArr[$statisticsKey]['last_year_pass'] = isset($twoYearStatisticsItemArr[$statisticsKey]['last_year_pass']) ? $twoYearStatisticsItemArr[$statisticsKey]['last_year_pass'] + $statisticsItem['last_year_pass'] : $statisticsItem['last_year_pass'];
                    $twoYearStatisticsItemArr[$statisticsKey]['last_year_failed'] = isset($twoYearStatisticsItemArr[$statisticsKey]['last_year_failed']) ? $twoYearStatisticsItemArr[$statisticsKey]['last_year_failed'] + $statisticsItem['last_year_failed'] : $statisticsItem['last_year_failed'];
                    $twoYearStatisticsItemArr[$statisticsKey]['last_year_total'] = isset($twoYearStatisticsItemArr[$statisticsKey]['last_year_total']) ? $twoYearStatisticsItemArr[$statisticsKey]['last_year_total'] + $statisticsItem['last_year_total'] : $statisticsItem['last_year_total'];
                    $twoYearStatisticsItemArr[$statisticsKey]['current_year_pass'] = isset($twoYearStatisticsItemArr[$statisticsKey]['current_year_pass']) ? $twoYearStatisticsItemArr[$statisticsKey]['current_year_pass'] + $statisticsItem['current_year_pass'] : $statisticsItem['current_year_pass'];
                    $twoYearStatisticsItemArr[$statisticsKey]['current_year_failed'] = isset($twoYearStatisticsItemArr[$statisticsKey]['current_year_failed']) ? $twoYearStatisticsItemArr[$statisticsKey]['current_year_failed'] + $statisticsItem['current_year_failed'] : $statisticsItem['current_year_failed'];
                    $twoYearStatisticsItemArr[$statisticsKey]['current_year_total'] = isset($twoYearStatisticsItemArr[$statisticsKey]['current_year_total']) ? $twoYearStatisticsItemArr[$statisticsKey]['current_year_total'] + $statisticsItem['current_year_total'] : $statisticsItem['current_year_total'];

                    // Insert statistics data
                    $statisticsItem = array_values($statisticsItem);
                    $index = 0;
                    for ($c = 'C'; $c != 'I'; ++$c) {
                        $row = $startInsertCell + $stepBetweenTable * $typeIndex + $count;
                        $statisticsSheet->getCell($c . $row)->setValue($statisticsItem[$index]);
                        $index++;
                    }
                    $count++;
                }
                $typeIndex++;
            }

            // Remove unneeded content
            if (!empty($row)) {
                for ($i = $row + 1; $i <= 100; $i++) {
                    $statisticsSheet->getRowDimension($i)->setVisible(false);
                }
            }

            // Insert chart data
            foreach ($twoYearStatisticsItemArr as $twoYearStatisticsItem) {
                $oneYearStatisticsItem = [
                    $twoYearStatisticsItem['current_year_pass'],
                    $twoYearStatisticsItem['current_year_failed'],
                    $twoYearStatisticsItem['current_year_total'],
                ];

                $twoYearStatisticsItem = array_values($twoYearStatisticsItem);
                $index = 0;
                for ($c = 'C'; $c != 'I'; ++$c) {
                    $twoYearStatisticsSheet->getCell($c . $startInsertCell)->setValue($twoYearStatisticsItem[$index]);
                    if ($c <= 'E') {
                        $oneYearStatisticsSheet->getCell($c . $startInsertCell)->setValue($oneYearStatisticsItem[$index]);
                    }
                    $index++;
                }
                $startInsertCell++;
            }
            $columns = ['C', 'D'];
            $inputs = [
                'Thời gian thống kê: ' => 'time',
                'Loại dây dẫn: ' => 'type'
            ];
            $request->offsetSet('time',  $request['time_type'] == 'quarter' ? (int)$request['time'] : date('m/Y', strtotime($request['time'])));
            fillSearchRequestToExcel($statisticsSheet, $request, $columns, $inputs, 2);
            $columnTitles = $request['time_type'] == 'quarter' ? ['A5', 'A14', 'A23', 'A32', 'A41'] : ['A5', 'A22', 'A39', 'A56', 'A73'];
            foreach($columnTitles as $column){
                $statisticsSheet->getStyle($column)->getAlignment()->setWrapText(false);
            }
            $title = $request['time_type'] == 'quarter' ? '-theo-quy-' : '-theo-thang-';
            $url = writeExcel($spreadsheet, 'bao-cao-thong-ke-cap-va-day-dan-tran-' . $title . time() . '.xlsx');

            return response()->json([
                'success' => true,
                'url' => $url
            ]);
        } catch (\Exception $ex) {
            Log::error('[Electromechanical] Get monthly report preview: Fail | Reason: ' . $ex->getMessage() . '. ' . $ex->getFile() . ':' . $ex->getLine() . '| Params: ' . json_encode($request->all()));

            return response()->json([
                'error' => [$ex->getMessage()]
            ]);
        }
    }

    /**
     * Annually report preview
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function annuallyReportPreview(Request $request)
    {
        try {
            if (empty($request['startYear']) || empty($request['endYear'])) {
                return response()->json([
                    'error' => ['Thời gian cần thống kê không được để trống']
                ]);
            }

            if ($request['endYear'] < $request['startYear']) {
                return response()->json([
                    'error' => ['Thời gian kết thúc phải lớn hơn hoặc bằng thời gian bắt đầu']
                ]);
            }

            // Get report type
            $types = $request['type'];
            if (empty($request['type'])) {
                $types = [
                    config('constant.electromechanical.device.type.cap_boc_ha_ap'),
                    config('constant.electromechanical.device.type.cap_boc_trung_ap'),
                    config('constant.electromechanical.device.type.cap_luc_trung_the'),
                    config('constant.electromechanical.device.type.cap_van_xoan'),
                    config('constant.electromechanical.device.type.day_dan_tran'),
                ];
            }
            $arrType = getReportFromType($types);

            // Get report
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

            $whereClause .= ' AND class.zetc_type = 1 AND zlaboratoryDate >= ' . strtotime($request['startYear'] . '-01-01') . ' AND zlaboratoryDate < ' . strtotime("+1 years", strtotime($request['endYear'] . '-01-01'));
            $reports = getDataFromService('doSelect', 'nr', [
                'zresultEnd_chk',
                'zlaboratoryDate',
                'class.type'
            ], $whereClause, 100);

            if(empty($reports)) {
                return response()->json([
                    'error' => ['Không tìm thấy dữ liệu thống kê thỏa mãn']
                ]);
            }

            $dataArr = [];
            for ($i = (int)$request['startYear']; $i <= (int)$request['endYear']; $i++) {
                foreach ($types as $type) {
                    $arr = getReportFromType([$type]);
                    $dataArr[$i][$type] = [
                        'pass' => 0,
                        'failed' => 0,
                        'total' => 0
                    ];
                    foreach ($reports as $key => $report) {
                        if (! in_array($report['class.type'], $arr) || empty($report['zlaboratoryDate']) || ((int)date('Y', $report['zlaboratoryDate']) != $i)) {
                            continue;
                        }

                        $reports[$key]['zlaboratoryDate'] = ! empty($report['zlaboratoryDate']) ? date('Y', $report['zlaboratoryDate']) : '';
                        $reports[$key]['zresultEnd_chk'] = @$report['zresultEnd_chk'] ? 'Đạt' : '';

                        $dataArr[$i][$type]['total'] += 1;
                        if ($reports[$key]['zresultEnd_chk'] == 'Đạt') {
                            $dataArr[$i][$type]['pass'] += 1;
                        } else {
                            $dataArr[$i][$type]['failed'] += 1;
                        }
                    }
                }
            }

            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);

            // Read Template Excel
            $template = storage_path('templates_excel/phan_xuong_co_dien/bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-theo-thoi-gian/bao-cao-theo-nam/bao-cao-theo-nam-' . count($dataArr) . '.xlsx');
            $spreadsheet = $reader->load($template);

            // Sheet data
            $statisticsSheet = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THONG_KE");
            $totalNumberSheet = $spreadsheet->setActiveSheetIndexByName("DATA_BĐ1");
            $failedPercentageSheet = $spreadsheet->setActiveSheetIndexByName("DATA_BĐ2");

            $count = 0;
            $row = 5;
            foreach ($dataArr as $key => $val) {
                $passedTotal = $failedTotal = $total = 0;

                // Set data for statistics sheet
                $statisticsSheet->getCell('A' . $row)->setValue($count + 1);
                $statisticsSheet->getCell('B' . $row)->setValue($key);
                foreach ($val as $index => $item) {
                    $statisticsSheet->getRowDimension($row)->setRowHeight(35);
                    $statisticsSheet->getCell('C' . $row)->setValue($index);
                    $statisticsSheet->getCell('D' . $row)->setValue($item['pass']);
                    $statisticsSheet->getCell('E' . $row)->setValue($item['failed']);
                    $statisticsSheet->getCell('F' . $row)->setValue($item['total']);
                    $statisticsSheet->getCell('G' . $row)->setValue(round(!empty($item['total']) ? $item['failed'] / $item['total'] * 100 : 0, 2));
                    $passedTotal += $item['pass'];
                    $failedTotal += $item['failed'];
                    $total += $item['total'];
                    $row++;
                }
                $statisticsSheet->getCell('H' . ($row - count($types)))->setValue($total);
                $statisticsSheet->mergeCells('A' . ($row - count($types)) . ":A" . ($row - 1));
                $statisticsSheet->mergeCells('B' . ($row - count($types)) . ":B" . ($row - 1));
                $statisticsSheet->mergeCells('H' . ($row - count($types)) . ":H" . ($row - 1));
                $statisticsSheet->getStyle('A' . ($row - count($types)) . ':H' . ($row - 1))->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                // Set data for total number sheet
                $totalNumberSheet->getCell('B' . ($count + 7))->setValue('Năm ' . $key);
                $totalNumberSheet->getCell('C' . ($count + 7))->setValue($passedTotal);
                $totalNumberSheet->getCell('D' . ($count + 7))->setValue($failedTotal);

                // Set data for total failed percentage sheet
                $failedPercentageSheet->getCell('B' . ($count + 7))->setValue('Năm ' . $key);
                $failedPercentageSheet->getCell('C' . ($count + 7))->setValue(round(!empty($total) ? $failedTotal / $total * 100 : 0, 2));
                $count++;
            }

            $chartSheet = $spreadsheet->setActiveSheetIndexByName("BIEU_DO");
            // Set data for specific type sheet
            foreach ($types as $index => $type) {
                $count = 0;
                $chartSheet->getCell('B' . (57 + 26 * $index))->setValue('Biểu đồ thể hiện thí nghiệm ' . $type);
                $chartDataForTypeSheet = $spreadsheet->setActiveSheetIndexByName("DATA_BĐ" . ($index + 3));
                $chartDataForTypeSheet->getCell('A4')->setValue('Dữ liệu Biểu đồ thể hiện thí nghiệm ' . $type);
                foreach ($dataArr as $key => $val) {
                    $chartDataForTypeSheet->getCell('B' . ($count + 7))->setValue('Năm ' . $key);
                    $chartDataForTypeSheet->getCell('C' . ($count + 7))->setValue($val[$type]['failed']);
                    $count++;
                }
            }

            $spreadsheet->setActiveSheetIndexByName("BIEU_DO");
            // Remove unneeded content
            if (count($types) < 5) {
                $startDeletionRow = 57 + 26 * count($types);
                for ($i = $startDeletionRow; $i <= 200; $i++) {
                    $chartSheet->getRowDimension($i)->setVisible(false);
                }
                for ($i = count($types) + 3; $i <= 7; $i++) {
                    $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($spreadsheet->getSheetByName("DATA_BĐ" . ($i))));
                }
            }

            $columns = ['D', 'E', 'F'];
            $inputs = [
                'Năm bắt đầu thống kê: ' => 'startYear',
                'Năm kết thúc thống kê: ' => 'endYear',
                'Loại dây dẫn: ' => 'type'
            ];
            fillSearchRequestToExcel($statisticsSheet, $request, $columns, $inputs, 1);
            alignmentExcel($statisticsSheet, 'C6:C1000');
            $url = writeExcel($spreadsheet, 'bao-cao-thong-ke-cap-va-day-dan-tran-theo-nam-' . time() . '.xlsx');

            return response()->json([
                'success' => true,
                'url' => $url
            ]);
        } catch (\Exception $ex) {
            Log::error('[Electromechanical] Get annually report preview: Fail | Reason: ' . $ex->getMessage() . '. ' . $ex->getFile() . ':' . $ex->getLine() . '| Params: ' . json_encode($request->all()));

            return response()->json([
                'error' => [$ex->getMessage()]
            ]);
        }
    }

    /**
     * Manufacturers sales report
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function manufacturersSalesReport()
    {
        return view('electromechanical::manufacturersSalesReport.index');
    }

    /**
     * Manufacturers sales report preview
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function manufacturersSalesReportPreview(Request $request)
    {
        try {
            if (empty($request['startYear']) || empty($request['endYear'])) {
                return response()->json([
                    'error' => ['Thời gian cần thống kê không được để trống']
                ]);
            }

            if ($request['endYear'] < $request['startYear']) {
                return response()->json([
                    'error' => ['Thời gian kết thúc phải lớn hơn hoặc bằng thời gian bắt đầu']
                ]);
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
                $whereClause .= "'". $val ."'";
                if ($key < count($arrType) - 1) {
                    $whereClause .= ",";
                } else {
                    $whereClause .= ")";
                }
            }

            $whereClause .= ' AND class.zetc_type = 1 AND zManufacturer.zsym IS NOT NULL AND zlaboratoryDate >= ' . strtotime($request['startYear'] . '-01-01') . ' AND zlaboratoryDate < ' . strtotime("+1 years", strtotime($request['endYear'] . '-01-01'));
            $reports = getDataFromService('doSelect', 'nr', [
                'zresultEnd_chk',
                'zlaboratoryDate',
                'zManufacturer.zsym'
            ], $whereClause, 100);

            if(empty($reports)) {
                return response()->json([
                    'error' => ['Không tìm thấy dữ liệu thống kê thỏa mãn']
                ]);
            }

            $dataArr = $chartDataArr = [];
            foreach ($reports as $key => $report) {
                $reports[$key]['zlaboratoryDate'] = !empty($report['zlaboratoryDate']) ? date('Y', $report['zlaboratoryDate']) : '';
                $reports[$key]['zresultEnd_chk'] = @$report['zresultEnd_chk'] ? 'Đạt' : '';

                if (!isset($dataArr[$reports[$key]['zlaboratoryDate'] . '__' . $reports[$key]['zManufacturer.zsym']])) {
                    $dataArr[$reports[$key]['zlaboratoryDate'] . '__' . $reports[$key]['zManufacturer.zsym']] = [
                        'total' => 0,
                        'pass' => 0,
                        'failed' => 0
                    ];
                }

                if (!isset($chartDataArr[$reports[$key]['zManufacturer.zsym']])) {
                    $chartDataArr[$reports[$key]['zManufacturer.zsym']] = [
                        'pass' => 0,
                        'failed' => 0,
                        'total' => 0
                    ];
                }

                $dataArr[$reports[$key]['zlaboratoryDate'] . '__' . $reports[$key]['zManufacturer.zsym']]['total'] += 1;
                $chartDataArr[$reports[$key]['zManufacturer.zsym']]['total'] += 1;
                if ($reports[$key]['zresultEnd_chk'] == 'Đạt') {
                    $dataArr[$reports[$key]['zlaboratoryDate'] . '__' . $reports[$key]['zManufacturer.zsym']]['pass'] += 1;
                    $chartDataArr[$reports[$key]['zManufacturer.zsym']]['pass'] += 1;
                } else {
                    $dataArr[$reports[$key]['zlaboratoryDate'] . '__' . $reports[$key]['zManufacturer.zsym']]['failed'] += 1;
                    $chartDataArr[$reports[$key]['zManufacturer.zsym']]['failed'] += 1;
                }
            }

            // Calculate percentage of each manufacture in separate year and sort it desc
            foreach ($dataArr as $key => $val) {
                $arr = [
                    'year' => !empty(explode('__', $key)[0]) ? explode('__', $key)[0] : '',
                    'manufacture' => !empty(explode('__', $key)[1]) ? explode('__', $key)[1] : '',
                    'pass' => $val['pass'],
                    'failed' => $val['failed'],
                    'total' => $val['total'],
                    'percentage' => round($val['total'] / count($reports) * 100, 2)
                ];
                unset($dataArr[$key]);
                array_push($dataArr, $arr);
            }
            usort($dataArr, function($a, $b) {
                return $b['percentage'] <=> $a['percentage'];
            });

            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);

            // Read Template Excel
            $template = storage_path('templates_excel/phan_xuong_co_dien/bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-theo-nha-cung-cap/bao-cao-doanh-so-giua-cac-nha-san-xuat-' . count($chartDataArr) . '.xlsx');
            $spreadsheet = $reader->load($template);

            // Sheet data
            $statisticsSheet = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THONG_KE");
            $chartDataSheet = $spreadsheet->setActiveSheetIndexByName("THONG_KE_THEO_HANG");
            $chartSheet = $spreadsheet->setActiveSheetIndexByName("BIEU_DO");

            // Insert statistics data
            sortData($dataArr, 'desc', 'year');
            $years = collect($dataArr)->groupBy('year')->toArray();
            foreach($years as $year => $values){
                $years[$year] = count($values);
            }
            foreach ($dataArr as $key => $val) {
                $val = array_values($val);
                $count = 0;
                $statisticsSheet->getCell('A' . ($key + 5))->setValue($key + 1);
                for ($c = 'B'; $c != 'H'; ++$c) {
                    $statisticsSheet->getCell($c . ($key + 5))->setValue($val[$count] . ($c == 'G' ? '%' : ''));
                    $count++;
                }
            }
            
            $statisticsSheet->getCell('A' . (count($dataArr) + 5))->setValue(count($dataArr) + 1);
            $statisticsSheet->getCell('C' . (count($dataArr) + 5))->setValue('Tổng số');
            $statisticsSheet->getCell('F' . (count($dataArr) + 5))->setValue(count($reports));
            $statisticsSheet->getCell('G' . (count($dataArr) + 5))->setValue('100%');
            // merge cell same year
            $startIndex = $endIndex = 5;
            foreach(array_values($years) as $key => $val){
                $endIndex += $val - ($key == 0 ? 1 : 0);
                $statisticsSheet->mergeCells("B{$startIndex}:B{$endIndex}")->getStyle("B{$startIndex}:B{$endIndex}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ]
                ]);
                $startIndex += $val;
            }
            $columns = ['C', 'D'];
            $inputs = [
                'Năm bắt đầu thống kê: ' => 'startYear',
                'Năm kết thúc thống kê: ' => 'endYear'
            ];
            fillSearchRequestToExcel($statisticsSheet, $request, $columns, $inputs, 1);
            alignmentExcel($statisticsSheet, 'C6:C1000');
            $statisticsSheet->getCell('G5')->setvalue('Tỷ lệ %');
            // Insert chart data
            $count = 3;
            foreach ($chartDataArr as $key => $val) {
                $chartDataSheet->getCell('B' . $count)->setValue($key);
                $chartDataSheet->getCell('C' . $count)->setValue(round($val['total'] / count($reports) * 100, 2));
                $count++;
            }

            $url = writeExcel($spreadsheet, 'bao-cao-doanh-so-giua-cac-nha-san-xuat-' . time() . '.xlsx');

            return response()->json([
                'success' => true,
                'url' => $url
            ]);
        } catch (\Exception $ex) {
            Log::error('[Electromechanical] Get manufacturers sales report preview: Fail | Reason: ' . $ex->getMessage() . '. ' . $ex->getFile() . ':' . $ex->getLine() . '| Params: ' . json_encode($request->all()));

            return response()->json([
                'error' => [$ex->getMessage()]
            ]);
        }
    }

    /**
     * Figures for each unit index
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function figuresForEachUnit(Request $request)
    {
        $units = getDataFromService('doSelect', 'zDVQL', [], '', 100);
        sortData($units);
        return view('electromechanical::figuresForEachUnit.index', compact('units'));
    }

    /**
     * Figures for each unit preview
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function figuresForEachUnitPreview(Request $request)
    {
        try {
            $result = figuresForEachUnitData($request->all());

            if (!empty($result['error'])) {
                return response()->json([
                    'error' => [$result['error']]
                ]);
            }

            $html = view('electromechanical::figuresForEachUnit.data')->with('dataArr', $result['dataArr'])->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Exception $ex) {
            Log::error('[Electromechanical] Get figures for each unit report preview: Fail | Reason: '.$ex->getMessage().'. '.$ex->getFile().':'.$ex->getLine().'| Params: '.json_encode($request->all()));

            return response()->json([
                'error' => [$ex->getMessage()]
            ]);
        }
    }

    /**
     * Figures for each unit export
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function figuresForEachUnitExport(Request $request)
    {
        return Excel::download(new FiguresForEachUnitExport($request->all()), 'Báo cáo theo đơn vị sử dụng - Bảng số liệu cho từng đơn vị.xlsx');
    }

    /**
     * Test results report
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function testResultsReport()
    {
        $manufactures = getDataFromService('doSelect', 'zManufacturer', ['id', 'zsym'], 'zsym IS NOT NULL AND zclass = 1002851', 300);
        sortData($manufactures);
        return view('electromechanical::testResultsReport.index', compact('manufactures'));
    }

    /**
     * Test results report preview
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testResultsReportPreview(Request $request)
    {
        try {
            if (empty($request['startDate']) || empty($request['endDate'])) {
                return response()->json([
                    'error' => ['Thời gian cần thống kê không được để trống']
                ]);
            }

            if ($request['endDate'] < $request['startDate']) {
                return response()->json([
                    'error' => ['Thời gian kết thúc phải lớn hơn hoặc bằng thời gian bắt đầu']
                ]);
            }

            // Get report type
            $allTypeArr = [
                config('constant.electromechanical.device.type.cap_luc_trung_the'),
                config('constant.electromechanical.device.type.cap_van_xoan'),
                config('constant.electromechanical.device.type.cap_boc_trung_ap'),
                config('constant.electromechanical.device.type.cap_boc_ha_ap'),
                config('constant.electromechanical.device.type.day_dan_tran'),
            ];

            $types = $request['type'];
            if (empty($request['type'])) {
                $types = $allTypeArr;
            }
            $arrType = getReportFromType($types);

            // Get report
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
            // CA update => zManufacturer has id but not has name => index zManufacturer.zsym does not exist
            $whereClause .= ' AND class.zetc_type = 1 AND zManufacturer.zsym IS NOT NULL AND zlaboratoryDate >= ' . strtotime($request['startDate']) . ' AND zlaboratoryDate < ' . strtotime("+1 day", strtotime($request['endDate']));
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

            $reports = getDataFromService('doSelect', 'nr', [
                'id',
                'name',
                'zresultEnd_chk',
                'zlaboratoryDate',
                'zManufacturer.zsym',
                'class.type',
            ], $whereClause, 100);
            if(empty($reports)) {
                return response()->json([
                    'error' => ['Không tìm thấy dữ liệu thống kê thỏa mãn']
                ]);
            }

            // Group report by type
            $groupReportByTypeArr = [];
            foreach ($reports as $report) {
                if (getTypeFromReport($report['class.type']) == config('constant.electromechanical.device.type.cap_boc_ha_ap')) {
                    $groupReportByTypeArr[config('constant.electromechanical.device.type.cap_boc_ha_ap')][$report['class.type']][] = $report;
                    continue;
                }
                if (getTypeFromReport($report['class.type']) == config('constant.electromechanical.device.type.cap_boc_trung_ap')) {
                    $groupReportByTypeArr[config('constant.electromechanical.device.type.cap_boc_trung_ap')][$report['class.type']][] = $report;
                    continue;
                }
                if (getTypeFromReport($report['class.type']) == config('constant.electromechanical.device.type.cap_luc_trung_the')) {
                    $groupReportByTypeArr[config('constant.electromechanical.device.type.cap_luc_trung_the')][$report['class.type']][] = $report;
                    continue;
                }
                if (getTypeFromReport($report['class.type']) == config('constant.electromechanical.device.type.cap_van_xoan')) {
                    $groupReportByTypeArr[config('constant.electromechanical.device.type.cap_van_xoan')][$report['class.type']][] = $report;
                    continue;
                }
                if (getTypeFromReport($report['class.type']) == config('constant.electromechanical.device.type.day_dan_tran')) {
                    $groupReportByTypeArr[config('constant.electromechanical.device.type.day_dan_tran')][$report['class.type']][] = $report;
                }
            }

            // Get extend info of each record
            if(env('FLAG_CACHE')){
                $groupReportByTypeArr = Cache::remember('group_report_by_type_arr__' . md5($whereClause), 7200, function () use ($groupReportByTypeArr) {
                    return $this->groupReportByTypeArr($groupReportByTypeArr);
                });
            }else{
                $groupReportByTypeArr = $this->groupReportByTypeArr($groupReportByTypeArr);
            }
            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);

            // Read Template Excel
            $template = storage_path('templates_excel/phan_xuong_co_dien/bao-cao-thong-ke-cap-va-day-dan-da-thi-nghiem/bao-cao-ket-qua-thi-nghiem/bao-cao-ket-qua-thi-nghiem.xlsx');
            $spreadsheet = $reader->load($template);

            foreach ($groupReportByTypeArr as $groupKey => $groupReportType) {
                $reportArr = $chartData = [];

                // Format data to insert excel
                foreach ($groupReportType as $reportType) {
                    foreach ($reportType as $report) {
                        // First init
                        if (empty($reportArr[$report['zManufacturer.zsym']])) {
                            $reportArr[$report['zManufacturer.zsym']] = [
                                'pass' => 0,
                                'failed' => 0,
                                'total' => 0,
                                'otherInfo' => [],
                                'detailOtherInfo' => []
                            ];
                            for ($i = 0;$i < count($report['extendInfo']); $i++) {
                                array_push($reportArr[$report['zManufacturer.zsym']]['otherInfo'], 0);
                                $reportArr[$report['zManufacturer.zsym']]['detailOtherInfo'][$i] = [
                                    'pass' => 0,
                                    'failed' => 0
                                ];
                            }
                        }

                        // Calculate data for chart data sheet
                        foreach ($report['extendInfo'] as $extendInfoKey => $extendInfo) {
                            if ($extendInfo == 1 || $extendInfo == 400001) {
                                $reportArr[$report['zManufacturer.zsym']]['otherInfo'][$extendInfoKey] += 1;
                                $reportArr[$report['zManufacturer.zsym']]['detailOtherInfo'][$extendInfoKey]['pass'] += 1;
                            } else {
                                $reportArr[$report['zManufacturer.zsym']]['detailOtherInfo'][$extendInfoKey]['failed'] += 1;
                            }
                        }

                        // Calculate data for statistics sheet
                        $reportArr[$report['zManufacturer.zsym']]['total'] += 1;
                        if ( !empty($report['zresultEnd_chk']) ) {
                            $reportArr[$report['zManufacturer.zsym']]['pass'] += 1;
                        } else {
                            $reportArr[$report['zManufacturer.zsym']]['failed'] += 1;
                        }
                    }
                }

                // Sheet data
                $statisticsSheet = $spreadsheet->setActiveSheetIndexByName($groupKey . " DL");
                $chartDataSheet = $spreadsheet->setActiveSheetIndexByName($groupKey. " BĐ_DATA");
                $spreadsheet->setActiveSheetIndexByName($groupKey . " BĐ");

                // Insert statistics data
                $count = 0;
                foreach ($reportArr as $key => $val) {
                    if ($count == 0) {
                        foreach ($val['detailOtherInfo'] as $info) {
                            array_push($chartData, [
                                'pass' => 0,
                                'failed' => 0
                            ]);
                        }
                    }

                    $statisticsSheet->getCell('A' . ($count + 5))->setValue($count + 1);
                    $statisticsSheet->getCell('B' . ($count + 5))->setValue($key);

                    $c = 'C';
                    for ($i = 0; $i < count($val['otherInfo']); $i++) {
                        $statisticsSheet->getCell($c . ($count + 5))->setValue($val['otherInfo'][$i]);
                        ++$c;
                    }
                    $statisticsSheet->getCell($c . ($count + 5))->setValue($val['pass']);
                    $statisticsSheet->getCell(++$c . ($count + 5))->setValue($val['failed']);
                    $statisticsSheet->getCell(++$c . ($count + 5))->setValue($val['total']);

                    foreach ($val['detailOtherInfo'] as $infoKey => $info) {
                        $chartData[$infoKey]['pass'] += $info['pass'];
                        $chartData[$infoKey]['failed'] += $info['failed'];
                    }
                    $count++;
                }

                // Insert chart data
                $count = 7;
                foreach ($chartData as $val) {
                    $chartDataSheet->getCell('C' . $count)->setValue($val['pass']);
                    $chartDataSheet->getCell('D' . $count)->setValue($val['failed']);
                    $count++;
                }
            }

            // Remove unneeded sheet
            foreach ($allTypeArr as $type) {
                if (in_array($type, array_keys($groupReportByTypeArr))) {
                    $spreadsheet->setActiveSheetIndexByName($type . " DL")->getColumnDimension('B')->setWidth(40);
                    alignmentExcel($spreadsheet->setActiveSheetIndexByName($type . " DL"), 'b5:B1000');
                    continue;
                }
                $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($spreadsheet->getSheetByName($type . " DL")));
                $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($spreadsheet->getSheetByName($type . " BĐ")));
                $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($spreadsheet->getSheetByName($type . " BĐ_DATA")));
            }
            foreach($allTypeArr as $type){
                $sheetActive = $spreadsheet->getSheetByName($type . " DL");
                if (in_array($type, array_keys($groupReportByTypeArr))) {
                    break;
                }
            }

            if ( isset($sheetActive) ) {
                $columns = range('B', 'E');
                $requestArr = formatRequest($request);
                $inputs = [
                    'Thời gian bắt đầu: ' => 'startDate',
                    'Thời gian kết thúc: ' => 'endDate',
                    'Hãng sản xuất: ' => 'zManufacturer',
                    'Loại dây dẫn: ' => 'type',
                ];
                fillSearchRequestToExcel($sheetActive, $requestArr, $columns, $inputs, 2);
            }
            $url = writeExcel($spreadsheet, 'bao-cao-ket-qua-thi-nghiem-mau-cap-va-day-dan-da-thi-nghiem-' . time() . '.xlsx');

            return response()->json([
                'success' => true,
                'url' => $url
            ]);
        } catch (\Exception $ex) {
            Log::error('[Electromechanical] Get test result report preview: Fail | Reason: ' . $ex->getMessage() . '. ' . $ex->getFile() . ':' . $ex->getLine() . '| Params: ' . json_encode($request->all()));

            return response()->json([
                'error' => [$ex->getMessage()]
            ]);
        }
    }
    // handle cache flag for function testResultsReportPreview
    private function groupReportByTypeArr(array $groupReportByTypeArr)
    {
        foreach ($groupReportByTypeArr as $groupKey => $groupReportType) {
            foreach ($groupReportType as $reportTypeKey => $reportType) {
                $obj = 'zPXCD' . explode('.', $reportType[0]['class.type'])[0];
                $extendAttrs = getAttrFromReportType($reportType[0]['class.type']);
                foreach ($reportType as $reportKey => $report) {
                    $extendInfo = getDataFromService('doSelect', $obj, array_values(array_filter($extendAttrs)), "id = U'" . $report['id'] . "'");
                    foreach ($extendAttrs as $extendAttr) {
                        $groupReportByTypeArr[$groupKey][$reportTypeKey][$reportKey]['extendInfo'][] = !empty($extendInfo[$extendAttr]) ? $extendInfo[$extendAttr] : '';
                    }
                }
            }
        }
        return $groupReportByTypeArr;
    }

    /**
     * Device test report
     *
     * @param $class
     * @param null $type
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function deviceTestReport($class, $type = null)
    {
        return view('electromechanical::deviceTestReport.index', compact('class', 'type'));
    }

    /**
     * Device test report preview
     *
     * @param Request $request
     * @param $class
     * @param null $type
     * @return \Illuminate\Http\JsonResponse
     */
    public function deviceTestReportPreview(Request $request, $class, $type = null)
    {
        try {
            if (empty($request['startYear']) || empty($request['endYear'])) {
                return response()->json([
                    'error' => ['Thời gian cần thống kê không được để trống']
                ]);
            }

            if ($request['endYear'] < $request['startYear']) {
                return response()->json([
                    'error' => ['Thời gian kết thúc phải lớn hơn hoặc bằng thời gian bắt đầu']
                ]);
            }

            // Get report
            $whereClause = "class.zetc_type = 1 AND zCI_Device.class.type LIKE '%" . $class . "%' AND zlaboratoryDate >= " . strtotime($request['startYear'] . '-01-01') . " AND zlaboratoryDate < " . strtotime("+1 years", strtotime($request['endYear'] . "-01-01"));

            if (!empty($type)) {
                $whereClause .= " AND zCI_Device_Type.active_flag = 1 AND zCI_Device_Type.zsym LIKE '%" . $type . "%'";
            }

            $reports = [];
            for ($i = 0; $i <= 3; $i++) {
                $whereClause .= " AND zExperimenter" . ($i != 0 ? $i : "") . ".dept = 1000001";

                $reportsByDepartment = getDataFromService('doSelect', 'nr', [
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
                return response()->json([
                    'error' => ['Không tìm thấy dữ liệu thống kê thỏa mãn']
                ]);
            }

            $monthDataArr = $yearDataArr = [];
            for ($i = (int)$request['startYear']; $i <= (int)$request['endYear']; $i++) {
                $yearDataArr[$i] = [];
                for ($j = 1; $j <= 12; $j++) {
                    $monthDataArr[$i][$j] = [];
                    foreach ($reports as $key => $report) {
                        if (date('Y-m', $report['zlaboratoryDate']) == $i . '-' . ($j < 10 ? '0' . $j : $j)) {
                            $monthDataArr[$i][$j][] = $yearDataArr[$i][] = $report;
                        }
                    }
                }
            }

            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);

            // Read Template Excel
            $template = storage_path('templates_excel/phan_xuong_co_dien/bao-cao-thi-nghiem-thiet-bi-le/bao-cao-thi-nghiem-thiet-bi-le-' . ((int)$request['endYear'] - (int)$request['startYear'] + 1) . '.xlsx');
            $spreadsheet = $reader->load($template);

            // Sheet data
            $chartDataSheet1 = $spreadsheet->setActiveSheetIndexByName("DATA_BĐ1");
            $chartDataSheet2 = $spreadsheet->setActiveSheetIndexByName("DATA_BĐ2");
            $chartSheet = $spreadsheet->setActiveSheetIndexByName("BIEU_DO");

            // Set chart sheet info
            $chartSheet->getCell('B3')->setValue('BÁO CÁO THÍ NGHIỆM THIẾT BỊ LẺ - BÁO CÁO THÍ NGHIỆM ' . (!empty($type) ? mb_strtoupper($type) : mb_strtoupper($class)));
            $chartSheet->getCell('B6')->setValue('Biểu đồ thống kê số lượng ' . (!empty($type) ? $type : $class) . ' đã làm theo từng tháng');
            $chartSheet->getChartByName('chart1')->setTitle(new \PhpOffice\PhpSpreadsheet\Chart\Title('Biểu đồ thống kê số lượng máy ' . (!empty($type) ? $type : $class) . ' đã làm theo từng tháng'));
            $chartSheet->getCell('B32')->setValue('Biểu đồ thống kê số lượng ' . (!empty($type) ? $type : $class) . ' đã làm từ năm ' . (int)$request['startYear'] . ' đến năm ' . (int)$request['endYear']);
            $chartSheet->getChartByName('chart8')->setTitle(new \PhpOffice\PhpSpreadsheet\Chart\Title('Biểu đồ thống kê số lượng ' . (!empty($type) ? $type : $class) . ' đã làm từ năm ' . (int)$request['startYear'] . ' đến năm ' . (int)$request['endYear']));

            // Insert data into chart data sheet 1
            $chartDataSheet1->getCell('A2')->setValue('BÁO CÁO THÍ NGHIỆM THIẾT BỊ LẺ - BÁO CÁO THÍ NGHIỆM ' . (!empty($type) ? mb_strtoupper($type) : mb_strtoupper($class)));
            $chartDataSheet1->getCell('A4')->setValue('Biểu đồ thống kê số lượng ' . (!empty($type) ? $type : $class) . ' đã làm theo từng tháng');
            $count = 7;
            foreach ($monthDataArr as $key => $val) {
                $chartDataSheet1->getCell('B' . $count)->setValue('Năm ' . $key);
                foreach ($val as $item) {
                    $index = 1;
                    for ($c = 'C'; $c != 'O'; ++$c) {
                        $chartDataSheet1->getCell($c . $count)->setValue(count($val[$index]));
                        $index++;
                    }
                }
                $count++;
            }

            // Insert data into chart data sheet 2
            $chartDataSheet2->getCell('A2')->setValue('BÁO CÁO THÍ NGHIỆM THIẾT BỊ LẺ - BÁO CÁO THÍ NGHIỆM ' . (!empty($type) ? mb_strtoupper($type) : mb_strtoupper($class)));
            $chartDataSheet2->getCell('A4')->setValue('Biểu đồ thống kê số lượng ' . (!empty($type) ? $type : $class) . ' đã làm từ năm ' . (int)$request['startYear'] . ' đến năm ' . (int)$request['endYear']);
            $count = 7;
            foreach ($yearDataArr as $key => $val) {
                $chartDataSheet2->getCell('B' . $count)->setValue('Năm ' . $key);
                $chartDataSheet2->getCell('C' . $count)->setValue(count($val));
                $count++;
            }
            $columns = ['E', 'F'];
            $inputs = [
                'Năm bắt đầu: ' => 'startYear',
                'Năm kết thúc: ' => 'endYear',
            ];
            fillSearchRequestToExcel($chartSheet, $request, $columns, $inputs, 1, true);
            $chartSheet->getStyle('B6')->getAlignment()->setWrapText(false);
            $chartSheet->getStyle('B32')->getAlignment()->setWrapText(false);

            $title = Str::slug($type ?? $class, '-');
            $url = writeExcel($spreadsheet, 'bao-cao-thi-nghiem-thiet-bi-le-' . $title . time() . '.xlsx');

            return response()->json([
                'success' => true,
                'url' => $url
            ]);
        } catch (\Exception $ex) {
            Log::error('[Electromechanical] Get device test report preview: Fail | Reason: ' . $ex->getMessage() . '. ' . $ex->getFile() . ':' . $ex->getLine() . '| Params: ' . json_encode($request->all()));

            return response()->json([
                'error' => [$ex->getMessage()]
            ]);
        }
    }

    /**
     * Number of experiments report
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function numberOfExperimentsReport()
    {
        return view('electromechanical::numberOfExperimentsReport.index');
    }

    /**
     * Number of experiments report preview
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function numberOfExperimentsReportPreview(Request $request)
    {
        try {
            $result = numberOfExperimentsReportData($request->all());

            if (!empty($result['error'])) {
                return response()->json([
                    'error' => [$result['error']]
                ]);
            }

            $html = view('electromechanical::numberOfExperimentsReport.data')->with('dataArr', $result['dataArr'])->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Exception $ex) {
            Log::error('[Electromechanical] Get number of experiments report preview: Fail | Reason: '.$ex->getMessage().'. '.$ex->getFile().':'.$ex->getLine().'| Params: '.json_encode($request->all()));

            return response()->json([
                'error' => [$ex->getMessage()]
            ]);
        }
    }

    /**
     * Number of experiments export

     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function numberOfExperimentsExport(Request $request)
    {
        return Excel::download(new NumberOfExperimentsExport($request->all()), 'Báo cáo thí nghiệm thiết bị lẻ - Báo cáo số lượng thí nghiệm trên từng loại thiết bị .xlsx');
    }
}
