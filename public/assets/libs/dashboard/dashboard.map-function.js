async function createRequest(url, method, headers, data = {}) {
    return new Promise((resolve, reject) => {
        const axiosConfig = {
            method: method.toLowerCase(),
            url: url,
            data: data,
            headers: headers,
        };

        axios(axiosConfig)
            .then((response) => {
                resolve(response);
            })
            .catch((error) => {
                reject(error);
            });
    });
}

async function loadTeams(is_load_html, is_show_loader) {
    if (is_load_html == 1) {
        closeAllAccordian();
    }
    if (is_show_loader == 1) {
        spinnerJS.showSpinner();
    }
    var checkuserstatus = $('input[name="user_status"]:checked').val();
    var team_id = $("#team_id").val();
    var search_by_name = $("#search_by_name").val();

    let headers = {
        "X-CSRF-Token": X_CSRF_TOKEN,
    };

    let data = {
        userstatus: checkuserstatus,
        team_id: team_id,
        search_by_name: search_by_name,
        is_load_html: is_load_html,
        routedate: $("#basic-datepicker").val(),
        dashboard_theme: dashboardTheme,
    };

    //fetch team data form server
    try {
        var response = await createRequest(teamDataUrl, "post", headers, data);
        
        // Handle successful response
        handleDataSuccess(response, is_load_html, "#teams_container");
    } catch (error) {
        // Handle error
        console.error("An error occurred:", error);
        handleError(error)
    }
}

// autoload dashbard
async function loadOrders(is_load_html, is_show_loader, url = "") {
    if (is_load_html == 1) {
        closeAllAccordian();
    }
    if (is_show_loader == 1) {
        spinnerJS.showSpinner();
    }
    var checkuserroutes = $('input[name="user_routes"]:checked').val();
    var agent_id = $("#agent_id").val();
    url = url ? url : orderDataUrl;

    let headers = {
        "X-CSRF-Token": X_CSRF_TOKEN,
    };

    let data = {
        agent_id: agent_id,
        checkuserroutes: checkuserroutes,
        is_load_html: is_load_html,
        routedate: $("#basic-datepicker").val(),
        dashboard_theme: dashboardTheme,
    };

    try {
        var response = await createRequest(url, "post", headers, data);
        // Handle successful response
        handleDataSuccess(response, is_load_html, "#handle-dragula-left0");
    } catch (error) {
        // Handle error
        console.error("An error occurred:", error);
        handleError(error)
    }
}

async function drawRoute(id) {
    // Remove old route and markers
    // if (typeof directionsRenderer !== 'undefined') {
    //     directionsRenderer.setDirections({ routes: [] });
    // }

    // Remove all the markers on map
    // if (typeof markers !== 'undefined') {
    //     for (var i = 0; i < markers.length; i++) {
    //         markers[i].setMap(null);
    //     }
    //     markers = [];
    // }
    let headers = {
        "X-CSRF-Token": X_CSRF_TOKEN,
    };

    let data = { id: id };

    try {
        var response = await createRequest(getRouteDetailUrl, "post", headers, data);
        // Handle successful response
        handleDrawRouteSuccess(response.data, true); //true denotes for drawing multiple routes on map
    } catch (error) {
        // Handle error
        console.error("An error occurred:", error);
        handleError(error)
    }
}

