<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Config;
use App\Model\Client;
use Exception;
use DB;


class RunSingleSeederCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seeder:run {--seedername=}';

    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'this command for re-run the particular seeder in this command we have to pass seeder name for target that seeder';

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
        $seeder_name = $this->option('seedername'); 

        $clients = Client::where('status', 1)->get();
      
        //$clients = Client::all();
        foreach ($clients as $key => $client) {
            $database_name = 'db_' . $client->database_name;
            $this->info("select database start: {$database_name}!");
            $query = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME =  ?";
            $db = DB::select($query, [$database_name]);
            
            if ($db) {


                $database_name = 'db_' . $client->database_name;
                $default = [
                    'driver' => env('DB_CONNECTION', 'mysql'),
                    'host' => config('database.connections.mysql.host'),
                    'port' => config('database.connections.mysql.port'),
                    'database' => $database_name,
                    'username' => config('database.connections.mysql.username'),
                    'password' => config('database.connections.mysql.password'),
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'prefix_indexes' => true,
                    'strict' => false,
                    'engine' => null
                ];



                Config::set("database.connections.$database_name", $default);

                Artisan::call('db:seed', ['--class'=>$seeder_name,'--database' => $database_name]);

                \DB::disconnect($database_name);
            }else{
                DB::disconnect($database_name);
                $this->info("migrate database end: {$database_name}!");
            }
        }

        return 0;
    }
}
