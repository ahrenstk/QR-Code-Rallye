<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', 'Off');
ini_set('log_errors', 'On');
ini_set('error_log', 'error.log');


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


$secret = $config['secret'] ?? 'qr-code-rallye';

// DIN A4
$breite = 210;
$hoehe = 297;
$sub = 257;
$size = 200;
$type = "A4";

// DIN A5
//$breite = 148;
//$hoehe = 210;
//$sub = 190;
//$size = 200;
//$type = "A5";

// Für DIN A5 sind evtl. noch einige Anpassungen notwendig.

?>
<!DOCTYPE HTML>
<html lang="de">
   <head>
      <meta charset="utf-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <meta name="description" content="QR-Code-Rallye">
      <meta name="keywords" content="QR-Code-Rallye">
      <meta name="author" content="QR-Code-Rallye">
      <title></title>
      <script src="js/qrcode.min.js"></script>
      <link rel="stylesheet" href="css/w3.css">
      <style type="text/css">
         body {
         width: 100%;
         height: 100%;
         margin: 0;
         padding: 0;
         background-color: #FFFFFF;
         }
         * {
         box-sizing: border-box;
         -moz-box-sizing: border-box;
         }
         .page {
         width: <?php echo $breite; ?>mm;
         min-height: <?php echo $hoehe; ?>mm;
         padding: 20mm;
         margin: 10mm auto;
         border: 0px #000000 solid;  /* auf 1px setzen zur Orientierung bei Änderungen */
         background: white;
         align: center; 
         }
         .subpage {
         padding: 1cm;
         border: 0px black solid;   /* auf 1px setzen zur Orientierung bei Änderungen */
         height: <?php echo $sub; ?>mm;
         outline: 0cm #FFEAEA solid;
         }
         @page {
         size: <?php echo $type; ?>;
         margin: 0;
         }
         @media print {
         html, body {
         width: <?php echo $breite; ?>mm;
         height: <?php echo $hoehe; ?>mm;      
         }
         .page {
         margin: 0;
         width: initial;
         min-height: initial;
         box-shadow: initial;
         background: initial;
         page-break-after: always;
         align: center; 
         }
         }
      </style>
   </head>
   <body>
      <div class="book">
         <div class="page">
            <div class="subpage">
               <?php
                  $protocol = $_SERVER['PROTOCOL'] = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
                  $uri = $_SERVER['REQUEST_URI'];
                  $addr = substr($uri, 0, strripos($uri, "_"));
                  $url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $addr;
                  $gefunden = false;

                  if (isset($_GET['a'])) {
                      $a = $_GET["a"] ?? NULL;
                      $a = strtolower($a);
                  }
                  if (isset($_GET['b'])) {
                      $size = $_GET["b"];
                  }

                  if (!isset($a)) {
                      echo "Es wurde kein Übergabewert angegeben";
                  } else {
                      $a = substr($a, 0, 5);

                      $filename = file("orte/orte.txt");
                      foreach ($filename as $line) {
                          $list = explode(";", $line);
                          if (isset($list[0])) {
                              $nr = $list[0];
                          }
                          if (isset($list[1])) {
                              $name = $list[1];
                          }
                          if (($nr >= 0) && ($nr <= 89)) {
                              $station[$nr] = $name;
                          }
                      }

                      for ($i = 0; $i <= 89; $i++) {
                          $stationnr = sprintf('%02d', $i);    
                          $stationtupel = $stationnr . ";" . $secret;
                          $stationmd5 = md5($stationtupel);
                          $stationsecret = substr($stationmd5, 0, 5);
                          $stationsname = $station[$stationnr] ?? NULL;

                          if ($a == $stationsecret) {
                              $gefunden = true;
                              $stationsname = str_replace(' - ', '<br>', $stationsname);  // Zeilenumbruch bei " - "
                              
                              echo "<center>";
                              echo '<font size="8">';
                              echo '<b>';
                              echo $stationsname . '</b><br><br>';  
                              echo '</font>';
                              echo '<div id="qrcode"></div>'; // QR-Code wird hier eingefügt
                              echo '<br>';
                              echo '<font size="3">';
                              echo '<b>' . $url . '</b><br>';
                              echo '</font>';
                              echo '<br><br><br>';
                              echo '<br><br><br>';
                              echo '<br><br><br>';
                              echo '<br><br><br>';
                              echo '<br><br><br>';
                              echo '</center>';
                          }
                      }
                      if (!$gefunden) {
                          echo "Der Ort konnte nicht gefunden werden. Sehr wahrscheinlich ist in den Dateien <b>index.php</b> und <b>qr.php</b> die Variable <b>\$secret</b> nicht identisch.";
                      }
                  }
               ?>
            </div>
         </div>
      </div>
      <script>
   
         function generateQRCode(url, size) {
             const qrCodeContainer = document.getElementById("qrcode");
             new QRCode(qrCodeContainer, {
                 text: url,
                 width: size,
                 height: size,
                 colorDark: "#000000",
                 colorLight: "#ffffff",
                 correctLevel: QRCode.CorrectLevel.H
             });
         }


         const url = "<?php echo $url; ?>";
         const size = <?php echo $size; ?>;


         generateQRCode(url, size);
      </script>
   </body>
</html>
