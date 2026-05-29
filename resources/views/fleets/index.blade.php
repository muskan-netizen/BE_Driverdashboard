@extends('layouts.vertical', ['title' => 'Fleets' ])
@section('content')
<div class="container-fluid">
    @csrf
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">{{ __('Fleets') }} </h4>
            </div>
        </div>
    </div>

    <!-- end page title -->
    <div class="row">
            @if (\Session::has('success'))
            <div class="col m-2 alert alert-success alert-dismissible fade show" role="alert">
                    <span>{!! \Session::get('success') !!}</span>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @endif

        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="alFilterLocation">
                        <ul class="p-0 d-flex justify-content-end">
                                <li class="d-flex">
                                    <button type="button" class="btn btn-blue waves-effect waves-light openModal mr-1" data-toggle="modal" data-target="" data-backdrop="static" data-keyboard="false"><i class="mdi mdi-plus-circle mr-1"></i> {{__("Add Fleets")}}</button>
                                </li>
                            </ul>
                    </div>
                    <ul class="nav nav-tabs nav-material alNavTopMinus" id="top-tab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="all-fleets" data-toggle="tab" href="#all_fleets" role="tab" aria-selected="false" data-rel="agent-listing" data-status="0">
                                <i class="icofont icofont-man-in-glasses"></i>{{ __('All Fleets') }}
                                <sup class="total-items">({{$all}})</sup>
                            </a>
                            <div class="material-border"></div>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" id="assigned-fleets" data-toggle="tab" href="#assigned_fleets" role="tab" aria-selected="true" data-rel="agent-listing"  data-status="1">
                                <i class="icofont icofont-ui-home"></i>{{ __('Assigned Fleets')}}
                                <sup class="total-items">({{$assigned}})</sup></a>
                            <div class="material-border"></div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="open-fleets" data-toggle="tab" href="#open_fleets" role="tab" aria-selected="false" data-rel="agent-listing" data-status="2">
                                <i class="icofont icofont-man-in-glasses"></i>{{ __('Open Fleets') }}
                                <sup class="total-items">({{$free}})</sup> </a>
                            <div class="material-border"></div>
                        </li>
                    </ul>

                    <div class="tab-content nav-material pt-0" id="top-tabContent">
                        <div class="tab-pane fade past-order show active" id="active_vendor" role="tabpanel" aria-labelledby="active-vendor">

                            <div class="table-responsive nagtive-margin">

                                <div class="mt-3 mb-2 row w-100 justify-content-end">
                                    <div class="float-right col-md-2">
                                        <span id="date-label-from" class="date-label">From: </span><input class="date_range_filter form-control" type="date" id="datepicker_from" />
                                        </div>
                                    <div class="float-right col-md-2">
                                            <span id="date-label-to" class="date-label">To:<input class="date_range_filter form-control" type="date" id="datepicker_to" />
                                    </div>
                                    <div class="float-right col-md-2">
                                        <span id="date-label-to" class="date-label">Driver
                                            <select class="form-control" id="driver-id" name="driver">
                                                <option value="">Select Driver</option>
                                                @forEach($drivers as $driver)
                                                <option value="{{$driver->id}}">{{$driver->name}}</option>
                                                @endForeach
                                            </select>
                                </div>
                                </div>

                                <table class="table table-striped dt-responsive nowrap w-100 all agent-listing" id="agent-listing">
                                    <thead>
                                        <tr>
                                            <th class="sort-icon">{{__("Name")}} <i class="fa fa-sort ml-1" aria-hidden="true"></i></th>
                                            <th class="sort-icon">{{__("Model")}} <i class="fa fa-sort ml-1" aria-hidden="true"></i></th>
                                            <th class="sort-icon">{{__("Make")}} <i class="fa fa-sort ml-1" aria-hidden="true"></i></th>
                                            <th class="sort-icon">{{__("Registration Name")}} <i class="fa fa-sort ml-1" aria-hidden="true"></i></th>
                                            <th class="sort-icon">{{__("Color")}} <i class="fa fa-sort ml-1" aria-hidden="true"></i></th>
                                            <th class="sort-icon">{{__("Year")}} <i class="fa fa-sort ml-1" aria-hidden="true"></i></th>
                                            <th class="sort-icon">{{__("Driver Name")}} <i class="fa fa-sort ml-1" aria-hidden="true"></i></th>
                                            <th class="sort-icon">{{__("Created At")}} <i class="fa fa-sort ml-1" aria-hidden="true"></i></th>
                                            <th class="sort-icon">{{__("Updated At")}} <i class="fa fa-sort ml-1" aria-hidden="true"></i></th>
                                            <th>{{__("Action")}}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                       
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div> <!-- end card-body-->
                </div> <!-- end card-->
                
            </div> <!-- end col -->
        </div>
    </div>
</div>

@include('fleets.modals')
@endsection

@section('script')
<script src="{{ asset('assets/js/storeAgent.js') }}"></script>
<script src="{{ asset('assets/libs/datatables/datatables.min.js') }}"></script>
<script src="{{ asset('assets/js/jquery.tagsinput-revisited.js') }}"></script>
<script src="{{ asset('telinput/js/intlTelInput.js') }}"></script>
<link rel="stylesheet" href="{{ asset('assets/css/jquery.tagsinput-revisited.css') }}" />
@include('fleets.pagescript')
@endsection