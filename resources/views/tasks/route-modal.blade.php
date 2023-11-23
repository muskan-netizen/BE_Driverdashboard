    <style>


    ul.custom-list {
    list-style: none;
    margin: 0;
    padding: 0;
    position: relative;
    }

    ul.custom-list:before {
    content: "";
    display: inline-block;
    width: 2px;
    background: lightblue;
    position: absolute;
    left: 3px;
    top: 5px;
    height: calc(100% - 10px);
    }
    ul.custom-list li.is_delivered:before {
    background-color: blue; /* Change dot color to blue */
    }

    ul.custom-list li {
    position: relative;
    padding-left: 15px;
    margin-bottom: 15px;
    }

    ul.custom-list li:before {
    content: "";
    display: inline-block;
    width: 8px;
    height: 8px;
    background: red;
    position: absolute;
    left: 0;
    top: 5px;
    border-radius: 10px;
    }

    .agent-select {
    display: inline-block;
    margin-left: 10px;
  }
 
    </style>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                
                <ul class="custom-list">
                @php

               $flag = true;
               $select_block = 'd-none';        
              @endphp 
                    @foreach($order->task as $route)
                    @php
                    if($route->task_type_id ==1)
                    {
                    $type ='Pickup';
                    }else{
                    $type ='Drop Off';

                    if(($route->task_status != 4) && ($flag = true))
                    {
                       $flag  = false;
                    }
                    }

                    $class='';
                    if($route->task_status == 4 || $route->task_status == 2)
                    {
                      $class= 'is_delivered';
                    }
                    @endphp
                    <li  class="{{ $class}}">
                    @if($route->task_status  == 0 )
                    {{ $route->location->address ?? ''}}
                    @endif
                    @if($route->task_type_id  == 1)
                    
                        @if($route->task_status  != 0)
                        <span class="agent-name"> Pickup Location:- {{ $route->location->address}}  Agent Name:-  {{$order->agent->name ?? 'Unassigned'}}  Status:- {{ $route->status ?? 'Unassigned'}}</span>
                        


                        @else
                         

                        
                        <select class="agent-select select_agent @if($flag == false) d-none   @endif" data-id="{{ $order->id}}" task-id="{{$route->id}}">
                            <option value="">Select Agent</option>
                            @foreach($agents as $agent)
                            <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                            @endforeach
                        </select>
                    
                        @endif
                       
                    @else
                    @if($route->task_type_id  == 2)
                    
                    @if($route->task_status  != 0)

                    <span class="agent-name"> Drop-Off Location:-   {{ $route->location->address}}   Agent Name:- {{$order->agent->name ?? 'Unassigned'}}  Status:- {{ $route->status ?? 'Unassigned'}}</span>
                    

                    @endif
                    @endif
                    @endif
                      
                    </li>
                   
                    @endforeach
                </ul>
            </div>
        </div>
    </div>