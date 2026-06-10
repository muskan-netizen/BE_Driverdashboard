@extends('layouts.vertical', ['title' =>  'Warehouse' ])
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    span.select2-selection.select2-selection--multiple { line-height: 21px;height: 38px; }
    .select2-container--default .select2-selection--multiple .select2-selection__choice { line-height: initial; }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove { color: #fff !important;border: unset !important;}
    .select2-container--default .select2-selection--multiple .select2-selection__choice__display { padding-left: 10px !important;padding-right: 0px !important; }
    .select2-container--default.select2-container--focus .select2-selection--multiple.select2-selection--clearable { display: flex !important;flex-wrap: nowrap !important; }
    .select2-container .select2-search--inline .select2-search__field { margin-top: 8px !important; }
</style>
@section('content')
    <!-- Start Content-->
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-md-8">
                <div class="page-title-box">
                    @if(isset($warehouse))
                        <h4 class="page-title">{{__("Update Warehouse")}}</h4>
                    @else
                        <h4 class="page-title">{{__("Create Warehouse")}}</h4>
                    @endif
                </div>
            </div>
            <div class="col-sm-4 text-right btn-auto">
                <button type="button" class="btn btn-blue waves-effect waves-light openModal" data-toggle="modal" data-target="" data-backdrop="static" data-keyboard="false"><i class="mdi mdi-plus-circle mr-1"></i> {{__("Add Amenities")}}</button>
            </div>
        </div>
        <!-- end page title -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="text-sm-left">
                            @if (\Session::has('success'))
                                <div class="alert alert-success">
                                    <span>{!! \Session::get('success') !!}</span>
                                </div>
                            @endif
                        </div>
                        @if(isset($warehouse))
                            <form id="UpdateWarehouse" method="post" action="{{route('warehouse.update', $warehouse->id)}}" enctype="multipart/form-data">
                            @method('PUT')
                        @else
                            <form id="StoreWarehouse" method="post" action="{{route('warehouse.store')}}" enctype="multipart/form-data">
                        @endif
                        @csrf
                        <input type="hidden" name="warehouse_id" value="{{$warehouse->id??''}}">
                        <div class=" row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name" class="control-label">{{__('Name')}}</label>
                                    <input type="text" class="form-control" name="name" id="name" value="{{ old('name', $warehouse->name ?? '')}}" placeholder="Enter name" required>
                                    @if($errors->has('name'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('name') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="code" class="control-label">{{__("Code")}}</label>
                                    <input type="text" class="form-control" id="code" name="code" value="{{ old('code', $warehouse->code ?? '')}}" placeholder="Enter code" required>
                                    @if($errors->has('code'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('code') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email" class="control-label">{{__("Email")}}</label>
                                    <input type="text" class="form-control" id="email" name="email" value="{{ old('email', $warehouse->email ?? '')}}" placeholder="Enter email" required>
                                    @if($errors->has('email'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('email') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone_no" class="control-label">{{__("Phone Number")}}</label>
                                    <input type="text" class="form-control" id="phone_no" name="phone_no" value="{{ old('phone_no', $warehouse->phone_no ?? '')}}" placeholder="Enter Phone number" required>
                                    @if($errors->has('phone_no'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('phone_no') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <label for="address" class="control-label">{{__("Address")}}</label>
                                <div class="form-group input-group" id="addressInput">
                                    <input type="text" id="address" name="address" class="form-control" placeholder="{{__('Address')}}" value="{{ old('address', $warehouse->address ?? '')}}">
                                    <div class="input-group-append">
                                        <button class="btn btn-xs btn-dark waves-effect waves-light showMap" type="button" num="add1"> <i class="mdi mdi-map-marker-radius"></i></button>
                                    </div>
                                    <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', $warehouse->latitude ?? 0)}}" />
                                    <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', $warehouse->longitude ?? 0)}}" />
                                    <span class="invalid-feedback" role="alert" >
                                        <strong></strong>
                                    </span>
                                </div>
                            </div>
                            {{-- <div class="col-md-6">
                                <div class="form-group">
                                    <label for="address" class="control-label">{{__("Address")}}</label>
                                    <input type="tel" name="address" class="form-control" value="{{ old('address', $warehouse->address ?? '')}}"id="address" placeholder="Enter address">
                                    @if($errors->has('address'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('address') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div> --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="check_label">
                                        <label for="amenities" class="control-label">{{__("Amenities")}}</label>
                                    </div>
                                    @php
                                        $amenity = [];
                                        if(!empty($warehouse->amenity)){
                                            $amenity = $warehouse->amenity->pluck('id')->toArray();
                                        }
                                    @endphp
                                    @if(!empty($amenities) && $amenities->count() > 0)
                                        @foreach ($amenities as $ameniti)
                                            <label class="checkbox-inline">
                                                <input type="checkbox" name="amenities[]" value="{{$ameniti->id}}" @if(in_array($ameniti->id, $amenity)) checked @endif>&nbsp;&nbsp;{{$ameniti->name}}
                                            </label>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="category" class="control-label">{{__("Category")}}</label>
                                    @php
                                        $categoryIds = [];
                                        if(!empty($warehouse->category)){
                                            $categoryIds = $warehouse->category->pluck('id')->toArray();
                                        }
                                    @endphp
                                    <select name="category[]" class="form-control" multiple="multiple" id="category">
                                        @foreach ($category as $cat)
                                            <option value="{{$cat->id}}"  @if(in_array($cat->id, $categoryIds)) selected @endif>{{$cat->slug}}</option>
                                        @endforeach
                                    </select>
                                    @if($errors->has('category'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('category') }}</strong>
                                        </span>
                                    @endif                                        
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="category" class="control-label">{{__("Warehouse Type")}}</label>
                                   
                                    <select name="type" class="form-control" id="warehhouse-type">
                                       
                                     <option value=""  >Select</option>
                                     <option value="0" {{ ($warehouse->type ?? '' == 0) ? 'selected':'' }} >Small Hub</option>
                                     <option value="1" {{ ($warehouse->type ?? '' == 1) ? 'selected':'' }}>Large Hub</option>
                                      
                                    </select>
                                                                         
                                </div>
                            </div>
                        </div>
                        <div class="row mb-2 mt-2">
                            <div class="col-12">
                                <div class="form-group mb-0 text-center">
                                    <button class="btn btn-blue btn-block" type="submit"> {{__("Submit")}} </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@include('warehouse.warehouse-modal')
@section('script')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    @include('warehouse.warehouse-script')
@endsection
