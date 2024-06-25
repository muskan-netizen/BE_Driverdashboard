
@extends('layouts.vertical', ['title' => 'Dashboard','demo'=>'creative'])
{{-- Variable section --}}
@php
    use Carbon\Carbon;
    $color = ['one','two','three','four','five','six','seven','eight'];
    $imgproxyurl = 'https://imgproxy.royodispatch.com/insecure/fill/60/60/sm/0/plain/https://'.env('AWS_BUCKET').'.s3.us-west-2.amazonaws.com/';
    $agent_lat_longs = json_encode(@$agentMarkerData);
    $socket_url = env('SOCKET_URL').'/socket.io/socket.io.js';
    $app_name = env('APP_NAME');
   
@endphp

{{--End Variable section --}}


@section('css')
    @include("dashboard/parts/layout-$dashboard_theme/top")
@endsection



@section('content')
    @include("dashboard/parts/layout-$dashboard_theme/left")
    @include("dashboard/parts/layout-$dashboard_theme/right")
@endsection


@section('script')
    <script>
        var agentsLatLong =`<?php  echo $agent_lat_longs  ?>`;
        var channelname = "orderdata{{ $client_code }}{{ date('Y-m-d', time()) }}";
        var logchannelname = "agentlog{{ $client_code }}{{ date('Y-m-d', time()) }}";
        var imgproxyurl = {!! json_encode($imgproxyurl) !!};
        var optimizeRouteUrl = "{{ url('/optimize-route') }}";
        var optimizeArrangeRouteUrl = "{{ url('/optimize-arrange-route') }}";
        var assignAgentUrl = "{{ route('assign.agent') }}";
        var getRouteDetailUrl = "{{ route('get-route-detail') }}";
        var X_CSRF_TOKEN = '{{ csrf_token() }}';
        var iconsRoute = "{{ asset('assets/newicons/') }}";
        var teamDataUrl = "{{ route('dashboard.teamsdata') }}";
        var orderDataUrl = "{{ route('dashboard.orderdata') }}";
        var channelName = "orderdata{{ $client_code }}";
        var logChannelName = "agentlog{{ $client_code }}";
        var dashboardTheme = "{{ $dashboard_theme }}";
        var exportPathUrl = "{{ url('/export-path') }}";
        var getTasks = "{{ url('/get-tasks') }}";
        var arrangeRoute  = "{{ url('/arrange-route') }}";
        var getAgentNomenclature = "{{ __(getAgentNomenclature()) }}";
        var const_img ='/assets/images/profile-pic-dummy.png';
        var socket_url = '{{ Request::getHost() }}/socket.io/socket.io.js';
        var app_name = '{{ $app_name }}';
        var agentFilter = "{{url('agent/filter')}}";


    
    </script>
     @include("dashboard/parts/layout-$dashboard_theme/bottom")
@endsection


