@extends('layouts.vertical', ['title' => __('Route')])

@section('css')
@endsection
@php
use Carbon\Carbon;
$imgproxyurl = 'https://imgproxy.royodispatch.com/insecure/fill/90/90/sm/0/plain/';
@endphp

<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css" />
<style>
    .agents-datatable tbody td,.dataTables_scrollHead thead th {
        display: table-cell !important;
    }
    #wrapper {
        overflow: auto !important;
    }
    .dt-buttons .btn.btn-secondary,.dt-buttons .btn.btn-secondary:focus,.dt-buttons .btn.btn-secondary:active {
        border-radius: 5px;
        background: #6658ddd6 !important;
    }
    .footer{
        z-index: 3;
    }
    .dataTables_scrollHead thead th {
        cursor: pointer;
    }
    .agents-datatable tbody td, .dataTables_scrollHead thead th {
        vertical-align: middle;
    }
    .btn-label,.btn-label:focus,.btn-label:active {
        background-color: rgb(102 88 221) !important;
    }
    .table thead th {
    font-size: 14px;
    font-weight: 600;
    vertical-align: middle !important;
}
.light .btn-info{border-radius: 4px;font-size:13px;padding: 6px 20px;}
.sort-icon .fa-sort{top:30px !important;bottom:0px !important;transform: translate(-20%, -20%);}
    #agents-datatable_processing {
        position: absolute !important;
        background: transparent !important;
        top: 60%;
        transform: translateY(-50%) !important;
        left: 0;
        right: 0;
        z-index: 1;
    }
    .dt-buttons.btn-group.flex-wrap {
        float: right;
        margin: 5px 0 10px 15px;
    }
  
    div#agents-datatable_filter {
        padding-top: 5px;
    }
    .dataTables_filter label {
        width: 25%;
    }
    .dataTables_filter label .form-control {
        height: 37px;
        font-size: 16px;
    }
    .datetime_div{
    display:flex;
    align-items: center;
    min-width:162px;
    }

    .datetime_div i{
    font-size:23px;
    padding-right:5px;
    color:#3283f6;
    }

    select#agent_name_id {
        width: 135px;
    }
   
