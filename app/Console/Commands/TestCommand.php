<?php

namespace App\Console\Commands;

use alhimik1986\PhpExcelTemplator\PhpExcelTemplator;
use App\Services\CAWebServices;
use GreenCape\Xml\Converter;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\Shared\ZipArchive;
use PhpOffice\PhpWord\TemplateProcessor;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cmd:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        dd(date('Y-m-d H:i:s', 1626945981213));
    //    $sid = $this->getSessionId();dd($sid);
        $sid = 1004898093;
        $service = new CAWebServices(env('CA_WSDL_URL'));
        $menu = $service->getTreeMenu($sid);
        dd($menu);
        $a = $this->doSelect($sid, '',[]);
        dd($a);
        $attrs = [ pu
//            'id',
//            'class.type',
//            'zCI_Device.name',
//            'zCI_Device.zCI_Device_Type',
//            'zCI_Device.zCustomer',
        ];
//        $devices = $this->doSelect($sid, 'Test cao ap 001', $attrs );
//        dd($devices);

        $data = $this->getObjectValues($sid, 'nr:0107FF3EF723FA4DA92FC90385F5FC0C', []);
        dd($data);
    }

    function getSessionId()
    {
        $service = new CAWebServices(env('CA_WSDL_URL'));
        return $service->getSessionID(env('CA_USERNAME'), env('CA_PASSWORD'));
    }

    function getObjectValues($sid, $objectHandle, $attrs) {
        $service = new CAWebServices(env('CA_WSDL_URL'));
        $data = [];
        try {
            $payload = [
                'sid' => $sid,
                'objectHandle' => $objectHandle,
                'attributes' => $attrs
            ];
            $resp = $service->callFunction('getObjectValues', $payload);
            $parser = new Converter($resp->getObjectValuesReturn);
            foreach ($parser->data['UDSObject'][1]['Attributes'] as $attr) {
                if ($attr['Attribute'][1]['AttrValue']) {
                    $data[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                }
            }
        } catch (\Exception $exception) {
            $this->exceptionHandler($exception);
        }
        return $data;
    }

    function doSelect($sid, $name, $attrs = [], $limit = 10)
    {
        $service = new CAWebServices(env('CA_WSDL_URL'));
        $payload = [
            'sid' => $sid,
            'objectType' => 'zNL',
            'whereClause' => "zdelete_flag = 1",
            'maxRows' => $limit,
            'attributes' => $attrs
        ];
        $resp = $service->callFunction('doSelect', $payload);
        return $this->listParser($resp->doSelectReturn);
    }

    function listParser($xml)
    {
        try {
            file_put_contents(storage_path('qwe.xml'), $xml);
            $data = [];
            $parser = new Converter($xml);
            if (!isset($parser->data['UDSObjectList'][0])) {
                $parser->data['UDSObjectList'] = [$parser->data['UDSObjectList']];
            }
            foreach ($parser->data['UDSObjectList'] as $obj) {
                if (!$obj) continue;
                foreach ($obj['UDSObject'][1]['Attributes'] as $attr) {
                    if ($attr['Attribute'][1]['AttrValue']) {
                        $item[$attr['Attribute'][0]['AttrName']] = $attr['Attribute'][1]['AttrValue'];
                    }
                }
                array_push($data, $item);
                unset($item);
            }
            return $data;
        } catch
        (\Exception $exception) {
            exceptionHandle($exception);
            dd($exception->getMessage());
        }
    }

}
//  php artisan cmd:test
