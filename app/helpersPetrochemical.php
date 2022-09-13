<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

if(!function_exists('getDataForEvaluationBoardV1')) {
    function getDataForEvaluationBoardV1 ($ids, $obj, $attr, $comparedCategories, $comparedMinMax, $sampleVersion = 2) {
        $formattedData = $groupItems = [];
        $comparedItems = getDataFromService('doSelect', 'zreportpam', [], "zre_cat = " . array_key_first($comparedCategories), 10);
        $itemArr = getPetrochemicalReportData($ids, $obj, $attr);

        foreach ($itemArr as $item) {
            foreach ($comparedItems as $comparedItem) {
                if ($comparedItem['zre_kind'] == $item['zre_kind'] && $comparedItem['zre_year'] == $item['zre_year'] && $comparedItem['zre_manu'] == $item['zre_manu'] && $comparedItem['zre_country_origin'] == $item['zre_country_origin'] && $comparedItem['zre_ratecurrent'] == $item['zre_ratecurrent'] && $comparedItem['zre_ratecurrent'] == $item['zre_ratecurrent'] && $comparedItem['zre_ratevoltage'] == $item['zre_ratevoltage'] && $comparedItem['zre_rate_short_cc'] == $item['zre_rate_short_cc']) {
                    $min = !empty($comparedItem[$comparedMinMax[0]]) ? (float)$comparedItem[$comparedMinMax[0]] : 0;
                    $max = !empty($comparedItem[$comparedMinMax[1]]) ? (float)$comparedItem[$comparedMinMax[1]] : 0;
                }
            }

            $item['min'] = $item['max'] = $item['pass'] = $item['failed'] = $item['not_info'] = '';
            $item['compared_value'] = $item[$attr[0]];
            if (!isset($min) && !isset($max)) {
                $item['not_info'] = 'x';
            } else {
                $item['min'] = $min;
                $item['max'] = $max;
                $check = 1;
                if (isset($min) && $item[$attr[0]] < $min) {
                    $check = 0;
                }
                if (isset($max) && $item[$attr[0]] > $max) {
                    $check = 0;
                }
                $item['pass'] = !empty($check) ? 'x' : '';
                $item['failed'] = empty($check) ? 'x' : '';
            }

            if (isset($min)) {
                unset($min);
            }

            if (isset($max)) {
                unset($max);
            }

            $groupItems[$item['zlaboratoryDate']][] = $item;
        }

        foreach ($groupItems as $index => $groupItem) {
            usort($groupItem, function ($a, $b) {
                return $a['zphase'] - $b['zphase'];
            });
            $comparedItems = getDataFromService('doSelect', 'zreportpam', [], "zre_cat = " . array_key_last($comparedCategories), 10);
            $borderArr = [];
            foreach ($comparedItems as $comparedItem) {
                foreach ($groupItem as $item) {
                    if ($comparedItem['zre_kind'] == $item['zre_kind'] && $comparedItem['zre_year'] == $item['zre_year'] && $comparedItem['zre_manu'] == $item['zre_manu'] && $comparedItem['zre_country_origin'] == $item['zre_country_origin'] && $comparedItem['zre_ratecurrent'] == $item['zre_ratecurrent'] && $comparedItem['zre_ratevoltage'] == $item['zre_ratevoltage'] && $comparedItem['zre_rate_short_cc'] == $item['zre_rate_short_cc']) {
                        $borderArr[$item['id']]['min'] = !empty($comparedItem[$comparedMinMax[0]]) ? (float)$comparedItem[$comparedMinMax[0]] : 0;
                        $borderArr[$item['id']]['max'] = !empty($comparedItem[$comparedMinMax[1]]) ? (float)$comparedItem[$comparedMinMax[1]] : 0;
                    }
                }
            }
            $borderArr = array_map("unserialize", array_unique(array_map("serialize", array_values($borderArr))));

            $sampleData = sampleDataForEvaluationBoardOfPetrochemicalReport2($comparedCategories, 2, $sampleVersion == 2 ? 1 : 2);

            $count = 0;
            $phase = 'A';
            $arrNumber = [];
            foreach ($sampleData as $key => $val) {
                foreach ($groupItem as $item) {
                    if ($count >= 3) {
                        continue;
                    }
                    $sampleData[$count]['result_2'] = 'Pha ' . $phase . ': ' . $item['compared_value'];
                    $sampleData[$count]['min'] = $item['min'];
                    $sampleData[$count]['max'] = $item['max'];
                    $sampleData[$count]['pass'] = $item['pass'];
                    $sampleData[$count]['failed'] = $item['failed'];
                    $sampleData[$count]['not_info'] = $item['not_info'];
                    $count++;
                    ++$phase;
                    $arrNumber[] = $item['compared_value'];
                }

                if ($key < 3) {
                    continue;
                }

                $sampleData = formatNonSimultaneousCalculatedData($sampleData, 'result_2', $key, $arrNumber[0], $arrNumber[1], $arrNumber[2], $borderArr);
            }
            $formattedData[$index] = $sampleData;
        }

        return $formattedData;
    }
}

if(!function_exists('getDataForEvaluationBoardV2')){
    function getDataForEvaluationBoardV2($ids, $obj, $attr, $comparedCategories, $comparedMinMax, $sampleVersion = 2) {
        $formattedData = $comparedCategoryArr = [];
        $itemArr = getPetrochemicalReportData($ids, $obj, $attr);

        $comparedAttr = [
            'zre_kind',
            'zre_year',
            'zre_manu',
            'zre_country_origin',
            'zre_ratecurrent',
            'zre_ratevoltage',
            'zre_rate_short_cc',
        ];

        foreach ($comparedCategories as $index => $comparedCategory) {
            $comparedCategoryArr[] = getDataFromService('doSelect', 'zreportpam', array_merge($comparedAttr, $comparedMinMax), "zre_cat = " . $index, 10);
        }

        foreach ($itemArr as $index => $item) {
            $sampleData = sampleDataForEvaluationBoardOfPetrochemicalReport2($comparedCategories, 1, $sampleVersion == 2 ? 1 : 2);

            foreach ($comparedCategoryArr as $key => $comparedCategoryGroup) {
                // Get min max value
                foreach ($comparedCategoryGroup as $comparedItem) {
                    $arrCheck = [];
                    foreach ($comparedAttr as $val) {
                        if ($comparedItem[$val] == $item[$val]) {
                            $arrCheck[] = 1;
                        } else {
                            $arrCheck[] = 0;
                        }
                    }
                    if (!in_array(0, $arrCheck)) {
                        $min = !empty($comparedItem[$comparedMinMax[0]]) ? (float)$comparedItem[$comparedMinMax[0]] : 0;
                        $max = !empty($comparedItem[$comparedMinMax[1]]) ? (float)$comparedItem[$comparedMinMax[1]] : 0;
                    }
                }

                if ($key == 0) {
                    $sampleData[$key]['result_1'] = 'Pha A: ' . (float)$item[$attr[0]] . ' - Pha B: ' . (float)$item[$attr[1]] . ' - Pha C: ' . (float)$item[$attr[2]];
                } else {
                    $sampleData[$key]['result_1'] = max((float)$item[$attr[0]] - (float)$item[$attr[1]], (float)$item[$attr[1]] - (float)$item[$attr[2]], (float)$item[$attr[2]] - (float)$item[$attr[0]]);
                }

                if (!isset($min) && !isset($max)) {
                    $sampleData[$key]['not_info'] = 'x';
                } else {
                    $sampleData[$key]['min'] = $min;
                    $sampleData[$key]['max'] = $max;

                    $check = 1;
                    if ($key == 0) {
                        if ((isset($min) && ((float)$item[$attr[0]] < $min || (float)$item[$attr[1]] < $min || (float)$item[$attr[2]] < $min)) || (isset($max) && ((float)$item[$attr[0]] > $max || (float)$item[$attr[1]] > $max || (float)$item[$attr[2]] > $max))) {
                            $check = 0;
                        }
                    } else {
                        if ((isset($min) && $sampleData[$key]['result_1'] < $min) || (isset($max) && $sampleData[$key]['result_1'] > $max)) {
                            $check = 0;
                        }
                    }
                    $sampleData[$key]['pass'] = !empty($check) ? 'x' : '';
                    $sampleData[$key]['failed'] = empty($check) ? 'x' : '';
                }

                if (isset($min)) {
                    unset($min);
                }

                if (isset($max)) {
                    unset($max);
                }
            }

            $formattedData[!empty($item['zlaboratoryDate']) ? $item['zlaboratoryDate'] : $index] = $sampleData;
        }

        return $formattedData;
    }
}

if(!function_exists('getDataForEvaluationBoardV3')){
    function getDataForEvaluationBoardV3($ids, $obj, $attr, $comparedCategories, $comparedMinMax, $sampleVersion = 2) {
        $formattedData = $groupItems = [];

        $comparedItems = getDataFromService('doSelect', 'zreportpam', [], "zre_cat = " . array_key_first($comparedCategories), 10);
        $itemArr = getPetrochemicalReportData($ids, $obj, $attr);

        foreach ($itemArr as $item) {
            foreach ($comparedItems as $comparedItem) {
                if ($comparedItem['zre_kind'] == $item['zre_kind'] && $comparedItem['zre_year'] == $item['zre_year'] && $comparedItem['zre_manu'] == $item['zre_manu'] && $comparedItem['zre_country_origin'] == $item['zre_country_origin'] && $comparedItem['zre_ratecurrent'] == $item['zre_ratecurrent'] && $comparedItem['zre_ratecurrent'] == $item['zre_ratecurrent'] && $comparedItem['zre_ratevoltage'] == $item['zre_ratevoltage'] && $comparedItem['zre_rate_short_cc'] == $item['zre_rate_short_cc']) {
                    $min = !empty($comparedItem[$comparedMinMax[0]]) ? (float)$comparedItem[$comparedMinMax[0]] : 0;
                    $max = !empty($comparedItem[$comparedMinMax[1]]) ? (float)$comparedItem[$comparedMinMax[1]] : 0;
                }
            }

            foreach ($attr as $key => $val) {
                $newItem['title'] = 'Buồng cắt ' . ($key + 1);
                $newItem['min'] = $newItem['max'] = $newItem['pass'] = $newItem['failed'] = $newItem['not_info'] = '';
                $newItem['compared_value'] = $item[$val];
                if (!isset($min) && !isset($max)) {
                    $newItem['not_info'] = 'x';
                } else {
                    $newItem['min'] = $min;
                    $newItem['max'] = $max;
                    $check = 1;
                    if (isset($min) && $item[$val] < $min) {
                        $check = 0;
                    }
                    if (isset($max) && $item[$val] > $max) {
                        $check = 0;
                    }
                    $newItem['pass'] = !empty($check) ? 'x' : '';
                    $newItem['failed'] = empty($check) ? 'x' : '';
                }
                $item['other_info'][] = $newItem;
            }

            if (isset($min)) {
                unset($min);
            }

            if (isset($max)) {
                unset($max);
            }

            $groupItems[$item['zlaboratoryDate']][] = $item;
        }

        foreach ($groupItems as $index => $groupItem) {
            $groupItemByCategory = [];
            $reorderGroupItem = [];
            foreach ($groupItem as $groupItemKey => $groupItemVal) {
                if ($groupItemKey % 3 == 0) {
                    $groupItemByCategory = [];
                }
                array_push($groupItemByCategory, $groupItemVal);
                if ($groupItemKey % 3 == 2) {
                    usort($groupItemByCategory, function ($a, $b) {
                        return $a['zphase'] - $b['zphase'];
                    });
                    $reorderGroupItem = array_merge($reorderGroupItem, $groupItemByCategory);
                }
            }
            $groupItem = $reorderGroupItem;

            $comparedItems = getDataFromService('doSelect', 'zreportpam', [], "zre_cat = " . array_key_last($comparedCategories), 10);
            $borderArr = [];
            foreach ($comparedItems as $comparedItem) {
                foreach ($groupItem as $item) {
                    if ($comparedItem['zre_kind'] == $item['zre_kind'] && $comparedItem['zre_year'] == $item['zre_year'] && $comparedItem['zre_manu'] == $item['zre_manu'] && $comparedItem['zre_country_origin'] == $item['zre_country_origin'] && $comparedItem['zre_ratecurrent'] == $item['zre_ratecurrent'] && $comparedItem['zre_ratevoltage'] == $item['zre_ratevoltage'] && $comparedItem['zre_rate_short_cc'] == $item['zre_rate_short_cc']) {
                        $borderArr[$item['id']]['min'] = !empty($comparedItem[$comparedMinMax[0]]) ? (float)$comparedItem[$comparedMinMax[0]] : 0;
                        $borderArr[$item['id']]['max'] = !empty($comparedItem[$comparedMinMax[1]]) ? (float)$comparedItem[$comparedMinMax[1]] : 0;
                    }
                }
            }
            $borderArr = array_map("unserialize", array_unique(array_map("serialize", array_values($borderArr))));

            $sampleData = sampleDataForEvaluationBoardOfPetrochemicalReport2($comparedCategories, 3, $sampleVersion == 2 ? 1 : 2);
            $count = 0;
            $phase = 'A';
            foreach ($sampleData as $key => $val) {
                foreach ($groupItem as $item) {
                    foreach ($item['other_info'] as $info) {
                        if ($count >= 6) {
                            continue;
                        }
                        $sampleData[$count]['result_3'] = 'Pha ' . $phase . ' - ' . $info['title'] . ': ' . (float)$info['compared_value'];
                        $sampleData[$count]['min'] = $info['min'];
                        $sampleData[$count]['max'] = $info['max'];
                        $sampleData[$count]['pass'] = $info['pass'];
                        $sampleData[$count]['failed'] = $info['failed'];
                        $sampleData[$count]['not_info'] = $info['not_info'];
                        $count++;
                        if ($count % 2 == 0) {
                            ++$phase;
                        }
                    }
                }

                if ($key < 6) {
                    continue;
                }

                switch ($key) {
                    case 6:
                        $firstNumber = (float) trim(explode(':', $sampleData[0]['result_3'])[1]);
                        $secondNumber = (float) trim(explode(':', $sampleData[2]['result_3'])[1]);
                        $thirdNumber = (float) trim(explode(':', $sampleData[4]['result_3'])[1]);
                        $string = 'Buồng cắt 1';
                        break;
                    default:
                        $firstNumber = (float) trim(explode(':', $sampleData[1]['result_3'])[1]);
                        $secondNumber = (float) trim(explode(':', $sampleData[3]['result_3'])[1]);
                        $thirdNumber = (float) trim(explode(':', $sampleData[5]['result_3'])[1]);
                        $string = 'Buồng cắt 2';
                }
                $sampleData = formatNonSimultaneousCalculatedData($sampleData, 'result_3', $key, $firstNumber, $secondNumber, $thirdNumber, $borderArr, $string);
            }
            $formattedData[$index] = $sampleData;
        }

        return $formattedData;
    }
}

if( !function_exists('formatNonSimultaneousCalculatedData') ){
    function formatNonSimultaneousCalculatedData($sampleData, $result, $key, $firstNumber, $secondNumber, $thirdNumber, $borderArr, $string = ''){
        $resultValue = max($firstNumber - $secondNumber, $secondNumber - $thirdNumber, $thirdNumber - $firstNumber);
        $sampleData[$key][$result] = !empty($string) ? ($string . ': ' . $resultValue) : $resultValue;
        if (count($borderArr) != 1 || (count($borderArr) == 1) && ($borderArr[0]['min'] === '' || $borderArr[0]['max'] === '')) {
            $sampleData[$key]['not_info'] = 'x';
        } else {
            $check = 1;
            if ($resultValue < $borderArr[0]['min']) {
                $check = 0;
            }
            if ($resultValue > $borderArr[0]['max']) {
                $check = 0;
            }
            $sampleData[$key]['min'] = $borderArr[0]['min'];
            $sampleData[$key]['max'] = $borderArr[0]['max'];
            $sampleData[$key]['pass'] = !empty($check) ? 'x' : '';
            $sampleData[$key]['failed'] = empty($check) ? 'x' : '';
        }

        return $sampleData;
    }
}

if( !function_exists('getPetrochemicalReportData') ){
    function getPetrochemicalReportData($ids, $obj, $attr){
        $itemArr = [];

        foreach ($ids as $id) {
            $item = getDataFromService('doSelect', 'nr', [
                'id',
                'class.type',
                'name',
                'zCI_Device',
                'zCI_Device_Kind',
                'zCI_Device_Kind.zsym',
                'zYear_of_Manafacture',
                'zYear_of_Manafacture.zsym',
                'zManufacturer',
                'zManufacturer.zsym',
                'zCountry',
                'zCountry.name',
                'zlaboratoryDate',
                'zphase',
            ], "id = U'" . $id . "'");

            $device = getDataFromService('doSelect', 'zETC_Device', [
                'id',
                'zdongdiendinhmuc',
                'zdienapdinhmuc',
                'zrate_short_cc',
            ], "id = U'" . $item['zCI_Device'] . "'");

            $extendInfo = getDataFromService('doSelect', $obj, $attr, "id = U'" . $item['id'] . "'");

            foreach ($attr as $val) {
                $item[$val] = !empty($extendInfo[$val]) ? $extendInfo[$val] : 0;
            }

            // Format item
            $item['zre_kind'] = !empty($item['zCI_Device_Kind']) ? $item['zCI_Device_Kind'] : '';
            $item['zre_year'] = !empty($item['zYear_of_Manafacture']) ? $item['zYear_of_Manafacture'] : '';
            $item['zre_manu'] = !empty($item['zManufacturer']) ? $item['zManufacturer'] : '';
            $item['zre_country_origin'] = !empty($item['zCountry']) ? $item['zCountry'] : '';
            $item['zre_ratecurrent'] = !empty($device['zdongdiendinhmuc']) ? $device['zdongdiendinhmuc'] : '';
            $item['zre_ratevoltage'] = !empty($device['zdienapdinhmuc']) ? $device['zdienapdinhmuc'] : '';
            $item['zre_rate_short_cc'] = !empty($device['zrate_short_cc']) ? $device['zrate_short_cc'] : '';
            $item['zlaboratoryDate'] = !empty($item['zlaboratoryDate']) ? date('d/m/Y', $item['zlaboratoryDate']) : '';

            array_push($itemArr, $item);
        }

        return $itemArr;
    }
}

