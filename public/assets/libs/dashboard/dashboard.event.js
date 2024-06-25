$(function(){ 
    $('#wrapper').addClass('dshboard');
    $(".timeago").timeago();
// alert()
    if($("#newmarker_map_data").val()!=''){
            olddata  = JSON.parse($("#newmarker_map_data").val());
        }

    
    if($("#uniquedrivers_map_data").val()!=''){
            allroutes  = JSON.parse($("#uniquedrivers_map_data").val());
    }

    // if ($("#agents_map_data").val() != '') {
    //     allagent = JSON.parse($("#agents_map_data").val());
    // }
    if(agentsLatLong != ''){
        allagent = JSON.parse(agentsLatLong);
    }
    

    if ($("#agentslocations_map_data").val() != '') {
        defaultmaplocation = JSON.parse($("#agentslocations_map_data").val());
        defaultlat = parseFloat(defaultmaplocation[0].lat);
        defaultlong = parseFloat(defaultmaplocation[0].long);
    }

    $("#team_id").select2({
        allowClear: true,
        width: "resolve",
        placeholder: "Select Team"
    });
    $('#dummy').hide()

    $("#agent_id").select2({
        allowClear: true,
        width: "resolve",
        placeholder: "Select Agent"
    });

    //initialize the map first
    initMap(1)
    ListenAgentLogChannel();
});

$(document).on('click', '.checkUserStatus', function() {
    loadTeams(1, 1);
});

$(document).on('click', '.checkUserRoutes', function() {
    loadOrders(1, 1);
});

$(document).on('change', '#team_id', function() {
    loadTeams(1, 1);
});

$(document).on('change', '#agent_id', function() {
    loadOrders(1, 1);
});

$(document).on('keyup', '#search_by_name', function() {
    loadTeams(1, 1);
});

// on submiting optimization popup
$(document).on('click','.submitoptimizeForm', function() {
    var driverStartTime = $('.driverStartTime').val();
    var driverTaskDuration = $('.driverTaskDuration').val();
    var driverBrakeStartTime = $('.driverBrakeStartTime').val();
    var driverBrakeEndTime = $('.driverBrakeEndTime').val();
    var sortingtype = $('#optimizeType').val();
    var err = 0;
    if (driverStartTime == '') {
        $('#DriverStartTime span').css('display', 'block');
        err = 1;
    }

    if (driverTaskDuration == '') {
        $('#DriverTaskDuration span').css('display', 'block');
        err = 1;
    }
    if (driverBrakeStartTime == '') {
        $('#DriverBrakeStartTime span').css('display', 'block');
        err = 1;
    }

    if (driverBrakeEndTime == '') {
        $('#DriverBrakeEndTime span').css('display', 'block');
        err = 1;
    }

    if (err == 0) {
        $('.routetext').text('Optimizing Route');
        $('#optimize-route-modal').modal('hide');
        spinnerJS.showSpinner()
        var formdata = $('form#optimizerouteform').serialize();
        var formurl = optimizeArrangeRouteUrl;
        if (sortingtype == 'optimize') {
            formurl = optimizeRouteUrl;
        }
        $.ajax({
            type: 'POST',
            url: formurl,
            headers: {
                'X-CSRF-Token': X_CSRF_TOKEN,
            },
            data: formdata,
            success: function(response) {
                if (response != "Try again later") {
                    loadTeams(1, 1);
                    loadOrders(1, 1);
                    spinnerJS.hideSpinner();
                } else {
                    alert(response);
                    spinnerJS.hideSpinner();
                }
            },
            error: function(response) {

            }
        });
    }

});

$(document).on('click', '.unassigned-badge', function() {
    $("#route-assign-agent-modal").modal('show');
    var order_id = $(this).data('id');
    $('#order_id').val(order_id);
});

$(document).on('click', '.submitassignAgentForm', function() {
    var order_id = Array($('#order_id').val());
    var agent_id = $('#select_agent_id').val();
    if (agent_id != '') {
        $.ajax({
            type: "POST",
            url: assignAgentUrl,
            data: {
                _token: CSRF_TOKEN,
                orders_id: order_id,
                agent_id: agent_id
            },
            success: function(msg) {
                $.toast({
                    heading: "Success!",
                    text: `${getAgentNomenclature} assigned successfully.`,
                    showHideTransition: 'slide',
                    bgColor: 'green',
                    textColor: '#eee',
                    allowToastClose: true,
                    hideAfter: 5000,
                    stack: 5,
                    textAlign: 'left',
                    position: 'top-right'
                });
                $("#route-assign-agent-modal").modal('hide');
                loadOrders(1, 1);
            },
            error: function(errors) {
                $.toast({
                    heading: "Error!",
                    text: `${getAgentNomenclature} can not be assigned.`,
                    showHideTransition: 'slide',
                    bgColor: 'red',
                    textColor: '#eee',
                    allowToastClose: true,
                    hideAfter: 5000,
                    stack: 5,
                    textAlign: 'left',
                    position: 'top-right'
                });
                $("#route-assign-agent-modal").modal('hide');
                loadOrders(1, 1);
            }
        });
    }
});


