<?php
/**
 * @author    Run_As_Root <hello@run-as-root.sh>
 * @copyright 2025 Run_As_Root
 * @license   https://opensource.org/license/mit  MIT License
 * @link      https://run-as-root.sh/
 */

declare(strict_types=1);

namespace RunAsRoot\AgenticCommerceProtocol\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use RunAsRoot\AgenticCommerceProtocol\Model\Webhook\WebhookDispatcher;

/**
 * Observer for order creation event
 */
class OrderCreatedObserver implements ObserverInterface
{
    public function __construct(
        private readonly WebhookDispatcher $webhookDispatcher
    ) {
    }

    public function execute(Observer $observer): void
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();

        if (!$order || !$order->getId()) {
            return;
        }

        // Check if this is an ACP order
        $acpSessionId = $order->getData('acp_checkout_session_id');
        if (empty($acpSessionId)) {
            return;
        }

        $payload = [
            'checkout_session_id' => $acpSessionId,
            'order_id' => $order->getIncrementId(),
            'status' => $order->getStatus(),
            'state' => $order->getState(),
            'grand_total' => $order->getGrandTotal(),
            'currency' => $order->getOrderCurrencyCode(),
            'created_at' => $order->getCreatedAt()
        ];

        $this->webhookDispatcher->dispatch('order.created', $payload);
    }
}
