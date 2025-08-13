function initMap() {

    let center = { lat: event_lat, lng: event_lng };

    const map = new google.maps.Map(document.getElementById("map"), {
        center: center,
        zoom: 7,
        mapTypeId: "roadmap",
    });

    new google.maps.Marker({
        map,
        position: center,
    });
}

global.initMap = initMap;