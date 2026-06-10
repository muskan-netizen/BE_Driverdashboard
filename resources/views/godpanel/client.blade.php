@extends('layouts.god-vertical', ['title' => 'Clients'])

@section('css')
<link href="{{asset('assets/libs/dropzone/dropzone.min.css')}}" rel="stylesheet" type="text/css" />
<link href="{{asset('assets/libs/dropify/dropify.min.css')}}" rel="stylesheet" type="text/css" />
@endsection

@section('content')

<!-- Start Content-->
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Clients</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-sm-8">
                            <div class="text-sm-left">
                                @if(\Session::has('success'))
                                <div class="alert alert-success">
                                    <span>{!! \Session::get('success') !!}</span>
                                </div>
                                @endif

                                @if(\Session::has('error'))
                                <div class="alert alert-error">
                                    <span>{!! \Session::get('error') !!}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-sm-4 text-right">
                            <a class="btn btn-blue waves-effect waves-light text-sm-right"
                                href="{{route('client.create')}}"><i class="mdi mdi-plus-circle mr-1"></i> Add
                                Client</a>
                        </div>

                    </div>

                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap table-striped" id="products-datatable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Database Name</th>
                                    <th>Client Code</th>
                                    <th style="width: 85px;">Action</th>
                                    <th style="width: 85px;">Lumen microservices</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($clients as $client)
                                <tr>
                                    <td class="table-user">
                                        <a href="javascript:void(0);"
                                            class="text-body font-weight-semibold">{{$client->name}}</a>
                                    </td>
                                    <td>
                                        {{$client->email}}
                                    </td>
                                    <td>
                                        {{$client->phone_number}}
                                    </td>
                                    <td>
                                        {{$client->database_name}}
                                    </td>
                                    <td>
                                        {{ $client->code }}
                                    </td>
                                    <!-- <td>
                                        <span class="badge bg-soft-success text-success">Active</span>
                                    </td> -->


                                    <td>
                                        <div class="form-ul" style="width: 60px;">
                                            <div class="inner-div"> <a href1="#" href="{{route('client.edit', $client->id)}}"  class="action-icon editIconBtn"> <i class="mdi mdi-square-edit-outline"></i></a></div>
                                            <div class="inner-div">
                                                <form id="clientdelete{{$client->id}}" method="POST" action="{{route('client.destroy', $client->id)}}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <div class="form-group">
                                                        <button type="button" class="btn btn-primary-outline action-icon"> <i class="mdi mdi-delete" clientid="{{ $client->id }}"></i></button>

                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="col-md-12">
                                            <div class="form-group d-flex justify-content-between mb-3">
                                                <label for="lumen" class="mr-2 mb-0">{{__("Enable")}} </label>
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input is_lumen" id="is_lumen_enabled{{$client->id}}" name="is_lumen_enabled" data-id = "{{$client->id}}" @if($client->is_lumen_enabled == 1) checked  @endif>
                                                        <label class="custom-control-label" for="is_lumen_enabled{{$client->id}}"></label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination pagination-rounded justify-content-end mb-0">
                        {{ $clients->links() }}
                    </div>

                </div> <!-- end card-body-->
            </div> <!-- end card-->
        </div> <!-- end col -->
    </div>


</div>
@endsection

@section('script')
<script src="{{asset('assets/libs/dropzone/dropzone.min.js')}}"></script>
<script src="{{asset('assets/libs/dropify/dropify.min.js')}}"></script>
<!-- Page js-->
<script src="{{asset('assets/js/pages/form-fileuploads.init.js')}}"></script>

<script>
      $(document).ready(function() {
        $.ajaxSetup({
            headers: {'X-CSRF-TOKEN': $('input[name="_token"]').val()}
        });

    });
    $('.mdi-delete').click(function(){
            
            var r = confirm("{{__('Are you sure?')}}");
            if (r == true) {
               var clientid = $(this).attr('clientid');
               $('form#clientdelete'+clientid).submit();

            }
        });


        $('.is_lumen').on('change',function(){
        var is_lumen  = 0;
        var client_id  = $(this).data('id');
        if ($(this).is(":checked")) {
            is_lumen  = 1;
        }else{
            is_lumen  = 0;

        }

        $.ajax({
                    url: "{{route('enable-lumen-service')}}",
                    type: "POST",
                    dataType: 'json',
                    data: 
                    { 
                      client_id:client_id,
                      is_lumen:is_lumen
                    },
                    headers: {Accept: "application/json"},
                    success: function(response) {
                        console.log('in success');
                    }
                });
      

});

    </script>

<!-- @parent

@if(count($errors->add) > 0)
<script>
$(function() {
    $('#add-client-modal').modal({
        show: true
    });
});
</script>
@elseif(count($errors->update) > 0)
<script>
$(function() {
    $('#update-client-modal').modal({
        show: true
    });
});
</script>
@endif
@if(\Session::has('getClient'))
<script>
$(function() {
    $('#update-client-modal').modal({
        show: true
    });
});
</script>
@endif -->
@endsection