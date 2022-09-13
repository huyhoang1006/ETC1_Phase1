<?php

namespace Modules\Measure\Http\Controllers;

use App\Exports\DeviceListingExport;
use App\Exports\EquipmentUnderInspectionExport;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use File;
use App\Services\CAWebServices;
use Exception;
use GreenCape\Xml\Converter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MeasureController extends Controller
{
    /** index report 1
     * Display a listing of the resource.
     * @return Renderable
     */
    public function equipmentUnderInspection(Request $request)
    {
        $areas = Cache::remember('list_areas', 7200, function () {
            return getDataFromService('doSelect', 'zArea', ['id', 'zsym'], 'zsym IS NOT NULL AND active_flag = 1', 150);
        });
        sortData($areas);
        $units = Cache::remember('unitList', 7200, function () {
            return getDataFromService('doSelect', 'zDVQL', ['id', 'zsym'], 'zsym IS NOT NULL AND active_flag = 1', 150);
        });
        sortData($units);
        $report = "class.type In('Máy biến điện áp', 'Máy biến dòng', 'Công tơ')";

        $request = $request->all();
        $request['type'] = $report ;
        $request['attributes'] = ['id','zrefnr_td.zsym','zrefnr_nl.zsym','name','zCI_Device_Type.zsym','class.type','zrefnr_dvql.zsym','zCustomer.zsym'];
        $request['attributes_2'] = ['zhankiemdinhdate','zvitridialy', 'zdvquanlydiemdosrel', 'zdvquanlydiemdosrel.zsym'];
        $request['number'] = 1;

        $items = !empty($request['type_device']) ? getEquipmentUnderInspection($request['type'], $request) : [];
        sortData($items, 'asc', 'name');
        return view('measure::equipment_under.index', compact('items', 'request', 'units', 'areas'));
    }

    /** export report 1
     * Export industrial furnace by manufacture
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportEquipmentUnderInspection(Request $request)
    {
        $request['data'] = $request->data;
        $request['number'] = 1;
        return Excel::download(new EquipmentUnderInspectionExport($request), 'Báo cáo danh sách thiết bị theo hạn kiểm định.xlsx');
    }

    /** index report 2
     * Display a listing of the resource.
     * @return Renderable
     */
    public function equipmentEveryYear(Request $request)
    {
        $units = Cache::remember('unitList', 7200, function () {
            return getDataFromService('doSelect', 'zDVQL', ['id', 'zsym'], 'zsym IS NOT NULL AND active_flag = 1', 150);
        });
        sortData($units);
        $report = "class.type In('Máy biến điện áp', 'Máy biến dòng', 'Công tơ')";

        $request = $request->all();
        $request['type'] = $report;
        $request['attributes'] = ['id','class.type','zCI_Device_Type.zsym','zrefnr_dvql.zsym','zManufacturer.zsym','zYear_of_Manafacture.zsym'];
        $request['number'] = 2;

        $items = !empty($request['type_device_id']) ? getEquipmentUnderInspection($request['type'], $request) : [];
        $year = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now())->year;

        $manufacturer = !empty($request['type_device_id']) ? collect(getDataFromService('doSelect', 'zManufacturer', ['id', 'zsym'], 'zsym IS NOT NULL AND zclass = ' . $request['type_device_id'], 300))->sortBy('zsym')->pluck('zsym', 'id')->toArray() : [];
        return view('measure::equipment_every.index', compact('items', 'request', 'year', 'units', 'manufacturer'));
    }

    /** export report 2
     * Export industrial furnace by manufacture
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportEquipmentEveryYear(Request $request)
    {
        $request['data'] = $request->data;
        $request['number'] = 2;
        return Excel::download(new EquipmentUnderInspectionExport($request), 'Báo cáo số lượng thiết bị của từng năm.xlsx');
    }

    /**
     * Get device listing report
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function deviceListingReport(Request $request)
    {
        $units = Cache::remember('unitList', 7200, function () {
            return getDataFromService('doSelect', 'zDVQL', ['id', 'zsym'], 'zsym IS NOT NULL AND active_flag = 1', 150);
        });
        sortData($units);

        $tds = $request->zrefnr_dvql_ids ? $this->getTD($request) : [];

        $deviceModel = Cache::remember('deviceModel', 7200, function () {
            return getAllDataFromService('zCI_Device_Kind', ['id', 'zsym'], '');
        });
        $deviceModel = collect($deviceModel)->unique('zsym')->toArray();

        $years = getAllDataFromService('zYear_of_Manafacture', ['id', 'zsym'], 'active_flag = 1');
        sortData($years, 'desc');

        $deliveryUnit = getAllDataFromService('zdvgiaodiennang', ['id', 'zsym'], 'active_flag = 1');
        sortData($deliveryUnit);

        $receivingUnit = getAllDataFromService('zdvnhandiennang', ['id', 'zsym'], 'active_flag = 1');
        sortData($receivingUnit);

        $typeMeasuring = getAllDataFromService('zloaidiemdo', ['id', 'zsym'], 'active_flag = 1');
        sortData($typeMeasuring);
        $data = $request->all();
        $deviceArr = [];
        if (!empty($data['type_id'])) {
            if ($data['type_id'] == 1004903) {
                $extendAttrs = [
                    'zdvquanlydiemdosrel.zsym',
                    'zdvgiaodiennangsrel.zsym',
                    'zdvnhandiennangsrel.zsym',
                    'zvitridialy',
                    'zloaidiemdosrel.zsym',
                    'zngaynttinhdate',
                    'zngayntmangtaidate',
                    'zTUinform',
                    'zTIinform',
                    'zhangkepmachdong',
                    'zhangkepmachap',
                    'zdienapsrel.zsym',
                    'zdongdiensrel.zsym',
                    'zcapcxpsrel.zsym',
                    'zcapcxqsrel.zsym',
                    'zhangsosxungsrel.zsym',
                    'ztysotu',
                    'ztysoti',
                    'zsolanlaptrinh',
                    'zlasttimelaptrinhdate',
                    'zkqkiemdinhsrel.zsym',
                    'zcanhbao',
                    'zhankiemdinhdate',
                    'zsotemkiemdinh',
                    'zseritemkiemding',
                    'zchucnangsrel',
                ];
            } elseif ($data['type_id'] == 1002783) {
                $extendAttrs = [
                    'zhankiemdinhdate',
                    'ztemkiemdinhnum',
                    'zseritemkiemding',
                    'zkqkiemdinhsrel.zsym',
                    'ztysobiendongstr',
                    'zcapchinhxacstr',
                    'zdungluonstr',
                    'zniemphongnapbocstr',
                ];
            } else {
                $extendAttrs = [
                    'zhankiemdinhdate',
                    'ztemkiemdinhnum',
                    'zseritemkiemding',
                    'zkqkiemdinhsrel.zsym',
                    'ztysobienapstr',
                    'zcapchinhxacstr',
                    'zdungluonstr',
                    'zgiatritustr',
                ];
            }

            $deviceArr = getDevice($request, $extendAttrs);
        }
        sortData($deviceArr, 'asc', 'name');

        $deviceTypes = !empty($data['type_id']) ? collect(getDataFromService('doSelect', 'zCI_Device_Type', ['id', 'zsym'], 'zsym IS NOT NULL AND zclass = ' . $data['type_id'], 300))->pluck('zsym', 'id')->toArray() : [];
        asort($deviceTypes);
        
        return view('measure::device_listing.index', [
            'deviceArr' => $deviceArr,
            'request' => $data,
            'units' => $units,
            'deviceTypes' => $deviceTypes,
            'tds' => $tds,
            'deviceModel' => $deviceModel,
            'years' => $years,
            'deliveryUnit' => $deliveryUnit,
            'receivingUnit' => $receivingUnit,
            'typeMeasuring' => $typeMeasuring,
        ]);
    }

    /**
     * get list td by list dvql id
     * @param $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    private function getTD($request)
    {
        $data = [];
        if( !empty(array_filter($request->zrefnr_dvql_ids)) ){
            $whereClause = " zref_dvql IN( ".join(',', $request->zrefnr_dvql_ids) . ")";
            $data = getDataFromService('doSelect', 'zTD', ['id', 'zsym'], $whereClause, 300);
            usort($data, function($a, $b){
                return strcmp(strtolower($a['zsym']), strtolower($b['zsym']));
            });
        }
        return $data;
    }

    /**
     * ajax get TD by list dvql id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxGetTD(Request $request)
    {
        try {
            $data = $this->getTD($request);
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('[Measure] Ajax get TD: Fail | Reason: ' . $e->getMessage() . '. ' . $e->getFile() . ':' . $e->getLine() . '| Params: ' . json_encode($request->all()));
            return response()->json([
                'error' => [ $e->getMessage() ]
            ]);
        }
    }

    /**
     * Device listing export
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function deviceListingExport(Request $request)
    {
        return Excel::download(new DeviceListingExport($request), 'Báo cáo thống kê danh sách thiết bị.xlsx');
    }

    /** index report 4
     * Display a listing of the resource.
     * @return Renderable
     */
    public function incidentByCompany(Request $request)
    {
        $request = $request->all();
        try {
            $files = File::directories(public_path('/export/do-luong'));
            foreach($files as $dir){
                $link = File::files($dir);
                foreach($link as $f){
                    $data[] = $f->getRelativePathname();
                }
            }
            if(!empty($data)){
                foreach($data as $key => $val){
                    $item = explode("_",$val);
                    $items[$key]['time'] = substr($item[2], 0, 7);
                    $items[$key]['year'] = substr($item[2], 3, 4);
                    $items[$key]['link'] = '/export/do-luong/'.$items[$key]['year'].'/'.$val;
                    $items[$key]['type'] = $item[1]??'';
                    if($items[$key]['type'] == 'cong-to'){
                        $items[$key]['type'] = 'Công tơ';
                    }elseif($items[$key]['type'] == 'bien-ap-do-luong'){
                        $items[$key]['type'] = 'Máy biến điện áp';
                    }elseif($items[$key]['type'] == 'bien-dong-do-luong'){
                        $items[$key]['type'] = 'Máy biến dòng';
                    }
                }
                if(!empty($request['type_device'])){
                    foreach($items as $key => $value){
                        if($value['type'] != $request['type_device']){
                            unset($items[$key]);
                            continue;
                        }
                    }
                }
                sortData($items, 'asc', 'type');
            }else{
                $items = '';
            }
        } catch (Exception $e) {
            $items = '';
        }
        return view('measure::incident_by_company.index', compact('items', 'request'));
    }

    /**
     * Defective devices by manufacturer
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function defectiveDevicesByManufacturer(Request $request)
    {
        $units = Cache::remember('unitList', 7200, function () {
            return getDataFromService('doSelect', 'zDVQL', ['id', 'zsym'], 'zsym IS NOT NULL AND active_flag = 1', 150);
        });
        sortData($units);
        $areas = Cache::remember('list_areas', 7200, function(){
            return getDataFromService('doSelect', 'zArea', ['id', 'zsym'], 'zsym IS NOT NULL AND active_flag = 1', 250);
        });
        sortData($areas);
        $request = $request->all();
        return view('measure::devices_by_manufacturer.index', compact('request', 'units', 'areas'));
    }

    /**
     * Export defective devices by manufacturer
     *
     * @param Request $request
     * @return false|string
     * @throws \ErrorException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function exportDefectiveDevicesByManufacturer(Request $request)
    {
        // get user login session
        $user = session()->get(env('AUTH_SESSION_KEY'));
        // get url webservice
        $service = new CAWebServices(env('CA_WSDL_URL'));
        $type = '2. Biên bản nghiệm thu hệ thống đo đếm điện năng';
        $whereClause = "class.type = '" . $type . "'";
        if (!empty($request['date_from'])) {
            $whereClause .= " AND zlaboratoryDate >= " . strtotime($request['date_from']) . "";
            $request['date_from'] = Carbon::parse($request['date_from'])->format('d/m/Y');
        }else{
            $request['date_from'] = '---';
        }
        if (!empty($request['date_to'])) {
            $whereClause .= " AND zlaboratoryDate <= " . strtotime($request['date_to']) . "";
            $request['date_to'] = Carbon::parse($request['date_to'])->format('d/m/Y');
        }else{
            $request['date_to'] = '---';
        }

        $time = 'Thời gian thống kế từ '.$request['date_from'].' đến '.$request['date_to']??'';
        // query data webservice
        $payload = [
            'sid' => $user['ssid'],
            'objectType' => 'nr',
            'whereClause' => $whereClause,
            'maxRows' => 150,
            'attributes' => config('attributes.attribute_measure_report_5'),
        ];
        // return data webservice
        $resp = $service->callFunction('doSelect', $payload);
        // convert data to object
        $parser = new Converter($resp->doSelectReturn);
        $items = [];
        if (!isset($parser->data['UDSObjectList'][0])) {
            $parser->data['UDSObjectList'] = [$parser->data['UDSObjectList']];
        }
        foreach ($parser->data['UDSObjectList'] as $obj) {
            if (!$obj) continue;
            $item = [];
            $item['handle_id'] = $obj['UDSObject'][0]['Handle'];
            foreach ($obj['UDSObject'][1]['Attributes'] as $attr) {
                if ($attr['Attribute'][1]['AttrValue']) {
                    $item[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                }
            }
            array_push($items, $item);
        }

        // get data assoc
        foreach($items as $key => $value){
            if(!empty($value['zCI_Device'])){
                $payload_2 = [
                    'sid' => $user['ssid'],
                    'objectType' => 'zETC_Device',
                    'whereClause' => "id = U'" . $value['zCI_Device'] . "'",
                    'maxRows' => 1,
                    'attributes' => [
                        'zkqkiemdinhsrel.zsym',
                        'zdvquanlydiemdosrel.zsym',
                    ]
                ];
                $resp_2 = $service->callFunction('doSelect', $payload_2);
                $parser_2 = new Converter($resp_2->doSelectReturn);
                foreach ($parser_2->data['UDSObjectList']['UDSObject'][1]['Attributes'] as $attr_2) {
                    $items[$key][$attr_2['Attribute'][0]['AttrName']] = $attr_2['Attribute'][1]['AttrValue']??'';
                }
            }
        }

        // get data table BÁO CÁO SỐ LƯỢNG THIẾT BỊ SAI SỐ KHÔNG ĐẠT THEO HÃNG SẢN XUẤT
        $type_item_1 = config('attributes.attribute_measure_report_5_1');
        $name_table = 1;
        $filter_search = ['zCI_Device.zrefnr_dvql.zsym','zCI_Device.zArea.zsym'];
        $item_1 = countDeviceMeasure($items, $type_item_1, $request, $filter_search, $name_table);

        // get data table BÁO CÁO SỐ LƯỢNG THIẾT BỊ THAY THẾ
        $type_item_2 = config('attributes.attribute_measure_report_5_2');
        $name_table = 2;
        $filter_search = ['zCI_Device1.zrefnr_dvql.zsym','zCI_Device1.zArea.zsym'];
        $item_2 = countDeviceMeasure($items, $type_item_2, $request, $filter_search, $name_table);
        sortData($item_1, 'asc', 'zCI_Device.class.type');
        sortData($item_2, 'asc', 'zCI_Device1.name');
        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);
        // Read Template Excel
        $template = storage_path('templates_excel/do_luong/template_report_5.xlsx');
        $spreadsheet = $reader->load($template);
        // Create New Writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);
        $sheetTable2 = $spreadsheet->setActiveSheetIndexByName('SO_LUONG_THIET_BI_THAY_THE');
        $sheetTable1 = $spreadsheet->setActiveSheetIndexByName('SAI_SO_THEO_HANG_SAN_XUAT');

        $sheetTable1->getCell('A3')->setValue($time ?? '');
        $sheetTable2->getCell('A3')->setValue($time ?? '');
        $count = 0;
        foreach($item_1 as $value){
            $count++;
            $colum_key = $count + 4;
            $table = range('A', 'F');
            $colum_name = config('attributes.attribute_measure_report_5_3');
            foreach($colum_name as $k => $val){
                $sheetTable1->getCell($table[$k] . ($colum_key))->setValue($value[$val]??'');
            }
        }
        $count = 0;
        foreach($item_2 as $value){
            $count++;
            $colum_key = $count + 4;
            $table = range('A', 'G');
            $colum_name = config('attributes.attribute_measure_report_5_4');
            foreach($colum_name as $k => $val){
                if($val != 'zlaboratoryDate'){
                     $sheetTable2->getCell($table[$k] . ($colum_key))->setValue($value[$val]??'');
                }else{
                    if(!empty($value['zlaboratoryDate'])){
                         $sheetTable2->getCell($table[$k] . ($colum_key))->setValue(date('d-m-Y', $value['zlaboratoryDate']));
                    }
                }
            }
        }

        $columns = range('B', 'E');
        $inputs = [
            'Đơn vị quản lý: ' => 'data_management',
            'Khu vực (Tỉnh/thành phố): ' => 'data_area',
            'Ngày bắt đầu cần thống kê: ' => 'date_from',
            'Ngày kết thúc cần thống kê: ' => 'date_to',
        ];
        fillSearchRequestToExcel($sheetTable1, $request, $columns, $inputs, 2);
        alignmentExcel($sheetTable2, 'A5:A1000');
        alignmentExcel($sheetTable2, 'C5:G1000');
        alignmentExcel($sheetTable1, 'A7:E1000');
        $spreadsheet->setActiveSheetIndexByName('SAI_SO_THEO_HANG_SAN_XUAT');
        $result = writeExcel($spreadsheet, 'bao-cao-so-luong-thiet-bi-sai-so-khong-dat-theo-hang-san-xuat-'.time().'.xlsx');
        return json_encode($result);
    }
}
