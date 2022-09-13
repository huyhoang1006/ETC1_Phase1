<?php

namespace Modules\Enery\Http\Controllers;

use App\Exports\BoilersByManufactureExport;
use App\Exports\IndustrialFurnaceByManufactureExport;
use App\Exports\GasTurbineParameterTestResultsExport;
use App\Services\CAWebServices;
use GreenCape\Xml\Converter;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpWord\Shared\ZipArchive;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Style;
use PhpParser\Node\Expr\Cast\Array_;

class EneryController extends Controller
{
    /**
     * Index
     *
     * @param Request $request
     * @param $title
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index(Request $request, $title)
    {
        $report = $experiment_type = $content = $detail = "";
        $input = false;
        switch ($title) {
            case 'bao-cao-ket-qua-danh-gia-do-khong-dam-bao-do-cua-thiet-bị-hieu-chuan-ap-xuat':
                $report = 'BM01.QĐ06 Giấy chứng nhận hiệu chuẩn áp suất';
                $experiment_type = 'zCNNL7';
                $content = "Quản lý báo cáo kết quả đánh giá độ không đảm bảo do của thiết bị hiệu chuẩn áp suất";
                $input = true;
                $detail = route('admin.thiet_bi_hieu_chuan_ap_xuat_preview');
                break;
            case 'bao-cao-ket-qua-danh-gia-do-khong-dam-bao-do-cua-thiet-bị-hieu-chuan-nhiet-am-ke':
                $report = 'BM01.QĐ06 Giấy chứng nhận hiệu chuẩn nhiệt ẩm';
                $experiment_type = 'zCNNL8';
                $content = "Quản lý báo cáo kết quả đánh giá độ không đảm bảo do của thiết bị hiệu chuẩn nhiệt ẩm kế";
                $input = true;
                $detail = route('thiet_bi_hieu_chuan_nhiet_am_ke_preview');
                break;
            case 'bao-cao-ket-qua-danh-gia-do-khong-dam-bao-do-cua-thiet-bị-hieu-chuan-nhiet-do':
                $report = 'BM01.QĐ06 Giấy chứng nhận hiệu chuẩn nhiệt độ';
                $experiment_type = 'zCNNL22';
                $content = "Quản lý báo cáo kết quả đánh giá độ không đảm bảo đo của thiết bị hiệu chuẩn nhiệt độ";
                $detail = route('thiet_bi_hieu_chuan_nhiet_do_preview');
                $input = true;
                break;
            case 'bao-cao-so-sanh-ket-qua-thi-nghiem-thong-so-tua-bin-hoi':
                $report = 'BM06.QT02 Biên bản thí nghiệm hiệu chỉnh tua bin hơi';
                $experiment_type = 'zCNNL14';
                $content = "Quản lý báo cáo so sánh kết quả thí nghiệm thông số tua bin hơi";
                $detail = route('admin.steamTurbineParametersDetail');
                break;
            case 'ket-qua-thi-nghiem-thong-so-tuabin-khi':
                $report = 'BM07.QT02 Thí nghiệm hiệu chỉnh tua bin khí';
                $experiment_type = 'zCNNL5';
                $content = "Báo cáo so sánh kết quả thí nghiệm thông số tuabin khí";
                $detail = route('admin.gasTurbineParametersDetail');
                break;
            case 'bao-cao-danh-gia-ket-qua-thi-nghiem-do-dac-tuyen-to-may':
                $report = 'BM08.QT02 Kết quả thí nghiệm đo đặc tuyến tổ máy phát điện';
                $experiment_type = 'zCNNL20';
                $content = "Quản lý báo cáo đánh giá kết quả thí nghiệm đo đặc tuyến tổ máy";
                $detail = route('admin.unitCharacteristicMeasurementDetail');
                break;
            case 'bao-cao-so-sanh-ket-qua-thi-nghiem-thong-so-lo-hoi-lon':
                $report = 'BM07.QT01 Biên bản thí nghiệm hiệu chỉnh lò hơi (công suất ≥ 300mw)';
                $experiment_type = 'zCNNL16';
                $content = "Quản lý báo cáo so sánh kết quả thí nghiệm thông số lò hơi lớn";
                $detail = route('admin.largeBoilerParametersDetail');
                break;
            case 'bao-cao-so-sanh-ket-qua-thi-nghiem-thong-so-lo-hoi-nho':
                $report = 'BM06.QT01 Biên bản thí nghiệm hiệu chỉnh lò hơi (công suất ≤ 100mw)';
                $experiment_type = 'zCNNL19';
                $content = "Quản lý báo cáo so sánh kết quả thí nghiệm thông số lò hơi nhỏ";
                $detail = route('admin.smallBoilerParametersDetail');
                break;
        }
        
        $dvqls = $this->getDVQL();
        $tds = $this->getTD($request);
        $nls = $this->getNL($request);

        if(empty($report) && empty($experiment_type)) {
            return view('enery::master.index', [
                'item'=>[],
                'request' => $request->all(),
                'title' => $title,
                'input' => $input,
                'content' => $content,
                'detail' => $detail,
                'dvqls' => $dvqls,
                'tds' => $tds,
                'nls' => $nls,
            ]);
        }

        $request = $request->all();
        $request['type'] = $report ;
        $request['experiment_type'] =  $experiment_type;

        $items = !empty($request['devices']) ? getEnergyIndex($request['type'], $request) : [];
        sortData($items, 'asc', 'zCI_Device.name');

        $types = collect(getDataFromService('doSelect', 'grc', ['id', 'type'], 'zetc_type = 0 AND zCNNL = 1', 300))->pluck('type', 'id')->toArray();

        $manufacturer = !empty($request['devices']) ? collect(getDataFromService('doSelect', 'zManufacturer', ['id', 'zsym'], 'zsym IS NOT NULL AND zclass = ' . $request['devices'], 300))->pluck('zsym', 'id')->toArray() : [];

        $deviceTypes = !empty($request['devices']) ? collect(getDataFromService('doSelect', 'zCI_Device_Type', ['id', 'zsym'], 'zsym IS NOT NULL AND zclass = ' . $request['devices'], 300))->pluck('zsym', 'id')->toArray() : [];

        return view('enery::master.index', compact('items', 'request', 'title', 'input', 'content', 'detail', 'types', 'manufacturer', 'deviceTypes', 'dvqls', 'tds', 'nls'));
    }

    /**
     * Measurement uncertainty of the pressure calibrator index
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function thiet_bi_hieu_chuan_ap_xuat(Request $request)
    {
        $request = $request->all();
        $request['type'] = 'BM01.QĐ06 Giấy chứng nhận hiệu chuẩn áp suất';
        $request['experiment_type'] = 'zCNNL7';

        $items = !empty($request['devices']) ? getEnergyIndex($request['type'], $request) : [];
        sortData($items, 'asc', 'zCI_Device.name');

        $types = collect(getDataFromService('doSelect', 'grc', ['id', 'type'], 'zetc_type = 0 AND zCNNL = 1', 300))->pluck('type', 'id')->toArray();
        Arr::sort($types);
        $manufacturer = !empty($request['devices']) ? collect(getDataFromService('doSelect', 'zManufacturer', ['id', 'zsym'], 'zsym IS NOT NULL AND zclass = ' . $request['devices'], 300))->pluck('zsym', 'id')->toArray() : [];
        Arr::sort($manufacturer);
        $deviceTypes = !empty($request['devices']) ? collect(getDataFromService('doSelect', 'zCI_Device_Type', ['id', 'zsym'], 'zsym IS NOT NULL AND zclass = ' . $request['devices'], 300))->pluck('zsym', 'id')->toArray() : [];
        Arr::sort($deviceTypes);
        $dvqls = $this->getDVQL();
        $tds = $this->getTD($request);
        $nls = $this->getNL($request);
        return view('enery::pressureCalibrationDevice.index', compact('items', 'request', 'types', 'manufacturer', 'deviceTypes', 'dvqls', 'tds', 'nls'));
    }

    public function thiet_bi_hieu_chuan_ap_xuat_preview(Request $request)
    {
        $ids = $request->get('ids');
        $excel = $this->ExportPressureCalibrationDevice($ids, $request);
        return view('enery::pressureCalibrationDevice.thiet-bi-hieu-chuan-ap-xuat',compact('ids','excel'));
    }

    public function ExportPressureCalibrationDevice(string $handleId, $request)
    {
        // Get User Login Session
        $user = session()->get(env('AUTH_SESSION_KEY'));

        // Get Url Web Service
        $service = new CAWebServices(env('CA_WSDL_URL'));
        $handleIds = explode(',', $handleId);
        $arr = [];

        foreach ($handleIds as $key => $val) {
            // Query Data Service 'nr' Object
            $payload = [
                'sid' => $user['ssid'],
                'objectHandle' => 'nr:' . $val,
                'attributes' => [
                    'zlaboratoryDate',
                    'zCI_Device',
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
                    'zCI_Device.zStage.zsym',
                ]
            ];

            // Return Data Web Service
            $resp = $service->callFunction('getObjectValues', $payload);
            // Convert Data To Object
            $parser = new Converter($resp->getObjectValuesReturn);

            $item = [];

            foreach ($parser->data['UDSObject'][1]['Attributes'] as $attr) {
                if ($attr['Attribute'][1]['AttrValue']) {
                    $item[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                }
            }


            // Query Object Data
            $resp = $service->callFunction('doSelect', [
                'sid' => $user['ssid'],
                'objectType' => 'zCNNL7',
                'whereClause' => "id=U'".$val."'",
                'maxRows' => 10,
                'attributes' => [

                ]
            ]);
            // Convert Object New
            $parser = new Converter($resp->doSelectReturn);
            // Writing Data Into Array

            if (!isset($parser->data['UDSObjectList'][0])) {
                $parser->data['UDSObjectList'] = [$parser->data['UDSObjectList']];
            }
            $zCNNLObj = [];
            foreach ($parser->data['UDSObjectList'] as $obj) {
                if (!$obj) continue;
                $zCNNLObj['handle_id'] = $obj['UDSObject'][0]['Handle'];
                foreach ($obj['UDSObject'][1]['Attributes'] as $attr) {
                    if ($attr['Attribute'][1]['AttrValue']) {
                        $zCNNLObj[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                    }
                }
            }

            // Query Object Data zCIDevice

            if(!empty($item['zCI_Device'])) {
                $resp = $service->callFunction('doSelect', [
                    'sid' => $user['ssid'],
                    'objectType' => 'zETC_Device',
                    'whereClause' => "id=U'".$item['zCI_Device']."'",
                    'maxRows' => 10,
                    'attributes' => [
                        'zscope_scale',
                        'zaccuracy'
                    ]
                ]);
                // Convert Object New
                $parser = new Converter($resp->doSelectReturn);
                // Writing Data Into Array

                if (!isset($parser->data['UDSObjectList'][0])) {
                    $parser->data['UDSObjectList'] = [$parser->data['UDSObjectList']];
                }
                $zCIDeviceObj = [];
                foreach ($parser->data['UDSObjectList'] as $obj) {
                    if (!$obj) continue;
                    $zCIDeviceObj['handle_id'] = $obj['UDSObject'][0]['Handle'];
                    foreach ($obj['UDSObject'][1]['Attributes'] as $attr) {
                        if ($attr['Attribute'][1]['AttrValue']) {
                            $zCIDeviceObj[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                        }
                    }
                }
            }
            // Data item
            $arr[$key]['zlaboratoryDate'] = !empty($item['zlaboratoryDate']) ? $item['zlaboratoryDate'] : 0;
            $arr[$key]['zCI_Device.zrefnr_dvql.zsym'] = !empty($item['zCI_Device.zrefnr_dvql.zsym']) ? $item['zCI_Device.zrefnr_dvql.zsym'] : '';
            $arr[$key]['zCI_Device.zArea.zsym'] = !empty($item['zCI_Device.zArea.zsym']) ? $item['zCI_Device.zArea.zsym'] : '';
            $arr[$key]['zCI_Device.zrefnr_td.zsym'] = !empty($item['zCI_Device.zrefnr_td.zsym']) ? $item['zCI_Device.zrefnr_td.zsym'] : '';
            $arr[$key]['zCI_Device.zrefnr_nl.zsym'] = !empty($item['zCI_Device.zrefnr_nl.zsym']) ? $item['zCI_Device.zrefnr_nl.zsym'] : '';
            $arr[$key]['zCI_Device.name'] = !empty($item['zCI_Device.name']) ? $item['zCI_Device.name'] : '';
            $arr[$key]['zCI_Device.zCI_Device_Type.zsym'] = !empty($item['zCI_Device.zCI_Device_Type.zsym']) ? $item['zCI_Device.zCI_Device_Type.zsym'] : '';
            $arr[$key]['zCI_Device.zManufacturer.zsym'] = !empty($item['zCI_Device.zManufacturer.zsym']) ? $item['zCI_Device.zManufacturer.zsym'] : '';
            $arr[$key]['zCI_Device.zCI_Device_Kind.zsym'] = !empty($item['zCI_Device.zCI_Device_Kind.zsym']) ? $item['zCI_Device.zCI_Device_Kind.zsym'] : '';
            $arr[$key]['zCI_Device.serial_number'] = !empty($item['zCI_Device.serial_number']) ? $item['zCI_Device.serial_number'] : '';
            $arr[$key]['zCI_Device.zYear_of_Manafacture.zsym'] = !empty($item['zCI_Device.zYear_of_Manafacture.zsym']) ? $item['zCI_Device.zYear_of_Manafacture.zsym'] : '';
            $arr[$key]['zCI_Device.zCountry.name'] = !empty($item['zCI_Device.zCountry.name']) ? $item['zCI_Device.zCountry.name'] : '';
            $arr[$key]['zCI_Device.zStage.zsym'] = !empty($item['zCI_Device.zStage.zsym']) ? $item['zCI_Device.zStage.zsym'] : '';

            // Data zCIDeviceObj
            $arr[$key]['zscope_scale'] = !empty($zCIDeviceObj['zscope_scale']) ? $zCIDeviceObj['zscope_scale'] : '';
            $arr[$key]['zaccuracy'] = !empty($zCIDeviceObj['zaccuracy']) ? $zCIDeviceObj['zaccuracy'] : '';

            // Data zCNNLObj


            $arr[$key]['zDTHC01'] = !empty($zCNNLObj['zDTHC01']) ? $zCNNLObj['zDTHC01'] : '';
            $arr[$key]['zDTHC02'] = !empty($zCNNLObj['zDTHC02']) ? $zCNNLObj['zDTHC02'] : '';

            $arr[$key]['zUncertainty_Measure_Value01'] = !empty($zCNNLObj['zUncertainty_Measure_Value01']) ? $zCNNLObj['zUncertainty_Measure_Value01'] : '';
            $arr[$key]['zUncertainty_Measure_Value02'] = !empty($zCNNLObj['zUncertainty_Measure_Value02']) ? $zCNNLObj['zUncertainty_Measure_Value02'] : '';
            $arr[$key]['zUncertainty_Measure_Value03'] = !empty($zCNNLObj['zUncertainty_Measure_Value03']) ? $zCNNLObj['zUncertainty_Measure_Value03'] : '';
            $arr[$key]['zUncertainty_Measure_Value04'] = !empty($zCNNLObj['zUncertainty_Measure_Value04']) ? $zCNNLObj['zUncertainty_Measure_Value04'] : '';
            $arr[$key]['zUncertainty_Measure_Value05'] = !empty($zCNNLObj['zUncertainty_Measure_Value05']) ? $zCNNLObj['zUncertainty_Measure_Value05'] : '';
            $arr[$key]['zUncertainty_Measure_Value06'] = !empty($zCNNLObj['zUncertainty_Measure_Value06']) ? $zCNNLObj['zUncertainty_Measure_Value06'] : '';
            $arr[$key]['zUncertainty_Measure_Value07'] = !empty($zCNNLObj['zUncertainty_Measure_Value07']) ? $zCNNLObj['zUncertainty_Measure_Value07'] : '';
            $arr[$key]['zUncertainty_Measure_Value08'] = !empty($zCNNLObj['zUncertainty_Measure_Value08']) ? $zCNNLObj['zUncertainty_Measure_Value08'] : '';
            $arr[$key]['zUncertainty_Measure_Value09'] = !empty($zCNNLObj['zUncertainty_Measure_Value09']) ? $zCNNLObj['zUncertainty_Measure_Value09'] : '';
            $arr[$key]['zUncertainty_Measure_Value10'] = !empty($zCNNLObj['zUncertainty_Measure_Value10']) ? $zCNNLObj['zUncertainty_Measure_Value10'] : '';
            $arr[$key]['zUncertainty_Measure_Value11'] = !empty($zCNNLObj['zUncertainty_Measure_Value11']) ? $zCNNLObj['zUncertainty_Measure_Value11'] : '';
            $arr[$key]['zUncertainty_Measure_Value12'] = !empty($zCNNLObj['zUncertainty_Measure_Value12']) ? $zCNNLObj['zUncertainty_Measure_Value12'] : '';
            $arr[$key]['zUncertainty_Measure_Value13'] = !empty($zCNNLObj['zUncertainty_Measure_Value13']) ? $zCNNLObj['zUncertainty_Measure_Value13'] : '';
            $arr[$key]['zUncertainty_Measure_Value14'] = !empty($zCNNLObj['zUncertainty_Measure_Value14']) ? $zCNNLObj['zUncertainty_Measure_Value14'] : '';
            $arr[$key]['zUncertainty_Measure_Value15'] = !empty($zCNNLObj['zUncertainty_Measure_Value15']) ? $zCNNLObj['zUncertainty_Measure_Value15'] : '';
            $arr[$key]['zUncertainty_Measure_Value16'] = !empty($zCNNLObj['zUncertainty_Measure_Value16']) ? $zCNNLObj['zUncertainty_Measure_Value16'] : '';
            $arr[$key]['zUncertainty_Measure_Value17'] = !empty($zCNNLObj['zUncertainty_Measure_Value17']) ? $zCNNLObj['zUncertainty_Measure_Value17'] : '';
            $arr[$key]['zUncertainty_Measure_Value18'] = !empty($zCNNLObj['zUncertainty_Measure_Value18']) ? $zCNNLObj['zUncertainty_Measure_Value18'] : '';
            $arr[$key]['zUncertainty_Measure_Value19'] = !empty($zCNNLObj['zUncertainty_Measure_Value19']) ? $zCNNLObj['zUncertainty_Measure_Value19'] : '';
            $arr[$key]['zUncertainty_Measure_Value20'] = !empty($zCNNLObj['zUncertainty_Measure_Value20']) ? $zCNNLObj['zUncertainty_Measure_Value20'] : '';
            $arr[$key]['zUncertainty_Measure_Value21'] = !empty($zCNNLObj['zUncertainty_Measure_Value21']) ? $zCNNLObj['zUncertainty_Measure_Value21'] : '';
            $arr[$key]['zUncertainty_Measure_Value22'] = !empty($zCNNLObj['zUncertainty_Measure_Value22']) ? $zCNNLObj['zUncertainty_Measure_Value22'] : '';
        }
        usort($arr, function ($a, $b) {
            return $a['zlaboratoryDate'] - $b['zlaboratoryDate'];
        });

        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);

        // Read Template Excel
        $template = storage_path('templates_excel/cong_nghe_nang_luong/bao_cao_danh_gia_dkbdd_thiet_bi_hieu_chinh_ap_suat/bao_cao_danh_gia_dkbdd_thiet_bi_hieu_chinh_ap_suat_' . count($arr) . '.xlsx');
        $spreadsheet = $reader->load($template);

        // Create New Writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);

        $sheet2 = $spreadsheet->setActiveSheetIndexByName("BANG_KQ_HIEU_CHUAN");
        $sheet1 = $spreadsheet->setActiveSheetIndexByName("ĐKĐB_TBHC_AP_XUAT");

        foreach ($arr as $key => $val) {
            if ($key > 0) {
                // DuplicateStyle Table
                $spreadsheet->setActiveSheetIndexByName("ĐKĐB_TBHC_AP_XUAT")->duplicateStyle($spreadsheet->getActiveSheet()->getStyle('B33:E33'),'B' . (33 + $key * 17) . ':E' .(33 + $key * 17));
                $spreadsheet->setActiveSheetIndexByName("ĐKĐB_TBHC_AP_XUAT")->duplicateStyle($spreadsheet->getActiveSheet()->getStyle('B34:E47'),'B' . (34 + $key * 17) . ':E' .(47 + $key * 17));

                // Get value
                //$content = $spreadsheet->getActiveSheet()->getCell('B33')->getValue();
                $customer = $sheet1->getCell('B34')->getValue();
                $area = $sheet1->getCell('B35')->getValue();
                $substation = $sheet1->getCell('B36')->getValue();
                $system = $sheet1->getCell('B37')->getValue();
                $equipment = $sheet1->getCell('B38')->getValue();
                $type = $sheet1->getCell('B39')->getValue();
                $manufacturer = $sheet1->getCell('B40')->getValue();
                $model = $sheet1->getCell('B41')->getValue();
                $serial_number = $sheet1->getCell('B42')->getValue();
                $scale = $sheet1->getCell('B43')->getValue();
                $accuracy = $sheet1->getCell('B44')->getValue();
                $year = $sheet1->getCell('B45')->getValue();
                $country = $sheet1->getCell('B46')->getValue();
                $status = $sheet1->getCell('B47')->getValue();

                // Fix value table ĐKĐB_TBHC_AP_XUAT
                $sheet1->mergeCells("B" . (34 + $key * 17) . ":C" . (34 + $key * 17))->getCell('B' . (34 + $key * 17))->setValue($customer);
                $sheet1->mergeCells("B" . (35 + $key * 17) . ":C" . (35 + $key * 17))->getCell('B' . (35 + $key * 17))->setValue($area);
                $sheet1->mergeCells("B" . (36 + $key * 17) . ":C" . (36 + $key * 17))->getCell('B' . (36 + $key * 17))->setValue($substation);
                $sheet1->mergeCells("B" . (37 + $key * 17) . ":C" . (37 + $key * 17))->getCell('B' . (37 + $key * 17))->setValue($system);
                $sheet1->mergeCells("B" . (38 + $key * 17) . ":C" . (38 + $key * 17))->getCell('B' . (38 + $key * 17))->setValue($equipment);
                $sheet1->mergeCells("B" . (39 + $key * 17) . ":C" . (39 + $key * 17))->getCell('B' . (39 + $key * 17))->setValue($type);
                $sheet1->mergeCells("B" . (40 + $key * 17) . ":C" . (40 + $key * 17))->getCell('B' . (40 + $key * 17))->setValue($manufacturer);
                $sheet1->mergeCells("B" . (41 + $key * 17) . ":C" . (41 + $key * 17))->getCell('B' . (41 + $key * 17))->setValue($model);
                $sheet1->mergeCells("B" . (42 + $key * 17) . ":C" . (42 + $key * 17))->getCell('B' . (42 + $key * 17))->setValue($serial_number);
                $sheet1->mergeCells("B" . (43 + $key * 17) . ":C" . (43 + $key * 17))->getCell('B' . (43 + $key * 17))->setValue($scale);
                $sheet1->mergeCells("B" . (44 + $key * 17) . ":C" . (44 + $key * 17))->getCell('B' . (44 + $key * 17))->setValue($accuracy);
                $sheet1->mergeCells("B" . (45 + $key * 17) . ":C" . (45 + $key * 17))->getCell('B' . (45 + $key * 17))->setValue($year);
                $sheet1->mergeCells("B" . (46 + $key * 17) . ":C" . (46 + $key * 17))->getCell('B' . (46 + $key * 17))->setValue($country);
                $sheet1->mergeCells("B" . (47 + $key * 17) . ":C" . (47 + $key * 17))->getCell('B' . (47 + $key * 17))->setValue($status);
            }
            // Sheet 2
            $arrKey = array($val['zUncertainty_Measure_Value01'], $val['zUncertainty_Measure_Value02'],$val['zUncertainty_Measure_Value03'],
                            $val['zUncertainty_Measure_Value04'], $val['zUncertainty_Measure_Value05'], $val['zUncertainty_Measure_Value06'], $val['zUncertainty_Measure_Value07'],
                            $val['zUncertainty_Measure_Value08'], $val['zUncertainty_Measure_Value09'], $val['zUncertainty_Measure_Value10'], $val['zUncertainty_Measure_Value11'],
                            $val['zUncertainty_Measure_Value12'], $val['zUncertainty_Measure_Value13'], $val['zUncertainty_Measure_Value14'], $val['zUncertainty_Measure_Value15'],
                            $val['zUncertainty_Measure_Value16'], $val['zUncertainty_Measure_Value17'], $val['zUncertainty_Measure_Value18'], $val['zUncertainty_Measure_Value19'],
                            $val['zUncertainty_Measure_Value20'], $val['zUncertainty_Measure_Value21'], $val['zUncertainty_Measure_Value22']
            );

            if(!empty($val['zDTHC01']) && !empty($val['zDTHC02'])) {
                $thc = 'Pt =' . ' '. $val['zDTHC01'] . ' + ' .  $val['zDTHC02']. '*Ps ± Uexp';
            } elseif(empty($val['zDTHC01']) && !empty($val['zDTHC02'])) {
                $thc = 'Pt =' . ' '. $val['zDTHC01'] . ' + ' .  $val['zDTHC02']. '*Ps ± Uexp';
            }  elseif(!empty($val['zDTHC01']) && empty($val['zDTHC02'])) {
                $thc = 'Pt =' . ' '. $val['zDTHC01'] . ' + ' .  $val['zDTHC02']. '*Ps ± Uexp';
            }
            else {
                $thc = "";
            }

            $sheet2->getCell('C' . (12 + $key * 1))->setValue(!empty($val['zlaboratoryDate']) ? date('d/m/Y', $val['zlaboratoryDate']) : '');
            $sheet2->getCell('D' . (12 + $key * 1))->setValue($thc);
            $sheet2->getCell('E' . (12 + $key * 1))->setValue(max($arrKey));

            // Sheet 1
            $count = 0;
            for ($i = 33; $i <= 47; $i++) {
                $sheet1->duplicateStyle($sheet1->getStyle('B' . ($i + $key * 17)), 'A' . ($i + $key * 17));
                if ($count == 0) {
                    $sheet1->getCell('A' . ($i + $key * 17))->setValue('STT');
                } else {
                    $sheet1->getCell('A' . ($i + $key * 17))->setValue($count);
                }
                $count++;
            }

            $sheet1->mergeCells("B" . (33 + $key * 17) . ":E" . (33 + $key * 17))->getCell('B' . (33 + $key * 17))->setValue('BÁO CÁO KẾT QUẢ ĐÁNH GIÁ NGÀY'. ' '. (!empty($val['zlaboratoryDate']) ? date('d/m/Y', $val['zlaboratoryDate']) : ''));
            $sheet1->mergeCells("D" . (34 + $key * 17) . ":E" . (34 + $key * 17))->getCell('D' . (34 + $key * 17))->setValue($val['zCI_Device.zrefnr_dvql.zsym']);
            $sheet1->mergeCells("D" . (35 + $key * 17) . ":E" . (35 + $key * 17))->getCell('D' . (35 + $key * 17))->setValue($val['zCI_Device.zArea.zsym']);
            $sheet1->mergeCells("D" . (36 + $key * 17) . ":E" . (36 + $key * 17))->getCell('D' . (36 + $key * 17))->setValue($val['zCI_Device.zrefnr_td.zsym']);
            $sheet1->mergeCells("D" . (37 + $key * 17) . ":E" . (37 + $key * 17))->getCell('D' . (37 + $key * 17))->setValue($val['zCI_Device.zrefnr_nl.zsym']);
            $sheet1->mergeCells("D" . (38 + $key * 17) . ":E" . (38 + $key * 17))->getCell('D' . (38 + $key * 17))->setValue($val['zCI_Device.name']);
            $sheet1->mergeCells("D" . (39 + $key * 17) . ":E" . (39 + $key * 17))->getCell('D' . (39 + $key * 17))->setValue($val['zCI_Device.zCI_Device_Type.zsym']);
            $sheet1->mergeCells("D" . (40 + $key * 17) . ":E" . (40 + $key * 17))->getCell('D' . (40 + $key * 17))->setValue($val['zCI_Device.zManufacturer.zsym']);
            $sheet1->mergeCells("D" . (41 + $key * 17) . ":E" . (41 + $key * 17))->getCell('D' . (41 + $key * 17))->setValue($val['zCI_Device.zCI_Device_Kind.zsym']);
            $sheet1->mergeCells("D" . (42 + $key * 17) . ":E" . (42 + $key * 17))->getCell('D' . (42 + $key * 17))->setValue($val['zCI_Device.serial_number']);
            $sheet1->mergeCells("D" . (43 + $key * 17) . ":E" . (43 + $key * 17))->getCell('D' . (43 + $key * 17))->setValue($val['zscope_scale']);
            $sheet1->mergeCells("D" . (44 + $key * 17) . ":E" . (44 + $key * 17))->getCell('D' . (44 + $key * 17))->setValue($val['zaccuracy']);
            $sheet1->mergeCells("D" . (45 + $key * 17) . ":E" . (45 + $key * 17))->getCell('D' . (45 + $key * 17))->setValue($val['zCI_Device.zYear_of_Manafacture.zsym']);
            $sheet1->mergeCells("D" . (46 + $key * 17) . ":E" . (46 + $key * 17))->getCell('D' . (46 + $key * 17))->setValue($val['zCI_Device.zCountry.name']);
            $sheet1->mergeCells("D" . (47 + $key * 17) . ":E" . (47 + $key * 17))->getCell('D' . (47 + $key * 17))->setValue($val['zCI_Device.zStage.zsym']);
        }
        $requestArr = formatRequest($request);
        fillSearchRequestToExcel($sheet2, $requestArr, [], [], 4, true);
        $spreadsheet->setActiveSheetIndexByName("ĐKĐB_TBHC_AP_XUAT");
        // Output Permission
        $outputDir = public_path('export');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        // Output path
        $output = $outputDir . '/' . 'bao-cao-cong-nghe-nang-luong-hieu-chuan-ap-xuat'.'-'.time() .'-'.$user['ssid']. '.xlsx';
        if (is_file($output)) {
            unlink($output);
        }
        // Writer File Into Path
        $writer->save('export/bao-cao-cong-nghe-nang-luong-hieu-chuan-ap-xuat' . '-'.time() .'-'.$user['ssid']. '.xlsx');

        // Return Link Download
        return str_replace(public_path(''), getenv('APP_URL'), $output);

    }

    public function thiet_bi_hieu_chuan_nhiet_am_ke(Request $request)
    {
        $request = $request->all();
        $request['type'] = 'BM01.QĐ06 Giấy chứng nhận hiệu chuẩn nhiệt ẩm';
        $request['experiment_type'] = 'zCNNL8';

        $items = !empty($request['devices']) ? getEnergyIndex($request['type'], $request) : [];
        sortData($items, 'asc', 'zCI_Device.name');

        $types = collect(getDataFromService('doSelect', 'grc', ['id', 'type'], 'zetc_type = 0 AND zCNNL = 1', 300))->pluck('type', 'id')->toArray();

        $manufacturer = !empty($request['devices']) ? collect(getDataFromService('doSelect', 'zManufacturer', ['id', 'zsym'], 'zsym IS NOT NULL AND zclass = ' . $request['devices'], 300))->pluck('zsym', 'id')->toArray() : [];

        $deviceTypes = !empty($request['devices']) ? collect(getDataFromService('doSelect', 'zCI_Device_Type', ['id', 'zsym'], 'zsym IS NOT NULL AND zclass = ' . $request['devices'], 300))->pluck('zsym', 'id')->toArray() : [];

        $dvqls = $this->getDVQL();
        $tds = $this->getTD($request);
        $nls = $this->getNL($request);

        return view('enery::thermalHygrometer.index', compact('items', 'request', 'types', 'manufacturer', 'deviceTypes', 'dvqls', 'tds', 'nls'));
    }

    public function thiet_bi_hieu_chuan_nhiet_am_ke_preview(Request $request)
    {
        $ids = $request->get('ids');
        $excel = $this->ExportThermalDevice($ids, $request);
        return view('enery::thermalHygrometer.thiet-bi-hieu-chuan-nhiet-am-ke',compact('ids','excel'));
    }

    public function ExportThermalDevice(string $handleId, $request)
    {
        // Get User Login Session
        $user = session()->get(env('AUTH_SESSION_KEY'));

        // Get Url Web Service
        $service = new CAWebServices(env('CA_WSDL_URL'));
        $handleIds = explode(',', $handleId);
        $arr = [];

        foreach ($handleIds as $key => $val) {
            // Query Data Service 'nr' Object
            $payload = [
                'sid' => $user['ssid'],
                'objectHandle' => 'nr:' . $val,
                'attributes' => [
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
                    'zCI_Device',
                    'zCI_Device.zYear_of_Manafacture.zsym',
                    'zCI_Device.zCountry.name',
                    'zExperimental_Place',
                    'zCI_Device.zStage.zsym',
                ]
            ];

            // Return Data Web Service
            $resp = $service->callFunction('getObjectValues', $payload);
            // Convert Data To Object
            $parser = new Converter($resp->getObjectValuesReturn);

            $item = [];

            foreach ($parser->data['UDSObject'][1]['Attributes'] as $attr) {
                if ($attr['Attribute'][1]['AttrValue']) {
                    $item[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                }
            }


            // Query Object Data
            $resp = $service->callFunction('doSelect', [
                'sid' => $user['ssid'],
                'objectType' => 'zCNNL8',
                'whereClause' => "id=U'".$val."'",
                'maxRows' => 10,
                'attributes' => [
                    'zDKDBD_Uh',
                    'zDKDBD_Ut'
                ]
            ]);
            // Convert Object New
            $parser = new Converter($resp->doSelectReturn);
            // Writing Data Into Array

            if (!isset($parser->data['UDSObjectList'][0])) {
                $parser->data['UDSObjectList'] = [$parser->data['UDSObjectList']];
            }
            $zCNNLObj = [];
            foreach ($parser->data['UDSObjectList'] as $obj) {
                if (!$obj) continue;
                $zCNNLObj['handle_id'] = $obj['UDSObject'][0]['Handle'];
                foreach ($obj['UDSObject'][1]['Attributes'] as $attr) {
                    if ($attr['Attribute'][1]['AttrValue']) {
                        $zCNNLObj[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                    }
                }
            }

            // Query Object Data zCIDevice
            if(!empty($item['zCI_Device'])) {
                $resp = $service->callFunction('doSelect', [
                    'sid' => $user['ssid'],
                    'objectType' => 'zETC_Device',
                    'whereClause' => "id=U'".$item['zCI_Device']."'",
                    'maxRows' => 10,
                    'attributes' => [
                        'zresolution',
                        'ztemperature',
                        'zhumidity',
                        'zscope_scale',
                    ]
                ]);
                // Convert Object New
                $parser = new Converter($resp->doSelectReturn);
                // Writing Data Into Array

                if (!isset($parser->data['UDSObjectList'][0])) {
                    $parser->data['UDSObjectList'] = [$parser->data['UDSObjectList']];
                }
                $zCIDeviceObj = [];
                foreach ($parser->data['UDSObjectList'] as $obj) {
                    if (!$obj) continue;
                    $zCIDeviceObj['handle_id'] = $obj['UDSObject'][0]['Handle'];
                    foreach ($obj['UDSObject'][1]['Attributes'] as $attr) {
                        if ($attr['Attribute'][1]['AttrValue']) {
                            $zCIDeviceObj[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                        }
                    }
                }
            }

            // Data item
            $arr[$key]['zlaboratoryDate'] = !empty($item['zlaboratoryDate']) ? date('d/m/Y', $item['zlaboratoryDate']) : '';
            $arr[$key]['zCI_Device.zrefnr_dvql.zsym'] = !empty($item['zCI_Device.zrefnr_dvql.zsym']) ? $item['zCI_Device.zrefnr_dvql.zsym'] : '';
            $arr[$key]['zCI_Device.zArea.zsym'] = !empty($item['zCI_Device.zArea.zsym']) ? $item['zCI_Device.zArea.zsym'] : '';
            $arr[$key]['zCI_Device.zrefnr_td.zsym'] = !empty($item['zCI_Device.zrefnr_td.zsym']) ? $item['zCI_Device.zrefnr_td.zsym'] : '';
            $arr[$key]['zCI_Device.zrefnr_nl.zsym'] = !empty($item['zCI_Device.zrefnr_nl.zsym']) ? $item['zCI_Device.zrefnr_nl.zsym'] : '';
            $arr[$key]['zCI_Device.name'] = !empty($item['zCI_Device.name']) ? $item['zCI_Device.name'] : '';
            $arr[$key]['zCI_Device.zCI_Device_Type.zsym'] = !empty($item['zCI_Device.zCI_Device_Type.zsym']) ? $item['zCI_Device.zCI_Device_Type.zsym'] : '';
            $arr[$key]['zCI_Device.zManufacturer.zsym'] = !empty($item['zCI_Device.zManufacturer.zsym']) ? $item['zCI_Device.zManufacturer.zsym'] : '';
            $arr[$key]['zCI_Device.zCI_Device_Kind.zsym'] = !empty($item['zCI_Device.zCI_Device_Kind.zsym']) ? $item['zCI_Device.zCI_Device_Kind.zsym'] : '';
            $arr[$key]['zCI_Device.serial_number'] = !empty($item['zCI_Device.serial_number']) ? $item['zCI_Device.serial_number'] : '';
            $arr[$key]['zCI_Device.zYear_of_Manafacture.zsym'] = !empty($item['zCI_Device.zYear_of_Manafacture.zsym']) ? $item['zCI_Device.zYear_of_Manafacture.zsym'] : '';
            $arr[$key]['zCI_Device.zCountry.name'] = !empty($item['zCI_Device.zCountry.name']) ? $item['zCI_Device.zCountry.name'] : '';
            $arr[$key]['zExperimental_Place'] = !empty($item['zExperimental_Place']) ? $item['zExperimental_Place'] : '';
            $arr[$key]['zCI_Device.zStage.zsym'] = !empty($item['zCI_Device.zStage.zsym']) ? $item['zCI_Device.zStage.zsym'] : '';


            // Data zCIDeviceObj
            $arr[$key]['zresolution'] = !empty($zCIDeviceObj['zresolution']) ? $zCIDeviceObj['zresolution'] : '';
            $arr[$key]['ztemperature'] = !empty($zCIDeviceObj['ztemperature']) ? $zCIDeviceObj['ztemperature'] : '';
            $arr[$key]['zhumidity'] = !empty($zCIDeviceObj['zhumidity']) ? $zCIDeviceObj['zhumidity'] : '';
            $arr[$key]['zscope_scale'] = !empty($zCIDeviceObj['zscope_scale']) ? $zCIDeviceObj['zscope_scale'] : '';

            // Data zCNNLObj
            $arr[$key]['zDKDBD_Ut'] = !empty($zCNNLObj['zDKDBD_Ut']) ? $zCNNLObj['zDKDBD_Ut'] : '';
            $arr[$key]['zDKDBD_Uh'] = !empty($zCNNLObj['zDKDBD_Uh']) ? $zCNNLObj['zDKDBD_Uh'] : '';
        }

        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);

        // Read Template Excel
        $template = storage_path('templates_excel/cong_nghe_nang_luong/danh_gia_dkdbd_thiet_bi_nhiet_am_ke/danh_gia_dkdbd_thiet_bi_nhiet_am_ke_' . count($arr) . '.xlsx');
        $spreadsheet = $reader->load($template);

        // Create New Writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);

        $sheet2 = $spreadsheet->setActiveSheetIndexByName("THONG TIN KQ THI NGHIEM");
        $sheet1 = $spreadsheet->setActiveSheetIndexByName("ĐKĐB_TBHC_NHIET_AM_KE");

        foreach ($arr as $key => $val) {
            if ($key > 0) {
                // DuplicateStyle Table
                $sheet1->duplicateStyle($sheet1->getStyle('B33:E33'),'B' . (33 + $key * 20) . ':E' .(33 + $key * 20));
                $sheet1->duplicateStyle($sheet1->getStyle('B34:E49'),'B' . (34 + $key * 20) . ':E' .(49 + $key * 20));

                // Get value
                //$content = $spreadsheet->getActiveSheet()->getCell('B33')->getValue();
                $customer = $sheet1->getCell('B34')->getValue();
                $area = $sheet1->getCell('B35')->getValue();
                $substation = $sheet1->getCell('B36')->getValue();
                $system = $sheet1->getCell('B37')->getValue();
                $equipment = $sheet1->getCell('B38')->getValue();
                $type = $sheet1->getCell('B39')->getValue();
                $manufacturer = $sheet1->getCell('B40')->getValue();
                $model = $sheet1->getCell('B41')->getValue();
                $serial_number = $sheet1->getCell('B42')->getValue();
                $scale = $sheet1->getCell('B43')->getValue();
                $resolution = $sheet1->getCell('B44')->getValue();
                $temperature = $sheet1->getCell('B45')->getValue();
                $humidity = $sheet1->getCell('B46')->getValue();
                $year = $sheet1->getCell('B47')->getValue();
                $country = $sheet1->getCell('B48')->getValue();
                $status = $sheet1->getCell('B49')->getValue();

                // Fix value table ĐKĐB_TBHC_NHIET_AM_KE
                $sheet1->mergeCells("B" . (34 + $key * 20) . ":C" . (34 + $key * 20))->getCell('B' . (34 + $key * 20))->setValue($customer);
                $sheet1->mergeCells("B" . (35 + $key * 20) . ":C" . (35 + $key * 20))->getCell('B' . (35 + $key * 20))->setValue($area);
                $sheet1->mergeCells("B" . (36 + $key * 20) . ":C" . (36 + $key * 20))->getCell('B' . (36 + $key * 20))->setValue($substation);
                $sheet1->mergeCells("B" . (37 + $key * 20) . ":C" . (37 + $key * 20))->getCell('B' . (37 + $key * 20))->setValue($system);
                $sheet1->mergeCells("B" . (38 + $key * 20) . ":C" . (38 + $key * 20))->getCell('B' . (38 + $key * 20))->setValue($equipment);
                $sheet1->mergeCells("B" . (39 + $key * 20) . ":C" . (39 + $key * 20))->getCell('B' . (39 + $key * 20))->setValue($type);
                $sheet1->mergeCells("B" . (40 + $key * 20) . ":C" . (40 + $key * 20))->getCell('B' . (40 + $key * 20))->setValue($manufacturer);
                $sheet1->mergeCells("B" . (41 + $key * 20) . ":C" . (41 + $key * 20))->getCell('B' . (41 + $key * 20))->setValue($model);
                $sheet1->mergeCells("B" . (42 + $key * 20) . ":C" . (42 + $key * 20))->getCell('B' . (42 + $key * 20))->setValue($serial_number);
                $sheet1->mergeCells("B" . (43 + $key * 20) . ":C" . (43 + $key * 20))->getCell('B' . (43 + $key * 20))->setValue($scale);
                $sheet1->mergeCells("B" . (44 + $key * 20) . ":C" . (44 + $key * 20))->getCell('B' . (44 + $key * 20))->setValue($resolution);
                $sheet1->mergeCells("B" . (45 + $key * 20) . ":C" . (45 + $key * 20))->getCell('B' . (45 + $key * 20))->setValue($temperature);
                $sheet1->mergeCells("B" . (46 + $key * 20) . ":C" . (46 + $key * 20))->getCell('B' . (46 + $key * 20))->setValue($humidity);
                $sheet1->mergeCells("B" . (47 + $key * 20) . ":C" . (47 + $key * 20))->getCell('B' . (47 + $key * 20))->setValue($year);
                $sheet1->mergeCells("B" . (48 + $key * 20) . ":C" . (48 + $key * 20))->getCell('B' . (48 + $key * 20))->setValue($country);
                $sheet1->mergeCells("B" . (49 + $key * 20) . ":C" . (49 + $key * 20))->getCell('B' . (49 + $key * 20))->setValue($status);
            }

            // Sheet 2
            $sheet2->getCell('C' . (12 + $key * 1))->setValue($val['zlaboratoryDate'] ?? '');
            $sheet2->getCell('D' . (12 + $key * 1))->setValue($val['zDKDBD_Ut'] ?? '');
            $sheet2->getCell('E' . (12 + $key * 1))->setValue($val['zDKDBD_Uh'] ?? '');

            // Sheet 1
            $count = 0;
            for ($i = 33; $i <= 49; $i++) {
                $sheet1->duplicateStyle($sheet1->getStyle('B' . ($i + $key * 20)), 'A' . ($i + $key * 20));
                if ($count == 0) {
                    $sheet1->getCell('A' . ($i + $key * 20))->setValue('STT');
                } else {
                    $sheet1->getCell('A' . ($i + $key * 20))->setValue($count);
                }
                $count++;
            }

            $sheet1->mergeCells("B" . (33 + $key * 20) . ":E" . (33 + $key * 20))->getCell('B' . (33 + $key * 20))->setValue('BÁO CÁO KẾT QUẢ ĐÁNH GIÁ NGÀY'. ' '. $val['zlaboratoryDate']);
            $sheet1->mergeCells("D" . (34 + $key * 20) . ":E" . (34 + $key * 20))->getCell('D' . (34 + $key * 20))->setValue($val['zCI_Device.zrefnr_dvql.zsym']);
            $sheet1->mergeCells("D" . (35 + $key * 20) . ":E" . (35 + $key * 20))->getCell('D' . (35 + $key * 20))->setValue($val['zCI_Device.zArea.zsym']);
            $sheet1->mergeCells("D" . (36 + $key * 20) . ":E" . (36 + $key * 20))->getCell('D' . (36 + $key * 20))->setValue($val['zCI_Device.zrefnr_td.zsym']);
            $sheet1->mergeCells("D" . (37 + $key * 20) . ":E" . (37 + $key * 20))->getCell('D' . (37 + $key * 20))->setValue($val['zCI_Device.zrefnr_nl.zsym']);
            $sheet1->mergeCells("D" . (38 + $key * 20) . ":E" . (38 + $key * 20))->getCell('D' . (38 + $key * 20))->setValue($val['zCI_Device.name']);
            $sheet1->mergeCells("D" . (39 + $key * 20) . ":E" . (39 + $key * 20))->getCell('D' . (39 + $key * 20))->setValue($val['zCI_Device.zCI_Device_Type.zsym']);
            $sheet1->mergeCells("D" . (40 + $key * 20) . ":E" . (40 + $key * 20))->getCell('D' . (40 + $key * 20))->setValue($val['zCI_Device.zManufacturer.zsym']);
            $sheet1->mergeCells("D" . (41 + $key * 20) . ":E" . (41 + $key * 20))->getCell('D' . (41 + $key * 20))->setValue($val['zCI_Device.zCI_Device_Kind.zsym']);
            $sheet1->mergeCells("D" . (42 + $key * 20) . ":E" . (42 + $key * 20))->getCell('D' . (42 + $key * 20))->setValue($val['zCI_Device.serial_number']);
            $sheet1->mergeCells("D" . (43 + $key * 20) . ":E" . (43 + $key * 20))->getCell('D' . (43 + $key * 20))->setValue($val['zscope_scale']);
            $sheet1->mergeCells("D" . (44 + $key * 20) . ":E" . (44 + $key * 20))->getCell('D' . (44 + $key * 20))->setValue($val['zresolution']);
            $sheet1->mergeCells("D" . (45 + $key * 20) . ":E" . (45 + $key * 20))->getCell('D' . (45 + $key * 20))->setValue($val['ztemperature']);
            $sheet1->mergeCells("D" . (46 + $key * 20) . ":E" . (46 + $key * 20))->getCell('D' . (46 + $key * 20))->setValue($val['zhumidity']);
            $sheet1->mergeCells("D" . (47 + $key * 20) . ":E" . (47 + $key * 20))->getCell('D' . (47 + $key * 20))->setValue($val['zCI_Device.zYear_of_Manafacture.zsym']);
            $sheet1->mergeCells("D" . (48 + $key * 20) . ":E" . (48 + $key * 20))->getCell('D' . (48 + $key * 20))->setValue($val['zCI_Device.zCountry.name']);
            $sheet1->mergeCells("D" . (49 + $key * 20) . ":E" . (49 + $key * 20))->getCell('D' . (49 + $key * 20))->setValue($val['zCI_Device.zStage.zsym']);

        }
        $requestArr = formatRequest($request);
        fillSearchRequestToExcel($sheet2, $requestArr, [], [], 4, true);
        $sheet2->getStyle('A6:Z6')->getAlignment()->setWrapText(false);
        $spreadsheet->setActiveSheetIndexByName("ĐKĐB_TBHC_NHIET_AM_KE");

        // Output Permission
        $outputDir = public_path('export');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        // Output path
        // $output = $outputDir . '/' . 'bao-cao-cong-nghe-nang-luong'.'-'.time() .'-'.$user['ssid']. '.xlsx';
        $output = $outputDir . '/' . 'bao-cao-cong-nghe-nang-luong-nhiet-am-ke'.'-'.time() .'-'.$user['ssid']. '.xlsx';
        if (is_file($output)) {
            unlink($output);
        }
        // Writer File Into Path
        $writer->save('export/bao-cao-cong-nghe-nang-luong-nhiet-am-ke' . '-'.time() .'-'.$user['ssid']. '.xlsx');

        // Return Link Download
        return str_replace(public_path(''), getenv('APP_URL'), $output);
    }

    public function thiet_bi_hieu_chuan_nhiet_do(Request $request)
    {
        $request = $request->all();
        $request['type'] = 'BM01.QĐ06 Giấy chứng nhận hiệu chuẩn nhiệt độ';
        $request['experiment_type'] = 'zCNNL22';

        $items = !empty($request['devices']) ? getEnergyIndex($request['type'], $request) : [];
        sortData($items, 'asc', 'zCI_Device.name');

        $types = collect(getDataFromService('doSelect', 'grc', ['id', 'type'], 'zetc_type = 0 AND zCNNL = 1', 300))->pluck('type', 'id')->toArray();

        $manufacturer = !empty($request['devices']) ? collect(getDataFromService('doSelect', 'zManufacturer', ['id', 'zsym'], 'zsym IS NOT NULL AND zclass = ' . $request['devices'], 300))->pluck('zsym', 'id')->toArray() : [];

        $deviceTypes = !empty($request['devices']) ? collect(getDataFromService('doSelect', 'zCI_Device_Type', ['id', 'zsym'], 'zsym IS NOT NULL AND zclass = ' . $request['devices'], 300))->pluck('zsym', 'id')->toArray() : [];
        $dvqls = $this->getDVQL();
        $tds = $this->getTD($request);
        $nls = $this->getNL($request);
        return view('enery::temp.index', compact('items', 'request', 'types', 'manufacturer', 'deviceTypes', 'dvqls', 'tds', 'nls'));
    }

    public function thiet_bi_hieu_chuan_nhiet_do_preview(Request $request)
    {
        $ids = $request->get('ids');
        $excel = $this->ExportTempDevice($ids, $request);
        return view('enery::temp.thiet-bi-hieu-chuan-nhiet-do',compact('ids','excel'));
    }

    public function ExportTempDevice(string $handleId, $request)
    {
        // Get User Login Session
        $user = session()->get(env('AUTH_SESSION_KEY'));

        // Get Url Web Service
        $service = new CAWebServices(env('CA_WSDL_URL'));
        $handleIds = explode(',', $handleId);
        $arr = [];

        foreach ($handleIds as $key => $val) {
            // Query Data Service 'nr' Object
            $payload = [
                'sid' => $user['ssid'],
                'objectHandle' => 'nr:' . $val,
                'attributes' => [
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
                    'zCI_Device',
                    'zCI_Device.zYear_of_Manafacture.zsym',
                    'zCI_Device.zCountry.name',
                    'zExperimental_Place',
                    'zCI_Device.zStage.zsym',
                ]
            ];

            // Return Data Web Service
            $resp = $service->callFunction('getObjectValues', $payload);
            // Convert Data To Object
            $parser = new Converter($resp->getObjectValuesReturn);

            $item = [];

            foreach ($parser->data['UDSObject'][1]['Attributes'] as $attr) {
                if ($attr['Attribute'][1]['AttrValue']) {
                    $item[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                }
            }

            // Query Object Data
            $resp = $service->callFunction('doSelect', [
                'sid' => $user['ssid'],
                'objectType' => 'zCNNL22',
                'whereClause' => "id=U'".$val."'",
                'maxRows' => 10,
                'attributes' => [

                ]
            ]);
            // Convert Object New
            $parser = new Converter($resp->doSelectReturn);

            // Writing Data Into Array

            if (!isset($parser->data['UDSObjectList'][0])) {
                $parser->data['UDSObjectList'] = [$parser->data['UDSObjectList']];
            }
            $zCNNLObj = [];
            foreach ($parser->data['UDSObjectList'] as $obj) {
                if (!$obj) continue;
                $zCNNLObj['handle_id'] = $obj['UDSObject'][0]['Handle'];
                foreach ($obj['UDSObject'][1]['Attributes'] as $attr) {
                    if ($attr['Attribute'][1]['AttrValue']) {
                        $zCNNLObj[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                    }
                }
            }


            // Query Object Data zCIDevice

            if(!empty($item['zCI_Device'])) {
                $resp = $service->callFunction('doSelect', [
                    'sid' => $user['ssid'],
                    'objectType' => 'zETC_Device',
                    'whereClause' => "id=U'".$item['zCI_Device']."'",
                    'maxRows' => 10,
                    'attributes' => [
                        'zmeasure_scale',
                        'zcapchinhxac',
                        'zresolution',
                        'zdochinhxac',
                    ]
                ]);
                // Convert Object New
                $parser = new Converter($resp->doSelectReturn);
                // Writing Data Into Array

                if (!isset($parser->data['UDSObjectList'][0])) {
                    $parser->data['UDSObjectList'] = [$parser->data['UDSObjectList']];
                }
                $zCIDeviceObj = [];
                foreach ($parser->data['UDSObjectList'] as $obj) {
                    if (!$obj) continue;
                    $zCIDeviceObj['handle_id'] = $obj['UDSObject'][0]['Handle'];
                    foreach ($obj['UDSObject'][1]['Attributes'] as $attr) {
                        if ($attr['Attribute'][1]['AttrValue']) {
                            $zCIDeviceObj[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                        }
                    }
                }
            }

            // Data item
            $arr[$key]['zlaboratoryDate'] = !empty($item['zlaboratoryDate']) ? $item['zlaboratoryDate'] : 0;
            $arr[$key]['zCI_Device.zrefnr_dvql.zsym'] = !empty($item['zCI_Device.zrefnr_dvql.zsym']) ? $item['zCI_Device.zrefnr_dvql.zsym'] : '';
            $arr[$key]['zCI_Device.zArea.zsym'] = !empty($item['zCI_Device.zArea.zsym']) ? $item['zCI_Device.zArea.zsym'] : '';
            $arr[$key]['zCI_Device.zrefnr_td.zsym'] = !empty($item['zCI_Device.zrefnr_td.zsym']) ? $item['zCI_Device.zrefnr_td.zsym'] : '';
            $arr[$key]['zCI_Device.zrefnr_nl.zsym'] = !empty($item['zCI_Device.zrefnr_nl.zsym']) ? $item['zCI_Device.zrefnr_nl.zsym'] : '';
            $arr[$key]['zCI_Device.name'] = !empty($item['zCI_Device.name']) ? $item['zCI_Device.name'] : '';
            $arr[$key]['zCI_Device.zCI_Device_Type.zsym'] = !empty($item['zCI_Device.zCI_Device_Type.zsym']) ? $item['zCI_Device.zCI_Device_Type.zsym'] : '';
            $arr[$key]['zCI_Device.zManufacturer.zsym'] = !empty($item['zCI_Device.zManufacturer.zsym']) ? $item['zCI_Device.zManufacturer.zsym'] : '';
            $arr[$key]['zCI_Device.zCI_Device_Kind.zsym'] = !empty($item['zCI_Device.zCI_Device_Kind.zsym']) ? $item['zCI_Device.zCI_Device_Kind.zsym'] : '';
            $arr[$key]['zCI_Device.serial_number'] = !empty($item['zCI_Device.serial_number']) ? $item['zCI_Device.serial_number'] : '';
            $arr[$key]['zCI_Device.zYear_of_Manafacture.zsym'] = !empty($item['zCI_Device.zYear_of_Manafacture.zsym']) ? $item['zCI_Device.zYear_of_Manafacture.zsym'] : '';
            $arr[$key]['zCI_Device.zCountry.name'] = !empty($item['zCI_Device.zCountry.name']) ? $item['zCI_Device.zCountry.name'] : '';
            $arr[$key]['zExperimental_Place'] = !empty($item['zExperimental_Place']) ? $item['zExperimental_Place'] : '';
            $arr[$key]['zCI_Device.zStage.zsym'] = !empty($item['zCI_Device.zStage.zsym']) ? $item['zCI_Device.zStage.zsym'] : '';

            // Data zCIDeviceObj
            $arr[$key]['zmeasure_scale'] = !empty($zCIDeviceObj['zmeasure_scale']) ? $zCIDeviceObj['zmeasure_scale'] : '';
            $arr[$key]['zcapchinhxac'] = !empty($zCIDeviceObj['zcapchinhxac']) ? $zCIDeviceObj['zcapchinhxac'] : '';
            $arr[$key]['zresolution'] = !empty($zCIDeviceObj['zresolution']) ? $zCIDeviceObj['zresolution'] : '';
            $arr[$key]['zdochinhxac'] = !empty($zCIDeviceObj['zdochinhxac']) ? $zCIDeviceObj['zdochinhxac'] : '';

            // Data zCNNLObj
            $arr[$key]['z43uexpnum'] = !empty($zCNNLObj['z43uexpnum']) ? $zCNNLObj['z43uexpnum'] : '';
        }
        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);

        // Read Template Excel
        $template = storage_path('templates_excel/cong_nghe_nang_luong/danh_gia_dkdbd_thiet_bi_hieu_chinh_nhiet_do/danh_gia_dkdbd_thiet_bi_hieu_chinh_nhiet_do_' . count($arr) . '.xlsx');
        $spreadsheet = $reader->load($template);

        // Create New Writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);
        usort($arr, function ($a, $b) {
            return $a['zlaboratoryDate'] - $b['zlaboratoryDate'];
        });

        $sheet1 = $spreadsheet->setActiveSheetIndexByName("ĐKĐB_TBHC_NHIET_DO");
        $sheet2 = $spreadsheet->setActiveSheetIndexByName("THONG_TIN_KQ_THI_NGHIEM");
        $lengthRow = 20;
        foreach ($arr as $key => $val) {
            if ($key > 0) {
                // DuplicateStyle Table
                $sheet1->duplicateStyle($sheet1->getStyle('B33:E33'),'B' . (33 + $key * $lengthRow) . ':E' .(33 + $key * $lengthRow));
                $sheet1->duplicateStyle($sheet1->getStyle('B34:E49'),'B' . (34 + $key * $lengthRow) . ':E' .(49 + $key * $lengthRow));

                // Get value
                $customer = $sheet1->getCell('B34')->getValue();
                $area = $sheet1->getCell('B35')->getValue();
                $substation = $sheet1->getCell('B36')->getValue();
                $system = $sheet1->getCell('B37')->getValue();
                $equipment = $sheet1->getCell('B38')->getValue();
                $type = $sheet1->getCell('B39')->getValue();
                $manufacturer = $sheet1->getCell('B40')->getValue();
                $model = $sheet1->getCell('B41')->getValue();
                $serial_number = $sheet1->getCell('B42')->getValue();
                $scale = $sheet1->getCell('B43')->getValue();
                $resolution = $sheet1->getCell('B44')->getValue();
                $temperature = $sheet1->getCell('B45')->getValue();
                $humidity = $sheet1->getCell('B46')->getValue();
                $year = $sheet1->getCell('B47')->getValue();
                $country = $sheet1->getCell('B48')->getValue();
                $status = $sheet1->getCell('B49')->getValue();

                // Fix value table ĐKĐB_TBHC_NHIET_DO
                $sheet1->mergeCells("B" . (34 + $key * $lengthRow) . ":C" . (34 + $key * $lengthRow))->getCell('B' . (34 + $key * $lengthRow))->setValue($customer);
                $sheet1->mergeCells("B" . (35 + $key * $lengthRow) . ":C" . (35 + $key * $lengthRow))->getCell('B' . (35 + $key * $lengthRow))->setValue($area);
                $sheet1->mergeCells("B" . (36 + $key * $lengthRow) . ":C" . (36 + $key * $lengthRow))->getCell('B' . (36 + $key * $lengthRow))->setValue($substation);
                $sheet1->mergeCells("B" . (37 + $key * $lengthRow) . ":C" . (37 + $key * $lengthRow))->getCell('B' . (37 + $key * $lengthRow))->setValue($system);
                $sheet1->mergeCells("B" . (38 + $key * $lengthRow) . ":C" . (38 + $key * $lengthRow))->getCell('B' . (38 + $key * $lengthRow))->setValue($equipment);
                $sheet1->mergeCells("B" . (39 + $key * $lengthRow) . ":C" . (39 + $key * $lengthRow))->getCell('B' . (39 + $key * $lengthRow))->setValue($type);
                $sheet1->mergeCells("B" . (40 + $key * $lengthRow) . ":C" . (40 + $key * $lengthRow))->getCell('B' . (40 + $key * $lengthRow))->setValue($manufacturer);
                $sheet1->mergeCells("B" . (41 + $key * $lengthRow) . ":C" . (41 + $key * $lengthRow))->getCell('B' . (41 + $key * $lengthRow))->setValue($model);
                $sheet1->mergeCells("B" . (42 + $key * $lengthRow) . ":C" . (42 + $key * $lengthRow))->getCell('B' . (42 + $key * $lengthRow))->setValue($serial_number);
                $sheet1->mergeCells("B" . (43 + $key * $lengthRow) . ":C" . (43 + $key * $lengthRow))->getCell('B' . (43 + $key * $lengthRow))->setValue($scale);
                $sheet1->mergeCells("B" . (44 + $key * $lengthRow) . ":C" . (44 + $key * $lengthRow))->getCell('B' . (44 + $key * $lengthRow))->setValue($resolution);
                $sheet1->mergeCells("B" . (45 + $key * $lengthRow) . ":C" . (45 + $key * $lengthRow))->getCell('B' . (45 + $key * $lengthRow))->setValue($temperature);
                $sheet1->mergeCells("B" . (46 + $key * $lengthRow) . ":C" . (46 + $key * $lengthRow))->getCell('B' . (46 + $key * $lengthRow))->setValue($humidity);
                $sheet1->mergeCells("B" . (47 + $key * $lengthRow) . ":C" . (47 + $key * $lengthRow))->getCell('B' . (47 + $key * $lengthRow))->setValue($year);
                $sheet1->mergeCells("B" . (48 + $key * $lengthRow) . ":C" . (48 + $key * $lengthRow))->getCell('B' . (48 + $key * $lengthRow))->setValue($country);
                $sheet1->mergeCells("B" . (49 + $key * $lengthRow) . ":C" . (49 + $key * $lengthRow))->getCell('B' . (49 + $key * $lengthRow))->setValue($status);
            }

            // Sheet 2
            $sheet2->getCell('C' . (12 + $key * 1))->setValue(!empty($val['zlaboratoryDate']) ? date('d/m/Y', $val['zlaboratoryDate']) : '');
            $sheet2->getCell('D' . (12 + $key * 1))->setValue($val['z43uexpnum']);

            // Sheet 1
            $count = 0;
            for ($i = 33; $i <= 49; $i++) {
                $sheet1->duplicateStyle($sheet1->getStyle('B' . ($i + $key * $lengthRow)), 'A' . ($i + $key * $lengthRow));
                if ($count == 0) {
                    $sheet1->getCell('A' . ($i + $key * $lengthRow))->setValue('STT');
                } else {
                    $sheet1->getCell('A' . ($i + $key * $lengthRow))->setValue($count);
                }
                $count++;
            }

            $sheet1->mergeCells("B" . (33 + $key * $lengthRow) . ":E" . (33 + $key * $lengthRow))->getCell('B' . (33 + $key * $lengthRow))->setValue('BÁO CÁO KẾT QUẢ ĐÁNH GIÁ NGÀY'. ' '. (!empty($val['zlaboratoryDate']) ? date('d/m/Y', $val['zlaboratoryDate']) : ''));
            $sheet1->mergeCells("D" . (34 + $key * $lengthRow) . ":E" . (34 + $key * $lengthRow))->getCell('D' . (34 + $key * $lengthRow))->setValue($val['zCI_Device.zrefnr_dvql.zsym']);
            $sheet1->mergeCells("D" . (35 + $key * $lengthRow) . ":E" . (35 + $key * $lengthRow))->getCell('D' . (35 + $key * $lengthRow))->setValue($val['zCI_Device.zArea.zsym']);
            $sheet1->mergeCells("D" . (36 + $key * $lengthRow) . ":E" . (36 + $key * $lengthRow))->getCell('D' . (36 + $key * $lengthRow))->setValue($val['zCI_Device.zrefnr_td.zsym']);
            $sheet1->mergeCells("D" . (37 + $key * $lengthRow) . ":E" . (37 + $key * $lengthRow))->getCell('D' . (37 + $key * $lengthRow))->setValue($val['zCI_Device.zrefnr_nl.zsym']);
            $sheet1->mergeCells("D" . (38 + $key * $lengthRow) . ":E" . (38 + $key * $lengthRow))->getCell('D' . (38 + $key * $lengthRow))->setValue($val['zCI_Device.name']);
            $sheet1->mergeCells("D" . (39 + $key * $lengthRow) . ":E" . (39 + $key * $lengthRow))->getCell('D' . (39 + $key * $lengthRow))->setValue($val['zCI_Device.zCI_Device_Type.zsym']);
            $sheet1->mergeCells("D" . (40 + $key * $lengthRow) . ":E" . (40 + $key * $lengthRow))->getCell('D' . (40 + $key * $lengthRow))->setValue($val['zCI_Device.zManufacturer.zsym']);
            $sheet1->mergeCells("D" . (41 + $key * $lengthRow) . ":E" . (41 + $key * $lengthRow))->getCell('D' . (41 + $key * $lengthRow))->setValue($val['zCI_Device.zCI_Device_Kind.zsym']);
            $sheet1->mergeCells("D" . (42 + $key * $lengthRow) . ":E" . (42 + $key * $lengthRow))->getCell('D' . (42 + $key * $lengthRow))->setValue($val['zCI_Device.serial_number']);
            $sheet1->mergeCells("D" . (43 + $key * $lengthRow) . ":E" . (43 + $key * $lengthRow))->getCell('D' . (43 + $key * $lengthRow))->setValue($val['zmeasure_scale']);
            $sheet1->mergeCells("D" . (44 + $key * $lengthRow) . ":E" . (44 + $key * $lengthRow))->getCell('D' . (44 + $key * $lengthRow))->setValue($val['zcapchinhxac']);
            $sheet1->mergeCells("D" . (45 + $key * $lengthRow) . ":E" . (45 + $key * $lengthRow))->getCell('D' . (45 + $key * $lengthRow))->setValue($val['zresolution']);
            $sheet1->mergeCells("D" . (46 + $key * $lengthRow) . ":E" . (46 + $key * $lengthRow))->getCell('D' . (46 + $key * $lengthRow))->setValue($val['zdochinhxac']);
            $sheet1->mergeCells("D" . (47 + $key * $lengthRow) . ":E" . (47 + $key * $lengthRow))->getCell('D' . (47 + $key * $lengthRow))->setValue($val['zCI_Device.zYear_of_Manafacture.zsym']);
            $sheet1->mergeCells("D" . (48 + $key * $lengthRow) . ":E" . (48 + $key * $lengthRow))->getCell('D' . (48 + $key * $lengthRow))->setValue($val['zCI_Device.zCountry.name']);
            $sheet1->mergeCells("D" . (49 + $key * $lengthRow) . ":E" . (49 + $key * $lengthRow))->getCell('D' . (49 + $key * $lengthRow))->setValue($val['zCI_Device.zStage.zsym']);
        }

        $requestArr = formatRequest($request);
        fillSearchRequestToExcel($sheet2, $requestArr, ['B', 'C', 'D', 'E', 'F'], [], 4, true);

        $spreadsheet->setActiveSheetIndexByName("ĐKĐB_TBHC_NHIET_DO");
        // Output Permission
        $outputDir = public_path('export');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        // Output path
        $output = $outputDir . '/' . 'bao-cao-cong-nghe-nang-luong-nhiet-do'.'-'.time() .'-'.$user['ssid']. '.xlsx';
        if (is_file($output)) {
            unlink($output);
        }

        // Writer File Into Path
        $writer->save('export/bao-cao-cong-nghe-nang-luong-nhiet-do' . '-'.time() .'-'.$user['ssid']. '.xlsx');

        // Return Link Download
        return str_replace(public_path(''), getenv('APP_URL'), $output);
    }

    public function export_steam(Request $request)
    {
        $ids = $request->get('ids');
        $excel = $this->ExportSteamDevice($ids);
        return redirect($excel);
    }

    public function ExportSteamDevice(string $handleId)
    {
        // Get User Login Session
        $user = session()->get(env('AUTH_SESSION_KEY'));

        // Get Url Web Service
        $service = new CAWebServices(env('CA_WSDL_URL'));
        $handleIds = explode(',', $handleId);
        $arr = [];

        foreach ($handleIds as $key => $val) {
            // Query Data Service 'nr' Object
            $payload = [
                'sid' => $user['ssid'],
                'objectHandle' => 'nr:' . $val,
                'attributes' => [
                    'zlaboratoryDate',
                    'zCI_Device.zrefnr_dvql.zsym',
                    'zCI_Device.zCustomer.zsym',
                    'zCI_Device.zrefnr_td.zsym',
                    'zCI_Device.zrefnr_nl.zsym',
                    'zCI_Device.name',
                    'zCI_Device.zCI_Device_Type.zsym',
                    'zCI_Device.zManufacturer.zsym',
                    'zCI_Device.zCI_Device_Kind.zsym',
                    'zCI_Device.serial_number',
                    'zCI_Device',
                    'zCI_Device.zYear_of_Manafacture.zsym',
                    'zCI_Device.zCountry.name',
                    'zExperimental_Place',
                ]
            ];

            // Return Data Web Service
            $resp = $service->callFunction('getObjectValues', $payload);

            // Convert Data To Object
            $parser = new Converter($resp->getObjectValuesReturn);

            $items = [];

            // Writing Data Into Array
            foreach ($parser->data['UDSObject'][1]['Attributes'] as $attr) {
                $nrObj[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                array_push($items, $nrObj);
            }

            // Query Object Data
            $resp = $service->callFunction('getObjectValues', [
                'sid' => $user['ssid'],
                'objectHandle' => 'zCNNL18:' . $val,
                'attributes' => [
                    'zRate_Output_Test_DV',
                    'zRate_Output_Test_Result1',
                    'zRate_Output_Test_Result2',
                    'zRate_Output_Test_Result3',
                    'zRate_Output_Test_Result4',
                    'zTurbEffic_TestCdt_DV',
                    'zTurbEffic_TestCdt_Result1',
                    'zTurbEffic_TestCdt_Result2',
                    'zTurbEffic_TestCdt_Result3',
                    'zTurbEffic_TestCdt_Result4',
                    'zCorTEff_ReferCdt_DV',
                    'zCorTEff_ReferCdt_Result1',
                    'zCorTEff_ReferCdt_Result2',
                    'zCorTEff_ReferCdt_Result3',
                    'zCorTEff_ReferCdt_Result4',
                    'zTurbHeat_RateTCdt_DV',
                    'zTurbHeat_RateTCdt_Result1',
                    'zTurbHeat_RateTCdt_Result2',
                    'zTurbHeat_RateTCdt_Result3',
                    'zTurbHeat_RateTCdt_Result4',
                    'zCorTHeat_RateReCdt_DV',
                    'zCorTHeat_RateReCdt_Result1',
                    'zCorTHeat_RateReCdt_Result2',
                    'zCorTHeat_RateReCdt_Result3',
                    'zCorTHeat_RateReCdt_Result4',
                    'zFeedwater_Temp_DV',
                    'zFeedwater_Temp_Result1',
                    'zFeedwater_Temp_Result2',
                    'zFeedwater_Temp_Result3',
                    'zFeedwater_Temp_Result4',
                    'zCondenser_Vacuum_DV',
                    'zCondenser_Vacuum_Result1',
                    'zCondenser_Vacuum_Result2',
                    'zCondenser_Vacuum_Result3',
                    'zCondenser_Vacuum_Result4',
                    'zSuperheat_Press_DV',
                    'zSuperheat_Press_Result1',
                    'zSuperheat_Press_Result2',
                    'zSuperheat_Press_Result3',
                    'zSuperheat_Press_Result4',
                    'zSuperheat_Temp_DV',
                    'zSuperheat_Temp_Result1',
                    'zSuperheat_Temp_Result2',
                    'zSuperheat_Temp_Result3',
                    'zSuperheat_Temp_Result4',
                    'zTGTNHC_From',
                    'zTGTNHC_To',
                    'zTest_Run.zsym',
                    'zRate_Output_Test_Sym.zsym',
                    'zRate_Output_Test_Unit.zsym',
                    'zTurbEffic_TestCdt_Sym.zsym',
                    'zTurbEffic_TestCdt_Unit.zsym',
                    'zCorTEff_ReferCdt_Sym.zsym',
                    'zCorTEff_ReferCdt_Unit.zsym',
                    'zTurbHeat_RateTCdt_Sym.zsym',
                    'zTurbHeat_RateTCdt_Unit.zsym',
                    'zCorTHeat_RateReCdt_Sym.zsym',
                    'zCorTHeat_RateReCdt_Unit.zsym',
                    'zFeedwater_Temp_Sym.zsym',
                    'zFeedwater_Temp_Unit.zsym',
                    'zCondenser_Vacuum_Sym.zsym',
                    'zCondenser_Vacuum_Unit.zsym',
                    'zSuperheat_Press_Sym.zsym',
                    'zSuperheat_Press_Unit.zsym',
                    'zSuperheat_Temp_Sym.zsym',
                    'zSuperheat_Temp_Unit.zsym',
                ]
            ]);

            // Convert Object New
            $parser = new Converter($resp->getObjectValuesReturn);

            // Writing Data Into Array
            foreach ($parser->data['UDSObject'][1]['Attributes'] as $attr) {
                if (!$attr['Attribute'][1]['AttrValue']) {
                    continue;
                }
                $zCNNLObj[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
            }
            // Query Object Data zCIDevice
            foreach ($items as $val) {
                if(!empty($val['zCI_Device'])) {
                    // Get info
                    $payload = [
                        'sid' => $user['ssid'],
                        'objectType' => 'zETC_Device',
                        'whereClause' => "id = U'" . $val['zCI_Device'] . "'",
                        'maxRows' => 10,
                        'attributes' => [
                            'zscope_scale',
                            'zplace',
                            'zstage.zsym',
                        ]
                    ];
                    $resp = $service->callFunction('doSelect', $payload);
                    $parser = new Converter($resp->doSelectReturn);
                    if (!isset($parser->data['UDSObjectList'][0])) {
                        $parser->data['UDSObjectList'] = [$parser->data['UDSObjectList']];
                    }
                    foreach ($parser->data['UDSObjectList'] as $obj) {
                        if (!$obj) continue;
                        $zCIDeviceObj['handle_id'] = $obj['UDSObject'][0]['Handle'];
                        foreach ($obj['UDSObject'][1]['Attributes'] as $attr) {
                            if ($attr['Attribute'][1]['AttrValue']) {
                                $zCIDeviceObj[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                            }
                        }
                    }
                }
            }

            // Data nrObj
            $arr[$key]['zlaboratoryDate'] = !empty($nrObj['zlaboratoryDate']) ? date('d/m/Y', $nrObj['zlaboratoryDate']) : '';
            $arr[$key]['zCI_Device.zrefnr_dvql.zsym'] = !empty($nrObj['zCI_Device.zrefnr_dvql.zsym']) ? $nrObj['zCI_Device.zrefnr_dvql.zsym'] : '';
            $arr[$key]['zCI_Device.zCustomer.zsym'] = !empty($nrObj['zCI_Device.zCustomer.zsym']) ? $nrObj['zCI_Device.zCustomer.zsym'] : '';
            $arr[$key]['zCI_Device.zrefnr_td.zsym'] = !empty($nrObj['zCI_Device.zrefnr_td.zsym']) ? $nrObj['zCI_Device.zrefnr_td.zsym'] : '';
            $arr[$key]['zCI_Device.zrefnr_nl.zsym'] = !empty($nrObj['zCI_Device.zrefnr_nl.zsym']) ? $nrObj['zCI_Device.zrefnr_nl.zsym'] : '';
            $arr[$key]['zCI_Device.name'] = !empty($nrObj['zCI_Device.name']) ? $nrObj['zCI_Device.name'] : '';
            $arr[$key]['zCI_Device.zCI_Device_Type.zsym'] = !empty($nrObj['zCI_Device.zCI_Device_Type.zsym']) ? $nrObj['zCI_Device.zCI_Device_Type.zsym'] : '';
            $arr[$key]['zCI_Device.zManufacturer.zsym'] = !empty($nrObj['zCI_Device.zManufacturer.zsym']) ? $nrObj['zCI_Device.zManufacturer.zsym'] : '';
            $arr[$key]['zCI_Device.zCI_Device_Kind.zsym'] = !empty($nrObj['zCI_Device.zCI_Device_Kind.zsym']) ? $nrObj['zCI_Device.zCI_Device_Kind.zsym'] : '';
            $arr[$key]['zCI_Device.serial_number'] = !empty($nrObj['zCI_Device.serial_number']) ? $nrObj['zCI_Device.serial_number'] : '';
            $arr[$key]['zCI_Device.zYear_of_Manafacture.zsym'] = !empty($nrObj['zCI_Device.zYear_of_Manafacture.zsym']) ? $nrObj['zCI_Device.zYear_of_Manafacture.zsym'] : '';
            $arr[$key]['zCI_Device.zCountry.name'] = !empty($nrObj['zCI_Device.zCountry.name']) ? $nrObj['zCI_Device.zCountry.name'] : '';
            $arr[$key]['zExperimental_Place'] = !empty($nrObj['zExperimental_Place']) ? $nrObj['zExperimental_Place'] : '';


            // // Data zCIDeviceObj
            $arr[$key]['zplace'] = !empty($zCIDeviceObj['zplace']) ? $zCIDeviceObj['zplace'] : '';
            $arr[$key]['zstage.zsym'] = !empty($zCIDeviceObj['zstage.zsym']) ? $zCIDeviceObj['zstage.zsym'] : '';
            $arr[$key]['zscope_scale'] = !empty($zCIDeviceObj['zscope_scale']) ? $zCIDeviceObj['zscope_scale'] : '';


            // Data zCNNLObj Đơn vị
            $arr[$key]['zRate_Output_Test_Sym.zsym'] = !empty($zCNNLObj['zRate_Output_Test_Sym.zsym']) ? $zCNNLObj['zRate_Output_Test_Sym.zsym'] : '';
            $arr[$key]['zRate_Output_Test_Unit.zsym'] = !empty($zCNNLObj['zRate_Output_Test_Unit.zsym']) ? $zCNNLObj['zRate_Output_Test_Unit.zsym'] : '';
            $arr[$key]['zTurbEffic_TestCdt_Sym.zsym'] = !empty($zCNNLObj['zTurbEffic_TestCdt_Sym.zsym']) ? $zCNNLObj['zTurbEffic_TestCdt_Sym.zsym'] : '';
            $arr[$key]['zTurbEffic_TestCdt_Unit.zsym'] = !empty($zCNNLObj['zTurbEffic_TestCdt_Unit.zsym']) ? $zCNNLObj['zTurbEffic_TestCdt_Unit.zsym'] : '';
            $arr[$key]['zCorTEff_ReferCdt_Sym.zsym'] = !empty($zCNNLObj['zCorTEff_ReferCdt_Sym.zsym']) ? $zCNNLObj['zCorTEff_ReferCdt_Sym.zsym'] : '';
            $arr[$key]['zCorTEff_ReferCdt_Unit.zsym'] = !empty($zCNNLObj['zCorTEff_ReferCdt_Unit.zsym']) ? $zCNNLObj['zCorTEff_ReferCdt_Unit.zsym'] : '';
            $arr[$key]['zTurbHeat_RateTCdt_Sym.zsym'] = !empty($zCNNLObj['zTurbHeat_RateTCdt_Sym.zsym']) ? $zCNNLObj['zTurbHeat_RateTCdt_Sym.zsym'] : '';
            $arr[$key]['zTurbHeat_RateTCdt_Unit.zsym'] = !empty($zCNNLObj['zTurbHeat_RateTCdt_Unit.zsym']) ? $zCNNLObj['zTurbHeat_RateTCdt_Unit.zsym'] : '';
            $arr[$key]['zCorTHeat_RateReCdt_Sym.zsym'] = !empty($zCNNLObj['zCorTHeat_RateReCdt_Sym.zsym']) ? $zCNNLObj['zCorTHeat_RateReCdt_Sym.zsym'] : '';
            $arr[$key]['zCorTHeat_RateReCdt_Unit.zsym'] = !empty($zCNNLObj['zCorTHeat_RateReCdt_Unit.zsym']) ? $zCNNLObj['zCorTHeat_RateReCdt_Unit.zsym'] : '';
            $arr[$key]['zFeedwater_Temp_Sym.zsym'] = !empty($zCNNLObj['zFeedwater_Temp_Sym.zsym']) ? $zCNNLObj['zFeedwater_Temp_Sym.zsym'] : '';
            $arr[$key]['zFeedwater_Temp_Unit.zsym'] = !empty($zCNNLObj['zFeedwater_Temp_Unit.zsym']) ? $zCNNLObj['zFeedwater_Temp_Unit.zsym'] : '';
            $arr[$key]['zCondenser_Vacuum_Sym.zsym'] = !empty($zCNNLObj['zCondenser_Vacuum_Sym.zsym']) ? $zCNNLObj['zCondenser_Vacuum_Sym.zsym'] : '';
            $arr[$key]['zCondenser_Vacuum_Unit.zsym'] = !empty($zCNNLObj['zCondenser_Vacuum_Unit.zsym']) ? $zCNNLObj['zCondenser_Vacuum_Unit.zsym'] : '';
            $arr[$key]['zSuperheat_Press_Sym.zsym'] = !empty($zCNNLObj['zSuperheat_Press_Sym.zsym']) ? $zCNNLObj['zSuperheat_Press_Sym.zsym'] : '';
            $arr[$key]['zSuperheat_Press_Unit.zsym'] = !empty($zCNNLObj['zSuperheat_Press_Unit.zsym']) ? $zCNNLObj['zSuperheat_Press_Unit.zsym'] : '';
            $arr[$key]['zSuperheat_Temp_Sym.zsym'] = !empty($zCNNLObj['zSuperheat_Temp_Sym.zsym']) ? $zCNNLObj['zSuperheat_Temp_Sym.zsym'] : '';
            $arr[$key]['zSuperheat_Temp_Unit.zsym'] = !empty($zCNNLObj['zSuperheat_Temp_Unit.zsym']) ? $zCNNLObj['zSuperheat_Temp_Unit.zsym'] : '';

            // Data zCNNLObj - Tai thi nghiem
             $arr[$key]['zRate_Output_Test_DV'] = !empty($zCNNLObj['zRate_Output_Test_DV']) ? $zCNNLObj['zRate_Output_Test_DV'] : '';
             $arr[$key]['zRate_Output_Test_Result1'] = !empty($zCNNLObj['zRate_Output_Test_Result1']) ? $zCNNLObj['zRate_Output_Test_Result1'] : '';
             $arr[$key]['zRate_Output_Test_Result2'] = !empty($zCNNLObj['zRate_Output_Test_Result2']) ? $zCNNLObj['zRate_Output_Test_Result2'] : '';
             $arr[$key]['zRate_Output_Test_Result3'] = !empty($zCNNLObj['zRate_Output_Test_Result3']) ? $zCNNLObj['zRate_Output_Test_Result3'] : '';
             $arr[$key]['zRate_Output_Test_Result4'] = !empty($zCNNLObj['zRate_Output_Test_Result4']) ? $zCNNLObj['zRate_Output_Test_Result4'] : '';

             // Data zCNNLObj - hieu suat tua bin o dieu kien thi nghiem
             $arr[$key]['zTurbEffic_TestCdt_DV'] = !empty($zCNNLObj['zRate_Output_Test_DV']) ? $zCNNLObj['zRate_Output_Test_DV'] : '';
             $arr[$key]['zTurbEffic_TestCdt_Result1'] = !empty($zCNNLObj['zTurbEffic_TestCdt_Result1']) ? $zCNNLObj['zTurbEffic_TestCdt_Result1'] : '';
             $arr[$key]['zTurbEffic_TestCdt_Result2'] = !empty($zCNNLObj['zTurbEffic_TestCdt_Result2']) ? $zCNNLObj['zTurbEffic_TestCdt_Result2'] : '';
             $arr[$key]['zTurbEffic_TestCdt_Result3'] = !empty($zCNNLObj['zTurbEffic_TestCdt_Result3']) ? $zCNNLObj['zTurbEffic_TestCdt_Result3'] : '';
             $arr[$key]['zTurbEffic_TestCdt_Result4'] = !empty($zCNNLObj['zTurbEffic_TestCdt_Result4']) ? $zCNNLObj['zTurbEffic_TestCdt_Result4'] : '';

             // Data zCNNLObj - hieu suat tua bin o dieu kien dac tuyen
             $arr[$key]['zCorTEff_ReferCdt_DV'] = !empty($zCNNLObj['zCorTEff_ReferCdt_DV']) ? $zCNNLObj['zCorTEff_ReferCdt_DV'] : '';
             $arr[$key]['zCorTEff_ReferCdt_Result1'] = !empty($zCNNLObj['zCorTEff_ReferCdt_Result1']) ? $zCNNLObj['zCorTEff_ReferCdt_Result1'] : '';
             $arr[$key]['zCorTEff_ReferCdt_Result2'] = !empty($zCNNLObj['zCorTEff_ReferCdt_Result2']) ? $zCNNLObj['zCorTEff_ReferCdt_Result2'] : '';
             $arr[$key]['zCorTEff_ReferCdt_Result3'] = !empty($zCNNLObj['zCorTEff_ReferCdt_Result3']) ? $zCNNLObj['zCorTEff_ReferCdt_Result3'] : '';
             $arr[$key]['zCorTEff_ReferCdt_Result4'] = !empty($zCNNLObj['zCorTEff_ReferCdt_Result4']) ? $zCNNLObj['zCorTEff_ReferCdt_Result4'] : '';

            // Data zCNNLObj - suat tieu hao nhiet do dieu kien thi nghiem
            $arr[$key]['zTurbHeat_RateTCdt_DV'] = !empty($zCNNLObj['zTurbHeat_RateTCdt_DV']) ? $zCNNLObj['zTurbHeat_RateTCdt_DV'] : '';
            $arr[$key]['zTurbHeat_RateTCdt_Result1'] = !empty($zCNNLObj['zTurbHeat_RateTCdt_Result1']) ? $zCNNLObj['zTurbHeat_RateTCdt_Result1'] : '';
            $arr[$key]['zTurbHeat_RateTCdt_Result2'] = !empty($zCNNLObj['zTurbHeat_RateTCdt_Result2']) ? $zCNNLObj['zTurbHeat_RateTCdt_Result2'] : '';
            $arr[$key]['zTurbHeat_RateTCdt_Result3'] = !empty($zCNNLObj['zTurbHeat_RateTCdt_Result3']) ? $zCNNLObj['zTurbHeat_RateTCdt_Result3'] : '';
            $arr[$key]['zTurbHeat_RateTCdt_Result4'] = !empty($zCNNLObj['zTurbHeat_RateTCdt_Result4']) ? $zCNNLObj['zTurbHeat_RateTCdt_Result4'] : '';


            // Data zCNNLObj - suat tieu hao nhiet do dieu kien dac tuyen
            $arr[$key]['zCorTHeat_RateReCdt_DV'] = !empty($zCNNLObj['zCorTHeat_RateReCdt_DV']) ? $zCNNLObj['zCorTHeat_RateReCdt_DV'] : '';
            $arr[$key]['zCorTHeat_RateReCdt_Result1'] = !empty($zCNNLObj['zCorTHeat_RateReCdt_Result1']) ? $zCNNLObj['zCorTHeat_RateReCdt_Result1'] : '';
            $arr[$key]['zCorTHeat_RateReCdt_Result2'] = !empty($zCNNLObj['zCorTHeat_RateReCdt_Result2']) ? $zCNNLObj['zCorTHeat_RateReCdt_Result2'] : '';
            $arr[$key]['zCorTHeat_RateReCdt_Result3'] = !empty($zCNNLObj['zCorTHeat_RateReCdt_Result3']) ? $zCNNLObj['zCorTHeat_RateReCdt_Result3'] : '';
            $arr[$key]['zCorTHeat_RateReCdt_Result4'] = !empty($zCNNLObj['zCorTHeat_RateReCdt_Result4']) ? $zCNNLObj['zCorTHeat_RateReCdt_Result4'] : '';

            // Data zCNNLObj - Nhiet do nuoc cap
            $arr[$key]['zFeedwater_Temp_DV'] = !empty($zCNNLObj['zFeedwater_Temp_DV']) ? $zCNNLObj['zFeedwater_Temp_DV'] : '';
            $arr[$key]['zFeedwater_Temp_Result1'] = !empty($zCNNLObj['zFeedwater_Temp_Result1']) ? $zCNNLObj['zFeedwater_Temp_Result1'] : '';
            $arr[$key]['zFeedwater_Temp_Result2'] = !empty($zCNNLObj['zFeedwater_Temp_Result2']) ? $zCNNLObj['zFeedwater_Temp_Result2'] : '';
            $arr[$key]['zFeedwater_Temp_Result3'] = !empty($zCNNLObj['zFeedwater_Temp_Result3']) ? $zCNNLObj['zFeedwater_Temp_Result3'] : '';
            $arr[$key]['zFeedwater_Temp_Result4'] = !empty($zCNNLObj['zFeedwater_Temp_Result4']) ? $zCNNLObj['zFeedwater_Temp_Result4'] : '';

            // Data zCNNLObj - chan khong binh ngung
            $arr[$key]['zCondenser_Vacuum_DV'] = !empty($zCNNLObj['zCondenser_Vacuum_DV']) ? $zCNNLObj['zCondenser_Vacuum_DV'] : '';
            $arr[$key]['zCondenser_Vacuum_Result1'] = !empty($zCNNLObj['zCondenser_Vacuum_Result1']) ? $zCNNLObj['zCondenser_Vacuum_Result1'] : '';
            $arr[$key]['zCondenser_Vacuum_Result2'] = !empty($zCNNLObj['zCondenser_Vacuum_Result2']) ? $zCNNLObj['zCondenser_Vacuum_Result2'] : '';
            $arr[$key]['zCondenser_Vacuum_Result3'] = !empty($zCNNLObj['zCondenser_Vacuum_Result3']) ? $zCNNLObj['zCondenser_Vacuum_Result3'] : '';
            $arr[$key]['zCondenser_Vacuum_Result4'] = !empty($zCNNLObj['zCondenser_Vacuum_Result4']) ? $zCNNLObj['zCondenser_Vacuum_Result4'] : '';


            // Data zCNNLObj - Áp suất hơi quá nhiệt
            $arr[$key]['zSuperheat_Press_DV'] = !empty($zCNNLObj['zSuperheat_Press_DV']) ? $zCNNLObj['zSuperheat_Press_DV'] : '';
            $arr[$key]['zSuperheat_Press_Result1'] = !empty($zCNNLObj['zSuperheat_Press_Result1']) ? $zCNNLObj['zSuperheat_Press_Result1'] : '';
            $arr[$key]['zSuperheat_Press_Result2'] = !empty($zCNNLObj['zSuperheat_Press_Result2']) ? $zCNNLObj['zSuperheat_Press_Result2'] : '';
            $arr[$key]['zSuperheat_Press_Result3'] = !empty($zCNNLObj['zSuperheat_Press_Result3']) ? $zCNNLObj['zSuperheat_Press_Result3'] : '';
            $arr[$key]['zSuperheat_Press_Result4'] = !empty($zCNNLObj['zSuperheat_Press_Result4']) ? $zCNNLObj['zSuperheat_Press_Result4'] : '';

            // Data zCNNLObj - nhiet do hoi qua nhiet
            $arr[$key]['zSuperheat_Temp_DV'] = !empty($zCNNLObj['zSuperheat_Temp_DV']) ? $zCNNLObj['zSuperheat_Temp_DV'] : '';
            $arr[$key]['zSuperheat_Temp_Result1'] = !empty($zCNNLObj['zSuperheat_Temp_Result1']) ? $zCNNLObj['zSuperheat_Temp_Result1'] : '';
            $arr[$key]['zSuperheat_Temp_Result2'] = !empty($zCNNLObj['zSuperheat_Temp_Result2']) ? $zCNNLObj['zSuperheat_Temp_Result2'] : '';
            $arr[$key]['zSuperheat_Temp_Result3'] = !empty($zCNNLObj['zSuperheat_Temp_Result3']) ? $zCNNLObj['zSuperheat_Temp_Result3'] : '';
            $arr[$key]['zSuperheat_Temp_Result4'] = !empty($zCNNLObj['zSuperheat_Temp_Result4']) ? $zCNNLObj['zSuperheat_Temp_Result4'] : '';

            // Data zCNNLObj - tabale 3
            $arr[$key]['zTGTNHC_From'] = !empty($zCNNLObj['zTGTNHC_From']) ?  date('d/m/Y', $zCNNLObj['zTGTNHC_From']) : '';
            $arr[$key]['zTGTNHC_To'] = !empty($zCNNLObj['zTGTNHC_To']) ?  date('d/m/Y', $zCNNLObj['zTGTNHC_To']) : '';
            $arr[$key]['zTest_Run.zsym'] = !empty($zCNNLObj['zTest_Run.zsym']) ? $zCNNLObj['zTest_Run.zsym'] : '';
        }

        $check = checkValueChart($arr);
        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);

        // Read Template Excel
        $template = storage_path('templates_excel/cong_nghe_nang_luong/bao_cao_so_sanh_ket_qua_thi_nghiem_thong_so_tuanin_hoi.xlsx');
        $spreadsheet = $reader->load($template);

        // Create New Writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);

        foreach ($arr as $key => $val) {

           if ($key > 0) {
               // DuplicateStyle Table
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->duplicateStyle($spreadsheet->getActiveSheet()->getStyle('A' . (7) . ':E' . (20)),'A' . (7 + $key * 22) . ':E' .(20 + $key * 22));
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->duplicateStyle($spreadsheet->getActiveSheet()->getStyle('G' . (7) . ':N' . (18)),'G' . (7 + $key * 22) . ':N' .(18 + $key * 22));
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->duplicateStyle($spreadsheet->getActiveSheet()->getStyle('B' . (22) . ':E' . (25)),'B' . (22 + $key * 22) . ':E' .(25 + $key * 22));

               // Get value table 1
               $content = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('B7')->getValue();
               $customer = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('B8')->getValue();
               $area = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('B9')->getValue();
               $substation = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('B10')->getValue();
               $system = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('B11')->getValue();
               $equipment = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('B12')->getValue();
               $type = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('B13')->getValue();
               $manufacturer = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('B14')->getValue();
               $model = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('B15')->getValue();
               $serial_number = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('B16')->getValue();
               $year = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('B17')->getValue();
               $country = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('B18')->getValue();
               $place = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('B19')->getValue();
               $status = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('B20')->getValue();

               // Get value table 2

               $header_1 = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('G7')->getValue();
               $header_2 = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('H7')->getValue();
               $header_3 = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('I7')->getValue();
               $header_4 = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('J7')->getValue();
               $header_5 = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('K7')->getValue();
               $wattage = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('G8')->getValue();
               $performance_1 = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('G9')->getValue();
               $performance_2 = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('G10')->getValue();
               $consumption_1 = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('G11')->getValue();
               $consumption_2 = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('G12')->getValue();
               $temp_water = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('G13')->getValue();
               $Vacuum = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('G14')->getValue();
               $pressure = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('G15')->getValue();
               $steam_temperature = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('G16')->getValue();
               $steam_pressure = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('G17')->getValue();
               $steam_temperatures = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('G17')->getValue();

               // Get value table 3
               $row_1 = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('B22')->getValue();
               $row_2 = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('B23')->getValue();
               $row_3 = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('B24')->getValue();
               $row_4 = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('B25')->getValue();




               // Fix value table_1 KET_QUA_THI_NGHIEM_TUA_BIN_HOI
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("B" . (7 + $key * 22) . ":E" . (7 + $key * 22))->getCell('B' . (7 + $key * 22))->setValue($content);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("B" . (8 + $key * 22) . ":C" . (8 + $key * 22))->getCell('B' . (8 + $key * 22))->setValue($customer);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("B" . (9 + $key * 22) . ":C" . (9 + $key * 22))->getCell('B' . (9 + $key * 22))->setValue($area);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("B" . (10 + $key * 22) . ":C" . (10 + $key * 22))->getCell('B' . (10 + $key * 22))->setValue($substation);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("B" . (11 + $key * 22) . ":C" . (11 + $key * 22))->getCell('B' . (11 + $key * 22))->setValue($system);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("B" . (12 + $key * 22) . ":C" . (12 + $key * 22))->getCell('B' . (12 + $key * 22))->setValue($equipment);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("B" . (13 + $key * 22) . ":C" . (13 + $key * 22))->getCell('B' . (13 + $key * 22))->setValue($type);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("B" . (14 + $key * 22) . ":C" . (14 + $key * 22))->getCell('B' . (14 + $key * 22))->setValue($manufacturer);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("B" . (15 + $key * 22) . ":C" . (15 + $key * 22))->getCell('B' . (15 + $key * 22))->setValue($model);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("B" . (16 + $key * 22) . ":C" . (16 + $key * 22))->getCell('B' . (16 + $key * 22))->setValue($serial_number);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("B" . (17 + $key * 22) . ":C" . (17 + $key * 22))->getCell('B' . (17 + $key * 22))->setValue($year);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("B" . (18 + $key * 22) . ":C" . (18 + $key * 22))->getCell('B' . (18 + $key * 22))->setValue($country);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("B" . (19 + $key * 22) . ":C" . (19 + $key * 22))->getCell('B' . (19 + $key * 22))->setValue($place);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("B" . (20 + $key * 22) . ":C" . (20 + $key * 22))->getCell('B' . (20 + $key * 22))->setValue($status);

               // Fix value table_2 KET_QUA_THI_NGHIEM_TUA_BIN_HOI
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('G' . (7 + $key * 22))->setValue($header_1);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('H' . (7 + $key * 22))->setValue($header_2);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('I' . (7 + $key * 22))->setValue($header_3);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('J' . (7 + $key * 22))->setValue($header_4);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("K" . (7 + $key * 22) . ":N" . (7 + $key * 22))->getCell('K' . (7 + $key * 22))->setValue($header_5);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('G' . (8 + $key * 22))->setValue($wattage);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('G' . (9 + $key * 22))->setValue($performance_1);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('G' . (10 + $key * 22))->setValue($performance_2);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('G' . (11 + $key * 22))->setValue($consumption_1);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('G' . (12 + $key * 22))->setValue($consumption_2);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('G' . (13 + $key * 22))->setValue($temp_water);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('G' . (14 + $key * 22))->setValue($Vacuum);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('G' . (15 + $key * 22))->setValue($pressure);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('G' . (16 + $key * 22))->setValue($steam_temperature);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('G' . (17 + $key * 22))->setValue($steam_pressure);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('G' . (18 + $key * 22))->setValue($steam_temperatures);

               // Fix value table_3 KET_QUA_THI_NGHIEM_TUA_BIN_HOI
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("B" . (22 + $key * 22) . ":C" . (22 + $key * 22))->getCell('B' . (22 + $key * 22))->setValue($row_1);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("B" . (23 + $key * 22) . ":C" . (23 + $key * 22))->getCell('B' . (23 + $key * 22))->setValue($row_2);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("B" . (24 + $key * 22) . ":C" . (24 + $key * 22))->getCell('B' . (24 + $key * 22))->setValue($row_3);
               $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("B" . (25 + $key * 22) . ":C" . (25 + $key * 22))->getCell('B' . (25 + $key * 22))->setValue($row_4);

           }
        //    // Sheet 2
        //    $spreadsheet->setActiveSheetIndexByName("DATA")->getCell('C' . (8 + $key * 1))->setValue($val['zlaboratoryDate']);
        //    $spreadsheet->setActiveSheetIndexByName("DATA")->getCell('D' . (8 + $key * 1))->setValue($val['zDKDBD_Ut']);
        //    $spreadsheet->setActiveSheetIndexByName("DATA")->getCell('E' . (8 + $key * 1))->setValue($val['zDKDBD_Uh']);

           //Sheet 1
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("A" . (7 + $key * 22) . ":A" . (20 + $key * 22))->getCell('A' . (7 + $key * 22))->setValue('KẾT QUẢ CHÍNH THÍ NGHIỆM THÔNG SỐ TUA BIN HƠI CỦA NGÀY'. ' '. $val['zlaboratoryDate']);
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("D" . (8 + $key * 22) . ":E" . (8 + $key * 22))->getCell('D' . (8 + $key * 22))->setValue($val['zCI_Device.zrefnr_dvql.zsym']??'');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("D" . (9 + $key * 22) . ":E" . (9 + $key * 22))->getCell('D' . (9 + $key * 22))->setValue($val['zCI_Device.zCustomer.zsym']);
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("D" . (10 + $key * 22) . ":E" . (10 + $key * 22))->getCell('D' . (10 + $key * 22))->setValue($val['zCI_Device.zrefnr_td.zsym']);
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("D" . (11 + $key * 22) . ":E" . (11 + $key * 22))->getCell('D' . (11 + $key * 22))->setValue($val['zCI_Device.zrefnr_nl.zsym']??'');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("D" . (12 + $key * 22) . ":E" . (12 + $key * 22))->getCell('D' . (12 + $key * 22))->setValue($val['zCI_Device.name']??'');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("D" . (13 + $key * 22) . ":E" . (13 + $key * 22))->getCell('D' . (13 + $key * 22))->setValue($val['zCI_Device.zCI_Device_Type.zsym']??'');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("D" . (14 + $key * 22) . ":E" . (14 + $key * 22))->getCell('D' . (14 + $key * 22))->setValue($val['zCI_Device.zManufacturer.zsym']??'');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("D" . (15 + $key * 22) . ":E" . (15 + $key * 22))->getCell('D' . (15 + $key * 22))->setValue($val['zCI_Device.zCI_Device_Kind.zsym']??'');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("D" . (16 + $key * 22) . ":E" . (16 + $key * 22))->getCell('D' . (16 + $key * 22))->setValue($val['zCI_Device.serial_number']??'');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("D" . (17 + $key * 22) . ":E" . (17 + $key * 22))->getCell('D' . (17 + $key * 22))->setValue($val['zCI_Device.zYear_of_Manafacture.zsym']??'');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("D" . (18 + $key * 22) . ":E" . (18 + $key * 22))->getCell('D' . (18 + $key * 22))->setValue($val['zCI_Device.zCountry.name']??'');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("D" . (19 + $key * 22) . ":E" . (19 + $key * 22))->getCell('D' . (19 + $key * 22))->setValue($val['zplace']??'');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("D" . (20 + $key * 22) . ":E" . (20 + $key * 22))->getCell('D' . (20 + $key * 22))->setValue($val['zstage.zsym']??'');


            //Sheet 2-2
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("D" . (22 + $key * 22) . ":E" . (22 + $key * 22))->getCell('D' . (22 + $key * 22))->setValue($val['zCI_Device.name']??'');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("D" . (23 + $key * 22) . ":E" . (23 + $key * 22))->getCell('D' . (23 + $key * 22))->setValue($val['zCI_Device.zrefnr_dvql.zsym']??'');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("D" . (24 + $key * 22) . ":E" . (24 + $key * 22))->getCell('D' . (24 + $key * 22))->setValue($val['zTGTNHC_From'] . ' - ' . $val['zTGTNHC_To']??'');
            if (!empty($val['zExperimental_Place'])) {
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->mergeCells("D" . (25 + $key * 22) . ":E" . (25 + $key * 22))->getCell('D' . (25 + $key * 22))->setValue($val['zTest_Run.zsym']??'');
            }


            // Data value ky hieu
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('H' . (8 + $key * 22))->setValue(!empty($val['zRate_Output_Test_Sym.zsym'])? $val['zRate_Output_Test_Sym.zsym'] : '');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('H' . (9 + $key * 22))->setValue(!empty($val['zTurbEffic_TestCdt_Sym.zsym'])? $val['zTurbEffic_TestCdt_Sym.zsym']: '');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('H' . (10 + $key * 22))->setValue(!empty($val['zCorTEff_ReferCdt_Sym.zsym'])? $val['zCorTEff_ReferCdt_Sym.zsym']: '');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('H' . (11 + $key * 22))->setValue(!empty($val['zTurbHeat_RateTCdt_Sym.zsym'])? $val['zTurbHeat_RateTCdt_Sym.zsym']: '');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('H' . (12 + $key * 22))->setValue(!empty($val['zCorTHeat_RateReCdt_Sym.zsym'])? $val['zCorTHeat_RateReCdt_Sym.zsym']: '');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('H' . (13 + $key * 22))->setValue(!empty($val['zFeedwater_Temp_Sym.zsym'])? $val['zFeedwater_Temp_Sym.zsym']: '');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('H' . (14 + $key * 22))->setValue(!empty($val['zCondenser_Vacuum_Sym.zsym'])? $val['zCondenser_Vacuum_Sym.zsym']: '');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('H' . (15 + $key * 22))->setValue(!empty($val['zSuperheat_Press_Sym.zsym'])? $val['zSuperheat_Press_Sym.zsym']: '');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('H' . (16 + $key * 22))->setValue(!empty($val['zSuperheat_Temp_Sym.zsym'])? $val['zSuperheat_Temp_Sym.zsym']: '');

            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('I' . (8 + $key * 22))->setValue($val['zRate_Output_Test_Unit.zsym']??'');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('I' . (9 + $key * 22))->setValue($val['zTurbEffic_TestCdt_Unit.zsym']??'');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('I' . (10 + $key * 22))->setValue($val['zCorTEff_ReferCdt_Unit.zsym']??'');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('I' . (11 + $key * 22))->setValue($val['zTurbHeat_RateTCdt_Unit.zsym']??'');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('I' . (12 + $key * 22))->setValue($val['zCorTHeat_RateReCdt_Unit.zsym']??'');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('I' . (13 + $key * 22))->setValue($val['zFeedwater_Temp_Unit.zsym']??'');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('I' . (14 + $key * 22))->setValue($val['zCondenser_Vacuum_Unit.zsym']??'');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('I' . (15 + $key * 22))->setValue($val['zSuperheat_Press_Unit.zsym']??'');
            $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('I' . (16 + $key * 22))->setValue($val['zSuperheat_Temp_Unit.zsym']??'');


           //Sheet 2 - 1
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('J' . (8 + $key * 22))->setValue($val['zRate_Output_Test_DV']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('K' . (8 + $key * 22))->setValue($val['zRate_Output_Test_Result1']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('L' . (8 + $key * 22))->setValue($val['zRate_Output_Test_Result2']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('M' . (8 + $key * 22))->setValue($val['zRate_Output_Test_Result3']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('N' . (8 + $key * 22))->setValue($val['zRate_Output_Test_Result4']??'');

           //Sheet 2 - 2
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('J' . (9 + $key * 22))->setValue($val['zTurbEffic_TestCdt_DV']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('K' . (9 + $key * 22))->setValue($val['zTurbEffic_TestCdt_Result1']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('L' . (9 + $key * 22))->setValue($val['zTurbEffic_TestCdt_Result2']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('M' . (9 + $key * 22))->setValue($val['zTurbEffic_TestCdt_Result3']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('N' . (9 + $key * 22))->setValue($val['zTurbEffic_TestCdt_Result4']??'');

           //Sheet 2 - 3
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('J' . (10 + $key * 22))->setValue($val['zCorTEff_ReferCdt_DV']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('K' . (10 + $key * 22))->setValue($val['zCorTEff_ReferCdt_Result1']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('L' . (10 + $key * 22))->setValue($val['zCorTEff_ReferCdt_Result2']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('M' . (10 + $key * 22))->setValue($val['zCorTEff_ReferCdt_Result3']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('N' . (10 + $key * 22))->setValue($val['zCorTEff_ReferCdt_Result4']??'');

           //Sheet 2 - 4
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('J' . (11 + $key * 22))->setValue($val['zTurbHeat_RateTCdt_DV']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('K' . (11 + $key * 22))->setValue($val['zTurbHeat_RateTCdt_Result1']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('L' . (11 + $key * 22))->setValue($val['zTurbHeat_RateTCdt_Result2']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('M' . (11 + $key * 22))->setValue($val['zTurbHeat_RateTCdt_Result3']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('N' . (11 + $key * 22))->setValue($val['zTurbHeat_RateTCdt_Result4']??'');

           //Sheet 2 - 5
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('J' . (12 + $key * 22))->setValue($val['zCorTHeat_RateReCdt_DV']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('K' . (12 + $key * 22))->setValue($val['zCorTHeat_RateReCdt_Result1']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('L' . (12 + $key * 22))->setValue($val['zCorTHeat_RateReCdt_Result2']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('M' . (12 + $key * 22))->setValue($val['zCorTHeat_RateReCdt_Result3']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('N' . (12 + $key * 22))->setValue($val['zCorTHeat_RateReCdt_Result4']??'');

           //Sheet 2 - 5
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('J' . (13 + $key * 22))->setValue($val['zFeedwater_Temp_DV']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('K' . (13 + $key * 22))->setValue($val['zFeedwater_Temp_Result1']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('L' . (13 + $key * 22))->setValue($val['zFeedwater_Temp_Result2']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('M' . (13 + $key * 22))->setValue($val['zFeedwater_Temp_Result3']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('N' . (13 + $key * 22))->setValue($val['zFeedwater_Temp_Result4']??'');

           //Sheet 2 - 6
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('J' . (14 + $key * 22))->setValue($val['zCondenser_Vacuum_DV']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('K' . (14 + $key * 22))->setValue($val['zCondenser_Vacuum_Result1']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('L' . (14 + $key * 22))->setValue($val['zCondenser_Vacuum_Result2']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('M' . (14 + $key * 22))->setValue($val['zCondenser_Vacuum_Result3']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('N' . (14 + $key * 22))->setValue($val['zCondenser_Vacuum_Result4']??'');

           //Sheet 2 - 7
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('J' . (15 + $key * 22))->setValue($val['zSuperheat_Press_DV']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('K' . (15 + $key * 22))->setValue($val['zSuperheat_Press_Result1']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('L' . (15 + $key * 22))->setValue($val['zSuperheat_Press_Result2']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('M' . (15 + $key * 22))->setValue($val['zSuperheat_Press_Result3']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('N' . (15 + $key * 22))->setValue($val['zSuperheat_Press_Result4']??'');

           //Sheet 2 - 8
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('J' . (16 + $key * 22))->setValue($val['zSuperheat_Temp_DV']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('K' . (16 + $key * 22))->setValue($val['zSuperheat_Temp_Result1']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('L' . (16 + $key * 22))->setValue($val['zSuperheat_Temp_Result2']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('M' . (16 + $key * 22))->setValue($val['zSuperheat_Temp_Result3']??'');
           $spreadsheet->setActiveSheetIndexByName("KET_QUA_THI_NGHIEM_TUA_BIN_HOI")->getCell('N' . (16 + $key * 22))->setValue($val['zSuperheat_Temp_Result4']??'');

           if ( $check == 1 ) {
             //Sheet Data_1
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ1")->getCell('C8')->setValue($val['zRate_Output_Test_Result1']);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ1")->getCell('D8')->setValue($val['zRate_Output_Test_Result2']);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ1")->getCell('E8')->setValue($val['zRate_Output_Test_Result3']);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ1")->getCell('F8')->setValue($val['zRate_Output_Test_Result4']);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ1")->getCell('A' . (9 + $key * 1))->setValue($val['zlaboratoryDate']);
             $getvalue_1 = $spreadsheet->setActiveSheetIndexByName("DATA_BĐ1")->getCell('B9')->getValue();
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ1")->getCell('B' . (9 + $key * 1))->setValue($getvalue_1);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ1")->getCell('C' . (9 + $key * 1))->setValue($val['zTurbEffic_TestCdt_Result1']??'');
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ1")->getCell('D' . (9 + $key * 1))->setValue($val['zTurbEffic_TestCdt_Result2']??'');
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ1")->getCell('E' . (9 + $key * 1))->setValue($val['zTurbEffic_TestCdt_Result3']??'');
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ1")->getCell('F' . (9 + $key * 1))->setValue($val['zTurbEffic_TestCdt_Result4']??'');

             // Sheet Data_2
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ2")->getCell('C8')->setValue($val['zRate_Output_Test_Result1']);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ2")->getCell('D8')->setValue($val['zRate_Output_Test_Result2']);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ2")->getCell('E8')->setValue($val['zRate_Output_Test_Result3']);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ2")->getCell('F8')->setValue($val['zRate_Output_Test_Result4']);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ2")->getCell('A' . (9 + $key * 1))->setValue($val['zlaboratoryDate']);
             $getvalue_2 = $spreadsheet->setActiveSheetIndexByName("DATA_BĐ2")->getCell('B9')->getValue();
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ2")->getCell('B' . (9 + $key * 1))->setValue($getvalue_2);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ2")->getCell('C' . (9 + $key * 1))->setValue($val['zTurbHeat_RateTCdt_Result1']??'');
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ2")->getCell('D' . (9 + $key * 1))->setValue($val['zTurbHeat_RateTCdt_Result2']??'');
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ2")->getCell('E' . (9 + $key * 1))->setValue($val['zTurbHeat_RateTCdt_Result3']??'');
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ2")->getCell('F' . (9 + $key * 1))->setValue($val['zTurbHeat_RateTCdt_Result4']??'');

             // Sheet Data_3
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ3")->getCell('C8')->setValue($val['zRate_Output_Test_Result1']);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ3")->getCell('D8')->setValue($val['zRate_Output_Test_Result2']);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ3")->getCell('E8')->setValue($val['zRate_Output_Test_Result3']);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ3")->getCell('F8')->setValue($val['zRate_Output_Test_Result4']);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ3")->getCell('A' . (9 + $key * 1))->setValue($val['zlaboratoryDate']);
             $getvalue_3 = $spreadsheet->setActiveSheetIndexByName("DATA_BĐ3")->getCell('B9')->getValue();
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ3")->getCell('B' . (9 + $key * 1))->setValue($getvalue_3);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ3")->getCell('C' . (9 + $key * 1))->setValue($val['zCorTEff_ReferCdt_Result1']??'');
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ3")->getCell('D' . (9 + $key * 1))->setValue($val['zCorTEff_ReferCdt_Result2']??'');
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ3")->getCell('E' . (9 + $key * 1))->setValue($val['zCorTEff_ReferCdt_Result3']??'');
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ3")->getCell('F' . (9 + $key * 1))->setValue($val['zCorTEff_ReferCdt_Result4']??'');

             // Sheet Data_4
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ4")->getCell('C8')->setValue($val['zRate_Output_Test_Result1']);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ4")->getCell('D8')->setValue($val['zRate_Output_Test_Result2']);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ4")->getCell('E8')->setValue($val['zRate_Output_Test_Result3']);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ4")->getCell('F8')->setValue($val['zRate_Output_Test_Result4']);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ4")->getCell('A' . (9 + $key * 1))->setValue($val['zlaboratoryDate']);
             $getvalue_4 = $spreadsheet->setActiveSheetIndexByName("DATA_BĐ4")->getCell('B9')->getValue();
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ4")->getCell('B' . (9 + $key * 1))->setValue($getvalue_4);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ4")->getCell('C' . (9 + $key * 1))->setValue($val['zCorTHeat_RateReCdt_Result1']??'');
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ4")->getCell('D' . (9 + $key * 1))->setValue($val['zCorTHeat_RateReCdt_Result2']??'');
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ4")->getCell('E' . (9 + $key * 1))->setValue($val['zCorTHeat_RateReCdt_Result3']??'');
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ4")->getCell('F' . (9 + $key * 1))->setValue($val['zCorTHeat_RateReCdt_Result4']??'');

             // Sheet Data_5
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ5")->getCell('C8')->setValue($val['zRate_Output_Test_Result1']);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ5")->getCell('D8')->setValue($val['zRate_Output_Test_Result2']);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ5")->getCell('E8')->setValue($val['zRate_Output_Test_Result3']);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ5")->getCell('F8')->setValue($val['zRate_Output_Test_Result4']);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ5")->getCell('A' . (9 + $key * 1))->setValue($val['zlaboratoryDate']);
             $getvalue_5 = $spreadsheet->setActiveSheetIndexByName("DATA_BĐ5")->getCell('B9')->getValue();
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ5")->getCell('B' . (9 + $key * 1))->setValue($getvalue_5);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ5")->getCell('C' . (9 + $key * 1))->setValue($val['zFeedwater_Temp_Result1']??'');
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ5")->getCell('D' . (9 + $key * 1))->setValue($val['zFeedwater_Temp_Result2']??'');
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ5")->getCell('E' . (9 + $key * 1))->setValue($val['zFeedwater_Temp_Result3']??'');
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ5")->getCell('F' . (9 + $key * 1))->setValue($val['zFeedwater_Temp_Result4']??'');

             // Sheet Data_6
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ6")->getCell('C8')->setValue($val['zRate_Output_Test_Result1']);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ6")->getCell('D8')->setValue($val['zRate_Output_Test_Result2']);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ6")->getCell('E8')->setValue($val['zRate_Output_Test_Result3']);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ6")->getCell('F8')->setValue($val['zRate_Output_Test_Result4']);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ6")->getCell('A' . (9 + $key * 1))->setValue($val['zlaboratoryDate']);
             $getvalue_6 = $spreadsheet->setActiveSheetIndexByName("DATA_BĐ6")->getCell('B9')->getValue();
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ6")->getCell('B' . (9 + $key * 1))->setValue($getvalue_6);
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ6")->getCell('C' . (9 + $key * 1))->setValue($val['zCondenser_Vacuum_Result1']??'');
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ6")->getCell('D' . (9 + $key * 1))->setValue($val['zCondenser_Vacuum_Result2']??'');
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ6")->getCell('E' . (9 + $key * 1))->setValue($val['zCondenser_Vacuum_Result3']??'');
             $spreadsheet->setActiveSheetIndexByName("DATA_BĐ6")->getCell('F' . (9 + $key * 1))->setValue($val['zCondenser_Vacuum_Result4']??'');
            } else {
                $spreadsheet->setActiveSheetIndexByName("BIEU_DO")->getCell('B5')->setValue("Biểu đồ sự thay đổi hiệu suất tua bin ở điều kiện thí nghiệm - Dữ liệu đầu vào không hợp lệ");
                $spreadsheet->setActiveSheetIndexByName("BIEU_DO")->getCell('B31')->setValue("Biểu đồ sự thay đổi Suất tiêu hao nhiệt ở điều kiện thí nghiệm - Dữ liệu đầu vào không hợp lệ");
                $spreadsheet->setActiveSheetIndexByName("BIEU_DO")->getCell('B57')->setValue("Biểu đồ sự thay đổi hiệu suất tua bin ở điều kiện đặc tuyến - Dữ liệu đầu vào không hợp lệ");
                $spreadsheet->setActiveSheetIndexByName("BIEU_DO")->getCell('B83')->setValue("Biểu đồ sự thay đổi suất tiêu hao nhiệt ở điều kiện đặc tuyến - Dữ liệu đầu vào không hợp lệ");
                $spreadsheet->setActiveSheetIndexByName("BIEU_DO")->getCell('B109')->setValue("Biểu đồ sự thay đổi nhiệt độ nước cấp - Dữ liệu đầu vào không hợp lệ");
                $spreadsheet->setActiveSheetIndexByName("BIEU_DO")->getCell('B135')->setValue("Biểu đồ sự thay đổi nhiệt độ nước cấp - Dữ liệu đầu vào không hợp lệ");
            }
           // active sheet
           $spreadsheet->setActiveSheetIndexByName("BIEU_DO");
        }

       // Output Permission
       $outputDir = public_path('export');
       if (!is_dir($outputDir)) {
           mkdir($outputDir, 0777, true);
       }

       // Output path
       // $output = $outputDir . '/' . 'bao-cao-cong-nghe-nang-luong'.'-'.time() .'-'.$user['ssid']. '.xlsx';
       $output = $outputDir . '/' . 'bao-cao-cong-nghe-nang-luong-tua-bin-hoi'.'-'.time() .'-'.$user['ssid']. '.xlsx';
       if (is_file($output)) {
           unlink($output);
       }
       // Writer File Into Path
       $writer->save('export/bao-cao-cong-nghe-nang-luong-tua-bin-hoi' . '-'.time() .'-'.$user['ssid']. '.xlsx');

       // Return Link Download
       return str_replace(public_path(''), getenv('APP_URL'), $output);

    }

    /**
     * Boiler by manufacture
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function boilersByManufacture(Request $request)
    {
        $request = $request->all();
        $request['type'] = 'BM02.QT05 Biên bản thử nghiệm hiệu suất lò hơi tải dầu công nghiệp';
        $request['experiment_type'] = 'zCNNL1';

        $items = !empty($request['devices']) ? getEnergyReport($request['type'], $request) : [];
        sortData($items, 'asc', 'zlaboratoryDate');

        $types = collect(getDataFromService('doSelect', 'grc', ['id', 'type'], 'zetc_type = 0 AND zCNNL = 1', 300))->pluck('type', 'id')->toArray();
        Arr::sort($types);
        $deviceTypes = !empty($request['devices']) ? collect(getDataFromService('doSelect', 'zCI_Device_Type', ['id', 'zsym'], 'zsym IS NOT NULL AND zclass = ' . $request['devices'], 300))->pluck('zsym', 'id')->toArray() : [];
        Arr::sort($deviceTypes);
        $dvqls = $this->getDVQL();
        $tds = $this->getTD($request);
        $nls = $this->getNL($request);
        return view('enery::boiler.index', compact('items', 'request', 'types', 'deviceTypes', 'dvqls', 'tds', 'nls'));
    }

    /**
     * Export boiler by manufacture
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportBoilersByManufacture(Request $request)
    {
        return Excel::download(new BoilersByManufactureExport($request), 'Báo cáo kết quả đánh gía hiệu suất nồi hơi công nghiệp tải dầu theo hãng sản xuất.xlsx');
    }

    /**
     * Industrial furnace by manufacture
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function industrialFurnaceByManufacture(Request $request)
    {
        $request = $request->all();
        $request['type'] = 'BM01.QT05 Biên bản thử nghiệm hiệu suất lò hơi công nghiệp';
        $request['experiment_type'] = 'zCNNL2';

        $items = !empty($request['devices']) ? getEnergyReport($request['type'], $request) : [];
        sortData($items, 'asc', 'zlaboratoryDate');

        $types = collect(getDataFromService('doSelect', 'grc', ['id', 'type'], 'zetc_type = 0 AND zCNNL = 1', 300))->pluck('type', 'id')->toArray();

        $deviceTypes = !empty($request['devices']) ? collect(getDataFromService('doSelect', 'zCI_Device_Type', ['id', 'zsym'], 'zsym IS NOT NULL AND zclass = ' . $request['devices'], 300))->pluck('zsym', 'id')->toArray() : [];
        $dvqls = $this->getDVQL();
        $tds = $this->getTD($request);
        $nls = $this->getNL($request);
        return view('enery::industrial_furnace.index', compact('items', 'request', 'types', 'deviceTypes', 'dvqls', 'tds', 'nls'));
    }

    /**
     * Export industrial furnace by manufacture
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportIndustrialFurnaceByManufacture(Request $request)
    {
        return Excel::download(new IndustrialFurnaceByManufactureExport($request), 'Báo cáo đánh giá hiệu suất lò công nghiệp theo Hãng - nhiên liệu.xlsx');
    }

    /**
     * Get gas turbine parameters detail
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function gasTurbineParametersDetail(Request $request)
    {
        $oldRequest = $request;
        $statisticsAttr = [
            'arrDVExtendType' => [
                'zRate_Output_Test',
                'zTurbEffic_TestCdt',
                'zCorTEff_ReferCdt',
                'zTurbHeat_RateTCdt',
                'zCorTHeat_RateReCdt',
                'zGasTur_ExhTemp',
                'zDsgDiff_InletPress',
                'zDsgDiff_OutletPress',
            ]
        ];
        $chartDataArr = [
            'arrDVExtendType' => [
                'zRate_Output_Test',
                'zCorTEff_ReferCdt',
                'zTurbEffic_TestCdt',
                'zTurbHeat_RateTCdt',
                'zCorTHeat_RateReCdt',
                'zGasTur_ExhTemp',
                'zDsgDiff_InletPress',
                'zDsgDiff_OutletPress',
            ]
        ];
        $statistics = formatInputDataEnergyReport($statisticsAttr);
        $chartData = formatInputDataEnergyReport($chartDataArr);
        $request = [
            'ids' => $request->ids,
            'report_type' => 'zCNNL17',
            'statistics' => $statistics,
            'other_info' => 'zCI_Device.name',
            'chart_data' => $chartData,
            'excel_template' => 'bao_cao_so_sanh_ket_qua_thi_nghiem_thong_so_tuabin_khi',
            'start_row' => 4,
            'second_start_row' => 19,
            'excel_item_info_height' => 13,
            'excel_item_info_title' => 'KẾT QUẢ CHÍNH THÍ NGHIỆM HIỆU CHỈNH TUA BIN KHÍ',
            'excel_item_other_info_height' => 3,
            'excel_item_statistics_height' => 8,
            'statistics_first_insertion_row' => 5,
            'number_chart_data' => 7,
            'chart_first_insertion_row' => 8,
            'chart_last_insertion_row' => 25,
            'chart_data_invalid_input_msg_index' => ['B5', 'B31', 'B57', 'B83', 'B109', 'B135', 'B161']
        ];
        $excel = exportEnergyReport($request, $oldRequest);
        return view('enery::detail', [
            'ids' => $request['ids'],
            'excel' => $excel,
            'title' => 'Báo cáo thống kê kết quả thí nghiệm thông số tuabin khí',
            'path' => 'bao-cao-so-sanh-ket-qua-thi-nghiem-thong-tuabin-khi'
        ]);
    }

    /**
     * Get steam turbine parameters detail
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function steamTurbineParametersDetail(Request $request)
    {
        $oldRequest = $request;
        $statisticsAttr = [
            'arrDVExtendType' => [
                'zRate_Output_Test',
                'zTurbEffic_TestCdt',
                'zCorTEff_ReferCdt',
                'zTurbHeat_RateTCdt',
                'zCorTHeat_RateReCdt',
                'zFeedwater_Temp',
                'zCondenser_Vacuum',
                'zSuperheat_Press',
                'zSuperheat_Temp',
                'zReheat_pressure',
                'zRheat_temperture'
            ]
        ];
        $chartDataArr = [
            'arrDVExtendType' => [
                'zRate_Output_Test',
                'zTurbEffic_TestCdt',
                'zTurbHeat_RateTCdt',
                'zCorTEff_ReferCdt',
                'zCorTHeat_RateReCdt',
                'zFeedwater_Temp',
                'zCondenser_Vacuum',
            ]
        ];
        $statistics = formatInputDataEnergyReport($statisticsAttr);
        $chartData = formatInputDataEnergyReport($chartDataArr);
        $request = [
            'ids' => $request->ids,
            'report_type' => 'zCNNL18',
            'statistics' => $statistics,
            'other_info' => 'zCI_Device.name',
            'chart_data' => $chartData,
            'excel_template' => 'bao_cao_so_sanh_ket_qua_thi_nghiem_thong_so_tuabin_hoi',
            'start_row' => 4,
            'second_start_row' => 19,
            'excel_item_info_height' => 13,
            'excel_item_info_title' => 'KẾT QUẢ CHÍNH THÍ NGHIỆM THÔNG SÔ TUABIN HƠI',
            'excel_item_other_info_height' => 3,
            'excel_item_statistics_height' => 11,
            'statistics_first_insertion_row' => 5,
            'number_chart_data' => 6,
            'chart_first_insertion_row' => 8,
            'chart_last_insertion_row' => 25,
            'chart_data_invalid_input_msg_index' => ['B5', 'B31', 'B57', 'B83', 'B109', 'B135']
        ];
        $excel = exportEnergyReport($request, $oldRequest);
        return view('enery::detail', [
            'ids' => $request['ids'],
            'excel' => $excel,
            'title' => 'Báo cáo thống kê kết quả thí nghiệm thông số tuabin hơi',
            'path' => 'bao-cao-so-sanh-ket-qua-thi-nghiem-thong-so-tuabin-hoi'
        ]);
    }

    /**
     * Get unit characteristic measurement detail
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function unitCharacteristicMeasurementDetail(Request $request)
    {
        $oldRequest = $request;
        $statisticsAttr = [
            'arrDVExtendType' => [
                'zRate_Output_Test',
                'zUnit_Gross_Effi',
                'zUnit_Net_Effi',
                'zUnit_Gross_HR',
                'zUnit_Net_HR',
                'zUnit_Gross_CCR',
                'zUnit_Net_CCR',
                'zAuxP_GOR',
                'zPowerM_DOF'
            ]
        ];
        $chartDataArr = [
            'arrDesignValueExtendType' => [
                'zRate_Output_Test',
                'zUnit_Gross_Effi',
                'zUnit_Net_Effi',
                'zUnit_Gross_HR',
                'zUnit_Net_HR',
            ]
        ];
        $statistics = formatInputDataEnergyReport($statisticsAttr);
        $chartData = formatInputDataEnergyReport($chartDataArr);
        $request = [
            'ids' => $request->ids, // Id of selected report in index page
            'report_type' => 'zCNNL20', // Object type
            'statistics' => $statistics, // Data of statistics table in experimental results sheet
            'other_info' => 'zCI_Device.zrefnr_nl.zsym', // Flexible data in second common table of experimental results sheet
            'chart_data' => $chartData, // Data of charts sheet
            'excel_template' => 'bao_cao_danh_gia_ket_qua_thi_nghiem_do_dac_tuyen_to_may', // Template excel name
            'start_row' => 4, // Start data row in experimental results sheet
            'second_start_row' => 19, // Second start data row in experimental results sheet - used for second table of common information
            'excel_item_info_height' => 13, // Number of data row in experimental results sheet - used for first table of common information
            'excel_item_info_title' => 'KẾT QUẢ CHÍNH THÍ NGHIỆM ĐO ĐẶC TUYẾN TỔ MÁY PHÁT ĐIỆN', // First column content of first table in common information/experimental results sheet
            'excel_item_other_info_height' => 3, // Number of data row in experimental results sheet - used for second table of common information
            'excel_item_statistics_height' => 9, // Number of data row in experimental results sheet - used for statistics table
            'statistics_first_insertion_row' => 5, // First insertion data row in experimental results sheet
            'number_chart_data' => 4, // Number of chart data
            'chart_first_insertion_row' => 8, // First insertion data row in chart data sheet
            'chart_last_insertion_row' => 25, // Last insertion data row in chart data sheet
            'chart_data_invalid_input_msg_index' => ['B5', 'B31', 'B57', 'B83'], // Insertion position index of invalid input content
        ];
        $excel = exportEnergyReport($request, $oldRequest);
        return view('enery::detail', [
            'ids' => $request['ids'],
            'excel' => $excel,
            'title' => 'Báo cáo thống kê kết quả thí nghiệm tổ máy',
            'path' => 'bao-cao-danh-gia-ket-qua-thi-nghiem-do-dac-tuyen-to-may'
        ]);
    }

    /**
     * Get large boiler parameters detail
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function largeBoilerParametersDetail(Request $request)
    {
        $oldRequest = $request;
        $statisticsAttr = [
            'arrDesignValueExtendType' => [
                'zTest_RO',
                'zMain_SF',
                'zBoiler_Effic',
                'zGas_Temp_LAP',
                'zCoeff_Excess_ALF',
                'zCoeff_Excess_AEG',
                'zAir_Heater_Leak',
                'zCoal_Fineness',
                'zCoal_Fine_MA',
                'zCoal_Fine_MB',
                'zCoal_Fine_MC',
                'zCoal_Fine_MD',
                'zCoal_Fine_ME',
                'zCoal_Fine_MF'
            ]
        ];
        $chartDataArr = [
            'arrDesignValueExtendType' => [
                'zTest_RO',
                'zBoiler_Effic',
                'zCoeff_Excess_ALF',
                'zGas_Temp_LAP',
                'zAir_Heater_Leak'
            ]
        ];
        $statistics = formatInputDataEnergyReport($statisticsAttr);
        $chartData = formatInputDataEnergyReport($chartDataArr);
        $request = [
            'ids' => $request->ids,
            'report_type' => 'zCNNL16',
            'statistics' => $statistics,
            'other_info' => 'zCI_Device.name',
            'chart_data' => $chartData,
            'excel_template' => 'bao_cao_so_sanh_ket_qua_thi_nghiem_thong_so_lo_hoi_lon',
            'start_row' => 4,
            'second_start_row' => 19,
            'excel_item_info_height' => 13,
            'excel_item_info_title' => 'KẾT QUẢ CHÍNH THÍ NGHIỆM HIỆU CHỈNH LÒ HƠI',
            'excel_item_other_info_height' => 3,
            'excel_item_statistics_height' => 14,
            'statistics_first_insertion_row' => 5,
            'number_chart_data' => 4,
            'chart_first_insertion_row' => 8,
            'chart_last_insertion_row' => 25,
            'chart_data_invalid_input_msg_index' => ['B5', 'B31', 'B57', 'B83']
        ];
        $excel = exportEnergyReport($request, $oldRequest);
        return view('enery::detail', [
            'ids' => $request['ids'],
            'excel' => $excel,
            'title' => 'Báo cáo thống kê kết quả thí nghiệm thông số lò hơi lớn ',
            'path' => 'bao-cao-so-sanh-ket-qua-thi-nghiem-thong-so-lo-hoi-lon'
        ]);
    }

    /**
     * Get large boiler parameters detail
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function smallBoilerParametersDetail(Request $request)
    {
        $oldRequest = $request;
        $statisticsAttr = [
            'arrDesignValueExtendType' => [
                1 => 'zTest_RO',
                3 => 'zBoiler_Effic',
                4 => 'zGasTemp_LAirPreh',
                6 => 'zCoeff_Excess_AEG',
                7 => 'zAir_Heater_Leak',
                8 => 'zCoal_Fine_MA',
                9 => 'zCoal_Fine_MB',
            ],
            'arrDVExtendType' => [
                5 => 'zCoefExcess_ALF',
                10 => 'zFNo1_BPipe',
                11 => 'zFNo2_BPipe',
                12 => 'zFNo3_BPipe',
                13 => 'zFNo4_BPipe',
                14 => 'zFNo5_BPipe',
                15 => 'zFNo6_BPipe',
                16 => 'zFNo7_BPipe',
                17 => 'zFNo8_BPipe',
                18 => 'zFNo1_BPipe2',
                19 => 'zFNo2_BPipe2',
                20 => 'zFNo3_BPipe2',
                21 => 'zFNo4_BPipe2',
                22 => 'zFNo1_BPipe3',
                23 => 'zFNo2_BPipe3',
                24 => 'zFNo3_BPipe3',
                25 => 'zFNo4_BPipe3',
                26 => 'zFAFan_InletP',
                27 => 'zFBFan_InletP',
            ],
            'arrValueExtendType' => [
                2 => 'zCapacity_Steam'
            ]
        ];
        $chartDataArr = [
            'arrDesignValueExtendType' => [
                1 => 'zTest_RO',
                2 => 'zBoiler_Effic',
                4 => 'zGasTemp_LAirPreh',
                5 => 'zAir_Heater_Leak'
            ],
            'arrDVExtendType' => [
                3 => 'zCoefExcess_ALF'
            ],
        ];
        $statistics = formatInputDataEnergyReport($statisticsAttr);
        $chartData = formatInputDataEnergyReport($chartDataArr);
        $request = [
            'ids' => $request->ids,
            'report_type' => 'zCNNL19',
            'statistics' => $statistics,
            'other_info' => 'zCI_Device.name',
            'chart_data' => $chartData,
            'excel_template' => 'bao_cao_so_sanh_ket_qua_thi_nghiem_thong_so_lo_hoi_nho',
            'start_row' => 4,
            'second_start_row' => 19,
            'excel_item_info_height' => 13,
            'excel_item_info_title' => 'KẾT QUẢ CHÍNH THÍ NGHIỆM HIỆU CHỈNH LÒ HƠI',
            'excel_item_other_info_height' => 3,
            'excel_item_statistics_height' => 27,
            'statistics_first_insertion_row' => 5,
            'number_chart_data' => 4,
            'chart_first_insertion_row' => 8,
            'chart_last_insertion_row' => 25,
            'chart_data_invalid_input_msg_index' => ['B5', 'B31', 'B57', 'B83']
        ];
        $excel = exportEnergyReport($request, $oldRequest);
        return view('enery::detail', [
            'ids' => $request['ids'],
            'excel' => $excel,
            'title' => 'Báo cáo thống kê kết quả thí nghiệm thông số lò hơi công suất nhỏ',
            'path' => 'bao-cao-so-sanh-ket-qua-thi-nghiem-thong-so-lo-hoi-nho'
        ]);
    }

    /**
     * get list dvql
     * @param $request
     * @return mixed
     */
    private function getDVQL()
    {
        $dvqls = Cache::remember('unitList', 7200, function () {
            return getDataFromService('doSelect', 'zDVQL', ['id', 'zsym'], 'zsym IS NOT NULL AND active_flag = 1', 200);
        });
        sortData($dvqls);
        return $dvqls;
    }

