@extends('layouts.vertical', ['title' => 'Customize']) @section('css')
@endsection @php $getAdditionalPreference =
getAdditionalPreference(['pickup_type',
'drop_type','is_attendence','idle_time','hold_to_start','hold_to_arrive','hold_to_pick','hold_to_complete']); @endphp @section('content')
@include('modals.tandc') @include('modals.privacyandpolicy')

<!-- Start Content-->
<div class="container-fluid">

	@if (\Session::has('success'))
	<div class="row">
		<div class="col-11">
			<div class="text-sm-left">

				<div class="alert alert-success">
					<span>{!! \Session::get('success') !!}</span>
				</div>

			</div>
		</div>
	</div>
	@endif
	<!-- start Section title -->
	<div class="row nomencla_rwd">
		<div class="col-12">
			<div class="page-title-box">
				<h4 class="page-title">{{__("Nomenclature & Localisation")}}</h4>
			</div>
		</div>
	</div>
	<!-- end Section title -->
	<div class="row mb-3">
		<div class="col-md-5 col-xl-4">
			<div class="card-box h-100">
				<div class="row">
					<div class="col-md-12">
						<form method="POST" class="h-100"
							action="{{route('preference', Auth::user()->code)}}">
							@csrf
							<div
								class="d-flex align-items-center justify-content-between mb-2">
								<h4 class="header-title mb-0">{{__("Nomenclature")}}</h4>
								<button class="btn btn-outline-info d-block" type="submit">
									{{__('Save')}}</button>
							</div>
							<p class="sub-header">{{__("View and update the naming, currency
								and distance units.")}}</p>
							<div class="row mb-2">
								<div class="col-md-6">
									<div class="form-group mb-3">
										<label for="agent_type">{{__(strtoupper(getAgentNomenclature())."
											NAME")}}</label> <input type="text" name="agent_name"
											id="agent_type"
											placeholder="e.g {{ __(getAgentNomenclature())}}"
											class="form-control"
											value="{{ old('agent_type', $preference->agent_name ?? '')}}">
										@if($errors->has('agent_name')) <span class="text-danger"
											role="alert"> <strong>{{ $errors->first('agent_name') }}</strong>
										</span> @endif
									</div>
								</div>
								<div class="col-md-6">
									<div class="form-group mb-3">
										<label for="currency">{{__("CURRENCY")}}</label> <select
											class="form-control" id="currency" name="currency_id">
											@foreach($currencies as $currency)
											<option value="{{ $currency->id }}"
												{{ ($preference && $preference->currency_id ==
												$currency->id)? "selected" : "" }}>{{ $currency->iso_code }}
												- {{ $currency->symbol }}</option> @endforeach
										</select> @if($errors->has('currency_id')) <span
											class="text-danger" role="alert"> <strong>{{
												$errors->first('currency_id') }}</strong>
										</span> @endif
									</div>
								</div>
							</div>
							<div class="row mb-2">
								<div class="col-md-6">
									<div class="form-group mb-3">
										<label for="pickup_type">{{__("PICKUP NAME")}}</label> <input
											type="text" name="pickup_type" id="pickup_type"
											placeholder="e.g {{ __('PICKUP NAME')}}" class="form-control"
											value="{{ old('pickup_type', $getAdditionalPreference['pickup_type'] ?? '')}}">
										@if($errors->has('pickup_type')) <span class="text-danger"
											role="alert"> <strong>{{ $errors->first('pickup_type') }}</strong>
										</span> @endif
									</div>
								</div>
								<div class="col-md-6">
									<div class="form-group mb-3">
										<label for="drop_type">{{__("DROP OFF")}}</label> <input
											type="text" name="drop_type" id="drop_type"
											placeholder="e.g {{ __('DROP OFF')}}" class="form-control"
											value="{{ old('drop_type', $getAdditionalPreference['drop_type'] ?? '')}}">
										@if($errors->has('drop_type')) <span class="text-danger"
											role="alert"> <strong>{{ $errors->first('drop_type') }}</strong>
										</span> @endif
									</div>
								</div>
							</div>
							<div class="row mb-2">
								<div class="col-md-6">
									<div class="form-group mb-3">
										<label for="pickup_type">{{__("Hold to Start")}}</label> <input
											type="text" name="hold_to_start" id="hold_to_start"
											placeholder="e.g {{ __('Hold to Start')}}" class="form-control"
											value="{{ old('hold_to_start', $getAdditionalPreference['hold_to_start'] ?? '')}}">
										@if($errors->has('hold_to_start')) <span class="text-danger"
											role="alert"> <strong>{{ $errors->first('hold_to_start') }}</strong>
										</span> @endif
									</div>
								</div>
								<div class="col-md-6">
									<div class="form-group mb-3">
										<label for="drop_type">{{__("Hold to Arrive")}}</label> <input
											type="text" name="hold_to_arrive" id="hold_to_arrive"
											placeholder="e.g {{ __('Hold to Arrive')}}" class="form-control"
											value="{{ old('hold_to_arrive', $getAdditionalPreference['hold_to_arrive'] ?? '')}}">
										@if($errors->has('hold_to_arrive')) <span class="text-danger"
											role="alert"> <strong>{{ $errors->first('hold_to_arrive') }}</strong>
										</span> @endif
									</div>
								</div>
							</div>
							<div class="row mb-2">
								<div class="col-md-6">
									<div class="form-group mb-3">
										<label for="pickup_type">{{__("Hold to Pick")}}</label> <input
											type="text" name="hold_to_pick" id="hold_to_pick"
											placeholder="e.g {{ __('Hold to Pick')}}" class="form-control"
											value="{{ old('hold_to_pick', $getAdditionalPreference['hold_to_pick'] ?? '')}}">
										@if($errors->has('hold_to_pick')) <span class="text-danger"
											role="alert"> <strong>{{ $errors->first('hold_to_pick') }}</strong>
										</span> @endif
									</div>
								</div>
								<div class="col-md-6">
									<div class="form-group mb-3">
										<label for="hold_to_complete">{{__("Hold to Complete")}}</label> <input
											type="text" name="hold_to_complete" id="hold_to_complete"
											placeholder="e.g {{ __('Hold to Complete')}}" class="form-control"
											value="{{ old('hold_to_complete', $getAdditionalPreference['hold_to_complete'] ?? '')}}">
										@if($errors->has('hold_to_complete')) <span class="text-danger"
											role="alert"> <strong>{{ $errors->first('hold_to_complete') }}</strong>
										</span> @endif
									</div>
								</div>
							</div>

							<div class="row mb-2">
								<div class="col-md-12">
									<label>{{__('Unit')}}</label>
									<div class="col-sm-12">
										<div class="radio radio-info form-check-inline">
											<input type="radio" id="metric" value="metric"
												name="distance_unit" {{ ($preference && $preference->distance_unit
											=="metric")? "checked" : "" }}> <label for="metric">
												{{__("Metric")}}</label>
										</div>
										<div class="radio form-check-inline">
											<input type="radio" id="imperial" value="imperial"
												name="distance_unit" {{ ($preference && $preference->distance_unit
											=="imperial")? "checked" : "" }}> <label for="imperial">
												{{__("Imperial")}}</label>
										</div>
										@if($errors->has('distance_unit')) <span class="text-danger"
											role="alert"> <strong>{{ $errors->first('distance_unit') }}</strong>
										</span> @endif
									</div>
								</div>
							</div>

							<div class="row mb-2">

								<div class="col-md-6">
						<label>{{__('Attendence')}}</label>
<div class="col-sm-12">
										<div class="radio radio-info form-check-inline">
											<input type="radio" id="is_attendence_1" value="1"
												name="is_attendence" {{ ($getAdditionalPreference['is_attendence'] ==
											1)? "checked" : "" }}> <label for="is_attendence_1">
												{{__("On")}}</label>
										</div>
										<div class="radio form-check-inline">
											<input type="radio" id="is_attendence_0" value="0"
												name="is_attendence" {{ ($getAdditionalPreference['is_attendence'] ==
											0)? "checked" : "" }}> <label for="is_attendence_0">
												{{__("Off")}}</label>
										</div>
										@if($errors->has('is_attendence')) <span class="text-danger"
											role="alert"> <strong>{{ $errors->first('is_attendence') }}</strong>
										</span> @endif
									</div>
									</div>
								<div class="col-md-6">
									<div class="form-group mb-3">
										<label for="drop_type">{{__("Idle Time (in Hours)")}}</label> <input
											type="number" name="idle_time" id="idle_time"
											placeholder="e.g {{ __('Idle Time')}}" class="form-control" min="1" max="20"
											value="{{ old('idle_time', $getAdditionalPreference['idle_time'] ?? '')}}">
										@if($errors->has('idle_time')) <span class="text-danger"
											role="alert"> <strong>{{ $errors->first('idle_time') }}</strong>
										</span> @endif
									</div>
								</div>
							</div>

						</form>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<form method="POST"
							action="{{route('preference',Auth::user()->code)}}">
							@csrf
							<div
								class="d-flex align-items-center justify-content-between mb-2">
								<h4 class="header-title mb-0">{{__("Date & Time")}}</h4>
								<button class="btn btn-outline-info d-block" type="submit">
									{{__('Save')}}</button>
							</div>
							<p class="sub-header">{{__("View and update the date & time
								format.")}}</p>
							<div class="row">
								<div class="col-md-6">
									<div class="form-group">
										<label for="date_format">{{__("DATE FORMAT")}}</label> <select
											class="form-control" id="date_format" name="date_format">
											<option value="m/d/Y" {{ ($preference && $preference->date_format
												=="m/d/Y")? "selected" : "" }}> MM/DD/YYYY</option>
											<option value="d-m-Y" {{ ($preference && $preference->date_format
												=="d-m-Y")? "selected" : "" }}> DD-MM-YYYY</option>
											<option value="d/m/Y" {{ ($preference && $preference->date_format
												=="d/m/Y")? "selected" : "" }}> DD/MM/YYYY</option>
											<option value="Y-m-d" {{ ($preference && $preference->date_format
												=="Y-m-d")? "selected" : "" }}> YYYY-MM-DD</option>
										</select> @if($errors->has('date_format')) <span
											class="text-danger" role="alert"> <strong>{{
												$errors->first('date_format') }}</strong>
										</span> @endif
									</div>
								</div>
								<div class="col-md-6">
									<div class="form-group">
										<label for="time_format">{{__("TIME FORMAT")}}</label> <select
											class="form-control" id="time_format" name="time_format">
											<option value="12" {{ ($preference && $preference->time_format
												=="12")? "selected" : "" }}>12 {{__("hours")}}</option>
											<option value="24" {{ ($preference && $preference->time_format
												=="24")? "selected" : "" }}>24 {{__("hours")}}</option>
										</select> @if($errors->has('time_format')) <span
											class="text-danger" role="alert"> <strong>{{
												$errors->first('time_format') }}</strong>
										</span> @endif
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>

		<div class="col-md-4 col-xl-4">
			<div class="card-box h-100">
				<form method="POST" class="h-100"
					action="{{route('preference', Auth::user()->code)}}">
					@csrf <input type="hidden" name="address_limit_order_config"
						value="1">
					<div class="d-flex align-items-center justify-content-between mb-2">
						<h4 class="header-title mb-0">{{__("Saved Address selection")}}</h4>
						<button class="btn btn-outline-info d-block" type="submit">
							{{__('Save')}}</button>
					</div>
					<p class="sub-header">{{__("Manage how you want to show saved
						addresses while creating routes.")}}</p>
					<div class="row mb-2">
						<div class="col-sm-12">
							<div class="radio radio-info form-check-inline mb-2">
								<input type="radio" id="all_contact" value="1"
									name="allow_all_location"
									{{ (isset($preference) && $preference->allow_all_location ==1)?
								"checked" : "" }}> <label for="all_contact"> {{__("Shared saved
									addresses for all customers")}} </label>
							</div>
							<div class="radio form-check-inline mb-2">
								<input type="radio" id="my_contact" value="0"
									name="allow_all_location"
									{{ (isset($preference) &&  $preference->allow_all_location
								==0)? "checked" : "" }}> <label for="my_contact"> {{__("Saved
									addresses linked to each customer")}} </label>
							</div>
							@if($errors->has('allow_all_location')) <span class="text-danger"
								role="alert"> <strong>{{ $errors->first('allow_all_location') }}</strong>
							</span> @endif
							<hr>
							<h4 class="header-title">{{__("Show Limited Address")}}</h4>

							<div class="custom-control custom-switch">
								<input type="checkbox" class="custom-control-input event_type"
									id="show_limited_address" name="show_limited_address"
									{{isset($preference) && $preference->show_limited_address == 1
								? 'checked':''}}> <label class="custom-control-label"
									for="show_limited_address">{{__("Show only first 5 address")}}</label>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
		<div class="col-md-3 col-xl-4">
			<div class="card-box h-100">
				<form method="POST" class="h-100"
					action="{{route('update.contact.us', Auth::user()->code)}}">
					<div class="d-flex align-items-center justify-content-between mb-2">
						<h4 class="header-title mb-0">{{__('Contact Us')}}</h4>
						<button class="btn btn-outline-info d-block" type="submit">
							{{__('Save')}}</button>
					</div>

					@csrf
					<div class="row">
						<div class="col-md-12">
							<div class="form-group mb-0">
								<label for="contact_address">{{__('Address')}}</label>
								<div class="input-group">
									<input type="text" name="contact_address" id="contact_address"
										class="form-control"
										value="{{ old('contact_address', $clientContact->contact_address ?? '')}}">
								</div>
								@if($errors->has('contact_address')) <span class="text-danger"
									role="alert"> <strong>{{ $errors->first('contact_address') }}</strong>
								</span> @endif
							</div>
							<div class="form-group mt-2 mb-0">
								<label for="contact_phone_number">{{__('Number')}}</label> <input
									type="text" name="contact_phone_number"
									id="contact_phone_number" placeholder="" class="form-control"
									value="{{ old('contact_phone_number', $clientContact->contact_phone_number ?? '')}}">
								@if($errors->has('contact_phone_number')) <span
									class="text-danger" role="alert"> <strong>{{
										$errors->first('contact_phone_number') }}</strong>
								</span> @endif
							</div>
							<div class="form-group mt-2 mb-0">
								<label for="contact_email">{{__('Email')}}</label> <input
									type="text" name="contact_email" id="contact_email"
									placeholder="" class="form-control"
									value="{{ old('contact_email', $clientContact->contact_email ?? '')}}">
								@if($errors->has('contact_email')) <span class="text-danger"
									role="alert"> <strong>{{ $errors->first('contact_email') }}</strong>
								</span> @endif
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>

		{{--
		<div class="col-md-3">
			<div class="card-box h-100">
				<form method="POST" class="h-100"
					action="{{route('update.orderPanelDbDetail')}}">
					<div class="d-flex align-items-center justify-content-between mb-2">
						<h4 class="header-title mb-0">{{__('Oeder Panel DB Detail')}}</h4>
						<button class="btn btn-outline-info d-block" type="submit">Save</button>
					</div>

					@csrf
					<div class="row">
						<div class="col-md-12">
							<div class="form-group mb-0">
								<label for="db_host">DB Host</label>
								<div class="input-group">
									<input type="text" name="db_host" id="db_host"
										class="form-control"
										value="{{ old('db_host', $order_panel_detail->db_host ?? '')}}">
								</div>
								@if($errors->has('db_host')) <span class="text-danger"
									role="alert"> <strong>{{ $errors->first('db_host') }}</strong>
								</span> @endif
							</div>
							<div class="form-group mt-2 mb-0">
								<label for="db_port">DB Port</label> <input type="text"
									name="db_port" id="db_port" placeholder="" class="form-control"
									value="{{ old('db_port', $order_panel_detail->db_port ?? '')}}">
								@if($errors->has('db_port')) <span class="text-danger"
									role="alert"> <strong>{{ $errors->first('db_port') }}</strong>
								</span> @endif
							</div>
							<div class="form-group mt-2 mb-0">
								<label for="db_name">DB Name</label> <input type="text"
									name="db_name" id="db_name" placeholder="" class="form-control"
									value="{{ old('db_name', $order_panel_detail->db_name ?? '')}}">
								@if($errors->has('db_name')) <span class="text-danger"
									role="alert"> <strong>{{ $errors->first('db_name') }}</strong>
								</span> @endif
							</div>
							<div class="form-group mt-2 mb-0">
								<label for="db_username">DB Username</label> <input type="text"
									name="db_username" id="db_username" placeholder=""
									class="form-control"
									value="{{ old('db_username', $order_panel_detail->db_username ?? '')}}">
								@if($errors->has('db_username')) <span class="text-danger"
									role="alert"> <strong>{{ $errors->first('db_username') }}</strong>
								</span> @endif
							</div>
							<div class="form-group mt-2 mb-0">
								<label for="db_password">DB Password</label> <input
									type="password" name="db_password" id="db_password"
									placeholder="" class="form-control" value="">
								@if($errors->has('db_password')) <span class="text-danger"
									role="alert"> <strong>{{ $errors->first('db_password') }}</strong>
								</span> @endif
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
		--}}
	</div>
    <div class="col-6 mobile-attribute-rwd">
            <div class="">
                <div class="influencer-form-list">
                    <div class="">
                        <div class="card-box h-100">
                            <div class="row mb-2">
                                <div class="col-sm-12">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h4 class="page-title">{{ ("Attribute") }}</h4>
                                        <button class="btn btn-info waves-effect waves-light text-sm-right addAttributbtn" dataid="0"><i class="mdi mdi-plus-circle mr-1"></i> {{ __('Add') }}
                                        </button>
                                    </div>
                                    <p class="sub-header">
                                        {{ __("Drag & drop Attribute to change the position") }}
                                    </p>
                                </div>
                            </div>
                            <div class="row variant-row">
                                <div class="col-md-12">
                                    
                                    <div class="table-responsive outer-box">
                                        <table class="table table-centered table-nowrap table-striped" id="varient-datatable">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Name') }}</th>
                                                    <th>{{ __('Options') }}</th>
                                                    <th>{{ __('Action') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                
                                                @if(!empty($attributes))
                                                @foreach($attributes as $key => $variant)

                                                    @if(!empty($variant->translation_one))
                                                        <tr class="variantList" data-row-id="{{$variant->id}}">
                                                            <td>
                                                                <a class="editAttributeBtn" dataid="{{$variant->id}}" href="javascript:void(0);">{{$variant->title}}</a>
                                                            </td>
                                                           
                                                            <td>
                                                                @foreach($variant->option as $key => $value)
                                                                <label style="margin-bottom: 3px;">
                                                                    @if(isset($variant) && !empty($variant->type) && $variant->type == 2)
                                                                    <span style="padding:8px; float: left; border: 1px dotted #ccc; background:{{$value->hexacode}};"> </span>
                                                                    @endif
                                                                    &nbsp;&nbsp; {{$value->title}}</label> <br />
                                                                @endforeach
                                                            </td>
                                                            <td>
                                                                <a class="action-icon editAttributeBtn" dataid="{{$variant->id}}" href="javascript:void(0);">
                                                                    <i class="mdi mdi-square-edit-outline"></i>
                                                                </a>
                                                                @if( auth()->user()->is_superadmin )
                                                                <a class="action-icon deleteAttribute" dataid="{{$variant->id}}" href="javascript:void(0);">
                                                                    <i class="mdi mdi-delete"></i>
                                                                </a>
                                                                <form action="{{route('attribute.delete', $variant->id)}}" method="POST" style="display: none;" id="attrDeleteForm{{$variant->id}}">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="action-icon btn btn-primary-outline" dataid="{{$variant->id}}" onclick="return confirm('Are you sure? You want to delete the attribute.')"> <i class="mdi mdi-delete"></i></button>
                                                                </form>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endif
                                              @endforeach
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	<form method="POST" action="{{route('task.proof')}}">
		@csrf
		<div class="row">
			<div class="col-xl-9 col-md-12">
				<div class="card-box">
					<div class="d-flex align-items-center justify-content-between mb-2">
						<h4 class="header-title mb-0">{{__('Task Completion Proofs')}}</h4>
						<button class="btn btn-outline-info d-block" type="submit">
							{{__('Save')}}</button>
					</div>
					<div>
						{{-- @php echo "
						<pre>";
                            print_r($task_list); @endphp --}}
                        @foreach ($task_proofs as $key => $taskproof)
                        @php $counter = 1; @endphp
                        <h5 class="header-title mb-3">{{__($task_list[$key]->name)}}</h5>

                        <div class="table-responsive table_spacing">
                            <table
									class="table table-borderless table-nowrap table-hover table-centered m-0">

                                <thead class="thead-light">
                                    <tr>
                                        <th>{{__("Type")}}</th>
                                        <th>{{__("Enable")}}</th>
                                        <th>{{__("Required")}}</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    </td>

                                    <tr>
                                        <td>
                                            <h5
													class="m-0 font-weight-normal">{{__("Image")}}</h5>
                                        </td>

                                        <td>
                                            <div
													class="custom-control custom-switch">
                                                <input type="checkbox"
														class="custom-control-input event_type"
														id="customSwitch_{{$key.''.$counter.'1'}}"
														name="image_{{$key+1}}" {{isset($taskproof->image) && $taskproof->image == 1 ? 'checked':''}}>
                                                <label
														class="custom-control-label"
														for="customSwitch_{{$key.''.$counter.'1'}}"></label>
                                            </div>
                                        </td>

                                        <td>
                                            <div
													class="custom-control custom-switch">
                                                <input type="checkbox"
														class="custom-control-input event_type"
														id="customSwitch_{{$key.''.$counter.'2'}}"
														name="image_requried_{{$key+1}}" {{isset($taskproof->image_requried) && $taskproof->image_requried == 1 ? 'checked':''}}>
                                                <label
														class="custom-control-label"
														for="customSwitch_{{$key.''.$counter.'2'}}"></label>
                                            </div>
                                        </td>


                                    </tr>

                                    <tr>
                                        <td>
                                            <h5
													class="m-0 font-weight-normal">{{__("Signature")}}</h5>
                                        </td>

                                        <td>
                                            <div
													class="custom-control custom-switch">
                                                <input type="checkbox"
														class="custom-control-input event_type"
														id="customSwitch_{{$key.''.$counter.'3'}}"
														name="signature_{{$key+1}}" {{isset($taskproof->signature) && $taskproof->signature == 1 ? 'checked':''}}>
                                                <label
														class="custom-control-label"
														for="customSwitch_{{$key.''.$counter.'3'}}"></label>
                                            </div>
                                        </td>

                                        <td>
                                            <div
													class="custom-control custom-switch">
                                                <input type="checkbox"
														class="custom-control-input event_type"
														id="customSwitch_{{$key.''.$counter.'4'}}"
														name="signature_requried_{{$key+1}}" {{isset($taskproof->signature_requried) && $taskproof->signature_requried == 1 ? 'checked':''}}>
                                                <label
														class="custom-control-label"
														for="customSwitch_{{$key.''.$counter.'4'}}"></label>
                                            </div>
                                        </td>


                                    </tr>

                                    <tr>
                                        <td>
                                            <h5
													class="m-0 font-weight-normal">{{__('Notes')}}</h5>
                                        </td>

                                        <td>
                                            <div
													class="custom-control custom-switch">
                                                <input type="checkbox"
														class="custom-control-input event_type"
														id="customSwitch_{{$key.''.$counter.'5'}}"
														name="note_{{$key+1}}" {{isset($taskproof->note) && $taskproof->note == 1 ? 'checked':''}}>
                                                <label
														class="custom-control-label"
														for="customSwitch_{{$key.''.$counter.'5'}}"></label>
                                            </div>
                                        </td>

                                        <td>
                                            <div
													class="custom-control custom-switch">
                                                <input type="checkbox"
														class="custom-control-input event_type"
														id="customSwitch_{{$key.''.$counter.'6'}}"
														name="note_requried_{{$key+1}}" {{isset($taskproof->note_requried) && $taskproof->note_requried == 1 ? 'checked':''}}>
                                                <label
														class="custom-control-label"
														for="customSwitch_{{$key.''.$counter.'6'}}"></label>
                                            </div>
                                        </td>


                                    </tr>


                                    <tr>
                                        <td>
                                            <h5
													class="m-0 font-weight-normal">{{__("Barcode")}}</h5>
                                        </td>

                                        <td>
                                            <div
													class="custom-control custom-switch">
                                                <input type="checkbox"
														class="custom-control-input event_type"
														id="customSwitch_{{$key.''.$counter.'7'}}"
														name="barcode_{{$key+1}}" {{isset($taskproof->barcode) && $taskproof->barcode == 1 ? 'checked':''}}>
                                                <label
														class="custom-control-label"
														for="customSwitch_{{$key.''.$counter.'7'}}"></label>
                                            </div>
                                        </td>

                                        <td>
                                            <div
													class="custom-control custom-switch">
                                                <input type="checkbox"
														class="custom-control-input event_type barcode-requried-check"
														id="customSwitch_{{$key.''.$counter.'8'}}"
														name="barcode_requried_{{$key+1}}" {{isset($taskproof->barcode_requried) && $taskproof->barcode_requried == 1 ? 'checked':''}}>
                                                <label
														class="custom-control-label"
														for="customSwitch_{{$key.''.$counter.'8'}}"></label>
                                            </div>
                                        </td>


                                    </tr>
                                    <tr>
                                        <td>
                                            <h5
													class="m-0 font-weight-normal">{{__("OTP")}}</h5>
                                        </td>

                                        <td>
                                            <div
													class="custom-control custom-switch">
                                                <input type="checkbox"
														class="custom-control-input event_type"
														id="customSwitch_{{$key.''.$counter.'9'}}"
														name="otp_{{$key+1}}" {{!empty($taskproof->otp) && $taskproof->otp == 1 ? 'checked':''}}>
                                                <label
														class="custom-control-label"
														for="customSwitch_{{$key.''.$counter.'9'}}"></label>
                                            </div>
                                        </td>

                                        <td>
                                            <div
													class="custom-control custom-switch">
                                                <input type="checkbox"
														class="custom-control-input event_type otp-requried-check"
														id="customSwitch_{{$key.''.$counter.'10'}}"
														name="otp_requried_{{$key+1}}" {{!empty($taskproof->otp_requried) && $taskproof->otp_requried == 1 ? 'checked':''}}>
                                                <label
														class="custom-control-label"
														for="customSwitch_{{$key.''.$counter.'10'}}"></label>
                                            </div>
                                        </td>


                                    </tr>

                                    <tr>
                                        <td>
                                            <h5
													class="m-0 font-weight-normal">{{__("Face Proof")}}</h5>
                                        </td>

                                        <td>
                                            <div
													class="custom-control custom-switch">
                                                <input type="checkbox"
														class="custom-control-input event_type"
														id="customSwitch_{{$key.''.$counter.'11'}}"
														name="face_{{$key+1}}" {{!empty($taskproof->face) && $taskproof->face == 1 ? 'checked':''}}>
                                                <label
														class="custom-control-label"
														for="customSwitch_{{$key.''.$counter.'11'}}"></label>
                                            </div>
                                        </td>

                                        <td>
                                            <div
													class="custom-control custom-switch">
                                                <input type="checkbox"
														class="custom-control-input event_type otp-requried-check"
														id="customSwitch_{{$key.''.$counter.'12'}}"
														name="face_requried_{{$key+1}}" {{!empty($taskproof->face_requried) && $taskproof->face_requried == 1 ? 'checked':''}}>
                                                <label
														class="custom-control-label"
														for="customSwitch_{{$key.''.$counter.'12'}}"></label>
                                            </div>
                                        </td>


                                    </tr>

                                    <tr>
                                        <td>
                                            <h5
													class="m-0 font-weight-normal">{{__("QR Code Scan")}}</h5>
                                        </td>

                                        <td>
                                            <div
													class="custom-control custom-switch">
                                                <input type="checkbox"
														class="custom-control-input event_type"
														id="customSwitch_{{$key.''.$counter.'13'}}"
														name="qrcode_{{$key+1}}" {{!empty($taskproof->qrcode) && $taskproof->qrcode == 1 ? 'checked':''}}>
                                                <label
														class="custom-control-label"
														for="customSwitch_{{$key.''.$counter.'13'}}"></label>
                                            </div>
                                        </td>

                                        <td>
                                            <div
													class="custom-control custom-switch">
                                                <input type="checkbox"
														class="custom-control-input event_type otp-requried-check"
														id="customSwitch_{{$key.''.$counter.'14'}}"
														name="qrcode_requried_{{$key+1}}" {{!empty($taskproof->qrcode_requried) && $taskproof->qrcode_requried == 1 ? 'checked':''}}>
                                                <label
														class="custom-control-label"
														for="customSwitch_{{$key.''.$counter.'14'}}"></label>
                                            </div>
                                        </td>


                                    </tr>


                                </tbody>
                            </table>
                         </div>
                         {{-- <h4 class="header-title mb-3">{{$key == 0 ? 'Drop-Off': $key == 1 ? 'Appointment':''}}</h4> --}}
                         @php $counter++; @endphp
                        @endforeach

                        {{-- <h4 class="header-title mb-3">Drop-Off</h4>
                        <div class="table-responsive table_spacing">
                            <table
									class="table table-borderless table-nowrap table-hover table-centered m-0">

                                <thead class="thead-light">
                                    <tr>
                                        <th>Type</th>
                                        <th>Enable</th>
                                        <th>Required</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    </td>

                                    <tr>
                                        <td>
                                            <h5
													class="m-0 font-weight-normal">Image</h5>
                                        </td>

                                        <td>
                                            <div
													class="custom-control custom-switch">
                                                <input type="checkbox"
														class="custom-control-input event_type"
														id="customSwitch_7" name="image_2" {{isset($taskproof->image) && $taskproof->image == 1 ? 'checked':''}}>
                                                <label
														class="custom-control-label" for="customSwitch_7"></label>
                                            </div>
                                        </td>

                                        <td>
                                            <div
													class="custom-control custom-switch">
                                                <input type="checkbox"
														class="custom-control-input event_type"
														id="customSwitch_8" name="image_requried_2"
														{{isset($taskproof->image_requried) && $taskproof->image_requried == 1 ? 'checked':''}}>
                                                <label
														class="custom-control-label" for="customSwitch_8"></label>
                                            </div>
                                        </td>


                                    </tr>

                                    <tr>
                                        <td>
                                            <h5
													class="m-0 font-weight-normal">Signature</h5>
                                        </td>

                                        <td>
                                            <div
													class="custom-control custom-switch">
                                                <input type="checkbox"
														class="custom-control-input event_type"
														id="customSwitch_9" name="signature_2" {{isset($taskproof->signature) && $taskproof->signature == 1 ? 'checked':''}}>
                                                <label
														class="custom-control-label" for="customSwitch_9"></label>
                                            </div>
                                        </td>

                                        <td>
                                            <div
													class="custom-control custom-switch">
                                                <input type="checkbox"
														class="custom-control-input event_type"
														id="customSwitch_10" name="signature_requried_2"
														{{isset($taskproof->signature_requried) && $taskproof->signature_requried == 1 ? 'checked':''}}>
                                                <label
														class="custom-control-label" for="customSwitch_10"></label>
                                            </div>
                                        </td>


                                    </tr>

                                    <tr>
                                        <td>
                                            <h5
													class="m-0 font-weight-normal">Notes</h5>
                                        </td>

                                        <td>
                                            <div
													class="custom-control custom-switch">
                                                <input type="checkbox"
														class="custom-control-input event_type"
														id="customSwitch_11" name="note_2" {{isset($taskproof->note) && $taskproof->note == 1 ? 'checked':''}}>
                                                <label
														class="custom-control-label" for="customSwitch_11"></label>
                                            </div>
                                        </td>

                                        <td>
                                            <div
													class="custom-control custom-switch">
                                                <input type="checkbox"
														class="custom-control-input event_type"
														id="customSwitch_12" name="note_requried_2"
														{{isset($taskproof->note_requried) && $taskproof->note_requried == 1 ? 'checked':''}}>
                                                <label
														class="custom-control-label" for="customSwitch_12"></label>
                                            </div>
                                        </td>


                                    </tr>


                                </tbody>
                            </table>
                        </div> --}}

                        {{-- <h4 class="header-title mb-3">Appointment</h4>
                        <div class="table-responsive table_spacing">
                            <table
									class="table table-borderless table-nowrap table-hover table-centered m-0">

                                <thead class="thead-light">
                                    <tr>
                                        <th>Type</th>
                                        <th>Enable</th>
                                        <th>Required</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    </td>

                                    <tr>
                                        <td>
                                            <h5
													class="m-0 font-weight-normal">Image</h5>
                                        </td>

                                        <td>
                                            <div
													class="custom-control custom-switch">
                                                <input type="checkbox"
														class="custom-control-input event_type"
														id="customSwitch_13" name="image_3" {{isset($taskproof->image) && $taskproof->image == 1 ? 'checked':''}}>
                                                <label
														class="custom-control-label" for="customSwitch_13"></label>
                                            </div>
                                        </td>

                                        <td>
                                            <div
													class="custom-control custom-switch">
                                                <input type="checkbox"
														class="custom-control-input event_type"
														id="customSwitch_14" name="image_requried_3"
														{{isset($taskproof->image_requried) && $taskproof->image_requried == 1 ? 'checked':''}}>
                                                <label
														class="custom-control-label" for="customSwitch_14"></label>
                                            </div>
                                        </td>


                                    </tr>

                                    <tr>
                                        <td>
                                            <h5
													class="m-0 font-weight-normal">Signature</h5>
                                        </td>

                                        <td>
                                            <div
													class="custom-control custom-switch">
                                                <input type="checkbox"
														class="custom-control-input event_type"
														id="customSwitch_15" name="signature_3"
														{{isset($taskproof->signature) && $taskproof->signature == 1 ? 'checked':''}}>
                                                <label
														class="custom-control-label" for="customSwitch_15"></label>
                                            </div>
                                        </td>

                                        <td>
                                            <div
													class="custom-control custom-switch">
                                                <input type="checkbox"
														class="custom-control-input event_type"
														id="customSwitch_16" name="signature_requried_3"
														{{isset($taskproof->signature_requried) && $taskproof->signature_requried == 1 ? 'checked':''}}>
                                                <label
														class="custom-control-label" for="customSwitch_16"></label>
                                            </div>
                                        </td>


                                    </tr>

                                    <tr>
                                        <td>
                                            <h5
													class="m-0 font-weight-normal">Notes</h5>
                                        </td>

                                        <td>
                                            <div
													class="custom-control custom-switch">
                                                <input type="checkbox"
														class="custom-control-input event_type"
														id="customSwitch_17" name="note_3" {{isset($taskproof->note) && $taskproof->note == 1 ? 'checked':''}}>
                                                <label
														class="custom-control-label" for="customSwitch_17"></label>
                                            </div>
                                        </td>

                                        <td>
                                            <div
													class="custom-control custom-switch">
                                                <input type="checkbox"
														class="custom-control-input event_type"
														id="customSwitch_18" name="note_requried_3"
														{{isset($taskproof->note_requried) && $taskproof->note_requried == 1 ? 'checked':''}}>
                                                <label
														class="custom-control-label" for="customSwitch_18"></label>
                                            </div>
                                        </td>


                                    </tr>


                                </tbody>
                            </table>
                        </div> --}}
                    
					
					</div>
				</div>
			</div>
		</div>
	</form>




</div> <!-- container -->
<div id="addAttributemodal" class="modal al fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h4 class="modal-title">{{ __('Add Attribute') }}</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <form id="addAttributeForm" method="post" enctype="multipart/form-data" action="{{route('attribute.store')}}">
                @csrf
                <div class="modal-body" id="AddAttributeBox">

                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-info waves-effect waves-light addAttributeSubmit">{{ __("Submit") }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>

    $(document).ready(function() {



        var CSRF_TOKEN = $("input[name=_token]").val();



        $( '#tandc_form' ).on( 'submit', function(e) {
            e.preventDefault();

            var content = $(this).find('textarea[name=content]').val();


            $.ajax({
            type: "POST",
            url: "{{ route('cms.save',[1]) }}",
            data: { _token: CSRF_TOKEN,content:content,id:1},
            success: function( msg ) {
                $("#create-tandc-modal .close").click();
            }
           });

        });

        $( '#pandp_form' ).on( 'submit', function(e) {
            e.preventDefault();

            var content = $(this).find('textarea[name=content]').val();


            $.ajax({
            type: "POST",
            url: "{{ route('cms.save',2) }}",
            data: { _token: CSRF_TOKEN,content:content},
            success: function( msg ) {
                $("#create-pandp-modal .close").click();
            }
           });

        });


        $(document).on('click', '[name="myRadios"]', function () {

            if (lastSelected != $(this).val() && typeof lastSelected != "undefined") {
                alert("radio box with value " + $('[name="myRadios"][value="' + lastSelected + '"]').val() + " was deselected");
            }
            lastSelected = $(this).val();

        });

    });

     // Attribute script
     $(".addAttributbtn").click(function(e) {
        console.log('click function called');
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': jQuery('meta[name="csrf-token"]').attr('content')
            }
        });
        e.preventDefault();
        var did = $(this).attr('dataid');
        $.ajax({
            type: "get",
            url: "{{route('attribute.create')}}",
            data: '',
            dataType: 'json',
            success: function(data) {
                $('#addAttributemodal').modal({
                    backdrop: 'static',
                    keyboard: false
                });
                $('#addAttributeForm #AddAttributeBox').html(data.html);
                $('.dropify').dropify();
                $('.selectize-select').selectize();

                var picker = new jscolor('#add-hexa-colorpicker-1', options);
            },
            error: function(data) {
                console.log('data2');
            }
        });

    });

    $(document).on('click', '.addOptionRow-attribute-edit', function(e) {
        var d = new Date();
        var n = d.getTime();
        var $tr = $('.optionTableEditAttribute tbody>tr:first').next('tr');
        var $clone = $tr.clone();
        $clone.find(':text').val('');
        $clone.find(':hidden').val('');
        $clone.find('.hexa-colorpicker').attr("id", "hexa-colorpicker-" + n);
        $clone.find('.lasttd').html('<a href="javascript:void(0);" class="action-icon deleteCurRow"> <i class="mdi mdi-delete"></i></a>');
        $('.optionTableEditAttribute').append($clone);
        var picker = new jscolor("#hexa-colorpicker-" + n, options);
    });

    $("#addAttributemodal").on('click', '.deleteCurRow', function() {
        $(this).closest('tr').remove();
    });

</script>
@endsection
