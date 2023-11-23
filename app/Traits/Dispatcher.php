<?php

namespace App\Traits;

use App\Model\Agent;
use App\Model\ClientPreference;
use App\Model\Order;
use App\Model\Task;
use App\Model\Team;
use App\Model\LocationDistance;
use App\Model\Client;
use App\Model\Timezone;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Model\Countries;
use App\Traits\googleMapApiFunctions;
use Illuminate\Support\Facades\Redis;
use Log;

trait Dispatcher
{
    public function homePage($request)
    {
        $userstatus = $request->userstatus ?? 2;
        $team_ids = $request->team_id ?? '';
        $is_load_html = $request->is_load_html ?? 1;
        $search_by_name = $request->search_by_name ?? '';
        $user = Auth::user();
        $auth = Client::where('code', $user->code)->with(['getAllocation', 'getPreference'])->first();
        $tz = new Timezone();
        $auth->timezone = $tz->timezone_name($user->timezone);

        $date = $request->routedate ? $date = Carbon::parse($request->routedate)->format('Y-m-d') : date('Y-m-d');
        $startdate = date("Y-m-d 00:00:00", strtotime($date));
        $enddate = date("Y-m-d 23:59:59", strtotime($date));

        
        $startdate = Carbon::parse($startdate . @$auth->timezone ?? 'UTC')->tz('UTC');
        $enddate = Carbon::parse($enddate . @$auth->timezone ?? 'UTC')->tz('UTC');
        
        $startdate = $startdate->format('Y-m-d');
        $enddate = $enddate->format('Y-m-d');
        $limit = 10;

        // GET TEAM DATA

    $sql = "SELECT teams.*,
            COUNT(DISTINCT ag.id) AS total_agents,
            SUM(ag.is_available = 1) AS online_agents,
            SUM(ag.is_available = 0) AS offline_agents,
            agents.id AS agent_id,
            agents.name AS agent_name,
            agents.profile_picture AS profile_picture,
            agents.is_available AS is_available,
            COUNT(DISTINCT orders.id) AS order_count,
            COUNT(DISTINCT tasks.id) AS agent_task_count,
            orders.id AS order_id,
            tasks.id AS task_id,
            tasks.task_order,
            locations.id AS location_id,
            locations.latitude,
            locations.longitude
        FROM teams";


        if($userstatus != 2){
           
            $sql .= "
            LEFT JOIN agents AS ag ON ag.team_id = teams.id AND ag.deleted_at IS NULL AND ag.is_available = {$userstatus}
            LEFT JOIN agents ON agents.team_id = teams.id AND agents.deleted_at IS NULL AND agents.is_available = {$userstatus}
            LEFT JOIN agent_logs ON agent_logs.agent_id = agents.id";

           
        }else{
            $sql .= "
            LEFT JOIN agents AS ag ON ag.team_id = teams.id AND ag.deleted_at IS NULL
            LEFT JOIN agents ON agents.team_id = teams.id AND agents.deleted_at IS NULL";
            
        }
        
        $sql .= " LEFT JOIN orders ON orders.driver_id = agents.id AND orders.order_time >= '{$startdate}' AND orders.order_time <= '{$enddate}'
        LEFT JOIN tasks ON tasks.order_id = orders.id
        LEFT JOIN locations ON tasks.location_id = locations.id";
        
      
        if ($user && $user->manager_type == 3 && $user->manual_allocation == 1 && $user->all_team_access != 1) {
            $teamsIdsQuery = "
                SELECT DISTINCT team_id
                FROM sub_admin_team_permissions
                WHERE sub_admin_id = :userId
            ";

            $teamsIds = \DB::select($teamsIdsQuery, ['userId' => $user->id]);
            $teamsIds = (array) $teamsIds[0];
            $sql .= "
                AND EXISTS (
                    SELECT 1
                    FROM teams
                    WHERE teams.id IN (".implode(',', $teamsIds).")
                    AND agents.team_id = teams.id
                )
            ";
        }

        $searchNameSql = '';
        if(!empty($search_by_name)){
            $searchNameSql = " WHERE agents.name LIKE '%$search_by_name%'";
        }

        $teamsIdSql = '';
        if(!empty($team_ids)){
            $team_ids = implode(',', $team_ids);
            if(empty($searchNameSql))
                $teamsIdSql = "WHERE teams.id IN ({$team_ids})";
            else
                $teamsIdSql = " AND teams.id IN ({$team_ids})";
        }

        $sql .= "{$searchNameSql} {$teamsIdSql}  GROUP BY agent_id having `total_agents` > 0";
      
        $total_count  = count(\DB::select($sql));
        $perPage = $limit; // Number of items per page
        $page = $request->input('page', 1);
        
        $offset = ($page - 1) * $perPage;

        $sql .= " LIMIT $perPage OFFSET $offset";
        $teams = \DB::select($sql);
        $lastPage =  ceil($total_count / $perPage);

       //--------------------------------------------------
        // GET ALL TASKS
        //--------------------------------------------------

        // $sql = "SELECT orders.*,
        // (
        //     SELECT JSON_ARRAYAGG(
        //          JSON_OBJECT(
        //              'id', agents.id,
        //              'team_id', agents.team_id,
        //              'name', agents.name,
        //              'agentlog' , (
        //                 SELECT JSON_OBJECT(
        //                     'id', agent_logs.id,
        //                     'device_type', agent_logs.device_type,
        //                     'created_at', agent_logs.created_at,
        //                     'battery_level', agent_logs.battery_level
        //                 ) FROM agent_logs WHERE agent_logs.agent_id = agents.id ORDER BY agent_logs.id DESC LIMIT 1
        //              )
        //          )
        //      ) FROM agents WHERE orders.driver_id = agents.id
        // ) AS agent,
        // (
        //     SELECT JSON_ARRAYAGG(
        //          JSON_OBJECT(
        //              'id', tasks.id,
        //              'task_type_id', tasks.task_type_id,
        //              'task_status', tasks.task_status,
        //              'task_order', tasks.task_order,
        //              'assigned_time', tasks.assigned_time,
        //              'location', (SELECT 
        //                 JSON_OBJECT(
        //                     'id', locations.id,
        //                     'latitude', locations.latitude,
        //                     'longitude', locations.longitude,
        //                     'address', locations.address
        //                 ) FROM locations WHERE tasks.location_id = locations.id
        //             )
        //          )
        //      ) FROM tasks WHERE orders.id = tasks.order_id
        // ) AS task,
        // (
        //     SELECT JSON_ARRAYAGG(
        //          JSON_OBJECT(
        //              'id', customers.id,
        //              'name', customers.name,
        //              'phone_number', customers.phone_number
        //          )
        //      ) FROM customers WHERE orders.customer_id = customers.id
        // ) AS customer
        // FROM orders
        // LEFT JOIN tasks ON orders.id = tasks.order_id
        // LEFT JOIN locations ON tasks.location_id = locations.id
        // LEFT JOIN agents ON orders.driver_id = agents.id
        // LEFT JOIN agent_logs ON agent_logs.agent_id = agents.id
        // LEFT JOIN customers ON orders.customer_id = customers.id
        // -- LEFT JOIN teams ON agents.team_id = teams.id
        // WHERE orders.order_time >= '{$startdate}'
        // AND orders.order_time <= '{$enddate}'
        // AND (
        //         {$user->manager_type} = 3 AND orders.created_by = {$user->id}
        //         OR {$user->manager_type} != 3
        //     )
        // ORDER BY orders.id DESC LIMIT {$limit}";

        $sql = "SELECT 
            orders.*,
            agents.id AS agent_id,
            agents.team_id,
            agents.name AS agent_name,
            agent_logs.id AS agent_log_id,
            agent_logs.device_type,
            agent_logs.created_at,
            agent_logs.battery_level,
            tasks.id AS task_id,
            tasks.task_type_id,
            tasks.task_status,
            tasks.task_order,
            tasks.assigned_time,
            locations.id AS location_id,
            locations.latitude,
            locations.longitude,
            locations.address,
            customers.id AS customer_id,
            customers.name AS customer_name,
            customers.phone_number
        FROM orders
        LEFT JOIN tasks ON orders.id = tasks.order_id
        LEFT JOIN locations ON tasks.location_id = locations.id
        LEFT JOIN agents ON orders.driver_id = agents.id
        LEFT JOIN agent_logs ON agent_logs.agent_id = agents.id
        LEFT JOIN customers ON orders.customer_id = customers.id
        WHERE orders.order_time >= '{$startdate}'
        AND orders.order_time <= '{$enddate}'
        ORDER BY orders.id DESC
        LIMIT {$limit};
        ";
// AND (
//                  {$user->manager_type} = 3 AND orders.created_by = {$user->id}
//                  OR {$user->manager_type} != 3
//             )
        $orders = \DB::select($sql);
        
        $newmarker = [];
     
        foreach ($orders as $key => $order) {
            $append = [];
                switch($order->task_type_id){
                    case 1:
                        $name = 'Pickup';
                    break;
                    case 2:
                        $name = 'DropOff';
                    break;
                    default:
                        $name = 'Appointment';
                    break;
                }
                $append['task_type']             = $name;
                $append['task_id']               = $order->task_id;
                $append['latitude']              = floatval($order->latitude) ?? 0.00;
                $append['longitude']             = floatval($order->longitude) ??  0.00;
                $append['address']               = $order->address ?? '';
                $append['task_type_id']          = $order->task_type_id ?? '';
                $append['task_status']           = (int)$order->task_status;
                $append['team_id']               = @$order->team_id ?? 0;
                $append['driver_name']           = @$order->agent_name ?? '';
                $append['driver_id']             = $order->driver_id ?? '';
                $append['customer_name']         = $order->customer_name ?? '';
                $append['customer_phone_number'] = $order->phone_number ?? '';
                $append['task_order']            = $order->task_order ?? 0;
                $append['agentlog']            = [
                    'id' => $order->agent_log_id ?? '',
                    'device_type' => $order->device_type ?? '',
                    'created_at' => $order->created_at ?? '',
                    'battery_level' => $order->battery_level ?? ''
                ];
                array_push($newmarker, $append);
            // }
        }        
        // GET AGENTS

       
        // $sql = "SELECT 
        //     agents.*,
        //     JSON_OBJECT(
        //         'id', agent_logs.id,
        //         'agent_id', agent_logs.agent_id,
        //         'current_task_id', agent_logs.current_task_id,
        //         'lat', agent_logs.lat,
        //         'long', agent_logs.long,
        //         'battery_level', agent_logs.battery_level,
        //         'os_version', agent_logs.os_version,
        //         'app_version', agent_logs.app_version,
        //         'on_route', agent_logs.on_route,
        //         'device_type', agent_logs.device_type,
        //         'heading_angle', agent_logs.heading_angle,
        //         'distance_covered', agent_logs.distance_covered,
        //         'is_active', agent_logs.is_active
        //     ) AS agentlog,
            
        //     JSON_OBJECT(
        //         'id', fleets.id,
        //         'name', fleets.name,
        //         'registration_name', fleets.registration_name
        //     ) AS get_driver
            
        // FROM agents
        // JOIN agent_logs ON agent_logs.agent_id = agents.id AND agent_logs.lat IS NOT NULL AND agent_logs.long IS NOT NULL
        // LEFT JOIN agent_fleets ON agent_fleets.agent_id = agents.id
        // LEFT JOIN fleets ON agent_fleets.fleet_id = fleets.id
        // WHERE agents.deleted_at IS NULL ";

        $sql = "SELECT
        agents.*,
        agent_logs.id AS agentlog_id,
        agent_logs.agent_id,
        agent_logs.current_task_id,
        agent_logs.lat,
        agent_logs.long,
        agent_logs.battery_level,
        agent_logs.os_version,
        agent_logs.app_version,
        agent_logs.on_route,
        agent_logs.device_type,
        agent_logs.heading_angle,
        agent_logs.distance_covered,
        agent_logs.is_active,
        fleets.id AS fleet_id,
        fleets.name AS fleet_name,
        fleets.registration_name
    FROM agents
    LEFT JOIN (
    SELECT aglogs.*
    FROM (
        SELECT agent_id, MAX(id) AS max_id
        FROM agent_logs
        WHERE `lat` IS NOT NULL AND `long` IS NOT NULL
        GROUP BY agent_id
    ) AS latest_logs
    INNER JOIN agent_logs AS aglogs
    ON latest_logs.agent_id = aglogs.agent_id AND latest_logs.max_id = aglogs.id
    ) AS agent_logs ON agent_logs.agent_id = agents.id
    LEFT JOIN agent_fleets
        ON agent_fleets.agent_id = agents.id
    LEFT JOIN fleets
        ON agent_fleets.fleet_id = fleets.id
    WHERE agents.deleted_at IS NULL";

        if ($user && $user->manager_type == 3 && $user->manual_allocation == 1 && $user->all_team_access != 1) {
            $teamsIdsQuery = "
                SELECT *
                FROM sub_admin_team_permissions
                WHERE sub_admin_id = :userId
            ";

            $teamsIds = \DB::select($teamsIdsQuery, ['userId' => $user->id]);
            $teamsIds = (array) $teamsIds[0];
            
            if(!empty($teamsIds)){
                $teamsIds = implode(',', $teamsIds);
                $sql .= "
                    AND EXISTS (
                        SELECT 1
                        FROM teams
                        WHERE teams.id IN ('{$teamsIds}')
                        AND agents.team_id = teams.id
                    )
                ";
            }
        }
        if($userstatus!=2){
            $sql .= " AND agents.is_available = {$userstatus}";
        }
        $sql .= " GROUP BY agents.id";
        $agents = \DB::select($sql);
        
     
        $j = 0;
        $routeoptimization = array();
        $taskarray = array();
        $points = array();
        $taskids = array();
        $distancematrix = array();
        $uniquedrivers = [];
        $unassigned_orders = [];
        $distancematrix = [];
        $un_total_distance = 0;
        foreach ($agents as $key => $singleagent) {
            $agents[$key] = $singleagent = (array) $singleagent;
            $singleagent['agentlog']['id'] = $singleagent['agentlog_id'] ?? null;
            $singleagent['agentlog']['agent_id'] = $singleagent['id'] ?? null;
            $singleagent['agentlog']['current_task_id'] = $singleagent['current_task_id'] ?? null;
            $singleagent['agentlog']['lat'] = $singleagent['lat'] ?? null;
            $singleagent['agentlog']['long'] = $singleagent['long'] ?? null;
            $singleagent['agentlog']['battery_level'] = $singleagent['battery_level'] ?? null;
            $singleagent['agentlog']['os_version'] = $singleagent['os_version'] ?? null;
            $singleagent['agentlog']['app_version'] = $singleagent['app_version'] ?? null;
            $singleagent['agentlog']['current_speed'] = $singleagent['current_speed'] ?? null;
            $singleagent['agentlog']['on_route'] = $singleagent['on_route'] ?? null;
            $singleagent['agentlog']['app_version'] = $singleagent['app_version'] ?? null;
            $agents[$key]['agentlog'] = $singleagent['agentlog'];
            $agents[$key]['get_driver'] = [
                'id' => $singleagent['fleet_id'],
                'name' => $singleagent['fleet_name'],
                'registration_name' => $singleagent['registration_name']
            ];
            if (is_array($singleagent['agentlog'])) {
                $taskarray = [];
                foreach ($newmarker as $singlemark) {
                    if ($singlemark['driver_id'] == $singleagent['agentlog']['agent_id']) {
                        $taskarray[] = $singlemark;
                    }
                }
                if (!empty($taskarray)) {
                    usort($taskarray, function ($a, $b) {
                        return $a['task_order'] <=> $b['task_order'];
                    });
                    if ($date != date('Y-m-d')) {
                        $singleagent['agentlog']['lat'] = $taskarray[0]['latitude'];
                        $singleagent['agentlog']['long'] = $taskarray[0]['longitude'];
                    }
                    $uniquedrivers[$j]['driver_detail'] = $singleagent['agentlog'];
                    $uniquedrivers[$j]['task_details'] = $taskarray;
                    if(count($uniquedrivers[$j]['task_details']) > 1){
                        $points[] = array(floatval($uniquedrivers[$j]['driver_detail']['lat']),floatval($uniquedrivers[$j]['driver_detail']['long']));
                        foreach ($uniquedrivers[$j]['task_details'] as $singletask) {
                            $points[] = array(floatval($singletask['latitude']),floatval($singletask['longitude']));
                            $taskids[] = $singletask['task_id'];
                        }
                        $taskarray[$uniquedrivers[$j]['driver_detail']['agent_id']] = implode(',', $taskids);
                        $routeoptimization[$uniquedrivers[$j]['driver_detail']['agent_id']] = $points;
                        
                        $distancematrix[$uniquedrivers[$j]['driver_detail']['agent_id']]['tasks'] = $taskarray[$uniquedrivers[$j]['driver_detail']['agent_id']];
                        $distancematrix[$uniquedrivers[$j]['driver_detail']['agent_id']]['distance'] = $routeoptimization[$uniquedrivers[$j]['driver_detail']['agent_id']];
                    }
                    $j++;
                }
            }
        }

        $sql = "SELECT * FROM client_preferences where id = 1 LIMIT 1";
        $clientPreference = \DB::select($sql);
        $clientPreference = $clientPreference[0];
        

        $sql = "SELECT orders.*,
        tasks.id AS task_id,
        tasks.task_order AS task_order
        FROM orders
        LEFT JOIN customers ON orders.customer_id = customers.id
        LEFT JOIN tasks ON orders.id = tasks.order_id
        LEFT JOIN locations ON tasks.location_id = locations.id
        WHERE orders.order_time >= '{$startdate}'
        AND orders.order_time <= '{$enddate}'
        AND orders.status = 'unassigned'
        ";
// AND (
//             {$user->manager_type} = 3 AND orders.created_by = {$user->id}
//             OR {$user->manager_type} != 3
//         )        
        $un_order = \DB::select($sql);
        
       
        $result = [];

        foreach ($un_order as $row) {
            $orderId = $row->id;
        
            // Create an array for the task information
            $task = [
                'id' => $row->task_id,
                'task_order' => $row->task_order,
            ];
        
            // Remove task-specific columns from the main row
            unset($row->task_id, $row->task_order);
        
            // If the order hasn't been added to the result yet, add it with an empty 'task' array
            if (!isset($result[$orderId])) {
                $result[$orderId] = (array)$row; // Convert the row to an array
                $result[$orderId]['tasks'] = [];
            }
        
            // Add the task information to the 'tasks' array for the order
            $result[$orderId]['tasks'][] = $task;
        }
        
        // Convert the associative array to a simple array of order objects
        $un_order = array_values($result);
         
       
        
        if (count($un_order)>=1) {
            $unassigned_orders = $this->splitOrder($un_order);

            if (count($unassigned_orders)>1) {
                $unassigned_distance_mat = array();
                $unassigned_points = [];
                if(!empty($unassigned_orders[0]['task'][0]['location'])){
                    $unassigned_points[] = array(floatval($unassigned_orders[0]['task'][0]['location']['latitude']),floatval($unassigned_orders[0]['task'][0]['location']['longitude']));
                }
                $unassigned_taskids = array();
                $un_route = array();
                foreach ($unassigned_orders as $singleua) {
                    $unassigned_taskids[] = $singleua['task'][0]['id'];
                    if(!empty($singleua['task'][0]['location'])){
                        $unassigned_points[] = array(floatval($singleua['task'][0]['location']['latitude']),floatval($singleua['task'][0]['location']['longitude']));
                    }

                    //for drawing route
                    $s_task = $singleua['task'][0];
                    switch($s_task['task_type_id']){
                        case 1:
                            $name = 'Pickup';
                        break;
                        case 2:
                            $name = 'DropOff';
                        break;
                        default:
                            $name = 'Appointment';
                        break;
                    }
                    $aappend = [];
                    $aappend['task_type'] = $name;
                    $aappend['task_id'] = $s_task['id'];
                    $aappend['latitude'] = $s_task['location']['latitude'] ?? '';
                    $aappend['longitude'] = $s_task['location']['longitude'] ?? '';
                    $aappend['address'] = $s_task['location']['address'] ?? '';
                    $aappend['task_type_id'] = $s_task['task_type_id'];
                    $aappend['task_status'] = $s_task['task_status'];
                    $aappend['team_id'] = 0;
                    $aappend['driver_name'] = '';
                    $aappend['driver_id'] = 0;
                    $aappend['customer_name'] = $singleua['customer']['name'];
                    $aappend['customer_phone_number'] = $singleua['customer']['phone_number'];
                    $aappend['task_order'] = $singleua['task_order'];
                    $un_route[] = $aappend;
                }
                $unassigned_distance_mat['tasks'] = implode(',', $unassigned_taskids);
                $unassigned_distance_mat['distance'] = $unassigned_points;
                $distancematrix[0] = $unassigned_distance_mat;
                $first_un_loc = [];
                if(!empty($unassigned_orders[0]['task'][0]['location'])){
                    $first_un_loc = array('lat'=>floatval($unassigned_orders[0]['task'][0]['location']['latitude']),'long'=>floatval($unassigned_orders[0]['task'][0]['location']['longitude']));
                }
                $final_un_route['driver_detail'] = $first_un_loc;
                $final_un_route['task_details'] = $un_route;
                $uniquedrivers[] = $final_un_route;

                // $gettotal_un_distance = $this->getTotalDistance($unassigned_taskids);

                $un_total_distance = 36.25; //$gettotal_un_distance['total_distance_miles'];
            }
        }

        $getAdminCurrentCountry = Countries::where('id', '=', $user->country_id)->get()->first();
        $defaultCountryLatitude  = $getAdminCurrentCountry->latitude ?? '';
        $defaultCountryLongitude  = $getAdminCurrentCountry->longitude ?? '';
       
        $data = [
            'status' =>"success",
            'client_code' => $user->code,
            'userstatus' => $userstatus,
            'agents' => $agents,
            'routedata' => $uniquedrivers,
            'teams' => $teams,
            'defaultCountryLongitude' => $defaultCountryLongitude,
            'defaultCountryLatitude' => $defaultCountryLatitude,
            'newmarker' => $newmarker??[],
            'distance_matrix' => $distancematrix,
            'sql' => $request->sql,
            'page' => $page,
            'lastPage' => $lastPage
        ];

        if($is_load_html == 1)
        {
            return view('agent_dashboard_task_html_sql')->with($data)->render();
        }else{
            return json_encode($data);
        }

    }
}
