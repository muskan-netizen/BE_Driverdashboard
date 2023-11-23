@extends('layouts.vertical', ['title' => 'Dashboard','demo'=>'creative'])
@section('css')
    <!-- Plugins css -->
    <link href="{{ asset('demo/css/style.css') }}" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove{color: #fff !important;}
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover{background:transparent !important;}
        .customui_card { background-color: #424e5a; border-bottom: 0 solid #36404a;border-radius: 10px;margin: 0 10px;}
        #accordion {padding-bottom: 20px !important;}
        .assigned_agent{margin-bottom: 20px;}
    </style>
@endsection
@php

    use Carbon\Carbon;
    $color = ['one','two','three','four','five','six','seven','eight'];
    $imgproxyurl = 'https://imgproxy.royodispatch.com/insecure/fill/90/90/sm/0/plain/';
@endphp
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
            <div id="scrollbar" class="col-md-3 col-xl-3 left-sidebar pt-3">
                <div class="side_head mb-2 py-2">
                    <div class="d-flex align-items-center justify-content-center mb-2"> 
                        <i class="mdi mdi-sync mr-1" onclick="reloadData()" aria-hidden="true"></i>
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
                        
                        {{-- <span class="allAccordian ml-2"><span class="" onclick="openAllAccordian()">{{__("Open All")}}</span></span> --}}
                    </div>
                   <div class="row search_bar">
                        <div class="col-md-6">
                            <div class="form-group mb-0 ml-1">
                                <select name="team_id[]" id="team_id" multiple="multiple" class="form-control">
                                    @foreach ($teams as $team)
                                        <option value="{{$team->id}}">{{$team->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-0 ml-1">
                                <input type="text" class="form-control" name="search_by_name" id="search_by_name" value="" placeholder="Search By Name" />
                            </div>
                        </div>
                   </div>
                </div>
                <div  id="teams_container">
                    
                </div>
                <form id="pdfgenerate" method="post" enctype="multipart/form-data" action="{{ route('download.pdf') }}">
                    @csrf
                    <input id="pdfvalue" type="hidden" name="pdfdata">
                </form>
            </div>
            <div class="col-md-6 col-xl-6">
                <div class="map-wrapper">
                    <div style="width: 100%">
                        <div id="map_canvas" style="width: 100%; height:calc(100vh - 70px);"></div>
                    </div>
                    {{-- <div class="contant">
                        <div class="bottom-content">
                            <input type="text"  id="basic-datepicker" class="datetime" value="{{date('Y-m-d', strtotime($date))}}" data-date-format="Y-m-d">
                            <div style="display:none">
                                <input class="newchecks filtercheck teamchecks" cla type="checkbox" value="-1" name="teamchecks[]" checked>
                                <input class="taskchecks filtercheck" type="checkbox" name="taskstatus[]" value="5" checked>
                            </div>
                        </div>
                    </div> --}}
                </div>
            </div>
            {{-- @dd($unassigned_orders) --}}
            <div id="scrollbar" class="col-md-3 col-xl-3 left-sidebar pt-3">
                <div class="side_head mb-2 py-2">
                    <div class="select_bar_date mb-2 d-flex align-items-center justify-content-center">
                        <input type="date"  id="basic-datepicker" class="datetime form-control" value="{{date('Y-m-d', strtotime($date))}}" data-date-format="YY-mm-dd" onchange="handler();" style="width: 250px;">
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
                        <select name="agent_id[]" id="agent_id" multiple="multiple" class="form-control">
                              
                              @foreach ($agents as $agent)
                                  @php
                                  
                                      $checkAgentActive = ($agent['is_available'] == 1) ? ' ('.__('Online').')' : ' ('.__('Offline').')';
                                  @endphp
                                      <option value="{{$agent['id']}}">{{ ucfirst($agent['name']). $checkAgentActive }}</option>
                                  @endforeach
                              </select>
                        </div>
                    </div>
                </div>
                <div id="agent_route_container">
                    
                </div>
                <form id="pdfgenerate" method="post" enctype="multipart/form-data" action="{{ route('download.pdf') }}">
                    @csrf
                    <input id="pdfvalue" type="hidden" name="pdfdata">
                </form>
            </div>
        </div>
    </div>
@endsection
@include('modals.optimize-route')
@include('modals.route-assign-agent')
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
@section('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-timeago/1.6.3/jquery.timeago.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>

    <script>
        // var marker;
        var show = [0];
        let map,directionsRenderer;
        let markers = [];
        let driverMarkers = [];
        let privesRoute = [];
        let url = window.location.origin;
        let olddata  = [];
        let allagent = [];

        // for getting default map location
        let defaultmaplocation = [];
        let defaultlat  = 0.000;
        let defaultlong = 0.000;
        let allroutes = [];
        let old_channelname = old_logchannelname = '';
        let channelname = "orderdata{{$client_code}}{{date('Y-m-d', time())}}";
        let logchannelname = "agentlog{{$client_code}}{{date('Y-m-d', time())}}";
        let imgproxyurl = {!!json_encode($imgproxyurl)!!};
        let directionsArray = [];

        $(document).ready(function() {
            $('#wrapper').addClass('dshboard');
            $(".timeago").timeago();
            $('.checkUserStatus').click(function() {
                loadTeams(1, 1);
            });

            $('.checkUserRoutes').click(function() {
                loadOrders(1, 1);
            });

            $('#team_id').change(function() {
                loadTeams(1, 1);
            });

            $('#agent_id').change(function() {
                loadOrders(1, 1);
            });

            $('#search_by_name').keyup(function() {
                loadTeams(1, 1);
            });

            loadTeams(1, 1);
            ListenDataChannel();
            ListenAgentLogChannel();
            loadOrders(1, 1);
        });

        $(document).ready(function(){
            $("#team_id").select2({
                allowClear: true,
                width: "resolve",
                placeholder: "Select Team"
            });
        });

        $(document).ready(function(){
            $("#agent_id").select2({
                allowClear: true,
                width: "resolve",
                placeholder: "Select Agent"
            });
        });

        function gm_authFailure() {
            $('.excetion_keys').append('<span><i class="mdi mdi-block-helper mr-2"></i> <strong>Google Map</strong> key is not valid</span><br/>');
            $('.displaySettingsError').show();
        };

        function initMap(is_refresh) {
            
            //new code for route
            var color = ["blue", "green", "red", "purple", "skyblue", "yellow", "orange"];
            // const haightAshbury = {
            //     lat: allagent.length != 0 && allagent[0].agentlog && allagent[0].agentlog['lat']  != "0.00000000" ? parseFloat(allagent[0].agentlog['lat']):defaultlat,
            //     lng: allagent.length != 0 && allagent[0].agentlog && allagent[0].agentlog['long'] != "0.00000000" ? parseFloat(allagent[0].agentlog['long']):defaultlong
            // };
            const haightAshbury = {
                lat: allagent.length !== 0 &&
                    allagent[0].agentlog &&
                    allagent[0].agentlog['lat'] !== "0.00000000" &&
                    allagent[0].agentlog['lat'] !== null
                    ? parseFloat(allagent[0].agentlog['lat'])
                    : defaultlat,
                lng: allagent.length !== 0 &&
                    allagent[0].agentlog &&
                    allagent[0].agentlog['long'] !== "0.00000000" &&
                    allagent[0].agentlog['long'] !== null
                    ? parseFloat(allagent[0].agentlog['long'])
                    : defaultlong,
                };


            if(is_refresh==1)
            {
                map = new google.maps.Map(document.getElementById("map_canvas"), {
                    zoom: 12,
                    center: haightAshbury,
                    mapTypeId: "roadmap",
                    styles: themeType,
                });
        
                // Adds a marker at the center of the map.
                for (let i = 0; i < olddata.length; i++) {
                    checkdata = olddata[i];
                    var urlnewcreate = '';
                    if(checkdata['task_status'] == 0){
                        urlnewcreate = 'unassigned';
                    }else if(checkdata['task_status'] == 1 || checkdata['task_status'] == 2){
                        urlnewcreate = 'assigned';
                    }else if(checkdata['task_status'] == 3){
                        urlnewcreate = 'complete';
                    }else{
                        urlnewcreate = 'faild';
                    }
            
                    if(checkdata['task_type_id'] == 1){
                        urlnewcreate += '_P.png';
                    }else if(checkdata['task_type_id'] == 2){
                        urlnewcreate +='_D.png';
                    }else{
                        urlnewcreate +='_A.png';
                    }
            
                    img = '{{ asset('assets/newicons/') }}'+'/'+urlnewcreate;
            
                    send = null;
                    type = 1;
                    addMarker({
                        lat: parseFloat(checkdata['latitude']),
                        lng:  parseFloat(checkdata['longitude'])
                    }, send, img,checkdata,type);
                }
            }else{
                deleteAgentMarks();
                clearRoutes();
            }
    
            $.each(allroutes, function(i, item) {
                const directionsService = new google.maps.DirectionsService();
                const directionsRenderer = new google.maps.DirectionsRenderer({suppressMarkers: true});
                if(i < color.length)
                {
                    var routecolor = color[i];
                }else{
                    var routecolor = "pink";
                }
                directionsRenderer.setOptions({
                    polylineOptions: {
                        strokeColor: routecolor
                    }
                });
                directionsRenderer.setMap(map);
                var al_task = allroutes[i].task_details;
                var agent_locatn = allroutes[i].driver_detail;
                calculateAndDisplayRoute(directionsService, directionsRenderer, map, al_task, agent_locatn);
            });
           
            
            //agents markers
            for (let i = 0; i < allagent.length; i++) {
                displayagent = allagent[i];
                if(displayagent.agentlog != null && displayagent.agentlog['lat'] != "0.00000000" && displayagent.agentlog['lat'] != null && displayagent.agentlog['long'] != "0.00000000"  && displayagent.agentlog['long'] != null){
                    if (displayagent['is_available'] == 1) {
                        images = url+'/demo/images/location.png';
                    }else {
                       
                        images = url+'/demo/images/location_grey.png';
                    }
                    var image = {
                        url: images, // url
                        scaledSize: new google.maps.Size(50, 50), // scaled size
                        origin: new google.maps.Point(0,0), // origin
                        anchor: new google.maps.Point(22,22) // anchor
                    };
                    send = null;
                    type = 2;
                  
                    addMarker({lat: parseFloat(displayagent.agentlog['lat']),
                    lng:  parseFloat(displayagent.agentlog['long'])
                }, send, image,displayagent, type);
            }
        }
    
        map.setCenter(haightAshbury);
    }

    function deleteAgentMarks() {
        for (let i = 0; i < driverMarkers.length; i++) {
            driverMarkers[i].setMap(null);
        }
    }

    // function for displaying route  on map
    function calculateAndDisplayRoute(directionsService, directionsRenderer, map, alltask, agent_location)
    {
        const waypts = [];
        const checkboxArray = document.getElementById("waypoints");
        
        for (let i = 0; i < alltask.length; i++) {
        if (i != alltask.length - 1 && alltask[i].task_status != 4 && alltask[i].task_status != 5 ) {
            waypts.push({
                location: new google.maps.LatLng(parseFloat(alltask[i].latitude), parseFloat(alltask[i]
                    .longitude)),
                stopover: true,
            });
        }
        var image = url+'/assets/newicons/'+alltask[i].task_type_id+'.png';
    }

    directionsService.route({
            origin: new google.maps.LatLng(parseFloat(agent_location.lat), parseFloat(agent_location.long)),
            destination: new google.maps.LatLng(parseFloat(alltask[alltask.length - 1].latitude),
                parseFloat(alltask[alltask.length - 1].longitude)),
            waypoints: waypts,
            optimizeWaypoints: false,
            travelMode: google.maps.TravelMode.DRIVING,
        },
        (response, status) => {
            if (status === "OK" && response) {
                directionsRenderer.setDirections(response);
                directionsArray.push(directionsRenderer);
            } else {
                //window.alert("Directions request failed due to " + status);
            }
        }
    );
  }

function clearRoutes() {
    if (directionsArray.length <1 ) {
        //alert("No directions have been set to clear");
        return;
    }
    else {
        $('#directions').hide();
        for (var i = 0;i< directionsArray.length; i ++) {
            if (directionsArray [i] !== -1) {
                directionsArray [i].setMap(null);
            }
        }
        directionsArray = [];
        return;
    }
}

// Adds a marker to the map and push to the array.
function addMarker(location, lables, images, data, type) {

    
    var contentString = '';
    if(type == 1){
        contentString =
        '<div id="content">' +
        '<div id="siteNotice">' +
        "</div>" +
        '<h5 id="firstHeading" class="firstHeading">'+data['driver_name']+'</h5>' +
        '<h6 id="firstHeading" class="firstHeading">'+data['task_type']+'</h6>' +
        '<div id="bodyContent">' +
        "<p><b>Address :- </b> " +data['address']+ " " +
        ".</p>" +
        '<p><b>Customer: '+data['customer_name']+'</b>('+data['customer_phone_number']+') </p>' +
        "</div>" +
        "</div>";
    }else{
        img = data['image_url'];
        contentString =
        '<div class="row no-gutters align-items-center">'+
            '<div class="col-sm-4">'+
                '<div class="img_box mb-sm-0 mb-2"> <img src="'+data["image_url"]+'"/></div> </div>'+
            '<div class="col-sm-8 pl-2 user_info">'+
                '<div class="user_name mb-2 11"><label class="d-block m-0">'+data["name"]+'</label><span> <i class="fas fa-phone-alt"></i>'+data["phone_number"]+'</span></div>'+
                '<div><b class="d-block mb-2"><i class="far fa-clock"></i> <span> '+jQuery.timeago(new Date(data['agentlog']['created_at']))+
                ' </span></b> <b><i class="fas fa-mobile-alt"></i> '+data['agentlog']['device_type']+'</b> <b class="ml-2"> <i class="fas fa-battery-half"></i>  '+data['agentlog']['battery_level']+'%</b>';
               if(data['get_driver'][0]){
                    contentString +='<a target="_blank" href="fleet/details/'+btoa(data['get_driver'][0]['id'])+'"><b class="d-block mt-2"><i class="fa fa-car"></i><span>'+data['get_driver'][0]['name']+' </span></b></a>';
                }
                contentString +='</div>';
            '</div>'+
        '</div>';
    }

    const infowindow = new google.maps.InfoWindow({
        content: contentString,
        minWidth: 250,
        minheight: 250,
    });


    const marker = new google.maps.Marker({
        position: location,
        label: lables,
        icon: images,
        map: map,
        //animation: google.maps.Animation.DROP,
    });

 
    if (type == 2) {
        driverMarkers.push(marker)
    }

    markers.push(marker);
  
    marker.addListener("click", () => {
    infowindow.open(map, marker);
    });
}

// Sets the map on all markers in the array.
function setMapOnAll(map) {
    for (let i = 0; i < markers.length; i++) {
        markers[i].setMap(null);
    }
}

// Removes the markers from the map, but keeps them in the array.
function clearMarkers() {
    setMapOnAll(null);
}

// Shows any markers currently in the array.
function showMarkers() {
    setMapOnAll(map);
}

// Deletes all markers in the array by removing references to them.
function deleteMarkers() {
    clearMarkers();
    markers = [];
}


// $(".datetime").on('change', function(){
//     loadTeams(1, 1);
//     loadOrders(1, 1);
//     old_channelname = channelname;
//     old_logchannelname = logchannelname;
//     channelname = "orderdata{{$client_code}}"+$(this).val();
//     logchannelname = "agentlog{{$client_code}}"+$(this).val();
//     if(old_channelname != channelname)
//     {
//         ListenDataChannel();
//         ListenAgentLogChannel();
//     }
// });

function handler(){
    loadTeams(1, 1);
    loadOrders(1, 1);
    old_channelname = channelname;
    old_logchannelname = logchannelname;
    channelname = "orderdata{{$client_code}}"+$(this).val();
    logchannelname = "agentlog{{$client_code}}"+$(this).val();
    if(old_channelname != channelname)
    {
        ListenDataChannel();
        ListenAgentLogChannel();
    }
}

//function fot optimizing route
function RouteOptimization(taskids, distancematrix, optimize, agentid, date) {
    $('#routeTaskIds').val(taskids);
    $('#routeMatrix').val(distancematrix);
    $('#routeOptimize').val(optimize);
    $('#routeAgentid').val(agentid);
    $('#routeDate').val(date);
    $('#optimizeType').val('optimize');
    $("input[name='driver_start_location'][value='current']").prop("checked", true);
    $('#addressBlock').css('display','none');
    $('#addressTaskBlock').css('display','none');
    $('#selectedtasklocations').html('');
    $('.selecttask').css('display','');
    $.ajax({
        type: 'POST',
        url: '{{url("/get-tasks")}}',
        headers: {
            'X-CSRF-Token': '{{ csrf_token() }}',
        },
        data: {'taskids':taskids},
        success: function(response) {
            var data = $.parseJSON(response);
            for (var i = 0; i < data.length; i++) {
                var object = data[i];
                var task_id =  object['id'];
                var tasktypeid = object['task_type_id'];
                var current_location = object['current_location'];
                if(current_location == 0)
                {
                    $('input[type=radio][name=driver_start_location]').prop('checked', false);
                    $("input[type=radio][name=driver_start_location][value='current']").remove();
                    $("#radio-current-location-span").remove();
                    $("input[type=radio][name=driver_start_location][value='select']").click();
                }
                
                if(tasktypeid==1)
                {
                    tasktype = "Pickup";
                }else if(tasktypeid==2)
                {
                    tasktype = "Dropoff";
                }else{
                    tasktype = "Appointment";
                }
                
                var location_address =  object['location']['address'];
                var shortname =  object['location']['short_name'];

                var option   = '<option value="'+task_id+'">'+tasktype+' - '+shortname+' - '+location_address+'</option>';
                $('#selectedtasklocations').append(option);
            }
        },
        error: function(response) {
            
        }
    });
    $('#optimize-route-modal').modal('show');
}


// on submiting optimization popup
$('.submitoptimizeForm').click(function(){
    var driverStartTime = $('.driverStartTime').val();
    var driverTaskDuration = $('.driverTaskDuration').val();
    var driverBrakeStartTime = $('.driverBrakeStartTime').val();
    var driverBrakeEndTime = $('.driverBrakeEndTime').val();
    var sortingtype = $('#optimizeType').val();
    var err = 0;
    if(driverStartTime=='')
    {
        $('#DriverStartTime span').css('display','block');
        err = 1;
    }

    if(driverTaskDuration=='')
    {
        $('#DriverTaskDuration span').css('display','block');
        err = 1;
    }
    if(driverBrakeStartTime=='')
    {
        $('#DriverBrakeStartTime span').css('display','block');
        err = 1;
    }

    if(driverBrakeEndTime=='')
    {
        $('#DriverBrakeEndTime span').css('display','block');
        err = 1;
    }

    if(err == 0)
    {
        $('.routetext').text('Optimizing Route');
        $('#optimize-route-modal').modal('hide');
        spinnerJS.showSpinner()
        var formdata =$('form#optimizerouteform').serialize();
        if(sortingtype=='optimize')
        {
            var formurl = '{{url("/optimize-route")}}';
        }else{
            var formurl = '{{url("/optimize-arrange-route")}}';
        }
        $.ajax({
                type: 'POST',
                url: formurl,
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data : formdata,
                success: function(response) {
                    if(response!="Try again later")
                    {
                        loadTeams(1, 1);
                        loadOrders(1, 1);
                        spinnerJS.hideSpinner();
                    }else{
                        alert(response);
                        spinnerJS.hideSpinner();
                    }
                },
                error: function(response) {

                }
            });
    }

});

function cancleForm()
{
    $('#optimizerouteform').trigger("reset");
    $('#optimize-route-modal').modal('hide');
}

// autoload dashbard
function loadTeams(is_load_html, is_show_loader)
{
    if(is_load_html == 1)
    {
        closeAllAccordian();
    }
    if(is_show_loader == 1)
    {
        spinnerJS.showSpinner();
    }
    var checkuserstatus = $('input[name="user_status"]:checked').val();
    var team_id = $('#team_id').val();
    var search_by_name = $('#search_by_name').val();
    $.ajax({
        type: 'POST',
        url: "{{ route('dashboard.agent-teamsdata')}}",
        headers: {
            'X-CSRF-Token': '{{ csrf_token() }}',
        },
        data: {'userstatus':checkuserstatus, 'team_id':team_id, 'search_by_name':search_by_name, 'is_load_html':is_load_html, 'routedate':$("#basic-datepicker").val()},
        success: function(result) {
            olddata = allagent = defaultmaplocation = [];
            //if Html is required to load or not, for agent's log it is not required

            if(is_load_html == 1)
            {
                $("#teams_container").empty();
                $("#teams_container").html(result);

                if(is_show_loader == 1)
                {
                    spinnerJS.hideSpinner();
                }
                initializeSortable();

                if($("#newmarker_map_data").val()!=''){
                    olddata  = JSON.parse($("#newmarker_map_data").val());
                }

                if($("#agents_map_data").val()!=''){
                    allagent  = JSON.parse($("#agents_map_data").val());
                }

                if($("#uniquedrivers_map_data").val()!=''){
                    allroutes  = JSON.parse($("#uniquedrivers_map_data").val());
                }

                if($("#agentslocations_map_data").val()!=''){
                    defaultmaplocation = JSON.parse($("#agentslocations_map_data").val());
                    defaultlat = parseFloat(defaultmaplocation[0].lat);
                    defaultlong = parseFloat(defaultmaplocation[0].long);
                }
            }else{
                var data1 = JSON.parse(result);
                if(data1['status'] == "success")
                {// setting up required variables to refreshing the google map route
                    olddata = data1['newmarker'];
                    allagent = data1['agents'];
                    allroutes = data1['routedata'];
                    defaultlat = parseFloat(data1['defaultCountryLatitude']);
                    defaultlong = parseFloat(data1['defaultCountryLongitude']);
                }
            }

            initMap(is_load_html);
        },
        error: function(data) {
            Swal.fire({
                    icon: 'error',
                    title: 'Oops',
                    text: 'There is some issue. Try again later',
                });
            if(is_load_html == 1)
            {
                spinnerJS.hideSpinner();
            }
        }
    });
}

// autoload dashbard
function loadOrders(is_load_html, is_show_loader, url = '')
{ 
    if(is_load_html == 1)
    {
        closeAllAccordian();
    }
    if(is_show_loader == 1)
    {
        spinnerJS.showSpinner();
    }
    var checkuserroutes = $('input[name="user_routes"]:checked').val();
    var agent_id = $('#agent_id').val();
    url = url ? url : "{{ route('dashboard.agent-orderdata')}}";
    $.ajax({
        type: 'POST',
        url: url,
        headers: {
            'X-CSRF-Token': '{{ csrf_token() }}',
        },
        data: {'agent_id':agent_id, 'checkuserroutes':checkuserroutes, 'is_load_html':is_load_html, 'routedate':$("#basic-datepicker").val()},
        success: function(result) {
            olddata = allagent = defaultmaplocation = [];
            
            //if Html is required to load or not, for agent's log it is not required

            if(is_load_html == 1)
            {
                $("#agent_route_container").empty();
                $("#agent_route_container").html(result);

                if(is_show_loader == 1)
                {
                    spinnerJS.hideSpinner();
                }
                initializeSortable();

              /*  if($("#newmarker_map_data").val()!=''){
                    olddata  = JSON.parse($("#newmarker_map_data").val());
                }

                if($("#agents_map_data").val()!=''){
                    allagent  = JSON.parse($("#agents_map_data").val());
                }

                if($("#uniquedrivers_map_data").val()!=''){
                    allroutes  = JSON.parse($("#uniquedrivers_map_data").val());
                }

                if($("#agentslocations_map_data").val()!=''){
                    defaultmaplocation = JSON.parse($("#agentslocations_map_data").val());
                    defaultlat = parseFloat(defaultmaplocation[0].lat);
                    defaultlong = parseFloat(defaultmaplocation[0].long);
                }*/
            }else{
                var data1 = JSON.parse(result);
                if(data1['status'] == "success")
                {// setting up required variables to refreshing the google map route
                    olddata = data1['newmarker'];
                    allagent = data1['agents'];
                    allroutes = data1['routedata'];
                    defaultlat = parseFloat(data1['defaultCountryLatitude']);
                    defaultlong = parseFloat(data1['defaultCountryLongitude']);
                }
            }

            initMap(is_load_html);
        },
        error: function(data) {
            Swal.fire({
                    icon: 'error',
                    title: 'Oops',
                    text: 'There is some issue. Try again later',
                });
            if(is_load_html == 1)
            {
                spinnerJS.hideSpinner();
            }
        }
    });
}


function initializeSortable()
{
    $(".dragable_tasks").sortable({
        update : function(event, ui) {
            $('.routetext').text('Arranging Route');
            spinnerJS.showSpinner();
            var divid = $(this).attr('id');
            var params = $(this).attr('params');
            var agentid = $(this).attr('agentid');
            var date = $(this).attr('date');

            var taskorder = "";
            jQuery("#"+divid+" .card-body.ui-sortable-handle").each(function (index, element) {
                taskorder = taskorder + $(this).attr('task_id') + ",";
            });
            $('input[type=radio][name=driver_start_location]').prop('checked', false);
            $.ajax({
                type: 'POST',
                url: '{{url("/arrange-route")}}',
                headers: {
                    'X-CSRF-Token': '{{ csrf_token() }}',
                },
                data: {'taskids':taskorder,'agentid':agentid,'date':date},

                success: function(response) {
                    var data = $.parseJSON(response);

                    $('.totdis'+agentid).html(data.total_distance);
                    var funperams = '<span class="optimize_btn" onclick="RouteOptimization('+params+')">Optimize</span>';
                    $('.optimizebtn'+agentid).html(funperams);
                    spinnerJS.hideSpinner();
                    $('#routeTaskIds').val(taskorder);
                    $('#routeMatrix').val('');
                    $('#routeOptimize').val('');
                    $('#routeAgentid').val(agentid);
                    $('#routeDate').val(date);
                    $('#optimizeType').val('dragdrop');
                    $("input[name='driver_start_location'][value='current']").prop("checked",true);
                    $('#addressBlock').css('display','none');
                    $('#addressTaskBlock').css('display','none');
                    $('#selectedtasklocations').html('');
                    $('.selecttask').css('display','none');

                    if(data.current_location == 0)
                    {
                        $("input[type=radio][name=driver_start_location][value='current']").remove();
                        $("#radio-current-location-span").remove();
                        $("input[type=radio][name=driver_start_location][value='select']").click();
                    }
                    $('#optimize-route-modal').modal('show');
                },
                error: function(response) {
                    Swal.fire({
                    icon: 'error',
                    title: 'Oops',
                    text: 'There is some issue. Try again later',
                });
                    spinnerJS.hideSpinner();
                }
            });
        }
    });
}

$(document).on('click', '.unassigned-badge', function(){
    $("#route-assign-agent-modal").modal('show');
    var order_id = $(this).data('id');
    $('#order_id').val(order_id);
});

$(document).on('click', '.submitassignAgentForm', function(){
    var order_id = Array($('#order_id').val());
    var agent_id = $('#select_agent_id').val();
    if(agent_id != ''){
        $.ajax({
            type: "POST",
            url: '{{route("assign.agent")}}',
            data: {_token: CSRF_TOKEN, orders_id: order_id, agent_id: agent_id},
            success: function( msg ) {
                $.toast({ 
                    heading:"Success!",
                    text : "{{__(getAgentNomenclature()) }} assigned successfully.", 
                    showHideTransition : 'slide', 
                    bgColor : 'green',              
                    textColor : '#eee',            
                    allowToastClose : true,      
                    hideAfter : 5000,            
                    stack : 5,                   
                    textAlign : 'left',         
                    position : 'top-right'
                });
                $("#route-assign-agent-modal").modal('hide');
                loadOrders(1, 1);
            },
            error: function(errors){
                $.toast({ 
                    heading:"Error!",
                    text : "{{__(getAgentNomenclature()) }} can not be assigned.", 
                    showHideTransition : 'slide', 
                    bgColor : 'red',              
                    textColor : '#eee',            
                    allowToastClose : true,      
                    hideAfter : 5000,            
                    stack : 5,                   
                    textAlign : 'left',         
                    position : 'top-right'      
                });
                $("#route-assign-agent-modal").modal('hide');
                loadOrders(1, 1);
            }
        });
    }
});

function reloadData() {
    location.reload();
}

function openAllAccordian() {
    $("#accordion").find(`[data-toggle="collapse"]`).removeClass('collapsed');
    $("#accordion").find(`[data-toggle="collapse"]`).attr('aria-expanded','true');
    $(".collapse").addClass('show');
    $(".allAccordian").html('<span class="" onclick="closeAllAccordian()">Close All</span>');
}

function closeAllAccordian() {
    $("#accordion").find(`[data-toggle="collapse"]`).addClass('collapsed');
    $("#accordion").find(`[data-toggle="collapse"]`).attr('aria-expanded','false');
    $(".collapse").removeClass('show');
    $(".allAccordian").html('<span class="" onclick="openAllAccordian()">{{__("Open All")}}</span>');
}

function NavigatePath(taskids,distancematrix,optimize,agentid,date) {
    $('.routetext').text('Exporting Pdf');
    spinnerJS.showSpinner()

    $.ajax({
        type: 'POST',
        url: '{{url("/export-path")}}',
        headers: {
            'X-CSRF-Token': '{{ csrf_token() }}',
        },
        data: {'taskids':taskids,'agentid':agentid,'date':date},

        success: function(response) {
            if(response!="Try again later")
            {
                $('#pdfvalue').val(response);
                $("#pdfgenerate").submit();
                spinnerJS.hideSpinner()
            }else{
                alert(response);
                spinnerJS.hideSpinner()
            }
        },
        error: function(response) {

        }
    });
}

$('input[type=radio][name=driver_start_location]').change(function() {
    if (this.value == 'current') {
        $('#addressBlock').css('display','none');
        $('#addressTaskBlock').css('display','none');
    }
    else if (this.value == 'select') {
        $('#addressBlock').css('display','block');
        $('#addressTaskBlock').css('display','none');
    }
    else if(this.value == 'task_location') {
        $('#addressTaskBlock').css('display','block');
        $('#addressBlock').css('display','none');
    }
});
</script>
<script src="{{ asset('js/app.js') }}"></script>
<script>
//function to listen different channels of event of different dates and different agent status
function ListenDataChannel()
{
    //leave/not listen previous channel in case filters have been changed
    Echo.leave(old_channelname);

    //listen route add/update/delete/assigned/completed event
    Echo.channel(channelname)
    .listen('loadDashboardData', (e) => {
        var heading = "";
        var message = "";
        var toastcolor = "";
        if(typeof(e.order_status) != "undefined")
        {
            if(e.order_status == "unassigned")
            {
                heading = "Created";
                message = "Route Created/Updated.";
                toastcolor = "green";
            }
            else if(e.order_status == "assigned")
            {
                heading = "Assigned";
                message = "Route Assigned to {{__(getAgentNomenclature())}}.";
                toastcolor = "orange";
            }
            else if(e.order_status == "completed")
            {
                heading = "Completed";
                message = "Route Completed by {{__(getAgentNomenclature())}}.";
                toastcolor = "green";
            }
            else
            {
                heading = "Deleted";
                message = "Route Deleted.";
                toastcolor = "red";
            }
        }
        else
        {
            heading = "Deleted";
            message = "Route Deleted.";
            toastcolor = "red";
        }

        if(heading!='')
        {
            loadTeams(1, 0);
            $.toast({
                heading:heading,
                text : message,
                showHideTransition : 'slide',
                bgColor : toastcolor,
                textColor : '#eee',
                allowToastClose : true,
                hideAfter : 5000,
                stack : 5,
                textAlign : 'left',
                position : 'top-right'
            });
        }
    });
}

function ListenAgentLogChannel()
{
    Echo.leave(old_logchannelname);
    //listen agent log updation event
    Echo.channel(logchannelname)
    .listen('agentLogFetch', (e) => {
        loadTeams(0, 0);
    });
}

$(document).on('click', '.view_route-btn', function (e) {
    var id = $(this).data('id');

    // Remove old route and markers
    if (typeof directionsRenderer !== 'undefined') {
        directionsRenderer.setDirections({ routes: [] });
    }
    
    if (typeof markers !== 'undefined') {
        for (var i = 0; i < markers.length; i++) {
            markers[i].setMap(null);
        }
        markers = [];
    }

    $.ajax({
        type: 'POST',
        url: "{{ route('get-route-detail')}}",
        headers: {
            'X-CSRF-Token': '{{ csrf_token() }}',
        },

        data: { 'id': id },
        success: function (data) {
            var pickup_lat = data.pickup_location.lat;
            var pickup_lng = data.pickup_location.lng;
            var dest_lat = data.dropoff_location.lat;
            var dest_lng = data.dropoff_location.lng;

            var pickupLocation = new google.maps.LatLng(pickup_lat, pickup_lng);
            var dropoffLocation = new google.maps.LatLng(dest_lat, dest_lng);

            var request = {
                origin: pickupLocation,
                destination: dropoffLocation,
                travelMode: google.maps.TravelMode.DRIVING,
            };

            if (typeof directionsRenderer === 'undefined') {
                directionsRenderer = new google.maps.DirectionsRenderer({ suppressMarkers: true });
                directionsRenderer.setMap(map);
            }

            // Assuming you have a global variable 'map'
            if (typeof map !== 'undefined') {
                map.setCenter(new google.maps.LatLng(pickup_lat, pickup_lng));
                map.setZoom(10);
            } else {
                map = new google.maps.Map(document.getElementById("map_canvas"), {
                    zoom: 15,
                    center: new google.maps.LatLng(pickup_lat, pickup_lng),
                    mapTypeId: "roadmap",
                    styles: themeType,
                });
            }

            var directionsService = new google.maps.DirectionsService();
            directionsService.route(request, function (response, status) {
                if (status === 'OK') {
                    directionsRenderer.setDirections(response);

                    var pickupMarker = new google.maps.Marker({
                        position: pickupLocation,
                        map: map,
                        title: 'Pickup Location',
                    });

                    var dropoffMarker = new google.maps.Marker({
                        position: dropoffLocation,
                        map: map,
                        title: 'Dropoff Location',
                    });

                    markers.push(pickupMarker);
                    markers.push(dropoffMarker);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Directions request failed: ' + status,
                    });
                }
            });
        },
        error: function (data) {
            Swal.fire({
                icon: 'error',
                title: 'Oops',
                text: 'There is some issue. Try again later',
            });
            spinnerJS.hideSpinner();
        }
    });
});


$(document).on('click', '#load-more', function(e){
    let url = $(this).data('url');
    
    closeAllAccordian();
    spinnerJS.showSpinner();
    
    var checkuserroutes = $('input[name="user_routes"]:checked').val();
    var agent_id = $('#agent_id').val();
    $(this).remove();
    $.ajax({
        type: 'POST',
        url: url,
        headers: {
            'X-CSRF-Token': '{{ csrf_token() }}',
        },
        data: {'agent_id':agent_id, 'checkuserroutes':checkuserroutes, 'is_load_html':1, 'routedate':$("#basic-datepicker").val()},
        success: function(result) {
            $("#handle-dragula-left0").append(result);
            spinnerJS.hideSpinner();
        },
        error: function(data) {
            Swal.fire({
                    icon: 'error',
                    title: 'Oops',
                    text: 'There is some issue. Try again later',
                });
            spinnerJS.hideSpinner();
        }
    });
})
$(document).on('click', '#load-more-teams', function(e){
    let url = $(this).data('url');
  
    $('#load-more-teams').remove();
    spinnerJS.showSpinner();
    var checkuserstatus = $('input[name="user_status"]:checked').val();
    $.ajax({
        type: 'POST',
        url: url,
        headers: {
            'X-CSRF-Token': '{{ csrf_token() }}',
        },
        data: {'userstatus':checkuserstatus,'is_load_html':1, 'routedate':$("#basic-datepicker").val()},
        success: function(result) {
            //if Html is required to load or not, for agent's log it is not required

            $("#teams_container .teams-data:last").after(result);

                spinnerJS.hideSpinner();

        },
        error: function(data) {
            Swal.fire({
                    icon: 'error',
                    title: 'Oops',
                    text: 'There is some issue. Try again later',
                });
           
                spinnerJS.hideSpinner();
            
        }
    });
})
</script>

<style>
.gm-style-iw.gm-style-iw-c {
    width: 300px !important;
}
.img_box {
    width: 100%;
    height: 100px;
    border-radius: 8px;
    overflow: hidden;
}

.img_box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.user_name label {
    font-size: 14px;
    color: #000;
    text-transform: capitalize;
    margin: 0 0 12px !important;
    display: block;
}
.user_info i {
    font-size: 14px;
    width: 20px;
    text-align: center;
    color: #6658dd;
}

.user_info span,.user_info b {
    font-size: 12px;
    font-weight: 500;
}

.pageloader {
    width: 100%;
    height: 100%;
   position: absolute;
    top: 40%;
    z-index: 999;
    left: 50%;
}

.box {
    background: #fff;
    width: 250px;
    height: 170px;
    text-align: center;
    padding: 17px;
    color: blue;
    opacity: 0.9;
    border-radius: 5px;
}

.box h4 {
    color: #3283f6;
}

</style>

@endsection
