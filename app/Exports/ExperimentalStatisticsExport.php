<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class ExperimentalStatisticsExport implements FromView, WithEvents
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
                        'size'      =>  12,
                        'bold'      =>  true,
                    ],
                ]);
                $columns = ['C', 'D', 'E'];
                $inputs = [
                    'Ngày bắt đầu cần thống kê : ' => 'from',
                    'Ngày kết thúc cần thống kê : ' => 'to',
                    'Loại hình thí nghiệm : ' => 'ztestType_ids',
                ];
                fillSearchRequestToExcel($event->sheet, formatRequest($this->request), $columns, $inputs, 3);
                $event->sheet->getDelegate()->getStyle('A1:Z1000')->applyFromArray([
                    'font' => [
                        'name' => 'Times New Roman',
                    ],
                ]);
                $event->sheet->mergeCells('B3:I3')->getCell('B3')->setValue('Báo cáo thống kê công tác thí nghiệm');
                $event->sheet->getStyle('B3')->applyFromArray([
                    'font' => [
                        'size'      =>  20,
                        'bold'      =>  true,
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                    ]
                ]);
            },
        ];
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function view(): View
    {
        $request = $this->request;
        $result = figuresExperimentalStatisticsData($request);
        if (!empty($result['error'])) {
            return view('electromechanical::statistic_report.export', [
                'error' => $result['error']
            ]);
        }
        return view('electromechanical::statistic_report.export', [
            'dataArr' => $result['dataArr']
        ]);
    }
}
