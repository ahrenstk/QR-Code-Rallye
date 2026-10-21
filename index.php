<?php
/* -----------------------------------------------------------------------------

MIT License

Copyright (c) 2026 Heinz Ahrens https://github.com/ahrenstk/QR-Code-Rallye

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

----------------------------------------------------------------------------- */

declare(strict_types=1);
error_reporting(E_ALL);
ini_set("display_errors", "Off");
ini_set("log_errors", "On");
ini_set("error_log", "error.log");

function loadConfig($filename)
{
    $config = [];
    $safe_filename = preg_replace("/[^a-zA-Z0-9_\-\.\/]/", "", $filename);

    if (file_exists($safe_filename) && is_readable($safe_filename)) {
        $lines = file(
            $safe_filename,
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
        );
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === "" || $line[0] === "#" || $line[0] === ";") {
                continue;
            }

            if (strpos($line, "=") !== false) {
                list($key, $value) = explode("=", $line, 2);
                $config[trim($key)] = trim($value);
            }
        }
    }
    return $config;
}

$config = loadConfig("config/variablen.txt");
$titelkurz = $config["titelkurz"] ?? "titelkurz";
$titellang = $config["titellang"] ?? "titellang";

$secret = $config["secret"] ?? "secret";
$stationoffset = (int) ($config["stationoffset"] ?? 42);
$sevenmodulo = (int) ($config["sevenmodulo"] ?? 100);
$adminpasswort = $config["adminpasswort"] ?? "passwort";
$aktionsende = filter_var(
    $config["aktionsende"] ?? "false",
    FILTER_VALIDATE_BOOLEAN
);
$beginnaktion = $config["beginnaktion"] ?? "01.09.2024";
$endeaktion = $config["endeaktion"] ?? "31.10.2024";
$meinname = $config["meinname"] ?? "meinname42";

if (isset($config["tag"])) {
    $tag = $config["tag"];
}

// Funktionstest (Simulierung eines Tages im Aktionszeitraum)
//$tag = "08.06.2025";
//$st = "10";
//$minute = "10";

function ArraySort($tArray)
{
    $aOriginal = $tArray;
    if (count($aOriginal) == 0) {
        return $aOriginal;
    }
    $aModified = [];
    $aReturn = [];
    $aSearch = ["Ä", "ä", "Ö", "ö", "Ü", "ü", "ß", "-"];
    $aReplace = ["Ae", "ae", "Oe", "oe", "Ue", "ue", "ss", " "];
    foreach ($aOriginal as $key => $val) {
        $aModified[$key] = str_replace($aSearch, $aReplace, $val);
    }
    natcasesort($aModified);
    foreach ($aModified as $key => $val) {
        $aReturn[$key] = $aOriginal[$key];
    }
    return $aReturn;
}

function date2dayofyear($string)
{
    $zeile = explode(".", $string);
    $day = date(
        "z",
        mktime(0, 0, 0, intval($zeile[1]), intval($zeile[0]), intval($zeile[2]))
    );
    return $day;
}

function dayofyear2date($tDay)
{
    global $beginnaktion;
    $year = date("Y", strtotime($beginnaktion));
    $datumday = date_create_from_format("Y z", $year . $tDay);
    $str = $datumday->format("d.m.Y");
    return $str;
}

function anzahltage($anfang, $ende)
{
    $date1 = DateTime::createFromFormat("d.m.Y", $anfang);
    $date2 = DateTime::createFromFormat("d.m.Y", $ende);
    $interval = $date1->diff($date2);
    return $interval->days;
}

function offset1($sta)
{
    global $stationoffset;
    if ($sta != 0) {
        $sta = $sta + $stationoffset;
        if ($sta > 89) {
            $sta = $sta - 89;
        }
    }
    return $sta;
}

function offset2($sta)
{
    global $stationoffset;
    if ($sta != 0) {
        $sta = $sta - $stationoffset;
        if ($sta <= 0) {
            $sta = $sta + 89;
        }
    }
    return $sta;
}

function aktionstag($str)
{
    global $beginnaktion;
    $string = strval($str);
    $zeile = explode(".", $string);
    $a = $zeile[0] ?? null;
    $b = $zeile[1] ?? null;
    $c = $zeile[2] ?? null;

    $zeile = explode(".", $beginnaktion);
    $d = $zeile[0] ?? null;
    $e = $zeile[1] ?? null;
    $f = $zeile[2] ?? null;

    $groesser = sprintf("%04d-%02d-%02d", intval($c), intval($b), intval($a));
    $kleiner = sprintf("%04d-%02d-%02d", intval($f), intval($e), intval($d));

    $datum_groesser = strtotime($groesser);
    $datum_kleiner = strtotime($kleiner);
    $differenz = $datum_groesser - $datum_kleiner;
    $differenz = $differenz / 86400;

    return $differenz;
}

