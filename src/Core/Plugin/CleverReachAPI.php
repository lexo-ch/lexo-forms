<?php

namespace LEXO\LF\Core\Plugin;

use const LEXO\LF\{
    API_BASE_URL,
    API_TIMEOUT
};

use LEXO\LF\Core\Utils\Logger;
use LEXO\LF\Core\Utils\CleverReachHelper;

class CleverReachAPI {

    protected string $apiUrl;
    protected string $token;
    protected array $headers;
    protected bool $debug = false;

    public function __construct(string $token = '')
    {
        $this->apiUrl = API_BASE_URL;
        $this->token = $token;
        $this->headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->token
        ];
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
        $this->headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->token
        ];
    }

    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->apiUrl . $endpoint;
        $method = strtoupper($method);

        // Convert headers array to associative array for wp_remote_request
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->token
        ];

        // Prepare request arguments
        $args = [
            'method' => $method,
            'timeout' => API_TIMEOUT,
            'headers' => $headers,
            'sslverify' => true,
        ];

        // Handle different HTTP methods
        if (in_array($method, ['POST', 'PUT']) && !empty($data)) {
            $args['body'] = json_encode($data);
        } elseif ($method === 'GET' && !empty($data)) {
            $url = add_query_arg($data, $url);
        }

        // Make the request using WordPress HTTP API
        $response = wp_remote_request($url, $args);

        // Check for WordPress errors
        if (is_wp_error($response)) {
            $errorMessage = $response->get_error_message();
            $this->logError("HTTP Error", $errorMessage, $method, $url);
            throw new \Exception("HTTP Error: " . $errorMessage);
        }

        // Get response code and body
        $httpCode = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $decodedResponse = json_decode($body, true);

        if ($httpCode >= 400) {
            $errorMessage = $decodedResponse['error']['message'] ?? "HTTP Error {$httpCode}";

            if ($httpCode >= 500 || $httpCode === 401 || $httpCode === 403 || $httpCode === 400 || $httpCode === 422) {
                $this->logError("API Error", $errorMessage, $method, $url);
            }

            throw new \Exception("API Error: {$errorMessage}");
        }

        return [
            'data' => $decodedResponse,
            'http_code' => $httpCode,
            'success' => $httpCode >= 200 && $httpCode < 300
        ];
    }

    public function getGroups(): array
    {
        return $this->makeRequest('GET', '/groups');
    }

    public function getGroup(int $groupId): array
    {
        return $this->makeRequest('GET', "/groups/{$groupId}");
    }

    public function createGroup(array $groupData): array
    {
        // Ensure description is sanitized if provided, or use name
        if (!isset($groupData['description']) && isset($groupData['name'])) {
            $groupData['description'] = CleverReachHelper::sanitizeDescription($groupData['name']);
        } elseif (isset($groupData['description'])) {
            $groupData['description'] = CleverReachHelper::sanitizeDescription($groupData['description']);
        }

        return $this->makeRequest('POST', '/groups', $groupData);
    }

    public function updateGroup(int $groupId, array $groupData): array
    {
        return $this->makeRequest('PUT', "/groups/{$groupId}", $groupData);
    }

    public function deleteGroup(int $groupId): array
    {
        return $this->makeRequest('DELETE', "/groups/{$groupId}");
    }

    public function getRecipients(int $groupId, array $params = []): array
    {
        return $this->makeRequest('GET', "/groups/{$groupId}/receivers", $params);
    }

    public function getRecipient(int $groupId, string $email): array
    {
        return $this->makeRequest('GET', "/groups/{$groupId}/receivers/{$email}");
    }

    public function addRecipient(int $groupId, array $recipientData): array
    {
        $defaults = [
            'registered' => time(),
        ];

        $recipientData = array_merge($defaults, $recipientData);

        return $this->makeRequest('POST', "/groups/{$groupId}/receivers", $recipientData);
    }

    public function addRecipients(int $groupId, array $recipientsData): array
    {
        return $this->makeRequest('POST', "/groups/{$groupId}/receivers/insert", $recipientsData);
    }

    public function updateRecipient(int $groupId, string $email, array $recipientData): array
    {
        return $this->makeRequest('PUT', "/groups/{$groupId}/receivers/{$email}", $recipientData);
    }

    public function deleteRecipient(int $groupId, string $email): array
    {
        return $this->makeRequest('DELETE', "/groups/{$groupId}/receivers/{$email}");
    }

    public function setRecipientInactive(int $groupId, string $email): array
    {
        return $this->makeRequest('DELETE', "/groups/{$groupId}/receivers/{$email}/deactivate");
    }

    public function setRecipientActive(int $groupId, string $email): array
    {
        return $this->makeRequest('POST', "/groups/{$groupId}/receivers/{$email}/activate");
    }

    public function getForms(): array
    {
        return $this->makeRequest('GET', '/forms');
    }

    public function getForm(int $formId): array
    {
        return $this->makeRequest('GET', "/forms/{$formId}");
    }

    public function createFormFromTemplate(int $groupId, string $name, string $type = 'DOI', ?string $description = null): array
    {
        $data = ['name' => $name];

        // Add sanitized description if provided, otherwise use sanitized name
        if ($description !== null) {
            $data['description'] = CleverReachHelper::sanitizeDescription($description);
        } else {
            $data['description'] = CleverReachHelper::sanitizeDescription($name);
        }

        return $this->makeRequest('POST', "/forms.json/{$groupId}/createfromtemplate/{$type}", $data);
    }

    public function sendDoubleOptInEmail(int $groupId, string $email, int $formId): array
    {
        $data = [
            'email' => $email,
            'groups_id' => $groupId,
            'doidata' => [
                'user_ip' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '',
                'referer' => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : '',
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : ''
            ]
        ];

        return $this->makeRequest('POST', "/forms/{$formId}/send/activate", $data);
    }

    public function getMailings(array $params = []): array
    {
        return $this->makeRequest('GET', '/mailings', $params);
    }

    public function getMailing(int $mailingId): array
    {
        return $this->makeRequest('GET', "/mailings/{$mailingId}");
    }

    public function createMailing(array $mailingData): array
    {
        return $this->makeRequest('POST', '/mailings', $mailingData);
    }

    public function sendMailing(int $mailingId): array
    {
        return $this->makeRequest('POST', "/mailings/{$mailingId}/send");
    }

    public function getAttributes(): array
    {
        return $this->makeRequest('GET', '/attributes');
    }

    public function createAttribute(array $attributeData): array
    {
        return $this->makeRequest('POST', '/attributes', $attributeData);
    }

    public function createGroupAttribute(int $groupId, array $attributeData): array
    {
        return $this->makeRequest('POST', "/groups/{$groupId}/attributes", $attributeData);
    }

    public function getGroupAttributes(int $groupId): array
    {
        return $this->makeRequest('GET', "/groups/{$groupId}/attributes");
    }

    public function ensureAttribute(string $name, string $type = 'text', string $description = '', bool $global = true, ?int $groupId = null): array
    {
        try {
            if ($groupId && !$global) {
                $attributes = $this->getGroupAttributes($groupId);

                if ($attributes['success'] && $attributes['http_code'] === 200) {
                    foreach ($attributes['data'] as $attr) {
                        if ($attr['name'] === $name) {
                            return ['exists' => true, 'created' => false, 'attribute' => $attr];
                        }
                    }
                }

                $sanitizedDescription = CleverReachHelper::sanitizeDescription($description ?: ucfirst($name));
                $attributeData = [
                    'name' => $name,
                    'type' => $type,
                    'description' => $sanitizedDescription
                ];

                $result = $this->createGroupAttribute($groupId, $attributeData);
            } else {
                $attributes = $this->getAttributes();

                if ($attributes['success'] && $attributes['http_code'] === 200) {
                    foreach ($attributes['data'] as $attr) {
                        if ($attr['name'] === $name) {
                            return ['exists' => true, 'created' => false, 'attribute' => $attr];
                        }
                    }
                }

                $sanitizedDescription = CleverReachHelper::sanitizeDescription($description ?: ucfirst($name));
                $attributeData = [
                    'name' => $name,
                    'type' => $type,
                    'description' => $sanitizedDescription,
                    'global' => $global
                ];

                $result = $this->createAttribute($attributeData);
            }

            if ($result['success'] && $result['http_code'] >= 200 && $result['http_code'] < 300) {
                return ['exists' => false, 'created' => true, 'attribute' => $result['data'] ?? null];
            } else {
                return ['exists' => false, 'created' => false, 'error' => 'Failed to create attribute'];
            }

        } catch (\Exception $e) {
            Logger::error('Failed to ensure attribute: ' . $e->getMessage(), Logger::CATEGORY_API);
            return ['exists' => false, 'created' => false, 'error' => $e->getMessage()];
        }
    }


    public function getReports(int $mailingId): array
    {
        return $this->makeRequest('GET', "/reports/{$mailingId}");
    }

    public function testConnection(): bool
    {
        try {
            $result = $this->makeRequest('GET', '/debug/ping');
            return $result['success'];
        } catch (\Exception) {
            return false;
        }
    }

    private function logError(string $errorType, string $message, string $method, string $url): void
    {
        $endpoint = basename(parse_url($url, PHP_URL_PATH));
        Logger::apiError("{$errorType}: {$message}", $endpoint, $method);
    }
}