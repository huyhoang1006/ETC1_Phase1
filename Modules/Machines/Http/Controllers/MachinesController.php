<?php

namespace Modules\Machines\Http\Controllers;

use App\Services\CAWebServices;
use GreenCape\Xml\Converter;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Chart\Axis;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Layout;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Exception;
use Redirect;


class MachinesController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function CAService()
    {
        // Get User Login Session
        $user = session()->get(env('AUTH_SESSION_KEY'));

        // Get Url Web Service
        $service = new CAWebServices(env('CA_WSDL_URL'));

        // Query Data Web Service
        $payload = [
            'sid' => $user['ssid'],
            'objectType' => 'nr',
            'whereClause' => "class.type = '10. BBTN Cao Áp - Máy Cắt'",
            'maxRows' => 150,
            'attributes' => [
                'id',
                'name',
                'zReport_Date',
                'zExperimenter.first_name',
                'zExperimenter.middle_name',
                'zExperimenter.last_name',
                'zCustomer',
                'zArea.zsym',
                'zCI_Device.name',
                'zCI_Device.zCI_Device_Type'
            ],
        ];

        // Return Data Web Service
        $resp = $service->callFunction('doSelect', $payload);

        // Convert Data To Object
        $data = new Converter($resp->doSelectReturn);

        $items = [];

        // Writing Data Into Array
        foreach ($data->data['UDSObjectList'] as $obj) {
            $item =[];
            foreach ($obj['UDSObject'][1]['Attributes'] as $attr) {
                if ($attr['Attribute'][1]['AttrValue']) {
                    $item[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                }
            }
            array_push($items, $item);
        }
        return $items;
    }

    // Index Function
    public function index()
    {
        $items = $this->CAService();
        return view('machines::index',compact('items'));
    }

    // Export Excel
    public function export(Request $request)
    {
        $ids = $request->get('ids');
        $excel = $this->generateExcel($ids);
        return redirect($excel);
    }

    // Generate Excel Function
    public function generateExcel(string $handleId)
    {
        $id = explode(',', $handleId);

        // Get User Login Session
        $user = session()->get(env('AUTH_SESSION_KEY'));
        // Get Url Web Service
        $service = new CAWebServices(env('CA_WSDL_URL'));
        // Query Data Service 'nr' Object

        $payload = [
            'sid' => $user['ssid'],
            'objectHandle' => 'nr:' . $id[0],
            'attributes' => [
                'name',
                'zReport_Date',
            ]
        ];
        // Return Data Web Service
        $resp = $service->callFunction('getObjectValues', $payload);
        // Convert Data To Object
        $parser = new Converter($resp->getObjectValuesReturn);
        // Writing Data Into Array
        foreach ($parser->data['UDSObject'][1]['Attributes'] as $attr) {
            $nrObj[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
        }
        // Query Object Data Object 1
        $resp = $service->callFunction('getObjectValues', [
            'sid' => $user['ssid'],
            'objectHandle' => 'zCA10:' . $id[0],
            'attributes' => [
            ]
        ]);
        // Convert Object New
        $parser = new Converter($resp->getObjectValuesReturn);
        // Writing Data Into Array
        foreach ($parser->data['UDSObject'][1]['Attributes'] as $attr) {
            if (!$attr['Attribute'][1]['AttrValue']) {
                continue;
            }
            $zCA10Obj[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
        }

        // Data_array_1
        $ngay_lam_thi_nghiem = @$nrObj['zReport_Date'] ?  date('Y-m-d', $nrObj['zReport_Date']) : '';
        $thao_tac_dong_ngat_co_khi = @$zCA10Obj['zGI_CloOp5_CloseOpen5_P'] == 1 ? 'Pass' : 'Fail';
        $be_mat_xu_mc = @$zCA10Obj['zGI_CloOp5_CheckOfPorcenlain_P'] == 1 ? 'Pass' : 'Fail';
        $cac_day_tin_hieu = @$zCA10Obj['zGI_CloOp5_CCC_P'] == 1 ? 'Pass' : 'Fail';
        $cong_venh = @$zCA10Obj['zGI_CloOp5_CheckMechanical_P'] == 1 ? 'Pass' : 'Fail';

        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        // Read Template Excel
        $template = storage_path('templates_excel/bao_cao_cao_ap/may_cat/bao-cao-kiem-tra-ben-ngoai-may-cat.xlsx');
        $spreadsheet = $reader->load($template);
        // Read data on the table
        //$cellValue = $spreadsheet->getActiveSheet()->getCell('B12')->getValue();
        // Create New Writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        // Writer Value On The Table BB1
        $spreadsheet->getActiveSheet()->getCell('B9')->setValue($ngay_lam_thi_nghiem);
        $spreadsheet->getActiveSheet()->getCell('D9')->setValue($thao_tac_dong_ngat_co_khi);
        $spreadsheet->getActiveSheet()->getCell('D10')->setValue($be_mat_xu_mc);
        $spreadsheet->getActiveSheet()->getCell('D11')->setValue($cac_day_tin_hieu);
        $spreadsheet->getActiveSheet()->getCell('D12')->setValue($cong_venh);

        // Output Permission
        $outputDir = public_path('export');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        // Output path
        $output = $outputDir . '/' . 'bao-cao-kiem-tra-ben-ngoai-ket-qua'.'-'.time() .'-'.$user['ssid']. '.xlsx';
        if (is_file($output)) {
            unlink($output);
        }
        // Writer File Into Path
        $writer->save('export/bao-cao-kiem-tra-ben-ngoai-ket-qua'.'-'.time() .'-'.$user['ssid']. '.xlsx');

        // Return Link Download
        return str_replace(public_path(''), getenv('APP_URL'), $output);

    }

    // Dien tro cach dien

    public function export_dien_tro(Request $request)
    {
        $ids = $request->get('ids');
        $excel = $this->generateExcel_dien_tro($ids);
        return redirect($excel);
    }


    public function generateExcel_dien_tro(string $handleId) {
        $ids = explode(',', $handleId);
        $id_arr_1 = $ids[0];
        $id_arr_2 = $ids[1];

        // Get User Login Session
        $user = session()->get(env('AUTH_SESSION_KEY'));
        // Get Url Web Service
        $service = new CAWebServices(env('CA_WSDL_URL'));
        // Query Data Service 'nr' Object
        $payload_id_1 = [
            'sid' => $user['ssid'],
            'objectHandle' => 'nr:' . $id_arr_1,
            'attributes' => [
                'name',
                'zReport_Date',
            ]
        ];
        // Return Data Web Service
        $resp = $service->callFunction('getObjectValues', $payload_id_1);
        // Convert Data To Object
        $parser = new Converter($resp->getObjectValuesReturn);
        // Writing Data Into Array
        foreach ($parser->data['UDSObject'][1]['Attributes'] as $attr) {
            $nrObj[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
        }
        // Query Object Data Object 1
        $resp = $service->callFunction('getObjectValues', [
            'sid' => $user['ssid'],
            'objectHandle' => 'zCA10:' . $id_arr_1,
            'attributes' => [
            ]
        ]);
        // Convert Object New
        $parser = new Converter($resp->getObjectValuesReturn);
        // Writing Data Into Array
        foreach ($parser->data['UDSObject'][1]['Attributes'] as $attr) {
            if (!$attr['Attribute'][1]['AttrValue']) {
                continue;
            }
            $zCA10Obj[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
        }

        // Query Data Service 'nr' Object
        $payload_id_2 = [
            'sid' => $user['ssid'],
            'objectHandle' => 'nr:' . $id_arr_2,
            'attributes' => [
                'name',
                'zReport_Date',
            ]
        ];
        // Return Data Web Service
        $resps = $service->callFunction('getObjectValues', $payload_id_2);
        // Convert Data To Object
        $parsers = new Converter($resps->getObjectValuesReturn);
        // Writing Data Into Array
        foreach ($parsers->data['UDSObject'][1]['Attributes'] as $attr) {
            $nrObjs[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
        }

        // Query Object Data Object 2
        $resps = $service->callFunction('getObjectValues', [
            'sid' => $user['ssid'],
            'objectHandle' => 'zCA10:' . $id_arr_2,
            'attributes' => [
            ]
        ]);
        // Convert Object New
        $parsers = new Converter($resps->getObjectValuesReturn);
        // Writing Data Into Array
        foreach ($parsers->data['UDSObject'][1]['Attributes'] as $attr) {
            if (!$attr['Attribute'][1]['AttrValue']) {
                continue;
            }
            $zCA10Objs[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
        }
        // Data object 1
        $ngay_lam_thi_nghiem = @$nrObj['zReport_Date'] ?  date('Y-m-d', $nrObj['zReport_Date']) : '';
        $truoc_khi_thu_pha_a= @$zCA10Obj['zIRM_BeforeHVA'];
        $truoc_khi_thu_pha_b = @$zCA10Obj['zIRM_BeforeHVB'];
        $truoc_khi_thu_pha_c = @$zCA10Obj['zIRM_BeforeHVC'];
        $sau_khi_thu_pha_a = @$zCA10Obj['zIRM_AffterHVA'];
        $sau_khi_thu_pha_b = @$zCA10Obj['zIRM_AffterHVB'];
        $sau_khi_thu_pha_c = @$zCA10Obj['zIRM_AffterHVC'];
        // Data object 2
        $ngay_lam_thi_nghiem_arr = @$nrObjs['zReport_Date'] ?  date('Y-m-d', $nrObjs['zReport_Date']) : '';
        $truoc_khi_thu_pha_a_arr= @$zCA10Objs['zIRM_BeforeHVA'];
        $truoc_khi_thu_pha_b_arr = @$zCA10Objs['zIRM_BeforeHVB'];
        $truoc_khi_thu_pha_c_arr = @$zCA10Objs['zIRM_BeforeHVC'];
        $sau_khi_thu_pha_a_arr = @$zCA10Objs['zIRM_AffterHVA'];
        $sau_khi_thu_pha_b_arr = @$zCA10Objs['zIRM_AffterHVB'];
        $sau_khi_thu_pha_c_arr = @$zCA10Objs['zIRM_AffterHVC'];

        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);
        // Read Template Excel
        $template = storage_path('templates_excel/bao_cao_cao_ap/may_cat/bao-cao-kiem-tra-dien-tro-cach-dien.xlsx');
        $spreadsheet = $reader->load($template);
        // Read data on the table
        //$cellValue = $spreadsheet->getActiveSheet()->getCell('B12')->getValue();
        // Create New Writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);
        // Writer Value On The Table - 3
        $spreadsheet->getActiveSheet()->getCell('B9')->setValue($ngay_lam_thi_nghiem);
        $spreadsheet->getActiveSheet()->getCell('E9')->setValue($truoc_khi_thu_pha_a);
        $spreadsheet->getActiveSheet()->getCell('F9')->setValue($truoc_khi_thu_pha_b);
        $spreadsheet->getActiveSheet()->getCell('G9')->setValue($truoc_khi_thu_pha_c);
        $spreadsheet->getActiveSheet()->getCell('E10')->setValue($sau_khi_thu_pha_a);
        $spreadsheet->getActiveSheet()->getCell('F10')->setValue($sau_khi_thu_pha_b);
        $spreadsheet->getActiveSheet()->getCell('G10')->setValue($sau_khi_thu_pha_c);

        $spreadsheet->getActiveSheet()->getCell('B11')->setValue($ngay_lam_thi_nghiem_arr);
        $spreadsheet->getActiveSheet()->getCell('E11')->setValue($truoc_khi_thu_pha_a_arr);
        $spreadsheet->getActiveSheet()->getCell('F11')->setValue($truoc_khi_thu_pha_b_arr);
        $spreadsheet->getActiveSheet()->getCell('G11')->setValue($truoc_khi_thu_pha_c_arr);
        $spreadsheet->getActiveSheet()->getCell('E12')->setValue($sau_khi_thu_pha_a_arr);
        $spreadsheet->getActiveSheet()->getCell('F12')->setValue($sau_khi_thu_pha_b_arr);
        $spreadsheet->getActiveSheet()->getCell('G12')->setValue($sau_khi_thu_pha_c_arr);

        // Writer Value On The Table - 3


        // Output Permission
        $outputDir = public_path('export');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        // Output path
        $output = $outputDir . '/' . 'bao-cao-kiem-tra-dien-tro-cach-dien' .'-'.time() .'-'.$user['ssid']. '.xlsx';
        if (is_file($output)) {
            unlink($output);
        }
        // Writer File Into Path
        $writer->save('export/bao-cao-kiem-tra-dien-tro-cach-dien'.'-'.time() .'-'.$user['ssid']. '.xlsx');

        // Return Link Download
        return str_replace(public_path(''), getenv('APP_URL'), $output);
    }

    public function export_tiep_xuc(Request $request)
    {
        $ids = $request->get('ids');
        $excel = $this->generateExcel_tiep_xuc($ids);
        return redirect($excel);
    }

    public function generateExcel_tiep_xuc(string $handleId)
    {
        $ids = explode(',', $handleId);

        // Get User Login Session
        $user = session()->get(env('AUTH_SESSION_KEY'));

        // Get Url Web Service
        $service = new CAWebServices(env('CA_WSDL_URL'));

        // Query Data Service 'nr' Object
        $payload_id_1 = [
            'sid' => $user['ssid'],
            'objectHandle' => 'nr:' . $ids[0],
            'attributes' => [
                'name',
                'zReport_Date',
            ]
        ];
        // Return Data Web Service
        $resp = $service->callFunction('getObjectValues', $payload_id_1);
        // Convert Data To Object
        $parser = new Converter($resp->getObjectValuesReturn);
        // Writing Data Into Array
        foreach ($parser->data['UDSObject'][1]['Attributes'] as $attr) {
            $nrObj[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
        }
        // Query Object Data Object 1
        $resp = $service->callFunction('getObjectValues', [
            'sid' => $user['ssid'],
            'objectHandle' => 'zCA10:' . $ids[0],
            'attributes' => [
            ]
        ]);
        // Convert Object New
        $parser = new Converter($resp->getObjectValuesReturn);
        // Writing Data Into Array
        foreach ($parser->data['UDSObject'][1]['Attributes'] as $attr) {
            if (!$attr['Attribute'][1]['AttrValue']) {
                continue;
            }
            $zCA10Obj[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
        }

        // Query Data Service 'nr' Object
        $payload_id_2 = [
            'sid' => $user['ssid'],
            'objectHandle' => 'nr:' . $ids[1],
            'attributes' => [
                'name',
                'zReport_Date',
            ]
        ];
        // Return Data Web Service
        $resps = $service->callFunction('getObjectValues', $payload_id_2);
        // Convert Data To Object
        $parsers = new Converter($resps->getObjectValuesReturn);
        // Writing Data Into Array
        foreach ($parsers->data['UDSObject'][1]['Attributes'] as $attr) {
            $nrObjs[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
        }

        // Query Object Data Object 2
        $resps = $service->callFunction('getObjectValues', [
            'sid' => $user['ssid'],
            'objectHandle' => 'zCA10:' . $ids[1],
            'attributes' => [
            ]
        ]);
        // Convert Object New
        $parsers = new Converter($resps->getObjectValuesReturn);
        // Writing Data Into Array
        foreach ($parsers->data['UDSObject'][1]['Attributes'] as $attr) {
            if (!$attr['Attribute'][1]['AttrValue']) {
                continue;
            }
            $zCA10Objs[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
        }

        // Query Data Service 'nr' Object
        $payload_id_3 = [
            'sid' => $user['ssid'],
            'objectHandle' => 'nr:' . $ids[2],
            'attributes' => [
                'name',
                'zReport_Date',
            ]
        ];
        // Return Data Web Service
        $respsp = $service->callFunction('getObjectValues', $payload_id_3);
        // Convert Data To Object
        $parsersp = new Converter($respsp->getObjectValuesReturn);
        // Writing Data Into Array
        foreach ($parsersp->data['UDSObject'][1]['Attributes'] as $attr) {
            $nrObjsp[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
        }

        // Query Object Data Object 2
        $respsp = $service->callFunction('getObjectValues', [
            'sid' => $user['ssid'],
            'objectHandle' => 'zCA10:' . $ids[2],
            'attributes' => [
            ]
        ]);
        // Convert Object New
        $parsersp = new Converter($respsp->getObjectValuesReturn);
        // Writing Data Into Array
        foreach ($parsersp->data['UDSObject'][1]['Attributes'] as $attr) {
            if (!$attr['Attribute'][1]['AttrValue']) {
                continue;
            }
            $zCA10Objsp[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
        }



        // Data_array_1
        $ngay_lam_thi_nghiem = @$nrObj['zReport_Date'] ?  date('Y-m-d', $nrObj['zReport_Date']) : '';
        $buong_cat_1_a= @$zCA10Obj['zIRM_BeforeHVA'];
        $buong_cat_1_b = @$zCA10Obj['zIRM_BeforeHVB'];
        $buong_cat_1_c = @$zCA10Obj['zIRM_BeforeHVC'];
        $buong_cat_2_a = @$zCA10Obj['zIRM_AffterHVA'];
        $buong_cat_2_b = @$zCA10Obj['zIRM_AffterHVB'];
        $buong_cat_2_c = @$zCA10Obj['zIRM_AffterHVC'];


        // Data_array_2
        $ngay_lam_thi_nghiems = @$nrObjs['zReport_Date'] ?  date('Y-m-d', $nrObj['zReport_Date']) : '';
        $buong_cat_1_as= @$zCA10Objs['zIRM_BeforeHVA'];
        $buong_cat_1_bs = @$zCA10Objs['zIRM_BeforeHVB'];
        $buong_cat_1_cs = @$zCA10Objs['zIRM_BeforeHVC'];
        $buong_cat_2_as = @$zCA10Objs['zIRM_AffterHVA'];
        $buong_cat_2_bs = @$zCA10Objs['zIRM_AffterHVB'];
        $buong_cat_2_cs = @$zCA10Objs['zIRM_AffterHVC'];


        // Data_array_3
        $ngay_lam_thi_nghiemsp = @$nrObjs['zReport_Date'] ?  date('Y-m-d', $nrObj['zReport_Date']) : '';
        $buong_cat_1_asp= @$zCA10Objsp['zIRM_BeforeHVA'];
        $buong_cat_1_bsp = @$zCA10Objsp['zIRM_BeforeHVB'];
        $buong_cat_1_csp = @$zCA10Objsp['zIRM_BeforeHVC'];
        $buong_cat_2_asp = @$zCA10Objsp['zIRM_AffterHVA'];
        $buong_cat_2_bsp = @$zCA10Objsp['zIRM_AffterHVB'];
        $buong_cat_2_csp = @$zCA10Objsp['zIRM_AffterHVC'];

        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);
        // Read Template Excel
        $template = storage_path('templates_excel/bao_cao_cao_ap/may_cat/bao-cao-dien-tro-tiep-xuc.xlsx');
        $spreadsheet = $reader->load($template);
        // Read data on the table
        //$cellValue = $spreadsheet->getActiveSheet()->getCell('B12')->getValue();
        // Create New Writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);

        // Writer Value On The Table BB
        $spreadsheet->getActiveSheet("Data")->getCell('B13')->setValue($ngay_lam_thi_nghiem);
        $spreadsheet->getActiveSheet("Data")->getCell('B15')->setValue($ngay_lam_thi_nghiems);
        $spreadsheet->getActiveSheet("Data")->getCell('B17')->setValue($ngay_lam_thi_nghiemsp);
        // Writer Value On The Table BB1
        $spreadsheet->getActiveSheet("Data")->getCell('E13')->setValue($buong_cat_1_a);
        $spreadsheet->getActiveSheet("Data")->getCell('E14')->setValue($buong_cat_2_a);
        $spreadsheet->getActiveSheet("Data")->getCell('E15')->setValue($buong_cat_1_as);
        $spreadsheet->getActiveSheet("Data")->getCell('E16')->setValue($buong_cat_2_as);
        $spreadsheet->getActiveSheet("Data")->getCell('E17')->setValue($buong_cat_1_asp);
        $spreadsheet->getActiveSheet("Data")->getCell('E18')->setValue($buong_cat_2_asp);
        // Writer Value On The Table BB2
        $spreadsheet->getActiveSheet("Data")->getCell('F13')->setValue($buong_cat_1_b);
        $spreadsheet->getActiveSheet("Data")->getCell('F14')->setValue($buong_cat_2_b);
        $spreadsheet->getActiveSheet("Data")->getCell('F15')->setValue($buong_cat_1_bs);
        $spreadsheet->getActiveSheet("Data")->getCell('F16')->setValue($buong_cat_2_bs);
        $spreadsheet->getActiveSheet("Data")->getCell('F17')->setValue($buong_cat_1_bsp);
        $spreadsheet->getActiveSheet("Data")->getCell('F18')->setValue($buong_cat_2_bsp);

        // Writer Value On The Table BB3
        $spreadsheet->getActiveSheet("Data")->getCell('G13')->setValue($buong_cat_1_c);
        $spreadsheet->getActiveSheet("Data")->getCell('G14')->setValue($buong_cat_2_c);
        $spreadsheet->getActiveSheet("Data")->getCell('G15')->setValue($buong_cat_1_cs);
        $spreadsheet->getActiveSheet("Data")->getCell('G16')->setValue($buong_cat_2_cs);
        $spreadsheet->getActiveSheet("Data")->getCell('G17')->setValue($buong_cat_1_csp);
        $spreadsheet->getActiveSheet("Data")->getCell('G18')->setValue($buong_cat_2_csp);

        // Output Permission
        $outputDir = public_path('export');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        // Output path
        $output = $outputDir . '/' . 'bao-cao-dien-tro-tiep-xuc'.'-'.time() .'-'.$user['ssid']. '.xlsx';
        if (is_file($output)) {
            unlink($output);
        }
        // Writer File Into Path
        $writer->save('export/bao-cao-dien-tro-tiep-xuc'.'-'.time() .'-'.$user['ssid']. '.xlsx');

        // Return Link Download
        return str_replace(public_path(''), getenv('APP_URL'), $output);

    }



    public function bao_cao_kiem_tra_ben_ngoai(Request $request) {
        $ids = $request->get('ids');
        $excel = $this->generateExcel($ids);
        return view('machines::pages.kiem-tra-ben-ngoai', compact('ids','excel'));
    }

    public function bao_cao_dien_tro_cach_dien(Request $request) {
        $ids = $request->get('ids');
        $excel = $this->generateExcel_dien_tro($ids);
        return view('machines::pages.dien_tro_cach_dien', compact('ids','excel'));
    }

    public function bao_cao_dien_tro_tiep_xuc(Request $request)
    {
        $ids = $request->get('ids');
        $excel = $this->generateExcel($ids);
        return view('machines::pages.dien_tro_tiep_xuc', compact('ids','excel'));
    }

    /** preview report 'Điện trở cách điên cuộn đóng/ cuộn cắt (MW)'
     * @param Request $request
     * @return Application|Factory|View|RedirectResponse
     */
    public function insulationIndex(Request $request){
        $ids = explode(',', $request->ids);
        try {
            $data = checkTypeMachines($request, 'Báo cáo cuộn đóng/ cuộn cắt 1/ cuộn cắt 2', config('attributes.insulationReport.function'));
            $title = $data['title_report']??'';
            $function = $data['title_function'];
            $excel = $this->$function($ids, $data['type'], $request);
            $link_preview = "https://view.officeapps.live.com/op/embed.aspx?src=".$excel;
            return view('machines::pages.preview-share', compact('ids','excel','title','link_preview'));
        } catch (\Exception $e) {
            Log::error('[Machines] insulation report: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return back()->withErrors([$e->getMessage()]);
        }
    }

    /** Export report 'Báo cáo điện trở cách điên cuộn đóng/ cuộn cắt (MW) - Máy cắt 3 pha 1 bộ truyền động'
     * @param $ids
     * @return string|string[]
     * @throws Exception
     */
    private function getDataThreePhaseCircuitBreaker($ids, $dataType, $request){
        $item = $date = [];
        $whereClause = 'zlaboratoryDate IS NOT NULL';
        foreach ($ids as $key => $value){
            $obj = 'nr:' . $value;
            $item[] = getDataFromService('getObjectValues', $obj, config('attributes.atrHighPressure'), $whereClause, 100);
        }
        // order by zlaboratoryDate
        foreach ($item as $key => $row)
        {
            $date[$key] = $row['zlaboratoryDate']??'';
        }
        array_multisort($date, SORT_ASC, $item);
        // get data assoc
        $number = 0;
        foreach ($item as $key => $value){
            $number++;
            $item[$key]['number'] = $number;
            $item[$key]['time'] = @$value['zlaboratoryDate'] ? (date('d/m/Y', $value['zlaboratoryDate'])) : '';
            try {
                $where = "id = U'" . $value['id'] . "'";
                $data_obj = getDataFromService('doSelect', 'zCA10_2', config('attributes.atrHighPressureObjTwo'), $where, 150);
            } catch (\Exception $ex) {
                continue;
            }
            foreach (config('attributes.atrHighPressureObjTwo') as $k => $val){
                $item[$key][$val] = $data_obj[0][$val]??'0';
            }
        }
        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);
        if(count($item) > 0){
            $check_template = count($item);
        }elseif(count($item) >= 20){
            $check_template = 20;
        }else{
            return '';
        }
        $template = storage_path("templates_excel/cao-ap/bao-cao-phan-tich/template-dien-tro-cach-dien-cuon-dong-cuon-cat/3-pha/template-".$check_template.'.xlsx');
        $spreadsheet = $reader->load($template);

        // get data THONG_KE_DU_LIEU
        $table = config('attributes.tableInlutionDataAll');
        $startIndexRow = $startIndexData = 5;
        $sheetStatistical = $spreadsheet->setActiveSheetIndexByName(config('attributes.nameSheetAll'));
        foreach ($item as $value){
            // get year and time
            foreach (config('attributes.columKeyInlutionDataAll') as $key => $val){
                // get time and number
                $sheetStatistical->mergeCells($table[$key].($startIndexRow).":".$table[$key].($startIndexRow + 5))
                    ->getCell($table[$key].$startIndexRow)->setValue($value[$val]??'');
            }
            // get title
            $sheetStatistical->mergeCells('C'.($startIndexRow).":".'C'.($startIndexRow + 2))
                ->getCell('C'.($startIndexRow))->setValue('Điện trở cách điện (MΩ) *(Insulation resistance)');
            $sheetStatistical->mergeCells('C'.($startIndexRow + 3).":".'C'.($startIndexRow + 5))
                ->getCell('C'.($startIndexRow + 3))->setValue('Điện trở một chiều (Ω) (DC resistance)');
            // get data
            foreach (config('attributes.rollCloseRollCutReport.atrValueAll') as $k => $attr){
                $sheetStatistical->getCell('D'.$startIndexData)->setValue(config('attributes.columTitleInlutionDataAll')[$k]??'0');
                $sheetStatistical->getCell('E'.$startIndexData)->setValue($value[$attr??'0']);
                $startIndexData++;
            }
            $startIndexRow += 6;
        }

        $this->getDataChartInsulation($item ,$spreadsheet, config('constant.name_sheet.sheetOne'), config('attributes.rollCloseRollCutReport.atrValueChartOne'));
        $this->getDataChartInsulation($item ,$spreadsheet, config('constant.name_sheet.sheetTwo'), config('attributes.rollCloseRollCutReport.atrValueChartTwo'));
        $this->getDataChartInsulation($item ,$spreadsheet, config('constant.name_sheet.sheetThree'), config('attributes.rollCloseRollCutReport.atrValueChartThree'));

        fillSearchDataToExcelCuttingMachines($sheetStatistical, $request);
        alignmentExcel($sheetStatistical, 'C8:D1000');
        $sheetStatistical = $spreadsheet->setActiveSheetIndexByName(config('constant.name_sheet.sheetChart'));

        $title = 'bao-cao-phan-tich-dien-tro-cuon-dong-cuon-cat-'.time().'.xlsx';
        return writeExcel($spreadsheet, $title);
    }

    /** Find DATA CHART
     * @param $data
     * @param $spreadsheet
     * @param $nameSheet
     * @param $tableValue
     * @return void
     */
    private function getDataChartInsulation($data ,$spreadsheet, $nameSheet, $tableValue)
    {
        $startIndexData = 5;
        $table = config('attributes.columNameInlutionDataAll');
        $sheetStatistical = $spreadsheet->setActiveSheetIndexByName($nameSheet);
        // get data to template
        foreach ($data as $value){
            foreach ($tableValue as $key => $val){
                $sheetStatistical->getCell($table[$key].($startIndexData))->setValue($value[$val]??'0');
            }
            $startIndexData++;
        }
    }

    /** Export report 'Báo cáo điện trở cách điên cuộn đóng/ cuộn cắt (MW) - Máy cắt 1 pha, 1 bộ truyền động, 1 buồng cắt - Máy cắt 1 pha 1 bộ truyền động, 2 buồng cắt'
     * @param $ids
     * @param $type
     * @return string|string[]
     * @throws Exception
     * attr_assoc => attributes CA Object
     * obj_assoc => Object
     * atrFormat => attributes format
     * atrValueAll => Value sheet statistical results
     * $atrValueChartOne, $atrValueChartTwo, $atrValueChartThree, $atrValueChartFor => Value Sheet Data 1-2-3-4
     * $type = 1 is the report 'Báo cáo điện trở cách điện cuộn đóng cuộn cắt. Máy cắt 1 pha, 1 bộ truyền động, 1 buồng cắt'
     * $type = 2 is the report 'Báo cáo điện trở cách điện cuộn đóng cuộn cắt. Máy cắt 1 pha 1 bộ truyền động, 2 buồng cắt'
     */
    private function writeDataMachinesTimeOneReportDotTwo($ids, $type, $request){
        // check report
        $obj_assoc = '';
        $attr_assoc = $atrFormat = $atrValueAll = $atrValueChartOne = $atrValueChartTwo = $atrValueChartThree = $atrValueChartFor = $titleReport = [];
        if($type == 1){
            $attr_assoc = config('attributes.atrDataMachinesOnePhase');
            $obj_assoc = 'zCA10_1';
            $atrFormat = config('attributes.dataValueOnePhase');
            $atrValueAll = config('attributes.atrMachinesOnePhaseChartOne');
            $atrValueChartOne = config('attributes.valueMachinesOnePhaseChartOne');
            $atrValueChartTwo = config('attributes.atrMachinesOnePhaseChartTwo');
            $atrValueChartThree = config('attributes.atrMachinesOnePhaseChartThree');
            $atrValueChartFor = config('attributes.atrMachinesOnePhaseChartFor');
            $titleReport = 'Báo cáo điện trở cách điện, điện trở 1 chiều cuộn đóng/ cuộn cắt 1/ cuộn cắt 2. Máy cắt 1 pha, 1 bộ truyền động, 1 buồng cắt';
        }elseif($type == 2){
            $attr_assoc = config('attributes.atrDataMachinesOnePhaseTwochambers');
            $obj_assoc = 'zCA10_3';
            $atrFormat = config('attributes.dataValueOnePhaseTwochambers');
            $atrValueAll = config('attributes.atrMachinesCuttingChambersChartOne');
            $atrValueChartOne = config('attributes.valueMachinesCuttingChambersChartOne');
            $atrValueChartTwo = config('attributes.valueMachinesCuttingChambersChartTwo');
            $atrValueChartThree = config('attributes.valueMachinesCuttingChambersChartThree');
            $atrValueChartFor = config('attributes.valueMachinesCuttingChambersChartFor');
            $titleReport = 'Báo cáo điện trở cách điện, điện trở 1 chiều cuộn đóng/ cuộn cắt 1/ cuộn cắt 2. Máy cắt 1 pha 1 bộ truyền động, 2 buồng cắt';
        }
        $reports = $this->queryDataMachines($ids, $obj_assoc, $attr_assoc);
        // format data chart
        $data = $arrPhase = [];
        foreach ($reports as $key => $value){
            foreach($atrFormat as $k => $val){
                foreach($val as $item){
                    $data[$value['date']][$value['zphase.zsym'].'_'.$item] = $value[$item]??'0';
                }
            }
            $arrPhase[$key] = $value['zphase.zsym'];
        }
        // order phase
        $arrPhase = array_unique($arrPhase);
        foreach ($arrPhase as $key => $row)
        {
            $phase[$key] = $row??'';
        }
        array_multisort($phase, SORT_ASC, $arrPhase);
        // order data
        foreach ($data as $key => $row)
        {
            $count[$key] = $key;
        }
        array_multisort($count, SORT_DESC, $data);

        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);
        // set template
        $countData = count($data) >= 10 ? 10 : count($data);
        $template = storage_path("templates_excel/cao-ap/bao-cao-phan-tich/template-dien-tro-cach-dien-cuon-dong-cuon-cat/1-pha/template-".$countData.".xlsx");
        $spreadsheet = $reader->load($template);

        // get data
        $startIndexRow = $startIndexData = $indexPhase = 5;
        // get data THONG_KE_DU_LIEU
        $sheetStatistical = $spreadsheet->setActiveSheetIndexByName(config('attributes.nameSheetAll'));
        $table = range("A", "B");
        foreach ($data as $key => $value){
            // get year and time
            $sheetStatistical->mergeCells('A'.($startIndexRow).":".'A'.($startIndexRow + 17))
                ->getCell('A'.$startIndexRow)->setValue($key??'');
            for ($i=0; $i <= 2; $i++) {
                // get phase
                $sheetStatistical->mergeCells('B'.($startIndexRow).":".'B'.($startIndexRow + 5))
                    ->getCell('B'.($startIndexRow))->setValue($arrPhase[$i]);
                // get title
                $sheetStatistical->mergeCells('C'.($startIndexRow).":".'C'.($startIndexRow + 2))
                    ->getCell('C'.($startIndexRow))->setValue('Điện trở cách điện (MΩ) *(Insulation resistance)');
                $sheetStatistical->mergeCells('C'.($startIndexRow + 3).":".'C'.($startIndexRow + 5))
                ->getCell('C'.($startIndexRow + 3))->setValue('Điện trở một chiều (Ω) (DC resistance)');
                $startIndexRow += 6;
                // find data
                foreach ($atrValueAll as $k => $a){
                    $sheetStatistical->getCell('D'.$startIndexData)->setValue(config('attributes.columTitleInlutionDataAll')[$k]??'0');
                    $sheetStatistical->getCell('E'.$startIndexData)->setValue($value[$arrPhase[$i].'_'.$a]??'');
                    $startIndexData++;
                }
            }
        }

        // format data orderBy Phase Alphabet
        foreach ($reports as $key => $row)
        {
            $phase[$key] = $row['zphase.zsym'];
        }
        array_multisort($phase, SORT_ASC, $reports);
        // get title
        $spreadsheet->setActiveSheetIndexByName(config('attributes.nameSheetAll'))->getCell('A1')->setValue($titleReport);
        $spreadsheet->setActiveSheetIndexByName('BIEU_DO')->getCell('B1')->setValue($titleReport);
        // If there is only 1 day of experiment, export the graph in 1 experiment
        if(count($data) == 1){
            fillDataChart($reports, $spreadsheet, config('constant.name_sheet.sheetOne'), $atrValueChartOne);
            $nameSheetTwo = config('constant.name_sheet.sheetTwo');
            $nameSheetThree = config('constant.name_sheet.sheetThree');
            $nameSheetFor = config('constant.name_sheet.sheetFor');
        }else{ //If there are more than one test day, skip the chart in one experiment
            $nameSheetTwo = config('constant.name_sheet.sheetOne');
            $nameSheetThree = config('constant.name_sheet.sheetTwo');
            $nameSheetFor = config('constant.name_sheet.sheetThree');
        }
        // get data chart 2
        fillDataChart($data, $spreadsheet, $nameSheetTwo, $atrValueChartTwo);
        // get data chart 3
        fillDataChart($data, $spreadsheet, $nameSheetThree, $atrValueChartThree);
        // get data chart 4
        fillDataChart($data, $spreadsheet, $nameSheetFor, $atrValueChartFor);

        fillSearchDataToExcelCuttingMachines($spreadsheet->setActiveSheetIndexByName(config('constant.name_sheet.sheetAll')), $request, 4, range('A', 'E'));
        alignmentExcel($spreadsheet->setActiveSheetIndexByName(config('constant.name_sheet.sheetAll')), 'C9:D1000');
        if( $countData == 1 ){
            for ($i = 106; $i >= 30; $i--) {
                $spreadsheet->setActiveSheetIndexByName('BIEU_DO')->getRowDimension($i)->setVisible(false);
            }
        }
        $spreadsheet->setActiveSheetIndexByName(config('attributes.nameSheetChart'));
        $title = 'bao-cao-phan-tich-dien-tro-cuon-dong-cuon-cat-dien-tro-cach-dien-cuon-dong-cuon-cat-1-pha'.time().'.xlsx';
        return writeExcel($spreadsheet, $title);
    }
    /** preview report 'Báo cáo động cơ tích năng'
     * @param Request $request
     * @return Application|Factory|View|RedirectResponse
     */
    public function accumulativeEngineReport(Request $request){
        $ids = explode(',', $request->ids);
        try {
            $data = checkTypeMachines($request, 'Báo cáo động cơ tích năng', config('attributes.accumulativeEngineReport.function'));
            $titleFunction = $data['title_function']??'';
            $title = $data['title_report']??'';
            $excel = $this->$titleFunction($ids, $data['type'], $request);
            $link_preview = "https://view.officeapps.live.com/op/embed.aspx?src=".$excel;
            return view('machines::pages.preview-share', compact('ids','excel','title','link_preview'));
        } catch (\Exception $e) {
            Log::error('[Machines] insulation report: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return back()->withErrors([$e->getMessage()]);
        }
    }

    /** Export report 'Báo cáo động cơ tích năng - máy cắt 3 pha 1 bộ truyền động'
     * @param $ids
     * @return void
     * @throws Exception
     */
    private function writeDataAccumulativeEngineDotThree($ids, $type, $request)
    {
        $object = 'zCA10_2';
        $atrObject = config('attributes.atrAccumulativeEngineDotThree');
        $reports = $this->queryDataMachines($ids, $object, $atrObject);
        // order data by zlaboratoryDate
        foreach ($reports as $key => $row)
        {
            $date[$key] = $row['zlaboratoryDate']??'';
        }
        array_multisort($date, SORT_ASC, $reports);
        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);
        $countData  = count($reports) >= 30 ? 30 : count($reports);
        $template = storage_path("templates_excel/cao-ap/bao-cao-thong-ke/template-bao-cao-dong-co-tich-nang/may-cat-3-pha-1-bo-truyen-dong/bao-cao-dong-co-tich-nang-may-cat-3-pha-1-bo-truyen-dong-".$countData.".xlsx");
        $spreadsheet = $reader->load($template);

        // get data THONG_KE_DU_LIEU
        $startIndexRow = $startIndexData = 5;
        $sheetStatistical = $spreadsheet->setActiveSheetIndexByName(config('attributes.nameSheetAll'));
        foreach ($reports as $value){
            // get time
            $sheetStatistical->mergeCells('A'.($startIndexRow).":".'A'.($startIndexRow + 1))
                ->getCell('A'.($startIndexRow))->setValue($value['date']??'');
            // get title
            $sheetStatistical->getCell('B'.($startIndexRow))->setValue('Điện trở cách điện động cơ tích năng (MΩ) (Insulation resistance of motor)');
            $sheetStatistical->getCell('B'.($startIndexRow + 1))->setValue('Điện trở 1 chiều động cơ tích năng (Ω) (DC resistance of motor)');
            // get value
            $sheetStatistical->getCell('C'.($startIndexRow))->setValue($value['z81irmstr']??'');
            $sheetStatistical->getCell('C'.($startIndexRow + 1))->setValue($value['z66dcrmnum']??'');
            $startIndexRow = $startIndexRow + 2;
        }
        // find data chart
        fillDataChart($reports ,$spreadsheet, config('constant.name_sheet.sheetOne'), config('attributes.atrValueAccumulativeEngineDotThreeChartOne'));

        fillSearchDataToExcelCuttingMachines($sheetStatistical, $request, 5, range('A', 'C'));
        alignmentExcel($sheetStatistical, 'B10:B1000');
        $spreadsheet->setActiveSheetIndexByName(config('attributes.nameSheetChart'));
        $title = 'bao-cao-phan-tich-dong-co-tich-nang-'.time().'.xlsx';
        return writeExcel($spreadsheet, $title);
    }

    /** Query & format data
     * @param $ids
     * @param $object
     * @param $atrObject
     * @return array
     */
    private function queryDataMachines($ids, $object, $atrObject)
    {
        // query data
        $whereClause = 'zlaboratoryDate IS NOT NULL';
        $reports = [];
        foreach ($ids as $key => $value){
            $obj = 'nr:' . $value;
            $reports[] = getDataFromService('getObjectValues', $obj, config('attributes.atrValueDataMachinesOnePhase'), $whereClause, 100);
        }
        // get data Object
        foreach ($reports as $key => $value){
            $where = "id = U'" . $value['id'] . "'";
            $reports[$key]['date'] = @$value['zlaboratoryDate'] ?  date('d/m/Y', $value['zlaboratoryDate']) : '';
            $data_obj = getDataFromService('doSelect', $object, $atrObject, $where, 150);
            foreach ($atrObject as $val){
                $reports[$key][$val] = $data_obj[0][$val]??'0';
            }
        }
        return $reports;
    }

    /** Export report 'Báo cáo động cơ tích năng - Máy cắt 1 pha, 1 bộ truyền động, 1 buồng cắt - Máy cắt 1 pha 1 bộ truyền động, 2 buồng cắt'
     * @param $ids
     * @param $type
     * @return void
     * @throws Exception
     */
    private function writeDataAccumulativeEngineDotOne($ids, $type, $request)
    {
        $obj_assoc = '';
        $attr_assoc = $atrFormat = $atrValueChartOne = $atrValueChartTwo = $titleReport = $atrValueValueAll = [];
        if($type == 1){
            $attr_assoc = config('attributes.atrAccumulativeEngineDotOne');
            $obj_assoc = 'zCA10_1';
            $atrFormat = config('attributes.dataValueAccumulativeEngineDotOne');
            $atrValueValueAll = config('attributes.valueAllAccumulativeEngineDotOne');
            $atrValueChartOne = config('attributes.atrValueAccumulativeEngineDotOneChartOne');
            $atrValueChartTwo = config('attributes.atrValueAccumulativeEngineDotOneChartTwo');
            $titleReport = 'Báo cáo động cơ tích năng - máy cắt 1 pha, 1 bộ truyền động, 1 buồng cắt';
        }elseif($type == 2){
            $attr_assoc = config('attributes.atrAccumulativeEngineDotTwo');
            $obj_assoc = 'zCA10_3';
            $atrFormat = config('attributes.dataValueAccumulativeEngineDotTwo');
            $atrValueValueAll = config('attributes.valueAllAccumulativeEngineDotTwo');
            $atrValueChartOne = config('attributes.atrValueAccumulativeEngineDotTwoChartOne');
            $atrValueChartTwo = config('attributes.atrValueAccumulativeEngineDotTwoChartTwo');
            $titleReport = 'Báo cáo động cơ tích năng - máy cắt 1 pha 1 bộ truyền động, 2 buồng cắt';
        }
        $reports = $this->queryDataMachines($ids, $obj_assoc, $attr_assoc);
        // order data by zlaboratoryDate
        foreach ($reports as $key => $row)
        {
            $date[$key] = $row['zlaboratoryDate']??'';
        }
        array_multisort($date, SORT_ASC, $reports);
        // format data chart
        $data = [];
        foreach ($reports as $key => $value){
            foreach($atrFormat as $k => $val){
                foreach($val as $item){
                    $data[$value['date']][$value['zphase.zsym'].'_'.$item] = $value[$item]??'0';
                }
            }
        }
        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);
        $countData  = count($data) >= 10 ? 10 : count($data);
        $template = storage_path("templates_excel/cao-ap/bao-cao-thong-ke/template-bao-cao-dong-co-tich-nang/may-cat-1-pha-1-bo-truyen-dong-1-buong-cat/bao-cao-dong-co-tich-nang-may-cat-1-pha-1-bo-truyen-dong-1-buong-cat-".$countData.".xlsx");
        $spreadsheet = $reader->load($template);

        // find data sheet THONG_KE_DU_LIEU
        $startIndexRow = $startIndexColumCuttingChamber = $startIndexColum = 5;
        $sheetStatistical = $spreadsheet->setActiveSheetIndexByName(config('attributes.nameSheetAll'));
        foreach ($data as $key => $value){
            $sheetStatistical->mergeCells('A'.($startIndexRow).":".'A'.($startIndexRow + 5))
                ->getCell('A'.($startIndexRow))->setValue($key??'');
            for ($i = 0; $i <= 2; $i++){
                // get title Phase
                $sheetStatistical->mergeCells('B'.($startIndexColum).":".'B'.($startIndexColum + 1))
                    ->getCell('B'.($startIndexColum))->setValue('Phase '.range('A','C')[$i]);
                $startIndexColum += 2;
                // get type
                for ($j = 0; $j <= 1; $j++){
                    $sheetStatistical->getCell('C'.($startIndexColumCuttingChamber))->setValue(config('attributes.nameCategoryAccumulativeEngineDotTwo')[$j]);
                    $startIndexColumCuttingChamber += 1;
                }
                // get value
                $keyValue = 0;
                for ($l=0; $l < 6; $l++) {
                    $sheetStatistical->getCell('D'.($startIndexRow + $l))->setValue($value[$atrValueValueAll[$keyValue]]);
                    $keyValue++;
                }
            }
            $startIndexRow += 6;
        }
        // find chart 1
        if(count($reports) == 3){
            // format data orderBy Phase Alphabet
            foreach ($reports as $key => $row)
            {
                $phase[$key] = $row['zphase.zsym'];
            }
            array_multisort($phase, SORT_ASC, $reports);
            fillDataChart($reports ,$spreadsheet, config('constant.name_sheet.sheetOne'), $atrValueChartOne);
        }
        // find chart 2
        fillDataChart($data ,$spreadsheet, config('constant.name_sheet.sheetTwo'), $atrValueChartTwo);
        // get title
        $spreadsheet->setActiveSheetIndexByName(config('constant.name_sheet.sheetAll'))->getCell('A1')->setValue($titleReport);
        $spreadsheet->setActiveSheetIndexByName(config('constant.name_sheet.sheetChart'))->getCell('A1')->setValue($titleReport);

        fillSearchDataToExcelCuttingMachines($sheetStatistical, $request, 4, range('A', 'D'));
        alignmentExcel($sheetStatistical, 'C9:C1000');
        if($countData == 1){
            for ($i = 55; $i >= 30; $i--) {
                $spreadsheet->setActiveSheetIndexByName('BIEU_DO')->getRowDimension($i)->setVisible(false);
            }
        }
        $spreadsheet->setActiveSheetIndexByName(config('attributes.nameSheetChart'));
        $title = 'bao-cao-phan-tich-dong-co-tich-nang-'.time().'.xlsx';
        return writeExcel($spreadsheet, $title);
    }
    /** preview report 'Báo cáo áp lực khí nạp'
     * @param Request $request
     * @return Application|Factory|View|RedirectResponse
     */
    public function intakeAirPressureReport(Request $request){
        $ids = explode(',', $request->ids);
        try {
            $data = checkTypeMachines($request, 'Báo cáo áp lực khí nạp', config('attributes.intakeAirPressureReport.function'));
            $titleFunction = $data['title_function']??'';
            $title = $data['title_report']??'';
            $excel = $this->$titleFunction($ids, $data['type'], $request);
            $link_preview = "https://view.officeapps.live.com/op/embed.aspx?src=".$excel;
            return view('machines::pages.preview-share', compact('ids','excel','title','link_preview'));
        } catch (\Exception $e) {
            Log::error('[Machines] intake air pressure report: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return back()->withErrors([$e->getMessage()]);
        }
    }
    /** Export report 'Báo cáo áp lực khí nạp - máy cắt 3 pha 1 bộ truyền động'
     * @param $ids
     * @return void
     * @throws Exception
     */
    private function writeDataIntakeAirPressureDotThree($ids, $type, $request)
    {
        $object = 'zCA10_2';
        $atrObject = config('attributes.intakeAirPressureReport.atrDotOne');
        $reports = $this->queryDataMachines($ids, $object, $atrObject);
        // order data by zlaboratoryDate
        foreach ($reports as $key => $row)
        {
            $reports[$key]['title_type'] = 'Áp lực khí nạp ở t = 20°C (MPa) (Check gas pressure gauge)';
            $date[$key] = $row['zlaboratoryDate']??'';
        }
        array_multisort($date, SORT_ASC, $reports);
        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);
        $countData  = count($reports) >= 30 ? 30 : count($reports);
        $template = storage_path("templates_excel/cao-ap/bao-cao-thong-ke/template-bao-cao-ap-luc-khi-nap/may-cat-3-pha-1-bo-truyen-dong/template-may-cat-3-pha-1-bo-truyen-dong-".$countData.".xlsx");
        $spreadsheet = $reader->load($template);

        // get data THONG_KE_DU_LIEU
        fillDataChart($reports ,$spreadsheet, config('constant.name_sheet.sheetAll'), config('attributes.intakeAirPressureReport.atrValueDotOneAll'));
        // find data chart
        fillDataChart($reports ,$spreadsheet, config('constant.name_sheet.sheetOne'), config('attributes.intakeAirPressureReport.atrValueDotOneChartOne'));

        fillSearchDataToExcelCuttingMachines($spreadsheet->setActiveSheetIndexByName(config('constant.name_sheet.sheetAll')), $request, 6, range('A', 'C'));
        alignmentExcel($spreadsheet->setActiveSheetIndexByName(config('constant.name_sheet.sheetAll')), 'B10:B1000');
        $spreadsheet->setActiveSheetIndexByName(config('attributes.nameSheetChart'));
        $title = 'bao-cao-ap-luc-khi-nap-may-cat-3-pha-1-bo-truyen-dong'.time().'.xlsx';
        return writeExcel($spreadsheet, $title);
    }
    /** Export report 'Báo cáo áp lực khí nạp - Máy cắt 1 pha, 1 bộ truyền động, 1 buồng cắt - Máy cắt 1 pha 1 bộ truyền động, 2 buồng cắt'
     * @param $ids
     * @param $type
     * @return void
     * @throws Exception
     */
    private function writeDataIntakeAirPressureDotOne($ids, $type, $request)
    {
        $obj_assoc = '';
        $attr_assoc = $atrFormat = $atrValueChartOne = $titleReport = $attrValueAll = [];
        if($type == 1){
            $attr_assoc = config('attributes.intakeAirPressureReport.atrDotTwo');
            $obj_assoc = 'zCA10_1';
            $atrFormat = config('attributes.intakeAirPressureReport.atrFormatDotTwo');
            $attrValueAll = config('attributes.intakeAirPressureReport.atrValueDotTwoAll');
            $atrValueChartOne = config('attributes.intakeAirPressureReport.atrValueDotTwoChartOne');
            $titleReport = 'Báo cáo áp lực khí nạp. Máy cắt 1 pha, 1 bộ truyền động, 1 buồng cắt';
        }elseif($type == 2){
            $attr_assoc = config('attributes.intakeAirPressureReport.atrDotThree');
            $obj_assoc = 'zCA10_3';
            $atrFormat = config('attributes.intakeAirPressureReport.atrFormatDotThree');
            $attrValueAll = config('attributes.intakeAirPressureReport.atrValueDotThreeAll');
            $atrValueChartOne = config('attributes.intakeAirPressureReport.atrValueDotThreeChartOne');
            $titleReport = 'Báo cáo áp lực khí nạp. Máy cắt 1 pha 1 bộ truyền động, 2 buồng cắt';
        }
        $reports = $this->queryDataMachines($ids, $obj_assoc, $attr_assoc);
        // order data by zlaboratoryDate
        foreach ($reports as $key => $row)
        {
            $date[$key] = $row['zlaboratoryDate']??'';
        }
        array_multisort($date, SORT_ASC, $reports);
        // format data chart
        $data = [];
        foreach ($reports as $key => $value){
            foreach($atrFormat as $k => $val){
                foreach($val as $item){
                    $data[$value['date']][$value['zphase.zsym'].'_'.$item] = $value[$item]??'0';
                }
            }
        }
        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);
        $countData  = count($data) >= 10 ? 10 : count($data);
        $template = storage_path("templates_excel/cao-ap/bao-cao-thong-ke/template-bao-cao-ap-luc-khi-nap/may-cat-1-pha-1-bo-truyen-dong/template-may-cat-1-pha-1-bo-truyen-dong-".$countData.".xlsx");
        $spreadsheet = $reader->load($template);

        // find data sheet THONG_KE_DU_LIEU
        $startIndexRow = $startIndexColumn = 4;
        $sheetStatistical = $spreadsheet->setActiveSheetIndexByName(config('attributes.nameSheetAll'));
        $phase = range('A','C');
        foreach ($data as $key => $value){
            $sheetStatistical->mergeCells('A'.($startIndexRow).":".'A'.($startIndexRow + 2))
                ->getCell('A'.($startIndexRow))->setValue($key??'');
            $sheetStatistical->mergeCells('C'.($startIndexRow).":".'C'.($startIndexRow + 2))
                ->getCell('C'.($startIndexRow))->setValue('Áp lực khí nạp ở t = 20°C (MPa) (Check gas pressure gauge)');
            for ($i = 0; $i <= 2; $i++){
                $sheetStatistical->getCell('B'.($startIndexColumn))->setValue('Phase '.range('A','C')[$i]);
                $sheetStatistical->getCell('D'.($startIndexColumn))->setValue($value[$attrValueAll[$i]]??'');
                $startIndexColumn += 1;
            }
            $startIndexRow += 3;
        }

        // find data sheet DATA 1
        fillDataChart($data ,$spreadsheet, config('constant.name_sheet.sheetOne'), $atrValueChartOne);
        // get title
        $spreadsheet->setActiveSheetIndexByName(config('attributes.nameSheetAll'))->getCell('A1')->setValue($titleReport);
        $spreadsheet->setActiveSheetIndexByName('BIEU_DO')->getCell('B1')->setValue($titleReport);

        fillSearchDataToExcelCuttingMachines($spreadsheet->setActiveSheetIndexByName(config('constant.name_sheet.sheetAll')), $request, 5, range('A', 'D'));
        alignmentExcel($spreadsheet->setActiveSheetIndexByName(config('constant.name_sheet.sheetAll')), 'C9:C1000');
        // hidden row wrap chart multi experimental
        if($countData == 1){
            for ($i = 51; $i >= 27; $i--) {
                $spreadsheet->setActiveSheetIndexByName('BIEU_DO')->getRowDimension($i)->setVisible(false);
            }
        }
        $spreadsheet->setActiveSheetIndexByName(config('attributes.nameSheetChart'));
        $title = 'bao-cao-ap-luc-khi-nap-'.time().'.xlsx';
        return writeExcel($spreadsheet, $title);
    }
    /** preview report 'Báo cáo thí nghiệm điện áp xoay chiều tăng cao'
     * @param Request $request
     * @return Application|Factory|View|RedirectResponse
     */
    public function voltageRisesHighReport(Request $request){
        $ids = explode(',', $request->ids);
        try {
            $data = checkTypeMachines($request, 'Báo cáo thí nghiệm điện áp xoay chiều tăng cao', config('attributes.voltageRisesHighReport.function'));
            $titleFunction = $data['title_function']??'';
            $title = $data['title_report']??'';
            $excel = $this->$titleFunction($ids, $data['type'], $request);
            $link_preview = "https://view.officeapps.live.com/op/embed.aspx?src=".$excel;
            return view('machines::pages.preview-share', compact('ids','excel','title','link_preview'));
        } catch (\Exception $e) {
            Log::error('[Machines] voltage rises high report: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return back()->withErrors([$e->getMessage()]);
        }
    }

    /** Export report 'Báo cáo thí nghiệm điện áp xoay chiều tăng cao - máy cắt 3 pha 1 bộ truyền động - Máy cắt 1 pha 1 bộ truyền động 1 buồng cắt'
     * @param $ids
     * @param $type
     * @return void
     * @throws Exception
     * $atrValueAllColumnCDotOne => value sheet THONG_KE_DU_LIEU colum C 'Giữa máy cắt ở trạng thái đóng với đất'
     * $atrValueAllColumnCDotOne => value sheet THONG_KE_DU_LIEU colum D 'Giữa các tiếp điểm máy cắt khi mở'
     * $valueChartOne => value chart 1
     * $valueChartOne => value chart 2
     */
    private function writeDataVoltageRisesHighDotOne($ids, $type, $request)
    {
        $data = $reports = $valueChartOne = $valueChartTwo = $atrValueAllColumnCDotOne = $atrValueAllColumnDDotOne = $valueChartOne = $valueChartTwo = [];
        if($type == 1){
            $object = 'zCA10_1';
            $atrObject = config('attributes.voltageRisesHighReport.atrDotTwo');
            $reports = $this->queryDataMachines($ids, $object, $atrObject);
            // format data
            foreach ($reports as $key => $value){
                foreach(config('attributes.voltageRisesHighReport.atrFormatDotTwo') as $k => $val){
                    foreach($val as $item){
                        $data[$value['date']][$value['zphase.zsym'].'_'.$item] = $value[$item]??'0';
                        $data[$value['date']]['date'] = $value['date']??'';
                        $data[$value['date']]['zlaboratoryDate'] = $value['zlaboratoryDate']??'';
                    }
                }
            }
            $reports = $data;
            $atrValueAllColumnCDotOne = config('attributes.voltageRisesHighReport.atrValueAllColumnCDotTwo');
            $atrValueAllColumnDDotOne = config('attributes.voltageRisesHighReport.atrValueAllColumnDDotTwo');
            $valueChartOne = config('attributes.voltageRisesHighReport.atrValueDotTwoChartOne');
            $valueChartTwo = config('attributes.voltageRisesHighReport.atrValueDotTwoChartTwo');
        }elseif($type == 3){
            $object = 'zCA10_2';
            $atrObject = config('attributes.voltageRisesHighReport.atrDotOne');
            $reports = $this->queryDataMachines($ids, $object, $atrObject);
            $atrValueAllColumnCDotOne = config('attributes.voltageRisesHighReport.atrValueAllColumnCDotOne');
            $atrValueAllColumnDDotOne = config('attributes.voltageRisesHighReport.atrValueAllColumnDDotOne');
            $valueChartOne = config('attributes.voltageRisesHighReport.atrValueDotOneChartOne');
            $valueChartTwo = config('attributes.voltageRisesHighReport.atrValueDotOneChartTwo');
        }
        // order data by zlaboratoryDate
        foreach ($reports as $key => $row)
        {
            $date[$key] = $row['zlaboratoryDate']??'';
        }
        array_multisort($date, SORT_ASC, $reports);

        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);
        $countData  = count($reports) >= 30 ? 30 : count($reports);
        $template = storage_path("templates_excel/cao-ap/bao-cao-thong-ke/template-bao-cao-thi-nghiem-dien-ap-xoay-chieu-tang-cao/may-cat-3-pha-1-bo-truyen-dong/bao-cao-thi-nghiem-dien-ap-xoay-chieu-tang-cao-".$countData.".xlsx");
        $spreadsheet = $reader->load($template);

        // find data THONG_KE_DU_LIEU
        $startIndexRow = 5;
        $sheetStatistical = $spreadsheet->setActiveSheetIndexByName(config('attributes.nameSheetAll'));
        foreach ($reports as $value){
            $sheetStatistical->mergeCells('A'.($startIndexRow).":".'A'.($startIndexRow + 2))
                ->getCell('A'.($startIndexRow))->setValue($value['date']??'');
            for ($i = 0; $i <= 2; $i++){
                $sheetStatistical->getCell('B'.($startIndexRow + $i))->setValue('Phase '.range('A','C')[$i]);
                $sheetStatistical->getCell('C'.($startIndexRow + $i))->setValue($value[$atrValueAllColumnCDotOne[$i]]??'');
                $sheetStatistical->getCell('D'.($startIndexRow + $i))->setValue($value[$atrValueAllColumnDDotOne[$i]]??'');
            }
            $startIndexRow += 3;
        }
        // find data chart 1
        fillDataChart($reports ,$spreadsheet, config('constant.name_sheet.sheetOne'), $valueChartOne);
        // find data chart 2
        fillDataChart($reports ,$spreadsheet, config('constant.name_sheet.sheetTwo'), $valueChartTwo);

        fillSearchDataToExcelCuttingMachines($sheetStatistical, $request, 4, range('A', 'D'));

        $spreadsheet->setActiveSheetIndexByName(config('attributes.nameSheetChart'));
        $title = 'bao-cao-thi-nghiem-dien-ap-xoay-chieu-tang-cao-'.time().'.xlsx';
        return writeExcel($spreadsheet, $title);
    }
    /** Export report 'Báo cáo thí nghiệm điện áp xoay chiều tăng cao - Máy cắt 1 pha 1 bộ truyền động 2 buồng cắt'
     * @param $ids
     * @param $type
     * @return void
     * @throws Exception
     */
    private function writeDataVoltageRisesHighDotThree($ids, $type, $request)
    {
        $data = $reports = $valueChartThree = [];
        $reports = $this->queryDataMachines($ids, 'zCA10_3', config('attributes.voltageRisesHighReport.atrValueDotThree'));
        // order data by zlaboratoryDate
        foreach ($reports as $key => $row)
        {
            $date[$key] = $row['zlaboratoryDate']??'';
        }
        array_multisort($date, SORT_ASC, $reports);
        // format data
        foreach ($reports as $key => $value){
            foreach(config('attributes.voltageRisesHighReport.atrFormatDotThree') as $k => $val){
                foreach($val as $item){
                    $data[$value['date']][$value['zphase.zsym'].'_'.$item] = $value[$item]??'0';
                    $data[$value['date']]['zlaboratoryDate'] = $value['zlaboratoryDate']??'';
                }
            }
        }

        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);
        $countData  = count($data) >= 10 ? 10 : count($data);
        $template = storage_path("templates_excel/cao-ap/bao-cao-thong-ke/template-bao-cao-thi-nghiem-dien-ap-xoay-chieu-tang-cao/may-cat-1-pha-1-bo-truyen-dong-2-buong-cat/template-".$countData.".xlsx");
        $spreadsheet = $reader->load($template);

        // find data THONG_KE_DU_LIEU
        $startIndexRow = $startIndexColumCuttingChamber = $startIndexColum = 5;
        $sheetStatistical = $spreadsheet->setActiveSheetIndexByName(config('attributes.nameSheetAll'));
        foreach ($data as $key => $value){
            $sheetStatistical->mergeCells('A'.($startIndexRow).":".'A'.($startIndexRow + 5))
                ->getCell('A'.($startIndexRow))->setValue($key??'');
            for ($i = 0; $i <= 2; $i++){
                // get title Phase
                $sheetStatistical->mergeCells('B'.($startIndexColum).":".'B'.($startIndexColum + 1))
                ->getCell('B'.($startIndexColum))->setValue('Phase '.range('A','C')[$i]);
                $startIndexColum += 2;
                // get cutting chamber
                for ($j = 1; $j <= 2; $j++){
                    $sheetStatistical->getCell('C'.($startIndexColumCuttingChamber))->setValue('Buồng cắt '.$j);
                    $startIndexColumCuttingChamber += 1;
                }
                // get value
                $startIndexValue = 0;
                for ($l=0; $l < 6; $l++) {
                    $keyValue = $startIndexValue + $l;
                    $sheetStatistical->getCell('D'.($startIndexRow + $l))->setValue($value[config('attributes.voltageRisesHighReport.atrValueAllColumnDDotThree')[$keyValue]]);
                    $sheetStatistical->getCell('E'.($startIndexRow + $l))->setValue($value[config('attributes.voltageRisesHighReport.atrValueAllColumnEDotThree')[$keyValue]]);
                }
            }
            $startIndexRow += 6;
        }
        // find data chart 1
        fillDataChart($data ,$spreadsheet, config('constant.name_sheet.sheetOne'), config('attributes.voltageRisesHighReport.atrValueDotThreeChartOne'));
        // find data chart 2
        fillDataChart($data ,$spreadsheet, config('constant.name_sheet.sheetTwo'), config('attributes.voltageRisesHighReport.atrValueDotThreeChartTwo'));
        // find data chart 3
        fillDataChart($data ,$spreadsheet, config('constant.name_sheet.sheetThree'), config('attributes.voltageRisesHighReport.atrValueDotThreeChartThree'));
        // find data chart 4
        fillDataChart($data ,$spreadsheet, config('constant.name_sheet.sheetFor'), config('attributes.voltageRisesHighReport.atrValueDotThreeChartfor'));

        fillSearchDataToExcelCuttingMachines($sheetStatistical, $request, 4, range('A', 'F'));

        $spreadsheet->setActiveSheetIndexByName(config('attributes.nameSheetChart'));
        $title = 'bao-cao-thi-nghiem-dien-ap-xoay-chieu-tang-cao-'.time().'.xlsx';
        return writeExcel($spreadsheet, $title);
    }
    /** preview report 'Báo cáo kiểm tra cơ cấu truyền động'
     * @param Request $request
     * @return Application|Factory|View|RedirectResponse
     */
    public function checkTransmissionMechanismReport(Request $request){
        $ids = explode(',', $request->ids);
        $obj = [];
        try {
            $excel = '';
            switch ($request->classType) {
                case '10.2. BBTN Máy cắt 1 buồng cắt 3 bộ truyền động':
                    $title = 'Báo cáo kiểm tra cơ cấu truyền động - máy cắt 3 pha 1 bộ truyền động';
                    $obj = 'zCA10_2';
                    $atrObject = config('attributes.checkTransmissionMechanismReport.atrDotOne');
                    $atrObjectValue = config('attributes.checkTransmissionMechanismReport.atrDotOneValue');
                    break;
                case '10.1 BBTN Máy cắt 1 buồng cắt 1 bộ truyền động':
                    $title = 'Báo cáo kiểm tra cơ cấu truyền động - Máy cắt 1 pha 1 bộ truyền động 1 buồng cắt';
                    $obj = 'zCA10_1';
                    $atrObject = config('attributes.checkTransmissionMechanismReport.atrDotTwo');
                    $atrObjectValue = config('attributes.checkTransmissionMechanismReport.atrDotTwoValue');
                    break;
                default:
                    $title = 'Báo cáo kiểm tra cơ cấu truyền động - Máy cắt 1 pha 1 bộ truyền động 2 buồng cắt';
                    $obj = 'zCA10_3';
                    $atrObject = config('attributes.checkTransmissionMechanismReport.atrDotThree');
                    $atrObjectValue = config('attributes.checkTransmissionMechanismReport.atrDotThreeValue');
                    break;
            }
            $reports = $this->queryDataMachines($ids, $obj, $atrObject);
            foreach ($reports as $key => $value){
                $reports[$key]['title'] = 'Kiểm tra cơ cấu truyền động';
                if($value[$atrObjectValue] == 1){
                    $reports[$key]['result'] = 'Đạt';
                }else{
                    $reports[$key]['result'] = 'Không đạt';
                }
            }
            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);
            $template = storage_path("templates_excel/cao-ap/bao-cao-thong-ke/template-bao-cao-kiem-tra-co-cau-truyen-dong/template.xlsx");
            $spreadsheet = $reader->load($template);
            // dd($reports);
            // find data
            fillDataChart($reports ,$spreadsheet, config('constant.name_sheet.sheetOne'), config('attributes.checkTransmissionMechanismReport.atrValue'));

            fillSearchDataToExcelCuttingMachines($spreadsheet->setActiveSheetIndexByName(config('constant.name_sheet.sheetOne')), $request, 5, range('A', 'C'));

            $title_template = 'bao-cao-kiem-tra-co-cau-truyen-dong-'.time().'.xlsx';
            $excel = writeExcel($spreadsheet, $title_template);
            $link_preview = "https://view.officeapps.live.com/op/embed.aspx?src=".$excel;
            return view('machines::pages.preview-share', compact('ids','excel','title','link_preview'));
        } catch (\Exception $e) {
            Log::error('[Machines] check transmission mechanism report : Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return back()->withErrors([$e->getMessage()]);
        }
    }

    /** preview report 'Báo cáo dòng điện và tổn hao không tải ở điện áp thấp'
     * @param $ids
     * @param Request $request
     * @return Application|Factory|View
     */
    public function currentAndNoLoadLossReport(Request $request){
        try {
            $title = 'Báo cáo dòng điện và tổn hao không tải ở điện áp thấp và điện trở cách điện';
            $ids = explode(',', $request->ids);
            $reports = $param = [];
            foreach ($ids as $key => $value){
                $obj = 'nr:' . $value;
                $reports[] = getDataFromService('getObjectValues', $obj, config('attributes.currentAndNoLoadLossReport.atr'), '', 100);
            }
            foreach ($reports as $key => $report) {
                switch ($report['class.type']) {
                    case '13.1. BBTN MBA 2 cuộn dây, 1 cuộn cân bằng':
                        $param = config('attributes.currentAndNoLoadLossReport.twoCoilsOneBalanceCoil');
                        break;
                    case '13.2. BBTN MBA 2 cuộn dây':
                        $param = config('attributes.currentAndNoLoadLossReport.rollTwo');
                        break;
                    case '13.6. BBTN MBA 4 cuộn dây':
                        $param = config('attributes.currentAndNoLoadLossReport.rollFor');
                        break;
                    case '13.4. BBTN MBA 3 cuộn dây, chuyên nấc trung áp':
                    case '13.3 BBTN MBA 3 cuộn dây, chuyên nấc hạ áp':
                        $param = config('attributes.currentAndNoLoadLossReport.rollThree');
                        break;
                    default:
                        $param = config('attributes.currentAndNoLoadLossReport.threeAutoCoils');
                        break;
                }
                $obj = 'zCA13_'.substr($report['class.type'],3,1);
                $where = "id = U'" . $report['id'] . "'";
                if(!empty($report['zlaboratoryDate'])){
                    $reports[$key]['date'] = $report['zlaboratoryDate'] ?  date('d/m/Y', $report['zlaboratoryDate']) : '';
                }
                $data_obj = getDataFromService('doSelect', $obj, $param['atrObject'], $where, 150);
                foreach ($param['atrObject'] as $index => $val){
                    // check if the value contains text, an error will be reported
                    if (
                        !empty($data_obj[0][$val])
                        && in_array($val, $param['atrCheckValue'])
                        && preg_match("/[a-z]/", $data_obj[0][$val])
                    ) {
                        return back()->withErrors(['Dữ liệu đầu vào không hợp lệ!']);
                    }
                    // If the value is greater than 1000 then take 1000
                    if(
                        !empty($data_obj[0][$val]) && $data_obj[0][$val] > 1000
                        && in_array($val, $param['atrCheckValue'])
                    ){
                        $reports[$key][$val] = '1000';
                    }else{
                        $reports[$key][$val] = $data_obj[0][$val]??'';
                    }
                }
            }
            // get title Sheet chart 1 & 2
            $listTitle = [];
            foreach ($reports as $key => $value){
                foreach ($param['atrNameObject'] as $val){
                    $listTitle[$key][] = $value[$val]??'';
                }
            }
            if(!empty($listTitle)){
                $valueTitle = $listTitle[0];
                foreach($listTitle as $value){
                    if($value != $valueTitle){
                        return back()->withErrors(['Các biên bản đã chọn đang có đối tượng khác nhau!']);
                    }
                }
            }else{
                $valueTitle = ['','',''];
            }

            $countData  = count($reports) >= 30 ? 30 : count($reports);
            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);
            $template = storage_path("templates_excel/cao-ap/bao-cao-phan-tich/template-bao-cao-kiem-tra-tong-quan/template-".$countData.'-date-'.$param['roll']."-coil.xlsx");
            $spreadsheet = $reader->load($template);
            // find data THONG_KE_DU_LIEU
            $this->setValueCurrentAndNoLoadLossReport($reports, $spreadsheet, config('attributes.nameSheetAll'), $param['atrSheetAllOne']);
            // find data DONG_DIEN_KHONG_TAI
            $this->setValueCurrentAndNoLoadLossReport($reports, $spreadsheet, 'DONG_DIEN_KHONG_TAI', $param['atrSheetAllTwo']);
            // find data TON_HAO_KHONG_TAI
            $this->setValueCurrentAndNoLoadLossReport($reports, $spreadsheet, 'TON_HAO_KHONG_TAI', $param['atrSheetAllThree']);
            // find data DIEN_TRO_CACH_DIEN_CUON_DAY
            $this->setValueCurrentAndNoLoadLossReport($reports, $spreadsheet, 'DIEN_TRO_CACH_DIEN_CUON_DAY', $param['atrValueDataAllFor'], $param['titleChartThree']);
            // find data CHART 1-2-3-4-5-6
            fillDataChart($reports ,$spreadsheet, config('constant.name_sheet.sheetOne'), $param['atrChartOne'], $valueTitle, '', false);
            fillDataChart($reports ,$spreadsheet, config('constant.name_sheet.sheetTwo'), $param['atrChartTwo'], $valueTitle, '', false);
            fillDataChart($reports ,$spreadsheet, config('constant.name_sheet.sheetThree'), $param['atrChartThree'], $param['titleChartThree'], '', false);
            fillDataChart($reports ,$spreadsheet, config('constant.name_sheet.sheetFor'), $param['atrChartFor'], $param['titleChartThree'], '', false);
            fillDataChart($reports ,$spreadsheet, config('constant.name_sheet.sheetFive'), $param['atrChartFive'], $param['titleChartThree'], '', false);
            fillDataChart($reports ,$spreadsheet, config('constant.name_sheet.sheetSix'), $param['atrChartSix'], $param['titleChartSix'], '', false);
            // remove title STT
            $sheetCharts = ['DATA_1', 'DATA_2', 'DATA_3', 'DATA_4', 'DATA_5', 'DATA_6'];
            foreach($sheetCharts as $sheet){
                $spreadsheet->setActiveSheetIndexByName($sheet)->getCell('A3')->setValue('');
            }
            $spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU')->getCell('A4')->setValue('');
            $spreadsheet->setActiveSheetIndexByName('DONG_DIEN_KHONG_TAI')->getCell('A4')->setValue('');
            $spreadsheet->setActiveSheetIndexByName('TON_HAO_KHONG_TAI')->getCell('A4')->setValue('');
            $spreadsheet->setActiveSheetIndexByName('DIEN_TRO_CACH_DIEN_CUON_DAY')->getCell('A3')->setValue('');
            fillSearchDataToExcelHighPressure($spreadsheet->setActiveSheetIndexByName(config('constant.name_sheet.sheetAll')), $request, 4);
            // fill title to excel
            $sheetBD = $spreadsheet->setActiveSheetIndexByName(config('constant.name_sheet.sheetChart'))->setSelectedCell('A1');
            $sheetBD->getCell('B3')->setValue(trim($sheetBD->getCell('B3')->getValue()) . ' (mA)');
            $sheetBD->getCell('B29')->setValue(trim($sheetBD->getCell('B29')->getValue()) . ' (W)');
            $rowMW = [55, 81, 107];
            foreach($rowMW as $row){
                $sheetBD->getCell('B'.$row)->setValue( trim($sheetBD->getCell('B'.$row)->getValue()) . ' (MΩ)' );
            }
            $sheetBD->getCell('B1')->setValue(trim($sheetBD->getCell('B1')->getValue()).' và điện trở cách điện');
            $title_template = 'bao-cao-kiem-tra-tong-quan-'.time().'.xlsx';
            $excel = writeExcel($spreadsheet, $title_template);
            $link_preview = "https://view.officeapps.live.com/op/embed.aspx?src=".$excel;
            $type_report = 'Máy biến áp';
            return view('machines::pages.preview-share', compact('ids','excel','title','link_preview','type_report'));
        } catch (\Exception $e) {
            Log::error('[Machines] current and no load loss report : Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return back()->withErrors([$e->getMessage()]);
        }
    }

    /** preview report 'Báo cáo phân tích kết quả thí nghiệm điện trở cách điện gông từ, mạch từ'
     * @param Request $request
     * @return Application|Factory|View
     */
    public function syllableWordCircuitReport(Request $request)
    {
        try {
            $title = 'Báo cáo phân tích kết quả thí nghiệm điện trở cách điện gông từ, mạch từ';
            $ids = explode(',', $request->ids);
            $reports = [];
            foreach ($ids as $key => $value) {
                $obj = 'nr:' . $value;
                $reports[] = getDataFromService('getObjectValues', $obj, config('attributes.syllableWordCircuitReport.atrValue'), '', 100);
            }
            // get data Object
            foreach ($reports as $key => $value) {
                $where = "id = U'" . $value['id'] . "'";
                $reports[$key]['date'] = @$value['zlaboratoryDate'] ? date('d/m/Y', $value['zlaboratoryDate']) : '';
                $object = 'zCA13_' . substr($value['class.type'], 3, 1);
                $data_obj = getDataFromService('doSelect', $object, config('attributes.syllableWordCircuitReport.atrObject'), $where, 150);
                foreach (config('attributes.syllableWordCircuitReport.atrObject') as $val) {
                    $reports[$key][$val] = $data_obj[0][$val] ?? '0';
                }
            }
            $countData = count($reports) >= 30 ? 30 : count($reports);
            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);
            $template = storage_path("templates_excel/cao-ap/bao-cao-thong-ke/template-bao-cao-phan-tich-ket-qua-thi-nghiem-dien-tro-cach-dien-gong-tu-mach-tu/template-" . $countData . ".xlsx");
            $spreadsheet = $reader->load($template);

            // find data THONG_KE_DU_LIEU
            $startIndexRow = 4;
            $sheetStatistical = $spreadsheet->setActiveSheetIndexByName(config('attributes.nameSheetAll'));
            $table = range('D', 'G');
            foreach ($reports as $key => $value) {
                $sheetStatistical->mergeCells('B' . ($startIndexRow) . ":" . 'B' . ($startIndexRow + 2))
                    ->getCell('B' . ($startIndexRow))->setValue($value['date'] ?? '');
                for ($i = 0; $i <= 2; $i++) {
                    $sheetStatistical->getCell('C' . ($startIndexRow))->setValue(config('attributes.syllableWordCircuitReport.titleSheetAll')[$i]);
                    if( checkValueIsString(@$value[config('attributes.syllableWordCircuitReport.atrObject')[$i]], 1000) ){
                        return back()->withErrors(['Dữ liệu đầu vào không hợp lệ!']);
                    }
                    $sheetStatistical->getCell('D' . ($startIndexRow))->setValue(convertValueDataFromCA($value[config('attributes.syllableWordCircuitReport.atrObject')[$i]] ?? '', 1000));
                    $startIndexRow++;
                }
            }
            // find data CHART
            fillDataChart($reports, $spreadsheet, config('constant.name_sheet.sheetOne'), config('attributes.syllableWordCircuitReport.atrValueChartOne'), '', '', false, 1000);
            fillSearchDataToExcelHighPressure($sheetStatistical, $request, 4);
            alignmentExcel($sheetStatistical, 'C8:C1000');
            $sheetBD = $spreadsheet->setActiveSheetIndexByName(config('constant.name_sheet.sheetChart'));
            $sheetBD->getCell('B1')->setValue(trim($sheetBD->getCell('B1')->getValue()) .' (MΩ)');

            $title_template = '/bao-cao-phan-tich-ket-qua-thi-nghiem-dien-tro-cach-dien-gong-tu-mach-tu-' . time() . '.xlsx';
            $excel = writeExcel($spreadsheet, $title_template);
            $link_preview = "https://view.officeapps.live.com/op/embed.aspx?src=" . $excel;
            $type_report = 'Máy biến áp';
            return view('machines::pages.preview-share', compact('ids', 'excel', 'title', 'link_preview', 'type_report'));
        } catch (\Exception $e) {
            Log::error('[Machines] current and no load loss report : Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return back()->withErrors([$e->getMessage()]);
        }
    }

    /** Find data sheet DONG_DIEN_KHONG_TAI - TON_HAO_KHONG_TAI - DIEN_TRO_CACH_DIEN_CUON_DAY - THONG_KE_DU_LIEU report 'Báo cáo dòng điện và tổn hao không tải'
     * @param $data
     * @param $spreadsheet
     * @param $nameSheet
     * @param $atrValue
     * @param string $title
     * @param int $startIndexRow
     * @return void
     */
    private function setValueCurrentAndNoLoadLossReport($data, $spreadsheet, $nameSheet, $atrValue, $title = '', $startIndexRow = 5)
    {
        $sheetStatistical = $spreadsheet->setActiveSheetIndexByName($nameSheet);
        $roll = count($atrValue);
        // If there is a title passed in, add the title
        if($title != ''){
            $table = range('D','k');
        }else{
            $table = range('C','k');
        }
        foreach ($data as $key => $value){
            $sheetStatistical->mergeCells('B'.($startIndexRow).":".'B'.($startIndexRow + $roll - 1))
                ->getCell('B'.($startIndexRow))->setValue($value['date']??'');
            for ($i = 0; $i <= $roll-1; $i++){
                if($title != ''){
                    $sheetStatistical->getCell('C'.($startIndexRow))->setValue($title[$i]??'');
                }
                foreach ($atrValue[$i] as $k => $item){
                    if($item == 'zMWDCR_TGH_N1RNumber'){
                        $sheetStatistical->mergeCells($table[$k].($startIndexRow).":".$table[$k + 2].($startIndexRow))->getCell($table[$k].($startIndexRow))->setValue($value['zMWDCR_TGH_N1RNumber']??'');
                        $sheetStatistical->mergeCells($table[$k + 3].($startIndexRow).":".$table[$k + 5].($startIndexRow))->getCell($table[$k + 3].($startIndexRow))->setValue($value['zMWDCR_TGH_N1R75Number']??'');
                    }else{
                        $sheetStatistical->getCell($table[$k].($startIndexRow))->setValue($value[$item]??'');
                    }
                }
                $startIndexRow++;
            }
        }
    }

    /** get Data report 'Báo cáo phân tích kết quả thí nghiệm điện trở một chiều của các cuộn dây'
     * @param Request $request
     * @return void
     * @throws Exception
     */
    public function oneWayResistorReport(Request $request)
    {
        try {
            $title = 'Báo cáo phân tích kết quả thí nghiệm điện trở một chiều của các cuộn dây';
            $ids = explode(',', $request->ids);
            $reports = $arrTitle = $reports_sync = $param = $arrTitleChart = [];
            foreach ($ids as $key => $value){
                $obj = 'nr:' . $value;
                $reports[] = getDataFromService('getObjectValues', $obj, config('attributes.currentAndNoLoadLossReport.atr'), '', 100);
            }
            // get title hiccup
            for($i = 1; $i <= 21; $i++){
                $arrTitleChart[0] = 'Cuộn cao áp';
                $arrTitleChart[] = 'Nấc '.$i;
            }
            foreach ($reports as $key => $report) {
                switch ($report['class.type']) {
                    case '13.6. BBTN MBA 4 cuộn dây':
                        $param = config('attributes.oneWayResistorReport.forCoils');
                        break;
                    case '13.4. BBTN MBA 3 cuộn dây, chuyên nấc trung áp':
                        $param = config('attributes.oneWayResistorReport.threeCoilsMediumVoltage');
                        break;
                    case '13.3 BBTN MBA 3 cuộn dây, chuyên nấc hạ áp':
                        $param = config('attributes.oneWayResistorReport.threeRollsLowPressureStepSwitch');
                        break;
                    case '13.2. BBTN MBA 2 cuộn dây':
                        $param = config('attributes.oneWayResistorReport.twoCoil');
                        break;
                    case '13.1. BBTN MBA 2 cuộn dây, 1 cuộn cân bằng':
                        $param = config('attributes.oneWayResistorReport.twoCoilOneBalanceRoll');
                        break;
                    default:
                        $param = config('attributes.oneWayResistorReport.threeCoilRandom');
                        break;
                }
                $param['atrTwoTimesExperiment'] = array_merge(config('attributes.oneWayResistorReport.deviationAnalysisReport'), $param['atrTwoTimesExperimentSync']);
                $param['atrObject'] = array_merge(config('attributes.oneWayResistorReport.atrObjectDefault') ,$param['atrObject']);
                $param['atrChartHighPressure'] = array_merge(config('attributes.oneWayResistorReport.attrValueChartHighPressureExperiments'), $param['attrValueExperiment']);
                $param['atrSheetAllOne'] = array_merge(config('attributes.oneWayResistorReport.atrSheetAllOneDefault') ,$param['atrSheetAllOne']);
                $param['title'] = array_merge($arrTitleChart ,$param['title']);
                $param['atrNameObject'] = array_merge(config('attributes.oneWayResistorReport.listTitleMeasured'), config('attributes.oneWayResistorReport.listTitleExchange') ,$param['atrNameObject']);
                $obj = 'zCA13_'.substr($report['class.type'],3,1);
                $where = "id = U'" . $report['id'] . "'";
                if(!empty($report['zlaboratoryDate'])){
                    $reports[$key]['date'] = $report['zlaboratoryDate'] ?  date('d/m/Y', $report['zlaboratoryDate']) : '';
                }else{
                    $reports[$key]['date'] = '';
                }
                $data_obj = getDataFromService('doSelect', $obj, $param['atrObject'], $where, 150);
                // add data to object $reports
                foreach ($param['atrObject'] as $index => $val){
                    $reports[$key][$val] = $data_obj[0][$val]??'';
                }
            }
            // check name scroll, If the reports do not have the same name, then an error is reported
            $listTitle = [];
            foreach ($reports as $key => $value){
                foreach ($param['atrNameObject'] as $val){
                    $listTitle[$key][] = $value[$val]??'';
                }
            }
            if(!empty($listTitle)){
                $valueTitle = $listTitle[0];
                foreach($listTitle as $value){
                    if($value != $valueTitle){
                        return back()->withErrors(['Dữ liệu đầu vào không hợp lệ!']);
                    }
                }
            }
            // order by zlaboratoryDate
            foreach ($reports as $key => $row)
            {
                $date[$key] = $row['zlaboratoryDate']??'';
            }
            array_multisort($date, SORT_ASC, $reports);

            // Count the number of minutes to get the corresponding template
            $countData  = count($reports) >= 30 ? 30 : count($reports);
            // Constructor Excel Reader
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);
            if($countData == 1){
                $template = storage_path("templates_excel/cao-ap/bao-cao-phan-tich/template-bao-cao-phan-tich-ket-qua-thi-nghiem-dien-tro-mot-chieu-cua-cac-cuon-day/trong-1-lan-thi-nghiem/".$param['titleTemplate'].".xlsx");
            }else{
                $template = storage_path("templates_excel/cao-ap/bao-cao-phan-tich/template-bao-cao-phan-tich-ket-qua-thi-nghiem-dien-tro-mot-chieu-cua-cac-cuon-day/qua-cac-lan-thi-nghiem/".$param['titleTemplate']."-".$countData.".xlsx");
            }
            $spreadsheet = $reader->load($template);

            // find data THONG_KE_DU_LIEU
            $sheetStatistical = $spreadsheet->setActiveSheetIndexByName(config('attributes.nameSheetAll'));
            $table = range('C','k');
            $startIndexRow = 5;

            foreach ($reports as $key => $value){
                $noneDuplicate = '';
                $sheetStatistical->mergeCells('A'.($startIndexRow).":".'A'.($startIndexRow + $param['roll']))
                    ->getCell('A'.($startIndexRow))->setValue($value['date']??'');
                for ($i = 0; $i <= count($param['atrSheetAllOne'])-1; $i++){
                    $sheetStatistical->getCell('B'.($startIndexRow))->setValue( $param['title'][$i]??'');
                    if(count($param['atrSheetAllOne'][$i]) == 6){
                        $sheetStatistical->duplicateStyle($spreadsheet->getActiveSheet()->getStyle('B' . ('5') . ':H' . ('5')),'B' . ($startIndexRow) . ':H' .($startIndexRow));
                    }
                    if(count($param['atrSheetAllOne'][$i]) == 2 && empty($noneDuplicate)){
                        $sheetStatistical->duplicateStyle($spreadsheet->getActiveSheet()->getStyle('B' . ('29') . ':F' . ('29')),'B' . ($startIndexRow) . ':F' .($startIndexRow));
                        $noneDuplicate = 1;
                    }
                    foreach ($param['atrSheetAllOne'][$i] as $k => $item){
                        if($item == 'zMWDCR_TGH_N1RNumber' || $item == 'zMWDCR_TGH_RString' && $k == 0){
                            $sheetStatistical->mergeCells($table[$k].($startIndexRow).":".$table[$k + 2].($startIndexRow))->getCell($table[$k].($startIndexRow))->setValue($value[$param['atrSheetAllOne'][$i][0]]??'');
                            $sheetStatistical->mergeCells($table[$k + 3].($startIndexRow).":".$table[$k + 5].($startIndexRow))->getCell($table[$k + 3].($startIndexRow))->setValue($value[$param['atrSheetAllOne'][$i][1]]??'');
                        }else{
                            $sheetStatistical->getCell($table[$k].($startIndexRow))->setValue($value[$item]??'');
                        }
                    }
                    $startIndexRow++;
                }
            }
            // find data in 1 experiment
            if($countData == 1){
                // find data chart one
                $this->findDataChart($reports, $spreadsheet, config('constant.name_sheet.sheetOne'), config('attributes.oneWayResistorReport.attrSheetOne'), config('attributes.oneWayResistorReport.listTitleExchange'));
                $this->findDataChart($reports, $spreadsheet, config('constant.name_sheet.sheetTwo'), $param['attrSheetTwo'], $param['atrTitleScrollTwo']);
                // check if the minutes have 3 charts, then fill data chart 3
                if(!empty($param['attrSheetThree'])){
                    $this->findDataChart($reports, $spreadsheet, config('constant.name_sheet.sheetThree'), $param['attrSheetThree'], $param['atrTitleScrollThree']);
                }
                // check if the minutes have 4 charts, then fill data chart 4
                if(!empty($param['attrSheetFor'])){
                    $this->findDataChart($reports, $spreadsheet, config('constant.name_sheet.sheetFor'), $param['attrSheetFor'], $param['atrTitleScrollFor']);
                }
                $rowWOne = $param['row_w_one'];
                // draw chart hiccup number equal 21
                $this->drawChartForReportOneWayResistor($reports, $spreadsheet);
            }else{
                // find data sheet DIEN_TRO_1CHIEU_QUA_CAC_LAN_TN
                $atrSheetAllTwo = array_merge(config('attributes.oneWayResistorReport.resulnExperimentWayResistor'), $param['attrSheetAllTwo']);
                $this->findDataResult($reports, $spreadsheet, 'DIEN_TRO_1CHIEU_QUA_CAC_LAN_TN', $atrSheetAllTwo, 4, $param['title']);
                // fix style sheet DIEN_TRO_1CHIEU_QUA_CAC_LAN_TN
                $spreadsheet->setActiveSheetIndexByName('DIEN_TRO_1CHIEU_QUA_CAC_LAN_TN')->getStyle('A1:Z1000')
                    ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $spreadsheet->setActiveSheetIndexByName('DIEN_TRO_1CHIEU_QUA_CAC_LAN_TN')->getStyle('A1:Z1000')
                    ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                $styleArray = array(
                    'font'  => array(
                        'name'  => 'Times New Roman'
                    ));
                $spreadsheet->setActiveSheetIndexByName('DIEN_TRO_1CHIEU_QUA_CAC_LAN_TN')->getStyle('A1:Z1000')->applyFromArray($styleArray);
                $c = 'B';
                for($i = 0; $i <= 104; $i++){
                    $spreadsheet->setActiveSheetIndexByName('DIEN_TRO_1CHIEU_QUA_CAC_LAN_TN')->getColumnDimension($c)->setWidth(25);
                    ++$c;
                }
                $sheet1 = $spreadsheet->setActiveSheetIndexByName('DIEN_TRO_1CHIEU_QUA_CAC_LAN_TN');
                $sheet2 = $spreadsheet->setActiveSheetIndexByName('DO_LECH_DIEN_TRO_QUA_CAC_LAN_TN');
                if( $sheet1->getCell('A1')->getValue() ){
                    $sheet1->getCell('A1')->setValue($sheet1->getCell('A1')->getValue(). ' (Ω)');
                }else{
                    $sheet1->getCell('B1')->setValue($sheet1->getCell('B1')->getValue(). ' (Ω)');
                    $sheet1->mergeCells('B1:F1')->getStyle('B1:F1')->getAlignment()->setWrapText(false);
                }
                $sheet2->getCell('A1')->setValue($sheet2->getCell('A1')->getValue(). ' (%)');
                // find data sheet DO_LECH_DIEN_TRO_QUA_CAC_LAN_TN
                $atrSheetAllThree = array_merge(config('attributes.oneWayResistorReport.attrValueThroughExperiments'), $param['atrSheetAllThree']);
                $sheetStatistical = $spreadsheet->setActiveSheetIndexByName('DO_LECH_DIEN_TRO_QUA_CAC_LAN_TN');
                foreach ($reports as $key => $value){
                    $startIndexRow = 3;
                    $value['date_title'] = 'ΔR (%) của ngày '.$value['date'];
                    foreach ($atrSheetAllThree as $k => $item) {
                        $sheetStatistical->getCell('A'.($startIndexRow))->setValue($param['title'][$k]??'');
                        $sheetStatistical->getCell(range('B','Z')[$key].($startIndexRow))->setValue($value[$item]??'');
                        $startIndexRow++;
                    }
                }
                // find data - test results for 1 dimensional resistance
                $keyChart = 1;
                // fin data MBA
                foreach (config('attributes.oneWayResistorReport.atrChartHighPressure') as $value){
                    $this->findDataThroughExperiments($reports ,$spreadsheet, 'DATA_'.($keyChart), $value, config('attributes.oneWayResistorReport.listTitleExchange'));
                    $keyChart++;
                }
                // find data scroll 2
                foreach ($param['atrChartScrollTwo'] as $value){
                    $this->findDataThroughExperiments($reports ,$spreadsheet, 'DATA_'.($keyChart), $value, $param['atrTitleScrollTwo']);
                    $keyChart++;
                }
                // find data scroll 3
                if(!empty($param['atrChartScrollThree'])){
                    foreach ($param['atrChartScrollThree'] as $value){
                        $this->findDataThroughExperiments($reports ,$spreadsheet, 'DATA_'.($keyChart), $value, $param['atrTitleScrollThree']);
                        $keyChart++;
                    }
                }
                // find data scroll 4
                if(!empty($param['atrChartScrollFor'])){
                    foreach ($param['atrChartScrollFor'] as $value){
                        $this->findDataThroughExperiments($reports ,$spreadsheet, 'DATA_'.($keyChart), $value, $param['atrTitleScrollFor']);
                        $keyChart++;
                    }
                }
                // find data - 1-way resistance bias
                foreach ($param['atrChartHighPressure'] as $value){
                    fillDataChart($reports ,$spreadsheet, 'DATA_'.($keyChart), $value);
                    $keyChart++;
                }
                if(count($reports) == 2) {
                    // find data "Báo cáo phân tích độ lệch điện trở 1 chiều của từng pha giữa 2 lần thí nghiệm" in case there are 2 reports
                    $objectTitle = array_merge(config('attributes.oneWayResistorReport.listTitleExchange'), $param['attrTitleReport']);
                    foreach ($param['atrTwoTimesExperiment'] as $index => $item) {
                        if (($reports[1][$item] == '' && $reports[0][$item] != '') || ($reports[1][$item] != '' && $reports[0][$item] == '') || ($reports[1][$item] != '' && $reports[0][$item] == 0)) {
                            return back()->withErrors(['Dữ liệu đầu vào không hợp lệ!']);
                        }

                        $reports_sync[$item] = (!empty($reports[1][$item]) && !empty($reports[0][$item])) ? (($reports[1][$item] - $reports[0][$item]) / $reports[0][$item] * 100) : '';
                        foreach($objectTitle as $val){
                            $reports_sync[$val] = $reports[1][$val]??'';
                        }
                    }

                    // find data "DO_LECH_DIEN_TRO_GIUA_2_LAN_TN"
                    $time = $reports[0]['date'] . ' và ' . $reports[1]['date'];
                    $startIndexRow = $this->finDataBetweenTwoExperiments($reports_sync, $spreadsheet, config('attributes.oneWayResistorReport.atrValueTwoTimesExperimentTableOne'), 4, 'Cao áp', $time);
                    $startIndexRow = $this->finDataBetweenTwoExperiments($reports_sync, $spreadsheet, $param['atrAllTableForScrollTwo'], $startIndexRow, $param['nameAllTableForScrollTwo'], $time);
                    if(!empty($param['atrAllTableForScrollThree'])){
                        $startIndexRow = $this->finDataBetweenTwoExperiments($reports_sync, $spreadsheet, $param['atrAllTableForScrollThree'], $startIndexRow, $param['nameAllTableForScrollThree'], $time);
                    }
                    if(!empty($param['atrAllTableForScrollFor'])){
                        $this->finDataBetweenTwoExperiments($reports_sync, $spreadsheet, $param['atrAllTableForScrollFor'], $startIndexRow, $param['nameAllTableForScrollFor'], $time);
                    }

                    // find data "Kết quả"
                    $resuln = 'Pass';
                    // check "Độ lệch điện trở 1 chiều giữa các pha ở ít nhất 1 nấc ΔR3pha > 2%"
                    foreach ($reports as $value) {
                        foreach ($atrSheetAllThree as $item) {
                            if (!empty($item) && !empty($value[$item]) && $value[$item] > 2) {
                                $resuln = 'Fail';
                            }
                        }
                    }
                    // check "Độ lệch điện trở 1 chiều của từng pha qua 2 lần thí nghiệm ở ít nhất 1 nấc (ở bất kì pha nào) ΔR1pha > 2%"
                    if ($resuln == 'Pass') {
                        foreach ($param['atrTwoTimesExperiment'] as $item) {
                            if ($reports_sync[$item] > 2) {
                                $resuln = 'Fail';
                            }
                        }
                    }
                    // Create a new worksheet called "KET_QUA_DANH_GIA"
                    $sheetBDG = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'KET_QUA_DANH_GIA');
                    // Attach the "KET_QUA_DANH_GIA" worksheet as the 3nd(0->first) worksheet in the Spreadsheet object
                    $spreadsheet->addSheet($sheetBDG, 5);
                    $spreadsheet->setActiveSheetIndexByName('KET_QUA_DANH_GIA')->getStyle('A1:Z1000')->applyFromArray([
                        'font' => [
                            'name' => 'Times New Roman',
                            'size' => 16,
                            'bold' => true
                        ]
                    ]);
                    $spreadsheet->setActiveSheetIndexByName('KET_QUA_DANH_GIA')->getCell('A1')->setvalue('Kết quả đánh giá: '. $resuln);
                }
                $rowWMulti = $param['row_w_multi'];
                $rowPrecentMulti = $param['row_precent_multi'];
            }
            // fill title to excel follow excel file
            $sheetBD = $spreadsheet->setActiveSheetIndexByName(config('constant.name_sheet.sheetChart'));
            if( isset($rowWOne) ){
                foreach($rowWOne as $row){
                    if($sheetBD->getCell('B'.$row)->getValue()){
                        $sheetBD->getCell('B'.$row)->setValue(trim($sheetBD->getCell('B'.$row)->getValue()) . ' (Ω)');
                    }else{
                        $sheetBD->getCell('C'.$row)->setValue(trim($sheetBD->getCell('C'.$row)->getValue()) . ' (Ω)');
                    }
                }
            }
            if( isset($rowWMulti) ){
                foreach($rowWMulti as $row){
                    if($sheetBD->getCell('B'.$row)->getValue()){
                        $sheetBD->getCell('B'.$row)->setValue(trim($sheetBD->getCell('B'.$row)->getValue()) . ' (Ω)');
                    }else{
                        $sheetBD->getCell('C'.$row)->setValue(trim($sheetBD->getCell('C'.$row)->getValue()) . ' (Ω)');
                    }
                }
            }
            if( isset($rowPrecentMulti) ){
                foreach($rowPrecentMulti as $row){
                    if($sheetBD->getCell('B'.$row)->getValue()){
                        $sheetBD->getCell('B'.$row)->setValue(trim($sheetBD->getCell('B'.$row)->getValue()) . ' (%)');
                    }else{
                        $sheetBD->getCell('C'.$row)->setValue(trim($sheetBD->getCell('C'.$row)->getValue()) . ' (%)');
                    }
                }
            }
            fillSearchDataToExcelHighPressure($spreadsheet->setActiveSheetIndexByName(config('attributes.nameSheetAll')), $request, 4);
            $spreadsheet->setActiveSheetIndexByName(config('attributes.nameSheetAll'))->getCell('A5')->setValue(trim($spreadsheet->setActiveSheetIndexByName(config('attributes.nameSheetAll'))->getCell('A5')->getValue()).' (Ω)');
            $spreadsheet->setActiveSheetIndexByName(config('constant.name_sheet.sheetChart'))->setSelectedCell('A1');

            $title_template = 'bao-cao-phan-tich-ket-qua-thi-nghiem-dien-tro-mot-chieu-cua-cac-cuon-day-'.time().'.xlsx';
            $excel = writeExcel($spreadsheet, $title_template);
            $link_preview = "https://view.officeapps.live.com/op/embed.aspx?src=".$excel;
            $type_report = 'Máy biến áp';
            return view('machines::pages.preview-share', compact('ids','excel','title','link_preview','type_report'));
        } catch (\Exception $e) {
            Log::error('[Machines] analyze the results of the DC resistance test of the coils : Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return back()->withErrors([$e->getMessage()]);
        }
    }

    private function finDataBetweenTwoExperiments($data ,$spreadsheet, $objectValue, $startIndex, $type, $time)
    {
        $table = range('A','D');
        $startIndexRow = $startIndex;
        $sheetStatistical = $spreadsheet->setActiveSheetIndexByName('DO_LECH_DIEN_TRO_GIUA_2_LAN_TN');
        $sheetStatistical->getCell('B'.($startIndex - 1))->setValue('Độ lệch điện trở 1 chiều cuộn '.$type.' giữa 2 lần thí nghiệm ngày '.$time);
        foreach ($data as $value){
            $startIndexRow = $startIndex;
            $hiccup = 0;
            foreach ($objectValue as $k => $item) {
                $sheetStatistical->getCell($table[0].($startIndexRow))->setValue('Nấc '.$hiccup??'');
                foreach ($item as $key => $val){
                    $key++;
                    $sheetStatistical->getCell($table[$key].($startIndexRow))->setValue(@$data[$val]??'');
                }
                $startIndexRow++;
                $hiccup++;
            }
        }
        return $startIndexRow + 3;
    }

    /** find Data report 'Báo cáo phân tích kết quả thí nghiệm điện trở một chiều của các cuộn dây' - trong 1 lần thí nghiệm
     * @param $data
     * @param $spreadsheet
     * @param $nameSheet
     * @param $tableValue
     * @param $listTitleExchange
     * @return void
     */
    private function findDataThroughExperiments($data ,$spreadsheet, $nameSheet, $tableValue, $listTitleExchange)
    {
        $table = range("A", "Z");
        $startIndexData = 4;
        foreach ($listTitleExchange as $k => $value){
            $spreadsheet->setActiveSheetIndexByName($nameSheet)->getCell(range("B", "D")[$k].(3))->setValue($data[0][$value]??'');
        }
        foreach ($data as $key => $value){
            foreach ($tableValue as $k => $val){
                $spreadsheet->setActiveSheetIndexByName($nameSheet)->getCell($table[$k].$startIndexData)->setValue($value[$val]??'');
            }
            $startIndexData++;
        }
    }

    /** find Data report 'Báo cáo phân tích kết quả thí nghiệm điện trở một chiều của các cuộn dây' - trong 1 lần thí nghiệm
     * @param $data
     * @param $spreadsheet
     * @param $nameSheet
     * @param $tableValue
     * @param $title
     * @return void
     */
    private function findDataChart($data ,$spreadsheet, $nameSheet, $tableValue, $title = '')
    {
        $sheetStatistical = $spreadsheet->setActiveSheetIndexByName($nameSheet);
        $startIndexRow = 5;
        $table = range('C','Z');
        if($title != ''){
            foreach ($title as $k => $val){
                $spreadsheet->setActiveSheetIndexByName($nameSheet)->getCell($table[$k].'4')->setValue($data[0][$val]??'');
            }
        }
        foreach ($data as $value){
            $sheetStatistical->getCell('A'.($startIndexRow))->setValue($value['date']??'');
            foreach ($tableValue as $item) {
                foreach ($item as $key => $val){
                    $sheetStatistical->getCell($table[$key].($startIndexRow))->setValue($value[$val]??'');
                }
                $startIndexRow++;
            }
        }
    }

    /** find Data report 'Báo cáo phân tích kết quả thí nghiệm điện trở một chiều của các cuộn dây - Báo cáo phân tích kết quả thí nghiệm điện trở 1 chiều'
     * @param $data
     * @param $spreadsheet
     * @param $nameSheet
     * @param $tableValue
     * @param $startIndex
     * @param $titleHiccup
     * @return int
     */
    private function findDataResult($data ,$spreadsheet, $nameSheet, $tableValue, $startIndex, $titleHiccup)
    {
        $table = range('B','Z');
        $sheetStatistical = $spreadsheet->setActiveSheetIndexByName($nameSheet);
        $keyStartTable = $startIndexRow = $keyPhase = 0;
        foreach ($data as $value){
            $startIndexRow = $startIndex;
            $sheetStatistical->mergeCells($table[$keyStartTable].($startIndexRow - 1).":".$table[$keyStartTable + 2].($startIndexRow - 1))->getCell($table[$keyStartTable].($startIndexRow - 1))->setValue($value['date']??'');
            foreach ($tableValue as $index => $item) {
                $sheetStatistical->getCell('A'.($startIndexRow))->setValue( $titleHiccup[$index]??'');
                if(count($item) == 1){
                    $sheetStatistical->mergeCells($table[$keyStartTable].($startIndexRow).":".$table[$keyStartTable + 2].($startIndexRow))
                        ->getCell($table[$keyStartTable].($startIndexRow))->setValue($value[$item[0]]??'');
                }else{
                    foreach ($item as $key => $val){
                        $sheetStatistical->getCell($table[$keyStartTable + $key].($startIndexRow))->setValue($value[$val]??'');
                    }
                }
                $startIndexRow++;
            }
            $keyStartTable+=3;
        }
        return $startIndexRow + 2;
    }

    /**
     * Dielectric loss report
     *
     * @param Request $request
     * @return Application|Factory|View|RedirectResponse
     */
    public function dielectricLossReport(Request $request)
    {
        try {
            $title = 'Báo cáo phân tích kết quả thí nghiệm tổn hao điện môi và điện dung các cuộn dây máy biến áp';
            $ids = explode(',', $request->ids);
            if( count($ids) < 2 ){
                return back()->withErrors(['Vui lòng chọn ít nhất 2 biên bản để xem báo cáo!']);
            }
            $reports = $param = [];
            foreach ($ids as $key => $value) {
                $obj = 'nr:' . $value;
                $reports[] = getDataFromService('getObjectValues', $obj, config('attributes.syllableWordCircuitReport.atrValue'), '', 100);
            }
            // get data Object
            foreach ($reports as $key => $value) {
                $where = "id = U'" . $value['id'] . "'";
                $reports[$key]['date'] = @$value['zlaboratoryDate'] ? date('d/m/Y', $value['zlaboratoryDate']) : '';
                $object = 'zCA13_' . substr($value['class.type'], 3, 1);
                switch ($value['class.type']) {
                    case '13.1. BBTN MBA 2 cuộn dây, 1 cuộn cân bằng':
                        $param = config('attributes.dielectricLossReport.twoCoilsOneBalanceCoil');
                        break;
                    case '13.2. BBTN MBA 2 cuộn dây':
                        $param = config('attributes.dielectricLossReport.rollTwo');
                        break;
                    case '13.3 BBTN MBA 3 cuộn dây, chuyên nấc hạ áp':
                        $param = config('attributes.dielectricLossReport.threeRollsLowPressureStepSwitch');
                        break;
                    case '13.4. BBTN MBA 3 cuộn dây, chuyên nấc trung áp':
                        $param = config('attributes.dielectricLossReport.threeCoilsMediumVoltage');
                        break;
                    case '13.6. BBTN MBA 4 cuộn dây':
                        $param = config('attributes.dielectricLossReport.rollFor');
                        break;
                    default:
                        $param = config('attributes.dielectricLossReport.threeAutoCoils');
                        break;
                }
                $data_obj = getDataFromService('doSelect', $object, $param['atrDataProcessing'], $where, 150);
                foreach ($param['atrDataProcessing'] as $val) {
                    $reports[$key][$val] = $data_obj[0][$val]??'';
                }
            }
            // order by zlaboratoryDate
            foreach ($reports as $key => $row)
            {
                $date[$key] = $row['zlaboratoryDate']??'';
            }
            array_multisort($date, SORT_ASC, $reports);
            // check template
            $countData = count($reports) >= 30 ? 30 : count($reports);
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setIncludeCharts(true);
            $template = storage_path("templates_excel/cao-ap/bao-cao-phan-tich/template-bao-cao-phan-tich-ket-qua-thi-nghiem-ton-hao-dien-moi/". $param['titleTemplate'] . $countData . "-date.xlsx");
            $spreadsheet = $reader->load($template);

            // find title chart
            // $keyColumn is the line starting to dump the chart title data || $keyTitleValue is key to retrieve title chart || $countChart is count how many graphs there are
            $keyTitleValue = 0;
            $keyColumn = 3;
            $countChart = count($param['arrValueChart']);
            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $chartNames = $worksheet->getChartNames();
                foreach($chartNames as $chartName)
                {
                    // If you run out of dielectric diagram, then switch to capacitance chart
                    if($keyTitleValue == $countChart / 2){
                        $keyTitleValue = 0;
                        $keyColumn = $param['keyStartCapacitance'];
                    }
                    $spreadsheet->setActiveSheetIndexByName(config('constant.name_sheet.sheetChart'))->getCell('C' . ($keyColumn))->setValue($param['atrDataTitle'][$keyTitleValue]??'');
                    $chart = $worksheet->getChartByName($chartName);
                    $titleChar = new \PhpOffice\PhpSpreadsheet\Chart\Title($param['atrDataTitle'][$keyTitleValue]??'');
                    $chart->setTitle($titleChar);
                    $keyColumn+=33;
                    $keyTitleValue++;
                }
            }
            // find data THONG_KE_DU_LIEU
            $startIndexRow = 6;
            $sheetStatistical = $spreadsheet->setActiveSheetIndexByName(config('attributes.nameSheetAll'));
            foreach ($reports as $key => $value) {
                $sheetStatistical->mergeCells('A' . ($startIndexRow) . ":" . 'A' . ($startIndexRow + count($param['atrDataValue']) - 1))->getCell('A' . ($startIndexRow))->setValue($value['date'] ?? '');
                foreach ($param['atrDataValue'] as $key => $val){
                    $value['title'] = $param['atrDataTitle'][$key];
                    foreach ($val as $index => $item){
                        $sheetStatistical->getCell(range('B', 'G')[$index] . ($startIndexRow))->setValue($value[$item]??'');
                    }
                    $startIndexRow++;
                }
            }
            // find data sheet THONG_KE_DU_LIEU_DIEN_MOI || THONG_KE_DU_LIEU_MBA
            $this->findDataSheetAllDielectricLossReport($reports ,$spreadsheet, 'THONG_KE_DU_LIEU_DIEN_MOI', $param['atrSheetStatisticalDielectric'], $param['atrDataTitle']);
            $this->findDataSheetAllDielectricLossReport($reports ,$spreadsheet, 'THONG_KE_DU_LIEU_MBA', $param['atrSheetStatisticalTransformers'], $param['atrDataTitle']);
            // find data CHART
            $keyTitleValue = 0;
            foreach ($param['arrValueChart'] as $key => $value){
                if($keyTitleValue >= $countChart / 2){
                    $keyTitleValue = 0;
                }
                if($key < $countChart / 2){
                    $titleChart = 'Biểu đồ phân tích giá trị tổn hao điện môi các phép đo qua các lần thí nghiệm ';
                }else{
                    $titleChart = 'Biểu đồ phân tích giá trị đo điện dung các phép đo qua các lần thí nghiệm ';
                }
                $key++;
                fillDataChart($reports ,$spreadsheet, 'DATA_'.$key, $value);
                $spreadsheet->setActiveSheetIndexByName('DATA_'.$key)->getCell('A1')->setValue($titleChart.$param['atrDataTitle'][$keyTitleValue]??'');
                $keyTitleValue++;
            }
            if(count($reports) == 2){
                // find data "Báo cáo phân tích độ lệch điện dung giữa 2 lần thí nghiệm" in case there are 2 reports
                foreach ($param['atrTwoTimesExperiment'] as $index => $item) {
                    if (($reports[1][$item] == '' && $reports[0][$item] != '') || ($reports[1][$item] != '' && $reports[0][$item] == '') || ($reports[1][$item] != '' && $reports[0][$item] == 0)) {
                        return back()->withErrors(['Dữ liệu đầu vào không hợp lệ!']);
                    }
                    $reports_sync[0][$item] = (!empty($reports[1][$item]) && !empty($reports[0][$item])) ? (($reports[1][$item] - $reports[0][$item]) / $reports[0][$item] * 100) : '';
                }

                $reports_sync[0]['date'] = 'Độ lệch điện dung giữa 2 lần thí nghiệm ngày '.$reports[0]['date'].' và ngày '.$reports[1]['date'];
                $this->findDataSheetAllDielectricLossReport($reports_sync, $spreadsheet, 'DO_LECH_DIEN_DUNG_GIUA_2_LAN_TN', $param['atrTwoTimesExperiment'], $param['atrDataTitle'], '%');

                // find data "Kết quả"
                $result = [];
                // check "tg ≤ 1% then pass, the rest fail"
                foreach ($param['atrTG'] as $key => $item){
                    if(!empty($reports[0][$item]) && $reports[0][$item] <= 1 && !empty($reports[1][$item]) && $reports[1][$item] <= 1){
                        $result[0][$param['atrDataTitle'][$key]] = 'Pass';
                    }else{
                        $result[0][$param['atrDataTitle'][$key]] = 'Fail';
                    }
                }
                // check "điện dung ≤ 10% and điện dung >= -5% and tg's result reached then pass, the rest fail"
                foreach ($reports_sync as $value){
                    foreach ($param['atrTwoTimesExperiment'] as $key => $item){
                        if ($value[$item] === '') {
                            $result[0][$param['atrDataTitle'][$key]] = '';
                            continue;
                        }
                        if ($result[0][$param['atrDataTitle'][$key]] == 'Pass' && $value[$item] >= -5 && $value[$item] <= 10) {
                            $result[0][$param['atrDataTitle'][$key]] = 'Pass';
                            continue;
                        }
                        $result[0][$param['atrDataTitle'][$key]] = 'Fail';
                    }
                }
                $this->findDataSheetAllDielectricLossReport($result, $spreadsheet, 'DANH_GIA', $param['atrDataTitle'], $param['atrDataTitle'], '', '');
            }elseif(count($reports) > 2){
                $spreadsheet->setActiveSheetIndexByName('DO_LECH_DIEN_DUNG_GIUA_2_LAN_TN')->getCell('A1')->setValue('Báo cáo phân tích độ lệch điện dung giữa 2 lần thí nghiệm - Lỗi do chọn trên 2 biên bản!');
                $spreadsheet->setActiveSheetIndexByName('DANH_GIA')->getCell('A1')->setValue('Đánh giá - Lỗi do chọn trên 2 biên bản!');
            }
            fillSearchDataToExcelHighPressure($sheetStatistical, $request, 4);
            alignmentExcel($sheetStatistical, 'B10:B1000');
            alignmentExcel($spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU_DIEN_MOI'), 'A5:A1000');
            alignmentExcel($spreadsheet->setActiveSheetIndexByName('THONG_KE_DU_LIEU_MBA'), 'A5:A1000');
            alignmentExcel($spreadsheet->setActiveSheetIndexByName('DO_LECH_DIEN_DUNG_GIUA_2_LAN_TN'), 'A5:A1000');
            alignmentExcel($spreadsheet->setActiveSheetIndexByName('DANH_GIA'), 'A5:A1000');
            // fill title to sheet
            $sheetBD =  $spreadsheet->setActiveSheetIndexByName(config('constant.name_sheet.sheetChart'));
            // get index row title from template
            $rowPercent = [3, 36, 69, 102, 135, 168, 201, 234, 267, 300, 333, 366, 399];
            $rowPF = [435, 437, 470, 503, 536, 569, 602, 635, 668, 701, 734, 767, 800, 833];
            if($param['titleTemplate'] == 'template-18-chart-'){
                $rowPercent = [3, 36, 69, 102, 135, 168, 201, 234, 267];
                $rowPF = [303, 305, 338, 371, 404, 437, 470, 503, 536, 569];
            }
            foreach($rowPercent as $row){
                $sheetBD->getCell('C'.$row)->setValue(trim($sheetBD->getCell('C'.$row)) . ' (%)');
            }
            foreach($rowPF as $row){
                $sheetBD->getCell('C'.$row)->setValue(trim($sheetBD->getCell('C'.$row)) . ' (pF)');
            }

            $spreadsheet->setActiveSheetIndexByName(config('constant.name_sheet.sheetChart'))->setSelectedCell('A1');

            $title_template = '/bao-cao-phan-tich-ket-qua-thi-nghiem-ton-hao-dien-moi-va-dien-dung-cac-cuon-day-may-bien-ap-' . time() . '.xlsx';
            $excel = writeExcel($spreadsheet, $title_template);
            $link_preview = "https://view.officeapps.live.com/op/embed.aspx?src=" . $excel;
            $type_report = 'Máy biến áp';
            return view('machines::pages.preview-share', compact('ids', 'excel', 'title', 'link_preview', 'type_report'));
        } catch (\Exception $e) {
            Log::error('[Machines] dielectric Loss Report : Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return back()->withErrors([$e->getMessage()]);
        }
    }
    /** Report 'Find data table - Báo cáo Phân tích kết quả thí nghiệm tổn hao điện môi và điện dung các cuộn dây máy biến áp'
     * @param $data
     * @param $spreadsheet
     * @param $nameSheet
     * @param $tableValue
     * @param $titleValue
     * @param string $unit
     * @param bool $titleTable
     * @return void
     */
    private function findDataSheetAllDielectricLossReport($data ,$spreadsheet, $nameSheet, $tableValue, $titleValue, $unit = '', $titleTable = TRUE)
    {
        $sheetStatistical = $spreadsheet->setActiveSheetIndexByName($nameSheet);
        // get title
        $startIndexRow = 5;
        foreach ($titleValue as $value){
            $sheetStatistical->getCell('A'.($startIndexRow))->setValue($value??'');
            $startIndexRow++;
        }
        $table = range('B','Z');
        foreach ($data as $key => $value){
            $startIndexRow = 5;
            if($titleTable == TRUE){
                $sheetStatistical->getCell($table[$key].(4))->setValue($value['date']??'');
            }
            foreach ($tableValue as $item) {
                $sheetStatistical->getCell($table[$key].($startIndexRow))->setValue((isset($value[$item]) && $value[$item] !== '') ? ($value[$item] . $unit) : $value[$item]);
                $startIndexRow++;
            }
        }
    }

    /**
     * handle draw chart oneWayResistorReport
     * @param array $data
     * @param $spreadsheet
     * @return mixed
     */
    private function drawChartForReportOneWayResistor(array $data, $spreadsheet)
    {
        // get array value data chart and caculate min max axis
        $attrs = collect(config('attributes.oneWayResistorReport.attrSheetOne'))->flatten()->toArray();
        $result = array_filter(getArrayValueByArrayKey($data[0], $attrs));
        $min = count($result) > 0 ? (float)min($result) : 0;
        $max = count($result) > 0 ? (float)max($result) : 100;
        // Set the X-Axis Labels
        $xAxisTickValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'DATA_1!$C$4:$E$4', null, 3),
        ];
        // Set the Labels for each data series we want to plot
        // Set the Data values for each data series we want to plot
        $dataSeriesValues = [];
        $dataSeriesLabels = [];
        for( $i = 5; $i <= 25; $i++ ){
            $dataSeriesValues[] =  new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, 'DATA_1!$C$'.$i.':$E$'.$i, null, 3);
            $dataSeriesLabels[] = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'DATA_1!$B$'.$i);
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
        $title = new Title('Biểu đồ phân tích giữa 3 pha tại 1 nấc');
        // Set the chart legend
        $legend = new Legend(Legend::POSITION_BOTTOM, null, false);
        // Create the chart
        $chart = new Chart(
            'chart5', // name
            $title, // title
            $legend, // legend
            $plotArea1, // plotArea
            false, // plotVisibleOnly
            DataSeries::EMPTY_AS_GAP, // displayBlanksAs
            null, // xAxisLabel
            null, // yAxisLabel
            null, // Axis X
            $yaxis, // Axis Y
        );
        // Set the position where the chart should appear in the worksheet
        $chart->setTopLeftPosition('C5', 0, 0);
        $chart->setBottomRightPosition('T34', 0, 0);
        // Add the chart to the worksheet
        return $spreadsheet->setActiveSheetIndexByName(config('constant.name_sheet.sheetChart'))->addChart($chart);
    }
}
