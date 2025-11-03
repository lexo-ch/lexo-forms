<?php

namespace LEXO\LF\Core\Services;

use LEXO\LF\Core\Abstracts\Singleton;
use LEXO\LF\Core\Templates\TemplateLoader;

/**
 * Template Service
 *
 * Main service for managing HTML form templates.
 * Follows LEXO standard - business logic separation.
 */
class TemplateService extends Singleton
{
    protected static $instance = null;

    private TemplateLoader $loader;

    /**
     * Initialize service
     */
    public function __construct()
    {
        $this->loader = TemplateLoader::getInstance();
    }

    /**
     * Get all available templates
     *
     * @return array
     */
    public function getAllTemplates(): array
    {
        return $this->loader->getAvailableTemplates();
    }

    /**
     * Load template by ID
     *
     * @param string $templateId Template hash ID
     * @return array|null
     */
    public function loadTemplate(string $templateId): ?array
    {
        return $this->loader->getTemplateById($templateId);
    }

    /**
     * Get template fields (CR group attributes)
     *
     * @param string $templateId
     * @return array|null
     */
    public function getTemplateFields(string $templateId): ?array
    {
        $template = $this->loadTemplate($templateId);

        return $template['fields'] ?? null;
    }

    /**
     * Get template HTML
     *
     * @param string $templateId
     * @return string|null
     */
    public function getTemplateHtml(string $templateId): ?string
    {
        $template = $this->loadTemplate($templateId);

        return $template['html'] ?? null;
    }

    /**
     * Get template name
     *
     * @param string $templateId
     * @return string|null
     */
    public function getTemplateName(string $templateId): ?string
    {
        $template = $this->loadTemplate($templateId);

        return $template['name'] ?? null;
    }

    /**
     * Check if template exists
     *
     * @param string $templateId
     * @return bool
     */
    public function templateExists(string $templateId): bool
    {
        return $this->loadTemplate($templateId) !== null;
    }

    /**
     * Refresh template cache
     *
     * @return void
     */
    public function refreshTemplates(): void
    {
        $this->loader->clearCache();
    }
}
