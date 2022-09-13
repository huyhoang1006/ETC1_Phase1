<?php


namespace App\Services;


use GreenCape\Xml\Converter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
class CAWebServices
{
    private $wsdlUrl;
    const TREE_MENU_CACHE_KEY = 'TREE_MENU';
    const LIST_MANUFACTURES_CACHE_KEY = 'LIST_MANUFACTURES';
    const LIST_YEARS_OF_MANUFACTURES_CACHE_KEY = 'LIST_YEARS_OF_MANUFACTURES';

    public function __construct($wsdlUrl)
    {
        $this->wsdlUrl = $wsdlUrl;
    }

    public function getSessionID($userName, $password)
    {
        try {
            $soapClient = new \SoapClient($this->wsdlUrl, array("trace" => TRUE, "soap_version" => SOAP_1_1));
            $resp = $soapClient->__soapCall('login', [[
                'username' => $userName,
                'password' => $password
            ]]);
            return $resp->loginReturn;
        } catch (\Exception $exception) {
            $this->exceptionHandler($exception);
        }
    }

    public function callFunction($functionName, $payload)
    {
        try {
            $soapClient = new \SoapClient($this->wsdlUrl, array("trace" => TRUE, "soap_version" => SOAP_1_1));
            return $soapClient->__soapCall($functionName, [$payload]);
        } catch (\Exception $exception) {
            if (isset($exception->detail) && $exception->detail->ErrorCode == 1010) { // access denied
                $user = session()->get(env('AUTH_SESSION_KEY'));
                $user['ssid'] = $this->getSessionID($user['username'], $user['password']);
                session()->put(env('auth_session_key'), $user);
                $payload['sid'] = $user['ssid'];
                return $this->callFunction($functionName, $payload);
            }
            if (isset($exception->detail) && $exception->detail->ErrorCode == 1000) { // login failed
                return redirect()->back()->withInput()->with('login_error', 'Thông tin đăng nhập không hợp lệ');
            }
            return $exception;
        }

    }

    private function exceptionHandler(\Exception $exception)
    {
        //TODO: logging errors
        //dd($exception->getMessage());
    }

    public function getListHandle($sid, $objectType, $whereStr)
    {
        try {
            $payload = [
                'sid' => $sid,
                'objectType' => $objectType,
                'whereClause' => $whereStr
            ];
            return $this->callFunction('doQuery', $payload)->doQueryReturn;
        } catch (\Exception $exception) {
            exceptionHandle($exception);
        }
    }

    public function getTreeMenu($sid)
    {
        $listDisplay = $this->getListHandle($sid, 'zdisplay', 'active_flag = 1');
        $zDisplay = $this->getAllItemsFromListHandle($sid, $listDisplay, ['zsym', 'id']);
        sortData($zDisplay);
        $listHandleDVQL = $this->getListHandle($sid, 'zDVQL', 'active_flag = 1');
        $zDVQL = $this->getAllItemsFromListHandle($sid, $listHandleDVQL, ['zsym', 'id', 'zdisplay']);
        sortData($zDVQL);
        $zDVQL = collect($zDVQL);
        $this->freeListHandles($sid, $listHandleDVQL->listHandle);
        $listHandleTD = $this->getListHandle($sid, 'zTD', 'active_flag = 1');
        $zTD = $this->getAllItemsFromListHandle($sid, $listHandleTD, ['id', 'zref_dvql', 'zsym']);
        usort($zTD, function($a, $b) {
            return strcmp(strtolower(@$a['zsym']), strtolower(@$b['zsym']));
        });
        $zTD = collect($zTD);
        $listHandleNL = $this->getListHandle($sid, 'zNL', 'active_flag = 1');
        $zNL =$this->getAllItemsFromListHandle($sid, $listHandleNL, ['id', 'zref_td', 'zsym']);
        usort($zNL, function($a, $b) {
            return strcmp(strtolower(@$a['zsym']), strtolower(@$b['zsym']));
        });
        $zNL = collect($zNL);
        $this->freeListHandles($sid, $listHandleNL->listHandle);
        $data = [];
        foreach($zDisplay as $zdis){
            $filterDVQL = $zDVQL->filter(function ($dvql) use ($zdis) {
                return @$dvql['zdisplay'] == $zdis['id'];
            });
            $zdis['dvql'] = $filterDVQL->toArray();
            foreach ($zdis['dvql'] as $index => $dvql) {
                $filteredTD = $zTD->filter(function ($td) use ($dvql) {
                    return $td['zref_dvql'] == $dvql['id'];
                });
                $dvql['td'] = $filteredTD->toArray();
                $zdis['dvql'][$index] = $dvql;
                foreach ($dvql['td'] as $i => $td) {
                    $filteredNL = $zNL->filter(function ($nl) use ($td) {
                        return $nl['zref_td'] == $td['id'];
                    });
                    $td['nl'] = $filteredNL->toArray();
                    $zdis['dvql'][$index]['td'][$i] = $td;
                }
            }
            array_push($data, $zdis);
        }
        Cache::forever(self::TREE_MENU_CACHE_KEY, $data);
        return $data;
    }

