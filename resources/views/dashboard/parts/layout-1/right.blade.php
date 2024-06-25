
@section('content')
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
            <div id="scrollbar" class="col-md-4 col-xl-4 left-sidebar pt-3">
                <div class="side_head d-flex justify-content-between align-items-center mb-2">
                    <i class="mdi mdi-sync mr-1" onclick="reloadData()" aria-hidden="true"></i>
                    <div>
                        <div class="radio radio-primary form-check-inline ml-3 mr-2">
                            <input type="radio" id="user_status_all" value="2" name="user_status" class="checkUserStatus" checked>
                            <label for="user_status_all"> {{__("All")}} </label>
                        </div>
                        <div class="radio radio-primary form-check-inline">
                            <input type="radio" id="user_status_online" value="1" name="user_status" class="checkUserStatus">
                            <label for="user_status_online"> {{__("Online")}} </label>
                        </div>
                        <div class="radio radio-info form-check-inline mr-2">
                            <input type="radio" id="user_status_offline" value="0" name="user_status" class="checkUserStatus">
                            <label for="user_status_offline"> {{__("Offline")}} </label>
                        </div>
                    </div>
                    <span class="allAccordian"><span class="" onclick="openAllAccordian()">{{__("Open All")}}</span></span>
                </div>
                <div  id="teams_container">
                    @include('dashboard.parts.layout-'.$dashboard_theme.'.ajax.agent')
                </div>
                <form id="pdfgenerate" method="post" enctype="multipart/form-data" action="{{ route('download.pdf') }}">
                    @csrf
                    <input id="pdfvalue" type="hidden" name="pdfdata">
                </form>
            </div>
            <div class="col-md-8 col-xl-8">
                <div class="map-wrapper">
                    <div style="width: 100%">
                        <div id="map_canvas" style="width: 100%; height:calc(100vh - 70px);"></div>
                    </div>
                    <div class="contant">
                        <div class="bottom-content">
                            <input type="text"  id="basic-datepicker" class="datetime" value="{{date('Y-m-d', strtotime($date))}}" data-date-format="Y-m-d">
                            <div style="display:none">
                                <input class="newchecks filtercheck teamchecks" cla type="checkbox" value="-1" name="teamchecks[]" checked>
                                <input class="taskchecks filtercheck" type="checkbox" name="taskstatus[]" value="5" checked>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('modals.optimize-route')
 @endsection

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