function wochentag($tag)
{
    $wochentage = ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"];
    $zeile = explode(".", $tag);
    $zeit = $zeile[2] . "-" . $zeile[1] . "-" . $zeile[0];
    $w = strtotime($zeit);
    return $wochentage[date("w", $w)];
}

function timetointerval($st, $zehn)
{
    $startMinute = $zehn * 10;
    $endMinute = ($startMinute + 10) % 60;
    $endHour = ($st + (int) (($startMinute + 10) / 60)) % 24;

    return sprintf(
        "%02d:%02d - %02d:%02d Uhr",
        $st,
        $startMinute,
        $endHour,
        $endMinute
    );
}


function feistelRound($R, $round, $secret) {
    $hash = hash("sha256", $secret . "_" . $round . "_" . $R);
    return hexdec(substr($hash, 0, 8)) % 900;
}


function xxxxtocode($string) {
    global $secret;
    $all = "123456789ACDEFGHJKLMNPQRTUVWXZ";
    

    $dez = (int) base_convert($string, 30, 10);
    

    $L = $dez % 900;
    $R = intdiv($dez, 900);
    

    for ($round = 0; $round < 4; $round++) {
        $next_L = $R;
        $next_R = ($L + feistelRound($R, $round, $secret)) % 900;
        $L = $next_L;
        $R = $next_R;
    }
    
    $encrypted_zahl = $R * 900 + $L;
    

    $output = "";
    $temp = $encrypted_zahl;
    for ($i = 0; $i < 4; $i++) {
        $output = $all[$temp % 30] . $output;
        $temp = intdiv($temp, 30);
    }
    
    return $output;
}


function codetoxxxx($string) {
    global $secret;
    $all = "123456789ACDEFGHJKLMNPQRTUVWXZ";
    

    $encrypted_zahl = 0;
    $chars = str_split(strtoupper($string));
    foreach ($chars as $char) {
        $encrypted_zahl = $encrypted_zahl * 30 + strpos($all, $char);
    }
    
    $R = intdiv($encrypted_zahl, 900);
    $L = $encrypted_zahl % 900;
    

    for ($round = 3; $round >= 0; $round--) {
        $R_prev = $L;
        $L_prev = ($R - feistelRound($R_prev, $round, $secret) + 900) % 900;
        $L = $L_prev;
        $R = $R_prev;
    }
    
    $original_zahl = $R * 900 + $L;
    

    $xxxx = strtoupper(base_convert((string)$original_zahl, 10, 30));
    return sprintf("%04s", $xxxx);
}


function stringtoxxxx($string)
{
    global $offset;
    $zeile = explode(";", $string);

    $tag = aktionstag($zeile[1]);
    $sta = offset1(intval($zeile[0]));
    $zeile3 = intval($zeile[3]);
    $zeile2 = intval($zeile[2]);

    $zahl = 9000 * $sta + 1500 * $zeile3 + $tag * 24 + $zeile2;

    $xxxx = strtoupper(base_convert(strval($zahl), 10, 30));
    return sprintf("%04s", $xxxx);
}

function xxxxtostring($xxxx)
{
    global $beginnaktion, $offset;

    $xxxx = strtoupper($xxxx);
    $dez = base_convert($xxxx, 30, 10);

    $sta = intdiv((int) $dez, 9000);
    $rest = (int) $dez % 9000;

    $sta = offset2($sta);
    $sta = sprintf("%02d", $sta);

    $zehn = intdiv($rest, 1500);
    $rest2 = $rest % 1500;

    $tag = intdiv($rest2, 24);
    $stunde = $rest2 % 24;

    $stunde = sprintf("%02d", $stunde);

    $tag += date2dayofyear($beginnaktion);
    $datum = dayofyear2date($tag);

    return "$sta;$datum;$stunde;$zehn";
}

function xxxxtoday($xxxx)
{
    global $beginnaktion;
    $dez = base_convert(strval($xxxx), 30, 10);
    $sta = intdiv((int) $dez, 9000);
    $rest = (int) $dez - $sta * 9000;
    $zehn = intdiv($rest, 1500);
    $rest2 = $rest - $zehn * 1500;
    $tag2 = intdiv($rest2, 24);
    $tag2 = $tag2 + date2dayofyear($beginnaktion);
    $datum = dayofyear2date($tag2);
    return $datum;
}

function xxxxtotime($xxxx)
{
    $dez = base_convert(strval($xxxx), 30, 10);
    $sta = intdiv((int) $dez, 9000);
    $rest = (int) $dez - $sta * 9000;
    $zehn = intdiv($rest, 1500);
    $rest2 = $rest - $zehn * 1500;
    $tag = intdiv($rest2, 24);
    $stunde = $rest2 - $tag * 24;
    $stunde = sprintf("%02d", $stunde);
    $intervall = timetointerval($stunde, $zehn);
    return $intervall;
}

