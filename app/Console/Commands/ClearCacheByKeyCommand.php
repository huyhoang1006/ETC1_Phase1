<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearCacheByKeyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cmd:clear-cache {key}';

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
        $keys = explode(',', $this->argument('key'));
        foreach($keys as $key){
            if(Cache::has($key)){
                Cache::forget($key);
                $this->info("Clear cache key {$key} success!");
            }else{
                $this->error("Key: {$key} does not exist!");   
            }
        }
        
    }
}
