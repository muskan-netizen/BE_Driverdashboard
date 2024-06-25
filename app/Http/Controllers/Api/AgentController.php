<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Model\{Agent, AgentLog, AgentSlot, AllocationRule, Client, ClientPreference, Cms, Order, Task, TaskProof, Timezone, User, DriverGeo, Geo, TagsForAgent, DriverHomeAddress};
use App\Model\Order\Category;
use Validator;
use DB;
use Illuminate\Support\Facades\Storage;
use App\Model\Roster;
use Config;
use Illuminate\Support\Facades\URL;
use GuzzleHttp\Client as GClient;
use App\Traits\{AgentSlotTrait,ResponseTrait};

class AgentController extends BaseController
{
    use AgentSlotTrait,ResponseTrait;
    /**   get agent according to lat long  */
    function getAgents(Request $request)
    {
        try {

            $validator = Validator::make(request()->all(), [
                'latitude' => 'required',
                'longitude' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->messages(), 422);
            }

            $geoid = $this->findLocalityByLatLng($request->latitude, $request->longitude);
            $geoagents_ids = DriverGeo::where('geo_id', $geoid)->pluck('driver_id');

            $tagId = '';
            if (!empty($request->tag)) {
                $tag = TagsForAgent::where('name', $request->tag)->get()->first();
                if (!empty($tag)) {
                    $tagId = $tag->id;
                } else {
                    return response()->json([
                        'data' => [],
                        'status' => 200,
                        'message' => __('Agents not found.')
                    ], 200);
                }
            }

            $geoagents = Agent::with(['agentlog', 'vehicle_type']);

            if (!empty($tagId)) {
                $geoagents->whereHas('tags', function ($q) use ($tagId) {
                    $q->where('tag_id', $tagId);
                });
            }

            $geoagents = $geoagents->whereIn('id', $geoagents_ids)->where(["is_available" => 1, "is_approved" => 1])->orderBy('id', 'DESC')->get();
            // $preferences = ClientPreference::with('currency')->first();
            // $clientPreference = json_decode($preferences->customer_notification_per_distance);

            $finalAgents = [];
            foreach($geoagents as $geoagent){
                $agentLat = !empty($geoagent->agentlog) ? $geoagent->agentlog->lat : 0.00000;
                $agentLong = !empty($geoagent->agentlog) ? $geoagent->agentlog->long : 0.00000;
                $getLatLongDistance = $this->getLatLongDistance($agentLat, $agentLong, $request->latitude, $request->longitude);
                $getDistance =   $getLatLongDistance['km']; 
                $geoagent->distance = $getDistance;
                $geoagent->distance_type = 'km';

                $getArrivalTime =  $getLatLongDistance['time']; 
                $geoagent->arrival_time = $getArrivalTime;
                //get agent under 5 km
                if ($getDistance < 6) {
                    $finalAgents[] = $geoagent;
                }

            }

            $distance = array_column($finalAgents, 'distance');
            array_multisort($distance, SORT_ASC, $finalAgents);

            return response()->json([
                'data' => $finalAgents,
                'status' => 200,
                'message' => __('success')
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    function getLatLongDistance($lat1, $lon1, $lat2, $lon2, $unit = '')
    {
        $return_array= [];
        $earthRadius = 6371;  // earth radius in km

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo   = deg2rad($lat2);
        $lonTo   = deg2rad($lon2);
        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;
        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        $final = round($angle * $earthRadius); // get KM 
        $return_array['km']=  $final;
        // if ($unit == "km") {
        //     return $final;
        // } else if ($unit == "minutes") {
            $time  = '';
            $kmsPerMin = 0.5; // asume per km time estimate 0.5 minute
            $minutesTaken = $final / $kmsPerMin;
            if ($minutesTaken < 60) {
                $time = $minutesTaken . ' min';
            } else {
                $time = intdiv($minutesTaken, 60) . 'hours ' . ($minutesTaken % 60) . 'min';
            }
            $return_array['time']=  $time;
            $return_array['miles']=   round($final * 0.6214); //miles
        // } else {
        //     return round($final * 0.6214); //miles
        // }
        return $return_array;
    }

    public function findLocalityByLatLng($lat, $lng)
    {
        // get the locality_id by the coordinate //
        $latitude_y = $lat;
        $longitude_x = $lng;
        $localities = Geo::all();

        if (empty($localities)) {
            return false;
        }

        foreach ($localities as $k => $locality) {

            if (!empty($locality->polygon)) {
                $geoLocalitie = Geo::where('id', $locality->id)->whereRaw("ST_Contains(POLYGON, ST_GEOMFROMTEXT('POINT(" . $lat . " " . $lng . ")'))")->first();
                if (!empty($geoLocalitie)) {
                    return $locality->id;
                }
            } else {
                $all_points = $locality->geo_array;
                $temp = $all_points;
                $temp = str_replace('(', '[', $temp);
                $temp = str_replace(')', ']', $temp);
                $temp = '[' . $temp . ']';
                $temp_array =  json_decode($temp, true);

                foreach ($temp_array as $k => $v) {
                    $data[] = [
                        'lat' => $v[0],
                        'lng' => $v[1]
                    ];
                }

                // $all_points[]= $all_points[0]; // push the first point in end to complete
                $vertices_x = $vertices_y = array();

                foreach ($data as $key => $value) {
                    $vertices_y[] = $value['lat'];
                    $vertices_x[] = $value['lng'];
                }

                $points_polygon = count($vertices_x) - 1;  // number vertices - zero-based array
                $points_polygon;

                if ($this->is_in_polygon($points_polygon, $vertices_x, $vertices_y, $longitude_x, $latitude_y)) {
                    return $locality->id;
                }
            }
        }

        return false;
    }

    public function is_in_polygon($points_polygon, $vertices_x, $vertices_y, $longitude_x, $latitude_y)
    {
        $i = $j = $c = 0;
        for ($i = 0, $j = $points_polygon; $i < $points_polygon; $j = $i++) {
            if ((($vertices_y[$i]  >  $latitude_y != ($vertices_y[$j] > $latitude_y)) &&
                ($longitude_x < ($vertices_x[$j] - $vertices_x[$i]) * ($latitude_y - $vertices_y[$i]) / ($vertices_y[$j] - $vertices_y[$i]) + $vertices_x[$i]))) {
                $c = !$c;
            }
        }
        return $c;
    }
    public function On_demand_services_list(Request $request)
    {

        try {
            $category_list = Category::with('products')->where('type_id', '8')->get();

            if (!empty($category_list[0]->slug)) {
                return response()->json([
                    'data' => $category_list,
                ]);
            } else {
                return response()->json(['error' => 'No record found.'], 404);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }
    public function getTaskListWithDate(Request $request){
        $header = $request->header();
        $client_code = Client::where('database_name', $header['client'][0])->first();
        $tz = new Timezone();
        $orderStatus = 'assigned';
        $client_code->timezone = $tz->timezone_name($client_code->timezone);
        $selectedDatesArray   = $request->has('selectedDatesArray') ? $request->selectedDatesArray : [];
     
      
        $id     = Auth::user()->id;

        $all     = $request->all;
        $tasks   = [];
        $ordersDats =  $orders = Order::where('driver_id', $id);//->where('status',  $orderStatus);
        //if ($all != 1) { //geting today task
            $orders = $orders->whereIn(DB::raw('DATE(order_time)'), $selectedDatesArray );
            //$orders = $orders->whereBetween('order_time', [$utc_start,$utc_end]);
           // } 
       
        $orders = $orders->orderBy("order_time","ASC")->orderBy("id","ASC")->pluck('id')->toArray();
     
        $ordersDates = Order::where('driver_id', $id)->where('status', 'assigned')->orderBy("order_time","ASC")->orderBy("id","ASC")->pluck('order_time')->toArray();

        if (count($orders) > 0) {
            
            $tasks = Task::whereIn('order_id', $orders)
            ->with(['location','tasktype','order.customer','order.customer.resources','order.task.location','order.additionData'])->orderBy("order_id", "DESC")
            ->orderBy("id","ASC")
            ->get();
            if (count($tasks) > 0) {
                //sort according to task_order
                $tasks = $tasks->toArray();
                if ($tasks[0]['task_order'] !=0) {
                    usort($tasks, function ($a, $b) {
                        return $a['task_order'] <=> $b['task_order'];
                    });
                }
            }
        }
        $request->merge(['agent_id'=> $id]);
        $AgentSlotRoster  = $this->getAgentSlotByType($request);
        $AgentBlockRoster = $this->getAgentSlotBlocked($request);
        $response =  [
                        'tasks' => $tasks,
                        'agent_slots'=> $AgentSlotRoster,
                        'ordersDates' => $ordersDates,
                        'agent_blocked_dates'=> $AgentBlockRoster
                    ];
        return response()->json([
            'data' => $response,
            'status' => 200,
            'message' => __('success')
        ], 200);
    }

     /**   get agent data api */
     function getAgentDetails(Request $request,$driver_id)
     {
         try {
            $agent = Agent::with(['agentlog','agentRating'])->where('id',$driver_id)->first();
            return response()->json([
                'data' => $agent,
                'status' => 200,
                'message' => __('success')
            ], 200);
         } catch (Exception $e) {
             return response()->json([
                 'message' => $e->getMessage()
             ], 400);
         }
     }


     function getAllAgentDetails(Request $request)
     {
       
         try {
            $agent = Agent::with(['agentlog','agentRating'])->get();
            return response()->json([
                'data' => $agent,
                'status' => 200,
                'message' => __('success')
            ], 200);
         } catch (Exception $e) {
             return response()->json([
                 'message' => $e->getMessage()
             ], 400);
         }
     }


     
    /**   get agent enable/disbale go to home address option  */
    // function getAgentgotoHomeAddress(Request $request){
    //     try {
          
    //         $agent_id     = Auth::user()->id;
    //         $agent                          = Agent::find($agent_id);
    //         return response()->json([
    //             'data' => $agent,
    //             'status' => 200,
    //             'message' => __('success')
    //         ], 200);
            
    //     }
    //     catch (Exception $e) {
    //         return response()->json([
    //             'message' => $e->getMessage()
    //         ], 400);
    //     }
    // }
     /**   add agent enable/disbale go to home address option  */
     function addAgentgotoHomeAddress(Request $request){
        try {
            $validator = Validator::make(request()->all(), [
                'is_go_to_home_address'   => 'required|in:0,1',
            ]);
            if ($validator->fails()) {
                return $this->errorResponse($validator->messages()->first(), 422);
            }
            $agent_id     = Auth::user()->id;
            $is_go_to_home_address = $request->is_go_to_home_address ?? 0;
            
            $agent                          = Agent::find($agent_id);
            $agent->is_go_to_home_address   = $is_go_to_home_address;
            $agent->save();
            return response()->json([
                'data' => $agent,
                'status' => 200,
                'message' => __('success')
            ], 200);
            
        }
        catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
    /**   get agent according to lat long  */
    function addagentAddress(Request $request)
    {
       
        try {
           
            $validator = Validator::make(request()->all(), [
                'latitude' => 'required',
                'longitude' => 'required',
            ]);
            if ($validator->fails()) {
                return $this->errorResponse($validator->messages()->first(), 422);
            }
            $agent_id     = Auth::user()->id;
            DriverHomeAddress::unsetDefaultAddress($agent_id);
           
            $address                = new DriverHomeAddress();
            $address->agent_id      = $agent_id;
            $address->latitude      = $request->latitude;
            $address->longitude     = $request->longitude;
            $address->short_name    = $request->short_name;
            $address->address       = $request->address;
            $address->post_code     = $request->post_code ?? '';
            $address->is_default    = 1;
            $address->save();
           
            return response()->json([
                'data' => $address,
                'status' => 200,
                'message' => __('success')
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
     /**   get agent home address By Agent id  */
    function allHomeAddress(Request $request)
    {
        try {
            $agent_id = Auth::user()->id;
            $address  = DriverHomeAddress::where('agent_id',$agent_id)->get();
            return response()->json([
                'data' => $address,
                'status' => 200,
                'message' => __('success')
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
    /**Check status go to home address is Enabled/Disabled */
    function HomeAddressStatus(Request $request){
        try {
            $validator = Validator::make(request()->all(), [
                'address_id' => 'required|exists:agents_home_address,id',
            ]);
            if ($validator->fails()) {
                return $this->errorResponse($validator->messages()->first(), 422);
            }
           
            $agent_id     = Auth::user()->id;
            $status = 1;
           
            DriverHomeAddress::unsetDefaultAddress($agent_id);
            $address                = DriverHomeAddress::where(['agent_id'=>$agent_id,'id'=>$request->address_id])->first();
            $address->is_default    = $status;
            $address->save();
           
            return response()->json([
                'data' => $address,
                'status' => 200,
                'message' => __('success')
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

}
