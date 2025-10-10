<?php
/**
 * @author    run_as_root GmbH <info@run-as-root.sh>
 * @copyright 2025 run_as_root GmbH
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Test\Unit\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use RunAsRoot\AgenticCommerceProtocol\Api\Data\CheckoutSessionInterface;
use RunAsRoot\AgenticCommerceProtocol\Api\Data\CheckoutSessionInterfaceFactory;
use RunAsRoot\AgenticCommerceProtocol\Model\CheckoutSessionManagement;
use RunAsRoot\AgenticCommerceProtocol\Model\Checkout\Session;

final class CheckoutSessionManagementTest extends TestCase
{
    private CheckoutSessionManagement $checkoutSessionManagement;
    private CheckoutSessionInterfaceFactory $checkoutSessionFactory;
    private StoreManagerInterface $storeManager;

    protected function setUp(): void
    {
        $this->checkoutSessionFactory = $this->createMock(CheckoutSessionInterfaceFactory::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        
        $store = $this->createMock(StoreInterface::class);
        $currency = $this->createMock(\Magento\Directory\Model\Currency::class);
        $currency->method('getCode')->willReturn('USD');
        $store->method('getCurrentCurrency')->willReturn($currency);
        $this->storeManager->method('getStore')->willReturn($store);

        $this->checkoutSessionManagement = new CheckoutSessionManagement(
            $this->checkoutSessionFactory,
            $this->storeManager
        );
    }

    public function test_create_throws_exception_when_items_missing(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Items are required to create a checkout session');

        $this->checkoutSessionManagement->create(['foo' => 'bar']);
    }

    public function test_create_returns_checkout_session_with_open_status(): void
    {
        $session = new Session();
        $this->checkoutSessionFactory->method('create')->willReturn($session);

        $data = ['items' => [['sku' => 'TEST-001', 'quantity' => 1, 'price' => 99.99]]];
        $result = $this->checkoutSessionManagement->create($data);

        $this->assertEquals('open', $result->getStatus());
        $this->assertNotEmpty($result->getCheckoutSessionId());
        $this->assertEquals('USD', $result->getCurrency());
    }

    public function test_create_calculates_total_from_items(): void
    {
        $session = new Session();
        $this->checkoutSessionFactory->method('create')->willReturn($session);

        $data = [
            'items' => [
                ['sku' => 'TEST-001', 'quantity' => 2, 'price' => 50.00],
                ['sku' => 'TEST-002', 'quantity' => 1, 'price' => 30.00]
            ]
        ];
        
        $result = $this->checkoutSessionManagement->create($data);

        $this->assertEquals(130.00, $result->getTotal());
    }

    public function test_get_throws_exception_for_non_existent_session(): void
    {
        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('Checkout session with ID "invalid" does not exist');

        $this->checkoutSessionManagement->get('invalid');
    }

    public function test_update_modifies_existing_session(): void
    {
        $session = new Session();
        $this->checkoutSessionFactory->method('create')->willReturn($session);

        $data = ['items' => [['sku' => 'TEST-001', 'quantity' => 1, 'price' => 99.99]]];
        $created = $this->checkoutSessionManagement->create($data);

        $updateData = ['items' => [['sku' => 'TEST-001', 'quantity' => 3, 'price' => 99.99]]];
        $updated = $this->checkoutSessionManagement->update($created->getCheckoutSessionId(), $updateData);

        $this->assertEquals(299.97, $updated->getTotal());
    }

    public function test_complete_changes_status_to_completed(): void
    {
        $session = new Session();
        $this->checkoutSessionFactory->method('create')->willReturn($session);

        $data = ['items' => [['sku' => 'TEST-001', 'quantity' => 1, 'price' => 99.99]]];
        $created = $this->checkoutSessionManagement->create($data);

        $completed = $this->checkoutSessionManagement->complete($created->getCheckoutSessionId(), []);

        $this->assertEquals('completed', $completed->getStatus());
    }

    public function test_complete_throws_exception_for_non_open_session(): void
    {
        $session = new Session();
        $this->checkoutSessionFactory->method('create')->willReturn($session);

        $data = ['items' => [['sku' => 'TEST-001', 'quantity' => 1, 'price' => 99.99]]];
        $created = $this->checkoutSessionManagement->create($data);
        $this->checkoutSessionManagement->complete($created->getCheckoutSessionId(), []);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Cannot complete a checkout session that is not open');

        $this->checkoutSessionManagement->complete($created->getCheckoutSessionId(), []);
    }

    public function test_cancel_changes_status_to_canceled(): void
    {
        $session = new Session();
        $this->checkoutSessionFactory->method('create')->willReturn($session);

        $data = ['items' => [['sku' => 'TEST-001', 'quantity' => 1, 'price' => 99.99]]];
        $created = $this->checkoutSessionManagement->create($data);

        $canceled = $this->checkoutSessionManagement->cancel($created->getCheckoutSessionId());

        $this->assertEquals('canceled', $canceled->getStatus());
    }

    public function test_cancel_throws_exception_for_completed_session(): void
    {
        $session = new Session();
        $this->checkoutSessionFactory->method('create')->willReturn($session);

        $data = ['items' => [['sku' => 'TEST-001', 'quantity' => 1, 'price' => 99.99]]];
        $created = $this->checkoutSessionManagement->create($data);
        $this->checkoutSessionManagement->complete($created->getCheckoutSessionId(), []);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Cannot cancel a completed checkout session');

        $this->checkoutSessionManagement->cancel($created->getCheckoutSessionId());
    }
}
