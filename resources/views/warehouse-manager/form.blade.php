@extends('layouts.vertical', ['title' =>  'Warehouse Managers' ])
@section('css')
    <link href="{{asset('assets/libs/dropzone/dropzone.min.css')}}" rel="stylesheet" type="text/css" />
    <link href="{{asset('assets/libs/dropify/dropify.min.css')}}" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="{{ asset('telinput/css/intlTelInput.css') }}" type="text/css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        span.select2-selection.select2-selection--multiple { line-height: 10px;height: 38px; }
        .select2-container--default .select2-selection--multiple .select2-selection__choice { line-height: initial; }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove { color: #fff !important;border: unset !important;}
        .select2-container--default .select2-selection--multiple .select2-selection__choice__display { padding-left: 10px !important;padding-right: 0px !important; }
        .select2-container--default.select2-container--focus .select2-selection--multiple.select2-selection--clearable { display: flex !important;flex-wrap: nowrap !important; }
    </style>
@endsection

@section('content')
    <!-- Start Content-->
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    @if(isset($WarehouseManager))
                        <h4 class="page-title">{{__("Update Warehouse Manager")}}</h4>
                    @else
                        <h4 class="page-title">{{__("Create Warehouse Manager")}}</h4>
                    @endif
                </div>
            </div>
        </div>
        <!-- end page title -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        @if(isset($WarehouseManager))
                        <form id="UpdateSubadmin" method="post" action="{{route('warehouse-manager.update', $WarehouseManager->id)}}" enctype="multipart/form-data">
                        @method('PUT')
                        @else
                        <form id="StoreSubadmin" method="post" action="{{route('warehouse-manager.store')}}" enctype="multipart/form-data">
                        @endif
                        @csrf
                            <div class=" row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name" class="control-label">{{__('Name')}}</label>
                                        <input type="text" class="form-control" name="name" id="name" value="{{ old('name', $WarehouseManager->name ?? '')}}" placeholder="John Doe" required>
                                        @if($errors->has('name'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('name') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email" class="control-label">{{__("Email")}}</label>
                                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $WarehouseManager->email ?? '')}}" placeholder={{__("Enter email address")}} required>
                                        @if($errors->has('email'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('email') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="hidden" name="country_code" id="countryCode">
                                    <div class="form-group" id="phone_numberInput">
                                        <label for="phone_number" class="control-label">{{__("Phone Number")}}</label>
                                        <div class="input-group">
                                            @if(isset($WarehouseManager) && !empty($WarehouseManager))
                                            <input type="tel" name="phone_number" class="form-control xyz" value="{{old('full_number',$WarehouseManager->phone_number)}}"id="phone_number" placeholder="9876543210" maxlength="14">
                                            <input type="hidden" id="countryData" name="countryData" value="us">
                                            <input type="hidden" id="dialCode" name="dialCode" value="{{ old('dialCode', $WarehouseManager->dial_code)}}">
                                            @else
                                            <input type="tel" name="phone_number" class="form-control xyz" value="{{old('full_number')}}"id="phone_number" placeholder="9876543210" maxlength="14">
                                            <input type="hidden" id="countryData" name="countryData" value="us">
                                            <input type="hidden" id="dialCode" name="dialCode" value="{{ old('dialCode')}}">
                                            @endif
                                         </div>
                                        @if($errors->has('phone_number'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('phone_number') }}</strong>
                                        </span>
                                        @endif
                                        <span class="invalid-feedback" role="alert">
                                            <strong></strong>
                                        </span>
                                    </div>                                    
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="team_access" class="control-label">{{__("Warehouses")}}</label>
                                        @php
                                            $warehouseIds = [];
                                            if(!empty($WarehouseManager->warehouse)){
                                                $warehouseIds = $WarehouseManager->warehouse->pluck('id')->toArray();
                                            }
                                        @endphp
                                        <select name="warehouses[]" class="form-control" id="warehouses" multiple="multiple">
                                            @foreach ($warehouses as $warehouse)
                                                <option value="{{$warehouse->id}}" @if(in_array($warehouse->id, $warehouseIds)) selected @endif>{{$warehouse->name}}</option>
                                            @endforeach
                                        </select>
                                        @if($errors->has('warehouses'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('warehouses') }}</strong>
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password" class="control-label">{{__("Status")}}</label>
                                        <?php $status =  (isset($WarehouseManager))?$WarehouseManager->status:'';?>
                                        <select name="status" class="form-control">
                                            <option value="1" <?=($status==1)?'selected':'';?>>{{__("Active")}}</option>
                                            <option value="2" <?=($status==2)?'selected':'';?>>{{__("Inactive")}}</option>
                                        </select>                                        
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-2 mt-4">
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
@section('script')
    <script src="{{ asset('assets/js/jquery-ui.min.js') }}" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="{{ asset('assets/css/jquery-ui.css') }}">
    <script src="{{ asset('assets/js/storeAgent.js') }}"></script>
    <script src="{{ asset('assets/libs/dropzone/dropzone.min.js') }}"></script>
    <script src="{{ asset('assets/libs/dropify/dropify.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/form-fileuploads.init.js') }}"></script>
    {{-- <script src="{{ asset('assets/libs/datatables/datatables.min.js') }}"></script>  --}}
    <script src="{{ asset('assets/js/jquery.tagsinput-revisited.js') }}"></script>
    <script src="{{ asset('telinput/js/intlTelInput.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('assets/css/jquery.tagsinput-revisited.css') }}" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $('.xyz').change(function() {
        var phonevalue = $('.xyz').val();
        $("#countryCode").val(mobile_number.getSelectedCountryData().dialCode);
    });
//     const phoneInputs = document.querySelectorAll(".phone_number");
//                 phoneInputs.forEach(phoneInitializer);
//                 function phoneInitializer(element)
//                 {
//                     let phone_number_intltel = window.intlTelInput(element, {
//                     separateDialCode: true,
//                     preferredCountries: ["{{getCountryCode()}}"],
//                     initialCountry: "{{getCountryCode()}}",
//                     hiddenInput: "full_number",
//                     utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.3/js/utils.js"
//                     });
//                     element.addEventListener("countrychange", function() {
//                     const dialCodeInput = element.nextElementSibling;
//                     dialCodeInput.value =phone_number_intltel.getSelectedCountryData().dialCode;
//                     });
//                 }
    phoneInput();
    function phoneInput() {
        var input = document.querySelector(".xyz");
        var mobile_number_input = document.querySelector(".xyz");
        mobile_number = window.intlTelInput(mobile_number_input, {
            separateDialCode: true,
            hiddenInput: "full_number",
            initialCountry: '{{$selectedCountryCode ?? ''}}',
            utilsScript: "{{ asset('telinput/js/utils.js') }}",        
        });        
    }
    $(document).delegate('.iti__country', 'click', function() {
        var code = $(this).attr('data-country-code');
        $('#countryData').val(code);
        var dial_code = $(this).attr('data-dial-code');
        $('#dialCode').val(dial_code);
    });
    $(document).ready(function(){
        $("#warehouses").select2({
            allowClear: true,
            width: "resolve",
            placeholder: "Select Warehouse"
        });
    });
</script>
@endsection