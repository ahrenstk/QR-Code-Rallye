<?php

$headerContent = file_get_contents("config/orte-header.txt");
echo $headerContent;

function ArraySort($tArray) 
{
    $aOriginal = $tArray;
    if (count($aOriginal) == 0) { return $aOriginal; }
    $aModified = array();
    $aReturn   = array();
    $aSearch   = array("Ä","ä","Ö","ö","Ü","ü","ß","-");
    $aReplace  = array("Ae","ae","Oe","oe","Ue","ue","ss"," ");
    foreach ($aOriginal as $key => $val) 
    {
        $aModified[$key] = str_replace($aSearch, $aReplace, $val);
    }
    natcasesort($aModified);
    foreach ($aModified as $key => $val) 
    {
        $aReturn[$key] = $aOriginal[$key];
    }
    return $aReturn;
}               

$filename = file("orte/orte.txt");
$i = 0;
foreach ($filename as $line)
{
    $list = explode(";", $line);
    $nr = $list[0];
    if (($nr >= 0) && ($nr <= 89))
    {
        if (($nr > 0) && ($nr <= 89))
        {
            $name = $list[1];
            $dropdown[$i] = $name;
            $station[$name] = $list[1];
            $lat[$name] = $list[2];
            $lon[$name] = trim($list[3]);
            $text[$name] = trim($list[4]) ?? NULL;
            $i = $i + 1;
        }
    }
}

echo 'Im Aktionszeitraum kannst du an den folgenden ' . $i . ' Orten den QR-Code suchen:';
echo '<br><br>';

$dropdown2 = ArraySort($dropdown);
foreach($dropdown2 as $ziel) 
{
    echo "<b>" . $station[$ziel] . "</b>" . " ";
    echo $text[$ziel] . " ";
    echo '<a href="Karte_' . $lat[$ziel] . '_' . $lon[$ziel] . '"> <img src="bilder/bilder/blue-small.png"></a>&nbsp;'; 
    echo '<a href="https://maps.google.com/maps?q=' . $lat[$ziel] . ',' . $lon[$ziel] . '"><img src="bilder/bilder/maps.png"></a>&nbsp;';    
    echo '<a href="geo:' . $lat[$ziel] . ',' . $lon[$ziel] . '"><img src="bilder/bilder/geo.jpg" alt="Smartphone Navigation" style="width:20px; height:auto;"></a>'; // Größe angepasst
    echo '<br>';
}  

$headerContent = file_get_contents("config/orte-footer.txt");
echo $headerContent;

?>
