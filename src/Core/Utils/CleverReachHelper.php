<?php

namespace LEXO\LF\Core\Utils;

/**
 * CleverReach Helper Functions
 *
 * Utility functions for CleverReach API operations.
 *
 * @package LEXO\LF
 * @since 1.0.0
 */
class CleverReachHelper
{
    /**
     * Sanitize description to be alphanumeric only
     * CleverReach API requires alphanumeric descriptions
     *
     * @param string $description Original description
     * @return string Sanitized alphanumeric description
     */
    public static function sanitizeDescription(string $description): string
    {
        // Remove all non-alphanumeric characters (keep spaces and convert to underscores)
        $sanitized = preg_replace('/[^a-zA-Z0-9\s]/', '', $description);

        // Replace multiple spaces with single space
        $sanitized = preg_replace('/\s+/', ' ', $sanitized);

        // Trim whitespace
        $sanitized = trim($sanitized);

        // Replace spaces with underscores for better API compatibility
        $sanitized = str_replace(' ', '_', $sanitized);

        // If empty after sanitization, use a default value
        if (empty($sanitized)) {
            $sanitized = 'Field';
        }

        return $sanitized;
    }
}
