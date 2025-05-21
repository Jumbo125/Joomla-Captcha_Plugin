<?php

namespace Joomla\Plugin\System\Baohoneypotar;

\defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\CMS\Language\Text;

class Plugin extends CMSPlugin

{
    public function onAfterInitialise(): void
    {
        $app = Factory::getApplication();

        if (!$app->isClient('site')) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $exceptionString = $this->params->get('honeypot_exceptions', '');
        $exceptions = array_map('trim', explode(',', $exceptionString));
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';

        foreach ($exceptions as $ex) {
            if (!empty($ex) && str_contains($requestUri, $ex)) {
                if ((int) $this->params->get('debug', 0)) {
                    file_put_contents(JPATH_SITE . '/honeypot-debug.txt', sprintf(Text::_('PLG_SYSTEM_BAOHONEYPOTAR_SKIP_EXCEPTION'), $ex, $requestUri) . "\n", FILE_APPEND);
                }
                return;
            }
        }

        $skipKeysString = $this->params->get('honeypot_skip_keys', '');
        $skipKeys = array_map('trim', explode(',', $skipKeysString));

        foreach ($_POST as $key => $value) {
            if (in_array($key, $skipKeys, true)) {
                if ((int) $this->params->get('debug', 0)) {
                    file_put_contents(JPATH_SITE . '/honeypot-debug.txt', sprintf(Text::_('PLG_SYSTEM_BAOHONEYPOTAR_SKIP_POST_KEY'), $key) . "\n", FILE_APPEND);
                }
                return;
            }
        }

        $input  = $app->input;
        $post   = $input->post;
        $debug  = (int) $this->params->get('debug', 0);
        $secret = $this->params->get('secret');
        $praefix = trim($this->params->get('secret_praefix', 'hp'), '_') . '_';

        if (empty($secret)) {
            if ($debug) {
                file_put_contents(JPATH_SITE . '/honeypot-debug.txt', Text::_('PLG_SYSTEM_BAOHONEYPOTAR_NO_SECRET') . "\n", FILE_APPEND);
            }

            return;
        }

        $honeypotField = null;

        foreach ($post->getArray() as $key => $value) {
            if (str_starts_with($key, $praefix) && $key !== ($praefix . 'token') && $key !== ($praefix . 'token_time')) {
                $honeypotField = $key;
                break;
            }
        }

        if (!$honeypotField) {
            if ($debug) {
                file_put_contents(JPATH_SITE . '/honeypot-debug.txt', Text::_('PLG_SYSTEM_BAOHONEYPOTAR_NO_HONEYPOT_FIELD') . "\n", FILE_APPEND);
            }
            $this->blockRequest(Text::_('PLG_SYSTEM_BAOHONEYPOTAR_CHECK_FAILED_NO_FIELD'));
        }

        $honeypotValue  = $input->getString($honeypotField, '');
        $tokenField     = $honeypotField . 'token';
        $token          = $input->getString($tokenField, '');
        $timestampField = $honeypotField . '_token_time';
        $timestamp      = $input->getInt($timestampField, 0);
        $expected = hash('sha256', $honeypotField . $secret);
        $now = time();

        if ($debug) {
            $log = "🧪 Honeypot Prüfung\n";
            $log .= "Feld: $honeypotField\n";
            $log .= "Wert: $honeypotValue\n";
            $log .= "Token: $token\n";
            $log .= "Erwartet: $expected\n";
            $log .= "Zeit: " . ($now - $timestamp) . " Sek.\n";
            $log .= date('Y-m-d H:i:s') . ' - ' . $_SERVER['REQUEST_METHOD'] . ' ' . ($_SERVER['REQUEST_URI'] ?? '') . "\n";
            file_put_contents(JPATH_SITE . '/honeypot-debug.txt', $log, FILE_APPEND);
        }

        if (!empty($honeypotValue)) {
            $this->blockRequest(Text::_('PLG_SYSTEM_BAOHONEYPOTAR_CHECK_FAILED_VALUE'));
        }

        if ($token !== $expected) {
            $this->blockRequest(Text::_('PLG_SYSTEM_BAOHONEYPOTAR_CHECK_FAILED_TOKEN'));
        }

        if ($timestamp > 0 && ($now - $timestamp) < 3) {
            $this->blockRequest(Text::_('PLG_SYSTEM_BAOHONEYPOTAR_CHECK_FAILED_TIME'));
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
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $logParts = [];
            $postArray = $_POST;
            foreach ($postArray as $key => $value) {
                $logParts[] = $key . ' -> ' . (is_array($value) ? '[array]' : $value);
            }
            $postLog = implode(', ', $logParts);
            file_put_contents(JPATH_SITE . '/honeypot-debug.txt', "🚫 Blockiert: $message - " . $requestUri ." - " . $userAgent . " - " . $postLog . "\n", FILE_APPEND);
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

        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->useScript('plg_system_baohoneypotar.honeypot-loader');
    }

    public function onAjaxBaohoneypotar()
    {
        $secret  = $this->params->get('secret');
        $praefix = trim($this->params->get('secret_praefix', 'hp'), '_') . '_';

        if (empty($secret)) {
            return new JsonResponse(['error' => Text::_('PLG_SYSTEM_BAOHONEYPOTAR_NO_SECRET')], 400);
        }

        $field = $praefix . bin2hex(random_bytes(5));
        $token = hash('sha256', $field . $secret);

        return [
            'field' => $field,
            'token' => $token
        ];
    }
}
