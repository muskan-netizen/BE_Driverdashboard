<script type="text/javascript">
    $(document).ready(function() {

        function intializeTable()
        {
                let status = $(this).data('status');
                var fdate = $('#datepicker_from').val();
                var tdate = $('#datepicker_to').val();
                var driver = $('#driver-id').val();
                initDataTable('agent-listing', status, fdate , tdate,driver);
        }

         $(document).on("click", ".nav-link", function() {
                let status = $(this).data('status');
                var fdate = $('#datepicker_from').val();
                var tdate = $('#datepicker_to').val();
                var driver = $('#driver-id').val();
                initDataTable('agent-listing', status, fdate , tdate,driver);
            });

       

        // initDataTable();
        setTimeout(function() {
            $('#all-fleets').trigger('click');
        }, 200);

        $(document).on("change", ".date_range_filter", function() {
            intializeTable();
        });
        $(document).on("change", "#driver-id", function() {
            intializeTable();
        });


        function initDataTable(table, status, fdate, tdate,driver) {
            // alert(fdate);
            var fleet_details_route = "{{ url('fleet/details') }}";
            var columnsDynamic =   [
                {
                    data: 'name',
                    name: 'name',
                    orderable: true,
                    searchable: false,
                    "mRender": function(data, type, full) {
                        return '<div class="edit-icon-div"><a href="'+fleet_details_route+'/'+btoa(full.id)+'" target="_blank" class="child-name " fleetId="'+full.id+'">'+data+'</a></div>';
                    }
                },
                {
                    data: 'model',
                    name: 'model',
                    orderable: true,
                    searchable: false
                },
                {
                    data: 'make',
                    name: 'make',
                    orderable: true,
                    searchable: false
                },
                {
                    data: 'registration_name',
                    name: 'registration_name',
                    orderable: true,
                    searchable: false
                },
                {
                    data: 'color',
                    name: 'color',
                    orderable: true,
                    searchable: false
                },
                {
                    data: 'year',
                    name: 'year',
                    orderable: true,
                    searchable: false
                },
                {
                    data: 'driver',
                    name: 'driver',
                    orderable: false,
                    searchable: false,
                    "mRender": function(data, type, full) {
                        return '<div class="edit-icon-div"><a href="javascript:;" class="child-name assignAgent" agentId="'+full.id+'">'+data+'</a></div>';
                    }
                },
                {
                    data: 'created_at',
                    name: 'action',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'updated_at',
                    name: 'action',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                },
            ];
            var table_dy = $('#' + table).DataTable({
                "scrollX": true,
                "destroy": true,
                "serverSide": true,
                "responsive": true,
                "processing": true,
                "iDisplayLength": 10,
                ajax: {
                    url: "{{url('fleet/filter')}}",
                    type : 'GET',
                    data: function(d) {
                        d.search = $('input[type="search"]').val();
                        d.status = status;
                        d.fdate = fdate;
                        d.tdate = tdate;
                        d.driver = driver;
                    }
                },
                columns:columnsDynamic
            });
        }

       
        var mobile_number = '';

        $(document).on("change", "#add-agent-modal .xyz", function(e) {
            var phonevalue = $('.xyz').val();
            $("#countryCode").val(mobile_number.getSelectedCountryData().dialCode);
        });

        function phoneInput() {
            var input = document.querySelector(".xyz");

            var mobile_number_input = document.querySelector(".xyz");
            mobile_number = window.intlTelInput(mobile_number_input, {
                separateDialCode: true,
                hiddenInput: "full_number",
                utilsScript: "{{ asset('telinput/js/utils.js') }}"
               });

        }

        $(document).on("click", ".openModal", function(e) {
            $('#add-agent-modal').modal({
                backdrop: 'static',
                keyboard: false
            });
        });

        jQuery('#onfoot').click();

        /* Get agent by ajax */
        $(document).on("click", ".assignAgent", function(e) {
            e.preventDefault();
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': jQuery('meta[name="csrf-token"]').attr('content')
                }
            });
            e.preventDefault();

            var uid = $(this).attr('agentId');

            $.ajax({
                type: "get",
                url: "{{url('fleet')}}" + '/' + uid + '/driver',
                data: '',
                dataType: 'json',
                success: function(data) {
                    $('#assign-driver-modal').modal({
                        backdrop: 'static',
                        keyboard: false
                    });
                    $('#fleet_id').val(data.fleet.id);
                    $('#selectBox').html(data.agents);
                },
                error: function(data) {
                    // console.log('data2');
                }
            });
        });

        $(document).on("click", ".viewIcon", function(e) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': jQuery('meta[name="csrf-token"]').attr('content')
                }
            });
            e.preventDefault();

            var uid = $(this).attr('agentId');

            $.ajax({
                type: "get",
                url: "<?php echo url('agent'); ?>" + '/' + uid + '/show',
                data: '',
                dataType: 'json',
                success: function(data) {
                    $('#view-agent-modal #viewCardBox').html(data.html);
                    $('#view-agent-modal').modal({
                        backdrop: 'static',
                        keyboard: false
                    });
                    makeTag();
                    phoneInput();
                    //$('.dropify').dropify();
                    // var imgs = $('#profilePic').attr('showImg');

                    // $('#profilePic').attr("data-default-file", imgs);
                    // $('#profilePic').dropify();
                    // $('').dropify();
                },
                error: function(data) {
                    // console.log('data2');
                }
            });
        });

        /* add Team using ajax*/
        
        $(document).on("submit", "#submitAgent", function(e) {
            e.preventDefault();
            var form = document.getElementById('submitAgent');
            var formData = new FormData(form);
            var urls = "{{URL::route('fleet.store')}}";
            saveTeam(urls, formData, inp = '', modal = 'add-agent-modal');
        });

        /* edit Team using ajax*/
        // $(document).on("submit", "#edit-agent-modal #UpdateAgent", function(e) {
        //     e.preventDefault();
        // });


        $(document).on("submit", '#UpdateAgent', function(e) {
            e.preventDefault();
            var form = document.getElementById('UpdateAgent');
            var formData = new FormData(form);
            var urls = document.getElementById('agent_id').getAttribute('url');
            saveTeam(urls, formData, inp = 'Edit', modal = 'edit-agent-modal');
            // console.log(urls);
        });

        function saveTeam(urls, formData, inp = '', modal = '') {

            $.ajax({
                method: 'post',
                headers: {
                    Accept: "application/json"
                },
                url: urls,
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    if (response.status == 'success') {
                        $("#" + modal + " .close").click();
                        location.reload();
                    } else {
                        $(".show_all_error.invalid-feedback").show();
                        $(".show_all_error.invalid-feedback").text(response.message);
                    }
                    return response;
                },
                error: function(response) {
                    if (response.status === 422) {
                        let errors = response.responseJSON.errors;
                        Object.keys(errors).forEach(function(key) {
                            $("#" + key + "Input" + inp + " input").addClass("is-invalid");
                            $("#" + key + "Input" + inp + " span.invalid-feedback").children("strong").text(errors[key][0]);
                            $("#" + key + "Input span.invalid-feedback").show();
                        });
                    } else {
                        $(".show_all_error.invalid-feedback").show();
                        $(".show_all_error.invalid-feedback").text('Something went wrong, Please try Again.');
                    }
                    return response;
                }
            });

        }


        /* Get agent by ajax */
        $(document).on("click", ".submitpayreceive", function(e) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': jQuery('meta[name="csrf-token"]').attr('content')
                }
            });
            e.preventDefault();


            $.ajax({
                type: "post",
                url: "",
                data: '',
                dataType: 'json',
                success: function(data) {
                    $('#edit-agent-modal #editCardBox').html(data.html);
                    $('#edit-agent-modal').modal({
                        backdrop: 'static',
                        keyboard: false
                    });
                    makeTag();
                    //$('.dropify').dropify();
                    var imgs = $('#profilePic').attr('showImg');

                    $('#profilePic').attr("data-default-file", imgs);
                    $('#profilePic').dropify();
                    $('').dropify();
                },
                error: function(data) {
                    // console.log('data2');
                }
            });
        });

        $(document).on("change", ".agent_approval_switch", function(e) {
            var is_approved = $(this).prop('checked') == true ? 1 : 0;
            var agent_id = $(this).data('id');

            $.ajax({
                type: "Post",
                dataType: "json",
                url: "{{ route('agent/approval_status')}}",
                data: {
                    "_token": "{{ csrf_token() }}",
                    'is_approved': is_approved,
                    'id': agent_id
                },
                success: function(data) {
                    if (data.status == 1) {
                        $.NotificationApp.send("", data.message, "top-right", "#5ba035", "success");
                    }
                }
            });
        });

        $(document).on("click", ".agent_approval_button", function(e) {
            if (confirm('Are you sure?')) {
                var agent_id = $(this).data('agent_id');
                var status = $(this).data('status');
                var activeTabDetail = $("#top-tab li a.active").data('rel');
                $.ajax({
                    type: "Post",
                    dataType: "json",
                    url: "{{ route('agent/change_approval_status')}}",
                    data: {
                        "_token": "{{ csrf_token() }}",
                        'status': status,
                        'id': agent_id
                    },
                    success: function(data) {
                        if (data.status == 1) {
                            $('#active_vendor_count').text('('+data.agentIsApproved+')');
                            $('#awaiting_vendor_count').text('('+data.agentNotApproved+')');
                            $('#blocked_vendor_count').text('('+data.agentRejected+')');
                            $.NotificationApp.send("", data.message, "top-right", "#5ba035", "success");
                            setTimeout(function() {
                                $('#' + activeTabDetail).DataTable().ajax.reload();
                            }, 100);
                            
                        }
                    }
                });
            }
        });

    });


    $(document).on('click', '.mdi-delete', function(e) {
      e.preventDefault();
            var r = confirm("Are you sure. Want to delete?");
            if (r == true) {
               var agentid = $(this).attr('agentid');
               $('form#agentdelete'+agentid).submit();
            }
    });

    $(document).on("click", ".fleetDetails", function(e) {
            $('#fleet-detail-modal').modal({
                backdrop: 'static',
                keyboard: false
            });
        });


    $(document).on("click", ".editFleet", function(e) { 
        $.ajax({
            type: "post",
            url: "{{ url('fleet/get-car-detail')}}",
            data: {
                "_token": "{{ csrf_token() }}",
                "fleetId": $(this).attr('data-fleet-id'),
                "action":"edit"
            },
            success: function(resp) {
                $('#add-agent-modal').modal({
                    backdrop: 'static',
                    keyboard: false
                });
                $('#fleetViewCardBox').html(resp);    
                $('#name').val(resp.name);    
                $('#make').val(resp.make);    
                $('#model').val(resp.model);    
                $('#registration_name').val(resp.registration_name);    
                $('#year').val(resp.year);    
                $('#color').val(resp.color);    
                $('#editId').val(resp.id);    

            }
        });
    });


    $(document).on("click", ".fleetDetail", function(e) { 
        $.ajax({
            type: "post",
            url: "{{ url('fleet/get-car-detail')}}",
            data: {
                "_token": "{{ csrf_token() }}",
                "fleetId": $(this).attr('data-id')
            },
            success: function(resp) {
                $('#fleet-modal').modal({
                    backdrop: 'static',
                    keyboard: false
                });
                    $('#fleetViewCardBox').html(resp);      
            }
        });
    });


    $(document).on("click", ".orderDetail", function(e) {
        
        $.ajax({
            type: "post",
            url: "{{ url('fleet/get-order-detail')}}",
            data: {
                "_token": "{{ csrf_token() }}",
                "orderId": $(this).attr('data-id')
            },
            success: function(resp) {
                $('#order-modal').modal({
                    backdrop: 'static',
                    keyboard: false
                });
                    $('#orderViewCardBox').html(resp);      
            }
        });
    });


</script>