    public function getCachedTreeMenu($sid)
    {
        if (Cache::has(self::TREE_MENU_CACHE_KEY)) {
            return Cache::get(self::TREE_MENU_CACHE_KEY);
        }
        return $this->getTreeMenu($sid);
    }

    public function getManufactures($sid) {
        return Cache::remember(self::LIST_MANUFACTURES_CACHE_KEY, 7200, function () use ($sid) {
            $listHandle = $this->getListHandle($sid, 'zManufacturer', ' zsym IS NOT NULL ');
            $items = collect($this->getAllItemsFromListHandle($sid, $listHandle, ['id','zsym']))
                ->sortBy(function ($item) {
                    return iconv('UTF-8', 'ASCII//TRANSLIT', $item['zsym']);
                });
            $this->freeListHandles($sid, $listHandle->listHandle);
            return $items;
        });
    }

    public function getYearOfManufactures($sid) {
        return Cache::remember(self::LIST_YEARS_OF_MANUFACTURES_CACHE_KEY, 7200, function () use ($sid) {
            $listHandle = $this->getListHandle($sid, 'zYear_of_Manafacture', '');
            $items = collect($this->getAllItemsFromListHandle($sid, $listHandle, ['id','zsym']))
                ->sortBy(function ($item) {
                    return iconv('UTF-8', 'ASCII//TRANSLIT', $item['zsym']);
                });
            $this->freeListHandles($sid, $listHandle->listHandle);
            return $items;
        });
    }

    public function getAllItemsFromListHandle($sid, $listHandle, $attrs)
    {
        $start = 0;
        $limit = 150;

        $items = [];
        do {
            $end = $start + $limit;
            if ($end >= $listHandle->listLength) {
                $end = $listHandle->listLength - 1;
            }
            $resp = $this->getListValues($sid, $listHandle->listHandle, $start, $end, $attrs);
            if (!$resp) {
                break;
            }
            $items = array_merge($items, $resp);
            $start = $start + $limit + 1;

        } while ($start <= ($listHandle->listLength - 1));
        return $items;
    }

    public function getListValues($sid, $listHandleId, $start, $end, $attrs)
    {
        try {
            $payload = [
                'sid' => $sid,
                'listHandle' => $listHandleId,
                'startIndex' => $start,
                'endIndex' => $end,
                'attributeNames' => $attrs
            ];
            $resp = $this->callFunction('getListValues', $payload);
            $parser = new Converter($resp->getListValuesReturn);
            if (!isset($parser->data['UDSObjectList'][0])) {
                $parser->data['UDSObjectList'] = [$parser->data['UDSObjectList']];
            }
            $data = [];
            foreach ($parser->data['UDSObjectList'] as $obj) {
                if (!$obj) continue;
                foreach ($obj['UDSObject'][1]['Attributes'] as $attr) {
                    $item[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                }
                array_push($data, $item);
                unset($item);
            }
            return $data;
        } catch (\Exception $exception) {
            Log::error('[CA Service] get list values: Fail | Reason: ' . $exception->getMessage() . '. ' . $exception->getFile() . ':' . $exception->getLine() . '| Params: ' . json_encode([
                'sid' => $sid,
                'listHandleId' => $listHandleId,
                'start' => $start,
                'end' => $end,
                'attrs' => $attrs,
            ]));
            exceptionHandle($exception);
        }
    }

    public function freeListHandles($sid, $listHandleId)
    {
        try {
            $payload = [
                'sid' => $sid,
                'handles' => $listHandleId
            ];
            $this->callFunction('freeListHandles', $payload);
        } catch (\Exception $exception) {
            $this->exceptionHandler($exception);
        }
    }
}