function handleDrawRouteSuccess(data, isMultiple = false) {

    if(isMultiple){
        // Remove old route and markers
        if (typeof directionsRenderer !== 'undefined') {
            directionsRenderer.setDirections({
                routes: []
            });
            allRenderRoute = [];
        }
    }

    var pickup_lat = data.pickup_location.lat;
    var pickup_lng = data.pickup_location.lng;
    var dest_lat = data.dropoff_location.lat;
    var dest_lng = data.dropoff_location.lng;

    var pickupLocation = new google.maps.LatLng(pickup_lat, pickup_lng);
    var dropoffLocation = new google.maps.LatLng(dest_lat, dest_lng);

    var request = {
        origin: pickupLocation,
        destination: dropoffLocation,
        travelMode: google.maps.TravelMode.DRIVING,
    };

    allRenderRoute.push(request);

    if (typeof map !== "undefined") {
        map.setCenter(new google.maps.LatLng(pickup_lat, pickup_lng));
        map.setZoom(10);
    } else {
        map = new google.maps.Map(
            document.getElementById("map_canvas"),
            {
                zoom: 15,
                center: new google.maps.LatLng(pickup_lat, pickup_lng),
                mapTypeId: "roadmap",
                styles: themeType,
            }
        );
    }

    var directionsService = new google.maps.DirectionsService();

    

    function calculateRoute(request) {
  
        directionsRenderer = new google.maps.DirectionsRenderer();
        directionsRenderer.setMap(map);

        directionsService.route(request, function (response, status) {
            if (status === "OK") {
                directionsRenderer.setDirections(response);
               var pickupIcon = mapIcons(1);
         
               var dropOffIcon = mapIcons(2);
                var pickupMarker = new google.maps.Marker({
                    position: pickupLocation,
                    map: map,
                    icon : icon,
                    title: "Pickup Location",
                    optimized: true,
                });


                var dropoffMarker = new google.maps.Marker({
                    position: dropoffLocation,
                    map: map,
                    icon:icon,
                    title: "Dropoff Location",
                    optimized: true,
                });

                markers.push(pickupMarker);
                markers.push(dropoffMarker);
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Directions request failed: " + status,
                });
            }
        });
    }

    allRenderRoute.forEach(function (routeRequest) {
        calculateRoute(routeRequest);
    });
}

function mapIcons(type) {
    var urlnewcreate = ""; 

    urlnewcreate = "assigned";
    if (type == 1) {
        urlnewcreate += "_P.png";
    } else if (type == 2) {
        urlnewcreate += "_D.png";
    } else {
        urlnewcreate += "_A.png";
    }

    img = url+'/assets/newicons/' + urlnewcreate;
    const image = {
        url: img, // url
        // scaledSize: new google.maps.Size(50, 50), // scaled size
        // origin: new google.maps.Point(0, 0), // origin
        // anchor: new google.maps.Point(22, 22) // anchor
        size: new google.maps.Size(50, 50),
        origin: new google.maps.Point(0, 0),
        anchor: new google.maps.Point(0, 32),
    };
    return image;
}

    

//handle the success response of the post request to server
function handleDataSuccess(result, is_load_html, element) {
    olddata = defaultmaplocation = [];

    if (is_load_html == 1) {
        //replace the content of appropriate box
        $(`${element}`).html(result.data);

        if (is_show_loader == 0) {
            spinnerJS.hideSpinner();
        }

        initializeSortable();

        //this code is executed only while updating the team data
        if (element == "#teams_container") {
           var newmarker_map_data = ($("#newmarker_map_data").val() == undefined) ? '' : $("#newmarker_map_data").val();
           var uniquedrivers_map_data = ($("#uniquedrivers_map_data").val() == undefined) ? '' : $("#uniquedrivers_map_data").val();
           var agentslocations_map_data = ($("#agentslocations_map_data").val() == undefined) ? '' : $("#agentslocations_map_data").val();

            if (newmarker_map_data != "") {
                olddata = JSON.parse($("#newmarker_map_data").val());
            }

            if(agentsLatLong != ''){
                allagent = JSON.parse(agentsLatLong);
            }

            if (uniquedrivers_map_data != "") {
                allroutes = JSON.parse($("#uniquedrivers_map_data").val());
            }

            if (agentslocations_map_data != "") {
                defaultmaplocation = JSON.parse(
                    $("#agentslocations_map_data").val()
                );
                defaultlat = parseFloat(defaultmaplocation[0].lat);
                defaultlong = parseFloat(defaultmaplocation[0].long);
            }
            drawAgents()
        }
    } else {
        var data1 = JSON.parse(result);
        if (data1["status"] == "success") {
            // setting up required variables to refreshing the google map route
            olddata = data1["newmarker"];
            allagent = data1["agents"];
            allroutes = data1["routedata"];
            defaultlat = parseFloat(data1["defaultCountryLatitude"]);
            defaultlong = parseFloat(data1["defaultCountryLongitude"]);
        }
    }

    // initMap(is_load_html);
}

function handleError(data) {
    Swal.fire({
        icon: "error",
        title: "Oops",
        text: "There is some issue. Try again later",
    });
    if (is_load_html == 1) {
        spinnerJS.hideSpinner();
    }
}

