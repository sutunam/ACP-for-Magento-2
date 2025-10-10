<?php
/**
 * @author    Run_As_Root <hello@run-as-root.sh>
 * @copyright 2025 Run_As_Root
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Test\Unit\Model\Auth;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\AuthenticationException;
use PHPUnit\Framework\TestCase;
use RunAsRoot\AgenticCommerceProtocol\Logger\Logger;
use RunAsRoot\AgenticCommerceProtocol\Model\Auth\HeaderValidator;

final class HeaderValidatorTest extends TestCase
{
    private HeaderValidator $headerValidator;
    private RequestInterface $request;
    private Logger $logger;

    protected function setUp(): void
    {
        $this->request = $this->createMock(RequestInterface::class);
        $this->logger = $this->createMock(Logger::class);

        $this->headerValidator = new HeaderValidator(
            $this->request,
            $this->logger
        );
    }

    public function test_validate_throws_exception_when_idempotency_key_missing(): void
    {
        $this->request->method('getHeader')->willReturnMap([
            ['Idempotency-Key', null],
            ['Request-Id', 'req-123'],
            ['Timestamp', '1696348800'],
        ]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessageMatches('/Idempotency-Key/');

        $this->headerValidator->validate();
    }

    public function test_validate_throws_exception_when_request_id_missing(): void
    {
        $this->request->method('getHeader')->willReturnMap([
            ['Idempotency-Key', 'idem-key-123'],
            ['Request-Id', null],
            ['Timestamp', '1696348800'],
        ]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessageMatches('/Request-Id/');

        $this->headerValidator->validate();
    }

    public function test_validate_throws_exception_when_timestamp_missing(): void
    {
        $this->request->method('getHeader')->willReturnMap([
            ['Idempotency-Key', 'idem-key-123'],
            ['Request-Id', 'req-123'],
            ['Timestamp', null],
        ]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessageMatches('/Timestamp/');

        $this->headerValidator->validate();
    }

    public function test_validate_returns_headers_when_all_required_present(): void
    {
        $this->request->method('getHeader')->willReturnMap([
            ['Idempotency-Key', 'idem-key-abc123'],
            ['Request-Id', 'req-xyz789'],
            ['Timestamp', '1696348800'],
            ['Signature', null],
            ['API-Version', null],
        ]);

        $result = $this->headerValidator->validate();

        $this->assertArrayHasKey('Idempotency-Key', $result);
        $this->assertArrayHasKey('Request-Id', $result);
        $this->assertArrayHasKey('Timestamp', $result);
        $this->assertEquals('idem-key-abc123', $result['Idempotency-Key']);
    }

    public function test_get_timestamp_returns_integer(): void
    {
        $this->request->method('getHeader')->willReturn('1696348800');

        $result = $this->headerValidator->getTimestamp();

        $this->assertIsInt($result);
        $this->assertEquals(1696348800, $result);
    }

    public function test_get_timestamp_throws_exception_for_non_numeric(): void
    {
        $this->request->method('getHeader')->willReturn('not-a-number');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessageMatches('/valid Unix timestamp/');

        $this->headerValidator->getTimestamp();
    }

    public function test_validate_includes_optional_headers_when_present(): void
    {
        $this->request->method('getHeader')->willReturnMap([
            ['Idempotency-Key', 'idem-123'],
            ['Request-Id', 'req-123'],
            ['Timestamp', '1696348800'],
            ['Signature', 'hmac-signature-here'],
            ['API-Version', 'v1'],
        ]);

        $result = $this->headerValidator->validate();

        $this->assertArrayHasKey('Signature', $result);
        $this->assertArrayHasKey('API-Version', $result);
        $this->assertEquals('hmac-signature-here', $result['Signature']);
        $this->assertEquals('v1', $result['API-Version']);
    }
}
