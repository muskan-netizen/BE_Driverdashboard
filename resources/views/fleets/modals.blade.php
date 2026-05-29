<div id="add-agent-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h4 class="modal-title">{{__("Add Fleet")}} </h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>

            <form id="submitAgent" enctype="multipart/form-data" action="{{ url('fleet/store') }}">
                <div class="modal-body px-3 py-0">
                    @csrf
                    <span class="show_all_error invalid-feedback"></span>
                    <div class="row">
                        <div class="col-md-6">
                             <input type="hidden" class="form-control" id="editId" name="editId">
                            <div class="form-group" id="nameInput">
                                <label for="name" class="control-label">{{__("NAME")}}</label>
                                <input type="text" class="form-control" id="name" placeholder="Fleet Name" name="name">
                                <span class="invalid-feedback" role="alert">
                                    <strong></strong>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group" id="makeInput">
                                <label for="make" class="control-label">{{__("Make")}}</label>
                                <input type="text" class="form-control" id="make" placeholder="Make" name="make">
                                <span class="invalid-feedback" role="alert">
                                    <strong></strong>
                                </span>

                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group" id="modelInput">
                                <label for="model" class="control-label">{{__("Model")}}</label>
                                <input type="text" class="form-control" id="model" placeholder="Model" name="model">
                                <span class="invalid-feedback" role="alert">
                                    <strong></strong>
                                </span>

                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group" id="regnameInput">
                                <label for="regname" class="control-label">{{__("Registration Name")}}</label>
                                <input type="text" class="form-control" id="registration_name" placeholder="Registration Name" name="registration_name">
                                <span class="invalid-feedback" role="alert">
                                    <strong></strong>
                                </span>

                            </div>
                        </div>
                    </div>


                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group" id="yearInput">
                                <label for="year" class="control-label">{{__("Year")}}</label>
                                <input type="text" class="form-control" id="year" placeholder="year" name="year">
                                <span class="invalid-feedback" role="alert">
                                    <strong></strong>
                                </span>

                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group" id="colorInput">
                                <label for="color" class="control-label">{{__("Color")}}</label>
                                <input type="text" class="form-control" id="color" placeholder="color" name="color">
                                <span class="invalid-feedback" role="alert">
                                    <strong></strong>
                                </span>

                            </div>
                        </div>
                    </div>
                    

                    

                

                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-blue waves-effect waves-light submitAgentForm">{{__("Submit")}}</button>
                </div>
            </form>
        </div>

    </div>
</div>

<div id="assign-driver-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display:none;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h4 class="modal-title">{{__("Assign")}} {{ getAgentNomenclature() }}</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <form method="POST" action="{{url('fleet/updateDriver')}}">
                @csrf
                <input type="hidden" name="fleet_id" id="fleet_id"/>
                <div class="modal-body px-3" id="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group" id="nameInput">
                                <label for="name" class="control-label">{{__("Select "). getAgentNomenclature()}}</label>
                                <span id="selectBox"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-blue waves-effect waves-light">{{__("Submit")}}</button>
                </div>


        </div>
    </div>
</div>
</form>

<div id="fleet-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display:none;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h4 class="modal-title">{{__('Fleet Details')}}</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
         
                <div class="modal-body px-3" id="fleetViewCardBox">

                </div>

        </div>
    </div>
</div>


<div id="order-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display:none;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h4 class="modal-title">{{__('Order Details')}}</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
         
                <div class="modal-body px-3" id="orderViewCardBox">

                </div>

        </div>
    </div>
</div>