function addMarker(location, lables, images, data, type) {
  
    var contentString = "";
    if (type == 1) {
        contentString =
            '<div id="content">' +
            '<div id="siteNotice">' +
            "</div>" +
            '<h5 id="firstHeading" class="firstHeading">' +
            data["driver_name"] +
            "</h5>" +
            '<h6 id="firstHeading" class="firstHeading">' +
            data["task_type"] +
            "</h6>" +
            '<div id="bodyContent">' +
            "<p><b>Address :- </b> " +
            data["address"] +
            " " +
            ".</p>" +
            "<p><b>Customer: " +
            data["customer_name"] +
            "</b>(" +
            data["customer_phone_number"] +
            ") </p>" +
            "</div>" +
            "</div>";
    } else {
        img = imgproxyurl+data.image_url;
        
        contentString =
            '<div class="row no-gutters align-items-center">' +
            '<div class="col-sm-4">' +
            '<div class="img_box mb-sm-0 mb-2"> <img src="' +
            img +
            '"/></div> </div>' +
            '<div class="col-sm-8 pl-2 user_info">' +
            '<div class="user_name mb-2 11"><label class="d-block m-0">' +
            data.agent_name +
            '</label><span> <i class="fas fa-phone-alt"></i>' +
            data.phone_number +
            "</span></div>" +
            '<div><b class="d-block mb-2"><i class="far fa-clock"></i> <span> ' +
            jQuery.timeago(new Date(data.created_at)) +
            ' </span></b> <b><i class="fas fa-mobile-alt"></i> ' +
            data.device_type +
            '</b> <b class="ml-2"> <i class="fas fa-battery-half"></i>  ' +
            data.battery_level +
            "%</b>";
        if (data.id) {
            contentString +=
                '<a target="_blank" href="fleet/details/' +
                btoa(data.id) +
                '"><b class="d-block mt-2"><i class="fa fa-car"></i><span>' +
                data.agent_name +
                " </span></b></a>";
        }
        contentString += "</div>";
        "</div>" + "</div>";
    }

    const infowindow = new google.maps.InfoWindow({
        content: contentString,
        minWidth: 250,
        minheight: 250,
    });

    const marker = new google.maps.Marker({
        position: location,
        label: lables,
        icon: images,
        map: map,
        //animation: google.maps.Animation.DROP,
    });

    if (type == 2) {
        driverMarkers.push(marker);
    }

    markers.push(marker);
    mark.push({id:data.agent_id, data:marker})

    marker.addListener("click", () => {
        infowindow.open(map, marker);
    });
}

// Sets the map on all markers in the array.
function setMapOnAll(map) {
    for (let i = 0; i < markers.length; i++) {
        markers[i].setMap(null);
    }
}

// Removes the markers from the map, but keeps them in the array.
function clearMarkers() {
    setMapOnAll(null);
}

// Shows any markers currently in the array.
function showMarkers() {
    setMapOnAll(map);
}

// Deletes all markers in the array by removing references to them.
function deleteMarkers() {
    clearMarkers();
    markers = [];
}

function clearRoutes() {
    if (directionsArray.length < 1) {
        //alert("No directions have been set to clear");
        return;
    } else {
        $("#directions").hide();
        for (var i = 0; i < directionsArray.length; i++) {
            if (directionsArray[i] !== -1) {
                directionsArray[i].setMap(null);
            }
        }
        directionsArray = [];
        return;
    }
}

//handle the on change event of date field
function handler(element) {
    // loadTeams(1, 1);
    loadOrders(1, 1);
    old_channelname = channelname;
    old_logchannelname = logchannelname;
    channelname = `${channelName}${element.value}`;
    logchannelname = `${logChannelName}${element.value}`;
    if (old_channelname != channelname) {
        //ListenDataChannel();
        ListenAgentLogChannel();
    }
}

function drawAgents(){
        // Remove all the markers on map
    if (typeof markers !== 'undefined') {
        for (var i = 0; i < markers.length; i++) {
            markers[i].setMap(null);
        }
        markers = [];
    }
    for (let i = 0; i < allagent.length; i++) {
        displayagent = allagent[i];

        if (
            displayagent != null &&
            displayagent.lat != "0.00000000" &&
            displayagent.lat != null &&
            displayagent.long != "0.00000000" &&
            displayagent.long != null
        ) {
            if (displayagent["is_available"] == 1) {
                images = url + "/demo/images/location.png";
            } else {
                images = url + "/demo/images/location_grey.png";
            }
            var image = {
                url: images, // url
                scaledSize: new google.maps.Size(50, 50), // scaled size
                origin: new google.maps.Point(0, 0), // origin
                anchor: new google.maps.Point(22, 22), // anchor
            };
            send = null;
            type = 2;

            addMarker(
                {
                    lat: parseFloat(displayagent.lat),
                    lng: parseFloat(displayagent.long),
                },
                send,
                image,
                displayagent,
                type
            );

         
        }
    }

}

