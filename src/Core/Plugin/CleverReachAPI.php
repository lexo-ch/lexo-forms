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

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => API_TIMEOUT,
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);

        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, true);
                if (!empty($data)) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;

            case 'PUT':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                if (!empty($data)) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;

            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;

            case 'GET':
            default:
                if (!empty($data)) {
                    $url .= '?' . http_build_query($data);
                    curl_setopt($curl, CURLOPT_URL, $url);
                }
                break;
        }

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            $this->logError("cURL Error", $error, $method, $url);
            throw new \Exception("cURL Error: " . $error);
        }

        $decodedResponse = json_decode($response, true);

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
            $groupData['description'] = $this->sanitizeDescription($groupData['name']);
        } elseif (isset($groupData['description'])) {
            $groupData['description'] = $this->sanitizeDescription($groupData['description']);
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
            $data['description'] = $this->sanitizeDescription($description);
        } else {
            $data['description'] = $this->sanitizeDescription($name);
        }

        return $this->makeRequest('POST', "/forms.json/{$groupId}/createfromtemplate/{$type}", $data);
    }

    public function sendDoubleOptInEmail(int $groupId, string $email, int $formId): array
    {
        $data = [
            'email' => $email,
            'groups_id' => $groupId,
            'doidata' => [
                'user_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'referer' => $_SERVER['HTTP_REFERER'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
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

                $sanitizedDescription = $this->sanitizeDescription($description ?: ucfirst($name));
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

                $sanitizedDescription = $this->sanitizeDescription($description ?: ucfirst($name));
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

    /**
     * Sanitize description to be alphanumeric only
     * CleverReach API requires alphanumeric descriptions
     *
     * @deprecated Use CleverReachHelper::sanitizeDescription() instead
     * @param string $description Original description
     * @return string Sanitized alphanumeric description
     */
    private function sanitizeDescription(string $description): string
    {
        return CleverReachHelper::sanitizeDescription($description);
    }
}