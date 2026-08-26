<?php
// ──────────────────────────────────────────
//  abcMusic — Audio download proxy
//  Forces the MP3 download (cross-origin)
// ──────────────────────────────────────────

$url      = $_GET['url']      ?? '';
$filename = $_GET['filename'] ?? 'my-song.mp3';

// Only accept URLs from this Supabase account
if (!$url || !preg_match('/^https:\/\/baltzukuszagxcgkfrpi\.supabase\.co\/storage\//', $url)) {
    http_response_code(400);
    exit('Invalid URL');
}

// Sanitize the file name
$filename = preg_replace('/[^\w\-. ]/u', '_', $filename);
if (!preg_match('/\.mp3$/i', $filename)) {
    $filename .= '.mp3';
}

// Fetch the file from Supabase
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_USERAGENT      => 'abcMusic/1.0',
    CURLOPT_TIMEOUT        => 30,
]);
$data     = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($data === false || $httpCode !== 200) {
    http_response_code(502);
    exit('Failed to fetch file');
}

header('Content-Type: audio/mpeg');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($data));
header('Cache-Control: no-store');
echo $data;
