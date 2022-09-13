<?php

namespace Modules\Report\Http\Controllers;

use App\Exports\NumberOfDevicesByManufactureExport;
use App\Exports\PetrochemicalManufacturesExport;
use App\Exports\QuantityPercentageByManufacturerExport;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class PetrochemicalController extends Controller
{
    /**
     * Petrochemical manufactures report
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function petrochemicalManufactures()
    {
        return view('report::petrochemicalManufactures.index');
    }

    /**
     * Petrochemical manufactures report preview
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function petrochemicalManufacturesPreview(Request $request)
    {
        try {
            if (empty($request['types'])) {
                $request->offsetSet('types', ['Máy biến áp', 'OLTC', 'Máy cắt']);
            }

            $result = petrochemicalManufacturesData($request->all());

            if (!empty($result['error'])) {
                return response()->json([
                    'error' => [$result['error']]
                ]);
            }

            $html = view('report::petrochemicalManufactures.data')->with([
                'types' => $request['types'],
                'dataArr' => $result['dataArr']]
            )->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Exception $ex) {
            Log::error('[Petrochemical] Get petrochemical manufactures report preview: Fail | Reason: '.$ex->getMessage().'. '.$ex->getFile().':'.$ex->getLine().'| Params: '.json_encode($request->all()));

            return response()->json([
                'error' => [$ex->getMessage()]
            ]);
        }
    }

    /**
     * Petrochemical manufactures export
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function petrochemicalManufacturesExport(Request $request)
    {
        if (empty($request['types'])) {
            $request->offsetSet('types', ['Máy biến áp', 'OLTC', 'Máy cắt']);
        }

        return Excel::download(new PetrochemicalManufacturesExport($request->all()), 'Báo cáo thống kê hãng sản xuất.xlsx');
    }

    /**
     * Number of devices by manufacture report
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function numberOfDevicesByManufactureReport()
    {
        return view('report::numberOfDevicesByManufactureReport.index');
    }

    /**
     * Number of devices by manufacture report preview
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function numberOfDevicesByManufactureReportPreview(Request $request)
    {
        try {
            $result = numberOfDevicesByManufactureData($request->all());

            if (!empty($result['error'])) {
                return response()->json([
                    'error' => [$result['error']]
                ]);
            }
            $html = view('report::numberOfDevicesByManufactureReport.data')->with('dataArr', $result['dataArr'])->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Exception $ex) {
            Log::error('[Petrochemical] Get number of devices by manufacture preview: Fail | Reason: '.$ex->getMessage().'. '.$ex->getFile().':'.$ex->getLine().'| Params: '.json_encode($request->all()));

            return response()->json([
                'error' => [$ex->getMessage()]
            ]);
        }
    }

    /**
     * Number of devices by manufacture export
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function numberOfDevicesByManufactureReportExport(Request $request)
    {
        if (empty($request['types'])) {
            $request->offsetSet('types', ['Máy biến áp', 'OLTC', 'Máy cắt']);
        }
        return Excel::download(new NumberOfDevicesByManufactureExport($request->all()), 'Báo cáo thống kê số lượng thiết bị của từng hãng sản xuất.xlsx');
    }

    /**
     * Quantity percentage by manufacturer report
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function quantityPercentageByManufacturerReport()
    {
        return view('report::quantityPercentageByManufacturerReport.index');
    }

    /**
     * Quantity percentage by manufacturer report preview
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function quantityPercentageByManufacturerReportPreview(Request $request)
    {
        try {
            $result = quantityPercentageByManufacturerData($request->all());

            if (!empty($result['error'])) {
                return response()->json([
                    'error' => [$result['error']]
                ]);
            }

            $html = view('report::quantityPercentageByManufacturerReport.data')->with('dataArr', $result['dataArr'])->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Exception $ex) {
            Log::error('[Petrochemical] Get quantity percentage by manufacturer preview: Fail | Reason: '.$ex->getMessage().'. '.$ex->getFile().':'.$ex->getLine().'| Params: '.json_encode($request->all()));

            return response()->json([
                'error' => [$ex->getMessage()]
            ]);
        }
    }

    /**
     * Quantity percentage by manufacturer export
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function quantityPercentageByManufacturerReportExport(Request $request)
    {
        if (empty($request['types'])) {
            $request->offsetSet('types', ['Máy biến áp', 'OLTC', 'Máy cắt']);
        }
        return Excel::download(new QuantityPercentageByManufacturerExport($request->all()), 'Báo cáo thống kê tỷ lệ theo số lượng của từng hãng sản xuất.xlsx');
    }
}
