<?php

namespace App\Http\Controllers;

use App\Model\Agent;
use App\Model\ClientPreference;
use App\Model\Team;
use App\Model\Client;
use App\Model\Timezone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Model\Countries;
use App\Traits\googleMapApiFunctions;
use App\Traits\{GlobalFunction,  Dispatcher, DispatcherOrders};
use Carbon\Carbon;
use DB;


class DashBoardController extends Controller
{
    use googleMapApiFunctions, GlobalFunction, Dispatcher, DispatcherOrders;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
      
        $agents = [];
        $com_data = $this->GetCommonItem($request);
        // pr($com_data['clientPreference']);
        $client = $com_data['clientPreference'];
        //$client = ClientPreference::select('id', 'map_key_1', 'dashboard_mode', 'dashboard_theme')->first();

        $googleapikey = $client->map_key_1 ?? '';
        $dashboardMode = isset($client->dashboard_mode) ? json_decode($client->dashboard_mode) : '';
        $dashboard_theme = isset($client->dashboard_theme) ? $client->dashboard_theme : 2;

        $show_dashboard_by_agent_wise = 0;
        if (!empty($dashboardMode->show_dashboard_by_agent_wise) && $dashboardMode->show_dashboard_by_agent_wise == 1) {
            $show_dashboard_by_agent_wise = 1;
        }
        $getAdminCurrentCountry = Countries::where('id', '=', Auth::user()->country_id)->first();
        if (!empty($getAdminCurrentCountry)) {
            $defaultCountryLatitude  = $getAdminCurrentCountry->latitude;
            $defaultCountryLongitude  = $getAdminCurrentCountry->longitude;
        } else {
            $defaultCountryLatitude  = '';
            $defaultCountryLongitude  = '';
        }

        $teams  = Team::get();
        //$teams = [];
        if ($show_dashboard_by_agent_wise == 0 && $dashboard_theme == 1) {
            
            // $agents  = Agent::with('agentlog')->where('is_approved',1)->get();
            $agentsData = \DB::table('agents')
                ->select('agents.*', 'latest_log.lat', 'latest_log.long', 'latest_log.device_type', 'latest_log.battery_level', 'latest_log.created_at')
                ->leftJoin('agent_logs as latest_log', function ($join) {
                    $join->on('agents.id', '=', 'latest_log.agent_id')
                        ->whereRaw('latest_log.id = (SELECT MAX(id) FROM agent_logs WHERE agent_id = agents.id)');
                })
                ->where('agents.is_approved', 1)
                ->get();

            $agents = $agentsData->map(function ($agent) {
                $agent->agentlog = [
                    'lat' => $agent->lat ?? 0,
                    'long' => $agent->long ?? 0,
                    'device_type' => $agent->device_type ?? '',
                    'battery_level' => $agent->battery_level ?? '',
                    'created_at' => $agent->created_at ?? ''
                ];
                unset($agent->lat, $agent->long);
                return (array)$agent;
            });


       }
        $response = ['client_code' => Auth::user()->code, 'date' => @$com_data['date'], 'defaultCountryLongitude' => $defaultCountryLongitude, 'defaultCountryLatitude' => $defaultCountryLatitude, 'map_key' => $googleapikey, 'client_timezone' => $com_data['user']->timezone, 'searchTeams' => $teams, 'agentsData' => $agents,'agents' => $agents, 'show_dashboard_by_agent_wise' => $show_dashboard_by_agent_wise, 'dashboard_theme' => $dashboard_theme,'preference'=>$client,'user'=>@$com_data['user'],'date'=>@$com_data['date']];
        $request->merge(['dashboard_theme' => $dashboard_theme,'start_date'=>@$com_data['startdate'],'end_date'=>@$com_data['enddate']]);
        if ($dashboard_theme == 1) {
            $this->teamData($request, $response);
        } else {
            $this->teamDataEN($request, $response);
        }

        // pr($response);
        
       // pr($response);
       $response['agentMarkerData']=  $this->GetAgentLogs(-1,-1);
        if($dashboard_theme != 1){
       
      
            $this->orderDataEN($request, $response);
        }
        else{
       
            $this->orderData($request, $response);

        }
    
        return view('dashboard.index')->with($response);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * All below functions moved to @GlobalFunction Trait
     *
     * 
     */

    public function GetCommonItem($request){
        $sql = "SELECT *
        FROM client_preferences
        WHERE id = 1
        LIMIT 1";
        $clientPreference = \DB::select($sql);

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

        $data['clientPreference'] = $clientPreference[0];
        $data['startdate'] = $startdate;
        $data['enddate'] = $enddate;
        $data['user'] = $auth;
        $data['date'] = $date;

        
        return $data;
    }


    public function dashboardTeamData(Request $request)
    {  
        $data_com = $this->GetCommonItem($request);
       
        $request->merge(['start_date'=>@$data_com['startdate'],'end_date'=>@$data_com['enddate']]);
        $data['agentMarkerData']=  $this->GetAgentLogs(-1,$request->userstatus);
        $data= $this->teamDataEN($request,$data_com);
       
        return $data;
    }
    public function dashboardOrderData(Request $request)
    {
         $data_com = $this->GetCommonItem($request);
         $request->merge(['start_date'=>@$data_com['startdate'],'end_date'=>@$data_com['enddate']]);
         $data_com['preference'] = $data_com['clientPreference'];
         $data = $this->orderDataEN($request,$data_com);
         return $data;
    }


    
        
        /**
         * Get Agent records for current location
         *
         * @param  int  $pAgentId,$pIsAvailable
         * @parsam send -1 if need all records
         * @return \Illuminate\Http\Response
         */
        public function GetAgentLogs($pAgentId = null, $pIsAvailable = null)
        {
           
            // Call the stored procedure
          
            $results = DB::select('CALL GetLatestAgentLogs(?, ?)', [$pAgentId, $pIsAvailable]);
            // Return or do something with the results
            return $results;
        } 
    

    
}
