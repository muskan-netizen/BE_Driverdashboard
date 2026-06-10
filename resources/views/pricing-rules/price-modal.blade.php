<div id="add-pricing-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered modal-lg price_geo-fence">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h4 class="modal-title">{{__("Add Pricing Rule")}}</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <form id="add_customer" action="{{ route('pricing-rules.store') }}" method="POST">
                @csrf
                <div class="modal-body px-3 py-0">

                    <div class="row">

                        <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group" id="nameInput">
                                            {!! Form::label('title', __('Name'),['class' => 'control-label']) !!}
                                            {!! Form::text('name', null, ['class' => 'form-control','placeholder'=> __('Name'),'required' => 'required']) !!}
                                            <span class="invalid-feedback" role="alert">
                                                <strong></strong>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <h5 class="text-uppercase bg-light-yellopink p-2 mt-0 mb-3">Conditions To Apply Price Rule</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group" id="typeInput">
                                            {!! Form::label('title', __('Select Geo Fence'),['class' => 'control-label']) !!} <span class="badge badge-primary float-right" id="select_geo_all" style="cursor:pointer;">Select All</span>
                                            {!! Form::select('geo_id[]',$geos,null,['id' => 'geo_id', 'data-toggle' => 'select2', 'class' => 'form-control', 'multiple' => 'multiple', 'data-placeholder' => 'Choose ...']) !!}
                                            <span class="invalid-feedback" role="alert">
                                                <strong></strong>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group" id="typeInput">
                                            {!! Form::label('title', __('Select Team'),['class' => 'control-label']) !!} <span class="badge badge-primary float-right" id="select_team_all" style="cursor:pointer;">Select All</span>
                                            {!! Form::select('team_id[]',$teams,null,['id' => 'team_id','data-toggle' => 'select2', 'class' => 'form-control', 'multiple' => 'multiple', 'data-placeholder' => 'Choose ...']) !!}
                                            <span class="invalid-feedback" role="alert">
                                                <strong></strong>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">    
                                    <div class="col-md-6">
                                        <div class="form-group" id="typeInput">
                                            {!! Form::label('title', __('Select Team Tag'),['class' => 'control-label']) !!} <span class="badge badge-primary float-right" id="select_team_tag_all" style="cursor:pointer;">Select All</span>
                                            {!! Form::select('team_tag_id[]',$team_tag,null,['id' => 'team_tag_id','data-toggle' => 'select2', 'class' => 'form-control', 'multiple' => 'multiple', 'data-placeholder' => 'Choose ...']) !!}
                                            <span class="invalid-feedback" role="alert">
                                                <strong></strong>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group" id="typeInput">
                                            {!! Form::label('title', __('Select '.getAgentNomenclature().' Tag'),['class' => 'control-label']) !!} <span class="badge badge-primary float-right" id="select_driver_tag_all" style="cursor:pointer;">Select All</span>
                                            {!! Form::select('driver_tag_id[]',$driver_tag,null,['id' => 'driver_tag_id','data-toggle' => 'select2', 'class' => 'form-control', 'multiple' => 'multiple', 'data-placeholder' => 'Choose ...']) !!}
                                            <span class="invalid-feedback" role="alert">
                                                <strong></strong>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">  
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            {!! Form::label('title', __('Apply Timetable'),['class' => 'control-label']) !!}
                                            <div class="mt-md-1">
                                                <input type="checkbox" data-plugin="switchery" name="apply_timetable" class="form-control apply_timetable" data-color="#43bee1">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="timetable_div" style="display:none;">
                                    <h5><span class="digital-clock1" style="float:right;color: rgb(183 33 33);">00:00:00</span></h5>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-striped dt-responsive nowrap w-100">
                                            <thead>
                                                <tr>
                                                    <th>{{__("Day")}}</th>
                                                    <th>{{__("Start Time")}}</th>
                                                    <th>{{__("End Time")}}</th>
                                                    <th>{{__("Add")}} <input type="hidden" id="hddn_days_count" name="hddn_days_count" value="{{count($weekdays)}}"/></th>   
                                                </tr>
                                            </thead>
                                            <?php $i = 0;?>
                                            @foreach($weekdays as $weekday)<?php $i++;?>
                                            <tbody id="timeframe_tbody_{{$i}}">
                                                <tr id="timeframe_row_{{$i}}_1">
                                                    <td>
                                                        <input type="hidden" name="hddnWeekdays_{{$i}}" id="hddnWeekdays_{{$i}}" value="{{$weekday}}" />
                                                        <div class="checkbox checkbox-primary mb-1">
                                                            <input type="checkbox" name="checkdays_{{$i}}" id="checkdays_{{$i}}" value="1" data-parsley-mincheck="2">
                                                            <label for="checkdays_{{$i}}">&nbsp;&nbsp;&nbsp;&nbsp;{{__($weekday)}} </label>
                                                        </div>
                                                    </td>
                                                    
                                                    <td>{!! Form::text('price_starttime_'.$i.'_1', null, ['id'=>'price_starttime_'.$i.'_1', 'class' => 'form-control', 'autocomplete' => 'off', 'placeholder' => __('00:00')]) !!}</td>
                                                    <td>{!! Form::text('price_endtime_'.$i.'_1', null, ['id'=>'price_endtime_'.$i.'_1', 'class' => 'form-control', 'autocomplete' => 'off', 'placeholder' => __('00:00')]) !!}</td>
                                                    <td style="text-align:center;">
                                                        <input type="hidden" name="no_of_time_{{$i}}" id="no_of_time_{{$i}}" value="1" />
                                                        <button type="button" class="btn btn-info btn-rounded waves-effect waves-light add_sub_pricing_row" data-id="{{$i}}"><i class="far fa-plus-square"></i> Add</button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                            @endforeach
                                        </table>
                                    </div>
                                </div>

                                <hr>
                                <h5 class="text-uppercase bg-light-yellopink p-2 mt-0 mb-3">Pricing Values @if(checkColumnExists('client_preferences', 'is_bid_ride_toggle')) @if($client->getPreference->is_bid_ride_toggle == 1) (Recommendation) @endif @endif</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group" id="">
                                            {!! Form::label('title', __('Base Price'),['class' => 'control-label']) !!}
                                            {!! Form::text('base_price', 10, ['class' => 'form-control','required' => 'required']) !!}
                                            <span class="invalid-feedback" role="alert">
                                                <strong></strong>
                                            </span>

                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group" id="">
                                            {!! Form::label('title', __('Waiting Price'),['class' => 'control-label']) !!}
                                            {{-- <a href="javascript:void(0)" class="btn btn-success btn-sm mb-1  add_more_button add_button float-right"  data-id="1" style=""><i class="mdi mdi-plus-circle mr-1" aria-hidden="true"></i> Add Distance wise Price</a> --}}
                                            {!! Form::number('waiting_price', 1, ['class' => 'form-control']) !!}
                                            <span class="invalid-feedback" role="alert">
                                                <strong></strong>
                                            </span>

                                        </div>
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group" id="">
                                            {!! Form::label('title', __('Base Duration'),['class' => 'control-label']) !!}
                                            {!! Form::text('base_duration', 1, ['class' => 'form-control','required' => 'required']) !!}
                                            <span class="invalid-feedback" role="alert">
                                                <strong></strong>
                                            </span>

                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group" id="">
                                            {!! Form::label('title', __('Base Distance'),['class' => 'control-label']) !!}
                                            {!! Form::text('base_distance', 1, ['class' => 'form-control','required' => 'required']) !!}
                                            <span class="invalid-feedback" role="alert">
                                                <strong></strong>
                                            </span>

                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group" id="">
                                            {!! Form::label('title', __('Duration Price (per minute)'),['class' => 'control-label']) !!}
                                            {!! Form::text('duration_price', 1, ['class' => 'form-control','required' => 'required']) !!}
                                            <span class="invalid-feedback" role="alert">
                                                <strong></strong>
                                            </span>

                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group" id="">
                                            {!! Form::label('title', __('Distance Fee'),['class' => 'control-label']) !!}
                                            <input type="text" name="distance_fee" value="1" class="form-control" id="distance_fee" required>
                                            <span class="invalid-feedback" role="alert">
                                                <strong></strong>
                                            </span>

                                        </div>
                                    </div>
                                </div>

                                <span id="new-rows"></span>
                                @if(checkColumnExists('client_preferences', 'is_bid_ride_toggle')) @if($client->getPreference->is_bid_ride_toggle == 1)
                                <hr>
                                <h5 class="text-uppercase bg-light-yellopink p-2 mt-0 mb-3">Pricing Values (Minimum)</h5>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group" id="">
                                            {!! Form::label('title', __('Base Price'),['class' => 'control-label']) !!}
                                            {!! Form::text('base_price_minimum', 10, ['class' => 'form-control','required' => 'required']) !!}
                                            <span class="invalid-feedback" role="alert">
                                                <strong></strong>
                                            </span>

                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group" id="">
                                            {!! Form::label('title', __('Base Duration'),['class' => 'control-label']) !!}
                                            {!! Form::text('base_duration_minimum', 1, ['class' => 'form-control','required' => 'required']) !!}
                                            <span class="invalid-feedback" role="alert">
                                                <strong></strong>
                                            </span>

                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group" id="">
                                            {!! Form::label('title', __('Base Distance'),['class' => 'control-label']) !!}
                                            {!! Form::text('base_distance_minimum', 1, ['class' => 'form-control','required' => 'required']) !!}
                                            <span class="invalid-feedback" role="alert">
                                                <strong></strong>
                                            </span>

                                        </div>
                                    </div>
                                </div>

                                <div class="row d-none">
                                    <div class="col-md-6">
                                        <div class="form-group" id="">
                                            {!! Form::label('title', __('Duration Price(per minute)'),['class' => 'control-label']) !!}
                                            {!! Form::text('duration_price_minimum', 1, ['class' => 'form-control','required' => 'required']) !!}
                                            <span class="invalid-feedback" role="alert">
                                                <strong></strong>
                                            </span>

                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group" id="">
                                            {!! Form::label('title', __('Distance Fee'),['class' => 'control-label']) !!}
                                            {!! Form::text('distance_fee_minimum', 1, ['class' => 'form-control','required' => 'required']) !!}
                                            <span class="invalid-feedback" role="alert">
                                                <strong></strong>
                                            </span>

                                        </div>
                                    </div>
                                </div>

                                <hr>
                                <h5 class="text-uppercase bg-light-yellopink p-2 mt-0 mb-3">Pricing Values (Maximum)</h5>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group" id="">
                                            {!! Form::label('title', __('Base Price'),['class' => 'control-label']) !!}
                                            {!! Form::text('base_price_maximum', 10, ['class' => 'form-control','required' => 'required']) !!}
                                            <span class="invalid-feedback" role="alert">
                                                <strong></strong>
                                            </span>

                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group" id="">
                                            {!! Form::label('title', __('Base Duration'),['class' => 'control-label']) !!}
                                            {!! Form::text('base_duration_maximum', 1, ['class' => 'form-control','required' => 'required']) !!}
                                            <span class="invalid-feedback" role="alert">
                                                <strong></strong>
                                            </span>

                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group" id="">
                                            {!! Form::label('title', __('Base Distance'),['class' => 'control-label']) !!}
                                            {!! Form::text('base_distance_maximum', 1, ['class' => 'form-control','required' => 'required']) !!}
                                            <span class="invalid-feedback" role="alert">
                                                <strong></strong>
                                            </span>

                                        </div>
                                    </div>
                                </div>

                                {{-- <div class="row d-none">
                                    <div class="col-md-6">
                                        <div class="form-group" id="">
                                            {!! Form::label('title', __('Duration Price (per minute)'),['class' => 'control-label']) !!}
                                            {!! Form::text('duration_price_maximum', 1, ['class' => 'form-control','required' => 'required']) !!}
                                            <span class="invalid-feedback" role="alert">
                                                <strong></strong>
                                            </span>

                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group" id="">
                                            {!! Form::label('title', __('Distance Fee'),['class' => 'control-label']) !!}
                                            {!! Form::text('distance_fee_maximum', 1, ['class' => 'form-control','required' => 'required']) !!}
                                            <span class="invalid-feedback" role="alert">
                                                <strong></strong>
                                            </span>

                                        </div>
                                    </div>
                                </div> --}}
                                <hr>
                                @endif @endif
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group" id="">
                                            {!! Form::label('title', __('Employee Commission Percentage'),['class' => 'control-label']) !!}
                                            {!! Form::text('agent_commission_percentage', null, ['class' => 'form-control','required' => 'required']) !!}
                                            <span class="invalid-feedback" role="alert">
                                                <strong></strong>
                                            </span>

                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group" id="">
                                            {!! Form::label('title', __('Employee Commission Fixed'),['class' => 'control-label']) !!}
                                            {!! Form::text('agent_commission_fixed', null, ['class' => 'form-control','required' => 'required']) !!}
                                            <span class="invalid-feedback" role="alert">
                                                <strong></strong>
                                            </span>

                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group" id="">
                                            {!! Form::label('title', __('Freelancer Commission Percentage'),['class' => 'control-label']) !!}
                                            {!! Form::text('freelancer_commission_percentage', null, ['class' => 'form-control']) !!}
                                            <span class="invalid-feedback" role="alert">
                                                <strong></strong>
                                            </span>

                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group" id="">
                                            {!! Form::label('title', __('Freelancer Commission Fixed'),['class' => 'control-label']) !!}
                                            {!! Form::text('freelancer_commission_fixed', null, ['class' => 'form-control']) !!}
                                            <span class="invalid-feedback" role="alert">
                                                <strong></strong>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                           
                            {{-- Do not Remove this blow div --}}
                            <div class="" id="nestable_list_1" style="display: none">

                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-blue waves-effect waves-light">{{__("Submit")}}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="edit-price-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered modal-lg price_geo-fence">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h4 class="modal-title">{{__("Pricing Edit")}}</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>

            <div class="modal-body px-3 py-0">
                <div class="row">
                    <div class="col-md-12" id="editCardBox">

                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="submit" class="btn btn-blue waves-effect waves-light submitEditForm">{{__("Submit")}}</button>
            </div>

        </div>
    </div>
