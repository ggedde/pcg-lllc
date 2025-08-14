<?php
/**
 * Map View
 */

$categories = !empty($vars->entries) ? array_values(array_unique(array_column($vars->entries, 'category'))) : [];
sort($categories);

foreach ($categories as $categoryIndex => $category) {
    if (!isset($vars->categoryColors->$category)) {
        $vars->categoryColors->$category = '000000';
    }
}

foreach ($vars->entries as &$entry) {
    $category = $entry->category;
    $entry->categoryColor = !empty($vars->categoryColors->$category) ? $vars->categoryColors->$category : '000000';
}

?>
<body>
    <div class="map-container">
        <div id="map" style="height:100%;width:100%;"></div>
    </div>
<script>

    var map;
    let markers = [];
    var infoWindows = [];
    var categories = <?= json_encode($categories); ?>;
    var iconURL = '/assets/map-marker-0.png';
    var entries = <?= json_encode($vars->entries); ?>;
    var filteredLocations = [];
    var incrementValue = 100000;

    const currencyFormatter = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    });

    document.ontouchmove = function(event){
        event.preventDefault();
    }

    function initMap()
    {
        var wWidth = window.innerWidth;
        var marker;

        const OGStyles = [{"elementType":"geometry","stylers":[{"color":"#f5f5f5"}]},{"elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"elementType":"labels.text.fill","stylers":[{"color":"#616161"}]},{"elementType":"labels.text.stroke","stylers":[{"color":"#f5f5f5"}]},{"featureType":"administrative","elementType":"geometry","stylers":[{"visibility":"off"}]},{"featureType":"administrative.country","elementType":"geometry.fill","stylers":[{"color":"#ead948"},{"visibility":"on"},{"weight":4}]},{"featureType":"administrative.country","elementType":"geometry.stroke","stylers":[{"color":"#ffffff"},{"visibility":"on"},{"weight":4}]},{"featureType":"administrative.country","elementType":"labels.text","stylers":[{"visibility":"off"}]},{"featureType":"administrative.land_parcel","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"administrative.land_parcel","elementType":"labels.text.fill","stylers":[{"color":"#bdbdbd"}]},{"featureType":"administrative.province","elementType":"geometry.fill","stylers":[{"visibility":"on"},{"weight":5}]},{"featureType":"administrative.province","elementType":"geometry.stroke","stylers":[{"color":"#ffffff"},{"visibility":"on"},{"weight":2.75}]},{"featureType":"administrative.province","elementType":"labels.text.fill","stylers":[{"color":"#15277a"},{"visibility":"on"}]},{"featureType":"administrative.province","elementType":"labels.text.stroke","stylers":[{"visibility":"off"}]},{"featureType":"landscape","elementType":"geometry.fill","stylers":[{"color":"#b4ebfc"},{"visibility":"on"}]},{"featureType":"poi","stylers":[{"visibility":"off"}]},{"featureType":"poi","elementType":"geometry","stylers":[{"color":"#eeeeee"}]},{"featureType":"poi","elementType":"labels.text","stylers":[{"visibility":"off"}]},{"featureType":"poi","elementType":"labels.text.fill","stylers":[{"color":"#757575"}]},{"featureType":"poi.park","elementType":"geometry","stylers":[{"color":"#e5e5e5"}]},{"featureType":"poi.park","elementType":"labels.text.fill","stylers":[{"color":"#9e9e9e"}]},{"featureType":"road","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"geometry","stylers":[{"color":"#ffffff"}]},{"featureType":"road","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"road.arterial","elementType":"labels.text.fill","stylers":[{"color":"#757575"}]},{"featureType":"road.highway","elementType":"geometry","stylers":[{"color":"#dadada"}]},{"featureType":"road.highway","elementType":"labels.text.fill","stylers":[{"color":"#616161"}]},{"featureType":"road.local","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"road.local","elementType":"labels.text.fill","stylers":[{"color":"#9e9e9e"}]},{"featureType":"transit","stylers":[{"visibility":"off"}]},{"featureType":"transit.line","elementType":"geometry","stylers":[{"color":"#e5e5e5"}]},{"featureType":"transit.station","elementType":"geometry","stylers":[{"color":"#eeeeee"}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#c9c9c9"}]},{"featureType":"water","elementType":"geometry.fill","stylers":[{"color":"#ffffff"},{"visibility":"on"}]},{"featureType":"water","elementType":"labels.text.fill","stylers":[{"color":"#9e9e9e"}]}];

        const whiteMapStyle = [
            { elementType: 'geometry', stylers: [{ color: '#ffffff' }] },
            { elementType: 'labels', stylers: [{ visibility: 'on' }] },
            { featureType: 'administrative', stylers: [{ visibility: 'on' }] },
            { featureType: 'poi', stylers: [{ visibility: 'off' }] },
            { featureType: 'road', elementType: 'geometry', stylers: [{ visibility: 'on' }, { color: '#cccccc' }] },
            { featureType: 'transit', stylers: [{ visibility: 'off' }] },
            { featureType: 'water', stylers: [{ color: '#ffffff' }] },
            {
  "elementType": "labels.text.fill",
  "stylers": [ { "color": "#888888" } ]
}
        ];

        const nevadaBounds = {
            north: 47.0,   // northern border
            south: 29.0,   // southern border
            west: -126.0,  // western border
            east: -108.0   // eastern border
        };

        map = new google.maps.Map(document.getElementById('map'), {
            mapTypeControlOptions: {
                mapTypeIds: [google.maps.MapTypeId.ROADMAP]
            }, // here´s the array of controls
            disableDefaultUI: true, // a way to quickly hide all controls
            mapTypeControl: false,
            scaleControl: false,
            scrollwheel: false,
            zoomControl: true,
            zoomControlOptions: {
                style: google.maps.ZoomControlStyle.LARGE 
            },
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            // center: { lat: 39.0, lng: -116.75 },
            // zoom: wWidth > 600 ? 5.8 : 5,
            restriction: {
                latLngBounds: nevadaBounds,
                // strictBounds: true,
            },
            minZoom: 5,
            maxZoom: 13,
            styles: whiteMapStyle
        });

        map.data.loadGeoJson('<?= APP_HOST.ASSETS_URI; ?>/nevada.json');

        map.data.setStyle({
            fillColor: '#e4e4e4',
            strokeColor: '#cccccc',
            strokeWeight: 2
        });

        // google.maps.event.addListenerOnce(map, 'bounds_changed', function() {
        //     map.setZoom(wWidth > 600 ? 5.8 : 5); // or whatever zoom level you want
        // });

        // map.fitBounds(nevadaBounds);
        setLocations();
    }

    function trimLength(value, length) {
        if (value.length > length) {
            value = value.substring(0, length) + '...';
        }
        return value;
    }

    function setLocations() {

        var bounds = new google.maps.LatLngBounds();

        for (var e in entries) {

            if (!entries[e].latitude || !entries[e].longitude || entries[e].error) {
                continue; // Don't load ones with errors
            }

            // build infoWindow
            var infoWindow = new google.maps.InfoWindow({
                content: '<div class="infowindow">'+
                    '<h6 class="mb-2">'+trimLength(entries[e].name, 60)+'</h6>'+
                    '<p class="display-field-address">'+entries[e].address+'</p>'+
                    '<p class="display-field-url pt-1"><a class="btn btn-secondary color-purple bold" target="_blank" href="'+entries[e].url+'">Click Here For Events</a></p>'+
                '</div>',
            });
            infoWindows.push(infoWindow);

            let iconColor = '#000000';

            // set icon url from array
            if (entries[e].categoryColor) {
                iconColor = '#'+entries[e].categoryColor.replace('#', '');
            }

            // create marker and set per type
            marker = new google.maps.Marker({
                position: {lat: parseFloat(entries[e].latitude), lng: parseFloat(entries[e].longitude)},
                map: map,
                title: trimLength(entries[e].name, 60),
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    fillColor: iconColor,     // <== HEX color here
                    fillOpacity: 1,
                    strokeWeight: 0,
                    scale: 4,                 // Diameter in pixels
                },
                entryId: parseInt(e)
            });

            // set event listener for markers
            google.maps.event.addListener(marker,'click', (function(marker,infoWindow,infoWindows) {
                return function() {
                    for(var i in infoWindows) {
                        infoWindows[i].close();
                    }
                    infoWindow.open({
                        anchor: marker,
                        shouldFocus: false
                    });
                };
            })(marker,infoWindow,infoWindows));

            markers.push(marker);
            bounds.extend(marker.getPosition());

        }

        const listener = map.addListener("bounds_changed", function () {
            const maxZoom = 7;
            if (map.getZoom() > maxZoom) {
                map.setZoom(maxZoom);
            }
            google.maps.event.removeListener(listener); // Run only once
        });

        map.fitBounds(bounds);

        // noLocations();
    }

    function noLocations()
    {
        var center = new google.maps.LatLng("38.85758045976994", "-98.81608132538786");
        map.setCenter(center);
    }

    function clearMarkers()
    {
        for (let i = 0; i < markers.length; i++) {
            markers[i].setMap(null);
        }
    }

</script>
<script type='text/javascript' src='//maps.googleapis.com/maps/api/js?key=<?= $vars->googleApiKey ? $vars->googleApiKey : ''; ?>&ver=20180820&callback=initMap'></script>
</body>
