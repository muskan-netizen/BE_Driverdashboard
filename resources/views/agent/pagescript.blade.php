<script type="text/javascript">
    $(document).ready(function() {
        $(document).on("click", ".nav-link", function() {
            let rel = $(this).data('rel');
            // console.log(rel);
            let status = $(this).data('status');
            initDataTable(rel, status);
            // setTimeout(function() {
            //     $('#' + rel).DataTable().ajax.reload();
            // }, 500);
        });

        // initDataTable();
    //     setTimeout(function() {
    //         // $('#active-vendor').trigger('click');
    // }, 200);

        $(document).on("change", "#geo_filter", function() {
            let rel = $('.nav-link.active').data('rel');
            let status = $('.nav-link.active').data('status');
            initDataTable(rel, status);
            // setTimeout(function() {
            //     $('#' + rel).DataTable().ajax.reload();
            // }, 500);
        });

        $(document).on("change", "#tag_filter", function() {
            let rel = $('.nav-link.active').data('rel');
            let status = $('.nav-link.active').data('status');
            initDataTable(rel, status);
            // setTimeout(function() {
            //     $('#' + rel).DataTable().ajax.reload();
            // }, 500);
        });

        function padDigits(number, digits) {
            return Array(Math.max(digits - String(number).length + 1, 0)).join(0) + number;
        }

        function initDataTable(table, status) {
            var edit_agent_route = "{{ route('agent.edit', ':id') }}";
            var geo_filter = $("#geo_filter").val();
            var tag_filter = $("#tag_filter").val();
            $.ajax({
            url: "{{ url('agent/filter') }}",
            method: "GET",
            data: {
                search: $('input[type="search"]').val(),
                imgproxyurl: '{{$imgproxyurl}}',
                geo_filter: geo_filter,
                tag_filter: tag_filter,
                status: status
            },
            success: function(data) {
                let name = table + "-data";
                $('.' + name).empty();
                 $('.' + name).append(data.html);
                $('.pagination').html(data.pagination);

            },
            error: function(xhr, error, thrown) {
                console.log('AJAX error:', error);
                console.log('AJAX error details:', thrown);
            }
        });


        }

        var tagList = "{{$showTag}}";
        tagList = tagList.split(',');

        function makeTag() {
            $('.myTag1').tagsInput({
                'autocomplete': {
                    source: tagList
                }
            });
        }
        var mobile_number = '';

        $(document).on("change", "#add-agent-modal .xyz", function(e) {
           var phonevalue = $('.xyz').val();
            $("#countryCode").val(mobile_number.getSelectedCountryData().dialCode);
        });

        function phoneInput() {
            var input = document.querySelector(".xyz");

            $("#phone_number").intlTelInput({
                separateDialCode:true,
                preferredCountries:["{{getCountryCode()}}"],
                initialCountry:"{{getCountryCode()}}",
                utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.18/js/utils.js",
            });

        }

        $(document).on("click", ".openModal", function(e) {
            $('#add-agent-modal').modal({
                backdrop: 'static',
                keyboard: false
            });

            makeTag();

            phoneInput();

            select2();
            var instance = $("[name=phone_number]");
            instance.intlTelInput();
            $("#countryCode").val(instance.intlTelInput('getSelectedCountryData').dialCode);

        });

        jQuery('#onfoot').click();

        $(document).on('click', '.click', function() {
            var radi = $(this).find('input[type="radio"]');
            radi.prop('checked', true);
            var check = radi.val();
            var act = radi.attr('act');
            var walk = "{{ asset('assets/icons/walk.png') }}";
            var cycle ="{{ asset('assets/icons/cycle.png') }}";
            var bike ="{{ asset('assets/icons/bike.png') }}";
            var car ="{{ asset('assets/icons/car.png') }}";
            var truck = "{{ asset('assets/icons/truck.png') }}";
            var auto = "{{ asset('assets/icons/auto.png') }}";
            switch (check) {
            case "1":
            walk = "{{ asset('assets/icons/walk_blue.png') }}";
            break;
            case "2":
            cycle = "{{ asset('assets/icons/cycle_blue.png') }}";
            break;
            case "3":
            bike = "{{ asset('assets/icons/bike_blue.png') }}";
            break;
            case "4":
            car = "{{ asset('assets/icons/car_blue.png') }}";
            break;
            case "5":
            truck = "{{ asset('assets/icons/truck_blue.png') }}";
            break;
            case "6":
            auto = "{{ asset('assets/icons/auto_blue.png') }}";
            break;
            }
            setIcon (act ,walk,cycle,bike,car,truck,auto);

            });
            function setIcon (act ,walk,cycle,bike,car,truck,auto){

            $("#foot_" + act).attr("src", walk);
            $("#cycle_" + act).attr("src",cycle);
            $("#bike_" + act).attr("src", bike);
            $("#cars_" + act).attr("src",car);
            $("#trucks_" + act).attr("src",truck);
            $("#auto_" + act).attr("src",auto);
        }

        function select2(){
            $("#warehouse_id").select2({
                allowClear: true,
                width: "resolve",
                placeholder: "Select Warehouse"
            });
        }

        /* Get agent by ajax */
        $(document).on("click", ".editIcon", function(e) {
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
                url: "<?php echo url('agent'); ?>" + '/' + uid + '/edit',
                data: '',
                dataType: 'json',
                success: function(data) {
                    $('#edit-agent-modal #editCardBox').html(data.html);
                    $('#edit-agent-modal').modal({
                        backdrop: 'static',
                        keyboard: false
                    });
                    makeTag();
                    phoneInput();
                    $('.dropify').dropify();
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
            // $(document).on('click', '.submitAgentForm', function() {
            var form = document.getElementById('submitAgent');
            var formData = new FormData(form);
            var urls = "{{URL::route('agent.store')}}";
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
                let table = $('.nav-link.active').data('rel');
                let data_status = $('.nav-link.active').data('status');
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
                            initDataTable(table,data_status);

                        }
                    }
                });
            }
        });

    });


    $(document).on('click', '.mdi-delete', function(e) {
      e.preventDefault();
            var r = confirm("Are you sure?");
            if (r != true) {
                return false;
            }
            var agentid = $(this).attr('agentid');
            $('form#agentdelete'+agentid).submit();
    });

    function updateData(page = 1) {
        let table = $('.nav-link.active').data('rel');
        let status = $('.nav-link.active').data('status');
        var edit_agent_route = "{{ route('agent.edit', ':id') }}";
            var geo_filter = $("#geo_filter").val();
            var tag_filter = $("#tag_filter").val();
            $.ajax({
            url: "{{ url('agent/filter') }}",
            method: "GET",
            data: {
                search: $('input[type="search"]').val(),
                imgproxyurl: '{{$imgproxyurl}}',
                geo_filter: geo_filter,
                tag_filter: tag_filter,
                status: status,
                page:page
            },
            success: function(data) {
                let name = table + "-data";

                $('.' + name).empty();
                $('.' + name).append(data.html);
                $('.pagination').html(data.pagination);
        },
        error: function(xhr, error, thrown) {
            console.log('AJAX error:', error);
            console.log('AJAX error details:', thrown);
        }
    });
}

$(document).on('click', '.pagination a', function(e) {
    e.preventDefault();
    let page = $(this).attr('href').split('page=')[1];

    updateData(page);
});
$(document).on('keyup', '.search-btn', function() {


    updateData();
});

</script>
