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
 * Integration test for ACP Checkout Session API endpoints
 */
class CheckoutSessionApiTest extends WebapiAbstract
{
    private const RESOURCE_PATH = '/V1/acp/checkout_sessions';

    public function test_create_checkout_session_returns_session_with_open_status(): void
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST
            ]
        ];

        $requestData = [
            'items' => [
                ['sku' => 'TEST-001', 'quantity' => 1, 'price' => 99.99]
            ]
        ];

        $response = $this->_webApiCall($serviceInfo, ['data' => $requestData]);

        $this->assertArrayHasKey('checkout_session_id', $response);
        $this->assertEquals('open', $response['status']);
        $this->assertNotEmpty($response['checkout_session_id']);
        $this->assertEquals(99.99, $response['total']);
    }

    public function test_get_checkout_session_returns_existing_session(): void
    {
        // First create a session
        $createServiceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST
            ]
        ];

        $requestData = [
            'items' => [
                ['sku' => 'TEST-001', 'quantity' => 2, 'price' => 50.00]
            ]
        ];

        $createResponse = $this->_webApiCall($createServiceInfo, ['data' => $requestData]);
        $checkoutSessionId = $createResponse['checkout_session_id'];

        // Now get the session
        $getServiceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/' . $checkoutSessionId,
                'httpMethod' => Request::HTTP_METHOD_GET
            ]
        ];

        $getResponse = $this->_webApiCall($getServiceInfo, ['checkoutSessionId' => $checkoutSessionId]);

        $this->assertEquals($checkoutSessionId, $getResponse['checkout_session_id']);
        $this->assertEquals('open', $getResponse['status']);
        $this->assertEquals(100.00, $getResponse['total']);
    }

    public function test_update_checkout_session_recalculates_total(): void
    {
        // Create session
        $createServiceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST
            ]
        ];

        $createResponse = $this->_webApiCall($createServiceInfo, [
            'data' => ['items' => [['sku' => 'TEST-001', 'quantity' => 1, 'price' => 50.00]]]
        ]);

        $checkoutSessionId = $createResponse['checkout_session_id'];

        // Update session
        $updateServiceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/' . $checkoutSessionId,
                'httpMethod' => Request::HTTP_METHOD_POST
            ]
        ];

        $updateResponse = $this->_webApiCall($updateServiceInfo, [
            'checkoutSessionId' => $checkoutSessionId,
            'data' => ['items' => [['sku' => 'TEST-001', 'quantity' => 3, 'price' => 50.00]]]
        ]);

        $this->assertEquals(150.00, $updateResponse['total']);
    }

    public function test_complete_checkout_session_changes_status(): void
    {
        // Create session
        $createServiceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST
            ]
        ];

        $createResponse = $this->_webApiCall($createServiceInfo, [
            'data' => ['items' => [['sku' => 'TEST-001', 'quantity' => 1, 'price' => 99.99]]]
        ]);

        $checkoutSessionId = $createResponse['checkout_session_id'];

        // Complete session
        $completeServiceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/' . $checkoutSessionId . '/complete',
                'httpMethod' => Request::HTTP_METHOD_POST
            ]
        ];

        $completeResponse = $this->_webApiCall($completeServiceInfo, [
            'checkoutSessionId' => $checkoutSessionId,
            'data' => ['payment_data' => ['token' => 'test_token']]
        ]);

        $this->assertEquals('completed', $completeResponse['status']);
    }

    public function test_cancel_checkout_session_changes_status(): void
    {
        // Create session
        $createServiceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST
            ]
        ];

        $createResponse = $this->_webApiCall($createServiceInfo, [
            'data' => ['items' => [['sku' => 'TEST-001', 'quantity' => 1, 'price' => 99.99]]]
        ]);

        $checkoutSessionId = $createResponse['checkout_session_id'];

        // Cancel session
        $cancelServiceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/' . $checkoutSessionId . '/cancel',
                'httpMethod' => Request::HTTP_METHOD_POST
            ]
        ];

        $cancelResponse = $this->_webApiCall($cancelServiceInfo, [
            'checkoutSessionId' => $checkoutSessionId
        ]);

        $this->assertEquals('canceled', $cancelResponse['status']);
    }
}