if( !function_exists('getCuttingTimeDataForEvaluationBoard') ){
    function getCuttingTimeDataForEvaluationBoard($type, $ids){
        $formattedData = [];
        $comparedCategories = [
            '400051' => 'Thời gian cắt',
            '400052' => 'Độ không đồng thời thời gian cắt'
        ];

        switch ($type) {
            case '10.1 BBTN Máy cắt 1 buồng cắt 1 bộ truyền động':
                $groupItems = [];
                $comparedItems = getDataFromService('doSelect', 'zreportpam', [], "zre_cat = " . array_key_first($comparedCategories), 10);

                $itemArr = getPetrochemicalReportData($ids, 'zCA10_1', ['z49ott1panum', 'z53ott2panum']);

                $attrArr = [
                    ['z49ott1panum'],
                    ['z53ott2panum']
                ];

                foreach ($attrArr as $attr) {
                    foreach ($itemArr as $item) {
                        foreach ($comparedItems as $comparedItem) {
                            if ($comparedItem['zre_kind'] == $item['zre_kind'] && $comparedItem['zre_year'] == $item['zre_year'] && $comparedItem['zre_manu'] == $item['zre_manu'] && $comparedItem['zre_country_origin'] == $item['zre_country_origin'] && $comparedItem['zre_ratecurrent'] == $item['zre_ratecurrent'] && $comparedItem['zre_ratecurrent'] == $item['zre_ratecurrent'] && $comparedItem['zre_ratevoltage'] == $item['zre_ratevoltage'] && $comparedItem['zre_rate_short_cc'] == $item['zre_rate_short_cc']) {
                                $min = !empty($comparedItem['zremin_tgc']) ? $comparedItem['zremin_tgc'] : 0;
                                $max = !empty($comparedItem['zremax_tgc']) ? $comparedItem['zremax_tgc'] : 0;
                            }
                        }

                        $item['min'] = '';
                        $item['max'] = '';
                        $item['pass'] = '';
                        $item['failed'] = '';
                        $item['not_info'] = '';
                        $item['compared_value'] = $item[$attr[0]];
                        if (!isset($min) && !isset($max)) {
                            $item['not_info'] = 'x';
                        } else {
                            $item['min'] = $min;
                            $item['max'] = $max;
                            $check = 1;
                            if (isset($min) && $item[$attr[0]] < $min) {
                                $check = 0;
                            }
                            if (isset($max) && $item[$attr[0]] > $max) {
                                $check = 0;
                            }
                            $item['pass'] = !empty($check) ? 'x' : '';
                            $item['failed'] = empty($check) ? 'x' : '';
                        }

                        if (isset($min)) {
                            unset($min);
                        }

                        if (isset($max)) {
                            unset($max);
                        }

                        $groupItems[$item['zlaboratoryDate']][] = $item;
                    }
                }

                foreach ($groupItems as $index => $groupItem) {
                    $comparedItems = getDataFromService('doSelect', 'zreportpam', [], "zre_cat = " . array_key_last($comparedCategories), 10);
                    $borderArr = [];
                    foreach ($comparedItems as $comparedItem) {
                        foreach ($groupItem as $item) {
                            if ($comparedItem['zre_kind'] == $item['zre_kind'] && $comparedItem['zre_year'] == $item['zre_year'] && $comparedItem['zre_manu'] == $item['zre_manu'] && $comparedItem['zre_country_origin'] == $item['zre_country_origin'] && $comparedItem['zre_ratecurrent'] == $item['zre_ratecurrent'] && $comparedItem['zre_ratevoltage'] == $item['zre_ratevoltage'] && $comparedItem['zre_rate_short_cc'] == $item['zre_rate_short_cc']) {
                                $borderArr[$item['id']]['min'] = !empty($comparedItem['zremin_tgc']) ? $comparedItem['zremin_tgc'] : 0;
                                $borderArr[$item['id']]['max'] = !empty($comparedItem['zremax_tgc']) ? $comparedItem['zremax_tgc'] : 0;
                            }
                        }
                    }
                    $borderArr = array_map("unserialize", array_unique(array_map("serialize", array_values($borderArr))));

                    $sampleData = sampleDataForEvaluationBoardOfPetrochemicalReport('cutting_time_v2');
                    $count = 0;
                    $phase = 'A';
                    $arrNumber = [];
                    foreach ($sampleData as $key => $val) {
                        foreach ($groupItem as $item) {
                            if ($count >= 6) {
                                continue;
                            }
                            $sampleData[$count]['result_2'] = 'Pha ' . $phase . ': ' . $item['compared_value'];
                            $sampleData[$count]['min'] = $item['min'];
                            $sampleData[$count]['max'] = $item['max'];
                            $sampleData[$count]['pass'] = $item['pass'];
                            $sampleData[$count]['failed'] = $item['failed'];
                            $sampleData[$count]['not_info'] = $item['not_info'];
                            $count++;
                            ++$phase;
                            if ($count == 3) {
                                $phase = 'A';
                            }
                            $arrNumber[] = $item['compared_value'];
                        }

                        if ($key < 6) {
                            continue;
                        }

                        switch ($key) {
                            case 6:
                                $sampleData = formatNonSimultaneousCalculatedData($sampleData, 'result_2', $key, $arrNumber[0], $arrNumber[1], $arrNumber[2], $borderArr);
                                break;
                            default:
                                $sampleData = formatNonSimultaneousCalculatedData($sampleData, 'result_2', $key, $arrNumber[3], $arrNumber[4], $arrNumber[5], $borderArr);
                                break;
                        }
                    }
                    $formattedData[$index] = $sampleData;
                }
                break;
            case '10.2. BBTN Máy cắt 1 buồng cắt 3 bộ truyền động':
                foreach ($ids as $key => $id) {
                    $sampleData = sampleDataForEvaluationBoardOfPetrochemicalReport('cutting_time');

                    $item = getDataFromService('doSelect', 'nr', [
                        'id',
                        'class.type',
                        'zCI_Device',
                        'zCI_Device_Kind',
                        'zCI_Device_Kind.zsym',
                        'zYear_of_Manafacture',
                        'zYear_of_Manafacture.zsym',
                        'zManufacturer',
                        'zManufacturer.zsym',
                        'zCountry',
                        'zCountry.name',
                        'zlaboratoryDate'
                    ], "id = U'" . $id . "'");

                    $device = getDataFromService('doSelect', 'zETC_Device', [
                        'id',
                        'zdongdiendinhmuc',
                        'zdienapdinhmuc',
                        'zrate_short_cc',
                    ], "id = U'" . $item['zCI_Device'] . "'");

                    $obj = 'zCA10_2';
                    $attr = ['z49ott1panum', 'z50ott1pbnum', 'z51ott1pcnum', 'z53ott2panum', 'z53ott2pbnum', 'z54ott2pcnum'];
                    $extendInfo = getDataFromService('doSelect', $obj, $attr, "id = U'" . $item['id'] . "'");

                    if (count($extendInfo) - 1 != count($attr)) {
                        return [
                            'error' => 'Dữ liệu đầu vào không hợp lệ'
                        ];
                    }

                    // Format item
                    $formattedItem = [
                        'zre_kind' => !empty($item['zCI_Device_Kind']) ? $item['zCI_Device_Kind'] : '',
                        'zre_year' => !empty($item['zYear_of_Manafacture']) ? $item['zYear_of_Manafacture'] : '',
                        'zre_manu' => !empty($item['zManufacturer']) ? $item['zManufacturer'] : '',
                        'zre_country_origin' => !empty($item['zCountry']) ? $item['zCountry'] : '',
                        'zre_ratecurrent' => !empty($device['zdongdiendinhmuc']) ? $device['zdongdiendinhmuc'] : '',
                        'zre_ratevoltage' => !empty($device['zdienapdinhmuc']) ? $device['zdienapdinhmuc'] : '',
                        'zre_rate_short_cc' => !empty($device['zrate_short_cc']) ? $device['zrate_short_cc'] : '',
                    ];

                    $count1 = $count2 = 0;
                    $attrArr = [
                        ['z49ott1panum', 'z50ott1pbnum', 'z51ott1pcnum'],
                        ['z53ott2panum', 'z53ott2pbnum', 'z54ott2pcnum']
                    ];

                    foreach ($comparedCategories as $index => $comparedCategory) {
                        $comparedItems = getDataFromService('doSelect', 'zreportpam', [], "zre_cat = " . $index, 10);

                        foreach ($comparedItems as $comparedItem) {
                            if ($comparedItem['zre_kind'] == $formattedItem['zre_kind'] && $comparedItem['zre_year'] == $formattedItem['zre_year'] && $comparedItem['zre_manu'] == $formattedItem['zre_manu'] && $comparedItem['zre_country_origin'] == $formattedItem['zre_country_origin'] && $comparedItem['zre_ratecurrent'] == $formattedItem['zre_ratecurrent'] && $comparedItem['zre_ratevoltage'] == $formattedItem['zre_ratevoltage'] && $comparedItem['zre_rate_short_cc'] == $formattedItem['zre_rate_short_cc']) {
                                $min = !empty($comparedItem['zremin_tgc']) ? (float)$comparedItem['zremin_tgc'] : 0;
                                $max = !empty($comparedItem['zremax_tgc']) ? (float)$comparedItem['zremax_tgc'] : 0;
                            }
                        }

                        foreach ($sampleData as $val) {
                            if ($count1 <= 1) {
                                $sampleData[$count1]['result_1'] = 'Pha A: ' . $extendInfo[$attrArr[$count2][0]] . ' - Pha B: ' . $extendInfo[$attrArr[$count2][1]] . ' - Pha C: ' . $extendInfo[$attrArr[$count2][2]];
                            } else {
                                $sampleData[$count1]['result_1'] = max($extendInfo[$attrArr[$count2][0]] - $extendInfo[$attrArr[$count2][1]], $extendInfo[$attrArr[$count2][1]] - $extendInfo[$attrArr[$count2][2]], $extendInfo[$attrArr[$count2][2]] - $extendInfo[$attrArr[$count2][0]]);
                            }
                            $sampleData[$count1]['min'] = isset($min) ? $min : 0;
                            $sampleData[$count1]['max'] = isset($max) ? $max : 0;

                            if (!isset($min) && !isset($max)) {
                                $sampleData[$count1]['not_info'] = 'x';
                            } else {
                                $check = 1;
                                if ($count1 <= 1) {
                                    if (isset($min) && ($extendInfo[$attrArr[$count2][0]] < $min || $extendInfo[$attrArr[$count2][1]] < $min || $extendInfo[$attrArr[$count2][2]] < $min)) {
                                        $check = 0;
                                    }
                                    if (isset($max) && ($extendInfo[$attrArr[$count2][0]] > $max || $extendInfo[$attrArr[$count2][1]] > $max || $extendInfo[$attrArr[$count2][2]] > $max)) {
                                        $check = 0;
                                    }
                                } else {
                                    if (isset($min) && $sampleData[$count1]['result_1'] < $min) {
                                        $check = 0;
                                    }
                                    if (isset($max) && $sampleData[$count1]['result_1'] > $max) {
                                        $check = 0;
                                    }
                                }
                                $sampleData[$count1]['pass'] = !empty($check) ? 'x' : '';
                                $sampleData[$count1]['failed'] = empty($check) ? 'x' : '';
                            }

                            if ($count1 % 2 == 1) {
                                $count1++;
                                $count2 = 0;
                                break;
                            }

                            $count1++;
                            $count2++;
                        }
                    }
                    $formattedData[!empty($item['zlaboratoryDate']) ? date('d/m/Y', $item['zlaboratoryDate']) : $key] = $sampleData;
                }
                break;
            default:
                $groupItems = [];
                $comparedItems = getDataFromService('doSelect', 'zreportpam', [], "zre_cat = " . array_key_first($comparedCategories), 10);
                $itemArr = getPetrochemicalReportData($ids, 'zCA10_3', ['z44ott11num', 'z45ott12num', 'z46ott21num', 'z47ott22num']);

                $attrArr = [
                    ['z44ott11num', 'z45ott12num'],
                    ['z46ott21num', 'z47ott22num']
                ];

                foreach ($attrArr as $attr) {
                    foreach ($itemArr as $item) {
                        foreach ($comparedItems as $comparedItem) {
                            if ($comparedItem['zre_kind'] == $item['zre_kind'] && $comparedItem['zre_year'] == $item['zre_year'] && $comparedItem['zre_manu'] == $item['zre_manu'] && $comparedItem['zre_country_origin'] == $item['zre_country_origin'] && $comparedItem['zre_ratecurrent'] == $item['zre_ratecurrent'] && $comparedItem['zre_ratecurrent'] == $item['zre_ratecurrent'] && $comparedItem['zre_ratevoltage'] == $item['zre_ratevoltage'] && $comparedItem['zre_rate_short_cc'] == $item['zre_rate_short_cc']) {
                                $min = !empty($comparedItem['zremin_tgc']) ? (float)$comparedItem['zremin_tgc'] : 0;
                                $max = !empty($comparedItem['zremax_tgc']) ? (float)$comparedItem['zremax_tgc'] : 0;
                            }
                        }

                        foreach ($attr as $key => $val) {
                            $newItem['title'] = 'Buồng cắt ' . ($key + 1);
                            $newItem['min'] = '';
                            $newItem['max'] = '';
                            $newItem['pass'] = '';
                            $newItem['failed'] = '';
                            $newItem['not_info'] = '';
                            $newItem['compared_value'] = $item[$val];
                            if (!isset($min) && !isset($max)) {
                                $newItem['not_info'] = 'x';
                            } else {
                                $newItem['min'] = $min;
                                $newItem['max'] = $max;
                                $check = 1;
                                if (isset($min) && $item[$val] < $min) {
                                    $check = 0;
                                }
                                if (isset($max) && $item[$val] > $max) {
                                    $check = 0;
                                }
                                $newItem['pass'] = !empty($check) ? 'x' : '';
                                $newItem['failed'] = empty($check) ? 'x' : '';
                            }
                            $item['other_info'][] = $newItem;
                        }

                        if (isset($min)) {
                            unset($min);
                        }

                        if (isset($max)) {
                            unset($max);
                        }

                        $groupItems[$item['zlaboratoryDate']][] = $item;
                    }
                }

                foreach ($groupItems as $index => $groupItem) {
                    $groupItemByCategory = [];
                    $reorderGroupItem = [];
                    foreach ($groupItem as $groupItemKey => $groupItemVal) {
                        if ($groupItemKey % 3 == 0) {
                            $groupItemByCategory = [];
                        }
                        array_push($groupItemByCategory, $groupItemVal);
                        if ($groupItemKey % 3 == 2) {
                            usort($groupItemByCategory, function ($a, $b) {
                                return $a['zphase'] - $b['zphase'];
                            });
                            $reorderGroupItem = array_merge($reorderGroupItem, $groupItemByCategory);
                        }
                    }
                    $groupItem = $reorderGroupItem;

                    $comparedItems = getDataFromService('doSelect', 'zreportpam', [], "zre_cat = " . array_key_last($comparedCategories), 10);
                    $borderArr = [];
                    foreach ($comparedItems as $comparedItem) {
                        foreach ($groupItem as $item) {
                            if ($comparedItem['zre_kind'] == $item['zre_kind'] && $comparedItem['zre_year'] == $item['zre_year'] && $comparedItem['zre_manu'] == $item['zre_manu'] && $comparedItem['zre_country_origin'] == $item['zre_country_origin'] && $comparedItem['zre_ratecurrent'] == $item['zre_ratecurrent'] && $comparedItem['zre_ratevoltage'] == $item['zre_ratevoltage'] && $comparedItem['zre_rate_short_cc'] == $item['zre_rate_short_cc']) {
                                $borderArr[$item['id']]['min'] = !empty($comparedItem['zremin_tgc']) ? (float)$comparedItem['zremin_tgc'] : 0;
                                $borderArr[$item['id']]['max'] = !empty($comparedItem['zremax_tgc']) ? (float)$comparedItem['zremax_tgc'] : 0;
                            }
                        }
                    }
                    $borderArr = array_map("unserialize", array_unique(array_map("serialize", array_values($borderArr))));

                    $sampleData = sampleDataForEvaluationBoardOfPetrochemicalReport('cutting_time_v3');
                    $count = 0;
                    $phase = 'A';
                    foreach ($sampleData as $key => $val) {
                        foreach ($groupItem as $item) {
                            foreach ($item['other_info'] as $info) {
                                if ($count >= 12) {
                                    continue;
                                }
                                $sampleData[$count]['result_3'] = 'Pha ' . $phase . ' - ' . $info['title'] . ': ' . (float)$info['compared_value'];
                                $sampleData[$count]['min'] = $info['min'];
                                $sampleData[$count]['max'] = $info['max'];
                                $sampleData[$count]['pass'] = $info['pass'];
                                $sampleData[$count]['failed'] = $info['failed'];
                                $sampleData[$count]['not_info'] = $info['not_info'];
                                $count++;
                                if ($count % 2 == 0) {
                                    ++$phase;
                                }
                                if ($count % 6 == 0) {
                                    $phase = 'A';
                                }
                            }
                        }

                        if ($key < 12) {
                            continue;
                        }

                        switch ($key) {
                            case 12:
                                $firstNumber = (float) trim(explode(':', $sampleData[0]['result_3'])[1]);
                                $secondNumber = (float) trim(explode(':', $sampleData[2]['result_3'])[1]);
                                $thirdNumber = (float) trim(explode(':', $sampleData[4]['result_3'])[1]);
                                break;
                            case 13:
                                $firstNumber = (float) trim(explode(':', $sampleData[1]['result_3'])[1]);
                                $secondNumber = (float) trim(explode(':', $sampleData[3]['result_3'])[1]);
                                $thirdNumber = (float) trim(explode(':', $sampleData[5]['result_3'])[1]);
                                break;
                            case 14:
                                $firstNumber = (float) trim(explode(':', $sampleData[6]['result_3'])[1]);
                                $secondNumber = (float) trim(explode(':', $sampleData[8]['result_3'])[1]);
                                $thirdNumber = (float) trim(explode(':', $sampleData[10]['result_3'])[1]);
                                break;
                            default:
                                $firstNumber = (float) trim(explode(':', $sampleData[7]['result_3'])[1]);
                                $secondNumber = (float) trim(explode(':', $sampleData[9]['result_3'])[1]);
                                $thirdNumber = (float) trim(explode(':', $sampleData[11]['result_3'])[1]);
                                break;
                        }
                        $sampleData = formatNonSimultaneousCalculatedData($sampleData, 'result_3', $key, $firstNumber, $secondNumber, $thirdNumber, $borderArr, $key % 2 == 0 ? 'Buồng cắt 1' : 'Buồng cắt 2');
                    }
                    $formattedData[$index] = $sampleData;
                }
        }

        return $formattedData;
    }
}

