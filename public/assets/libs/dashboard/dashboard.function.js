
function agent_html(data) {
    return `<div id="agent_tab_${
        data.id
    }" class="alTabBoxOuter p-2 agent_detail" agent_value="${
        data.id
    }" agent_name="${
        data.name
    }"> <div class="d-flex align-items-center justify-content-between"> <div class="d-flex align-items-start justify-content-between"> <div class="me-2 position-relative"> <span class="alAgentFirtsLetter fs-3 bg-primary rounded-circle text-white">
    ${data.name.substr(0, 1)} 
    </span> <span class="position-absolute bottom-0 start-100 translate-middle p-1 .bg-primary rounded-circle"></span> </div> <div class="alAgentsDetails w-100"> <div class="d-flex align-items-start justify-content-around"> <div class="me-2"> <b class="alAgentsNameBox text-truncate"><span class="orderId"></span><span class="alAgentsName">
    ${data.agent_id} 
    </span></b> <b class="alAgentsNameBox text-truncate"><span class="orderId"></span><span class="alAgentsName">${
        data.name
    } </span></b>  <span class="alAgentsPhone d-block">${data.phone_number} 
    </span> <ul class="p-0 m-0 d-flex alAgentOtherInfo"> <li class="me-3"><span class="alAgentVehicle pe-1 me-1 border-end">${
        data.vehicle_name ? data.vehicle_name : ""
    } 
    </span> <span class="alAgentTransaction"> Cash</span> </li> <li><span class="alAgentTimeTake">7 minutes</span></li> </ul> </div> <div class="alAgentsChatBox d-flex"> <span style="margin-top: -8px;"><i class="mdi-message-processing mdi mdi-24px text-primary"></i></span> <span class="alAgentsTaskBox text-center"><span class="alAgentsTaskInner rounded-circle border border-light px-1 py-0 mb-2">${
        data.complete_order_count ? data.complete_order_count : 0
    }</span> <small> Task </small></span> </div> </div> </div> </div> <div class="text-right"> <span><i style="font-size: 30px;" class="uil-angle-right agent_detail" agent_value="${
        data.id
    }"
    ></i></span> </div> </div> <hr class="my-2"> </div>`;
}


function order_details(val, date, start_time, end_time) {
    var checkorderstatus = val;
    if (checkorderstatus == "") {
        var checkorderstatus = "unassigned";
    }
    start_time = $(".start_time").val();

    end_time = $(".end_time").val();

    $.ajax({
        type: "POST",
        url: get_tasks_status,
        headers: {
            "X-CSRF-Token": X_CSRF_Token,
        },
        data: {
            date: date,
            status: checkorderstatus,
            start_time: start_time,
            end_time: end_time,
        },
        success: function (response) {
            allorders = response.orders;
            alltask = response.task;
            $("#unassigned").empty();
            $("#completed_tab1,.tasks").hide();
            $("#unassigned_task").text(response.unassigned_task);
            $("#assigned_task").text(response.assigned_task);
            $("#completed_task").text(response.complete_task);
            $("#pre_book_task").text(response.pre_book_task);
            $("#unassigned").empty().html(response.html);
            $(".check_task_btn").hover(
                function () {
                    $(this).css("background-color", "#1394ff");
                    $(this).text("Show On Map");
                },
                function () {
                    $(this).css("background-color", "#696969");
                    $(this).text(checkorderstatus);
                }
            );
            jQuery.each(response.geo, function (index, element) {
                $("#fencemenus").append(
                    "<option value=" +
                        element.id +
                        ">" +
                        element.name +
                        "</option>"
                );
            });

            jQuery.each(response.agent_tag, function (index, element) {
                $("#tagmenus").append(
                    "<option value=" +
                        element.id +
                        ">" +
                        element.name +
                        "</option>"
                );
            });
        },
    });
}
$(document).on(".agent_detail", "click", function () {
    var agent_id = $(this).attr("agent_value");
    var team_name = $(this).attr("agent_name");

    $("#all_agents").text(team_name);
    agent_details(agent_id);
});

function agent_details(agent_id) {
    $("#freeagent").empty();
    $("#busyagents").show();
    $.ajax({
        type: "POST",
        url: get_agent_details,
        headers: {
            "X-CSRF-Token": X_CSRF_Token,
        },
        data: {
            id: agent_id,
        },
        success: function (response) {
            var agent_details = response;
            $("#agent_names").text(agent_details.agents.name);
            $("#agent_number").text(agent_details.agents.phone_number);
            $("#wallet_balance").text(agent_details.agents.wallet.balance);
            $("#vehicle_type").text(agent_details.agents.vehicle_type.name);
            if (agent_details.agents.agent_id != null) {
                $("#agent_number1").text(agent_details.agents.agent_id);
                $("#agent-id").text("#" + agent_details.agents.agent_id);
            }
            if (agent_details.agents.agentlog != null) {
                $("#device_type").text(
                    agent_details.agents.agentlog.device_type
                );
                $("#battery_level").text(
                    agent_details.agents.agentlog.battery_level
                );
                $("#app_version").text(
                    agent_details.agents.agentlog.app_version
                );
            }
            if (agent_details.orders != null) {
                $("#tasks").empty();
                jQuery.each(agent_details.orders, function (index, element) {
                    if (element.task[0].location != null) {
                        $("#tasks").append(
                            '<div class="alTaskAddressPickup mb-2 ps-4"> <div class="d-flex align-items-stretch justify-content-between"> <div class="alTaskNameDate pe-4"> <span class="alPickDate d-block ">' +
                                element.order_time +
                                '</span> <span class="alPickName d-block text-truncate ">' +
                                element.task[0].location.address +
                                '</span> </div> <div class="alTaskStatus text-right"> <span class="badge rounded-0 d-block text-bg-success px-2">' +
                                element.status +
                                '</span> </div> </div> <div class="d-flex align-items-center"> <span class="alPickAddress text-truncate"></span> <div><a href="javascript:void(0)" class="agent_detail_check"data-id="' +
                                element.id +
                                '" data-values="' +
                                element.order_number +
                                ' " >Check Order Details</a></div>    </div> </div>'
                        );
                    }
                });
            }
            if (agent_details.agents.agentlog != null) {
                agent_log = agent_details.agents.agentlog;
                var result = [];
                result.push(
                    {
                        lat: parseFloat(agent_log.lat),
                        lng: parseFloat(agent_log.long),
                    },
                    agent_details.agents.is_available,
                    agent_details.agents.name
                );
                AgentSpeificLocationMap(result);
            }
        },
    });
}
$(document).on(".cross", "click", function () {
    $("#completed_tab1").css("display", "none");
    var dates = $("#birthdatepicker").val();
    var val = $(".current_task_status").val();
    order_details(val, dates, (start_time = ""), (endtime = ""));
});
$(document).on(".team_detail", "click", function () {
    var team_details = $(this).attr("team_value");
    var team_name = $(this).text();
    $("#all_teams").text(team_name);
    var value = $(this).attr("data-value");
    loadTeams(0, 0, value, team_details);
});
$(document).on(".agent_detail_check", "click", function () {
    var order_details = $(this).attr("data-values");
    var order_id = $(this).attr("data-id");
    var i = 0;
    $("#unassigned").empty();
    $("#completed_tab1").show();
    $("#completed_tab1").css("display", "contents");
    get_task_lat_lng(order_id);
    if (i == 0) {
        get_details("", order_details);
    }
    i++;
});

