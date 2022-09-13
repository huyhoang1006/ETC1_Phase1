<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class GasTurbineParameterTestResultsExport implements FromView
{

    public function __construct($request = [])
    {
        $this->request = $request;
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function view(): View
    {
        $request = $this->request;
        $items = getEnergyReport($request['type'], $request);

        return view('enery::gas_turbine.export', [
            'items' => $items
        ]);
    }
}