//initialize the map
async function initMap(is_refresh) {
    
    //new code for route
    var color = [
        "blue",
        "green",
        "red",
        "purple",
        "skyblue",
        "yellow",
        "orange",
    ];
    
    const haightAshbury = {
        lat:
            allagent.length !== 0 &&
            allagent[0].agentlog &&
            allagent[0].agentlog["lat"] !== "0.00000000" &&
            allagent[0].agentlog["lat"] !== null
                ? parseFloat(allagent[0].agentlog["lat"])
                : defaultlat,
        lng:
            allagent.length !== 0 &&
            allagent[0].agentlog &&
            allagent[0].agentlog["long"] !== "0.00000000" &&
            allagent[0].agentlog["long"] !== null
                ? parseFloat(allagent[0].agentlog["long"])
                : defaultlong,
    };

    if (is_refresh == 1) {
        map = new google.maps.Map(document.getElementById("map_canvas"), {
            zoom: 12,
            center: haightAshbury,
            mapTypeId: "roadmap",
            styles: themeType,
        });

        // Adds a marker at the center of the map.
        for (let i = 0; i < olddata.length; i++) {
            checkdata = olddata[i];
            var urlnewcreate = "";
            if (checkdata["task_status"] == 0) {
                urlnewcreate = "unassigned";
            } else if (
                checkdata["task_status"] == 1 ||
                checkdata["task_status"] == 2
            ) {
                urlnewcreate = "assigned";
            } else if (checkdata["task_status"] == 3) {
                urlnewcreate = "complete";
            } else {
                urlnewcreate = "faild";
            }

            if (checkdata["task_type_id"] == 1) {
                urlnewcreate += "_P.png";
            } else if (checkdata["task_type_id"] == 2) {
                urlnewcreate += "_D.png";
            } else {
                urlnewcreate += "_A.png";
            }

            img = iconsRoute + "/" + urlnewcreate;

            send = null;
            type = 1;
            addMarker(
                {
                    lat: parseFloat(checkdata["latitude"]),
                    lng: parseFloat(checkdata["longitude"]),
                },
                send,
                img,
                checkdata,
                type
            );
        }
    } else {
        deleteAgentMarks();
        clearRoutes();
    }

    $.each(allroutes, function (i, item) {
        const directionsService = new google.maps.DirectionsService();
        const directionsRenderer = new google.maps.DirectionsRenderer({
            suppressMarkers: true,
        });
        if (i < color.length) {
            var routecolor = color[i];
        } else {
            var routecolor = "pink";
        }
        directionsRenderer.setOptions({
            polylineOptions: {
                strokeColor: routecolor,
            },
        });
        directionsRenderer.setMap(map);
        var al_task = allroutes[i].task_details;
        var agent_locatn = allroutes[i].driver_detail;
        calculateAndDisplayRoute(
            directionsService,
            directionsRenderer,
            map,
            al_task,
            agent_locatn
        );
    });

    await drawAgents()

    map.setCenter(haightAshbury);
}

// ==============================================================================================================================================================
//
//  TRY PARCEL CODE BELOW
//
// ==============================================================================================================================================================

