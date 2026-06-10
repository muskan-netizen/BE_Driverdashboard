@extends('layouts.vertical', ['title' => 'Analytics','demo'=>'creative'])

@section('css')
    <!-- Plugins css -->
    <link href="{{asset('assets/libs/flatpickr/flatpickr.min.css')}}" rel="stylesheet" type="text/css" />
    <link href="{{asset('assets/libs/selectize/selectize.min.css')}}" rel="stylesheet" type="text/css" />
    <style>
       td.view_analytics{text-decoration:underline;color:#00f;cursor:pointer}.agent-list li a.total_amt{color:#000}#map{height:485px;width:100%}.stv-radio-buttons-wrapper{clear:both;display:inline-block}.agent-list li{list-style:none;padding:2px 0;display: flex;align-items: center;justify-content: space-between;}.stv-radio-button{position:absolute;left:-9999em;top:-9999em}.agent-head li{padding:8px 0}.stv-radio-button+label{float:left;padding:.3em .8em;cursor:pointer;border:1px solid #697480;margin-right:-1px;color:#fff;background-color:#697480;font-size:12px}.agent-list li a{color:#fff;height:30px;border-radius:50%;width:30px;display:flex;align-items:center;justify-content:center;font-size:12px;cursor:not-allowed!important}.agent-list li a.view_analytics{color:#fff;cursor:pointer!important}.stv-radio-button+label:first-of-type{border-radius:.7em 0 0 .7em}.stv-radio-button+label:last-of-type{border-radius:0 .7em .7em 0}.ordr-details{font-size:16px}.stv-radio-button:checked+label{background-color:#3c4854;border:1px solid #3c4854}
       .card-box-head {position: relative;height: 300px;overflow: auto;}
       .table-responsive.card-box-head {display: block;}
    </style>
@endsection
@php
    $imgproxyurl = 'https://imgproxy.royodispatch.com/insecure/fill/90/90/sm/0/plain/';
@endphp
@section('content')
    <!-- Start Content-->
    <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box">
                        <div class="page-title-right">
                            <form class="form-inline" name="reset" id="resetaccunting" method="get" action="{{route('accounting')}}">
                                <div class="form-group">
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control border" id="dash-daterange" name="date">
                                        <div class="input-group-append">
                                            <span class="input-group-text bg-blue border-blue text-white">
                                                <i class="fe-search" onclick="handleClick(this);" ></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <a href="javascript: void(0);" class="btn btn-blue btn-sm ml-2">
                                    <i class="mdi mdi-autorenew"></i>
                                </a>
                                <a href="javascript: void(0);" class="btn btn-blue btn-sm ml-1">
                                    <i class="mdi mdi-filter-variant"></i>
                                </a>
                            </form>
                        </div>
                        {{-- <h4 class="page-title">Tasks</h4> --}}
                    </div>
                </div>
            </div>


            <div class="row">

                <div class="col-md-12">
                    <h3 class="page-title">{{__("Analytics")}}</h3>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="widget-rounded-circle card-box">
                        <div class="row">
                            <div class="col-6">
                                <div class="avatar-lg rounded-circle bg-soft-primary border-primary border">
                                <i class="fe-heart font-22 avatar-title text-primary"></i>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-right">
                                    <h3 class="text-dark mt-1"><span data-plugin="counterup">{{$totalearning}}</span></h3>
                                    <p class="text-muted mb-1 text-truncate">{{__("Platform Earning")}}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="widget-rounded-circle card-box">
                        <div class="row">
                            <div class="col-6">
                                <div class="avatar-lg rounded-circle bg-soft-success border-success border">
                                    <i class="fe-shopping-cart font-22 avatar-title text-success"></i>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-right">
                                    <h3 class="text-dark mt-1"><span data-plugin="counterup">{{$totalagentearning}}</span></h3>
                                    <p class="text-muted mb-1 text-truncate">{{__(getAgentNomenclature()."s's Earning")}}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="widget-rounded-circle card-box">
                        <div class="row">
                            <div class="col-6">

                                <div class="avatar-lg rounded-circle bg-soft-info border-info border">
                                    <i class="fe-bar-chart-line- font-22 avatar-title text-info"></i>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-right">
                                    <h3 class="text-dark mt-1"><span data-plugin="counterup">{{$totalorders}}</span></h3>
                                    <p class="text-muted mb-1 text-truncate">{{__("Orders")}}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="widget-rounded-circle card-box">
                        <div class="row">
                            <div class="col-6">

                                <div class="avatar-lg rounded-circle bg-soft-warning border-warning border">
                                    <i class="fe-eye font-22 avatar-title text-warning"></i>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-right">
                                    <h3 class="text-dark mt-1"><span data-plugin="counterup">{{$totalagents}}</span></h3>
                                    <p class="text-muted mb-1 text-truncate">{{__(getAgentNomenclature()."s")}}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
            <!-- Start agent analytics section -->
            <div class="row">
                <div class="col-md-12">
                    <h3 class="col page-title">{{__("Agent Analytics")}}</h3>
                </div>
                <div class="col-12">
                    <div class="card-box pb-0 h-100">
                        <div class="Agent_list mb-2">
                            <label for="agent_name">Select Agent Name</label>
                            <select name="agent_name" id="agent_name" class="form-control">
                                <option value="">Select</option>
                                @foreach ($agents as $agent)
                                    <option value="{{ $agent->id}}">{{ $agent->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-borderless table-hover table-nowrap table-centered m-0">
                            <thead class="thead-light">
                                <tr>
                                    <th class="bg-info text-white">Order Details</th>
                                    <th class="bg-success text-white">Today Day</th>
                                    <th class="bg-secondary text-white">Prev Day</th>
                                    <th class="bg-success text-white">This Week</th>
                                    <th class="bg-secondary text-white">Prev Week</th>
                                    <th class="bg-success text-white">This Month</th>
                                    <th class="bg-secondary text-white">Prev Month</th>
                                </tr>
                            </thead>
                            <tbody id="agent_analytics_records">
                                @if($order_analytic_data)
                                @php $order_analytics =  json_decode($order_analytic_data['this_day']); @endphp
                                <tr>
                                    <td>
                                        <ul class="agent-list agent-head m-0 p-0">
                                            <li>Unassigned</li>
                                            <li>Assigned</li>
                                            <li>Live Order</li>
                                            <li>Return/Failed</li>
                                            <li>Completed</li>
                                            <li>Total</li>
                                        </ul>
                                    </td>
                                    <td>
                                        <ul class="agent-list  m-0 p-0">
                                            <li><a href="javascript:void(0)" @if($order_analytics->unassigned > 0) class="view_analytics btn btn-primary" data-status ="unassigned"  data-atype="this_day" @else class="btn btn-secondary" @endif>{{ $order_analytics->unassigned }}</a> @if($order_analytics->unassigned_pecentage_this_day) <span class="percentage"><i class="mdi @if($order_analytics->unassigned_pecentage_this_day > 0) mdi-arrow-up text-success @else mdi-arrow-down text-danger @endif"></i> {{ $order_analytics->unassigned_pecentage_this_day }} %</span> @endif</li>
                                            <li><a href="javascript:void(0)" @if($order_analytics->assigned > 0) class="view_analytics btn btn-primary" data-status ="assigned" data-atype="this_day" @else class="btn btn-secondary" @endif>{{ $order_analytics->assigned }}</a> @if($order_analytics->assigned_pecentage_this_day) <span class="percentage"><i class="mdi @if($order_analytics->assigned_pecentage_this_day > 0) mdi-arrow-up text-success @else mdi-arrow-down text-danger @endif"></i> {{ $order_analytics->assigned_pecentage_this_day }} %</span> @endif</li>
                                            <li><a href="javascript:void(0)" @if($order_analytics->order_live > 0) class="view_analytics btn btn-primary" data-status ="live" data-atype="this_day" @else class="btn btn-secondary" @endif>{{ $order_analytics->order_live }}</a> @if($order_analytics->live_order_pecentage_this_day) <span class="percentage"><i class="mdi @if($order_analytics->live_order_pecentage_this_day > 0) mdi-arrow-up text-success @else mdi-arrow-down text-danger @endif"></i> {{ $order_analytics->live_order_pecentage_this_day }} %</span> @endif</li>
                                            <li><a href="javascript:void(0)" @if($order_analytics->order_failed > 0) class="view_analytics btn btn-primary" data-status ="cancelled" data-atype="this_day" @else class="btn btn-secondary" @endif>{{ $order_analytics->order_failed }}</a> @if($order_analytics->failed_order_pecentage_this_day) <span class="percentage"><i class="mdi @if($order_analytics->failed_order_pecentage_this_day > 0) mdi-arrow-up text-success @else mdi-arrow-down text-danger @endif"></i> {{ $order_analytics->failed_order_pecentage_this_day }} %</span> @endif</li>
                                            <li><a href="javascript:void(0)" @if($order_analytics->completed > 0) class="view_analytics btn btn-primary" data-status ="completed" data-atype="this_day" @else class="btn btn-secondary" @endif>{{ $order_analytics->completed }}</a> @if($order_analytics->complete_pecentage_this_day) <span class="percentage"> <i class="mdi @if($order_analytics->complete_pecentage_this_day > 0) mdi-arrow-up text-success @else mdi-arrow-down text-danger @endif"></i> {{ $order_analytics->complete_pecentage_this_day  }} %</span> @endif</li>
                                            <li><a href="javascript:void(0)" class="total_amt">{{ $order_analytics->total_order }}</a> @if($order_analytics->total_order_pecentage_this_day) <span class="percentage"><i class="mdi @if($order_analytics->total_order_pecentage_this_day > 0) mdi-arrow-up text-success @else mdi-arrow-down text-danger @endif"></i> {{ $order_analytics->total_order_pecentage_this_day }} %</span> @endif</li>
                                        </ul>
                                    </td>
                                    <td>
                                        @php $order_analytics =  json_decode($order_analytic_data['prev_day']); @endphp
                                        <ul class="agent-list  m-0 p-0">
                                            <li><a href="javascript:void(0)" @if($order_analytics->unassigned > 0) class="view_analytics btn btn-primary" data-status ="unassigned"  data-atype="prev_day" @else class="btn btn-secondary" @endif>{{ $order_analytics->unassigned }}</a></li>
                                            <li><a href="javascript:void(0)" @if($order_analytics->assigned > 0) class="view_analytics btn btn-primary" data-status ="assigned" data-atype="prev_day" @else class="btn btn-secondary" @endif>{{ $order_analytics->assigned }}</a></li>
                                            <li><a href="javascript:void(0)" @if($order_analytics->order_live > 0) class="view_analytics btn btn-primary" data-status ="live" data-atype="prev_day" @else class="btn btn-secondary" @endif>{{ $order_analytics->order_live }}</a></li>
                                            <li><a href="javascript:void(0)" @if($order_analytics->order_failed > 0) class="view_analytics btn btn-primary" data-status ="cancelled" data-atype="prev_day" @else class="btn btn-secondary" @endif>{{ $order_analytics->order_failed }}</a></li>
                                            <li><a href="javascript:void(0)" @if($order_analytics->completed > 0) class="view_analytics btn btn-primary" data-status ="completed" data-atype="prev_day" @else class="btn btn-secondary" @endif>{{ $order_analytics->completed }}</a></li>
                                            <li><a href="javascript:void(0)" class="total_amt">{{ $order_analytics->total_order }}</a></li>
                                        </ul>
                                    </td>
                                    <td>
                                        @php $order_analytics =  json_decode($order_analytic_data['this_week']); @endphp
                                        <ul class="agent-list  m-0 p-0">
                                            <li><a href="javascript:void(0)" @if($order_analytics->unassigned > 0) class="view_analytics btn btn-primary" data-status ="unassigned"  data-atype="this_week" @else class="btn btn-secondary" @endif>{{ $order_analytics->unassigned }}</a> @if($order_analytics->unassigned_pecentage_this_week)<span class="percentage"> <i class="mdi @if($order_analytics->unassigned_pecentage_this_week > 0) mdi-arrow-up text-success @else mdi-arrow-down text-danger @endif"></i> {{ $order_analytics->unassigned_pecentage_this_week }} %</span> @endif</li>
                                            <li><a href="javascript:void(0)" @if($order_analytics->assigned > 0) class="view_analytics btn btn-primary" data-status ="assigned" data-atype="this_week" @else class="btn btn-secondary" @endif>{{ $order_analytics->assigned }}</a> @if($order_analytics->assigned_pecentage_this_week)<span class="percentage"> <i class="mdi @if($order_analytics->assigned_pecentage_this_week > 0) mdi-arrow-up text-success @else mdi-arrow-down text-danger @endif"></i> {{ $order_analytics->assigned_pecentage_this_week }} %</span> @endif</li>
                                            <li><a href="javascript:void(0)" @if($order_analytics->order_live > 0) class="view_analytics btn btn-primary" data-status ="live" data-atype="this_week" @else class="btn btn-secondary" @endif>{{ $order_analytics->order_live }}</a> @if($order_analytics->live_order_pecentage_this_week) <span class="percentage"><i class="mdi @if($order_analytics->live_order_pecentage_this_week > 0) mdi-arrow-up text-success @else mdi-arrow-down text-danger @endif"></i> {{ $order_analytics->live_order_pecentage_this_week }} %</span> @endif</li>
                                            <li><a href="javascript:void(0)" @if($order_analytics->order_failed > 0) class="view_analytics btn btn-primary" data-status ="cancelled" data-atype="this_week" @else class="btn btn-secondary" @endif>{{ $order_analytics->order_failed }}</a> @if($order_analytics->failed_order_pecentage_this_week) <span class="percentage"><i class="mdi @if($order_analytics->failed_order_pecentage_this_week > 0) mdi-arrow-up text-success @else mdi-arrow-down text-danger @endif"></i> {{ $order_analytics->failed_order_pecentage_this_week }} %</span> @endif </li>
                                            <li><a href="javascript:void(0)" @if($order_analytics->completed > 0) class="view_analytics btn btn-primary" data-status ="completed" data-atype="this_week" @else class="btn btn-secondary" @endif>{{ $order_analytics->completed }}</a> @if($order_analytics->complete_pecentage_this_week) <span class="percentage"> <i class="mdi @if($order_analytics->complete_pecentage_this_week > 0) mdi-arrow-up text-success @else mdi-arrow-down text-danger @endif"></i>  {{ $order_analytics->complete_pecentage_this_week  }} %</span> @endif</li>
                                            <li><a href="javascript:void(0)" class="total_amt">{{ $order_analytics->total_order }}</a> @if($order_analytics->total_order_pecentage_this_week) <span class="percentage"><i class="mdi @if($order_analytics->total_order_pecentage_this_week > 0) mdi-arrow-up text-success @else mdi-arrow-down text-danger @endif"></i>  {{ $order_analytics->total_order_pecentage_this_week }} %</span> @endif</li>
                                        </ul>
                                    </td>

                                    <td>
                                        @php $order_analytics =  json_decode($order_analytic_data['prev_week']); @endphp
                                        <ul class="agent-list  m-0 p-0">
                                            <li><a href="javascript:void(0)" @if($order_analytics->unassigned > 0) class="view_analytics btn btn-primary" data-status ="unassigned"  data-atype="prev_week" @else class="btn btn-secondary" @endif>{{ $order_analytics->unassigned }}</a></li>
                                            <li><a href="javascript:void(0)" @if($order_analytics->assigned > 0) class="view_analytics btn btn-primary" data-status ="assigned" data-atype="prev_week" @else class="btn btn-secondary" @endif>{{ $order_analytics->assigned }}</a></li>
                                            <li><a href="javascript:void(0)" @if($order_analytics->order_live > 0) class="view_analytics btn btn-primary" data-status ="live" data-atype="prev_week" @else class="btn btn-secondary" @endif>{{ $order_analytics->order_live }}</a></li>
                                            <li><a href="javascript:void(0)" @if($order_analytics->order_failed > 0) class="view_analytics btn btn-primary" data-status ="cancelled" data-atype="prev_week" @else class="btn btn-secondary" @endif>{{ $order_analytics->order_failed }}</a></li>
                                            <li><a href="javascript:void(0)" @if($order_analytics->completed > 0) class="view_analytics btn btn-primary" data-status ="completed" data-atype="prev_week" @else class="btn btn-secondary" @endif>{{ $order_analytics->completed }}</a></li>
                                            <li><a href="javascript:void(0)" class="total_amt">{{ $order_analytics->total_order }}</a></li>
                                        </ul>
                                    </td>

                                    <td>
                                        @php $order_analytics =  json_decode($order_analytic_data['this_month']); @endphp
                                        <ul class="agent-list  m-0 p-0">
                                            <li><a href="javascript:void(0)" @if($order_analytics->unassigned > 0) class="view_analytics btn btn-primary" data-status ="unassigned"  data-atype="this_month" @else class="btn btn-secondary" @endif>{{ $order_analytics->unassigned }}</a> @if($order_analytics->unassigned_pecentage_this_month)<span class="percentage"> <i class="mdi @if($order_analytics->unassigned_pecentage_this_month > 0) mdi-arrow-up text-success @else mdi-arrow-down text-danger @endif"></i> {{ $order_analytics->unassigned_pecentage_this_month }} %</span> @endif</li>
                                            <li><a href="javascript:void(0)" @if($order_analytics->assigned > 0) class="view_analytics btn btn-primary" data-status ="assigned" data-atype="this_month" @else class="btn btn-secondary" @endif>{{ $order_analytics->assigned }}</a> @if($order_analytics->assigned_pecentage_this_month)<span class="percentage"> <i class="mdi @if($order_analytics->assigned_pecentage_this_month > 0) mdi-arrow-up text-success @else mdi-arrow-down text-danger @endif"></i> {{ $order_analytics->assigned_pecentage_this_month }} %</span> @endif</li>
                                            <li><a href="javascript:void(0)" @if($order_analytics->order_live > 0) class="view_analytics btn btn-primary" data-status ="live" data-atype="this_month" @else class="btn btn-secondary" @endif>{{ $order_analytics->order_live }}</a> @if($order_analytics->live_order_pecentage_this_month) <span class="percentage"><i class="mdi @if($order_analytics->live_order_pecentage_this_month > 0) mdi-arrow-up text-success @else mdi-arrow-down text-danger @endif"></i> {{ $order_analytics->live_order_pecentage_this_month }} %</span> @endif</li>
                                            <li><a href="javascript:void(0)" @if($order_analytics->order_failed > 0) class="view_analytics btn btn-primary" data-status ="cancelled" data-atype="this_month" @else class="btn btn-secondary" @endif>{{ $order_analytics->order_failed }}</a> @if($order_analytics->failed_order_pecentage_this_month) <span class="percentage"><i class="mdi @if($order_analytics->failed_order_pecentage_this_month > 0) mdi-arrow-up text-success @else mdi-arrow-down text-danger @endif"></i> {{ $order_analytics->failed_order_pecentage_this_month }} %</span> @endif</li>
                                            <li><a href="javascript:void(0)" @if($order_analytics->completed > 0) class="view_analytics btn btn-primary" data-status ="completed" data-atype="this_month" @else class="btn btn-secondary" @endif>{{ $order_analytics->completed }}</a> @if($order_analytics->complete_pecentage_this_month) <span class="percentage"> <i class="mdi @if($order_analytics->complete_pecentage_this_month > 0) mdi-arrow-up text-success @else mdi-arrow-down text-danger @endif"></i> {{ $order_analytics->complete_pecentage_this_month  }} %</span> @endif</li>
                                            <li><a href="javascript:void(0)" class="total_amt">{{ $order_analytics->total_order }}</a> @if($order_analytics->total_order_pecentage_this_month) <span class="percentage"> <i class="mdi @if($order_analytics->total_order_pecentage_this_month > 0) mdi-arrow-up text-success @else mdi-arrow-down text-danger @endif"></i> {{ $order_analytics->total_order_pecentage_this_month }} %</span> @endif</li>
                                        </ul>
                                    </td>

                                    <td>
                                        @php $order_analytics =  json_decode($order_analytic_data['prev_month']); @endphp
                                        <ul class="agent-list  m-0 p-0">
                                            <li><a href="javascript:void(0)" @if($order_analytics->unassigned > 0) class="view_analytics btn btn-primary" data-status ="unassigned"  data-atype="prev_month" @else class="btn btn-secondary" @endif>{{ $order_analytics->unassigned }}</a> </li>
                                            <li><a href="javascript:void(0)" @if($order_analytics->assigned > 0) class="view_analytics btn btn-primary" data-status ="assigned" data-atype="prev_month" @else class="btn btn-secondary" @endif>{{ $order_analytics->assigned }}</a> </li>
                                            <li><a href="javascript:void(0)" @if($order_analytics->order_live > 0) class="view_analytics btn btn-primary" data-status ="live" data-atype="prev_month" @else class="btn btn-secondary" @endif>{{ $order_analytics->order_live }}</a></li>
                                            <li><a href="javascript:void(0)" @if($order_analytics->order_failed > 0) class="view_analytics btn btn-primary" data-status ="cancelled" data-atype="prev_month" @else class="btn btn-secondary" @endif>{{ $order_analytics->order_failed }}</a></li>
                                            <li><a href="javascript:void(0)" @if($order_analytics->completed > 0) class="view_analytics btn btn-primary" data-status ="completed" data-atype="prev_month" @else class="btn btn-secondary" @endif>{{ $order_analytics->completed }}</a></li>
                                            <li><a href="javascript:void(0)" class="total_amt">{{ $order_analytics->total_order }}</a></li>
                                        </ul>
                                    </td>
                                </tr>
                               @endif
                            </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
    <!-- End agent analytics section -->
        <div class="row mt-2">
            <div class="col-lg-4">
            </div>
        </div>


        <div class="row">
            <div class="col-lg-4 mb-3">
                <div class="card-box pb-0 h-100">
                    <div >
                        <div id="map"></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card-box pb-2 h-100">
                    <div class="row d-flex align-items-center">
                        <div class="col-md-6">
                            <h4 class="header-title mb-3">{{__("Analytics")}}</h4>
                        </div>
                        <div class="col-md-6 text-right">
                            <form class="" name="chatreset" id="chatreset" method="get" action="{{route('accounting')}}">
                                <div class="float-right d-none d-md-inline-block">
                                    <div class="stv-radio-buttons-wrapper">
                                        <input type="radio" class="stv-radio-button" name="type" onclick="handleChat(this);" value="1" id="button1" {{isset($type) && $type == 1 ? 'checked':''}}/>
                                        <label for="button1">{{__("Today")}}</label>
                                        <input type="radio" class="stv-radio-button" name="type" onclick="handleChat(this);" value="2" id="button2" {{isset($type) && $type == 2 ? 'checked':''}}/>
                                        <label for="button2">{{__("Weekly")}}</label>
                                        <input type="radio" class="stv-radio-button" name="type" onclick="handleChat(this);" value="3" id="button3" {{$type == 3 ? 'checked':''}}/>
                                        <label for="button3">{{__("Monthly")}}</label>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>



                    <div dir="ltr">
                        <div id="sales-analytics" class="mt-4" data-colors="#1abc9c,#4a81d4"></div>
                    </div>
                </div>
            </div>
        </div>



        <br>

        <div class="row">
            <div class="col-xl-6">
                <div class="card-box">
                    {{-- <div class="dropdown float-right">
                        <a href="#" class="dropdown-toggle arrow-none card-drop" data-toggle="dropdown" aria-expanded="false">
                            <i class="mdi mdi-dots-vertical"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">

                            <a href="javascript:void(0);" class="dropdown-item">Edit Report</a>

                            <a href="javascript:void(0);" class="dropdown-item">Export Report</a>

                            <a href="javascript:void(0);" class="dropdown-item">Action</a>
                        </div>
                    </div> --}}

                    <h4 class="header-title mb-3">{{__(getAgentNomenclature()."s")}}</h4>

                    <div class="table-responsive card-box-head">
                        <table class="table table-borderless table-hover table-nowrap table-centered m-0">

                            <thead class="thead-light">
                                <tr>
                                    <th colspan="2">{{__("Profile")}}</th>

                                    <th>{{__("Cash at hand")}}</th>
                                    <th>{{__("Phone Number")}}</th>
                                    <th>{{__("Type")}}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($agents as $agent)
                                <tr>
                                    <td style="width: 36px;">
                                        <img src="{{$imgproxyurl.Storage::disk('s3')->url($agent->profile_picture)}}" alt="contact-img" title="contact-img" class="rounded-circle avatar-sm" />
                                    </td>

                                    <td>
                                        <h5 class="m-0 font-weight-normal ">{{$agent->name}}</h5>
                                        <p class="mb-0 text-muted"><small>{{__("Member Since")}} {{ \Carbon\Carbon::parse($agent->created_at)->format('Y')}}</small></p>
                                    </td>


                                    <td>
                                        {{round($agent->cash_at_hand)}}
                                    </td>

                                    <td>
                                        {{$agent->phone_number}}
                                    </td>

                                    <td>
                                        @if ($agent->type == 'Employee')
                                        <span class="badge bg-soft-success text-success">{{__($agent->type)}}</span>
                                        @else
                                        <span class="badge bg-soft-danger text-danger">{{__($agent->type)}}</span>
                                        @endif

                                    </td>

                                </tr>
                                @endforeach

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card-box">


                    <h4 class="header-title mb-3">{{__("Customers")}}</h4>

                    <div class="table-responsive card-box-head">
                        <table class="table table-borderless table-hover table-nowrap table-centered m-0">

                            <thead class="thead-light">
                                <tr>
                                    <th >{{__("Name")}}</th>
                                    <th>{{__("Total Spent")}}</th>
                                    <th>{{__("Phone Number")}}</th>
                                    <th>{{__("Total Orders")}}</th>

                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($customers as $customer)
                                  <tr>
                                    <td>
                                        <h5 class="m-0 font-weight-normal">{{$customer->name}}</h5>
                                        <p class="mb-0 text-muted"><small>{{__("Member Since")}} {{ \Carbon\Carbon::parse($customer->created_at)->format('Y')}}</small></p>
                                    </td>

                                    <td>
                                        {{ $customer->orders->sum('order_cost') }}
                                    </td>

                                    <td>
                                        {{$customer->phone_number}}
                                    </td>

                                    <td>
                                        {{$customer->orders_count}}
                                    </td>


                                  </tr>
                                @endforeach

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>


        <div id="analytics_modal"></div>

    </div> <!-- container -->

@endsection

@section('script')
    <!-- Plugins js-->


    {{-- <script
      src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB85kLYYOmuAhBUPd7odVmL6gnQsSGWU-4&callback=initMap&libraries=visualization&v=weekly"
      defer
    ></script> --}}

    <script src="{{asset('assets/libs/flatpickr/flatpickr.min.js')}}"></script>
    <script src="{{asset('assets/libs/apexcharts/apexcharts.min.js')}}"></script>
    <script src="{{asset('assets/libs/selectize/selectize.min.js')}}"></script>

    <!-- Dashboar 1 init js-->
    {{-- <script src="{{asset('assets/js/pages/dashboard-1.init.js')}}"></script> --}}
    <script>
        $(document).ready(function() {
            initMap();

        });
        let map, heatmap;
        var heatLatLog  = {!!json_encode($heatLatLog)!!};

        if(heatLatLog.length > 0){
            var lat  = parseFloat(heatLatLog[0]['latitude']);
            var long = parseFloat(heatLatLog[0]['longitude']);
        }else{
            // var lat  = 37.775;
            // var long = -122.434;
            var lat  = 30.7046;
            var long = 76.7179;
        }
        function initMap() {
        map = new google.maps.Map(document.getElementById("map"), {
            zoom: 10,
            styles: themeType,
            center: { lat: lat, lng: long },
            mapTypeId: "roadmap",
        });
        heatmap = new google.maps.visualization.HeatmapLayer({
            data: getPoints(),
            map: map,
        });
        }

        function toggleHeatmap() {
        heatmap.setMap(heatmap.getMap() ? null : map);
        }

        function changeGradient() {
            const gradient = [
                "rgba(0, 255, 255, 0)",
                "rgba(0, 255, 255, 1)",
                "rgba(0, 191, 255, 1)",
                "rgba(0, 127, 255, 1)",
                "rgba(0, 63, 255, 1)",
                "rgba(0, 0, 255, 1)",
                "rgba(0, 0, 223, 1)",
                "rgba(0, 0, 191, 1)",
                "rgba(0, 0, 159, 1)",
                "rgba(0, 0, 127, 1)",
                "rgba(63, 0, 91, 1)",
                "rgba(127, 0, 63, 1)",
                "rgba(191, 0, 31, 1)",
                "rgba(255, 0, 0, 1)",
            ];
                heatmap.set("gradient", heatmap.get("gradient") ? null : gradient);
        }

        function changeRadius() {
            heatmap.set("radius", heatmap.get("radius") ? null : 20);
        }

        function changeOpacity() {
            heatmap.set("opacity", heatmap.get("opacity") ? null : 0.2);
        }

        // Heatmap data: 500 Points
        function getPoints() {
            var data = [];
            for (let i = 0; i < heatLatLog.length; i++) {
                checkdata = heatLatLog[i];

                data.push(new google.maps.LatLng(checkdata['latitude'],checkdata['longitude']));

            }
            return data;
        }

        //chart code goes here
        var countOrders  = {!!json_encode($countOrders)!!};
        var sumOrders    = {!!json_encode($sumOrders)!!};
        var dates        = {!!json_encode($dates)!!};

        var colors = ['#f1556c'];
        var dataColors = $("#total-revenue").data('colors');
        if (dataColors) {
            colors = dataColors.split(",");
        }
        var options = {
            series: [68],
            chart: {
                height: 220,
                type: 'radialBar',
            },
            plotOptions: {
                radialBar: {
                    hollow: {
                        size: '65%',
                    }
                },
            },
            colors: colors,
            labels: ['Revenue'],
        };

        var chart = new ApexCharts(document.querySelector("#total-revenue"), options);
        chart.render();


        //
        // Sales Analytics
        //
        var colors = ['#1abc9c', '#4a81d4'];
        var dataColors = $("#sales-analytics").data('colors');
        if (dataColors) {
            colors = dataColors.split(",");
        }

        var options = {
            series: [{
                name: 'Earning',
                type: 'column',
                data: sumOrders
            }, {
                name: 'Orders',
                type: 'line',
                data: countOrders
            }],
            chart: {
                height: 378,
                type: 'line',
            },
            stroke: {
                width: [2, 3]
            },
            plotOptions: {
                bar: {
                    columnWidth: '50%'
                }
            },
            colors: colors,
            dataLabels: {
                enabled: true,
                enabledOnSeries: [1]
            },
            labels: dates,
            xaxis: {
                type: 'datetime'
            },
            legend: {
                offsetY: 7,
            },
            grid: {
                padding: {
                bottom: 20
                }
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'light',
                    type: "horizontal",
                    shadeIntensity: 0.25,
                    gradientToColors: undefined,
                    inverseColors: true,
                    opacityFrom: 0.75,
                    opacityTo: 0.75,
                    stops: [0, 0, 0]
                },
            },
            yaxis: [{
                title: {
                    text: 'Net Earning',
                },

            }, {
                opposite: true,
                title: {
                    text: 'Number of Orders'
                }
            }]
        };

    var chart = new ApexCharts(document.querySelector("#sales-analytics"), options);
    chart.render();

    var startDate = "{{ $startDate ?? now() }}";
    var endDate = "{{ $endDate ?? now() }}";

    // Datepicker
    $('#dash-daterange').flatpickr({
        altInput: true,
        mode: "range",
        altFormat: "F j, y",
        defaultDate: [startDate,endDate]
    });

    function handleClick(myRadio) {
        $('#resetaccunting').submit();
    }



    function handleChat(myRadio) {
        $('#chatreset').submit();
    }


    /***
     *  get order analytics data by agent
    */

    $( document ).delegate( "#agent_name", "change", function() {
        $.ajaxSetup({
            headers: {
               'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
         });
        var agent_id = $(this).val();
        $.ajax({
            url: "{{ route('agent.complete.order') }}",
            type: "POST",
            data: {
                agent_id: agent_id,
            },
            success: function(response) {

               $("#agent_analytics_records").empty();
               var obj  = jQuery.parseJSON(response);
               var obj1 = jQuery.parseJSON(obj.this_day);
               var obj2 = jQuery.parseJSON(obj.prev_day);
               var obj3 = jQuery.parseJSON(obj.this_week);
               var obj4 = jQuery.parseJSON(obj.prev_week);
               var obj5 = jQuery.parseJSON(obj.this_month);
               var obj6 = jQuery.parseJSON(obj.prev_month);

               var this_day,prev_day,prev_week,this_week,this_month,prev_month;
               var class11,class12,class13,class14,class15;
               var class21,class22,class23,class24,class25;
               var class31,class32,class33,class34,class35;;
               var class41,class42,class43,class44,class45;;
               var class51,class52,class53,class54,class55;;
               var class61,class62,class63,class64,class65;;

               this_day     = obj1.this_day;
               prev_day     = obj2.prev_day;
               this_week    = obj3.this_week;
               prev_week    = obj4.prev_week;
               this_month   = obj5.this_month;
               prev_month   = obj6.prev_month;

               class11  = class12 =  class13 =  class14 = class15 ='class="btn btn-secondary"';
               class21  = class22 =  class23 =  class24 = class25 =  'class="btn btn-secondary"';
               class31  = class32 =  class33 =  class34 = class35 =  'class="btn btn-secondary"';
               class41  = class42 =  class43 =  class44 = class45 =  'class="btn btn-secondary"';
               class51  = class52 =  class53 =  class54 = class55 =  'class="btn btn-secondary"';
               class61  = class62 =  class63 =  class64 = class65 =  'class="btn btn-secondary"';

               var complete_pecentage_prev_month        = '';
               var assigned_pecentage_prev_month        = '';
               var unassigned_pecentage_prev_month      = '';
               var live_order_pecentage_prev_month      = '';
               var failed_order_pecentage_prev_month    = '';
               var total_order_pecentage_prev_month     = '';

               var complete_pecentage_this_month        = '';
               var assigned_pecentage_this_month        = '';
               var unassigned_pecentage_this_month      = '';
               var live_order_pecentage_this_month      = '';
               var failed_order_pecentage_this_month    = '';
               var total_order_pecentage_this_month     = '';


               var complete_pecentage_prev_week        = '';
               var assigned_pecentage_prev_week        = '';
               var unassigned_pecentage_prev_week      = '';
               var live_order_pecentage_prev_week      = '';
               var failed_order_pecentage_prev_week    = '';
               var total_order_pecentage_prev_week     = '';

               var complete_pecentage_this_week        = '';
               var assigned_pecentage_this_week        = '';
               var unassigned_pecentage_this_week      = '';
               var live_order_pecentage_this_week      = '';
               var failed_order_pecentage_this_week    = '';
               var total_order_pecentage_this_week     = '';


               var complete_pecentage_prev_day        = '';
               var assigned_pecentage_prev_day        = '';
               var unassigned_pecentage_prev_day      = '';
               var live_order_pecentage_prev_day      = '';
               var failed_order_pecentage_prev_day    = '';
               var total_order_pecentage_prev_day     = '';

               var complete_pecentage_this_day        = '';
               var assigned_pecentage_this_day        = '';
               var unassigned_pecentage_this_day      = '';
               var live_order_pecentage_this_day      = '';
               var failed_order_pecentage_this_day    = '';
               var total_order_pecentage_this_day     = '';

              /** this day and prev day percentage **/

               if(obj1.complete_pecentage_this_day){
                    complete_pecentage_this_day = (obj1.complete_pecentage_this_day > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj1.complete_pecentage_this_day+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i>'+obj1.complete_pecentage_this_day+' %</span>');
               }
               if(obj1.assigned_pecentage_this_day){
                    assigned_pecentage_this_day = (obj1.assigned_pecentage_this_day > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj1.assigned_pecentage_this_day+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i>'+obj1.assigned_pecentage_this_day+' %</span>');
               }
               if(obj1.unassigned_pecentage_this_day){
                    unassigned_pecentage_this_day = (obj1.unassigned_pecentage_this_day > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj1.unassigned_pecentage_this_day+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i>'+obj1.unassigned_pecentage_this_day+' %</span>');
               }
               if(obj1.total_order_pecentage_this_day){
                    total_order_pecentage_this_day = (obj1.total_order_pecentage_this_day > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj1.total_order_pecentage_this_day+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i> '+obj1.total_order_pecentage_this_day+' % </span>');
               }
               if(obj1.live_order_pecentage_this_day){
                    live_order_pecentage_this_day   = (obj1.live_order_pecentage_this_day > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj1.live_order_pecentage_this_day+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i> '+obj1.live_order_pecentage_this_day+' % </span>');
               }
               if(obj1.failed_order_pecentage_this_day){
                    failed_order_pecentage_this_day = (obj1.failed_order_pecentage_this_day > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj1.failed_order_pecentage_this_day+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i> '+obj1.failed_order_pecentage_this_day+' % </span>');
               }

               if(obj2.complete_pecentage_prev_day){
                    complete_pecentage_prev_day = (obj2.complete_pecentage_prev_day > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj2.complete_pecentage_prev_day+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i>'+obj2.complete_pecentage_prev_day+' %</span>');
               }
               if(obj2.assigned_pecentage_prev_day){
                    assigned_pecentage_prev_day = (obj2.assigned_pecentage_prev_day > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj2.assigned_pecentage_prev_day+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i>'+obj2.assigned_pecentage_prev_day+' %</span>');
               }
               if(obj2.unassigned_pecentage_prev_day){
                    unassigned_pecentage_prev_day = (obj2.unassigned_pecentage_prev_day > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj2.unassigned_pecentage_prev_day+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i>'+obj2.unassigned_pecentage_prev_day+' %</span>');
               }
               if(obj2.live_order_pecentage_prev_day){
                    live_order_pecentage_prev_day = (obj2.live_order_pecentage_prev_day > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj2.live_order_pecentage_prev_day+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i> '+obj2.live_order_pecentage_prev_day+' % </span>');
               }
               if(obj2.failed_order_pecentage_prev_day){
                    failed_order_pecentage_prev_day = (obj2.failed_order_pecentage_prev_day > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj2.failed_order_pecentage_prev_day+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i> '+obj2.failed_order_pecentage_prev_day+' % </span>');
               }
               if(obj2.total_order_pecentage_prev_day){
                    total_order_pecentage_prev_day = (obj2.total_order_pecentage_prev_day > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj2.total_order_pecentage_prev_day+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i> '+obj2.total_order_pecentage_prev_day+' % </span>');
               }

                /** this day and prev day percentage **/

                /** this week and prev week percentage **/

               if(obj3.complete_pecentage_this_week){
                    complete_pecentage_this_week = (obj3.complete_pecentage_this_week > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj3.complete_pecentage_this_week+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i>'+obj3.complete_pecentage_this_week+' %</span>');
               }
               if(obj3.assigned_pecentage_this_week){
                    assigned_pecentage_this_week = (obj3.assigned_pecentage_this_week > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj3.assigned_pecentage_this_week+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i>'+obj3.assigned_pecentage_this_week+' %</span>');
               }
               if(obj3.unassigned_pecentage_this_week){
                    unassigned_pecentage_this_week = (obj3.unassigned_pecentage_this_week > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj3.unassigned_pecentage_this_week+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i>'+obj3.unassigned_pecentage_this_week+' %</span>');
               }
               if(obj3.live_order_pecentage_this_week){
                    live_order_pecentage_this_week = (obj3.live_order_pecentage_this_week > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj3.live_order_pecentage_this_week+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i> '+obj3.live_order_pecentage_this_week+' % </span>');
               }
               if(obj3.failed_order_pecentage_this_week){
                    failed_order_pecentage_this_week = (obj3.failed_order_pecentage_this_week > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj3.failed_order_pecentage_this_week+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i> '+obj3.failed_order_pecentage_this_week+' % </span>');
               }
               if(obj3.total_order_pecentage_this_week){
                    total_order_pecentage_this_week = (obj3.total_order_pecentage_this_week > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj3.total_order_pecentage_this_week+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i> '+obj3.total_order_pecentage_this_week+' % </span>');
               }

               if(obj4.complete_pecentage_prev_week){
                    complete_pecentage_prev_week = (obj4.complete_pecentage_prev_week > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj4.complete_pecentage_prev_week+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i>'+obj4.complete_pecentage_prev_week+' %</span>');
               }
               if(obj4.assigned_pecentage_prev_week){
                    assigned_pecentage_prev_week = (obj4.assigned_pecentage_prev_week > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj4.assigned_pecentage_prev_week+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i>'+obj4.assigned_pecentage_prev_week+' %</span>');
               }
               if(obj4.unassigned_pecentage_prev_week){
                    unassigned_pecentage_prev_week = (obj4.unassigned_pecentage_prev_week > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj4.unassigned_pecentage_prev_week+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i>'+obj4.unassigned_pecentage_prev_week+' %</span>');
               }
               if(obj4.live_order_pecentage_prev_week){
                    live_order_pecentage_prev_week = (obj4.live_order_pecentage_prev_week > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj4.live_order_pecentage_prev_week+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i> '+obj4.total_order_pecentage_prev_week+' % </span>');
               }
               if(obj4.failed_order_pecentage_prev_week){
                    failed_order_pecentage_prev_week = (obj4.failed_order_pecentage_prev_week > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj4.failed_order_pecentage_prev_week+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i> '+obj4.failed_order_pecentage_prev_week+' % </span>');
               }
               if(obj4.total_order_pecentage_prev_week){
                    total_order_pecentage_prev_week = (obj4.total_order_pecentage_prev_week > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj4.total_order_pecentage_prev_week+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i> '+obj4.total_order_pecentage_prev_week+' % </span>');
               }

               /** this week and prev week percentage **/

               /** this month and prev month percentage **/

               if(obj5.complete_pecentage_this_month){
                    complete_pecentage_this_month = (obj5.complete_pecentage_this_month > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj5.complete_pecentage_this_month+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i>'+obj5.complete_pecentage_this_month+' %</span>');
               }
               if(obj5.assigned_pecentage_this_month){
                    assigned_pecentage_this_month = (obj5.assigned_pecentage_this_month > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj5.assigned_pecentage_this_month+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i>'+obj5.assigned_pecentage_this_month+' %</span>');
               }
               if(obj5.unassigned_pecentage_this_month){
                    unassigned_pecentage_this_month = (obj5.unassigned_pecentage_this_month > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj5.unassigned_pecentage_this_month+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i>'+obj5.unassigned_pecentage_this_month+' %</span>');
               }
               if(obj5.live_order_pecentage_this_month){
                    live_order_pecentage_this_month = (obj5.live_order_pecentage_this_month > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj5.live_order_pecentage_this_month+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i> '+obj5.live_order_pecentage_this_month+' % </span>');
               }
               if(obj5.failed_order_pecentage_this_month){
                    failed_order_pecentage_this_month = (obj5.failed_order_pecentage_this_month > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj5.failed_order_pecentage_this_month+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i> '+obj5.failed_order_pecentage_this_month+' % </span>');
               }
               if(obj5.total_order_pecentage_this_month){
                    total_order_pecentage_this_month = (obj5.total_order_pecentage_this_month > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj5.total_order_pecentage_this_month+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i> '+obj5.total_order_pecentage_this_month+' % </span>');
               }


               if(obj6.complete_pecentage_prev_month){
                    complete_pecentage_prev_month = (obj6.complete_pecentage_prev_month > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj6.complete_pecentage_prev_month+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i>'+obj6.complete_pecentage_prev_month+' %</span>');
               }
               if(obj6.assigned_pecentage_prev_month){
                    assigned_pecentage_prev_month = (obj6.assigned_pecentage_prev_month > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj6.assigned_pecentage_prev_month+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i>'+obj6.assigned_pecentage_prev_month+' %</span>');
               }
               if(obj6.unassigned_pecentage_prev_month){
                    unassigned_pecentage_prev_month = (obj6.unassigned_pecentage_prev_month > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj6.unassigned_pecentage_prev_month+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i>'+obj6.unassigned_pecentage_prev_month+' %</span>');
               }
               if(obj6.live_order_pecentage_prev_month){
                    live_order_pecentage_prev_month = (obj6.live_order_pecentage_prev_month > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj6.live_order_pecentage_prev_month+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i> '+obj6.live_order_pecentage_prev_month+' % </span>');
               }
               if(obj6.failed_order_pecentage_prev_month){
                    failed_order_pecentage_prev_month = (obj6.failed_order_pecentage_prev_month > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj6.failed_order_pecentage_prev_month+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i> '+obj6.failed_order_pecentage_prev_month+' % </span>');
               }
               if(obj6.total_order_pecentage_prev_month){
                    total_order_pecentage_prev_month = (obj6.total_order_pecentage_prev_month > 0 ? ' <span class="percentage"> <i class="mdi mdi-arrow-up text-success"></i> '+obj6.total_order_pecentage_prev_month+' %</span>' : '<span class="percentage"> <i class="mdi mdi-arrow-down text-danger"></i> '+obj6.total_order_pecentage_prev_month+' % </span>');
               }

               /** this month and prev month percentage **/

               if(this_day > 0 && obj1.completed > 0){
                 class11 = 'class="view_analytics btn btn-primary" data-atype="this_day"';
               }
               if(this_day > 0 && obj1.assigned > 0){
                 class12 = 'class="view_analytics btn btn-primary" data-atype="this_day"';
               }
               if(this_day > 0 && obj1.unassigned > 0){
                 class13 = 'class="view_analytics btn btn-primary" data-atype="this_day"';
               }
               if(this_day > 0 && obj1.order_live > 0){
                 class14 = 'class="view_analytics btn btn-primary" data-atype="this_day"';
               }
               if(this_day > 0 && obj1.order_failed > 0){
                 class15 = 'class="view_analytics btn btn-primary" data-atype="this_day"';
               }

               if(prev_day > 0 && obj2.completed > 0){
                 class21 = 'class="view_analytics btn btn-primary" data-atype="prev_day"';
               }
               if(prev_day > 0 && obj2.assigned > 0){
                 class22 = 'class="view_analytics btn btn-primary" data-atype="prev_day"';
               }
               if(prev_day > 0 && obj2.unassigned > 0){
                 class23 = 'class="view_analytics btn btn-primary" data-atype="prev_day"';
               }
               if(prev_day > 0 && obj2.order_live > 0){
                 class24 = 'class="view_analytics btn btn-primary" data-atype="prev_day"';
               }
               if(prev_day > 0 && obj2.order_failed > 0){
                 class25 = 'class="view_analytics btn btn-primary" data-atype="prev_day"';
               }


               if(this_week > 0 && obj3.completed > 0){
                 class31 = 'class="view_analytics btn btn-primary" data-atype="this_week"';
               }
               if(this_week > 0 && obj3.assigned > 0){
                 class32 = 'class="view_analytics btn btn-primary" data-atype="this_week"';
               }
               if(this_week > 0 && obj3.unassigned > 0){
                 class33 = 'class="view_analytics btn btn-primary" data-atype="this_week"';
               }
               if(this_week > 0 && obj3.order_live > 0){
                 class34 = 'class="view_analytics btn btn-primary" data-atype="this_week"';
               }
               if(this_week > 0 && obj3.order_failed > 0){
                 class35 = 'class="view_analytics btn btn-primary" data-atype="this_week"';
               }

               if(prev_week > 0 && obj4.completed > 0){
                 class41 = 'class="view_analytics btn btn-primary" data-atype="prev_week"';
               }
               if(prev_week > 0 && obj4.assigned > 0){
                 class42 = 'class="view_analytics btn btn-primary" data-atype="prev_week"';
               }
               if(prev_week > 0 && obj4.unassigned > 0){
                 class43 = 'class="view_analytics btn btn-primary" data-atype="prev_week"';
               }
               if(prev_week > 0 && obj4.order_live > 0){
                 class44 = 'class="view_analytics btn btn-primary" data-atype="prev_week"';
               }
               if(prev_week > 0 && obj4.order_failed > 0){
                 class45 = 'class="view_analytics btn btn-primary" data-atype="prev_week"';
               }

               if(this_month > 0 && obj5.completed > 0){
                 class51 = 'class="view_analytics btn btn-primary" data-atype="this_month"';
               }
               if(this_month > 0 && obj5.assigned > 0){
                 class52 = 'class="view_analytics btn btn-primary" data-atype="this_month"';
               }
               if(this_month > 0 && obj5.unassigned > 0){
                 class53 = 'class="view_analytics btn btn-primary" data-atype="this_month"';
               }
               if(this_month > 0 && obj5.order_live > 0){
                 class54 = 'class="view_analytics btn btn-primary" data-atype="this_month"';
               }
               if(this_month > 0 && obj5.order_failed > 0){
                 class55 = 'class="view_analytics btn btn-primary" data-atype="this_month"';
               }

               if(prev_month > 0 && obj6.completed > 0){
                 class61 = 'class="view_analytics btn btn-primary" data-atype="prev_month"';
               }
               if(prev_month > 0 && obj6.assigned > 0){
                 class62 = 'class="view_analytics btn btn-primary" data-atype="prev_month"';
               }
               if(prev_month > 0 && obj6.unassigned > 0){
                 class63 = 'class="view_analytics btn btn-primary" data-atype="prev_month"';
               }
               if(prev_month > 0 && obj6.order_live > 0){
                 class64 = 'class="view_analytics btn btn-primary" data-atype="prev_month"';
               }
               if(prev_month > 0 && obj6.order_failed > 0){
                 class65 = 'class="view_analytics btn btn-primary" data-atype="prev_month"';
               }
              $("#agent_analytics_records").html('<tr> <td> <ul class="agent-list agent-head m-0 p-0"> <li>Unassigned</li> <li>Assigned</li> <li>Live Order</li> <li>Return/Failed</li> <li>Completed</li> <li>Total</li> </ul> </td> <td> <ul class="agent-list m-0 p-0"><li><a href="javascript:void(0)" '+class13+' data-status="unassigned">'+obj1.unassigned+'</a>'+unassigned_pecentage_this_day+'</li><li><a href="javascript:void(0)" '+class12+' data-status="assigned">'+obj1.assigned+'</a>'+assigned_pecentage_this_day+'</li><li><a href="javascript:void(0)" '+class14+' data-status="live">'+obj1.order_live+'</a>'+live_order_pecentage_this_day+'</li><li><a href="javascript:void(0)" '+class15+' data-status="cancelled">'+obj1.order_failed+'</a>'+failed_order_pecentage_this_day+'</li><li><a href="javascript:void(0)" '+class11+' data-status="completed">'+obj1.completed+'</a>'+complete_pecentage_this_day+'</li> <li><a href="javascript:void(0)" class="total_amt">'+obj1.total_order+'</a> '+total_order_pecentage_this_day+' </li> </ul> </td> <td> <ul class="agent-list m-0 p-0"><li><a href="javascript:void(0)" '+class23+' data-status="unassigned">'+obj2.unassigned+'</a></li><li><a href="javascript:void(0)" '+class22+' data-status="assigned">'+obj2.assigned+'</a></li><li><a href="javascript:void(0)" '+class24+' data-status="live">'+obj2.order_live+'</a></li><li><a href="javascript:void(0)" '+class25+' data-status="cancelled">'+obj2.order_failed+'</a></li><li><a href="javascript:void(0)" '+class21+' data-status="completed">'+obj2.completed+'</a></li><li><a href="javascript:void(0)" class="total_amt">'+obj2.total_order+'</a></li> </ul> </td> <td> <ul class="agent-list m-0 p-0"><li><a href="javascript:void(0)" '+class33+' data-status="unassigned">'+obj3.unassigned+'</a>'+unassigned_pecentage_this_week+'</li><li><a href="javascript:void(0)" '+class32+' data-status="assigned">'+obj3.assigned+'</a>'+assigned_pecentage_this_week+'</li><li><a href="javascript:void(0)" '+class34+' data-status="live">'+obj3.order_live+'</a>'+live_order_pecentage_this_week+'</li><li><a href="javascript:void(0)" '+class35+' data-status="cancelled">'+obj3.order_failed+'</a>'+failed_order_pecentage_this_week+'</li><li><a href="javascript:void(0)" '+class31+' data-status="completed">'+obj3.completed+'</a>'+complete_pecentage_this_week+'</li> <li><a href="javascript:void(0)" class="total_amt">'+obj3.total_order+'</a>'+total_order_pecentage_this_week+'</li> </ul> </td> <td> <ul class="agent-list m-0 p-0"><li><a href="javascript:void(0)" '+class43+' data-status="unassigned">'+obj4.unassigned+'</a></li><li><a href="javascript:void(0)" '+class42+' data-status="assigned">'+obj4.assigned+'</a></li><li><a href="javascript:void(0)" '+class44+' data-status="live">'+obj4.order_live+'</a></li><li><a href="javascript:void(0)" '+class45+' data-status="cancelled">'+obj4.order_failed+'</a></li><li><a href="javascript:void(0)" '+class41+' data-status="completed">'+obj4.completed+'</a></li><li><a href="javascript:void(0)" class="total_amt">'+obj4.total_order+'</a></li> </ul> </td> <td> <ul class="agent-list m-0 p-0"><li><a href="javascript:void(0)" '+class53+' data-status="unassigned">'+obj5.unassigned+'</a>'+unassigned_pecentage_this_month+'</li><li><a href="javascript:void(0)" '+class52+' data-status="assigned">'+obj5.assigned+'</a>'+assigned_pecentage_this_month+'</li><li><a href="javascript:void(0)" '+class54+' data-status="live">'+obj5.order_live+'</a>'+live_order_pecentage_this_month+'</li><li><a href="javascript:void(0)" '+class55+' data-status="cancelled">'+obj5.order_failed+'</a>'+failed_order_pecentage_this_month+'</li><li><a href="javascript:void(0)" '+class51+' data-status="completed">'+obj5.completed+'</a>'+complete_pecentage_this_month+'</li><li><a href="javascript:void(0)" class="total_amt">'+obj5.total_order+'</a>'+total_order_pecentage_this_month+'</li> </ul> </td> <td> <ul class="agent-list m-0 p-0"><li><a href="javascript:void(0)" '+class63+' data-status="unassigned">'+obj6.unassigned+'</a></li><li><a href="javascript:void(0)" '+class62+' data-status="assigned">'+obj6.assigned+'</a></li><li><a href="javascript:void(0)" '+class64+' data-status="live">'+obj6.order_live+'</a></li><li><a href="javascript:void(0)" '+class65+' data-status="cancelled">'+obj6.order_failed+'</a></li><li><a href="javascript:void(0)" '+class61+' data-status="completed">'+obj6.completed+'</a></li><li><a href="javascript:void(0)" class="total_amt">'+obj6.total_order+'</a></li> </ul> </td></tr>');
            },
        });
    });


    /**
     * Show all agents which has completed order
    */

    $( document ).delegate( ".view_analytics", "click", function() {
        $.ajaxSetup({
            headers: {
               'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
         });

        var data_type   = $(this).attr('data-atype');
        var data_status = $(this).attr('data-status');
        var agent_id    = $("#agent_name").val();
        $("#analytics_modal").empty();
        $("#analytics_modal").html('<div class="modal fade" id="agent_analytics_modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true"> <div class="modal-dialog modal-lg" role="document"> <div class="modal-content"> <div class="modal-header"> <h5 class="modal-title" id="exampleModalLabel">Agent Analytics</h5> <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button> </div> <div class="modal-body"><div class="loader text-center"><div class="spinner-border" role="status"> <span class="sr-only">Loading...</span></div></div></div> <!--<div class="modal-footer"> <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button> <button type="button" class="btn btn-primary">Save changes</button> </div> </div> </div>--></div>');
        $("#agent_analytics_modal").modal('show');
        $.ajax({
            url: "{{ route('agent.view.analytics') }}",
            type: "POST",
            data: {
                agent_id: agent_id,
                data_type: data_type,
                data_status: data_status,
            },
            success: function(response) {
                $("#analytics_modal").find('.modal-body').empty();
                $("#analytics_modal").find('.modal-body').html(response);
            },
        });


    });
    </script>

@endsection
