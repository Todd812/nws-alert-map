<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>NWS Weather Alerts - Smart Fire Zones</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        if (typeof L.DomEvent.fakeStop === 'undefined') {
            L.DomEvent.fakeStop = function(e) {
                if (e) e._stopped = true;
            };
        }
    </script>

    <script src="https://unpkg.com/leaflet.vectorgrid@latest/dist/Leaflet.VectorGrid.bundled.js"></script>
    <script src="https://unpkg.com/@turf/turf@6/turf.min.js"></script>
    <script src="https://unpkg.com/shpjs@latest/dist/shp.min.js"></script>

    <style>
        body { margin:0; padding:0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif; }
        #map { position:absolute; top:0; bottom:0; width:100%; }

        .gear-button {
            position: absolute;
            bottom: 24px;
            right: 24px;
            width: 56px;
            height: 56px;
            background: white;
            border-radius: 50%;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 1000;
            font-size: 28px;
            color: #444;
            transition: all 0.2s ease;
        }
        .gear-button:hover {
            transform: scale(1.08);
            box-shadow: 0 6px 24px rgba(0,0,0,0.2);
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(6px);
            z-index: 10000;
            animation: fadeIn 0.3s ease-out;
        }
        .modal-overlay.active {
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }
        @media (min-width: 768px) {
            .modal-overlay.active { align-items: center; }
        }

        .modal-container {
            background: white;
            width: 100%;
            height: 80vh;
            max-height: 90vh;
            border-radius: 0;
            box-shadow: 0 -8px 40px rgba(0,0,0,0.25);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @media (min-width: 768px) {
            .modal-container {
                width: 680px;
                height: auto;
                max-height: 90vh;
                border-radius: 0;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
        }

        .modal-header {
            padding: 0px 0px;
            background: #0066cc;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            flex-shrink: 0;
            min-height: 20px;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 36px;
            color: white;
            cursor: pointer;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            opacity: 0.9;
            transition: opacity 0.2s;
        }
        .modal-close:hover { opacity: 1; }

        .modal-content {
            padding: 0;
            overflow-y: auto;
            flex: 1;
            background: #f8fbff;
        }

        .alert-count {
            background: #e3f2fd;
            color: #0066cc;
            padding: 16px;
            text-align: center;
            font-weight: 600;
            font-size: 16px;
            border-bottom: 1px solid #ddd;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .alert-item {
            background: white;
            border-bottom: 1px solid #e0e0e0;
            padding: 20px 24px;
        }
        .alert-headline {
            font-size: 17px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 8px;
            line-height: 1.4;
        }
        .alert-event {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 6px;
            line-height: 1.3;
        }
        /* Only show event type for LSRs */
        .alert-item:not(.lsr) .alert-event {
            display: none;
        }
        .alert-item.lsr {
            border-left: 5px solid #FF8C00 !important;
        }
        .alert-item.lsr .alert-event {
            color: #FF8C00 !important;
        }

        .alert-description {
            font-size: 15px;
            line-height: 1.7;
            color: #333;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #f0f0f0;
            white-space: pre-wrap;
        }
        .no-alerts {
            text-align: center;
            padding: 80px 20px;
            color: #888;
            font-size: 17px;
        }

        /* LSR DivIcon - color-coded, smaller (24px), tappable on mobile */
        .lsr-marker-div > div {
            width: 100%;
            height: 100%;
            border: 3px solid #000;
            border-radius: 50%;
            box-shadow: 0 2px 6px rgba(0,0,0,0.4);
        }
        .lsr-marker-div:after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            transform: translate(-50%, -50%);
        }

        .filter-section {
            margin-bottom: 32px;
            background: #f9f9fb;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .section-header {
            padding: 16px 20px;
            background: #ffffff;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            user-select: none;
        }
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        .section-controls {
            display: flex;
            gap: 12px;
            align-items: center;
            font-size: 14px;
        }
        .section-controls button {
            background: none;
            border: none;
            color: #0066cc;
            cursor: pointer;
            font-weight: 500;
        }
        .section-controls button:hover {
            text-decoration: underline;
        }
        .reset-colors-btn {
            background: #ff3b30;
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 13px;
            margin: 20px;
        }
        .reset-colors-btn:hover {
            background: #ff1900;
        }

        .section-content {
            padding: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 14px;
        }
        .filter-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: white;
            border-radius: 12px;
            transition: background 0.2s;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }
        .filter-item:hover {
            background: #f0f8ff;
        }
        .filter-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: #0066cc;
            flex-shrink: 0;
        }
        .filter-color-picker {
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            flex-shrink: 0;
            box-shadow: 0 1px 4px rgba(0,0,0,0.15);
        }
        .filter-current-color {
            width: 24px;
            height: 24px;
            border-radius: 6px;
            border: 2px solid #ddd;
            flex-shrink: 0;
        }
        .filter-label {
            flex: 1;
            font-size: 15px;
            color: #333;
            font-weight: 500;
        }

        .url-override-notice {
            background: #fff3cd;
            padding: 16px;
            border-radius: 12px;
            margin: 20px;
            color: #856404;
            font-weight: 500;
            border: 1px solid #ffeaa7;
        }

        .layer-section {
            margin: 0 20px 32px 20px;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .layer-section-header {
            padding: 16px 20px;
            background: #f0f8ff;
            border-bottom: 1px solid #ddd;
            font-size: 18px;
            font-weight: 600;
            color: #0066cc;
        }
        .layer-list {
            padding: 12px 0;
        }
        .layer-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            border-bottom: 1px solid #eee;
        }
        .layer-item:last-child {
            border-bottom: none;
        }
        .layer-label {
            font-size: 16px;
            color: #333;
        }
        .layer-toggle {
            width: 52px;
            height: 32px;
            position: relative;
            display: inline-block;
        }
        .layer-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #0066cc;
        }
        input:checked + .slider:before {
            transform: translateX(20px);
        }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp {
            from { transform: translateY(100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>
<div id="map"></div>

<div class="gear-button" onclick="openFilterModal()" title="Alert Filters & Colors">
    ⚙️
</div>

<div class="modal-overlay" id="alertModal">
    <div class="modal-container">
        <div class="modal-header">
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-content" id="modalContent"></div>
    </div>
</div>

<div class="modal-overlay" id="filterModal">
    <div class="modal-container">
        <div class="modal-header">
            <h2 class="modal-title">Alert Filters & Colors</h2>
            <button class="modal-close" onclick="closeFilterModal()">×</button>
        </div>
        <div class="modal-content" id="filterContent"></div>
    </div>
</div>

<script>
// ========================
// CONFIGURE YOUR SHAPEFILES AND THEIR STYLES / ORDER HERE
// ========================
const shapefileLayers = [
    { 
        id: "counties", 
        name: "US Counties", 
        filename: "cb_2018_us_county_20m (2).zip", 
        loadOnStart: false,
        style: {
            fill: false,
            weight: 1,
            opacity: 0.6,
            color: "#888888"
        }
    },
    { 
        id: "states", 
        name: "US State Boundaries", 
        filename: "cb_2018_us_state_20m.zip", 
        loadOnStart: true,
        style: {
            fill: false,
            weight: 3,
            opacity: 1,
            color: "#000000"
        }
    }
];

const baseUrl = "";

// States array
const states = [
    { code: "AL", name: "Alabama", center: [32.8067, -86.7911] },
    { code: "AK", name: "Alaska", center: [63.5888, -154.4931] },
    { code: "AZ", name: "Arizona", center: [34.0489, -111.0937] },
    { code: "AR", name: "Arkansas", center: [34.7999, -92.1999] },
    { code: "CA", name: "California", center: [36.7783, -119.4179] },
    { code: "CO", name: "Colorado", center: [39.5501, -105.7821] },
    { code: "CT", name: "Connecticut", center: [41.6032, -73.0877] },
    { code: "DE", name: "Delaware", center: [38.9108, -75.5277] },
    { code: "DC", name: "District of Columbia", center: [38.9072, -77.0369] },
    { code: "FL", name: "Florida", center: [27.7663, -81.6868] },
    { code: "GA", name: "Georgia", center: [32.1656, -83.4412] },
    { code: "HI", name: "Hawaii", center: [19.7418, -155.8444] },
    { code: "ID", name: "Idaho", center: [44.0682, -114.7420] },
    { code: "IL", name: "Illinois", center: [40.6331, -89.3985] },
    { code: "IN", name: "Indiana", center: [40.2672, -86.1349] },
    { code: "IA", name: "Iowa", center: [41.9646, -93.3891] },
    { code: "KS", name: "Kansas", center: [39.0119, -98.4842] },
    { code: "KY", name: "Kentucky", center: [37.8393, -84.2700] },
    { code: "LA", name: "Louisiana", center: [30.9843, -91.9623] },
    { code: "ME", name: "Maine", center: [45.2538, -69.4455] },
    { code: "MD", name: "Maryland", center: [39.0458, -76.6413] },
    { code: "MA", name: "Massachusetts", center: [42.4072, -71.3824] },
    { code: "MI", name: "Michigan", center: [44.3148, -85.6024] },
    { code: "MN", name: "Minnesota", center: [46.7296, -94.6859] },
    { code: "MS", name: "Mississippi", center: [32.3547, -89.3985] },
    { code: "MO", name: "Missouri", center: [37.9643, -91.8318] },
    { code: "MT", name: "Montana", center: [46.8797, -110.3626] },
    { code: "NE", name: "Nebraska", center: [41.4925, -99.9018] },
    { code: "NV", name: "Nevada", center: [38.8026, -116.4194] },
    { code: "NH", name: "New Hampshire", center: [43.1939, -71.5724] },
    { code: "NJ", name: "New Jersey", center: [40.0583, -74.4057] },
    { code: "NM", name: "New Mexico", center: [34.5199, -105.8701] },
    { code: "NY", name: "New York", center: [43.2994, -74.2179] },
    { code: "NC", name: "North Carolina", center: [35.6301, -79.8064] },
    { code: "ND", name: "North Dakota", center: [47.5515, -101.0020] },
    { code: "OH", name: "Ohio", center: [40.4173, -82.9071] },
    { code: "OK", name: "Oklahoma", center: [35.4676, -97.5164] },
    { code: "OR", name: "Oregon", center: [43.8041, -120.5542] },
    { code: "PA", name: "Pennsylvania", center: [41.2033, -77.1945] },
    { code: "RI", name: "Rhode Island", center: [41.5801, -71.4774] },
    { code: "SC", name: "South Carolina", center: [33.8361, -81.1637] },
    { code: "SD", name: "South Dakota", center: [43.9695, -99.9018] },
    { code: "TN", name: "Tennessee", center: [35.5175, -86.5804] },
    { code: "TX", name: "Texas", center: [31.9686, -99.9018] },
    { code: "UT", name: "Utah", center: [39.3210, -111.0937] },
    { code: "VT", name: "Vermont", center: [44.5588, -72.5778] },
    { code: "VA", name: "Virginia", center: [37.4316, -78.6569] },
    { code: "WA", name: "Washington", center: [47.7511, -120.7401] },
    { code: "WV", name: "West Virginia", center: [38.5976, -80.4549] },
    { code: "WI", name: "Wisconsin", center: [43.7844, -88.7879] },
    { code: "WY", name: "Wyoming", center: [43.0759, -107.2903] }
];

const urlParams = new URLSearchParams(window.location.search);
const stateParam = urlParams.get('state') ? urlParams.get('state').toUpperCase() : null;

let initialCenter = [39.8283, -98.5795];
let initialZoom = 5;

if (stateParam) {
    const state = states.find(s => s.code === stateParam);
    if (state) {
        initialCenter = state.center;
        initialZoom = 7;
    }
}

const map = L.map('map').setView(initialCenter, initialZoom);

L.tileLayer('https://services.arcgisonline.com/arcgis/rest/services/Canvas/World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 16,
    attribution: '&copy; Esri, HERE, Garmin, USGS, NGA, EPA, USDA, NPS | ' +
                 'Storm reports &copy; NOAA/NWS Storm Prediction Center'
}).addTo(map);

// Panes
shapefileLayers.forEach((layer, index) => {
    const paneName = `overlayPane_${layer.id}`;
    map.createPane(paneName);
    map.getPane(paneName).style.zIndex = 350 + index;
});

map.createPane('zonePane');
map.getPane('zonePane').style.zIndex = 400;

map.createPane('alertPane');
map.getPane('alertPane').style.zIndex = 600;

map.createPane('lsrPane');
map.getPane('lsrPane').style.zIndex = 650;

let overlayLayers = {};
let layerVisibility = {};

try {
    const saved = localStorage.getItem('shapefileLayerVisibility');
    if (saved) layerVisibility = JSON.parse(saved);
} catch(e) {}

shapefileLayers.forEach(config => {
    const shouldLoad = config.loadOnStart || (layerVisibility[config.id] === true);
    layerVisibility[config.id] = shouldLoad;

    if (shouldLoad) {
        loadShapefile(config);
    }
});

function loadShapefile(config) {
    if (overlayLayers[config.id]) return;

    const url = baseUrl + config.filename;
    const paneName = `overlayPane_${config.id}`;

    fetch(url)
        .then(response => {
            if (!response.ok) throw new Error(`Failed to fetch ${config.filename}`);
            return response.arrayBuffer();
        })
        .then(arrayBuffer => shp(arrayBuffer))
        .then(geojson => {
            const layer = L.geoJSON(geojson, {
                style: config.style,
                pane: paneName
            }).addTo(map);

            overlayLayers[config.id] = layer;
            console.log(`${config.name} loaded`);
        })
        .catch(err => {
            console.error(`Error loading ${config.filename}:`, err);
            layerVisibility[config.id] = false;
            localStorage.setItem('shapefileLayerVisibility', JSON.stringify(layerVisibility));
        });
}

function toggleOverlayLayer(id, enabled) {
    layerVisibility[id] = enabled;
    localStorage.setItem('shapefileLayerVisibility', JSON.stringify(layerVisibility));

    const config = shapefileLayers.find(l => l.id === id);

    if (enabled) {
        if (overlayLayers[id]) {
            map.addLayer(overlayLayers[id]);
        } else {
            loadShapefile(config);
        }
    } else {
        if (overlayLayers[id]) {
            map.removeLayer(overlayLayers[id]);
        }
    }
}

// === Local Storm Reports (LSR) Layer - Color-coded smaller DivIcon markers ===
const lsrColors = {
    'AVALANCHE': '#F0F8FF',
    'BLIZZARD': '#87CEEB',
    'BLOWING DUST': '#CD853F',
    'COASTAL FLOOD': '#20B2AA',
    'DEBRIS FLOW': '#A0522D',
    'DENSE FOG': '#A9A9A9',
    'DOWNBURST': '#696969',
    'DUST STORM': '#B8860B',
    'EXCESSIVE HEAT': '#FF4500',
    'EXTREME COLD': '#0000FF',
    'EXTREME HEAT': '#FF0000',
    'EXTR WIND CHILL': '#696969',
    'FLASH FLOOD': '#228B22',
    'FLOOD': '#32CD32',
    'FOG': '#C0C0C0',
    'FREEZE': '#696969',
    'FREEZING RAIN': '#8A2BE2',
    'FUNNEL CLOUD': '#FF4500',
    'HAIL': '#00FF00',
    'HEAVY RAIN': '#006400',
    'HEAVY SLEET': '#696969',
    'HEAVY SNOW': '#00FFFF',
    'HIGH ASTR TIDES': '#696969',
    'HIGH SURF': '#00CED1',
    'HIGH SUST WINDS': '#696969',
    'ICE JAM': '#696969',
    'ICE STORM': '#9932CC',
    'LANDSLIDE': '#8B4513',
    'LANDSPOUT': '#696969',
    'LIGHTNING': '#FF8C00',
    'LOW ASTR TIDES': '#696969',
    'MARINE TSTM WIND': '#696969',
    'MISC MRN/SRF HZD': '#696969',
    'NON-TSTM WND DMG': '#DAA520',
    'NON-TSTM WND GST': '#D2691E',
    'RAIN': '#696969',
    'RIP CURRENTS': '#40E0D0',
    'SEICHE': '#696969',
    'SLEET': '#9370DB',
    'SNOW': '#ADD8E6',
    'SNOW/ICE DMG': '#696969',
    'SNOW SQUALL': '#4682B4',
    'STORM SURGE': '#008080',
    'TORNADO': '#FF0000',
    'TROPICAL CYCLONE': '#696969',
    'TROPICAL STORM': '#696969',
    'TSTM WND DMG': '#FF8C00',
    'TSTM WND GST': '#FF8C00',
    'WATER SPOUT': '#FF1493',
    'WATERSPOUT': '#FF1493',
    'WILDFIRE': '#8B0000',
    'WIND CHILL': '#4169E1',
    'default': '#696969'
};

let lsrLayerGroup = L.layerGroup();
const LSR_VISIBILITY_KEY = 'lsrLayerVisibility';
let lsrVisible = false;

try {
    const saved = localStorage.getItem(LSR_VISIBILITY_KEY);
    lsrVisible = saved ? JSON.parse(saved) : false;
} catch(e) {}

function toggleLSRLayer(enabled) {
    lsrVisible = enabled;
    localStorage.setItem(LSR_VISIBILITY_KEY, JSON.stringify(enabled));

    if (enabled) {
        if (lsrLayerGroup.getLayers().length === 0) {
            loadLSRLayer();
        }
        map.addLayer(lsrLayerGroup);
    } else {
        map.removeLayer(lsrLayerGroup);
    }
}

function loadLSRLayer() {
    if (lsrLayerGroup.getLayers().length > 0) return;

    fetch('https://mesonet.agron.iastate.edu/geojson/lsr.geojson?hours=12')
        .then(r => r.json())
        .then(data => {
            data.features.forEach(feature => {
                const p = feature.properties;
                const typetext = (p.typetext || 'UNKNOWN').trim();
                const typeUpper = typetext.toUpperCase();
                const color = lsrColors[typeUpper] || lsrColors['default'];

                const magnitude = p.magnitude ? `${p.magnitude} ${p.unit || ''}`.trim() : '';
                const remark = p.remark ? p.remark.trim() : 'No remarks';

                const icon = L.divIcon({
                    className: 'lsr-marker-div',
                    html: `<div style="background: ${color};"></div>`,
                    iconSize: [24, 24],
                    iconAnchor: [12, 12]
                });

                const marker = L.marker([p.lat, p.lon], {
                    icon: icon,
                    pane: 'lsrPane'
                });

                marker.on('click', function(e) {
                    L.DomEvent.stopPropagation(e);
                    const fakeAlerts = [{
                        properties: {
                            event: typetext,
                            headline: magnitude ? `${magnitude} reported` : typetext,
                            description: remark || 'No additional details',
                            sent: p.valid,
                            expires: null
                        }
                    }];
                    showAlertsModal('Local Storm Report', fakeAlerts);
                });

                lsrLayerGroup.addLayer(marker);
            });

            if (lsrVisible) {
                map.addLayer(lsrLayerGroup);
            }
        })
        .catch(err => {
            console.error('Failed to load LSR data:', err);
            lsrVisible = false;
            localStorage.setItem(LSR_VISIBILITY_KEY, JSON.stringify(false));
        });
}

let regularZonesLayer = null;
let alertsLayer = L.layerGroup().addTo(map);

let currentAlerts = [];
let allRawAlerts = [];
let alertedZones = {};
let previousAlertedZones = {};
let zoneAlertsLookup = {};
let regularZoneData = null;

let lastETag = null;
let isFirstLoad = true;
let previousPolygonAlertIds = new Set();

const FILTER_VISIBILITY_KEY = 'nwsAlertVisibility';
const FILTER_COLORS_KEY = 'nwsAlertCustomColors';

let userVisibleAlertTypes = {};
let customColors = {};

try {
    const visSaved = localStorage.getItem(FILTER_VISIBILITY_KEY);
    if (visSaved) userVisibleAlertTypes = JSON.parse(visSaved);

    const colSaved = localStorage.getItem(FILTER_COLORS_KEY);
    if (colSaved) customColors = JSON.parse(colSaved);
} catch(e) {}

let currentVisibleAlertTypes = {};

function applyUrlOverride() {
    const params = new URLSearchParams(window.location.search);
    const alertsParam = params.get('alerts');

    if (alertsParam) {
        currentVisibleAlertTypes = {};
        alertsParam.split(',').forEach(type => {
            const trimmed = type.trim();
            if (trimmed && alertColors.hasOwnProperty(trimmed)) {
                currentVisibleAlertTypes[trimmed] = true;
            }
        });
    } else {
        currentVisibleAlertTypes = {};
        Object.keys(alertColors).forEach(type => {
            currentVisibleAlertTypes[type] = userVisibleAlertTypes.hasOwnProperty(type) ? userVisibleAlertTypes[type] : true;
        });
    }
}

function isAlertTypeVisible(event) {
    return currentVisibleAlertTypes[event] === true;
}

function getAlertColor(event) {
    return customColors[event] || alertColors[event] || defaultColor;
}

function saveUserVisibility() {
    localStorage.setItem(FILTER_VISIBILITY_KEY, JSON.stringify(userVisibleAlertTypes));
}

function saveColors() {
    localStorage.setItem(FILTER_COLORS_KEY, JSON.stringify(customColors));
}

function resetAllColors() {
    if (confirm("Reset all custom colors to defaults?")) {
        customColors = {};
        saveColors();
        reprocessAlerts();
        openFilterModal();
    }
}

function openFilterModal() {
    const hasUrlOverride = new URLSearchParams(window.location.search).has('alerts');
    const visibleTypes = hasUrlOverride ? Object.keys(currentVisibleAlertTypes) : Object.keys(alertColors);

    let html = '<button class="reset-colors-btn" onclick="resetAllColors()">Reset All Colors to Default</button>';

    if (hasUrlOverride) {
        html += `
            <div class="url-override-notice">
                <strong>Custom Alert Types</strong><br>
                This map is customized to only show the alert types below.
            </div>`;
    }

    html += `
        <div class="layer-section">
            <div class="layer-section-header">Additional Layers</div>
            <div class="layer-list">
                <div class="layer-item">
                    <span class="layer-label">Local Storm Reports (last 12 hours)</span>
                    <label class="layer-toggle">
                        <input type="checkbox" ${lsrVisible ? 'checked' : ''} onchange="toggleLSRLayer(this.checked)">
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
        </div>`;

    html += `
        <div class="layer-section">
            <div class="layer-section-header">Map Layers (Top → Bottom)</div>
            <div class="layer-list">`;

    shapefileLayers.forEach(layer => {
        const checked = layerVisibility[layer.id] !== false ? 'checked' : '';
        html += `
            <div class="layer-item">
                <span class="layer-label">${layer.name}</span>
                <label class="layer-toggle">
                    <input type="checkbox" ${checked} onchange="toggleOverlayLayer('${layer.id}', this.checked)">
                    <span class="slider"></span>
                </label>
            </div>`;
    });

    html += `</div></div>`;

    const alertGroups = { "Warnings": [], "Watches": [], "Advisories": [], "Statements": [], "Other": [] };

    visibleTypes.sort().forEach(type => {
        if (type.includes("Warning") && !type.includes("Watch")) alertGroups["Warnings"].push(type);
        else if (type.includes("Watch")) alertGroups["Watches"].push(type);
        else if (type.includes("Advisory")) alertGroups["Advisories"].push(type);
        else if (type.includes("Statement") || type.includes("Outlook") || type.includes("Forecast")) alertGroups["Statements"].push(type);
        else alertGroups["Other"].push(type);
    });

    Object.entries(alertGroups).forEach(([groupName, types]) => {
        if (types.length === 0) return;

        const groupId = groupName.toLowerCase().replace(/ /g, '');
        html += `
            <div class="filter-section">
                <div class="section-header" onclick="toggleSection('${groupId}')">
                    <div class="section-title">${groupName} (${types.length})</div>
                    <div class="section-controls">
                        <button onclick="event.stopPropagation(); selectAll('${groupId}', true)">Select All</button>
                        <button onclick="event.stopPropagation(); selectAll('${groupId}', false)">Deselect All</button>
                    </div>
                </div>
                <div class="section-content" id="${groupId}-content">
        `;

        types.forEach(type => {
            const userPref = userVisibleAlertTypes.hasOwnProperty(type) ? userVisibleAlertTypes[type] : true;
            const checked = userPref ? 'checked' : '';
            const currentColor = getAlertColor(type);

            html += `
                <div class="filter-item">
                    <input type="checkbox" ${checked} onchange="toggleUserVisibility('${type.replace(/'/g, "\\'")}', this.checked)">
                    <input type="color" class="filter-color-picker" value="${currentColor}" onchange="setCustomColor('${type.replace(/'/g, "\\'")}', this.value)">
                    <div class="filter-current-color" style="background-color:${currentColor};"></div>
                    <span class="filter-label">${type}</span>
                </div>
            `;
        });

        html += `</div></div>`;
    });

    document.getElementById('filterContent').innerHTML = html;
    document.getElementById('filterModal').classList.add('active');
    document.querySelector('#filterModal .modal-content').scrollTop = 0;
}

function toggleSection(groupId) {
    const content = document.getElementById(groupId + '-content');
    content.style.display = content.style.display === 'none' ? 'grid' : 'none';
}

function selectAll(groupId, enable) {
    const content = document.getElementById(groupId + '-content');
    const items = content.querySelectorAll('.filter-item');
    items.forEach(item => {
        const label = item.querySelector('.filter-label').textContent.trim();
        const checkbox = item.querySelector('input[type="checkbox"]');
        checkbox.checked = enable;
        userVisibleAlertTypes[label] = enable;
    });
    saveUserVisibility();

    const params = new URLSearchParams(window.location.search);
    const hasOverride = params.has('alerts');

    if (hasOverride) {
        items.forEach(item => {
            const label = item.querySelector('.filter-label').textContent.trim();
            if (currentVisibleAlertTypes.hasOwnProperty(label)) {
                currentVisibleAlertTypes[label] = enable;
            }
        });
        reprocessAlerts();
    } else {
        Object.keys(alertColors).forEach(type => {
            currentVisibleAlertTypes[type] = userVisibleAlertTypes.hasOwnProperty(type) ? userVisibleAlertTypes[type] : true;
        });
        reprocessAlerts();
    }
}

function toggleUserVisibility(type, enabled) {
    userVisibleAlertTypes[type] = enabled;
    saveUserVisibility();

    const params = new URLSearchParams(window.location.search);
    const hasOverride = params.has('alerts');

    if (hasOverride) {
        if (currentVisibleAlertTypes.hasOwnProperty(type)) {
            currentVisibleAlertTypes[type] = enabled;
            reprocessAlerts();
        }
    } else {
        currentVisibleAlertTypes[type] = enabled;
        reprocessAlerts();
    }
}

function setCustomColor(type, color) {
    customColors[type] = color;
    saveColors();
    reprocessAlerts();
}

function closeFilterModal() {
    document.getElementById('filterModal').classList.remove('active');
}

function closeModal() {
    document.getElementById('alertModal').classList.remove('active');
}

document.getElementById('alertModal').addEventListener('click', e => {
    if (e.target === e.currentTarget) closeModal();
});
document.getElementById('filterModal').addEventListener('click', e => {
    if (e.target === e.currentTarget) closeFilterModal();
});

const defaultColor = "#FFFF00";

const alertColors = {
    "Tornado Watch": "#FFFF00",
    "Severe Thunderstorm Watch": "#FFA500",
    "Tsunami Warning": "#FD6347",
    "Tornado Warning": "#FF0000",
    "Extreme Wind Warning": "#FF8C00",
    "Severe Thunderstorm Warning": "#FFA500",
    "Flash Flood Warning": "#8B0000",
    "Flash Flood Statement": "#8B0000",
    "Severe Weather Statement": "#00FFFF",
    "Shelter In Place Warning": "#FA8072",
    "Evacuation Immediate": "#7FFF00",
    "Civil Danger Warning": "#FFB6C1",
    "Nuclear Power Plant Warning": "#4B0082",
    "Radiological Hazard Warning": "#4B0082",
    "Hazardous Materials Warning": "#4B0082",
    "Fire Warning": "#A0522D",
    "Civil Emergency Message": "#FFB6C1",
    "Law Enforcement Warning": "#C0C0C0",
    "Storm Surge Warning": "#B524F7",
    "Hurricane Force Wind Warning": "#CD5C5C",
    "Hurricane Warning": "#DC143C",
    "Typhoon Warning": "#DC143C",
    "Special Marine Warning": "#FFA500",
    "Blizzard Warning": "#FF4500",
    "Snow Squall Warning": "#C71585",
    "Ice Storm Warning": "#8B008B",
    "Heavy Freezing Spray Warning": "#00BFFF",
    "Winter Storm Warning": "#FF69B4",
    "Lake Effect Snow Warning": "#008B8B",
    "Dust Storm Warning": "#FFE4C4",
    "Blowing Dust Warning": "#FFE4C4",
    "High Wind Warning": "#DAA520",
    "Tropical Storm Warning": "#B22222",
    "Storm Warning": "#9400D3",
    "Tsunami Advisory": "#D2691E",
    "Tsunami Watch": "#FF00FF",
    "Avalanche Warning": "#1E90FF",
    "Earthquake Warning": "#8B4513",
    "Volcano Warning": "#2F4F4F",
    "Ashfall Warning": "#A9A9A9",
    "Flood Warning": "#00FF00",
    "Coastal Flood Warning": "#228B22",
    "Lakeshore Flood Warning": "#228B22",
    "Ashfall Advisory": "#696969",
    "High Surf Warning": "#228B22",
    "Extreme Heat Warning": "#C71585",
    "Gale Warning": "#DDA0DD",
    "Flood Statement": "#00FF00",
    "Extreme Cold Warning": "#0000FF",
    "Freeze Warning": "#483D8B",
    "Red Flag Warning": "#FF1493",
    "Storm Surge Watch": "#DB7FF7",
    "Hurricane Watch": "#FF00FF",
    "Hurricane Force Wind Watch": "#9932CC",
    "Typhoon Watch": "#FF00FF",
    "Tropical Storm Watch": "#F08080",
    "Storm Watch": "#FFE4B5",
    "Tropical Cyclone Local Statement": "#FFE4B5",
    "Winter Weather Advisory": "#7B68EE",
    "Avalanche Advisory": "#CD853F",
    "Cold Weather Advisory": "#AFEEEE",
    "Heat Advisory": "#FF7F50",
    "Flood Advisory": "#00FF7F",
    "Coastal Flood Advisory": "#7CFC00",
    "Lakeshore Flood Advisory": "#7CFC00",
    "High Surf Advisory": "#BA55D3",
    "Dense Fog Advisory": "#708090",
    "Dense Smoke Advisory": "#F0E68C",
    "Small Craft Advisory": "#D8BFD8",
    "Brisk Wind Advisory": "#D8BFD8",
    "Hazardous Seas Warning": "#D8BFD8",
    "Dust Advisory": "#BDB76B",
    "Blowing Dust Advisory": "#BDB76B",
    "Lake Wind Advisory": "#D2B48C",
    "Wind Advisory": "#D2B48C",
    "Frost Advisory": "#6495ED",
    "Freezing Fog Advisory": "#008080",
    "Freezing Spray Advisory": "#00BFFF",
    "Low Water Advisory": "#A52A2A",
    "Local Area Emergency": "#C0C0C0",
    "Winter Storm Watch": "#4682B4",
    "Rip Current Statement": "#40E0D0",
    "Beach Hazards Statement": "#40E0D0",
    "Gale Watch": "#FFC0CB",
    "Avalanche Watch": "#F4A460",
    "Hazardous Seas Watch": "#483D8B",
    "Heavy Freezing Spray Watch": "#BC8F8F",
    "Flood Watch": "#2E8B57",
    "Coastal Flood Watch": "#66CDAA",
    "Lakeshore Flood Watch": "#66CDAA",
    "High Wind Watch": "#B8860B",
    "Extreme Heat Watch": "#800000",
    "Extreme Cold Watch": "#5F9EA0",
    "Freeze Watch": "#00FFFF",
    "Fire Weather Watch": "#FFDEAD",
    "Extreme Fire Danger": "#E9967A",
    "911 Telephone Outage": "#C0C0C0",
    "Coastal Flood Statement": "#6B8E23",
    "Lakeshore Flood Statement": "#6B8E23",
    "Special Weather Statement": "#FFE4B5",
    "Marine Weather Statement": "#FFDAB9",
    "Air Quality Alert": "#808080",
    "Air Stagnation Advisory": "#808080",
    "Hazardous Weather Outlook": "#EEE8AA",
    "Hydrologic Outlook": "#90EE90",
    "Short Term Forecast": "#98FB98",
    "Administrative Message": "#C0C0C0",
    "Test": "#F0FFFF",
    "Child Abduction Emergency": "#FFFFFF",
    "Blue Alert": "#FFFFFF"
};

const alertPriorities = {
    "Tornado Warning": 100,
    "Tsunami Warning": 99,
    "Extreme Wind Warning": 98,
    "Flash Flood Warning": 97,
    "Tornado Watch": 90,
    "Severe Thunderstorm Warning": 85,
    "Blizzard Warning": 84,
    "Winter Storm Warning": 83,
    "Hurricane Warning": 82,
    "Typhoon Warning": 82,
    "Storm Surge Warning": 81,
    "Flood Warning": 80,
    "Red Flag Warning": 79,
    "Winter Storm Watch": 78,
    "Extreme Cold Warning": 77,
    "Winter Weather Advisory": 76,
    "Flood Watch": 76,
    "Freeze Warning": 75,
    "Lake Effect Snow Warning": 74,
};

function getAlertPriority(event) { return alertPriorities[event] || 0; }

applyUrlOverride();

function pointInAlert(latlng, alert) {
    if (!alert.geometry) return false;
    const point = turf.point([latlng.lng, latlng.lat]);
    return turf.booleanPointInPolygon(point, alert.geometry);
}

function getZonesAtPoint(latlng) {
    const seenZoneIds = new Set();
    const matchingZones = [];
    const point = turf.point([latlng.lng, latlng.lat]);

    if (!regularZoneData || !regularZoneData.features) return matchingZones;

    regularZoneData.features.forEach(feature => {
        if (feature.geometry && feature.properties && feature.properties.STATE_ZONE) {
            try {
                if (turf.booleanPointInPolygon(point, feature.geometry)) {
                    const zoneId = feature.properties.STATE_ZONE;
                    if (!seenZoneIds.has(zoneId)) {
                        seenZoneIds.add(zoneId);
                        matchingZones.push({
                            zoneId: zoneId,
                            zoneName: feature.properties.NAME || 'Unknown Zone'
                        });
                    }
                }
            } catch (e) {}
        }
    });
    return matchingZones;
}

function getAlertsAtPoint(latlng, zoneIds = []) {
    const alertSet = new Map();

    currentAlerts.forEach(alert => {
        if (!isAlertTypeVisible(alert.properties.event)) return;
        if (alert.geometry && pointInAlert(latlng, alert)) {
            const id = alert.id || JSON.stringify(alert.properties);
            alertSet.set(id, alert);
        }
    });

    zoneIds.forEach(zoneId => {
        if (zoneAlertsLookup[zoneId]) {
            zoneAlertsLookup[zoneId].forEach(alert => {
                if (isAlertTypeVisible(alert.properties.event)) {
                    const id = alert.id || JSON.stringify(alert.properties);
                    alertSet.set(id, alert);
                }
            });
        }
    });

    return Array.from(alertSet.values());
}

function showAlertsModal(title, alerts) {
    if (alerts.length === 0) {
        document.getElementById('modalContent').innerHTML = '<div class="no-alerts">No active alerts for this location.</div>';
    } else {
        let content = `<div class="alert-count">${alerts.length} Active Alert${alerts.length > 1 ? 's' : ''}</div>`;

        const sortedAlerts = alerts.sort((a, b) => 
            getAlertPriority(b.properties.event) - getAlertPriority(a.properties.event)
        );

        sortedAlerts.forEach(alert => {
            const p = alert.properties;
            const isLSR = p.event && !alertColors.hasOwnProperty(p.event);
            const itemClass = isLSR ? 'alert-item lsr' : 'alert-item';
            const color = getAlertColor(p.event) || '#888888';

            const description = p.description || 'No additional details';

            content += `
                <div class="${itemClass}" style="border-left: 5px solid ${color};">
                    <div class="alert-headline">${p.headline || p.event}</div>
                    <div class="alert-event">${p.event}</div>
                    <div class="alert-description">${description}</div>
                </div>
            `;
        });

        document.getElementById('modalContent').innerHTML = content;
    }

    document.getElementById('alertModal').classList.add('active');
    document.querySelector('#alertModal .modal-content').scrollTop = 0;
}

function flashZone(zoneId, color) {
    if (!regularZonesLayer) return;

    const flashStyle = {
        fill: true,
        fillColor: color,
        fillOpacity: 0.9,
        weight: 3,
        color: '#000',
        opacity: 1
    };

    regularZonesLayer.setFeatureStyle(zoneId, flashStyle);

    setTimeout(() => {
        const normalStyle = alertedZones[zoneId] ? {
            fill: true,
            fillColor: color,
            fillOpacity: 0.55,
            weight: 2.5,
            color: '#000',
            opacity: 1
        } : {
            fill: false,
            fillOpacity: 0,
            weight: 0,
            color: 'transparent',
            opacity: 0
        };
        regularZonesLayer.setFeatureStyle(zoneId, normalStyle);
    }, 10000);
}

function flashPolygon(layer, color) {
    layer.setStyle({
        fillOpacity: 0.9,
        weight: 5
    });

    setTimeout(() => {
        layer.setStyle({
            fillOpacity: 0.6,
            weight: 3
        });
    }, 10000);
}

function reprocessAlerts() {
    const newAlertedZones = {};
    zoneAlertsLookup = {};
    const currentPolygonIds = new Set();

    const newlyAlertedZones = new Set();
    if (!isFirstLoad) {
        Object.keys(previousAlertedZones).forEach(zoneId => {
            if (alertedZones[zoneId] && (!previousAlertedZones[zoneId] || previousAlertedZones[zoneId].color !== alertedZones[zoneId].color)) {
                newlyAlertedZones.add(zoneId);
            }
        });
    }

    allRawAlerts.forEach(feature => {
        const event = feature.properties.event;
        if (!isAlertTypeVisible(event)) return;

        const color = getAlertColor(event);
        const priority = getAlertPriority(event);

        const hasGeometry = feature.geometry && (feature.geometry.type === 'Polygon' || feature.geometry.type === 'MultiPolygon');

        if (!hasGeometry) {
            (feature.properties.geocode?.UGC || []).forEach(ugc => {
                if (ugc.length === 6) {
                    const zoneId = ugc;

                    if (!newAlertedZones[zoneId] || priority > (newAlertedZones[zoneId].priority || 0)) {
                        newAlertedZones[zoneId] = { color, priority };
                    }

                    if (!zoneAlertsLookup[zoneId]) zoneAlertsLookup[zoneId] = [];
                    const alertId = feature.id || JSON.stringify(feature.properties);
                    if (!zoneAlertsLookup[zoneId].some(a => (a.id || JSON.stringify(a.properties)) === alertId)) {
                        zoneAlertsLookup[zoneId].push(feature);
                    }
                }
            });
        } else {
            const alertId = feature.id || JSON.stringify(feature.properties);
            currentPolygonIds.add(alertId);
        }
    });

    alertedZones = newAlertedZones;

    if (!isFirstLoad) {
        newlyAlertedZones.forEach(zoneId => {
            const color = alertedZones[zoneId].color;
            flashZone(zoneId, color);
        });
    }

    alertsLayer.clearLayers();

    const visibleFeatures = allRawAlerts.filter(f => isAlertTypeVisible(f.properties.event));
    const sorted = visibleFeatures.slice().sort((a, b) => 
        getAlertPriority(a.properties.event) - getAlertPriority(b.properties.event)
    );

    sorted.forEach(feature => {
        const alertId = feature.id || JSON.stringify(feature.properties);
        const layer = L.geoJSON(feature, {
            style: {
                fillColor: getAlertColor(feature.properties.event),
                color: '#000',
                weight: 3,
                opacity: 1,
                fillOpacity: 0.6
            },
            pane: 'alertPane'
        });

        if (!isFirstLoad && !previousPolygonAlertIds.has(alertId)) {
            flashPolygon(layer, getAlertColor(feature.properties.event));
        }

        layer.on('click', function(e) {
            L.DomEvent.stopPropagation(e);
            handleMapClick(e);
        });

        alertsLayer.addLayer(layer);
    });

    previousPolygonAlertIds = currentPolygonIds;

    reapplyZoneStyles();

    isFirstLoad = false;
}

function updateAlerts() {
    const headers = {
        'User-Agent': 'StormsAlertApp (contact@stormsalert.com)',
        'Accept': 'application/geo+json'
    };

    if (lastETag) {
        headers['If-None-Match'] = lastETag;
    }

    fetch('https://api.weather.gov/alerts/active?region_type=land', { headers })
        .then(response => {
            if (response.status === 304) {
                return;
            }

            if (!response.ok) throw new Error('NWS API error');

            lastETag = response.headers.get('ETag');

            return response.json();
        })
        .then(data => {
            if (!data) return;

            const features = data.features || [];
            allRawAlerts = features;
            currentAlerts = features;

            reprocessAlerts();
        })
        .catch(err => console.error('Error fetching alerts:', err));
}

function reapplyZoneStyles() {
    if (!regularZonesLayer) return;

    const allZoneIds = new Set([...Object.keys(alertedZones), ...Object.keys(previousAlertedZones)]);

    allZoneIds.forEach(zoneId => {
        const current = alertedZones[zoneId];
        const style = current ? {
            fill: true,
            fillColor: current.color,
            fillOpacity: 0.55,
            weight: 2.5,
            color: '#000',
            opacity: 1
        } : {
            fill: false,
            fillOpacity: 0,
            weight: 0,
            color: 'transparent',
            opacity: 0
        };

        regularZonesLayer.setFeatureStyle(zoneId, style);
    });

    previousAlertedZones = { ...alertedZones };
}

function handleMapClick(e) {
    const latlng = e.latlng;
    const zones = getZonesAtPoint(latlng);
    const zoneIds = zones.map(z => z.zoneId);
    const alerts = getAlertsAtPoint(latlng, zoneIds);

    if (alerts.length === 0) {
        return;
    }

    let title = 'Location';
    if (zones.length > 0) {
        title = zones[0].zoneName;
    }

    showAlertsModal(title, alerts);
}

map.on('click', handleMapClick);

function attachVectorClick(layer) {
    if (layer) {
        layer.on('click', e => {
            L.DomEvent.stopPropagation(e);
            handleMapClick(e);
        });
    }
}

const CACHE_NAME = 'nws-zones-cache-v44';
const ONE_MONTH_IN_SECONDS = 30 * 24 * 60 * 60;

async function getCachedJson(url) {
    if (!('caches' in window)) {
        const response = await fetch(url);
        return response.json();
    }

    const cache = await caches.open(CACHE_NAME);
    const cachedResponse = await cache.match(url);

    if (cachedResponse) {
        const dateHeader = cachedResponse.headers.get('date');
        if (dateHeader) {
            const fetchDate = new Date(dateHeader).getTime();
            const now = Date.now();
            if ((now - fetchDate) / 1000 < ONE_MONTH_IN_SECONDS) {
                return cachedResponse.json();
            }
        } else {
            return cachedResponse.json();
        }
    }

    const networkResponse = await fetch(url);
    if (networkResponse.ok) {
        const clone = networkResponse.clone();
        const headers = new Headers(clone.headers);
        headers.set('date', new Date().toUTCString());
        headers.set('cache-control', 'max-age=' + ONE_MONTH_IN_SECONDS);

        const cachedVersion = new Response(clone.body, {
            status: clone.status,
            statusText: clone.statusText,
            headers: headers
        });

        await cache.put(url, cachedVersion);
        return networkResponse.json();
    }
    throw new Error(`Failed to load ${url}`);
}

getCachedJson('combined_zones40_fixed.json')
    .then(data => {
        regularZoneData = data;
        regularZonesLayer = L.vectorGrid.slicer(data, {
            rendererFactory: L.canvas.tile,
            vectorTileLayerStyles: {
                sliced: () => ({
                    fill: true,
                    fillOpacity: 0,
                    weight: 0,
                    color: 'transparent',
                    opacity: 0
                })
            },
            interactive: true,
            getFeatureId: f => f.properties.STATE_ZONE,
            pane: 'zonePane'
        })
        .addTo(map);

        attachVectorClick(regularZonesLayer);
        regularZonesLayer.on('load', () => reapplyZoneStyles());
    })
    .catch(err => console.error('Failed to load combined_zones40_fixed.json:', err));

if (lsrVisible) {
    loadLSRLayer();
}

updateAlerts();
setInterval(updateAlerts, 60000);
</script>
</body>
</html>
