    <!-- Bannar Section -->
    {{-- <section class="bannar header-setting"> --}}
       
    <div class="container-fluid p-0">
        <div class="row coolcheck no-gutters">
            {{-- <div class="pageloader" style="display: none;">
                <div class="box">
                    <h4 class="routetext"></h4>
                    <div class="spinner-border avatar-lg text-primary m-2" role="status"></div>
                </div>
            </div> --}}
            {{-- <div id="scrollbar" class="col-md-3 col-xl-3 left-sidebar pt-3"> --}}
            <div id="scrollbar" class="col-md-3 col-xl-3">
                <div class="side_head">   <!---mb-2 py-2"-->
                    <div class="d-flex align-items-center justify-content-center mb-2"> 
                        <i class="mdi mdi-sync mr-1" onclick="reloadData()" aria-hidden="true"></i>
                       
                        <div class="radio radio-primary form-check-inline">
                            <input type="radio" id="user_status_online" value="1" name="user_status" class="checkUserStatus" checked>
                            <label for="user_status_online"> {{__("Online")}} </label>
                        </div>
                        <div class="radio radio-info form-check-inline mr-2">
                            <input type="radio" id="user_status_offline" value="0" name="user_status" class="checkUserStatus">
                            <label for="user_status_offline"> {{__("Offline")}} </label>
                        </div>
                        <div class="radio radio-primary form-check-inline ml-3 mr-2">
                            <input type="radio" id="user_status_all" value="2" name="user_status" class="checkUserStatus" >
                            <label for="user_status_all"> {{__("All")}} </label>
                        </div>
                        
                        {{-- <span class="allAccordian ml-2"><span class="" onclick="openAllAccordian()">{{__("Open All")}}</span></span> --}}
                    </div>
                   <div class="row search_bar">
                        <div class="col-md-6">
                            <div class="form-group">
                                <input type="text" class="form-control" name="search_by_name" id="search_by_name" value="" placeholder="Search Name" />
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group custom_select">
                                <select name="" id="dummy" class="form-control">
                                    <option>Select Team</option>
                                </select>
                                <select style="display: none" name="team_id[]" id="team_id" multiple="multiple" class="form-control">
                                    @foreach ($searchTeams as $team)
                                        <option value="{{$team->id}}">{{$team->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                   </div>
                </div>
                <div  id="teams_container">
                    @include('dashboard.parts.layout-'.$dashboard_theme.'.ajax.agent')
                </div>
                <form id="pdfgenerate" method="post" enctype="multipart/form-data" action="{{ route('download.pdf') }}">
                    @csrf
                    <input id="pdfvalue" type="hidden" name="pdfdata">
                </form>
            </div>
            <div class="col-md-6 col-xl-6">
                <div class="map-wrapper">
                    <div style="width: 100%">
                        <div id="map_canvas" style="width: 100%; height:100vh;"></div>
                    </div>
            
                </div>
            </div>
            {{-- @dd($unassigned_orders) --}}
            {{-- left-sidebar pt-3 --}}
            <div id="scrollbar" class="col-md-3 col-xl-3">
                <div class="side_head"> <!---mb-2 py-2--->
                    <div class="select_bar_date mb-2 d-flex align-items-center justify-content-center">
                        <input type="date"  id="basic-datepicker" class="datetime form-control" value="{{date('Y-m-d', strtotime($date))}}" data-date-format="YY-mm-dd" onchange="handler(this);" style="width: 250px;">
                        <div style="display:none">
                            <input class="newchecks filtercheck teamchecks" cla type="checkbox" value="-1" name="teamchecks[]" checked>
                            <input class="taskchecks filtercheck" type="checkbox" name="taskstatus[]" value="5" checked>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-center mb-2"> 
                        <div class="radio radio-primary form-check-inline ml-3 mr-2">
                            <input type="radio" id="user_all_routes" value="" name="user_routes" class="checkUserRoutes" checked>
                            <label for="user_all_routes"> {{__("All")}} </label>
                        </div>
                        <div class="radio radio-primary form-check-inline">
                            <input type="radio" id="user_unassigned_routes" value="unassigned" name="user_routes" class="checkUserRoutes">
                            <label for="user_unassigned_routes"> {{__("Unassigned")}} </label>
                        </div>
                        <div class="radio radio-info form-check-inline mr-2">
                            <input type="radio" id="user_assigned_routes" value="assigned" name="user_routes" class="checkUserRoutes">
                            <label for="user_assigned_routes"> {{__("Assigned")}} </label>
                        </div>                        
                        {{-- <span class="allAccordian ml-2"><span class="" onclick="openAllAccordian()">{{__("Open All")}}</span></span> --}}
                    </div>
                    <div class="select_bar">
                        <div class="form-group mb-0 ml-1">
                   
                        </div>
                    </div>
                </div>


                {{-- agent section  --}}
                <?php
             
                if(isset($distance_matrix[0]))
                {
                    
                    // if($unassigned_orders[0]['task_order']==0){
                    //     $opti0 = "yes";
                    // }else{
                    //     die('pass');
                        $opti0 = "";
                    // }
                    $routeperams0 = "'".$distance_matrix[0]['tasks']."','".json_encode($distance_matrix[0]['distance'])."','".$opti0."',0,'".$date."'";
                    $optimize0 = '<span class="optimize_btn" onclick="RouteOptimization('.$routeperams0.')">'.__("Optimize").'</span>';
                    $params0 = "'".$distance_matrix[0]['tasks']."','".json_encode($distance_matrix[0]['distance'])."','yes',0,'".$date."'";
       
                    $turnbyturn0 = '<span class="navigation_btn optimize_btn" onclick="NavigatePath('.$routeperams0.')">'.__("Export").'</span>';
                }else{
                    $optimize0="";
                    $params0 = "";
                    $turnbyturn0 = "";
                }
            ?>
            @php
                $date = date('Y-m-d');
                use Carbon\Carbon;
            
               
            @endphp
            <div id="agent_route_container">
                <div id="accordion" class="overflow-hidden">
                    <!-- dragable_tasks -->
                    <div id="handle-dragula-left0" class="dragable_tasks" agentid="1"  params="{{ $params0 }}" date="{{ $date }}">
                        @include('dashboard.parts.layout-'.$dashboard_theme.'.ajax.order')
                    </div>
                </div>
            </div>
                {{-- agent section end --}}




               
                <form id="pdfgenerate" method="post" enctype="multipart/form-data" action="{{ route('download.pdf') }}">
                    @csrf
                    <input id="pdfvalue" type="hidden" name="pdfdata">
                </form>
            </div>
        </div>
        @include('modals.optimize-route');
    </div>
    <?php   // for setting default location on map
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
    ?>