$(document).on("#sub", "click", function () {
    var order_number = $("#order_num").val();
    var agent_id = $("#agentmenu").val();
    var order_status = $("#order_status").val();

    $.ajax({
        type: "POST",
        url: assign_agent,
        headers: {
            "X-CSRF-Token": X_CSRF_Token,
        },
        data: {
            order_number: order_number,
            agent_id: agent_id,
            order_status: order_status,
        },
        success: function (response) {
            if (response.status == "Success") {
                $("#assignAgentmodal").hide();
                $(".modal-backdrop.show").remove();
                swal(
                    "Sucess!",
                    "Your order Has been assign Successfully!",
                    "success"
                );
                var dates = $("#birthdatepicker").val();
                var val = $(".current_task_status").val();
                order_details(val, dates, (start_time = ""), (endtime = ""));
            } else {
                swal("Oops...", "Something went wrong!", "error");
            }
        },
    });
});
$(document).on(".check_task_btn", "click", function () {
    var orderid = $(this).data("orderid");
    get_task_lat_lng(orderid);
});

function get_task_lat_lng(orderid) {
    $.ajax({
        type: "POST",
        url: order_tasks,
        headers: {
            "X-CSRF-Token": X_CSRF_Token,
        },
        data: { orderid: orderid },
        success: function (response) {
            const output = mapData(response.data);
            focusMap(output);
        },
        error: function (response) {},
    });
}

function mapData(data) {
    
    const output = data.map(function (element) {
        if (element.location != null) {
            var result = [];
            result.push(
                {
                    lat: parseFloat(element.location.latitude),
                    lng: parseFloat(element.location.longitude),
                },
                `${element.location.address}`,
                element.task_type_id,
                element.task_status,
                element.order_id,
                element.task_number,
                element.order.customer.name
            );
            return result;
        }
        result = "test";
        return result;
    });
    return output;
}

function maplatLong(data) {
    const latlong = data.map(function (element) {
        if (element.agentlog != null) {
            var result = [];
            result.push(
                {
                    lat: parseFloat(element.agentlog.lat),
                    lng: parseFloat(element.agentlog.long),
                },
                element.is_available,
                element.name,
                element.id,
                element.is_busy,
                element.agent_id
            );
            return result;
        }
        result = "test";
        return result;
    });
    return latlong;
}

// function pathlatLong(data) {
//     const latlong = data.map(function (element) {
//         var lat=element.location.latitude;

//             var result = [];
//             result.push({ lat: parseFloat(element.location.latitude), lng: parseFloat(element.location.longitude) });
//             return result;
//     });
//     return latlong;
// }
function pathlatLong(data) {
    const latlong = data.map(function (element) {
        var lat = element.location.latitude;

        var result = [];
        result.push({
            lat: parseFloat(element.location.latitude),
            lng: parseFloat(element.location.longitude),
        });
        return result;
    });
    return latlong;
}
// function AgentLocationMap(result) {
//     const infoWindow = new google.maps.InfoWindow({
//         content: "",
//         disableAutoPan: false,
//     });
//     var bounds = new google.maps.LatLngBounds();
//     const labels = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
//     const marks = result.map(
//         (
//             [
//                 position,
//                 is_available,
//                 agent_name,
//                 agent_id,
//                 is_busy,
//                 agentPrimaryId,
//             ],
//             i
//         ) => {
//             if (position != "t") {
//                 const geocoder = new google.maps.Geocoder();
//                 image = agenticon(is_available, is_busy);
//                 const label = labels[i % labels.length];
//                 bounds.extend(position);

//                 const marker = new google.maps.Marker({
//                     position,
//                     map,
//                     title: `${i + 1}. ${agent_name}`,
//                     icon: image,
//                     agent_id: agentPrimaryId ?? "",
//                 });
//                 geocoder.geocode(
//                     { location: { position } },
//                     function (results, status) {
//                         if (status === "OK") {
//                             if (results[0]) {
//                                 // Set the marker title to the formatted address
//                                 marker.setTitle(results[0].formatted_address);
//                             }
//                         }
//                     }
//                 );

//                 mark.push({ id: agent_id, data: marker });

//                 map.fitBounds(bounds);
//                 // Add a click listener for each marker, and set up the info window.

