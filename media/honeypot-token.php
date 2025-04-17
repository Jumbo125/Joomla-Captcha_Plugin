<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Dieses Script wird per JavaScript geladen und liefert JSON (Feldname + Token)

define('_JEXEC', 1);
define('JPATH_BASE', dirname(__DIR__, 2)); // geht 2x zurück vom media-Ordner ins Joomla-Root

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

header('Content-Type: application/json');

// Plugin-Parameter laden
$app = Factory::getApplication('site');
PluginHelper::importPlugin('system', 'baohoneypotar');
$plugin = $app->getPlugin('system', 'baohoneypotar');

if (!$plugin) {
    echo json_encode(['error' => 'Plugin nicht geladen']);
    exit;
}

$params = new Registry($plugin->params);
$secret  = $params->get('secret', '');
$praefix = trim($params->get('secret_praefix', 'hp'), '_') . '_';

if (empty($secret)) {
    echo json_encode(['error' => 'Secret nicht gesetzt']);
    exit;
}

// Feldname und Token erzeugen
$field = $praefix . bin2hex(random_bytes(5));
$token = hash('sha256', $field . $secret);

echo json_encode([
    'field' => $field,
    'token' => $token
]);