date_default_timezone_set("Europe/Berlin");
$aktionstage = anzahltage($beginnaktion, $endeaktion) + 1;
$gesamtorte = [];
$stationgefunden = "";
$startseite = true;
$adminhtml = "";
$profil = false;
$stationid = "";
$aktionszeit = false;
$tag = $tag ?? date("d.m.Y");

$st = $st ?? (int) date("H");
$minute = $minute ?? intval(date("i"));
$minute = !empty($minute) ? intval($minute) : intval(date("i"));

$zehn = intdiv($minute, 10);

$jetztdatum = $tag;
$jetztzeit = timetointerval($st, $zehn);

$ergebnisdatum = $tag;
$ergebniswtag = wochentag($tag);
$ergebniszeit = timetointerval($st, $zehn);

$orte_datei = "orte/orte.txt";
$i = 0;
$anzgesamtorte = 0;

if (file_exists($orte_datei) && is_readable($orte_datei)) {
    $filename = file(
        $orte_datei,
        FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
    );

    foreach ($filename as $line) {
        $line = trim($line);

        if ($line === "" || $line[0] === "#") {
            continue;
        }

        $list = explode(";", $line);

        if (count($list) < 2) {
            continue;
        }

        $nr = trim($list[0]);

        if (ctype_digit($nr) && intval($nr) >= 0 && intval($nr) <= 89) {
            $nr_int = intval($nr);
            $nr_formatted = sprintf("%02d", $nr_int);

            // Standardwerte setzen, falls Spalten in der Zeile fehlen (verhindert PHP-Warnungen)
            $station_name = trim($list[1]);
            $station_lat = isset($list[2]) ? trim($list[2]) : "0.0";
            $station_lon = isset($list[3]) ? trim($list[3]) : "0.0";
            $station_text = isset($list[4]) ? trim($list[4]) : "";

            $listname[$nr_formatted] = $station_name;
            $station[$nr_formatted] = $station_name;
            $lat[$nr_formatted] = $station_lat;
            $lon[$nr_formatted] = $station_lon;
            $text[$nr_formatted] = $station_text;

            if ($nr_int > 0 && $nr_int <= 89) {
                $dropdown[$i] = $station_name;
                $i = $i + 1;

                array_push($gesamtorte, $nr_formatted);
            }
        }
    }
    $anzgesamtorte = $i;
}

if (isset($_POST["fstation"])) {
    $fstation = $_POST["fstation"];
    $fstation = trim($fstation);
    for ($i = 0; $i <= 89; $i++) {
        $i2 = sprintf("%02d", $i);
        $tmp = $station[$i2] ?? "";
        if (strcmp($tmp, $fstation) == null) {
            $stationid = $i2;
        }
    }
}

if (isset($_POST["seven"])) {
    $startseite = false;

    // Nur exakt alphanumerische Zeichen für den 7-stelligen Code erlauben
    $seven = preg_replace("/[^a-zA-Z0-9]/", "", $_POST["seven"]);

    $code = $_POST["code"];
    $code = strtolower($code);
    $s = $code . ";" . $secret;
    $md5 = md5($s);

    // Pfad-Bereinigung zur absoluten Sicherheit gegen Path Traversal
    $folder_name = preg_replace("/[^a-f0-9]/i", "", substr($md5, 0, 2));
    $folder_path = "profile/" . $folder_name;

    if (!file_exists($folder_path)) {
        // 0755 ist restriktiver und sicherer als 0777
        mkdir($folder_path, 0755, true);
    }

    $safe_md5 = preg_replace("/[^a-f0-9]/i", "", $md5);
    $file = $folder_path . "/" . $safe_md5 . ".txt";

    $inhalt = "";
    if (file_exists($file)) {
        $inhalt = file_get_contents($file);
    }
    $inhalt = $inhalt ?? "";

    // Nur schreiben, wenn die Eingabe nach der Bereinigung immer noch exakt 7 Zeichen lang ist
    if (strlen($seven) == 7) {
        if (strpos($inhalt, $seven) === false) {
            $inhalt .= $seven . "\n";
            file_put_contents($file, $inhalt);
        }
    }
}

