<?php

namespace App\Exports;

use App\Services\CAWebServices;
use GreenCape\Xml\Converter;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class AutomationExport implements FromView, WithEvents
{

    public function __construct($request = [])
    {
        $this->request = $request;
    }
    /**
     * apply style for sheet
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
                $cellRange2 = 'A6:BZ1000'; // content
                $event->sheet->getDelegate()->getStyle($cellRange2)->getFont()->setSize(12);
                $event->sheet->getDelegate()->getStyle('A1')->applyFromArray([
                    'font' => [
                        'size'      =>  20,
                        'bold'      =>  true
                    ]
                ]);
                // apply center content
                $event->sheet->getDelegate()->getStyle('A1:BZ1000')->applyFromArray([
                    'alignment' => [
                        'horizontal'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                        'vertical'     => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        'textRotation' => 0,
                        'wrapText'     => TRUE
                    ],
                    'font' => [
                        'name' => 'Times New Roma'
                    ]
                ]);
                $event->sheet->getDelegate()->getStyle('A3')->applyFromArray([
                    'font' => [
                        'size'      =>  13,
                        'bold'      =>  true
                    ]
                ]);
                $event->sheet->getDelegate()->getStyle('A5:BZ6')->applyFromArray([
                    'font' => [
                        'size'      =>  13,
                        'bold'      =>  true
                    ]
                ]);
                // apply width column
                $c = 'A';
                for($i = 0; $i <= 104; $i++){
                    $event->sheet->getColumnDimension($c)->setWidth(25);
                    ++$c;
                }
                
                for($i = 7; $i <= 1000; $i++){
                    $event->sheet->getDelegate()->getRowDimension($i)->setRowHeight(30);
                }
                alignmentExcel($event->sheet, 'A1', 'center');
                alignmentExcel($event->sheet, 'A3', 'center');
                $columns = range('B', 'J');
                $inputs = [
                    'Ngày bắt đầu: ' => 'from',
                    'Ngày kết thúc: ' => 'to',
                    'Khu vực: ' => 'area_name',
                    'Trạm/ Nhà máy: ' => 'td_names',
                    'Ngăn lộ / Hệ thống: ' => 'nl_names',
                    'Thiết bị: ' => 'name',
                    'Loại thiết bị: ' => 'devices_name',
                    'Hãng sản xuất: ' => 'manufacture_name',
                    'Kiểu: ' => 'type',
                    'Số chế tạo: ' => 'series',
                    'Năm sản xuất: ' => 'year',
                    'Nước sản xuất: ' => 'country_name',
                    'Firmware/ OS: ' => 'firmware',
                    'Phần mềm: ' => 'software',
                    'Phiên bản phần mềm: ' => 'version',
                    'Địa chỉ mạng: ' => 'ip_address',
                    'Thời gian lắp đặt: ' => 'installation_time',
                    'Chủng loại: ' => 'zCI_Device_Type_name',
                ];
                fillSearchRequestToExcel($event->sheet, $this->request, $columns, $inputs, 3);
                alignmentExcel($event->sheet, 'A8:BZ9', 'center');
            },
        ];
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function view(): View
    {
        $request = $this->request;
        $devices = Cache::get('automation_index_list');
        $deviceArr = [];

        if (!empty($devices)) {
            foreach ($devices as $device) {
                if (!empty($request['area']) && (empty($device['zArea']) || stripos($device['zArea'], $request['area']) === false)) {
                    continue;
                }
                if (!empty($request['td_names']) && (empty($device['zrefnr_td.zsym']) || !in_array($device['zrefnr_td.zsym'], $request['td_names']))) {
                    continue;
                }
                if (!empty($request['nl_names']) && (empty($device['zrefnr_nl.zsym']) || !in_array($device['zrefnr_nl.zsym'], $request['nl_names']))) {
                    continue;
                }
                if (!empty($request['name']) && (empty($device['name']) || stripos($device['name'], $request['name']) === false)) {
                    continue;
                }
                if (!empty($request['devices']) && (empty($device['class']) || stripos($device['class'], $request['devices']) === false)) {
                    continue;
                }
                if (!empty($request['manufacture']) && (empty($device['zManufacturer.zsym']) || stripos($device['zManufacturer.zsym'], $request['manufacture']) === false)) {
                    continue;
                }
                if (!empty($request['type']) && (empty($device['zCI_Device_Kind.zsym']) || stripos($device['zCI_Device_Kind.zsym'], $request['type']) === false)) {
                    continue;
                }
                if (!empty($request['series']) && (empty($device['serial_number']) || stripos($device['serial_number'], $request['series']) === false)) {
                    continue;
                }
                if (!empty($request['year']) && (empty($device['zYear_of_Manafacture.zsym']) || stripos($device['zYear_of_Manafacture.zsym'], $request['year']) === false)) {
                    continue;
                }
                if (!empty($request['country']) && (empty($device['zCountry']) || stripos($device['zCountry'], $request['country']) === false)) {
                    continue;
                }
                if (!empty($request['zCI_Device_Type']) && (empty($device['zCI_Device_Type']) || stripos($device['zCI_Device_Type'], $request['zCI_Device_Type']) === false)) {
                    continue;
                }
                if (!empty($request['from']) || !empty($request['to'])) {
                    if (empty($device['report'])) {
                        continue;
                    }
                    foreach ($device['report'] as $keyReport => $report) {
                        $checkTime = true;
                        if (!empty($request['from']) && empty($request['to'])) {
                            $checkTime = (int)$report['creation_date'] >= strtotime($request['from']);
                        } elseif (empty($request['from']) && !empty($request['to'])) {
                            $checkTime = (int)$report['creation_date'] <= strtotime($request['to']);
                        } elseif (!empty($request['from']) && !empty($request['to'])) {
                            $checkTime = (int)$report['creation_date'] >= strtotime($request['from']) && (int)$report['creation_date'] <= strtotime($request['to']);
                        }
                        if (!$checkTime) {
                            unset($device['report'][$keyReport]);
                        }
                    }
                    if (empty($device['report'])) {
                        continue;
                    }
                }
                if (!empty($request['firmware']) || !empty($request['software']) || !empty($request['version']) || !empty($request['ip_address']) || !empty($request['installation_time'])) {
                    if (empty($device['info'])) {
                        continue;
                    }
                    if (!empty($request['firmware']) && (empty($device['info']['zhedieuhanh.zsym']) || stripos($device['info']['zhedieuhanh.zsym'], $request['firmware']) === false)) {
                        continue;
                    }
                    if (!empty($request['software']) && (empty($device['info']['zSoftware.zsym']) || stripos($device['info']['zSoftware.zsym'], $request['software']) === false)) {
                        continue;
                    }
                    if (!empty($request['version']) && (empty($device['info']['zversion']) || $device['info']['zversion'] != $request['version'])) {
                        continue;
                    }
                    if (!empty($request['ip_address']) && (empty($device['info']['zIP']) || $device['info']['zIP'] != $request['ip_address'])) {
                        continue;
                    }
                    if (!empty($request['installation_time']) && (empty($device['info']['zTGLD']) || date('Y-m-d', $device['info']['zTGLD']) != $request['installation_time'])) {
                        continue;
                    }
                }
                array_push($deviceArr, $device);
            }
        }
        return view('automation::export', [
            'devices' => $deviceArr,
            'request' => $request
        ]);
    }
}
