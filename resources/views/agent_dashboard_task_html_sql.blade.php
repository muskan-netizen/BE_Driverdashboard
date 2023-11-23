@php
$date = date('Y-m-d');
$color = ['one','two','three','four','five','six','seven','eight'];
$imgproxyurl = 'https://imgproxy.royodispatch.com/insecure/fill/90/90/sm/0/plain/';
use Carbon\Carbon;

$agentslocations = array();
if(!empty($agents)){
    foreach ($agents as $singleagent) {
        $singleagent = $singleagent;
        if((array_filter($singleagent['agentlog'])) && ($singleagent['agentlog']['lat']!=0) && ($singleagent['agentlog']['long']!=0))
        {
            $agentslocations[] = $singleagent['agentlog'];
            
        }
    }
}

$defaultmaplocation['lat'] = $defaultCountryLatitude;
$defaultmaplocation['long'] = $defaultCountryLongitude;
$agentslocations[] = $defaultmaplocation;


@endphp

<input type="hidden" id="newmarker_map_data" value="{{json_encode($newmarker)}}"/>
<input type="hidden" id="agents_map_data" value="{{json_encode($agents)}}"/>
<input type="hidden" id="agentslocations_map_data" value="{{json_encode($agentslocations)}}"/>
<input type="hidden" id="uniquedrivers_map_data" value="{{json_encode($routedata)}}"/>
@foreach ($teams as $item)
    <?php
        if(isset($distance_matrix[$item->agent_id]))
        {
            if($item->task_order == 0){
                $opti = "yes";
            }else{
                $opti = "";
            }
            
            $routeperams = "'".$distance_matrix[$item->agent_id]['tasks']."','".json_encode($distance_matrix[$item->agent_id]['distance'])."','".$opti."',".$item->agent_id.",'".$date."'";
            
            $optimize = '<span class="optimize_btn" onclick="RouteOptimization('.$routeperams.')">'.__("Optimize").'</span>';
            $params = "'".$distance_matrix[$item->agent_id]['tasks']."','".json_encode($distance_matrix[$item->agent_id]['distance'])."','yes',".$item->agent_id.",'".$date."'";
            
            $turnbyturn = '<span class="navigation_btn optimize_btn" onclick="NavigatePath('.$routeperams.')">'.__("Export").'</span>';
        }else{
            $optimize="";
            $params = "";
            $turnbyturn = "";
        }
        
        //for exporting path
        $status = 'Free ';
        if($item->order_count > 0)
        {
            $status = 'Busy ';
            if($item->is_available == 1)
            {
                $pdfperams = "'".$item->task_id."','','',".$item->agent_id.",'".$date."'";
                $turnbyturn = '<span class="navigation_btn optimize_btn" onclick="NavigatePath('.$pdfperams.')">'.__("Export").'</span>';
            }else{
                $pdfperams = "";
                $turnbyturn = "";
            }
        }
    ?>
    <div id="accordion-1" class="mb-2 teams-data">
        <div class="card no-border-radius">
            <div class="card-header ml-2" id="by1">
                <div class="row p-2">
                    <div class="col-md-3 col-3">
                        <img class="profile-circle" src="{{isset($item->profile_picture) ? $imgproxyurl.Storage::disk('s3')->url($item->profile_picture):'https://dummyimage.com/36x36/ccc/fff'}}">
                    </div>
                    <div class="col-md-9 col-9">
                        <h6 class="mb-0 header-title scnd">
                            {{ ucfirst($item->agent_name) }}
                            <div class="optimizebtn1">
                                {!! $optimize !!}
                            </div>
                            <div class="exportbtn1">
                                {!! $turnbyturn !!}
                            </div>
                        </h6>
                        <p class="mb-0"><span class="badge badge-blue text-white">{{ $item->name??'' }}</span></p>

                        <p class="mb-0">{{$status }}<span>{{$item->agent_task_count}} {{__('Tasks')}}</span></p>
                    </div>
                </div>
                @php
                    $checkAgentActive = ($item->is_available == 1)? ' ('.__('Online').')' : ' ('.__('Offline').')';
                @endphp
                <div class="row">
                    <div class="col-md-12">
                        <span class="tram_agent_online_status_{{ $item->agent_id }} {{ $item->is_available == 1 ? 'online' : 'offline' }}" id="tram_agent_online_status_{{ $item->agent_id }}">
                            {{ $checkAgentActive }}
                        </span>
                    </div>
                </div>
            </div>
            <div id="collapse1" class="collapse" data-parent="#accordion-1" aria-labelledby="by1">
                <div id="handle-dragula-left1" class="dragable_tasks ui-sortable" agentid="1" params="" date="2022-11-29">
                    
                </div>
            </div>
        </div>
    </div>
@endforeach


@if($lastPage != $page && $teams )
  <button class="form-control" id="load-more-teams" data-page="{{$page + 1}}" data-url="{{ route('dashboard.agent-teamsdata', ['page' => $page + 1])}}">Load More</button>
@endif