if (isset($_POST["code"])) {
    $code = $_POST["code"];
    $code = trim($code);
    $code = strtolower($code);

    if (strcmp($code, "0000") == 0 && $stationid == "00") {
        $stationgefunden = $stationid;
        $ergebnis = "0000";
    }

    if (strcmp($code, $adminpasswort) == 0 || (empty($code) && $aktionsende)) {
        $stationgefunden = $stationid;
        $ergebnis = "____";
    }

    if (strlen($code) == 5) {
        for ($i = 0; $i <= 89; $i++) {
            $stationnr = sprintf("%02d", $i);
            $stationtupel = $stationnr . ";" . $secret;
            $stationmd5 = md5($stationtupel);
            $stationsecret = substr($stationmd5, 0, 5);
            if ($code == $stationsecret && $stationnr == $stationid) {
                $stationgefunden = $stationnr;
                $ergebnis = "____";
            }
        }
    }

    if (
        strlen($code) > 7 &&
        strlen($code) < 15 &&
        strcmp($code, $adminpasswort) != 0 &&
        strcmp($code, $meinname) != 0 &&
        preg_match("/(?:\d.*){2,}/", $code) &&
        preg_match("/(?:[a-zA-Z].*){2,}/", $code)
    ) {
        $s = $code . ";" . $secret;
        $md5 = md5($s);
        header("Location: ./" . $md5);
        exit();
    }

    if (strlen($code) == 4) {
        if (
            preg_match(
                "/^[123456789acdefghjklmnpqrtuvwxzACDEFGHJKLMNPQRTUVWXZ]+$/",
                $code
            ) == 1
        ) {
            $ergebnisxxxx = codetoxxxx($code);
            $dez = base_convert($ergebnisxxxx, 30, 10);
            $hex = base_convert($ergebnisxxxx, 30, 16);
            $hex = sprintf("%05s", $hex);
            $dezcheck = $dez % $sevenmodulo;
            $dezcheck = sprintf("%02d", $dezcheck);

            $dez2 = base_convert($ergebnisxxxx, 30, 10);
            $sta = intdiv(intval($dez2), 9000);
            $sta = sprintf("%02d", $sta);

            $str = xxxxtostring($ergebnisxxxx);
            $zeile = explode(";", $str);
            $tmptag = aktionstag($zeile[1]);

            if (
                strcmp($zeile[0], $stationid) == 0 &&
                $tmptag >= 0 &&
                $tmptag < $aktionstage
            ) {
                $seven =
                    substr($hex, 0, 2) .
                    substr($dezcheck, 0, 1) .
                    substr($hex, 2, 1) .
                    substr($dezcheck, 1, 1) .
                    substr($hex, 3, 2);
                header("Location: ./" . $seven);
                exit();
            }
        }
    }
}

if (isset($_GET["a"])) {
    $a = preg_replace("/[^a-zA-Z0-9]/", "", $_GET["a"]);
    $a = strtolower($a);

    if (strlen($a) == 32) {
        $profil = true;
        $startseite = false;
    }
}