$(document).on('change', 'input[type=radio][name=driver_start_location]', function() {
    if (this.value == 'current') {
        $('#addressBlock').css('display', 'none');
        $('#addressTaskBlock').css('display', 'none');
    } else if (this.value == 'select') {
        $('#addressBlock').css('display', 'block');
        $('#addressTaskBlock').css('display', 'none');
    } else if (this.value == 'task_location') {
        $('#addressTaskBlock').css('display', 'block');
        $('#addressBlock').css('display', 'none');
    }
});

$(document).on('click', '.view_route-btn', function (e) {
    let id = $(this).data('id');
    drawRoute(id)
});

$(document).on('click', '#load-more', function(e) {
    let url = $(this).data('url');

    closeAllAccordian();
    spinnerJS.showSpinner();

    var checkuserroutes = $('input[name="user_routes"]:checked').val();
    var agent_id = $('#agent_id').val();
    $(this).remove();
    $.ajax({
        type: 'POST',
        url: url,
        headers: {
            'X-CSRF-Token': X_CSRF_TOKEN,
        },
        data: {
            'agent_id': agent_id,
            'checkuserroutes': checkuserroutes,
            'is_load_html': 1,
            'routedate': $("#basic-datepicker").val()
        },
        success: function(result) {
            $("#handle-dragula-left0").append(result);
            spinnerJS.hideSpinner();
        },
        error: function(data) {
            Swal.fire({
                icon: 'error',
                title: 'Oops',
                text: 'There is some issue. Try again later',
            });
            spinnerJS.hideSpinner();
        }
    });
})

$(document).on('click', '#load-more-teams', function(e) {
    let url = $(this).data('url');

    $('#load-more-teams').remove();
    spinnerJS.showSpinner();
    var checkuserstatus = $('input[name="user_status"]:checked').val();
    $.ajax({
        type: 'POST',
        url: url,
        headers: {
            'X-CSRF-Token': X_CSRF_TOKEN,
        },
        data: {
            'userstatus': checkuserstatus,
            'is_load_html': 1,
            'routedate': $("#basic-datepicker").val()
        },
        success: function(result) {
            //if Html is required to load or not, for agent's log it is not required

            $("#teams_container .teams-data:last").after(result);

            spinnerJS.hideSpinner();

        },
        error: function(data) {
            Swal.fire({
                icon: 'error',
                title: 'Oops',
                text: 'There is some issue. Try again later',
            });

            spinnerJS.hideSpinner();

        }
    });
});

$(document).on(".applyBtn", "click", function () {
    var date = $("#birthdatepicker").val();
    order_details((val = ""), date, (start_time = ""), (endtime = ""));
    var value = $(this).attr("data-value");
    loadTeams(0, 0, value, 0);
});

$(document).on(".order_number", "click", function () {
    var val = $(this).attr("data-orderNum");
    $.ajax({
        type: "POST",
        url: get_agent_distance,
        headers: {
            "X-CSRF-Token": X_CSRF_Token,
        },
        data: {
            order_number: val,
        },
        success: function (response) {
            $("#agentmenu").empty();
            jQuery.each(response, function (index, element) {
                if (element.distance != null) {
                    $("#agentmenu").append(
                        "<option value=" +
                            element.id +
                            ">" +
                            element.name +
                            " <span>" +
                            element.distance +
                            "</span></option>"
                    );
                } else {
                    $("#agentmenu").append(
                        "<option value=" +
                            element.id +
                            ">" +
                            element.name +
                            "</option>"
                    );
                }
            });
        },
    });
});

$(document).on("#show_path", "click", function () {
    var order_details = $("#order_id").val();
    $.ajax({
        type: "POST",
        url: order_detail,
        headers: {
            "X-CSRF-Token": X_CSRF_Token,
        },
        data: {
            order_number: order_details,
        },
        success: function (response) {
            pathlatlng = pathlatLong(response.order_details.task);
            agentlatlng = agentlatLong(response.agent_logs);
            initMap(pathlatlng, agentlatlng, "ShowPath");
        },
    });
});

$(document).on(".dispatch", "click", function () {
    $(".filter-dropdown").slideToggle();
});

$(document).on(".checking", "click", function () {
    var agent_location = $(".dark_theme3").val();
});

$(document).on("change", "#fencemenus", function () {
    var fence_id = $("#fencemenus").val();
    $.ajax({
        type: "POST",
        url: get_geo_fence_agents,
        headers: {
            "X-CSRF-Token": X_CSRF_Token,
        },
        data: {
            geo_id: fence_id,
        },
        success: function (response) {
            $("#agentmenu").empty();
            jQuery.each(response.agents, function (index, element) {
                $("#agentmenu").append(
                    "<option value=" +
                        element.agent.id +
                        ">" +
                        element.agent.name +
                        "</option>"
                );
            });
        },
    });
});

$(document).on("change","#tagmenus", function () {
    var tag_id = $("#tagmenus").val();
    $.ajax({
        type: "POST",
        url: get_agents_tags,
        headers: {
            "X-CSRF-Token": X_CSRF_Token,
        },
        data: {
            tag_id: tag_id,
        },
        success: function (response) {
            $("#agentmenu").empty();
            if (response.agents != null) {
                jQuery.each(response.agents, function (index, element) {
                    $("#agentmenu").append(
                        "<option value=" +
                            element.agents.id +
                            ">" +
                            element.agents.name +
                            "</option>"
                    );
                });
            }
        },
    });
});