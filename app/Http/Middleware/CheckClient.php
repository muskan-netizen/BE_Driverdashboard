<?php

namespace App\Http\Middleware;

use App\Model\Client;
use Illuminate\Support\Facades\Cache;
use Request;
use Config;
use Illuminate\Support\Facades\DB;

use Closure;

class CheckClient
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $url = Request::url();
        $new_url = str_replace(array('http://', '.test.com/login'), '', $url);
        $database = 'dispatcher';
        $client = Cache::get($database);
        $database_name = '';
        if (isset($client)) {
            $database_name = 'db_' . $client['database_name'];
            
        } else {
            $database_serch = Client::where('database_name', $database)->first();
            if (isset($database_serch)) {
                $database_name = 'db_' . $database_serch->database_name;
                Cache::set($database_serch->database_name, $database_serch);
            } 
        }
            
        if (isset($database_name)) {
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
            Config::set("client_connected", true);
            Config::set("client_data", $client);
            DB::setDefaultConnection($database_name);
            DB::purge($database_name);


            return $next($request);
        }
        abort(404);
    }
}