//                 marker.addListener("click", () => {
//                     agent_details(agent_id);
//                     infoWindow.close();
//                     infoWindow.setContent(marker.getTitle());
//                     infoWindow.open(marker.getMap(), marker);
//                     markers.push(marker);
//                 });
//                 //     const markerCluster = new markerClusterer.MarkerClusterer({ map, marks});
//                 return marker;
//             }
//         }
//     );
// }

function agentlatLong(agentLogs) {
    var newArray = [];
    $.each(agentLogs, function (index, value) {
        newArray.push({
            lat: parseFloat(value.agent_logs.lat),
            lng: parseFloat(value.agent_logs.long),
        });
    });
    return newArray;
}

function specificMap(result) {
    var cluster_filter = $(".dark_theme2").val();
    if (cluster_filter == "1") {
        const infoWindow = new google.maps.InfoWindow({
            content: "",
            disableAutoPan: false,
        });
        // var bounds = new google.maps.LatLngBounds();
        // Create the markers.
        const labels = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        const markers = result.map(
            (
                [
                    position,
                    title,
                    task_type,
                    task_status,
                    order_id,
                    task_number,
                    customer_name,
                ],
                i
            ) => {
                image = mapIcon(task_type, task_status);
                tasks = task_details(task_status);
                const label = labels[i % labels.length];
                const marker = new google.maps.Marker({
                    position,
                    title: `${
                        i + 1
                    }. #${task_number}.${customer_name}.${tasks}.${title}`,
                    // label: `${task_type == 1 ? 'Chaklo' : 'Dharlo'}`,
                    optimized: false,
                    icon: image,
                    placeId: order_id,
                });

                // markers can only be keyboard focusable when they have click listeners
                // open info window when marker is clicked
                marker.addListener("click", () => {
                    get_details(order_id, "");
                    infoWindow.close();
                    infoWindow.setContent(marker.getTitle());
                    infoWindow.open(marker.getMap(), marker);
                    markers.push(marker);
                });
                return marker;
            }
        );

        // Add a marker clusterer to manage the markers.

        const markerCluster = new markerClusterer.MarkerClusterer({
            map,
            markers,
        });
    } else {
        const markerss = result.map(
            (
                [
                    position,
                    title,
                    task_type,
                    task_status,
                    order_id,
                    task_number,
                    customer_name,
                ],
                i
            ) => {
                image = mapIcon(task_type, task_status);
                tasks = task_details(task_status);
                const markers = new google.maps.Marker({
                    position,
                    map,
                    title: `${
                        i + 1
                    }. #${task_number}.${customer_name}.${tasks}.${title}`,
                    // label: `${task_type == 1 ? 'Chaklo' : 'Dharlo'}`,
                    optimized: false,
                    icon: image,
                    placeId: order_id,
                });

                // Add a click listener for each marker, and set up the info window.

                markers.addListener("click", () => {
                    get_details(order_id, "");
                    infoWindow.close();
                    infoWindow.setContent(marker.getTitle());
                    infoWindow.open(marker.getMap(), marker);
                    markers.push(marker);
                });
                return marker;
            }
        );
    }
}

// function AgentSpeificLocationMap(result) {
//     const infoWindow = new google.maps.InfoWindow();
//     var bounds = new google.maps.LatLngBounds();

//     result.forEach((position, is_available, agent_name) => {
//         if (position != "t") {
//             image = agenticon(is_available);
//             bounds.extend(position);

//             const marker = new google.maps.Marker({
//                 position,
//                 map,
//                 title: `${agent_name}`,
//                 icon: image,
//             });

//             map.fitBounds(bounds);
//             // Add a click listener for each marker, and set up the info window.

//             marker.addListener("click", () => {
//                 infoWindow.close();
//                 infoWindow.setContent(marker.getTitle());
//                 infoWindow.open(marker.getMap(), marker);
//             });
//             agent_mark.push({ data: marker });
//         }
//     });
// }

function focusMap(result) {
    var filter = $(".dark_theme2").val();
    $("[aria-label='Close']").click();
    var bounds = new google.maps.LatLngBounds();
    // Create the markers.
    result.forEach(([position, title, task_type, task_status], i) => {
        image = mapIcon(task_type, task_status);

        if (filter == "1") {
            bounds.extend(position);
        }

        const marker = new google.maps.Marker({
            position,
            map,
            title: `${i + 1}. ${title}`,
            optimized: false,
            icon: image,
        });
        map.fitBounds(bounds);
        initializeAutocomplete(marker);
    });
}

function initializeAutocomplete(marker) {
    let infoWindow = new google.maps.InfoWindow();
    infoWindow.setContent(marker.getTitle());
    infoWindow.open(marker.getMap(), marker);
}

function task_details(status) {
    var urlnewcreate = "";
    if (status == 0) {
        urlnewcreate = "Uassigned";
    } else if (status == 1) {
        urlnewcreate = "Assign";
    } else if (status == 2) {
        urlnewcreate = "Start";
    } else if (status == 4) {
        urlnewcreate = "Complete";
    } else {
        urlnewcreate = "Faild";
    }

    return urlnewcreate;
}