</style>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js"></script>
@section('content')
    <!-- Start Content-->
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <h4 class="page-title">{{__("Routes")}}</h4>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card widget-inline main-card-header">
                    <div class="card-body p-2">
                        <div class="row">
                            <input type="hidden" name="customer_id" id="customer_id" value="{{ app('request')->input('customer_id')??'' }}">
                            <div class="col-sm-6 col-md-3 mb-3 mb-md-0">
                                <div class="text-center">
                                    <h3>
                                        <i class="mdi mdi-storefront text-primary mdi-24px"></i>
                                        <span data-plugin="counterup" id="total_earnings_by_vendors">{{ $panding_count }}</span>
                                    </h3>
                                    <p class="text-muted font-15 mb-0">{{__("Pending Assignment")}}</p>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-3 mb-3 mb-md-0">
                                <div class="text-center">
                                    <h3>
                                        <i class="mdi mdi-store-24-hour text-primary mdi-24px"></i>
                                        <span data-plugin="counterup" id="total_order_count">{{$active_count}}</span>
                                    </h3>
                                    <p class="text-muted font-15 mb-0">{{__("Active") .' ' .__('Orders')}}</p>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-3 mb-3 mb-md-0">
                                <div class="text-center">
                                    <h3>
                                        <i class="fas fa-money-check-alt text-primary"></i>
                                        <span data-plugin="counterup" id="total_cash_to_collected">{{$employeesCount}}</span>
                                    </h3>
                                    <p class="text-muted font-15 mb-0">{{__("Active") . ' ' .__('Customer')}}</p>
                                </div>
                            </div>
                            <div class="col-sm-6 col-md-3 mb-3 mb-md-0">
                                <div class="text-center">
                                    <h3>
                                        <i class="fas fa-money-check-alt text-primary"></i>
                                        <span data-plugin="counterup" id="total_delivery_fees">{{$agentsCount}}</span>
                                    </h3>
                                    <p class="text-muted font-15 mb-0">{{__("Active")}} {{__(getAgentNomenclature()) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- end page title -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body custom-body-table">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="text-sm-left">
                                    @if (\Session::has('success'))
                                        <div class="alert alert-success">
                                            <span>{!! \Session::get('success') !!}</span>
                                            @php
                                                \Session::forget('success')
                                            @endphp
                                        </div>
                                    @endif
                                    @if (\Session::has('error'))
                                        <div class="alert alert-danger">
                                            <span>{!! \Session::get('error') !!}</span>
                                            @php
                                                \Session::forget('error')
                                            @endphp
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @csrf
                            <div class="col-md-6">
                                <form class="mb-0" name="getTask" id="getTask" method="get" action="{{ route('tasks.index') }}">
                                    <div class="login-form">
                                        <ul class="list-inline mb-0">
                                            <li class="d-inline-block mr-1">
                                                <input type="radio" id="teacher" name="status" onclick="handleClick(this);"
                                                    value="unassigned" {{ $status == 'unassigned' ? 'checked' : '' }}>
                                                <label for="teacher">{{__("Pending Assignment")}}<span
                                                        class="showspan">{{ ' (' . $panding_count . ')' }}</span></label>
                                            </li>
                                            <li class="d-inline-block mr-1">
                                                <input type="radio" id="student" onclick="handleClick(this);" name="status"
                                                    value="assigned" {{ $status == 'assigned' ? 'checked' : '' }}>
                                                <label for="student">{{__("Active")}}<span
                                                        class="showspan">{{ ' (' . $active_count . ')' }}</span></label>
                                            </li>
                                            <li class="d-inline-block mr-1">
                                                <input type="radio" id="parent" name="status" onclick="handleClick(this);"
                                                    value="completed" {{ $status == 'completed' ? 'checked' : '' }}>
                                                <label for="parent">{{__("History")}}<span
                                                        class="showspan">{{ ' (' . $history_count . ')' }}</span></label>
                                            </li>
                                            <li class="d-inline-block mr-1">
                                                <input type="radio" id="failed" name="status" onclick="handleClick(this);"
                                                    value="failed" {{ $status == 'failed' ? 'checked' : '' }}>
                                                <label for="failed">{{__("Failed")}}<span
                                                        class="showspan">{{ ' (' . $failed_count . ')' }}</span></label>
                                            </li>
                                            <input type="hidden" name="customer_id" id="customer_id" value="{{ app('request')->input('customer_id')??'' }}">
                                            @php
                                                $warehouse_mode = checkWarehouseMode();
                                            @endphp
                                            @if($warehouse_mode['show_warehouse_module'] == 1)
                                                <li class="d-inline-block mr-1">
                                                    <select name="search_warehouse" class="form-control"  onchange="handleClick(this);" id="search_warehouse">
                                                        <option value="">All</option>
                                                        @foreach ($warehouses as $warehouse)
                                                            <option value="{{$warehouse->id}}" @if (app('request')->input('search_warehouse') == $warehouse->id) {{'selected="selected"'}} @endif>{{$warehouse->name}}</option>
                                                        @endforeach
                                                    </select>
                                                </li>
                                                @if(Auth::user()->is_superadmin == 1 && Auth::user()->manager_type == 0)
                                                <li class="d-inline-block mr-1">
                                                    <select name="warehouse_manager" class="form-control" onchange="handleClick(this);"  id="warehouse_manager">
                                                        <option value="">Select Warehouse Manager</option>
                                                        @foreach ($warehouse_manager as $manager)
                                                            <option value="{{$manager->id}}" @if (app('request')->input('warehouse_manager') == $manager->id) {{'selected="selected"'}} @endif>{{$manager->name}}</option>
                                                        @endforeach
                                                    </select>
                                                </li>
                                                @endif
                                            @endif
                                            <li class="d-inline-block mr-1">
                                                <a href="{{route('tasks.index')}}" type="button" class="btn btn-info btn-sm">Clear</a>

                                            <li class="d-inline-block mr-2">
                                                <button type="button" class="btn btn-info bulkupload" data-toggle="modal" data-target="#upload-bulk-tasks" data-backdrop="static" data-keyboard="false"><i class="mdi mdi-cloud-upload mr-1"></i> {{__("Upload")}}</button>
                                            </li>
                                        </ul>
                                    </div>
                                </form>
                            </div>

                            @if(!empty($preference->create_batch_hours) && $preference->create_batch_hours > 0)
                            <a href="{{route('batch.list')}}"><button type="button" class="btn btn-info" >All Batches</button></a>
                            @endif
                            
                            <div class="col-md-6 assign-toggle assign-show">
                                <button type="button" class="btn btn-info assign_agent" data-toggle="modal" data-target="#add-assgin-agent-model" data-backdrop="static" data-keyboard="false">{{__("Assign")}}</button> 
                                <button type="button" class="btn btn-info assign_date" data-toggle="modal" data-target="#add-assgin-date-model" data-backdrop="static" data-keyboard="false">{{__("Change Date")}}/{{__("Time")}}</button> 
                            </div>
                        </div>
                        <input type="hidden" id="routes-listing-status" value="unassigned">
                        <div class="table-responsive mt-2">
                            <table class="table table-striped dt-responsive nowrap w-100 agents-datatable" id="agents-datatable">
                                <thead>
                                    <tr>
                                        @if (!isset($status) || $status == 'unassigned')
                                        <th><input type="checkbox" class="all-driver_check" name="all_driver_id" id="all-driver_check"></th>
                                        @endif
                                        <th class="sort-icon">{{__("Order Number")}} <i class="fa fa-sort ml-1" aria-hidden="true"></i></th>
                                        <th class="sort-icon">{{__("Customer ID")}} <i class="fa fa-sort ml-1" aria-hidden="true"></i></th>
                                        <th class="sort-icon">{{__("Customer")}} <i class="fa fa-sort ml-1" aria-hidden="true"></i></th>
                                        <th class="sort-icon">{{__("Phone No.")}} <i class="fa fa-sort ml-1" aria-hidden="true"></i></th>
                                        <th class="sort-icon">{{__("Type")}} <i class="fa fa-sort ml-1" aria-hidden="true"></i></th>
                                        <th class="sort-icon">{{__(getAgentNomenclature()) }} <i class="fa fa-sort ml-1" aria-hidden="true"></i></th>
                                        <th class="sort-icon">{{__("Due Time")}} <i class="fa fa-sort ml-1" aria-hidden="true"></i></th>
                                        <th class="routes-head">{{__("Routes")}}</th>
                                        <!-- <th>{{__("Tracking URL")}}</th>
                                         <th>{{__("Route Proofs")}}</th> -->
                                     
                                        <th>{{__("Created At")}}</th>
                                        {{-- <th>{{__("Updated At")}}</th> --}}
                                        <th style="width: 85px;">{{__("Action")}}</th>
                                    </tr>
                                </thead>
                                <tbody style="height: 8%;overflow: auto !important;">

                                </tbody>
                            </table>
                        </div>
                    </div> <!-- end card-body-->
                </div> <!-- end card-->
            </div> <!-- end col -->
        </div>
    </div>

    @include('modals.task-list')
    @include('modals.task-accounting')
    @include('modals.task-proofs')
    @include('modals.assgin_task_agent')
    @include('modals.assgin_task_date')
    @include('modals.upload_bulk_tasks')
@endsection

@section('script')
    <script src="{{asset('assets/libs/select2/select2.min.js')}}"></script>
    <script src="{{asset('assets/libs/bootstrap-select/bootstrap-select.min.js')}}"></script>
    <script src="{{ asset('assets/libs/datatables/datatables.min.js') }}"></script>
    <script src="{{ asset('assets/libs/dropify/dropify.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/form-fileuploads.init.js') }}"></script>
    @include('tasks.taskpagescript')


<style>
    .col-md-4.assign-toggle button {
    color: #fff;
    width: 160px;
}
.agents-datatable tbody td, .dataTables_scrollHead thead th {
        padding: 6px 6px 6px 6px !important;
        vertical-align: middle;
    }
    select#agent_name_id {
    width: 135px;
    color: #dbe9f9;
}
    select option:hover {
      background:#d4a34a !important;
      color:#fff;
    }
    select option:checked,
    select option:hover {
        background:#d4a34a !important;
}
select:focus > option:checked {
    background:#d4a34a !important;
}
.address_box span {
    width: 100px;
    text-align: center;
}
body.dark .table thead th {
    font-size: 14px;
    vertical-align: middle;
    /* width: auto !important; */
}
select#search_warehouse {
    width: 80px;
}
</style>
@endsection
