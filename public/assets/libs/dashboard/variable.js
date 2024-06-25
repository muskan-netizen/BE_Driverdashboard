var themeType = [{
    featureType: "poi",
    elementType: "labels",
    stylers: [{
        visibility: "off"
    }]
}];
var style= [];
var mark = [];
var agent_mark = [];
var extended_map = 0;
var latlongs;
var show = [0];
var order_tag=[];
var marker = null;
var map;
var markers = [];
var driverMarkers = [];
var privesRoute = [];
var directionsArray = [];
var is_initMap = true;
var url = window.location.origin;
var directionDisplay;
var directionsService;
var stepDisplay;
var position;
var polyline = null;
var poly2 = null;
var speed = 0.000005, wait = 1;
var infowindow = null;
var directionsRenderer;

var myPano;   
var panoClient;
var nextPanoId;
var timerHandle = null;

var show = [0];
var olddata  = [];
var allagent = [];

// for getting default map location
var defaultmaplocation = [];
var defaultlat  = 0.000;
var defaultlong = 0.000;
var allroutes = [];
var old_channelname = old_logchannelname = '';
var newicons = '';
var is_show_loader = 0;
var is_load_html = 0;
var allRenderRoute = [];
var url = window.location.origin;