if (isset($_GET["c"])) {
    $c = $_GET["c"];
}
if (isset($_GET["d"])) {
    $d = $_GET["d"];
} else {
    $d = "";
}
if (isset($_GET["e"])) {
    $e = $_GET["e"];
}
if (isset($_GET["b"])) {
    if (strcmp($a, $adminpasswort) === 0) {
        $startseite = false;
        $adminhtml = " ";

        $b = preg_replace("/[^a-zA-Z0-9_\-\s]/", "", $_GET["b"]);

        if (empty($c)) {
            $orte_datei = "orte/orte.txt";
            if (file_exists($orte_datei) && is_readable($orte_datei)) {
                $filename = file(
                    $orte_datei,
                    FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
                );
                foreach ($filename as $line) {
                    $pos = stripos($line, $b);
                    if ($pos !== false || strcmp($b, "all") === 0) {
                        $zeile = explode(";", $line);

                        $z0 = isset($zeile[0]) ? trim($zeile[0]) : "";
                        $z2 = isset($zeile[2]) ? trim($zeile[2]) : "0.0";
                        $z3 = isset($zeile[3]) ? trim($zeile[3]) : "0.0";

                        $s = $z0 . ";" . $secret;
                        $md5 = md5($s);
                        $stationadmin = substr($md5, 0, 5);

                        $safe_line = htmlspecialchars(
                            $line,
                            ENT_QUOTES,
                            "UTF-8"
                        );
                        $safe_stationadmin = htmlspecialchars(
                            $stationadmin,
                            ENT_QUOTES,
                            "UTF-8"
                        );
                        $safe_z2 = htmlspecialchars($z2, ENT_QUOTES, "UTF-8");
                        $safe_z3 = htmlspecialchars($z3, ENT_QUOTES, "UTF-8");

                        $adminhtml .=
                            $safe_line .
                            " " .
                            '<a href="./' .
                            $safe_stationadmin .
                            '">' .
                            $safe_stationadmin .
                            "</a> " .
                            '<a href="./' .
                            $safe_stationadmin .
                            '_qr">' .
                            $safe_stationadmin .
                            "_qr</a>" .
                            '<a href="Karte_' .
                            $safe_z2 .
                            "_" .
                            $safe_z3 .
                            '"> <img src="bilder/bilder/blue-small.png"></a>&nbsp;' .
                            '<a href="https://maps.google.com/maps?q=' .
                            $safe_z2 .
                            "," .
                            $safe_z3 .
                            '"><img src="bilder/bilder/maps.png"></a>' .
                            '<a href="geo:' .
                            $safe_z2 .
                            "," .
                            $safe_z3 .
                            '"><img src="bilder/bilder/geo.jpg"></a><br>';
                    }
                }
            }
        }

        if (preg_match("/^[0-9.]+$/", $d) == 1) {
            $b = sprintf("%02d", $b);
            $c = sprintf("%06s", $c);
            $d = sprintf("%02d", $d);

            $dteile = explode(".", $c);
            $dtag = (int) $dteile[0];
            $dmonat = (int) $dteile[1];
            $djahr = (int) $dteile[2];
            $atag = aktionstag($c);

            if (
                checkdate($dmonat, $dtag, $djahr) &&
                $atag >= 0 &&
                $atag < $aktionstage
            ) {
                $str = $b . ";" . $c . ";" . $d . ";" . $e;

                $xxxx = stringtoxxxx($str);
                $code = xxxxtocode($xxxx);
                $tag = aktionstag($c);
                $stationname = $station[$b] ?? null;
                if ($e < 6) {
                    $adminhtml =
                        $adminhtml .
                        $str .
                        " " .
                        " " .
                        $atag .
                        " " .
                        $code .
                        " " .
                        $stationname .
                        "<br>";
                }
            }
        }

        if (
            preg_match(
                "/^[123456789acdefghjklmnpqrtuvwxzACDEFGHJKLMNPQRTUVWXZ]+$/",
                $b
            ) == 1 &&
            strlen($b) == 4
        ) {
            $xxxx = codetoxxxx($b);
            $str = xxxxtostring($xxxx);
            list($s, $datum, $rest) = explode(";", $str);
            if (aktionstag($datum) >= 0 && aktionstag($datum) < $aktionstage) {
                $wtag = wochentag($datum);
                $tag = aktionstag($datum);
                $s2 = $s . ";" . $secret;
                $md5 = md5($s2);
                $stationname = $station[$s] ?? null;
                $stationadmin = substr($md5, 0, 5);
                $adminhtml =
                    $adminhtml .
                    $b .
                    " " .
                    $str .
                    " " .
                    $wtag .
                    " " .
                    $tag .
                    " " .
                    $stationname .
                    " " .
                    ' <a href="./' .
                    $stationadmin .
                    '">' .
                    $stationadmin .
                    "</a>";
                $adminhtml =
                    $adminhtml .
                    ' <a href="./' .
                    $stationadmin .
                    '_qr">' .
                    $stationadmin .
                    "_qr</a><br>";
            }
        }
    }
}

if (!$stationgefunden) {
    if (aktionstag($tag) >= 0 && aktionstag($tag) < $aktionstage) {
        $aktionszeit = true;
    }

    if (isset($a) && strlen($a) == 5) {
        for ($i = 0; $i <= 89; $i++) {
            $stationnr = sprintf("%02d", $i);
            $stationtupel = $stationnr . ";" . $secret;
            $stationmd5 = md5($stationtupel);
            $stationsecret = substr($stationmd5, 0, 5);
            $tmp = $station[$stationnr] ?? null;
            if ($a == $stationsecret && $tmp) {
                $stationgefunden = $stationnr;
                if ($aktionszeit) {
                    $string =
                        $stationgefunden . ";" . $tag . ";" . $st . ";" . $zehn;
                    $ergebnisxxxx = stringtoxxxx($string);
                    $ergebnis = xxxxtocode($ergebnisxxxx);

                    $dez = base_convert($ergebnisxxxx, 30, 10);
                    $hex = base_convert($ergebnisxxxx, 30, 16);
                    $hex = sprintf("%05s", $hex);

                    $dezcheck = $dez % $sevenmodulo;
                    $dezcheck = sprintf("%02d", $dezcheck);

                    $seven =
                        substr($hex, 0, 2) .
                        substr($dezcheck, 0, 1) .
                        substr($hex, 2, 1) .
                        substr($dezcheck, 1, 1) .
                        substr($hex, 3, 2);
                    header("Location: ./" . $seven);
                    exit();
                } else {
                    $ergebnis = "____";
                }
            }
        }
    }

    if (isset($a) && strlen($a) == 7) {
        $seven = $a;
        $hex = substr($a, 0, 2) . substr($a, 3, 1) . substr($a, 5, 2);
        $checkdez = substr($a, 2, 1) . substr($a, 4, 1);
        $dez = base_convert($hex, 16, 10);
        $checkmodulo = $dez % $sevenmodulo;

        if ($checkmodulo == $checkdez) {
            $xxxx = base_convert($hex, 16, 30);
            $xxxx = sprintf("%04s", $xxxx);
            $ergebnis = xxxxtocode($xxxx);
            $str = xxxxtostring($xxxx);
            $zeile = explode(";", $str);
            if ($station[$zeile[0]] != "") {
                $stationgefunden = $zeile[0];
            }
            $s = codetoxxxx($ergebnis);
            $ergebnisdatum = xxxxtoday($s);
            $ergebniszeit = xxxxtotime($s);
            $ergebniswtag = wochentag($ergebnisdatum);
        }
    }
}

