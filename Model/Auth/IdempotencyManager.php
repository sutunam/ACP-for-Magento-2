<?php
/**
 * @author    run_as_root GmbH <info@run-as-root.sh>
 * @copyright 2025 run_as_root GmbH
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model\Auth;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use RunAsRoot\AgenticCommerceProtocol\Logger\Logger;

/**
 * Manages idempotency keys to prevent duplicate requests
 */
class IdempotencyManager
{
    private const CACHE_PREFIX = 'acp_idempotency_';
    private const CACHE_LIFETIME = 86400; // 24 hours

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly SerializerInterface $serializer,
        private readonly Logger $logger
    ) {
    }

    /**
     * Check if idempotency key already exists and return cached response
     *
     * @param string $idempotencyKey
     * @return array|null Cached response or null if key is new
     */
    public function getResponse(string $idempotencyKey): ?array
    {
        $cacheKey = $this->getCacheKey($idempotencyKey);
        $cachedData = $this->cache->load($cacheKey);

        if ($cachedData) {
            $this->logger->info("Idempotency key found: {$idempotencyKey}, returning cached response");
            return $this->serializer->unserialize($cachedData);
        }

        return null;
    }

    /**
     * Store response for idempotency key
     *
     * @param string $idempotencyKey
     * @param array $response
     * @return void
     */
    public function storeResponse(string $idempotencyKey, array $response): void
    {
        $cacheKey = $this->getCacheKey($idempotencyKey);
        $serializedResponse = $this->serializer->serialize($response);

        $this->cache->save(
            $serializedResponse,
            $cacheKey,
            ['acp_idempotency'],
            self::CACHE_LIFETIME
        );

        $this->logger->info("Stored response for idempotency key: {$idempotencyKey}");
    }

    /**
     * Check if idempotency key exists (without returning response)
     *
     * @param string $idempotencyKey
     * @return bool
     */
    public function exists(string $idempotencyKey): bool
    {
        $cacheKey = $this->getCacheKey($idempotencyKey);
        return (bool)$this->cache->load($cacheKey);
    }

    /**
     * Get cache key for idempotency key
     *
     * @param string $idempotencyKey
     * @return string
     */
    private function getCacheKey(string $idempotencyKey): string
    {
        return self::CACHE_PREFIX . md5($idempotencyKey);
    }

    /**
     * Validate idempotency key format
     *
     * @param string $idempotencyKey
     * @return bool
     */
    public function validateFormat(string $idempotencyKey): bool
    {
        // Idempotency keys should be UUIDs or similar unique strings
        // Minimum length to prevent trivial keys
        if (strlen($idempotencyKey) < 16) {
            $this->logger->warning("Idempotency key too short: {$idempotencyKey}");
            return false;
        }

        // Maximum length to prevent abuse
        if (strlen($idempotencyKey) > 255) {
            $this->logger->warning("Idempotency key too long");
            return false;
        }

        return true;
    }
}
