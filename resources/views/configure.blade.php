@extends('layouts.vertical', ['title' => 'Configure'])

@section('css')
<link href="{{ asset('assets/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
<style>

</style>
@endsection
@php

$sms_crendential = json_decode($preference->sms_credentials);

@endphp
@section('content')
    <style>
        .alMultiSelect .btn{border-radius: 7px;}
        .threshold-section{display: none;}
    </style>
    <!-- Start Content-->
    <div class="container-fluid">

        @if (\Session::has('success'))
            <div class="alert alert-success">
                <span>{!! \Session::get('success') !!}</span>
            </div>
        @endif
        <!-- start page title -->
        <div class="row maps-configure-mobile">
            <div class="col-12">
                <div class="page-title-box">
                    <h4 class="page-title">{{ __('MAP, SMS and EMAILS') }}</h4>
                </div>
            </div>
        </div>
        <!-- end page title -->
        <div class="row configure_responsive">
            <div class="col-md-4 mb-3">
                <form method="POST" class="h-100" action="{{ route('preference', Auth::user()->code) }}">
                    @csrf
                    <div class="card-box h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h4 class="header-title mb-0">{{__("Map Configuration")}}</h4>
                            <button class="btn btn-outline-info d-block" type="submit"> {{__('Save')}} </button>
                        </div>
                        <p class="sub-header">{{ __("View and update your Map type and it's API key.") }}</p>

                        <div class="row mb-2">
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label for="currency">{{ __('MAP TYPE') }}</label>
                                    <select class="form-control" id="map_type" name="map_type">
                                        <option value="google_maps"
                                            {{ isset($preference) && $preference->map_type == 'google_maps' ? 'selected' : '' }}>
                                            {{__('Google Maps')}}</option>
                                    </select>
                                    @if ($errors->has('map_type'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('map_type') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label for="map_key_1">{{ __('API Key') }}</label>
                                    <input type="password" name="map_key_1" id="map_key_1" placeholder="kjadsasd66asdas"
                                        class="form-control"
                                        value="{{ old('map_key_1', $preference->map_key_1 ?? '') }}">
                                    @if ($errors->has('map_key_1'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('map_key_1') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-12"> 
                                <div class="form-group d-flex justify-content-between mb-3">
                                <label for="" class="mr-2 mb-0">{{__("Toll Api")}} </label>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input " id="toll_fee" name="toll_fee" {{ (!empty($preference->toll_fee) && $preference->toll_fee > 0) ? 'checked' :'' }}>
                                            <label class="custom-control-label" for="toll_fee"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-md-4 mb-3">
                <form method="POST" class="h-100" action="{{ route('preference', Auth::user()->code) }}">
                    @csrf
                    <div class="card-box h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h4 class="header-title mb-0">{{__("SMS")}}</h4>
                            <button class="btn btn-outline-info d-block" type="submit"> {{__('Save')}} </button>
                        </div>
                        <p class="sub-header">{{ __("View and update your SMS Gateway and it's API keys.") }}</p>
                        <div class="d-flex align-items-center justify-content-between mt-3 mb-2">
                            <h5 class="font-weight-normal m-0">{{ __('Send Static Otp ') }} {{ __(getAgentNomenclature()) }}</h5>
                            
                            <div class="custom-control custom-switch">
     
                                <input type="checkbox" class="custom-control-input"
                                    id="cancelOrderCustomSwitch_static_otp"
                                    name="static_otp"
                                    {{ (isset( $sms_crendential->static_otp ) && $sms_crendential->static_otp == 1) ? 'checked' : '' }}>
                                <label class="custom-control-label"
                                    for="cancelOrderCustomSwitch_static_otp"></label>
                                
                            </div>
                            
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label for="sms_provider">{{ __('CURRENT SELECTION') }}</label>
                                    <select class="form-control" id="sms_provider" name="sms_provider"
                                        onchange="toggle_smsFields(this)">
                                        <!-- <option value="Twilio" {{ isset($preference) && $preference->sms_provider == 'Twilio' ? 'selected' : '' }}>
                                            Twilio</option> -->
                                        @foreach ($smsTypes as $sms)
                                            <option data-id="{{ $sms->keyword }}_fields" value="{{ $sms->id }}"
                                                {{ isset($preference) && $preference->sms_provider == $sms->id ? 'selected' : '' }}>
                                                {{ $sms->provider }} </option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('sms_provider'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('sms_provider') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <!-- Twillio Serive -->
                        {{-- <div class="sms_fields row mx-0" id="twilio_fields" style="display : {{$preference->sms_provider == 1 ? 'flex' : 'none'}};">
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="sms_provider_number">{{__("Number")}}</label>
                                    <input type="text" name="sms_provider_number" id="sms_provider_number" placeholder="+17290876681" class="form-control" value="{{ old('sms_provider_number', $preference->sms_provider_number ?? '') }}">
                                    @if ($errors->has('sms_provider_number'))
                                    <span class="text-danger" role="alert">
                                        <strong>{{ $errors->first('sms_provider_number') }}</strong>
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="sms_provider_key_1">{{__("Account SID")}}</label>
                                    <input type="text" name="sms_provider_key_1" id="sms_provider_key_1" placeholder={{__("Account Sid")}} class="form-control" value="{{ old('sms_provider_key_1', $preference->sms_provider_key_1 ?? '') }}">
                                    @if ($errors->has('sms_provider_key_1'))
                                    <span class="text-danger" role="alert">
                                        <strong>{{ $errors->first('sms_provider_key_1') }}</strong>
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                         <div class="row mb-0">
                            <div class="col-md-6">
                                <p class="sub-header">
                                    To Configure your Bumbl SMS Service, go to <a href="#">Bumble Dashboard</a>
                                </p>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="sms_provider_key_2">{{__("Auth Token")}}</label>
                                    <input type="password" name="sms_provider_key_2" id="sms_provider_key_2" placeholder={{__("Auth Token")}} class="form-control" value="{{ old('sms_provider_key_2', $preference->sms_provider_key_2 ?? '') }}">
                                    @if ($errors->has('sms_provider_key_2'))
                                    <span class="text-danger" role="alert">
                                        <strong>{{ $errors->first('sms_provider_key_2') }}</strong>
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div> --}}
                        <!-- For twillio -->
                        <div class="sms_fields row mx-0" id="twilio_fields"
                            style="display : {{ $preference->sms_provider == 1 || $preference->sms_provider == 'Twilio' ? 'flex' : 'none' }};">
                            <div class="col-12">
                                <div class="form-group mb-2">
                                    <label for="sms_from">{{ __('SMS From') }}</label>
                                    <input type="text" name="sms_from" id="sms_from" placeholder="" class="form-control"
                                        value="{{ old('sms_from', $preference->sms_provider_number ?? '') }}">
                                    @if ($errors->has('sms_from'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('sms_from') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group mb-2">
                                    <label for="sms_key">{{ __('API KEY') }}</label>
                                    <input type="text" name="sms_key" id="sms_key" placeholder="" class="form-control"
                                        value="{{ old('sms_key', $preference->sms_provider_key_1 ?? '') }}">
                                    @if ($errors->has('sms_key'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('sms_key') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group mb-2">
                                    <label for="sms_secret">{{ __('API Secret') }}</label>
                                    <input type="password" name="sms_secret" id="sms_secret" placeholder=""
                                        class="form-control"
                                        value="{{ old('sms_secret', $preference->sms_provider_key_2 ?? '') }}">
                                    @if ($errors->has('sms_secret'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('sms_secret') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>



                        <div class="row sms_fields mx-0" id="mTalkz_fields"
                            style="display : {{ $preference->sms_provider == 2 ? 'flex' : 'none' }};">
                            <div class="col-12">
                                <div class="form-group mb-2">
                                    <label for="mtalkz_api_key">{{ __('API Key') }}</label>
                                    <input type="text" name="mtalkz_api_key" id="mtalkz_api_key" placeholder=""
                                        class="form-control"
                                        value="{{ old('mtalkz_api_key', $sms_crendential->api_key ?? '') }}">
                                    @if ($errors->has('mtalkz_api_key'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('mtalkz_api_key') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group mb-2">
                                    <label for="mtalkz_sender_id">{{ __('Sender ID') }}</label>
                                    <input type="text" name="mtalkz_sender_id" id="mtalkz_sender_id" placeholder=""
                                        class="form-control"
                                        value="{{ old('mtalkz_sender_id', $sms_crendential->sender_id ?? '') }}">
                                    @if ($errors->has('mtalkz_sender_id'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('mtalkz_sender_id') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- For mTalkz -->
                        <div class="row sms_fields mx-0" id="mazinhost_fields"
                            style="display : {{ $preference->sms_provider == 3 ? 'flex' : 'none' }};">
                            <div class="col-12">
                                <div class="form-group mb-2">
                                    <label for="mazinhost_api_key">{{ __('API Key') }}</label>
                                    <input type="text" name="mazinhost_api_key" id="mazinhost_api_key" placeholder=""
                                        class="form-control"
                                        value="{{ old('mazinhost_api_key', $sms_crendential->api_key ?? '') }}">
                                    @if ($errors->has('mazinhost_api_key'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('mazinhost_api_key') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group mb-2">
                                    <label for="mazinhost_sender_id">{{ __('Sender ID') }}</label>
                                    <input type="text" name="mazinhost_sender_id" id="mazinhost_sender_id" placeholder=""
                                        class="form-control"
                                        value="{{ old('mazinhost_sender_id', $sms_crendential->sender_id ?? '') }}">
                                    @if ($errors->has('mazinhost_sender_id'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('mazinhost_sender_id') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <!-- For unifonic_fields -->
                        <div class="row sms_fields mx-0" id="unifonic_fields"
                            style="display : {{ $preference->sms_provider == 4 ? 'flex' : 'none' }};">
                            <div class="col-12">
                                <div class="form-group mb-2">
                                    <label for="unifonic_app_id">{{ __('App Id') }}</label>
                                    <input type="text" name="unifonic_app_id" id="unifonic_app_id" placeholder=""
                                        class="form-control"
                                        value="{{ old('unifonic_app_id', $sms_crendential->unifonic_app_id ?? '') }}">
                                    @if ($errors->has('unifonic_app_id'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('unifonic_app_id') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group mb-2">
                                    <label for="unifonic_account_email">{{ __('Unifonic Account Email') }}</label>
                                    <input type="text" name="unifonic_account_email" id="unifonic_account_email"
                                        placeholder="" class="form-control"
                                        value="{{ old('unifonic_account_email', $sms_crendential->unifonic_account_email ?? '') }}">
                                    @if ($errors->has('unifonic_account_email'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('unifonic_account_email') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group mb-2">
                                    <label for="unifonic_account_password">{{ __('Unifonic Account Password') }}</label>
                                    <input type="text" name="unifonic_account_password" id="unifonic_account_password"
                                        placeholder="" class="form-control"
                                        value="{{ old('unifonic_account_password', $sms_crendential->unifonic_account_password ?? '') }}">
                                    @if ($errors->has('unifonic_account_password'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('unifonic_account_password') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>


                         <!-- For arkesel -->
                        <div class="row sms_fields mx-0" id="arkesel_fields" style="display : {{$preference->sms_provider == 5 ? 'flex' : 'none'}};">
                            <div class="col-12">
                                <div class="form-group mb-2">
                                <label for="arkesel_api_key">{{ __("API Key") }}</label>
                                <input type="text" name="arkesel_api_key" id="arkesel_api_key" placeholder="" class="form-control" value="{{ old('arkesel_api_key', $sms_crendential->api_key ?? '')}}">
                                @if($errors->has('arkesel_api_key'))
                                <span class="text-danger" role="alert">
                                    <strong>{{ $errors->first('arkesel_api_key') }}</strong>
                                </span>
                                @endif
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group mb-2">
                                <label for="arkesel_sender_id">{{ __("Sender ID") }}</label>
                                <input type="text" name="arkesel_sender_id" id="arkesel_sender_id" placeholder="" class="form-control" value="{{ old('arkesel_sender_id', $sms_crendential->sender_id ?? '')}}">
                                @if($errors->has('arkesel_sender_id'))
                                <span class="text-danger" role="alert">
                                    <strong>{{ $errors->first('arkesel_sender_id') }}</strong>
                                </span>
                                @endif
                                </div>
                            </div>
                        </div>

                        <!-- For Vonage (nexmo) -->
                        <div class="row sms_fields mx-0" id="vonage_fields" style="display : {{$preference->sms_provider == 6 ? 'flex' : 'none'}};">
                            <div class="col-12">
                                <div class="form-group mb-2">
                                <label for="vonage_api_key">{{ __("API Key") }}</label>
                                <input type="text" name="vonage_api_key" id="vonage_api_key" placeholder="" class="form-control" value="{{ old('vonage_api_key', $sms_crendential->api_key ?? '')}}">
                                @if($errors->has('vonage_api_key'))
                                <span class="text-danger" role="alert">
                                    <strong>{{ $errors->first('vonage_api_key') }}</strong>
                                </span>
                                @endif
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group mb-2">
                                <label for="vonage_secret_key">{{ __("Secret Key") }}</label>
                                <input type="password" name="vonage_secret_key" id="vonage_secret_key" placeholder="" class="form-control" value="{{ old('vonage_secret_key', $sms_crendential->secret_key ?? '')}}">
                                @if($errors->has('vonage_secret_key'))
                                <span class="text-danger" role="alert">
                                    <strong>{{ $errors->first('vonage_secret_key') }}</strong>
                                </span>
                                @endif
                                </div>
                            </div>
                        </div>

                        <!-- For SMS Partner France -->
                        <div class="row sms_fields mx-0" id="sms_partner_fields" style="display : {{ $preference->sms_provider == 7 ? 'flex' : 'none' }};">
                            <div class="col-12">
                                <div class="form-group mb-2">
                                    <label for="sms_partner_api_key">{{ __('API Key') }}</label>
                                    <input type="password" name="sms_partner_api_key" id="sms_partner_api_key" placeholder=""
                                        class="form-control"
                                        value="{{ old('sms_partner_api_key', $sms_crendential->api_key ?? '') }}">
                                    @if ($errors->has('sms_partner_api_key'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('sms_partner_api_key') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group mb-2">
                                    <label for="sms_partner_sender_id">{{ __('Sender Id') }}</label>
                                    <input type="text" name="sms_partner_sender_id" id="sms_partner_sender_id"
                                        placeholder="" class="form-control"
                                        value="{{ old('sms_partner_sender_id', $sms_crendential->sender_id ?? '') }}">
                                    @if ($errors->has('sms_partner_sender_id'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('sms_partner_sender_id') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
            <div class="col-md-4 mb-3">
                <form method="POST" class="h-100" action="{{ route('smtp') }}">
                    @csrf
                    <div class="card-box h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h4 class="header-title mb-0">{{__("Email")}} (SMTP)</h4>
                            <button class="btn btn-outline-info d-block" type="submit"> {{__('Save')}} </button>
                        </div>
                        <p class="sub-header">{{ __('View and update your SMTP credentials.') }}</p>
                        <div class="row mb-2 configure_responsive">

                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="host">{{ __('Host') }}</label>
                                    <input type="text" name="host" id="host" placeholder="smtp.mailgun.org"
                                        class="form-control" value="{{ old('host', $smtp_details->host ?? '') }}"
                                        required>
                                    @if ($errors->has('host'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('host') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="port">{{ __('Port') }}</label>
                                    <input type="text" name="port" id="port" placeholder="587" class="form-control"
                                        value="{{ old('port', $smtp_details->port ?? '') }}" required>
                                    @if ($errors->has('port'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('port') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>


                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="encryption">{{ __('Encryption') }}</label>
                                    <select class="form-control" id="encryption" name="encryption">
                                        <option value="tls">
                                            tls</option>
                                    </select>
                                    @if ($errors->has('sms_provider'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('sms_provider') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>



                        </div>
                        {{-- <div class="row mb-0">
                        <div class="col-md-6">
                            <p class="sub-header">
                                To Configure your Bumbl SMS Service, go to <a href="#">Bumble Dashboard</a>
                            </p>
                        </div>
                    </div> --}}
                        <div class="row mb-2 configure_responsive">

                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="username">{{ __('User-Name') }}</label>
                                    <input type="text" name="username" id="username" placeholder="user@gmail.com"
                                        class="form-control"
                                        value="{{ old('username', $smtp_details->username ?? '') }}" required>
                                    @if ($errors->has('user_name'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('user_name') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="password">{{ __('Password') }}</label>
                                    <input type="password" name="password" id="password" placeholder="********"
                                        class="form-control"
                                        value="{{ old('password', $smtp_details->password ?? '') }}" required>
                                    @if ($errors->has('password'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('password') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="from_address">{{ __('From Address') }}</label>
                                    <input type="text" name="from_address" id="from_address" placeholder="user@gmail.com"
                                        class="form-control"
                                        value="{{ old('from_address', $smtp_details->from_address ?? '') }}" required>
                                    @if ($errors->has('from_address'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('from_address') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row maps-configure-mobile">
            <div class="col-12">
                <div class="page-title-box">
                    <h4 class="page-title">{{ __('FCM, API ACCESS TOKEN and CUSTOM DOMAIN') }}</h4>
                </div>
            </div>
        </div>
        <div class="row configure_responsive">
            <div class="col-md-4 mb-3">
                <form method="POST" class="h-100" action="{{ route('preference', Auth::user()->code) }}">
                    @csrf
                    <div class="card-box h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h4 class="header-title mb-0">{{__("Personal Access Token")}}</h4>
                            <button class="btn btn-outline-info d-block" type="submit"> {{__('Save')}} </button>
                        </div>
                        <p class="sub-header">
                            {{ __('View and Generate API keys.') }}
                        </p>
                        <div class="row">

                            <div class="col-12">
                                <div class="form-group mb-3">
                                    <div class="domain-outer border-0 d-flex align-items-center justify-content-between">
                                        <label for="personal_access_token_v1">V1 {{ __('API ACCESS TOKEN') }}</label>
                                        <span class="text-right col-6 col-md-6"><a
                                                href="javascript: genrateKeyAndToken();">{{ __('Generate Key') }}</a></span>

                                    </div>
                                    <input type="text" name="personal_access_token_v1" id="personal_access_token_v1"
                                        placeholder="kjadsasd66asdas" class="form-control"
                                        value="{{ old('personal_access_token_v1', $preference->personal_access_token_v1 ?? '') }}">
                                    @if ($errors->has('personal_access_token_v1'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('personal_access_token_v1') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-md-4 mb-3">
                <form method="POST" class="h-100" action="{{ route('preference', Auth::user()->code) }}">
                    @csrf
                    <div class="card-box h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h4 class="header-title mb-0">{{__("Custom Domain")}}</h4>
                            <button class="btn btn-outline-info d-block" type="submit"> {{__('Save')}} </button>
                        </div>
                        <p class="sub-header">
                            {{ __('View and update your Domain.') }}
                        </p>
                        <div class="row">
                            <div class="col-12">
                                <label for="custom_domain">{{ __('Custom Domain') }}</label>
                                *{{ __('Make sure you already pointed to IP') }} ({{ \env('IP') }})
                                {{ __('from your domain.') }}
                                <div class="domain-outer d-flex align-items-center">
                                    <div class="domain_name">https://</div>
                                    <input type="text" name="custom_domain" id="custom_domain" placeholder="dummy.com"
                                        class="form-control"
                                        value="{{ old('custom_domain', Auth::user()->custom_domain ?? '') }}">
                                </div>
                                @if ($errors->has('custom_domain'))
                                    <span class="text-danger" role="alert">
                                        <strong>{{ $errors->first('custom_domain') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-md-4 mb-3">
                <form method="POST" class="h-100" action="{{ route('preference', Auth::user()->code) }}">
                    @csrf
                    <div class="card-box h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h4 class="header-title mb-0">{{__("FCM Server Key")}}</h4>
                            <button class="btn btn-outline-info d-block" type="submit"> {{__('Save')}} </button>
                        </div>
                        <p class="sub-header">
                            {{ __('View and Update FCM key.') }}
                        </p>
                        <div class="row">

                            <div class="col-12">
                                <div class="form-group">
                                    <div class="domain-outer border-0 d-flex align-items-center justify-content-between">
                                        <label for="personal_access_token_v1">{{ __('FCM Key') }}</label>
                                    </div>
                                    <input type="text" name="fcm_server_key" id="fcm_server_key"
                                        placeholder="kjadsasd66asdas" class="form-control"
                                        value="{{ old('fcm_server_key', $preference->fcm_server_key ?? '') }}">
                                    @if ($errors->has('fcm_server_key'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('fcm_server_key') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <h4 class="page-title">{{ __(getAgentNomenclature()) }}</h4>
                </div>
            </div>
        </div>

        <div class="row configure_responsive">
            <div class="col-md-4 mb-3">
                <div class="card-box h-100">
                    <form method="POST" class="h-100" action="{{ route('preference', Auth::user()->code) }}">
                        @csrf
                        <input type="hidden" name="cancel_verify_edit_order_config" value="1">
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h4 class="header-title mb-0"></h4>
                                    <button class="btn btn-outline-info d-block" type="submit"> {{__('Save')}} </button>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mt-3 mb-2">
                                    <h5 class="font-weight-normal m-0">{{ __('Cancel Order By') }} {{ __(getAgentNomenclature()) }}</h5>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input"
                                            id="cancelOrderCustomSwitch_{{ $preference->is_cancel_order_driver }}"
                                            name="is_cancel_order_driver"
                                            {{ $preference->is_cancel_order_driver == 1 ? 'checked' : '' }}>
                                        <label class="custom-control-label"
                                            for="cancelOrderCustomSwitch_{{ $preference->is_cancel_order_driver }}"></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between mt-3 mb-2">
                                    <h5 class="font-weight-normal m-0">{{ __(getAgentNomenclature()) }} {{ __('Registration Phone Verification') }}</h5>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input event_type"
                                            id="customSwitch_{{ $preference->verify_phone_for_driver_registration }}"
                                            name="verify_phone_for_driver_registration"
                                            {{ $preference->verify_phone_for_driver_registration == 1 ? 'checked' : '' }}>
                                        <label class="custom-control-label"
                                            for="customSwitch_{{ $preference->verify_phone_for_driver_registration }}"></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between mt-3 mb-2">
                                    <h5 class="font-weight-normal m-0">{{ __('Edit Order By') }} {{ __(getAgentNomenclature()) }}</h5>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input"
                                            id="editOrderCustomSwitch_{{ $preference->is_edit_order_driver }}"
                                            name="is_edit_order_driver"
                                            {{ $preference->is_edit_order_driver == 1 ? 'checked' : '' }}>
                                        <label class="custom-control-label"
                                            for="editOrderCustomSwitch_{{ $preference->is_edit_order_driver }}"></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between mt-3 mb-2">
                                    <h5 class="font-weight-normal m-0">{{ __('Manage ') }}{{ Session::get('agent_name') ? Session::get('agent_name') : 'Agent' }} {{ __('Schedule') }} </h5>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input"
                                            id="editFleetCustomSwitch_{{ $preference->is_driver_slot }}"
                                            name="is_driver_slot"
                                            {{ $preference->is_driver_slot == 1 ? 'checked' : '' }}>
                                        <label class="custom-control-label"
                                            for="editFleetCustomSwitch_{{ $preference->is_driver_slot }}"></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between mt-3 mb-2">
                                    <h5 class="font-weight-normal m-0">{{ __('Fleet Managements ') }}</h5>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input"
                                            id="editDriverSlotCustomSwitch_{{ $preference->manage_fleet }}"
                                            name="manage_fleet"
                                            {{ $preference->manage_fleet == 1 ? 'checked' : '' }}>
                                        <label class="custom-control-label"
                                            for="editDriverSlotCustomSwitch_{{ $preference->manage_fleet }}"></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between mt-3 mb-2">
                                    <h5 class="font-weight-normal m-0">{{ __('Show Cab Pooling Toggle ') }}</h5>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input"
                                            id="editCabPoolingSwitch"
                                            name="is_cab_pooling_toggle"
                                            {{ $preference->is_cab_pooling_toggle == 1 ? 'checked' : '' }}>
                                        <label class="custom-control-label"
                                            for="editCabPoolingSwitch"></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row" id="radius_for_pooling_div" style="display:{{ $preference->is_cab_pooling_toggle == 1 ? '' : 'none' }}">
                            <div class="col-9">
                                <div class=" align-items-center justify-content-between mt-3 mb-2">
                                    <h5 class="font-weight-normal m-0">{{ __('Radius To Show Pooling Suggessions (KM)') }}</h5>
                                </div>
                            </div>
                            <div class="col-3 pt-2">
                                <input class="form-control" type="number" id="radius_for_pooling_km" name="radius_for_pooling_km" value="{{ old('radius_for_pooling_km', $preference->radius_for_pooling_km ?? '0') }}" min="0">
                            </div>
                        </div>
                        {{-- </div> --}}
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between mt-3 mb-2">
                                    <h5 class="font-weight-normal m-0">{{ Session::has('agent_name') ? Session::get('agent_name') : 'Agent' }} {{ __('Freelancing mode') }} </h5>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input"
                                            id="is_freelancer_{{ $preference->is_freelancer }}"
                                            name="is_freelancer"
                                            {{ $preference->is_freelancer == 1 ? 'checked' : '' }}>
                                        <label class="custom-control-label"
                                            for="is_freelancer_{{ $preference->is_freelancer }}"></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between mt-3 mb-2">
                                    <h5 class="font-weight-normal m-0">{{ __('Enable Bid & Ride Related Features') }}</h5>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input"
                                            id="editBidRideSwitch"
                                            name="is_bid_ride_toggle"
                                            {{ $preference->is_bid_ride_toggle == 1 ? 'checked' : '' }}>
                                        <label class="custom-control-label"
                                            for="editBidRideSwitch"></label>
                                    </div>
                                </div>
                            </div>
                        </div> 
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between mt-3 mb-2">
                                    <h5 class="font-weight-normal m-0">{{ Session::has('agent_name') ? Session::get('agent_name') : 'Agent' }} {{ __('Geting task on Route of Home address') }}</h5>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input"
                                            id="is_go_to_home"
                                            name="is_go_to_home"
                                            {{ $preference->is_go_to_home == 1 ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="is_go_to_home"></label>
                                           
                                          
                                    </div>
                                </div>
                            </div>
                            <div id='go_to_home_radians' class="col-12 {{ $preference->is_go_to_home == 1 ? '' : 'd-none' }}">
                                <input type="number"  class="form-control" placeholder="{{ __('Go to home Radians in KM') }}"  id="" value="{{ $preference->go_to_home_radians }}" name="go_to_home_radians" >
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between mt-3 mb-2">
                                    <h5 class="font-weight-normal m-0">{{ __('Enable Road Side Pick Features') }}</h5>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input"
                                            id="editRoadSideSwitch"
                                            name="is_road_side_toggle"
                                            {{ $preference->is_road_side_toggle == 1 ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="editRoadSideSwitch"></label>
                                    </div>
                                </div>
                            </div> 
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between mt-3 mb-2">
                                    <h5 class="font-weight-normal m-0">{{ __('Show Driver Unique Id in Profile') }}</h5>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input"
                                            id="unique_id_show"
                                            name="unique_id_show"
                                            {{ $preference->unique_id_show == 1 ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="unique_id_show"></label>
                                    </div>
                                </div>
                            </div>
                        </div> 
                    </form>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card-box h-100">
                    <form method="POST" class="h-100" action="{{ route('preference', Auth::user()->code) }}">
                        @csrf
                        <input type="hidden" name="cancel_order_config" value="1">
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h4 class="header-title text-uppercase mb-0">{{ __(getAgentNomenclature()) }} {{__('Registration Documents')}}</h4>
                                    <button class="btn btn-outline-info d-block" id="add_driver_registration_document_modal_btn" type="button"> {{__('Add')}} </button>
                                </div>
                                <div class="table-responsive mt-3 mb-1">
                                    <table class="table table-centered table-nowrap table-striped" id="promo-datatable">
                                        <thead>
                                            <tr>
                                                <th>{{__('Name')}}</th>
                                                <th>{{__('Type')}}</th>
                                                <th>{{__('Required?')}}</th>
                                                <th>{{__('Action')}}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="post_list">
                                            @forelse($agent_docs as $agent_doc)
                                                <tr>
                                                    <td>
                                                        <a class="edit_driver_registration_document_btn"
                                                            data-driver_registration_document_id="{{ $agent_doc->id }}"
                                                            href="javascript:void(0)">
                                                            {{ $agent_doc->name ? $agent_doc->name : '' }}
                                                        </a>
                                                    </td>
                                                    <td>{{ $agent_doc->file_type == 'Pdf' ? 'PDF' : $agent_doc->file_type }}</td>
                                                    <td>{{ $agent_doc->is_required ? 'Yes' : 'No' }}</td>
                                                    <td>
                                                        <div>
                                                            <div class="inner-div" style="float: left;">
                                                                <a class="action-icon edit_driver_registration_document_btn"
                                                                    data-driver_registration_document_id="{{ $agent_doc->id }}"
                                                                    href="javascript:void(0)">
                                                                    <i class="mdi mdi-square-edit-outline"></i>
                                                                </a>
                                                            </div>
                                                            <div class="inner-div">
                                                                <button type="button"
                                                                    class="btn btn-primary-outline action-icon delete_driver_registration_document_btn"
                                                                    data-driver_registration_document_id="{{ $agent_doc->id }}">
                                                                    <i class="mdi mdi-delete"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr align="center">
                                                    <td colspan="4" style="padding: 20px 0">{{__('Result not found.')}}</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card-box h-100">
                    <form method="POST" class="h-100" action="{{ route('preference', Auth::user()->code) }}">
                        @csrf
                        <input type="hidden" name="edit_order_config" value="1">
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                <h4 class="header-title mb-0">{{ __('Refer And Earn(Driver To Customer)') }}</h4>
                                    <button class="btn btn-outline-info d-block" type="submit"> {{__('Save')}} </button>
                                </div>
                                <div class="row">
                                    <div class="col-xl-12 my-2" id="addCur-160">
                                        <label class="primaryCurText">{{ __('Referred To Amount') }}</label>
                                        <input class="form-control" type="number" id="reffered_to_amount"
                                            name="reffered_to_amount"
                                            value="{{ old('reffered_to_amount', $preference->reffered_to_amount ?? '0') }}"
                                            min="0">
                                    </div>
                                    <div class="col-xl-12 mb-2 mt-3" id="addCur-160">
                                        <label class="primaryCurText">{{ __('Referred By Amount') }}</label>
                                        <input class="form-control" type="number" name="reffered_by_amount"
                                            id="reffered_by_amount"
                                            value="{{ old('reffered_by_amount', $preference->reffered_by_amount ?? '0') }}"
                                            min="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @include('rating.rating')
            @include('rating.ratingAttribute')
        </div>

        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <h4 class="page-title">{{__('Miscellaneous')}}</h4>
                </div>
            </div>
        </div>

        <div class="row configure_responsive">
            <div class="col-md-4 mb-3 dashboard-custom-temp">
                <div class="card">
                    <form method="POST" class="h-100" action="{{ route('preference', Auth::user()->code) }}">
                        @csrf
                        <input type="hidden" name="dashboard_mode" value="1">
                        <div class="card-body al_custom_control p-2">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h4 class="header-title text-uppercase mb-0">{{__("Dashboard Home Page Style")}}</h4>
                                <button class="btn btn-outline-info d-block" type="submit"> {{__('Save')}} </button>
                            </div>
                            {{-- @dd($dashboardMode) --}}
                            <div class="row mt-3">
                                <div class="col-xl-4 col-lg-6 col-md-6 mb-3 alThemeDemoSec">
                                    <div class="card mb-0">
                                        <div class="card-body p-0">
                                            <div class="col-sm-12 custom-control custom-radio radio_new p-0">
                                                <input type="radio"  value="0" id="show_dashboard_by_agent_wise_{{ !empty($dashboardMode)? $dashboardMode->show_dashboard_by_agent_wise : 0 }}" name="dashboard_mode[show_dashboard_by_agent_wise]" class="custom-control-input" {{ !empty($dashboardMode) &&$dashboardMode->show_dashboard_by_agent_wise == 0 ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="">
                                                    <span class="card-img-top img-fluid" style="background-image: url({{ asset('/assets/images/Dashboard-Smiile-1.png') }})"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="alTemplateName mt-3 w-100 text-center">Default</span>
                                </div>
                                <div class="col-xl-4 col-lg-6 col-md-6 mb-3 alThemeDemoSec">
                                    <div class="card mb-0">
                                        <div class="card-body p-0">
                                            <div class="col-sm-12 custom-control custom-radio radio_new p-0">
                                                <input type="radio"  value="1" id="show_dashboard_by_agent_wise_{{ !empty($dashboardMode)? $dashboardMode->show_dashboard_by_agent_wise : 0 }}" name="dashboard_mode[show_dashboard_by_agent_wise]" class="custom-control-input" {{ (!empty($dashboardMode) && $dashboardMode->show_dashboard_by_agent_wise == 1) ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="">
                                                    <span class="card-img-top img-fluid" style="background-image: url({{ asset('/assets/images/Dashboard-Smiile-2.png') }})"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="alTemplateName mt-3 w-100 text-center">Agent Dashboard</span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>


            <div class="col-md-4 mb-3">
                <div class="card-box h-100">
                    <form method="POST" class="h-100" action="{{ route('preference', Auth::user()->code) }}">
                        @csrf
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h4 class="header-title mb-0">{{ __('Customer Support') }}</h4>
                            <button class="btn btn-outline-info d-block" type="submit"> {{__('Save')}} </button>
                        </div>
                        <p class="sub-header">
                            {{ __("View and update your Customer Support, it's API key and Application ID") }}
                        </p>
                        <div class="row mb-2">
                            <div class="col-sm-8">
                                <div class="text-sm-left">

                                </div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="currency">{{ __('Customer Support') }}</label>
                                    <select class="form-control" id="customer_support" name="customer_support">
                                        <option value="zen_desk"
                                            {{ isset($preference) && $preference->customer_support == 'zen_desk' ? 'selected' : '' }}>
                                            {{ __('Zen Desk') }}
                                        </option>
                                    </select>
                                    @if ($errors->has('customer_support'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('customer_support') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="map_key_1">{{ __('API Key') }}</label>
                                    <input type="password" name="customer_support_key" id="customer_support_key"
                                        placeholder="{{ __('Please enter key') }}" class="form-control"
                                        value="{{ old('customer_support_key', $preference->customer_support_key ?? '') }}">
                                    @if ($errors->has('customer_support_key'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('customer_support_key') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="customer_support_application_id">{{ __('Application ID') }}</label>
                                    <input type="password" name="customer_support_application_id"
                                        id="customer_support_application_id"
                                        placeholder="{{ __('Please enter application ID') }}" class="form-control"
                                        value="{{ old('customer_support_application_id', $preference->customer_support_application_id ?? '') }}">
                                    @if ($errors->has('customer_support_application_id'))
                                        <span class="text-danger" role="alert">
                                            <strong>{{ $errors->first('customer_support_application_id') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <!-- Custom Mods start -->
                <form method="POST" class="h-100" action="{{ route('preference', Auth::user()->code) }}">
                    @csrf
                    <input type="hidden" name="custom_mode" value="1">
                    <div class="card-box h-100">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h4 class="header-title text-uppercase mb-0">{{__("Custom Mods")}}</h4>
                            <button class="btn btn-outline-info d-block" type="submit"> {{__('Save')}} </button>
                        </div>

                        <div class="row align-items-start custom-mods-rwd">
                            <div class="col-md-12">
                                <div class="form-group d-flex justify-content-between mb-3">
                                <label for="pharmacy_check" class="mr-2 mb-0">{{__("Customer Notification Per Distance")}} <small class="d-block pr-5">{{__('Enable to show customer notification per distance from notifications.')}}</small></label>
                                <div class="d-flex align-items-center justify-content-between mt-3 mb-2">
                                    <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="hide_customer_notification_{{ !empty($customMode->is_hide_customer_notification)? $customMode->is_hide_customer_notification : 0 }}" name="custom_mode[is_hide_customer_notification]" {{ (!empty($customMode->is_hide_customer_notification) && $customMode->is_hide_customer_notification == 1) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="hide_customer_notification_{{ !empty($customMode->is_hide_customer_notification)? $customMode->is_hide_customer_notification : 0 }}"></label>
                                        </div>
                                    </div>
                                </div>

                                @php
                                    $arr = [];
                                    if(isset($customMode->show_vehicle_type_icon)){
                                        $arr = explode(',',$customMode->show_vehicle_type_icon);
                                    }
                                @endphp
                                <div class="form-group d-flex justify-content-between mb-3">
                                    <label for="pharmacy_check" class="mr-2 w-50 mb-0">{{__("Hide Transportation Type Icons")}} <small class="d-block pr-5">{{__('Hide Transportation Type from Signup Form.')}}</small></label>
                                    <div class="col-md p-0 custom-control alMultiSelect">
                                        <select class="selectpickera select2-multiple" data-toggle="select2" multiple="multiple"  data-placeholder="Choose ..."  name="custom_mode[show_vehicle_type_icon][]" multiple data-live-search="true" required>

                                            @foreach($vehicleType as $type)
                                            <option value="{{$type->id}}" @if(isset($arr) && in_array($type->id,$arr)) {{'selected'}} @endif  >{{ucfirst($type->name)}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                            </div>

                            <div class="col-md-12">
                                <div class="form-group d-flex justify-content-between mb-3">
                                <label for="pharmacy_check" class="mr-2 mb-0">{{__("Hide subscription module")}} <small class="d-block pr-5">{{__('It will hide  subscription module from panel.')}}</small></label>
                                <div class="d-flex align-items-center justify-content-between mt-1 mb-2">
                                    <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="hide_subscription_module_{{ !empty($customMode->hide_subscription_module)? $customMode->hide_subscription_module : 0 }}" name="custom_mode[hide_subscription_module]" {{ (!empty($customMode->hide_subscription_module) && $customMode->hide_subscription_module == 1) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="hide_subscription_module_{{ !empty($customMode->hide_subscription_module)? $customMode->hide_subscription_module : 0 }}"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>
                </form>
                <!-- Custom Mods start -->
            </div>

            <div class="col-md-4 mb-3">
                <!-- Custom Mods start -->
                <form method="POST" class="h-100" action="{{ route('preference', Auth::user()->code) }}">
                    @csrf
                    <input type="hidden" name="warehouse_mode" value="1">
                    <div class="card-box h-100">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h4 class="header-title text-uppercase mb-0">{{__("Warehouse")}}</h4>
                            <button class="btn btn-outline-info d-block" type="submit"> {{__('Save')}} </button>
                        </div>

                        <div class="row align-items-start">

                            <div class="col-md-12">
                                <div class="form-group d-flex justify-content-between mb-3">
                                <label for="warehouse_check" class="mr-2 mb-0">{{__("Show warehouse module")}} <small class="d-block pr-5">{{__('It will show  warehouse module from panel.')}}</small></label>
                                <div class="d-flex align-items-center justify-content-between mt-1 mb-2">
                                    <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="show_warehouse_module_{{ !empty($warehoseMode->show_warehouse_module)? $warehoseMode->show_warehouse_module : 0 }}" name="warehouse_mode[show_warehouse_module]" {{ (!empty($warehoseMode->show_warehouse_module) && $warehoseMode->show_warehouse_module == 1) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="show_warehouse_module_{{ !empty($warehoseMode->show_warehouse_module)? $warehoseMode->show_warehouse_module : 0 }}"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group d-flex justify-content-between mb-3">
                                <label for="category_check" class="mr-2 mb-0">{{__("Show category module")}} <small class="d-block pr-5">{{__('It will show  category module from panel.')}}</small></label>
                                <div class="d-flex align-items-center justify-content-between mt-1 mb-2">
                                    <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="show_category_module_{{ !empty($warehoseMode->show_category_module)? $warehoseMode->show_category_module : 0 }}" name="warehouse_mode[show_category_module]" {{ (!empty($warehoseMode->show_category_module) && $warehoseMode->show_category_module == 1) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="show_category_module_{{ !empty($warehoseMode->show_category_module)? $warehoseMode->show_category_module : 0 }}"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </form>
                <!-- Custom Mods start -->
            </div>

            {{-- <div class="col-md-4 mb-3">
                <!-- Custom Mods start -->
                <form method="POST" class="h-100" action="{{ route('preference', Auth::user()->code) }}">
                    @csrf
                    <input type="hidden" name="dashboard_mode" value="1">
                    <div class="card-box h-100">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h4 class="header-title text-uppercase mb-0">{{__("Dashboard Settings")}}</h4>
                            <button class="btn btn-outline-info d-block" type="submit"> {{__('Save')}} </button>
                        </div>

                        <div class="row align-items-start">

                            <div class="col-md-12">
                                <div class="form-group d-flex justify-content-between mb-3">
                                <label for="dashboard_check" class="mr-2 mb-0">{{__("Show dashboard by agent wise")}} <small class="d-block pr-5">{{__('It will show  dashboard by agent wise.')}}</small></label>
                                <div class="d-flex align-items-center justify-content-between mt-1 mb-2">
                                    <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="show_dashboard_by_agent_wise_{{ !empty($dashboardMode->show_dashboard_by_agent_wise)? $dashboardMode->show_dashboard_by_agent_wise : 0 }}" name="dashboard_mode[show_dashboard_by_agent_wise]" {{ (!empty($dashboardMode->show_dashboard_by_agent_wise) && $dashboardMode->show_dashboard_by_agent_wise == 1) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="show_dashboard_by_agent_wise_{{ !empty($dashboardMode->show_dashboard_by_agent_wise)? $dashboardMode->show_dashboard_by_agent_wise : 0 }}"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                <!-- Custom Mods start -->
            </div> --}}
            

            <div class="col-md-4 mb-3">
                <!-- Custom Mods start -->
                <form method="POST" class="h-100" action="{{ route('preference', Auth::user()->code) }}">
                    @csrf
                    <input type="hidden" name="mybatch" value="1">
                    <div class="card-box h-100">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h4 class="header-title text-uppercase mb-0">{{__("Batch Allocation")}}</h4>
                            <button class="btn btn-outline-info d-block" type="submit"> {{__('Save')}} </button>
                        </div>
                        <div class="row align-items-start">
                            <div class="col-md-12">
                                <div class="form-group d-flex justify-content-between mb-3">
                                <label for="" class="mr-2 mb-0">{{__("Enable Batch Allocation")}} </label>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input " id="batch_allocation" name="batch_allocation" {{ (!empty($preference->create_batch_hours) && $preference->create_batch_hours > 0) ? 'checked' :'' }}>
                                            <label class="custom-control-label" for="batch_allocation"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row align-items-start batch-allocation" style="display:{{ (!empty($preference->create_batch_hours) && $preference->create_batch_hours > 0) ? '':'none'}}" >
                            <div class="col-md-12">
                                <div class="form-group d-flex justify-content-between mb-3">
                                <label for="" class="mr-2 mb-0">{{__("Enable this to specify Job consist of pickup or delivery.")}} </label>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input " id="job_consist_of_pickup_or_delivery" name="job_consist_of_pickup_or_delivery" {{ (!empty($preference->job_consist_of_pickup_or_delivery) && $preference->job_consist_of_pickup_or_delivery == 1) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="job_consist_of_pickup_or_delivery"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row align-items-start batch-allocation" style="display:{{ (!empty($preference->create_batch_hours) && $preference->create_batch_hours > 0) ? '':'none'}}" >
                            <div class="col-md-12">
                                <div class="form-group d-flex justify-content-between mb-3">
                                <label for="" class="mr-2 mb-0">{{__("Create batch on every")}} </label>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="custom-control custom-switch">
                                            <select class="form-control" name="create_batch_hours">
                                                <option value="">Select interval to create job</option>
                                                <option value="1" {{($preference->create_batch_hours == 1) ? 'selected' : '' }}>1 Hour</option>
                                                <option value="2" {{($preference->create_batch_hours == 2) ? 'selected' : '' }}>2 Hour</option>
                                                <option value="3" {{($preference->create_batch_hours == 3) ? 'selected' : '' }}>3 Hour</option>
                                                <option value="4" {{($preference->create_batch_hours == 4) ? 'selected' : '' }}>4 Hour</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row align-items-start batch-allocation" style="display:{{ (!empty($preference->create_batch_hours) && $preference->create_batch_hours > 0) ? '':'none'}}" >
                            <div class="col-md-12">
                                <div class="form-group d-flex justify-content-between mb-3">
                                <label for="" class="mr-2 mb-0">{{__("Maximum Route/Job per Geo Fence")}} </label>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="custom-control custom-switch">
                                            <select class="form-control" name="maximum_route_per_job">
                                                <option value="">Select route per job</option>
                                                <option value="2" {{($preference->maximum_route_per_job == 2) ? 'selected' : '' }}>2</option>
                                                <option value="3" {{($preference->maximum_route_per_job == 3) ? 'selected' : '' }}>3</option>
                                                <option value="4" {{($preference->maximum_route_per_job == 4) ? 'selected' : '' }}>4</option>
                                                <option value="5" {{($preference->maximum_route_per_job == 5) ? 'selected' : '' }}>5</option>
                                                <option value="6" {{($preference->maximum_route_per_job == 6) ? 'selected' : '' }}>6</option>
                                                <option value="7" {{($preference->maximum_route_per_job == 7) ? 'selected' : '' }}>7</option>
                                                <option value="8" {{($preference->maximum_route_per_job == 8) ? 'selected' : '' }}>8</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>


                        </div>

                    </div>
                </form>
                <!-- Custom Mods start -->
            </div>

            <div class="col-md-4 mb-3">
                <form method="POST" class="h-100" action="{{ route('preference', Auth::user()->code) }}">
                @csrf
                    <input type="hidden" name="threshold" value="1">
                    <div class="card-box h-100">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h4 class="header-title text-uppercase mb-0">{{__("Threshold")}}</h4>
                            <button class="btn btn-outline-info d-block" type="submit"> {{__('Save')}} </button>
                        </div>
                        <div class="row align-items-start">
                            <div class="col-md-12">
                                <div class="form-group d-flex justify-content-between mb-3">
                                    <label for="enabled-threshold" class="mr-2 mb-0">{{__("Enable Threshold")}} </label>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input " id="is_threshold" name="is_threshold" {{ (!empty($preference->is_threshold) && $preference->is_threshold > 0) ? 'checked' :'' }}>
                                            <label class="custom-control-label" for="is_threshold"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row threshold-section  {{ (!empty($preference->is_threshold) && $preference->is_threshold > 0) ? 'd-block' :'d-none' }}">

                            <div class="col-md-12">
                                <div class="form-group d-block justify-content-between mb-3">
                                    <label for="agent_ids" class="mr-2 mb-0">{{__("Recursive")}} </label>
                                    @if(!empty($preference->threshold_data))
                                        @php 
                                            $threshold_data      =   json_decode($preference->threshold_data,true);
                                            $recursive_type      =   isset($threshold_data['recursive_type']) ? $threshold_data['recursive_type'] : '';
                                            $threshold_amount    =   isset($threshold_data['threshold_amount']) ? $threshold_data['threshold_amount'] : '';
                                            $stripe_connect_id   =   isset($threshold_data['stripe_connect_id']) ? $threshold_data['stripe_connect_id']: '';
                                        @endphp
                                        @else
                                        @php $recursive_type = $threshold_amount = $stripe_connect_id = '' @endphp
                                    @endif
                                    <div class="row mt-2 mb-2">
                                        <div class="col-md-12">
                                            <select name="recursive_type" id="recursive_type" class="form-control" required>
                                                <option value="">{{__("Select Option")}}</option>
                                                <option value="1" {{ (!empty($recursive_type) && $recursive_type == 1) ? 'selected' :'' }}>{{__("Day")}}</option>
                                                <option value="2" {{ (!empty($recursive_type) && $recursive_type == 2) ? 'selected' :'' }}>{{__("Week")}}</option>
                                                <option value="3" {{ (!empty($recursive_type) && $recursive_type == 3) ? 'selected' :'' }}>{{__("Month")}}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-12 {{ (!empty($preference->is_threshold) && $preference->is_threshold > 0) ? 'd-block' :'d-none' }} threshold_amount mt-2">
                                            <input name="threshold_amount" type="text"  data-mini="true"  value="{{ $threshold_amount }}" onKeyPress="return isNumber(event)" class ="form-control" placeholder="{{__("Amount")}}" id="threshold_amount" required />
                                        </div>
                                        <div class="col-md-12 {{ (!empty($preference->is_threshold) && $preference->is_threshold > 0) ? 'd-block' :'d-none' }} threshold_amount mt-2">
                                            <input name="stripe_connect_id" type="text"  data-mini="true"  value="{{ $stripe_connect_id }}" onKeyPress="return isNumber(event)" class ="form-control" placeholder="{{__("Stripe Connect ID")}}" id="stripe_connect_id" required />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
             <div class="col-md-4 mb-3">
                <form method="POST" class="h-100" action="{{ route('preference', Auth::user()->code) }}">
                @csrf
                    <input type="hidden" name="dispatcher_autoallocation" value="1">
                    <div class="card-box h-100">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h4 class="header-title text-uppercase mb-0">{{__("Dispatcher Route Auto Allocation")}}</h4>
                            <button class="btn btn-outline-info d-block" type="submit"> {{__('Save')}} </button>
                        </div>
                        <div class="row align-items-start">
                            <div class="col-md-12">
                                <div class="form-group d-flex justify-content-between mb-3">
                                    <label for="enabled-threshold" class="mr-2 mb-0">{{__("Enable")}} </label>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input " id="is_dispatcher" name="is_dispatcher" {{ (!empty($preference->is_dispatcher_allocation) && $preference->is_dispatcher_allocation > 0) ? 'checked' :'' }}>
                                            <label class="custom-control-label" for="is_dispatcher"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div> 

            <div class="col-md-4 mb-3 d-none">
                <form method="POST" class="h-100" action="{{ route('preference', Auth::user()->code) }}">
                    @csrf
                    <input type="hidden" name="send_to" id="send_to" value="customize">
                    <div class="card-box mb-0 pb-1 h-100">
                        <div class="d-flex align-items-center justify-content-between">
                        <h4 class="header-title">{{ __("Refer and Earn(Driver To Driver)") }}</h4>
                        <button class="btn btn-outline-info d-block" type="submit"> {{ __("Save") }} </button>
                        </div>
                        <div class="col-xl-12 my-2" id="addCur-160">
                        <label class="primaryCurText">{{ __("Referred To Amount") }} </label>
                        <input class="form-control" type="number" id="reffered_to_amount" name="reffered_to_amount" value="{{ $preferenceAdditional['reffered_to_amount'] ?? '' }}" min="0" step="any">
                        </div>
                        <div class="col-xl-12 mb-2 mt-3" id="addCur-160">
                        <label class="primaryCurText">{{ __("Referred By Amount") }} </label>
                        <input class="form-control" type="number" name="reffered_by_amount" id="reffered_by_amount" value="{{ $preferenceAdditional['reffered_by_amount'] ?? '' }}" min="0" step="any">
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-md-4 mb-3 d-none">
                <form method="POST" class="h-100" action="{{ route('preference', Auth::user()->code) }}">
                @csrf
                    <input type="hidden" name="route_optimize" value="1">
                    <div class="card-box h-100">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h4 class="header-title text-uppercase mb-0">{{__("Route Optimization")}}</h4>
                            <button class="btn btn-outline-info d-block" type="submit"> {{__('Save')}} </button>
                        </div>
                        <div class="row align-items-start">
                            <div class="col-md-12">
                                <div class="form-group d-flex justify-content-between mb-3">
                                    <label for="route_optimization" class="mr-2 mb-0">{{__("Enable")}} </label>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input " id="route_optimization" name="route_optimization" {{ (!empty($preference->route_optimization) && $preference->route_optimization > 0) ? 'checked' :'' }}>
                                            <label class="custom-control-label" for="route_optimization"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div> 
            <div class="col-md-4 mb-3">
                <form method="POST" class="h-100" action="{{ route('preference', Auth::user()->code) }}">
                @csrf
                    <input type="hidden" name="is_lumen" value="1">
                    <div class="card-box h-100">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h4 class="header-title text-uppercase mb-0">{{__("Lumen")}}</h4>
                            <button class="btn btn-outline-info d-block" type="submit"> {{__('Save')}} </button>
                        </div>
                        <div class="row align-items-start">
                            <div class="col-md-12">
                                <div class="form-group d-flex justify-content-between mb-3">
                                    <label for="lumen" class="mr-2 mb-0">{{__("Enable")}} </label>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input " id="is_lumen_enabled" name="is_lumen_enabled">
                                            <label class="custom-control-label" for="is_lumen_enabled"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </div>
                            <div class="row lumen-field" style="display: {{ (!empty($preference->is_lumen_enabled) && $preference->is_lumen_enabled > 0) ? 'block' :'none' }}">
                    <div class="col-12 ">
                        <div class="form-group mb-3">
                            <div class="domain-outer border-0 d-flex align-items-center justify-content-between">
                                <label for="lumen_domain_url">{{ __('LUMEN DOMAIN URL') }}</label>

                            </div>
                            <input type="text" name="lumen_domain_url" id="lumen_domain_url"
                                placeholder="" class="form-control"
                                value="{{ old('lumen_domain_url', $preference->lumen_domain_url ?? '') }}">
                            @if ($errors->has('lumen_domain_url'))
                                <span class="text-danger" role="alert">
                                    <strong>{{ $errors->first('lumen_domain_url') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>
                            </div>
                            <div class="row lumen-field" style="display: {{ (!empty($preference->is_lumen_enabled) && $preference->is_lumen_enabled > 0) ? 'block' :'none' }}"">

                    <div class="col-12">
                        <div class="form-group mb-3">
                            <div class="domain-outer border-0 d-flex align-items-center justify-content-between">
                                <label for="lumen_access_token">{{ __('LUMEN ACCESS TOKEN') }}</label>
                                <span class="text-right col-6 col-md-6"><a
                                        href="javascript: generateLumenToken();">{{ __('Generate Key') }}</a></span>

                            </div>
                            <input type="text" name="lumen_access_token" id="lumen_access_token"
                                placeholder="kjadsasd66asdas" class="form-control"
                                value="{{ old('lumen_access_token', $preference->lumen_access_token ?? '') }}">
                            @if ($errors->has('lumen_access_token'))
                                <span class="text-danger" role="alert">
                                    <strong>{{ $errors->first('lumen_access_token') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>
                    </div>
           
                    </div>
                </form>
            </div> 
        </div>


        <div style="display:none;">
            <form method="POST" action="{{ route('preference', Auth::user()->code) }}">
                @csrf
                <div class="row">
                    <div class="col-xl-11 col-md-offset-1">
                        <div class="card-box">
                            <h4 class="header-title">{{ __('Email') }}</h4>
                            <p class="sub-header">
                                {{ __("Choose Email paid plan to whitelable 'From email address' and 'Sender Name' in the Email sent out from your account.") }}
                            </p>
                            <div class="row mb-0">

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email_plan">{{ __('CURRENT SELECTION') }}</label>
                                        <select class="form-control" id="email_plan" name="email_plan">
                                            <option>{{ __('Select Plan') }}</option>
                                            <option value="free"
                                                {{ isset($preference) && $preference->email_plan == 'free' ? 'selected' : '' }}>
                                                {{ __('Free') }}
                                            </option>
                                            <option value="paid"
                                                {{ isset($preference) && $preference->email_plan == 'paid' ? 'selected' : '' }}>
                                                {{ __('Paid') }}
                                            </option>
                                        </select>
                                        @if ($errors->has('email_plan'))
                                            <span class="text-danger" role="alert">
                                                <strong>{{ $errors->first('email_plan') }}</strong>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <div class="form-group mb-3">
                                        <label for="sms_service_api_key">{{ __('PREVIEW') }}</label>
                                        <div class="card">
                                            <div class="card-body">

                                                <p class="mb-2"><span
                                                        class="font-weight-semibold mr-2">From:</span>
                                                    johndoe<span>
                                                        < </span>contact@royodispatcher.com<span>></span>
                                                </p>
                                                <p class="mb-2"><span class="font-weight-semibold mr-2">Reply
                                                        To:</span>
                                                    johndoe@gmail.com</p>

                                                <p class="mt-3 text-center">
                                                    {{ __('Your message here') }}..
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-2">
                                <div class="col-md-2">
                                    <div class="form-group mb-0 text-center">
                                        <button class="btn btn-blue btn-block" type="submit"> {{ __('Update') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>


        

        <!-- end page title -->
        {{-- <div class="row">
        <div class="col-11">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-sm-8">
                            <div class="text-sm-left">
                                @if (\Session::has('success'))
                                    <div class="alert alert-success">
                                        <span>{!! \Session::get('success') !!}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-sm-4 text-right">
                            <button type="button" class="btn btn-blue waves-effect waves-light sub-client_modal" data-toggle="modal" data-target="#add-sub-client-modal" data-backdrop="static" data-keyboard="false" data-backdrop="static" data-keyboard="false"><i class="mdi mdi-plus-circle mr-1"></i> Add Client</button>
                        </div>

                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped dt-responsive nowrap w-100" id="agents-datatable">
                            <thead>
                                <tr>
                                    <th>Uid</th>
                                    <th>Name</th>
                                    <th>email</th>
                                    <th>phone_number</th>
                                    <th>status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach ($subClients as $subClient)
                                <tr>

                                    <td>
                                        {{ $subClient->uid }}
    </td>
    <td>
        {{ $subClient->name }}
    </td>
    <td>
        {{ $subClient->email }}
    </td>
    <td>
        {{ $subClient->phone_number }}
    </td>
    <td>
        {{ $subClient->status == 1 ? 'Active' :'In-active' }}
    </td>


    <td>
        <div class="form-ul" style="width: 60px;">
            <div class="inner-div"> <a href="{{ route('subclient.edit', $subClient->id) }}" class="action-icon editIcon"> <i class="mdi mdi-square-edit-outline"></i></a></div>
            <div class="inner-div">
                <form method="POST" action="{{ route('subclient.destroy', $subClient->id) }}">
                    @csrf
                    @method('DELETE')
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary-outline action-icon"> <i class="mdi mdi-delete"></i></button>

                    </div>
                </form>
            </div>
        </div>


    </td>
    </tr>
    @endforeach
    </tbody>
    </table>
</div>

</div> <!-- end card-body-->
</div> <!-- end card-->
</div> <!-- end col -->
</div> --}}

        <div id="add_driver_registration_document_modal" class="modal fade" tabindex="-1" role="dialog"
            aria-labelledby="standard-modalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-bottom">
                        <h4 class="modal-title" id="standard-modalLabel">Add {{ __(getAgentNomenclature()) }} Registration Document</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    </div>
                    <div class="modal-body">
                        <form id="driverRegistrationDocumentForm" method="POST" action="javascript:void(0)">
                            @csrf
                            <div id="save_social_media">
                                <input type="hidden" name="driver_registration_document_id" value="">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group position-relative">
                                            <label for="">Type</label>
                                            <div class="input-group mb-2">
                                                <select class="form-control" name="file_type" id="file_type_select">
                                                    <option value="Image">Image</option>
                                                    <option value="Pdf">PDF</option>
                                                    <option value="Text">Text</option>
                                                    <option value="Date">Date</option>
                                                    <option value="selector">selector</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="form-group position-relative">
                                                    <label for="">{{ __('Name') }}</label>
                                                    <input class="form-control" name="name" type="text"
                                                        id="driver_registration_document_name">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="selector_div" class="col-md-12 d-none">
                                        <div class="card">
                                            <div class="card-box mb-0 ">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <h4 class="header-title text-uppercase">{{ __('Options') }}</h4>
                                                </div>
                                                <div id="option_div">

                                                    <div class="">
                                                        <div class="col-lg-12">
                                                            <div id="opt_val_div">
                                                                <div id="row" class="edit_option">
                                                                    <div class="input-group m-3">
                                                                        <div class="input-group-prepend">
                                                                            <button class="btn btn-danger" id="DeleteRow"
                                                                                type="button">
                                                                                <i class="bi bi-trash"></i>
                                                                                Delete
                                                                            </button>
                                                                        </div>
                                                                        <input type="text" name="option_name[]" class="form-control m-input">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div id="newinput"></div>
                                                            <button id="rowAdder" type="button"
                                                                class="btn btn-dark">
                                                                <span class="bi bi-plus-square-dotted">
                                                                </span> ADD
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12 mb-2">
                                        <label for="">{{ __('Required?') }} </label>
                                        <div class="custom-switch redio-all">
                                            <input type="checkbox" value="1"
                                                class="custom-control-input alcoholic_item large-icon"
                                                id="required_checkbox" name="is_required">
                                            <label class="custom-control-label" for="required_checkbox"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary submitSaveDriverRegistrationDocument">{{__('Save')}}</button>
                    </div>
                </div>
            </div>
        </div>

        
        @include('rating.ratingModel')

        @include('rating.ratingAttributeModel')

    </div> <!-- container -->
    @include('modals.add-sub-client')
@endsection

@section('script')
    <script src="{{ asset('assets/libs/dropzone/dropzone.min.js') }}"></script>
    <script src="{{ asset('assets/libs/dropify/dropify.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/form-fileuploads.init.js') }}"></script>
    <script src="{{ asset('assets/libs/datatables/datatables.min.js') }}"></script>
    <script src="{{ asset('assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script type="text/javascript">
        function toggleDisplayCustomDomain() {
            $("#custom_domain_name").toggle('fast', function() {

            });
        }


        $('#add_driver_registration_document_modal_btn').click(function(e) {
            document.getElementById("driverRegistrationDocumentForm").reset();
            $('#add_driver_registration_document_modal input[name=driver_registration_document_id]').val("");
            $('#add_driver_registration_document_modal').modal('show');
            $('#add_driver_registration_document_modal #standard-modalLabel').html(
                'Add {{getAgentNomenclature()}} Registration Document');
        });
        $(document).on("click", ".delete_driver_registration_document_btn", function() {
            var driver_registration_document_id = $(this).data('driver_registration_document_id');
            if (confirm('Are you sure?')) {
                $.ajax({
                    type: "POST",
                    dataType: 'json',
                    url: "{{ route('driver.registration.document.delete') }}",
                    data: {
                        _token: "{{ csrf_token() }}",
                        driver_registration_document_id: driver_registration_document_id
                    },
                    success: function(response) {
                        if (response.status == "Success") {
                            $.NotificationApp.send("Success", response.message, "top-right", "#5ba035",
                                "success");
                            setTimeout(function() {
                                location.reload()
                            }, 2000);
                        }
                    }
                });
            }
        });
        $(document).on("change", "#file_type_select", function() {
            var file_type = $(this).val();
            if (file_type == 'selector') {
                $("#selector_div").removeClass("d-none");
                var classoption_section = $('#option_div').find('.option_section');
                if (classoption_section.length == 0) {
                    //addoptionTemplate(0);
                }
            } else {
                $("#selector_div").addClass("d-none");
            }
        });

        function addoptionTemplate(section_id) {
            section_id = parseInt(section_id);
            section_id = section_id + 1;
            var data = '';

            var price_section_temp = $('#vendorSelectorTemp').html();
            var modified_temp = _.template(price_section_temp);
            var result_html = modified_temp({
                id: section_id,
                data: data
            });
            $("#vendor-selector-datatable #table_body").append(result_html);
            $('.add_more_button').hide();
            $('#vendor-selector-datatable #add_button_' + section_id).show();
        }
        var i=0;
        $("#rowAdder").click(function() {
            var c=i++;
            newRowAdd =
                '<div id="row"> <div class="input-group m-3">' +
                '<div class="input-group-prepend">' +
                '<button class="btn btn-danger" id="DeleteRow" type="button">' +
                '<i class="bi bi-trash"></i> Delete</button> </div>' +
                '<input type="text" name="option_name[]" class="form-control m-input_'+c+'"> </div> </div>';

            $('#newinput').append(newRowAdd);
        });
        $("body").on("click", "#DeleteRow", function() {
            $(this).parents("#row").remove();
        })
        $(document).on('click', '.submitSaveDriverRegistrationDocument', function(e) {
            var driver_registration_document_id = $(
                "#add_driver_registration_document_modal input[name=driver_registration_document_id]").val();
            if (driver_registration_document_id) {
                var post_url = "{{ route('driver.registration.document.update') }}";
            } else {
                var post_url = "{{ route('driver.registration.document.create') }}";
            }
            var form_data = new FormData(document.getElementById("driverRegistrationDocumentForm"));
            $.ajax({
                url: post_url,
                method: 'POST',
                data: form_data,
                contentType: false,
                processData: false,
                success: function(response) {
                    if (response.status == 'Success') {
                        $('#add_or_edit_social_media_modal').modal('hide');
                        $.NotificationApp.send("Success", response.message, "top-right", "#5ba035",
                            "success");
                        setTimeout(function() {
                            location.reload()
                        }, 2000);
                    } else {
                        $.NotificationApp.send("Error", response.message, "top-right", "#ab0535",
                            "error");
                    }
                },
                error: function(response) {
                    $('#add_driver_registration_document_modal .social_media_url_err').html(
                        'The default language name field is required.');
                }
            });
        });
        $(document).on("click", ".edit_driver_registration_document_btn", function() {
            let driver_registration_document_id = $(this).data('driver_registration_document_id');
            $('#add_driver_registration_document_modal input[name=driver_registration_document_id]').val(
                driver_registration_document_id);
            $.ajax({
                method: 'GET',
                data: {
                    driver_registration_document_id: driver_registration_document_id
                },
                url: "{{ route('driver.registration.document.edit') }}",
                success: function(response) {
                    console.log(response.data.driver_option);
                    var dynamicOption = '';
                    if (response.status = 'Success') {
                        $("#add_driver_registration_document_modal select[name=file_type]").val(response
                            .data.file_type).change();
                        $("#add_driver_registration_document_modal input[name=name]").val(response.data
                            .name);
                        if (response.data.is_required) {
                            $("#add_driver_registration_document_modal input[name=is_required]").prop(
                                "checked", "checked");
                        } else {
                            $("#add_driver_registration_document_modal input[name=is_required]").prop(
                                "checked", false);
                        }

                        if(response.data.file_type=='selector'){
                            $('#selector_div').removeClass('d-none');
                            $.each(response.data.driver_option, function(i, option){
                                dynamicOption +=  "<div id='row"+option.id+"'><div class='input-group m-3'><div class='input-group-prepend'><button class='btn btn-danger' id='DeleteRow"+option.id+"' type='button'><i class='bi bi-trash'></i>Delete</button></div><input type='text' name='option_name[]' value='"+option.driver_registartion_option_name+"' class='form-control m-input'></div></div>";
                                $('body').on('click', '#DeleteRow'+option.id, function() {
                                    $(this).parents('#row'+option.id).remove(); 
                                });
                            });
                        }
                        $('#opt_val_div').html(dynamicOption);
                        $('#add_driver_registration_document_modal #standard-modalLabel').html(
                            'Update {{getAgentNomenclature()}} Registration Document');
                        $('#add_driver_registration_document_modal').modal('show');
                    }
                },
                error: function() {

                }
            });
        });

        function generateRandomString(length) {
            var text = "";
            var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

            for (var i = 0; i < length; i++)
                text += possible.charAt(Math.floor(Math.random() * possible.length));

            return text;
        }

        function genrateKeyAndToken() {
            var key = generateRandomString(30);
            var token = generateRandomString(60);

            $('#personal_access_token_v1').val(key);
            $('#personal_access_token_v2').val(token);
        }
        function generateLumenToken() {
            var token = generateRandomString(30);

            $('#lumen_access_token').val(token);
        }

        $(document).ready(function() {
            smsChange();
        });

        function toggle_smsFields(obj) {
            smsChange();
            // var id = $(obj).find(':selected').attr('data-id');
            // $('.sms_fields').css('display','none');
            // $('#'+id).css('display','flex');
            // console.log(id);
        }

        function smsChange() {
            var id = $("#sms_provider").find(':selected').attr('data-id');
            $('.sms_fields').css('display', 'none');
            $('#' + id).css('display', 'flex');

        }
        $('#batch_allocation').on('change',function(){
            if ($(this).is(":checked")) {
                $('.batch-allocation').show();
            }else{
                $('.batch-allocation').hide();
            }
        });
        $('#is_lumen_enabled').on('change',function(){

            if ($(this).is(":checked")) {
                $('.lumen-field').show();
            }else{
                $('.lumen-field').hide();
            }
        });

        $('#is_threshold').on('change',function(){
            if ($(this).is(":checked")) {
                $('.threshold-section').removeClass('d-none').addClass('d-block');
            }else{
                $('.threshold-section').removeClass('d-block').addClass('d-none');
            }
        });
        $('#is_dispatcher').on('change',function(){
            if ($(this).is(":checked")) {
                $('.dispatcher-section').removeClass('d-none').addClass('d-block');
            }else{
                $('.dispatcher-section').removeClass('d-block').addClass('d-none');
            }
        });

        $('#recursive_type').on('change',function(){
            $(document).find('.threshold_amount').removeClass('d-none').addClass('d-block');
        });

        


        $('#toll_fee').on('change',function(){
            if ($(this).is(":checked")) {
                $('.toll_fee').show();
            }else{
                $('.toll_fee').hide();
            }
        });


        function isNumber(evt) {
            evt = (evt) ? evt : window.event;
            var charCode = (evt.which) ? evt.which : evt.keyCode;
            if (charCode > 31 && (charCode < 46 || charCode > 57)) {
                return false;
            }
            return true;
        }
        $('#editCabPoolingSwitch').on('change',function(){
            if ($(this).is(":checked")) {
                $('#radius_for_pooling_div').show();
            }else{
                $('#radius_for_pooling_div').hide();
            }
        });
        $('#is_go_to_home').on('change',function(){
            if ($(this).is(":checked")) {
                $('#go_to_home_radians').show();
            }else{
                $('#go_to_home_radians').hide();
            }
        });
    </script>
<script src="{{ asset('assets/js/rating/rating.js')}}"></script>
@endsection