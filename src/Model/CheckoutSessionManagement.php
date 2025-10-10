<?php
/**
 * @author    Run_As_Root <hello@run-as-root.sh>
 * @copyright 2025 Run_As_Root
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use RunAsRoot\AgenticCommerceProtocol\Api\CheckoutSessionManagementInterface;
use RunAsRoot\AgenticCommerceProtocol\Api\Data\CheckoutSessionInterface;
use RunAsRoot\AgenticCommerceProtocol\Api\Data\CheckoutSessionInterfaceFactory;

/**
 * ACP Checkout Session Management Implementation
 */
class CheckoutSessionManagement implements CheckoutSessionManagementInterface
{
    private array $sessions = [];

    public function __construct(
        private readonly CheckoutSessionInterfaceFactory $checkoutSessionFactory,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function create($data): CheckoutSessionInterface
    {
        $requestData = is_string($data) ? json_decode($data, true) : $data;
        
        if (!isset($requestData['items']) || empty($requestData['items'])) {
            throw new LocalizedException(__('Items are required to create a checkout session'));
        }

        $checkoutSessionId = $this->generateSessionId();
        
        $session = $this->checkoutSessionFactory->create();
        $session->setCheckoutSessionId($checkoutSessionId);
        $session->setStatus('open');
        $session->setItems($requestData['items']);
        $session->setCurrency($this->storeManager->getStore()->getCurrentCurrency()->getCode());
        $session->setTotal($this->calculateTotal($requestData['items']));
        $session->setData('created_at', date('c'));
        $session->setData('updated_at', date('c'));

        $this->sessions[$checkoutSessionId] = $session;

        return $session;
    }

    public function update(string $checkoutSessionId, $data): CheckoutSessionInterface
    {
        $session = $this->get($checkoutSessionId);
        
        $requestData = is_string($data) ? json_decode($data, true) : $data;

        if (isset($requestData['items'])) {
            $session->setItems($requestData['items']);
            $session->setTotal($this->calculateTotal($requestData['items']));
        }

        $session->setData('updated_at', date('c'));
        $this->sessions[$checkoutSessionId] = $session;

        return $session;
    }

    public function get(string $checkoutSessionId): CheckoutSessionInterface
    {
        if (!isset($this->sessions[$checkoutSessionId])) {
            throw new NoSuchEntityException(__('Checkout session with ID "%1" does not exist', $checkoutSessionId));
        }

        return $this->sessions[$checkoutSessionId];
    }

    public function complete(string $checkoutSessionId, $data): CheckoutSessionInterface
    {
        $session = $this->get($checkoutSessionId);

        if ($session->getStatus() !== 'open') {
            throw new LocalizedException(__('Cannot complete a checkout session that is not open'));
        }

        $session->setStatus('completed');
        $session->setData('updated_at', date('c'));
        $session->setData('completed_at', date('c'));

        $this->sessions[$checkoutSessionId] = $session;

        return $session;
    }

    public function cancel(string $checkoutSessionId): CheckoutSessionInterface
    {
        $session = $this->get($checkoutSessionId);

        if ($session->getStatus() === 'completed') {
            throw new LocalizedException(__('Cannot cancel a completed checkout session'));
        }

        $session->setStatus('canceled');
        $session->setData('updated_at', date('c'));
        $session->setData('canceled_at', date('c'));

        $this->sessions[$checkoutSessionId] = $session;

        return $session;
    }

    private function generateSessionId(): string
    {
        return 'cs_' . bin2hex(random_bytes(16));
    }

    private function calculateTotal(array $items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $quantity = $item['quantity'] ?? 1;
            $price = $item['price'] ?? 0.0;
            $total += $quantity * $price;
        }
        return $total;
    }
}
