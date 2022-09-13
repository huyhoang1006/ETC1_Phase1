<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class DeviceListingExport implements FromView, WithEvents
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
                $columns = range('C', 'G');
                $inputs = [
                    'Đơn vị quản lý: ' => 'zrefnr_dvql_ids',
                    'Trạm: ' => 'td_name',
                    'Kiểu: ' => 'zCI_Device_Kind',
                    'Chủng loại: ' => 'zCI_Device_Type_id',
                    'Năm sản xuất: ' => 'zYear_of_Manafacture',
                    'Đơn vị quản lý điểm đo: ' => 'zdvquanlydiemdosrel',
                    'Đơn vị giao điện năng: ' => 'zdvgiaodiennangsrel',
                    'Đơn vị nhân điện năng: ' => 'zdvnhandiennangsrel',
                    'Loại điểm đo: ' => 'zloaidiemdosrel',
                    'Thiết bị: ' => 'type_name',
                ];
                $requestArr = formatRequest($this->request);
                fillSearchRequestToExcel($event->sheet, $requestArr, $columns, $inputs, 3);
            },
        ];
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function view(): View
    {
        $request = $this->request;
        $data = $request->data;
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
                'ztysobiendongstr',
                'zcapchinhxacstr',
                'zdungluonstr',
                'zgiatritustr',
            ];
        }
        $deviceArr = getDevice($request, $extendAttrs, $data);

    return view('measure::device_listing.export', [
            'deviceArr' => $deviceArr,
            'request' => $data
        ]);
    }
}
