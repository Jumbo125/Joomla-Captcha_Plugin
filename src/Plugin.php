<?php

namespace Joomla\Plugin\System\Baohoneypotar;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;

\defined('_JEXEC') or die;

class Plugin extends CMSPlugin
{
    public function onAfterInitialise(): void
    {
        $app = Factory::getApplication();

        if (!$app->isClient('site') || $_SERVER['REQUEST_METHOD'] !== 'POST') {
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

        $honeypotValue = $input->getString($honeypotField, '');
        $tokenField = $honeypotField . 'token';
        $token = $input->getString($tokenField, '');
        $timestampField = $honeypotField . '_token_time';
        $timestamp = $input->getInt($timestampField, 0);

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
}
