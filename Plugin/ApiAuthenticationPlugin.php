<?php
/**
 * @author    Run_As_Root <hello@run-as-root.sh>
 * @copyright 2025 Run_As_Root
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Webapi\Rest\Request;
use RunAsRoot\AgenticCommerceProtocol\Logger\Logger;
use RunAsRoot\AgenticCommerceProtocol\Model\Auth\ApiKeyValidator;
use RunAsRoot\AgenticCommerceProtocol\Model\Auth\HeaderValidator;
use RunAsRoot\AgenticCommerceProtocol\Model\Auth\IdempotencyManager;
use RunAsRoot\AgenticCommerceProtocol\Model\Auth\SignatureValidator;
use RunAsRoot\AgenticCommerceProtocol\Model\Auth\TimestampValidator;

/**
 * Plugin to validate API authentication for ACP endpoints
 */
class ApiAuthenticationPlugin
{
    public function __construct(
        private readonly ApiKeyValidator $apiKeyValidator,
        private readonly RequestInterface $request,
        private readonly HeaderValidator $headerValidator,
        private readonly IdempotencyManager $idempotencyManager,
        private readonly SignatureValidator $signatureValidator,
        private readonly TimestampValidator $timestampValidator,
        private readonly Logger $logger
    ) {
    }

    /**
     * Validate authentication before processing ACP API requests
     */
    public function beforeDispatch($subject): void
    {
        // Only validate for ACP endpoints
        if (!$this->isAcpEndpoint()) {
            return;
        }

        $this->logger->info('ACP request authentication started', [
            'method' => $this->request->getMethod(),
            'uri' => $this->request->getRequestUri()
        ]);

        // 1. Validate Authorization header (API Key)
        $authHeader = $this->request->getHeader('Authorization');
        $this->apiKeyValidator->validate($authHeader);

        // 2. Validate all required headers
        $headers = $this->headerValidator->validate();

        // 3. Get and validate idempotency key
        $idempotencyKey = $this->headerValidator->getIdempotencyKey();

        // Validate idempotency key format
        if (!$this->idempotencyManager->validateFormat($idempotencyKey)) {
            throw new AuthenticationException(__('Invalid idempotency key format'));
        }

        // Check if request with this idempotency key was already processed
        // Note: Idempotency check happens but cached response handling is complex
        // For now, we just validate the key exists and log. Full implementation
        // would require response interception which is beyond this scope
        if ($this->idempotencyManager->exists($idempotencyKey)) {
            $this->logger->warning('Duplicate idempotency key detected', [
                'idempotency_key' => $idempotencyKey
            ]);
        }

        // 4. Validate timestamp (prevent replay attacks)
        $timestamp = $this->headerValidator->getTimestamp();
        $this->timestampValidator->validate($timestamp);

        // 5. Validate signature (if provided)
        $signature = $this->headerValidator->getSignature();
        if ($signature) {
            $this->signatureValidator->validate($signature);
        }

        // 6. Log request ID for tracking
        $requestId = $this->headerValidator->getRequestId();
        $this->logger->info('Request validated successfully', [
            'request_id' => $requestId,
            'idempotency_key' => $idempotencyKey
        ]);
    }

    /**
     * Check if current request is for ACP endpoints
     */
    private function isAcpEndpoint(): bool
    {
        $pathInfo = $this->request->getPathInfo();
        return str_contains($pathInfo, '/V1/acp/');
    }
}
