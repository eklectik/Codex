<?php
require __DIR__ . '/../bootstrap.php';

$config = require __DIR__ . '/../config.php';

$pdo = db();

$targetUrl = $config['data_source_url'];
$timeout = (int) $config['download_timeout'];
$storageDir = __DIR__ . '/../storage';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0777, true);
}
$tmpZip = $storageDir . '/prix-carburants.zip';
$tmpXml = $storageDir . '/prix-carburants.xml';

$ch = curl_init($targetUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => $timeout,
]);
$data = curl_exec($ch);
if ($data === false) {
    fwrite(STDERR, "Download failed: " . curl_error($ch) . PHP_EOL);
    exit(1);
}
$status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

if ($status >= 400) {
    fwrite(STDERR, "Unexpected HTTP status: {$status}" . PHP_EOL);
    exit(1);
}

file_put_contents($tmpZip, $data);

$zip = new ZipArchive();
if ($zip->open($tmpZip) !== true) {
    fwrite(STDERR, "Unable to open zip archive" . PHP_EOL);
    exit(1);
}

if ($zip->numFiles < 1) {
    fwrite(STDERR, "Zip archive is empty" . PHP_EOL);
    $zip->close();
    exit(1);
}

$xmlName = $zip->getNameIndex(0);
$zip->extractTo($storageDir, [$xmlName]);
$zip->close();

$extractedPath = $storageDir . '/' . $xmlName;
if (file_exists($tmpXml)) {
    unlink($tmpXml);
}
rename($extractedPath, $tmpXml);

$xml = simplexml_load_file($tmpXml);
if (!$xml) {
    fwrite(STDERR, "Unable to parse XML dataset" . PHP_EOL);
    exit(1);
}

$pdo->beginTransaction();
$pdo->exec('DELETE FROM fuels');
$pdo->exec('DELETE FROM stations');

$insertStation = $pdo->prepare('INSERT INTO stations (id, name, address, postal_code, city, latitude, longitude, last_updated) VALUES (:id, :name, :address, :postal_code, :city, :latitude, :longitude, :last_updated)');
$insertFuel = $pdo->prepare('INSERT INTO fuels (station_id, fuel_code, fuel_name, price, last_update) VALUES (:station_id, :fuel_code, :fuel_name, :price, :last_update)');

$stationCount = 0;
$fuelCount = 0;

foreach ($xml->pdv as $station) {
    $stationId = (string) $station['id'];
    $latitude = ((float) $station['latitude']) / 100000;
    $longitude = ((float) $station['longitude']) / 100000;
    $address = trim((string) $station->adresse);
    $city = trim((string) $station->ville);
    $postalCode = trim((string) $station['cp']);
    $name = trim((string) ($station->nom ?? $city));
    if ($name === '') {
        $name = $city !== '' ? $city : 'Station ' . $stationId;
    }

    $lastUpdated = null;
    if (isset($station->prix)) {
        $lastUpdated = (string) $station->prix[0]['maj'];
    }

    $insertStation->execute([
        'id' => $stationId,
        'name' => $name,
        'address' => $address,
        'postal_code' => $postalCode,
        'city' => $city,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'last_updated' => $lastUpdated,
    ]);
    $stationCount++;

    foreach ($station->prix as $price) {
        $fuelCount++;
        $insertFuel->execute([
            'station_id' => $stationId,
            'fuel_code' => (string) $price['id'],
            'fuel_name' => (string) $price['nom'],
            'price' => ((float) $price['valeur']) / 1000,
            'last_update' => (string) $price['maj'],
        ]);
    }
}

$pdo->commit();

if (file_exists($tmpZip)) {
    unlink($tmpZip);
}
if (file_exists($tmpXml)) {
    unlink($tmpXml);
}

echo "Stations imported: {$stationCount}" . PHP_EOL;
echo "Fuel entries imported: {$fuelCount}" . PHP_EOL;