function handleTeamData(result) {
    olddata = allagent = defaultmaplocation = [];
    //if Html is required to load or not, for agent's log it is not required

    if (is_load_html == 1) {
        $("#teams_container").empty();
        $("#teams_container").html(result);

        if (is_show_loader == 1) {
            spinnerJS.hideSpinner();
        }
        initializeSortable();

        if ($("#newmarker_map_data").val() != "") {
            olddata = JSON.parse($("#newmarker_map_data").val());
        }

        if ($("#agents_map_data").val() != "") {
            allagent = JSON.parse($("#agents_map_data").val());
        }

        if ($("#uniquedrivers_map_data").val() != "") {
            allroutes = JSON.parse($("#uniquedrivers_map_data").val());
        }

        if ($("#agentslocations_map_data").val() != "") {
            defaultmaplocation = JSON.parse(
                $("#agentslocations_map_data").val()
            );
            defaultlat = parseFloat(defaultmaplocation[0].lat);
            defaultlong = parseFloat(defaultmaplocation[0].long);
        }
    } else {
        var data1 = JSON.parse(result);
        if (data1["status"] == "success") {
            // setting up required variables to refreshing the google map route
            teams = data1["teams"][0];
            olddata = data1["newmarker"];
            allagent = data1["agents"];
            allteams = data1["teams"];
            unassigned_task = data1["unassigned"];
            latlongs = maplatLong(data1["agents"]);
            $("#freeagent").empty();
            $("#busyagents,#completed_tab1").hide();
            $("#busy").text(teams.busy_agents);
            $("#incative").text(teams.offline_agents);
            $("#all").text(teams.online_agents);
            $("#teammenu,#menu,#agentmenu").empty();
            jQuery.each(allagent, function (index, element) {
                // $("#freeagent").append(
                //     '<div class="alTabBoxOuter p-2 agent_detail" id="agent_tab_'+element.id+'" agent_value= "' +
                //     element.id +
                //     '" > <div class="d-flex align-items-center justify-content-between"> <div class="d-flex align-items-start justify-content-between"> <div class="me-2 position-relative"> <span class="alAgentFirtsLetter fs-3 bg-primary rounded-circle text-white">' +
                //     element.name.substr(0, 1) +
                //     '</span> <span class="position-absolute bottom-0 start-100 translate-middle p-1 .bg-primary rounded-circle"></span> </div> <div class="alAgentsDetails w-100"> <div class="d-flex align-items-start justify-content-around"> <div class="me-2"> <b class="alAgentsNameBox text-truncate"><span class="orderId"></span><span class="alAgentsName">' +
                //     element.agent_id +
                //     '</span></b> <b class="alAgentsNameBox text-truncate"><span class="orderId"></span><span class="alAgentsName">' +
                //     element.name +
                //     '</span></b>  <span class="alAgentsPhone d-block">' + element
                //         .phone_number +
                //     '</span> <ul class="p-0 m-0 d-flex alAgentOtherInfo"> <li class="me-3"><span class="alAgentVehicle pe-1 me-1 border-end">' +
                //     element.vehicle_type.name +
                //     '</span> <span class="alAgentTransaction"> Cash</span> </li> <li><span class="alAgentTimeTake">7 minutes</span></li> </ul> </div> <div class="alAgentsChatBox d-flex"> <span style="margin-top: -8px;"><i class="mdi-message-processing mdi mdi-24px text-primary"></i></span> <span class="alAgentsTaskBox text-center"><span class="alAgentsTaskInner rounded-circle border border-light px-1 py-0 mb-2">0</span> <small> Task </small></span> </div> </div> </div> </div> <div class="text-right"> <span><i style="font-size: 30px;" class="uil-angle-right agent_detail" agent_value=' +
                //     element.id +
                //     '></i></span> </div> </div> <hr class="my-2"> </div>'

                // )

                $("#freeagent").append(agent_html(element));
                $("#menu").append(
                    '<a class="dropdown-item agent_detail" agent_value="' +
                        element.id +
                        '" href="#">' +
                        element.name +
                        " (" +
                        element.agent_id +
                        ")</a>"
                );

                $("#agentmenu").append(
                    "<option value=" +
                        element.id +
                        ">" +
                        element.name +
                        "        <span>Idle</span></option>"
                );
            });

            jQuery.each(allteams, function (index, element) {
                $("#teammenu").append(
                    '<a class="dropdown-item team_detail" href="#" team_value="' +
                        element.id +
                        '">' +
                        element.name +
                        "</a>"
                );
                $("#teammenus").append(
                    "<option value=" +
                        element.id +
                        ">" +
                        element.name +
                        "</option>"
                );
            });
            allroutes = data1["routedata"];
            defaultlat = parseFloat(data1["defaultCountryLatitude"]);
            defaultlong = parseFloat(data1["defaultCountryLongitude"]);
        }
    }

    const output = mapData(data1["data"]);

    var cluster_filter = $(".dark_theme2").val();
    // if(cluster_filter=='1'){
    //     specificMap(pending_task);
    // }
    const latlong = maplatLong(data1["agent_log"]);
    var agent_location = $(".dark_theme3").val();
    if (refresh == 0) {
        map = checkInitMap(is_initMap);
    }
    if (agent_location == "1") {
        AgentLocationMap(latlong);
    }
    specificMap(output);
}

