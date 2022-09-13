<?php

namespace Modules\Report\Http\Controllers;

use App\Services\CAWebServices;
use Carbon\Carbon;
use GreenCape\Xml\Converter;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpWord\Shared\ZipArchive;
use Illuminate\Support\Facades\Log;
use App\Exports\OilQualityReportExport;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    // public function index()
    // {
    //     return view('report::index');
    // }

    function hoa_dau__bien_ban_khi_hoa_tan_trong_dau_cach_dien(Request $request)
    {
        $request = $request->all();
        $request['type'] = 'Biên Bản Thử Nghiệm Khí Hoà Tan Trong Dầu Cách Điện';
        $items = showOilIndex($request['type'], $request);
        return view('report::reports.report1', compact('items'));
    }

    public function export($id) {
        $excel = $this->generateExcel($id);
        return redirect($excel);
    }

    public function preview($id) {
        $excel = $this->generateExcel($id);
        return view('report::reports.preview', compact('excel', 'id'));
    }

    function generateExcel($handleId)
    {
        $user = session()->get(env('AUTH_SESSION_KEY'));
        $service = new CAWebServices(env('CA_WSDL_URL'));
        $payload = [
            'sid' => $user['ssid'],
            'objectHandle' => 'nr:' . $handleId,
            'attributes' => [
                'name',
                'zCI_Device.name',
                'zsamplingDate',
                'zpointSampling',
                'zresultEnd',
                'zCI_Device.zrefnr_dvql.zsym'
            ]
        ];
        $resp = $service->callFunction('getObjectValues', $payload);


        $parser = new Converter($resp->getObjectValuesReturn);
        foreach ($parser->data['UDSObject'][1]['Attributes'] as $attr) {
            $nrObj[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
        }
        $resp = $service->callFunction('getObjectValues', [
            'sid' => $user['ssid'],
            'objectHandle' => 'zHD:' . $handleId,
            'attributes' => []
        ]);

        $parser = new Converter($resp->getObjectValuesReturn);
        foreach ($parser->data['UDSObject'][1]['Attributes'] as $attr) {
            if (!$attr['Attribute'][1]['AttrValue']) {
                continue;
            }
            $zHDObj[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
        }
        $template = storage_path('excel_templates/hoa_dau/khi_hoa_tan/hoa_dau__khi_hoa_tan_tmpl.xlsx');
        $sharedStringXmlTemplate = storage_path('excel_templates/hoa_dau/khi_hoa_tan/sharedStrings.xml');
        $sheet1XmlTemplate = storage_path('excel_templates/hoa_dau/khi_hoa_tan/sheet1.xml');
        $workbookXmlTemplate = storage_path('excel_templates/hoa_dau/khi_hoa_tan/workbook.xml');
        $sheet1Data = [
            'padi_h2' => @$zHDObj['zHydro'],
            'padi_ch4' => @$zHDObj['zMetan'],
            'padi_c2h4' => @$zHDObj['zEtylen'],
            'padi_c2h6' => @$zHDObj['zEtan'],
            'padi_c2h2' => @$zHDObj['zAcetylen'],
            'padi_co' => @$zHDObj['zMonoxyd_Cacbon'],
            'padi_co2' => @$zHDObj['zDioxyd_Cacbon'],
        ];
        $sharedStringData = [
            'padi_dia_diem' => $nrObj['zCI_Device.zrefnr_dvql.zsym'] ?? '',
            'padi_ten_thiet_bi' => $nrObj['zCI_Device.name'] ?? '',
            'padi_ngay_lay_mau' => $nrObj['zsamplingDate'] ? (date('d/m/Y', $nrObj['zsamplingDate'])) : '',
            'padi_vi_tri_lay_mau' => $nrObj['zpointSampling'] ?? '',
            'padi_danh_gia' => $nrObj['zresultEnd'] ?? ''
        ];

        $sheet1Content = strtr(file_get_contents($sheet1XmlTemplate), $sheet1Data);
        $sharedStringContent = strtr(file_get_contents($sharedStringXmlTemplate), $sharedStringData);
        $outputDir = public_path('export');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }
        $output = $outputDir . '/bao-cao-phan-tich-ket-qua-khi-hoa-tan-trong-dau-cach-dien-' . time() . '.xlsx';
        if (is_file($output)) {
            unlink($output);
        }

        copy($template, $output);
        $zip = new ZipArchive();
        if (!$zip->open($output) === TRUE) {
            return Response::json(['error' => "Can't open zip file"]);
        }
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet1Content);
        $zip->addFromString('xl/sharedStrings.xml', $sharedStringContent);
        $zip->addFromString('xl/workbook.xml', file_get_contents($workbookXmlTemplate));
        $zip->close();
        chmod($output,0777);
        return str_replace(public_path(''), getenv('APP_URL'), $output);
    }

    public function oltcAnalytic(Request $request)
    {
        $request = $request->all();
        $request['type'] = 'Biên Bản Thử Nghiệm Khí Hoà Tan Trong Dầu Cách Điện';
        $items = showOilIndex($request['type'], $request);
        return view('report::oltc.oltc', compact('items', 'request'));
    }

    public function oltcAnalytic_export($id) {
        $excel = $this->generateExportOltc($id);
        return redirect($excel);
    }

    public function oltcAnalytic_preview($id) {
        $excel = $this->generateExportOltc($id);
        return view('report::oltc.preview', compact('excel', 'id'));
    }

    function generateExportOltc($handleId)
    {
        $user = session()->get(env('AUTH_SESSION_KEY'));
        $service = new CAWebServices(env('CA_WSDL_URL'));
        $payload = [
            'sid' => $user['ssid'],
            'objectHandle' => 'nr:' . $handleId,
            'attributes' => [
                'name',
                'zCI_Device.name',
                'zsamplingDate',
                'zpointSampling',
                'zresultEnd',
                'zrefnr_dvql.zsym',
            ]
        ];
        $resp = $service->callFunction('getObjectValues', $payload);
        $parser = new Converter($resp->getObjectValuesReturn);
        foreach ($parser->data['UDSObject'][1]['Attributes'] as $attr) {
            $nrObj[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
        }
        foreach ($nrObj as $key => $value){
            if(!empty($nrObj['zsamplingDate'])){
                $nrObj['date_sync'] = date('d/m/Y', $nrObj['zsamplingDate']);
            }
        }
        $resp = $service->callFunction('getObjectValues', [
            'sid' => $user['ssid'],
            'objectHandle' => 'zHD:' . $handleId,
            'attributes' => []
        ]);
        $parser = new Converter($resp->getObjectValuesReturn);
        foreach ($parser->data['UDSObject'][1]['Attributes'] as $attr) {
            if (!$attr['Attribute'][1]['AttrValue']) {
                continue;
            }
            $zHDObj[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
        }

        $template = storage_path('templates_excel/hoa_dau/bao-cao-phan-tich-oltc/oltc_bao_cao_hoa_dau.xlsx');
        $sharedStringXmlTemplate = storage_path('templates_excel/hoa_dau/bao-cao-phan-tich-oltc/sharedStrings.xml');
        $sheet2XmlTemplate = storage_path('templates_excel/hoa_dau/bao-cao-phan-tich-oltc/sheet2.xml');
        $workbookXmlTemplate = storage_path('templates_excel/hoa_dau/bao-cao-phan-tich-oltc/workbook.xml');

        $sheet2Data = [
            'padi_a10' => @$zHDObj['zHydro']*1.0,
            'padi_a11' => @$zHDObj['zMetan']*1.0,
            'padi_a12' => @$zHDObj['zEtylen']*1.0,
            'padi_a13' => @$zHDObj['zEtan']*1.0,
            'padi_a14' => @$zHDObj['zAcetylen']*1.0,
            'padi_a15' => @$zHDObj['zMonoxyd_Cacbon']*1.0,
        ];

        $sharedStringData = [
            'padi_c6' => @$nrObj['date_sync'],
            'padi_c5' => @$nrObj['zpointSampling'],
            'padi_c4' => @$nrObj['zCI_Device.name'],
            'padi_c3' => @$nrObj['zrefnr_dvql.zsym'],
            'padi_d22' => @$nrObj['zresultEnd'],
        ];

        $sheet2Content = strtr(file_get_contents($sheet2XmlTemplate), $sheet2Data);
        $sharedStringContent = strtr(file_get_contents($sharedStringXmlTemplate), $sharedStringData);
        $outputDir = public_path('excel');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }
        $output = $outputDir . '/bao-cao-phan-tich-oltc-' . time() . '.xlsx';
        if (is_file($output)) {
            unlink($output);
        }

        copy($template, $output);
        $zip = new ZipArchive();
        if (!$zip->open($output) === TRUE) {
            return Response::json(['error' => "Can't open zip file"]);
        }
        $zip->addFromString('xl/worksheets/sheet2.xml', $sheet2Content);
        $zip->addFromString('xl/sharedStrings.xml', $sharedStringContent);
        $zip->addFromString('xl/workbook.xml', file_get_contents($workbookXmlTemplate));
        $zip->close();
        chmod($output, 0777);
        return str_replace(public_path(''), getenv('APP_URL'), $output);
    }

    /**
     * Array device report petrochemical
     *
     * @return array
     */
    private function arrayDeviceReportPetrochemical()
    {
        return [
            'Máy biến áp',
            'Máy cắt',
            'OLTC',
        ];
    }
    /**
     * View index device report by year and manufacturer
     *
     * @return view()
     */
    public function deviceReport()
    {
        $deviceTypes = $this->arrayDeviceReportPetrochemical();
        return view('report::deviceReport.index', compact('deviceTypes'));
    }

    /**
     * Device report export by year and manufacturer
     * @param Request $request
     * @return Response::json()
     */
    public function deviceReportExport(Request $request)
    {
        try {
            $data = getDataDeviceReport($request);

            if( !empty($data['error'])){
                return response()->json([
                    'error' => $data['error']
                ]);
            }
            $groupByManafactureYear = $data['groupByManafactureYear'];
            $groupByManufacturer = $data['groupByManufacturer'];
            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);

            // count
            $count = $groupByManufacturer->count();
            // Read Template Excel
            $template = storage_path("templates_excel/hoa_dau/bao-cao-ti-le-theo-so-luong-thiet-bi-cua-tung-hang-san-xuat-ung-voi-nam-hoac-khoang-thoi-gian-san-xuat-{$count}.xlsx");
            $spreadsheet = $reader->load($template);

            // Sheet data
            $sheetStatistical = $spreadsheet->setActiveSheetIndexByName('DU_LIEU_THONG_KE');
            $sheetChartData = $spreadsheet->setActiveSheetIndexByName('DATA_BD');
            $sheetBD = $spreadsheet->setActiveSheetIndexByName('BIEU_DO');
            $sheetBD->getCell('B2')->setValue('BIỂU ĐỒ THỐNG KÊ TỈ LỆ THIẾT BỊ THEO NĂM SẢN XUẤT');

            // Set title for chart
            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $chartNames = $worksheet->getChartNames();
                if(in_array('chart1', $chartNames))
                {
                    $chart = $worksheet->getChartByName('chart1');

                    $titleChar = new \PhpOffice\PhpSpreadsheet\Chart\Title("Tỉ lệ");

                    $chart->setTitle($titleChar);
                }
            }

            // Fill data to sheet "DU_LIEU_THONG_KE"
            $stt = 1;
            $startIndexRow = 6;
            foreach($groupByManafactureYear as $year => $manufactureCollections) {
                foreach($manufactureCollections as $manufactureName => $collect){
                    foreach($collect as $deviceName => $deviceCount){
                        // Ignore device with value 0
                        if($deviceCount > 0){
                            // Set cell STT
                            $sheetStatistical->getCell("B{$startIndexRow}")->setValue($stt);
                            // Fill data to sheet
                            $sheetStatistical->getCell("C{$startIndexRow}")->setValue($year);
                            $sheetStatistical->getCell("D{$startIndexRow}")->setValue($manufactureName);
                            $sheetStatistical->getCell("E{$startIndexRow}")->setValue($deviceName);
                            $sheetStatistical->getCell("F{$startIndexRow}")->setValue($deviceCount.' bộ');
                            // increment stt and index row in sheet
                            $startIndexRow++;
                            $stt++;
                        }
                    }
                }
            }

            // Fill data to sheet "DATA_BD"
            $startIndexRow = 6;
            foreach($groupByManufacturer as $manufacturerName => $manufacturerCollect){
                $sheetChartData->getCell("C{$startIndexRow}")->setValue($manufacturerName);
                $sheetChartData->getCell("D{$startIndexRow}")->setValue($manufacturerCollect->count());
                $startIndexRow++;
            }

            $columns = range('C', 'E');
            $inputs = [
                'Năm bắt đầu: ' => 'startYear',
                'Năm kết thúc: ' => 'endYear',
                'Thiết bị: ' => 'device',
            ];
            fillSearchRequestToExcel($sheetStatistical, $request, $columns, $inputs, 2);
            $spreadsheet->setActiveSheetIndexByName('BIEU_DO');
            $title = 'bao-cao-ti-le-theo-so-luong-thiet-bi-cua-tung-hang-san-xuat-ung-voi-nam-hoac-khoang-thoi-gian-san-xuat-'.time().'.xlsx';
            $url = writeExcel($spreadsheet, $title);
            return response()->json([
                'success' => true,
                'url' => $url,
            ]);

        } catch (\Exception $e) {
            Log::error('[Report] Report the percentage by number of devices by each manufacturer for the year or period of manufacture: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));

            return response()->json([
                'error' => [$e->getMessage()]
            ]);
        }
    }

    /**
     * @return Factory|View
     */
    public function reportExperimentalByRegion()
    {
        $deviceTypes = $this->arrayDeviceReportPetrochemical();
        $dvqls = Cache::remember('unitList', 7200, function () {
            return getDataFromService('doSelect', 'zDVQL', ['id', 'zsym'], 'zsym IS NOT NULL AND active_flag = 1', 200);
        });
        sortData($dvqls);
        $areas = Cache::remember('list_areas', 7200, function () {
            return getDataFromService('doSelect', 'zArea', [], ' zsym IS NOT NULL AND active_flag = 1', 200);
        });
        sortData($areas);
        return view('report::reportExperimentalByRegion.index', compact('deviceTypes', 'dvqls', 'areas'));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function reportExperimentalByRegionExport(Request $request)
    {
        try {
            $reports = getDataExperimentalByRegionReport($request);
            if( !empty($reports['error']) ) {
                return response()->json([
                    'error' => $reports['error']
                ]);
            }

            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);

            // Read Template Excel
            $template = storage_path("templates_excel/hoa_dau/bao-cao-thong-ke-thi-nghiem-theo-khu-vuc.xlsx");
            $spreadsheet = $reader->load($template);

            $sheetStatistical = $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');

            $columnName = ['zlaboratoryDate', 'zCI_Device.zArea.zsym', 'zCI_Device.zrefnr_td.zsym', 'zCI_Device.zrefnr_nl.zsym', 'zCI_Device.class.type', 'zsamplingDate', 'zresultEnd'];
            $stt = 1;
            $startIndexRow = 7;
            foreach($reports as $report){
                // define table column map $columnName and recove in loop
                $table = ['D', 'E', 'F', 'G', 'H', 'I'];
                // add column by class.type
                $column = $this->getColumnNameByType($report['class.type']);
                array_push($table, $column);

                // Fill data to sheet
                $sheetStatistical->getCell("C{$startIndexRow}")->setValue($stt);
                foreach($table as $index => $columnKey){
                    $sheetStatistical->getCell($columnKey.$startIndexRow)->setValue($report[$columnName[$index]] ?? '');
                }
                $stt++;
                $startIndexRow++;
            }
            $columns = range('E', 'I');
            $inputs = [
                'Ngày bắt đầu: ' => 'startDate',
                'Ngày kết thúc: ' => 'endDate',
                'Thiết bị: ' => 'device',
                'Đơn vị quản lý: ' => 'dvqls',
                'Khu vực: ' => 'areas_name'
            ];
            $requestArr = formatRequest($request);
            fillSearchRequestToExcel($sheetStatistical, $requestArr, $columns, $inputs, 1, true);
            alignmentExcel($sheetStatistical, 'E7:H1000');
            alignmentExcel($sheetStatistical, 'J7:M1000');
            $title = 'bao-cao-thong-ke-thi-nghiem-theo-khu-vuc-'.time().'.xlsx';
            $url = writeExcel($spreadsheet, $title);
            return response()->json([
                'success' => true,
                'url' => $url,
            ]);

        } catch (\Exception $e) {
            Log::error('[Report] Report on experimental statistics by region: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));

            return response()->json([
                'error' => [$e->getMessage()]
            ]);
        }
    }
    /**
     * Get column name in sheet mapping class.type
     * @param $type
     * @return string
     */
    private function getColumnNameByType($type){
        $column = '';
        switch ($type) {
            case 'Biên Bản Thử Nghiệm Khí Hoà Tan Trong Dầu Cách Điện':
                $column = 'L';
                break;
            case 'Biên Bản Thí Nghiệm Dầu Cách Điện - OLTC':
            case 'Biên Bản Thí Nghiệm Dầu Cách Điện - Dầu máy biến áp':
            case 'Biên Bản Thí Nghiệm Dầu Cách Điện - Dầu téc':
                $column = 'K';
                break;
            case 'Biên Bản Thử Nghiệm Khí SF6':
                $column = 'M';
                break;
            case 'Biên bản thử nghiệm Furan Methanol - BM06':
                $column = 'J';
                break;
            default:
                break;
        }
        return $column;
    }

    /**
     * index Oil quality report
     *
     * @return Application|Factory|View
     */
    function oilQualityReport(Request $request)
    {
        $data_request = $request->all();
        $title = 'Báo cáo thống kê chất lượng dầu';
        $data_request['arrType'] = config('attributes.arr_oil_quality_report');
        $data = getDataOilQualityReport($data_request);
        $items = [];
        foreach ($data as $key => $value)
        {
            if( !empty($value['zCI_Device.class.type']) && in_array($value['zCI_Device.class.type'], ['OLTC', 'Máy biến áp', 'Máy biến áp phân phối']) ){
                $items[$key] = $value;
            }
        }
        $route_form = route('admin.oilQualityReport');
        $route_preview = route('admin.oilQualityReportPreview');
        $dvqls = Cache::remember('unitList', 7200, function () {
            return getDataFromService('doSelect', 'zDVQL', ['id', 'zsym'], 'zsym IS NOT NULL AND active_flag = 1', 200);
        });
        sortData($dvqls);
        $deviceTypes = $this->arrayDeviceReportPetrochemical();
        $listDeviceName = !empty($request->devices_name) ? $this->getListDeviceName($request) : [];
        return view('report::oil_quality.index', compact('items','route_form','route_preview','title', 'dvqls', 'deviceTypes', 'listDeviceName'));
    }

    /**
     * get list device by class.type
     * @param Request $request
     * @return array
     */
    private function getListDeviceName(Request $request)
    {
        $data = [];
        if( !empty($request->devices_name) ){
            $whereClause = ' class.zetc_type != 1 AND delete_flag = 0';
            foreach($request->devices_name as $key => $val){
                if ($key == 0) {
                    $whereClause .= " AND class.type IN (";
                }
                $whereClause .= "'" . $val . "'";
                if ($key < count($request->devices_name) - 1) {
                    $whereClause .= ",";
                } else {
                    $whereClause .= ")";
                }
            }
            $data = getAllDataFromService('nr', ['id', 'name'], $whereClause);
        }
        sortData($data, 'asc', 'name');
        return $data;
    }

    public function ajaxGetListDeviceName(Request $request)
    {
        try {
            $data = $this->getListDeviceName($request);
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('[Report] get list device name: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return response()->json([
                'error' => [$e->getMessage()]
            ]);
        }
    }

    /**
     * preview Oil quality report
     *
     * @param Request $request
     * @return Application|Factory|View
     * @throws \ErrorException
     */
    function oilQualityReportPreview(Request $request)
    {
        $ids = $request->get('ids');
        $excel = $this->getDataOilQuality($ids, $request);
        $title = 'Báo cáo chất lượng dầu';
        $link_preview = "https://view.officeapps.live.com/op/embed.aspx?src=".$excel;
        return view('report::gas_quality.export', compact('ids','excel','title','link_preview'));
    }

    /** get data Oil quality report
     * @param $ids
     * @return string|string[]
     * @throws \ErrorException
     * @throws Exception
     */
    private function getDataOilQuality($ids, $request)
    {
        $attr = config('attributes.atrDataOilQuality');
        $ids = explode(',', $ids);
        $reports = [];
        foreach ($ids as $key => $value){
            $obj = 'nr:' . $value;
            $reports[] = getDataFromService('getObjectValues', $obj, $attr, '', 100);
        }
        // get data assoc
        $attr = config('attributes.arr_oil_quality_report_data_assoc');
        foreach ($reports as $key => $value) {
            $where = "id = U'" . $value['id'] . "'";
            $data_obj = getDataFromService('doSelect', 'zHD', $attr, $where, 150);
            if (!empty($value['zsamplingDate'])) {
                $reports[$key]['zsamplingDate_sync'] = date('d/m/Y', $value['zsamplingDate']);
            }
            foreach ($attr as $val) {
                $reports[$key][$val] = $data_obj[0][$val] ??'';
            }
        }
        // get data type [transformer, otlc]
        $data_oltc = $data_transformer = [];
        foreach ($reports as $val){
            if(!empty($val['zCI_Device.class.type']) && $val['zCI_Device.class.type'] == 'OLTC'){
                $data_oltc[] = $val;
            }else{
                $data_transformer[] = $val;
            }
        }
        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);
        $template = storage_path("templates_excel/hoa_dau/bao-cao-chat-luong-dau/template-chat-luong-dau.xlsx");
        $spreadsheet = $reader->load($template);
        // get data transformers
        $startIndexData = 4;
        $table = config('attributes.columOilQualityTransformers');
        $tableValue = config('attributes.valueOilQualityTransformers');;
        $sheetStatistical = $spreadsheet->setActiveSheetIndexByName('DATA');
        // get data to template
        foreach ($data_transformer as $value){
            foreach ($tableValue as $key => $val){
                $sheetStatistical->getCell($table[$key].($startIndexData))->setValue($value[$val]??'');
            }
            $startIndexData++;
        }
        // get data OTLC
        $startIndexData = 105;
        $table = config('attributes.columOilQualityOtlc');
        $tableValue = config('attributes.valueOilQualityOtlc');
        foreach ($data_oltc as $value){
            foreach ($tableValue as $key => $val){
                $sheetStatistical->getCell($table[$key].($startIndexData))->setValue($value[$val]??'');
            }
            $startIndexData++;
        }
        // delete row template
        if(count($data_transformer) != 100){
            for ($i = 104; $i >= 4 + count($data_transformer); --$i){
            $sheetStatistical->removeRow($i);
            }
        }
        if(count($data_oltc) != 100){
            for ($i = 205; $i >= count($data_transformer) + 4 + count($data_oltc); --$i){
                $sheetStatistical->removeRow($i);
            }
        }
        $columns = range('C', 'G');
        $inputs = [
            'Thời gian bắt đầu cần thống kê: ' => 'from',
            'Thời gian kết thúc cần thống kê: ' => 'to',
            'Đơn vị quản lý: ' => 'dvqls',
            'Loại thiết bị: ' => 'devices_name',
            'Tên thiết bị: ' => 'listDeviceName'
        ];
        $requestArr = formatRequest($request);
        fillSearchRequestToExcel($sheetStatistical, $requestArr, $columns, $inputs, 2);
        $title = 'bao-cao-chat-luong-dau-'.time().'.xlsx';
        return writeExcel($spreadsheet, $title);
    }

    /**
     * index sf6 gas quality report
     *
     * @return Application|Factory|View
     */
    function sf6GasQualityReport(Request $request)
    {
        $data_request = $request->all();
        $data_request['arrType'] = config('attributes.arrSf6GasQuality');
        $items = getDataOilQualityReport($data_request);
        $title = 'Báo cáo chất lượng khí SF6';
        $route_form = route('admin.sf6GasQualityReport');
        $route_preview = route('admin.sf6GasQualityReportPreview');
        $dvqls = Cache::remember('unitList', 7200, function () {
            return getDataFromService('doSelect', 'zDVQL', ['id', 'zsym'], 'zsym IS NOT NULL AND active_flag = 1', 200);
        });
        sortData($dvqls);
        $deviceTypes = $this->arrayDeviceReportPetrochemical();
        $listDeviceName = !empty($request->devices_name) ? $this->getListDeviceName($request) : [];
        return view('report::oil_quality.index', compact('items','route_form','route_preview','title', 'dvqls', 'deviceTypes', 'listDeviceName'));
    }

    /**
     * preview sf6 gas quality report
     *
     * @param Request $request
     * @return Application|Factory|View
     */
    function sf6GasQualityReportPreview(Request $request)
    {
        $ids = $request->get('ids');
        $excel = $this->getDataSf6GasQuality($ids, $request);
        $title = 'Báo cáo chất lượng khí SF6';
        $link_preview = "https://view.officeapps.live.com/op/embed.aspx?src=".$excel;
        return view('report::gas_quality.export', compact('ids','excel','title','link_preview'));
    }
    /** get data Oil quality report
     * @param $ids
     * @return string|string[]
     * @throws \ErrorException
     * @throws Exception
     */
    private function getDataSf6GasQuality($ids, $request)
    {
        $attr = config('attributes.atr_sf6_gas_quality');
        $ids = explode(',', $ids);
        $reports = [];
        foreach ($ids as $key => $value){
            $obj = 'nr:' . $value;
            $reports[] = getDataFromService('getObjectValues', $obj, $attr, '', 100);
        }
        // get data assoc
        $attr = config('attributes.atr_sf6_gas_quality_assoc');
        $number = 1;
        foreach ($reports as $key => $value) {
            $where = "id = U'" . $value['id'] . "'";
            $data_obj = getDataFromService('doSelect', 'zHD', $attr, $where, 150);
            if (!empty($value['zsamplingDate'])) {
                $reports[$key]['zsamplingDate_sync'] = date('d-m-Y', $value['zsamplingDate']);
            }else{
                $reports[$key]['zsamplingDate_sync'] = '';
            }
            foreach ($attr as $val) {
                $reports[$key][$val] = $data_obj[0][$val] ??'';
            }
            // get data number
            $reports[$key]['number'] = $number;
            // get data area (Trạm điện - Ngăn lộ  (Zrefnr_td - zrefnr_nl))
            if(!empty($value['zrefnr_td.zsym'])){
                $space = ' - ';
            }else{
                $space = '';
            }
            $reports[$key]['area'] = @$value['zrefnr_td.zsym'].$space.@$value['zrefnr_nl.zsym'];
            $number++;
        }
        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);
        // Read Template Excel
        $template = storage_path('templates_excel/hoa_dau/bao-cao-chat-luong-khi-sf6/template-bao-cao-chat-luong-khi-sf6.xlsx');
        $spreadsheet = $reader->load($template);
        // Create New Writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);
        // get data export
        $atr_data = config('attributes.atr_sf6_gas_quality_export');
        $table = config('attributes.columSf6GasQuality');
        $colum_key = 6;

        foreach($reports as $value){
            foreach($atr_data as $k => $val){
                $spreadsheet->setActiveSheetIndexByName('Sheet1')->getCell($table[$k] . ($colum_key))->setValue($value[$val]??'');
            }
            $colum_key++;
        }
        if(count($reports) != 100){
            for ($i = 105; $i >= count($reports) + 6; --$i){
                $spreadsheet->setActiveSheetIndexByName('Sheet1')->removeRow($i);
            }
        }
        $this->fillSearchShareReport($spreadsheet->setActiveSheetIndexByName('Sheet1'), $request);
        $title = 'bao-cao-chat-luong-khi-sf6-'.time().'.xlsx';
        return writeExcel($spreadsheet, $title);
    }

    /**
     * @return Factory|View
     */
    public function reportStatisticalPeriodicallyPetrochemical()
    {
        $deviceTypes = $this->arrayDeviceReportPetrochemical();
        $dvqls = Cache::remember('unitList', 7200, function () {
            return getDataFromService('doSelect', 'zDVQL', ['id', 'zsym'], 'zsym IS NOT NULL AND active_flag = 1', 200);
        });
        sortData($dvqls);
        return view('report::reportStatisticalPeriodicallyPetrochemical.index', compact('deviceTypes', 'dvqls'));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function reportStatisticalPeriodicallyPetrochemicalExport(Request $request)
    {
        try {
            $data = getDataStatisticalPeriodicallyPetrochemicalReport($request);
            if( !empty($data['error']) ) {
                return response()->json([
                    'error' => $data['error']
                ]);
            }
            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);

            // Read Template Excel
            $template = storage_path("templates_excel/hoa_dau/bao-cao-thong-ke-ket-qua-thi-nghiem-dinh-ky.xlsx");
            $spreadsheet = $reader->load($template);

            $sheetStatistical = $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU');

            $table = ['C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O'];
            $columnKeys = array_merge(['area_name', 'td_name', 'nl_name'], array_keys(initDataStatisticalPeriodicallyPetrochemical()));
            $stt = 1;
            $startIndexRow = 9;
            foreach($data as $item){
                $sheetStatistical->getCell('B'.$startIndexRow)->setValue($stt);
                foreach($table as $index => $columnName){
                    $sheetStatistical->getCell($columnName.$startIndexRow)->setValue(@$item[$columnKeys[$index]]);
                }
                $startIndexRow++;
                $stt++;
            }

            $columns = range('C', 'F');
            $inputs = [
                'Ngày bắt đầu lấy mẫu: ' => 'startDate',
                'Ngày kết thúc lấy mẫu: ' => 'endDate',
                'Thiết bị: ' => 'device',
                'Đơn vị quản lý: ' => 'dvqls',
            ];
            $requestArr = formatRequest($request);
            fillSearchRequestToExcel($sheetStatistical, $requestArr, $columns, $inputs, 1);
            $title = 'bao-cao-thong-ke-ket-qua-thi-nghiem-dinh-ky-'.time().'.xlsx';
            $url = writeExcel($spreadsheet, $title);
            return response()->json([
                'success' => true,
                'url' => $url,
            ]);

        } catch (\Exception $e) {
            Log::error('[Report] Statistical reports of test results periodically: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));

            return response()->json([
                'error' => [$e->getMessage()]
            ]);
        }
    }
    /**
     * index Dissolved Gas Oil
     *
     * @param Request $request
     * @return Application|Factory|View
     */
    function reportDissolvedGasOil(Request $request)
    {
        $data_request = $request->all();
        $data_request['arrType'] = config('attributes.arrDissolvedGasOil');
        $data = getDataOilQualityReport($data_request);
        $items = [];
        // get type OLTC & biến áp
        foreach ($data as $key => $value)
        {
            if( !empty($value['zCI_Device.class.type']) && in_array($value['zCI_Device.class.type'], ['OLTC', 'Máy biến áp', 'Máy biến áp phân phối']) ){
                $items[$key] = $value;
            }
        }
        $data_items = [];
        // format data
        foreach ($items as $key => $value){
            if(empty($data_items[$value['zCI_Device.class.type'].'_'.$value['zsamplingDate_sync']])){
                $data_items[$value['zCI_Device.class.type'].'_'.$value['zsamplingDate_sync']] = $value;
            }
            if($value['class.type'] == 'Biên Bản Thử Nghiệm Khí Hoà Tan Trong Dầu Cách Điện'){
                $data_items[$value['zCI_Device.class.type'].'_'.$value['zsamplingDate_sync']]['name_1'] = $value['name'];
                $data_items[$value['zCI_Device.class.type'].'_'.$value['zsamplingDate_sync']]['id_check'] = $value['id'];
            }else{
                $data_items[$value['zCI_Device.class.type'].'_'.$value['zsamplingDate_sync']]['name_2'] = $value['name'];
            }
        }
        $title = 'Báo cáo khí hòa tan trong dầu';
        $route_form = route('admin.reportDissolvedGasOil');
        $route_preview = route('admin.reportDissolvedGasOilPreview');
        $dvqls = Cache::remember('unitList', 7200, function () {
            return getDataFromService('doSelect', 'zDVQL', ['id', 'zsym'], 'zsym IS NOT NULL AND active_flag = 1', 200);
        });
        sortData($dvqls);
        $deviceTypes = $this->arrayDeviceReportPetrochemical();
        return view('report::dissolved_gas_oil.index', compact('data_items','route_form','route_preview','title', 'dvqls', 'deviceTypes'));
    }
    /**
     * Export Dissolved Gas Oil
     *
     * @param $ids
     * @return void
     */
    function reportDissolvedGasOilPreview(Request $request)
    {
        $ids = $request->get('ids');
        $excel = $this->getDataDissolvedGasOil($ids, $request);
        $title = 'Báo cáo khí hòa tan trong dầu';
        $link_preview = "https://view.officeapps.live.com/op/embed.aspx?src=".$excel;
        return view('report::gas_quality.export', compact('ids','excel','title','link_preview'));
    }
    /** get data Oil quality report
     * @param $ids
     * @return string|string[]
     * @throws \ErrorException
     * @throws Exception
     */
    private function getDataDissolvedGasOil($ids, $request)
    {
        $attr = config('attributes.attrDissolvedGasOilTranformer');
        $ids = explode(',', $ids);
        // get data OLTC
        $reports = [];
        foreach ($ids as $key => $value){
            $obj = 'nr:' . $value;
            $reports[] = getDataFromService('getObjectValues', $obj, $attr, '', 100);
        }
        // get data assoc
        $attr = config('attributes.atrDataDissolvedGasOilAssoc');
        foreach ($reports as $key => $value) {
            $where = "id = U'" . $value['id'] . "'";
            $data_obj = getDataFromService('doSelect', 'zHD', $attr, $where, 150);
            if(!empty($value['zsamplingDate'])){
                $reports[$key]['zsamplingDate_sync'] = date('d/m/Y', $value['zsamplingDate']);
            }
            foreach ($attr as $val) {
                $reports[$key][$val] = $data_obj[0][$val] ??'';
            }
        }
        // get data methanol
        foreach ($reports as $key => $value){
            $whereClause = "class.type = 'Biên bản thử nghiệm Furan Methanol - BM06'";
            $whereClause .= "AND zCI_Device.name = '".$value['zCI_Device.name']."'";
            $data_methanol = getDataFromService('doSelect', 'nr', config('attributes.atrMethanol'), $whereClause, 100);
            if(!empty($data_methanol[0]['zYear_of_Manafacture.zsym'])){
                $reports[$key]['year_use'] = date("Y") - $data_methanol[0]['zYear_of_Manafacture.zsym'];
                $reports[$key]['zYear_of_Manafacture.zsym'] = $data_methanol[0]['zYear_of_Manafacture.zsym'];
            }
            // get data assoc
            if(!empty($data_methanol[0]['id'])){
                $where = "id = U'" . $data_methanol[0]['id'] . "'";
                $data_obj = getDataFromService('doSelect', 'zHD', config('attributes.atrMethanolAssoc'), $where, 150);
                foreach (config('attributes.atrMethanolAssoc') as $item){
                    $reports[$key][$item] = $data_obj[0][$item]??'';
                }
            }
        }
        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);
        // Read Template Excel
        $template = storage_path('templates_excel/hoa_dau/bao-cao-khi-hoa-tan-trong-dau/template-bao-cao-khi-hoa-tan-trong-dau.xlsx');
        $spreadsheet = $reader->load($template);
        // Create New Writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);

        // get data export sheet MBA
        $colum_key = $colum_key_oltc = 4;
        $range = range("A", "Z");
        foreach($reports as $value){
            // get data sheet OLTC
            if( !empty($value['zCI_Device.class.type']) && in_array($value['zCI_Device.class.type'], ['OLTC']) ){
                foreach(config('attributes.atrDissolOltcValue') as $k => $val){
                    if($val != 'break'){
                        $spreadsheet->setActiveSheetIndexByName('OLTC')->getCell($range[$k] . ($colum_key_oltc))->setValue($value[$val]??'');
                        if($val != 'zCI_Device.name' && $val != 'zsamplingDate_sync' && $val != 'zOil_Name_v2.zsym'){
                            $spreadsheet->setActiveSheetIndexByName('OLTC')->getCell($range[$k] . ($colum_key_oltc + 1))->setValue($value[$val]??'');
                        }
                    }
                }
                $colum_key_oltc += 2;
            }
            // get data sheet MBA
            if( !empty($value['zCI_Device.class.type']) && in_array($value['zCI_Device.class.type'], ['Máy biến áp', 'Máy biến áp phân phối']) ){
                foreach(config('attributes.atrDissolvedGasOilValue') as $k => $val){
                    if($val != 'break'){
                        $spreadsheet->setActiveSheetIndexByName('MBA')->getCell($range[$k] . ($colum_key))->setValue($value[$val]??'');
                    }
                }
                $colum_key++;
            }
        }
        // delete row
        if(count($reports) != 100){
            for ($i = 105; $i >= $colum_key; --$i){
                $spreadsheet->setActiveSheetIndexByName('MBA')->removeRow($i);
            }
            for ($i = 205; $i >= $colum_key_oltc; --$i){
                $spreadsheet->setActiveSheetIndexByName('OLTC')->removeRow($i);
            }
        }

        // check if OLTC null then actice MBA, if MBA null then actice OLTC
        if($colum_key_oltc == 4){
            $sheetActive = $spreadsheet->setActiveSheetIndexByName('MBA');
        }elseif($colum_key == 4){
            $sheetActive = $spreadsheet->setActiveSheetIndexByName('OLTC');
        }else{
            $sheetActive = $spreadsheet->setActiveSheetIndexByName('MBA');
        }
        $this->fillSearchShareReport($sheetActive, $request);
        $title = 'bao-cao-khi-hoa-tan-trong-dau-'.time().'.xlsx';
        return writeExcel($spreadsheet, $title);
    }

    private function fillSearchShareReport($sheetActive, $request)
    {
        $columns = range('D', 'G');
        $inputs = [
            'Thời gian bắt đầu cần thống kê: ' => 'from',
            'Thời gian kết thúc cần thống kê: ' => 'to',
            'Đơn vị quản lý: ' => 'dvqls',
            'Loại thiết bị: ' => 'devices_name'
        ];
        $requestArr = formatRequest($request);
        fillSearchRequestToExcel($sheetActive, $requestArr, $columns, $inputs, 2);
    }
}
