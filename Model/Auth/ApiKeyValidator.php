<?php
/**
 * @author    run_as_root GmbH <info@run-as-root.sh>
 * @copyright 2025 run_as_root GmbH
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model\Auth;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\AuthenticationException;

/**
 * Validates API key from Authorization header
 */
class ApiKeyValidator
{
    private const CONFIG_PATH_API_KEY = 'acp/general/api_key';
    private const CONFIG_PATH_ENABLED = 'acp/general/enabled';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Validate Bearer token from Authorization header
     */
    public function validate(?string $authorizationHeader): bool
    {
        // Check if module is enabled
        if (!$this->isEnabled()) {
            throw new AuthenticationException(__('ACP module is disabled'));
        }

        // Check if API key is configured
        $configuredApiKey = $this->getConfiguredApiKey();
        if (empty($configuredApiKey)) {
            throw new AuthenticationException(__('ACP API key is not configured'));
        }

        // Extract Bearer token
        $providedApiKey = $this->extractBearerToken($authorizationHeader);
        if (empty($providedApiKey)) {
            throw new AuthenticationException(__('Authorization header missing or invalid'));
        }

        // Constant-time comparison to prevent timing attacks
        if (!hash_equals($configuredApiKey, $providedApiKey)) {
            throw new AuthenticationException(__('Invalid API key'));
        }

        return true;
    }

    /**
     * Extract Bearer token from Authorization header
     */
    private function extractBearerToken(?string $authorizationHeader): ?string
    {
        if (empty($authorizationHeader)) {
            return null;
        }

        // Format: "Bearer <token>"
        if (!preg_match('/Bearer\s+(.*)$/i', $authorizationHeader, $matches)) {
            return null;
        }

        return $matches[1] ?? null;
    }

    /**
     * Get configured API key from system config
     */
    private function getConfiguredApiKey(): ?string
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_API_KEY);
    }

    /**
     * Check if module is enabled
     */
    private function isEnabled(): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(self::CONFIG_PATH_ENABLED);
    }
}