async function checkInitMap(is_initMap) {
    if (is_initMap) {
        var dark_theme = $(".dark_theme5").val();
        if (extended_map == 1) {
            if (dark_theme == "1") {
                style = [
                    {
                        elementType: "geometry",
                        stylers: [{ color: "#242f3e" }],
                    },
                    {
                        elementType: "labels.text.stroke",
                        stylers: [{ color: "#242f3e" }],
                    },
                    {
                        elementType: "labels.text.fill",
                        stylers: [{ color: "#746855" }],
                    },
                    // {
                    //     featureType: "administrative.locality",
                    //     elementType: "labels.text.fill",
                    //     stylers: [{ visibility: "off" }]
                    // },
                    {
                        featureType: "poi",
                        stylers: [{ visibility: "off" }],
                    },
                    // {
                    //     featureType: "poi.park",
                    //     elementType: "geometry",
                    //     stylers: [{ visibility: "off" }]
                    // },
                    {
                        featureType: "poi.park",
                        stylers: [{ visibility: "off" }],
                    },
                    // {
                    //     featureType: "road",
                    //     elementType: "geometry",
                    //     stylers: [{ visibility: "off" }]
                    // },
                    {
                        featureType: "road",
                        stylers: [{ visibility: "off" }],
                    },
                    {
                        featureType: "road",
                        stylers: [{ visibility: "off" }],
                    },
                    {
                        featureType: "road.highway",
                        stylers: [{ visibility: "off" }],
                    },
                    // {
                    //     featureType: "road.highway",
                    //     elementType: "geometry.stroke",
                    //     stylers: [{ visibility: "off" }]
                    // },
                    // {
                    //     featureType: "road.highway",
                    //     elementType: "labels.text.fill",
                    //     stylers: [{ visibility: "off" }]
                    // },
                    {
                        featureType: "transit",
                        stylers: [{ visibility: "off" }],
                    },
                    {
                        featureType: "transit.station",
                        stylers: [{ visibility: "off" }],
                    },
                    {
                        featureType: "water",
                        stylers: [{ visibility: "off" }],
                    },
                    {
                        featureType: "water",
                        stylers: [{ visibility: "off" }],
                    },
                    {
                        featureType: "water",
                        stylers: [{ visibility: "off" }],
                    },
                ];
            } else {
                style = [
                    // {
                    //     featureType: "administrative.locality",
                    //     elementType: "labels.text.fill",
                    //     stylers: [{ visibility: "off" }]
                    // },
                    {
                        featureType: "poi",
                        stylers: [{ visibility: "off" }],
                    },
                    // {
                    //     featureType: "poi.park",
                    //     elementType: "geometry",
                    //     stylers: [{ visibility: "off" }]
                    // },
                    {
                        featureType: "poi.park",
                        stylers: [{ visibility: "off" }],
                    },
                    // {
                    //     featureType: "road",
                    //     elementType: "geometry",
                    //     stylers: [{ visibility: "off" }]
                    // },
                    {
                        featureType: "road",
                        stylers: [{ visibility: "off" }],
                    },
                    {
                        featureType: "road",

                        stylers: [{ visibility: "off" }],
                    },
                    {
                        featureType: "road.highway",
                        stylers: [{ visibility: "off" }],
                    },
                    // {
                    //     featureType: "road.highway",
                    //     elementType: "geometry.stroke",
                    //     stylers: [{ visibility: "off" }]
                    // },
                    // {
                    //     featureType: "road.highway",
                    //     elementType: "labels.text.fill",
                    //     stylers: [{ visibility: "off" }]
                    // },
                    {
                        featureType: "transit",
                        stylers: [{ visibility: "off" }],
                    },
                    {
                        featureType: "transit.station",
                        stylers: [{ visibility: "off" }],
                    },
                    {
                        featureType: "water",
                        stylers: [{ visibility: "off" }],
                    },
                    {
                        featureType: "water",
                        stylers: [{ visibility: "off" }],
                    },
                    {
                        featureType: "water",
                        stylers: [{ visibility: "off" }],
                    },
                ];
                document.getElementById("remove-overlay");
            }
        }
        // else {
        //     style = [
        //         { elementType: "geometry", stylers: [{ color: "#242f3e" }] },
        //         {
        //             elementType: "labels.text.stroke",
        //             stylers: [{ color: "#242f3e" }],
        //         },
        //         {
        //             elementType: "labels.text.fill",
        //             stylers: [{ color: "#746855" }],
        //         },
        //         {
        //             featureType: "administrative.locality",
        //             elementType: "labels.text.fill",
        //             stylers: [{ color: "#d59563" }],
        //         },
        //         {
        //             featureType: "poi",
        //             elementType: "labels.text.fill",
        //             stylers: [{ color: "#d59563" }],
        //         },
        //         {
        //             featureType: "poi.park",
        //             elementType: "geometry",
        //             stylers: [{ color: "#263c3f" }],
        //         },
        //         {
        //             featureType: "poi.park",
        //             elementType: "labels.text.fill",
        //             stylers: [{ color: "#6b9a76" }],
        //         },
        //         {
        //             featureType: "road",
        //             elementType: "geometry",
        //             stylers: [{ color: "#38414e" }],
        //         },
        //         {
        //             featureType: "road",
        //             elementType: "geometry.stroke",
        //             stylers: [{ color: "#212a37" }],
        //         },
        //         {
        //             featureType: "road",
        //             elementType: "labels.text.fill",
        //             stylers: [{ color: "#9ca5b3" }],
        //         },
        //         {
        //             featureType: "road.highway",
        //             elementType: "geometry",
        //             stylers: [{ color: "#746855" }],
        //         },
        //         {
        //             featureType: "road.highway",
        //             elementType: "geometry.stroke",
        //             stylers: [{ color: "#1f2835" }],
        //         },
        //         {
        //             featureType: "road.highway",
        //             elementType: "labels.text.fill",
        //             stylers: [{ color: "#f3d19c" }],
        //         },
        //         {
        //             featureType: "transit",
        //             elementType: "geometry",
        //             stylers: [{ color: "#2f3948" }],
        //         },
        //         {
        //             featureType: "transit.station",
        //             elementType: "labels.text.fill",
        //             stylers: [{ color: "#d59563" }],
        //         },
        //         {
        //             featureType: "water",
        //             elementType: "geometry",
        //             stylers: [{ color: "#17263c" }],
        //         },
        //         {
        //             featureType: "water",
        //             elementType: "labels.text.fill",
        //             stylers: [{ color: "#515c6d" }],
        //         },
        //         {
        //             featureType: "water",
        //             elementType: "labels.text.stroke",
        //             stylers: [{ color: "#17263c" }],
        //         },
        //     ];
        // }

        let defaultLocation = await getDefaultLocation();

        const map = new google.maps.Map(document.getElementById("map_canvas"), {
            // zoom: 3,
            // styles: style,
            // mapTypeControl: false,
            // streetViewControl: false,
            center: defaultLocation, // {lat:30.7333, lng:76.7794},
            zoom: 10,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            styles: style,
            // mapTypeId: "roadmap",
            // center: result[0][0],
        });

        return map;
    }
}
async function getDefaultLocation() {
    // if ($("#agents_map_data").val() != "") {
    //     allagent = JSON.parse($("#agents_map_data").val());
    // }
    if(agentsLatLong != ''){
        allagent = JSON.parse(agentsLatLong);
    }

    if ($("#agentslocations_map_data").val() != "") {
        defaultmaplocation = JSON.parse($("#agentslocations_map_data").val());
        defaultlat = parseFloat(defaultmaplocation[0].lat);
        defaultlong = parseFloat(defaultmaplocation[0].long);
    }

    const defaultLocation = {
        lat:
            allagent.length !== 0 &&
            allagent[0].agentlog &&
            allagent[0].agentlog["lat"] !== "0.00000000" &&
            allagent[0].agentlog["lat"] !== null
                ? parseFloat(allagent[0].agentlog["lat"])
                : defaultlat,
        lng:
            allagent.length !== 0 &&
            allagent[0].agentlog &&
            allagent[0].agentlog["long"] !== "0.00000000" &&
            allagent[0].agentlog["long"] !== null
                ? parseFloat(allagent[0].agentlog["long"])
                : defaultlong,
    };

    return defaultLocation;
}