    /**
     * get list td by $request
     * @param $request
     * @return mixed
     */
    private function getTD($request)
    {
        if( !empty($request['dvql_id']) ){
            $tds = getAllDataFromService('zTD', ['id', 'zsym'], " zref_dvql = ".$request['dvql_id']);
            usort($tds, function($a, $b){
                return strcmp(strtolower($a['zsym']), strtolower($b['zsym']));
            });
            return $tds;
        }
        return [];
    }

    /**
     * get list nl by $request
     * @param $request
     * @return mixed
     */
    private function getNL($request)
    {
        if( !empty($request['td_id']) ){
            $nls = getAllDataFromService('zNL', ['id', 'zsym'], " zref_td = ".$request['td_id']);
            usort($nls, function($a, $b){
                return strcmp(strtolower($a['zsym']), strtolower($b['zsym']));
            });
            return $nls;
        }
        return [];
    }

    /**
     * get data obj zTD, zNL
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDataObject(Request $request)
    {
        try {
            $data = [];
            if( $request->obj ){
                switch ($request->obj) {
                    case 'zTD':
                        $whereClause = $request->id ? " zref_dvql = $request->id " : '';
                        break;
                    default:
                        $whereClause = $request->id ? " zref_td = $request->id " : '';
                        break;
                }
                $data = getAllDataFromService($request->obj, ['id', 'zsym'], $whereClause);
                usort($data, function($a, $b){
                    return strcmp(strtolower($a['zsym']), strtolower($b['zsym']));
                });
            }
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('[Enery] Ajax get data by object: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return response()->json([
                'error' => [ $e->getMessage() ]
            ]);
        }
    }
}
