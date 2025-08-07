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
<body id="app" v-cloak>
    <div class="map-container">
        <div id="map" style="height:100%;width:100%;"></div>
    </div>
    <div class="modal blur" :class="{show: showNoLocations}" @click="showNoLocations=false">
        <div class="modal-content" style="max-width: 400px;">
            <div class="card card-light bg-light shadow color-text border rounded">
                <header>
                    <button type="button" class="btn btn-link btn-close circle mr-1 mt-1 inset-top inset-right" @click="showNoLocations=false"></button>
                    <h4 class="px-2 pr-3">No Locations Found</h4>
                </header>
                <p class="p-3">Try changing the filters and try again.</p>
            </div>
        </div>
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
            { elementType: 'labels', stylers: [{ visibility: 'off' }] },
            { featureType: 'administrative', stylers: [{ visibility: 'off' }] },
            { featureType: 'poi', stylers: [{ visibility: 'off' }] },
            { featureType: 'road', stylers: [{ visibility: 'off' }] },
            { featureType: 'transit', stylers: [{ visibility: 'off' }] },
            { featureType: 'water', stylers: [{ color: '#ffffff' }] }
        ];

        const nevadaBounds = {
            north: 43.0,   // northern border
            south: 34.0,   // southern border
            west: -123.0,  // western border
            east: -110.0   // eastern border
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
            // zoom: wWidth > 600 ? 4 : (wWidth > 500 ? 3.6 : 3),
            center: { lat: 39.0, lng: -116.75 },
            zoom: 5.8,
            restriction: {
                latLngBounds: nevadaBounds,
                // strictBounds: true,
            },
            minZoom: 5.5,
            maxZoom: 10,
            styles: whiteMapStyle
        });

        map.data.loadGeoJson('<?= APP_HOST.ASSETS_URI; ?>/nevada.json');

        map.data.setStyle({
            fillColor: '#e4e4e4',
            strokeColor: '#cccccc',
            strokeWeight: 2
        });

        google.maps.event.addListenerOnce(map, 'bounds_changed', function() {
            map.setZoom(6); // or whatever zoom level you want
        });

        map.fitBounds(nevadaBounds);
        setLocations();
    }

    function trimLength(value, length) {
        if (value.length > length) {
            value = value.substring(0, length) + '...';
        }
        return value;
    }

    function setLocations() {

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
        }

        noLocations();
    }

    function GoogleMapFilterMarkers(state, category, min, max) {

        mapFilteredLocations = [];

        if (typeof state === 'undefined') {
            state = null;
        }

        if (typeof category === 'undefined') {
            category = null;
        }

        if (typeof min === 'undefined') {
            min = null;
        }

        if (typeof max === 'undefined') {
            max = null;
        }

        clearMarkers();

        var bounds = new google.maps.LatLngBounds();

        for (var e in markers) {

            if (!markers[e]) {
                continue; // Don't load ones with errors
            }

            var entryId = markers[e].entryId;

            if (!entries[entryId].latitude || !entries[entryId].longitude || entries[entryId].error) {
                continue; // Don't load ones with errors
            }

            if (state && entries[entryId].state !== state) {
                continue;
            }

            if (category && entries[entryId].category !== category) {
                continue;
            }

            if (min && entries[entryId].amount < min) {
                continue;
            }

            if (max && entries[entryId].amount > max) {
                continue;
            }

            markers[e].setMap(map);
            bounds.extend(markers[e].getPosition());
            mapFilteredLocations.push(entries[entryId]);
        }

        if (mapFilteredLocations.length) {
            map.fitBounds(bounds);
        } else {
            noLocations();
        }
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

    function getKeyByValue(object, value) {
        return Object.keys(object).find(key => object[key] === value);
    }

</script>
<script type='text/javascript' src='//maps.googleapis.com/maps/api/js?key=<?= $vars->googleApiKey ? $vars->googleApiKey : ''; ?>&ver=20180820&callback=initMap'></script>
<script type="module">
    import { createApp } from '<?= ASSETS_URI; ?>/petite-vue.module.min.js';
    createApp({
        categories: categories,
        selectedCategory: null,
        showNoLocations: false,
        isFiltered: true,
        filteredLocations: [],
        selectCategory(category) {
            this.selectedCategory = category;
            this.updateLocations();
        },
        updateLocations() {
            this.filteredLocations = [];
            var hasLocations = false;
            entries.forEach(entry => {
                if (
                    (!this.selectedCategory || entry.category === this.selectedCategory)) {
                    hasLocations = true;
                }
            });

            if (!hasLocations) {
                this.showNoLocations = true;
            }

            GoogleMapFilterMarkers(this.selectedState, this.selectedCategory, this.selectedMin, (this.selectedMax ? this.selectedMax : 10000000000000));
            this.filteredLocations = mapFilteredLocations;
        },
        clearFilters() {
            this.selectedCategory = null;
            this.updateLocations();
        }
    }).mount('#app');
</script>
<style>
    .display-field-program {
        margin-top: 0;
        display: <?php echo (in_array('program', $vars->displayFields, true)) ? 'block' : 'none'; ?>;
    }
    .display-field-congressional-district {
        display: <?php echo (in_array('congressional_district', $vars->displayFields, true)) ? 'block' : 'none'; ?>;
    }
    .display-field-agency {
        display: <?php echo (in_array('agency', $vars->displayFields, true)) ? 'block' : 'none'; ?>;
    }
    .display-field-category {
        display: <?php echo (in_array('category', $vars->displayFields, true)) ? 'block' : 'none'; ?>;
    }
</style>
</body>
