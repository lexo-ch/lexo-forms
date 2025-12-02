<?php

namespace LEXO\LF\Core\Templates;

use LEXO\LF\Core\Abstracts\Singleton;
use LEXO\LF\Core\Utils\Logger;

/**
 * Template Loader
 *
 * Scans and loads HTML form templates from plugin and theme.
 * Follows LEXO standard - template management.
 */
class TemplateLoader extends Singleton
{
    protected static $instance = null;

    private const PLUGIN_TEMPLATES_DIR = 'template-forms';
    private const THEME_TEMPLATES_DIR = 'template-forms';

    private array $templates = [];
    private bool $scanned = false;

    /**
     * Get all available templates
     *
     * @return array
     */
    public function getAvailableTemplates(): array
    {
        if (!$this->scanned) {
            $this->scanTemplates();
        }

        return $this->templates;
    }

    /**
     * Scan all template directories
     *
     * @return void
     */
    private function scanTemplates(): void
    {
        $this->templates = [];

        // Scan plugin templates
        $pluginTemplates = $this->scanPluginTemplates();

        // Scan theme templates
        $themeTemplates = $this->scanThemeTemplates();

        // Merge templates
        $this->templates = array_merge($pluginTemplates, $themeTemplates);

        $this->scanned = true;
    }

    /**
     * Scan plugin templates directory
     *
     * @return array
     */
    private function scanPluginTemplates(): array
    {
        $templates = [];
        $dir = trailingslashit(\LEXO\LF\PATH) . self::PLUGIN_TEMPLATES_DIR;

        if (!is_dir($dir)) {
            return $templates;
        }

        $files = glob($dir . '/*.php');

        if (empty($files)) {
            return $templates;
        }

        foreach ($files as $file) {
            $template = $this->loadTemplateFile($file, 'plugin');

            if ($template !== null) {
                $templates[$template['id']] = $template;
            }
        }

        return $templates;
    }

    /**
     * Scan theme templates directory
     *
     * @return array
     */
    private function scanThemeTemplates(): array
    {
        $templates = [];
        $dir = trailingslashit(get_stylesheet_directory()) . self::THEME_TEMPLATES_DIR;

        if (!is_dir($dir)) {
            return $templates;
        }

        $files = glob($dir . '/*.php');

        if (empty($files)) {
            return $templates;
        }

        foreach ($files as $file) {
            $template = $this->loadTemplateFile($file, 'theme');

            if ($template !== null) {
                $templates[$template['id']] = $template;
            }
        }

        return $templates;
    }

    /**
     * Load and validate template file
     *
     * @param string $file Template file path
     * @param string $source 'plugin' or 'theme'
     * @return array|null
     */
    private function loadTemplateFile(string $file, string $source): ?array
    {
        if (!file_exists($file)) {
            return null;
        }

        // Include template file
        $data = include $file;

        // Validate template structure
        if (!$this->validateTemplate($data)) {
            Logger::error('Invalid template structure in ' . $file, Logger::CATEGORY_GENERAL);
            return null;
        }

        // Generate unique ID
        $id = $this->getTemplateHash($file);

        // Return template with metadata
        return [
            'id' => $id,
            'name' => $data['name'],
            'fields' => $data['fields'],
            'html' => $data['html'],
            'source' => $source,
            'file' => $file,
            'filename' => basename($file, '.php'),
        ];
    }

    /**
     * Validate template structure
     *
     * @param mixed $data
     * @return bool
     */
    private function validateTemplate($data): bool
    {
        // Must be array
        if (!is_array($data)) {
            return false;
        }

        // Must have required keys
        if (!isset($data['name']) || !isset($data['fields']) || !isset($data['html'])) {
            return false;
        }

        // name must be string OR multilingual array
        if (is_string($data['name'])) {
            // String format: must not be empty
            if (empty($data['name'])) {
                return false;
            }
        } elseif (is_array($data['name'])) {
            // Array format: must have at least one language
            if (empty($data['name'])) {
                return false;
            }
        } else {
            // Invalid format
            return false;
        }

        // fields must be array
        if (!is_array($data['fields'])) {
            return false;
        }

        // html must be string
        if (!is_string($data['html']) || empty($data['html'])) {
            return false;
        }

        // Validate fields structure
        foreach ($data['fields'] as $field) {
            if (!is_array($field)) {
                return false;
            }

            // Each field must have required keys
            if (!isset($field['name']) || !isset($field['type']) || !isset($field['email_label'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate unique hash for template
     *
     * @param string $file Template file path
     * @return string
     */
    private function getTemplateHash(string $file): string
    {
        return md5($file);
    }

    /**
     * Get template by ID
     *
     * @param string $id Template ID (hash)
     * @return array|null
     */
    public function getTemplateById(string $id): ?array
    {
        $templates = $this->getAvailableTemplates();

        return $templates[$id] ?? null;
    }

    /**
     * Clear template cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->templates = [];
        $this->scanned = false;
    }
}