if ($stationgefunden) {
    $stationsname = $station[$stationgefunden];
    $stationsnamexxl = $stationsname;
} else {
    $stationsname = $titelkurz;
    $stationsnamexxl = $titellang;
}

$headerContent = file_get_contents("config/startseite-header1.txt");
echo $headerContent;

echo "<title>" . $stationsname . "</title>";

$headerContent = file_get_contents("config/startseite-header2.txt");
echo $headerContent;

echo '<div class="w3-bar-item w3-button w3-padding-large w3-hover-white w3-large w3-hide-small w3-hide-medium">' .
    $stationsnamexxl .
    "</div>";
echo '<div class="w3-bar-item w3-button w3-padding-large w3-hover-white w3-hide-large w3-medium">' .
    $stationsname .
    "</div>";

$headerContent = file_get_contents("config/startseite-header3.txt");
echo $headerContent;

if ($stationgefunden) {
    echo '<div class="w3-container w3-hide-medium w3-hide-large"><br></div>';

    echo '<button class="w3-button w3-hover-teal w3-teal w3-round">';
    echo substr($ergebnis, 0, 1) .
        " " .
        substr($ergebnis, 1, 1) .
        " " .
        substr($ergebnis, 2, 1) .
        " " .
        substr($ergebnis, 3, 1);
    echo "</button>";
    echo " ";
    echo '<button class="w3-button w3-hover-teal w3-teal w3-round">';
    echo $ergebniswtag . ". ";
    echo $ergebnisdatum;
    echo "</button>";
    echo " ";
    echo '<button class="w3-button w3-hover-teal w3-teal w3-round">';
    echo $ergebniszeit;
    echo "</button>";

    echo "<br><br>";

    $safe_stationgefunden = preg_replace("/[^0-9]/", "", $stationgefunden);
    $dateiname = "orte/" . $safe_stationgefunden . ".txt";

    if (!empty($safe_stationgefunden) && file_exists($dateiname)) {
        $filename = file($dateiname);
        foreach ($filename as $line) {
            echo $line;
        }
    } else {
        $default = "bilder/bilder/default.jpg";
        if (file_exists($default)) {
            echo '<img src="' . $default . '" class="w3-image center">';
        }
    }

    echo "<br><br>";

    if (
        $stationgefunden &&
        strcmp($ergebnis, "____") !== 0 &&
        $stationgefunden != 0 &&
        $ergebnisdatum == $jetztdatum &&
        $ergebniszeit == $jetztzeit
    ) {
        echo '<form method="post" action="index.php" class="w3-container w3-center">';
        echo '<div class="w3-row-padding">';
        echo '<div class="w3-col s12 m6 l4 w3-center" style="float: none; margin: 0 auto;">';
        echo '<input class="w3-input w3-border w3-margin-bottom" type="text" name="code" placeholder="Benutzername">';
        echo '<input type="hidden" name="seven" value="' . $seven . '">';
        echo '<button class="w3-btn w3-teal w3-round w3-block">Besuch hinzufügen</button>';
        echo "</div>";
        echo "</div>";
        echo "</form>";
    }
    echo "<br><br><br>";
    echo '<div class="w3-container w3-center">';
    echo "<b>" . $stationsname . "</b><br>";
    echo $text[$stationgefunden] . "<br>";
    echo '<a href="Karte_' .
        $lat[$stationgefunden] .
        "_" .
        $lon[$stationgefunden] .
        '"><img src="bilder/bilder/blue.png"></a>';
    echo "<br>";
    if (strcmp($ergebnis, "____") !== 0) {
        echo $ergebnis .
            " " .
            $ergebniswtag .
            ". " .
            $ergebnisdatum .
            " " .
            $ergebniszeit .
            "<br>";
        $protocol = $_SERVER["PROTOCOL"] =
            isset($_SERVER["HTTPS"]) && !empty($_SERVER["HTTPS"])
                ? "https"
                : "http";
        $url =
            $protocol .
            "://" .
            $_SERVER["HTTP_HOST"] .
            "<br>" .
            $_SERVER["REQUEST_URI"];
        $a = $a ?? "";
        echo '<a href="./' . $a . '">' . $url . "</a>";
    }

    echo "</div>";

    echo "<br><br><br>";
} else {
    if ($adminhtml) {
        echo "<b>Admin:</b>";
        echo "<br>";
        echo $adminhtml;
        echo "<br><br>";

        $headerContent = file_get_contents("config/startseite-admin.txt");
        echo $headerContent;
        echo "<br><br>";
    }

    if ($profil) {
        $a = $a ?? "";
        $code = strtolower($a);
        $folder_name = substr($code, 0, 2);
        $folder_path = "profile/" . $folder_name;
        $file = $folder_path . "/" . $code . ".txt";

        if (file_exists($file)) {
            $inhalt = file_get_contents($file);
        }
        if (isset($seven) && strlen($seven) == 7) {
            if (strpos($inhalt, $seven) === false) {
                $inhalt .= $seven . "\n";
                file_put_contents($file, $inhalt);
            }
        }
        $inhalt = $inhalt ?? "";
        $zeilen = explode("\n", $inhalt);

        $i = 0;
        $ortegefunden = [];
        foreach ($zeilen as $a) {
            if (strlen($a) == 7) {
                $hex = substr($a, 0, 2) . substr($a, 3, 1) . substr($a, 5, 2);
                $checkdez = substr($a, 2, 1) . substr($a, 4, 1);
                $dez = base_convert($hex, 16, 10);
                $checkmodulo = $dez % $sevenmodulo;

                if ($checkmodulo == $checkdez) {
                    $xxxx = base_convert($hex, 16, 30);
                    $xxxx = sprintf("%04s", $xxxx);
                    $ergebnis = xxxxtocode($xxxx);

                    $s = codetoxxxx($ergebnis);
                    $ergebnisdatum = xxxxtoday($s);
                    $ergebniszeit = xxxxtotime($s);
                    $ergebniswtag = wochentag($ergebnisdatum);
                    $string = xxxxtostring($s);
                    $zeile = explode(";", $string);
                    $stationsnummer = $zeile[0];
                    $stationsname = $listname[$stationsnummer];

                    array_push($ortegefunden, "$stationsnummer");

                    if (!isset($eintraggefunden)) {
                        $eintraggefunden = true;
                        echo "<b>Deine gefundenen Orte:</b>";
                        echo "<ul>";
                    }
                    echo "<li>";
                    echo "<b>" .
                        htmlspecialchars(
                            $stationsname ?? "",
                            ENT_QUOTES,
                            "UTF-8"
                        ) .
                        "</b>&nbsp;";
                    echo htmlspecialchars(
                        $text[$stationsnummer] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) . "&nbsp;";

                    $safe_lat = htmlspecialchars(
                        $lat[$stationsnummer] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    $safe_lon = htmlspecialchars(
                        $lon[$stationsnummer] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    );

                    echo '<a href="Karte_' .
                        $safe_lat .
                        "_" .
                        $safe_lon .
                        '"><img src="bilder/bilder/blue-small.png"></a>&nbsp;';
                    echo '<a href="https://maps.google.com/maps?q=' .
                        $safe_lat .
                        "," .
                        $safe_lon .
                        '"><img src="bilder/bilder/maps.png"></a>';
                    echo '<a href="geo:' .
                        $safe_lat .
                        "," .
                        $safe_lon .
                        '"><img src="bilder/bilder/geo.jpg" alt="Smartphone Navigation" style="width:20px; height:auto;"></a>&nbsp;';

                    echo htmlspecialchars($ergebniswtag, ENT_QUOTES, "UTF-8") .
                        ". " .
                        htmlspecialchars($ergebnisdatum, ENT_QUOTES, "UTF-8") .
                        ", " .
                        htmlspecialchars($ergebniszeit, ENT_QUOTES, "UTF-8") .
                        ", ";
                    echo '<b><a href="' .
                        htmlspecialchars($a, ENT_QUOTES, "UTF-8") .
                        '">' .
                        htmlspecialchars($ergebnis, ENT_QUOTES, "UTF-8") .
                        "</a></b> ";
                    echo "</li>";
                }
            }
        }

        echo "</ul>";

        if (isset($eintraggefunden) && $eintraggefunden) {
            $diff = array_diff($gesamtorte, $ortegefunden);

            if ($diff) {
                uasort($diff, function ($a, $b) use ($station) {
                    return strcasecmp($station[$a], $station[$b]);
                });

                echo "<br>";
                echo "<b>Die folgenden Orte fehlen dir noch:</b>";
                echo "<ul>";
                foreach ($diff as $eintrag) {
                    echo "<li>";
                    echo "<b>" .
                        htmlspecialchars(
                            $station[$eintrag] ?? "",
                            ENT_QUOTES,
                            "UTF-8"
                        ) .
                        "</b>&nbsp;";
                    echo htmlspecialchars(
                        $text[$eintrag] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) . "&nbsp;";

                    $safe_lat_diff = htmlspecialchars(
                        $lat[$eintrag] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    $safe_lon_diff = htmlspecialchars(
                        $lon[$eintrag] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    );

                    echo '<a href="Karte_' .
                        $safe_lat_diff .
                        "_" .
                        $safe_lon_diff .
                        '"><img src="bilder/bilder/blue-small.png"></a>';
                    echo '<a href="https://maps.google.com/maps?q=' .
                        $safe_lat_diff .
                        "," .
                        $safe_lon_diff .
                        '"><img src="bilder/bilder/maps.png"></a>';
                    echo '<a href="geo:' .
                        $safe_lat_diff .
                        "," .
                        $safe_lon_diff .
                        '"><img src="bilder/bilder/geo.jpg" alt="Smartphone Navigation" style="width:20px; height:auto;"></a>&nbsp;';

                    echo "</li>";
                }
                echo "</ul>";
            }

            $anzorte = count(array_unique($ortegefunden));

            echo "<br>";
            if ($anzorte == $anzgesamtorte) {
                echo "<b>Toll. Du hast alle " .
                    $anzgesamtorte .
                    " Orte gefunden.</b> ";
            } else {
                echo "<b>Toll. Du hast " .
                    $anzorte .
                    " von " .
                    $anzgesamtorte .
                    " Orte gefunden.</b> ";
            }
        } else {
            echo "Mit diesem Benutzernamen wurden noch keine Orte und Zeitstempel verknüpft. Entdecke einen Ort, scanne den QR-Code und füge den Ort deinem Benutzerprofil hinzu.";
        }
        echo "<br><br>";

        $headerContent = file_get_contents("config/benutzername.txt");
        echo $headerContent;
    }

    if ($startseite) {

        echo '<form method="post" class="w3-row-padding" action="index.php">';
        echo '<div class="w3-col s4  w3-center ">';

        echo '<input class="w3-input w3-border" type="text" name="code" placeholder="';
        if (!$aktionsende) {
            echo 'Zeitstempel / Benutzername">';
        } else {
            echo 'Zeitstempel / Benutzername / keine Eingabe">';
        }
        echo "</div>";
        echo '<div class="w3-col s4 w3-center">';
        echo '<select class="w3-select w3-border w3-white" name="fstation" id="fstation">';
        echo '<option value="' .
            $station["00"] .
            '">' .
            $station["00"] .
            "</option>\n";

        if ($dropdown) {
            $dropdown2 = ArraySort($dropdown);
            foreach ($dropdown2 as $ziel) {
                if ($ziel) {
                    echo '<option value="' .
                        $ziel .
                        '">' .
                        $ziel .
                        "</option>\n";
                }
            }
        }
        echo "</select>";
        echo "</div>";
        ?>
      <div class="w3-col s4  w3-left">
         <button class="w3-btn w3-teal w3-round">Ort besuchen</button>
      </div>
      </form>
      <br>
      <?php
      $headerContent = file_get_contents("config/startseite-inhalt1.txt");
      echo $headerContent;

      $s2 = "00;" . $secret;
      $md5 = md5($s2);
      $stationname = $station[0] ?? null;
      $ortsid = substr($md5, 0, 5);
      $protocol = $_SERVER["PROTOCOL"] =
          isset($_SERVER["HTTPS"]) && !empty($_SERVER["HTTPS"])
              ? "https"
              : "http";
      $uri = $_SERVER["REQUEST_URI"];
      $verzeichnis = str_replace("index.php", "", $uri);
      $url1 = $protocol . "://" . $_SERVER["HTTP_HOST"];
      $url2 = $verzeichnis . $ortsid;
      $url = $url1 . $url2;

      echo "    <br>" . PHP_EOL;
      echo '<div class="qrcode">';
      echo '<div id="qrcode"></div>';
      echo "</div>";

      echo "    <script>";
      echo '        const url = "' . addslashes($url) . '";';
      echo "        generateQRCode(url,200);";
      echo "    </script>";
      echo "    <br>";

      echo "<br>";
      echo $url1 . $url2 . "</b><br><br></div>";

      $headerContent = file_get_contents("config/startseite-inhalt2.txt");
      echo $headerContent;

    }
}
$headerContent = file_get_contents("config/startseite-footer.txt");
echo $headerContent;

?>
