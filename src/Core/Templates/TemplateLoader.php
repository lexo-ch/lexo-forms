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
    private const PREVIEWS_SUBDIR = 'previews';

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

        $previewsDir = $dir . '/' . self::PREVIEWS_SUBDIR;
        $previewsUrl = trailingslashit(\LEXO\LF\URL) . self::PLUGIN_TEMPLATES_DIR . '/' . self::PREVIEWS_SUBDIR;

        foreach ($files as $file) {
            $template = $this->loadTemplateFile($file, 'plugin', $previewsDir, $previewsUrl);

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

        $previewsDir = $dir . '/' . self::PREVIEWS_SUBDIR;
        $previewsUrl = trailingslashit(get_stylesheet_directory_uri()) . self::THEME_TEMPLATES_DIR . '/' . self::PREVIEWS_SUBDIR;

        foreach ($files as $file) {
            $template = $this->loadTemplateFile($file, 'theme', $previewsDir, $previewsUrl);

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
     * @param string $previewsDir Path to previews directory
     * @param string $previewsUrl URL to previews directory
     * @return array|null
     */
    private function loadTemplateFile(string $file, string $source, string $previewsDir = '', string $previewsUrl = ''): ?array
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

        // Generate unique ID (using source + filename for cross-environment consistency)
        $id = $this->getTemplateHash($file, $source);
        $filename = basename($file, '.php');

        // Find preview image (check for jpg, png, webp)
        $previewUrl = $this->findPreviewImage($filename, $previewsDir, $previewsUrl);

        // Return template with metadata
        return [
            'id' => $id,
            'name' => $data['name'],
            'fields' => $data['fields'],
            'html' => $data['html'],
            'form_preview' => $previewUrl,
            'source' => $source,
            'file' => $file,
            'filename' => $filename,
            'visitor_email_variants' => $data['visitor_email_variants'] ?? null,
        ];
    }

    /**
     * Find preview image for template
     *
     * @param string $filename Template filename (without .php)
     * @param string $previewsDir Path to previews directory
     * @param string $previewsUrl URL to previews directory
     * @return string|null Preview image URL or null if not found
     */
    private function findPreviewImage(string $filename, string $previewsDir, string $previewsUrl): ?string
    {
        if (empty($previewsDir) || !is_dir($previewsDir)) {
            return null;
        }

        $extensions = ['webp', 'png', 'jpg', 'jpeg', 'svg'];

        foreach ($extensions as $ext) {
            $imagePath = $previewsDir . '/' . $filename . '.' . $ext;
            if (file_exists($imagePath)) {
                return trailingslashit($previewsUrl) . $filename . '.' . $ext;
            }
        }

        return null;
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
     * Uses source + filename to ensure consistent IDs across different environments.
     * This allows database exports/imports between production and local without
     * losing template selections.
     *
     * @param string $file Template file path
     * @param string $source 'plugin' or 'theme'
     * @return string
     */
    private function getTemplateHash(string $file, string $source): string
    {
        // Use source + filename for consistent IDs across environments
        $filename = basename($file);
        return md5($source . ':' . $filename);
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
