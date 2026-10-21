<?php
// 1. KOMPLETTE LOGIK & DATENVERARBEITUNG AM ANFANG
declare(strict_types=1);

$content ='<?xml version="1.0" encoding="UTF-8" standalone="no" ?>' . "\n";
$content .= '<gpx xmlns="http://www.topografix.com/GPX/1/1" creator="QR-Code-Rallye" version="1.1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd">' . "\n";

$filename = @file("orte/orte.txt");
$totalLocations = 0;

if ($filename !== false) {
    foreach ($filename as $line) {
        $list = explode(";", $line);
        $nr = isset($list[0]) ? intval($list[0]) : 0;
        if ($nr >= 1 && $nr <= 89) {
            $name = isset($list[1]) ? trim($list[1]) : '';
            $lat = isset($list[2]) ? trim($list[2]) : '';
            $lon = isset($list[3]) ? trim($list[3]) : '';
            
            $safe_name = htmlspecialchars($name, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            
            $content .= '<wpt lat="' . htmlspecialchars($lat, ENT_QUOTES, 'UTF-8') . '" lon="' . htmlspecialchars($lon, ENT_QUOTES, 'UTF-8') . '">' . "\n";
            $content .= "<name>" . $safe_name . "</name>\n";
            $content .= "</wpt>\n";
            $totalLocations++;
        }
    }
}
$content .= "</gpx>\n";
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Erstellung GPX-Datei</title>
</head>
<body>
<b>Erstellung GPX-Datei .gpx</b><br>
Im Textfeld werden die Wegpunkte der aktuell vorhandenen Orte (orte/orte.txt) im GPX-Format angezeigt.<br>
Bitte nach der Festlegung aller Orte (Namen und Koordinaten) den in der Textbox aufgeführten Inhalt in die Datei dateien/navigation.gpx kopieren.
<br><br>

<!-- 2. REINE HTML-AUSGABE AM ENDE -->
<form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>">
<textarea name="content" cols="150" rows="30"><?php echo htmlspecialchars($content, ENT_QUOTES, 'UTF-8'); ?></textarea>
</form>
<br>
<b>Pfad des Webspaces:</b><br>

<?php

echo htmlspecialchars($_SERVER['DOCUMENT_ROOT'], ENT_QUOTES, 'UTF-8') . "<br>";
echo htmlspecialchars($_SERVER['SCRIPT_FILENAME'], ENT_QUOTES, 'UTF-8');
?>
</body>
</html>