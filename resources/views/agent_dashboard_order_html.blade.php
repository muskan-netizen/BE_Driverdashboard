<?php
    if(isset($distance_matrix[0]))
    {
        if($unassigned_orders[0]['task_order']==0){
            $opti0 = "yes";
        }else{
            $opti0 = "";
        }
        $routeperams0 = "'".$distance_matrix[0]['tasks']."','".json_encode($distance_matrix[0]['distance'])."','".$opti0."',0,'".$date."'";
        $optimize0 = '<span class="optimize_btn" onclick="RouteOptimization('.$routeperams0.')">'.__("Optimize").'</span>';
        $params0 = "'".$distance_matrix[0]['tasks']."','".json_encode($distance_matrix[0]['distance'])."','yes',0,'".$date."'";
        $turnbyturn0 = '<span class="navigation_btn optimize_btn" onclick="NavigatePath('.$routeperams0.')">'.__("Export").'</span>';
    }else{
        $optimize0="";
        $params0 = "";
        $turnbyturn0 = "";
    }
?>
@php
    $date = date('Y-m-d');
    use Carbon\Carbon;

   
@endphp

@if($page == 1)
<div id="accordion" class="overflow-hidden">
    <!-- dragable_tasks -->
    <div id="handle-dragula-left0" class=" " agentid="0"  params="{{ $params0 }}" date="{{ $date }}">
@endif
        @foreach($unassigned_orders as $orders)
           
            @foreach($tasks[$orders['id']] as $task)
                <div class="card-body" task_id ="{{ $task['id'] }}">
                    <div class="p-2 assigned-block">
                        @php
                            $st = ucfirst($orders['status']);
                            
                            if($orders['status'] == "unassigned"){
                                $color_class = "unassigned-badge";
                            }else{
                                $color_class = "green_";
                            }
                            if($task['task_type_id']==1)
                            {
                                $tasktype = "Pickup";
                                $pickup_class = "yellow_";
                            }elseif($task['task_type_id']==2)
                            {
                                $tasktype = "Dropoff";
                                $pickup_class = "green_";
                            }else{
                                $tasktype = "Appointment";
                                $pickup_class = "assign_";
                            }
                        @endphp
                        <div>
                            <div class="pick_drop_item_list">
                                <div class="col-12 ">
                                    <i class="fas fa-bars"></i>
                                    @php
                                    if($task['assigned_time']=="")
                                    {
                                        $tasks['assigned_time'] = date('Y-m-d H:i:s');
                                    }
                                        $timeformat = $preference->time_format == '24' ? 'H:i:s':'g:i a';

                                        $dateString = $task['assigned_time'];
                                        
                                       
                                        // Format the date as needed
                                        //$order = date('Y-m-d H:i:s');//@$order->format('Y-m-d H:i:s');
                                    @endphp

                                    <h5 class="w-100 d-flex align-items-center  justify-content-between">
                                        {{-- <span>{{@date(''.@$timeformat.'', strtotime(@$date))}}</span> --}}
                                        <span>{{$dateString}}</span>
                                        {{-- <p>
                                            @if(!empty($agent))
                                                <span class="badge ">{{ucfirst($agent['name'])}}</span>
                                                @else
                                                <span class="badge badge-danger text-white unassigned-badge" data-id="{{$orders['id']}}">{{__('Unassigned')}}</span>
                                            @endempty
                                        </p> --}}
                                        <button class="assigned-btn float-left ml-1 unassigned-badge {{$color_class}}" data-id="{{$orders['id']}}">{{__($st)}}</button>

                                    </h5>
                                    <div class="second_list_pick w-100 d-flex align-items-center justify-content-between">
                                         <h6 class="d-inline"><img class="vt-top" src="{{ asset('demo/images/ic_location_blue_1.png') }}"> {{ $orders['address'] ?? $orders['address']}} <span class="d-block">{{ $orders['short_name'] ?? $orders['short_name'] }}</span></h6>
                                          <button class="assigned-btn float-left  mb-2 {{$pickup_class}}">{{__($tasktype)}}</button>  
                                    </div>
                                        {{-- <div> --}}


                                        {{-- </div> --}}
                                </div>
                                <div class="col-12 mb-4 justify-content-between">
                                 <button class="view_route-btn float-left  mb-2 btn btn-primary" data-id="{{$orders['id']}}">{{__("view route")}}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            
        @endforeach
        @if($lastPage != $page && $unassigned_orders)
            <button class="form-control" id="load-more" data-page="{{$page + 1}}" data-url="{{ route('dashboard.agent-orderdata', ['page' => $page + 1])}}">Load More</button>
        @endif
        @if($page == 1)
    </div>
</div>
@endif