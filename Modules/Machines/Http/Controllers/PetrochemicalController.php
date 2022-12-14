<?php

namespace Modules\Machines\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PetrochemicalController extends Controller
{
    /**
     * Get object by device type
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function objectByDeviceType(Request $request)
    {
        try {
            if (empty($request['type'])) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $whereClause = 'zsym IS NOT NULL';
            if (is_array($request['type'])) {
                foreach ($request['type'] as $key => $val) {
                    if ($key == 0) {
                        $whereClause .= " AND zclass IN (";
                    }
                    $whereClause .= $val;
                    if ($key < count($request['type']) - 1) {
                        $whereClause .= ",";
                    } else {
                        $whereClause .= ")";
                    }
                }
            } else {
                $whereClause .= ' AND zclass = ' . $request['type'];
            }
            $manufacturer = collect(getDataFromService('doSelect', $request['obj'], ['id', 'zsym'], $whereClause, 300))->pluck('zsym', 'id')->toArray();
            asort($manufacturer);
            return response()->json([
                'success' => true,
                'data' => $manufacturer
            ]);
        } catch (\Exception $ex) {
            Log::error('Get manufacture by device type: Fail | Reason: ' . $ex->getMessage() . '. ' . $ex->getFile() . ':' . $ex->getLine() . '| Params: '.json_encode($request->all()));

            return response()->json([
                'error' => [$ex->getMessage()]
            ]);
        }
    }

    /**
     * Get high pressure statistics report index
     *
     * @param $type
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function highPressureStatisticsReport($type)
    {
        $units = Cache::remember('allUnit', 7200, function () {
            return getDataFromService('doSelect', 'zDVQL', ['id', 'zsym'], 'zsym IS NOT NULL', 300);
        });
        sortData($units);
        $contracts = $type == 'ton-hao-may-bien-ap-phan-phoi' ? Cache::remember('allContract', 7200, function () {
            return getDataFromService('doSelect', 'svc_contract', ['id', 'sym'], 'sym IS NOT NULL', 300);
        }) : [];
        sortData($contracts);
        switch($type) {
            case 'chong-set-van':
                $classType = '3. BBTN Cao ??p - Ch???ng s??t van';
                $testItemNumber = 1;
                $title = 'B??o c??o th???ng k?? ch???ng s??t van';
                $classId = 1002788;
                break;
            case 'may-bien-dong-dien':
                $classType = '9.1. BBTN M??y bi???n d??ng ??i???n 3 cu???n 3 t??? s???;9.2. BBTN M??y bi???n d??ng ??i???n 5 cu???n 5 t??? s???';
                $testItemNumber = 1;
                $title = 'B??o c??o th???ng k?? th?? nghi???m m???u m??y bi???n d??ng ??i???n';
                $classId = 1002783;
                break;
            case 'may-bien-dien-ap':
                $classType = '8.1. BBTN M??y bi???n ??i???n ??p 4 cu???n d??y';
                $testItemNumber = 1;
                $title = 'B??o c??o th???ng k?? th?? nghi???m m???u m??y bi???n ??i???n ??p';
                $classId = 1002779;
                break;
            case 'mau-cach-dien':
                $classType = '7. BBTN Cao ??p - M???u c??ch ??i???n';
                $testItemNumber = 1;
                $title = 'B??o c??o th???ng k?? s??? l?????ng th?? nghi???m m???u c??ch ??i???n';
                $classId = 1002772;
                break;
            case 'cap':
                $classType = '19. BBTN Cao ??p - Xung s??t c??p';
                $testItemNumber = 1;
                $title = 'B??o c??o th???ng k?? th?? nghi???m m???u c??p';
                $classId = 1002776;
                break;
            default:
                $classType = '13.8. BBTN M??y bi???n ??p ph??n ph???i 1 pha;13.9. BBTN M??y bi???n ??p ph??n ph???i 3 pha';
                $testItemNumber = 2;
                $title = 'B??o c??o th???ng k?? ch???t l?????ng t???n hao MBA ph??n ph???i';
                $classId = 1002784;
        }
        $manufactures = collect(getDataFromService('doSelect', 'zManufacturer', ['id', 'zsym'], 'zsym IS NOT NULL AND zclass = ' . $classId, 300))->toArray();
        sortData($manufactures);
        return view('machines::highPressureStatistics.index', compact('units', 'manufactures', 'type', 'classType', 'testItemNumber', 'title', 'contracts'));
    }

    /**
     * Get high pressure statistics optional time
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function highPressureStatisticsOptionalTime(Request $request)
    {
        try {
            $result = highPressureStatisticsOptionalTime($request->all());

            if (!empty($result['error'])) {
                return response()->json([
                    'error' => [$result['error']]
                ]);
            }

            return response()->json($result);
        } catch (\Exception $ex) {
            Log::error('[Machines] Get high pressure statistics by optional time preview: Fail | Reason: ' . $ex->getMessage() . '. ' . $ex->getFile() . ':' . $ex->getLine() . '| Params: '.json_encode($request->all()));

            return response()->json([
                'error' => [$ex->getMessage()]
            ]);
        }
    }

    /**
     * Get high pressure statistics exactly time
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function highPressureStatisticsExactlyTime(Request $request)
    {
        try {
            if ($request->type == '7. BBTN Cao ??p - M???u c??ch ??i???n') {
                $result = highPressureStatisticsExactlyTime2($request->all());
            } else {
                $result = highPressureStatisticsExactlyTime($request->all());
            }

            if (!empty($result['error'])) {
                return response()->json([
                    'error' => [$result['error']]
                ]);
            }

            return response()->json($result);

        } catch (\Exception $ex) {
            Log::error('[Machines] Get high pressure statistics by exactly time preview: Fail | Reason: ' . $ex->getMessage() . '. ' . $ex->getFile() . ':' . $ex->getLine() . '| Params: '.json_encode($request->all()));

            return response()->json([
                'error' => [$ex->getMessage()]
            ]);
        }
    }

    /**
     * Get high pressure statistics quarterly
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function highPressureStatisticsQuarterly(Request $request)
    {
        try {
            if ($request->type == '7. BBTN Cao ??p - M???u c??ch ??i???n') {
                $result = highPressureStatisticsQuarterly2($request->all());
            } else {
                $result = highPressureStatisticsQuarterly($request->all());
            }

            if (!empty($result['error'])) {
                return response()->json([
                    'error' => [$result['error']]
                ]);
            }

            return response()->json($result);

        } catch (\Exception $ex) {
            Log::error('[Machines] Get quarterly high pressure statistics preview: Fail | Reason: ' . $ex->getMessage() . '. ' . $ex->getFile() . ':' . $ex->getLine() . '| Params: '.json_encode($request->all()));

            return response()->json([
                'error' => [$ex->getMessage()]
            ]);
        }
    }

    /**
     * Get high pressure statistics annually
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function highPressureStatisticsAnnually(Request $request)
    {
        try {
            if ($request->type == '7. BBTN Cao ??p - M???u c??ch ??i???n') {
                $result = highPressureStatisticsAnnually2($request->all());
            } else {
                $result = highPressureStatisticsAnnually($request->all());
            }

            if (!empty($result['error'])) {
                return response()->json([
                    'error' => [$result['error']]
                ]);
            }

            return response()->json($result);

        } catch (\Exception $ex) {
            Log::error('[Machines] Get annually high pressure statistics preview: Fail | Reason: ' . $ex->getMessage() . '. ' . $ex->getFile() . ':' . $ex->getLine() . '| Params: '.json_encode($request->all()));

            return response()->json([
                'error' => [$ex->getMessage()]
            ]);
        }
    }

    /**
     * Get high pressure statistics sales and quality
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function highPressureStatisticsSalesAndQuality(Request $request)
    {
        try {
            $result = highPressureStatisticsSalesAndQuality($request->all());

            if (!empty($result['error'])) {
                return response()->json([
                    'error' => [$result['error']]
                ]);
            }

            return response()->json($result);

        } catch (\Exception $ex) {
            Log::error('[Machines] Get high pressure statistics sales and quality by each manufacturer preview: Fail | Reason: ' . $ex->getMessage() . '. ' . $ex->getFile() . ':' . $ex->getLine() . '| Params: '.json_encode($request->all()));

            return response()->json([
                'error' => [$ex->getMessage()]
            ]);
        }
    }

    /**
     * Get high pressure statistics sales by manufacture
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function highPressureStatisticsSalesByManufacture(Request $request)
    {
        try {
            if ($request->type == '7. BBTN Cao ??p - M???u c??ch ??i???n') {
                $result = highPressureStatisticsSalesByManufacture2($request->all());
            } else {
                $result = highPressureStatisticsSalesByManufacture($request->all());
            }

            if (!empty($result['error'])) {
                return response()->json([
                    'error' => [$result['error']]
                ]);
            }

            return response()->json($result);

        } catch (\Exception $ex) {
            Log::error('[Machines] Get high pressure statistics sales by manufacturer preview: Fail | Reason: ' . $ex->getMessage() . '. ' . $ex->getFile() . ':' . $ex->getLine() . '| Params: '.json_encode($request->all()));

            return response()->json([
                'error' => [$ex->getMessage()]
            ]);
        }
    }

    /**
     * Get high pressure statistics quality by manufacture
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function highPressureStatisticsQualityByManufacture(Request $request)
    {
        try {
            if ($request->type == '7. BBTN Cao ??p - M???u c??ch ??i???n') {
                $result = highPressureStatisticsQualityByManufacture2($request->all());
            } else {
                $result = highPressureStatisticsQualityByManufacture($request->all());
            }

            if (!empty($result['error'])) {
                return response()->json([
                    'error' => [$result['error']]
                ]);
            }

            return response()->json($result);

        } catch (\Exception $ex) {
            Log::error('[Machines] Get high pressure statistics quantity by manufacturer preview: Fail | Reason: ' . $ex->getMessage() . '. ' . $ex->getFile() . ':' . $ex->getLine() . '| Params: '.json_encode($request->all()));

            return response()->json([
                'error' => [$ex->getMessage()]
            ]);
        }
    }

    /**
     * Get high pressure statistics by unit
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function highPressureStatisticsByUnit(Request $request)
    {
        try {
            if ($request->type == '7. BBTN Cao ??p - M???u c??ch ??i???n') {
                $result = highPressureStatisticsByUnit2($request->all());
            } else {
                $result = highPressureStatisticsByUnit($request->all());
            }

            if (!empty($result['error'])) {
                return response()->json([
                    'error' => [$result['error']]
                ]);
            }

            return response()->json($result);

        } catch (\Exception $ex) {
            Log::error('[Machines] Get high pressure statistics by unit preview: Fail | Reason: ' . $ex->getMessage() . '. ' . $ex->getFile() . ':' . $ex->getLine() . '| Params: '.json_encode($request->all()));

            return response()->json([
                'error' => [$ex->getMessage()]
            ]);
        }
    }

    /**
     * Get high pressure statistics loss quality
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function highPressureStatisticsLossQuality(Request $request)
    {
        try {
            $result = highPressureStatisticsLossQuality($request->all());

            if (!empty($result['error'])) {
                return response()->json([
                    'error' => [$result['error']]
                ]);
            }

            return response()->json($result);

        } catch (\Exception $ex) {
            Log::error('[Machines] Get high pressure statistics loss quality preview: Fail | Reason: ' . $ex->getMessage() . '. ' . $ex->getFile() . ':' . $ex->getLine() . '| Params: '.json_encode($request->all()));

            return response()->json([
                'error' => [$ex->getMessage()]
            ]);
        }
    }
}
