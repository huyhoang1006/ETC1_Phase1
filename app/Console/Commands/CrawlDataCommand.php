<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CrawlDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cmd:crawl';
    const JWT_TOKEN = 'eyJhbGciOiJSUzI1NiIsImtpZCI6IjZCN0FDQzUyMDMwNUJGREI0RjcyNTJEQUVCMjE3N0NDMDkxRkFBRTEiLCJ0eXAiOiJKV1QiLCJ4NXQiOiJhM3JNVWdNRnY5dFBjbExhNnlGM3pBa2ZxdUUifQ.eyJuYmYiOjE2MjM5ODMwNjUsImV4cCI6MTYyNjU3NTA2NSwiaXNzIjoiaHR0cDovL2xvZ2luLm5wY2V0Yy52biIsImF1ZCI6WyJodHRwOi8vbG9naW4ubnBjZXRjLnZuL3Jlc291cmNlcyIsIm5wY19hcGkiXSwiY2xpZW50X2lkIjoicm8uY2xpZW50Iiwic3ViIjoiMjg5OSIsImF1dGhfdGltZSI6MTYyMzk4MzA2NSwiaWRwIjoibG9jYWwiLCJ1c2VyX2lkIjoiMjg5OSIsIm5hbWUiOiJUcuG6p24gVGhhbmggU8ahbiIsImVtYWlsIjoic29udHRAZ21haWwuY29tIiwidGltZXN0ZW1wIjoiNGJkOWYwYjItMjcyMy00Mjc5LWEzOTUtZjY0OGU5NGQ5ZTA4IiwicGVybWlzc2lvbnMiOiIiLCJzY29wZSI6WyJvcGVuaWQiLCJucGNfYXBpIiwib2ZmbGluZV9hY2Nlc3MiXSwiYW1yIjpbImN1c3RvbSJdfQ.G4ZxDyHkwaN1zHchAYg32MbraaLI64nkAyAeVJBn6AzE-CAh2-397VApeMd_il3-Cjbww0M_rq8O-UscaBiHG9OnKftjbsZ7_vkNB4IwD0G6ReOldKEuS_ybkvleb-V2P96ZX1XZ9MonEVkqFL5TjfxbCovCMFpvpYDav49cZA2D8OpD0SXgKvOY_p5MpFGTHzlqLekRxbsRerClQYzM1oP5bOF69s1PsE_fGn6xBHCPWNJOTjxmw9-bi-RY-O1Z7QrJpqsquClExlKgZb7rXNLpO7S7iWNgbM5zbOSL8dfzCgrkrhpmNw3xtpuxs8kyBZBylB6k8Oy4iL4xCeQPSQ';

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
//        $parents = json_decode($this->callAPI('http://api.npcetc.vn/api/city/get_equipment_master_trees', 'GET'));
//        file_put_contents(storage_path('jsons/parents.json'), json_encode($parents->data));
//        $cities = $this->getListCities();
//        file_put_contents(storage_path('jsons/cities.json'), json_encode($cities));
//        $blocks = $this->getListBlock();
//                file_put_contents(storage_path('jsons/blocks.json'), json_encode($blocks));
        $items = $this->getDevices();
        file_put_contents(storage_path('jsons/devices.json'), json_encode($items));
    }

    function getDevices() {
        $items = json_decode(file_get_contents(storage_path('jsons/blocks.json')));
        // {"id":77965,"cityId":1393,"stationId":40202,"code":"T\u1ee7 AC,DC","name":"T\u1ee7 AC,DC","description":null,"order":null,"cityName":"H\u01afNG Y\u00caN","stationName":"110kV B\u00e3i S\u1eady"}
        $data = [];
        $total = count($items);
        foreach ($items as $index => $item) {
            try {
                echo date('Y-m-d H:i:s') . sprintf(' PROCESSING %s/%s', $index, $total) . PHP_EOL;
                $apiResponse = json_decode($this->callAPI('http://api.npcetc.vn/api/electrical_equipment/filter','POST', [
                    'cityId' => $item->cityId,
                    'blockingRoadId' => $item->id,
                    'stationId' => $item->stationId,
                    "search" => '',
                    "departmentId" => 15,
                ]));
                $data = array_merge($data, $apiResponse->data->list);
                if ($index == 3000) {
                    break;
                }
            } catch (\Exception $exception) {

            }

        }
        return $data;
    }

    function callAPI($url, $method, $data = null) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);


        $headers = array();
        $headers[] = 'Accept: application/json';
        $headers[] = 'Authorization: Bearer ' . self::JWT_TOKEN;
        $headers[] = 'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.101 Safari/537.36';
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($data && $method == 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        return $result;
    }

    function getListCities() {
        $items = json_decode(file_get_contents(storage_path('jsons/parents.json')));
        $data = [];
        $total = count($items);
        foreach ($items as $index => $item) {
            if ($item->children && is_array($item->children)) {
                echo date('Y-m-d H:i:s') . sprintf(' PROCESSING %s/%s', $index, $total) . PHP_EOL;
                $totalChild = count($item->children);
                foreach ($item->children as $i => $child) {
                    echo date('Y-m-d H:i:s') . sprintf(' PROCESSING CHILD: %s/%s', $i, $totalChild). PHP_EOL;
                    $apiResponse = json_decode($this->callAPI('http://api.npcetc.vn/api/station/filter','POST', [
                        'cityId' => $child->id,
                        'paging' => [
                            'currentPage' => 1,
                            'pageSize' => 100
                        ]
                    ]));
                    $data = array_merge($data, $apiResponse->data->list);
                }
            }
        }
        return $data;
    }

    function getListBlock() {
        $items = json_decode(file_get_contents(storage_path('jsons/cities.json')));
        $data = [];
        $total = count($items);
        foreach ($items as $index => $item) {
            echo date('Y-m-d H:i:s') . sprintf(' PROCESSING %s/%s', $index, $total) . PHP_EOL;
            $apiResponse = json_decode($this->callAPI('http://api.npcetc.vn/api/blockingroad/filter','POST', [
                'cityId' => $item->cityId,
                'stationId' => $item->id,
                'paging' => [
                    'currentPage' => 1,
                    'pageSize' => 100
                ]
            ]));
            $data = array_merge($data, $apiResponse->data->list);

        }
        return $data;
    }
}
