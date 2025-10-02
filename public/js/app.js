const map = L.map('map').setView([46.603354, 1.888334], 6);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a>'
}).addTo(map);

let userMarker = null;
let stationMarkers = new Map();
let currentPosition = null;

const searchForm = document.getElementById('search-form');
const radiusInput = document.getElementById('radius');
const radiusValue = document.getElementById('radius-value');
const stationList = document.getElementById('station-list');
const cheapestContainer = document.getElementById('cheapest-fuels');
const fuelSelect = document.getElementById('fuel');

radiusInput.addEventListener('input', () => {
    radiusValue.textContent = `${radiusInput.value} km`;
});

map.on('click', (event) => {
    setPosition(event.latlng.lat, event.latlng.lng, true);
    fetchStations();
});

searchForm.addEventListener('submit', (event) => {
    event.preventDefault();
    if (!currentPosition) {
        alert('Sélectionnez un point sur la carte ou activez la géolocalisation.');
        return;
    }
    fetchStations();
});

function setPosition(lat, lng, moveMap = false) {
    currentPosition = { lat, lng };
    if (!userMarker) {
        userMarker = L.marker([lat, lng], { icon: L.icon({
            iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
        }) });
        userMarker.addTo(map).bindPopup('Votre position');
    } else {
        userMarker.setLatLng([lat, lng]);
    }
    userMarker.openPopup();
    if (moveMap) {
        map.flyTo([lat, lng], 12);
    }
}

function fetchStations() {
    if (!currentPosition) return;
    const radius = radiusInput.value;
    const fuel = fuelSelect.value;
    toggleLoading(true);
    const params = new URLSearchParams({
        lat: currentPosition.lat,
        lng: currentPosition.lng,
        radius
    });
    if (fuel) {
        params.append('fuel', fuel);
    }
    fetch(`/api/stations.php?${params.toString()}`)
        .then(async (response) => {
            if (!response.ok) {
                throw new Error('Réponse invalide du serveur');
            }
            return response.json();
        })
        .then((data) => {
            updateFuelOptions(data.fuel_types);
            updateCheapestTiles(data.cheapest);
            updateStationsList(data.stations);
            toggleLoading(false);
            if (data.stations.length) {
                const bounds = L.latLngBounds(data.stations.map((station) => [station.latitude, station.longitude]));
                bounds.extend([currentPosition.lat, currentPosition.lng]);
                map.fitBounds(bounds.pad(0.2));
            }
        })
        .catch((error) => {
            console.error(error);
            alert("Impossible de récupérer les stations pour le moment.");
            toggleLoading(false);
        });
}

function updateFuelOptions(fuelTypes) {
    if (!fuelTypes) return;
    const selected = fuelSelect.value;
    Array.from(fuelSelect.querySelectorAll('option')).forEach((option, index) => {
        if (index !== 0) option.remove();
    });
    fuelTypes.forEach((fuel) => {
        const option = document.createElement('option');
        option.value = fuel.code;
        option.textContent = `${fuel.name} (${fuel.code})`;
        fuelSelect.appendChild(option);
    });
    if (selected) {
        fuelSelect.value = selected;
    }
}

function updateCheapestTiles(entries) {
    cheapestContainer.innerHTML = '';
    if (!entries || entries.length === 0) {
        cheapestContainer.innerHTML = '<p>Aucun carburant trouvé dans ce rayon.</p>';
        return;
    }
    entries.forEach((entry) => {
        const tile = document.createElement('article');
        tile.className = 'fuel-tile';
        tile.innerHTML = `
            <div class="fuel-tile__header">
                <h3>${entry.fuel_name}</h3>
                <span class="fuel-price">${formatPrice(entry.price)}</span>
            </div>
            <p class="fuel-station">${entry.station_name}</p>
            <p class="fuel-distance">${entry.distance.toFixed(1)} km</p>
            <button class="btn-secondary" data-action="focus" data-id="${entry.station_id}">Voir sur la carte</button>
            <a class="btn-primary" target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&destination=${entry.latitude},${entry.longitude}">Itinéraire</a>
        `;
        tile.addEventListener('click', (event) => {
            if (event.target.matches('[data-action="focus"]')) {
                event.stopPropagation();
                focusStation(entry.station_id);
            }
        });
        tile.addEventListener('mouseenter', () => focusStation(entry.station_id, false));
        cheapestContainer.appendChild(tile);
    });
}

function updateStationsList(stations) {
    stationList.innerHTML = '';
    stationMarkers.forEach((marker) => map.removeLayer(marker));
    stationMarkers.clear();

    if (!stations || stations.length === 0) {
        stationList.innerHTML = '<p>Aucune station dans ce rayon. Essayez d\'augmenter le rayon ou de choisir un autre point.</p>';
        return;
    }

    stations.forEach((station) => {
        const marker = L.marker([station.latitude, station.longitude]).addTo(map);
        marker.bindPopup(`
            <strong>${station.name}</strong><br>
            ${station.address}<br>
            ${station.postal_code || ''} ${station.city || ''}<br>
            Distance : ${station.distance.toFixed(1)} km
        `);
        stationMarkers.set(station.id, marker);

        const stationCard = document.createElement('article');
        stationCard.className = 'station-card';
        const fuels = station.fuels.map((fuel) => `
            <li>
                <span>${fuel.name}</span>
                <strong>${formatPrice(fuel.price)}</strong>
                <small>MAJ ${formatDate(fuel.last_update)}</small>
            </li>
        `).join('');
        stationCard.innerHTML = `
            <div class="station-card__header">
                <h4>${station.name}</h4>
                <span class="station-distance">${station.distance.toFixed(1)} km</span>
            </div>
            <p class="station-address">${station.address}<br>${station.postal_code || ''} ${station.city || ''}</p>
            <ul class="station-fuels">${fuels}</ul>
            <div class="station-actions">
                <button class="btn-secondary" data-action="focus" data-id="${station.id}">Afficher sur la carte</button>
                <a class="btn-primary" target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&destination=${station.latitude},${station.longitude}">Itinéraire</a>
            </div>
        `;
        stationCard.addEventListener('click', (event) => {
            if (event.target.matches('[data-action="focus"]')) {
                event.stopPropagation();
                focusStation(station.id);
            }
        });
        stationList.appendChild(stationCard);
    });
}

function focusStation(stationId, openPopup = true) {
    const marker = stationMarkers.get(stationId);
    if (marker) {
        map.flyTo(marker.getLatLng(), 14);
        if (openPopup) {
            marker.openPopup();
        }
    }
}

function formatPrice(value) {
    return `${value.toFixed(3)} €`;
}

function formatDate(value) {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }
    return date.toLocaleDateString('fr-FR', { year: 'numeric', month: 'short', day: 'numeric' });
}

function toggleLoading(state) {
    searchForm.classList.toggle('is-loading', state);
}

if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition((position) => {
        setPosition(position.coords.latitude, position.coords.longitude, true);
        fetchStations();
    }, () => {
        displayGeolocationFallback();
    });
} else {
    displayGeolocationFallback();
}

function displayGeolocationFallback() {
    if (!document.querySelector('.geoloc-hint')) {
        const info = document.createElement('p');
        info.className = 'geoloc-hint';
        info.textContent = 'Cliquez sur la carte pour définir un point de recherche.';
        searchForm.parentElement.insertBefore(info, searchForm.nextSibling);
    }
    map.setView([48.8566, 2.3522], 11);
}
