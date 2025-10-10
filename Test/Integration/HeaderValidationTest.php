<?php
/**
 * @author    Run_As_Root <hello@run-as-root.sh>
 * @copyright 2025 Run_As_Root
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Test\Integration;

use Magento\Framework\Webapi\Rest\Request;
use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * Integration test for ACP header validation
 * Tests that API properly validates all required security headers
 */
class HeaderValidationTest extends WebapiAbstract
{
    private const RESOURCE_PATH = '/V1/acp/checkout_sessions';

    /**
     * Test request fails without Idempotency-Key header
     */
    public function test_request_rejected_without_idempotency_key(): void
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST
            ]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Idempotency-Key/');

        $headers = [
            'Authorization' => 'Bearer test-key',
            // Missing Idempotency-Key
            'Request-Id' => 'test-req-123',
            'Timestamp' => (string)time(),
        ];

        $this->_webApiCall($serviceInfo, [
            'data' => ['items' => [['sku' => '24-MB01', 'quantity' => 1]]]
        ], null, $headers);
    }

    /**
     * Test request fails without Request-Id header
     */
    public function test_request_rejected_without_request_id(): void
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST
            ]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Request-Id/');

        $headers = [
            'Authorization' => 'Bearer test-key',
            'Idempotency-Key' => 'test-idem-456',
            // Missing Request-Id
            'Timestamp' => (string)time(),
        ];

        $this->_webApiCall($serviceInfo, [
            'data' => ['items' => [['sku' => '24-MB01', 'quantity' => 1]]]
        ], null, $headers);
    }

    /**
     * Test request fails without Timestamp header
     */
    public function test_request_rejected_without_timestamp(): void
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST
            ]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Timestamp/');

        $headers = [
            'Authorization' => 'Bearer test-key',
            'Idempotency-Key' => 'test-idem-789',
            'Request-Id' => 'test-req-789',
            // Missing Timestamp
        ];

        $this->_webApiCall($serviceInfo, [
            'data' => ['items' => [['sku' => '24-MB01', 'quantity' => 1]]]
        ], null, $headers);
    }

    /**
     * Test request fails with old timestamp (replay attack prevention)
     */
    public function test_request_rejected_with_old_timestamp(): void
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST
            ]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/too old/');

        $oldTimestamp = time() - 600; // 10 minutes ago (beyond 5min tolerance)

        $headers = [
            'Authorization' => 'Bearer test-key',
            'Idempotency-Key' => 'test-idem-old',
            'Request-Id' => 'test-req-old',
            'Timestamp' => (string)$oldTimestamp,
        ];

        $this->_webApiCall($serviceInfo, [
            'data' => ['items' => [['sku' => '24-MB01', 'quantity' => 1]]]
        ], null, $headers);
    }

    /**
     * Test request succeeds with all valid headers
     */
    public function test_request_succeeds_with_all_valid_headers(): void
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST
            ]
        ];

        $headers = [
            'Authorization' => 'Bearer test-api-key-12345',
            'Idempotency-Key' => 'test-idem-valid-' . uniqid(),
            'Request-Id' => 'test-req-valid-' . uniqid(),
            'Timestamp' => (string)time(),
        ];

        $response = $this->_webApiCall($serviceInfo, [
            'data' => ['items' => [['sku' => '24-MB01', 'quantity' => 1]]]
        ], null, $headers);

        $this->assertArrayHasKey('checkout_session_id', $response);
        $this->assertArrayHasKey('status', $response);
    }
}