</div>
<script>
 $(document).on('click','.add_more_button',function(){
    var main_id = $(this).attr('data-id');
    section_id  = parseInt(main_id);
    var val = $('#distance_fee'+section_id).val();

    if(val==0 || val == undefined)
        {
            var val = $('#distance_fee').val();
            val  = parseInt(val) + 1;
        }else{
            val = parseInt(val) + 1;
        }

    if(section_id !== 1){
        $(this).hide();
    }else{
        $(this).prop('disabled', true);
        $(this).addClass('disabled');
    }

    $('#remove_button'+section_id).hide();

    id          = section_id +1;
        addTemplate(id,val);
        $('#add_button').attr('data-id',id);
    });

    function addTemplate(section_id,val){
        id = section_id;
        var data  = '';

        var data = '<div class="row" id="remove'+id+'"><div class="col-md-6"><div class="form-group" id="">{!! Form::label('title', __('Price (per km)'),['class' => 'control-label']) !!}{!! Form::number('duration_price_arr[]', 1, ['class' => 'form-control']) !!}</div></div><div class="col-md-6"><div class="form-group" id="">{!! Form::label('title', __('Distance km'),['class' => 'control-label']) !!}<a href="javascript:void(0)" class="action-icon remove_more_button float-right" id="remove_button'+id+'" data-rid="'+id+'"> <i class="mdi mdi-delete"></i></a><a href="javascript:void(0)" class="ml-1 add_more_button float-right" id="add_button'+id+'" data-id="'+id+'" style=""><i class="mdi mdi-plus-circle mr-1" aria-hidden="true"></i></a><input type="number" name="distance_fee_arr[]" value="'+val+'" class="form-control" id="distance_fee'+id+'" min="'+val+'" ></div></div></div>';

        var chk = $('#option-check').val();
        if(chk == 1){
            $('#new-rows').append(data);
        }else{
            $('#new-rows-edit').append(data);
        }

    }


    $(document).on('click','.remove_more_button',function(){
        var id = $(this).attr('data-rid');
        $('#remove'+ id).remove();
        id  = id - 1;
        if(id !== 1){
            $('#add_button'+id).show();
        }else{
            $('.add_button').prop('disabled', false);
            $('.add_button').removeClass('disabled');

        }
        $('#remove_button'+id).show();

    });
</script>