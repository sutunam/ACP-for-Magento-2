<?php
/**
 * @author    run_as_root GmbH <info@run-as-root.sh>
 * @copyright 2025 run_as_root GmbH
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model\Auth;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;
use RunAsRoot\AgenticCommerceProtocol\Logger\Logger;

/**
 * Validates ACP required headers
 */
class HeaderValidator
{
    private const REQUIRED_HEADERS = [
        'Idempotency-Key',
        'Request-Id',
        'Timestamp',
    ];

    private const OPTIONAL_HEADERS = [
        'Signature',
        'API-Version',
    ];

    public function __construct(
        private readonly RequestInterface $request,
        private readonly Logger $logger
    ) {
    }

    /**
     * Validate all required headers
     *
     * @throws AuthenticationException
     */
    public function validate(): array
    {
        $headers = [];

        // Validate required headers
        foreach (self::REQUIRED_HEADERS as $headerName) {
            $value = $this->getHeader($headerName);
            if (empty($value)) {
                $this->logger->error("Missing required header: {$headerName}");
                throw new AuthenticationException(__("Missing required header: %1", $headerName));
            }
            $headers[$headerName] = $value;
        }

        // Get optional headers
        foreach (self::OPTIONAL_HEADERS as $headerName) {
            $value = $this->getHeader($headerName);
            if (!empty($value)) {
                $headers[$headerName] = $value;
            }
        }

        return $headers;
    }

    /**
     * Get header value (case-insensitive)
     *
     * @param string $headerName
     * @return string|null
     */
    private function getHeader(string $headerName): ?string
    {
        // Try hyphenated version
        $value = $this->request->getHeader($headerName);
        if ($value) {
            return $value;
        }

        // Try uppercase with underscores (HTTP_X_HEADER format)
        $upperName = 'HTTP_' . str_replace('-', '_', strtoupper($headerName));
        $value = $this->request->getServer($upperName);
        if ($value) {
            return $value;
        }

        // Try direct server variable
        $directName = str_replace('-', '_', strtoupper($headerName));
        return $this->request->getServer($directName);
    }

    /**
     * Get idempotency key from headers
     *
     * @return string
     * @throws AuthenticationException
     */
    public function getIdempotencyKey(): string
    {
        $key = $this->getHeader('Idempotency-Key');
        if (empty($key)) {
            throw new AuthenticationException(__('Idempotency-Key header is required'));
        }
        return $key;
    }

    /**
     * Get request ID from headers
     *
     * @return string
     * @throws AuthenticationException
     */
    public function getRequestId(): string
    {
        $requestId = $this->getHeader('Request-Id');
        if (empty($requestId)) {
            throw new AuthenticationException(__('Request-Id header is required'));
        }
        return $requestId;
    }

    /**
     * Get timestamp from headers
     *
     * @return int
     * @throws AuthenticationException
     */
    public function getTimestamp(): int
    {
        $timestamp = $this->getHeader('Timestamp');
        if (empty($timestamp)) {
            throw new AuthenticationException(__('Timestamp header is required'));
        }

        if (!is_numeric($timestamp)) {
            throw new AuthenticationException(__('Timestamp must be a valid Unix timestamp'));
        }

        return (int)$timestamp;
    }

    /**
     * Get signature from headers
     *
     * @return string|null
     */
    public function getSignature(): ?string
    {
        return $this->getHeader('Signature');
    }

    /**
     * Get API version from headers
     *
     * @return string|null
     */
    public function getApiVersion(): ?string
    {
        return $this->getHeader('API-Version');
    }
}
