<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ModifDbData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modif:dbdata {query}';

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
        $query = $this->argument('query');  //User~role_id=1~email=algoritmer
        $params = explode('~',$query);
        $model = '\\App\\Models\\'.$params[0];
        $where = $params[2];
        $data = explode('=',$params[1]);
        $model::whereRaw($where)->update([$data[0] => $data[1] ]);
        echo "update success";
        return 0;
    }
}
