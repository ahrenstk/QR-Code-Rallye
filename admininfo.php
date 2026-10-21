<?php
$file = "config/admininfo.txt";

if (!file_exists($file) || !is_readable($file)) {
    exit;
}

$content = file_get_contents($file);
if ($content === false) {
    exit;
}

$content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);


$content = preg_replace('/(\s+)(on[a-z]+\s*=\s*)/i', '$1data-disabled-$2', $content);


$content = preg_replace('/href\s*=\s*(["\'])\s*javascript:[^"\']*\1/i', 'href="#"', $content);

header('Content-Type: text/html; charset=utf-8');
echo $content;
?>