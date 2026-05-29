<option value="">Select Warehouse</option>
@foreach ($category->warehouses as $warehouse)
    <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
@endforeach