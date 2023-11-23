@php
$date = date('Y-m-d');
$color = ['one','two','three','four','five','six','seven','eight'];
$imgproxyurl = 'https://imgproxy.royodispatch.com/insecure/fill/90/90/sm/0/plain/';
use Carbon\Carbon;

$agentslocations = array();
if(!empty($agents)){
    foreach ($agents as $singleagent) {
        if((!empty($singleagent['agentlog'])) && ($singleagent['agentlog']['lat']!=0) && ($singleagent['agentlog']['long']!=0))
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
    @foreach($item['agents'] as $agent)
        <?php
            if(isset($distance_matrix[$agent['id']]))
            {
                if($agent['order'][0]['task_order']==0){
                    $opti = "yes";
                }else{
                    $opti = "";
                }
                
                $routeperams = "'".$distance_matrix[$agent['id']]['tasks']."','".json_encode($distance_matrix[$agent['id']]['distance'])."','".$opti."',".$agent['id'].",'".$date."'";
                
                $optimize = '<span class="optimize_btn" onclick="RouteOptimization('.$routeperams.')">'.__("Optimize").'</span>';
                $params = "'".$distance_matrix[$agent['id']]['tasks']."','".json_encode($distance_matrix[$agent['id']]['distance'])."','yes',".$agent['id'].",'".$date."'";
                
                $turnbyturn = '<span class="navigation_btn optimize_btn" onclick="NavigatePath('.$routeperams.')">'.__("Export").'</span>';
            }else{
                $optimize="";
                $params = "";
                $turnbyturn = "";
            }
            
            //for exporting path
            if(count($agent['order'])==1)
            {
                if($agent['is_available']==1)
                {
                    $pdfperams = "'".$agent['order'][0]['task'][0]['id']."','','',".$agent['id'].",'".$date."'";
                    $turnbyturn = '<span class="navigation_btn optimize_btn" onclick="NavigatePath('.$pdfperams.')">'.__("Export").'</span>';
                }else{
                    $pdfperams = "";
                    $turnbyturn = "";
                }
            }
        ?>
        <div id="accordion-1" class="mb-2">
            <div class="card no-border-radius">
                <div class="card-header" id="by1">
                    <div class="row align-items-center">
                        <div class="col-md-3 col-3">
                            <img class="profile-circle" src="{{isset($agent['profile_picture']) ? $imgproxyurl.Storage::disk('s3')->url($agent['profile_picture']):'https://dummyimage.com/36x36/ccc/fff'}}">
                        </div>
                        <div class="col-md-9 col-9">
                            <h6 class="mb-0 header-title scnd">
                                {{ ucfirst($agent['name']) }}
                               
                            </h6>
                            <p class="mb-0"><span class="badge badge-blue text-white">{{ $item['name']??'' }}</span></p>

                            <p class="mb-0">{{count($agent['order'])>0?__('Busy '):__('Free ')}}<span>{{$agent['agent_task_count']}} {{__('Tasks')}}</span> {!!$agent['total_distance']==''?'':' <i class="fas fa-route"></i>'!!}<span class="dist_sec totdis{{ $agent['id'] }}  ml-1">{{ $agent['total_distance'] }}</span></p>
                            <div class="d-flex mt-1">
                            <div class="optimizebtn1">
                                    {!! $optimize !!}
                                </div>
                                <div class="exportbtn1 ml-2">
                                    {!! $turnbyturn !!}
                                </div>
                            </div>
                        </div>
                        
                    </div>
                    @php
                        $checkAgentActive = ($agent['is_available'] == 1)? ' ('.__('Online').')' : ' ('.__('Offline').')';
                    @endphp
                    <div class="row">
                        <div class="col-md-12">
                            <span class="tram_agent_online_status_{{ $agent['id'] }} {{ $agent['is_available'] == 1 ? 'online' : 'offline' }}" id="tram_agent_online_status_{{ $agent['id'] }}">
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
@endforeach

