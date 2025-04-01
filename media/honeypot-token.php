<?php

// Dieses Script wird von außen per JS aufgerufen ? liefert JSON mit Feldname + Token

// Joomla initialisieren

$configJson = file_get_contents("secret.txt");

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

