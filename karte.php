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

function loadOrte($filename) {
    $orte = [];
    if (file_exists($filename)) {
        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = explode(';', $line);
            if (count($parts) >= 5 && $parts[0] !== '00') {
                $orte[] = [
                    'id' => $parts[0],
                    'name' => $parts[1],
                    'lat' => floatval($parts[2]),
                    'lon' => floatval($parts[3]),
                    'zusatzinfo' => $parts[4],
                    'hasImage' => file_exists("bilder/{$parts[0]}/karte.jpg")
                ];
            }
        }
    }
    return $orte;
}

$config = loadConfig('config/variablen.txt');
$orte = loadOrte('orte/orte.txt');
$breite = floatval($config['breite'] ?? 52.9575839);
$laenge = floatval($config['laenge'] ?? 7.30046252);
$zoom1 = intval($config['zoom1'] ?? 10);
$zoom2 = intval($config['zoom2'] ?? 18);
$zoom = $zoom1;
$aktionsende = isset($config['aktionsende']) ? filter_var($config['aktionsende'], FILTER_VALIDATE_BOOLEAN) : false;

// Neue Logik für kartenhinweis
$kartenhinweis = true;
if (isset($config['kartenhinweis'])) {
    if ($config['kartenhinweis'] === 'false') {
        $kartenhinweis = false;
    } elseif ($config['kartenhinweis'] === 'true') {
        $kartenhinweis = true;
    }
}

if (isset($_GET['a']) && isset($_GET['b'])) {
    $breite = floatval($_GET['a']);
    $laenge = floatval($_GET['b']);
    $zoom = $zoom2;
}

$headerContent = file_get_contents("config/karte-header.txt");
echo $headerContent;
?>

<link rel="stylesheet" href="css/qr-code-rallye.css">
</head>

<?php
$headerContent = file_get_contents("config/karte-inhalt.txt");
echo $headerContent;
?>

<div id="map"></div>

<?php if ($kartenhinweis): ?>
<div id="consentNotice">
    <p>Wir verwenden Karten von OpenStreetMap und Esri, die möglicherweise Ihre Daten verarbeiten.</p>
    <button class="w3-btn w3-teal w3-round" id="acceptButton">Akzeptieren</button>
</div>
<?php endif; ?>

<?php
$headerContent = file_get_contents("config/karte-footer.txt");
echo $headerContent;
?>

<script src="js/leaflet.js"></script>
<script src="js/leaflet-gpx.js"></script>
<link rel="stylesheet" href="css/leaflet.fullscreen.css" />
<script src="js/leaflet.fullscreen.js"></script>
<link rel="stylesheet" href="css/L.Control.Locate.css" />
<script src="js/L.Control.Locate.min.js"></script>
<script>

function loadMap() {
    var breite = <?php echo json_encode($breite); ?>;
    var laenge = <?php echo json_encode($laenge); ?>;
    var zoom = <?php echo json_encode($zoom); ?>;
    
var map = L.map('map', {
    fullscreenControl: true, // Vollbild-Button aktivieren
    fullscreenControlOptions: {
        position: 'topright' // Position des Buttons (optional)
    }
}).setView([breite, laenge], zoom);

    
    
    var osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    var esriLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 20,
        attribution: 'Tiles © Esri'
    });

    var baseMaps = {
        "OSM": osmLayer,
        "Esri Satellite": esriLayer
    };

    L.control.layers(baseMaps).addTo(map);

    var orte = <?php echo json_encode($orte); ?>;

    orte.forEach(function(ort) {
        var popupContent = '<div class="popup-content">' +
                           '<b>' + ort.name + '</b>';

        if (ort.hasImage) {
            popupContent += '<img src="bilder/' + ort.id + '/karte.jpg" alt="' + ort.name + '" />';
        }

        popupContent += '<p>' + ort.zusatzinfo + '</p>' +
                        '<div class="actions">' +
                        '<a href="https://maps.google.com/maps?q=' + ort.lat + ',' + ort.lon + '">' +
                        '<img src="bilder/bilder/maps.png" alt="Route planen" style="width:30px; height:auto;" /></a>' +
                        '<a href="geo:' + ort.lat + ',' + ort.lon + '">' +
                        '<img src="bilder/bilder/geo.jpg" alt="Smartphone Navigation" style="width:30px; height:auto;" /></a>';
        
        if (<?php echo json_encode($aktionsende); ?>) {
            popupContent += '<form method="post" style="display:inline;" action="index.php">' +
                            '<input type="hidden" name="fstation" value="' + ort.name + '">' +
                            '<input type="hidden" name="code" value="">' +
                            '<input class="w3-btn w3-teal w3-round" type="submit" value="Besuchen">' +
                            '</form>';
        }

        popupContent += '</div></div>';

        var popup = L.popup({
            maxWidth: 300,
            closeButton: true,
            className: 'custom-popup'
        }).setContent(popupContent);

        L.marker([ort.lat, ort.lon])
            .addTo(map)
            .bindPopup(popup);
    });


new L.GPX('dateien/track.gpx', {
    async: true,
    marker_options: {
        startIconUrl: false, 
        endIconUrl: false,   
        shadowUrl: ''        
    },
    polyline_options: {
        color: 'red',       
        weight: 4,           
        opacity: 0.75 
    }
}).addTo(map);



// Standard-Locate-Button von Leaflet hinzufügen
L.control.locate({
    position: 'topright', // Position des Buttons
    flyTo: true,          // Karte auf den Standort zentrieren
    showPopup: false,     // Kein automatisches Popup anzeigen
    strings: {
        title: "Standort anzeigen" // Tooltip-Text für den Button
    }
}).addTo(map);

   
}


    <?php if ($kartenhinweis): ?>
    document.getElementById("consentNotice").style.display = "block";

    document.getElementById("acceptButton").onclick = function() {
        document.getElementById("consentNotice").style.display = "none";
        loadMap();
    };
    <?php else: ?>
    loadMap();
    <?php endif; ?>
</script>

<br>
<br><br>
</body>  
</html>