function mapIcon(type, status) {
    var urlnewcreate = "";
    if (status == 0) {
        urlnewcreate = "faild";
    } else if (status == 1) {
        urlnewcreate = "assign";
    } else if (status == 2) {
        urlnewcreate = "start";
    } else if (status == 4) {
        urlnewcreate = "complete";
    } else {
        urlnewcreate = "faild";
    }

    if (type == 1) {
        urlnewcreate += "_P.png";
    } else if (type == 2) {
        urlnewcreate += "_D.png";
    } else {
        urlnewcreate += "_A.png";
    }

    img = newicons + "/" + urlnewcreate;
    const image = {
        url: img, // url
        // scaledSize: new google.maps.Size(50, 50), // scaled size
        // origin: new google.maps.Point(0, 0), // origin
        // anchor: new google.maps.Point(22, 22) // anchor
        size: new google.maps.Size(32, 32),
        origin: new google.maps.Point(0, 0),
        anchor: new google.maps.Point(0, 32),
    };
    return image;
}
function agenticon(is_available, is_busy) {
    var urlnewcreate = "";
    if (is_available == "1") {
        images = url + "/demo/images/green_ripple.gif";
    } else if (is_available == "0" && is_busy == "1") {
        images = url + "/demo/images/location.png";
    } else {
        images = url + "/demo/images/location_grey.png";
    }

    const image = {
        url: images, // url
        scaledSize: new google.maps.Size(50, 50), // scaled size
        origin: new google.maps.Point(0, 0), // origin
        anchor: new google.maps.Point(22, 22), // anchor
    };
    return image;
}
$(document).on(".filter_update", "click", function () {
    var filter_id = $(this).attr("filter-value");
    $.ajax({
        type: "POST",
        url: filter_update,
        headers: {
            "X-CSRF-Token": X_CSRF_Token,
        },
        data: {
            id: filter_id,
        },
        success: function (response) {
            if ((response = "1")) {
                get_filter_details();
            }
        },
    });
});

$("#teammenus").change(function () {
    var team_id = $("#teammenus").val();
    $.ajax({
        type: "POST",
        url: get_agents,
        headers: {
            "X-CSRF-Token": X_CSRF_Token,
        },
        data: {
            team_id: team_id,
        },
        success: function (response) {
            $("#agentmenu").empty();
            jQuery.each(response, function (index, element) {
                $("#agentmenu").append(
                    "<option value=" +
                        element.id +
                        ">" +
                        element.name +
                        "</option>"
                );
            });
        },
    });
});
$(document).on("click", ".mdi-deletes", function (e) {
    var r = confirm("Are you sure?");
    if (r == true) {
        var taskid = $(this).attr("data_orderids");
        e.preventDefault();
        $.ajax({
            type: "post",
            url: task_destroy,
            headers: {
                "X-CSRF-Token": X_CSRF_Token,
            },
            data: {
                id: taskid,
            },
            dataType: "json",
            success: function (data) {
                location.reload();
            },
        });
    }
});

// function initMap(latlang, agentLogs = "", action = "") {
//     var latlngArray = latlang;
//     var patharray = [];
//     for (var i = 0; i < latlngArray.length; i++) {
//         if (latlngArray[i][0] != "t") {
//             patharray.push(
//                 new google.maps.LatLng(
//                     latlngArray[i][0]["lat"],
//                     latlngArray[i][0]["lng"]
//                 )
//             );
//         }
//     }

//     var agentlatlngArray = agentLogs;
    // if(action == 'ShowPath') {
    //     if(agentlatlngArray.length > 0) {
    //         var end = parseInt(agentlatlngArray.length) - parseInt(1);
    //         patharray.push(new google.maps.LatLng(agentLogs[0]['lat'],agentLogs[0]['lng']));
    //         patharray.push(new google.maps.LatLng(agentLogs[end]['lat'],agentLogs[end]['lng']));
    //         initMap1(patharray,agentLogs);
    //     } else {
    //         alert("This Agent is not start the ride");
    //     }
    // }
    // initMap1(patharray, agentLogs);
// }

// function initMap1(patharray, agentLogs = "") {
//     infowindow = new google.maps.InfoWindow({
//         size: new google.maps.Size(150, 50),
//     });
//     // Instantiate a directions service.
//     directionsService = new google.maps.DirectionsService();
//     const directionsRenderer = new google.maps.DirectionsRenderer();
//     // Create a map and center it on Manhattan.
//     var myOptions = {
//         zoom: 3,
//         mapTypeId: google.maps.MapTypeId.ROADMAP,
//     };
//     map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);

//     // Create a renderer for directions and bind it to the map.
//     var rendererOptions = {
//         map: map,
//     };
//     directionsRenderer.setMap(map);
//     calculateAndDisplayRoute(
//         directionsService,
//         directionsRenderer,
//         patharray,
//         agentLogs
//     );
//     // Instantiate an info window to hold step text.
//     stepDisplay = new google.maps.InfoWindow();

//     polyline = new google.maps.Polyline({
//         path: [],
//         strokeColor: "#FF0000",
//         strokeWeight: 3,
//     });
//     poly2 = new google.maps.Polyline({
//         path: [],
//         strokeColor: "#FF0000",
//         strokeWeight: 3,
//     });
// }

