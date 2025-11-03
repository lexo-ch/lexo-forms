<?php
/**
 * LEXO Forms Logger
 *
 * Centralized logging system for the plugin
 *
 * @package LEXO\LF\Core\Utils
 */

namespace LEXO\LF\Core\Utils;

class Logger {

    const LOG_LEVEL_ERROR = 'ERROR';
    const LOG_LEVEL_WARNING = 'WARNING';
    const LOG_LEVEL_DEBUG = 'DEBUG';

    const CATEGORY_API = 'API';
    const CATEGORY_EMAIL = 'EMAIL';
    const CATEGORY_FORM = 'FORM';
    const CATEGORY_SYNC = 'SYNC';
    const CATEGORY_AUTH = 'AUTH';
    const CATEGORY_GENERAL = 'GENERAL';

    private static $instance = null;
    private $plugin_prefix = 'LEXO Forms';
    private $debug_enabled = true;

    private function __construct() {
        // Logger is always enabled, can be controlled via enableDebug() method if needed
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function error($message, $category = self::CATEGORY_GENERAL, $context = []) {
        self::getInstance()->log(self::LOG_LEVEL_ERROR, $message, $category, $context);
    }

    public static function warning($message, $category = self::CATEGORY_GENERAL, $context = []) {
        self::getInstance()->log(self::LOG_LEVEL_WARNING, $message, $category, $context);
    }

    public static function debug($message, $category = self::CATEGORY_GENERAL, $context = []) {
        $instance = self::getInstance();
        if ($instance->debug_enabled) {
            $instance->log(self::LOG_LEVEL_DEBUG, $message, $category, $context);
        }
    }

    public static function apiError($message, $endpoint = '', $method = '', $context = []) {
        $formatted_message = $message;
        if ($endpoint && $method) {
            $formatted_message .= " ({$method} {$endpoint})";
        }
        self::error($formatted_message, self::CATEGORY_API, $context);
    }

    public static function emailError($message, $form_id = null, $context = []) {
        if ($form_id) {
            $message .= " for form ID: {$form_id}";
        }
        self::error($message, self::CATEGORY_EMAIL, $context);
    }

    public static function formError($message, $form_id = null, $context = []) {
        if ($form_id) {
            $message .= " for form ID: {$form_id}";
        }
        self::error($message, self::CATEGORY_FORM, $context);
    }

    public static function syncError($message, $context = []) {
        self::error($message, self::CATEGORY_SYNC, $context);
    }

    public static function authError($message, $context = []) {
        self::error($message, self::CATEGORY_AUTH, $context);
    }

    private function log($level, $message, $category, $context = []) {
        $timestamp = current_time('Y-m-d H:i:s');
        $formatted_message = "[{$timestamp}] {$this->plugin_prefix} [{$level}] [{$category}]: {$message}";

        if (!empty($context)) {
            $formatted_message .= ' | Context: ' . json_encode($context);
        }

        error_log($formatted_message);

        if (function_exists('do_action')) {
            do_action('lexo_lf_log', $level, $category, $message, $context);
        }
    }

    public function enableDebug($enable = true) {
        $this->debug_enabled = $enable;
    }

    public function isDebugEnabled() {
        return $this->debug_enabled;
    }
}