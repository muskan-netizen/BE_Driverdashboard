

<table class="table table-striped dt-responsive nowrap w-100 agents-datatable" id="agents-datatable">
    <thead>
        <tr>
            @if (!isset($status) || $status == 'unassigned')
            <th><input type="checkbox" class="all-driver_check" name="all_driver_id" id="all-driver_check"></th>
            @endif
            <th class="sort-icon">{{__("Order Number")}} <i class="fa fa-sort ml-1" aria-hidden="true"></i></th>
            <th class="sort-icon">{{__("Customer ID")}} <i class="fa fa-sort ml-1" aria-hidden="true"></i></th>
            <th class="sort-icon">{{__("Customer")}} <i class="fa fa-sort ml-1" aria-hidden="true"></i></th>
            <th class="sort-icon">{{__("Phone No.")}} <i class="fa fa-sort ml-1" aria-hidden="true"></i></th>
            <th class="sort-icon">{{__("Type")}} <i class="fa fa-sort ml-1" aria-hidden="true"></i></th>
            <th class="sort-icon">{{__(getAgentNomenclature()) }} <i class="fa fa-sort ml-1" aria-hidden="true"></i></th>
            <th class="sort-icon">{{__("Due Time")}} <i class="fa fa-sort ml-1" aria-hidden="true"></i></th>
            <th class="routes-head">{{__("Routes")}}</th>
            <th>{{__("Created At")}}</th>
            <th style="width: 85px;">{{__("Action")}}</th>
        </tr>
    </thead>
    <tbody style="height: 8%; overflow: auto !important;">
        @foreach($order as $orders)
            <tr>
               
                <td>{{ $orders->order_number }}</td>
                <td>{{ $orders->customer_id }}</td>
                <td>{{ $orders->customer_name }}</td>
                <td>{{ $orders->phone_number }}</td>
                @if($orders->is_return != 1)
                <td>Normal</td>
                @else
                <td>Return</td>
                @endif
                @if (!isset($status) || $status == 'unassigned')
                <td><input type="checkbox" class="all-driver_check" name="all_driver_id" id="all-driver_check"></td>
                @else
                <td>{{ $orders->agent_name }}</td>
            @endif
               
                <td>{{ $orders->order_time }}</td>
                <td>{{ $orders->address }}</td>
                <td>{{ $orders->created_at }}</td>
                <td style="width: 85px;">{{ __("Action") }}</td>
            </tr>
        @endforeach
    </tbody>
</table>



<script>
    $(document).ready(function() {
        var dataTable = $('#agents-datatable').DataTable({
        });
    });
</script>