async function calculateAndDisplayRoute(
    directionsService,
    directionsRenderer,
    patharray,
    agentLogs = ""
) {
    if (timerHandle) {
        clearTimeout(timerHandle);
    }
    polyline = await new google.maps.Polyline({
        path: [],
        strokeColor: "#FF0000",
        strokeWeight: 3,
    });
    poly2 = new google.maps.Polyline({
        path: [],
        strokeColor: "#FF0000",
        strokeWeight: 3,
    });
    // Create a renderer for directions and bind it to the map.
    var rendererOptions = {
        map: map,
    };
    if (patharray[1] == null) {
        patharray[1] = { lat: 30.7411, lng: 76.779 };
    }
    const start = patharray[0];
    const end = patharray[1];
    directionsService
        .route({
            origin: start,
            destination: end,
            travelMode: google.maps.TravelMode.DRIVING,
        })
        .then((response) => {
            directionsRenderer.setDirections(response);
            var bounds = new google.maps.LatLngBounds();
            var route = response.routes[0];
            startLocation = new Object();
            endLocation = new Object();

            // For each route, display summary information.
            //    var path = response.routes[0].overview_path;
            //    var legs = response.routes[0].legs;
            //     for (i=0;i<legs.length;i++) {
            //       endLocation.latlng = legs[i].end_location;
            //       console.log(legs[i].end_location);
            //       endLocation.address = legs[i].end_address;
            //       var steps = legs[i].steps;
            //       for (j=0;j<steps.length;j++) {
            //         var nextSegment = steps[j].path;
            //         for (k=0;k<nextSegment.length;k++) {
            //           polyline.getPath().push(nextSegment[k]);
            //           bounds.extend(nextSegment[k]);
            //         }
            //       }
            //     }

            // Create a Polyline for the driving route
            polyline.getPath().push(agentLogs);
            //     var flightPlanCoordinates = [
            //         { lat: 30.71672320, lng: 76.81146880 },
            //         { lat: 30.70330000, lng: 76.78880000 }
            //     ];

            //    console.log(flightPlanCoordinates);

            polyline1 = new google.maps.Polyline({
                path: agentLogs,
                strokeColor: "#FF0000",
                strokeWeight: 3,
                geodesic: true,
                strokeOpacity: 1.0,
                strokeWeight: 2,
            });

            polyline1.setMap(map);
            map.fitBounds(bounds);
            //        createMarker(endLocation.latlng,"end",endLocation.address,"red");
            startAnimation();
        });
}
var step = 50; // 5; // metres
var tick = 100; // milliseconds
var eol;
var k = 0;
var stepnum = 0;
var speed = "";
var lastVertex = 1;
//=============== animation functions ======================
function updatePoly(d) {
    // Spawn a new polyline every 20 vertices, because updating a 100-vertex poly is too slow
    if (poly2.getPath().getLength() > 20) {
        poly2 = new google.maps.Polyline([
            polyline.getPath().getAt(lastVertex - 1),
        ]);
        // map.addOverlay(poly2)
    }

    if (polyline.GetIndexAtDistance(d) < lastVertex + 2) {
        if (poly2.getPath().getLength() > 1) {
            poly2.getPath().removeAt(poly2.getPath().getLength() - 1);
        }
        poly2
            .getPath()
            .insertAt(
                poly2.getPath().getLength(),
                polyline.GetPointAtDistance(d)
            );
    } else {
        poly2
            .getPath()
            .insertAt(poly2.getPath().getLength(), endLocation.latlng);
    }
}

function animate(d) {
    if (d > eol) {
        map.panTo(endLocation.latlng);
        marker.setPosition(endLocation.latlng);
        return;
    }
    var p = polyline.GetPointAtDistance(d);
    map.panTo(p);
    marker.setPosition(p);
    updatePoly(d);
    timerHandle = setTimeout("animate(" + (d + step) + ")", tick);
}

function createMarker(latlng, label, html) {
    // alert("createMarker("+latlng+","+label+","+html+","+color+")");
    var contentString = "<b>" + label + "</b><br>" + html;
    marker = new google.maps.Marker({
        position: latlongs[2][0],
        map: map,
        title: endLocation.address,
        zIndex: Math.round(latlongs[2][0].lat * -100000) << 5,
    });
    marker.myname = label;
    // gmarkers.push(marker);

    google.maps.event.addListener(marker, "click", function () {
        infowindow.setContent(contentString);
        infowindow.open(map, marker);
    });
    return marker;
}

async function startAnimation() {
    eol = await polyline.Distance();
    marker = createMarker(
        "legs[i].start_location",
        "start",
        "legs[i].start_address",
        "green"
    );
    setTimeout("animate(50)", 2000); // Allo
}

