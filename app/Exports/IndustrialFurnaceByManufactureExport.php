<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class IndustrialFurnaceByManufactureExport implements FromView, WithEvents
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
                    $event->sheet->getDelegate()->getRowDimension($i)->setRowHeight(40);
                }
                // apply width column
                $event->sheet->getColumnDimension('A')->setWidth(10);
                $event->sheet->getColumnDimension('B')->setWidth(20);
                $event->sheet->getColumnDimension('C')->setWidth(35);
                $event->sheet->getColumnDimension('D')->setWidth(25);
                $event->sheet->getColumnDimension('E')->setWidth(30);
                $event->sheet->getColumnDimension('F')->setWidth(30);
                $event->sheet->getColumnDimension('G')->setWidth(30);
                $event->sheet->getColumnDimension('H')->setWidth(30);
                $event->sheet->getColumnDimension('I')->setWidth(30);
                $event->sheet->getColumnDimension('J')->setWidth(35);
                $event->sheet->getColumnDimension('K')->setWidth(30);

                $columns = ['C', 'D', 'E', 'F'];
                $inputs = [
                    'Ng??y b???t ?????u: ' => 'from',
                    'Ng??y k???t th??c: ' => 'to',
                    '????n v??? qu???n l??: ' => 'dvql_id',
                    'Tr???m/Nh?? m??y: ' => 'td_id',
                    'Ng??n l???/H??? th???ng: ' => 'nl_id',
                    'Nhi??n li???u s??? d???ng: ' => 'use_fuel',
                    'Lo???i thi???t b???: ' => 'devices',
                    'Ch???ng lo???i thi???t b???: ' => 'species',
                ];
                $requestArr = formatRequest($this->request);

                fillSearchRequestToExcel($event->sheet, $requestArr, $columns, $inputs, 3);
                // apply center content
                $event->sheet->getStyle('C5:G1000')->applyFromArray([
                    'alignment' => [
                        'horizontal'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                        'wrapText'     => TRUE
                    ],
                ]);

                $event->sheet->mergeCells('C3:G3')->getCell('C3')->setValue('B??o c??o ????nh gi?? hi???u su???t l?? c??ng nghi???p theo H??ng - Nhi??n li???u');
                $event->sheet->getStyle('C3')->applyFromArray([
                    'font' => [
                        'size' => 20,
                    ],
                    'alignment' => [
                        'horizontal'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical'     => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        'textRotation' => 0,
                        'wrapText'     => TRUE
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
        $request = $this->request->data;
        $items = getEnergyReport($request['type'], $request);

        return view('enery::industrial_furnace.export', [
            'items' => $items
        ]);
    }
}
