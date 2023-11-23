<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use GuzzleHttp\Client;
use App\Model\ClientPreference;
use App\Model\Location;
use App\Model\Task;
use App\Model\Warehouse;
use Log;
use Illuminate\Support\Facades\DB;
use SplPriorityQueue;
use Unifonic;

trait DispatcherRouteAllocation
{

    public function findNearestWarehouse($data, $is_last = null, $warehouse = null)
    {

        $query = "
            SELECT
                id,
                latitude,
                longitude,
                address,
                ROUND(
                    6371 * ACOS(
                        COS(RADIANS(?))
                        * COS(RADIANS(latitude))
                        * COS(RADIANS(?)- RADIANS(longitude))
                        + SIN(RADIANS(?))
                        * SIN(RADIANS(latitude))
                    ),4
                ) AS distance
            FROM
                warehouses
            WHERE
                latitude IS NOT NULL
                AND longitude IS NOT NULL
                AND deleted_at IS NULL";

        $params = [];

      
           
        if(empty($data))
        {
            return null;
        }
        if (is_array($data)) {
            $params = [$data['latitude'], $data['longitude'], $data['latitude']];
        } else {
            $params = [$data->latitude, $data->longitude, $data->latitude];
        }

        
        $query .= "
            ORDER BY
            distance ASC;
        ";

        $result = DB::select($query, $params);



        if (empty($result)) {

            return null;
        }
        return $result;
    }




    // Calculate the distance between two sets of coordinates using Haversine formula
    function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $R = 6371; // Radius of the Earth in km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $R * $c;

        return $distance;
    }

    // Find the shortest path between all locations
    function findShortestPath($array, $end = null)
    {

        if (is_array($array)) {
            $numLocations = count($array);
            $distances = [];

            // Calculate distances between all locations
            for ($i = 0; $i < $numLocations; $i++) {
                for ($j = 0; $j < $numLocations; $j++) {
                    $distance = $this->calculateDistance($array[$i]->latitude, $array[$i]->longitude, $array[$j]->latitude, $array[$j]->longitude);
                    $distances[$i][$j] = $distance;
                }
            }

            // Initialize arrays for tracking visited and unvisited locations
            $visited = [];
            $unvisited = [];
            for ($i = 0; $i < $numLocations; $i++) {
                $unvisited[$i] = true;
            }

            // Start with the first location
            $current = 0;
            $unvisited[$current] = false;
            $path = [$current];

            // Find the shortest path
            while (count($path) < $numLocations) {
                $minDistance = PHP_INT_MAX;
                $nextLocation = -1;

                // Find the unvisited location with the shortest distance
                for ($i = 0; $i < $numLocations; $i++) {
                    if ($unvisited[$i] && $distances[$current][$i] < $minDistance) {
                        $minDistance = $distances[$current][$i];
                        $nextLocation = $i;
                    }
                }

                // Mark the next location as visited and add it to the path
                $unvisited[$nextLocation] = false;
                $current = $nextLocation;
                $path[] = $current;
            }


            // Reorder the array based on the shortest path
            $sortedArray = [];
            foreach ($path as $index) {

                $sortedArray[] = $array[$index];
            }

            if (isset($end)) {
                $targetId = $end->id;
                $filteredArray = [];

                foreach ($sortedArray as $object) {
                    $filteredArray[] = $object;
                    if ($object->id == $targetId) {
                        break;
                    }
                }
                return $filteredArray;
            }
            return $sortedArray;
        }
    }

    function DispatcherRouteAllocation($client, $value, $request, $orders, $dep_id, $Loction, $cus_id)
    {

        if(isset($value['task_type_id']))
        {

            $user_location = [
                'latitude' => $request->task[1]['latitude'],
                'longitude' => $request->task[1]['longitude']
            ];
        }else{
        $user_key = array_keys($request['task_type_id'], 2)[0];
        
        if($request->filled('warehouse_id') && @$request->warehouse_id[1]){
            $warehouse_detail = Warehouse::find($request->warehouse_id[1]);
            
            $user_location = [
                'latitude' => $warehouse_detail->latitude ?? '',
                'longitude' => $warehouse_detail->longitude ?? ''
            ];
        }else{
            $user_location = [
                'latitude' => $request->latitude[$user_key],
                'longitude' => $request->longitude[$user_key]
            ];
         }
        
         }
        $user_location = collect($user_location)->toArray();

        $best_routes = $this->findNearestWarehouse($user_location);

        return $best_routes;
    }

    function createTasksForLocation($orders, $dep_id, $route, $cus_id)
    {
        $loc_id = null;
       
        if (isset($route)) {
            $loc = [
                'latitude' => $route->latitude ?? 0.00,
                'longitude' => $route->longitude ?? 0.00,
                'address' =>  $route->address ?? null,
                'customer_id' => $cus_id
            ];

            $loc_update = [
                'latitude' => $route->latitude ?? 0.00,
                'longitude' => $route->longitude ?? 0.00,
                'address' =>  $route->address ?? null,
            ];

            $Location = Location::updateOrCreate($loc, $loc_update);
            $loc_id = $Location->id;


            $finalLocation = Location::where('id', $loc_id)->first();
            $warehouse =  Warehouse::find($route->id);
            $lastTask = Task::where('order_id', $orders->id)
            ->where('task_type_id', 1)
            ->orderBy('id', 'desc')

            ->first();
            $data = [
                'order_id' => $orders->id,
                'task_type_id' => 2,
                'location_id' => $loc_id,
                'dependent_task_id' => $lastTask->id ?? '',
                'vendor_id' => isset($warehouse) ? $warehouse->id : '',
                'warehouse_id' =>  isset($warehouse) ? $warehouse->id : null
            ];
            
            $task1 = Task::create($data);
            $data1 = [
                'order_id' => $orders->id,
                'task_type_id' => 1,
                'location_id' => $loc_id,
                'dependent_task_id' => null,
                'vendor_id' => isset($warehouse) ? $warehouse->id : '',
                'warehouse_id' =>  isset($warehouse) ? $warehouse->id : null
            ];

            
            $task2 = Task::create($data1);
        }
    }
    public function createWarehouseTasks($client, $value, $request, $order, $dep_id, $location, $cus_id)
    {
                $routes = $this->DispatcherRouteAllocation($client, $value, $request, $order, $dep_id, $location, $cus_id);

                if(empty($routes))
                {
                    return;
                }
                
                if(isset($value['task_type_id']))
                {
                    $last_nearest_warehouse = $this->findNearestWarehouse($request->task[0]);

                }else{
                    $last_nearest_warehouse = $this->findNearestWarehouse($location);
                }
                if (!empty($last_nearest_warehouse)) {
                    $shortestPath = $this->findShortestPath($routes, $last_nearest_warehouse[0]);
                } else {
                    $shortestPath = $this->findShortestPath($routes, null);
                }

                $shortestPath = array_reverse($shortestPath);

                if (!empty($shortestPath)) {
                    foreach ($shortestPath as $route) {
                        $this->createTasksForLocation($order, $dep_id, $route, $cus_id);
                    }
                }
            
        
    }
}