function get_details(order_details, order_number) {
    $("#unassigned,#task_number").empty();
    $(".checkOrderStatus").removeClass("active");
    $("#completed_tab1").show();
    $("#completed_tab1").css("display", "contents");

    $.ajax({
        type: "POST",
        url: order_detail,
        headers: {
            "X-CSRF-Token": X_CSRF_Token,
        },
        data: {
            order_id: order_details,
            order_number: order_number,
        },
        success: function (response) {
            $("#edit_stop").attr(
                "href",
                window.location.origin +
                    "/tasks/" +
                    response.order_details.id +
                    "/edit"
            );
            $("#export_stop").attr(
                "href",
                window.location.origin +
                    "/dashboard/task/export/" +
                    response.order_details.id
            );
            $("#delete_stop").attr("data_orderids", +response.order_details.id);
            var status = response.order_details.status;
            var divID = "#" + status + "-tab";
            $(divID).addClass("active");
            var divIDs = "#" + status + "_task";
            $(divIDs).text("1");
            $("#customer_name").text(response.order_details.customer.name);
            $("#agent_username").text(response.order_details.customer_id);
            $("#customer_phone_number").text(
                response.order_details.customer.phone_number
            );
            $("#customer_email").text(response.order_details.customer.email);
            $(".alTaskId").text(response.order_details.order_number);
            $("#order_id").val(response.order_details.order_number);
            $(".create_task").text(response.order_details.order_time);
            if (response.order_details.status != "unassigned") {
                $(".notifications").empty();
                var taskDetail = response.order_details.task;
                jQuery.each(taskDetail, function (index, elements) {
                    if (elements.assigned_time != null) {
                        var htmldata =
                            '<div class="single-history fullwidth mb-2 Notification"> <div class="d-flex align-items-start"> <div class="history-time"> <div class="history-time pull-left"> <span>' +
                            elements.assigned_time +
                            '</span> </div><div class="alAssignLine"> <span class="alCircle" style="background-color:#1394ff"></span></div></div><div class="history-detail"> <span class="title"> <span class="alStatusHistory text-bg-warning text-uppercase text-white alFontSm">Notification</span><span class="dirverNotifications"></span> to task - ' +
                            elements.task_number +
                            '<span class=> Accepted</span></span> <span class="alMapLocation d-block"><a href="#"></a></span> </div></div></div>';
                    }

                    if (elements.started_time != null) {
                        $(".notifications").append(
                            '<div class="single-history fullwidth mb-2 Notification"> <div class="d-flex align-items-start"> <div class="history-time"> <div class="history-time pull-left"> <span>' +
                                elements.started_time +
                                '</span> </div><div class="alAssignLine"> <span class="alCircle" style="background-color:#1394ff"></span></div></div><div class="history-detail"> <span class="title"> <span class="alStatusHistory text-bg-warning text-uppercase text-white alFontSm">Notification</span><span class="dirverNotifications"></span> to task - ' +
                                elements.task_number +
                                '<span class=> Assigned</span></span> <span class="alMapLocation d-block"><a href="#"></a></span> </div></div></div>'
                        );
                    }

                    if (elements.reached_time != null) {
                        $(".notifications").append(
                            '<div class="single-history fullwidth mb-2 Notification"> <div class="d-flex align-items-start"> <div class="history-time"> <div class="history-time pull-left"> <span>' +
                                elements.reached_time +
                                '</span> </div><div class="alAssignLine"> <span class="alCircle" style="background-color:#1394ff"></span></div></div><div class="history-detail"> <span class="title"> <span class="alStatusHistory text-bg-warning text-uppercase text-white alFontSm">Notification</span><span class="dirverNotifications"></span> to task - ' +
                                elements.task_number +
                                '<span class=> Reached</span></span> <span class="alMapLocation d-block"><a href="#"></a></span> </div></div></div>'
                        );
                    }

                    if (elements.task_status == 4) {
                        $(".notifications").append(
                            '<div class="single-history fullwidth mb-2 Notification"> <div class="d-flex align-items-start"> <div class="history-time"> <div class="history-time pull-left"> <span>' +
                                elements.Completed_time +
                                '</span> </div><div class="alAssignLine"> <span class="alCircle" style="background-color:#1394ff"></span></div></div><div class="history-detail"> <span class="title"> <span class="alStatusHistory text-bg-warning text-uppercase text-white alFontSm">Notification</span><span class="dirverNotifications"></span> to task - ' +
                                elements.task_number +
                                '<span class=> Completed</span></span> <span class="alMapLocation d-block"><a href="#"></a></span> </div></div></div>'
                        );
                    }
                });
                $("#customer_address").text(
                    response.order_details.task[0].location.address
                );
                $("#start_time").text(
                    response.order_details.task[0].assigned_time
                );
                $("#end_time").text(
                    response.order_details.task[0].assigned_time
                );
                $("#team").text(response.agent_details.team.name ?? "");
                $("#agent_name,.dirverName").text(response.agent_details.name);
                $("#agent_id").text(response.agent_details.agent_id);
                $("#vechile_type").text(
                    response.agent_details.vehicle_type.name
                );
                $("#usernames").text(response.agent_details.name);
                $("#distance").text(response.order_details.base_distance);
                $("#duration").text(response.order_details.base_duration);
                $(".accepted_time").text(
                    response.order_details.task[0].accepted_time
                );
                $(".assigned").css("background-color", "#1394ff");
                $(".assigne").show();
                $(".re_assign").show();

                task_attachment = response.order_details.task;
                jQuery.each(task_attachment, function (index, element) {
                    if (element.proof_image != null) {
                        $(".attachment").show();
                        $("#attachment_id").val(element.id);
                    }
                });
            }

            var string = null;
            if (response.order_details.allteamtags != null) {
                tags = response.order_details.allteamtags;
                jQuery.each(tags, function (index, element) {
                    order_tag = element.tag.name;
                    string += $(".tag").text(order_tag);
                });
            }
            if (response.order_details.status == "completed") {
                $(".completed,.start,.reached").show();
                $("#reach_time").text(
                    response.order_details.task[0].reached_time ?? ""
                );
                $("#started_time").text(
                    response.order_details.task[0].started_time ?? ""
                );
                if (response.order_details.task[0].Completed_time != null) {
                    $("#completed_time").text(
                        response.order_details.task[0].Completed_time ?? ""
                    );
                } else {
                    $("#completed_time").text(
                        response.order_details.task[1].Completed_time ?? ""
                    );
                }
                $(".alCircle").css("background-color", "#1394ff");
            }
            $(".tasks").show();
            if (response.order_details.task[0].task_status == 3) {
                $(".start,.reached").show();
                $("#reach_time").text(
                    response.order_details.task[0].reached_time
                );
                $("#started_time").text(
                    response.order_details.task[0].started_time
                );
            }
            if (response.order_details.task[0].task_status == 2) {
                $(".start").show();
                $("#started_time").text(
                    response.order_details.task[0].started_time
                );
            }
            var task_number = response.order_details.task;
            $(".taskss").text("#" + task_number[0].task_number);
            if (task_number[0].task_number != null) {
                jQuery.each(task_number, function (index, element) {
                    if (element.task_type_id == "1") {
                        $("#task_number").append(
                            '<a class="dropdown-item check_task_btn" data-orderid="' +
                                response.order_details.id +
                                '" href="#"><span class="pr-2">P</span>' +
                                element.task_number +
                                "</a>"
                        );
                    } else {
                        $("#task_number").append(
                            '<a class="dropdown-item check_task_btn" data-orderid="' +
                                response.order_details.id +
                                '" href="#"><span class="pr-2">D</span>' +
                                element.task_number +
                                "</a>"
                        );
                    }
                });
            }

            if (response.additional_detail != null) {
                $(".templates").empty();
                $(".template_name").text(
                    response.additional_detail.template_name
                );
                var template_details = response.additional_detail.template_data;
                jQuery.each(template_details, function (index, element1) {
                    $(".templates").append(
                        ' <span class="d-block alTaskStatus_label">' +
                            element1.input_field_id +
                            '</span> <span class="d-block alTaskStatus_text">' +
                            element1.value +
                            "</span>"
                    );
                });
            }

            if (response.order_details.get_notification_agents != null) {
                $(".notifications").empty();
                var notification_details =
                    response.order_details.get_notification_agents;
                jQuery.each(notification_details, function (index, elements) {
                    if (elements.agent_name != null) {
                        $(".notifications").append(
                            '<div class="single-history fullwidth mb-2 Notification"> <div class="d-flex align-items-start"> <div class="history-time"> <div class="history-time pull-left"> <span>' +
                                elements.created_at +
                                '</span> </div><div class="alAssignLine"> <span class="alCircle" style="background-color:#1394ff"></span></div></div><div class="history-detail"> <span class="title"> <span class="alStatusHistory text-bg-warning text-uppercase text-white alFontSm">Notification</span><span class="dirverNotifications">' +
                                elements.agent_name +
                                '</span> to task - <span class="alTaskId"></span></span> <span class="alMapLocation d-block"><a href="#"></a></span> </div></div></div>'
                        );
                    }
                });
            }
        },
    });
}
$(document).on(".attachment", "click", function () {
    var attachemt_id = $("#attachment_id").val();
    $("#proofmodal").modal("show");
    $.ajax({
        type: "POST",
        url: attachment_detail,
        headers: {
            "X-CSRF-Token": X_CSRF_Token,
        },
        data: {
            task_id: attachemt_id,
        },
        success: function (response) {
            $("#attach_image").attr("src", response.proof_image);
        },
    });

    $("input").change(function () {
        alert("The text has been changed.");
    });
});

