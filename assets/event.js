import autosize from 'autosize';

var $address = $('#event_address'),
    $lat = $('#event_lat'),
    $lng = $('#event_lng'),
    $type = $('select[name="event[type]"]'),
    $momentsSection = $('#special-standard_plus_moments');

function toggleMomentsSection() {
    if ($type.val() === 'standard_plus_moments') {
        $momentsSection.show();
    } else {
        $momentsSection.hide();
    }
}

// Initial state
toggleMomentsSection();

// Listen for changes
$type.on('change', toggleMomentsSection);

autosize($address);

$address.on('change blur keyup keypress', function(){

    if ($address.val() === '') {

        $lat.val('');
        $lng.val('');
    }
})

function initAutocomplete() {

    let center = { lat: 46.61617, lng: 2.70825 };
    let markers = [];
    let initialMarker = null;

    if ($lat.val() && $lng.val()) {

        center = { lat: parseFloat($lat.val()), lng: parseFloat($lng.val()) };
    }

    const map = new google.maps.Map(document.getElementById("map"), {
        center: center,
        zoom: 6,
        mapTypeId: "roadmap",
    });

    if ($lat.val() && $lng.val()) {

        initialMarker = new google.maps.Marker({
            map,
            position: center,
        });

        markers.push(initialMarker);
    }


    // Create the search box and link it to the UI element.
    const input = document.getElementById("event_autocomplete");
    const searchBox = new google.maps.places.SearchBox(input);
    // map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);
    // Bias the SearchBox results towards current map's viewport.
    map.addListener("bounds_changed", () => {
        searchBox.setBounds(map.getBounds());
    });
    // Listen for the event fired when the user selects a prediction and retrieve
    // more details for that place.
    searchBox.addListener("places_changed", () => {
        const places = searchBox.getPlaces();

        if (places.length == 0) {
            return;
        }
        // Clear out the old markers.
        markers.forEach((marker) => {
            marker.setMap(null);
        });
        markers = [];
        // For each place, get the icon, name and location.
        const bounds = new google.maps.LatLngBounds();
        places.forEach((place) => {
            if (!place.geometry || !place.geometry.location) {
                console.log("Returned place contains no geometry");
                return;
            }
            const icon = {
                url: place.icon,
                size: new google.maps.Size(71, 71),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(17, 34),
                scaledSize: new google.maps.Size(25, 25),
            };

            // Create a marker for each place.
            markers.push(
                new google.maps.Marker({
                    map,
                    title: place.name,
                    position: place.geometry.location,
                })
            );

            var address = place.formatted_address.replace(', France', '').replaceAll(', ', "\n");

            if (!(place.types.indexOf('points_of_interest') === -1 && place.types.indexOf('establishment') === -1)) {

                address = place.name+"\n"+address;
            }

            autosize.update($address.val(address));

            $lat.val(place.geometry.location.lat);
            $lng.val(place.geometry.location.lng);

            if (place.geometry.viewport) {
                // Only geocodes have viewport.
                bounds.union(place.geometry.viewport);
            } else {
                bounds.extend(place.geometry.location);
            }
        });
        map.fitBounds(bounds);
    });
}

global.initAutocomplete = initAutocomplete;