if( !function_exists('sampleDataForEvaluationBoardOfPetrochemicalReport2') ){
    function sampleDataForEvaluationBoardOfPetrochemicalReport2($category, $version, $type = 1)
    {
        $arr = [];
        $baseItem = [
            'category' => '',
            'result_1' => '',
            'result_2' => '',
            'result_3' => '',
            'min' => '',
            'max' => '',
            'pass' => '',
            'failed' => '',
            'not_info' => ''
        ];

        switch ($version) {
            case 1:
                $numberItem1 = 1;
                if ($type == 1) {
                    $numberItem2 = 1;
                }
                break;
            case 2:
                $numberItem1 = 3;
                if ($type == 1) {
                    $numberItem2 = 1;
                }
                break;
            default:
                $numberItem1 = 6;
                if ($type == 1) {
                    $numberItem2 = 2;
                }
                break;
        }

        for ($i = 0; $i < $numberItem1; $i++) {
            $arrItem = $baseItem;
            $arrItem['category'] = reset($category);

            array_push($arr, $arrItem);
        }

        if (!empty($numberItem2)) {
            for ($i = 0; $i < $numberItem2; $i++) {
                $arrItem = $baseItem;
                $arrItem['category'] = end($category);

                array_push($arr, $arrItem);
            }
        }

        return $arr;
    }
}

if( !function_exists('sampleDataForEvaluationBoardOfPetrochemicalReport3') ){
    function sampleDataForEvaluationBoardOfPetrochemicalReport3($category, $version)
    {
        $arr = [];
        $baseItem = [
            'category' => '',
            'result_1' => '',
            'result_2' => '',
            'result_3' => '',
            'min' => '',
            'max' => '',
            'pass' => '',
            'failed' => '',
            'not_info' => ''
        ];

        switch ($version) {
            case 1:
                $numberItem1 = 1;
                break;
            case 2:
                $numberItem1 = 3;
                break;
            default:
                $numberItem1 = 6;
                break;
        }

        for ($i = 0; $i < $numberItem1; $i++) {
            $arrItem = $baseItem;
            $arrItem['category'] = reset($category);

            array_push($arr, $arrItem);
        }

        return $arr;
    }
}