async function get_filter_details() {
    var value = $(this).attr("data-value");
    $.ajax({
        type: "GET",
        url: get_filters_details,
        success: function (response) {
            $(".dispatcher").empty();
            jQuery.each(response, function (index, element) {
                if (element.filter_status == 1) {
                    var status = "checked";
                } else {
                    var status = "unchecked";
                }

                $(".dispatcher").append(
                    '<label class="filter-check">' +
                        element.filter_title +
                        '<input type="checkbox" class="dark_theme' +
                        element.id +
                        ' filter_update" value="' +
                        element.filter_status +
                        '" filter-value="' +
                        element.id +
                        '"  ' +
                        status +
                        '="' +
                        status +
                        '"><span class="checkmark"> </span> </label>'
                );
            });
            loadTeams(0, 0, value, 0);
        },
    });
}

function getUser(search) {
   
    return mark.find(({ id }) => +id === search) ?? "Not working";
}

function MoveAgentLocation(data) {
    //var data = data;
    if (data.is_available == 1) {
        image = url + "/demo/images/location.png";
    } else {
        image = url + "/demo/images/location_grey.png";
    }
    var image = {
        url: image, // url
        scaledSize: new google.maps.Size(50, 50), // scaled size
        origin: new google.maps.Point(0, 0), // origin
        anchor: new google.maps.Point(22, 22), // anchor
    };
        var dataAgent = getUser(data.id);
        marker = dataAgent.data;
        //marker.setMap( map );
    
    marker != undefined ? moveBus(map, marker, data, image) : "";
}

function moveBus(map, marker, data, image) {
    marker.setPosition(new google.maps.LatLng(data.lat, data.lng));
    marker.setIcon(image);
}

//function to listen different channels of event of different dates and different agent status

function ListenAgentLogChannel() {
    
    if (window.Echo) {
        window.Echo.channel(`${app_name}_database_user-channel`).listen(
            ".UserEvent",
            (data) => {
                //markers = [];
                //loadTeams(0, 0,'','','',1);
                if (
                    data.event_type == "agent_log" ||
                    data.event_type == "task_log" ||
                    data.event_type == "agent_status_update"
                ) {
                    MoveAgentLocation(data);
                } else if (
                    data.event_type == "agent_create" ||
                    data.event_type == "agent_update"
                ) {
                    AgentCreatedOrUpdate(data);
                    MoveAgentLocation(data);
                } else {
                    var date = $("#birthdatepicker").val();
                    order_details(
                        (val = ""),
                        date,
                        (start_time = ""),
                        (endtime = "")
                    );
                }

                //is_load_html, is_show_loader, value,teamid,dates
            }
        );
    }
}

function AgentCreatedOrUpdate(data) {
    if (data) {
        if ($(`#agent_tab_${data.id}`)) {
            $(`#agent_tab_${data.id}`).remove();
        }
        $("#busy").text(data.online_agents);
        $("#incative").text(data.offline_agents);
        $("#all").text(data.agent_count);
        $("#freeagent").append(agent_html(data));
    }
}

function cancleForm() {
    $('#optimizerouteform').trigger("reset");
    $('#optimize-route-modal').modal('hide');
}


function initializeSortable() {
    $(".dragable_tasks").sortable({
        update: function(event, ui) {
            $('.routetext').text('Arranging Route');
            spinnerJS.showSpinner();
            var divid = $(this).attr('id');
            var params = $(this).attr('params');
            var agentid = $(this).attr('agentid');
            var date = $(this).attr('date');
            
            var taskorder = "";
            jQuery("#" + divid + " .card-body.ui-sortable-handle").each(function(index, element) {
                taskorder = taskorder + $(this).attr('task_id') + ",";
            });
            console.log(taskorder);
            $('input[type=radio][name=driver_start_location]').prop('checked', false);
            $.ajax({
                type: 'POST',
                url: arrangeRoute,
                headers: {
                    'X-CSRF-Token': X_CSRF_TOKEN,
                },
                data: {
                    'taskids': taskorder,
                    'agentid': agentid,
                    'date': date
                },

                success: function(response) {
                    var data = $.parseJSON(response);

                    $('.totdis' + agentid).html(data.total_distance);
                    var funperams =
                        '<span class="optimize_btn" onclick="RouteOptimization(' + params +
                        ')">Optimize</span>';
                    $('.optimizebtn' + agentid).html(funperams);
                    spinnerJS.hideSpinner();
                    $('#optimize-route-modal').modal('show');
                    $('#routeTaskIds').val(taskorder);
                    $('#routeMatrix').val('');
                    $('#routeOptimize').val('');
                    $('#routeAgentid').val(agentid);
                    $('#routeDate').val(date);
                    $('#optimizeType').val('dragdrop');
                    $("input[name='driver_start_location'][value='current']").prop(
                        "checked", true);
                    $('#addressBlock').css('display', 'none');
                    $('#addressTaskBlock').css('display', 'none');
                    $('#selectedtasklocations').html('');
                    $('.selecttask').css('display', 'none');
                    
                    if (data.current_location == 0) {
                        $("input[type=radio][name=driver_start_location][value='current']")
                            .remove();
                        $("#radio-current-location-span").remove();
                        $("input[type=radio][name=driver_start_location][value='select']")
                            .click();
                    }
                    
                },
                error: function(response) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops',
                        text: 'There is some issue. Try again later',
                    });
                    spinnerJS.hideSpinner();
                }
            });
        }
    });
}

