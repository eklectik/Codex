<?php
require __DIR__ . '/../../bootstrap.php';

$config = require __DIR__ . '/../../config.php';

$lat = isset($_GET['lat']) ? (float) $_GET['lat'] : null;
$lng = isset($_GET['lng']) ? (float) $_GET['lng'] : null;
$radius = isset($_GET['radius']) ? (float) $_GET['radius'] : 10;
$fuel = trim($_GET['fuel'] ?? '');

if ($lat === null || $lng === null) {
    http_response_code(400);
    respondJson(['error' => 'Latitude et longitude sont requises.']);
    exit;
}

$radius = max(1, min($radius, $config['max_radius_km']));

$latDelta = $radius / 111;
$lngDelta = $radius / (111 * max(cos(deg2rad($lat)), 0.1));

$params = [
    'lat_min' => $lat - $latDelta,
    'lat_max' => $lat + $latDelta,
    'lng_min' => $lng - $lngDelta,
    'lng_max' => $lng + $lngDelta,
];

$sql = 'SELECT s.id, s.name, s.address, s.city, s.postal_code, s.latitude, s.longitude, s.last_updated,
               f.fuel_code, f.fuel_name, f.price, f.last_update
        FROM stations s
        JOIN fuels f ON f.station_id = s.id
        WHERE s.latitude BETWEEN :lat_min AND :lat_max
          AND s.longitude BETWEEN :lng_min AND :lng_max';

if ($fuel !== '') {
    $sql .= ' AND (LOWER(f.fuel_name) = LOWER(:fuel_name) OR f.fuel_code = :fuel_code)';
    $params['fuel_name'] = $fuel;
    $params['fuel_code'] = $fuel;
}

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stations = [];
$cheapest = [];
$fuelTypes = [];

foreach ($rows as $row) {
    $stationId = $row['id'];
    $distance = haversine($lat, $lng, (float) $row['latitude'], (float) $row['longitude']);
    if ($distance > $radius) {
        continue;
    }
    if (!isset($stations[$stationId])) {
        $stations[$stationId] = [
            'id' => $stationId,
            'name' => $row['name'],
            'address' => $row['address'],
            'postal_code' => $row['postal_code'],
            'city' => $row['city'],
            'latitude' => (float) $row['latitude'],
            'longitude' => (float) $row['longitude'],
            'last_updated' => $row['last_updated'],
            'distance' => $distance,
            'fuels' => [],
        ];
    }
    $fuelEntry = [
        'code' => $row['fuel_code'],
        'name' => $row['fuel_name'],
        'price' => (float) $row['price'],
        'last_update' => $row['last_update'],
    ];
    $stations[$stationId]['fuels'][] = $fuelEntry;
    $fuelTypes[$row['fuel_code']] = $row['fuel_name'];

    $fuelKey = $row['fuel_code'];
    if (!isset($cheapest[$fuelKey]) || $row['price'] < $cheapest[$fuelKey]['price']) {
        $cheapest[$fuelKey] = [
            'fuel_code' => $row['fuel_code'],
            'fuel_name' => $row['fuel_name'],
            'price' => (float) $row['price'],
            'last_update' => $row['last_update'],
            'station_id' => $stationId,
            'station_name' => $row['name'],
            'latitude' => (float) $row['latitude'],
            'longitude' => (float) $row['longitude'],
            'distance' => $distance,
            'address' => $row['address'],
            'city' => $row['city'],
        ];
    }
}

$stations = array_values($stations);
usort($stations, fn($a, $b) => $a['distance'] <=> $b['distance']);
foreach ($stations as &$station) {
    usort($station['fuels'], fn($a, $b) => $a['price'] <=> $b['price']);
}

$cheapestList = array_values($cheapest);
usort($cheapestList, fn($a, $b) => $a['price'] <=> $b['price']);

ksort($fuelTypes);
$fuelOptions = [];
foreach ($fuelTypes as $code => $name) {
    $fuelOptions[] = ['code' => $code, 'name' => $name];
}

respondJson([
    'filters' => [
        'radius' => $radius,
    ],
    'fuel_types' => $fuelOptions,
    'stations' => $stations,
    'cheapest' => $cheapestList,
]);

function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $earthRadius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}
