<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class StatisticalExperimentalExport implements FromView, WithEvents
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
                $cellRange = 'A1:Z1'; // All headers
                $cellRange2 = 'A2:Z1000'; // content
                $event->sheet->getDelegate()->getStyle($cellRange2)->getFont()->setSize(12);
                $event->sheet->getDelegate()->getStyle($cellRange)->applyFromArray([
                    'font' => [
                        'size'      =>  14,
                        'bold'      =>  true
                    ]
                ]);
                // apply center content
                $event->sheet->getDelegate()->getStyle('A1:Z1000')->applyFromArray([
                    'alignment' => [
                         'horizontal'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                       'vertical'     => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                       'textRotation' => 0,
                       'wrapText'     => TRUE
                    ],
                ]);
                // apply width column
                $event->sheet->getColumnDimension('A')->setWidth(50);
                $event->sheet->getColumnDimension('B')->setWidth(25);
                $event->sheet->getColumnDimension('C')->setWidth(25);
                $event->sheet->getColumnDimension('D')->setWidth(35);

                $columns = ['A', 'B', 'C', 'D'];
                $inputs = [
                    'Ngày bắt đầu thí nghiệm: ' => 'start_date',
                    'Ngày kết thúc thí nghiệm: ' => 'end_date',
                    'Đơn vị quản lý: ' => 'dvql',
                    'Trạm: ' => 'td',
                    'Thiết bị: ' => 'deviceNames',
                ];

                $request = $this->request;
                if( !empty($request['dvql']) ){
                    $result = getDataFromService('doSelect', 'zDVQL', ['id', 'zsym'], 'id='.$request['dvql']);
                    $request['dvql'] = @$result['zsym'];
                }

                fillSearchRequestToExcel($event->sheet, $request, $columns, $inputs, 3);
                $event->sheet->mergeCells('A3:D3')->getCell('A3')->setValue('Báo cáo thống kê công tác thí nghiệm');
                $event->sheet->getStyle('A3')->applyFromArray([
                    'alignment' => [
                        'horizontal'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical'     => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        'textRotation' => 0,
                        'wrapText'     => TRUE
                    ],
                    'font' => [
                        'size'      =>  20,
                        'bold'      =>  true
                    ]
                ]);
                alignmentExcel($event->sheet, 'A5:C1000');
            },
        ];
    }
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function view(): View
    {
        $request = $this->request;
        $result = getNumberOfExperiments($request);
        return view('machines::statisticalExperimental.report', [
            'items' => $result
        ]);
    }
}
