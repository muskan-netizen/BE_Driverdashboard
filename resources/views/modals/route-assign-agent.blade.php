
<div id="route-assign-agent-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h4 class="modal-title">{{__('Route Assign Agent')}}</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <form id="assignagentrouteform" action="" method="POST">
                @csrf
                <input type="hidden" name="order_id" id="order_id" value="">
                <div class="modal-body pb-0">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card-box mb-0 p-0">
                                <div class="row">
                                    <div class="col-lg-12 col-sm-12 mb-lg-0 mb-3">
                                        <div class="form-group" id="agent_select">
                                            <select class="form-control" name="agent_id" id="select_agent_id">
                                                @foreach ($agents as $agent)
                                                    @php
                                                
                                                        $checkAgentActive = ($agent['is_available'] == 1) ? ' ('.__('Online').')' : ' ('.__('Offline').')';
                                                    @endphp
                                                    <option value="{{$agent['id']}}">{{ ucfirst($agent['name']). $checkAgentActive }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        {{-- <button type="button" class="btn btn-blue waves-effect waves-light" onclick="cancleForm()">Cancel</button> --}}
                        <button type="button" class="btn btn-blue waves-effect waves-light submitassignAgentForm">{{__('Submit')}}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>