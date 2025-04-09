<?php

// Dieses Script wird von außen per JS aufgerufen ? liefert JSON mit Feldname + Token

// Joomla initialisieren
header('Content-Type: application/json');

// Konfig aus Datei lesen (absoluter Pfad)
$configFile = __DIR__ . '/secret.txt';

if (!file_exists($configFile)) {
    echo json_encode(['error' => 'Secret-Datei fehlt']);
    exit;
}


$configJson = file_get_contents($configFile);

$config = json_decode($configJson);


// Fallbacks setzen
$praefix = $config->praefix ?? 'hp_';
$secret  = $config->secret ?? '123456';

// Fallback bei fehlendem Secret

if (!$secret) {

    echo json_encode(['error' => 'Secret nicht gesetzt']);

    exit;

}



// Feldname + Token generieren

$field = $praefix . bin2hex(random_bytes(5));

$token = hash('sha256', $field . $secret);



// JSON zurückgeben

echo json_encode([

    'field' => $field,

    'token' => $token

]);

