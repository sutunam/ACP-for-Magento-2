<?php
/**
 * @author    run_as_root GmbH <info@run-as-root.sh>
 * @copyright 2025 run_as_root GmbH
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Test\Integration;

use Magento\Framework\Webapi\Rest\Request;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * End-to-end integration test for ACP-compliant checkout flow
 * Tests full spec compliance with real Magento Quote integration
 */
class AcpCheckoutFlowTest extends WebapiAbstract
{
    private const RESOURCE_PATH = '/V1/acp/checkout_sessions';
    private const API_KEY = 'test-api-key-12345';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setApiKey();
    }

    /**
     * Test complete checkout flow: create → update → complete
     * Validates ACP spec compliance at each step
     */
    public function test_complete_checkout_flow_with_spec_compliant_responses(): void
    {
        // Step 1: Create session
        $createResponse = $this->createSession([
            'items' => [
                ['sku' => '24-MB01', 'quantity' => 2]
            ]
        ]);

        $this->assertAcpResponseSchema($createResponse);
        $this->assertEquals('not_ready_for_payment', $createResponse['status']);
        $this->assertArrayHasKey('line_items', $createResponse);
        $this->assertArrayHasKey('total_details', $createResponse);
        $this->assertEquals('stripe', $createResponse['payment_provider']);

        // Verify monetary values are integers (cents)
        $this->assertIsInt($createResponse['total_details']['total_amount']['amount']);

        $sessionId = $createResponse['checkout_session_id'];

        // Step 2: Update with address and buyer
        $updateResponse = $this->updateSession($sessionId, [
            'buyer' => [
                'email' => 'test@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe'
            ],
            'fulfillment_address' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'address_line1' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'country' => 'US'
            ]
        ]);

        // Should transition to ready_for_payment
        $this->assertEquals('ready_for_payment', $updateResponse['status']);
        $this->assertArrayHasKey('buyer', $updateResponse);
        $this->assertArrayHasKey('fulfillment_address', $updateResponse);
        $this->assertArrayHasKey('fulfillment_options', $updateResponse);
        $this->assertNotEmpty($updateResponse['fulfillment_options']);

        // Step 3: Select shipping method
        $firstShippingOption = $updateResponse['fulfillment_options'][0];
        $shippingResponse = $this->updateSession($sessionId, [
            'fulfillment_option_id' => $firstShippingOption['id']
        ]);

        $this->assertEquals($firstShippingOption['id'], $shippingResponse['selected_fulfillment_option_id']);

        // Verify totals include shipping
        $this->assertGreaterThan(
            $createResponse['total_details']['total_amount']['amount'],
            $shippingResponse['total_details']['total_amount']['amount']
        );

        // Step 4: Complete (would process payment in real scenario)
        // Note: This would fail without real payment processing, so we skip for now
    }

    /**
     * Test response schema matches ACP specification
     */
    public function test_create_response_has_all_required_acp_fields(): void
    {
        $response = $this->createSession([
            'items' => [
                ['sku' => '24-MB01', 'quantity' => 1]
            ]
        ]);

        // Required top-level fields per ACP spec
        $this->assertArrayHasKey('checkout_session_id', $response);
        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('payment_provider', $response);
        $this->assertArrayHasKey('currency', $response);
        $this->assertArrayHasKey('line_items', $response);
        $this->assertArrayHasKey('total_details', $response);

        // Validate line_items structure
        $this->assertIsArray($response['line_items']);
        $this->assertNotEmpty($response['line_items']);

        $lineItem = $response['line_items'][0];
        $this->assertArrayHasKey('item_id', $lineItem);
        $this->assertArrayHasKey('product_title', $lineItem);
        $this->assertArrayHasKey('sku', $lineItem);
        $this->assertArrayHasKey('quantity', $lineItem);
        $this->assertArrayHasKey('base_amount', $lineItem);
        $this->assertArrayHasKey('discount_amount', $lineItem);
        $this->assertArrayHasKey('tax_amount', $lineItem);
        $this->assertArrayHasKey('total_amount', $lineItem);

        // Validate amount structure (amount + currency)
        $this->assertArrayHasKey('amount', $lineItem['base_amount']);
        $this->assertArrayHasKey('currency', $lineItem['base_amount']);
        $this->assertIsInt($lineItem['base_amount']['amount']); // CRITICAL: Must be integer

        // Validate total_details structure
        $totals = $response['total_details'];
        $this->assertArrayHasKey('subtotal_amount', $totals);
        $this->assertArrayHasKey('shipping_amount', $totals);
        $this->assertArrayHasKey('tax_amount', $totals);
        $this->assertArrayHasKey('discount_amount', $totals);
        $this->assertArrayHasKey('total_amount', $totals);

        // All totals must be integers (cents)
        $this->assertIsInt($totals['subtotal_amount']['amount']);
        $this->assertIsInt($totals['shipping_amount']['amount']);
        $this->assertIsInt($totals['tax_amount']['amount']);
        $this->assertIsInt($totals['total_amount']['amount']);
    }

    /**
     * Test status transitions follow ACP spec
     */
    public function test_status_transitions_follow_acp_spec(): void
    {
        // Create starts as not_ready_for_payment
        $createResponse = $this->createSession([
            'items' => [['sku' => '24-MB01', 'quantity' => 1]]
        ]);

        $this->assertEquals('not_ready_for_payment', $createResponse['status']);

        // Adding address + buyer transitions to ready_for_payment
        $updateResponse = $this->updateSession($createResponse['checkout_session_id'], [
            'buyer' => [
                'email' => 'buyer@test.com',
                'first_name' => 'Jane',
                'last_name' => 'Smith'
            ],
            'fulfillment_address' => [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'address_line1' => '456 Oak Ave',
                'city' => 'Los Angeles',
                'state' => 'CA',
                'postal_code' => '90001',
                'country' => 'US'
            ]
        ]);

        $this->assertEquals('ready_for_payment', $updateResponse['status']);

        // Cancel transitions to cancelled
        $cancelResponse = $this->cancelSession($createResponse['checkout_session_id']);
        $this->assertEquals('cancelled', $cancelResponse['status']);
    }

    /**
     * Test fulfillment_options are returned when address is set
     */
    public function test_fulfillment_options_returned_with_shipping_address(): void
    {
        $createResponse = $this->createSession([
            'items' => [['sku' => '24-MB01', 'quantity' => 1]]
        ]);

        // Initially no fulfillment options
        $this->assertArrayNotHasKey('fulfillment_options', $createResponse);

        // Add shipping address
        $updateResponse = $this->updateSession($createResponse['checkout_session_id'], [
            'fulfillment_address' => [
                'first_name' => 'Test',
                'last_name' => 'User',
                'address_line1' => '789 Pine St',
                'city' => 'Chicago',
                'state' => 'IL',
                'postal_code' => '60601',
                'country' => 'US'
            ]
        ]);

        // Now should have fulfillment options
        $this->assertArrayHasKey('fulfillment_options', $updateResponse);
        $this->assertIsArray($updateResponse['fulfillment_options']);
        $this->assertNotEmpty($updateResponse['fulfillment_options']);

        $option = $updateResponse['fulfillment_options'][0];
        $this->assertArrayHasKey('id', $option);
        $this->assertArrayHasKey('type', $option);
        $this->assertArrayHasKey('amount', $option);
        $this->assertArrayHasKey('description', $option);

        // Amount must be integer (cents)
        $this->assertIsInt($option['amount']['amount']);
        $this->assertEquals('shipping', $option['type']);
    }

    // Helper methods

    private function createSession(array $data): array
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST
            ]
        ];

        return $this->_webApiCall($serviceInfo, ['data' => $data], null, $this->getHeaders());
    }

    private function updateSession(string $sessionId, array $data): array
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/' . $sessionId,
                'httpMethod' => Request::HTTP_METHOD_POST
            ]
        ];

        return $this->_webApiCall($serviceInfo, [
            'checkoutSessionId' => $sessionId,
            'data' => $data
        ], null, $this->getHeaders());
    }

    private function cancelSession(string $sessionId): array
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/' . $sessionId . '/cancel',
                'httpMethod' => Request::HTTP_METHOD_POST
            ]
        ];

        return $this->_webApiCall($serviceInfo, [
            'checkoutSessionId' => $sessionId
        ], null, $this->getHeaders());
    }

    private function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . self::API_KEY,
            'Idempotency-Key' => 'test-idem-' . uniqid(),
            'Request-Id' => 'test-req-' . uniqid(),
            'Timestamp' => (string)time(),
        ];
    }

    private function assertAcpResponseSchema(array $response): void
    {
        $this->assertArrayHasKey('checkout_session_id', $response);
        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('payment_provider', $response);
        $this->assertArrayHasKey('currency', $response);
    }

    private function setApiKey(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $configWriter = $objectManager->get(\Magento\Framework\App\Config\Storage\WriterInterface::class);
        $configWriter->save('acp/general/api_key', self::API_KEY);
    }
}
