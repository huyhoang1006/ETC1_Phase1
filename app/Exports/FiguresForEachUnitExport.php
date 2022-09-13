<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class FiguresForEachUnitExport implements FromView, WithEvents
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
                
                $cellRange = 'A1:Z2'; // All headers
                $cellRange2 = 'A3:Z200'; // content
                $event->sheet->getDelegate()->getStyle($cellRange2)->getFont()->setSize(12);
                $event->sheet->getDelegate()->getStyle($cellRange)->applyFromArray([
                    'font' => [
                        'size'      =>  12,
                        'bold'      =>  true
                    ],
                ]);
                // apply center content
                $event->sheet->getDelegate()->getStyle('A1:Z200')->applyFromArray([
                    'alignment' => [
                        'horizontal'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical'     => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        'textRotation' => 0,
                        'wrapText'     => TRUE
                    ]
                ]);
                for($i = 1; $i <= 200; $i++){
                    $event->sheet->getDelegate()->getRowDimension($i)->setRowHeight(20);
                }
                // apply width column
                $event->sheet->getColumnDimension('A')->setWidth(10);
                $event->sheet->getColumnDimension('B')->setWidth(20);
                $event->sheet->getColumnDimension('C')->setWidth(20);
                $event->sheet->getColumnDimension('D')->setWidth(25);
                $event->sheet->getColumnDimension('E')->setWidth(18);
                $event->sheet->getColumnDimension('F')->setWidth(18);
                $event->sheet->getColumnDimension('G')->setWidth(18);
                $event->sheet->getColumnDimension('H')->setWidth(18);
                $event->sheet->getColumnDimension('I')->setWidth(18);
                $event->sheet->getColumnDimension('J')->setWidth(18);
                $event->sheet->getColumnDimension('K')->setWidth(18);
                $event->sheet->getColumnDimension('L')->setWidth(18);
                $event->sheet->getColumnDimension('M')->setWidth(18);
                $event->sheet->getColumnDimension('N')->setWidth(18);

                $columns = ['B', 'C', 'D'];
                $inputs = [
                    'Năm bắt đầu thống kê: ' => 'startYear',
                    'Năm kết thúc thống kê: ' => 'endYear',
                    'Đơn vị quản lý: ' => 'zDVQL',
                ];
                $requestArr = formatRequest($this->request);
                fillSearchRequestToExcel($event->sheet, $requestArr, $columns, $inputs, 3);
                $event->sheet->mergeCells('B3:N3')->getCell('B3')->setValue('Báo cáo thống kê cáp và dây dẫn đã thí nghiệm - Báo cáo theo đơn vị sử dụng - Bảng số liệu cho từng đơn vị');
                $event->sheet->getStyle('B3')->applyFromArray([
                    'font' => [
                        'size' => 20
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ]
                ]);
                alignmentExcel($event->sheet, 'B6:B1000');
                alignmentExcel($event->sheet, 'D6:D1000');
            },
        ];
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function view(): View
    {
        $request = $this->request;

        $result = figuresForEachUnitData($request);

        if (!empty($result['error'])) {
            return view('electromechanical::figuresForEachUnit.export', [
                'error' => $result['error']
            ]);
        }

        return view('electromechanical::figuresForEachUnit.export', [
            'dataArr' => $result['dataArr']
        ]);
    }
}
