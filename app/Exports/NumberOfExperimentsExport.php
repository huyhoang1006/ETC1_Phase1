<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class NumberOfExperimentsExport implements FromView, WithEvents
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
                $cellRange2 = 'A2:Z200'; // content
                $event->sheet->getDelegate()->getStyle($cellRange2)->getFont()->setSize(12);
                $event->sheet->getDelegate()->getStyle($cellRange)->applyFromArray([
                    'font' => [
                        'size'      =>  14,
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
                    $event->sheet->getDelegate()->getRowDimension($i)->setRowHeight(35);
                }
                // apply width column
                $event->sheet->getColumnDimension('A')->setWidth(10);
                $event->sheet->getColumnDimension('B')->setWidth(20);
                $event->sheet->getColumnDimension('C')->setWidth(35);
                $event->sheet->getColumnDimension('D')->setWidth(35);
                $event->sheet->getColumnDimension('E')->setWidth(35);
                $columns = ['B', 'C'];
                $inputs = [
                    'Năm bắt đầu: ' => 'startYear',
                    'Năm kết thúc: ' => 'endYear',
                ];
                $requestArr = formatRequest($this->request);
                fillSearchRequestToExcel($event->sheet, $requestArr, $columns, $inputs, 3);
                $event->sheet->mergeCells('A3:D3')->getCell('A3')->setValue('Báo cáo thí nghiệm thiết bị lẻ - Báo cáo số lượng thí nghiệm trên từng loại thiết bị');
                $event->sheet->getStyle('A3')->applyFromArray([
                    'font' => [
                        'size' => 18
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ]
                ]);
                $event->sheet->getStyle('B5:B1000')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            },
        ];
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function view(): View
    {
        $request = $this->request;

        $result = numberOfExperimentsReportData($request);

        if (!empty($result['error'])) {
            return view('electromechanical::numberOfExperimentsReport.export', [
                'error' => $result['error']
            ]);
        }

        return view('electromechanical::numberOfExperimentsReport.export', [
            'dataArr' => $result['dataArr']
        ]);
    }
}
