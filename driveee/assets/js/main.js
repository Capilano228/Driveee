let map;
let pickupMarker, dropoffMarker;

function initMap() {
    const container = document.getElementById('map');
    if (!container) return;
    
    if (typeof ymaps === 'undefined') {
        container.innerHTML = '<div style="padding:2rem;text-align:center;color:#666;">⚠️ Яндекс.Карты не загружены. Проверьте API ключ в config.php</div>';
        return;
    }
    
    ymaps.ready(() => {
        map = new ymaps.Map('map', { 
            center: [55.751574, 37.573856], 
            zoom: 12, 
            controls: ['zoomControl', 'fullscreenControl', 'geolocationControl'] 
        });
        
        const search = new ymaps.control.SearchControl({ 
            options: { provider: 'yandex#search', noPlacemark: true, resultsPerPage: 5 } 
        });
        map.controls.add(search);
        
        search.events.add('resultselect', e => {
            search.getResult(e.get('index')).then(res => {
                const coords = res.geometry.getCoordinates();
                const address = res.getAddressLine();
                if (document.activeElement?.id === 'pickupAddress') {
                    setPickup(address, coords);
                } else if (document.activeElement?.id === 'dropoffAddress') {
                    setDropoff(address, coords);
                }
            });
        });
        
        map.events.add('click', e => {
            ymaps.geocode(e.get('coords')).then(res => {
                const address = res.geoObjects.get(0).getAddressLine();
                const coords = e.get('coords');
                if (document.activeElement?.id === 'pickupAddress') {
                    setPickup(address, coords);
                } else if (document.activeElement?.id === 'dropoffAddress') {
                    setDropoff(address, coords);
                } else {
                    if (confirm(`Выбрать как адрес посадки: ${address}?`)) {
                        setPickup(address, coords);
                    } else {
                        setDropoff(address, coords);
                    }
                }
            });
        });
        
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(pos => {
                const coords = [pos.coords.latitude, pos.coords.longitude];
                map.setCenter(coords, 14);
                ymaps.geocode(coords).then(res => {
                    setPickup(res.geoObjects.get(0).getAddressLine(), coords);
                });
            });
        }
    });
}

function setPickup(address, coords) {
    document.getElementById('pickupAddress').value = address;
    document.getElementById('pickupLat').value = coords[0];
    document.getElementById('pickupLng').value = coords[1];
    if (pickupMarker) map.geoObjects.remove(pickupMarker);
    pickupMarker = new ymaps.Placemark(coords, { 
        balloonContent: '📍 Точка посадки' 
    }, { 
        preset: 'islands#greenStretchyIcon', 
        draggable: true 
    });
    pickupMarker.events.add('dragend', e => {
        const c = pickupMarker.geometry.getCoordinates();
        ymaps.geocode(c).then(r => setPickup(r.geoObjects.get(0).getAddressLine(), c));
    });
    map.geoObjects.add(pickupMarker);
    if (document.getElementById('dropoffLat').value) drawRoute();
}

function setDropoff(address, coords) {
    document.getElementById('dropoffAddress').value = address;
    document.getElementById('dropoffLat').value = coords[0];
    document.getElementById('dropoffLng').value = coords[1];
    if (dropoffMarker) map.geoObjects.remove(dropoffMarker);
    dropoffMarker = new ymaps.Placemark(coords, { 
        balloonContent: '🏁 Точка назначения' 
    }, { 
        preset: 'islands#redStretchyIcon', 
        draggable: true 
    });
    dropoffMarker.events.add('dragend', e => {
        const c = dropoffMarker.geometry.getCoordinates();
        ymaps.geocode(c).then(r => setDropoff(r.geoObjects.get(0).getAddressLine(), c));
    });
    map.geoObjects.add(dropoffMarker);
    calculatePrice();
    drawRoute();
}

function drawRoute() {
    const lat1 = parseFloat(document.getElementById('pickupLat')?.value);
    const lng1 = parseFloat(document.getElementById('pickupLng')?.value);
    const lat2 = parseFloat(document.getElementById('dropoffLat')?.value);
    const lng2 = parseFloat(document.getElementById('dropoffLng')?.value);
    
    if (lat1 && lat2) {
        const route = new ymaps.Polyline([[lat1, lng1], [lat2, lng2]], {}, { 
            strokeColor: '#6bff73', 
            strokeWidth: 5,
            strokeOpacity: 0.8 
        });
        map.geoObjects.add(route);
        map.setBounds([[Math.min(lat1, lat2), Math.min(lng1, lng2)], [Math.max(lat1, lat2), Math.max(lng1, lng2)]], { 
            zoomMargin: 50 
        });
        setTimeout(() => map.geoObjects.remove(route), 5000);
    }
}

function calculatePrice() {
    const lat1 = parseFloat(document.getElementById('pickupLat')?.value);
    const lng1 = parseFloat(document.getElementById('pickupLng')?.value);
    const lat2 = parseFloat(document.getElementById('dropoffLat')?.value);
    const lng2 = parseFloat(document.getElementById('dropoffLng')?.value);
    
    if (lat1 && lat2) {
        const R = 6371;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lng2 - lng1) * Math.PI / 180;
        const a = Math.sin(dLat/2)**2 + Math.cos(lat1 * Math.PI/180) * Math.cos(lat2 * Math.PI/180) * Math.sin(dLon/2)**2;
        const dist = R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        const price = Math.round(dist * 35 + 70);
        document.getElementById('priceEstimate').innerHTML = price + ' ₽';
        return price;
    }
    return 0;
}

window.initMap = initMap;
window.calculatePrice = calculatePrice;
window.setPickup = setPickup;
window.setDropoff = setDropoff;