function reloadData() {
    location.reload();
}

function openAllAccordian() {
    $("#accordion").find(`[data-toggle="collapse"]`).removeClass('collapsed');
    $("#accordion").find(`[data-toggle="collapse"]`).attr('aria-expanded', 'true');
    $(".collapse").addClass('show');
    $(".allAccordian").html('<span class="" onclick="closeAllAccordian()">Close All</span>');
}

function closeAllAccordian() {
    $("#accordion").find(`[data-toggle="collapse"]`).addClass('collapsed');
    $("#accordion").find(`[data-toggle="collapse"]`).attr('aria-expanded', 'false');
    $(".collapse").removeClass('show');
    $(".allAccordian").html('<span class="" onclick="openAllAccordian()">Open All</span>');
}

function NavigatePath(taskids, distancematrix, optimize, agentid, date) {
    $('.routetext').text('Exporting Pdf');
    spinnerJS.showSpinner()

    $.ajax({
        type: 'POST',
        url: exportPathUrl,
        headers: {
            'X-CSRF-Token': X_CSRF_TOKEN,
        },
        data: {
            'taskids': taskids,
            'agentid': agentid,
            'date': date
        },

        success: function(response) {
            if (response != "Try again later") {
                $('#pdfvalue').val(response);
                $("#pdfgenerate").submit();
                spinnerJS.hideSpinner()
            } else {
                alert(response);
                spinnerJS.hideSpinner()
            }
        },
        error: function(response) {

        }
    });
}

function RouteOptimization(taskids, distancematrix, optimize, agentid, date) {
    $('#routeTaskIds').val(taskids);
    $('#routeMatrix').val(distancematrix);
    $('#routeOptimize').val(optimize);
    $('#routeAgentid').val(agentid);
    $('#routeDate').val(date);
    $('#optimizeType').val('optimize');
    $('#addressBlock').css('display', 'none');
    $('#addressTaskBlock').css('display', 'none');
    $('#selectedtasklocations').html('');
    $('.selecttask').css('display', '');
    $.ajax({
        type: 'POST',
        url: getTasks,
        headers: {
            'X-CSRF-Token': X_CSRF_TOKEN,
        },
        data: {
            'taskids': taskids
        },
        success: function(response) {
            var data = $.parseJSON(response);
            for (var i = 0; i < data.length; i++) {
                var object = data[i];
                var task_id = object['id'];
                var tasktypeid = object['task_type_id'];
                var current_location = object['current_location'];
                if (current_location == 0) {
                    $('input[type=radio][name=driver_start_location]').prop('checked', false);
                    $("input[type=radio][name=driver_start_location][value='current']").remove();
                    $("#radio-current-location-span").remove();
                    $("input[type=radio][name=driver_start_location][value='select']").click();
                }

                if (tasktypeid == 1) {
                    tasktype = "Pickup";
                } else if (tasktypeid == 2) {
                    tasktype = "Dropoff";
                } else {
                    tasktype = "Appointment";
                }

                var location_address = object['location']['address'];
                var shortname = object['location']['short_name'];

                var option = '<option value="' + task_id + '">' + tasktype + ' - ' + shortname + ' - ' +
                    location_address + '</option>';
                $('#selectedtasklocations').append(option);

            }
        },
        error: function(response) {

        }
    });
    $('#optimize-route-modal').modal('show');
}

 // function for displaying route  on map
 function calculateAndDisplayRoute(directionsService, directionsRenderer, map, alltask, agent_location) {
    const waypts = [];
    const checkboxArray = document.getElementById("waypoints");

    for (let i = 0; i < alltask.length; i++) {
        if (i != alltask.length - 1 && alltask[i].task_status != 4 && alltask[i].task_status != 5) {
            waypts.push({
                location: new google.maps.LatLng(parseFloat(alltask[i].latitude), parseFloat(alltask[i]
                    .longitude)),
                stopover: true,
            });
        }
        var image = url + '/assets/newicons/' + alltask[i].task_type_id + '.png';
    }

    directionsService.route({
            origin: new google.maps.LatLng(parseFloat(agent_location.lat), parseFloat(agent_location.long)),
            destination: new google.maps.LatLng(parseFloat(alltask[alltask.length - 1].latitude),
                parseFloat(alltask[alltask.length - 1].longitude)),
            waypoints: waypts,
            optimizeWaypoints: false,
            travelMode: google.maps.TravelMode.DRIVING,
        },
        (response, status) => {
            if (status === "OK" && response) {
                directionsRenderer.setDirections(response);
                directionsArray.push(directionsRenderer);
            } else {
                //window.alert("Directions request failed due to " + status);
            }
        }
    );
}

function gm_authFailure() { 
    $('.excetion_keys').append(
        '<span><i class="mdi mdi-block-helper mr-2"></i> <strong>Google Map</strong> key is not valid</span><br/>'
        );
    $('.displaySettingsError').show();
}

function deleteAgentMarks() {
    for (let i = 0; i < driverMarkers.length; i++) {
        driverMarkers[i].setMap(null);
    }
}