if( !function_exists('sampleDataForEvaluationBoardOfPetrochemicalReport') ){
    function sampleDataForEvaluationBoardOfPetrochemicalReport($type)
    {
        if ($type == 'cutting_time') {
            return [
                [
                    'category' => 'Thời gian cắt cuộn cắt 1 Tc1',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
                [
                    'category' => 'Thời gian cắt cuộn cắt 2 Tc2',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
                [
                    'category' => 'Độ không đồng thời thời gian cắt 1',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
                [
                    'category' => 'Độ không đồng thời thời gian cắt 2',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
            ];
        }

        if ($type == 'cutting_time_v2') {
            return [
                [
                    'category' => 'Thời gian cắt cuộn cắt 1 Tc1',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
                [
                    'category' => 'Thời gian cắt cuộn cắt 1 Tc1',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
                [
                    'category' => 'Thời gian cắt cuộn cắt 1 Tc1',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
                [
                    'category' => 'Thời gian cắt cuộn cắt 2 Tc2',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
                [
                    'category' => 'Thời gian cắt cuộn cắt 2 Tc2',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
                [
                    'category' => 'Thời gian cắt cuộn cắt 2 Tc2',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
                [
                    'category' => 'Độ không đồng thời thời gian cắt 1',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
                [
                    'category' => 'Độ không đồng thời thời gian cắt 2',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
            ];
        }

        if ($type == 'cutting_time_v3') {
            return [
                [
                    'category' => 'Thời gian cắt cuộn cắt 1 Tc1',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
                [
                    'category' => 'Thời gian cắt cuộn cắt 1 Tc1',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
                [
                    'category' => 'Thời gian cắt cuộn cắt 1 Tc1',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
                [
                    'category' => 'Thời gian cắt cuộn cắt 1 Tc1',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
                [
                    'category' => 'Thời gian cắt cuộn cắt 1 Tc1',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
                [
                    'category' => 'Thời gian cắt cuộn cắt 1 Tc1',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
                [
                    'category' => 'Thời gian cắt cuộn cắt 2 Tc2',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
                [
                    'category' => 'Thời gian cắt cuộn cắt 2 Tc2',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
                [
                    'category' => 'Thời gian cắt cuộn cắt 2 Tc2',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
                [
                    'category' => 'Thời gian cắt cuộn cắt 2 Tc2',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
                [
                    'category' => 'Thời gian cắt cuộn cắt 2 Tc2',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
                [
                    'category' => 'Thời gian cắt cuộn cắt 2 Tc2',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
                [
                    'category' => 'Độ không đồng thời thời gian cắt 1',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
                [
                    'category' => 'Độ không đồng thời thời gian cắt 1',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
                [
                    'category' => 'Độ không đồng thời thời gian cắt 2',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
                [
                    'category' => 'Độ không đồng thời thời gian cắt 2',
                    'result_1' => '',
                    'result_2' => '',
                    'result_3' => '',
                    'min' => '',
                    'max' => '',
                    'pass' => '',
                    'failed' => '',
                    'not_info' => ''
                ],
            ];
        }
    }
}

if (!function_exists('getReportByCondition')) {
    function getReportByCondition($request, $attr = [], $validateTime = true)
    {
        if ($validateTime) {
            if (empty($request['startTime']) || empty($request['endTime'])) {
                return [
                    'error' => ['Thời gian cần thống kê không được để trống']
                ];
            }

            if ($request['endTime'] < $request['startTime']) {
                return [
                    'error' => ['Thời gian kết thúc phải lớn hơn hoặc bằng thời gian bắt đầu']
                ];
            }
        }

        // Get report
        $whereClause = "class.zetc_type = 1 AND zManufacturer.zsym IS NOT NULL AND zrefnr_dvql IS NOT NULL";

        if (!empty($request['type'])) {
            $typeArr = explode(';', $request['type']);
            foreach ($typeArr as $key => $val) {
                if ($key == 0) {
                    $whereClause .= " AND class.type IN (";
                }
                $whereClause .= "'". trim($val) ."'";
                if ($key < count($typeArr) - 1) {
                    $whereClause .= ",";
                } else {
                    $whereClause .= ")";
                }
            }
        }

        if (!empty($request['startTime'])) {
            $whereClause .= " AND zlaboratoryDate >= " . strtotime($request['startTime']);
        }

        if (!empty($request['endTime'])) {
            $whereClause .= " AND zlaboratoryDate <= " . strtotime($request['endTime']);
        }

        if(!empty($request['zManufacturer_ids'])){
            foreach ($request['zManufacturer_ids'] as $key => $val) {
                if ($key == 0) {
                    $whereClause .= " AND zManufacturer IN (";
                }
                $whereClause .= "'". $val ."'";
                if ($key < count($request['zManufacturer_ids']) - 1) {
                    $whereClause .= ",";
                } else {
                    $whereClause .= ")";
                }
            }
        }

        if(!empty($request['zrefnr_dvql_ids'])){
            foreach ($request['zrefnr_dvql_ids'] as $key => $val) {
                if ($key == 0) {
                    $whereClause .= " AND zrefnr_dvql IN (";
                }
                $whereClause .= "'". $val ."'";
                if ($key < count($request['zrefnr_dvql_ids']) - 1) {
                    $whereClause .= ",";
                } else {
                    $whereClause .= ")";
                }
            }
        }

        if(!empty($request['zContract_Number'])){
            foreach ($request['zContract_Number'] as $key => $val) {
                if ($key == 0) {
                    $whereClause .= " AND zContract_Number IN (";
                }
                $whereClause .= "'". $val ."'";
                if ($key < count($request['zContract_Number']) - 1) {
                    $whereClause .= ",";
                } else {
                    $whereClause .= ")";
                }
            }
        }

        if (empty($attr)) {
            $attr = [
                'id',
                'name',
                'zCI_Device',
                'zCI_Device.name',
                'zManufacturer',
                'zManufacturer.zsym',
                'zlaboratoryDate',
                'zrefnr_dvql',
                'zrefnr_dvql.zsym',
                'class',
                'class.type',
                'zCI_Device.zCI_Device_Type',
                'zCI_Device.zCI_Device_Type.zsym',
            ];
        }

        $reports = getDataFromService('doSelect', 'nr', $attr, $whereClause, 100);

        if (empty($reports)) {
            return [
                'error' => ['Không có dữ liệu thống kê thỏa mãn']
            ];
        }

        return $reports;
    }
}

if (!function_exists('getHighPressureStatisticsOptionalTimeData')) {
    function getHighPressureStatisticsOptionalTimeData($request, $optionalTime = true, $byManufacture = false)
    {
        $reports = getReportByCondition($request);

        if (!empty($reports['error'])) {
            return $reports;
        }

        usort($reports, function ($a, $b) {
            return $a['zlaboratoryDate'] - $b['zlaboratoryDate'];
        });

        $arr = $deviceArr = [];
        foreach ($reports as $report) {
            if (in_array($report['class.type'], ['13.8. BBTN Máy biến áp phân phối 1 pha', '13.9. BBTN Máy biến áp phân phối 3 pha']) && in_array($report['zCI_Device'], $deviceArr)) {
                return [
                    'error' => ['Tồn tại thiết bị có nhiều hơn 1 lần làm biên bản']
                ];
            }

            array_push($deviceArr, $report['zCI_Device']);

            $testItemArr = $extendAttrArr = [];
            // Detect obj and attr by report's class type
            switch ($report['class.type']) {
                case '3. BBTN Cao Áp - Chống sét van':
                    $obj = 'zCA3';
                    $testItemArr[] = ['zCheck_IS'];
                    $report['attrArr'][] = $extendAttrArr[] = ['zCheck_IS_Result'];
                    break;
                case '7. BBTN Cao Áp - Mẫu cách điện':
                    $obj = 'zCA7';
                    $testItemArr[] = ['zVD', 'zMT', 'zDLIT', 'zZTI', 'zSDT', 'zTST', 'zDWPFWSVT', 'zPWSVT'];
                    $report['attrArr'][] = $extendAttrArr[] = ['zVD_Result', 'zMT_Result', 'zDLIT_Result', 'zZTI_Result', 'zSDT_Result', 'zTST_Result', 'zDWPFWSVT_Result', 'zPWSVT_Result'];
                    break;
                case '8.1. BBTN Máy biến điện áp 4 cuộn dây':
                    $obj = 'zCA8_1';
                    $testItemArr[] = ['z13dpdcbchk', 'z12ddtnchk', 'z11tnsxchk'];
                    $report['attrArr'][] = $extendAttrArr[] = ['z206pdmchk', 'z193trtchk', 'z176litck'];
                    break;
                case '9.1. BBTN Máy biến dòng điện 3 cuộn 3 tỷ số':
                    $obj = 'zCA9_1';
                    $testItemArr[] = ['z13pdmchk', 'z10tfzchk', 'z11litchk'];
                    $report['attrArr'][] = $extendAttrArr[] = ['z382pdmchk', 'z353dacucchk', 'z369litchk'];
                    break;
                case '9.2. BBTN Máy biến dòng điện 5 cuộn 5 tỷ số':
                    $obj = 'zCA9_2';
                    $testItemArr[] = ['z13check', 'z10check', 'z11check'];
                    $report['attrArr'][] = $extendAttrArr[] = ['z909check', 'z863check', 'z884check'];
                    break;
                case '13.8. BBTN Máy biến áp phân phối 1 pha':
                    $obj = 'zCA13_8';
                    $testItemArr[] = ['z4check', 'z7check'];
                    $testItemArr[] = ['z11check', 'z10check', 'z9check', 'z12check'];
                    $report['attrArr'][] = $extendAttrArr[] = ['z44check', 'z86check'];
                    $report['attrArr'][] = $extendAttrArr[] = ['z121check', 'z109check', 'z96check', 'z126check'];
                    break;
                case '19. BBTN Cao Áp - Xung sét cáp':
                    $obj = 'zCA19';
                    $testItemArr[] = ['zLIV_WST'];
                    $report['attrArr'][] = $extendAttrArr[] = ['zLIV_WST_Re'];
                    break;
                default:
                    $obj = 'zCA13_9';
                    $testItemArr[] = ['z4nlclm1chk', 'z8scvllmchk'];
                    $testItemArr[] = ['z12trtchk', 'z11litchk', 'z10achvwtchk', 'z13nlmchk'];
                    $report['attrArr'][] = $extendAttrArr[] = ['z42nlclmchk', 'z151scvllmtchk'];
                    $report['attrArr'][] = $extendAttrArr[] = ['z212trtchk', 'z196litchk', 'z185achvwtchk', 'z220nlmchk'];
            }

            // Get all extend attribute
            $allExtendAttrArr = [];
            foreach ($testItemArr as $val) {
                $allExtendAttrArr = array_merge($allExtendAttrArr, $val);
            }

            foreach ($report['attrArr'] as $val) {
                $allExtendAttrArr = array_merge($allExtendAttrArr, $val);
            }

            $extendInfo = getDataFromService('doSelect', $obj, $allExtendAttrArr, "id = U'" . $report['id'] . "'");

            // Get test unit and validate it, if report don't have any test unit, skip
            foreach ($testItemArr as $attrArr) {
                foreach ($attrArr as $attr) {
                    $report['testItem'][$attr] = isset($extendInfo[$attr]) ? $extendInfo[$attr] : '';
                }
            }

            $checkingTestItems = array_unique(array_values($report['testItem']));

            if (count($checkingTestItems) == 1 && $checkingTestItems[0] === '') {
                continue;
            }

            // Get result of all test unit
            $testItemArr = array_values($report['testItem']);
            $count = 0;
            foreach ($extendAttrArr as $attrArr) {
                foreach ($attrArr as $attr) {
                    if (empty($testItemArr[$count])) {
                        $count++;
                        continue;
                    }
                    $report['extendInfo'][$attr] = isset($extendInfo[$attr]) ? $extendInfo[$attr] : '';
                    $count++;
                }
            }

            if ($byManufacture) {
                $arr[$report['zManufacturer.zsym'] . '_' . date('Y', $report['zlaboratoryDate'])][] = $report;
                continue;
            }

            if ($optionalTime) {
                switch ($request['timeType']) {
                    case 1:
                        $arr[$report['zManufacturer.zsym'] . '_Tháng ' . date('m/Y', $report['zlaboratoryDate']) . '_' . $report['zrefnr_dvql.zsym']][] = $report;
                        break;
                    case 2:
                        $quarter = ceil(date('m', $report['zlaboratoryDate']) / 3);
                        $year = date('Y', $report['zlaboratoryDate']);
                        $arr[$report['zManufacturer.zsym'] . '_Quý ' . $quarter . '/' . $year . '_' . $report['zrefnr_dvql.zsym']][] = $report;
                        break;
                    default:
                        $arr[$report['zManufacturer.zsym'] . '_Năm ' . date('Y', $report['zlaboratoryDate']) . '_' . $report['zrefnr_dvql.zsym']][] = $report;
                }
                continue;
            }

            $arr['Năm ' . date('Y', $report['zlaboratoryDate'])][] = $report;
        }

        // Calculate statistics and format data
        $dataArr = [];
        $count = 1;
        foreach ($arr as $key => $val) {
            $item['order'] = $count;
            if ($optionalTime) {
                $item['zManufacturer'] = explode('_', $key)[0];
                $item['time'] = explode('_', $key)[1];
                $item['zrefnr_dvql'] = explode('_', $key)[2];
            } else {
                $item['time'] = $key;
            }

            // Calculate pass and failed number item
            for ($i = 1; $i <= $request['testItemNumber']; $i++) {
                $item['rate_' . $i] = $item['total_' . $i] = $item['failed_' . $i] = $item['pass_' . $i] = 0;
                foreach ($val as $index) {
                    $check = 1;
                    $checkGroupItem = [];
                    foreach ($index['attrArr'][$i - 1] as $attr) {
                        array_push($checkGroupItem, empty($index['extendInfo'][$attr]) ? 0 : 1);
                        if (isset($index['extendInfo'][$attr]) && $index['extendInfo'][$attr] != 1) {
                            $check = 0;
                        }
                    }

                    if (!in_array(1, $checkGroupItem)) {
                        $check = 0;
                    }

                    if ($check == 1) {
                        $item['pass_' . $i] += 1;
                    } else {
                        $item['failed_' . $i] += 1;
                    }
                    $item['total_' . $i] += 1;
                }
                $item['rate_' . $i] = ($item['total_' . $i] != 0) ? round($item['failed_' . $i] / $item['total_' . $i] * 100, 2) : 0;
            }

            array_push($dataArr, $item);
            $count++;
        }

        return $dataArr;
    }
}

if (!function_exists('getHighPressureStatisticsOptionalTimeData2')) {
    function getHighPressureStatisticsOptionalTimeData2($request, $optionalTime = true, $byManufacture = false)
    {
        $reports = getReportByCondition($request);

        if (!empty($reports['error'])) {
            return $reports;
        }

        usort($reports, function ($a, $b) {
            $a['zCI_Device.zCI_Device_Type.zsym'] = ($a['zCI_Device.zCI_Device_Type'] == 400074 || $a['zCI_Device.zCI_Device_Type'] == 400078) ? 'Gốm + Sứ' : $a['zCI_Device.zCI_Device_Type.zsym'];
            $b['zCI_Device.zCI_Device_Type.zsym'] = ($b['zCI_Device.zCI_Device_Type'] == 400074 || $b['zCI_Device.zCI_Device_Type'] == 400078) ? 'Gốm + Sứ' : $b['zCI_Device.zCI_Device_Type.zsym'];
            return $a['zCI_Device.zCI_Device_Type.zsym'] <=> $b['zCI_Device.zCI_Device_Type.zsym'];
        });

        $arr = $deviceArr = [];
        foreach ($reports as $report) {
            if (in_array($report['zCI_Device'], $deviceArr)) {
                return [
                    'error' => ['Tồn tại thiết bị có nhiều hơn 1 lần làm biên bản']
                ];
            }

            array_push($deviceArr, $report['zCI_Device']);

            $testItemArr = $extendAttrArr = [];
            // Detect obj and attr by report's class type
            $obj = 'zCA7';
            $testItemArr[] = ['zVD', 'zMT', 'zDLIT', 'zZTI', 'zSDT', 'zTST', 'zDWPFWSVT', 'zPWSVT'];
            $report['attrArr'][] = $extendAttrArr[] = ['zVD_Result', 'zMT_Result', 'zDLIT_Result', 'zZTI_Result', 'zSDT_Result', 'zTST_Result', 'zDWPFWSVT_Result', 'zPWSVT_Result'];

            // Get all extend attribute
            $allExtendAttrArr = [];
            foreach ($testItemArr as $val) {
                $allExtendAttrArr = array_merge($allExtendAttrArr, $val);
            }

            foreach ($report['attrArr'] as $val) {
                $allExtendAttrArr = array_merge($allExtendAttrArr, $val);
            }

            $extendInfo = getDataFromService('doSelect', $obj, $allExtendAttrArr, "id = U'" . $report['id'] . "'");

            // Get test unit and validate it, if report don't have any test unit, skip
            foreach ($testItemArr as $attrArr) {
                foreach ($attrArr as $attr) {
                    $report['testItem'][$attr] = isset($extendInfo[$attr]) ? $extendInfo[$attr] : '';
                }
            }

            $checkingTestItems = array_unique(array_values($report['testItem']));

            if (count($checkingTestItems) == 1 && $checkingTestItems[0] === '') {
                continue;
            }

            // Get result of all test unit
            $testItemArr = array_values($report['testItem']);
            $count = 0;
            foreach ($extendAttrArr as $attrArr) {
                foreach ($attrArr as $attr) {
                    if (empty($testItemArr[$count])) {
                        $count++;
                        continue;
                    }
                    $report['extendInfo'][$attr] = isset($extendInfo[$attr]) ? $extendInfo[$attr] : '';
                    $count++;
                }
            }

            if ($byManufacture) {
                $arr[($report['zCI_Device.zCI_Device_Type'] == 400074 || $report['zCI_Device.zCI_Device_Type'] == 400078) ? 'Gốm + Sứ' : $report['zCI_Device.zCI_Device_Type.zsym']][$report['zManufacturer.zsym'] . '_' . date('Y', $report['zlaboratoryDate'])][] = $report;
                continue;
            }

            if ($optionalTime) {
                switch ($request['timeType']) {
                    case 1:
                        $arr[$report['zManufacturer.zsym'] . '_Tháng ' . date('m/Y', $report['zlaboratoryDate']) . '_' . $report['zrefnr_dvql.zsym']][] = $report;
                        break;
                    case 2:
                        $quarter = ceil(date('m', $report['zlaboratoryDate']) / 3);
                        $year = date('Y', $report['zlaboratoryDate']);
                        $arr[$report['zManufacturer.zsym'] . '_Quý ' . $quarter . '/' . $year . '_' . $report['zrefnr_dvql.zsym']][] = $report;
                        break;
                    default:
                        $arr[($report['zCI_Device.zCI_Device_Type'] == 400074 || $report['zCI_Device.zCI_Device_Type'] == 400078) ? 'Gốm + Sứ' : $report['zCI_Device.zCI_Device_Type.zsym']][$report['zManufacturer.zsym'] . '_Năm ' . date('Y', $report['zlaboratoryDate']) . '_' . $report['zrefnr_dvql.zsym']][] = $report;
                }
                continue;
            }

            $arr[($report['zCI_Device.zCI_Device_Type'] == 400074 || $report['zCI_Device.zCI_Device_Type'] == 400078) ? 'Gốm + Sứ' : $report['zCI_Device.zCI_Device_Type.zsym']]['Năm ' . date('Y', $report['zlaboratoryDate'])][] = $report;
        }

        // Calculate statistics and format data
        $groupArr = [];
        foreach ($arr as $groupKey => $group) {
            $dataArr = [];
            $count = 1;
            foreach ($group as $key => $val) {
                $item['order'] = $count;
                if ($optionalTime) {
                    $item['zManufacturer'] = explode('_', $key)[0];
                    $item['time'] = explode('_', $key)[1];
                    $item['zrefnr_dvql'] = explode('_', $key)[2];
                } else {
                    $item['time'] = $key;
                }

                // Calculate pass and failed number item
                for ($i = 1; $i <= $request['testItemNumber']; $i++) {
                    $item['rate_' . $i] = $item['total_' . $i] = $item['failed_' . $i] = $item['pass_' . $i] = 0;
                    foreach ($val as $index) {
                        $check = 1;
                        $checkGroupItem = [];
                        foreach ($index['attrArr'][$i - 1] as $attr) {
                            array_push($checkGroupItem, empty($index['extendInfo'][$attr]) ? 0 : 1);
                            if (isset($index['extendInfo'][$attr]) && $index['extendInfo'][$attr] != 1) {
                                $check = 0;
                            }
                        }

                        if (!in_array(1, $checkGroupItem)) {
                            $check = 0;
                        }

                        if ($check == 1) {
                            $item['pass_' . $i] += 1;
                        } else {
                            $item['failed_' . $i] += 1;
                        }
                        $item['total_' . $i] += 1;
                    }
                    $item['rate_' . $i] = ($item['total_' . $i] != 0) ? round($item['failed_' . $i] / $item['total_' . $i] * 100, 2) : 0;
                }

                array_push($dataArr, $item);
                $count++;
            }
            $groupArr[$groupKey] = $dataArr;
        }

        return $groupArr;
    }
}

if (!function_exists('highPressureStatisticsOptionalTime')) {
    function highPressureStatisticsOptionalTime($request)
    {
        $dataArr = getHighPressureStatisticsOptionalTimeData($request);

        if (!empty($dataArr['error'])) {
            return $dataArr;
        }

        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);

        // Read Template Excel
        $template = storage_path('templates_excel/thong_ke_cao_ap/' . $request['directoryName'] . '/bao-cao-theo-thoi-gian-tuy-chon.xlsx');
        $spreadsheet = $reader->load($template);

        // Sheet data
        $statisticsSheet = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THONG_KE");

        $row = 6;
        foreach ($dataArr as $val) {
            $col = 'A';
            foreach ($val as $item) {
                $statisticsSheet->getCell($col . $row)->setValue($item);
                $statisticsSheet->getStyle($col . $row)->getAlignment()->setWrapText(true);
                ++$col;
            }
            $row++;
        }

        // Calculate for total row
        $statisticsSheet->getCell('D' . $row)->setValue('Tổng');

        $col = 'E';
        for ($i = 1; $i <= $request['testItemNumber']; $i++) {
            $statisticsSheet->getCell($col . $row)->setValue(array_sum(array_column($dataArr, 'pass_' . $i)));
            ++$col;
            $statisticsSheet->getCell($col . $row)->setValue(array_sum(array_column($dataArr, 'failed_' . $i)));
            ++$col;
            $statisticsSheet->getCell($col . $row)->setValue(array_sum(array_column($dataArr, 'total_' . $i)));
            ++$col;
            $statisticsSheet->getCell($col . $row)->setValue((array_sum(array_column($dataArr, 'total_' . $i)) != 0) ? round(array_sum(array_column($dataArr, 'failed_' . $i)) / array_sum(array_column($dataArr, 'total_' . $i)) * 100, 2) : 0);
            ++$col;
        }

        $statisticsSheet->getStyle('D' . $row . ':L' . $row)->applyFromArray([
            'font' => [
                'bold' => true,
            ]
        ]);

        $url = writeExcel($spreadsheet, 'bao-cao-thong-ke-'. $request['directoryName'] .'-theo-thoi-gian-tuy-chon-' . time() . '.xlsx');

        return [
            'success' => true,
            'url' => $url
        ];
    }
}

if (!function_exists('getExtendInfoFromReport')) {
    function getExtendInfoFromReport($report)
    {
        switch ($report['class.type']) {
            case '3. BBTN Cao Áp - Chống sét van':
                $obj = 'zCA3';
                $testItemArr = ['zCheck_IS'];
                $report['attrArr'] = $extendAttrArr = ['zCheck_IS_Result'];
                break;
            case '7. BBTN Cao Áp - Mẫu cách điện':
                $obj = 'zCA7';
                $testItemArr = ['zVD', 'zMT', 'zDLIT', 'zZTI', 'zSDT', 'zTST', 'zDWPFWSVT', 'zPWSVT'];
                $report['attrArr'] = $extendAttrArr = ['zVD_Result', 'zMT_Result', 'zDLIT_Result', 'zZTI_Result', 'zSDT_Result', 'zTST_Result', 'zDWPFWSVT_Result', 'zPWSVT_Result'];
                break;
            case '8.1. BBTN Máy biến điện áp 4 cuộn dây':
                $obj = 'zCA8_1';
                $testItemArr = ['z13dpdcbchk', 'z12ddtnchk', 'z11tnsxchk'];
                $report['attrArr'] = $extendAttrArr = ['z206pdmchk', 'z193trtchk', 'z176litck'];
                break;
            case '9.1. BBTN Máy biến dòng điện 3 cuộn 3 tỷ số':
                $obj = 'zCA9_1';
                $testItemArr = ['z13pdmchk', 'z10tfzchk', 'z11litchk'];
                $report['attrArr'] = $extendAttrArr = ['z382pdmchk', 'z353dacucchk', 'z369litchk'];
                break;
            case '9.2. BBTN Máy biến dòng điện 5 cuộn 5 tỷ số':
                $obj = 'zCA9_2';
                $testItemArr = ['z13check', 'z10check', 'z11check'];
                $report['attrArr'] = $extendAttrArr = ['z909check', 'z863check', 'z884check'];
                break;
            case '13.8. BBTN Máy biến áp phân phối 1 pha':
                $obj = 'zCA13_8';
                $testItemArr = ['z4check', 'z7check'];
                $report['attrArr'] = $extendAttrArr = ['z44check', 'z86check'];
                break;
            case '19. BBTN Cao Áp - Xung sét cáp':
                $obj = 'zCA19';
                $testItemArr = ['zLIV_WST'];
                $report['attrArr'] = $extendAttrArr = ['zLIV_WST_Re'];
                break;
            default:
                $obj = 'zCA13_9';
                $testItemArr = ['z4nlclm1chk', 'z8scvllmchk'];
                $report['attrArr'] = $extendAttrArr = ['z42nlclmchk', 'z151scvllmtchk'];
        }

        $extendInfo = getDataFromService('doSelect', $obj, array_merge($testItemArr, $extendAttrArr), "id = U'" . $report['id'] . "'");

        // Get test unit and validate it, if report don't have any test unit, skip
        foreach ($testItemArr as $attr) {
            $report['testItem'][$attr] = isset($extendInfo[$attr]) ? $extendInfo[$attr] : '';
        }

        $checkingTestItems = array_unique(array_values($report['testItem']));

        if (count($checkingTestItems) == 1 && $checkingTestItems[0] === '') {
            $report['error'] = 1;
            return $report;
        }

        // Get result of all test unit
        $testItemArr = array_values($report['testItem']);
        $count = 0;
        foreach ($extendAttrArr as $attr) {
            if (empty($testItemArr[$count])) {
                $count++;
                continue;
            }
            $report['extendInfo'][$attr] = isset($extendInfo[$attr]) ? $extendInfo[$attr] : '';
            $count++;
        }

        $report['result'] = 1;
        foreach ($report['attrArr'] as $attr) {
            if (isset($report['extendInfo'][$attr]) && $report['extendInfo'][$attr] != 1) {
                $report['result'] = 0;
            }
        }

        return $report;
    }
}

if (!function_exists('highPressureStatisticsExactlyTime')) {
    function highPressureStatisticsExactlyTime($request)
    {
        $startTime = $request['startTime'];
        $endTime = $request['endTime'];

        $request['endTime'] = !empty($request['endTime']) ? (date('Y', strtotime($request['endTime'])) . '/12/31 23:59:59') : '';
        $relativeStartTime = !empty($request['endTime']) ? (date('Y', strtotime("-1 year", strtotime($request['endTime']))) . '/01/01 00:00:00') : '';
        $request['startTime'] = strtotime($startTime) < strtotime($relativeStartTime) ? $startTime : $relativeStartTime;

        $reports = getReportByCondition($request);
        $absoluteTimeReportArr = $arr = [];

        if (!empty($reports['error'])) {
            return $reports;
        }

        // Reorder by date
        usort($reports, function ($a, $b) {
            return $a['zlaboratoryDate'] - $b['zlaboratoryDate'];
        });

        // Get extend info
        $deviceArr = [];
        foreach ($reports as $report) {
            if (in_array($report['class.type'], ['13.8. BBTN Máy biến áp phân phối 1 pha', '13.9. BBTN Máy biến áp phân phối 3 pha']) && in_array($report['zCI_Device'], $deviceArr)) {
                return [
                    'error' => ['Tồn tại thiết bị có nhiều hơn 1 lần làm biên bản']
                ];
            }

            array_push($deviceArr, $report['zCI_Device']);

            $report = getExtendInfoFromReport($report);
            if (!empty($report['error'])) {
                continue;
            }

            if ($report['zlaboratoryDate'] >= strtotime($relativeStartTime)) {
                $arr[date('Y', $report['zlaboratoryDate'])][] = $report;
            }
            $absoluteTimeReportArr[] = $report;
        }

        // Get data from exactly request time
        foreach ($absoluteTimeReportArr as $key => $report) {
            if ((!empty($startTime) && $report['zlaboratoryDate'] < strtotime($startTime)) || (!empty($endTime) && $report['zlaboratoryDate'] > strtotime($endTime))) {
                unset($absoluteTimeReportArr[$key]);
            }
        }

        $absoluteTimeDataArr = [
            'order' => 1,
            'time' => date('d/m/Y', strtotime($startTime)) . ' - '. date('d/m/Y', strtotime($endTime)),
            'pass' => 0,
            'failed' => 0,
            'total' => 0,
        ];

        foreach ($absoluteTimeReportArr as $val) {
            if ($val['result'] == 1) {
                $absoluteTimeDataArr['pass'] += 1;
            } else {
                $absoluteTimeDataArr['failed'] += 1;
            }
        }
        $absoluteTimeDataArr['total'] = $absoluteTimeDataArr['pass'] + $absoluteTimeDataArr['failed'];

        // Get data from relative time period, from request year to previous year
        $relativeTimeDataArr = [
            'order' => 1,
            'time' => date('Y', strtotime($request['endTime'])),
            'prevYearPass' => 0,
            'prevYearFailed' => 0,
            'currentYearPass' => 0,
            'currentYearFailed' => 0,
            'prevYearTotal' => 0,
            'currentYearTotal' => 0
        ];

        foreach ($arr as $key => $item) {
            $prefix = $key != date('Y', strtotime($request['endTime'])) ? 'prev' : 'current';

            foreach ($item as $val) {
                if ($val['result'] == 1) {
                    $relativeTimeDataArr[$prefix . 'YearPass'] += 1;
                } else {
                    $relativeTimeDataArr[$prefix . 'YearFailed'] += 1;
                }
            }
            $relativeTimeDataArr[$prefix . 'YearTotal'] = $relativeTimeDataArr[$prefix . 'YearPass'] + $relativeTimeDataArr[$prefix . 'YearFailed'];
        }

        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);

        // Read Template Excel
        $template = storage_path('templates_excel/thong_ke_cao_ap/' . $request['directoryName'] . '/bao-cao-theo-thoi-gian-chinh-xac.xlsx');
        $spreadsheet = $reader->load($template);

        // Sheet data
        $statisticsSheet = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THONG_KE");
        $chartSheet = $spreadsheet->setActiveSheetIndexByName("BIEU_DO");

        // Insert chart sheet label
        $chartSheet->getCell('B5')->setValue('Biểu đồ so sánh số liệu trong khoảng thời gian ' . date('d/m/Y', strtotime($startTime)) . ' - '. date('d/m/Y', strtotime($endTime)));
        $chartSheet->getCell('B31')->setValue('Biểu đồ so sánh số liệu trong 2 năm liền kề (' . ((int) date('Y', strtotime($request['endTime'])) - 1) . ' - ' . date('Y', strtotime($request['endTime'])) . ')');
        switch ($request['directoryName']) {
            case 'chong-set-van':
                $chartSheet->getChartByName('chart1')->setTitle(new \PhpOffice\PhpSpreadsheet\Chart\Title('Biểu đồ so sánh số liệu thí nghiệm CSV thí nghiệm từ ngày ' . date('d/m/Y', strtotime($startTime)) . ' đến ngày '. date('d/m/Y', strtotime($endTime))));
                $chartSheet->getChartByName('chart2')->setTitle(new \PhpOffice\PhpSpreadsheet\Chart\Title('Biểu đồ so sánh số liệu CSV thí nghiệm mẫu trong năm ' . ((int) date('Y', strtotime($request['endTime'])) - 1) . ' và năm ' . ((int) date('Y', strtotime($request['endTime'])))));
                break;
            case 'may-bien-dong-dien':
                $chartSheet->getChartByName('chart1')->setTitle(new \PhpOffice\PhpSpreadsheet\Chart\Title('Biểu đồ so sánh số liệu thí nghiệm TI thí nghiệm từ ngày ' . date('d/m/Y', strtotime($startTime)) . ' đến ngày '. date('d/m/Y', strtotime($endTime))));
                $chartSheet->getChartByName('chart2')->setTitle(new \PhpOffice\PhpSpreadsheet\Chart\Title('Biểu đồ so sánh số liệu TI thí nghiệm mẫu trong năm ' . ((int) date('Y', strtotime($request['endTime'])) - 1) . ' và năm ' . ((int) date('Y', strtotime($request['endTime'])))));
                break;
            case 'may-bien-dien-ap':
                $chartSheet->getChartByName('chart1')->setTitle(new \PhpOffice\PhpSpreadsheet\Chart\Title('Biểu đồ so sánh số liệu thí nghiệm TU thí nghiệm từ ngày ' . date('d/m/Y', strtotime($startTime)) . ' đến ngày '. date('d/m/Y', strtotime($endTime))));
                $chartSheet->getChartByName('chart2')->setTitle(new \PhpOffice\PhpSpreadsheet\Chart\Title('Biểu đồ so sánh số liệu TU thí nghiệm mẫu trong năm ' . ((int) date('Y', strtotime($request['endTime'])) - 1) . ' và năm ' . ((int) date('Y', strtotime($request['endTime'])))));
                break;
            case 'cap':
                $chartSheet->getChartByName('chart1')->setTitle(new \PhpOffice\PhpSpreadsheet\Chart\Title('Biểu đồ so sánh số liệu thí nghiệm mẫu cáp thí nghiệm từ ngày ' . date('d/m/Y', strtotime($startTime)) . ' đến ngày '. date('d/m/Y', strtotime($endTime))));
                $chartSheet->getChartByName('chart2')->setTitle(new \PhpOffice\PhpSpreadsheet\Chart\Title('Biểu đồ so sánh số liệu mẫu cáp thí nghiệm mẫu trong năm ' . ((int) date('Y', strtotime($request['endTime'])) - 1) . ' và năm ' . ((int) date('Y', strtotime($request['endTime'])))));
                break;
            default:
                $chartSheet->getChartByName('chart1')->setTitle(new \PhpOffice\PhpSpreadsheet\Chart\Title('Biểu đồ so sánh số liệu thí nghiệm MBA thí nghiệm từ ngày ' . date('d/m/Y', strtotime($startTime)) . ' đến ngày '. date('d/m/Y', strtotime($endTime))));
                $chartSheet->getChartByName('chart2')->setTitle(new \PhpOffice\PhpSpreadsheet\Chart\Title('Biểu đồ so sánh số liệu MBA thí nghiệm Po, Pk trong năm ' . ((int) date('Y', strtotime($request['endTime'])) - 1) . ' và năm ' . ((int) date('Y', strtotime($request['endTime'])))));
        }

        $statisticsSheet->getCell('C11')->setValue('Năm ' . date('Y', strtotime($relativeStartTime)));
        $statisticsSheet->getCell('E11')->setValue('Năm ' . date('Y', strtotime($request['endTime'])));
        $statisticsSheet->getCell('G11')->setValue('Năm ' . date('Y', strtotime($relativeStartTime)));
        $statisticsSheet->getCell('H11')->setValue('Năm ' . date('Y', strtotime($request['endTime'])));

        // Insert filter request
        insertFilterRequest($request, $statisticsSheet, 'C', [
            'Ngày bắt đầu: ' => (!empty($startTime) ? date('d/m/Y', strtotime($startTime)) : ''),
            'Ngày kết thúc: ' => (!empty($endTime) ? date('d/m/Y', strtotime($endTime)) : ''),
            'Hãng: ' => (!empty($request['zManufacturer_names']) ? $request['zManufacturer_names'] : ''),
            'Đơn vị quản lý: ' => (!empty($request['zrefnr_dvql_names']) ? $request['zrefnr_dvql_names'] : '')
        ]);

        // Insert data
        $col = 'A';
        foreach ($absoluteTimeDataArr as $val) {
            $statisticsSheet->getCell($col . '7')->setValue($val);
            ++$col;
        }

        $col = 'A';
        foreach ($relativeTimeDataArr as $val) {
            $statisticsSheet->getCell($col . '13')->setValue($val);
            ++$col;
        }

        $url = writeExcel($spreadsheet, Str::slug($request['fileTitle']) . '-theo-thoi-gian-chinh-xac-' . time() . '.xlsx');

        return [
            'success' => true,
            'url' => $url
        ];
    }
}

if (!function_exists('insertFilterRequest')) {
    function insertFilterRequest($request, $statisticsSheet, $startCol = 'C', $data = [])
    {
        foreach ($data as $key => $val) {
            $statisticsSheet->getColumnDimension($startCol)->setWidth(30);
            $statisticsSheet->getCell($startCol . '1')->setValue($key . $val);
            ++$startCol;
        }

        if (!empty($request['zManufacturer_ids']) || !empty($request['zrefnr_dvql_ids'])) {
            if (!empty($request['zManufacturer_ids']) && empty($request['zrefnr_dvql_ids'])) {
                $num = count($request['zManufacturer_ids']);
            } elseif (empty($request['zManufacturer_ids']) && !empty($request['zrefnr_dvql_ids'])) {
                $num = count($request['zrefnr_dvql_ids']);
            } else {
                $num = count($request['zManufacturer_ids']) < count($request['zrefnr_dvql_ids']) ? count($request['zManufacturer_ids']) : count($request['zrefnr_dvql_ids']);
            }
            $statisticsSheet->getRowDimension('1')->setRowHeight($num * 25);
        }
    }
}

if (!function_exists('highPressureStatisticsExactlyTime2')) {
    function highPressureStatisticsExactlyTime2($request)
    {
        $baseType = [
            'GOM_SU',
            'POLYMER_CHUOI',
            'POLYMER_DUNG',
            'THUY_TINH'
        ];
        $startTime = $request['startTime'];
        $endTime = $request['endTime'];

        $request['endTime'] = !empty($request['endTime']) ? (date('Y', strtotime($request['endTime'])) . '/12/31 23:59:59') : '';
        $relativeStartTime = !empty($request['endTime']) ? (date('Y', strtotime("-1 year", strtotime($request['endTime']))) . '/01/01 00:00:00') : '';
        $request['startTime'] = strtotime($startTime) < strtotime($relativeStartTime) ? $startTime : $relativeStartTime;

        $reports = getReportByCondition($request);
        $absoluteTimeReportArr = $arr = [];

        if (!empty($reports['error'])) {
            return $reports;
        }

        // Reorder by device type
        usort($reports, function ($a, $b) {
            return $a['zCI_Device.zCI_Device_Type.zsym'] <=> $b['zCI_Device.zCI_Device_Type.zsym'];
        });

        // Get extend info
        $deviceArr = [];
        foreach ($reports as $report) {
            $deviceType = ($report['zCI_Device.zCI_Device_Type'] == 400074 || $report['zCI_Device.zCI_Device_Type'] == 400078) ? 'GOM_SU' : strtoupper(str_replace('-', '_', Str::slug($report['zCI_Device.zCI_Device_Type.zsym'])));

            if (!in_array($deviceType, $baseType)) {
                return [
                    'error' => ['Dữ liệu đầu vào không hợp lệ']
                ];
            }

            if (in_array($report['class.type'], ['7. BBTN Cao Áp - Mẫu cách điện']) && in_array($report['zCI_Device'], $deviceArr)) {
                return [
                    'error' => ['Tồn tại thiết bị có nhiều hơn 1 lần làm biên bản']
                ];
            }

            array_push($deviceArr, $report['zCI_Device']);

            $report = getExtendInfoFromReport($report);
            if (!empty($report['error'])) {
                continue;
            }

            if ($report['zlaboratoryDate'] >= strtotime($relativeStartTime)) {
                $arr[($report['zCI_Device.zCI_Device_Type'] == 400074 || $report['zCI_Device.zCI_Device_Type'] == 400078) ? 'Gốm + Sứ' : $report['zCI_Device.zCI_Device_Type.zsym']][date('Y', $report['zlaboratoryDate'])][] = $report;
            }
            $absoluteTimeReportArr[($report['zCI_Device.zCI_Device_Type'] == 400074 || $report['zCI_Device.zCI_Device_Type'] == 400078) ? 'Gốm + Sứ' : $report['zCI_Device.zCI_Device_Type.zsym']][] = $report;
        }

        if (empty($arr)) {
            return [
                'error' => ['Không có dữ liệu thống kê thỏa mãn']
            ];
        }

        // Get data from exactly request time
        foreach ($absoluteTimeReportArr as $key => $val) {
            foreach ($val as $index => $report) {
                if ((!empty($startTime) && $report['zlaboratoryDate'] < strtotime($startTime)) || (!empty($endTime) && $report['zlaboratoryDate'] > strtotime($endTime))) {
                    unset($absoluteTimeReportArr[$key][$index]);
                }
            }
        }

        $absoluteTimeDataArr = $relativeTimeDataArr = [];
        foreach ($absoluteTimeReportArr as $key => $item) {
            $absoluteTimeDataArr[$key] = [
                'order' => 1,
                'time' => date('d/m/Y', strtotime($startTime)) . ' - '. date('d/m/Y', strtotime($endTime)),
                'pass' => 0,
                'failed' => 0,
                'total' => 0,
            ];

            // Reorder by date
            usort($item, function ($a, $b) {
                return $a['zlaboratoryDate'] - $b['zlaboratoryDate'];
            });

            foreach ($item as $val) {
                if ($val['result'] == 1) {
                    $absoluteTimeDataArr[$key]['pass'] += 1;
                } else {
                    $absoluteTimeDataArr[$key]['failed'] += 1;
                }
            }

            $absoluteTimeDataArr[$key]['total'] = $absoluteTimeDataArr[$key]['pass'] + $absoluteTimeDataArr[$key]['failed'];
        }

        // Get data from relative time period, from request year to previous year
        foreach ($arr as $groupKey => $group) {
            $relativeTimeDataArr[$groupKey] = [
                'order' => 1,
                'time' => date('Y', strtotime($request['endTime'])),
                'prevYearPass' => 0,
                'prevYearFailed' => 0,
                'currentYearPass' => 0,
                'currentYearFailed' => 0,
                'prevYearTotal' => 0,
                'currentYearTotal' => 0
            ];

            foreach ($group as $key => $item) {
                // Reorder by date
                usort($item, function ($a, $b) {
                    return $a['zlaboratoryDate'] - $b['zlaboratoryDate'];
                });

                $prefix = $key != date('Y', strtotime($request['endTime'])) ? 'prev' : 'current';

                foreach ($item as $val) {
                    if ($val['result'] == 1) {
                        $relativeTimeDataArr[$groupKey][$prefix . 'YearPass'] += 1;
                    } else {
                        $relativeTimeDataArr[$groupKey][$prefix . 'YearFailed'] += 1;
                    }
                }
                $relativeTimeDataArr[$groupKey][$prefix . 'YearTotal'] = $relativeTimeDataArr[$groupKey][$prefix . 'YearPass'] + $relativeTimeDataArr[$groupKey][$prefix . 'YearFailed'];
            }
        }

        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);

        // Read Template Excel
        $template = storage_path('templates_excel/thong_ke_cao_ap/' . $request['directoryName'] . '/bao-cao-theo-thoi-gian-chinh-xac.xlsx');
        $spreadsheet = $reader->load($template);

        foreach ($absoluteTimeDataArr as $index => $item) {
            // Sheet data
            $statisticsSheet = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THONG_KE_" . strtoupper(str_replace('-', '_', Str::slug($index))));
            $chartSheet = $spreadsheet->setActiveSheetIndexByName("BIEU_DO_" . strtoupper(str_replace('-', '_', Str::slug($index))));

            // Insert chart sheet label
            $chartSheet->getCell('B5')->setValue('Biểu đồ so sánh số liệu trong khoảng thời gian ' . date('d/m/Y', strtotime($startTime)) . ' - '. date('d/m/Y', strtotime($endTime)));
            $chartSheet->getCell('B31')->setValue('Biểu đồ so sánh số liệu trong 2 năm liền kề (' . ((int) date('Y', strtotime($request['endTime'])) - 1) . ' - ' . date('Y', strtotime($request['endTime'])) . ')');

            $chartNames = $chartSheet->getChartNames();

            $chartSheet->getChartByName($chartNames[0])->setTitle(new \PhpOffice\PhpSpreadsheet\Chart\Title('Biểu đồ so sánh số liệu thí nghiệm Cách điện ' . strtolower(str_replace(' + ', ' ', $index)) . ' thí nghiệm từ ngày ' . date('d/m/Y', strtotime($startTime)) . ' đến ngày '. date('d/m/Y', strtotime($endTime))));
            $chartSheet->getChartByName($chartNames[1])->setTitle(new \PhpOffice\PhpSpreadsheet\Chart\Title('Biểu đồ so sánh số liệu Cách điện ' . strtolower(str_replace(' + ', ' ', $index)) . ' thí nghiệm mẫu trong năm ' . ((int) date('Y', strtotime($request['endTime'])) - 1) . ' và năm ' . ((int) date('Y', strtotime($request['endTime'])))));

            $statisticsSheet->getCell('C11')->setValue('Năm ' . date('Y', strtotime($request['startTime'])));
            $statisticsSheet->getCell('E11')->setValue('Năm ' . date('Y', strtotime($request['endTime'])));
            $statisticsSheet->getCell('G11')->setValue('Năm ' . date('Y', strtotime($request['startTime'])));
            $statisticsSheet->getCell('H11')->setValue('Năm ' . date('Y', strtotime($request['endTime'])));

            // Insert filter request
            insertFilterRequest($request, $statisticsSheet, 'C', [
                'Ngày bắt đầu: ' => (!empty($startTime) ? date('d/m/Y', strtotime($startTime)) : ''),
                'Ngày kết thúc: ' => (!empty($endTime) ? date('d/m/Y', strtotime($endTime)) : ''),
                'Hãng: ' => (!empty($request['zManufacturer_names']) ? $request['zManufacturer_names'] : ''),
                'Đơn vị quản lý: ' => (!empty($request['zrefnr_dvql_names']) ? $request['zrefnr_dvql_names'] : '')
            ]);

            // Insert data
            $col = 'A';
            foreach ($item as $val) {
                $statisticsSheet->getCell($col . '7')->setValue($val);
                ++$col;
            }

            $col = 'A';
            if (!empty($relativeTimeDataArr[$index])) {
                foreach ($relativeTimeDataArr[$index] as $val) {
                    $statisticsSheet->getCell($col . '13')->setValue($val);
                    ++$col;
                }
            }

            if (in_array(strtoupper(str_replace('-', '_', Str::slug($index))), $baseType)) {
                unset($baseType[array_search(strtoupper(str_replace('-', '_', Str::slug($index))), $baseType)]);
            }
        }

        // Remove unnecessary sheet
        if (!empty($baseType)) {
            foreach ($baseType as $type) {
                $statisticsSheetIndex = $spreadsheet->getIndex($spreadsheet->getSheetByName('KET_QUA_THONG_KE_' . $type));
                $spreadsheet->removeSheetByIndex($statisticsSheetIndex);

                $chartSheetIndex = $spreadsheet->getIndex($spreadsheet->getSheetByName('BIEU_DO_' . $type));
                $spreadsheet->removeSheetByIndex($chartSheetIndex);

                $chartSheetDataIndex = $spreadsheet->getIndex($spreadsheet->getSheetByName('DATA_BĐ_' . $type));
                $spreadsheet->removeSheetByIndex($chartSheetDataIndex);
            }
        }

        $spreadsheet->setActiveSheetIndexByName("BIEU_DO_" . strtoupper(str_replace('-', '_', Str::slug(array_key_first($absoluteTimeDataArr)))));

        $url = writeExcel($spreadsheet, Str::slug($request['fileTitle']) . '-theo-thoi-gian-chinh-xac-' . time() . '.xlsx');

        return [
            'success' => true,
            'url' => $url
        ];
    }
}

if (!function_exists('highPressureStatisticsQuarterly')) {
    function highPressureStatisticsQuarterly($request)
    {
        $startTime = $request['startTime'];
        $endTime = $request['endTime'];

        $request['endTime'] = !empty($request['endTime']) ? (date('Y', strtotime($request['endTime'])) . '/12/31 23:59:59') : '';
        $request['startTime'] = !empty($request['endTime']) ? (date('Y', strtotime("-1 year", strtotime($request['endTime']))) . '/01/01 00:00:00') : '';

        $reports = getReportByCondition($request);

        if (!empty($reports['error'])) {
            return $reports;
        }

        // Reorder by date
        usort($reports, function ($a, $b) {
            return $a['zlaboratoryDate'] - $b['zlaboratoryDate'];
        });

        // Get extend info
        $arr = $deviceArr = [];
        foreach ($reports as $report) {
            if (in_array($report['class.type'], ['13.8. BBTN Máy biến áp phân phối 1 pha', '13.9. BBTN Máy biến áp phân phối 3 pha']) && in_array($report['zCI_Device'], $deviceArr)) {
                return [
                    'error' => ['Tồn tại thiết bị có nhiều hơn 1 lần làm biên bản']
                ];
            }

            array_push($deviceArr, $report['zCI_Device']);

            $report = getExtendInfoFromReport($report);
            if (!empty($report['error'])) {
                continue;
            }

            $arr[date('Y-m', $report['zlaboratoryDate'])][] = $report;
        }

        // Format data and group by quarter
        $dataArr = [];
        for ($i = 1; $i <= 12; $i++) {
            if ($i % 3 == 1) {
                $item = [];
                $item['order'] = ($i - 1) / 3 + 1;
                $item['quarter'] = 'Quý ' . (($i - 1) / 3 + 1);
                $item['currentYearTotal'] = $item['prevYearTotal'] = $item['currentYearFailed'] = $item['currentYearPass'] = $item['prevYearFailed'] = $item['prevYearPass'] = 0;
            }

            if (!empty($item)) {
                $currentYearKey = date('Y', strtotime($request['endTime'])) . '-' . ($i < 10 ? '0' . $i : $i);
                $prevYearKey = date('Y', strtotime($request['startTime'])) . '-' . ($i < 10 ? '0' . $i : $i);
                $timeKeyArr = [
                    'prev' => $prevYearKey,
                    'current' => $currentYearKey
                ];

                foreach ($timeKeyArr as $key => $timeKey) {
                    if (!empty($arr[$timeKey])) {
                        foreach ($arr[$timeKey] as $val) {
                            if ($val['result'] == 1) {
                                $item[$key . 'YearPass'] += 1;
                            } else {
                                $item[$key . 'YearFailed'] += 1;
                            }
                        }
                        $item[$key . 'YearTotal'] = $item[$key . 'YearPass'] + $item[$key . 'YearFailed'];
                    }
                }

                if ($i % 3 == 0) {
                    array_push($dataArr, $item);
                }
            }
        }

        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);

        // Read Template Excel
        $template = storage_path('templates_excel/thong_ke_cao_ap/' . $request['directoryName'] . '/bao-cao-theo-quy.xlsx');
        $spreadsheet = $reader->load($template);

        // Sheet data
        $statisticsSheet = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THONG_KE");
        $chartSheet = $spreadsheet->setActiveSheetIndexByName("BIEU_DO");

        // Insert chart sheet label
        $chartSheet->getCell('B5')->setValue('Biểu đồ so sánh số liệu trong cùng năm ' . date('Y', strtotime($request['endTime'])));
        $chartSheet->getCell('B31')->setValue('Biểu đồ so sánh số liệu trong 2 năm liền kề (' . date('Y', strtotime($request['startTime'])) . ' - ' . date('Y', strtotime($request['endTime'])) . ')');

        switch ($request['directoryName']) {
            case 'chong-set-van':
                $chartTitle1 = 'Biểu đồ so sánh số liệu CSV thí nghiệm theo các quý trong năm ' . date('Y', strtotime($request['endTime']));
                $chartTitle2 = 'Biểu đồ so sánh số liệu CSV thí nghiệm theo các quý trong năm ' . date('Y', strtotime($request['startTime'])) . ' và năm ' . date('Y', strtotime($request['endTime']));
                break;
            case 'may-bien-dong-dien':
                $chartTitle1 = 'Biểu đồ so sánh số liệu TI thí nghiệm theo các quý trong năm ' . date('Y', strtotime($request['endTime']));
                $chartTitle2 = 'Biểu đồ so sánh số liệu TI thí nghiệm theo các quý trong năm ' . date('Y', strtotime($request['startTime'])) . ' và năm ' . date('Y', strtotime($request['endTime']));
                break;
            case 'may-bien-dien-ap':
                $chartTitle1 = 'Biểu đồ so sánh số liệu TU thí nghiệm theo các quý trong năm ' . date('Y', strtotime($request['endTime']));
                $chartTitle2 = 'Biểu đồ so sánh số liệu TU thí nghiệm theo các quý trong năm ' . date('Y', strtotime($request['startTime'])) . ' và năm ' . date('Y', strtotime($request['endTime']));
                break;
            case 'cap':
                $chartTitle1 = 'Biểu đồ so sánh số liệu mẫu cáp thí nghiệm theo các quý trong năm ' . date('Y', strtotime($request['endTime']));
                $chartTitle2 = 'Biểu đồ so sánh số liệu mẫu cáp thí nghiệm theo các quý trong năm ' . date('Y', strtotime($request['startTime'])) . ' và năm ' . date('Y', strtotime($request['endTime']));
                break;
            default:
                $chartTitle1 = 'Biểu đồ so sánh số liệu MBA thí nghiệm Po, Pk theo các quý trong năm ' . date('Y', strtotime($request['endTime']));
                $chartTitle2 = 'Biểu đồ so sánh số liệu MBA thí nghiệm Po, Pk theo các quý trong năm ' . date('Y', strtotime($request['startTime'])) . ' và năm ' . date('Y', strtotime($request['endTime']));
        }
        $chartSheet->getChartByName('chart1')->setTitle(new \PhpOffice\PhpSpreadsheet\Chart\Title($chartTitle1));
        $chartSheet->getChartByName('chart2')->setTitle(new \PhpOffice\PhpSpreadsheet\Chart\Title($chartTitle2));

        // Insert statistics sheet label
        $statisticsSheet->getCell('C6')->setValue('Năm ' . date('Y', strtotime($request['startTime'])));
        $statisticsSheet->getCell('E6')->setValue('Năm ' . date('Y', strtotime($request['endTime'])));
        $statisticsSheet->getCell('G6')->setValue('Năm ' . date('Y', strtotime($request['startTime'])));
        $statisticsSheet->getCell('H6')->setValue('Năm ' . date('Y', strtotime($request['endTime'])));

        // Insert filter request
        insertFilterRequest($request, $statisticsSheet, 'C', [
            'Ngày bắt đầu: ' => (!empty($startTime) ? date('d/m/Y', strtotime($startTime)) : ''),
            'Ngày kết thúc: ' => (!empty($endTime) ? date('d/m/Y', strtotime($endTime)) : ''),
            'Hãng: ' => (!empty($request['zManufacturer_names']) ? $request['zManufacturer_names'] : ''),
            'Đơn vị quản lý: ' => (!empty($request['zrefnr_dvql_names']) ? $request['zrefnr_dvql_names'] : '')
        ]);

        $row = 8;
        foreach ($dataArr as $val) {
            $col = 'A';
            foreach ($val as $item) {
                $statisticsSheet->getCell($col . $row)->setValue($item);
                ++$col;
            }
            $row++;
        }

        $url = writeExcel($spreadsheet, Str::slug($request['fileTitle']) . '-theo-quy-' . time() . '.xlsx');

        return [
            'success' => true,
            'url' => $url
        ];
    }
}

if (!function_exists('highPressureStatisticsQuarterly2')) {
    function highPressureStatisticsQuarterly2($request)
    {
        $startTime = $request['startTime'];
        $endTime = $request['endTime'];

        $request['endTime'] = !empty($request['endTime']) ? (date('Y', strtotime($request['endTime'])) . '/12/31 23:59:59') : '';
        $request['startTime'] = !empty($request['endTime']) ? (date('Y', strtotime("-1 year", strtotime($request['endTime']))) . '/01/01 00:00:00') : '';

        $reports = getReportByCondition($request);

        if (!empty($reports['error'])) {
            return $reports;
        }

        // Reorder by device type
        usort($reports, function ($a, $b) {
            return $a['zCI_Device.zCI_Device_Type.zsym'] <=> $b['zCI_Device.zCI_Device_Type.zsym'];
        });

        // Get extend info
        $arr = $deviceArr = [];
        foreach ($reports as $report) {
            if (in_array($report['class.type'], ['7. BBTN Cao Áp - Mẫu cách điện']) && in_array($report['zCI_Device'], $deviceArr)) {
                return [
                    'error' => ['Tồn tại thiết bị có nhiều hơn 1 lần làm biên bản']
                ];
            }

            array_push($deviceArr, $report['zCI_Device']);

            $report = getExtendInfoFromReport($report);
            if (!empty($report['error'])) {
                continue;
            }

            $arr[($report['zCI_Device.zCI_Device_Type'] == 400074 || $report['zCI_Device.zCI_Device_Type'] == 400078) ? 'Gốm + Sứ' : $report['zCI_Device.zCI_Device_Type.zsym']][date('Y-m', $report['zlaboratoryDate'])][] = $report;
        }

        // Format data and group by quarter
        $groupArr = [];
        foreach ($arr as $groupKey => $group) {
            $dataArr = [];
            for ($i = 1; $i <= 12; $i++) {
                if ($i % 3 == 1) {
                    $item = [];
                    $item['order'] = ($i - 1) / 3 + 1;
                    $item['quarter'] = 'Quý ' . (($i - 1) / 3 + 1);
                    $item['currentYearTotal'] = $item['prevYearTotal'] = $item['currentYearFailed'] = $item['currentYearPass'] = $item['prevYearFailed'] = $item['prevYearPass'] = 0;
                }

                if (!empty($item)) {
                    $currentYearKey = date('Y', strtotime($request['endTime'])) . '-' . ($i < 10 ? '0' . $i : $i);
                    $prevYearKey = date('Y', strtotime($request['startTime'])) . '-' . ($i < 10 ? '0' . $i : $i);
                    $timeKeyArr = [
                        'prev' => $prevYearKey,
                        'current' => $currentYearKey
                    ];

                    foreach ($timeKeyArr as $key => $timeKey) {
                        if (!empty($group[$timeKey])) {
                            foreach ($group[$timeKey] as $val) {
                                if ($val['result'] == 1) {
                                    $item[$key . 'YearPass'] += 1;
                                } else {
                                    $item[$key . 'YearFailed'] += 1;
                                }
                            }
                            $item[$key . 'YearTotal'] = $item[$key . 'YearPass'] + $item[$key . 'YearFailed'];
                        }
                    }

                    if ($i % 3 == 0) {
                        array_push($dataArr, $item);
                    }
                }
            }
            $groupArr[$groupKey] = $dataArr;
        }

        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);
        // Read Template Excel
        $template = storage_path('templates_excel/thong_ke_cao_ap/' . $request['directoryName'] . '/bao-cao-theo-quy.xlsx');
        $spreadsheet = $reader->load($template);

        // Sheet data
        $statisticsSheet = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THONG_KE");

        // Insert filter request
        insertFilterRequest($request, $statisticsSheet, 'I', [
            'Ngày bắt đầu: ' => (!empty($startTime) ? date('d/m/Y', strtotime($startTime)) : ''),
            'Ngày kết thúc: ' => (!empty($endTime) ? date('d/m/Y', strtotime($endTime)) : ''),
            'Hãng: ' => (!empty($request['zManufacturer_names']) ? $request['zManufacturer_names'] : ''),
            'Đơn vị quản lý: ' => (!empty($request['zrefnr_dvql_names']) ? $request['zrefnr_dvql_names'] : '')
        ]);

        // Insert statistics sheet label
        $statisticsSheet->getCell('C6')->setValue('Năm ' . date('Y', strtotime($request['startTime'])));
        $statisticsSheet->getCell('E6')->setValue('Năm ' . date('Y', strtotime($request['endTime'])));
        $statisticsSheet->getCell('G6')->setValue('Năm ' . date('Y', strtotime($request['startTime'])));
        $statisticsSheet->getCell('I6')->setValue('Năm ' . date('Y', strtotime($request['endTime'])));
        $statisticsSheet->getCell('K6')->setValue('Năm ' . date('Y', strtotime($request['startTime'])));
        $statisticsSheet->getCell('M6')->setValue('Năm ' . date('Y', strtotime($request['endTime'])));
        $statisticsSheet->getCell('O6')->setValue('Năm ' . date('Y', strtotime($request['startTime'])));
        $statisticsSheet->getCell('Q6')->setValue('Năm ' . date('Y', strtotime($request['endTime'])));
        $statisticsSheet->getCell('S6')->setValue('Năm ' . date('Y', strtotime($request['startTime'])));
        $statisticsSheet->getCell('T6')->setValue('Năm ' . date('Y', strtotime($request['endTime'])));

        $formattedGroupArr = [];
        foreach ($groupArr as $index => $item) {
            $chartSheet = $spreadsheet->setActiveSheetIndexByName("BIEU_DO_" . strtoupper(str_replace('-', '_', Str::slug($index))));

            // Insert chart sheet label
            $chartSheet->getCell('B5')->setValue('Biểu đồ so sánh số liệu trong cùng năm ' . date('Y', strtotime($request['endTime'])));
            $chartSheet->getCell('B31')->setValue('Biểu đồ so sánh số liệu trong 2 năm liền kề (' . date('Y', strtotime($request['startTime'])) . ' - ' . date('Y', strtotime($request['endTime'])) . ')');

            $chartNames = $chartSheet->getChartNames();

            $chartSheet->getChartByName($chartNames[0])->setTitle(new \PhpOffice\PhpSpreadsheet\Chart\Title('Biểu đồ so sánh số liệu Cách điện ' . strtolower(str_replace(' + ', ' ', $index)) . ' theo các quý trong năm ' . date('Y', strtotime($request['endTime']))));
            $chartSheet->getChartByName($chartNames[1])->setTitle(new \PhpOffice\PhpSpreadsheet\Chart\Title('Biểu đồ so sánh số liệu Cách điện ' . strtolower(str_replace(' + ', ' ', $index)) . ' thí nghiệm theo các quý trong năm ' . date('Y', strtotime($request['startTime'])) . ' và năm ' . date('Y', strtotime($request['endTime']))));

            foreach ($item as $val) {
                $formattedGroupArr[$val['order']][] = $val;
            }
        }

        // Insert data
        $row = 8;
        foreach ($formattedGroupArr as $group) {
            $col = 'C';
            foreach ($group as $val) {
                unset($val['order']);
                unset($val['quarter']);
                unset($val['prevYearTotal']);
                unset($val['currentYearTotal']);

                foreach ($val as $item) {
                    $statisticsSheet->getCell($col . $row)->setValue($item);
                    ++$col;
                }
            }
            $row++;
        }

        $spreadsheet->setActiveSheetIndexByName("BIEU_DO_" . strtoupper(str_replace('-', '_', Str::slug(array_key_first($groupArr)))));

        $url = writeExcel($spreadsheet, Str::slug($request['fileTitle']) . '-theo-quy-' . time() . '.xlsx');

        return [
            'success' => true,
            'url' => $url
        ];
    }
}

if (!function_exists('highPressureStatisticsAnnually')) {
    function highPressureStatisticsAnnually($request, $byManufacture = false)
    {
        $dataArr = getHighPressureStatisticsOptionalTimeData($request, false, $byManufacture);

        if (!empty($dataArr['error'])) {
            return $dataArr;
        }

        // Format data by request time
        $startTime = new DateTime($request['startTime']);
        $endTime = new DateTime($request['endTime']);
        $diffYears = $startTime->diff($endTime)->y;

        $arr = [];
        for ($i = 0; $i <= $diffYears; $i++) {
            $item = [];
            $year = (int) $startTime->format('Y') + $i;
            foreach ($dataArr as $val) {
                if (($byManufacture ? (int)explode('_', $val['time'])[1] : (int)explode(' ', $val['time'])[1]) == $year) {
                    $val['order'] = $i + 1;
                    $val['time'] = $year;
                    $item = $val;
                }
            }

            if (empty($item)) {
                $item = [
                    "order" => $i + 1,
                    "time" => $year,
                ];

                for ($j = 1; $j <= $request['testItemNumber']; $j++) {
                    $item['pass_' . $j] = 0;
                    $item['failed_' . $j] = 0;
                    $item['total_' . $j] = 0;
                    $item['rate_' . $j] = 0;
                }
            }

            if ($byManufacture) {
                $manufactures = Cache::remember('allManufacture', 7200, function () {
                    return getDataFromService('doSelect', 'zManufacturer', ['id', 'zsym'], 'zsym IS NOT NULL', 300);
                });

                $manufacture = collect($manufactures)->filter(function ($value, $key) use ($request) {
                    return in_array($value['id'], $request['zManufacturer_ids']);
                })->first();

                array_splice($item, 1, 0, ['manufacture' => !empty($manufacture['zsym']) ? $manufacture['zsym'] : '']);
            }

            array_push($arr, $item);
        }

        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);

        // Read Template Excel
        $template = storage_path('templates_excel/thong_ke_cao_ap/' . $request['directoryName'] . '/' . (!$byManufacture ? 'bao-cao-theo-nam/bao-cao-theo-nam-' : 'bao-cao-theo-doanh-so-va-chat-luong-tung-nha-san-xuat/bao-cao-theo-doanh-so-va-chat-luong-tung-nha-san-xuat-') . ($diffYears + 1) . '.xlsx');
        $spreadsheet = $reader->load($template);

        // Sheet data
        $statisticsSheet = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THONG_KE");

        // Insert filter request
        insertFilterRequest($request, $statisticsSheet, $request['directoryName'] == 'may-bien-ap-phan-phoi' ? 'D' : 'B', [
            'Ngày bắt đầu: ' => (!empty($startTime) ? date('d/m/Y', strtotime($request['startTime'])) : ''),
            'Ngày kết thúc: ' => (!empty($endTime) ? date('d/m/Y', strtotime($request['endTime'])) : ''),
            'Hãng: ' => (!empty($request['zManufacturer_names']) ? $request['zManufacturer_names'] : ''),
            'Đơn vị quản lý: ' => (!empty($request['zrefnr_dvql_names']) ? $request['zrefnr_dvql_names'] : '')
        ]);

        $spreadsheet->setActiveSheetIndexByName("BIEU_DO");

        // Insert data to statistics sheet
        $row = 7;
        foreach ($arr as $val) {
            $col = 'A';
            foreach ($val as $item) {
                $statisticsSheet->getCell($col . $row)->setValue($item);
                ++$col;
            }
            $row++;
        }

        $url = writeExcel($spreadsheet, Str::slug($request['fileTitle']) . '-theo-nam-' . time() . '.xlsx');

        return [
            'success' => true,
            'url' => $url
        ];
    }
}

if (!function_exists('highPressureStatisticsAnnually2')) {
    function highPressureStatisticsAnnually2($request, $byManufacture = false)
    {
        $dataArr = getHighPressureStatisticsOptionalTimeData2($request, false, $byManufacture);

        if (!empty($dataArr['error'])) {
            return $dataArr;
        }

        // Format data by request time
        $startTime = new DateTime($request['startTime']);
        $endTime = new DateTime($request['endTime']);
        $diffYears = $startTime->diff($endTime)->y;

        $baseType = [
            'Gốm + Sứ',
            'Polymer chuỗi',
            'Polymer đứng',
            'Thủy tinh',
        ];

        $formattedGroup = [];
        $count = 0;
        foreach ($dataArr as $groupKey => $group) {
            if (!in_array($baseType[$count], array_keys($dataArr))) {
                $arr = [];
                for ($i = 0; $i <= $diffYears; $i++) {
                    $item = [
                        "order" => $i + 1,
                        "time" => (int) $startTime->format('Y') + $i,
                    ];

                    for ($j = 1; $j <= $request['testItemNumber']; $j++) {
                        $item['pass_' . $j] = 0;
                        $item['failed_' . $j] = 0;
                        $item['total_' . $j] = 0;
                        $item['rate_' . $j] = 0;
                    }

                    array_push($arr, $item);
                }
                $formattedGroup[$baseType[$count]] = $arr;
            }

            $arr = [];
            for ($i = 0; $i <= $diffYears; $i++) {
                $item = [];
                $year = (int) $startTime->format('Y') + $i;
                foreach ($group as $val) {
                    if (($byManufacture ? (int)explode('_', $val['time'])[1] : (int)explode(' ', $val['time'])[1]) == $year) {
                        $val['order'] = $i + 1;
                        $val['time'] = $year;
                        $item = $val;
                    }
                }

                if (empty($item)) {
                    $item = [
                        "order" => $i + 1,
                        "time" => $year,
                    ];

                    for ($j = 1; $j <= $request['testItemNumber']; $j++) {
                        $item['pass_' . $j] = 0;
                        $item['failed_' . $j] = 0;
                        $item['total_' . $j] = 0;
                        $item['rate_' . $j] = 0;
                    }
                }

                array_push($arr, $item);
            }

            $formattedGroup[$groupKey] = $arr;
            $count++;
        }

        if ($byManufacture) {
            $manufactures = Cache::remember('allManufacture', 7200, function () {
                return getDataFromService('doSelect', 'zManufacturer', ['id', 'zsym'], 'zsym IS NOT NULL', 300);
            });

            $manufacture = collect($manufactures)->filter(function ($value, $key) use ($request) {
                return in_array($value['id'], $request['zManufacturer_ids']);
            })->first();
        }

        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);

        // Read Template Excel
        $template = storage_path('templates_excel/thong_ke_cao_ap/' . $request['directoryName'] . '/' . (!$byManufacture ? 'bao-cao-theo-nam/bao-cao-theo-nam-' : 'bao-cao-theo-doanh-so-va-chat-luong-tung-nha-san-xuat/bao-cao-theo-doanh-so-va-chat-luong-tung-nha-san-xuat-') . ($diffYears + 1) . '.xlsx');
        $spreadsheet = $reader->load($template);

        // Sheet data
        $statisticsSheet = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THONG_KE");

        // Insert filter request
        insertFilterRequest($request, $statisticsSheet, 'J', [
            'Ngày bắt đầu: ' => (!empty($startTime) ? date('d/m/Y', strtotime($request['startTime'])) : ''),
            'Ngày kết thúc: ' => (!empty($endTime) ? date('d/m/Y', strtotime($request['endTime'])) : ''),
            'Hãng: ' => (!empty($request['zManufacturer_names']) ? $request['zManufacturer_names'] : ''),
            'Đơn vị quản lý: ' => (!empty($request['zrefnr_dvql_names']) ? $request['zrefnr_dvql_names'] : '')
        ]);

        // Insert data to statistics sheet
        $row = 7;
        for ($i = 0; $i <= $diffYears; $i++) {
            $statisticsSheet->getCell('A' . $row)->setValue($i + 1);
            if (!$byManufacture) {
                $statisticsSheet->getCell('B' . $row)->setValue((int) $startTime->format('Y') + $i);
            } else {
                $statisticsSheet->getCell('B' . $row)->setValue(!empty($manufacture['zsym']) ? $manufacture['zsym'] : '');
                $statisticsSheet->getCell('C' . $row)->setValue((int) $startTime->format('Y') + $i);
            }

            $col = $byManufacture ? 'D' : 'C';
            foreach ($formattedGroup as $group) {
                unset($group[$i]['order']);
                unset($group[$i]['time']);

                foreach ($group[$i] as $item) {
                    $statisticsSheet->getCell($col . $row)->setValue($item);
                    ++$col;
                }
            }
            $row++;
        }

        $spreadsheet->setActiveSheetIndexByName("BIEU_DO_" . strtoupper(str_replace('-', '_', Str::slug(array_key_first($formattedGroup)))));

        $url = writeExcel($spreadsheet, Str::slug($request['fileTitle']) . '-theo-nam-' . time() . '.xlsx');

        return [
            'success' => true,
            'url' => $url
        ];
    }
}

if (!function_exists('highPressureStatisticsSalesAndQuality')) {
    function highPressureStatisticsSalesAndQuality($request)
    {
        if (!isset($request['zManufacturer_ids']) || count($request['zManufacturer_ids']) > 1) {
            return [
                'error' => ['Đầu vào báo cáo yêu cầu phải chọn 1 hãng sản xuất']
            ];
        }

        if ($request['type'] == '7. BBTN Cao Áp - Mẫu cách điện') {
            return highPressureStatisticsAnnually2($request, true);
        }

        return highPressureStatisticsAnnually($request, true);
    }
}

if (!function_exists('highPressureStatisticsSalesByManufacture')) {
    function highPressureStatisticsSalesByManufacture($request, $type = '')
    {
        $dataArr = getHighPressureStatisticsOptionalTimeData($request, false, true);

        if (!empty($dataArr['error'])) {
            return $dataArr;
        }

        // Reformatted data
        $statisticsArr = $dataChartArr = [];
        foreach ($dataArr as $key => $val) {
            $formattedItem = [];
            foreach ($val as $index => $item) {
                if ($index == 'time') {
                    $formattedItem['manufacture'] = explode('_', $val[$index])[0];
                    $formattedItem['time'] = explode('_', $val[$index])[1];
                    continue;
                }

                if ($index == 'rate_1') {
                    if (empty($type)) {
                        $formattedItem['rate_1'] = round($val['total_1'] / array_sum(array_column($dataArr,'total_1')) * 100, 2);
                    } else {
                        $formattedItem['rate_1'] = round($val['pass_1'] / $val['total_1'] * 100, 2);
                    }
                    break;
                }

                $formattedItem[$index] = $item;
            }
            array_push($statisticsArr, $formattedItem);
            $dataChartArr[explode('_', $val['time'])[0]][] = $val;
        }
        usort($statisticsArr, function ($a, $b) {
            return $b['rate_1'] - $a['rate_1'];
        });

        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);

        // Read Template Excel
        $template = storage_path('templates_excel/thong_ke_cao_ap/' . $request['directoryName'] . '/' . (!empty($type) ? $type : 'bao-cao-so-sanh-doanh-so-giua-cac-nha-san-xuat') . '/' . (!empty($type) ? $type : 'bao-cao-so-sanh-doanh-so-giua-cac-nha-san-xuat') . '-' . count($dataChartArr) . '.xlsx');
        $spreadsheet = $reader->load($template);

        // Sheet data
        $statisticsSheet = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THONG_KE");
        $chartDataSheet = $spreadsheet->setActiveSheetIndexByName("DATA_BĐ1");

        // Insert data to statistics sheet
        $row = 6;
        foreach ($statisticsArr as $key => $val) {
            $col = 'A';
            foreach ($val as $item) {
                $statisticsSheet->getCell($col . $row)->setValue($col == 'A' ? ($key + 1) : $item);
                ++$col;
            }
            $row++;
        }
        $statisticsSheet->getCell('B' . $row)->setValue('Tổng số');
        $statisticsSheet->getStyle('B' . $row)->applyFromArray([
            'font' => [
                'bold' => true,
            ]
        ]);
        $statisticsSheet->getCell('D' . $row)->setValue(array_sum(array_column($statisticsArr,'pass_1')));
        $statisticsSheet->getCell('E' . $row)->setValue(array_sum(array_column($statisticsArr,'failed_1')));
        $statisticsSheet->getCell('F' . $row)->setValue(array_sum(array_column($statisticsArr,'total_1')));
        if (empty($type)) {
            $statisticsSheet->getCell('G' . $row)->setValue('100');
        } else {
            $statisticsSheet->getCell('G' . $row)->setValue(round(array_sum(array_column($statisticsArr,'pass_1')) / array_sum(array_column($statisticsArr,'total_1')) * 100, 2));
        }

        // Insert data to chart data sheet
        $row = 7;

        $formattedDataChart = [];
        foreach ($dataChartArr as $key => $val) {
            $item = [];
            $item['manufacture'] = $key;
            $item['percentage'] = empty($type) ? round(array_sum(array_column($val,'total_1')) / array_sum(array_column($dataArr,'total_1')) * 100, 2) : (array_sum(array_column($val,'total_1')) == 0 ? 0 : round(array_sum(array_column($val,'pass_1')) / array_sum(array_column($val,'total_1')) * 100, 2));
            array_push($formattedDataChart, $item);
        }
        usort($formattedDataChart, function ($a, $b) {
            return $b['percentage'] - $a['percentage'];
        });

        foreach ($formattedDataChart as $key => $val) {
            $chartDataSheet->getCell('A' . $row)->setValue($key + 1);
            $chartDataSheet->getCell('B' . $row)->setValue($val['manufacture']);
            $chartDataSheet->getCell('C' . $row)->setValue($val['percentage']);
            $row++;
        }

        $statisticsSheet = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THONG_KE");
        fillSearchRequestToExcel($statisticsSheet, formatRequest($request), range('B', 'E'), [
            'Ngày bắt đầu: ' => 'startTime',
            'Ngày kết thúc: ' => 'endTime',
            'Hãng: ' => 'zManufacturer_ids',
            'Đơn vị quản lý: ' => 'zrefnr_dvql_ids',
        ], 1);
        $spreadsheet->setActiveSheetIndexByName("BIEU_DO");

        $url = writeExcel($spreadsheet, (!empty($type) ? $type : 'bao-cao-so-sanh-doanh-so-giua-cac-nha-san-xuat') . '-' . time() . '.xlsx');

        return [
            'success' => true,
            'url' => $url
        ];
    }
}

if (!function_exists('highPressureStatisticsSalesByManufacture2')) {
    function highPressureStatisticsSalesByManufacture2($request, $type = '')
    {
        $dataArr = getHighPressureStatisticsOptionalTimeData2($request, false, true);

        if (!empty($dataArr['error'])) {
            return $dataArr;
        }

        $baseType = [
            'Gốm + Sứ',
            'Polymer chuỗi',
            'Polymer đứng',
            'Thủy tinh',
        ];

        $arr = [];
        foreach ($dataArr as $groupKey => $group) {
            foreach ($group as $key => $val) {
                $val['type'] = $groupKey;
                $arr[$val['time']][] = $val;
            }
        }

        // Format statistics data
        $statisticsArr = [];
        foreach ($arr as $groupKey => $group) {
            $totalPass = $totalFailed = $total = 0;
            $item = [
                'manufacture' => explode('_', $groupKey)[0],
                'time' => explode('_', $groupKey)[1]
            ];

            foreach ($baseType as $typeKey => $typeVal) {
                $item['pass_' . ($typeKey + 1)] = 0;
                $item['failed_' . ($typeKey + 1)] = 0;
                $item['total_' . ($typeKey + 1)] = 0;
                $item['rate_' . ($typeKey + 1)] = 0;
            }

            foreach ($group as $key => $val) {
                $index = array_search($val['type'], $baseType);

                if ($index !== false) {
                    $item['pass_' . ($index + 1)] = $val['pass_1'];
                    $item['failed_' . ($index + 1)] = $val['failed_1'];
                    $item['total_' . ($index + 1)] = $val['total_1'];
                    $item['rate_' . ($index + 1)] = $val['rate_1'];

                    $totalPass += $val['pass_1'];
                    $totalFailed += $val['failed_1'];
                    $total += $val['total_1'];
                }
            }

            $item['totalPass'] = $totalPass;
            $item['totalFailed'] = $totalFailed;
            $item['total'] = $total;

            array_push($statisticsArr, $item);
        }

        if (empty($type)) {
            usort($statisticsArr, function ($a, $b) use ($statisticsArr) {
                return round($b['total'] / array_sum(array_column($statisticsArr, 'totalPass')) * 100) - round($a['total'] / array_sum(array_column($statisticsArr, 'totalPass')) * 100);
            });
        } else {
            usort($statisticsArr, function ($a, $b) {
                return round($b['totalPass'] / $b['total'] * 100) - round($a['totalPass'] / $a['total'] * 100);
            });
        }

        // Format chart data
        $dataChartArr = [];
        foreach ($dataArr as $groupKey => $group) {
            foreach ($group as $key => $val) {
                $dataChartArr[explode('_', $val['time'])[0]][] = $val;
            }
        }

        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);

        // Read Template Excel
        $template = storage_path('templates_excel/thong_ke_cao_ap/' . $request['directoryName'] . '/' . (!empty($type) ? $type : 'bao-cao-so-sanh-doanh-so-giua-cac-nha-san-xuat') . '/' . (!empty($type) ? $type : 'bao-cao-so-sanh-doanh-so-giua-cac-nha-san-xuat') . '-' . count($dataChartArr) . '.xlsx');
        $spreadsheet = $reader->load($template);

        // Sheet data
        $statisticsSheet = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THONG_KE");
        $chartDataSheet = $spreadsheet->setActiveSheetIndexByName("DATA_BĐ1");

        // Insert data to statistics sheet
        $row = 6;
        $percentagePosition = [
            1 => 'G',
            2 => 'K',
            3 => 'O',
            4 => 'S',
        ];
        foreach ($statisticsArr as $key => $val) {
            $statisticsSheet->getCell('A' . $row)->setValue($key + 1);
            $col = 'B';
            foreach ($val as $item) {
                if (empty($type)) {
                    if (in_array($col, $percentagePosition)) {
                        $positionIndex = array_search($col, $percentagePosition);
                        $statisticsSheet->getCell($col . $row)->setValue(array_sum(array_column($statisticsArr, 'total_' . $positionIndex)) == 0 ? 0 : round($val['total_' . $positionIndex] / array_sum(array_column($statisticsArr, 'total_' . $positionIndex)) * 100, 2));
                    } else {
                        $statisticsSheet->getCell($col . $row)->setValue($item);
                    }
                } else {
                    $statisticsSheet->getCell($col . $row)->setValue($item);
                }
                ++$col;
            }

            if (empty($type)) {
                $statisticsSheet->getCell($col . $row)->setValue(array_sum(array_column($statisticsArr, 'totalPass')) == 0 ? 0 : round($val['total'] / array_sum(array_column($statisticsArr, 'total')) * 100, 2));
            } else {
                $statisticsSheet->getCell($col . $row)->setValue(round($val['totalPass'] / $val['total'] * 100, 2));
            }
            $row++;
        }
        $statisticsSheet->getCell('B' . $row)->setValue('Tổng số');
        $statisticsSheet->getStyle('B' . $row)->applyFromArray([
            'font' => [
                'bold' => true,
            ]
        ]);

        $col = 'D';
        for ($i = 1; $i <= 4; $i++) {
            $statisticsSheet->getCell($col . $row)->setValue(array_sum(array_column($statisticsArr,'pass_' . $i)));
            ++$col;
            $statisticsSheet->getCell($col . $row)->setValue(array_sum(array_column($statisticsArr,'failed_' . $i)));
            ++$col;
            $statisticsSheet->getCell($col . $row)->setValue(array_sum(array_column($statisticsArr,'total_' . $i)));
            ++$col;
            $statisticsSheet->getCell($col . $row)->setValue('100');
            ++$col;
        }
        $statisticsSheet->getCell('T' . $row)->setValue(array_sum(array_column($statisticsArr,'totalPass')));
        $statisticsSheet->getCell('U' . $row)->setValue(array_sum(array_column($statisticsArr,'totalFailed')));
        $statisticsSheet->getCell('V' . $row)->setValue(array_sum(array_column($statisticsArr,'total')));
        if (empty($type)) {
            $statisticsSheet->getCell('W' . $row)->setValue('100');
        } else {
            $statisticsSheet->getCell('W' . $row)->setValue(round(array_sum(array_column($statisticsArr,'totalPass')) / array_sum(array_column($statisticsArr,'total')) * 100, 2));
        }

        $statisticsSheet = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THONG_KE");
        fillSearchRequestToExcel($statisticsSheet, formatRequest($request), range('J', 'M'), [
            'Ngày bắt đầu: ' => 'startTime',
            'Ngày kết thúc: ' => 'endTime',
            'Hãng: ' => 'zManufacturer_ids',
            'Đơn vị quản lý: ' => 'zrefnr_dvql_ids',
        ], 1);
        $spreadsheet->setActiveSheetIndexByName("BIEU_DO");

        // Insert data to chart data sheet
        $row = 7;

        $formattedDataChart = [];
        foreach ($dataChartArr as $key => $val) {
            $item = [];
            $item['manufacture'] = 'Nhà sản xuất ' . $key;

            if (empty($type)) {
                $item['percentage'] = array_sum(array_column($statisticsArr, 'total')) == 0 ? 0 : round(array_sum(array_column($val, 'total_1')) / array_sum(array_column($statisticsArr, 'total')) * 100, 2);
            } else {
                $item['percentage'] = array_sum(array_column($statisticsArr, 'totalPass')) == 0 ? 0 : round(array_sum(array_column($val, 'pass_1')) / array_sum(array_column($statisticsArr, 'totalPass')) * 100, 2);
            }

            array_push($formattedDataChart, $item);
        }
        usort($formattedDataChart, function ($a, $b) {
            return $b['percentage'] - $a['percentage'];
        });

        foreach ($formattedDataChart as $key => $val) {
            $chartDataSheet->getCell('A' . $row)->setValue($key + 1);
            $chartDataSheet->getCell('B' . $row)->setValue($val['manufacture']);
            $chartDataSheet->getCell('C' . $row)->setValue($val['percentage']);
            $row++;
        }

        $spreadsheet->setActiveSheetIndexByName("BIEU_DO");

        $url = writeExcel($spreadsheet, (!empty($type) ? $type : 'bao-cao-so-sanh-doanh-so-giua-cac-nha-san-xuat') . '-' . time() . '.xlsx');

        return [
            'success' => true,
            'url' => $url
        ];
    }
}

if (!function_exists('highPressureStatisticsQualityByManufacture')) {
    function highPressureStatisticsQualityByManufacture($request)
    {
        return highPressureStatisticsSalesByManufacture($request, 'bao-cao-so-sanh-chat-luong-nha-san-xuat');
    }
}

if (!function_exists('highPressureStatisticsQualityByManufacture2')) {
    function highPressureStatisticsQualityByManufacture2($request)
    {
        return highPressureStatisticsSalesByManufacture2($request, 'bao-cao-so-sanh-chat-luong-nha-san-xuat');
    }
}

if (!function_exists('highPressureStatisticsByUnit')) {
    function highPressureStatisticsByUnit($request)
    {
        $request['timeType'] = 3;
        $dataArr = getHighPressureStatisticsOptionalTimeData($request);

        if (!empty($dataArr['error'])) {
            return $dataArr;
        }

        // Reformatted unit data
        $unitArr = $generalArr = $formattedGeneralArr = $checkingArr = [];
        foreach ($dataArr as $key => $val) {
            $unitArr[$key]['order'] = $val['order'];
            $unitArr[$key]['zrefnr_dvql'] = $val['zrefnr_dvql'];
            $unitArr[$key]['time'] = $val['time'];
            $unitArr[$key]['zManufacturer'] = $val['zManufacturer'];
            $unitArr[$key]['total_1'] = $val['total_1'];

            if (!isset($generalArr[$val['zrefnr_dvql'] . '_' . $val['time']])) {
                $generalArr[$val['zrefnr_dvql'] . '_' . $val['time']] = 0;
            }
            $generalArr[$val['zrefnr_dvql'] . '_' . $val['time']] += $val['total_1'];
        }
        usort($unitArr, function ($a, $b) {
            return $b['total_1'] - $a['total_1'];
        });
        // Reformatted general data
        $count = 1;
        foreach ($generalArr as $key => $val) {
            $item['order'] = $count;
            $item['zrefnr_dvql'] = explode('_', $key)[0];
            $item['time'] = explode('_', $key)[1];
            $item['total'] = $val;
            array_push($formattedGeneralArr, $item);
            $checkingArr[$item['zrefnr_dvql']][] = $item;

            $count++;
        }
        usort($formattedGeneralArr, function ($a, $b) {
            return $b['total'] - $a['total'];
        });

        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);

        // Read Template Excel
        $template = storage_path('templates_excel/thong_ke_cao_ap/' . $request['directoryName'] . '/bao-cao-theo-don-vi-su-dung.xlsx');
        $spreadsheet = $reader->load($template);

        // Sheet data
        $generalSheet = $spreadsheet->setActiveSheetIndexByName("BANG_SO_LIEU_TONG_HOP");
        $unitSheet = $spreadsheet->setActiveSheetIndexByName("BANG_SO_LIEU_CHO_TUNG_DON_VI");

        if (count($checkingArr) == 1) {
            // Insert data to statistics sheet
            $row = 5;
            foreach ($unitArr as $key => $val) {
                $col = 'A';
                foreach ($val as $item) {
                    $unitSheet->getCell($col . $row)->setValue($item);
                    ++$col;
                }
                $row++;
            }

            $unitSheet->mergeCells(('B5:B' . ($row - 1)));
            $unitSheet->getCell('C' . $row)->setValue('Tổng số');
            $unitSheet->getStyle('C' . $row)->applyFromArray([
                'font' => [
                    'bold' => true,
                ]
            ]);
            $unitSheet->getCell('E' . $row)->setValue(array_sum(array_column($unitArr,'total_1')));

            fillSearchRequestToExcel($unitSheet, formatRequest($request), range('B', 'E'), [
                'Ngày bắt đầu: ' => 'startTime',
                'Ngày kết thúc: ' => 'endTime',
                'Hãng: ' => 'zManufacturer_ids',
                'Đơn vị quản lý: ' => 'zrefnr_dvql_ids',
            ], 1);
        } else {
            $sheetIndex = $spreadsheet->getIndex(
                $spreadsheet->getSheetByName('BANG_SO_LIEU_CHO_TUNG_DON_VI')
            );
            $spreadsheet->removeSheetByIndex($sheetIndex);
        }

        // Insert data to statistics sheet
        $row = 5;
        foreach ($formattedGeneralArr as $key => $val) {
            $col = 'A';
            foreach ($val as $index => $item) {
                if ($col == 'A') {
                    $generalSheet->getCell($col . $row)->setValue($key + 1);
                } else {
                    $generalSheet->getCell($col . $row)->setValue($item);
                }
                ++$col;
            }
            $row++;
        }

        fillSearchRequestToExcel($generalSheet, formatRequest($request), range('A', 'D'), [
            'Ngày bắt đầu: ' => 'startTime',
            'Ngày kết thúc: ' => 'endTime',
            'Hãng: ' => 'zManufacturer_ids',
            'Đơn vị quản lý: ' => 'zrefnr_dvql_ids',
        ], 1);

        $url = writeExcel($spreadsheet, 'bao-cao-theo-don-vi-su-dung-' . time() . '.xlsx');

        return [
            'success' => true,
            'url' => $url
        ];
    }
}

if (!function_exists('highPressureStatisticsByUnit2')) {
    function highPressureStatisticsByUnit2($request)
    {
        $request['timeType'] = 3;
        $dataArr = getHighPressureStatisticsOptionalTimeData2($request);

        if (!empty($dataArr['error'])) {
            return $dataArr;
        }

        $baseType = [
            'Gốm + Sứ',
            'Polymer chuỗi',
            'Polymer đứng',
            'Thủy tinh',
        ];

        $unitArr = $generalArr = $formattedUnitArr = $formattedGeneralArr = [];
        foreach ($dataArr as $groupKey => $group) {
            foreach ($group as $key => $val) {
                $val['type'] = $groupKey;
                $unitArr[$val['zrefnr_dvql']][$val['zrefnr_dvql'] . '_' . $val['time'] . '_' . $val['zManufacturer']][] = $val;
                $generalArr[$val['zrefnr_dvql'] . '_' . $val['time']][] = $val;
            }
        }

        // Reformatted general data
        foreach ($generalArr as $key => $val) {
            $data = [];
            $data['zrefnr_dvql'] = explode('_', $key)[0];
            $data['time'] = explode('_', $key)[1];

            foreach ($baseType as $typeKey => $typeVal) {
                $data['total_' . ($typeKey + 1)] = 0;
            }

            $data['total'] = 0;

            foreach ($val as $index => $item) {
                $typeIndex = array_search($item['type'], $baseType);
                $data['total_' . ($typeIndex + 1)] += $item['total_1'];
                $data['total'] += $item['total_1'];
            }

            if (!empty($data)) {
                $formattedGeneralArr[] = $data;
            }
        }

        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);

        // Read Template Excel
        $template = storage_path('templates_excel/thong_ke_cao_ap/' . $request['directoryName'] . '/bao-cao-theo-don-vi-su-dung.xlsx');
        $spreadsheet = $reader->load($template);

        // Sheet data
        $generalSheet = $spreadsheet->setActiveSheetIndexByName("BANG_SO_LIEU_TONG_HOP");
        $unitSheet = $spreadsheet->setActiveSheetIndexByName("BANG_SO_LIEU_CHO_TUNG_DON_VI");

        if (count($unitArr) == 1) {
            // Reformatted unit data
            foreach ($unitArr as $groupKey => $groupVal) {
                foreach ($groupVal as $key => $val) {
                    $data = [];
                    $data['zrefnr_dvql'] = explode('_', $key)[0];
                    $data['time'] = explode('_', $key)[1];
                    $data['zManufacturer'] = explode('_', $key)[2];

                    foreach ($baseType as $typeKey => $typeVal) {
                        $data['total_' . ($typeKey + 1)] = 0;
                    }

                    $data['total'] = 0;

                    foreach ($val as $index => $item) {
                        $typeIndex = array_search($item['type'], $baseType);
                        $data['total_' . ($typeIndex + 1)] += $item['total_1'];
                        $data['total'] += $item['total_1'];
                    }

                    if (!empty($data)) {
                        $formattedUnitArr[] = $data;
                    }
                }
            }

            // Insert data to statistics sheet
            $row = 6;
            foreach ($formattedUnitArr as $key => $val) {
                $unitSheet->getCell('A' . $row)->setValue($key + 1);
                $col = 'B';
                foreach ($val as $item) {
                    $unitSheet->getCell($col . $row)->setValue($item);
                    ++$col;
                }
                $row++;
            }

            $unitSheet->mergeCells(('B6:B' . ($row - 1)));
            $unitSheet->getCell('C' . $row)->setValue('Tổng số');
            $unitSheet->getStyle('C' . $row)->applyFromArray([
                'font' => [
                    'bold' => true,
                ]
            ]);
            $unitSheet->getCell('E' . $row)->setValue(array_sum(array_column($formattedUnitArr,'total_1')));
            $unitSheet->getCell('F' . $row)->setValue(array_sum(array_column($formattedUnitArr,'total_2')));
            $unitSheet->getCell('G' . $row)->setValue(array_sum(array_column($formattedUnitArr,'total_3')));
            $unitSheet->getCell('H' . $row)->setValue(array_sum(array_column($formattedUnitArr,'total_4')));
            $unitSheet->getCell('I' . $row)->setValue(array_sum(array_column($formattedUnitArr,'total')));

            fillSearchRequestToExcel($unitSheet, formatRequest($request), range('C', 'F'), [
                'Ngày bắt đầu: ' => 'startTime',
                'Ngày kết thúc: ' => 'endTime',
                'Hãng: ' => 'zManufacturer_ids',
                'Đơn vị quản lý: ' => 'zrefnr_dvql_ids',
            ], 1);
        } else {
            $sheetIndex = $spreadsheet->getIndex(
                $spreadsheet->getSheetByName('BANG_SO_LIEU_CHO_TUNG_DON_VI')
            );
            $spreadsheet->removeSheetByIndex($sheetIndex);
        }

        // Insert data to statistics sheet
        $row = 6;
        foreach ($formattedGeneralArr as $key => $val) {
            $generalSheet->getCell('A' . $row)->setValue($key + 1);
            $col = 'B';
            foreach ($val as $index => $item) {
                $generalSheet->getCell($col . $row)->setValue($item);
                ++$col;
            }
            $row++;
        }

        fillSearchRequestToExcel($generalSheet, formatRequest($request), range('B', 'E'), [
            'Ngày bắt đầu: ' => 'startTime',
            'Ngày kết thúc: ' => 'endTime',
            'Hãng: ' => 'zManufacturer_ids',
            'Đơn vị quản lý: ' => 'zrefnr_dvql_ids',
        ], 1);

        $url = writeExcel($spreadsheet, 'bao-cao-theo-don-vi-su-dung-' . time() . '.xlsx');

        return [
            'success' => true,
            'url' => $url
        ];
    }
}

if (!function_exists('highPressureStatisticsLossQuality')) {
    function highPressureStatisticsLossQuality($request)
    {
        if (!isset($request['zrefnr_dvql_ids']) || count($request['zrefnr_dvql_ids']) > 1) {
            return [
                'error' => ['Đầu vào báo cáo yêu cầu phải chọn 1 đơn vị quản lý']
            ];
        }

        $reports = getReportByCondition($request, [
            'id',
            'zrefnr_dvql',
            'zrefnr_dvql.zsym',
            'zrefnr_td',
            'zrefnr_td.zsym',
            'zrefnr_nl',
            'zrefnr_nl.zsym',
            'zQT.zsym',
            'zlaboratoryDate',
            'zCI_Device',
            'zCI_Device.serial_number',
            'zCI_Device.zCI_Device_Kind.zsym',
            'zYear_of_Manafacture',
            'zYear_of_Manafacture.zsym',
            'zManufacturer',
            'zManufacturer.zsym',
            'class',
            'class.type',
        ], false);

        if (!empty($reports['error'])) {
            return [
                'error' => $reports['error']
            ];
        }

        $deviceArr = [];
        foreach ($reports as $key => $report) {
            array_push($deviceArr, $report['zCI_Device']);

            // Detect obj and attr by report's class type
            if ($report['class.type'] == '13.8. BBTN Máy biến áp phân phối 1 pha') {
                $obj = 'zCA13_8';
                $extendAttr = ['z55num', 'z56num', 'z91num', 'z92num'];
            } else {
                $obj = 'zCA13_9';
                $extendAttr = ['z70pmeas2num', 'z71pcrea2num', 'z157p21num', 'z158p31num'];
            }

            $extendInfo = getDataFromService('doSelect', $obj, $extendAttr, "id = U'" . $report['id'] . "'");

            $reports[$key]['result_1'] = isset($extendInfo[$extendAttr[0]]) && isset($extendInfo[$extendAttr[1]]) ? calculateHighPressureStatisticsLossQuality($extendInfo[$extendAttr[0]], $extendInfo[$extendAttr[1]]) : '';
            $reports[$key]['result_2'] = isset($extendInfo[$extendAttr[2]]) && isset($extendInfo[$extendAttr[3]]) ? calculateHighPressureStatisticsLossQuality($extendInfo[$extendAttr[2]], $extendInfo[$extendAttr[3]]) : '';
        }

        // Constructor Excel Reader
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setIncludeCharts(true);

        // Read Template Excel
        $template = storage_path('templates_excel/thong_ke_cao_ap/may-bien-ap-phan-phoi/bao-cao-thong-ke-chat-luong-ton-hao-mba-phan-phoi.xlsx');
        $spreadsheet = $reader->load($template);

        // Sheet data
        $statisticsSheet = $spreadsheet->setActiveSheetIndexByName("KET_QUA_THONG_KE");

        $row = 5;
        foreach ($reports as $report) {
            $statisticsSheet->getCell('A' . $row)->setValue($row - 4);
            $statisticsSheet->getCell('B' . $row)->setValue(!empty($report['zrefnr_dvql.zsym']) ? $report['zrefnr_dvql.zsym'] : '');
            $statisticsSheet->getCell('C' . $row)->setValue(!empty($report['zrefnr_td.zsym']) ? $report['zrefnr_td.zsym'] : '');
            $statisticsSheet->getCell('D' . $row)->setValue(!empty($report['zrefnr_nl.zsym']) ? $report['zrefnr_nl.zsym'] : '');
            $statisticsSheet->getCell('E' . $row)->setValue(!empty($report['zQT.zsym']) ? $report['zQT.zsym'] : '');
            $statisticsSheet->getCell('F' . $row)->setValue(!empty($report['zlaboratoryDate']) ? date('d/m/Y', $report['zlaboratoryDate']) : '');
            $statisticsSheet->getCell('G' . $row)->setValue(!empty($report['zCI_Device.serial_number']) ? $report['zCI_Device.serial_number'] : '');
            $statisticsSheet->getCell('H' . $row)->setValue(!empty($report['zCI_Device.zCI_Device_Kind.zsym']) ? $report['zCI_Device.zCI_Device_Kind.zsym'] : '');
            $statisticsSheet->getCell('I' . $row)->setValue(!empty($report['zYear_of_Manafacture.zsym']) ? $report['zYear_of_Manafacture.zsym'] : '');
            $statisticsSheet->getCell('J' . $row)->setValue(!empty($report['zManufacturer.zsym']) ? $report['zManufacturer.zsym'] : '');
            $statisticsSheet->getCell('K' . $row)->setValue($report['result_1']);
            $statisticsSheet->getCell('L' . $row)->setValue($report['result_2']);

            for ($col = 'A'; $col != 'M'; ++$col) {
                $statisticsSheet->getStyle($col . $row)->getAlignment()->setWrapText(true);
            }

            $row++;
        }
        $columns = range('C', 'G');
        $inputs = [
            'Ngày bắt đầu: ' => 'startTime',
            'Ngày kết thúc: ' => 'endTime',
            'Hợp đồng thiết bị: ' => 'zContract_Number',
            'Hãng: ' => 'zManufacturer_ids',
            'Đơn vị quản lý: ' => 'zrefnr_dvql_ids',
        ];
        fillSearchRequestToExcel($statisticsSheet, formatRequest($request), $columns, $inputs, 1, true);
        $url = writeExcel($spreadsheet, 'bao-cao-thong-ke-chat-luong-ton-hao-mba-phan-phoi-' . time() . '.xlsx');

        return [
            'success' => true,
            'url' => $url
        ];
    }
}

if (!function_exists('calculateHighPressureStatisticsLossQuality')) {
    function calculateHighPressureStatisticsLossQuality($first, $second)
    {
        if (empty($first) or empty($second)) {
            return '';
        }

        return round(($first - $second) / $second * 100, 2);
    }
}
