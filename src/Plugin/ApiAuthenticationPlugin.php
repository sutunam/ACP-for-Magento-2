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
use RunAsRoot\AgenticCommerceProtocol\Model\Auth\ApiKeyValidator;

/**
 * Plugin to validate API authentication for ACP endpoints
 */
class ApiAuthenticationPlugin
{
    public function __construct(
        private readonly ApiKeyValidator $apiKeyValidator,
        private readonly RequestInterface $request
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

        // Get Authorization header
        $authHeader = $this->request->getHeader('Authorization');
        
        // Validate API key
        $this->apiKeyValidator->validate($authHeader);
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
