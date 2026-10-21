<?php
function loadConfig($filename) {
    $config = [];
    if (file_exists($filename)) {
        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $config[trim($key)] = trim($value);
            }
        }
    }
    return $config;
}

$config = loadConfig('config/variablen.txt');

$breite = floatval($config['breite'] ?? 52.9575839);
$laenge = floatval($config['laenge'] ?? 7.30046252);
$zoom = intval($config['zoom'] ?? 10);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koordinaten</title>
    <link rel="stylesheet" href="css/leaflet.css" />
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        #map {
            height: 100%;
            width: 100%;
        }
        #coordinateInput {
            position: absolute;
            bottom: 10px;
            left: 10px;
            background: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            z-index: 1000;
            width: 200px;
        }

        #map div {
            background-color: transparent;
        }
        .leaflet-control-layers, .leaflet-bar, .leaflet-popup-content-wrapper {
            background-color: white !important;
        }
    </style>
</head>
<body>

<input type="text" id="coordinateInput" placeholder="Koordinaten hier..." readonly />

<div id="map"></div>

<script src="js/leaflet.js"></script>
<script>
    var map = L.map('map').setView([<?php echo $breite; ?>, <?php echo $laenge; ?>], <?php echo $zoom; ?>);

    var osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    var esriLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 20,
        attribution: 'Tiles © Esri'
    });

    var baseMaps = {
        "OpenStreetMap": osmLayer,
        "Esri Satelliten": esriLayer
    };

    L.control.layers(baseMaps).addTo(map);

    var marker;

    function onMapClick(e) {
        var coordinateInput = document.getElementById('coordinateInput');
        coordinateInput.value = e.latlng.lat.toFixed(6) + ';' + e.latlng.lng.toFixed(6);

        if (marker) {
            marker.setLatLng(e.latlng);
        } else {
            marker = L.marker(e.latlng).addTo(map);
        }
    }

    map.on('click', onMapClick);
</script>

</body>
</html>