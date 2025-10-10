<?php
/**
 * @author    Run_As_Root <hello@run-as-root.sh>
 * @copyright 2025 Run_As_Root
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Test\Unit\Model\Auth;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\TestCase;
use RunAsRoot\AgenticCommerceProtocol\Logger\Logger;
use RunAsRoot\AgenticCommerceProtocol\Model\Auth\IdempotencyManager;

final class IdempotencyManagerTest extends TestCase
{
    private IdempotencyManager $idempotencyManager;
    private CacheInterface $cache;
    private SerializerInterface $serializer;
    private Logger $logger;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->logger = $this->createMock(Logger::class);

        $this->idempotencyManager = new IdempotencyManager(
            $this->cache,
            $this->serializer,
            $this->logger
        );
    }

    public function test_get_response_returns_null_for_new_key(): void
    {
        $idempotencyKey = 'unique-key-12345';
        $this->cache->method('load')->willReturn(false);

        $result = $this->idempotencyManager->getResponse($idempotencyKey);

        $this->assertNull($result);
    }

    public function test_get_response_returns_cached_response_for_existing_key(): void
    {
        $idempotencyKey = 'duplicate-key-67890';
        $cachedResponse = ['checkout_session_id' => 'cs_abc123', 'status' => 'completed'];

        $this->cache->method('load')->willReturn('serialized_data');
        $this->serializer->method('unserialize')->willReturn($cachedResponse);

        $result = $this->idempotencyManager->getResponse($idempotencyKey);

        $this->assertEquals($cachedResponse, $result);
    }

    public function test_store_response_saves_to_cache(): void
    {
        $idempotencyKey = 'new-key-xyz';
        $response = ['checkout_session_id' => 'cs_new123'];

        $this->serializer->method('serialize')->willReturn('serialized_response');

        $this->cache->expects($this->once())
            ->method('save')
            ->with(
                'serialized_response',
                $this->stringContains('acp_idempotency_'),
                ['acp_idempotency'],
                86400
            );

        $this->idempotencyManager->storeResponse($idempotencyKey, $response);
    }

    public function test_exists_returns_false_for_new_key(): void
    {
        $this->cache->method('load')->willReturn(false);

        $result = $this->idempotencyManager->exists('new-key');

        $this->assertFalse($result);
    }

    public function test_exists_returns_true_for_existing_key(): void
    {
        $this->cache->method('load')->willReturn('some_cached_data');

        $result = $this->idempotencyManager->exists('existing-key');

        $this->assertTrue($result);
    }

    public function test_validate_format_rejects_short_keys(): void
    {
        $result = $this->idempotencyManager->validateFormat('short');

        $this->assertFalse($result);
    }

    public function test_validate_format_rejects_too_long_keys(): void
    {
        $tooLong = str_repeat('a', 256);

        $result = $this->idempotencyManager->validateFormat($tooLong);

        $this->assertFalse($result);
    }

    public function test_validate_format_accepts_valid_keys(): void
    {
        $validKey = 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6'; // 36 chars (UUID-like)

        $result = $this->idempotencyManager->validateFormat($validKey);

        $this->assertTrue($result);
    }
}
