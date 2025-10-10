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
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\AuthenticationException;
use RunAsRoot\AgenticCommerceProtocol\Logger\Logger;

/**
 * Validates HMAC SHA256 request signatures
 */
class SignatureValidator
{
    private const CONFIG_PATH_SIGNATURE_SECRET = 'agentic_commerce/security/signature_secret';
    private const CONFIG_PATH_SIGNATURE_ENABLED = 'agentic_commerce/security/signature_validation_enabled';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly RequestInterface $request,
        private readonly Logger $logger
    ) {
    }

    /**
     * Validate request signature
     *
     * @param string $signature
     * @throws AuthenticationException
     */
    public function validate(string $signature): void
    {
        // Check if signature validation is enabled
        if (!$this->isEnabled()) {
            return;
        }

        $secret = $this->getSecret();
        if (empty($secret)) {
            $this->logger->error('Signature secret not configured');
            throw new AuthenticationException(__('Signature validation is enabled but secret is not configured'));
        }

        // Compute expected signature
        $payload = $this->buildPayload();
        $expectedSignature = $this->computeSignature($payload, $secret);

        // Compare signatures (timing-safe comparison)
        if (!hash_equals($expectedSignature, $signature)) {
            $this->logger->error('Signature mismatch');
            throw new AuthenticationException(__('Invalid request signature'));
        }

        $this->logger->info('Signature validated successfully');
    }

    /**
     * Build payload for signature computation
     *
     * @return string
     */
    private function buildPayload(): string
    {
        // Payload format: METHOD\nURI\nTIMESTAMP\nBODY
        $method = $this->request->getMethod();
        $uri = $this->request->getRequestUri();
        $timestamp = $this->request->getHeader('Timestamp') ?? '';
        $body = $this->request->getContent();

        return "{$method}\n{$uri}\n{$timestamp}\n{$body}";
    }

    /**
     * Compute HMAC SHA256 signature
     *
     * @param string $payload
     * @param string $secret
     * @return string
     */
    private function computeSignature(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Check if signature validation is enabled
     *
     * @return bool
     */
    private function isEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::CONFIG_PATH_SIGNATURE_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get signature secret from configuration
     *
     * @return string|null
     */
    private function getSecret(): ?string
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_SIGNATURE_SECRET,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
