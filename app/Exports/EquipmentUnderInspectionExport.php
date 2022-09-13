<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class EquipmentUnderInspectionExport implements FromView, WithEvents
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
                $columns = range('B', 'F');
                if($this->request['number'] == 1){
                    $inputs = [
                        'Loại thiết bị: ' => 'type_device',
                        'Ngày bắt đầu hạn kiểm định: ' => 'from',
                        'Ngày kết thúc hạn kiểm định: ' => 'to',
                        'Đơn vị quản lý: ' => 'zrefnr_dvql',
                        'Đơn vị quản lý điểm đo: ' => 'zdvquanlydiemdosrel'
                    ];
                }else{
                    $inputs = [
                        'Loại thiết bị: ' => 'type_device_id',
                        'Ngày bắt đầu hạn kiểm định: ' => 'year_from',
                        'Ngày kết thúc hạn kiểm định: ' => 'year_to',
                        'Hãng sản xuất: ' => 'manufacturer_id',
                        'Đơn vị quản lý: ' => 'zrefnr_dvql',
                    ];
                }
                $requestArr = formatRequest($this->request);
                fillSearchRequestToExcel($event->sheet, $requestArr, $columns, $inputs, 2);
            },
        ];
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function view(): View
    {
        $data = $this->request;
        // check report to export
        $report = $data['number'];
        // get data request
        $request = $data['data'];
        $items = getEquipmentUnderInspection($request['type'], $request);
        if($report == 1){
            return view('measure::equipment_under.export', compact('items'));
        }elseif($report == 2){
            return view('measure::equipment_every.export', compact('items'));
        }elseif($report == 4){
            return view('measure::incident_by_company.export', compact('items'));
        }
    }
}
