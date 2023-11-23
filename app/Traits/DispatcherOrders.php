<?php

namespace App\Traits;

use App\Model\Client;
use App\Model\Timezone;
use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;

trait DispatcherOrders
{
    public function orderData($request)
    {
        $sql = "SELECT *
        FROM client_preferences
        WHERE id = 1
        LIMIT 1";
        $clientPreference = \DB::select($sql);
        $limit = 10;
        $userstatus = isset($request->userstatus) ? $request->userstatus : 2;
        $checkuserroutes = isset($request->checkuserroutes) ? $request->checkuserroutes : '';
        $team_ids = isset($request->team_id) ? $request->team_id : '';
        $is_load_html = isset($request->is_load_html) ? $request->is_load_html : 1;
        $search_by_name = isset($request->search_by_name) ? $request->search_by_name : '';
        $agent_ids = isset($request->agent_id) ? $request->agent_id : '';
        $branchId = $request->branchId;
        $user = Auth::user();
        $auth = Client::where('code', $user->code)->with(['getAllocation', 'getPreference'])->first();
        

        $tz = new Timezone();
        $auth->timezone = $tz->timezone_name($user->timezone);

        if(isset($request->routedate)) {
            $date = Carbon::parse(strtotime($request->routedate))->format('Y-m-d');
        }else{
            $date = date('Y-m-d');
        }
        $startdate = date("Y-m-d 00:00:00", strtotime($date));
        $enddate = date("Y-m-d 23:59:59", strtotime($date));


        $startdate = Carbon::parse($startdate . @$auth->timezone ?? 'UTC')->tz('UTC');
        $enddate = Carbon::parse($enddate . @$auth->timezone ?? 'UTC')->tz('UTC');




        // $sql = "SELECT teams.*,
        // COUNT(DISTINCT ag.id) as total_agents,
        // (SELECT count(ags.id) from agents as  ags where ags.team_id = teams.id AND ags.deleted_at IS NULL AND ags.is_available = 1) as total_online_agents,
        // (SELECT count(ags.id) from agents as  ags where ags.team_id = teams.id AND ags.deleted_at IS NULL AND ags.is_available = 0) as total_offline_agents,
        // (
        //     SELECT JSON_ARRAYAGG(
        //          JSON_OBJECT(
        //              'id', agents.id,
        //              'name', agents.name,
        //              'image_url', agents.profile_picture,
        //              'is_available', agents.is_available,
        //              'order_count', (
        //                 SELECT COUNT(DISTINCT orders.id)
        //                 FROM orders
        //                 WHERE orders.driver_id = agents.id
        //                 ),
        //             'agent_task_count', (
        //                 SELECT COUNT(DISTINCT tasks.id)
        //                     FROM tasks
        //                     WHERE tasks.order_id = orders.id
        //                 ),
        //             'order', (
        //                 SELECT JSON_ARRAYAGG(
        //                     JSON_OBJECT('id', orders.id,
        //                     'status',orders.status,
        //                     'base_duration',orders.base_duration,
        //                     'task', (
        //                         SELECT JSON_ARRAYAGG(
        //                             JSON_OBJECT(
        //                                 'id', tasks.id,
        //                                 'task_order', tasks.task_order,
        //                                 'task_type_id', tasks.task_type_id,
        //                                 'assigned_time',tasks.assigned_time,
        //                                 'location', (SELECT JSON_ARRAYAGG(
        //                                         JSON_OBJECT(
        //                                             'id', locations.id,
        //                                             'latitude', locations.latitude,
        //                                             'longitude', locations.longitude
        //                                         )
        //                                     ) FROM locations WHERE tasks.location_id = locations.id
        //                                 )
        //                             )
        //                         ) FROM tasks WHERE tasks.order_id = orders.id
        //                     ))    
        //                 ) FROM orders WHERE orders.driver_id = agents.id
        //             )
        //             -- 'agentlog', (
        //             --     SELECT JSON_ARRAYAGG(
        //             --         JSON_OBJECT(
        //             --             'id', agent_logs.id,
        //             --             'lat', CAST(agent_logs.lat AS CHAR),
        //             --             'long', CAST(agent_logs.long AS CHAR)
        //             --         )
        //             --     )
        //             --     FROM agent_logs
        //             --     WHERE agent_logs.agent_id = agents.id
        //             -- )
        //          )
        //      ) FROM agents WHERE agents.team_id = teams.id AND agents.deleted_at IS NULL
        // ) AS agents
        // FROM teams
        // LEFT JOIN agents as ag ON ag.team_id = teams.id AND ag.deleted_at IS NULL
        // LEFT JOIN orders ON orders.driver_id = ag.id AND orders.order_time >= '{$startdate}' AND orders.order_time <= '{$enddate}'
        // LEFT JOIN tasks ON tasks.order_id = orders.id";

        $sql = "SELECT teams.*,
        COUNT(DISTINCT ag.id) AS total_agents,
        SUM(ag.is_available = 1) AS total_online_agents,
        SUM(ag.is_available = 0) AS total_offline_agents,
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
        FROM teams
        LEFT JOIN agents AS ag ON ag.team_id = teams.id AND ag.deleted_at IS NULL
        LEFT JOIN agents ON agents.team_id = teams.id AND agents.deleted_at IS NULL
        LEFT JOIN orders ON orders.driver_id = agents.id AND orders.order_time >= '{$startdate}' AND orders.order_time <= '{$enddate}'
        LEFT JOIN tasks ON tasks.order_id = orders.id
        LEFT JOIN locations ON tasks.location_id = locations.id
        ";

        if($userstatus != 2){
            $sql .= "LEFT JOIN agent_logs ON agent_logs.agent_id = agents.id
            AND agents.is_available = {$userstatus}";
        }
        if(!empty($search_by_name)){
            $sql .= "AND LIKE agents.name = '%$search_by_name%')";
        }
        
        if(!empty($team_ids)){
            $team_ids = implode(',', $team_ids);
            $sql .= "AND teams.id IN ({$team_ids})";
        }

        $sql .= " GROUP BY teams.id";

        $teams =(array) \DB::select($sql);

        foreach ($teams as $key =>$team) {
            $teams[$key] = $team = (array) $team;
            $team['online_agents']  = $team['total_online_agents'];
            $team['offline_agents'] = $team['total_offline_agents'];
            // $team['agents'] = json_decode($team['agents'], true);
        }

        //UNASSGINED ORDERS

        // if($user->manager_type==3){
        //     $un_order  = Order::where('created_by',$user->id)->where('order_time', '>=', $startdate)->where('order_time', '<=', $enddate)->where('status', 'unassigned')->with(['customer', 'task.location'])->get();
        //     }else{
        //         $un_order  = Order::where('order_time', '>=', $startdate)->where('order_time', '<=', $enddate)->where('status', 'unassigned')->with(['customer', 'task.location'])->get();
        //     }
        //     if($branchId){
        //         $un_order = $un_order->where('created_by', $branchId);
        //     }
        // pr($un_order->toArray());

        $sql = "SELECT *
        FROM orders
        LEFT JOIN customers ON orders.customer_id = customers.id
        LEFT JOIN tasks ON orders.id = tasks.order_id
        LEFT JOIN locations ON tasks.location_id = locations.id
        WHERE orders.order_time >= '{$startdate}'
        AND orders.order_time <= '{$enddate}'
        AND orders.status = 'unassigned'";
//         AND (
//             {$user->manager_type} = 3 AND orders.created_by = {$user->id}
//             OR {$user->manager_type} != 3
//         )";

        $un_order = \DB::select($sql);


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
        //     )";

        $perPage = $limit; // Number of items per page
        $page = $request->input('page', 1);
        
        $offset = ($page - 1) * $perPage;

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
            locations.short_name,
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
        AND orders.order_time <= '{$enddate}'";
//         AND (
//             {$user->manager_type} = 3 AND orders.created_by = {$user->id}
//             OR {$user->manager_type} != 3
//         )";

        if(!empty($checkuserroutes)){
            $sql .= " AND orders.status = '{$checkuserroutes}'";
        }
        if(!empty($agent_ids)){
            $sql .= " AND orders.driver_id IN (".implode(',', $agent_ids).")";
        }

        $count = "{$sql} GROUP BY orders.id ORDER BY orders.id DESC";
        
        $count = \DB::select($count);
        
        $totalcount = count($count);
      
        $lastPage =  ceil($totalcount / $perPage);
        
        $sql .= " GROUP BY orders.id ORDER BY orders.id DESC LIMIT $perPage OFFSET $offset";
        $un_order = \DB::select($sql);

        $uniquedrivers = [];
        $unassigned_orders = [];
        $un_total_distance = 26.25;
        $distancematrix = [];
        $tasks = [];
        if (count($un_order)>=1) {
            $unassigned_orders = (array)$un_order;
                $unassigned_distance_mat = [];
                $unassigned_points = [];
                $unassigned_taskids = [];
                $un_route = [];
                $unassigned_orders[0] = (array) $unassigned_orders[0];
                $unassigned_points[] = [floatval($unassigned_orders[0]['latitude'] ?? ''), floatval($unassigned_orders[0]['longitude'] ?? '')];
                
                foreach ($unassigned_orders as $k => $singleua) {
                    $unassigned_orders[$k] = $singleua = (array) $singleua;
                    $unassigned_taskids[] = $singleua['task_id'] ?? '';// $singleua['task'][0]['id'];
                    $unassigned_orders[$k]['task_order'] = $singleua['task_order'] ?? ''; //['task'][0]['task_order'];
                    // $unassigned_task[] = $singleua['task'];
                    if(!empty($singleua['latitude']) && !empty($singleua['longitude'])){
                        $unassigned_points[] = [floatval($singleua['latitude']), floatval($singleua['longitude'])];
                    }

                    //collect tasks

                    $tasks[$singleua['id']][] = [
                        'id' => $singleua['task_id'],
                        'task_type_id' => $singleua['task_type_id'],
                        'assigned_time' => $singleua['assigned_time'],
                        'task_status' => $singleua['task_status'],
                        'task_order' => $singleua['task_order']
                    ];

                    //for drawing route
                    switch($singleua['task_type_id']){
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
                    $aappend = array();
                    $aappend['task_type']             = $name;
                    $aappend['task_id']               = $singleua['task_id'];
                    $aappend['latitude']              = $singleua['latitude'] ?? '';
                    $aappend['longitude']             = $singleua['longitude'] ?? '';
                    $aappend['address']               = $singleua['address'] ?? '';
                    $aappend['task_type_id']          = $singleua['task_type_id'];
                    $aappend['task_status']           = $singleua['task_status'];
                    $aappend['team_id']               = 0;
                    $aappend['driver_name']           = '';
                    $aappend['driver_id']             = 0;
                    $aappend['customer_name']         = $singleua['customer_name'] ?? '';
                    $aappend['customer_phone_number'] = $singleua['phone_number'] ?? '';
                    $aappend['task_order']            = $singleua['task_order'] ?? '';
                    $un_route[] = $aappend;
                }
                
                $unassigned_distance_mat['tasks'] = implode(',', $unassigned_taskids);
                $unassigned_distance_mat['distance'] = $unassigned_points;
                $distancematrix[0] = $unassigned_distance_mat;
                $first_un_loc = [];
                if(!empty($singleua['latitude']) && !empty($singleua['longitude'])){
                    $first_un_loc = array('lat'=> floatval($singleua['latitude']),'long'=> floatval($singleua['longitude']));
                }
                $final_un_route['driver_detail'] = $first_un_loc;
                $final_un_route['task_details'] = $un_route;
                $uniquedrivers[] = $final_un_route;

                // $gettotal_un_distance = $this->getTotalDistance($unassigned_task);

                $un_total_distance = 36.46;//$gettotal_un_distance['total_distance_miles'];
            // }
        }
        $clientPreference = $clientPreference[0];
        $googleapikey = $clientPreference->map_key_1 ?? '';

        $sql = "SELECT * FROM countries WHERE id = {$user->country_id} LIMIT 1";
        $getAdminCurrentCountry = \DB::select($sql);
        if(!empty($getAdminCurrentCountry)){
            $getAdminCurrentCountry = $getAdminCurrentCountry[0];
            $defaultCountryLatitude  = $getAdminCurrentCountry->latitude;
            $defaultCountryLongitude  = $getAdminCurrentCountry->longitude;
        }else{
            $defaultCountryLatitude  = '';
            $defaultCountryLongitude  = '';
        }
        
        $data = [
            'status' =>"success",
            'teams'=> $teams,
            'userstatus' => $userstatus,
            'client_code' => $user->code,
            'defaultCountryLongitude' => $defaultCountryLongitude,
            'defaultCountryLatitude' => $defaultCountryLatitude,
            'date'=> $date,
            'preference' => $clientPreference,
            'routedata' => $uniquedrivers,
            'distance_matrix' => $distancematrix,
            'unassigned_orders' => $unassigned_orders,
            'unassigned_distance' => $un_total_distance,
            'map_key'=>$googleapikey,
            'client_timezone'=>$auth->timezone,
            'checkuserroutes' => $checkuserroutes,
            'agent_ids' => $agent_ids,
            'tasks' => $tasks,
            'page' => $page,
            'lastPage' => $lastPage
        ];

        if($is_load_html == 1)
        {
            return view('agent_dashboard_order_html', compact('un_order'))->with($data)->render();
        }else{
            return json_encode($data);
        }

    }
}

