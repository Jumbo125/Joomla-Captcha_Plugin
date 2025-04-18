<?php

namespace Joomla\Plugin\System\Baohoneypotar;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;

\defined('_JEXEC') or die;

class Plugin extends CMSPlugin
{
    public function onAfterInitialise(): void
    {
        $app = Factory::getApplication();

        // Nur im Frontend prüfen
        if (!$app->isClient('site')) {
            return;
        }

        // Nur POST-Anfragen prüfen
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        // Bestimmte AJAX-/System-Requests ausschließen (z. B. YOOtheme, Medien, com_ajax)
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if (
            str_contains($requestUri, 'yootheme') ||              // PageBuilder
            str_contains($requestUri, 'option=com_ajax') ||       // Joomla Ajax
            str_contains($requestUri, 'option=com_media') ||      // Medienverwaltung
            str_contains($requestUri, 'task=media.') ||           // Task-Aufrufe
            str_contains($requestUri, 'customizer') ||            // YOOtheme Customizer
            $app->input->get('format') === 'json' ||              // JSON-Ausgaben
            str_contains($userAgent, 'YOOtheme')                  // Falls ihr Customizer sich ausweist
        ) {
            return;
        }

        $input  = $app->input;
        $post   = $input->post;
        $debug  = (int) $this->params->get('debug', 0);
        $secret = $this->params->get('secret');
        $praefix = trim($this->params->get('secret_praefix', 'hp'), '_') . '_';

        if (empty($secret)) {
            if ($debug) {
                file_put_contents(JPATH_SITE . '/honeypot-debug.txt', "⚠️ Kein Secret verfügbar\n", FILE_APPEND);
            }
            return;
        }

        // 🔍 Dynamisches Honeypot-Feld suchen
        $honeypotField = null;

        foreach ($post->getArray() as $key => $value) {
            if (str_starts_with($key, $praefix) && $key !== ($praefix . 'token') && $key !== ($praefix . 'token_time')) {
                $honeypotField = $key;
                break;
            }
        }

        if (!$honeypotField) {
            if ($debug) {
                file_put_contents(JPATH_SITE . '/honeypot-debug.txt', "⚠️ Kein Honeypot-Feld gefunden\n", FILE_APPEND);
            }
            $this->blockRequest('Bot erkannt – Honeypot-Feld fehlt (JS vermutlich deaktiviert)');
        }

        $honeypotValue  = $input->getString($honeypotField, '');
        $tokenField     = $honeypotField . 'token';
        $token          = $input->getString($tokenField, '');
        $timestampField = $honeypotField . '_token_time';
        $timestamp      = $input->getInt($timestampField, 0);

        $expected = hash('sha256', $honeypotField . $secret);
        $now = time();

        // 🪵 Debug Logging
        if ($debug) {
            $log = "🧪 Honeypot Prüfung\n";
            $log .= "Feld: $honeypotField\n";
            $log .= "Wert: $honeypotValue\n";
            $log .= "Token: $token\n";
            $log .= "Erwartet: $expected\n";
            $log .= "Zeit: " . ($now - $timestamp) . " Sek.\n";
            file_put_contents(JPATH_SITE . '/honeypot-debug.txt', $log, FILE_APPEND);
        }

        // ❌ Fallen auslösen
        if (!empty($honeypotValue)) {
            $this->blockRequest('Bot erkannt – Honeypot-Feld wurde ausgefüllt');
        }

        if ($token !== $expected) {
            $this->blockRequest('Bot erkannt – Token ungültig');
        }

        if ($timestamp > 0 && ($now - $timestamp) < 3) {
            $this->blockRequest('Bot erkannt – Formular zu schnell abgeschickt');
        }
    }

    private function blockRequest(string $message): void
    {
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: application/json');

        echo json_encode([
            'success' => false,
            'message' => $message
        ]);

        if ((int) $this->params->get('debug', 0)) {
            file_put_contents(JPATH_SITE . '/honeypot-debug.txt', "🚫 Blockiert: $message\n", FILE_APPEND);
        }

        exit;
    }

    public function onBeforeCompileHead()
    {
        $app = Factory::getApplication();

        if (!$app->isClient('site')) {
            return;
        }

        if ((int) $this->params->get('auto_insert', 0) !== 1) {
            return;
        }

        $doc = Factory::getDocument();
        $doc->addScript('/media/plg_baohoneypotar/honeypot-loader.js');
    }

    public function onAjaxBaohoneypotar()
    {
        $secret  = $this->params->get('secret');
        $praefix = trim($this->params->get('secret_praefix', 'hp'), '_') . '_';

        if (empty($secret)) {
            return new JsonResponse(['error' => 'Secret nicht gesetzt'], 400);
        }

        $field = $praefix . bin2hex(random_bytes(5));
        $token = hash('sha256', $field . $secret);

        return [
            'field' => $field,
            'token' => $token
        ];
    }
}
